<?php

/*
Join an array into a string with the given glue (glue is required).

{{join tags ", "}}
*/
$helpers['join'] = function ($array, $glue, $options) {
    return is_array($array) ? implode((string) $glue, $array) : '';
};
