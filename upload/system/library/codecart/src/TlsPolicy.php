<?php
namespace CodeCart\Core;

/**
 * Central TLS policy for CodeCart PRO outbound connections.
 *
 * TLS 1.0/1.1 are intentionally excluded. TLS 1.2 is the minimum and
 * TLS 1.3 is used automatically when the local TLS stack and peer support it.
 */
final class TlsPolicy {
    public static function streamCryptoMethod(): int {
        return STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
    }

    public static function streamContextOptions(string $peerName): array {
        return array(
            'verify_peer' => true,
            'verify_peer_name' => true,
            'peer_name' => $peerName,
            'SNI_enabled' => true,
            'crypto_method' => self::streamCryptoMethod()
        );
    }

    public static function curlSslVersion(): int {
        if (!defined('CURL_SSLVERSION_TLSv1_2')) {
            throw new \RuntimeException('The installed libcurl is too old to enforce TLS 1.2.');
        }

        $version = CURL_SSLVERSION_TLSv1_2;

        if (defined('CURL_SSLVERSION_MAX_TLSv1_3')) {
            $version |= CURL_SSLVERSION_MAX_TLSv1_3;
        }

        return $version;
    }

    public static function applyCurl($handle): void {
        if (!function_exists('curl_setopt')) {
            throw new \RuntimeException('The cURL extension is unavailable.');
        }

        if (!curl_setopt($handle, CURLOPT_SSLVERSION, self::curlSslVersion())) {
            throw new \RuntimeException('Unable to apply the CodeCart PRO TLS policy to cURL.');
        }
    }
}
