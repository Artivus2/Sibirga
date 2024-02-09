<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\cachemanagers;

use backend\controllers\WorkerBasicController;
use backend\controllers\WorkerMainController;
use Exception;
use Throwable;
use Yii;
use yii\db\Query;

/**
 * Класс по работе с кэшем работников. Логику по работе с БД включать в этот метод нельзя.
 * Этот класс ИСКЛЮЧИТЕЛЬНО по работе с кэшем работников.
 * Другие лишние методы не связанные с кэшом нельзя добавить
 * Class WorkerCacheController
 * @package backend\controllers\cachemanagers
 */
class WorkerCacheController
{

    // buildStructureWorker                 - Метод создания структуры работника в кеше
    // buildStructureWorkerParametersValue  - Метод создания структуры значения параметра работника в кеше
    // setSensorWorker                      - метод привязки/редактирования привязки лампы к работнику.
    // delSensorWorker                      - метод удаление привязки сенсора к работнику по сенсору
    // delInWorkerMine                      - Метод удаления работника из списка работников по шахте из кэша
    // addWorker                            - Метод добавлния работника в кэш(БЕЗ ПАРАМЕТРОВ)
    // getParameterValue                    - Метод получения значения конкретного парамтера сенсора кэша WorkerParameter
    // multiGetParameterValue               - Метод получения значения параметров воркеров из кэша redis (групповое получение).
    // multiGetSensorWorker                 - метод получения привязанного сенсора к работнику по ИД сенсору или по ИД работнику
    // getWorkerMineByWorker                - Метод получения сенсора(ов) по сенсор айди из кэша redis.
    // getWorkerMineByWorkerOne             - Получение информации о воркере из кэша WorkerMine
    // getSensorWorker                      - метод получения привязанного сенсора к работнику по ИД сенсору или по ИД работнику
    // getWorkerMine                        - Метод получения данные работника(ов) по шахте(по шахтам) из кэша redis.
    // removeAll                            - Метод полного удаления кэша работников. Очищает все кэши связанные с работниками
    // amicum_flushall                      - метод очистки кеша работников
    // setWorkingHours                      - метод сохранение времени регистрации лампы
    // getWorkingHours                      - метод получение времени регистрации лампы
    // cleanWorkingHours                    - метод очистки времени регистрации лампы

    // amicum_mGet                          - метод получения данных с редис за один раз методами редиса
    // amicum_mSet                          - Метод вставки значений в кэш командами редиса.

    // buildWorkerMineKey                   - метод создания ключа для списка работников по шахте в кэше WorkerMine
    // buildParameterKey                    - Метод создания ключа кэша для списка параметров работников с их значениями (WorkerParameter)
    // buildSensorWorkerKey                 - Метод получения привязок работников к сенсорам
    // buildWorkerCheckInKey()              - метод создания ключа кэша зачекиненых работников по шахте


    public $redis_cache;
    public static $worker_mine_cache_key = 'WoMi';         //Якимов М.Н. переделал так - т.к. кеш всех работников - очень плохая идея - не войдут туда сведения о всех и сложно будет поддерживать в актуальном состоянии
    public static $worker_check_in_cache_key = 'WoMi';
    public static $worker_parameter_cache_key = 'WoPa';
    public static $worker_working_hours_cache_key = 'WoHo';

    public static $mine_map_cache_key = 'WMiMap';
    public static $worker_map_cache_key = 'WoMap';
    public static $sensor_worker_cache_key = 'SeWo';

    public function __construct()
    {
        $this->redis_cache = Yii::$app->redis_worker;
    }

    /**
     * Название метода: runInit()
     * Назначение метода: Метод полной инициализации кэша работников по шахте и со всеми значениями параметров
     * @param int $mine_id - идентификатор шахты
     * @return array $result - массив рузельтата выполнения метода. Сами данные не возвращает
     *
     * @package backend\controllers\cachemanagers
     * порядок очень важен!!!!!
     * Метод инициализирует следующие кэши:
     * 1. WorkerMine
     * 2. WorkerParameter значения value
     * 3. WorkerParameter значения handbook
     * Входные обязательные параметры:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 31.05.2019 13:19
     * @since ver
     */
    public function runInit($mine_id)
    {
//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', '5000M');
        $errors = array();
        $status = array();
        if ($mine_id != "") {
            if ($this->initWorkerParameterValue()) {                                                                     // инициализируем кэш списка работников со значениями value
                $status['initWorkerParameterValue'] = true;
            } else {
                $status['initWorkerParameterValue'] = false;
            }

            if ($this->initWorkerParameterHandbookValue()) {                                                             // инициализируем кэш списка работников со значениями handbook
                $status['initWorkerParameterHandbookValue'] = true;
            } else {
                $status['initWorkerParameterHandbookValue'] = false;
            }

            $status['initWorkerMine'] = $this->initWorkerMine($mine_id);                                                // инициализируем кэш списка работников по шахте

            if ($this->initSensorWorker()) {
                $status['initSensorWorker'] = true;
            } else {
                $status['initSensorWorker'] = false;
            }
        } else $errors[] = "Идентификатор шахты не передан. Ошибка инициализации кэша работников";
        $result = array('errors' => $errors, 'status' => $status);
        unset($status);
        return $result;
    }

    public function runInitHash($mine_id)
    {
//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', '5000M');
        $errors = array();
        $status = array();
        if ($mine_id != "") {

            if ($this->initWorkerParameterValueHash()) {                                                                     // инициализируем кэш списка работников со значениями value
                $status['initWorkerParameterValueHash'] = true;
            } else {
                $status['initWorkerParameterValueHash'] = false;
            }

            if ($this->initWorkerParameterHandbookValueHash()) {                                                             // инициализируем кэш списка работников со значениями handbook
                $status['initWorkerParameterHandbookValueHash'] = true;
            } else {
                $status['initWorkerParameterHandbookValueHash'] = false;
            }

            $status['initWorkerMineHash'] = $this->initWorkerMineHash($mine_id);                                                // инициализируем кэш списка работников по шахте

            if ($this->initSensorWorker()) {
                $status['initSensorWorker'] = true;
            } else {
                $status['initSensorWorker'] = false;
            }
        } else $errors[] = "Идентификатор шахты не передан. Ошибка инициализации кэша работников";
        $result = array('errors' => $errors, 'status' => $status);
        unset($status);
        return $result;
    }

    /**
     * Название метода: delInWorkerMine() -Метод удаления работника из списка работников по шахте из кэша
     * Назначение метода: Метод удаления работника из списка работников по шахте из кэша
     * Метод ничего не возвращает.
     *
     * Входные обязательные параметры:
     *
     * @param $mine_id - идентификатор шахты. По умолчанию сенсор с таким идентификатор ищется во всех шахтах и
     *     удаляется
     * @param $worker_id - идентифкатор конкретного сенсора
     *
     * @package backend\controllers\cachemanagers
     * @example $this->delInSensorMine(290, 310)
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 27.05.2019 15:32
     */
    public function delInWorkerMine($worker_id, $mine_id = '*')
    {
        $redis_cache_key = self::buildWorkerMineKey($mine_id, $worker_id);
        $keys = $this->redis_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
        if ($keys) {
            $this->amicum_mDel($keys);
        }
    }

    public function delInWorkerMineHash($worker_id, $mine_id)
    {
        $mine_map_key = self::buildMineMapKeyHash($mine_id);
        $keys[$mine_map_key][] = $worker_id;
        $this->amicum_mDelHash($keys);
        return true;
    }

