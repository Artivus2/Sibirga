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

//$this->registerJsFile('/js/jquery-3.6.0.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerCssFile('/css/jquery-ui.min.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/jquery-ui.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/common_functions.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/dash_board_common.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);

$files = scandir($_SERVER['DOCUMENT_ROOT'] . "/web/order_system_dev");

foreach ($files as $file) {

    if (preg_match('/^[A-Za-z0-9]*\.js$/', $file)) {
        $this->registerJsFile('/order_system_dev/' . $file . '?ver=' . $version, ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
    }
}

if (is_dir($_SERVER['DOCUMENT_ROOT'] . "/web/order_system_dev/js")) {
    $files = scandir($_SERVER['DOCUMENT_ROOT'] . "/web/order_system_dev/js");

    foreach ($files as $file) {
        $this->registerJsFile('/order_system_dev/js/' . $file . '?ver=' . $version, ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
    }
}

if (is_dir($_SERVER['DOCUMENT_ROOT'] . "/web/order_system_dev/css")) {
    $files = scandir($_SERVER['DOCUMENT_ROOT'] . "/web/order_system_dev/css");

    foreach ($files as $file) {
        $this->registerCssFile('/order_system_dev/css/' . $file . '?ver=' . $version, ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
    }
}
?>
