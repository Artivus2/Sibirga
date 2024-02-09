<?php
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use yii\web\View;
use yii\bootstrap5\Tabs;
use frontend\assets\AppAsset;
use frontend\models\User;

$this->registerCssFile('/css/index.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/index.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
/** @var yii\web\View $this */
$this->title = 'Гараж';
//$session = Yii::$app->session;
//$sess_id = session_id();

?>
<div class="main-index">
    <div class="container-upper-panel">
	<div class="upper-panel-main-text">Система учета оборудования АГК</div>
    </div>
</div>




