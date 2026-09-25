<?php
namespace CodeCart\Core;

final class ExtensionInstallJournal {
    private $root;

    public function __construct() {
        if (!defined('DIR_STORAGE')) {
            throw new \RuntimeException('DIR_STORAGE is not defined.');
        }
        $this->root = rtrim(str_replace('\\', '/', DIR_STORAGE), '/') . '/codecart/extension_installer/';
    }

    public function prepare($installId, $filename, array $operations) {
        $installId = (int)$installId;
        if ($installId < 1) { throw new \InvalidArgumentException('Invalid extension install id.'); }

        $directory = $this->directory($installId);
        if (is_dir($directory)) { $this->removeTree($directory); }
        if (!mkdir($directory . '/backup', 0750, true) && !is_dir($directory . '/backup')) {
            throw new \RuntimeException('Unable to create extension installer journal directory.');
        }

        $entries = array();
        $conflicts = array();
        $seen = array();
        foreach ($operations as $operation) {
            $logical = $this->normalizeLogical(isset($operation['logical']) ? $operation['logical'] : '');
            $source = isset($operation['source']) ? (string)$operation['source'] : '';
            $destination = isset($operation['destination']) ? (string)$operation['destination'] : '';
            if ($logical === '' || !is_file($source) || $destination === '' || isset($seen[$logical])) {
                throw new \RuntimeException('Invalid or duplicate extension installer operation.');
            }
            $seen[$logical] = true;
            $this->assertDestinationSafe($logical, $destination);
            if (is_dir($destination)) { throw new \RuntimeException('Extension target is a directory: ' . $logical); }

            $entry = array(
                'path' => $logical,
                'state' => is_file($destination) ? 'overwritten' : 'created',
                'before_sha256' => '',
                'installed_sha256' => '',
                'applied' => false
            );

            if ($entry['state'] === 'overwritten') {
                $entry['before_sha256'] = (string)hash_file('sha256', $destination);
                $backup = $directory . '/backup/' . $logical;
                $this->ensureParent($backup);
                if (!copy($destination, $backup)) {
                    throw new \RuntimeException('Unable to back up existing extension target: ' . $logical);
                }
                $conflicts[] = $logical;
            }
            $entries[] = $entry;
        }

        $journal = array(
            'version' => 2,
            'extension_install_id' => $installId,
            'filename' => basename((string)$filename),
            'created_at' => gmdate('c'),
            'status' => 'prepared',
            'previous_modification' => null,
            'entries' => $entries
        );
        $this->write($installId, $journal);
        return array('conflicts' => $conflicts, 'count' => count($entries));
    }

    public function has($installId) {
        return is_file($this->directory((int)$installId) . '/journal.json');
    }

    public function applyFile($installId, $logical, $source, $destination) {
        $installId = (int)$installId;
        $logical = $this->normalizeLogical($logical);
        $journal = $this->read($installId);
        $index = $this->findEntry($journal, $logical);
        if ($index < 0) { throw new \RuntimeException('Extension file is missing from prepared journal: ' . $logical); }
        if (!is_file($source)) { throw new \RuntimeException('Extension source file disappeared: ' . $logical); }
        $this->assertDestinationSafe($logical, $destination);

        $this->ensureParent($destination);
        $temp = $destination . '.codecart-' . bin2hex(random_bytes(6)) . '.tmp';
        if (!copy($source, $temp)) { throw new \RuntimeException('Unable to stage extension file: ' . $logical); }
        @chmod($temp, 0644);
        if (!$this->activateStagedFile($temp, $destination)) {
            @unlink($temp);
            throw new \RuntimeException('Unable to activate extension file: ' . $logical);
        }

        $journal['entries'][$index]['installed_sha256'] = (string)hash_file('sha256', $destination);
        $journal['entries'][$index]['applied'] = true;
        $journal['status'] = 'applying';
        $this->write($installId, $journal);
    }

    public function backupModification($installId, array $modification) {
        $journal = $this->read((int)$installId);
        if (!empty($journal['previous_modification'])) { return; }
        $allowed = array('extension_install_id','name','code','author','version','link','xml','status');
        $backup = array();
        foreach ($allowed as $key) {
            if (array_key_exists($key, $modification)) { $backup[$key] = $modification[$key]; }
        }
        if (!empty($backup['code'])) {
            $journal['previous_modification'] = $backup;
            $this->write((int)$installId, $journal);
        }
    }

    public function getPreviousModification($installId) {
        $journal = $this->read((int)$installId);
        return !empty($journal['previous_modification']) && is_array($journal['previous_modification']) ? $journal['previous_modification'] : array();
    }

