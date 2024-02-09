<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\positioningsystem;

//ob_start();

use backend\controllers\cachemanagers\EdgeCacheController;
use backend\controllers\cachemanagers\EquipmentCacheController;
use backend\controllers\cachemanagers\EventCacheController;
use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\cachemanagers\ServiceCache;
use backend\controllers\cachemanagers\SituationCacheController;
use backend\controllers\cachemanagers\WorkerCacheController;
use backend\controllers\CoordinateController;
use backend\controllers\OpcController;
use backend\controllers\SensorMainController;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\handbooks\HandbookEmployeeController;
use frontend\controllers\handbooks\HandbookTypicalObjectController;
use frontend\controllers\system\LogAmicumFront;
use frontend\controllers\XmlController;
use frontend\models\AccessCheck;
use frontend\models\Asmtp;
use frontend\models\GroupAlarm;
use frontend\models\KindParameter;
use frontend\models\Main;
use frontend\models\Mine;
use frontend\models\ParameterType;
use frontend\models\Place;
use frontend\models\SensorType;
use frontend\models\TypicalObject;
use frontend\models\ViewObjectSpecific;
use frontend\models\WorkerObject;
use Throwable;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\Response;

// actionAutoInitMain           - метод по автоматической переинициализации кэша на всех шахтах одновременно
// actionInitMain               - метод инициализации всех кешей с нуля по заданной шахте

class SpecificObjectController extends HandbookTypicalObjectController
{
    public static function compareStringsWithNumbers($a, $b)
    {
        preg_match_all("/\d+/", $a['title'], $ma);
        preg_match_all("/\d+/", $b['title'], $mb);
        if (isset($ma) && isset($mb)) {
            if ($ma == $mb) {
                return 0;
            }
            return ($ma < $mb) ? -1 : 1;
        } else {
            return ($a['title'] < $b['title']) ? -1 : 1;
        }
    }

    /*
     * Функция построения массива конкретных объектов
     * Выходные параметры:
     * - $specificArray (array) - массив конкретных объектов
     * - $specificArray[$i]['id'] - id конкретного объекта
     * - $specificArray[$i]['title'] - название конкретного объекта
     */

    public static function actionAddEntryMain($tableAddress)
    {
        $model = new Main();//создаем новую запись в таблице main
        $model->table_address = $tableAddress;
        $model->db_address = "amicum2";
        if (!$model->save()) {
            return $model->errors;
        }
        return $model->id;
    }

