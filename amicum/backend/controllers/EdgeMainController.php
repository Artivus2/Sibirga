<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers;


use backend\controllers\cachemanagers\EdgeCacheController;
use backend\controllers\cachemanagers\LogCacheController;
use backend\controllers\const_amicum\ParamEnum;
use backend\controllers\const_amicum\ParameterTypeEnumController;
use backend\controllers\const_amicum\StatusEnumController;
use backend\controllers\const_amicum\TypicalObjectEnumController;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\HandbookCachedController;
use frontend\controllers\handbooks\HandbookPlaceController;
use frontend\controllers\positioningsystem\EdgeHistoryController;
use frontend\controllers\positioningsystem\SpecificConjunctionController;
use frontend\controllers\positioningsystem\SpecificEdgeController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Conjunction;
use frontend\models\Edge;
use frontend\models\EdgeParameterHandbookValue;
use frontend\models\EdgeStatus;
use frontend\models\EdgeType;
use frontend\models\Mine;
use frontend\models\Place;
use frontend\models\Plast;
use frontend\models\TypicalObject;
use Throwable;
use Yii;
use yii\db\Query;

class EdgeMainController
{
    // GetEdgesRelation             - Метод получения массива связанных между собой выработок
    // getEdgeMineDetail            - Возвращает информацию по edge из кеша EdgeScheme
    // edgeHasConveyor              - Проверяет, есть ли в выработке конвейер
    // GetShema                     - Метод получения схемы шахты из кеша
    // EditParametersValuesEdges    - Метод редактирования параметров выработок в кеше
    // SearchParameterValuesEdges   - Метод поиска выработок принадлежавших переданному place


