<?php
namespace CodeCart\Core;

final class SafeRichHtml {
    private static $purifier;

    public static function sanitize($html, bool $encoded = true) {
        $html = (string)$html;
        if ($html === '' || trim($html) === '') { return $html; }

        // Native description fields use Request::clean() on input and one
        // html_entity_decode() at output. JSON blocks/forms use raw HTML instead.
        if ($encoded) { $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'); }
        $html = self::purifier()->purify($html);
        return $encoded ? htmlspecialchars($html, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8') : $html;
    }

    private static function purifier(): \HTMLPurifier {
        if (self::$purifier === null) {
            require_once dirname(__DIR__, 3) . '/helper/HTMLPurifierauto.php';
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('Core.Encoding', 'UTF-8');
            $config->set('Cache.DefinitionImpl', null);
            $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
            $config->set('HTML.SafeIframe', true);
            $config->set('URI.SafeIframeRegexp', '#^https?://(?:www\.)?(?:youtube\.com|youtube-nocookie\.com|youtu\.be|player\.vimeo\.com|vimeo\.com|facebook\.com|instagram\.com|dailymotion\.com|drive\.google\.com)(?:/|$)#i');
            $config->set('URI.AllowedSchemes', array('http' => true, 'https' => true, 'mailto' => true, 'tel' => true, 'data' => true));
            $config->set('Attr.EnableID', true);
            $config->set('Attr.AllowedFrameTargets', array('_blank', '_self', '_parent', '_top'));
            $config->set('HTML.TargetNoopener', true);
            $config->set('HTML.TargetNoreferrer', true);
            $definition = $config->getHTMLDefinition(true);
            $definition->addAttribute('img', 'src', new SafeRasterImageUri(true));
            $definition->addAttribute('iframe', 'allowfullscreen', 'Bool');
            $definition->addAttribute('iframe', 'allow', 'Text');
            foreach (array('img', 'iframe') as $tag) { $definition->addAttribute($tag, 'loading', 'Enum#lazy,eager'); }
            foreach (array('section', 'article', 'figure', 'figcaption') as $tag) { $definition->addElement($tag, 'Block', 'Flow', 'Common'); }
            $definition->addElement('video', 'Block', 'Flow', 'Common', array('src' => 'URI#embedded', 'poster' => 'URI#embedded', 'width' => 'Length', 'height' => 'Length', 'controls' => 'Bool', 'autoplay' => 'Bool', 'muted' => 'Bool', 'loop' => 'Bool', 'preload' => 'Enum#none,metadata,auto'));
            $definition->addElement('audio', 'Block', 'Flow', 'Common', array('src' => 'URI#embedded', 'controls' => 'Bool', 'preload' => 'Enum#none,metadata,auto'));
            $definition->addElement('source', 'Block', 'Empty', null, array('src' => 'URI#embedded', 'type' => 'Text'));
            self::$purifier = new \HTMLPurifier($config);
        }
        return self::$purifier;
    }
}
