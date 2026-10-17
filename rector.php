<?php
declare(strict_types=1);

use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\Config\RectorConfig;

// No PHP-version upgrade rules: CodeCart must support PHP 8.1-8.5.
// Only project-owned MVC sources, not language packs or bundled vendors.
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/upload/admin/controller',
        __DIR__ . '/upload/admin/model',
        __DIR__ . '/upload/catalog/controller',
        __DIR__ . '/upload/catalog/model',
        __DIR__ . '/upload/system/engine',
        __DIR__ . '/upload/system/library',
    ])
    ->withRules([ExplicitBoolCompareRector::class])
    ->withCache(__DIR__ . '/build/rector');
