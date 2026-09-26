<?php
class ControllerCommonMenu extends Controller {
    public function index() {
        $this->load->language('common/menu');
        $data['text_category'] = $this->language->get('text_category');
        $data['text_all'] = $this->language->get('text_all');
        $data['text_catalog'] = $this->language->get('text_catalog');
        $data['text_pages'] = $this->language->get('text_pages');
        $data['text_products'] = $this->language->get('text_products');
        $data['text_close'] = $this->language->get('text_close');

        $this->load->model('catalog/category');
        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $menuMode = (string)$this->config->get('theme_default_header_menu_mode');
        $data['menu_mode'] = in_array($menuMode, array('horizontal','vertical'), true) ? $menuMode : 'horizontal';

        // Build the complete category tree in one category query. Depth is capped only as a safety guard.
        $rows = $this->model_catalog_category->getAllCategories();
        $byParent = array();
        $allIds = array();
        foreach ($rows as $row) {
            $pid = (int)$row['parent_id'];
            if (!isset($byParent[$pid])) { $byParent[$pid] = array(); }
            $byParent[$pid][] = $row;
            $allIds[] = (int)$row['category_id'];
        }
        // Header navigation never shows product counters. Avoid an unnecessary aggregate count query here;
        // category/list modules calculate counts independently when their own setting enables them.
        $counts = array();
        $self = $this;
        $build = function($parentId, $path, $depth, $visited) use (&$build, &$byParent, &$counts, $self) {
            if ($depth > 10 || empty($byParent[$parentId])) { return array(); }
            $items = array();
            foreach ($byParent[$parentId] as $row) {
                $id = (int)$row['category_id'];
                if (isset($visited[$id])) { continue; }
                $nextVisited = $visited; $nextVisited[$id] = true;
                $nextPath = $path === '' ? (string)$id : $path . '_' . $id;
                $count = isset($counts[$id]) ? (int)$counts[$id] : 0;
                // Header navigation stays compact: product counters belong to category walls/lists, not menu labels.
                $name = (string)$row['name'];
                $thumb = '';
                if ($depth <= 2 && !empty($row['image'])) {
                    $thumb = $self->model_tool_image->resize((string)$row['image'], $depth === 1 ? 38 : 46, $depth === 1 ? 38 : 46);
                }
                $items[] = array(
                    'category_id' => $id,
                    'name' => $name,
                    'count' => $count,
                    'thumb' => $thumb,
                    'image' => isset($row['image']) ? (string)$row['image'] : '',
                    'top' => !empty($row['top']) ? 1 : 0,
                    'sort_order' => isset($row['sort_order']) ? (int)$row['sort_order'] : 0,
                    'children' => $build($id, $nextPath, $depth + 1, $nextVisited),
                    'column' => max(1, min(6, !empty($row['column']) ? (int)$row['column'] : 1)),
                    'href' => $self->url->link('product/category', 'path=' . $nextPath)
                );
            }
            return $items;
        };
        $rootTree = $build(0, '', 1, array());
        $data['categories'] = array();
        foreach ($rootTree as $item) {
            // OpenCart top flag only applies to root categories shown in the main navigation.
            foreach ($rows as $row) {
                if ((int)$row['category_id'] === (int)$item['category_id'] && !empty($row['top'])) { $data['categories'][] = $item; break; }
            }
        }

        $data['extra_links'] = array();
        $data['product_links'] = array();

        $informationIds = $this->config->get('theme_default_header_information_ids');
        if (is_array($informationIds) && $informationIds) {
            $this->load->model('catalog/information');
            $byId = array();
            foreach ($this->model_catalog_information->getInformations() as $information) {
                $byId[(int)$information['information_id']] = $information;
            }
            foreach (array_values(array_unique(array_filter(array_map('intval', $informationIds)))) as $id) {
                if (isset($byId[$id])) {
                    $data['extra_links'][] = array('name' => $byId[$id]['title'], 'href' => $this->url->link('information/information', 'information_id=' . $id), 'kind' => 'information');
                }
            }
        }

        $productIds = $this->config->get('theme_default_header_product_ids');
        if (is_array($productIds)) {
            foreach (array_values(array_unique(array_filter(array_map('intval', $productIds)))) as $productId) {
                $product = $this->model_catalog_product->getProduct($productId);
                if ($product) {
                    $data['product_links'][] = array('name' => $product['name'], 'href' => $this->url->link('product/product', 'product_id=' . $productId), 'kind' => 'product');
                }
            }
        }

        $languageId = (int)$this->config->get('config_language_id');
        $customLinks = $this->config->get('theme_default_header_custom_links');
        if (is_array($customLinks)) {
            usort($customLinks, function($a,$b){ return (int)($a['sort_order'] ?? 0) <=> (int)($b['sort_order'] ?? 0); });
            foreach ($customLinks as $link) {
                $href = trim((string)($link['href'] ?? ''));
                $titles = isset($link['title']) && is_array($link['title']) ? $link['title'] : array();
                $title = trim((string)($titles[$languageId] ?? ''));
                if ($title === '' || $href === '' || preg_match('/^(?:javascript|data|vbscript):/i', $href)) { continue; }
                $data['extra_links'][] = array('name' => $title, 'href' => $href, 'kind' => 'custom');
            }
        }

        // In vertical mode secondary destinations are independent navigation buttons,
        // not category-list rows. This keeps the Catalog tree focused on categories only.
        $data['quick_links'] = array_merge($data['extra_links'], $data['product_links']);

        $compatibility = $this->registry->get('codecart_compatibility_framework');
        if ($compatibility) {
            $data = $compatibility->apply('catalog.menu.data', $data);
        }

        return $this->load->view('common/menu', $data);
    }
}
