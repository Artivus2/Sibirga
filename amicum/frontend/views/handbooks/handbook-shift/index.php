<?php
use yii\web\View;
$getSourceData =  'let plan_shifts = ' . json_encode($plan_shifts) .  '
shift_types= ' . json_encode($shift_types) . ',
model = ' . json_encode($model) . ' ;';

$this->registerJs($getSourceData, View::POS_HEAD, 'shift-js');
$this->registerCssFile('../css/handbook.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
// $this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('/css/main.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/employees.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/shift.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/style.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/js/handbook-shift.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerCssFile('/css/header-form-handbook.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->title = 'Справочник режимов работы';

?>
<!--Прелодер-->
<div id="preload" class="hidden">
    <div class="circle-container">
        <div id="circle_preload"></div>
        <h4 class="preload-title">Идёт загрузка</h4>
    </div>
</div>
<!--Кнопка добавления режима работы-->
<header class="clear">
    <div class="handbook-header__add-item" id="addButton">
        <div class="button-add-left">
            <div class="glyphicon glyphicon-plus"></div>
            <div class="button-add-right"></div>
        </div>
        <div class="handbook-header__search-container addShiftModeButton"><span>Добавить</span></div>
    </div>
    <!--    <button class="addShiftModeButton" title="Добавить новый режим работы"><span class="icon-add-current-btn"></span> Добавить режим работы</button>-->
</header>
<!--Конец кнопки-->
<!--Левый блок со списком режимов-->
<div class="row col-xs-12 table-margin">
    <div class="col-xs-2 table-margins">
        <div class="work-mode-left-header">
            <span>Режим работы</span>
        </div>
        <div class="work-modes-left">
            <ul class="list-script">
                <!--<li class=" list-styles-header">Режим работы</li>-->
            </ul>
        </div>
    </div>
    <!--Таблица со списком смен-->
    <div class="col-xs-10 table-margins">
        <table class="table-main table-striped" id="grid">
            <thead>
            <tr class="table-title">
                <th class="th-id" data-type="th-id">Порядок смены <span class="caret caret-id rotate180"></span><span class="glyphicon"></span></th>
                <th class="th-name" data-type="th-name">Наименование смены <span class="caret caret-name rotate180"></span><span class="glyphicon"></span></th>
                <th class="th-type" data-type="th-type">Тип смены <span class="caret caret-type rotate180"></span><span class="glyphicon"></span></th>
                <th class="th-start" data-type="th-start">Начало смены <span class="caret caret-start rotate180"></span><span class="glyphicon"></span></th>
                <th class="th-stop" data-type="th-stop">Окончание смены <span class="caret caret-stop rotate180"></span><span class="glyphicon"></span></th>
                <th class="table-empty"></th>
            </tr>
            </thead>
            <tbody>
            <!--<td>
                <tr>1</tr>
                <tr>Смена 1</tr>
                <tr>Ремонтная</tr>
                <tr>9:00</tr>
                <tr>14:59:59</tr>
            </td>-->
            </tbody>
            <tfoot></tfoot>
        </table>
    </div>
</div>
<!--Конец блока-->
<!--Модальное окно, отвечающее за добавление нового режима работы-->
<div id="addShiftMode" class="modal fade" tabindex="-1" role="dialog" >
    <div class="modal-dialog" role="document">
        <div class="modal-content no-padding">
            <div class="modal-header">
                <div class="add-header">
                    <span class="icon-add-current-btn"></span>
                    <span>Добавление нового режима работы</span>
                </div>
                <button class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body addShiftMode">
                <span class="modalCreateShiftModeBody">Введите название режима</span>
                <input id="add-mode" type="text">
            </div>
            <div class="modal-footer" id="foot-margin-small" style="top: 84px;">
                <button class="btn btn-primary" id="add-work-mode" type="button" data-dismiss="modal">Создать</button>
                <button class="btn" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>
<!--Конец модального окна-->
<!--Всплывающее окно с кнопками-->
<div id="blockActionMenu" class="container-of-action">
    <div class="buttons">
        <button id="addWorkShift"><span class="icon-add-current-btn"></span></button>
        <button id="editWorkShiftMode"><span class="icon-edit-btn"></span></button>
        <button id="deleteWorkShiftMode"><span class="icon-delete-btn"></span></button>
    </div>
    <div class="bottom-triangle">
    </div>
</div>
<!--Конец всплывающего окна-->
<!--Модальная форма редактирования режима работы-->
<div id="editShiftMode" class="modal fade" tabindex="-1" role="dialog" >
    <div class="modal-dialog" role="document">
        <div class="modal-content no-padding">
            <div class="modal-header edit-header">
                <div class="add-header">
                    <span class="icon-edit-btn"></span>
                    <span>Редактируйте режим работы</span>
                </div>
                <button class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body addShiftMode">
                <span class="modalCreateShiftModeBody">Измените название режима</span>
                <input id="edit-mode" type="text">
            </div>
            <div class="modal-footer" id="foot-margin-small">
                <button class="btn btn-primary" id="edit-work-mode" type="button" data-dismiss="modal">Изменить</button>
                <button class="btn" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>
<!--Конец модальной формы-->
<!--Модальная форма удаления режима работы-->
<div id="deleteShiftMode" class="modal fade" tabindex="-1" role="dialog" >
    <div class="modal-dialog" role="document">
        <div class="modal-content no-padding">
            <div class="modal-header edit-header">
                <div class="add-header">
                    <span class="icon-delete-btn"></span>
                    <span>Удаление режима работы</span>
                </div>
                <button class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body deleteShiftModeBody">
                <span class="modalDeleteShiftModeBody"></span>
            </div>
            <div class="modal-footer" id="foot-margin-small">
                <button class="btn btn-primary" id="delete-work-mode" type="button" data-dismiss="modal">Удалить</button>
                <button class="btn" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>
<!--Конец формы-->
<!--Модальная форма создания смены-->
<div id="addfonmodal">
    <div id="addShift" class="modal fade" tabindex="-1" role="dialog" style="position: relative;">
        <div class="modal-dialog" role="document">
            <div class="modal-content no-padding">
                <div class="modal-header">
                    <div class="add-header">
                        <span class="icon-add-current-btn"></span>
                        <span>Добавление смены</span>
                    </div>
                    <button class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="addShift">
                    <span class="createShiftBodyTitle">Наименование смены:<span id="getTitleFromArray"></span></span></span>
                    <div class="shiftTypeList">
                        <span>Выберите тип смены</span>
                        <!--                        <select class="shift-select">-->
                        <!--                            <option value="1">Ремонтная</option>-->
                        <!--                            <option value="2">Производственная</option>-->
                        <!--                        </select>-->

                        <div class="shift-select" id="shift_mode">
                            <span class="empty-span" id="shift_mode_select">Выберите тип смены...</span><i
                                    class="shift-caret"></i>
                            <ul class="shift-mode-list hidden">
                                <li class="shift-mode-title" value="1">Ремонтная</li>
                                <li class="shift-mode-title" value="2">Производственная</li>
                            </ul>
                        </div>
                    </div>
                    <span class="createShiftBodyTitle2">Установите время смены</span>
                </div>
                <div class="col-xs-12">
                    <div class="col-xs-5 add-shift-left">
                        <p>Введите начало смены</p>
                        <p>Введите окончание смены</p>
                    </div>
                    <div class="col-xs-4 add-shift-center">
                        <span><input id="t-start-hour" placeholder="00" maxlength="2" min="0" max="23" type="text" onkeyup="jmp(this)" class="hide-add"> : <input id="t-start-min" placeholder="00" min="0" max="59" maxlength="2" type="text" onkeyup="jmp(this)" class="hide-add"> : <input id="t-start-sec" placeholder="00" min="0" max="59" maxlength="2" type="text" onkeyup="jmp(this)" class="hide-add"></span>
                        <span><input id="t-stop-hour" placeholder="00" maxlength="2" min="0" max="23" type="text" onkeyup="jmp(this)" class="hide-add"> : <input id="t-stop-min" placeholder="00" min="0" max="59" maxlength="2" type="text" onkeyup="jmp(this)" class="hide-add"> : <input id="t-stop-sec" placeholder="00" min="0" max="59" maxlength="2" type="text" onkeyup="jmp(this)" class="hide-add"></span>
                    </div>
                    <div class="col-xs-3">
                        <div class="clock-frame">
                            <div class="clock">
                                <!--                        <img src="/img/icon-clock-bg.png" id="clock-bg">-->
                            </div>
                            <div  class="clock-overlay"><img src="/img/clock-overlay.png" id="clock-dots"></div>
                        </div>

                        <span class="disp-hour-start"></span><span class="disp-hour-stop"></span>
                    </div>
                </div>
                <div id="foot-margin">
                    <button class="btn btn-primary add-shift-btn" id="add-shift" type="button" data-dismiss="modal">Добавить</button>
                    <button class="btn" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!--Конец модальной формы-->
<!--Модальная форма редактирования смены-->
<div id="editfonmodal">
    <div id="editShift" class="modal fade" tabindex="-1" role="dialog" style="position: relative;" >
        <div class="modal-dialog" role="document">
            <div class="modal-content no-padding">
                <div class="modal-header edit-header">
                    <div class="add-header">
                        <span class="icon-edit-btn"></span>
                        <span>Редактирование смены</span>
                    </div>
                    <button class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="addShift">
                    <span class="createShiftBodyTitle">Наименование смены:<span id="getTitleFromArray2"></span></span></span>
                    <div class="shiftTypeList">
                        <span>Выберите тип смены</span>
                        <!--                        <select class="shift-select" id="sc2">-->
                        <!--                            <option value="1">Ремонтная</option>-->
                        <!--                            <option value="2">Производственная</option>-->
                        <!--                        </select>-->
                        <div class="shift-select" id="shift_mode_edit">
                            <span class="empty-span" id="shift_mode_select_edit">Выберите тип смены...</span><i
                                    class="shift-caret"></i>
                            <ul class="shift-mode-list hidden">
                                <li class="shift-mode-title" value="1">Ремонтная</li>
                                <li class="shift-mode-title" value="2">Производственная</li>
                            </ul>
                        </div>
                    </div>
                    <span class="createShiftBodyTitle2">Установите время смены</span>
                </div>
                <div class="col-xs-12">
                    <div class="col-xs-5 add-shift-left">
                        <p>Введите начало смены</p>
                        <p>Введите окончание смены</p>
                    </div>
                    <div class="col-xs-4 add-shift-center">
                            <span><input id="et-start-hour" placeholder="00" maxlength="2" min="0" max="23" type="text"">
                                : <input id="et-start-min" placeholder="00" min="0" max="59" maxlength="2" type="text"">
                                : <input id="et-start-sec" placeholder="00" min="0" max="59" maxlength="2" type="text""></span>
                        <span><input id="et-stop-hour" placeholder="00" maxlength="2" min="0" max="23" type="text"">
                                : <input id="et-stop-min" placeholder="00" min="0" max="59" maxlength="2" type="text"">
                                : <input id="et-stop-sec" placeholder="00" min="0" max="59" maxlength="2" type="text""></span>
                    </div>
                    <div class="col-xs-3">
                        <div class="clock-frame">
                            <div class="clock"></div>
                            <div  class="clock-overlay"><img src="/img/clock-overlay.png" id="clock-dots"></div>
                        </div>

                        <span class="disp-hour-start"></span><span class="disp-hour-stop"></span>
                    </div>
                </div>
                <div class="modal-footer" id="foot-margin">
                    <button class="btn btn-primary add-shift-btn" id="edit-shift" type="button" data-dismiss="modal">Изменить</button>
                    <button class="btn" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!--Конец модальной формы-->
<!--Модальная форма удаления смены-->
<div id="deleteShift" class="modal fade" tabindex="-1" role="dialog" >
    <div class="modal-dialog" role="document">
        <div class="modal-content no-padding">
            <div class="modal-header edit-header">
                <div class="add-header">
                    <span class="icon-delete-btn"></span>
                    <span>Удаление смены</span>
                </div>
                <button class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body deleteShiftBody">
                <span class="modalDeleteShiftBody"></span>
            </div>
            <div class="modal-footer" id="foot-margin-small">
                <button class="btn btn-primary" id="delete-work-shift" type="button" data-dismiss="modal">Удалить</button>
                <button class="btn" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>
<!--Конец модальной формы-->