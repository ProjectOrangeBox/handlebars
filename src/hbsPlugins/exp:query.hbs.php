<?php

$helpers['exp:query'] = function ($options) {
    $output = '';
    $index = 1;

    $results = ci()->db->query($options['hash']['sql']);

    $number_rows = $results->num_rows();

    while ($row = $results->unbuffered_row('array')) {
        $row['query_num_rows'] = $number_rows;
        $row['query_first_row'] = ($index == 1);
        $row['query_last_row'] = ($index == $number_rows);
        $row['query_odd'] = !($index % 2 == 0);
        $row['query_even'] = ($index % 2 == 0);

        /* increases this last */
        $row['query_index_row'] = $index++;

        $output .= $options['fn']($row);
    }

    return $output;
};
