<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;
use yii\web\View;
$this->registerCssFile('/css/role.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/jquery.cookie.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/role.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->title = 'Справочник ролей';
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
    <button class="handbook-header__add-item" id="addRoleButton" title="Добавить новую роль">
        <div class="button-add-left">
            <span class="glyphicon glyphicon-plus"></span>
        </div>
        <div class="button-add-right"><span style="margin: auto;">Добавить</span></div>
    </button>
    <div class="handbook-header__search-container">
        <input class="handbook-header__input" id="searchInput"" placeholder="Введите поисковой запрос">
        <button class="clear-search-button" id="searchClear"></button>
        <button class="handbook-header__search-button" id="searchButton"></button>
    </div>
</div>
<!-- Таблица с данными -->
<div id="mainTableContainer" class="content-body" >
    <div class="handbook-content__header">
        <div id="header-id-1" class="handbook-content__header__element"><span>№ п/п</span>
            <span class="glyphicon"></span>
        </div>
        <div id="header-title-2" class="handbook-content__header__element"><span>Наименование роли</span>
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
<!-- Модальное окно добавления роли -->
<div  id="addRoleModal" class="crud-modal modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content no-padding">

            <div class="modal-header ">
                <div class="add-header-role">
                    <span class="icon-add-current-btn"></span>
                    <span>Добавление роли</span>
                </div>
                <button class="close close-x" data-dismiss="modal">×</button>
            </div>

            <div class="handbook-modal-add-edit-container-body">
                <span class="modal-field-title">Наименование роли</span>
                <input id="roleTitleInput" placeholder="Введите наименование">
            </div>
            <div class="handbook-modal-add-edit-container-footer">
                <button class="btn modal-footer-button" data-dismiss="modal" type="button" id="addRole">Добавить</button>
                <button class="modal-footer-button cancel-button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>
<!-- Модальное окно редактирования роли -->
<div  id="editRoleModal" class="crud-modal modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content no-padding">

            <div class="modal-header" style="background-color: #f07d02;">
                <div class="add-header-role">
                    <span class="icon-edit-btn"></span>
                    <span>Редактирование роли</span>
                </div>
                <button class="close close-x" data-dismiss="modal">×</button>
            </div>

            <div class="handbook-modal-add-edit-container-body">
                <span class="modal-field-title">Наименование роли</span>
                <input id="roleEditTitleInput" placeholder="Введите наименование">
            </div>
            <div class="handbook-modal-add-edit-container-footer">
                <button class="modal-footer-button" id="editRole" data-dismiss="modal" type="button" >Сохранить</button>
                <button class="modal-footer-button cancel-button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>
<!-- Модальное окно удаления роли -->
<div  id="deleteRoleModal" class="crud-modal modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content no-padding">
            <div class="modal-header" style="background-color: #f07d02;">
                <div class="add-header-role">
                    <span class="icon-delete-btn"></span>
                    <span>Удаление роли</span>
                </div>
                <button class="close close-x" data-dismiss="modal">×</button>
            </div>

            <div class="handbook-modal-delete-container-body">
                <span>Вы действительно хотите удалить роль: </span>
                <span id="role-title"></span>
                <span> ?</span>
            </div>
            <div class="handbook-modal-delete-container-footer">
                <button class="modal-footer-button" id="deleteRole" data-dismiss="modal">Удалить</button>
                <button class="modal-footer-button cancel-button" data-dismiss="modal">Отмена</button>
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

