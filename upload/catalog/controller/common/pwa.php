<?php
/**
 * Dynamic installable-web-app manifest and PNG icons for the current OpenCart store.
 * No cart, session, customer data or page content is persisted by this controller.
 */
class ControllerCommonPwa extends Controller {
    private function enabled() {
        return (int)$this->config->get('config_codecart_pwa_status') === 1
            && codecart_is_https((array)$this->request->server);
    }

    private function reject() {
        $this->response->addHeader('HTTP/1.1 404 Not Found');
        $this->response->setOutput('');
    }

    public function manifest() {
        if (!$this->enabled() || ($this->request->server['REQUEST_METHOD'] ?? '') !== 'GET') {
            $this->reject();
            return;
        }

        $name = trim(strip_tags(html_entity_decode((string)$this->config->get('config_name'), ENT_QUOTES, 'UTF-8')));
        if ($name === '') {
            $name = 'Online Store';
        }
        $base = (string)$this->config->get('config_ssl');
        $path = parse_url($base, PHP_URL_PATH);
        $scope = rtrim(is_string($path) ? $path : '/', '/') . '/';
        $scope = $scope === '//' ? '/' : $scope;
        $iconPath = $scope . 'index.php?route=common/pwa/icon&size=';

        $manifest = array(
            'id' => $scope,
            'name' => $name,
            'short_name' => function_exists('mb_substr') ? mb_substr($name, 0, 18, 'UTF-8') : substr($name, 0, 18),
            'start_url' => $scope,
            'scope' => $scope,
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#0b6fd3',
            'icons' => array(
                array('src' => $iconPath . '192', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'),
                array('src' => $iconPath . '512', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable')
            )
        );
        $json = json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        $this->response->addHeader('Content-Type: application/manifest+json; charset=utf-8');
        $this->response->addHeader('Cache-Control: private, no-cache, must-revalidate');
        $this->response->addHeader('X-Content-Type-Options: nosniff');
        $this->response->setOutput(is_string($json) ? $json : '{}');
    }

    public function icon() {
        if (!$this->enabled() || ($this->request->server['REQUEST_METHOD'] ?? '') !== 'GET') {
            $this->reject();
            return;
        }
        $size = (int)($this->request->get['size'] ?? 0);
        if (!in_array($size, array(180, 192, 512), true) || !function_exists('imagecreatetruecolor')) {
            $this->reject();
            return;
        }
        $canvas = imagecreatetruecolor($size, $size);
        if ($canvas === false) {
            $this->reject();
            return;
        }
        $blue = imagecolorallocate($canvas, 11, 111, 211);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $size, $size, $blue);

        // Restrict source to existing, small, local store icons (no URL fetches).
        $image = null;
        $root = realpath(DIR_IMAGE);
        // Prefer the dedicated app icon; fall back to the existing store favicon.
        foreach (array('config_codecart_pwa_icon', 'config_icon') as $setting) {
            $file = str_replace('\\\\', '/', (string)$this->config->get($setting));
            $source = $file !== '' ? realpath(DIR_IMAGE . $file) : false;
            if (!$root || !$source || strpos($source, $root . DIRECTORY_SEPARATOR) !== 0
                || !is_file($source) || filesize($source) > 8 * 1024 * 1024) {
                continue;
            }
            $info = @getimagesize($source);
            if (!is_array($info) || $info[0] <= 0 || $info[1] <= 0
                || $info[0] > 2048 || $info[1] > 2048) {
                continue;
            }
            switch ($info[2]) {
                case IMAGETYPE_PNG:  $image = @imagecreatefrompng($source); break;
                case IMAGETYPE_JPEG: $image = @imagecreatefromjpeg($source); break;
                case IMAGETYPE_GIF:  $image = @imagecreatefromgif($source); break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagecreatefromwebp')) {
                        $image = @imagecreatefromwebp($source);
                    }
                    break;
            }
            if ($image) {
                break;
            }
        }

        if ($image) {
            $w = imagesx($image);
            $h = imagesy($image);
            $box = (int)round($size * 0.64);
            $scale = min($box / max(1, $w), $box / max(1, $h));
            $targetW = max(1, (int)round($w * $scale));
            $targetH = max(1, (int)round($h * $scale));
            imagealphablending($canvas, true);
            imagecopyresampled($canvas, $image, (int)(($size-$targetW)/2), (int)(($size-$targetH)/2), 0, 0, $targetW, $targetH, $w, $h);
            imagedestroy($image);
        } else {
            // Neutral shopping-bag symbol. The icon is self-contained, not an external logo.
            $p = function ($value) use ($size) { return (int)round($value * $size / 100); };
            imagesetthickness($canvas, max(2, (int)round($size * .055)));
            imagerectangle($canvas, $p(30), $p(38), $p(70), $p(75), $white);
            imagearc($canvas, $p(50), $p(39), $p(22), $p(30), 180, 360, $white);
        }

        ob_start();
        imagepng($canvas, null, 6);
        $png = ob_get_clean();
        imagedestroy($canvas);
        $this->response->addHeader('Content-Type: image/png');
        $this->response->addHeader('Cache-Control: private, max-age=3600');
        $this->response->addHeader('X-Content-Type-Options: nosniff');
        $this->response->setOutput(is_string($png) ? $png : '');
    }
}
