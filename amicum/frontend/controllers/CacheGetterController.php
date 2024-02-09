<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers;
//ob_start();

use backend\controllers\Assistant as BackendAssistant;
use backend\controllers\cachemanagers\EdgeCacheController;
use backend\controllers\cachemanagers\EquipmentCacheController;
use backend\controllers\cachemanagers\EventCacheController;
use backend\controllers\cachemanagers\LogCacheController;
use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\cachemanagers\ServiceCache;
use backend\controllers\cachemanagers\SituationCacheController;
use backend\controllers\cachemanagers\WorkerCacheController;
use backend\controllers\SensorBasicController;
use backend\controllers\StrataJobController;
use backend\controllers\WorkerBasicController;
use backend\controllers\WorkerMainController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Sensor;
use Throwable;
use Yii;
use yii\web\Controller;
use yii\web\Response;


class CacheGetterController extends Controller
{
    // actionViewCacheWorker                - Функция для вывода значений работника из кеша по заданному ключу
    // actionViewCacheSensor                - Функция для вывода значений Сенсорам из кеша по заданному ключу
    // actionViewCacheEdge                  - Функция для вывода значений выработки из кеша по заданному ключу
    // actionViewCacheEquipment             - Функция для вывода значений оборудования из кеша по заданному ключу
    // actionViewCacheEvent                 - Функция для вывода значений события из кеша по заданному ключу
    // actionViewCacheSituation             - Функция для вывода значений ситуаций из кеша по заданному ключу
    // actionViewCacheService               - Функция для вывода значений сервисного параметра из кеша по заданному ключу
    // actionViewCacheRedis                 - Функция для вывода значений сервисного параметра из кеша по заданному ключу
    // actionLogs                           - метод для контрол логов ошибок из браузера

    // actionViewMessages                   - Метод проверки наличие сообщения на отправку на луч


    // actionSensorMethodTestCoordinate     - метод проверки БД/кеша/метода получения данных по сенсору на предмет
    // actionMultiGetCacheSensorParameter   - тест производительности редиса от ключей
    //                                        его установки на схеме шахты

    // actionGetSensorsList                 - сравнения данных в кеше с загружаемым документом

    // actionRemoveInactiveWorkers          - выписать из кеша всех не активных работников
    // actionForceCheckoutWorker            - выписать из кеша всех работников
    // actionForceCheckOutDbAndCache        - метод принудительной выписки работника из шахты как с базы, так и с кеша
    // actionForceCheckoutSensor            - выписать из кеша все сенсоры

    // actionFlushCacheSituation            - сброс кеша ситуаций
    // actionFlushCacheEvent                - сброс кеша событий
    // actionFlushCacheLogs                 - метод для удаления значения с кэш логов по ключу

    // MultiGetWorkerParameterValue         - метод получения всех параметров воркерав из кэша


    // ключи не правильные - используются только первые 2 буквы после заглавных
    public static $cache_worker_mine_key = 'CheckinWorkerMine_';                                                        // ключ кэша списка работников по шахте
    public static $cache_edge_mine_key_static = 'EdgeMine_';                                                            // ключ кэша выработок по шахте
    public static $cache_sensor_mine_key = 'SensorMine_';                                                               // ключ кэша сенсора по шахте. В ЭТОМ КЭШЕ ХРАНЯТСЯ ВСЕ СЕНСОРЫ, КОТОРЫЕ НАХОДЯТСЯ СЕНСОРЫ, ТОЛЬКО СПИСОК
    public static $cache_sensor_parameter_key = 'SensorParameter_';                                                     // ключ кэша сенсора по шахте
    public static $cache_edge_parameter_key = 'EdgeParameter_';                                                         // ключ кэша сенсора по шахте
    public $cache_edge_scheme_key = 'EdgeShema_';                                                                       // ключ кэша схемы выработки
    public static $cache_equipment_mine_key = 'EquipmentMine_';                                                            // ключ кэша списка оборудований по шахте
    public static $cache_equipment_type_parameter_key = 'EquipmentTypeParameter_';                                        // ключ кэша параметров конкретного оборудования
    public static $cache_equipment_parameter_key = 'EquipmentParameter_';                                                // ключ кэша значения параметра конкретного оборудования

    public function actionIndex()
    {
        return $this->render('index');
    }


