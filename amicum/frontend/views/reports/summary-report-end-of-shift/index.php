<?php
use yii\web\View;

$getSourceData = 'let departmentList = '.json_encode($departmentList).',
                      companyList = '.json_encode($companyList).';';

$this->registerJs($getSourceData, View::POS_HEAD, 'summary-report-js');
$this->registerCssFile('/css/summary-report.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/pickmeup.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/Static.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/summary-report-end-of-shift.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
//$this->registerCssFile('/css/summary-report-media.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/js/jquery.datetimepicker.full.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/xlsx.core.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/Blob.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/FileSaver.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/summary-report-end-of-shift.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->title = 'Время выхода персонала по окончанию смены';
?>
<style>
    #menuL6 {
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
<p id="captionPrint">Время выхода персонала по окончанию смены</p>
<!-- Основной контейнер для левой и правой части -->
<div class="col-xs-12 main-container">
    <!-- Контейнер для левой части -->
    <div class="col-xs-2 left-side">
        <?php require_once "../views/reports/menu.php" ?>
    </div>
    <!-- Контейнер для правой части -->
    <div class="col-xs-10 right-side">
        <!-- Контейнер для шапки -->
        <div class="content-header">
            <div class="left-side-header">
                <!-- Кнопка экспорта -->
                <button class="content-header-button btn-icn-1" title="Экспортировать данные в Excel документ" id="excelExport">Выгрузить в Excel</button>
                <!-- Кнопка печати -->
                <a href ="/reports/print-page" target="_blank"><button class="content-header-button btn-icn-2" style="padding-left: 33px;" title="Предпросмотр документа" id="printViewButton">Предпросмотр</button></a>
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
            <div class="content-body col-xs-12" id="thisContent">
                <!-- Заголовки -->
                <div class="body-th" id="bodyTH">
                    <div class="body-th-item sort-field" data-field="date_work"><span>Дата смены</span><span class="glyphicon"></span></div>
                    <div class="body-th-item sort-field" data-field="FIO"><span>ФИО</span><span class="glyphicon"></span></div>
                    <div class="body-th-item sort-field" data-field="tabel_number"><span>Таб. №</span><span class="glyphicon"></span></div>
                    <div class="body-th-item sort-field" data-field="department_title"><span>Подразделение</span><span class="glyphicon"></span></div>
                    <!--                    <div class="body-th-item sort-field" data-field="smena" style="width: 8%;">Смена<span class="glyphicon"></span></div>-->
                    <div class="body-th-item sort-field" data-field="company_title"><span>Предприятие</span><span class="glyphicon"></span></div>
                    <div class="body-th-item sort-field" data-field="date_time"><span>Дата/время выхода</span><span class="glyphicon"></span></div>
                </div>
                <!-- Контент -->
                <div class="body-tb"></div>
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
                    <a class="show-pages-button">20</a><a class="show-pages-button">10</a><a class="show-pages-button">100</a><a class="show-pages-button">200</a>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Окошко фильтрации -->
<div class="filter-container" id="filterContainer">
    <!--    <div class="filter-item " id="filterItem-1">Смена</div>-->
    <div class="filter-item " id="filterItem-1">Подразделение</div>
    <div class="filter-item " id="filterItem-2">Предприятие</div>
</div>

<!-- Окошко выбора значений фильтра не дата/время -->
<div class="filter-inner-container" id="filterInnerContainer">
</div>

<!-- Окошко выбора значений фильтра дата/время -->
<div class="filter-date-container" id="filterDate">
</div>

<!-- Контейнер для графика -->
<div class="graphic-container" id="graphContainer">
</div>

<!-- Контейнер для лишних фильтров -->
<div class="more-filter-container" id="moreFilterContainer"></div>
