<?php

use frontend\assets\AppAsset;
use yii\web\View;

$arrayCompany = 'var parameterTypesArray = '
    . json_encode($parameterTypes) . ', sensorArray = ' . json_encode($sensorList) . ', 
    kindParameters = ' . json_encode($kindParameters) . ', unitsArray = ' . json_encode($units) . ', 
   placeArray = ' . json_encode($place) . ', asmtpArray = ' . json_encode($asmtp) . ', 
    sensorTypeArray = ' . json_encode($sensorType) . ';';

$this->registerCssFile('/css/filters.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/main.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/jquery.contextMenu.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/pickmeup.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/employees.css', ['depends' => [AppAsset::className()]]);
$this->title = "Справочник сотрудников";
?>

    <div class="row" id="filters_block">
        <div class="button-create-main col-xs-3">
            <button class="button-create">
                <div class="button-content">
                    <i class="icon-filter-add-btn" id="create_button"></i>
                    <span class="button-title">Создать</span>
                    <i class="caret"></i>
                </div>
            </button>
            <div class="button-type-list">
                <div class="creating company" onclick="callModalWindowCompany();">Предприятие</div>
                <div class="creating department" onclick="callModalWindowDepartment()">Подразделение</div>
<!--                <div class="creating employee" onclick="callModalWindowEmployee('create-button')">Сотрудника</div>-->
            </div>
        </div>

        <div id="filters" class="col-xs-9">
            <div id="filter_container">
            </div>
            <div id="input_container">
                <input id="filter_text" placeholder="Введите фамилию или таб. номер">
            </div>
            <button id="clear_filter_button" class="clear_filter_btn" title="Очистить фильтр">
                &times;
            </button>
            <div id="accept_filters">
                <i class="icon-filter-search-btn"></i>
            </div>
        </div>

        <div id="filters_types_list" class="hidden main-filter">
            Фильтровать по:
            <button id="close_modal" class="close">&times;</button>
            <div class="filter_types"></div>
        </div>
        <div id="filters_list" class="hidden">

            <div class="filter_types">
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

    <!-- Создание компании -->
    <div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel" id="add_company">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="gridSystemModalLabel">
                        <i class="icon-filter-add-btn" id="create_button"></i>
                        Добавление предприятия
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <input id="add_name" type="text" class="padding-in add-name full-width"
                               placeholder="Введите название..." autocomplete="off">
                    </div>
                    <div class="row padding-style">
                        <p>Выберите вышестоящее предприятие</p>
                        <div class="select-div companies">
                            <span class="empty-span company-select"
                                  id="company_select_parent">Выберите предприятие...</span><i class="caret"></i>
                            <div class="search">
                                <input class="selectSearch" id="selectSearch" type="search"
                                       placeholder="Поиск предприятия...">
                                <button id="clearSelectSearch" class="clearSearchButton btn close">&times;</button>
                                <button class="searchButton"></button>
                            </div>
                            <ul class="company-list hidden"></ul>
                        </div>
                    </div>
                    <div class="row padding-style last-field">
                        <p>Режим работы</p>
                        <div class="select-div work-mode">
                            <span class="empty-span" id="work_mode_create_company">Выберите режим работы...</span><i
                                    class="caret"></i>
                            <ul class="work-mode-list hidden">
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary add-company" data-dismiss="modal">Добавить</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Создание подразделения -->
    <div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel" id="add_department">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="gridSystemModalLabel">
                        <i class="icon-filter-add-btn" id="create_button"></i>
                        Добавление подразделения
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <input id="add_name_department" type="text" class="padding-in add-name full-width"
                               placeholder="Введите название..." autocomplete="off">
                    </div>
                    <div class="row padding-style">
                        <p>Выберите предприятие</p>
                        <div class="select-div companies">
                            <span class="empty-span" id="choose_company_parent_dep">Выберите предприятие...</span><i
                                    class="caret"></i>
                            <div class="search">
                                <input class="selectSearch" id="selectSearch" type="search"
                                       placeholder="Поиск предприятия...">
                                <button id="clearSelectSearch" class="clearSearchButton btn close">&times;</button>
                                <button class="searchButton"></button>
                            </div>
                            <ul class="company-list hidden"></ul>
                        </div>
                    </div>
                    <div class="row padding-style">
                        <p>Выберите тип подразделения</p>
                        <div class="select-div department-type">
                            <span class="empty-span" id="type_work_create_dep">Выберите тип подразделения...</span><i
                                    class="caret"></i>
                            <ul class="department-type-list hidden">
                            </ul>
                        </div>
                    </div>
                    <div class="row padding-style last-field">
                        <p>Режим работы</p>
                        <div class="select-div work-mode">
                            <span class="empty-span" id="work_mode_create_dep">Выберите режим работы...</span>
                            <i class="caret"></i>
                            <ul class="work-mode-list hidden"></ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary add-department" data-dismiss="modal">Добавить
                        </button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- Редактирование подразделения -->
    <div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel" id="edit_department">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="gridSystemModalLabel">
                        <i class="icon-edit-btn" id="create_button"></i>
                        Редактирование подразделения</h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <input id="edit_name_department" type="text" class="padding-in add-name full-width"
                               placeholder="Введите название..." autocomplete="off">
                    </div>
                    <div class="row padding-style">
                        <p>Выберите тип подразделения</p>
                        <div class="select-div department-type">
                            <span class="empty-span" id="type_work_edit_dep">Выберите тип подразделения...</span>
                            <i class="caret"></i>
                            <ul class="department-type-list hidden"></ul>
                        </div>
                    </div>
                    <div class="row padding-style last-field">
                        <p>Режим работы</p>
                        <div class="select-div work-mode">
                            <span class="empty-span workmode-edit-department" id="work_mode_edit_department">Выберите режим работы...</span>
                            <i class="caret"></i>
                            <ul class="work-mode-list hidden">
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary edit-department" data-dismiss="modal">Изменить</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Редактирование компании -->
    <div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel" id="edit_company">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="gridSystemModalLabel">
                        <i class="icon-filter-add-btn" id="create_button"></i>
                        Редактирование подразделения</h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <input id="edit_name_company" type="text"
                               class="padding-in add-name full-width title-edit-company"
                               placeholder="Введите название..." autocomplete="off">
                    </div>
                    <div class="row padding-style">
                        <p>Выберите тип подразделения</p>
                        <div class="select-div department-type">
                            <span class="empty-span" id="type_work_edit_dep">Выберите тип подразделения...</span>
                            <i class="caret"></i>
                            <ul class="department-type-list hidden"></ul>
                        </div>
                    </div>
                    <div class="row padding-style last-field">
                        <p>Режим работы</p>
                        <div class="select-div work-mode">
                            <span class="empty-span workmode-edit-company" id="work_mode_edit_company">Выберите режим работы...</span><i
                                    class="caret"></i>
                            <ul class="work-mode-list hidden"></ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary edit-company" data-dismiss="modal">Изменить</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Удаление подразделения -->
    <div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel" id="delete_department">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="gridSystemModalLabel">
                        <i class="icon-delete-btn" id="create_button"></i>
                        Удаление подразделения</h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <p>
                            Вы действительно хотите удалить подразделение: <span id="delete_department_title"></span>
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary delete-department" data-dismiss="modal">Удалить</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Удаление предприятия -->
    <div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel" id="delete_company">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="gridSystemModalLabel">
                        <i class="icon-delete-btn" id="create_button"></i>
                        Удаление предприятия</h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <p>
                            Вы действительно хотите удалить предприятие: <span id="delete_company_title"></span>
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary delete-company" data-dismiss="modal">Удалить</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Удаление работника -->
    <div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel" id="delete_worker">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="gridSystemModalLabel"><i class="icon-delete-btn" id="create_button"></i>Удаление
                        работника</h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <p>
                            Вы действительно хотите удалить работника: <span id="delete_worker_title"></span>
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary delete-worker" data-dismiss="modal">Удалить</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Создание сотрудника -->
    <div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel" id="add_employee">
        <div class="modal-dialog dop-media-dialog" role="document">
            <div class="modal-content">
                <div class="row">
                    <ul class="nav nav-tabs" id="employee_tabs">
                        <li class="active common-info">
                            <span class="save-btn" title="Сохранить все значения"><img
                                        src="../../img/typical_objects/save_icon.svg"></span>
                            <a href="#common_info">Общие сведения</a>
                            <span class="reset-to-defaults-btn" title="Вернуть исходные данные"><img
                                        src="../../img/typical_objects/default_value.svg"></span>
                        </li>
                        <li class="parameters">
                            <span class="save-btn" title="Сохранить все значения"><img
                                        src="../../img/typical_objects/save_icon.svg"></span>
                            <button id="add_global_parameter_btn" class="add-global-parameter tab-add-button"
                                    title="Добавить параметр">
                                <i class="icon-add-current-btn"></i>
                            </button>
                            <a href="#properties">Параметры</a>
                            <span class="reset-to-defaults-btn" title="Вернуть исходные данные"><img
                                        src="../../img/typical_objects/default_value.svg"></span>
                        </li>
                    </ul>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                </div>
                <div class="row">
                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane active" id="common_info">
                            <div class="col-xs-12 row">
                                <div class="fio-personal">
                                    <label for="lastName">Фамилия<span class="text-danger">*</span></label>
                                    <input id="lastName" type="text" placeholder="Введите фамилию..."
                                           autocomplete="off">
                                    <label for="firstName">Имя<span class="text-danger">*</span></label>
                                    <input id="firstName" type="text" placeholder="Введите имя..." autocomplete="off">
                                    <label for="patronymic">Отчество</label>
                                    <input id="patronymic" type="text" placeholder="Введите отчество..."
                                           autocomplete="off">
                                </div>
                            </div>
                            <div class="col-xs-12 row">
                                <div class="col-xs-6 personnel-info">
                                    <p>
                                        Пол
                                    </p>
                                    <div id="gender_block">
                                        <input id="man" class="fixed-value" value="М" type="checkbox" checked="checked">
                                        <label for="man">
                                                <span class="choose-gender">
                                                <i class="glyphicon glyphicon-ok"></i>
                                            </span>
                                            <span class="text-input">мужской</span>
                                        </label>
                                        <input id="woman" class="fixed-value" value="Ж" type="checkbox">
                                        <label for="woman">
                                                <span class="choose-gender">
                                                <i class="glyphicon"></i>
                                            </span>
                                            <span class="text-input">женский</span>
                                        </label>
                                    </div>
                                    <p>
                                        Рост
                                    </p>
                                    <input class="input-width" id="growth" type="text"
                                           placeholder="Введите рост в см..." maxlength="3" autocomplete="off">
                                    <p>
                                        Дата рождения<span class="text-danger">*</span>
                                    </p>
                                    <div class="data-birth-block">
                                        <input class="input-width calendar-input" id="birth_date" type="text"
                                               placeholder="Выберите дату..." autocomplete="off"><i
                                                class="icon-calendar" id="birth_icon"></i>
                                    </div>
                                    <p>
                                        Дата начала работы<span class="text-danger">*</span>
                                    </p>
                                    <div class="start-work">
                                        <input class="input-width calendar-input" id="date_start_work" type="text"
                                               placeholder="Выберите дату..." autocomplete="off"><i
                                                class="icon-calendar"></i>
                                    </div>
                                    <p>
                                        Дата окончания работы
                                    </p>
                                    <div class="end-work">
                                        <input class="input-width calendar-input" id="date_end_work" type="text"
                                               placeholder="Выберите дату..." autocomplete="off"><i
                                                class="icon-calendar"></i>
                                    </div>
                                    <p>
                                        Режим работы
                                    </p>
                                    <div class="select-div work-mode" id="job_mode">
                                        <span class="empty-span" id="work_mode_select">Выберите режим работы...</span><i
                                                class="caret"></i>
                                        <ul class="work-mode-list hidden">
                                        </ul>
                                    </div>
                                    <p>
                                        Тип работы
                                    </p>
                                    <div id="staff_type">
                                        <input type="checkbox" class="fixed-value" id="underground"
                                               value="Подземный персонал" checked="checked">
                                        <label class="underground type-object" for="underground">
                                                <span class="choose-work">
                                                    <i class="glyphicon glyphicon-ok"></i>
                                                </span>
                                            <span class="text-input">Подземный</span>
                                        </label>
                                        <input type="checkbox" class="fixed-value" id="surface"
                                               value="Поверхностный персонал">
                                        <label class="surface type-object" for="surface">
                                            <span class="choose-work">
                                                <i class="glyphicon"></i>
                                            </span>
                                            <span class="text-input">Поверхностный</span>
                                        </label>
                                    </div>

                                </div>
