<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;
use yii\web\View;

$getSourceData = 'const kindGroupSituationsArray = ' . json_encode($kinds) . ', 
groupSituationsArray = ' . json_encode($groups) . ', situationsArray=' . json_encode($situations) . ',
eventsArray = ' . json_encode($events) . ' ;
';
$this->registerJs($getSourceData, View::POS_HEAD, 'my-inline-js');
$this->registerJsFile('/js/handbook-situation.js', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/filters.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/main.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/handbook-situation.css', ['depends' => [AppAsset::className()]]);
$this->title = "Справочник ситуаций";
?>
    <div class="main_info">
        <div class="row" id="filters_block">
            <div id="filters_label">
                <span class="filters-title">Фильтр: </span>
            </div>
            <div id="filters">
                <div id="filter_container">
                </div>
                <div id="input_container">
                    <input id="filter_text" placeholder="Введите текст...">
                </div>
                <div id="add_filter_button" title="Добавить фильтр"
                     onclick="add_filter_modal();/*moving_after_plus('');*/">
                    <i class="icon-filter-add-btn"></i>
                </div>
                <button id="clear_filter_button" class="clear_filter_btn" title="Очистить фильтр">
                    &times
                </button>
                <div id="accept_filters">
                    <i class="icon-filter-search-btn"></i>
                </div>
            </div>

            <div id="filters_types_list" class="hidden main-filter">
                Фильтровать по:
                <button id="close_modal" class="close">&times;</button>
                <div class="filter_types">
                    <div id="fill_kinds">Виды групп ситуаций</div>
                    <div class="line-filters"></div>
                    <div id="fill_groups">Группы ситуаций</div>
                    <div class="line-filters"></div>
                    <div id="fill_situations">Ситуации</div>
                    <div class="line-filters"></div>
                    <div id="fill_events">События</div>
                </div>

            </div>
            <div id="filters_list" class="hidden">

            </div>
        </div>

        <div class="row">
            <div id="situationContainer" class="col-xs-12">
                <div class="row">
                    <!--                    <div class="block-for-name-cell col-xs-3 sort-kinds" onclick="sortCol('sort-kinds')">-->
                    <div class="block-for-name-cell col-xs-3 sort-kinds">
                        <h4>Виды групп ситуаций<i class="glyphicon glyphicon-triangle-top"></i></h4>
                    </div>
                    <!--                    <div class="col-xs-3 hidden sort-groups" onclick="sortCol('sort-groups')">-->
                    <div class="col-xs-3 hidden sort-groups">
                        <h4>Группы ситуаций<i class="glyphicon"></i></h4>
                    </div>
                    <!--                    <div class="col-xs-3 hidden sort-situations" onclick="sortCol('sort-situations')">-->
                    <div class="col-xs-3 hidden sort-situations">
                        <h4>Ситуации<i class="glyphicon"></i></h4>
                    </div>
                    <!--                    <div class="col-xs-3 hidden sort-events" onclick="sortCol('sort-events')">-->
                    <div class="col-xs-3 hidden sort-events">
                        <h4>События<i class="glyphicon"></i></h4>
                    </div>
                </div>
                <div class="row" id="kindsContainer">
                    <div id='svg_object' class='svg-style'>
                        <svg xmlns="http://www.w3.org/2000/svg" width="100%" id="connectorCanvas"></svg>
                    </div>
                    <div class="col-xs-3 left-side">
                        <div id="blockOfKinds" class="kinds-block"></div>
                    </div>
                    <div class="col-xs-9 right-side hidden"></div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="manipulationWithData">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                        <h5 id="header_edit"></h5>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-xs-12" id="modal_form">
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <div class="form-group">
                            <div>
                            </div>
                            <div>
                                <button class="btn btn-default" data-dismiss="modal" aria-hidden="true">
                                    Отмена
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div id="blockActionMenu" class="container-of-action-menu">
            <div id="actionMenu" class="action-menu">
                <button type="button" id="btn_add_first" data-target="#manipulationWithData" data-toggle="modal"><i
                            class="icon-add-current-btn"></i></button>
                <button type="button" id="btn_add_next" data-target="#manipulationWithData" data-toggle="modal"><i
                            class="icon-add-next-btn"></i></button>
                <button type="button" id="btn_edit_current" data-target="#manipulationWithData" data-toggle="modal"><i
                            class="icon-edit-btn"></i></button>
                <button type="button" id="btn_delete_current" data-target="#manipulationWithData" data-toggle="modal"><i
                            class="icon-delete-btn"></i></button>
                <button type="button" id="btn_copy"><i class="icon-copy_btn"></i></button>
                <button type="button" id="btn_paste"><i class="icon-insert-btn"></i></button>
                <button type="button" id="btn_reset_selection"><i class="icon-reset-selection-btn"></i></button>


            </div>
            <div class="triangle-position">
                <div id="triangleContainer" class="triangle-container">
                    <div class="triangle-action-menu" id="actionMenuTriangle"></div>
                </div>
            </div>
        </div>

    </div>

<?php

//echo "<pre>";

//var_dump($groups);
//echo "</pre>";


$script = <<< JS
    // $("#filter_text").focus(); для задания фокуса строке фильтра, но нам эта функция не особо нужна
    sortKinds(kindGroupSituationsArray,"sort-kinds", filters);
    setHeightForKinds();
    sortKindsByClick(kindGroupSituationsArray, "sort-kinds", filters);
    $(window).resize(function() {
        console.log('resize entered');
        setHeightForKinds();
        setStaticTriangleHeight('kinds');
        setStaticTriangleHeight('groups');
        setStaticTriangleHeight('situations');
        // setLeftCoord("filters_types_list");
        // setLeftCoord("filters_list");
    });
   
    Array.from(document.querySelectorAll("div[id^=fill_]")).forEach(elem => {
        let class_title = elem.id.split("_")[1];
        
        //console.log(elem);
        switch(class_title) {
            case "kinds":
                // console.log("1");
                 elem.addEventListener("click", function(){
                    add_filter(class_title, kindGroupSituationsArray);
                });
                break;
            case "groups":
            // console.log("2");
                elem.addEventListener("click", function(){
                    add_filter(class_title, groupSituationsArray);
                });
                break;
            case "situations":
                // console.log("3");
                elem.addEventListener("click", function(){
                    add_filter(class_title, situationsArray);
                });
                break;
            case "events":
            // console.log("4");
                elem.addEventListener("click", function(){
                    add_filter(class_title, eventsArray);
                });
                break;
        }
       
    });
   
JS;
$this->registerJs($script, yii\web\View::POS_READY);
$this->registerJsFile('/js/filter.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
?>
