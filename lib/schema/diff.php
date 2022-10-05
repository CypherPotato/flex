<?php

namespace schema;

trait diff
{
    public static function diff(mixed $old_schema, mixed $new_schema): array
    {
        $indexes = [];
        $alters = [];
        $no_index = [];
        foreach ($new_schema as $field => $type) {
            $alias = \Schema::alias($type, $field);
            $common_type = $alias->full_name;
            $index = $alias->index;
            $unique = $alias->unique ? "UNIQUE" : "";
            $default = "";

            if(!empty($alias->default)) {
                $default = "DEFAULT " . sanitize_db_literal($alias->default);
            }

            $field_s = sanitize_db_constant($field);
            if (array_key_exists($field, $old_schema)) {
                if ($old_schema[$field] != $common_type) {
                    $alters[] = "CHANGE COLUMN $field_s $field_s $common_type $default";
                    if ($index != "") {
                        $indexes[] = "DROP INDEX $field_s";
                        $indexes[] = "ADD $unique INDEX $field_s ($index) USING BTREE";
                    };
                }
            } else {
                $alters[] = "ADD COLUMN $field_s $common_type $default";
                if ($index != "") $indexes[] = "ADD $unique INDEX $field_s ($index) USING BTREE";
            }
            if ($index != "") $no_index[] = $field;
        }

        $new_keys = array_keys((array)$new_schema);
        $old_keys = array_keys($old_schema);
        foreach (array_diff($old_keys, $new_keys) as $dropped_field) {
            if (!in_array($dropped_field, ["id", "created_at", "updated_at"])) {
                $dropped_field = sanitize_db_constant($dropped_field);
                $alters[] = "DROP COLUMN $dropped_field";
                if (!in_array($dropped_field, $no_index)) $indexes[] = "DROP INDEX $dropped_field";
            }
        }

        return array_merge($alters, $indexes);
    }
}
