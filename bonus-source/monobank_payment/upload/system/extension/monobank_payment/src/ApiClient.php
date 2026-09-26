<?php
namespace CodeCart\Extension\MonobankPayment;

final class ApiClient {
    private $token;
    private $timeout;
    private $connectTimeout;

    public function __construct($token, $timeout = 15, $connectTimeout = 5) {
        $this->token = trim((string)$token);
        $this->timeout = max(3, min(30, (int)$timeout));
        $this->connectTimeout = max(2, min(10, (int)$connectTimeout));
    }

    public function merchantDetails(): array {
        return $this->request('GET', '/api/merchant/details');
    }

    public function createInvoice(array $payload): array {
        return $this->request('POST', '/api/merchant/invoice/create', $payload);
    }

    public function publicKey(): string {
        $result = $this->request('GET', '/api/merchant/pubkey');
        if (isset($result['key']) && is_string($result['key'])) { return $result['key']; }
        if (isset($result['pubkey']) && is_string($result['pubkey'])) { return $result['pubkey']; }
        if (isset($result['publicKey']) && is_string($result['publicKey'])) { return $result['publicKey']; }
        if (isset($result['value']) && is_string($result['value'])) { return $result['value']; }
        if (count($result) === 1) {
            $value = reset($result);
            if (is_string($value)) { return $value; }
        }
        throw new \RuntimeException('Monobank API returned an unexpected public-key response.');
    }

    private function request($method, $path, array $payload = null): array {
        if ($this->token === '') { throw new \RuntimeException('Monobank token is not configured.'); }
        if (!function_exists('curl_init')) { throw new \RuntimeException('PHP cURL extension is required.'); }

        $url = 'https://api.monobank.ua' . $path;
        $ch = curl_init($url);
        $headers = array(
            'Accept: application/json',
            'X-Token: ' . $this->token,
            'X-Cms: CodeCart',
            'X-Cms-Version: 3.0.6.0'
        );
        $method = strtoupper((string)$method);
        if ($method === 'POST') {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) { throw new \RuntimeException('Unable to encode Monobank request.'); }
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ));
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) { throw new \RuntimeException('Monobank connection error: ' . $error); }
        $data = json_decode((string)$body, true);
        if (!is_array($data)) { $data = array(); }
        if ($status < 200 || $status >= 300) {
            $message = isset($data['errText']) ? (string)$data['errText'] : (isset($data['message']) ? (string)$data['message'] : 'HTTP ' . $status);
            throw new \RuntimeException('Monobank API error: ' . $message);
        }
        return $data;
    }
}
