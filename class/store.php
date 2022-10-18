<?php

class store
{
    public static function add()
    {
        require_param("collection");
        require_param("object");
        require_param("object.contents");

        $collection_name = REQUEST->collection->name ?? REQUEST->collection;
        $data_contents = REQUEST->object->contents;

        $now = time();
        $id = get_sequential_id();

        $insert_properties = "id,created_at,updated_at,";
        $insert_schema = [":id", ":created_at", ":updated_at"];

        $objs = [
            'id' => $id,
            'created_at' => sql_timestamp($now),
            'updated_at' => sql_timestamp($now)
        ];
        foreach ($data_contents as $field_name => $b) {
            if (is_bool($b)) {
                $objs[$field_name] = $b ? 1 : 0;
            } else if (is_object($b) || is_array($b)) {
                $objs[$field_name] = json_encode($b);
            } else {
                $objs[$field_name] = $b;
            }

            $field_name_sanitized = sanitize_db_constant($field_name);
            if (!str_contains($insert_properties, $field_name_sanitized)) $insert_properties .= $field_name_sanitized . ",";
            if (!in_array(":" . $field_name, $insert_schema)) $insert_schema[] = ":" . $field_name;
        }

        $collection_name = sanitize_db_constant($collection_name);
        $insert_values_schame = implode(',', $insert_schema);
        $insert_properties = rtrim($insert_properties, ',');

        $sql = "INSERT INTO $collection_name ($insert_properties) VALUES ($insert_values_schame);";

        try {
            $sth = DB_CONNECTION->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($objs);
        } catch (Exception $ex) {
            add_message("error", $ex->getMessage());
            return json_response();
        }

        add_message("info", "Object successfully stored.");
        return json_response(["id" => $id]);
    }

    public static function edit()
    {
        require_param("collection");
        require_param("object");
        require_param("object.id");
        require_param("object.contents");

        $collection_name = REQUEST->collection->name ?? REQUEST->collection;
        $id = REQUEST->object->id;
        $data_contents = REQUEST->object->contents;

        $now = time();
        $objs = [
            'id' => $id,
            'updated_at' => sql_timestamp($now)
        ];
        $update_schema = "updated_at = :updated_at,";
        foreach ($data_contents as $field_name => $___value) {
            if (in_array($field_name, ["id", "created_at", "updated_at"])) {
                add_message("error", "Cannot modify protected fields.");
                return json_response();
            }
            $b = $data_contents->$field_name ?? null;
            if (is_bool($b)) {
                $b = $b ? 1 : 0;
            } else if (is_object($b) || is_array($b)) {
                $b = json_encode($b);
            }

            $value = ":$field_name" . "__value";
            $objs[$field_name . "__value"] = $b;

            $$field_name = sanitize_db_constant($field_name);
            $update_schema .= "$field_name = $value,";
        }

        $update_schema = rtrim($update_schema, ",");

        $collection_name = sanitize_db_constant($collection_name);
        $sql = "UPDATE $collection_name SET $update_schema WHERE id = :id;";

        try {
            $sth = DB_CONNECTION->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($objs);
        } catch (Exception $ex) {
            add_message("error", $ex->getMessage());
            return json_response();
        }

        if ($sth->rowCount()) {
            add_message("info", "Object successfully updated.");
        } else {
            add_message("error", "Object with specified ID wasn't found.");
        }

        return json_response(["id" => $id]);
    }

