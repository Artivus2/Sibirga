<?php
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use yii\web\View;
use frontend\assets\AppAsset;
use yii\grid\GridView;
use yii\data\ActiveDataProvider;
use frontend\models\workJournal;


$dataProvider = new ActiveDataProvider([
'query' => workJournal::find(),
'pagination' => [
'pageSize' => 10,
],
]);


$this->registerCssFile('/css/jobs.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/materials.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/jquery-ui.min.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/arrows.css', ['depends' => [AppAsset::className()]]);
//$this->registerCssFile('/css/jqx.base.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/jobs.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/xlsx.full.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/jspdf.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/moment-with-locales.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
?>

<div class="main-index" id="main-index">
    <div class="container-upper-panel">
	<div class="upper-panel-main-text" id="test2">Журнал сменных заданий</div>
    </div>

<div class="middle-panel-jobs" id="middle-panel-jobs">
    <div class="header-content">
	<div class="pickup-date">
	<div> с <input class="datein" id="datepicker1"></input></div>
	<div>  по <input class="dateend" id="datepicker2"></input></div>
	<div class="login-button2 add-button" id="refresh">Обновить</div>
	<div class="login-button2 add-button" id="excel">Выгрузить в Excel</div>
	<div class="login-button2 add-button" id="report">Отправить на печать</div>
	</div>
    </div>
    <div class="table-content" id="table-content">
    </div>
    
    <div class="buttons">
    </div>
</div>
    
    <div class = "container-down-panel">
    
	<div class="arrow-back">
	</div>
	  <div class="arrow-back-click" id="back2">
           <i class="zmdi zmdi-chevron-left"></i>
           <i class="zmdi zmdi-chevron-left"></i>
           <i class="zmdi zmdi-chevron-left"></i>
           <i class="zmdi zmdi-chevron-left"></i>
          </div>
    </div>
</div>

