<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\serviceamicum;


use backend\controllers\Assistant;
use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\cachemanagers\ServiceCache;
use backend\controllers\cachemanagers\WorkerCacheController;
use backend\controllers\webSocket\AmicumWebSocketClient;
use Exception;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\ViewSensorNetworkId;
use frontend\models\Worker;
use frontend\models\WorkerObject;
use frontend\models\WorkerParameter;
use Throwable;
use WebSocket\Client;
use Yii;
use yii\db\Query;
use yii\web\Controller;
use yii\web\Response;

class AmicumServiceController extends Controller
{
// Контроллер для управления службами сбора данных системы АМИКУМ и для последовательного обновления системы
    public static $service_OPC_key = "ServiceOPC";                                                                      //ключ в кэше
    public static $statusON = "1";
    public static $statusOFF = "0";


    // buildServiceOPCKey                                                                                               - метод создания ключа кеша службы OPC
    // StartStopOpc                                                                                                     - метод отправки в кэш флага запуска/остановки службы OPC
    // actionStopOpc                                                                                                    - метод остановки службы OPC
    // actionStartOpc                                                                                                   - метод запуска службы OPC

    // actionSearchDuplicateSensor                                                                                      - метод поиска дубликатов сенсоров
    // actionAutoBindSensors                                                                                            - метод автоматической привязки лучей к людям
    // actionLoadFileBindLamp                                                                                           - Метод для загрузки файла на сервер

    // buildServiceOPCKey                                                                                               - метод создания ключа сервиса службы OPC

    // actionSendWebSocketArray                                                                                         - тестовый метод отправки сообщения прохождения работником СКУД в АБК
    // SendWebSocketArray                                                                                               - метод отправки сообщения на веб-сокет
    // actionRestartStrata                                                                                              - метод по перезапуску страты
    // actionChangeAllDcsStatus                                                                                         - метод изменения статуса службы в кэш
    // actionCheckDcsStatus                                                                                             - метод проверки статуса разрешения на запись конкретной службе
    // actionChangeAllDcsStatus                                                                                         - метод изменения статусов  всех служб в кэш
    // actionCheckDcsState                                                                                              - Метод поиска последнего параметра по конкретной службе для определения статуса работы служб
    // actionAutoUnbindLamp                                                                                             - метод отвязки светильников по уволенным работникам
    public static function buildServiceOPCKey($mine_id)
    {
        return self::$service_OPC_key . ":" . $mine_id;
    }

    /**
     * startStopOpc       - метод отправки в кэш флага запуска/остановки службы OPC
     * Входные параметры:
     * @param $mine_id - идентификатор шахтного поля
     * @param $status_on_off - флаг остановки или запуска службы (значение: 0 или 1)
     * Разработала: Кендялова М.И.
     */
    public static function startStopOpc($mine_id, $status_on_off)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $response = array();
        try {
            $warnings[] = "startStopOpc. Начало метода";
            /***** запись ключа и флага в кэш *****/
            $key = self::buildServiceOPCKey($mine_id);
            $service_cache_controller = (new ServiceCache());
            $response = $service_cache_controller->amicum_rSet($key, $status_on_off);
            if ($response) {
                $warnings[] = "startStopOpc. Данные уложены в кеш: ключ $key , флаг $status_on_off";
            } else {
                throw new Exception("startStopOpc. Не смог уложить данные в кеш на остановку службы");
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "startStopOpc. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "startStopOpc. Окончание метода";
        $result_main = array('items' => $response, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
        //return $result_main;
    }

    // actionStopOPC - метод остановки службы OPC
    // входные параметры:
    //      mine_id         -   идентификатор шахтного поля
    // выходные параметры:
    //
    // пример: http://127.0.0.1/admin/serviceamicum/amicum-service/stop-opc?mine_id=290
    // Разработала: Кендялова М.И.
    public function actionStopOpc()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            /***** Блок проверки входных параметров *****/
            $post = Assistant::GetServerMethod();
            if (isset($post['mine_id']) and $post['mine_id'] != "") {
                $mine_id = $post['mine_id'];
            } else {
                throw new Exception("actionStopOPC. Не переданы входные параметры");
            }
            /***** Блок создания ключа в кэше и значения флага в кэш *****/
            $warnings[] = "actionStopOPC. Начло метода";
            $service_cache_controller = (new ServiceCache());                                                           //инициализация класса servicecache
            $key = self::buildServiceOPCKey($mine_id);                                                                  //создание ключа сервиса opc для кэша
            $response = $service_cache_controller->amicum_rSet($key, self::$statusOFF);                                   //добавление в кэш ключа и флага 0
            if ($response) {                                                                                              //проверка на то, что в кэш добавилось значение
                $warnings[] = "actionStopOPC. $key уложен в кеш на остановку службы";
            } else {
                throw new Exception("actionStopOPC. Не смог уложить данные в кеш на остановку службы");
            }
        } catch (Throwable $e) {                                                                                       //обрабатываем исключение
            $errors[] = "actionStopOPC. Исключение ";
            $status = 0;
            $errors[] = $e->getMessage();                                                                               //записываем ошибку в массив еррорс
            $errors[] = $e->getLine();                                                                                   //записываем ошибку в массив еррорс             //сам возврат ошибок во фронт энд
        }
        $warnings[] = "actionStopOPC. Окончание метода";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //формат возвращаемых данных json методом yii2
        Yii::$app->response->data = $result_main;

        // return $result_main;
    }

