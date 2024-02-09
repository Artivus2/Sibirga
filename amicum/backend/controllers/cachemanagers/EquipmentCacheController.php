<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\cachemanagers;

use backend\controllers\EquipmentBasicController;
use backend\controllers\EquipmentMainController;
use Exception;
use frontend\models\Equipment;
use Throwable;
use Yii;
use yii\db\Query;

/**
 * Класс по работе с кэшем оборубования. Логику по работе с БД включать в этот метод нельзя.
 * Этот класс ИСКЛЮЧИТЕЛЬНО по работе с кэшем оборудования.
 * Другие лишние методы не связанные с кэшом нельзя добавить
 * Class EquipmentCacheController
 * @package backend\controllers\cachemanagers
 */
class EquipmentCacheController implements ObjectCacheInterface
{

    // buildStructureEquipment                  - Метод создания структуры оборудования в кеше
    // buildStructureEquipmentParametersValue   - Метод создания структуры значения параметра оборудования в кеше
    // buildStructureEquipmentParameterSensor   - Метод создания структуры привязки оборудования по параметрам к сенсору  в кеше
    // buildStructureEquipmentSensor            - Метод создания структуры привязки оборудования к сенсору  в кеше
    // buildEquipmentKey                        - Метод создания главного ключа оборудования
    // buildParameterKey                        - Метод создания ключа параметра оборудования
    // buildSensorEquipmentParameterKey         - метод создания ключа для списка сенсоров привязанных к оборудования с парамтерами
    // buildSensorEquipmentKey                  - метод создания ключа для списка оборудования привязанных к оборудования
    // setSensorEquipment                       - метод привязки/редактирования привязки метки к оборудованию.
    // getSensorEquipment                       - получение привязанного сенсора к оборудованию по ИД сенсору или по ИД оборудования
    // delSensorEquipment                       - метод удаление привязки сенсора к оборудованию по сенсору
    // setSensorEquipmentParameter              - метод привязки/редактирования привязки метки к оборудованию с учетом параметера.
    // getSensorEquipmentParameter              - получение привязанного сенсора к оборудованию с у четом параметра по ИД сенсору или по ИД оборудования
    // delSensorEquipmentParameter              - метод удаление привязки сенсора к оборудованию с учетом параметра по сенсору
    // getEquipmentMineByEquipment              - Метод получения оборудования по equipment_id из кэша redis
    // getEquipmentMineByEquipmentOne           - Получение информации об оборудовании из кеша EquipmentMine
    // delInEquipmentMine                       - Метод удаления оборудования из списка оборудования по шахте из кэша
    // addEquipment                             - Метод добавлния оборудования в кэш(БЕЗ ПАРАМЕТРОВ)
    // initEquipmentSensor                      - Метод привязки параметров оборудования к сенсорам - нужен для системы позиционирования
    // initEquipmentParameterSensor             - Метод привязки параметров оборудования по параметрам к сенсорам   - нужен для работы системы OPC
    // multiSetEquipmentParameterSensor         - Метод массовой вставки массива привязок оборудования по парамтерам к сенсору
    // multiSetEquipmentSensor                  - Метод массовой вставки массива привязок оборудования к сенсору
    // getParameterValue                        - Метод получения значения конкретного парамтера оборудования кэша SensorParameter
    // multiGetParameterValue                   - Метод получения значения параметров оборудования из кэша redis (групповое получение).
    // amicum_flushall                          - метод очистки кеша оборудования

    // amicum_mGet                          - метод получения данных с редис за один раз методами редиса
    // amicum_mSet                          - Метод вставки значений в кэш командами редиса.

    /**
     * Методы в классе (основные методы, которые чаще всего используются):
     *
     * $this->runInit() - Метод полной инициализации кэша работников по шахте и со всеми значениями параметров
     * $this->initEquipmentMain() - метод инициализации кэша списка оборудований по шахте(EquipmentMine)
     * $this->initEquipmentParameterValue() - метод инициализации вычисляемых значений параметров оборудований в кэш EquipmentParameter
     * $this->initEquipmentParameterHandbookValue() - метод инициализации справочных значений параметров оборудований в кэш EquipmentParameter
     *
     * Методы по работе со списом оборудований по шахте EquipmentMine:
     * $this->getSetEquipmentMine() - Метод добавления/редатирования значения параметра оборудования в списке оборудований по шахте в кэше EquipmentMine.
     * $this->getEquipmentMine() - Метод получения оборудования из списка оборудований по шахте EquipmentMine
     * $this->multiGetEquipmentMine() - Расширенный метод получения оборудований(я) из списка оборудований по шахте EquipmentMine
     * $this->delEquipmentMine() - Метод удаления оборудования из списка оборудований по шахте из кэша EquipmentMine
     *
     * Методы по работе со значениями параметров выработки EquipmentParameter:
     * $this->setParameterValue() - метод добавления/редактирования значения параметра оборудования
     * $this->getParameterValue() - метод получения значения параметра оборудования по конкретным данным
     * $this->multiGetParameterValue() - расширенный метод поиска/получения значения параметров оборудования(й).
     * $this->delParameterValue() - метод очистки(удаления параметров оборудований(оборудования)) кэша (из кэша) EquipmentParameter.
     *
     * EquipmentCacheController::removeAll() - Метод полного удаления кэша оборудований. Очищает все кэши связанные с оборудованиями
     *
     * Не все методы могут быть перечислены в этом списке!!!
     * Более подробно прочитать документацию для конкретного метода
     */

    public $redis_cache;
    public static $equipment_mine_cache_key = 'EqMi';                               // кеш самого оборудования
    public static $equipment_parameter_cache_key = 'EqPa';                          // кеш параметров оборудования
    public static $equipment_sensor_cache_key = 'SeEq';                             // кеш привязки оборудования к меткам системы позиционирования
    public static $equipment_parameter_sensor_cache_key = 'SePaEq';                 // кеш привязки оборудования к сенсорам для работы OPC
    public static $mine_map_cache_key = 'WMiMap';
    public static $equipment_map_cache_key = 'EqMap';

    public function __construct()
    {
        $this->redis_cache = Yii::$app->redis_equipment;
    }

