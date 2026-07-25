<?php

/*
Output a fallback when a value is null or an empty string.

{{default page_title "Untitled"}}
*/
$helpers['default'] = function ($value, $fallback, $options) {
    return ($value === null || $value === '') ? $fallback : $value;
};
