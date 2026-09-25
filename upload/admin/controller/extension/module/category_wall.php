<?php
class ControllerExtensionModuleCategoryWall extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/category_wall');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/module');
        $this->load->model('catalog/category');
        $this->load->model('localisation/language');

        if (($this->request->server['REQUEST_METHOD'] === 'POST') && $this->validate()) {
            $post = $this->normalise($this->request->post);
            if (!isset($this->request->get['module_id'])) {
                $this->model_setting_module->addModule('category_wall', $post);
            } else {
                $this->model_setting_module->editModule((int)$this->request->get['module_id'], $post);
            }
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
            return;
        }

        $data = array();
        foreach (array('heading_title','text_edit','text_enabled','text_disabled','text_yes','text_no','text_source_selected','text_source_top','text_source_children','text_source_current','text_sort_order','text_sort_name','text_fit_contain','text_fit_cover','text_display_settings','text_grid','text_carousel','text_image_text','text_text_only','text_peek_off','text_mobile_same','text_mobile_grid','entry_display_mode','entry_display_mode_mobile','entry_autoplay','entry_autoplay_delay','entry_show_arrows','entry_show_dots','entry_loop','entry_carousel_step','text_step_item','text_step_page','help_carousel_step','entry_name','entry_heading','entry_source','entry_category','entry_parent','entry_limit','entry_subcategory_limit','entry_columns_desktop','entry_columns_tablet','entry_columns_mobile','entry_width','entry_height','entry_width_mobile','entry_height_mobile','entry_image_fit','entry_image_mode','entry_show_image','entry_show_count','entry_hide_empty','entry_mobile_peek','entry_sort','entry_status','help_category','help_parent','help_columns','help_display_mode','help_display_mode_mobile','help_autoplay_delay','help_image_mode','help_mobile_peek','button_save','button_cancel','text_home','text_extension','text_subcategories','help_subcategories','help_subcategory_limit') as $key) { $data[$key] = $this->language->get($key); }
        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['error_name'] = isset($this->error['name']) ? $this->error['name'] : '';
        $data['breadcrumbs'] = array(
            array('text'=>$this->language->get('text_home'),'href'=>$this->url->link('common/dashboard','user_token='.$this->session->data['user_token'],true)),
            array('text'=>$this->language->get('text_extension'),'href'=>$this->url->link('marketplace/extension','user_token='.$this->session->data['user_token'].'&type=module',true)),
            array('text'=>$this->language->get('heading_title'),'href'=>$this->url->link('extension/module/category_wall','user_token='.$this->session->data['user_token'].(isset($this->request->get['module_id'])?'&module_id='.(int)$this->request->get['module_id']:''),true))
        );
        $data['action'] = $this->url->link('extension/module/category_wall','user_token='.$this->session->data['user_token'].(isset($this->request->get['module_id'])?'&module_id='.(int)$this->request->get['module_id']:''),true);
        $data['cancel'] = $this->url->link('marketplace/extension','user_token='.$this->session->data['user_token'].'&type=module',true);
        $data['user_token'] = $this->session->data['user_token'];
        $module = array();
        if (isset($this->request->get['module_id']) && $this->request->server['REQUEST_METHOD'] !== 'POST') { $module = $this->model_setting_module->getModule((int)$this->request->get['module_id']); }
        $src = $this->request->server['REQUEST_METHOD'] === 'POST' ? $this->request->post : $module;
        $defaults = array('name'=>'','heading'=>array(),'source'=>'selected','category'=>array(),'subcategory'=>array(),'parent_id'=>0,'limit'=>12,'subcategory_limit'=>10,'display_mode'=>'carousel','display_mode_mobile'=>'inherit','columns_desktop'=>5,'columns_tablet'=>3,'columns_mobile'=>2,'mobile_peek'=>'0','autoplay'=>0,'autoplay_delay'=>5000,'show_arrows'=>1,'show_dots'=>1,'loop'=>1,'carousel_step'=>'item','width'=>220,'height'=>180,'width_mobile'=>180,'height_mobile'=>140,'image_fit'=>'contain','image_mode'=>'image_text','show_image'=>1,'show_count'=>1,'hide_empty'=>0,'sort'=>'sort_order','status'=>1);
        foreach ($defaults as $key=>$default) { $data[$key] = isset($src[$key]) ? $src[$key] : $default; }
        if (!isset($src['image_mode']) && isset($src['show_image']) && !$src['show_image']) { $data['image_mode'] = 'text_only'; }
        $data['show_image'] = $data['image_mode'] === 'image_text' ? 1 : 0;
        $data['languages'] = $this->model_localisation_language->getLanguages();
        $data['categories'] = array();
        $data['category_subcategories'] = array();
        $selected_ids = array_values(array_unique(array_filter(array_map('intval', (array)$data['category']))));
        if ($selected_ids) {
            foreach ($this->model_catalog_category->getCategoriesByIds($selected_ids) as $info) {
                $category_id = (int)$info['category_id'];
                $data['categories'][] = array('category_id'=>$category_id,'name'=>$info['name']);
                $selected_children = isset($data['subcategory'][$category_id]) ? array_values(array_unique(array_filter(array_map('intval', (array)$data['subcategory'][$category_id])))) : array();
                $children = array();
                foreach ($this->model_catalog_category->getCategoriesByParentId($category_id) as $child) {
                    $children[] = array(
                        'category_id' => (int)$child['category_id'],
                        'name' => $child['name'],
                        'selected' => in_array((int)$child['category_id'], $selected_children, true)
                    );
                }
                $data['category_subcategories'][$category_id] = $children;
            }
        }
        $data['parent_name'] = '';
        if ((int)$data['parent_id']) { $info=$this->model_catalog_category->getCategory((int)$data['parent_id']); if($info){$data['parent_name']=$info['name'];} }
        $data['header']=$this->load->controller('common/header'); $data['column_left']=$this->load->controller('common/column_left'); $data['footer']=$this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/module/category_wall',$data));
    }

    public function subcategories() {
        $this->load->language('extension/module/category_wall');
        $json = array('children' => array());

        if (!$this->user->hasPermission('access', 'extension/module/category_wall')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('catalog/category');
            $category_id = isset($this->request->get['category_id']) ? (int)$this->request->get['category_id'] : 0;
            if ($category_id > 0) {
                foreach ($this->model_catalog_category->getCategoriesByParentId($category_id) as $child) {
                    $json['children'][] = array('category_id' => (int)$child['category_id'], 'name' => $child['name']);
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function normalise($post) {
        $post['name'] = trim((string)$post['name']);
        $heading = isset($post['heading']) && is_array($post['heading']) ? $post['heading'] : array();
        $post['heading'] = array();
        foreach ($heading as $language_id => $value) {
            $language_id = (int)$language_id;
            if ($language_id > 0) {
                $post['heading'][$language_id] = utf8_substr(trim(strip_tags((string)$value)), 0, 120);
            }
        }
        $post['source'] = in_array(isset($post['source'])?$post['source']:'selected', array('selected','top','children','current'), true) ? $post['source'] : 'selected';
        $post['category'] = array_values(array_unique(array_filter(array_map('intval', isset($post['category'])?(array)$post['category']:array()))));
        $subcategory = isset($post['subcategory']) && is_array($post['subcategory']) ? $post['subcategory'] : array();
        $post['subcategory'] = array();
        foreach ($subcategory as $parent_id => $child_ids) {
            $parent_id = (int)$parent_id;
            if ($parent_id > 0 && in_array($parent_id, $post['category'], true)) {
                $post['subcategory'][$parent_id] = array_values(array_unique(array_filter(array_map('intval', (array)$child_ids))));
            }
        }
        $post['parent_id'] = isset($post['parent_id']) ? (int)$post['parent_id'] : 0;
        $post['display_mode'] = isset($post['display_mode']) && $post['display_mode'] === 'grid' ? 'grid' : 'carousel';
        $post['display_mode_mobile'] = isset($post['display_mode_mobile']) && $post['display_mode_mobile'] === 'grid' ? 'grid' : 'inherit';
        $post['autoplay'] = !empty($post['autoplay']) ? 1 : 0;
        $post['autoplay_delay'] = max(1500, min(20000, isset($post['autoplay_delay']) ? (int)$post['autoplay_delay'] : 5000));
        $post['show_arrows'] = !empty($post['show_arrows']) ? 1 : 0;
        $post['show_dots'] = !empty($post['show_dots']) ? 1 : 0;
        $post['loop'] = !empty($post['loop']) ? 1 : 0;
        $post['carousel_step'] = isset($post['carousel_step']) && $post['carousel_step'] === 'page' ? 'page' : 'item';
        foreach (array('limit'=>array(1,100,12),'subcategory_limit'=>array(1,30,10),'columns_desktop'=>array(2,8,5),'columns_tablet'=>array(1,6,3),'columns_mobile'=>array(1,3,2),'width'=>array(40,1600,220),'height'=>array(40,1600,180),'width_mobile'=>array(40,1200,180),'height_mobile'=>array(40,1200,140)) as $key=>$rule) { $v=isset($post[$key])?(int)$post[$key]:$rule[2]; $post[$key]=max($rule[0],min($rule[1],$v)); }
        $post['image_fit'] = isset($post['image_fit']) && $post['image_fit']==='cover' ? 'cover' : 'contain';
        if (isset($post['image_mode'])) {
            $post['image_mode'] = $post['image_mode'] === 'text_only' ? 'text_only' : 'image_text';
        } else {
            $post['image_mode'] = !isset($post['show_image']) || !empty($post['show_image']) ? 'image_text' : 'text_only';
        }
        $post['show_image'] = $post['image_mode'] === 'image_text' ? 1 : 0;
        $post['mobile_peek'] = isset($post['mobile_peek']) && in_array((string)$post['mobile_peek'], array('0','1.2','1.3','1.4'), true) ? (string)$post['mobile_peek'] : '0';
        $post['sort'] = isset($post['sort']) && $post['sort']==='name' ? 'name' : 'sort_order';
        $post['show_count'] = !empty($post['show_count']) ? 1 : 0; $post['hide_empty'] = !empty($post['hide_empty']) ? 1 : 0; $post['status'] = !empty($post['status']) ? 1 : 0;
        return $post;
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify','extension/module/category_wall')) { $this->error['warning']=$this->language->get('error_permission'); }
        $name=isset($this->request->post['name'])?trim((string)$this->request->post['name']):'';
        if (utf8_strlen($name)<3 || utf8_strlen($name)>64) { $this->error['name']=$this->language->get('error_name'); }
        return !$this->error;
    }
}
