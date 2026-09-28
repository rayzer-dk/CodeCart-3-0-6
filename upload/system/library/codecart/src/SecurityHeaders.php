<?php
namespace CodeCart\Core;

final class SecurityHeaders {
    private $registry;
    private $config;
    private $response;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->config = $registry->get('config');
        $this->response = $registry->get('response');
    }

    public function apply(bool $admin = false): void {
        // The administration is never embedded by a foreign origin. Clickjacking
        // protection for admin is therefore on by default and independent of the
        // storefront header settings (payment widgets may legitimately frame the
        // storefront). Opt-out only via codecart_security_admin_frame_protection=0.
        $adminFrameProtection = $admin && (string)$this->config->get('codecart_security_admin_frame_protection') !== '0';
        if ($adminFrameProtection) {
            $this->response->addHeader('X-Frame-Options: SAMEORIGIN');
            $this->response->addHeader("Content-Security-Policy: frame-ancestors 'self'");
        }

        if (!(int)$this->config->get('codecart_security_headers_status')) {
            return;
        }

        if ((int)$this->config->get('codecart_security_nosniff_status')) {
            $this->response->addHeader('X-Content-Type-Options: nosniff');
        }

        $referrer = trim((string)$this->config->get('codecart_security_referrer_policy'));
        $allowedReferrer = array('no-referrer','no-referrer-when-downgrade','origin','origin-when-cross-origin','same-origin','strict-origin','strict-origin-when-cross-origin','unsafe-url');
        if ($referrer !== '' && in_array($referrer, $allowedReferrer, true)) {
            $this->response->addHeader('Referrer-Policy: ' . $referrer);
        }

        if (!$adminFrameProtection && (int)$this->config->get('codecart_security_frame_options_status')) {
            $frame = strtoupper(trim((string)$this->config->get('codecart_security_frame_options')));
            if (!in_array($frame, array('DENY','SAMEORIGIN'), true)) {
                $frame = 'SAMEORIGIN';
            }
            $this->response->addHeader('X-Frame-Options: ' . $frame);
        }

        if ((int)$this->config->get('codecart_security_permissions_policy_status')) {
            $policy = $this->sanitizePolicy((string)$this->config->get('codecart_security_permissions_policy'));
            if ($policy !== '') {
                $this->response->addHeader('Permissions-Policy: ' . $policy);
            }
        }

        if ((int)$this->config->get('codecart_security_csp_report_only_status')) {
            $policy = $this->sanitizePolicy((string)$this->config->get('codecart_security_csp_report_only_policy'));
            if ($policy !== '') {
                $this->response->addHeader('Content-Security-Policy-Report-Only: ' . $policy);
            }
        }

        if ((int)$this->config->get('codecart_security_hsts_status') && $this->isHttps()) {
            $maxAge = (int)$this->config->get('codecart_security_hsts_max_age');
            $maxAge = max(300, min(63072000, $maxAge > 0 ? $maxAge : 31536000));
            $value = 'max-age=' . $maxAge;
            if ((int)$this->config->get('codecart_security_hsts_subdomains')) {
                $value .= '; includeSubDomains';
            }
            $this->response->addHeader('Strict-Transport-Security: ' . $value);
        }
    }

    private function sanitizePolicy(string $value): string {
        $value = trim(str_replace(array("\r", "\n", "\0"), '', $value));
        return substr($value, 0, 4000);
    }

    private function isHttps(): bool {
        if (function_exists('codecart_is_https')) {
            return (bool)codecart_is_https();
        }
        $request = $this->registry->get('request');
        return $request && !empty($request->server['HTTPS']) && strtolower((string)$request->server['HTTPS']) !== 'off';
    }
}
