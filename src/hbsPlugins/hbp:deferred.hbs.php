<?php

$helpers['hbp:deferred'] = function ($options) {
    if (isset($options['hash']['id'])) {
        return new \LightnCandy\SafeString('<i id="' . $options['hash']['id'] . '"></i>');
    } else {
        return new \LightnCandy\SafeString(ci('output')->injector(true));
    }
};
