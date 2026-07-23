<?php

/*
Output a fallback when a value is null or an empty string.

{{default page_title "Untitled"}}
*/
$helpers['default'] = (fn($value, $fallback, $options) => ($value === null || $value === '') ? $fallback : $value);
