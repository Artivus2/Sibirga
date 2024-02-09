<?php

use yii\web\View;
use frontend\assets\AppAsset;

$this->title = "Тестовая страница Unity";
$getSourceData = 'let mine_id = ' . json_encode($mine_id) . '
place = ' . json_encode($place) . ';';
$this->registerJs($getSourceData, View::POS_HEAD, 'unity-js');
$this->registerCssFile('unity-for-test/TemplateData/style.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/jquery.contextMenu.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/jquery.datetimepicker.min.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/jquery-ui.min.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/unity.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/unity-for-test/TemplateData/UnityProgress.js', ['depends' => [AppAsset::className()], 'position' => View::POS_HEAD]);
$this->registerJsFile('/js/jquery.contextMenu.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/jquery-ui.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/jquery.datetimepicker.full.min.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/moment-with-locales.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/Chart.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/hammer.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/chartjs-plugin-zoom.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
//$this->registerJsFile('/js/unityChat.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/unity-for-test.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/UnityLoader.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/add-edge-unity.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
?>

<!--Прелоадер-->
<div id="preload" class="hidden">
    <div class="circle-container">
        <div id="circle_preload"></div>
        <h4 class="preload-title">Идёт загрузка</h4>
    </div>
</div>
<div id="unity-container" class="unity-desktop">
    <canvas id="unity-canvas" width=1366 height=768></canvas>
    <div id="unity-loading-bar">
        <div id="unity-logo"></div>
        <div id="unity-progress-bar-empty">
            <div id="unity-progress-bar-full"></div>
        </div>
    </div>
    <div id="unity-warning"> </div>
    <div id="unity-footer">
        <div id="unity-webgl-logo"></div>
        <div id="unity-fullscreen-button"></div>
        <div id="unity-build-title">amicum_unity</div>
    </div>
</div>
<!--Прелоадер-->

