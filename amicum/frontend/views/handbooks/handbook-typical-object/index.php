<?php
/* @var $this yii\web\View */
use yii\web\View;

$object_kinds_array = "var objectKinds = " . json_encode($objectKinds) . ", functionArray = " . json_encode
    ($functions) .  ", parameterTypesArray = " . json_encode($parameterTypes) .  ", objectOptions = " . json_encode
    ($objectProps) . ", unitsArray = " . json_encode($units) . ", sensorArray = ". json_encode($sensorList) .", 
     functionTypes = " . json_encode($functionTypes) . ", 
    asmtpArray = " . json_encode($asmtp) . ", sensorTypeArray = " . json_encode($sensorType) . ", placeArray = ".json_encode($placeArray).";";
$this->registerJs($object_kinds_array,View::POS_HEAD, 'my-object-array-js');
$this->title = "Типовые объекты";
$this->registerCssFile('/css/pickmeup.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/filters.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/typical_objects.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/jquery-ui.min.css', ['depends' => [\frontend\assets\AppAsset::className()]]);

?>
<!-- Меню действий -->
<div id="blockActionMenu" class="container-of-action-menu">
    <div id="actionMenu" class="action-menu">
        <button type="button" id="btn_copy" data-target="#copyObject" data-toggle="modal"
                title="Скопировать типовой объект">
            <i class="icon-copy_btn"></i>
        </button>
        <button type="button" id="btn_move_obj" data-target="#moveObject" data-toggle="modal"
                title="Переместить типовой объект"></button>
        <button type="button" id="btn_delete" data-target="#deleteObject" data-toggle="modal"
                title="Удалить типовой объект">
            <i class="icon-delete-btn"></i>
        </button>
    </div>
    <div class="triangle-position">
        <div id="triangleContainer" class="triangle-container">
            <div class="triangle-action-menu" id="actionMenuTriangle"></div>
        </div>
    </div>
</div>
<!--Прелоадер-->
<div id="preload" class="hidden">
    <div class="circle-container">
        <div id="circle_preload"></div>
        <h4 class="preload-title">Идёт загрузка</h4>
    </div>
</div>
<!--Прелоадер-->

