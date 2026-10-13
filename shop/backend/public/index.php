<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Normalize SCRIPT_NAME when hosted in public_html with a root .htaccess rewrite to public/
if (
    isset($_SERVER['SCRIPT_NAME'], $_SERVER['REQUEST_URI']) &&
    str_ends_with($_SERVER['SCRIPT_NAME'], '/public/index.php') &&
    ! str_starts_with($_SERVER['REQUEST_URI'], '/public')
) {
    $_SERVER['SCRIPT_NAME'] = substr($_SERVER['SCRIPT_NAME'], 0, -strlen('/public/index.php')).'/index.php';
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());

