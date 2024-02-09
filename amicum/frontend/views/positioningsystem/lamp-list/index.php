<?php

use frontend\assets\AppAsset;
use yii\web\View;

$script = "let departments = " . json_encode($departments) . ", companies = " . json_encode($companies) . ";";
$this->registerJS($script, View::POS_HEAD, 'my-arrays');
$this->registerCssFile('/css/jquery.contextMenu.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/lamp-list.css', ['position' => View::POS_HEAD]);
$this->registerJsFile('/js/xlsx.core.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/Blob.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/FileSaver.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->title = "Список шахтёров и их ламп";
?>

<!--Прелоадер-->
<div id="preload" class="hidden">
    <div class="circle-container">
        <div id="circle_preload"></div>
        <h4 class="preload-title">Идёт загрузка</h4>
    </div>
</div>
<!--Прелоадер-->

<!--Модальное окно отображения истории привязанных ламп у сотрудника либо истории шахтеров, к которым была привязана лампа-->
<div class="history-modal modal fade" id="historyOfLampsOrWorkers">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="Закрыть окно">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title text-center">
                    <span>История <span class="detailed-title"></span></span>
                </h4>
            </div>
            <div class="modal-body">
                <h4 class="object-header-name text-center">
                    <span class="object-name"></span>, <span class="extra-info"></span>
                </h4>
                <div class="row padding-style">
                    <div class="table">
                        <div class="table-header"></div>
                        <div class="table-body"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--Конец модального окна отображения истории привязанных ламп у сотрудника либо истории шахтеров, к которым была привязана лампа-->

<div class="container-for-lantern">
    <div class="search-container">
        <div id="filters_label">
            <span class="filters-title">Фильтр: </span>
        </div>

        <div id="input_container">
            <!-- Кнопка экспорта -->
            <button class="content-header-button excel-export-btn" title="Экспортировать данные в Excel документ"
                    id="excelExport">Выгрузить в Excel
            </button>
            <div id="add_filter_button" title="Добавить фильтр">
                <i class="icon-filter-add-btn"></i>
            </div>
            <div id="filterElements" class="filter-elements">
                <div class="filter-block-item hidden" id="departmentElement">
                    <span>Участок</span>
                    <span class="span3">Выбрать</span>
                    <span class="caret span4"></span>
                    <span class="span5">&times;</span>
                    <div id="departmentSelect" class="hidden department-select select-item">
                        <ul class="department-list  select-list"></ul>
                    </div>
                </div>
                <div class="filter-block-item hidden" id="companyElement">
                    <span>Предприятие</span>
                    <span class="span3">Выбрать</span>
                    <span class="caret span4"></span>
                    <span class="span5">&times;</span>
                    <div id="companySelect" class="hidden company-select select-item">
                        <ul class="company-list select-list"></ul>
                    </div>
                </div>
            </div>
            <!-- Поле для ввода запроса -->
            <input id="searchInput" placeholder="Введите поисковый запрос" type="search">
            <!-- Кнопка для очистки поиска -->
            <button id="clear_filter_button" class="clear_filter_btn" title="Очистить фильтр">&times;</button>
            <!-- Кнопка поиска       -->
            <div id="accept_filters">
                <i class="icon-filter-search-btn"></i>
            </div>
        </div>

        <!--Критерии поиска-->
        <div class="search-options hidden">
            <div class="filter-item department-option" id="departmentOption"><span
                        class="filter-item-span">Участок</span></div>
            <div class="filter-item company-option" id="companyOption"><span
                        class="filter-item-span">Предприятие</span></div>
        </div>
    </div>
    <div class="employees-table">
        <div class="table-header">
            <div class="ordered-number"><span>№ п/п</span></div>
            <div class="sensor-title sort-field" data-field="sensor_title"><span>Лампа</span><span
                        class="glyphicon"></span></div>
            <div class="sensor-network-id sort-field" data-field="network_id">
                <span>Сетевой идентификатор лампы</span><span class="glyphicon"></span></div>
            <div class="full-name sort-field" data-field="full_name"><span>Ф. И. О.</span><span
                        class="glyphicon"></span></div>
            <div class="staff-number sort-field" data-field="staff_number"><span>Табельный номер</span><span
                        class="glyphicon"></span></div>
            <div class="position-title sort-field" data-field="position_title"><span>Должность</span><span
                        class="glyphicon"></span></div>
            <div class="department sort-field" data-field="department_title"><span>Участок</span><span
                        class="glyphicon"></span></div>
            <div class="company sort-field" data-field="position_title"><span>Предприятие</span><span
                        class="glyphicon"></span></div>
        </div>
        <div class="table-body"></div>

    </div>
    <div class="table-footer">
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
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()], 'position' =>
    View::POS_END]);
$this->registerJsFile('/js/jquery.contextMenu.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/lamp-list.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
?>
