<?php
class ControllerReportLostUrl extends Controller {
    private function postedId(): int {
        $id = $this->request->post['id'] ?? '';
        return is_scalar($id) && ctype_digit((string)$id) ? (int)$id : 0;
    }

    public function index() {
        $this->load->language('report/lost_url');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->addStyle('view/stylesheet/codecart-lost-url.css');
        $token = $this->session->data['user_token'] ?? '';
        $given = $this->request->get['user_token'] ?? '';
        if (!is_string($given) || !is_string($token) || $token === '' || !hash_equals($token, $given) || !$this->user->hasPermission('access', 'report/online')) {
            $this->response->setStatusCode(403);
            return $this->response->setOutput($this->load->controller('error/permission'));
        }
        $this->load->model('report/lost_url');
        $canModify = $this->user->hasPermission('modify', 'design/seo_url');
        $installed = (bool)$this->config->get('codecart_lost_url_installed');
        $enabled = (bool)$this->config->get('codecart_lost_url_status');
        $error = '';
        if (($this->request->server['REQUEST_METHOD'] ?? '') === 'POST') {
            $action = $this->request->post['action'] ?? '';
            if (!$canModify) { $error = 'error_permission'; }
            elseif ($action === 'settings') {
                $enabled = isset($this->request->post['enabled']) && is_scalar($this->request->post['enabled']) && (string)$this->request->post['enabled'] === '1';
                try {
                    if ($enabled) { (new \CodeCart\Core\LostUrlMonitor($this->registry))->install(); $installed = true; }
                    if ($installed) { (new \CodeCart\Core\Scheduler($this->registry))->register('codecart.lost_url.cleanup', 'cron/lost_url', 86400, $enabled); }
                    $this->load->model('setting/setting');
                    $days = \CodeCart\Core\LostUrlMonitor::retentionDays($this->request->post['days'] ?? 60);
                    $mode = ($this->request->post['cleanup_mode'] ?? '') === 'all' ? 'all' : 'safe';
                    $this->model_setting_setting->editSetting('codecart_lost_url', array('codecart_lost_url_status'=>(int)$enabled, 'codecart_lost_url_installed'=>(int)$installed, 'codecart_lost_url_days'=>$days, 'codecart_lost_url_cleanup_mode'=>$mode));
                } catch (\Throwable $e) { $this->log->write('Lost URL setup: ' . get_class($e)); $error = 'error_setup'; }
            } elseif (!$enabled || !$installed) { $error = 'error_disabled'; }
            elseif ($action === 'redirect') {
                $target = $this->request->post['target'] ?? '';
                $error = is_string($target) ? $this->model_report_lost_url->saveRedirect($this->postedId(), trim($target)) : 'error_slug';
            } elseif ($action === 'ignore' || $action === 'reopen') {
                $this->model_report_lost_url->setStatus($this->postedId(), $action === 'ignore' ? 'ignored' : 'new');
            } elseif ($action === 'delete_redirect') {
                $this->model_report_lost_url->deleteRedirect($this->postedId());
            } else { $error = 'error_action'; }
            if (!$error) {
                $this->session->data['success'] = $this->language->get('text_success');
                return $this->response->redirect($this->url->link('report/lost_url', 'user_token=' . $token, true));
            }
        }
        $data = array();
        foreach (array('heading_title','text_help','text_privacy','text_enabled','text_disabled','text_attention','text_doubtful','text_all','text_ignored','text_fixed','text_empty','text_redirects','text_redirect_help','text_confirm','text_backup','text_counts','text_bot_help','text_cron','column_url','column_hits','column_bots','column_seen','column_referrer','column_language','entry_view','entry_search','entry_store','entry_target','button_save','button_filter','button_ignore','button_reopen','button_redirect','button_delete') as $key) { $data[$key] = $this->language->get($key); }
        $data['error_warning'] = $error ? $this->language->get($error) : '';
        $data['success'] = $this->session->data['success'] ?? ''; unset($this->session->data['success']);
        $data['enabled']=$enabled; $data['installed']=$installed; $data['can_modify']=$canModify;
        $data['days']=\CodeCart\Core\LostUrlMonitor::retentionDays($this->config->get('codecart_lost_url_days'));
        $data['cleanup_mode']=$this->config->get('codecart_lost_url_cleanup_mode') === 'all' ? 'all' : 'safe';
        foreach (array('entry_days','entry_cleanup_mode','text_days','text_cleanup_safe','text_cleanup_all','help_retention') as $key) { $data[$key]=$this->language->get($key); }
        $data['user_token']=$token;
        $data['action']=$this->url->link('report/lost_url', 'user_token='.$token, true);
        $data['build']=defined('CODECART_PACKAGE_BUILD') ? CODECART_PACKAGE_BUILD : '';
        $data['store_id']=max(0,(int)($this->request->get['store_id'] ?? 0));
        $view = $this->request->get['view'] ?? 'attention';
        $data['view']=in_array($view,array('attention','doubtful','all','ignored','fixed'),true)?$view:'attention';
        $search=$this->request->get['search'] ?? ''; $data['search']=is_string($search)?utf8_substr($search,0,200):'';
        $sort=$this->request->get['sort'] ?? 'browser_hits'; $data['sort']=in_array($sort,array('browser_hits','last_seen','url'),true)?$sort:'browser_hits';
        $data['order']=($this->request->get['order'] ?? '')==='ASC'?'ASC':'DESC';
        $page=max(1,(int)($this->request->get['page'] ?? 1));
        $this->load->model('setting/store');
        $data['stores']=array_merge(array(array('store_id'=>0,'name'=>$this->config->get('config_name'))),$this->model_setting_store->getStores());
        $data['rows']=array(); $data['redirects']=array(); $total=0; $redirectTotal=0;
        $redirectPage=max(1,(int)($this->request->get['redirect_page'] ?? 1));
        if ($installed) {
            $filter=array('store_id'=>$data['store_id'],'view'=>$data['view'],'search'=>$data['search'],'sort'=>$data['sort'],'order'=>$data['order'],'start'=>($page-1)*25);
            $data['rows']=$this->model_report_lost_url->getEntries($filter);
            $total=$this->model_report_lost_url->getTotal($filter);
            $data['redirects']=$this->model_report_lost_url->getRedirects($data['store_id'],($redirectPage-1)*25);
            $redirectTotal=$this->model_report_lost_url->getRedirectTotal($data['store_id']);
        }
        $args='user_token='.$token.'&store_id='.$data['store_id'].'&view='.$data['view'].'&search='.urlencode($data['search']);
        foreach (array('url','browser_hits','last_seen') as $key) { $data['sort_'.$key]=$this->url->link('report/lost_url',$args.'&sort='.$key.'&order='.($data['order']==='ASC'?'DESC':'ASC'),true); }
        $pagination=new Pagination(); $pagination->total=$total; $pagination->page=$page; $pagination->limit=25;
        $pagination->url=$this->url->link('report/lost_url',$args.'&sort='.$data['sort'].'&order='.$data['order'].'&page={page}',true);
        $data['pagination']=$pagination->render(); $data['total']=$total;
        $pagination->total=$redirectTotal; $pagination->page=$redirectPage;
        $pagination->url=$this->url->link('report/lost_url',$args.'&redirect_page={page}',true);
        $data['redirect_pagination']=$pagination->render();
        $data['header']=$this->load->controller('common/header');
        $data['column_left']=$this->load->controller('common/column_left');
        $data['footer']=$this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('report/lost_url',$data));
    }
}
