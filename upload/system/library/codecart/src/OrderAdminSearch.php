<?php
namespace CodeCart\Core;
final class OrderAdminSearch {
    public static function digits($value): string { return preg_replace('/[^0-9]/', '', (string)$value); }
    public static function amount($value): string {
        $value = trim(str_replace(',', '.', (string)$value));
        return preg_match('/^\d{1,12}(?:\.\d{1,4})?$/D', $value) ? $value : '';
    }
    private static function phoneSql(): string {
        $expr = "COALESCE(o.telephone, '')";
        foreach (array(' ', '-', '(', ')', '+', '.', '/') as $ch) {
            $expr = "REPLACE(" . $expr . ", '" . $ch . "', '')";
        }
        return "REPLACE(REPLACE(" . $expr . ", CHAR(9), ''), CHAR(13), '')";
    }
    public static function where($db, array $data): string {
        $sql = '';
        if (!empty($data['filter_order_id'])) { $sql .= " AND o.order_id = '" . (int)$data['filter_order_id'] . "'"; }
        if (!empty($data['filter_customer'])) {
            $sql .= " AND CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $db->escape($data['filter_customer']) . "%'";
        }
        if (!empty($data['filter_date_added'])) {
            $date = $db->escape($data['filter_date_added'] . ' 00:00:00');
            $sql .= " AND o.date_added >= '" . $date . "' AND o.date_added < DATE_ADD('" . $date . "', INTERVAL 1 DAY)";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND o.date_added >= '" . $db->escape($data['filter_date_start'] . ' 00:00:00') . "'";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND o.date_added < DATE_ADD('" . $db->escape($data['filter_date_end'] . ' 00:00:00') . "', INTERVAL 1 DAY)";
        }
        if (!empty($data['filter_date_modified'])) {
            $sql .= " AND DATE(o.date_modified) = DATE('" . $db->escape($data['filter_date_modified']) . "')";
        }
        $min = self::amount(isset($data['filter_total_min']) ? $data['filter_total_min'] : '');
        $max = self::amount(isset($data['filter_total_max']) ? $data['filter_total_max'] : '');
        $legacy = self::amount(isset($data['filter_total']) ? $data['filter_total'] : '');
        if ($min === '' && $max === '' && $legacy !== '') { $min = $legacy; $max = $legacy; }
        if ($min !== '') { $sql .= " AND o.total >= '" . $min . "'"; }
        if ($max !== '') { $sql .= " AND o.total <= '" . $max . "'"; }
        if (!empty($data['filter_email'])) {
            $email = mb_strtolower(trim((string)$data['filter_email']), 'UTF-8');
            $sql .= " AND LOCATE('" . $db->escape($email) . "', LOWER(o.email)) > 0";
        }
        $phone = self::digits(isset($data['filter_phone']) ? $data['filter_phone'] : '');
        if (strlen($phone) >= 4) {
            $expr = self::phoneSql();
            $conditions = array("LOCATE('" . $phone . "', " . $expr . ") > 0");
            if (strlen($phone) >= 9) { $conditions[] = "RIGHT(" . $expr . ", 9) = '" . substr($phone, -9) . "'"; }
            if (strlen($phone) >= 7) { $conditions[] = "RIGHT(" . $expr . ", 7) = '" . substr($phone, -7) . "'"; }
            $sql .= " AND (" . implode(' OR ', $conditions) . ")";
        }
        if (!empty($data['filter_product'])) {
            $term = $db->escape(mb_strtolower(trim((string)$data['filter_product']), 'UTF-8'));
            $sql .= " AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "order_product` op "
                . "LEFT JOIN `" . DB_PREFIX . "product` p ON p.product_id = op.product_id "
                . "WHERE op.order_id = o.order_id AND (LOCATE('" . $term . "', LOWER(op.name)) > 0 "
                . "OR LOCATE('" . $term . "', LOWER(op.model)) > 0 OR LOCATE('" . $term . "', LOWER(p.sku)) > 0))";
        }
        foreach (array('filter_shipping_method' => 'shipping_method', 'filter_payment_method' => 'payment_method') as $filter => $col) {
            if (isset($data[$filter]) && is_string($data[$filter]) && $data[$filter] !== '') {
                $sql .= " AND o." . $col . " = '" . $db->escape($data[$filter]) . "'";
            }
        }
        return $sql;
    }
    public static function ranking($db, array $data): string {
        $keys = array();
        $phone = self::digits(isset($data['filter_phone']) ? $data['filter_phone'] : '');
        if (strlen($phone) >= 4) {
            $expr = self::phoneSql();
            $rank = "CASE WHEN " . $expr . " = '" . $phone . "' THEN 0";
            if (strlen($phone) >= 9) { $rank .= " WHEN RIGHT(" . $expr . ", 9) = '" . substr($phone, -9) . "' THEN 1"; }
            if (strlen($phone) >= 7) { $rank .= " WHEN RIGHT(" . $expr . ", 7) = '" . substr($phone, -7) . "' THEN 2"; }
            $keys[] = $rank . " ELSE 3 END ASC";
        }
        if (!empty($data['filter_email'])) {
            $email = $db->escape(mb_strtolower(trim((string)$data['filter_email']), 'UTF-8'));
            $keys[] = "CASE WHEN LOWER(o.email) = '" . $email . "' THEN 0 "
                . "WHEN LEFT(LOWER(o.email), CHAR_LENGTH('" . $email . "')) = '" . $email . "' THEN 1 ELSE 2 END ASC";
        }
        return $keys ? implode(', ', $keys) . ', ' : '';
    }
}
