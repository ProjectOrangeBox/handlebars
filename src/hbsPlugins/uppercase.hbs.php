<?php

/*
if (!$output = ci()->handlebars->cache($options)) {
    $output = strtoupper($options['fn']($options['_this']));

    ci()->handlebars->cache($options,$output);
}
*/
$helpers['exp:uppercase'] = function ($options) {
    return strtoupper($options['fn']($options['_this']));
};
