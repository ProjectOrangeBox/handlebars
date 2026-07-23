<?php

/* value helper: {{exclaim name}} -> name! */
$helpers['exclaim'] = function ($value, $options) {
    return $value . '!';
};
