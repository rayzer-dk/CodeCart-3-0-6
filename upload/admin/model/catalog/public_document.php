<?php
class ModelCatalogPublicDocument extends Model {
    public function install() {
        require_once(DIR_SYSTEM . 'library/codecart/documents_schema.php');
        CodeCartDocumentsSchema::install($this->db);
        $this->config->set('codecart_file_stats_status', 1);
        $this->config->set('codecart_public_documents_schema_version', '2.0.8');
    }
    public function get($id) {
        $q=$this->db->query("SELECT * FROM `".DB_PREFIX."codecart_public_document` WHERE document_id=".(int)$id." LIMIT 1");
        return $q->num_rows ? $q->row : array();
    }
    public function descriptions($id) {
        $res=array();
        $q=$this->db->query("SELECT language_id,title FROM `".DB_PREFIX."codecart_public_document_description` WHERE document_id=".(int)$id);
        foreach ($q->rows as $row) $res[(int)$row['language_id']]=$row['title'];
        return $res;
    }
    private function order($sort) {
        $allowed=array('date_added'=>'d.date_added','downloads'=>'d.download_count','name'=>'title','last_download'=>'d.last_download');
        return isset($allowed[$sort]) ? $allowed[$sort] : 'd.date_added';
    }
    public function listing($options) {
        $language=(int)$this->config->get('config_language_id');
        $start=max(0,(int)$options['start']);$limit=max(1,min(100,(int)$options['limit']));
        $order=strtoupper((string)$options['order'])==='ASC'?'ASC':'DESC';$sort=$this->order($options['sort']);
        $filter=trim((string)($options['filter_name']??''));
        $where=$filter!==''?" WHERE (dd.title LIKE '%".$this->db->escape($filter)."%' OR d.original_name LIKE '%".$this->db->escape($filter)."%')":'';
        $q=$this->db->query("SELECT d.*,COALESCE(NULLIF(dd.title,''),d.original_name) AS title,
             COALESCE(s.download_count,0) AS downloads_30d
             FROM `".DB_PREFIX."codecart_public_document` d
             LEFT JOIN `".DB_PREFIX."codecart_public_document_description` dd ON dd.document_id=d.document_id AND dd.language_id=".$language."
             LEFT JOIN (SELECT document_id,SUM(download_count) AS download_count FROM `".DB_PREFIX."codecart_public_document_daily` WHERE day >= DATE_SUB(CURDATE(),INTERVAL 29 DAY) GROUP BY document_id) s ON s.document_id=d.document_id
             ".$where." ORDER BY ".$sort." ".$order.",d.document_id DESC LIMIT ".$start.",".$limit);
        return $q->rows;
    }
    public function total($filter='') {
        $filter=trim((string)$filter);
        $where=$filter!==''?" WHERE (dd.title LIKE '%".$this->db->escape($filter)."%' OR d.original_name LIKE '%".$this->db->escape($filter)."%')":'';
        $q=$this->db->query("SELECT COUNT(*) AS total FROM `".DB_PREFIX."codecart_public_document` d LEFT JOIN `".DB_PREFIX."codecart_public_document_description` dd ON (dd.document_id=d.document_id AND dd.language_id=".(int)$this->config->get('config_language_id').")".$where);
        return (int)$q->row['total'];
    }
    public function save($id,$data,$descriptions,$upload) {
        $old=$id?$this->get($id):array();
        if ($id && !$old) throw new RuntimeException('Document not found');
        $filename=$old?(string)$old['filename']:'';
        $name=$old?(string)$old['original_name']:'';
        $size=$old?(int)$old['filesize']:0;
        $mime=$old?(string)$old['mime']:'application/octet-stream';
        if ($upload && isset($upload['error']) && (int)$upload['error']!==UPLOAD_ERR_NO_FILE) {
            if ((int)$upload['error']!==UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) throw new RuntimeException('File upload failed');
            if ((int)$upload['size']<1 || (int)$upload['size']>26214400) throw new RuntimeException('File must be between 1 byte and 25 MB');
            $name=basename(str_replace('\\','/',(string)$upload['name']));
            $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
            $allowed=array('pdf'=>array('application/pdf'),'docx'=>array('application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/zip'),'doc'=>array('application/msword','application/x-ole-storage','application/octet-stream'),
                'xlsx'=>array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/zip'), 'xls'=>array('application/vnd.ms-excel','application/x-ole-storage','application/octet-stream'),
                'jpg'=>array('image/jpeg'),'jpeg'=>array('image/jpeg'),'png'=>array('image/png'),'webp'=>array('image/webp'),
                'txt'=>array('text/plain'),'zip'=>array('application/zip','application/x-zip-compressed'));
            if (!isset($allowed[$ext]) || $name==='' || strlen($name)>240) throw new RuntimeException('Unsupported file extension');
            $finfo=new finfo(FILEINFO_MIME_TYPE);$detected=(string)$finfo->file($upload['tmp_name']);
            if (!in_array($detected,$allowed[$ext],true)) throw new RuntimeException('File content/type mismatch');
            $base=rtrim(DIR_STORAGE,'/\\').'/public_documents';
            if (!is_dir($base) && !mkdir($base,0750,true) && !is_dir($base)) throw new RuntimeException('Storage directory unavailable');
            $web=realpath(dirname(rtrim(DIR_CATALOG,'/\\')));
            $storage=realpath(DIR_STORAGE);
            if ($web && $storage && ($storage===$web || strpos($storage,rtrim($web,'/\\').DIRECTORY_SEPARATOR)===0)) {
                throw new RuntimeException('Public documents require storage outside the web root');
            }
            $filename=bin2hex(random_bytes(20)).'.'.$ext;
            $temp=$base.'/.'.bin2hex(random_bytes(16)).'.tmp';
            if (!move_uploaded_file($upload['tmp_name'],$temp)) throw new RuntimeException('Unable to store uploaded file');
            @chmod($temp,0640);
            if (!rename($temp,$base.'/'.$filename)) { @unlink($temp);throw new RuntimeException('Unable to publish uploaded file'); }
            $size=(int)$upload['size'];$mime=$detected;
            // Replaced file is deliberately retained for manual recovery, not deleted.
        }
        if (!$filename) throw new RuntimeException('Choose a file');
        $kind=$data['kind'];$target=$data['target_type'];$target_id=(int)$data['target_id'];
        if ($target==='none') $target_id=0;
        if ($target!=='none') {
            $source=$target==='product'?'product':'category';
            $exists=$this->db->query("SELECT ".$source."_id FROM `".DB_PREFIX.$source."` WHERE ".$source."_id=".$target_id." LIMIT 1");
            if (!$exists->num_rows) throw new RuntimeException('Product or category ID does not exist');
        }
        $status=(int)$data['status'];$sort=(int)$data['sort_order'];
        $common="kind='".$this->db->escape($kind)."', target_type='".$this->db->escape($target)."', target_id=".$target_id.", filename='".$this->db->escape($filename)."',original_name='".$this->db->escape($name)."',filesize=".$size.",mime='".$this->db->escape($mime)."',status=".$status.",sort_order=".$sort.",date_modified=NOW()";
        if ($id) $this->db->query("UPDATE `".DB_PREFIX."codecart_public_document` SET ".$common." WHERE document_id=".$id);
        else { $this->db->query("INSERT INTO `".DB_PREFIX."codecart_public_document` SET ".$common.",date_added=NOW()");$id=(int)$this->db->getLastId(); }
        foreach ($descriptions as $language_id=>$title) {
            $lang=(int)$language_id;$title=trim((string)$title);
            $this->db->query("INSERT INTO `".DB_PREFIX."codecart_public_document_description` SET document_id=".$id.",language_id=".$lang.",title='".$this->db->escape($title)."' ON DUPLICATE KEY UPDATE title=VALUES(title)");
        }
        $this->syncVisibility();
        return $id;
    }
    public function autocomplete($filter, $limit = 20) {
        $filter = trim((string)$filter);
        $limit = max(1, min(30, (int)$limit));
        $language = (int)$this->config->get('config_language_id');
        $where = $filter !== '' ? " AND (dd.title LIKE '%" . $this->db->escape($filter) . "%' OR d.original_name LIKE '%" . $this->db->escape($filter) . "%')" : '';
        $q = $this->db->query("SELECT d.document_id, COALESCE(NULLIF(dd.title,''),d.original_name) AS name FROM `" . DB_PREFIX . "codecart_public_document` d LEFT JOIN `" . DB_PREFIX . "codecart_public_document_description` dd ON dd.document_id=d.document_id AND dd.language_id=" . $language . " WHERE d.status=1" . $where . " ORDER BY name ASC LIMIT " . $limit);
        return $q->rows;
    }
    public function selectedForProduct($product_id) {
        $product_id = (int)$product_id;
        if ($product_id < 1 || !$this->config->get('codecart_file_stats_status') || (string)$this->config->get('codecart_public_documents_schema_version') !== '2.0.8') return array();
        $lang = (int)$this->config->get('config_language_id');
        $q = $this->db->query("SELECT d.document_id, COALESCE(NULLIF(dd.title,''),d.original_name) AS name FROM `" . DB_PREFIX . "codecart_document_to_product` l INNER JOIN `" . DB_PREFIX . "codecart_public_document` d ON d.document_id=l.document_id LEFT JOIN `" . DB_PREFIX . "codecart_public_document_description` dd ON dd.document_id=d.document_id AND dd.language_id=" . $lang . " WHERE l.product_id=" . $product_id . " ORDER BY l.sort_order,l.document_id");
        return $q->rows;
    }
    public function selectedByIds($ids) {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array)$ids))));
        if (!$ids || !$this->config->get('codecart_file_stats_status')) return array();
        $lang = (int)$this->config->get('config_language_id');
        $q = $this->db->query("SELECT d.document_id,COALESCE(NULLIF(dd.title,''),d.original_name) AS name FROM `" . DB_PREFIX . "codecart_public_document` d LEFT JOIN `" . DB_PREFIX . "codecart_public_document_description` dd ON dd.document_id=d.document_id AND dd.language_id=" . $lang . " WHERE d.document_id IN (" . implode(',', $ids) . ")");
        return $q->rows;
    }
    public function syncProduct($product_id, $ids) {
        $product_id = (int)$product_id;
        if ($product_id < 1 || !$this->config->get('codecart_file_stats_status')) return;
        $ids = array_values(array_unique(array_filter(array_map('intval', (array)$ids))));
        $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_document_to_product` WHERE product_id=" . $product_id);
        if (!$ids) return;
        $available = $this->db->query("SELECT document_id FROM `" . DB_PREFIX . "codecart_public_document` WHERE document_id IN (" . implode(',', $ids) . ")");
        $exists = array(); foreach ($available->rows as $row) $exists[(int)$row['document_id']] = true;
        foreach ($ids as $index=>$id) {
            if (!isset($exists[$id])) continue;
            $this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_document_to_product` SET document_id=" . $id . ",product_id=" . $product_id . ",sort_order=" . (int)$index);
        }
    }
    private function syncVisibility() {
        $count=$this->db->query("SELECT document_id FROM `".DB_PREFIX."codecart_public_document` WHERE status=1 LIMIT 1");
        $enabled=$count->num_rows?1:0;
        $this->db->query("UPDATE `".DB_PREFIX."setting` SET value='".$enabled."' WHERE store_id=0 AND `key`='codecart_public_documents_status'");
        $this->config->set('codecart_public_documents_status',$enabled);
    }
    public function delete($ids) {
        foreach ($ids as $id) {
            $id=(int)$id;if (!$id)continue;
            $this->db->query("DELETE FROM `".DB_PREFIX."codecart_document_to_product` WHERE document_id=".$id);
            $this->db->query("DELETE FROM `".DB_PREFIX."codecart_public_document_description` WHERE document_id=".$id);
            $this->db->query("DELETE FROM `".DB_PREFIX."codecart_public_document_daily` WHERE document_id=".$id);
            $this->db->query("DELETE FROM `".DB_PREFIX."codecart_public_document` WHERE document_id=".$id);
        }
        $this->syncVisibility();
        // Files remain in protected storage for recovery: do not automatically unlink.
    }
}
