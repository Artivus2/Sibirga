<?php
/* @var $this yii\web\View */
use yii\web\View;
$this->title = "Контроль БПД-3";
$script = "const place_array = ".json_encode($places).";";
$this->registerJs($script, View::POS_HEAD, 'my-script-str');
$this->registerCssFile('/css/bpd.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/jquery.contextMenu.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/js/pickmeup.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/xlsx.core.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerCssFile('/css/pickmeup.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/js/FileSaver.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
?>
<!--Прелоадер-->
<div id="preload" class="hidden">
    <div class="circle-container">
        <div id="circle_preload"></div>
        <h4 class="preload-title">Идёт загрузка</h4>
    </div>
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

<!-- Шапка таблицы -->
<div class="handbook-header">
    <!-- Кнопка экспорта -->
    <button class="content-header-button btn-icn-1" title="Экспортировать данные в Excel документ" id="excelExport">Выгрузить в Excel</button>
    <div class="handbook-header__search-container">

        <div id="add_filter_button" title="Добавить фильтр">
            <i class="icon-filter-add-btn"></i>
        </div>
        <div class="filter-elements">
            <div class="filter-block-item hidden" id="placeElement">
                <span>Местоположение</span>
                <span class="select-filter-type span1">Выбрать</span>
                <span class="caret span4"></span>
                <span class="span5">&times;</span>
            </div>
            <div class="filter-block-item hidden" id="stateElement">
                <span>Состояние</span>
                <span class="select-filter-type span1">Выбрать</span>
                <span class="caret span4"></span>
                <span class="span5">&times;</span>
            </div>
            <div class="filter-block-item hidden" id="chargeElement">
                <span>Уровень заряда батареи</span>
                <span class="select-filter-type span1">Выбрать</span>
                <span class="caret span4"></span>
                <span class="span5">&times;</span>
            </div>
        </div>
        <input class="handbook-header__input" id="searchInput" placeholder="Введите поисковый запрос">
        <button class="clear-search-button" id="searchClear"></button>
        <button class="handbook-header__search-button" id="searchButton"></button>
    </div>
    <div class="filter-options hidden">
        <div class="filter-item full-name-option" id="placeOption"><span class="filter-item-span">Местоположение</span></div>
        <div class="filter-item network-id-option" id="stateOption"><span class="filter-item-span">Состояние</span></div>
        <div class="filter-item staff-number-option" id="chargeOption"><span class="filter-item-span">Уровень заряда батареи</span></div>
    </div>
    <div class="charge-dropdown dropdown-container hidden">
        <div class="charge-list-option flex-style dropdown-option"><span class="charge-value">0-20</span><span class="charge-unit"> %</span></div>
        <div class="charge-list-option flex-style dropdown-option"><span class="charge-value">20-40</span><span class="charge-unit"> %</span></div>
        <div class="charge-list-option flex-style dropdown-option"><span class="charge-value">40-60</span><span class="charge-unit"> %</span></div>
        <div class="charge-list-option flex-style dropdown-option"><span class="charge-value">60-80</span><span class="charge-unit"> %</span></div>
        <div class="charge-list-option flex-style dropdown-option"><span class="charge-value">80-100</span><span class="charge-unit"> %</span></div>
    </div>
    <div class="place-dropdown dropdown-container hidden">
    </div>
    <div class="state-dropdown dropdown-container hidden">
        <div class="state-list-option flex-style dropdown-option" data-state="0"><span>Отключен / неисправен</span></div>
        <div class="state-list-option flex-style dropdown-option" data-state="1"><span>Включен / исправен</span></div>
        <div class="state-list-option flex-style dropdown-option" data-state="2"><span>Включен / работает от аккумулятора</span></div>
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
        <div class="handbook-content__header__element" id="header_id"><span>№ п/п</span></div>
        <div class="handbook-content__header__element" id="header_title"><span>Наименование</span><span class="glyphicon"></span></div>
        <div class="handbook-content__header__element" id="header_ip_address"><span>IP адрес</span><span class="glyphicon"></span></div>
        <div class="handbook-content__header__element" id="header_state"><span>Состояние</span><span class="glyphicon"></span></div>
        <div class="handbook-content__header__element" id="header_place"><span>Местоположение</span><span class="glyphicon"></span></div>
        <div class="handbook-content__header__element" id="header_voltage"><span>Напряжение с выхода ПИП3</span><span class="glyphicon"></span></div>
        <div class="handbook-content__header__element" id="header_charge"><span>Уровень заряда</span><span class="glyphicon"></span></div>
    </div>
    <div class="handbook-content__body" id="contentBody"></div>
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
<?php
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' =>
    View::POS_END]);
$this->registerJsFile('/js/jquery.contextMenu.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bpd.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
?>
