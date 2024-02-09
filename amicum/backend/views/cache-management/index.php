<?php
/* @var $this yii\web\View */

use backend\assets\AppAsset;
use yii\web\View;
$this->title = "Управление КЭШем";
$this->registerCssFile('/css/cache-management.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()], 'position' =>
    View::POS_END]);
?>
    <div class="cache-management">

        <nav>
            <ol>
                <li><a href="#function15"><span>Метод инициализации кэша</span></a></li>
                <li><a href="#function16"><span>Функция получения текущих параметров о персонале для списка людей в Unity из кэша</span></a>
                </li>
                <li><a href="#function17"><span>Функция получения текущих сведений о персонале для списка людей в Unity из кэша</span></a>
                </li>
                <li><a href="#function18"><span>Функция заполнения кэша тестовыми данными по 1 БПД-3</span></a></li>
                <li><a href="#function19"><span>Функция получения данных по 1 БПД-3 из кэша</span></a></li>
                <li><a href="#function20"><span>Функция удаления данных по 1 БПД-3 из кэша</span></a></li>
                <li><a href="#function21"><span>Функция заполнения кэша тестовыми данными по всем БПД-3</span></a></li>
                <li><a href="#function22"><span>Функция получения данных из кэша по всем БПД-3</span></a></li>
                <li><a href="#function23"><span>Функция удаления данных из кэша по всем БПД-3</span></a></li>
                <li><a href="#function24"><span>Функция обновления кэша по событиям</span></a></li>
                <li><a href="#function25"><span>Функция отправки смс сообщений</span></a></li>
                <!--                <li><a href="#function25"><span>Функция полной очистки кэша</span></a></li>-->
            </ol>
        </nav>

        <!--------------------------------------------------------------------------------------------------------------------->
        <div class="function-block init-cache" id="function15">
            <h3 class="function-name">
                <span>Метод инициализации кэша</span>
            </h3>
            <div class="function-inputs">
                <input type="text" placeholder="flag_del_all" class="flag_del_all">
                <input type="text" placeholder="mine_id" class="mine_id">
                <input type="text" placeholder="worker_id" class="worker_id">
                <input type="text" placeholder="type_parameter_parameter_id" class="type_parameter_parameter_id">
            </div>
            <button class="send-query">Отправить запрос</button>
            <div class="response-container">
                <iframe src="" frameborder="1"></iframe>
            </div>
        </div>

        <div class="function-block get-cache-worker-parameters" id="function16">
            <h3 class="function-name">
                <span>Функция получения текущих параметров о персонале для списка людей в Unity из кэша</span>
            </h3>
            <div class="function-inputs">
                <input type="text" placeholder="mine_id" class="mine_id">
                <input type="text" placeholder="worker_id" class="worker_id">
            </div>
            <button class="send-query">Отправить запрос</button>
            <div class="response-container">
                <iframe src="" frameborder="1"></iframe>
            </div>
        </div>

        <div class="function-block get-cache-worker-info" id="function17">
            <h3 class="function-name">
                <span>Функция получения текущих сведений о персонале для списка людей в Unity из кэша</span>
            </h3>
            <div class="function-inputs">
                <input type="text" placeholder="mine_id" class="mine_id">
                <input type="text" placeholder="worker_id" class="worker_id">
            </div>
            <button class="send-query">Отправить запрос</button>
            <div class="response-container">
                <iframe src="" frameborder="1"></iframe>
            </div>
        </div>

        <div class="function-block fill-cache-bpd-info" id="function18">
            <h3 class="function-name">
                <span>Функция заполнения кэша тестовыми данными по 1 БПД-3</span>
            </h3>
            <div class="function-inputs">
                <input type="text" placeholder="ip_address" class="ip">
                <input type="text" placeholder="dcs_id" class="dcs_id">
                <input type="text" placeholder="tag_name" class="tag_name">
                <input type="text" placeholder="tag_value" class="tag_value">
                <input type="text" placeholder="date_time" class="date_time">
            </div>
            <button class="send-query">Отправить запрос</button>
            <div class="response-container">
                <iframe src="" frameborder="1"></iframe>
            </div>
        </div>

        <div class="function-block get-one-bpd-data-cache" id="function19"> //new new new new new new new
            <h3 class="function-name">
                <span>Функция получения данных по 1 БПД-3 из кэша</span>
            </h3>
            <div class="function-inputs">
                <input type="text" placeholder="sensor_id" class="sensor_id">
            </div>
            <button class="send-query">Отправить запрос</button>
            <div class="response-container">
                <iframe src="" frameborder="1"></iframe>
            </div>
        </div>

        <div class="function-block delete-one-bpd-data-cache" id="function20"> //new new new new new new new
            <h3 class="function-name">
                <span>Функция удаления данных по 1 БПД-3 из кэша</span>
            </h3>
            <div class="function-inputs">
                <input type="text" placeholder="sensor_id" class="sensor_id">
            </div>
            <button class="send-query">Отправить запрос</button>
            <div class="response-container">
                <iframe src="" frameborder="1"></iframe>
            </div>
        </div>

        <div class="function-block add-all-bpd-info" id="function21">
            <h3 class="function-name">
                <span>Функция заполнения кэша тестовыми данными по всем БПД-3</span>
            </h3>

            <button class="send-query">Отправить запрос</button>
            <div class="response-container">
                <iframe src="" frameborder="1"></iframe>
            </div>
        </div>

        <div class="function-block get-all-bpd-info" id="function22">
            <h3 class="function-name">
                <span>Функция получения данных из кэша по всем БПД-3</span>
            </h3>

            <button class="send-query">Отправить запрос</button>
            <div class="response-container">
                <iframe src="" frameborder="1"></iframe>
            </div>
        </div>

        <div class="function-block remove-all-bpd-cache-data" id="function23">
            <h3 class="function-name">
                <span>Функция удаления данных из кэша по всем БПД-3</span>
            </h3>

            <button class="send-query">Отправить запрос</button>
            <div class="response-container">
                <iframe src="" frameborder="1"></iframe>
            </div>
        </div>

        <div class="function-block event-force-update-cache" id="function24">
            <h3 class="function-name">
                <span>Функция обновления кэша по событиям</span>
            </h3>
            <button class="send-query">Отправить запрос</button>
            <div class="response-container">
                <iframe src="" frameborder="1"></iframe>
            </div>
        </div>

        <div class="function-block send-sms" id="function25">
            <h3 class="function-name">
                <span>Функция отправки смс сообщений</span>
            </h3>
            <div class="function-inputs">
                <input type="tel" class="num" placeholder="Номер телефона в формате 79991234568">
                <input type="text" class="msg" placeholder="Текст сообщения">
            </div>
            <button class="send-query">Отправить запрос</button>
            <div class="response-container">
                <iframe src="" frameborder="1"></iframe>
            </div>
        </div>
    </div>
