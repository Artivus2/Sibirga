<?php
use yii\web\View;
use frontend\assets\AppAsset;
$getSourceData = 'let model = ' . json_encode($model) . ';';
$this->registerJs($getSourceData, View::POS_HEAD, 'handbook-position-js');
$this->registerCssFile('/css/handbook-position.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/handbook-position.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->title = 'Справочник должностей';
?>
<!--Прелодер-->
<div id="preload" class="hidden">
    <div class="circle-container">
        <div id="circle_preload"></div>
        <h4 class="preload-title">Идёт загрузка</h4>
    </div>
</div>

<!-- Форма добавления должности и поиска-->
<div class="handbook-header">
    <div class="handbook-header__add-item" id="call_modal_add_window">
        <div class="button-add-left">
            <div class="glyphicon glyphicon-plus"></div>
            <div class="button-add-right"></div>
        </div>
        <div class="handbook-header__search-container"><span>Добавить </span></div>
    </div>
    <div class="handbook-header__search-container">
        <input type="text" class="handbook-header__input" placeholder="Введите поисковой запрос" id="job_search">
        <button class="clear-search-button" id="clear_button"></button>
        <button class="handbook-header__search-button" id="search_button"></button>
    </div>
</div>

<!-- Таблица -->

<div class="content-body">
    <!-- ШКАПКА ТАБЛИЦЫ -->
    <div class="header_table">
        <div class="head_table_id-1 handbook-content__header__element " id="header-id-1"><span>№ п/п</span><span class="glyphicon"></span></div>
        <div class="head_table_parameter-2 handbook-content__header__element " id="header-title-2"><span>Наименование должности</span><span class="glyphicon"></span></div>
    </div>
    <!-- ТЕЛО ТАБЛИЦЫ -->
    <div class="span_row_table " id="mainTable">
    </div>
</div>

<!-- Переключение страниц -->
<div class="handbook-content__footer">
    <div class="handbook-content__footer__pagination"></div>
</div>

<!-- МЕНЮ ДЕЙСТВИЙ -->
<div class="action-menu-background" id="actionMenuPosition">
    <div class="action-menu-buttons-container">
        <button id="position_edit-button" class="butt-one icon-edit-btn"></button>
        <button id="position_delete-button" class="butt-two icon-delete-btn"></button>
        <div class="bottom-triangle"></div>
    </div>
</div>


<!-- ДОБАВЛЕНИЕ -->
<div  id="model_word" class="modal fade in" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content no-padding">

            <div class="modal-header ">
                <div class="add-header-asmtp">
                    <span class="icon-add-current-btn"></span>
                    <span>Добавление должности</span>
                </div>
                <button class="close close-x" data-dismiss="modal">×</button>
            </div>

            <div class="handbook-position_modal-body">
                <span>Должность</span>
                <input type="text" placeholder="Введите должность" id="position_input">
            </div>
            <div class="handbook-position_modal-footer-editing">
                <button class="modal-footer-button-editing" id="add_position">Добавить</button>
                <button class="modal-footer-button-editing" id="closed_modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

<!-- РЕДАКТИРОВАНИЕ -->
<div  id="model_word-editing" class="modal fade in" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content no-padding">

            <div class="modal-header" style="background-color: #f07d02;">
                <div class="add-header-asmtp">
                    <span class="icon-edit-btn"></span>
                    <span>Редактирование должности</span>
                </div>
                <button class="close close-x" data-dismiss="modal">×</button>
            </div>

            <div class="handbook-position_modal-body-editing">
                <span>Должность</span>
                <input type="text" placeholder="" id="position_input-editing">
            </div>
            <div class="handbook-position_modal-footer-editing">
                <button class="modal-footer-button-editing" id="add_position-editing">Сохранить</button>
                <button class="modal-footer-button-editing" id="closed_modal-editing">Отмена</button>
            </div>
        </div>
    </div>
</div>


<!-- УДАЛЕНИЕ -->
<div  id="model_word-delete" class="modal fade in" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content no-padding">
            <div class="modal-header" style="background-color: #f07d02;">
                <div class="add-header-asmtp">
                    <span class="icon-delete-btn"></span>
                    <span>Удаление должности</span>
                </div>
                <button class="close close-x" data-dismiss="modal">×</button>
            </div>

            <div class="handbook-modal-delete-container-body">
                <div class="handbook-position_modal-body-delete"><span>Вы действительно хотите удалить должность:</span><span id="span_delete"></span></div>
            </div>
            <div class="handbook-position_modal-footer-editing">
                <button class="modal-footer-button-editing" id="add_position-delete">Удалить</button>
                <button class="modal-footer-button-editing" id="closed_modal-delete">Отмена</button>
            </div>
        </div>
    </div>
</div>
