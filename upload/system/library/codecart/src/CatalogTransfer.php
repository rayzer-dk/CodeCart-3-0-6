<?php
namespace CodeCart\Core;

/**
 * Safe catalog import/export for CodeCart/OpenCart 3.x.
 * Uses logical table names (without DB_PREFIX), schema intersection and language/store mapping.
 * Default import is non-destructive upsert: it never TRUNCATEs or DELETEs catalog tables.
 */
final class CatalogTransfer {
    const FORMAT_VERSION = '1';
    const MAX_UPLOAD_BYTES = 268435456; // 256 MB
    const MAX_PACKAGE_IMAGES = 20000;
    const MAX_PACKAGE_IMAGE_BYTES = 201326592; // 192 MB total uncompressed image budget
    const MAX_SINGLE_IMAGE_BYTES = 20971520; // 20 MB per image
    const MAX_PACKAGE_DOWNLOAD_BYTES = 268435456; // 256 MB for digital files
    const MAX_SINGLE_DOWNLOAD_BYTES = 67108864; // 64 MB per digital file

    private $registry;
    private $db;
    private $config;
    private $log;
    private $progressCallback;
    private $badProductIds = array();
    private $productProblems = array();
    private $progressCounts = array();
    private $productNames = array();
    private $productSkus = array();
    private $validDownloadIds = array();
    private $incomingDownloadNames = array();
    private $incomingDownloadLinks = array();
    private $importOptions = array('status'=>'preserve','fields'=>array());

    /**
     * Per-operation rules. No persistent setting and no implicit changes to
     * existing product fields unless the administrator explicitly selects it.
     */
    public function setImportOptions(array $options) {
        $mode = isset($options['status']) ? (string)$options['status'] : 'preserve';
        if (!in_array($mode, array('preserve','enabled','disabled'), true)) {
            throw new \InvalidArgumentException('Invalid imported product status rule.');
        }
        $types = array(
            'model'=>64,'sku'=>64,'mpn'=>64,'upc'=>12,'ean'=>14,'jan'=>13,
            'isbn'=>17,'location'=>128,'quantity'=>0,'price'=>0
        );
        $normalized = array('status'=>$mode,'fields'=>array());
        $rules = isset($options['fields']) && is_array($options['fields']) ? $options['fields'] : array();
        foreach ($rules as $field=>$rule) {
            if (!array_key_exists($field, $types) || !is_array($rule)) {
                throw new \InvalidArgumentException('Unknown product import fallback field.');
            }
            $policy = isset($rule['mode']) ? (string)$rule['mode'] : 'preserve';
            if (!in_array($policy, array('preserve','empty','custom'), true)) {
                throw new \InvalidArgumentException('Invalid import fallback mode for ' . $field);
            }
            if ($policy === 'preserve') { continue; }
            $value = '';
            if ($policy === 'custom') {
                $value = isset($rule['value']) && is_scalar($rule['value']) ? trim((string)$rule['value']) : '';
                if ($value === '') {
                    throw new \InvalidArgumentException('Custom fallback is empty for ' . $field);
                }
                if (preg_match('//u', $value) !== 1) {
                    throw new \InvalidArgumentException('Custom fallback is not valid UTF-8: ' . $field);
                }
            }
            if ($field === 'quantity') {
                if ($policy === 'custom' && (!preg_match('/^[0-9]{1,9}$/D', $value) || (float)$value > 999999999)) {
                    throw new \InvalidArgumentException('Quantity must be a non-negative whole number.');
                }
                $value = $policy === 'empty' ? '0' : (string)(int)$value;
            } elseif ($field === 'price') {
                if ($policy === 'custom' && (!preg_match('/^(?:[0-9]{1,10})(?:\\.[0-9]{1,4})?$/D', $value) || (float)$value > 9999999999)) {
                    throw new \InvalidArgumentException('Price must be a non-negative decimal using a dot.');
                }
                $value = $policy === 'empty' ? '0' : $value;
            } elseif ($policy === 'custom' && strlen($value) > $types[$field]) {
                throw new \InvalidArgumentException('Custom value is too long for ' . $field);
            }
            $normalized['fields'][$field] = array('mode'=>$policy,'value'=>$value);
        }
        $this->importOptions = $normalized;
        return $this;
    }

    private function applyProductImportOptions(array $row, array $schema) {
        $available = isset($schema['columns']) ? $schema['columns'] : array();
        foreach ($this->importOptions['fields'] as $field=>$rule) {
            if (!isset($available[$field])) { continue; }
            if (!array_key_exists($field, $row) || $row[$field] === null || trim((string)$row[$field]) === '') {
                $row[$field] = $rule['value'];
            }
        }
        if ($this->importOptions['status'] !== 'preserve' && isset($available['status'])) {
            $row['status'] = $this->importOptions['status'] === 'enabled' ? '1' : '0';
        }
        return $row;
    }

    private function requiredColumnHasFallback($sourceLogical, $column) {
        return $sourceLogical === 'product' &&
            (isset($this->importOptions['fields'][$column]) ||
            ($column === 'status' && $this->importOptions['status'] !== 'preserve'));
    }

    private function validatePreviewImportOptions(array &$result) {
        $result['import_options'] = $this->importOptions;
        if ($this->importOptions['status'] !== 'preserve' && !$this->tableExists('product')) {
            $result['errors'][] = 'Cannot set imported product status: target product table is missing.';
        }
        $schema = $this->tableExists('product') ? $this->schema('product') : array('columns'=>array());
        if ($this->importOptions['status'] !== 'preserve' && !isset($schema['columns']['status'])) {
            $result['errors'][] = 'Target product table does not contain the status field.';
        }
        foreach ($this->importOptions['fields'] as $field=>$rule) {
            if (!isset($schema['columns'][$field])) {
                $result['errors'][] = 'Target product table does not contain the field ' . $field . '.';
            }
        }
    }


    public function onProgress(callable $callback = null) {
        $this->progressCallback = $callback;
        return $this;
    }

    private function publishProgress($stage, $force = false) {
        if (!$this->progressCallback) { return; }
        $counts = $this->progressCounts;
        $processed = isset($counts['processed']) ? (int)$counts['processed'] : 0;
        if (!$force && $processed % 50 !== 0) { return; }
        $total = isset($counts['total']) ? (int)$counts['total'] : 0;
        $counts['stage'] = $stage;
        $counts['percent'] = $total ? min(95, (int)round($processed * 90 / $total) + 5) : 5;
        $counts['issues'] = array_values($this->productProblems);
        try {
            call_user_func($this->progressCallback, $counts);
        } catch (\Throwable $e) {
            // Status reporting is auxiliary: failure must not corrupt or stop
            // an otherwise valid catalog transfer.
            if ($this->log) { try { $this->log->write('Catalog progress update failed.'); } catch (\Throwable $ignored) {} }
        }
    }

    private function rejectProduct($id, $reason, $row = array()) {
        $id = (int)$id;
        if ($id < 1 || isset($this->badProductIds[$id])) { return; }
        $this->badProductIds[$id] = true;
        $this->productProblems[$id] = array(
            'product_id' => $id,
            'sku' => isset($row['sku']) ? (string)$row['sku'] : (isset($this->productSkus[$id]) ? $this->productSkus[$id] : ''),
            'name' => isset($this->productNames[$id]) ? $this->productNames[$id] : '',
            'reason' => (string)$reason
        );
    }

    private function invalidTextColumn(array $row) {
        foreach ($row as $column => $value) {
            if ($value !== null && is_string($value) && preg_match('//u', $value) !== 1) {
                return (string)$column;
            }
        }
        return '';
    }

    private function diagnoseExportProducts(array $tables, $portable) {
        $this->badProductIds = array();
        $this->productProblems = array();
        $this->productSkus = array();
        $this->productNames = array();
        if (!in_array('product', $tables, true) || !$this->tableExists('product')) { return; }
        $all = $this->db->query('SELECT COUNT(*) AS total FROM `' . $this->table('product') . '`');
        $total = isset($all->row['total']) ? (int)$all->row['total'] : 0;
        $this->progressCounts = array('total'=>$total,'processed'=>0,'success'=>0,'skipped'=>0,'fixed'=>0);
        $this->publishProgress('checking', true);
        $scan = array('product');
        foreach ($tables as $t) {
            if ($t !== 'product' && ($t === 'product_description' || strpos($t, 'product_') === 0) && $this->tableExists($t)) {
                $scan[] = $t;
            }
        }
        foreach ($scan as $logical) {
            $schema = $this->schema($logical);
            if (!isset($schema['columns']['product_id'])) { continue; }
            $order = $schema['primary']
                ? ' ORDER BY ' . implode(',', array_map(function($c){return '`'.$c.'`';}, $schema['primary'])) : '';
            $offset = 0; $limit = 500;
            do {
                $rows = $this->db->query('SELECT * FROM `'.$this->table($logical).'`'.$order.' LIMIT '.$offset.','.$limit)->rows;
                foreach ($rows as $row) {
                    $id = isset($row['product_id']) ? (int)$row['product_id'] : 0;
                    if ($id <= 0) { continue; }
                    if ($logical === 'product') {
                        $this->productSkus[$id] = isset($row['sku']) ? (string)$row['sku'] : '';
                        $this->progressCounts['processed']++;
                        $this->publishProgress('checking');
                    }
                    if ($logical === 'product_description' && !isset($this->productNames[$id]) && isset($row['name']) && preg_match('//u',(string)$row['name']) === 1) {
                        $this->productNames[$id] = (string)$row['name'];
                    }
                    $badColumn = $this->invalidTextColumn($row);
                    if ($badColumn !== '') {
                        $this->rejectProduct($id, 'Invalid UTF-8: '.$logical.'.'.$badColumn, $row);
                        continue;
                    }
                    if ($portable) {
                        $images = array();
                        $this->collectImages($logical, $row, $images);
                        foreach (array_keys($images) as $relative) {
                            $full = rtrim(DIR_IMAGE,'/\\').DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$relative);
                            if (!is_file($full) || !is_readable($full)) {
                                $this->rejectProduct($id, 'Image file unavailable: '.$relative, $row);
                                break;
                            }
                        }
                    }
                }
                $n = count($rows);
                $offset += $limit;
            } while ($n === $limit);
        }
        // A broken digital download belongs to its product, not to the whole store.
        $this->validDownloadIds = array();
        if (in_array('product_to_download', $tables, true) && $this->tableExists('download')
            && $this->tableExists('product_to_download')) {
            $offset = 0; $limit = 500;
            do {
                $records = $this->db->query("SELECT pd.product_id,pd.download_id,d.filename
                    FROM `".$this->table('product_to_download')."` pd
                    LEFT JOIN `".$this->table('download')."` d ON d.download_id=pd.download_id
                    ORDER BY pd.product_id,pd.download_id LIMIT ".$offset.",".$limit)->rows;
                foreach ($records as $record) {
                    $id = (int)$record['product_id'];
                    if ($id <= 0 || isset($this->badProductIds[$id])) { continue; }
                    $name = isset($record['filename']) ? (string)$record['filename'] : '';
                    if (!$this->safeDownloadFilename($name)) {
                        $this->rejectProduct($id,'Invalid digital download filename');
                    } elseif ($portable) {
                        $path = rtrim(DIR_DOWNLOAD,'/\\').DIRECTORY_SEPARATOR.$name;
                        if (!is_file($path) || !is_readable($path)) {
                            $this->rejectProduct($id,'Digital file unavailable: '.$name);
                        } elseif ((int)filesize($path) > self::MAX_SINGLE_DOWNLOAD_BYTES) {
                            $this->rejectProduct($id,'Digital file exceeds 64 MB package limit: '.$name);
                        }
                    }
                }
                $n = count($records);
                $offset += $limit;
            } while ($n === $limit);
            // Resolve download IDs only after all product failures are known.
            $offset = 0;
            do {
                $rows = $this->db->query("SELECT product_id,download_id FROM `".$this->table('product_to_download')."`
                    ORDER BY product_id,download_id LIMIT ".$offset.",".$limit)->rows;
                foreach ($rows as $row) {
                    if (!isset($this->badProductIds[(int)$row['product_id']])) {
                        $this->validDownloadIds[(int)$row['download_id']] = true;
                    }
                }
                $n = count($rows);
                $offset += $limit;
            } while ($n === $limit);
        }
        foreach ($this->productProblems as $id => &$issue) {
            if ($issue['name'] === '' && isset($this->productNames[$id])) { $issue['name'] = $this->productNames[$id]; }
        }
        unset($issue);
        $this->progressCounts['processed'] = 0;
        $this->progressCounts['skipped'] = count($this->badProductIds);
        $this->publishProgress('exporting', true);
    }

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
        $this->log = $registry->get('log');
    }

