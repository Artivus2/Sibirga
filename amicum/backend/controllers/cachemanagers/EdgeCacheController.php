<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\cachemanagers;

use frontend\controllers\system\LogAmicumFront;
use Throwable;
use Yii;
use yii\db\Query;

/**
 * Класс по работе с кэшем выработок(edge). Логику по работе с БД включать в этот класс нельзя.
 * Этот класс ИСКЛЮЧИТЕЛЬНО по работе с кэшем вырботок.
 * Другие лишние методы не связанные с кэшом нельзя добавить
 * Class EdgeCacheController
 * @package backend\controllers\cachemanagers
 */
class EdgeCacheController
{
    /**
     * Методы в классе
     * $this->runInit(290)                      - Метод полной инициализации кэша выработок по шахте и со всеми значениями
     * $this->initEdgeScheme()                  - Метод инициализации кэша списка выработок по шахте EdgeMine.
     * $this->initEdgeParameterValue()          - Метод инициализации вычисляемых значений параметров выработок в кэш EdgeParameter
     * $this->initEdgeParameterHandbookValue()  - Метод инициализации справочных значений параметров выработок в кэш EdgeParameter
     * $this->setParameterValue(123, 1, 122, 1, 6789, 19) - метод добавления/редактирования значения параметра выработки
     * $this->getParameterValue(1234, 122, 1)   - Метод получения значения параметра работника по конкретным данным
     * $this->multiGetParameterValue()          - Расширенный метод поиска/получения значения параметров работника.
     * $this->delParameterValue()               - Метод очистки(удаления параметров выработок(выработки)) кэша (из кэша) EdgeParameter.
     * $this->setEdgeMine()                     - Метод добавления/редактирования выработки в кэш список выработок по шахте (EdgeMine)
     * $this->getEdgeMine()                     - Метод получения данных о выработке по конкретной шахте и по конкретному ИД выработки
     * $this->multiGetEdgeMine()                - Расширенный метод получения списка работников по шахте.
     * $this->delEdgeMine()                     - Метод очистки кэша списка выработок по шахте с возможностью удаления выработки из списка выработок из кэша EdgeMine
     * $this->setEdgeScheme()                   - Метод добавления/редактирования выработки в схему выработок в кэш EdgeScheme
     * $this->getSetEdgeScheme()                - Метод редактирования конкретного поля выработки в кэше EdgScheme
     * $this->getEdgeScheme()                   - Метод поиска/получения схемы выработки по конкретной шахте и по конкретному ID выработки
     * $this->multiGetEdgeScheme()              - Расширенный метод поиска/получения схемы выработок(и)
     * $this->multiGetEdgesSchema               - Метод получения данных по перечню edge
     * $this->delEdgeScheme()                   - Метод очистки кэша схемы выработок с возможностью удаления выработки из схемы выработок EdgeScheme
     * $this->removeAll()                       - Метод полного удаления кэша выработок. Очищает все кэши связанные с выработками
     * $this->amicum_flushall()                 - Метод очистки кеша выработок
     * self::buildStructureEdgeParametersValue  - Метод создания структуры значения параметра выработок в кеше
     * $this->multiSetEdgeParameterValue()      - Метод массового добавления параметров выработки в кеш
     *
     * Не все методы могут быть перечислены в этом списке!!!
     * Более подробно прочитать документацию для конкретного метода
     */


    public $redis_cache;
    public static $edge_mine_cache_key = 'EdMi';
    public static $edge_parameter_cache_key = 'EdPa';
    public static $edge_scheme_cache_key = 'EdSch';

    public function __construct()
    {
        $this->redis_cache = Yii::$app->redis_edge;
    }

