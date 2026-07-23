<?php

/*
<div class="date">Posted on {{format:date entry_date format="Y-m-d H:i:s"}}</div>
*/
$helpers['format:date'] = (fn($arg1, $options) => date($options['hash']['format'], $arg1));
