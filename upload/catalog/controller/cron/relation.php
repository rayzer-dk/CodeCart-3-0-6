<?php
class ControllerCronRelation extends Controller {
    public function batch($args = array()) {
        if (!(bool)$this->config->get('codecart_relation_status')) {
            return true;
        }
        if (!is_array($args)) { $args = array(); }
        $layer = new \CodeCart\Core\RelationLayer($this->registry);

        if (!empty($args['ids']) && is_array($args['ids'])) {
            $ids = array_values(array_unique(array_filter(array_map('intval', $args['ids']))));
            foreach (array_slice($ids, 0, 100) as $productId) {
                $layer->generateProduct((int)$productId);
            }
            return true;
        }

        $afterId = isset($args['after_id']) ? max(0, (int)$args['after_id']) : 0;
        $limit = isset($args['limit']) ? max(10, min(100, (int)$args['limit'])) : 50;
        $query = $this->db->query("SELECT product_id FROM `" . DB_PREFIX . "product` WHERE product_id > '" . $afterId . "' ORDER BY product_id ASC LIMIT " . $limit);
        if (!$query->num_rows) {
            return true;
        }

        $lastId = $afterId;
        foreach ($query->rows as $row) {
            $productId = (int)$row['product_id'];
            $layer->generateProduct($productId);
            $lastId = $productId;
        }

        if ($query->num_rows >= $limit) {
            (new \CodeCart\Core\Queue($this->registry))->enqueue('relation.generate.all', 'cron/relation/batch', array('after_id' => $lastId, 'limit' => $limit), 95, null, 3);
        }
        return true;
    }
}
