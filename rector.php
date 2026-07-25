<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    // LightnCandy compiles a helper by extracting its *source text* through
    // reflection, and Exporter::closure()'s regex only recognises the `function`
    // keyword with a { } body. An arrow function is copied verbatim - assignment
    // and trailing semicolon included - into the generated template, which then
    // fails to parse. So ClosureToArrowFunctionRector must not touch these.
    ->withSkip([
        __DIR__ . '/src/hbsPlugins',
    ])
    ->withSets([
        LevelSetList::UP_TO_PHP_84,
    ]);
