<?php

use frontend\assets\AppAsset;
use yii\web\View;

$this->title = "Узлы (повороты)";
$this->registerCssFile('/css/mine_node.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/media_queries.css', ['depends' => [AppAsset::className()]]);
?>
    <div class="container" id="app">
        <div class="content">
            <div class="form-group">
                <label for="uploadFile">Выберите текстовый документ
                    <input type="file" id="uploadFile" accept=".txt" name="textDocument">
                </label>
                <button type="button" id="readFile" class="btn btn-primary mb-2">Загрузить</button>
                <button type="button" id="copyTable" class="btn btn-warning mb-2" data-clipboard-target="#myTable">Скопировать таблицу</button>
                <button class="btn btn-success" id="saveTable">Сохранить таблицу</button>
            </div>

            <div class="table-container">
                <table class="table table-bordered table-responsive table-striped table-hover" id="myTable">
                    <thead>
                    <tr>
                        <th>Узел</th>
                        <th>X</th>
                        <th>Y</th>
                        <th>Z</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>


<?php
$this->registerJsFile('/js/clipboard.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/mine_node.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
?>