<?php

function run_query($query): array
{
    require_param("collection");

    $output = [];

    try {
        $collection = sanitize_db_constant($query->collection);
        $s = DB_CONNECTION->prepare("DESCRIBE $collection");
        $s->execute();
        $mcollection_data = $s->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        json_response(["error" => $ex->getMessage()], true, true, 400);
        die;
    }

    $collection_description = [];
    foreach ($mcollection_data as $field) {
        $collection_description[$field["Field"]] = $field["Type"] . ($field["Null"] == "YES" ? "?" : "");
    }

    $skip = $query->pagination->skip ?? -1;
    $take = $query->pagination->take ?? -1;

    $select_all = in_array("*", $query->select ?? ["*"]);
    $filters = $query->filters ?? [];
    $hasWhere = isset($query->where);

    $whereDescriptors = [];
    if ($hasWhere) {
        if (!is_array($query->where)) {
            foreach ($query->where as $clausuleName => $clausuleValue) {
                $whereDescriptors[] = [
                    "pattern" => $clausuleValue,
                    "field" => $clausuleName
                ];
            }
        } else {
            foreach ($query->where as $clausuleGroup) {
                foreach ($clausuleGroup as $clausuleName => $clausuleValue) {
                    $whereDescriptors[] = [
                        "pattern" => $clausuleValue,
                        "field" => $clausuleName
                    ];
                }
            }
        }
    }

    $sql = "";
    if ($select_all) {
        $sql = "SELECT * FROM " . sanitize_db_constant($query->collection);
    } else if (isset($query->select)) {
        $select_formatted = array_map(fn ($s) => sanitize_db_constant($s), $query->select);
        $sql = "SELECT " . implode(", ", $select_formatted) . " FROM " . sanitize_db_constant($query->collection);
    }

    if (sizeof($whereDescriptors) > 0) {
        $sql .= " WHERE";
        $firstClausule = true;
        foreach ($whereDescriptors as $whereDescriptor) {
            $key = $whereDescriptor["field"];
            $pattern = $whereDescriptor["pattern"];
            $op = "AND";

            if (str_starts_with($key, '&')) {
                $key = substr($key, 1);
                $op = "AND";
            } else if (str_starts_with($key, '|')) {
                $key = substr($key, 1);
                $op = "OR";
            }

            if ($firstClausule) {
                $op = "";
            }

            $timestampAsValue = in_array($key, ["created_at", "updated_at"]);
            if ($timestampAsValue) {
                $whereCond = build_where_conditional($key, $pattern, $filters + ["val-unix-timestamp"]);
            } else {
                $whereCond = build_where_conditional($key, $pattern, $filters);
            }
            $sql .= $op . " " . $whereCond . " ";

            if ($firstClausule == true) {
                $firstClausule = false;
            }
        }
    }

    if (isset($query->order_by)) {
        $sql .= " ORDER BY " . sanitize_db_constant($query->order_by);
    }

    if (isset($query->order_term) && strtolower($query->order_term) == "desc") {
        $sql .= " DESC";
    } else if (isset($query->order_term) && strtolower($query->order_term) == "asc") {
        $sql .= " ASC";
    }

    if ($take > 0) {
        $sql .= " LIMIT " . sanitize_db_int($take);
    }
    if ($skip > 0) {
        $sql .= " OFFSET " . sanitize_db_int($skip);
    }

    if (VIEW_AS == CONTENT_TYPE_X_SQL) {
        die($sql);
    }

    $columns = [];
    $ongoing_results = [];
    $results = DB_CONNECTION->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $res) {
        if (empty($columns)) {
            foreach ($res as $k => $v) {
                $columns[] = $k;
            }
        }
        foreach ($res as $field => $value) {
            if ($value === null) {
                $res[$field] = null;
                continue;
            }
            if (str_contains($collection_description[$field], "timestamp")) {
                $res[$field] = strtotime($value);
            }
            //if (str_contains($collection_description[$field], "decimal")) {
            //    $res[$field] = floatval($value);
            //}
            if (str_contains($collection_description[$field], "json")) {
                $res[$field] = json_decode($value);
            }
        }
        $ongoing_results[] = $res;
    }
    header("Flex-Query-Length: " . sizeof($output));

    define('SQL_QUERY_RESULTS', $ongoing_results);
    define('SQL_QUERY_COLUMNS', $columns);

    return $ongoing_results;
}
