<?php
class ControllerExtensionCaptchaBasic extends Controller {
    private const SESSION_KEY = 'codecart_local_captcha';
    private const COLS = 3;
    private const ROWS = 3;
    private const TARGET_COUNT = 3;
    private const MAX_ATTEMPTS = 5;
    private const DEFAULT_MIN_AGE_MS = 900;
    private const VERIFIED_TTL = 1800;

    public function index($error = array()) {
        $this->load->language('extension/captcha/basic');

        $this->document->addStyle('catalog/view/javascript/codecart/captcha/local-captcha.css?v=3.0.6.0-captcha-outline6');
        $this->document->addScript('catalog/view/javascript/codecart/captcha/local-captcha.js?v=3.0.6.0-captcha-outline6', 'footer');

        $postedNonce = $this->requestNonce();
        $existing = $postedNonce !== '' ? $this->getChallenge($postedNonce) : array();
        $reuseAfterError = !empty($error['captcha']) && !empty($existing['nonce']) && !empty($existing['expires']) && (int)$existing['expires'] >= time();
        $forceVisual = $reuseAfterError && !empty($existing['force_visual']) && (int)(isset($existing['attempts']) ? $existing['attempts'] : 0) >= self::MAX_ATTEMPTS;
        $challenge = $reuseAfterError ? $existing : $this->createChallenge(false);
        if ($forceVisual) {
            $challenge['force_visual'] = true;
            $this->storeChallenge($challenge);
        }
        $mode = $this->captchaMode();
        $showVisual = $mode === 'visual' || !empty($challenge['force_visual']);

        $data['error_captcha'] = isset($error['captcha']) ? $error['captcha'] : '';
        $data['captcha_nonce'] = $challenge['nonce'];
        $data['captcha_mode'] = $mode;
        $data['captcha_show_visual'] = $showVisual;
        $data['captcha_honeypot_name'] = $challenge['honeypot_name'];
        $data['captcha_verify'] = $this->url->link('extension/captcha/basic/verifyHuman', '', true);
        $data['captcha_prompt'] = sprintf($this->language->get('text_visual_instruction'), (string)$challenge['target_label']);
        // Keep adaptive forms light, but embed the image when Visual mode is
        // actually visible. This avoids a fragile second HTTP request while still
        // preventing base64 CAPTCHA data from bloating every ordinary form page.
        $data['captcha_image_url'] = $showVisual
            ? $this->captchaImageDataUri($challenge)
            : $this->captchaImageUrl((string)$challenge['nonce']);
        $data['captcha_refresh'] = $this->url->link('extension/captcha/basic/refresh', '', true);
        $data['captcha_cols'] = self::COLS;
        $data['captcha_rows'] = self::ROWS;
        $data['captcha_indexes'] = range(0, self::COLS * self::ROWS - 1);
        $data['captcha_min_age_ms'] = $this->minAgeMs();
        $data['captcha_max_attempts'] = self::MAX_ATTEMPTS;
        $data['button_visual_fallback'] = $this->language->get('button_visual_fallback');
        $data['captcha_expires_at'] = (int)$challenge['expires'];

        return $this->load->view('extension/captcha/basic', $data);
    }

    public function validate() {
        $this->load->language('extension/captcha/basic');

        $nonce = $this->requestNonce();
        $challenge = $nonce !== '' ? $this->getChallenge($nonce) : array();
        if (!$challenge || empty($challenge['nonce'])) {
            return $this->language->get('error_captcha');
        }

        $honeypot = isset($challenge['honeypot_name']) ? (string)$challenge['honeypot_name'] : '';
        if ($honeypot !== '' && isset($this->request->post[$honeypot]) && trim((string)$this->request->post[$honeypot]) !== '') {
            $this->deleteChallenge((string)$challenge['nonce']);
            return $this->language->get('error_captcha');
        }

        $submitted = isset($this->request->post['captcha']) && is_scalar($this->request->post['captcha'])
            ? trim((string)$this->request->post['captcha'])
            : '';
        if ($submitted === '' && isset($this->request->post['captcha_tiles']) && is_array($this->request->post['captcha_tiles'])) {
            $indexes = array();
            foreach ($this->request->post['captcha_tiles'] as $index) {
                $index = (int)$index;
                if ($index >= 0 && $index < self::COLS * self::ROWS) { $indexes[$index] = $index; }
            }
            $indexes = array_values($indexes);
            sort($indexes, SORT_NUMERIC);
            if ($indexes) { $submitted = 'v:' . $challenge['nonce'] . ':' . implode(',', $indexes); }
        }

        if ($this->validateAdaptiveToken($challenge, $submitted)) {
            return;
        }

        if (!empty($challenge['verified_value']) && !empty($challenge['verified_until']) && (int)$challenge['verified_until'] >= time() && hash_equals((string)$challenge['verified_value'], (string)$submitted)) {
            return;
        }

        // Visual selections are tied to the short-lived challenge itself. Adaptive
        // verification has its own longer confirmation TTL so a customer can finish
        // filling a form without having to solve CAPTCHA twice.
        if (empty($challenge['expires']) || (int)$challenge['expires'] < time()) {
            return $this->language->get('error_captcha');
        }

        if ($this->validateVisualSelection($challenge, $submitted)) {
            $challenge['verified_token'] = hash('sha256', (string)$submitted . '|' . $challenge['nonce']);
            $challenge['verified_at'] = time();
            $challenge['verified_until'] = time() + self::VERIFIED_TTL;
            $challenge['verified_value'] = (string)$submitted;
            $this->storeChallenge($challenge);
            return;
        }

        $attempts = isset($challenge['attempts']) ? ((int)$challenge['attempts'] + 1) : 1;
        $challenge['attempts'] = $attempts;
        $challenge['force_visual'] = $attempts >= self::MAX_ATTEMPTS;
        $this->storeChallenge($challenge);

        return $this->language->get('error_captcha');
    }

