<?php
namespace CodeCart\Core;

final class CookieConsent {
    const COOKIE = 'CCPCONSENT';
    const VERSION = 1;

    private $request;
    private $config;

    public function __construct($registry) {
        $this->request = $registry->get('request');
        $this->config = $registry->get('config');
    }

    public function enabled(): bool {
        return (bool)(int)$this->config->get('config_cookie_consent_status');
    }

    public function state(): array {
        $base = array('necessary' => true, 'analytics' => false, 'marketing' => false, 'version' => self::VERSION);
        if (!$this->enabled()) {
            $base['analytics'] = true;
            $base['marketing'] = true;
            return $base;
        }
        $raw = isset($this->request->cookie[self::COOKIE]) ? (string)$this->request->cookie[self::COOKIE] : '';
        if ($raw === '') { return $base; }
        $decoded = json_decode(rawurldecode($raw), true);
        if (!is_array($decoded) || (int)($decoded['v'] ?? 0) !== self::VERSION) { return $base; }
        $base['analytics'] = !empty($decoded['a']);
        $base['marketing'] = !empty($decoded['m']);
        return $base;
    }

    public function hasDecision(): bool {
        if (!$this->enabled()) { return true; }
        $raw = isset($this->request->cookie[self::COOKIE]) ? (string)$this->request->cookie[self::COOKIE] : '';
        if ($raw === '') { return false; }
        $decoded = json_decode(rawurldecode($raw), true);
        return is_array($decoded) && (int)($decoded['v'] ?? 0) === self::VERSION;
    }

    public function allowed(string $category): bool {
        $category = strtolower(trim($category));
        if ($category === 'necessary') { return true; }
        $state = $this->state();
        return !empty($state[$category]);
    }
}
