<?php

/*
Encode a value as JSON. Returns a SafeString so the JSON is not HTML-escaped -
intended for use inside a <script> block, not an HTML attribute.

<script>var data = {{json user}};</script>
{{json user pretty=true}}
*/
$helpers['json'] = function ($value, $options) {
    $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    if (!empty($options['hash']['pretty'])) {
        $flags |= JSON_PRETTY_PRINT;
    }

    return new \LightnCandy\SafeString(json_encode($value, $flags));
};
