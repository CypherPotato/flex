<?php

namespace schema;

use Schema;

trait build
{
    public static function build(object|array $schema, bool $includeMetadataIndex): array|bool
    {
        $output = [
            "fields" => [],
            "index" => [],
        ];
        $metadataIndex = [
            'PRIMARY KEY (`id`) USING BTREE',
            'INDEX `timestamps` (`id`, `created_at`, `updated_at`) USING BTREE'
        ];
        foreach ($schema as $name => $accept) {
            if (
                $name == "id"
                or $name == "created_at"
                or $name == "updated_at"
            ) {
                add_message("error", "Illegal or reserved schema field name: \"" . $name . "\".");
                return false;
            }

            if (empty($name) || empty($accept)) {
                add_message("error", "No empty value is accepted at \"" . $name . "\".");
                return false;
            }

            $alias = Schema::alias($accept, $name);
            $toIndex = $alias->index;
            $type = $alias->full_name;
            $unique = $alias->unique ? "UNIQUE" : "";
            $default = $alias->default;

            if ($default !== null && $default !== "") {
                $default = "DEFAULT " . sanitize_db_literal($default);
            }

            $name = sanitize_db_constant($name);

            $output["fields"][] = "$name $type $default";
            if ($toIndex != "") $output["index"][] = $unique . " INDEX $name ($toIndex) USING BTREE";
        }

        if ($includeMetadataIndex) {
            return array_merge($output["fields"], $metadataIndex, $output["index"]);
        } else {
            return array_merge($output["fields"], $output["index"]);
        }
    }
}
