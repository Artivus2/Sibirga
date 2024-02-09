<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

$config = [
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'gfKmHHqs2T9dIbs9xPrR_vCjysLukuwD',
        ],
    ],
];
if (YII_DEBUG_FULL_STATUS || YII_DEBUG_FRONTEND_STATUS) {
    if (!YII_ENV_TEST) {
        // configuration adjustments for 'dev' environment
        $config['bootstrap'][] = 'debug';
        $config['modules']['debug'] = [
            'class' => 'yii\debug\Module',
            'traceLine' => '<a href="phpstorm://open?url={file}&line={line}">{file}:{line}</a>',
//	'allowedIPs' => ['192.168.31.1', '::1'],
//            'allowedIPs' => ['*'],
        ];

        $config['bootstrap'][] = 'gii';
        $config['modules']['gii'] = [
            'class' => 'yii\gii\Module',
//	 'allowedIPs' => ['192.168.31.1', '::1'],
            'allowedIPs' => ['*'],
        ];
    }
}


return $config;
