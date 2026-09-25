<?php
class ModelSaleAbandonedCart extends Model {
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

    public function getAbandonedCarts($start = 0, $limit = 20, $sort = 'last_activity', $order = 'DESC') {
        $start = max(0, (int)$start);
        $limit = max(1, min(100, (int)$limit));

        $sql = "SELECT c.customer_id, c.session_id, COUNT(*) AS line_count, SUM(c.quantity) AS quantity, MAX(c.date_added) AS last_activity, " .
               "MAX(CASE WHEN cu.customer_id IS NULL THEN '' ELSE CONCAT(cu.firstname, ' ', cu.lastname) END) AS customer, " .
               "MAX(COALESCE(cu.email,'')) AS email, MAX(COALESCE(cu.telephone,'')) AS telephone, TIMESTAMPDIFF(MINUTE, MAX(c.date_added), NOW()) AS idle_minutes " .
               "FROM `" . DB_PREFIX . "cart` c LEFT JOIN `" . DB_PREFIX . "customer` cu ON (cu.customer_id=c.customer_id AND c.customer_id>0) " .
               "WHERE c.api_id = '0' AND c.date_added < DATE_SUB(NOW(), INTERVAL 1 HOUR) AND c.date_added >= DATE_SUB(NOW(), INTERVAL 7 DAY) " .
               "GROUP BY c.customer_id, c.session_id ORDER BY " . $this->sortSql((string)$sort, (string)$order) . " LIMIT " . $start . "," . $limit;

        return $this->db->query($sql)->rows;
    }

    public function getTotalAbandonedCarts() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM (SELECT c.customer_id, c.session_id FROM `" . DB_PREFIX . "cart` c WHERE c.api_id = '0' AND c.date_added < DATE_SUB(NOW(), INTERVAL 1 HOUR) AND c.date_added >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY c.customer_id, c.session_id) x");
        return (int)($query->row['total'] ?? 0);
    }

    public function deleteAbandonedCart($customer_id, $session_id) {
        $customer_id = (int)$customer_id;
        $session_id = (string)$session_id;
        if ($session_id === '') { return; }
        $this->db->query("DELETE FROM `" . DB_PREFIX . "cart` WHERE api_id='0' AND customer_id='" . $customer_id . "' AND session_id='" . $this->db->escape($session_id) . "' AND date_added < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    }

    public function clearAbandonedCarts() {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "cart` WHERE api_id='0' AND date_added < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
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
