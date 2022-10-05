# Flex

## Transform your MySQL server in a fully working Restful API!

Flex is a superfast database provider server that integrates with your existing MySQL server and allows for easy API integration. That way, you can create API access tokens and don't expose your MySQL server data like address, username or password.

Everything is controlled by JSON contents for a better portability of your content. Secure requests can be sent by JSON requests and the system has its own security to prevent SQL injection attacks.

```json
{
    "collection": "cars",
    "object": {
        "contents": {
            "model": "Murcielago",
            "brand": "Lamborghini",
            "year": 2008
        }
    }
}
```

It's equivalent to:

```sql
INSERT INTO `cars` (id, created_at, updated_at, `model`, `brand`, `year`)
VALUES (16649890104392314377, '2022-10-05 13:56:50', '2022-10-05 13:56:50','Murcielago','Lamborghini', 2008);
```

Behind the scenes, the application uses PHP PDO, and always uses `PDO->prepare()` in its Flex methods.

Access is segmented by access tokens, which constitute the accesses to the respective databases, for example:

```json
{
    "token": "r7Ff35XqjFbwVRDCBrJjWpXFNcfW24h7T07zHyRyUY",
    "label": "My Cool Car Application",
    "db_connection": {
        "db_name": "cars",
        "db_host": "localhost",
        "db_user": "root",
        "db_pass": "NcfW24h7T07z"
    }
}
```

API access is always via the "token" header and does not expose database data. Read and write settings are configured on the database server.

```http
POST /store HTTP1.1
Token: r7Ff35XqjFbwVRDCBrJjWpXFNcfW24h7T07zHyRyUY
...
```

Flex also allows you to run pure SQL queries. It's recommended to use it only for reading data, and to use the Flex ready functions for writing in the database.

```http
POST /query HTTP1.1
Token: r7Ff35XqjFbwVRDCBrJjWpXFNcfW24h7T07zHyRyUY
Content-Type: application/x-sql
Flex-Query-Param-Brand: lamborghini

SELECT
    model,
    year
FROM
    cars
WHERE
    brand = :brand
```

Note that in the above query, `:brand` will be securely replaced by `'lamborghini'`.

## Requeriments

- Works on any HTTP server.
- PHP 8.0+ (not tested on PHP 7.x)
- MySQL 5.5+

## Documentation

Documentation is still under development. Come back soon.

## Credits

This project uses Teeny for routing, available [here](https://github.com/inphinit/teeny).