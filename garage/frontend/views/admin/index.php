<?php
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use yii\web\View;
use yii\bootstrap5\Tabs;
use frontend\assets\AppAsset;
use frontend\models\User;
use yii\grid\GridView;
use yii\data\ActiveDataProvider;
use frontend\models\Garage;


$this->registerCssFile('/css/admin.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/admin.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
//$this->registerJsFile('/js/three.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
//$this->registerJsFile('/js/three.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
//$this->registerJsFile('/js/TextGeometry.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
/** @var yii\web\View $this */
if ($model==0) {
?>
		    <div class="main-index-admin">
		        <div class="container-upper-panel">
		    	<div class="upper-panel-main-text">У вас нет прав для просмотра содержимого данной страницы</div>
		        </div>
		        <div class="container-upper-panel">
		    	<div class="upper-panel-main-text">
		    </div> </div> </div>
<?php
} else {

$files = scandir($_SERVER['DOCUMENT_ROOT'] . "vue");

foreach ($files as $file) {

    if (preg_match('/^[A-Za-z0-9]*\.js$/', $file)) {
        $this->registerJsFile('/vue/' . $file, ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
    }
}
if (is_dir($_SERVER['DOCUMENT_ROOT'] . "/vue/js")) {
    $files = scandir($_SERVER['DOCUMENT_ROOT'] . "/vue/js");

    foreach ($files as $file) {
        $this->registerJsFile('/vue/js/' . $file, ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
    }
}

if (is_dir($_SERVER['DOCUMENT_ROOT'] . "/vue/css")) {
    $files = scandir($_SERVER['DOCUMENT_ROOT'] . "/vue/css");

    foreach ($files as $file) {
        $this->registerCssFile('/vue/css/' . $file, ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
    }
}
//$this->title = 'Админка';
//$session = Yii::$app->session;
//$sess_id = session_id();
?>
<div id="app"></div>
</div>
<?php
}
?>