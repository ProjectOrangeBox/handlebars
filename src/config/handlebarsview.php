<?php

/**
 * Defaults for orange\handlebars\HandlebarsView.
 *
 * The filename is not free. ConfigurationTrait::determineConfigPath() derives it
 * from the lowercased short class name, so HandlebarsView looks for
 * "handlebarsview.php" - and without this file every HandlebarsView::getInstance()
 * died with ConfigFileNotFound before it reached a single line of its own
 * constructor.
 *
 * handlebars.php next door holds the parser's defaults and is loaded directly by
 * Handlebars::__construct(), so it cannot simply be renamed. This file is that
 * array plus the keys ViewAbstract requires, which keeps one source of truth for
 * the parser settings and leaves the two unable to drift apart.
 */

declare(strict_types=1);

return array_replace(require __DIR__ . '/handlebars.php', [
    // where to look for .hbs files - the app supplies these
    'view paths' => [],
    'default view paths' => [],
    // ViewAbstract compiles string templates here; the handlebars cache is
    // separate and lives under 'cache directory' in handlebars.php
    'temp directory' => sys_get_temp_dir(),
    'debug' => DEBUG,
    'sub path size' => 6,
]);
