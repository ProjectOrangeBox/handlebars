<?php

/*
Convert newlines to <br> tags. The text is HTML-escaped first, then returned as
a SafeString, so it is safe to use with {{ }} (no double escaping, no XSS).

{{nl2br comment}}
*/
$helpers['nl2br'] = (fn($text, $options) => new \LightnCandy\SafeString(nl2br(htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'))));
