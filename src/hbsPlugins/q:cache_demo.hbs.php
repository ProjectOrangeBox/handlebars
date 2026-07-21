<?php

$helpers['q:cache_demo'] = function ($options) {
    if (!$html = HBCache::get($options)) {
        $html = $options['fn']($options['_this']) . PHP_EOL;
        $html .= 'Cached on: ' . date('Y-m-d H:i:s') . PHP_EOL;
        $html .= 'For ' . $options['hash']['cache'] . ' Minutes' . PHP_EOL;
        $html .= 'At ' . date('Y-m-d H:i:s', strtotime('+' . (int)$options['hash']['cache'] . ' minutes'));

        HBCache::set($options, $html);
    }

    return $html;
};
