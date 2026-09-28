<?php
class ControllerExtensionModuleSubcategories extends Controller {
    public function index($setting) {
        if (empty($setting['status']) || empty($this->request->get['path'])) return;

        $parts = array_values(array_filter(array_map('intval', explode('_', (string)$this->request->get['path']))));
        if (!$parts) return;
        $category_id = (int)end($parts);
        if ($category_id < 1) return;

        $this->load->language('extension/module/subcategories');
        $this->load->model('catalog/category');
        $this->load->model('tool/image');

        $rows = $this->model_catalog_category->getCategories($category_id);
        if (!$rows) return;
        $limit = max(1, min(100, isset($setting['limit']) ? (int)$setting['limit'] : 20));
        $rows = array_slice($rows, 0, $limit);
        $show_image = !isset($setting['show_image']) || !empty($setting['show_image']);
        $width = max(40, min(400, isset($setting['width']) ? (int)$setting['width'] : 96));
        $height = max(40, min(400, isset($setting['height']) ? (int)$setting['height'] : 72));
        $base_path = implode('_', $parts);

        $data['categories'] = array();
        foreach ($rows as $row) {
            $image = !empty($row['image']) ? (string)$row['image'] : 'no_image.webp';
            $data['categories'][] = array(
                'name' => $row['name'],
                'href' => $this->url->link('product/category', 'path=' . $base_path . '_' . (int)$row['category_id']),
                'thumb' => $show_image ? $this->model_tool_image->resize($image, $width, $height) : ''
            );
        }
        $language_id = (int)$this->config->get('config_language_id');
        $heading = isset($setting['heading'][$language_id]) ? trim((string)$setting['heading'][$language_id]) : '';
        $data['heading_title'] = $heading !== '' ? $heading : $this->language->get('heading_title');
        $data['show_image'] = $show_image;
        $data['image_width'] = $width;
        $data['image_height'] = $height;
        return $this->load->view('extension/module/subcategories', $data);
    }
}
