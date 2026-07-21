<?php

/*
{{#if_gt page_title 10}}
    True Do This
{{else}}
    False Do This
{{/if_gt}}
*/
$helpers['if_gt'] = function ($value1, $value2, $options) {
    if ($value1 > $value2) {
        $return = $options['fn']();
    } elseif ($options['inverse'] instanceof \Closure) {
        $return = $options['inverse']();
    }

    return $return;
};
