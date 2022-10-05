<?php

setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set("America/Sao_Paulo");

define("ROOT_DIR", __DIR__);
define("LIB_DIR", ROOT_DIR . "/lib");
define("UTIL_DIR", ROOT_DIR . "/util");
define("CLASS_DIR", ROOT_DIR . "/class");

require_once UTIL_DIR . '/teeny.php';
require_once UTIL_DIR . '/debug.php';
require_once UTIL_DIR . '/env.php';
require_once UTIL_DIR . '/debug.php';
require_once UTIL_DIR . '/err_handler.php';
require_once UTIL_DIR . '/request_handler.php';

require_once LIB_DIR . '/constant/datatypes.php';
require_once LIB_DIR . '/query/where_executor.php';
require_once LIB_DIR . '/query/query.php';
require_once LIB_DIR . '/query/sql_query.php';
require_once LIB_DIR . '/schema/builder.php';
require_once LIB_DIR . '/schema/alias.php';
require_once LIB_DIR . '/schema/diff.php';
require_once LIB_DIR . '/schema/schema.php';
require_once LIB_DIR . '/id.php';
require_once LIB_DIR . '/permission.php';
require_once LIB_DIR . '/db.php';
require_once LIB_DIR . '/sanitize.php';

require_once CLASS_DIR . '/collections.php';
require_once CLASS_DIR . '/query.php';
require_once CLASS_DIR . '/store.php';
require_once CLASS_DIR . '/ping.php';

$app = new \Inphinit\Teeny;

$app->action('GET',    '/collections',      fn() => collections::browse());
$app->action('GET',    '/collections/<id>', fn($p) => collections::read($p['id']));
$app->action('PUT',    '/collections',      fn() => collections::edit());
$app->action('POST',   '/collections',      fn() => collections::add());
$app->action('DELETE', '/collections',      fn() => collections::delete());

$app->action('POST',   '/store',            fn() => store::add());
$app->action('PATCH',  '/store',            fn() => store::patch());
$app->action('PUT',    '/store',            fn() => store::edit());
$app->action('PUT',    '/store/many',       fn() => store::edit_many());
$app->action('DELETE', '/store',            fn() => store::delete());

$app->action('POST',   '/query',            fn() => query::run());

$app->action('GET',    '/ping',             fn() => ping::ping());
return $app->exec();