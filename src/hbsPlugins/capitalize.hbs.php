<?php

/*
Uppercase the first character of a string.

{{capitalize word}}
*/
$helpers['capitalize'] = function ($value, $options) {
    return ucfirst((string) $value);
};