    /**
     * getEdgeMineDetail - Возвращает информацию по эджу из кеша EdgeScheme.
     * Если в кеше нет данных, то выполняется запрос к БД, с последующим
     * занесением в кеш. Если в БД нет данных - возвращается false.
     *
     * Возвращает массив вида:
     * [
     *  'edge_id',
     *  'place_id',
     *  'place_title',
     *  'conjunction_start_id',
     *  'conjunction_end_id',
     *  'xStart',
     *  'yStart',
     *  'zStart',
     *  'xEnd',
     *  'yEnd',
     *  'zEnd',
     *  'place_object_id',
     *  'danger_zona',
     *  'color_edge',
     *  'color_edge_rus',
     *  'mine_id',
     *  'conveyor',
     *  'conveyor_tag',
     *  'value_ch',
     *  'value_co',
     *  'date_time'
     * ]
     *
     * @param int $mine_id идентификатор шахты
     * @param int $edge_id идентификатор выработки
     * @return mixed
     *
     * @author Сырцев А.П.
     * @since 05.06.2019
     */
    public static function getEdgeMineDetail($mine_id, $edge_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();

        $edge_info = false;

        try {
            // Поиск выработки в кеше
            $warnings[] = "getEdgeMineDetail. Поиск выработки $edge_id в кеше шахты $mine_id";
            if (!$edge_id) {
                throw new Exception('getEdgeMineDetail. edge_id не может быть пустым');
            }

            $edge_cache_controller = new EdgeCacheController();
            $edge_info_response = $edge_cache_controller->getEdgeScheme($mine_id, $edge_id);
            if (!$edge_info_response) {
                // Поиск выработки в базе
                $warnings[] = "getEdgeMineDetail. Выработка не найдена в кеше. Поиск выработки $edge_id в БД";
                $edge_info_response = $edge_cache_controller->initEdgeScheme($mine_id, $edge_id);
                if (!$edge_info_response) {
                    throw new Exception('getEdgeMineDetail. Выработка ' . $edge_id . ' не найдена в базе во вьюшке view_initEdgeScheme');
                } else {
                    $edge_info = $edge_info_response[0];
                }
            } else {
                $edge_info = $edge_info_response;
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'getEdgeMineDetail. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings, 'edge_info' => $edge_info);
    }

    /**
     * GetShema - Метод получения схемы шахты из кеша
     * @param $mine_id
     * @return array
     */
    public static function GetShema($mine_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();
        try {
            $warnings[] = "GetShema. Поиск выработок в кеше шахты $mine_id";
            $edge_cache_controller = new EdgeCacheController();
            $response = $edge_cache_controller->multiGetEdgeScheme($mine_id);
            if ($response != false) {
                $result = $response;
                $warnings[] = "Выполнил поиск вырабток в кеше EdSch:$mine_id";
            } else {
                $errors[] = "GetShema. Ошибка поиска выработок в кеше EdSch:$mine_id";
                throw new Exception("GetShema. Ошибка поиска выработок в кеше EdSch:$mine_id");
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'GetShema.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        return array('result' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * EditParametersValuesEdges - Метод редактирования параметров выработок в кеше
     * при изменении значений у place которому они принадлежат
     *
     * @param $parameter_id - id параметра который надо поменять в кеше у выработок
     * @param $parameter_type_id - id типа параметра который надо поменять в кеше у выработок
     * @param $parameter_place - id параметра по которому будут искаться выработки данного place(118 параметр)
     * @param $specific_id - id самого place
     * @param $new_value - новое значение параметра
     * @return array
     *
     *
     * @package backend\controllers
     *
     * @author fidchenkoM
     * Created date: on 03.07.2019 8:38
     * @since ver
     */
    public static function EditParametersValuesEdges($parameter_id, $parameter_type_id, $parameter_place, $specific_id, $new_value, $mine_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();
        try {
            /**
             * Блок нахождения всеx выработок переданного значения place
             */
            $response = self::SearchParameterValuesEdges($parameter_place, $specific_id);
            if ($response['status'] == 1) {
                $edge_parameter_values = $response['result'];
                $warnings[] = "EditParametersValuesEdges.Выполнил поиск параметров $parameter_id";
            } else {
                $errors[] = "EditParametersValuesEdges. Ошибка  поиска параметров $parameter_id";
                throw new Exception("EditParametersValuesEdges. Ошибка  поиска параметров $parameter_id");
            }
            /**
             * Блок в ставки в кеш и в бд нового значения параметра
             */
            foreach ($edge_parameter_values as $edge_parameter_value) {

                $edge_id = $edge_parameter_value['edge_id'];
                $response = (new EdgeCacheController())->getParameterValue($edge_id, $parameter_id, $parameter_type_id);
                if ($response != false) {
                    $edge_parameter_id = $response['edge_parameter_id'];
                    $status_id = $response['status_id'];
                    $date_time = $response['date_time'];
                    $warnings[] = "EditParametersValuesEdges.Выполнил поиск параметра $parameter_id для $edge_id в кеше";
                } else {
                    $errors[] = "EditParametersValuesEdges. Ошибка  поиска параметра $parameter_id для $edge_id в кеше";
                    throw new Exception("EditParametersValuesEdges. Ошибка поиска параметра $parameter_id для $edge_id в кеше");
                }
                EdgeBasicController::addEdgeParameterHandbookValue($edge_parameter_id, $new_value, $status_id);
                $response = (new EdgeCacheController())->setParameterValue($edge_id, $edge_parameter_id, $parameter_id, $parameter_type_id, $new_value, $status_id);
                if ($parameter_id == 162) {
                    $edge_schm = (new EdgeCacheController())->getEdgeScheme($mine_id, $edge_id);
                    if ($edge_schm != false) {
                        $edge_schm['place_title'] = $new_value;
                        $response = (new EdgeCacheController())->setEdgeScheme($mine_id, $edge_id, $edge_schm);
                    }
                }
                if ($parameter_id == 346) {
                    $response = self::moveEdgeSchemaMineInitCache($mine_id, $edge_id, $new_value);
                    if ($response['status'] != -1) {
                        $warnings[] = "EditParametersValuesEdges.Выполнил перенос кеша схемы edge_id $edge_id из шахты $mine_id в $new_value";
                    } else {
                        $errors[] = "EditParametersValuesEdges. Ошибка  переноса кеша схемы edge_id $edge_id из шахты $mine_id в $new_value";
                        $errors[] = $response['errors'];
                        throw new Exception("EditParametersValuesEdges. Ошибка перенос кеша схемы edge_id $edge_id из шахты $mine_id в $new_value");
                    }
                }
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'EditParametersValuesEdges.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        return array('result' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
    }

    public static function moveEdgeSchemaMineInitCache($mine_id_old, $edge_id, $mine_id_new)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();
        try {
            $edge_schm = (new EdgeCacheController())->getEdgeScheme($mine_id_old, $edge_id);
            if ($edge_schm) {
                $warnings[] = "moveEdgeSchemaMineInitCache. кеш схемы для edge_id =  $edge_id получен";
                $warnings = $edge_schm;
            } else {
                throw new Exception("moveEdgeSchemaMineInitCache. Главный кеш схемы выработок не инициализирован. Не смог получить кеш схемы выработки: " . $edge_id);
            }
            $edge_schm['mine_id'] = $mine_id_new;
            $response = (new EdgeCacheController())->setEdgeScheme($mine_id_new, $edge_id, $edge_schm);
            (new EdgeCacheController())->delEdgeScheme($mine_id_old, $edge_id);
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'moveEdgeSchemaMineInitCache.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        return array('status' => $status,
            'errors' => $errors, 'warnings' => $warnings);

    }

    /**
     * SearchParameterValuesEdges - Метод поиска выработок принадлежавших переданному place
     * @param $parameter_place - id параметра по которому будут искаться выработки в кеше(118 параметр)
     * @param $specific_id - id самого place
     * @return array                - массив выоаботок или ошибка
     *
     *
     * @package backend\controllers
     *
     * @see
     * @example
     *
     * @author fidchenkoM
     * Created date: on 03.07.2019 8:42
     * @since ver
     */
    public static function SearchParameterValuesEdges($parameter_place, $specific_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();
        try {
            $edges = (new EdgeCacheController)->multiGetParameterValue('*', $parameter_place, '*');
            foreach ($edges as $edge) {
                if ($edge['value'] == $specific_id) {
                    $result[] = $edge;
                }
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'SearchParameterValuesEdges.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        return array('result' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * edgeHasConveyor - Проверяет, есть ли в выработке конвейер
     * @param $mine_id - Идентификатор шахты
     * @param $edge_id - Идентификатор выработки
     * @return bool|mixed
     * @throws Exception
     */
    public static function edgeHasConveyor($mine_id, $edge_id)
    {
        $errors = array();
        $status = 1;
        $result = array();
        $warnings = array();

        $conveyor_tag = '';

        $warnings[] = 'edgeHasConveyor. Начало метода';
        try {
            /**
             * Получение из кэша/бд информации о выработке
             */
            $warnings[] = "edgeHasConveyor. Получение из кэша/бд информации о выработке $edge_id";
            $edge_info = array();
            $response = self::getEdgeMineDetail($mine_id, $edge_id);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $edge_info = $response['edge_info'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception("edgeHasConveyor. Ошибка при получении информации о выработке $edge_id");
            }

            /**
             * Проверяем, есть ли на выработке конвейер.
             * Если есть, то возвращаем его тег
             */
            if ($edge_info['conveyor'] == 1) {
                $conveyor_tag = $edge_info['conveyor_tag'];
                $warnings[] = "edgeHasConveyor. На выработке есть конвейер с тегом $conveyor_tag";
            } else {
                throw new Exception("edgeHasConveyor. На выработке $edge_id нет конвейера");
            }

            $warnings[] = 'edgeHasConveyor. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'edgeHasConveyor.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('result' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings,
            'conveyor_tag' => $conveyor_tag);
    }

    // GetEdgesRelation - получить массив связанных между собой выработок
    // по конкретному edge_id с учетом дальности расстояния, ищем все вырбаотки которые попадают в этузону
    // входные параметры:
    //      edge_id         - ключ выработки
    //      mine_id         - ключ шахтного поля
    //      length          - радиус охвата выработок от искомой
    //      xyz             - координата от которой надо начать считать
    // выходные параметры:
    //      edge_id_array   - массив ключей выработок
    // алгоритм:
    //  1. Получить схему шахты по ключу шахты
    //  2. построить объект выработка (сопряжение)
    //  3. построить объект сопряжение (выработка)
    //  4. ищем у искомой выработки сопряжения
    //  5. находим по данным сопряжениям все выработки
    //  6. удаляем из объяекта сопряжений находящиеся в них выработки
    //  7. для найденных выработк делаем проверку на выход из протяженности искомой
    //  8. перебираем пока не закончаться выработки
    public static function GetEdgesRelation($edge_id, $mine_id, $length, $xyz)
    {
        $log = new LogAmicumFront("GetEdgesRelation");

        $result = null;                                                                                                 // результирующий массив (если требуется)

        try {
            $log->addLog('Начало выполнения метода');

            /** Метод начало */
            /**
             * получаем граф выработок шахты из кеша, если его там нет, то строим с нуля
             */
            $result[$edge_id] = $edge_id;
            $shema_mine_edges = (new EdgeCacheController())->multiGetEdgeScheme($mine_id);
            if ($shema_mine_edges === false) {
                $shema_mine_edges = (new EdgeCacheController())->initEdgeScheme($mine_id);
                if ($shema_mine_edges === false) {
                    throw new Exception("Не удалось получить схему шахты ни из БД ни из кеша mine_id " . $mine_id);
                }
                $log->addLog('Получил схему шахты из БД');
            } else {
                $log->addLog('Получил схему шахты из кеша');
            }

            $log->addLog('Получил схему шахты');

            /**
             * Перепаковываем схему на нужные параметры, лишние параметры удаляем
             */
            foreach ($shema_mine_edges as $shema_mine_edge) {
                unset($shema_mine_edge['place_title']);
                unset($shema_mine_edge['place_object_id']);
                unset($shema_mine_edge['danger_zona']);
                unset($shema_mine_edge['color_edge']);
                unset($shema_mine_edge['color_edge_rus']);
                unset($shema_mine_edge['conveyor']);
                unset($shema_mine_edge['conveyor_tag']);
                unset($shema_mine_edge['value_ch']);
                unset($shema_mine_edge['value_co']);
                unset($shema_mine_edge['date_time']);
                unset($shema_mine_edge['mine_id']);

                //длина ребра в метрах
                $shema_mine_edge['edge_lenght'] = OpcController::calcDistance(
                    $shema_mine_edge['xStart'], $shema_mine_edge['yStart'], $shema_mine_edge['zStart'],
                    $shema_mine_edge['xEnd'], $shema_mine_edge['yEnd'], $shema_mine_edge['zEnd']
                );
                $shema_mine_edge_repac[$shema_mine_edge['edge_id']] = $shema_mine_edge;
                /**
                 * перепаковываем сопряжения в новый массив - получаем связи каждого сопряжения и делаем справочник сопряжений
                 */
                $shema_mine_conj_repac[$shema_mine_edge['conjunction_start_id']][$shema_mine_edge['conjunction_end_id']]['conjunction_id'] = $shema_mine_edge['conjunction_end_id'];
                $shema_mine_conj_repac[$shema_mine_edge['conjunction_start_id']][$shema_mine_edge['conjunction_end_id']]['edge_id'] = $shema_mine_edge['edge_id'];
                $conjunction_id = $shema_mine_edge['conjunction_start_id'];
                $conjunction_spr[$conjunction_id]['conjunction_id'] = $conjunction_id;
                $conjunction_spr[$conjunction_id]['x'] = $shema_mine_edge['xStart'];
                $conjunction_spr[$conjunction_id]['y'] = $shema_mine_edge['yStart'];
                $conjunction_spr[$conjunction_id]['z'] = $shema_mine_edge['zStart'];

                $shema_mine_conj_repac[$shema_mine_edge['conjunction_end_id']][$shema_mine_edge['conjunction_start_id']]['conjunction_id'] = $shema_mine_edge['conjunction_start_id'];
                $shema_mine_conj_repac[$shema_mine_edge['conjunction_end_id']][$shema_mine_edge['conjunction_start_id']]['edge_id'] = $shema_mine_edge['edge_id'];
                $conjunction_id = $shema_mine_edge['conjunction_end_id'];
                $conjunction_spr[$conjunction_id]['conjunction_id'] = $conjunction_id;
                $conjunction_spr[$conjunction_id]['x'] = $shema_mine_edge['xEnd'];
                $conjunction_spr[$conjunction_id]['y'] = $shema_mine_edge['yEnd'];
                $conjunction_spr[$conjunction_id]['z'] = $shema_mine_edge['zEnd'];
            }
            unset($shema_mine_edges);
            unset($shema_mine_edge);

            $log->addLog('подготовил данные для поиска' . $xyz);

            // делаем расчет до конца и до начал выработки с целью проверки нужно ли искать дальше или нет
            // разбираем текущую координату $xyz
            // считаем расстояние от текущей координаты до начала
            // считаем расстояние от текущей координаты до конца

            if (isset($shema_mine_edge_repac[$edge_id])) {
                list ($xyzCurrent['x'], $xyzCurrent['y'], $xyzCurrent['z']) = explode(',', $xyz);
                $lenght_to_start = OpcController::calcDistance(
                    $shema_mine_edge_repac[$edge_id]['xStart'], $shema_mine_edge_repac[$edge_id]['yStart'], $shema_mine_edge_repac[$edge_id]['zStart'],
                    $xyzCurrent['x'], $xyzCurrent['y'], $xyzCurrent['z']);

                $lenght_to_end = OpcController::calcDistance(
                    $xyzCurrent['x'], $xyzCurrent['y'], $xyzCurrent['z'],
                    $shema_mine_edge_repac[$edge_id]['xEnd'], $shema_mine_edge_repac[$edge_id]['yEnd'], $shema_mine_edge_repac[$edge_id]['zEnd']);


                // если от места, где случилось событие до начала/конца выработки больше расстояние от запрашиваемого, то поиск не делаем
                // блок поиска эджей, которые находятся на расстояние от искомого до $length метров
                if ($lenght_to_start < $length) {
                    $response = OpcController::findAllConjunction20M($shema_mine_edge_repac[$edge_id]['conjunction_start_id'], $length, $shema_mine_conj_repac, $conjunction_spr, 0);
                    foreach ($response['edges'] as $edge_item_id) {
                        $result[$edge_item_id] = $edge_item_id;
                    }
                }
                if ($lenght_to_end < $length) {
                    $response = OpcController::findAllConjunction20M($shema_mine_edge_repac[$edge_id]['conjunction_end_id'], $length, $shema_mine_conj_repac, $conjunction_spr, 0);
                    foreach ($response['edges'] as $edge_item_id) {
                        $result[$edge_item_id] = $edge_item_id;
                    }
                }
            } else {
                $log->addLog('Сенсор установлен на несуществующей выработке!!!!!!!!!!!!!! Выработка:' . $edge_id);
            }

            $log->addLog('Закончил поиск смежных выработок');

            unset($shema_mine_edge_repac, $shema_mine_conj_repac, $conjunction_spr, $sensor_CH4, $graph_for_search, $distancesSensor, $graph_edge_by_sensor_result, $graph_without_sensor_edge);
            /** Метод окончание */

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog('Окончание выполнения метода');

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * AddChangeEdge - Метод добавления/редактирования выработки
     * @param $edge_changes_id - ключ истории, в которую применяются изменения
     * @param $edgeEdit - текущая создаваемая/редактируемая выработка
     *          version_scheme                  - версия схемы шахты
     *          edge_id                         - ключ выработки
     *          place_id                        - ключ места
     *          place_title                     - название места
     *          conjunction_start_id            - ключ сопряжения начала
     *          conjunction_end_id              - ключ сопряжения конца
     *          xStart                          - X начала
     *          yStart                          - Y начала
     *          zStart                          - Z начала
     *          xEnd                            - X конца
     *          yEnd                            - Y конца
     *          zEnd                            - Z конца
     *          place_object_id                 - ключ типа места
     *          place_object_title              - наименование типа места
     *          plast_id                        - ключ пласта
     *          plast_title                     - название пласта
     *          type_place_title                - название типа места
     *          edge_type_id                    - ключ типа выработки
     *          edge_type_title                 - название типа выработки
     *          lenght                          - длина
     *          weight                          - вес
     *          height                          - высота
     *          width                           - ширина
     *          angle                           - угол наклона
     *          section                         - сечение
     *          danger_zona                     - флаг запретной зоны
     *          color_edge                      - цвет выработки ключ
     *          color_edge_rus                  - цвет выработки название
     *          conveyor                        - наличие конвейера
     *          mine_id                         - ключ шахтного поля
     *          mine_title                      - название шахтного поля
     *          conveyor_tag                    - название тега конвейера
     *          set_point_ch                    - уставка CH4
     *          set_point_co                    - уставка СО
     *          type_place_id                   - тип места
     *          color_hex                       - цвет выработки
     *          shape_edge_id                   - ключ формы выработки
     *          shape_edge_title                - наименование формы выработки
     *          type_shield_id                  - ключ типа крепи
     *          type_shield_title               - наименование типа крепи
     *          company_department_id           - ключ ответственного подразделения
     *          company_department_title        - наименование ответственного подразделения
     *          company_department_date         - дата закрепления ответственного подразделения
     *          company_department_state        - флаг открепления ответственного подразделения
     *
     * @return array|null[]
     */
    public static function AddChangeEdge($edgeEdit, $edge_changes_id = null)
    {

        $log = new LogAmicumFront("AddChangeEdge");

        try {
            $log->addLog("Начало выполнения метода");

            /** ПРОВЕРКА НА СУЩЕСТВОВАНИЕ ШАХТЫ */
            $edgeEdit->mine_id = ($edgeEdit->mine_id and $edgeEdit->mine_id > 0) ? $edgeEdit->mine_id : Yii::$app->session['userMineId'];
            $mine = Mine::findOne(['id' => $edgeEdit->mine_id]);
            if (!$mine) {
                throw new Exception("Ключ шахтного поля не найден в БД");
            }
            $edgeEdit->mine_title = $mine->title;
            $mine_id = $mine->id;

            /** ПРОВЕРКА НА СУЩЕСТВОВАНИЕ ПЛАСТА */
            $plast_id = $edgeEdit->plast_id;
            $plast = Plast::findOne(['id' => $plast_id]);
            if (!$plast) {
                throw new Exception("Ключ пласта не найден в БД");
            }
            $edgeEdit->plast_title = $plast->title;

            /** ПРОВЕРКА НА СУЩЕСТВОВАНИЕ ТИПА МЕСТА */
            $place_object_id = ($edgeEdit->place_object_id and $edgeEdit->place_object_id > 0) ? $edgeEdit->place_object_id : TypicalObjectEnumController::PLACE;
            $place_object = TypicalObject::findOne(['id' => $place_object_id]);
            if (!$place_object) {
                throw new Exception("Ключ типа места не найден в БД");
            }
            $edgeEdit->place_object_id = $place_object->id;
            $edgeEdit->place_object_title = $place_object->title;
            $edgeEdit->type_place_id = $place_object->id;
            $edgeEdit->type_place_title = $place_object->title;

            /** ПРОВЕРКА НА СУЩЕСТВОВАНИЕ МЕСТА */
            $place_id = $edgeEdit->place_id;
            $place_title = $edgeEdit->place_title;
            if (!$place_title) {
                throw new Exception("Название выработки не задано");
            }

            $response = HandbookPlaceController::SaveNewPlace((object)array(
                'place_id' => $place_id,
                'place_title' => $place_title,
                'plast_id' => $plast_id,
                'mine_id' => $mine_id,
                'object_id' => $place_object_id
            ));
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения места');
            }
            $place = $response['place'];

            $place_id = $place->id;
            $edgeEdit->place_id = $place->id;
            $edgeEdit->place_title = $place->title;

            /** ПРОВЕРКА НА СУЩЕСТВОВАНИЕ ТИПА ВЫРАБОТКИ */
            $edge_type_id = $edgeEdit->edge_type_id;
            $edge_type = EdgeType::findOne(['id' => $edge_type_id]);
            if (!$edge_type) {
                throw new Exception("Ключ типа выработки не найден в БД");
            }
            $edgeEdit->edge_type_id = $edge_type->id;
            $edgeEdit->edge_type_title = $edge_type->title;

            /** ПРОВЕРКА НА СУЩЕСТВОВАНИЕ СОПРЯЖЕНИЯ СТАРТОВОГО */
            $conjunction_start_id = $edgeEdit->conjunction_start_id;
            $conjunction_start = Conjunction::find()
                ->where(['id' => $conjunction_start_id])
                ->orWhere(['x' => $edgeEdit->xStart, 'y' => $edgeEdit->yStart, 'z' => $edgeEdit->zStart, 'mine_id' => $mine_id,])
                ->one();
            if (!$conjunction_start) {
                $log->addLog("Сопряжения стартового не существовало, создаю новое сопряжение");
                $response = SpecificConjunctionController::AddConjunction($mine_id, $edgeEdit->xStart, $edgeEdit->yStart, $edgeEdit->zStart);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка сохранения нового сопряжения');
                }
                $conjunction_start_id = $response['conjunction_id'];
                $conjunction_start = $response['conjunction'];
            } else {
                $conjunction_start->x = (float)str_replace(',', '.', (string)$edgeEdit->xStart);
                $conjunction_start->y = (float)str_replace(',', '.', (string)$edgeEdit->yStart);
                $conjunction_start->z = (float)str_replace(',', '.', (string)$edgeEdit->zStart);
                $conjunction_start->mine_id = $mine_id;
                if (!$conjunction_start->save()) {
                    $log->addData($conjunction_start->errors, '$conjunction_start->errors', __LINE__);
                    throw new Exception("Ошибка сохранения модели Conjunction");
                }
                $conjunction_start->refresh();
                $conjunction_start_id = $conjunction_start->id;
            }
            $edgeEdit->conjunction_start_id = $conjunction_start_id;
            $edgeEdit->xStart = $conjunction_start->x;
            $edgeEdit->yStart = $conjunction_start->y;
            $edgeEdit->zStart = $conjunction_start->z;

            /** ПРОВЕРКА НА СУЩЕСТВОВАНИЕ СОПРЯЖЕНИЯ КОНЕЧНОГО */
            $conjunction_end_id = $edgeEdit->conjunction_end_id;
            $conjunction_end = Conjunction::find()
                ->where(['id' => $conjunction_end_id])
                ->orWhere(['x' => $edgeEdit->xEnd, 'y' => $edgeEdit->yEnd, 'z' => $edgeEdit->zEnd, 'mine_id' => $mine_id,])
                ->one();
            if (!$conjunction_end) {
                $log->addLog("Сопряжения стартового не существовало, создаю новое сопряжение");
                $response = SpecificConjunctionController::AddConjunction($mine_id, $edgeEdit->xEnd, $edgeEdit->yEnd, $edgeEdit->zEnd);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка сохранения нового сопряжения');
                }
                $conjunction_end_id = $response['conjunction_id'];
                $conjunction_end = $response['conjunction'];
            } else {
                $conjunction_end->x = (float)str_replace(',', '.', (string)$edgeEdit->xEnd);
                $conjunction_end->y = (float)str_replace(',', '.', (string)$edgeEdit->yEnd);
                $conjunction_end->z = (float)str_replace(',', '.', (string)$edgeEdit->zEnd);
                $conjunction_end->mine_id = $mine_id;
                if (!$conjunction_end->save()) {
                    $log->addData($conjunction_end->errors, '$conjunction_end->errors', __LINE__);
                    throw new Exception("Ошибка сохранения модели Conjunction");
                }
                $conjunction_end->refresh();
                $conjunction_end_id = $conjunction_end->id;
            }
            $edgeEdit->conjunction_end_id = $conjunction_end_id;
            $edgeEdit->xEnd = $conjunction_end->x;
            $edgeEdit->yEnd = $conjunction_end->y;
            $edgeEdit->zEnd = $conjunction_end->z;

            /** СОХРАНЕНИЕ ВЫРАБОТКИ */
            $edge_id = $edgeEdit->edge_id;
            $section = $edgeEdit->section;
            $height = $edgeEdit->height;
            $width = $edgeEdit->width;
            $date_time_now = Assistant::GetDateTimeNow();
            $edge = Edge::find()
                ->where(['id' => $edge_id])
                ->orWhere(['conjunction_start_id' => $conjunction_start_id, 'conjunction_end_id' => $conjunction_end_id])
                ->orWhere(['conjunction_start_id' => $conjunction_end_id, 'conjunction_end_id' => $conjunction_start_id])
                ->one();
            if (!$edge) {
                $log->addLog("Выработки не существовало, создаю новую выработку");
                $response = SpecificEdgeController::addEdge(
                    $place_id, $edge_type_id, $conjunction_start_id, $conjunction_end_id, null, null,
                    $place_title, $mine_id, $plast_id, $section, $height, $width, $date_time_now,
                    $edgeEdit->type_shield_id, $edgeEdit->angle, $edgeEdit->shape_edge_id, $edgeEdit->color_hex
                );
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка сохранения новой выработки');
                }
                $edge_id = $response['edge_id'];
            } else {
                $edge->conjunction_start_id = $conjunction_start_id;
                $edge->conjunction_end_id = $conjunction_end_id;
                $edge->place_id = $place_id;
                $edge->edge_type_id = $edge_type_id;
                if (!$edge->save()) {
                    $log->addData($edge->errors, '$edge->errors', __LINE__);
                    throw new Exception("Ошибка сохранения модели Edge");
                }
                $edge->refresh();
                $edge_id = $edge->id;
            }
            $edgeEdit->edge_id = $edge_id;

            /** СОХРАНЕНИЕ ПАРАМЕТРОВ ВЕТВИ И ЕЕ ЗНАЧЕНИЙ */
            $edge_param_to_cache = null;

            $edge_param_hand = null;
            $edge_param_hand_values = EdgeBasicController::getEdgeParameterHandbookValue($edge_id);
            foreach ($edge_param_hand_values as $edge_param_hand_value) {
                $edge_param_hand[$edge_param_hand_value['parameter_id']] = $edge_param_hand_value;
            }

            if (property_exists($edgeEdit, "weight")) {
                $weight = $edgeEdit->weight;
            } else {
                $weight = $edgeEdit->lenght;
            }

            $params_to_save = array(
                ['name' => 'TITLE', 'param_id' => ParamEnum::TITLE, 'param_type_id' => ParameterTypeEnumController::REFERENCE, 'param_value' => $place_title, 'status_id' => StatusEnumController::ACTUAL, 'date_time' => $date_time_now,],
                ['name' => 'EDGE_TYPE_ID', 'param_id' => ParamEnum::EDGE_TYPE_ID, 'param_type_id' => ParameterTypeEnumController::REFERENCE, 'param_value' => $edge_type_id, 'status_id' => StatusEnumController::ACTUAL, 'date_time' => $date_time_now,],
                ['name' => 'PLAST_ID', 'param_id' => ParamEnum::PLAST_ID, 'param_type_id' => ParameterTypeEnumController::REFERENCE, 'param_value' => $plast_id, 'status_id' => StatusEnumController::ACTUAL, 'date_time' => $date_time_now,],
                ['name' => 'MINE_ID', 'param_id' => ParamEnum::MINE_ID, 'param_type_id' => ParameterTypeEnumController::REFERENCE, 'param_value' => $mine_id, 'status_id' => StatusEnumController::ACTUAL, 'date_time' => $date_time_now,],
                ['name' => 'SECTION', 'param_id' => ParamEnum::SECTION, 'param_type_id' => ParameterTypeEnumController::REFERENCE, 'param_value' => $edgeEdit->section, 'status_id' => StatusEnumController::ACTUAL, 'date_time' => $date_time_now,],
                ['name' => 'LEVEL_CH4', 'param_id' => ParamEnum::LEVEL_CH4, 'param_type_id' => ParameterTypeEnumController::REFERENCE, 'param_value' => $edgeEdit->set_point_ch, 'status_id' => StatusEnumController::ACTUAL, 'date_time' => $date_time_now,],
                ['name' => 'LEVEL_CO', 'param_id' => ParamEnum::LEVEL_CO, 'param_type_id' => ParameterTypeEnumController::REFERENCE, 'param_value' => $edgeEdit->set_point_co, 'status_id' => StatusEnumController::ACTUAL, 'date_time' => $date_time_now,],
                ['name' => 'HEIGHT', 'param_id' => ParamEnum::HEIGHT, 'param_type_id' => ParameterTypeEnumController::REFERENCE, 'param_value' => $edgeEdit->height, 'status_id' => StatusEnumController::ACTUAL, 'date_time' => $date_time_now,],
                ['name' => 'WIDTH', 'param_id' => ParamEnum::WIDTH, 'param_type_id' => ParameterTypeEnumController::REFERENCE, 'param_value' => $edgeEdit->width, 'status_id' => StatusEnumController::ACTUAL, 'date_time' => $date_time_now,],
                ['name' => 'LENGTH', 'param_id' => ParamEnum::LENGTH, 'param_type_id' => ParameterTypeEnumController::REFERENCE, 'param_value' => $edgeEdit->lenght, 'status_id' => StatusEnumController::ACTUAL, 'date_time' => $date_time_now,],
                ['name' => 'WEIGHT', 'param_id' => ParamEnum::WEIGHT, 'param_type_id' => ParameterTypeEnumController::REFERENCE, 'param_value' => $weight, 'status_id' => StatusEnumController::ACTUAL, 'date_time' => $date_time_now,],
                ['name' => 'TEXTURE', 'param_id' => ParamEnum::TEXTURE, 'param_type_id' => ParameterTypeEnumController::REFERENCE, 'param_value' => $edgeEdit->color_edge, 'status_id' => StatusEnumController::ACTUAL, 'date_time' => $date_time_now,],
                ['name' => 'DANGER_ZONA', 'param_id' => ParamEnum::DANGER_ZONA, 'param_type_id' => ParameterTypeEnumController::REFERENCE, 'param_value' => $edgeEdit->danger_zona, 'status_id' => StatusEnumController::ACTUAL, 'date_time' => $date_time_now,],
                ['name' => 'CONVEYOR', 'param_id' => ParamEnum::CONVEYOR, 'param_type_id' => ParameterTypeEnumController::REFERENCE, 'param_value' => $edgeEdit->conveyor, 'status_id' => StatusEnumController::ACTUAL, 'date_time' => $date_time_now,],
                ['name' => 'CONVEYOR_TAG', 'param_id' => ParamEnum::CONVEYOR_TAG, 'param_type_id' => ParameterTypeEnumController::REFERENCE, 'param_value' => $edgeEdit->conveyor_tag, 'status_id' => StatusEnumController::ACTUAL, 'date_time' => $date_time_now,],
                ['name' => 'STATE', 'param_id' => ParamEnum::STATE, 'param_type_id' => ParameterTypeEnumController::REFERENCE, 'param_value' => StatusEnumController::ACTUAL, 'status_id' => StatusEnumController::ACTUAL, 'date_time' => $date_time_now,],
            );

            foreach ($params_to_save as $param_to_save) {
                if (
                    $edge_param_hand and
                    (
                        (isset($edge_param_hand[$param_to_save['param_id']]) and $edge_param_hand[$param_to_save['param_id']]['value'] != $param_to_save['param_value']) or
                        !isset($edge_param_hand[$param_to_save['param_id']])
                    )
                ) {
                    $log->addLog("Параметр изменился: " . $param_to_save['param_id']);
                    $param_to_save['param_value'] = (!$param_to_save['param_value'] or $param_to_save['param_value'] == "") ? -1 : $param_to_save['param_value'];
                    $response = EdgeBasicController::addEdgeParameterWithHandbookValue($edge_id, $param_to_save['param_id'], $param_to_save['param_type_id'], $param_to_save['param_value'], $param_to_save['status_id'], $param_to_save['date_time']);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception('Ошибка сохранения параметра ' . $param_to_save['name']);
                    }
                    $edge_param_to_cache[] = $response['edge_param_to_cache'];
                }
            }

            if ($edge_param_hand and
                (
                    isset($edge_param_hand[ParamEnum::COMPANY_ID]) and $edge_param_hand[ParamEnum::COMPANY_ID]['value'] != $edgeEdit->company_department_id or
                    !isset($edge_param_hand[ParamEnum::COMPANY_ID]) or
                    $edgeEdit->company_department_state
                )
            ) {
                $log->addLog("Параметр изменился: " . ParamEnum::COMPANY_ID);

                if (isset($edge_param_hand[ParamEnum::COMPANY_ID])) {
                    EdgeParameterHandbookValue::deleteAll(['edge_parameter_id' => $edge_param_hand[ParamEnum::COMPANY_ID]['edge_parameter_id']]);
                    if ($edgeEdit->company_department_state) {
                        $edgeEdit->company_department_state = 1;
                        $edgeEdit->company_department_id = -1;
                        $edgeEdit->company_department_date = "";
                        $edgeEdit->company_department_title = "";
                    }
                }

                if (!$edgeEdit->company_department_state and $edgeEdit->company_department_date and $edgeEdit->company_department_date != -1) {
                    $response = EdgeBasicController::addEdgeParameterWithHandbookValue($edge_id, ParamEnum::COMPANY_ID, ParameterTypeEnumController::REFERENCE, $edgeEdit->company_department_id, StatusEnumController::ACTUAL, $edgeEdit->company_department_date);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception('Ошибка сохранения параметра COMPANY_ID');
                    }
                    $edge_param_to_cache[] = $response['edge_param_to_cache'];
                }
            }

            /** СОХРАНЕНИЕ ИСТОРИИ ИЗМЕНЕНИЯ ВЫРАБОТКИ */
            $edge_history[] = $edge_id;
            if (!$edge_changes_id) {
                $response = EdgeHistoryController::AddEdgeChange($edge_history);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка сохранения истории изменения выработок');
                }
                $edge_changes_id = $response['edge_changes_id'];
            } else {
                $response = EdgeHistoryController::AddEdgeToHistoryChange($edge_changes_id, $edge_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка сохранения истории изменения выработок');
                }
            }

            /** ОБНОВЛЕНИЯ КЕША СХЕМЫ ШАХТЫ */
            $edge_cache_controller = new EdgeCacheController();
            $edge_schema = $edge_cache_controller->initEdgeScheme($mine_id, $edge_id);
            if (!$edge_schema) {
                throw new Exception('Ошибка инициализации выработки в кеше схема шахты');
            }

            /** ОБНОВЛЕНИЕ КЕША ВЫРАБОТОК ШАХТЫ */
            $edge_mine = $edge_cache_controller->initEdgeMine($mine_id, $edge_id);
            if (!$edge_mine) {
                throw new Exception('Ошибка инициализации выработки в главном кеше выработок');
            }

            /** ОБНОВЛЕНИЕ ПАРАМЕТРОВ ВЫРАБОТКИ В КЕШЕ */
            if ($edge_param_to_cache) {
                $response = $edge_cache_controller->multiSetEdgeParameterValue($edge_param_to_cache);
                $log->addLogAll($response);
                if ($response['status'] == 0) {
                    throw new Exception('Ошибка сохранения параметра edge в кеш Массовая вставка');
                }
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $edgeEdit, 'edge_changes_id' => $edge_changes_id], $log->getLogAll());
    }


    /**
     * DeleteEdge - метод удаления выработки из БД и кеш
     * если цод, то удаляется только из БД
     */
    public static function DeleteEdge($edge_id, $mine_id)
    {
        $log = new LogAmicumFront("DeleteEdge");
        $result = array();

        try {
            $log->addLog("Начал выполнять метод");

            /**
             * Блок проверки наличия у edge статуса актуальности параметр 164, если нет то создать
             */

            $edge = Edge::findOne(['id' => $edge_id]);
            if (!$edge) {
                throw new Exception("Выработки $edge_id не существует. Удаление не возможно");
            }

            $edgeParameterStatus = (new Query)
                ->select('id')
                ->from('edge_parameter')
                ->where('edge_id = ' . $edge_id . ' and parameter_id = 164')
                ->one();
            if ($edgeParameterStatus) {
                $edge_parameter_id = $edgeParameterStatus['id'];
            } else {
                $response = EdgeBasicController::addEdgeParameterWithHandbookValue($edge_id, ParamEnum::STATE, ParameterTypeEnumController::REFERENCE, 1, 1, Assistant::GetDateTimeNow());
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception("Не смог создать значение параметра 164(статуса) выработки $edge_id. ");
                }
                $edge_parameter_id = $response['edge_parameter_id'];
            }

            /**
             * Блок сохранения статуса выработки в БД
             */
            $response = EdgeBasicController::saveEdgeStatus($edge_id, 19);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Не смог сохранить статус выработки');
            }

            /**
             * Блок сохранения значения статуса в справочные значения
             */

            $edge_hanbook_value = new EdgeParameterHandbookValue();
            $edge_hanbook_value->edge_parameter_id = $edge_parameter_id;
            $edge_hanbook_value->status_id = 1;
            $edge_hanbook_value->date_time = Assistant::GetDateTimeNow();
            $edge_hanbook_value->value = '19';
            if (!$edge_hanbook_value->save()) {
                $log->addData($edge_hanbook_value->errors, '$edge_hanbook_value->errors', __LINE__);
                throw new Exception("Ошибка сохранения статуса выработки в справочные параметры выработки $edge_id.  модель EdgeParameterHandbookValue");
            }

            /********************* УДАЛЕНИЕ ВЫРАБОТКИ ИЗ КЭША СО ВСЕМИ ЕЁ  ПАРАМЕТРАМИ И ЗНАЧЕНИЯМИ *******************/
            $edge_mine_id = $mine_id;
            if (!COD) {
                $edge_cache_controller = new EdgeCacheController();

                $flag_done = $edge_cache_controller->delEdgeMine($edge_mine_id, $edge_id);
                if (!true) {                                                                                      // убрал проверку Якимов - причина не верно построенная изначально схема шахты. для выработок с отсутствующим статусом не строиться данный кеш
                    throw new Exception('Ошибка удаления выработки из главного кеша');
                }

                $flag_done = $edge_cache_controller->delEdgeScheme($edge_mine_id, $edge_id);
                if (!$flag_done) {
                    throw new Exception('Ошибка удаления выработки из кеша схемы шахты');
                }

                $flag_done = $edge_cache_controller->delParameterValue($edge_id);
                if (!$flag_done) {
                    throw new Exception('Ошибка удаления параметров выработки из кеша схемы шахты. Не было параметров в кеше');
                }
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
            $data_to_log = array_merge(['Items' => $result], $log->getLogAll());
            LogCacheController::setEdgeLogValue('DeleteEdge', $data_to_log, 1);
        }

        $log->addLog("Окончил выполнять метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}