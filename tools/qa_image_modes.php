<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$fixture = realpath(sys_get_temp_dir()) . '/codecart-image-modes-' . bin2hex(random_bytes(6));
mkdir($fixture . '/catalog', 0755, true);
define('DIR_IMAGE', $fixture . '/');
require $root . '/upload/system/engine/registry.php';
require $root . '/upload/system/engine/model.php';
require $root . '/upload/system/library/config.php';
require $root . '/upload/system/helper/utf8.php';
require $root . '/upload/system/library/image.php';
require $root . '/upload/catalog/model/tool/image.php';

$checks = 0;
$failed = false;
function imageCheck(bool $condition, string $message): void {
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    $checks++;
}
function derivative(string $url): string {
    imageCheck(str_contains($url, '/image/cache/'), 'Derivative must use image/cache');
    return DIR_IMAGE . rawurldecode(substr($url, strpos($url, '/image/') + 7));
}
function dimensions(string $file): array {
    $info = Image::getInfo($file);
    return array((int)$info[0], (int)$info[1]);
}

try {
    imageCheck(extension_loaded('gd'), 'GD is required');
    $raster = imagecreatetruecolor(1086, 355);
    imagefill($raster, 0, 0, imagecolorallocate($raster, 25, 80, 130));
    imagejpeg($raster, DIR_IMAGE . 'catalog/wide.jpg', 100);
    $hash = hash_file('sha256', DIR_IMAGE . 'catalog/wide.jpg');

    $legacy = new Image(DIR_IMAGE . 'catalog/wide.jpg');
    $legacy->resize(1100, 1100);
    imageCheck($legacy->getWidth() === 1100 && $legacy->getHeight() === 1100, 'Legacy resize retains the requested canvas');
    imageCheck(imagecolorsforindex($legacy->getImage(), imagecolorat($legacy->getImage(), 0, 0))['red'] === 255, 'Legacy padding stays compatible');
    imageCheck(method_exists(Image::class, 'resizeContain'), 'Image::resizeContain must exist');
    imageCheck(method_exists(Image::class, 'resizeCover'), 'Image::resizeCover must exist');
    $contained = new Image(DIR_IMAGE . 'catalog/wide.jpg');
    $contained->resizeContain(1100, 1100);
    imageCheck($contained->getWidth() === 1100 && $contained->getHeight() === 360, 'Contain uses a proportional canvas');
    foreach (array(array(0, 0), array(1099, 0), array(0, 359), array(1099, 359)) as $point) {
        $color = imagecolorsforindex($contained->getImage(), imagecolorat($contained->getImage(), $point[0], $point[1]));
        imageCheck($color['red'] < 50 && $color['blue'] > 100, 'Contain has no white border, including rounding edges');
    }

    $strip = imagecreatetruecolor(300, 100);
    imagefilledrectangle($strip, 0, 0, 99, 99, imagecolorallocate($strip, 255, 0, 0));
    imagefilledrectangle($strip, 100, 0, 199, 99, imagecolorallocate($strip, 0, 255, 0));
    imagefilledrectangle($strip, 200, 0, 299, 99, imagecolorallocate($strip, 0, 0, 255));
    imagepng($strip, DIR_IMAGE . 'catalog/strip.png');
    $cover = new Image(DIR_IMAGE . 'catalog/strip.png');
    $cover->resizeCover(80, 80);
    imageCheck($cover->getWidth() === 80 && $cover->getHeight() === 80, 'Cover fills the requested canvas');
    foreach (array(0, 40, 79) as $x) {
        $color = imagecolorsforindex($cover->getImage(), imagecolorat($cover->getImage(), $x, 40));
        imageCheck($color['green'] > 240 && $color['red'] < 10 && $color['blue'] < 10, 'Cover crops the source centrally');
    }

    $registry = new Registry();
    $config = new Config();
    $config->set('config_url', 'http://localhost/');
    $registry->set('config', $config);
    $request = (object)array('server' => array('HTTP_ACCEPT' => 'image/avif,image/webp'));
    $registry->set('request', $request);
    $model = new ModelToolImage($registry);
    copy(DIR_IMAGE . 'catalog/wide.jpg', DIR_IMAGE . 'catalog/wide-contain.jpg');
    touch(DIR_IMAGE . 'catalog/wide-contain.jpg', filemtime(DIR_IMAGE . 'catalog/wide.jpg'));
    $containedCache = $model->resizeContain('catalog/wide.jpg', 200, 200);
    $namedLikeMode = $model->resize('catalog/wide-contain.jpg', 200, 200);
    imageCheck($containedCache !== $namedLikeMode, 'Mode caches cannot collide with source filenames');
    imageCheck(dimensions(derivative($namedLikeMode)) === array(200, 200), 'Legacy named-like-mode input keeps exact canvas');
    imageCheck(method_exists($model, 'productImage'), 'Explicit CodeCart product image API must exist');
    $config->set('config_theme', 'codecart');
    foreach (array(array(500, 300, 1100, 660), array(300, 500, 660, 1100), array(1600, 900, 1100, 619), array(900, 1600, 619, 1100), array(800, 800, 1100, 1100), array(2000, 2000, 1100, 1100), array(100, 100, 300, 300)) as $sample) {
        $input = imagecreatetruecolor($sample[0], $sample[1]);
        imagefill($input, 0, 0, imagecolorallocate($input, 25, 80, 130));
        imagejpeg($input, DIR_IMAGE . 'catalog/sample-' . $sample[0] . 'x' . $sample[1] . '.jpg');
        $name = 'catalog/sample-' . $sample[0] . 'x' . $sample[1] . '.jpg';
        $url = $model->productImage($name, 1100, 1100, 'gallery');
        imageCheck(dimensions(derivative($url)) === array($sample[2], $sample[3]), 'Product contain respects 3x cap: ' . $name);
        $metadata = $model->getProductImageSize($name, 1100, 1100, 'gallery');
        imageCheck(array($metadata['width'], $metadata['height']) === array($sample[2], $sample[3]), 'Product metadata equals actual dimensions');
        imagedestroy($input);
    }
    $config->set('theme_codecart_image_upscale', '2');
    imageCheck(dimensions(derivative($model->productImage('catalog/sample-100x100.jpg', 1100, 1100))) === array(200, 200), 'Common upscale setting applies');
    $config->set('theme_codecart_image_gallery_upscale', '1');
    imageCheck(dimensions(derivative($model->productImage('catalog/sample-100x100.jpg', 1100, 1100, 'gallery'))) === array(100, 100), 'Gallery may disable upscaling');
    $config->set('theme_codecart_image_gallery_upscale', '0');
    imageCheck(dimensions(derivative($model->productImage('catalog/sample-100x100.jpg', 1100, 1100, 'gallery'))) === array(1100, 1100), 'Unlimited upscale is explicit');
    $config->set('theme_codecart_image_catalog_fit', 'cover');
    imageCheck(dimensions(derivative($model->productImage('catalog/strip.png', 80, 80))) === array(80, 80), 'Catalog may choose cover');
    $beforeBackground = $model->productImage('catalog/strip.png', 80, 80);
    $config->set('theme_codecart_image_catalog_background', 'dark');
    imageCheck($beforeBackground === $model->productImage('catalog/strip.png', 80, 80), 'Background changes never invalidate image cache');
    $config->set('config_theme', 'default');
    imageCheck($model->productImage('catalog/wide.jpg', 200, 200) === $model->resize('catalog/wide.jpg', 200, 200), 'Other themes keep the legacy resize pipeline');
    $config->set('config_theme', 'codecart');
    $urls = array(
        $model->resize('catalog/wide.jpg', 200, 200),
        $model->resizeContain('catalog/wide.jpg', 200, 200),
        $model->resizeCover('catalog/wide.jpg', 200, 200)
    );
    imageCheck(count(array_unique($urls)) === 3, 'Each resize mode has a separate cache key: ' . json_encode($urls));
    foreach (array(array(200, 200), array(200, 65), array(200, 200)) as $i => $size) {
        imageCheck(dimensions(derivative($urls[$i])) === $size, 'Mode dimensions match actual file');
    }
    imageCheck(str_ends_with($urls[1], '.jpg'), 'WebP and AVIF stay off by default');
    $display = $model->display('catalog/wide.jpg', 200, 200);
    imageCheck(dimensions(derivative($display)) === array(200, 65), 'CodeCart display explicitly uses contain');
    $size = $model->getDisplaySize('catalog/wide.jpg', 200, 200);
    imageCheck(array($size['width'], $size['height']) === array(200, 65), 'Display metadata matches its cached file');
    imageCheck(dimensions(derivative($model->display('catalog/wide.jpg', 1100, 1100))) === array(1086, 355), 'Display preserves small originals without upscaling');

    $alpha = imagecreatetruecolor(90, 30);
    imagealphablending($alpha, false);
    imagesavealpha($alpha, true);
    imagefill($alpha, 0, 0, imagecolorallocatealpha($alpha, 0, 0, 0, 127));
    imagefilledrectangle($alpha, 35, 8, 55, 22, imagecolorallocatealpha($alpha, 100, 50, 20, 0));
    imagepng($alpha, DIR_IMAGE . 'catalog/alpha.png');
    $palette = imagecreate(90, 30);
    $transparent = imagecolorallocate($palette, 0, 0, 0);
    imagecolortransparent($palette, $transparent);
    imagefill($palette, 0, 0, $transparent);
    imagefilledrectangle($palette, 35, 8, 55, 22, imagecolorallocate($palette, 100, 50, 20));
    imagegif($palette, DIR_IMAGE . 'catalog/alpha.gif');
    foreach (array('resizeContain', 'resizeCover') as $method) {
        $gif = new Image(derivative($model->{$method}('catalog/alpha.gif', 60, 60)));
        $pixel = imagecolorsforindex($gif->getImage(), imagecolorat($gif->getImage(), 0, 0));
        imageCheck($pixel['alpha'] >= 120, 'GIF transparency survives ' . $method);
    }
    foreach (array('resizeContain', 'resizeCover') as $method) {
        foreach (array('png', 'webp', 'avif') as $format) {
            if ($format === 'avif' && !function_exists('imageavif')) { continue; }
            $config->set('config_image_webp', $format === 'webp' ? 1 : 0);
            $config->set('config_image_avif', $format === 'avif' ? 1 : 0);
            $url = $model->{$method}('catalog/alpha.png', 60, 60);
            $file = derivative($url);
            imageCheck(str_ends_with($file, '.' . $format), 'Requested ' . $method . ' output ' . $format . ': ' . $url);
            $decoded = new Image($file);
            $color = imagecolorsforindex($decoded->getImage(), imagecolorat($decoded->getImage(), 0, 0));
            imageCheck($color['alpha'] >= 120, 'Transparency survives ' . $method . ' and ' . $format);
        }
    }
    $config->set('config_image_avif', 0);
    $config->set('config_image_webp', 1);
    $request->server['HTTP_ACCEPT'] = '';
    imageCheck(str_ends_with($model->resizeContain('catalog/wide.jpg', 200, 200), '.jpg'), 'CLI/feed requests retain the original format');
    $request->server['HTTP_ACCEPT'] = 'image/webp';
    $config->set('config_image_webp_quality', 75);
    $firstQuality = $model->resizeContain('catalog/wide.jpg', 200, 200);
    $config->set('config_image_webp_quality', 90);
    imageCheck($firstQuality !== $model->resizeContain('catalog/wide.jpg', 200, 200), 'Quality changes invalidate modern derivatives');
    imageCheck($model->resizeContain('../outside.jpg', 200, 200) === null, 'Path traversal cannot read outside image root');
    imageCheck(hash_file('sha256', DIR_IMAGE . 'catalog/wide.jpg') === $hash, 'Original source remains unchanged');
    fwrite(STDOUT, '[PASS] Image modes: ' . $checks . " assertions\n");
} catch (Throwable $e) {
    fwrite(STDERR, '[FAIL] ' . $e->getMessage() . "\n");
    $failed = true;
} finally {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fixture, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) { $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
    rmdir($fixture);
}
if ($failed) { exit(1); }
