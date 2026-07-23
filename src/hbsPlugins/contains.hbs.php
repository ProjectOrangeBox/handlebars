<?php

/*
Block helper. Renders the block when needle is in haystack - haystack may be an
array (in_array) or a string (substring match).

{{#contains user.roles "admin"}}
    Show admin tools
{{else}}
    Regular user
{{/contains}}
*/
$helpers['contains'] = function ($haystack, $needle, $options) {
    $found = is_array($haystack)
        ? in_array($needle, $haystack)
        : str_contains((string) $haystack, (string) $needle);

    if ($found) {
        return $options['fn']($options['_this']);
    }

    return ($options['inverse'] instanceof \Closure) ? $options['inverse']($options['_this']) : '';
};