    /**
     * Название метода: runInit()
     * Назначение метода: Метод полной инициализации кэша выработок по шахте и со всеми значениями параметров
     *
     * @param int $mine_id - идентификатор шахты
     *
     * @return array $result - массив рузельтата выполнения метода. Сами данные не возвращает
     *
     * @package backend\controllers\cachemanagers
     * Метод инициализирует следующие кэши:
     * 1. EdgeMine
     * 2. EdgeParameter значения value
     * 3. EdgeParameter значения handbook
     * Входные обязательные параметры:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 14:32
     * @since ver
     */
    public function runInit($mine_id, $edge_id = null)
    {
//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', '5000M');
        $errors = array();
        $warnings = array();
        $status = 0;
        if ($mine_id != "") {
            $warnings['initEdgeParameterValue'] = $this->initEdgeParameterValue($edge_id);                                                                            // инициализируем кэш списка выработок со значениями value
            $warnings['initEdgeParameterHandbookValue'] = $this->initEdgeParameterHandbookValue($edge_id);                                                                    // инициализируем кэш списка выработок со значениями handbook
            $warnings['initEdgeMine'] = $this->initEdgeMine($mine_id, $edge_id);                                                                              // инициализируем кэш списка выработок по шахте
            $warnings['initEdgeScheme'] = $this->initEdgeScheme($mine_id, $edge_id);                                                                                    // инициализируем кэш схемы выработок по шахте
            $status = 1;
        } else $errors[] = "Идентификатор шахты не передан. Ошибка инициализации кэша выработок";
        return array('Items' => null, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings);
    }

    /**
     * Название метода: initEdgeScheme()
     * Назначение метода: метод инициализации кэша схемы выработок (EdgeScheme)
     *
     * Входные необязательные параметры
     *
     * @param $mine_id
     * @param int $edge_id
     * @return array|bool если данные есть, то массив данных, иначе false(0)
     *
     * @package backend\controllers\cachemanagers
     *
     * @example $this->initEdgeScheme();
     * @example $this->initEdgeScheme(7897);
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 14:58
     */
    public function initEdgeScheme($mine_id, $edge_id = null)
    {
        $condition = 'mine_id=' . $mine_id;
        if ($edge_id !== null) {
            $condition .= ' AND edge_id = ' . $edge_id;
        }

        $edges_mine_values = (new Query())
            ->select([
                'edge_id',
                'place_id',
                'place_title',
                'conjunction_start_id',
                'conjunction_end_id',
                'xStart',
                'yStart',
                'zStart',
                'xEnd',
                'yEnd',
                'zEnd',
                'place_object_id',
                'danger_zona',
                'color_edge',
                'color_hex',
                'color_edge_rus',
                'mine_id',
                'conveyor',
                'conveyor_tag',
                'value_ch',
                'value_co',
                'date_time',
                'edge_type_id',
                'lenght',
                'weight',
                'height',
                'width',
                'section',
                "plast_id",
                "plast_title",
                "type_place_title",
                "type_place_title as place_object_title",
                "edge_type_title",
                "angle",
                'mine_title',
                "value_ch as set_point_ch",
                "value_co as set_point_co",
                "place_object_id as type_place_id",
                "shape_edge_id",
                "shape_edge_title",
                "type_shield_id",
                "type_shield_title",
                "company_department_id",
                "company_department_title",
                "company_department_date",
                "company_department_state",
            ])
            ->from('view_initEdgeScheme')
            ->where($condition)
            ->all();
        if ($edges_mine_values) {
            foreach ($edges_mine_values as $edge_mine_values) {
                $edge_scheme_key = $this->buildKeyEdgeScheme($edge_mine_values['mine_id'], $edge_mine_values['edge_id']);
                $edge_schema_to_cache[$edge_scheme_key] = $edge_mine_values;
            }
            $this->amicum_mSet($edge_schema_to_cache);
            return $edges_mine_values;
        }
        return false;
    }

    /**
     * Название метода: initEdgeMine()
     * Назначение метода: метод инициализации кэша списка выработок по шахте EdgeMine.
     * Метод получает жанные из БД, если данные есть, то добдавляет в кэш и возвращает массив полученных данных.
     * Если данные нет, то возвращает false
     *
     * Входные обязательные параметры:
     *
     * @param int $mine_id - идентификтор шахты
     *
     * Входные необязательные параметры
     * @param int $edge_id - идентификатор выработки
     *
     * @return array|bool - массив данных либо false
     * @package backend\controllers\cachemanagers
     *
     * @example $this->initEdgeMine(290)
     * @example $this->initEdgeMine(290, 7878)
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 14:03
     */
    public function initEdgeMine($mine_id, $edge_id = null)
    {
        $sql = "mine_id = $mine_id ";
        if ($edge_id != null) {
            $sql .= " and edge_id = $edge_id";
        }
        $edges = (new Query())
            ->select([
                'mine_id',
                'edge_id',
                'conjunction_start_id',
                'conjunction_end_id',
                'ventilation_id'
            ])->from('view_initEdgeMine')->where($sql)->all();
        if ($edges) {
            foreach ($edges as $edge) {
                $edge_mine_key = $this->buildKeyEdgeMine($edge['mine_id'], $edge['edge_id'], $edge['ventilation_id']);
                $edge['mine_id'];
                $edges_to_cache[$edge_mine_key] = $edge;
            }
            $this->amicum_mSet($edges_to_cache);
            return $edges;
        }
        return false;
    }

