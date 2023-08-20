<?php
/**
 * Define your configs here.
 */
return [
    /* Note: dot (.) notations are valid for base keys. */
    /* With/without dots. */
    /* 'response' => ['json' => ['flags' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE]] */
    /* 'response.json' => ['flags' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE] */

    /* Routes & services. */
    'routes'   => include 'routes.php',
    'services' => include 'services.php',

    /* Protocols. */
    'http'     => 'http://' . $_SERVER['SERVER_NAME'],
    'https'    => 'https://' . $_SERVER['SERVER_NAME'],

    /* Localization. */
    'timezone' => 'UTC',
    'language' => 'en',
    'encoding' => 'UTF-8',
    'locales'  => [LC_TIME => 'en_US.UTF-8'],

    /* Ini. */
    // 'ini' => [string => scalar],

    /* Initial response headers (null means remove). */
    'headers'  => [
        // Cache (https://stackoverflow.com/questions/49547/how-do-we-control-web-page-caching-across-all-browsers).
        'Cache-Control'          => 'no-cache, no-store, must-revalidate, max-age=0, pre-check=0, post-check=0',
        'Pragma'                 => 'no-cache',
        'Expires'                => '0',
        // 'Connection'             => 'close',
        'X-Powered-By'           => 'Froq!',
        // Security (https://www.owasp.org/index.php/List_of_useful_HTTP_headers).
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options'        => 'sameorigin',
        'X-XSS-Protection'       => '1; mode=block',
    ],

    /* Initial response cookies. */
    // 'cookies'  => [name => [value, ?[options]]],

    /* Log options. */
    // 'log' => [
    //     'level'     => froq\log\LogLevel::ERROR | froq\log\LogLevel::WARN,
    //     'directory' => APP_DIR . '/var/log',
    // ],

    /* Session options (true = activate with default options). */
    // 'session'  => true,
    /* With custom options (below is default). */
    // 'session'  => [
    //     'name'     => 'SID',
    //     'hash'     => 'uuid' or 32, 40, 16, 'hashUpper' => bool,
    //     'savePath' => null, 'saveHandler' => class or [class, class-file],
    //     'cookie'   => [
    //         'lifetime' => 0,    'path'     => '/',  'domain'   => '',
    //         'secure'   => bool, 'httponly' => bool, 'samesite' => '',
    //     ],
    // ],

    /* Response options. */
    // 'response.compress'       => ['gzip' or 'zlib', 'minlen' => 64 (in kb) 'level' => 1..9],
    // 'response.xml'            => ['indent' => bool, 'indentString' => ' '],
    // 'response.json'           => ['indent' => string|int, 'flags' => JSON_*],
    // 'response.file.rateLimit' => 1024 ** 2, // 1MB

    /* Database options. */
    // 'database'    => [
    //     'dsn'  => string,
    //     'user' => string, 'pass' => string, 'profiling' => bool, 'logging' => array,
    // ],

    /* Cache options. */
    // 'cache' => ['id' => string, 'options' => ['id' => ?string, 'agent' => string, 'static' => bool,
    //     'ttl' => int, ...]],

    /* Router options. */
    'router' => ['defaultController' => 'app\controller\AppController', 'defaultAction' => 'index'],

    /* Dot-env. */
    // 'dotenv' => ['file' => string, 'global' => bool],

    /* View options. */
    'view' => ['base' => APP_DIR . '/app/system/view', 'layout' => APP_DIR . '/app/system/view/_layout.php'],

    /* Misc options. */
    'exposeAppRuntime' => true, // true=all, false=none or 'development', 'testing', 'staging', 'production'
];
