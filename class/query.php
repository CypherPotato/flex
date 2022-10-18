<?php

class query
{
    public static function run()
    {
        if (REQUEST_CONTENT_TYPE == CONTENT_TYPE_JSON) {
            $parsed_query = run_query(REQUEST);
            if ($GLOBALS["success"] == false) {
                return error_response();
            }
        } else if (REQUEST_CONTENT_TYPE == CONTENT_TYPE_X_SQL) {
            $parsed_query = sql_query(REQUEST);
            if ($GLOBALS["success"] == false) {
                return error_response();
            }
        }

        if (VIEW_AS == CONTENT_TYPE_JSON) {
            return json_response($parsed_query, false, true, 200);
        } else {
            require UTIL_DIR . "/html_table.php";
        }
    }
}
