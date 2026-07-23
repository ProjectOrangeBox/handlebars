<?php

/*
Format a number with grouped thousands. Control the output with hash arguments:
decimals (default 0), dec_point (default "."), thousands_sep (default ",").

{{format:number total decimals=2}}
{{format:number total decimals=2 thousands_sep=" "}}
*/
$helpers['format:number'] = function ($number, $options) {
    $decimals = (int) ($options['hash']['decimals'] ?? 0);
    $decPoint = $options['hash']['dec_point'] ?? '.';
    $thousands = $options['hash']['thousands_sep'] ?? ',';

    return number_format((float) $number, $decimals, $decPoint, $thousands);
};
