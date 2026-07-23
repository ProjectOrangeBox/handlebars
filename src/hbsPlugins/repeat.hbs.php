<?php

/*
Block helper. Renders its block N times. Inside the block {{index}} (0-based) and
{{number}} (1-based) are available.

{{#repeat 3}}<span>{{number}}</span>{{/repeat}}
*/
$helpers['repeat'] = function ($times, $options) {
    $out = '';
    $times = (int) $times;

    for ($i = 0; $i < $times; $i++) {
        $context = is_array($options['_this']) ? $options['_this'] : [];
        $context['index'] = $i;
        $context['number'] = $i + 1;

        $out .= $options['fn']($context);
    }

    return $out;
};
