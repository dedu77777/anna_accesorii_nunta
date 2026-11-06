<?php

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $webroot = realpath(__DIR__);
    $file = is_string($path) ? realpath(__DIR__ . $path) : false;

    if ($webroot !== false && $file !== false
        && str_starts_with($file, $webroot . DIRECTORY_SEPARATOR)
        && preg_match('~\.(?:css|js|png|jpe?g|webp|svg|ico|txt|xml)$~iD', $file)
        && is_file($file)) {
        return false;
    }
}

// Load Composer
require __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env
require __DIR__ . '/../config/bootstrap-env.php';

// Configure Yii2 environment
$environment = getenv('APP_ENV') ?: 'prod';

defined('YII_ENV') or define('YII_ENV', $environment);

defined('YII_DEBUG') or define(
    'YII_DEBUG',
    $environment === 'dev'
);

// Load Yii2
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

// Load application configuration
$config = require __DIR__ . '/../config/web.php';

// Start application
(new yii\web\Application($config))->run();
