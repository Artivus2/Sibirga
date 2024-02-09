<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

use frontend\assets\AppAsset;
use frontend\controllers\Assistant as Assistant;
use frontend\models\UpdateArchive;
use yii\web\View;

/**
 * БЛОК ПРИНУДИТЕЛЬНОГО ОБНОВЛЕНИЯ КЕША БРАУЗЕРА
 */
$version = date("Y-m-d H:i:s");
if (!(defined('AMICUM_NEED_UPDATE') ? AMICUM_NEED_UPDATE : false)) {
    $version_model = UpdateArchive::find()->orderBy(['date_time' => SORT_DESC])->one();
    if ($version_model and $version_model['release_number']) {
        $version = $version_model['release_number'];
    }
}

$amicumWebsocketString1 = AMICUM_CONNECT_STRING_WEBSOCKET;
$amicumWebsocketString2 = AMICUM_CONNECT_STRING_WEBSOCKET_OUTER;
$amicumWebsocketString3 = AMICUM_CONNECT_STRING_WEBSOCKET_INNER;
$this->registerJsVar('AMICUM_CONNECT_STRING_WEBSOCKET', $amicumWebsocketString1);
$this->registerJsVar('AMICUM_CONNECT_STRING_WEBSOCKET_OUTER', $amicumWebsocketString2);
$this->registerJsVar('AMICUM_CONNECT_STRING_WEBSOCKET_INNER', $amicumWebsocketString3);

$this->registerJsVar('AMICUM_DEFAULT_SHIFTS', Assistant::GetCountShifts());

$this->title = "Тестовая страница Unity";

$getSourceData = 'let backendMineId = ' . json_encode($mine_id) . ',
typicalObjects = ' . json_encode($typicalObjects) . ', 
objectIdsForInit = ' . json_encode($objectIdsForInit) . ', 
kindObjectIdsForInit = ' . json_encode($kindObjectIdsForInit) . ', 
place = ' . json_encode($place) . ';';

$this->registerJs($getSourceData, View::POS_HEAD, 'unity-positioning-system');


$this->registerCssFile('/css/jquery-ui.min.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/jquery-ui.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);


$this->registerCssFile('/css/unity.css', ['depends' => [AppAsset::className()]]);

$this->registerCssFile('unity-positioning-system/TemplateData/style.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/unity-positioning-system/TemplateData/UnityProgress.js', ['depends' => [AppAsset::className()], 'position' => View::POS_HEAD]);
$this->registerJsFile('/unity-positioning-system/UnityLoader.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
//


$this->registerJsFile('/js/common_functions.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);

$files = scandir($_SERVER['DOCUMENT_ROOT'] . "/web/unity-vue-directory");

foreach ($files as $file) {

    if (preg_match('/^[A-Za-z0-9]*\.js$/', $file)) {
        $this->registerJsFile('/unity-vue-directory/' . $file . '?ver=' . $version, ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
    }
}
if (is_dir($_SERVER['DOCUMENT_ROOT'] . "/web/unity-vue-directory/js")) {
    $files = scandir($_SERVER['DOCUMENT_ROOT'] . "/web/unity-vue-directory/js");

    foreach ($files as $file) {
        $this->registerJsFile('/unity-vue-directory/js/' . $file . '?ver=' . $version, ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
    }
}

if (is_dir($_SERVER['DOCUMENT_ROOT'] . "/web/unity-vue-directory/css")) {
    $files = scandir($_SERVER['DOCUMENT_ROOT'] . "/web/unity-vue-directory/css");

    foreach ($files as $file) {
        $this->registerCssFile('/unity-vue-directory/css/' . $file . '?ver=' . $version, ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
    }
}

$this->registerJsFile('/js/unity-positioning-system.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);

?>

<!--Прелоадер-->
<div id="preload" class="hidden">
    <div class="circle-container">
        <div id="circle_preload"></div>
        <h4 class="preload-title">Идёт загрузка</h4>
    </div>
</div>

<div id="app"></div>


<div class="webgl-content" id="webglContent">
    <!--Меню "Слои(видимость объектов на схеме)"-->
    <div class="webgl-content-menu" id="layerModal">
        <div class="webgl-content-menu-header">
            <span class="webgl-content-menu-header-logo"></span>
                <span class="webgl-content-menu-header-title">Слои (видимость объектов на схеме)</span>
                <span class="webgl-content-menu-header-close" id="closeMenuLayerId">&#10006;</span>
            </div>
            <div class="webgl-content-menu-body"></div>
        </div>
        <canvas id="gameContainer" tabindex="1"></canvas>

        <div id="unity-loading-bar">
            <div id="unity-logo"></div>
            <div id="unity-progress-bar-empty">
                <div id="unity-progress-bar-full"></div>
            </div>
        </div>
    </div>


