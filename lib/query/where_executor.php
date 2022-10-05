<?php

function filter_string(array $filters, string $str, bool $isFieldName, int $cutIndex, string $appendLeft, string $appendRight)
{
    $str = substr($str, $cutIndex);
    $original_padded = $str;
    $str = $appendLeft . $str . $appendRight;
    $str = $isFieldName ? sanitize_db_constant($str) : DB_CONNECTION->quote($str);
    /*if (in_array("ignore-case", $filters)) {
        $str = "UPPER($str)";
    }*/
    if (in_array("val-unix-timestamp", $filters) && !$isFieldName && preg_match('/^[0-9]+$/', $original_padded)) {
        $str = "FROM_UNIXTIME($str)";
    }
    if (in_array("sanitize-numbers", $filters)) {
        $str = "SUBSTRING($str, PATINDEX('%[0-9]%', $str), LEN($str))";
    }
    if (in_array("trim", $filters)) {
        $str = "TRIM($str)";
    }
    return $str;
}

function build_where_conditional(string $field, string $pattern, array $filters): string
{
    $before_field = "";

    if (str_starts_with($pattern, '!')) {
        $pattern = substr($pattern, 1);
        $before_field = "NOT";
    }

    if ($pattern == ":null") {
        return "(" . implode(' ', [$before_field, sanitize_db_constant($field), "IS NULL"]) . ")";
    } else if ($pattern == ":not-null") {
        return "(" . implode(' ', [$before_field, sanitize_db_constant($field), "IS NOT NULL"]) . ")";
    } else if ($pattern == ":empty") {
        return "(" . implode(' ', [$before_field, sanitize_db_constant($field), "= ''", 'OR', $before_field, sanitize_db_constant($field), "IS NULL",]) . ")";
    } else {
        try {
            if (str_starts_with($pattern, '^')) {
                $pattern = filter_string($filters, $pattern, false, 1, "", "%");
                $field = filter_string($filters, $field, true, 0, "", "");
                return "(" . trim(implode(' ', [$before_field, $field, "LIKE", $pattern])) . ")";
            } else if (str_starts_with($pattern, '$')) {
                $pattern = filter_string($filters, $pattern, false, 1, "%", "");
                $field = filter_string($filters, $field, true, 0, "", "");
                return "(" . trim(implode(' ', [$before_field, $field, "LIKE", $pattern])) . ")";
            } else if (str_starts_with($pattern, '~')) {
                $pattern = filter_string($filters, $pattern, false, 1, "%", "%");
                $field = filter_string($filters, $field, true, 0, "", "");
                return "(" . trim(implode(' ', [$before_field, $field, "LIKE", $pattern])) . ")";
            } else if (str_starts_with($pattern, '>=')) {
                $pattern = filter_string($filters, $pattern, false, 2, "", "");
                $field = filter_string($filters, $field, true, 0, "", "");
                return "(" . trim(implode(' ', [$before_field, $field, ">=", $pattern])) . ")";
            } else if (str_starts_with($pattern, '<=')) {
                $pattern = filter_string($filters, $pattern, false, 2, "", "");
                $field = filter_string($filters, $field, true, 0, "", "");
                return "(" . trim(implode(' ', [$before_field, $field, "<=", $pattern])) . ")";
            } else if (str_starts_with($pattern, '>')) {
                $pattern = filter_string($filters, $pattern, false, 1, "", "");
                $field = filter_string($filters, $field, true, 0, "", "");
                return "(" . trim(implode(' ', [$before_field, $field, ">", $pattern])) . ")";
            } else if (str_starts_with($pattern, '<')) {
                $pattern = filter_string($filters, $pattern, false, 1, "", "");
                $field = filter_string($filters, $field, true, 0, "", "");
                return "(" . trim(implode(' ', [$before_field, $field, "<", $pattern])) . ")";
            } else if (str_starts_with($pattern, '=')) {
                $pattern = filter_string($filters, $pattern, false, 1, "", "");
                $field = filter_string($filters, $field, true, 0, "", "");
                return "(" . trim(implode(' ', [$before_field, $field, "=", $pattern])) . ")";
            } else {
                $pattern = filter_string($filters, $pattern, false, 0, "", "");
                $field = filter_string($filters, $field, true, 0, "", "");
                return "(" . trim(implode(' ', [$before_field, $field, "=", $pattern])) . ")";
            }
        } catch (Exception $ex) {
            return false;
        }
    }
}
