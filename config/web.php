<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$cookieValidationKey = getenv('COOKIE_VALIDATION_KEY');
if (!$cookieValidationKey) {
    throw new \RuntimeException('COOKIE_VALIDATION_KEY must be configured.');
}
$secureCookies = getenv('APP_HTTPS') === '1'
    || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$mailerConfig = [
    'class' => \yii\symfonymailer\Mailer::class,
    'useFileTransport' => !getenv('SMTP_DSN'),
    'viewPath' => '@app/mail',
];
if (getenv('SMTP_DSN')) $mailerConfig['transport'] = ['dsn' => getenv('SMTP_DSN')];

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'container' => [
        'singletons' => [
            \yii\mail\MailerInterface::class => $mailerConfig,
        ],
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => $cookieValidationKey,
            'csrfCookie' => ['httpOnly' => true, 'sameSite' => \yii\web\Cookie::SAME_SITE_LAX, 'secure' => $secureCookies],
        ],
        'response' => [
            'on beforeSend' => static function ($event): void {
                $headers = $event->sender->headers;
                $headers->set('X-Content-Type-Options', 'nosniff');
                $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
                $headers->set('X-Frame-Options', 'DENY');
                $route = \Yii::$app->requestedRoute;
                if (str_starts_with($route, 'admin/')
                    || str_starts_with($route, 'checkout/')
                    || in_array($route, ['website/cos', 'website/cauta-produse'], true)) {
                    $headers->set('X-Robots-Tag', 'noindex, nofollow');
                }
                $headers->set('Content-Security-Policy',
                    "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; "
                    . "form-action 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; "
                    . "img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'");
                $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
                if (getenv('APP_HTTPS') === '1') {
                    $headers->set('Strict-Transport-Security', 'max-age=31536000');
                }
            },
        ],
        'session' => [
            'cookieParams' => ['httponly' => true, 'sameSite' => \yii\web\Cookie::SAME_SITE_LAX, 'secure' => $secureCookies],
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'user' => [
            'identityClass' => \app\models\AdminUser::class,
            'enableAutoLogin' => false,
            'authTimeout' => 1800,
            'absoluteAuthTimeout' => 28800,
            'loginUrl' => ['admin/admin-anna-accesorii'],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => \yii\mail\MailerInterface::class,
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                    'logVars' => [],
                ],
            ],
        ],
        'db' => $db,

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '' => 'website/home',
                'GET despre-noi' => 'website/despre-noi',
                'GET produse' => 'website/produse',
                'GET produse/cauta' => 'website/cauta-produse',
                'GET site-version' => 'website/site-version',
                'GET sitemap.xml' => 'website/sitemap',
                'GET produse/<id:\d+>' => 'website/detalii-produs',

                // SHOPPING CART ENDPOINTS
                'POST adauga-in-cos' => 'website/adauga-in-cos',
                'GET cos' => 'website/cos',
                'PUT actualizeaza-cos' => 'website/actualizeaza-cos',
                'DELETE sterge-din-cos' => 'website/sterge-din-cos',

                'GET plaseaza-comanda' => 'checkout/index',
                'POST plaseaza-comanda' => 'checkout/submit',
                'GET comanda/<number:[A-F0-9]{16}>' => 'checkout/confirm',
                'GET politica-confidentialitate' => 'checkout/privacy',

                //admin
                'GET admin-anna-accesorii' => 'admin/admin-anna-accesorii',
                'POST admin-anna-accesorii' => 'admin/admin-anna-accesorii',

                'GET admin/dashboard-anna-accesorii' => 'admin/dashboard-anna-accesorii',
                'POST admin/site-refresh' => 'admin/publish-site-refresh',
                'GET admin/products' => 'admin/products-anna-accesorii',
                'POST admin/products/create' => 'admin/add-product-anna-accesorii',
                'GET admin/products/create' => 'admin/add-product-anna-accesorii',
                'GET admin/products/<id:\d+>/edit' => 'admin/edit-product-anna-accesorii',
                'POST admin/products/<id:\d+>/edit' => 'admin/edit-product-anna-accesorii',
                'POST admin/products/<id:\d+>/delete' => 'admin/delete-product-anna-accesorii',
                'POST admin/products/<id:\d+>/move' => 'admin/move-product-anna-accesorii',
                'GET admin/categories' => 'admin/categories-anna-accesorii',
                'POST admin/categories/create' => 'admin/add-category-anna-accesorii',
                'GET admin/categories/create' => 'admin/add-category-anna-accesorii',
                'GET admin/categories/<id:\d+>/edit' => 'admin/edit-category-anna-accesorii',
                'POST admin/categories/<id:\d+>/edit' => 'admin/edit-category-anna-accesorii',
                'POST admin/categories/<id:\d+>/delete' => 'admin/delete-category-anna-accesorii',
                'POST admin/categories/<id:\d+>/move' => 'admin/move-category-anna-accesorii',
                'POST admin/logout' => 'admin/logout',
            ],
        ],

    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
