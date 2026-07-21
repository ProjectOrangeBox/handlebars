<?php

use LightnCandy\LightnCandy;

return [
    'cache directory' => __ROOT__ . '/var/handlebars',
    'templates' => [],
    'partials' => [],
    'forceCompile' => DEBUG,
    'hbCachePrefix' => 'hbs.',
    'extension' => '.hbs',
    'delimiters' => ['{{', '}}'],
    'helpers' => [],
    // lightncandy handlebars compiler flags https://github.com/zordius/lightncandy#compile-options */
    'flags' => LightnCandy::FLAG_ERROR_EXCEPTION | LightnCandy::FLAG_HANDLEBARS | LightnCandy::FLAG_HANDLEBARSJS | LightnCandy::FLAG_RUNTIMEPARTIAL,
];