    /**
     * Название метода: initWorkerParameterValue()
     * Назначение метода: метод инициализации вычисляемых значений параметров выработок в кэш EdgeParameter
     *
     * Входные необязательные параметры
     *
     * @param $edge_id - идентификатор выработки. Если указать этот параметр, то берет данные для конкретной выработки
     * и добавляет в кэш
     * @param $sql - условие для фильтра. Если указать этот параметр, то  $edge_id не учитывается!!!!!
     *
     * @return bool|array возвращает массив данных при успешном добавлении в кэш, иначе false
     *
     *
     * @example $this->initWorkerParameterValue();
     * @example $this->initWorkerParameterValue(475);
     * @example $this->initWorkerParameterValue(-1, 'worker_id = 475');
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 11:27
     */
    public function initEdgeParameterValue($edge_id = null, $sql = '')
    {
        $sql_filter = '';
        if ($edge_id !== null) {
            $sql_filter .= "edge_id = $edge_id";
        }

        if ($sql !== '') {
            $sql_filter = $sql;
        }

        $edge_parameter_values = (new Query())->select(['edge_id', 'edge_parameter_id', 'parameter_id', 'parameter_type_id', 'date_time', 'value', 'status_id'])->from('view_initEdgeParameterValue')->where($sql_filter)->all();
        if ($edge_parameter_values) {
            foreach ($edge_parameter_values as $edge_parameter_value) {
                $key = $this->buildKeyParameter($edge_parameter_value['edge_id'], $edge_parameter_value['parameter_id'], $edge_parameter_value['parameter_type_id']);
                $edge_parameters[$key] = $edge_parameter_value;
            }
            $this->amicum_mSet($edge_parameters);
            return $edge_parameter_values;
        }
        return false;
    }

    // buildStructureEdgeParametersValue - Метод создания структуры значения параметра выработок в кеше
    // сделан, что бы легче было создавать массив для массовой вставки
    // разработал: Якимов М.Н.
    public static function buildStructureEdgeParametersValue($edge_id, $edge_parameter_id, $parameter_id, $parameter_type_id, $date_time, $parameter_value, $status_id)
    {
        $edge_parameter_value_to_cache['edge_id'] = $edge_id;
        $edge_parameter_value_to_cache['edge_parameter_id'] = $edge_parameter_id;
        $edge_parameter_value_to_cache['parameter_id'] = $parameter_id;
        $edge_parameter_value_to_cache['parameter_type_id'] = $parameter_type_id;
        $edge_parameter_value_to_cache['date_time'] = $date_time;
        $edge_parameter_value_to_cache['value'] = $parameter_value;
        $edge_parameter_value_to_cache['status_id'] = $status_id;
        return $edge_parameter_value_to_cache;
    }

    /**
     * Название метода: initEdgeParameterHandbookValue()
     * Назначение метода: метод инициализации справочных значений параметров выработок в кэш EdgeParameter
     *
     * Входные необязательные параметры
     *
     * @param $edge_id - идентификатор выработки. Если указать этот параметр, то берет данные для конкретной выработки
     * и добавляет в кэш
     * @param $sql - условие для фильтра. Если указать этот параметр, то  $edge_id не учитывается!!!!!
     *
     * @return bool|array возвращает массив данных при успешном добавлении в кэш, иначе false
     *
     *
     * @example $this->initWorkerParameterValue();
     * @example $this->initWorkerParameterValue(475);
     * @example $this->initWorkerParameterValue(-1, 'worker_id = 475');
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 11:27
     */
    public function initEdgeParameterHandbookValue($edge_id = null, $sql = '')
    {
        $sql_filter = '';
        if ($edge_id !== null) {
            $sql_filter .= "edge_id = $edge_id";
        }

        if ($sql !== '') {
            $sql_filter = $sql;
        }

        $edge_parameter_values = (new Query())
            ->select([
                'edge_id',
                'edge_parameter_id',
                'parameter_id',
                'parameter_type_id',
                'date_time',
                'value',
                'status_id'
            ])
            ->from('view_initEdgeParameterHandbookValue')
            ->where($sql_filter)
            ->all();
        if ($edge_parameter_values) {
            foreach ($edge_parameter_values as $edge_parameter_value) {
                $key = $this->buildKeyParameter($edge_parameter_value['edge_id'], $edge_parameter_value['parameter_id'], $edge_parameter_value['parameter_type_id']);
                $edge_parameters[$key] = $edge_parameter_value;
            }
            $this->amicum_mSet($edge_parameters);
            return $edge_parameter_values;
        }
        return false;
    }

