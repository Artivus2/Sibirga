<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers;

use backend\controllers\cachemanagers\EdgeCacheController;
use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\cachemanagers\ServiceCache;
use backend\controllers\const_amicum\ParamEnum;
use backend\controllers\const_amicum\ParameterTypeEnumController;
use Exception;
use frontend\controllers\system\LogAmicumFront;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Yii;
use yii\db\Query;

class CoordinateController
{
    // actionBuildGraph                 - первичное построение графа
    // buildSensorGraph                 - построение графа для конкретного сенсора
    // updateSensorGraph                - обновление графа для конкретного сенсора
    // buildMineGraphCacheKey           - генерирует структуру ключа для хранения полного графа шахты
    // buildEdgesCacheKey               - генерирует структуру ключа для хранения списка эджей
    // buildSensorGraphCacheKey         - генерирует структуру ключа для кэша графа конкретного сенсора
    // attachEdges                      - присоединяет ребра к графу
    // calculateCoordinates             - функция расчёта координаты точки по услышанным узлам
    // findNeedDot                      - поиск нужной точки в которой находится светильник
    // calculateProjectionDotOnEdge     - вычисление проекции точки на выработке
    // calculateDistanceToVector        - вычисляет расстояние от точки до вектора
    // findPossibleDotsCombinations     - нахождение всех комбинаций возможных точек между собой
    // calculateDistanceToRssi          - вычисление расстояния на котором "слышен" узел связи
    // calculatePossibleNeedDots        - вычисление возможных точек нахождения светильника на графе
    // calculateSpeed                   - вычисление скорости преодоления расстояния между двумя точками
    // setEdgeListCache                 - Сохранение в кеше списка выработок, по которым идёт построение графов и вычисление координат
    // getEdgeListCache                 - Получение из кэша списка выработок, по которым идёт построение графов и вычисление координат
    // delGraph                         - Удаление графа конкретного сенсора из кэша
    // setGraph                         - Добавление графа сенсора в кэш
    // getGraph                         - Получение графа сенсора из кэша

    // amicum_rSet
    // amicum_rGet

    /**
     * Базовое значение ключа для кэша графа конкретного сенсора
     */
    const SENSOR_GRAPH_CACHE_BASE = 'SeGr';

    /**
     * Базовое значение ключа для хранения полного графа шахты без учёта узлов связи
     */
    const MINE_GRAPH_CACHE_BASE = 'MiGr';

    /**
     * Базовое значение ключа для хранения списка эджей
     */
    const EDGES_CACHE_BASE = 'Ed';

    /**
     * Максимальная дистанция, после которой прекращается построение графов
     * для конкретных сенсоров
     */
    const GRAPH_MAX_DIST = 300;

    /**
     * Количество знаков после запятой при вычислении координат результирующей точки.
     *
     * Введено из-за того, что в БД максимальная длина строки с кординатами в
     * таблице event_journal не может превышать 45 символов. При превышении
     * выкидывается ошибка во время сохранения.
     */
    const PRECISION = 2;


    public $redis_cache;


    public function __construct()
    {
        $this->redis_cache = Yii::$app->redis_service;
    }


    /**
     * Начальная инициализация графа шахты для каждого сенсора
     * @param $mine_id -   идентификатор шахты
     * @return array
     */
    public function buildGraph($mine_id = '')
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();

        $start = microtime(true);

        $warnings[] = 'buildGraph. Начал выполнять метод';
        $start_mem = memory_get_usage();
        $warnings[] = 'start mem ' . $start_mem / 1024;
        try {
            /**
             * Проверка входных параметров
             */
            if ($mine_id == '') {
                $post = Assistant::GetServerMethod();
                if (isset($post) && $post !== '') {
                    $mine_id = $post['mine_id'];
                    $warnings[] = "buildGraph. Получен входной параметр mine_id $mine_id";
                } else {
                    throw new Exception('buildGraph. Не передан входной параметр mine_id');
                }
            }

            /**
             * Получение списка эджей
             */
            $edges = (new EdgeCacheController())->multiGetEdgeScheme($mine_id);
            if ($edges === false) {
                $edges = (new EdgeCacheController())->initEdgeScheme($mine_id);
                if ($edges === false) {
                    throw new Exception('buildGraph. Не удалось получить список эджей ни из БД ни из кеша mine_id: ' . $mine_id);
                }
                $warnings[] = 'buildGraph. Получил схему шахты из БД';
            } else {
                $warnings[] = 'buildGraph. Получил схему шахты из кеша';
            }

            $dur = microtime(true) - $start;
            $warnings[] = 'buildGraph. Получили список эджей ' . $dur;

            /**
             * Удаление лишних параметров, расчёт длины ребра, построение графа
             * без учёта узлов связи на ребрах
             */
            $mine_graph = array();
            $edge_list = array();
            foreach ($edges as $edge) {
                unset($edge['place_title'],
                    $edge['place_object_id'],
                    $edge['danger_zona'],
                    $edge['color_edge'],
                    $edge['color_edge_rus'],
                    $edge['mine_id'],
                    $edge['conveyor'],
                    $edge['conveyor_tag'],
                    $edge['value_ch'],
                    $edge['value_co'],
                    $edge['date_time']
                );
                $edge['length'] = OpcController::calcDistance(
                    $edge['xStart'], $edge['yStart'], $edge['zStart'],
                    $edge['xEnd'], $edge['yEnd'], $edge['zEnd']
                );

                // Данный массив нужен для дальнейшего поиска эджа, на который
                // мы будем ставить узел связи
                $edge_list[$edge['edge_id']] = $edge;

                // Нужен для построения графов для каждого сенсора и их обновления
                $mine_graph[$edge['conjunction_start_id']][$edge['conjunction_end_id']] = $edge;
                $mine_graph[$edge['conjunction_end_id']][$edge['conjunction_start_id']] = $edge;
            }
            $this->setEdgeListCache($mine_id, $edge_list);

            unset($edges);

            $dur = microtime(true) - $start;
            $warnings[] = 'buildGraph. Сформировали граф шахты без учета узлов связи ' . $dur;

            /**
             * Получение списка сенсоров по шахте
             */
            $sensor_cache_controller = new SensorCacheController();
            $sensors = $sensor_cache_controller->getSensorMineHash($mine_id);
            if ($sensors === false) {
                $sensor_cache_controller->runInitHash($mine_id);
                $sensors = $sensor_cache_controller->getSensorMineHash($mine_id);
                if ($sensors === false) {
                    throw new Exception('buildGraph. Не удалось получить сенсоры ни из БД ни из кеша mine_id: ' . $mine_id);
                }
                $warnings[] = 'buildGraph. Получил сенсоры шахты из БД';
            } else {
                $warnings[] = 'buildGraph. Получил сенсоры шахты из кеша';
            }

            $dur = microtime(true) - $start;
            $warnings[] = 'buildGraph. Получили список сенсоров ' . $dur;

            /**
             * Поиск узлов связи из общего списка сенсоров.
             * Для каждого узла связи строится свой граф. Дальность прохода по
             * данному графу относительно узла связи немного больше 300 метров
             */

            $sensor_parameter_handbook_values = (new Query())
                ->select([
                    'sensor_id',
                    'sensor_parameter_id',
                    'parameter_id',
                    'parameter_type_id',
                    'date_time',
                    'value',
                    'status_id'
                ])
                ->from('view_initSensorParameterHandbookValue')
                ->where(['parameter_id' => 83])
                ->orWhere(['parameter_id' => 269])
                ->all();
            $sphvs_hand = [];
            foreach ($sensor_parameter_handbook_values as $sensor_parameter_handbook_value) {
                $sphvs_hand[$sensor_parameter_handbook_value['sensor_id']][$sensor_parameter_handbook_value['parameter_id']] = $sensor_parameter_handbook_value;
            }
            foreach ($sensors as $sensor) {
                $sensor_id = $sensor['sensor_id'];
                if (SensorCacheController::isCommnodeSensor($sensor['object_id'])) {
                    $response = $this->buildSensorGraph($sensor_id, $edge_list, $mine_graph, $sensor_cache_controller, $sensor['sensor_title'], $sphvs_hand);
                    if ($response['status'] == 0) {
                        $errors[] = $response['errors'];
                    }
                }
//                else {
//                    $warnings[] = "Сенсор $sensor_id не является узлом связи";
//                }
            }

            $dur = microtime(true) - $start;
            $warnings[] = 'buildGraph. Сформировали граф для каждого сенсора ' . $dur;

            unset($mine_graph, $edge_list);
        } catch (Throwable $exception) {
            $errors[] = 'actionBuildGraph. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $end_mem = memory_get_usage();
        $warnings[] = 'end mem ' . $end_mem / 1024;
        $peak = memory_get_peak_usage();
        $warnings[] = 'peaak ' . $peak / 1024;
        $result_main = array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
        //Yii::$app->response->format = Response::FORMAT_JSON;
        //Yii::$app->response->data = $result_main;
    }

