<?php
use frontend\assets\AppAsset;
use yii\web\View;
use yii\helpers\Html;
use macgyer\yii2materializecss\widgets\grid\GridView;
use macgyer\yii2materializecss\widgets\form\ActiveForm;
use kartik\date\DatePicker;
$getSourceData = 'let model = '.json_encode($model).'
functionParameters = '. json_encode($functionParameters).'
parameterId = '. json_encode($parameterId).'
parameterType = '. json_encode($parameterType).'
parameterTypeId = '. json_encode($parameterTypeId).';';
//connectString = '. json_encode($connectString).'
//eventss = '. json_encode($eventss).'
//sensorParameters = '. json_encode($sensorParameters).'
//sensorParameterValues = '. json_encode($sensorParameterValues).';';
$this->registerJs($getSourceData, View::POS_HEAD, 'function-js');
$this->registerCssFile('/css/basic-table.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/function.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/basic-header.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/js/jquery.cookie.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/function.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/anime.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerCssFile('/css/header-form-handbook.css', ['depends' => [\frontend\assets\AppAsset::className()]]);

$this->title = 'Справочник функций';
?>
<!--Прелодер-->
<div id="preload" class="hidden">
    <div class="circle-container">
        <div id="circle_preload"></div>
        <h4 class="preload-title">Идёт загрузка</h4>
    </div>
</div>

<!-- Модальное окно добавления типов функций -->
<div id="addFunctionTypeModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header-functionType">
                    <!-- Иконка добавления -->
                    <span class="icon-add-current-btn"></span>
                    <!-- Текст шапки -->
                    <span>Добавление типа функции</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body add-functionType-body">
                <!-- Текст модального окна -->
                <span class="add-functionType-text">Введите наименование</span></br>
                <!-- Поле ввода наименования functionType -->
                <input class="add-functionType-input" id="addFunctionTypeInput" type="text" placeholder="Наименование...">
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer">
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add-functionType" id="addFunctionTypeButtonSave" type="button" data-dismiss="modal">Создать</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel-functionType" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для добавления функции и выбора типа функции -->
<div id="addFunctionTypeModalAndFunction" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header-functionType">
                    <!-- Иконка добавления -->
                    <span class="icon-add-current-btn"></span>
                    <!-- Текст шапки -->
                    <span>Добавление типа функции</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body add-functionType-body">
                <!-- Текст модального окна -->
                <span class="add-functionType-text">Введите наименование</span></br>
                <!-- Поле ввода наименования functionType -->
                <input class="add-functionType-input" id="addFunctionTypeInputAndFunction" type="text" placeholder="Наименование...">
                <span class="add-functionType-text">Введите наименование скрипта</span></br>
                <!-- Поле ввода наименования скрипта -->
                <input class="add-functionType-input" id="addFunctionessInput2" type="text" placeholder="Наименование...">
                <span class="add-functionType-text">Выберите тип функции</span>
                <!-- Поле ввода наименования functionType -->
                <div class="add-functionType-function-input" id="addFunctionTypeFunctionInput"><span class="functionType-text__span">Выбрать...</span></div>
                <div class="content__function" id="contentFunction">
                    <div class="content__function-drop" id="contentFunctionDrop">
                    </div>
                    <!-- Текущий тип датчика -->
                    <span class="dropdown-textcontent"></span>
                    <!-- Значок треугольника -->
                    <span class="caret"></span>
                </div>
            </div>

            <!-- Футер модального окна -->
            <div class="modal-footer_New">
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add-functionType" id="addFunctionTypeAndFunctionButtonSave" type="button" data-dismiss="modal">Создать</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel-functionType" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования functionType -->
<div id="editFunctionTypeModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header-functionType">
                    <!-- Иконка добавления -->
                    <span class="icon-edit-btn"></span>
                    <!-- Текст шапки -->
                    <span>Редактирование типа функции</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body add-functionType-body">
                <!-- Текст модального окна -->
                <span class="add-functionType-text">Введите наименование</span></br>
                <!-- Поле ввода наименования functionType -->
                <input class="add-functionType-input" id="editFunctionTypeInput" type="text" placeholder="Наименование...">
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer">
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add-functionType" id="editFunctionTypeButtonSave" type="button" data-dismiss="modal">Изменить</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel-functionType" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

<!--Модальная форма удаления режима работы-->
<div id="deleteFunctionTypeModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header-functionType">
                    <!-- Иконка добавления -->
                    <span class="icon-delete-btn"></span>
                    <!-- Текст шапки -->
                    <span>Удаление типа функции</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body delete-functionType-body">
                <!-- Текст модального окна -->
                <span class="delete-functionType-text">Вы действительно хотите удалить тип функции:</span>
                <!-- Название системы автоматизации -->
                </br><span class="delete-functionType-parse" id="deleteFunctionTypeParse"></span>
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer">
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add-functionType" id="deleteFunctionTypeButtonSave" type="button" data-dismiss="modal">Удалить</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel-functionType" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно добавления функции -->
<div id="addFunctionessModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header-functionType">
                    <!-- Иконка добавления -->
                    <span class="icon-add-current-btn"></span>
                    <!-- Текст шапки -->
                    <span>Добавление новой функции</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body add-functionType-body">
                <!-- Текст модального окна -->
                <span class="add-functionType-text">Введите наименование функции</span></br>
                <!-- Поле ввода наименования функции -->
                <input class="add-functionType-input" id="addFunctionessInput" type="text" placeholder="Наименование...">
                <!-- Текст модального окна -->
                <span class="add-functionType-text">Введите наименование скрипта</span></br>
                <!-- Поле ввода наименования скрипта -->
                <input class="add-functionType-input" id="addFunctionessInput2" type="text" placeholder="Наименование...">
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer" style="padding-top: 0;">
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add-functionType" id="addFunctionessButtonSave" type="button" data-dismiss="modal">Создать</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel-functionType" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования датчика -->
<div id="editFunctionessModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header-functionType">
                    <!-- Иконка добавления -->
                    <span class="icon-edit-btn"></span>
                    <!-- Текст шапки -->
                    <span>Редактирование датчика</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body add-functionType-body">
                <!-- Текст модального окна -->
                <span class="add-functionType-text">Введите наименование функции</span></br>
                <!-- Поле ввода наименования функции -->
                <input class="add-functionType-input" id="editFunctionessInput" type="text" placeholder="Наименование...">
                <!-- Текст модального окна -->
                <span class="add-functionType-text">Введите наименование скрипта</span></br>
                <!-- Поле ввода наименования скрипта -->
                <input class="add-functionType-input" id="editFunctionessInput2" type="text" placeholder="Наименование...">
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer" style="padding-top: 0;">
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add-functionType" id="editFunctionessButtonSave" type="button" data-dismiss="modal">Изменить</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel-functionType" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

<!--Модальная форма удаления датчика-->
<div id="deleteFunctionessModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header-functionType">
                    <!-- Иконка добавления -->
                    <span class="icon-delete-btn"></span>
                    <!-- Текст шапки -->
                    <span>Удаление функции</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body delete-functioness-body">
                <!-- Текст модального окна -->
                <span class="delete-functionType-text">Вы действительно хотите удалить функцию:</span>
                <!-- Название датчика -->
                <span class="delete-functioness-parse" id="deleteFunctionessParse"></span>
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer">
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add-functionType" id="deleteFunctionessButtonSave" type="button" data-dismiss="modal">Удалить</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel-functionType" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

<!--Модальная форма параметров-->
<div id="parameterFunctionessModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header-parameters">
                <!-- Внутренности шапки -->
                <!-- Иконка параметров -->
                <span class="glyphicon glyphicon-cog"></span>
                <!-- Текст шапки -->
                <span>Параметры функции</span>
                <!-- Кнопка закрытия окна -->
                <button class="close-params" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body parameters-functioness-body">
                <!-- Контейнер бутстрапа с заголовками -->
                <div class="col-xs-12 bootstrap-headers" style="top: 0; position: sticky; z-index: 999;">
                    <div class="col-xs-1">№ п/п</div>
                    <div class="col-xs-4">Параметр</div>
                    <div class="col-xs-4">Тип параметра</div>
                    <div class="col-xs-2">Входной/Выходной</div>
                    <div class="col-xs-1"></div>
                </div>
                <!-- Контейнер бутстрапа с содержимым -->
                <div class="col-xs-12 bootstrap-content" id="bootstrapContent" style="margin-bottom: 20px;"></div>
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer">
                <span id="parseParameterFunctioness"></span>
                <button class="addFrame" id="newParameterButton">Добавить параметр
                </button>
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add-functionType" id="parameterButtonSave" type="button" >Сохранить</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel-functionType" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

<!-- Внутренняя шапка в справочнике функции -->
<header class="header">
    <div class="handbook-header__add-item" id="addFunctionTypeButton">
        <div class="button-add-left">
            <div class="glyphicon glyphicon-plus"></div>
            <div class="button-add-right"></div>
        </div>
        <div class="handbook-header__search-container"><span>Добавить </span></div>
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

<!-- Основное окно -->
<div class="col-xs-12 main-body">
    <!-- Левая часть со списком систем автоматизации -->
    <div class="col-xs-3 functionType-menu">
        <!-- Заголовок левой части -->
        <div class="functionType-header">Типы функций</div>
        <!-- Контейнер с контентом -->
        <div class="functionType-content">
            <!-- Список систем автоматизации -->
            <ul class="functionType-content-list" id="functionTypeList">
                <!-- Пункты списка -->
            </ul>
        </div>
    </div>
    <!-- Правая часть со списком датчиков -->
    <div class="col-xs-9 main-table" id="scrTp">
        <!-- Строка заголовка -->
        <div class="table-head" id="tableHead">
            <!-- Первый заголовок -->
            <div class="col-title-1 sort-field" id="headerTitle1">№ п/п</div>
            <!-- Второй заголовок -->
            <div class="col-title-2 sort-field" data-field="title" id="headerTitle2">Наименование<span class="glyphicon"></span></div>
            <!-- Третий заголовок -->
            <div class="col-title-3 sort-field" data-field="functionScriptName" id="headerTitle2">Наименование скрипта<span class="glyphicon"></span></div>
        </div>
        <!-- Основная таблица справочника -->
        <div class="main-function-table" id="mainFunctionTable">
        </div>
    </div>
</div>

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

<!-- Всплывающее меню -->
<div id="blockActionMenu" class="container-of-action">
    <!-- Контейнер с кнопками -->
    <div class="buttons">
        <!-- Кнопка добавления датчика -->
        <button id="addFunctionessBtn"><span class="icon-add-current-btn"></span> Добавить функцию</button>
        <!-- Кнопка параметров датчика -->
        <button id="parameterFunctionessBtn"><span class="glyphicon glyphicon-cog"></span></button>
        <!-- Кнопка редактирования системы автоматизации -->
        <button id="editFunctionessBtn"><span class="icon-edit-btn"></span></button>
        <!-- Кнопка удаления системы автоматизации -->
        <button id="deleteFunctionessBtn"><span class="icon-delete-btn"></span></button>
    </div>
    <!-- Треугольник снизу -->
    <div class="bottom-triangle">
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

<!-- Плашка селекта без поиска -->
<div class="select-container-no-search" id="selectContainerNoSearch">
    <!-- Контейнер списка -->
    <div class="select-list-container-no-search" id="parseListNoSearchHere">
        <ul>
            <li class="listElementNoSearch" id="listElemsNoSrch1" data-original-id="1">in</li>
            <li class="listElementNoSearch" id="listElemsNoSrch2" data-original-id="2">out</li>
        </ul>
    </div>
</div>