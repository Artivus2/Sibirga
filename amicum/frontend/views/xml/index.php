<?php
/* @var $this yii\web\View */
use yii\web\View;
$this->title = "Модуль выгрузки данных в XML";
$this->registerCssFile('/css/jquery.datetimepicker.min.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/xml.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
?>

<!--Прелоадер-->
<div id="preload" class="hidden">
    <div class="circle-container">
        <div id="circle_preload"></div>
        <h4 class="preload-title">Идёт загрузка</h4>
    </div>
</div>
<!--Прелоадер-->
<!-- Меню действий -->
<div id="blockActionMenu" class="container-of-action-menu">
    <div id="actionMenu" class="action-menu">
        <button type="button" id="btn_edit" data-toggle="modal"
                title="Редактировать">
            <i class="icon-edit-btn"></i>
        </button>
        <button type="button" id="btn_delete" data-toggle="modal"
                title="Удалить">
            <i class="icon-delete-btn"></i>
        </button>
    </div>
    <div class="triangle-position">
        <div id="triangleContainer" class="triangle-container">
            <div class="triangle-action-menu" id="actionMenuTriangle"></div>
        </div>
    </div>
</div>
<!--    Модальное окно добавления модели-->
<div class="modal fade" id="addModelModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><i class="icon-add-current-btn"></i>
                    <span>Добавление модели</span>
                </h4>
            </div>
            <div class="modal-body">

                <div class="row padding-style">
                    <p class="model-title">Название модели</p>

                    <div class="select-div model-select">
<!--                        <span class="model-name empty-span" id="model_select">Выберите название модели</span>-->
<!--                        <i class="caret"></i>-->
                        <input type="text" class="full-width new-model-name" placeholder="Введите название модели">
                        <div class="search">
                            <input class="selectSearch" id="selectSearch" type="search" placeholder="Поиск модели...">
                            <button id="clearSelectSearch" class="clearSearchButton btn close">&times;</button>
                            <button class="searchButton"></button>
                        </div>

                        <ul class='model-list model-list hidden'></ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="button-content">
                    <button type="button" class="btn btn-primary add-model-modal-button"
                            data-dismiss="modal">Добавить</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!--Конец модального окна добавления модели-->

<!--    Модальное окно редактирования модели-->
<div class="modal fade" tabindex="-1" role="dialog" id="editModel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><i class="icon-edit-btn"></i>
                    <span>Редактирование модели</span>
                </h4>
            </div>
            <div class="modal-body">

                <div class="row padding-style">
                    <p>
                        Наименование модели
                    </p>
                    <input id="edit_model_name" type="text" class="padding-in edit-model-name full-width"
                           placeholder="Введите наименование...">
                </div>
            </div>
            <div class="modal-footer">
                <div class="button-content">
                    <button type="button" class="btn btn-primary edit-model-button"
                            data-dismiss="modal">Редактировать</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!--Конец модального окна редактирования модели-->

<!--    Модальное окно удаления модели-->
<div class="modal fade" tabindex="-1" role="dialog" id="deleteModel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><i class="icon-delete-btn"></i>
                    Удаление модели
                </h4>
            </div>
            <div class="modal-body">
                <div class="row padding-style">
                    <p>
                        Вы действительно хотите удалить модель "<span class="model-title"></span>" ?
                    </p>

                </div>
            </div>
            <div class="modal-footer">
                <div class="button-content">
                    <button type="button" class="btn btn-primary delete-model-button"
                            data-dismiss="modal">Удалить</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!--Конец модального окна удаления модели-->

<!--    Модальное окно добавления конфигурации-->
<div class="modal fade" id="addModelConfigModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><i class="icon-add-current-btn"></i>
                    <span>Добавление конфигурации выгрузки модели</span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row padding-style">
                    <p class="model-title">Название модели: <span></span></p>
                </div>
                <div class="row padding-style">
                    <p>Способ выгрузки</p>
                    <div class="select-div select-saving-way">
                        <span class="empty-span">Выберите способ выгрузки</span>
                        <i class="caret"></i>
                        <ul class="saving-way-list hidden"></ul>
                    </div>
                </div>
                <div class="row padding-style">
                    <p>Адрес / путь / номер телефона</p>
                    <input type="text" class="address-field full-width" placeholder="Адрес сохранения / отправки файла / номер телефона"></div>

                <div class="row padding-style">
                    <p>Периодичность</p>
                    <input type="text" class="time-period full-width" placeholder="Введите периодичность">
                </div>
                <div class="row padding-style">
                    <p>Единица измерения времени</p>
                    <div class="select-div time-unit-select">
                        <span class="empty-span">Выберите единицу измерения времени</span>
                        <i class="caret"></i>
                        <ul class="time-unit-list hidden"></ul>
                    </div>
                </div>
                <div class="row padding-style">
                    <p>Дата начала</p>
                    <input type="text" id="date_start" class="date-start-exporting full-width calendar-input" placeholder="Введите дату начала">
                    <i class="icon-calendar"></i>
                </div>
                <div class="row padding-style">
                    <p>Дата окончания</p>
                    <input type="text" id="date_end" class="date-end-exporting full-width calendar-input" placeholder="Введите дату завершения">
                    <i class="icon-calendar"></i>
                </div>
                <div class="row padding-style">
                    <p>Событие</p>
                    <div class="select-div event-select">
                        <span class="empty-span">Выберите событие</span>
                        <i class="caret"></i>
                        <div class="search">
                            <input class='selectSearch' type='search' placeholder='Поиск события...'>
                            <button class='clearSearchButton btn close'>&times;</button>
                            <button class='searchButton'></button>
                        </div>
                        <ul class="event-list hidden"></ul>
                        <button class="clear_filter_btn clear-value-btn" title="Удалить значение события?">&times;</button>
                    </div>
                </div>
                <div class="row padding-style">
                    <p>Группа оповещения</p>
                    <div class="select-div alarm-group-select">
                        <span class="empty-span">Выберите группу оповещения</span>
                        <i class="caret"></i>
                        <div class="search">
                            <input class='selectSearch' type='search' placeholder='Поиск группы оповещения...'>
                            <button class='clearSearchButton btn close'>&times;</button>
                            <button class='searchButton'></button>
                        </div>
                        <ul class="alarm-group-list hidden"></ul>
                        <button class="clear_filter_btn clear-value-btn" title="Удалить значение группы оповещения?">&times;</button>
                    </div>
                </div>
                <div class="row padding-style">
                    <p>Порядок отправки</p>
                    <input type="text" class="config-position full-width" placeholder="Введите порядок отправки сообщений">
                </div>
                <div class="row padding-style">
                    <p>Описание</p>
                    <textarea rwos="3" class="config-description full-width" placeholder="Введите описание"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <div class="button-content">
                    <button type="button" class="btn btn-primary add-model-config-button"
                            data-dismiss="modal">Добавить</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!--Конец модального окна добавления конфигурации-->

<!--    Модальное окно удаления конфигурации-->
<div class="modal fade" tabindex="-1" role="dialog" id="deleteModelConfiguration">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><i class="icon-delete-btn"></i>
                    Удаление конфигурации
                </h4>
            </div>
            <div class="modal-body">
                <div class="row padding-style">
                    <p>
                        Вы действительно хотите удалить конфигурацию по способу выгрузки "<span class="saving-way-title"></span>" модели <span class="model-title"></span>?
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <div class="button-content">
                    <button type="button" class="btn btn-primary delete-config-model-button"
                            data-dismiss="modal">Удалить</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!--Конец модального окна удаления конфигурации-->

<!--  Фильтр  -->
<div class="row hidden" id="filters_block">
    <div id="filters_label">
        <span class="filters-title">Фильтр: </span>
    </div>
    <div id="filters" class="col-xs-12">
        <div id="filter_container">
        </div>
        <div id="input_container">
            <input id="filter_text" placeholder="Введите текст...">
        </div>
        <div id="add_filter_button" title="Добавить фильтр">
            <i class="icon-filter-add-btn"></i>
        </div>
        <button id="clear_filter_button" class="clear_filter_btn" title="Очистить фильтр">
            &times;
        </button>
        <div id="accept_filters" onclick="apply_filter();">
            <i class="icon-filter-search-btn"></i>
        </div>
    </div>

    <div id="filters_types_list" class="hidden main-filter">
        Фильтровать по: <button id="close_modal" class="close">&times;</button>
        <div class="filter_types">
            <!--            <div onclick="add_filter('kinds')">Виды групп ситуаций</div>-->
            <!--            <div class="line-filters"></div>-->
            <!--            <div onclick="add_filter('groups')">Группы ситуаций</div>-->
            <!--            <div class="line-filters"></div>-->
            <!--            <div onclick="add_filter('events')">события</div>-->
            <!--            <div class="line-filters"></div>-->
            <!--            <div onclick="add_filter('events')">События</div>-->
        </div>

    </div>
    <div id="filters_list" class="hidden">

        <div class="filter_types">
        </div>
    </div>
</div>
<!--Конец контейнера фильтра-->

<!--  Кнопка добавления модели  -->

<div class="add-model-btn-container">
    <button class="add-model-btn" title="Добавить модель">
        <i class="icon-add-current-btn"></i>
        Добавить</button>
</div>
<!-- Конец контейнера кнопки добавления модели -->

<!--Контейнер блока списка моделей-->
<div class="xml-container">
    <div class="xml-content">
        <div class="xml-table">
            <div class="xml-table-header">
                <span>Модель</span>
            </div>
            <div class="xml-table-body"></div>
            <div class="xml-table-footer">
                <div class="handbook-content__footer">
                    <div class="handbook-content__footer__pagination">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--Конец блока списка моделей-->

<!--  Контейнер блока настройки сохранения модели  -->
<div class="settings-container hidden">
    <div class="settings-header">
        <button class="turn-back-to-models-btn" title="Назад"><span class="turn-back-btn"><img src="img/turn_back.svg"></span>Вернуться назад</button>
        <p class="settings-header-title"><span class="header-text">Настройка сохранения модели</span><span class="quotes">&laquo;</span><span class="setting-model-title"></span><span class="quotes">&raquo;</span></p>
        <button class="add-model-config-btn" title="Добавить конфигурацию модели"><i class="icon-add-current-btn"></i>Добавить</button>
        <!--            <button class="save-model-settings-btn" title="Сохранить настройки"><span class="save-btn"><img src="img/typical_objects/save_icon.svg"></span>Сохранить</button>-->
    </div>
    <div class="settings-content">
        <div class="settings-table">
            <div class="settings-table-header">
                <div class="table-header-column"><span class="table-header-title">Способ выгрузки</span></div>
                <div class="table-header-column"><span class="table-header-title">Адрес / путь / номер телефона</span></div>
                <div class="table-header-column"><span class="table-header-title">Периодичность</span></div>
                <div class="table-header-column"><span class="table-header-title">Единица измерения времени</span></div>
                <div class="table-header-column"><span class="table-header-title">Дата начала</span></div>
                <div class="table-header-column"><span class="table-header-title">Дата окончания</span></div>
                <div class="table-header-column"><span class="table-header-title">Событие</span></div>
                <div class="table-header-column"><span class="table-header-title">Группа оповещения</span></div>
                <div class="table-header-column"><span class="table-header-title">Порядок отправки</span></div>
                <div class="table-header-column"><span class="table-header-title">Описание</span></div>
            </div>
            <div class="settings-table-body"></div>
            <div class="settings-table-footer">
                <div class="handbook-content__footer">
                    <div class="handbook-content__footer__pagination">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--  Конец контейнера блока настроек сохранения модели  -->

<?php
$this->registerJsFile('/js/Blob.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/FileSaver.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/jquery.datetimepicker.full.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' =>
    View::POS_END]);
$this->registerJsFile('/js/xml.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);

?>