<!--                                <div class="col-xs-4 col-with-img">-->
<!--                                    <img id="images_gender_work" src="/img/underground-man.png">-->
<!--                                </div>-->
                                <div class="col-xs-6">
                                    <p>
                                        Предприятие<span class="text-danger">*</span>
                                    </p>
                                    <div class="select-div companies">
                                        <span class="empty-span company-select" id="company_select">Выберите предприятие...</span><i
                                                class="caret"></i>
                                        <div class="search">
                                            <input class="selectSearch" id="selectSearch" type="search"
                                                   placeholder="Поиск предприятия...">
                                            <button id="clearSelectSearch" class="clearSearchButton btn close">&times;
                                            </button>
                                            <button class="searchButton"></button>
                                        </div>
                                        <ul class="company-list hidden"></ul>
                                    </div>
<!--                                    <p>-->
<!--                                        Подразделение<span class="text-danger">*</span>-->
<!--                                    </p>-->
                                    <div class="select-div departments hidden">
                                        <span class="empty-span" id="department_select">Выберите подразделение...</span><i
                                                class="caret"></i>
                                        <div class="search">
                                            <input class="selectSearch" id="selectSearch" type="search"
                                                   placeholder="Поиск подразделения...">
                                            <button id="clearSelectSearch" class="clearSearchButton btn close">&times;
                                            </button>
                                            <button class="searchButton"></button>
                                        </div>
                                        <ul class="department-list hidden"></ul>
                                    </div>
                                    <p>
                                        Тип подразделения<span class="text-danger">*</span>
                                    </p>
                                    <div class="select-div department-type">
                                        <span class="empty-span"
                                              id="type_work_create_dep">Выберите тип подразделения...</span><i
                                                class="caret"></i>
                                        <ul class="department-type-list hidden">
                                        </ul>
                                    </div>
                                    <p>
                                        Должность<span class="text-danger">*</span>
                                    </p>
                                    <div class="select-div positions">
                                        <span class="empty-span" id="position_select">Выберите должность...</span><i
                                                class="caret"></i>
                                        <div class="search">
                                            <input class="selectSearch" id="selectSearch" type="search"
                                                   placeholder="Поиск должности...">
                                            <button id="clearSelectSearch" class="clearSearchButton btn close">&times;
                                            </button>
                                            <button class="searchButton"></button>
                                        </div>
                                        <ul class="position-list hidden"></ul>
                                    </div>
                                    <div class="pass-example">
                                        <div class="pass-content">
                                            <div class="pass-photo">
                                                <p class="upload-layer">
                                                    Загрузить<br>фото<br>
                                                    <span class="glyphicon glyphicon-download-alt"></span>
                                                </p>
                                                <div id="img_panel"></div>
                                                <form id="upload_photo" name="ajax_form" enctype="multipart/form-data">
                                                    <input type="file" id="img_path" name="imageFile"
                                                           accept="image/jpeg, image/png">
                                                </form>
                                            </div>
                                            <div class="content-text">
                                                <p class="text-title">Фамилия</p>
                                                <p class="text-in" id="content_last_name"></p>
                                                <p class="text-title">Имя</p>
                                                <p class="text-in" id="content_first_name"></p>
                                                <p class="text-title">Отчество</p>
                                                <p class="text-in" id="content_patronymic"></p>
                                            </div>
                                        </div>
                                        <div class="content-input">
                                            <div class="input-number">
                                                <p><span>Табельный №<span class="text-danger">*</span></span><input
                                                            id="staff_number" type="text"
                                                            placeholder="Введите табельный №..." autocomplete="off"
                                                            maxlength="15"></p>
                                            </div>
                                            <div class="input-number">
                                                <p><span>Пропуск №</span><input id="pass_number" type="text"
                                                                                placeholder="Введите № пропуска"
                                                                                autocomplete="off" maxlength="15"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="button-content">
                                        <button type="button" class="btn btn-primary add-worker">Добавить</button>
                                        <!--                                        <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть-->
                                        <!--                                        </button>-->
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-4 row note"><p><span><span class="text-danger">Звёздочкой(*)</span> обозначены обязательные для заполнения поля</span>
                                </p></div>
                        </div>
                        <div role="tabpanel" class="tab-pane" id="properties">
                            <div id="properties_content"></div>
                        </div>
                    </div><!-- /.tab-content -->
                </div><!-- /.row -->
                <!--</div> /.div -->
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    <!--  -->

    <!--    Модальное окно добавления параметра    -->
    <div class="modal fade" tabindex="-1" role="dialog" id="add_global_parameter">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
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

                            <ul class="parameter-kind-list hidden hidden"></ul>
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
                                <input class="selectSearch" id="selectSearch" type="search"
                                       placeholder="Поиск параметра...">
                                <button id="clearSelectSearch" class="clearSearchButton btn close">&times;</button>
                                <button class="searchButton"></button>
                            </div>
                            <ul class="parameter-list hidden hidden"></ul>
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
                                <input class="selectSearch" id="selectSearch" type="search"
                                       placeholder="Поиск параметра...">
                                <button id="clearSelectSearch" class="clearSearchButton btn close">&times;</button>
                                <button class="searchButton"></button>
                            </div>
                            <ul class="unit-list hidden hidden"></ul>
                        </div>
                    </div>
                    <div class="row padding-style">
                        <p>
                            Тип параметра
                        </p>
                        <div class="select-div parameter-type-select">
                            <span class="empty-span" id="parameter_select">Выберите тип параметра</span>
                            <i class="caret"></i>

                            <ul class="parameter-type-list hidden hidden"></ul>
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
                                data-dismiss="modal">Да
                        </button>
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
                                <input class="selectSearch" id="selectSearch" type="search"
                                       placeholder="Поиск параметра...">
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
                                data-dismiss="modal">Да
                        </button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Нет</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окна удаления параметра через кнопку рядом с названием вида параметров-->

    <!-- Меню действий -->
    <div id="blockActionMenu" class="container-of-action">
        <div class="buttons">
            <button id="add_employee_modal"><span class="icon-add-current-btn hidden"></span>
                <span>
                <img src="../../img/add_employee.svg" alt="">
            </span>
            </button>
            <button id="add_company_modal"><span class="icon-add-current-btn hidden"></span>
                <span>
              <img src="../../img/add_company.svg" alt="">

            </span>
            </button>
            <button id="add_department_modal"><span class="icon-add-current-btn hidden"></span>
                <span>
           <img src="../../img/add_department.svg" alt="">
            </span>
            </button>
            <button id="edit_company_modal"><span class="icon-edit-btn"></span></button>
            <button id="delete_company_modal"><span class="icon-delete-btn"></span></button>
        </div>
        <div class="bottom-triangle">
        </div>
    </div>
    <div class="row col-xs-12 employees-container">
        <!-- Левая часть справочника (список) -->
        <div class="col-xs-3 employees-name" id="height_script">
            <div class="container">
                <div class="panel-group" id="accordion">
                </div>
            </div>
        </div>
        <!-- Правая часть справочника (таблица) -->
        <div class="col-xs-9 employees-table-container">
            <div class="employees-table main-table" id="table">

                <div class="table-body">
                    <div class="table-header">
                        <div class="th-id" data-type="th-id"><span>Таб. №</span><span
                                    class="caret caret-id rotate180 hidden"></span></div>
                        <div class="th-name" data-type="th-name"><span>Ф. И. О.</span><span
                                    class="caret caret-name rotate180 hidden"></span></div>
                        <!--                <div class="th-gender" data-type="th-gender"><span>Пол</span><span class="caret caret-gender rotate180 hidden"></span></div>-->
                        <div class="th-date-birth" data-type="th-date-birth"><span>Дата рождения</span><span
                                    class="caret caret-date-birth rotate180 hidden"></span></div>
                        <div class="th-position" data-type="th-position"><span>Должность</span><span
                                    class="caret caret-position rotate180 hidden"></span></div>
                        <div class="th-company" data-type="th-company"><span>Подразделение</span><span
                                    class="caret caret-company rotate180 hidden"></span></div>
                        <div class="th-department" data-type="th-department"><span>Вышестоящее подразделение</span><span
                                    class="caret caret-department rotate180 hidden"></span></div>
                        <div class="th-work-start" data-type="th-work-start"><span>Начало работы</span><span
                                    class="caret caret-work-start rotate180 hidden"></span></div>
                        <div class="th-work-end" data-type="th-work-end"><span>Окончание работы</span><span
                                    class="caret caret-work-end rotate180 hidden"></span></div>
                    </div>
                </div>
                <div class="table-footer">
                    <div class="handbook-content__footer">
                        <div class="handbook-content__footer__rowsCount">Количество записей: <span
                                    id="actualRowCount">0</span></div>
                        <div class="handbook-content__footer__pagination">
                            <!-- Если разбить кнопки построчно, то между элементами появляется whitespace текстовый узел, как у inline-block элементов, из-за которого появляются отступы  TODO: решить проблему появления текстовых узлов -->
                            <button class="handbook-page-switch"><<</button><button class="handbook-page-switch"><</button><button class="handbook-page-switch numeric">1</button><button class="handbook-page-switch">></button><button class="handbook-page-switch">>></button>
                        </div>
                        <div class="handbook-content__footer__show">
                            Показывать по:
                            <div class="handbook-content__footer__show__buttons">
                            <a class="show-pages-button">20</a><a class="show-pages-button">50</a><a class="show-pages-button">100</a><a class="show-pages-button">200</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
$this->registerJs($arrayCompany, View::POS_HEAD, 'my-array-company-js');
$this->registerJsFile('/js/pickmeup.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/jquery.cookie.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/jquery.contextMenu.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()], 'position' =>
    View::POS_END]);
$this->registerJsFile('/js/employees.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
?>
