<?php

namespace schema;

trait alias
{
    public static function alias(mixed $type, string $field_name = ""): object
    {
        $nullable = "NOT NULL";
        if (is_string($type)) {
            if (str_ends_with($type, "?")) {
                $type = rtrim($type, '?');
                $nullable = "";
            }

            switch ($type) {
                case "string":
                    $type = "longtext";
                    $toIndex = "`$field_name`(128)";
                    break;
                case "text":
                    $type = "text";
                    $toIndex = "`$field_name`(64)";
                    break;
                case "uint":
                    $type = "bigint(20) unsigned";
                    $toIndex = "`$field_name`";
                    break;
                case "int":
                    $type = "bigint(20)";
                    $toIndex = "`$field_name`";
                    break;
                case "number":
                    $type = "decimal(24,2)";
                    $toIndex = "`$field_name`";
                    break;
                case "bool":
                    $type = "tinyint(1)";
                    $toIndex = "`$field_name`";
                    break;
                case "object":
                    $type = "json";
                    $toIndex = "";
                    break;
                default:
                    $type = $type;
                    $toIndex = "`$field_name`";
                    break;
            }

            return (object)[
                "type" => $type,
                "index" => $toIndex,
                "unique" => false,
                "default" => null,
                "full_name" => trim($type . " " . $nullable)
            ];
        } else {
            $index_text = "`$field_name`";
            $type = (array)$type;

            if (str_starts_with($type["type"], "text")) {
                $index_text .= "(32)";
            } else if (str_starts_with($type["type"], "mediumtext")) {
                $index_text .= "(64)";
            } else if (str_starts_with($type["type"], "longtext")) {
                $index_text .= "(128)";
            } else if (str_starts_with($type["type"], "varchar")) {
                $index_text = "`$field_name`";
            } else {
                $index_text = ""; // no index
            }
            return (object)[
                "type" => $type["type"],
                "index" => $index_text,
                "unique" => $type["unique"],
                "default" => $type["default"],
                "full_name" => $type["type"] . ($type["nullable"] ? " NULL" : " NOT NULL")
            ];
        }
    }
}