<div class="col-xs-12">
    <!--    Модальное окно добавления типового объекта    -->
    <div class="modal fade" tabindex="-1" role="dialog" id="modal_for_typical_object">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="icon-add-current-btn"></i>
                        Добавление типового объекта</h4>
                </div>
                <div class="modal-body">

                    <div class="row padding-style">
                        <p>
                            Вид объекта
                        </p>
                        <div class="select-div object-kind-select">
                            <span class="empty-span" id="object_kind_select"></span>
                        </div>
                    </div>

                    <div class="row padding-style last-field">
                        <p>
                            Тип объекта
                        </p>
                        <div class="select-div object-type-select">
                            <span class="empty-span" id="object_type_select">Выберите тип объекта</span>
                            <i class="caret"></i>
                            <ul class="object-type-list hidden"></ul>
                        </div>
                    </div>

                    <div class="row padding-style">
                        <p>
                            Наименование объекта
                        </p>
                        <input id="add_object_name" type="text" class="padding-in add-object-name full-width"
                               placeholder="Введите наименование...">
                    </div>

                    <div class="row padding-style">
                        <p>
                            Таблица
                        </p>
                        <input id="table_name" type="text" class="padding-in table-name full-width"
                               placeholder="Введите название таблицы в БД...">
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary add-typical-object-button"
                                data-dismiss="modal">Добавить</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окна добавления типового объекта-->

    <!--    Модальное окно копирования типового объекта-->
    <div class="modal fade" tabindex="-1" role="dialog" id="copyObject">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="icon-copy_btn"></i>
                        Копирование типового объекта
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <p>
                            Объект для копирования
                        </p>
                        <div class="object-title"></div>
                    </div>
                    <div class="row padding-style">
                        <p>
                            Наименование объекта
                        </p>
                        <input id="copy_object_name" type="text" class="padding-in copy-object-name full-width"
                               placeholder="Введите наименование...">
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary copy-typical-object-button"
                                data-dismiss="modal">Копировать</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окна копирования типового объекта-->

    <!--    Модальное окно перемещение типового объекта    -->
    <div class="modal fade" tabindex="-1" role="dialog" id="moveObject">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="move-btn-icon"></i>
                        <span>Перемещение типового объекта</span></h4>
                </div>
                <div class="modal-body">

                    <div class="row padding-style">
                        <p>
                            Объект для перемещения
                        </p>
                        <div class="object-title"></div>
                    </div>

                    <div class="row padding-style last-field">
                        <p>
                            Откуда
                        </p>
                        <div class="object-type-name"></div>
                    </div>

                    <div class="row padding-style last-field">
                        <p>
                            Куда
                        </p>
                        <div class="select-div object-type-select">
                            <span class="empty-span" id="object_type_choose">Выберите тип объекта</span>
                            <i class="caret"></i>
                            <ul class="object-type-list hidden"></ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary move-typical-object-button"
                                data-dismiss="modal">Переместить</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окна перемещения типового объекта-->

    <!--    Модальное окно удаления типового объекта-->
    <div class="modal fade" tabindex="-1" role="dialog" id="deleteObject">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="icon-delete-btn"></i>
                        Удаление типового объекта
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <p>
                            Вы действительно хотите удалить объект "<span class="object-title"></span>" ?
                        </p>

                    </div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary delete-typical-object-button"
                                data-dismiss="modal">Удалить</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окна удаления типового объекта-->

    <!--    Модальное окно добавления параметра    -->
    <div class="modal fade" tabindex="-1" role="dialog" id="add_global_parameter">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="icon-add-current-btn"></i>
                        Добавление параметра</h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <p>
                            Вид параметра
                        </p>
                        <div class="select-div parameter-kind-select">
                            <span class="empty-span" id="param_kind_select">
                                Выберите вид параметра
                            </span>
                            <i class="caret"></i>

                            <ul class="parameter-kind-list hidden"></ul>
                        </div>
                    </div>

                    <div class="row padding-style">
                        <p>
                            Параметр
                        </p>
                        <div class="select-div parameter-select" id="parameters_in_global_add">
                            <span class="empty-span" id="parameter_select">Выберите параметр</span>
                            <i class="caret"></i>
                            <div class="search">
                                <input class="selectSearch" id="selectSearch" type="search" placeholder="Поиск параметра...">
                                <button id="clearSelectSearch" class="clearSearchButton btn close">&times;</button>
                                <button class="searchButton"></button>
                            </div>
                            <ul class="parameter-list hidden"></ul>
                        </div>
                    </div>
                    <div class="row padding-style hidden">
                        <p>
                            Выберите единицу измерений
                        </p>
                        <div class="select-div unit-select">
                            <span class="empty-span" id="unit_select">Выберите параметр</span>
                            <i class="caret"></i>
                            <div class="search">
                                <input class="selectSearch" id="selectSearch" type="search" placeholder="Поиск параметра...">
                                <button id="clearSelectSearch" class="clearSearchButton btn close">&times;</button>
                                <button class="searchButton"></button>
                            </div>
                            <ul class="unit-list hidden"></ul>
                        </div>
                    </div>
                    <div class="row padding-style">
                        <p>
                            Тип параметра
                        </p>
                        <div id="idParameter" class="select-div parameter-type-select" >
                            <span class="empty-span" id="parameter_select">Выберите тип параметра</span>
                            <i class="caret"></i>

                            <ul class="parameter-type-list hidden"></ul>
                        </div>
                    </div>


                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary add-global-parameter-button">Добавить</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окна добавления параметра-->

    <!--    Модальное окно добавления параметра во вкладке Ресурсы   -->
    <div class="modal fade" tabindex="-1" role="dialog" id="add_resource_parameter">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="icon-add-current-btn"></i>
                        Добавление параметра</h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <p>
                            Вид параметра: <span id="resource_title">Ресурс</span>
                        </p>
                    </div>

                    <div class="row padding-style">
                        <p>
                            Параметр
                        </p>
                        <div class="select-div parameter-select">
                            <span class="empty-span" id="resource_parameter_select">Выберите параметр</span>
                            <i class="caret"></i>
                            <div class="search">
                                <input class="selectSearch" id="selectSearch" type="search" placeholder="Поиск параметра...">
                                <button id="clearSelectSearch" class="clearSearchButton btn close">&times;</button>
                                <button class="searchButton"></button>
                            </div>
                            <ul class="parameter-list hidden"></ul>
                        </div>
                    </div>
                    <div class="row padding-style hidden">
                        <p>
                            Выберите единицу измерений
                        </p>
                        <div class="select-div unit-select">
                            <span class="empty-span" id="unit_select">Выберите параметр</span>
                            <i class="caret"></i>
                            <div class="search">
                                <input class="selectSearch" id="selectSearch" type="search" placeholder="Поиск параметра...">
                                <button id="clearSelectSearch" class="clearSearchButton btn close">&times;</button>
                                <button class="searchButton"></button>
                            </div>
                            <ul class="unit-list hidden"></ul>
                        </div>
                    </div>
                    <div class="row padding-style">
                        <p>
                            Тип параметра
                        </p>
                        <div class="select-div parameter-type-select">
                            <span class="empty-span" id="parameter_select">Выберите тип параметра</span>
                            <i class="caret"></i>

                            <ul class="parameter-type-list hidden"></ul>
                        </div>
                    </div>


                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary add-resource-parameter-button">Добавить</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окна добавления параметра во вкладке Ресурсы-->


    <!--    Модальное окно удаления параметра через кнопку рядом с типом параметра-->
    <div class="modal fade" tabindex="-1" role="dialog" id="deleteParameter">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="icon-delete-btn"></i>
                        Удаление параметра
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <p>
                            Вы действительно хотите удалить параметр &laquo;<span class="p-title"></span>&raquo; ?
                        </p>
                        <p>
                            Тип параметра: <span class="type-param-title"></span>
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary delete-parameter-button"
                                data-dismiss="modal">Да</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Нет</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окна удаления параметра через кнопку рядом с типом параметра-->

    <!--    Модальное окно удаления параметра через кнопку рядом с названием вида параметров-->
    <div class="modal fade" tabindex="-1" role="dialog" id="deleteGlobalParameter">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="icon-delete-btn"></i>
                        Удаление параметра
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <p>
                            Вид параметра: &laquo;<span class="kind-title"></span>&raquo;
                        </p>
                    </div>

                    <div class="row padding-style">
                        <p>
                            Параметр
                        </p>
                        <div class="select-div parameter-select">
                            <span class="empty-span">Выберите параметр</span>
                            <i class="caret"></i>
                            <div class="search">
                                <input class="selectSearch" id="selectSearch" type="search" placeholder="Поиск параметра...">
                                <button id="clearSelectSearch" class="clearSearchButton btn close">&times;</button>
                                <button class="searchButton"></button>
                            </div>
                            <ul class="parameter-list hidden"></ul>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary delete-parameter-button"
                                data-dismiss="modal">Да</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Нет</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окна удаления параметра через кнопку рядом с названием вида параметров-->

    <!--    Модальное окно выбора функции во вкладке Функции   -->
    <div class="modal fade" tabindex="-1" role="dialog" id="set_function">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="icon-add-current-btn"></i>
                        Выбор функции</h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <p>Вид функции</p>
                        <div class="select-div function-type-select">
                            <span class="empty-span">Выберите тип функции</span>
                            <i class="caret"></i>
                            <ul class="function-type-list hidden"></ul>
                        </div>
                    </div>

                    <div class="row padding-style">
                        <p>Функция</p>
                        <div class="select-div function-select">
                            <span class="empty-span">Выберите функцию</span>
                            <i class="caret"></i>

                            <ul class="function-list hidden"></ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary set-function-button">Выбрать</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окна выбора функции во вкладке Функции-->

    <!--    Модальное окно удаления функции -->
    <div class="modal fade" tabindex="-1" role="dialog" id="deleteFunctionModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="icon-delete-btn"></i>
                        Удаление функции
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <p>
                            Вы действительно хотите удалить функцию: <br>&laquo;<span class="f-title"></span>&raquo; ?
                        </p>
                        <p>
                            Тип Функции: <span class="type-function-title"></span>
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary delete-function-button"
                                data-dismiss="modal">Да</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Нет</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окна удаления функции -->

    <!-- Контейнер видов объектов   -->
    <div class="object-kinds">
    </div>

    <!-- Контейнер блока типовых объектов и их параметров   -->
    <div class="parameters-container">
        <div class="object-block">
            <div id="filters" class="search-field">
                <button id="add_typical_object" class="add-typical-object">
                    <i class="icon-add-current-btn"></i>
                </button>
                <div id="input_container">
                    <input id="filter_text" placeholder="Введите текст...">
                </div>
                <button id="clear_filter_button" class="clear_filter_btn" title="Очистить фильтр">&times;</button>
                <div id="accept_filters">
                    <i class="icon-filter-search-btn"></i>
                </div>
            </div>
            <div class="panel-group" id="main_object_type_container"></div>
        </div>
        <div class="object-parameters">
            <ul id="parameter_types" class="nav nav-tabs">
                <li class="active common-info">
                    <span class="save-btn" title="Сохранить все значения"><img src="../frontend/web/img/typical_objects/save_icon.svg"></span>
                    <a href="#common_info">Общие сведения</a>
                    <span class="reset-to-defaults-btn" title="Вернуть исходные данные"><img src="../frontend/web/img/typical_objects/default_value.svg"></span>
                </li>
                <li class="parameters">
                    <span class="save-btn" title="Сохранить все значения"><img src="../frontend/web/img/typical_objects/save_icon.svg"></span>
                    <button id="add_global_parameter_btn" class="add-global-parameter tab-add-button" title="Добавить параметр">
                        <i class="icon-add-current-btn"></i>
                    </button>
                    <a href="#properties">Параметры</a>
                    <span class="reset-to-defaults-btn" title="Вернуть исходные данные"><img src="../frontend/web/img/typical_objects/default_value.svg"></span>
                </li>
                <li class="resources">
                    <span class="save-btn" title="Сохранить все значения"><img src="../frontend/web/img/typical_objects/save_icon.svg"></span>
                    <button id="add_resource_parameter_btn" class="add-resource-parameter tab-add-button" title="Добавить параметр">
                        <i class="icon-add-current-btn"></i>
                    </button>
                    <a href="#resource">Ресурс</a>
                    <span class="reset-to-defaults-btn" title="Вернуть исходные данные"><img src="../frontend/web/img/typical_objects/default_value.svg"></span>
                </li>
                <li class="functions">
                    <!--                    <span class="save-btn" title="Сохранить все значения"><img src="../frontend/web/img/typical_objects/save_icon.svg"></span>-->
                    <button id="add_function_btn" class="add-function-button tab-add-button" title="Выбрать функцию">
                        <i class="icon-add-current-btn"></i>
                    </button>
                    <a href="#function_tab">Функции</a>
                    <!--                <span class="reset-to-defaults-btn" title="Вернуть исходные данные"><img src="../frontend/web/img/typical_objects/default_value.svg"></span>-->
                </li>
                <li class="location-info hidden">
                    <span class="save-btn" title="Сохранить все значения"><img src="../frontend/web/img/typical_objects/save_icon.svg"></span>
                    <a href="#location">Местоположение</a>
                    <span class="reset-to-defaults-btn" title="Вернуть исходные данные"><img src="../frontend/web/img/typical_objects/default_value.svg"></span>
                </li>
            </ul>

            <div id="tab_content" class="tab-content">
                <div id="common_info" class="tab-pane fade in active col-xs-12">
                    <div id="common_info_content">
                        <div class="info-block">
                            <p class="parameter-option">
                                <label for="factory_number">Заводской №</label>
                                <input type="text" id="factory_number" placeholder="Введите заводской №..." data-parameter-id="160">
                            </p>
                            <p class="parameter-option">
                                <label for="object_title">Шаблон наименования</label>
                                <input type="text" id="object_title" placeholder="Введите наименование..." data-parameter-id="161">
                            </p>
                            <p class="parameter-option">
                                <label for="inventory_number">Инвентарный №</label>
                                <input type="text" id="inventory_number" placeholder="Введите инвентарный №..." data-parameter-id="104">
                            </p>
                            <p class="parameter-option before-select">
                                Тип объекта
                            </p>
                            <div class="select-div object-type-select">
                                <span class="empty-span" id="choose_object_type">Выберите тип объекта</span>
                            </div>
                            <p class="parameter-option">
                                <label for="commissioning_date">Дата ввода в эксплуатацию</label>
                                <input type="text" id="commissioning_date" placeholder="Введите дату..." data-parameter-id="163">
                                <i class="icon-calendar"></i>
                            </p>
                            <p class="parameter-option">
                                <label for="service_life">Срок службы<span class="unit-title">, мес</span></label>
                                <input type="text" id="service_life" placeholder="Введите значение..." data-parameter-id="165">
                            </p>
                            <div class="image-model-block">
                                <p class="model-2d model-fields">
                                    <span>
                                        <span class="rect-checkbox hidden">
                                            <i class="check check-ok"></i>
                                        </span>
                                        <span class="type-title">2D-модель</span>
                                    </span>
                                    <span>
                                        <input type="text" class="file-name" placeholder="Путь к модели">
                                        <input type="file" id="upload_2d" title="" data-parameter-id="168">
                                        <button class="upload">Загрузить</button>
                                    </span>
                                </p>
                                <p class="model-3d model-fields hidden">
                                    <span>
                                         <span class="rect-checkbox">
                                            <i class="check"></i>
                                        </span>
                                        <span class="type-title">3D-модель</span>
                                    </span>
                                    <span>
                                        <span class="file-name"></span>
                                        <input type="file" id="upload_3d" title="">
                                        <button class="upload">Загрузить</button>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="model-block">
                            <img id="modelImage" class="model-2d-image">
                        </div>
                        <div class="zoomButtons">
                            <button id="zoomIn"><img src="../img/specific_object/zoom_plus.png" alt="Zoom in"></button>
                            <button id="zoomOut"><img src="../img/specific_object/zoom_minus.png" alt="Zoom out"></button>
                            <button id="zoomReset"><img src="../img/specific_object/zoom_reset.png" alt="Zoom reset"></button>
                        </div>
                    </div>
                </div>
                <div id="properties" class="tab-pane fade col-xs-12">
                    <div id="properties_content">

                    </div>
                </div>
                <div id="resource" class="tab-pane fade col-xs-12">
                    <div id="resource_content"></div>
                </div>
                <div id="function_tab" class="tab-pane fade col-xs-12">
                    <div id="functions_content">
                        <div class="row hidden" id="tableHeader">
                            <div class="th-function-type col-xs-3">Тип функции</div>
                            <div class="col-xs-9 function-info">
                                <div class="th-function-title col-xs-6">Функция</div>
                                <div class="th-script-name col-xs-6">Название скрипта</div>
                            </div>
                        </div>
                        <div class="function-table row" id="functionTable">
                        </div>
                    </div>
                </div>
                <div id="location" class="tab-pane fade col-xs-12 hidden">
                    <div id="location_content">
                        <div class="info-block">
                            <p class="parameter-option before-select">
                                Выработка
                            </p>
                            <div class="select-div excavation-functions function-select">
                                <span class="hovered-list-element" id="choose_function_for_excavation">Алгоритм расчёта параметра задаётся на вкладке Функции</span>
                            </div>
                            <p class="parameter-option before-select">
                                Координаты: X, Y, Z
                            </p>
                            <div class="select-div sensor-select">
                                <span class="empty-span" id="choose_sensor_for_object_location">Выберите датчик</span>
                                <i class="caret"></i>
                                <div class='search'>
                                    <input class='selectSearch' id='selectSearch' type='search' placeholder='Поиск функции...'>
                                    <button id='clearSelectSearch' class='clearSearchButton btn close'>&times;</button>
                                    <button class='searchButton'></button>
                                </div>
                                <ul class="sensor-list hidden">
                                </ul>
                            </div>
                        </div>
                        <div class="model-block"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$this->registerJsFile('/js/pickmeup.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/jquery-ui.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' =>
    View::POS_END]);
$this->registerJsFile('/js/handbook-typical-object.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);

?>
