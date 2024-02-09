<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers;

use backend\controllers\cachemanagers\EdgeCacheController;
use backend\controllers\const_amicum\ParamEnum;
use backend\controllers\const_amicum\ParameterTypeEnumController;
use Exception;
use frontend\controllers\handbooks\HandbookParameterController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Edge;
use frontend\models\EdgeParameter;
use frontend\models\EdgeParameterHandbookValue;
use frontend\models\EdgeParameterValue;
use frontend\models\EdgeStatus;
use Throwable;
use yii\db\Query;

/**
 * Базовый класс по работе с таблицами выработок.
 * Класс исключительно использовать для работы с таблицами выработок в БД.
 * Class EdgeBasicController
 * @package backend\controllers
 */
class EdgeBasicController
{

    // getEdgeScheme()                      - метод получения кэша схемы выработок (EdgeScheme)
    // getEdgeParameterHandbookValue()      - Метод получения справочных значений параметров ветви в БД EdgeParameterHandbookValue
    // getEdgeParameterValue()              - Метод получения вычисляемых значений параметров выработки в БД EdgeParameterValue

    // addEdgeParameterWithHandbookValue()  - Метод создания параметра с его справочным значением

    // saveEdgeStatus()                     - Метод создания статуса выработки

    /**
     * Название метода: addEdgeParameterValue()
     * Назначение метода: метод добавления вычислямых значений параметров выработки
     *
     * Входные обязательные параметры:
     * @param $edge_parameter_id - идентификатор параметра работника
     * @param $value - значение
     * @param $status_id - статус параметра
     *
     * @param $date_time - дата и время. По умолчанию текущая дата и время.
     * @return int|array Если данные успешно сохранились, то возвращает id, иначе false
     *
     * Входные необязательные параметры
     * @package backend\controllers
     *
     * @example $this->addEdgeParameterValue(4785, 'Pop', 19);
     * @example $this->addEdgeParameterValue(4785, 'Pop', 19, '2019-05-06 09:50:78');
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 05.06.2019 9:42
     */
    public function addEdgeParameterValue($edge_parameter_id, $value, $status_id, $date_time = 1)
    {
        if ($date_time == 1) {
            $date_time = date('Y-m-d H:i:s.U');
        }
        $edge_parameter_value = new EdgeParameterValue();
        $edge_parameter_value->edge_parameter_id = $edge_parameter_id;
        $edge_parameter_value->value = (string)$value;
        $edge_parameter_value->status_id = $status_id;
        $edge_parameter_value->date_time = $date_time;
        if ($edge_parameter_value->save()) {
            $edge_parameter_value->refresh();
            return $edge_parameter_value->id;
        }
        return $edge_parameter_value->errors;
    }

    /**
     * /**
     * Название метода: addEdgeParameterHandbookValue()
     * Назначение метода: метод добавления справочных значений параметров выработки
     *
     * Входные обязательные параметры:
     * @param $edge_parameter_id - идентификатор параметра работника
     * @param $value - значение
     * @param $status_id - статус параметра
     *
     * @param $date_time - дата и время. По умолчанию текущая дата и время.
     * @return int|array Если данные успешно сохранились, то возвращает id, иначе false
     *
     * Входные необязательные параметры
     * @package backend\controllers
     *
     * @example $this->addEdgeParameterHandbookValue(4785, 'Pop', 19);
     * @example $this->addEdgeParameterHandbookValue(4785, 'Pop', 19, '2019-05-06 09:50:78');
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 05.06.2019 9:49
     */
    public static function addEdgeParameterHandbookValue($edge_parameter_id, $value, $status_id, $date_time = 1)
    {
        if ($date_time == 1) {
            $date_time = date('Y-m-d H:i:s.U');
        }
        $e_p_h_v = new EdgeParameterHandbookValue();
        $e_p_h_v->edge_parameter_id = $edge_parameter_id;
        $e_p_h_v->value = (string)$value;
        $e_p_h_v->status_id = $status_id;
        $e_p_h_v->date_time = $date_time;
        if ($e_p_h_v->save()) {
            return $e_p_h_v->id;
        }
        return $e_p_h_v->errors;
    }

