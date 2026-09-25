<?php
namespace CodeCart\Core;

final class SafeRichHtml {
    private const BLOCKED_CONTAINER_TAGS = array('script','style','object','applet','frameset','frame','xml','form','textarea','select','button');
    private const BLOCKED_SINGLE_TAGS = array('base','meta','link','embed','input','option');
    private const URI_ATTRIBUTES = array('href','src','poster','action','formaction','xlink:href');

    public static function sanitize($html) {
        $html = (string)$html;
        if ($html === '' || trim($html) === '') { return $html; }

        foreach (self::BLOCKED_CONTAINER_TAGS as $tag) {
            $quoted = preg_quote($tag, '#');
            $html = preg_replace('#<' . $quoted . '\b[^>]*>.*?</' . $quoted . '\s*>#is', '', $html);
        }
        foreach (self::BLOCKED_SINGLE_TAGS as $tag) {
            $html = preg_replace('#<' . preg_quote($tag, '#') . '\b[^>]*?/?>#is', '', $html);
        }

        // Iframe is permitted only for explicitly trusted media providers.
        $html = preg_replace_callback('#<iframe\b([^>]*)>(.*?)</iframe\s*>#is', function($match) {
            $attrs = self::sanitizeAttributes((string)$match[1], 'iframe');
            $src = self::extractAttribute($attrs, 'src');
            if ($src === '' || !self::safeIframe($src)) { return ''; }
            return '<iframe' . $attrs . '>' . (string)$match[2] . '</iframe>';
        }, $html);

        $html = preg_replace_callback('#<([a-zA-Z][a-zA-Z0-9:-]*)(\s[^<>]*?)?(/?)>#s', function($match) {
            $tag = strtolower($match[1]);
            $attrs = isset($match[2]) ? (string)$match[2] : '';
            $slash = isset($match[3]) ? $match[3] : '';

            if (in_array($tag, self::BLOCKED_CONTAINER_TAGS, true) || in_array($tag, self::BLOCKED_SINGLE_TAGS, true)) {
                return '';
            }

            $attrs = self::sanitizeAttributes($attrs, $tag);
            if ($tag === 'iframe') {
                $src = self::extractAttribute($attrs, 'src');
                if ($src === '' || !self::safeIframe($src)) { return ''; }
            }

            return '<' . $match[1] . $attrs . $slash . '>';
        }, $html);

        return $html;
    }

    private static function sanitizeAttributes($attrs, $tag) {
        if ($attrs === '') { return ''; }

        $attrs = preg_replace('/\s+on[a-z0-9_:-]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $attrs);
        $attrs = preg_replace('/\s+srcdoc\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $attrs);

        $attrs = preg_replace_callback('/\s+(href|src|poster|action|formaction|xlink:href)\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', function($m) use ($tag) {
            $name = strtolower($m[1]);
            $value = isset($m[3]) && $m[3] !== '' ? $m[3] : (isset($m[4]) && $m[4] !== '' ? $m[4] : (isset($m[5]) ? $m[5] : ''));
            $decoded = html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (!self::safeUri($decoded, $tag, $name)) { return ''; }
            return $m[0];
        }, $attrs);

        $attrs = preg_replace_callback('/\s+style\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', function($m) {
            $value = isset($m[2]) && $m[2] !== '' ? $m[2] : (isset($m[3]) && $m[3] !== '' ? $m[3] : (isset($m[4]) ? $m[4] : ''));
            $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (preg_match('/(?:expression\s*\(|javascript\s*:|vbscript\s*:|behavior\s*:|-moz-binding\s*:|@import)/i', $decoded)) { return ''; }
            return $m[0];
        }, $attrs);

        if ($tag === 'a' && preg_match('/\s+target\s*=\s*("_blank"|\'_blank\'|_blank)(?:\s|$)/i', $attrs)) {
            if (preg_match('/\s+rel\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $attrs, $relMatch)) {
                $relValue = isset($relMatch[2]) && $relMatch[2] !== '' ? $relMatch[2] : (isset($relMatch[3]) && $relMatch[3] !== '' ? $relMatch[3] : (isset($relMatch[4]) ? $relMatch[4] : ''));
                $tokens = preg_split('/\s+/', trim($relValue), -1, PREG_SPLIT_NO_EMPTY);
                foreach (array('noopener','noreferrer') as $token) { if (!in_array($token, $tokens, true)) { $tokens[] = $token; } }
                $replacement = ' rel="' . implode(' ', array_unique($tokens)) . '"';
                $attrs = preg_replace('/\s+rel\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', $replacement, $attrs, 1);
            } else {
                $attrs .= ' rel="noopener noreferrer"';
            }
        }

        return $attrs;
    }

    private static function extractAttribute($attrs, $name) {
        if (!preg_match('/\s+' . preg_quote($name, '/') . '\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $attrs, $m)) { return ''; }
        $value = isset($m[2]) && $m[2] !== '' ? $m[2] : (isset($m[3]) && $m[3] !== '' ? $m[3] : (isset($m[4]) ? $m[4] : ''));
        return html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private static function safeUri($value, $tag, $attribute) {
        if ($value === '') { return true; }
        $normalized = preg_replace('/[\x00-\x20]+/', '', $value);
        if ($normalized === '' || $normalized[0] === '#' || $normalized[0] === '/' || strpos($normalized, './') === 0 || strpos($normalized, '../') === 0) { return true; }
        if ($tag === 'img' && $attribute === 'src' && preg_match('#^data:image/(?:png|gif|jpe?g|webp|avif);base64,#i', $normalized)) { return true; }
        $scheme = strtolower((string)parse_url($normalized, PHP_URL_SCHEME));
        if ($scheme === '') { return true; }
        return in_array($scheme, array('http','https','mailto','tel'), true);
    }

    private static function safeIframe($src) {
        $normalized = preg_replace('/[\x00-\x20]+/', '', html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (!preg_match('#^https?://#i', $normalized)) { return false; }
        $host = strtolower((string)parse_url($normalized, PHP_URL_HOST));
        $allowed = array('youtube.com','www.youtube.com','youtube-nocookie.com','www.youtube-nocookie.com','youtu.be','player.vimeo.com','vimeo.com','www.facebook.com','facebook.com','www.instagram.com','instagram.com','www.dailymotion.com','dailymotion.com','drive.google.com');
        return in_array($host, $allowed, true);
    }
}
