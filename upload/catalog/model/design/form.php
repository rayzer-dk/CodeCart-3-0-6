<?php
class ModelDesignForm extends Model {
    public function getForm($form_id, $language_id = 0) {
        $form_id = (int)$form_id;
        $language_id = $language_id ? (int)$language_id : (int)$this->config->get('config_language_id');
        if ($form_id < 1 || $language_id < 1) { return array(); }
        if ((int)$this->config->get('codecart_presentation_schema_version') >= 31) {
            $style_select = "f.button_icon,f.button_bg,f.button_text_color,f.button_hover_bg";
        } else {
            $style_columns = array('button_icon'=>"'fa fa-envelope-o'", 'button_bg'=>"'#0b6fd3'", 'button_text_color'=>"'#ffffff'", 'button_hover_bg'=>"'#095eb4'");
            $available = array();
            $columns = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "codecart_form`");
            foreach ($columns->rows as $row) { if (isset($row['Field'])) { $available[(string)$row['Field']] = true; } }
            foreach ($style_columns as $column => $fallback) { $style_columns[$column] = isset($available[$column]) ? 'f.`' . $column . '`' : $fallback; }
            $style_select = $style_columns['button_icon'] . " AS button_icon," . $style_columns['button_bg'] . " AS button_bg," . $style_columns['button_text_color'] . " AS button_text_color," . $style_columns['button_hover_bg'] . " AS button_hover_bg";
        }
        $query = $this->db->query("SELECT f.form_id,f.name,f.kind,f.recipient," . $style_select . ",f.status,fd.title,fd.description,fd.submit_text,fd.success_text,fd.fields FROM " . DB_PREFIX . "codecart_form f INNER JOIN " . DB_PREFIX . "codecart_form_description fd ON (f.form_id=fd.form_id) WHERE f.form_id='" . $form_id . "' AND f.status='1' AND fd.language_id='" . $language_id . "' LIMIT 1");
        if (!$query->num_rows) { return array(); }
        $row = $query->row;
        $row['fields'] = \CodeCart\Core\FormBuilder::decode($row['fields']);
        return $row;
    }
}