    /**
     * Название метода: initWorkerMine()
     * Назначение метода: метод инициализации кэша списка работников по шахте(WorkerMine)
     * беруться только зачекиненые работники
     * Входные обязательные параметры:
     * @param int $mine_id - идентификатор шахты
     *
     * Входные необязательные параметры
     * @param int $worker_id - идентификатор работника. Если указать конкретный, то только данные одного работника
     * добавляет в кэш
     *
     * @return array|bool - возвращает массив данных если есть, иначе возвращает false
     *
     * @package backend\controllers\cachemanagers
     *
     * @example
     *
     * @author Якимов М.Н.
     * Created date: on 31.05.2019 10:03
     */
    public function initWorkerMine($mine_id, $worker_id = -1)
    {
        $sql_filter = "mine_id = $mine_id";
        if ($worker_id != -1) $sql_filter .= " AND worker_id = $worker_id";

        $workers = (new Query())
            ->select(
                [
                    'position_title',
                    'department_title',
                    'first_name',
                    'last_name',
                    'patronymic',
                    'gender',
                    'stuff_number',
                    'worker_object_id',
                    'worker_id',
                    'object_id',
                    'mine_id',
                    'checkin_status'
                ])
            ->from(['view_initWorkerMineCheckin'])
            ->where($sql_filter)
            ->all();

        if ($workers) {
            foreach ($workers as $worker) {
                $worker_id = $worker['worker_id'];
                $key = self::buildWorkerMineKey($mine_id, $worker_id);
                $worker_data = array(
                    // получаем ИД типизированного работника из БД
                    'worker_id' => (int)$worker['worker_id'],
                    'worker_object_id' => (int)$worker['worker_object_id'],
                    'object_id' => (int)$worker['object_id'],
                    'stuff_number' => $worker['stuff_number'],
                    'full_name' => $worker['last_name'] . " " . $worker['first_name'] . " " . $worker['patronymic'],
                    'position_title' => $worker['position_title'],
                    'department_title' => $worker['department_title'],
                    'gender' => $worker['gender'],
                    'mine_id' => (int)$worker['mine_id'],


//					'first_name' => $worker['first_name'],																// получаем имя работника из БД
//					'last_name' => $worker['last_name'],																// получаем фамилию работника из БД
//					'patronymic' => $worker['patronymic'],																// получаем отчество работника из БД
//					'brigade_id' => $worker['brigade_id'],																// получаем бригаду работника из БД
//					'chane_id' => $worker['chane_id'],																	// получаем звено работника из БД
//					'xyz' => $this->getParameterValue($worker_id, 83, 2)['value'],			// получаем координаты работника из кэша
//					'edge_id' => $this->getParameterValue($worker_id, 269, 2)['value'],		// получаем выработку для работника из кэша
//					'place_id' => $this->getParameterValue($worker_id, 122, 2)['value'],	// получаем местоположение работника из кэша
//					'status_id' => $this->getParameterValue($worker_id, 164, 2)['value'],	// получаем состояние работника из кэша
//					'check_in_out' => $this->getParameterValue($worker_id, 164, 2)['value'],// получаем местоположение работника из кэша
//					'CH4' => $this->getParameterValue($worker_id, 99, 2)['value'],			// получаем CH4 работника из кэша
//					'CO' => $this->getParameterValue($worker_id, 98, 2)['value'],			// получаем CO работника из кэша
//					'danger_zone' => $this->getParameterValue($worker_id, 131, 2)['value'],	// получаем запретную зону для работника из кэша
////					'in_mine_or_surface' => $this->getParameterValue($worker_id, 131, 2),								// получаем местонахождение работника(в шахте или на поверхности) работника из кэша //Todo уточнить как найти
//					'motion_flag' => $this->getParameterValue($worker_id, 356, 2)['value'],	// получаем флаг движения или без движения из кэша
//					'alarm' => $this->getParameterValue($worker_id, 323, 2)['value'],		// получаем Флаг сигнал SOS из кэша
//					'message_status' => $this->getParameterValue($worker_id, 323, 2)['value'],// получаем Флаг текстовое сообщение из кэша
                );

                $date_to_cache[$key] = $worker_data;
            }
            $this->amicum_mSet($date_to_cache);
            return true;
        }
        return false;
    }

    public function initWorkerMineHash($mine_id, $worker_id = -1)
    {
        $sql_filter = "mine_id = $mine_id";
        if ($worker_id != -1) $sql_filter .= " AND worker_id = $worker_id";

        $workers = (new Query())
            ->select(
                [
                    'position_title',
                    'department_title',
                    'first_name',
                    'last_name',
                    'patronymic',
                    'gender',
                    'stuff_number',
                    'worker_object_id',
                    'worker_id',
                    'object_id',
                    'mine_id',
                    'checkin_status'
                ])
            ->from(['view_initWorkerMineCheckin'])
            ->where($sql_filter)
            ->all();

        if ($workers) {
            $mine_map_key = self::buildMineMapKeyHash($mine_id);
            foreach ($workers as $worker) {
                $worker_id = $worker['worker_id'];
                $worker_data = array(
                    // получаем ИД типизированного работника из БД
                    'worker_id' => (int)$worker['worker_id'],
                    'worker_object_id' => (int)$worker['worker_object_id'],
                    'object_id' => (int)$worker['object_id'],
                    'stuff_number' => $worker['stuff_number'],
                    'full_name' => $worker['last_name'] . " " . $worker['first_name'] . " " . $worker['patronymic'],
                    'position_title' => $worker['position_title'],
                    'department_title' => $worker['department_title'],
                    'gender' => $worker['gender'],
                    'mine_id' => (int)$worker['mine_id'],


//					'first_name' => $worker['first_name'],																// получаем имя работника из БД
//					'last_name' => $worker['last_name'],																// получаем фамилию работника из БД
//					'patronymic' => $worker['patronymic'],																// получаем отчество работника из БД
//					'brigade_id' => $worker['brigade_id'],																// получаем бригаду работника из БД
//					'chane_id' => $worker['chane_id'],																	// получаем звено работника из БД
//					'xyz' => $this->getParameterValue($worker_id, 83, 2)['value'],			// получаем координаты работника из кэша
//					'edge_id' => $this->getParameterValue($worker_id, 269, 2)['value'],		// получаем выработку для работника из кэша
//					'place_id' => $this->getParameterValue($worker_id, 122, 2)['value'],	// получаем местоположение работника из кэша
//					'status_id' => $this->getParameterValue($worker_id, 164, 2)['value'],	// получаем состояние работника из кэша
//					'check_in_out' => $this->getParameterValue($worker_id, 164, 2)['value'],// получаем местоположение работника из кэша
//					'CH4' => $this->getParameterValue($worker_id, 99, 2)['value'],			// получаем CH4 работника из кэша
//					'CO' => $this->getParameterValue($worker_id, 98, 2)['value'],			// получаем CO работника из кэша
//					'danger_zone' => $this->getParameterValue($worker_id, 131, 2)['value'],	// получаем запретную зону для работника из кэша
////					'in_mine_or_surface' => $this->getParameterValue($worker_id, 131, 2),								// получаем местонахождение работника(в шахте или на поверхности) работника из кэша //Todo уточнить как найти
//					'motion_flag' => $this->getParameterValue($worker_id, 356, 2)['value'],	// получаем флаг движения или без движения из кэша
//					'alarm' => $this->getParameterValue($worker_id, 323, 2)['value'],		// получаем Флаг сигнал SOS из кэша
//					'message_status' => $this->getParameterValue($worker_id, 323, 2)['value'],// получаем Флаг текстовое сообщение из кэша
                );

                $date_to_cache[$mine_map_key][$worker_id] = $worker_data;
            }
            $this->amicum_mSetHash($date_to_cache);
            return true;
        }
        return false;
    }

    /**
     * Название метода: initWorkerParameterValue()
     * Назначение метода: метод инициализации вычисляемых значений параметров работника в кэш WorkerParameter
     *
     * Входные необязательные параметры
     * @param $worker_id - идентификатор работника. Если указать этот параметр, то берет данные для конкретного работника
     * и добавляет в кэш
     * @param $sql - условие для фильтра
     *
     * @return bool возвращает true при успешном добавлении в кэш, иначе false
     *
     *
     * @example $this->initWorkerParameterValue();
     * @example $this->initWorkerParameterValue(475);
     * @example $this->initWorkerParameterValue(-1, 'worker_id = 475');
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 31.05.2019 11:51
     */
    public function initWorkerParameterValue($worker_id = -1, $sql = '')
    {
        $sql_filter = '';
        if ($worker_id !== -1) {
            $sql_filter .= "worker_id = $worker_id ";
        }

        if ($sql !== '') {
            $sql_filter .= ' AND ' . $sql;
        }

        $worker_parameter_values = (new Query())
            ->select([
                'worker_id',
                'worker_parameter_id',
                'parameter_id',
                'parameter_type_id',
                'date_time',
                'value',
                'status_id'
            ])
            ->from('view_initWorkerParameterValue')
            ->where($sql_filter)
            ->all();
        if ($worker_parameter_values) {
            foreach ($worker_parameter_values as $worker_parameter_value) {
                $key = $this->buildParameterKey($worker_parameter_value['worker_id'], $worker_parameter_value['parameter_id'], $worker_parameter_value['parameter_type_id']);
                $date_to_cache[$key] = $worker_parameter_value;
            }
            $this->amicum_mSet($date_to_cache);
            return $worker_parameter_values;
        }
        return false;
    }

    public function initWorkerParameterValueHash($worker_id = -1, $sql = '')
    {
        $sql_filter = '';
        if ($worker_id !== -1) {
            $sql_filter .= "worker_id = $worker_id ";
        }

        if ($sql !== '') {
            $sql_filter .= ' AND ' . $sql;
        }

        $worker_parameter_values = (new Query())
            ->select([
                'worker_id',
                'worker_parameter_id',
                'parameter_id',
                'parameter_type_id',
                'date_time',
                'value',
                'status_id'
            ])
            ->from('view_initWorkerParameterValue')
            ->where($sql_filter)
            ->all();
        if ($worker_parameter_values) {
            foreach ($worker_parameter_values as $worker_parameter_value) {
                $key = $this->buildParameterKeyHash($worker_parameter_value['parameter_id'], $worker_parameter_value['parameter_type_id']);
                $worker_map_key = self::buildWorkerMapKeyHash($worker_parameter_value['worker_id']);
                $date_to_cache[$worker_map_key][$key] = $worker_parameter_value;
            }
            $this->amicum_mSetHash($date_to_cache);
            return $worker_parameter_values;
        }
        return false;
    }

