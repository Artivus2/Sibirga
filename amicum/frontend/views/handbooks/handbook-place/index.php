<?php
use yii\web\View;
use frontend\assets\AppAsset;
$getSourceData = 'let places = '.json_encode($model).', plasts = ' . json_encode($plasts) . ', objects = ' . json_encode($objects) . ', mines = ' . json_encode($mines) . ';';
$this->registerJs($getSourceData, View::POS_HEAD, 'place-js');
$this->registerCssFile('/css/handbook-place.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/place.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/anime.min.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->title = 'Справочник мест';
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
    <button class="handbook-header__add-item" id="addPlaceButton">
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
        <div class="handbook-content__header__element" style="width: 70px;" id="header-id-1">№ п/п
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element" id="header-title-2">Наименование места
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element" id="header-plast_title-3">Пласт
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element" id="header-mine_title-4">УГР(шахта/рудник)
            <span class="glyphicon"></span>
        </div>
        <div class="handbook-content__header__element" id="header-object_title-5" style="width: 450px;">Тип объекта
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
<!-- Модальное окно добавления места -->
<div  id="addPlaceModal" class="modal fade in" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content no-padding">

            <div class="modal-header ">
                <div class="add-header-asmtp">
                    <span class="icon-add-current-btn"></span>
                    <span>Добавление места</span>
                </div>
                <button class="close close-x" data-dismiss="modal">×</button>
            </div>


            <div class="handbook-modal-add-edit-container-body">
                <span class="modal-field-title">Наименование места</span>
                <input id="placeTitleInput" placeholder="Введите наименование">
                <span class="modal-field-title">Пласт</span>
                <div class="title-dropdown-container" id="add-plast-dropdown">
                    <span class="dropdown-textcontent no-toggle-dropdown">Нажмите для выбора</span>
                    <span class="dropdown-icon caret no-toggle-dropdown"></span>
                </div>
                <span class="modal-field-title">УГР(шахта/рудник)</span>
                <div class="title-dropdown-container" id="add-mine-dropdown">
                    <span class="dropdown-textcontent no-toggle-dropdown">Нажмите для выбора</span>
                    <span class="dropdown-icon caret no-toggle-dropdown"></span>
                </div>
                <span class="modal-field-title">Тип объекта</span>
                <div class="title-dropdown-container" id="add-object-dropdown">
                    <span class="dropdown-textcontent no-toggle-dropdown">Нажмите для выбора</span>
                    <span class="dropdown-icon caret no-toggle-dropdown"></span>
                </div>
            </div>
            <div class="handbook-modal-add-edit-container-footer">
                <button class="modal-footer-button" id="acceptAddPlace">Добавить</button>
                <button class="modal-footer-button" id="denyAddPlace">Отмена</button>
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
<div  id="editPlaceModal" class="modal fade in" tabindex="-1" role="dialog">
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
                <span class="modal-field-title">Наименование текстуры</span>
                <input id="placeEditTitleInput" placeholder="Введите наименование">
                <span class="modal-field-title">Пласт</span>
                <div class="title-dropdown-container" id="edit-plast-dropdown">
                    <span class="dropdown-textcontent no-toggle-dropdown">Нажмите для выбора</span>
                    <span class="dropdown-icon caret no-toggle-dropdown"></span>
                </div>
                <span class="modal-field-title">УГР(шахта/рудник)</span>
                <div class="title-dropdown-container" id="edit-mine-dropdown">
                    <span class="dropdown-textcontent no-toggle-dropdown">Нажмите для выбора</span>
                    <span class="dropdown-icon caret no-toggle-dropdown"></span>
                </div>
                <span class="modal-field-title">Тип объекта</span>
                <div class="title-dropdown-container" id="edit-object-dropdown">
                    <span class="dropdown-textcontent no-toggle-dropdown">Нажмите для выбора</span>
                    <span class="dropdown-icon caret no-toggle-dropdown"></span>
                </div>
            </div>
            <div class="handbook-modal-add-edit-container-footer">
                <button class="modal-footer-button" data-dismiss="modal" id="acceptEditPlace">Сохранить</button>
                <button class="modal-footer-button" data-dismiss="modal" id="denyEditPlace">Отмена</button>
            </div>
        </div>
    </div>
    <div class="dropdown-main-container" id="dropdown2">
        <div class="dropdown-search-container">
            <input class="handbook-header__input" onfocus="this.focused=true;" onblur="this.focused=false;" placeholder="Введите поисковой запрос" id="dropdownInput2">
            <button class="clear-search-button" id="clear-dropdown2"></button>
            <button class="handbook-header__search-button" id="search-dropdown2"></button>
        </div>
        <div class="dropdown-items-container" id="drop-container2">
        </div>
    </div>
</div>

<!-- Модальное окно удаления текстуры -->
<div  id="deletePlaceModal" class="modal fade in" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content no-padding">
            <div class="modal-header" style="background-color: #f07d02;">
                <div class="add-header-asmtp">
                    <span class="icon-delete-btn"></span>
                    <span>Удаление места</span>
                </div>
                <button class="close close-x" data-dismiss="modal">×</button>
            </div>


            <div class="handbook-modal-delete-container-body">
                <span>Вы действительно хотите удалить место: </span>
                <span id="paste-delete-title">GreenEdgeMaterial</span>
                <span> ?</span>
            </div>
            <div class="handbook-modal-delete-container-footer">
                <button class="modal-footer-button" data-dismiss="modal" id="acceptDeletePlace">Удалить</button>
                <button class="modal-footer-button" data-dismiss="modal" id="denyDeletePlace">Отмена</button>
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
