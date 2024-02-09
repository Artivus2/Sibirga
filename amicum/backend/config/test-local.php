<?php
return [
    'components' => [
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'mail.nic.ru',
                'username' => 'sap@pfsz.ru',
                'password' => 'DZUZW8FfC2Wug',
                'port' => 465,
                'encryption' => 'ssl',
            ],
            'messageConfig' => [
                'charset' => 'UTF-8',
            ],
            'useFileTransport' => true,
            //'fileTransportPath' => 'C:\xampp\htdocs\amicum2\backend\runtime\mail',
        ],
    ],
];