    /**
     * Название метода: initWorkerParameterHandbookValue()
     * Назначение метода: метод инициализации справочных значений параметров работника в кэш WorkerParameter
     *
     * Входные необязательные параметры
     * @param $worker_id - идентификатор работника. Если указать этот параметр, то берет данные для конкретного работника
     * и добавляет в кэш
     * @param $sql - условие для фильтра
     *
     * @return bool возвращает true при успешном добавлении в кэш, иначе false
     *
     *
     * @example $this->initWorkerParameterValue();
     * @example $this->initWorkerParameterValue(475);
     * @example $this->initWorkerParameterValue(-1, 'worker_id = 475');
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 31.05.2019 11:59
     */
    public function initWorkerParameterHandbookValue($worker_id = -1, $sql = '')
    {
        $sql_filter = '';
        if ($worker_id !== -1) {
            $sql_filter .= "worker_id = $worker_id ";
        }

        if ($sql !== '') {
            $sql_filter .= ' AND ' . $sql;
        }

        $worker_parameter_handbook_val = (new Query())
            ->select([
                'worker_id',
                'worker_parameter_id',
                'parameter_id',
                'parameter_type_id',
                'date_time',
                'value',
                'status_id'
            ])
            ->from('view_initWorkerParameterHandbookValue')
            ->where($sql_filter)
            ->all();
        if ($worker_parameter_handbook_val) {
            foreach ($worker_parameter_handbook_val as $worker_parameter_value) {
                $key = $this->buildParameterKey($worker_parameter_value['worker_id'], $worker_parameter_value['parameter_id'], $worker_parameter_value['parameter_type_id']);
                $date_to_cache[$key] = $worker_parameter_value;
            }
            $this->amicum_mSet($date_to_cache);
            return $worker_parameter_handbook_val;
        }
        return false;
    }

    public function initWorkerParameterHandbookValueHash($worker_id = -1, $sql = '')
    {
        $sql_filter = '';
        if ($worker_id !== -1) {
            $sql_filter .= "worker_id = $worker_id ";
        }

        if ($sql !== '') {
            $sql_filter .= ' AND ' . $sql;
        }

        $worker_parameter_handbook_val = (new Query())
            ->select([
                'worker_id',
                'worker_parameter_id',
                'parameter_id',
                'parameter_type_id',
                'date_time',
                'value',
                'status_id'
            ])
            ->from('view_initWorkerParameterHandbookValue')
            ->where($sql_filter)
            ->all();
        if ($worker_parameter_handbook_val) {
            foreach ($worker_parameter_handbook_val as $worker_parameter_value) {
                $key = $this->buildParameterKeyHash($worker_parameter_value['parameter_id'], $worker_parameter_value['parameter_type_id']);
                $worker_map_key = self::buildWorkerMapKeyHash($worker_parameter_value['worker_id']);
                $date_to_cache[$worker_map_key][$key] = $worker_parameter_value;
            }
            $this->amicum_mSetHash($date_to_cache);
            return $worker_parameter_handbook_val;
        }
        return false;
    }

    /**
     * Метод multiSetWorkerParameter() - метод массовой вставки значений парамттеров работников в кэш
     * @param $value
     * @return mixed массив вставленных данных
     * @package backend\controllers\cachemanagers
     * @example
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 02.10.2019 16:52
     */
    public function multiSetWorkerParameter($value)
    {
        foreach ($value as $item) {
            $build_structure = self::buildStructureWorkerParametersValue(
                $item['worker_id'],
                $item['worker_parameter_id'],
                $item['parameter_id'],
                $item['parameter_type_id'],
                $item['date_time'],
                $item['value'],
                $item['status_id']
            );
            $key = $this->buildParameterKey($item['worker_object_id'], $item['parameter_id'], $item['parameter_type_id']);
            $data_for_cache[$key] = $build_structure;
        }

        /******************* Добавление в кэш *******************/
        return $this->amicum_mSet($data_for_cache);
    }

    public function multiSetWorkerParameterHash($value)
    {
        foreach ($value as $item) {
            $build_structure = self::buildStructureWorkerParametersValue(
                $item['worker_id'],
                $item['worker_parameter_id'],
                $item['parameter_id'],
                $item['parameter_type_id'],
                $item['date_time'],
                $item['value'],
                $item['status_id']
            );
            $key = $this->buildParameterKeyHash($item['parameter_id'], $item['parameter_type_id']);
            $worker_map_key = self::buildWorkerMapKeyHash($item['worker_id']);
            $data_for_cache[$worker_map_key][$key] = $build_structure;
        }

        /******************* Добавление в кэш *******************/
        return $this->amicum_mSetHash($data_for_cache);
    }


    /**
     * Название метода: setParameterValue()
     * Назначение метода: метод добавления значения для параметра работника
     *
     * Входные обязательные параметры:
     * @param $worker_id - идентификатор работника
     * @param $value - массив значений. В массиве обязятельно должны быть
     * указаны $value['parameter_id'] и $value['parameter_type_id'], иначе данные не добавляются в кэш
     *
     * @return bool - если данные успешно добавились, то возвращает true, иначе false
     *
     * Входные необязательные параметры
     *
     * @package backend\controllers\cachemanagers
     *
     * @example $this->setParameterValue(458, array());
     *
     * Документация на портале:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 31.05.2019 15:29
     */
    public function setParameterValue($worker_id, $value)
    {
        if (isset($value['parameter_id'], $value['parameter_type_id'])) {
            $key = $this->buildParameterKey($worker_id, $value['parameter_id'], $value['parameter_type_id']);
            return $this->amicum_rSet($key, $value);
        }
        return false;
    }

    /**
     * Название метода: setWorkingHours()
     * Назначение метода: метод сохранение времени регистрации лампы
     *
     * Входные обязательные параметры:
     * @param $worker_id - идентификатор работника
     * @param $value - время
     *
     * @return bool - если данные успешно добавились, то возвращает true, иначе false
     *
     * @package backend\controllers\cachemanagers
     *
     * @example $this->setWorkingHours(458, '2023-10-10 14:44:41.138743');
     */
    public function setWorkingHours($worker_id, $value)
    {
        $key = $this->buildWorkingHoursKey($worker_id);
        return $this->amicum_rSet($key, $value);
    }

    /**
     * Название метода: getWorkingHours()
     * Назначение метода: метод получение времени регистрации лампы
     *
     * Входные обязательные параметры:
     * @param $worker_id - идентификатор работника
     *
     * @return bool - массив данных, если они есть, иначе false.
     *
     * @package backend\controllers\cachemanagers
     *
     * @example $this->getWorkingHours(458);
     */
    public function getWorkingHours($worker_id)
    {
        $key = $this->buildWorkingHoursKey($worker_id);
        return $this->amicum_rGet($key);
    }

    /**
     * Название метода: cleanWorkingHours()
     * Назначение метода: метод очистки времени регистрации лампы
     *
     * Входные обязательные параметры:
     * @param $worker_id - идентификатор работника
     *
     * @package backend\controllers\cachemanagers
     *
     * @example $this->cleanWorkingHours(458);
     */
    public function cleanWorkingHours($worker_id)
    {
        $key = $this->buildWorkingHoursKey($worker_id);
        return $this->amicum_rDel($key);
    }


    public function setParameterValueHash($worker_id, $value)
    {
        if (isset($value['parameter_id'], $value['parameter_type_id'])) {
            $key = $this->buildParameterKeyHash($value['parameter_id'], $value['parameter_type_id']);
            $worker_map_key = self::buildWorkerMapKeyHash($worker_id);
            $this->amicum_rSetHash($worker_map_key, $key, $value);
            return true;
        }
        return false;
    }

