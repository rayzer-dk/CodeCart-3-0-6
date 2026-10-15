<?php
class ModelCatalogPublicDocument extends Model {
    public function forTarget($kind,$targetId) {
        if (!$this->config->get('codecart_public_documents_status')) return array();
        if (!in_array($kind,array('product','category'),true) || (int)$targetId<1) return array();
        $language_id=(int)$this->config->get('config_language_id');
        $id=(int)$targetId;
        $link = $kind==='product' && (string)$this->config->get('codecart_public_documents_schema_version') === '2.0.8'
            ? " LEFT JOIN `".DB_PREFIX."codecart_document_to_product` l ON l.document_id=d.document_id AND l.product_id=".$id
            : '';
        $where = $kind==='product'
            ? ((string)$this->config->get('codecart_public_documents_schema_version') === '2.0.8' ? "((d.target_type='product' AND d.target_id=".$id.") OR l.product_id IS NOT NULL)" : "(d.target_type='product' AND d.target_id=".$id.")")
            : "(d.target_type='category' AND d.target_id=".$id.")";
        $q=$this->db->query("SELECT d.document_id,d.kind,d.original_name,d.filesize,
            COALESCE(NULLIF(dd.title,''),NULLIF(df.title,''),d.original_name) AS title
            FROM `".DB_PREFIX."codecart_public_document` d".$link."
            LEFT JOIN `".DB_PREFIX."codecart_public_document_description` dd ON dd.document_id=d.document_id AND dd.language_id=".$language_id."
            LEFT JOIN `".DB_PREFIX."codecart_public_document_description` df ON df.document_id=d.document_id AND df.language_id=(SELECT language_id FROM `".DB_PREFIX."language` WHERE status=1 ORDER BY sort_order,language_id LIMIT 1)
            WHERE d.status=1 AND ".$where." ORDER BY d.sort_order,d.document_id LIMIT 50");
        $out=array();foreach ($q->rows as $r) {
           $r['href']=$this->url->link('information/public_document/download','document_id='.(int)$r['document_id'],true);
           $out[]=$r;
        }return $out;
    }
    public function getPublic($id) {
        if (!$this->config->get('codecart_public_documents_status'))return array();
        $q=$this->db->query("SELECT document_id,filename,original_name,filesize FROM `".DB_PREFIX."codecart_public_document` WHERE document_id=".(int)$id." AND status=1 LIMIT 1");
        return $q->num_rows?$q->row:array();
    }
    public function increment($id) {
        $this->db->query("UPDATE `".DB_PREFIX."codecart_public_document` SET download_count=download_count+1,last_download=NOW() WHERE document_id=".(int)$id." AND status=1");
        $this->db->query("INSERT INTO `".DB_PREFIX."codecart_public_document_daily` SET document_id=".(int)$id.",day=CURDATE(),download_count=1 ON DUPLICATE KEY UPDATE download_count=download_count+1");
    }
}
