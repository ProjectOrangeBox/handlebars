<?php

/*
{{#exp:channel:entries channel="news" limit="15" category="2" entry_id="147"}}

<h3>The title is "{{title}}"</h3>

<p>The body is "{{body}}"</p>

<div class="date">Posted on {{format:date entry_date format="Y-m-d H:i:s"}}</div>

{{/exp:channel:entries}}
*/
$helpers['exp:channel:entries'] = function ($options) use (&$in) {
    // channel="news" limit="15" category="2" entry_id="147"
    $channel = $options['hash']['channel'];
    $limit = $options['hash']['limit'];
    $category = $options['hash']['category'];
    $entry_id = $options['hash']['entry_id'];

    $in['title'] = 'This is the title';
    $in['body'] = 'This is the body';
    $in['entry_date'] = date('U');

    return $options['fn']($in);
};
