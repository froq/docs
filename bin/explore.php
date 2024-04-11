<?php
// Generates an autoload map for app/system & app/library folders.
// $ php -f bin/explore.php -- OR $ composer explore
// $ php -f bin/explore.php -- --no-sort OR $ composer explore -- --no-sort

// @tome: ln -s /var/www/\!froq/froq* /var/www/\!froq/sample_test/vendor/froq

if (PHP_SAPI !== 'cli') {
    echo 'This file must be run via CLI only!', PHP_EOL;
    exit(1);
}

// Path to "vendor/froq" folder.
$froqDir = dirname(__DIR__) . '/vendor/froq';

// Include autoloader file.
if (!is_file($file = ($froqDir . '/froq/src/Autoloader.php'))) {
    echo 'Froq autoloader file "' . $file . '" not found!', PHP_EOL;
    exit(1);
}
require $file;

// Register autoloader.
$loader = froq\Autoloader::init($froqDir);
$loader->register();

// Default options.
$options = ['sort' => true, 'drop' => false, 'dirs' => null];

// Argument options (@see https://hotexamples.com/examples/-/-/getopt/php-getopt-function-examples.html#0xf9ece8ff405d63537039ab3109d73dce30d48cc67de91901e8acc7e12cb31a13-8,,33,).
$arguments = getopt('', ['no-sort', 'drop', 'dirs:']);
if (isset($arguments['no-sort'])) {
    $options['sort'] = false;
}
if (isset($arguments['drop'])) {
    $options['drop'] = true;
}
if (isset($arguments['dirs'])) {
    $options['dirs'] = $arguments['dirs'];
}

// Required for autoloader.
define('APP_DIR', dirname(__DIR__));

// Drop autoload map.
if ($options['drop']) {
    $mapFile = APP_DIR . $loader->getMapFile();
    if (!is_file($mapFile)) {
        echo 'No file exists such: "' . $mapFile .'", skipped.', PHP_EOL;
    } else {
        echo 'Dropping autoload map: "' . $mapFile . '" ... ';
        $loader->explore('', ['drop' => true]);
        echo 'OK!', PHP_EOL;
    }
    return;
}

// Default app directories.
$dirs = ['/app/system', '/app/service', '/app/library'];

if ($options['dirs']) {
    $dirs = array_merge($dirs, array_filter(explode(',', $options['dirs'])));
    $dirs = array_map(fn($dir) => '/' . ltrim($dir, '/'), $dirs);
}

// Explore directories.
foreach ($dirs as $dir) {
    echo 'Generating autoload map for: "' . APP_DIR . $dir . '" ... ';
    if (!is_dir(APP_DIR . $dir)) {
        echo PHP_EOL, 'No directory exists such "' . APP_DIR . $dir . ', skipped.', PHP_EOL;
        continue;
    }
    $loader->explore($dir, $options);
    echo 'OK!', PHP_EOL;
}
