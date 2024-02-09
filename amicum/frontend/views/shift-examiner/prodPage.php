<?php
/* @var $this yii\web\View */


use frontend\assets\AppAsset;
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


$this->registerCssFile('/css/jquery-ui.min.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/jquery-ui.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/common_functions.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);

$files = scandir($_SERVER['DOCUMENT_ROOT'] . "/web/dash_board_prod");

foreach ($files as $file) {

    if (preg_match('/^[A-Za-z0-9]*\.js$/', $file)) {
        $this->registerJsFile('/dash_board_prod/' . $file . '?ver=' . $version, ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
    }
}

?>
