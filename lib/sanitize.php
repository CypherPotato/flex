<?php

function sanitize_db_constant($value)
{
    return '`' . preg_replace('/[^a-zA-Z0-9_\-.]*/', '', $value) . '`';
}

function sanitize_db_literal($value)
{
    if ($value === 0) {
        return 0;
    } else if ($value == null) {
        return "NULL";
    } else if ($value === true) {
        return "true";
    } else if ($value === false) {
        return "false";
    } else if (is_numeric($value)) {
        return $value;
    } else {
        return DB_CONNECTION->quote($value);
    }
}

function sanitize_db_int($value)
{
    return preg_replace('/[^0-9]*/', '', $value);
}
