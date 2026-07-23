<?php

/*
Uppercase the first character of a string.

{{capitalize word}}
*/
$helpers['capitalize'] = (fn($value, $options) => ucfirst((string) $value));