    /********************** Методы по работе со значениями параметров выработки EdgeParameter *************************/

    /**
     * Название метода: setParameterValue()
     * Назначение метода: метод добавления/редактирования значения параметра выработки
     *
     * Входные обязательные параметры:
     * @param     $edge_id - идентификатор выработки
     * @param     $edge_parameter_id - идентификато параметра работника
     * @param     $parameter_id - идентификатор параметра
     * @param     $parameter_type_id - идентификатор типа параметра
     * @param     $value - значение
     * @param     $status_id - статус
     * @param int $date_time дата и время. Если не указать, то автоматически указывается текущее время
     *
     * @return boolean результат добавления в кэш
     *
     * Входные данные добавляются в кэше в виде:
     * 'edge_id' => $edge_id,
     * 'edge_parameter_id' => $edge_parameter_id,
     * 'parameter_id' => $parameter_id,
     * 'parameter_type_id' => $parameter_type_id,
     * 'date_time' => $date_time,
     * 'value' => $value,
     * 'status_id' => $status_id
     * @package backend\controllers\cachemanagers
     *
     * @example $this->setParameterValue(123, 1, 122, 1, 6789, 19)
     * @example $this->setParameterValue(123, 1, 122, 1, 6789, '2018-04-05 12:21:44.445879')
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 05.06.2019 8:07
     */
    public function setParameterValue($edge_id, $edge_parameter_id, $parameter_id, $parameter_type_id, $value, $status_id, $date_time = 1)
    {
        if ($date_time == 1) $date_time = date('Y-m-d H:i:s.U');
        $edge_parameter_value_key = $this->buildKeyParameter($edge_id, $parameter_id, $parameter_type_id);
        $edge_parameter_value = array(
            'edge_id' => $edge_id,
            'edge_parameter_id' => $edge_parameter_id,
            'parameter_id' => $parameter_id,
            'parameter_type_id' => $parameter_type_id,
            'date_time' => $date_time,
            'value' => $value,
            'status_id' => $status_id
        );
        return $this->amicum_rSet($edge_parameter_value_key, $edge_parameter_value);

    }

    /**
     * Название метода: getParameterValue()
     * Назначение метода: метод получения значения параметра работника по конкретным данным
     *
     * Входные обязательные параметры:
     * @param $edge_id - идентификатор выработки.
     * @param $parameter_id - идентификатор параметра.
     * @param $parameter_type_id - идентификатор типа параметра.
     *
     * @return bool|array возвращает массив данных если есть в кэше (возвращает одномерный массив), иначе false.
     *
     * @package backend\controllers\cachemanagers
     *
     * @example $this->getParameterValue(1234, 122, 1);
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 05.06.2019 8:20
     */
    public function getParameterValue($edge_id, $parameter_id, $parameter_type_id)
    {
        $edge_parameter_value_key = $this->buildKeyParameter($edge_id, $parameter_id, $parameter_type_id);
        return $this->amicum_rGet($edge_parameter_value_key);
    }

    /**
     * Название метода: multiGetParameterValue()
     * Назначение метода: расширенный метод поиска/получения значения параметров работника. Если для параметра метода
     * указавается '*', то это будет считаться, что любой ИД для конкретного параметра
     * С помощью этого метода можно получить все параметры выработок(ки)
     *
     * Входные обязательные параметры:
     * Входные необязательные параметры
     * @param $edge_id - идентификатор выработки. Если указать '*', то возвращает всех выработок со всеми параметрами
     * @param $parameter_id - идентификатор параметра. Если указать '*', то возвращает все параметры
     * @param $parameter_type_id - идентификатор типа параметра. Если указать '*', то возвращает все типы параметров
     * @return bool|array возвращает массив данных если есть в кэше (возвращает многомерный массив), иначе false.
     *
     * @package backend\controllers\cachemanagers
     *
     * @example $this->multiGetParameterValue(); все выработки со всеми параметрами
     * @example $this->multiGetParameterValue(7845); все значения параметров конкретнойвыработки
     * @example $this->multiGetParameterValue(7845, 122); все значения параметров конкретнойвыработки по конкретному параметру
     * @example $this->multiGetParameterValue(7845, 122, 78); все значения параметров конкретнойвыработки по конкретному параметру
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 05.06.2019 8:21
     */
    public function multiGetParameterValue($edge_id = '*', $parameter_id = '*', $parameter_type_id = '*')
    {
        $edge_parameter_value_key = $this->buildKeyParameter($edge_id, $parameter_id, $parameter_type_id);
        $edge_parameter_value_keys = $this->redis_cache->scan(0, 'MATCH', $edge_parameter_value_key, 'COUNT', '10000000')[1];
        if ($edge_parameter_value_keys) {
            return $this->amicum_mGet($edge_parameter_value_keys);
        }
        return false;
    }

