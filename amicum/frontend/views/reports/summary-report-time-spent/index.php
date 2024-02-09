<?php
use yii\web\View;
use yii\helpers\Html;
use macgyer\yii2materializecss\widgets\grid\GridView;
use macgyer\yii2materializecss\widgets\form\ActiveForm;
use kartik\date\DatePicker;
$getSourceData = 'departmentList = '.json_encode($departmentList).'
placeList = '.json_encode($placeList).'
faceList = '.json_encode($faceList).';';
$this->registerJs($getSourceData, View::POS_HEAD, 'summary-report-js');
$this->registerCssFile('/css/summary-report.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/pickmeup.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
//$this->registerCssFile('/css/summary-report-media.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/js/jquery.datetimepicker.full.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/xlsx.core.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/Blob.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/FileSaver.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/Chart.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/summary-report-time-spent.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerCssFile('/css/Static.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->title = 'Время нахождения персонала по зонам';
?>
<style>
    #menuL3 {
        background-color: #6e6f70;
        color: white;
    }
    .content-body {
        width: 200%;
        font-size: 12px;
    }
    @media print {
        .content-body {
            width: 100%;
            font-size: 18px !important;
            overflow-x: hidden;
        }
        .right-side {
            height: 100vh;
        }
        .table-body {
            height: 99vh;
        }
        .average-time {
            font-size: 18px !important;
        }
        .right-meanings-table {
            font-size: 18px !important;
        }
    }
</style>

<!--Прелоадер-->
<div id="preload" class="hidden">
    <div class="circle-container">
        <div id="circle_preload"></div>
        <h4 class="preload-title">Идёт загрузка</h4>
    </div>
