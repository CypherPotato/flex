<?php

function interpolate_query($query, $params)
{
    $keys = array();
    $values = $params;

    # build a regular expression for each parameter
    foreach ($params as $key => $value) {
        if (is_string($key)) {
            $keys[] = '/:' . $key . '/';
        } else {
            $keys[] = '/[?]/';
        }

        if (is_array($value))
            $values[$key] = implode(',', $value);

        if (is_null($value))
            $values[$key] = 'NULL';
    }
    // Walk the array to see if we can add single-quotes to strings
    array_walk($values, function (&$v, $k) {
        if (!is_numeric($v) && $v != "NULL") $v = "'" . $v . "'";
    });

    $query = preg_replace($keys, $values, $query, 1, $count);

    return $query;
}

function sql_timestamp(int $timestamp)
{
    return date('Y-m-d H:i:s', $timestamp);
}