    /**
     * @param $mine_id - идентификатор шахты. Если указать '*', то возвращает все шахты
     * @param $worker - идентификатор работника. Если указать '*', то возвращает всех работников. По умолчанию всех работников.
     * @return array|bool - массив данных, если они есть, иначе false.
     *
     * $worker_data = array(
     * // получаем ИД типизированного работника из БД
     *  [ ]$worker_data = array(
     * // получаем ИД типизированного работника из БД
     * 'worker_id' => (int)$worker['worker_id'],
     * 'worker_object_id' => (int)$worker['worker_object_id'],
     * 'object_id' => (int)$worker['object_id'],
     * 'stuff_number' => $worker['stuff_number'],
     * 'full_name' => $worker['last_name'] . " " . $worker['first_name'] . " " . $worker['patronymic'],
     * 'position_title' => $worker['position_title'],
     * 'department_title' => $worker['department_title'],
     * 'gender' => $worker['gender'],
     * 'mine_id' => (int)$worker['mine_id'],
     *
     *
     * //                    'first_name' => $worker['first_name'],                                                                // получаем имя работника из БД
     * //                    'last_name' => $worker['last_name'],                                                                // получаем фамилию работника из БД
     * //                    'patronymic' => $worker['patronymic'],                                                                // получаем отчество работника из БД
     * //                    'brigade_id' => $worker['brigade_id'],                                                                // получаем бригаду работника из БД
     * //                    'chane_id' => $worker['chane_id'],                                                                    // получаем звено работника из БД
     * //                    'xyz' => $this->getParameterValue($worker_id, 83, 2)['value'],            // получаем координаты работника из кэша
     * //                    'edge_id' => $this->getParameterValue($worker_id, 269, 2)['value'],        // получаем выработку для работника из кэша
     * //                    'place_id' => $this->getParameterValue($worker_id, 122, 2)['value'],    // получаем местоположение работника из кэша
     * //                    'status_id' => $this->getParameterValue($worker_id, 164, 2)['value'],    // получаем состояние работника из кэша
     * //                    'check_in_out' => $this->getParameterValue($worker_id, 164, 2)['value'],// получаем местоположение работника из кэша
     * //                    'CH4' => $this->getParameterValue($worker_id, 99, 2)['value'],            // получаем CH4 работника из кэша
     * //                    'CO' => $this->getParameterValue($worker_id, 98, 2)['value'],            // получаем CO работника из кэша
     * //                    'danger_zone' => $this->getParameterValue($worker_id, 131, 2)['value'],    // получаем запретную зону для работника из кэша
     * ////                    'in_mine_or_surface' => $this->getParameterValue($worker_id, 131, 2),                                // получаем местонахождение работника(в шахте или на поверхности) работника из кэша //Todo уточнить как найти
     * //                    'motion_flag' => $this->getParameterValue($worker_id, 356, 2)['value'],    // получаем флаг движения или без движения из кэша
     * //                    'alarm' => $this->getParameterValue($worker_id, 323, 2)['value'],        // получаем Флаг сигнал SOS из кэша
     * //                    'message_status' => $this->getParameterValue($worker_id, 323, 2)['value'],// получаем Флаг текстовое сообщение из кэша
     * ); - array
     *      'worker_id'             - ключ работника
     *      'worker_object_id'      - ключ конкретного работника
     *      'object_id'             - ключ типового объекта работника
     *      'stuff_number'          - табельный номер работника
     *      'full_name'             - полное имя работника
     *      'position_title'        - название должности работника
     *      'department_title'      - название департамента работника
     *      'gender'                - гендерный признак работника
     *      'mine_id'               - ключ шахты работника
     * );
     * @package backend\controllers\cachemanagers
     * Название метода: getWorkerMine()
     * Назначение метода: getWorkerMine - Метод получения данные работника(ов) по шахте(по шахтам) из кэша redis.
     *
     * Входные обязательные параметры:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 31.05.2019 10:12
     */
    public function getWorkerMine($mine_id, $worker = '*')
    {
        $redis_cache_key = self::buildWorkerMineKey($mine_id, $worker);
        $keys = $this->redis_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
        if ($keys) {
            $workers = $this->amicum_mGet($keys);
            return $workers;
        }
        return false;
    }

    public function getWorkerMineHash($mine_id, $worker_id = '*')
    {
        $mine_map_key = self::buildMineMapKeyHash($mine_id);
        $workers = $this->amicum_rGetMapHash($mine_map_key);

        if ($workers and $worker_id != '*') {
            foreach ($workers as $worker) {
                if ($worker['worker_id'] == $worker_id) {
                    $result[] = $worker;
                }
            }
        } else {
            $result = $workers;
        }
        if (!isset($result)) {
            return false;
        }
        return $result;
    }

    /**
     * Название метода: getParameterValue() - Метод получения значения конкретного парамтера воркера кэша WorkerParameter
     * Назначение метода: Метод получения значения конкретного парамтера воркера кэша WorkerParameter
     * В этом методе нельзя получать данные указываю * (звездочку)
     * Входные обязательные параметры:
     * @param $worker_id - идентификатор работника
     * @param $parameter_id - идентификатор параметра
     * @param $parameter_type_id - идентификатор типа параметра
     * @param array/boolean - массив данных либо false при отсутсвии данных
     *
     * @return mixed array/boolean - массив данных либо false при отсутсвии данных.
     * Возвращает одинарный массив!!!
     *
     * @example $this>getParameterValue(64895, 122, 2)
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 31.05.2019 12:04
     */
    public function getParameterValue($worker_id, $parameter_id, $parameter_type_id)
    {
        $key = $this->buildParameterKey($worker_id, $parameter_id, $parameter_type_id);
        return $this->amicum_rGet($key);
    }

    public function getParameterValueHash($worker_id, $parameter_id, $parameter_type_id)
    {
        $key = $this->buildParameterKeyHash($parameter_id, $parameter_type_id);
        $worker_map_key = self::buildWorkerMapKeyHash($worker_id);
        return $this->amicum_rGetHash($worker_map_key, $key);
    }

    /**
     * Название метода: multiGetParameterValue() - Метод получения значения параметров воркеров/воркера из кэша redis (групповое получение).
     * Назначение метода: Метод получения значения параметров воркеров/воркера из кэша redis (групповое получение).
     * Можно получить данные по разному. Если нужно выбрать любой воркера, или параметр,
     * или тип параметра, необходимо указать '*'.
     *
     * Входные обязательные параметры:
     *
     * @param $worker_id - идентификатор работника. Если указать '*', то возвращает все сенсоры
     * @param $parameter_id - идентификатор параметра. Если указать '*', то возвращает все параметры
     * @param $parameter_type_id - идентификатор типа параметра. Если указать '*', то возвращает все типы параметра
     *
     * @return bool/array результат выполнения метода. Если данные есть, то массив данных, иначе false;
     * Возвращает многомерный массив
     *
     * Напрмиер:
     * 1. Получить работник с id = 310 со всеми параметрами
     *    (new WorkerCacheController())->getParameterValue('310', '*', '*')
     * 2. Получить работник id = 310 c параметров 83 и тип параметра любой
     *  (new WorkerCacheController())->getParameterValue('310', '83', '*')
     * 3. Получить работник id = 310 c параметров 83 и тип параметра 2
     *  (new WorkerCacheController())->getParameterValue('310', '83', '2')
     * 4. Получить всех работников c параметров 83 и тип параметра 2
     *  (new WorkerCacheController())->getParameterValue('*', '83', '2')
     *
     *
     * @package backend\controllers\cachemanagers
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 31.05.2019 12:05
     * @since ver
     */
    public function multiGetParameterValue($worker_id = '*', $parameter_id = '*', $parameter_type_id = '*')
    {
        //$microtime_start = microtime(true);
        $redis_cache_key = $this->buildParameterKey($worker_id, $parameter_id, $parameter_type_id);
        //Assistant::VarDump("!--- Построил ключ ". round(microtime(true) - $microtime_start, 6));
        $keys = $this->redis_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
        //Assistant::VarDump( "------- Нашел ключ ".round(microtime(true) - $microtime_start, 6));
        if ($keys) {
            return $this->amicum_mGet($keys);
            //Assistant::VarDump("**------- Получил ключ ". round(microtime(true) - $microtime_start, 6));
        }
        return false;
    }

    public function multiGetParameterValueHash($worker_id = '*', $parameter_id = '*', $parameter_type_id = '*', $take_not_reference = false)
    {
        $result = [];
        if ($worker_id == '*') {
            if ($parameter_type_id != 1) {
                $wpv = WorkerBasicController::getWorkerParameterValue($worker_id, $parameter_id, $parameter_type_id);
                if ($wpv) {
                    $result = $wpv;
                }
            }
            if ($parameter_type_id != 2 and $parameter_type_id != 3) {
                $wphv = WorkerBasicController::getWorkerParameterHandbookValue($worker_id, $parameter_id);
                if ($wphv) {
                    $result = array_merge($result, $wphv);
                }
            }
        } else {
            $worker_map_key = self::buildWorkerMapKeyHash($worker_id);
            $worker_parameter_values = $this->amicum_rGetMapHash($worker_map_key);

            if (!$worker_parameter_values) {
                return false;
            }

            if ($worker_parameter_values and $parameter_id != '*' and $parameter_type_id != '*') {
                foreach ($worker_parameter_values as $worker_parameter_value) {
                    if ($worker_parameter_value['parameter_id'] == $parameter_id and $worker_parameter_value['parameter_type_id'] == $parameter_type_id) {
                        $result[] = $worker_parameter_value;
                    }
                }
            } else if ($worker_parameter_values and $parameter_id != '*') {
                foreach ($worker_parameter_values as $worker_parameter_value) {
                    if ($worker_parameter_value['parameter_id'] == $parameter_id) {
                        $result[] = $worker_parameter_value;
                    }
                }
            } else if ($worker_parameter_values and $parameter_type_id != '*') {
                foreach ($worker_parameter_values as $worker_parameter_value) {
                    if ($worker_parameter_value['parameter_type_id'] == $parameter_type_id) {
                        $result[] = $worker_parameter_value;
                    }
                }
            } else {
                $result = $worker_parameter_values;
            }
        }
        return $result;
    }