</div>
<!--Прелоадер-->
<p id="captionPrint">Время нахождения персонала по зонам</p>
<!-- Основной контейнер для левой и правой части -->
<div class="col-xs-12 main-container">
    <!-- Контейнер для левой части -->
    <div class="col-xs-2 left-side">
        <?php require_once "../views/reports/menu.php" ?>
    </div>
    <!-- Контейнер для правой части -->
    <div class="col-xs-10 right-side" id="rightSidePhp">
        <!-- Контейнер для шапки -->
        <div class="content-header">
            <div class="left-side-header">
                <!-- Кнопка экспорта -->
                <button class="content-header-button btn-icn-1" title="Экспортировать данные в Excel документ" id="excelExport">Выгрузить в Excel</button>
                <!-- Кнопка печати -->
                <a href ="/print-page" target="_blank"><button class="content-header-button btn-icn-2" style="padding-left: 33px;" title="Предпросмотр документа" id="printViewButton">Предпросмотр</button></a>
                <!-- Кнопка количества нарушений -->
                <button class="gr-btn page-switch-btn glyphicon glyphicon-stats" id="buildChart" title="Показать график"><span style="margin-left: 5px;">График</span></button>
                <!-- Контейнер поиска -->
                <div id="searchContainer" class="search-container col-xs-12">
                    <div class="col-xs-10 search-container-left">
                        <!-- Блок фильтрации -->
                        <div class="filter-block" id="pasteFiltersHere">
                            <!-- Кнопка фильтрации -->
                            <button class="in-search-button filter-button icon-add-current-btn" id="filterButton" title="Добавить фильтр" style="padding-left: 4px;"></button>
                        </div>
                        <!-- Сам поиск -->
                        <input type="search" placeholder="Введите поисковой запрос..." class="search" id="search" onfocus="this.focused=true;" onblur="this.focused=false;">
                    </div>
                    <div class="col-xs-2 search-container-right">
                        <!--Кнопка очистки строки поиска и таблицы-->
                        <button class="clear-search-button" id="searchClear"></button>
                        <!-- Кнопка поиска -->
                        <button class="in-search-button search-button icon-filter-search-btn" id="searchButtonBlue" style="padding-left: 4px;"></button>
                    </div>
                </div>
                <div class="handbook-header__refresh-time-container">
                    <button title="Обновить данные" class="refresh-button">
                        <span class="glyphicon glyphicon-refresh" ></span>
                    </button>
                </div>
            </div>
        </div>
        <div class="col-xs-12 table-body" id="graphicLegacy">
            <!-- Таблица -->
            <div class="content-body col-xs-10" id="contentBody">
                <!-- Заголовки -->
                <div class="body-th" id="bodyTH" style="height: auto; width: 100%;">
                    <div style="width: 30%; display: flex;">
                        <div class="body-th-item sort-field" data-field="FIO"  " style="width: 24%; display: flex; padding: 0;"><span style="margin: auto;">ФИО</span></div>
                        <div class="body-th-item sort-field" data-field="date_work" style="width: 16%; display: flex; padding: 0;"><span style="margin: auto;">Дата</span></div>
                        <div class="body-th-item sort-field" data-field="smena" style="width: 12%; display: flex; padding: 0;"><span style="margin: auto;">Смена</span></div>
                        <div class="body-th-item sort-field" data-field="department_title" style="width: 26%; display: flex; padding: 0;"><span style="margin: auto;">Подразделение</span></div>
                        <div class="body-th-item sort-field" data-field="type_worker_title" style="width: 22%; display: flex; padding: 0;"><span style="margin: auto;">Тип объекта</span></div>
                    </div>
                    <div style="width: 50%;">
                        <div class="average-time" id="averageTimeOutside">
                            <span style="width: 17%; background-color: #7e158426 !important; padding-top: 5px; padding-bottom: 5px;">Итоговое среднее время на поверхности, мин</span>
                            <span style="width: 17%; background-color: #154b841c !important; padding-top: 5px; padding-bottom: 5px;">Итоговое среднее время работы в забое, мин</span>
                            <span style="width: 17%; background-color: #a96d162b !important; padding-top: 5px; padding-bottom: 5px;">Среднее время следования в забой, мин</span>
                            <span style="width: 17%; background-color: #006d094d !important; padding-top: 5px; padding-bottom: 5px;">Среднее время выхода из забоя, мин</span>
                            <span style="width: 16%; background-color: #0d0d0d30 !important; padding-top: 5px; padding-bottom: 5px;">Итоговое время в шахте, мин</span>
                            <span style="width: 16%; background-color: #c6000c40 !important; padding-top: 5px; padding-bottom: 5px;">Итоговое время работы, мин</span>
                        </div>
                        <div class="meanings" id="meanings">
                            <div class="meanings-first" style="width: 17%; display: flex; background-color: #7e158426 !important;">
                                <span class="meanings-inner">П</span>
                                <span class="meanings-inner">Ф</span>
                                <span class="meanings-inner">+/-</span>
                            </div>
                            <div class="meanings-second" style="width: 17%; display: flex; background-color: #154b841c !important;">
                                <span class="meanings-inner">П</span>
                                <span class="meanings-inner">Ф</span>
                                <span class="meanings-inner">+/-</span>
                            </div>
                            <div class="meanings-third" style="width: 17%; display: flex; background-color: #a96d162b !important;">
                                <span class="meanings-inner">П</span>
                                <span class="meanings-inner">Ф</span>
                                <span class="meanings-inner">+/-</span>
                            </div>
                            <div class="meanings-fourth" style="width: 17%; display: flex; background-color: #006d094d !important;">
                                <span class="meanings-inner">П</span>
                                <span class="meanings-inner">Ф</span>
                                <span class="meanings-inner">+/-</span>
                            </div>
                            <div class="meanings-fifth" style="width: 16%; display: flex; background-color: #0d0d0d30 !important;">
                                <span class="meanings-inner">П</span>
                                <span class="meanings-inner">Ф</span>
                                <span class="meanings-inner">+/-</span>
                            </div>
                            <div class="meanings-sixth" style="width: 16%; display: flex; background-color: #c6000c40 !important;">
                                <span class="meanings-inner">П</span>
                                <span class="meanings-inner">Ф</span>
                                <span class="meanings-inner">+/-</span>
                            </div>
                        </div>
                    </div>
                    <div class="right-meanings-table" style="width: 20%; display: flex;">
                        <div class="body-th-item" style="width: 43%; display: flex; padding: 0; line-height: 1;"><span style="margin: auto;">Наименование выработки забоя</span></div>
                        <div class="body-th-item" style="width: 27%; display: flex; padding: 0;"><span style="margin: auto;">Забой</span></div>
                        <div class="body-th-item" style="width: 30%; display: flex; padding: 0; border-right: none;"><span style="margin: auto;">Предприятие</span></div>
                    </div>
                </div>
                <!-- Контент -->
                <div class="body-tb"></div>
            </div>
        </div>
        <!-- НИЖНЕЕ ПОЛЕ ПРОКРУТКИ -->
        <div class="handbook-content__footer">
            <div class="handbook-content__footer__pagination">
                <button class="handbook-page-switch"><<</button>
                <button class="handbook-page-switch"><</button>
                <button class="handbook-page-switch numeric">1</button>
                <button class="handbook-page-switch numeric">2</button>
                <button class="handbook-page-switch numeric">3</button>
                <button class="handbook-page-switch numeric">4</button>
                <button class="handbook-page-switch numeric">5</button>
                <button class="handbook-page-switch">></button>
                <button class="handbook-page-switch">>></button>
            </div>
        </div>
    </div>
</div>

<!-- Окошко фильтрации -->
<div class="filter-container" id="filterContainer">
    <div class="filter-item " id="filterItem-2">Смена</div>
    <div class="filter-item " id="filterItem-3">Подразделение</div>
    <div class="filter-item " id="filterItem-4">Тип объекта</div>
    <div class="filter-item " id="filterItem-5">Наименование выработки</div>
</div>

<!-- Окошко выбора значений фильтра не дата/время -->
<div class="filter-inner-container" id="filterInnerContainer">
</div>

<!-- Окошко выбора значений фильтра дата/время -->
<div class="filter-date-container" id="filterDate">
</div>

<!-- Контейнер для графика -->
<div class="graphic-container" id="graphContainer">
    <div class="this-container" id="thisContainer">
        <canvas id="timeSpentChart"></canvas>
    </div>
</div>

<!-- Контейнер для лишних фильтров -->
<div class="more-filter-container" id="moreFilterContainer"></div>