    /**
     * actionStartOPC           - метод запуска службы OPC
     * Входные параметры:
     *  mine_id         -   идентификатор шахтного поля
     * выходные параметры:
     * пример: http://127.0.0.1/admin/serviceamicum/amicum-service/start-opc?mine_id=290
     * Разработала: Кендялова М.И.
     */
    public function actionStartOpc()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        try {
            $warnings[] = "actionStartOpc. Начало метода";
            /***** Блок проверки входных параметров *****/
            $post = Assistant::GetServerMethod();
            if (isset($post['mine_id']) and $post['mine_id'] != "") {
                $mine_id = $post['mine_id'];
            } else {
                throw new Exception("actionStartOpc. Не передан входной параметер mine_id");
            }
            $warnings[] = "actionStartOpc. Проверен входной паремтр $mine_id";
            /***** Блок создания ключа в кэше и значения флага в кэш *****/
            $service_cache_controller = (new ServiceCache());                                                           //создание экземпляра класса
            $key = self::buildServiceOPCKey($mine_id);                                                                  //создание ключа для кэша
            $response = $service_cache_controller->amicum_rSet($key, self::$statusON);                                  //создание записи в кэше
            if ($response) {
                $warnings[] = "actionStartOpc. Данные уложены в кеш на запуск службы";
            } else {
                throw new Exception("actionStartOpc. Не смог уложить данные в кэш");
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "actionStartOpc. Исключение: ";
            $errors [] = $e->getMessage();
            $errors [] = $e->getLine();
        }
        $warnings[] = "actionStartOpc. Окончание метода";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
        //return $result_main;
    }