    /**
     * Сохранение в кеше списка выработок, по которым идёт построение графов и
     * вычисление координат
     * @param int $mine_id Идентификатор шахты
     * @param array $edge_list Массив выработок. Структура элемента следующая:
     *   [
     *      "edge_id",
     *      "place_id",
     *      "conjunction_start_id",
     *      "conjunction_end_id",
     *      "xStart",
     *      "yStart",
     *      "zStart",
     *      "xEnd",
     *      "yEnd",
     *      "zEnd",
     *      "length"
     *   ]
     */
    public function setEdgeListCache($mine_id, $edge_list)
    {
        $this->amicum_rSet(self::buildEdgesCacheKey($mine_id), $edge_list);
    }

    /**
     * Получение из кэша списка выработок, по которым идёт построение графов и
     * вычисление координат
     * @param int $mine_id Идентификатор шахты
     * @return bool
     */
    public function getEdgeListCache($mine_id)
    {
        return $this->amicum_rGet(self::buildEdgesCacheKey($mine_id));
    }

    /**
     * Удаление графа конкретного сенсора из кэша
     * @param $sensor_id - идентификатор конкретного сенсора
     */
    public function delGraph($sensor_id)
    {
        $redis_cache_key = self::buildSensorGraphCacheKey($sensor_id);
        $keys = $this->redis_cache->scan(0, 'MATCH', $redis_cache_key, 'COUNT', '10000000')[1];
        if ($keys) {
            $this->amicum_mDel($keys);
        }
    }

    /**
     * Добавление графа сенсора в кэш
     * @param int $sensor_id Идентификатор сенсора
     * @param array $sensor_graph Граф сенсора
     */
    public function setGraph($sensor_id, $sensor_graph)
    {
        $this->amicum_rSet(self::buildSensorGraphCacheKey($sensor_id), $sensor_graph);
    }

    /**
     * Получение графа сенсора из кэша
     * @param int $sensor_id Идентификатор сенсора
     * @return bool
     */
    public function getGraph($sensor_id)
    {
        return $this->amicum_rGet(self::buildSensorGraphCacheKey($sensor_id));
    }

