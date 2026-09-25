<?php
class ControllerExtensionModuleManufacturerWall extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/manufacturer_wall');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/module');
        $this->load->model('catalog/manufacturer');
        $this->load->model('localisation/language');

        if (($this->request->server['REQUEST_METHOD'] === 'POST') && $this->validate()) {
            $post = $this->normalise($this->request->post);
            if (!isset($this->request->get['module_id'])) {
                $this->model_setting_module->addModule('manufacturer_wall', $post);
            } else {
                $this->model_setting_module->editModule((int)$this->request->get['module_id'], $post);
            }
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
            return;
        }

        $keys = array('heading_title','text_edit','text_enabled','text_disabled','text_source_selected','text_source_all','text_sort_order','text_sort_name','text_fit_contain','text_fit_cover','text_display_settings','text_grid','text_carousel','text_step_item','text_step_page','entry_display_mode','entry_autoplay','entry_autoplay_delay','entry_show_arrows','entry_show_dots','entry_loop','entry_carousel_step','entry_name','entry_heading','entry_show_heading','entry_source','entry_manufacturer','entry_limit','entry_columns_desktop','entry_columns_tablet','entry_columns_mobile','entry_width','entry_height','entry_image_fit','entry_show_name','entry_show_image','entry_show_count','entry_hide_empty','entry_hide_without_image','entry_sort','entry_status','help_manufacturer','help_columns','help_show_heading','help_display_mode','help_autoplay_delay','help_carousel_step','button_save','button_cancel','text_home','text_extension');
        $data = array();
        foreach ($keys as $key) { $data[$key] = $this->language->get($key); }
        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['error_name'] = isset($this->error['name']) ? $this->error['name'] : '';
        $suffix = isset($this->request->get['module_id']) ? '&module_id=' . (int)$this->request->get['module_id'] : '';
        $data['breadcrumbs'] = array(
            array('text'=>$this->language->get('text_home'),'href'=>$this->url->link('common/dashboard','user_token='.$this->session->data['user_token'],true)),
            array('text'=>$this->language->get('text_extension'),'href'=>$this->url->link('marketplace/extension','user_token='.$this->session->data['user_token'].'&type=module',true)),
            array('text'=>$this->language->get('heading_title'),'href'=>$this->url->link('extension/module/manufacturer_wall','user_token='.$this->session->data['user_token'].$suffix,true))
        );
        $data['action'] = $this->url->link('extension/module/manufacturer_wall','user_token='.$this->session->data['user_token'].$suffix,true);
        $data['cancel'] = $this->url->link('marketplace/extension','user_token='.$this->session->data['user_token'].'&type=module',true);
        $data['user_token'] = $this->session->data['user_token'];

        $module = array();
        if (isset($this->request->get['module_id']) && $this->request->server['REQUEST_METHOD'] !== 'POST') {
            $module = $this->model_setting_module->getModule((int)$this->request->get['module_id']);
        }
        $src = $this->request->server['REQUEST_METHOD'] === 'POST' ? $this->request->post : $module;
        $defaults = array(
            'name'=>'','heading'=>array(),'show_heading'=>1,'source'=>'all','manufacturer'=>array(),'limit'=>12,'display_mode'=>'carousel',
            'columns_desktop'=>6,'columns_tablet'=>4,'columns_mobile'=>2,'autoplay'=>1,'autoplay_delay'=>2000,
            'show_arrows'=>1,'show_dots'=>1,'loop'=>1,'carousel_step'=>'item','width'=>180,'height'=>90,
            'image_fit'=>'contain','show_name'=>1,'show_image'=>1,'show_count'=>0,'hide_empty'=>0,'hide_without_image'=>0,'sort'=>'sort_order','status'=>1
        );
        foreach ($defaults as $key=>$default) { $data[$key] = isset($src[$key]) ? $src[$key] : $default; }
        $data['languages'] = $this->model_localisation_language->getLanguages();
        $data['manufacturers'] = array();
        foreach ((array)$data['manufacturer'] as $manufacturer_id) {
            $info = $this->model_catalog_manufacturer->getManufacturer((int)$manufacturer_id);
            if ($info) { $data['manufacturers'][] = array('manufacturer_id'=>(int)$info['manufacturer_id'],'name'=>$info['name']); }
        }
        $data['header']=$this->load->controller('common/header');
        $data['column_left']=$this->load->controller('common/column_left');
        $data['footer']=$this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/module/manufacturer_wall',$data));
    }

    private function normalise($post) {
        $post['name'] = trim((string)($post['name'] ?? ''));
        $heading = isset($post['heading']) && is_array($post['heading']) ? $post['heading'] : array();
        $post['heading'] = array();
        foreach ($heading as $language_id => $value) {
            $language_id = (int)$language_id;
            if ($language_id > 0) {
                $post['heading'][$language_id] = utf8_substr(trim(strip_tags((string)$value)), 0, 120);
            }
        }
        $post['show_heading'] = !isset($post['show_heading']) || !empty($post['show_heading']) ? 1 : 0;
        $post['source'] = isset($post['source']) && $post['source'] === 'selected' ? 'selected' : 'all';
        $post['manufacturer'] = array_values(array_unique(array_filter(array_map('intval', isset($post['manufacturer'])?(array)$post['manufacturer']:array()))));
        $post['display_mode'] = isset($post['display_mode']) && $post['display_mode'] === 'grid' ? 'grid' : 'carousel';
        $post['autoplay'] = !empty($post['autoplay']) ? 1 : 0;
        $post['autoplay_delay'] = max(1500, min(20000, (int)($post['autoplay_delay'] ?? 2000)));
        $post['show_arrows'] = !empty($post['show_arrows']) ? 1 : 0;
        $post['show_dots'] = !empty($post['show_dots']) ? 1 : 0;
        $post['loop'] = !empty($post['loop']) ? 1 : 0;
        $post['carousel_step'] = isset($post['carousel_step']) && $post['carousel_step'] === 'page' ? 'page' : 'item';
        foreach (array('limit'=>array(1,100,12),'columns_desktop'=>array(2,10,6),'columns_tablet'=>array(1,8,4),'columns_mobile'=>array(1,3,2),'width'=>array(40,1200,180),'height'=>array(40,1200,90)) as $key=>$rule) {
            $value = isset($post[$key]) ? (int)$post[$key] : $rule[2];
            $post[$key] = max($rule[0], min($rule[1], $value));
        }
        $post['image_fit'] = isset($post['image_fit']) && $post['image_fit'] === 'cover' ? 'cover' : 'contain';
        $post['show_name'] = !empty($post['show_name']) ? 1 : 0;
        $post['show_image'] = !empty($post['show_image']) ? 1 : 0;
        if (!$post['show_name'] && !$post['show_image']) { $post['show_name'] = 1; }
        $post['show_count'] = !empty($post['show_count']) ? 1 : 0;
        $post['hide_empty'] = !empty($post['hide_empty']) ? 1 : 0;
        $post['hide_without_image'] = !empty($post['hide_without_image']) ? 1 : 0;
        $post['sort'] = isset($post['sort']) && $post['sort'] === 'name' ? 'name' : 'sort_order';
        $post['status'] = !empty($post['status']) ? 1 : 0;
        return $post;
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify','extension/module/manufacturer_wall')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        $name = trim((string)($this->request->post['name'] ?? ''));
        if (utf8_strlen($name) < 3 || utf8_strlen($name) > 64) {
            $this->error['name'] = $this->language->get('error_name');
        }
        return !$this->error;
    }
}
