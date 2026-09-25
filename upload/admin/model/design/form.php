<?php
class ModelDesignForm extends Model {
    public function addForm($data) {
        $name = utf8_substr(trim(preg_replace('/\s+/u', ' ', strip_tags((string)(isset($data['name']) ? $data['name'] : '')))), 0, 128);
        $recipient = $this->normalizeEmail(isset($data['recipient']) ? $data['recipient'] : '');
        $status = !empty($data['status']) ? 1 : 0;
        $kind = isset($data['kind']) && (string)$data['kind'] === 'info' ? 'info' : 'request';
        $this->db->query("INSERT INTO " . DB_PREFIX . "codecart_form SET name='" . $this->db->escape($name) . "', kind='" . $this->db->escape($kind) . "', recipient='" . $this->db->escape($recipient) . "', status='" . $status . "', date_added=NOW(), date_modified=NOW()");
        $form_id = (int)$this->db->getLastId();
        $this->saveDescriptions($form_id, isset($data['form_description']) ? $data['form_description'] : array());
        return $form_id;
    }

    public function editForm($form_id, $data) {
        $form_id = (int)$form_id;
        $name = utf8_substr(trim(preg_replace('/\s+/u', ' ', strip_tags((string)(isset($data['name']) ? $data['name'] : '')))), 0, 128);
        $recipient = $this->normalizeEmail(isset($data['recipient']) ? $data['recipient'] : '');
        $status = !empty($data['status']) ? 1 : 0;
        $kind = isset($data['kind']) && (string)$data['kind'] === 'info' ? 'info' : 'request';
        $this->db->query("UPDATE " . DB_PREFIX . "codecart_form SET name='" . $this->db->escape($name) . "', kind='" . $this->db->escape($kind) . "', recipient='" . $this->db->escape($recipient) . "', status='" . $status . "', date_modified=NOW() WHERE form_id='" . $form_id . "'");
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
            $title = utf8_substr(trim(preg_replace('/\s+/u', ' ', strip_tags((string)(isset($row['title']) ? $row['title'] : '')))), 0, 160);
            $description = \CodeCart\Core\SafeRichHtml::sanitize((string)(isset($row['description']) ? $row['description'] : ''));
            $submit_text = utf8_substr(trim(preg_replace('/\s+/u', ' ', strip_tags((string)(isset($row['submit_text']) ? $row['submit_text'] : '')))), 0, 80);
            $success_text = utf8_substr(trim(preg_replace('/\s+/u', ' ', strip_tags((string)(isset($row['success_text']) ? $row['success_text'] : '')))), 0, 255);
            $fields = \CodeCart\Core\FormBuilder::encode(isset($row['fields_json']) ? $row['fields_json'] : array());
            $this->db->query("INSERT INTO " . DB_PREFIX . "codecart_form_description SET form_id='" . (int)$form_id . "', language_id='" . $language_id . "', title='" . $this->db->escape($title) . "', description='" . $this->db->escape($description) . "', submit_text='" . $this->db->escape($submit_text) . "', success_text='" . $this->db->escape($success_text) . "', fields='" . $this->db->escape($fields) . "'");
        }
    }

    private function normalizeEmail($email) {
        $email = trim((string)$email);
        return ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) ? utf8_substr($email, 0, 255) : '';
    }
}
