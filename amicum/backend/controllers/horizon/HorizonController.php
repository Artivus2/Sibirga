<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\horizon;

use backend\controllers\Assistant;
use backend\controllers\cachemanagers\EquipmentCacheController;
use backend\controllers\cachemanagers\LogCacheController;
use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\cachemanagers\ServiceCache;
use backend\controllers\cachemanagers\WorkerCacheController;
use backend\controllers\const_amicum\EventEnumController;
use backend\controllers\const_amicum\ParamEnum;
use backend\controllers\const_amicum\ParameterTypeEnumController;
use backend\controllers\const_amicum\StatusEnumController;
use backend\controllers\CoordinateController;
use backend\controllers\EdgeMainController;
use backend\controllers\EquipmentMainController;
use backend\controllers\EventMainController;
use backend\controllers\LogAmicum;
use backend\controllers\OpcController;
use backend\controllers\queuemanagers\RedisQueueController;
use backend\controllers\SensorMainController;
use backend\controllers\StrataJobController;
use backend\controllers\WorkerMainController;
use Exception;
use frontend\controllers\positioningsystem\ForbiddenZoneController;
use frontend\controllers\reports\SummaryReportEndOfShiftController;
use frontend\controllers\system\LogAmicumFront;
use frontend\controllers\WebsocketController;
use frontend\models\Equipment;
use frontend\models\Sensor;
use frontend\models\Worker;
use frontend\models\WorkerObject;
use Throwable;
use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * Класс работы с системой Горизонт фирмы Талнах
 */
class HorizonController extends Controller
{

    // метод подключения к вебсокету
    // метод получения данных с вебсокета
    // метод записи данных в очередь по id считывателя
    // метод инициализации вебсокет клиента
    // метод проверки статуса вебсокет клиента

    /** МЕТОДЫ СЛУЖБЫ ГОРИЗОНТ */
    // SavePackage()                                    - Центральный метод обработки пакетов службы горизонта
    // saveHeartbeatPacket()                            - Функция сохранения параметров Heartbeat сообщений
    // saveLocationPacket()                             - Метод сохранения местоположения (координат) и места (edge) работника и датчика.
    // saveLocationPacketSensorParameters()             - Сохраняет параметры сенсора, полученные из пакета положения шахтёра.
    // saveLocationPacketWorkerParameters()             - Сохраняет параметры воркера, полученные из пакета положения шахтёра.
    // saveLocationPacketEquipmentParameters()          - Сохраняет параметры оборудования, полученные из пакета положения шахтёра.
    // checkForbiddenZoneStatus()                       - Проверяет, находится ли объект в запретной зоне.
    // saveChemicalPacket                               - Метод сохранения газов
    // saveEnvironmentalPacketSensorParameters          - Сохранение параметров сенсора из пакета газов

    /** МЕТОДЫ УПРАВЛЕНИЯ */
    // actionSavePackage()                      - Центральный метод обработки пакетов службы горизонта
    // actionGenerateNodeByQuery()              - Метод генерации узлов связи/считывателей на основе очередей
    // actionInitSensorNetworkFromDb()          - Метод тестирования инициализации сенсора по сетевому адресу
    // actionGetPackageFromQueue()              - Метод получения данных из очереди
    // actionGetLogParamHorizon                 - Метод получения данных параметров Horizon


    /** ТЕСТОВЫЕ МЕТОДЫ УПРАВЛЕНИЯ (В СИСТЕМЕ НЕ ИСПОЛЬЗУЮТСЯ) */
    // actionStartWebSocketClientHorizon        - Метод запуска клиента получения данных с сервера системы горизонт
    // actionStatusClientHorizon                - Метод запуска клиента получения данных с сервера системы горизонт
    // actionStartSignalRClientHorizon          - Метод запуска клиента получения данных с сервера R системы горизонт

    const STATUS_IN_MINE = 1;
    const STATUS_OUT_MINE = 0;

    const SENDING_TO_RABBIT = 1;
    const SENDING_TO_REDIS = 0;

    const COMPANY_LINK_1C = '7837d7b2-43cb-11e9-810e-ecd1d7734ef5';
    const COMPANY_TITLE = 'ПАО "Южный Кузбасс"';

    const SAVE_ALL_LOG = false;

    /**
     * SavePackage - Центральный метод обработки пакетов службы горизонта
     * @param $package - пакет
     * @param $net_id - сетевой адрес считывателя
     * @param $mine_id - ключ шахтного поля
     */
    public static function SavePackage($package, $net_id, $mine_id)
    {
        $log = new LogAmicumFront("SavePackage");
        try {
            switch ($package->package_type) {
                case 'DeviceStatus':                                                                                    // хардбит узла связи
                    $log->addLog("Пакет состояния узла связи");

                    $response = self::saveHeartbeatPacket($package, $net_id, $mine_id);
                    $log->addLogAll($response);
                    break;
                case 'LocatorPositioning':                                                                              // локация
                    $log->addLog("Пакет локации");

                    $log->addData($package, 'Пакет после расшифровки $package: ', __LINE__);
                    $result = self::saveLocationPacket($package, $net_id, $mine_id);
                    $log->addLogAll($result);
                    $flag = $result['flag']; // 1- человек // 2- оборудование
                    $log->addLog("Обработал локацию: " . $flag);
                    break;
                case 'Chemical':                                                                                        // пакет с газами
                    $log->addLog("Пакет газов");

                    $response = self::saveChemicalPacket($package, $net_id, $mine_id);
                    $log->addLogAll($response);
                    break;
            }
        } catch (Exception $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
            if (self::SAVE_ALL_LOG){
                $logs = $log->getLogAll();
            } else {
                $logs['errors'] = $log->getErrors();
            }
            $data_to_log_cache = array_merge(['Items' => []], $logs);
//            LogCacheController::setStrataLogValue('SavePackage', $data_to_log_cache, 4);
            LogCacheController::setStrataTypeLogValue('SavePackage', $data_to_log_cache, $package->package_type.$net_id.':'.$ex->getMessage());
        }

        return array_merge(['Items' => []], $log->getLogAll());
    }


