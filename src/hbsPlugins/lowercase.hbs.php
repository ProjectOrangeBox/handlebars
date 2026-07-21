<?php

/*
if (!$output = ci()->handlebars->cache($options)) {
    $output = strtolower($options['fn']($options['_this']));

    ci()->handlebars->cache($options,$output);
}
*/
$helpers['exp:lowercase'] = function ($options) {
    return strtolower($options['fn']($options['_this']));
};
