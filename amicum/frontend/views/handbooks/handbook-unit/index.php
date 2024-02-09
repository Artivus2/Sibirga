<?php
use yii\web\View;
use frontend\assets\AppAsset;
use yii\helpers\Html;
//use macgyer\yii2materializecss\widgets\grid\GridView;
//use macgyer\yii2materializecss\widgets\form\ActiveForm;
//use kartik\date\DatePicker;
$getSourceData = 'units = '.json_encode($unitsList).';';
$this->registerJs($getSourceData, View::POS_HEAD, 'unit-js');
$this->registerCssFile('/css/unit.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/unit.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->title = 'Справочник единиц измерения';
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
    <button class="handbook-header__add-item" id="addUnitButton">
        <div class="button-add-left">
            <span class="glyphicon glyphicon-plus"></span>
        </div>
        <div class="button-add-right"><span style="margin: auto;">Добавить</span></div>
    </button>
    <div class="handbook-header__search-container">
        <input class="handbook-header__input" id="searchInput" onfocus="this.focused=true;" onblur="this.focused=false;" placeholder="Введите поисковой запрос">
        <button class="clear-search-button" id="searchClear"></button>
        <button class="handbook-header__search-button" id="searchButton"></button>
    </div>
</div>
<!-- Таблица с данными -->
<div id="mainTableContainer" class="content-body" >
    <div class="handbook-content__header">
        <div class="handbook-content__header__element" style="width: 70px;" id="header-id-1">№ п/п
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element" id="header-title-2">Наименование единицы измерения
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element" id="header-short-3">Сокращение
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
<!-- Модальное окно добавления единицы измерения -->
<div  id="addUnitModal" class="modal fade in" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content no-padding">

            <div class="modal-header ">
                <div class="add-header-asmtp">
                    <span class="icon-add-current-btn"></span>
                    <span>Добавление единицы измерения</span>
                </div>
                <button class="close close-x" data-dismiss="modal">×</button>
            </div>


            <div class="handbook-modal-add-edit-container-body">
                <span class="modal-field-title">Наименование единицы измерения</span>
                <input id="unitTitleInput" placeholder="Введите наименование">
                <span class="modal-field-title">Сокращенное наименование</span>
                <input id="unitShortInput" placeholder="Введите сокращенное наименование">
            </div>
            <div class="handbook-modal-add-edit-container-footer">
                <button class="btn modal-footer-button" data-dismiss="modal" type="button" id="acceptAddUnit">Добавить</button>
                <button class="modal-footer-button" id="denyAddUnit" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>
<!-- Модальное окно редактирования единицы измерения -->
<div  id="editUnitModal" class="modal fade in" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content no-padding">

            <div class="modal-header" style="background-color: #f07d02;">
                <div class="add-header-asmtp">
                    <span class="icon-edit-btn"></span>
                    <span>Редактирование единицы измерения</span>
                </div>
                <button class="close close-x" data-dismiss="modal">×</button>
            </div>

            <div class="handbook-modal-add-edit-container-body">
                <span class="modal-field-title">Наименование единицы измерения</span>
                <input id="unitEditTitleInput" placeholder="Введите наименование">
                <span class="modal-field-title">Сокращенное наименование</span>
                <input id="unitEditShortInput" placeholder="Введите сокращенное наименование">
            </div>
            <div class="handbook-modal-add-edit-container-footer">
                <button class="modal-footer-button" id="acceptEditUnit" data-dismiss="modal" type="button" >Сохранить</button>
                <button class="modal-footer-button" id="denyEditUnit" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>
<!-- Модальное окно удаления единицы измерения -->
<div  id="deleteUnitModal" class="modal fade in" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content no-padding">
            <div class="modal-header" style="background-color: #f07d02;">
                <div class="add-header-asmtp">
                    <span class="icon-delete-btn"></span>
                    <span>Удаление единицы измерения</span>
                </div>
                <button class="close close-x" data-dismiss="modal">×</button>
            </div>

            <div class="handbook-modal-delete-container-body">
                <span>Вы действительно хотите удалить единицу измерения: </span>
                <span id="paste-delete-title">GreenEdgeMaterial</span>
                <span> ?</span>
            </div>
            <div class="handbook-modal-delete-container-footer">
                <button class="modal-footer-button" id="acceptDeleteUnit" data-dismiss="modal">Удалить</button>
                <button class="modal-footer-button" id="denyDeleteUnit" data-dismiss="modal">Отмена</button>
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

