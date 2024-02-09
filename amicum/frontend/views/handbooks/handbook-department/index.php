<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;
use yii\web\View;
$getSourceData = 'departments = '.json_encode($model).';';
$this->registerJs($getSourceData, View::POS_HEAD, 'unit-js');
//$this->registerCssFile('/css/basic-table.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
//$this->registerCssFile('/css/basic-header.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/departments.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/js/jquery.cookie.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/departments.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->title = 'Справочник подразделений';
?>

<div id="preload" class="hidden">
    <div class="circle-container">
        <div id="circle_preload"></div>
        <h4 class="preload-title">Идёт загрузка</h4>
    </div>
</div>
<!-- Шапка справочника -->
<div class="handbook-header">
    <!--    <button class="handbook-header__add-item" id="addTextureButton">Добавить текстуру</button>-->
    <button class="handbook-header__add-item" id="addDepartmentButton">
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
<div class="content-body">
    <div class="handbook-content__header">
        <div class="handbook-content__header__element" style="width: 100px;" id="header-id-1">№ п/п
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element" id="header-title-2">Наименование подразделения
            <span class="glyphicon"></span>
        </div>
    </div>
    <div class="handbook-content__body" id="contentBody">
    </div>
</div>
<div class="handbook-content__footer">
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
<!-- Модальное окно добавления подразделения -->

<div  id="addDepartmentModal" class="modal fade in" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content no-padding">

            <div class="modal-header ">
                <div class="add-header-asmtp">
                    <span class="icon-add-current-btn"></span>
                    <span>Добавление подразделения</span>
                </div>
                <button class="close close-x" data-dismiss="modal">×</button>
            </div>


            <div class="handbook-modal-add-edit-container-body">
                <span class="modal-field-title">Наименование подразделения</span>
                <input id="departmentTitleInput" placeholder="Введите наименование">
            </div>
            <div class="handbook-modal-add-edit-container-footer">
                <button class="modal-footer-button" id="acceptAddDepartment">Добавить</button>
                <button class="modal-footer-button" id="denyAddDepartment">Отмена</button>
            </div>
        </div>
    </div>
</div>


<!-- Модальное окно редактирования подразделения -->

<div  id="editDepartmentModal" class="modal fade in" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content no-padding">

            <div class="modal-header" style="background-color: #f07d02;">
                <div class="add-header-asmtp">
                    <span class="icon-edit-btn"></span>
                    <span>Редактирование подразделения</span>
                </div>
                <button class="close close-x" data-dismiss="modal">×</button>
            </div>
            <div class="handbook-modal-add-edit-container-body">
                <span class="modal-field-title">Наименование подразделения</span>
                <input id="departmentEditTitleInput" placeholder="Введите наименование">
            </div>
            <div class="handbook-modal-add-edit-container-footer">
                <button class="modal-footer-button" id="acceptEditDepartment">Сохранить</button>
                <button class="modal-footer-button" id="denyEditDepartment">Отмена</button>
            </div>
        </div>
    </div>
</div>


<!-- Модальное окно удаления подразделения -->
<div  id="deleteDepartmentModal" class="modal fade in" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content no-padding">
            <div class="modal-header" style="background-color: #f07d02;">
                <div class="add-header-asmtp">
                    <span class="icon-delete-btn"></span>
                    <span>Удаление подразделения</span>
                </div>
                <button class="close close-x" data-dismiss="modal">×</button>
            </div>


            <div class="handbook-modal-delete-container-body">
                <span>Вы действительно хотите удалить подразделение: </span>
                <span id="paste-delete-title">GreenEdgeMaterial</span>
                <span> ?</span>
            </div>
            <div class="handbook-modal-delete-container-footer">
                <button class="modal-footer-button" id="acceptDeleteDepartment">Удалить</button>
                <button class="modal-footer-button" id="denyDeleteDepartment">Отмена</button>
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