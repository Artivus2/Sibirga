<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
require __DIR__ . '/../config/bootstrap.php';

if (YII_DEBUG_FULL_STATUS || YII_DEBUG_BACKEND_STATUS) {
    defined('YII_DEBUG') or define('YII_DEBUG', true);
    defined('YII_ENV') or define('YII_ENV', 'dev');
} else {
    defined('YII_DEBUG') or define('YII_DEBUG', false);
    defined('YII_ENV') or define('YII_ENV', 'prod');
}

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../../common/config/main-local.php',
    require __DIR__ . '/../config/main.php',
    require __DIR__ . '/../config/main-local.php'
);

use backend\models\ErrorAmicum;

try{
    (new yii\web\Application($config))->run();
}
catch(Throwable $ex){
    $message = $ex->getMessage();
    echo nl2br("ОШИБКА БЕКЕНД\r\n");
    echo nl2br(date('Y-m-d H:i:s')."\r\n\r\nПолное содержание ошибки:\r\n ".$ex->getPrevious()."\r\n\r\nОшибка в файле: ".$ex->getFile()."\r\n\r\nКод ошибки: ".$ex->getCode()."\r\n\r\nСтрока: ".$ex->getLine()."\r\n\r\nСуть ошибки: ".$message."\r\n\r\n ");

    $controller = Yii::$app;
    ErrorAmicum::errorHandlerAmicum($ex, $controller);
}

