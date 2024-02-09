<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers;
//ob_start();

use backend\controllers\Assistant as BackendAssistant;
use backend\controllers\cachemanagers\EdgeCacheController;
use backend\controllers\cachemanagers\EquipmentCacheController;
use backend\controllers\cachemanagers\GasCacheController;
use backend\controllers\cachemanagers\LogCacheController;
use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\cachemanagers\ServiceCache;
use backend\controllers\cachemanagers\WorkerCacheController;
use backend\controllers\const_amicum\EventEnumController;
use backend\controllers\const_amicum\ParamEnum;
use backend\controllers\const_amicum\StatusEnumController;
use backend\controllers\horizon\HorizonController;
use backend\controllers\queuemanagers\RabbitController;
use backend\controllers\queuemanagers\RedisQueueController;
use backend\controllers\queuemanagers\SynchronizationJobController;
use backend\controllers\serviceamicum\MigrationDbController;
use backend\controllers\sms\SmsSender;
use backend\models\StrataActionLog;
use backend\models\StrataPackageSource;
use backend\models\UserAccessLog;
use backend\models\UserActionLog;
use backend\models\WsLog;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\handbooks\InjunctionController;
use frontend\controllers\system\LogAmicumFront;
use frontend\controllers\WebsocketController;
use frontend\controllers\XmlController;
use frontend\models\AmicumStatistic;
use frontend\models\AmicumSynchronization;
use frontend\models\BpdPackageInfo;
use frontend\models\ExaminationAnswer;
use frontend\models\StrataPackageInfo;
use frontend\models\WorkerObject;
use ModbusTcpClient\Network\BinaryStreamConnection;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use ModbusTcpClient\Packet\RtuConverter;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;
use yii\web\Response;


class TestController extends Controller
{

    // actionGetJournalSynchronization          -  метод получения журнала синхронизации за период
    // actionGetJournalStatistic                -  метод получения журнала работы метода расчета статистики за период
    // actionGetStrataActionLog                 -  метод получения журнала работы ССД Strata за период
    // actionGetUserAccessLog                   -  метод получения журнала авторизаций пользователей АМИКУМ за период
    // actionGetUserActionLog                   -  метод получения журнала действий пользователей АМИКУМ за период
    // actionGetWsLog                           -  метод получения журнала вебсокет сервера за период
    // actionGetStrataPackageInfo               -  метод получения журнала обработанных пакетов ССД Strata за период
    // actionGetStrataPackageSource             -  метод получения журнала всех пакетов ССД Strata за период
    // actionGetBpdPackageInfo                  -  метод получения журнала всех пакетов ССД БПД за период
    // actionRecoveryPackageStrata              -  метод восстановления пакетов Strata за определенный период
    // actionRemoveSensor                       -  Метод удаления сенсоров которые не стоят на схеме но висят в кеше

    // actionSensorRunInit                      - метод инициализации кеша сенсоров на основе обычных ключей
    // actionSensorRunInitHash                  - метод инициализации кеша сенсоров на основе хэш
    // actionTestStrata                         - метод проверки работы методов расшифровки пакетов страта
    // actionTestStrataRegistrationPacket       - метод тестирования расшифровки пакетов страта

    // actionCallSyncMethod                     - метод ля вызова метод переноса данных с новыми айдишниками
    // actionSensorMineRunInitHash              - метод инициализации кеша шахты сенсоров на основе хэш
    // actionMultiGetParameterValueHash         - метод получения данных из кеша параметров сенсора ХЭШ
    // actionDelInSensorMineHash                - метод удаления сенсоров из кеша хэш
    // actionGetSensorMineHash                  - метод получения списка сенсоров по шахте из кеша хэш
    // actionGetWorkerMineHash                  - метод получения списка работников по шахте из кеша хэш
    // actionDelParameterValueHash              - метод удаления сенсоров из кеша хэш
    // actionWorkerMultiGetParameterValueHash   - метод получения данных из кеша параметров рабоника ХЭШ
    // actionInitWorkerParameterValueHash       - метод инициализации параметров работника ХЭШ
    // actionInitWorkerMineHash                 - метод инициализации главного кеша работников
    // actionInitSensorNetwork                  - метод получения данных из кеша параметров сенсора ХЭШ
    // actionMoveWorkerMineInitCache            - метод перенлса людей между шахтами
    // actionGetGatewayParameterByIpHash        - метод получения информации по апйпи шлюза
    // actionGetPlaceByTitle                    - метод получения айдишника места по названию
    // actionConvertFromJson                    - метод разконвертирует json в читаемый формат
    // actionGetUserIp                          - метод получения ip пользователя с которого пришел запрос
    // actionTestSetRedis                       - тестовый метод для проверки укладования значения в редисах
    // actionPullRabbitQueueEmployee            - метод прослушивания сообщений в очереди RabbitMQ Employee
    // actionPullRabbitQueueDivision            - метод прослушивания сообщений в очереди RabbitMQ Division
    // actionPullRabbitQueueAd                  - метод добавления сообщения в очередь RabbitMQ AD
    // actionPushRedisQueue                     - метод отправки данных в очередь RabbitMQ
    // actionPushRabbitAd                       - метод отправки данных в очередь AD
    // actionTestJsonRecoveryBy1C               - метод тестирования восстановления строки json после получения с 1С
    // actionTestFirebird                       - метод тестирования подключения к Firebird
    // actionSendMessage                        - метод для тестирования отправки текстовых сообщений с браузера
    // actionGetShiftDateNum                    - Метод получения смены по дате

    // actionGetSizeRabbitQuery                 - Метод получения размера очереди
    // actionPopRabbitQuery                     - Метод получения очереди сообщений по расчету времени нахождения в шахте
    // actionPushToQuery                        - Метод отправки тестового сообщения

    // actionGetWorkingHoursByWorkerId          - Метод сохранения пакета времени нахождения человека на смене
    // actionSetWorkingHoursByWorkerId          - Метод сохранения пакета времени нахождения человека на смене
    // actionCleanWorkingHoursByWorkerId        - Метод очистки пакета времени нахождения человека на смене