    public function verifyHuman() {
        $this->load->language('extension/captcha/basic');
        $json = array('success' => false, 'fallback' => false, 'message' => $this->language->get('error_captcha'));
        $nonce = isset($this->request->post['nonce']) ? trim((string)$this->request->post['nonce']) : '';
        $challenge = $nonce !== '' ? $this->getChallenge($nonce) : array();
        $honeypotValue = isset($this->request->post['honeypot']) ? trim((string)$this->request->post['honeypot']) : '';
        $method = isset($this->request->post['method']) ? strtolower(trim((string)$this->request->post['method'])) : 'pointer';
        $duration = isset($this->request->post['duration']) ? (int)$this->request->post['duration'] : 0;
        $steps = isset($this->request->post['steps']) ? (int)$this->request->post['steps'] : 0;

        if (!$challenge || empty($challenge['nonce']) || $nonce === '' || !hash_equals((string)$challenge['nonce'], $nonce) || (int)$challenge['expires'] < time()) {
            return $this->json($json, 400);
        }
        if ($honeypotValue !== '') {
            $this->deleteChallenge((string)$challenge['nonce']);
            return $this->json($json, 400);
        }

        $ageMs = (int)round((microtime(true) - (float)$challenge['created_at']) * 1000);
        $minimum = $this->minAgeMs();
        if ($ageMs < $minimum) {
            $json['fallback'] = false;
            $json['message'] = $this->language->get('error_too_fast');
            return $this->json($json, 429);
        }

        if (class_exists('\\CodeCart\\Core\\SpamService')) {
            try {
                $spam = new \CodeCart\Core\SpamService($this->registry);
                $limit = $spam->consume('captcha.verify', 15, 300, 0);
                if (empty($limit['allowed'])) {
                    $json['message'] = $this->language->get('error_rate_limit');
                    return $this->json($json, 429);
                }
            } catch (\Throwable $e) {
                // CAPTCHA remains available if the optional central limiter is unavailable.
            }
        }

        $gestureOk = false;
        if ($method === 'pointer' || $method === 'touch') {
            $gestureOk = $duration >= 250 && $duration <= 10000 && $steps >= 3;
        } elseif ($method === 'keyboard') {
            $gestureOk = $ageMs >= max(1400, $minimum);
        }
        if (!$gestureOk) {
            $attempts = isset($challenge['attempts']) ? ((int)$challenge['attempts'] + 1) : 1;
            $challenge['attempts'] = $attempts;
            $challenge['force_visual'] = $attempts >= self::MAX_ATTEMPTS;
            $this->storeChallenge($challenge);
            $json['fallback'] = $attempts >= self::MAX_ATTEMPTS;
            $json['attempts'] = $attempts;
            $json['max_attempts'] = self::MAX_ATTEMPTS;
            $json['message'] = $this->language->get('error_gesture');
            return $this->json($json, 400);
        }

        $token = bin2hex(random_bytes(16));
        $verifiedUntil = time() + self::VERIFIED_TTL;
        $challenge['verified_token'] = $token;
        $challenge['verified_at'] = time();
        $challenge['verified_until'] = $verifiedUntil;
        $challenge['verified_value'] = '';
        $challenge['force_visual'] = false;
        $this->storeChallenge($challenge);
        $json = array(
            'success' => true,
            'fallback' => false,
            'token' => 'a:' . $challenge['nonce'] . ':' . $token,
            'expires_at' => $verifiedUntil,
            'message' => $this->language->get('text_verified')
        );
        return $this->json($json, 200);
    }

    public function consume() {
        $nonce = $this->requestNonce();
        if ($nonce !== '') { $this->deleteChallenge($nonce); }
        return;
    }

    private function allowImageGeneration($scope = 'captcha.image') {
        if (!class_exists('\\CodeCart\\Core\\SpamService')) {
            return true;
        }
        try {
            $spam = new \CodeCart\Core\SpamService($this->registry);
            $limit = $spam->consume($scope, 30, 300, 1);
            return !empty($limit['allowed']);
        } catch (\Throwable $e) {
            return true;
        }
    }