    /**
     * Название метода: addEdgeParameter()
     * Назначение метода: метод добавления параметра выработки.
     * Метод сначала проверяем, существует ли такой параметр у вырабоки, если да, то возвращает id, иначе создает
     * сопись в таблице в БД и возвращает id
     *
     * Входные обязательные параметры:
     * @param $edge_id - идентификатор выработки
     * @param $parameter_id - идентифкатор параметра
     * @param $parameter_type_id - идентификатор типа параметра
     *
     * @return array|int если данные успешно сохранились в БД, то возвращает $edge_parameter_id, иначе массив ошибок
     *
     * @package backend\controllers
     *
     * @example $this->addEdgeParameter(15013, 122, 2)
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 05.06.2019 11:26
     */
    public static function addEdgeParameter($edge_id, $parameter_id, $parameter_type_id)
    {
        $edge_parameter = EdgeParameter::findOne(['edge_id' => $edge_id, 'parameter_id' => $parameter_id, 'parameter_type_id' => $parameter_type_id]);
        if ($edge_parameter) {
            return $edge_parameter->id;
        }
        $edge_parameter = new EdgeParameter();
        $edge_parameter->edge_id = $edge_id;
        $edge_parameter->parameter_id = $parameter_id;
        $edge_parameter->parameter_type_id = $parameter_type_id;
        if ($edge_parameter->save()) {
            return $edge_parameter->id;
        }
        return $edge_parameter->errors;
    }

