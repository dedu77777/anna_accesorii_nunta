<?php

declare(strict_types=1);

namespace app\assets;

use yii\bootstrap5\BootstrapAsset;
use yii\web\AssetBundle;

final class AdminDashboardAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/global.css',
        'css/admin-dashboard.css',
    ];
    public $depends = [
        BootstrapAsset::class,
    ];
}
