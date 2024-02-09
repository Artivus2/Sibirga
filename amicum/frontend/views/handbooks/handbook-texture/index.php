<?php
use yii\web\View;
use frontend\assets\AppAsset;
use yii\helpers\Html;

$getSourceData = 'let textures = '.json_encode($texturesList).';';
$this->registerJs($getSourceData, View::POS_HEAD, 'handbook-texture-js');
$this->registerCssFile('/css/handbook-texture.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/jquery.cookie.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/handbook-texture.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->title = 'Справочник текстур 3D компонента';
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
    <button class="handbook-header__add-item" id="addTextureButton">
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
        <div class="handbook-content__header__element sort-field" data-field='iterator' style="width: 70px;" id="header_id_1">№ п/п <span class="glyphicon"></span></div>
        <div class="handbook-content__header__element sort-field" data-field='texture' id="header_texture_2">Наименование текстуры <span class="glyphicon"></span></div>
        <div class="handbook-content__header__element sort-field" data-field='color' id="header_title_3">Цвет<span class="glyphicon"></span></div>
        <div class="handbook-content__header__element sort-field" data-field='description' id="header_description_4" style="width: 450px;">Описание<span class="glyphicon"></span></div>
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
<!-- Модальное окно добавления текстуры -->
<div  id="addTextureModal" class="modal fade in" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content no-padding">

            <div class="modal-header ">
                <div class="add-header-asmtp">
                    <span class="icon-add-current-btn"></span>
                    <span>Добавление текстуры</span>
                </div>
                <button class="close close-x" data-dismiss="modal">×</button>
            </div>


            <div class="handbook-modal-add-edit-container-body">
                <span class="modal-field-title">Наименование текстуры</span>
                <input id="textureTitleInput" placeholder="Введите наименование">
                <span class="modal-field-title">Цвет</span>
                <input id="textureColorInput" placeholder="Введите цвет">
                <span class="modal-field-title">Описание</span>
                <textarea id="textureDescriptionInput" placeholder="Введите описание"></textarea>
            </div>
            <div class="handbook-modal-add-edit-container-footer">
                <button class="btn modal-footer-button" id="acceptAddTexture" data-dismiss="modal">Добавить</button>
                <button class="btn modal-footer-button" id="denyAddTexture" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования текстуры -->
<div  id="editTextureModal" class="modal fade in" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content no-padding">

            <div class="modal-header" style="background-color: #f07d02;">
                <div class="add-header-asmtp">
                    <span class="icon-edit-btn"></span>
                    <span>Редактирование текстуры</span>
                </div>
                <button class="close close-x" data-dismiss="modal">×</button>
            </div>

            <div class="handbook-modal-add-edit-container-body">
                <span class="modal-field-title">Наименование текстуры</span>
                <input id="textureEditTitleInput" placeholder="Введите наименование">
                <span class="modal-field-title">Цвет</span>
                <input id="textureEditColorInput" placeholder="Введите цвет">
                <span class="modal-field-title">Описание</span>
                <textarea id="textureEditDescriptionInput" placeholder="Введите описание"></textarea>
            </div>
            <div class="handbook-modal-add-edit-container-footer">
                <button class="btn modal-footer-button" id="acceptEditTexture" data-dismiss="modal">Сохранить</button>
                <button class="btn modal-footer-button" id="denyEditTexture" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно удаления текстуры -->
<div  id="deleteTextureModal" class="modal fade in" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content no-padding">
            <div class="modal-header" style="background-color: #f07d02;">
                <div class="add-header-asmtp">
                    <span class="icon-delete-btn"></span>
                    <span>Удаление текстуры</span>
                </div>
                <button class="close close-x" data-dismiss="modal">×</button>
            </div>

            <div class="handbook-modal-delete-container-body">
                <span>Вы действительно хотите удалить текстуру: </span>
                <span id="paste-delete-title">GreenEdgeMaterial</span>
                <span> ?</span>
            </div>
            <div class="handbook-modal-delete-container-footer">
                <button class="btn modal-footer-button" id="acceptDeleteTexture" data-dismiss="modal">Удалить</button>
                <button class="btn modal-footer-button" id="denyDeleteTexture" data-dismiss="modal">Отмена</button>
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