    /**
     * actionSavePackage - Центральный метод обработки пакетов службы горизонта
     * @param $package - пакет
     * @param $net_id - сетевой адрес считывателя
     * @param $mine_id - ключ шахтного поля
     * @example 127.0.0.1/admin/horizon/horizon/save-package?net_id=2:832&mine_id=290&package={"localTime":"2021-09-24T22:32:06.1492258+07:00","package_type":"DeviceStatus","state":"Online","name":"832","type":"LSR"}
     * @example 127.0.0.1/admin/horizon/horizon/save-package?net_id=2:832&mine_id=290&package={"locatorNum":7213,"levelName":"Шахта [-150]","lampNum":624,"vectorId":206,"vectorName":"В.Ш. 3-1-13 пк.160 - В.Ш. 3-1-13 пк.180","speed":3.6,"staffFullName":"Васкан Андрей Викторович [624]","time":"2021-09-27T06:09:11.1519226Z","localTime":"2021-09-27T13:09:11.1519226+07:00","vectorLength":200.0,"distance":5.94,"procentPosition":0.0297,"timeMode":0.0,"timeModeLevelControl":"","hash":"Васкан Андрей Викторович [624]-206-5,94","id":0,"editTime":"0001-01-01T00:00:00","isEnabled":true,"isDeleted":false,"comment":"{ \"ReaderNumBegin\" : 820, \"ReaderNumEnd\" : 832 }","nodes":{"ReaderNumBegin":820,"ReaderNumEnd":832},"mine_id":"290","package_type":"LocatorPositioning"}
     * @example 127.0.0.1/admin/horizon/horizon/save-package?net_id=2:832&mine_id=290&package={"locatorNum":7094,"lselIp":"10.0.1.1","levelName":"Поверхность [10.0.1.1]","staffFullName":"Покидышев Леонид Вячеславович [428]","time":"2021-09-29T08:15:37.1550743Z","localTime":"2021-09-29T15:15:37.1550743+07:00","distance":0.0,"procentPosition":"NaN","timeMode":0.0,"timeModeLevelControl":"","hash":"Покидышев Леонид Вячеславович [428]--","id":0,"editTime":"0001-01-01T00:00:00","isEnabled":true,"isDeleted":false,"comment":"{ \"ReaderNumBegin\" : 257, \"ReaderNumEnd\" : null }","nodes":{"ReaderNumBegin":257,"ReaderNumEnd":-1},"mine_id":"290","package_type":"LocatorPositioning"}
     * @example ШАХТА http://127.0.0.1/admin/horizon/horizon/save-package?net_id=17043&mine_id=290&package={%22locatorNum%22:8780,%22lselIp%22:%2210.0.1.1%22,%22levelName%22:%22%D0%9F%D0%BE%D0%B2%D0%B5%D1%80%D1%85%D0%BD%D0%BE%D1%81%D1%82%D1%8C%20[10.0.1.1]%22,%22staffFullName%22:%22%D0%9F%D0%BE%D0%BA%D0%B8%D0%B4%D1%8B%D1%88%D0%B5%D0%B2%20%D0%9B%D0%B5%D0%BE%D0%BD%D0%B8%D0%B4%20%D0%92%D1%8F%D1%87%D0%B5%D1%81%D0%BB%D0%B0%D0%B2%D0%BE%D0%B2%D0%B8%D1%87%20[428]%22,%22time%22:%222023-03-28%2008:27:06.1492258%22,%22localTime%22:%222021-09-24T22:32:06.1492258+07:00%22,%22distance%22:0.0,%22procentPosition%22:%22NaN%22,%22timeMode%22:0.0,%22timeModeLevelControl%22:%22%22,%22hash%22:%22%D0%9F%D0%BE%D0%BA%D0%B8%D0%B4%D1%8B%D1%88%D0%B5%D0%B2%20%D0%9B%D0%B5%D0%BE%D0%BD%D0%B8%D0%B4%20%D0%92%D1%8F%D1%87%D0%B5%D1%81%D0%BB%D0%B0%D0%B2%D0%BE%D0%B2%D0%B8%D1%87%20[428]--%22,%22id%22:0,%22editTime%22:%220001-01-01T00:00:00%22,%22isEnabled%22:true,%22isDeleted%22:false,%22comment%22:%22{%20\%22ReaderNumBegin\%22%20:%20257,%20\%22ReaderNumEnd\%22%20:%20null%20}%22,%22nodes%22:{%22ReaderNumBegin%22:257,%22ReaderNumEnd%22:-1},%22mine_id%22:%22290%22,%22package_type%22:%22LocatorPositioning%22}
     * @example Ламповая 127.0.0.1/admin/horizon/horizon/save-package?net_id=8048&mine_id=290&package={"locatorNum":8780,"lselIp":"10.0.1.1","levelName":"Поверхность [10.0.1.1]","staffFullName":"Покидышев Леонид Вячеславович [428]","time":"2023-03-28 08:27:06.1492258","localTime":"2023-03-28T08:27:06.1492258+07:00","distance":0.0,"procentPosition":"NaN","timeMode":0.0,"timeModeLevelControl":"","hash":"Покидышев Леонид Вячеславович [428]--","id":0,"editTime":"0001-01-01T00:00:00","isEnabled":true,"isDeleted":false,"comment":"{ \"ReaderNumBegin\" : 257, \"ReaderNumEnd\" : null }","nodes":{"ReaderNumBegin":257,"ReaderNumEnd":-1},"mine_id":"290","package_type":"LocatorPositioning"}
     * @example http://127.0.0.1/admin/horizon/horizon/save-package?net_id=2:737&mine_id=290&package={%22num%22:0,%22threshold%22:100,%22preThreshold%22:100,%22meterage%22:0,%22substance%22:%22CO2%22,%22locatorNum%22:8780,%22package_type%22:%22Chemical%22,%22measurementTime%22:%222022-08-01T05:28:01.1387591Z%22,%22dataSource%22:%22GasAnalyser%22,%22id%22:0,%22editTime%22:%220001-01-01T00:00:00%22,%22isEnabled%22:true,%22isDeleted%22:false,%22staffHistory%22:{%22locatorNum%22:8780,%22vectorId%22:206,%22vectorName%22:%22%D0%92.%D0%A8.%203-1-13%20%D0%BF%D0%BA.160%20-%20%D0%92.%D0%A8.%203-1-13%20%D0%BF%D0%BA.180%22,%22speed%22:0,%22staffFullName%22:%22[%D0%93%D0%90%20(6)]%22,%22time%22:%222022-08-01T05:28:01.1387591Z%22,%22localTime%22:%222022-08-01T12:28:01.1387591+07:00%22,%22vectorLength%22:200,%22distance%22:56.25,%22procentPosition%22:%220.2812%22,%22hash%22:%22[%D0%93%D0%90%20(6)]-206-56,25%22,%22id%22:0,%22editTime%22:%220001-01-01T00:00:00%22,%22isEnabled%22:true,%22isDeleted%22:false,%22comment%22:%22{%20Type:%20\%22%D0%93%D0%90\%22,%20ReaderNumBegin:%20820,%20ReaderNumEnd:%20832}%22,%22nodes%22:{%22ReaderNumBegin%22:-1,%22ReaderNumEnd%22:-1,%22Type%22:%22none%22},%22mine_id%22:%22%22,%22package_type%22:%22LocatorPositioning%22},%22comment%22:%22{\%22LocatorNum\%22:8780,\%22LselIp\%22:null,\%22LevelName\%22:null,\%22LampNum\%22:null,\%22VectorId\%22:206,\%22VectorName\%22:null,\%22Speed\%22:0.0,\%22StaffFullName\%22:null,\%22Time\%22:\%222022-08-01T05:28:01.1387591Z\%22,\%22LocalTime\%22:\%222022-08-01T12:28:01.1387591+07:00\%22,\%22Geom\%22:null,\%22VectorLength\%22:null,\%22Distance\%22:56.25,\%22ProcentPosition\%22:\%22Infinity\%22,\%22TimeMode\%22:null,\%22TimeModeLevelControl\%22:null,\%22Hash\%22:\%22-206-56,25\%22,\%22ChemicalHistories\%22:null,\%22Id\%22:0,\%22EditTime\%22:\%220001-01-01T00:00:00\%22,\%22LastEditor\%22:null,\%22IsEnabled\%22:true,\%22IsDeleted\%22:false,\%22Comment\%22:null}%22,%22mine_id%22:%222%22,%22nodes%22:{%22ReaderNumBegin%22:820,%22ReaderNumEnd%22:832,%22Type%22:%22%D0%93%D0%90%22}}
     */
    public function actionSavePackage()
    {
        $log = new LogAmicumFront("actionSavePackage");
        try {
            $post = Assistant::GetServerMethod();
            $log->addData($post, '$post', __LINE__);
            $package = $post['package'];
            $net_id = $post['net_id'];
            $mine_id = $post['mine_id'];
            $response = self::SavePackage(json_decode($package), $net_id, $mine_id);
            $log->addLogAll($response);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => []], $log->getLogAll());
    }

    /**
     * actionStartWebSocketClientHorizon - Метод запуска клиента получения данных с сервера системы горизонт
     * ПРЯМОЙ ЗАПРОС НА ВЕБСОКЕТ
     * Вызов: 127.0.0.1/admin/horizon/horizon/start-web-socket-client-horizon
     */
    public function actionStartWebSocketClientHorizon()
    {
        $log = new LogAmicumFront("actionStartWebSocketClientHorizon");

        try {
//            ini_set('max_execution_time', -1);
            $horizon = new Horizon();
            $horizon->initClient();
            $response = $horizon->receiveMessage();
            $log->addLogAll($response);
        } catch (Exception $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => []], $log->getLogAll());
    }

    /**
     * actionStatusClientHorizon - Метод запуска клиента получения данных с сервера системы горизонт
     * ПРЯМОЙ ЗАПРОС НА ВЕБСОКЕТ
     * Вызов: 127.0.0.1/admin/horizon/horizon/status-client-horizon
     */
    public function actionStatusClientHorizon()
    {
        $log = new LogAmicumFront("actionStatusClientHorizon");
        $status = null;
        try {
            $horizon = new Horizon();               // TODO переписать на получение статуса из редиса
            $status = $horizon->getIsConnect();
            $log->addData($status, '$status', __LINE__);
        } catch (Exception $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $status], $log->getLogAll());
    }

    /**
     * actionStartSignalRClientHorizon - Метод запуска клиента получения данных с сервера R системы горизонт
     * ПОДПИСКА НА ВЕБСОКЕТ
     * Вызов: 127.0.0.1/admin/horizon/horizon/start-signal-r-client-horizon
     */
    public function actionStartSignalRClientHorizon()
    {
        $log = new LogAmicumFront("actionStartSignalRClientHorizon");

        try {
//            ini_set('max_execution_time', -1);
            $horizon = new Horizon();
            $horizon->initClientR("http://horizon", ["hardware"]);
            $response = $horizon->run();
            $log->addLogAll($response);

        } catch (Exception $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => []], $log->getLogAll());
    }

    /**
     * saveHeartbeatPacket - Функция сохранения параметров Heartbeat сообщений
     * @param $package - пакет на сохранение
     * @param $net_id - сетевой адрес считывателя
     * @param $mine_id - ключ шахтного поля
     * @return array
     */
    public static function saveHeartbeatPacket($package, $network_id, $mine_id)
    {
        $log = new LogAmicumFront("saveHeartbeatPacket");

        try {
            $log->addLog("Начало выполнения метода");

            /**=================================================================
             * Получаем из кеша сенсор айди по нетворк айди
             * ==================================================================*/
            $response = SensorMainController::getOrSetSensorByNetworkId($network_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("saveHeartbeatPacket. Ошибка при инициализации сенсора по сетевому адресу: " . $network_id);
            }

            $log->addLog("Успешно закончил поиск в кеше и БД ключа сенсора по нет айд");
            if ($response['sensor_id'] === false) {
                $title = 'Линейный считыватель networkID ' . $network_id;
                $response = StrataJobController::createSensorDatabase($title, $network_id, $mine_id, 105, 31, 11);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception("Ошибка создания сенсора по нетворк айди $network_id в кеше и в БД");
                }
            }
            $sensor_id = $response['sensor_id'];

            $log->addLog("Найден sensor_id: $sensor_id по network_id: $network_id и проинициализирован сенсор в кеш");

            /**=================================================================
             * Инициализация кеша сенсора SensorMine
             * ==================================================================*/
            if ((new SensorCacheController())->getSensorMineBySensorHash($sensor_id) === false) {
                $response = SensorMainController::initSensorInCache($sensor_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка при инициализации сенсора');
                }
            }

            $log->addLog("Проинициализирован сенсор в кеше SensorMine");

            /**=================================================================
             * Сохранение параметров
             * ==================================================================*/
            $sensor_cache_controller = new SensorCacheController();

            /**
             * Получаем за раз все последние значения по сенсору из кеша
             */
            $sensor_parameter_value_list_cache = $sensor_cache_controller->multiGetParameterValueHash($sensor_id, '*', '*');

            if ($sensor_parameter_value_list_cache !== false) {
                foreach ($sensor_parameter_value_list_cache as $sensor_parameter_value_cache) {
                    $sensor_parameter_value_cache_array[$sensor_parameter_value_cache['sensor_id']][$sensor_parameter_value_cache['parameter_type_id']][$sensor_parameter_value_cache['parameter_id']] = $sensor_parameter_value_cache;
                }
            } else {
                $sensor_parameter_value_cache_array = null;
            }

            $log->addLog("получил за раз все последние значения по сенсору из кеша");

            if ($package->state == "Online") {
                $state_value = 1;
            } else if ($package->state == "Offline") {
                $state_value = 0;
            } else {
                $state_value = -1;
            }

            //Сохранение состояния узла
            $response = StrataJobController::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::CALCULATED, ParamEnum::STATE, $state_value, $package->localTime, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения параметра ' . ParamEnum::STATE);
            }

            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }

            $log->addLog("Подготовка к сохранение состоянию узла");

            /**=================================================================
             * блок массовой вставки значений в БД
             * =================================================================*/
            if (isset($date_to_db)) {
                Yii::$app->db_amicum2->createCommand('SET SESSION wait_timeout = 28800;')->execute();
                Yii::$app->db_amicum2->createCommand()->batchInsert('sensor_parameter_value', ['sensor_parameter_id', 'date_time', 'value', 'status_id'], $date_to_db)->execute();
//                foreach ($date_to_db as $date) {
//                    $spv_new = new SensorParameterValue();
//                    $spv_new->sensor_parameter_id = $date['sensor_parameter_id'];
//                    $spv_new->date_time = $date['date_time'];
//                    $spv_new->value = (string)$date['value'];
//                    $spv_new->status_id = $date['status_id'];
//                    if (!$spv_new->save()) {
//                        $log->addData($spv_new->errors, '$spv_new->errors', __LINE__);
//                        throw new Exception('Ошибка сохранения значения параметра в БД');
//                    }
//                }
//                $insert_param_val = Yii::$app->db_amicum2->createCommand()->batchInsert('sensor_parameter_value', ['sensor_parameter_id', 'date_time', 'value', 'status_id'], $date_to_db)->execute();
//                $log->addLog("Вставил: $insert_param_val");
            }
            $log->addLog("Закончил массовую вставку значений в БД");

            /**
             * блок массовой вставки значений в кеш
             */
            if (isset($date_to_cache)) {
                $ask_from_method = (new SensorCacheController)->multiSetSensorParameterValueHash($date_to_cache);
                if ($ask_from_method['status'] != 1) {
                    throw new Exception('Не смог обновить параметры в кеше сенсора ' . $sensor_id);
                }
            }
            $log->addLog("Закончил массовую вставку значений в кеш");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());

            $errors['Method parameters'] = [
                'pack' => $package,
                'mine_id' => $mine_id,
                'errors' => $log->getErrors()
            ];
            $data_to_log_cache = $log->getLogAll();
            if (self::SAVE_ALL_LOG){
                $data_to_log_cache = $log->getLogAll();
            } else {
                $data_to_log_cache['Method parameters'] = $errors;
            }
            //LogCacheController::setStrataLogValue('saveHeartbeatPacket', $data_to_log_cache, '2');
            //LogAmicum::LogAmicumStrata("saveHeartbeatPacket", $package, $log->getWarnings(), $errors);
            LogCacheController::setStrataTypeLogValue('saveHeartbeatPacket', $data_to_log_cache, $network_id.':'.$ex->getMessage());
        }
        $log->addLog("Окончил выполнение метод");

        return array_merge(['Items' => []], $log->getLogAll());
    }


    /**
     * saveLocationPacket - Метод сохранения местоположения (координат) и места (edge) работника и датчика.
     * @param $package - Пакет местоположения шахтёра
     * @param int $mine_id Идентификатор шахты
     * @return array
     */
    public static function saveLocationPacket($package, $gateway_network_id, $mine_id)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("saveLocationPacket");
        $flag = 0;
        $count_all = 0;                                                                                                 // количество полученных записей
        $edge_id = -1;
        //состояние выполнения метода
        try {
            $log->addLog("Начало выполнения метода");

            $sensor_cache_controller = new SensorCacheController();

            $package->localTime = Assistant::repairDate($package->localTime);

            /**=================================================================
             * Получаем из кеша сенсор айди по нетворк айди
             * ==================================================================*/
            $network_id = $mine_id . ":" . $package->locatorNum;

            // Поиск объекта сенсора по сетевому идентификатору из пакета.
            // Если такой сенсор не найден, то создаём новый сенсор по шаблону.

            $response = SensorMainController::getOrSetSensorByNetworkId($network_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка при инициализации сенсора по сетевому адресу: ' . $network_id);
            }

            if ($response['sensor_id'] === false) {                                                                     //если sensor_id не найден, создать его
                $title = 'Метка прочее networkID ' . $network_id;
                $response = StrataJobController::createSensorDatabase($title, $network_id, $mine_id, 104, 31, 12);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception("Ошибка создания сенсора по нетворк айди $network_id в кеше и в БД");
                }
                $sensor_id = $response['sensor_id'];
            } else {
                $sensor_id = $response['sensor_id'];
            }

            $log->addLog('Получил из кеша сенсор айди ' . $sensor_id . ' по нетворк айди ' . $network_id);

            /**=================================================================
             * Инициализация кеша сенсора SensorMine
             * ==================================================================*/

            if ($sensor_cache_controller->getSensorMineBySensorHash($sensor_id) === false) {
                $response = SensorMainController::initSensorInCache($sensor_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка при инициализации сенсора: ' . $sensor_id);
                }
            } else {
                $response = SensorMainController::moveSensorMineInitCache($sensor_id, $mine_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception("Ошибка при перемещении сенсора $sensor_id из кеша шахты");
                }
            }

            $log->addLog('Инициализация кеша сенсора SensorMine закончил');

            /**=================================================================
             * Нахождение координат точки
             * ===============================================================*/
            $location_status = 1;
            $total_nodes_heared = 3;
            /**
             * Если в пакете не было услышанных узлов, то повторно сохраняем
             * последние параметры координат
             */
            if ($package->nodes->ReaderNumBegin == -1 and $package->nodes->ReaderNumEnd == -1) {
                $nodes_temp_src = str_replace(" ", "", $package->comment);
                $nodes_temp_src = str_replace("{", "", $nodes_temp_src);
                $nodes_temp_src = str_replace("}", "", $nodes_temp_src);
                $nodes_temp = explode(",", $nodes_temp_src);
                $log->addData($nodes_temp, '$nodes_temp', __LINE__);

                $package->nodes->ReaderNumBegin = explode(":", $nodes_temp[1])[1];
                $package->nodes->ReaderNumEnd = explode(":", $nodes_temp[2])[1];
            }