    /**
     * Название метода: runInit()
     * Назначение метода: Метод полной инициализации кэша оборудования по шахте и со всеми значениями параметров
     * @param int $mine_id - идентификатор шахты
     * @return array $result - массив рузельтата выполнения метода. Сами данные не возвращает
     *
     * @package backend\controllers\cachemanagers
     * порядок очень важен!!!!!
     * Метод инициализирует следующие кэши:
     * 1. EquipmentMine
     * 2. EquipmentParameter значения value
     * 3. EquipmentParameter значения handbook
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
            /**
             * Порядок очень важен!!!!!
             */
//            $status['initEquipmentParameterValue'] = $this->initEquipmentParameterValue();                                                                        // инициализируем кэш списка оборудований со значениями value
//            $status['initEquipmentParameterHandbookValue'] = $this->initEquipmentParameterHandbookValue();                                                                // инициализируем кэш списка оборудований со значениями handbook
//            $status['initEquipmentMain'] = $this->initEquipmentMain($mine_id);
//            $this->removeAll();
            $this->initEquipmentParameterValue();                                                                        // инициализируем кэш списка оборудований со значениями value
            $this->initEquipmentParameterHandbookValue();                                                                // инициализируем кэш списка оборудований со значениями handbook
            $this->initEquipmentMain($mine_id);                                                                            // инициализируем кэш списка оборудований по шахте
            $this->initEquipmentSensor();
            $this->initEquipmentParameterSensor();

        } else $errors[] = "Идентификатор шахты не передан. Ошибка инициализации кэша оборудований";
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

            if ($this->initEquipmentParameterValueHash()) {                                                                     // инициализируем кэш списка работников со значениями value
                $status['initEquipmentParameterValueHash'] = true;
            } else {
                $status['initEquipmentParameterValueHash'] = false;
            }

            if ($this->initEquipmentParameterHandbookValueHash()) {                                                             // инициализируем кэш списка работников со значениями handbook
                $status['initEquipmentParameterHandbookValueHash'] = true;
            } else {
                $status['initEquipmentParameterHandbookValueHash'] = false;
            }

            $status['initEquipmentMineHash'] = $this->initEquipmentMineHash($mine_id);                                                // инициализируем кэш списка работников по шахте

            if ($this->initEquipmentSensor()) {
                $status['initEquipmentSensor'] = true;
            } else {
                $status['initEquipmentSensor'] = false;
            }

            if ($this->initEquipmentParameterSensor()) {
                $status['initEquipmentParameterSensor'] = true;
            } else {
                $status['initEquipmentParameterSensor'] = false;
            }
        } else $errors[] = "Идентификатор шахты не передан. Ошибка инициализации кэша работников";
        $result = array('errors' => $errors, 'status' => $status);
        unset($status);
        return $result;
    }

    /**
     * Название метода: delInEquipmentMine() -Метод удаления оборудования из списка оборудования по шахте из кэша
     * Назначение метода: Метод удаления оборудования из списка оборудования по шахте из кэша
     * Метод ничего не возвращает.
     *
     * Входные обязательные параметры:
     *
     * @param $mine_id - идентификатор шахты. По умолчанию оборудование с таким идентификатор ищется во всех шахтах и
     *     удаляется
     * @param $equipment_id - идентифкатор конкретного оборудования
     *
     * @package backend\controllers\cachemanagers
     * @example $this->delInSensorMine(290, 310)
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 27.05.2019 15:32
     */
    public function delInEquipmentMine($equipment_id, $mine_id = '*')
    {
        $redis_cache_key = self::buildEquipmentMineKey($mine_id, $equipment_id);
        $keys = $this->redis_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
        if ($keys) {
            $this->amicum_mDel($keys);
        }
    }

    public function delInEquipmentMineHash($equipment_id, $mine_id)
    {
        $mine_map_key = self::buildMineMapKeyHash($mine_id);
        $keys[$mine_map_key][] = $equipment_id;
        $this->amicum_mDelHash($keys);
        return true;
    }

    /**
     * Название метода: initEquipmentMain()
     * Назначение метода: метод инициализации кэша списка оборудования по шахте(EquipmentMine)
     * беруться только зачекиненые оборудования
     * Входные обязательные параметры:
     * @param int $mine_id - идентификатор шахты
     *
     * Входные необязательные параметры
     * @param int $equipment_id - идентификатор оборудования. Если указать конкретный, то только данные одного оборудования
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
    public function initEquipmentMain($mine_id, $equipment_id = -1)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $equipment_mine=array();
        $result = null;
        $warnings = array();
        $warnings[] = "initEquipmentMain. Начал выполнять метод. Заполняемая шахта $mine_id";
        try {
            $sql_filter = "";
            if ($equipment_id != -1) $sql_filter = "equipment.id = $equipment_id";
            $equipments = Equipment::find()
                ->select([
                    'equipment.id as equipment_id',
                    'equipment.title as equipment_title',
                    'equipment.parent_equipment_id as parent_equipment_id',
                    'object.id as object_id',
                    'object.title as object_title',
                    'object_type_id'
                ])
                ->innerJoin('object', 'object.id = equipment.object_id')
                ->where($sql_filter)
                ->asArray()
                ->all();
            if ($equipments) {
                $warnings[] = "initEquipmentMain. Список на заполнение кеша присутствует";
                $equipment_mine = array();
                foreach ($equipments as $equipment) {
                    $equipment_id = $equipment['equipment_id'];
                    $equipment_last_mine = $this->getParameterValue($equipment_id, 346, 2);        // получаем шахту
                    //$warnings[] = "initEquipmentMain. последняя шахта для $equipment_id:";
                    //$warnings[] =  $equipment_last_mine;
                    if ($equipment_last_mine) {
                        $equipment_mine_id = $equipment_last_mine['value'];
                        if ($equipment_mine_id == $mine_id) {
                            $equipment_id = $equipment['equipment_id'];
                            //$warnings[] = "initEquipmentMain. Оборудование должно быть добавлено $equipment_id";
                            $equipment_mine_info = array(
                                'equipment_id' => $equipment['equipment_id'],
                                'equipment_title' => $equipment['equipment_title'],
                                'parent_equipment_id' => $equipment['parent_equipment_id'],
                                'object_id' => $equipment['object_id'],
                                'object_title' => $equipment['object_title'],
                                'object_type_id' => $equipment['object_type_id'],
                                'mine_id' => $mine_id
                            );
                            $equipment_mine_key = self::buildEquipmentMineKey($mine_id, $equipment_id);
                            $date_to_cache[$equipment_mine_key] = $equipment_mine_info;
                            $equipment_mine[] = $equipment_mine_info;
                        }
                    } else {
                        //$warnings[] = "initEquipmentMain. Шахта для оборудования $equipment_id не задана" . $equipment['equipment_title'];
                    }
                }
                if (isset($date_to_cache)) {
                    $this->amicum_mSet($date_to_cache);
                }
            } else {
                $warnings[] = "initEquipmentMain. Список оборудования на инициализацию пуст";
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "initEquipmentMain. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "initEquipmentMain. Выполнение метода закончил";
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'equipment' => $equipment_mine);
    }

    public function initEquipmentMineHash($mine_id, $equipment_id = -1)
    {
        $sql_filter = "mine_id = $mine_id";
        if ($equipment_id != -1) $sql_filter .= " AND equipment_id = $equipment_id";

        $equipments = Equipment::find()
            ->select([
                'equipment.id as equipment_id',
                'equipment.title as equipment_title',
                'equipment.parent_equipment_id as parent_equipment_id',
                'object.id as object_id',
                'object.title as object_title',
                'object_type_id'
            ])
            ->innerJoin('object', 'object.id = equipment.object_id')
            ->where($sql_filter)
            ->asArray()
            ->all();

        if ($equipments) {
            $mine_map_key = self::buildMineMapKeyHash($mine_id);
            foreach ($equipments as $equipment) {
                $equipment_id = $equipment['equipment_id'];
                $equipment_data = array(
                    'equipment_id' => $equipment['equipment_id'],
                    'equipment_title' => $equipment['equipment_title'],
                    'parent_equipment_id' => $equipment['parent_equipment_id'],
                    'object_id' => $equipment['object_id'],
                    'object_title' => $equipment['object_title'],
                    'object_type_id' => $equipment['object_type_id'],
                    'mine_id' => $mine_id
                );

                $date_to_cache[$mine_map_key][$equipment_id] = $equipment_data;
            }
            $this->amicum_mSetHash($date_to_cache);
            return true;
        }
        return false;
    }

    /**
     * Название метода: initEquipmentParameterValue()
     * Назначение метода: метод инициализации вычисляемых значений параметров оборудования в кэш EquipmentParameter
     *
     * Входные необязательные параметры
     * @param $equipment_id - идентификатор оборудования. Если указать этот параметр, то берет данные для конкретного оборудования
     * и добавляет в кэш
     * @param $sql - условие для фильтра. Если указать этот параметр, то  $equipment_id не учитывается!!!!!
     *
     * @return mixed возвращает true при успешном добавлении в кэш, иначе false
     *
     *
     * @example $this->initEquipmentParameterValue();
     * @example $this->initEquipmentParameterValue(475);
     * @example $this->initEquipmentParameterValue(-1, 'equipment_id = 475');
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 31.05.2019 11:51
     */
    public function initEquipmentParameterValue($equipment_id = -1, $sql = '')
    {
        $sql_filter = '';
        if ($equipment_id !== -1) {
            $sql_filter .= "$equipment_id = $equipment_id";
        }

        if ($sql !== '') {
            $sql_filter = $sql;
        }
        $filter_parameter = '(162,338,337,163,165,169,225,226,161,160,104,82,190,188,189,4,118,308,275,227,309,307,391,446,458,86,104,168,530)';
        $equipment_parameter_values = (new Query())
            ->select([
                'equipment_id',
                'equipment_parameter_id',
                'parameter_id',
                'parameter_type_id',
                'date_time',
                'value',
                'status_id'
            ])
            ->from('view_initEquipmentParameterValue')
            ->where($sql_filter)
            ->andwhere('parameter_id not in ' . $filter_parameter)
            ->all();
        if ($equipment_parameter_values) {
            foreach ($equipment_parameter_values as $equipment_parameter_value) {
                $key = $this->buildParameterKey($equipment_parameter_value['equipment_id'], $equipment_parameter_value['parameter_id'], $equipment_parameter_value['parameter_type_id']);
                $equipment_parameter_value_array[$key] = $equipment_parameter_value;
            }
            $this->amicum_mSet($equipment_parameter_value_array);
            return $equipment_parameter_values;
        }
        return false;
    }


    public function initEquipmentParameterValueHash($equipment_id = -1, $sql = '')
    {
        $sql_filter = '';
        if ($equipment_id !== -1) {
            $sql_filter .= "equipment_id = $equipment_id ";
        }

        if ($sql !== '') {
            $sql_filter .= ' AND ' . $sql;
        }
        $filter_parameter = '(162,338,337,163,165,169,225,226,161,160,104,82,190,188,189,4,118,308,275,227,309,307,391,446,458,86,104,168,530)';
        $equipment_parameter_values = (new Query())
            ->select([
                'equipment_id',
                'equipment_parameter_id',
                'parameter_id',
                'parameter_type_id',
                'date_time',
                'value',
                'status_id'
            ])
            ->from('view_initEquipmentParameterValue')
            ->where($sql_filter)
            ->andwhere('parameter_id not in ' . $filter_parameter)
            ->all();
        if ($equipment_parameter_values) {
            foreach ($equipment_parameter_values as $equipment_parameter_value) {
                $key = $this->buildParameterKeyHash($equipment_parameter_value['parameter_id'], $equipment_parameter_value['parameter_type_id']);
                $equipment_map_key = self::buildEquipmentMapKeyHash($equipment_parameter_value['equipment_id']);
                $date_to_cache[$equipment_map_key][$key] = $equipment_parameter_value;
            }
            $this->amicum_mSetHash($date_to_cache);
            return $equipment_parameter_values;
        }
        return false;
    }

    /**
     * Название метода: initEquipmentParameterHandbookValue()
     * Назначение метода: метод инициализации справочных значений параметров оборудования в кэш EquipmentParameter
     *
     * Входные необязательные параметры
     * @param $equipment_id - идентификатор оборудования. Если указать этот параметр, то берет данные для конкретного оборудования
     * и добавляет в кэш
     * @param $sql - условие для фильтра. Если указать этот параметр, то  $equipment_id не учитывается!!!!!
     *
     * @return mixed возвращает true при успешном добавлении в кэш, иначе false
     *
     *
     * @example $this->initEquipmentParameterValue();
     * @example $this->initEquipmentParameterValue(475);
     * @example $this->initEquipmentParameterValue(-1, 'equipment_id = 475');
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 31.05.2019 11:59
     */
    public function initEquipmentParameterHandbookValue($equipment_id = -1, $sql = '')
    {
        $sql_filter = '';
        if ($equipment_id !== -1) {
            $sql_filter .= "$equipment_id = $equipment_id";
        }

        if ($sql !== '') {
            $sql_filter = $sql;
        }

        $filter_parameter = '(162,338,337,163,165,169,225,226,161,160,104,82,190,188,189,4,118,308,275,227,309,307,391,446,458,86,104,168,530)';

        $equipment_parameter_handbook_val = (new Query())
            ->select([
                'equipment_id',
                'equipment_parameter_id',
                'parameter_id',
                'parameter_type_id',
                'date_time',
                'value',
                'status_id'
            ])
            ->from('view_initEquipmentParameterHandbookValue')
            ->where($sql_filter)
            ->andwhere('parameter_id not in ' . $filter_parameter)
            ->all();
        if ($equipment_parameter_handbook_val) {
            foreach ($equipment_parameter_handbook_val as $equipment_parameter_value) {
                $key = $this->buildParameterKey($equipment_parameter_value['equipment_id'], $equipment_parameter_value['parameter_id'], $equipment_parameter_value['parameter_type_id']);
                $equipment_parameter_value_array[$key] = $equipment_parameter_value;
            }
            $this->amicum_mSet($equipment_parameter_value_array);
            return $equipment_parameter_handbook_val;
        }
        return false;
    }

    public function initEquipmentParameterHandbookValueHash($equipment_id = -1, $sql = '')
    {
        $sql_filter = '';
        if ($equipment_id !== -1) {
            $sql_filter .= "equipment_id = $equipment_id ";
        }

        if ($sql !== '') {
            $sql_filter .= ' AND ' . $sql;
        }

        $filter_parameter = '(162,338,337,163,165,169,225,226,161,160,104,82,190,188,189,4,118,308,275,227,309,307,391,446,458,86,104,168,530)';

        $equipment_parameter_handbook_val = (new Query())
            ->select([
                'equipment_id',
                'equipment_parameter_id',
                'parameter_id',
                'parameter_type_id',
                'date_time',
                'value',
                'status_id'
            ])
            ->from('view_initEquipmentParameterHandbookValue')
            ->where($sql_filter)
            ->andwhere('parameter_id not in ' . $filter_parameter)
            ->all();
        if ($equipment_parameter_handbook_val) {
            foreach ($equipment_parameter_handbook_val as $equipment_parameter_value) {
                $key = $this->buildParameterKeyHash($equipment_parameter_value['parameter_id'], $equipment_parameter_value['parameter_type_id']);
                $equipment_map_key = self::buildEquipmentMapKeyHash($equipment_parameter_value['equipment_id']);
                $date_to_cache[$equipment_map_key][$key] = $equipment_parameter_value;
            }
            $this->amicum_mSetHash($date_to_cache);
            return $equipment_parameter_handbook_val;
        }
        return false;
    }

    public function multiSetEquipmentParameterHash($value)
    {
        foreach ($value as $item) {
            $build_structure = self::buildStructureEquipmentParametersValue(
                $item['equipment_id'],
                $item['equipment_parameter_id'],
                $item['parameter_id'],
                $item['parameter_type_id'],
                $item['date_time'],
                $item['value'],
                $item['status_id']
            );
            $key = $this->buildParameterKeyHash($item['parameter_id'], $item['parameter_type_id']);
            $equipment_map_key = self::buildEquipmentMapKeyHash($item['equipment_id']);
            $data_for_cache[$equipment_map_key][$key] = $build_structure;
        }

        /******************* Добавление в кэш *******************/
        return $this->amicum_mSetHash($data_for_cache);
    }

    /**
     * Инициализация списка сенсоров, привязанных к оборудованию.
     * Получает данные из БД и добавляет в кэш
     *
     * @return array|bool возвращает список связей если все норм, иначе false
     *
     * @example
     * $equipment_cache = new EquipmentCacheController();
     * $equipment_cache->initEquipmentSensor();
     *
     * @author Сырцев А.П.
     * Created date: on 02.10.2019
     */
    public function initEquipmentSensor()
    {
        $equipments_sensors = (new Query())
            ->select([
                'sensor_id',
                'equipment_id'
            ])
            ->from('view_GetEquipmentBySensor')
            ->all();

        if (!$equipments_sensors) {
            return false;
        }

        $date_to_cache = [];
        foreach ($equipments_sensors as $equipment_sensor) {
            $sensor_equipment_cache_key = $this->buildSensorEquipmentKey($equipment_sensor['sensor_id'], $equipment_sensor['equipment_id']);
            $date_to_cache[$sensor_equipment_cache_key]=$equipment_sensor;
        }
        $this->amicum_mSet($date_to_cache);
        return $equipments_sensors;
    }
