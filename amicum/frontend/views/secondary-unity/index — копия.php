<?php

use yii\web\View;
use frontend\assets\AppAsset;

$this->title = "Тестовая страница Unity";
$getSourceData = 'let mine_id = ' . json_encode($mine_id) . '
place = ' . json_encode($place) . ';';
$this->registerJs($getSourceData, View::POS_HEAD, 'unity-js');
$this->registerCssFile('unity-for-test/TemplateData/style.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/jquery.contextMenu.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/jquery.datetimepicker.min.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/jquery-ui.min.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/unity.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/unity-for-test/TemplateData/UnityProgress.js', ['depends' => [AppAsset::className()], 'position' => View::POS_HEAD]);
$this->registerJsFile('/js/jquery.contextMenu.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/unity-for-test/Build/UnityLoader.js', ['depends' => [AppAsset::className()], 'position' => View::POS_HEAD]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/jquery-ui.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/jquery.datetimepicker.full.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/moment-with-locales.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/Chart.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/hammer.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/chartjs-plugin-zoom.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/unity-for-test.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/unityChat.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/add-edge-unity.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
?>
<!--Прелоадер-->
<div id="preload" class="hidden">
    <div class="circle-container">
        <div id="circle_preload"></div>
        <h4 class="preload-title">Идёт загрузка</h4>
    </div>
</div>
<!--Прелоадер-->

<!-- Контекстное меню для 3Д компонента -->
<div class="threedee-context-menu-container no-close-context-menu" id="tdContextMenu">
</div>
<!-- Модальное окно редактирования сопряжения -->
<div class="conjunction-modal-background" id="editConjunctionModal">
    <div class="conjunction-modal">
        <div class="conjunction-modal-head">
            Редактирование поворота
        </div>
        <div class="conjunction-modal-body">
            <div class="conjunction-modal-body-title-container">
                <span id="parseConjunctionTitle"></span>
            </div>
            <div class="conjunction-modal-body-title-container">
                <span>Введите координаты поворота:</span>
            </div>
            <div class="conjunction-modal-body-title-container">
                <span>X:</span><input type="text" id="conjX" value="Загрузка">
            </div>
            <div class="conjunction-modal-body-title-container">
                <span>Y:</span><input type="text" id="conjY" value="Загрузка">
            </div>
            <div class="conjunction-modal-body-title-container">
                <span>Z:</span><input type="text" id="conjZ" value="Загрузка">
            </div>
        </div>
        <div class="conjunction-modal-footer">
            <button class="btn-primary-amicum" id="saveConjunctionCoord">Сохранить</button>
            <button class="btn-secondary-amicum" id="denyConjunctionCoord">Отмена</button>
        </div>
    </div>
</div>
<!-- Правое меню -->
<div class="right-side-menu" id="rightSideMenu">
    <div class="right-buttons">
        <button id="toggleRightContent"><span class="caret"></span><span>Сведения об объектах</span></button>
        <ul class="nav nav-tabs right-tabs" id="tabButtons">
            <li style="width: calc(50% / 3); text-align: center;" class="active" id="peopleListTab"><a data-toggle="tab"
                                                                                                       href="#peoples">Люди</a>
            </li>
            </li>
            <li style="width: 20%; text-align: center;" id="objectsListTab"><a data-toggle="tab" href="#objectsAC">Объекты
                    АС</a></li>
            <li style="width: 30%; text-align: center;" id="equipmentListTab"><a data-toggle="tab" href="#equipment">Оборудование</a>
            </li>
            <li style="width: calc(50% / 3); text-align: center;" id="placeListTab"><a data-toggle="tab" href="#places">Места</a>
            </li>
            <li style="width: calc(50% / 3); text-align: center;" id="shiftListTab"><a data-toggle="tab"
                                                                                       href="#shift">Смена</a>
        </ul>
        <div class="out-search" id="outSearch">
            <div class="search-out">
                <input type="search" id="search" class="WebGLKeyboardInput" name="WebGLKeyboardInput"
                       onfocus="this.focused=true;" onblur="this.focused=false;" placeholder="Введите поисковой запрос">
                <button class="clear-search-button" id="searchClear"></button>
                <button class="search-button" id="searchButton"></button>
            </div>
        </div>
        <div class="tab-content right-content" id="rightContent">
            <div id="shift" class="tab-pane fade">
            </div>
            <div id="objectsAC" class="tab-pane fade">
            </div>
            <div id="equipment" class="tab-pane fade">
            </div>
            <div id="places" class="tab-pane fade">
                Загрузка списка мест
            </div>
            <div id="peoples" class="tab-pane fade in active">
                <div style="position: sticky; top: 0;">
                    <button style="margin-bottom: 5px;" id="send-group-message">Открыть групповой чат</button>
                </div>
                <div><span style="margin-left: 10px;">Выбрать всех</span><input
                            style="float: right; margin-right: 10px;" id="broadcast-all" type="checkbox"></div>
                <div class="peopleContainer" id="peopleContainer"></div>
            </div>
        </div>
    </div>
</div>
<!-- Добавление узла -->
<div class="modal fade in add-node-modal">
    <div class="modal-dialog add-node-dialog" role="document" id="nodeModal">
        <div class="modal-content">
            <div class="modal-header add-node-header">
                <div class="add-node-header-title">
                    <span>Добавление объекта АС</span>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" id="closeXNode">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body add-node-body">
                <div class="add-node-body-left">
                    <div class="add-node-body-title">
                        <span>Общие параметры</span>
                    </div>
                    <div class="body-left-inner">
                        <div class="add-node-body-titles">Наименование</div>
                        <div class="dropdown-add-node-title" id="nodeDropdown">
                            <div class="dropdown-inner-node" id="nodeDrpdParams">
                                <div class="dropdown-title">Выберите узел</div>
                                <div class="dropdown-caret">
                                    <div class="caret"></div>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-list" id="nodeDropdownList">
                        </div>
                        <div class="add-node-body-titles">Координата X</div>
                        <div class="add-node-body-input" id="xCoord">
                            <input type="text" onfocus="noLetters(this.id);" id="xCoordinateAddNode">
                        </div>
                        <div class="add-node-body-titles">Координата Y</div>
                        <div class="add-node-body-input" id="yCoord">
                            <input type="text" onfocus="noLetters(this.id);" id="yCoordinateAddNode">
                        </div>
                        <div class="add-node-body-titles">Координата Z</div>
                        <div class="add-node-body-input" id="zCoord">
                            <input type="text" onfocus="noLetters(this.id);" id="zCoordinateAddNode">
                        </div>
                        <div class="add-node-body-titles">Местоположение</div>
                        <div class="add-node-body-input" id="nodePlaceTitle">
                            <input type="text" disabled>
                        </div>
                    </div>
                </div>
                <div class="add-node-body-right">
                    <div class="add-node-photo-container" id="addNodePhoto">
                    </div>
                </div>
            </div>
            <div class="modal-footer add-node-footer" style="height: 55px;">
                <button type="button" class="btn-primary-amicum" id="createNode">Установить</button>
                <button type="button" class="btn-secondary-amicum" data-dismiss="modal" id="denyNode">Отмена</button>
            </div>
        </div>
    </div>
</div>
<!-- Просмотр информации об узле -->
<div class="modal fade in info-node-modal" id="printNodeInfoFlex">
    <div class="modal-dialog add-node-dialog" role="document" id="nodeModalInfo">
        <div class="modal-content">
            <div class="modal-header add-node-header">
                <div class="add-node-header-title">
                    <span>Информация об объекте АС <span class="sensor-name"></span></span>

                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" id="closeXNodeInfo"
                        style="display: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body add-node-body node-body-block">
                <div class="node-info-tab">
                    <ul class="nav nav-tabs modal-tabs" id="tabAddEdge">
                        <li class="active" style="width: 50%; text-align: center;" id="nodeInfoTab">
                            <a data-toggle="tab" href="#nodeInfoTabs">Главная информация</a>
                        </li>
                        <li style="width: 50%; text-align: center;" id="nodeParameterTab">
                            <a data-toggle="tab" href="#nodeParameterTabs">Все параметры</a>
                        </li>
                    </ul>
                </div>
                <div class="node-body-flex tab-content">
                    <div id="nodeInfoTabs" class="tab-pane fade in active">
                        <div style="display: flex; width: 100%;">
                            <div class="add-node-body-left">
                                <div class="body-left-inner">
                                    <div class="add-node-body-titles">Наименование</div>
                                    <div class="add-node-body-input" id="titleNodeInfo">
                                        <input type="text" disabled>
                                    </div>
                                    <div class="add-node-body-titles">Координата X</div>
                                    <div class="add-node-body-input" id="xCoordInfo">
                                        <input type="text" disabled>
                                    </div>
                                    <div class="add-node-body-titles">Координата Y</div>
                                    <div class="add-node-body-input" id="yCoordInfo">
                                        <input type="text" disabled>
                                    </div>
                                    <div class="add-node-body-titles">Координата Z</div>
                                    <div class="add-node-body-input" id="zCoordInfo">
                                        <input type="text" disabled>
                                    </div>
                                    <div class="add-node-body-titles">Местоположение</div>
                                    <div class="add-node-body-input" id="nodePlaceTitleInfo">
                                        <input type="text" disabled>
                                    </div>
                                </div>
                            </div>
                            <div class="add-node-body-right">
                                <div class="add-node-photo-container" id="nodeInfoPhoto">
                                    Загрузка изображения
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="nodeParameterTabs" class="tab-pane fade" style="width: 100%;">
                        <div class="head-row">
                            <div class="node-parameter-row header-row" id="node_parameter_title">
                                <span class="node-parameter-title">Наименование параметра</span>
                                <span class="node-parameter-handbook-value">Справочный</span>
                                <span class="node-parameter-measured-value">Измеряемый</span>
                                <span class="node-parameter-calculated-value">Вычисляемый</span>
                                <span class="node-parameter-unit">Единица измерения</span>
                            </div>
                        </div>
                        <div class="parameters-body"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer add-node-footer">
                <button type="button" class="delete-object-button" id="deleteNodeButton" style="margin-left: auto;">
                    Удалить
                </button>
                <button type="button" class="move-sensor" id="moveSensorButton"><span
                            class="glyphicon glyphicon-move"></span><span>Переместить</span></button>
                <button type="button" class="btn btn-secondary btn-secondary-amicum" data-dismiss="modal"
                        id="denyNodeInfo">Закрыть
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Модальное окно добавления выработки (выработка - выработка) -->
<div class="modal fade in add-edge-modal" id="addEdgeModal">
    <div class="modal-dialog add-edge-dialog" role="document" id="edgeModal">
        <div class="modal-content">
            <div class="modal-header add-node-header">
                <div class="add-node-header-title">
                    <span>Добавление выработки</span>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" id="closeXEdge">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body add-edge-body">
                <!-- Переключение вкладок -->
                <ul class="nav nav-tabs right-tabs" id="tabAddEdge">
                    <li class="active" style="width: 50%; text-align: center;" id="mainParametersTab"><a
                                data-toggle="tab" href="#mainParameters">Общие параметры</a></li>
                    <li style="width: 50%; text-align: center;" id="conjunctionsTab"><a data-toggle="tab"
                                                                                        href="#conjunctions">Повороты</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <!-- Контейнер с общими параметрами -->
                    <div id="mainParameters" class="tab-pane fade in active">
                        <div class="main-parameters-flex">
                            <div class="add-edge-body-left">
                                <div class="edge-left-inner">
                                    <div class="body-left-left-inner">
                                        <div class="add-node-body-titles">Наименование</div>
                                        <div class="add-node-body-input" id="titleEdge"
                                             style="border: 1px solid darkgrey; display: flex;">
                                            <input type="text" id="title-add-edge-textbox"
                                                   placeholder="Введите наименование" style="border: none;"
                                                   onfocus="inputDropdownDynamic2(this.id)"
                                                   class="no-close-place-list">
                                            <div class="caret" style="margin: auto 10px auto auto;"></div>
                                        </div>
                                        <div class="add-node-body-titles">Координата X1</div>
                                        <div class="add-node-body-input" id="x1Coord">
                                            <input type="text" onfocus="noLetters(this.id);" id="xCoordinateAddEdge">
                                        </div>
                                        <div class="add-node-body-titles">Координата Y1</div>
                                        <div class="add-node-body-input" id="y1Coord">
                                            <input type="text" onfocus="noLetters(this.id);" id="yCoordinateAddEdge">
                                        </div>
                                        <div class="add-node-body-titles">Координата Z1</div>
                                        <div class="add-node-body-input" id="z1Coord">
                                            <input type="text" onfocus="noLetters(this.id);" id="zCoordinateAddEdge">
                                        </div>
                                        <div class="add-node-body-titles">Высота, м</div>
                                        <div class="add-node-body-input" id="heightEdge">
                                            <input type="text" onfocus="noLetters(this.id);" id="heightInputAddNode">
                                        </div>
                                        <div class="add-node-body-titles">Цвет горной выработки</div>
                                        <div class="dropdown-add-node-title" id="edgeDropdown">
                                            <div class="dropdown-inner-node" id="edgeDrpdParams">
                                                <div class="dropdown-title">Выберите цвет</div>
                                                <div class="dropdown-caret">
                                                    <div class="caret"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="dropdown-list" id="edgeDropdownList">
                                            <div class="dropdown-list-element" id="color-0">
                                                <span>BlueEdgeMaterial</span></div>
                                            <div class="dropdown-list-element" id="color-1">
                                                <span>CapitalEdgeMaterial</span></div>
                                            <div class="dropdown-list-element" id="color-2">
                                                <span>GrayEdgeMaterial</span></div>
                                            <div class="dropdown-list-element" id="color-3">
                                                <span>GreenEdgeMaterial</span></div>
                                            <div class="dropdown-list-element" id="color-4">
                                                <span>PinkEdgeMaterial</span></div>
                                            <div class="dropdown-list-element" id="color-5"><span>RedEdgeMaterial</span>
                                            </div>
                                        </div>
                                        <div class="add-node-body-titles">Тип горной выработки</div>
                                        <div class="dropdown-add-node-title" id="edgeTypeDropdown">
                                            <div class="dropdown-inner-node" id="edgeTypeDrpdParams">
                                                <div class="dropdown-title">Выберите тип горной выработки</div>
                                                <div class="dropdown-caret">
                                                    <div class="caret"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="dropdown-list" id="edgeTypeDropdownList">
                                        </div>
                                        <div class="add-node-body-titles">Название пласта</div>
                                        <div class="dropdown-add-node-title" id="edgePlastTitleDropdown">
                                            <div class="dropdown-inner-node" id="edgePlastTitleDrpdParams">
                                                <div class="dropdown-title">Выберите название пласта</div>
                                                <div class="dropdown-caret">
                                                    <div class="caret"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="dropdown-list" id="edgePlastTitleDropdownList">
                                        </div>
                                        <div class="add-node-body-titles">Уставка CO</div>
                                        <div class="add-node-body-input" id="INFOAddCo">
                                            <input type="text" onfocus="noLetters(this.id);" id="coAddEdge"
                                                   style="border: 1px solid darkgrey;">
                                        </div>
                                    </div>
                                    <div class="body-left-right-inner">
                                        <div class="add-node-body-titles">Протяженность, м</div>
                                        <div class="add-node-body-input" id="lengthEdge">
                                            <input type="text" onfocus="noLetters(this.id);" id="lengthInputAddNode">
                                        </div>
                                        <div class="add-node-body-titles">Координата X2</div>
                                        <div class="add-node-body-input" id="x2Coord">
                                            <input type="text" onfocus="noLetters(this.id);" id="x2CoordinateAddEdge">
                                        </div>
                                        <div class="add-node-body-titles">Координата Y2</div>
                                        <div class="add-node-body-input" id="y2Coord">
                                            <input type="text" onfocus="noLetters(this.id);" id="y2CoordinateAddEdge">
                                        </div>
                                        <div class="add-node-body-titles">Координата Z2</div>
                                        <div class="add-node-body-input" id="z2Coord">
                                            <input type="text" onfocus="noLetters(this.id);" id="z2CoordinateAddEdge">
                                        </div>
                                        <div class="add-node-body-titles">Сечение, м<sup>2</sup></div>
                                        <div class="add-node-body-input" id="sEdge">
                                            <input type="text" onfocus="noLetters(this.id);" id="sectionInputAddNode">
                                        </div>
                                        <div class="add-node-body-titles">Ширина, м</div>
                                        <div class="add-node-body-input" id="widthEdge">
                                            <input type="text" onfocus="noLetters(this.id);" id="widthInputAddNode">
                                        </div>
                                        <div class="add-node-body-input" id="dangerEdge"
                                             style="display: flex; margin-top: 35px;">
                                            <div class="border-for-checkbox">
                                                <input type="checkbox" id="dangerCheckbox" checked="false"
                                                       style="width: auto; margin: auto 10px"><span>Запрещенная зона</span>
                                            </div>
                                            <div class="border-for-checkbox" style="margin-left: 10%;">
                                                <input type="checkbox" id="isConvCheckbox" checked="false"
                                                       style="width: auto; margin: auto 10px"
                                                       onchange="toggleConveyorTagVisibility('isConvCheckbox', 'tag-add-block', 'tagAddEdge');"><span>Есть конвейер</span>
                                            </div>
                                        </div>
                                        <div class="tag-input hidden" id="tag-add-block">
                                            <div class="add-node-body-titles">Тег конвейера</div>
                                            <div class="add-node-body-input" id="INFOAddTag">
                                                <input type="text" val="Загрузка данных" id="tagAddEdge"
                                                       style="border: 1px solid darkgrey;" disabled="true">
                                            </div>
                                        </div>
                                        <div class="add-node-body-titles" style="margin-top: 10px;">Название типа
                                            места
                                        </div>
                                        <div class="dropdown-add-node-title" id="edgePlaceTypeDropdown">
                                            <div class="dropdown-inner-node" id="edgePlaceTypeDrpdParams">
                                                <div class="dropdown-title">Выберите название типа места</div>
                                                <div class="dropdown-caret">
                                                    <div class="caret"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="dropdown-list" id="edgePlaceTypeDropdownList">
                                        </div>
                                        <div class="add-node-body-titles">Уставка CH4</div>
                                        <div class="add-node-body-input" id="INFOAddCh4">
                                            <input type="text" onfocus="noLetters(this.id);" id="ch4AddEdge"
                                                   style="border: 1px solid darkgrey;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="add-node-body-right">
                                <div class="add-node-photo-container">
                                    <img src="../img/picEdge.png">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="conjunctions" class="tab-pane fade">
                        <div class="conjunctions-flex">
                            <div class="conjunctions-left">
                                <div class="add-node-body-titles">Поворот №1</div>
                                <div class="add-node-body-titles">Наименование</div>
                                <div class="add-node-body-input" id="dangerEdge">
                                    <input type="text">
                                </div>
                            </div>
                            <div class="conjunctions-right">
                                <div class="add-node-body-titles">Поворот №2</div>
                                <div class="add-node-body-titles">Наименование</div>
                                <div class="add-node-body-input" id="dangerEdge">
                                    <input type="text">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer add-node-footer" style="height: 55px;">
                <button type="button" class="btn-primary-amicum" id="createEdge">Добавить</button>
                <button type="button" class="btn-secondary-amicum" data-dismiss="modal" id="denyEdge">Отмена</button>
            </div>
        </div>
    </div>
</div>

<!--    Модальное окно отображения графика значений параметра-->
<div class="modal fade" role="dialog" id="objectParameterChart">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <!-- Окно выбора значений выпадающего списка -->
            <div class="filter-inner-container" id="filterInnerContainer" style="display: none;"></div>
            <div class="modal-header">
                <h4 class="modal-title">
                    График изменения значений концентрации <span class="gas-title"></span>
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="Закрыть окно">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="padding-style">
                    <div class="choose-place-block">
                        Место:
                        <span id="select" onclick="toggleInnerPlaceFilter(this)" class="select-filter-type span1">Выбрать место</span>
                        <span class="caret span4"></span>
                    </div>
                    <div class="choose-date-block">
                        <div class="choose-date-block-info">
                            <span>Анализируемое время с</span>
                            <div class="choose-date-block-time">
                                <div id="dateStart" class="trapezoid">
                                    <img src="/img/calendar.png" alt="calendar">
                                    <span class="dateStart select-filter-type date-span"></span>
                                    <img src="/img/dataTime.png" alt="dataTime">
                                    <span class="dateStart select-filter-type date-span"></span>
                                    <!--                                    <input type="datetime-local" >-->
                                </div>
                                <span>по</span>
                                <div id="dateEnd" class="trapezoid">
                                    <img src="/img/calendar.png" alt="calendar">
                                    <span class="dateEnd select-filter-type date-span"></span>
                                    <img src="/img/dataTime.png" alt="dataTime">
                                    <span class="dateEnd select-filter-type date-span"></span>
                                    <!--                                    <input type="datetime-local" >-->
                                </div>
                                <button id="drawChartBtn"><img src="/img/magnifier-blue.png" alt="magnifier"></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="chartContainer" class="padding-style chart-container">
                    <div id="unit"></div>
                    <div id="chart_div">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--Конец модального окна отображения графика значений параметра-->

<!--    Модальное окно выбора периода времени, за который нужно отобразить маршрут передвижений-->
<div class="modal fade" role="dialog" id="workerRouteModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    История передвижения работника <span class="miner-full-name"></span>
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="Закрыть окно">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="padding-style">
                    <div class="choose-date-block-time">
                        <span>с</span>
                        <div id="routeDateStart" class="trapezoid" title="Выбериту дату начала маршрута">
                            <img src="/img/calendar.png" alt="calendar">
                            <span class="dateOption select-filter-type date-span"></span>
                            <img src="/img/dataTime.png" alt="dataTime">
                            <span class="timeOption select-filter-type date-span"></span>
                            <!--                                    <input type="datetime-local" >-->
                        </div>
                        <span>по</span>
                        <div id="routeDateEnd" class="trapezoid" title="Выберите дату окончания маршрута">
                            <img src="/img/calendar.png" alt="calendar">
                            <span class="dateOption select-filter-type date-span"></span>
                            <img src="/img/dataTime.png" alt="dataTime">
                            <span class="timeOption select-filter-type date-span"></span>
                            <!--                                    <input type="datetime-local" >-->
                        </div>
                        <button id="activatePlayerBtn" data-dismiss="modal" title="Построить маршрут"><img
                                    src="/img/magnifier-blue.png" alt="magnifier"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--Конец модального окна выбора периода времени, за который нужно отобразить маршрут передвижений-->


<!-- Контейнер с Unity -->
<div class="webgl-content" id="webglContent">
    <div id="gameContainer"></div>
</div>

<!-- Нижняя таблица -->
<div class="center-table" id="centerTable">
    <div class="table-headers">
        <div class="header-item" id="statusHeader"><span>Статус</br>(загазованность) (sos)</span></div>
        <div class="header-item" id="tabelHeader"><span>Таб. №</span></div>
        <div class="header-item" id="fioHeader"><span>ФИО</span></div>
        <div class="header-item" id="jobHeader"><span>Должность</span></div>
        <div class="header-item" id="departmentHeader"><span>Подразделение</span></div>
        <div class="header-item" id="locationHeader"><span>Местоположение</span></div>
        <div class="header-item" id="ch4Header"><span>CH4, %</span></div>
        <div class="header-item" id="coHeader"><span>CO, мг/м<sup>3</sup></span></div>
    </div>
    <div class="table-content" id="tableContent"></div>
</div>
<!---->
<!--<div class="unity-interface-buttons" id="UIB">-->
<!--    <div class="the-most-left-button">-->
<!--        <button id="addObject" title="Добавить новый объект">Добавить</button>-->
<!--    </div>-->
<!--    <div class="interface-buttons-left">-->
<!--        <button class="view-button right-button" id="viewLeft" title="Переключить на вид спереди"></button>-->
<!--        <button class="view-button right-button" id="viewTop" title="Переключить на вид сверху"></button>-->
<!--        <button class="view-button right-button" id="viewRight" title="Переключить на вид сбоку"></button>-->
<!--    </div>-->
<!--    <div class="interface-buttons-right" id="unityControlButton">-->
<!---->
<!--        <button class="right-button" id="tabelButton" title="Отобразить/скрыть табельные номера" style="display: none;">-->
<!--            <span>Таб. №</span></button>-->
<!--        <button style="display: none;" class="right-button" id="gazButton"-->
<!--                title="Отобразить/скрыть значения уровня газа"><span>CO CH4</span></button>-->
<!--        <button class="right-button" id="colorOnButton" title="Включить/выключить цвета выработок"></button>-->
<!--        <button class="right-button" style="display: none;" id="warningButton"-->
<!--                title="Отобразить/скрыть иконки 'Уровень опасности'"></button>-->
<!--        <button class="right-button" style="display: none;" id="sosButton"-->
<!--                title="Отобразить/скрыть иконки 'Сигнал SOS'"></button>-->
<!--        <button class="right-button glyphicon glyphicon-comment" id="toggleNodesTitles"-->
<!--                title="Скрыть подробную информацию"></button>-->
<!--        <button class="right-button" id="viewIconButton" title="Отобразить/скрыть метки работников"></button>-->
<!--        <button class="right-button" id="controlLocationButton"-->
<!--                title="Отобразить только работников с просроченной локацией"></button>-->
<!--        <button class="right-button" id="layersUnity" title="Переключение слоев"></button>-->
<!--        <button class="right-button" id="rotate" title="Разворот камеры на 180 градусов"></button>-->
<!--        <button class="right-button" id="zoomIncreaseButton" title="Приближение камеры"></button>-->
<!--        <button class="right-button" id="zoomDecreaseButton" title="Отдаление камеры"></button>-->
<!--        <button class="right-button" id="zoomIncreaseButtonX1-2"></button>-->
<!--        <button class="right-button" id="zoomDecreaseButtonX1-2"></button>-->
<!--        <button class="right-button" id="showConjunctions" title="Отображение целостности мест"></button>-->
<!--        <button class="right-button" id="showDeadEndEdges" title="Отображение тупиковых выработок"></button>-->
<!--        <button class="right-button" id="joinEdges" title="Соединить выработки"></button>-->
<!---->
<!---->
<!--    </div>-->
<!--    <div class="block-with-layers" id="block-with-layers">-->
<!--        <label for="layer-nodes">-->
<!--            <input type="checkbox" checked class="layer-nodes" id="layer-nodes"> Датчики-->
<!--        </label>-->
<!--        <label for="layer-conjs">-->
<!--            <input type="checkbox" checked class="layer-conjs" id="layer-conjs"> Сопряжения-->
<!--        </label>-->
<!--        <label for="layer-workers">-->
<!--            <input type="checkbox" checked class="layer-workers" id="layer-workers"> Работники-->
<!--        </label>-->
<!--    </div>-->
<!---->
<!--</div>-->


<!-- Нижняя кнопка -->
<div class="center-menu" id="mainMenu">
    <div class="toggle-table" id="toggleTable"><span class="glyphicon glyphicon-resize-full"></span></div>
    <div class="center-button" id="centerMenu"><span>Показать весь журнал</span></div>

    <div class="number-of-workers" id="clickToToggleWorkers">
        <span class="child-element">Всего людей в шахте:</span>
        <span id="numberWorkersHere">-</span>
    </div>
    <!-- Блок со списком работников -->
    <div class="types-of-workers">
        <div class="ground-workers child-element"><span class="child-element">В шахте: </span><span
                    class="child-element" id="numberWorkersUnderground">-</span></div>
        <div class="underground-workers child-element"><span class="child-element">На поверхности: </span><span
                    class="child-element" id="numberWorkersOnGround">-</span></div>
    </div>
    <div class="alarm-btn-container">
        <button id="sendAlarm" title="Послать сигнал об опасности"></button>
    </div>
</div>
<div id="fieldId"></div>

<!-- Чятик -->
<div class="chat" id="chat">
    <!--draggable-window-->
    <div class="info-panel" id="chatHeader">
        <div class="chat-FIO">
            <span></span>
        </div>
        <div class="chat-close-button">
            <button id="chatCloseButton">&times;</button>
        </div>
    </div>
    <div class="chat-window">
    </div>
    <div class="input-panel">
        <input type="text" id="chatInput" placeholder="Введите текст сообщения" maxlength="40"
               onfocus="this.focused=true;" onblur="this.focused=false;">
        <button id="sendMessageButton"></button>
    </div>
</div>

<!--<div class="addButtonsContainer">-->
<!--    <div id="slideUp">-->
<!--        <button id="addDifferentEdges">Выработку</button>-->
<!--        <button id="addSomething">Объект АС</button>-->
<!--        <button id="addEquipment">Оборудование</button>-->
<!--    </div>-->
<!--</div>-->

<!-- Отображение информации о выработке -->
<div class="modal fade in add-edge-modal" id="showInfoEdge" style="z-index: 100;">
    <div class="modal-dialog add-edge-dialog" role="document" id="edgeModal">
        <div class="modal-content">
            <div class="modal-header add-node-header">
                <div class="add-node-header-title">
                    <span>Информация о выработке</span>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" id="closeXEdge"
                        style="display: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body add-edge-body">
                <!-- Переключение вкладок -->
                <ul class="nav nav-tabs right-tabs" id="tabAddEdge">
                    <li class="active" style="width: 50%; text-align: center;" id="INFOmainParametersTab"><a
                                data-toggle="tab" href="#INFOmainParameters">Общие параметры</a></li>
                    <li style="width: 50%; text-align: center;" id="INFOconjunctionsTab"><a data-toggle="tab"
                                                                                            href="#INFOconjunctions">Повороты</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <!-- Контейнер с общими параметрами -->
                    <div id="INFOmainParameters" class="tab-pane fade in active">
                        <div class="main-parameters-flex">
                            <div class="add-edge-body-left">
                                <div class="edge-left-inner">
                                    <div class="body-left-left-inner">
                                        <div class="add-node-body-titles">Наименование</div>
                                        <div class="add-node-body-input no-close-place-list" id="INFOtitleEdge"
                                             style="display: flex; border: 1px solid darkgrey;">
                                            <input id="drop-id-1" type="text" val="Загрузка данных"
                                                   style="border: none;" onfocus="inputDropdownDynamic(this.id)"
                                                   class="no-close-place-list">
                                            <div class="caret" style="margin: auto 10px auto auto;"></div>
                                        </div>
                                        <div class="add-node-body-titles">Координата X1</div>
                                        <div class="add-node-body-input" id="INFOx1Coord">
                                            <input type="text" val="Загрузка данных" id="x1CoordInputEditEdge"
                                                   style="border: 1px solid darkgrey;" disabled>
                                        </div>
                                        <div class="add-node-body-titles">Координата Y1</div>
                                        <div class="add-node-body-input" id="INFOy1Coord">
                                            <input type="text" val="Загрузка данных" id="y1CoordInputEditEdge"
                                                   style="border: 1px solid darkgrey;" disabled>
                                        </div>
                                        <div class="add-node-body-titles">Координата Z1</div>
                                        <div class="add-node-body-input" id="INFOz1Coord">
                                            <input type="text" val="Загрузка данных" id="z1CoordInputEditEdge"
                                                   style="border: 1px solid darkgrey;" disabled>
                                        </div>
                                        <div class="add-node-body-titles">Высота, м</div>
                                        <div class="add-node-body-input" id="INFOheightEdge">
                                            <input type="text" val="Загрузка данных" onfocus="noLetters(this.id);"
                                                   id="heightInputEditEdge" style="border: 1px solid darkgrey;">
                                        </div>
                                        <div class="add-node-body-titles">Цвет горной выработки</div>
                                        <div class="dropdown-add-node-title" id="INFOedgeDropdown">
                                            <div class="dropdown-inner-node" id="INFOedgeDrpdParams"
                                                 style="border: 1px solid darkgrey;"
                                                 style="border: 1px solid darkgrey;">
                                                <div class="dropdown-title">Загрузка</div>
                                                <div class="dropdown-caret">
                                                    <div class="caret"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="dropdown-list" id="INFOedgeDropdownList">
                                        </div>
                                        <div class="add-node-body-titles">Тип горной выработки</div>
                                        <div class="dropdown-add-node-title" id="INFOedgeTypeDropdown">
                                            <div class="dropdown-inner-node" id="INFOedgeTypeDrpdParams"
                                                 style="border: 1px solid darkgrey;">
                                                <div class="dropdown-title">Загрузка</div>
                                                <div class="dropdown-caret">
                                                    <div class="caret"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="dropdown-list" id="INFOedgeTypeDropdownList">
                                        </div>
                                        <div class="add-node-body-titles">Название пласта</div>
                                        <div class="dropdown-add-node-title" id="INFOedgePlastTitleDropdown">
                                            <div class="dropdown-inner-node" id="INFOedgePlastTitleDrpdParams"
                                                 style="border: 1px solid darkgrey;">
                                                <div class="dropdown-title">Загрузка</div>
                                                <div class="dropdown-caret">
                                                    <div class="caret"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="dropdown-list" id="INFOedgePlastTitleDropdownList"></div>

                                        <div class="add-node-body-titles">Уставка CO</div>
                                        <div class="add-node-body-input" id="INFOco">
                                            <input type="text" onfocus="noLetters(this.id);" id="coInputEditEdge"
                                                   style="border: 1px solid darkgrey;">
                                        </div>
                                    </div>
                                    <div class="body-left-right-inner">
                                        <div class="add-node-body-titles">Протяженность, м</div>
                                        <div class="add-node-body-input" id="INFOlengthEdge">
                                            <input type="text" val="Загрузка данных" onfocus="noLetters(this.id);"
                                                   id="lengthInputEditEdge" style="border: 1px solid darkgrey;">
                                        </div>
                                        <div class="add-node-body-titles">Координата X2</div>
                                        <div class="add-node-body-input" id="INFOx2Coord">
                                            <input type="text" val="Загрузка данных" id="x2CoordInputEditEdge"
                                                   style="border: 1px solid darkgrey;" disabled>
                                        </div>
                                        <div class="add-node-body-titles">Координата Y2</div>
                                        <div class="add-node-body-input" id="INFOy2Coord">
                                            <input type="text" val="Загрузка данных" id="y2CoordInputEditEdge"
                                                   style="border: 1px solid darkgrey;" disabled>
                                        </div>
                                        <div class="add-node-body-titles">Координата Z2</div>
                                        <div class="add-node-body-input" id="INFOz2Coord">
                                            <input type="text" val="Загрузка данных" id="z2CoordInputEditEdge"
                                                   style="border: 1px solid darkgrey;" disabled>
                                        </div>
                                        <div class="add-node-body-titles">Ширина, м</div>
                                        <div class="add-node-body-input" id="INFOwidthEdge">
                                            <input type="text" val="Загрузка данных" onfocus="noLetters(this.id);"
                                                   id="widthInputEditEdge" style="border: 1px solid darkgrey;">
                                        </div>
                                        <div class="add-node-body-titles">Сечение, м<sup>2</sup></div>
                                        <div class="add-node-body-input" id="INFOsEdge">
                                            <input type="text" val="Загрузка данных" onfocus="noLetters(this.id);"
                                                   id="sectionInputEditEdge" style="border: 1px solid darkgrey;">
                                        </div>
                                        <div class="add-node-body-input" id="dangerEdge"
                                             style="display: flex; margin-top: 35px;">
                                            <div class="border-for-checkbox"
                                                 style="border: none; background-color: #efefef;">
                                                <input type="checkbox" id="INFOdangerCheckbox" checked="false"
                                                       style="width: auto; margin: auto 10px;"><span>Запрещенная зона</span>
                                            </div>
                                            <div class="border-for-checkbox"
                                                 style="margin-left: 10%; border: none; background-color: #efefef;">
                                                <input type="checkbox" id="INFOisConvCheckbox" checked="false"
                                                       style="width: auto; margin: auto 10px;"
                                                       onchange="toggleConveyorTagVisibility('INFOisConvCheckbox', 'tag-edit-block', 'tagEditEdge');"><span>Есть конвейер</span>
                                            </div>
                                        </div>
                                        <div class="tag-input hidden" id="tag-edit-block">
                                            <div class="add-node-body-titles">Тег конвейера</div>
                                            <div class="add-node-body-input" id="INFOtag">
                                                <input type="text" val="Загрузка данных" id="tagEditEdge"
                                                       style="border: 1px solid darkgrey;">
                                            </div>
                                        </div>
                                        <div class="add-node-body-titles">Название типа места</div>
                                        <div class="dropdown-add-node-title" id="INFOedgePlaceTypeDropdown">
                                            <div class="dropdown-inner-node" id="INFOedgePlaceTypeDrpdParams"
                                                 style="border: 1px solid darkgrey;">
                                                <div class="dropdown-title">Загрузка</div>
                                                <div class="dropdown-caret">
                                                    <div class="caret"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="dropdown-list" id="INFOedgePlaceTypeDropdownList"></div>

                                        <div class="add-node-body-titles">Уставка CH4</div>
                                        <div class="add-node-body-input" id="INFOch4">
                                            <input type="text" onfocus="noLetters(this.id);" id="ch4InputEditEdge"
                                                   style="border: 1px solid darkgrey;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="add-node-body-right">
                                <div class="add-node-photo-container" style="width: 80%;">
                                    <img src="../img/picEdge.png">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="INFOconjunctions" class="tab-pane fade">
                        <div class="conjunctions-flex">
                            <div class="conjunctions-left">
                            </div>
                            <div class="conjunctions-right">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer add-node-footer" style="height: 55px;">
                <button type="button" class="delete-object-button" id="deleteEdgeButton">Удалить</button>
                <button type="button" class="btn-primary-amicum" id="saveChangesEdge">Сохранить</button>
                <button type="button" class="btn-secondary-amicum" data-dismiss="modal" id="closeEdgeInfo">Отмена
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Окошко слоев -->

<div class="layers-container">
    <div class="layers-inner-border">
        <!--<div class="layer-1-level equipment-layer" id="1-layer-1-1" data-original-id="1-1">
            <button class="show-hide-layer glyphicon glyphicon-eye-open"></button>
            <span>Оборудование</span>
        </div>-->
        <div class="layer-1-level mine-environment-layer" id="1-layer-1-2" data-original-id="1-2">
            <button class="show-hide-layer glyphicon glyphicon-eye-open"></button>
            <button class="toggle-inner-layer glyphicon glyphicon-chevron-down"></button>
            <span>Горная среда</span>
        </div>
        <div class="layer-2-level mine-environment-list dropdown-layer" id="2-layer-1-2">
            <div class="layer-1-level edge-layer" id="1-layer-2-111" data-original-id="2-111">
                <button class="show-hide-layer glyphicon glyphicon-eye-open"></button>
                <span>Выработка</span>
            </div>
            <div class="layer-1-level conjunction-layer" id="1-layer-2-113" data-original-id="2-113">
                <button class="show-hide-layer glyphicon glyphicon-eye-open"></button>
                <span>Поворот</span>
            </div>
        </div>
        <div class="layer-1-level automated-system-layer" id="1-layer-1-4" data-original-id="1-4">
            <button class="show-hide-layer glyphicon glyphicon-eye-open"></button>
            <button class="toggle-inner-layer glyphicon glyphicon-chevron-down"></button>
            <span>Автоматизированнная система</span>
        </div>
        <div class="layer-2-level sensor-types-list dropdown-layer" id="2-layer-1-4">
            <div class="layer-1-level bpd-layer" id="1-layer-2-95" data-original-id="2-95">
                <button class="show-hide-layer glyphicon glyphicon-eye-open"></button>
                <span>БПД-3</span>
            </div>
            <div class="layer-1-level node-layer" id="1-layer-2-001" data-original-id="2-001">
                <button class="show-hide-layer glyphicon glyphicon-eye-open"></button>
                <span>Узлы связи</span>
            </div>
            <div class="layer-1-level other-lamps-layer" id="1-layer-2-001" data-original-id="2-001">
                <button class="show-hide-layer glyphicon glyphicon-eye-open"></button>
                <span>Метка прочее</span>
            </div>
            <div class="layer-1-level mine-phone-layer" id="1-layer-2-001" data-original-id="2-001">
                <button class="show-hide-layer glyphicon glyphicon-eye-open"></button>
                <span>Шахтный телефон</span>
            </div>
            <div class="layer-1-level mikrotik-layer" id="1-layer-2-001" data-original-id="2-001">
                <button class="show-hide-layer glyphicon glyphicon-eye-open"></button>
                <span>Коммутаторы MikroTik</span>
            </div>
        </div>
        <div class="layer-1-level staff-layer" id="1-layer-1-11" data-original-id="1-11">
            <button class="show-hide-layer glyphicon glyphicon-eye-open"></button>
            <span>Персонал</span>
        </div>
    </div>
</div>

<div class="context-menu">
    <div class="context-menu_element" id="contextEdit">
        <span>Редактировать</span>
    </div>
    <div class="context-menu_element" id="contextDelete">
        <span>Удалить</span>
    </div>
</div>

<!-- Модальное окно удаления -->
<div class="delete-modal" id="deleteModalDelete">
    <div class="delete-modal_header">
        <span>Удаление</span>
    </div>
    <div class="delete-modal_body">
        <span>Вы действительно хотите удалить: </span>
        <span id="pastDeleteTitle"></span>
    </div>
    <div class="delete-modal_footer">
        <button type="button" class="btn-primary-amicum" id="confirmDeleteAnything">Удалить</button>
        <button type="button" class="btn-secondary-amicum" id="confirmDenyAnything">Отмена</button>
    </div>
</div>

<!-- Модальное окно добавления оборудования -->
<div class="equipment-modal-back" id="equipmentModalBack">
    <div class="add-equipment-modal" id="equipmentModal">
        <div class="add-equipment-modal_header">
            <span>Добавление оборудования</span>
        </div>
        <div class="add-equipment-modal_body">
            <div class="add-equipment-modal_body_info-part">
                <div class="add-node-body-titles">Наименование</div>
                <div class="dropdown-add-node-title" id="equipmentDropdown">
                    <div class="dropdown-inner-node" id="equipmentDrpdParams">
                        <div class="dropdown-title">Выберите оборудование</div>
                        <div class="dropdown-caret">
                            <div class="caret"></div>
                        </div>
                    </div>
                </div>
                <div class="dropdown-list" id="equipmentDropdownList">
                </div>
                <div class="add-node-body-titles">Координата X</div>
                <div class="add-node-body-input" id="xCoordEquipment">
                    <input type="text">
                </div>
                <div class="add-node-body-titles">Координата Y</div>
                <div class="add-node-body-input" id="yCoordEquipment">
                    <input type="text">
                </div>
                <div class="add-node-body-titles">Координата Z</div>
                <div class="add-node-body-input" id="zCoordEquipment">
                    <input type="text">
                </div>
                <div class="add-node-body-titles">Местоположение</div>
                <div class="add-node-body-input" id="equipmentPlaceTitle">
                    <input type="text" disabled>
                </div>
            </div>
            <div class="add-equipment-modal__body_photo-part">
                <img src="">
            </div>
        </div>
        <div class="add-equipment-modal_footer">
            <button class="btn-primary-amicum" id="acceptEquipment">Добавить</button>
            <button class="btn-secondary-amicum" id="denyEquipment">Отмена</button>
        </div>
    </div>
</div>

<!-- Модальное окно информации об оборудовании -->
<div class="equipment-modal-back" id="INFOequipment">
    <div class="add-equipment-modal" id="INFOequipmentModal">
        <div class="add-equipment-modal_header">
            <span>Информация об оборудовании</span>
        </div>
        <div class="add-equipment-modal_body">
            <div class="add-equipment-modal_body_info-part">
                <div class="add-node-body-titles">Наименование</div>
                <div class="dropdown-add-node-title" id="INFOequipmentDropdown">
                    <div class="dropdown-inner-node" id="INFOequipmentDrpdParams">
                        <div class="dropdown-title">Выберите оборудование</div>
                        <div class="dropdown-caret">
                            <!--                            <div class="caret"></div>-->
                        </div>
                    </div>
                </div>
                <div class="dropdown-list" id="INFOequipmentDropdownList">
                </div>
                <div class="add-node-body-titles">Координата X</div>
                <div class="add-node-body-input" id="INFOxCoordEquipment">
                    <input type="text" disabled>
                </div>
                <div class="add-node-body-titles">Координата Y</div>
                <div class="add-node-body-input" id="INFOyCoordEquipment">
                    <input type="text" disabled>
                </div>
                <div class="add-node-body-titles">Координата Z</div>
                <div class="add-node-body-input" id="INFOzCoordEquipment">
                    <input type="text" disabled>
                </div>
                <div class="add-node-body-titles">Местоположение</div>
                <div class="add-node-body-input" id="INFOequipmentPlaceTitle">
                    <input type="text" disabled>
                </div>
            </div>
            <div class="add-equipment-modal__body_photo-part">
                <img src="">
            </div>
        </div>
        <div class="add-equipment-modal_footer">
            <button class="btn-primary-amicum" id="INFOacceptEquipment">Сохранить</button>
            <button class="btn-secondary-amicum" id="INFOdenyEquipment">Отмена</button>
        </div>
    </div>
</div>

<!-- Уведомление при добавлении чего-либо -->
<div class="notification-container">
    <div class="notification-container_notification-border">
        <span></span>
    </div>
</div>

<!-- Модальное окно выбора шахты при id шахты -1 -->
<div class="choose-mine_background">
    <div class="choose-mine-window">
        <div class="choose-mine-window_title">
            <span>Выберите шахту для первичного построения</span>
        </div>
        <div class="choose-mine-window_select">
            <select id="primarySelectMine">
            </select>
        </div>
        <div class="choose-mine-window_footer">
            <button id="chooseMineConfirm">Выбрать</button>
        </div>
    </div>
</div>

<!-- Перемещение объекта АС -->
<div class="move-node-modal" id="moveSaveNode">
    <div class="modal-dialog add-node-dialog" role="document" id="nodeMoveModalInfo">
        <div class="modal-content">
            <div class="modal-header add-node-header">
                <div class="add-node-header-title">
                    <span>Перемещение объекта АС</span>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" id="closeXMoveNodeInfo"
                        style="display: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body add-node-body">
                <div class="add-node-body-left">
                    <div class="add-node-body-title">
                        <span>Общие параметры</span>
                    </div>
                    <!--                    <hr>-->
                    <div class="body-left-inner">
                        <div class="add-node-body-titles">Наименование</div>
                        <div class="add-node-body-input" id="titleMoveNodeInfo">
                            <input type="text" disabled>
                        </div>

                        <div class="add-node-body-titles">Координата X</div>
                        <div class="add-node-body-input" id="xCoordMoveInfo">
                            <input type="text">
                        </div>
                        <div class="add-node-body-titles">Координата Y</div>
                        <div class="add-node-body-input" id="yCoordMoveInfo">
                            <input type="text">
                        </div>
                        <div class="add-node-body-titles">Координата Z</div>
                        <div class="add-node-body-input" id="zCoordMoveInfo">
                            <input type="text">
                        </div>
                        <div class="add-node-body-titles">Местоположение</div>
                        <div class="add-node-body-input" id="nodeMovePlaceTitleInfo">
                            <input type="text" disabled>
                        </div>
                    </div>
                </div>
                <div class="add-node-body-right">
                    <div class="add-node-photo-container" id="nodeMoveInfoPhoto">
                        Загрузка изображения
                    </div>
                </div>
            </div>
            <div class="modal-footer add-node-footer">
                <button type="button" class="btn btn-secondary btn-primary-amicum" data-dismiss="modal"
                        id="acceptMoveNodeInfo">Сохранить
                </button>
                <button type="button" class="btn btn-secondary btn-secondary-amicum" data-dismiss="modal"
                        id="denyMoveNodeInfo">Отмена
                </button>
            </div>
        </div>
    </div>
</div>


<div id="sendAlarmModal" class="send-alarm-modal">
    <div class="send-alarm-window">
        <span>Подтвердите отправку аварийного сигнала</span></br>
        <input type="text" id="alarmMessage" placeholder="Текст аварийного сообщения"
               style="padding-left: 5px; width: 100%;" val="Авария Выходи Авария">
        <div class="send-alarm-window-footer">
            <button id="confirmAlarmSignal">Подтвердить</button>
            <button id="denyAlarmSignal">Закрыть</button>
        </div>
    </div>
</div>

<div class="danger-situation-banner" id="dangerBanner">
    <div class="danger-banner-left">
        <span>Объявлена аварийная ситуация</span>
    </div>
    <div class="danger-banner-right">
        <button id="cancelAlarm">Отменить</button>
    </div>
</div>
<!-- Модальное окно со всеми параметрами работника -->
<div class="modal fade" id="all-parameters">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Параметры "<span class="worker-full-name"></span>"</h4>
            </div>
            <div class="modal-body">
                <div class="modal-table-header-container">
                    <div class="modal-table-header-element"><span>Наименование параметра</span></div>
                    <div class="modal-table-header-element"><span>Справочный</span></div>
                    <div class="modal-table-header-element"><span>Измеряемый</span></div>
                    <div class="modal-table-header-element"><span>Вычисляемый</span></div>
                    <div class="modal-table-header-element"><span>Единица измерения</span></div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="refreshBtn" class="btn btn-primary">Обновить</button>
            </div>
        </div><!-- /.модальное окно-Содержание -->
    </div><!-- /.модальное окно-диалог -->
</div><!-- /.модальное окно -->
<!-- Кастомное контекстное меню -->
<div id="inputDynamicDropdown" class="no-close-place-list">
    <div id="parsePlaceListHere" class="no-close-place-list"></div>
</div>

<div class="join-edges-modal-background" id="mergeEdgeModal">
    <div class="join-edges-container">
        <div class="join-edges-header">Объединение выработок</div>
        <div class="join-edges-body">
            <div class="join-edges-body-left">
                <div class="join-edge-left-title">Список мест</div>
                <div class="join-edge-left-place-container" id="mergedEdgePlaceContainer">
                </div>
            </div>
            <div class="join-edges-body-right">
                <div class
                <div class="join-edges-x-1">
                    <span>Поворот 1 id: </span><span id="id1MergeEdge">Загрузка</span>
                </div>
                <div class="join-edges-y-1">
                    <span>Поворот 2 id: </span><span id="id2MergeEdge">Загрузка</span>
                </div>
            </div>
        </div>
        <div class="join-edges-footer">
            <button class="join-edges-button" id="acceptMergeEdges">Объединить</button>
            <button class="join-edges-button" id="denyMergeEdges">Отмена</button>
        </div>
    </div>
</div>


<!-- Модальное окно со всеми параметрами оборудования -->
<div class="modal fade" id="equipmentParametersModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Параметры "<span class="equipment-name"></span>"</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-table-header-container">
                    <div class="modal-table-header-element"><span>Наименование параметра</span></div>
                    <div class="modal-table-header-element"><span>Справочный</span></div>
                    <div class="modal-table-header-element"><span>Измеряемый</span></div>
                    <div class="modal-table-header-element"><span>Вычисляемый</span></div>
                    <div class="modal-table-header-element"><span>Единица измерения</span></div>
                </div>
                <div class="modal-table-body"></div>
            </div>
            <div class="modal-footer">
                <button id="refreshParametersBtn" class="btn btn-primary">Обновить</button>
            </div>
        </div><!-- /.модальное окно-Содержание -->
    </div><!-- /.модальное окно-диалог -->
</div><!-- /.модальное окно -->