    public static function edit_many()
    {
        require_param("collection");
        require_param("objects");

        $collection_name = REQUEST->collection->name ?? REQUEST->collection;
        $objects = REQUEST->objects;

        $collection_name = sanitize_db_constant($collection_name);

        $now = sql_timestamp(time());
        $failed_ids = [];
        $success = 0;
        foreach ($objects as $object) {
            $id = $object->id;
            $interpolation = [];
            $update_schema = "updated_at = '$now',";
            foreach ($object->contents as $field_name => $b) {
                if (in_array($field_name, ["id", "created_at", "updated_at"])) {
                    add_message("error", "Cannot modify protected fields. (id=" . $object->id . ")");
                    return json_response();
                }
                if (is_bool($b)) {
                    $b = $b ? 1 : 0;
                } else if (is_object($b) || is_array($b)) {
                    $b = json_encode($b);
                }

                $value = ":$field_name" . "__value_" . $id;
                $interpolation[$field_name . "__value_" . $id] = $b;

                $field_name = sanitize_db_constant($field_name);
                $update_schema .= "$field_name = $value,";
            }

            $update_schema = rtrim($update_schema, ",");

            $sql = "UPDATE $collection_name SET $update_schema WHERE id = '$id';";
            try {
                $sth = DB_CONNECTION->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $ok = $sth->execute($interpolation);
                if ($sth->rowCount() > 0) {
                    $success++;
                } else {
                    $failed_ids[] = $id;
                }
            } catch (Exception $ex) {
                add_message("error", "At ID $id: " . $ex->getMessage());
                return json_response();
            }
        }

        if ($success) {
            add_message("info", "Objects successfully updated.");
        } else {
            add_message("info", "No object was updated.");
        }

        return json_response([
            "updated_object_count" => $success,
            "failed_ids" => $failed_ids
        ]);
    }

    public static function patch()
    {
        require_param("collection");
        require_param("objects");

        $collection_name = REQUEST->collection->name ?? REQUEST->collection;
        $collection_name = sanitize_db_constant($collection_name);

        if (isset(REQUEST->collection->truncate) && REQUEST->collection->truncate != false) {
            $sql = "TRUNCATE TABLE $collection_name;";

            try {
                $sth = DB_CONNECTION->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $sth->execute();
            } catch (Exception $ex) {
                add_message("error", $ex->getMessage());
                return json_response();
            }
        }

        $pad = 0;
        $now = time();
        $success = 0;
        $failed_ids = [];
        foreach (REQUEST->objects as $object) {
            $pad++;
            $id = get_sequential_id($pad);
            $insert_properties = "id,created_at,updated_at,";
            $insert_schema = [":id", ":created_at", ":updated_at"];

            $objs = [
                'id' => $object->id ?? $id,
                'created_at' => $object->created_at ?? sql_timestamp($now),
                'updated_at' => $object->updated_at ?? sql_timestamp($now)
            ];
            foreach ($object as $field_name => $b) {
                if (is_bool($b)) {
                    $objs[$field_name] = $b ? 1 : 0;
                } else if (is_object($b) || is_array($b)) {
                    $objs[$field_name] = json_encode($b);
                } else {
                    $objs[$field_name] = $b;
                }

                $field_name_sanitized = sanitize_db_constant($field_name);
                if (!str_contains($insert_properties, $field_name_sanitized)) $insert_properties .= $field_name_sanitized . ",";
                if (!in_array(":" . $field_name, $insert_schema)) $insert_schema[] = ":" . $field_name;
            }

            $insert_values_schame = implode(',', $insert_schema);
            $insert_properties = rtrim($insert_properties, ',');

            try {
                $sql = "INSERT INTO $collection_name ($insert_properties) VALUES ($insert_values_schame);";
                $sth = DB_CONNECTION->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $sth->execute($objs);
                if ($sth->rowCount() > 0) {
                    $success++;
                } else {
                    $failed_ids[] = $objs["id"];
                }
            } catch (Exception $ex) {
                add_message("error", "At id " . $objs["id"] . ": " . $ex->getMessage());
                $failed_ids[] = $objs["id"];
            }
        }

        add_message("info", $success . " of " . sizeof(REQUEST->objects) . " was inserted in the collection.");
        return json_response(["failed_ids" => $failed_ids]);
    }

    public static function delete()
    {
        require_param("collection");
        require_param("objects");

        $collection_name = REQUEST->collection->name ?? REQUEST->collection;
        $collection_name = sanitize_db_constant($collection_name);

        $success = 0;
        $failed_ids = [];
        foreach (REQUEST->objects as $id) {
            $sql = "DELETE FROM $collection_name WHERE id = :id";
            $sth = DB_CONNECTION->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            try {
                $sth->execute(["id" => $id]);
                if ($sth->rowCount() > 0) {
                    $success++;
                } else {
                    $failed_ids[] = $id;
                }
            } catch (Exception $ex) {
                add_message("error", "At id " . $id . ": " . $ex->getMessage());
                return json_response();
            }
        }

        add_message("info", $success . " of " . count(REQUEST->objects) . " objects was removed.");
        return json_response(["failed_ids" => $failed_ids]);
    }
}