    /**
     * Метод построения графа для конкретного сенсора
     * @param $sensor_id -   идентификатор сенсора
     * @param $edge_list -   список эджей шахты
     * @param $mine_graph -   граф шахты
     * @param SensorCacheController $sensor_cache_controller
     * @param $sensor_title -   название сенсора (не обязательный нужен для отладки)
     * @param $sphvs_hand -   справочник параметров сенсоров
     * Передаётся в функцию по ссылке, т.к. при расчёте координат требуется
     * занести узлы связи в справочник сопряжений.
     *
     * @return array
     */
    private function buildSensorGraph($sensor_id, $edge_list, $mine_graph,
                                      SensorCacheController $sensor_cache_controller, $sensor_title = '', $sphvs_hand = [])
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        try {
            $sensor_xyz = false;
            $sensor_edge = false;
            if (!empty($sphvs_hand) and $sphvs_hand) {
                if (isset($sphvs_hand[$sensor_id][ParamEnum::COORD])) {
                    $sensor_xyz = $sphvs_hand[$sensor_id][ParamEnum::COORD];
                }
                if (isset($sphvs_hand[$sensor_id][ParamEnum::EDGE_ID])) {
                    $sensor_edge = $sphvs_hand[$sensor_id][ParamEnum::EDGE_ID];
                }
            } else {
                $sensor_xyz = $sensor_cache_controller->getParameterValueHash($sensor_id, ParamEnum::COORD, ParameterTypeEnumController::REFERENCE);
                $sensor_edge = $sensor_cache_controller->getParameterValueHash($sensor_id, ParamEnum::EDGE_ID, ParameterTypeEnumController::REFERENCE);
            }

            if ($sensor_xyz && $sensor_edge && $sensor_xyz['value'] && $sensor_edge['value']) {
                $coordinates = explode(',', $sensor_xyz['value']);
                $edge_id = $sensor_edge['value'];

                if (!isset($edge_list[$edge_id])) {
                    throw new Exception("buildSensorGraph. Эджа $edge_id сенсора $sensor_id $sensor_title нет в кеше схемы шахты");
                }

                /**
                 * Построение графа для сенсора
                 */
                $sensor_edge_conj_start = $edge_list[$edge_id]['conjunction_start_id'];
                $sensor_edge_conj_end = $edge_list[$edge_id]['conjunction_end_id'];

                $sensor_graph[$sensor_id][$sensor_edge_conj_start]['edge_id'] = $edge_id;
                $sensor_graph[$sensor_id][$sensor_edge_conj_start]['place_id'] = $edge_list[$edge_id]['place_id'];
                $sensor_graph[$sensor_id][$sensor_edge_conj_start]['conjunction_start_id'] = $sensor_id;
                $sensor_graph[$sensor_id][$sensor_edge_conj_start]['conjunction_end_id'] = $sensor_edge_conj_start;
                $sensor_graph[$sensor_id][$sensor_edge_conj_start]['xStart'] = $coordinates[0];
                $sensor_graph[$sensor_id][$sensor_edge_conj_start]['yStart'] = $coordinates[1];
                $sensor_graph[$sensor_id][$sensor_edge_conj_start]['zStart'] = $coordinates[2];
                $sensor_graph[$sensor_id][$sensor_edge_conj_start]['xEnd'] = $edge_list[$edge_id]['xStart'];
                $sensor_graph[$sensor_id][$sensor_edge_conj_start]['yEnd'] = $edge_list[$edge_id]['yStart'];
                $sensor_graph[$sensor_id][$sensor_edge_conj_start]['zEnd'] = $edge_list[$edge_id]['zStart'];
                $sensor_graph[$sensor_id][$sensor_edge_conj_start]['length'] = OpcController::calcDistance(
                    $coordinates[0], $coordinates[1], $coordinates[2],
                    $edge_list[$edge_id]['xStart'], $edge_list[$edge_id]['yStart'], $edge_list[$edge_id]['zStart']
                );

                $sensor_graph[$sensor_id][$sensor_edge_conj_end]['edge_id'] = $edge_id;
                $sensor_graph[$sensor_id][$sensor_edge_conj_end]['place_id'] = $edge_list[$edge_id]['place_id'];
                $sensor_graph[$sensor_id][$sensor_edge_conj_end]['conjunction_start_id'] = $sensor_id;
                $sensor_graph[$sensor_id][$sensor_edge_conj_end]['conjunction_end_id'] = $sensor_edge_conj_end;
                $sensor_graph[$sensor_id][$sensor_edge_conj_end]['xStart'] = $coordinates[0];
                $sensor_graph[$sensor_id][$sensor_edge_conj_end]['yStart'] = $coordinates[1];
                $sensor_graph[$sensor_id][$sensor_edge_conj_end]['zStart'] = $coordinates[2];
                $sensor_graph[$sensor_id][$sensor_edge_conj_end]['xEnd'] = $edge_list[$edge_id]['xEnd'];
                $sensor_graph[$sensor_id][$sensor_edge_conj_end]['yEnd'] = $edge_list[$edge_id]['yEnd'];
                $sensor_graph[$sensor_id][$sensor_edge_conj_end]['zEnd'] = $edge_list[$edge_id]['zEnd'];
                $sensor_graph[$sensor_id][$sensor_edge_conj_end]['length'] = OpcController::calcDistance(
                    $coordinates[0], $coordinates[1], $coordinates[2],
                    $edge_list[$edge_id]['xEnd'], $edge_list[$edge_id]['yEnd'], $edge_list[$edge_id]['zEnd']
                );

                self::attachEdges($sensor_graph, $sensor_edge_conj_start, $sensor_edge_conj_end, $mine_graph, 0);
                self::attachEdges($sensor_graph, $sensor_edge_conj_end, $sensor_edge_conj_start, $mine_graph, 0);

                $this->setGraph($sensor_id, $sensor_graph);

                unset($sensor_graph);
            } else {
                throw new Exception("buildSensorGraph. Отсутствует пар-р 83 или 269 у сенсора $sensor_id $sensor_title");
            }
        } catch (Throwable $exception) {
            $errors[] = 'buildSensorGraph. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Обновление графа для конкретного сенсора
     * @param $sensor_id -   идентификатор сенсора
     * @param $mine_id -   идентификатор шахты
     * @param $sensor_title -   название сенсора для отладки
     * @return array
     */
    public function updateSensorGraph($sensor_id, $mine_id, $sensor_title = '')
    {
        $errors = array();
        $status = 1;
        $result = array();
        $warnings = array();

        try {
            /**
             * Получение списка эджей
             */
            $edges = (new EdgeCacheController())->multiGetEdgeScheme($mine_id);
            if ($edges === false) {
                $edges = (new EdgeCacheController())->initEdgeScheme($mine_id);
                if ($edges === false) {
                    throw new Exception('buildGraph. Не удалось получить список эджей ни из БД ни из кеша mine_id: ' . $mine_id);
                }
                $warnings[] = 'buildGraph. Получил схему шахты из БД';
            } else {
                $warnings[] = 'buildGraph. Получил схему шахты из кеша';
            }

            /**
             * Удаление лишних параметров, расчёт длины ребра, построение графа
             * без учёта узлов связи на ребрах
             */
            $mine_graph = array();
            $edge_list = array();
            foreach ($edges as $edge) {
                unset($edge['place_title'],
                    $edge['place_object_id'],
                    $edge['danger_zona'],
                    $edge['color_edge'],
                    $edge['color_edge_rus'],
                    $edge['mine_id'],
                    $edge['conveyor'],
                    $edge['conveyor_tag'],
                    $edge['value_ch'],
                    $edge['value_co'],
                    $edge['date_time']
                );
                $edge['length'] = OpcController::calcDistance(
                    $edge['xStart'], $edge['yStart'], $edge['zStart'],
                    $edge['xEnd'], $edge['yEnd'], $edge['zEnd']
                );

                // Данный массив нужен для дальнейшего поиска эджа, на который
                // мы будем ставить узел связи
                $edge_list[$edge['edge_id']] = $edge;

                // Нужен для построения графов для каждого сенсора и их обновления
                $mine_graph[$edge['conjunction_start_id']][$edge['conjunction_end_id']] = $edge;
                $mine_graph[$edge['conjunction_end_id']][$edge['conjunction_start_id']] = $edge;
            }
            $this->setEdgeListCache($mine_id, $edge_list);

            /**
             * Обновление графа для конкретного сенсора
             */
            $response = $this->buildSensorGraph($sensor_id, $edge_list, $mine_graph,
                new SensorCacheController(), $sensor_title);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("updateSensorGraph. Ошибка обновления графа для сенсора $sensor_id");
            }

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'updateSensorGraph.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * Генерирует структуру ключа для кэша графа шахты
     * @param $mine_id -   идентификатор шахты
     * @return string
     *
     * Тесты:
     * @see CoordinateControllerTest::testBuildMineGraphCacheKey()
     */
    public static function buildMineGraphCacheKey($mine_id)
    {
        return self::MINE_GRAPH_CACHE_BASE . ':' . $mine_id;
    }


    /**
     * Генерирует структуру ключа для хранения списка эджей
     * @param $mine_id -   идентификатор шахты
     * @return string
     *
     * Тесты:
     * @see \CoordinateControllerTest::testBuildEdgesCacheKey()
     */
    public static function buildEdgesCacheKey($mine_id)
    {
        return self::EDGES_CACHE_BASE . ':' . $mine_id;
    }


    /**
     * Генерирует структуру ключа для кэша графа конкретного сенсора
     * @param $sensor_id -   идентификатор сенсора
     * @return string
     *
     * Тесты:
     * @see \CoordinateControllerTest::testBuildSensorGraphCacheKey()
     */
    public static function buildSensorGraphCacheKey($sensor_id)
    {
        return self::SENSOR_GRAPH_CACHE_BASE . ':' . $sensor_id;
    }


    /**
     * Присоединяет ребра к графу $sensor_graph начиная от точки $vertex_id.
     * Метод рекурсивный.
     *
     * $prev_vertex_id указывает с какой стороны идёт добавление. Нужен для того,
     * чтобы при первом проходе эдж не захватывался целиком. Причина в том, что
     * в $mine_graph хранятся ребра не разбитые узлами
     *
     * @param $sensor_graph -   результирующий граф для конкретного сенсора
     * @param $vertex_id -   идентификатор вершины (сопряжения), чьи
     *                              рёбра будем добавлять в граф
     * @param $prev_vertex_id -   идентификатор вершины из которой мы пришли
     * @param $mine_graph -   полный граф шахты без учёта узлов связи
     * @param $dist_covered -   дистанция, которую мы прошли
     */
    private static function attachEdges(&$sensor_graph, $vertex_id, $prev_vertex_id, $mine_graph, $dist_covered)
    {
        foreach ($mine_graph[$vertex_id] as $conj_end => $edge) {
            if ($conj_end != $prev_vertex_id && !isset($sensor_graph[$conj_end])) {
                $sensor_graph[$vertex_id][$conj_end] = $edge;
                $new_dist = $dist_covered + $edge['length'];
                if ($new_dist < self::GRAPH_MAX_DIST) {
                    self::attachEdges($sensor_graph, $conj_end, $vertex_id, $mine_graph, $new_dist);
                }
            }
        }
    }


    /**
     * Функция расчёта координаты точки по услышанным узлам
     * @param array $heared_nodes - массив услышанных узлов с уровнями сигнала
     * @param $mine_id - идентификатор шахты
     * @return array
     */
    public function calculateCoordinates(array $heared_nodes, $mine_id)
    {
        $method_name = "calculateCoordinates";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        $start = microtime(true);
        $error_number = null;

        $xyz = -1;
        $edge_id = -1;
        $place_id = -1;

        $warnings[] = 'calculateCoordinates. Начало метода';
        try {
            /** Отладка */
            $description = 'Начал метод';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            /**
             * Забираем из кэша графы услышанных узлов связи и для каждого
             * услышанного узла вычисляем возможные точки нахождения светильника
             */
            $possible_dots = array();
            $service_cache = new ServiceCache();
            foreach ($heared_nodes as $heared_node) {
                $network_id = $heared_node->address;
                $sensor_id = $service_cache->getSensorByNetworkId($network_id);
                // Если у какого-либо из услышанных узлов нет сенсора в кэше, то пропускаем его
                // и вычисляем координаты по оставшимся
                if ($sensor_id === false) {
                    $warnings[] = "calculateCoordinates. Нет привязки сетевого айди $network_id в кеше";
                    continue;
                }
                $heared_node_graph = $this->getGraph($sensor_id);
                // Если у какого-либо из услышанных узлов нет графа в кэше, то пропускаем его
                // и вычисляем координаты по оставшимся
                if ($heared_node_graph === false) {
                    $warnings[] = "calculateCoordinates. Нет графа выработок у сенсора $sensor_id в кеше с сетевым айди $network_id";
                    continue;
                    //throw new \Exception("calculateCoordinates. Для сенсора $sensor_id не удалось найти граф в кэше");
                }
                $warnings[] = "calculateCoordinates. Граф сенсора: " . $sensor_id;
                $warnings[] = $heared_node_graph;

                $distance_to_rssi = self::calculateDistanceToRssi($heared_node->rssi);
                $warnings[] = "calculateCoordinates. Возможное расстояние от узла связи до метки: ";
                $warnings[] = $distance_to_rssi;

                $response = self::calculatePossibleNeedDots($sensor_id, $heared_node_graph, $distance_to_rssi, 0);
                $possible_dots[] = $response['possible_dots'];
            }
            unset($service_cache);

            /** Отладка */
            $description = 'Закончил вычисление возможных точек';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            // Если ни по одному из услышанных узлов нет информации в кэше, то
            // прекращаем расчёт координат

            if (empty($possible_dots)) {
                if (isset($response['warnings'])) {
                    $warnings[] = $response['warnings'];
                }
                $error_number = 598;
                throw new Exception(__FUNCTION__ . '. Ни по одному из услышанных узлов нет информации в кэше. Или не смог произвести расчет возможных точек местоположения работника');
            }

            //$warnings['possible_dots'] = $response['warnings'];
            $dur = microtime(true) - $start;
            $warnings[] = 'calculateCoordinates. Получили графы и нашли на них возможные точки ' . $dur;

            /**
             * Составляем массив из всех возможных комбинаций точек, по
             * одной от каждого услышанного узла
             */
            $response = self::findPossibleDotsCombinations($possible_dots);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $possible_dots_combinatinations = $response['combinations'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('calculateCoordinates. Ошибка при нахождении комбинаций возможных точек');
            }

            $dur = microtime(true) - $start;
            $warnings[] = 'calculateCoordinates. Нашли все возможные комбинации точек ' . $dur;

            /** Отладка */
            $description = 'Закончил поиск возможных комбинаций точек';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            /**
             * Находим из всех комбинаций нужную, в которой периметр фигуры,
             * с вершинами в данных точках, наименьший.
             * Центр данной фигуры и будет исходной точкой координат
             */
            $edge_list = $this->getEdgeListCache($mine_id);
            if ($edge_list === false) {
                throw new Exception('calculateCoordinates. Списка выработок нет в кэше');
            }

            /** Отладка */
            $description = 'Закончил получение списка горных вырбаоток';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $response = self::findNeedDot($possible_dots_combinatinations);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $need_dot = $response['need_dot'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('calculateCoordinates. Ошибка при нахождении нужной точки');
            }

            $dur = microtime(true) - $start;
            $warnings[] = 'calculateCoordinates. Нашли нужную комбинацию и нужную точку по ней ' . $dur;

            /** Отладка */
            $description = 'Нашли нужную комбинацию и нужную точку по ней';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            /**
             * Находим проекции точки на все эджи из путей к точке
             */
            $dot_projections = array();
            foreach ($need_dot['possible_edges'] as $possible_edge) {
                if (isset($edge_list[$possible_edge])) {
                    $response = self::calculateProjectionDotOnEdge($need_dot, $edge_list[$possible_edge]);
                    if ($response['status'] == 1) {
                        $warnings[] = $response['warnings'];
                        $dot_projections[$possible_edge] = $response['normal_need_dot'];
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors_calc[] = $response['errors'];
                    }
                } else {
                    $errors[] = "calculateCoordinates. НЕ существующая выработка: " . $possible_edge . " Проверьте граф сенсоров - обновите";
                }
            }

            /** Отладка */
            $description = 'Нашли проекции точки на выработку';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            /**
             * Если проекция точки не попадает ни на одну выработку, то кидаем исключение
             * и записываем лог в БД
             */
            if (empty($dot_projections)) {
                $err_msg = 'Проекция точки не попадает ни на одну выработку. Услышанные узлы ' . print_r($heared_nodes, true) . ' ';
                $err_msg .= 'Исходная точка ' . print_r($need_dot, true);
                $errors[] = $errors_calc;
                Yii::$app->db_amicum_log->createCommand()->insert('strata_action_log', [
                    'date_time' => Assistant::GetDateNow(),
                    'metod_amicum' => 'calculateCoordinates',
                    'duration' => microtime(true) - $start,
                    'errors' => $err_msg,
                ])->execute();
                throw new Exception(__FUNCTION__ . '. Проекция точки не попадает ни на одну выработку');
            }


            /**
             * Вычисляем расстояние до каждой проекции и возвращаем ближайшую
             */
            $min_distance = INF;
            foreach ($dot_projections as $projection_edge => $projection_dot) {
                $distance_to_projection = OpcController::calcDistance(
                    $need_dot['x'], $need_dot['y'], $need_dot['z'],
                    $projection_dot['x'], $projection_dot['y'], $projection_dot['z']
                );
                if ($distance_to_projection < $min_distance) {
                    $min_distance = $distance_to_projection;
                    $normal_need_dot['x'] = $projection_dot['x'];
                    $normal_need_dot['y'] = $projection_dot['y'];
                    $normal_need_dot['z'] = $projection_dot['z'];
                    $normal_need_dot['edge_id'] = $projection_edge;
                }
            }

            /** Отладка */
            $description = 'Нашли точку';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


            $dur = microtime(true) - $start;
            $warnings[] = __FUNCTION__ . '. Вычислили проекцию точки на выработку ' . $dur;

            $xyz = $normal_need_dot['x'] . ',' . $normal_need_dot['y'] . ',' . $normal_need_dot['z'];
            $edge_id = $normal_need_dot['edge_id'];
            $place_id = $edge_list[$edge_id]['place_id'];

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();

            $errors[__FUNCTION__ . ' parameters'] = [
                '$heared_nodes' => $heared_nodes,
                '$mine_id' => $mine_id
            ];
        }

        /** Отладка */
        $description = 'Закончил выполнение метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        $method_duration = microtime(true) - $start;
        $warnings[] = __FUNCTION__ . ". Время выполнения = $method_duration";
        $warnings[] = __FUNCTION__ . ". Конец метода. xyz = $xyz, edge = $edge_id, place = $place_id";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug,
            'xyz' => $xyz, 'edge_id' => $edge_id, 'place_id' => $place_id, 'error_number' => $error_number);
        return $result_main;
    }


    /**
     * Вычисление проекции точки на выработке
     * @param $dot - точка
     * @param $edge - выработка
     * @return array
     *
     * Тесты:
     * @see \CoordinateControllerTest::testCalculateProjectionDotOnEdgeWithCorrectArguments()
     * @see \CoordinateControllerTest::testCalculateProjectionDotOnEdgeWithInvalidArgumentsThrowsException()
     * @see \CoordinateControllerTest::testCalculateProjectionDotOnEdgeWithZeroLength()
     */
    public static function calculateProjectionDotOnEdge($dot, $edge)
    {
        $warnings = array();
        $errors = array();
        $status = 1;

        $normal_need_dot = array();

        $warnings[] = 'calculateProjectionDotOnEdge. Начало метода';
        try {
            $v['x'] = $edge['xEnd'] - $edge['xStart'];
            $v['y'] = $edge['yEnd'] - $edge['yStart'];
            $v['z'] = $edge['zEnd'] - $edge['zStart'];
            //$warnings[] = __FUNCTION__ . '. $v = ' . print_r($v, true);

            $edge_length = pow($v['x'], 2) + pow($v['y'], 2) + pow($v['z'], 2);

            if ($edge_length === 0) {
                $normal_need_dot['x'] = $edge['xStart'];
                $normal_need_dot['y'] = $edge['yStart'];
                $normal_need_dot['z'] = $edge['zStart'];
                if (isset($edge['edge_id'])) {
                    $normal_need_dot['edge_id'] = $edge['edge_id'];
                    $normal_need_dot['place_id'] = $edge['place_id'];
                }
            } else {
                $t = ($dot['x'] * $v['x'] - $v['x'] * $edge['xStart'] +
                        $dot['y'] * $v['y'] - $v['y'] * $edge['yStart'] +
                        $dot['z'] * $v['z'] - $v['z'] * $edge['zStart']) / $edge_length;
                $t = round($t, self::PRECISION);
                //$warnings[] = $t;
                //$warnings[] = __FUNCTION__ . '. $t = ' . print_r($t, true);

                if ($t > 1 || $t < 0) {
                    $dot_coord = $dot['x'] . ',' . $dot['y'] . ',' . $dot['z'];
                    throw new Exception('Проекция точки ' . $dot_coord . ' не попадёт на эдж ' . print_r($edge, true));
                }

                $normal_need_dot['x'] = round($edge['xStart'] + $v['x'] * $t, self::PRECISION);
                $normal_need_dot['y'] = round($edge['yStart'] + $v['y'] * $t, self::PRECISION);
                $normal_need_dot['z'] = round($edge['zStart'] + $v['z'] * $t, self::PRECISION);
                if (isset($edge['edge_id'])) {
                    $normal_need_dot['edge_id'] = $edge['edge_id'];
                    $normal_need_dot['place_id'] = $edge['place_id'];
                }
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'calculateProjectionDotOnEdge. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'calculateProjectionDotOnEdge. Конец метода';
        return array('status' => $status, 'errors' => $errors, 'warnings' => $warnings,
            'normal_need_dot' => $normal_need_dot);
    }


    /**
     * Поиск нужной точки в которой находится светильник по комбинациям всех
     * возможных точек.
     *
     * Находим из всех комбинаций нужную, в которой периметр фигуры,
     * с вершинами в данных точках, наименьший.
     * Центр данной фигуры и будет исходной точкой координат
     *
     * @param $possible_dots_combination - комбинации возможных точек
     *
     * @return array
     */
    public static function findNeedDot($possible_dots_combination)
    {
        $warnings = array();
        $errors = array();
        $status = 1;

        $need_dot = array();

        $warnings[] = 'findNeedDot. Начало метода';
        try {
            $min_perimeter_iter = -1;
            $min_perimeter = INF;
            foreach ($possible_dots_combination as $combination_key => $combination) {
                $dot_count = count($combination);
                $current_perimeter = 0;
                for ($i = 0; $i < $dot_count - 1; $i++) {
                    $current_perimeter += OpcController::calcDistance(
                        $combination[$i]['x'], $combination[$i]['y'], $combination[$i]['z'],
                        $combination[$i + 1]['x'], $combination[$i + 1]['y'], $combination[$i + 1]['z']
                    );
                }
                $current_perimeter += OpcController::calcDistance(
                    $combination[0]['x'], $combination[0]['y'], $combination[0]['z'],
                    $combination[$dot_count - 1]['x'], $combination[$dot_count - 1]['y'], $combination[$dot_count - 1]['z']
                );

                if ($current_perimeter < $min_perimeter) {
                    $warnings[] = "findNeedDot. Новый минимальный периметр = $current_perimeter";
                    $warnings[] = 'findNeedDot. Комбинация:';
                    $warnings[] = $combination;
                    $min_perimeter = $current_perimeter;
                    $min_perimeter_iter = $combination_key;
                }
            }

            $sum_x = 0;
            $sum_y = 0;
            $sum_z = 0;
            $possible_edges = array();
            foreach ($possible_dots_combination[$min_perimeter_iter] as $dot) {
                $sum_x += $dot['x'];
                $sum_y += $dot['y'];
                $sum_z += $dot['z'];
                $possible_edges = array_unique(array_merge($possible_edges, $dot['path']));
            }
            $warnings[] = 'findNeedDot. Список возможных эджей для нужной комбинации точек:';
            $warnings[] = $possible_edges;

            $need_dot['x'] = $sum_x / count($possible_dots_combination[$min_perimeter_iter]);
            $need_dot['y'] = $sum_y / count($possible_dots_combination[$min_perimeter_iter]);
            $need_dot['z'] = $sum_z / count($possible_dots_combination[$min_perimeter_iter]);
            $need_dot['possible_edges'] = $possible_edges;
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'findNeedDot. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'findNeedDot. Конец метода';
        return array('status' => $status, 'errors' => $errors, 'warnings' => $warnings,
            'need_dot' => $need_dot);
    }


    /**
     * Вычисляет расстояние от точки до вектора
     * @param $dot - точка
     * @param $vector_start - начало вектора
     * @param $vector_end - конец вектора
     * @return float|int
     *
     * Тесты:
     * @see \CoordinateControllerTest::testCalculateDistanceToVectorWithCorrectArguments()
     * @see \CoordinateControllerTest::testCalculateDistanceToVectorWithInvalidArguments()
     * @see \CoordinateControllerTest::testCalculateDistanceToVectorWithZeroLength()
     */
    public static function calculateDistanceToVector($dot, $vector_start, $vector_end)
    {
        // Вектор эджа
        $v['x'] = $vector_end['x'] - $vector_start['x'];
        $v['y'] = $vector_end['y'] - $vector_start['y'];
        $v['z'] = $vector_end['z'] - $vector_start['z'];

        // Проверка является ли прямая точкой. В таком случае просто находим
        // расстояние между двумя точками
        if ($v['x'] == 0 && $v['y'] == 0 && $v['z'] == 0) {
            return sqrt(
                pow($dot['x'] - $vector_start['x'], 2) +
                pow($dot['y'] - $vector_start['y'], 2) +
                pow($dot['z'] - $vector_start['z'], 2)
            );
        }

        // Вектор от точки до эджа
        $w0['x'] = $vector_start['x'] - $dot['x'];
        $w0['y'] = $vector_start['y'] - $dot['y'];
        $w0['z'] = $vector_start['z'] - $dot['z'];

        // Скалярное произведение двух векторов
        $vw0['x'] = $w0['y'] * $v['z'] - $w0['z'] * $v['y'];
        $vw0['y'] = $w0['x'] * $v['z'] - $w0['z'] * $v['x'];
        $vw0['z'] = $w0['x'] * $v['y'] - $w0['y'] * $v['x'];

        // Площадь параллелограмма лежащего на двух векторах
        $vw0_square = sqrt(pow($vw0['x'], 2) + pow($vw0['y'], 2) + pow($vw0['z'], 2));

        // Длина вектора (эджа)
        $v_length = sqrt(pow($v['x'], 2) + pow($v['y'], 2) + pow($v['z'], 2));

        // Расстояние от точки до вектора
        return $vw0_square / $v_length;
    }


    /**
     * Нахождение всех комбинаций возможных точек между собой.
     *
     * Из каждой комбинации берётся по одной точке.
     *
     * Функция составляет все возможные комбинации элементов в множестве массивов,
     * поэтому не зависит от типов элементов в массиве
     *
     * @param $possible_dots_on_nodes - массив наборов возможных точек, в которых
     * может находится светильник относительно узлов связи
     *
     * @return array
     *
     * Тесты:
     * @see \CoordinateControllerTest::testFindPossibleDotsCombinations()
     */
    public static function findPossibleDotsCombinations($possible_dots_on_nodes)
    {
        $warnings = array();
        $errors = array();
        $status = 1;

        $combinations = [[]];

        $warnings[] = 'findPossibleDotsCombinations. Начало метода';
        try {

            foreach ($possible_dots_on_nodes as $possible_dots_set) {
                $tmp = [];
                foreach ($combinations as $v1) {
                    foreach ($possible_dots_set as $v2) {
                        $tmp[] = array_merge($v1, [$v2]);
                    }
                }
                $combinations = $tmp;
            }
        } catch (Throwable $exception) {
            $errors[] = 'findPossibleDotsCombinations. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $warnings[] = 'findPossibleDotsCombinations. Конец метода';
        return array('status' => $status, 'errors' => $errors, 'warnings' => $warnings,
            'combinations' => $combinations);
    }


    /**
     * Вычисление расстояния на котором узел связи "слышен" с уровнем сигнала $rssi
     * @param $rssi - уровень сигнала, на котором "слышен" узел связи
     * @return float|int
     */
    public static function calculateDistanceToRssi($rssi)
    {
        // Уровень затухания на расстоянии 1 метра от узла
        $start_rssi = -24;

        // Коэффициент потерь мощности сигнала при распространении в среде,
        // безразмерная величина (для воздуха n = 2; увеличивается при наличии препятствий)
        //$n = 2;
        $n = 3;

        return pow(10, ($start_rssi - $rssi) / (10 * $n));

        // Формула выведенная Андреем П.
        /*$diff_rssi = $start_rssi - $rssi;
        return 0.0006 * $diff_rssi * $diff_rssi + 2.3823 * $diff_rssi - 27.0336;*/
    }


    /**
     * Вычисление возможных точек нахождения светильника на графе
     * относительно уровня затухания сигнала
     *
     * @param $start_point - Идентификатор точки из которой начинается обход графа.
     * Является идентификатором сенсора, которому принадлежит граф $sensor_graph.
     * По мере рекурсивного прохода точка смещается на соседние идентификаторы
     * сопряжений
     *
     * @param $sensor_graph - Граф конкретного сенсора
     *
     * @param $distance_to_rssi - Расстояние на котором был услышан узел связи
     *
     * @param int $distance_traveled - Дистанция, пройденная по графу.
     * Является одним из условий выхода из рекурсии.
     * При явном вызове функции задавать значение не нужно!
     *
     * @param array $traveled_edges
     *
     * @return array
     *
     * @warnings Функция рекурсивная
     */
    public static function calculatePossibleNeedDots($start_point, $sensor_graph, $distance_to_rssi, $distance_traveled = 0, $traveled_edges = [])
    {
        $warnings[] = array();

        $possible_dots = array();

        $warnings[] = "Начал обход с вершины $start_point";

        foreach ($sensor_graph[$start_point] as $edge_end_id => $edge) {
            // Индекс массива пройденных выработок совпадает со значением для удаления дубликатов.
            // При этом теряется порядок элементов, что может быть неоптимально в дальнейшем на больших массивах
            $traveled_edges[$edge['edge_id']] = $edge['edge_id'];
            $warnings[] = "Иду в вершину $edge_end_id";
            $warnings[] = "Нахожусь на выработке {$edge['edge_id']}";
            $current_distance = $distance_traveled + $edge['length'];
            if ($distance_to_rssi < $current_distance) {
                $warnings[] = "distance_to_rssi < current_distance $distance_to_rssi < $current_distance";

                $distance_from_end_to_dot = $current_distance - $distance_to_rssi;
                $warnings[] = "Дистанция от конца ребра до нужной точки = $distance_from_end_to_dot";

                $warnings[] = 'Длина ребра = ' . $edge['length'];
                $location_parameter = $distance_from_end_to_dot / $edge['length'];
                $warnings[] = "Множитель на который будем сдвигать точку = $location_parameter";

                // Определяем точку начала выработки по пути нашего прохода.
                // Сделано так, потому что направление выработки по базе данных
                // может не совпадать с нашим направлением прохода
                if ($start_point == $edge['conjunction_start_id']) {
                    $xS = $edge['xStart'];
                    $yS = $edge['yStart'];
                    $zS = $edge['zStart'];
                    $xE = $edge['xEnd'];
                    $yE = $edge['yEnd'];
                    $zE = $edge['zEnd'];
                } else {
                    $xS = $edge['xEnd'];
                    $yS = $edge['yEnd'];
                    $zS = $edge['zEnd'];
                    $xE = $edge['xStart'];
                    $yE = $edge['yStart'];
                    $zE = $edge['zStart'];
                }

                $possible_dot['x'] = $xE - ($xE - $xS) * $location_parameter;
                $possible_dot['y'] = $yE - ($yE - $yS) * $location_parameter;
                $possible_dot['z'] = $zE - ($zE - $zS) * $location_parameter;
                $possible_dot['edge_id'] = $edge['edge_id'];
                $possible_dot['path'] = $traveled_edges;
                $warnings[] = 'Найденная точка';
                $warnings[] = $possible_dot;
                $possible_dots[] = $possible_dot;
            } else {
                if (!isset($sensor_graph[$edge_end_id])) {
                    $warnings[] = "Конец графа, из вершины $edge_end_id не выходит ребёр";

                    if ($edge_end_id == $edge['conjunction_start_id']) {
                        $possible_dot['x'] = $edge['xStart'];
                        $possible_dot['y'] = $edge['yStart'];
                        $possible_dot['z'] = $edge['zStart'];
                    } else {
                        $possible_dot['x'] = $edge['xEnd'];
                        $possible_dot['y'] = $edge['yEnd'];
                        $possible_dot['z'] = $edge['zEnd'];
                    }
                    $possible_dot['edge_id'] = $edge['edge_id'];
                    $possible_dot['path'] = $traveled_edges;
                    $possible_dots[] = $possible_dot;
                } else {
                    $warnings[] = "Идём на следующую вершину, пройденный путь $current_distance";
                    $response = self::calculatePossibleNeedDots($edge_end_id, $sensor_graph, $distance_to_rssi, $current_distance, $traveled_edges);
                    $possible_dots = array_merge($possible_dots, $response['possible_dots']);
                    $warnings[] = $response['warnings'];
                }
            }
        }


        // Проблема - возможно такое, что точка находится на выработке, до которой
        // не успевает дойти алгоритм.
        // Костыль - писать все графы в возможные выработки
        $possible_edges = [];
        foreach ($sensor_graph as $edge_start) {
            foreach ($edge_start as $edge_end => $edge) {
                $possible_edges[$edge['edge_id']] = $edge['edge_id'];
            }
        }

        foreach ($possible_dots as &$possible_dot) {
            $possible_dot['path'] = $possible_edges;
        }

        return array('possible_dots' => $possible_dots, 'warnings' => $warnings);
    }


    /**
     * Вычисление скорости преодоления расстояния между двумя точками
     *
     * @param string $coordStart Начальная точка
     * @param string $timeStart Время нахождения в начальной точке
     * @param string $coordEnd Конечная точка
     * @param string $timeEnd Время нахождения в конечной точке
     *
     * @return array Скорость преодоления расстояния между двумя точками в м/с
     *
     * speed:
     *      speed_value             -   значение скорости
     *      generate_event          -   разрешение на генерацию события по превышению скорости
     * @throws Exception Если входные данные не соответствуют формату
     *
     * @author Сырцев Александр
     *
     * @since 24.01.2019 Написан метод
     * @since 25.01.2019 Добавлены проверки на разницу по времени и координатам
     * @since 22.07.2019 Добавлена проверка на деление на 0, актуализированы условия и тесты
     *
     * Тесты:
     * @see \CoordinateControllerTest::testCalculateSpeedCorrect()
     * @see \CoordinateControllerTest::testCalculateSpeedDistanceOver300ThrowsException()
     * @see \CoordinateControllerTest::testCalculateSpeedInvalidArgumentsThrowsException()
     * @see \CoordinateControllerTest::testCalculateSpeedTimeDiffOverMinuteThrowsException()
     * @see \CoordinateControllerTest::testCalculateSpeedTimeDiffZeroThrowsException()
     */
    public static function calculateSpeed($coordStart, $timeStart, $coordEnd, $timeEnd)
    {
        // Валидация входных данных
        $speed['generate_event'] = false;
        $speed['speed_value'] = -1;
        if (
            preg_match('/^-{0,1}\d{1,}.\d{1,},-{0,1}\d{1,}.\d{1,},-{0,1}\d{1,}.\d{1,}?/', $coordStart) == false
            ||
            preg_match('/^-{0,1}\d{1,}.\d{1,},-{0,1}\d{1,}.\d{1,},-{0,1}\d{1,}.\d{1,}?/', $coordEnd) == false
            ||
            preg_match('/^\d{1,4}\-\d{1,2}\-\d{1,2} \d{1,2}:\d{1,2}:\d{1,2}?/', $timeStart) == false
            ||
            preg_match('/^\d{1,4}\-\d{1,2}\-\d{1,2} \d{1,2}:\d{1,2}:\d{1,2}?/', $timeEnd) == false
        ) {
            throw new InvalidArgumentException('Входные данные не соответствуют формату');
        }

        // Избавляемся от микросекунд
        $timeStart = explode('.', $timeStart)[0];
        $timeEnd = explode('.', $timeEnd)[0];

        if (strtotime($timeEnd) < strtotime($timeStart)) {
            // пакет старый в расчет не брать
            $speed['generate_event'] = false;
        } else {
            $speed['generate_event'] = true;
        }
        // Разбиение строки на отдельные координаты
        list ($coordStartVector['x'], $coordStartVector['y'], $coordStartVector['z']) = explode(',', $coordStart);
        list ($coordEndVector['x'], $coordEndVector['y'], $coordEndVector['z']) = explode(',', $coordEnd);

        // Находим длину отрезка
        $vectorLength = round(sqrt(
            pow($coordEndVector['x'] - $coordStartVector['x'], 2) +
            pow($coordEndVector['y'] - $coordStartVector['y'], 2) +
            pow($coordEndVector['z'] - $coordStartVector['z'], 2)
        ), 2);

        // Проверка на разницу координат
        if ($vectorLength > 300) {
            throw new RuntimeException('Пройденное расстояние превышает 300 метров');
        }


        // Находим промежуток во времени между точками
        $dTime = abs(strtotime($timeEnd) - strtotime($timeStart));
        if ($dTime === 0) {
            throw new RuntimeException('Разница во времени равна 0');
        }

        // Проверка на разницу во времени
        if ($dTime > 60) {
            throw new RuntimeException('Время между точками превышает 1 минуту');
        }

        // Возвращаем скорость с точностью до 1 знака после запятой
        $speed['speed_value'] = round($vectorLength / $dTime, 1);

        return $speed;
    }


    /**
     * amicum_rSet - Метод вставки значений в кэш командами редиса.
     * Аналогичен методу set(), только ключи не преобразуются в какой-либо формат,
     * они добавляюся как есть
     * @param $key
     * @param $value
     * @param null $dependency
     * @return mixed
     */
    private function amicum_rSet($key, $value, $dependency = null)
    {
        $value = serialize([$value, $dependency]);
        $data[] = $key;
        $data[] = $value;

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'set', $data);
        }

        return $this->redis_cache->executeCommand('set', $data);
    }

    /**
     * Метод получение значения из кэша на прямую из редис
     * @param $key
     * @return bool
     */
    private function amicum_rGet($key)
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
        } catch (\Throwable $exception) {
            $status = 0;
            $errors[] = 'amicum_repRedis. Исключение:';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'amicum_repRedis. Конец метода';
        return array('Items' => $result, 'warnings' => $warnings, 'errors' => $errors, 'status' => $status);
    }

    public function calculateCoordinatesHorizon($nodes, $distance, $vectorLength, $mine_id)
    {
        $log = new LogAmicumFront("calculateCoordinatesHorizon", true);
        $result = array(
            "xyz" => "-1",
            "place_id" => "-1",
            "edge_id" => "-1"
        );
        try {
            $log->addLog("Начало метода");

            if (!$vectorLength) {
                throw new Exception("Длина вектора не верная - деление на 0. Длина: " . $vectorLength);
            }

            /** ПОЛУЧАЕМ ИЗ КЕША ДАННЫЕ ПО СЧИТЫВАТЕЛЯМ*/
            $start_node_id = (new ServiceCache())->getSensorByNetworkId($mine_id . ":" . $nodes->ReaderNumBegin);
            $log->addData($start_node_id, '$start_node_id', __LINE__);
            if ($start_node_id === false) {
                throw new Exception("Стартовый Шлюз/узел не стоит на схеме. Не удалось найти в кэше сенсор по сетевому идентификатору " . $mine_id . ":" . $nodes->ReaderNumBegin);
            }

            $end_node_id = (new ServiceCache())->getSensorByNetworkId($mine_id . ":" . $nodes->ReaderNumEnd);
            $log->addData($end_node_id, '$end_node_id', __LINE__);
            if ($end_node_id === false) {
                throw new Exception("Конечный Шлюз/узел не стоит на схеме. Не удалось найти в кэше сенсор по сетевому идентификатору " . $mine_id . ":" . $nodes->ReaderNumEnd);
            }


            /** ИЩЕМ КООРДИНАТЫ СЧИТЫВАТЕЛЕЙ*/
            $sensor_cache_controller = new SensorCacheController();

            $start_node_param = $sensor_cache_controller->getParameterValueHash($start_node_id, 83, 1);
            $log->addData($start_node_param, '$start_node_param', __LINE__);
            if ($start_node_param) {
                $xyz_start = $start_node_param['value'];
            } else {
                throw new Exception("В кэше не найдены координаты для узла $nodes->ReaderNumBegin");
            }

            $end_node_param = $sensor_cache_controller->getParameterValueHash($end_node_id, 83, 1);
            $log->addData($end_node_param, '$end_node_param', __LINE__);
            if ($end_node_param) {
                $xyz_end = $end_node_param['value'];
            } else {
                throw new Exception("В кэше не найдены координаты для узла $nodes->ReaderNumEnd");
            }

            $xyz_start = explode(",", $xyz_start);
            if (count($xyz_start) < 2) {
                throw new Exception("Стартовый узел имеет неверную координату $nodes->ReaderNumBegin");
            }

            $xyz_end = explode(",", $xyz_end);
            if (count($xyz_end) < 2) {
                throw new Exception("Конечный узел имеет неверную координату $nodes->ReaderNumEnd");
            }


            $multipale = $distance / $vectorLength;

            for ($i = 0; $i < 3; $i++) {
                $xyz[$i] = (($xyz_end[$i] - $xyz_start[$i]) * $multipale) + $xyz_start[$i];
            }
            $log->addData($xyz, '$xyz', __LINE__);

            $result['xyz'] = $xyz[0] . "," . $xyz[1] . "," . $xyz[2];
            $need_dot = array(
                'x' => $xyz[0],
                'y' => $xyz[1],
                'z' => $xyz[2],
            );

            /**
             * Забираем из кэша графы услышанных узлов связи и для каждого
             * услышанного узла вычисляем возможные точки нахождения светильника
             */
            $possible_edges = false;

            $node_graph_start = $this->getGraph($start_node_id);
            if ($node_graph_start) {
                $possible_edges = $node_graph_start[$start_node_id];
            }

            $node_graph_end = $this->getGraph($end_node_id);
            if ($node_graph_end) {
                if ($possible_edges) {
                    $possible_edges = array_merge($possible_edges, $node_graph_end[$end_node_id]);
                } else {
                    $possible_edges = $node_graph_end[$end_node_id];
                }
            }

//            $log->addData($possible_edges, '$possible_edges', __LINE__);

            if (!$possible_edges) {
                throw new Exception('У считывателей/узлов связи нет графа выработок');
            }

            /**
             * Находим из всех комбинаций нужную, в которой периметр фигуры,
             * с вершинами в данных точках, наименьший.
             * Центр данной фигуры и будет исходной точкой координат
             */
            $edge_list = $this->getEdgeListCache($mine_id);
            if ($edge_list === false) {
                throw new Exception('Списка выработок нет в кэше');
            }

            /**
             * Находим проекции точки на все эджи из путей к точке
             */
            $dot_projections = array();
            foreach ($possible_edges as $possible_edge) {
                $response = self::calculateProjectionDotOnEdge($need_dot, $possible_edge);
                $log->addLogAll($response);
                if ($response['status'] == 1) {
                    $dot_projections[$possible_edge['edge_id']] = $response['normal_need_dot'];
                }
            }

            $log->addLog("Нашли проекции точки на выработку");

            /**
             * Если проекция точки не попадает ни на одну выработку, то берем стартовый узел связи за главный
             */
            if (empty($dot_projections)) {
                $start_node_param = $sensor_cache_controller->getParameterValueHash($start_node_id, 269, 1);
                if ($start_node_param) {
                    $result['edge_id'] = $start_node_param['value'];
                } else {
                    throw new Exception("В кэше не найдена ветвь узла $nodes->ReaderNumBegin");
                }

                $start_node_param = $sensor_cache_controller->getParameterValueHash($start_node_id, 122, 1);
                if ($start_node_param) {
                    $result['place_id'] = $start_node_param['value'];
                } else {
                    throw new Exception("В кэше не найдено место узла $nodes->ReaderNumBegin");
                }
//                throw new Exception('Проекция точки не попадает ни на одну выработку');
            }

            /**
             * Вычисляем расстояние до каждой проекции и возвращаем ближайшую
             */
            $min_distance = INF;

            foreach ($dot_projections as $projection_dot) {
                $distance_to_projection = OpcController::calcDistance(
                    $need_dot['x'], $need_dot['y'], $need_dot['z'],
                    $projection_dot['x'], $projection_dot['y'], $projection_dot['z']
                );
                if ($distance_to_projection < $min_distance) {
                    $min_distance = $distance_to_projection;
                    $result['edge_id'] = $projection_dot['edge_id'];
                    $result['place_id'] = $projection_dot['place_id'];
                }
            }

            $log->addLog("Нашли точку");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog('Окончил выполнение метода');

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}