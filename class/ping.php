<?php

class ping
{
    public static function ping()
    {
        add_message("info", "Pong!");
        return json_response([
            "connection" => [
                "status" => DB_CONNECTION->getAttribute(PDO::ATTR_CONNECTION_STATUS),
                "driver" => DB_CONNECTION->getAttribute(PDO::ATTR_DRIVER_NAME)
            ],
            "auth" => [
                "label" => DB_LABEL,
                "db_location" => DB_LOCATION
            ]
        ]);
    }
}
