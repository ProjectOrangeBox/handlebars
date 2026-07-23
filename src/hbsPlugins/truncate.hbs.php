<?php

/*
Truncate a string to a maximum length, appending an ellipsis when cut.
Override the suffix with the "ellipsis" hash argument.

{{truncate summary 120}}
{{truncate summary 120 ellipsis=" [more]"}}
*/
$helpers['truncate'] = function ($text, $length, $options) {
    $text = (string) $text;
    $length = (int) $length;
    $ellipsis = $options['hash']['ellipsis'] ?? '…';

    if (mb_strlen($text) <= $length) {
        return $text;
    }

    return rtrim(mb_substr($text, 0, $length)) . $ellipsis;
};