    /**
     *  actionSearchDuplicateSensor - метод поиска дубликатов сенсоров в кеше
     * !! требуется переписать на поиск дубликатов в бд
     *  пример: localhost/admin/serviceamicum/amicum-service/search-duplicate-sensor
     * Разработала: Кендялова М.И. 06.09.2019
     */
    public function actionSearchDuplicateSensor()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $startTime = microtime(true);
        try {
            $warnings[] = "actionSearchDuplicateSensor. Начало метода";
            //Блок получения сетевых идентификаторов сенсоров
            $sensor_parameters = (new SensorCacheController())->multiGetParameterValueHash('*', 88, 1);
            if ($sensor_parameters !== false and $sensor_parameters != null) {
                foreach ($sensor_parameters as $sensor_parameter) {
                    if ($sensor_parameter['parameter_id'] == 88 and $sensor_parameter['value'] != null) {
                        $sensors_netid[] = $sensor_parameter['value'];
                    }
                }
            } else {
                throw new Exception("actionSearchDuplicateSensor. Не найдены сенсоры");
            }
            // $res=$sensor_parameter_array;
            //$res =array_count_values($sensor_parameter_array);
            foreach (array_count_values($sensors_netid) as $key => $value_netid) {
                if ($value_netid > 1) $res[] = $key;
            }
            $result[] = $res;
        } catch (Exception $e) {
            $status = 0;
            $errors[] = "actionSearchDuplicateSensor. Исключение: ";
            $errors [] = $e->getMessage();
            $errors [] = $e->getLine();
        }
        $warnings[] = "actionSearchDuplicateSensor. Окончание метода " . (microtime(true) - $startTime);
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
        //return $result_main;
    }

    // actionSendWebSocketArray - тестовый метод отправки сообщения прохождения работником СКУД в АБК
    // разработал Файзуллоев А.Э.
    // дата 23.09.2019г.
    // 127.0.0.1/admin/serviceamicum/amicum-service/send-web-socket-array
    public static function actionSendWebSocketArray()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $client = null;
        try {
            $warnings[] = 'actionSendWebSocketArray. Начало выполнения метода';

            // адрес вебсокета
            $url_websocket = 'ws://192.168.1.168:8282/ws';                                                              // адрес вебсокета
            $actionType = 'publish';                                                                                    // тип действия - публикация или подписка subscribe
            $subPubList[] = 'worker_skud_in_out';                                                                       // подписка по которой происходит рассылка в вебсокете клиентам
            $type = 'setStatusSkudInOrder';                                                                             // тип сообщения - для фронта - он определяет какой метод у него запускать - можно использовать название метода/действия
            $message = array(                                                                                           // сообщение, которое мы отправляем клиенту
                'worker_id' => 1801,
                'date_time' => Assistant::GetDateNow(),
                'type_skud' => 2
            );
            $response = self::SendWebSocketArray($actionType, $subPubList, $type, $message);
            if ($response['status'] == 1) {
                $status = $response['status'];
                $result = $response['Items'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('actionSendWebSocketArray не смог выполнить отправку на вебсокет');
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionSendWebSocketArray. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'actionSendWebSocketArray. Окончание метода';

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //формат возвращаемых данных json методом yii2
        Yii::$app->response->data = $result_main;
    }

    // SendWebSocketArray - метод отправки сообщения на вебсокет
    // разработал Файзуллоев А.Э.
    // дата 23.09.2019г.
    // 127.0.0.1/admin/serviceamicum/amicum-service/send-web-socket-array
    public static function SendWebSocketArray($actionType, $subPubList, $type, $message)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $client = null;
        try {
            $warnings[] = 'SendWebSocketArray. Начало выполнения метода';

            // сервисная часть
            $date = array(
                'type' => $type,
                'message' => json_encode($message)
            );
            // подготавливаем массив для отправки на вебсокет

            $send_message = json_encode(
                array(
                    'clientType' => 'server',
                    'clientId' => 'server',
                    'actionType' => $actionType,
                    'subPubList' => $subPubList,
                    'messageToSend' => json_encode($date)
                )
            );

            $warnings[] = 'SendWebSocketArray. Все отправляемое сообщение на вебсокет';
            $warnings[] = $send_message;

            $warnings[] = 'SendWebSocketArray. Устанавливаю соединение';
            $warnings[] = 'SendWebSocketArray. Адрес вебсокета ' . AMICUM_CONNECT_STRING_WEBSOCKET;
            $address = 'ws://' . AMICUM_CONNECT_STRING_WEBSOCKET . '/ws';
            $warnings[] = 'SendWebSocketArray. Адрес вебсокета ' . $address;
            $client = new Client($address);
            if ($client) {
                $warnings[] = 'SendWebSocketArray. Отправляю данные на вебсокет';
                $client->send($send_message);
                $warnings[] = 'SendWebSocketArray. Данные отправлены';
            } else {
                throw new Exception('SendWebSocketArray не смог выполнится');
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'SendWebSocketArray. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'SendWebSocketArray. Окончание метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     *  actionAutoBindSensors - метод автоматической привязки лучей к людям
     *  пример: 127.0.0.1/admin/serviceamicum/amicum-service/auto-bind-sensors?type_bind=browser&db_strata=db_strata_vorg
     *  пример: 127.0.0.1/admin/serviceamicum/amicum-service/auto-bind-sensors
     *  пример: 127.0.0.1/admin/serviceamicum/amicum-service/auto-bind-sensors?file=1
     * $type_bind - тип привязки (если не задано, то загружать из файла иначе напрямую из БД страты)
     * $db_strata - строка подключения к БД страты
     * Разработала: Кендялова М.И. 29.09.2019
     */
    public static function actionAutoBindSensors($type_bind = null, $db_strata = null)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionAutoBindSensors");

        try {
            $log->addLog("Начало выполнения метода");

            $double_stuff_number = array();                                                                                 // дубли табельных номеров
            $net_id_null = array();                                                                                         // нет айди 0 (внутри табельные номера)
            $net_id_without_tabel_number = array();                                                                         // нет айди без табельных номеров
            $worker_without_object = array();                                                                               // нет приявзки к конкретному работнику (worker_object)
            $count_releation_lamp = array();                                                                                // статистика привязанных/не привязанных ламп
            $strata_error_lists = array();                                                                                  // В страте есть / в АМИКУМе нет
            $tabel_number_column = null;
            $net_id_column = null;
            $net_id_for_search = null;


            $count_releation_lamp['Надо привязать'] = 0;
            $count_releation_lamp['Ламп с табельными номерами'] = 0;
            $count_releation_lamp['Всего ламп'] = 0;
            $count_releation_lamp['Уже были привязаны'] = 0;


            // проверяем откуда вызывается метод (крон или файл)
            if ($type_bind == null) {
                /**
                 * Блок распарсивания csv
                 */
                //получение net_id  и tabel_number из csv

                $row = 1;
                if (PHP_OS == "Linux") {
                    $puthFile = "/var/www/html/amicum/backend/web/log/bindlamp.csv";
                } else {
                    $puthFile = "C:\\xampp\\htdocs\\bindlamp.csv";
                }
                if (($handle = fopen($puthFile, "r")) !== FALSE) {
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        $num = count($data);            //количество полей в строке
                        if ($row == 1) {
                            for ($c = 0; $c < $num; $c++) {
                                switch ($data[$c])                //поиск номера конкретного поля в массиве
                                {
                                    case 'ID Сотрудника':
                                        $tabel_number_column = $c;
                                        break;
                                    case  'Сетевой ID';
                                        $net_id_column = $c;
                                        break;
                                }
                            }
                        } else {

                            // формируем привязку табельных номеров и сетевых адресов из данных со страты.
                            $stuff_number = (int)$data[$tabel_number_column];
                            $net_id = (int)$data[$net_id_column];
                            $count_releation_lamp['Всего ламп']++;
                            if ($stuff_number) {
                                $count_releation_lamp['Ламп с табельными номерами']++;
                                $strata_list[$stuff_number]['net_id'] = $net_id;                                            // сетевые адреса
                                $strata_list[$stuff_number]['tabel_number'] = $stuff_number;                                // таблеьные номера
                                if (!isset($strata_list[$stuff_number]['count_tabel_number'])) {
                                    $strata_list[$stuff_number]['count_tabel_number'] = 1;
                                } else {
                                    $strata_list[$stuff_number]['count_tabel_number']++;                                    // список сетевых адресов по табельным номерам сгруппированным
                                    $double_stuff_number[$stuff_number] = $stuff_number;                                    // дубли табельных номеров
                                }

                                $stuff_number_for_search[] = $stuff_number;                                                 // список табельных номер для поиска людей
                                $net_id_for_search[] = $net_id;                                                             // список сетевых адресов для поика сенсоров
                            } else {
                                $net_id_without_tabel_number[] = $net_id;
                            }
                        }
                        $row++;
                    }
                    fclose($handle);

                } else {
                    throw new Exception("csv не открылся");
                }
            } else {
                $log->addLog("БД $db_strata");

                //делаем запрос в БД страты для получения актуальных данных
                $data_from_db_strata = Yii::$app->$db_strata->createCommand("SELECT personal_id as tabel_number, commtrac_external_id  FROM asset_human")->queryAll();
                $lamps_from_db_strata = array();
                foreach ($data_from_db_strata as $db_stratum) {
                    $lamps_from_db_strata[] = array(
                        'tabel_number' => $db_stratum['tabel_number'],
                        'net_id' => $db_stratum['commtrac_external_id'] & 8388607
                    );
                }
                foreach ($lamps_from_db_strata as $lamp_from_db_strata) {

                    // формируем привязку табельных номеров и сетевых адресов из данных со страты.
                    $stuff_number = (int)$lamp_from_db_strata['tabel_number'];
                    $net_id = (int)$lamp_from_db_strata['net_id'];
                    $count_releation_lamp['Всего ламп']++;
                    if ($net_id != 0) {
                        if ($stuff_number) {
                            $count_releation_lamp['Ламп с табельными номерами']++;
                            $strata_list[$stuff_number]['net_id'] = $net_id;                                            // сетевые адреса
                            $strata_list[$stuff_number]['tabel_number'] = $stuff_number;                                // таблеьные номера
                            if (!isset($strata_list[$stuff_number]['count_tabel_number'])) {
                                $strata_list[$stuff_number]['count_tabel_number'] = 1;
                            } else {
                                $strata_list[$stuff_number]['count_tabel_number']++;                                    // список сетевых адресов по табельным номерам сгруппированным
                                $double_stuff_number[$stuff_number] = $stuff_number;                                    // дубли табельных номеров
                            }

                            $stuff_number_for_search[] = $stuff_number;                                                 // список табельных номер для поиска людей
                            $net_id_for_search[] = $net_id;                                                             // список сетевых адресов для поика сенсоров
                        } else {
                            $net_id_without_tabel_number[] = $net_id;
                        }
                    } else {
                        $net_id_null[] = $stuff_number;
                    }
                }
            }
            $count_releation_lamp['Ламп с net_id=0'] = count($net_id_null);

            $log->addLog("Получил актуальные данные по работникам из старты (или файла)");

            if (!isset($strata_list) or !isset($stuff_number_for_search)) {
                throw new Exception("Список привязок Страты пуст. Привязывать не чего");
            }

            $log->addLog("обработал входной файл");

            // находим по табельным номерам список работников
            $worker_list = Worker::find()
                ->joinWith('workerObjects')
                ->where(['IN', 'tabel_number', $stuff_number_for_search])
                ->all();
            if (!$worker_list) {
                throw new Exception("Список привязок табельных номеров и работников пуст");
            }

            $log->addLog("Получил по табельным номерам список работников");

            foreach ($worker_list as $worker) {
                $worker_handbook[(int)$worker['tabel_number']]['worker_id'] = $worker['id'];
                $worker_handbook[(int)$worker['tabel_number']]['tabel_number'] = $worker['tabel_number'];
                if (isset($worker->workerObjects[0])) {
                    $worker_handbook[(int)$worker['tabel_number']]['worker_object_id'] = $worker->workerObjects[0]->id;
                } else {
                    $worker_handbook[(int)$worker['tabel_number']]['worker_object_id'] = null;
                    $worker_without_object[] = $worker['tabel_number'];
                }
            }
            unset($worker_list);

            // находим список сенсоров по сетевым адресам
            $sensor_list = ViewSensorNetworkId::find()
                ->where(['IN', 'value', $net_id_for_search])
                ->all();
            if (!$sensor_list) {
                throw new Exception("Список привязок сенсоров и сетевых адресов пуст");
            }

            $log->addLog("Получил список сенсоров по сетевым адресам");

            foreach ($sensor_list as $sensor) {
                $temp_net_id = (int)$sensor['value'];
                $sensor_handbook[$temp_net_id]['sensor_id'] = $sensor['id'];
                $sensor_handbook[$temp_net_id]['net_id'] = $sensor['value'];
            }
            unset($sensor_list);

            // получаем типы ламп временная/постоянная
            $sensor_handbook_list = (new Query())
                ->select([
                    'sensor_id',
                    'handbook_value as type_lamp'
                ])
                ->from('view_sensor_handbook_parameter_last_value_lamp')
                ->where('object_id in(47, 104)')
                ->andWhere(['parameter_id' => 459])
                ->andWhere(['parameter_type_id' => 1])
                ->all();

            $log->addLog("Получил типы ламп временная/постоянная");

            foreach ($sensor_handbook_list as $sensor_handbook_item) {
                if ($sensor_handbook_item['type_lamp'] == "Постоянная") {
                    $type_lamp_temp = 1;
                } else {
                    $type_lamp_temp = 0;
                }
                $type_lamp[$sensor_handbook_item['sensor_id']] = $type_lamp_temp;
            }
            unset($sensor_handbook_list);

            // получаем старые привязки ламп
            $last_worker_sensors = (new Query())
                ->select([
                    'sensor_id',
                    'worker_id'
                ])
                ->from('view_GetWorkerBySensor')//переписал вьюшку - на правильную, view_worker_sensor_maxDate_fullInfo - содержит много лишней информации.
                ->all();

            $log->addLog("Получил старые привязки ламп");

            if (!$last_worker_sensors) {
                $log->addLog("в БД нет привязок сенсоров и воркеров");
            }
            foreach ($last_worker_sensors as $last_worker_sensor) {
                $last_worker_sensor_handbook[$last_worker_sensor['sensor_id']]['worker_id'] = $last_worker_sensor['worker_id'];
                $last_worker_sensor_handbook[$last_worker_sensor['sensor_id']]['sensor_id'] = $last_worker_sensor['sensor_id'];
            }
            unset($last_worker_sensors);

            // получаем конкретные параметры работника для привязки/проверки привязки ламп к ним
            $worker_parameters = WorkerParameter::find()
                ->where(['parameter_id' => 83, 'parameter_type_id' => 2])
                ->indexBy('worker_object_id')
                ->all();

            $log->addLog("Получил конкретные параметры работника для привязки/проверки привязки ламп к ним");

            if (!$worker_parameters) {
                $log->addLog("в БД нет конкретных параметров работников");
            }

            $log->addLog("Начал привязку");

            $today = Assistant::GetDateNow();
            // перебираем список страты
            foreach ($strata_list as $strata) {
                // привязываемый светильник
                $net_id = (int)$strata['net_id'];                                                                       // сетевые адреса
                $tabel_number = $strata['tabel_number'];                                                                // табельный номер

                // если есть что привязывать и к кому привязывать, то привязываем
                if (isset($worker_handbook[$tabel_number]) and isset($sensor_handbook[$net_id]) and $net_id != 0) {
                    $worker_id = $worker_handbook[$tabel_number]['worker_id'];                                          // ключ работника
                    $worker_object_id = $worker_handbook[$tabel_number]['worker_object_id'];                            // ключ конкретного работника
                    $sensor_id = $sensor_handbook[$net_id]['sensor_id'];                                                // ключ привязываемого сенсора

                    // получаем тип привязки лампы по сенсору
                    if (isset($type_lamp[$sensor_id])) {
                        $type_relation_sensor = $type_lamp[$sensor_id];
                    } else {
                        $type_relation_sensor = 1;
                    }
                    // проверяем необходимость изменения привязки
                    if (
                        isset($last_worker_sensor_handbook[$sensor_id])
                        and $last_worker_sensor_handbook[$sensor_id]['worker_id'] == $worker_id

                    ) {
                        $count_releation_lamp['Уже были привязаны']++;
                    } else {
                        $log->addLog("Надо привязать лампу $net_id/" . $strata['net_id'] . " к работнику $worker_id с табельным номером $tabel_number.");

                        $count_releation_lamp['Надо привязать']++;
                        // проверяем наличие у работника worker_object
                        if (!$worker_object_id) {
                            $log->addLog("не было конкретного работника. Начал создавать");
                            $new_worker_object = new WorkerObject();
                            $new_worker_object->id = $worker_id;
                            $new_worker_object->worker_id = $worker_id;
                            $new_worker_object->object_id = 25;
                            $new_worker_object->role_id = 9;
                            if ($new_worker_object->save()) {
                                $new_worker_object->refresh();
                                $worker_object_id = $new_worker_object->id;
                                $log->addLog("Конкретного работника успешно создал");
                            } else {
                                $log->addData($new_worker_object->errors, '$new_worker_object->errors', __LINE__);
                                throw new Exception("Ошибка создания WorkerObject конкретного работника $worker_id");
                            }
                        }

                        // если у работника нет конкретного параметра, то надо его созадть иначе просто берем его
                        if (!isset($worker_parameters[$worker_object_id])) {
                            $new_worker_parameter = new WorkerParameter();
                            $new_worker_parameter->parameter_type_id = 2;
                            $new_worker_parameter->parameter_id = 83;
                            $new_worker_parameter->worker_object_id = $worker_object_id;
                            if ($new_worker_parameter->save()) {
                                $new_worker_parameter->refresh();
                                $worker_parameter_id = $new_worker_parameter->id;
                                $log->addLog("Параметр конкретного работника 83/2 создан");
                            } else {
                                $log->addData($new_worker_parameter->errors, '$new_worker_parameter->errors', __LINE__);
                                throw new Exception("Ошибка создания WorkerParameter конкретного параметра 83/2 работника $worker_object_id");
                            }
                        } else {
                            $worker_parameter_id = $worker_parameters[$worker_object_id]['id'];
                        }

                        // создаем массив для массовой вставки привязки
                        $batch_relation_item['worker_parameter_id'] = $worker_parameter_id;
                        $batch_relation_item['sensor_id'] = $sensor_id;
                        $batch_relation_item['date_time'] = $today;
                        $batch_relation_item['type_relation_sensor'] = $type_relation_sensor;
                        $batch_relation_array[] = $batch_relation_item;
                    }
                } else {
                    $strata_error_list['табельный номер'] = $tabel_number;
                    $strata_error_list['сетевой адрес'] = $net_id;
                    $strata_error_lists[] = $strata_error_list;
                }
            }

            // массово вставляем в БД привязку если она требуется
            if (isset($batch_relation_array)) {
                $result_batch_insert = Yii::$app->db->createCommand()
                    ->batchInsert('worker_parameter_sensor',
                        [
                            'worker_parameter_id',
                            'sensor_id',
                            'date_time',
                            'type_relation_sensor'
                        ], $batch_relation_array)//массовая вставка в БД
                    ->execute();
                if ($result_batch_insert != 0) {
                    $log->addLog("Успешное сохранение списка привязок");
                } else {
                    throw new Exception('Ошибка при добавлении списка привязок');
                }

                $log->addLog("Массово вставил в БД привязок");
            }
            $worker_cache_controller = new WorkerCacheController();
            $worker_cache_controller->delSensorWorker("*");
            $log->addLog("Удалил кеш привязок сенсоров и работников");
            $worker_cache_controller->initSensorWorker();
            $log->addLog("Заполнил кеш привязок сенсоров и работников");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание метода");
        $log->addData($count_releation_lamp, 'Статистика привязанных/не привязанных ламп', __LINE__);
        $log->addData($double_stuff_number, 'Дубли табельных номеров в Страте', __LINE__);
        $log->addData($worker_without_object, 'Работники без привязки к конкретному объекту (worker_object)', __LINE__);
        $log->addData($strata_error_lists, 'В БД АМИКУМ НЕТ ТАКИХ СВЯЗОК СТРАТЫ', __LINE__);
        $log->addData($net_id_without_tabel_number, 'Сетевые адреса без табельных номеров', __LINE__);
        $log->addData($net_id_null, 'Сетевые адреса 0', __LINE__);

        $log->saveLogSynchronization();

        $result_main = array_merge(['Items' => $result], $log->getLogAll());
        if (!$type_bind or $type_bind == 'browser') {
            Yii::$app->response->format = Response::FORMAT_JSON;
            Yii::$app->response->data = $result_main;
        } else {
            return $result_main;
        }
    }

    // actionLoadFileBindLamp - Метод для загрузки файла на сервер
    public function actionLoadFileBindLamp()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        try {
            $warnings[] = "loadFileBindLamp. Начало метода";
            if (PHP_OS == "Linux") {
//                $pathFile = "/var/www/html/amicum/backend/web/log/bindlamp.csv";
                $dirname = "/var/www/html/amicum/backend/web/log/";
            } else {
//                $pathFile = "C:\\xampp\\htdocs\\bindlamp.csv";
                $dirname = "C:\\xampp\\htdocs\\";
            }
            if (isset($_FILES) && $_FILES['lampfile']['error'] == 0) {
                $destiation_dir = $dirname . "bindlamp.csv";
                // $destiation_dir = dirname("C:\\xampp\\htdocs\\amicum_advanced\\") ."bindlamp.csv";                                                    //для windows
                move_uploaded_file($_FILES['lampfile']['tmp_name'], $destiation_dir);
                $warnings[] = "loadFileBindLamp. Файл загружен $destiation_dir";
            } else {
                throw new Exception("loadFileBindLamp. Файл не загружен");
            }
        } catch (Exception $e) {
            $status = 0;
            $errors[] = "loadFileBindLamp. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = $result_main;
        return $result_main;
    }


    //actionRestartStrata - метод по перезапуску страты
    //перезупск ССД происходит в течения минуты
    //пример вызова: http://10.36.59.8/admin/serviceamicum/amicum-service/restart-strata
    public function actionRestartStrata()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        try {

            $warnings[] = 'actionRestartStrata начало';
            $date = Assistant::GetDateTimeNow();
            $ip = Yii::$app->request->userIP;
            exec('echo Был перезапуск в : ' . $date . ' с адреса : ' . $ip . ' >> /var/www/html/amicum/script_amicum/state_restart_strata.txt');
            exec('echo 1 >> /var/www/html/amicum/script_amicum/state_restart_strata.txt');
            $warnings[] = "История:";
            $warnings[] = file_get_contents('/var/www/html/amicum/script_amicum/state_restart_strata.txt');

        } catch (Exception $e) {
            $status = 0;
            $errors[] = "actionRestartStrata. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * actionAutoUnbindLamp - метод отвязки светильников по уволенным работникам
     * пример вызова: 127.0.0.1/admin/serviceamicum/amicum-service/auto-unbind-lamp?browser=true
     * алгоритм:
     * 1. Получаем список людей с привязанными светильниками
     * 2. Получаем текущий статус работника
     * 3. Проверяем, если статус у работника в таблице worker значение поля date_and меньше текущего то ищем у этого работника светильник
     *  3.1 Если есть светильник то отвязать и писать логи в таблицы синхронизации
     */
    public static function actionAutoUnbindLamp($browser = false)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionAutoUnbindLamp");

        try {
            $date_time_now = date('Y-m-d', strtotime(Assistant::GetDateNow()));                        // время начала выполнения метода
            $log->addLog("Начало выполнения метода");

            /**
             * Получаем список людей с привязанными светильниками
             */
            // Получаем последнюю регулярную лампу у работника
            $regulations_lamp = (new Query())
                ->select([
                    'worker_object_id', 'worker_parameter_id'
                ])
                ->from('view_worker_parameter_sensor_maxDate_regular_with_workers')
                ->innerJoin('worker', 'worker.id = view_worker_parameter_sensor_maxDate_regular_with_workers.worker_object_id')
                ->where("worker.date_end < '$date_time_now' and view_worker_parameter_sensor_maxDate_regular_with_workers.sensor_id != -1")
                ->all();


            $log->addLog("Получил последнюю регулярную лампу у работников");

            // Получаем последнюю резервную лампу у работника
            $reservs_lamp = (new Query())
                ->select([
                    'worker_object_id', 'worker_parameter_id'
                ])
                ->from('view_worker_parameter_sensor_maxDate_reserve_with_workers')
                ->innerJoin('worker', 'worker.id = view_worker_parameter_sensor_maxDate_reserve_with_workers.worker_object_id')
                ->where("worker.date_end < '$date_time_now' and view_worker_parameter_sensor_maxDate_reserve_with_workers.sensor_id != -1")
                ->all();

            $array_dismissed_workers = [];

            foreach ($regulations_lamp as $worker) {
                $array_dismissed_workers[$worker['worker_object_id']] = $worker;
            }

            foreach ($reservs_lamp as $worker) {
                $array_dismissed_workers[$worker['worker_object_id']] = $worker;
            }

            $log->addLog("Получил последнюю резервную лампу у работников");

            $data_to_bach_insert = array();
            /**
             * готовим массив на массовую вставку изминения изначения у работников таблице worker_parameter_sensor
             */
            $log->addData(count($array_dismissed_workers), 'count_$array_dismissed_workers', __LINE__);

            foreach ($array_dismissed_workers as $dismissed_worker) {
                $worker_object = $dismissed_worker['worker_object_id'];
                $warnings[] = "У работника $worker_object (worker_object_id) отвязаем лампу";
                $data_to_bach_insert[] = array(
                    'worker_parameter_id' => $dismissed_worker['worker_parameter_id'],
                    'sensor_id' => -1,
                    'date_time' => date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow())),
                    'type_relation_sensor' => 1,
                );
                $data_to_bach_insert[] = array(
                    'worker_parameter_id' => $dismissed_worker['worker_parameter_id'],
                    'sensor_id' => -1,
                    'date_time' => date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow() . '+ 1 seconds')),
                    'type_relation_sensor' => 0,
                );
            }
            unset($array_dismissed_workers);

            $log->addLog("Подготовил массив для массовой вставки в таблицу worker_parameter_sensor");

            if ($data_to_bach_insert) {
                $builder_data_to_db = Yii::$app->db->createCommand()->batchInsert('worker_parameter_sensor', ['worker_parameter_id', 'sensor_id', 'date_time', 'type_relation_sensor'], $data_to_bach_insert)->execute();
                if (!$builder_data_to_db) {
                    throw new Exception('Массовая вставка не удалась в таблицу worker_parameter_sensor');
                }

                /**
                 * обновляем кэш приязок
                 */
                $worker_cache_controller = new WorkerCacheController();
                $response = $worker_cache_controller->delSensorWorker("*");
                if (!$response) {
                    $log->addError("Метод очистки приязок в кэше вренул ошибку (false)", __LINE__);
                }
                $response = $worker_cache_controller->initSensorWorker();
                if (!$response) {
                    $log->addError("Метод инициализации списка сенсоров привязанных к работникам вренул ошибку (false)", __LINE__);
                }
                $log->addLog("Заполнил кеш воркеров");
                unset($response);
                unset($data_to_bach_insert);
                $log->addLog("Вставил/обновил данные в таблицу таблицу worker_parameter_sensor");
            } else {
                $log->addLog("Нет записей для обновления");
            }

            $log->addLog("Окончание метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->saveLogSynchronization();
        if ($browser) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
        } else {
            return array_merge(['Items' => $result], $log->getLogAll());
        }
    }

    /**
     * actionSendDashBoardWebSocketArray - тестовый метод отправки тестового сообщения пользователям интерактивного рабочего стола
     *
     * @example 127.0.0.1/admin/serviceamicum/amicum-service/send-dash-board-web-socket-array
     */
    public static function actionSendDashBoardWebSocketArray()
    {
        $log = new LogAmicumFront("actionSendDashBoardWebSocketArray", true);
        $client = null;
        $result = null;

        try {
            $log->addLog("Начало выполнения метода");
            for ($i = 0; $i < 1000000; $i++) {
                $socket = new AmicumWebSocketClient();
//                $log->addData($socket->getSourceClientId(), '$sourceClientId', __LINE__);
                $socket->sendDataBroadcast("Привет");

                $socket->sendDataClients(12, ["PersonnelShifts"], 170);
//                $socket->disconnect();
//                $socket->close();
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog('Окончание метода');

        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //формат возвращаемых данных json методом yii2
        Yii::$app->response->data = array_merge(["Items" => $result], $log->getLogAll());;
    }

}