    public function entityGroups() {
        return array(
            'products' => array(
                'product','product_description','product_extra_tab','product_to_category','product_image','product_option','product_option_value',
                'product_attribute','product_discount','product_special','product_reward','product_to_store','product_to_layout',
                'product_filter','product_to_download','download','download_description','product_recurring','product_related','product_related_article','product_related_wb','product_related_mn'
            ),
            'categories' => array(
                'category','category_description','category_path','category_to_store','category_to_layout','category_filter','product_related_wb'
            ),
            'manufacturers' => array('manufacturer','manufacturer_description','manufacturer_to_store','manufacturer_to_layout','product_related_mn'),
            'options' => array('option','option_description','option_value','option_value_description'),
            'attributes' => array('attribute_group','attribute_group_description','attribute','attribute_description'),
            'filters' => array('filter_group','filter_group_description','filter','filter_description')
        );
    }

    public function entityCounts() {
        $map = array('products'=>'product','categories'=>'category','manufacturers'=>'manufacturer','options'=>'option','attributes'=>'attribute','filters'=>'filter');
        $out = array();

        foreach ($map as $entity => $logical) {
            if (!$this->tableExists($logical)) {
                $out[$entity] = 0;
                continue;
            }

            try {
                $q = $this->db->query("SELECT COUNT(*) AS total FROM `" . $this->table($logical) . "`");
                $out[$entity] = (int)($q->row['total'] ?? 0);
            } catch (\Throwable $e) {
                $out[$entity] = 0;
                if ($this->log) {
                    $this->log->write('CodeCart PRO catalog counter failed [' . $logical . ']: ' . $e->getMessage());
                }
            }
        }

        return $out;
    }

