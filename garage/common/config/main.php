<?php
return [
    'id' => 'app-frontend',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'language' => 'ru-RU',
    'controllerNamespace' => 'frontend\controllers',
    'components' => [
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],

    ],
];
