<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers;
//ob_start();

use backend\controllers\cachemanagers\EdgeCacheController;
use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\const_amicum\DepartmentTypeEnum;
use backend\controllers\const_amicum\ParamEnum;
use backend\controllers\const_amicum\ShapeEdgeEnumController;
use backend\controllers\const_amicum\TypeShieldEnumController;
use backend\controllers\const_amicum\TypicalObjectEnumController;
use DOMDocument;
use Exception;
use frontend\controllers\positioningsystem\EdgeHistoryController;
use frontend\controllers\positioningsystem\SpecificEdgeController;
use frontend\controllers\positioningsystem\SpecificPlaceController;
use frontend\controllers\service\Excel;
use frontend\controllers\SuperTestController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Company;
use frontend\models\CompanyDepartment;
use frontend\models\Conjunction;
use frontend\models\Edge;
use frontend\models\EdgeFunction;
use frontend\models\EdgeParameter;
use frontend\models\EdgeParameterHandbookValue;
use frontend\models\EdgeType;
use frontend\models\Main;
use frontend\models\Mine;
use frontend\models\Place;
use frontend\models\PlaceFunction;
use frontend\models\PlaceParameter;
use frontend\models\PlaceParameterHandbookValue;
use frontend\models\Plast;
use frontend\models\Position;
use frontend\models\ShiftDepartment;
use frontend\models\ShiftMine;
use frontend\models\TypeObjectFunction;
use frontend\models\TypeObjectParameter;
use frontend\models\TypeObjectParameterHandbookValue;
use frontend\models\WorkerFunction;
use frontend\models\WorkerParameter;
use frontend\models\WorkerParameterSensor;
use Throwable;
use Yii;
use yii\db\Query;
use yii\helpers\FileHelper;
use yii\web\Controller;
use yii\web\Response;

class ArrowController extends Controller
{

    public function actionGetArrows()
    {
        $files = FileHelper::findFiles('../../');
        $models = array();
        foreach ($files as $file) {
            $model = str_replace('.php', "", str_replace('..\\models\\', "", $file));
            $models[] = $model;
        }
        return $this->render('get-arrows', ['files' => $models]);
    }

    public function actionIndex()
    {
        $mines = ['Воргашорская'];
        $mine = Mine::findOne(['title' => $mines[0]]);
        $nodes = $this->getNodes($mine->id, 20);
        $nodeIds = array();
        foreach ($nodes as $id => $node) {
            $nodeIds[] = $id;
        }
        $arrows = $this->getArrows($mine->id, $nodeIds);
        $dom = new DOMDocument();
        $root = $dom->createElement('arrows');
        $dom->appendChild($root);
        foreach ($arrows as $arrow) {
            $element = $dom->createElement('arrow');
            $element->setAttribute('id', $arrow['id']);
            $startNode = $dom->createElement('startNode');
            $startNode->setAttribute('id', $arrow['nodeStart']);
            $startNode->setAttribute('x', $nodes[$arrow['nodeStart']]['x']);
            $startNode->setAttribute('y', $nodes[$arrow['nodeStart']]['y']);
            $startNode->setAttribute('z', $nodes[$arrow['nodeStart']]['z']);
            $element->appendChild($startNode);
            $endNode = $dom->createElement('endNode');
            $endNode->setAttribute('id', $arrow['nodeEnd']);
            $endNode->setAttribute('x', $nodes[$arrow['nodeEnd']]['x']);
            $endNode->setAttribute('y', $nodes[$arrow['nodeEnd']]['y']);
            $endNode->setAttribute('z', $nodes[$arrow['nodeEnd']]['z']);
            $element->appendChild($endNode);
            Edge::find($arrow['id']);

            $root->appendChild($element);
        }
        $dom->save('xml/tmp/arrows.xml');

        return $this->render('index', [
            'nodes' => $nodes,
            'arrows' => $arrows,
            'file' => 'xml/tmp/arrows.xml'
        ]);
    }

    public function actionDownloadFile()
    {
        $get = Yii::$app->request->get();
        return Yii::$app->response->sendFile($get['title']);
        //return $this->render('download-file');
    }

    public function getNodes($mineId, $count = 0)
    {
        $model = $count ?
            Conjunction::find()->where(['between', 'id', $mineId * 1000000, ($mineId + 1) * 1000000])
                ->orderBy('x')->limit($count)->all() :
            Conjunction::find()->where(['between', 'id', $mineId * 1000000, ($mineId + 1) * 1000000])->all();
        $nodes = array();
//        var_dump($model);
        foreach ($model as $node) {
            $nodes[$node->id]['x'] = $node->x;
            $nodes[$node->id]['y'] = $node->y;
            $nodes[$node->id]['z'] = $node->z;
        }
        return $nodes;
    }

    public function getArrows($mineId, $nodeIds = [])
    {
        $model = $nodeIds ?
            Edge::find()->where(['in', 'conjunction_start_id', $nodeIds])
                ->andWhere(['in', 'conjunction_end_id', $nodeIds])->all() :
            Edge::find()->where(['between', 'id', $mineId * 10000, ($mineId + 1) * 10000])->all();
        $arrows = array();
        foreach ($model as $arrow) {
            $tmp_arrow['id'] = $arrow->id;
            $tmp_arrow['nodeStart'] = $arrow->conjunction_start_id;
            $tmp_arrow['nodeEnd'] = $arrow->conjunction_end_id;
            $tmp_arrow['title'] = $arrow->place->title;
            $arrows[] = $tmp_arrow;
        }
        return $arrows;
    }


