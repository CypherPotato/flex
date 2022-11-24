<?php

use Inphinit\Http\Response;

header("Server: FLEX/SQL 1.0alpha-1233552");

define('CONTENT_TYPE_HTML', 13);
define('CONTENT_TYPE_X_SQL', 12);
define('CONTENT_TYPE_JSON', 11);

$GLOBALS["success"] = true;
$GLOBALS["messages"] = [];
$GLOBALS["supress-messaging"] = false;

define("RAW_REQUEST", file_get_contents('php://input'));
if (!empty(RAW_REQUEST) && $_SERVER['REQUEST_METHOD'] != "GET") {
    if (str_contains($_SERVER["CONTENT_TYPE"], "application/json")) {
        define('REQUEST', json_decode(RAW_REQUEST, false));
        define('REQUEST_CONTENT_TYPE', CONTENT_TYPE_JSON);
        define('REQUEST_TREE', json_tree(REQUEST));
        $err = json_last_error();
        if ($err != 0) {
            add_message("error", "JSON decoding error: " . $err);
            json_response(null, true);
        }
    } else if (str_contains($_SERVER["CONTENT_TYPE"], "application/x-sql")) {
        define('REQUEST', RAW_REQUEST);
        define('REQUEST_CONTENT_TYPE', CONTENT_TYPE_X_SQL);
        define('REQUEST_TREE', []);
    } else {
        add_message("error", "Invalid Content-Type received: " . $_SERVER["CONTENT_TYPE"]);
        json_response(null, true);
    }
}

if (isset($_SERVER["HTTP_ACCEPT"])) {
    switch ($_SERVER["HTTP_ACCEPT"]) {
        case "application/json":
            define('VIEW_AS', CONTENT_TYPE_JSON);
            break;
        case "text/html":
            define('VIEW_AS', CONTENT_TYPE_HTML);
            break;
        case "application/x-sql":
            define('VIEW_AS', CONTENT_TYPE_X_SQL);
            break;
        default:
            define('VIEW_AS', CONTENT_TYPE_JSON);
            break;
    }
} else {
    define('VIEW_AS', CONTENT_TYPE_JSON);
}

function json_tree(array|object $json_object, string $root = "")
{
    $ROOT = ($root == "" ? "" : $root . ".");
    $output = [];
    foreach ($json_object as $key => $value) {
        if (is_array($value) || is_object($value)) {
            $output[$ROOT . $key] = "-nested-";
            $output += json_tree($value, $ROOT . $key);
        } else {
            $output[$ROOT . $key] = $value;
        }
    }
    return $output;
}

function require_param(string $expression)
{
    if (!defined("REQUEST_TREE")) {
        add_message("error", "RAW_REQUEST: " . strlen(RAW_REQUEST));
        json_response(null, true);
        die();
    }
    if (!array_key_exists($expression, REQUEST_TREE)) {
        add_message("error", "Required parameter not found in the request: " . $expression);
        json_response(null, true);
        die();
    }
}

function safe_get_useragent()
{
    if (!isset($_SERVER['HTTP_USER_AGENT'])) {
        return "unknown";
    } else {
        return $_SERVER['HTTP_USER_AGENT'];
    }
}

function add_message(string $type, string $message)
{
    if ($GLOBALS["supress-messaging"]) return;
    $GLOBALS["messages"][] = [
        "level" => $type,
        "message" => $message
    ];
    if ($type == "error" || $type == "fatal") {
        $GLOBALS["success"] = false;
    }
}

function json_response($content = null, bool $close_connection = false, $raw = false, $override_http_response = 0)
{
    header("Content-Type: application/json");

    if ($GLOBALS["success"] == false) {
        http_response_code(400);
    } else {
        http_response_code(200);
    }

    if ($override_http_response != 0) {
        http_response_code($override_http_response);
    }

    if ($raw) {
        $json_response = json_encode($content);
    } else {
        $json_response = json_encode([
            'success' => $GLOBALS["success"],
            'messages' => array_reverse($GLOBALS["messages"]),
            'response' => $content
        ]);
    }

    if (!$close_connection) {
        return $json_response;
    } else {
        ob_end_clean();
        header("Connection: close");
        ignore_user_abort(true);
        ob_start();
        echo $json_response;
        $size = ob_get_length();
        header("Content-Length: $size");
        ob_end_flush();
        flush();
    }
}

function error_response()
{
    return json_response([
        "error" => $GLOBALS["messages"][0]["message"]
    ], false, true);
}