    public $post;
    public static $sensor_basic_controller;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->post = Assistant::GetServerMethod();
        self::$sensor_basic_controller = new SensorBasicController();
    }

    public static function actionEventTest()
    {
        $a['test'] = 2.22;
        Assistant::VarDump(json_encode($a));
        Assistant::VarDump(json_encode((object)$a));

        /*$event_cache_controller = new EventCacheController();

        //$result = $event_cache_controller->setEvent(290, 1,1,3,4,5,6,7);

        //$result = $event_cache_controller->getEvent(290, 1,2);

        $result = $event_cache_controller->getEventsList();

        \backend\controllers\Assistant::VarDump($result);*/
    }


    public function actionSet()
    {
        Yii::$app->cache->set($this->post['key'], $this->post['val']);
        echo Yii::$app->cache->get($this->post['key']);
    }


    public function actionGet()
    {
//		$count = \Yii::$app->redis->LLEN("SensorMine_290:*");
//		$res = \Yii::$app->redis->lrange("SensorMine_290:*", 0, $count );
        Assistant::PrintR(Yii::$app->cache->get('EdgeMine_108693'));
//		echo Yii::$app->cache->get('EdgeMine_290');
    }


    public static function SetCache($sensors = null)
    {
        Assistant::PrintR($sensors->mines->value);
//		$val = Yii::$app->cache->amicum_get('TestCache');
//		Yii::$app->cache->amicum_set("TestCache", $val."<br>".$sensors['value'].$sensors['value2']);
    }

    public function actionGetSmsSendingList()
    {
        var_dump(XmlController::getSmsSendingList(22409));
    }

    // http://amicum.advanced/admin/test/worker-cache
    public function actionWorkerCache()
    {
        Assistant::VarDump(WorkerMainController::getWorkerInfoBySensorId(666));
        Assistant::VarDump(WorkerMainController::getWorkerParameterLastValue(2914215, 269, 2));

    }

    // тестирование верности заполнения отчета движения между зонами
    // Разработал Якимов М.Н.
    public function actionSymmaryReport()
    {
        //15009 - ламповая
        //6523 - надшахтное здание
        //6183 - капитальная выработка
        //6184 - капитальная выработка
        //6185 - капитальная выработка
        //6751 - забой
        //6194 - забой
//        $date_time=\backend\controllers\Assistant::GetDateNow();
//        $date_time="2019-06-16 02:00:00.00000"; //зарядился
//        $result_main=WorkerMainController::addWorkerCollection(1011221,16,15009,$date_time);
//        $date_time="2019-06-16 02:10:00.00000"; //в надшахтном здании
//        $result_main=WorkerMainController::addWorkerCollection(1011221,16,6523,$date_time);
//        $date_time="2019-06-16 02:20:00.00000"; //в шахте
//        $result_main=WorkerMainController::addWorkerCollection(1011221,16,6183,$date_time);
//        $date_time="2019-06-16 02:30:00.00000";  //в шахте
//        $result_main=WorkerMainController::addWorkerCollection(1011221,16,6184,$date_time);
//        $date_time="2019-06-16 02:40:00.00000";  //в шахте
//        $result_main=WorkerMainController::addWorkerCollection(1011221,16,6185,$date_time);
//        $date_time="2019-06-16 02:50:00.00000"; //в забое начало
//        $result_main=WorkerMainController::addWorkerCollection(1011221,16,6751,$date_time);
//        $date_time="2019-06-16 03:00:00.00000"; // в забое продолжение
//        $result_main=WorkerMainController::addWorkerCollection(1011221,16,6751,$date_time);
//        $date_time="2019-06-16 03:10:00.00000"; //вышел с забоя идет по выработке
//        $result_main=WorkerMainController::addWorkerCollection(1011221,16,6185,$date_time);
//        $date_time="2019-06-16 03:20:00.00000"; //зашел в забой
//        $result_main=WorkerMainController::addWorkerCollection(1011221,16,6194,$date_time);
//        $date_time="2019-06-16 03:30:00.00000"; // работал в забое
//        $result_main=WorkerMainController::addWorkerCollection(1011221,16,6194,$date_time);
//        $date_time="2019-06-16 03:40:00.00000"; // вышел из забоя
//        $result_main=WorkerMainController::addWorkerCollection(1011221,16,6185,$date_time);
//        $date_time="2019-06-16 03:50:00.00000"; // шел по выработке
//        $result_main=WorkerMainController::addWorkerCollection(1011221,16,6184,$date_time);
//        $date_time="2019-06-16 04:00:00.00000"; //разрядился
//        $result_main=WorkerMainController::addWorkerCollection(1011221,16,15009,$date_time);
//
//
//        $date_time=\backend\controllers\Assistant::GetDateNow();
//        $date_time="2019-06-16 02:00:00.00000"; //зарядился
//        $result_main=WorkerMainController::addWorkerCollection(1010134,16,15009,$date_time);
//        $date_time="2019-06-16 02:10:00.00000"; //в надшахтном здании
//        $result_main=WorkerMainController::addWorkerCollection(1010134,16,6523,$date_time);
//        $date_time="2019-06-16 02:20:00.00000"; //в шахте
//        $result_main=WorkerMainController::addWorkerCollection(1010134,16,6183,$date_time);
//        $date_time="2019-06-16 02:30:00.00000";  //в шахте
//        $result_main=WorkerMainController::addWorkerCollection(1010134,16,6184,$date_time);
//        $date_time="2019-06-16 02:40:00.00000";  //в шахте
//        $result_main=WorkerMainController::addWorkerCollection(1010134,16,6185,$date_time);
//        $date_time="2019-06-16 03:40:00.00000"; // вышел из забоя
//        $result_main=WorkerMainController::addWorkerCollection(1010134,16,6185,$date_time);
//        $date_time="2019-06-16 03:50:00.00000"; // шел по выработке
//        $result_main=WorkerMainController::addWorkerCollection(1010134,16,6184,$date_time);
//        $date_time="2019-06-16 04:00:00.00000"; //разрядился
//        $result_main=WorkerMainController::addWorkerCollection(1010134,16,15009,$date_time);
//        $date_time=\backend\controllers\Assistant::GetDateNow();
//
//        $date_time="2019-06-16 02:00:00.00000"; //зарядился
//        $result_main=WorkerMainController::addWorkerCollection(1010225,16,15009,$date_time);
//        $date_time="2019-06-16 02:10:00.00000"; //в надшахтном здании
//        $result_main=WorkerMainController::addWorkerCollection(1010225,16,6523,$date_time);
//
//        $date_time="2019-06-16 03:50:00.00000"; // шел по выработке
//        $result_main=WorkerMainController::addWorkerCollection(1010225,16,6184,$date_time);
//        $date_time="2019-06-16 04:00:00.00000"; //разрядился
//        $result_main=WorkerMainController::addWorkerCollection(1010225,16,15009,$date_time);
        $result_main = \backend\controllers\SensorMainController::GetListOpcParameters();
        //$result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //формат возвращаемых данных json методом yii2
        Yii::$app->response->data = $result_main;
    }

    public function actionGetOrSetSensorByNetworkId()
    {
        try {
            $response = SensorMainController::getOrSetSensorByNetworkId(11);
            $warnings[] = $response['warnings'];
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'saveLocationPacket.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        $warnings = 'saveLocationPacket. Закончил метод';
        $result_main = array('Items' => $response, 'warnings' => $warnings);

        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //формат возвращаемых данных json методом yii2
        Yii::$app->response->data = $result_main;
    }


    public function actionGetDateNow()
    {
        $result_main = BackendAssistant::GetDateNow();
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //формат возвращаемых данных json методом yii2
        Yii::$app->response->data = $result_main;
    }

    public function actionGetWorkerSensor()
    {
        $result_main = (new WorkerCacheController())->getSensorWorker("*", 2086175);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //формат возвращаемых данных json методом yii2
        Yii::$app->response->data = $result_main;
    }

    public function actionPhpInfo()
    {
        phpinfo();
    }

    /**
     * Название метода: actionRemoveSensor - Метод удаления сенсоров которые не стоят на схеме но висят в кеше
     * Метод удаления сенсоров которые не стоят на схеме но висят в кеше
     * @package backend\controllers
     *
     * Входные обязательные параметры:
     * mine_id - идентификатор шахты
     * Входные необязательные параметры
     *
     * @see
     * @example
     *
     * @author fidchenkoM
     * Created date: on 28.06.2019 16:33
     * @since ver
     */
    public function actionRemoveSensor()
    {
        $post = Assistant::GetServerMethod();
        $sensors = (new SensorCacheController())->getSensorMineHash($post['mine_id']);
        $status = 1;
        $result = array();
        $warnings = array();
        $errors = array();
        foreach ($sensors as $sensor) {
            $object_type_id = $sensor['object_type_id'];
            $sensor_id = $sensor['sensor_id'];
            $parameter_type_id = 2;
            if ($object_type_id == 22 || $object_type_id == 116 || $object_type_id == 95 || $object_type_id == 96 || $object_type_id == 28) {
                $parameter_type_id = 1;
            }
            $sensor_mine_id = (new SensorCacheController())->getParameterValueHash($sensor_id, 346, $parameter_type_id);
            if ($sensor_mine_id['value'] == $post['mine_id']) {
                $sensor_edge_id = (new SensorCacheController())->getParameterValueHash($sensor_id, 269, $parameter_type_id);
//                if($sensor_edge_id != false)
//                {
                $edges = (new EdgeCacheController())->getEdgeScheme($post['mine_id'], $sensor_edge_id['value']);
                if ($edges == false) {
                    $result_delete_sensor = SensorMainController::DeleteSensorFromShema($post['mine_id'], $sensor_id);//удаляем сеноср со схемы
                    $status *= $result_delete_sensor['status'];
                    $warnings[] = $result_delete_sensor['warnings'];
                    $errors[] = $result_delete_sensor['errors'];
                    $result[] = $sensor;
                }
//                }
            }
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //формат возвращаемых данных json методом yii2
        Yii::$app->response->data = $result_main;
    }

    /**
     * lamp_id - айди светильника
     * lamp_edge_id - эдж, на котором находится свет
     * lamp_xyz - коорды света
     * mine_id - айди шахты
     * lamp_gas_value - значение ch4 на свете
     *
     * @example
     * 127.0.0.1/admin/test/test-gas-diff?lamp_id=123&lamp_edge_id=15009&lamp_xyz=&mine_id=290&lamp_gas_value=3
     */
    public function actionTestGasDiff()
    {
        $post = Assistant::GetServerMethod();
        $sensor_id = $post['lamp_id'];
        $sensor_edge_id = $post['lamp_edge_id'];
        $sensor_xyz = $post['lamp_xyz'];
        $mine_id = $post['mine_id'];
        $sensor_ch4_value = $post['lamp_gas_value'];
        $response = OpcController::actionCalcGasValueStaticMovement(
            $sensor_id,
            $sensor_edge_id,
            $sensor_xyz,
            $mine_id,
            $sensor_ch4_value
        );

        //Assistant::VarDump($response);
    }
    // тестовый метод для проверки подключения к бд oracle
    // пример вызова: 10.36.55.8/admin/test/conn-oracle-db
    public function actionConnOracleDb()
    {
        $result = null;
        $errors = array();
        $status = 1;
        $warnings = array();
        try {
            //$conn_oracle = oci_connect('Amicum_PS', 'y62#yZfl$U$e', '(DESCRIPTION =(ADDRESS = (PROTOCOL = TCP)(HOST =' . SKUD_HOST_NAME . ')(PORT = 1521))(CONNECT_DATA =(SERVER =default)(SERVICE_NAME =' . SKUD_SERVICE_NAME . ')))', 'AL32UTF8');
            $conn_oracle = oci_connect('AMICUM_CON', 'vRqrWIOlStg7p24IAaR8', '(DESCRIPTION =(ADDRESS = (PROTOCOL = TCP)(HOST =10.120.28.198)(PORT = 1521))(CONNECT_DATA =(SERVER =default)(SERVICE_NAME =BATCHPROD.severstal.severstalgroup.com)))', 'AL32UTF8');
            if (!$conn_oracle) {
                $error = oci_error();
                trigger_error(htmlentities($error['message'], ENT_QUOTES), E_USER_ERROR);
                $result = 'Connection failed';
            } else {
                $result = "Connetion";
                $warnings[] = ob_get_status();
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'Connection failed: ' . $exception->getMessage();
        }
        $result_main = array('status' => $status, 'Item' => $result, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;

    }

    public function actionWs()
    {
        $event_journal_send = array(
            'event_journal_id' => 1472736,
            'event_id' => 22409,
            'event_title' => "Превышение CH4 со светильника",
            'status_checked' => 0,
            'event_date_time' => date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow())),
            'event_date_time_format' => date('d.m.Y H:i:s', strtotime(BackendAssistant::GetDateNow())),
            'sensor_id' => 141538,
            'sensor_title' => "CH4 CH2_KUSH42",
            'edge_id' => 22616,
            'place_id' => 6323,
            'status_id' => 38,
            'sensor_value' => "1.2",
            'kind_reason_id' => null,
            'status_date_time' => date('d.m.Y H:i:s', strtotime(BackendAssistant::GetDateNow())),
            'event_status_id' => null,
            'duration' => null,
            'statuses' => [],
            'gilties' => [],
            'operations' => [],
        );
        $response = WebsocketController::SendMessageToWebSocket('addNewEventJournal', $event_journal_send);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $response;
    }

    public function actionGetByNet()
    {
        $residue = "";
        $sensor_id = 1;
        $worker_id = '';
        $Items = array();
        $errors = array();
        $status = 1;
        $warnings = array();
        $date_time = "";
        $down_value = "";
        try {


            // $network_id = 0;
            $post = Assistant::GetServerMethod();
            $response = SensorMainController::getOrSetSensorByNetworkId($post['net_id']);
            if ($response['status'] == 1) {
                if ($response['sensor_id'] === false) {
                    $warnings[] = "sensor_id = false";
                } else {
                    $sensor_id = $response['sensor_id'];
                    $worker_sensor = WorkerMainController::getWorkerInfoBySensorId($sensor_id);
                    //         $worker_cache_controller = (new WorkerCacheController());
//                    $workerPar = $worker_cache_controller->getSensorWorker($sensor_id);
                    if ($worker_sensor !== false) {
                        $worker_id = $worker_sensor['worker_id'];
                        $worker_cache_controller = new WorkerCacheController();
                        //$warnings[] = 'saveLocationPacketWorkerParameters. получаем данные с кеша по всему воркеру';
                        $worker_parameter_value_list_cache = $worker_cache_controller->multiGetParameterValueHash($worker_id, '*', '*');
                        $down_value = $worker_parameter_value_list_cache['value'];
                        $time_fom_cache = $worker_parameter_value_list_cache['date_time'];

                        $diff = strtotime(BackendAssistant::GetDateNow()) - strtotime($time_fom_cache);
                        if ($diff >= 1800) {
                            $residue = strtotime("2019-11-14 3:08:01.35424") - strtotime("2019-11-14 5:00:01.354240");
                            if ($residue >= 1800) {
                                $warnings['move_to_cache'] = true;
                            }
                        }
                    } else {
                        $down_value = "не найден";
                        $worker_parameter_value_list_cache = "";
                        $errors[] = "actionGetByNet. worker_id найден";
                    }
                }
            }
        } catch (Exception $exception) {
            $status = 0;
            $errors[] = $exception->getMessage();
        }

        $result = array('status' => $status, 'sensor_id' => $sensor_id, '$worker_parameter_value_list_cache' =>
            $worker_parameter_value_list_cache, 'value' => $down_value, 'residue' => $residue, 'warnings' => $warnings, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;

    }

    public function GetSensorid()
    {

        $sensor_par_id = 123841;
        $sensor_cache_controller = (new SensorCacheController());
        $eq_cache_controller = (new EquipmentCacheController());
        $sensor_cache = $sensor_cache_controller->multiGetSenParSenTag($sensor_par_id, '*');
        $sensor_id_id = $sensor_cache[0]['sensor_id'];
        $eq_id = $eq_cache_controller->getSensorEquipmentParameter($sensor_id_id, '*', '*');
        $check = $eq_cache_controller->getEquipmentMineByEquipment($eq_id['equipment_id']);

        $object_values = (new Query())
            ->select([
                'equipment_parameter_value.equipment_parameter_id as object_parameter_id',
                'equipment_parameter_value.date_time as date_time',
                'equipment_parameter_value.value'
            ])
            ->from('equipment_parameter')
            ->innerJoin('equipment_parameter_value', 'equipment_parameter_value.equipment_parameter_id = equipment_parameter.id')
            ->where(['equipment_parameter.equipment_id' => $eq_id])
            ->andWhere(['parameter_id' => 3, 'parameter_type_id' => 164])
            ->andWhere(['BETWEEN', 'date_time', '2019-12-04 04:20:11.377300', '2019-12-04 07:28:11.377300'])
            ->orderBy(['date_time' => SORT_ASC])
            ->all();
        $warnings[] = array();
        foreach ($object_values as $object) {
            $warnings[] = $object;
        }
        $result = array('sensor_id' => $sensor_id_id, '$eq_id' => $eq_id, 'getEquipmentMineByEquipment' => $check, '$object_values' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }


    public function actionCheckGas()
    {
        $Item = array();
        $warnings = array();
        $errors = array();

        try {

            /**
             * получить список зон с датчиками
             */
            $gas_cache_controller = (new GasCacheController());
            $zone_edge_mine = $gas_cache_controller->getZonesEdgeMine(290);
            if ($zone_edge_mine) {
                $warnings[] = 'actionCalcGasValueStaticMovement. кеш списка зон сенсоров есть';
                //$warnings[] = $zone_edge_mine;
                //$warnings[] = $zone_edge_mine;
            } else {
                throw new Exception('actionCalcGasValueStaticMovement. кеш списка зон сенсоров пуст');
            }

            $date = BackendAssistant::GetDateNow();
            $sensor_cache_controller = (new SensorCacheController());
            $to_get_sensor_id = $sensor_cache_controller->multiGetParameterValueHash(597762, '*');
            $sensor_id = $to_get_sensor_id[0]['sensor_id'];
            if (isset($sensor_id)) {
                // $lamp_id = $sensor_cache_controller->getSensorMineBySensor()
                $event_main_controller = EventMainController::createCompareEvent(intval($sensor_id), 26777, 22411, "0", "2", $date, 44, 11, 290, 38, 24003, 24003);
                $warnings[] = $event_main_controller['warnings'];
                $warnings['status'] = $event_main_controller['status'];
                $errors[] = $event_main_controller['errors'];
                $Item['Items $event_main_controller'] = $event_main_controller['Items'];

            } else {
                $errors[] = "не найден сенсор айди";
            }
        } catch (Exception $ex) {
            $errors[] = __FUNCTION__ . $ex->getMessage();
        }
        $result = array('$warnings' => $warnings, 'errors' => $errors, '$Item' => $Item);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    // actionConvertFromJson - метод разконвертирует json в читаемый формат
    // 127.0.0.1/admin/test/convert-from-json?json=
    public function actionConvertFromJson()
    {
        $response = json_decode(Yii::$app->request->get()['json']);
        $result = json_decode($response);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    public function actionGas()
    {
        $response = EventMainController::createEventForWorkerGas(
            'worker',
            2914215,
            22409,
            '3.3',
            "2020-03-17 19:02:46.244513",
            44,
            99,
            290,
            38,
            '',
            "13613.49,-344.5,-7774.15",
            27637);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $response;
    }

    // actionGetUserIp - метод получения ip пользователя с которого пришел запрос
    // http://127.0.0.1/admin/test/get-user-ip
    public function actionGetUserIp()
    {
        $user_ip = Yii::$app->request->userIP;
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $user_ip;
    }

    public function actionModbusT()
    {

        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $method_name = "actionModbusT";                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();

        $startAddress = 2150;
        $quantity = 20;
        $slaveId = 1;

        $connection = BinaryStreamConnection::getBuilder()
            ->setPort(10002)
            ->setHost('192.168.88.62')
            ->setReadTimeoutSec(3) // increase read timeout to 3 seconds
            ->build();

        $tcpPacket = new ReadHoldingRegistersRequest($startAddress, $quantity, $slaveId);
        $rtuPacket = RtuConverter::toRtu($tcpPacket);


        try {
            $warnings[] = $method_name . ' Start';
            $binaryData = $connection->connect()->sendAndReceive($rtuPacket);

            $warnings[] = $binaryData;

            //$response = RtuConverter::fromRtu($binaryData);
            //   $warnings[] ='Parsed packet (in hex):     ' . $response->toHex();
            //$warnings[] ='Data parsed from packet (bytes):' . PHP_EOL;
            // $result[] = $response->getData();

        } catch (Exception $exception) {
            $errors[] = 'Exception';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getTraceAsString();
        } finally {
            $connection->close();
            $warnings[] = 'Connection closed';
            $warnings[] = $method_name . ' End';
        }
        $result_m = array('status' => $status, 'result' => $result, 'warnings' => $warnings, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_m;
    }

    // actionTestSetRedis - тестовый метод для проверки укладования значения в редисах
    public function actionTestSetRedis()
    {

        $this->sensor_cache = Yii::$app->redis_sensor;
        $sensor_cache_controller = (new SensorCacheController());
        $result_m = $sensor_cache_controller->amicum_rSetDebug($sensor_cache_controller->sensor_cache, 'test', 'value');
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_m;
    }
    /**
     * методы по получения информации с журналов ССД и прочее
     */

    // actionGetJournalSynchronization -  метод получения журнала синхронизации за период
    // входные параметры:
    //      date_time_start          - дата и время начала получения журнала
    //      date_time_end            - дата и время окончания получения журнала
    // выходные параметры:
    //
    // пример вызова: http://127.0.0.1/admin/test/get-journal-synchronization?date_time_start=2020-01-01%2010:00:00&date_time_end=2020-04-17%2012:00:00
    // разработчик: Якимов М.Н.
    // дата разработки 17.04.2020 года
    public static function actionGetJournalSynchronization()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
//            ini_set('max_execution_time', 6000000);
//            ini_set('memory_limit', "10500M");

            $warnings[] = "actionGetJournalSynchronization. Начал выполнять метод";
            $session = Yii::$app->session;
            $session->open();
            $result = array();
//            if (!isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
//                $errors[] = 'actionGetJournalSynchronization. Время сессии закончилось. Требуется повторный ввод пароля';
//                throw new \Exception("actionGetJournalSynchronization. Время сессии закончилось. Требуется повторный ввод пароля");
//            }
//            if (AccessCheck::checkAccess($session['sessionLogin'], 77)) {                                        //если пользователю разрешен доступ к функции
//                $warnings[] = "actionGetJournalSynchronization. Прав для выполнения достаточно";
//            } else {
//                throw new \Exception("actionGetJournalSynchronization. Недостаточно прав для совершения данной операции");
//            }

            $post = Assistant::GetServerMethod();                                                                   //получение данных от ajax-запроса

            if (isset($post['date_time_start']) && $post['date_time_start'] != '' and isset($post['date_time_end']) and $post['date_time_end'] != '') {                                                              //если передан id ветви
                $date_time_start = $post['date_time_start'];
                $date_time_end = $post['date_time_end'];
                $warnings[] = "actionGetJournalSynchronization. Получил входные параметры $date_time_start и $date_time_end";
            } else {
                throw new Exception("actionGetJournalSynchronization. Параметры не переданы");
            }

            $result = AmicumSynchronization::find()
                ->where("date_time_start>'" . $date_time_start . "'")
                ->andWhere("date_time_start<'" . $date_time_end . "'")
                ->asArray()
                ->all();


        } catch (Throwable $exception) {
            $errors[] = "actionGetJournalSynchronization. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "actionGetJournalSynchronization. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionGetJournalStatistic -  метод получения журнала работы метода расчета статистики за период
    // входные параметры:
    //      date_time_start          - дата и время начала получения журнала
    //      date_time_end            - дата и время окончания получения журнала
    // выходные параметры:
    //
    // пример вызова: http://127.0.0.1/admin/test/get-journal-statistic?date_time_start=2020-01-01%2010:00:00&date_time_end=2020-04-17%2012:00:00
    // разработчик: Якимов М.Н.
    // дата разработки 17.04.2020 года
    public static function actionGetJournalStatistic()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $warnings[] = "actionGetJournalStatistic. Начал выполнять метод";
            $session = Yii::$app->session;
            $session->open();
            $result = array();
//            if (!isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
//                $errors[] = 'actionGetJournalStatistic. Время сессии закончилось. Требуется повторный ввод пароля';
//                throw new \Exception("actionGetJournalStatistic. Время сессии закончилось. Требуется повторный ввод пароля");
//            }
//            if (AccessCheck::checkAccess($session['sessionLogin'], 77)) {                                        //если пользователю разрешен доступ к функции
//                $warnings[] = "actionGetJournalStatistic. Прав для выполнения достаточно";
//            } else {
//                throw new \Exception("actionGetJournalStatistic. Недостаточно прав для совершения данной операции");
//            }

            $post = Assistant::GetServerMethod();                                                                   //получение данных от ajax-запроса

            if (isset($post['date_time_start']) && $post['date_time_start'] != '' and isset($post['date_time_end']) and $post['date_time_end'] != '') {                                                              //если передан id ветви
                $date_time_start = $post['date_time_start'];
                $date_time_end = $post['date_time_end'];
                $warnings[] = "actionGetJournalStatistic. Получил входные параметры $date_time_start и $date_time_end";
            } else {
                throw new Exception("actionGetJournalStatistic. Параметры не переданы");
            }

            $result = AmicumStatistic::find()
                ->where("date_time_start>'" . $date_time_start . "'")
                ->andWhere("date_time_start<'" . $date_time_end . "'")
                ->all();


        } catch (Throwable $exception) {
            $errors[] = "actionGetJournalStatistic. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "actionGetJournalStatistic. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionGetStrataActionLog -  метод получения журнала работы ССД Strata за период
    // входные параметры:
    //      date_time_start          - дата и время начала получения журнала
    //      date_time_end            - дата и время окончания получения журнала
    // выходные параметры:
    //
    // пример вызова: http://127.0.0.1/admin/test/get-strata-action-log?date_time_start=2020-01-01%2010:00:00&date_time_end=2020-04-17%2012:00:00
    // разработчик: Якимов М.Н.
    // дата разработки 17.04.2020 года
    public static function actionGetStrataActionLog()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $warnings[] = "actionGetStrataActionLog. Начал выполнять метод";
            $session = Yii::$app->session;
            $session->open();
            $result = array();
//            if (!isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
//                $errors[] = 'actionGetStrataActionLog. Время сессии закончилось. Требуется повторный ввод пароля';
//                throw new \Exception("actionGetStrataActionLog. Время сессии закончилось. Требуется повторный ввод пароля");
//            }
//            if (AccessCheck::checkAccess($session['sessionLogin'], 77)) {                                        //если пользователю разрешен доступ к функции
//                $warnings[] = "actionGetStrataActionLog. Прав для выполнения достаточно";
//            } else {
//                throw new \Exception("actionGetStrataActionLog. Недостаточно прав для совершения данной операции");
//            }

            $post = Assistant::GetServerMethod();                                                                   //получение данных от ajax-запроса

            if (isset($post['date_time_start']) && $post['date_time_start'] != '' and isset($post['date_time_end']) and $post['date_time_end'] != '') {                                                              //если передан id ветви
                $date_time_start = $post['date_time_start'];
                $date_time_end = $post['date_time_end'];
                $warnings[] = "actionGetStrataActionLog. Получил входные параметры $date_time_start и $date_time_end";
            } else {
                throw new Exception("actionGetStrataActionLog. Параметры не переданы");
            }

            $result = StrataActionLog::find()
                ->where("date_time>'" . $date_time_start . "'")
                ->andWhere("date_time<'" . $date_time_end . "'")
                ->all();


        } catch (Throwable $exception) {
            $errors[] = "actionGetStrataActionLog. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "actionGetStrataActionLog. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionGetUserAccessLog -  метод получения журнала авторизаций пользователей АМИКУМ за период
    // входные параметры:
    //      date_time_start          - дата и время начала получения журнала
    //      date_time_end            - дата и время окончания получения журнала
    // выходные параметры:
    //
    // пример вызова: http://127.0.0.1/admin/test/get-user-access-log?date_time_start=2020-01-01%2010:00:00&date_time_end=2020-04-17%2012:00:00
    // разработчик: Якимов М.Н.
    // дата разработки 17.04.2020 года
    public static function actionGetUserAccessLog()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $warnings[] = "actionGetUserAccessLog. Начал выполнять метод";
            $session = Yii::$app->session;
            $session->open();
            $result = array();
//            if (!isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
//                $errors[] = 'actionGetUserAccessLog. Время сессии закончилось. Требуется повторный ввод пароля';
//                throw new \Exception("actionGetUserAccessLog. Время сессии закончилось. Требуется повторный ввод пароля");
//            }
//            if (AccessCheck::checkAccess($session['sessionLogin'], 77)) {                                        //если пользователю разрешен доступ к функции
//                $warnings[] = "actionGetUserAccessLog. Прав для выполнения достаточно";
//            } else {
//                throw new \Exception("actionGetUserAccessLog. Недостаточно прав для совершения данной операции");
//            }

            $post = Assistant::GetServerMethod();                                                                   //получение данных от ajax-запроса

            if (isset($post['date_time_start']) && $post['date_time_start'] != '' and isset($post['date_time_end']) and $post['date_time_end'] != '') {                                                              //если передан id ветви
                $date_time_start = $post['date_time_start'];
                $date_time_end = $post['date_time_end'];
                $warnings[] = "actionGetUserAccessLog. Получил входные параметры $date_time_start и $date_time_end";
            } else {
                throw new Exception("actionGetUserAccessLog. Параметры не переданы");
            }

            $result = UserAccessLog::find()
                ->where("date_time>'" . $date_time_start . "'")
                ->andWhere("date_time<='" . $date_time_end . "'")
                ->all();


        } catch (Throwable $exception) {
            $errors[] = "actionGetUserAccessLog. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "actionGetUserAccessLog. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionGetUserActionLog -  метод получения журнала действий пользователей АМИКУМ за период
    // входные параметры:
    //      date_time_start          - дата и время начала получения журнала
    //      date_time_end            - дата и время окончания получения журнала
    // выходные параметры:
    //
    // пример вызова: http://127.0.0.1/admin/test/get-user-action-log?date_time_start=2020-01-01%2010:00:00&date_time_end=2020-04-17%2012:00:00
    // разработчик: Якимов М.Н.
    // дата разработки 17.04.2020 года
    public static function actionGetUserActionLog()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
//        ini_set('max_execution_time', -1);
//        ini_set('memory_limit', "10500M");
        try {
            $warnings[] = "actionGetUserActionLog. Начал выполнять метод";
            $session = Yii::$app->session;
            $session->open();
            $result = array();
//            if (!isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
//                $errors[] = 'actionGetUserActionLog. Время сессии закончилось. Требуется повторный ввод пароля';
//                throw new \Exception("actionGetUserActionLog. Время сессии закончилось. Требуется повторный ввод пароля");
//            }
//            if (AccessCheck::checkAccess($session['sessionLogin'], 77)) {                                        //если пользователю разрешен доступ к функции
//                $warnings[] = "actionGetUserActionLog. Прав для выполнения достаточно";
//            } else {
//                throw new \Exception("actionGetUserActionLog. Недостаточно прав для совершения данной операции");
//            }

            $post = Assistant::GetServerMethod();                                                                   //получение данных от ajax-запроса

            if (isset($post['date_time_start']) && $post['date_time_start'] != '' and isset($post['date_time_end']) and $post['date_time_end'] != '') {                                                              //если передан id ветви
                $date_time_start = $post['date_time_start'];
                $date_time_end = $post['date_time_end'];
                $warnings[] = "actionGetUserActionLog. Получил входные параметры $date_time_start и $date_time_end";
            } else {
                throw new Exception("actionGetUserActionLog. Параметры не переданы");
            }

            $user_action_logs = UserActionLog::find()
                ->where("date_time>'" . $date_time_start . "'")
                ->andWhere("date_time<'" . $date_time_end . "'")
                ->all();
            foreach ($user_action_logs as $user_action_log) {
                $result[] = array(
                    'id' => $user_action_log['id'],
                    'date_time' => $user_action_log['date_time'],
                    'metod_amicum' => $user_action_log['metod_amicum'],
                    'duration' => $user_action_log['duration'],
                    'table_number' => $user_action_log['table_number'],
                    'post' => $user_action_log['post'],
                    'errors' => $user_action_log['errors'],
                    'result' => $user_action_log['result'],
                );
            }


        } catch (Throwable $exception) {
            $errors[] = "actionGetUserActionLog. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "actionGetUserActionLog. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionGetWsLog -  метод получения журнала вебсокет сервера за период
    // входные параметры:
    //      date_time_start          - дата и время начала получения журнала
    //      date_time_end            - дата и время окончания получения журнала
    // выходные параметры:
    //
    // пример вызова: http://127.0.0.1/admin/test/get-ws-log?date_time_start=2020-01-01%2010:00:00&date_time_end=2020-04-17%2012:00:00
    // разработчик: Якимов М.Н.
    // дата разработки 17.04.2020 года
    public static function actionGetWsLog()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $warnings[] = "actionGetWsLog. Начал выполнять метод";
            $session = Yii::$app->session;
            $session->open();
            $result = array();
//            if (!isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
//                $errors[] = 'actionGetWsLog. Время сессии закончилось. Требуется повторный ввод пароля';
//                throw new \Exception("actionGetWsLog. Время сессии закончилось. Требуется повторный ввод пароля");
//            }
//            if (AccessCheck::checkAccess($session['sessionLogin'], 77)) {                                        //если пользователю разрешен доступ к функции
//                $warnings[] = "actionGetWsLog. Прав для выполнения достаточно";
//            } else {
//                throw new \Exception("actionGetWsLog. Недостаточно прав для совершения данной операции");
//            }

            $post = Assistant::GetServerMethod();                                                                   //получение данных от ajax-запроса

            if (isset($post['date_time_start']) && $post['date_time_start'] != '' and isset($post['date_time_end']) and $post['date_time_end'] != '') {                                                              //если передан id ветви
                $date_time_start = $post['date_time_start'];
                $date_time_end = $post['date_time_end'];
                $warnings[] = "actionGetWsLog. Получил входные параметры $date_time_start и $date_time_end";
            } else {
                throw new Exception("actionGetWsLog. Параметры не переданы");
            }

            $result = WsLog::find()
                ->where("date_time>'" . $date_time_start . "'")
                ->andWhere("date_time<'" . $date_time_end . "'")
                ->all();


        } catch (Throwable $exception) {
            $errors[] = "actionGetWsLog. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "actionGetWsLog. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    public function actionTestEventFor()
    {

        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $method_name = "actionTestEventFor";
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {

            $response = Yii::$app->db_target->createCommand('delete FROM worker_parameter_value where id < 17060018')->queryAll();

            $response = count($response);
        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $response;

    }


    // actionGetStrataPackageInfo -  метод получения журнала обработанных пакетов ССД Strata за период
    // входные параметры:
    //      date_time_start          - дата и время начала получения журнала
    //      date_time_end            - дата и время окончания получения журнала
    // выходные параметры:
    //
    // пример вызова: http://127.0.0.1/admin/test/get-strata-package-info?date_time_start=2020-01-01%2010:00:00&date_time_end=2020-04-17%2012:00:00
    // разработчик: Якимов М.Н.
    // дата разработки 17.04.2020 года
    public static function actionGetStrataPackageInfo()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $warnings[] = "actionGetStrataPackageInfo. Начал выполнять метод";
            $session = Yii::$app->session;
            $session->open();
            $result = array();
//            if (!isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
//                $errors[] = 'actionGetStrataPackageInfo. Время сессии закончилось. Требуется повторный ввод пароля';
//                throw new \Exception("actionGetStrataPackageInfo. Время сессии закончилось. Требуется повторный ввод пароля");
//            }
//            if (AccessCheck::checkAccess($session['sessionLogin'], 77)) {                                        //если пользователю разрешен доступ к функции
//                $warnings[] = "actionGetStrataPackageInfo. Прав для выполнения достаточно";
//            } else {
//                throw new \Exception("actionGetStrataPackageInfo. Недостаточно прав для совершения данной операции");
//            }

            $post = Assistant::GetServerMethod();                                                                   //получение данных от ajax-запроса

            if (isset($post['date_time_start']) && $post['date_time_start'] != '' and isset($post['date_time_end']) and $post['date_time_end'] != '') {                                                              //если передан id ветви
                $date_time_start = $post['date_time_start'];
                $date_time_end = $post['date_time_end'];
                $warnings[] = "actionGetStrataPackageInfo. Получил входные параметры $date_time_start и $date_time_end";
            } else {
                throw new Exception("actionGetStrataPackageInfo. Параметры не переданы");
            }

            $result = StrataPackageInfo::find()
                ->where("date_time>'" . $date_time_start . "'")
                ->andWhere("date_time<'" . $date_time_end . "'")
                ->asArray()
                ->all();


        } catch (Throwable $exception) {
            $errors[] = "actionGetStrataPackageInfo. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "actionGetStrataPackageInfo. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionGetStrataPackageSource -  метод получения журнала всех пакетов ССД Strata за период
    // входные параметры:
    //      date_time_start          - дата и время начала получения журнала
    //      date_time_end            - дата и время окончания получения журнала
    // выходные параметры:
    //
    // пример вызова: http://127.0.0.1/admin/test/get-strata-package-source?date_time_start=2020-01-01%2010:00:00&date_time_end=2020-04-17%2012:00:00
    // разработчик: Якимов М.Н.
    // дата разработки 17.04.2020 года
    public static function actionGetStrataPackageSource()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $warnings[] = "actionGetStrataPackageSource. Начал выполнять метод";
            $session = Yii::$app->session;
            $session->open();
            $result = array();
//            if (!isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
//                $errors[] = 'actionGetStrataPackageSource. Время сессии закончилось. Требуется повторный ввод пароля';
//                throw new \Exception("actionGetStrataPackageSource. Время сессии закончилось. Требуется повторный ввод пароля");
//            }
//            if (AccessCheck::checkAccess($session['sessionLogin'], 77)) {                                        //если пользователю разрешен доступ к функции
//                $warnings[] = "actionGetStrataPackageSource. Прав для выполнения достаточно";
//            } else {
//                throw new \Exception("actionGetStrataPackageSource. Недостаточно прав для совершения данной операции");
//            }

            $post = Assistant::GetServerMethod();                                                                   //получение данных от ajax-запроса

            if (isset($post['date_time_start']) && $post['date_time_start'] != '' and isset($post['date_time_end']) and $post['date_time_end'] != '') {                                                              //если передан id ветви
                $date_time_start = $post['date_time_start'];
                $date_time_end = $post['date_time_end'];
                $warnings[] = "actionGetStrataPackageSource. Получил входные параметры $date_time_start и $date_time_end";
            } else {
                throw new Exception("actionGetStrataPackageSource. Параметры не переданы");
            }

            $result = StrataPackageSource::find()
                ->where("date_time>'" . $date_time_start . "'")
                ->andWhere("date_time<'" . $date_time_end . "'")
                ->asArray()
                ->all();


        } catch (Throwable $exception) {
            $errors[] = "actionGetStrataPackageSource. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "actionGetStrataPackageSource. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionGetBpdPackageInfo -  метод получения журнала всех пакетов ССД БПД за период
    // входные параметры:
    //      date_time_start          - дата и время начала получения журнала
    //      date_time_end            - дата и время окончания получения журнала
    // выходные параметры:
    //
    // пример вызова: http://127.0.0.1/admin/test/get-bpd-package-info?date_time_start=2020-01-01%2010:00:00&date_time_end=2020-04-17%2012:00:00
    // разработчик: Якимов М.Н.
    // дата разработки 17.04.2020 года
    public static function actionGetBpdPackageInfo()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $warnings[] = "actionGetBpdPackageInfo. Начал выполнять метод";
            $session = Yii::$app->session;
            $session->open();
            $result = array();
//            if (!isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
//                $errors[] = 'actionGetBpdPackageInfo. Время сессии закончилось. Требуется повторный ввод пароля';
//                throw new \Exception("actionGetBpdPackageInfo. Время сессии закончилось. Требуется повторный ввод пароля");
//            }
//            if (AccessCheck::checkAccess($session['sessionLogin'], 77)) {                                        //если пользователю разрешен доступ к функции
//                $warnings[] = "actionGetBpdPackageInfo. Прав для выполнения достаточно";
//            } else {
//                throw new \Exception("actionGetBpdPackageInfo. Недостаточно прав для совершения данной операции");
//            }

            $post = Assistant::GetServerMethod();                                                                   //получение данных от ajax-запроса

            if (isset($post['date_time_start']) && $post['date_time_start'] != '' and isset($post['date_time_end']) and $post['date_time_end'] != '') {                                                              //если передан id ветви
                $date_time_start = $post['date_time_start'];
                $date_time_end = $post['date_time_end'];
                $warnings[] = "actionGetBpdPackageInfo. Получил входные параметры $date_time_start и $date_time_end";
            } else {
                throw new Exception("actionGetBpdPackageInfo. Параметры не переданы");
            }

            $result = BpdPackageInfo::find()
                ->where("date_time>'" . $date_time_start . "'")
                ->andWhere("date_time<'" . $date_time_end . "'")
                ->asArray()
                ->all();


        } catch (Throwable $exception) {
            $errors[] = "actionGetBpdPackageInfo. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "actionGetBpdPackageInfo. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionCallSyncMethod - метод для вызова метод переноса данных с новыми айдишниками
    // 127.0.0.1/admin/test/call-sync-method
    public function actionCallSyncMethod()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $method_name = "actionCallSyncMethod";
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $debug = array();
        try {

//            $reseult =  MigrationDbController::JoinTableParameters();
//            $warnings[] = $method_name . ' Закончил JoinTableParameters';
//            $warnings[] = $reseult['warnings'];
//
//            $debug[] = $reseult['debug'];
//            $errors[] = $reseult['errors'];
//            if($reseult['status'] !=1 ){
//                throw new Exception('метод выполнился с ошибкой');
//            }
            /*
                      $reseult = MigrationDbController::TransferPlaces();
                        $warnings[] = $method_name . ' Закончил TransferEdges';
                        $warnings[] = $reseult['warnings'];

                        $debug[] = $reseult['debug'];
                        $errors[] = $reseult['errors'];
                        if ($reseult['status'] != 1) {
                            throw new Exception('метод выполнился с ошибкой');
                        }

                        $reseult = MigrationDbController::TransferConjunctions();
                        $warnings[] = $method_name . ' Закончил TransferEdges';
                        $warnings[] = $reseult['warnings'];
                        $debug[] = $reseult['debug'];
                        $errors[] = $reseult['errors'];
                        if ($reseult['status'] != 1) {
                            throw new Exception('метод выполнился с ошибкой');
                        }

                        $reseult = MigrationDbController::TransferEdges();
                        $warnings[] = $method_name . ' Закончил TransferEdges';
                        $warnings[] = $reseult['warnings'];
                        $debug[] = $reseult['debug'];
                        $errors[] = $reseult['errors'];
                        if ($reseult['status'] != 1) {
                            throw new Exception('метод выполнился с ошибкой');
                        }


                                    $reseult =  MigrationDbController::TransferSensors();
                                    $warnings[] = $method_name . ' Закончил TransferSensors';
                                    $warnings[] = $reseult['warnings'];
                                    $debug[] = $reseult['debug'];
                                    $errors[] = $reseult['errors'];
                                    if($reseult['status'] !=1 ){
                                        throw new Exception('метод выполнился с ошибкой');
                                    }
                                    $reseult =  MigrationDbController::TransferWorkerParameters();
                                    $warnings[] = $method_name . ' Закончил TransferConnectString';
                                    $warnings[] = $reseult['warnings'];
                                    $debug[] = $reseult['debug'];
                                    $errors[] = $reseult['errors'];
                                    if($reseult['status'] !=1 ){
                                        throw new Exception('метод выполнился с ошибкой');
                                    };

                                    $reseult =  MigrationDbController::TransferWorkerParameters();
                                    $warnings[] = $method_name . ' Закончил TransferWorkerParameters';
                                    $warnings[] = $reseult['warnings'];
                                    $debug[] = $reseult['debug'];
                                    $errors[] = $reseult['errors'];
                                    if($reseult['status'] !=1 ){
                                        throw new Exception('метод выполнился с ошибкой');
                                    }

                                    $reseult =  MigrationDbController::TransferEquipments();
                                    $warnings[] = $method_name . ' Закончил TransferEquipments';
                                    $warnings[] = $reseult['warnings'];
                                    $debug[] = $reseult['debug'];
                                    $errors[] = $reseult['errors'];
                                    if($reseult['status'] !=1 ){
                                        throw new Exception('метод выполнился с ошибкой');
                                    }
                                    $reseult =  MigrationDbController::TransSensorPlaceHandbook();
                                    $warnings[] = $method_name . ' Закончил TransSensorPlaceHandbook';
                                    $warnings[] = $reseult['warnings'];
                                    $debug[] = $reseult['debug'];
                                    $errors[] = $reseult['errors'];
                                    if($reseult['status'] !=1 ){
                                        throw new Exception('метод выполнился с ошибкой');
                                    }
                                    $reseult =  MigrationDbController::TransferConnectString();
                                    $warnings[] = $method_name . ' Закончил TransferConnectString';
                                    $warnings[] = $reseult['warnings'];
                                    $debug[] = $reseult['debug'];
                                    $errors[] = $reseult['errors'];
                                    if($reseult['status'] !=1 ){
                                        throw new Exception('метод выполнился с ошибкой');
                                    }
                                    $reseult =  MigrationDbController::TransferSensorConnectString();
                                    $warnings[] = $method_name . ' Закончил TransferSensorConnectString';
                                    $warnings[] = $reseult['warnings'];
                                    $debug[] = $reseult['debug'];
                                    $errors[] = $reseult['errors'];
                                    if($reseult['status'] !=1 ){
                                        throw new Exception('метод выполнился с ошибкой');
                                    }




                                    $reseult =  MigrationDbController::TransEvents();
                                    $warnings[] = $method_name . ' Закончил TransEvents';
                                    $warnings[] = $reseult['warnings'];
                                    $debug[] = $reseult['debug'];
                                    $errors[] = $reseult['errors'];
                                    if($reseult['status'] !=1 ){
                                        throw new Exception('метод выполнился с ошибкой');
                                    }

                        $reseult =  MigrationDbController::TransWorkerEdgeValue();
                        $warnings[] = $method_name . ' Закончил TransferSensorConnectString';
                        $warnings[] = $reseult['warnings'];
                        $debug[] = $reseult['debug'];
                        $errors[] = $reseult['errors'];
                        if($reseult['status'] !=1 ){
                            throw new Exception('метод выполнился с ошибкой');
                        }
                        $reseult =  MigrationDbController::TransWorkerPlaceValue();
                        $warnings[] = $method_name . ' Закончил TransEvents';
                        $warnings[] = $reseult['warnings'];
                        $debug[] = $reseult['debug'];
                        $errors[] = $reseult['errors'];
                        if($reseult['status'] !=1 ){
                            throw new Exception('метод выполнился с ошибкой');
                        }
            */
            $reseult = MigrationDbController::TransferWorkerParameters();
            $warnings[] = $method_name . ' Закончил TransferConnectString';
            $warnings[] = $reseult['warnings'];
            $debug[] = $reseult['debug'];
            $errors[] = $reseult['errors'];
            if ($reseult['status'] != 1) {
                throw new Exception('метод выполнился с ошибкой');
            }
            $reseult = MigrationDbController::TransferSensorConnectString();
            $warnings[] = $method_name . ' Закончил TransferSensorConnectString';
            $warnings[] = $reseult['warnings'];
            $debug[] = $reseult['debug'];
            $errors[] = $reseult['errors'];
            if ($reseult['status'] != 1) {
                throw new Exception('метод выполнился с ошибкой');
            }


        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }


        $result_m = array('items' => '', 'status' => $status, 'warnings' => $warnings, 'debug' => $debug, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_m;
    }

    // actionRecoveryPackageStrata - метод восстановления пакетов Strata за определенный период
    // пример: 127.0.0.1/admin/test/recovery-package-strata?date_time_start=2020-06-14 00:00:00&date_time_end=2020-06-14 00:00:10
    public function actionRecoveryPackageStrata()
    {
        $method_name = "actionRecoveryPackageStrata";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        try {

//            ini_set('max_execution_time', 6000000);
//            ini_set('memory_limit', "20500M");

            // получить период восстановления данных
            $post = Assistant::GetServerMethod();
            if (isset($post['date_time_start']) && $post['date_time_start'] != '' and isset($post['date_time_end']) and $post['date_time_end'] != '') {                                                              //если передан id ветви
                $date_time_start = $post['date_time_start'];
                $date_time_end = $post['date_time_end'];
                $warnings[] = "actionRecoveryPackageStrata. Получил входные параметры $date_time_start и $date_time_end";
            } else {
                throw new Exception("actionRecoveryPackageStrata. Параметры не переданы");
            }

            $last_id = -1;
            $counter_iteration = true;
            $count_all = 0;
            while ($counter_iteration) {
                // за заданный период получить данные из БД
                $sql_query_source_column = "SELECT * FROM strata_package_source WHERE id > " . $last_id . " and date_time between '" . $date_time_start . "' and '" . $date_time_end . "'  limit 15000"; //определяем столбцы исходной таблицы
                $strata_packages = Yii::$app->db_amicum_log->createCommand($sql_query_source_column)->queryAll();

                $warnings[] = "actionRecoveryPackageStrata. Данные для обработки";
                $warnings[] = $strata_packages;

                if (count($strata_packages) < 15000) {
                    $counter_iteration = false;
                }

                // обработать пакеты в порядке очереди
                foreach ($strata_packages as $strata_package) {
                    $response = StrataJobController::TranslatePackage($strata_package['bytes'], $strata_package['date_time'], $strata_package['ip']);

                    if ($response['status'] == 1) {
                        $warnings[] = $response['warnings'];
                        if (!empty($response['errors'])) {
                            $errors[] = $strata_package;
                            $errors[] = $response['errors'];
                        }
                        $status *= $response['status'];
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        $errors[] = $strata_package;
//                        throw new \Exception("actionRecoveryPackageStrata. Ошибка в обработке пакета");
                    }
                    $count_all++;
                    $last_id = $strata_package['id'];
                }
            }
            $warnings[] = "actionRecoveryPackageStrata. Количество обработанных записей: " . $count_all;

        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_m = array('items' => '', 'status' => $status, 'warnings' => $warnings, 'debug' => $debug, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_m;
    }


    // actionSensorRunInit - метод инициализации кеша сенсоров на основе обычных ключей
    // пример: 127.0.0.1/admin/test/sensor-run-init
    public function actionSensorRunInit()
    {
        $method_name = "actionSensorRunInit";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        try {

//            ini_set('max_execution_time', 6000000);
//            ini_set('memory_limit', "20500M");

            // получить период восстановления данных
            $result = (new SensorCacheController)->runInit(270);

            $warnings[] = "actionSensorRunInit. Количество обработанных записей: " . $count_all;

        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_m = array('items' => $result, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_m;
    }

    // actionSensorRunInitHash - метод инициализации кеша сенсоров на основе хэш
    // пример: 127.0.0.1/admin/test/sensor-run-init-hash
    public function actionSensorRunInitHash()
    {
        $method_name = "actionSensorRunInitHash";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        try {

//            ini_set('max_execution_time', 6000000);
//            ini_set('memory_limit', "20500M");

            // получить период восстановления данных
            $result = (new SensorCacheController)->runInitHash(270);

            $warnings[] = "actionSensorRunInitHash. Количество обработанных записей: " . $count_all;

        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_m = array('items' => $result, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_m;
    }

    // actionSensorMineRunInitHash - метод инициализации кеша шахты сенсоров на основе хэш
    // пример: 127.0.0.1/admin/test/sensor-mine-run-init-hash
    public function actionSensorMineRunInitHash()
    {
        $method_name = "actionSensorRunInitHash";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        try {

//            ini_set('max_execution_time', 6000000);
//            ini_set('memory_limit', "20500M");

            // получить период восстановления данных
            $result = (new SensorCacheController)->initSensorMainHash(270);

            $warnings[] = "actionSensorRunInitHash. Количество обработанных записей: " . $count_all;

        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_m = array('items' => $result, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_m;
    }

    // actionMultiGetParameterValueHash - метод получения данных из кеша параметров сенсора ХЭШ
    // пример: 127.0.0.1/admin/test/multi-get-parameter-value-hash?sensor_id=1163763
    public function actionMultiGetParameterValueHash()
    {
        $method_name = "actionMultiGetParameterValueHash";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        try {

//            ini_set('max_execution_time', 6000000);
//            ini_set('memory_limit', "20500M");
            $post = Assistant::GetServerMethod();
            if (isset($post['sensor_id']) and $post['sensor_id'] != "") {
                $sensor_id = $post['sensor_id'];
            } else {
                $sensor_id = "*";
            }
            // получить период восстановления данных
            $result = (new SensorCacheController)->multiGetParameterValueHash($sensor_id, "*", "*");

            $warnings[] = "actionMultiGetParameterValueHash. Количество обработанных записей: " . $count_all;

        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_m = array('items' => $result, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_m;
    }

    // actionDelInSensorMineHash - метод удаления сенсоров из кеша хэш
    // пример: 127.0.0.1/admin/test/del-in-sensor-mine-hash
    public function actionDelInSensorMineHash()
    {
        $method_name = "actionDelInSensorMineHash";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        try {

//            ini_set('max_execution_time', 6000000);
//            ini_set('memory_limit', "20500M");

            // получить период восстановления данных
            $result = (new SensorCacheController)->delInSensorMineHash("1171887", '270');

            $warnings[] = "actionDelInSensorMineHash. Количество обработанных записей: " . $count_all;

        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_m = array('items' => $result, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_m;
    }

    // actionGetSensorMineHash - метод получения списка сенсоров по шахте из кеша хэш
    // пример: 127.0.0.1/admin/test/get-sensor-mine-hash?sensor_id=343&mine_id=270
    public function actionGetSensorMineHash()
    {
        $method_name = "actionGetSensorMineHash";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        try {

//            ini_set('max_execution_time', 6000000);
//            ini_set('memory_limit', "20500M");

            $post = Assistant::GetServerMethod();
            if (isset($post['worker_id']) and $post['worker_id'] != "") {
                $sensor_id = $post['worker_id'];
            } else {
                $sensor_id = "*";
            }
            if (isset($post['mine_id']) and $post['mine_id'] != "") {
                $mine_id = $post['mine_id'];
            } else {
                $mine_id = -1;
            }

            // получить период восстановления данных
            $result = (new SensorCacheController)->getSensorMineHash($mine_id, $sensor_id);

            $warnings[] = "actionGetSensorMineHash. Количество обработанных записей: " . $count_all;

        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_m = array('items' => $result, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_m;
    }

    // actionGetWorkerMineHash - метод получения списка работников по шахте из кеша хэш
    // пример: 127.0.0.1/admin/test/get-worker-mine-hash?worker_id=343&mine_id=270
    public function actionGetWorkerMineHash()
    {
        $method_name = "actionGetWorkerMineHash";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        try {

//            ini_set('max_execution_time', 6000000);
//            ini_set('memory_limit', "20500M");

            $post = Assistant::GetServerMethod();
            if (isset($post['worker_id']) and $post['worker_id'] != "") {
                $worker_id = $post['worker_id'];
            } else {
                $worker_id = "*";
            }
            if (isset($post['mine_id']) and $post['mine_id'] != "") {
                $mine_id = $post['mine_id'];
            } else {
                $mine_id = -1;
            }

            // получить период восстановления данных
            $result = (new WorkerCacheController)->getWorkerMineHash($mine_id, $worker_id);

            $warnings[] = "actionGetWorkerMineHash. Количество обработанных записей: " . $count_all;

        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_m = array('items' => $result, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_m;
    }

    // actionDelParameterValueHash - метод удаления сенсоров из кеша хэш
    // пример: 127.0.0.1/admin/test/del-parameter-value-hash
    public function actionDelParameterValueHash()
    {
        $method_name = "actionDelParameterValueHash";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        try {

//            ini_set('max_execution_time', 6000000);
//            ini_set('memory_limit', "20500M");

            // получить период восстановления данных
            $result = (new SensorCacheController)->delParameterValueHash('1171887', 164, 3);

            $warnings[] = "actionDelParameterValueHash. Количество обработанных записей: " . $count_all;

        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_m = array('items' => $result, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_m;
    }

    // actionWorkerMultiGetParameterValueHash - метод получения данных из кеша параметров сенсора ХЭШ
    // пример: 127.0.0.1/admin/test/worker-multi-get-parameter-value-hash?worker_id=1163763
    public function actionWorkerMultiGetParameterValueHash()
    {
        $method_name = "actionWorkerMultiGetParameterValueHash";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        try {

//            ini_set('max_execution_time', 6000000);
//            ini_set('memory_limit', "20500M");
            $post = Assistant::GetServerMethod();
            if (isset($post['worker_id']) and $post['worker_id'] != "") {
                $worker_id = $post['worker_id'];
            } else {
                $worker_id = "*";
            }
            // получить период восстановления данных
            $result = (new WorkerCacheController())->multiGetParameterValueHash($worker_id, "*", "*");

            $warnings[] = "actionWorkerMultiGetParameterValueHash. Количество обработанных записей: " . $count_all;

        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_m = array('items' => $result, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_m;
    }

    // actionInitWorkerParameterValueHash - метод инициализации параметров работника ХЭШ
    // пример: 127.0.0.1/admin/test/init-worker-parameter-value-hash
    public function actionInitWorkerParameterValueHash()
    {
        $method_name = "actionInitWorkerParameterValueHash";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        try {

//            ini_set('max_execution_time', 6000000);
//            ini_set('memory_limit', "20500M");
            $post = Assistant::GetServerMethod();
            if (isset($post['worker_id']) and $post['worker_id'] != "") {
                $worker_id = $post['worker_id'];
            } else {
                $worker_id = "*";
            }
            // получить период восстановления данных
            $result = (new WorkerCacheController())->initWorkerParameterValueHash();

            $warnings[] = "actionInitWorkerParameterValueHash. Количество обработанных записей: " . $count_all;

        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_m = array('items' => $result, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_m;
    }

    // actionInitWorkerMineHash - метод инициализации главного кеша работников
    // пример: 127.0.0.1/admin/test/init-worker-mine-hash?mine_id=290&worker_id=1163763
    // пример: 127.0.0.1/admin/test/init-worker-mine-hash?mine_id=290
    public function actionInitWorkerMineHash()
    {
        $method_name = "actionInitWorkerMineHash";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        try {

//            ini_set('max_execution_time', 6000000);
//            ini_set('memory_limit', "20500M");
            $post = Assistant::GetServerMethod();
            if (isset($post['worker_id']) and $post['worker_id'] != "") {
                $worker_id = $post['worker_id'];
            } else {
                $worker_id = -1;
            }
            if (isset($post['mine_id']) and $post['mine_id'] != "") {
                $mine_id = $post['mine_id'];
            }
            // получить период восстановления данных
            $result = (new WorkerCacheController())->initWorkerMineHash($mine_id, $worker_id);

            $warnings[] = "actionInitWorkerMineHash. Количество обработанных записей: " . $count_all;

        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_m = array('items' => $result, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_m;
    }

    // actionInitSensorNetwork - метод получения данных из кеша параметров сенсора ХЭШ
    // пример: 127.0.0.1/admin/test/init-sensor-network
    public function actionInitSensorNetwork()
    {
        $method_name = "actionInitSensorNetwork";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        try {

//            ini_set('max_execution_time', 6000000);
//            ini_set('memory_limit', "20500M");
            $post = Assistant::GetServerMethod();
            if (isset($post['worker_id']) and $post['worker_id'] != "") {
                $worker_id = $post['worker_id'];
            } else {
                $worker_id = "*";
            }
            // получить период восстановления данных
            $result = (new ServiceCache())->initSensorNetwork();

            $warnings[] = "actionInitSensorNetwork. Количество обработанных записей: " . $count_all;

        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_m = array('items' => $result, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_m;
    }

    // actionMoveWorkerMineInitCache - метод перенлса людей между шахтами
    // пример: 127.0.0.1/admin/test/move-worker-mine-init-cache?worker_id=2911761&mine_id=250
    public function actionMoveWorkerMineInitCache()
    {
        $method_name = "actionMoveWorkerMineInitCache";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        try {

//            ini_set('max_execution_time', 6000000);
//            ini_set('memory_limit', "20500M");
            $post = Assistant::GetServerMethod();
            if (isset($post['worker_id']) and $post['worker_id'] != "") {
                $worker_id = $post['worker_id'];
            } else {
                $worker_id = "*";
            }
            if (isset($post['mine_id']) and $post['mine_id'] != "") {
                $mine_id = $post['mine_id'];
            } else {
                $mine_id = -1;
            }
            // получить период восстановления данных
            $result = WorkerMainController::moveWorkerMineInitCache($worker_id, $mine_id);

            $warnings[] = "actionMoveWorkerMineInitCache. Количество обработанных записей: " . $count_all;

        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_m = array('items' => $result, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_m;
    }

    // actionGetGatewayParameterByIpHash - метод получения информации по апйпи шлюза
    // пример: 127.0.0.1/admin/test/get-gateway-parameter-by-ip-hash?ip=172.16.52.170
    public function actionGetGatewayParameterByIpHash()
    {
        $method_name = "actionGetGatewayParameterByIpHash";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        try {

//            ini_set('max_execution_time', 6000000);
//            ini_set('memory_limit', "20500M");
            $post = Assistant::GetServerMethod();
            if (isset($post['ip']) and $post['ip'] != "") {
                $ip = $post['ip'];
            } else {
                $ip = "*";
            }

            // получить период восстановления данных
            $result = (new SensorCacheController())->getGatewayParameterByIpHash($ip);

            $warnings[] = "actionGetGatewayParameterByIpHash. Количество обработанных записей: " . $count_all;

        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_m = array('items' => $result, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_m;
    }

    // actionGetPlaceByTitle - метод получения айдишника места по названию
    // пример: 127.0.0.1/admin/test/get-place-by-title?title=УКТ: К\У 12ц ,К\КВ-г 12Ц
    public function actionGetPlaceByTitle()
    {
        $method_name = "actionGetPlaceByTitle";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        try {

//            ini_set('max_execution_time', 6000000);
//            ini_set('memory_limit', "20500M");
            $post = Assistant::GetServerMethod();
            if (isset($post['title']) and $post['title'] != "") {
                $title = $post['title'];
            } else {
                $title = "*";
            }
            $title = (new Query())
                ->select('dop_text1')
                ->from("AMICUM_INSTRUCTION_MV")
                ->where(['INSTRUCTION_ID' => 8221])
                ->limit(1)
                ->scalar();
            // получить период восстановления данных
            $result = InjunctionController::GetPlaceByTitle($title);

            $warnings[] = "actionGetPlaceByTitle. Количество обработанных записей: " . $count_all;

        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array('items' => $result, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug, 'errors' => $errors);
    }

    //динамичный метод для тестрования различных функционалов.
    // МЕТОД ВСЕГДА ДОЛЖЕН БЫТЬ ПОСЛЕДНЫМ
    public function actionTest()
    {
        $reseult = array();
        $lamps_from_db_strata = Yii::$app->db_strata->createCommand("SELECT personal_id as tabel_number, commtrac_external_id  FROM asset_human")->queryAll();
        $new = array();
        foreach ($lamps_from_db_strata as $db_stratum) {
            $new[] = array(
                'tabel_number' => $db_stratum['tabel_number'],
                'net_id' => $db_stratum['commtrac_external_id'] & 8388607
            );
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $new;
    }

    // actionTestStrata - метод проверки работы методов расшифровки пакетов страта
    // пример: 127.0.0.1/admin/test/test-strata
    public function actionTestStrata()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionTestStrata");

        try {
//            ini_set('max_execution_time', 6000000);
//            ini_set('memory_limit', "20500M");

            $strata_packages = [
                ['date_time' => '2021-02-10 09:06:10.638680', 'ip' => '172.16.59.198', 'mine_id' => '290', 'bytes' => '10028a822504640000001465020100006600280000670000000095a78fea100d8a8002046400000000650003000c6600280000670000000073cc8fea0105298a1605060400bc1bb2005343ab008e84a9008938a8453e8fea100c8a1605046400000014650201000066002800006700000000132d8fea100e8a1605046400000000650003000c660028000067000000000da18fea0102298a80b90004005343bc00bc1baf008e84a5008938a460bb8fea0103288a82fe0003008e84df005343c5008938be7d7e8fea0100288a17290604005343bb00bc1bb3008e84b0008938ab7f548fea100d8a172904640000001465020100006600280000670000000039758fea100e8a1729046400000000650003000c6600280000670000000032d07061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea0105288a80020603008e84cc008938b5005343b258b98fea0109298a18500003008e84ab005343ab00bc1ba61c218fea010c288a7ea40603005343b800bc1ba8008e84a5e7718fea010a298a18500603005343af008e84ab00bc1ba92abf8fea0101288a17290602005343b1008e84aa78b68fea0102298a81380005005343c100bc1bb9008e84b6008938b000489ba696318fea0106288ad0520602005343b100bc1ba923278fea10078ad05204640000001465020100006600280000670000000015048fea0109288a82250605005343c200bc1bc0008e84b9008938b6004466a567e98fea10088ad052046400000000650003000c660028000067000000000e5f8fea0108288a82fe0003008e84de008938cb005343c089a08fea010e288a810d0603005343b300bc1ba5008e84a44cb18fea10018a810d0464000000146502010000660028000067000000007b678fea10028a810d046400000000650003000c6600280000670000000074c28fea0104288a17290603005343b3008e84a500bc1ba2f2e18fea10038a17290464000000146502010000660028000067000000002f7b8fea10058a1729046400050000650003000c660028000067000000002e497061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea010a288a14a20003008e84de005343d5008938d1dc198fea0106298a80b90004005343bb00bc1bb0008e84ad008938a56d3c8fea10088a14670464000000146502010000660028000067000000006f078fea0105298a82c20604005343bd008e84b5008938ae00bc1bab8b4a8fea10078a1498046400000014650201000066002900006700000000a02c7061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea010d298a18500603008e84ac00bc1ba8005343a826c18fea10008a81a20464000000146502010000660028000067000000000f1c8fea0106298a81380604005343c300bc1bbb008e84b6008938af192b8fea010c298ad0b50603005343ea008938d2008e84cab6a48fea010a288a82250603005343b8008e84b100bc1bae7c3c7061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea010b288a14a20603008e84de005343d3008938d1e1768fea0101258a162b0003008e84dd005343c6008938bf39f98fea0c00288a8358100022068fea0108248a17130002008938d2005343cb50848fea0109298a14980603005343ee008938d6008e84d3eb398fea0103288a810d0603005343ae00bc1ba6008e84a63fba8fea10008a8358046400000014650201000066002800006700000000c7ee8fea10008a8358046400000014650201000066002800006700000000c7ee8fea10018a8358046400000000650003000c66002800006700000000c0498fea0107288a80020003008e84cb008938b3005343ae4d748fea100c8a82fe046400000000650003000c6600280000670000000070897061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea0102288a81a20602005343bd00bc1bb033ea8fea0105258ad1cd0003008e84ef008938ca005343c5b6db8fea100e8ad0b5046400000000650003000c66002900006700000000787f8fea0106298a82c20604005343bd008e84b3008938af00bc1bab8b548fea0107298a80b90604005343bb00bc1baf008e84ae008938a372b98fea040806008e84008e84008e84002000008938008938d600010000004008800104020201000002f806488fea010c288a14a20603008e84df005343d4008938d1e4977061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea0106258ad1cd0603008e84e8005343ca008938c3b4558fea10008a17070464000000146502010000660028000067000000000a448fea010a298a14980603005343ee008938dc008e84d0ef678fea010d288a82fe0003008e84dc008938c7005343c28adb8fea010c288a14670603005343e3008938d4008e84d1ad128fea0102288a83580603008e84ef008938d7005343cb0cb88fea010a288a80020003008e84cb008938b1005343aa4a9f8fea0102288a7ea40604005343be008e84b300bc1bb0008938a863bf8fea0107288ad3360604005343c1008e84bd008938bb00bc1baf6e138fea0102258a162b0003008e84dd005343c7008938c03c128fea100e8ad33604640000001465020100006600280000670000000003908fea10008ad336046400000000650003000c66002800006700000000ed747061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea0108298a81380604005343bf00bc1bb9008e84b8008938ae161c8fea010a248a17130603008e84ed008938d2005343cc59138fea0107258ad1cd0603008e84eb008938cd005343ccc4477061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea010b298a14980603005343e9008938dc008e84d1ec4e8fea010d288a14670603005343e3008e84d3008938d3af668fea010b288a80020003008e84c6008938b3005343b04e958fea010a248a17130603008e84ed008938d2005343cc59138fea100e8a1467046400000014650201000066002800006700000000759d8fea100d8a1498046400000000650003000c660029000067000000009e048fea0101298ad0b50603005343dc008938c4008e84bf84047061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea10098ad336046400000014650201000066002800006700000000fe138fea0109298a82c20604005343bb008e84b5008938ac00bc1bab8b828fea10048a8358046400000014650201000066002800006700000000cb528fea10058a8358046400000000650003000c66002800006700000000c4ad8fea0102298ad0b50604005343cd008e84be008938bd00bc1ba7ed8e8fea010b248a17130603008e84ea008938d1005343ca54048fea0109298a81380004005343bf00bc1bb9008e84b6008938b112c08fea0108258ad1cd0603008e84ea008938d4005343c5c46d8fea10008a1467046400000000650003000c660028000067000000005f818fea0104258a162b0003008e84df008938c7005343c545fb7061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea0c00288a83878e00cf8f8fea010b288ad3360603005343cb008938bf008e84b8f4b68fea010a298a80b90003005343ba00bc1bb2008e84aa08ad8fea0102298a18500603005343ab008e84aa00bc1ba216f78fea0100288a82fe0003008e84de008938c5005343be79e88fea0101288a14670604005343d1008e84c2008938b900bc1bacea308fea0106288a83580603008e84e0008938d4005343cbfe6e8fea10038a1707046400000000650003000c6600280000670000000005d18fea010c288a80020003008e84cc008938b4005343b45ae78fea10018a82fe0464000000146502010000660028000067000000006d348fea0105288a7ea40004005343b9008e84ad00bc1bac008938a8510d8fea0106288a81a20603005343bb00bc1bad008938a69a9f7061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea010a298a82c20604005343bb008e84b5008938af00bc1baa8ea78fea0107298a16210602005343ac00bc1ba733568fea10028a162104640000001465020100006600290000670000000026a38fea10038a16210464ffff0000650003000c660029000067000000001dd98fea010d288a17290001005343b4c4c88fea0103298a18500602008e84aa005343a99b678fea0100288a14a20003008e84df005343d7008938d2d66f8fea10048ad052046400000000650003000c660028000067000000000afb8fea0105288a17070603008e84e8008938d6005343c645298fea040906008e84008e84008e84002000008938008938dc00010000004009800104020201000002f90fe68fea010c288ad3360603008938c7005343c6008e84bafa768fea100e8a17290464001200146502010000660028000067000000004cd28fea010c248a17130603008e84e8008938d4005343cc58168fea100d8a80020464000000146502010000660028000067000000007b8a8fea0100298a14980603005343e7008938d8008e84d4de5a7061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea10018a8387046400000014650201000066002800006700000000f7118fea10028a8387046400000000650003000c66002800006700000000f06c8fea0109288a810d0003005343a8008e84a5008938a521298fea0105258a162b0003008e84df005343cc008938c54b7b8fea100e8a8002046400000000650003000c6600280000670000000074e58fea0103288a82fe0003008e84dd008938c5005343bd7a178fea010e298a1605060400bc1bb3005343af008e84ae008938a959588fea0107288a83580603008e84da008938d5005343c7f64c8fea0102298a82250604005343af00bc1ba8008e84a6008938a3c06f8fea0100288a80020003008e84cc005343b3008938b24b508fea0103288a83870603008e84ef008938d8005343d345998fea10008a1729046400000000650003000c6600280000670000000024728fea0107288a81a20602005343ba00bc1baf34258fea0106288ad0520602005343ab00bc1ba71b078fea0109258ad1cd0603008e84e9008938cd005343c5bd547061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea010b298a82c20604005343bd008e84b3008938af00bc1ba78cc38fea010b298a80b90003005343ba00bc1bb1008e84ac0abd8fea0100298a1605060400bc1bb3005343b0008e84aa008938a645088fea0100298a1605060400bc1bb3005343b0008e84aa008938a645088fea0101288a14a20003008e84db005343d6008938d2d2598fea0105258ad306060400bc1bae004466ab0041c1aa005343a6d30c8fea0106288a17070603008e84ed008938d6005343ce53718fea0101288a17290002005343b300bc1bac3b3e8fea010c288a7f2e000100bc1ba461fb8fea010b298a81380004005343c000bc1bba008e84b6008938ac11ff8fea10048a7f2e046400000000650003000c66002800006700000000959c8fea10088a7f2e046400000014650201000066002800006700000000a1be8fea0c00258a1392e400bd8a8fea010d248a17130603008e84e5008938d5005343ce59158fea0106258a162b0003008e84da005343c5008938c23d3b8fea0104288a82fe0003008e84df005343c3008938c2808b8fea010a288a810d0603005343b1008e84a6008938a532e68fea010d288a7f2e0001005343a8251f8fea010a258ad1cd0003008e84e8008938d4005343c3bc2b8fea0d00258a1392e500bf948fea010c298a80b90002005343bb00bc1bb14d3a7061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea010a298a16210603005343a800bc1ba6008e84a3e7948fea10088a16210464000000146502010000660029000067000000002c398fea10098a16210464ffff0000650003000c66002900006700000000236f8fea0104298a18500602008e84aa005343a99c768fea0c00248ad0172b0045958fea0c00258ad1de4000231e8fea100a8a7f2e046400050000650003000c66002800006700000000a08c8fea100e8a7f2e046400000014650201000066002800006700000000a7548fea0d00258ad1de410025288fea0101298a7ea80604005343b9008e84af00bc1baf008938ad62a58fea0d00258a16622200cf8a8fea0d00248ad0172c00479f8fea0c00258a81070800c4e98fea0108288a83580003008e84d7008938d4005343d2f8f68fea0104288a83870603008e84e5008938d2005343cc2f2d8fea10098a8358046400000014650201000066002800006700000000d0cf8fea100a8a8358046400000000650003000c66002800006700000000c92a8fea0c00258a80402a001ed48fea0d00258a81070900c6f38fea0107288a7ea40603005343ba00bc1bb0008e84a9f0508fea0d00258a82b29a00031a8fea0105298a18500003005343ac008e84a900bc1ba617e48fea010c298a81380002005343c4008e84bc1c3b8fea0d00258a80402b0020de7061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea0c00258a82e01200a88c8fea0102288a17290602005343b6008e84aa7ede8fea10028a14980464000000146502010000660029000067000000009baf8fea10038a1498046400000000650003000c66002900006700000000940a8fea10078a170704640000001465020100006600280000670000000011f38fea0101298a16050604005343b200bc1bb0008e84ac008938a8491a8fea10008a7f2e046400000000650003000c6600280000670000000091388fea0102288a14a20002008e84de005343d541948fea0d00258a82e01300aa968fea0101288a80020003008e84d1008938af005343ae49248fea100b8a810d04640000001465020100006600280000670000000085618fea0109288a17070602008e84ec005343d4c3f98fea100c8a810d046400070000650003000c66002800006700000000853a8fea010c298a82c20603005343be008e84b2008938ae0d838fea0108288a81a20603005343bb008e84af00bc1bacf5688fea10098a81a204640000001465020100006600280000670000000018fd8fea100a8a81a2046400000000650003000c6600280000670000000011588fea0104298a14980603005343d2008e84c8004466a7793d8fea0107258a162b0002008e84d6005343bfb0728fea0105288a82fe0002008e84df005343c2fccf7061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea0102288a80020602008e84d1005343b2e39c8fea10068a82fe04640000001465020100006600280000670000000072b18fea0106298a18500602008e84ac005343a79e9c8fea10078a82fe046400000000650003000c660028000067000000006b0c7061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea10048a8002046400000000650003000c660028000067000000006aeb8fea0103288a14a20602008e84d6005343d33eb58fea010a288a17070602005343e1008e84cbb0d88fea0100248a17130602008e84e8005343d3bda98fea040a06008e84008e84008e84002000008938008938cc00010000004009000104020201000002fa81ed8fea0108288a82fe0003008e84dc008938c1005343c07d5c8fea0d00258a82a622007f068fea010c258ad1cd0603008e84eb008938bf005343bdac518fea010e298a80b90002005343b8008e84aa808a8fea0106288a83870603005343c6008938c5008e84c0f90f7061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea0105288a80020602008e84d1005343b9edd08fea0c00258ad12452007b148fea010a258ad3060603008e84cb005343c7008938c0d0018fea0d00258ad12453007d1e8fea0c00258a1787af00810f8fea010c288a83580603005343cc008938bc008e84b9c6c28fea010e298a81380605005343c400bc1bbd008e84b7008938ae004466a574e88fea10078a8387046400000014650201000066002800006700000000fda78fea10088a8387046400000000650003000c66002800006700000000f6028fea010e298a82c20603005343bc008e84b300bc1ba81ef58fea0105298a14980602005343c2008e84b909057061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea10068a7e93046400000000650003000c66002400006700000000f7458fea0d00258a15b4ad00ab928fea0c00258a16267a00ea7e8fea0103288ad3360602005343c5008e84b764a48fea0106298a82250604005343b300bc1bad008938a5008e84a0c9e08fea0101248a17130002008e84ea005343d3ba868fea0c00258a143bdf00627f8fea0d00258a16267b00ec888fea0104288a14a20602008e84db005343d546df8fea010d258ad1cd0002008e84ea005343c129ec8fea0c00258acea3c9006e738fea0c00248ad0bf2f00f1958fea0109288a82fe0002008e84dd005343c3ff028fea0d00258ad191aa0041138fea0c00258ad191a9003f098fea0c00258acfe10300e7a58fea010b288a17070602005343e9008e84d5c3198fea0104298a7ea80001005343b9a7258fea0d00258acfe10400e9af8fea0103298a16050602005343af008e84ab55418fea0d00258acea3ca00707d8fea0100298a80b90003005343b800bc1bb5008e84a8fdea7061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea0109248a7e930603005343ca008938b5008e84aee1268fea0d00248ad0bf3000f39f8fea0100288a810d0601005343ae05e38fea0103288a7f2e0602005343ad00bc1ba8a68d8fea0c00258a18ac45003dae8fea10048a7f2e0464000000146502010000660028000067000000009d5a8fea0109258a162b0002008e84d6005343c7ba988fea010b258ad3060602005343d8008e84d1665b8fea10058a7f2e046400000000650003000c6600280000670000000096b58fea0106288a80020602008e84cd005343baebcc8fea0c00258ad01d49006ae98fea100a8a7e9304640000001465020100006600240000670000000003678fea0c00258a18e23e006c428fea100b8a7e93046400000000650003000c66002400006700000000fcc28fea0100298a82c20602005343b7008e84b38b738fea0d00268a81070b00c9fd8fea0108298a18500603005343ad008e84ab00bc1ba421827061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea010c288a17070602005343c5008e84be895d8fea0106288ad3360602005343cd008e84be76008fea100d8a170704640000001465020100006600280000670000000017898fea0100298a81380602005343be008e84b3079c8fea100e8a81a20464000000146502010000660028000067000000001d7a8fea10008a81a2046400000000650003000c66002800006700000000075e8fea010e258ad1cd0002008e84eb005343c630058fea010a278a82fe0002008e84dc005343bdf8f88fea010a258a162b0002008e84d4005343c5b79b8fea0d00258ad01d4a006cf38fea0d00258a8375070034418fea0107288a80020602008e84cd005343b4e6d58fea010c248a7e930602008e84da005343d1a0697061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea010c258ad3060602008e84e7005343e186b58fea0c00258a1610f0004a287061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea0c00258a1426cb0039188fea0d00258a1610f1004c328fea0109298a18500602008e84aa005343a8a0c08fea0101298a81380602005343bd008e84b206a58fea100d8ad306046400000000650003000c66002500006700000000c7818fea100b8a82fe04640000001465020100006600270000670000000076268fea0c00238a82dad90067fc8fea0106288a14a20602008e84dd005343d449067061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea10088a8002046400000014650201000066002800006700000000760d8fea0103248a17130002008e84e9005343d2ba9e8fea010d288a82fe0002008e84dc005343c100378fea040b06008e84008e84008e84002000008938008938c700010000004009800104020201000002fbfeba8fea0d00258ad306620070ea7061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea0c00258a18c9d300e8218fea0d00258a18c9d400ea2b8fea0107288a14a20602008e84d8005343d546fd8fea0102298a81380602005343be008e84b40abb8fea0104248a17130602008e84ea005343d2c2ee7061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea010e288a82fe0002008e84dc005343bfff448fea0d00258a17a539002a858fea010b298a80020602008e84d4005343b7f5457061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea0103298a81380603005343ba008e84b5008938af799e8fea0108288a14a20603008e84da005343d3008938d2db1a8fea0105248a17130603008e84e9008938d3005343c2478b7061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea0d00258a8055090013d98fea010c298a80020603008e84d3008938b6005343b46a967061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea10018a82fe0464000000146502010000660027000067000000006c2c8fea010e258a162b0003008e84d8008938cc005343c64e947061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea010d298a80020603008e84d2008938b9005343b36cae8fea040c06008e84008e84008e84002000008938008938d400010000004009800104020201000002fc0dcb7061727365'],
//                ['date_time' => '2021-02-10 08:15:07.919015', 'ip' => '172.16.59.3', 'mine_id' => '290' , 'bytes' => '005190c50010000000c005000104030201010a0dd4bfd48fea04060600bbf100bbf100bbf1005000001f80004525dc0004000000400280010402020100000096283a8fea0102248a80550605004155e200536dca0045ddbb004c84a9004c75a4c7438fea100b8a179e046400000014650201000066002800006700000000ac518fea0109248ad01d0602005106bc00519da3ca4d8fea0105248acf37000300519dba005106b60051b7aa9d0c8fea0109278a7ea30603004468be004446bb0045fdaefd538fea100c8a179e046400000000650003000c66002800006700000000a5ac8fea010a278a80610603005106da005108b700519dadfb958fea10028a1436046400000014650201000066002400006700000000341b8fea010b288a82d4000200451ebe00bbf1b20ed78fea10038a1436046400000000650003000c660024000067000000002d768fea0109288a82ee06030052edeb004c75ba0041a6b7f1078fea0102278a18e70602004468d6004446a9e9c08fea0102248ad1cd0604004c84c9004695bb00536db4004155a3aefa7061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea04053a00510400bbf1005190c5400c001f80005190c50010000000c005800104030201010a0dd541fb8fea0106238ad0bf0603005106c300519dc30051b7b64eab7061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea10018a18780464000000146502010000660028000067000000007d2a8fea100a8a82ee0464002800146502010000660028000067000000008e858fea10028a1878046400000000650003000c6600280000670000000076858fea0102248a143b0603004c84c100536db8004695a90fdd8fea100e8a182104640000001465020100006600270000670000000032ed8fea10098a174d04640010001465020100006600240000670000000065298fea010b278a80f706020052edcc004c75b435a68fea010e278a17de0604004446d90045fdbc004468b3004447b1347c8fea100b8a82ee046400000000650003000c660028000067000000005f108fea10038a18e7046400000014650201000066002700006700000000edde8fea100e8ad1e7046400000014650201000066002700006700000000b1908fea10048a18e7046400000000650003000c66002700006700000000e6398fea04093a00519d00bbf10051b7cd3008001f800051b7cd000c010100c00788010403020101004349d0778fea0103288a18780604004446c70045fdc5004447af004468aeb5e38fea0101278ad1e70602005106bf005108b00bab8fea010e278a14ce06040052fcd90052dec2004436b400451eaa79e88fea10008ad1e7046400000000650003000c660027000067000000009b748fea10008a14ce046400000014650201000066002700006700000000cd118fea04063a00510400bbf1005190c5400c001f80005190c50010000000c005000104030201010a0dd6c3228fea100a8a174d046400000000650003000c660024000067000000004e648fea0109288acf3f0604004446be0045fdb6004468aa004447a715e68fea0108278a828a06020052edd1004c75b0c8f78fea10008a1821046400000000650003000c660027000067000000001cd18fea10068a828a04640018001465020100006600280000670000000016698fea0104278a180c0603004468d8004446b80045fda70b8f8fea10098a82d40464000000146502010000660028000067000000004b608fea10078a828a046400000000650003000c66002800006700000000f7148fea010d278acf6c0604004446d40045fdc4004468ba004447b6887e8fea100a8a82d4046400000000650003000c6600280000670000000044bb8fea010b248a174d0605004c84c400536dbe004695b3004155ac0045dda74d848fea0107248acfe1060400536dc2004c84be004155ae0045dda706348fea10058a180c046400000014650201000066002700006700000000143e8fea100c8a80f70464001d00146502010000660027000067000000008b818fea100d8acf9b0464001900146502010000660027000067000000007b838fea100e8acf9b046400000000650003000c660027000067000000005b1c8fea10068a180c046400000000650003000c660027000067000000000d998fea100d8a80f7046400000000650003000c6600270000670000000067d28fea010c278ad0c70605004524e200521fdb005194d4005190aa004695a938ef8fea0108278a17480604004695d9004524c5004c84ae00521fae1bb58fea0104248a14360602004468de004446b4461c8fea04073a00510400bbf1005190c5400c001f80005190c50010000000c005000104030201010a0dd7c5498fea10018a14ce046400000000650003000c66002700006700000000c66c8fea0100278acf9b060300519db7005106b70051b7aa03fd8fea010c288a82d4000200451eb900bbf1b20acd8fea010c248a17870603005106c200519db90051b7ae51148fea100d8a1787046400100014650201000066002400006700000000a3898fea010e248a18e20601004c84b1b8508fea010a248ad01d0602005106b100519da6c3287061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea0102248ad1cd0604004c84c9004695bb00536db4004155a3aefa8fea0106248acf37000300519db9005106b10051b7a997fc8fea10038ad1cd046400180014650201000066002400006700000000a1d98fea0101278a18210604004446d60045fdc0004468b3004447ab66558fea100e8a1787046400000000650003000c660024000067000000008cc48fea010d288a179e0603004446c5004468bd0045fdad9eb68fea010b278a80610603005106da005108af00519dacf37f8fea0109278a828a06030052edd70041a6a8004c75a453908fea0106278a82e3060100536dab084e8fea0103248a80550605004155e100536dcb0045ddc3004c84a9004c75a4d0a28fea04002f00510800bbf1005190be300c001f80005190be0010010100c0058801040302010100bb9080b98fea010c288a82ee06040052edd50041a6d5004508b9004c75a9f2508fea10048a82e304640021001465020100006600270000670000000075778fea10008a17de046400000014650201000066002700006700000000e0b68fea10058a82e3046400000000650003000c660027000067000000004d808fea010a278a7ea30603004446c3004468b90045fdae01018fea10048ad1cd046400000000650003000c6600240000670000000082848fea040b3d0052fc00bbf10052dec74003001f800052dec70007010111c00388010403020101000d6b78798fea10088acfe1046400000014650201000066002400006700000000a0308fea04083a00510400bbf1005190c6400c001f80005190c60010000000c005000104030201010a0dd8c99d8fea10098acfe1046400000000650003000c66002400006700000000998b8fea10018a17de046400000000650003000c66002700006700000000d9118fea0105248ad1cd060300536db9004c84b1004695b15ad18fea0107238ad0bf060300519dc0005106b90051b7b33fca8fea0104248a189e0604004c84bd00536dbc004155a9004695a8b7f28fea0100248a17870603005106bf00519dbe0051b7a841288fea0101278a811c00040052fcd0004436cc004c75ae0052deac7c978fea0101248a18e20602004c84ac00536da50cd58fea100c8a7ea3046400000000650003000c6600270000670000000010538fea0103248a143b060400536dc2004c84b7004695ab004155a44d208fea100b8a7ea304640000001465020100006600270000670000000017f88fea10088ad0bf04640015001465020100006600230000670000000093cd8fea04093a00510400bbf1005190c6400c001f80005190c60010000000c005000104030201010a0dd9cbc48fea10098ad0bf046400000000650003000c6600230000670000000077ae8fea0102278a17de0605004446d00045fdc7004447b1004468ae004474a684c88fea010a248acfe1060500536dcd004c84bc004155b2004695ae0045dda7a0218fea010e278acf6c0605004446d20045fdcc004468ba004447b3004474adf2668fea0102278ad1e70602005106bd005108b10bb18fea0105278a18e70602004468c2004446b2e1928fea0107278a180c0603004468ca004446bb0045fdae0a608fea0102288a14ce06030052fce40052debb004436b363e37061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea010c248a174d060400536dcb004c84b2004155ae0045ddacc1598fea10078acf3704640012001465020100006600240000670000000007bf8fea10088acf37046400000000650003000c66002400006700000000eed68fea100a8a1748046400000000650003000c660027000067000000004c0e8fea10098a174804640000001465020100006600270000670000000053b38fea10028a811c0464000000146502010000660027000067000000008ac28fea0104288a187806040045fdd5004446c0004447b3004468acc1758fea040a3a00510400bbf1005190c5400c001f80005190c50010000000c006000104030201010a0ddaccc98fea010a288acf3f06040045fdcb004446bd004447ad004468a72d508fea010b278a17480603004695e1004524ba004c84b300258fea10038a811c046400000000650003000c66002700006700000000831d8fea010e278a80f706030052edd9004c75b60041a6abdaf98fea10048a143b0464001300146502010000660024000067000000004e118fea10008a18e2046400000000650003000c66002400006700000000da4f8fea0102278a18210604004446d30045fdc7004447b6004468b376178fea010d278ad0c7060400521fe4005194e1004524dc005190b8d2168fea04073e004c7500bbf10052fcb65004001f800052fcb60008020201c105880104030201010008e780758fea0105248a14360602004468cb004446ba3ad28fea0101278acf9b0603005106bb00519dbb0051b7b012f28fea10058a143b046400000000650003000c6600240000670000000034168fea0109248acf37000300519dc5005106bf0051b7b7c2f58fea0104248a80550603004155e10045ddc000536dbde06b8fea0107248a189e060500536dc7004c84bc004695ad004155ac0045dda594188fea100d8a8061046400000000650003000c66002700006700000000d1ee8fea010d288a82d4000200451ebc00bbf1b20eeb8fea010e278a80610602005106ce005108b757328fea0106248ad1cd0603004c84bf00536dbe004695b3709d8fea040b3a00510400bbf1005190c5400c001f80005190c50010000000c006080104030201010a0ddbd6407061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea100c8a8061046400100014650201000066002700006700000000e8b38fea10008acf6c04640000001465020100006600270000670000000026728fea010a288a828a06030052edd90041a6b8004c75a86b1b8fea010e288a179e0603004446d5004468bd0045fdb0b25c8fea0102248a18e2060200536db2004c84a614c38fea10018acf6c046400000000650003000c660027000067000000001fcd8fea10058a189e046400000014650201000066002400006700000000a3b28fea0106278a18e70603004468cd004446ad0045fda4cfed8fea10068a189e046400000000650003000c660024000067000000009c0d8fea0108278a180c0603004468cb004446bc0045fdae0d818fea010d288a82ee06040041a6e70052edbe004508b90051b7a430b08fea0101248a17870603005106bf00519dbd0051b7b14a3f8fea010d278a7ea30603004446de0045fdb2004468b01a648fea010a238ad0bf060300519dc2005106bb0051b7b2451e8fea0104278a811c00040052fccc004436cb004c75a90052dea871828fea04053d00415500bbf1004c75b94005001f80004c75b90009020200c015b80104030201010008e56cd98fea040c3a00510400bbf1005190c5400c001f80005190c50010000000c006000104030201010a0ddcd0178fea0103278ad1e70602005106be005108b00cc48fea0100278a80f706030052ede50041a6c0004c75b7ee318fea0106248a143b060300536dcb004c84ab004695aa11038fea010d248a174d060500536dd1004c84b7004155b70045ddab004695a95a258fea010b248acfe1060500536dcc004c84be004155b6004695b10045ddabad7c8fea0103278a14ce06040052fcd50052deb2004c75ac004436abb1df8fea0103278a17de06050045fdd2004446c0004447b2004468b0004474a6839d8fea0102278acf6c06050045fde8004446cf004447bb004468b5005193ab2c918fea10058a8055046400000014650201000066002400006700000000c2c48fea04043a00444600bbf1004447ab6002001f800045fdc30003010133c0048c0104030201010024d4b9488fea10068a8055046400000000650003000c66002400006700000000bb1f7061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea100b8acf3f0464001700146502010000660028000067000000001c4d8fea0105288a187806050045fde6004446bc004447b2004474ab004468a62c128fea010c288acf3f06040045fddd004446bd004447ad004474a74d808fea040d3a00510400bbf1005190c5400c001f80005190c50010000000c006000104030201010a0dddd23e8fea100e8a82d404640000001465020100006600280000670000000050dd8fea100b8a828a0464001900146502010000660027000067000000001bf08fea10008a82d4046400000000650003000c660028000067000000003ac18fea100c8a828a046400000000650003000c66002700006700000000fb898fea010c278a17480603004695d9004524c2004c84a9f70e8fea100e8ad0c704640000001465020100006600270000670000000090b98fea0102278acf9b0603005106bf00519db40051b7a606fc8fea0103278a182106040045fdd6004446c6004468b2004447ae70978fea0106248a14360603004468d6004446bf0045fdb03ecf8fea010b248ad01d0602005106b100519da9c73a8fea100c8ad01d046400190014650201000066002400006700000000fa958fea0100278ad0c70605005194eb00521fdd004524d2005190be005104a8c28e8fea100d8ad01d046400000000650003000c66002400006700000000da2e8fea0107248a80550604004155e20045ddc400536dc3004c75ae5e0c8fea010a248acf370603005106c400519dc20051b7b4c8038fea10018ad0c7046400000000650003000c660027000067000000007bb67061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea0100288a179e0603004446e10045fdb7004468b0aaf88fea040e3a00510400bbf1005190c5300c001f80005190c50010000000c007000104030201010a0ddec5d08fea0100278a80610603005106d5005108b900519da5e6ac8fea010d278a828a06030041a6d20052edba004508a8f4be8fea0109278a180c0603004468ca004446bf0045fdb91ba58fea0107248ad1cd060400536dc9004c84be004695b3004155b0c2e48fea0103248a18e2060200536dc6004c84a82b388fea010e288a82ee06040041a6e4004508bc0052edae0051b7ac29978fea0107278a18e70603004468cb004446bc0045fdabe4408fea0108248a189e060500536dd7004c84b8004155b6004695ac0045dda7ac4e8fea040f3a00510400bbf1005190c5400c001f80005190c50010000000c006000104030201010a0ddfd68c8fea010b238ad0bf0603005106bf00519dbe0051b7b145c88fea04070600bbf100bbf100bbf1005000001f80004525dc00040000004002800104020201000000972a618fea0107278acf82060300451ed300bbf1a90052dea4ebfa8fea0101288a82d4000200451ebe00bbf1b204418fea100a8acf82046400120014650201000066002800006700000000599c8fea100b8acf82046400000000650003000c6600280000670000000040b38fea0107248a143b060500536deb004155b0004c84b00045dda8004695a645c38fea0105278a811c00040052fccd004436ca004c75aa0052dea974a38fea0102248a17870603005106be00519dbc0051b7ae46418fea0101278a80f706030041a6da0052edbf004508b26a888fea0101278a80f706030041a6da0052edbf004508b26a888fea010e248a174d060500536ddd004155bb004c84b20045ddad004695a564318fea0104278a17de06050045fddd004446bb004447b6004468aa004474aa8c3c8fea0104278ad1e70002005106be005108b007978fea0103278acf6c06050045fde4004446bf004447bd004474b2004468abec668fea0108278a82e306009e698fea10038acf9b0464001b001465020100006600270000670000000073ad8fea0104278a14ce06040052fcd3004436c50052deae004c75a8c29a8fea010c248acfe1060500536de9004155bd004c84b70045ddb1004695adcdb68fea10048acf9b046400000000650003000c6600270000670000000051228fea10048a182104640000001465020100006600270000670000000028f38fea04003a00510400bbf1005190c5400c001f80005190c50010000000c006000104030201010a0de0c8538fea10008a82ee0464002a001465020100006600280000670000000086af8fea10058ad1e7046400000014650201000066002700006700000000a8af8fea10068ad1e7046400000000650003000c66002700006700000000a10a8fea10008a174d0464001000146502010000660024000067000000005c487061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea10058a14ce046400000014650201000066002700006700000000d28e8fea10018a174d046400000000650003000c6600240000670000000045838fea010e278a7ea30603004446da0045fdb3004468a70f4f8fea10078a187804640000001465020100006600280000670000000083c08fea10018a179e046400000014650201000066002800006700000000a2578fea10028a80f70464001d001465020100006600270000670000000081878fea10088a1878046400000000650003000c660028000067000000007c1b8fea010d278a17480603004695cd004524c2004c84a5e8b18fea10078a143604640000001465020100006600240000670000000039988fea040f3400519400bbf1004524c7400a001f80004524c7000e030210c0068c01040302010100daefe4d38fea10038a80f7046400000000650003000c660027000067000000005dd88fea10028a179e046400000000650003000c660028000067000000009bb28fea10018a82ee046400000000650003000c6600280000670000000055168fea0106288a187806050045fdda004447b7004446b6004474ab005193a759c68fea0105278acf9b0603005106c600519dac0051b7a305498fea10058a1821046400000000650003000c66002700006700000000214e8fea0102278ad0c70605005194d700521fd2005190c3004524c0005104aa9a468fea0108248a14360603004468d4004446c30045fdb345fa8fea04013a00510400bbf1005190c5400c001f80005190c50010000000c006800104030201010a0de14a7a8fea0109248a189e060500536dde004155ba004c84b40045ddb0004695a7b8308fea0109278a82e30601004c84a616838fea10048a18e20464001d0014650201000066002400006700000000037b8fea0108248a80550603004155d80045ddbe00536db5d1548fea010b248acf37060300519dc2005106be0051b7a9b8418fea0102288a82d4000200451ebc00bbf1b102458fea100a8a180c046400000000650003000c6600270000670000000011fd8fea10058a18e2046400000000650003000c66002400006700000000dfcc8fea0109248ad1cd060500536ddf004155b8004c84b50045ddad004695a79db28fea10088ad1cd046400180014650201000066002400006700000000a6568fea10038a17870464000f0014650201000066002400006700000000987d8fea10068a14ce046400000000650003000c66002700006700000000cbe98fea0106248a18e2060200536dc0004155a7edc78fea0100248ad01d0602005106ab00519daab7788fea10048a1787046400000000650003000c6600240000670000000082ca8fea04023a00510400bbf1005190c5400c001f80005190c50010000000c006000104030201010a0de2cca18fea0102288a82ee06040041a6db004508be0051b7ae0052edae18468fea100a8ad1cd046400000000650003000c66002400006700000000881a7061727365722e633a205265636569766564206b65657020616c69766520646174610d0a8fea0101278a80610603005106d7005108b900519da8ecd48fea0103288a179e0603004446d10045fdb9004468af9eaa8fea010c238ad0bf0603005106c100519dbc0051b7aa3fdc8fea10088a18e7046400000014650201000066002700006700000000f25b8fea100d8acfe1046400000014650201000066002400006700000000a5ad8fea10098a18e7046400000000650003000c66002700006700000000ebb68fea010d288acf3f06040045fdcd004447b6004446ad004474a7378c8fea0105248a17870603005106bc00519db80051b7ae43548fea100e8acfe1046400000000650003000c660024000067000000009e088fea0106278a811c00040052fccd004436ca004c75ab0052dea976bf8fea040f3300521f00bbf1004524e4500a001f80004524e4000e010110c0058801040302010100fa5f41518fea0108248a143b060400536dcc004155b80045ddad004c84aaac078fea0104278a80f706040041a6e8004508b80052edb30051b7a725428fea0100278a7ea30604004446c30045fdc0004468a7004447a528a38fea0100248acfe1060500536dd4004155bf004c84b50045ddb4004695a6a81d8fea0106278a182106050045fdea004447bd004446bc004468b3004474aef4f18fea0108278acf82060300451ed400bbf1a90052dea4ed168fea10068a17de046400000000650003000c66002700006700000000de8e8fea010e278a828a06030041a6e100'],

            ];

            // обработать пакеты в порядке очереди
            foreach ($strata_packages as $strata_package) {
                $response = StrataJobController::TranslatePackage($strata_package['bytes'], $strata_package['date_time'], $strata_package['ip'], $strata_package['mine_id']);
                $log->addLogAll($response);

                if ($response['status'] != 1) {
                    throw new Exception('Ошибка обработки пакета');
                }
                $count_record++;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    // actionTestStrataRegistrationPacket - метод тестирования расшифровки пакетов страта
    // пример: 127.0.0.1/admin/test/test-strata-registration-packet?mine_id=290&ip=172.16.59.199&date_time=2021-5-7 9:13:56.469428&net_id=708373&checkIn=1
    public function actionTestStrataRegistrationPacket()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionTestStrataRegistrationPacket");

        try {
//            ini_set('max_execution_time', 6000000);
//            ini_set('memory_limit', "20500M");
            $post = Assistant::GetServerMethod();
            $mine_id = $post['mine_id'];
            $ip = $post['ip'];
            $date_time = $post['date_time'];
            $minerNodeAddress = $post['net_id'];
            $checkIn = $post['checkIn'];

            $packet_object = (object)[
                'timestamp' => $date_time,
                'sequenceNumber' => '0',
                'batteryVoltage' => '3.600',
                'hearedNodes' => null,
                'hearedNodesCount' => null,
                'minerNodeAddress' => $minerNodeAddress,
                'sourceNode' => $minerNodeAddress,
                'checkIn' => $checkIn
            ];

            $response = StrataJobController::saveRegistrationPacket(new MinerNodeCheckInOut($packet_object), $mine_id, $ip);
            $log->addLogAll($response);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    // actionPullRabbitQueueDivision - метод прослушивания сообщений в очереди RabbitMQ Division
    // пример: 127.0.0.1/admin/test/pull-rabbit-queue-division
    public function actionPullRabbitQueueDivision()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionPullRabbitQueueDivision");

        try {
            Yii::$app->rabbitDivision->listen();
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    // actionPullRabbitQueueEmployee - метод прослушивания сообщений в очереди RabbitMQ Employee
    // пример: 127.0.0.1/admin/test/pull-rabbit-queue-employee
    public function actionPullRabbitQueueEmployee()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionPullRabbitQueueEmployee");

        try {
            Yii::$app->rabbitEmployee->listen();
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    // actionPullRabbitQueueAd - метод добавления сообщения в очередь RabbitMQ AD
    // пример: 127.0.0.1/admin/test/pull-rabbit-queue-ad
    public function actionPullRabbitQueueAd()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionPullRabbitQueueAd");

        try {
            Yii::$app->rabbitAdPull->listen();
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    // actionPushRedisQueue - метод отправки данных в очередь RabbitMQ
    // пример: 127.0.0.1/admin/test/push-rabbit-queue
    public function actionPushRabbitQueue()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionPushRabbitQueue");

        try {
//            $rabbit = new RabbitController(['port' => 5672,
//                'user' => 'guest',
//                'host' => '192.168.1.224',
//                'password' => 'VpAG7P?8',
//                'queueName' => 'rabbitDivision',
//                'dsn' => 'amqp://guest:VpAG7P?8@192.168.1.224:5672/%2F']);
            $result = Yii::$app->rabbitDivision->push(new SynchronizationJobController([
                'text' => date("Y-m-d H:i:s"),
                'file' => 'log\queue.dat'
            ]));


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    // actionTestJsonRecoveryBy1C - метод тестирования восстановления строки json после получения с 1С
    // пример: 127.0.0.1/admin/test/test-json-recovery-by-1-c?key=LogEv:my:2021-04-08 16:04:42.634654
    public function actionTestJsonRecoveryBy1C($key)
    {
        $result = [];                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionTestJsonRecoveryBy1C");

        try {
//            $json_encoded_arr[] = '{\r\n\"Ссылка\": \"abff8c5b-e290-11ea-80ee-20677cd5ee4b\",\r\n\"Наименование\": \"0401.03.03. Буровой участок\",\r\n\"Родитель\": {\r\n\"Ссылка\": \"4cb2ee29-e200-11ea-80ee-20677cd5ee4b\",\r\n\"Наименование\": \"0401.03. Отделение дегазации и геологоразведочных работ\",\r\n\"Родитель\": {\r\n\"Ссылка\": \"d7ea5c1e-e1e8-11ea-80ee-20677cd5ee4b\",\r\n\"Наименование\": \"0401. Департамент технического развития\",\r\n\"Родитель\": {\r\n\"Ссылка\": \"01cca5ee-a948-11ea-80e3-20677cd5ee4b\",\r\n\"Наименование\": \"0400. Управление по операционной деятельности\",\r\n\"Родитель\": {\r\n\"Ссылка\": \"0fe1c078-2003-11ea-80cf-20677cd5ee4b\",\r\n\"Наименование\": \"ПАО ЮЖНЫЙ КУЗБАСС\",\r\n\"Родитель\": \"\",\r\n\"Владелец\": {\r\n\"Ссылка\": \"7837d7b2-43cb-11e9-810e-ecb1d7734ef5\",\r\n\"Наименование\": \"ПАО \\\"Южный Кузбасс\\\"\",\r\n\"КодУзла\": \"KZB\"\r\n}\r\n},\r\n\"Владелец\": {\r\n\"Ссылка\": \"7837d7b2-43cb-11e9-810e-ecb1d7734ef5\",\r\n\"Наименование\": \"ПАО \\\"Южный Кузбасс\\\"\",\r\n\"КодУзла\": \"KZB\"\r\n}\r\n},\r\n\"Владелец\": {\r\n\"Ссылка\": \"7837d7b2-43cb-11e9-810e-ecb1d7734ef5\",\r\n\"Наименование\": \"ПАО \\\"Южный Кузбасс\\\"\",\r\n\"КодУзла\": \"KZB\"\r\n}\r\n},\r\n\"Владелец\": {\r\n\"Ссылка\": \"7837d7b2-43cb-11e9-810e-ecb1d7734ef5\",\r\n\"Наименование\": \"ПАО \\\"Южный Кузбасс\\\"\",\r\n\"КодУзла\": \"KZB\"\r\n}\r\n},\r\n\"Владелец\": {\r\n\"Ссылка\": \"7837d7b2-43cb-11e9-810e-ecb1d7734ef5\",\r\n\"Наименование\": \"ПАО \\\"Южный Кузбасс\\\"\",\r\n\"КодУзла\": \"KZB\"\r\n},\r\n\"НаименованиеПолное\": \"Буровой участок Отделение дегазации и геологоразведочных работ Департамент технического развития Управление по операционной деятельности\",\r\n\"Актуально\": true,\r\n\"Сформировано\": true,\r\n\"Расформировано\": false,\r\n\"ДатаСоздания\": \"2020-08-14T00:00:00\",\r\n\"ДатаРасформирования\": \"0001-01-01T00:00:00\",\r\n\"Руководитель\": \"\",\r\n\"РуководительФизлицо\": {\r\n\"Ссылка\": \"c402f1dc-32b0-11ea-80d0-20677cd5ee4b\",\r\n\"Наименование\": \"Ритиков Игорь Андреевич\",\r\n\"ДатаРождения\": \"1969-03-09T00:00:00\",\r\n\"ИНН\": \"\",\r\n\"СтраховойНомерПФР\": \"046-735-708 79\",\r\n\"Пол\": \"Мужской\",\r\n\"Код\": \"ЮК-0000003\"\r\n},\r\n\"Мечел_МестоРаботы\": {\r\n\"Ссылка\": \"\",\r\n\"Наименование\": \"\",\r\n\"Город\": \"\",\r\n\"ДистанционнаяРабота\": false\r\n},\r\n\"СменныеРуководители\": []\r\n}';
            $log->addData($key, '$key', __LINE__);
            $response = (new LogCacheController())->getLogJournalFromCache($key);
            $log->addLogAll($response);
            $json_encoded_arr = $response['Items'];

            foreach ($json_encoded_arr as $item) {
                if (property_exists($item, 'message')) {
                    $json = BackendAssistant::jsonRecoveryBy1C($item->message);
                    $result[] = BackendAssistant::jsonDecodeAmicum($json);
                }
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }


    // actionPushRabbitAd - метод отправки данных в очередь AD
    // пример: 127.0.0.1/admin/test/push-rabbit-ad
    public function actionPushRabbitAd()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionPushRabbitAd");

        try {

            $message_src = [
                'method' => 'get_user_info',
                'params' => [
                    'user_ldap' => 'MECHEL\DubinskySL',
                    'personGUID' => 'a8f1cb3e-2126-11ea-80cf-20677cd5ee4b',
                    'employeeGUID' => 'a8f1cb3e-2126-11ea-80cf-20677cd5ee4b'
                ]
            ];

            $payload = '<FixedStructure xmlns="http://v8.1c.ru/8.1/data/core" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<Property name="method">
<Value xsi:type="xs:string">get_user_info</Value>
</Property>
<Property name="params">
<Value xsi:type="Structure">
<Property name="user_ldap">
<Value xsi:type="xs:string">MECHEL\DubinskySL</Value>
</Property><Property name="personGUID">
<Value xsi:type="xs:string">a8f1cb3e-2126-11ea-80cf-20677cd5ee4b</Value>
</Property>
<Property name="employeeGUID">
<Value xsi:type="xs:string">a8f1cb3e-2126-11ea-80cf-20677cd5ee4b</Value>
</Property>
</Value>
</Property>
</FixedStructure>';

            $message = [
                'method' => 'get_user_info',
                'correlation_id' => time(),
                'payload' => $payload
            ];

            $config = [
                'host' => '10.0.18.136',
                'port' => 5672,
                'user' => 'ykuz_external',
                'password' => 'deukecrh',
                'exchangeName' => 'amicum.ad',
                'queueName' => 'rpc.it.sdesk',
                'replyQueueName' => 'amicum.ad',
                'vhost' => 'staging',
                'durable_queue' => 1,
                'auto_delete' => 4,
                'dsn' => 'amqp://ykuz_external:deukecrh@10.0.18.136:5672/staging',
            ];
            $log->addData($message_src, '$message_src', __LINE__);
            $log->addData($message, '$message', __LINE__);
            $log->addData($config, '$config', __LINE__);

            $rabbit = new RabbitController($config);
            $result = $rabbit->pushMessage($message, 0, 0, 1);


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    // actionRabGetObjectAd - метод отправки данных в метод get_object очереди rpc Rabbit
    // пример: 127.0.0.1/admin/test/rab-get-object-ad
    public function actionRabGetObjectAd()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionRabGetObjectAd");

        try {
            $payload = '<FixedStructure xmlns="http://v8.1c.ru/8.1/data/core" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<Property name="method">
<Value xsi:type="xs:string">get_object</Value>
</Property>
<Property name="params">
<Value xsi:type="Structure">
<Property name="user_ldap">
<Value xsi:type="xs:string">MECHEL\DubinskySL</Value>
</Property><Property name="personGUID">
<Value xsi:type="xs:string">a8f1cb3e-2126-11ea-80cf-20677cd5ee4b</Value>
</Property>
<Property name="employeeGUID">
<Value xsi:type="xs:string">a8f1cb3e-2126-11ea-80cf-20677cd5ee4b</Value>
</Property>
<Property name="GUID">
<Value xsi:type="xs:string">a8f1cb3e-2126-11ea-80cf-20677cd5ee4b</Value>
</Property>
<Property name="type">
<Value xsi:type="xs:string">user_info</Value>
</Property>
<Property name="send">
<Value xsi:type="xs:string">Да</Value>
</Property>
<Property name="queue_name">
<Value xsi:type="xs:string">user_info.СОУР.KZB</Value>
</Property>
</Value>
</Property>
</FixedStructure>';

            $message = [
                'method' => 'get_object',
                'correlation_id' => time(),
                'payload' => $payload
            ];

            $config = [
                'host' => '10.0.19.152',
                'port' => 5672,
                'user' => 'DataExchange',
                'password' => 'SLMRC3wCMZP7T6m',
                'exchangeName' => 'rpc.int.service.IT.SDESK',
                'queueName' => 'user_info.СОУР.KZB',
                'replyQueueName' => 'user_info.СОУР.KZB',
                'vhost' => 'async',
                'durable_queue' => 1,
                'auto_delete' => 4,
                'dsn' => 'amqp://DataExchange:SLMRC3wCMZP7T6m@10.0.19.152:5672/async',
            ];
            $log->addData($message, '$message', __LINE__);
            $log->addData($config, '$config', __LINE__);

            $rabbit = new RabbitController($config);
            $result = $rabbit->pushMessage($message, 0, 0, 1);


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }


//    public function xml_encode($array, $node = null)
//    {
    /*        if (!isset($node)) $node = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><note></note>');*/
//        foreach ($array as $key => $value) {
//            if (is_numeric($key))
//                $key = 'item' . $key;
//            if (is_array($value)) {
//                $subnode = $node->addChild($key);
//                $node = $this->xml_encode($array, $subnode);
//            } else {
//                $node->addChild($key, $value);
//            }
//        }
//        return $node->asXML();
//    }

    // actionTestFirebird - метод тестирования подключения к Firebird
    // пример: 127.0.0.1/admin/test/test-firebird
    public function actionTestFirebird()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionTestFirebird");
        try {

            $sql_query_source_column = "SELECT FIRST 10 SKIP 0 ID_STAFF, LAST_NAME, FIRST_NAME, MIDDLE_NAME, TABEL_ID, TEMPORARY_ACC, VALID, ACCESS_BEGIN_DATE, ACCESS_END_DATE, ACCESS_PROHIBIT, DEL_GUEST_AFTER_PASS, DATE_BEGIN, DATE_DISMISS, DELETED, ID_FROM_1C, STAFF_STATE, LAST_TIMESTAMP, DIN_PAY_SCHEMES_ID, DIN_GRAPHS_ID, DIN_GRANT_MEASURE_SPENDED, DIN_COST_VALUE_SPENDED, PATH_ACTDIR, PATH_ACTDIR_LOGIN, PATH_ACTDIR_DOMAIN, SHORT_FIO, FULL_FIO FROM STAFF";
            $result = Yii::$app->firebird->createCommand($sql_query_source_column)->queryAll();

//            var_dump($result);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    // actionSendMessage - метод для тестирования отправки текстовых сообщений с браузера
    // Разработал: Якимов М.Н.
    //пример вызова метода в браузере - http://10.36.59.202/admin/test/send-message?message=%22%D0%9F%D1%80%D0%B8%D0%B2%D0%B5%D1%82%22&list_phone_number=['+79333002774','+79059675355']

    public static function actionSendMessage()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $result = array();                                                                                              // Массив предупреждений
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $count_sends = -1;
        try {
            $warnings[] = 'actionSendMessage. начал выполнять метод';
            $post = Assistant::GetServerMethod();
            /**
             * Проверка наличия правильности полученных полей
             */
            if (isset($post['message']) and $post['message'] != "" and
                isset($post['list_phone_number']) and $post['list_phone_number'] != ""
            ) {
                $message = $post['message'];
                $list_phone_number = json_decode($post['list_phone_number']);
            } else {
                throw new Exception('actionSendMessage. переменная message или list_phone_number не существуют');
            }

            /**
             * проверка наличия массива. Если не массив, то ошибка
             */

            if (!is_array($list_phone_number)) {
                throw new Exception('actionSendMessage. Входной набор списка номеров не массив');
            }

            /**
             * Блок вызова метода для отправки текстовых сообщений
             */
            $response = SmsSender::SendMessage($message, $list_phone_number);

            /**
             * Блок стандартных проверок
             */
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $count_sends = $response['count_sends'];

            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('actionSendMessage. Ошибка при отправке текстовых сообщений ');
            }
        } catch (Throwable $exception) {
            $warnings[] = 'actionSendMessage. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = 'actionSendMessage. Закончил выполнять метод';
        $result_main = array('Items' => $count_sends, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод для проверки генерации записи о нахождении человека в запретной зоне
     * @return void
     * @example 127.0.0.1/admin/test/check-forbidden-zone-status
     */
    public static function actionCheckForbiddenZoneStatus()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionCheckForbiddenZoneStatus");
        try {

            $date_now = Assistant::GetDateTimeNow();
            $main_obj = WorkerObject::find()
                ->with('worker.employee')
                ->where(['worker_id' => 1])
                ->limit(1)
                ->one();
            $main_title = $main_obj->worker->employee->last_name . ' ' . $main_obj->worker->employee->first_name . ' ' . $main_obj->worker->employee->patronymic;
            $table_name = 'worker_object';

            $response = HorizonController::checkForbiddenZoneStatus(
                $date_now,
                "2:test",
                StatusEnumController::FORBIDDEN,
                $main_obj,
                $main_title,
                $table_name,
                210854,
                339344,
            );
            $log->addLogAll($response);

            $date_now = date("Y-m-d H:i:s", strtotime($date_now . "+1 min"));

            $response = HorizonController::checkForbiddenZoneStatus(
                $date_now,
                "2:test",
                StatusEnumController::PERMITTED,
                $main_obj,
                $main_title,
                $table_name,
                210854,
                339344,
            );
            $log->addLogAll($response);


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionGetShiftDateNum - Метод получения смены по дате
     * @return void
     * @example 127.0.0.1/admin/test/get-shift-date-num?date=2022-10-01 05:00:00
     */
    public static function actionGetShiftDateNum()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionCheckForbiddenZoneStatus");
        try {
            $post = Assistant::GetServerMethod();
            $date_time = $post['date'];

            $result = StrataJobController::getShiftDateNum($date_time);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionGetSizeRabbitQuery - Метод получения размера очереди
     * @return void
     * @example 127.0.0.1/admin/test/get-size-rabbit-query
     */
    public static function actionGetSizeRabbitQuery()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionGetSizeRabbitQuery");
        try {

            $result = (new RedisQueueController())->SizeQuery();

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionPopRabbitQuery - Метод получения очереди сообщений по расчету времени нахождения в шахте
     * @return void
     * @example 127.0.0.1/admin/test/pop-rabbit-query
     */
    public static function actionPopRabbitQuery()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionPopRabbitQuery");
        try {

            $response = (new RedisQueueController())->PullFromQuery();
            $log->addLogAll($response);
            $result['src'] = $response['Items'];
            $result['json_decode'] = json_decode($response['Items']);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionPushToQuery - Метод отправки тестового сообщения
     * @return void
     * @example 127.0.0.1/admin/test/push-to-query
     */
    public static function actionPushToQuery()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionPushToQuery");
        try {

            $cache = new RedisQueueController();
            $response = $cache->PushToQuery('TEST');

            $result = $response;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionGetWorkingHoursByWorkerId - Метод получения пакета времени нахождения человека на смене
     * @return void
     * @example 127.0.0.1/admin/test/get-working-hours-by-worker-id?worker_id=1000522
     */
    public static function actionGetWorkingHoursByWorkerId()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionGetWorkingHoursByWorkerId");
        try {
            $post = Assistant::GetServerMethod();

            $worker_id = $post['worker_id'];

            $cache = new WorkerCacheController();
            $result = $cache->getWorkingHours($worker_id);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionCleanWorkingHoursByWorkerId - Метод очистки пакета времени нахождения человека на смене
     * @return void
     * @example 127.0.0.1/admin/test/clean-working-hours-by-worker-id?worker_id=1000522
     */
    public static function actionCleanWorkingHoursByWorkerId()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionCleanWorkingHoursByWorkerId");
        try {
            $post = Assistant::GetServerMethod();

            $worker_id = $post['worker_id'];

            $cache = new WorkerCacheController();
            $result = $cache->cleanWorkingHours($worker_id);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionSetWorkingHoursByWorkerId - Метод сохранения пакета времени нахождения человека на смене
     * @return void
     * @example 127.0.0.1/admin/test/set-working-hours-by-worker-id?worker_id=1000522&value="gggg"
     */
    public static function actionSetWorkingHoursByWorkerId()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionSetWorkingHoursByWorkerId");
        try {
            $post = Assistant::GetServerMethod();

            $worker_id = $post['worker_id'];
            $value = $post['value'];

            $cache = new WorkerCacheController();
            $result = $cache->setWorkingHours($worker_id, $value);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionCreateTestMovementWorker - Метод создания тестового маршрута для шахты 290
     * @return void
     * @example 127.0.0.1/admin/test/create-test-movement-worker?worker_id=63918&date_start=2023-01-02&sensor_id=1172199
     */
    public static function actionCreateTestMovementWorker()
    {
        $result = null;
        $log = new LogAmicumFront("actionCreateTestMovementWorker");
        try {
            $post = Assistant::GetServerMethod();

            $date_start = $post['date_start'];
            $worker_sensor['worker_id'] = $post['worker_id'];
            $worker_sensor['sensor_id'] = $post['sensor_id'];

            $scenario = [
                '00:00:00' => ['LocationPacket' => ['xyz' => '12212,-89.00494,-7524.071', 'edge_id' => 337352, 'place_id' => 210968, 'status_danger_zone' => 16, 'place_object_id' => 10, 'speed' => ['speed_value' => 1, 'generate_event' => true]]],
                '00:05:00' => ['LocationPacket' => ['xyz' => '12282.92,-89.35448,-7529.096', 'edge_id' => 337352, 'place_id' => 210968, 'status_danger_zone' => 16, 'place_object_id' => 10, 'speed' => ['speed_value' => 1.2, 'generate_event' => true]]],
                '00:06:00' => ['Chemical' => ['xyz' => '12282.92,-89.35448,-7529.096', 'edge_id' => 337352, 'place_id' => 210968, 'status_danger_zone' => 16, 'parameter_id' => ParamEnum::GAS_LEVEL_CO, 'meterage' => 0.0020, 'status_id' => StatusEnumController::EMERGENCY_VALUE, 'parameter_excess_id' => ParamEnum::GAS_EXCESS_CO, 'is_gas_excess' => 1, 'event_id' => EventEnumController::CO_EXCESS_LAMP]],
                '00:10:00' => ['LocationPacket' => ['xyz' => '12398.68,-89.97366,-7544.328', 'edge_id' => 337358, 'place_id' => 210968, 'status_danger_zone' => 16, 'place_object_id' => 10, 'speed' => ['speed_value' => 6, 'generate_event' => true]]],
                '00:11:00' => ['Chemical' => ['xyz' => '12398.68,-89.97366,-7544.328', 'edge_id' => 337358, 'place_id' => 210968, 'status_danger_zone' => 16, 'parameter_id' => ParamEnum::GAS_LEVEL_CH4, 'meterage' => 2, 'status_id' => StatusEnumController::EMERGENCY_VALUE, 'parameter_excess_id' => ParamEnum::GAS_EXCESS_CH4, 'is_gas_excess' => 1, 'event_id' => EventEnumController::CH4_EXCESS_LAMP]],
                '00:20:00' => ['LocationPacket' => ['xyz' => '12585.53,-90.64577,-7635.637', 'edge_id' => 337370, 'place_id' => 210968, 'status_danger_zone' => 15, 'place_object_id' => 10, 'speed' => ['speed_value' => 1.5, 'generate_event' => true]]],
                '00:21:00' => ['Chemical' => ['xyz' => '12585.53,-90.64577,-7635.637', 'edge_id' => 337370, 'place_id' => 210968, 'status_danger_zone' => 16, 'parameter_id' => ParamEnum::GAS_LEVEL_O2, 'meterage' => 20, 'status_id' => StatusEnumController::NORMAL_VALUE, 'parameter_excess_id' => ParamEnum::GAS_EXCESS_O2, 'is_gas_excess' => 1, 'event_id' => EventEnumController::O2_EXCESS_LAMP]],
                '00:35:00' => ['LocationPacket' => ['xyz' => '12734.81,-91.24138,-7675.362', 'edge_id' => 337376, 'place_id' => 210968, 'status_danger_zone' => 15, 'place_object_id' => 10, 'speed' => ['speed_value' => 1.8, 'generate_event' => true]]],
                '00:50:00' => ['Chemical' => ['xyz' => '12790,-91.5,-7656', 'edge_id' => 337394, 'place_id' => 210968, 'status_danger_zone' => 16, 'parameter_id' => ParamEnum::GAS_LEVEL_CO2, 'meterage' => 0.6, 'status_id' => StatusEnumController::EMERGENCY_VALUE, 'parameter_excess_id' => ParamEnum::GAS_EXCESS_CO2, 'is_gas_excess' => 1, 'event_id' => EventEnumController::CO2_EXCESS_LAMP]],
                '00:55:00' => ['LocationPacket' => ['xyz' => '12825.3,-91.5,-7666.59', 'edge_id' => 337394, 'place_id' => 210968, 'status_danger_zone' => 16, 'place_object_id' => 10, 'speed' => ['speed_value' => 0.8, 'generate_event' => true]]],
            ];
//            $date_start = "2023-01-02";
//            $worker_sensor['worker_id'] = 63918;
            $log = self::CreateTestMovementWorker($date_start, $worker_sensor, $scenario);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    public static function CreateTestMovementWorker($date_start, $worker_sensor, $scenario)
    {
        $mine_id = 290;
        foreach ($scenario as $date => $event) {
            if (isset($event['LocationPacket'])) {
                $xyz = $event['LocationPacket']['xyz'];
                $edge_id = $event['LocationPacket']['edge_id'];
                $place_id = $event['LocationPacket']['place_id'];
                $status_danger_zone = $event['LocationPacket']['status_danger_zone'];
                $place_object_id = $event['LocationPacket']['place_object_id'];
                $speed = $event['LocationPacket']['speed'];
                $response = HorizonController::saveLocationPacketWorkerParameters("$date_start $date", $worker_sensor, $mine_id, $xyz, $edge_id, $place_id, $status_danger_zone, $place_object_id, $speed);
            }
            if (isset($event['Chemical'])) {
                $package['measurementTime'] = "$date_start $date";
                $package['meterage'] = $event['Chemical']['meterage'];
                $xyz = $event['Chemical']['xyz'];
                $edge_id = $event['Chemical']['edge_id'];
                $place_id = $event['Chemical']['place_id'];
                $status_danger_zone = $event['Chemical']['status_danger_zone'];
                $parameter_id = $event['Chemical']['parameter_id'];
                $status_id = $event['Chemical']['status_id'];
                $parameter_excess_id = $event['Chemical']['parameter_excess_id'];
                $is_gas_excess = $event['Chemical']['is_gas_excess'];
                $event_id = $event['Chemical']['event_id'];
                $response = HorizonController::saveEnvironmentalPacketWorkerParameters((object)$package, $mine_id, $worker_sensor, $status_danger_zone, $xyz, $place_id, $edge_id, $parameter_id, $status_id, $parameter_excess_id, $is_gas_excess, $event_id);
            }
        }
    }

    /**
     * actionMultiCreateTestMovementWorker - Метод массового создания тестового маршрута для шахты 290
     * @return void
     * @example 127.0.0.1/admin/test/multi-create-test-movement-worker?count=400&date_start=2023-01-02
     */
    public static function actionMultiCreateTestMovementWorker()
    {
        $result = null;
        $log = new LogAmicumFront("actionMultiCreateTestMovementWorker");
        try {
            $post = Assistant::GetServerMethod();

            $date_start = $post['date_start'];
            $count = $post['count'];

            $worker_sensors = (new Query())
                ->select([
                    'worker_id',
                    'sensor_id'
                ])
                ->from('view_worker_constant_lamp')
                ->innerJoin('worker_parameter', 'worker_parameter.id = view_worker_constant_lamp.worker_parameter_id')
                ->innerJoin('worker_object', 'worker_object.id = worker_parameter.worker_object_id')
                ->limit($count)
                ->all();

            foreach ($worker_sensors as $worker_sensor) {
                $xyz = random_int(-10000, 10000).','.random_int(-100, 100).','.random_int(-10000, 10000);
                $scenario = ['00:00:00' => ['LocationPacket' => ['xyz' => $xyz, 'edge_id' => 337352, 'place_id' => 210968, 'status_danger_zone' => 16, 'place_object_id' => 10, 'speed' => ['speed_value' => 1, 'generate_event' => true]]]];
                self::CreateTestMovementWorker($date_start, $worker_sensor, $scenario);
            }
            $result = $worker_sensors;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionFixExaminationAnswerTyTestQuestionIdIsNull - Метод заполнения поля test_question_id в таблице examination_answer
     * @return void
     * @example 127.0.0.1/admin/test/fix-examination-answer-ty-test-question-id-is-null
     */
    public static function actionFixExaminationAnswerTyTestQuestionIdIsNull()
    {
        $result = null;
        $log = new LogAmicumFront("actionFixExaminationAnswerTyTestQuestionIdIsNull");

        try {

            $examinations_answers = ExaminationAnswer::find()
                ->where('examination_answer.test_question_id=0')
                ->joinWith('testQuestionAnswer')
                ->all();

            foreach ($examinations_answers as $examination_answer) {
                $examination_answer->test_question_id = $examination_answer->testQuestionAnswer->test_question_id;
                if (!$examination_answer->save()) {
                    $log->addData($examination_answer->errors, '$examination_answer->errors', __LINE__);
                    throw new Exception("Ошибка сохранения модели ExaminationAnswer");
                }
                $result[$examination_answer->id] = $examination_answer;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }
}
