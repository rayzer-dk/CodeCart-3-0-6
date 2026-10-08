<?php
class ModelReportLostUrl extends Model {
    private function table(): string { return '`' . DB_PREFIX . 'codecart_lost_url`'; }

    public function getEntry(int $id): array {
        return $this->db->query('SELECT * FROM ' . $this->table() . ' WHERE lost_url_id=' . $id)->row;
    }

    private function where(array $filter): string {
        $where = ' WHERE store_id=' . (int)$filter['store_id'];
        $view = $filter['view'] ?? 'attention';
        if ($view === 'attention') { $where .= " AND status='new' AND (browser_hits>=2 OR referred_hits>0)"; }
        elseif ($view === 'doubtful') { $where .= " AND status='new' AND browser_hits<2 AND referred_hits=0"; }
        elseif ($view === 'ignored' || $view === 'fixed') { $where .= " AND status='" . $view . "'"; }
        if (!empty($filter['search'])) {
            $search = str_replace(array('!', '%', '_'), array('!!', '!%', '!_'), $filter['search']);
            $where .= " AND url LIKE '%" . $this->db->escape($search) . "%' ESCAPE '!'";
        }
        return $where;
    }

    public function getEntries(array $filter): array {
        $sort = in_array($filter['sort'], array('browser_hits', 'last_seen', 'url'), true) ? $filter['sort'] : 'browser_hits';
        $order = ($filter['order'] ?? '') === 'ASC' ? 'ASC' : 'DESC';
        return $this->db->query('SELECT * FROM ' . $this->table() . $this->where($filter) . ' ORDER BY `' . $sort . '` ' . $order . ', lost_url_id DESC LIMIT ' . max(0, (int)$filter['start']) . ',25')->rows;
    }

    public function getTotal(array $filter): int {
        return (int)$this->db->query('SELECT COUNT(*) AS total FROM ' . $this->table() . $this->where($filter))->row['total'];
    }

    public function setStatus(int $id, string $status): void {
        if (!in_array($status, array('new', 'ignored'), true)) { return; }
        $this->db->query('UPDATE ' . $this->table() . " SET status='" . $status . "' WHERE lost_url_id=" . $id);
    }

    public function saveRedirect(int $id, string $target): string {
        $row = $this->getEntry($id);
        if (!$row || !\CodeCart\Core\LostUrlMonitor::validSlug($row['slug']) || !\CodeCart\Core\LostUrlMonitor::validSlug($target) || $row['slug'] === $target) { return 'error_slug'; }
        $scope = 'store_id=' . (int)$row['store_id'] . ' AND language_id=' . (int)$row['language_id'];
        $old = $this->db->escape($row['slug']); $new = $this->db->escape($target);
        // Existing SEO URLs always win. Never replace a live source or build redirect chains.
        if ($this->db->query("SELECT seo_url_id FROM `" . DB_PREFIX . "seo_url` WHERE " . $scope . " AND keyword='" . $old . "' LIMIT 1")->num_rows) { return 'error_source'; }
        $destination = $this->db->query("SELECT query FROM `" . DB_PREFIX . "seo_url` WHERE " . $scope . " AND keyword='" . $new . "' LIMIT 2");
        if ($destination->num_rows !== 1) { return 'error_target'; }
        if (!preg_match('/^(?:product_id|category_id|manufacturer_id|information_id|article_id|news_id|uni_news_id)=\d+$/D', $destination->row['query'])) { return 'error_target'; }
        if ($this->db->query("SELECT redirect_id FROM `" . DB_PREFIX . "codecart_lost_url_redirect` WHERE " . $scope . " AND (old_slug='" . $new . "' OR new_slug='" . $old . "') LIMIT 1")->num_rows) { return 'error_chain'; }
        $this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_lost_url_redirect` SET " . str_replace(' AND ', ', ', $scope) . ", old_slug='" . $old . "', new_slug='" . $new . "', date_modified=NOW() ON DUPLICATE KEY UPDATE new_slug=VALUES(new_slug), date_modified=NOW()");
        $this->db->query('UPDATE ' . $this->table() . " SET status='fixed' WHERE store_id=" . (int)$row['store_id'] . ' AND language_id=' . (int)$row['language_id'] . " AND slug='" . $old . "'");
        return '';
    }

    public function getRedirects(int $store, int $start = 0): array {
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "codecart_lost_url_redirect` WHERE store_id=" . $store . ' ORDER BY date_modified DESC, redirect_id DESC LIMIT ' . max(0, $start) . ',25')->rows;
    }

    public function getRedirectTotal(int $store): int {
        return (int)$this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "codecart_lost_url_redirect` WHERE store_id=" . $store)->row['total'];
    }

    public function deleteRedirect(int $id): void {
        $table = '`' . DB_PREFIX . 'codecart_lost_url_redirect`';
        $row = $this->db->query('SELECT * FROM ' . $table . ' WHERE redirect_id=' . $id)->row;
        if (!$row) { return; }
        $this->db->query('DELETE FROM ' . $table . ' WHERE redirect_id=' . $id);
        $this->db->query('UPDATE ' . $this->table() . " SET status='new' WHERE status='fixed' AND store_id=" . (int)$row['store_id'] . ' AND language_id=' . (int)$row['language_id'] . " AND slug='" . $this->db->escape($row['old_slug']) . "'");
    }
}
