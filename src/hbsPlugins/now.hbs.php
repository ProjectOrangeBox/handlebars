<?php

/*
<div class="date">Posted on {{date:now}}</div>
*/
$helpers['now'] = function () {
    return 'The current time is ' . date('H:i:s');
};
