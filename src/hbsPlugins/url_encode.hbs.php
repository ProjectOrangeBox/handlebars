<?php

/*
URL-encode a value (RFC 3986) for safe use in a query string or path segment.

<a href="/search?q={{url_encode query}}">Search</a>
*/
$helpers['url_encode'] = (fn($value, $options) => rawurlencode((string) $value));