    public function saveNodes2($nodes, $mine_id)
    {
        $log = new LogAmicumFront("saveNodes2");
        set_time_limit(0);
        $mas_cache = array();
        $list_nodes = array();
        $node_hand = [];

        try {
            $log->addLog("Начал выполнение метода");

            $index = 0;

            $node_in_dbs = Conjunction::findAll(['mine_id' => $mine_id]);
            foreach ($node_in_dbs as $node) {
                $node_hand[$node['x'] . '_' . $node['y'] . '_' . $node['z']] = $node;
            }
            $index = 0;

            foreach ($nodes as $node) {
                $index++;
                if (!$node['Узел']) {
                    $log->addData($index, '$index', __LINE__);
                    $log->addData($node, '$node', __LINE__);
                    continue;
                }

                if (!$node['X']) {
                    $node['X'] = 0;
                }
                if (!$node['Y']) {
                    $node['Y'] = 0;
                }
                if (!$node['Z']) {
                    $node['Z'] = 0;
                }

                if (isset($node_hand[$node['X'] . '_' . $node['Z'] . '_' . $node['Y']])) {
                    $node_db = $node_hand[$node['X'] . '_' . $node['Z'] . '_' . $node['Y']];
                    $key = $node_db['ventilation_id'];
                    $mas_cache[$key] = $node_db['id'];
                } else {
                    $main_id = $this->AddMain('conjunction');
                    if ($main_id == -1) {
                        throw new Exception("Ошибка создания главного ключа сопряжения");
                    }

                    $list_nodes[$index] = array(
                        'ventilation_id' => $node['Узел'],
                        'x' => $node['X'],
                        'y' => $node['Z'],
                        'z' => $node['Y'],
                        'main_id' => $main_id,
                        'title' => "Поворот $main_id",
                        'object_id' => TypicalObjectEnumController::CONJUNCTION,
                        'mine_id' => $mine_id,
                    );

                    $key = $node['Узел'];
                    $mas_cache[$key] = $main_id;
                    $index++;
                }

            }
            $log->addLog("Перебрал все узлы");

            Yii::$app->db->createCommand()->batchInsert('conjunction', ['ventilation_id', 'x', 'y', 'z', 'id', 'title', 'object_id', 'mine_id'], $list_nodes)->execute();

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Закончил выполнение метода");

        return array_merge(['Items' => $mas_cache], $log->getLogAll());
    }

    public function AddMain($table_address)
    {
        $main = new Main();
        $main->db_address = "amicum2";
        $main->table_address = $table_address;
        if ($main->save()) {
            return $main->id;
        } else {
            return -1;
        }
    }

    public function UpdateArrows2($arrows, $mineId, $mas_cache)
    {
//        ini_set('max_execution_time', -1);
//        ini_set('memory_limit', "10500M");

        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("UpdateArrows2");

        $placeList = array();
        $edgeList = array();

        try {
            $log->addLog("Начало выполнения метода");


            $EdgeCacheController = new EdgeCacheController();

            $list_edges_now = $EdgeCacheController->multiGetEdgeMine($mineId);

            $log->addLog("Получил данные с кеша шахты");

            $place_param_value_add = [];                                                                                // массив значений параметров мест для массовой вставки в БД
            $edge_param_value_add = [];                                                                                 // массив значений параметров ветвей для массовой вставки в БД
            $date_time_now = Assistant::GetDateNow();

            foreach ($arrows as $arrow) {

                $list_edge = $EdgeCacheController->multiGetEdgeMine($mineId, '*', $arrow['Ветвь']);

                if ($list_edge != false) {
                    if (
                        $arrow['Тип ветви'] === 'Выработанное пространство' or
                        $arrow['Тип ветви'] === 'Изолированная выработка'
                    ) {
                        $edge_id = $list_edge[0]['edge_id'];
                        EdgeMainController::DeleteEdge($edge_id, $mineId);
                    }
                } elseif ($arrow['Тип ветви'] != 'Выработанное пространство' && $arrow['Тип ветви'] != 'Изолированная выработка') {
                    $edge_from_db = (new Query())
                        ->select([
                            '*'
                        ])
                        ->from('edge')
                        ->leftJoin('view_edge_mine_main', 'view_edge_mine_main.edge_id = edge.id')
                        ->where(['edge.ventilation_id' => $arrow['Ветвь']])
                        ->andWhere(['view_edge_mine_main.mine_id' => $mineId])
                        ->limit(1)
                        ->one();

                    if ($edge_from_db) {
                        $response = EdgeHistoryController::EditStatusEdge($edge_from_db['id'], 1);
                        if ($response['status'] != 1) {
                            $log->addLogAll($response);
                        }
                    }
                }

                $key = $arrow['Нач. узел'];
                if (isset($mas_cache[$key])) {
                    $fullStartNodeId = $mas_cache[$key];
                } else {
                    $fullStartNodeId = false;
                }
                $key = $arrow['Кон. узел'];
                if (isset($mas_cache[$key])) {
                    $fullEndNodeId = $mas_cache[$key];
                } else {
                    $fullEndNodeId = false;
                }

                if (!$fullEndNodeId or !$fullStartNodeId) {
                    $log->addData($arrow, '$arrow', __LINE__);
                    continue;
//                    throw new Exception("Список ветвей содержит узлы, которых нет в импортируемом файле");
                }

                if ($arrow['Тип ветви'] !== 'Выработанное пространство' && $arrow['Тип ветви'] !== 'Вент. трубопровод'
                    && $arrow['Тип ветви'] !== 'Утечка' && $arrow['Тип ветви'] !== 'Внешняя утечка' && $arrow['Тип ветви'] !== 'Внешние утечки'
                    && $arrow['Тип ветви'] !== 'Внутренняя утечка' && $arrow['Тип ветви'] !== 'ВМП' && $arrow['Тип ветви'] !== 'Изолированная'
                    && $arrow['Тип ветви'] !== 'Поверхностный газопровод'
                    && $arrow['Тип ветви'] !== 'Дегазационный трубопровод' && $arrow['Тип ветви'] !== 'Дегазационная скважина'
                    && $arrow['Тип ветви'] !== 'Изолированная выработка' && $arrow['Тип ветви'] !== 'Вент.став') {


                    /**
                     * Создание пласта
                     */
                    $plast_model = Plast::findOne(['title' => $arrow['Пласт']]);
                    if (!$plast_model) {
                        if (!is_null($arrow['Пласт'])) {
                            $plast_main_id = $this->AddMain('plast');
                            $plast = new Plast();
                            $plast->id = $plast_main_id;
                            $plast->title = $arrow['Пласт'];
                            $plast->object_id = TypicalObjectEnumController::PLAST;
                            if (!$plast->save()) {
                                $log->addData($plast->errors, '$plast_errors', __LINE__);
                                throw new Exception("Ошибка сохранения Пласта");
                            }
                            $plast_id = $plast->id;
                        } else {
                            $plast_id = 2109;
                        }
                    } else {
                        $plast_id = $plast_model->id;
                    }

                    /**
                     * Создание места
                     */
                    $place_model = Place::find()->where(['title' => $arrow['Название'], 'mine_id' => $mineId])->limit(1)->one();
                    if (!$place_model) {
                        $response = SpecificPlaceController::addPlace($arrow['Название'], $mineId, $plast_id);
                        if ($response['status'] != 1) {
                            $log->addLogAll($response);
                            throw new Exception("Ошибка сохранения места");
                        }

                        $place_id = $response['place_id'];
                        $place_param_value_add = array_merge($place_param_value_add, $response['place_param_values']);

                    } else {
                        $place_id = $place_model->id;
                    }


                    /**
                     * Создание типа ветви
                     */
                    $edge_type_model = EdgeType::findOne(['title' => $arrow['Тип ветви']]);
                    if (!$edge_type_model) {
                        if (!is_null($arrow['Тип ветви'])) {
                            $edge_type = new EdgeType();
                            $edge_type->title = $arrow['Тип ветви'];
                            if (!$edge_type->save()) {
                                $log->addData($edge_type->errors, '$edge_type_errors', __LINE__);
                                throw new Exception("Ошибка сохранения типа ветви");
                            }
                            $edge_type_id = $edge_type->id;
                        } else {
                            $edge_type_id = 1;
                        }
                    } else {
                        $edge_type_id = $edge_type_model->id;
                    }

                    /** ТИП КРЕПИ */
                    switch ($arrow['Тип крепи']) {
                        case "1 - Бетон, кирпич":
                        case "2 - Незакрепленные":
                            $type_shield_id = TypeShieldEnumController::STONE;
                            break;
                        case "3 - Металлическая арка, между рамами 1 м":
                        case "4 - Металлическая арка, между рамами 0,5 м":
                        case "7 - Неполные рамы из ЖБС при калибре 4 или арка при L = 1м в конв. выработке":
                        case "8 - Металлическая арка, между рамами 0,5 м, с конвейером":
                            $type_shield_id = TypeShieldEnumController::METAL;
                            break;
                        default:
                            $type_shield_id = TypeShieldEnumController::STONE;
                            break;
                    }

                    /** ФОРМА ВЫРАБОТКИ */
                    switch ($arrow['Форма сечения']) {
                        case "арка":
                            $shape_edge_id = ShapeEdgeEnumController::ARCHED;
                            break;
                        case "квадратная":
                            $shape_edge_id = ShapeEdgeEnumController::RECTANGLE;
                            break;
                        case "круглая":
                            $shape_edge_id = ShapeEdgeEnumController::ROUND;
                            break;
                        case "трапеция":
                            $shape_edge_id = ShapeEdgeEnumController::TRAPEZOID;
                            break;
                        default:
                            $shape_edge_id = ShapeEdgeEnumController::RECTANGLE;
                            break;
                    }

                    $color = "#000000";
                    if (isset($arrow['Цвет'])) {
                        $color = $arrow['Цвет'];
                    }
                    /**
                     * Создание самого edge
                     */
                    $response = SpecificEdgeController::addEdge(
                        $place_id,
                        $edge_type_id,
                        $fullStartNodeId,
                        $fullEndNodeId,
                        (int)$arrow['ID'],
                        (int)$arrow['Ветвь'],
                        $arrow['Название'],
                        $mineId,
                        $plast_id,
                        $arrow['Сечение м2'],
                        $arrow['Высота м'],
                        $arrow['Ширина м'],
                        $date_time_now,
                        $type_shield_id,
                        $arrow['Угол град'],
                        $shape_edge_id,
                        $color,
                    );
                    if ($response['status'] != 1) {
                        $log->addLogAll($response);
                        throw new Exception("Ошибка добавления выработки");
                    }

                    $edge_param_value_add = array_merge($edge_param_value_add, $response['edge_param_values']);
                    $edge_ids_new[] = $response['edge_id'];


                }

            }
            $log->addLog("Закончил перебор ветвей и их сохранение в БД");

            if (isset($edge_ids_new)) {
                $response = EdgeHistoryController::AddEdgeChange($edge_ids_new, $date_time_now);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка сохранения истории изменения выработок');
                }
            }

            /**
             * Сохранение параметров мест и выработок.
             */
            Yii::$app->db->createCommand()->batchInsert('place_parameter_handbook_value', ['place_parameter_id', 'date_time', 'value', 'status_id'], $place_param_value_add)->execute();
            unset($place_param_value_add);

            $log->addLog("Сохранил значения параметров места");

            $count_to_insert = 0;
            foreach ($edge_param_value_add as $item) {
                $edge_param_value_add_to_insert[] = $item;
                $count_to_insert++;
                if ($count_to_insert == 2000) {
                    Yii::$app->db->createCommand()->batchInsert('edge_parameter_handbook_value', ['edge_parameter_id', 'date_time', 'value', 'status_id'], $edge_param_value_add_to_insert)->execute();
                    $count_to_insert = 0;
                    $edge_param_value_add_to_insert = [];
                }
            }

            if (!empty($edge_param_value_add_to_insert)) {
                Yii::$app->db->createCommand()->batchInsert('edge_parameter_handbook_value', ['edge_parameter_id', 'date_time', 'value', 'status_id'], $edge_param_value_add_to_insert)->execute();
            }
            unset($edge_param_value_add_to_insert);
            unset($edge_param_value_add);

            $log->addLog("Сохранил значения параметров эджа");

            /**
             * Удаляем выработки которых нет в загружаемой схеме шахты.
             */
            $delete_edges = array();                                                                                    //массив с выработками которые надло удалить
            if ($list_edges_now !== false)                                                                              //если существует массив с текущими выработками на схеме
            {

                $log->addLog("Перебор ветвей, существовавших на схеме");

                foreach ($list_edges_now as $elem)                                                                      //перебираем массив текущих выработок
                {
                    $triger = 0;                                                                                        //флаг того что выработку надо добавить в массив выработок на удаление со схемы
                    foreach ($arrows as $candidate)                                                                     //для каждой текущей выработки ищем есть ли такая выработка в массиве который надо построить
                    {
                        if ($candidate['Ветвь'] == $elem['ventilation_id'])                                             //если такая выработка есть на схеме то флаг переводим в положение не удалять со схемы так как она есть в массиве который нужно построить
                        {
                            $triger = 1;
                        }
                    }
                    if ($triger == 0)                                                                                   //если флаг стоит в положении 0 то мы добавляем выработку в массив на удаление со схемы
                    {
                        $delete_edges[] = $elem;
                    }
                }

                $log->addLog("Определил перечень на удаление");

                /**
                 * Удаляем сесоры с ввыработок которых нет в загружаемой схеме шахты.
                 */
                $sens = new SensorCacheController();
                $sens->runInitHash($mineId);                                                                            //заполянем кеш сенсоров

                $log->addLog("Заполнил кеш сенсоров");

                $result_sensors = array();
                $temp_sensors_handbook = $sens->multiGetParameterValueHash('*', ParamEnum::EDGE_ID, '1'); //ищем параметр у всех сенсоров в справочнике
                $temp_sensors_value = $sens->multiGetParameterValueHash('*', ParamEnum::EDGE_ID, '2');    //ищем параметр у всех сенсоров

                $log->addLog("Получил Список параметров сенсоров из БД");

                $temp_sensors = array();
                if ($temp_sensors_handbook !== false)                                                                   //если такие параметры есть в справочнике
                {                                                                                                       //то добавляем их в массив по котрому будем искать сенсоры
                    $temp_sensors = array_merge($temp_sensors, $temp_sensors_handbook);
                }
                if ($temp_sensors_value !== false)                                                                      //если такие параметры есть
                {                                                                                                       //то добавляем их в массив по котрому будем искать сенсоры
                    $temp_sensors = array_merge($temp_sensors, $temp_sensors_value);
                }

                $log->addLog("Сделал справочник параметров сенсора");

                foreach ($delete_edges as $delete_edge)                                                                 //перебирааем все удаляемые выработки
                {
                    EdgeMainController::DeleteEdge($delete_edge['edge_id'], $mineId);                                      //удаляем выработку со схемы
                    foreach ($temp_sensors as $sensor)                                                                  //перебераем сенсоры
                    {
                        if ($sensor['value'] == $delete_edge['edge_id'])                                                //если сенсор стоит на текущей выраотке
                        {
                            $result_sensors[] = $sensor;                                                                //заносим сенсорв массив на сохранение в файл
                            $result_delete_sensor = SensorMainController::DeleteSensorFromShema($mineId, $sensor['sensor_id']);//удаляем сеноср со схемы
                            $status *= $result_delete_sensor['status'];
                            $warnings[] = $result_delete_sensor['warnings'];
                            $errors[] = $result_delete_sensor['errors'];
                        }
                    }
                }

                $log->addLog("Удалил сенсоры и эджы со схемы");


                /**
                 * Cохранение удаленных выработок и сенсоров в файл
                 */
                $filename = 'array_edge.txt';
                $filename2 = 'array_sensors.txt';
                // Запись.
                $data = serialize($delete_edges);      // PHP формат сохраняемого значения.
                $data2 = serialize($result_sensors);      // PHP формат сохраняемого значения.
                file_put_contents($filename, $data);
                file_put_contents($filename2, $data2);

                $log->addLog("Сохранил удаленные выработки и сенсоры в файлы");

                $cache_edge = Yii::$app->cache_edge;
                (new EdgeCacheController())->amicum_flushall();
                $cache_edge->flush();

                $log->addLog("Очистил кеш выработок полностью");

                /**
                 * кеш выработок
                 */
                $response = (new EdgeCacheController())->runInit($mineId);
                $errors[] = $response['errors'];
                $status['EdgeCacheController'] = $response['status'];
                unset($response);

                $log->addLog("Заполнил кеш выработок полностью");

                /**
                 * Кэш графа шахты
                 */
                $response = (new CoordinateController())->buildGraph($mineId);
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                unset($response);

                $log->addLog("Построил граф схемы для расчета координат");
                /**
                 * кеш сенсоров OPC
                 */
                $response = (new OpcController('1', '1'))->actionBuildGraph($mineId);
                $errors[] = $response['errors'];
                unset($response);

                $log->addLog("Заполнил кеш OPC полностью");

            }

            // пересчет длины выработок:
            $response = SuperTestController::UpdateEdgeLength($mineId, $date_time_now);
            $warnings[] = $response['warnings'];
            $errors[] = $response['errors'];
            unset($response);

            $log->addLog("Пересчитал длину выработок");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());

    }


