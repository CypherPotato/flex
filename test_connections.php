<?php

if (!file_exists('environment.json')) {
    echo "No environment.json file found.\n";
    die;
}

$env_file = file_get_contents('environment.json');
$env = json_decode($env_file);

foreach ($env->tokens as $ftoken) {
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

        echo "Connection '", $ftoken->label, "' connection: OK\n";
    } catch (Exception $ex) {
        echo "Connection '", $ftoken->label, "' connection: failed: ", $ex->getMessage(), "\n";
    }
}