    /**
     * Название метода: delParameterValue()
     * Назначение метода: метод очистки(удаления параметров выработок(выработки)) кэша (из кэша) EdgeParameter.
     *
     * Входные необязательные параметры
     * @param $edge_id - идентификатор выработки. Если указать '*', то возвращает удаляет всех выработок со всеми параметрами
     * @param $parameter_id - идентификатор параметра. Если указать '*', то удаляет всех  параметров выработки
     * @param $parameter_type_id - идентификатор типа параметра.  Если указать '*', то удаляет всех  параметров выработки
     *
     * @return bool возвращает true при удалении или если данные не были обнаружены, иначе false
     * @package backend\controllers\cachemanagers
     *
     * @example $this->delParameterValue() - очистка кэша EdgeParameter.
     * @example $this->delParameterValue(7412) - очистка кэша конкретной выработки .
     * @example $this->delParameterValue(7412, 122) - очистка кэша конкретной выработки с указанным параметром.
     * @example $this->delParameterValue(7412, 122, 1) - очистка кэша конкретной выработки с указанным параметром и типа параметра .
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 05.06.2019 8:32
     */
    public function delParameterValue($edge_id = '*', $parameter_id = '*', $parameter_type_id = '*')
    {
        $edge_parameter_value_key = $this->buildKeyParameter($edge_id, $parameter_id, $parameter_type_id);
        $edge_parameter_value_keys = $this->redis_cache->scan(0, 'MATCH', $edge_parameter_value_key, 'COUNT', '10000000')[1];
        if ($edge_parameter_value_keys) {
            return $this->amicum_mDel($edge_parameter_value_keys);
        }
        return false;
    }
    /*************************************** Методы создания ключей ***************************************************/

    /**
     * @param $edge_id - идентификатор выработки. Если указать '*', то возвращает всех выработок со всеми параметрами
     * @param $parameter_id - идентификатор параметра. Если указать '*', то возвращает все параметры
     * @param $parameter_type_id - идентификатор типа параметра. Если указать '*', то возвращает все типы параметров
     * @return string созданный ключ кэша в виде EdgeParameter:edge_id:parameter_id:parameter_type_id
     *
     * @package backend\controllers\cachemanagers
     * Название метода: buildParameterKey()
     * Назначение метода: Метод создания ключа кэша для списка параметров выработок с их значениями (EdgeParameter)
     *
     * Входные обязательные параметры:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 11:09
     */
    public function buildKeyParameter($edge_id, $parameter_id, $parameter_type_id)
    {
        return self::$edge_parameter_cache_key . ':' . $edge_id . ':' . $parameter_id . ':' . $parameter_type_id;
    }

    /**
     * @param $mine_id - идентификатор шахты
     * @param $edge_id - идентификатор выработки
     * @param $ventilation_id - идентификатор выработки с Excel
     *
     * @return string - созданный ключ
     *
     * Документация на портале:
     * @package backend\controllers\cachemanagers
     * Название метода: buildEdgeMineKey()
     * Назначение метода: метод создания ключа для списка выработок по шахте в кэше EdgeScheme
     *
     * Входные обязательные параметры:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 13:10
     */
    public function buildKeyEdgeMine($mine_id, $edge_id, $ventilation_id)
    {
        return self::$edge_mine_cache_key . ':' . $mine_id . ':' . $edge_id . ':' . $ventilation_id;
    }