    //Функция заполнения шахты из Excel файла
    //В БД должен существовать plast с id = 2109(Прочее)
    //В БД должен существовать edge_type с id = 1(Прочее)
    public function actionUploadMine()
    {
        $log = new LogAmicumFront("actionUploadMine");

//        ini_set('max_execution_time', -1);
//        ini_set('memory_limit',"2500M");
//        ini_set('memory_limit', "10500M");
        $cache = Yii::$app->cache_edge;
        $cache->flush();
        $result = array();
        try {
            $log->addLog("Начал выполнение метода");
            $post = Assistant::GetServerMethod();
            if (!isset($post['mine_id']) || $post['mine_id'] == "") {
                throw new Exception("Не передан ключ шахты");
            }

            $mine = Mine::findOne((int)$post['mine_id']);                                                               //найти шахту
            if (!$mine) {                                                                                               //если найдена
                throw new Exception("Шахта не найдена по ключу");
            }

            if ($_FILES['mineFile']['size'] < 6557 || !isset($post['file_type']) || $post['file_type'] == "") {         //если передан файл и его тип /если файл размером меньше 6557, то считается пустым
                throw new Exception("Файл не загружен или пустой файл");
            }
            $file = $_FILES['mineFile'];
            $file_type = $post['file_type'];

            if (PHP_OS == "Linux") {
                $upload_dir = '/var/www/html/amicum/frontend/web/mines-excel';                                          //объявляем и инициируем переменную для хранения пути к папке с файлом
            } else {
                $upload_dir = 'C:\xampp\htdocs\amicum\frontend\web\mines-excel';
            }

            $uploaded_file = $upload_dir . "\mine_" . $post['mine_id'] . "_" . date('YmdHis') . "." . $file_type; //генерация названия загруженного файла

            if (!move_uploaded_file($file['tmp_name'], $uploaded_file)) {                                               // если не удалось сохранить переданный файл в указанную директорию
                throw new Exception("Не удалось сохранить файл");
            }

            $log->addLog("Уложил файл на сервер");


            $excelFile = Excel::import($uploaded_file);                                                                 // импортировать загруженный файл
//            $log->addData($excelFile[1][5], '$excelFile', __LINE__);
            if (count($excelFile) < 2) {
                throw new Exception("Не верная структура импортируемого файла");
            }

            $nodesList = $excelFile[0];                                                                                 // узлы
            $edgesList = $excelFile[1];                                                                                 // ветви

            if (isset($excelFile[2])) {
                $peremichki = $excelFile[2];                                                                            // перемычки
            }

            if (isset($excelFile[3])) {
                $sensors = $excelFile[3];                                                                               // датчики
            }

            if (isset($excelFile[4])) {
                $stations = $excelFile[4];                                                                              // станции
            }

            if (isset($excelFile[5])) {
                $pressures = $excelFile[5];                                                                             // манометры
            }


            $log->addLog("Импорт файла завершен. Начинаю обработку");

            $response = $this->saveNodes2($nodesList, $mine->id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка выполнения метода сохранения сопряжения");
            }
            $mas_cache = $response['Items'];

            $log->addLog("Импортировал сопряжения");

//            throw new Exception("Отладочный стоп");

            $response = $this->UpdateArrows2($edgesList, $mine->id, $mas_cache);                                     //вызвать функцию сохранения ветвей
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка выполнения метода сохранения ветвей");
            }

            $log->addLog("Импортировал ветви");


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Название метода: actionUpdateMine()
     * Обновить имеющиеся данные схемы в бд.
     * @package backend\controllers
     *
     * Входные обязательные параметры:
     *
     * Входные необязательные параметры
     *
     * @see
     * @example
     *
     * @author fidchenkoM
     * Created date: on 04.06.2019 15:10
     * @since ver
     */
    public function actionUpdateMine()
    {
//        ini_set('max_execution_time', -1);
//        ini_set('memory_limit', "10500M");
//        Yii::$app->cache->flush();
        $warnings = array();
        require_once(Yii::getAlias('@vendor/moonlandsoft/yii2-phpexcel/Excel.php'));

        require_once(Yii::getAlias('@vendor/phpoffice/phpexcel/Classes/PHPExcel.php'));
        $errors = array();
        $status = 1;
        $result = array();
        $post = Yii::$app->request->post();                                                                             //получение данных от ajax-запроса
        if (isset($post['mine_id']) && $post['mine_id'] != "") {                                                           //если передан id шахты
            try {

                $microtime_start = microtime(true);
                $mine = Mine::findOne((int)$post['mine_id']);                                                               //найти шахту
                if ($mine) {                                                                                                 //если найдена
                    $warnings[] = "actionUpdateMine.mine_id = " . $mine->id;
                    if ($_FILES['mineFile']['size'] > 0 and isset($post['file_type']) and $post['file_type'] != "") {       //если передан файл и его тип
                        $file = $_FILES['mineFile'];                                                                        //записываем в переменную полученные данные
                        $file_type = $post['file_type'];                                                                    //записываем в переменную полученные данные
                        $upload_dir = 'C:\xampp\htdocs\amicum\frontend\web\mines-excel';                                                                       //объявляем и инициируем переменную для хранения пути к папке с файлом
                        $uploaded_file = $upload_dir . "\mine_" . $post['mine_id'] . "_" . date('YmdHis') . "." . $file_type;//генерация названия загруженного файла
                        $warnings[] = "actionUpdateMine.Начинается загрузка файла.Сохранение временного файла";
                        if (!move_uploaded_file($file['tmp_name'], $uploaded_file)) {                                       //если не удалось сохранить переданный файл в указанную директорию
                            $errors[] = "не удалось сохранить файл";                                                        //выдать соответствующую ошибку
                        } else {                                                                                            //если удалось
                            $warnings[] = "actionUpdateMine.Временный файл создан";
                            $duration_method = round(microtime(true) - $microtime_start, 6);
                            $warnings[] = 'actionUpdateMine. Время выполнения до загрузки ексель. ' . $duration_method;
                            $excelFile = Excel::import($uploaded_file);                                            //импортировать загруженный файл
                            $warnings[] = "actionUpdateMine.Импорт ексель файла завершен";
                            $nodesList = $excelFile[0];                                                                     //получить из первого листа сопряжения
                            $edgesList = $excelFile[1];                                                                     //получить из второго листа ветви


                            $duration_method = round(microtime(true) - $microtime_start, 6);
                            $warnings[] = 'actionUpdateMine. Время выполнения загрузки ексель. ' . $duration_method;
                            $warnings[] = "actionUpdateMine.Начинаю работу с конджакшеном";

                            $result_conjunction = $this->UpdateNodes($nodesList, $mine->id);
                            $status *= $result_conjunction['status'];
                            $warnings[] = $result_conjunction['warnings'];
                            $errors[] = $result_conjunction['errors'];
                            if ($status == 1) {

                                $duration_method = round(microtime(true) - $microtime_start, 6);
                                $warnings[] = "actionUpdateMine.Начинаю сохранение выработок " . $duration_method;
                                $result_edge = $this->UpdateArrow($edgesList, $mine->id);                                            //вызвать функцию сохранения ветвей
                                $duration_method = round(microtime(true) - $microtime_start, 6);
                                $warnings[] = "actionUpdateMine.Закончил сохранение выработок " . $duration_method;
                                $status *= $result_edge['status'];
                                $warnings[] = $result_edge['warnings'];
                                $errors[] = $result_edge['errors'];
                            }                                    //вызвать функцию сохранения сопряжений
                            else {
                                $errors[] = "actionUpdateMine.Ошибка выполнения метода сохранения сопряжения UpdateNodes";
                            }
                            $warnings[] = "uploaded";                                                                          //сказать, что шахта успешно загружена
                        }
                    } else {                                                                                                   //если файл или его тип не передан
                        $errors[] = "Файл не загружен";                                                                     //сообщить об этом
                    }
                } else {                                                                                                       //если шахта не найдена
                    $errors[] = "Шахта не найдена";                                                                         //сообщить об этом
                }
            } catch (Throwable $ex) {
                $warnings[] = "actionUpdateMine.Исключение:";
                $warnings[] = $ex->getMessage();
                $warnings[] = $ex->getLine();
            }

        } else {                                                                                                           //если id шахты не передан
            $errors[] = "Шахта не передана";                                                                            //сообщить об этом
        }
        $duration_method = round(microtime(true) - $microtime_start, 6);
        $warnings[] = 'actionUploadMine. Время выполнения метода. ' . $duration_method;
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Название метода: UpdateArrow()
     * Обновление выработок с схемы полувинтеляции.(Добавление ventilation_id)
     * @return array
     *
     * @package backend\controllers
     *
     * Входные обязательные параметры:
     * $arrows - массив выработок из excel
     *
     * @author fidchenkoM
     * Created date: on 04.06.2019 14:09
     * @since ver
     */
    public function UpdateArrow($arrows, $MineId)
    {
        set_time_limit(0);
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();
//        $list_edge = Edge::find()->limit(10000)->all();                                                                 //ищем вырабтки в бд

        $EdgeCacheController = new EdgeCacheController();
        $list_edge = $EdgeCacheController->multiGetEdgeMine($MineId);
        $cache = Yii::$app->cache;
        if ($list_edge === false) {
            $list_edge = $EdgeCacheController->initEdgeMine($MineId);
        }
        if ($list_edge !== false) {
            foreach ($list_edge as $edge) {
                $conj_start = $edge['conjunction_start_id'];
                $conj_stop = $edge['conjunction_end_id'];
                $key = 'ConjunctionDB_' . $conj_start;
                $fullStartNodeId = $cache->get($key);
                $key = 'ConjunctionDB_' . $conj_stop;
                $fullEndNodeId = $cache->get($key);
                if ($fullEndNodeId && $fullStartNodeId) {
                    foreach ($arrows as $arrow) {
                        if (($arrow['Нач. узел'] == $fullStartNodeId && $arrow['Кон. узел'] == $fullEndNodeId)
                            || ($arrow['Нач. узел'] == $fullEndNodeId && $arrow['Кон. узел'] == $fullStartNodeId)) {
                            $current_edge = Edge::find()->where(['id' => $edge['edge_id']])->limit(1)->one();
                            $current_edge['ventilation_current_id'] = (int)$arrow['ID'];
                            $current_edge['ventilation_id'] = (int)$arrow['Ветвь'];
                            if (!$current_edge->save()) {
                                $status = 0;
                                $errors[] = 'UpdateArrow.Не смог сохранить edge c id = ' . $current_edge['id'];
                                $errors[] = $current_edge->errors;
                            }
                            break;
                        }
                    }
                } else {
                    $warnings[] = 'Не нашел узлы в кеше для edge с id= ' . $edge['edge_id'];
                }
            }
        } else {
            $warnings[] = 'Нет в кеше выработок и в БД для mine_id = ' . $MineId;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Название метода: UpdateNodes()
     * @return array
     * @package backend\controllers
     *
     * Входные обязательные параметры:
     * $mineId - идентификатор шахты
     * $nodes - узлы из excel
     *
     * @author fidchenkoM
     * Created date: on 04.06.2019 14:16
     * @since ver
     */
    public function UpdateNodes($nodes, $mineId)
    {
        set_time_limit(0);
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();
        $mas_cache_conj = array();
        try {
            $microtime_start = microtime(true);
            foreach ($nodes as $node) {
                $node_in_db = Conjunction::find()
                    ->andWhere('x like ' . $node['X'])
                    ->andWhere('z like ' . $node['Y'])
                    ->andWhere('y like ' . $node['Z'])
                    ->andWhere('mine_id =' . $mineId)
                    ->one();
                if ($node_in_db) {
                    $node_in_db['ventilation_id'] = $node['Узел'];
                    if ($node_in_db->save()) {
                        $key = 'ConjunctionDB_' . $node_in_db['id'];
                        $mas_cache_conj[$key] = $node['Узел'];
//                        $warnings[] = 'Пересохранил узел в бд'.$node_in_db['id'];
                    } else {
                        $errors[] = 'UpdateNodes.Не смог сохранить conjunction c id = ' . $node_in_db['id'];
                        $errors[] = $node_in_db->errors;
                    }
                }
            }
            $warnings[] = "UpdateNodes. Перебрал все узлы.";
            $cache = Yii::$app->cache;
            if ($mas_cache_conj) {
                $cache->multiSet($mas_cache_conj);
            }

            $duration_method = round(microtime(true) - $microtime_start, 6);
            $warnings[] = 'UpdateNodes. Время выполнения метода. ' . $duration_method;

        } catch (Throwable $ex) {
            $errors[] = "UpdateNodes.Исключение:";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;

        }
        $warnings[] = "UpdateNodes.Закончил выполнять метод UpdateNodes.";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    //Функция получения списка шахт для модалки, в которой выбирается файл
    public function actionGetMinesForExcel()
    {
        $mines = (new Query())
            ->select(['id', 'title'])
            ->from('mine')
            ->all();
        echo json_encode($mines);
    }


    //метод копирует параметры типового параметра в параметры конкретного объекта - нужен для создания конкретного объекта по шаблону типового объекта
    private function actionCopyTypicalParametersToEdge($typical_object_id, $specific_object_id)
    {
        $debug_flag = 0;                                                                                                  //отладочный флаг
        $flag_done = 1;                                                                                                   //флаг успешного выполнения метода
        //копирование параметров справочных
        if ($type_object_parameters = TypeObjectParameter::find()->where(['object_id' => $typical_object_id, 'parameter_type_id' => 1])->all())                           //Находим все параметры типового объекта
        {
            foreach ($type_object_parameters as $type_object_parameter) {
                //создаем новый параметр у конкретного объекта
                $edge_parameter_id = $this->actionAddEdgeParameter($specific_object_id, $type_object_parameter->parameter_id, $type_object_parameter->parameter_type_id);

                //ищем последние справочное значения параметра типового объекта и копируем их в значение справочное конкретного объекта
                if ($edge_parameter_id
                    and $typical_object_parameter_handbook_values = TypeObjectParameterHandbookValue::find()
                        ->where(['type_object_parameter_id' => $type_object_parameter->id])
                        ->orderBy(['date_time' => SORT_DESC])
                        ->one())
                    $flag_done = $this->actionAddEdgeParameterHandbookValue($edge_parameter_id, $typical_object_parameter_handbook_values->value, $typical_object_parameter_handbook_values->status_id, 1);
            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись справочных параметров" . "\n");

        //копирование функций типового объекта
        //находим функции типового объекта
        if ($type_object_functions = TypeObjectFunction::find()->where(['object_id' => $typical_object_id])->all()) {
            foreach ($type_object_functions as $type_object_function) {
                $edge_function_id = $this->actionAddEdgeFunction($specific_object_id, $type_object_function->func_id);
                if ($edge_function_id == -1) $flag_done = -1;
            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись функций" . "\n");
        return $flag_done;
    }

    //сохранение справочного значения конкретного параметра эджа/ветви
    public function actionAddEdgeParameterHandbookValue($edge_parameter_id, $value, $status_id, $date_time)
    {
        $edge_parameter_handbook_value = new EdgeParameterHandbookValue();
        $edge_parameter_handbook_value->edge_parameter_id = $edge_parameter_id;
        if ($date_time == 1) $edge_parameter_handbook_value->date_time = date("Y-m-d H:i:s.U", strtotime("-1 second"));
        else $edge_parameter_handbook_value->date_time = $date_time;
        $edge_parameter_handbook_value->value = strval($value);
        $edge_parameter_handbook_value->status_id = $status_id;

        if (!$edge_parameter_handbook_value->save()) {
            return (-1);
        } else return 1;
    }

    //создание параметра конкретного места
    public function actionAddEdgeParameter($edge_id, $parameter_id, $parameter_type_id)
    {
        $debug_flag = 0;

        if ($debug_flag == 1) echo nl2br("----зашел в функцию создания параметров места  =" . $edge_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        if ($edge_parameter = EdgeParameter::find()->where(['edge_id' => $edge_id, 'parameter_id' => $parameter_id, 'parameter_type_id' => $parameter_type_id])->one()) {
            return $edge_parameter->id;
        } else {
            $edge_parameter_new = new EdgeParameter();
            $edge_parameter_new->edge_id = $edge_id;                                                                 //айди ветви
            $edge_parameter_new->parameter_id = $parameter_id;                                                         //айди параметра
            $edge_parameter_new->parameter_type_id = $parameter_type_id;                                               //айди типа параметра

            if ($edge_parameter_new->save()) return $edge_parameter_new->id;
            else return (-1); //"Ошибка сохранения значения параметра места" . $place_id->id;
        }
    }

    //сохранение функций edga ветви
    public function actionAddEdgeFunction($edge_id, $function_id)
    {
        $debug_flag = 0;
        if ($debug_flag == 1) echo nl2br("----зашел в функцию создания функции места  =" . $edge_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        if ($edge_function = EdgeFunction::find()->where(['edge_id' => $edge_id, 'function_id' => $function_id])->one()) {
            return $edge_function->id;
        } else {
            $edge_function_new = new EdgeFunction();
            $edge_function_new->edge_id = $edge_id;                                                                  //айди эджа
            $edge_function_new->function_id = $function_id;                                                            //айди функции
            //статус значения

            if ($edge_function_new->save()) return $edge_function_new->id;
            else return -1;
        }
    }

    //метод GET!!!!
    //метод добавляет заданный параметр в параметры конкретного объекта а в случае если задано значение то устанавливает его
    //метод работает только на справочные значени
    //http://127.0.0.1/arrow/add-current-parameters-to-place-with-value-get?parameter_id=263&value=1 - метан уставка
    //http://127.0.0.1/arrow/add-current-parameters-to-place-with-value-get?parameter_id=264&value=17 - СО уставка
    public function actionAddCurrentParametersToPlaceWithValueGet()
    {
        $post = Yii::$app->request->get();
        $flag_param = 0;                                                                                                  //флаг наличия парамтера
//        $flag_value = 0;                                                                                                  //флаг наличия значения
        if (isset($post['parameter_id']) && $post['parameter_id'] != "") {                                                 //проверяем входящие данные со стороны фронта в данном случае параметр айди
            $parameter_id = $post['parameter_id'];                                                                      //объявляем ключ параметра из фронт энда
            $flag_param = 1;
        }
        if (isset($post['value']) && $post['value'] != "") {
            $handbook_value = $post['value'];                                                                          //объявляем значение устанавливаемое этому параметру
//            $flag_value = 1;
        }
        $errors = array();                                                                                                //объявляем пустой массив ошибок
        $flag_done = 0;                                                                                                   //флаг успешного выполнения метода
        //копирование параметров справочных
        $places = (new Query())//получаем все плейсы на шахте
        ->select(
            ['id'])
            ->from(['place'])
            ->all();

        if ($places and $flag_param == 1)                                                                               //если плэйс существует, то делаем выборку
        {
            foreach ($places as $place) {
                //создаем новый параметр у конкретного места который получили с фронт энд стороны
                $place_parameter_id = $this->actionAddPlaceParameter($place['id'], $parameter_id, 1);
                if ($place_parameter_id and isset($handbook_value)) {                                                       //если плэейс параметр айди создан и существует, то записываем его значение
                    $flag_done = $this->actionAddPlaceParameterHandbookValue($place_parameter_id, $handbook_value, 1, 1);
                } else {
                    if (!$place_parameter_id) $errors[] = "Параметер " . $parameter_id . " для плейс айди: " . $place['id'] . " не создан";
                }
            }
        } else {
            if (!$places) $errors[] = "Плейсев  в базе данных нет. Параметр и значения не установлены";
            if ($flag_param == 0) $errors[] = "Параметр не задан на стороне фронта";
        }
        $result = array('Состояние добавления: ' => $flag_done, 'количество обработанных плейсов ' => count($places), 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                   //формат возвращаемых данных json методом yii2
        Yii::$app->response->data = $result;
    }

    //метод копирует параметры типового параметра в параметры конкретного объекта - нужен для создания конкретного объекта по шаблону типового объекта
    private function actionCopyTypicalParametersToPlace($typical_object_id, $specific_object_id)
    {
        $debug_flag = 0;                                                                                                  //отладочный флаг
        $flag_done = 1;                                                                                                   //флаг успешного выполнения метода
        //копирование параметров справочных
        if ($type_object_parameters = TypeObjectParameter::find()->where(['object_id' => $typical_object_id, 'parameter_type_id' => 1])->all())                           //Находим все параметры типового объекта
        {
            foreach ($type_object_parameters as $type_object_parameter) {
                //создаем новый параметр у конкретного объекта
                $place_parameter_id = $this->actionAddPlaceParameter($specific_object_id, $type_object_parameter->parameter_id, $type_object_parameter->parameter_type_id);

                //ищем последние справочное значения параметра типового объекта и копируем их в значение справочное конкретного объекта
                if ($place_parameter_id
                    and $typical_object_parameter_handbook_values = TypeObjectParameterHandbookValue::find()
                        ->where(['type_object_parameter_id' => $type_object_parameter->id])
                        ->orderBy(['date_time' => SORT_DESC])
                        ->one())
                    $flag_done = $this->actionAddPlaceParameterHandbookValue($place_parameter_id, $typical_object_parameter_handbook_values->value, $typical_object_parameter_handbook_values->status_id, 1);
            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись справочных параметров" . "\n");

        //копирование функций типового объекта
        //находим функции типового объекта
        if ($type_object_functions = TypeObjectFunction::find()->where(['object_id' => $typical_object_id])->all()) {
            foreach ($type_object_functions as $type_object_function) {
                $place_function_id = $this->actionAddPlaceFunction($specific_object_id, $type_object_function->func_id);
                if ($place_function_id == -1) $flag_done = -1;
            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись функций" . "\n");
        return $flag_done;
    }

    //сохранение функций места
    public function actionAddPlaceFunction($place_id, $function_id)
    {
        $debug_flag = 0;
        if ($debug_flag == 1) echo nl2br("----зашел в функцию создания функции места  =" . $place_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        if ($place_function = PlaceFunction::find()->where(['place_id' => $place_id, 'function_id' => $function_id])->one()) {
            return $place_function->id;
        } else {
            $place_function_new = new PlaceFunction();
            $place_function_new->place_id = $place_id;                                                                  //айди места
            $place_function_new->function_id = $function_id;                                                            //айди функции
            //статус значения

            if ($place_function_new->save()) return $place_function_new->id;
            else return -1;
        }
    }

    //создание параметра конкретного места
    public function actionAddPlaceParameter($place_id, $parameter_id, $parameter_type_id)
    {
        $debug_flag = 0;

        if ($debug_flag == 1) echo nl2br("----зашел в функцию создания параметров места  =" . $place_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        if ($place_parameter = PlaceParameter::find()->where(['place_id' => $place_id, 'parameter_id' => $parameter_id, 'parameter_type_id' => $parameter_type_id])->one()) {
            return $place_parameter->id;
        } else {
            $place_parameter_new = new PlaceParameter();
            $place_parameter_new->place_id = $place_id;                                                                 //айди места
            $place_parameter_new->parameter_id = $parameter_id;                                                         //айди параметра
            $place_parameter_new->parameter_type_id = $parameter_type_id;                                               //айди типа параметра

            if ($place_parameter_new->save()) return $place_parameter_new->id;
            else return (-1); //"Ошибка сохранения значения параметра места" . $place_id->id;
        }
    }

    //сохранение справочного значения конкретного параметра места
    public function actionAddPlaceParameterHandbookValue($place_parameter_id, $value, $status_id, $date_time)
    {
        $place_parameter_handbook_value = new PlaceParameterHandbookValue();
        $place_parameter_handbook_value->place_parameter_id = $place_parameter_id;
        if ($date_time == 1) $place_parameter_handbook_value->date_time = date("Y-m-d H:i:s.U", strtotime("-1 second"));
        else $place_parameter_handbook_value->date_time = $date_time;
        $place_parameter_handbook_value->value = strval($value);
        $place_parameter_handbook_value->status_id = $status_id;

        if (!$place_parameter_handbook_value->save()) {
            return (-1);
        } else return 1;
    }

    //синхронизация справочников Oracle ДОЛЖНОСТИ
    //метод нужен для выкачивания справочника должностей из оракл в базу майскуль
    //делайет полный селект справочника должностей оракал как есть и загружает его в базу амикум как есть.
    public function actionSinhroOracleJob()
    {
        //gc_collect_cycles();
        //echo "----".(memory_get_peak_usage()/1024/1024)."----";
//        $post = Yii::$app->request->post();                                                                           //получение данных от ajax-запроса
        $error = array();                                                                                               //массив ошибок
        $varible_array = array();                                                                                       //массив переменных, для возврата на фронт
        $conn_oracle = oci_connect('strata', 'strata_psw', '10.36.22.67:1521/komstu01', 'AL32UTF8');        //подключение к оракаловскому серверу на шахте Комсольской
        if (!$conn_oracle) {                                                                                            //проверка наличия подключения с сервером оракл
            $varible_array[0] = "connection=False";                                                                     //параметр соединения к базе данных если коннекшена
            $varible_array[1] = "errors=yes";                                                                           //параметр наличия ошибки
            $error = oci_error();                                                                                       //заполнение массива ошибок в случае ее наступления
            trigger_error(htmlentities($error['message'], ENT_QUOTES), E_USER_ERROR);                       //отправка данных на фронт мимо всех проверок
        } else {
            $varible_array[0] = "Connection=True";                                                                      //параметр успешного соединения
            $varible_array[1] = "errors=no";                                                                            //параметр отсутствия ошибок при соединении
        }
        $varible_array[2] = "controllers=Arraycontroller/actionSinhroOracleJob";                                        //параметр из какого контролера вызван

        $stid = oci_parse($conn_oracle, 'SELECT STELL,STEXT FROM SAP_PMS.ZHCM_ITF_JOB');                         //создание строки запрос списка должностей
        oci_execute($stid);                                                                                             //выполнение запроса на строне оракл и получение самих данных

        $i = 0;                                                                                                         //индекс
//        $oracle_position = array();                                                                                   //массив строк - должностей
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS))                                       //пробегаемся по массиву строк должностей
        {
            $j = 0;                                                                                                     //индекс
            $oracle_position = array();                                                                                 //перегоняемый массив должностей
            foreach ($row as $item)                                                                                     //пробегаемся по массиву строк
            {
                if ($item !== null and $item != "" and $item != " ")                                                    //проверка на нулевую часть, если не ноль, то писать
                    $oracle_position[$j] = $item;
                else                                                                                                    //если ноль, то писать -
                    $oracle_position[$j] = "-";
                $j++;
            }
            $position = new Position();                                                                                 //создаем модель
            $position->id = (int)$oracle_position[0];                                                                   //запись айди
            $position->title = (string)$oracle_position[1];                                                             //запись тайтла
            if (!$position->save())                                                                                     //запись все строки в целом, если ошибка, то пишем ее и идем дальше
                $error[] = (int)$oracle_position[0] . "---" . (string)$oracle_position[1] . "---" . var_dump($position->errors);

            $stroka[$i] = array($oracle_position);                                                                      //заполняем массив строк должностей, для того, чтобы передать во фронт
            $i++;
        }

        $varible_array[3] = $stroka;                                                                                    //перегоняем массив значений в окончательный массив для передачи во фронт энд
        $result = array("varibleArray" => $varible_array, "errorArray" => $error);                                      //дополняем окончательный массив массивом ошибок
//gc_collect_cycles();
        //echo "----".(memory_get_peak_usage()/1024/1024)."----";
        //вывод информации на страницу фронт энда
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;

    }

    //метод создает связку компаний и подразделений. фактически все подразделения храняться в компаниях
    public function actionSinhroOracleDepComp()
    {
//        $post = Yii::$app->request->post();                                                                            //получение данных от ajax-запроса
        $error = array();                                                                                                 //массив ошибок
        $varible_array = array();                                                                                        //массив переменных, для возврата на фронт
        $conn_oracle = oci_connect('strata', 'strata_psw', '10.36.22.67:1521/komstu01', 'AL32UTF8');        //подключение к оракаловскому серверу на шахте Комсольской
        if (!$conn_oracle) {                                                                                            //проверка наличия подключения с сервером оракл
            $varible_array[0] = "connection=False;";                                                                        //параметр соединения к базе данных если коннекшена
            $varible_array[1] = "errors=yes";                                                                             //параметр наличия ошибки
            $error = oci_error();                                                                                       //заполнение массива ошибок в случае ее наступления
            trigger_error(htmlentities($error['message'], ENT_QUOTES), E_USER_ERROR);                //отправка данных на фронт мимо всех проверок
        } else {
            $varible_array[0] = "Connection=True";                                                                        //параметр успешного соединения
            $varible_array[1] = "errors=no";                                                                            //параметр отсутствия ошибок при соединении
        }
        $varible_array[2] = "controllers=Arraycontroller/actionSinhroOraclePodr";                                         //параметр из какого контролера вызван
        $stid = oci_parse($conn_oracle, '
                SELECT OBJID, STEXT, SOBID FROM SAP_PMS.ZHCM_ITF_PODR
        ');                                                                                                            //создание строки запрос списка подразделений
        oci_execute($stid);                                                                                             //выполнение запроса на строне оракл и получение самих данных
        $depar = array();
        $i = 0;                                                                                                           //индекс
//        $oracle_department = array();                                                                                       //массив строк - должностей
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS))                                        //пробегаемся по массиву строк должностей
        {
            $j = 0;                                                                                                        //индекс
            $oracle_department = array();                                                                                   //перегоняемый массив поразделений по сути нам нуежн из запроса самый верхний и самый нижний элемент

            foreach ($row as $item)                                                                                     //пробегаемся по массиву строк
            {
                if ($item !== null and $item != "" and $item != " " and $item != "  ")                                                                                      //проверка на нулевую часть, если не ноль, то писать
                    $oracle_department[$j] = $item;
                else                                                                                                    //если ноль, то писать -
                    $oracle_department[$j] = "0";
                $j++;
            }
//[0] - код подразделения [1]-название подразделения [2] - код вышестоящего подразделения
            $compdep = new CompanyDepartment();                                                                   //модель которая хранит связку компании и департамента
            $compdep->id = (int)$oracle_department[0];                                                                       //ключ связки компании и департамента - равен ключу департамента - логика сменена, т.к. есть задача пробросить единый ключ связку до прикладных таблиц
            $compdep->department_id = 1;                                                            //ключ департамента подразделения

            if ((int)$oracle_department[2] == 0)
                $compdep->company_id = (int)$oracle_department[0];
            else {
                $CompanyFindFlag = Company::findOne(['id' => (int)$oracle_department[2]]);
                if ($CompanyFindFlag)
                    $compdep->company_id = (int)$oracle_department[2];                                                    //ключ компании - у нас. у них это ключ подразделения, но самого верхнего в их ирархии
                else
                    $compdep->company_id = 201;                                                    //ключ компании - у нас. у них это ключ подразделения, но самого верхнего в их ирархии
            }
            $compdep->department_type_id = DepartmentTypeEnum::OTHER;                                                                     //тип департамента 5 прочеее
            if (!$compdep->save())                                                                                        //запись все строки в целом, если ошибка, то пишем ее и идем дальше
                $error[] = "||>>--Company_departments: ID связки" . (int)$oracle_department[0] . "-ID компании" . (int)$oracle_department[2] . "-Дамп ошибки:" . var_dump($compdep->errors) . "||--<<";
            else {
                $shift_department = new ShiftDepartment();                                                        //создаем привязку подразделения к режиму работ - берем по умолчанию режим 4 смены
                $shift_department->date_time = date("Y-m-d H:i:s");                                        //текущее дата и время
                $shift_department->plan_shift_id = 5;                                                             //режим работы ключ 5( 4 сменка)
                $shift_department->company_department_id = $compdep->id;                                          //ключ связки департамента и компании
                if (!$shift_department->save())                                                                                        //запись все строки в целом, если ошибка, то пишем ее и идем дальше
                    $error[] = "||>>--Shift_departments: ID связки" . (int)$oracle_department[0] . "-ID компании" . (int)$oracle_department[2] . "-Дамп ошибки:" . var_dump($compdep->errors) . "||--<<";
            }
            $depar[$i] = array($oracle_department);                                                                       //заполняем массив строк должностей, для того, чтобы передать во фронт
            $i++;
        }
        $varible_array[3] = $depar;                                                                                       //перегоняем массив значений в окончательный массив для передачи во фронт энд
        $result = array("varibleArray" => $varible_array, "errorArray" => $error);                                            //дополняем окончательный массив массивом ошибок
        Yii::$app->response->format = Response::FORMAT_JSON;                                                   //вывод информации на страницу фронт энда
        Yii::$app->response->data = $result;
    }

    //синхронизация справочников Oracle Компаний
    //метод нужен для выкачивания справочника (компаний) из оракл в базу майскуль
    //делает запрос как есть к оракал где берутся все подразделения которые больше >1000000 и у тех у кого SOBID пустой
    public function actionSinhroOracleComp()
    {
        //gc_collect_cycles();
        //echo "----".(memory_get_peak_usage()/1024/1024)."----";
//        $post = Yii::$app->request->post();                                                                            //получение данных от ajax-запроса
        $error = array();                                                                                                 //массив ошибок
        $varible_array = array();                                                                                        //массив переменных, для возврата на фронт
        $conn_oracle = oci_connect('strata', 'strata_psw', '10.36.22.67:1521/komstu01', 'AL32UTF8');        //подключение к оракаловскому серверу на шахте Комсольской
        if (!$conn_oracle) {                                                                                            //проверка наличия подключения с сервером оракл
            $varible_array[0] = "connection=False;";                                                                        //параметр соединения к базе данных если коннекшена
            $varible_array[1] = "errors=yes";                                                                             //параметр наличия ошибки
            $error = oci_error();                                                                                       //заполнение массива ошибок в случае ее наступления
            trigger_error(htmlentities($error['message'], ENT_QUOTES), E_USER_ERROR);                //отправка данных на фронт мимо всех проверок
        } else {
            $varible_array[0] = "Connection=True";                                                                        //параметр успешного соединения
            $varible_array[1] = "errors=no";                                                                            //параметр отсутствия ошибок при соединении
        }
        $varible_array[2] = "controllers=Arraycontroller/actionSinhroOracleComp";                                         //параметр из какого контролера вызван
        $stid = oci_parse($conn_oracle, '
              SELECT OBJID, STEXT, SOBID FROM SAP_PMS.ZHCM_ITF_PODR
        ');                                                                                                            //создание строки запрос списка Компаний
        oci_execute($stid);                                                                                             //выполнение запроса на строне оракл и получение самих данных

        $i = 0;                                                                                                           //индекс
//        $oracle_comp = array();                                                                                       //массив строк - должностей
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS))                                        //пробегаемся по массиву строк компании
        {
            $j = 0;                                                                                                        //индекс
            $oracle_comp = array();                                                                                   //перегоняемый массив поразделений по сути нам нуежн из запроса самый верхний и самый нижний элемент

            foreach ($row as $item)                                                                                     //пробегаемся по массиву строк
            {
                if ($item !== null and $item != "" and $item != " " and $item != "  ")                                                                                      //проверка на нулевую часть, если не ноль, то писать
                    $oracle_comp[$j] = $item;
                else                                                                                                    //если ноль, то писать -
                    $oracle_comp[$j] = "-1";
                $j++;
            }
//[0] - код компании [1]-название компании [2] - код вышестоящей компании
            $company = new Company();                                                                                    //создаем модель
            $company->id = (int)$oracle_comp[0];                                                                    //запись айди
            $company->title = (string)$oracle_comp[1];                                                             //запись тайтла
            if ((int)$oracle_comp[2] != -1)
                $company->upper_company_id = (int)$oracle_comp[2];                                                                            //айдишник воркутаугоь 201 - его и вписываем по умолчанию
            else
                $company->upper_company_id = 201;
            if (!$company->save()) {                                                                                    //запись все строки в целом, если ошибка, то пишем ее и идем дальше
                $error[] = "||>>--Company: ID компании" . (int)$oracle_comp[0] . "-ID вышестоящ компании" . (int)$oracle_comp[2] . "-Дамп ошибки:" . var_dump($company->errors) . "||--<<";
            } else {
                $shift_mine = new ShiftMine();                                                        //создаем привязку компании к режиму работ - берем по умолчанию режим 4 смены
                $shift_mine->date_time = date("Y-m-d H:i:s");                                        //текущее дата и время
                $shift_mine->plan_shift_id = 5;                                                             //режим работы ключ 5( 4 сменка)
                $shift_mine->company_id = $company->id;                                          //ключ связки департамента и компании
                if (!$shift_mine->save())                                                                                        //запись все строки в целом, если ошибка, то пишем ее и идем дальше
                    $error[] = "||>>--ShiftMine: ID компании" . (int)$oracle_comp[0] . "-ID вышестоящ компании" . (int)$oracle_comp[2] . "-Дамп ошибки:" . var_dump($company->errors) . "||--<<";
            }
            $comp[$i] = array($oracle_comp);                                                                              //заполняем массив строк должностей, для того, чтобы передать во фронт
            $i++;
        }
        //gc_collect_cycles();
        //echo "----".(memory_get_peak_usage()/1024/1024)."----";
        $varible_array[3] = $comp;                                                                                        //перегоняем массив значений в окончательный массив для передачи во фронт энд
        $result = array("varibleArray" => $varible_array, "errorArray" => $error);                                            //дополняем окончательный массив массивом ошибок
        Yii::$app->response->format = Response::FORMAT_JSON;                                                   //вывод информации на страницу фронт энда
        Yii::$app->response->data = $result;

    }


    //метод тестирования соединения с сервером Oracle
    public function actionSinhroOracleTestConnection()
    {

        //gc_collect_cycles();
        //echo "----".(memory_get_peak_usage()/1024/1024)."----";
        //ini_set('max_execution_time',5500);
        //ini_set('memory_limit',"1500M");
//        $post = Yii::$app->request->post();                                                                            //получение данных от ajax-запроса
        $error = array();                                                                                                 //массив ошибок
        $varible_array = array();                                                                                        //массив переменных, для возврата на фронт
        $conn_oracle = oci_connect('strata', 'strata_psw', '10.36.22.67:1521/komstu01', 'AL32UTF8');        //подключение к оракаловскому серверу на шахте Комсольской
        if (!$conn_oracle) {                                                                                            //проверка наличия подключения с сервером оракл
            $varible_array[0] = "connection=False;";                                                                        //параметр соединения к базе данных если коннекшена
            $varible_array[1] = "errors=yes";                                                                             //параметр наличия ошибки
            $error = oci_error();                                                                                       //заполнение массива ошибок в случае ее наступления
            trigger_error(htmlentities($error['message'], ENT_QUOTES), E_USER_ERROR);                //отправка данных на фронт мимо всех проверок
        } else {
            $varible_array[0] = "Connection=True";                                                                        //параметр успешного соединения
            $varible_array[1] = "errors=no";                                                                            //параметр отсутствия ошибок при соединении
        }
        $varible_array[2] = "controllers=Arraycontroller/actionSinhroOracleComp";                                         //параметр из какого контролера вызван
        //echo (string) date("Ymd");                                                                                    //проверка формирования верности даты
        $stringSQLper = '
              SELECT PERNR, STELL, OBJID, NACHN, VORNA, MIDNM, BEGDA, ENDDA
              FROM SAP_PMS.ZHCM_ITF_PERSN
              WHERE (ZHCM_ITF_PERSN.BEGDA<' . (string)date("Ymd") . ' AND ZHCM_ITF_PERSN.ENDDA>' . (string)date("Ymd") . ')';
        //echo $stringSQLper;
        //echo nl2br("/n");
        $stid = oci_parse($conn_oracle, $stringSQLper);                                                                                                            //создание строки запрос списка Компаний
        oci_execute($stid);                                                                                             //выполнение запроса на строне оракл и получение самих данных

        $i = 0;                                                                                                           //индекс
        $pers = array();
//        $oracle_pers = array();                                                                                       //массив строк - должностей
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS))                                        //пробегаемся по массиву строк компании
        {
            $j = 0;                                                                                                        //индекс
            $oracle_pers = array();                                                                                   //перегоняемый массив поразделений по сути нам нуежн из запроса самый верхний и самый нижний элемент

            foreach ($row as $item)                                                                                     //пробегаемся по массиву строк
            {
                if ($item !== null and $item != "" and $item != " ")                                                                                      //проверка на нулевую часть, если не ноль, то писать
                    $oracle_pers[$j] = $item;
                else                                                                                                    //если ноль, то писать -
                    $oracle_pers[$j] = "-1";
                $j++;
            }
            $pers[$i] = array($oracle_pers);                                                                              //заполняем массив строк должностей, для того, чтобы передать во фронт
            $i++;
        }
        $varible_array[3] = $pers;                                                                                        //перегоняем массив значений в окончательный массив для передачи во фронт энд
        $result = array("varibleArray" => $varible_array, "errorArray" => $error);
        //echo "----".(memory_get_peak_usage()/1024/1024)."----";
        //дополняем окончательный массив массивом ошибок
        Yii::$app->response->format = Response::FORMAT_JSON;                                                   //вывод информации на страницу фронт энда
        Yii::$app->response->data = $result;
    }

    //синхронизация справочников Oracle Компаний
    //метод нужен для выкачивания справочника (компаний) из оракл в базу майскуль
    //делает сложный вложенный запрос к оракал где берутся все подразделения которые больше >1000000 и у тех у кого SOBID пустой
    public function actionSinhroOracleCompReserv()
    {
//        $post = Yii::$app->request->post();                                                                            //получение данных от ajax-запроса
        $error = array();                                                                                                 //массив ошибок
        $varible_array = array();                                                                                        //массив переменных, для возврата на фронт
        $conn_oracle = oci_connect('strata', 'strata_psw', '10.36.22.67:1521/komstu01', 'AL32UTF8');        //подключение к оракаловскому серверу на шахте Комсольской
        if (!$conn_oracle) {                                                                                            //проверка наличия подключения с сервером оракл
            $varible_array[0] = "connection=False;";                                                                        //параметр соединения к базе данных если коннекшена
            $varible_array[1] = "errors=yes";                                                                             //параметр наличия ошибки
            $error = oci_error();                                                                                       //заполнение массива ошибок в случае ее наступления
            trigger_error(htmlentities($error['message'], ENT_QUOTES), E_USER_ERROR);                //отправка данных на фронт мимо всех проверок
        } else {
            $varible_array[0] = "Connection=True";                                                                        //параметр успешного соединения
            $varible_array[1] = "errors=no";                                                                            //параметр отсутствия ошибок при соединении
        }
        $varible_array[2] = "controllers=Arraycontroller/actionSinhroOracleComp";                                         //параметр из какого контролера вызван
        $stid = oci_parse($conn_oracle, 'SELECT OBJID, STEXT, SOBID FROM SAP_PMS.ZHCM_ITF_PODR');                                                                                                            //создание строки запрос списка Компаний
        oci_execute($stid);                                                                                             //выполнение запроса на строне оракл и получение самих данных

        $i = 0;                                                                                                           //индекс
//        $oracle_comp = array();                                                                                       //массив строк - должностей
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS))                                        //пробегаемся по массиву строк компании
        {
            $j = 0;                                                                                                        //индекс
            $oracle_comp = array();                                                                                   //перегоняемый массив поразделений по сути нам нуежн из запроса самый верхний и самый нижний элемент

            foreach ($row as $item)                                                                                     //пробегаемся по массиву строк
            {
                if ($item !== null and $item != "" and $item != " ")                                                                                      //проверка на нулевую часть, если не ноль, то писать
                    $oracle_comp[$j] = $item;
                else                                                                                                    //если ноль, то писать -
                    $oracle_comp[$j] = "-";
                $j++;
            }

            if ((int)$oracle_comp[2] < 1) {
                $company = new Company();                                                                                    //создаем модель
                $company->id = (int)$oracle_comp[0];                                                                    //запись айди
                $company->title = (string)$oracle_comp[1];                                                             //запись тайтла
                $company->upper_company_id = 201;                                                                            //айдишник воркутаугоь 201 - его и вписываем по умолчанию
                if (!$company->save()) {                                                                                    //запись все строки в целом, если ошибка, то пишем ее и идем дальше
                    $error[] = "||>>--Company: ID компании" . (int)$oracle_comp[0] . "-ID вышестоящ компании" . (int)$oracle_comp[2] . "-Дамп ошибки:" . var_dump($company->errors) . "||--<<";
                } else {
                    $shift_mine = new ShiftMine();                                                        //создаем привязку компании к режиму работ - берем по умолчанию режим 4 смены
                    $shift_mine->date_time = date("Y-m-d H:i:s");                                        //текущее дата и время
                    $shift_mine->plan_shift_id = 5;                                                             //режим работы ключ 5( 4 сменка)
                    $shift_mine->company_id = $company->id;                                          //ключ связки департамента и компании
                    if (!$shift_mine->save())                                                                                        //запись все строки в целом, если ошибка, то пишем ее и идем дальше
                        $error[] = "||>>--ShiftMine: ID компании" . (int)$oracle_comp[0] . "-ID вышестоящ компании" . (int)$oracle_comp[2] . "-Дамп ошибки:" . var_dump($company->errors) . "||--<<";
                }
                $comp[$i] = array($oracle_comp);                                                                              //заполняем массив строк должностей, для того, чтобы передать во фронт
                $i++;
            }

        }

        $varible_array[3] = $comp;                                                                                      //перегоняем массив значений в окончательный массив для передачи во фронт энд
        $result = array("varibleArray" => $varible_array, "errorArray" => $error);                                            //дополняем окончательный массив массивом ошибок

        //вывод информации на страницу фронт энда
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;

    }

    //сохранение функций работника
    public function actionAddWorkerFunction($worker_object_id, $function_id)
    {
        //gc_collect_cycles();
//        ini_set('max_execution_time', 5500);
//        ini_set('memory_limit', "1500M");
        $debug_flag = 0;
        if ($debug_flag == 1) echo nl2br("----зашел в функцию создания функции сопряжения  =" . $worker_object_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        if ($worker_function = WorkerFunction::find()->where(['worker_object_id' => $worker_object_id, 'function_id' => $function_id])->one()) {
            return $worker_function->id;
        } else {
            $worker_function_new = new WorkerFunction();
            $worker_function_new->worker_object_id = $worker_object_id;                                                                      //айди сопряжения
            $worker_function_new->function_id = $function_id;                                                                  //айди функции
            //статус значения

            if ($worker_function_new->save())
                return $worker_function_new->id;
            else
                return -1;
        }
    }

    /**
     * Функция приязки лампы к сотруднику(Добавление данных в worker_parameter_sensor)
     * @param $worker_parameter_id
     * @param $sensor_id
     * @return int
     * Created by: Одилов О.У. on 31.10.2018 11:03
     */
    public static function actionAddWorkerParameterSensor($worker_parameter_id, $sensor_id, $type_relation_sensor)
    {
        $worker_parameter_sensor = new WorkerParameterSensor();
        $worker_parameter_sensor->worker_parameter_id = $worker_parameter_id;
        $worker_parameter_sensor->sensor_id = $sensor_id;
        $worker_parameter_sensor->date_time = date('Y-m-d H:i:s', strtotime('-1 second'));
        $worker_parameter_sensor->type_relation_sensor = $type_relation_sensor;
        if (!$worker_parameter_sensor->save()) {
            return (-1);
        }
        return 1;

    }

    //создание параметра конкретного воркера
    public function actionAddWorkerParameter($worker_object_id, $parameter_id, $parameter_type_id)
    {
//        ini_set('max_execution_time', 5500);
//        ini_set('memory_limit', "1500M");
        $debug_flag = 0;

        if ($debug_flag == 1)
            echo nl2br("----зашел в функцию создания параметров worker'a  =" . $worker_object_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        if (
            $worker_parameter = WorkerParameter::find()->where
            (
                [
                    'worker_object_id' => $worker_object_id,
                    'parameter_id' => $parameter_id,
                    'parameter_type_id' => $parameter_type_id
                ]
            )->one()
        ) {
            return $worker_parameter->id;
        } else {
            $worker_parameter_new = new WorkerParameter();
            $worker_parameter_new->worker_object_id = $worker_object_id;                                                                 //айди воркер обджекта
            $worker_parameter_new->parameter_id = $parameter_id;                                                           //айди параметра
            $worker_parameter_new->parameter_type_id = $parameter_type_id;                                                 //айди типа параметра

            if ($worker_parameter_new->save())
                return $worker_parameter_new->id;
            else
                return (-1); //"Ошибка сохранения значения параметра сопряжения" . $worker_id->id;
        }
    }

    /**
     * ВНИМАНИЕ!!!!!!!
     *      НЕ ЗАПУСТИТЬ МЕТОД БЕЗ ПЕРЕКЛЮЧЕНИЕ СОЕДЕНЕНИЯ К ТЕСТОВОЙ БД
     *  Модуль синхронизации справочников SAP (должгости) c нашими
     * Данные синхронизируются по успловиям
     * Если id не ровны и название такого нет в БД - добавляются
     * Если id ровны и названия отличаются, то update
     * @throws \yii\db\Exception
     */
    public function actionSynJob()
    {
        $new_positions_to_amicum = array();                                                                             // пустой массив для хранения новых значений полученных с SAP
        $connection_sap = Yii::$app->db;
        $sap_positions = $connection_sap->createCommand('SELECT STELL,STEXT FROM sap_jobs ORDER BY STELL ASC')->queryAll(); // получаем справочники SAP (job)
        $positions_in_amicum = (new Query())// получаем наши справочники (positions)
        ->select([
            'id',
            'title'
        ])
            ->from('position')
            ->orderBy(['id' => SORT_ASC])
            ->all();
        $index = 0;
        foreach ($sap_positions as $position_in_sap)                                                                    // каждый position SAP проверяем с нашими справочниками
        {
            $position_in_sap_id = $position_in_sap['STELL'];
            $position_in_sap_title = $position_in_sap['STEXT'];
            $found_position_array_index = array_search($position_in_sap_id, array_column($positions_in_amicum, 'id')); // находим такой id в амикум
            $found_position_array_title = array_search($position_in_sap_title, array_column($positions_in_amicum, 'title')); // находим такое название в амикум
            if ($found_position_array_index)                                                                            // если такой id найден, то сделаем проверку
            {
                if ($positions_in_amicum[$found_position_array_index]['id'] === $position_in_sap_id
                    and $positions_in_amicum[$found_position_array_index]['title'] !== $position_in_sap_title
                    and preg_match("/[\S]/", $position_in_sap_title))                                           // если id ровны, но названия отличаются, то изменить
                {
                    $new_positions_to_amicum['update'][$index]['position_id'] = $positions_in_amicum[$found_position_array_index]['id'];
                    $new_positions_to_amicum['update'][$index]['position_title'] = $position_in_sap['STEXT'];
                }
            } else if (!$found_position_array_title && !$found_position_array_index) {
                if (preg_match("/[\S]/", $position_in_sap_title) and $position_in_sap_id !== null and
                    $position_in_sap_id != 0) {
                    $new_positions_to_amicum['add'][$index]['position_id'] = (int)$position_in_sap['STELL'];
                    $new_positions_to_amicum['add'][$index]['position_title'] = $position_in_sap['STEXT'];
                }
            }
            $index++;
        }
        if (!empty($new_positions_to_amicum))                                                                            // если есть новые изменения или данные, то добавим или же редактируем
        {
            if (!empty($new_positions_to_amicum['add']))                                                                // Если есть что добавлять, то есть если есть новые должности в справочнике SAP
            {
                $positions_for_insert = array();
                foreach ($new_positions_to_amicum['add'] as $item)                                                      // то в цикле добавляем
                {
                    $position_id = (int)$item['position_id'];
                    $position_title = $item['position_title'];
                    $positions_for_insert[] = "($position_id, '$position_title')";                                       // добавим в один массив, чтоб закрузки к БД не было в одном индексе будет так: [0] => (id, title), (id2, title2)
                }
                $positions_from_SAP = implode(",", $positions_for_insert);
                $sql = "INSERT INTO position (id, title) VALUES " . $positions_from_SAP . " ON DUPLICATE KEY UPDATE title = VALUES(title)";
                $result = Yii::$app->db->createCommand($sql)->execute();
                if ($result) {
                    echo 'Новые данные были синхронизированы<br>';
                    echo 'Новые должности со справочника SAP:<br>';
                    echo '<pre>';
                    print_r($new_positions_to_amicum['add']);
                    echo '</pre>';
                } else {
                    echo 'ошибка добавления данных';
                }
            }
            if (!empty($new_positions_to_amicum['update']))                                                             // Если есть что добавлять, то есть если есть ли измененые должности в справочнике SAP
            {
                $errors = array();
                foreach ($new_positions_to_amicum['update'] as $position)                                               // то в цикле добавляем
                {
                    $sql = "UPDATE position SET title = '" . $position['position_title'] . "' WHERE id = " . $position['position_id'];
                    $result = Yii::$app->db->createCommand($sql)->execute();
                    if (!$result) {
                        $errors[] = "Ошибка редактирования должности: id = " . $position['position_id'] . ", title = " . $position['position_title'];
                    }
                }
                if (empty($errors)) {
                    echo "Данные успешно синхронизированы. Список измененых должностей<br>";
                    echo '<pre>';
                    print_r($new_positions_to_amicum['update']);
                    echo '</pre>';
                } else {
                    echo '<pre>';
                    print_r($errors);
                    echo '</pre>';
                }
            }
        } else {
            echo 'Нет новых данных со правочников SAP';
        }
    }

    /**
     * ВНИМАНИЕ!!!!!!!
     *      НЕ ЗАПУСТИТЬ МЕТОД БЕЗ ПЕРЕКЛЮЧЕНИЕ СОЕДЕНЕНИЯ К ТЕСТОВОЙ БД
     *  Модуль синхронизации справочников SAP (подразделения) c нашими
     * Данные синхронизируются по успловиям
     * Если id не ровны и название такого нет в БД - добавляются
     * Если id ровны и названия отличаются, то update
     * @throws \yii\db\Exception
     */
    public function actionSynDepartments()
    {
//        $errors = array();
        $amicum_table = 'department';
        $SAP_table = 'sap_departments';
        $new_departments_to_amicum = array();                                                                             // пустой массив для хранения новых значений полученных с SAP
        $connection_sap = Yii::$app->db;                                                                         // Подключение к БД SAP
        $sap_departments = $connection_sap->createCommand('SELECT OBJID,STEXT FROM ' . $SAP_table . ' ORDER BY OBJID ASC')->queryAll();                                                                          // выполняем запрос
//        echo '<pre>';
//        print_r($sap_departments);
//        echo '</pre>';
//        echo '-------------------------------------------------------------------------------------------------------';
        $departments_in_amicum = (new Query())// получаем наши справочники (positions)
        ->select([
            'id',
            'title'
        ])
            ->from($amicum_table)
            ->orderBy(['id' => SORT_ASC])
            //->limit(5)
            ->all();
//        echo '<pre>';
//        print_r($departments_in_amicum);
//        echo '</pre>';
//        echo '-------------------------------------------------------------------------------------------------------';
        $index = 0;
        foreach ($sap_departments as $department_in_sap)                                                                // каждый department SAP проверяем с нашими справочниками
        {
            $department_in_sap_id = $department_in_sap['OBJID'];
            $department_in_sap_title = $department_in_sap['STEXT'];
            $found_department_array_index = array_search($department_in_sap_id, array_column($departments_in_amicum, 'id')); // находим такой id в амикум
            $found_department_array_title = array_search($department_in_sap_title, array_column($departments_in_amicum, 'title')); // находим такое название в амикум
            if ($found_department_array_index)                                                                            // если такой id найден, то сделаем проверку
            {
                if ($departments_in_amicum[$found_department_array_index]['id'] === $department_in_sap_id
                    and $departments_in_amicum[$found_department_array_index]['title'] !== $department_in_sap_title
                    and preg_match("/[\S]/", $department_in_sap_title))                                           // если id ровны, но названия отличаются, то изменить
                {
                    $new_departments_to_amicum['update'][$index]['department_id'] = $departments_in_amicum[$found_department_array_index]['id'];
                    $new_departments_to_amicum['update'][$index]['department_title'] = $department_in_sap['STEXT'];
                }
            } else if (!$found_department_array_title && !$found_department_array_index)                                  //если навзание и id не найдены, то добавляем
            {
                if (preg_match("/[\S]/", $department_in_sap_title) and
                    $department_in_sap_id !== null && $department_in_sap_id != 0)                                       // если полученные данные не пустые
                {
                    $new_departments_to_amicum['add'][$index]['department_id'] = (int)$department_in_sap['OBJID'];
                    $new_departments_to_amicum['add'][$index]['department_title'] = $department_in_sap['STEXT'];
                }
            }
            $index++;
        }
//       echo '<pre>';
//       print_r($new_departments_to_amicum);
//       echo '</pre>';

        if (!empty($new_departments_to_amicum))                                                                            // если есть новые изменения или данные, то добавим или же редактируем
        {
            if (!empty($new_departments_to_amicum['add']))                                                                // Если есть что добавлять, то есть если есть новые должности в справочнике SAP
            {
                $departments_for_insert = array();
                foreach ($new_departments_to_amicum['add'] as $item)                                                      // то в цикле добавляем
                {
                    $department_id = (int)$item['department_id'];
                    $department_title = $item['department_title'];
                    $departments_for_insert[] = "($department_id, '$department_title')";                                       // добавим в один массив, чтоб закрузки к БД не было в одном индексе будет так: [0] => (id, title), (id2, title2)
                }
                $departments_from_SAP = implode(",", $departments_for_insert);
                $sql = "INSERT INTO $amicum_table (id, title) VALUES " . $departments_from_SAP . " ON DUPLICATE KEY UPDATE title = VALUES(title)";
                $result = Yii::$app->db->createCommand($sql)->execute();

                if ($result) {
                    echo 'Новые данные были синхронизированы<br>';
                    echo 'Новые подразделения со справочника SAP:<br>';
                    echo '<pre>';
                    print_r($new_departments_to_amicum['add']);
                    echo '</pre>';
                } else {
                    echo 'ошибка добавления данных';
                }
            }
            if (!empty($new_departments_to_amicum['update']))                                                             // Если есть что добавлять, то есть если есть ли измененые должности в справочнике SAP
            {
                $errors = array();
                foreach ($new_departments_to_amicum['update'] as $department)                                               // то в цикле добавляем
                {
                    $sql = "UPDATE $amicum_table SET title = '" . $department['department_title'] . "' WHERE id = " . $department['department_id'];
                    $result = Yii::$app->db->createCommand($sql)->execute();
                    if (!$result) {
                        $errors[] = "Ошибка редактирования должности: id = " . $department['department_id'] . ", title = " . $department['department_title'];
                    }
                }
                if (empty($errors)) {
                    echo "Данные успешно синхронизированы. Список измененых подразделений<br>";
                    echo '<pre>';
                    print_r($new_departments_to_amicum['update']);
                    echo '</pre>';
                } else {
                    echo '<pre>';
                    print_r($errors);
                    echo '</pre>';
                }
            }
        } else {
            echo 'Нет новых данных со правочников SAP';
        }
    }

    /**
     * Модуль по синхронизации справочника сотрудников SAP с нашими
     * @throws \yii\db\Exception
     * @todo необходмо добавить пол для персонала. Будет 2 допол... полей: GESC. Если значение 1, то мужик, если 2, то женщина
     */
    public function actionSynEmployee()
    {
//        ini_set('max_execution_time', 5500);
//        ini_set('memory_limit', "1500M");
//        $flag_syn = false;
        $flag_syn_fio = false;
        $flag_syn_position = false;
        $flag_syn_department = false;
        $flag_syn_dismissed_workers = false;
        $flag_syn_new_workers = true;
        $report_list = array();
        $employee_table = 'employee';
        $worker_table = 'worker';
        $shift_worker_table = 'shift_worker';
        $success = array();
        $errors = array();
        /************************************ СИНХРОНИЗАЦИЯ ФИО СОТРУДНИКОВ  SAP   ****************************************/
        $updates_fio_sap_employees = (new Query())// получаем id сотрудников SAP, у которых SAP поменялось ФИО
        ->select([
            'amicum_employee_id',
            'last_name',
            'first_name',
            'patronymic'
        ])
            ->from('view_updates_sap_employee_fio')
            ->all();
        $report_list['updates_sap_employee_fio']['title'] = "Список сотрудников у которых ФИО поменялось";
        $report_list['updates_sap_employee_fio']['list'] = $updates_fio_sap_employees;
        if ($updates_fio_sap_employees)                                                                                  // если у какого-то сотрудника SAP поменялось ФИО, то обновлюяем в нашей БД
        {
            if ($flag_syn_fio === true) {
                foreach ($updates_fio_sap_employees as $sap_employee)                                                           // Обновляем ФФИо каждого сотрудника SAP, у которых Фио поменялось по worker_id
                {
                    $last_name = $sap_employee['last_name'];
                    $first_name = $sap_employee['first_name'];
                    $patronymic = $sap_employee['patronymic'];
                    $employee_id = $sap_employee['amicum_employee_id'];
                    $sql = "UPDATE $employee_table set
                    last_name = '$last_name',
                    first_name = '$first_name',
                    patronymic = '$patronymic'
                 WHERe id = $employee_id";
                    $result = Yii::$app->db->createCommand($sql)->execute();
                    if ($result) {
                        $success[] = 'Данные (ФИО) сотрудника с id = ' . $employee_id . ' редактированы';
                    } else {
                        $errors[] = 'Ошибка редактирования (ФИО) данных сотрудника с id = ' . $employee_id . $result;
                    }
                }
            }
        } else {
            $success[] = "У сотрудников SAP ФИО не менляось";
        }
        /********************  РЕДАКТИРОВАНИЕ ПОДРАЗДЕЛЕНИИ СОТРУДНИКОВ (ТОЛЬКО У ТЕХ, У кого поменлялось подразделение)**/

        $updates_departments_sap_employees = (new Query())// получаем id сотрудников(которые работают) SAP, у которых SAP поменялось подрзделение
        ->select([
            'amicum_worker_id',
            'department_id'
        ])
            ->from('view_updates_sap_employee_department')
            ->all();
        $report_list['updates_sap_employee_department']['title'] = "Список сотрудников у которых подразделение поменялось";
        $report_list['updates_sap_employee_department']['list'] = $updates_departments_sap_employees;
        if ($updates_departments_sap_employees)                                                                          // проверяем, поменялось ли id подразделения сотрудников SAP
        {
            if ($flag_syn_department === true) {
                foreach ($updates_departments_sap_employees as $sap_employee_dep)                                           //для каждого сотрудника(которые работают) SAP, у которого поменяллось id подразделения, редактируем  id подразделения у нас в БД
                {
                    $new_dep = 801;// Прочее
                    $worker_id = (int)$sap_employee_dep['amicum_worker_id'];
                    $department_id = (int)$sap_employee_dep['department_id'];
                    $result = Yii::$app->db->createCommand("SELECT id FROM department where id = $department_id")->execute();   // проверяем, существует ли такое подразделение у нас в БД
                    if ($result)                                                                                             // если есть такое подразделение по id у нас в БД, то ничего не добавим, если нету то установим id = 1 (прочее)
                    {
                        $new_dep = $department_id;
                    }
                    $sql = "UPDATE $worker_table
                        SET company_department_id = $new_dep
                        WHERE id = $worker_id";
                    $update_sap_employee_depart = Yii::$app->db->createCommand($sql)->execute();
                    $new_dep = 801;// Прочее
                    if ($update_sap_employee_depart) {
                        $success[] = 'Данные (Подразделение) сотрудника с id = ' . $worker_id . ' редактированы. ID подразделения этого сотрудника = ' . $new_dep;
                    } else {
                        $errors[] = 'Ошибка редактирования (Подразделение) данных сотрудника с id = ' . $worker_id . " 
                                        Данные сотрудника не обновлены либо нет изменений. Возможно в справочнике подразделений Amicum нет такого подразделения.";
                    }
                }
            }
        } else {
            $success[] = "У сотрудников SAP подразделение не менляось";
        }

        /***************** РЕДАКТИРОВАНИЕ ДОЛЖНОСТИ СОТРУДНИКОВ (ТОЛЬКО У ТЕХ, У кого поменлялась должность) ***************/

        $updates_positions_sap_employees = (new Query())// получаем id сотрудников(которые работают) SAP, у которых SAP поменялась должность
        ->select([
            'amicum_worker_id',
            'position_id'
        ])
            ->from('view_updates_sap_employee_position')
            ->all();
        $report_list['updates_sap_employee_position']['title'] = "Список сотрудников у которых должность поменялась";
        $report_list['updates_sap_employee_position']['list'] = $updates_positions_sap_employees;
        if ($updates_positions_sap_employees)                                                                          // проверяем, поменялось ли id подразделения сотрудников SAP
        {
            if ($flag_syn_position === true) {
                foreach ($updates_positions_sap_employees as $sap_employee_position)                                           //для каждого сотрудника SAP, у которого поменяллось id подразделения, редактируем  id подразделения у нас в БД
                {
                    $new_position = 1;  // прочее
                    $worker_id = (int)$sap_employee_position['amicum_worker_id'];
                    $position_id = (int)$sap_employee_position['position_id'];
                    $result = Yii::$app->db->createCommand("SELECT id FROM position where id = $position_id")->execute();   // проверяем, существует ли такое подразделение у нас в БД
                    if ($result)                                                                                             // если есть такое подразделение по id у нас в БД, то ничего не добавим, если нету то установим id = 1 (прочее)
                    {
                        $new_position = $position_id;
                    }
                    $sql = "UPDATE $worker_table
                        SET position_id = $new_position
                        WHERE id = $worker_id";
                    $update_sap_employee_position = Yii::$app->db->createCommand($sql)->execute();
                    $new_position = 1;  // прочее
                    if ($update_sap_employee_position) {
                        $success[] = 'Данные (Должность) сотрудника с id = ' . $worker_id . ' редактированы. ID должности этого сотрудника = ' . $new_position;
                    } else {
                        $errors[] = 'Ошибка редактирования (Должность) данных сотрудника с id = ' . $worker_id . '. 
                                   Данные сотрудника не обновлены либо нет изменений. Возможно в справочнике должностей Amicum нет такой должности.';
                    }
                }
            }
        } else {
            $success[] = "У сотрудников SAP должность не менлялась";
        }
        /******* РЕДАКТИРОВАНИЕ ДАННЫХ(ТОЛЬКО ДАТА ЗАВЕРШЕНИЯ РАБОТЫ) УВОЛЕННЫХ СОТРУДНИКОВ SAP В НАШЕМ СПРАВОЧНИКЕ  ******/
        $dismissed_sap_workers = (new Query())// получаем список сотрудников справочника SAP которые уволились
        ->select([
            'worker_id',
            'date_end'
//            , 'date_start'
        ])
            ->from('view_sap_and_amicum_dismissed_workers_date')
            ->all();
        $report_list['sap_and_amicum_dismissed_workers_date']['title'] = "Список увольненых сотрудников";
        $report_list['sap_and_amicum_dismissed_workers_date']['list'] = $dismissed_sap_workers;
        if ($dismissed_sap_workers)                                                                                          // если список не пустой, то есть если кто-то уволилился, то  редактируем дату завершения конкретного работника в нашем справочнике
        {
            if ($flag_syn_dismissed_workers === true) {
                foreach ($dismissed_sap_workers as $dismissed_worker) {
                    $dismissed_worker_id = $dismissed_worker['worker_id']; // id уволенного сотрудника
                    $dismissed_worker_date_start = $dismissed_worker['date_start'];             // дата начало работы
                    $dismissed_worker_date_end = $dismissed_worker['date_end'];             // дата увольнения
                    $sql_update_dismiss_date = "UPDATE $worker_table
                                        SET
//                                          date_start = '$dismissed_worker_date_start',
                                          date_end = '$dismissed_worker_date_end'
                                        WHERE id = $dismissed_worker_id";
                    $update_sap_employee_dismiss_date = Yii::$app->db->createCommand($sql_update_dismiss_date)->execute();
                    if ($update_sap_employee_dismiss_date)                                                                       //  если данные обновлены, то выводим сообщение
                    {
                        $success[] = 'Данные (Дата увольнения) сотрудника с id = ' . $dismissed_worker_id . ' редактированы';
                    } else {                                                                                                       // если данные не обновились, то выводим ошибку для конкретного работника
                        $errors[] = 'Ошибка редактирования (Дата увольнения) данных сотрудника с id = ' . $dismissed_worker_id . '. 
                                Данные сотрудника не обновлены либо нет изменений.';
                    }
                }
            }
        } else {
            $success[] = "В справочнике сотрудников SAP нет уволенных сотрудников";
        }
        /*************************** ДОБАВЛЕНИЕ НОВЫХ СОТРУДНИКОВ SAP В НАШУ БД *******************************************/
        $new_employee_list = array();
        $new_worker_list = array();
        $shift_workers = array();
        $sap_new_employees = (new Query())// ПОЛУЧАЕМ НОВЫХ СОТРУДНИКОВ ИЗ СПРАВОЧНИКА SAP
        ->select([
            'sap_employee_worker_id',
            'sap_employee_position_id',
            'sap_employee_company_department_id',
            'sap_employee_tabel_number',
            'sap_employee_date_start',
            'sap_employee_date_end',
            'sap_employee_last_name',
            'sap_employee_first_name',
            'sap_employee_patronymic',
        ])
            ->from('view_sap_new_employees')
            ->all();
        $report_list['sap_new_employees']['title'] = "Новые сотрудники со справочника SAP";
        $report_list['sap_new_employees'][] = $sap_new_employees;
        if ($sap_new_employees) {
            if ($flag_syn_new_workers === true) {
//                $i = -1;
                $sap_new_employees = $this->GroupNewEmployeesArrayById($sap_new_employees, 'sap_employee_worker_id');
//                $worker_ids_for_shift = array();
                $employee_ids_for_shift = array();
                $current_date_time = date("Y-m-d H:i:s");
                $current_date = date("Y-m-d");
                foreach ($sap_new_employees as $new_employee) {
                    $employee_id = $new_employee['sap_employee_worker_id'];
                    $employee_ids_for_shift[] = $employee_id;                                                       // employee_id по которым потом находим worker_id для создания график работы
                    $position_id = $new_employee['sap_employee_position_id'];
                    $company_department_id = $new_employee['sap_employee_company_department_id'];
                    $tabel_number = $new_employee['sap_employee_tabel_number'];
                    $date_start = $new_employee['sap_employee_date_start'];
                    $date_end = $new_employee['sap_employee_date_end'];
                    $last_name = $new_employee['sap_employee_last_name'];
                    $first_name = $new_employee['sap_employee_first_name'];
                    $patronymic = $new_employee['sap_employee_patronymic'];

                    $find_department = Yii::$app->db->createCommand("SELECT id FROM department where id = $company_department_id")->execute();   // проверяем, существует ли такое подразделение у нас в БД
                    if ($find_department === 0)                                                                              // если нет такого подразделения по id у нас в БД, то установим id = 1 (прочее)
                    {
                        $company_department_id = 801; // id  подраздения, то есть "Прочее"
                    }
                    $find_position = Yii::$app->db->createCommand("SELECT id FROM position where id = $company_department_id")->execute();   // проверяем, существует ли такая должность у нас в справочниках
                    if ($find_position === 0)                                                                              // если нет такой должности по id у нас в БД, то установим id = 1 (прочее)
                    {
                        $position_id = 1; // id  должности, то есть "Прочее"
                    }
                    $new_worker_list[] = "($employee_id, $position_id, $company_department_id, '$tabel_number', '$date_start', '$date_end')";
                    $new_employee_list[$employee_id] = "($employee_id, '$last_name', '$first_name', '$patronymic', 'М', '$current_date')";
                }
//                $new_employee_list = array_values($new_employee_list);
                $sap_new_employees_datas = implode(",", $new_employee_list);
                $sap_new_workers_datas = implode(",", $new_worker_list);
//                $shift_workers_datas = implode(",", $shift_workers);
                /*************************  ДОБАВЛЕНИЕ ФИО ДЛЯ ДОБАВЛЕННЫХ РАБОТНИКОВ   *******************************/
                $sql_employee_insert = "INSERT INTO $employee_table (id, last_name, first_name, patronymic, gender, birthdate) VALUES $sap_new_employees_datas ON DUPLICATE KEY UPDATE  last_name=VALUES(last_name), first_name=VALUES(first_name), patronymic=VALUES(patronymic), gender=VALUES(gender), birthdate=VALUES(birthdate)";
                $employee_insert = Yii::$app->db->createCommand($sql_employee_insert)->execute();
                if (!$employee_insert) {
                    $errors[] = 'Ошибка добавления данных о сотрудниках  в справочник сотрудников (Amicum), полученных из справочника SAP. Либо они есть и не добавлены';
                } else {
                    $success[] = 'Добавлены новые данные о сотрудниках в справочник сотрудников (Amicum), полученных из справочника SAP';
                }
                /*** ДОБАВЛЕНИЕ НОВЫХ WORKER ПОЛУЧУЧЕННЫХ ИЗ СПРАВОЧНИКА SAP В НАШ СПРАВОЧНИК сотрудников.  ******/
                $sql_workers_insert = "INSERT INTO $worker_table (employee_id, position_id, company_department_id, tabel_number, date_start, date_end) VALUES $sap_new_workers_datas ON DUPLICATE KEY UPDATE employee_id=VALUES(employee_id), position_id=VALUES(position_id), company_department_id=VALUES(company_department_id), tabel_number=VALUES(tabel_number), date_start=VALUES(date_start), date_end=VALUES(date_end)";
                $worker_insert = Yii::$app->db->createCommand($sql_workers_insert)->execute();
                if (!$worker_insert) {
                    $errors[] = 'Ошибка добавления новых сотрудников со справочника SAP в Amicum. Либо они есть и не добавлены';
                } else {
                    $success[] = 'Добавлены новые сотрудники со справочника SAP в Amicum';
                }
                /**************************** СОЗДАНИЕ ГАФИК РАБОТЫ ДЛЯ НОВЫХ ПОЛУЧЕННЫХ СОТРУДНИКОВ ******************/
                $employee_ids_string = implode(",", $employee_ids_for_shift);
                $new_workers_ids = Yii::$app->db->createCommand("SELECT id from worker where employee_id in ($employee_ids_string)")->queryAll();
                if ($new_workers_ids)                                                                              // если у добавленных сотрудников есть worker_id
                {
                    foreach ($new_workers_ids as $worker)                                                      // для каждого работника (worker_id) создаем график работы
                    {
                        $worker_id = $worker['id'];
                        $shift_workers[$worker_id] = "('$current_date_time',5,$worker_id)";
                    }
//                        echo '<pre>';
//                        print_r($new_workers_ids);
//                        echo '</pre>';
                    $shift_workers = implode(",", $shift_workers);
                    $sql_shift_worker = "INSERT INTO $shift_worker_table (date_time, plan_shift_id, worker_id) VALUES $shift_workers";  //  Добавим в БД график работы для каждого работника (worker)
                    $shift_worker_insert = Yii::$app->db->createCommand($sql_shift_worker)->execute();              // выполняем запрос
                    if (!$shift_worker_insert)                                                                       // если данные не были добавлены в БД, то выводим ошибку
                    {
                        $errors[] = 'Ошибка добавления график работы для новых сотрудников со справочника SAP в Amicum';
                    } else {
                        $success[] = 'Добавлен график работы для новых сотрудников со справочника SAP в Amicum';
                    }
                } else {
                    $errors[] = "Из-за того, что новые сотрудники не были добавлены, не удалось создать график работы";
                }
            }
        } else {
            $success[] = "Нет новых сотрудников в српавочнике сотрудников (SAP)";
        }
        $result = array('errors' => $errors, 'success' => $success, 'report' => $report_list);
        $this->CreateWriteFile($result, "syn-employees-report");
        echo '<pre>';
        print_r($result);
        echo '</pre>';
    }

    public function CreateWriteFile($text, $file_name)
    {
//        $file_exist = true;
        $dirname = 'Отчет синхронизации справочников SAP и AMICUM';
        $i = 1;
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . "/" . $dirname)) {
            mkdir($_SERVER['DOCUMENT_ROOT'] . "/" . $dirname, 0777, true);
        }
        $path = $file_name . " " . date("Ymd") . ".txt";
        $results = print_r($text, true);
        $file_path = $_SERVER['DOCUMENT_ROOT'] . "/" . $dirname . "/" . $path;
        do {
            if (file_exists($file_path)) {
                $file_path = $_SERVER['DOCUMENT_ROOT'] . "/" . $dirname . "/" . $file_name . " " . date("Ymd") . "-$i.txt";
                $i++;
                $file_exist = true;
            } else {
                $file_exist = false;

            }
        } while ($file_exist != false);
        $fp = fopen($file_path, "w");
        fwrite($fp, "Отчет синхронизации сотрудников\n");
        fclose($fp);
        $fp = fopen($file_path, "a");
        fwrite($fp, $results);
        fclose($fp);
    }

    /**
     * Метод для группировки массива по id
     * Метод берет id  как index и хранить в новом массиве значения
     * Если id повторяется, то массив с индексом id перезаписывается.
     * После группировки переиндексирует массив.
     * @param $array
     * @param $column_id_name
     * @return array
     */
    public function GroupNewEmployeesArrayById($array, $column_id_name)
    {
        $result_array = array();
        foreach ($array as $sap_employee) {
            $result_array[$sap_employee[$column_id_name]]['sap_employee_worker_id'] = (int)$sap_employee['sap_employee_worker_id'];
            $result_array[$sap_employee[$column_id_name]]['employee_id'] = (int)$sap_employee['sap_employee_worker_id'];
            $result_array[$sap_employee[$column_id_name]]['sap_employee_position_id'] = (int)$sap_employee['sap_employee_position_id'];
            $result_array[$sap_employee[$column_id_name]]['sap_employee_company_department_id'] = (int)$sap_employee['sap_employee_company_department_id'];
            $result_array[$sap_employee[$column_id_name]]['sap_employee_tabel_number'] = (int)$sap_employee['sap_employee_tabel_number'];
            $result_array[$sap_employee[$column_id_name]]['sap_employee_date_start'] = $sap_employee['sap_employee_date_start'];
            $result_array[$sap_employee[$column_id_name]]['sap_employee_date_end'] = $sap_employee['sap_employee_date_end'];
            $result_array[$sap_employee[$column_id_name]]['sap_employee_last_name'] = $sap_employee['sap_employee_last_name'];
            $result_array[$sap_employee[$column_id_name]]['sap_employee_first_name'] = $sap_employee['sap_employee_first_name'];
            $result_array[$sap_employee[$column_id_name]]['sap_employee_patronymic'] = $sap_employee['sap_employee_patronymic'];
        }
//        $result_array = array_values($result_array);
        return $result_array;
    }

    /**
     * Функция синхронизации справочника SAP (spr_businnes_unit - компании)
     */
    public function actionSynCompanies()
//    public function actionTest()
    {
        $errors = array();
        $amicum_table = 'company_1';
        $SAP_table = 'spr_business_unit';
        $new_company_to_amicum = array();                                                                             // пустой массив для хранения новых значений полученных с SAP
        $amicum_copmany_list = (new Query())
            ->select('id, title')
            ->from($amicum_table)
            ->orderBy(['id' => SORT_ASC])
            ->all();
        $sap_company_list = (new Query())
            ->select('id, title')
            ->from($SAP_table)
            ->orderBy(['id' => SORT_ASC])
            ->all();
        $index = 0;
        foreach ($sap_company_list as $sap_company)                                                                     // Ищем каждую компанию SAP в таблице company(amicum)
        {
            $sap_company_id = (int)$sap_company['id'];
            $sap_company_title = $sap_company['title'];
            $found_company_array_index = array_search($sap_company_id, array_column($amicum_copmany_list, 'id')); // находим такой id в амикум
            $found_company_array_title_index = array_search($sap_company_title, array_column($amicum_copmany_list, 'title')); // находим такое название в амикум ( вернет индекс массива)
            if ($found_company_array_index)                                                                              // если найдена компания по id
            {
                if ($amicum_copmany_list[$found_company_array_index]['id'] == $sap_company_id and
                    $amicum_copmany_list[$found_company_array_title_index]['title'] != $sap_company_title and /*если по id найдена компания в аmicum, то редактируем*/
                    preg_match("/[\S]/", $sap_company_title))                                              // если по id найдена компания в amicuum и если название найденной компании в amicum != тек.названию sap
                {
                    $new_company_to_amicum['update'][$index]['id'] = $sap_company_id;
                    $new_company_to_amicum['update'][$index]['title'] = $sap_company_title;
                }
            }
            if (!$found_company_array_index and !$found_company_array_title_index and // если по id компания(sap) в amicum не найдена, id и title не пустые, то добавим   новую компанию SAP в Amicum
                $sap_company_id != '' and preg_match("/[\S]/", $sap_company_title)) {
                $new_company_to_amicum['insert'][$index]['id'] = $sap_company_id;
                $new_company_to_amicum['insert'][$index]['title'] = $sap_company_title;
            }
            $index++;
        }
        if (!empty($new_company_to_amicum['insert']))                                                                 // если есть новые поля со справочника SAP, то добавим их
        {
            $insert_new_companies_to_amicum = array();
            foreach ($new_company_to_amicum['insert'] as $company)                                                      // каждое значение добавим в новый массив, чтоб в циклекаждый раз не отправить запрос
            {
                $company_id = $company['id'];
                $company_title = $company['title'];
                $insert_new_companies_to_amicum[] = "($company_id, '$company_title', 201)";                             // добавим в один массив, чтоб закрузки к БД не было в одном индексе будет так: [0] => (id, title), (id2, title2)
            }
            $sap_companies = implode(",", $insert_new_companies_to_amicum);
            $sql = "INSERT INTO $amicum_table (id, title, upper_company_id) VALUES " . $sap_companies;
            $result = Yii::$app->db->createCommand($sql)->execute();
            if (!$result) {
                $errors[] = 'Ошибка добавления данных';
            }
        } else {
            $errors[] = 'Нет новых данных для добавления';
        }
        if (!empty($new_company_to_amicum['update']))                                                                 // если есть новые поля со справочника SAP, то добавим их
        {
            foreach ($new_company_to_amicum['update'] as $company)                                               // то в цикле добавляем
            {
                $sql = "UPDATE $amicum_table SET title = '" . $company['title'] . "' WHERE id = " . $company['id'];
                $result = Yii::$app->db->createCommand($sql)->execute();
                if (!$result) {
                    $errors[] = "Ошибка редактирования должности: id = " . $company['id'] . ", title = " . $company['title'] . ' Teкст ошибки:' . $result;
                }
            }
        } else {
            $errors[] = 'Нечего редактировать';
        }
        $result = array(['errors' => $errors, 'company_list' => $new_company_to_amicum]);                                  // сохраним в массив список ошибок и новый список
        Yii::$app->response->format = Response::FORMAT_JSON;                                                   // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * Метод автоматической отвязки резервной лампы у работника
     * Работнику приязывается постоянная лампа
     * Алгоритм:
     * 1. Получаем последнюю лампу у работника
     * 2. Если у работника последняя лампа 'Резервная', то получим его последнюю постоянную лампу, и прияжем к работнику
     * вмето резервной лампы, то есть добавим для него постоянную лампу
     * @param $worker_parameter_id - id параметра воркера
     * Created by: Одилов О.У. on 31.10.2018 14:31
     */
    public function UntieReserveWorkerLamp($worker_parameter_id)
//    public function actionUn()
    {
//        $worker_parameter_id = 159;
        $errors = array();
        $worker_last_lamp = (new Query())// Получаем последнюю лампу у работника, неважно какую
        ->select([
            'lamp_type',
            'sensor_id',
//            'worker_parameter_id',
//            'date_time'
        ])
            ->from('view_worker_last_lamp')
            ->where(['worker_parameter_id' => $worker_parameter_id])
            ->one();
        if ($worker_last_lamp)                                                                                           // если последняя лампа у работкника найдена, то получаем тип лампы работника
        {
            $worker_lamp_type = $worker_last_lamp['lamp_type'];                                                         // получаем тип лампы, которая есть у работника
            $worker_lamp_id = $worker_last_lamp['sensor_id'];                                                         // получаем тип лампы, которая есть у работника
            if ($worker_lamp_type == 'Резервная')                                                                        // проверяем, какая лампа у работника. Если у работника лампа резервная, то находим его постоянную лампу и назначим ему его постоянную лампу
            {
                $worker_constant_lamp = (new Query())// Получаем постоянную лампу работника
                ->select([
                    'sensor_id',
//                    'worker_parameter_id',
//                    'date_time',
//                    'sensor_type'
                ])
                    ->from('view_worker_constant_lamp')
                    ->where(['worker_parameter_id' => $worker_parameter_id])
                    ->one();
                if ($worker_constant_lamp)                                                                               // Если найдена постоянная лампа работника, то назначим ему найденную его постоянную лампу вместо резервной лампы
                {
                    $sensor_id = $worker_constant_lamp['sensor_id'];
                    $this->actionAddWorkerParameterSensor($worker_parameter_id, $sensor_id, 1);
                    $insert_result = $this->actionAddWorkerParameterSensor($worker_parameter_id, -1, 0);
                    if ($insert_result == -1) {
                        $errors[] = "Ошибка отвязки резервной лампы работника. Не удалось привязать постоянную лампу к работнику с worker_parameter_id = $worker_parameter_id";
                    } else {
                        $errors['success'][] = "Резервная лампа была c sensor_id = $sensor_id отвязана у сотрудника с worker_parameter_id = $worker_parameter_id";
                        $errors['success'][] = "Для работника с worker_parameter_id = $worker_parameter_id был привязан его постоянная лампа с sensor_id = $sensor_id";
                    }
                } else {
                    $insert_result = $this->actionAddWorkerParameterSensor($worker_parameter_id, -1, 0);
                    if ($insert_result == -1) {
                        $errors[] = "Ошибка отвязки резервной лампы работника. Не удалось привязать постоянную лампу к работнику с worker_parameter_id = $worker_parameter_id";
                    } else {
                        $errors['success'][] = "Резервная лампа была отвязана у сотрудника с worker_parameter_id = $worker_parameter_id";
                    }
                }
            } else if ($worker_lamp_type == 'empty') {
                $errors[] = "Не указано тип лампы у sensor_id = $worker_lamp_id";
            } else if ($worker_lamp_type == 'Постоянная')                                                                  // Если тип лампы у работника не резерная, то не нужно отвязать у него лампу
            {
                $errors['success'][] = "Нет необходимости отвязать лампу у сотрудника с worker_parameter_id = $worker_parameter_id, так как у него лампа '$worker_lamp_type'.";
            }
        } else                                                                                                            // если последняя лампа у работкника НЕ найдена, для выводим ошибку
        {
            $errors[] = "Не найден работник с worker_parameter_id = $worker_parameter_id";
        }
//        return $errors;
//        Assistant::PrintR($errors);
    }

    public function actionCompareSensors()
    {

//        ini_set('max_execution_time', -1);
//        ini_set('memory_limit', "10500M");
//        Yii::$app->cache->flush();
//        $warnings = array();
        require_once(Yii::getAlias('@vendor/moonlandsoft/yii2-phpexcel/Excel.php'));

        require_once(Yii::getAlias('@vendor/phpoffice/phpexcel/Classes/PHPExcel.php'));
        $errors = array();

        $post = Assistant::GetServerMethod();                                                                             //получение данных от ajax-запроса
        if (isset($post['mine_id']) && $post['mine_id'] != "") {
            try {
                $uploaded_file = fopen('a.ods', 'r');
                var_dump($uploaded_file);
//                $excelFile = Excel::import($uploaded_file);                                                             //импортировать загруженный файл
//                foreach ($excelFile[0] as $item)
//                {
//                    Assistant::PrintR($item);
//                }
            } catch (Throwable $ex) {
                $errors[] = $ex->getMessage();
                $errors[] = $ex->getLine();
                Assistant::VarDump($errors);
            }

        }
    }
}