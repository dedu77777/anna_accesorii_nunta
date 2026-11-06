<?php

namespace app\assets;

use yii\web\AssetBundle;

class WebsiteAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/global.css',
        'css/header.css',
        'css/footer.css',
        'css/home.css',
        'css/products.css',
        'css/desprenoi.css',
        'css/website-motion.css',
        'css/detailsProducts.css',
        'css/checkout.css',
        'css/cart-toast.css',
    ];

    public $js = [
        'js/website-motion.js',
        'js/site-refresh.js',
        'js/product-search.js',
        'js/cart-ui.js',
    ];

    public $depends = [
        \yii\web\YiiAsset::class,
    ];
}
