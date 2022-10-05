<?php

function sanitize_db_constant($value)
{
    return '`' . preg_replace('/[^a-zA-Z0-9_\-.]*/', '', $value) . '`';
}

function sanitize_db_literal($value)
{
    if ($value == null) {
        return "NULL";
    }
    return DB_CONNECTION->quote($value);
}

function sanitize_db_int($value)
{
    return preg_replace('/[^0-9]*/', '', $value);
}
