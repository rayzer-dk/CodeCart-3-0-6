<?php
namespace CodeCart\Core;

/**
 * Compact storefront purchase-information blocks.
 * Storage is additive and independent from native OpenCart option/attribute tables.
 */
final class PurchaseBlocks {
    const MAX_BLOCKS = 12;
    const MAX_ITEMS = 50;
    const MAX_COLUMNS = 8;
    const MAX_ROWS = 50;

    public static function decode($json) {
        if (is_array($json)) { return self::sanitize($json); }
        // OpenCart Request::clean() HTML-escapes POST scalar values, therefore JSON
        // arrives as [{&quot;type&quot;:...}]. Decode entities before json_decode or
        // every builder payload is silently reduced to an empty array on save.
        $raw = html_entity_decode((string)$json, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $data = json_decode($raw, true);
        return is_array($data) ? self::sanitize($data) : array();
    }

    public static function encode($value) {
        return json_encode(self::decode($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function sanitize($blocks) {
        if (!is_array($blocks)) { return array(); }
        $out = array();
        foreach ($blocks as $block) {
            if (count($out) >= self::MAX_BLOCKS || !is_array($block)) { break; }
            $type = isset($block['type']) ? strtolower(trim((string)$block['type'])) : 'info';
            if (!in_array($type, array('info','size_table','sizes','colors','form'), true)) { $type = 'info'; }
            $clean = array('type'=>$type, 'title'=>self::text(isset($block['title']) ? $block['title'] : '', 128), 'enabled'=>(!isset($block['enabled']) || (int)$block['enabled'] ? 1 : 0));
            if ($type === 'info') {
                $clean['content'] = SafeRichHtml::sanitize(substr((string)(isset($block['content']) ? $block['content'] : ''), 0, 100000));
            } elseif ($type === 'form') {
                $clean['form_id'] = max(0, (int)(isset($block['form_id']) ? $block['form_id'] : 0));
                // A form block without a selected form renders nothing; do not persist it.
                if ($clean['form_id'] < 1) { continue; }
                $display = isset($block['display']) ? strtolower(trim((string)$block['display'])) : 'inline';
                $clean['display'] = in_array($display, array('inline','button'), true) ? $display : 'inline';
                $clean['button_text'] = self::text(isset($block['button_text']) ? $block['button_text'] : '', 100);
                $icon = isset($block['button_icon']) ? trim((string)$block['button_icon']) : '';
                // Same class grammar as the form-level icon (FA4 "fa-x" and FA6 "fa-solid fa-x"),
                // otherwise an icon chosen in the picker was silently dropped on save.
                $icon = strtolower(trim(preg_replace('/\s+/', ' ', $icon)));
                $clean['button_icon'] = (strlen($icon) <= 64 && preg_match('/^(?:fa(?:-[a-z]+)?\s+)?fa-[a-z0-9-]+(?:\s+fa-[a-z0-9-]+)*$/', $icon)) ? $icon : '';
                foreach (array('button_bg','button_text_color','button_hover_bg') as $color_key) {
                    $color = isset($block[$color_key]) ? strtolower(trim((string)$block[$color_key])) : '';
                    $clean[$color_key] = preg_match('/^#[0-9a-f]{6}$/', $color) ? $color : '';
                }
            } elseif ($type === 'size_table') {
                $columns = isset($block['columns']) && is_array($block['columns']) ? $block['columns'] : array();
                $clean['columns'] = array();
                foreach (array_slice($columns, 0, self::MAX_COLUMNS) as $column) { $clean['columns'][] = self::text($column, 80); }
                if (!$clean['columns']) { $clean['columns'] = array(''); }
                $rows = isset($block['rows']) && is_array($block['rows']) ? $block['rows'] : array();
                $clean['rows'] = array();
                foreach (array_slice($rows, 0, self::MAX_ROWS) as $row) {
                    if (!is_array($row)) { continue; }
                    $clean_row = array();
                    for ($i=0; $i<count($clean['columns']); $i++) { $clean_row[] = self::text(isset($row[$i]) ? $row[$i] : '', 128); }
                    $clean['rows'][] = $clean_row;
                }
            } else {
                $items = isset($block['items']) && is_array($block['items']) ? $block['items'] : array();
                $clean['items'] = array();
                foreach (array_slice($items, 0, self::MAX_ITEMS) as $item) {
                    if (!is_array($item)) { continue; }
                    $label = self::text(isset($item['label']) ? $item['label'] : '', 80);
                    $value = self::text(isset($item['value']) ? $item['value'] : '', 80);
                    $swatch = '';
                    if ($type === 'colors' && preg_match('/^#?[0-9a-fA-F]{6}$/', $value)) { $value = '#' . ltrim(strtolower($value), '#'); $swatch = $value; }
                    if ($label !== '' || $value !== '') {
                        $clean_item = array('label'=>$label, 'value'=>$value);
                        if ($swatch !== '') { $clean_item['swatch'] = $swatch; }
                        $clean['items'][] = $clean_item;
                    }
                }
            }
            $out[] = $clean;
        }
        return $out;
    }

    private static function text($value, $limit) {
        $value = strip_tags(html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8'));
        $value = trim(preg_replace('/\\s+/u', ' ', $value));
        return utf8_substr($value, 0, (int)$limit);
    }
}
