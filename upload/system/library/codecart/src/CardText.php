<?php
namespace CodeCart\Core;

final class CardText {
    public static function excerpt($html, $limit, $suffix = '..') {
        $decoded = html_entity_decode((string)$html, ENT_QUOTES, 'UTF-8');
        // Full descriptions may start with a repeated heading and an anchor-only
        // table of contents. Keep them on the full page, but not in product cards.
        $decoded = preg_replace('/<h[1-6]\b[^>]*>.*?<\/h[1-6]>/isu', ' ', $decoded);
        $decoded = preg_replace_callback('/<p\b[^>]*>(.*?)<\/p>/isu', static function ($match) {
            return substr_count($match[1], 'href="#') >= 2 || substr_count($match[1], "href='#") >= 2 ? ' ' : $match[0];
        }, $decoded);
        $decoded = preg_replace('/<\s*\/?(?:p|div|br|li|tr|td|th)\b[^>]*>/iu', ' ', $decoded);
        $plain = strip_tags($decoded);
        $plain = trim(preg_replace('/\s+/u', ' ', $plain));
        $limit = max(0, (int)$limit);
        if ($plain === '' || $limit === 0) { return ''; }
        if (utf8_strlen($plain) <= $limit) { return $plain; }
        return rtrim(utf8_substr($plain, 0, $limit)) . (string)$suffix;
    }
}
