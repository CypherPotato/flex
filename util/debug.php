<?php

function dd(...$expression)
{
    header("Content-Type: application/json");
    echo json_encode($expression, JSON_PRETTY_PRINT);
    die();
}
