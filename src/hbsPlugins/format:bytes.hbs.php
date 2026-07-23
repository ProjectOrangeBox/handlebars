<?php

/*
Format a byte count as a human-readable size. Override decimal places with the
"precision" hash argument (default 1).

{{format:bytes file_size}}
{{format:bytes file_size precision=2}}
*/
$helpers['format:bytes'] = function ($bytes, $options) {
    $bytes = (float) $bytes;
    $precision = (int) ($options['hash']['precision'] ?? 1);
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
};
