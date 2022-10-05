<?php

/*function recursive_create_multi_array(array &$starter_array, array $indexes, int $index, mixed $final_value)
{
    $obj = [];
    if ($index == 0) {
        $starter_array[$indexes[$index]] = array_merge_recursive(
            $starter_array[$indexes[$index]] ?? [],
            recursive_create_multi_array($starter_array, $indexes, $index + 1, $final_value)
        );
    } else if ($index < sizeof($indexes)) {
        $value = recursive_create_multi_array($starter_array, $indexes, $index + 1, $final_value);
        if (is_array($value)) {
            $obj[$indexes[$index]] = array_merge(
                $obj[$indexes[$index]] ?? [],
                $value
            );
        } else {
            $obj[$indexes[$index]] = $value;
        }
    } else {
        return $final_value;
    }
    return $obj;
}*/

function sql_query_parse_key(mixed $object): array
{
    $to_add = [];
    foreach ($object as $k => $v) {
        if (
            !empty($v)
            && (str_starts_with($v, '{') || str_starts_with($v, '['))
            &&  (str_ends_with($v, '}') || str_ends_with($v, ']'))
            &&  str_contains($v, '"')
        ) {
            // maybe it's a JSON
            $v = json_decode($v) ?? $v;
        }
        if (str_contains($k, ':')) {
            $spl = explode(':', $k);
            $current = &$to_add;

            /* proposto pelo @inphinit */
            foreach ($spl as $value) {
                if (!is_array($current)) {
                    $current = ["__default" => $current];
                }
                if (array_key_exists($value, $current) === false) {
                    $current[$value] = [];
                }
                $current = &$current[$value];
            }
            $current = $v;
        } else {
            $to_add[$k] = $v;
        }
    }
    return $to_add;
}

function sql_query($query): array
{
    $variables = array_filter(apache_request_headers(), fn ($k, $h) => str_starts_with($h, 'Flex-Query-Param-'), ARRAY_FILTER_USE_BOTH);
    $variables_filtered = [];
    foreach ($variables as $k => $h) {
        $variables_filtered[':' . strtolower(str_replace('Flex-Query-Param-', '', $k))] = $h;
    }

    try {
        DB_CONNECTION->beginTransaction();
        $sth = DB_CONNECTION->prepare($query);
        $sth->execute($variables_filtered);
        $results = $sth->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        json_response(["error" => $ex->getMessage()], true, true, 400);
        DB_CONNECTION->rollBack();
        die;
    }

    DB_CONNECTION->commit();

    $newResults = [];
    foreach ($results as $res) {
        $newResults[] = sql_query_parse_key($res);
    }

    header("Flex-Query-Length: " . sizeof($newResults));

    return $newResults;
}
