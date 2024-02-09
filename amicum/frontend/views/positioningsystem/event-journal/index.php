<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;
use yii\web\View;


$this->registerCssFile('/css/filters.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/jquery.contextMenu.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/pickmeup.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/event-journal.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/xlsx.core.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/Blob.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/FileSaver.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->title = "Журнал событий для диспетчера";

?>

<!--Фильтр начало-->
<div class="filter-container">

    <!--Строка фильтра начало-->
    <div class="row" id="filters_block">
        <div id="filters">
            <div id="filter_container">
            </div>
            <div id="input_container">
                <!-- Кнопка экспорта -->
                <button class="content-header-button excel-export-btn"
                        title="Экспортировать данные в Excel документ" id="excelExport">Выгрузить в Excel
                </button>
                <!-- добавить фильтры -->
                <div id="add_filter_button" class="filter-button new_filter_button" title="Добавить фильтр">
                    <i class="icon-filter-add-btn"></i>
                </div>
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
                        <span class="span3">Выбрать</span>
                        <span class="caret span4"></span>
                        <span class="span5" title="Убрать фильтр по событию">&times;</span>
                    </div>
                    <div class="filter-block-item" id="placeElement">
                        <span>Место</span>
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
                <input type="text" autocomplete="off" id="filter_text" placeholder="Введите поисковый запрос">
            </div>

            <button id="clear_filter_button" class="clear_filter_btn" title="Очистить фильтр">
                &times
            </button>
            <div id="accept_filters">
                <i class="icon-filter-search-btn"></i>
            </div>
        </div>
        <!-- Кнопка печати -->
        <button class="content-header-button print-btn hidden" title="Распечатать документ" id="printButton">
            Распечатать
        </button>
        <!-- Окошко фильтрации -->
        <div class="filter-list" id="filterInnerContainer">
            <div class="filter-item" id="eventFilter">Событие</div>
            <div class="filter-item" id="placeFilter">Место</div>
        </div>
        <div class="event-dropdown dropdown-container hidden"><div class="no-data"><span>Нет данных</span></div></div>
<!--    <div class="object-dropdown dropdown-container hidden"><div class=no-data"><span>Нет данных</span></</div>-->
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

    <!-- Контейнер для лишних фильтров -->
    <div class="more-filter-container" id="moreFilterContainer"></div>
</div>
<!--Фильтр конец-->
<!--Модальное окно уведомления о событии начало-->
<div class="modal fade" id="workerOnConveyor">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <span>Уведомление системы позиционирования</span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row padding-style">
                    <div class="message-content">
                        <h1>Внимание!</h1>
                        <h3>Человек на ленте <span class="conveyor-title">Имя конвейера</span></h3>
                    </div>
                    <div class="message-info">
                        <p class="place-title"><span>Конвейерный штрек - 233</span></p>
                        <p class="miner-name"><span class="person-name">Муратбаев Ж. Б. </span><span
                                    class="staff-number">215419</span></p>
                        <p><img src="/img/event_journal/stop_conveyor_icon.png" alt="Иконка остановки конвейера">
                        </p>
                    </div>
                </div>
                <div class="row padding-style">
                    <h4 class="fz-20">Вы действительно хотите остановить конвейер?</h4>
                </div>
            </div>
            <div class="modal-footer">
                <div class="button-content">
                    <button type="button" class="btn btn-primary stop-conveyor-btn" data-dismiss="modal">Да</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Нет</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!--Модальное окно уведомления о событии конец-->

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
                <div class="archive-title"><span><a href="/archive-event">Показать архив событий</a></span></div>
            </div>
        </div>
        <!-- НИЖНЕЕ ПОЛЕ ПРОКРУТКИ -->
        <div class="handbook-content__footer">
            <div class="handbook-content__footer__pagination"></div>
        </div>
    </div>
</div>
<!--Таблица событий конец-->
<audio id="notify_sound" src="audio/notification.mp3"></audio>
<?php
$this->registerJsFile('/js/jquery.contextMenu.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/pickmeup.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/event-journal.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
?>
