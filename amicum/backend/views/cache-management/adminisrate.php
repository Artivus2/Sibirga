<?php
use backend\assets\AppAsset;
use yii\web\View;
$this->title = "Администрирование системы";
$this->registerCssFile('/css/cache-management.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile( '/js/administrate.js',['depends' => [AppAsset::className()], 'position' => View::POS_END]);//регестрирует ссылку на js файл
?>
<!--11 Автоматическая привязка лучей к людям-->
<div class="autoBindSensors">
    <h4><span> Автоматическая привязка лучей к людям </span></h4>
        <input type="file" class="autoBindSensors" id="input_load_file">
    <p><button class="autoBindSensors" id="button_autoBindSensors" onclick="funcAutoBindSensors()">Привязать</button>
<!--        onchange="getFileForBind(this)"-->
    </p>
    <p><label for="output_autoBindSensors"></label><textarea class="autoBindSensors" id="output_autoBindSensors" rows="15" cols="230"></textarea></p>
<!--    <p><button class="autoBindSensors" id="button_autoBindSensors" >Привязать</button>-->
<!--        <input type="file" class="autoBindSensors" id="input_load_file" >-->
<!--    </p>-->
<!--    <p><label for="output_autoBindSensors"></label><textarea class="autoBindSensors" id="output_autoBindSensors" rows="15" cols="230"></textarea></p>-->
</div>
<!--3. Переинициализация кеша и перезапуск ССД со стационарных датчиков CH4 Микон-->
<div class="restartCacheAndOpc">
    <h4><span>Переинициализация кеша и перезапуск ССД со стационарных датчиков CH4 Микон</span></h4>
    <p> <input type="text" placeholder="mine_id" class="restartCacheAndOpc" id="mine_id_restartCacheAndOpc"></p>
    <p><button class="restartCacheAndOpc" id="button_restartCacheAndOpc" onclick="funcRestartCacheAndOpc()">Отправить запрос</button></p>
    <p><label for="output_restartCacheAndOpc"></label><textarea class="restartCacheAndOpc" id="output_restartCacheAndOpc" rows="15" cols="230"></textarea></p>
</div>
<!--1. Остановка ССД со стационарных датчиков CH4 Микон-->
<div class="stopOpc">
    <h4><span>Остановка ССД со стационарных датчиков CH4 Микон</span></h4>
    <p> <input type="text" placeholder="mine_id" class="stopOpc" id="mine_id_stopopc"></p>
    <p><button class="stopOpc" id="button_stopopc" onclick="funcStopOpc()">Отправить запрос</button></p>
    <p><label for="result_stopopc"></label><textarea class="stopOpc" id="result_stopopc" rows="15" cols="230"></textarea></p>
</div>
<!--2. Запуск ССД со стационарных датчиков CH4 Микон-->
<div class="startOpc">
    <h4><span>Запуск ССД со стационарных датчиков CH4 Микон</span></h4>
        <p> <input type="text" placeholder="mine_id" class="startOpc" id="mine_id_startopc"></p>
    <p><button class="startOpc" id="button_startopc" onclick="funcStartOpc()">Отправить запрос</button></p>
    <p><label for="output_startopc"></label><textarea class="startOpc" id="output_startopc" rows="15" cols="230"></textarea></p>
</div>
<!--4. Очистка кеша-->
<div class="flushAllCache">
    <h4><span>Очистка кеша</span></h4>
<!--    <p> <input type="text" placeholder="mine_id" class="flushAllCache" id="mine_id_flushAllCache"></p>-->
    <p><button class="flushAllCache" id="button_flushAllCache" onclick="funcFlushAllCache()">Отправить запрос</button></p>
    <p><label for="output_flushAllCache"></label><textarea class="flushAllCache" id="output_flushAllCache" rows="15" cols="230"></textarea></p>
</div>
<!--5. Инициализация кеша выработок-->
<div class="initEdgeCache">
    <h4><span>Инициализация кеша выработок</span></h4>
    <p> <input type="text" placeholder="mine_id" class="initEdgeCache" id="mine_id_initEdgeCache"></p>
    <p><button class="initEdgeCache" id="button_initEdgeCache" onclick="funcInitEdgeCache()">Отправить запрос</button></p>
    <p><label for="output_restartCacheAndOpc"></label><textarea class="initEdgeCache" id="output_initEdgeCache" rows="15" cols="230"></textarea></p>
</div>
<!--6.инициализация кеша графа выработок-->
<div class="initSensorCache">
    <h4><span>инициализация кеша графа выработок</span></h4>
    <p> <input type="text" placeholder="mine_id" class="initSensorCache" id="mine_id_initSensorCache"></p>
    <p><button class="initSensorCache" id="button_initSensorCache" onclick="funcInitSensorCache()">Отправить запрос</button></p>
    <p><label for="output_initSensorCache"></label><textarea class="initSensorCache" id="output_initSensorCache" rows="15" cols="230"></textarea></p>
</div>
<!--7-->
<!--// actionInitSensorCache    - метод инициализации сенсоров-->
<!--8-->
<!--// actionInitWorkerCache    - метод инициализации воркеров-->
<!--9-->
<!--// actionInitEquipmentCache - метод инициализации оборудования-->
<!--10-->
<!---->
<!--// actionSearchDuplicateSensor  - метод поиска дубликатов сенсоров (в амикум)-->

