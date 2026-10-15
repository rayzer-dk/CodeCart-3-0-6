<?php
/** Idempotent document-to-product migration for CodeCart Build 2.0.8. */
class ModelUpgrade3054 extends Model {
    public function upgrade() {
        require_once(DIR_SYSTEM . 'library/codecart/documents_schema.php');
        CodeCartDocumentsSchema::install($this->db);
    }
}
