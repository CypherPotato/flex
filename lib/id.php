<?php

function get_sequential_id(?int $sequential_pad = 0)
{
    $tm = microtime(true) * 10000;
    $rd = $sequential_pad === 0 ? rand(100000, 999999) : str_pad($sequential_pad, 6, "0", STR_PAD_LEFT);
    $hx = $tm . $rd;

    return $hx;
}
