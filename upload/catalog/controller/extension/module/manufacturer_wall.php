<?php
class ControllerExtensionModuleManufacturerWall extends Controller {
    public function index($setting) {
        if (empty($setting['status'])) { return; }
        $this->load->language('extension/module/manufacturer_wall');
        $this->load->language('extension/module/codecart_slider');
		$data['text_pause_autoplay'] = $this->language->get('text_pause_autoplay');
		$data['text_play_autoplay'] = $this->language->get('text_play_autoplay');
        $this->load->model('catalog/manufacturer');
        $this->load->model('tool/image');

        $data['text_previous_items'] = $this->language->get('text_previous');
        $data['text_next_items'] = $this->language->get('text_next');
        $language_id = (int)$this->config->get('config_language_id');
        $source = isset($setting['source']) && $setting['source'] === 'selected' ? 'selected' : 'all';
        $limit = max(1, min(100, (int)($setting['limit'] ?? 12)));
        $sort = isset($setting['sort']) && $setting['sort'] === 'name' ? 'name' : 'sort_order';

        if ($source === 'selected') {
            $ids = array_values(array_unique(array_filter(array_map('intval', isset($setting['manufacturer'])?(array)$setting['manufacturer']:array()))));
            $ids = array_slice($ids, 0, $limit);
            $rows = $this->model_catalog_manufacturer->getManufacturersByIds($ids);
        } else {
            $rows = $this->model_catalog_manufacturer->getManufacturers(array('sort'=>$sort,'order'=>'ASC','start'=>0,'limit'=>$limit));
        }
        if (!$rows) { return; }

        $width = max(40, min(1200, (int)($setting['width'] ?? 180)));
        $height = max(40, min(1200, (int)($setting['height'] ?? 90)));
        $data['image_width'] = $width;
        $data['image_height'] = $height;
        $show_count = !empty($setting['show_count']);
        $hide_empty = !empty($setting['hide_empty']);
        $manufacturer_ids = array();
        foreach ($rows as $row) { $manufacturer_ids[] = (int)$row['manufacturer_id']; }
        $counts = ($show_count || $hide_empty) ? $this->model_catalog_manufacturer->getManufacturerProductCounts($manufacturer_ids) : array();
        $data['manufacturers'] = array();
        foreach ($rows as $row) {
            $manufacturer_id = (int)$row['manufacturer_id'];
            $count = isset($counts[$manufacturer_id]) ? (int)$counts[$manufacturer_id] : 0;
            if ($hide_empty && $count < 1) { continue; }
            $image = !empty($row['image']) && is_file(DIR_IMAGE . $row['image']) ? (string)$row['image'] : '';
            if (!empty($setting['hide_without_image']) && $image === '') { continue; }
            if ($image === '') { $image = 'no_image.webp'; }
            $data['manufacturers'][] = array(
                'manufacturer_id' => $manufacturer_id,
                'name' => (string)$row['name'],
                'count' => $show_count ? $count : false,
                'thumb' => $this->model_tool_image->resize($image,$width,$height),
                'href' => $this->url->link('product/manufacturer/info','manufacturer_id='.$manufacturer_id)
            );
        }
        if (!$data['manufacturers']) { return; }

        $heading = isset($setting['heading']) && is_array($setting['heading']) && isset($setting['heading'][$language_id]) ? trim((string)$setting['heading'][$language_id]) : '';
        $data['heading_title'] = $heading !== '' ? $heading : $this->language->get('heading_title');
        if (isset($setting['show_heading']) && !$setting['show_heading']) { $data['heading_title'] = ''; }
        $data['show_name'] = !isset($setting['show_name']) || !empty($setting['show_name']);
        $data['show_image'] = !isset($setting['show_image']) || !empty($setting['show_image']);
        $data['show_count'] = $show_count;
        $data['image_fit'] = isset($setting['image_fit']) && $setting['image_fit'] === 'cover' ? 'cover' : 'contain';
        $data['display_mode'] = isset($setting['display_mode']) && $setting['display_mode'] === 'grid' ? 'grid' : 'carousel';
        $data['columns_desktop'] = max(2,min(10,(int)($setting['columns_desktop'] ?? 6)));
        $data['columns_tablet'] = max(1,min(8,(int)($setting['columns_tablet'] ?? 4)));
        $data['columns_mobile'] = max(1,min(3,(int)($setting['columns_mobile'] ?? 2)));
        $data['autoplay'] = !empty($setting['autoplay']) ? 1 : 0;
        $data['autoplay_delay'] = max(1500,min(20000,(int)($setting['autoplay_delay'] ?? 2000)));
        $data['show_arrows'] = !isset($setting['show_arrows']) || !empty($setting['show_arrows']) ? 1 : 0;
        $data['show_dots'] = !isset($setting['show_dots']) || !empty($setting['show_dots']) ? 1 : 0;
        $data['loop'] = !isset($setting['loop']) || !empty($setting['loop']) ? 1 : 0;
        $data['carousel_step'] = isset($setting['carousel_step']) && $setting['carousel_step'] === 'page' ? 'page' : 'item';
        $this->document->addStyle('catalog/view/javascript/codecart/modules/native-modules.css?v=3.0.6.0-9');
        $this->document->addScript('catalog/view/javascript/codecart/modules/native-modules.js?v=3.0.6.0-7', 'footer');
        return $this->load->view('extension/module/manufacturer_wall',$data);
    }
}
