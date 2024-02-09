<?php

use frontend\assets\AppAsset;
use yii\web\View;
use macgyer\yii2materializecss\widgets\grid\GridView;
use macgyer\yii2materializecss\widgets\form\ActiveForm;
use kartik\date\DatePicker;
$getSourceData = 'model = '.json_encode($model).'
statusType = '. json_encode($statusType).';';
//sourceType = '. json_encode($sourceType).'
//sensors = '. json_encode($sensors).'
//parameterTitle = '. json_encode($parameterTitle).'
//parameterTypeTitle = '. json_encode($parameterTypeTitle).'
//eventss = '. json_encode($eventss).'
//sensorParameters = '. json_encode($sensorParameters).'
//sensorParameterValues = '. json_encode($sensorParameterValues).';';
$this->registerJs($getSourceData, View::POS_HEAD, 'status-js');
$this->registerCssFile('/css/basic-table.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/connect-string.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/basic-header.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/js/jquery.cookie.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/status.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/anime.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerCssFile('/css/header-form-handbook.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->title = 'Справочник статусов';
?>
<!--Прелодер-->
<div id="preload" class="hidden">
    <div class="circle-container">
        <div id="circle_preload"></div>
        <h4 class="preload-title">Идёт загрузка</h4>
    </div>
</div>

<header class="header">
    <!-- Кнопка добавления статуса-->
    <!--    <button class="addButton" id="addButton" title="Добавить новый статус">-->
    <!--        <!-- Иконка добавления -->
    <!--        <span class="icon-add-current-btn"></span>-->
    <!--        <!-- Надпись -->
    <!--        Добавить-->
    <!--    </button>-->
    <div class="handbook-header__add-item" id="addButton">
        <div class="button-add-left">
            <div class="glyphicon glyphicon-plus"></div>
            <div class="button-add-right"></div>
        </div>
        <div class="handbook-header__search-container"><span>Добавить</span></div>
    </div>
    <!-- Кнопка синхронизации -->
    <button class="syncButton" title="Синхронизировать данные"></button>
    <!-- Кнопка экспорта -->
    <button class="exportButton" title="Экспортировать данные в Excel"></button>
    <!-- Кнопка импорта -->
    <button class="importButton" title="Загрузить готовые данные"></button>
    <!-- Строка поиска -->
    <div class="search">
        <!-- Поле для ввода запроса -->
        <input id="search" type="search" placeholder="Введите поисковой запрос">
        <!-- Кнопка для очистки поиска -->
        <button id="clear" class="clearSearchButton"></button>
        <!-- Кнопка поиска(нужна только для декорации, потому что поиск живой) -->
        <button class="searchButton" id="searchButton"></button>
    </div>
</header>
<!-- Модальное окно добавления статуса -->
<div id="addModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document" style="margin-top: 8%;">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header" style="left: 205px;">
                    <!-- Иконка добавления -->
                    <span class="icon-add-current-btn"></span>
                    <!-- Текст шапки -->
                    <span>Добавление статуса</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body add-asmtp-body">
                <!-- Текст модального окна -->
                <div class="add-text">Наименование*</div>
                <!-- Поле ввода наименования строки подключения -->
                <input class="add-input" id="addInputTitle" type="text" placeholder="Введите наименование...">
                <!-- Текст модального окна -->
                <div class="add-text">Триггер</div>
                <!-- Поле ввода айпи -->
                <input class="add-input" id="addInputTrigger" type="text" placeholder="Введите триггер...">
                <div class="add-text">Тип статуса*</div>
                <!-- Селект настройки Типа статуса -->
                <div class="select" id="dcsSelect">
                    <!-- Текущий тип статуса -->
                    <span>Нажмите для выбора</span>
                    <!-- Значок треугольника -->
                    <span class="caret" style="top: 200px;"></span>
                </div>
                <div class="help" style="color: #c5c5c5; text-align: right;">Поля, отмеченные звездочкой (*) обязательны для заполнения</div>
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer">
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add" id="addContentButton" type="button">Создать</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>
<!-- Модальное окно редактирования статуса -->
<div id="editModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document" style="margin-top: 8%;">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header" style="left: 190px;">
                    <!-- Иконка добавления -->
                    <span class="icon-edit-btn"></span>
                    <!-- Текст шапки -->
                    <span>Редактирование статуса</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body add-asmtp-body">
                <!-- Текст модального окна -->
                <div class="add-text">Наименование*</div>
                <!-- Поле ввода наименования  -->
                <input class="add-input" id="editInputTitle" type="text" placeholder="Введите наименование...">
                <!-- Текст модального окна -->
                <div class="add-text">Триггер</div>
                <!-- Поле ввода айпи -->
                <input class="add-input" id="editInputTrigger" type="text" placeholder="Введите триггер...">
                <div class="add-text">Тип статуса*</div>
                <!-- Селект настройки Типа статуса -->
                <div class="select" id="dcsSelect2">
                    <!-- Текущий тип статуса -->
                    <span>Нажмите для выбора</span>
                    <!-- Значок треугольника -->
                    <span class="caret" style="top: 204px;"></span>
                </div>
                <div class="help" style="color: #c5c5c5; text-align: right;">Поля, отмеченные звездочкой (*) обязательны для заполнения</div>
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer">
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add" id="editContentButton" type="button">Изменить</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>
<!--Модальная форма удаления статуса-->
<div id="deleteModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header" style="left: 220px;">
                    <!-- Иконка добавления -->
                    <span class="icon-delete-btn"></span>
                    <!-- Текст шапки -->
                    <span>Удаление статуса</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body delete-sensor-body">
                <!-- Текст модального окна -->
                <span class="delete-text">Вы действительно хотите удалить статус:</span>
                <!-- Название статуса -->
                <span class="delete-sensor-parse" id="deleteParse"></span>
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer">
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add" id="deleteContent" type="button" data-dismiss="modal">Удалить</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>
<!-- Основное окно -->
<div class="col-xs-12 main-body" id="mainTableContainer">
    <div class="table-headers" id="mainTableHeader">
        <div class="col-xs-1 table-id table-header sort-field" data-field='iterator' id="iterator" style="border-right: 1px solid #9e9e9e;" >№ п/п<span class="glyphicon"></span></div>
        <div class="col-xs-3 table-title table-header sort-field" data-field='title' id="title" style="border-right: 1px solid #9e9e9e;" >Наименование<span class="glyphicon"></span></div>
        <div class="col-xs-4 table-ip table-header sort-field" data-field='trigger' id="trigger" style="border-right: 1px solid #9e9e9e;" >Триггер<span class="glyphicon"></span></div>
        <div class="col-xs-4 table-ip table-header sort-field" data-field='statusTypeTitle' id="statusTypeTitle" style="border-right: 1px solid #9e9e9e;" >Тип статуса<span class="glyphicon"></span></div>
    </div>
    <div class="table-content" id="tableContent"></div>
</div>
<!-- Переключение страниц-->
<div class="footRow">
    <div class="handbook-content__footer__rowsCount">Количество записей: <span id="actualRowCount">0</span></div>
    <div class="handbook-content__footer__pagination">
        <button class="handbook-page-switch"><<</button><button class="handbook-page-switch"><</button><button class="handbook-page-switch numeric">1</button><button class="handbook-page-switch">></button><button class="handbook-page-switch">>></button>
    </div>
    <div class="handbook-content__footer__show">
        Показывать по:
        <div class="handbook-content__footer__show__buttons">
            <a class="show-pages-button">25</a><a class="show-pages-button">50</a><a class="show-pages-button">100</a><a class="show-pages-button">200</a>
        </div>
    </div>
</div>
<!-- Плашка селекта с поиском -->
<div class="select-container" id="selectContainer">
    <!-- Контейнер поиска -->
    <div class="select-search-container">
        <input class="select-search" id="selectSearch" type="search" placeholder="Введите запрос...">
        <button id="clearSelectSearch" class="clearSearchButton"></button>
        <button class="searchButton"></button>
    </div>
    <!-- Контейнер списка -->
    <div class="select-list-container" id="parseListHere">
    </div>
</div>
<!-- Всплывающее меню -->
<div id="blockActionMenu" class="container-of-action">
    <!-- Контейнер с кнопками -->
    <div class="buttons">
        <!-- Кнопка редактирования статуса -->
        <button id="editBAM"><span class="icon-edit-btn"></span></button>
        <!-- Кнопка удаления статуса -->
        <button id="deleteBAM"><span class="icon-delete-btn"></span></button>
    </div>
    <!-- Треугольник снизу -->
    <div class="bottom-triangle">
    </div>
</div>