//            $package->nodes->ReaderNumBegin = $nodes_temp['ReaderNumBegin'];
//            $package->nodes->ReaderNumEnd = $nodes_temp['ReaderNumEnd'];
//            if ($package->nodes->ReaderNumBegin == -1 and $package->nodes->ReaderNumEnd == -1) {
//                $total_nodes_heared = 0;
//                $log->addLog('Начал расчет координат В пакете не было услышанных узлов, беру последние параметры сенсора');
//
//                $last_sensor_values = $sensor_cache_controller->multiGetParameterValueHash($sensor_id);
//
//                foreach ($last_sensor_values as $last_sensor_value) {
//                    $last_sensor_values_hand[$last_sensor_value['parameter_id']][$last_sensor_value['parameter_type_id']] = $last_sensor_value;
//                }
//
//                if (isset($last_sensor_values_hand[ParameterEnumController::COORD][ParameterTypeEnumController::MEASURED])) {
//                    $xyz = $last_sensor_values_hand[ParameterEnumController::COORD][ParameterTypeEnumController::MEASURED]['value'];
//                } else {
//                    throw new Exception("В кэше не найдены координаты для узла $sensor_id");
//                }
//
//                if (isset($last_sensor_values_hand[ParameterEnumController::EDGE_ID][ParameterTypeEnumController::MEASURED])) {
//                    $edge_id = $last_sensor_values_hand[ParameterEnumController::EDGE_ID][ParameterTypeEnumController::MEASURED]['value'];
//                } else {
//                    throw new Exception("В кэше не найдены эдж для узла $sensor_id");
//                }
//
//                if (isset($last_sensor_values_hand[ParameterEnumController::PLACE_ID][ParameterTypeEnumController::MEASURED])) {
//                    $place_id = $last_sensor_values_hand[ParameterEnumController::PLACE_ID][ParameterTypeEnumController::MEASURED]['value'];
//                } else {
//                    throw new Exception("В кэше не найдены плейс для узла $sensor_id");
//                }
//
//                $log->addLog('Закончил расчет координат Если в пакете не было услышанных узлов, то повторно сохраняем');
//            } /**
//             * Если в пакете был только один услышанный узел, то берём его параметры
//             */
//            else
            if ($package->nodes->ReaderNumEnd == -1 or $package->nodes->ReaderNumEnd == "null" or !$package->nodes->ReaderNumEnd) {

                $log->addLog('Начал расчет координат для метки ' . $network_id . ' В пакете был только один услышанный узел: ' . $gateway_network_id . ', берём его параметры');

                $total_nodes_heared = 1;
                $location_status = StatusEnumController::CALCULATED_VALUE;

                $node_id = (new ServiceCache())->getSensorByNetworkId($gateway_network_id);
                if ($node_id === false) {
                    throw new Exception("Шлюз/узел не стоит на схеме. Не удалось найти в кэше сенсор по сетевому идентификатору $gateway_network_id");
                }


                $log->addLog("Сенсор $node_id по сетевому адресу $gateway_network_id, беру его параметры");

                $lamp_node_values = $sensor_cache_controller->multiGetParameterValueHash($node_id);

                if ($lamp_node_values) {
                    foreach ($lamp_node_values as $lamp_node_value) {
                        $lamp_node_values_hand[$lamp_node_value['parameter_id']][$lamp_node_value['parameter_type_id']] = $lamp_node_value;
                    }
                }

                if (isset($lamp_node_values_hand[ParamEnum::COORD][ParameterTypeEnumController::REFERENCE])) {
                    $xyz = $lamp_node_values_hand[ParamEnum::COORD][ParameterTypeEnumController::REFERENCE]['value'];
                } else {
                    throw new Exception("В кэше не найдены координаты для узла $node_id");
                }

                if (isset($lamp_node_values_hand[ParamEnum::EDGE_ID][ParameterTypeEnumController::REFERENCE])) {
                    $edge_id = $lamp_node_values_hand[ParamEnum::EDGE_ID][ParameterTypeEnumController::REFERENCE]['value'];
                } else {
                    throw new Exception("В кэше не найдены эдж для узла $node_id");
                }

                if (isset($lamp_node_values_hand[ParamEnum::PLACE_ID][ParameterTypeEnumController::REFERENCE])) {
                    $place_id = $lamp_node_values_hand[ParamEnum::PLACE_ID][ParameterTypeEnumController::REFERENCE]['value'];
                } else {
                    throw new Exception("В кэше не найдены плейс для узла $node_id");
                }
                $log->addLog("Закончил расчет координат");
            } else {
                /**
                 * Если в пакете больше 1 услышанного узла, то вычисляем координаты
                 */
                $log->addLog("Начал расчет координат Если в пакете больше 1 услышанного узла, то вычисляем координаты");

                if (!property_exists($package, "vectorLength")) {
                    $vectorLength = 0;
                } else {
                    $vectorLength = $package->vectorLength;
                }

                $response = (new CoordinateController)->calculateCoordinatesHorizon($package->nodes, $package->distance, $vectorLength, $mine_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка при расчёте координат');
                }

                $xyz = $response['Items']['xyz'];
                $edge_id = $response['Items']['edge_id'];
                $place_id = $response['Items']['place_id'];

                $log->addLog("Закончил расчет координат Если в пакете больше 1 услышанного узла, то вычисляем координаты");
            }

            $log->addLog("Закончил расчет координат");

            $log->addData($xyz, '$xyz', __LINE__);
            $log->addData($edge_id, '$edge_id', __LINE__);
            $log->addData($place_id, '$place_id', __LINE__);

            if ($xyz == -1 || $edge_id == -1 || $place_id == -1) {
                throw new Exception('Параметры положения некорректны');
            }

            /**=================================================================
             * Нахождение информации о нахождении светильника.
             * Нужно для проверки на нахождение в ламповой и запретной зоне
             * ==================================================================*/
            $log->addLog("Определение статуса местоположения (разрешено/запрещено)");

            $place_object_id = -1;
            $status_danger_zone = StatusEnumController::PERMITTED;

            $edge_info = EdgeMainController::getEdgeMineDetail($mine_id, $edge_id);
            $log->addLogAll($edge_info);

            if ($edge_info && $edge_info['edge_info']) {
                $log->addLog("Сведения о выработке были в кеше - проверяем запрет");
                $place_object_id = $edge_info['edge_info']['place_object_id'];
                if ($edge_info['edge_info']['danger_zona'] == 1) {
                    $log->addLog("Запрет постоянный через выработку Unity");
                    $status_danger_zone = StatusEnumController::FORBIDDEN;
                } else {
                    $log->addLog("Ищем заперт через конструктор");
                    $isForbidden = 0;

                    $response = ForbiddenZoneController::GetActiveForbiddenZoneByEdge($edge_id);
                    $log->addLogAll($response);
                    if ($response['status'] == 1) {
                        $isForbidden = $response['isForbidden'];
                    }

                    if ($isForbidden) {
                        $log->addLog("Запрет через конструктор запретов");
                        $status_danger_zone = StatusEnumController::FORBIDDEN;
                    } else {
                        $log->addLog("Запрет через конструктор не установлен");
                    }
                }
            } else {
                $log->addLog("Нет сведений о выработке в кеше. Запрет не определялся");
            }

            $log->addLog("Закончил определение статуса местоположения (разрешено/запрещено)");
            $log->addLog("Нахождение скорости движения человека");


            /**=================================================================
             * Вычисление скорости движения человека
             * Условия запуска алгоритма:
             *  1) На выработке есть конвейер
             *  2) В пакете не менее 2 узлов связи
             *  3) Предыдущее значение координат расчитано не менее чем по 2 узлам связи
             * ==================================================================*/

            $speed['generate_event'] = false;
            $speed['speed_value'] = -1;
            if ($edge_info && $edge_info['edge_info'] && $edge_info['edge_info']['conveyor'] == 1) {
                $log->addLog("На выработке $edge_id есть конвейер");
                if ($total_nodes_heared > 2) {
                    $last_total_nodes_heared = $sensor_cache_controller->getParameterValueHash($sensor_id, ParamEnum::NEIGHBOUR_COUNT, ParameterTypeEnumController::MEASURED);
                    $last_total_nodes_heared_flag = false;                                                                // флаг разрешения для расчета скорости движения работника (если расчет проивзодился по двум узлам и более и время корректное, то делать расчет)
                    if ($last_total_nodes_heared !== false && $last_total_nodes_heared['status_id'] == 1) {
                        $delta_time = strtotime($package->localTime) - strtotime($last_total_nodes_heared['date_time']);
                        $log->addLog("Расчет скорости текущая дата " . strtotime($package->localTime));
                        $log->addLog("Расчет скорости Прошлая дата " . strtotime($last_total_nodes_heared['date_time']));

                        if ($delta_time < 150 and $last_total_nodes_heared['value'] > 1) {                                   // время не больше 2,5 минут и количество услышанных узлов в расчете больше 1
                            $last_total_nodes_heared_flag = true;
                            $log->addLog("Расчет скорости Разрешение расчета");
                        } else {
                            $log->addLog("Расчет скорости Количество услышанных узлов связи " . $last_total_nodes_heared['value']);
                            $log->addLog("Расчет скорости Дельта времени " . $delta_time);
                            $log->addLog("Расчет скорости Запрет расчета ");
                        }
                    } else {
                        $log->addLog("Нет предыдущего параметра расчета количества услышанных узлов");
                    }
                    $log->addLog("В текущем пакете не менее 2 узлов связи");

                    $last_coord = $sensor_cache_controller->getParameterValueHash($sensor_id, ParamEnum::COORD, ParameterTypeEnumController::MEASURED);
                    if ($last_coord !== false && $last_coord['status_id'] == 1 && $last_total_nodes_heared_flag) {
                        $log->addLog("Предыдущее значение координат расчитано не менее чем по 2 узлам связи");
                        try {
                            $speed = CoordinateController::calculateSpeed(
                                $last_coord['value'], $last_coord['date_time'],
                                $xyz, $package->localTime
                            );
                            $log->addLog("Расcчитал скорость движения человека = " . $speed['speed_value'] . " м/с");
                        } catch (Throwable $exception) {
                            $log->addData($exception->getMessage(), '$exception->getMessage()', __LINE__);
                        }
                    } else {
                        $log->addLog("Предыдущее значение координат вычислено менее чем по 2 узлам связи или есть запрет на расчет");
                    }
                } else {
                    $log->addLog("В пакете менее 2 узлов связи");
                }
            } else {
                $log->addLog("На выработке $edge_id нет конвейера");
            }
            $log->addLog("Вычислил скорость движения человека");

            /**=================================================================
             * Сохранение параметров сенсора
             * ==================================================================*/
            $response = self::saveLocationPacketSensorParameters($sensor_id, $package->localTime, $mine_id, $xyz, $edge_id, $place_id, $place_object_id, $location_status, $total_nodes_heared);
            $log->addLogAll($response);

            $log->addLog("Закончил Сохранение параметров сенсора");

            /**=================================================================
             * Сохранение параметров воркера
             * ==================================================================*/
            $response = WorkerMainController::getWorkerInfoBySensorId($sensor_id);
            $log->addLogAll($response);
            $worker_sensor = $response['Items'];
            if ($worker_sensor) {
                $flag += 1;
                $log->addLog("Найдена привязка воркера к сенсору: " . $worker_sensor['worker_id']);
                $response = self::saveLocationPacketWorkerParameters($package->localTime, $worker_sensor, $mine_id, $xyz, $edge_id, $place_id, $status_danger_zone, $place_object_id, $speed, $location_status);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка при сохранении параметров воркера');
                }
            }
            $log->addLog("Закончил Сохранение параметров воркера");

