#!/usr/bin/env php
<?php

/**
 * JSON endpoint for the permalink database.
 *
 * Serve it with the built-in server or any FastCGI web server, for example:
 *
 *     PERMALINK_DB_PATH=/var/lib/permalink/docs.sqlite php -S 127.0.0.1:8090 bin/permalink-api.php
 *
 * See T3Docs\GuidesCli\InventoryIndex\PermalinkApi for the available endpoints.
 */

declare(strict_types=1);

namespace T3Docs\GuidesCli;

use PDO;
use Throwable;
use T3Docs\GuidesCli\InventoryIndex\PermalinkApi;

use function dirname;
use function error_log;
use function file_exists;
use function getenv;
use function header;
use function http_response_code;
use function is_file;
use function is_string;
use function json_encode;
use function parse_url;

$respond = static function (int $status, array $body): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
};

$autoload = dirname(__DIR__) . '/../../vendor/autoload.php';
if (!file_exists($autoload)) {
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
}

if (!file_exists($autoload)) {
    $respond(500, ['error' => 'The Composer autoloader was not found.']);
    exit(1);
}

require $autoload;

$database = getenv('PERMALINK_DB_PATH');
if (!is_string($database) || $database === '') {
    $respond(503, ['error' => 'The environment variable PERMALINK_DB_PATH is not set.']);
    exit(1);
}

if (!is_file($database)) {
    $respond(503, ['error' => 'The permalink database has not been created yet.']);
    exit(1);
}

try {
    $connection = new PDO('sqlite:' . $database);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // The endpoint only reads: a write here would be a bug or an attack.
    $connection->exec('PRAGMA query_only = ON');

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $response = (new PermalinkApi($connection))->handle(
        $_SERVER['REQUEST_METHOD'] ?? 'GET',
        is_string($path) ? $path : '/',
        $_GET,
    );

    $respond($response['status'], $response['body']);
} catch (Throwable $throwable) {
    error_log($throwable->getMessage());
    $respond(500, ['error' => 'The permalink database could not be read.']);
}
