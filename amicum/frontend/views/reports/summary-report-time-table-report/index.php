<?php
use yii\web\View;
use yii\helpers\Html;
use macgyer\yii2materializecss\widgets\grid\GridView;
use macgyer\yii2materializecss\widgets\form\ActiveForm;
use kartik\date\DatePicker;
$getSourceData = 'departmentList = '.json_encode($departmentList).';';
//kindList = '.json_encode($kindList).'
//objectList = '.json_encode($objectList).'
$this->registerJs($getSourceData, View::POS_HEAD, 'summary-report-js');
$this->registerCssFile('/css/summary-report-timecard.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/pickmeup.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/Static.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/summary-report-media.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/js/pickmeup.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/xlsx.core.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/Blob.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/FileSaver.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/highcharts.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/summary-report-time-table-report.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->title = 'Табельный отчет';
?>
<style>
    #menuL5 {
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
<p id="captionPrint">Табельный отчет</p>
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
                <a href ="/print-page" target="_blank"><button class="content-header-button btn-icn-2" style="padding-left: 33px;" title="Предпросмотр документа" id="printViewButton">Предпросмотр</button></a>
                <!-- Кнопка количества нарушений -->
                <button class="page-switch-button-3 page-switch-btn" id="page1" title="Показать общий отчет"></button>
                <!-- Кнопка проведенного времени -->
                <button class="page-switch-button-4 page-switch-btn" id="page2" title="Показать отчет больше 4 часов"></button>
                <!-- Кнопка проведенного времени -->
                <button class="page-switch-button-5 page-switch-btn" id="page3" title="Показать отчет меньше 4 часов"></button>
                <!-- Контейнер поиска -->
                <div class="search-container col-xs-12">
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
            <div class="content-body col-xs-10" style="width: 150%;">
                <!-- Заголовки -->
                <div class="body-th" id="bodyTH" style="">
                    <div style="width: 30%; display: flex;">
                        <div class="body-th-item sort-field" data-field="company" style="width: 26%; padding-top: 9px;">Предприятие<span class="glyphicon"></span></div>
                        <div class="body-th-item sort-field" data-field="department" style="width: 26%; padding-top: 9px;">Подразделение<span class="glyphicon"></span></div>
                        <div class="body-th-item sort-field" data-field="FIO" style="width: 26%; padding-top: 9px;">ФИО<span class="glyphicon"></span></div>
                        <div class="body-th-item sort-field" data-field="tableNumber" style="width: 11%; padding-top: 9px;">Таб. №<span class="glyphicon"></span></div>
                        <div class="body-th-item sort-field" data-field="Sum" style="width: 11%; padding-top: 9px; background-color: #28b70042;">Итого<span class="glyphicon"></span></div>

                    </div>
                    <div style="width:70%;">
                        <div class="month-div" id="monthDiv"><span> Выберите период</span></div>
                        <div class="days-div" id="daysDiv">
                            <div class="day">1</div>
                            <div class="day">2</div>
                            <div class="day">3</div>
                            <div class="day">4</div>
                            <div class="day">5</div>
                            <div class="day">6</div>
                            <div class="day">7</div>
                            <div class="day">8</div>
                            <div class="day">9</div>
                            <div class="day">10</div>
                            <div class="day">11</div>
                            <div class="day">12</div>
                            <div class="day">13</div>
                            <div class="day">14</div>
                            <div class="day">15</div>
                            <div class="day">16</div>
                            <div class="day">17</div>
                            <div class="day">18</div>
                            <div class="day">19</div>
                            <div class="day">20</div>
                            <div class="day">21</div>
                            <div class="day">22</div>
                            <div class="day">23</div>
                            <div class="day">24</div>
                            <div class="day">25</div>
                            <div class="day">26</div>
                            <div class="day">27</div>
                            <div class="day">28</div>
                            <div class="day">29</div>
                            <div class="day">30</div>
                            <div class="day">31</div>
                        </div>
                    </div>
                </div>
                <!-- Контент -->
                <div class="body-tb" id="body-table">
<!--                    <div class="row-body-table">-->
<!--                        <div class="tb-row-left">-->
<!--                            <div class="tb-row-cell"></div>-->
<!--                            <div class="tb-row-cell"></div>-->
<!--                            <div class="tb-row-cell"></div>-->
<!--                            <div class="tb-row-cell"></div>-->
<!--                            <div class="tb-row-cell"></div>-->
<!--                        </div>-->
<!--                        <div class="tb-row-right">-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                    <div class="row-body-table">-->
<!--                        <div class="tb-row-left">-->
<!--                            <div class="tb-row-cell"></div>-->
<!--                            <div class="tb-row-cell"></div>-->
<!--                            <div class="tb-row-cell"></div>-->
<!--                            <div class="tb-row-cell"></div>-->
<!--                            <div class="tb-row-cell"></div>-->
<!--                        </div>-->
<!--                        <div class="tb-row-right">-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                            <div class="body-table-day"><span></span></div>-->
<!--                        </div>-->
<!--                    </div>-->
                </div>

            </div>
        </div>
        <!-- Переключение страниц-->
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
<!--        <div class="row col-xs-12 footRow">-->
<!--            <!-- Отступ слева -->
<!--            <div class="col-xs-5 carets" style="text-align: right;">-->
<!--                <!-- Кнопка переключения на предыдущую страницу -->
<!--                <span class="caret caret-block caret-left" id="switchLeft"></span>-->
<!--            </div>-->
<!--            <!-- Контейнер с переключалкой -->
<!--            <div class="page-select col-xs-2">-->
<!--                <!-- Отображение текущей страницы и количества страниц -->
<!--                <span id="pageNumeric">Данные не загружены</span>-->
<!--            </div>-->
<!--            <!-- Отступ справа -->
<!--            <div class="col-xs-5 carets">-->
<!--                <!-- Кнопка переключения на следующую страницу -->
<!--                <span class="caret caret-block caret-right" id="switchRight"></span>-->
<!--            </div>-->
<!--        </div>-->
    </div>
</div>

<!-- Окошко фильтрации -->
<div class="filter-container" id="filterContainer">
    <div class="filter-item" id="filterItem-1">Дата</div>
    <div class="filter-item" id="filterItem-2">Подразделение</div>
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