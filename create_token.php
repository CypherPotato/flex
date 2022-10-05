<?php

function get_random_token(): string
{
    $r = random_bytes(42);
    $s = base64_encode($r);
    $s = str_replace(["/", ".", "-", "+", "="], '', $s);
    return $s;
}

function get_example_environment(string $token)
{
    return [
        "development" => true,
        "tokens" => [
            [
                "token" => $token,
                "label" => "My first connection",
                "db_connection" => [
                    "db_host" => "localhost",
                    "db_user" => "root",
                    "db_name" => "",
                    "db_pass" => ""
                ]
            ]
        ]
    ];
}

$env_path = __DIR__ . "/environment.json";
$gen_token = get_random_token();

if (!file_exists($env_path)) {
    $env = json_encode(get_example_environment($gen_token), JSON_PRETTY_PRINT);
    file_put_contents($env_path, $env);
    echo "environment.json created!\n";
    echo "Your new token: ", $gen_token, "\n";
    exit;
} else {

    $label = readline("Connection label > ");
    $db_host = readline("Database host > ");
    $db_user = readline("Database user > ");
    $db_name = readline("Database name > ");
    $db_pass = readline("Database pass > ");

    $new_application = [
        "token" => $gen_token,
        "label" => $label,
        "db_connection" => [
            "db_host" => $db_host,
            "db_user" => $db_user,
            "db_name" => $db_name,
            "db_pass" => $db_pass
        ]
    ];

    $current_env = json_decode(file_get_contents($env_path));
    $current_env->tokens[] = $new_application;

    $new_env = json_encode($current_env, JSON_PRETTY_PRINT);
    file_put_contents($env_path, $new_env);

    echo "\nYour generated token for $label: ", $gen_token, "\n";
    exit;
}
