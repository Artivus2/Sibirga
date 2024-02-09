<?php

use frontend\assets\AppAsset;
use yii\web\View;
use yii\helpers\Html;
use macgyer\yii2materializecss\widgets\grid\GridView;
use macgyer\yii2materializecss\widgets\form\ActiveForm;
use kartik\date\DatePicker;
$getSourceData = 'model = '.json_encode($model).'
settingsDCS = '. json_encode($settingsDCS).'
sourceType = '. json_encode($sourceType).';';
$this->registerJs($getSourceData, View::POS_HEAD, 'connect-string-js');
$this->registerCssFile('/css/basic-table.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/connect-string.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/basic-header.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/js/jquery.cookie.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/connect-string.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/anime.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerCssFile('/css/header-form-handbook.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->title = 'Справочник строк подключения';
?>

<!--Прелодер-->
<div id="preload" class="hidden">
    <div class="circle-container">
        <div id="circle_preload"></div>
        <h4 class="preload-title">Идёт загрузка</h4>
    </div>
</div>

<header class="header">
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
<!-- Модальное окно добавления строки -->
<div id="addModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document" style="margin-top: 5%;">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header">
                    <!-- Иконка добавления -->
                    <span class="icon-add-current-btn"></span>
                    <!-- Текст шапки -->
                    <span>Добавление строки подключения</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body add-asmtp-body">
                <!-- Текст модального окна -->
                <div class="add-text">Наименование</div>
                <!-- Поле ввода наименования строки подключения -->
                <input class="add-input" id="addInputTitle" type="text" placeholder="Введите наименование...">
                <!-- Текст модального окна -->
                <div class="add-text">IP</div>
                <!-- Поле ввода айпи -->
                <input class="add-input" id="addInputIp" type="text" placeholder="Введите IP...">
                <!-- Текст модального окна -->
                <div class="add-text">Дополнительные параметры подключения</div>
                <!-- Поле ввода строки подключения -->
                <input class="add-input" id="addInputConnect" type="text" placeholder="Введите дополнительные параметры подключения...">
                <div class="add-text">Имя ССД</div>
                <!-- Селект настройки DCS -->
                <div class="select" id="dcsSelect">
                    <!-- Текущий тип датчика -->
                    <span>Нажмите для выбора</span>
                    <!-- Значок треугольника -->
                    <span class="caret"></span>
                </div>
                <!-- Текст модального окна -->
                <div class="add-text">Тип источника</div>
                <!-- Селект типа источника -->
                <div class="select" id="sourceSelect">
                    <!-- Текущий тип датчика -->
                    <span>Нажмите для выбора</span>
                    <!-- Значок треугольника -->
                    <span class="caret"></span>
                </div>
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer">
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add" id="addContentButton" type="button" data-dismiss="modal">Создать</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>
<!-- Модальное окно редактирования строки -->
<div id="editModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document" style="margin-top: 5%;">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header">
                    <!-- Иконка добавления -->
                    <span class="icon-edit-btn"></span>
                    <!-- Текст шапки -->
                    <span>Редактирование строки подключения</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body add-asmtp-body">
                <!-- Текст модального окна -->
                <div class="add-text">Наименование</div>
                <!-- Поле ввода наименования строки подключения -->
                <input class="add-input" id="editInputTitle" type="text" placeholder="Введите наименование...">
                <!-- Текст модального окна -->
                <div class="add-text">IP</div>
                <!-- Поле ввода айпи -->
                <input class="add-input" id="editInputIp" type="text" placeholder="Введите IP...">
                <!-- Текст модального окна -->
                <div class="add-text">Дополнительные параметры подключения</div>
                <!-- Поле ввода строки подключения -->
                <input class="add-input" id="editInputConnect" type="text" placeholder="Введите дополнительные параметры подключения подключения...">
                <div class="add-text">Имя ССД</div>
                <!-- Селект настройки DCS -->
                <div class="select" id="dcsSelect2">
                    <!-- Текущий тип датчика -->
                    <span>Нажмите для выбора</span>
                    <!-- Значок треугольника -->
                    <span class="caret"></span>
                </div>
                <!-- Текст модального окна -->
                <div class="add-text">Тип источника</div>
                <!-- Селект типа источника -->
                <div class="select" id="sourceSelect2">
                    <!-- Текущий тип датчика -->
                    <span>Нажмите для выбора</span>
                    <!-- Значок треугольника -->
                    <span class="caret"></span>
                </div>
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer">
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add" id="editContentButton" type="button" data-dismiss="modal">Сохранить</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>
<!--Модальная форма удаления датчика-->
<div id="deleteModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document" style="margin-top: 5%;">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header">
                    <!-- Иконка добавления -->
                    <span class="icon-delete-btn"></span>
                    <!-- Текст шапки -->
                    <span>Удаление строки подключения</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body delete-sensor-body">
                <!-- Текст модального окна -->
                <span class="delete-text">Вы действительно хотите удалить строку подключения:</span>
                <!-- Название датчика -->
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
        <div class="col-xs-1 table-id table-header sort-field" data-field='iterator' id="iterator" style="border-right: 1px solid #9e9e9e;" ><span>№ п/п</span><span class="glyphicon"></span></div>
        <div class="col-xs-2 table-title table-header sort-field" data-field='name'  id="title" style="border-right: 1px solid #9e9e9e;" ><span>Наименование </span><span class="glyphicon"></span></div>
        <div class="col-xs-2 table-ip table-header sort-field" data-field='IP'  id="ip" style="border-right: 1px solid #9e9e9e;" ><span>IP</span><span class="glyphicon"></span></div>
        <div class="col-xs-3 table-connect-string table-header sort-field" data-field='setting-conecting'  id="connectString" style="border-right: 1px solid #9e9e9e;" ><span>Дополнительные параметры подключения</span> <span class="glyphicon"></span></div>
        <div class="col-xs-2 table-settings-dcs table-header sort-field" data-field='SSD'  id="settingsDcsId" style="border-right: 1px solid #9e9e9e;" ><span>Имя ССД</span><span class="glyphicon"></span></div>
        <div class="col-xs-2 table-source-type table-header sort-field" data-field='source'  id="sourceType" ><span>Тип источника</span><span class="glyphicon"></span></div>
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
            <a class="show-pages-button">20</a><a class="show-pages-button">50</a><a class="show-pages-button">100</a><a class="show-pages-button">200</a>
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
        <!-- Кнопка редактирования системы автоматизации -->
        <button id="editBAM"><span class="icon-edit-btn"></span></button>
        <!-- Кнопка удаления системы автоматизации -->
        <button id="deleteBAM"><span class="icon-delete-btn"></span></button>
    </div>
    <!-- Треугольник снизу -->
    <div class="bottom-triangle">
    </div>
</div>
