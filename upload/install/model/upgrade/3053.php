<?php
class ModelUpgrade3053 extends Model {
    public function upgrade() {
        require_once(DIR_SYSTEM . 'library/codecart/documents_schema.php');
        CodeCartDocumentsSchema::install($this->db);
    }
}
