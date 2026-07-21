<?php

/*
$in is a reference to the data array sent in

{{set age="28" name=page_title food="pizza" }}
*/
$helpers['set'] = function ($options) use (&$in) {
    //$in[$options['hash']['name']] = $options['hash']['value'];

    $in = array_replace($in, $options['hash']);

    return '';
};