    public function refresh() {
        $this->load->language('extension/captcha/basic');
        $forceVisual = isset($this->request->get['visual']) && (int)$this->request->get['visual'] === 1;
        if ($forceVisual && !$this->allowImageGeneration('captcha.image.refresh')) {
            return $this->json(array('success' => false, 'message' => $this->language->get('error_rate_limit')), 429);
        }
        $oldNonce = isset($this->request->get['old_nonce']) ? trim((string)$this->request->get['old_nonce']) : '';
        if ($oldNonce !== '') { $this->deleteChallenge($oldNonce); }
        $challenge = $this->createChallenge($forceVisual);

        $json = array(
            'nonce' => $challenge['nonce'],
            'prompt' => sprintf($this->language->get('text_visual_instruction'), (string)$challenge['target_label']),
            // When visual verification is requested, return the generated image
            // in the same JSON response. This removes the session/nonce race that
            // could leave a broken image icon on cached or proxied storefronts.
            'image_url' => $forceVisual
                ? $this->captchaImageDataUri($challenge)
                : $this->captchaImageUrl((string)$challenge['nonce']),
            'cols' => self::COLS,
            'rows' => self::ROWS,
            'honeypot' => $challenge['honeypot_name'],
            'expires_at' => (int)$challenge['expires']
        );

        return $this->json($json, 200);
    }

