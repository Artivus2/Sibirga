<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Main frontend application asset bundle.
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
		'/css/header.css',
		'/css/style.css',
        '/css/fonts.css'
    ];
    public $js = [
		'/js/bootstrap.min.js',
        '/js/init_tokens.js',
        '/js/bootstrap-notify.min.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
		'\yii\web\JqueryAsset'
    ];
}
