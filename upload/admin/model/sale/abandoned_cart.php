<?php
class ModelSaleAbandonedCart extends Model {
    private $customSourceResolved = false;
    private $customSourceCache = null;
    // Optional external SQL cart adapter. Never writes to another module's table.
    private function customSource() {
        if ($this->customSourceResolved) { return $this->customSourceCache; }
        $this->customSourceResolved = true;
        $table = trim((string)$this->config->get('codecart_abandoned_cart_table'));
        if (!preg_match('/^[a-zA-Z0-9_]{1,64}$/', $table)) { return null; }
        if (strpos($table, DB_PREFIX) !== 0) { $table = DB_PREFIX . $table; }
        $fields = array();
        foreach (array('session','product','quantity','date','customer','email','phone') as $key) {
            $value = trim((string)$this->config->get('codecart_abandoned_cart_col_' . $key));
            if ($value === '' && in_array($key, array('customer','email','phone'), true)) { $fields[$key] = ''; continue; }
            if (!preg_match('/^[a-zA-Z0-9_]{1,64}$/', $value)) { return null; }
            $fields[$key] = $value;
        }
        try {
            $cols = $this->db->query('SHOW COLUMNS FROM ' . chr(96) . $table . chr(96))->rows;
            $exists = array();
            foreach ($cols as $col) { $exists[$col['Field']] = true; }
            foreach ($fields as $field) { if ($field !== '' && !isset($exists[$field])) { return null; } }
        } catch (\Throwable $e) { return null; }
        $this->customSourceCache = array('table'=>$table,'fields'=>$fields);
        return $this->customSourceCache;
    }