    /**
     * Название метода: buildKeyEdgeScheme()
     * Назначение метода: метод создания ключа кэше EdgeScheme для построения схемы шахты
     *
     * Входные обязательные параметры:
     * @param $mine_id - идентификато шахты
     * @param $edge_id - идентификато выработки
     * @return string -  созданный ключ для построения схемы шахты
     *
     * @package backend\controllers\cachemanagers
     *
     * @example $this->buildKeyEdgeScheme(290, 7659);
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 14:50
     */
    public function buildKeyEdgeScheme($mine_id, $edge_id)
    {
        return self::$edge_scheme_cache_key . ':' . $mine_id . ':' . $edge_id;
    }

    /******************************** Методы по работе со списком выработок по шахте EdgeMine *************************/

    /**
     * Название метода: setEdgeMine()
     * Назначение метода: метод добавления/редактирования выработки в кэш список выработок по шахте (EdgeMine)
     *
     * Входные обязательные параметры:
     * @param $mine_id - идентификатор шахты
     * @param $edge_id - идентификатор выработки
     * @param $conjunction_start_id - идентификатор начало поворота
     * @param $conjunction_end_id -  идентификатор конец поворота
     * @param $ventilation_id -  идентификатор  выработки с Excel
     *
     * @return bool - результат добавления значений в кэш
     *
     * @package backend\controllers\cachemanagers
     * @example $this->setEdgeMine(290, 7411, 789,788)
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 16:20
     */
    public function setEdgeMine($mine_id, $edge_id, $conjunction_start_id, $conjunction_end_id, $ventilation_id)
    {
        $edge_scheme_key = $this->buildKeyEdgeMine($mine_id, $edge_id, $ventilation_id);
        $edge_mine = array(
            'edge_id' => $edge_id,
            'conjunction_start_id' => $conjunction_start_id,
            'conjunction_end_id' => $conjunction_end_id,
            'ventilation_id' => $ventilation_id
        );
        return $this->amicum_rSet($edge_scheme_key, $edge_mine);
    }

    /**
     * Название метода: getEdgeMine()
     * Назначение метода: метод получения данных о выработке по конкретной шахте и по конкретному ИД выработки
     *
     * Входные обязательные параметры:
     * @param $mine_id - идентификатор шахты
     * @param $edge_id - идентификатор выработки
     * @param $ventilation_id - идентификатор выработки c Excel
     *
     * @return mixed если есть данные, то возвращает одномерный массив данных, иначе false(0)
     *
     * @package backend\controllers\cachemanagers
     * @example $this->getEdgeMine(290, 15489, 789)
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 13:47
     */
    public function getEdgeMine($mine_id, $edge_id, $ventilation_id)
    {
        $edge_mine_key = $this->buildKeyEdgeMine($mine_id, $edge_id, $ventilation_id);
        return $this->amicum_rGet($edge_mine_key);
    }

    /**
     * Название метода: multiGetEdgeMine()
     * Назначение метода: расширенный метод получения списка работников по шахте.
     * по умолчанию параметры методы ровны = '*', это значит, что поиск будет по всем шахтам или выработкам.
     * чтобы получить все, а не конкретный, то нужно указать параметр  = '*'
     *
     * Входные необязательные параметры:
     * @param $mine_id - идентификатор шахты
     * @param $edge_id - идентификатор выработки
     * @param $ventilation_id - идентификатор выработки с Excel
     *
     * @return mixed если есть данные, то возвращает МНОГОМЕРНЫЙ массив данных, иначе false(0)
     *
     * @package backend\controllers\cachemanagers
     * @example $this->multiGetEdgeMine() - получение всех вырботок по всем шахтам
     * @example $this->multiGetEdgeMine(290) - получение всех вырботок по конкретной шахте
     * @example $this->multiGetEdgeMine(290, 4545, 741) - получение выработки по id = 4545 по конкретной шахте
     * @example $this->multiGetEdgeMine('*', 4545, 741) - получение/поиск выработки по id = 4545 по всем шахтам
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 13:47
     */
    public function multiGetEdgeMine($mine_id = '*', $edge_id = '*', $ventilation_id = '*')
    {
        $edge_mine_key = $this->buildKeyEdgeMine($mine_id, $edge_id, $ventilation_id);
        $edge_mine_keys = $this->redis_cache->scan(0, 'MATCH', $edge_mine_key, 'COUNT', '10000000')[1];
        if ($edge_mine_keys) {
            return $this->amicum_mGet($edge_mine_keys);
        }
        return false;
    }