    /**
     * @param $mine_id - идентификатор шахты
     * @param $worker_id - идентификато работника
     *
     * @return string - созданный ключ
     *
     * @package backend\controllers\cachemanagers
     * Название метода: buildWorkerMineKey()
     * Назначение метода: метод создания ключа для списка работников по шахте в кэше WorkerMine
     *
     * Входные обязательные параметры:
     * @example $this->buildWorkerMineKey(290, 97878);
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 31.05.2019 9:59
     */
    public static function buildWorkerMineKey($mine_id, $worker_id)
    {
        return self::$worker_mine_cache_key . ':' . $mine_id . ':' . $worker_id;
    }

    /**
     * @param $worker_id - идентификатор  работника. Если указать '*', то возвращает всех работников
     * @param $parameter_id - идентификатор параметра. Если указать '*', то возвращает все параметры
     * @param $parameter_type_id - идентификатор типа параметра. Если указать '*', то возвращает все типы параметров
     * @return string созданный ключ кэша в виде WorkerParameter:worker_id:parameter_id:parameter_type_id
     *
     * @package backend\controllers\cachemanagers
     * Название метода: buildParameterKey()
     * Назначение метода: Метод создания ключа кэша для списка параметров работников с их значениями (WorkerParameter)
     *
     * Входные обязательные параметры:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 31.05.2019 10:49
     */
    public function buildParameterKey($worker_id, $parameter_id, $parameter_type_id)
    {
        return self::$worker_parameter_cache_key . ':' . $worker_id . ':' . $parameter_id . ':' . $parameter_type_id;
    }

    public function buildWorkingHoursKey($worker_id)
    {
        return self::$worker_working_hours_cache_key . ':' . $worker_id;
    }

    public function buildParameterKeyHash($parameter_id, $parameter_type_id)
    {
        return $parameter_id . ':' . $parameter_type_id;
    }

    public static function buildMineMapKeyHash($mine_id)
    {
        return self::$mine_map_cache_key . ':' . $mine_id;
    }

    public function buildWorkerMapKeyHash($worker_id)
    {
        return self::$worker_map_cache_key . ':' . $worker_id;
    }


