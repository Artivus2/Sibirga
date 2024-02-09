<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;
use yii\web\View;
$this->registerCssFile('/css/equipment_sensor.css', ['depends' => [AppAsset::className()]]);
$this->title = "Привязка метки к оборудованию";
?>

    <!--Прелоадер-->
    <div id="preload" class="hidden">
        <div class="circle-container">
            <div id="circle_preload"></div>
            <h4 class="preload-title">Идёт загрузка</h4>
        </div>
    </div>
    <!--Прелоадер-->

    <!--    Модальное окно привязки метки оборудованию -->
    <div class="modal fade" tabindex="-1" role="dialog" id="setEquipmentSensor">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="settings-icon"></i>
                        <span>Привязка метки к &laquo;</span><span class="equipment-header"></span>&raquo;
                    </h4>
                </div>
                <div class="modal-body">

                    <div class="row padding-style">
                        <p>Метка</p>
                        <div class="select-div equipment-sensor-select" id="equipmentSensorSelect">
                            <span class="empty-span">Выберите метку</span>
                            <i class="caret"></i>
                            <div class="search">
                                <input class="selectSearch" id="selectSearch" type="search" placeholder="Поиск метки...">
                                <button id="clearSelectSearch" class="clearSearchButton btn close">&times;</button>
                                <button class="searchButton"></button>
                            </div>
                            <ul class="equipment-sensor-list hidden"></ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary set-sensor-button"
                                data-dismiss="modal">Привязать</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окна привязки метки оборудованию -->

    <!--    Предупредительное окно при удалении привязки луча у шахтера-->
    <div class="modal fade" tabindex="-1" role="dialog" id="unbindSensorModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="glyphicon glyphicon-remove"></i>
                        Удаление привязки метки
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <p>
                            Вы действительно хотите отвязать метку <span class="sensor-title"></span>
                        </p>

                    </div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary delete-sensor-bind-button"
                                data-dismiss="modal">Да</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Нет</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец предупредительного окна при удалении привязки луча у шахтера-->

    <div class="container-for-sensor">
        <div class="search-container">
            <!-- Поле для ввода запроса -->
            <input id="searchInput" placeholder="Введите поисковый запрос..." type="search">
            <!-- Кнопка для очистки поиска -->
            <button id="clear_filter_button" class="clear_filter_btn" title="Очистить фильтр">&times;</button>
            <!-- Кнопка поиска       -->
            <div id="accept_filters">
                <i class="icon-filter-search-btn"></i>
            </div>
        </div>
        <div class="equipment-table">
            <div class="table-header">
                <div class="order-number"><span>№ п/п</span></div>
                <div class="equipment-title"><span>Наименование</span></div>
                <div class="factory-number"><span>Заводской номер</span></div>
                <div class="inventory-number"><span>Инвентарный номер</span></div>
                <div class="place-title"><span>Место</span></div>
                <div class="department-title"><span>Участок</span></div>
                <div class="sensor"><span>Последняя привязанная метка</span></div>
                <div class="button-for-binding"><span>Привязать метку</span></div>
                <div class="button-for-deleting-sensor"><span>Отвязать метку</span></div>
            </div>
            <div class="table-body"></div>
        </div>
        <div class="table-footer">
            <div class="handbook-content__footer">
                <div class="handbook-content__footer__pagination"></div>
            </div>
        </div>
    </div>

<?php
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/equipment_sensor.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
?>