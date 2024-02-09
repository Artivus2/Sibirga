
<?php
/* @var $this yii\web\View */
use yii\web\View;
$this->registerCssFile('/css/print-page.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/print-page.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->title = 'Печать сводного отчета';
?>


<div class="printTitle col-xs-12" style="display: flex">                                                                           <!---->
    <h1 id="captionPrint">Название сводного отчета</h1>
    <button class="content-header-button btn-icn-2" style="padding-left: 33px;" title="Печать документа" id="printButton">Печать</button>
</div>

<div class="col-xs-12 table-body">
    <!-- Таблица -->
    <div class="content-body col-xs-12" id="thisContent">
        <!-- Заголовки таблицы-->
        <div class="body-th" id="bodyTH"></div>
        <!-- Индикатор загрузки-->
        <div style="height: 100%; width: 100%; text-align: center;" title="Идет загрузка данных">
            <span id="loading" class="glyphicon glyphicon-refresh" style="display: none; font-size: 32px; margin: 30px 0 0;"></span>
        </div>
        <!-- Контент таблицы-->
        <div class="body-tb" id="body-table"></div>
    </div>

</div>