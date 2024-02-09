<?php

use frontend\assets\AppAsset;
use yii\web\View;

$session = Yii::$app->session;
$mine_id = $session['userMineId'];
$getSourceData = 'const mine_id = ' . $mine_id . ';';
$this->registerJs($getSourceData, View::POS_HEAD, 'my-mine-js');
$this->registerCssFile('/css/filters.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/jquery.contextMenu.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/pickmeup.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/event-journal.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/archive-event.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/xlsx.core.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/Blob.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/FileSaver.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()], 'position' =>
    View::POS_END]);
$this->title = "Архив событий";
?>
    <!--Прелоад начало-->
    <div class="modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel" id="preload"
         data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="circle-container">
                <div id="circle_preload"></div>
                <h4 class="preload-title">Идёт загрузка</h4>
            </div>
        </div>
    </div>
    <!--Прелоад конец-->

    <!--Фильтр начало-->
    <div class="filter-container">
        <!--Строка фильтра начало-->
        <div class="row" id="filters_block">

            <!-- Кнопка печати -->
            <button class="content-header-button print-btn hidden" title="Распечатать документ" id="printButton">
                Распечатать
            </button>

            <div id="filters">
                <div id="filter_container">
                    <!-- Кнопка экспорта -->
                    <button class="content-header-button excel-export-btn" title="Экспортировать данные в Excel документ"
                            id="excelExport">Выгрузить в Excel
                    </button>
                    <!-- добавить фильтры -->
                    <div id="add_filter_button" class="filter-button new_filter_button" title="Добавить фильтр">
                        <i class="icon-filter-add-btn"></i>
                    </div>
<!--                    <div class="filter-block" id="filterList">-->
<!---->
<!--                    </div>-->

                    <div id="elementsForFilter">
                        <div class="filter-block-item" id="dateElement">Дата: с
                            <span id="date1" class="select-filter-type span1">Выбрать</span>
                            <span class="caret span2"></span>
                            <div>по</div>
                            <span id="date2" class="select-filter-type span1">Выбрать</span>
                            <span class="caret span4"></span>
                        </div>
                        <div class="filter-block-item" id="mineElement">
                            <span>Шахта</span>
                            <span class="span3">Выбрать</span>
                            <span class="caret span4"></span>
                        </div>
                        <div class="filter-block-item" id="eventElement">
                            <span>Событие</span>
                            <!--                <span class="glyphicon glyphicon-triangle-bottom"></span>-->
                            <span class="span3">Выбрать</span>
                            <span class="caret span4"></span>
                            <span class="span5" title="Убрать фильтр по событию">&times;</span>
                        </div>
                        <div class="filter-block-item" id="placeElement">
                            <span>Место</span>
                            <!--                <span class="glyphicon glyphicon-triangle-bottom"></span>-->
                            <span class="span3">Выбрать</span>
                            <span class="caret span4"></span>
                            <span class="span5" title="Убрать фильтр по месту">&times;</span>
                        </div>

                        <!--                    <div class="filter-block-item" id="objectElement">-->
                        <!--                        <span>Объект</span>-->
                        <!--                        <span class="span3">Выбрать</span>-->
                        <!--                        <span class="span5" title="Убрать фильтр по объекту">&times;</span>-->
                        <!--                    </div>-->
                    </div>
                    <!-- Сам поиск -->
                    <input type="text" autocomplete="off" id="filter_text" placeholder="Введите поисковый запрос">
                    <!-- очистка фильтров -->
                    <button id="clear_filter_button" class="clear_filter_btn" title="Очистить фильтр">
                        &times
                    </button>
                    <div id="accept_filters">
                        <i class="icon-filter-search-btn"></i>
                    </div>

                    <!-- Окошко фильтрации -->
                    <!--                    <div class="filter-list" id="filterContainer">-->
                    <!--                        <div class="filter-item" id="dateFilter">Дата</div>-->
                    <!--                        <div class="filter-item" id="eventFilter">Событие</div>-->
                    <!--                        <div class="filter-item" id="placeFilter">Место</div>-->
                    <!--                        <div class="filter-item" id="objectFilter">Объект</div>-->
                    <!--                    </div>-->
                    <!-- Окошко выбора значений фильтра не дата/время -->
                    <div class="filter-inner-container" id="filterInnerContainer">
                        <div class="filter-item" id="eventFilter">Событие</div>
                        <div class="filter-item" id="placeFilter">Место</div>
                        <!--                        <div class="filter-item " id="objectFilter">Объект</div>-->
                    </div>

                    <!-- Контейнер для лишних фильтров -->
                    <div class="more-filter-container" id="moreFilterContainer"></div>
                </div>
            </div>

            <div class="event-dropdown dropdown-container hidden"><div class="no-data"><span>Нет данных</span></div></div>
