<?php
use yii\web\View;
use yii\helpers\Html;
use macgyer\yii2materializecss\widgets\grid\GridView;
use macgyer\yii2materializecss\widgets\form\ActiveForm;
use kartik\date\DatePicker;
$getSourceData = 'parameters = '.json_encode($parametersList).';';
$this->registerJs($getSourceData, View::POS_HEAD, 'parameter-js');
$this->registerCssFile('/css/basic-table.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/parameter.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/js/parameter.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->title = 'Справочник параметров';
?>
<!--Прелодер-->
<div id="preload" class="hidden">
    <div class="circle-container">
        <div id="circle_preload"></div>
        <h4 class="preload-title">Идёт загрузка</h4>
    </div>
</div>
<!-- Шапка справочника -->
<div class="handbook-header">
    <!--    <button class="handbook-header__add-item" id="addTextureButton">Добавить текстуру</button>-->
    <button class="handbook-header__add-item" id="addParameterButton">
        <div class="button-add-left">
            <span class="glyphicon glyphicon-plus"></span>
        </div>
        <div class="button-add-right">Добавить</div>
    </button>
    <div class="handbook-header__search-container">
        <input class="handbook-header__input" id="searchInput" onfocus="this.focused=true;" onblur="this.focused=false;" placeholder="Введите поисковой запрос">
        <button class="clear-search-button" id="searchClear"></button>
        <button class="handbook-header__search-button" id="searchButton"></button>
    </div>
</div>
<!-- Таблица с данными -->
<div id="mainTableContainer" class="content-body">
    <div class="handbook-content__header">
        <div class="handbook-content__header__element" style="width: 70px;" id="header-id-1">№ п/п
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element" id="header-parameter_title-2">Наименование параметра
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element" id="header-unit_title-3">Единицы измерения
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element" id="header-kind_parameter_title-4"">Вид параметра
            <span class="glyphicon"></span>
    </div>
</div>
<div class="handbook-content__body" id="contentBody">
</div>
</div>
<div class="handbook-content__footer">
    <div class="handbook-content__footer__pagination">
    </div>
</div>
<!-- Модальное окно добавления параметра -->
<div class="modal fade in" tabindex="-1" role="dialog" id="addParameterModal">
    <div class="modal-dialog">
        <div class="modal-content no-padding">

            <div class="modal-header ">
                <div class="add-header-asmtp">
                    <span class="icon-add-current-btn"></span>
                    <span>Добавление параметра</span>
                </div>
                <button class="close close-x" data-dismiss="modal">×</button>
            </div>


            <div class="handbook-modal-add-edit-container-body">
                <span class="modal-field-title">Наименование параметра</span>
                <input id="parameterTitleInput" placeholder="Введите наименование">
                <span class="modal-field-title">Единицы измерения</span>
                <div class="title-dropdown-container" id="add-unit-dropdown">
                    <span class="dropdown-textcontent">Нажмите для выбора</span>
                    <span class="dropdown-icon caret"></span>
                </div>
                <span class="modal-field-title">Вид параметра</span>
                <div class="title-dropdown-container" id="add-kind-dropdown">
                    <span class="dropdown-textcontent">Нажмите для выбора</span>
                    <span class="dropdown-icon caret"></span>
                </div>
            </div>
            <div class="handbook-modal-add-edit-container-footer">
                <button class="modal-footer-button" id="acceptAddParameter">Добавить</button>
                <button class="modal-footer-button" id="denyAddParameter">Отмена</button>
            </div>
        </div>
    </div>
    <div class="dropdown-main-container" id="dropdown">
        <div class="dropdown-search-container">
            <input class="handbook-header__input" onfocus="this.focused=true;" onblur="this.focused=false;" placeholder="Введите поисковой запрос" id="dropdownInput">
            <button class="clear-search-button" id="clear-dropdown"></button>
            <button class="handbook-header__search-button" id="search-dropdown"></button>
        </div>
        <div class="dropdown-items-container" id="drop-container">
        </div>
    </div>
</div>
<!-- Модальное окно редактирования текстуры -->
<div class="modal fade in" tabindex="-1" role="dialog" id="editParameterModal">
    <div class="modal-dialog">
        <div class="modal-content no-padding">
            <div class="modal-header" style="background-color: #f07d02;">
                <div class="add-header-asmtp">
                    <span class="icon-edit-btn"></span>
                    <span>Редактирование параметра</span>
                </div>
                <button class="close close-x" data-dismiss="modal">×</button>
            </div>


            <div class="handbook-modal-add-edit-container-body">
                <span class="modal-field-title">Наименование параметра</span>
                <input id="parameterEditTitleInput" placeholder="Введите наименование">
                <span class="modal-field-title">Единицы измерения</span>
                <div class="title-dropdown-container" id="edit-unit-dropdown">
                    <span class="dropdown-textcontent">Нажмите для выбора</span>
                    <span class="dropdown-icon caret"></span>
                </div>
                <span class="modal-field-title">Вид параметра</span>
                <div class="title-dropdown-container" id="edit-kind-dropdown">
                    <span class="dropdown-textcontent">Нажмите для выбора</span>
                    <span class="dropdown-icon caret"></span>
                </div>
            </div>
            <div class="handbook-modal-add-edit-container-footer">
                <button class="modal-footer-button" id="acceptEditParameter">Сохранить</button>
                <button class="modal-footer-button" id="denyEditParameter">Отмена</button>
            </div>
        </div>
    </div>
    <div class="dropdown-main-container" id="dropdown2">
        <div class="dropdown-search-container">
            <input class="handbook-header__input" onfocus="this.focused=true;" onblur="this.focused=false;" placeholder="Введите поисковой запрос" id="dropdownInput2">
            <button class="clear-search-button" id="clear-dropdown2"></button>
            <button class="handbook-header__search-button" ></button>
        </div>
        <div class="dropdown-items-container" id="drop-container2">
        </div>
    </div>
</div>
<!-- Модальное окно удаления текстуры -->
<div class="modal fade in" tabindex="-1" role="dialog" id="deleteParameterModal">
    <div class="modal-dialog">
        <div class="modal-content no-padding">

            <div class="modal-header" style="background-color: #f07d02;">
                <div class="add-header-asmtp">
                    <span class="icon-delete-btn"></span>
                    <span>Удаление параметра</span>
                </div>
                <button class="close close-x" data-dismiss="modal">×</button>
            </div>
            <div class="handbook-modal-delete-container-body">
                <span>Вы действительно хотите удалить параметр: </span>
                <span id="paste-delete-title">GreenEdgeMaterial</span>
                <span> ?</span>
            </div>
            <div class="handbook-modal-delete-container-footer">
                <button class="modal-footer-button" id="acceptDeleteParameter">Удалить</button>
                <button class="modal-footer-button" id="denyDeleteParameter">Отмена</button>
            </div>
        </div>
    </div>
</div>
<!-- Меню действий -->
<div class="action-menu-background" id="blockActionMenu">
    <div class="action-menu-buttons-container">
        <button class="action-menu-button icon-edit-btn" id="action-button-edit"></button>
        <button class="action-menu-button icon-delete-btn" id="action-button-delete"></button>
    </div>
    <div class="caret"></div>
</div>
<!-- Выпадающий список-->