//            throw new Exception('Стоп');

            /**=================================================================
             * Сохранение параметров оборудования
             * ==================================================================*/
            $response = EquipmentMainController::getEquipmentInfoBySensorId($sensor_id);
            $log->addLogAll($response);
            $equipment_sensor = $response['Items'];
            if ($equipment_sensor) {
                $flag += 2;
                $log->addLog("Найдена привязка оборудования к сенсору");
                $response = self::saveLocationPacketEquipmentParameters($package->localTime, $equipment_sensor, $xyz, $edge_id, $place_id, $status_danger_zone, $mine_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка при сохранении параметров оборудования');
                }
            }

            $log->addLog("Сохранение параметров оборудования");

            /**=================================================================
             * Генерация записи в отчётной таблице о нахождении в запретной зоне
             * (при необходимости)
             * ==================================================================*/
            // Нахождение объекта, привязанного к сенсору.
            // Если к сенсору не привязан объект, то ищется объект самого сенсора
            // Требуется для записи в отчётную таблицу summary_report_forbidden_zones
            if ($worker_sensor) {
                $log->addLog("Поиск объекта воркера");

                $main_obj = WorkerObject::find()
                    ->with('worker.employee')
                    ->where(['worker_id' => $worker_sensor['worker_id']])
                    ->limit(1)
                    ->one();
                $main_title = $main_obj->worker->employee->last_name . ' ' . $main_obj->worker->employee->first_name . ' ' . $main_obj->worker->employee->patronymic;
                $table_name = 'worker_object';
            } elseif ($equipment_sensor) {
                $log->addLog("Поиск объекта оборудования");
                $main_obj = Equipment::findOne(['id' => $equipment_sensor['equipment_id']]);
                $main_title = $main_obj['title'];
                $table_name = 'equipment';
            } else {
                $log->addLog("Поиск объекта сенсора");
                $main_obj = Sensor::findOne(['id' => $sensor_id]);
                $main_title = $main_obj['title'];
                $table_name = 'sensor';
            }

            // Выполнение проверки на нахождение в запретной зоне.
            // При выявлении факта нахождения или выхода из запретной зоны -
            // создаётся запись в отчетной таблице summary_report_forbidden_zones
            $response = self::checkForbiddenZoneStatus($package->localTime, $network_id, $status_danger_zone, $main_obj, $main_title, $table_name, $place_id, $edge_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка при проверке запретной зоны');
            }
            $log->addLog("Генерация записи в отчётной таблице о нахождении в запретной зоне");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
            if (self::SAVE_ALL_LOG){
                $logs = $log->getLogAll();
            } else {
                $logs['errors'] = $log->getErrors();
            }
            $data_to_log = array_merge(
                [
                    'Items' => $result,
                    'Method parameters' => ['pack' => $package, 'mine_id' => $mine_id]
                ],
                $logs
            );
            //LogCacheController::setStrataLogValue('saveLocationPacket', $data_to_log, '2');
            //LogAmicum::LogAmicumStrata("saveLocationPacket", $package, $log->getWarnings(), $log->getErrors());
            LogCacheController::setStrataTypeLogValue('saveLocationPacket', $data_to_log, $gateway_network_id.':'.$ex->getMessage());
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['flag' => $flag, 'Items' => $result], $log->getLogAll());
    }

    /**
     * saveChemicalPacket - Метод сохранения газов
     * @param $package - Пакет местоположения шахтёра
     * @param int $gateway_network_id Идентификатор шлюза, с которого пришел пакет
     * @param int $mine_id Идентификатор шахты
     * @return array
     */
    public static function saveChemicalPacket($package, $gateway_network_id, $mine_id)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("saveChemicalPacket");
        $count_all = 0;                                                                                                 // количество полученных записей
        //состояние выполнения метода
        try {
            $log->addLog("Начало выполнения метода");

            $sensor_cache_controller = new SensorCacheController();

            $log->addLog('Метка времени до package->measurementTime: ' . $package->measurementTime);
            $log->addLog('Метка времени до package->staffHistory->localTime: ' . $package->staffHistory->localTime);
            $package->measurementTime = Assistant::repairDate($package->staffHistory->localTime);
            $log->addLog('Метка времени ПОСЛЕ package->measurementTime: ' . $package->measurementTime);
            $log->addLog('Метка времени ПОСЛЕ package->staffHistory->localTime: ' . $package->staffHistory->localTime);

            /**=================================================================
             * Получаем из кеша сенсор айди по нетворк айди
             * ==================================================================*/
            $network_id = $mine_id . ":" . $package->locatorNum;

            // Поиск объекта сенсора по сетевому идентификатору из пакета.
            // Если такой сенсор не найден, то создаём новый сенсор по шаблону.

            $response = SensorMainController::getOrSetSensorByNetworkId($network_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка при инициализации сенсора по сетевому адресу: ' . $network_id);
            }

            if ($response['sensor_id'] === false) {                                                                     //если sensor_id не найден, создать его
                $title = 'Метка прочее networkID ' . $network_id;
                $response = StrataJobController::createSensorDatabase($title, $network_id, $mine_id, 104, 31, 12);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception("Ошибка создания сенсора по нетворк айди $network_id в кеше и в БД");
                }
                $sensor_id = $response['sensor_id'];
            } else {
                $sensor_id = $response['sensor_id'];
            }

            $log->addLog('Получил из кеша сенсор айди ' . $sensor_id . ' по нетворк айди ' . $network_id);

            /**=================================================================
             * Инициализация кеша сенсора SensorMine
             * ==================================================================*/

            if ($sensor_cache_controller->getSensorMineBySensorHash($sensor_id) === false) {
                $response = SensorMainController::initSensorInCache($sensor_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка при инициализации сенсора: ' . $sensor_id);
                }
            } else {
                $response = SensorMainController::moveSensorMineInitCache($sensor_id, $mine_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception("Ошибка при перемещении сенсора $sensor_id из кеша шахты");
                }
            }

            $log->addLog('Инициализация кеша сенсора SensorMine закончил');

            /**=================================================================
             * Нахождение координат точки
             * ===============================================================*/
            $location_status = 1;
            $total_nodes_heared = 3;
            /**
             * Если в пакете не было услышанных узлов, то повторно сохраняем
             * последние параметры координат
             */
            if ($package->nodes->ReaderNumBegin == -1 and $package->nodes->ReaderNumEnd == -1) {
                $nodes_temp_src = str_replace(" ", "", $package->comment);
                $nodes_temp_src = str_replace("{", "", $nodes_temp_src);
                $nodes_temp_src = str_replace("}", "", $nodes_temp_src);
                $nodes_temp = explode(",", $nodes_temp_src);
                $log->addData($nodes_temp, '$nodes_temp', __LINE__);

                $package->nodes->ReaderNumBegin = explode(":", $nodes_temp[1])[1];
                $package->nodes->ReaderNumEnd = explode(":", $nodes_temp[2])[1];
            }

            /**
             * Если в пакете был только один услышанный узел, то берём его параметры
             */
            if ($package->nodes->ReaderNumEnd == -1 or $package->nodes->ReaderNumEnd == "null" or !$package->nodes->ReaderNumEnd) {

                $log->addLog('Начал расчет координат для метки ' . $network_id . ' В пакете был только один услышанный узел: ' . $gateway_network_id . ', берём его параметры');

                $total_nodes_heared = 1;
                $location_status = StatusEnumController::CALCULATED_VALUE;

                $node_id = (new ServiceCache())->getSensorByNetworkId($gateway_network_id);
                if ($node_id === false) {
                    throw new Exception("Шлюз/узел не стоит на схеме. Не удалось найти в кэше сенсор по сетевому идентификатору $gateway_network_id");
                }


                $log->addLog("Сенсор $node_id по сетевому адресу $gateway_network_id, беру его параметры");

                $lamp_node_values = $sensor_cache_controller->multiGetParameterValueHash($node_id);

                if ($lamp_node_values) {
                    foreach ($lamp_node_values as $lamp_node_value) {
                        $lamp_node_values_hand[$lamp_node_value['parameter_id']][$lamp_node_value['parameter_type_id']] = $lamp_node_value;
                    }
                }

                if (isset($lamp_node_values_hand[ParamEnum::COORD][ParameterTypeEnumController::REFERENCE])) {
                    $xyz = $lamp_node_values_hand[ParamEnum::COORD][ParameterTypeEnumController::REFERENCE]['value'];
                } else {
                    throw new Exception("В кэше не найдены координаты для узла $node_id");
                }

                if (isset($lamp_node_values_hand[ParamEnum::EDGE_ID][ParameterTypeEnumController::REFERENCE])) {
                    $edge_id = $lamp_node_values_hand[ParamEnum::EDGE_ID][ParameterTypeEnumController::REFERENCE]['value'];
                } else {
                    throw new Exception("В кэше не найдены эдж для узла $node_id");
                }

                if (isset($lamp_node_values_hand[ParamEnum::PLACE_ID][ParameterTypeEnumController::REFERENCE])) {
                    $place_id = $lamp_node_values_hand[ParamEnum::PLACE_ID][ParameterTypeEnumController::REFERENCE]['value'];
                } else {
                    throw new Exception("В кэше не найдены плейс для узла $node_id");
                }
                $log->addLog("Закончил расчет координат");
            } else {
                /**
                 * Если в пакете больше 1 услышанного узла, то вычисляем координаты
                 */
                $log->addLog("Начал расчет координат Если в пакете больше 1 услышанного узла, то вычисляем координаты");

                if (!property_exists($package, "staffHistory") and !property_exists($package->staffHistory, "vectorLength")) {
                    $vectorLength = 0;
                } else {
                    $vectorLength = $package->staffHistory->vectorLength;
                }

                $response = (new CoordinateController)->calculateCoordinatesHorizon($package->nodes, $package->staffHistory->distance, $vectorLength, $mine_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка при расчёте координат');
                }

                $xyz = $response['Items']['xyz'];
                $edge_id = $response['Items']['edge_id'];
                $place_id = $response['Items']['place_id'];

                $log->addLog("Закончил расчет координат Если в пакете больше 1 услышанного узла, то вычисляем координаты");
            }

            $log->addLog("Закончил расчет координат");

            $log->addData($xyz, '$xyz', __LINE__);
            $log->addData($edge_id, '$edge_id', __LINE__);
            $log->addData($place_id, '$place_id', __LINE__);

            if ($xyz == -1 || $edge_id == -1 || $place_id == -1) {
                throw new Exception('Параметры положения некорректны');
            }

            /**=================================================================
             * Определение статуса местоположения (разрешено/запрещено)
             * 15 - запретная
             * 16 - разрешенная
             * ==================================================================*/
            $edge_info = EdgeMainController::getEdgeMineDetail($mine_id, $edge_id);
            $log->addLogAll($edge_info);
            if ($edge_info && $edge_info['edge_info']) {
                $place_object_id = $edge_info['edge_info']['place_object_id'];
                $status_danger_zone = $edge_info['edge_info']['danger_zona'] == 1 ? StatusEnumController::FORBIDDEN : StatusEnumController::PERMITTED;
            } else {
                $place_object_id = -1;
                $status_danger_zone = StatusEnumController::PERMITTED;
            }
            $log->addLog('Статус местоположения (запретная-16/разрешенная-15) = ' . $status_danger_zone);
            $log->addLog("Проверил выработку на запретную/разрешенную");

            /**=================================================================
             * Определение идентификаторов параметра нужного газа, параметра
             * превышения конкретного газа и генерируемого события при превышении
             * ==================================================================*/
            switch ($package->substance) {
                case "CO2":
                    $log->addLog("Данные от датчика CO2");

                    $parameter_id = ParamEnum::GAS_LEVEL_CO2;
                    $parameter_excess_id = ParamEnum::GAS_EXCESS_CO2;
                    $event_id = EventEnumController::CO2_EXCESS_LAMP;
                    $threshold_val = 0.5;
//                    $package->meterage = $package->meterage / 100;
                    $is_gas_excess = (($package->meterage > $threshold_val) ? 1 : 0);

                    break;
                case "CO":
                    $log->addLog("Данные от датчика CO");

                    $parameter_id = ParamEnum::GAS_LEVEL_CO;
                    $parameter_excess_id = ParamEnum::GAS_EXCESS_CO;
                    $event_id = EventEnumController::CO_EXCESS_LAMP;
                    $threshold_val = $edge_info['value_co'] ?? 0.0017;
//                    $package->meterage = $package->meterage / 10000;
                    $is_gas_excess = (($package->meterage > $threshold_val) ? 1 : 0);

                    break;
                case "CH4":
                    $log->addLog("Данные от датчика CH4");

                    $parameter_id = ParamEnum::GAS_LEVEL_CH4;
                    $parameter_excess_id = ParamEnum::GAS_EXCESS_CH4;
                    $event_id = EventEnumController::CH4_EXCESS_LAMP;
                    $threshold_val = $edge_info['value_ch'] ?? 1;
//                    $package->meterage = $package->meterage / 100;
                    $is_gas_excess = (($package->meterage > $threshold_val) ? 1 : 0);

                    break;
                case "O2":
                    $log->addLog("Данные от датчика O2");

                    $parameter_id = ParamEnum::GAS_LEVEL_O2;
                    $parameter_excess_id = ParamEnum::GAS_EXCESS_O2;
                    $event_id = EventEnumController::O2_EXCESS_LAMP;
                    $threshold_val = 19.5;
                    $is_gas_excess = (($package->meterage <= $threshold_val) ? 1 : 0);

                    break;
                default:
                    throw new Exception('. Данные от неизвестного датчика');
            }
            $log->addLog('Тип датчика = ' . $package->substance);
            $log->addLog('Идентификатор параметра = ' . $parameter_id);
            $log->addLog('Идентификатор параметра превышения газа = ' . $parameter_excess_id);
            $log->addLog('Идентификатор события = ' . $event_id);
            $log->addLog('Уставка газа ' . $threshold_val);
            $log->addLog("Нахождение уставок газа выполнено");

            /**=================================================================
             * Вычисление наличия превышения газов
             * ==================================================================*/

            $log->addLog("Вычисление наличия превышения газов");
            if ($is_gas_excess) {
                $log->addLog("Есть превышение газа");
                $status_id = StatusEnumController::EMERGENCY_VALUE;
                // получаем тип места из $edge_info[']
                //если превышение в ламповой, то генерируем отказ
                // иначе сообщение летит в обычном режиме
                if ($place_object_id == 80) {
                    $event_id = EventEnumController::CH4_CRUSH_LAMP;
                    $log->addLog("Отказ датчика. Показал газ в ламповой");
                }
            } else {
                $log->addLog("Нет превышения газа");
                $status_id = StatusEnumController::NORMAL_VALUE;
            }

            $log->addLog('Значение = ' . $package->meterage);
            $log->addLog('Превышен газ = ' . $is_gas_excess);
            $log->addLog('Уставка газа = ' . $threshold_val);
            $log->addLog('Статус газа (44 аварийное/45 нормальное) = ' . $status_id);
            $log->addLog("Вычислил наличие превышения газов");


            /**=================================================================
             * Сохранение параметров сенсора
             * ==================================================================*/
            $log->addLog(" Сохранение параметров сенсора");
            $response = self::saveEnvironmentalPacketSensorParameters($package, $sensor_id, $threshold_val, $xyz, $place_id, $edge_id, $parameter_id, $status_id, $parameter_excess_id, $is_gas_excess);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка при сохранении параметров сенсора');
            }
            /**
             * Вычисление статусов в зависимости от значения превышения газа
             */
            if ($is_gas_excess) {
                $value_status_id = StatusEnumController::EMERGENCY_VALUE;
                $event_status_id = StatusEnumController::EVENT_RECEIVED;
            } else {
                $value_status_id = StatusEnumController::NORMAL_VALUE;
                $event_status_id = StatusEnumController::EVENT_ELIMINATED_BY_SYSTEM;
            }
            if ($package->substance == "CH4") {
                $response = OpcController::actionCalcGasValueStaticMovement($sensor_id, $edge_id, $xyz, $mine_id, $package->meterage);
                $log->addLogAll($response);

                $response = EventMainController::createEventFor('sensor', $sensor_id, $event_id, $package->meterage, $package->measurementTime, $value_status_id, $parameter_id, $mine_id, $event_status_id, $edge_id, $xyz);
                $log->addLogAll($response);
            } else {
                $response = EventMainController::createEventFor('sensor', $sensor_id, $event_id, $package->meterage, $package->measurementTime, $value_status_id, $parameter_id, $mine_id, $event_status_id, $edge_id, $xyz);
                $log->addLogAll($response);
            }

            /**=================================================================
             * Сохранение параметров воркера
             * ==================================================================*/
            $response = WorkerMainController::getWorkerInfoBySensorId($sensor_id);
            $log->addLogAll($response);
            $worker_sensor = $response['Items'];
            $log->addData($worker_sensor, '$worker_sensor', __LINE__);

            if ($worker_sensor) {
                $log->addLog("Найдена привязка сенсора к воркеру");
                $response = self::saveEnvironmentalPacketWorkerParameters($package, $mine_id, $worker_sensor, $status_danger_zone, $xyz, $place_id, $edge_id, $parameter_id, $status_id, $parameter_excess_id, $is_gas_excess, $event_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка при сохранении параметров воркера');
                }
            }

            $log->addLog("Сохранил значения параметров воркеров");


            $log->addLog("Сохранил значения параметров сенсора");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
            if (self::SAVE_ALL_LOG){
                $logs = $log->getLogAll();
            } else {
                $logs['errors'] = $log->getErrors();
            }
            $data_to_log = array_merge(
                [
                    'Items' => $result,
                    'Method parameters' => ['pack' => $package, 'mine_id' => $mine_id]
                ],
                $logs
            );
            // LogCacheController::setStrataLogValue('saveChemicalPacket', $data_to_log, '2');
            // LogAmicum::LogAmicumStrata("saveChemicalPacket", $package, $log->getWarnings(), $log->getErrors());
            LogCacheController::setStrataTypeLogValue('saveChemicalPacket', $data_to_log, $gateway_network_id.':'.$ex->getMessage());
        }
        $log->addLog("Окончание выполнения метода");