    public function captcha() {
        if (!$this->allowImageGeneration('captcha.image.render')) {
            $this->response->setStatusCode(429);
            $this->response->addHeader('Retry-After: 60');
            $this->response->setOutput('');
            return;
        }
        $nonce = isset($this->request->get['nonce']) ? (string)$this->request->get['nonce'] : '';
        $challenge = $nonce !== '' ? $this->getChallenge($nonce) : array();

        if (!$challenge || empty($challenge['nonce']) || $nonce === '' || !hash_equals((string)$challenge['nonce'], $nonce) || (int)$challenge['expires'] < time()) {
            $this->response->setStatusCode(404);
            $this->response->setOutput('');
            return;
        }

        // Short-lived same-origin image endpoint used only when visual CAPTCHA is shown.
        // The request carries the regular session cookie and the nonce is validated
        // against that session before any image bytes are generated.
        $uri = $this->captchaImageDataUri($challenge);
        $comma = strpos($uri, ',');
        if ($comma === false) {
            $this->response->setStatusCode(500);
            $this->response->setOutput('');
            return;
        }
        $meta = substr($uri, 5, $comma - 5);
        $payload = substr($uri, $comma + 1);
        $binary = strpos($meta, ';base64') !== false ? base64_decode($payload, true) : rawurldecode($payload);
        if (!is_string($binary) || $binary === '') {
            $this->response->setStatusCode(500);
            $this->response->setOutput('');
            return;
        }
        $contentType = strpos($meta, 'image/webp') === 0 ? 'image/webp' : 'image/svg+xml; charset=utf-8';
        $this->response->addHeader('Content-Type: ' . $contentType);
        $this->response->addHeader('Content-Length: ' . strlen($binary));
        $this->response->addHeader('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        $this->response->addHeader('X-Content-Type-Options: nosniff');
        $this->response->setOutput($binary);
    }

    private function captchaImageUrl($nonce) {
        $nonce = strtolower(trim((string)$nonce));
        if (!preg_match('/^[a-f0-9]{32}$/', $nonce)) {
            return '';
        }
        return $this->url->link('extension/captcha/basic/captcha', 'nonce=' . rawurlencode($nonce), true);
    }

    private function captchaImageDataUri(array $challenge) {
        if (function_exists('imagecreatetruecolor') && function_exists('imagewebp')) {
            $level = ob_get_level();
            ob_start();
            try {
                $webp = $this->renderCaptchaWebp($challenge);
            } catch (\Throwable $e) {
                $webp = '';
            }
            while (ob_get_level() > $level) { ob_end_clean(); }
            if (is_string($webp) && strlen($webp) > 12 && substr($webp, 0, 4) === 'RIFF' && substr($webp, 8, 4) === 'WEBP') {
                return 'data:image/webp;base64,' . base64_encode($webp);
            }
        }
        return 'data:image/svg+xml;base64,' . base64_encode($this->renderCaptchaSvg($challenge));
    }

    private function renderCaptchaSvg(array $challenge) {
        $tileWidth = 96;
        $tileHeight = 96;
        $gap = 5;
        $padding = 7;
        $width = $padding * 2 + self::COLS * $tileWidth + (self::COLS - 1) * $gap;
        $height = $padding * 2 + self::ROWS * $tileHeight + (self::ROWS - 1) * $gap;
        $icons = isset($challenge['icons']) && is_array($challenge['icons']) ? $challenge['icons'] : array();
        $palette = array('#fafafa','#f8f9fa','#f9fafb','#f7f8f9','#fafafb','#f8f8f9');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
        $svg .= '<rect width="100%" height="100%" fill="#f8f9fb"/>';
        for ($i = 0; $i < self::COLS * self::ROWS; $i++) {
            $col = $i % self::COLS;
            $row = intdiv($i, self::COLS);
            $x = $padding + $col * ($tileWidth + $gap);
            $y = $padding + $row * ($tileHeight + $gap);
            $cx = $x + (int)($tileWidth / 2) + random_int(-4, 4);
            $cy = $y + (int)($tileHeight / 2) + random_int(-4, 4);
            $key = isset($icons[$i]) ? (string)$icons[$i] : 'star';
            $angle = random_int(-180, 180);
            $scale = random_int(88, 112) / 100;
            $fill = $palette[random_int(0, count($palette) - 1)];
            $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . ($tileWidth - 1) . '" height="' . ($tileHeight - 1) . '" rx="6" fill="' . $fill . '" stroke="#dadee3"/>';
            // Light geometric texture makes simple template matching harder without making the task ambiguous.
            for ($n = 0; $n < 4; $n++) {
                $x1 = $x + random_int(4, $tileWidth - 5);
                $y1 = $y + random_int(4, $tileHeight - 5);
                $x2 = $x + random_int(4, $tileWidth - 5);
                $y2 = $y + random_int(4, $tileHeight - 5);
                $svg .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="#9aa3ad" stroke-opacity=".24" stroke-width="' . random_int(1, 2) . '"/>';
            }
            for ($n = 0; $n < 3; $n++) {
                $svg .= '<circle cx="' . ($x + random_int(10, $tileWidth - 10)) . '" cy="' . ($y + random_int(10, $tileHeight - 10)) . '" r="' . random_int(4, 11) . '" fill="none" stroke="#aeb5bd" stroke-opacity=".34" stroke-width="1"/>';
            }
            $svg .= '<g transform="translate(' . $cx . ' ' . $cy . ') rotate(' . $angle . ') scale(' . $scale . ')" fill="none" stroke="#c9cfd6" stroke-opacity=".58" stroke-width="1.55" stroke-linecap="round" stroke-linejoin="round">' . $this->svgIcon($key) . '</g>';
        }
        return $svg . '</svg>';
    }

    private function createChallenge($forceVisual = false) {
        $cellCount = self::COLS * self::ROWS;
        $targetPositions = range(0, $cellCount - 1);
        shuffle($targetPositions);
        $targetIndexes = array_slice($targetPositions, 0, self::TARGET_COUNT);
        sort($targetIndexes, SORT_NUMERIC);

        $keys = array_keys($this->iconMap());
        $targetKey = $keys[random_int(0, count($keys) - 1)];
        $distractors = array_values(array_diff($keys, array($targetKey)));
        shuffle($distractors);
        $icons = array_fill(0, $cellCount, '');
        foreach ($targetIndexes as $index) { $icons[$index] = $targetKey; }
        $d = 0;
        for ($i = 0; $i < $cellCount; $i++) {
            if ($icons[$i] !== '') { continue; }
            $icons[$i] = $distractors[$d % count($distractors)];
            $d++;
        }
        $targetLabel = $this->iconLabel($targetKey);

        $nonce = bin2hex(random_bytes(16));
        $challenge = array(
            'nonce' => $nonce,
            'icons' => $icons,
            'target_indexes' => $targetIndexes,
            'target_key' => $targetKey,
            'target_label' => $targetLabel,
            'honeypot_name' => 'ccp_field_' . bin2hex(random_bytes(6)),
            'created_at' => microtime(true),
            'expires' => time() + 600,
            'attempts' => 0,
            'force_visual' => (bool)$forceVisual,
            'verified_token' => '',
            'verified_at' => 0,
            'verified_until' => 0,
            'verified_value' => ''
        );
        $this->storeChallenge($challenge);
        return $challenge;
    }

    private function requestNonce() {
        $submitted = isset($this->request->post['captcha']) && is_scalar($this->request->post['captcha']) ? trim((string)$this->request->post['captcha']) : '';
        if ($submitted !== '' && preg_match('/^[av]:([a-f0-9]{32}):/i', html_entity_decode($submitted, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $match)) {
            return strtolower((string)$match[1]);
        }
        if (isset($this->request->post['captcha_nonce']) && is_scalar($this->request->post['captcha_nonce'])) {
            $nonce = strtolower(trim((string)$this->request->post['captcha_nonce']));
            return preg_match('/^[a-f0-9]{32}$/', $nonce) ? $nonce : '';
        }
        return '';
    }

    private function challengeStore() {
        $store = isset($this->session->data[self::SESSION_KEY]) && is_array($this->session->data[self::SESSION_KEY]) ? $this->session->data[self::SESSION_KEY] : array();
        // Seamlessly migrate the old single-challenge session shape.
        if (isset($store['nonce'])) {
            $legacy = $store;
            $store = array('challenges' => array((string)$legacy['nonce'] => $legacy));
        }
        if (!isset($store['challenges']) || !is_array($store['challenges'])) { $store['challenges'] = array(); }
        $now = time();
        foreach ($store['challenges'] as $nonce => $challenge) {
            $expires = is_array($challenge) && isset($challenge['expires']) ? (int)$challenge['expires'] : 0;
            $verifiedUntil = is_array($challenge) && isset($challenge['verified_until']) ? (int)$challenge['verified_until'] : 0;
            if (max($expires, $verifiedUntil) < $now) { unset($store['challenges'][$nonce]); }
        }
        if (count($store['challenges']) > 8) {
            uasort($store['challenges'], function($a, $b) {
                return (float)(isset($a['created_at']) ? $a['created_at'] : 0) <=> (float)(isset($b['created_at']) ? $b['created_at'] : 0);
            });
            while (count($store['challenges']) > 8) { array_shift($store['challenges']); }
        }
        $this->session->data[self::SESSION_KEY] = $store;
        return $store;
    }

    private function getChallenge($nonce) {
        $nonce = strtolower(trim((string)$nonce));
        if (!preg_match('/^[a-f0-9]{32}$/', $nonce)) { return array(); }
        $store = $this->challengeStore();
        return isset($store['challenges'][$nonce]) && is_array($store['challenges'][$nonce]) ? $store['challenges'][$nonce] : array();
    }

    private function storeChallenge(array $challenge) {
        if (empty($challenge['nonce'])) { return; }
        $nonce = strtolower((string)$challenge['nonce']);
        if (!preg_match('/^[a-f0-9]{32}$/', $nonce)) { return; }
        $store = $this->challengeStore();
        $store['challenges'][$nonce] = $challenge;
        $this->session->data[self::SESSION_KEY] = $store;
    }

    private function deleteChallenge($nonce) {
        $nonce = strtolower(trim((string)$nonce));
        if ($nonce === '') { return; }
        $store = $this->challengeStore();
        if (isset($store['challenges'][$nonce])) { unset($store['challenges'][$nonce]); }
        $this->session->data[self::SESSION_KEY] = $store;
    }

    private function validateAdaptiveToken(array $challenge, $submitted) {
        if (strpos((string)$submitted, 'a:') !== 0 || empty($challenge['verified_token']) || empty($challenge['verified_at'])) {
            return false;
        }
        $parts = explode(':', (string)$submitted, 3);
        if (count($parts) !== 3 || !hash_equals((string)$challenge['nonce'], (string)$parts[1]) || !hash_equals((string)$challenge['verified_token'], (string)$parts[2])) {
            return false;
        }
        $verifiedUntil = !empty($challenge['verified_until']) ? (int)$challenge['verified_until'] : ((int)$challenge['verified_at'] + self::VERIFIED_TTL);
        return $verifiedUntil >= time();
    }

    private function validateVisualSelection(array $challenge, $submitted) {
        if (!empty($challenge['verified_value']) && !empty($challenge['verified_until']) && (int)$challenge['verified_until'] >= time() && hash_equals((string)$challenge['verified_value'], (string)$submitted)) {
            return true;
        }

        $value = (string)$submitted;
        if (strpos($value, 'v:') === 0) {
            $value = substr($value, 2);
        }
        $parts = explode(':', $value, 2);
        $selected = array();
        if (count($parts) === 2 && preg_match('/^\d+(?:,\d+)*$/', (string)$parts[1])) {
            foreach (explode(',', (string)$parts[1]) as $index) {
                $index = (int)$index;
                if ($index >= 0 && $index < self::COLS * self::ROWS) { $selected[$index] = $index; }
            }
        }
        $selected = array_values($selected);
        sort($selected, SORT_NUMERIC);
        $expected = isset($challenge['target_indexes']) && is_array($challenge['target_indexes']) ? array_map('intval', $challenge['target_indexes']) : array();
        sort($expected, SORT_NUMERIC);
        return count($parts) === 2
            && hash_equals((string)$challenge['nonce'], (string)$parts[0])
            && count($selected) === count($expected)
            && hash_equals(implode(',', $expected), implode(',', $selected));
    }

    private function captchaMode() {
        $mode = strtolower(trim((string)$this->config->get('captcha_basic_mode')));
        return in_array($mode, array('adaptive', 'visual'), true) ? $mode : 'adaptive';
    }

    private function minAgeMs() {
        $value = (int)$this->config->get('captcha_basic_min_age_ms');
        if ($value < 500 || $value > 5000) { $value = self::DEFAULT_MIN_AGE_MS; }
        return $value;
    }

    private function json(array $data, $status) {
        $status = (int)$status;
        if ($status !== 200) {
            $reasons = array(400 => 'Bad Request', 429 => 'Too Many Requests');
            $this->response->setStatusCode($status);
        }
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->addHeader('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        $this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return;
    }

    private function iconMap() {
        return array(
            'camera' => 'f030', 'cart' => 'f07a', 'star' => 'f005', 'heart' => 'f004',
            'home' => 'f015', 'phone' => 'f095', 'key' => 'f084', 'truck' => 'f0d1',
            'gift' => 'f06b', 'bell' => 'f0f3', 'leaf' => 'f06c', 'wrench' => 'f0ad'
        );
    }

    private function iconGlyph($key) {
        $map = $this->iconMap();
        $hex = isset($map[$key]) ? $map[$key] : $map['star'];
        return json_decode('"\\u' . $hex . '"');
    }

    private function renderCaptchaWebp(array $challenge) {
        $tileWidth = 96;
        $tileHeight = 96;
        $gap = 5;
        $padding = 7;
        $width = $padding * 2 + self::COLS * $tileWidth + (self::COLS - 1) * $gap;
        $height = $padding * 2 + self::ROWS * $tileHeight + (self::ROWS - 1) * $gap;
        $image = @imagecreatetruecolor($width, $height);
        if (!$image) { return ''; }
        if (function_exists('imageantialias')) { @imageantialias($image, true); }
        $background = imagecolorallocate($image, 233, 238, 244);
        $border = imagecolorallocate($image, 205, 216, 228);
        $ink = imagecolorallocate($image, 201, 207, 214);
        $texture = imagecolorallocate($image, 190, 198, 207);
        $palette = array(
            array(237,245,251), array(244,241,232), array(237,247,242),
            array(247,238,243), array(238,240,248), array(247,244,236)
        );
        imagefilledrectangle($image, 0, 0, $width, $height, $background);
        $icons = isset($challenge['icons']) && is_array($challenge['icons']) ? $challenge['icons'] : array();
        for ($i = 0; $i < self::COLS * self::ROWS; $i++) {
            $col = $i % self::COLS;
            $row = intdiv($i, self::COLS);
            $x = $padding + $col * ($tileWidth + $gap);
            $y = $padding + $row * ($tileHeight + $gap);
            $rgb = $palette[random_int(0, count($palette) - 1)];
            $tileBg = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
            imagefilledrectangle($image, $x, $y, $x + $tileWidth - 2, $y + $tileHeight - 2, $tileBg);
            imagerectangle($image, $x, $y, $x + $tileWidth - 2, $y + $tileHeight - 2, $border);
            for ($n = 0; $n < 3; $n++) {
                imageline($image,
                    $x + random_int(5, $tileWidth - 6), $y + random_int(5, $tileHeight - 6),
                    $x + random_int(5, $tileWidth - 6), $y + random_int(5, $tileHeight - 6),
                    $texture);
            }
            for ($n = 0; $n < 2; $n++) {
                imageellipse($image,
                    $x + random_int(12, $tileWidth - 12), $y + random_int(12, $tileHeight - 12),
                    random_int(8, 20), random_int(8, 20), $texture);
            }
            $key = isset($icons[$i]) ? (string)$icons[$i] : 'star';
            $this->drawRotatedCaptchaIcon(
                $image,
                $key,
                $x + (int)($tileWidth / 2) + random_int(-4, 4),
                $y + (int)($tileHeight / 2) + random_int(-4, 4),
                random_int(-180, 180),
                $ink
            );
        }
        ob_start();
        $ok = @imagewebp($image, null, 84);
        $binary = (string)ob_get_clean();
        if (PHP_VERSION_ID < 80500 && function_exists('imagedestroy')) { imagedestroy($image); }
        return $ok && $binary !== '' ? $binary : '';
    }

    private function drawRotatedCaptchaIcon($image, $key, $cx, $cy, $angle, $color) {
        $size = 66;
        $layer = @imagecreatetruecolor($size, $size);
        if (!$layer) {
            $this->drawCaptchaIcon($image, $key, $cx, $cy, $color);
            return;
        }
        imagealphablending($layer, false);
        imagesavealpha($layer, true);
        $transparent = imagecolorallocatealpha($layer, 255, 255, 255, 127);
        imagefilledrectangle($layer, 0, 0, $size - 1, $size - 1, $transparent);
        imagealphablending($layer, true);
        $layerInk = imagecolorallocate($layer, 201, 207, 214);
        $this->drawCaptchaIcon($layer, $key, (int)($size / 2), (int)($size / 2), $layerInk);
        $rotated = @imagerotate($layer, -$angle, $transparent);
        if ($rotated) {
            imagealphablending($image, true);
            $rw = imagesx($rotated);
            $rh = imagesy($rotated);
            imagecopy($image, $rotated, $cx - (int)($rw / 2), $cy - (int)($rh / 2), 0, 0, $rw, $rh);
            if (PHP_VERSION_ID < 80500 && function_exists('imagedestroy')) { imagedestroy($rotated); }
        } else {
            $this->drawCaptchaIcon($image, $key, $cx, $cy, $color);
        }
        if (PHP_VERSION_ID < 80500 && function_exists('imagedestroy')) { imagedestroy($layer); }
    }

    private function drawCaptchaIcon($image, $key, $cx, $cy, $color) {
        // Use the original bundled icon glyphs, but rasterise only their contour.
        // This preserves the recognisable CAPTCHA symbols while avoiding the high-
        // contrast filled silhouettes that made the visual challenge too obvious.
        $font = DIR_APPLICATION . 'view/javascript/codecart/captcha/captcha-icons.ttf';
        if (function_exists('imagettftext') && function_exists('imagettfbbox') && is_file($font)) {
            $glyph = $this->iconGlyph((string)$key);
            if ($glyph !== '') {
                $fontSize = 31;
                $box = @imagettfbbox($fontSize, 0, $font, $glyph);
                if (is_array($box) && count($box) >= 8) {
                    $minX = min($box[0], $box[2], $box[4], $box[6]);
                    $maxX = max($box[0], $box[2], $box[4], $box[6]);
                    $minY = min($box[1], $box[3], $box[5], $box[7]);
                    $maxY = max($box[1], $box[3], $box[5], $box[7]);
                    $x = (int)round($cx - (($maxX - $minX) / 2) - $minX);
                    $y = (int)round($cy - (($maxY - $minY) / 2) - $minY);

                    $maskWidth = imagesx($image);
                    $maskHeight = imagesy($image);
                    $mask = @imagecreatetruecolor($maskWidth, $maskHeight);
                    if ($mask) {
                        $black = imagecolorallocate($mask, 0, 0, 0);
                        $white = imagecolorallocate($mask, 255, 255, 255);
                        imagefilledrectangle($mask, 0, 0, $maskWidth - 1, $maskHeight - 1, $black);

                        if (@imagettftext($mask, $fontSize, 0, $x, $y, $white, $font, $glyph) !== false) {
                            $left = max(1, (int)floor($x + $minX) - 2);
                            $right = min($maskWidth - 2, (int)ceil($x + $maxX) + 2);
                            $top = max(1, (int)floor($y + $minY) - 2);
                            $bottom = min($maskHeight - 2, (int)ceil($y + $maxY) + 2);

                            for ($py = $top; $py <= $bottom; $py++) {
                                for ($px = $left; $px <= $right; $px++) {
                                    $value = imagecolorat($mask, $px, $py) & 0xFF;
                                    if ($value < 72) { continue; }

                                    $edge = false;
                                    for ($oy = -2; $oy <= 2 && !$edge; $oy += 2) {
                                        for ($ox = -2; $ox <= 2; $ox += 2) {
                                            if ($ox === 0 && $oy === 0) { continue; }
                                            if ((imagecolorat($mask, $px + $ox, $py + $oy) & 0xFF) < 72) {
                                                $edge = true;
                                                break;
                                            }
                                        }
                                    }
                                    if ($edge) { imagesetpixel($image, $px, $py, $color); }
                                }
                            }

                            if (PHP_VERSION_ID < 80500 && function_exists('imagedestroy')) { imagedestroy($mask); }
                            return;
                        }

                        if (PHP_VERSION_ID < 80500 && function_exists('imagedestroy')) { imagedestroy($mask); }
                    }
                }
            }
        }

        // Geometry fallback for GD builds without FreeType.
        imagesetthickness($image, 2);
        switch ((string)$key) {
            case 'camera':
                imagerectangle($image, $cx - 15, $cy - 9, $cx + 15, $cy + 10, $color);
                imagerectangle($image, $cx - 7, $cy - 14, $cx + 6, $cy - 9, $color);
                imageellipse($image, $cx, $cy + 1, 12, 12, $color);
                break;
            case 'cart':
                imageline($image, $cx - 17, $cy - 13, $cx - 12, $cy - 13, $color);
                imageline($image, $cx - 12, $cy - 13, $cx - 8, $cy + 7, $color);
                imageline($image, $cx - 8, $cy + 7, $cx + 13, $cy + 7, $color);
                imageline($image, $cx - 10, $cy - 7, $cx + 16, $cy - 7, $color);
                imageline($image, $cx + 16, $cy - 7, $cx + 12, $cy + 5, $color);
                imageellipse($image, $cx - 5, $cy + 13, 5, 5, $color); imageellipse($image, $cx + 10, $cy + 13, 5, 5, $color);
                break;
            case 'heart':
                imagearc($image, $cx - 7, $cy - 4, 16, 16, 190, 350, $color);
                imagearc($image, $cx + 7, $cy - 4, 16, 16, 190, 350, $color);
                imageline($image, $cx - 15, $cy - 3, $cx, $cy + 15, $color); imageline($image, $cx + 15, $cy - 3, $cx, $cy + 15, $color);
                break;
            case 'home':
                $homePoints = array($cx - 17,$cy - 1,$cx,$cy - 16,$cx + 17,$cy - 1);
                if (PHP_VERSION_ID >= 80500) { imagepolygon($image, $homePoints, $color); }
                else { imagepolygon($image, $homePoints, 3, $color); }
                imagerectangle($image, $cx - 12, $cy - 1, $cx + 12, $cy + 14, $color);
                imagerectangle($image, $cx - 4, $cy + 5, $cx + 4, $cy + 14, $color);
                break;
            case 'phone':
                // Clear smartphone silhouette: tall rounded body, speaker and home button.
                imagearc($image, $cx, $cy, 30, 44, 0, 360, $color);
                imagerectangle($image, $cx - 13, $cy - 20, $cx + 13, $cy + 20, $color);
                imageline($image, $cx - 5, $cy - 15, $cx + 5, $cy - 15, $color);
                imageellipse($image, $cx, $cy + 15, 4, 4, $color);
                break;
            case 'key':
                // Classic key: large ring, diagonal shaft and two visible teeth.
                imageellipse($image, $cx - 10, $cy - 8, 17, 17, $color);
                imageellipse($image, $cx - 10, $cy - 8, 7, 7, $color);
                imageline($image, $cx - 4, $cy - 2, $cx + 14, $cy + 16, $color);
                imageline($image, $cx + 7, $cy + 9, $cx + 12, $cy + 4, $color);
                imageline($image, $cx + 11, $cy + 13, $cx + 16, $cy + 8, $color);
                break;
            case 'truck':
                imagerectangle($image, $cx - 17, $cy - 10, $cx + 3, $cy + 8, $color); imagerectangle($image, $cx + 3, $cy - 4, $cx + 15, $cy + 8, $color);
                imageline($image, $cx + 3, $cy - 4, $cx + 10, $cy - 4, $color); imageline($image, $cx + 10, $cy - 4, $cx + 15, $cy + 2, $color);
                imageellipse($image, $cx - 10, $cy + 11, 6, 6, $color); imageellipse($image, $cx + 10, $cy + 11, 6, 6, $color);
                break;
            case 'gift':
                imagerectangle($image, $cx - 15, $cy - 7, $cx + 15, $cy + 14, $color); imagerectangle($image, $cx - 17, $cy - 13, $cx + 17, $cy - 7, $color);
                imageline($image, $cx, $cy - 13, $cx, $cy + 14, $color); imagearc($image, $cx - 6, $cy - 14, 13, 9, 180, 355, $color); imagearc($image, $cx + 6, $cy - 14, 13, 9, 185, 360, $color);
                break;
            case 'bell':
                imagearc($image, $cx, $cy, 26, 29, 180, 360, $color); imageline($image, $cx - 13, $cy, $cx - 16, $cy + 11, $color); imageline($image, $cx + 13, $cy, $cx + 16, $cy + 11, $color); imageline($image, $cx - 16, $cy + 11, $cx + 16, $cy + 11, $color); imageellipse($image, $cx, $cy + 15, 6, 6, $color);
                break;
            case 'leaf':
                imagearc($image, $cx, $cy, 30, 25, 20, 210, $color); imagearc($image, $cx, $cy, 30, 25, 200, 20, $color); imageline($image, $cx - 12, $cy + 10, $cx + 12, $cy - 10, $color);
                break;
            case 'wrench':
                imageellipse($image, $cx - 10, $cy - 8, 15, 15, $color); imageline($image, $cx - 5, $cy - 3, $cx + 14, $cy + 15, $color); imageellipse($image, $cx + 15, $cy + 15, 7, 7, $color);
                break;
            case 'star':
            default:
                $points = array();
                for ($i = 0; $i < 10; $i++) {
                    $angle = deg2rad(-90 + $i * 36);
                    $radius = ($i % 2 === 0) ? 16 : 7;
                    $points[] = (int)round($cx + cos($angle) * $radius); $points[] = (int)round($cy + sin($angle) * $radius);
                }
                if (PHP_VERSION_ID >= 80500) { imagepolygon($image, $points, $color); }
                else { imagepolygon($image, $points, 10, $color); }
                break;
        }
        imagesetthickness($image, 1);
    }

    private function svgIcon($key) {
        $icons = array(
            'camera' => '<rect x="-16" y="-10" width="32" height="22" rx="3"/><path d="M-9-10l3-5h12l3 5"/><circle cx="0" cy="1" r="6"/>',
            'cart' => '<path d="M-17-13h4l3 19h20l4-13h-25"/><circle cx="-7" cy="12" r="2"/><circle cx="9" cy="12" r="2"/>',
            'star' => '<path d="M0-16l4.7 9.5 10.5 1.5-7.6 7.4 1.8 10.4L0 8l-9.4 4.8 1.8-10.4-7.6-7.4 10.5-1.5z"/>',
            'heart' => '<path d="M0 14S-16 5-16-5c0-8 10-12 16-4 6-8 16-4 16 4C16 5 0 14 0 14z"/>',
            'home' => '<path d="M-17 0L0-15 17 0"/><path d="M-12-3v17h24V-3M-4 14V4h8v10"/>',
            'phone' => '<rect x="-12" y="-18" width="24" height="36" rx="4"/><path d="M-5-13h10"/><circle cx="0" cy="13" r="2"/>',
            'key' => '<circle cx="-9" cy="-7" r="7"/><circle cx="-9" cy="-7" r="2.5"/><path d="M-4-2L13 15M6 8l5-5M10 12l5-5"/>',
            'truck' => '<path d="M-17-9H3V9h-20zM3-4h8l6 7v6H3z"/><circle cx="-10" cy="11" r="3"/><circle cx="11" cy="11" r="3"/>',
            'gift' => '<rect x="-15" y="-8" width="30" height="22" rx="2"/><path d="M0-8v22M-17-8h34v-6h-34zM0-14c-7-10-13 1 0 6M0-14c7-10 13 1 0 6"/>',
            'bell' => '<path d="M-13 8h26l-3-5v-8c0-7-4-11-10-11S-10-12-10-5v8zM-4 12c1 5 7 5 8 0"/>',
            'leaf' => '<path d="M-15 11C-13-8-2-16 16-14 15 4 7 14-8 14zM-10 10C-3 3 3-3 11-9"/>',
            'wrench' => '<path d="M10-14a9 9 0 0 0-10 11l-14 14a4 4 0 0 0 6 6L6 3a9 9 0 0 0 11-10l-7 7-6-2-2-6z" transform="scale(.82)"/>'
        );
        return isset($icons[$key]) ? $icons[$key] : $icons['star'];
    }

    private function iconLabel($key) {
        $languageKey = 'text_icon_' . preg_replace('/[^a-z0-9_]/', '', (string)$key);
        $label = $this->language->get($languageKey);
        return $label === $languageKey ? (string)$key : $label;
    }
}
