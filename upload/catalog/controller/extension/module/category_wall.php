<?php
class ControllerExtensionModuleCategoryWall extends Controller {
    public function index($setting) {
        if (empty($setting['status'])) {
            return;
        }

        $this->load->language('extension/module/category_wall');
        $this->load->language('extension/module/codecart_slider');
        $this->load->model('catalog/category');
        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $data['text_pause_autoplay'] = $this->language->get('text_pause_autoplay');
        $data['text_play_autoplay'] = $this->language->get('text_play_autoplay');
        $data['text_previous_items'] = $this->language->get('text_previous');
        $data['text_next_items'] = $this->language->get('text_next');
        $data['text_all_categories'] = $this->language->get('text_all_categories');

        $language_id = (int)$this->config->get('config_language_id');
        $source = isset($setting['source']) ? (string)$setting['source'] : 'selected';
        if (!in_array($source, array('selected', 'top', 'children', 'current'), true)) {
            $source = 'selected';
        }

        $rows = array();
        if ($source === 'selected') {
            $ids = array_values(array_unique(array_filter(array_map('intval', isset($setting['category']) ? (array)$setting['category'] : array()))));
            $rows = $this->model_catalog_category->getCategoriesByIds($ids);
        } else {
            $parent_id = 0;
            if ($source === 'children') {
                $parent_id = isset($setting['parent_id']) ? (int)$setting['parent_id'] : 0;
            } elseif ($source === 'current') {
                $path = isset($this->request->get['path']) ? (string)$this->request->get['path'] : '';
                $parts = array_filter(array_map('intval', explode('_', $path)));
                $parent_id = $parts ? (int)end($parts) : 0;
            }
            $rows = $this->model_catalog_category->getCategories($parent_id);
        }

        if (!$rows) {
            return;
        }

        $sort = isset($setting['sort']) && $setting['sort'] === 'name' ? 'name' : 'sort_order';
        usort($rows, static function($a, $b) use ($sort) {
            if ($sort === 'name') {
                return strcasecmp((string)$a['name'], (string)$b['name']);
            }
            $left = (int)$a['sort_order'];
            $right = (int)$b['sort_order'];
            return $left === $right ? strcasecmp((string)$a['name'], (string)$b['name']) : ($left < $right ? -1 : 1);
        });

        $limit = max(1, min(100, isset($setting['limit']) ? (int)$setting['limit'] : 12));
        $rows = array_slice($rows, 0, $limit);

        $width = max(40, min(1600, isset($setting['width']) ? (int)$setting['width'] : 320));
        $height = max(40, min(1600, isset($setting['height']) ? (int)$setting['height'] : 220));
        $width_mobile = max(40, min(1200, isset($setting['width_mobile']) ? (int)$setting['width_mobile'] : min($width, 480)));
        $height_mobile = max(40, min(1200, isset($setting['height_mobile']) ? (int)$setting['height_mobile'] : min($height, 360)));

        // Backward compatibility: old show_image=0 maps to the new text-only mode.
        if (isset($setting['image_mode'])) {
            $image_mode = $setting['image_mode'] === 'text_only' ? 'text_only' : 'image_text';
        } else {
            $image_mode = (isset($setting['show_image']) && !$setting['show_image']) ? 'text_only' : 'image_text';
        }
        $show_image = $image_mode === 'image_text';
        $show_count = !empty($setting['show_count']);
        $hide_empty = !empty($setting['hide_empty']);

        $category_counts = array();
        if ($show_count || $hide_empty) {
            $count_ids = array();
            foreach ($rows as $row) {
                $count_ids[] = (int)$row['category_id'];
            }
            $category_counts = $this->model_catalog_product->getCategoryProductCounts($count_ids, true);
        }

        $parent_ids = array();
        foreach ($rows as $row) { $parent_ids[] = (int)$row['category_id']; }
        $children_by_parent = $this->model_catalog_category->getCategoriesByParentIds($parent_ids);

        $data['categories'] = array();
        foreach ($rows as $row) {
            $category_id = (int)$row['category_id'];
            $count = isset($category_counts[$category_id]) ? (int)$category_counts[$category_id] : false;
            if ($hide_empty && $count !== false && $count < 1) {
                continue;
            }

            $item = array(
                'category_id' => $category_id,
                'name' => $row['name'],
                'count' => $count,
                'href' => $this->url->link('product/category', 'path=' . $category_id),
                'thumb' => '',
                'thumb_mobile' => '',
                'subcategories' => array()
            );

            if ($show_image) {
                $image = !empty($row['image']) ? (string)$row['image'] : 'no_image.webp';
                $item['thumb'] = $this->model_tool_image->resize($image, $width, $height);
                $item['thumb_mobile'] = $this->model_tool_image->resize($image, $width_mobile, $height_mobile);
            }

            $selected_children = isset($setting['subcategory'][$category_id]) ? array_values(array_unique(array_filter(array_map('intval', (array)$setting['subcategory'][$category_id])))) : array();
            $subcategory_limit = max(1, min(30, isset($setting['subcategory_limit']) ? (int)$setting['subcategory_limit'] : 10));
            foreach (isset($children_by_parent[$category_id]) ? $children_by_parent[$category_id] : array() as $child) {
                $child_id = (int)$child['category_id'];
                // Empty selection means automatic mode: show direct children, like UniShop.
                // Once the merchant selects children explicitly, only those are shown.
                if ($selected_children && !in_array($child_id, $selected_children, true)) { continue; }
                $item['subcategories'][] = array(
                    'category_id' => $child_id,
                    'name' => $child['name'],
                    'href' => $this->url->link('product/category', 'path=' . $category_id . '_' . $child_id)
                );
                if (count($item['subcategories']) >= $subcategory_limit) { break; }
            }

            $data['categories'][] = $item;
        }

        if (!$data['categories']) {
            return;
        }

        $heading = isset($setting['heading']) && is_array($setting['heading']) && isset($setting['heading'][$language_id]) ? trim((string)$setting['heading'][$language_id]) : '';
        $data['heading_title'] = $heading;
        $data['image_mode'] = $image_mode;
        $data['show_image'] = $show_image;
        $data['show_count'] = $show_count;
        $data['image_fit'] = isset($setting['image_fit']) && $setting['image_fit'] === 'cover' ? 'cover' : 'contain';
        $data['display_mode'] = isset($setting['display_mode']) && $setting['display_mode'] === 'grid' ? 'grid' : 'carousel';
        $data['display_mode_mobile'] = isset($setting['display_mode_mobile']) && $setting['display_mode_mobile'] === 'grid' ? 'grid' : 'inherit';
        $data['autoplay'] = !empty($setting['autoplay']) ? 1 : 0;
        $data['autoplay_delay'] = max(1500, min(20000, isset($setting['autoplay_delay']) ? (int)$setting['autoplay_delay'] : 5000));
        $data['show_arrows'] = !isset($setting['show_arrows']) || !empty($setting['show_arrows']) ? 1 : 0;
        $data['show_dots'] = !isset($setting['show_dots']) || !empty($setting['show_dots']) ? 1 : 0;
        $data['loop'] = !isset($setting['loop']) || !empty($setting['loop']) ? 1 : 0;
        $data['carousel_step'] = isset($setting['carousel_step']) && $setting['carousel_step'] === 'page' ? 'page' : 'item';

        $data['image_width'] = $width;
        $data['image_height'] = $height;
        $data['image_width_mobile'] = $width_mobile;
        $data['image_height_mobile'] = $height_mobile;
        $data['image_ratio'] = $width . ' / ' . $height;
        $data['image_ratio_mobile'] = $width_mobile . ' / ' . $height_mobile;

        $data['columns_desktop'] = max(2, min(8, isset($setting['columns_desktop']) ? (int)$setting['columns_desktop'] : 5));
        $data['columns_tablet'] = max(1, min(6, isset($setting['columns_tablet']) ? (int)$setting['columns_tablet'] : 3));
        $data['columns_mobile'] = max(1, min(3, isset($setting['columns_mobile']) ? (int)$setting['columns_mobile'] : 2));
        $mobile_peek = isset($setting['mobile_peek']) ? (string)$setting['mobile_peek'] : '1.2';
        if (!in_array($mobile_peek, array('0', '1.2', '1.3', '1.4'), true)) {
            $mobile_peek = '0';
        }
        $data['mobile_peek'] = $mobile_peek;
        $data['columns_mobile_effective'] = ($data['display_mode'] === 'carousel' && $data['display_mode_mobile'] !== 'grid' && $mobile_peek !== '0') ? $mobile_peek : $data['columns_mobile'];

        $this->document->addStyle('catalog/view/javascript/codecart/modules/native-modules.css?v=3.0.6.0-9');
        $this->document->addScript('catalog/view/javascript/codecart/modules/native-modules.js?v=3.0.6.0-7', 'footer');

        return $this->load->view('extension/module/category_wall', $data);
    }
}