    /** Read-only schema assistance for an administrator configuring a separate SQL cart table. */
    public function inspectExternalTable(string $table): array {
        $table = trim($table);
        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $table)) {
            return array('valid' => false, 'columns' => array(), 'fields' => array(), 'missing' => array());
        }
        if (strpos($table, DB_PREFIX) !== 0) { $table = DB_PREFIX . $table; }
        $columns = array();
        try {
            $query = $this->db->query('SHOW COLUMNS FROM `' . $table . '`');
            foreach ($query->rows as $row) { $columns[] = (string)$row['Field']; }
        } catch (\Throwable $e) {
            return array('valid' => false, 'columns' => array(), 'fields' => array(), 'missing' => array());
        }
        $candidates = array(
            'col_session' => array('session_id', 'session', 'cart_session_id'),
            'col_product' => array('product_id', 'product', 'id_product'),
            'col_quantity' => array('quantity', 'qty', 'count'),
            'col_date' => array('date_added', 'date_modified', 'updated_at', 'created_at', 'date_create'),
            'col_customer' => array('customer_id', 'id_customer', 'customer'),
            'col_email' => array('email', 'customer_email'),
            'col_phone' => array('telephone', 'phone', 'customer_phone')
        );
        $fields = array();
        $missing = array();
        foreach ($candidates as $key => $names) {
            $found = '';
            foreach ($names as $name) {
                foreach ($columns as $column) {
                    if (strcasecmp($column, $name) === 0) { $found = $column; break 2; }
                }
            }
            $fields[$key] = $found;
            if ($found === '' && in_array($key, array('col_session','col_product','col_quantity','col_date'), true)) {
                $missing[] = $key;
            }
        }
        return array('valid' => true, 'columns' => $columns, 'fields' => $fields, 'missing' => $missing);
    }

    public function getExternalSourceStatus() { return $this->customSource() !== null; }

    private function sourceColumn($fields, $name, $otherwise) {
        return $fields[$name] === '' ? $otherwise : 'c.' . chr(96) . $fields[$name] . chr(96);
    }

    private function externalQuery($count = false) {
        $source = $this->customSource();
        if (!$source) { return ''; }
        $f = $source['fields'];
        $sid = $this->sourceColumn($f, 'session', "''");
        $pid = $this->sourceColumn($f, 'product', '0');
        $qty = $this->sourceColumn($f, 'quantity', '0');
        $dt = $this->sourceColumn($f, 'date', 'NOW()');
        $cid = $this->sourceColumn($f, 'customer', '0');
        $email = $this->sourceColumn($f, 'email', 'cu.email');
        $phone = $this->sourceColumn($f, 'phone', 'cu.telephone');
        $idle = max(1, min(10080, (int)$this->config->get('codecart_abandoned_cart_idle_minutes') ?: 60));
        $days = max(1, min(7, (int)$this->config->get('codecart_abandoned_cart_days') ?: 7));
        $from = ' FROM ' . chr(96) . $source['table'] . chr(96) . ' c LEFT JOIN '
            . chr(96) . DB_PREFIX . 'customer' . chr(96) . ' cu ON cu.customer_id=' . $cid;
        $where = ' WHERE ' . $dt . ' >= DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)'
            . ' AND ' . $sid . " <> '' AND " . $qty . ' > 0 AND ' . $pid . ' > 0';
        if ($count) {
            return 'SELECT COUNT(*) AS total FROM (SELECT ' . $sid . ' AS session_id, ' . $cid . ' AS customer_id'
                . $from . $where . ' GROUP BY ' . $cid . ', ' . $sid . ' HAVING MAX(' . $dt . ') < DATE_SUB(NOW(), INTERVAL ' . $idle . ' MINUTE)' . ') x';
        }
        return 'SELECT ' . $cid . ' AS customer_id, ' . $sid . ' AS session_id, '
            . 'COUNT(*) AS line_count, SUM(' . $qty . ') AS quantity, MAX(' . $dt . ') AS last_activity,'
            . " MAX(COALESCE(CONCAT(cu.firstname,' ',cu.lastname),'')) AS customer,"
            . ' MAX(COALESCE(' . $email . ",'')) AS email, MAX(COALESCE(" . $phone . ",'')) AS telephone,"
            . ' TIMESTAMPDIFF(MINUTE,MAX(' . $dt . '),NOW()) AS idle_minutes '
            . $from . $where . ' GROUP BY ' . $cid . ', ' . $sid . ' HAVING MAX(' . $dt . ') < DATE_SUB(NOW(), INTERVAL ' . $idle . ' MINUTE)';
    }

    public function getExternalCartProducts($customerId, $sessionId) {
        $source = $this->customSource();
        if (!$source || $sessionId === '') { return array(); }
        $f = $source['fields'];
        $sid = $this->sourceColumn($f,'session',"''");
        $cid = $this->sourceColumn($f,'customer','0');
        $pid = $this->sourceColumn($f,'product','0');
        $qty = $this->sourceColumn($f,'quantity','0');
        $sql = 'SELECT ' . $pid . ' AS product_id, ' . $qty . ' AS quantity, p.price, pd.name'
            . ' FROM ' . chr(96) . $source['table'] . chr(96) . ' c LEFT JOIN '
            . chr(96) . DB_PREFIX . 'product' . chr(96) . ' p ON p.product_id=' . $pid
            . ' LEFT JOIN ' . chr(96) . DB_PREFIX . 'product_description' . chr(96)
            . ' pd ON pd.product_id=' . $pid . " AND pd.language_id='" . (int)$this->config->get('config_language_id') . "'"
            . ' WHERE ' . $cid . "='" . (int)$customerId . "' AND " . $sid . "='" . $this->db->escape((string)$sessionId) . "' LIMIT 100";
        $rows = $this->db->query($sql)->rows;
        foreach ($rows as &$row) {
            $row['line_total'] = \CodeCart\Core\Money::decimal(
                \CodeCart\Core\Money::multiply((float)$row['price'], (int)$row['quantity']));
        }
        unset($row);
        return $rows;
    }

    private function sortSql($sort, $order) {
        $map = array(
            'customer' => 'customer',
            'email' => 'email',
            'telephone' => 'telephone',
            'quantity' => 'quantity',
            'last_activity' => 'last_activity',
            'inactive' => 'last_activity'
        );
        $column = isset($map[$sort]) ? $map[$sort] : 'last_activity';
        $direction = strtoupper((string)$order) === 'ASC' ? 'ASC' : 'DESC';
        if ($sort === 'inactive') {
            $direction = $direction === 'ASC' ? 'DESC' : 'ASC';
        }
        return $column . ' ' . $direction;
    }

    private function standardGroupedSql(array $filters = array()): string {
        $days = max(1, min(7, (int)$this->config->get('codecart_abandoned_cart_days') ?: 7));
        $idle = max(5, min(10080, (int)$this->config->get('codecart_abandoned_cart_idle_minutes') ?: 60));
        $guest = (bool)$this->config->get('codecart_abandoned_cart_guest_capture');
        $guestJoin = $guest ? " LEFT JOIN `" . DB_PREFIX . "codecart_cart_guest_contact` gc ON (gc.session_id=c.session_id AND c.customer_id=0 AND gc.store_id='" . (int)$this->config->get('config_store_id') . "' AND gc.consent=1 AND gc.date_modified>=DATE_SUB(NOW(), INTERVAL 7 DAY)) " : '';
        $name = "MAX(CASE WHEN cu.customer_id IS NULL THEN " . ($guest ? "COALESCE(gc.firstname,'')" : "''") . " ELSE CONCAT(cu.firstname,' ',cu.lastname) END)";
        $email = $guest ? "MAX(COALESCE(cu.email,gc.email,''))" : "MAX(COALESCE(cu.email,''))";
        $phone = $guest ? "MAX(COALESCE(cu.telephone,gc.telephone,''))" : "MAX(COALESCE(cu.telephone,''))";
        $optin = $guest ? "MAX(CASE WHEN c.customer_id=0 THEN COALESCE(gc.consent,0) ELSE COALESCE(cu.newsletter,0) END)" : "MAX(COALESCE(cu.newsletter,0))";
        $verified = $guest ? "MAX(CASE WHEN c.customer_id=0 AND gc.recovered_at IS NOT NULL THEN 1 ELSE 0 END)" : '0';
        $sql = "SELECT c.customer_id,c.session_id,COUNT(*) AS line_count,SUM(c.quantity) AS quantity,MAX(c.cart_id) AS max_cart_id,MAX(c.date_added) AS last_activity, " .
            $name . " AS customer," . $email . " AS email," . $phone . " AS telephone," . $optin . " AS newsletter," . $verified . " AS recovered, " .
            " TIMESTAMPDIFF(MINUTE, MAX(c.date_added), NOW()) AS idle_minutes " .
            " FROM `" . DB_PREFIX . "cart` c LEFT JOIN `" . DB_PREFIX . "customer` cu ON cu.customer_id=c.customer_id AND c.customer_id>0 " . $guestJoin .
            " WHERE c.api_id=0 AND c.date_added>=DATE_SUB(NOW(),INTERVAL " . $days . " DAY)";
        $sql .= " GROUP BY c.customer_id,c.session_id HAVING MAX(c.date_added)<DATE_SUB(NOW(),INTERVAL " . $idle . " MINUTE)";
        if (!empty($filters['search'])) {
            $search = $this->db->escape((string)$filters['search']);
            $sql .= " AND (MAX(cu.firstname) LIKE '%" . $search . "%' OR MAX(cu.lastname) LIKE '%" . $search . "%' OR MAX(cu.email) LIKE '%" . $search . "%' OR MAX(cu.telephone) LIKE '%" . $search . "%'" .
                ($guest ? " OR MAX(gc.email) LIKE '%" . $search . "%' OR MAX(gc.firstname) LIKE '%" . $search . "%' OR MAX(gc.telephone) LIKE '%" . $search . "%'" : '') .
                " OR EXISTS (SELECT 1 FROM `" . DB_PREFIX . "cart` search_cart JOIN `" . DB_PREFIX . "product_description` pd ON pd.product_id=search_cart.product_id AND pd.language_id='" . (int)$this->config->get('config_language_id') . "' WHERE search_cart.customer_id=c.customer_id AND search_cart.session_id=c.session_id AND search_cart.api_id=0 AND pd.name LIKE '%" . $search . "%'))";
        }
        if (($filters['contact'] ?? '') === 'yes') { $sql .= " AND " . $optin . "=1 AND " . $email . "<>''"; }
        if (($filters['contact'] ?? '') === 'no') { $sql .= " AND (" . $optin . "<>1 OR " . $email . "='')"; }
        if (($filters['contact'] ?? '') === 'guest') { $sql .= " AND c.customer_id=0"; }
        return $sql;
    }

    public function getAbandonedCarts($start = 0, $limit = 20, $sort = 'last_activity', $order = 'DESC', array $filters = array()) {
        $start = max(0, (int)$start);
        $limit = max(1, min(100, (int)$limit));
        if ((string)$this->config->get('codecart_abandoned_cart_source') === 'external') {
            $sql = $this->externalQuery();
            return $sql ? $this->db->query($sql . ' ORDER BY ' . $this->sortSql($sort, $order)
                . ' LIMIT ' . $start . ',' . $limit)->rows : array();
        }
        return $this->db->query($this->standardGroupedSql($filters) . ' ORDER BY ' . $this->sortSql((string)$sort, (string)$order) . ' LIMIT ' . $start . ',' . $limit)->rows;
    }

    public function getTotalAbandonedCarts(array $filters = array()) {
        if ((string)$this->config->get('codecart_abandoned_cart_source') === 'external') {
            $sql = $this->externalQuery(true);
            return $sql ? (int)$this->db->query($sql)->row['total'] : 0;
        }
        $q = $this->db->query('SELECT COUNT(*) AS total FROM (' . $this->standardGroupedSql($filters) . ') grouped');
        return (int)($q->row['total'] ?? 0);
    }

    public function getEstimatedCartValue(): float {
        // Current base product prices, without option, tax and historical discounts.
        // The seven-day limit and grouping prevent counting old lines of active carts.
        if ((string)$this->config->get('codecart_abandoned_cart_source') === 'external') { return 0.0; }
        $days = max(1, min(7, (int)$this->config->get('codecart_abandoned_cart_days') ?: 7));
        $idle = max(5, min(10080, (int)$this->config->get('codecart_abandoned_cart_idle_minutes') ?: 60));
        $sql = "SELECT SUM(c.quantity*COALESCE(p.price,0)) AS total FROM `" . DB_PREFIX . "cart` c INNER JOIN (SELECT customer_id,session_id FROM `" . DB_PREFIX . "cart` WHERE api_id=0 AND date_added>=DATE_SUB(NOW(),INTERVAL " . $days . " DAY) GROUP BY customer_id,session_id HAVING MAX(date_added)<DATE_SUB(NOW(),INTERVAL " . $idle . " MINUTE)) cart_scope ON cart_scope.customer_id=c.customer_id AND cart_scope.session_id=c.session_id LEFT JOIN `" . DB_PREFIX . "product` p ON p.product_id=c.product_id WHERE c.api_id=0 AND c.date_added>=DATE_SUB(NOW(),INTERVAL " . $days . " DAY)";
        return (float)($this->db->query($sql)->row['total'] ?? 0);
    }

    public function getCartContactSummary(): array {
        $result = $this->db->query("SELECT COUNT(*) AS total,SUM(CASE WHEN newsletter=1 AND email<>'' THEN 1 ELSE 0 END) AS reachable FROM (" . $this->standardGroupedSql() . ') all_carts');
        return array('total'=>(int)($result->row['total'] ?? 0), 'reachable'=>(int)($result->row['reachable'] ?? 0));
    }

    public function deleteAbandonedCart($customer_id, $session_id) {
        $customer_id = (int)$customer_id;
        $session_id = (string)$session_id;
        if ($session_id === '') { return; }
        $table = chr(96) . DB_PREFIX . 'cart' . chr(96);
        $where = "api_id='0' AND customer_id='" . $customer_id . "' AND session_id='" . $this->db->escape($session_id) . "'";
        $idle = max(5, min(10080, (int)$this->config->get('codecart_abandoned_cart_idle_minutes') ?: 60));
        $days = max(1, min(7, (int)$this->config->get('codecart_abandoned_cart_days') ?: 7));
        $eligible = $this->db->query('SELECT MAX(date_added) AS last_activity FROM ' . $table . ' WHERE ' . $where .
            ' HAVING MAX(date_added) < DATE_SUB(NOW(),INTERVAL ' . $idle . ' MINUTE) AND MAX(date_added)>=DATE_SUB(NOW(),INTERVAL ' . $days . ' DAY)');
        if (!$eligible->num_rows) { return; }
        // Recheck within the DELETE query to avoid removing a cart that became active meanwhile.
        $this->db->query('DELETE FROM ' . $table . ' WHERE ' . $where .
            ' AND (SELECT 1 FROM (SELECT MAX(date_added) AS updated FROM ' . $table . ' WHERE ' . $where .
            ') state WHERE state.updated < DATE_SUB(NOW(),INTERVAL ' . $idle . ' MINUTE) AND state.updated>=DATE_SUB(NOW(),INTERVAL ' . $days . ' DAY))=1');
    }

    public function clearAbandonedCarts() {
        $table = chr(96) . DB_PREFIX . 'cart' . chr(96);
        $idle = max(5, min(10080, (int)$this->config->get('codecart_abandoned_cart_idle_minutes') ?: 60));
        $days = max(1, min(7, (int)$this->config->get('codecart_abandoned_cart_days') ?: 7));
        // A nested derived table prevents a MySQL update-target table conflict.
        // Only whole abandoned carts in the selected history are removed.
        $sql = 'DELETE FROM ' . $table . " WHERE api_id='0' AND cart_id IN ("
            . 'SELECT cart_id FROM (SELECT c.cart_id FROM ' . $table . ' c JOIN ('
            . 'SELECT customer_id, session_id FROM ' . $table . " WHERE api_id='0'"
            . ' GROUP BY customer_id, session_id HAVING MAX(date_added) < DATE_SUB(NOW(), INTERVAL ' . $idle . ' MINUTE)'
            . ' AND MAX(date_added) >= DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)'
            . ') inactive ON inactive.customer_id=c.customer_id AND inactive.session_id=c.session_id'
            . " WHERE c.api_id='0') nested)";
        $this->db->query($sql);
    }

    public function getCartProducts($customer_id, $session_id) {
        $customer_id = (int)$customer_id;
        $session_id = (string)$session_id;

        $sql = "SELECT c.cart_id,c.product_id,c.quantity,c.option,p.price,p.tax_class_id,pd.name " .
               "FROM `" . DB_PREFIX . "cart` c " .
               "LEFT JOIN `" . DB_PREFIX . "product` p ON (p.product_id=c.product_id) " .
               "LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id=c.product_id AND pd.language_id='" . (int)$this->config->get('config_language_id') . "') " .
               "WHERE c.api_id='0' AND c.customer_id='" . $customer_id . "' AND c.session_id='" . $this->db->escape($session_id) . "' " .
               "ORDER BY c.date_added,c.cart_id";

        $rows = $this->db->query($sql)->rows;
        if (!$rows) { return array(); }

        $customer_group_id = $this->getCustomerGroupId($customer_id);
        $quantities = array();
        foreach ($rows as $row) {
            $product_id = (int)$row['product_id'];
            if (!isset($quantities[$product_id])) { $quantities[$product_id] = 0; }
            $quantities[$product_id] += (int)$row['quantity'];
        }

        foreach ($rows as &$row) {
            $product_id = (int)$row['product_id'];
            $quantity = max(0, (int)$row['quantity']);
            $price = (float)$row['price'];

            $discount = $this->db->query("SELECT price FROM `" . DB_PREFIX . "product_discount` WHERE product_id='" . $product_id . "' AND customer_group_id='" . $customer_group_id . "' AND quantity <= '" . (int)$quantities[$product_id] . "' AND ((date_start < NOW()) AND (date_end < '1000-01-01' OR date_end > NOW())) ORDER BY quantity DESC, priority ASC, price ASC LIMIT 1");
            if ($discount->num_rows) { $price = (float)$discount->row['price']; }

            $special = $this->db->query("SELECT price FROM `" . DB_PREFIX . "product_special` WHERE product_id='" . $product_id . "' AND customer_group_id='" . $customer_group_id . "' AND ((date_start < NOW()) AND (date_end < '1000-01-01' OR date_end > NOW())) ORDER BY priority ASC, price ASC LIMIT 1");
            if ($special->num_rows) { $price = (float)$special->row['price']; }

            $option_price = 0.0;
            $option_price_equal = null;
            $options = json_decode((string)$row['option'], true);
            if (!is_array($options)) { $options = array(); }

            foreach ($options as $product_option_id => $value) {
                $product_option_id = (int)$product_option_id;
                $option_query = $this->db->query("SELECT o.type FROM `" . DB_PREFIX . "product_option` po LEFT JOIN `" . DB_PREFIX . "option` o ON (o.option_id=po.option_id) WHERE po.product_option_id='" . $product_option_id . "' AND po.product_id='" . $product_id . "' LIMIT 1");
                if (!$option_query->num_rows) { continue; }

                $type = (string)$option_query->row['type'];
                if (($type === 'select' || $type === 'radio') && is_scalar($value)) {
                    $this->applyOptionPrice($product_option_id, (int)$value, $option_price, $option_price_equal, true);
                } elseif ($type === 'checkbox' && is_array($value)) {
                    foreach ($value as $product_option_value_id) {
                        $this->applyOptionPrice($product_option_id, (int)$product_option_value_id, $option_price, $option_price_equal, false);
                    }
                }
            }

            $unit_price = $option_price_equal !== null ? (float)$option_price_equal : ($price + $option_price);
            $row['unit_price'] = \CodeCart\Core\Money::decimal($unit_price);
            $row['line_total'] = \CodeCart\Core\Money::decimal(\CodeCart\Core\Money::multiply($row['unit_price'], $quantity));
        }
        unset($row);
        return $rows;
    }

    private function getCustomerGroupId($customer_id) {
        $customer_id = (int)$customer_id;
        if ($customer_id > 0) {
            $query = $this->db->query("SELECT customer_group_id FROM `" . DB_PREFIX . "customer` WHERE customer_id='" . $customer_id . "' LIMIT 1");
            if ($query->num_rows) { return (int)$query->row['customer_group_id']; }
        }
        return (int)$this->config->get('config_customer_group_id');
    }

    private function applyOptionPrice($product_option_id, $product_option_value_id, &$option_price, &$option_price_equal, $allow_equal) {
        $query = $this->db->query("SELECT price,price_prefix FROM `" . DB_PREFIX . "product_option_value` WHERE product_option_id='" . (int)$product_option_id . "' AND product_option_value_id='" . (int)$product_option_value_id . "' LIMIT 1");
        if (!$query->num_rows) { return; }
        $price = (float)$query->row['price'];
        $prefix = (string)$query->row['price_prefix'];
        if ($prefix === '+') { $option_price += $price; }
        elseif ($prefix === '-') { $option_price -= $price; }
        elseif ($allow_equal && $prefix === '=') { $option_price_equal = $price; }
    }
}
