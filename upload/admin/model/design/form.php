<?php
class ModelDesignForm extends Model {
    public function addForm($data) {
        $this->ensureButtonStyleColumns();
        $name = $this->plainText(isset($data['name']) ? $data['name'] : '', 128);
        $recipient = $this->normalizeEmail(isset($data['recipient']) ? $data['recipient'] : '');
        $status = !empty($data['status']) ? 1 : 0;
        $kind = isset($data['kind']) && (string)$data['kind'] === 'info' ? 'info' : 'request';
        $style = $this->normalizeButtonStyle($data);
        $this->db->query("INSERT INTO " . DB_PREFIX . "codecart_form SET name='" . $this->db->escape($name) . "', kind='" . $this->db->escape($kind) . "', recipient='" . $this->db->escape($recipient) . "', button_icon='" . $this->db->escape($style['button_icon']) . "', button_bg='" . $this->db->escape($style['button_bg']) . "', button_text_color='" . $this->db->escape($style['button_text_color']) . "', button_hover_bg='" . $this->db->escape($style['button_hover_bg']) . "', status='" . $status . "', date_added=NOW(), date_modified=NOW()");
        $form_id = (int)$this->db->getLastId();
        $this->saveDescriptions($form_id, isset($data['form_description']) ? $data['form_description'] : array());
        return $form_id;
    }

    public function editForm($form_id, $data) {
        $this->ensureButtonStyleColumns();
        $form_id = (int)$form_id;
        $name = $this->plainText(isset($data['name']) ? $data['name'] : '', 128);
        $recipient = $this->normalizeEmail(isset($data['recipient']) ? $data['recipient'] : '');
        $status = !empty($data['status']) ? 1 : 0;
        $kind = isset($data['kind']) && (string)$data['kind'] === 'info' ? 'info' : 'request';
        $style = $this->normalizeButtonStyle($data);
        $this->db->query("UPDATE " . DB_PREFIX . "codecart_form SET name='" . $this->db->escape($name) . "', kind='" . $this->db->escape($kind) . "', recipient='" . $this->db->escape($recipient) . "', button_icon='" . $this->db->escape($style['button_icon']) . "', button_bg='" . $this->db->escape($style['button_bg']) . "', button_text_color='" . $this->db->escape($style['button_text_color']) . "', button_hover_bg='" . $this->db->escape($style['button_hover_bg']) . "', status='" . $status . "', date_modified=NOW() WHERE form_id='" . $form_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "codecart_form_description WHERE form_id='" . $form_id . "'");
        $this->saveDescriptions($form_id, isset($data['form_description']) ? $data['form_description'] : array());
    }

    public function deleteForm($form_id) {
        $form_id = (int)$form_id;
        $this->db->query("DELETE FROM " . DB_PREFIX . "codecart_form WHERE form_id='" . $form_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "codecart_form_description WHERE form_id='" . $form_id . "'");
    }

