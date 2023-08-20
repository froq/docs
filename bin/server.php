<?php
// As current user.
// $ php -S localhost:8080 -t pub/ bin/server.php
// As another user (i.e. www-data).
// $ sudo -u www-data php -S localhost:8080 -t pub/ bin/server.php

if (PHP_SAPI !== 'cli-server') {
    echo 'This file must be run via CLI-Server only!', PHP_EOL;
    exit(1);
}

// Ensure request scheme.
$_SERVER['REQUEST_SCHEME'] ??= 'http' . (
    ($_SERVER['SERVER_PORT'] ?? '') === '443' ? 's' : ''
);

$_pub = realpath(__DIR__ . '/../pub');
$_uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Check for static files.
if ($_uri !== '/' && file_exists($_pub . $_uri)) {
    return false;
}

// Set env as local.
define('__local__', true);
define('__LOCAL__', true);

// Forward all to index.php.
require $_pub . '/index.php';