/**
     * Инициализация списка сенсоров, привязанных к оборудованию по параметрам.
     * Получает данные из БД и добавляет в кэш
     *
     * @return array|bool возвращает список связей если все норм, иначе false
     *
     * @example
     * $equipment_cache = new EquipmentCacheController();
     * $equipment_cache->initEquipmentSensor();
     *
     * @author Сырцев А.П.
     * Created date: on 02.10.2019
     */
    public function initEquipmentParameterSensor()
    {
        $equipments_sensors = (new Query())
            ->select([
                'sensor_id',
                'equipment_id',
                'parameter_id',
                'equipment_parameter_id'
            ])
            ->from('view_GetEquipmentParameterBySensor')
            ->all();

        if (!$equipments_sensors) {
            return false;
        }

        $date_to_cache = [];
        foreach ($equipments_sensors as $equipment_sensor) {
            $sensor_equipment_cache_key = $this->buildSensorEquipmentParameterKey($equipment_sensor['sensor_id'], $equipment_sensor['equipment_id'], $equipment_sensor['parameter_id']);
            $date_to_cache[$sensor_equipment_cache_key]=$equipment_sensor;
        }
        $this->amicum_mSet($date_to_cache);
        return $equipments_sensors;
    }


    /**
     * Название метода: setEquipmentMine()
     * Назначение метода: метод добавления оборудования в списке оборудования по шахте в кэш
     *
     * Входные обязательные параметры:
     * @param $value - массив данных для добавления оборудования в кэш списка оборудования. В массиве обязятельно должны быть
     * указаны $value['mine_id'] и $value['equipment_id'], иначе данные не добавляются в кэш
     *
     * @return bool - если данные успешно добавились, то возвращает true, иначе false
     *
     * @example $this->addEquipmentMine($value)
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 31.05.2019 14:59
     */
    public function setEquipmentMine($value)
    {
        if (isset($value['mine_id'], $value['equipment_id'])) {
            $cache_key = self::buildEquipmentMineKey($value['mine_id'], $value['equipment_id']);
            return $this->amicum_rSet($cache_key, $value);
        }
        return false;
    }

    /**
     * Название метода: getSetSensorMine()
     * Назначение метода: Метод редатирования значения параметра оборудования в списке оборудования по шахте в кэше EquipmentMine.
     * В кэше по ключу EquipmentMine хранятся данные place_id, xyz и тд. Этот метод используется именно для редактирования
     * конкретного поля в списке оборудования по шахте
     *
     * Входные обязательные параметры:
     * @param $equipment_id - идентификатор оборудования
     * @param $value - значение
     * @param $change_param_name - название параметра, для которого нужно менять конкретное значение.
     * Напрмер: Мы меняем параметр 122 для конкретного оборудования. Параметр 122 это хранится как place_id, поэтому
     * $change_param_name = 'place_id'
     *
     * Входные необязательные параметры
     * @param $mine_id - идентификатор шахты. По умолчанию = '*' (все). Если указать конкретный, то оборудования ищется в конкретной шахте
     *
     * @example $this->getSetEquipmentMine(2052227, 'привет', 'xyz', 290);
     * @example $this->getSetEquipmentMine(2052227, '4575,54468,789', 'xyz');
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 03.06.2019 16:02
     */
    public function getSetEquipmentMine($equipment_id, $value, $change_param_name, $mine_id = '*')
    {
        //Todo переделать метод
        $equipment_mine_key = self::buildEquipmentMineKey($mine_id, $equipment_id);
        $key = $this->redis_cache->scan(0, 'MATCH', $equipment_mine_key, 'COUNT', '10000000')[1];
        if ($key) {
            $equipment_mine = $this->amicum_mGet($key);
            if ($equipment_mine) {
                $equipment_mine[0][$change_param_name] = $value;
                $this->amicum_rSet($key[0], $equipment_mine[0]);
            }
        }
    }

    /**
     * Название метода: setParameterValue()
     * Назначение метода: метод добавления значения для параметра оборудования
     *
     * Входные обязательные параметры:
     * @param $equipment_id - идентификатор оборудования
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
    public function setParameterValue($equipment_id, $value)
    {
        if (isset($value['parameter_id'], $value['parameter_type_id'])) {
            $key = $this->buildParameterKey($equipment_id, $value['parameter_id'], $value['parameter_type_id']);
            return $this->amicum_rSet($key, $value);
        }
        return false;
    }

    public function setParameterValueHash($equipment_id, $value)
    {
        if (isset($value['parameter_id'], $value['parameter_type_id'])) {
            $key = $this->buildParameterKeyHash($value['parameter_id'], $value['parameter_type_id']);
            $equipment_map_key = self::buildEquipmentMapKeyHash($equipment_id);
            $this->amicum_rSetHash($equipment_map_key, $key, $value);
            return true;
        }
        return false;
    }

    /**
     * @param $mine_id - идентификатор шахты. Если указать '*', то возвращает все шахты
     * @param $equipment - идентификатор оборудования. Если указать '*', то возвращает всех оборудования. По умолчанию всех оборудования.
     * @return array|bool - массив данных, если они есть, иначе false.
     *
     * @package backend\controllers\cachemanagers
     * Название метода: getEquipmentMine()
     * Назначение метода: Метод получения данные оборудования(ов) по шахте(по шахтам) из кэша redis.
     *
     * Входные обязательные параметры:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 31.05.2019 10:12
     */
    public function getEquipmentMine($mine_id, $equipment = '*')
    {
        $redis_cache_key = self::buildEquipmentMineKey($mine_id, $equipment);
        $keys = $this->redis_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];

        if ($keys) {
            $equipments = $this->amicum_mGet($keys);
            return $equipments;
        }
        return false;
    }

    public function getEquipmentMineHash($mine_id, $equipment_id = '*')
    {
        $mine_map_key = self::buildMineMapKeyHash($mine_id);
        $equipments = $this->amicum_rGetMapHash($mine_map_key);

        if ($equipments and $equipment_id != '*') {
            foreach ($equipments as $equipment) {
                if ($equipment['equipment_id'] == $equipment_id) {
                    $result[] = $equipment;
                }
            }
        } else {
            $result = $equipments;
        }
        if (!isset($result)) {
            return false;
        }
        return $result;
    }

    /**
     * Название метода: getParameterValue()
     * Назначение метода: getParameterValue - Метод получения значения конкретного парамтера оборудования кэша SensorParameter
     * В этом методе нельзя получать данные указываю * (звездочку)
     * Входные обязательные параметры:
     * @param $equipment_id - идентификатор оборудования
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
    public function getParameterValue($equipment_id, $parameter_id, $parameter_type_id)
    {
        $key = $this->buildParameterKey($equipment_id, $parameter_id, $parameter_type_id);
        return $this->amicum_rGet($key);
    }

    public function getParameterValueHash($equipment_id, $parameter_id, $parameter_type_id)
    {
        $key = $this->buildParameterKeyHash($parameter_id, $parameter_type_id);
        $equipment_map_key = self::buildEquipmentMapKeyHash($equipment_id);
        return $this->amicum_rGetHash($equipment_map_key, $key);
    }

    /**
     * Название метода: multiGetParameterValue()
     * Назначение метода: multiGetParameterValue - Метод получения значения параметров оборудования из кэша redis (групповое получение).
     * Можно получить данные по разному. Если нужно выбрать любой сенсор, или параметр,
     * или тип параметра, необходимо указать '*'.
     *
     * Входные обязательные параметры:
     *
     * @param $equipment_id - идентификатор оборудования. Если указать '*', то возвращает все сенсоры
     * @param $parameter_id - идентификатор параметра. Если указать '*', то возвращает все параметры
     * @param $parameter_type_id - идентификатор типа параметра. Если указать '*', то возвращает все типы параметра
     *
     * @return bool/array результат выполнения метода. Если данные есть, то массив данных, иначе false;
     * Возвращает многомерный массив
     *
     * Напрмиер:
     * 1. Получить оборудования с id = 310 со всеми параметрами
     *    (new EquipmentCacheController())->getParameterValue('310', '*', '*')
     * 2. Получить оборудования id = 310 c параметров 83 и тип параметра любой
     *  (new EquipmentCacheController())->getParameterValue('310', '83', '*')
     * 3. Получить оборудования id = 310 c параметров 83 и тип параметра 2
     *  (new EquipmentCacheController())->getParameterValue('310', '83', '2')
     * 4. Получить всех оборудования c параметров 83 и тип параметра 2
     *  (new EquipmentCacheController())->getParameterValue('*', '83', '2')
     *
     *
     * @package backend\controllers\cachemanagers
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 31.05.2019 12:05
     * @since ver
     */
    public function multiGetParameterValue($equipment_id = '*', $parameter_id = '*', $parameter_type_id = '*')
    {
        $redis_cache_key = $this->buildParameterKey($equipment_id, $parameter_id, $parameter_type_id);
        $keys = $this->redis_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
        if ($keys) {
            return $this->amicum_mGet($keys);
        }
        return false;
    }

    public function multiGetParameterValueHash($equipment_id = '*', $parameter_id = '*', $parameter_type_id = '*', $take_not_reference = false)
    {
        $result = [];
        if ($equipment_id == '*') {
            if ($parameter_type_id != 1) {
                $wpv = EquipmentBasicController::getEquipmentParameterValue($equipment_id, $parameter_id, $parameter_type_id);
                if ($wpv) {
                    $result = $wpv;
                }
            }
            if ($parameter_type_id != 2 and $parameter_type_id != 3) {
                $wphv = EquipmentBasicController::getEquipmentParameterHandbookValue($equipment_id, $parameter_id);
                if ($wphv) {
                    $result = array_merge($result, $wphv);
                }
            }
        } else {
            $equipment_map_key = self::buildEquipmentMapKeyHash($equipment_id);
            $equipment_parameter_values = $this->amicum_rGetMapHash($equipment_map_key);

            if (!$equipment_parameter_values) {
                return false;
            }

            if ($equipment_parameter_values and $parameter_id != '*' and $parameter_type_id != '*') {
                foreach ($equipment_parameter_values as $equipment_parameter_value) {
                    if ($equipment_parameter_value['parameter_id'] == $parameter_id and $equipment_parameter_value['parameter_type_id'] == $parameter_type_id) {
                        $result[] = $equipment_parameter_value;
                    }
                }
            } else if ($equipment_parameter_values and $parameter_id != '*') {
                foreach ($equipment_parameter_values as $equipment_parameter_value) {
                    if ($equipment_parameter_value['parameter_id'] == $parameter_id) {
                        $result[] = $equipment_parameter_value;
                    }
                }
            } else if ($equipment_parameter_values and $parameter_type_id != '*') {
                foreach ($equipment_parameter_values as $equipment_parameter_value) {
                    if ($equipment_parameter_value['parameter_type_id'] == $parameter_type_id) {
                        $result[] = $equipment_parameter_value;
                    }
                }
            } else {
                $result = $equipment_parameter_values;
            }
        }
        return $result;
    }

    /**
     * Название метода: multiGetParameterValueByParameters()
     * Назначение метода: Метод получения оборудования(а) по указанным параметрам из кэша.
     *
     * Входные параметры:
     *
     * @param string $equipment_id - идентификатор оборудования. Если указать $equipment_id = '*', то поиск будет по всем оборудования, и
     * возращает данные для всех оборудования. по умолчанию $equipment_id = '*'. Не рекомендуется использовать в цикле
     * @param string $parameters - по умолчанию все параметры берутся. Если указать конкретный, то получает конкретный
     * параметр. Параметры можно указать разделя их двоеточием (:) и запятым (,). Сперва надо указать параметр, а потом
     * тип параметра. Например: $parameters = '122:2,83:2,164:3'; Параметр и тип параметра разделяются двоеточием (:).
     *
     * @return bool Если данных нет, то false, иначе массив данных из кэша SensorParameter
     *
     * @package backend\controllers\cachemanagers
     *
     * @example  (new EquipmentCacheController())->multiGetParameterValueByParameters('*', '88:1');
     * @example  (new EquipmentCacheController())->multiGetParameterValueByParameters(310, '88:1');
     * @example  (new EquipmentCacheController())->multiGetParameterValueByParameters(310, '88:1,164:3,83:2');
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 29.05.2019 13:05
     */
    public function multiGetParameterValueByParameters($equipment_id = '*', $parameters = '*:*')
    {
        $keys = [];
        $parameters = str_replace(' ', '', $parameters);
        $parameters_parts = explode(',', $parameters);
        foreach ($parameters_parts as $parameter_part) {
            $key = self::$equipment_parameter_cache_key . ':' . $equipment_id . ':' . $parameter_part;
            $equipment_keys = $this->redis_cache->scan(0, 'MATCH', $key, 'COUNT', '10000000')[1];
            if ($equipment_keys) {
                $keys = array_merge($keys, $equipment_keys);
            }
        }
        if ($keys) {
            return $this->amicum_mGet($keys);
        }
        return false;
    }

    /**
     * @param $mine_id - идентификатор шахты
     * @param $equipment_id - идентификато оборудования
     *
     * @return string - созданный ключ
     *
     * @package backend\controllers\cachemanagers
     * Название метода: buildEquipmentMineKey()
     * Назначение метода: метод создания ключа для списка оборудования по шахте в кэше EquipmentMine
     *
     * Входные обязательные параметры:
     * @example $this->buildEquipmentMineKey(290, 97878);
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 31.05.2019 9:59
     */
    public static function buildEquipmentMineKey($mine_id, $equipment_id)
    {
        return self::$equipment_mine_cache_key . ':' . $mine_id . ':' . $equipment_id;
    }

    public function buildParameterKeyHash($parameter_id, $parameter_type_id)
    {
        return $parameter_id . ':' . $parameter_type_id;
    }

    public static function buildMineMapKeyHash($mine_id)
    {
        return self::$mine_map_cache_key . ':' . $mine_id;
    }

    public function buildEquipmentMapKeyHash($equipment_id)
    {
        return self::$equipment_map_cache_key . ':' . $equipment_id;
    }

    /**
     * @param $equipment_id - идентификатор  оборудования. Если указать '*', то возвращает всех оборудования
     * @param $parameter_id - идентификатор параметра. Если указать '*', то возвращает все параметры
     * @param $parameter_type_id - идентификатор типа параметра. Если указать '*', то возвращает все типы параметров
     * @return string созданный ключ кэша в виде EquipmentParameter:equipment_id:parameter_id:parameter_type_id
     *
     * @package backend\controllers\cachemanagers
     * Название метода: buildParameterKey()
     * Назначение метода: Метод создания ключа кэша для списка параметров оборудования с их значениями (EquipmentParameter)
     *
     * Входные обязательные параметры:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 31.05.2019 10:49
     */
    public function buildParameterKey($equipment_id, $parameter_id, $parameter_type_id)
    {
        return self::$equipment_parameter_cache_key . ':' . $equipment_id . ':' . $parameter_id . ':' . $parameter_type_id;
    }

    public function delParameterValueHash($equipment_id, $parameter_id = '*', $parameter_type_id = '*')
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        try {
            $redis_cache_key = $this->buildParameterKeyHash($parameter_id, $parameter_type_id);
            $equipment_map_key = self::buildEquipmentMapKeyHash($equipment_id);
            if ($redis_cache_key) {
                $warnings[] = "delParameterValueHash. Создал ключ работника на удаление";
            } else {
                throw new Exception("delParameterValueHash. Не удалось создать ключ для удаления параметра работника");
            }

            $keys = $this->redis_cache->hscan($equipment_map_key, 0, 'MATCH', $redis_cache_key, 'COUNT', '10000000');

            if ($keys and isset($keys[1]) and count($keys[1])>0) {
                for ($i = 0; $i < count($keys[1]); $i = $i + 2) {
                    $keys_to_del[$equipment_map_key][] = $keys[1][$i];
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

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    public function delEquipmentMineHash($equipment_id, $mine_id = '*')
    {
        if ($mine_id == '*') {
            $mine_id = AMICUM_DEFAULT_MINE;
        }
        $mine_map_key = self::buildMineMapKeyHash($mine_id);
        $keys[$mine_map_key][] = $equipment_id;

        $this->amicum_mDelHash($keys);
    }

    /**
     * Название метода: removeAll()
     * Назначение метода: Метод полного удаления кэша оборудования. Очищает все кэши связанные с оборудования, а именно:
     *    -- EquipmentMine
     *    -- EquipmentParameter
     *    -- EquipmentMine
     *    -- SensorEquipment
     * @example EquipmentCacheController::removeAll();
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 31.05.2019 13:57
     */
    public function removeAll()
    {
        $equipment_mine_keys = $this->redis_cache->scan(0, 'MATCH', self::$equipment_mine_cache_key . ':*', 'COUNT', '10000000')[1];
        if ($equipment_mine_keys)
            $this->amicum_mDel($equipment_mine_keys);
        $equipment_parameter_keys = $this->redis_cache->scan(0, 'MATCH', self::$equipment_parameter_cache_key . ':*', 'COUNT', '10000000')[1];
        if ($equipment_parameter_keys)
            $this->amicum_mDel($equipment_parameter_keys);

        $equipment_sensor_keys = $this->redis_cache->scan(0, 'MATCH', self::$equipment_sensor_cache_key . ':*', 'COUNT', '10000000')[1];
        if ($equipment_sensor_keys)
            $this->amicum_mDel($equipment_sensor_keys);

        $equipment_parameter_sensor_keys = $this->redis_cache->scan(0, 'MATCH', self::$equipment_parameter_sensor_cache_key . ':*', 'COUNT', '10000000')[1];
        if ($equipment_parameter_sensor_keys)
            $this->amicum_mDel($equipment_parameter_sensor_keys);

    }

    // amicum_flushall - метод очистки кеша оборудования
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

    /**
     * Название метода: delParameterValue()
     * Назначение метода: Метод удаления значения параметров оборудования(а)
     * Метод ничего не возвращает.
     *
     * Входные обязательные параметры:
     *
     * @param        $equipment_id - идентификатор оборудования. Если указать '*', то удаляет всех параметров оборудования
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
    public function delParameterValue($equipment_id, $parameter_id = '*', $parameter_type_id = '*')
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        try {
            $redis_cache_key = $this->buildParameterKey($equipment_id, $parameter_id, $parameter_type_id);
            if ($redis_cache_key) {
                $warnings[] = "delParameterValue. Создал ключ воркера на удаление";
            } else {
                throw new Exception("delParameterValue. Не удалось создать ключ для удаления параметра оборудования");
            }

            $keys = $this->redis_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
            if ($keys) {
                $del_param_res = $this->amicum_mDel($keys);
                $warnings[] = $del_param_res;
                if (!$del_param_res) {
                    $errors[] = $keys;
                    throw new Exception("delParameterValue. Ошибка удаления параметра(ов) оборудования(ов) ");
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
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Название метода: delEquipmentMine()
     * Назначение метода: Метод удаления оборудования из оборудования по шахте из кэша
     * Метод ничего не возвращает.
     *
     * Входные обязательные параметры:
     *
     * @param $mine_id - идентификатор шахты. По умолчанию сенсор с таким идентификатор ищется во всех шахтах и удаляется
     * @param $equipment_id - идентифкатор конкретного оборудования. Если указать $equipment_id = '*', то удаляет всех рабтников в шахте
     * указанной
     *
     * @package backend\controllers\cachemanagers
     * @example $this->delEquipmentMine(290, 310)
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 27.05.2019 15:32
     */
    public function delEquipmentMine($equipment_id, $mine_id = '*')
    {
        $redis_cache_key = self::buildEquipmentMineKey($mine_id, $equipment_id);
        $keys = $this->redis_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
        if ($keys) {
            $this->amicum_mDel($keys);
        }
    }
    //TODO что ????
    /******************************* Методы привязки оборудования к оборудования *******************************************/

    /**
     * @param $sensor_id - идентификатор сенсора
     * @param $equipment_id - идентификато оборудования
     *
     * @return string - созданный ключ для кэша SensorEquipment
     *
     * @package backend\controllers\cachemanagers
     * Название метода: buildSensorEquipmentKey()
     * Назначение метода: метод создания ключа для списка оборудования привязанных к оборудования
     *
     * Входные обязательные параметры:
     * @example $this->buildSensorEquipmentKey(290, 97878);
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 03.06.2019 16:40
     */
    public function buildSensorEquipmentKey($sensor_id, $equipment_id)
    {
        return self::$equipment_sensor_cache_key . ':' . $sensor_id . ':' . $equipment_id;
    }
    /**
     * @param $sensor_id - идентификатор сенсора
     * @param $equipment_id - идентификатор оборудования
     * @param $parameter_id - идентификатор параметра оборудования
     *
     * @return string - созданный ключ для кэша SensorEquipmentParameter
     *
     * @package backend\controllers\cachemanagers
     * Название метода: buildSensorEquipmentKey()
     * Назначение метода: метод создания ключа для списка сенсоров привязанных к оборудования с парамтерами
     *
     * Входные обязательные параметры:
     * @example $this->buildSensorEquipmentKey(290, 97878);
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 03.06.2019 16:40
     */
    public function buildSensorEquipmentParameterKey($sensor_id, $equipment_id, $parameter_id)
    {
        return self::$equipment_parameter_sensor_cache_key . ':' . $sensor_id . ':' . $equipment_id . ':' . $parameter_id;
    }

    /**
     * Название метода: setSensorEquipment()
     * Назначение метода: метод привязки/редактирования привязки лаппы к оборудования.
     * При привязки лампы к оборудования, снача ищется у лампы предыдущие оборудования. Если лампа уже привяза к каким-то оборудования,
     * то отвязывается, и привязывается к ней новый оборудования.
     *
     * Входные обязательные параметры:
     * @param $sensor_id - идентифкатор сенсора
     * @param $equipment_id - идентификатор оборудования
     *
     * @return bool если данные успешно хранились
     *
     * @package backend\controllers\cachemanagers
     *
     * @example $this->setSensorEquipment(27321, 22222);
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 8:46
     */
    public function setSensorEquipment($sensor_id, $equipment_id)
    {
        $this->delSensorEquipment($equipment_id);
        $equipment_equipment_key_old = $this->buildSensorEquipmentKey('*', $equipment_id);
        $equipment_equipment_key_old_keys = $this->redis_cache->scan(0, 'MATCH', $equipment_equipment_key_old, 'COUNT', '10000000')[1];
        if ($equipment_equipment_key_old_keys) {
            $this->amicum_mDel($equipment_equipment_key_old_keys);
        }
        $equipment_equipment_key_new = $this->buildSensorEquipmentKey($sensor_id, $equipment_id);
        $equipment_equipment_date_new = $this->buildStructureEquipmentSensor($sensor_id, $equipment_id);
        return $this->amicum_rSet($equipment_equipment_key_new, $equipment_equipment_date_new);
    }
//    /** Удалил Якимов М.Н. */
//     * Название метода: setSensorEquipmentParameter()
//     * Назначение метода: метод привязки/редактирования привязки лаппы к оборудования c учетом параметра.
//     * При привязки лампы к оборудования, снача ищется у лампы предыдущие оборудования. Если лампа уже привяза к каким-то оборудования,
//     * то отвязывается, и привязывается к ней новый оборудования.
//     *
//     * Входные обязательные параметры:
//     * @param $equipment_id - идентифкатор оборудования
//     * @param $equipment_id - идентификатор оборудования
//     * @param $parameter_id - параметр оборудования
//     *
//     * @return bool если данные успешно хранились
//     *
//     * @package backend\controllers\cachemanagers
//     *
//     * @example $this->setSensorEquipmentParameter(27321, 22222, 323);
//     *
//     * @author Озармехр Одилов <ooy@pfsz.ru>
//     * Created date: on 04.06.2019 8:46
//     */
//    public function setSensorEquipmentParameter($sensor_id, $equipment_id, $parameter_id)
//    {
//        $this->delSensorEquipmentParameter($equipment_id, $parameter_id);
//        $equipment_equipment_key_old = $this->buildSensorEquipmentParameterKey('*', $equipment_id, $parameter_id);
//        $equipment_equipment_key_old_keys = $this->redis_cache->scan(0, 'MATCH', $equipment_equipment_key_old, 'COUNT', '10000000')[1];
//        if ($equipment_equipment_key_old_keys) {
//            $this->amicum_mDel($equipment_equipment_key_old_keys);
//        }
//        $equipment_equipment_key_new = $this->buildSensorEquipmentParameterKey($sensor_id, $equipment_id, $parameter_id);
//        $equipment_equipment_date_new = $this->buildStructureEquipmentParameterSensor($sensor_id, $equipment_id, $parameter_id);
//
//        return $this->amicum_rSet($equipment_equipment_key_new, $equipment_equipment_date_new);
//
//    }

    /**
     * Название метода: delSensorEquipment() - метод удаление привязки оборудования к оборудования по сенсору
     * Назначение метода: метод удаление привязки оборудования к оборудования по сенсору
     *
     * Входные обязательные параметры:
     * @param $equipment_id - идентифкатор оборудования
     *
     * @return bool если данные успешно хранились
     *
     * @package backend\controllers\cachemanagers
     *
     * @example $this->setSensorEquipment(27321, 22222);
     *
     * @author Якимов М.Н.
     * Created date: on 04.06.2019 8:46
     */
    public function delSensorEquipment($equipment_id)
    {

        $equipment_equipment_key_old = $this->buildSensorEquipmentKey('*', $equipment_id);
        $equipment_equipment_key_old_keys = $this->redis_cache->scan(0, 'MATCH', $equipment_equipment_key_old, 'COUNT', '10000000')[1];
        if ($equipment_equipment_key_old_keys) {
            $this->amicum_mDel($equipment_equipment_key_old_keys);
            return true;
        }
        return false;

    }
    /**
     * Название метода: delSensorEquipmentParameter() - метод удаление привязки оборудования к оборудования по сенсору
     * Назначение метода: метод удаление привязки оборудования к сенсору по оборудованию с учетом параметра
     *
     * Входные обязательные параметры:
     * @param $equipment_id - идентификатор оборудования
     * @param $parameter_id - идентификатор параметра
     *
     * @return bool если данные успешно хранились
     *
     * @package backend\controllers\cachemanagers
     *
     * @example $this->setSensorEquipment(27321, 22222);
     *
     * @author Якимов М.Н.
     * Created date: on 04.06.2019 8:46
     */
    public function delSensorEquipmentParameter($equipment_id, $parameter_id)
    {

        $equipment_equipment_key_old = $this->buildSensorEquipmentParameterKey('*' , $equipment_id, $parameter_id);
        $equipment_equipment_key_old_keys = $this->redis_cache->scan(0, 'MATCH', $equipment_equipment_key_old, 'COUNT', '10000000')[1];
        if ($equipment_equipment_key_old_keys) {
            $this->amicum_mDel($equipment_equipment_key_old_keys);
            return true;
        }
        return false;

    }

    /**
     * @param $equipment_id - идентификатор оборудования. Если указать '*', то возвращает сенсоры. По умолчанию все сенсоры.
     *
     * @return mixed созданный ключ кэша в виде SensorParameter:equipment_id:parameter_id:parameter_type_id
     *
     * @package backend\controllers\cachemanagers
     * Название метода: buildParameterKey()
     * Назначение метода: getEquipmentMineByEquipment - Метод получения оборудования(ов) по сенсор айди из кэша redis.
     *
     * Входные обязательные параметры:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 24.05.2019 13:05
     */
    public function getEquipmentMineByEquipment($equipment_id = '*')
    {
        $redis_cache_key = self::buildEquipmentMineKey('*', $equipment_id);
        $keys = $this->redis_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
        if ($keys) {
            return $this->amicum_mGet($keys);
        }
        return false;
    }

    public function getEquipmentMineByEquipmentHash($equipment_id)
    {
        $key = $this->buildParameterKeyHash(346, 2);
        $equipment_map_key = self::buildEquipmentMapKeyHash($equipment_id);
        $equipment_mine = $this->amicum_rGetHash($equipment_map_key, $key);

        if ($equipment_mine and isset($equipment_mine['value'])) {
            $mine_id = $equipment_mine['value'];
        } else {
            $mine_id = AMICUM_DEFAULT_MINE;
        }

        $mine_map_key = self::buildMineMapKeyHash($mine_id);
        $equipment_mine = $this->amicum_rGetHash($mine_map_key, $equipment_id);

        if (!$equipment_mine) {
            return false;
        }

        return $equipment_mine;
    }

    /**
     * Получение информации об оборудовании из кеша EquipmentMine
     * @param $mine_id      -   идентификатор шахты
     * @param $equipment_id -   идентификатор оборудования
     * @return bool
     */
    public function getEquipmentMineByEquipmentOne($mine_id, $equipment_id)
    {
        $cache_key = self::buildEquipmentMineKey($mine_id, $equipment_id);
        return $this->amicum_rGet($cache_key);
    }

    public function getEquipmentMineByEquipmentOneHash($mine_id, $equipment_id)
    {
        $mine_map_key = self::buildMineMapKeyHash($mine_id);
        $equipment_mine = $this->amicum_rGetHash($mine_map_key, $equipment_id);
        if (!$equipment_mine) {
            return false;
        }

        return $equipment_mine;
    }

    /**
     * Название метода: initSensorEquipment()
     * Назначение метода: метод инициализации списка оборудования привязанных к оборудования.
     * Получает данные из БД и добавляет в кэш
     *
     * Входные необязательные параметры
     * @param string $sql_condition - условие для поиска. Можно указать условие в виде $sql_condition = "equipment_id = 5454 AND equipment_id = 478"
     *
     * @return mixed возвращает true если все норм, иначе false
     * @package backend\controllers\cachemanagers
     *
     * @example $this->initEquipmentSensor("equipment_id = 5454 AND equipment_id = 478");
     * @example $this->initEquipmentSensor("equipment_id = 5454");
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 03.06.2019 16:42
     */
    public function initSensorEquipment($sql_condition = '')
    {
        $sql_query = '';
        if ($sql_condition != '') {
            $sql_query = $sql_condition;
        }
        $equipments_sensors = (new Query())
            ->select([
                'sensor_id',
//				'network_id',
                'equipment_id',
//				'equipment_object_id',
//				'object_id'
            ])
            ->from('view_GetEquipmentBySensor')
            ->where($sql_query)
            ->all();
        if ($equipments_sensors) {
            foreach ($equipments_sensors as $equipments_sensor) {
                $sensor_equipment_cache_key = $this->buildSensorEquipmentKey($equipments_sensor['sensor_id'], $equipments_sensor['equipment_id']);
                $this->amicum_rSet($sensor_equipment_cache_key, $equipments_sensor);
            }
            return $equipments_sensors;
        }
        return false;
    }

    /**
     * Название метода: getSensorEquipment()
     * Назначение метода: метод получения привязанного сенсора к оборудованию по ИД сенсору или по ИД оборудования
     *
     * Входные необязательные параметры
     * @param $sensor_id - идентифкатор сенсора
     * @param $equipment_id - идентификатор оборудования
     *
     * @return mixed возвращает массив данных если есть (одномерный массив), иначе false
     * @package backend\controllers\cachemanagers
     *
     * @example $this->multiGetSensorEquipment('*',310);
     * @example $this->multiGetSensorEquipment('*','*');
     * @example $this->multiGetSensorEquipment(4755,'*');
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 03.06.2019 16:50
     */
    public function getSensorEquipment($sensor_id = '*', $equipment_id = '*')
    {
        $equipment_sensor_cache_key = $this->buildSensorEquipmentKey($sensor_id, $equipment_id);
        $equipment_sensor_cache_keys = $this->redis_cache->scan(0, 'MATCH', $equipment_sensor_cache_key, 'COUNT', '10000000')[1];
        if ($equipment_sensor_cache_keys) {
            return $this->amicum_mGet($equipment_sensor_cache_keys)[0];
        }
        return false;
    }

    /**
     * Название метода: getSensorEquipmentParameter()
     * Назначение метода: метод получения привязанного сенсора к оборудованию с учетом парамтера по ИД сенсору или по ИД оборудования
     *
     * Входные необязательные параметры
     * @param $sensor_id - идентифкатор сенсора
     * @param $equipment_id - идентификатор оборудования
     * @param $parameter_id - идентификатор параметра
     *
     * @return mixed возвращает массив данных если есть (одномерный массив), иначе false
     * @package backend\controllers\cachemanagers
     *
     * @example $this->getSensorEquipmentParameter('*',310);
     * @example $this->getSensorEquipmentParameter('*','*');
     * @example $this->getSensorEquipmentParameter(4755,'*');
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 03.06.2019 16:50
     */
    public function getSensorEquipmentParameter($sensor_id = '*', $equipment_id = '*', $parameter_id = '*')
    {
        $equipment_sensor_cache_key = $this->buildSensorEquipmentParameterKey($sensor_id, $equipment_id, $parameter_id);
        $equipment_sensor_cache_keys = $this->redis_cache->scan(0, 'MATCH', $equipment_sensor_cache_key, 'COUNT', '10000000')[1];
        if ($equipment_sensor_cache_keys) {
            return $this->amicum_mGet($equipment_sensor_cache_keys);
        }
        return false;
    }

    public function setEquipmentParameterValue($equipment_id, $equipment_parameter_id, $parameter_id, $parameter_type_id, $date_time, $value, $status_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'setEquipmentParameterValue. Начало выполнения метода';
        try {
//            //если дата не передана, то брать текущее время с миллисекундами
//            if (!$date_time) {
//                $date_time = Assistant::GetDateNow();
//                $warnings[] = 'setEquipmentParameterValue. Дата не задана. Взял текущую';
//            }
//            /**
//             * если $equipment_parameter_id равен -1 при вызове функции, но указаны parameter_id и parameter_type_id,
//             * то мы его ищем в кеше, если его и там нет, то мы его ищем в БД, если его и там нет, то выкидываем исключение
//             */
//            if ($equipment_parameter_id == -1) {
//                $warnings[] = 'setEquipmentParameterValue. equipment_parameter_id = -1 Начинаю поиск в кеше или в базе';
//                $key = $this->buildParameterKey($equipment_id, $parameter_id, $parameter_type_id);
//                $equipment_parameters = $this->amicum_rGet($key);
//                if ($equipment_parameters) {
//                    $warnings[] = 'setEquipmentParameterValue. Нашел воркер параметер айди в кеше';
//                    $equipment_parameter_id = $equipment_parameters['equipment_parameter_id'];
//                } else {
//                    $warnings[] = 'setEquipmentParameterValue. В кеше не было ищу в базе';
//                    $response = EquipmentMainController::getOrSetEquipmentParameter($equipment_id, $parameter_id, $parameter_type_id);
//                    if ($response['status'] == 1) {
//                        $equipment_parameter_id = $equipment_parameters['equipment_parameter_id'];
//                        $warnings[] = $response['warnings'];
//                        $errors[] = $response['errors'];
//                        $warnings[] = 'setEquipmentParameterValue. Нашел в базе данных/или создал в БД';
//                    } else {
//                        $warnings[] = 'setEquipmentParameterValue. В базе сенсор параметер айди не нашел. генерирую исключение';
//                        $warnings[] = $response['warnings'];
//                        $errors[] = $response['errors'];
//                        throw new \Exception("setEquipmentParameterValue. Для оборудования $equipment_id не существует привязки к нему параметра $parameter_id и типа параметра $parameter_type_id");
//                    }
//                }
//            }
//            $warnings[] = "setEquipmentParameterValue. Сенсор параметер айди = $equipment_parameter_id";
//            $warnings[] = 'setEquipmentParameterValue. Начинаю сохранение в кеш';


            $key = $this->buildParameterKey($equipment_id, $parameter_id, $parameter_type_id);
            $equipment_parameter_value = self::buildStructureEquipmentParametersValue($equipment_id, $equipment_parameter_id, $parameter_id, $parameter_type_id, $date_time, $value, $status_id);
            $this->amicum_rSet($key, $equipment_parameter_value);
            $warnings[] = 'setEquipmentParameterValue. Сохранил в кеш';
            unset($equipment_parameter_value);
            $status *= 1;
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'setEquipmentParameterValue. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'setEquipmentParameterValue. Закончил выполнение метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    public function setEquipmentParameterValueHash($equipment_id, $equipment_parameter_id, $parameter_id, $parameter_type_id, $date_time, $value, $status_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'setEquipmentParameterValueHash. Начало выполнения метода';
        try {
            //если дата не передана, то брать текущее время с миллисекундами
            if (!$date_time) {
                $date_time = date('Y-m-d H:i:s.U');
                $warnings[] = 'setEquipmentParameterValueHash. Дата не задана. Взял текущую';
            }
            /**
             * если $equipment_parameter_id равен -1 при вызове функции, но указаны parameter_id и parameter_type_id,
             * то мы его ищем в кеше, если его и там нет, то мы его ищем в БД, если его и там нет, то выкидываем исключение
             */
            $equipment_map_key = self::buildEquipmentMapKeyHash($equipment_id);
            if ($equipment_parameter_id == -1) {
                $warnings[] = 'setEquipmentParameterValueHash. equipment_parameter_id = -1 Начинаю поиск в кеше или в базе';
                $key = $this->buildParameterKeyHash($parameter_id, $parameter_type_id);
                $equipment_parameters = $this->amicum_rGetHash($equipment_map_key, $key);
                if ($equipment_parameters) {
                    $warnings[] = 'setEquipmentParameterValueHash. Нашел воркер параметер айди в кеше';
                    $equipment_parameter_id = $equipment_parameters['equipment_parameter_id'];
                } else {
                    $warnings[] = 'setEquipmentParameterValueHash. В кеше не было ищу в базе';
                    $response = EquipmentMainController::getOrSetEquipmentParameter($equipment_id, $parameter_id, $parameter_type_id);
                    if ($response['status'] == 1) {
                        $equipment_parameter_id = $equipment_parameters['equipment_parameter_id'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        $warnings[] = 'setEquipmentParameterValueHash. Нашел в базе данных/или создал в БД';
                    } else {
                        $warnings[] = 'setEquipmentParameterValueHash. В базе сенсор параметер айди не нашел. генерирую исключение';
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new Exception("setEquipmentParameterValueHash. Для сенсора $equipment_id не существует привязки к нему параметра $parameter_id и типа параметра $parameter_type_id");
                    }
                }
            }
            $warnings[] = "setEquipmentParameterValueHash. Воркер параметер айди = $equipment_parameter_id";
            $warnings[] = 'setEquipmentParameterValueHash. Начинаю сохранение в кеш';
            $equipment_parameter_values['equipment_id'] = $equipment_id;
            $equipment_parameter_values['equipment_parameter_id'] = $equipment_parameter_id;
            $equipment_parameter_values['value'] = $value;
            $equipment_parameter_values['parameter_id'] = $parameter_id;
            $equipment_parameter_values['parameter_type_id'] = $parameter_type_id;
            $equipment_parameter_values['status_id'] = $status_id;
            $equipment_parameter_values['date_time'] = $date_time;

            $key = $this->buildParameterKeyHash($parameter_id, $parameter_type_id);
            $this->amicum_rSetHash($equipment_map_key, $key, $equipment_parameter_values);
            $warnings[] = 'setEquipmentParameterValueHash. Сохранил в кеш';
            unset($equipment_parameter_values);
            $status *= 1;
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'setEquipmentParameterValueHash. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'setEquipmentParameterValueHash. Закончил выполнение метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /******************************* Методы добавления оборудования  в EquipmentMine *********************************/

    /**
     * Название метода: buildEquipmentKey()
     * Назначение метода: метод создания ключа кэша зачек... оборудования по шахте
     *
     * Входные обязательные параметры:
     * @param $mine_id - идентификатор шахты
     * @param $equipment_id - идентификатор оборудования
     *
     * @return string созданный ключ для кэше EquipmentMine
     *
     * @package backend\controllers\cachemanagers
     *
     * @example $this->buildEquipmentKey(290, 4545);
     * @example $this->buildEquipmentKey('*', '*');
     * @example $this->buildEquipmentKey(290, '*');
     * @example $this->buildEquipmentKey('*', 4545);
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 9:34
     */
    public function buildEquipmentKey($mine_id, $equipment_id)
    {
        return self::$equipment_mine_cache_key . ':' . $mine_id . ':' . $equipment_id;
    }


    /**
     * Название метода: multiGetEquipmentMine()
     * Назначение метода: метод получения всех/конкретного оборудования из кэша EquipmentInMine.
     * Если указать $mine_id = '*' или $equipment_id = '*', то выборка будет по всем ИД указаным в параметре
     *
     * Входные необязательные параметры
     * @param string $mine_id - идентификатор шахты
     * @param string $equipment_id - идентификатор оборудования
     * @return array|bool возвращает многомерный массив если есть данные, иначе возвращает false
     * @package backend\controllers\cachemanagers
     *
     * @example $this->multiGetEquipmentMine() - получение всех оборудования по всем шахтам
     * @example $this->multiGetEquipmentMine(290) - получение всех оборудования по конкретной шахте
     * @example $this->multiGetEquipmentMine(290, 4545) - получение оборудования по конкретной шахте и ИД
     * @example $this->multiGetEquipmentMine('*', 4545) - получение/поиск оборудования из всех шахт
     *
     * Документация на портале:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 9:40
     */
    public function multiGetEquipmentMine($mine_id = '*', $equipment_id = '*')
    {
        $equipment_key = $this->buildEquipmentKey($mine_id, $equipment_id);
        $equipment_keys = $this->redis_cache->scan(0, 'MATCH', $equipment_key, 'COUNT', '10000000')[1];
        if ($equipment_keys) {
            return $this->amicum_mGet($equipment_keys);
        }
        return false;
    }

    public function multiGetEquipmentCheckMineHash($mine_id)
    {
        $mine_map_key = self::buildMineMapKeyHash($mine_id);
        return $this->amicum_rGetMapHash($mine_map_key);
    }

    public function delEquipmentCheckMineHash($mine_id)
    {
        $mine_map_key = self::buildMineMapKeyHash($mine_id);
        return $this->amicum_rDelMapHash($mine_map_key);
    }


    /**
     * Название метода: multiSetEquipmentParameterValue()
     * Назначение метода: Метод массовой вставки массива параметров и их значений в кеш
     *
     * Входные не обязательные параметры:
     * @param int $equipment_id - идентификатор конкретного оборудования.
     * @param array $equipment_parameter_values - массив параметров и их значений
     * @return  array массив данных
     * @package backend\controllers\cachemanagers
     *
     *
     * @author Якимов М.Н.
     * Created date: on 28.05.2019 10:18
     */
    public function multiSetEquipmentParameterValue($equipment_id, $equipment_parameter_values)
    {

        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = "multiSetEquipmentParameterValue. Начал выполнять метод";
        try {
            $warnings[] = "multiSetEquipmentParameterValue. Нашел параметры в БД начинаю инициализировать кеш";
            foreach ($equipment_parameter_values as $equipment_parameter_value) {
                $wpv_key = $this->buildParameterKey($equipment_parameter_value['equipment_id'], $equipment_parameter_value['parameter_id'], $equipment_parameter_value['parameter_type_id']);
                $warnings[] = "multiSetEquipmentParameterValue. Кеш параметров воркера $equipment_id по ключу $wpv_key инициализирован";
                $equipment_paramter_value_array[$wpv_key] = $equipment_parameter_value;
            }
            $this->amicum_mSet($equipment_paramter_value_array);
            unset($equipment_paramter_value_array);
            unset($equipment_parameter_values);

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "multiSetEquipmentParameterValue. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "multiSetEquipmentParameterValue. Выполнение метода закончил";
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    public function multiSetEquipmentParameterValueHash($equipment_parameter_values)
    {

        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = "multiSetEquipmentParameterValueHash. Начал выполнять метод";
        try {
            $warnings[] = "multiSetEquipmentParameterValueHash. Нашел параметры в БД начинаю инициализировать кеш";///???
            foreach ($equipment_parameter_values as $equipment_parameter_value) {
                $wpv_key = $this->buildParameterKeyHash($equipment_parameter_value['parameter_id'], $equipment_parameter_value['parameter_type_id']);
                $equipment_map_key = self::buildEquipmentMapKeyHash($equipment_parameter_value['equipment_id']);
                $equipment_paramter_value_array[$equipment_map_key][$wpv_key] = $equipment_parameter_value;
            }
            $this->amicum_mSetHash($equipment_paramter_value_array);
            unset($equipment_paramter_value_array);
            unset($equipment_parameter_values);

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "multiSetEquipmentParameterValueHash. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "multiSetEquipmentParameterValueHash. Выполнение метода закончил";
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Название метода: multiSetEquipmentParameterSensor()
     * Назначение метода: Метод массовой вставки массива привязок оборудования по парамтерам к сенсору
     *
     * Входные не обязательные параметры:
     * @param array $equipment_parameter_sensor - массив параметров и их значений
     * @return  array массив данных
     * @package backend\controllers\cachemanagers
     *
     *
     * @author Якимов М.Н.
     * Created date: on 28.05.2019 10:18
     */
    public function multiSetEquipmentParameterSensor($equipment_parameter_sensors)
    {

        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = "multiSetEquipmentParameterSensor. Начал выполнять метод";
        try {
            $warnings[] = "multiSetEquipmentParameterSensor. Нашел параметры в БД начинаю инициализировать кеш";
            foreach ($equipment_parameter_sensors as $equipment_parameter_sensor) {
                $eps = $this->buildSensorEquipmentParameterKey($equipment_parameter_sensor['sensor_id'], $equipment_parameter_sensor['equipment_id'], $equipment_parameter_sensor['parameter_id']);
                $warnings[] = "multiSetEquipmentParameterSensor. Кеш параметров привязки оборудования ". $equipment_parameter_sensor['equipment_id'] ." по ключу $eps инициализирован";
                $equipment_parameter_sensor_array[$eps] = $equipment_parameter_sensor;
            }
            $this->amicum_mSet($equipment_parameter_sensor_array);
            unset($equipment_parameter_sensor_array);
            unset($equipment_parameter_sensor);

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "multiSetEquipmentParameterSensor. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "multiSetEquipmentParameterSensor. Выполнение метода закончил";
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Название метода: multiSetEquipmentSensor()
     * Назначение метода: Метод массовой вставки массива привязок оборудования к сенсору
     *
     * Входные не обязательные параметры:
     * @param array $equipment_sensors - массив параметров и их значений
     * @return  array массив данных
     * @package backend\controllers\cachemanagers
     *
     *
     * @author Якимов М.Н.
     * Created date: on 28.05.2019 10:18
     */
    public function multiSetEquipmentSensor($equipment_sensors)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = "multiSetEquipmentSensor. Начал выполнять метод";
        try {
            $warnings[] = "multiSetEquipmentSensor. Нашел параметры в БД начинаю инициализировать кеш";
            foreach ($equipment_sensors as $equipment_sensor) {
                $eps = $this->buildSensorEquipmentKey($equipment_sensor['sensor_id'], $equipment_sensor['equipment_id']);
                $warnings[] = "multiSetEquipmentSensor. Кеш параметров привязки оборудования ". $equipment_sensor['equipment_id'] ." по ключу $eps инициализирован";
                $equipment_sensor_array[$eps] = $equipment_sensor;
            }
            $this->amicum_mSet($equipment_sensor_array);
            unset($equipment_sensor_array);
            unset($equipment_sensor);

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "multiSetEquipmentSensor. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "multiSetEquipmentSensor. Выполнение метода закончил";
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // buildStructureEquipmentParametersValue - Метод создания структуры значения параметра оборудования в кеше
    // сделан, что бы легче было создавать массив для массовой вставки
    // разработал: Якимов М.Н.
    public static function buildStructureEquipmentParametersValue($equipment_id, $equipment_parameter_id, $parameter_id, $parameter_type_id, $date_time, $parameter_value, $status_id)
    {
        $equipment_parameter_value_to_cache['equipment_id'] = $equipment_id;
        $equipment_parameter_value_to_cache['equipment_parameter_id'] = $equipment_parameter_id;
        $equipment_parameter_value_to_cache['parameter_id'] = $parameter_id;
        $equipment_parameter_value_to_cache['parameter_type_id'] = $parameter_type_id;
        $equipment_parameter_value_to_cache['date_time'] = $date_time;
        $equipment_parameter_value_to_cache['value'] = $parameter_value;
        $equipment_parameter_value_to_cache['status_id'] = $status_id;
        return $equipment_parameter_value_to_cache;
    }

    // buildStructureEquipment - Метод создания структуры оборудования в кеше
    // сделан, что бы легче было создавать массив для массовой вставки
    // разработал: Якимов М.Н.
    public static function buildStructureEquipment($equipment_id, $equipment_title, $object_id, $object_title, $object_type_id, $mine_id)
    {
        //ВАЖНО!!!!! при изменении структуры в части количества поравить getEquipmentMine - там проверка на количество элементов в это объетк
        $equipment_to_cache['equipment_id'] = $equipment_id;
        $equipment_to_cache['equipment_title'] = $equipment_title;
        $equipment_to_cache['object_id'] = $object_id;
        $equipment_to_cache['object_title'] = $object_title;
        $equipment_to_cache['object_type_id'] = $object_type_id;
        $equipment_to_cache['mine_id'] = $mine_id;
        return $equipment_to_cache;
    }

    // buildStructureEquipmentParameterSensor - Метод создания структуры привязки оборудования по параметрам к сенсору  в кеше
    // сделан, что бы легче было создавать массив для массовой вставки
    // разработал: Якимов М.Н.
    public static function buildStructureEquipmentParameterSensor($sensor_id, $equipment_id, $parameter_id, $equipment_parameter_id)
    {
        //ВАЖНО!!!!! при изменении структуры в части количества поравить getEquipmentMine - там проверка на количество элементов в это объетк
        $equipment_parameter_sensor_to_cache['equipment_id'] = $equipment_id;
        $equipment_parameter_sensor_to_cache['sensor_id'] = $sensor_id;
        $equipment_parameter_sensor_to_cache['parameter_id'] = $parameter_id;
        $equipment_parameter_sensor_to_cache['equipment_parameter_id'] = $equipment_parameter_id;
        return $equipment_parameter_sensor_to_cache;
    }
    // buildStructureEquipmentSensor - Метод создания структуры привязки оборудования к сенсору  в кеше
    // сделан, что бы легче было создавать массив для массовой вставки
    // разработал: Якимов М.Н.
    public static function buildStructureEquipmentSensor($sensor_id, $equipment_id)
    {
        //ВАЖНО!!!!! при изменении структуры в части количества поравить getEquipmentMine - там проверка на количество элементов в это объетк
        $equipment_sensor_to_cache['equipment_id'] = $equipment_id;
        $equipment_sensor_to_cache['sensor_id'] = $sensor_id;
        return $equipment_sensor_to_cache;
    }

    /**
     * equipmentEquipment - Метод добавлния оборудования в кэш(БЕЗ ПАРАМЕТРОВ)
     * Принимает массив параметров:
     * mine_id
     * equipment_id
     * Created by: Якимов М.Н.
     * @since 09.04.2019 Переписан метод для обычного добавления/замены оборудования. Сырцев А.П.
     */
    public function addEquipment($equipment)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = "addEquipment. Начал выполнять метод";
        try {
            $cache_key = self::buildEquipmentMineKey($equipment['mine_id'], $equipment['equipment_id']);
            $set_result = $this->amicum_rSet($cache_key, $equipment);
            if (!$set_result) {
                $errors[] = "addEquipment. Добавляемый оборудование в главный кеш: ";
                $errors[] = $equipment;
                throw new Exception("addEquipment. Не смог добавить оборудование в главный кеш оборудования");
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "addEquipment. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "addEquipment. Выполнение метода закончил";
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    public function addEquipmentHash($equipment)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = 'addEquipmentHash. Начал выполнять метод';
        try {
            $mine_map_key = self::buildMineMapKeyHash($equipment['mine_id']);
            $this->amicum_rSetHash($mine_map_key, $equipment['equipment_id'], $equipment);
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'addEquipmentHash. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'addEquipmentHash. Выполнение метода закончил';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    //метод получения данных с редис за один раз методами редиса
    public function amicum_mGet($keys){
        $mgets=$this->redis_cache->executeCommand('mget',$keys);
        if($mgets) {
            foreach ($mgets as $mget) {
                $result[] = unserialize($mget)[0];
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

    public function amicum_repRedis($hostname, $port, $command_redis,$data)
    {
        $errors = array();
        $warnings = array();
        $status = 1;
        $result = array();

        $warnings[] = 'amicum_repRedis. Начало метода';
        $microtime_start = microtime(true);
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
        $microtime_start = microtime(true);
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
     * Метод удаления по указанным ключам
     */
    public function amicum_mDel($keys)
    {
        //Todo: сделать проверку в будущем на возвращаемые из redis
        if($keys)
        {
            foreach ($keys as $key)
            {
                $key1=array();
                $key1[] = $key;
                $this->redis_cache->executeCommand('del', $key1);

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
        $this->redis_cache->executeCommand('hdel', $key1);

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


//    // Заполняется потому, что воркеры и оборудование хранятся в одном кэше
//$startTime = microtime(true);
//$response = (new EquipmentCacheController())->runInit($mine_id);
//$errors['EquipmentCacheController'] = $response['errors'];
//$warnings[] = $response['status'];
//    // $status *= $response['status'];
//$warnings[] = 'Заполнил кеш оборудования ' . (microtime(true) - $startTime);

}
