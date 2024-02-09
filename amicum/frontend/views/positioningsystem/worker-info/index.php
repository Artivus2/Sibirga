<?php
use yii\web\View;
$getSourceData = 'let status = '.json_encode($status).';';
$this->registerJs($getSourceData, View::POS_HEAD, 'worker-info-js');
$this->registerCssFile('/css/jquery.contextMenu.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/jquery.datetimepicker.min.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/sensor-info.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/worker-info.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/js/jquery.contextMenu.js', ['depends' => [\frontend\assets\AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/jquery.datetimepicker.full.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/moment-with-locales.min.js', ['depends' => [\frontend\assets\AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/Chart.min.js', ['depends' => [\frontend\assets\AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/hammer.min.js', ['depends' => [\frontend\assets\AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/chartjs-plugin-zoom.min.js', ['depends' => [\frontend\assets\AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/xlsx.core.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/FileSaver.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/worker-info.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->title = 'Контроль работников';
?>

<style>
    .handbook-content__header__element:last-of-type {
        border-right: none;
    }
    .handbook-content__body__row__column:last-of-type {
        border-right: none;
    }
</style>

<!--Прелоадер-->
<div id="preload" class="hidden">
    <div class="circle-container">
        <div id="circle_preload"></div>
        <h4 class="preload-title">Идёт загрузка</h4>
    </div>
</div>

<!--    Модальное окно выбора периода времени, за который нужно отобразить маршрут передвижений-->
<div class="modal fade" role="dialog" id="workerRouteModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    История передвижения работника <span class="miner-full-name"></span>
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="Закрыть окно">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="padding-style">
                    <div class="choose-date-block-time">
                        <span>с</span>
                        <div id="routeDateStart" class="trapezoid" title="Выбериту дату начала маршрута">
                            <img src="/img/calendar.png" alt="calendar">
                            <span class="dateOption select-filter-type date-span"></span>
                            <img src="/img/dataTime.png" alt="dataTime">
                            <span class="timeOption select-filter-type date-span"></span>
                            <!--                                    <input type="datetime-local" >-->
                        </div>
                        <span>по</span>
                        <div id="routeDateEnd" class="trapezoid" title="Выберите дату окончания маршрута">
                            <img src="/img/calendar.png" alt="calendar">
                            <span class="dateOption select-filter-type date-span"></span>
                            <img src="/img/dataTime.png" alt="dataTime">
                            <span class="timeOption select-filter-type date-span"></span>
                            <!--                                    <input type="datetime-local" >-->
                        </div>
                        <button id="activatePlayerBtn" data-dismiss="modal" title="Построить маршрут"><img src="/img/magnifier-blue.png" alt="magnifier"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--Конец модального окна выбора периода времени, за который нужно отобразить маршрут передвижений-->

<!-- Шапка таблицы -->
<div class="handbook-header">
    <!-- Кнопка экспорта -->
    <button class="content-header-button btn-icn-1" title="Экспортировать данные в Excel документ" id="excelExport">Выгрузить в Excel</button>
    <div class="handbook-header__search-container">
        <!-- Кнопка фильтрации -->
        <button class="in-search-button filter-button icon-add-current-btn" id="filterButton" title="Добавить фильтр"></button>
        <div class="filter-block" id="pasteFiltersHere">

        </div>
        <input class="handbook-header__input" id="searchInput" onfocus="this.focused=true;" onblur="this.focused=false;" placeholder="Введите поисковой запрос">
        <button class="clear-search-button" id="searchClear"></button>
        <button class="handbook-header__search-button" id="searchButton"></button>
        <div class="filter-container" id="filterContainer">
            <div class="filter-item" id="filterItem-1">Актуальность</div>
            <div class="filter-item" id="filterItem-2">Местоположение</div>
            <div class="filter-item" id="filterItem-3">Подразделение</div>
            <div class="filter-item" id="filterItem-4">Состояние</div>
            <div class="filter-item" id="filterItem-5">Статус спуска</div>
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
            <span class="caret"></span>
        </div>
        <button title="Обновить данные" id="instant-refresh">
            <div class="glyphicon glyphicon-refresh" id="refresh-icon"></div>
        </button>
    </div>
</div>
<!-- Таблица с данными -->
<div class="content-body">
    <div class="handbook-content__header">
        <div class="handbook-content__header__element" style="width: 10%; display: flex; justify-content: center;" id="header-worker_id-1">Таб. №
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element" id="header-fio-2" style="width: 20%;">ФИО
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element" id="header-status-3" style="width: 20%">Состояние
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element" id="header-place_title-4" style="width: 25%;">Местоположение
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element" id="header-charge-6" style="width: 25%;">Статус спуска
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
                <div class="modal-table-header-container">
                    <div class="modal-table-header-element"><span>Наименование параметра</span></div>
                    <div class="modal-table-header-element"><span>Справочный</span></div>
                    <div class="modal-table-header-element"><span>Измеряемый</span></div>
                    <div class="modal-table-header-element"><span>Вычисляемый</span></div>
                    <div class="modal-table-header-element"><span>Единица измерения</span></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="instant-refresh-modal" class="btn btn-primary">Обновить</button>
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
                    График изменения прараметра  <span class="gas-title"></span>
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
