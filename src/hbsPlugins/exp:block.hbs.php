<?php

$helpers['exp:block'] = function ($options) {
    return $options['fn']($options['_this']); /* parse inter block content */
};