    public function complete($installId) {
        $journal = $this->read((int)$installId);
        $journal['status'] = 'complete';
        $journal['completed_at'] = gmdate('c');
        $this->write((int)$installId, $journal);
    }

    public function detectChangedFiles($installId) {
        if (!$this->has($installId)) { return array(); }
        $journal = $this->read((int)$installId);
        $changed = array();
        foreach ($journal['entries'] as $entry) {
            if (empty($entry['applied'])) { continue; }
            $destination = $this->destinationFromLogical($entry['path']);
            if ($destination === '' || !is_file($destination)) { continue; }
            $currentHash = (string)hash_file('sha256', $destination);
            $installedHash = isset($entry['installed_sha256']) ? (string)$entry['installed_sha256'] : '';
            if ($installedHash !== '' && !hash_equals($installedHash, $currentHash)) { $changed[] = $entry['path']; }
        }
        return $changed;
    }

    public function rollback($installId) { return $this->restore((int)$installId, true); }
    public function uninstall($installId) { return $this->restore((int)$installId, false); }

    public function summary($installId) {
        $journal = $this->read((int)$installId);
        $conflicts = array();
        foreach ($journal['entries'] as $entry) {
            if ($entry['state'] === 'overwritten') { $conflicts[] = $entry['path']; }
        }
        return array('status' => $journal['status'], 'conflicts' => $conflicts, 'count' => count($journal['entries']));
    }

    private function restore($installId, $rollback) {
        if (!$this->has($installId)) { return array('restored' => 0, 'removed' => 0, 'skipped' => array()); }
        $journal = $this->read($installId);
        $restored = 0;
        $removed = 0;
        $skipped = array();

        for ($i = count($journal['entries']) - 1; $i >= 0; $i--) {
            $entry = $journal['entries'][$i];
            if (empty($entry['applied'])) { continue; }
            $destination = $this->destinationFromLogical($entry['path']);
            if ($destination === '') { $skipped[] = $entry['path']; continue; }

            $currentHash = is_file($destination) ? (string)hash_file('sha256', $destination) : '';
            $installedHash = isset($entry['installed_sha256']) ? (string)$entry['installed_sha256'] : '';
            if (!$rollback && $currentHash !== '' && $installedHash !== '' && !hash_equals($installedHash, $currentHash)) {
                $skipped[] = $entry['path'];
                continue;
            }

            if ($entry['state'] === 'overwritten') {
                $backup = $this->directory($installId) . '/backup/' . $entry['path'];
                if (!is_file($backup)) { $skipped[] = $entry['path']; continue; }
                $this->ensureParent($destination);
                $temp = $destination . '.codecart-restore-' . bin2hex(random_bytes(6)) . '.tmp';
                if (!copy($backup, $temp) || !$this->activateStagedFile($temp, $destination)) {
                    @unlink($temp);
                    $skipped[] = $entry['path'];
                    continue;
                }
                $restored++;
                $journal['entries'][$i]['applied'] = false;
                $journal['entries'][$i]['restored'] = true;
            } else {
                if (is_file($destination) && !@unlink($destination)) {
                    $skipped[] = $entry['path'];
                    continue;
                }
                if (!is_file($destination)) {
                    $removed++;
                    $this->removeEmptyParents(dirname($destination));
                    $journal['entries'][$i]['applied'] = false;
                    $journal['entries'][$i]['removed'] = true;
                }
            }
        }

        $journal['status'] = $skipped ? ($rollback ? 'partial_rollback' : 'partial_uninstall') : ($rollback ? 'rolled_back' : 'uninstalled');
        $journal['restored_at'] = gmdate('c');
        $journal['skipped'] = array_values(array_unique($skipped));
        $this->write($installId, $journal);
        return array('restored' => $restored, 'removed' => $removed, 'skipped' => $journal['skipped']);
    }

    private function activateStagedFile($staged, $destination) {
        if (!is_file($staged)) { return false; }
        if (!file_exists($destination)) { return @rename($staged, $destination); }

        // POSIX rename replaces atomically. On Windows it may fail if target exists,
        // so fall back to a reversible two-step swap instead of unlinking the target.
        if (@rename($staged, $destination)) { return true; }
        $old = $destination . '.codecart-old-' . bin2hex(random_bytes(6));
        if (!@rename($destination, $old)) { return false; }
        if (@rename($staged, $destination)) {
            @unlink($old);
            return true;
        }
        @rename($old, $destination);
        return false;
    }