<?php
//date_default_timezone_set('Asia/Novokuznetsk');                                                             //устанавливаем часовой пояс
//date_default_timezone_set('Europe/Moscow');                                                             //устанавливаем часовой пояс
$current_date = date('d.m.Y H:i:s');                                                                             //берем текущую дату и время
//echo nl2br($current_date."");
$offset = date('Z') / (60*60);                                                                                        //находим разницу во времени в часах между 0 часовым поясом и текущим
//echo nl2br($offset."");
$real_time = gmdate('Y-m-d H:i:s', strtotime($current_date. '+3 hours'));                          //находим реальное время часового пояса
//echo $real_time;                                                                                                      //вывод временеи для тестирования
$script = <<< JS
	let serverDate = new Date("$real_time");                                                                            //записываем текущее время в переменную
	    
	function showTime(serverDate) {                                                                                               //функция отображения серверного времени
		serverDate.setSeconds(serverDate.getSeconds() + 1);                                                             //устанавливаем новое время + 1 секунда
		document.getElementById("current-date").innerHTML = serverDate.toLocaleString("ru").replace(",","");            //записываем в div'е текущее время 
	}
	showTime(serverDate);                                                                                                         //вызов функции отображения серверного времени
 	setInterval(function(){
 	    showTime(serverDate) }, 1000);                                                                                        //вызываем функцию отображения серверного времени каждую секунду
 	                                                                                                                    //Всё это было сделано для того, чтобы не нагружать сервер еще одним запросом, который шлется каждую секунду непрерывно на всех страницах,
 	                                                                                                                    //вместо этого мы 1 раз получаем серверное время и потом на JavaScript'е прибавляем 1 секунду к текущей дате и обновляем это время каждую секнуду
	
JS;
$this->registerJs($script, yii\web\View::POS_READY);
$this->registerJsFile('/js/index.js', ['depends' => [AppAsset::className()],'position' => \yii\web\View::POS_END]);
?>
<?php
$this->registerJsFile('/js/jquery.contextMenu.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/js/cache_management.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);

?>
