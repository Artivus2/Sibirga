<?php
use yii\web\View;
use frontend\assets\AppAsset;
$this->title = "Контроль оборудования";
$this->registerCssFile('/css/jquery.contextMenu.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/jquery.datetimepicker.min.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/worker-info.css', ['depends' => [AppAsset::className()]]);
//$this->registerCssFile('/css/bpd.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/equipment-info.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/jquery.contextMenu.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/jquery.datetimepicker.full.min.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
//$this->registerJsFile('/js/moment-with-locales.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
//$this->registerJsFile('/js/Chart.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
//$this->registerJsFile('/js/hammer.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
//$this->registerJsFile('/js/chartjs-plugin-zoom.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/xlsx.core.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/FileSaver.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/equipment_info.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
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
        <button class="in-search-button filter-button icon-add-current-btn" id="filterButton" title="Добавить фильтр"></button>
        <div class="filter-block" id="pasteFiltersHere">

        </div>
        <input class="handbook-header__input" id="searchInput" onfocus="this.focused=true;" onblur="this.focused=false;" placeholder="Введите поисковой запрос">
        <button class="clear-search-button" id="searchClear"></button>
        <button class="handbook-header__search-button" id="searchButton"></button>
        <div class="filter-container" id="filterContainer">
            <div class="filter-item" id="placeFilter">Местоположение</div>
            <div class="filter-item" id="stateFilter">Состояние</div>
        </div>
        <div class="more-filter-container" id="moreFilterContainer"></div>
        <!-- Окошко выбора значений фильтра не дата/время -->
        <div class="filter-inner-container" id="filterInnerContainer">
        </div>
    </div>
    <div class="handbook-header__refresh-time-container">
        <span class="handbook-header_refresh-time-title">Время обновления данных:</span>
        <div class="handbook-header__refresh-time-dropdown" data-time="20">
            <span class="chosen-time">20 сек</span>
            <span class="caret"></span>
            <ul class="time-list hidden">
                <li data-time="20">20 сек</li>
                <li data-time="40">40 сек</li>
                <li data-time="60">1 мин</li>
                <li data-time="120">2 мин</li>
                <li data-time="180">3 мин</li>
                <li data-time="240">4 мин</li>
                <li data-time="300">5 мин</li>
                <li data-time="600">10 мин</li>
                <li data-time="1200">20 мин</li>
                <li data-time="1800">30 мин</li>
                <li data-time="3600">1 ч</li>
            </ul>
        </div>
        <button title="Обновить данные" class="refresh-button">
            <span class="glyphicon glyphicon-refresh"></span>
        </button>
    </div>
</div>
<!-- Таблица с данными -->
<div class="content-body">
    <div class="handbook-content__header">
        <div class="handbook-content__header__element ordered-number">№ п/п
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element equipment-title">Наименование оборудования
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element state-column">Состояние
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element place-title">Местоположение
            <span class="glyphicon"></span>
        </div>
    </div>
    <div class="handbook-content__body" id="contentBody">
    </div>
</div>
<div class="handbook-content__footer">
    <div class="table-footer">
        <div class="table-info">
            <div class="display-info">
                <span class="info-text">Показано:</span>
                <span class="last-element-on-page">0</span>
                <span>из</span>
                <span class="total-count">0</span>
            </div>
        </div>
    </div>
</div>


<!-- Модальное окно со всеми параметрами оборудования -->
<div class="modal fade" id="equipmentParametersModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Параметры "<span class="equipment-name"></span>"</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-table-header-container">
                    <div class="modal-table-header-element"><span>Наименование параметра</span></div>
                    <div class="modal-table-header-element"><span>Справочный</span></div>
                    <div class="modal-table-header-element"><span>Измеряемый</span></div>
                    <div class="modal-table-header-element"><span>Вычисляемый</span></div>
                    <div class="modal-table-header-element"><span>Единица измерения</span></div>
                </div>
                <div class="modal-table-body"></div>
            </div>
            <div class="modal-footer">
                <button id="refreshParametersBtn" class="btn btn-primary">Обновить</button>
            </div>
        </div><!-- /.модальное окно-Содержание -->
    </div><!-- /.модальное окно-диалог -->
</div><!-- /.модальное окно -->
