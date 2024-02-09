<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\cachemanagers;

use backend\controllers\SensorBasicController;
use frontend\controllers\system\LogAmicumFront;
use Throwable;
use Yii;
use yii\db\Exception;
use yii\db\Query;

class SensorCacheController
{

    // setSensorParameterValue              -   метод записи значения сенсора в кеш
    // isStaticSensor                       -   Метод для проверки того, является ли сенсор стационарным датчиком
    // GetSensorsParametersValuesFromCache  -   Метод получения значений параметров сенсора из кэша.
    // getParameterValue                    -   Метод получения значения конкретного парамтера сенсора кэша SensorParameter
    // runInit                              -   Метод полной инициализации кэша сенсоров по шахте и со всеми значениями параметров
    // initSensorMain                       -   Функция для заполнения кеша основной информацией по сенсорам, привязанным к шахте
    // initSensorParameterValue             -   Метод инициализации кэша списка значений параметров сенсоров(а) - SensorParameterValue.
    // initSensorParameterHandbookValue     -   Метод инициализации кэша списка значений параметров сенсоров(а) - SensorParameterHandbookValue.
    // setSensorValues                      -   Метод добавления/редактирования значений параметров сенсора в кэш
    // addSensor                            -   Метод добавлния сенсора в кэш(БЕЗ ПАРАМЕТРОВ)
    // multiGetParameterValue               -   Метод получения значения параметров сенсоров из кэша redis.
    // delInSensorMineByOne                 -   Удаление сенсора из кэша
    // multiSetSensorParameterValue         -   Метод массовой вставки массива параметров и их значений в кеш
    // buildStructureSensor                 -   Метод создания структуры сенсора в кеше
    // buildStructureSensorParametersValue  -   Метод создания структуры значения параметра сенсора в кеше
    // getSensorMineBySensor                -   Метод получения сенсора(ов) по сенсор айди из кэша redis
    // getSensorMine                        -   Метод получения сенсора(ов) по шахте из кэша redis, если указан сенсор айди, то вернет только 1, иначе все.
    // getSensorMineBySensorOne             -   Получение сенсора из кэша SensorMine
    // buildSensorMineKey                   -   Построение ключа для кэша SensorMine
    // buildSenParSenTagKey                 -   создание ключа привязки к сенсору параметра
    // delSenParSenTag                      -   удаление кеша sensorParameterSensor
    // buildStructureTags                   -   Метод создания структуры привязки тега к конкретному параметру в кеше
    // multiGetSenParSenTag                 -   получение из кеша всех привязок к конктретным параметрам, что там есть
    // getSenParSenTag                      -   получить из кеша привязанные к тегу sensor_parameter_id
    // initSensorParameterSensor            -   метод инициализации привязки парамтеров сенсора к тегам OPC
    // setSenParSenTag                      -   записать привязку сенсора в кеш
    // multiSetSenParSenTag                 -   разовая запись значений привязки сенсора в кеш
    // amicum_flushall                      -   метод очистки кешей сенсоров
    // removeAll                            -   Метод полного удаления кэша сенсоров. Очищает все кэши связанные с сенсором
    // multiSetSensorParameterHandbookValues-   Метод массового заполенния кэша списка справночных значений параметров сенсоров(а)
    // multiSetSensorParameterValues        -   Метод массового заполенния кэша списка измеренных значений параметров сенсоров(а)
    // initSensorMainHash                   -   инициализация кеша сенсора в конкретной шахте
    // getSensorMineBySensorHash            -   метод получения шахты сенсора по его ключу

    // amicum_mGet                          -   метод получения данных с редис за один раз методами редиса
    // amicum_mSet                          -   Метод вставки значений в кэш командами редиса.
    // amicum_rSetDebug                     -   Тестовый метод для проверки укладованыя значении срузу в двух редисах


    public static $sensor_mine_cache_key = 'SeMi';
    public static $mine_map_cache_key = 'SMiMap';
    public static $sensor_map_cache_key = 'SeMap';
    public static $sensor_parameter_cache_key = 'SePa';
    public static $sensor_binding_key = 'SenObj';
    public static $sen_par_sen_key = 'SeTags';

    public $sensor_cache;
    public $sensor_parameter_cache;
    public $sensor_parameter_handbook_cache;

    public function __construct()
    {
        $this->sensor_cache = Yii::$app->redis_sensor;
        $this->sensor_parameter_cache = Yii::$app->redis_sensor_parameter;
        $this->sensor_parameter_handbook_cache = Yii::$app->redis_sensor_parameter_handbook;
    }

    public function actionIndex()
    {
        echo 'Класс ' . __METHOD__;
    }

    /**
     * Название метода: runInit()
     * Назначение метода: Метод полной инициализации кэша сенсоров по шахте и со всеми значениями параметров
     * Порядок заполнения очен важен!!!
     *
     * @param int $mine_id - идентификатор шахты
     *
     * @return array $result - массив рузельтата выполнения метода. Сами данные не возвращает
     *
     * @package backend\controllers\cachemanagers
     *
     * Входные обязательные параметры:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 24.05.2019 10:53
     * @since ver
     */
    public function runInit($mine_id)
    {
        $warnings = array();
        $status = null;
//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', '3000M');
        $errors = array();

        if ($mine_id != "") {


            $response = $this->initSensorParameterValue();                                                                            // инициализируем кэш списка сенсоров со значениями value
            $errors[] = $response['errors'];
            $warnings[] = $response['warnings'];
            $status['initSensorParameterValue'] = $response['status'];

            $response = $this->initSensorParameterHandbookValue();                                                                    // инициализируем кэш списка сенсоров со значениями handbook
            $errors[] = $response['errors'];
            $warnings[] = $response['warnings'];
            $status['initSensorParameterHandbookValue'] = $response['status'];

            $response = $this->initSensorParameterSensor();                                                                    // инициализируем кэш списка сенсоров со значениями handbook
            $errors[] = $response['errors'];
            $warnings[] = $response['warnings'];
            $status['initSensorParameterSensor'] = $response['status'];
//
            $response = $this->initSensorMain($mine_id);                                                                            // инициализируем кэш списка сенсоров по шахте
            $errors[] = $response['errors'];
            $warnings[] = $response['warnings'];
            $status['initSensorMain'] = $response['status'];

            $status['initSensorNetwork'] = (new ServiceCache())->initSensorNetwork();

            unset($response);
        } else {
            $status = 0;
            $errors[] = "Идентификатор шахты не передан. Ошибка инициализации кэша сенсоров";
        }
        $result = array('errors' => $errors, 'status' => $status, 'warnings' => $warnings);
        return $result;
    }

