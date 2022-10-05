<?php

class query
{
    public static function run()
    {
        if ($_SERVER["CONTENT_TYPE"] == "application/json") {
            $parsed_query = run_query(REQUEST);
            if ($GLOBALS["success"] == false) {
                return error_response();
            }
        } else if ($_SERVER["CONTENT_TYPE"] == "application/x-sql") {
            $parsed_query = sql_query(REQUEST);
            if ($GLOBALS["success"] == false) {
                return error_response();
            }
        }

        return json_response($parsed_query, false, true, 200);
    }
}
