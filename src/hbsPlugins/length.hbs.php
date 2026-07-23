<?php

/*
Count the items in an array/Countable, or the characters in a string.

You have {{length items}} item(s).
*/
$helpers['length'] = function ($value, $options) {
    if (is_countable($value)) {
        return count($value);
    }

    return is_string($value) ? mb_strlen($value) : 0;
};
