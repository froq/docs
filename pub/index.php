<?php declare(strict_types=1);

/** Define app dir & start. */
define('APP_DIR', dirname(__DIR__));
define('APP_START', microtime(true));

/** Require Froq! */
require APP_DIR . '/app/Froq.php';

try {
    // Local dev check for dir.
    if (!defined('__local__')) {
        define('__local__', PHP_SAPI === 'cli-server');
    }
    $dir = __local__ ? '/var/www/!froq' : null;

    /** Initialize. */
    Froq::init(root: '/', env: null, dir: $dir)

    /** Prepare. */
    // ->prepare(function ($app) {
    //     // Error handler.
    //     $app->on('error', function ($event, $error) { ... });

    //     // Output handler.
    //     $app->on('output', function ($event, $output) { ... return $output });

    //     // Before/after handlers.
    //     $app->on('before', function ($event) { ... });
    //     $app->on('after', function ($event) { ... });

    //     // Shortcut routes.
    //     $app->get('/book/:id', 'Book.show');
    //     $app->get('/book/:id', function ($id) { ... });
    // })

    /** Run. */
    ->run();
} catch (Throwable $e) {
    error_log((string) $e);
    // echo $e->getMessage(), "\n";
    // echo $e->getTraceAsString();
}
