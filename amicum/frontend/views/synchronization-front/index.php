<?php
use frontend\assets\AppAsset;
use yii\web\View;
$this->registerCssFile('/css/synchronization-front.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()],'position' =>
    View::POS_END]);
$this->registerJsFile('/js/common_functions.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/synchronization_front.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
?>

<!--Прелоадер-->
<div id="preload" class="hidden">
    <div class="circle-container">
        <div id="circle_preload"></div>
        <h4 class="preload-title">Идёт загрузка</h4>
    </div>
</div>
<!--Прелоадер-->

<h1 class="text-center">Синхронизация справочников SAP</h1>
<h2 class="text-center"><button id="runSynchronization" class="btn btn-primary">Запустить синхронизацию</button></h2>
<div id="output"></div>