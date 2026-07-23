<?php

declare(strict_types=1);

/**
 * Runnable tour of orange/handlebars.
 *
 *   php examples/example.php
 *
 * Standalone: only needs the composer autoloader plus the framework's global
 * helpers (file_put_contents_atomic), which the app normally loads at runtime.
 */

// --- locate the composer autoloader (works installed or in a monorepo) -------
$autoloads = [
    __DIR__ . '/../vendor/autoload.php',   // installed standalone
    __DIR__ . '/../../../autoload.php',    // inside a vendor/ tree
];

foreach ($autoloads as $autoload) {
    if (is_file($autoload)) {
        require $autoload;
        break;
    }
}

if (!class_exists(\orange\handlebars\Handlebars::class)) {
    fwrite(STDERR, "Could not find the composer autoloader. Run `composer install` first.\n");
    exit(1);
}

// --- constants + framework global helpers ------------------------------------
defined('__ROOT__') || define('__ROOT__', dirname(__DIR__));
defined('DEBUG') || define('DEBUG', true);

foreach (['helpers', 'errors', 'wrappers'] as $helper) {
    foreach ([
        __DIR__ . '/../../framework/src/helpers/' . $helper . '.php',
        __DIR__ . '/../vendor/orange/framework/src/helpers/' . $helper . '.php',
    ] as $path) {
        if (is_file($path)) {
            require_once $path;
            break;
        }
    }
}

use orange\handlebars\Handlebars;

// --- boot --------------------------------------------------------------------
$cache = sys_get_temp_dir() . '/handlebars-example';
is_dir($cache) || mkdir($cache, 0777, true);

$pluginDir = __DIR__ . '/../src/hbsPlugins/';
$plugins = [
    'default', 'json', 'join', 'length', 'truncate', 'capitalize',
    'format:number', 'format:bytes', 'format:date', 'contains', 'repeat',
    'url_encode', 'if_gt', 'uppercase',
];

$hb = new Handlebars([
    'cache directory' => $cache,
    'forceCompile'    => true,
    'templates'       => ['welcome' => __DIR__ . '/views/welcome.hbs'],
    'partials'        => [
        'header' => __DIR__ . '/views/partials/header.hbs',
        'footer' => __DIR__ . '/views/partials/footer.hbs',
    ],
    'helpers'         => array_map(fn ($n) => $pluginDir . $n . '.hbs.php', $plugins),
]);

function section(string $title): void
{
    echo "\n\033[1m" . $title . "\033[0m\n" . str_repeat('-', strlen($title)) . "\n";
}

// --- 1. strings --------------------------------------------------------------
section('renderString');
echo $hb->renderString('Hello {{name}}!', ['name' => 'Ada']) . "\n";
echo $hb->renderString('escaped: {{v}} | raw: {{{v}}}', ['v' => '<b>']) . "\n";

// --- 2. helpers --------------------------------------------------------------
section('helpers');
echo $hb->renderString('{{default title "Untitled"}}', ['title' => '']) . "\n";
echo $hb->renderString('{{join tags ", "}}', ['tags' => ['php', 'handlebars', 'orange']]) . "\n";
echo $hb->renderString('{{truncate body 20}}', ['body' => 'The quick brown fox jumps over the lazy dog']) . "\n";
echo $hb->renderString('{{format:number n decimals=2}} / {{format:bytes b}}', ['n' => 1234567.891, 'b' => 5242880]) . "\n";
echo $hb->renderString('{{#repeat 5}}{{number}} {{/repeat}}', []) . "\n";
echo $hb->renderString('{{#contains roles "admin"}}admin{{else}}guest{{/contains}}', ['roles' => ['editor', 'admin']]) . "\n";
echo $hb->renderString('{{json user}}', ['user' => ['id' => 7, 'name' => 'Grace']]) . "\n";

// --- 3. a file template with partials ---------------------------------------
section('render (file template + partials + helpers)');
echo $hb->render('welcome', [
    'name'        => 'grace',
    'breadcrumbs' => ['Home', 'Reports'],
    'items'       => ['alpha', 'beta', 'gamma'],
    'score'       => 87.5,
    'now'         => time(),
    'bytes'       => 20480,
]) . "\n";

echo "\n(cache directory: {$cache})\n";
