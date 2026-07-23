<?php

/* block helper: {{#shout}}text{{/shout}} -> TEXT */
$helpers['shout'] = function ($options) {
    return strtoupper($options['fn']($options['_this']));
};