<!--            <div class="object-dropdown dropdown-container hidden"><div class=no-data"><span>Нет данных</span></</div>-->
            <div class="place-dropdown dropdown-container hidden"><div class="no-data"><span>Нет данных</span></div></div>
            <div class="mine-dropdown dropdown-container hidden"><div class="no-data"><span>Нет данных</span></div></div>
        </div>
        <!--Строка с легендой по типам и статусам сообщений начало-->
        <div class="row" id="message_legend">
            <div class="message-types">
                <div class="type-title">Тип сообщения:</div>
                <div class="emergency">
                    <div class="emergency-icon legend-icon"></div>
                    <span class="emergency-title icon-title">- аварийное</span>
                </div>
                <div class="normal">
                    <div class="normal-icon legend-icon"></div>
                    <span class="normal-title icon-title">- нормальное</span>
                </div>
                <!--                <div class="sos">-->
                <!--                    <div class="red-sos-icon legend-icon"></div>-->
                <!--                    <span class="red-sos-title  icon-title">- послал SOS</span>-->
                <!--                </div>-->
            </div>
            <div class="message-status">
                <div class="type-title">Значки статуса сообщения:</div>
                <div class="received">
                    <div class="received-icon legend-icon"></div>
                    <span class="received-title icon-title">Получил</span>
                </div>
                <div class="started-fix">
                    <div class="started-fix-icon legend-icon"></div>
                    <span class="started-fix-title icon-title">Начал устранять</span>
                </div>
                <div class="fixed">
                    <div class="fixed-icon legend-icon"></div>
                    <span class="fixed-title icon-title">Устранил</span>
                </div>
            </div>
        </div>
        <!--Строка с легендой по типам и статусам сообщений конец-->
    </div>
    <!--Фильтр конец-->


    <!--Таблица событий начало-->
    <div class="row journal">
        <div class="journal-table">
            <div class="table-header">
                <div class="table-header-title th-message-number"><span>№ сообщения</span></div>
                <div class="table-header-title th-message-type"><span>Тип сообщения</span></div>
                <div class="table-header-title th-message"><span>Сообщение / Событие</span></div>
                <div class="table-header-title th-place"><span>Место</span></div>
                <div class="table-header-title th-object"><span>Объект</span></div>
                <div class="table-header-title th-date"><span>Время / дата</span></div>
                <!--            <div class="table-header-title th-max-value"><span>Уставка</span></div>-->
                <div class="table-header-title th-actual-value"><span>Фактическое значение</span></div>
                <div class="table-header-title th-message-status"><span>Статус сообщения</span></div>
            </div>
            <div class="table-body"></div>
        </div>
        <div class="table-footer">
            <div class="message-archive-container">
                <div class="message-archive">
                    <div class="archive-icon"></div>
                    <div class="archive-title"><span><a href="/event-journal">Показать журнал событий</a></span></div>
                </div>
            </div>
            <!--            <div class="table-info">-->
            <!--                <button id="previousPage" class="footer-buttons" title="Переключить на предыдущую страницу">-->
            <!--                    <i class="glyphicon glyphicon-triangle-left"></i>-->
            <!--                </button>-->
            <!--                <div class="display-info">-->
            <!--                    <span class="info-text">Показано:</span>-->
            <!--                    <span class="first-element-on-page">0</span>-->
            <!--                    <span>-</span>-->
            <!--                    <span class="last-element-on-page">0</span>-->
            <!--                    <span>из</span>-->
            <!--                    <span class="total-count">0</span>-->
            <!--                </div>-->
            <!--                <button id="nextPage" class="footer-buttons" title="Переключить на следующую страницу">-->
            <!--                    <i class="glyphicon glyphicon-triangle-right"></i>-->
            <!--                </button>-->
            <!--            </div>-->
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
    <!--Таблица событий конец-->

<?php
$this->registerJsFile('/js/pickmeup.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/archive-event.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
?>