    /**
     * Название метода: delEdgeMine()
     * Назначение метода: метод очистки кэша спика выработок по шахте с возсожностью удаления выработки из списка выработок из кэша EdgeMine
     *
     * Входные необязательные параметры
     * @param $mine_id - идентифкатор шахты. По умолчанию все шахты.
     * @param $edge_id - идентифиткатор выработки. По умолчанию все выработки.
     * @param $ventilation_id - идентифиткатор выработки c Excel. По умолчанию все выработки c Excel.
     *
     * @return bool - результат выполения удаления из кэша
     *
     * @package backend\controllers\cachemanagers
     * @example $this->delEdgeScheme()
     * @example $this->delEdgeScheme(290)
     * @example $this->delEdgeScheme(290, 4879)
     * @example $this->delEdgeScheme('*', 4879)
     * @example $this->delEdgeScheme('*', 4879, 789)
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 16:35
     */
    public function delEdgeMine($mine_id = '*', $edge_id = '*', $ventilation_id = '*')
    {
        $edge_mine_key = $this->buildKeyEdgeMine($mine_id, $edge_id, $ventilation_id);
        $edge_mine_keys = $this->redis_cache->scan(0, 'MATCH', $edge_mine_key, 'COUNT', '10000000')[1];
        if ($edge_mine_keys) {
            return $this->amicum_mDel($edge_mine_keys);
        }
        return false;
    }

    /******************************** Методы добавления/получения схемы выработок *************************************/

    /**
     * Название метода: setEdgeScheme()
     * Назначение метода: метод добавления/редактирования выработки в схему выработок в кэш EdgeScheme
     *
     * Входные обязательные параметры:
     * @param $mine_id - идентифкатор шахты
     * @param $edge_id - идентифиткатор выработки
     * @param $array_values - массив значений. Структуру можно посмотреть в методе инициализации схемы выработок,
     * так как там есть конкретные параметры, которые добавляются в кэш
     *
     * @return bool - результат добавления значений в кэш
     *
     * @package backend\controllers\cachemanagers
     *
     * @example $this->setEdgeScheme(290, 456, array())
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 15:53
     */
    public function setEdgeScheme($mine_id, $edge_id, $array_values)
    {
        $edge_scheme_key = $this->buildKeyEdgeScheme($mine_id, $edge_id);
        return $this->amicum_rSet($edge_scheme_key, $array_values);
    }

    /**
     * Название метода: getEdgeScheme()
     * Назначение метода: метод поиска/получения схемы выработки по конкретной шахте и по конкретному ID выработки
     *
     * Входные обязательные параметры:
     * @param $mine_id - идентифкатор шахты
     * @param $edge_id - идентифиткатор выработки
     * @return mixed - если данные есть, то одномерный массив, иначе false(0)
     * @package backend\controllers\cachemanagers
     *
     * @example $this->getEdgeScheme(290, 15963)
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 16:01
     */
    public function getEdgeScheme($mine_id, $edge_id)
    {
        $edge_scheme_key = $this->buildKeyEdgeScheme($mine_id, $edge_id);
        return $this->amicum_rGet($edge_scheme_key);
    }

    /**
     * Название метода: multiGetEdgeScheme()
     * Назначение метода: расширенный метод поиска/получения схемы вырботок(и)
     * Входные необязательные параметры
     * @param $mine_id - идентифкатор шахты. По умолчанию все шахты.
     * @param $edge_id - идентифиткатор выработки. По умолчанию все выработки.
     * @return array|bool - если данные есть, то МНОГОМЕРНЫЙ массив, иначе false(0)
     *
     * @package backend\controllers\cachemanagers
     *
     * @example $this->multiGetEdgeScheme(290)
     * @example $this->multiGetEdgeScheme(290, 7878)
     * @example $this->multiGetEdgeScheme('*', 7878)
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 16:02
     */
    public function multiGetEdgeScheme($mine_id = '*', $edge_id = '*')
    {
        $edge_scheme_key = $this->buildKeyEdgeScheme($mine_id, $edge_id);
        $edge_scheme_keys = $this->redis_cache->scan(0, 'MATCH', $edge_scheme_key, 'COUNT', '10000000')[1];
        if ($edge_scheme_keys) {
            return $this->amicum_mGet($edge_scheme_keys);
        }
        return false;
    }

