<?php
/* @var $this yii\web\View */

use yii\web\View;

$getSourceData = 'let places = '.json_encode($places). ';';
$this->registerJs($getSourceData, View::POS_HEAD, 'summary-report-js');
$this->registerJsFile('/js/pickmeup.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerCssFile('/css/pickmeup.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/js/jquery.datetimepicker.full.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/chart.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/jquery.cookie.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerCssFile('/css/chart.min.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/Static.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/summary-report.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
//$this->registerCssFile('/css/summary-report-media.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/js/summary-report-excess-density-gas.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerCssFile('/css/summary-report-excess-density-gas.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/js/xlsx.core.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/FileSaver.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' =>
    View::POS_END]);
$this->title = 'Превышение концентрации газа';
?>
<style>
    #menuL11 {
        background-color: #6e6f70;
        color: white;
    }
    @media print {
        .content-body {
            width: 100%;
            font-size: 18px;
            overflow-x: hidden;
        }
        .right-side {
            height: 100vh;
        }
        .table-body {
            height: 99vh;
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
<p id="captionPrint">Превышение концентрации газа</p>
<!-- Основной контейнер для левой и правой части -->
<div class="col-xs-12 main-container" id="ContainerGeneral">
    <!-- Контейнер для левой части -->
    <div class="col-xs-2 left-side" id="containerLeft">
        <?php require_once "../views/reports/menu.php" ?>
    </div>
    <!-- Контейнер для правой части -->
    <div class="col-xs-10 right-side">
        <!-- Контейнер для шапки -->
        <div class="content-header">
            <div class="left-side-header">
                <!-- Кнопка экспорта -->
                <button class="content-header-button btn-icn-1" title="Экспортировать данные в Excel документ"
                        id="excelExport">Выгрузить в Excel
                </button>
                <!-- Кнопка печати -->
                <a href ="/reports/print-page" target="_blank"><button class="content-header-button btn-icn-2" style="padding-left: 33px;" title="Предпросмотр документа" id="printViewButton">Предпросмотр</button></a>
                <!-- Кнопка количества нарушений -->
                <!--                <button class="page-switch-button-3 page-switch-btn" id="page1" title="Показать общий отчет"></button>-->
                <!-- Кнопка проведенного времени -->
                <!--                <button class="page-switch-button-4 page-switch-btn" id="page2" title="Показать отчет больше 4 часов"></button>-->
                <!-- Кнопка проведенного времени -->
                <!--                <button class="page-switch-button-5 page-switch-btn" id="page3" title="Показать отчет меньше 4 часов"></button>-->
                <!-- Кнопка построения графика -->
                <button class="content-header-button btn-icn-3" style="padding-left: 33px; display: none;" title="Построение графика" id="chartViewButton">График</button>
                <!-- Контейнер поиска -->
                <div id="searchContainer" class="search-container col-xs-12">
                    <div class="col-xs-10 search-container-left">
                        <!-- Блок фильтрации -->
                        <div class="filter-block" id="pasteFiltersHere">
                            <!-- Кнопка фильтрации -->
                            <button class="in-search-button filter-button icon-add-current-btn" id="filterButton" title="Добавить фильтр"></button>
                        </div>
                        <!-- Сам поиск -->
                        <input type="search" placeholder="Введите поисковой запрос..." class="search" id="search" onfocus="this.focused=true;" onblur="this.focused=false;">
                    </div>
                    <div class="col-xs-2 search-container-right">
                        <!--Кнопка очистки строки поиска и таблицы-->
                        <button class="clear-search-button" id="searchClear"></button>
                        <!-- Кнопка поиска -->
                        <button class="in-search-button search-button icon-filter-search-btn" id="searchButtonBlue"></button>
                    </div>
                </div>
                <div class="handbook-header__refresh-time-container">
                    <button title="Обновить данные" class="refresh-button">
                        <span class="glyphicon glyphicon-refresh" ></span>
                    </button>
                </div>
            </div>
        </div>
        <div class="col-xs-12 table-body">
            <!-- Кнопка для отображения графика -->
            <!--            <div class="col-xs-2 graphics-button" title="Отобразить/скрыть график" id="graphicButton">-->
            <!--                <span>График</span>-->
            <!--                <span class="glyphicon glyphicon-stats"></span>-->
            <!--            </div>-->
            <!-- Таблица -->
            <div class="content-body col-xs-10">

                <!-- Контейнер для графика -->
                <div class="graphic-container" id="graphContainer">
                    <canvas id="reportChart"></canvas>
                </div>

                <!-- Заголовки -->
                <div class="body-th" id="bodyTH">

                    <div class="body-th-item sort-field" data-field="date_time"><span>Дата</span><span class="glyphicon"></span></div>
                    <div class="body-th-item sort-field" data-field="place_title"><span>Место</span><span class="glyphicon"></span></div>
                    <div class="body-th-item sort-field" data-field="sensor_title"><span>Наименование датчика</span><span class="glyphicon"></span></div>
                    <div class="body-th-item sort-field" data-field="parameter_title"><span>Тип газа</span><span class="glyphicon"></span></div>
                    <div class="body-th-item sort-field" data-field="edge_gas_nominal_value"><span>План</span><span class="glyphicon"></span></div>
                    <div class="body-th-item sort-field" data-field="unit_title"><span>Единица измерения</span><span class="glyphicon"></span></div>
<!--                    <div class="body-th-item sort-field" data-field="date_time_start"><span>Время начала превышения</span><span class="glyphicon"></span></div>-->
<!--                    <div class="body-th-item sort-field" data-field="date_time_end"><span>Время окончания превышения</span><span class="glyphicon"></span></div>-->
<!--                    <div class="body-th-item sort-field" data-field="duration"><span>Длительность превышения</span><span class="glyphicon"></span></div>-->
                    <div class="body-th-item sort-field" data-field="max_gas_val"><span>Максимальное значение</span><span class="glyphicon"></span></div>


                </div>
                <!-- Контент -->
                <div class="content-table" id="body-table">
                </div>
            </div>
        </div>
        <!-- Переключение страниц-->
        <div class="handbook-content__footer">
            <div class="handbook-content__footer__rowsCount">Количество записей: <span id="actualRowCount">0</span></div>
            <div class="handbook-content__footer__pagination">
                <button class="handbook-page-switch"><<</button><button class="handbook-page-switch"><</button><button class="handbook-page-switch numeric">1</button><button class="handbook-page-switch">></button><button class="handbook-page-switch">>></button>
            </div>
            <div class="handbook-content__footer__show">
                Показывать по:
                <div class="handbook-content__footer__show__buttons">
                    <a class="show-pages-button">20</a><a class="show-pages-button">50</a><a class="show-pages-button">100</a><a class="show-pages-button">200</a>
                </div>
            </div>
        </div>
    </div>
    <!-- Окошко фильтрации -->
    <div class="filter-container" id="filterContainer">
        <div class="filter-item" id="filterItem-1">Место</div>
        <div class="filter-item" id="filterItem-2">Тип газа</div>
    </div>
    <!-- Окошко выбора значений фильтра не дата/время -->
    <div class="filter-inner-container" id="filterInnerContainer">
    </div>

    <!-- Окошко выбора значений фильтра дата/время -->
    <div class="filter-date-container" id="filterDate">
    </div>

    <!-- Контейнер для лишних фильтров -->
    <div class="more-filter-container" id="moreFilterContainer"></div>
</div>