    public function getForm($form_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "codecart_form WHERE form_id='" . (int)$form_id . "' LIMIT 1");
        return $query->row;
    }

    public function getFormDescriptions($form_id) {
        $data = array();
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "codecart_form_description WHERE form_id='" . (int)$form_id . "'");
        foreach ($query->rows as $row) {
            $data[(int)$row['language_id']] = array(
                'title' => $row['title'],
                'description' => $row['description'],
                'submit_text' => $row['submit_text'],
                'success_text' => $row['success_text'],
                'fields_json' => \CodeCart\Core\FormBuilder::encode($row['fields'])
            );
        }
        return $data;
    }

    public function getForms($data = array()) {
        $sql = "SELECT f.*, fd.title FROM " . DB_PREFIX . "codecart_form f LEFT JOIN " . DB_PREFIX . "codecart_form_description fd ON (f.form_id=fd.form_id AND fd.language_id='" . (int)$this->config->get('config_language_id') . "')";
        $sort_data = array('f.name','fd.title','f.status','f.form_id');
        $sort = isset($data['sort']) && in_array($data['sort'], $sort_data, true) ? $data['sort'] : 'f.name';
        $order = isset($data['order']) && strtoupper((string)$data['order']) === 'DESC' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY " . $sort . " " . $order;
        if (isset($data['start']) || isset($data['limit'])) {
            $start = max(0, (int)(isset($data['start']) ? $data['start'] : 0));
            $limit = max(1, min(100, (int)(isset($data['limit']) ? $data['limit'] : 25)));
            $sql .= " LIMIT " . $start . "," . $limit;
        }
        return $this->db->query($sql)->rows;
    }

    public function getTotalForms() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "codecart_form");
        return isset($query->row['total']) ? (int)$query->row['total'] : 0;
    }

    public function getFormOptions() {
        $rows = $this->getForms(array('sort'=>'f.name','order'=>'ASC','start'=>0,'limit'=>500));
        $out = array();
        foreach ($rows as $row) {
            $out[] = array('form_id'=>(int)$row['form_id'], 'name'=>(string)$row['name'], 'title'=>(string)(isset($row['title']) ? $row['title'] : ''), 'status'=>(int)$row['status']);
        }
        return $out;
    }

    private function saveDescriptions($form_id, $descriptions) {
        foreach ((array)$descriptions as $language_id => $row) {
            $language_id = (int)$language_id;
            if ($language_id < 1 || !is_array($row)) { continue; }
            // OpenCart's Request escapes every POST value with htmlspecialchars(). Store plain
            // text and real (sanitized) HTML, like the bundled forms do: otherwise titles show
            // "&amp;"/"&quot;" and descriptions show literal "<b>" on the storefront.
            $title = $this->plainText(isset($row['title']) ? $row['title'] : '', 160);
            $description = \CodeCart\Core\SafeRichHtml::sanitize(html_entity_decode((string)(isset($row['description']) ? $row['description'] : ''), ENT_QUOTES, 'UTF-8'));
            $submit_text = $this->plainText(isset($row['submit_text']) ? $row['submit_text'] : '', 80);
            $success_text = $this->plainText(isset($row['success_text']) ? $row['success_text'] : '', 255);
            $fields = \CodeCart\Core\FormBuilder::encode(isset($row['fields_json']) ? $row['fields_json'] : array());
            $this->db->query("INSERT INTO " . DB_PREFIX . "codecart_form_description SET form_id='" . (int)$form_id . "', language_id='" . $language_id . "', title='" . $this->db->escape($title) . "', description='" . $this->db->escape($description) . "', submit_text='" . $this->db->escape($submit_text) . "', success_text='" . $this->db->escape($success_text) . "', fields='" . $this->db->escape($fields) . "'");
        }
    }

    private function plainText($value, $limit) {
        $value = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
        return utf8_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($value))), 0, (int)$limit);
    }

    private function ensureButtonStyleColumns() {
        static $ready = false;
        if ($ready) { return; }

        $table = DB_PREFIX . 'codecart_form';
        $exists = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table) . "'");
        if (!$exists->num_rows) { return; }

        $columns = array(
            'button_icon' => "varchar(64) NOT NULL DEFAULT 'fa-envelope-o' AFTER `recipient`",
            'button_bg' => "varchar(7) NOT NULL DEFAULT '#0b6fd3' AFTER `button_icon`",
            'button_text_color' => "varchar(7) NOT NULL DEFAULT '#ffffff' AFTER `button_bg`",
            'button_hover_bg' => "varchar(7) NOT NULL DEFAULT '#095eb4' AFTER `button_text_color`"
        );

        foreach ($columns as $column => $definition) {
            $check = $this->db->query("SHOW COLUMNS FROM `" . $table . "` LIKE '" . $this->db->escape($column) . "'");
            if (!$check->num_rows) {
                $this->db->query("ALTER TABLE `" . $table . "` ADD `" . $column . "` " . $definition);
            }
        }

        $ready = true;
    }

    private function normalizeButtonStyle($data) {
        $icon = isset($data['button_icon']) ? trim((string)$data['button_icon']) : 'fa fa-envelope-o';
        if ($icon === '') { $icon = 'fa fa-envelope-o'; }
        if (!preg_match('/^(?:fa(?:-[a-z]+)?\s+)?fa-[a-z0-9-]+(?:\s+fa-[a-z0-9-]+)*$/i', $icon) || strlen($icon) > 64) { $icon = 'fa fa-envelope-o'; }
        return array(
            'button_icon' => $icon,
            'button_bg' => $this->normalizeColor(isset($data['button_bg']) ? $data['button_bg'] : '', '#0b6fd3'),
            'button_text_color' => $this->normalizeColor(isset($data['button_text_color']) ? $data['button_text_color'] : '', '#ffffff'),
            'button_hover_bg' => $this->normalizeColor(isset($data['button_hover_bg']) ? $data['button_hover_bg'] : '', '#095eb4')
        );
    }

    private function normalizeColor($value, $default) {
        $value = strtolower(trim((string)$value));
        return preg_match('/^#[0-9a-f]{6}$/', $value) ? $value : $default;
    }

    private function normalizeEmail($email) {
        $email = trim((string)$email);
        return ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) ? utf8_substr($email, 0, 255) : '';
    }
}
