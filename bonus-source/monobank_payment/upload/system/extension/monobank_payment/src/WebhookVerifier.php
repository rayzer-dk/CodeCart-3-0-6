<?php
namespace CodeCart\Extension\MonobankPayment;

final class WebhookVerifier {
    public function verify($body, $signatureBase64, $publicKeyBase64): bool {
        $signature = base64_decode((string)$signatureBase64, true);
        $publicKeyPem = base64_decode((string)$publicKeyBase64, true);
        if ($signature === false || $publicKeyPem === false || $signature === '' || $publicKeyPem === '') { return false; }
        $key = openssl_pkey_get_public($publicKeyPem);
        if ($key === false) { return false; }
        $result = openssl_verify((string)$body, $signature, $key, OPENSSL_ALGO_SHA256);
        if (is_resource($key)) { openssl_free_key($key); }
        return $result === 1;
    }
}
