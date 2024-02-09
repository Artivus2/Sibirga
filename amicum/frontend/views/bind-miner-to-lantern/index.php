<?php
use yii\web\View;
$this->registerCssFile('/css/bind_miner_to_lantern.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
//$this->registerCssFile('/css/basic-header.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->title = "Привязка лампы к сотруднику";
?>
    <!--Прелоадер-->
    <div id="preload" class="hidden">
        <div class="circle-container">
            <div id="circle_preload"></div>
            <h4 class="preload-title">Идёт загрузка</h4>
        </div>
    </div>
    <!--Прелоадер-->

    <!--    Модальное окно добавления привязки лампы-->
    <div class="modal fade" id="bindModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="icon-add-current-btn"></i>
                        <span>Привязка лампы сотруднику</span>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <p>Сотрудник</p>
                        <p>&laquo;<span class="employee-full-name"></span>&raquo;</p>
                    </div>

                    <div class="row padding-style">
                        <p class="sensor-select-title">Последняя постоянная лампа</p>
                        <div class="select-div regular-sensor-select">
                            <span class="empty-span">Выберите лампу</span>
                            <i class="caret"></i>
                            <div class="search">
                                <input class='selectSearch' type='search' placeholder='Поиск лампы...'>
                                <button class='clearSearchButton btn close'>&times;</button>
                                <button class='searchButton'></button>
                            </div>
                            <ul class='sensor-list hidden'></ul>
                        </div>
                    </div>
                    <!-- Выпадающий список резервных ламп                   -->
                    <div class="row padding-style">
                        <p class="sensor-select-title">Последняя резервная лампа</p>
                        <div class="select-div reserve-sensor-select">
                            <span class="empty-span">Выберите лампу</span>
                            <i class="caret"></i>
                            <div class="search">
                                <input class='selectSearch' type='search' placeholder='Поиск лампы...'>
                                <button class='clearSearchButton btn close'>&times;</button>
                                <button class='searchButton'></button>
                            </div>
                            <ul class='sensor-list hidden'></ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary bind-employee-button"
                                data-dismiss="modal">Сохранить</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окна добавления привязки лампы-->

    <!--    Предупредительное окно при удалении привязки луча у шахтера-->
    <div class="modal fade" tabindex="-1" role="dialog" id="unbindLampModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="glyphicon glyphicon-remove"></i>
                        Удаление привязки лампы
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <p>
                            Вы действительно хотите отвязать лампу <span class="lamp-title"></span>
                        </p>

                    </div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary delete-lamp-bind-button"
                                data-dismiss="modal">Да</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Нет</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец предупредительного окна при удалении привязки луча у шахтера-->

    <div class="container-for-lantern">
        <div class="search-container">
            <!-- Поле для ввода запроса -->
            <input id="staff_number" placeholder="Введите табельный номер или ФИО сотрудника..." type="search">
            <!-- Кнопка для очистки поиска -->
            <button id="clear_filter_button" class="clear_filter_btn" title="Очистить фильтр">&times;</button>
            <!-- Кнопка поиска       -->
            <div id="accept_filters">
                <i class="icon-filter-search-btn"></i>
            </div>
        </div>
        <div class="employees-table hidden">
            <div class="table-header">
                <div class="staff-number"><span>Табельный номер</span></div>
                <div class="full-name"><span>Ф. И. О.</span></div>
                <div class="position"><span>Должность</span></div>
                <div class="department"><span>Участок</span></div>
                <div class="company"><span>Предприятие</span></div>
                <div class="photo"><span>Фотография</span></div>
                <div class="lantern"><span>Последняя привязанная лампа</span></div>
                <div class="button-for-binding"><span>Привязать лампу</span></div>
                <div class="button-for-deleting-lamp"><span>Отвязать лампу</span></div>
            </div>
            <div class="table-body"></div>
        </div>
        <div class="table-footer hidden">
            <div class="handbook-content__footer">
                <div class="handbook-content__footer__pagination">
                    <button class="handbook-page-switch"><<</button>
                    <button class="handbook-page-switch"><</button>
                    <button class="handbook-page-switch numeric">1</button>
                    <button class="handbook-page-switch numeric">2</button>
                    <button class="handbook-page-switch numeric">3</button>
                    <button class="handbook-page-switch numeric">4</button>
                    <button class="handbook-page-switch numeric">5</button>
                    <button class="handbook-page-switch">></button>
                    <button class="handbook-page-switch">>></button>
                </div>
            </div>
        </div>
    </div>
<?php
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' =>
    View::POS_END]);
$this->registerJsFile('/js/bind_miner_to_lantern.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
?>
