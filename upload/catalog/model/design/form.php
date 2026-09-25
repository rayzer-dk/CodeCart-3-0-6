<?php
class ModelDesignForm extends Model {
    public function getForm($form_id, $language_id = 0) {
        $form_id = (int)$form_id;
        $language_id = $language_id ? (int)$language_id : (int)$this->config->get('config_language_id');
        if ($form_id < 1 || $language_id < 1) { return array(); }
        $query = $this->db->query("SELECT f.form_id,f.name,f.kind,f.recipient,f.status,fd.title,fd.description,fd.submit_text,fd.success_text,fd.fields FROM " . DB_PREFIX . "codecart_form f INNER JOIN " . DB_PREFIX . "codecart_form_description fd ON (f.form_id=fd.form_id) WHERE f.form_id='" . $form_id . "' AND f.status='1' AND fd.language_id='" . $language_id . "' LIMIT 1");
        if (!$query->num_rows) { return array(); }
        $row = $query->row;
        $row['fields'] = \CodeCart\Core\FormBuilder::decode($row['fields']);
        return $row;
    }
}
