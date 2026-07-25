<?php

declare(strict_types=1);

error_reporting(E_ALL);

define('__ROOT__', realpath(__DIR__ . '/../'));
define('__WWW__', realpath(__DIR__ . '/../htdocs'));

if (!defined('DEBUG')) {
    define('DEBUG', true);
}

if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'testing');
}

// Composer autoloader (LightnCandy, orange\framework, orange\handlebars, ...).
// The vendor/bin/phpunit proxy exposes the autoload path in this global.
$autoload = $GLOBALS['_composer_autoload_path'] ?? realpath(__DIR__ . '/../../../autoload.php');
require $autoload;

// The framework's global helpers (logMsg, file_put_contents_atomic, ...) are
// normally loaded at runtime by Application::preContainer() via dynamic
// include_once, not through composer's autoloader. Handlebars and
// HandlebarsPluginCacher use file_put_contents_atomic, so load them here.
$frameworkHelpers = __DIR__ . '/../../framework/src/helpers';

foreach (['helpers.php', 'errors.php', 'wrappers.php'] as $helperFile) {
    $path = $frameworkHelpers . '/' . $helperFile;

    if (is_file($path)) {
        require $path;
    }
}

