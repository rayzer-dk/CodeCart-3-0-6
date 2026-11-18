<?php
namespace CodeCart\Core;

/**
 * Safe, bounded admin-only category hierarchy editor for catalog and blog.
 *
 * Moving a branch changes parent_id and its closure paths only. It never
 * rewrites descriptions, SEO aliases, products, article relations or media.
 * Requires InnoDB, and must only be called behind an authenticated POST route.
 */
final class AdminCategoryTreeEditor {
    private const MAX_BRANCH = 5000;

    private static function names(string $type): array {
        if ($type === 'catalog') {
            return array('category', 'category_path', 'category_id');
        }
        if ($type === 'blog') {
            return array('blog_category', 'blog_category_path', 'blog_category_id');
        }
        if ($type === 'article') {
            // Sorting articles is supported; hierarchy moves are not.
            return array('article', null, 'article_id');
        }
        throw new \InvalidArgumentException('Unsupported category tree.');
    }

    public static function quickSort($registry, string $type, int $id, int $sort, int $expected): int {
        [$entity, , $key] = self::names($type);
        if ($id <= 0 || $sort < -999999 || $sort > 999999) {
            throw new \InvalidArgumentException('Invalid category sort order.');
        }
        $db = $registry->get('db');
        $table = '`' . DB_PREFIX . $entity . '`';
        $db->query("UPDATE " . $table . " SET sort_order = '" . $sort . "', date_modified = NOW() WHERE `" . $key . "` = '" . $id . "' AND sort_order = '" . $expected . "'");
        $result = $db->query("SELECT sort_order FROM " . $table . " WHERE `" . $key . "` = '" . $id . "' LIMIT 1");
        if (!$result->num_rows) {
            throw new \RuntimeException('Category does not exist.');
        }
        if ((int)$result->row['sort_order'] !== $sort) {
            throw new \RuntimeException('Category was changed in another session. Refresh the page.');
        }
        self::clearCaches($registry, $type);
        return $sort;
    }

    public static function move($registry, string $type, int $id, int $newParent): bool {
        [$entity, $closure, $key] = self::names($type);
        if ($closure === null) {
            throw new \InvalidArgumentException('Articles do not have a category hierarchy.');
        }
        if ($id < 1 || $newParent < 0 || $id === $newParent) {
            throw new \InvalidArgumentException('Cannot place a category inside itself.');
        }

        $db = $registry->get('db');
        $entityTable = '`' . DB_PREFIX . $entity . '`';
        $pathTable = '`' . DB_PREFIX . $closure . '`';
        $lockName = 'cc_tree_' . substr(hash('sha256', DB_DATABASE . '|' . DB_PREFIX . '|' . $entity), 0, 32);
        $lockResult = $db->query("SELECT GET_LOCK('" . $db->escape($lockName) . "', 5) AS acquired");
        if (empty($lockResult->row['acquired'])) {
            throw new \RuntimeException('Category tree is busy. Try again.');
        }

        try {
            $changed = Transaction::run($db, function () use ($db, $entityTable, $pathTable, $key, $id, $newParent) {
                $item = $db->query("SELECT " . $key . ", parent_id FROM " . $entityTable . " WHERE `" . $key . "` = '" . $id . "' FOR UPDATE");
                if (!$item->num_rows) {
                    throw new \RuntimeException('Category was not found.');
                }
                if ($newParent === (int)$item->row['parent_id']) {
                    return false;
                }
                $ancestors = array();
                if ($newParent) {
                    $target = $db->query("SELECT " . $key . " FROM " . $entityTable . " WHERE `" . $key . "` = '" . $newParent . "' FOR UPDATE");
                    if (!$target->num_rows) {
                        throw new \InvalidArgumentException('Destination category does not exist.');
                    }
                    $chain = $db->query("SELECT path_id FROM " . $pathTable . " WHERE `" . $key . "` = '" . $newParent . "' ORDER BY level");
                    foreach ($chain->rows as $row) {
                        $ancestor = (int)$row['path_id'];
                        if ($ancestor === $id) {
                            throw new \InvalidArgumentException('Cannot move a category into its descendant.');
                        }
                        $ancestors[] = $ancestor;
                    }
                    if (!$ancestors || end($ancestors) !== $newParent) {
                        throw new \RuntimeException('Destination tree needs repair.');
                    }
                }

                $descendants = $db->query("SELECT DISTINCT `" . $key . "` AS id FROM " . $pathTable . " WHERE path_id = '" . $id . "' ORDER BY `" . $key . "`")->rows;
                if (!$descendants || count($descendants) > self::MAX_BRANCH) {
                    throw new \RuntimeException('Category branch is empty or exceeds the safe move limit.');
                }
                $ids = array_map('intval', array_column($descendants, 'id'));
                if (!in_array($id, $ids, true)) {
                    throw new \RuntimeException('Category closure is incomplete. Run Repair first.');
                }
                $idList = implode(',', $ids);

                // Lock every affected node before rebuilding the closure table.
                $db->query("SELECT `" . $key . "` FROM " . $entityTable . " WHERE `" . $key . "` IN (" . $idList . ") ORDER BY `" . $key . "` FOR UPDATE");
                $pathRows = $db->query("SELECT `" . $key . "` AS id, path_id FROM " . $pathTable . " WHERE `" . $key . "` IN (" . $idList . ") ORDER BY `" . $key . "`, level")->rows;

                $oldPaths = array();
                foreach ($pathRows as $row) {
                    $oldPaths[(int)$row['id']][] = (int)$row['path_id'];
                }
                $tuples = array();
                foreach ($ids as $nodeId) {
                    $old = $oldPaths[$nodeId] ?? array();
                    $pivot = array_search($id, $old, true);
                    if ($pivot === false || !$old || end($old) !== $nodeId) {
                        throw new \RuntimeException('Invalid closure path. Repair categories before moving.');
                    }
                    $newPath = array_merge($ancestors, array_slice($old, (int)$pivot));
                    if (count($newPath) > 50) {
                        throw new \RuntimeException('Category nesting limit exceeded.');
                    }
                    foreach ($newPath as $level => $pathId) {
                        $tuples[] = "(" . $nodeId . "," . $pathId . "," . (int)$level . ")";
                    }
                }
                // No changes occur before every path and every input is validated.
                $db->query("DELETE FROM " . $pathTable . " WHERE `" . $key . "` IN (" . $idList . ")");
                foreach (array_chunk($tuples, 300) as $chunk) {
                    $db->query("INSERT INTO " . $pathTable . " (`" . $key . "`, path_id, level) VALUES " . implode(',', $chunk));
                }
                $db->query("UPDATE " . $entityTable . " SET parent_id = '" . $newParent . "', date_modified = NOW() WHERE `" . $key . "` = '" . $id . "'");
                return true;
            });
        } finally {
            $db->query("SELECT RELEASE_LOCK('" . $db->escape($lockName) . "')");
        }
        if ($changed) {
            self::clearCaches($registry, $type);
        }
        return $changed;
    }

    private static function clearCaches($registry, string $type): void {
        $cache = $registry->get('cache');
        if (!$cache) { return; }
        $cache->delete('category');
        $cache->delete('blog_category');
        if ($type === 'article') { $cache->delete('article'); }
        $cache->delete('seopro');
        if ($type === 'catalog' && method_exists($cache, 'deleteTag')) {
            $cache->deleteTag('catalog.category_counts', 'category_product_counts');
        }
    }
}