    private function destinationFromLogical($logical) {
        $logical = $this->normalizeLogical($logical);
        if (strpos($logical, 'admin/') === 0) { return DIR_APPLICATION . substr($logical, 6); }
        if (strpos($logical, 'catalog/') === 0) { return DIR_CATALOG . substr($logical, 8); }
        if (strpos($logical, 'image/') === 0) { return DIR_IMAGE . substr($logical, 6); }
        if (strpos($logical, 'system/') === 0) { return DIR_SYSTEM . substr($logical, 7); }
        return '';
    }

    private function assertDestinationSafe($logical, $destination) {
        $expected = $this->destinationFromLogical($logical);
        if ($expected === '' || str_replace('\\', '/', $expected) !== str_replace('\\', '/', (string)$destination)) {
            throw new \RuntimeException('Extension destination mismatch: ' . $logical);
        }
        if (is_link($destination)) { throw new \RuntimeException('Symlink extension target is not allowed: ' . $logical); }
        $root = '';
        if (strpos($logical, 'admin/') === 0) { $root = DIR_APPLICATION; }
        elseif (strpos($logical, 'catalog/') === 0) { $root = DIR_CATALOG; }
        elseif (strpos($logical, 'image/') === 0) { $root = DIR_IMAGE; }
        elseif (strpos($logical, 'system/') === 0) { $root = DIR_SYSTEM; }
        $rootReal = realpath($root);
        if ($rootReal === false) { throw new \RuntimeException('Extension target root is unavailable.'); }
        $parent = dirname($destination);
        while (!is_dir($parent) && dirname($parent) !== $parent) { $parent = dirname($parent); }
        $parentReal = realpath($parent);
        $rootPrefix = rtrim(str_replace('\\', '/', $rootReal), '/') . '/';
        $parentNormalized = $parentReal ? rtrim(str_replace('\\', '/', $parentReal), '/') . '/' : '';
        if ($parentNormalized === '' || strpos($parentNormalized, $rootPrefix) !== 0) {
            throw new \RuntimeException('Extension target escapes its allowed root: ' . $logical);
        }
    }

    private function findEntry(array $journal, $logical) {
        foreach ($journal['entries'] as $index => $entry) {
            if (isset($entry['path']) && $entry['path'] === $logical) { return $index; }
        }
        return -1;
    }

    private function normalizeLogical($path) {
        $path = ltrim(str_replace('\\', '/', (string)$path), '/');
        if ($path === '' || strpos($path, "\0") !== false || preg_match('#(?:^|/)\.\.(?:/|$)#', $path)) { return ''; }
        return $path;
    }

    private function directory($installId) { return $this->root . (int)$installId; }

    private function read($installId) {
        $file = $this->directory($installId) . '/journal.json';
        $json = is_file($file) ? file_get_contents($file) : false;
        $data = $json !== false ? json_decode($json, true) : null;
        if (!is_array($data) || !isset($data['entries']) || !is_array($data['entries'])) {
            throw new \RuntimeException('Extension installer journal is missing or invalid.');
        }
        if (!array_key_exists('previous_modification', $data)) { $data['previous_modification'] = null; }
        return $data;
    }

    private function write($installId, array $journal) {
        $directory = $this->directory($installId);
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create extension installer journal directory.');
        }
        $file = $directory . '/journal.json';
        $temp = $file . '.tmp-' . bin2hex(random_bytes(6));
        $json = json_encode($journal, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false || file_put_contents($temp, $json, LOCK_EX) === false) {
            @unlink($temp);
            throw new \RuntimeException('Unable to write extension installer journal.');
        }
        @chmod($temp, 0640);
        if (!$this->activateStagedFile($temp, $file)) {
            @unlink($temp);
            throw new \RuntimeException('Unable to activate extension installer journal.');
        }
    }

    private function ensureParent($file) {
        $directory = dirname($file);
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create extension target directory.');
        }
    }

    private function removeEmptyParents($directory) {
        $roots = array(
            rtrim(str_replace('\\', '/', DIR_APPLICATION), '/'), rtrim(str_replace('\\', '/', DIR_CATALOG), '/'),
            rtrim(str_replace('\\', '/', DIR_IMAGE), '/'), rtrim(str_replace('\\', '/', DIR_SYSTEM), '/')
        );
        $directory = rtrim(str_replace('\\', '/', $directory), '/');
        while ($directory !== '' && !in_array($directory, $roots, true) && is_dir($directory)) {
            $items = array_diff(scandir($directory), array('.', '..'));
            if ($items) { break; }
            @rmdir($directory);
            $directory = dirname($directory);
        }
    }

    private function removeTree($directory) {
        if (!is_dir($directory)) { return; }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            if ($item->isDir() && !$item->isLink()) { @rmdir($item->getPathname()); }
            else { @unlink($item->getPathname()); }
        }
        @rmdir($directory);
    }
}