    // multiGetEdgesSchema - метод получения данных по перечню эджей
    // http://127.0.0.1/cache-getter/get-edge-shema-cache?mine_id=290&edge_id=22709
    // http://127.0.0.1/cache-getter/get-edge-shema-cache?mine_id=290&edge_id=22709,22358
    // http://127.0.0.1/cache-getter/get-edge-shema-cache?mine_id=290
    public function multiGetEdgesSchema($mine_id = '*', $edge_ids = '*')
    {
        $keys = [];
        $edge_ids = explode(',', str_replace(' ', '', $edge_ids));
        foreach ($edge_ids as $edge_id) {
            $key = $this->buildKeyEdgeScheme($mine_id, $edge_id);
            $edges_keys = $this->redis_cache->scan(0, 'MATCH', $key, 'COUNT', '10000000')[1];
            if ($edges_keys) {
                $keys = array_merge($keys, $edges_keys);
            }
        }

        if ($keys) {
            return $this->amicum_mGet($keys);
        }
        return false;
    }

    /**
     * multiSetEdgeParameterValue - метод массового добавления параметров выработки в кеш
     * @param $edge_parameter_values - список добавляемых значений
     * @return array|null[]
     */
    public function multiSetEdgeParameterValue($edge_parameter_values)
    {
        $log = new LogAmicumFront("multiSetEdgeParameterValue");
        $result = null;

        try {
            $log->addLog("Начал выполнять метод");

            foreach ($edge_parameter_values as $edge_parameter_value) {
                $spv_key = $this->buildKeyParameter($edge_parameter_value['edge_id'], $edge_parameter_value['parameter_id'], $edge_parameter_value['parameter_type_id']);
                $edge_paramter_value_array[$spv_key] = $edge_parameter_value;
            }
            if (isset($edge_paramter_value_array)) {
                $this->amicum_mSet($edge_paramter_value_array);
            }

            unset($edge_paramter_value_array);
            unset($edge_parameter_value);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Название метода: delEdgeScheme()
     * Назначение метода: метод очистки кэша схемы выработок с возможностью удаления выработки из схемы выработок EdgeScheme
     *
     * Входные необязательные параметры
     * @param $mine_id - идентифкатор шахты. По умолчанию все шахты.
     * @param $edge_id - идентифиткатор выработки. По умолчанию все выработки.
     *
     * @return bool - результат выполения удаления из кэша
     *
     * @package backend\controllers\cachemanagers
     * @example $this->delEdgeScheme()
     * @example $this->delEdgeScheme(290)
     * @example $this->delEdgeScheme(290, 4879)
     * @example $this->delEdgeScheme('*', 4879)
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 16:35
     */
    public function delEdgeScheme($mine_id = '*', $edge_id = '*')
    {
        $edge_scheme_key = $this->buildKeyEdgeScheme($mine_id, $edge_id);
        $edge_scheme_keys = $this->redis_cache->scan(0, 'MATCH', $edge_scheme_key, 'COUNT', '10000000')[1];
        if ($edge_scheme_keys) {
            return $this->amicum_mDel($edge_scheme_keys);
        }
        return false;
    }

    //метод получения данных с редис за один раз методами редиса
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

    public function amicum_repRedis($hostname, $port, $command_redis, $data)
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


    /**
     * тестовый метод по проверки порта репликационного редиса редиса
     */
    public function ChetPortRedis($key)
    {
        $key1[] = $key;
        return array('host' => REDIS_REPLICA_HOSTNAME, 'port' => $this->redis_cache->port);
    }

    /**
     * Название метода: removeAll()
     * Назначение метода: Метод полного удаления кэша выработок. Очищает все кэши связанные с выработками, а именно:
     *    -- $edge_scheme_cache_key
     *    -- $edge_parameter_cache_key
     *    -- $edge_mine_cache_key
     * @example EquipmentCacheController::removeAll();
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 31.05.2019 13:57
     */
    public function removeAll()
    {
        $edge_scheme_keys = $this->redis_cache->scan(0, 'MATCH', self::$edge_scheme_cache_key . ':*', 'COUNT', '10000000')[1];
        if ($edge_scheme_keys)
            $this->amicum_mDel($edge_scheme_keys);

        $edge_parameter_keys = $this->redis_cache->scan(0, 'MATCH', self::$edge_parameter_cache_key . ':*', 'COUNT', '10000000')[1];
        if ($edge_parameter_keys)
            $this->amicum_mDel($edge_parameter_keys);

        $edge_mine_keys = $this->redis_cache->scan(0, 'MATCH', self::$edge_mine_cache_key . ':*', 'COUNT', '10000000')[1];
        if ($edge_mine_keys)
            $this->amicum_mDel($edge_mine_keys);
    }

    // amicum_flushall - метод очистки кеша выработок
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
