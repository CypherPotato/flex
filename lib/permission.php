<?php

$token = $_SERVER["HTTP_TOKEN"] ?? "";
$tokens = ENV->tokens ?? [];
$ftoken = null;
foreach ($tokens as $xtoken) {
    if ($token == $xtoken->token) {
        $ftoken = $xtoken;
    }
}

if ($ftoken == null) {
    add_message("error", "Authentication required.");
    json_response(null, true, false, 401);
    die;
}

try {
    $connection = new PDO(
        'mysql:host=' . $ftoken->db_connection->db_host . ';dbname=' . $ftoken->db_connection->db_name . ';charset=utf8',
        $ftoken->db_connection->db_user,
        $ftoken->db_connection->db_pass,
        [
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        ]
    );

    $s = $connection->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_SCHEMA = :db_name");
    $r = $s->execute(["db_name" => $ftoken->db_connection->db_name]);

    DEFINE("TABLES", array_map(fn ($s) => $s["TABLE_NAME"], $s->fetchAll()));
} catch (Exception $ex) {
    add_message("error", "Cannot connect to the MySQL server: " . $ex->getMessage());
    json_response(null, true, false, 500);
    die;
}

define('DB_LABEL', $ftoken->label);
define('DB_LOCATION', $ftoken->db_connection->db_user . '@' . $ftoken->db_connection->db_name);
define('DB_CONNECTION', $connection);