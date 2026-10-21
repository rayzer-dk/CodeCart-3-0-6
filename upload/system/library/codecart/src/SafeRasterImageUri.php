<?php
namespace CodeCart\Core;

final class SafeRasterImageUri extends \HTMLPurifier_AttrDef_URI {
    public function validate($string, $config, $context) {
        $uri = $this->parser->parse($this->parseCDATA($string));
        if (!$uri || $uri->scheme !== 'data') { return parent::validate($string, $config, $context); }
        $allowed = $config->get('URI.AllowedSchemes');
        if ($config->get('URI.Disable') || !isset($allowed['data'])) { return false; }
        if (!preg_match('#^(image/(?:png|gif|jpe?g|webp|avif));base64,(.+)$#is', $uri->path, $parts)
            || base64_decode($parts[2], true) === false) { return false; }
        $mime = strtolower($parts[1]);
        $uri->path = ($mime === 'image/jpg' ? 'image/jpeg' : $mime) . ';base64,' . $parts[2];
        $context->register('EmbeddedURI', $this->embedsResource);
        try {
            if (!$uri->validate($config, $context)) { return false; }
            $definition = $config->getDefinition('URI');
            if (!$definition->filter($uri, $config, $context)) { return false; }
            if ($uri->scheme === 'data') {
                $scheme = new \HTMLPurifier_URIScheme_data();
                $scheme->allowed_types['image/webp'] = true;
                $scheme->allowed_types['image/avif'] = true;
            } else {
                $scheme = $uri->getSchemeObj($config, $context);
            }
            if (!$scheme || !$scheme->browsable) { return false; }
            // The vendor image sniffer emits warnings for malformed user input.
            if (!@$scheme->validate($uri, $config, $context) || !$definition->postFilter($uri, $config, $context)) { return false; }
            return $uri->toString();
        } finally {
            $context->destroy('EmbeddedURI');
        }
    }
}
