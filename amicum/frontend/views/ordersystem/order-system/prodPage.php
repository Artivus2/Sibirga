<?php
/* @var $this yii\web\View */

### Готовая сборка vue-проекта

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
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/jquery.datetimepicker.full.min.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/common_functions.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);

$files = scandir($_SERVER['DOCUMENT_ROOT']. "/web/vue-prod");

foreach($files AS $file)
{

    if(preg_match('/^[A-Za-z0-9]*\.js$/',$file))
    {
        $this->registerJsFile('/vue-prod/'.$file . '?ver=' . $version, ['depends' => [AppAsset::className()],'position' => View::POS_END]);
    }
}

?>
