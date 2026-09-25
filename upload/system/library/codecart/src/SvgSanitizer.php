<?php
namespace CodeCart\Core;

final class SvgSanitizer {
    const MAX_BYTES = 2097152;
    const MAX_ELEMENTS = 5000;

    private static $allowedElements = array(
        'svg','g','defs','symbol','use','path','rect','circle','ellipse','line','polyline','polygon',
        'text','tspan','title','desc','linearGradient','radialGradient','stop','clipPath','mask','pattern'
    );

    private static $allowedAttributes = array(
        'id','class','x','y','x1','y1','x2','y2','cx','cy','r','rx','ry','width','height','viewBox','preserveAspectRatio',
        'd','points','transform','fill','fill-opacity','fill-rule','stroke','stroke-width','stroke-opacity','stroke-linecap',
        'stroke-linejoin','stroke-miterlimit','stroke-dasharray','stroke-dashoffset','opacity','clip-path','clip-rule','mask',
        'offset','stop-color','stop-opacity','gradientUnits','gradientTransform','spreadMethod','patternUnits','patternContentUnits',
        'patternTransform','font-family','font-size','font-weight','font-style','text-anchor','dominant-baseline','letter-spacing',
        'word-spacing','direction','unicode-bidi','role','aria-label','aria-hidden','focusable','tabindex','href'
    );

    public static function sanitizeFile($path, &$error = '') {
        $error = '';
        if (!class_exists('DOMDocument') || !is_file($path)) {
            $error = 'svg_dom';
            return false;
        }

        $size = (int)@filesize($path);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            $error = 'svg_size';
            return false;
        }

        $xml = @file_get_contents($path);
        if (!is_string($xml) || $xml === '' || preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) {
            $error = 'svg_xml';
            return false;
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $ok = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$ok || !$dom->documentElement || strtolower($dom->documentElement->localName) !== 'svg') {
            $error = 'svg_xml';
            return false;
        }

        $elements = $dom->getElementsByTagName('*');
        if ($elements->length > self::MAX_ELEMENTS) {
            $error = 'svg_complexity';
            return false;
        }

        // Work from a stable snapshot because nodes may be removed during sanitizing.
        $nodes = array();
        foreach ($elements as $element) {
            $nodes[] = $element;
        }

        foreach ($nodes as $element) {
            if (!$element->parentNode && $element !== $dom->documentElement) {
                continue;
            }

            $name = $element->localName;
            if (!in_array($name, self::$allowedElements, true)) {
                if ($element === $dom->documentElement) {
                    $error = 'svg_element';
                    return false;
                }
                $element->parentNode->removeChild($element);
                continue;
            }

            $remove = array();
            foreach ($element->attributes as $attribute) {
                $attrName = $attribute->localName;
                $rawName = strtolower($attribute->nodeName);
                $value = trim((string)$attribute->nodeValue);

                if (strpos($rawName, 'on') === 0 || $rawName === 'style') {
                    $remove[] = $attribute->nodeName;
                    continue;
                }

                if (!in_array($attrName, self::$allowedAttributes, true)) {
                    $remove[] = $attribute->nodeName;
                    continue;
                }

                if ($attrName === 'href') {
                    if ($value === '' || $value[0] !== '#' || preg_match('/[\x00-\x20]/', $value)) {
                        $remove[] = $attribute->nodeName;
                    }
                    continue;
                }

                if (preg_match('/(?:javascript|vbscript|data|file|https?|ftp)\s*:/i', $value)) {
                    $remove[] = $attribute->nodeName;
                    continue;
                }

                if (stripos($value, 'url(') !== false && !preg_match('/^url\(\s*["\']?#[A-Za-z_][A-Za-z0-9_.:-]*["\']?\s*\)$/', $value)) {
                    $remove[] = $attribute->nodeName;
                }
            }

            foreach ($remove as $attributeName) {
                $element->removeAttribute($attributeName);
            }
        }

        // Strip processing instructions and comments so no hidden external processing remains.
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//processing-instruction() | //comment()') as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }

        $sanitized = $dom->saveXML($dom->documentElement);
        if (!is_string($sanitized) || $sanitized === '' || strlen($sanitized) > self::MAX_BYTES) {
            $error = 'svg_output';
            return false;
        }

        return $sanitized;
    }

    public static function sanitizeToFile($source, $target, &$error = '') {
        $sanitized = self::sanitizeFile($source, $error);
        if ($sanitized === false) {
            return false;
        }

        $directory = dirname($target);
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            $error = 'svg_directory';
            return false;
        }

        try {
            $random = bin2hex(random_bytes(8));
        } catch (\Throwable $e) {
            $random = str_replace('.', '', uniqid('', true));
        }

        $temporary = $directory . DIRECTORY_SEPARATOR . '.codecart-svg-' . $random . '.tmp';
        if (@file_put_contents($temporary, $sanitized, LOCK_EX) === false) {
            $error = 'svg_write';
            return false;
        }

        @chmod($temporary, 0644);
        if (is_file($target) && !@unlink($target)) {
            @unlink($temporary);
            $error = 'svg_replace';
            return false;
        }
        if (!@rename($temporary, $target)) {
            @unlink($temporary);
            $error = 'svg_replace';
            return false;
        }

        @chmod($target, 0644);
        return true;
    }
}