    public function runInitHash($mine_id)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)

        $status = [];
        // Стартовая отладочная информация
        $log = new LogAmicumFront("runInitHash");

        try {
//            ini_set('max_execution_time', 6000);
//            ini_set('memory_limit', '3000M');

            if ($mine_id != "") {
                $log->addLog("Начало выполнения метода");

                $response = $this->initSensorParameterValueHash();                                                                            // инициализируем кэш списка сенсоров со значениями value
                $log->addLogAll($response);
                $status['initSensorParameterValueHash'] = $response['status'];
                $log->addLog("Инициализировал initSensorParameterValueHash: " . $response['status']);

                $response = $this->initSensorParameterHandbookValueHash();                                                                    // инициализируем кэш списка сенсоров со значениями handbook
                $log->addLogAll($response);
                $status['initSensorParameterHandbookValueHash'] = $response['status'];
                $log->addLog("Инициализировал initSensorParameterHandbookValueHash: " . $response['status']);

                $response = $this->initSensorParameterSensor();                                                                    // инициализируем кэш списка сенсоров со значениями handbook
                $log->addLogAll($response);
                $status['initSensorParameterSensor'] = $response['status'];
                $log->addLog("Инициализировал initSensorParameterSensor: " . $response['status']);

                $response = $this->initSensorMainHash($mine_id);                                                                            // инициализируем кэш списка сенсоров по шахте
                $log->addLogAll($response);
                $status['initSensorMainHash'] = $response['status'];
                $log->addLog("Инициализировал initSensorMainHash: " . $response['status']);

                $status['initSensorNetwork'] = (new ServiceCache())->initSensorNetwork();

                $log->addLog("Инициализировал initSensorNetwork: " . $status['initSensorNetwork']);

                unset($response);
            } else {
                $log->addLog("Идентификатор шахты не передан. Ошибка инициализации кэша сенсоров");
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result, 'status_cash' => $status], $log->getLogAll());
    }

    /**
     * Метод для проверки того, является ли сенсор стационарным датчиком
     * @param $object_type_id - идентификатор типа объекта
     * @return int
     */
    public static function isStaticSensor($object_type_id)
    {
        $parameter_type_id = 2;
        if ($object_type_id == 22 || $object_type_id == 116 || $object_type_id == 95 || $object_type_id == 96 || $object_type_id == 28) {
            $parameter_type_id = 1;
        }
        return $parameter_type_id;
    }

    /**
     * Метод для проверки того, является ли сенсор стационарным датчиком
     * @param $object_id - идентификатор объекта
     * @return int
     */
    public function isStaticSensorByObject($object_id)
    {
        $parameter_type_id = 2;
        if (
            $object_id == 27 ||
            $object_id == 28 ||
            $object_id == 29 ||
            $object_id == 45 ||
            $object_id == 46 ||
            $object_id == 49 ||
            $object_id == 75 ||
            $object_id == 90 ||
            $object_id == 91 ||
            $object_id == 105 ||
            $object_id == 113 ||
            $object_id == 155 ||
            $object_id == 156 ||
            $object_id == 194 ||
            $object_id == 195 ||
            $object_id == 200) {
            $parameter_type_id = 1;
        }
        return $parameter_type_id;
    }


    /**
     * Метод для проверки того, является ли сенсор узлом связи Strata
     * @param $object_id - идентификатор объекта
     * @return bool
     */
    public static function isCommnodeSensor($object_id)
    {
        if (in_array($object_id, [45, 46, 90, 91, 105, 113])) {
            return true;
        }
        return false;
    }


    /**
     * Название метода: initSensorMain()
     * Назначение метода: Функция для заполнения кеша основной информацией по сенсорам, привязанным к шахте
     * Вызывается, если нужно заполнить кеш по сенсорам. Метод ничего не возвращает
     *
     * Входные обязательные параметры:
     *
     * @param     $mine_id - идентификатор шахты
     *
     * Входные необязательные параметры:
     * @param int $sensor_id - ИД конкретного сенсора. Если указать конкретный сенсор, то его только и добавляет в кэш.
     *
     * @return  array массив данных
     *
     * @example SensorCacheController::InitSensorMain(290)
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 23.05.2019 13:32
     */
    public function initSensorMain($mine_id, $sensor_id = -1)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();
        $warnings[] = 'initSensorMain. Начал выполнять метод';
        try {
            $sql_filter = '';
            if ($sensor_id != -1) $sql_filter = "sensor.id = $sensor_id";
            $sensors = (new Query())
                ->select([
                    'sensor.id as sensor_id',
                    'sensor.title as sensor_title',
                    'object.id as object_id',
                    'object.title as object_title',
                    'object_type_id'
                ])
                ->from('sensor')
                ->innerJoin('object', 'object.id = sensor.object_id')
                ->where($sql_filter)
                ->all();

            if ($sensors) {
                $warnings[] = 'initSensorMain. Нашел сенсор в БД';
                $warnings[] = 'initSensorMain. Начинаю перебирать';
                foreach ($sensors as $sensor) {
                    //$warnings[] = 'initSensorMain. Зашел в перебор';
                    $object_type_id = $sensor['object_type_id'];
                    $sensor_id = $sensor['sensor_id'];
                    $parameter_type_id = 2;
                    if ($object_type_id == 22 || $object_type_id == 116 || $object_type_id == 95 || $object_type_id == 96 || $object_type_id == 28) {
                        $parameter_type_id = 1;
                    }

                    $sensor_mine = $this->getParameterValueHash($sensor_id, 346, $parameter_type_id);

                    if ($sensor_mine and $sensor_mine['value'] == $mine_id) {
                        //$warnings[] = 'initSensorMain. Шахты совпали начинаю форматировать массив';
                        $sensor_main_info = self::buildStructureSensor(
                            $sensor['sensor_id'],
                            $sensor['sensor_title'],
                            $sensor['object_id'],
                            $sensor['object_title'],
                            $sensor['object_type_id'],
                            $mine_id
                        );
                        //$warnings[] = "initSensorMain. Начал создавать ключ для шахты $mine_id и сенсора $sensor_id";
                        $key = self::buildSensorMineKey($mine_id, $sensor_id);
                        $date_to_cache[$key] = $sensor_main_info;
                    } else {
                        //$warnings[] = 'initSensorMain. Шахты не совпали';
                    }
                }
                if (isset($date_to_cache)) {
                    $this->amicum_mSet($this->sensor_cache, $date_to_cache);
                }
            } else {
                throw new \Exception("initSensorMain. Заданного сенсора нет в БД $sensor_id");
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'initSensorMain. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'initSensorMain. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // initSensorMainHash - инициализация кеша сенсора в конкретной шахте
    // $mine_id     - ключ шахты
    // $sensor_id   - ключ сенсора
    public function initSensorMainHash($mine_id, $sensor_id = -1)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();
        $warnings[] = 'initSensorMainHash. Начал выполнять метод';
        try {
            $sql_filter = '';
            if ($sensor_id != -1) $sql_filter = "sensor.id = $sensor_id";
            Yii::$app->db_amicum2->createCommand('SET SESSION wait_timeout = 28800;')->execute();
            Yii::$app->db->createCommand('SET SESSION wait_timeout = 28800;')->execute();
            $sensors = (new Query())
                ->select([
                    'sensor.id as sensor_id',
                    'sensor.title as sensor_title',
                    'object.id as object_id',
                    'object.title as object_title',
                    'object_type_id'
                ])
                ->from('sensor')
                ->innerJoin('object', 'object.id = sensor.object_id')
                ->where($sql_filter)
                ->all();

            if ($sensors) {
                $warnings[] = 'initSensorMainHash. Нашел сенсор в БД';
                $warnings[] = 'initSensorMainHash. Начинаю перебирать';
                $mine_map_key = self::buildMineMapKeyHash($mine_id);
                foreach ($sensors as $sensor) {
                    //$warnings[] = 'initSensorMainHash. Зашел в перебор';
                    $object_type_id = $sensor['object_type_id'];
                    $sensor_id = $sensor['sensor_id'];
                    $parameter_type_id = 2;
                    if ($object_type_id == 22 || $object_type_id == 116 || $object_type_id == 95 || $object_type_id == 96 || $object_type_id == 28) {
                        $parameter_type_id = 1;
                    }

                    $sensor_mine = $this->getParameterValueHash($sensor_id, 346, $parameter_type_id);

                    if ($sensor_mine and $sensor_mine['value'] == $mine_id) {
                        //$warnings[] = 'initSensorMainHash. Шахты совпали начинаю форматировать массив';
                        $sensor_main_info = self::buildStructureSensor(
                            $sensor['sensor_id'],
                            $sensor['sensor_title'],
                            $sensor['object_id'],
                            $sensor['object_title'],
                            $sensor['object_type_id'],
                            $mine_id
                        );
                        //$warnings[] = "initSensorMainHash. Начал создавать ключ для шахты $mine_id и сенсора $sensor_id";
                        $date_to_cache[$mine_map_key][$sensor_id] = $sensor_main_info;
                    } else {
                        //$warnings[] = 'initSensorMainHash. Шахты не совпали';
                    }
                }
                if (isset($date_to_cache)) {
                    $this->amicum_mSetHash($this->sensor_cache, $date_to_cache);
                }
            } else {
                throw new \Exception("initSensorMainHash. Заданного сенсора нет в БД $sensor_id");
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'initSensorMainHash. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'initSensorMainHash. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Название метода: initSensorParameterValue()
     * Назначение метода: Метод инициализации кэша списка значений параметров сенсоров(а) - SensorParameterValue.
     * Если указать этот параметр, то инициализируется кэш конкретного сенсора
     * Метод ничего не возвращает
     *
     * Входные не обязательные параметры:
     *
     * @param int $sensor_id - идентификатор конкретного сенсора.
     *
     * @return  array массив данных
     * @package backend\controllers\cachemanagers
     *
     * @example (new SensorCacheController())->initSensorParameterValue();
     * @example (new SensorCacheController())->initSensorParameterValue(310);
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 28.05.2019 10:18
     */
    public function initSensorParameterValue($sensor_id = -1, $sql = '')
    {

        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = 'initSensorParameterValue. Начал выполнять метод';
        $filter_parameter = '(162,338,337,163,165,169,225,226,161,160,104,82,190,188,189,4,118,308,275,227,309,307,391,446,458,86)';
        try {
            $sql_filter = '';
            if ($sensor_id !== -1) {
                $sql_filter .= "sensor_id = $sensor_id ";
            }
            if ($sql !== '') {
                $sql_filter .= ' AND ' . $sql;
            }
            Yii::$app->db_amicum2->createCommand('SET SESSION wait_timeout = 28800;')->execute();
            Yii::$app->db->createCommand('SET SESSION wait_timeout = 28800;')->execute();
            $sensor_parameter_values = (new Query())
                ->select([
                    'sensor_id',
                    'sensor_parameter_id',
                    'parameter_id',
                    'parameter_type_id',
                    'date_time',
                    'value',
                    'status_id'])
                ->from('view_initSensorParameterValue')
                ->where($sql_filter)
                ->andwhere('parameter_id not in ' . $filter_parameter)
                ->all();
            if ($sensor_parameter_values) {
                $result = $sensor_parameter_values;
                $warnings[] = 'initSensorParameterValue. Нашел параметры в БД начинаю инициализировать кеш';
                foreach ($sensor_parameter_values as $sensor_parameter_value) {
                    $spv_key = $this->buildParameterKey($sensor_parameter_value['sensor_id'], $sensor_parameter_value['parameter_id'], $sensor_parameter_value['parameter_type_id']);
                    //$warnings[] = "Кеш параметров сенсора $sensor_id по ключу $spv_key инициализирован";
                    $sensor_paramter_value_array[$spv_key] = $sensor_parameter_value;
                }

                $this->amicum_mSet($this->sensor_parameter_cache, $sensor_paramter_value_array);
                unset(
                    $sensor_paramter_value_array,
                    $sensor_parameter_values
                );
            } else {
                $warnings[] = 'initSensorParameterValue. Список измеренных параметров в БД пуст';
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'initSensorParameterValue. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'initSensorParameterValue. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    public function initSensorParameterValueHash($sensor_id = -1, $sql = '')
    {

        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = 'initSensorParameterValueHash. Начал выполнять метод';
        $filter_parameter = '(162,338,337,163,165,169,225,226,161,160,104,82,190,188,189,4,118,308,275,227,309,307,391,446,458,86)';
        try {
            $sql_filter = '';
            if ($sensor_id !== -1) {
                $sql_filter .= "sensor_id = $sensor_id ";
            }
            if ($sql !== '') {
                $sql_filter .= ' AND ' . $sql;
            }
            Yii::$app->db_amicum2->createCommand('SET SESSION wait_timeout = 28800;')->execute();
            Yii::$app->db->createCommand('SET SESSION wait_timeout = 28800;')->execute();
            $sensor_parameter_values = (new Query())
                ->select([
                    'sensor_id',
                    'sensor_parameter_id',
                    'parameter_id',
                    'parameter_type_id',
                    'date_time',
                    'value',
                    'status_id'])
                ->from('view_initSensorParameterValue')
                ->where($sql_filter)
                ->andwhere('parameter_id not in ' . $filter_parameter)
                ->all();
            if ($sensor_parameter_values) {
                $result = $sensor_parameter_values;
                $warnings[] = 'initSensorParameterValueHash. Нашел параметры в БД начинаю инициализировать кеш';
                foreach ($sensor_parameter_values as $sensor_parameter_value) {
                    $spv_key = $this->buildParameterKeyHash($sensor_parameter_value['parameter_id'], $sensor_parameter_value['parameter_type_id']);
                    //$warnings[] = "initSensorParameterValueHash. Кеш параметров сенсора $sensor_id по ключу $spv_key инициализирован";
                    $sensor_map_key = $this->buildSensorMapKeyHash($sensor_parameter_value['sensor_id']);
                    $sensor_parameter_value_array[$sensor_map_key][$spv_key] = $sensor_parameter_value;
                }
                $warnings[] = $this->amicum_mSetHash($this->sensor_parameter_handbook_cache, $sensor_parameter_value_array);
                unset(
                    $sensor_parameter_value_array,
                    $sensor_parameter_values
                );
            } else {
                $warnings[] = 'initSensorParameterValueHash. Список измеренных параметров в БД пуст';
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'initSensorParameterValueHash. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'initSensorParameterValueHash. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Название метода: initSensorParameterHandbookValue()
     * Назначение метода: Метод инициализации кэша списка значений параметров сенсоров(а) -
     * SensorParameterHandbookValue. Если указать этот параметр, то инициализируется кэш конкретного сенсора Метод
     * ничего не возвращает
     *
     * Входные не обязательные параметры:
     *
     * @param int $sensor_id - идентификатор конкретного сенсора.
     *
     * @return  array массив данных
     * @package backend\controllers\cachemanagers
     *
     * @example (new SensorCacheController())->initSensorParameterHandbookValue();
     * @example (new SensorCacheController())->initSensorParameterHandbookValue(310);
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 28.05.2019 10:30
     */
    public function initSensorParameterHandbookValue($sensor_id = -1, $sql = '')
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = 'initSensorParameterHandbookValue. Начал выполнять метод';
        $filter_parameter = '(162,338,337,163,165,169,225,226,161,160,104,82,190,188,189,4,118,308,275,227,309,307,391,446,458,86)';
        try {
            $sql_filter = '';
            if ($sensor_id !== -1) {
                $sql_filter .= "sensor_id = $sensor_id ";
            }
            if ($sql !== '') {
                $sql_filter .= ' AND ' . $sql;
            }
            $sensor_parameter_handbook_values = (new Query())
                ->select(['sensor_id', 'sensor_parameter_id', 'parameter_id', 'parameter_type_id', 'date_time', 'value', 'status_id'])
                ->from('view_initSensorParameterHandbookValue')
                ->where($sql_filter)
                ->andwhere('parameter_id not in ' . $filter_parameter)
                ->all();

            if ($sensor_parameter_handbook_values) {
                $result = $sensor_parameter_handbook_values;
                $warnings[] = 'initSensorParameterHandbookValue. Нашел параметры в БД начинаю инициализировать кеш';
                $sensor_id_current = -1;
                foreach ($sensor_parameter_handbook_values as $sensor_parameter_handbook_value) {
                    $spv_key = $this->buildParameterKey($sensor_parameter_handbook_value['sensor_id'], $sensor_parameter_handbook_value['parameter_id'], $sensor_parameter_handbook_value['parameter_type_id']);
                    //$warnings[] = "initSensorParameterHandbookValue. Кеш параметров сенсора $sensor_id по ключу $spv_key инициализирован";
                    $sensor_paramter_handbook_value_array[$spv_key] = $sensor_parameter_handbook_value;
                }
                $this->amicum_mSet($this->sensor_parameter_handbook_cache, $sensor_paramter_handbook_value_array);
                unset($sensor_paramter_handbook_value_array, $sensor_parameter_handbook_values);
            } else {
                $warnings[] = 'initSensorParameterHandbookValue. Список справочных параметров в БД пуст';
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'initSensorParameterHandbookValue. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'initSensorParameterHandbookValue. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    public function initSensorParameterHandbookValueHash($sensor_id = -1, $sql = '')
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = 'initSensorParameterHandbookValueHash. Начал выполнять метод';
        $filter_parameter = '(162,338,337,163,165,169,225,226,161,160,104,82,190,188,189,4,118,308,275,227,309,307,391,446,458,86)';
        try {
            $sql_filter = '';
            if ($sensor_id !== -1) {
                $sql_filter .= "sensor_id = $sensor_id ";
            }
            if ($sql !== '') {
                $sql_filter .= ' AND ' . $sql;
            }
            $sensor_parameter_handbook_values = (new Query())
                ->select(['sensor_id', 'sensor_parameter_id', 'parameter_id', 'parameter_type_id', 'date_time', 'value', 'status_id'])
                ->from('view_initSensorParameterHandbookValue')
                ->where($sql_filter)
                ->andwhere('parameter_id not in ' . $filter_parameter)
                ->all();

            if ($sensor_parameter_handbook_values) {
                $result = $sensor_parameter_handbook_values;
                $warnings[] = 'initSensorParameterHandbookValueHash. Нашел параметры в БД начинаю инициализировать кеш';
                $sensor_id_current = -1;
                foreach ($sensor_parameter_handbook_values as $sensor_parameter_handbook_value) {
                    $spv_key = $this->buildParameterKeyHash($sensor_parameter_handbook_value['parameter_id'], $sensor_parameter_handbook_value['parameter_type_id']);
                    $sensor_map_key = $this->buildSensorMapKeyHash($sensor_parameter_handbook_value['sensor_id']);
                    //$warnings[] = "initSensorParameterHandbookValue. Кеш параметров сенсора $sensor_id по ключу $spv_key инициализирован";
                    $sensor_paramter_handbook_value_array[$sensor_map_key][$spv_key] = $sensor_parameter_handbook_value;
                }
                $warnings[] = $this->amicum_mSetHash($this->sensor_parameter_handbook_cache, $sensor_paramter_handbook_value_array);
                unset($sensor_paramter_handbook_value_array, $sensor_parameter_handbook_values);
            } else {
                $warnings[] = 'initSensorParameterHandbookValueHash. Список справочных параметров в БД пуст';
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'initSensorParameterHandbookValueHash. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'initSensorParameterHandbookValueHash. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // setSensorParameterValue - метод записи значения сенсора в кеш
    // пример использования: (new SensorCacheController())->setSensorParameterValue($sensor_id, $sensor_parameter_id, $sensor_parameter_value, $parameter_id, $parameter_type_id,$sensor_parameter_status_id,$sensor_parameter_date_time);
    public function setSensorParameterValue($sensor_id, $sensor_parameter_id, $sensor_parameter_value, $parameter_id, $parameter_type_id, $sensor_parameter_status_id = 1, $sensor_parameter_date_time = null)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'setSensorParameterValue. Начало выполнения метода';
        try {
            //если дата не передана, то брать текущее время с миллисекундами
            if (empty($sensor_parameter_date_time)) {
                $sensor_parameter_date_time = date('Y-m-d H:i:s.U');
                $warnings[] = 'setSensorParameterValue. Дата не задана. Взял текущую';
            }
            /**
             * если sensor_parameter_id равен -1 при вызове функции, но указаны parameter_id и parameter_type_id,
             * то мы его ищем в кеше, если его и там нет, то мы его ищем в БД, если его и там нет, то выкидываем исключение
             */
            if ($sensor_parameter_id == -1) {
                $warnings[] = 'setSensorParameterValue. Sensor_parameter_id=-1 Начинаю поиск в кеше или в базе';
                $key = $this->buildParameterKey($sensor_id, $parameter_id, $parameter_type_id);
                if ($parameter_type_id == 1) {
                    $sensor_parameters = $this->amicum_rGet($this->sensor_parameter_handbook_cache, $key);
                } else {
                    $sensor_parameters = $this->amicum_rGet($this->sensor_parameter_cache, $key);
                }

                if ($sensor_parameters) {
                    $warnings[] = 'setSensorParameterValue. Нашел сенсор параметер айди в кеше';
                    $sensor_parameter_id = $sensor_parameters['sensor_parameter_id'];
                } else {
                    $warnings[] = 'setSensorParameterValue. В кеше не было ищу в базе';
                    $response = SensorBasicController::getSensorParameter($sensor_id, $parameter_id, $parameter_type_id);
                    if ($response['status'] == 1) {
                        $sensor_parameter_id = $sensor_parameters['sensor_parameter_id'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        $warnings[] = 'setSensorParameterValue. Нашел в базе данных/или создал в БД';
                    } else {
                        $warnings[] = 'setSensorParameterValue. В базе сенсор параметер айди не нашел. генерирую исключение';
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new \Exception("setSensorParameterValue. Для сенсора $sensor_id не существует привязки к нему параметра $parameter_id и типа параметра $parameter_type_id");
                    }
                }
            }
            $warnings[] = "setSensorParameterValue. Сенсор параметер айди = $sensor_parameter_id";
            $warnings[] = 'setSensorParameterValue. Начинаю сохранение в кеш' . "Параметр: $parameter_id Тип параметра: $parameter_type_id";
            $sensor_parameter_values = self::buildStructureSensorParametersValue(
                $sensor_id, $sensor_parameter_id,
                $parameter_id, $parameter_type_id,
                $sensor_parameter_date_time, $sensor_parameter_value,
                $sensor_parameter_status_id);

            $key = $this->buildParameterKey($sensor_id, $parameter_id, $parameter_type_id);
            $warnings[] = "setSensorParameterValue. ключ для вставки $key";
            if ($parameter_type_id == 1) {
                $this->amicum_rSet($this->sensor_parameter_handbook_cache, $key, $sensor_parameter_values);
            } else {
                $this->amicum_rSet($this->sensor_parameter_cache, $key, $sensor_parameter_values);
            }
            $warnings[] = "setSensorParameterValue. Сохранил в кеш";
            unset($sensor_parameter_values);
            $status *= 1;
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "setSensorParameterValue. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        //$warnings[] = "setSensorParameterValue. Закончил выполнение метода";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    public function setSensorParameterValueHash($sensor_id, $sensor_parameter_id, $sensor_parameter_value, $parameter_id, $parameter_type_id, $sensor_parameter_status_id = 1, $sensor_parameter_date_time = null)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'setSensorParameterValueHash. Начало выполнения метода';
        try {
            //если дата не передана, то брать текущее время с миллисекундами
            if (empty($sensor_parameter_date_time)) {
                $sensor_parameter_date_time = date('Y-m-d H:i:s.U');
                $warnings[] = 'setSensorParameterValueHash. Дата не задана. Взял текущую';
            }
            /**
             * если sensor_parameter_id равен -1 при вызове функции, но указаны parameter_id и parameter_type_id,
             * то мы его ищем в кеше, если его и там нет, то мы его ищем в БД, если его и там нет, то выкидываем исключение
             */
            $sensor_map_key = $this->buildSensorMapKeyHash($sensor_id);
            if ($sensor_parameter_id == -1) {
                $warnings[] = 'setSensorParameterValueHash. Sensor_parameter_id=-1 Начинаю поиск в кеше или в базе';
                $key = $this->buildParameterKeyHash($parameter_id, $parameter_type_id);

                $sensor_parameters = $this->amicum_rGetHash($this->sensor_parameter_handbook_cache, $sensor_map_key, $key);


                if ($sensor_parameters) {
                    $warnings[] = 'setSensorParameterValueHash. Нашел сенсор параметер айди в кеше';
                    $sensor_parameter_id = $sensor_parameters['sensor_parameter_id'];
                } else {
                    $warnings[] = 'setSensorParameterValueHash. В кеше не было ищу в базе';
                    $response = SensorBasicController::getSensorParameter($sensor_id, $parameter_id, $parameter_type_id);
                    if ($response['status'] == 1) {
                        $sensor_parameter_id = $sensor_parameters['sensor_parameter_id'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        $warnings[] = 'setSensorParameterValueHash. Нашел в базе данных/или создал в БД';
                    } else {
                        $warnings[] = 'setSensorParameterValueHash. В базе сенсор параметер айди не нашел. генерирую исключение';
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new \Exception("setSensorParameterValueHash. Для сенсора $sensor_id не существует привязки к нему параметра $parameter_id и типа параметра $parameter_type_id");
                    }
                }
            }
            $warnings[] = "setSensorParameterValueHash. Сенсор параметер айди = $sensor_parameter_id";
            $warnings[] = "setSensorParameterValueHash. Начинаю сохранение в кеш. Параметр: $parameter_id Тип параметра:$parameter_type_id";
            $sensor_parameter_values = self::buildStructureSensorParametersValue(
                $sensor_id, $sensor_parameter_id,
                $parameter_id, $parameter_type_id,
                $sensor_parameter_date_time, $sensor_parameter_value,
                $sensor_parameter_status_id);

            $key = $this->buildParameterKeyHash($parameter_id, $parameter_type_id);
            $date_to_cache[$sensor_map_key][$key] = $sensor_parameter_values;
            $warnings[] = "setSensorParameterValueHash. ключ для вставки $key";

            $this->amicum_mSetHash($this->sensor_parameter_handbook_cache, $date_to_cache);

            $warnings[] = "setSensorParameterValueHash. Сохранил в кеш";
            unset($sensor_parameter_values);
            $status *= 1;
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "setSensorParameterValueHash. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        //$warnings[] = "setSensorParameterValueHash. Закончил выполнение метода";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * addSensor - Метод добавлния сенсора в кэш(БЕЗ ПАРАМЕТРОВ)
     * Принимает массив параметров:
     * mine_id
     * sensor_id
     * Created by: Якимов М.Н.
     * @since 09.04.2019 Переписан метод для обычного добавления/замены сенсора. Сырцев А.П.
     */
    public function addSensor($sensor)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = 'addSensor. Начал выполнять метод';
        try {
            $cache_key = self::buildSensorMineKey($sensor['mine_id'], $sensor['sensor_id']);
            $set_result = $this->amicum_rSet($this->sensor_cache, $cache_key, $sensor);
            if (!$set_result) {
                $errors[] = 'addSensor. Добавляемый сенсор в главный кеш: ';
                $errors[] = $sensor;
                throw new \Exception('addSensor. Не смог добавить сенсор в главный кеш сенсоров');
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'addSensor. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'addSensor. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    public function addSensorHash($sensor)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = 'addSensorHash. Начал выполнять метод';
        try {

            $mine_map_key = self::buildMineMapKeyHash($sensor['mine_id']);
            $set_result = $this->amicum_rSetHash($this->sensor_cache, $mine_map_key, $sensor['sensor_id'], $sensor);
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'addSensorHash. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'addSensorHash. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Название метода: multiGetParameterValue()
     * Назначение метода: Метод получения значения параметров сенсоров из кэша redis.
     * Можно получить данные по разному. Если нужно выбрать любой сенсор, или параметр,
     * или тип параметра, необходимо указать '*'.
     *
     * Входные обязательные параметры:
     *
     * @param $sensor_id - идентификатор сенсора. Если указать '*', то возвращает все сенсоры
     * @param $parameter_id - идентификатор параметра. Если указать '*', то возвращает все параметры
     * @param $parameter_type_id - идентификатор типа параметра. Если указать '*', то возвращает все типы параметра
     *
     * @return bool|array результат выполнения метода. Если данные есть, то массив данных, иначе false;
     *
     * Напрмиер:
     * 1. Получить сенсор id = 310 со всеми параметрами
     *    (new SensorCacheController())->getParameterValue('310', '*', '*')
     * 2. Получить сенсор id = 310 c параметров 83 и тип параметра любой
     *  (new SensorCacheController())->getParameterValue('310', '83', '*')
     * 3. Получить сенсор id = 310 c параметров 83 и тип параметра 2
     *  (new SensorCacheController())->getParameterValue('310', '83', '2')
     * 4. Получить все сенсоры c параметров 83 и тип параметра 2
     *  (new SensorCacheController())->getParameterValue('*', '83', '2')
     *
     *
     * @package backend\controllers\cachemanagers
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 24.05.2019 11:37
     * @since ver
     */
    public function multiGetParameterValue($sensor_id = '*', $parameter_id = '*', $parameter_type_id = '*', $take_not_reference = false)
    {
        if ($parameter_type_id == 1) {
            $redis_cache_key = $this->buildParameterKey($sensor_id, $parameter_id, $parameter_type_id);
            $keys = $this->sensor_parameter_handbook_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
            if ($keys) {
                return $this->amicum_mGet($this->sensor_parameter_handbook_cache, $keys);
            }
        } elseif ($parameter_type_id == 2 || $parameter_type_id == 3 || $parameter_type_id == 4) {
            $redis_cache_key = $this->buildParameterKey($sensor_id, $parameter_id, $parameter_type_id);
            $keys = $this->sensor_parameter_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
            if ($keys) {
                return $this->amicum_mGet($this->sensor_parameter_cache, $keys);
            }
        } elseif ($take_not_reference == true) {
            $redis_cache_key = $this->buildParameterKey($sensor_id, $parameter_id, '*');
            $keys = $this->sensor_parameter_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
            $result = $this->amicum_mGet($this->sensor_parameter_cache, $keys);
            return $result;
        } else {
            $redis_cache_key = $this->buildParameterKey($sensor_id, $parameter_id, 1);
            $keys = $this->sensor_parameter_handbook_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
            $result = $this->amicum_mGet($this->sensor_parameter_handbook_cache, $keys);
            unset($keys);

            $redis_cache_key = $this->buildParameterKey($sensor_id, $parameter_id, $parameter_type_id);
            $keys = $this->sensor_parameter_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
            $result = array_merge($result, $this->amicum_mGet($this->sensor_parameter_cache, $keys));
            return $result;
        }

        return false;
    }

    public function multiGetParameterValueHash($sensor_id = '*', $parameter_id = '*', $parameter_type_id = '*', $take_not_reference = false)
    {
        $result = [];
        if ($sensor_id == '*') {
            if ($parameter_type_id != 1) {
                $spv = SensorBasicController::getSensorParameterValue($sensor_id, $parameter_id, $parameter_type_id);
                if ($spv) {
                    $result = $spv;
                }
            }
            if ($parameter_type_id != 2 and $parameter_type_id != 3) {
                $sphv = SensorBasicController::getSensorParameterHandbookValue($sensor_id, $parameter_id);
                if ($sphv) {
                    $result = array_merge($result, $sphv);
                }
            }
        } else {
            $sensor_map_key = $this->buildSensorMapKeyHash($sensor_id);
            $sensor_parameter_values = $this->amicum_rGetMapHash($this->sensor_parameter_handbook_cache, $sensor_map_key);

            if (!$sensor_parameter_values) {
                return false;
            }

            if ($sensor_parameter_values and $parameter_id != '*' and $parameter_type_id != '*') {
                foreach ($sensor_parameter_values as $sensor_parameter_value) {
                    if ($sensor_parameter_value['parameter_id'] == $parameter_id and $sensor_parameter_value['parameter_type_id'] == $parameter_type_id) {
                        $result[] = $sensor_parameter_value;
                    }
                }
            } else if ($sensor_parameter_values and $parameter_id != '*') {
                foreach ($sensor_parameter_values as $sensor_parameter_value) {
                    if ($sensor_parameter_value['parameter_id'] == $parameter_id) {
                        $result[] = $sensor_parameter_value;
                    }
                }
            } else if ($sensor_parameter_values and $parameter_type_id != '*') {
                foreach ($sensor_parameter_values as $sensor_parameter_value) {
                    if ($sensor_parameter_value['parameter_type_id'] == $parameter_type_id) {
                        $result[] = $sensor_parameter_value;
                    }
                }
            } else {
                $result = $sensor_parameter_values;
            }
        }
        return $result;
    }

    /**
     * Название метода: getParameterValue()
     * Назначение метода: Метод получения значения конкретного парамтера сенсора кэша SensorParameter
     * Входные обязательные параметры:
     *
     * @param $sensor_id - идентификатор сенсора
     * @param $parameter_id - идентификатор параметра
     * @param $parameter_type_id - идентификатор типа параметра
     * @param array/boolean - массив данных либо false при отсутсвии данных
     *
     * @return mixed array/boolean - массив данных либо false при отсутсвии данных
     *
     * @see
     * @example
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 28.05.2019 16:42
     */
    public function getParameterValue($sensor_id, $parameter_id, $parameter_type_id)
    {
        $key = $this->buildParameterKey($sensor_id, $parameter_id, $parameter_type_id);
        if ($parameter_type_id == 1) {
            return $this->amicum_rGet($this->sensor_parameter_handbook_cache, $key);
        }

        return $this->amicum_rGet($this->sensor_parameter_cache, $key);
    }

    public function getParameterValueHash($sensor_id, $parameter_id, $parameter_type_id)
    {

        $key = $this->buildParameterKeyHash($parameter_id, $parameter_type_id);
        $sensor_map_key = $this->buildSensorMapKeyHash($sensor_id);
        return $this->amicum_rGetHash($this->sensor_parameter_handbook_cache, $sensor_map_key, $key);

    }

    public function getGatewayParameterByIp($ip)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();

        $gateway_coord = -1;
        $gateway_edge_id = -1;
        $gateway_place_id = -1;

        try {
            $gateway_id = (new ServiceCache())->getSensorIdByIp($ip);

            $gateway_place_id = $this->getParameterValue($gateway_id, 122, 1);
            if ($gateway_place_id === false) {
                throw new \Exception("getGatewayParameterByIp. В кэше не найдено место для шлюза в ламповой $gateway_id. Нет привязки строки подключения к сенсору");
            }
            $gateway_place_id = $gateway_place_id['value'];
            $warnings[] = "getGatewayParameterByIp. Плейс шлюза = $gateway_place_id";

            $gateway_coord = $this->getParameterValue($gateway_id, 83, 1);
            if ($gateway_coord === false) {
                throw new \Exception("getGatewayParameterByIp. В кэше не найдены координаты шлюза в ламповой $gateway_id");
            }
            $gateway_coord = $gateway_coord['value'];
            $warnings[] = "getGatewayParameterByIp. Координаты шлюза = $gateway_coord";

            $gateway_edge_id = $this->getParameterValue($gateway_id, 269, 1);
            if ($gateway_edge_id === false) {
                throw new \Exception("getGatewayParameterByIp. В кэше не найдена ветвь/выработка шлюза в ламповой $gateway_id");
            }
            $gateway_edge_id = $gateway_edge_id['value'];
            $warnings[] = "getGatewayParameterByIp. Ветвь/выработка шлюза = $gateway_edge_id";
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'getGatewayParameterByIp. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings,
            'gateway_coord' => $gateway_coord, 'gateway_edge_id' => $gateway_edge_id, 'gateway_place_id' => $gateway_place_id);
        return $result_main;
    }

    public function getGatewayParameterByIpHash($ip)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();

        $gateway_coord = -1;
        $gateway_edge_id = -1;
        $gateway_place_id = -1;

        try {
            $gateway_id = (new ServiceCache())->getSensorIdByIp($ip);
            if (!$gateway_id) {
                throw new \Exception(__FUNCTION__ . ". В кэше за данным IP нет шлюза");
            }

            $lamp_node_values = $this->multiGetParameterValueHash($gateway_id);
            $warnings[] = "getGatewayParameterByIpHash. Параметры сенсора";
//            $warnings[] = $lamp_node_values;

            if (!$lamp_node_values or empty($lamp_node_values)) {
                throw new \Exception(__FUNCTION__ . ". В кэше не найден шлюз в ламповой $gateway_id");
            }

            foreach ($lamp_node_values as $lamp_node_value) {
                $lamp_node_values_hand[$lamp_node_value['parameter_id']][$lamp_node_value['parameter_type_id']] = $lamp_node_value;
            }

            if (isset($lamp_node_values_hand[83][1])) {
                $gateway_coord = $lamp_node_values_hand[83][1]['value'];
            } else {
                throw new \Exception(__FUNCTION__ . ". В кэше не найдены координаты шлюза в ламповой $gateway_id");
            }
            $warnings[] = "getGatewayParameterByIpHash. Ветвь/выработка шлюза = $gateway_edge_id";

            if (isset($lamp_node_values_hand[269][1])) {
                $gateway_edge_id = $lamp_node_values_hand[269][1]['value'];
            } else {
                throw new \Exception(__FUNCTION__ . ". В кэше не найдена ветвь/выработка шлюза в ламповой $gateway_id");
            }
            $warnings[] = "getGatewayParameterByIpHash. Координаты шлюза = $gateway_coord";

            if (isset($lamp_node_values_hand[122][1])) {
                $gateway_place_id = $lamp_node_values_hand[122][1]['value'];
            } else {
                throw new \Exception(__FUNCTION__ . ". В кэше не найдено место для шлюза в ламповой $gateway_id. Нет привязки строки подключения к сенсору");
            }
            $warnings[] = "getGatewayParameterByIpHash. Плейс шлюза = $gateway_place_id";

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'getGatewayParameterByIpHash. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings,
            'gateway_coord' => $gateway_coord, 'gateway_edge_id' => $gateway_edge_id, 'gateway_place_id' => $gateway_place_id);
        return $result_main;
    }


    /**
     * @param $mine_id - идентификатор шахты. Если указать '*', то возвращает все шахты
     * @param $sensor_id - идентификатор сенсора. Если указать '*', то возвращает сенсоры. По умолчанию все сенсоры.
     *
     * @return mixed созданный ключ кэша в виде SensorParameter:sensor_id:parameter_id:parameter_type_id
     *
     * @package backend\controllers\cachemanagers
     * Название метода: buildParameterKey()
     * Назначение метода: Метод получения сенсора(ов) по шахте(по шахтам) из кэша redis.
     *
     * Входные обязательные параметры:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 24.05.2019 13:05
     */
    public function getSensorMine($mine_id, $sensor_id = '*')
    {
        $redis_cache_key = self::buildSensorMineKey($mine_id, $sensor_id);
        $keys = $this->sensor_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
        if ($keys) {
            return $this->amicum_mGet($this->sensor_cache, $keys);
        }
        return false;
    }

    public function getSensorMineHash($mine_id, $sensor_id = '*')
    {
        $mine_map_key = self::buildMineMapKeyHash($mine_id);
        $sensors_mine = $this->amicum_rGetMapHash($this->sensor_cache, $mine_map_key);

        if ($sensors_mine and $sensor_id != '*') {
            foreach ($sensors_mine as $sensor) {
                if ($sensor['sensor_id'] == $sensor_id) {
                    $result[] = $sensor;
                }
            }
        } else {
            $result = $sensors_mine;
        }

        if (!isset($result) or !$sensors_mine) {
            return false;
        }
        return $result;
    }

    /**
     * @param $sensor_id - идентификатор сенсора. Если указать '*', то возвращает сенсоры. По умолчанию все сенсоры.
     *
     * @return mixed созданный ключ кэша в виде SensorParameter:sensor_id:parameter_id:parameter_type_id
     *
     * @package backend\controllers\cachemanagers
     * Название метода: buildParameterKey()
     * Назначение метода: getSensorMineBySensor - Метод получения сенсора(ов) по сенсор айди из кэша redis.
     *
     * Входные обязательные параметры:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 24.05.2019 13:05
     */
    public function getSensorMineBySensor($sensor_id = '*')
    {
        $redis_cache_key = self::buildSensorMineKey('*', $sensor_id);
        $keys = $this->sensor_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
        if ($keys) {
            return $this->amicum_mGet($this->sensor_cache, $keys);
        }
        return false;
    }

    // getSensorMineBySensorHash - метод получения шахты сенсора по его ключу
    // $sensor_id - ключ сенсора
    public function getSensorMineBySensorHash($sensor_id = '*')
    {
        $sensor_map_key = self::buildSensorMapKeyHash($sensor_id);
        $sensor_parameter_handbook_value_lists = $this->amicum_rGetMapHash($this->sensor_parameter_handbook_cache, $sensor_map_key);
        if ($sensor_parameter_handbook_value_lists) {
            foreach ($sensor_parameter_handbook_value_lists as $sensor_parameter_value) {
                $sensor_parameter_hand[$sensor_parameter_value['parameter_type_id']][$sensor_parameter_value['parameter_id']] = $sensor_parameter_value;
            }
        }

        $parameter_type_id = -1;
        if (isset($sensor_parameter_hand[1][274])) {
            $object_id = $sensor_parameter_hand[1][274]['value'];
            $parameter_type_id = $this->isStaticSensorByObject($object_id);
        }
        if (isset($sensor_parameter_hand[$parameter_type_id][346])) {
            $mine_id = $sensor_parameter_hand[$parameter_type_id][346]['value'];
        } else {
            $mine_id = AMICUM_DEFAULT_MINE;
        }

        $mine_map_key = self::buildMineMapKeyHash($mine_id);
        $sensor_mine = $this->amicum_rGetHash($this->sensor_cache, $mine_map_key, $sensor_id);

        if (!$sensor_mine) {
            return false;
        }

        return $sensor_mine;
    }


    /**
     * Метод получения сенсора по сенсор айди и шахте из кэша redis.
     * @param $mine_id - идентификатор шахты
     * @param $sensor_id - идентификатор сенсора
     * @return bool
     */
    public function getSensorMineBySensorOne($mine_id, $sensor_id)
    {
        $key = self::buildSensorMineKey($mine_id, $sensor_id);
        return $this->amicum_rGet($this->sensor_cache, $key);
    }

    public function getSensorMineBySensorOneHash($mine_id, $sensor_id)
    {
        $mine_map_key = self::buildMineMapKeyHash($mine_id);
        $sensor_mine = $this->amicum_rGetHash($this->sensor_cache, $mine_map_key, $sensor_id);
        if (!$sensor_mine) {
            return false;
        }

        return $sensor_mine;
    }

    /**
     * @param $sensor_id - идентификатор сенсора. Если указать '*', то возвращает все сенсоры
     * @param $parameter_id - идентификатор параметра. Если указать '*', то возвращает все параметры
     * @param $parameter_type_id - идентификатор типа параметра
     *
     * @return string созданный ключ кэша в виде SensorParameter:sensor_id:parameter_id:parameter_type_id
     *
     * @package backend\controllers\cachemanagers
     * Название метода: buildParameterKey()
     * Назначение метода: Метод создания ключа кэша для списка сенсорово с их значениями (SensorParameter)
     *
     * Входные обязательные параметры:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 24.05.2019 13:05
     */
    public function buildParameterKey($sensor_id, $parameter_id, $parameter_type_id)
    {
        return self::$sensor_parameter_cache_key . ':' . $sensor_id . ':' . $parameter_id . ':' . $parameter_type_id;
    }

    public function buildParameterKeyHash($parameter_id, $parameter_type_id)
    {
        return $parameter_id . ':' . $parameter_type_id;
    }

    public static function buildSensorMineKey($mine_id, $sensor_id)
    {
        return self::$sensor_mine_cache_key . ':' . $mine_id . ':' . $sensor_id;
    }

    public static function buildMineMapKeyHash($mine_id)
    {
        return self::$mine_map_cache_key . ':' . $mine_id;
    }

    public function buildSensorMapKeyHash($sensor_id)
    {
        return self::$sensor_map_cache_key . ':' . $sensor_id;
    }


    /**
     * Название метода: delInSensorMine()
     * Назначение метода: Метод удаления сенсора из списка сенсоров по шахте из кэша
     * Метод ничего не возвращает.
     *
     * Входные обязательные параметры:
     *
     * @param $mine_id - идентификатор шахты. По умолчанию сенсор с таким идентификатор ищется во всех шахтах и
     *     удаляется
     * @param $sensor_id - идентифкатор конкретного сенсора
     *
     * @package backend\controllers\cachemanagers
     * @example $this->delInSensorMine(290, 310)
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 27.05.2019 15:32
     */
    public function delInSensorMine($sensor_id, $mine_id = '*')
    {
        $redis_cache_key = self::buildSensorMineKey($mine_id, $sensor_id);
        $keys = $this->sensor_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
        if ($keys) {
            $this->amicum_mDel($this->sensor_cache, $keys);
        }
    }

    public function delInSensorMineHash($sensor_id, $mine_id)
    {
        $mine_map_key = self::buildMineMapKeyHash($mine_id);
        $keys[$mine_map_key][] = $sensor_id;
        return $this->amicum_mDelHash($this->sensor_cache, $keys);
    }

    /**
     * Название метода: delInSensorMineByOne()
     * Назначение метода: Метод удаления сенсора из списка сенсоров по шахте из кэша
     * Метод ничего не возвращает.
     *
     * Входные обязательные параметры:
     *
     * @param $mine_id - идентификатор шахты. По умолчанию сенсор с таким идентификатор ищется во всех шахтах и
     *     удаляется
     * @param $sensor_id - идентифкатор конкретного сенсора
     *
     * @package backend\controllers\cachemanagers
     * @example $this->delInSensorMine(290, 310)
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 27.05.2019 15:32
     */
    public function delInSensorMineByOne($sensor_id, $mine_id)
    {
        $redis_cache_key = self::buildSensorMineKey($mine_id, $sensor_id);
        $this->amicum_rDel($this->sensor_cache, $redis_cache_key);
    }

    public function delInSensorMineByOneHash($sensor_id, $mine_id)
    {
        $mine_map_key = self::buildMineMapKeyHash($mine_id);
        $keys[$mine_map_key][] = $sensor_id;
        return $this->amicum_mDelHash($this->sensor_cache, $keys);
    }

    /**
     * Название метода: delParameterValue()
     * Назначение метода: Метод удаления значения параметров сенсоров(а)
     * Метод ничего не возвращает.
     *
     * Входные обязательные параметры:
     *
     * @param        $sensor_id - идентификатор сенсора. Если указать '*', то удаляет все сенсоры
     * @param string $parameter_id - идентификатор параметра.  Если указать '*', то удаляет все параметры
     * @param string $parameter_type_id - идентификато типа параметра. Если указать '*', то удаляет все типы параметров
     *
     * @return array возвращает массив результата выполнения метода. Если массив errors пустой, то значит параметр
     * был удален из кэша
     * @package backend\controllers\cachemanagers
     *
     * @example $this->delParameterValue(310, 83, 2)
     * @example $this->delParameterValue(310, *, 2)
     * @example $this->delParameterValue(*, 83, 2)
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 27.05.2019 15:40
     * @since ver
     */
    public function delParameterValue($sensor_id, $parameter_id = '*', $parameter_type_id = '*')
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        try {
            $redis_cache_key = $this->buildParameterKey($sensor_id, $parameter_id, $parameter_type_id);
            if ($redis_cache_key) {
                $warnings[] = "delParameterValue. Создал ключ сенсора на удаление";
            } else {
                throw new \Exception("delParameterValue. Не удалось создать ключ для удаления параметра сенсора");
            }

            $keys = $this->sensor_parameter_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
            $keys = array_merge($keys, $this->sensor_parameter_handbook_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1]);
            if ($keys) {
                $del_param_res = $this->amicum_mDel($this->sensor_parameter_cache, $keys);
                $del_param_res = $this->amicum_mDel($this->sensor_parameter_handbook_cache, $keys);
                if (!$del_param_res) {
                    $errors[] = $keys;
                    throw new \Exception("delParameterValue. Ошибка удаления параметра сенсора ");
                }
                $warnings[] = "delParameterValue. Параметры успешно удалены";
            } else {
                $warnings[] = "delParameterValue. Нет данных по указанному ключу в кэше. Нечего удалять";
            }

        } catch (Throwable $ex) {
            $errors[] = "delParameterValue. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        $warnings[] = "IsChangeParameterValue. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    public function delParameterValueHash($sensor_id, $parameter_id = '*', $parameter_type_id = '*')
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        try {
            $sensor_map_key = $this->buildSensorMapKeyHash($sensor_id);
            $redis_cache_key = $this->buildParameterKeyHash($parameter_id, $parameter_type_id);
            if ($redis_cache_key) {
                $warnings[] = "delParameterValueHash. Создал ключ сенсора на удаление";
            } else {
                throw new \Exception("delParameterValueHash. Не удалось создать ключ для удаления параметра сенсора");
            }

            $keys = $this->sensor_parameter_handbook_cache->hscan($sensor_map_key, 0, 'MATCH', $redis_cache_key, 'COUNT', '10000000');

            if ($keys and isset($keys[1]) and !empty($keys[1])) {
                $keys_to_del = [];
                for ($i = 0; $i < count($keys[1]); $i = $i + 2) {
                    $sensor_map_key = $this->buildSensorMapKeyHash($sensor_id);
                    $keys_to_del[$sensor_map_key][] = $keys[1][$i];
                }

                $del_param_res = $this->amicum_mDelHash($this->sensor_parameter_handbook_cache, $keys_to_del);
                if (!$del_param_res) {
                    $errors[] = $keys;
                    throw new \Exception("delParameterValueHash. Ошибка удаления параметра сенсора ");
                }
                $warnings[] = "delParameterValueHash. Параметры успешно удалены";
            }

        } catch (Throwable $ex) {
            $errors[] = "delParameterValueHash. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        $warnings[] = "IsChangeParameterValue. Закончил выполнять метод";

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * Название метода: getSensorsParametersValuesFromCache()
     * Метод получения значений параметров сенсора из кэша.
     * Сенсоры можно получить по нескольким параметрам, но только для конкретного сенсора.
     *
     * Алгоритм:
     *  1. Получаем все сенсоры из кэша списка сенсоров по шахте.
     *  2. Для каждого сенсора получаем значения указанных параметров из кэша по ключу SensorParameter.
     * (SensorParameter:310:122:1)
     *  3. Возвращаем результат.
     *
     * Входные параметры:
     *
     * @param $mine_id - идентификатор шахты. Чтобы получить для всех шахт, то нужно указать $mine_id = '*'
     * @param $sensor_id - идентификатор конкретного сеносора. Чтобы получить все, нужно указать $sensor_id = '*'
     * @param $parameters - параметры. Параметры должны указыватся в виде $parameters = "99:2, 99:1, 164:3". Чтобы
     *     получить все, нужно указать '*:*' Обязательно чтоб параметра был указан первым, а тип параметра вторым.
     *
     * Примеры вызова:
     * Из текущего класса: $this->getSensorsParametersValues(290, 310, "99:2, 99:1, 164:3"); - конкретный сенсор
     * Из текущего класса: $this->getSensorsParametersValues(290, *, "99:2, 99:1, 164:3"); - все сенсоры
     * Из текущего класса: $this->getSensorsParametersValues('*', '*', "99:2, 99:1, 164:3"); - все шахты, все сенсоры
     * Из текущего класса: $this->getSensorsParametersValues('*', '*'); - все шахты, все сенсоры, все парамтеры
     *
     * @return array - массив сенсоров
     * @author Озармехр Одилов
     * Created date: on 21.12.2018 17:42
     */
    public function getSensorsParametersValues($mine_id, $sensor_id = '*', $parameters = '*:*')
    {
//        ini_set('max_execution_time', 300);
        $errors = array();                                                                                              //создаем пустой массив ошибок
        $status = 1;
        $new_sensor_parameter_value_list = array();                                                                     //создаем пустой массив для значений
        if ($mine_id != '')                                                                                            // проверяем входные параметры
        {
            try {
                $index = 0;
                $sensors_mine = $this->getSensorMineHash($mine_id, $sensor_id);
                if ($sensors_mine) {
                    if ($parameters != '*:*') {
                        $parameters = str_replace(' ', '', $parameters);
                        $parameters = explode(',', $parameters);
                    }
                    foreach ($sensors_mine as $sensor)                                                                // для каждого сенсора который есть в
                    {
                        $keys = array();
                        $sensor_id = $sensor['sensor_id'];
                        if ($parameters == '*:*') {
                            $keys = $this->sensor_parameter_cache->scan(0, 'MATCH', self::$sensor_parameter_cache_key . ':' . $sensor_id . ':*:*', 'COUNT', '10000000')[1];
                            $handbook_keys = $this->sensor_parameter_handbook_cache->scan(0, 'MATCH', self::$sensor_parameter_cache_key . ':' . $sensor_id . ':*:*', 'COUNT', '10000000')[1];
                            $keys = array_merge($keys, $handbook_keys);
                            unset ($handbook_keys);
                        } else {
                            foreach ($parameters as $parameter) {
                                $keys[] = self::$sensor_parameter_cache_key . ':' . $sensor['sensor_id'] . ':' . $parameter;
                            }
                        }

                        $sensor_parameter_value_lists = $this->amicum_mGet($this->sensor_parameter_cache, $keys);
                        $sensor_parameter__handbook_value_lists = $this->amicum_mGet($this->sensor_parameter_handbook_cache, $keys);
                        $sensor_parameter_value_lists = array_merge($sensor_parameter_value_lists, $sensor_parameter__handbook_value_lists);
                        unset($sensor_parameter__handbook_value_lists);
                        if ($sensor_parameter_value_lists) {
                            foreach ($sensor_parameter_value_lists as $sensor_parameter_value)                         //для каждого параметра берем его значения по заданным воркерам
                            {
                                if ($sensor_parameter_value !== null) {
                                    $new_sensor_parameter_value_list[$index]['sensor_id'] = $sensor['sensor_id'];
                                    $new_sensor_parameter_value_list[$index]['object_id'] = $sensor['object_id'];
                                    $new_sensor_parameter_value_list[$index]['object_type_id'] = $sensor['object_type_id'];
                                    $new_sensor_parameter_value_list[$index]['sensor_parameter_id'] = $sensor_parameter_value['sensor_parameter_id'];
                                    $new_sensor_parameter_value_list[$index]['parameter_id'] = $sensor_parameter_value['parameter_id'];
                                    $new_sensor_parameter_value_list[$index]['parameter_type_id'] = $sensor_parameter_value['parameter_type_id'];
                                    $new_sensor_parameter_value_list[$index]['value'] = $sensor_parameter_value['value'];
                                    $new_sensor_parameter_value_list[$index]['date_time_work'] = $sensor_parameter_value['date_time'];
                                    $index++;
                                }
                            }
                        }
                    }
                } else {
                    $status = 0;
                    $errors[] = 'Нет данных в сенсоров по шахте. Кэш SensorMine пуст.';
                }
                unset($sensors_mine);
            } catch (\Exception $e)                                                                                    //обрабатываем исключение
            {
                $status = 0;
                $errors[] = $e->getMessage() . ' ' . $e->getLine();                                                     //записываем ошибку в массив еррорс
            }
        } else {
            $status = 0;
            $errors[] = "Не задан параметр шахты";                                                                                   //ключ от фронт энда не получен, потому формируем ошибку
        }
        return array('Items' => $new_sensor_parameter_value_list, 'errors' => $errors, 'status' => $status);
    }

    public function getSensorsParametersValuesHash($mine_id, $sensor_id = '*', $parameters = '*:*')
    {
//        ini_set('max_execution_time', 300);
        $errors = array();                                                                                              //создаем пустой массив ошибок
        $status = 1;
        $new_sensor_parameter_value_list = array();                                                                     //создаем пустой массив для значений
        if ($mine_id != '')                                                                                            // проверяем входные параметры
        {
            try {
                $index = 0;
                $mine_map_key = self::buildMineMapKeyHash($mine_id);
                $sensors_mine = $this->getSensorMineHash($mine_map_key, $sensor_id);
                if ($sensors_mine) {
                    if ($parameters != '*:*') {
                        $parameters = str_replace(' ', '', $parameters);
                        $parameters = explode(',', $parameters);
                    }
                    foreach ($sensors_mine as $sensor)                                                                // для каждого сенсора который есть в
                    {
                        $sensor_id = $sensor['sensor_id'];
                        $sensor_map_key = $this->buildSensorMapKeyHash($sensor_id);
                        if ($parameters == '*:*') {
                            $sensor_parameter_handbook_value_lists = $this->amicum_rGetMapHash($this->sensor_parameter_handbook_cache, $sensor_map_key);
                        } else {
                            $sensor_parameter_handbook_value_lists = $this->amicum_rGetHash($this->sensor_parameter_handbook_cache, $sensor_map_key, $parameters);
                        }

                        if ($sensor_parameter_handbook_value_lists) {
                            foreach ($sensor_parameter_handbook_value_lists as $sensor_parameter_value)                         //для каждого параметра берем его значения по заданным воркерам
                            {
                                if ($sensor_parameter_value !== null) {
                                    $new_sensor_parameter_value_list[$index]['sensor_id'] = $sensor['sensor_id'];
                                    $new_sensor_parameter_value_list[$index]['object_id'] = $sensor['object_id'];
                                    $new_sensor_parameter_value_list[$index]['object_type_id'] = $sensor['object_type_id'];
                                    $new_sensor_parameter_value_list[$index]['sensor_parameter_id'] = $sensor_parameter_value['sensor_parameter_id'];
                                    $new_sensor_parameter_value_list[$index]['parameter_id'] = $sensor_parameter_value['parameter_id'];
                                    $new_sensor_parameter_value_list[$index]['parameter_type_id'] = $sensor_parameter_value['parameter_type_id'];
                                    $new_sensor_parameter_value_list[$index]['value'] = $sensor_parameter_value['value'];
                                    $new_sensor_parameter_value_list[$index]['date_time_work'] = $sensor_parameter_value['date_time'];
                                    $index++;
                                }
                            }
                        }
                    }
                } else {
                    $status = 0;
                    $errors[] = 'getSensorsParametersValuesHash.Нет данных в сенсоров по шахте. Кэш SensorMine пуст.';
                }
                unset($sensors_mine);
            } catch (\Exception $e)                                                                                    //обрабатываем исключение
            {
                $status = 0;
                $errors[] = $e->getMessage() . ' ' . $e->getLine();                                                     //записываем ошибку в массив еррорс
            }
        } else {
            $status = 0;
            $errors[] = "getSensorsParametersValuesHash.Не задан параметр шахты";                                                                                   //ключ от фронт энда не получен, потому формируем ошибку
        }
        return array('Items' => $new_sensor_parameter_value_list, 'errors' => $errors, 'status' => $status);
    }


    /************************  Методы по получению сенсоров по сетевому идентификатору объектов  **********************/


    /**
     * Название метода: multiSetSensorParameterValue()
     * Назначение метода: Метод массовой вставки массива параметров и их значений в кеш
     *
     * Входные не обязательные параметры:
     *
     * @param int $sensor_id - идентификатор конкретного сенсора.
     * @param array $sensor_parameter_values - массив параметров и их значений
     *
     * @return  array массив данных
     * @package backend\controllers\cachemanagers
     *
     * @example (new SensorCacheController())->initSensorParameterValue();
     * @example (new SensorCacheController())->initSensorParameterValue(310);
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 28.05.2019 10:18
     */
    public function multiSetSensorParameterValue($sensor_id, $sensor_parameter_values)
    {

        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = 'multiSetSensorParameterValue. Начал выполнять метод';
        try {
            $warnings[] = 'multiSetSensorParameterValue. Нашел параметры в БД начинаю инициализировать кеш';
            foreach ($sensor_parameter_values as $sensor_parameter_value) {
                if ($sensor_parameter_value['parameter_type_id'] == 1) {
                    $sphv_key = $this->buildParameterKey($sensor_parameter_value['sensor_id'], $sensor_parameter_value['parameter_id'], $sensor_parameter_value['parameter_type_id']);
                    $warnings[] = "multiSetSensorParameterValue. Кеш параметров сенсора $sensor_id по ключу $sphv_key инициализирован";
                    $sensor_parameter_handbook_value_array[$sphv_key] = $sensor_parameter_value;
                } else {
                    $spv_key = $this->buildParameterKey($sensor_parameter_value['sensor_id'], $sensor_parameter_value['parameter_id'], $sensor_parameter_value['parameter_type_id']);
                    $warnings[] = "multiSetSensorParameterValue. Кеш параметров сенсора $sensor_id по ключу $spv_key инициализирован";
                    $sensor_parameter_value_array[$spv_key] = $sensor_parameter_value;
                }
            }
            if (isset($sensor_parameter_handbook_value_array)) {
                $this->amicum_mSet($this->sensor_parameter_handbook_cache, $sensor_parameter_handbook_value_array);
            }
            if (isset($sensor_parameter_value_array)) {
                $this->amicum_mSet($this->sensor_parameter_cache, $sensor_parameter_value_array);
            }
            unset($sensor_parameter_value_array, $sensor_parameter_handbook_value_array);

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'multiSetSensorParameterValue. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'multiSetSensorParameterValue. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    public function multiSetSensorParameterValueHash($sensor_parameter_values)
    {

        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = 'multiSetSensorParameterValueHash. Начал выполнять метод';
        try {
            $warnings[] = 'multiSetSensorParameterValueHash. Нашел параметры в БД начинаю инициализировать кеш';
            foreach ($sensor_parameter_values as $sensor_parameter_value) {
                $sphv_key = $this->buildParameterKeyHash($sensor_parameter_value['parameter_id'], $sensor_parameter_value['parameter_type_id']);
//                    $warnings[] = "multiSetSensorParameterValueHash. Кеш параметров сенсора $sensor_id по ключу $sphv_key инициализирован";
                $sensor_map_key = $this->buildSensorMapKeyHash($sensor_parameter_value['sensor_id']);
                $sensor_parameter_handbook_value_array[$sensor_map_key][$sphv_key] = $sensor_parameter_value;
            }
            if (isset($sensor_parameter_handbook_value_array)) {
                $this->amicum_mSetHash($this->sensor_parameter_handbook_cache, $sensor_parameter_handbook_value_array);
            }

            unset($sensor_paramter_value_array, $sensor_parameter_handbook_value_array);

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'multiSetSensorParameterValueHash. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'multiSetSensorParameterValueHash. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // buildStructureSensorParametersValue - Метод создания структуры значения параметра сенсора в кеше
    // сделан, что бы легче было создавать массив для массовой вставки
    // разработал: Якимов М.Н.
    public static function buildStructureSensorParametersValue($sensor_id, $sensor_parameter_id, $parameter_id, $parameter_type_id, $date_time, $parameter_value, $status_id)
    {
        $sensor_parameter_value_to_cache['sensor_id'] = $sensor_id;
        $sensor_parameter_value_to_cache['sensor_parameter_id'] = $sensor_parameter_id;
        $sensor_parameter_value_to_cache['parameter_id'] = $parameter_id;
        $sensor_parameter_value_to_cache['parameter_type_id'] = $parameter_type_id;
        $sensor_parameter_value_to_cache['date_time'] = $date_time;
        $sensor_parameter_value_to_cache['value'] = $parameter_value;
        $sensor_parameter_value_to_cache['status_id'] = $status_id;
        return $sensor_parameter_value_to_cache;
    }

    // buildStructureSensor - Метод создания структуры сенсора в кеше
    // сделан, что бы легче было создавать массив для массовой вставки
    // разработал: Якимов М.Н.
    public static function buildStructureSensor($sensor_id, $sensor_title, $object_id, $object_title, $object_type_id, $mine_id)
    {
        //ВАЖНО!!!!! при изменении структуры в части количества поравить getSensorMine - там проверка на количество элементов в это объетк
        $sensor_to_cache['sensor_id'] = $sensor_id;
        $sensor_to_cache['sensor_title'] = $sensor_title;
        $sensor_to_cache['object_id'] = $object_id;
        $sensor_to_cache['object_title'] = $object_title;
        $sensor_to_cache['object_type_id'] = $object_type_id;
        $sensor_to_cache['mine_id'] = $mine_id;
        return $sensor_to_cache;
    }
    // buildStructureTags - Метод создания структуры привязки тега к конкретному параметру в кеше
    // сделан, что бы легче было создавать массив для массовой вставки
    // разработал: Якимов М.Н.
    public static function buildStructureTags($sensor_parameter_id_source, $sensor_id, $sensor_parameter_id, $parameter_id)
    {
        //ВАЖНО!!!!! при изменении структуры в части количества поравить getSensorMine - там проверка на количество элементов в это объетк
        $sensor_parameter_sensor_to_cache['sensor_id'] = $sensor_id;
        $sensor_parameter_sensor_to_cache['sensor_parameter_id'] = $sensor_parameter_id;
        $sensor_parameter_sensor_to_cache['sensor_parameter_id_source'] = $sensor_parameter_id_source;
        $sensor_parameter_sensor_to_cache['parameter_id'] = $parameter_id;
        return $sensor_parameter_sensor_to_cache;
    }

    // getSenParSenTag - получить из кеша привязанные к тегу sensor_parameter_id
    public function getSenParSenTag($tags_id, $sensor_parameter_id)
    {
        $sensor_parameter_sensor_cache_key = $this->buildSenParSenTagKey($tags_id, $sensor_parameter_id);
        return $this->amicum_rGet($this->sensor_cache, $sensor_parameter_sensor_cache_key);
    }

    // multiGetSenParSenTag - получение из кеша всех привязок к конктретным параметрам, что там есть
    public function multiGetSenParSenTag($tags_id = "*", $sensor_parameter_id = "*")
    {
        $network_cache_key = $this->buildSenParSenTagKey($tags_id, $sensor_parameter_id);
        $keys = $this->sensor_cache->scan(0, 'MATCH', $network_cache_key, 'COUNT', '10000000')[1];
        if ($keys) {
            return $this->amicum_mGet($this->sensor_cache, $keys);
        }
        return false;
    }

    // buildSenParSenTagKey - создание ключа привязки к сенсору параметра
    public function buildSenParSenTagKey($tags_id, $sensor_parameter_id = "*")
    {
        return self::$sen_par_sen_key . ':' . $tags_id . ':' . $sensor_parameter_id;
    }

    // delSenParSenTag - удаление кеша sensorParameterSensor
    public function delSenParSenTag($tags_id = "*", $sensor_parameter_id = "*")
    {
        $sensor_parameter_sensor_cache_key = $this->buildSenParSenTagKey($tags_id, $sensor_parameter_id);
        $keys = $this->sensor_cache->scan(0, 'MATCH', $sensor_parameter_sensor_cache_key, 'COUNT', '10000000')[1];
        if ($keys) {
            return $this->amicum_mDel($this->sensor_cache, $keys);
        }
        return false;
    }

    // initSensorParameterSensor - метод инициализации привязки парамтеров сенсора к тегам OPC
    public function initSensorParameterSensor($sensor_id = -1, $sql = '')
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = 'initSensorParameterSensor. Начал выполнять метод';
        try {
            $sql_filter = '';
            if ($sensor_id !== -1) {
                $sql_filter .= "sensor_id = $sensor_id";
            }
            if ($sql !== '') {
                $sql_filter = $sql;
            }
            $sensor_parameter_sensors = (new Query())
                ->select(['sensor_id', 'sensor_parameter_id', 'sensor_parameter_id_source', 'parameter_id'])
                ->from('view_GetSensorParameterSensorMain')
                ->where($sql_filter)
                ->all();

            if ($sensor_parameter_sensors) {
                $result = $sensor_parameter_sensors;
                $warnings[] = 'initSensorParameterSensor. Нашел параметры в БД начинаю инициализировать кеш';
                $sensor_id_current = -1;
                foreach ($sensor_parameter_sensors as $sensor_parameter_sensor) {
                    $sensor_id = $sensor_parameter_sensor['sensor_id'];

                    $spv_key = $this->buildSenParSenTagKey($sensor_parameter_sensor['sensor_parameter_id_source'], $sensor_parameter_sensor['sensor_parameter_id']);
                    //$warnings[] = "initSensorParameterSensor. Кеш параметров сенсора $sensor_id по ключу $spv_key инициализирован";
                    $sensor_parameter_sensor_array[$spv_key] = $sensor_parameter_sensor;
                }
                $this->amicum_mSet($this->sensor_cache, $sensor_parameter_sensor_array);

                unset($sensor_parameter_sensor_array, $sensor_parameter_sensors);
            } else {
                $warnings[] = 'initSensorParameterSensor. Список привязок сенсора пуст. Кеш не инициализирован';
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'initSensorParameterSensor. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'initSensorParameterSensor. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // setSenParSenTag - записать привязку сенсора в кеш
    public function setSenParSenTag($tags_id, $sensor_parameter_id, $sensor_id, $parameter_id)
    {
        $sensor_parameter_sensor_cache_key = $this->buildSenParSenTagKey($tags_id, $sensor_parameter_id);
        $sensor_parameter_sensor = self::buildStructureTags($tags_id, $sensor_id, $sensor_parameter_id, $parameter_id);
        return $this->amicum_rSet($this->sensor_cache, $sensor_parameter_sensor_cache_key, $sensor_parameter_sensor);                                        // добавим новый сенсор для сетевоого идентифкатора
    }

    // multiSetSenParSenTag - разовая запись значений привязки сенсора в кеш
    public function multiSetSenParSenTag($sensor_parameter_sensors)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = 'multiSetSenParSenTag. Начал выполнять метод';
        try {
            foreach ($sensor_parameter_sensors as $sensor_parameter_sensor) {
                $this->delSenParSenTag('*', $sensor_parameter_sensor['sensor_parameter_id']);
                $sps_key = $this->buildSenParSenTagKey($sensor_parameter_sensor['sensor_parameter_id_source'], $sensor_parameter_sensor['sensor_parameter_id']);
                $warnings[] = "multiSetSenParSenTag. Кеш привязки параметров сенсора по ключу $sps_key инициализирован. Старый кеш удален. Ключ новый создан";
                $sensor_parameter_sensor_array[$sps_key] = $sensor_parameter_sensor;
            }
            if (isset($sensor_parameter_sensor_array)) {
                $this->amicum_mSet($this->sensor_cache, $sensor_parameter_sensor_array);
            }
            unset($sensor_parameter_sensor_array, $sensor_parameter_sensors);

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'multiSetSenParSenTag. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'multiSetSenParSenTag. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // amicum_mGet - метод получения данных с редис за один раз методами редиса
    public function amicum_mGet($cache, $keys)
    {
        $mgets = $cache->executeCommand('mget', $keys);
        if ($mgets) {
            foreach ($mgets as $mget) {
                $result[] = unserialize($mget)[0];
            }
            return $result;
        }
        return false;
    }

    public function amicum_rGetMapHash($cache, $key)
    {
        $key1[] = $key;
        $mgets = $cache->executeCommand('hvals', $key1);
//        var_dump($mgets);
        if ($mgets) {
            foreach ($mgets as $mget) {
                $result[] = unserialize($mget);
            }
            return $result;
        }
        return false;
    }

    /**
     * amicum_mSet - Метод вставки значений в кэш командами редиса. Аналогичен методу set(), только ключи не преобразуются в какой-либо формат,
     * они добавляюся как есть
     */
    public function amicum_mSet($cache, $items, $dependency = null)
    {
        $data = [];
        foreach ($items as $key => $value) {
            $value = serialize([$value, $dependency]);
            $data[] = $key;
            $data[] = $value;
        }
        $msets = $cache->executeCommand('mset', $data);

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $cache->port, 'mset', $data);
        }

        return $msets;
    }

    public function amicum_mSetHash($cache, $items)
    {
        $msets = 0;
        foreach ($items as $map_key => $values) {
            $data[] = $map_key;
            foreach ($values as $key => $value) {
                $data[] = $key;
                $data[] = serialize($value);
            }

            $cache->executeCommand('hset', $data);

            if (REDIS_REPLICA_MODE === true) {
                $this->amicum_repRedisHash(REDIS_REPLICA_HOSTNAME, $cache->port, 'hset', $data);
            }
            $data = [];
        }

        return $msets;
    }

    /**
     * amicum_rSet - Метод вставки значений в кэш командами редиса. Аналогичен методу set(), только ключи не преобразуются в какой-либо формат,
     * они добавляюся как есть
     */
    public function amicum_rSet($cache, $key, $value, $dependency = null)
    {
        $value = serialize([$value, $dependency]);
        $data[] = $key;
        $data[] = $value;

        $msets = $cache->executeCommand('set', $data);

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $cache->port, 'set', $data);
        }

        return $msets;
    }

    public function amicum_rSetHash($cache, $map_key, $key, $value)
    {

        $data[] = $map_key;
        $data[] = $key;
        $data[] = serialize($value);

        $msets = $cache->executeCommand('hset', $data);

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedisHash(REDIS_REPLICA_HOSTNAME, $cache->port, 'hset', $data);
        }


        return $msets;
    }

    /**
     * amicum_rSetDebug - Тестовый метод для проверки укладованыя значении срузу в двух редисах
     */
    public function amicum_rSetDebug($cache, $key, $value, $dependency = null)
    {
        $errors = array();
        $Items = array();
        $status = 1;
        $warnings = array();

        try {
            $value = serialize([$value, $dependency]);
            $data[] = $key;
            $data[] = $value;
            $warnings[] = 'Укладываю значение в редис ' . $cache->hostname . ':' . $cache->port;
            $msets = $cache->executeCommand('set', $data);
            if ($msets) {
                $warnings[] = 'Успешно уложил значение в редис ' . $cache->hostname . ':' . $cache->port;
                $warnings[] = 'Результат укладки в редис:';
                $warnings[] = $msets;
            }

            if (REDIS_REPLICA_MODE === true) {
                $warnings[] = 'Начинаю вставку в удаленный редис' . REDIS_REPLICA_HOSTNAME . ':' . $cache->port;
                $warnings[] = 'Результат укладки в удаленный редис:';
                $result_set_to_remote_redis = $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $cache->port, 'set', $data);
                $warnings[] = $result_set_to_remote_redis;
            } else {
                $warnings[] = 'Вставка в удаленый редис не требуется, так как REDIS_REPLICA_MODE === ' . REDIS_REPLICA_MODE;
            }
        } catch (Exception $exception) {
            $status = 0;
            $errors[] = 'amicum_rSetDebug. Исключение:';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        $result = array('Items' => $Items, 'status' => $status, 'warnings' => $warnings, 'error' => $errors);
        return $result;
    }

    public function amicum_rSetDebugHash($cache, $map, $key, $value, $dependency = null)
    {
        $errors = array();
        $Items = array();
        $status = 1;
        $warnings = array();

        try {
            $data[] = $map;
            $data[] = $key;
            $data[] = serialize($value);
            $warnings[] = 'amicum_rSetDebugHash. Укладываю значение в редис ' . $cache->hostname . ':' . $cache->port;
            $msets = $cache->executeCommand('hset', $data);
            if ($msets) {
                $warnings[] = 'amicum_rSetDebugHash. Успешно уложил значение в редис ' . $cache->hostname . ':' . $cache->port;
                $warnings[] = 'amicum_rSetDebugHash. Результат укладки в редис:';
                $warnings[] = $msets;
            }

            if (REDIS_REPLICA_MODE === true) {
                $warnings[] = 'amicum_rSetDebugHash. Начинаю вставку в удаленный редис' . REDIS_REPLICA_HOSTNAME . ':' . $cache->port;
                $warnings[] = 'amicum_rSetDebugHash. Результат укладки в удаленный редис:';
                $result_set_to_remote_redis = $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $cache->port, 'hset', $data);
                $warnings[] = $result_set_to_remote_redis;
            } else {
                $warnings[] = 'Вставка в удаленый редис не требуется, так как REDIS_REPLICA_MODE === ' . REDIS_REPLICA_MODE;
            }
        } catch (Exception $exception) {
            $status = 0;
            $errors[] = 'amicum_rSetDebug. Исключение:';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        $result = array('Items' => $Items, 'status' => $status, 'warnings' => $warnings, 'error' => $errors);
        return $result;
    }

    public function amicum_repRedis($hostname, $port, $command_redis, $data)
    {
        $errors = array();
        $warnings = array();
        $status = 1;
        $result = array();

        $warnings[] = 'amicum_repRedis. Начало метода';
        try {
            $redis_replica = new yii\redis\Connection();
            $redis_replica->hostname = $hostname;
            $redis_replica->port = $port;
            $result = $redis_replica->executeCommand($command_redis, $data);
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'amicum_repRedis. Исключение:';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'amicum_repRedis. Конец метода';
        return array('Items' => $result, 'warnings' => $warnings, 'errors' => $errors, 'status' => $status);
    }

    public function amicum_repRedisHash($hostname, $port, $command_redis, $data)
    {
        $errors = array();
        $warnings = array();
        $status = 1;
        $result = array();

        $warnings[] = 'amicum_repRedisHash. Начало метода';
        try {
            $redis_replica = new yii\redis\Connection();
            $redis_replica->hostname = $hostname;
            $redis_replica->port = $port;
            $result = $redis_replica->executeCommand($command_redis, $data);
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'amicum_repRedisHash. Исключение:';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'amicum_repRedisHash. Конец метода';
        return array('Items' => $result, 'warnings' => $warnings, 'errors' => $errors, 'status' => $status);
    }

    /**
     * Метод получение значения из кэша на прямую из редис
     */
    public function amicum_rGet($cache, $key)
    {
        $key1[] = $key;
        $value = $cache->executeCommand('get', $key1);

        if ($value) {
            $value = unserialize($value)[0];
            return $value;
        }
        return false;
    }

    /**
     * Метод получение значения из кэша на прямую из редис
     */
    public function amicum_rGetHash($cache, $map, $key)
    {
        $key1[] = $map;
        $key1[] = $key;
        $value = $cache->executeCommand('hget', $key1);

        if ($value) {
            $value = unserialize($value);
            return $value;
        }
        return false;
    }

    /**
     * Метод удаления по указанным ключам
     */
    public function amicum_mDel($cache, $keys)
    {
        //Todo: сделать проверку в будущем на возвращаемые из redis
        if ($keys) {
            foreach ($keys as $key) {
                $key1 = array();
                $key1[] = $key;
                $value = $cache->executeCommand('del', $key1);

                if (REDIS_REPLICA_MODE === true) {
                    $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $cache->port, 'del', $key1);
                }
            }
            return true;
        }
        return false;
    }

    public function amicum_mDelHash($cache, $map_keys)
    {
        //Todo: сделать проверку в будущем на возвращаемые из redis
        if ($map_keys) {
            foreach ($map_keys as $key_idx => $map_key) {
                $key1[] = $key_idx;

                foreach ($map_key as $key) {
                    $key1[] = $key;
                }
                $value = $cache->executeCommand('hdel', $key1);

                if (REDIS_REPLICA_MODE === true) {
                    $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $cache->port, 'hdel', $key1);
                }
                $key1 = [];
            }
            return true;
        }
        return false;
    }

    /**
     * Метод удаления по указанному ключу
     */
    public function amicum_rDel($cache, $key)
    {
        $key1[] = $key;
        $value = $cache->executeCommand('del', $key1);

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $cache->port, 'del', $key1);
        }
    }

    public function amicum_rDelHash($cache, $map, $key)
    {
        $key1[] = $map;
        $key1[] = $key;
        $value = $cache->executeCommand('hdel', $key1);

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $cache->port, 'hdel', $key1);
        }
    }

    // amicum_flushall - метод очистки кешей сенсоров
    public function amicum_flushall()
    {
        $this->sensor_cache->executeCommand('flushall');
        $this->sensor_parameter_cache->executeCommand('flushall');
        $this->sensor_parameter_handbook_cache->executeCommand('flushall');

        if (REDIS_REPLICA_MODE === true) {
            // главный кеш сенсоров
            $redis_replica = new yii\redis\Connection();
            $redis_replica->hostname = REDIS_REPLICA_HOSTNAME;
            $redis_replica->port = $this->sensor_cache->port;
            $redis_replica->executeCommand('flushall');

            // кеш параметров сенсоров
            $redis_replica_value = new yii\redis\Connection();
            $redis_replica_value->hostname = REDIS_REPLICA_HOSTNAME;
            $redis_replica_value->port = $this->sensor_parameter_cache->port;
            $redis_replica_value->executeCommand('flushall');

            // кеш параметров справочных сенсоров
            $redis_replica_handbook_value = new yii\redis\Connection();
            $redis_replica_handbook_value->hostname = REDIS_REPLICA_HOSTNAME;
            $redis_replica_handbook_value->port = $this->sensor_parameter_handbook_cache->port;
            $redis_replica_handbook_value->executeCommand('flushall');
        }
    }

    /**
     * Название метода: multiSetSensorParameterHandbookValues() - Метод массового заполенния кэша списка справночных значений параметров сенсоров(а)
     * Назначение метода: Метод массового заполенния кэша списка справночных значений параметров сенсоров(а) -
     *
     * Входные не обязательные параметры:
     * @return  array массив данных
     * @package backend\controllers\cachemanagers
     * Created date: on 28.05.2019 10:30
     */
    public function multiSetSensorParameterHandbookValues($sensor_parameter_handbook_values)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени

        try {
            if ($sensor_parameter_handbook_values) {
                foreach ($sensor_parameter_handbook_values as $sensor_parameter_handbook_value) {
                    $spv_key = $this->buildParameterKey($sensor_parameter_handbook_value['sensor_id'], $sensor_parameter_handbook_value['parameter_id'], $sensor_parameter_handbook_value['parameter_type_id']);
                    $sensor_parameter_handbook_value_array[$spv_key] = $sensor_parameter_handbook_value;
                }
                $this->amicum_mSet($this->sensor_parameter_handbook_cache, $sensor_parameter_handbook_value_array);
            } else {
                $warnings[] = 'multiSetSensorParameterHandbookValues. Список справочных параметров в БД пуст';
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'multiSetSensorParameterHandbookValues. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'multiSetSensorParameterHandbookValues. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    public function multiSetSensorParameterHandbookValuesHash($sensor_parameter_handbook_values)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени

        try {
            if ($sensor_parameter_handbook_values) {
                foreach ($sensor_parameter_handbook_values as $sensor_parameter_handbook_value) {
                    $spv_key = $this->buildParameterKeyHash($sensor_parameter_handbook_value['parameter_id'], $sensor_parameter_handbook_value['parameter_type_id']);
                    $sensor_map_key = $this->buildSensorMapKeyHash($sensor_parameter_handbook_value['sensor_id']);
                    $sensor_parameter_handbook_value_array[$sensor_map_key][$spv_key] = $sensor_parameter_handbook_value;
                }
                $this->amicum_mSetHash($this->sensor_parameter_handbook_cache, $sensor_parameter_handbook_value_array);
            } else {
                $warnings[] = 'multiSetSensorParameterHandbookValuesHash. Список справочных параметров в БД пуст';
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'multiSetSensorParameterHandbookValuesHash. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'multiSetSensorParameterHandbookValuesHash. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Название метода: multiSetSensorParameterValues() Метод массового заполенния кэша списка измеренных значений параметров сенсоров(а)
     * Назначение метода: Метод массового заполенния кэша списка измеренных значений параметров сенсоров(а) -
     *
     * Входные не обязательные параметры:
     * @return  array массив данных
     * @package backend\controllers\cachemanagers
     * Created date: on 28.05.2019 10:30
     */
    public function multiSetSensorParameterValues($sensor_parameter_values)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени

        try {
            if ($sensor_parameter_values) {
                foreach ($sensor_parameter_values as $sensor_parameter_value) {
                    $spv_key = $this->buildParameterKey($sensor_parameter_value['sensor_id'], $sensor_parameter_value['parameter_id'], $sensor_parameter_value['parameter_type_id']);
                    $sensor_parameter_value_array[$spv_key] = $sensor_parameter_value;
                }
                $this->amicum_mSet($this->sensor_parameter_cache, $sensor_parameter_value_array);
            } else {
                $warnings[] = 'multiSetSensorParameterValues. Список справочных параметров в БД пуст';
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'multiSetSensorParameterValues. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'multiSetSensorParameterValues. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    public function multiSetSensorParameterValuesHash($sensor_parameter_values)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени

        try {
            if ($sensor_parameter_values) {
                foreach ($sensor_parameter_values as $sensor_parameter_value) {
                    $spv_key = $this->buildParameterKeyHash($sensor_parameter_value['parameter_id'], $sensor_parameter_value['parameter_type_id']);
                    $sensor_map_key = $this->buildSensorMapKeyHash($sensor_parameter_value['sensor_id']);
                    $sensor_parameter_value_array[$sensor_map_key][$spv_key] = $sensor_parameter_value;
                }
                $this->amicum_mSetHash($this->sensor_parameter_cache, $sensor_parameter_value_array);
            } else {
                $warnings[] = 'multiSetSensorParameterValuesHash. Список справочных параметров в БД пуст';
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'multiSetSensorParameterValuesHash. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'multiSetSensorParameterValuesHash. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }
}