    /**
     * Название метода: delParameterValue()
     * Назначение метода: Метод удаления значения параметров работников(а)
     * Метод ничего не возвращает.
     *
     * Входные обязательные параметры:
     *
     * @param        $worker_id - идентификатор работника. Если указать '*', то удаляет всех параметров работника
     * @param string $parameter_id - идентификатор параметра.  Если указать '*', то удаляет все параметры
     * @param string $parameter_type_id - идентификато типа параметра. Если указать '*', то удаляет все типы параметров
     *
     * @package backend\controllers\cachemanagers
     *
     * @example $this->delParameterValue(310, 83, 2)
     * @example $this->delParameterValue(310, *, 2)
     * @example $this->delParameterValue(*, 83, 2)
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 31.05.2019 13:54
     */
    public function delParameterValue($worker_id, $parameter_id = '*', $parameter_type_id = '*')
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        try {
            $redis_cache_key = $this->buildParameterKey($worker_id, $parameter_id, $parameter_type_id);
            if ($redis_cache_key) {
                $warnings[] = "delParameterValue. Создал ключ воркера на удаление";
            } else {
                throw new Exception("delParameterValue. Не удалось создать ключ для удаления параметра сенсора");
            }

            $keys = $this->redis_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
            if ($keys) {
                $del_param_res = $this->amicum_mDel($keys);
                if (!$del_param_res) {
                    $errors[] = $keys;
                    throw new Exception("delParameterValue. Ошибка удаления параметра(ов) работника(ов) ");
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

        $warnings[] = "delParameterValue. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    public function delParameterValueHash($worker_id, $parameter_id = '*', $parameter_type_id = '*')
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        try {
            $redis_cache_key = $this->buildParameterKeyHash($parameter_id, $parameter_type_id);
            $worker_map_key = self::buildWorkerMapKeyHash($worker_id);
            if ($redis_cache_key) {
                $warnings[] = "delParameterValueHash. Создал ключ работника на удаление";
            } else {
                throw new Exception("delParameterValueHash. Не удалось создать ключ для удаления параметра работника");
            }

            if ($worker_id == '*') {
                throw new Exception("Поиск по хешам не доступен в редисе");
            }

            $keys = $this->redis_cache->hscan($worker_map_key, 0, 'MATCH', $redis_cache_key, 'COUNT', '10000000');

            if ($keys and isset($keys[1]) and count($keys[1]) > 0) {
                for ($i = 0; $i < count($keys[1]); $i = $i + 2) {
                    $keys_to_del[$worker_map_key][] = $keys[1][$i];
                }

                $del_param_res = $this->amicum_mDelHash($keys_to_del);
                if (!$del_param_res) {
                    $errors[] = $keys;
                    throw new Exception("delParameterValueHash. Ошибка удаления параметра работника ");
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
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Название метода: delWorkerMine()
     * Назначение метода: Метод удаления работника из работников по шахте из кэша
     * Метод ничего не возвращает.
     *
     * Входные обязательные параметры:
     *
     * @param $mine_id - идентификатор шахты. По умолчанию сенсор с таким идентификатор ищется во всех шахтах и удаляется
     * @param $worker_id - идентифкатор конкретного работника. Если указать $worker_id = '*', то удаляет всех рабтников в шахте
     * указанной
     *
     * @package backend\controllers\cachemanagers
     * @example $this->delWorkerMine(290, 310)
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 27.05.2019 15:32
     */
    public function delWorkerMine($worker_id, $mine_id = '*')
    {
        $redis_cache_key = self::buildWorkerMineKey($mine_id, $worker_id);
        $keys = $this->redis_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
        if ($keys) {
            $this->amicum_mDel($keys);
        }
    }

    public function delWorkerMineHash($worker_id, $mine_id = '*')
    {
        if ($mine_id == '*') {
            $mine_id = AMICUM_DEFAULT_MINE;
        }
        $mine_map_key = self::buildMineMapKeyHash($mine_id);
        $keys[$mine_map_key][] = $worker_id;

        $this->amicum_mDelHash($keys);
    }

    /******************************* Методы привязки сенсоров к работникам *******************************************/

    /**
     * @param $sensor_id - идентификатор сенсора
     * @param $worker_id - идентификато работника
     *
     * @return string - созданный ключ для кэша SensorWorker
     *
     * @package backend\controllers\cachemanagers
     * Название метода: buildSensorWorkerKey()
     * Назначение метода: метод создания ключа для списка сенсоров привязанных к работникам
     *
     * Входные обязательные параметры:
     * @example $this->buildSensorWorkerKey(290, 97878);
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 03.06.2019 16:40
     */
    public function buildSensorWorkerKey($sensor_id, $worker_id)
    {
        return self::$sensor_worker_cache_key . ':' . $sensor_id . ':' . $worker_id;
    }

    /**
     * Название метода: setSensorWorker()
     * Назначение метода: метод привязки/редактирования привязки лаппы к работнику.
     * При привязки лампы к работнику, снача ищется у лампы предыдущие работники. Если лампа уже привяза к каким-то работникам,
     * то отвязывается, и привязывается к ней новый работник.
     *
     * Входные обязательные параметры:
     * @param $sensor_id - идентифкатор сенсора
     * @param $worker_id - идентификатор работника
     *
     * @return bool если данные успешно хранились
     *
     * @package backend\controllers\cachemanagers
     *
     * @example $this->setSensorWorker(27321, 22222);
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 8:46
     */
    public function setSensorWorker($sensor_id, $worker_id)
    {
        $sensor_worker_key_old = $this->buildSensorWorkerKey('*', $worker_id);
        $sensor_worker_key_old_keys = $this->redis_cache->scan(0, 'MATCH', $sensor_worker_key_old, 'COUNT', '10000000')[1];
        if ($sensor_worker_key_old_keys) {
            $this->amicum_mDel($sensor_worker_key_old_keys);
        }
        $sensor_worker_key_new = $this->buildSensorWorkerKey($sensor_id, $worker_id);
        return $this->amicum_rSet($sensor_worker_key_new, array('sensor_id' => $sensor_id, 'worker_id' => $worker_id));

    }

    /**
     * Название метода: delSensorWorker() - метод удаление привязки сенсора к работнику по сенсору
     * Назначение метода: метод удаление привязки сенсора к работнику по сенсору
     *
     * Входные обязательные параметры:
     * @param $sensor_id - идентифкатор сенсора
     * @param $worker_id - идентификатор работника
     *
     * @return bool если данные успешно хранились
     *
     * @package backend\controllers\cachemanagers
     *
     * @example $this->setSensorWorker(27321, 22222);
     *
     * @author Якимов М.Н.
     * Created date: on 04.06.2019 8:46
     */
    public function delSensorWorker($sensor_id)
    {

        $sensor_worker_key_old = $this->buildSensorWorkerKey($sensor_id, '*');
        $sensor_worker_key_old_keys = $this->redis_cache->scan(0, 'MATCH', $sensor_worker_key_old, 'COUNT', '10000000')[1];
        if ($sensor_worker_key_old_keys) {
            $this->amicum_mDel($sensor_worker_key_old_keys);
            return true;
        }
        return false;

    }

    /**
     * @param $worker_id - идентификатор работника. Если указать '*', то возвращает сенсоры. По умолчанию все сенсоры.
     *
     * @return mixed созданный ключ кэша в виде SensorParameter:sensor_id:parameter_id:parameter_type_id
     *
     * @package backend\controllers\cachemanagers
     * Название метода: buildParameterKey()
     * Назначение метода: getWorkerMineByWorker - Метод получения сенсора(ов) по сенсор айди из кэша redis.
     *
     * Входные обязательные параметры:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 24.05.2019 13:05
     */
    public function getWorkerMineByWorker($worker_id = '*')
    {
        $redis_cache_key = self::buildWorkerMineKey('*', $worker_id);
        $keys = $this->redis_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
        if ($keys) {
            return $this->amicum_mGet($keys);
        }
        return false;
    }

    public function getWorkerMineByWorkerHash($worker_id)
    {
        $key = $this->buildParameterKeyHash(346, 2);
        $worker_map_key = self::buildWorkerMapKeyHash($worker_id);
        $worker_mine = $this->amicum_rGetHash($worker_map_key, $key);

        if ($worker_mine and isset($worker_mine['value'])) {
            $mine_id = $worker_mine['value'];
        } else {
            $mine_id = AMICUM_DEFAULT_MINE;
        }

        $mine_map_key = self::buildMineMapKeyHash($mine_id);
        $worker_mine = $this->amicum_rGetHash($mine_map_key, $worker_id);

        if (!$worker_mine) {
            return false;
        }

        return $worker_mine;
    }

    /**
     * Получение информации о воркере из кэша WorkerMine
     * @param $mine_id - идентификатор шахты
     * @param $worker_id - идентификатор воркера
     * @return bool
     */
    public function getWorkerMineByWorkerOne($mine_id, $worker_id)
    {
        $cache_key = self::buildWorkerMineKey($mine_id, $worker_id);
        return $this->amicum_rGet($cache_key);
    }

    public function getWorkerMineByWorkerOneHash($mine_id, $worker_id)
    {
        $mine_map_key = self::buildMineMapKeyHash($mine_id);
        $worker_mine = $this->amicum_rGetHash($mine_map_key, $worker_id);
        if (!$worker_mine) {
            return false;
        }

        return $worker_mine;
    }

    /**
     * Название метода: initSensorWorker()
     * Назначение метода: метод инициализации списка сенсоров привязанных к работникам.
     * Получает данные из БД и добавляет в кэш
     *
     * Входные необязательные параметры
     * @param string $sql_condition - условие для поиска. Можно указать условие в виде $sql_condition = "worker_id = 5454 AND sensor_id = 478"
     *
     * @return bool возвращает true если все норм, иначе false
     * @package backend\controllers\cachemanagers
     *
     * @example $this->initWorkerSensor("worker_id = 5454 AND sensor_id = 478");
     * @example $this->initWorkerSensor("worker_id = 5454");
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 03.06.2019 16:42
     */
    public function initSensorWorker($sql_condition = '')
    {
        $sql_query = '';
        if ($sql_condition != '') {
            $sql_query = $sql_condition;
        }
        $workers_sensors = (new Query())
            ->select([
                'sensor_id',
                'worker_id'
            ])
            ->from('view_GetWorkerBySensor')//переписал вьюшку - на правильную, view_worker_sensor_maxDate_fullInfo - содержит много лишней информации.
            ->where($sql_query)
            ->all();
        if ($workers_sensors) {
            foreach ($workers_sensors as $workers_sensor) {
                $sensor_worker_cache_key = $this->buildSensorWorkerKey($workers_sensor['sensor_id'], $workers_sensor['worker_id']);
                $date_to_cache[$sensor_worker_cache_key] = $workers_sensor;
            }
            $this->amicum_mSet($date_to_cache);
            return $workers_sensors;
        }
        return false;
    }

    /**
     * Название метода: getSensorWorker - метод получения привязанного сенсора к работнику по ИД сенсору или по ИД работнику
     * Назначение метода: метод получения привязанного сенсора к работнику по ИД сенсору или по ИД работнику
     *
     * Входные необязательные параметры
     * @param $sensor_id - идентифкатор сенсора
     * @param $worker_id - идентификатор работника
     *
     * @return mixed возвращает массив данных если есть (одномерный массив), иначе false
     * @package backend\controllers\cachemanagers
     *
     * @example $this->multiGetSensorWorker('*',310);
     * @example $this->multiGetSensorWorker('*','*');
     * @example $this->multiGetSensorWorker(4755,'*');
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 03.06.2019 16:50
     */
    public function getSensorWorker($sensor_id = '*', $worker_id = '*')
    {
        $sensor_worker_cache_key = $this->buildSensorWorkerKey($sensor_id, $worker_id);
        $sensor_worker_cache_keys = $this->redis_cache->scan(0, 'MATCH', $sensor_worker_cache_key, 'COUNT', '10000000')[1];
        if ($sensor_worker_cache_keys) {
            return $this->amicum_mGet($sensor_worker_cache_keys)[0];
        }
        return false;
    }

    public function setWorkerParameterValue($worker_id, $worker_parameter_id, $parameter_id, $parameter_type_id, $date_time, $value, $status_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'setWorkerParameterValue. Начало выполнения метода';
        try {
            //если дата не передана, то брать текущее время с миллисекундами
            if (!$date_time) {
                $date_time = date('Y-m-d H:i:s.U');
                $warnings[] = 'setWorkerParameterValue. Дата не задана. Взял текущую';
            }
            /**
             * если $worker_parameter_id равен -1 при вызове функции, но указаны parameter_id и parameter_type_id,
             * то мы его ищем в кеше, если его и там нет, то мы его ищем в БД, если его и там нет, то выкидываем исключение
             */
            if ($worker_parameter_id == -1) {
                $warnings[] = 'setWorkerParameterValue. worker_parameter_id = -1 Начинаю поиск в кеше или в базе';
                $key = $this->buildParameterKey($worker_id, $parameter_id, $parameter_type_id);
                $worker_parameters = $this->amicum_rGet($key);
                if ($worker_parameters) {
                    $warnings[] = 'setWorkerParameterValue. Нашел воркер параметер айди в кеше';
                    $worker_parameter_id = $worker_parameters['worker_parameter_id'];
                } else {
                    $warnings[] = 'setWorkerParameterValue. В кеше не было ищу в базе';
                    $response = WorkerMainController::getOrSetWorkerParameter($worker_id, $parameter_id, $parameter_type_id);
                    if ($response['status'] == 1) {
                        $worker_parameter_id = $worker_parameters['worker_parameter_id'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        $warnings[] = 'setWorkerParameterValue. Нашел в базе данных/или создал в БД';
                    } else {
                        $warnings[] = 'setWorkerParameterValue. В базе сенсор параметер айди не нашел. генерирую исключение';
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new Exception("setWorkerParameterValue. Для сенсора $worker_id не существует привязки к нему параметра $parameter_id и типа параметра $parameter_type_id");
                    }
                }
            }
            $warnings[] = "setWorkerParameterValue. Воркер параметер айди = $worker_parameter_id";
            $warnings[] = 'setWorkerParameterValue. Начинаю сохранение в кеш';
            $worker_parameter_values['worker_id'] = $worker_id;
            $worker_parameter_values['worker_parameter_id'] = $worker_parameter_id;
            $worker_parameter_values['value'] = $value;
            $worker_parameter_values['parameter_id'] = $parameter_id;
            $worker_parameter_values['parameter_type_id'] = $parameter_type_id;
            $worker_parameter_values['status_id'] = $status_id;
            $worker_parameter_values['date_time'] = $date_time;

            $key = $this->buildParameterKey($worker_id, $parameter_id, $parameter_type_id);
            $this->amicum_rSet($key, $worker_parameter_values);
            $warnings[] = 'setWorkerParameterValue. Сохранил в кеш';
            unset($worker_parameter_values);
            $status *= 1;
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'setWorkerParameterValue. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'setWorkerParameterValue. Закончил выполнение метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    public function setWorkerParameterValueHash($worker_id, $worker_parameter_id, $parameter_id, $parameter_type_id, $date_time, $value, $status_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'setWorkerParameterValueHash. Начало выполнения метода';
        try {
            //если дата не передана, то брать текущее время с миллисекундами
            if (!$date_time) {
                $date_time = date('Y-m-d H:i:s.U');
                $warnings[] = 'setWorkerParameterValueHash. Дата не задана. Взял текущую';
            }
            /**
             * если $worker_parameter_id равен -1 при вызове функции, но указаны parameter_id и parameter_type_id,
             * то мы его ищем в кеше, если его и там нет, то мы его ищем в БД, если его и там нет, то выкидываем исключение
             */
            $worker_map_key = self::buildWorkerMapKeyHash($worker_id);
            if ($worker_parameter_id == -1) {
                $warnings[] = 'setWorkerParameterValueHash. worker_parameter_id = -1 Начинаю поиск в кеше или в базе';
                $key = $this->buildParameterKeyHash($parameter_id, $parameter_type_id);
                $worker_parameters = $this->amicum_rGetHash($worker_map_key, $key);
                if ($worker_parameters) {
                    $warnings[] = 'setWorkerParameterValueHash. Нашел воркер параметер айди в кеше';
                    $worker_parameter_id = $worker_parameters['worker_parameter_id'];
                } else {
                    $warnings[] = 'setWorkerParameterValueHash. В кеше не было ищу в базе';
                    $response = WorkerMainController::getOrSetWorkerParameter($worker_id, $parameter_id, $parameter_type_id);
                    if ($response['status'] == 1) {
                        $worker_parameter_id = $worker_parameters['worker_parameter_id'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        $warnings[] = 'setWorkerParameterValueHash. Нашел в базе данных/или создал в БД';
                    } else {
                        $warnings[] = 'setWorkerParameterValueHash. В базе сенсор параметер айди не нашел. генерирую исключение';
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new Exception("setWorkerParameterValueHash. Для сенсора $worker_id не существует привязки к нему параметра $parameter_id и типа параметра $parameter_type_id");
                    }
                }
            }
            $warnings[] = "setWorkerParameterValueHash. Воркер параметер айди = $worker_parameter_id";
            $warnings[] = 'setWorkerParameterValueHash. Начинаю сохранение в кеш';
            $worker_parameter_values['worker_id'] = $worker_id;
            $worker_parameter_values['worker_parameter_id'] = $worker_parameter_id;
            $worker_parameter_values['value'] = $value;
            $worker_parameter_values['parameter_id'] = $parameter_id;
            $worker_parameter_values['parameter_type_id'] = $parameter_type_id;
            $worker_parameter_values['status_id'] = $status_id;
            $worker_parameter_values['date_time'] = $date_time;

            $key = $this->buildParameterKeyHash($parameter_id, $parameter_type_id);
            $this->amicum_rSetHash($worker_map_key, $key, $worker_parameter_values);
            $warnings[] = 'setWorkerParameterValueHash. Сохранил в кеш';
            unset($worker_parameter_values);
            $status *= 1;
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'setWorkerParameterValueHash. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'setWorkerParameterValueHash. Закончил выполнение метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /******************************* Методы добавления работника  в WorkerCheckInMine *********************************/

    /**
     * Название метода: buildWorkerCheckInKey() - метод создания ключа кэша зачек... работников по шахте
     * Назначение метода:
     *
     * Входные обязательные параметры:
     * @param $mine_id - идентификатор шахты
     * @param $worker_id - идентификатор работника
     *
     * @return string созданный ключ для кэше WorkerCheckInMine
     *
     * @package backend\controllers\cachemanagers
     *
     * @example $this->buildWorkerCheckInKey(290, 4545);
     * @example $this->buildWorkerCheckInKey('*', '*');
     * @example $this->buildWorkerCheckInKey(290, '*');
     * @example $this->buildWorkerCheckInKey('*', 4545);
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 9:34
     */
    public function buildWorkerCheckInKey($mine_id, $worker_id)
    {
        return self::$worker_check_in_cache_key . ':' . $mine_id . ':' . $worker_id;
    }

    /**
     * Название метода: multiGetWorkerCheckMine()
     * Назначение метода: метод получения всех/конкретного работника из кэша WorkerCheckInMine.
     * Если указать $mine_id = '*' или $worker_id = '*', то выборка будет по всем ИД указаным в параметре
     *
     * Входные необязательные параметры
     * @param string $mine_id - идентификатор шахты
     * @param string $worker_id - идентификатор работника
     * @return array|bool возвращает многомерный массив если есть данные, иначе возвращает false
     * @package backend\controllers\cachemanagers
     *
     * @example $this->multiGetWorkerCheckMine() - получение всех работником по всем шахтам
     * @example $this->multiGetWorkerCheckMine(290) - получение всех работником по конкретной шахте
     * @example $this->multiGetWorkerCheckMine(290, 4545) - получение работника по конкретной шахте и ИД
     * @example $this->multiGetWorkerCheckMine('*', 4545) - получение/поиск работника из всех шахт
     *
     * Документация на портале:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 9:40
     */
    public function multiGetWorkerCheckMine($mine_id = '*', $worker_id = '*')
    {
        $worker_check_in_key = $this->buildWorkerCheckInKey($mine_id, $worker_id);
        $worker_check_in_keys = $this->redis_cache->scan(0, 'MATCH', $worker_check_in_key, 'COUNT', '10000000')[1];
        if ($worker_check_in_keys) {
            return $this->amicum_mGet($worker_check_in_keys);
        }
        return false;
    }

    public function multiGetWorkerCheckMineHash($mine_id)
    {
        $mine_map_key = self::buildMineMapKeyHash($mine_id);
        return $this->amicum_rGetMapHash($mine_map_key);
    }

    /**
     * Название метода: delWorkerCheckMine()
     * Назначение метода: метод удаления работника из списка зачекининых работников по шахте из кэша WorkerCheckInMine
     * Метод ничего не возвращает
     * Входные необязательные параметры
     * @param string $mine_id - идентификатор шахты. Если указать $mine_id = '*' - удалет из всех шахт
     * @param string $worker_id - идентификатор работника. Если указать $worker_id = '*' - всех работников из кэша WorkerCheckInMine
     * @return  bool - результат выполнения метода
     * @package backend\controllers\cachemanagers
     * @example $this->delWorkerCheckMine() - очистка кэша WorkerCheckInMine
     * @example $this->delWorkerCheckMine(290) - очистка кэша WorkerCheckInMine по конкретной шахте
     * @example $this->delWorkerCheckMine(290б 45654) - удаление работника из кэша WorkerCheckInMine по конкретной шахте
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 9:59
     */
    public function delWorkerCheckMine($mine_id = '*', $worker_id = '*')
    {
        $worker_checkInKey = $this->buildWorkerCheckInKey($mine_id, $worker_id);
        $worker_checkInKeys = $this->redis_cache->scan(0, 'MATCH', $worker_checkInKey, 'COUNT', '10000000')[1];
        if ($worker_checkInKeys) {
            return $this->amicum_mDel($worker_checkInKeys);
        }
        return false;
    }

    public function delWorkerCheckMineHash($mine_id)
    {
        $mine_map_key = self::buildMineMapKeyHash($mine_id);
        return $this->amicum_rDelMapHash($mine_map_key);
    }


    /**
     * Название метода: multiSetWorkerParameterValue()
     * Назначение метода: Метод массовой вставки массива параметров и их значений в кеш
     *
     * Входные не обязательные параметры:
     * @param int $worker_id - идентификатор конкретного сенсора.
     * @param array $worker_parameter_values - массив параметров и их значений
     * @return  array массив данных
     * @package backend\controllers\cachemanagers
     *
     *
     * @author Якимов М.Н.
     * Created date: on 28.05.2019 10:18
     */
    public function multiSetWorkerParameterValue($worker_parameter_values, $worker_id = '')
    {

        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = "multiSetWorkerParameterValue. Начал выполнять метод";
        try {
            $warnings[] = "multiSetWorkerParameterValue. Нашел параметры в БД начинаю инициализировать кеш";///???
            foreach ($worker_parameter_values as $worker_parameter_value) {
                $wpv_key = $this->buildParameterKey($worker_parameter_value['worker_id'], $worker_parameter_value['parameter_id'], $worker_parameter_value['parameter_type_id']);

                $worker_paramter_value_array[$wpv_key] = $worker_parameter_value;
            }
            $this->amicum_mSet($worker_paramter_value_array);
            unset($worker_paramter_value_array);
            unset($worker_parameter_values);

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "multiSetWorkerParameterValue. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "multiSetWorkerParameterValue. Выполнение метода закончил";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    public function multiSetWorkerParameterValueHash($worker_parameter_values, $worker_id = '')
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = "multiSetWorkerParameterValueHash. Начал выполнять метод";
        try {
            $warnings[] = "multiSetWorkerParameterValueHash. Нашел параметры в БД начинаю инициализировать кеш";///???
            foreach ($worker_parameter_values as $worker_parameter_value) {
                $wpv_key = $this->buildParameterKeyHash($worker_parameter_value['parameter_id'], $worker_parameter_value['parameter_type_id']);
                $worker_map_key = self::buildWorkerMapKeyHash($worker_parameter_value['worker_id']);
                $worker_paramter_value_array[$worker_map_key][$wpv_key] = $worker_parameter_value;
            }
            $this->amicum_mSetHash($worker_paramter_value_array);
            unset($worker_paramter_value_array);
            unset($worker_parameter_values);

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "multiSetWorkerParameterValueHash. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "multiSetWorkerParameterValueHash. Выполнение метода закончил";
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // buildStructureWorkerParametersValue - Метод создания структуры значения параметра работника в кеше
    // сделан, что бы легче было создавать массив для массовой вставки
    // разработал: Якимов М.Н.
    public static function buildStructureWorkerParametersValue($worker_id, $worker_parameter_id, $parameter_id, $parameter_type_id, $date_time, $parameter_value, $status_id)
    {
        $worker_parameter_value_to_cache['worker_id'] = $worker_id;
        $worker_parameter_value_to_cache['worker_parameter_id'] = $worker_parameter_id;
        $worker_parameter_value_to_cache['parameter_id'] = $parameter_id;
        $worker_parameter_value_to_cache['parameter_type_id'] = $parameter_type_id;
        $worker_parameter_value_to_cache['date_time'] = $date_time;
        $worker_parameter_value_to_cache['value'] = $parameter_value;
        $worker_parameter_value_to_cache['status_id'] = $status_id;
        return $worker_parameter_value_to_cache;
    }

    // buildStructureWorker - Метод создания структуры работника в кеше
    // сделан, что бы легче было создавать массив для массовой вставки
    // разработал: Якимов М.Н.
    public static function buildStructureWorker($worker_id, $worker_object_id, $object_id, $stuff_number, $full_name, $mine_id, $position_title, $department_title, $gender)
    {
        $worker_to_cache['worker_id'] = $worker_id;
        $worker_to_cache['worker_object_id'] = $worker_object_id;
        $worker_to_cache['object_id'] = $object_id;
        $worker_to_cache['stuff_number'] = $stuff_number;
        $worker_to_cache['full_name'] = $full_name;
        $worker_to_cache['position_title'] = $position_title;
        $worker_to_cache['department_title'] = $department_title;
        $worker_to_cache['gender'] = $gender;
        $worker_to_cache['mine_id'] = $mine_id;
        return $worker_to_cache;
    }

    /**
     * addWorker - Метод добавлния работника в кэш(БЕЗ ПАРАМЕТРОВ)
     * Принимает массив параметров:
     * mine_id
     * sensor_id
     * Created by: Якимов М.Н.
     * @since 09.04.2019 Переписан метод для обычного добавления/замены сенсора. Сырцев А.П.
     */
    public function addWorker($worker)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = "addWorker. Начал выполнять метод";
        try {
            $cache_key = self::buildWorkerMineKey($worker['mine_id'], $worker['worker_id']);
            $set_result = $this->amicum_rSet($cache_key, $worker);
            if (!$set_result) {
                $errors[] = "addWorker. Добавляемый работника в главный кеш: ";
                $errors[] = $worker;
                throw new Exception("addWorker. Не смог добавить работника в главный кеш работников");
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "addWorker. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "addWorker. Выполнение метода закончил";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    public function addWorkerHash($worker)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = 'addWorkerHash. Начал выполнять метод';
        try {
            $mine_map_key = self::buildMineMapKeyHash($worker['mine_id']);
            $set_result = $this->amicum_rSetHash($mine_map_key, $worker['worker_id'], $worker);
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'addWorkerHash. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'addWorkerHash. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // amicum_mGet - метод получения данных с редис за один раз методами редиса
    public function amicum_mGet($keys)
    {
        $mgets = $this->redis_cache->executeCommand('mget', $keys);
        if ($mgets) {
            foreach ($mgets as $mget) {
                $result[] = unserialize($mget)[0];
            }
            return $result;
        }
        return false;
    }

    public function amicum_rGetMapHash($key)
    {
        $key1[] = $key;
        $mgets = $this->redis_cache->executeCommand('hvals', $key1);
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
    public function amicum_mSet($items, $dependency = null)
    {
        $data = [];
        foreach ($items as $key => $value) {
            $value = serialize([$value, $dependency]);
            $data[] = $key;
            $data[] = $value;
        }
        $msets = $this->redis_cache->executeCommand('mset', $data);

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'mset', $data);
        }

        return $msets;
    }

    public function amicum_mSetHash($items)
    {
        $msets = 0;
        foreach ($items as $map_key => $values) {
            $data[] = $map_key;
            foreach ($values as $key => $value) {
                $data[] = $key;
                $data[] = serialize($value);
            }

            $this->redis_cache->executeCommand('hset', $data);

            if (REDIS_REPLICA_MODE === true) {
                $this->amicum_repRedisHash(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'hset', $data);
            }
            $data = [];
        }

        return $msets;
    }

    /**
     * amicum_mSet - Метод вставки значений в кэш командами редиса. Аналогичен методу set(), только ключи не преобразуются в какой-либо формат,
     * они добавляюся как есть
     */
    public function amicum_rSet($key, $value, $dependency = null)
    {
        $value = serialize([$value, $dependency]);
        $data[] = $key;
        $data[] = $value;

        $msets = $this->redis_cache->executeCommand('set', $data);

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'set', $data);
        }

        return $msets;
    }

    public function amicum_rSetHash($map_key, $key, $value, $dependency = null)
    {

        $data[] = $map_key;
        $data[] = $key;
        $data[] = serialize($value);

        $msets = $this->redis_cache->executeCommand('hset', $data);

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedisHash(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'hset', $data);
        }

        return $msets;
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
    public function amicum_rGet($key)
    {
        $key1[] = $key;
        $value = $this->redis_cache->executeCommand('get', $key1);

        if ($value) {
            $value = unserialize($value)[0];
            return $value;
        }
        return false;
    }

    public function amicum_rGetHash($map, $key)
    {
        $key1[] = $map;
        $key1[] = $key;
        $value = $this->redis_cache->executeCommand('hget', $key1);

        if ($value) {
            $value = unserialize($value);
            return $value;
        }
        return false;
    }

    /**
     * Метод удаления по указанным ключам
     */
    public function amicum_mDel($keys)
    {
        //Todo: сделать проверку в будущем на возвращаемые из redis
        if ($keys) {
            foreach ($keys as $key) {
                $key1 = array();
                $key1[] = $key;
                $value = $this->redis_cache->executeCommand('del', $key1);

                if (REDIS_REPLICA_MODE === true) {
                    $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'del', $key1);
                }
            }
            return true;
        }
        return false;
    }

    public function amicum_mDelHash($map_keys)
    {
        //Todo: сделать проверку в будущем на возвращаемые из redis
        if ($map_keys) {
            foreach ($map_keys as $key_idx => $map_key) {
                $key1[] = $key_idx;

                foreach ($map_key as $key) {
                    $key1[] = $key;
                }
                $value = $this->redis_cache->executeCommand('hdel', $key1);

                if (REDIS_REPLICA_MODE === true) {
                    $this->amicum_repRedisHash(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'hdel', $key1);
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
    public function amicum_rDel($key)
    {
        $key1[] = $key;
        $value = $this->redis_cache->executeCommand('del', $key1);
        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'del', $key1);
        }
    }

    public function amicum_rDelHash($map, $key)
    {
        $key1[] = $map;
        $key1[] = $key;
        $value = $this->redis_cache->executeCommand('hdel', $key1);

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedisHash(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'hdel', $key1);
        }
    }

    public function amicum_rDelMapHash($map)
    {
        $key1[] = $map;
        $value = $this->redis_cache->executeCommand('del', $key1);

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedisHash(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'del', $key1);
        }
        return $value;
    }

    // amicum_flushall - метод очистки кеша работников
    public function amicum_flushall()
    {
        $this->redis_cache->executeCommand('flushall');

        if (REDIS_REPLICA_MODE === true) {
            // главный кеш
            $redis_replica = new yii\redis\Connection();
            $redis_replica->hostname = REDIS_REPLICA_HOSTNAME;
            $redis_replica->port = $this->redis_cache->port;
            $redis_replica->executeCommand('flushall');
        }
    }
}