    public function export(array $entities, $filename, $portable = false) {
        $entities = $this->normalizeEntities($entities);
        if (!$entities) { throw new \InvalidArgumentException('No catalog entity selected.'); }
        if (!SimpleXlsx::writeAvailable()) { throw new \RuntimeException('ZIP PHP extension is required for XLSX export.'); }

        $tables = $this->tablesForEntities($entities);
        $sheets = array();
        try {
            $metadataRows = $this->metadataRows($entities);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Catalog export failed while reading metadata. ' . $e->getMessage(), 0, $e);
        }
        $this->diagnoseExportProducts($tables, (bool)$portable);
        $metadataRows[] = array('type'=>'transfer','key'=>'skipped_product_count','value'=>(string)count($this->badProductIds));
        $sheets['_CodeCart'] = array('columns'=>array('type','key','value'), 'rows'=>$metadataRows);
        $imagePaths = array();

        foreach ($tables as $logical) {
            try {
                if (!$this->tableExists($logical)) { continue; }
                $schema = $this->schema($logical);
                $columns = array_keys($schema['columns']);
                if (($logical === 'seo_url' || $logical === 'url_alias') && !array_intersect($entities, array('products','categories','manufacturers'))) { continue; }
                $sheets[$logical] = array(
                    'columns' => $columns,
                    'rows' => function() use ($logical, $entities, &$imagePaths) {
                        return $this->exportRowsIterator($logical, $entities, $imagePaths);
                    }
                );
            } catch (\Throwable $e) {
                throw new \RuntimeException('Catalog export failed while preparing table ' . $logical . '. ' . $e->getMessage(), 0, $e);
            }
        }

        if ($portable) {
            $tmpBase = tempnam(DIR_UPLOAD, 'ccpxlsx_');
            if (!$tmpBase) { throw new \RuntimeException('Unable to create temporary workbook.'); }
            @unlink($tmpBase);
            $tmpXlsx = $tmpBase . '.xlsx';
        } else {
            $tmpXlsx = $filename;
        }
        if (!SimpleXlsx::writeStreaming($tmpXlsx, $sheets)) { throw new \RuntimeException('Unable to create catalog workbook.'); }
        $verifyZip = new \ZipArchive();
        if ($verifyZip->open($tmpXlsx) !== true) { @unlink($tmpXlsx); throw new \RuntimeException('Catalog workbook verification failed: invalid ZIP.'); }
        try {
            if ($verifyZip->locateName('xl/workbook.xml') === false || $verifyZip->locateName('[Content_Types].xml') === false) {
                throw new \RuntimeException('Catalog workbook verification failed: core OOXML entries are missing.');
            }
            $sheetNo = 1;
            foreach (array_keys($sheets) as $expectedSheet) {
                if ($verifyZip->locateName('xl/worksheets/sheet' . $sheetNo . '.xml') === false) { throw new \RuntimeException('Missing worksheet after write: ' . $expectedSheet); }
                $sheetNo++;
            }
        } finally { $verifyZip->close(); }
        if (!$portable) {
            $this->publishProgress('complete', true);
            return array('file'=>$filename,'tables'=>array_keys($sheets),'images'=>count($imagePaths),
                'products'=>$this->progressCounts,'issues'=>array_values($this->productProblems));
        }

        $packageImages = array(); $packageBytes = 0;
        foreach (array_keys($imagePaths) as $relative) {
            if (!$this->safeImagePath($relative)) { continue; }
            $full = rtrim(DIR_IMAGE, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if (!is_file($full)) {
                @unlink($tmpXlsx);
                throw new \RuntimeException('Image referenced by catalog is missing: ' . $relative);
            }
            $size = (int)filesize($full);
            if ($size <= 0) { continue; }
            if ($size > self::MAX_SINGLE_IMAGE_BYTES) {
                @unlink($tmpXlsx);
                throw new \RuntimeException('Portable package contains an image larger than the safe per-file limit: ' . $relative);
            }
            $packageImages[$relative] = array('file'=>$full, 'size'=>$size);
            $packageBytes += $size;
        }
        if (count($packageImages) > self::MAX_PACKAGE_IMAGES || $packageBytes > self::MAX_PACKAGE_IMAGE_BYTES) {
            @unlink($tmpXlsx);
            throw new \RuntimeException('Portable package is too large. Export XLSX without images or reduce the selected catalog scope.');
        }

        // A digital-product export is incomplete without the actual file, not only
        // product_to_download and download database records.
        $packageDownloads = array();
        $downloadBytes = 0;
        if (in_array('products', $entities, true) && $this->tableExists('download') && $this->tableExists('product_to_download')) {
            $sql = "SELECT DISTINCT d.download_id,d.filename FROM `" . $this->table('download') . "` d INNER JOIN `"
                . $this->table('product_to_download') . "` p ON p.download_id=d.download_id";
            foreach ($this->db->query($sql)->rows as $item) {
                if (!isset($this->validDownloadIds[(int)$item['download_id']])) { continue; }
                $name = (string)$item['filename'];
                if (!$this->safeDownloadFilename($name)) {
                    @unlink($tmpXlsx);
                    throw new \RuntimeException('Unsafe digital download filename encountered during export.');
                }
                $source = rtrim(DIR_DOWNLOAD, '/\\') . DIRECTORY_SEPARATOR . $name;
                if (!is_file($source) || !is_readable($source)) {
                    @unlink($tmpXlsx);
                    throw new \RuntimeException('Missing digital product file: ' . $name);
                }
                $size = (int)filesize($source);
                if ($size > self::MAX_SINGLE_DOWNLOAD_BYTES || $size < 0) {
                    @unlink($tmpXlsx);
                    throw new \RuntimeException('Digital product file exceeds the portable limit: ' . $name);
                }
                $downloadBytes += $size;
                if ($downloadBytes > self::MAX_PACKAGE_DOWNLOAD_BYTES) {
                    @unlink($tmpXlsx);
                    throw new \RuntimeException('Portable download archive limit exceeded; use a server-level backup for large digital files.');
                }
                $packageDownloads[$name] = $source;
            }
        }
        $zip = new \ZipArchive();
        if ($zip->open($filename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($tmpXlsx);
            throw new \RuntimeException('Unable to create portable catalog package.');
        }
        if (!$zip->addFile($tmpXlsx, 'catalog.xlsx')) { @unlink($tmpXlsx); $zip->close(); throw new \RuntimeException('Unable to add catalog.xlsx to portable package.'); }
        $count = 0; $bytes = 0;
        foreach ($packageImages as $relative=>$info) {
            if (!$zip->addFile($info['file'], 'image/' . $relative)) { $zip->close(); @unlink($tmpXlsx); throw new \RuntimeException('Unable to add image to portable package: ' . $relative); }
            $count++; $bytes += (int)$info['size'];
        }
        foreach ($packageDownloads as $name => $source) {
            if (!$zip->addFile($source, 'download/' . $name)) {
                $zip->close(); @unlink($tmpXlsx);
                throw new \RuntimeException('Unable to include digital product file: ' . $name);
            }
        }
        $manifest = array('format'=>'codecart-catalog-package','version'=>self::FORMAT_VERSION,'created'=>gmdate('c'),'images'=>$count,'bytes'=>$bytes,'downloads'=>count($packageDownloads),'download_bytes'=>$downloadBytes);
        if (!$zip->addFromString('manifest.json', json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))) { $zip->close(); @unlink($tmpXlsx); throw new \RuntimeException('Unable to add package manifest.'); }
        if (!$zip->close()) { @unlink($tmpXlsx); throw new \RuntimeException('Unable to finalize portable catalog package.'); }
        @unlink($tmpXlsx);
        $this->publishProgress('complete', true);
        return array('file'=>$filename,'tables'=>array_keys($sheets),'images'=>$count,'image_bytes'=>$bytes,
            'downloads'=>count($packageDownloads),'download_bytes'=>$downloadBytes,
            'products'=>$this->progressCounts,'issues'=>array_values($this->productProblems));
    }

    public function prepareUpload(array $file) {
        if (!isset($file['tmp_name'],$file['name'],$file['size']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('Upload is invalid.');
        }
        if ((int)$file['size'] <= 0 || (int)$file['size'] > self::MAX_UPLOAD_BYTES) {
            throw new \RuntimeException('Catalog file exceeds the allowed size.');
        }
        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, array('xlsx','zip'), true)) { throw new \RuntimeException('Only XLSX or CodeCart PRO ZIP package is supported.'); }
        $token = bin2hex(random_bytes(16));
        $base = DIR_UPLOAD . 'codecart_catalog_' . $token;
        $stored = $base . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $stored)) { throw new \RuntimeException('Unable to store uploaded catalog file.'); }
        @chmod($stored, 0600);
        $xlsx = $stored;
        $package = '';
        if ($ext === 'zip') {
            $package = $stored;
            $xlsx = $base . '.xlsx';
            $zip = new \ZipArchive();
            if ($zip->open($package) !== true) { @unlink($stored); throw new \RuntimeException('Invalid ZIP package.'); }
            try {
                $index = $zip->locateName('catalog.xlsx', \ZipArchive::FL_NOCASE);
                if ($index === false) { throw new \RuntimeException('Portable package does not contain catalog.xlsx.'); }
                $stat = $zip->statIndex($index);
                if (!$stat || (int)$stat['size'] <= 0 || (int)$stat['size'] > self::MAX_UPLOAD_BYTES) { throw new \RuntimeException('catalog.xlsx is invalid.'); }
                $stream = $zip->getStream($stat['name']);
                $out = fopen($xlsx, 'wb');
                if (!$stream || !$out) { throw new \RuntimeException('Unable to read catalog.xlsx.'); }
                stream_copy_to_stream($stream, $out, self::MAX_UPLOAD_BYTES + 1);
                fclose($stream); fclose($out);
            } finally { $zip->close(); }
        }
        $preview = $this->preflight($xlsx, $package);
        file_put_contents($base . '.json', json_encode(array('xlsx'=>$xlsx,'package'=>$package,
            'created'=>time(),'options'=>$this->importOptions), JSON_UNESCAPED_SLASHES));
        @chmod($base . '.json', 0600);
        $preview['token'] = $token;
        return $preview;
    }

    public function preflight($xlsx, $package = '') {
        if (SimpleXlsx::streamReadAvailable()) { return $this->preflightStreaming($xlsx, $package); }
        $workbook = SimpleXlsx::read($xlsx);
        if (!isset($workbook['_CodeCart'])) { throw new \RuntimeException('This workbook is not a CodeCart PRO catalog export.'); }
        $meta = $this->parseMetadata($workbook['_CodeCart']['rows']);
        $languageMap = $this->targetLanguageMap(isset($meta['languages']) ? $meta['languages'] : array());
        $storeMap = $this->targetStoreMap(isset($meta['stores']) ? $meta['stores'] : array());
        $result = array('ok'=>true,'errors'=>array(),'warnings'=>array(),'sheets'=>array(),'languages'=>$languageMap,'stores'=>$storeMap);
        $this->validatePreviewImportOptions($result);
        foreach ($languageMap as $sourceId => $targetId) {
            if ($targetId === null) {
                $result['errors'][] = 'Source language ID ' . (int)$sourceId . ' is missing on target. Install or map the source language before importing.';
            }
        }
        foreach ($storeMap as $sourceId => $targetId) {
            if ($targetId === null) {
                $result['errors'][] = 'Source store ID ' . (int)$sourceId . ' has no matching target store. Map stores before importing to avoid silent data loss.';
            }
        }
        $this->badProductIds = array();
        $this->productProblems = array();
        $this->productSkus = array();
        $this->productNames = array();
        $this->incomingDownloadNames = array();
        $this->incomingDownloadLinks = array();
        $imagePaths = array();
        $productImagePaths = array();
        $downloadFiles = array();

        foreach ($workbook as $sourceLogical => $sheet) {
            if ($sourceLogical === '_CodeCart') { continue; }
            if (!$this->isTransferTable($sourceLogical)) { $result['warnings'][] = 'Skipped unsupported sheet: ' . $sourceLogical; continue; }
            $targetLogical = $this->targetLogicalForSource($sourceLogical);
            if ($targetLogical === null) { $result['warnings'][] = 'Target table is missing and will be skipped: ' . DB_PREFIX . $sourceLogical; continue; }
            $schema = $this->schema($targetLogical);
            $sourceColumns = isset($sheet['columns']) ? $sheet['columns'] : array();
            $this->warnMainCategoryCapability($sourceLogical, $sourceColumns, $result);
            $seoSheet = $this->isSeoLogical($sourceLogical) && $this->isSeoLogical($targetLogical);
            if ($seoSheet) {
                if (!in_array('query', $sourceColumns, true) || !in_array('keyword', $sourceColumns, true)) {
                    $result['errors'][] = DB_PREFIX . $targetLogical . ': SEO sheet must contain query and keyword columns.';
                }
                $intersection = array_values(array_intersect($sourceColumns, array_keys($schema['columns'])));
                foreach (array('query','keyword') as $requiredSeo) {
                    if (isset($schema['columns'][$requiredSeo]) && !in_array($requiredSeo, $intersection, true)) { $intersection[] = $requiredSeo; }
                }
                if ($sourceLogical !== $targetLogical) {
                    $result['warnings'][] = 'SEO compatibility conversion: ' . $sourceLogical . ' -> ' . $targetLogical . '.';
                    if ($sourceLogical === 'url_alias' && $targetLogical === 'seo_url') {
                        $result['warnings'][] = 'Legacy url_alias has no language/store fields; imported SEO rows will use the primary target language and main store.';
                    }
                }
            } else {
                $intersection = array_values(array_intersect($sourceColumns, array_keys($schema['columns'])));
                $missingRequired = array();
                foreach ($schema['columns'] as $name=>$column) {
                    if (in_array($name, $intersection, true)) { continue; }
                    if ($column['required'] && !$this->requiredColumnHasFallback($sourceLogical, $name)) { $missingRequired[] = $name; }
                }
                if ($missingRequired) {
                    $result['errors'][] = DB_PREFIX . $targetLogical . ': required target columns are absent: ' . implode(', ', $missingRequired);
                }
                if (!$schema['primary']) {
                    $result['warnings'][] = DB_PREFIX . $targetLogical . ': table has no PRIMARY KEY and will be skipped to avoid duplicates.';
                } else {
                    foreach ($schema['primary'] as $pk) {
                        if (!in_array($pk, $intersection, true)) {
                            $result['errors'][] = DB_PREFIX . $targetLogical . ': primary key column is absent: ' . $pk . '. CodeCart PRO does not guess or remap catalog IDs during a safe import.';
                        }
                    }
                }
            }
            $ignored = array_values(array_diff($sourceColumns, array_keys($schema['columns'])));
            if ($seoSheet) { $ignored = array_values(array_diff($ignored, array('seo_url_id','url_alias_id','store_id','language_id'))); }
            if ($ignored) { $result['warnings'][] = DB_PREFIX . $targetLogical . ': unsupported source columns will be ignored: ' . implode(', ', $ignored); }
            foreach (isset($sheet['rows']) ? $sheet['rows'] : array() as $row) {
                $this->collectImages($sourceLogical, $row, $imagePaths);
                if (isset($row['product_id']) && (int)$row['product_id']>0) {
                    $images = array(); $this->collectImages($sourceLogical, $row, $images);
                    foreach (array_keys($images) as $img) { $productImagePaths[$img][(int)$row['product_id']] = true; }
                }
                if ($sourceLogical === 'product' && isset($row['product_id'])) {
                    $this->productSkus[(int)$row['product_id']] = isset($row['sku']) ? (string)$row['sku'] : '';
                }
                if ($sourceLogical === 'product_description' && isset($row['product_id'],$row['name'])) {
                    $this->productNames[(int)$row['product_id']] = (string)$row['name'];
                }
                if ($sourceLogical === 'download' && isset($row['filename'])) {
                    $downloadFiles[(string)$row['filename']] = true;
                    if (isset($row['download_id'])) {
                        $this->incomingDownloadNames[(int)$row['download_id']] = (string)$row['filename'];
                    }
                }
                if ($sourceLogical === 'product_to_download' && isset($row['download_id'],$row['product_id'])) {
                    $this->incomingDownloadLinks[(int)$row['download_id']][(int)$row['product_id']] = true;
                }
            }
            $result['sheets'][] = array('table'=>$sourceLogical,'target_table'=>$targetLogical,'rows'=>count(isset($sheet['rows'])?$sheet['rows']:array()),'columns'=>count($intersection),'ignored'=>$ignored,'primary'=>$schema['primary']);
        }

        $this->checkDownloadAvailability($downloadFiles, $package, $result);
        if (isset($workbook['product']['rows'])) {
            foreach (array_chunk($workbook['product']['rows'], 500) as $batch) {
                $this->checkProductIdentityBatch($batch, $result);
            }
        }
        $this->validateCatalogReferences($workbook, $result);
        $result['warnings'][] = 'ID policy: catalog primary IDs are preserved and are not remapped. Existing target rows with the same IDs will be updated. This is intended for backup/restore or migration of the same catalog lineage; review ID collisions before merging unrelated stores.';
        $result['warnings'][] = 'Relation policy: merge/update only. Existing product-category and other catalog relations that are absent from the workbook are preserved; import never deletes them.';

        if ($imagePaths) {
            $packageImages = $package && is_file($package) ? $this->packageImageIndex($package) : array();
            $missing = array();
            foreach (array_keys($imagePaths) as $relative) {
                $full = rtrim(DIR_IMAGE, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
                if (is_file($full) || isset($packageImages[$relative])) { continue; }
                if ($this->progressCallback && isset($productImagePaths[$relative])) {
                    foreach (array_keys($productImagePaths[$relative]) as $id) { $this->rejectProduct($id,'Missing image: '.$relative); }
                } elseif (count($missing) < 20) { $missing[] = $relative; }
            }
            if ($missing) { $result['errors'][] = 'Missing required image files: ' . implode(', ', $missing) . (count($missing) >= 20 ? ' ...' : '') . '. Use the portable ZIP or transfer the images first.'; }
        }
        foreach ($this->productProblems as $id => &$item) {
            if ($item['name']==='' && isset($this->productNames[$id])) { $item['name']=$this->productNames[$id]; }
        }
        unset($item);
        $total = isset($workbook['product']['rows']) ? count($workbook['product']['rows']) : 0;
        $result['total_products']=$total;
        $result['skipped_products']=count($this->badProductIds);
        $result['product_errors']=array_values($this->productProblems);
        $this->progressCounts=array('total'=>$total,'processed'=>0,'success'=>0,
            'skipped'=>count($this->badProductIds),'fixed'=>0);
        $result['ok'] = !$result['errors'];
        return $result;
    }

    public function importToken($token, $overwriteImages = false) {
        if (SimpleXlsx::streamReadAvailable()) { return $this->importTokenStreaming($token, $overwriteImages); }
        if (!preg_match('/^[a-f0-9]{32}$/', (string)$token)) { throw new \RuntimeException('Invalid import token.'); }
        $base = DIR_UPLOAD . 'codecart_catalog_' . $token;
        $stateFile = $base . '.json';
        if (!is_file($stateFile)) { throw new \RuntimeException('Import session expired or not found.'); }
        $state = json_decode((string)file_get_contents($stateFile), true);
        if (!is_array($state) || empty($state['xlsx']) || !is_file($state['xlsx']) || (time() - (int)$state['created']) > 3600) {
            $this->cleanupToken($token); throw new \RuntimeException('Import session expired.');
        }
        if (isset($state['options']) && $state['options'] !== $this->importOptions) {
            throw new \RuntimeException('Import rules changed after preview. Select the catalog file again to recheck with the new rules.');
        }
        $xlsx = $state['xlsx'];
        $package = !empty($state['package']) && is_file($state['package']) ? $state['package'] : '';
        $preview = $this->preflight($xlsx, $package);
        if (!$preview['ok']) { throw new \RuntimeException(implode('; ', $preview['errors'])); }
        $workbook = SimpleXlsx::read($xlsx);
        $meta = $this->parseMetadata($workbook['_CodeCart']['rows']);
        $languageMap = $this->targetLanguageMap(isset($meta['languages']) ? $meta['languages'] : array());
        $storeMap = $this->targetStoreMap(isset($meta['stores']) ? $meta['stores'] : array());
        $plans = array(); $backupTables = array();
        foreach ($workbook as $sourceLogical=>$sheet) {
            if ($sourceLogical === '_CodeCart' || !$this->isTransferTable($sourceLogical)) { continue; }
            $targetLogical = $this->targetLogicalForSource($sourceLogical);
            if ($targetLogical === null) { continue; }
            $schema = $this->schema($targetLogical);
            if (!$schema['primary'] && !$this->isSeoLogical($targetLogical)) { continue; }
            $plans[$sourceLogical] = $targetLogical;
            $backupTables[$targetLogical] = true;
        }
        $plans = $this->orderedPlans($plans);
        $mainCategoryMap = $this->extractMainCategoryMap($workbook);
        if ($mainCategoryMap) {
            if ($this->tableExists('product')) { $backupTables['product'] = true; }
            if ($this->tableExists('product_to_category')) { $backupTables['product_to_category'] = true; }
        }
        $backup = $this->createSqlSnapshot(array_keys($backupTables));
        $stats = array('inserted_or_updated'=>0,'skipped_rows'=>0,'tables'=>array(),'images'=>0,
            'main_categories'=>0,'backup'=>$backup,'warnings'=>$preview['warnings'],
            'total_products'=>(int)$preview['total_products'],'processed_products'=>0,
            'successful_products'=>0,'skipped_products'=>count($this->badProductIds),
            'product_errors'=>array_values($this->productProblems));
        $this->publishProgress('importing', true);
        $this->db->query('START TRANSACTION');
        try {
            foreach ($plans as $sourceLogical=>$targetLogical) {
                $sheet = $workbook[$sourceLogical];
                $targetLogical = $plans[$sourceLogical];
                $schema = $this->schema($targetLogical);
                $count = 0; $skipped = 0;
                foreach ($sheet['rows'] as $row) {
                    if ($this->ignoreRejectedProductRow($sourceLogical, $row)) {
                        if ($sourceLogical === 'product') {
                            $this->progressCounts['processed']++;
                            $this->publishProgress('importing');
                        }
                        $skipped++;
                        continue;
                    }
                    if ($sourceLogical === 'product') {
                        $this->progressCounts['processed']++;
                        $this->progressCounts['success']++;
                        $this->publishProgress('importing');
                    }
                    if ($this->isSeoLogical($sourceLogical) && $this->isSeoLogical($targetLogical)) {
                        $mapped = $this->mapSeoRow($sourceLogical, $targetLogical, $row, $languageMap, $storeMap);
                        if ($mapped === null) { $skipped++; continue; }
                        $this->upsertSeo($targetLogical, $mapped);
                    } else {
                        $columns = array_values(array_intersect($sheet['columns'], array_keys($schema['columns'])));
                        $mapped = $this->mapRow($sourceLogical, $row, $columns, $languageMap, $storeMap);
                        if ($mapped === null) { $skipped++; continue; }
                        if ($sourceLogical === 'product') {
                            $mapped = $this->applyProductImportOptions($mapped, $schema);
                        }
                        $this->upsert($targetLogical, $mapped, $schema);
                    }
                    $count++;
                }
                $stats['tables'][$sourceLogical] = $count;
                $stats['inserted_or_updated'] += $count;
                $stats['skipped_rows'] += $skipped;
            }
            if ($mainCategoryMap) {
                foreach (array_keys($this->badProductIds) as $id) { unset($mainCategoryMap[$id]); }
                $stats['main_categories'] = $this->applyMainCategoryMap($mainCategoryMap);
            }
            $this->db->query('COMMIT');
        } catch (\Throwable $e) {
            try { $this->db->query('ROLLBACK'); } catch (\Throwable $ignored) {}
            throw $e;
        }
        if ($package) { $stats['images'] = $this->importPackageImages($package, (bool)$overwriteImages); $stats['downloads'] = $this->importPackageDownloads($package); }
        $stats['processed_products']=(int)$this->progressCounts['processed'];
        $stats['successful_products']=(int)$this->progressCounts['success'];
        $this->publishProgress('complete', true);
        $this->cleanupToken($token);
        return $stats;
    }


    private function warnMainCategoryCapability($sourceLogical, array $sourceColumns, array &$result) {
        $hasSourceFlag = ($sourceLogical === 'product_to_category' && in_array('main_category', $sourceColumns, true))
            || ($sourceLogical === 'product' && in_array('main_category_id', $sourceColumns, true));
        if (!$hasSourceFlag) { return; }
        $productColumns = $this->tableExists('product') ? $this->schema('product')['columns'] : array();
        $relationColumns = $this->tableExists('product_to_category') ? $this->schema('product_to_category')['columns'] : array();
        if (!isset($productColumns['main_category_id']) && !isset($relationColumns['main_category'])) {
            $notice = 'Target OpenCart schema has no main-category field. All product-category memberships can be imported, but the primary-category designation cannot be preserved.';
            if (!in_array($notice, $result['warnings'], true)) { $result['warnings'][] = $notice; }
        }
    }

    private function checkProductIdentityBatch(array $rows, array &$result) {
        if (!$rows || !$this->tableExists('product')) { return; }
        $incoming = array();
        foreach ($rows as $row) {
            $id = isset($row['product_id']) ? (int)$row['product_id'] : 0;
            if ($id > 0) { $incoming[$id] = $row; }
        }
        if (!$incoming) { return; }
        $ids = array_keys($incoming);
        $sql = "SELECT product_id, sku, model FROM `" . $this->table('product') . "` WHERE product_id IN (" . implode(',', array_map('intval', $ids)) . ")";
        foreach ($this->db->query($sql)->rows as $target) {
            $id = (int)$target['product_id'];
            $source = $incoming[$id];
            $sourceSku = trim((string)(isset($source['sku']) ? $source['sku'] : ''));
            $targetSku = trim((string)$target['sku']);
            $sourceModel = trim((string)(isset($source['model']) ? $source['model'] : ''));
            $targetModel = trim((string)$target['model']);
            $mismatch = ($sourceSku !== '' && $targetSku !== '' && $sourceSku !== $targetSku)
                || ($sourceSku === '' && $targetSku === '' && $sourceModel !== ''
                    && $targetModel !== '' && $sourceModel !== $targetModel);
            if ($mismatch) {
                $reason = 'Product ID collision: target SKU/model differs from source.';
                if ($this->progressCallback) {
                    $this->rejectProduct($id, $reason, $source);
                } else {
                    $result['errors'][] = 'Product ID collision ' . $id . ': existing SKU/model does not match the imported item. Import is blocked to prevent overwriting a different product.';
                }
            }
        }
    }

    private function transferReferenceChecks() {
        return array(
            array('sheet'=>'product_to_category','column'=>'category_id','target'=>'category','target_key'=>'category_id','allow_zero'=>false,'label'=>'product category'),
            array('sheet'=>'product','column'=>'manufacturer_id','target'=>'manufacturer','target_key'=>'manufacturer_id','allow_zero'=>true,'label'=>'manufacturer'),
            array('sheet'=>'product_to_download','column'=>'download_id','target'=>'download','target_key'=>'download_id','allow_zero'=>false,'label'=>'download'),
            array('sheet'=>'product_to_download','column'=>'download_id','target'=>'download','target_key'=>'download_id','allow_zero'=>false,'label'=>'download'),
            array('sheet'=>'product_option','column'=>'option_id','target'=>'option','target_key'=>'option_id','allow_zero'=>false,'label'=>'product option'),
            array('sheet'=>'product_option_value','column'=>'option_value_id','target'=>'option_value','target_key'=>'option_value_id','allow_zero'=>false,'label'=>'option value'),
            array('sheet'=>'product_attribute','column'=>'attribute_id','target'=>'attribute','target_key'=>'attribute_id','allow_zero'=>false,'label'=>'attribute'),
            array('sheet'=>'product_filter','column'=>'filter_id','target'=>'filter','target_key'=>'filter_id','allow_zero'=>false,'label'=>'filter'),
            array('sheet'=>'product_extra_tab','column'=>'product_id','target'=>'product','target_key'=>'product_id','allow_zero'=>false,'label'=>'product'),
            array('sheet'=>'product_related','column'=>'product_id','target'=>'product','target_key'=>'product_id','allow_zero'=>false,'label'=>'related product source'),
            array('sheet'=>'product_related','column'=>'related_id','target'=>'product','target_key'=>'product_id','allow_zero'=>false,'label'=>'related product'),
            array('sheet'=>'product_related_article','column'=>'product_id','target'=>'product','target_key'=>'product_id','allow_zero'=>false,'label'=>'article related product'),
            array('sheet'=>'product_related_article','column'=>'article_id','target'=>'article','target_key'=>'article_id','allow_zero'=>false,'when_table'=>'product_related_article','label'=>'related article'),
            array('sheet'=>'manufacturer_description','column'=>'manufacturer_id','target'=>'manufacturer','target_key'=>'manufacturer_id','allow_zero'=>false,'label'=>'manufacturer'),
            array('sheet'=>'product_related_wb','column'=>'category_id','target'=>'category','target_key'=>'category_id','allow_zero'=>false,'label'=>'related category'),
            array('sheet'=>'product_related_wb','column'=>'product_id','target'=>'product','target_key'=>'product_id','allow_zero'=>false,'label'=>'category related product'),
            array('sheet'=>'product_related_mn','column'=>'manufacturer_id','target'=>'manufacturer','target_key'=>'manufacturer_id','allow_zero'=>false,'label'=>'related manufacturer'),
            array('sheet'=>'product_related_mn','column'=>'product_id','target'=>'product','target_key'=>'product_id','allow_zero'=>false,'label'=>'manufacturer related product')
        );
    }

    private function readStreamingMetadata($xlsx) {
        $rows = array();
        SimpleXlsx::streamSheet($xlsx, '_CodeCart', function($row) use (&$rows) { if (count($rows) < 1000) { $rows[] = $row; } });
        if (!$rows) { throw new \RuntimeException('This workbook is not a CodeCart PRO catalog export.'); }
        return $this->parseMetadata($rows);
    }

    private function preflightStreaming($xlsx, $package = '') {
        $sheetNames = SimpleXlsx::getSheetNames($xlsx);
        if (!in_array('_CodeCart', $sheetNames, true)) { throw new \RuntimeException('This workbook is not a CodeCart PRO catalog export.'); }
        $meta = $this->readStreamingMetadata($xlsx);
        $languageMap = $this->targetLanguageMap(isset($meta['languages']) ? $meta['languages'] : array());
        $storeMap = $this->targetStoreMap(isset($meta['stores']) ? $meta['stores'] : array());
        $result = array('ok'=>true,'errors'=>array(),'warnings'=>array(),'sheets'=>array(),'languages'=>$languageMap,'stores'=>$storeMap);
        $this->validatePreviewImportOptions($result);
        foreach ($languageMap as $sourceId => $targetId) {
            if ($targetId === null) {
                $result['errors'][] = 'Source language ID ' . (int)$sourceId . ' is missing on target. Install or map the source language before importing.';
            }
        }
        foreach ($storeMap as $sourceId => $targetId) {
            if ($targetId === null) {
                $result['errors'][] = 'Source store ID ' . (int)$sourceId . ' has no matching target store. Map stores before importing to avoid silent data loss.';
            }
        }
        $this->badProductIds = array();
        $this->productProblems = array();
        $this->productSkus = array();
        $this->productNames = array();
        $this->incomingDownloadNames = array();
        $this->incomingDownloadLinks = array();
        $imagePaths = array();
        $productImagePaths = array();
        $downloadFiles = array();
        $identityBatch = array();
        $checks = $this->transferReferenceChecks();
        $requiredRefs = array(); $availableRefs = array();
        $totalProducts = 0;
        foreach ($sheetNames as $sourceLogical) {
            if ($sourceLogical === '_CodeCart') { continue; }
            if (!$this->isTransferTable($sourceLogical)) { $result['warnings'][]='Skipped unsupported sheet: '.$sourceLogical; continue; }
            $targetLogical=$this->targetLogicalForSource($sourceLogical);
            if ($targetLogical===null) { $result['warnings'][]='Target table is missing and will be skipped: '.DB_PREFIX.$sourceLogical; continue; }
            $schema=$this->schema($targetLogical);
            $rowCount=0;
            $info=SimpleXlsx::streamSheet($xlsx,$sourceLogical,function($row) use ($sourceLogical,&$imagePaths,&$productImagePaths,&$downloadFiles,&$identityBatch,&$result,&$requiredRefs,&$availableRefs,$checks,&$rowCount,&$totalProducts){
                $rowCount++;
                $this->collectImages($sourceLogical,$row,$imagePaths);
                if (isset($row['product_id']) && (int)$row['product_id']>0) {
                    $relatedImages = array();
                    $this->collectImages($sourceLogical,$row,$relatedImages);
                    foreach (array_keys($relatedImages) as $img) {
                        $productImagePaths[$img][(int)$row['product_id']] = true;
                    }
                }
                if ($sourceLogical === 'product_description' && isset($row['product_id'],$row['name'])
                    && !isset($this->productNames[(int)$row['product_id']])) {
                    $this->productNames[(int)$row['product_id']] = (string)$row['name'];
                }
                if ($sourceLogical === 'download' && isset($row['filename'])) {
                    $downloadFiles[(string)$row['filename']] = true;
                    if (isset($row['download_id'])) {
                        $this->incomingDownloadNames[(int)$row['download_id']] = (string)$row['filename'];
                    }
                }
                if ($sourceLogical === 'product_to_download' && isset($row['download_id'],$row['product_id'])) {
                    $this->incomingDownloadLinks[(int)$row['download_id']][(int)$row['product_id']] = true;
                }
                if ($sourceLogical === 'product') {
                    $totalProducts++;
                    if (isset($row['product_id'])) {
                        $this->productSkus[(int)$row['product_id']] = isset($row['sku']) ? (string)$row['sku'] : '';
                    }
                    $identityBatch[] = $row;
                    if (count($identityBatch) >= 500) {
                        $this->checkProductIdentityBatch($identityBatch, $result);
                        $identityBatch = array();
                    }
                }
                foreach($checks as $idx=>$check){
                    if($check['sheet']===$sourceLogical && isset($row[$check['column']]) && $row[$check['column']]!=='' && $row[$check['column']]!==null){$id=(int)$row[$check['column']];if(!($id===0 && $check['allow_zero']) && $id>0){$pid=isset($row['product_id'])?(int)$row['product_id']:0;$requiredRefs[$idx][$id][$pid]=true;}}
                    if($check['target']===$sourceLogical && isset($row[$check['target_key']]) && (int)$row[$check['target_key']]>0){$availableRefs[$idx][(int)$row[$check['target_key']]]=true;}
                }
            });
            if ($sourceLogical === 'product' && $identityBatch) {
                $this->checkProductIdentityBatch($identityBatch, $result);
                $identityBatch = array();
            }
            $sourceColumns=$info['columns'];
            $this->warnMainCategoryCapability($sourceLogical, $sourceColumns, $result);
            $seoSheet=$this->isSeoLogical($sourceLogical)&&$this->isSeoLogical($targetLogical);
            if($seoSheet){
                if(!in_array('query',$sourceColumns,true)||!in_array('keyword',$sourceColumns,true)){$result['errors'][]=DB_PREFIX.$targetLogical.': SEO sheet must contain query and keyword columns.';}
                $intersection=array_values(array_intersect($sourceColumns,array_keys($schema['columns'])));
                foreach(array('query','keyword') as $requiredSeo){if(isset($schema['columns'][$requiredSeo])&&!in_array($requiredSeo,$intersection,true)){$intersection[]=$requiredSeo;}}
                if($sourceLogical!==$targetLogical){$result['warnings'][]='SEO compatibility conversion: '.$sourceLogical.' -> '.$targetLogical.'.';if($sourceLogical==='url_alias'&&$targetLogical==='seo_url'){$result['warnings'][]='Legacy url_alias has no language/store fields; imported SEO rows will use the primary target language and main store.';}}
            } else {
                $intersection=array_values(array_intersect($sourceColumns,array_keys($schema['columns'])));$missingRequired=array();
                foreach($schema['columns'] as $name=>$column){if(!in_array($name,$intersection,true)&&$column['required']&&!$this->requiredColumnHasFallback($sourceLogical,$name)){$missingRequired[]=$name;}}
                if($missingRequired){$result['errors'][]=DB_PREFIX.$targetLogical.': required target columns are absent: '.implode(', ',$missingRequired);}
                if(!$schema['primary']){$result['warnings'][]=DB_PREFIX.$targetLogical.': table has no PRIMARY KEY and will be skipped to avoid duplicates.';}else{foreach($schema['primary'] as $pk){if(!in_array($pk,$intersection,true)){$result['errors'][]=DB_PREFIX.$targetLogical.': primary key column is absent: '.$pk.'. CodeCart PRO does not guess or remap catalog IDs during a safe import.';}}}
            }
            $ignored=array_values(array_diff($sourceColumns,array_keys($schema['columns'])));if($seoSheet){$ignored=array_values(array_diff($ignored,array('seo_url_id','url_alias_id','store_id','language_id')));}if($ignored){$result['warnings'][]=DB_PREFIX.$targetLogical.': unsupported source columns will be ignored: '.implode(', ',$ignored);}
            $result['sheets'][]=array('table'=>$sourceLogical,'target_table'=>$targetLogical,'rows'=>$rowCount,'columns'=>count($intersection),'ignored'=>$ignored,'primary'=>$schema['primary']);
        }
        foreach($checks as $idx=>$check){
            if(isset($check['when_table'])&&!$this->tableExists($check['when_table']))continue;
            $ids=isset($requiredRefs[$idx])?array_keys($requiredRefs[$idx]):array(); if(!$ids)continue;
            $available=isset($availableRefs[$idx])?$availableRefs[$idx]:array();
            if($this->tableExists($check['target'])){foreach(array_chunk(array_values(array_diff($ids,array_keys($available))),500) as $chunk){if(!$chunk)continue;$q=$this->db->query("SELECT `".$check['target_key']."` FROM `".$this->table($check['target'])."` WHERE `".$check['target_key']."` IN (".implode(',',array_map('intval',$chunk)).")");foreach($q->rows as $row){$available[(int)$row[$check['target_key']]]=true;}}}
            $missing=array_values(array_diff($ids,array_keys($available)));
            if ($missing) {
                $systemMissing = array();
                foreach ($missing as $id) {
                    $owners = isset($requiredRefs[$idx][$id]) ? array_keys($requiredRefs[$idx][$id]) : array(0);
                    foreach ($owners as $owner) {
                        if ($this->progressCallback && $owner > 0 && isset($this->productSkus[$owner])) {
                            $this->rejectProduct($owner, 'Missing '.$check['label'].' ID '.$id);
                        } else {
                            $systemMissing[$id] = true;
                        }
                    }
                }
                if ($systemMissing) {
                    $sample = array_slice(array_keys($systemMissing), 0, 20);
                    $result['errors'][] = 'Missing referenced '.$check['label'].' IDs on target: '.implode(', ', $sample).'. Import is blocked to prevent broken catalog relations.';
                }
            }
        }
        $this->checkDownloadAvailability($downloadFiles,$package,$result);
        $result['warnings'][]='ID policy: catalog primary IDs are preserved and are not remapped. Existing target rows with the same IDs will be updated. This is intended for backup/restore or migration of the same catalog lineage; review ID collisions before merging unrelated stores.';
        $result['warnings'][]='Relation policy: merge/update only. Existing product-category and other catalog relations that are absent from the workbook are preserved; import never deletes them.';
        if($imagePaths){
            $packageImages=$package&&is_file($package)?$this->packageImageIndex($package):array();
            $missing=array();
            foreach(array_keys($imagePaths) as $relative) {
                $full=rtrim(DIR_IMAGE,'/\\').DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$relative);
                if(is_file($full)||isset($packageImages[$relative]))continue;
                if ($this->progressCallback && isset($productImagePaths[$relative])) {
                    foreach(array_keys($productImagePaths[$relative]) as $id) {
                        $this->rejectProduct($id, 'Missing image: '.$relative);
                    }
                } elseif(count($missing)<20) {
                    $missing[]=$relative;
                }
            }
            if($missing){$result['errors'][]='Missing required images outside product cards: '.implode(', ',$missing).'.';}
        }
        foreach ($this->productProblems as $id => &$item) {
            if ($item['name']==='' && isset($this->productNames[$id])) { $item['name']=$this->productNames[$id]; }
        }
        unset($item);
        $result['total_products'] = $totalProducts;
        $result['skipped_products'] = count($this->badProductIds);
        $result['product_errors'] = array_values($this->productProblems);
        $this->progressCounts = array('total'=>$totalProducts,'processed'=>0,
            'success'=>0,'skipped'=>count($this->badProductIds),'fixed'=>0);
        $result['ok']=!$result['errors']; return $result;
    }

    private function importTokenStreaming($token, $overwriteImages = false) {
        if(!preg_match('/^[a-f0-9]{32}$/',(string)$token)){throw new \RuntimeException('Invalid import token.');}
        $base=DIR_UPLOAD.'codecart_catalog_'.$token;$stateFile=$base.'.json';if(!is_file($stateFile)){throw new \RuntimeException('Import session expired or not found.');}
        $state=json_decode((string)file_get_contents($stateFile),true);if(!is_array($state)||empty($state['xlsx'])||!is_file($state['xlsx'])||(time()-(int)$state['created'])>3600){$this->cleanupToken($token);throw new \RuntimeException('Import session expired.');}
        if(isset($state['options']) && $state['options'] !== $this->importOptions){
            throw new \RuntimeException('Import rules changed after preview. Select the file again.');
        }
        $xlsx=$state['xlsx'];$package=!empty($state['package'])&&is_file($state['package'])?$state['package']:'';$preview=$this->preflightStreaming($xlsx,$package);if(!$preview['ok']){throw new \RuntimeException(implode('; ',$preview['errors']));}
        $meta=$this->readStreamingMetadata($xlsx);$languageMap=$this->targetLanguageMap(isset($meta['languages'])?$meta['languages']:array());$storeMap=$this->targetStoreMap(isset($meta['stores'])?$meta['stores']:array());
        $plans=array();$backupTables=array();foreach($preview['sheets'] as $sheet){$source=$sheet['table'];$target=$sheet['target_table'];if(!$this->isTransferTable($source)||$target===null)continue;$schema=$this->schema($target);if(!$schema['primary']&&!$this->isSeoLogical($target))continue;$plans[$source]=$target;$backupTables[$target]=true;}
        $plans=$this->orderedPlans($plans);
        $mainCategoryMap=array();if(isset($plans['product_to_category'])||isset($plans['product'])){if($this->tableExists('product'))$backupTables['product']=true;if($this->tableExists('product_to_category'))$backupTables['product_to_category']=true;}
        $backup=$this->createSqlSnapshot(array_keys($backupTables));
        $stats=array('inserted_or_updated'=>0,'skipped_rows'=>0,'tables'=>array(),'images'=>0,
            'main_categories'=>0,'backup'=>$backup,'warnings'=>$preview['warnings'],
            'total_products'=>(int)$preview['total_products'],'processed_products'=>0,
            'successful_products'=>0,'skipped_products'=>count($this->badProductIds),
            'product_errors'=>array_values($this->productProblems));
        $this->publishProgress('importing', true);
        $this->db->query('START TRANSACTION');
        try{
            foreach($plans as $source=>$target){$schema=$this->schema($target);$count=0;$skipped=0;$columns=null;
                $info=SimpleXlsx::streamSheet($xlsx,$source,function($row) use ($source,$target,$schema,$languageMap,$storeMap,&$count,&$skipped,&$columns,&$mainCategoryMap){
                    if($columns===null){$columns=array_keys($row);} // actual header is supplied below after stream; row keys are equivalent for mapped data
                    if ($this->ignoreRejectedProductRow($source, $row)) {
                        if ($source === 'product') {
                            $this->progressCounts['processed']++;
                            $this->publishProgress('importing');
                        }
                        $skipped++;
                        return;
                    }
                    if ($source === 'product') {
                        $this->progressCounts['processed']++;
                        $this->progressCounts['success']++;
                        $this->publishProgress('importing');
                    }
                    if($source==='product_to_category'&&!empty($row['main_category'])){$pid=isset($row['product_id'])?(int)$row['product_id']:0;$cid=isset($row['category_id'])?(int)$row['category_id']:0;if($pid>0&&$cid>0)$mainCategoryMap[$pid]=$cid;}
                    if($source==='product'&&isset($row['main_category_id'])){$pid=isset($row['product_id'])?(int)$row['product_id']:0;$cid=(int)$row['main_category_id'];if($pid>0&&$cid>0&&!isset($mainCategoryMap[$pid]))$mainCategoryMap[$pid]=$cid;}
                    if($this->isSeoLogical($source)&&$this->isSeoLogical($target)){$mapped=$this->mapSeoRow($source,$target,$row,$languageMap,$storeMap);if($mapped===null){$skipped++;return;}$this->upsertSeo($target,$mapped);}else{$allowed=array_values(array_intersect(array_keys($row),array_keys($schema['columns'])));$mapped=$this->mapRow($source,$row,$allowed,$languageMap,$storeMap);if($mapped===null){$skipped++;return;}
                    if($source==='product'){$mapped=$this->applyProductImportOptions($mapped,$schema);}
                    $this->upsert($target,$mapped,$schema);} $count++;
                });
                $stats['tables'][$source]=$count;$stats['inserted_or_updated']+=$count;$stats['skipped_rows']+=$skipped;
            }
            if($mainCategoryMap){
                foreach(array_keys($this->badProductIds) as $id){unset($mainCategoryMap[$id]);}
                $stats['main_categories']=$this->applyMainCategoryMap($mainCategoryMap);
            }
            $this->db->query('COMMIT');
        }catch(\Throwable $e){try{$this->db->query('ROLLBACK');}catch(\Throwable $ignored){}throw $e;}
        if($package){$stats['images']=$this->importPackageImages($package,(bool)$overwriteImages);$stats['downloads']=$this->importPackageDownloads($package);}
        $stats['processed_products'] = (int)$this->progressCounts['processed'];
        $stats['successful_products'] = (int)$this->progressCounts['success'];
        $this->publishProgress('complete', true);
        $this->cleanupToken($token);
        return $stats;
    }

    private function ignoreRejectedProductRow($source, array $row) {
        if (($source === 'download' || $source === 'download_description') && isset($row['download_id'])) {
            $downloadId = (int)$row['download_id'];
            if (isset($this->incomingDownloadLinks[$downloadId])) {
                $allRejected = true;
                foreach (array_keys($this->incomingDownloadLinks[$downloadId]) as $productId) {
                    if (!isset($this->badProductIds[$productId])) { $allRejected = false; break; }
                }
                if ($allRejected) { return true; }
            }
        }
        if (isset($row['product_id']) && isset($this->badProductIds[(int)$row['product_id']])) {
            return true;
        }
        if ($source === 'product_related' && isset($row['related_id']) && isset($this->badProductIds[(int)$row['related_id']])) {
            return true;
        }
        if ($this->isSeoLogical($source) && isset($row['query'])
            && preg_match('/^product_id=([0-9]+)$/', (string)$row['query'], $match)
            && isset($this->badProductIds[(int)$match[1]])) {
            return true;
        }
        return false;
    }

    /**
     * Keep referenced catalog dependencies ahead of their relations.
     * Export sheet ordering is intentionally independent from import ordering.
     */
    private function orderedPlans(array $plans) {
        $first = array(
            'category', 'manufacturer', 'option', 'option_value', 'attribute_group',
            'attribute', 'filter_group', 'filter', 'download', 'product'
        );
        $order = array_flip($first);
        uksort($plans, function($a, $b) use ($order) {
            $pa = isset($order[$a]) ? $order[$a] : 100;
            $pb = isset($order[$b]) ? $order[$b] : 100;
            return $pa === $pb ? strcmp($a, $b) : ($pa <=> $pb);
        });
        return $plans;
    }

    private function exportRowsIterator($logical, array $entities, array &$imagePaths) {
        $table = $this->table($logical);
        $where = '';
        if (($logical === 'download' || $logical === 'download_description') && $this->tableExists('product_to_download')) {
            $where = ' WHERE `download_id` IN (SELECT DISTINCT `download_id` FROM `' . $this->table('product_to_download') . '`)';
        }
        if ($logical === 'seo_url' || $logical === 'url_alias') {
            $parts = array();
            if (in_array('products', $entities, true)) { $parts[] = "`query` LIKE 'product_id=%'"; }
            if (in_array('categories', $entities, true)) { $parts[] = "`query` LIKE 'category_id=%'"; }
            if (in_array('manufacturers', $entities, true)) { $parts[] = "`query` LIKE 'manufacturer_id=%'"; }
            if (!$parts) { return; }
            $where = ' WHERE ' . implode(' OR ', $parts);
        }
        $schema = $this->schema($logical);
        $order = $schema['primary'] ? ' ORDER BY ' . implode(',', array_map(function($c){ return '`' . $c . '`'; }, $schema['primary'])) : '';
        $limit = 1000; $offset = 0;
        while (true) {
            $query = $this->db->query("SELECT * FROM `" . $table . "`" . $where . $order . " LIMIT " . $offset . "," . $limit);
            if (!$query->rows) { break; }
            foreach ($query->rows as $row) {
                $productId = isset($row['product_id']) ? (int)$row['product_id'] : 0;
                if (($logical === 'download' || $logical === 'download_description')
                    && isset($row['download_id']) && !isset($this->validDownloadIds[(int)$row['download_id']])) {
                    continue;
                }
                if ($productId > 0 && isset($this->badProductIds[$productId])) {
                    if ($logical === 'product') {
                        $this->progressCounts['processed']++;
                        $this->publishProgress('exporting');
                    }
                    continue;
                }
                if ($logical === 'product_related' && isset($row['related_id']) && isset($this->badProductIds[(int)$row['related_id']])) {
                    continue;
                }
                if ($logical === 'seo_url' || $logical === 'url_alias') {
                    if (isset($row['query']) && preg_match('/^product_id=([0-9]+)$/',(string)$row['query'],$m) && isset($this->badProductIds[(int)$m[1]])) {
                        continue;
                    }
                }
                if ($logical === 'product') {
                    $this->progressCounts['processed']++;
                    $this->progressCounts['success']++;
                    $this->publishProgress('exporting');
                }
                $this->collectImages($logical, $row, $imagePaths);
                yield $row;
            }
            $count = count($query->rows);
            unset($query);
            if ($count < $limit) { break; }
            $offset += $limit;
        }
    }

    private function exportRows($logical, array $entities) {
        $table = $this->table($logical);
        if ($logical === 'seo_url') {
            $parts = array();
            if (in_array('products',$entities,true)) $parts[] = "`query` LIKE 'product_id=%'";
            if (in_array('categories',$entities,true)) $parts[] = "`query` LIKE 'category_id=%'";
            if (in_array('manufacturers',$entities,true)) $parts[] = "`query` LIKE 'manufacturer_id=%'";
            if (!$parts) return null;
            return $this->db->query("SELECT * FROM `".$table."` WHERE " . implode(' OR ', $parts))->rows;
        }
        if ($logical === 'url_alias') {
            $parts = array();
            if (in_array('products',$entities,true)) $parts[] = "`query` LIKE 'product_id=%'";
            if (in_array('categories',$entities,true)) $parts[] = "`query` LIKE 'category_id=%'";
            if (in_array('manufacturers',$entities,true)) $parts[] = "`query` LIKE 'manufacturer_id=%'";
            if (!$parts) return null;
            return $this->db->query("SELECT * FROM `".$table."` WHERE " . implode(' OR ', $parts))->rows;
        }
        $schema = $this->schema($logical);
        $order = $schema['primary'] ? ' ORDER BY ' . implode(',', array_map(function($c){return '`'.$c.'`';}, $schema['primary'])) : '';
        return $this->db->query("SELECT * FROM `".$table."`".$order)->rows;
    }

    private function tablesForEntities(array $entities) {
        $groups = $this->entityGroups(); $tables=array();
        foreach ($entities as $entity) { if (isset($groups[$entity])) { foreach($groups[$entity] as $table){$tables[$table]=true;} } }
        if (array_intersect($entities,array('products','categories','manufacturers'))) {
            if ($this->tableExists('seo_url')) $tables['seo_url']=true; elseif($this->tableExists('url_alias')) $tables['url_alias']=true;
        }
        return array_keys($tables);
    }

    private function normalizeEntities(array $entities) {
        $valid=array_keys($this->entityGroups());$out=array();foreach($entities as $e){$e=(string)$e;if(in_array($e,$valid,true)&&!in_array($e,$out,true))$out[]=$e;}return $out;
    }

    private function metadataRows(array $entities) {
        $rows=array(
            array('type'=>'meta','key'=>'format','value'=>'codecart-catalog'),
            array('type'=>'meta','key'=>'format_version','value'=>self::FORMAT_VERSION),
            array('type'=>'meta','key'=>'codecart_version','value'=>defined('VERSION')?VERSION:''),
            array('type'=>'meta','key'=>'source_prefix','value'=>DB_PREFIX),
            array('type'=>'meta','key'=>'created_utc','value'=>gmdate('c')),
            array('type'=>'meta','key'=>'entities','value'=>implode(',',$entities)),
            array('type'=>'meta','key'=>'relation_policy','value'=>'merge-preserve-existing')
        );
        if ($this->tableExists('language')) {
            foreach($this->db->query("SELECT `language_id`, `code` FROM `".$this->table('language')."` ORDER BY `language_id`")->rows as $r){$rows[]=array('type'=>'language','key'=>(string)$r['language_id'],'value'=>(string)$r['code']);}
        }
        $rows[]=array('type'=>'store','key'=>'0','value'=>'__MAIN__');
        if ($this->tableExists('store')) {
            foreach($this->db->query("SELECT `store_id`, `url`, `ssl` FROM `".$this->table('store')."` ORDER BY `store_id`")->rows as $r){$url=trim((string)$r['ssl'])!==''?(string)$r['ssl']:(string)$r['url'];$rows[]=array('type'=>'store','key'=>(string)$r['store_id'],'value'=>$this->normalizeUrl($url));}
        }
        return $rows;
    }

    private function parseMetadata(array $rows) {
        $out=array('meta'=>array(),'languages'=>array(),'stores'=>array());
        foreach($rows as $r){$type=isset($r['type'])?(string)$r['type']:'';$key=isset($r['key'])?(string)$r['key']:'';$value=isset($r['value'])?(string)$r['value']:'';if($type==='meta')$out['meta'][$key]=$value;elseif($type==='language')$out['languages'][(int)$key]=$value;elseif($type==='store')$out['stores'][(int)$key]=$value;}
        if (!isset($out['meta']['format']) || $out['meta']['format']!=='codecart-catalog') throw new \RuntimeException('Unsupported catalog workbook.');
        return $out;
    }

    private function targetLanguageMap(array $source) {
        $targetByCode=array();if($this->tableExists('language')){foreach($this->db->query("SELECT `language_id`, `code` FROM `".$this->table('language')."`")->rows as $r){$targetByCode[strtolower((string)$r['code'])]=(int)$r['language_id'];}}
        $map=array();foreach($source as $id=>$code){$key=strtolower((string)$code);$map[(int)$id]=isset($targetByCode[$key])?$targetByCode[$key]:null;}return $map;
    }

    private function targetStoreMap(array $source) {
        $target=array('__MAIN__'=>0);if($this->tableExists('store')){foreach($this->db->query("SELECT `store_id`, `url`, `ssl` FROM `".$this->table('store')."`")->rows as $r){$url=trim((string)$r['ssl'])!==''?(string)$r['ssl']:(string)$r['url'];$target[$this->normalizeUrl($url)]=(int)$r['store_id'];}}
        $map=array();foreach($source as $id=>$url){$n=$url==='__MAIN__'?'__MAIN__':$this->normalizeUrl($url);$map[(int)$id]=isset($target[$n])?$target[$n]:null;}if(!isset($map[0]))$map[0]=0;return $map;
    }

    private function isSeoLogical($logical) {
        return $logical === 'seo_url' || $logical === 'url_alias';
    }

    private function targetLogicalForSource($sourceLogical) {
        if ($this->tableExists($sourceLogical)) { return $sourceLogical; }
        if ($sourceLogical === 'seo_url' && $this->tableExists('url_alias')) { return 'url_alias'; }
        if ($sourceLogical === 'url_alias' && $this->tableExists('seo_url')) { return 'seo_url'; }
        return null;
    }

    private function mapSeoRow($sourceLogical, $targetLogical, array $row, array $languageMap, array $storeMap) {
        $query = isset($row['query']) ? trim((string)$row['query']) : '';
        $keyword = isset($row['keyword']) ? trim((string)$row['keyword']) : '';
        if ($query === '' || $keyword === '') { return null; }
        if (!preg_match('/^(?:product_id|category_id|manufacturer_id)=[0-9]+$/', $query)) { return null; }
        if ($targetLogical === 'url_alias') { return array('query'=>$query,'keyword'=>$keyword); }

        $targetLanguageId = (int)$this->config->get('config_language_id');
        if ($targetLanguageId <= 0 && $this->tableExists('language')) {
            $q = $this->db->query("SELECT `language_id` FROM `".$this->table('language')."` ORDER BY `sort_order`, `language_id` LIMIT 1");
            if ($q->num_rows) { $targetLanguageId = (int)$q->row['language_id']; }
        }
        $storeId = 0;
        $languageId = $targetLanguageId;
        if ($sourceLogical === 'seo_url') {
            if (isset($row['store_id'])) {
                $sourceStore = (int)$row['store_id'];
                if (!array_key_exists($sourceStore, $storeMap) || $storeMap[$sourceStore] === null) { return null; }
                $storeId = (int)$storeMap[$sourceStore];
            }
            if (isset($row['language_id'])) {
                $sourceLanguage = (int)$row['language_id'];
                if (!array_key_exists($sourceLanguage, $languageMap) || $languageMap[$sourceLanguage] === null) { return null; }
                $languageId = (int)$languageMap[$sourceLanguage];
            }
        }
        return array('store_id'=>$storeId,'language_id'=>$languageId,'query'=>$query,'keyword'=>$keyword);
    }

    private function upsertSeo($targetLogical, array $row) {
        if ($targetLogical === 'seo_url') {
            $query = $this->db->escape((string)$row['query']);
            $storeId = (int)$row['store_id'];
            $languageId = (int)$row['language_id'];
            $existing = $this->db->query("SELECT seo_url_id FROM `".$this->table('seo_url')."` WHERE `query`='".$query."' AND store_id=".$storeId." AND language_id=".$languageId." ORDER BY seo_url_id LIMIT 1");
            if ($existing->num_rows) {
                $this->db->query("UPDATE `".$this->table('seo_url')."` SET keyword='".$this->db->escape((string)$row['keyword'])."' WHERE seo_url_id=".(int)$existing->row['seo_url_id']);
            } else {
                $this->db->query("INSERT INTO `".$this->table('seo_url')."` SET store_id=".$storeId.", language_id=".$languageId.", `query`='".$query."', keyword='".$this->db->escape((string)$row['keyword'])."'");
            }
            return;
        }
        $query = $this->db->escape((string)$row['query']);
        $existing = $this->db->query("SELECT url_alias_id FROM `".$this->table('url_alias')."` WHERE `query`='".$query."' ORDER BY url_alias_id LIMIT 1");
        if ($existing->num_rows) {
            $this->db->query("UPDATE `".$this->table('url_alias')."` SET keyword='".$this->db->escape((string)$row['keyword'])."' WHERE url_alias_id=".(int)$existing->row['url_alias_id']);
        } else {
            $this->db->query("INSERT INTO `".$this->table('url_alias')."` SET `query`='".$query."', keyword='".$this->db->escape((string)$row['keyword'])."'");
        }
    }

    private function extractMainCategoryMap(array $workbook) {
        $map = array();
        if (isset($workbook['product_to_category']['columns']) && in_array('main_category', $workbook['product_to_category']['columns'], true)) {
            foreach ($workbook['product_to_category']['rows'] as $row) {
                if (empty($row['main_category'])) { continue; }
                $productId = isset($row['product_id']) ? (int)$row['product_id'] : 0;
                $categoryId = isset($row['category_id']) ? (int)$row['category_id'] : 0;
                if ($productId > 0 && $categoryId > 0 && !isset($map[$productId])) { $map[$productId] = $categoryId; }
            }
        }
        if (isset($workbook['product']['columns']) && in_array('main_category_id', $workbook['product']['columns'], true)) {
            foreach ($workbook['product']['rows'] as $row) {
                $productId = isset($row['product_id']) ? (int)$row['product_id'] : 0;
                $categoryId = isset($row['main_category_id']) ? (int)$row['main_category_id'] : 0;
                // ocStore variants may keep the main category in product.main_category_id,
                // product_to_category.main_category, or both. Prefer the relation flag when it
                // exists for this product and use product.main_category_id only as per-product fallback.
                if ($productId > 0 && $categoryId > 0 && !isset($map[$productId])) { $map[$productId] = $categoryId; }
            }
        }
        return $map;
    }

    private function applyMainCategoryMap(array $map) {
        if (!$map) { return 0; }
        $productSchema = $this->tableExists('product') ? $this->schema('product') : array('columns'=>array());
        $relationSchema = $this->tableExists('product_to_category') ? $this->schema('product_to_category') : array('columns'=>array());
        $hasProductMain = isset($productSchema['columns']['main_category_id']);
        $hasRelationMain = isset($relationSchema['columns']['main_category']);
        if (!$hasProductMain && !$hasRelationMain) { return 0; }
        $done = 0;
        foreach ($map as $productId=>$categoryId) {
            $productId = (int)$productId; $categoryId = (int)$categoryId;
            if ($productId <= 0 || $categoryId <= 0) { continue; }
            if ($this->tableExists('category')) {
                $categoryExists = $this->db->query("SELECT category_id FROM `".$this->table('category')."` WHERE category_id=".$categoryId." LIMIT 1");
                if (!$categoryExists->num_rows) { continue; }
            }
            if ($hasProductMain) {
                $this->db->query("UPDATE `".$this->table('product')."` SET main_category_id=".$categoryId." WHERE product_id=".$productId);
            }
            if ($hasRelationMain) {
                $this->db->query("UPDATE `".$this->table('product_to_category')."` SET main_category=0 WHERE product_id=".$productId);
                $exists = $this->db->query("SELECT product_id FROM `".$this->table('product_to_category')."` WHERE product_id=".$productId." AND category_id=".$categoryId." LIMIT 1");
                if ($exists->num_rows) {
                    $this->db->query("UPDATE `".$this->table('product_to_category')."` SET main_category=1 WHERE product_id=".$productId." AND category_id=".$categoryId);
                } else {
                    $this->db->query("INSERT INTO `".$this->table('product_to_category')."` SET product_id=".$productId.", category_id=".$categoryId.", main_category=1");
                }
            }
            $done++;
        }
        return $done;
    }

    private function validateCatalogReferences(array $workbook, array &$result) {
        $checks = array(
            array('sheet'=>'product_to_category','column'=>'category_id','target'=>'category','target_key'=>'category_id','allow_zero'=>false,'label'=>'product category'),
            array('sheet'=>'product','column'=>'manufacturer_id','target'=>'manufacturer','target_key'=>'manufacturer_id','allow_zero'=>true,'label'=>'manufacturer'),
            array('sheet'=>'product_option','column'=>'option_id','target'=>'option','target_key'=>'option_id','allow_zero'=>false,'label'=>'product option'),
            array('sheet'=>'product_option_value','column'=>'option_value_id','target'=>'option_value','target_key'=>'option_value_id','allow_zero'=>false,'label'=>'option value'),
            array('sheet'=>'product_attribute','column'=>'attribute_id','target'=>'attribute','target_key'=>'attribute_id','allow_zero'=>false,'label'=>'attribute'),
            array('sheet'=>'product_filter','column'=>'filter_id','target'=>'filter','target_key'=>'filter_id','allow_zero'=>false,'label'=>'filter'),
            array('sheet'=>'product_extra_tab','column'=>'product_id','target'=>'product','target_key'=>'product_id','allow_zero'=>false,'label'=>'product'),
            array('sheet'=>'product_related','column'=>'product_id','target'=>'product','target_key'=>'product_id','allow_zero'=>false,'label'=>'related product source'),
            array('sheet'=>'product_related','column'=>'related_id','target'=>'product','target_key'=>'product_id','allow_zero'=>false,'label'=>'related product'),
            array('sheet'=>'product_related_article','column'=>'product_id','target'=>'product','target_key'=>'product_id','allow_zero'=>false,'label'=>'article related product'),
            array('sheet'=>'product_related_article','column'=>'article_id','target'=>'article','target_key'=>'article_id','allow_zero'=>false,'when_table'=>'product_related_article','label'=>'related article'),
            array('sheet'=>'manufacturer_description','column'=>'manufacturer_id','target'=>'manufacturer','target_key'=>'manufacturer_id','allow_zero'=>false,'label'=>'manufacturer'),
            array('sheet'=>'product_related_wb','column'=>'category_id','target'=>'category','target_key'=>'category_id','allow_zero'=>false,'label'=>'related category'),
            array('sheet'=>'product_related_wb','column'=>'product_id','target'=>'product','target_key'=>'product_id','allow_zero'=>false,'label'=>'category related product'),
            array('sheet'=>'product_related_mn','column'=>'manufacturer_id','target'=>'manufacturer','target_key'=>'manufacturer_id','allow_zero'=>false,'label'=>'related manufacturer'),
            array('sheet'=>'product_related_mn','column'=>'product_id','target'=>'product','target_key'=>'product_id','allow_zero'=>false,'label'=>'manufacturer related product')
        );
        foreach ($checks as $check) {
            if (!isset($workbook[$check['sheet']]['rows'])) { continue; }
            if (isset($check['when_table']) && !$this->tableExists($check['when_table'])) { continue; }
            $ids = array();
            $owners = array();
            foreach ($workbook[$check['sheet']]['rows'] as $row) {
                if (!isset($row[$check['column']]) || $row[$check['column']] === '' || $row[$check['column']] === null) { continue; }
                $id = (int)$row[$check['column']];
                if ($id === 0 && $check['allow_zero']) { continue; }
                if ($id > 0) {
                    $ids[$id] = true;
                    $pid = isset($row['product_id']) ? (int)$row['product_id'] : 0;
                    $owners[$id][$pid] = true;
                }
            }
            if (!$ids) { continue; }

            $available = array();
            if (isset($workbook[$check['target']]['rows'])) {
                foreach ($workbook[$check['target']]['rows'] as $row) {
                    if (isset($row[$check['target_key']]) && (int)$row[$check['target_key']] > 0) {
                        $available[(int)$row[$check['target_key']]] = true;
                    }
                }
            }
            if ($this->tableExists($check['target'])) {
                $remaining = array_values(array_diff(array_keys($ids), array_keys($available)));
                if ($remaining) {
                    foreach (array_chunk($remaining, 500) as $chunk) {
                        $safeIds = array_map('intval', $chunk);
                        $query = $this->db->query("SELECT `".$check['target_key']."` FROM `".$this->table($check['target'])."` WHERE `".$check['target_key']."` IN (".implode(',', $safeIds).")");
                        foreach ($query->rows as $row) { $available[(int)$row[$check['target_key']]] = true; }
                    }
                }
            }
            $missing = array_values(array_diff(array_keys($ids), array_keys($available)));
            if ($missing) {
                $systemMissing = array();
                foreach ($missing as $missingId) {
                    foreach (array_keys(isset($owners[$missingId]) ? $owners[$missingId] : array(0=>true)) as $pid) {
                        if ($this->progressCallback && $pid > 0 && isset($this->productSkus[$pid])) {
                            $this->rejectProduct($pid, 'Missing ' . $check['label'] . ' ID ' . $missingId);
                        } else { $systemMissing[$missingId] = true; }
                    }
                }
                if ($systemMissing) {
                    $sample = array_slice(array_keys($systemMissing), 0, 20);
                    $result['errors'][] = 'Missing referenced '.$check['label'].' IDs on target: '.implode(', ',$sample).'. Import is blocked to prevent broken catalog relations.';
                }
            }
        }
    }

    private function mapRow($logical, array $row, array $columns, array $languageMap, array $storeMap) {
        $out=array();foreach($columns as $column){$out[$column]=array_key_exists($column,$row)?$row[$column]:null;}
        if (array_key_exists('language_id',$out)) {$source=(int)$out['language_id'];if(!array_key_exists($source,$languageMap)||$languageMap[$source]===null)return null;$out['language_id']=$languageMap[$source];}
        if (array_key_exists('store_id',$out)) {$source=(int)$out['store_id'];if(!array_key_exists($source,$storeMap)||$storeMap[$source]===null)return null;$out['store_id']=$storeMap[$source];}
        return $out;
    }

    private function upsert($logical, array $row, array $schema) {
        $columns=array_keys($row);if(!$columns)return;
        $names=array();$values=array();foreach($columns as $c){$names[]='`'.$c.'`';$v=$row[$c];$values[]=$v===null?'NULL':"'".$this->db->escape((string)$v)."'";}
        $updates=array();foreach($columns as $c){if(!in_array($c,$schema['primary'],true))$updates[]='`'.$c.'`=VALUES(`'.$c.'`)';}

        // Pure relation tables can consist entirely of a composite PRIMARY KEY
        // (for example product_to_store or product_filter). In that case there
        // is no non-key column to update, but a repeated import must still be
        // idempotent. Use a harmless self-assignment instead of falling back to
        // a plain INSERT, which would raise duplicate-key error 1062. Do not use
        // INSERT IGNORE here because it can hide unrelated data/constraint errors.
        if (!$updates && !empty($schema['primary'])) {
            foreach ($schema['primary'] as $primaryColumn) {
                if (in_array($primaryColumn, $columns, true)) {
                    $updates[] = '`' . $primaryColumn . '`=`' . $primaryColumn . '`';
                    break;
                }
            }
        }

        $sql="INSERT INTO `".$this->table($logical)."` (".implode(',',$names).") VALUES (".implode(',',$values).")";
        if($updates)$sql.=' ON DUPLICATE KEY UPDATE '.implode(',',$updates);
        $this->db->query($sql);
    }

    private function schema($logical) {
        static $cache = array();
        if (isset($cache[$logical])) {
            return $cache[$logical];
        }

        if (!$this->validLogicalTable($logical)) {
            throw new \InvalidArgumentException('Invalid table.');
        }

        $table = $this->table($logical);
        $columns = array();
        $primary = array();

        // Keep schema discovery deliberately simple. SHOW FULL COLUMNS / SHOW INDEX
        // are supported by the MySQL and MariaDB versions targeted by CodeCart PRO and
        // avoid vendor/version differences in INFORMATION_SCHEMA metadata queries.
        try {
            $rows = $this->db->query("SHOW FULL COLUMNS FROM `" . $table . "`")->rows;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Catalog schema read failed for table ' . $logical . ' [SHOW FULL COLUMNS]. ' . $e->getMessage(), 0, $e);
        }

        foreach ($rows as $r) {
            $name = isset($r['Field']) ? (string)$r['Field'] : '';
            if ($name === '') {
                continue;
            }

            $nullable = strtoupper(isset($r['Null']) ? (string)$r['Null'] : 'NO') === 'YES';
            $default = array_key_exists('Default', $r) ? $r['Default'] : null;
            $extra = strtolower(isset($r['Extra']) ? (string)$r['Extra'] : '');

            $columns[$name] = array(
                'type' => isset($r['Type']) ? (string)$r['Type'] : '',
                'nullable' => $nullable,
                'default' => $default,
                'extra' => $extra,
                'required' => !$nullable && $default === null && strpos($extra, 'auto_increment') === false
            );

            if (isset($r['Key']) && strtoupper((string)$r['Key']) === 'PRI') {
                $primary[] = $name;
            }
        }

        if (!$columns) {
            throw new \RuntimeException('Catalog schema is empty for table ' . $logical . '.');
        }

        try {
            $indexRows = $this->db->query("SHOW INDEX FROM `" . $table . "`")->rows;
            $orderedPrimary = array();
            foreach ($indexRows as $indexRow) {
                $keyName = isset($indexRow['Key_name']) ? (string)$indexRow['Key_name'] : '';
                if (strtoupper($keyName) !== 'PRIMARY') {
                    continue;
                }
                $columnName = isset($indexRow['Column_name']) ? (string)$indexRow['Column_name'] : '';
                $sequence = isset($indexRow['Seq_in_index']) ? (int)$indexRow['Seq_in_index'] : 0;
                if ($columnName !== '' && isset($columns[$columnName]) && $sequence > 0) {
                    $orderedPrimary[$sequence] = $columnName;
                }
            }
            if ($orderedPrimary) {
                ksort($orderedPrimary, SORT_NUMERIC);
                $primary = array_values($orderedPrimary);
            }
        } catch (\Throwable $e) {
            // Export can continue with the PRI markers returned by SHOW FULL COLUMNS.
            if ($this->log) {
                $this->log->write('CodeCart PRO catalog primary-key metadata fallback [' . $logical . ' / SHOW INDEX]: ' . $e->getMessage());
            }
        }

        return $cache[$logical] = array('columns' => $columns, 'primary' => $primary);
    }

    private function tableExists($logical) {
        if (!$this->validLogicalTable($logical)) {
            return false;
        }

        static $tableMap = null;
        if ($tableMap === null) {
            $tableMap = array();
            try {
                // Plain SHOW TABLES is intentionally used here: no LIKE wildcards,
                // no dynamic INFORMATION_SCHEMA column names and no table-data read.
                $rows = $this->db->query('SHOW TABLES')->rows;
                foreach ($rows as $row) {
                    if (!is_array($row) || !$row) {
                        continue;
                    }
                    $name = (string)reset($row);
                    if ($name !== '') {
                        $tableMap[$name] = true;
                    }
                }
            } catch (\Throwable $e) {
                throw new \RuntimeException('Catalog table discovery failed [SHOW TABLES]. ' . $e->getMessage(), 0, $e);
            }
        }

        return isset($tableMap[$this->table($logical)]);
    }

    private function table($logical){if(!$this->validLogicalTable($logical))throw new \InvalidArgumentException('Invalid table.');return DB_PREFIX.$logical;}
    private function validLogicalTable($logical){return is_string($logical)&&preg_match('/^[a-z0-9_]{1,64}$/',$logical)&&in_array($logical,$this->allowedTables(),true);}
    private function isTransferTable($logical){return is_string($logical)&&in_array($logical,$this->transferTables(),true);}
    private function transferTables(){static $all=null;if($all!==null)return $all;$map=array();foreach($this->entityGroups() as $tables)foreach($tables as $t)$map[$t]=true;$map['seo_url']=true;$map['url_alias']=true;$all=array_keys($map);return $all;}
    private function allowedTables(){static $all=null;if($all!==null)return $all;$map=array_fill_keys($this->transferTables(),true);$map['language']=true;$map['store']=true;$map['article']=true;$all=array_keys($map);return $all;}

    private function collectImages($logical,array $row,array &$paths) {
        $columns=array();if(in_array($logical,array('product','category','manufacturer','option_value'),true))$columns[]='image';if($logical==='product_image')$columns[]='image';
        foreach($columns as $c){if(empty($row[$c]))continue;$p=ltrim(str_replace('\\','/',(string)$row[$c]),'/');if($this->safeImagePath($p))$paths[$p]=true;}
    }
    private function safeImagePath($path){$path=str_replace('\\','/',(string)$path);if($path===''||strpos($path,'..')!==false||$path[0]==='/'||strpos($path,'catalog/')!==0||!preg_match('~^[\\pL\\pN_\\-/ .()@+]+\\.(?:jpe?g|png|gif|webp|avif|svg)$~iu',$path))return false;return true;}

    private function packageImageIndex($package) {
        $out = array();
        $zip = new \ZipArchive();
        if ($zip->open($package) !== true) {
            throw new \RuntimeException('Cannot inspect portable image archive.');
        }
        $count = 0;
        $bytes = 0;
        try {
            $finfo = class_exists('finfo') ? new \finfo(FILEINFO_MIME_TYPE) : null;
            foreach (range(0, $zip->numFiles - 1) as $i) {
                $entry = $zip->statIndex($i);
                if (!$entry) { throw new \RuntimeException('Invalid portable ZIP entry.'); }
                $name = str_replace('\\', '/', (string)$entry['name']);
                if (strpos($name, 'image/') !== 0) { continue; }
                $rel = substr($name, 6);
                if (!$this->safeImagePath($rel)) {
                    throw new \RuntimeException('Portable ZIP contains an unsafe image path.');
                }
                if (isset($out[$rel])) { throw new \RuntimeException('Portable ZIP contains duplicate image paths.'); }
                $size = (int)$entry['size'];
                $bytes += $size;
                if (++$count > self::MAX_PACKAGE_IMAGES || $size <= 0 ||
                    $size > self::MAX_SINGLE_IMAGE_BYTES || $bytes > self::MAX_PACKAGE_IMAGE_BYTES) {
                    throw new \RuntimeException('Portable ZIP exceeds safe image extraction limits.');
                }
                $raw = $zip->getFromIndex($i);
                if (!is_string($raw) || strlen($raw) !== $size) {
                    throw new \RuntimeException('Portable ZIP contains an incomplete image.');
                }
                $extension = strtolower((string)pathinfo($rel, PATHINFO_EXTENSION));
                $expected = array('jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png',
                    'gif'=>'image/gif','webp'=>'image/webp','avif'=>'image/avif','svg'=>'image/svg+xml');
                $actual = $finfo ? (string)$finfo->buffer($raw) : '';
                if (!isset($expected[$extension]) || ($actual !== '' && $actual !== $expected[$extension]
                    && !($extension === 'svg' && in_array($actual, array('application/xml','text/xml','text/plain'), true)))) {
                    throw new \RuntimeException('Portable ZIP image content does not match its extension: ' . $rel);
                }
                if ($extension === 'svg') {
                    // SVG is validated again immediately before installation.
                    $error = '';
                    $tmp = tempnam(DIR_UPLOAD, 'ccpsvg_');
                    if (!$tmp) { throw new \RuntimeException('SVG validation staging failed.'); }
                    try {
                        file_put_contents($tmp, $raw);
                        if (SvgSanitizer::sanitizeFile($tmp, $error) === false) {
                            throw new \RuntimeException('Unsafe SVG in portable catalog: ' . $rel);
                        }
                    } finally { @unlink($tmp); }
                }
                $out[$rel] = true;
            }
        } finally { $zip->close(); }
        return $out;
    }

    private function importPackageImages($package,$overwrite) {
        $zip=new \ZipArchive(); if($zip->open($package)!==true)return 0;
        $count=0; $bytes=0; $written=0;
        $allowedMimes=array('image/jpeg'=>true,'image/png'=>true,'image/gif'=>true,'image/webp'=>true,'image/avif'=>true,'image/svg+xml'=>true);
        try {
            for($i=0;$i<$zip->numFiles;$i++){
                $s=$zip->statIndex($i); if(!$s)continue;
                $name=str_replace('\\','/',(string)$s['name']); if(strpos($name,'image/')!==0)continue;
                $rel=substr($name,6); if(strpos($rel,'catalog/')!==0||!$this->safeImagePath($rel))continue;
                $size=(int)$s['size']; if($size<=0||$size>self::MAX_SINGLE_IMAGE_BYTES)continue;
                if(++$count>self::MAX_PACKAGE_IMAGES)break;
                if(($bytes+$size)>self::MAX_PACKAGE_IMAGE_BYTES)break; $bytes+=$size;
                $target=rtrim(DIR_IMAGE,'/\\').DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$rel);
                if(is_file($target)&&!$overwrite)continue;
                $parent=dirname($target); if(!is_dir($parent)&&!mkdir($parent,0755,true)&&!is_dir($parent))continue;
                $realBase=realpath(DIR_IMAGE); $realParent=realpath($parent);
                if(!$realBase||!$realParent)continue;
                $prefix=rtrim($realBase,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
                if(strncmp(rtrim($realParent,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR,$prefix,strlen($prefix))!==0)continue;

                $tmp=tempnam(DIR_UPLOAD,'ccpimg_'); if(!$tmp)continue;
                $in=$zip->getStream($s['name']); $out=$in?fopen($tmp,'wb'):false;
                if(!$in||!$out){if(is_resource($in))fclose($in);if(is_resource($out))fclose($out);@unlink($tmp);continue;}
                $copied=stream_copy_to_stream($in,$out,$size+1); fclose($in); fclose($out);
                if($copied!==$size||$copied>self::MAX_SINGLE_IMAGE_BYTES){@unlink($tmp);continue;}
                $mime='';
                if(function_exists('finfo_open')){$fi=finfo_open(FILEINFO_MIME_TYPE);if($fi){$mime=(string)finfo_file($fi,$tmp);finfo_close($fi);}}
                if($mime===''&&function_exists('getimagesize')){$info=@getimagesize($tmp);if(is_array($info)&&isset($info['mime']))$mime=(string)$info['mime'];}
                $imageType = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
                $mimeOk = isset($allowedMimes[strtolower($mime)])
                    || ($imageType === 'svg' && in_array(strtolower($mime), array('application/xml','text/xml','text/plain'), true));
                if (!$mimeOk) { @unlink($tmp); continue; }
                if (strtolower(pathinfo($rel, PATHINFO_EXTENSION)) === 'svg') {
                    $error = '';
                    $sanitized = SvgSanitizer::sanitizeFile($tmp, $error);
                    if ($sanitized === false || file_put_contents($tmp, $sanitized, LOCK_EX) === false) {
                        @unlink($tmp);
                        throw new \RuntimeException('Unsafe or unwritable SVG in portable catalog: ' . $rel);
                    }
                }
                if(!@rename($tmp,$target)){if(!@copy($tmp,$target)){@unlink($tmp);continue;}@unlink($tmp);}
                @chmod($target,0644); $written++;
            }
        } finally {$zip->close();}
        return $written;
    }

    private function safeDownloadFilename($name) {
        return is_string($name) && strlen($name) >= 3 && strlen($name) <= 255
            && preg_match('/^[A-Za-z0-9][A-Za-z0-9_.-]*$/D', $name)
            && strpos($name, '..') === false;
    }

    private function rejectMissingProductDownload($name, $message) {
        if (!$this->progressCallback) { return false; }
        $found = false;
        foreach ($this->incomingDownloadNames as $downloadId => $filename) {
            if ($filename !== $name || !isset($this->incomingDownloadLinks[$downloadId])) { continue; }
            foreach (array_keys($this->incomingDownloadLinks[$downloadId]) as $productId) {
                if (isset($this->productSkus[$productId])) {
                    $this->rejectProduct($productId, $message . ': ' . $name);
                    $found = true;
                }
            }
        }
        return $found;
    }

    private function checkDownloadAvailability(array $downloadFiles, $package, array &$result) {
        if (!$downloadFiles) { return; }
        $zip = null;
        if ($package && is_file($package)) {
            $zip = new \ZipArchive();
            if ($zip->open($package) !== true) {
                $result['errors'][] = 'Portable package cannot be opened to check digital files.';
                return;
            }
        }
        try {
            foreach (array_keys($downloadFiles) as $name) {
                if (!$this->safeDownloadFilename($name)) {
                    if (!$this->rejectMissingProductDownload($name, 'Invalid digital download filename')) {
                        $result['errors'][] = 'Invalid digital download filename in catalog.';
                    }
                    continue;
                }
                $local = rtrim(DIR_DOWNLOAD, '/\\') . DIRECTORY_SEPARATOR . $name;
                if (is_file($local)) { continue; }
                $found = $zip ? $zip->locateName('download/' . $name) : false;
                if ($found === false) {
                    if (!$this->rejectMissingProductDownload($name, 'Digital product file missing')) {
                        $result['errors'][] = 'Digital product file is not present on target or in package: ' . $name;
                    }
                } else {
                    $stat = $zip->statIndex($found);
                    if (!$stat || (int)$stat['size'] > self::MAX_SINGLE_DOWNLOAD_BYTES) {
                        if (!$this->rejectMissingProductDownload($name, 'Digital product file exceeds package size limit')) {
                            $result['errors'][] = 'Digital product file exceeds the allowed package size: ' . $name;
                        }
                    }
                }
            }
        } finally {
            if ($zip) { $zip->close(); }
        }
    }

    private function importPackageDownloads($package) {
        $zip = new \ZipArchive();
        if ($zip->open($package) !== true) {
            throw new \RuntimeException('Unable to read digital product package.');
        }
        $written = 0;
        $bytes = 0;
        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (!$stat) { continue; }
                $entry = str_replace('\\', '/', (string)$stat['name']);
                if (strpos($entry, 'download/') !== 0) { continue; }
                $name = substr($entry, 9);
                if (!$this->safeDownloadFilename($name)) {
                    throw new \RuntimeException('Unsafe digital download archive path.');
                }
                $size = (int)$stat['size'];
                $bytes += $size;
                if ($size < 0 || $size > self::MAX_SINGLE_DOWNLOAD_BYTES || $bytes > self::MAX_PACKAGE_DOWNLOAD_BYTES) {
                    throw new \RuntimeException('Digital download archive exceeds safe extraction limits.');
                }
                $target = rtrim(DIR_DOWNLOAD, '/\\') . DIRECTORY_SEPARATOR . $name;
                if (is_file($target)) { continue; }
                $tmp = tempnam(DIR_DOWNLOAD, 'ccpd_');
                if (!$tmp) { throw new \RuntimeException('Digital download staging failed.'); }
                try {
                    $in = $zip->getStream($stat['name']);
                    $out = $in ? fopen($tmp, 'wb') : false;
                    if (!$in || !$out) { throw new \RuntimeException('Digital download extraction failed.'); }
                    $copied = stream_copy_to_stream($in, $out, $size + 1);
                    fclose($in); fclose($out);
                    if ($copied !== $size) { throw new \RuntimeException('Digital product file length mismatch.'); }
                    if (!rename($tmp, $target)) { throw new \RuntimeException('Digital product file cannot be installed.'); }
                    @chmod($target, 0600);
                    $written++;
                } finally {
                    if (is_file($tmp)) { @unlink($tmp); }
                }
            }
        } finally { $zip->close(); }
        return $written;
    }

    private function createSqlSnapshot(array $logicalTables) {
        $dir = defined('DIR_STORAGE') ? rtrim(DIR_STORAGE, '/\\') . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR : rtrim(DIR_UPLOAD, '/\\') . DIRECTORY_SEPARATOR;
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) { throw new \RuntimeException('Unable to create backup directory.'); }
        $file = $dir . 'codecart_catalog_preimport_' . gmdate('Ymd_His') . '_' . substr(hash('sha256', microtime(true) . random_int(1, PHP_INT_MAX)), 0, 8) . '.sql';
        $h = fopen($file, 'wb'); if (!$h) { throw new \RuntimeException('Unable to create pre-import SQL snapshot.'); }
        try {
            foreach ($logicalTables as $logical) {
                if (!$this->tableExists($logical)) { continue; }
                $table = $this->table($logical); $schema = $this->schema($logical);
                $order = $schema['primary'] ? ' ORDER BY ' . implode(',', array_map(function($c){ return '`' . $c . '`'; }, $schema['primary'])) : '';
                fwrite($h, "-- CodeCart PRO pre-import snapshot: `" . $table . "`\nTRUNCATE TABLE `" . $table . "`;\n");
                $offset = 0; $limit = 500;
                while (true) {
                    $q = $this->db->query("SELECT * FROM `" . $table . "`" . $order . " LIMIT " . $offset . "," . $limit);
                    if (!$q->rows) { break; }
                    foreach ($q->rows as $row) {
                        $names = array(); $values = array();
                        foreach ($row as $k => $v) { $names[] = '`' . $k . '`'; $values[] = $v === null ? 'NULL' : "'" . $this->db->escape((string)$v) . "'"; }
                        fwrite($h, "INSERT INTO `" . $table . "` (" . implode(',', $names) . ") VALUES (" . implode(',', $values) . ");\n");
                    }
                    $count = count($q->rows); unset($q);
                    if ($count < $limit) { break; }
                    $offset += $limit;
                }
                fwrite($h, "\n");
            }
        } finally { fclose($h); }
        @chmod($file, 0600);
        return basename($file);
    }

    private function cleanupToken($token){$base=DIR_UPLOAD.'codecart_catalog_'.$token;foreach(array($base.'.json',$base.'.xlsx',$base.'.zip') as $f){if(is_file($f))@unlink($f);}foreach(glob($base.'.*')?:array() as $f){if(is_file($f))@unlink($f);}}
    private function normalizeUrl($url){$url=trim(strtolower((string)$url));if($url==='')return '';$url=preg_replace('#/+$#','',$url);return $url;}
}
