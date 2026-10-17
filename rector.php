<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\Config\RectorConfig;

// Preserve PHP 8.1–8.5 compatibility; never rewrite vendor, translations or OCMOD.
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/upload/admin/controller',
        __DIR__ . '/upload/admin/model',
        __DIR__ . '/upload/catalog/controller',
        __DIR__ . '/upload/catalog/model',
        __DIR__ . '/upload/system/engine',
        __DIR__ . '/upload/system/library',
    ])
    ->withRules([SimplifyIfReturnBoolRector::class])
    ->withCache(__DIR__ . '/build/rector');
