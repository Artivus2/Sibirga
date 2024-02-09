<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

use yii\web\View;

$session = Yii::$app->session;
$getSourceData = 'let departmentList = ' . json_encode($departments) .
    ', mine_id = ' . $session['userMineId'] .
    ', placesList = ' . json_encode($places) . ';';

$this->registerJs($getSourceData, View::POS_HEAD, 'summary-report-js');
$this->registerJsFile('/js/pickmeup.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerCssFile('/css/pickmeup.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/js/jquery.datetimepicker.full.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerCssFile('/css/Static.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/summary-report-motionless-general.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/summary-report.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
//$this->registerCssFile('/css/summary-report-media.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/js/jquery.cookie.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/summary-motionless-general.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerCssFile('/css/Static.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/js/xlsx.core.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/FileSaver.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->title = 'Персонал без движения, общее время';
?>
<style>
    #menuL10 {
        background-color: #6e6f70;
        color: white;
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
<p id="captionPrint">Нахождение персонала без движения (общее время)</p>
<!-- Основной контейнер для левой и правой части -->
<div class="col-xs-12 main-container" id="ContainerGeneral">
    <!-- Контейнер для левой части -->
    <div class="col-xs-2 left-side" id="containerLeft">
        <?php require_once "../views/reports/menu.php" ?>
    </div>
    <!-- Контейнер для правой части -->
    <div class="col-xs-10 right-side" id="containerRight">
        <!-- Контейнер для шапки -->
        <div class="content-header">
            <div class="left-side-header">
                <!-- Кнопка экспорта -->
                <button class="content-header-button btn-icn-1" title="Экспортировать данные в Excel документ" id="excelExport">Выгрузить в Excel</button>
                <!-- Кнопка печати -->
                <a href ="/reports/print-page" target="_blank"><button class="content-header-button btn-icn-2" style="padding-left: 33px;" title="Предпросмотр документа" id="printViewButton">Предпросмотр</button></a>
                <!--                Кнопка количества нарушений -->
                <!--                <button class="page-switch-button-3 page-switch-btn" id="page1" title="Показать общий отчет"></button>-->
                <!--                Кнопка проведенного времени -->
                <!--                <button class="page-switch-button-4 page-switch-btn" id="page2" title="Показать отчет больше 4 часов"></button>-->
                <!--                Кнопка проведенного времени -->
                <!--                <button class="page-switch-button-5 page-switch-btn" id="page3" title="Показать отчет меньше 4 часов"></button>-->
                <!-- Контейнер поиска -->
                <div id="searchContainer" class="search-container col-xs-12">
                    <div class="col-xs-10 search-container-left">
                        <!-- Блок фильтрации -->
                        <button class="in-search-button filter-button icon-add-current-btn noneClosed" id="filterButton" title="Добавить фильтр"></button>
                        <div class="form_add_mini noneClosedd" id="form_add_minii">...</div>
                        <div class="modal_field noneClosed" id="add_modal_field">
                            <div class="modal_field-shift " id="shift_str">Смена</div>
                            <div class="modal_field-departmen" id="departmen-str">Подразделение</div>
                            <div class="modal_field-location" id="location_str">Местоположение</div>
                        </div>
                        <div class="modal_add_mini noneClosedd" id="modal_add_mini"></div>
                        <div class="content-field"></div>
                        <div class="filter-block" id="pasteFiltersHere"></div>
                        <div class="filter-block-item" id="mineElement">
                            <span>Шахта</span>
                            <span class="span3">Выбрать</span>
                            <span class="caret span4"></span>
                        </div>
                        <div class="field-shift hidden-block none" id="add_shift">
                            <span>Смена </span><span id="shift-modal" class="choose-filter shift">Выбрать :
                            <div class="modal-shift" id="modal-shift-add">
                                <div>Смена 1</div>
                                <div>Смена 2</div>
                                <div>Смена 3</div>
                                <div>Смена 4</div>

                            </div>
                            </span><span id="closed-shift">×</span>
                        </div>
                        <div class="field-departmen hidden-block" id="add_department">
                            <span>Подразделение </span><span id="department-modal" class="choose-filter department">Выбрать :
                            <div class="modal-department" id="modal-department-add">
<!--                                <div>Подразделение</div>-->
                                <!--                                <div>Подразделение</div>-->
                                <!--                                <div>Подразделение</div>-->
                                <!--                                <div>Подразделение</div>-->
                            </div>
                            </span><span id="closed-departmen">×</span>
                        </div>
                        <div class="field-location hidden-block" id="add_location">
                            <span>Местоположение </span><span id="location-modal" class="choose-filter location">Выбрать :
                             <div class="modal-location" id="modal-location-add">
<!--                                <div>Местоположение</div>-->
                                 <!--                                <div>Местоположение</div>-->
                                 <!--                                <div>Местоположение</div>-->
                                 <!--                                <div>Местоположение</div>-->
                            </div>
                            </span><span id="closed-location">×</span>

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
        <div class="mine-dropdown dropdown-container hidden">
            <div class="no-data"><span>Нет данных</span></div>
        </div>
        <div class="col-xs-12 table-body">
            <!-- Кнопка для отображения графика -->
            <!--            <div class="col-xs-2 graphics-button" title="Отобразить/скрыть график" id="graphicButton">-->
            <!--                <span>График</span>-->
            <!--                <span class="glyphicon glyphicon-stats"></span>-->
            <!--            </div>-->
            <!-- Таблица -->
            <div class="content-body col-xs-10" style="width: 100%;">
                <!-- Заголовки -->
                <div class="body-th" id="bodyTH" style="height: auto;">
                    <div class="body-th-item sort-field" data-field="date_time"><span>Дата</span><span class="glyphicon"></span></div>
                    <div class="body-th-item sort-field" data-field="FIO"><span>ФИО</span><span class="glyphicon"></span></div>
                    <div class="body-th-item sort-field" data-field="tabel_number"><span>Табельный номер</span><span class="glyphicon"></span></div>
                    <div class="body-th-item sort-field" data-field="title_department"><span>Подразделение</span><span class="glyphicon"></span></div>
                    <div class="body-th-item sort-field" data-field="title_place"><span>Местоположение</span><span class="glyphicon"></span></div>
                    <div class="body-th-item sort-field" data-field="smena"><span>Смена</span><span class="glyphicon"></span></div>
                    <div class="body-th-item sort-field" data-field="motion_less_time"><span>Общее время события</span><span class="glyphicon"></span></div>
                </div>
                <!-- Контент -->
                <div class="content-table" id="body-table"></div>
            </div>
        </div>
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
    <!-- Переключение страниц-->
</div>



<!-- Окошко фильтрации -->
<div class="filter-container" id="filterContainer">
    <div class="filter-item" id="filterItem-2">Подразделение</div>
    <div class="filter-item" id="filterItem-2">Смена</div>
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
