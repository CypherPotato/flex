<?php

class collections
{
    public static function browse()
    {
        return json_response(["collections" => TABLES]);
    }

    public static function read($collection)
    {
        $collection = sanitize_db_constant($collection);

        try {
            $s = DB_CONNECTION->prepare("DESCRIBE $collection");
            $s->execute();
            $mcollection_data = $s->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            add_message("error", $ex->getMessage());
            return json_response();
        }

        $output = [];
        foreach ($mcollection_data as $field) {
            if (in_array($field["Field"], ["id", "created_at", "updated_at"])) continue;
            $output[$field["Field"]] = [
                "type" => $field["Type"],
                "nullable" => $field["Null"] == "YES" ? true : false,
                "default" => $field["Default"],
                "unique" => $field["Key"] == "UNI" ? true : false
            ];
        }

        return json_response([
            "collection" => $output
        ]);
    }

    public static function add()
    {
        require_param("collection");
        require_param("collection.name");
        require_param("schema");

        $collection_name = REQUEST->collection->name;
        $collection_name = sanitize_db_constant($collection_name);

        if (isset(REQUEST->collection->fresh) && REQUEST->collection->fresh == true) {
            $drop = "DROP TABLE IF EXISTS $collection_name;\n";
            DB_CONNECTION->query($drop);
        }

        $sql = "CREATE TABLE $collection_name (\n";
        $sql .= "`id` bigint(20) unsigned NOT NULL,\n";
        $sql .= "`created_at` timestamp NULL DEFAULT NULL,\n";
        $sql .= "`updated_at` timestamp NULL DEFAULT NULL,\n";

        $schema_built = \Schema::build(REQUEST->schema, true);

        $sql .= implode(",\n", $schema_built) . ");";
        
        try {
            $s = DB_CONNECTION->prepare($sql);
            DB_CONNECTION->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $s->execute();
        } catch (Exception $ex) {
            add_message("error", $ex->getMessage());
            return json_response();
        }

        add_message("info", "Collection created succesfully.");
        return json_response();
    }

    public static function edit()
    {
        require_param("collection");
        require_param("collection.name");
        require_param("schema");

        $collection_name = REQUEST->collection->name;
        $collection_name = sanitize_db_constant($collection_name);

        try {
            $s = DB_CONNECTION->prepare("DESCRIBE $collection_name");
            $s->execute();
            $mcollection_data = $s->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            add_message("error", $ex->getMessage());
            return json_response();
        }

        $old_schema = [];
        foreach ($mcollection_data as $field) {
            $old_schema[$field["Field"]] = $field["Type"] . ($field["Null"] == "YES" ? " NULL" : " NOT NULL");
        }

        $schema_built = \Schema::diff($old_schema, REQUEST->schema);

        if (sizeof($schema_built) == 0) {
            add_message("info", "Nothing to migrate.");
            return json_response();
        }

        $sql = "ALTER TABLE $collection_name\n";
        $sql .= implode(",\n", $schema_built) . ";";

        try {
            $s = DB_CONNECTION->prepare($sql);
            DB_CONNECTION->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $s->execute();
        } catch (Exception $ex) {
            add_message("error", $ex->getMessage());
            return json_response();
        }

        add_message("info", "Collection edited succesfully.");
        return json_response();
    }

    public static function delete()
    {
        require_param("collection.name");

        $collection_name = REQUEST->collection->name;
        $collection_name = sanitize_db_constant($collection_name);

        $sql = "DROP TABLE $collection_name;";

        try {
            $s = DB_CONNECTION->prepare($sql);
            DB_CONNECTION->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $s->execute();
        } catch (Exception $ex) {
            add_message("error", $ex->getMessage());
            return json_response();
        }

        add_message("info", "Collection deleted.");
        return json_response();
    }
}
