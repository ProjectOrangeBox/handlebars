<?php

/*
Join an array into a string with the given glue (glue is required).

{{join tags ", "}}
*/
$helpers['join'] = (fn($array, $glue, $options) => is_array($array) ? implode((string) $glue, $array) : '');