    /** Функция используется для вывода списка конкретных объектов в древовидной структре
     * @param int $kindObjectId - id вида объектов
     * @param int $objectTypeId - id типа объектов
     * @param int $objectId - id типового объекта
     * @return array $specificArray - массив конкретных объектов
     * Edited by: Курбанов И. С. on 17.12.2018 11:53
     */
    // 127.0.0.1/specific-object/build-specific-object-array?kindObjectId=&objectTypeId=&objectId=
    /**
     * Инициализация кэша выработок и графов сенсоров.
     */
    public static function actionInitScheme()
    {
        $status = 1;
        $debug = array();
        $errors = array();
        $result = array();
        $warnings = array();

        $warnings[] = 'actionInitScheme. Начало выполнения метода';
        try {
            $post = Assistant::GetServerMethod();
            if (isset($post['mine_id']) && $post['mine_id'] != '') {
                $mine_id = $post['mine_id'];
            } else {
                throw new Exception('actionInitScheme. Не передан входной параметр - идентификатор шахты');
            }

            $cache_edge = Yii::$app->redis_edge;
            $cache_edge->flushall();

            /**
             * Кеш выработок
             */
            $startTime = microtime(true);
            $response = (new EdgeCacheController())->runInit($mine_id);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $warnings[] = 'actionInitScheme. Кэш эджей инициализирован';
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('actionInitScheme. Ошибка инициализации кэша эджей');
            }
            $debug[] = 'Заполнил кеш выработок за ' . (microtime(true) - $startTime);
            unset($response);

            /**
             * Кэш графа шахты
             */
            $startTime = microtime(true);
            $response = (new CoordinateController())->buildGraph($mine_id);
            if ($response['status'] == 1) {
                $warnings[] = 'actionInitScheme. Кэш графов сенсоров инициализирован';
            } else {
                $errors[] = $response['errors'];
                throw new Exception('actionInitScheme. Ошибка инициализации кэша графов сенсоров');
            }
            $debug[] = 'Заполнил кеш графа схемы шахты для сенсоров за ' . (microtime(true) - $startTime);
            unset($response);

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionInitScheme. Исключение';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'actionInitScheme. Закончил выполнение метода';

        $result_main = array('debug' => $debug, 'Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    public static function actionInitMain()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = array();

        $log = new LogAmicumFront("actionInitMain");

        try {
            $log->addLog("Начало выполнения метода");
            //состояние выполнения метода

//            ini_set('max_execution_time', 6000);
//            ini_set('memory_limit', "10000M");

            $post = Assistant::GetServerMethod();
            $mine_id = $post['mine_id'];

            if (isset($post['flushCache'])) {
                $flushCache = json_decode($post['flushCache']);                                                         // массив статусов очистки кеша
            }
            if (isset($post['fillCache'])) {
                $fillCache = json_decode($post['fillCache']);                                                           // массив статусов очистки кеша
            }

            $log->addLog("Начинаю сбрасывать кеш");

            /**
             * Ставим запреть на запись службам сбора данных во время инициализации кэша
             */

            (new ServiceCache())->ChangeDcsStatus("0", $mine_id);
            sleep(5);                                                                                           // задержка для того, что бы успели отработать запросы в работе

            // сброс кеша выработок
            if (isset($flushCache) and isset($flushCache->edge) and $flushCache->edge) {
                $cache_edge = Yii::$app->cache_edge;
                (new EdgeCacheController())->amicum_flushall();
                $cache_edge->flush();
                $status['cache_edge'] = "Сбросил кеш cache_edge";
            }

            // сброс кеша оборудования
            if (isset($flushCache) and isset($flushCache->equipment) and $flushCache->equipment) {
                $cache_equipment = Yii::$app->cache_equipment;
                (new EquipmentCacheController())->amicum_flushall();
                $cache_equipment->flush();
                $status['cache_equipment'] = "Сбросил кеш cache_equipment";
            }

            // сброс кеша работников
            if (isset($flushCache) and isset($flushCache->worker) and $flushCache->worker) {
                $cache_worker = Yii::$app->cache_worker;
                (new WorkerCacheController())->amicum_flushall();
                $cache_worker->flush();
                $status['cache_worker'] = "Сбросил кеш cache_worker";
            }

            // сброс кеша фреймворка yii2
            if (isset($flushCache) and isset($flushCache->yii2) and $flushCache->yii2) {
                $cache_yii2 = Yii::$app->cache_yii2;
                $cache_yii2->flush();
                $status['cache_yii2'] = "Сбросил кеш cache_yii2";
            }

            // сброс кеша событий
            if (isset($flushCache) and isset($flushCache->event) and $flushCache->event) {
                $cache_event = Yii::$app->cache_event;
                (new EventCacheController())->amicum_flushall();
                $cache_event->flush();
                $status['cache_event'] = "Сбросил кеш cache_event";
            }

            // сброс кеша ситуаций
            if (isset($flushCache) and isset($flushCache->situation) and $flushCache->situation) {
                $cache_situation = Yii::$app->cache_situation;
                (new SituationCacheController())->amicum_flushall();
                $cache_situation->flush();
                $status['cache_situation'] = "Сбросил кеш cache_situation";
            }

            // сброс кеша сервисного
            if (isset($flushCache) and isset($flushCache->service) and $flushCache->service) {
                $cache_service = Yii::$app->cache_service;
                (new ServiceCache())->amicum_flushall();
                $cache_service->flush();
                $status['cache_service'] = "Сбросил кеш cache_service";
            }

            // сброс кеша сенсоров
            $sensor_cache_controller = new SensorCacheController();
            if (isset($flushCache) and isset($flushCache->sensor) and $flushCache->sensor) {
//                $sensor_cache_controller->removeAll();
                $sensor_cache_controller->amicum_flushall();
                $status['сенсоры'] = "Сбросил кеш сенсоров";
            }

            // сброс кеша событий
            if (isset($flushCache) and isset($flushCache->rabbit) and $flushCache->rabbit) {
                $cache_rabbit = Yii::$app->cache_rabbit;
                $cache_rabbit->flush();
                $status['cache_rabbit'] = "Сбросил кеш cache_rabbit";
            }

            $log->addLog("Закончил сброс кеша");

            /**
             * Заполнение кеша сенсоров общими данными
             */
            if (isset($fillCache) and isset($fillCache->sensor) and $fillCache->sensor) {
                $response = $sensor_cache_controller->initSensorParameterValueHash();                                   // инициализируем кэш списка сенсоров со значениями value
                $log->addLogAll($response);
                $status['initSensorParameterValueHash'] = $response['status'];
                $log->addLog("Инициализировал initSensorParameterValueHash: " . $response['status']);

                $response = $sensor_cache_controller->initSensorParameterHandbookValueHash();                           // инициализируем кэш списка сенсоров со значениями handbook
                $log->addLogAll($response);
                $status['initSensorParameterHandbookValueHash'] = $response['status'];
                $log->addLog("Инициализировал initSensorParameterHandbookValueHash: " . $response['status']);

                $response = $sensor_cache_controller->initSensorParameterSensor();                                      // инициализируем кэш списка сенсоров со значениями handbook
                $log->addLogAll($response);
                $status['initSensorParameterSensor'] = $response['status'];
                $log->addLog("Инициализировал initSensorParameterSensor: " . $response['status']);

                $status['initSensorNetwork'] = (new ServiceCache())->initSensorNetwork();
                $log->addLog("Заполнил кеш сенсоров Общий");
            }

            /**
             * Кеш работников
             */
            $worker_cache_controller = (new WorkerCacheController());
            if (isset($fillCache) and isset($fillCache->worker) and $fillCache->worker) {
                if ($worker_cache_controller->initWorkerParameterValueHash()) {                                         // инициализируем кэш списка работников со значениями value
                    $status['initWorkerParameterValueHash'] = 1;
                } else {
                    $status['initWorkerParameterValueHash'] = 0;
                }

                if ($worker_cache_controller->initWorkerParameterHandbookValueHash()) {                                 // инициализируем кэш списка работников со значениями handbook
                    $status['initWorkerParameterHandbookValueHash'] = 1;
                } else {
                    $status['initWorkerParameterHandbookValueHash'] = 0;
                }

                if ($worker_cache_controller->initSensorWorker()) {
                    $status['initSensorWorker'] = 1;
                } else {
                    $status['initSensorWorker'] = 0;
                }
                $log->addLog("Заполнил кеш воркеров Общий");
            }

            /**
             * Кеш оборудования
             */
            if (isset($fillCache) and isset($fillCache->equipment) and $fillCache->equipment) {
                $equipment_cache_controller = (new EquipmentCacheController());
                $response = $equipment_cache_controller->initEquipmentParameterValue();                                 // инициализируем кэш списка оборудований со значениями value
                $status['initEquipmentParameterValue'] = $response ? 1 : 0;
                $response = $equipment_cache_controller->initEquipmentParameterHandbookValue();                         // инициализируем кэш списка оборудований со значениями handbook
                $status['initEquipmentParameterHandbookValue'] = $response ? 1 : 0;
                $response = $equipment_cache_controller->initEquipmentSensor();
                $status['initEquipmentSensor'] = $response ? 1 : 0;
                $response = $equipment_cache_controller->initEquipmentParameterSensor();
                $status['initEquipmentParameterSensor'] = $response ? 1 : 0;
                $log->addLog("Заполнил кеш оборудования Общий");
            }

            /**
             * Кеш выработок
             */
            if (isset($fillCache) and isset($fillCache->edge) and $fillCache->edge) {
                $edge_cache_controller = (new EdgeCacheController());
                $status['initEdgeParameterValue'] = $edge_cache_controller->initEdgeParameterValue() ? 1 : 0;           // инициализируем кэш списка выработок со значениями value
                $status['initEdgeParameterHandbookValue'] = $edge_cache_controller->initEdgeParameterHandbookValue() ? 1 : 0;// инициализируем кэш списка выработок со значениями handbook
                $log->addLog("Заполнил кеш выработок Общий");

            }


            if (!isset($status)) {
                $status[$mine_id] = array();
            }
            /**
             * Заполнил кеш сенсоров
             */
            if (isset($fillCache) and isset($fillCache->sensor) and $fillCache->sensor) {
                $response = $sensor_cache_controller->initSensorMainHash($mine_id);                                     // инициализируем кэш списка сенсоров по шахте
                $log->addLogAll($response);
                $status[$mine_id]['initSensorMainHash'] = $response['status'];
                $log->addLog("Инициализировал initSensorMainHash: " . $response['status']);

                unset($response);
                $log->addLog("Заполнил кеш сенсоров шахты $mine_id");
            }
            /**
             * Кеш работников
             */
            if (isset($fillCache) and isset($fillCache->worker) and $fillCache->worker) {
                $status[$mine_id]['initWorkerMineHash'] = $worker_cache_controller->initWorkerMineHash($mine_id);
                $log->addLog("Заполнил кеш воркеров шахты $mine_id");
                unset($response);
            }

            /**
             * Кеш оборудования
             */
            if (isset($fillCache) and isset($fillCache->equipment) and $fillCache->equipment) {
                $response = $equipment_cache_controller->initEquipmentMain($mine_id);                                                                            // инициализируем кэш списка оборудований по шахте
                $status[$mine_id]['initEquipmentMain'] = $response['status'];
                $log->addLog("Заполнил кеш оборудования шахты $mine_id");
                unset($response);
            }


            /**
             * Кеш выработок
             */
            if (isset($fillCache) and isset($fillCache->edge) and $fillCache->edge) {
                $status[$mine_id]['initEdgeMine'] = $edge_cache_controller->initEdgeMine($mine_id) ? 1 : 0;                                                                              // инициализируем кэш списка выработок по шахте
                $status[$mine_id]['initEdgeScheme'] = $edge_cache_controller->initEdgeScheme($mine_id) ? 1 : 0;
                $log->addLog("Заполнил кеш выработок шахты $mine_id");
                unset($response);


                /**
                 * Кеш графа шахты
                 */
                $response = (new CoordinateController())->buildGraph($mine_id);
                $log->addLogAll($response);
                $status[$mine_id]['CoordinateController'] = $response['status'];
                $log->addLog("Заполнил кеш графа схемы шахты для сенсоров шахты $mine_id");
                unset($response);
            }

            /**
             * Кеш сенсоров OPC
             */
            if (isset($fillCache) and isset($fillCache->opc) and $fillCache->opc) {
                $response = (new OpcController('1', '1'))->actionBuildGraph($mine_id);
                $log->addLogAll($response);
                $status[$mine_id]['OpcController'] = $response['status'];
                $log->addLog("Заполнил кеш OPC привязок тега шахты $mine_id");
                unset($response);
            }

            /**
             * Кеш ситуаций
             */
            if (isset($fillCache) and isset($fillCache->situation) and $fillCache->situation) {
                $response = (new SituationCacheController())->runInit($mine_id);
                $log->addLogAll($response);
                $status[$mine_id]['SituatuionCacheController'] = $response['status'];
                $log->addLog("Заполнил кеш ситуаций шахты $mine_id");
                unset($response);
            }
            $log->addLog("Инициализировал всё шахты $mine_id");


            /**
             * Снимаем запреть на запись службам сбора данных во время инициализации кэша
             */
            (new ServiceCache())->ChangeDcsStatus("1", $mine_id);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Закончил выполнение метода");

        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = array_merge(['Items' => $result, 'status_cash' => $status], $log->getLogAll());


    }

    /*Функция сортировки с учетом наличия числа в наименовании объекта*/

    /**
     * actionAutoInitMain - метод по автоматической переинициализации кэша на всех шахтах одновременно
     *
     *  сперва полностью кеш отчищается - затем заполняется последовательно
     *  входные параметры массив:
     *       mine_id - ключ шахты
     *       flushCache:     - сбросить кеш
     *           sensor          - кеш сенсоров
     *           service         - сервисный кеш
     *           situation       - кеш ситуаций
     *           event           - кеш событий
     *           yii2            - кеш проекта
     *           worker          - кеш работников
     *           equipment       - кеш оборудования
     *       fillCache:      - заполнить кеш
     *           situation       - кеш ситуаций
     *           opc             - кеш OPC
     *           edge            - кеш выработок
     *           equipment       - кеш оборудования
     *           worker          - кеш работников
     *           sensor          - кеш сенсоров
     *   выходные параметры:
     *       стандартный набор
     *  пример использования: http://127.0.0.1/specific-object/auto-init-main?flushCache={%22edge%22:true,%22equipment%22:true,%22worker%22:true,%22yii2%22:true,%22event%22:true,%22situation%22:true,%22service%22:true,%22sensor%22:true}&fillCache={%22edge%22:true,%22equipment%22:true,%22worker%22:true,%22yii2%22:true,%22situation%22:true,%22sensor%22:true,%22opc%22:true}
     *  дата создания 07.07.2020
     */
    public static function actionAutoInitMain()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = array();
        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionAutoInitMain");

        try {
            $log->addLog("Начало выполнения метода");
            //состояние выполнения метода

//            ini_set('max_execution_time', 6000);
//            ini_set('memory_limit', "10000M");

            $post = Assistant::GetServerMethod();

            if (isset($post['flushCache'])) {
                $flushCache = json_decode($post['flushCache']);                                                         // массив статусов очистки кеша
            }
            if (isset($post['fillCache'])) {
                $fillCache = json_decode($post['fillCache']);                                                           // массив статусов очистки кеша
            }

            $log->addLog("Начинаю сбрасывать кеш");
            /**
             * По циклу заполняем кэш на всех шахт
             */
            // получем список шахт
            $mines = Mine::find()->select(['id'])->asArray()->all();
            $status['список шахт для которых инициализируем кэш'] = $mines;

            /**
             * Ставим запреть на запись службам сбора данных во время инициализации кэша
             */
            foreach ($mines as $mine) {
                (new ServiceCache())->ChangeDcsStatus("0", $mine['id']);
            }

            sleep(5);                                                                                           // задержка для того, что бы успели отработать запросы в работе

            // сброс кеша выработок
            if (isset($flushCache) and isset($flushCache->edge) and $flushCache->edge) {
                $cache_edge = Yii::$app->cache_edge;
                (new EdgeCacheController())->amicum_flushall();
                $cache_edge->flush();
                $status['cache_edge'] = "Сбросил кеш cache_edge";
            }

            // сброс кеша оборудования
            if (isset($flushCache) and isset($flushCache->equipment) and $flushCache->equipment) {
                $cache_equipment = Yii::$app->cache_equipment;
                (new EquipmentCacheController())->amicum_flushall();
                $cache_equipment->flush();
                $status['cache_equipment'] = "Сбросил кеш cache_equipment";
            }

            // сброс кеша работников
            if (isset($flushCache) and isset($flushCache->worker) and $flushCache->worker) {
                $cache_worker = Yii::$app->cache_worker;
                (new WorkerCacheController())->amicum_flushall();
                $cache_worker->flush();
                $status['cache_worker'] = "Сбросил кеш cache_worker";
            }

            // сброс кеша фреймворка yii2
            if (isset($flushCache) and isset($flushCache->yii2) and $flushCache->yii2) {
                $cache_yii2 = Yii::$app->cache_yii2;
                $cache_yii2->flush();
                $status['cache_yii2'] = "Сбросил кеш cache_yii2";
            }

            // сброс кеша событий
            if (isset($flushCache) and isset($flushCache->event) and $flushCache->event) {
                $cache_event = Yii::$app->cache_event;
                (new EventCacheController())->amicum_flushall();
                $cache_event->flush();
                $status['cache_event'] = "Сбросил кеш cache_event";
            }

            // сброс кеша ситуаций
            if (isset($flushCache) and isset($flushCache->situation) and $flushCache->situation) {
                $cache_situation = Yii::$app->cache_situation;
                (new SituationCacheController())->amicum_flushall();
                $cache_situation->flush();
                $status['cache_situation'] = "Сбросил кеш cache_situation";
            }

            // сброс кеша сервисного
            if (isset($flushCache) and isset($flushCache->service) and $flushCache->service) {
                $cache_service = Yii::$app->cache_service;
                (new ServiceCache())->amicum_flushall();
                $cache_service->flush();
                $status['cache_service'] = "Сбросил кеш cache_service";
            }

            // сброс кеша сенсоров
            $sensor_cache_controller = new SensorCacheController();
            if (isset($flushCache) and isset($flushCache->sensor) and $flushCache->sensor) {
//                $sensor_cache_controller->removeAll();
                $sensor_cache_controller->amicum_flushall();
                $status['сенсоры'] = "Сбросил кеш сенсоров";
            }

            // сброс кеша событий
            if (isset($flushCache) and isset($flushCache->rabbit) and $flushCache->rabbit) {
                $cache_rabbit = Yii::$app->cache_rabbit;
                $cache_rabbit->flush();
                $status['cache_rabbit'] = "Сбросил кеш cache_rabbit";
            }

            $log->addLog("Закончил сброс кеша");

            /**
             * Заполнение кеша сенсоров общими данными
             */
            if (isset($fillCache) and isset($fillCache->sensor) and $fillCache->sensor) {
                $response = $sensor_cache_controller->initSensorParameterValueHash();                                                                            // инициализируем кэш списка сенсоров со значениями value
                $log->addLogAll($response);
                $status['initSensorParameterValueHash'] = $response['status'];
                $log->addLog("Инициализировал initSensorParameterValueHash: " . $response['status']);

                $response = $sensor_cache_controller->initSensorParameterHandbookValueHash();                                                                    // инициализируем кэш списка сенсоров со значениями handbook
                $log->addLogAll($response);
                $status['initSensorParameterHandbookValueHash'] = $response['status'];
                $log->addLog("Инициализировал initSensorParameterHandbookValueHash: " . $response['status']);

                $response = $sensor_cache_controller->initSensorParameterSensor();                                                                    // инициализируем кэш списка сенсоров со значениями handbook
                $log->addLogAll($response);
                $status['initSensorParameterSensor'] = $response['status'];
                $log->addLog("Инициализировал initSensorParameterSensor: " . $response['status']);

                $status['initSensorNetwork'] = (new ServiceCache())->initSensorNetwork();
                $log->addLog("Заполнил кеш сенсоров Общий");
            }

            /**
             * Кеш работников
             */
            $worker_cache_controller = (new WorkerCacheController());
            if (isset($fillCache) and isset($fillCache->worker) and $fillCache->worker) {
                if ($worker_cache_controller->initWorkerParameterValueHash()) {                                                                     // инициализируем кэш списка работников со значениями value
                    $status['initWorkerParameterValueHash'] = 1;
                } else {
                    $status['initWorkerParameterValueHash'] = 0;
                }

                if ($worker_cache_controller->initWorkerParameterHandbookValueHash()) {                                                             // инициализируем кэш списка работников со значениями handbook
                    $status['initWorkerParameterHandbookValueHash'] = 1;
                } else {
                    $status['initWorkerParameterHandbookValueHash'] = 0;
                }

                if ($worker_cache_controller->initSensorWorker()) {
                    $status['initSensorWorker'] = 1;
                } else {
                    $status['initSensorWorker'] = 0;
                }
                $log->addLog("Заполнил кеш воркеров Общий");
            }

            /**
             * Кеш оборудования
             */
            if (isset($fillCache) and isset($fillCache->equipment) and $fillCache->equipment) {
                $equipment_cache_controller = (new EquipmentCacheController());
                $response = $equipment_cache_controller->initEquipmentParameterValue();                                                                        // инициализируем кэш списка оборудований со значениями value
                $status['initEquipmentParameterValue'] = $response ? 1 : 0;
                $response = $equipment_cache_controller->initEquipmentParameterHandbookValue();                                                                // инициализируем кэш списка оборудований со значениями handbook
                $status['initEquipmentParameterHandbookValue'] = $response ? 1 : 0;
                $response = $equipment_cache_controller->initEquipmentSensor();
                $status['initEquipmentSensor'] = $response ? 1 : 0;
                $response = $equipment_cache_controller->initEquipmentParameterSensor();
                $status['initEquipmentParameterSensor'] = $response ? 1 : 0;
                $log->addLog("Заполнил кеш оборудования Общий");
            }

            /**
             * Кеш выработок
             */
            if (isset($fillCache) and isset($fillCache->edge) and $fillCache->edge) {
                $edge_cache_controller = (new EdgeCacheController());
                $status['initEdgeParameterValue'] = $edge_cache_controller->initEdgeParameterValue() ? 1 : 0;                                                                            // инициализируем кэш списка выработок со значениями value
                $status['initEdgeParameterHandbookValue'] = $edge_cache_controller->initEdgeParameterHandbookValue() ? 1 : 0;                                                                    // инициализируем кэш списка выработок со значениями handbook
                $log->addLog("Заполнил кеш выработок Общий");

            }

            foreach ($mines as $mine) {
                $mine_id = $mine['id'];
                if (!isset($status)) {
                    $status[$mine_id] = array();
                }
                /**
                 * Заполнил кеш сенсоров
                 */
                if (isset($fillCache) and isset($fillCache->sensor) and $fillCache->sensor) {
                    $response = $sensor_cache_controller->initSensorMainHash($mine_id);                                                                            // инициализируем кэш списка сенсоров по шахте
                    $log->addLogAll($response);
                    $status[$mine_id]['initSensorMainHash'] = $response['status'];
                    $log->addLog("Инициализировал initSensorMainHash: " . $response['status']);

                    unset($response);
                    $log->addLog("Заполнил кеш сенсоров шахты $mine_id");
                }
                /**
                 * Кеш работников
                 */
                if (isset($fillCache) and isset($fillCache->worker) and $fillCache->worker) {
                    $status[$mine_id]['initWorkerMineHash'] = $worker_cache_controller->initWorkerMineHash($mine_id);
                    $log->addLog("Заполнил кеш воркеров шахты $mine_id");
                    unset($response);
                }

                /**
                 * Кеш оборудования
                 */
                if (isset($fillCache) and isset($fillCache->equipment) and $fillCache->equipment) {
                    $response = $equipment_cache_controller->initEquipmentMain($mine_id);                                                                            // инициализируем кэш списка оборудований по шахте
                    $status[$mine_id]['initEquipmentMain'] = $response['status'];
                    $log->addLog("Заполнил кеш оборудования шахты $mine_id");
                    unset($response);
                }


                /**
                 * Кеш выработок
                 */
                if (isset($fillCache) and isset($fillCache->edge) and $fillCache->edge) {
                    $status[$mine_id]['initEdgeMine'] = $edge_cache_controller->initEdgeMine($mine_id) ? 1 : 0;                                                                              // инициализируем кэш списка выработок по шахте
                    $status[$mine_id]['initEdgeScheme'] = $edge_cache_controller->initEdgeScheme($mine_id) ? 1 : 0;
                    $log->addLog("Заполнил кеш выработок шахты $mine_id");
                    unset($response);


                    /**
                     * Кэш графа шахты
                     */
                    $response = (new CoordinateController())->buildGraph($mine_id);
                    $log->addLogAll($response);
                    $status[$mine_id]['CoordinateController'] = $response['status'];
                    $log->addLog("Заполнил кеш графа схемы шахты для сенсоров шахты $mine_id");
                    unset($response);
                }

                /**
                 * Кеш сенсоров OPC
                 */
                if (isset($fillCache) and isset($fillCache->opc) and $fillCache->opc) {
                    $response = (new OpcController('1', '1'))->actionBuildGraph($mine_id);
                    $log->addLogAll($response);
                    $status[$mine_id]['OpcController'] = $response['status'];
                    $log->addLog("Заполнил кеш OPC привязок тега шахты $mine_id");
                    unset($response);
                }

                /**
                 * Кеш ситуаций
                 */
                if (isset($fillCache) and isset($fillCache->situation) and $fillCache->situation) {
                    $response = (new SituationCacheController())->runInit($mine_id);
                    $log->addLogAll($response);
                    $status[$mine_id]['SituatuionCacheController'] = $response['status'];
                    $log->addLog("Заполнил кеш ситуаций шахты $mine_id");
                    unset($response);
                }
                $log->addLog("Инициализировал всё шахты $mine_id");
            }

            /**
             * Снимаем запреть на запись службам сбора данных во время инициализации кэша
             */
            foreach ($mines as $mine) {
                (new ServiceCache())->ChangeDcsStatus("1", $mine['id']);
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Закончил выполнение метода");

        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = array_merge(['Items' => $result, 'status_cash' => $status], $log->getLogAll());
    }

    /*
     * Функция построения массива параметров конкретных объектов
     * Входные параметры:
     * - $specificObjectId (int) - id конкретного объекта, для которого запрашиваются параметры
     * Выходные параметры:
     * - $paramsArray (array) – массив групп параметров конкретного объекта (по сути вкладок);
     * - $paramsArray[i][“id”] (int) – id вида параметров;
     * - $paramsArray[i][“title”] (string) – наименование вида параметров;
     * - $paramsArray[i]['params'] (array) – массив параметров вида параметров;
     * - $paramsArray[i]['params'][j][“id”] (int) – id параметра;
     * - $paramsArray[i]['params'][j][“title”] (string) – наименование параметра;
     * - $paramsArray[i]['params'][j][“units”] (string) – единица измерения;
     * - $paramsArray[i]['params'][$j]["units_id"] (int) - id единицы измерения
     * - $paramsArray[$i]['params'][$j]['specific'][$k]['id'] (int) - тип(вычисленный/измеренный/справочный) параметра
     * - $paramsArray[$i]['params'][$j]['specific'][$k]['title'] (string) - наименование типа параметра
     * - $paramsArray[$i]['params'][$j]['specific'][$k]['specificObjectParameterId'] (int) - id привязки параметра к конкретному объекту
     * - $paramsArray[$i]['params'][$j]['specific'][$k]['value'] (int) - измеряемое/справочное значение параметра
     */
    // пример: http://127.0.0.1/specific-object/get-specific-object-parameters-array?id=115919&table_name=sensor
    // пример: http://127.0.0.1/specific-object/get-specific-object-parameters-array?id=2072&table_name=equipment
    // пример: http://127.0.0.1/specific-object/get-specific-object-parameters-array?id=1801&table_name=worker

    public function actionIndex()
    {
        $objectKinds = parent::buildTypicalObjectArray();

        $parameterTypes = ParameterType::find()
            ->select(['title', 'id'])
            ->orderBy(['id' => SORT_ASC])
            ->asArray()->all();
        //получить все виды параметров
        $parameterKinds = KindParameter::find()->orderBy('title')->asArray()->all();
        $units = (new Query())
            ->select('id, title, short')
            ->from('unit')
            ->all();

        // Выпадашка изменена на выбор только сенсоров OPC. Изменения сделал Якимов М.Н.
        $response = SensorMainController::getListOpcParameters();

        $sensorParameterOPCList = $response['Items'];
        $list_parameters_sensors = $response['list_parameters_sensors'];
        $sensorList = (new Query())
            ->select('id, title')
            ->from('sensor')
            ->orderBy(['title' => SORT_ASC])
            ->all();
        $sensorObj = array();
        foreach ($sensorList as $sensorList_item) {
            $sensorObj[$sensorList_item['id']] = $sensorList_item['title'];
        }
        $asmtp = (new Query())
            ->select('id, title')
            ->from('asmtp')
            ->orderBy(['title' => SORT_ASC])
            ->all();
        $sensorType = (new Query())
            ->select('id, title')
            ->from('sensor_type')
            ->orderBy(['title' => SORT_ASC])
            ->all();
        $plast = (new Query())
            ->select('id, title')
            ->from('plast')
            ->orderBy(['title' => SORT_ASC])
            ->all();
        $mine = (new Query())
            ->select('id, title')
            ->from('mine')
            ->orderBy(['title' => SORT_ASC])
            ->all();
        $company = (new Query())
            ->select('id, title')
            ->from('company')
            ->orderBy(['title' => SORT_ASC])
            ->all();
        $place = (new Query())
            ->select('id, title')
            ->from('place')
            ->orderBy(['title' => SORT_ASC])
            ->all();
        $place_obj = array();
        foreach ($place as $place_item) {
            $place_obj[$place_item['id']] = $place_item['title'];
        }
        $all_parameters = (new Query())
            ->select('id, title')
            ->from('parameter')
            ->orderBy('title ASC')
            ->all();

        $pps_equipment = (new Query())
            ->select(['main_id id', 'object_title title'])
            ->from('view_main_object_pps')
            ->orderBy(['title' => SORT_ASC])
            ->all();
        $energy_equipment = (new Query())
            ->select(['main_id id', 'object_title title'])
            ->from('view_main_object_energy')
            ->orderBy(['title' => SORT_ASC])
            ->all();

        $alarm_groups = GroupAlarm::find()
            ->orderBy(['title' => SORT_ASC])
            ->asArray()
            ->all();

        $this->view->registerJsVar('objectKinds', $objectKinds);
        $this->view->registerJsVar('objectOptions', $parameterKinds);
        $this->view->registerJsVar('parameterTypesArray', $parameterTypes);
        $this->view->registerJsVar('unitsArray', $units);
        $this->view->registerJsVar('sensorArray', $sensorList);
        $this->view->registerJsVar('sensorObject', $sensorObj);
        $this->view->registerJsVar('asmtpArray', $asmtp);
        $this->view->registerJsVar('ppsEquipmentArray', $pps_equipment);
        $this->view->registerJsVar('energyEquipmentArray', $energy_equipment);
        $this->view->registerJsVar('sensorTypeArray', $sensorType);
        $this->view->registerJsVar('mineArray', $mine);
        $this->view->registerJsVar('plastArray', $plast);
        $this->view->registerJsVar('placeObj', $place_obj);
        $this->view->registerJsVar('companyArray', $company);
        $this->view->registerJsVar('placeArray', $place);
        $this->view->registerJsVar('parametersArray', $all_parameters);
        $this->view->registerJsVar('OPCTags', $sensorParameterOPCList);
        $this->view->registerJsVar('OPCTagsSensor', $list_parameters_sensors);
        $this->view->registerJsVar('alarmGroupsArray', $alarm_groups);
        return $this->render('index');
    }

    /*
     * Функция построения параметров конкретного объекта
     * */

    public function actionGetSpecificObjectArray()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $specificArray = array();
        $errors = array();
        if (isset($post['kind_object_id']) && $post['kind_object_id'] != "" && isset($post['object_type_id']) &&
            $post['object_type_id'] != "" && isset($post['object_id']) && $post['object_id'] != "") {
            $kindObjectId = $post['kind_object_id'];
            $objectTypeId = $post['object_type_id'];
            $objectId = $post['object_id'];
            $specificArray = self::buildSpecificObjectArray($kindObjectId, $objectTypeId, $objectId);                  //вызов функции построения массива
            //вернуть построенный массив
        } else {
            $errors[] = "Не передано значение либо значение содержит пустую строку";
        }

//        ArrayHelper::multisort($specificArray, 'title', SORT_DESC, SORT_REGULAR);
        $result = array('errors' => $errors, 'specific_objects' => $specificArray);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /*
     * функция построения функций дял конкретного объекта
     * */


    public static function buildSpecificObjectArray(int $kindObjectId, int $objectTypeId, int $objectId)
    {
        $errors = array();
        $specificArray = array();
        $warnings = array();
        try {
            $warnings[] = "buildSpecificObjectArray. Начал выполнять метод";
            $warnings[] = "buildSpecificObjectArray. Входные параметры" . $kindObjectId . "--" . $objectTypeId . "===" . $objectId;
            $i = 0;
            $tableName = "";
            if (isset($kindObjectId) && isset($objectTypeId) && isset($objectId))//проверка, что все данные пришли
            {
                $table_name = ViewObjectSpecific::findOne(['object_id' => $objectId]);
                $warnings[] = "buildSpecificObjectArray. Поиск типовой таблицы";
                $warnings[] = $table_name;
                if (isset($table_name->table_address))  //если модель была найдена
                {
                    $tableName = self::camelCase($table_name->table_address);                                           //получаем название таблицы из вьюшки
                    $all_models = XmlController::actionGetNameFilesModels();                                            //получаем список моделей MVC
                    $modelName = "frontend\\models\\" . ucfirst($tableName);                                            //создаем динамическую переменную, в кт храним модель
                    if (in_array(ucfirst($tableName), $all_models)) {                                                   //если такая модель в $tableName существует, то составляем массив конкретных объектов
                        $itemArray = $modelName::find()->where(['object_id' => $objectId])->orderBy(["title" => SORT_ASC])->all();
                        if ($itemArray) {
                            foreach ($itemArray as $item)                                                               //в цикле перебираем каждый элемента массива и добавляем в БД
                            {
                                if ($item) {                                                                            // если данные не пустые
                                    $specificArray[$i]['id'] = $item->id;                                               //сохранить в массив id конкретного объекта
                                    $specificArray[$i]['title'] = $item->title;                                         //сохранить в массив название конкретного объекта
                                    $specificArray[$i]['table_name'] = $table_name->table_address;                      //сохранить в массив название таблицы
                                    if ($table_name->table_address === 'equipment')                                     // если название таблицы == equipment
                                    {
                                        $specificArray[$i]['parent_equipment_id'] = $item->parent_equipment_id;         // добавляем родителя для оборудования
                                    }
                                    if ($table_name->table_address === 'place')                                         // если название таблицы == place
                                    {
                                        $edges_list = (new Query())
                                            ->select('id')                                                      // выбираем id
                                            ->from('edge')                                                        // из таблицы edge
                                            ->where('place_id = ' . $item->id)                                  // где id места = выбранному id
                                            ->all(); // выбрать все

//                                $specificArray[$i]['edges'] = $edges_list;
                                        $edge_index = 0;                                                                // переменная для индексирования объекта массива
                                        foreach ($edges_list as $edge)                                                  // в цикле добавляем каждое значение полученного массива ветвь
                                        {
                                            $specificArray[$i]['edges'][$edge_index]['id'] = $edge['id'];           // добавим новый объект (2 уровень) в массив с названием edge и добавим id
                                            $specificArray[$i]['edges'][$edge_index]['table_name'] = 'edge';
                                            $edge_index++;
                                        }
                                    }
                                    $i++;
                                }
                            }
                        }
                    }

                }
            }
        } catch (Throwable $ex) {
            $errors[] = "buildSpecificObjectArray. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        return $specificArray;
//        $warnings[]="buildSpecificObjectArray. Закончил выполнять метод";
//        $result = array('specificArray' => $specificArray, 'warnings' => $warnings);
//        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
//        Yii::$app->response->data = $result;
    }

    /*
     * функция для создания записи в таблице main
     * */

    private static function camelCase($str)
    {
        $words = explode('_', $str);
        $newStr = '';
        foreach ($words as $key => $word) {
            $newStr .= $key == 0 ? $word : mb_convert_case($word, MB_CASE_TITLE, "UTF-8");
        }
        return $newStr;
    }

    public function actionGetSpecificObjectParametersArray()
    {
        $microtime_start = microtime(true);
        $post = Assistant::GetServerMethod();                                                                             //получение данных от ajax-запроса
        $paramsArray = array();                                                                                         //массив для сохранения параметров
        $functionsArray = array();                                                                                      //массив для сохранения функций
        $errors = array();
        if (isset($post['id']) and $post['id'] != "" and isset($post['table_name']) and $post['table_name'] != "") {
            $specificId = (int)$post['id'];

            //$mainObject = Main::findOne($specificId);                                                                       //сохраняем в какой базе хранится и в какой таблице конкретный объект
            // echo nl2br("-----specific_id = ".$post['id']."\n");
            //echo nl2br("-----table_address = ".$mainObject->table_address."\n");
//            if (isset($mainObject->table_address)) {
            $tableName = $post['table_name'];//сохраняем конкретную таблицу
            switch ($tableName) {
                case 'sensor':
                    $tableName = $post['table_name'];//сохраняем конкретную таблицу
                    $response = SpecificSensorController::buildsensorParameterArray($specificId);
                    if ($response['status'] == 1) {
                        $paramsArray = $response['Items'];
                    } else {
                        $errors[] = $response['errors'];
                    }
//                    $functionsArray = $this->buildSpecificFunctionArray($specificId, $tableName);
                    break;
                case 'worker':
                    $workerObjectId = WorkerObject::findOne(['worker_id' => $specificId]);
                    $tableName = "worker";//сохраняем конкретную таблицу
                    $paramsArray = HandbookEmployeeController::buildSpecificParameterArray($workerObjectId);
//                    $functionsArray = $this->buildSpecificFunctionArray($workerObjectId, $tableName);
                    break;
                case 'edge':
                    $tableName = $post['table_name'];//сохраняем конкретную таблицу
                    $paramsArray = SpecificEdgeController::buildEdgeParameterArray($specificId);                                 //метод построения параметров
                    //echo "   ";
                    //echo $duration_method = round(microtime(true) - $microtime_start, 6);
//                    $functionsArray = $this->buildSpecificFunctionArray($specificId, $tableName);
                    break;
                case 'equipment':
                    $response = SpecificEquipmentController::buildEquipmentParameterArray($specificId);                                 //метод построения параметров
                    if ($response['status'] == 1) {
                        $paramsArray = $response['Items'];
                    } else {
                        $errors[] = $response['errors'];
                    }
//                    $functionsArray = $this->buildSpecificFunctionArray($specificId, 'equipment');                               //метод построения функций
                    break;
                default:
                    $tableName = $post['table_name'];//сохраняем конкретную таблицу
                    $paramsArray = $this->buildSpecificParameterArray($specificId, $tableName);                                 //метод построения параметров
//                    $functionsArray = $this->buildSpecificFunctionArray($specificId, $tableName);                               //метод построения функций

            }
        } else {
            $errors[] = "Данные не переданы";
        }
        ArrayHelper::multisort($paramsArray, 'title', SORT_ASC);
        $result = array('paramArray' => $paramsArray, 'funcArray' => $functionsArray, 'errors' => $errors);

        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    public function buildSpecificParameterArray($specificObjectId, $table_name = 1)
    {
        if ($table_name == 1) {
            $sql_filter = 'main_id="' . $specificObjectId . '"';
            $table_name = (new Query())
                ->select(
                    'table_address'
                )
                ->from(['view_object_specific'])
                ->where($sql_filter)
                ->one();
        }
        $paramsArray = array();                                                                                         // массив для сохранения параметров

        $tableName = self::camelCase($table_name);
        $modelName = 'get' . ucfirst($tableName) . 'Parameters';                                                        // динамическое построение имени для поиска параметров
        $tableNameParameterValue = 'get' . ucfirst($tableName) . 'ParameterValues';
        $tableNameParameterSensor = '';
        if ($table_name != 'edge') {
            $tableNameParameterSensor = 'get' . ucfirst($tableName) . 'ParameterSensors';
        }
        $tableNameParameterHandbookValue = 'get' . ucfirst($tableName) . 'ParameterHandbookValues';
        if ($table_name == 'worker') {
            $nameId = $table_name . '_object_id';
        } else {
            $nameId = $table_name . '_id';                                                                              // динамическое построение имени столбца с id
        }

        $kinds = KindParameter::find()
            ->with('parameters')
            ->with('parameters.unit')
            ->all();                                                                                                    // находим все виды параметров
        $i = 0;
        if ($specificObjectId) {                                                                                        // если передан id конкретного объекта
            foreach ($kinds as $kind) {                                                                                 // перебираем все виды параметров
                $paramsArray[$i]['id'] = $kind->id;                                                                     // сохраняем id вида параметров
                $paramsArray[$i]['title'] = $kind->title;                                                               // сохраняем имя вида параметра
                if ($parameters = $kind->parameters) {                                                                  // если у вида параметра есть параметры
                    $j = 0;
                    foreach ($parameters as $parameter) {                                                               // перебираем все параметры
                        try {
                            if ($specificObjParameters = $parameter->$modelName()
                                ->where([$nameId => $specificObjectId])->orderBy(['parameter_type_id' => SORT_ASC])->all()) {// если есть типовые параметры переданного объекта
                                $paramsArray[$i]['params'][$j]['id'] = $parameter->id;                                  // сохраняем id параметра
                                $paramsArray[$i]['params'][$j]['title'] = $parameter->title;                            // сохраняем наименование параметра
                                $paramsArray[$i]['params'][$j]['units'] = $parameter->unit->short;                      // сохраняем единицу измерения
                                $paramsArray[$i]['params'][$j]['units_id'] = $parameter->unit_id;                       // сохраняем id единицы измерения
                                $k = 0;
                                foreach ($specificObjParameters as $specificObjParameter) {                             // перебираем конкретный параметр
                                    $paramsArray[$i]['params'][$j]['specific'][$k]['id'] = $specificObjParameter->parameter_type_id;//id типа параметра
                                    $paramsArray[$i]['params'][$j]['specific'][$k]['title'] = $specificObjParameter->parameterType->title;//название параметра
                                    $paramsArray[$i]['params'][$j]['specific'][$k]['specificObjectParameterId'] = $specificObjParameter->id;//id параметра конкретного объекта

                                    switch ($specificObjParameter->parameter_type_id) {
                                        case 1:
                                            if ($value = $specificObjParameter->$tableNameParameterHandbookValue()->orderBy(['date_time' => SORT_DESC])->one()) {
                                                if ($value->value != 'empty')
                                                    $paramsArray[$i]['params'][$j]['specific'][$k]['value'] = $value->value;//сохраняем справочное значение

                                                if ($parameter->id == 337) {
                                                    // echo "зашли в условие для асутп " .(int)$value->value."\n";
                                                    $asmtpTitle = $value->value == -1 ? '' : ASMTP::findOne((int)$value->value)->title;
                                                    $paramsArray[$i]['params'][$j]['specific'][$k]['asmtpTitle'] = $asmtpTitle;
                                                } else if ($parameter->id == 338) {
//                                                echo "зашли в условие для типов датчика ". $value->value. "\n";
                                                    $sensorTypeTitle = $value->value == -1 ? '' : SensorType::findOne((int)$value->value)->title;
                                                    $paramsArray[$i]['params'][$j]['specific'][$k]['sensorTypeTitle'] = $sensorTypeTitle;
                                                } else if ($parameter->id == 274) {
//                                                echo "зашли в условие для типов датчика ". $value->value. "\n";
                                                    if ($objectTitle = TypicalObject::findOne($value->value)) {
                                                        $paramsArray[$i]['params'][$j]['specific'][$k]['objectTitle'] = $objectTitle->title;
                                                    }
                                                } else if ($parameter->id == 122) {
                                                    if ($placeTitle = Place::findOne($value->value)) {
                                                        $paramsArray[$i]['params'][$j]['specific'][$k]['placeTitle'] = $placeTitle->title;
                                                    } else {
                                                        $paramsArray[$i]['params'][$j]['specific'][$k]['placeTitle'] = '';
                                                    }
                                                }
                                            }

                                            break;
                                        case 2:
                                            if ($valueFromParameterValue = $specificObjParameter->$tableNameParameterValue()->orderBy(['date_time' => SORT_DESC])->one()) {
                                                $paramsArray[$i]['params'][$j]['specific'][$k]['value'] = $valueFromParameterValue->value;
                                            } else {
                                                $paramsArray[$i]['params'][$j]['specific'][$k]['value'] = '-1';
                                            }

                                            break;
                                        case 3:
                                            if ($valueFromParameterValue = $specificObjParameter->$tableNameParameterValue()->orderBy(['date_time' => SORT_DESC])->one()) {
                                                $paramsArray[$i]['params'][$j]['specific'][$k]['value'] = $valueFromParameterValue->value;
                                            } else {
                                                $paramsArray[$i]['params'][$j]['specific'][$k]['value'] = '-1';
                                            }
                                            $k++;
                                            $paramsArray[$i]['params'][$j]['specific'][$k]['id'] = 5;                   //id типа параметра
                                            $paramsArray[$i]['params'][$j]['specific'][$k]['title'] = 'Привязка датчика';//название параметра
                                            $paramsArray[$i]['params'][$j]['specific'][$k]['specificObjectParameterId'] = $specificObjParameter->id;//id параметра кон
                                            if ($tableNameParameterSensor != '' && $value = $specificObjParameter->$tableNameParameterSensor()->orderBy(['date_time' => SORT_DESC])->one()) {
                                                $paramsArray[$i]['params'][$j]['specific'][$k]['sensor_id'] = $value->sensor_id;//сохраняем измеряемое значение
                                            } else {
                                                $paramsArray[$i]['params'][$j]['specific'][$k]['sensor_id'] = -1;
                                            }
                                            break;
                                    }
                                    $k++;
                                }
                                $j++;
                            }
                        } catch (Throwable $exception) {
                            Assistant::VarDump($exception->getLine());
                            Assistant::VarDump($exception->getMessage());
                            Assistant::VarDump($exception->getTraceAsString());
                        }
                    }
                    ArrayHelper::multisort($paramsArray[$i]['params'], 'title', SORT_ASC);
                }
                $i++;
            }
        }
        ArrayHelper::multisort($paramsArray, 'title', SORT_ASC);
        return $paramsArray;
    }

    public function buildSpecificFunctionArray($specificObjectId, $table_name)
    {
        $functionsArray = array();
        $i = -1;
        $j = 0;
        $tableName = self::camelCase($table_name);
        if ($table_name == "worker") {
            $tableNameId = $table_name . "_object_id";
        } else {
            $tableNameId = $table_name . "_id";
        }
        $specificFunction = "frontend\\models\\" . ucfirst($tableName) . "Function";//динамическое построение таблицы с функциями для конкретных объектов
        $specificTable = $table_name . "_function";
        //$functions = $specificFunction::find()->where([$tableNameId => $specificObjectId])->all();//находим все функции которые есть у конкретного объекта
        $functions = (new Query())
            ->select(
                [
                    $specificTable . '.id id',
                    'func.title functionTitle',
                    'func.id functionId',
                    'func.func_script_name scriptName',
                    'function_type.title functionTypeTitle',
                    'func.function_type_id functionTypeId',
                ])
            ->from([$specificTable])
            ->leftJoin('func', $specificTable . '.function_id = func.id')
            ->leftJoin('function_type', 'function_type.id = func.function_type_id')
            ->where([$specificTable . '.' . $tableNameId => $specificObjectId])
            ->orderBy("functionTypeId")
            ->all();

        foreach ($functions as $function) {
            if ($i == -1 || $functionsArray[$i]['id'] != $function['functionTypeId']) {
                $i++;
                $functionsArray[$i]['id'] = $function['functionTypeId'];
                $functionsArray[$i]['title'] = $function['functionTypeTitle'];
                $j = 0;

            }
            $functionsArray[$i]['funcs'][$j]['id'] = $function['id'];
            $functionsArray[$i]['funcs'][$j]['title'] = $function['functionTitle'];
            $functionsArray[$i]['funcs'][$j]['script_name'] = $function['scriptName'];
            $j++;
            if (count($functionsArray[$i]['funcs']) > 0) {
                ArrayHelper::multisort($functionsArray[$i]['funcs'], 'title', SORT_ASC);
            }
        }

        ArrayHelper::multisort($functionsArray[$i]['funcs'], ['title'], SORT_ASC);
        return $functionsArray;
    }

    /**
     * Метод создания parameter_value или же parameter_handbook_value в зависимости от  типа параметра
     * включает в себя CreateParameterValueOrHandbookValue()
     */
    public function actionAddSpecificObjectParameter()
    {
        //TODO: для работников не работает, надо исправить
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();

        try {
            $warnings[] = "actionAddSpecificObjectParameter. Начал выполнять метод";

            $session = Yii::$app->session;
            $session->open();
            if (!isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
                throw new Exception('actionGetSensorsParameters. Время сессии закончилось. Требуется повторный ввод пароля');
            }

            if (!AccessCheck::checkAccess($session['sessionLogin'], 93)) {                                        //если пользователю разрешен доступ к функции
                throw new Exception('actionGetSensorsParameters. Недостаточно прав для совершения данной операции');
            }

            $post = Yii::$app->request->post();
            if (isset($post['table_name']) and $post['table_name'] != ""
                and isset($post['parameter_id']) and $post['parameter_id'] != ""
                and isset($post['parameter_type_id']) and $post['parameter_type_id'] != ""
                and isset($post['object_id']) and $post['object_id'] != ""
                and isset($post['value']) and $post['value'] != "") {
                $table_name = strval($post['table_name']);
                $parameter_id = (int)$post['parameter_id'];
                $object_id = (int)$post['object_id'];
                $parameter_type_id = (int)$post['parameter_type_id'];
                $value = strval($post['value']);
            } else {
                throw new Exception('actionGetSensorsParameters. Не переданы параметры запроса');

            }

            $modelName = "frontend\\models\\";
            switch ($table_name) {
                case "pps_mine" :
                    $modelName .= "PpsMine";
                    break;
                case "energy_mine" :
                    $modelName .= "EnergyMine";
                    break;
                default :
                    $modelName .= ucfirst($table_name);
                    break;
            }

            $modelParameterName = $modelName . 'Parameter';
            $specific_column_name_id = $table_name . '_id';                                                                         // название столбца таблицы (sensor_id, place_id? equipment_id)
            $specific_column_parameter_name_id = $table_name . '_parameter_id';
            $specific_ids = (new Query())->select('id')->from($table_name)->where(['object_id' => $object_id])->all();
            $arrLength = count($specific_ids);
            for ((int)$i = 0; $i < $arrLength; $i++) {
                $specific_parameter = (new Query())// получаем объекты у которых есть такой параметр
                ->select('id')
                    ->from($table_name . "_parameter")
                    ->where([$table_name . "_id" => (int)$specific_ids[$i]['id'], 'parameter_id' => $parameter_id, 'parameter_type_id' => $parameter_type_id])
                    ->one();
                if ($specific_parameter) {                                                                                 // если найдены объекты с такими параметра, то добавим значения, без создания параметр
                    $flag_done = $this->CreateParameterValueOrHandbookValue((int)$specific_parameter['id'], $specific_column_parameter_name_id, $modelName, $parameter_type_id, $value);
                } else {                                                                                                   // если нет такого параметра у конкретного объекта, то создадим
                    $flag_done = $this->CreateSpecificParameter((int)$specific_ids[$i]['id'], $modelParameterName, $specific_column_name_id, $parameter_id, $parameter_type_id); //  метод создания параметра для конкретного объекта
                    if (!$flag_done) {                                                                                // если параметр создан, то создадим parameter_value или же parameter_handbook_value в зависимости от parameter_type_id
                        $errors[] = "Ошибка добавления параметра для конкретного объекта $specific_column_parameter_name_id = " . $specific_ids[$i]['id'];
                        continue;
                    }
                }

                $flag_done = $this->CreateParameterValueOrHandbookValue($flag_done, $specific_column_parameter_name_id, $modelName, $parameter_type_id, $value);
                switch ($flag_done) {
                    case -1 :
                        $errors[] = "Ошибка создания " . $modelName . "ParameterHandbookValue для конкретного объекта " . $table_name . "_id' = " . $specific_ids[$i]['id'];
                        break;
                    case -2 :
                        $errors[] = "Ошибка создания " . $modelName . "ParameterValue для конкретного объекта " . $table_name . "_id' = " . $specific_ids[$i]['id'];
                        break;
                }
            }

            //если мы добавляем типовой объект в горной среде
            if ($table_name == "place") {
                $modelParameterName2 = "frontend\\models\\" . "Edge" . "Parameter";                                  //формируем переменные для вызова метода добавления параметров и значений параметров для edge
                $specific_column_name_id2 = "edge_id";
                $specific_column_parameter_name_id2 = "edge_parameter_id";
                $modelName2 = "frontend\\models\\" . "Edge";
                $edge_place = (new Query())//ищем все ребра текущего place
                ->select('id')
                    ->from('edge')
                    ->where("place_id = " . (int)$specific_ids[$i]['id'])
                    ->all();
                foreach ($edge_place as $edge_id)                                                           //если нашли начинаем перебирать
                {
                    $edge_parameter = (new Query())//ищем нужный нам параметр у edge
                    ->select('id')
                        ->from('edge_parameter')
                        ->where(["edge_id" => $edge_id['id'], 'parameter_id' => $parameter_id, 'parameter_type_id' => $parameter_type_id])
                        ->one();
                    /**  СОЗДАЕМ ПАРАМЕТР ТОЛЬКО ТЕМ EDGE У КОГО НЕТ ТАКОГО ПАРАМЕТРА(ЕСЛИ У EDGE ЕСТЬ ТАКОЙ ПАРАМЕТР ТО НИЧЕГО С НИМ НЕ ДЕЛАЕМ) */
                    if (!$edge_parameter)                                                                    //если у edge нет такого параметра
                    {
                        $flag_done = $this->CreateSpecificParameter($edge_id['id'], $modelParameterName2, $specific_column_name_id2, $parameter_id, $parameter_type_id); //  метод создания параметра для конкретного объекта
//
                        if ($flag_done)                                                                                // если параметр создан, то создадим parameter_value или же parameter_handbook_value в зависимости от parameter_type_id
                        {
                            $flag_done = $this->CreateParameterValueOrHandbookValue($flag_done, $specific_column_parameter_name_id2, $modelName2, $parameter_type_id, $value);
                            switch ($flag_done) {
                                case -1 :
                                    $errors[] = "Ошибка создания " . $modelName . "ParameterHandbookValue для конкретного ребра id = " . $edge_id['id'];
                                    break;
                                case -2 :
                                    $errors[] = "Ошибка создания " . $modelName . "ParameterValue для конкретного ребра id' = " . $edge_id['id'];
                                    break;
                            }
                        } else {
                            $errors[] = "Ошибка добавления параметра для конкретного объекта $specific_column_parameter_name_id2 = " . $edge_id['id'];
                        }
                    }
                }
            }

            unset($specific_parameter);


        } catch (Throwable $ex) {
            $errors[] = "actionAddSpecificObjectParameter. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        $warnings[] = "actionAddSpecificObjectParameter. Закончил выполнять метод";
        $result_main = array('Items' => $result,
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionInitMain - метод инициализации всех кешей с нуля
    // сперва полностью кеш отчищается - затем заполняется последовательно
    // входные параметры массив:
    //      mine_id - ключ шахты
    //      flushCache:     - сбросить кеш
    //          sensor          - кеш сенсоров
    //          service         - сервисный кеш
    //          situation       - кеш ситуаций
    //          event           - кеш событий
    //          yii2            - кеш проекта
    //          worker          - кеш работников
    //          equipment       - кеш оборудования
    //      fillCache:      - заполнить кеш
    //          situation       - кеш ситуаций
    //          opc             - кеш OPC
    //          edge            - кеш выработок
    //          equipment       - кеш оборудования
    //          worker          - кеш работников
    //          sensor          - кеш сенсоров
    //  выходные параметры:
    //      стандартный набор
    // пример использования: http://127.0.0.1/specific-object/init-main?mine_id=270&flushCache={%22edge%22:true,%22equipment%22:true,%22worker%22:true,%22yii2%22:true,%22event%22:true,%22situation%22:true,%22service%22:true,%22sensor%22:true}&fillCache={%22edge%22:true,%22equipment%22:true,%22worker%22:true,%22yii2%22:true,%22situation%22:true,%22sensor%22:true,%22opc%22:true}
    // разработал Якимов М.Н.
    // дата создания 09.08.2019

    /**
     * Метод создания parameter_value или же parameter_handbook_value в зависимости от parameter_type_id
     * Если parameter_type_id = 1, то создается справочное значение для конкретного объекта
     * Если parameter_type_id = 2, то создается parameter_value для конкретного объекта
     * @param $specific_object_id - id конкретного объекта(sensor_id, place_id и тд...)
     * @param $specific_column_name_id - название столбца (sensor_id, place_id и тд...)
     * @param $table_upper_case_name - название таблицы с большой буквой
     * @param $parameter_type_id
     * @param $value
     * @return int
     */
    public function CreateParameterValueOrHandbookValue($specific_object_id, $specific_column_name_id, $table_upper_case_name, $parameter_type_id, $value)
    {
        $date_time = \backend\controllers\Assistant::GetDateNow();
        if ($parameter_type_id == 1) {
            $modelName = $table_upper_case_name . 'ParameterHandbookValue';
            $specific_parameter_handbook_value = new $modelName();
            $specific_parameter_handbook_value->$specific_column_name_id = $specific_object_id;
            $specific_parameter_handbook_value->date_time = $date_time;
            $specific_parameter_handbook_value->value = $value;
            $specific_parameter_handbook_value->status_id = 1;
            if ($specific_parameter_handbook_value->save()) {
                unset($specific_parameter_handbook_value);
                return 1;
            } else {
                unset($specific_parameter_handbook_value);
                return -1;
            }
        } else {
            $modelName = $table_upper_case_name . 'ParameterValue';
            $specific_parameter_value = new $modelName();
            $specific_parameter_value->$specific_column_name_id = $specific_object_id;
            $specific_parameter_value->date_time = $date_time;
            $specific_parameter_value->value = $value;
            $specific_parameter_value->status_id = 1;
            if ($specific_parameter_value->save()) {
                unset($specific_parameter_value);
                return 1;
            } else {
                unset($specific_parameter_value);
                return -2;
            }
        }
    }

    /**
     *  Универсальный метод лдя создания параметров для конкретного объекта
     * @param $specific_id - id конкретного объекта(sensor_id, place_id и тд...)
     * @param $table_upper_case_name - название таблицы с большой буквой
     * @param $specific_column_name_id - название столбца (sensor_id, place_id и тд...)
     * @param $parameter_id
     * @param $parameter_type_id
     * @return int
     */
    public function CreateSpecificParameter($specific_id, $table_upper_case_name, $specific_column_name_id, $parameter_id, $parameter_type_id)
    {
        $new_specific_parameter = new $table_upper_case_name();
        $new_specific_parameter->$specific_column_name_id = $specific_id;
        $new_specific_parameter->parameter_id = $parameter_id;
        $new_specific_parameter->parameter_type_id = $parameter_type_id;
        if ($new_specific_parameter->save()) {
            $new_specific_parameter->refresh();
            $new_specific_parameter_id = $new_specific_parameter->id;
            unset($new_specific_parameter);
            return $new_specific_parameter_id;
        }
        unset($new_specific_parameter);
        return -1;
    }
}