    // 127.0.0.1/cache-getter/view-cache-service?cache_key=package
    public function actionViewCacheService()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();
        $warnings[] = 'actionViewCacheService. Выполнение метода начал';
        try {
            $microtime_start = microtime(true);
            $post = Assistant::GetServerMethod();
            $cache_key = (string)$post['cache_key'];
            $warnings[] = 'actionViewCacheService. прошел POST' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $redis = Yii::$app->redis_service;
            $warnings[] = 'actionViewCacheService. подключил кеш' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $keys = $redis->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1];
            $warnings[] = 'actionViewCacheService. Сосканировал кеш ключей' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $warnings[] = 'actionViewCacheService. Количество запрашиваемых ключей: ' . count($keys);
            $warnings[] = $keys;
            $warnings[] = 'actionViewCacheService. массив в строку' . $duration_method = round(microtime(true) - $microtime_start, 6);
            if ($keys) {
                $result = (new ServiceCache())->amicum_mGet($keys);
            } else {
                $result = 'actionViewCacheService. Нет кеша с таким ключом';
            }
            $warnings[] = 'actionViewCacheService. Получил данные с кеша' . $duration_method = round(microtime(true) - $microtime_start, 6);
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionViewCacheService. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'actionViewCacheService. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // 127.0.0.1/cache-getter/view-cache-event?cache_key=Ev
    public function actionViewCacheEvent()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();
        $warnings[] = 'actionViewCacheEvent. Выполнение метода начал';
        try {
            $microtime_start = microtime(true);
            $post = Assistant::GetServerMethod();
            $cache_key = (string)$post['cache_key'];
            $warnings[] = 'actionViewCacheEvent. прошел POST' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $redis = Yii::$app->redis_event;
            $warnings[] = 'actionViewCacheEvent. подключил кеш' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $keys = $redis->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1];
            $warnings[] = 'actionViewCacheEvent. Сосканировал кеш ключей' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $warnings[] = 'actionViewCacheEvent. Количество запрашиваемых ключей: ' . count($keys);
            $warnings[] = $keys;
            $warnings[] = 'actionViewCacheEvent. массив в строку' . $duration_method = round(microtime(true) - $microtime_start, 6);
            if ($keys) {
                $result = (new EventCacheController())->amicum_mGet($keys);
            } else {
                $result = 'actionViewCacheEvent. Нет кеша с таким ключом';
            }
            $warnings[] = 'actionViewCacheEvent. Получил данные с кеша' . $duration_method = round(microtime(true) - $microtime_start, 6);
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionViewCacheEvent. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'actionViewCacheEvent. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // 127.0.0.1/cache-getter/view-cache-situation?cache_key=Si
    public function actionViewCacheSituation()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();
        $warnings[] = 'actionViewCacheSituation. Выполнение метода начал';
        try {
            $microtime_start = microtime(true);
            $post = Assistant::GetServerMethod();
            $cache_key = (string)$post['cache_key'];
            $warnings[] = 'actionViewCacheSituation. прошел POST' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $redis = Yii::$app->redis_situation;
            $warnings[] = 'actionViewCacheSituation. подключил кеш' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $keys = $redis->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1];
            $warnings[] = 'actionViewCacheSituation. Сосканировал кеш ключей' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $warnings[] = 'actionViewCacheSituation. Количество запрашиваемых ключей: ' . count($keys);
            $warnings[] = $keys;
            $warnings[] = 'actionViewCacheSituation. массив в строку' . $duration_method = round(microtime(true) - $microtime_start, 6);
            if ($keys) {
                $result = (new SituationCacheController())->amicum_mGet($keys);
            } else {
                $result = 'actionViewCacheSituation. Нет кеша с таким ключом';
            }
            $warnings[] = 'actionViewCacheSituation. Получил данные с кеша' . $duration_method = round(microtime(true) - $microtime_start, 6);
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionViewCacheSituation. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'actionViewCacheSituation. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // 127.0.0.1/cache-getter/view-cache-edge?cache_key=Ed
    public function actionViewCacheEdge()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();
        $warnings[] = 'actionViewCacheEdge. Выполнение метода начал';
        try {
            $microtime_start = microtime(true);
            $post = Assistant::GetServerMethod();
            $cache_key = (string)$post['cache_key'];
            $warnings[] = 'actionViewCacheEdge. прошел POST' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $redis = Yii::$app->redis_edge;
            $warnings[] = 'actionViewCacheEdge. подключил кеш' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $keys = $redis->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1];
            $warnings[] = 'actionViewCacheEdge. Сосканировал кеш ключей' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $warnings[] = 'actionViewCacheEdge. Количество запрашиваемых ключей: ' . count($keys);
            $warnings[] = $keys;
            $warnings[] = 'actionViewCacheEdge. массив в строку' . $duration_method = round(microtime(true) - $microtime_start, 6);
            if ($keys) {
                $result = (new EdgeCacheController())->amicum_mGet($keys);
            } else {
                $result = 'actionViewCacheEdge. Нет кеша с таким ключом';
            }
            $warnings[] = 'actionViewCacheEdge. Получил данные с кеша' . $duration_method = round(microtime(true) - $microtime_start, 6);
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionViewCacheEdge. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'actionViewCacheEdge. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // 127.0.0.1/cache-getter/view-cache-equipment?cache_key=Eq
    public function actionViewCacheEquipment()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();
        $warnings[] = 'actionViewCacheEquipment. Выполнение метода начал';
        try {
            $microtime_start = microtime(true);
            $post = Assistant::GetServerMethod();
            $cache_key = (string)$post['cache_key'];
            $warnings[] = 'actionViewCacheEquipment. прошел POST' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $redis = Yii::$app->redis_equipment;
            $warnings[] = 'actionViewCacheEquipment. подключил кеш' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $keys = $redis->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1];
            $warnings[] = 'actionViewCacheEquipment. Сосканировал кеш ключей' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $warnings[] = 'actionViewCacheEquipment. Количество запрашиваемых ключей: ' . count($keys);
            $warnings[] = $keys;
            $keys_string = implode(',', $keys);
            $warnings[] = 'actionViewCacheEquipment. массив в строку' . $duration_method = round(microtime(true) - $microtime_start, 6);
            if ($keys) {
                $result = (new EquipmentCacheController())->amicum_mGet($keys);
                //$result = $redis->mget($keys_string);
            } else {
                $result = 'actionViewCacheEquipment. Нет кеша с таким ключом';
            }
            $warnings[] = 'actionViewCacheEquipment. Получил данные с кеша' . $duration_method = round(microtime(true) - $microtime_start, 6);
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionViewCacheEquipment. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'actionViewCacheEquipment. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionViewCacheWorker - Функция для вывода значений Рбаотников/оборудования/выработок из кеша по заданному ключу
    // 127.0.0.1/cache-getter/view-cache-worker?cache_key=WoMi:564456
    // 127.0.0.1/cache-getter/view-cache-worker?cache_key=WoPa:564456:88:1
    public function actionViewCacheWorker()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();
        $warnings[] = 'actionViewCacheWorker. Выполнение метода начал';
        try {
            $microtime_start = microtime(true);
            $post = Assistant::GetServerMethod();
            $cache_key = (string)$post['cache_key'];
            $warnings[] = 'actionViewCacheWorker. прошел POST' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $redis = Yii::$app->redis_worker;
            $warnings[] = 'actionViewCacheWorker. подключил кеш' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $keys = $redis->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1];
            $warnings[] = 'actionViewCacheWorker. Сосканировал кеш ключей' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $warnings[] = 'actionViewCacheWorker. Количество запрашиваемых ключей: ' . count($keys);
            $warnings[] = $keys;
            $keys_string = implode(',', $keys);
            $warnings[] = 'actionViewCacheWorker. массив в строку' . $duration_method = round(microtime(true) - $microtime_start, 6);
            if ($keys) {
                $result = (new WorkerCacheController())->amicum_mGet($keys);
                //$result = $redis->mget($keys_string);
            } else {
                $result = 'actionViewCacheWorker. Нет кеша с таким ключом';
            }
            $warnings[] = 'actionViewCacheWorker. Получил данные с кеша' . $duration_method = round(microtime(true) - $microtime_start, 6);
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionViewCacheWorker. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'actionViewCacheWorker. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionViewCacheSensor - Функция для вывода значений Сенсорам из кеша по заданному ключу
    //.../cache-getter/view-cache-sensor?cache_key=packages  -  просмотр кеша сообщений на отправку
    public function actionViewCacheSensor()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();
        $warnings[] = 'actionViewCacheSensor. Выполнение метода начал';
        try {
            $microtime_start = microtime(true);
            $post = Assistant::GetServerMethod();
            $cache_key = (string)$post['cache_key'];
            $warnings[] = 'actionViewCacheSensor. прошел POST' . $duration_method = round(microtime(true) - $microtime_start, 6);

            $redis = Yii::$app->redis_sensor;
            $redis_parameter = Yii::$app->redis_sensor_parameter;
            $redis_parameter_handbook = Yii::$app->redis_sensor_parameter_handbook;
            $warnings[] = 'actionViewCacheSensor. подключил кеш' . $duration_method = round(microtime(true) - $microtime_start, 6);

            $keys = $redis->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1];
            $keys = array_merge($keys, $redis_parameter->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1]);
            $keys = array_merge($keys, $redis_parameter_handbook->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1]);
            $warnings[] = 'actionViewCacheSensor. Со сканировал кеш ключей' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $warnings[] = 'actionViewCacheSensor. Количество запрашиваемых ключей: ' . count($keys);
            $warnings[] = $keys;

            if ($keys) {
                $sensor_cache_controller = new SensorCacheController();
                $result = $sensor_cache_controller->amicum_mGet($sensor_cache_controller->sensor_cache, $keys);
                $result = array_merge($result, $sensor_cache_controller->amicum_mGet($sensor_cache_controller->sensor_parameter_cache, $keys));
                $result = array_merge($result, $sensor_cache_controller->amicum_mGet($sensor_cache_controller->sensor_parameter_handbook_cache, $keys));
            } else {
                $result = 'actionViewCacheSensor. Нет кеша с таким ключом';
            }
            $warnings[] = 'actionViewCacheSensor. Получил данные с кеша' . $duration_method = round(microtime(true) - $microtime_start, 6);
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionViewCacheSensor. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'actionViewCache. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }



    //Функция для вывода значений из кеша по заданному ключу
    //.../cache-getter/view-cache-redis?cache_key=packages  -  просмотр кеша сообщений на отправку
    public function actionViewCacheRedis()
    {
        $post = Assistant::GetServerMethod();
        $cache = Yii::$app->redis_service;
        $cache_key = $post['cache_key'];

        if ($cache->exists($cache_key)) {
            $pack = $cache->lrange($post['cache_key'], 0, $cache->llen($post['cache_key']));
            Assistant::VarDump($pack);
        } else {
            echo 'Нет кеша с таким ключом';
        }
    }


    /**
     * Метод actionGetSensorsList() - Метод сравнения сенсоров в кэше и в предоставленном документе
     * @package frontend\controllers
     * @example http://localhost/cache-getter/get-sensors-list?mine_id=290
     * Входные параметры:
     *      Обязательные:
     *          mine_id     - id шахты.
     * Выходные параметр:
     *          $in_cache    - узлы связи, которые есть в кэше, но отсутствуют в документе
     *          $in_doc    - узлы связи, которые есть в документе, но отсутствуют в кэше
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 15.07.2019 16:59
     */
    public function actionGetSensorsList()
    {
        $errors = array();                                                                                              //массив ошибок
        $status = 1;                                                                                                    //состояние выполнения метода
        $result = null;
        $path = null;
        $warnings = array();
        $sensors_csv = array();
        require_once(Yii::getAlias('@vendor/moonlandsoft/yii2-phpexcel/Excel.php'));

        require_once(Yii::getAlias('@vendor/phpoffice/phpexcel/Classes/PHPExcel.php'));
        $warnings[] = "actionGetSensorsList начал выоплнение метода";
        try {
            $warnings[] = "actionGetSensorsList. зашел в метод";
            /**
             * Блок обработки входных данных
             */
            $post = Assistant::GetServerMethod();
            if (isset($post['mine_id']) and $post['mine_id'] != "") {
                $mine_id = $post['mine_id'];
                $warnings[] = "actionGetSensorsList. Получили входные данные $mine_id";
            } else {
                throw new \Exception("actionGetSensorsList. Обязательный входной параметр mine_id не передан");
            }

            /**
             * Блок импорта значений датчиков из файла
             */

            $sensors_csv = array();
            $handle = fopen("C:\\Users\\Ingener401\\Desktop\\data.csv", "r");                                               //поля в файле: порядковый номер, название и net_id
            if ($handle) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $sensors_csv[$data[2]] = $data;                                                                           //переиндексация массива
                }
                $warnings[] = "actionGetSensorsList. Список сенсоров из файла получен ";

            } else {
                throw new \Exception('actionGetSensorsList. Сенсоры не выгружены ');
            }
            $warnings[] = "actionGetSensorsList. Закончил получать список сенсоров ";

            /**
             * Блок получения параметров 88 узлов связи из кеша
             */
            $sensor_cache_controller = (new SensorCacheController());
            $response = $sensor_cache_controller->getSensorsParametersValuesHash($mine_id, '*', "88:1");
            $in_cache = array();                                                                                           //net_id сенсоров, хранящихся в кэше, но отсутствующих в документе
            $sensors_cache = array();                                                                                            //массив всех net_id из кэша
            if ($response['status'] == 1) {
                $warnings[] = "actionGetSensorsList. Список параметров 88  тип 1 сенсоров шахты получен";
                $sensor_parameters = $response['Items'];
                //$warnings[] = $sensor_parameters;

                /**
                 * Блок поиска сенсоров из кэша, которые отсутствуют в документе
                 */
                foreach ($sensor_parameters as $sensor_parameter) {
                    if (!isset($sensors_csv[$sensor_parameter['value']])
                        && ($sensor_parameter['object_id'] == 45 ||
                            $sensor_parameter['object_id'] == 46 ||
                            $sensor_parameter['object_id'] == 105 ||
                            $sensor_parameter['object_id'] == 90 ||                                                            //ищем сенсоры в кэше, которых нет в документе, тип которых = 45,46,90,91,105
                            $sensor_parameter['object_id'] == 91)
                    ) {
                        $in_cache[] = $sensor_parameter['value'];
                    }
                    $sensors_cache[] = $sensor_parameter['value'];

                }
                /**
                 * Блок поиска сенсоров из документа, которые отсутствуют в кэше
                 */
                foreach ($sensors_csv as $sensor_net_id) {
                    if (!in_array($sensor_net_id[2], $sensors_cache)) {
                        $in_doc[] = $sensor_net_id[2];                                                                        //ищем сенсоры в документе, которых нет в кэше
                    }
                }

            } else {
                $errors[] = $response['errors'];
                throw new \Exception('actionGetSensorsList. Кеш параметров сенсоров шахты ' . $mine_id . ' пуст');
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "actionGetSensorsList. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }


        $warnings[] = "actionGetSensorsList. Закончил выполнение метода";
        $result = ['in_document' => $in_doc, 'in_cache' => $in_cache];
        $result_main = array(
            'Items' => $result,
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings);

        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //формат возвращаемых данных json методом yii2
        Yii::$app->response->data = $result_main;
    }

    // 127.0.0.1/cache-getter/remove-inactive-workers?mine_id=270
    public static function actionRemoveInactiveWorkers($mine_id)
    {
        $warnings = array();
        $worker_parameters_from_cache = array();
        $worker_cache_controller = new  WorkerCacheController();

        $workers = $worker_cache_controller->getWorkerMineHash($mine_id);
        if ($workers) {
            foreach ($workers as $worker_cache) {
                $worker_parameters_from_cache = $worker_cache_controller->getParameterValueHash($worker_cache['worker_id'], 83, 2);
                $difference_in_time = strtotime(BackendAssistant::GetDateNow()) - strtotime($worker_parameters_from_cache['date_time']);
                if ($difference_in_time >= 3600) {
                    $worker_cache_controller->delWorkerMineHash($worker_cache['worker_id'], $mine_id);
                    $warnings['deleted_from_cache'] = $worker_parameters_from_cache['worker_id'];

                    //TODO: добавить чтобы изминил параметры данного воркера в БД
                }
            }
        } else {
            $worker_parameters_from_cache = "Кеш работников пуст";
        }
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //формат возвращаемых данных json методом yii2
        Yii::$app->response->data = $worker_parameters_from_cache;
    }

    public static function actionForceCheckoutWorker($mine_id)
    {
        $status = 1;
        $errors = [];
        $result = [];
        $warnings = [];
        try {
            $result = (new WorkerCacheController())->delWorkerCheckMineHash($mine_id);

        } catch (Throwable $exception) {
            $errors[] = "actionSensorMethodTest. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "actionForceCheckoutWorker. Закончил выполнять метод";
        $result_main = array('status' => $status, 'result' => $result, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    public static function actionForceCheckoutSensor($mine_id)
    {
        $sensor_cache_controller = new SensorCacheController();
        $sensors = $sensor_cache_controller->getSensorMineHash($mine_id);

        if ($sensors) {
//            Assistant::PrintR($sensors);
            foreach ($sensors as $sensor) {

                if ($sensor['object_id'] == 104 || $sensor['object_id'] == 47) {
                    $response['sensor'] = $sensor;
                    $response['del_result'] = $sensor_cache_controller->delInSensorMineByOneHash($sensor['sensor_id'], $mine_id);
                    Assistant::PrintR($response);
                }

            }
            return 'Метод завершился';
        }
        return 'Сенсоры не найдены по указанному mine_id = ' . $mine_id;
    }

    /**
     * actionSensorMethodTestCoordinate - метод проверки БД/кеша/метода получения данных по сенсору на предмет его установки на схеме шахты
     * логика проверки:
     * проверяем сенсор стационарный или
     * @param $sensor_id
     * @example http://127.0.0.1/cache-getter/sensor-method-test-coordinate?sensor_id=8253
     */
    public static function actionSensorMethodTestCoordinate($sensor_id)
    {
        $status = 1;
        $errors = [];
        $warnings = [];
        try {
            $sensor_id = (int)$sensor_id;
            $warnings[] = "actionSensorMethodTest. Начал выполнять метод";
            $sensorObj = Sensor::find()
                ->select("
                sensor.*,
                object.object_type_id
                ")
                ->joinWith('object')
                ->where(['sensor.id' => $sensor_id])
                ->asArray()
                ->one();

            if (!$sensorObj) {
                throw new \Exception("Такого сенсора нет в БД");
            }

            $warnings[] = "Сенсор есть в БД: " . $sensor_id;
            $warnings[] = $sensorObj;
            $sensor_cache_controller = (new SensorCacheController);
            $parameter_type_id = $sensor_cache_controller->isStaticSensor($sensorObj['object_type_id']);
            $warnings[] = "Тип параметров сенсора: " . $parameter_type_id;
            if ($parameter_type_id === 1) {
                $warnings[] = "Сенсор стационарный";
            }
            $warnings[] = "---ПРОВЕРЯЕМ ШАХТУ СЕНСОРА---";
            /** блок проверки налияия привязки к шахте в БД */
            if ($parameter_type_id == 1) {
                $mineObjBase = (new SensorBasicController)->getLastSensorParameterHandbookValue($sensor_id, ' parameter_id = 346 AND parameter_type_id = ' . $parameter_type_id);
            } else {
                $mineObjBase = (new SensorBasicController)->getLastSensorParameterValue($sensor_id, ' parameter_id = 346 AND parameter_type_id = ' . $parameter_type_id);
            }

            if ($mineObjBase === false) {
                $errors[] = $mineObjBase;
                throw new \Exception("У сенсора не сконфигурирован параметр шахты в БД - нужно добавить в конкретных объектах");
            }

            if (!$mineObjBase[0]['value'] or $mineObjBase[0]['value'] == -1) {
                throw new \Exception("У сенсора не задано значение шахты в БД. Задайте в конкретных объектах или проверьте ССД Strata");
            }
            $warnings[] = "Значение шахтного поля в БД: " . $mineObjBase[0]['value'];
            /**
             * проверяем кеш параметра шахты сенсора
             */
            $mineObjCache = $sensor_cache_controller->getParameterValueHash($sensor_id, 346, $parameter_type_id);
            if ($mineObjCache === false) {
                throw new \Exception("У сенсора не сконфигурирован параметр шахты в кеше - нужно добавить в конкретных объектах");
            }
            if (!$mineObjCache['value'] or $mineObjCache['value'] == -1) {
                throw new \Exception("У сенсора не задано значение шахты в кеше. Задайте в конкретных объектах или проверьте ССД Strata");
            }
            $warnings[] = "Значение шахтного поля в кеше: " . $mineObjCache['value'];
            if ($mineObjCache['value'] !== $mineObjBase[0]['value']) {
                throw new \Exception("в КЕШЕ и в БД разные значения привязки сенсора к шахтному полю. Переинициализируйте кеш этого сенсора");
            }
            $warnings[] = "Значение шахтного поля в кеше и в БД совпадают и равно : " . $mineObjCache['value'];

            /**
             * блок проверки главного кеша сенсоров
             */
            $mineListCache = $sensor_cache_controller->getSensorMineBySensorHash($sensor_id);
            if (!$mineListCache) {
                throw new \Exception("Главный кеш SensorMine не инициализирован либо не содержит данный сенсор в своем списке. Переинициализируйте главный кеш");
            }
            $warnings[] = "Главный кеш SensorMine для данного сенсора существует и равен: " . $mineObjCache['value'];
            foreach ($mineListCache as $sensor_mine) {
                if ($mineObjBase[0]['value'] != $sensor_mine['mine_id']) {
                    throw new \Exception("Главный кеш SensorMine рассинхронизирован с БД. Переинициализируйте главный кеш");
                } else {
                    $warnings[] = "Перебор всех сенсоров по списку. Главный кеш SensorMine заполнен сенсором и равен: " . $sensor_mine['mine_id'];
                }
            }
            $warnings[] = "---ПРОВЕРЯЕМ КООРДИНАТУ СЕНСОРА---";
            /** блок проверки налияия координаты сенсора БД */
            if ($parameter_type_id == 1) {
                $sensor_coordinate_base = (new SensorBasicController)->getLastSensorParameterHandbookValue($sensor_id, ' parameter_id = 83 AND parameter_type_id = ' . $parameter_type_id);
            } else {
                $sensor_coordinate_base = (new SensorBasicController)->getLastSensorParameterValue($sensor_id, ' parameter_id = 83 AND parameter_type_id = ' . $parameter_type_id);
            }
            if (!$sensor_coordinate_base) {
                throw new \Exception("У сенсора не сконфигурирован параметр координаты в БД - нужно добавить в конкретных объектах");
            }

            if (!$sensor_coordinate_base[0]['value'] or $sensor_coordinate_base[0]['value'] == -1) {
                throw new \Exception("У сенсора не задано значение координаты в БД. Задайте в конкретных объектах или проверьте ССД Strata");
            }
            $warnings[] = "Значение координаты в БД: " . $sensor_coordinate_base[0]['value'];
            /**
             * проверяем кеш параметра координаты сенсора
             */
            $sensor_coordinate_cache = $sensor_cache_controller->getParameterValueHash($sensor_id, 83, $parameter_type_id);
            if ($sensor_coordinate_cache === false) {
                throw new \Exception("У сенсора не сконфигурирован параметр координаты в кеше - нужно добавить в конкретных объектах");
            }
            if (!$sensor_coordinate_cache['value'] or $sensor_coordinate_cache['value'] == -1) {
                throw new \Exception("У сенсора не задано значение координаты в кеше. Задайте в конкретных объектах или проверьте ССД Strata");
            }
            $warnings[] = "Значение шахтного поля в кеше: " . $sensor_coordinate_cache['value'];

            if ($sensor_coordinate_cache['value'] !== $sensor_coordinate_base[0]['value']) {
                throw new \Exception("в КЕШЕ и в БД разные значения координаты сенсора. Переинициализируйте кеш этого сенсора");
            }
            $warnings[] = "---ПРОВЕРЯЕМ МЕТОД ПОЛУЧЕНИЯ ДАННЫХ НА СХЕМЕ СЕНСОРА---";
            $warnings[] = "Значение координаты в кеше и в БД совпадают и равно : " . $sensor_coordinate_cache['value'];
            $response = self::cloneUnityPlayerSensorParameter($mineObjBase[0]['value'], $sensor_id);
            if ($response['status'] == 1) {
                $result = $response['Items'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $warnings[] = "actionGetSensorsParameters. метод успешно выполнился.";
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception("actionGetSensorsParameters. Не смог получить списко кеша для схемы шахты - ошибка метода");
            }

            if (!$result) {
                throw new \Exception("метод actionGetSensorsParameters успешно выполнился, но данных не вернул. Ошибка в методу");
            }
            $flag_perebor = 0;
            foreach ($result as $sensor_parameter) {
                if ($sensor_parameter['parameter_id'] == 83 and $sensor_parameter['parameter_type_id'] == $parameter_type_id) {
                    $warnings[] = "actionGetSensorsParameters. Параметр координаты найден в ответе метода и равен: " . $sensor_parameter['value'];
                    $method_coordinate_value = $sensor_parameter['value'];
                    $flag_perebor = 1;
                }
            }
            if ($flag_perebor == 0) {
                throw new \Exception("actionGetSensorsParameters. нет в методе параметра координаты - ошибка метода");
            }

            /**
             * проверка на синхронность данных в методе и в кеше/БД
             */
            if ($method_coordinate_value != $sensor_coordinate_cache['value']) {
                throw new \Exception("actionGetSensorsParameters. координата в кеше/БД и в методе разные - ошибка метода");
            }
            $warnings[] = "actionGetSensorsParameters. Параметр координаты синхронизирован с БД И КЕШ ";
            $warnings[] = "---ТЕСТ УСПЕШНО ПРОЙДЕН---";
            $warnings[] = "---Если проблема наблюдается, требуется проверка схемы шахты---";

//            /** блок получения значений по сенсору из БД */
//            SensorBasicController::getSensorParameter($sensor_id, $parameter_id, $parameter_type_id);
        } catch (Throwable $exception) {
            $errors[] = "actionSensorMethodTest. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "actionSensorMethodTest. Закончил выполнять метод";
        $result_main = array('status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    private static function cloneUnityPlayerSensorParameter($mine_id, $sensor_id)
    {
        $status = 1;
        $errors = [];
        $warnings = [];
        try {
            $warnings[] = 'actionGetSensorsParameters. получаю данные из кеша';

//            ini_set('max_execution_time', 300);
//            ini_set('memory_limit', '1024M');

            $warnings[] = 'actionGetSensorsParameters. Начал выполнять метод';

            $microtime_start = microtime(true);

            $warnings[] = 'actionGetSensorsParameters. Начал получать кеш работников ' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $sensor_mines = (new SensorCacheController())->getSensorMineHash($mine_id, $sensor_id);

            $warnings[] = 'actionGetSensorsParameters. получил кеш работников ' . $duration_method = round(microtime(true) - $microtime_start, 6);
            if ($sensor_mines) {
                $warnings[] = 'actionGetSensorsParameters. кеш работников шахты есть';
            } else {
                throw new \Exception('actionGetSensorsParameters. кеш сенсоров шахты пуст');                                                                                  //ключ от фронт энда не получен, потому формируем ошибку
            }
            $sensor_parameters = array(
                83,
                98,
                99,
                164,
                386,
                387,
                447,
                448
            );
            /**
             * получаю все параметры всех воркеров из кеша и пепелопачиваю их метод надо переделать на запрос параметров, только нужных воркеров
             */
            $full_parameters = (new SensorCacheController())->multiGetParameterValueHash('*', '*');
            if ($full_parameters) {

                $warnings[] = 'actionGetSensorsParameters. Полный кеш параметров сенсоров получен';
                foreach ($full_parameters as $full_parameter) {
                    $sensorList[$full_parameter['sensor_id']][$full_parameter['parameter_id']][$full_parameter['parameter_type_id']] = $full_parameter;
                }
            } else {
                throw new \Exception('actionGetSensorsParameters. кеш параметров работников шахты пуст');
            }

            /**
             * фильтруем только тех кто нужен
             */
            foreach ($sensor_mines as $sensor_mine) {
                for ($i = 1; $i <= 3; $i++) {
                    foreach ($sensor_parameters as $sensor_parameter) {
                        if (isset($sensorList[$sensor_mine['sensor_id']][$sensor_parameter][$i]['value'])) {
                            /**
                             * блок фильтрации параметров стационарных датчиков газа
                             */
                            $sensor_result['sensor_id'] = $sensorList[$sensor_mine['sensor_id']][$sensor_parameter][$i]['sensor_id'];
                            $sensor_result['object_id'] = $sensor_mine['object_id'];
                            $sensor_result['object_type_id'] = $sensor_mine['object_type_id'];
                            $sensor_result['sensor_parameter_id'] = $sensorList[$sensor_mine['sensor_id']][$sensor_parameter][$i]['sensor_parameter_id'];
                            $sensor_result['parameter_id'] = $sensorList[$sensor_mine['sensor_id']][$sensor_parameter][$i]['parameter_id'];
                            $sensor_result['parameter_type_id'] = $sensorList[$sensor_mine['sensor_id']][$sensor_parameter][$i]['parameter_type_id'];
                            $sensor_result['date_time'] = $sensorList[$sensor_mine['sensor_id']][$sensor_parameter][$i]['date_time'];
                            $sensor_result['value'] = $sensorList[$sensor_mine['sensor_id']][$sensor_parameter][$i]['value'];
                            $sensors[] = $sensor_result;
                        }
                    }
                }
            }

            unset($sensor_mines, $full_parameters);
        } catch (Throwable $exception) {
            $errors[] = "actionSensorMethodTest. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "actionSensorMethodTest. Закончил выполнять метод";
        $result_main = array("Items" => $sensors, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод получения журнала логов из кеша системы через браузер
     * Пример: 127.0.0.1/cache-getter/logs?cache_key=Log*
     */
    public function actionLogs()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionLogs");
        try {
            $log->addLog("Начало выполнения метода");

            $post = Assistant::GetServerMethod();

            $cache_key = (string)$post['cache_key'];
            $response = (new LogCacheController())->getLogJournalFromCache($cache_key);
            $log->addLogAll($response);
            $result = $response['Items'];

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод получения ключей журнала логов из кеша системы через браузер
     * Пример: 127.0.0.1/cache-getter/key-logs?cache_key=Log*
     */
    public function actionKeyLogs()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionLogs");
        try {
            $log->addLog("Начало выполнения метода");

            $post = Assistant::GetServerMethod();

            $cache_key = (string)$post['cache_key'];
            $response = (new LogCacheController())->getKeyLogJournalFromCache($cache_key);
            $log->addLogAll($response);
            $result = $response['Items'];

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionFlushCacheLogs - метод для удаления значения с кэш логов по ключу
     * Важно: если не указать нужного ключа то удаляются все значения
     * прмиер вызова: http://127.0.0.1:98/cache-getter/flush-cache-logs?cache_key=Log*
     */
    public function actionFlushCacheLogs()
    {
        $errors = array();
        $warnings = array();
        $status = 1;
        $redis_log = Yii::$app->redis_log;
        try {
            $warnings[] = 'actionFlushCacheLogs. Начало';

            $post = Assistant::GetServerMethod();
            if ((string)$post['cache_key'] == '*') {
                $redis_log->flushall();
            } else {
                $cache_key = (string)$post['cache_key'];

                $keys = $redis_log->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1];
                if (!isset($keys) or $keys == false) {
                    throw new \Exception('actionFlushCacheLogs. Нечего удалять, кэш пустой');
                }
                $warnings[] = 'Ключи на удаления:';
                $warnings[] = $keys;
                $result = (new LogCacheController())->amicum_mDel($keys);
                if (!$result) {
                    throw new \Exception('actionFlushCacheLogs. Не смог удалить значение из кэш логов');
                }
                $warnings[] = 'actionFlushCacheLogs. Вышеперечиленные ключи успешно удалены из кэша логов';
            }
            $warnings[] = 'actionFlushCacheLogs. Конец';

        } catch (\Exception $exception) {
            $status = 0;
            $errors[] = 'actionFlushCacheLogs. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        $result_main = array('status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * actionGetLogKyes - метод для полкчения пключей с кэш логов
     *
     * прмиер вызова: http://127.0.0.1:98/cache-getter/get-log-keys?cache_key=Log*
     */
    public function actionGetLogKyes()
    {
        $errors = array();
        $warnings = array();
        $status = 1;
        $redis_log = Yii::$app->redis_log;
        try {
            $warnings[] = 'actionFlushCacheLogs. Начало';
            $post = Assistant::GetServerMethod();
            $cache_key = (string)$post['cache_key'];
            $keys = $redis_log->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1];
            if (!isset($keys) or $keys == false) {
                throw new \Exception('actionFlushCacheLogs. Нечего полчать, кэш пусто');
            }
            $warnings[] = 'Кслючи:';
            $warnings[] = $keys;


            $warnings[] = 'actionFlushCacheLogs. Конец';

        } catch (\Exception $exception) {
            $status = 0;
            $errors[] = 'actionFlushCacheLogs. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        $result_main = array('status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }


    /** MultiGetWorkerParameterValue - метод получения всех параметров воркерав из кэша
     * @param null $data_post
     * @return array
     * пример вызова: /read-manager-amicum?controller=CacheGetter&method=MultiGetWorkerParameterValue&subscribe=&data={"worker_id":"2020460","parameter_id":"*","parameter_type_id":"*"}
     */
    public static function MultiGetWorkerParameterValue($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $result = array();                                                                                              // Промежуточный результирующий массив
        $errors = array();                                                                                              // Массив ошибок
        try {
            $warnings[] = 'MultiGetWorkerParameterValue. Начало';
            if ($data_post == null and $data_post == '') {
                throw new \Exception('MultiGetWorkerParameterValue. Не переданы входные параметры');
            }
            $warnings[] = 'MultiGetWorkerParameterValue. Данные успешно переданы';
            $warnings[] = 'MultiGetWorkerParameterValue. Входной массив данных' . $data_post;
            $post_decode = json_decode($data_post);
            $warnings[] = 'AcceptOrder. Декодировал входные параметры';
            $worker_id = $post_decode->worker_id;
            $parameter_id = $post_decode->parameter_id;
            $parameter_type_id = $post_decode->parameter_type_id;
            if ($worker_id == null) {
                $worker_id = '*';
            }
            if ($parameter_id == null) {
                $parameter_id = '*';
            }
            if ($parameter_type_id == null) {
                $parameter_type_id = '*';
            }
            $result = (new WorkerCacheController())->multiGetParameterValueHash($worker_id, $parameter_id, $parameter_type_id);
            if (!$result) {
                throw new \Exception('MultiGetWorkerParameterValue. Данные из кэша не получены');
            }
            $warnings[] = 'MultiGetWorkerParameterValue. Конец';
        } catch (\Exception $exception) {
            $status = 0;
            $errors[] = 'actionFlushCacheLogs. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('status' => $status, 'Items' => $result, 'errors' => $errors, 'warnings' => $warnings);
    }

    // actionFlushCacheSituation - сброс кеша ситуаций
    // пример: http://127.0.0.1/cache-getter/flush-cache-situation
    public static function actionFlushCacheSituation()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = array();                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = "actionFlushCacheSituation. Начало выполнения метода";
        try {
            $startTime = microtime(true);

            /**
             * кеш сенсоров
             */
            $warnings[] = "actionFlushCacheSituation. Начинаю сбрасывать кеш";

            $cache_situation = Yii::$app->cache_situation;
            $cache_situation->flush();
            $warnings[] = "actionFlushCacheSituation. Сбросил кеш cache_situation";

            $warnings[] = 'actionFlushCacheSituation. Заполнил кеш сенсоров ' . (microtime(true) - $startTime);


        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionFlushCacheSituation. Исключение';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "actionFlushCacheSituation. Закончил выполнение метода";

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        unset($result);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result_main;
    }

    // actionFlushCacheEvent - сброс кеша событий
    // пример: http://127.0.0.1/cache-getter/flush-cache-event
    public static function actionFlushCacheEvent()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = array();                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = "actionFlushCacheEvent. Начало выполнения метода";
        try {
            $startTime = microtime(true);

            /**
             * кеш сенсоров
             */
            $warnings[] = "actionFlushCacheEvent. Начинаю сбрасывать кеш";

            $cache_situation = Yii::$app->cache_event;
            $cache_situation->flush();
            $warnings[] = "actionFlushCacheEvent. Сбросил кеш cache_event";
            $warnings[] = 'actionFlushCacheEvent. Сбросил кеш событий ' . (microtime(true) - $startTime);

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionFlushCacheEvent. Исключение';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "actionFlushCacheEvent. Закончил выполнение метода";

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        unset($result);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result_main;
    }

    // actionFlushCacheEvent - сброс кеша событий
    // пример: http://127.0.0.1/cache-getter/flush-cache-rabbit
    public static function actionFlushCacheRabbit()
    {
        $errors = array();
        $status = array();
        $result = null;
        $warnings = array();

        try {
            $warnings[] = "actionFlushCacheRabbit. Начало выполнения метода";
            $startTime = microtime(true);

            $warnings[] = "actionFlushCacheRabbit. Начинаю сбрасывать кеш";

            $cache_situation = Yii::$app->cache_rabbit;
            $cache_situation->flush();
            $warnings[] = "actionFlushCacheRabbit. Сбросил кеш cache_rabbit";
            $warnings[] = 'actionFlushCacheRabbit. Сбросил кеш rabbit ' . (microtime(true) - $startTime);

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionFlushCacheRabbit. Исключение';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "actionFlushCacheRabbit. Закончил выполнение метода";

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // actionViewMessages - Метод проверки наличие сообщения на отправку на луx
    public function actionViewMessages()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = array();                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = "actionViewMessages. Начало выполнения метода";
        try {
            $result = (new ServiceCache())->getMessagesFromCache();

        } catch (\Exception $exception) {
            $status = 0;
            $errors[] = 'actionViewMessages. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionMultiGetCacheSensorParameter - тест производительности редиса от ключей
    //.../cache-getter/multi-get-cache-sensor-parameter?sensor_id=*&parameter_id=*
    public function actionMultiGetCacheSensorParameter()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();
        $warnings[] = 'actionMultiGetCacheSensorParameter. Выполнение метода начал';
        try {
            $microtime_start = microtime(true);
            $post = Assistant::GetServerMethod();
            $sensor_id = (string)$post['sensor_id'];
            $parameter_id = (string)$post['parameter_id'];

            $sensor_cache_controller = new SensorCacheController();
            $cache_key = $sensor_cache_controller->buildParameterKey($sensor_id, $parameter_id, '*');
            $warnings[] = $duration_method = round(microtime(true) - $microtime_start, 6) . 'actionMultiGetCacheSensorParameter. Получил шаблон ключа';

            $redis_parameter_handbook = Yii::$app->redis_sensor_parameter_handbook;
            $keys = $redis_parameter_handbook->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1];
            $warnings[] = $duration_method = round(microtime(true) - $microtime_start, 6) . 'actionMultiGetCacheSensorParameter. Сосканировал кеш';
            $warnings[] = 'actionMultiGetCacheSensorParameter. количество ключей ' . count($keys);

            $mgets = $sensor_cache_controller->sensor_parameter_handbook_cache->executeCommand('mget', $keys);
            $warnings[] = $duration_method = round(microtime(true) - $microtime_start, 6) . 'actionMultiGetCacheSensorParameter. Получил значения ключей кеша';
            if ($mgets) {
                foreach ($mgets as $mget) {
                    $result1[] = unserialize($mget)[0];
                }
            }

            $warnings[] = $duration_method = round(microtime(true) - $microtime_start, 6) . 'actionMultiGetCacheSensorParameter. Обработал данные с кеша';
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionMultiGetCacheSensorParameter. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = $duration_method = round(microtime(true) - $microtime_start, 6) . 'actionMultiGetCacheSensorParameter. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionForceCheckOutDbAndCache - метод принудительной выписки работника из шахты как с базы, так и с кеша
    // входные параметры:
    //  $mine_id    - шахта с которой выписываем работника, модет быть не задана
    // выходные параметры:
    //  типовой набор
    // пример использования: 127.0.0.1/cache-getter/force-check-out-db-and-cache?mine_id=290
    public function actionForceCheckOutDbAndCache()
    {

        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();                                                                                              //массив предупреждений

        try {
            $post = Assistant::GetServerMethod();
            if (isset($post['mine_id']) and $post['mine_id'] != ''
            ) {
                $mine_id = $post['mine_id'];
                $warnings[] = 'actionForceCheckOutDbAndCache. Входные параметры переданы ' . $mine_id;
            } else {
                throw new \Exception('actionForceCheckOutDbAndCache. Входные параметры не переданы' . $post['mine_id']);
            }
            $warnings[] = 'actionForceCheckOutDbAndCache. Начало выполнения метода';
            /**
             * инициализируем кеш работников
             */
            $worker_cache_controller = new WorkerCacheController();
            $datetime = BackendAssistant::GetDateNow();
            $shift_info = StrataJobController::getShiftDateNum($datetime); //получаем смену
            /**
             * Проверяем на наличие работника в шахте
             */
            $workers = $worker_cache_controller->getWorkerMineHash($mine_id);
            if ($workers) {
                foreach ($workers as $worker) {
                    $worker_id = $worker['worker_id'];
                    $warnings[] = 'actionForceCheckOutDbAndCache. Расчекиниваю работника: ' . $worker_id;
                    if ($worker) {
                        $response = WorkerMainController::moveWorkerMineInitCache($worker_id, -1);
                        if ($response['status'] == 1) {
                            $warnings[] = $response['warnings'];
                            //$errors[] = $response['errors'];
                            $warnings[] = 'actionForceCheckOutDbAndCache. Переместил работника в пустую шахту';
                        } else {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                            //throw new \Exception('actionForceCheckOutDbAndCache. Не смог перенести работника в пустую шахту');
                        }
                    } else {
                        throw new \Exception("actionForceCheckOutDbAndCache. в кеше шахты $mine_id нет такого работника $worker_id");
                    }
                    /**
                     * сохраняем в кеш параметр шахты работника на -1
                     */
                    $worker_parameter = $worker_cache_controller->getParameterValueHash($worker_id, 346, 2);
                    if ($worker_parameter) {
                        $worker_parameter['value'] = -1;
                        $response = $worker_cache_controller->setParameterValueHash($worker_id, $worker_parameter);
                        if ($response) {
                            $warnings[] = 'actionForceCheckOutDbAndCache. Сменил значение в КЕШЕ параметра  шахты у работника -1';
                        } else {
                            $errors[] = $worker_id;
                            $errors[] = $worker_parameter;
                            throw new \Exception('actionForceCheckOutDbAndCache. Не удалось установить в КЕШЕ новое значение парамтера шахты -1');
                        }
                    } else {
                        throw new \Exception("actionForceCheckOutDbAndCache. У работника в КЕШЕ нет последней шахты $worker_id");
                    }
                    /**
                     * пишем в БД новый параметр шахты
                     */
                    $response = WorkerBasicController::addWorkerParameterValue($worker_parameter['worker_parameter_id'], -1, $shift_info['shift_num'], 1, $datetime, $datetime);
                    if ($response) {
                        $warnings[] = 'actionForceCheckOutDbAndCache. Сменил значение параметра шахты у работника в БД на -1';
                    } else {
                        throw new \Exception('actionForceCheckOutDbAndCache. Не удалось установить новое значение параметра шахта в БД у работника на -1');
                    }
                    /**
                     * сохраняем в кеш параметр статуса спуска у рабоника на 0
                     */
                    $worker_parameter = $worker_cache_controller->getParameterValueHash($worker_id, 158, 2);
                    if ($worker_parameter) {
                        $worker_parameter['value'] = 0;

                        $worker_parameter['date_time'] = date("Y-m-d H:i:s", strtotime($worker_parameter['date_time'] . '-3600 seconds'));
                        $response = $worker_cache_controller->setParameterValueHash($worker_id, $worker_parameter);
                        if ($response) {
                            $warnings[] = 'actionForceCheckOutDbAndCache. Сменил значение в КЕШЕ параметра статуса спуска у работника на 0';
                        } else {
                            throw new \Exception('actionForceCheckOutDbAndCache. Не удалось установить в КЕШЕ новое значение парамтера статуса спуска у работника на 0');
                        }
                    } else {
                        throw new \Exception("actionForceCheckOutDbAndCache. в кеше нет параметра регистрации такого работника $worker_id");
                    }
                    /**
                     * пишем в БД новый статус спуска у работника
                     */
                    $response = WorkerBasicController::addWorkerParameterValue($worker_parameter['worker_parameter_id'], 0, $shift_info['shift_num'], 1, $datetime, $datetime);
                    if ($response) {
                        $warnings[] = 'actionForceCheckOutDbAndCache. Сменил значение параметра статуса спуска у работника в БД на 0';
                    } else {
                        throw new \Exception('actionForceCheckOutDbAndCache. Не удалось установить новое значение парамтера статуса спуска в БД у работника на 0');
                    }
                }
            } else {
                $warnings[] = 'actionForceCheckOutDbAndCache. Кеш пустой работников';
            }
            $workers_del = $worker_cache_controller->delWorkerCheckMineHash($mine_id);
            $warnings[] = 'actionForceCheckOutDbAndCache. удалил кеш работников';
            $warnings[] = $workers_del;
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionForceCheckOutDbAndCache. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'actionForceCheckOutDbAndCache. Закончил выполнение метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;

    }

}
