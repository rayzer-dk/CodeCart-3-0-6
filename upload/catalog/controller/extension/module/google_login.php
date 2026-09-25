<?php
class ControllerExtensionModuleGoogleLogin extends Controller {
    public function button($setting = array()) {
        if (!$this->isConfigured()) { return ''; }
        $context = isset($setting['context']) ? (string)$setting['context'] : 'login';
        if ($context === 'login' && !$this->config->get('module_google_login_show_login')) { return ''; }
        if ($context === 'checkout' && !$this->config->get('module_google_login_show_checkout')) { return ''; }
        $this->load->language('extension/module/google_login');
        $route = ($context === 'checkout') ? 'checkout/checkout' : 'account/account';
        $data['google_url'] = $this->url->link('extension/module/google_login/start', 'return=' . rawurlencode($route), true);
        $data['button_google'] = $this->language->get('button_google');
        $data['text_or'] = $this->language->get('text_or');
        return $this->load->view('extension/module/google_login', $data);
    }

    public function start() {
        $this->load->language('extension/module/google_login');
        if (!$this->isConfigured()) { $this->fail($this->language->get('error_disabled')); return; }
        try {
            $state = bin2hex(random_bytes(32));
            $verifier = $this->base64url(random_bytes(48));
        } catch (Throwable $e) {
            $this->fail($this->language->get('error_login')); return;
        }
        $return = isset($this->request->get['return']) ? (string)$this->request->get['return'] : 'account/account';
        if (!in_array($return, array('account/account','checkout/checkout'), true)) { $return = 'account/account'; }
        $this->session->data['google_login_oauth'] = array('state'=>$state,'verifier'=>$verifier,'return'=>$return,'created'=>time());
        $params = array(
            'client_id'=>(string)$this->config->get('module_google_login_client_id'),
            'redirect_uri'=>$this->redirectUri(),
            'response_type'=>'code',
            'scope'=>'openid email profile',
            'state'=>$state,
            'code_challenge'=>$this->base64url(hash('sha256', $verifier, true)),
            'code_challenge_method'=>'S256',
            'prompt'=>'select_account'
        );
        $this->response->redirect('https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986));
    }

    public function callback() {
        $this->load->language('extension/module/google_login');
        if (!$this->isConfigured()) { $this->fail($this->language->get('error_disabled')); return; }
        $oauth = isset($this->session->data['google_login_oauth']) && is_array($this->session->data['google_login_oauth']) ? $this->session->data['google_login_oauth'] : array();
        unset($this->session->data['google_login_oauth']);
        $return = isset($oauth['return']) && in_array($oauth['return'], array('account/account','checkout/checkout'), true) ? $oauth['return'] : 'account/account';
        $state = isset($this->request->get['state']) ? (string)$this->request->get['state'] : '';
        if (!$oauth || empty($oauth['state']) || empty($oauth['verifier']) || empty($oauth['created']) || time() - (int)$oauth['created'] > 600 || !hash_equals((string)$oauth['state'], $state)) {
            $this->fail($this->language->get('error_state')); return;
        }
        if (!empty($this->request->get['error'])) { $this->fail($this->language->get('error_cancelled')); return; }
        $code = isset($this->request->get['code']) ? trim((string)$this->request->get['code']) : '';
        if ($code === '' || strlen($code) > 4096) { $this->fail($this->language->get('error_login')); return; }

        try {
            $token = $this->httpForm('https://oauth2.googleapis.com/token', array(
                'client_id'=>(string)$this->config->get('module_google_login_client_id'),
                'client_secret'=>(string)$this->config->get('module_google_login_client_secret'),
                'code'=>$code,
                'code_verifier'=>(string)$oauth['verifier'],
                'grant_type'=>'authorization_code',
                'redirect_uri'=>$this->redirectUri()
            ));
            if (empty($token['access_token']) || !is_string($token['access_token'])) { throw new RuntimeException('Access token missing'); }
            $profile = $this->httpJson('https://openidconnect.googleapis.com/v1/userinfo', array('Authorization: Bearer ' . $token['access_token'], 'Accept: application/json'));
            if (empty($profile['sub']) || empty($profile['email']) || empty($profile['email_verified'])) { throw new RuntimeException('Verified profile missing'); }
            $email = trim((string)$profile['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 96) { throw new RuntimeException('Invalid email'); }
            $subHash = hash('sha256', (string)$profile['sub']);
            $customer = $this->resolveCustomer($subHash, $email, $profile);
            if (!$customer || empty($customer['status']) || !$this->customer->login($customer['email'], '', true)) { throw new RuntimeException('Customer login unavailable'); }
            unset($this->session->data['guest']);
            $this->restoreCustomerSession();
            $this->response->redirect($this->url->link($return, '', true));
        } catch (Throwable $e) {
            if ($this->log) { $this->log->write('Google Login failed: ' . get_class($e)); }
            $this->fail($this->language->get('error_login'));
        }
    }

    private function resolveCustomer($subHash, $email, array $profile) {
        if (!$this->tableExists('codecart_google_identity')) { throw new RuntimeException('Google identity table missing'); }
        $this->load->model('account/customer');
        $q = $this->db->query("SELECT customer_id FROM `" . DB_PREFIX . "codecart_google_identity` WHERE google_sub_hash='" . $this->db->escape($subHash) . "' LIMIT 1");
        if ($q->num_rows) {
            $customer = $this->model_account_customer->getCustomer((int)$q->row['customer_id']);
            if ($customer) { return $customer; }
            $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_google_identity` WHERE google_sub_hash='" . $this->db->escape($subHash) . "'");
        }
        $customer = $this->model_account_customer->getCustomerByEmail($email);
        if (!$customer) {
            if (!$this->config->get('module_google_login_auto_register')) { throw new RuntimeException('Auto registration disabled'); }
            $firstname = trim((string)(isset($profile['given_name']) ? $profile['given_name'] : 'Google'));
            $lastname = trim((string)(isset($profile['family_name']) ? $profile['family_name'] : ''));
            $firstname = utf8_substr($firstname !== '' ? $firstname : 'Google', 0, 32);
            $lastname = utf8_substr($lastname, 0, 32);
            $password = bin2hex(random_bytes(24));
            $customerId = $this->model_account_customer->addCustomer(array(
                'firstname'=>$firstname,'lastname'=>$lastname,'email'=>$email,'telephone'=>'','fax'=>'','password'=>$password,'newsletter'=>0,'custom_field'=>array('account'=>array())
            ));
            $customer = $this->model_account_customer->getCustomer($customerId);
        }
        if (!$customer) { throw new RuntimeException('Customer unavailable'); }
        try {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_google_identity` SET customer_id='" . (int)$customer['customer_id'] . "', google_sub_hash='" . $this->db->escape($subHash) . "', date_added=NOW(), date_modified=NOW() ON DUPLICATE KEY UPDATE customer_id=VALUES(customer_id), date_modified=NOW()");
        } catch (Throwable $e) {
            $q = $this->db->query("SELECT customer_id FROM `" . DB_PREFIX . "codecart_google_identity` WHERE google_sub_hash='" . $this->db->escape($subHash) . "' LIMIT 1");
            if (!$q->num_rows || (int)$q->row['customer_id'] !== (int)$customer['customer_id']) { throw $e; }
        }
        return $customer;
    }

    private function restoreCustomerSession() {
        $this->load->model('account/address');
        if ($this->config->get('config_tax_customer') === 'payment') { $this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId()); }
        if ($this->config->get('config_tax_customer') === 'shipping') { $this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId()); }
        if (isset($this->session->data['wishlist']) && is_array($this->session->data['wishlist'])) {
            $this->load->model('account/wishlist');
            foreach ($this->session->data['wishlist'] as $key=>$product_id) { $this->model_account_wishlist->addWishlist((int)$product_id); unset($this->session->data['wishlist'][$key]); }
        }
    }

    private function isConfigured() {
        return (bool)$this->config->get('module_google_login_status') && trim((string)$this->config->get('module_google_login_client_id')) !== '' && trim((string)$this->config->get('module_google_login_client_secret')) !== '';
    }
    private function redirectUri() { return rtrim((string)$this->config->get('config_ssl'), '/') . '/index.php?route=extension/module/google_login/callback'; }
    private function base64url($raw) { return rtrim(strtr(base64_encode($raw), '+/', '-_'), '='); }
    private function fail($message) { $this->session->data['error'] = $message; $this->response->redirect($this->url->link('account/login', '', true)); }
    private function tableExists($table) { $table=preg_replace('/[^a-z0-9_]/i','',(string)$table); if($table===''){return false;} $q=$this->db->query("SHOW TABLES LIKE '".$this->db->escape(DB_PREFIX.$table)."'"); return (bool)$q->num_rows; }

    private function httpForm($url, array $data) {
        return $this->requestJson('POST', $url, array('Content-Type: application/x-www-form-urlencoded','Accept: application/json'), http_build_query($data, '', '&', PHP_QUERY_RFC3986));
    }
    private function httpJson($url, array $headers) { return $this->requestJson('GET', $url, $headers, null); }
    private function requestJson($method, $url, array $headers, $body) {
        if (!preg_match('#^https://#i', $url) || !function_exists('curl_init')) { throw new RuntimeException('Secure HTTP unavailable'); }
        $ch=curl_init($url); curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,8); curl_setopt($ch,CURLOPT_TIMEOUT,20); curl_setopt($ch,CURLOPT_FOLLOWLOCATION,false); curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,true); curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2); curl_setopt($ch,CURLOPT_ENCODING,''); curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
        if ($method==='POST') { curl_setopt($ch,CURLOPT_POST,true); curl_setopt($ch,CURLOPT_POSTFIELDS,(string)$body); }
        $raw=curl_exec($ch); $errno=curl_errno($ch); $status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); unset($ch);
        if($errno||!is_string($raw)||$raw===''||strlen($raw)>1048576||$status<200||$status>=300){throw new RuntimeException('Google HTTP request failed');}
        $decoded=json_decode($raw,true); if(!is_array($decoded)){throw new RuntimeException('Invalid Google response');} return $decoded;
    }
}
