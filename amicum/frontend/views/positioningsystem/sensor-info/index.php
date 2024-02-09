<?php

use yii\web\View;
use frontend\assets\AppAsset;

$getSourceData = 'mine = ' . json_encode($mine) . ';';
$this->registerJs($getSourceData, View::POS_HEAD, 'sensor-info-js');
$this->registerCssFile('/css/jquery.contextMenu.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/jquery.datetimepicker.min.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/sensor-info.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/jquery.contextMenu.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/jquery.datetimepicker.full.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/moment-with-locales.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/Chart.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/hammer.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/chartjs-plugin-zoom.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/xlsx.core.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/FileSaver.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/sensor-info.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->title = 'Контроль объектов АС';
?>
<!--Прелоадер-->
<div id="preload" class="hidden">
    <div class="circle-container">
        <div id="circle_preload"></div>
        <h4 class="preload-title">Идёт загрузка</h4>
    </div>
</div>

<!-- Шапка таблицы -->
<div class="handbook-header">
    <!-- Кнопка экспорта -->
    <button class="content-header-button btn-icn-1" title="Экспортировать данные в Excel документ" id="excelExport">Выгрузить в Excel</button>
    <div class="handbook-header__search-container">
        <!-- Кнопка фильтрации -->
        <button class="in-search-button filter-button icon-add-current-btn" id="filterButton"
                title="Добавить фильтр"></button>
        <div class="filter-block" id="pasteFiltersHere">

        </div>
        <input class="handbook-header__input" id="searchInput" onfocus="this.focused=true;" onblur="this.focused=false;"
               placeholder="Введите поисковой запрос">
        <button class="clear-search-button" id="searchClear"></button>
        <button class="handbook-header__search-button" id="searchButton"></button>
        <!--Выпадашка с фильтрами-->
        <div class="filter-container" id="filterContainer">
            <div class="filter-item" id="filterItem-1">Актуальность</div>
            <div class="filter-item" id="filterItem-2">Местоположение</div>
            <div class="filter-item" id="filterItem-3">Состояние</div>
            <div class="filter-item" id="filterItem-4">Объект</div>
        </div>
        <div class="more-filter-container" id="moreFilterContainer"></div>
        <!-- Окошко выбора значений фильтра не дата/время -->
        <div class="filter-inner-container" id="filterInnerContainer">
        </div>
    </div>
    <div class="handbook-header__refresh-time-container">
        <span class="handbook-header_refresh-time-title">Время обновления данных:</span>
        <div class="handbook-header__refresh-time-dropdown" id="use-dropdown" data-time="20">
            <span>20 сек</span>
            <span class="caret refresh-time-caret"></span>
        </div>
        <button title="Обновить данные" id="instant-refresh">
            <div class="glyphicon glyphicon-refresh" id="refresh-icon"></div>
        </button>
    </div>
</div>
<!-- Таблица с данными -->
<div class="content-body">
    <div class="handbook-content__header">
        <div class="handbook-content__header__element" style="width: 4%; display: flex; justify-content: center;"
             id="header-sensor_id-1">№ п/п
        </div>
        <div class="handbook-content__header__element" id="header-sensor_title-2" style="width: 25%;">Наименование
            объекта АС
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element" id="header-network_id-3" style="width: 12%;">Сетевой идентификатор
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element" id="header-status-4" style="width: 25%;">Состояние
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element" id="header-place_title-5" style="width: 25%;">Местоположение
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element" id="header-place_title-6" style="width: 9%;">Заряд батареи
            <span class="glyphicon"></span>
        </div>
    </div>
    <div class="handbook-content__body" id="contentBody">
    </div>
</div>
<div class="handbook-content__footer">
    <div class="handbook-content__footer__pagination">
    </div>
</div>
<!-- Выпадашка с временем обновления -->
<div class="reload-time-dropdown" id="dropdown">
    <div class="dropdown-element" id="dpd-1" data-time="20"><span>20 сек</span></div>
    <div class="dropdown-element" id="dpd-2" data-time="40"><span>40 сек</span></div>
    <div class="dropdown-element" id="dpd-3" data-time="60"><span>1 мин</span></div>
    <div class="dropdown-element" id="dpd-4" data-time="120"><span>2 мин</span></div>
    <div class="dropdown-element" id="dpd-5" data-time="180"><span>3 мин</span></div>
    <div class="dropdown-element" id="dpd-6" data-time="240"><span>4 мин</span></div>
    <div class="dropdown-element" id="dpd-7" data-time="300"><span>5 мин</span></div>
    <div class="dropdown-element" id="dpd-8" data-time="600"><span>10 мин</span></div>
    <div class="dropdown-element" id="dpd-9" data-time="1200"><span>20 мин</span></div>
    <div class="dropdown-element" id="dpd-10" data-time="1800"><span>30 мин</span></div>
    <div class="dropdown-element" id="dpd-11" data-time="3600"><span>1 час</span></div>
</div>
<!-- Модальное окно со всеми параметрами -->
<div class="modal fade" id="all-parameters">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title modal-params-title">Параметры</h4>
            </div>
            <div class="modal-body">
                <div class="modal-table-header-container" style="display: flex; width: 100%;">
                    <div class="modal-table-header-element" style="width: 25%; border-right: 1px solid #adadad;"><span>Наименование параметра</span>
                    </div>
                    <div class="modal-table-header-element" style="width: 20%; border-right: 1px solid #adadad;"><span>Справочный</span>
                    </div>
                    <div class="modal-table-header-element" style="width: 20%; border-right: 1px solid #adadad;"><span>Измеряемый</span>
                    </div>
                    <div class="modal-table-header-element" style="width: 20%; border-right: 1px solid #adadad;"><span>Вычисляемый</span>
                    </div>
                    <div class="modal-table-header-element" style="width: 15%;"><span>Единица измерения</span></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="instant-refresh-modal" class="btn btn-primary btn-refresh">Обновить</button>
            </div>
        </div><!-- /.модальное окно-Содержание -->
    </div><!-- /.модальное окно-диалог -->
</div><!-- /.модальное окно -->

<!-- Модальное окно по газам -->
<div class="modal fade" id="objectParameterChart">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    График изменения параметра <span class="gas-title"></span>
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="Закрыть окно">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="padding-style">

                    <div class="choose-date-block">
                        <div class="choose-date-block-info">
                            <span>Анализируемое время с</span>
                            <div class="choose-date-block-time">
                                <div id="dateStart" class="trapezoid">
                                    <img src="/img/calendar.png" alt="calendar">
                                    <span class="dateStart select-filter-type date-span"></span>
                                    <img src="/img/dataTime.png" alt="dataTime">
                                    <span class="dateStart select-filter-type date-span"></span>
                                    <!--                                    <input type="datetime-local" >-->
                                </div>
                                <span>по</span>
                                <div id="dateEnd" class="trapezoid">
                                    <img src="/img/calendar.png" alt="calendar">
                                    <span class="dateEnd select-filter-type date-span"></span>
                                    <img src="/img/dataTime.png" alt="dataTime">
                                    <span class="dateEnd select-filter-type date-span"></span>
                                    <!--                                    <input type="datetime-local" >-->
                                </div>
                            </div>
                            <button id="drawChartBtn"><img src="/img/magnifier-blue.png" alt="magnifier"></button>
                        </div>
                    </div>

                </div>
                <div id="chartContainer" class="padding-style chart-container">
                    <div id="unit"></div>
                    <div id="chart_div">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>
            </div>
        </div><!-- /.модальное окно-диалог -->
    </div><!-- /.модальное окно -->
</div>
<!--<script type="module" scr="../../web/js/echarts.min.js"></script>-->
