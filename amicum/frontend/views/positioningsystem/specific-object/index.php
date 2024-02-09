<?php
/* @var $this yii\web\View */
use yii\web\View;
use frontend\assets\AppAsset;

$this->title = "Конкретные объекты";
$this->registerCssFile('/css/pickmeup.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/filters.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/specific_object.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/jquery-ui.min.css', ['depends' => [AppAsset::className()]]);

?>

<!-- Меню действий -->
<div id="blockActionMenu" class="container-of-action-menu">
    <div id="actionMenu" class="action-menu">
        <button type="button" id="btn_move_obj" data-target="#moveObject" data-toggle="modal"
                title="Переместить конкретный объект"></button>
        <button type="button" id="btn_set_lamp" data-target="#setEquipmentLamp" title="Привязать метку"></button>
        <button type="button" id="btn_copy" data-toggle="modal" title="Добавить параметр всем конкретным объектам">
            <i class="icon-copy_btn"></i>
        </button>
        <button type="button" id="btn_delete" data-target="#deleteObject" data-toggle="modal"
                title="Удалить конкретный объект">
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
    <!--    Модальное окно добавления конкретного объекта    -->
    <div class="modal fade" tabindex="-1" role="dialog" id="addSpecificObject">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="icon-add-current-btn"></i>
                        Добавление конкретного объекта</h4>
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

                    <div class="row padding-style">
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
                            Типовой объект
                        </p>
                        <div class="select-div typical-object-select">
                            <span class="empty-span">Выберите типовой объект</span>
                            <i class="caret"></i>
                            <ul class="typical-object-list hidden"></ul>
                        </div>
                    </div>

                    <div class="row padding-style">
                        <p>
                            Наименование конкретного объекта
                        </p>
                        <input type="text" class="padding-in specific-object-name full-width"
                               placeholder="Введите наименование...">
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary add-specific-object-button"
                                data-dismiss="modal">Добавить</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окна добавления конкретного объекта-->

    <!--    Модальное окно привязки метки оборудованию -->
    <div class="modal fade" tabindex="-1" role="dialog" id="setEquipmentLamp">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="settings-icon"></i>
                        <span>Привязка метки к &laquo;</span><span class="equipment-title"></span>&raquo;
                    </h4>
                </div>
                <div class="modal-body">

                    <div class="row padding-style">
                        <p>Метка</p>
                        <div class="select-div equipment-lamp-select" id="equipmentLampSelect">
                            <span class="empty-span">Выберите метку</span>
                            <i class="caret"></i>
                            <div class="search">
                                <input class="selectSearch" id="selectSearch" type="search" placeholder="Поиск метки...">
                                <button id="clearSelectSearch" class="clearSearchButton btn close">&times;</button>
                                <button class="searchButton"></button>
                            </div>
                            <ul class="equipment-lamp-list hidden"></ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary set-lamp-button"
                                data-dismiss="modal">Привязать</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окна привязки метки оборудованию -->

    <!--    Модальное окно перемещение конкретного объекта    -->
    <div class="modal fade" tabindex="-1" role="dialog" id="moveObject">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="move-btn-icon"></i>
                        <span>Перемещение конкретного объекта</span></h4>
                </div>
                <div class="modal-body">

                    <div class="row padding-style">
                        <p>Объект для перемещения:</p>
                        <div class="object-title"></div>
                    </div>

                    <div class="row padding-style last-field">
                        <p>Откуда:</p>
                        <p>Тип объекта</p>
                        <div class="objects-type-name"></div>
                    </div>

                    <div class="row padding-style last-field">
                        <p>Типовой объект</p>
                        <div class="type-object-name"></div>
                    </div>

                    <div class="row padding-style">
                        <p>Куда:</p>
                        <p>Типовой объект</p>
                        <div class="select-div typical-object-select">
                            <span class="empty-span">Выберите типовой объект</span>
                            <i class="caret"></i>
                            <ul class="typical-object-list hidden"></ul>
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
    <!--Конец модального окна перемещения конкретного объекта-->

    <!--    Модальное окно удаления конкретного объекта-->
    <div class="modal fade" tabindex="-1" role="dialog" id="deleteObject">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="icon-delete-btn"></i>
                        Удаление конкретного объекта
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
    <!--Конец модального окна удаления конкретного объекта-->

    <!--    Модальное окно копирования типовых параметров-->
    <div class="modal fade" tabindex="-1" role="dialog" id="addParametersForAllSpecificObjects">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="icon-copy_btn"></i>
                        Копирование параметров типового объекта
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <p>
                            Параметр
                        </p>
                        <div class="select-div parameter-select" id="parameterForAllSpecificObjects">
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
                    <div class="row padding-style">
                        <p>
                            Значение
                        </p>
                        <input type="text" class="parameter-value full-width padding-in" placeholder="Значение параметра">
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary add-main-parameter-button"
                                data-dismiss="modal">Добавить</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окно копирования типовых параметров-->

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
                        <div class="select-div parameter-type-select">
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

    <!--    Модальное окно загрузки выработок через файл   -->
    <div class="modal fade" tabindex="-1" role="dialog" id="upload_places">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="icon-add-current-btn"></i>
                        Загрузка файла Excel</h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <p>Шахта</p>
                        <div class="select-div mine-select">
                            <span class="empty-span">Выберите шахту</span>
                            <i class="caret"></i>
                            <div class="search">
                                <input class="selectSearch" id="selectSearch" type="search" placeholder="Поиск параметра...">
                                <button id="clearSelectSearch" class="clearSearchButton btn close">&times;</button>
                                <button class="searchButton"></button>
                            </div>
                            <ul class="mine-list hidden"></ul>
                        </div>
                    </div>

                    <div class="row padding-style">
                        <p>Файл</p>
                        <div class="file-select">
                            <form id="upload-spreadsheet">
                                <input type="text" id="filePlaceholder" placeholder="Выберите файл...">
                                <input type="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel, .ods, .xlsx" id="mineFile">
                            </form>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary upload-places-button">Загрузить</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окна загрузки выработок через файл-->



    <!-- Контейнер видов объектов   -->
    <div class="object-kinds">
    </div>

    <!-- Контейнер блока типовых объектов и их параметров   -->
    <div class="parameters-container">
        <div class="object-block">
            <div id="filters" class="search-field">
                <button id="add_specific_object" class="add-specific-object" title="Добавить конкретный объект">
                    <i class="icon-add-current-btn"></i>
                </button>
                <div id="input_container">
                    <input id="filter_text" placeholder="Введите текст...">
                </div>
                <button id="clear_filter_button" class="clear_filter_btn" title="Очистить фильтр">
                    &times;
                </button>
                <div id="accept_filters">
                    <i class="icon-filter-search-btn"></i>
                </div>
            </div>
            <div class="scrollable-container">
                <div class="panel-group" id="main_object_type_container"></div>
            </div>
            <div class="upload-places hidden">
                <button id="updateMine" class="col-xs-12 col-md-12 col-lg-4">Обновить схему</button>
                <button id="uploadMineDoc" class="col-xs-12 col-md-12 col-lg-4">Загрузить схему шахты</button>
                <button id="reinitializeCache" class="col-xs-12 col-md-12 col-lg-4">Обновить кэш</button>
            </div>
        </div>
        <div class="object-parameters">
            <ul id="parameter_types" class="nav nav-tabs">
                <li class="active common-info">
                    <span class="save-btn" title="Сохранить все значения"><img src="/img/typical_objects/save_icon.svg"></span>
                    <a href="#common_info">Общие сведения</a>
                    <span class="reset-to-defaults-btn" title="Вернуть исходные данные"><img src="/img/typical_objects/default_value.svg"></span>
                </li>
                <li class="parameters">
                    <span class="save-btn" title="Сохранить все значения"><img src="/img/typical_objects/save_icon.svg"></span>
                    <button id="add_global_parameter_btn" class="add-global-parameter tab-add-button" title="Добавить параметр">
                        <i class="icon-add-current-btn"></i>
                    </button>
                    <a href="#properties">Параметры</a>
                    <span class="reset-to-defaults-btn" title="Вернуть исходные данные"><img src="/img/typical_objects/default_value.svg"></span>
                </li>
                <li class="resources hidden">
                    <span class="save-btn" title="Сохранить все значения"><img src="/img/typical_objects/save_icon.svg"></span>
                    <button id="add_resource_parameter_btn" class="add-resource-parameter tab-add-button" title="Добавить параметр">
                        <i class="icon-add-current-btn"></i>
                    </button>
                    <a href="#resource">Ресурс</a>
                    <span class="reset-to-defaults-btn" title="Вернуть исходные данные"><img src="/img/typical_objects/default_value.svg"></span>
                </li>
            </ul>

            <div id="tab_content" class="tab-content">
                <div id="common_info" class="tab-pane fade in active col-xs-12">
                    <div id="common_info_content">
                        <div class="info-block">
                            <p class="parameter-option">
                                <label for="object_title">Наименование</label>
                                <input type="text" id="object_title"  data-parameter-id="162" placeholder="Введите наименование...">
                            </p>
                            <p class="parameter-option">
                                <label for="factory_number">Заводской №</label>
                                <input type="text" id="factory_number" data-parameter-id="160" placeholder="Введите заводской №...">
                            </p>
                            <p class="parameter-option">
                                <label for="inventory_number">Инвентарный №</label>
                                <input type="text" id="inventory_number" data-parameter-id="104" placeholder="Введите инвентарный №...">
                            </p>
                            <p class="parameter-option before-select">
                                Тип объекта
                            </p>
                            <div class="select-div object-type-select">
                                <span class="empty-span" id="choose_object_type">Выберите тип объекта</span>
                            </div>
                            <p class="parameter-option">
                                <label for="commissioning_date">Дата ввода в эксплуатацию</label>
                                <input type="text" id="commissioning_date" data-parameter-id="163" placeholder="Введите дату...">
                                <i class="icon-calendar"></i>
                            </p>
                            <p class="parameter-option">
                                <label for="service_life">Срок службы<span class="unit-title">, мес</span></label>
                                <input type="text" id="service_life" data-parameter-id="165" placeholder="Введите значение...">
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
                                        <input type="file" id="upload_2d" title="" accept="image/jpeg, image/png" data-parameter-id="168">
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
                    <div id="properties_content"></div>
                </div>
                <div id="resource" class="hidden tab-pane fade col-xs-12">
                    <div id="resource_content"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerJsFile('/js/pickmeup.min.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()],'position' =>
    View::POS_END]);
$this->registerJsFile('/js/jquery-ui.min.js', ['depends' => [AppAsset::className()],'position' =>
    View::POS_END]);
$this->registerJsFile('/js/specific_object.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);

?>