    /**
     * addEdge - Метод сохранения выработки в БД
     * @param $place_id - id места
     * @param $conjunction_start_id - id координаты начала
     * @param $conjunction_end_id - id координаты конца
     * @param $edge_type_id - тип выработки
     *
     * Выходные параметры:
     *      edge_id - ключ выработки
     */
    public static function addEdge($place_id, $conjunction_start_id, $conjunction_end_id, $edge_type_id)
    {
        $log = new LogAmicumFront("addEdge");

        $edge_id = false;

        try {
            $log->addLog("Начало выполнения метода");

            $response = MainBasicController::addMain('edge');
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка сохранения главного id выработки");
            }

            $main_id = $response['main_id'];

            $edge = new Edge();
            $edge->id = $main_id;
            $edge->conjunction_start_id = $conjunction_start_id;                                                        // сопряжение начало
            $edge->conjunction_end_id = $conjunction_end_id;                                                            // сопряжение конец
            $edge->place_id = $place_id;                                                                                // название места
            $edge->edge_type_id = $edge_type_id;                                                                        // тип ветви (выработка, вертикальный ствол/скважина)
            if (!$edge->save()) {
                $log->addData($edge->errors, '$edge->errors', __LINE__);
                throw new Exception("Ошибка сохранения выработки в БД");
            }

            $edge_id = $edge->id;

            $log->addData($edge->id, '$edge->id', __LINE__);

            $date_now = Assistant::GetDateTimeNow();
            $edge_status = new EdgeStatus();
            $edge_status->edge_id = $edge_id;
            $edge_status->status_id = 1;                                                                                // записываем статус 1-актуальная
            $edge_status->date_time = $date_now;

            if (!$edge_status->save()) {
                $log->addData($edge_status->errors, '$edge_status->errors', __LINE__);
                throw new Exception("Ошибка сохранения статуса выработки в БД");
            }

            $log->addData($edge_status->id, '$edge_status->id', __LINE__);

            $edge_status->refresh();
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => null, 'edge_id' => $edge_id], $log->getLogAll());
    }

    /**
     * Название метода: getEdgeScheme() - метод получения кэша схемы выработок (EdgeScheme)
     * Назначение метода: метод получения кэша схемы выработок (EdgeScheme)
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
    public static function getEdgeScheme($mine_id, $edge_id = '*')
    {
        $sql_filter = null;
        $edge_ids = array();

        if ($mine_id != '*') {
            $sql_filter = "mine_id = $mine_id";
        }

        if ($edge_id != '*') {
            $edge_ids = explode(',', str_replace(' ', '', $edge_id));
            if (!$edge_ids) {
                $edge_ids = array();
            }
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
            ->where($sql_filter)
            ->andFilterWhere(['in', 'edge_id', $edge_ids])
            ->all();
        if ($edges_mine_values) {
            return $edges_mine_values;
        }
        return false;
    }

    /**
     * Название метода: getEdgeSchemeByDate() - метод получения кэша схемы выработок (EdgeScheme) по дате
     *
     * Входные обязательные параметры:
     * @param $mine_id - идентификатор шахты
     * @param $data_from - дата от
     *
     * @return array|bool если данные есть, то массив данных, иначе false(0)
     *
     * @package backend\controllers\cachemanagers
     *
     */
    public static function getEdgeSchemeByDate($mine_id, $data_from)
    {
        $edge_status_date = (new Query())
            ->select(['edge_id', 'date_time'])
            ->from('edge_status')
            ->where("date_time <= '$data_from'");

        $edge_status_date_max = (new Query())
            ->select([
                'edge_id',
                'MAX(date_time) AS date_time_max'
            ])
            ->from($edge_status_date)
            ->groupBy('edge_id');

        $edges = (new Query())
            ->select([
                'e_s_date_max.edge_id',
                'place.id AS place_id',
                'place.title AS place_title',
                'conjunction_start.id AS conjunction_start_id',
                'conjunction_end.id AS conjunction_end_id',
 	            'conjunction_start.x AS xStart',
 	            'conjunction_start.y AS yStart',
 	            'conjunction_start.z AS zStart',
 	            'conjunction_end.x AS xEnd',
 	            'conjunction_end.y AS yEnd',
 	            'conjunction_end.z AS zEnd'
            ])
            ->from(['e_s_date_max' => $edge_status_date_max])
            ->leftJoin('edge_status', 'e_s_date_max.edge_id = edge_status.edge_id AND e_s_date_max.date_time_max = edge_status.date_time')
            ->leftJoin('edge', 'edge.id = edge_status.edge_id')
            ->leftJoin('place', 'place.id = edge.place_id')
            ->leftJoin('conjunction conjunction_start', 'conjunction_start.id = edge.conjunction_start_id')
            ->leftJoin('conjunction conjunction_end', 'conjunction_end.id = edge.conjunction_end_id')
            ->where(['status_id' => 1, 'place.mine_id' => $mine_id])
            ->all();

        $e_p_h_v_date = (new Query())
            ->select(['edge_parameter_id', 'date_time'])
            ->from('edge_parameter_handbook_value')
            ->where("date_time <= '$data_from'");

        $e_p_h_v_date_max = (new Query())
            ->select(['e_p_h_v_date.edge_parameter_id', 'MAX(e_p_h_v_date.date_time) date_time_max'])
            ->from(['e_p_h_v_date' => $e_p_h_v_date])
            ->groupBy('edge_parameter_id');

        $e_p_values = (new Query())
            ->select([
                'e_p.edge_id',
                'e_p.parameter_id',
                'e_p_h_v.value',
                'e_p_h_v.date_time',
                'e_p_h_v.status_id'
            ])
            ->from(['e_p_h_v_date_max' => $e_p_h_v_date_max])
            ->leftJoin('edge_parameter_handbook_value e_p_h_v', 'e_p_h_v.edge_parameter_id = e_p_h_v_date_max.edge_parameter_id AND e_p_h_v.date_time = e_p_h_v_date_max.date_time_max')
            ->leftJoin('edge_parameter e_p', 'e_p.id = e_p_h_v.edge_parameter_id')
            ->leftJoin('edge', 'edge.id = e_p.edge_id')
            ->leftJoin('place', 'place.id = edge.place_id')
            ->where(['place.mine_id' => 290])
            ->indexBy(function ($func) {
                return $func['edge_id'].'_'.$func['parameter_id'];
            })
            ->all();

        $parameters = [
            ['name' => 'weight', 'param_id' => ParamEnum::WEIGHT],
            ['name' => 'lenght', 'param_id' => ParamEnum::LENGTH],
            ['name' => 'height', 'param_id' => ParamEnum::HEIGHT],
            ['name' => 'width', 'param_id' => ParamEnum::WIDTH],
            ['name' => 'section', 'param_id' => ParamEnum::SECTION],
            ['name' => 'type_shield_id', 'param_id' => ParamEnum::TYPE_SHIELD_ID],
            ['name' => 'shape_edge_id', 'param_id' => ParamEnum::SHAPE_EDGE_ID],
            ['name' => 'danger_zona', 'param_id' => ParamEnum::DANGER_ZONA],
            ['name' => 'conveyor', 'param_id' => ParamEnum::CONVEYOR],
            ['name' => 'angle', 'param_id' => ParamEnum::ANGLE],
            ['name' => 'conveyor_tag', 'param_id' => ParamEnum::CONVEYOR_TAG],
            ['name' => 'value_ch', 'param_id' => ParamEnum::LEVEL_CH4],
            ['name' => 'value_co', 'param_id' => ParamEnum::LEVEL_CO],
            ['name' => 'company_department_id', 'param_id' => ParamEnum::COMPANY_ID],
            ['name' => 'company_department_date', 'param_id' => ParamEnum::COMPANY_ID],
            ['name' => 'company_department_state', 'param_id' => ParamEnum::COMPANY_ID],
            ['name' => 'plast_id', 'param_id' => ParamEnum::PLAST_ID],
            ['name' => 'color_hex', 'param_id' => ParamEnum::COLOR_HEX]
        ];

        $edges_start = array();

        foreach ($edges as $edge) {
            foreach ($parameters as $parameter) {
                if (isset($e_p_values[$edge['edge_id'].'_'.$parameter['param_id']]['value'])) {
                    $edge[$parameter['name']] = $e_p_values[$edge['edge_id'].'_'.$parameter['param_id']]['value'];
                } else {
                    $edge[$parameter['name']] = null;
                }
            }
            $edges_start[] = $edge;
        }

        if ($edges_start) {
            return $edges_start;
        }
        return false;
    }

    /**
     * addEdgeParameterWithHandbookValue - Метод создания параметра с его справочным значением
     * @param $edge_id - ключ выработки
     * @param $parameter_id - ключ параметра
     * @param $parameter_type_id - ключ типа параметра
     * @param $value - значение параметра
     * @param $status_id - статус значения параметра
     * @param $date_time - дата/время создания параметра
     * @return array
     */
    public static function addEdgeParameterWithHandbookValue($edge_id, $parameter_id, $parameter_type_id, $value, $status_id, $date_time = 1)
    {
        $log = new LogAmicumFront("addEdgeParameterWithHandbookValue");

        $edge_param_to_cache = array();                                                                                            // Массив предупреждений
        $result = array();
        $edge_parameter_id = -1;

        try {
            $log->addLog("Начал выполнение метода");

            if ($edge_id != '' && $parameter_id != '' && $parameter_type_id != '' && $value != '' && $value != 'empty' && $status_id != '' && $date_time != '') {
            } else {
                throw new Exception('Некоторые параметры имеют пустое значение');
            }

            $edge_parameter = EdgeParameter::findOne(['edge_id' => $edge_id, 'parameter_id' => $parameter_id, 'parameter_type_id' => $parameter_type_id]);
            if (!$edge_parameter) {
                $edge_parameter = new EdgeParameter();
                $edge_parameter->edge_id = $edge_id;                                                                    // id ветви
                $edge_parameter->parameter_id = $parameter_id;                                                          // параметр id
                $edge_parameter->parameter_type_id = $parameter_type_id;                                                // тип параметра (справочный/измеренный/вычисленный)
                if (!$edge_parameter->save()) {
                    $log->addData($edge_parameter->errors, '$edge_parameter->errors', __LINE__);
                    throw new Exception("Ошибка сохранения параметров ветви $edge_id. Модели EdgeParameter");
                }
            }
            $edge_parameter_id = $edge_parameter->id;

            $edge_parameter_value = new EdgeParameterHandbookValue();
            $edge_parameter_value->edge_parameter_id = $edge_parameter_id;                                              // id параметра ветви
            if ($date_time == 1) $date_time = Assistant::GetDateTimeNow();                                              //время текущее
            $edge_parameter_value->date_time = $date_time;
            $edge_parameter_value->value = (string)$value;                                                              // значение справочное
            $edge_parameter_value->status_id = $status_id;                                                              // статус значения
            if (!$edge_parameter_value->save()) {
                $log->addData($edge_parameter_value->errors, '$edge_parameter_value->errors', __LINE__);
                throw new Exception("Ошибка сохранения значения параметра $edge_parameter_id. Модели EdgeParameterHandbookValue");
            }

            $result = $edge_parameter_value->value;

            $edge_param_to_cache = EdgeCacheController::buildStructureEdgeParametersValue(
                $edge_id,
                $edge_parameter_id,
                $parameter_id,
                $parameter_type_id,
                $date_time,
                $value,
                $status_id
            );

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result, 'edge_param_to_cache' => $edge_param_to_cache, 'edge_parameter_id' => $edge_parameter_id], $log->getLogAll());
    }

    /**
     * getEdgeParameterValue() - метод получения вычисляемых значений параметров выработки в БД EdgeParameterValue
     *
     * Входные необязательные параметры
     * @param $edge_id - идентификатор ветви.
     * @param $parameter_id - ключ параметра
     * @param $parameter_type_id - ключ типа параметра
     *
     * @return array/bool возвращает true при успешном добавлении в кэш, иначе false
     *
     *
     *
     * @author Якимов М.Н.
     * Created date: on 31.05.2019 11:51
     */
    public static function getEdgeParameterValue($edge_id = '*', $parameter_id = '*', $parameter_type_id = 2)
    {
        $sql_filter = 'parameter_type_id = ' . $parameter_type_id;

        if ($parameter_type_id == '*') {
            $sql_filter = "parameter_type_id in (2, 3)";
        }

        if ($parameter_id !== '*') {
            $sql_filter .= " and parameter_id = $parameter_id";
        }

        if ($edge_id !== '*') {
            $sql_filter .= " and edge_id = $edge_id";
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
            ->from('view_initEdgeParameterValue')
            ->where($sql_filter)
            ->all();

        return $edge_parameter_values;
    }

    /**
     * getEdgeParameterHandbookValue() - метод получения справочных значений параметров ветви в БД EdgeParameterHandbookValue
     *
     * Входные необязательные параметры
     * @param $edge_id - идентификатор оборудования.
     * @param $parameter_id - ключ параметра
     *
     * @return array/bool возвращает true при успешном добавлении в кэш, иначе false
     *
     *
     *
     * @author Якимов М.Н.
     * Created date: on 31.05.2019 11:51
     */
    public static function getEdgeParameterHandbookValue($edge_id = '*', $parameter_id = '*'): array
    {
        $sql_filter = 'parameter_type_id = 1';

        if ($parameter_id !== '*') {
            $sql_filter .= " and parameter_id = $parameter_id";
        }

        if ($edge_id !== '*') {
            $sql_filter .= " and edge_id = $edge_id";
        }

        $edge_parameter_handbook_values = (new Query())
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

        return $edge_parameter_handbook_values;
    }

    /**
     * saveEdgeStatus - Метод создания статуса выработки
     * @param $edge_id - ключ выработки
     * @param $status_id - статус значения выработки
     * @param $date_time - дата/время создания выработки
     * @return array
     */
    public static function saveEdgeStatus($edge_id, $status_id, $date_time = 1)
    {
        $log = new LogAmicumFront("saveEdgeStatus");

        $result = array();

        try {

            $log->addLog("Начал выполнение метода");

            if ($edge_id == '') {
                throw new Exception("Не передан edge_id");
            }

            if ($status_id == '') {
                throw new Exception("Не передан status_id");
            }

            if ($date_time == 1) {
                $date_time = Assistant::GetDateTimeNow();
            }

            $edge_status = new EdgeStatus();
            $edge_status->edge_id = $edge_id;
            $edge_status->status_id = $status_id;
            $edge_status->date_time = $date_time;
            if (!$edge_status->save()) {
                $log->addData($edge_status->errors, '$edge_status->errors', __LINE__);
                throw new Exception("Не смог сохранить статус выработки $edge_id");
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}