//        $data_to_log = array_merge(
//            [
//                'Items' => $result,
//                'Method parameters' => ['pack' => $package, 'mine_id' => $mine_id]
//            ],
//            $log->getLogAll()
//        );
//        LogCacheController::setStrataLogValue('saveChemicalPacket', $data_to_log, '2');

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * saveLocationPacketSensorParameters - Сохраняет параметры сенсора, полученные из пакета положения шахтёра.
     * @param int $sensor_id Объект сенсора
     * @param $package_date_time - дата и время из пакета
     * @param int $mine_id Идентификатор шахты
     *
     * @param $xyz - координата метки
     * @param $edge_id - ключ выработки
     * @param $place_id - ключ места
     * @param $place_object_id - ключ типа места
     * @param int $location_status - статус локации
     * @param int $total_nodes_heared - Количество услышанных узлов связи
     * @return array
     *
     */
    public static function saveLocationPacketSensorParameters($sensor_id, $package_date_time, $mine_id, $xyz, $edge_id, $place_id, $place_object_id, $location_status = 1, $total_nodes_heared = 0)
    {
        $log = new LogAmicumFront("saveLocationPacketSensorParameters");

        try {
            /**
             * получаем за раз все последние значения по сенсору из кеша
             */
            $log->addLog("Получаем данные с кеша по всему сенсору");
            $sensor_cache_controller = new SensorCacheController();
            $sensor_parameter_value_list_cache = $sensor_cache_controller->multiGetParameterValueHash($sensor_id, '*', '*', true);
            if ($sensor_parameter_value_list_cache) {
                foreach ($sensor_parameter_value_list_cache as $sensor_parameter_value_cache) {
                    $sensor_parameter_value_cache_array[$sensor_parameter_value_cache['sensor_id']][$sensor_parameter_value_cache['parameter_type_id']][$sensor_parameter_value_cache['parameter_id']] = $sensor_parameter_value_cache;
                }
            } else {
                $sensor_parameter_value_cache_array = [];
            }

            $log->addLog("Получаем за раз все последние значения по сенсору из кеша");

            /**=================================================================
             * Генерация структур для вставки
             * ==================================================================*/

            //количество услышанных узлов связи меткой (используется для расчета скорости - откидывает координаты меток без расчета)
            $response = StrataJobController::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::NEIGHBOUR_COUNT, $total_nodes_heared, $package_date_time, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения параметра ' . ParamEnum::NEIGHBOUR_COUNT);
            }

            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }

            $response = StrataJobController::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::CALCULATED, ParamEnum::STATE, 1, $package_date_time, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения параметра ' . ParamEnum::STATE);
            }

            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }


            $response = StrataJobController::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::COORD, $xyz, $package_date_time, $location_status, $sensor_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения параметра ' . ParamEnum::COORD);
            }

            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }


            $response = StrataJobController::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::PLACE_ID, $place_id, $package_date_time, $location_status, $sensor_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения параметра ' . ParamEnum::PLACE_ID);
            }

            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }


            $response = StrataJobController::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::EDGE_ID, $edge_id, $package_date_time, $location_status, $sensor_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения параметра ' . ParamEnum::EDGE_ID);
            }

            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }


            /**=================================================================
             * Перемещение сенсора в кеше шахт (если надо) и сохранение параметра шахты
             * ==================================================================*/
            SensorMainController::moveSensorMineInitCache($sensor_id, $mine_id);
            $response = StrataJobController::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::MINE_ID, $mine_id, $package_date_time, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения параметра ' . ParamEnum::MINE_ID);
            }

            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }

            $log->addLog("Перемещение сенсора в кеше шахт (если надо) и сохранение параметра шахты");

            /**=================================================================
             * Сохранение параметра регистрации для сенсора
             * Если уже в шахте, то писать чекин не нужно
             * ==================================================================*/
            $log->addLog("Проверка на нахождение в ламповой");

            $flagRegistration = 1;
            $sensor_main_controller = new SensorMainController(Yii::$app->id, Yii::$app);
            $sensor_checkin_parameter_last_value = $sensor_main_controller->getOrSetParameterValue($sensor_id, 158, 2);
            if ($place_object_id == 80 || (isset($sensor_checkin_parameter_last_value['Items']['value']) && $sensor_checkin_parameter_last_value['Items']['value'] == 1)) {
                $log->addLog("Сенсор находится в ламповой или уже зачекинен, чекин не нужен");
                $flagRegistration = 0;
            }
            // Сохранение параметра, если сенсор не зачекинен и не в ламповой
            if ($flagRegistration) {
                $log->addLog("Сенсор находится не в ламповой, чекини");
                $response = StrataJobController::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::CHECKIN, '1', $package_date_time, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка сохранения параметра ' . ParamEnum::CHECKIN);
                }

                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
                $log->addLog("Сохранение параметра чекина");
            }

            /**=================================================================
             * блок массовой вставки значений в кеш
             * =================================================================*/
            if (isset($date_to_cache)) {
                $ask_from_method = $sensor_cache_controller->multiSetSensorParameterValueHash($date_to_cache);
                $log->addLogAll($ask_from_method);
                if ($ask_from_method['status'] != 1) {
                    throw new Exception('Не смог обновить параметры в кеше сенсора' . $sensor_id);
                }
            }
            $log->addLog("блок массовой вставки значений в кеш");

            /**=================================================================
             * блок массовой вставки значений в БД
             * =================================================================*/

            if (isset($date_to_db)) {
                Yii::$app->db_amicum2->createCommand('SET SESSION wait_timeout = 28800;')->execute();
                Yii::$app->db_amicum2->createCommand()->batchInsert('sensor_parameter_value', ['sensor_parameter_id', 'date_time', 'value', 'status_id'], $date_to_db)->execute();
//                foreach ($date_to_db as $date) {
//                    $spv_new = new SensorParameterValue();
//                    $spv_new->sensor_parameter_id = $date['sensor_parameter_id'];
//                    $spv_new->date_time = $date['date_time'];
//                    $spv_new->value = (string)$date['value'];
//                    $spv_new->status_id = $date['status_id'];
//                    if (!$spv_new->save()) {
//                        $log->addData($spv_new->errors, '$spv_new->errors', __LINE__);
//                        throw new Exception('Ошибка сохранения значения параметра в БД');
//                    }
//                }
            }
            $log->addLog("блок массовой вставки значений в БД");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Закончил метод");

        //LogCacheController::setStrataLogValue('saveLocationPacket', $log->getLogAll(), '2');

        return array_merge(['Items' => []], $log->getLogAll());
    }

    /**
     * saveLocationPacketWorkerParameters - Сохраняет параметры воркера, полученные из пакета положения шахтёра.
     * @param $package_date_time
     * @param $worker_sensor
     * @param $mine_id
     * @param $xyz
     * @param $edge_id
     * @param $place_id
     * @param $status_danger_zone
     * @param $place_object_id
     * @param $speed
     * @param int $location_status
     * @return array
     */
    public static function saveLocationPacketWorkerParameters($package_date_time, $worker_sensor, $mine_id, $xyz, $edge_id, $place_id, $status_danger_zone, $place_object_id, $speed, $location_status = 1)
    {
        $log = new LogAmicumFront("saveLocationPacketWorkerParameters");

        try {
            $log->addLog("Сохранение параметров работника");

            /**=================================================================
             * Сохранение параметров работника
             * ==================================================================*/
            /**
             * получаем за раз все последние значения по воркеру из кеша
             */
            $worker_cache_controller = new WorkerCacheController();
            $worker_parameter_value_list_cache = $worker_cache_controller->multiGetParameterValueHash($worker_sensor['worker_id'], '*', '*');
            if ($worker_parameter_value_list_cache === false) {
                $worker_parameter_value_cache_array = null;
            } else {
                foreach ($worker_parameter_value_list_cache as $worker_parameter_value_cache) {
                    $worker_parameter_value_cache_array[$worker_parameter_value_cache['worker_id']][$worker_parameter_value_cache['parameter_type_id']][$worker_parameter_value_cache['parameter_id']] = $worker_parameter_value_cache;
                }
                $log->addLog("получил данные с кеша по всему воркеру");
            }

            $log->addLog("получил за раз все последние значения по воркеру из кеша");

            $response = StrataJobController::saveWorkerParameterBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, ParamEnum::MINE_ID, $mine_id, $package_date_time, StatusEnumController::ACTUAL, $worker_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения параметра ' . ParamEnum::MINE_ID);
            }

            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }


            $response = StrataJobController::saveWorkerParameterBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, ParamEnum::COORD, $xyz, $package_date_time, $location_status, $worker_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения параметра ' . ParamEnum::COORD);
            }

            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }

            $response = StrataJobController::saveWorkerParameterBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, ParamEnum::EDGE_ID, $edge_id, $package_date_time, $location_status, $worker_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения параметра ' . ParamEnum::EDGE_ID);
            }

            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }

            $log->addLog("Подготовил параметры для сохранения");


            /**=================================================================
             * Сохранение скорости движения человека и генерация события,
             * если параметр расчитывался
             * ==================================================================*/
            if ($speed['speed_value'] > -1) {
                if ($speed['speed_value'] > 3.5) {
                    $log->addLog("Сохранение скорости человека с генерацией события");
                    $value_status_id = StatusEnumController::EMERGENCY_VALUE;
                    $event_status_id = StatusEnumController::EVENT_RECEIVED;
                } else {
                    $log->addLog("Сохранение скорости человека без генерации событи");
                    $value_status_id = StatusEnumController::NORMAL_VALUE;
                    $event_status_id = StatusEnumController::EVENT_ELIMINATED_BY_SYSTEM;
                }

                $response = StrataJobController::saveWorkerParameterBatch($worker_sensor['worker_id'], ParameterTypeEnumController::CALCULATED, ParamEnum::WORKER_SPEED, $speed['speed_value'], $package_date_time, StatusEnumController::ACTUAL, $worker_parameter_value_cache_array);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка сохранения параметра ' . ParamEnum::WORKER_SPEED);
                }

                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }

                if ($speed['generate_event'] == true and $speed['speed_value'] < 5.5) {
                    $response = EventMainController::createEventFor('worker', $worker_sensor['worker_id'], EventEnumController::MOVEMENT_ON_CONVEYOR, $speed['speed_value'], $package_date_time, $value_status_id, ParamEnum::WORKER_SPEED, $mine_id, $event_status_id, $edge_id, $xyz);
                    $log->addLogAll($response);
                }
                // Если есть превышение возможной скорости, то вызываем метод
                // остановки конвейера
                if ($value_status_id == StatusEnumController::EMERGENCY_VALUE) {
                    $response = StrataJobController::stopConveyor($mine_id, $edge_id);
                    $log->addLogAll($response);
                }
            }

            $log->addLog("Скорость и конвейера - обработал");


            /**=================================================================
             * Добавление записи в отчётную таблицу worker_collection.
             * Для отчёта "История местоположения персонала и транспорта"
             * ==================================================================*/
            $response = WorkerMainController::addWorkerCollection($worker_sensor['worker_id'], $status_danger_zone, $place_id, $package_date_time);
            $log->addLogAll($response);
            $worker_model = $response['worker_model'];

            $log->addLog("Записал воркер коллекшен");


            /**=================================================================
             * Сохранение идентификатора плейса.
             * При статусе зоны - запретная, создается событие
             * ==================================================================*/
            $log->addLog("Вычисление статуса запретной зоны");
            if ($status_danger_zone == StatusEnumController::FORBIDDEN) {
                $log->addLog("Сохранение запретной зоны с генерацией события");
                $value_status_id = StatusEnumController::EMERGENCY_VALUE;
                $event_status_id = StatusEnumController::EVENT_RECEIVED;
            } else {
                $log->addLog("Сохранение запретной зоны без генерации события");
                $value_status_id = StatusEnumController::NORMAL_VALUE;
                $event_status_id = StatusEnumController::EVENT_ELIMINATED_BY_SYSTEM;
            }

            $response = StrataJobController::saveWorkerParameterBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, ParamEnum::PLACE_ID, $place_id, $package_date_time, $status_danger_zone, $worker_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения параметра ' . ParamEnum::PLACE_ID);
            }

            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }


            $response = EventMainController::createEventFor('worker', $worker_sensor['worker_id'], EventEnumController::WORKER_DANGER_ZONE, $place_id . " / " . $edge_id . " / " . $status_danger_zone, $package_date_time, $value_status_id, ParamEnum::PLACE_ID, $mine_id, $event_status_id, $edge_id, $xyz);
            $log->addLogAll($response);

            $log->addLog("Запретная зона - закончил");


            /**=================================================================
             * Сохранение параметра чекина
             * ==================================================================*/
            $log->addLog("Проверка на нахождение в ламповой");
            $flagRegistration = 1;
            $flag_zapret_registration = true;                                                                           // по умолчанию запрет на регистрацию в шахте
            $worker_checkin_parameter_last_value = WorkerMainController::getWorkerParameterLastValue($worker_sensor['worker_id'], ParamEnum::CHECKIN, ParameterTypeEnumController::MEASURED);
            $check_posibility_registration = StrataJobController::getCheckPossibilityRegistration($worker_sensor['worker_id'], $package_date_time);
            $log->addLogAll($check_posibility_registration);
            if ($check_posibility_registration['status'] == 1) {
                $flag_zapret_registration = !$check_posibility_registration['posibility_registration_status'];
            }
            if ($worker_checkin_parameter_last_value['value'] == 1 || $place_object_id == 80 || $flag_zapret_registration) {
                $log->addLog('$worker_checkin_parameter_last_value[value]: ' . $worker_checkin_parameter_last_value['value']);
                $log->addLog('$place_object_id: ' . $place_object_id);
                $log->addLog('$flag_zapret_registration: ' . $flag_zapret_registration);
                $log->addLog('Воркер был зарегистрирован');

                $flagRegistration = 0;
            }

            $working_hours_start_work = $worker_cache_controller->getWorkingHours($worker_sensor['worker_id']);

            if ($flagRegistration) {                                                                                    // Сохранение параметра, если воркер не зачекинен и не в ламповой
                $log->addLog("Воркер находится не в ламповой, чекиним");
                $response = StrataJobController::saveWorkerParameterBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, ParamEnum::CHECKIN, 1, $package_date_time, StatusEnumController::FORCED, $worker_parameter_value_cache_array);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка сохранения параметра ' . ParamEnum::CHECKIN);
                }

                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }

                if (!$working_hours_start_work or (strtotime($working_hours_start_work) - strtotime($package_date_time) > 28800)) {
                    $worker_cache_controller->setWorkingHours($worker_sensor['worker_id'],$package_date_time);
                    $log->addLog("Кэш стара записан");
                }

                if ($working_hours_start_work and (strtotime($working_hours_start_work) - strtotime($package_date_time) > 28800)) {
                    $log->addLog("Кэш старта устарел");
                    $response = HorizonController::sendWorkingHoursToRabbitMQ($worker_sensor['worker_id'], $working_hours_start_work, /*$package_date_time*/null);
                    $log->addLogAll($response);
                    $worker_cache_controller->cleanWorkingHours($worker_sensor['worker_id']);
                }
            } else {
                if ($worker_checkin_parameter_last_value['value'] == 1) {
                    $log->addLog("Воркер находится в ламповой, выводим из шахты");
                    $response = StrataJobController::saveWorkerParameterBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, ParamEnum::CHECKIN, 0, $package_date_time, StatusEnumController::FORCED, $worker_parameter_value_cache_array);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception('Ошибка сохранения параметра ' . ParamEnum::CHECKIN);
                    }

                    if ($response['date_to_cache']) {
                        $date_to_cache[] = $response['date_to_cache'];
                    }
                    if ($response['date_to_db']) {
                        $date_to_db[] = $response['date_to_db'];
                    }
                    $worker_info = SummaryReportEndOfShiftController::AddTableReportRecord($worker_sensor['worker_id'], $package_date_time);// Добавление записи о выходе в отчётную таблицу
                    $log->addLog("Записал SummaryReportEndOfShift");


                    if ($working_hours_start_work) {
                        $response = HorizonController::sendWorkingHoursToRabbitMQ($worker_sensor['worker_id'], $working_hours_start_work, $package_date_time, 0, $worker_info);
                        $log->addLogAll($response);
                        $log->addLog("Отправил часы работы в RabbitMQ");
                        $worker_cache_controller->cleanWorkingHours($worker_sensor['worker_id']);
                    }
                }
            }

            $log->addLog("Чекин закончил");

            /**=================================================================
             * блок массовой вставки значений в БД
             * =================================================================*/
            if (isset($date_to_db)) {
                Yii::$app->db_amicum2->createCommand('SET SESSION wait_timeout = 28800;')->execute();
//                Yii::$app->db_amicum2->createCommand()->batchInsert('worker_parameter_value', ['worker_parameter_id', 'date_time', 'value', 'status_id', 'shift', 'date_work'], $date_to_db)->execute();
                $insert_param_val = Yii::$app->db_amicum2->queryBuilder->batchInsert('worker_parameter_value', ['worker_parameter_id', 'date_time', 'value', 'status_id', 'shift', 'date_work'], $date_to_db);
                Yii::$app->db_amicum2->createCommand($insert_param_val . " ON DUPLICATE KEY UPDATE `value` = VALUES (`value`), `status_id` = VALUES (`status_id`), `shift` = VALUES (`shift`), `date_work` = VALUES (`date_work`)")->execute();
//                foreach ($date_to_db as $date) {
//                    $wpv_new = new WorkerParameterValue();
//                    $wpv_new->worker_parameter_id = $date['worker_parameter_id'];
//                    $wpv_new->date_time = $date['date_time'];
//                    $wpv_new->value = (string)$date['value'];
//                    $wpv_new->status_id = $date['status_id'];
//                    $wpv_new->shift = $date['shift'];
//                    $wpv_new->date_work = $date['date_work'];
//                    if (!$wpv_new->save()) {
//                        $log->addData($wpv_new->errors, '$wpv_new->errors', __LINE__);
//                        throw new Exception('Ошибка сохранения значения параметра в БД');
//                    }
//                }
            }

            $log->addLog("Закончил массовую вставку в БД");


            /**
             * блок массовой вставки значений в кеш
             */
            if (isset($date_to_cache)) {

                $response = StrataJobController::checkExpiredPackage($worker_sensor['worker_id'], $package_date_time, 83, 2, $worker_parameter_value_cache_array);
                $log->addLogAll($response);

                if ($response['status'] == 1 and $response['move_to_cache'] == true) {
                    $ask_from_method = $worker_cache_controller->multiSetWorkerParameterValueHash($date_to_cache, $worker_sensor['worker_id']);
                    if ($ask_from_method['status'] != 1) {
                        throw new Exception('Не смог обновить параметры в кеше работника' . $worker_sensor['worker_id']);
                    }
                    $log->addLog("Массовая вставка в кеш завершена");
                } else {
                    $log->addLog("В КЕШ ДАННЫЕ НЕ ПИСАЛ, ОНИ ПРОСРОЧЕНЫ: " . $response['move_to_cache'] . " ИЛИ ОШИБКА ОПРЕДЕЛЕНИЯ ПРОСРОЧКИ: " . $response['status']);
                }

            } else {
                $log->addLog("Нет данных для вставки в КЕШ");
            }


            /**
             * Сохранение в кеш зачекиненных, если воркер не в ламповой
             */
            if ($flagRegistration) {
                $worker_cache_controller->initWorkerMineHash($mine_id, $worker_sensor['worker_id']);
                $log->addLog('Воркер ' . $worker_sensor['worker_id'] . ' сохранен в кеш зачекиненных');
            }

            $log->addLog('Инициализация кеша в работнике если его там не было: ' . $flagRegistration);

            /**
             * Блок отправки на веб-сокет
             */
            if (isset($date_to_cache)) {
                $worker = array();
                foreach ($date_to_cache as $parameter) {
                    $worker[$parameter['worker_id']]['parameters'][$parameter['parameter_id'].':'.$parameter['parameter_type_id']] = $parameter;
                }

                $response = WebsocketController::SendMessageToWebSocket('horizon',
                    array(
                        'type' => 'WorkerParameter',
                        'message' => $worker
                    )
                );
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка отправки данных на веб-сокет');
                }
            }

            $log->addLog('Конец метода сохранения параметров воркера');

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog('Окончание выполнения метода');

        return array_merge(['Items' => []], $log->getLogAll());
    }

    /**
     * saveLocationPacketEquipmentParameters - Сохраняет параметры оборудования, полученные из пакета положения шахтёра.
     * @param $package_date_time
     * @param array $equipment_sensor Массив с данными о привязке оборудования к сенсору
     * @param $xyz
     * @param $edge_id
     * @param $place_id
     * @param $status_danger_zone
     * @param $mine_id
     * @return array
     */
    public static function saveLocationPacketEquipmentParameters($package_date_time, $equipment_sensor, $xyz, $edge_id, $place_id, $status_danger_zone, $mine_id)
    {
        $log = new LogAmicumFront("saveLocationPacketEquipmentParameters");

        try {
            $log->addLog('Начало метода');
            // Сохранение координат
            $response = StrataJobController::SaveEquipmentParameter(
                $equipment_sensor['equipment_id'],
                ParameterTypeEnumController::MEASURED, ParamEnum::COORD,
                $xyz,
                $package_date_time,
                1
            );
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения координат оборудования');
            }

            // Сохранение выработки
            StrataJobController::SaveEquipmentParameter(
                $equipment_sensor['equipment_id'],
                ParameterTypeEnumController::MEASURED, ParamEnum::EDGE_ID,
                $edge_id,
                $package_date_time,
                1
            );
            // Сохранение местоположения
            StrataJobController::SaveEquipmentParameter(
                $equipment_sensor['equipment_id'],
                ParameterTypeEnumController::MEASURED, ParamEnum::PLACE_ID,
                $place_id,
                $package_date_time,
                $status_danger_zone
            );
            // Сохранение состояния
            StrataJobController::SaveEquipmentParameter(
                $equipment_sensor['equipment_id'],
                ParameterTypeEnumController::CALCULATED, ParamEnum::STATE,
                1,
                $package_date_time,
                1
            );
            // перенос оборудования между шахтами, если они сменились
            // todo метод требует оптимизации т.к. произовдится двойная проверка и запрос в кеш, в случае если сменилась шахта, кроме того, нужно добавить обработчики и проверку на корректность обработки и возврата данных
            $equipment_move = false;
            $equipment_cache_controller = new EquipmentCacheController();
            $equipment_mines = $equipment_cache_controller->getEquipmentMineByEquipment($equipment_sensor['equipment_id']);
            if ($equipment_mines) {
                foreach ($equipment_mines as $equipment_mine) { // может вернуться массив списка шахт, потому сдела форич
                    if ($equipment_mine['mine_id'] != $mine_id) {
                        $equipment_move = true;
                    }
                }
                if ($equipment_move === true) {
                    EquipmentMainController::moveEquipmentMineInitCache($equipment_sensor['equipment_id'], $mine_id);
                }
            }
            // Сохранение шахты
            StrataJobController::SaveEquipmentParameter(
                $equipment_sensor['equipment_id'],
                ParameterTypeEnumController::MEASURED, ParamEnum::MINE_ID,
                $mine_id,
                $package_date_time,
                1
            );
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog('Окончание выполнения метода');

        return array_merge(['Items' => []], $log->getLogAll());
    }


    /**
     * saveEnvironmentalPacketSensorParameters - Сохранение параметров сенсора из пакета газов
     * @param $pack
     * @param $sensor_id - ключ сенсора
     * @param $porog_val - пороговое значение газа
     * @param $last_coord - последняя координата
     * @param $last_place - ключ последнего места
     * @param $last_edge - ключ последней выработки
     * @param $parameter_id - ключ параметра газа (СО или СН4) 98/99
     * @param $status_id - ключ статуса значения
     * @param $parameter_excess_id - ключ параметра превышения газа
     * @param $is_gas_excess - флаг наличия превышения газа
     * @return array
     */
    public static function saveEnvironmentalPacketSensorParameters($pack, $sensor_id, $porog_val, $last_coord, $last_place, $last_edge, $parameter_id, $status_id, $parameter_excess_id, $is_gas_excess)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("saveEnvironmentalPacketSensorParameters");

        try {
            $log->addLog("Начало выполнения метода");

            /**=================================================================
             * Получаем за раз все последние значения по сенсору из кеша
             * =================================================================*/
            $sensor_parameter_value_cache_array = null;

            $sensor_cache_controller = new SensorCacheController();
            $sensor_parameter_value_list_cache = $sensor_cache_controller->multiGetParameterValueHash($sensor_id, '*', 2);

            if ($sensor_parameter_value_list_cache !== false) {
                foreach ($sensor_parameter_value_list_cache as $sensor_parameter_value_cache) {
                    $sensor_parameter_value_cache_array[$sensor_parameter_value_cache['sensor_id']][$sensor_parameter_value_cache['parameter_type_id']][$sensor_parameter_value_cache['parameter_id']] = $sensor_parameter_value_cache;
                }
                $log->addLog("Нашел данные по сенсору в кеше");
            } else {
                $log->addLog("Не нашел данные по сенсору в кеше");
                $log->addData($sensor_parameter_value_list_cache, '$sensor_parameter_value_list_cache', __LINE__);
                $log->addData($sensor_id, '$sensor_id', __LINE__);
            }

            /**=================================================================
             * Сохранение параметров положения и состояния для сенсора
             * ==================================================================*/
            // Coord (83)
            $response = StrataJobController::saveSensorParameterBatchForce($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::COORD, $last_coord, $pack->measurementTime, 1, $sensor_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения параметра ' . ParamEnum::COORD);
            }
            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];                                                // Отключил, т.к. при таком раскладе неверно расчитывается скорость движения работника (там стоит проверка на время, а тут получается, что время обновляется  (на 1 минуту вырастает погрешность)
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }


            // Place (122)
            $response = StrataJobController::saveSensorParameterBatchForce($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::PLACE_ID, $last_place, $pack->measurementTime, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения параметра ' . ParamEnum::PLACE_ID);
            }
            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];                                                  // Отключил, т.к. при таком раскладе неверно расчитывается скорость движения работника (там стоит проверка на время, а тут получается, что время обновляется  (на 1 минуту вырастает погрешность)
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }

            // Edge (269)
            $response = StrataJobController::saveSensorParameterBatchForce($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::EDGE_ID, $last_edge, $pack->measurementTime, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения параметра ' . ParamEnum::EDGE_ID);
            }
            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];                                                  // Отключил, т.к. при таком раскладе неверно расчитывается скорость движения работника (там стоит проверка на время, а тут получается, что время обновляется  (на 1 минуту вырастает погрешность)
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }

            // Состояние (164)
            $response = StrataJobController::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::CALCULATED, ParamEnum::STATE, 1, $pack->measurementTime, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения параметра ' . ParamEnum::STATE);
            }

            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }


            /**=================================================================
             * Сохранение значений параметров газа для сенсоров
             * ==================================================================*/
            $response = StrataJobController::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, $parameter_id, $pack->meterage, $pack->measurementTime, $status_id, $sensor_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения параметра ' . $parameter_id);
            }

            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }


            $response = StrataJobController::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, $parameter_excess_id, $is_gas_excess, $pack->measurementTime, $status_id, $sensor_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения параметра превышения газа ' . $parameter_excess_id);
            }
            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }

            $log->addLog("Закончил подготовку данных");

            /**=================================================================
             * блок массовой вставки значений в БД
             * =================================================================*/

            if (isset($date_to_db)) {
//                    Yii::$app->db_amicum2->createCommand()->batchInsert('sensor_parameter_value',
//                        ['sensor_parameter_id', 'date_time', 'value', 'status_id'],
//                        $date_to_db)->execute();
                $insert_param_val = Yii::$app->db_amicum2->queryBuilder->batchInsert('sensor_parameter_value', ['sensor_parameter_id', 'date_time', 'value', 'status_id'], $date_to_db);
                Yii::$app->db_amicum2->createCommand($insert_param_val)->execute();
//                    Yii::$app->db_amicum2->createCommand($insert_param_val . " ON DUPLICATE KEY UPDATE `value` = VALUES (`value`), `status_id` = VALUES (`status_id`)")->execute();
            }

            $log->addLog("Массово вставил данные в БД");

            /**=============================================================
             * блок массовой вставки значений в кеш
             * =============================================================*/
            if (isset($date_to_cache)) {
                $ask_from_method = (new SensorCacheController)->multiSetSensorParameterValueHash($date_to_cache);
                $log->addLogAll($ask_from_method);
                if ($ask_from_method['status'] != 1) {
                    throw new Exception('Не смог обновить параметры в кеше сенсора ' . $sensor_id);
                }
            }

            $log->addLog("Массово вставил данные в Кеш");

            /**=================================================================
             * Добавление записи в таблицу summary_report_sensor_gas_concentration,
             * если есть превышение нормы
             * ==================================================================*/
            if ($is_gas_excess) {
                StrataJobController::AddSummaryReportSensorGasConcentrationRecord($sensor_id, $parameter_id, $pack->meterage, $porog_val, $pack->measurementTime, $last_edge, $last_place);
                $log->addLog("Добавление записи в таблицу summary_report_sensor_gas_concentration");
            }

            $log->addLog("Конец метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Сохранение параметров воркера из пакета газов
     * @param $pack
     * @param $mine_id - ключ шахты работника
     * @param $worker_sensor - ключ лампы работника
     * @param $status_danger_zone - флаг опасной зоны
     * @param $last_coord - последняя координата, где был зафиксирован шахтер
     * @param $last_place - ключ последнего места где был зафиксирован шахтер
     * @param $last_edge - ключ последней выработки где был зафиксирован шахтер
     * @param $parameter_id - ключ параметра
     * @param $status_id - ключ статуса значения парамтера
     * @param $parameter_excess_id - параметр по которому было выявлено превышение
     * @param $is_gas_excess - флаг наличия превышения газа
     * @param $event_id - ключ события
     * @return array
     */
    public static function saveEnvironmentalPacketWorkerParameters($pack, $mine_id, $worker_sensor, $status_danger_zone, $last_coord, $last_place, $last_edge, $parameter_id, $status_id, $parameter_excess_id, $is_gas_excess, $event_id)
    {
        $log = new LogAmicumFront("saveEnvironmentalPacketWorkerParameters");

        $result = array();                                                                                              // результирующий массив (если требуется)

        try {

            $log->addLog("Начало выполнения метода");

            /**
             * Получаем за раз все последние значения по воркеру из кеша
             */
            $worker_cache_controller = new WorkerCacheController();
            $worker_parameter_value_list_cache = $worker_cache_controller->multiGetParameterValueHash($worker_sensor['worker_id'], '*', '*');
            if ($worker_parameter_value_list_cache === false) {
                $worker_parameter_value_cache_array = null;
                $log->addLog("НЕ получил данные с кеша по всему воркеру. Кеш воркера ПУСТ");
            } else {
                foreach ($worker_parameter_value_list_cache as $worker_parameter_value_cache) {
                    $worker_parameter_value_cache_array[$worker_parameter_value_cache['worker_id']][$worker_parameter_value_cache['parameter_type_id']][$worker_parameter_value_cache['parameter_id']] = $worker_parameter_value_cache;
                }
                $log->addLog("Получил данные с кеша по всему воркеру. Кеш воркера ПОЛОН");
            }

            $log->addLog("Получил за раз все последние значения по воркеру из кеша");

            /**=================================================================
             * Сохранение параметров положения и состояния для воркера
             * ==================================================================*/
            $log->addLog("Обработка параметров положения и состояния для воркера");
            //Сохранить параметр воркера Координаты (83)
            $response = StrataJobController::saveWorkerParameterForceBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, ParamEnum::COORD, $last_coord, $pack->measurementTime, StatusEnumController::ACTUAL);
            $log->addLogAll($response);
            if ($response['status'] == 1) {
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                throw new Exception('Ошибка сохранения параметра ' . ParamEnum::COORD);
            }

            $log->addLog("Обработал координату и состояние работника (но не сохранил)");

            //Сохранить параметр воркера Местоположение (122)
            $response = StrataJobController::saveWorkerParameterForceBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, ParamEnum::PLACE_ID, $last_place, $pack->measurementTime, $status_danger_zone);
            $log->addLogAll($response);
            if ($response['status'] == 1) {
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                throw new Exception('Ошибка сохранения параметра ' . ParamEnum::PLACE_ID);
            }

            $log->addLog("Обработал место (place) работника (но не сохранил)");

            //Сохранить параметр воркера Местоположение (269)
            $response = StrataJobController::saveWorkerParameterForceBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, ParamEnum::EDGE_ID, $last_edge, $pack->measurementTime, StatusEnumController::ACTUAL);
            $log->addLogAll($response);
            if ($response['status'] == 1) {
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                throw new Exception('Ошибка сохранения параметра ' . ParamEnum::EDGE_ID);
            }

            $log->addLog("Обработал выработку (edge) работника (но не сохранил)");

            /**=================================================================
             * Сохранение значений параметров газа для воркера
             * ==================================================================*/
            $log->addLog("Сохранение значений параметров превышения газа для воркера");
            $response = StrataJobController::saveWorkerParameterBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, $parameter_excess_id, $is_gas_excess, $pack->measurementTime, $status_id, $worker_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] == 1) {
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                throw new Exception('Ошибка сохранения параметра ' . $parameter_excess_id);
            }

            $log->addLog('Обработал Параметр статус превышения (386(метан)/387(СО)) газа работника (но не сохранил)' . $parameter_excess_id);

            /**
             * Вычисление статусов в зависимости от значения превышения газа
             */
            if ($is_gas_excess) {
                $value_status_id = StatusEnumController::EMERGENCY_VALUE;
                $event_status_id = StatusEnumController::EVENT_RECEIVED;
            } else {
                $value_status_id = StatusEnumController::NORMAL_VALUE;
                $event_status_id = StatusEnumController::EVENT_ELIMINATED_BY_SYSTEM;
            }

            $log->addLog('Обработал Статус превышения (386(метан)/387(СО)) газа у работника (но не сохранил)');

            /**
             * Сохранение значения концентрации газа
             */
            $log->addLog('Сохранение значения газа');
            $response = StrataJobController::saveWorkerParameterBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, $parameter_id, $pack->meterage, $pack->measurementTime, StatusEnumController::ACTUAL, $worker_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] == 1) {
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                throw new Exception('Ошибка сохранения параметра ' . $parameter_id);
            }

            $log->addLog('Обработал значение газа (98(СО)/99(метан)) работника (но не сохранил)' . $parameter_id);

            /**=================================================================
             * блок массовой вставки значений в БД
             * =================================================================*/
            if (isset($date_to_db)) {
//                    Yii::$app->db_amicum2->createCommand()->batchInsert('worker_parameter_value',
//                        ['worker_parameter_id', 'date_time', 'value', 'status_id', 'shift', 'date_work'],
//                        $date_to_db)->execute();
                $insert_param_val = Yii::$app->db_amicum2->queryBuilder->batchInsert('worker_parameter_value', ['worker_parameter_id', 'date_time', 'value', 'status_id', 'shift', 'date_work'], $date_to_db);
//                    Yii::$app->db_amicum2->createCommand($insert_param_val)->execute();
                Yii::$app->db_amicum2->createCommand($insert_param_val . " ON DUPLICATE KEY UPDATE `value` = VALUES (`value`), `status_id` = VALUES (`status_id`), `shift` = VALUES (`shift`), `date_work` = VALUES (`date_work`)")->execute();
            }

            $log->addLog('Массовая вставка параметров в БД');

            /**
             * блок массовой вставки значений в кеш
             */
            if (isset($date_to_cache)) {
                $ask_from_method = (new WorkerCacheController)->multiSetWorkerParameterValueHash($date_to_cache, $worker_sensor['worker_id']);
                $log->addLogAll($ask_from_method);
                if ($ask_from_method['status'] != 1) {
                    throw new Exception('Не смог обновить параметры в кеше работника' . $worker_sensor['worker_id']);
                }
            }

            $log->addLog('Массовая вставка параметров в кеш');

            /**
             * Блок отправки на веб-сокет
             */
            if (isset($date_to_cache)) {
                $worker = array();
                foreach ($date_to_cache as $parameter) {
                    $worker[$parameter['worker_id']]['parameters'][$parameter['parameter_id'].':'.$parameter['parameter_type_id']] = $parameter;
                }

                $response = WebsocketController::SendMessageToWebSocket('horizon',
                    array(
                        'type' => 'WorkerParameter',
                        'message' => $worker
                    )
                );
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка отправки данных на веб-сокет');
                }
            }


            /**
             * Генерация события для концентрации газа
             */
            if ($event_id != -1) {
                $response = EventMainController::createEventForWorkerGas('worker', $worker_sensor['worker_id'], $event_id, $pack->meterage, $pack->measurementTime, $value_status_id, $parameter_id, $mine_id, $event_status_id, $last_edge, $last_coord, $worker_sensor['sensor_id']);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Не смог создать событие работника' . $worker_sensor['worker_id']);
                }
            }

            $log->addLog('Сгенерировал событие');
            $log->addLog('Конец метода');


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * checkForbiddenZoneStatus - Проверяет находится ли объект в запретной зоне.
     * Если объект входит в запретную зону, то фиксируется время входа.
     * При выходе объекта из запретной зоны вычисляется длительность его нахождения
     * в ней и создается запись в отчётной таблице summary_report_forbidden_zones
     * @param $status_danger_zone - Статус зоны (запретная/разрешённая)
     * @param $object - Объект, который сохраняется в базу.
     * Это либо воркер/оборудование, привязанный к сенсору, либо сам сенсор,
     * если к нему не привязан никакой объект.
     * @param $place_id - Объект плейса в котором находится сенсор
     * @return array
     */
    public static function checkForbiddenZoneStatus($package_date_time, $network_id, $status_danger_zone, $object, $main_title, $table_name, $place_id, $edge_id)
    {
        $log = new LogAmicumFront("checkForbiddenZoneStatus");

        try {
            $log->addLog("Начало метода");

            // Проверка на нахождение в запрещённой зоне
            // При выходе из запретной зоны в отчётную таблицу заносится запись
            $service_cache = new ServiceCache();
            $cache_key = 'ObjectDangerZone_' . $network_id;

            $object_danger_info = $service_cache->amicum_rGet($cache_key);                                              // Получение данных из кэша

            if (!$object_danger_info) {                                                                                 // Если кэша не существует, то его начальная генерация
                $log->addLog("Кеш не существует, генерация");
                $cache_array = array();
                $cache_array['danger_zone_status'] = StatusEnumController::PERMITTED;
                $cache_array['date_start'] = 0;
                $cache_array['edge_id'] = $edge_id;
                $cache_array['place_id'] = $place_id;
                $service_cache->amicum_rSet($cache_key, $cache_array);
                $object_danger_info = $cache_array;
                unset($cache_array);
            }

            if ($object_danger_info and isset($object_danger_info['danger_zone_status']) and $object_danger_info['danger_zone_status'] != $status_danger_zone) {                                     // Если статус запретной зоны изменился
                $log->addLog(' Статус запретной зоны изменился с ' . $object_danger_info['danger_zone_status'] . ' на ' . $status_danger_zone);

                if ($status_danger_zone == StatusEnumController::FORBIDDEN) {                                          // Если объект вошёл в запретную зону
                    $log->addLog('Объект вошёл в запретную зону, фиксирую время');

                    $object_danger_info['danger_zone_status'] = StatusEnumController::FORBIDDEN;                        // Генерация в кэше структуры, обозначающей метку времени входа в запретную зону
                    $object_danger_info['date_start'] = date('Y-m-d H:i:s');
                    $object_danger_info['edge_id'] = $edge_id;
                    $object_danger_info['place_id'] = $place_id;
                    $service_cache->amicum_rSet($cache_key, $object_danger_info);
                } elseif ($status_danger_zone == StatusEnumController::PERMITTED) {                                    // Если объект вышел из запретной зоны
                    $log->addLog('Объект вышел из запретной зоны, расчёт времени и генерация события');
                    $object_danger_info['danger_zone_status'] = StatusEnumController::PERMITTED;

                    $forbidden_zone_duration = Assistant::GetMysqlTimeDifference(                                       // Расчёт времени нахождения в запретной зоне
                        $package_date_time,
                        $object_danger_info['date_start']
                    );

                    $shift = StrataJobController::getShiftDateNum($package_date_time);                                  // Нахождение дополнительных данных для создания записи в отчётной таблице

                    // Запись факта нахождения в запретной зоне в отчётную таблицу
                    Yii::$app->db_amicum2->createCommand()->insert('summary_report_forbidden_zones', [
                        'date_work' => $shift['shift_date'],
                        'shift' => $shift['shift_num'],
                        'main_id' => $object->id,
                        'main_title' => $main_title,
                        'table_name' => $table_name,
                        'place_id' => $object_danger_info['place_id'],
                        'edge_id' => $object_danger_info['edge_id'],
                        'object_id' => $object->object_id,
                        'place_status_id' => $status_danger_zone,
                        'date_time_start' => $object_danger_info['date_start'],
                        'date_time_end' => $package_date_time,
                        'duration' => Assistant::SecondsToTime($forbidden_zone_duration)
                    ])->execute();

                    $object_danger_info['date_start'] = 0;                                                              // "Сброс" кэша
                    $service_cache->amicum_rSet($cache_key, $object_danger_info);
                }
            } else {
                $log->addLog('Статус запрета не поменялся: ' . $status_danger_zone);
                $log->addLog('Статус запрета не поменялся: ' . (($object_danger_info and isset($object_danger_info['danger_zone_status'])) ? $object_danger_info['danger_zone_status'] : -1));
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog('Окончил выполнение метода');

//        LogCacheController::setStrataLogValue('checkForbiddenZoneStatus', $log->getLogAll(), '2');

        return array_merge(['Items' => []], $log->getLogAll());
    }

    /**
     * actionGenerateNodeByQuery - Метод генерации узлов связи/считывателей на основе очередей
     * @return array
     * @example 127.0.0.1/admin/horizon/horizon/generate-node-by-query?mine_id=2
     */
    public static function actionGenerateNodeByQuery()
    {
        $log = new LogAmicumFront("actionGenerateNodeByQuery");

        try {
            $log->addLog("Начало метода");
            $post = Assistant::GetServerMethod();
            $mine_id = $post['mine_id'];

            $response = HorizonQueueController::getQuriesKeys($mine_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка получения очередей");
            }
            $queries = $response['Items'];
            $log->addData($queries, '$queries', __LINE__);
            foreach ($queries as $query) {
                $query_param = explode(":", $query);
                if (count($query_param) == 3 and $query_param[0] == "StQu") {
                    $network_id = $mine_id . ":" . $query_param[2];

                    $response = SensorMainController::getOrSetSensorByNetworkId($network_id);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception("saveHeartbeatPacket. Ошибка при инициализации сенсора по сетевому адресу: " . $network_id);
                    }

                    $log->addLog("Успешно закончил поиск в кеше и БД ключа сенсора по нет айд");
                    if ($response['sensor_id'] === false) {
                        $title = 'Линейный считыватель networkID ' . $network_id;
                        $response = StrataJobController::createSensorDatabase($title, $network_id, $mine_id, 105, 31, 11);
                        $log->addLogAll($response);
                        if ($response['status'] != 1) {
                            throw new Exception("Ошибка создания сенсора по нетворк айди $network_id в кеше и в БД");
                        }
                    }
                    $sensor_id = $response['sensor_id'];

                    $log->addLog("Найден sensor_id: $sensor_id по network_id: $network_id и проинициализирован сенсор в кеш");
                }
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog('Окончил выполнение метода');

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => []], $log->getLogAll());
    }

    /**
     * actionInitSensorNetworkFromDb - Метод тестирования инициализации сенсора по сетевому адресу
     * @return array
     * @example 127.0.0.1/admin/horizon/horizon/init-sensor-network-from-db?net_id=17652
     */
    public static function actionInitSensorNetworkFromDb()
    {
        $log = new LogAmicumFront("actionInitSensorNetworkFromDb");

        try {
            $log->addLog("Начало метода");
            $post = Assistant::GetServerMethod();
            $net_id = $post['net_id'];
            $service_cache = new ServiceCache();
            $response = $service_cache->initSensorNetworkFromDb($net_id);
            $log->addData($response, '$response', __LINE__);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog('Окончил выполнение метода');

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => []], $log->getLogAll());
    }

    /**
     * actionGetPackageFromQueue - Метод получения данных из очереди
     * @return array
     * @example 127.0.0.1/admin/horizon/horizon/get-package-from-queue?queue_id="ErStQu:2:257"
     */
    public static function actionGetPackageFromQueue()
    {
        $log = new LogAmicumFront("actionGetPackageFromQueue");

        try {
            $log->addLog("Начало метода");
            $post = Assistant::GetServerMethod();
            $queue_id = $post['queue_id'];
            $log->addData($queue_id, '$queue_id', __LINE__);
            $response = HorizonQueueController::PullFromQuery("11", $queue_id);
            $log->addLogAll($response);
            $log->addData($response['Items'], '$response', __LINE__);
            $json_package = $response['Items'];

            $package = json_decode($json_package);
            $log->addData($package, '$package', __LINE__);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog('Окончил выполнение метода');

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => []], $log->getLogAll());
    }

    /**
     * sendWorkingHoursToRabbitMQ - Метод отправки пакета working_hours в очередь RabbitMQ
     * Метод собирает данные о смене, формирует json из данных и отправляет в RabbitMQ
     * @param $worker_id - ключ работника
     * @param $date_time_start - дата и время начала смены
     * @param $date_time_end - дата и время конца смены
     * @param int $isSendToRabbit - если 1 отправить в RabbitMQ, если иное, то отправить в Redis
     * @param $worker_info - модель работника для формирования недостающих данных
     * @return array
     */
    public static function sendWorkingHoursToRabbitMQ($worker_id, $date_time_start, $date_time_end, int $isSendToRabbit = self::SENDING_TO_REDIS, $worker_info = null)
    {
        $log = new LogAmicumFront("sendWorkingHoursToRabbitMQ");

        $result = null;

        try {
            $log->addLog("Начало метода");

            if (strtotime($date_time_end) - strtotime($date_time_start) < 1800) {
                throw new Exception("Разница во времени меньше 30 минут");
            }

            if (!$worker_info) {
                $worker = Worker::find()
                    ->joinWith('employee')
                    ->joinWith('company')
                    ->where(['worker.id' => $worker_id])
                    ->one();

                if (!$worker) {
                    throw new Exception("Рабочий не найден по id $worker_id");
                }
                $fio = Assistant::GetFullName($worker->employee->first_name, $worker->employee->patronymic, $worker->employee->last_name);
                $employee_link_1c = $worker->employee->link_1c;
                $tabel_number = $worker->tabel_number;
                $company_link_1c = $worker->companyDepartment->company->link_1c;
                $company_title = $worker->companyDepartment->company->title;
            } else {
//                'FIO',
//                'worker_object_id',
//                'department_title',
//                'company_title',
//                'tabel_number',
//                'department_id',
//                'company_id'
                $log->addData($worker_info, '$worker_info', __LINE__);
                $fio = $worker_info['FIO'];
                $tabel_number = $worker_info['tabel_number'];
                $company_link_1c = $worker_info['company_link_1c'];
                $company_title = $worker_info['company_title'];

                $worker = Worker::find()
                    ->joinWith('employee')
                    ->where(['worker.id' => $worker_id])
                    ->one();
                $employee_link_1c = $worker->employee->link_1c;
            }

            if (!$date_time_end) {
                $data = ['date_work' => null, 'shift_title' => null];
            } else {
                $data = Assistant::GetShiftByDateTimeWorkingHours($date_time_end);
            }
            $working_hours = array(
                '_class' => 'working_hours',
                '_version' => '2.0.0.1',
                'Тип' => 'ПутевыеЛистыХоз',
                'Дата' => $data['date_work'],
                'Смена' => $data['shift_title'],
                'Номер' => '',
                'ПометкаУдаления' => false,
                'Организация' => array(
                    '_class' => 'organization',
                    '_version' => '2.0.0.1',
                    'Ссылка' => self::COMPANY_LINK_1C,
                    'Наименование' => self::COMPANY_TITLE,
                    'Тип' => '',
                    'СлужебныйКодОрганизации' => ''
                ),
                'ФизическоеЛицо' => array(
                    'ВидДанных' => 'подземные работы',
                    'ГУИД' => $employee_link_1c,
                    'ФизическоеЛицо' => $fio,
                    'ТабельныйНомер' => $tabel_number,
                    'События' => array(
                        array(
                            'ДатаСобытия' => $date_time_start,
                            'ВидСобытия' => self::STATUS_IN_MINE,                                                       // 1 - вход, 0 - выход
                            'ВидСобытияАмикум' => "Спустился в шахту"
                        ),
                        array(
                            'ДатаСобытия' => $date_time_end,
                            'ВидСобытия' => self::STATUS_OUT_MINE,                                                      // 1 - вход, 0 - выход
                            'ВидСобытияАмикум' => "Вышел из шахты"
                        ),
                    )
                )
            );

            $value = json_encode($working_hours, JSON_UNESCAPED_UNICODE);

            if ($isSendToRabbit == self::SENDING_TO_RABBIT) {
                $message = [
                    'method' => 'working_hours',
                    'correlation_id' => $worker_id,
                    'payload' => $value
                ];
                $message_id = Yii::$app->rabbitWorkingHours->pushMessage($message, 0, 0, 1);
                $log->addData($message_id, '$message_id', __LINE__);
                $log->addLog("Отправил данные в очередь RabbitMq");
            } else {
                $cache = new RedisQueueController();
                $response = $cache->PushToQuery($value);
                $log->addLogAll($response);
                $log->addLog("Уложил данные в кеш redis_rabbit");
            }
            $log->addData($value, "данные отправленные в слой интеграции", __LINE__);;
//            main.php
//            'rabbitWorkingHours' => [
//                'class' => RabbitController::class,
//                'as log' => LogBehavior::class,
//                'host' => '192.168.1.231',
//                'port' => 5672,
//                'user' => 'admin',
//                'password' => 'admin',
//                'queueName' => 'working_hours',
//                'exchangeName'=>'working_hours',
//                'vhost' => '/',
//                'dsn' => 'amqp://admin:admin@192.168.1.231:5672//',
//            ],

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog('Окончил выполнение метода');

        return array_merge(['Items' => []], $log->getLogAll());
    }

    /**
     * actionGetLogParamHorizon - Метод получения данных параметров Horizon
     * @return array
     * @example 127.0.0.1/admin/horizon/horizon/get-log-param-horizon?mine_id=2
     */
    public static function actionGetLogParamHorizon()
    {
        $log = new LogAmicumFront("actionGetLogParamHorizon");
        $result = null;

        try {
            $log->addLog("Начало метода");
            $post = Assistant::GetServerMethod();
            $mine_id = $post['mine_id'];

            $parameters = [
                'statusSignalR',
                'errorSignalR',
                'countPackage',
                'sumCountPackage1min',
                'sumCountPackage10min'
            ];

            $redis = Yii::$app->redis_yii2;
            foreach ($parameters as $parameter) {
                $result[$parameter] = $redis->get("horizon:$mine_id:".$parameter);
            }

            $parameters = [
                'stopSendPost',
                'querySizeSendPost',
                'statusSendPost',
                'countSendPost',
                'sumCountSendPost1min',
                'sumCountSendPost10min'
            ];

            $keys = $redis->keys("horizon:$mine_id:statusSendPost:*");

            foreach ($keys as $key) {
                $key = str_replace("horizon:$mine_id:statusSendPost:",'',$key);
                foreach ($parameters as $parameter) {
                    $result[$key][$parameter] = $redis->get("horizon:$mine_id:".$parameter.':'.$key);
                }
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog('Окончил выполнение метода');

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

}