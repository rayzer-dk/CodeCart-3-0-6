<?php
namespace CodeCart\Core;
final class CoreContracts {
    public const LEGACY_API = '3.x';
    public const MODERN_API = '1';
    public static function legacy(): array { return array('mvc-l','registry','loader','routes','ocmod','events','jquery-3.7.1','bootstrap-3','font-awesome-4'); }
    public static function modern(): array { return array('psr-4','services','repositories','manifest','migrations','queue','scheduler','extension-points','api','api-v1','webhooks','assets','compatibility-layer','search-adapter'); }
}
