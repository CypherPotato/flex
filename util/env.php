<?php

$file_path = ROOT_DIR . "/environment.json";
if (!is_file($file_path)) {
    die("Environment file environment.json not found.");
}
$env_text = file_get_contents($file_path);
define('ENV', json_decode($env_text, false));
