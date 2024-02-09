<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\positioningsystem;

use backend\controllers\cachemanagers\EdgeCacheController;
use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\cachemanagers\WorkerCacheController;
use backend\controllers\const_amicum\ParamEnum;
use backend\controllers\EdgeBasicController;
use backend\controllers\EdgeMainController;
use backend\controllers\SensorBasicController;
use backend\controllers\WorkerBasicController;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\handbooks\HandbookTypicalObjectController;
use frontend\controllers\system\LogAmicumFront;
use frontend\controllers\WebsocketController;
use frontend\models\Mine;
use frontend\models\Place;
use frontend\models\TypicalObject;
use frontend\models\UnityConfig;
use frontend\models\WorkerObject;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;

class UnityManagerController extends Controller
{

    // GetEdgeShemaCache                        - Метод получения схемы шахты
    // GetCameraRotation                        - Метод получения поворот камеры по всем шахтам
    // GetTypePlace                             - Метод получения типов мест
    // GetTypeEdge                              - Метод получения типов выработок
    // GetShapeEdge                             - Метод получения форм выработок
    // GetColorEdge                             - Метод получения цвета выработок
    // GetTypeShieldEdge                        - Метод получения типа крепи выработки
    // EditEdge                                 - Метод редактирования выработки
    // ReverseEdge                              - Метод отмены действия по выработке
    // GetHistoryEditEdge                       - Метод возвращает шахту на тот момент и изменения до того момента
    // SaveUnityConfig                          - Метод сохранения конфигурации Unity
    // GetUnityConfig                           - Метод получения конфигурации Unity
    // GetPlaceListByMineId                     - Метод получения списка мест по ключу шахты
    // GetInitLayerUnity                        - Метод получения ключей типового объекта для инициализации слоев на 3дСхеме шахты
    // GetWorkersMineCheckin                    - Метод получения массива зарегистрированных работников по ключу шахты
    // GetWorkerParameterValue                  - Метод получения массива параметров по работникам
    // GetPlaceSearch                           - Метод получения массива шахта->пласт->место->выработка
    // GetSensorsMine                           - Метод получения массива привязанных к шахте сенсоров
    // GetSensorsParameters                     - Метод получения массива параметров сенсоров
    // GetEquipmentMine                         - Метод получения массива привязанного оборудования к шахте
    // GetHistoryWorkers                        - Метод получения истории по работнику

    public function actionIndex()
    {
        $session = Yii::$app->session;
        $mine_id = $session['userMineId'];
        $response = HandbookTypicalObjectController::getTypicalObjectArray();
        $typicalObjects = $response['Items'];
        $objectInfo = $response['objectInfo'];

        $kindObjectIdsForInit = [];
        $objectIdsForInit = [];
        $response = self::GetInitLayerUnity();
        if ($response['status']) {
            $kindObjectIdsForInit = $response['Items']['kindObjectIdsForInit'];
            $objectIdsForInit = $response['Items']['objectIdsForInit'];
        }

        $place = Place::find()
            ->select(['title', 'id'])
            ->asArray()->all();
        return $this->render('index', [
            //'ex' => $ex,
            'mine_id' => $mine_id,
            'typicalObjects' => $typicalObjects,
            'objectInfo' => $objectInfo,
            'kindObjectIdsForInit' => $kindObjectIdsForInit,
            'objectIdsForInit' => $objectIdsForInit,
            'place' => $place
        ]);
    }

    /**
     * GetInitLayerUnity - Метод получения ключей типового объекта для инициализации слоев на 3дСхеме шахты
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetInitLayerUnity&subscribe=&data={}
     * @param $data_post
     * @return array|array[]|\int[][]
     */
    public static function GetInitLayerUnity($data_post = NULL)
    {
        $log = new LogAmicumFront("GetInitLayerUnity");
        $kindObjectIdsForInit = [1, 2, 3, 5];
        $result = array(
            "all_layers" => [],
            "objectIdsForInit" => [],
            "kindObjectIdsForInit" => $kindObjectIdsForInit,
        );

        try {

            $log->addLog("Начало выполнения метода");

            $result['objectIdsForInit'] = (new Query())
                ->select('object.id')
                ->from('object')
                ->innerJoin('object_type', 'object_type.id=object.object_type_id')
                ->where(['kind_object_id' => $kindObjectIdsForInit])
                ->column();

            $result['all_layers'] = (new Query())
                ->select('object.id')
                ->from('object')
                ->column();

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * GetEdgeShemaCache - Метод получения схемы шахты
     * метод работает так:
     *      если данных в кеше нет, то он их выгребает из бд
     *      если данные есть в кеше, то сразу заполняет результирующий массив
     * Разработал:
     *      Якимов М.Н.
     * Входные параметры:
     *      mine_id  - ключ шахтного поля
     *      edge_id  - ключ ребра
     * Выходной массив:
     *         ['edge_id']
     *              |-  ['mine_id']               - id шахты
     *              |-  ['edge_id']               - id ветви
     *              |-  ['place_id']              - id места
     *              |-  ['place_title']           - название места
     *              |-  ['conjunction_start_id']  - id сопряжения старт
     *              |-  ['conjunction_end_id']    - id сопряжения конец
     *              |-  ['xStart']                - координата начала сопряжения X
     *              |-  ['yStart']                - координата начала сопряжения Y
     *              |-  ['zStart']                - координата начала сопряжения Z
     *              |-  ['xEnd']                  - координата конца сопряжения X
     *              |-  ['yEnd']                  - координата конца сопряжения Y
     *              |-  ['zEnd']                  - координата конца сопряжения Z
     *              |-  ['place_object_id']       - тип места - типовой объект по месту
     *              |-  ['danger_zona']           - параметр опасная зона
     *              |-  ['color_edge']            - параметр цвет выработки
     *              |-  ['color_edge_rus']        - параметр цвет выработки по русски
     *              |-  ['shape_edge']            - параметр форма выработки
     *              |-  ['conveyor']              - параметр наличия конвейера в данном выработке
     *              |-  ['conveyor_tag']          - тег конвейера для остановки в случае обнаружения движения
     *              |-  ['value_ch']              - уставка в выработке по СН4
     *              |-  ['value_co']              - уставка в выработке по СО
     *              |-  ['date_time']             - дата создания выработки - используется как статус актуальности
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetEdgeShemaCache&subscribe=&data={"mine_id":290,"edge_id":null}
     */
    public static function GetEdgeShemaCache($data_post = NULL)
    {
        $log = new LogAmicumFront("GetEdgeShemaCache");
        $result = array();

        try {

            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'mine_id') ||
                !property_exists($post, 'edge_id') ||
                $post->mine_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $mine_id = $post->mine_id;
            $edge_id = $post->edge_id;


            if ($mine_id == -1) {
                $mine_id = '*';
            }

            if ($edge_id == null) {
                $edge_id = '*';
            }
            $log->addData(COD, "Текущий режим ЦОД: ", __LINE__);

            if (!COD) {
                $edge_cache_controller = (new EdgeCacheController());
                $edges = $edge_cache_controller->multiGetEdgesSchema($mine_id, $edge_id);
            } else {
                $edges = EdgeBasicController::getEdgeScheme($mine_id, $edge_id);

            }

            if ($edges) {
                $result = $edges;
//                throw new Exception("В БД или в кеше нет схемы горных выработок");
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetCameraRotation - Метод получения поворот камеры по всем шахтам
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetCameraRotation&subscribe=&data={}
     */
    public static function GetCameraRotation()
    {
        $log = new LogAmicumFront("GetCameraRotation");
        $result = array();

        try {

            $log->addLog("Начало выполнения метода");

            $mines_collection = (new Query())
                ->select([
                    'mine_id',
                    'x',
                    'y',
                    'z',
                ])
                ->from('mine_camera_rotation')
                ->all();

            foreach ($mines_collection as $mine) {
                if ($mine['mine_id'] == 1) {
                    $result[] = array('mine_id' => -1, 'x' => $mine['x'], 'y' => $mine['y'], 'z' => $mine['z']);
                } else {
                    $result[] = $mine;
                }
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetTypePlace - Метод получения типов мест
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetTypePlace&subscribe=&data={}
     */
    public static function GetTypePlace()
    {
        $log = new LogAmicumFront("GetTypePlace");
        $result = (object)array();

        try {

            $log->addLog("Начало выполнения метода");

            $sql_filter = '(object_type_id = 111 or object_type_id = 112 or object_type_id = 110 or object_type_id = 115)';

            $result = TypicalObject::find()
                ->innerJoinWith('objectType')
                ->where($sql_filter)
                ->indexBy('id')
                ->all();

            if (!$result) {
                $result = (object)array();
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetTypeEdge - Метод получения типов выработок
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetTypeEdge&subscribe=&data={}
     */
    public static function GetTypeEdge()
    {
        $log = new LogAmicumFront("GetTypeEdge");
        $result = (object)array();

        try {

            $log->addLog("Начало выполнения метода");

            $result = (new Query())
                ->select(
                    [
                        'id',
                        'title'
                    ])
                ->from(['edge_type'])
                ->indexBy('id')
                ->all();

            if (!$result) {
                $result = (object)array();
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetShapeEdge - Метод получения форм выработок
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetShapeEdge&subscribe=&data={}
     */
    public static function GetShapeEdge()
    {
        $log = new LogAmicumFront("GetShapeEdge");
        $result = (object)array();

        try {

            $log->addLog("Начало выполнения метода");

            $result = (new Query())
                ->select(
                    [
                        'id',
                        'title'
                    ])
                ->from(['handbook_shape_edge'])
                ->indexBy('id')
                ->all();

            if (!$result) {
                $result = (object)array();
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetColorEdge - Метод получения цвета выработок
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetColorEdge&subscribe=&data={}
     */
    public static function GetColorEdge()
    {
        $log = new LogAmicumFront("GetColorEdge");
        $result = (object)array();

        try {

            $log->addLog("Начало выполнения метода");

            $result = (new Query())
                ->select(
                    [
                        'id',
                        'color_hex as title'
                    ])
                ->from(['unity_texture'])
                ->indexBy('id')
                ->all();

            if (!$result) {
                $result = (object)array();
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetTypeShieldEdge - Метод получения типа крепи выработки
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetTypeShieldEdge&subscribe=&data={}
     */
    public static function GetTypeShieldEdge()
    {
        $log = new LogAmicumFront("GetTypeShieldEdge");
        $result = (object)array();

        try {

            $log->addLog("Начало выполнения метода");

            $result = (new Query())
                ->select(
                    [
                        'id',
                        'title'
                    ])
                ->from(['type_shield'])
                ->indexBy('id')
                ->all();

            if (!$result) {
                $result = (object)array();
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * EchoTest - метод загшлушка, используется для отладки работы методов на фронте, связанных сервером
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=EchoTest&subscribe=&data={"mine_id":290}
     * @param $data_post
     * @return array|object[]
     */
    public static function EchoTest($data_post = null)
    {
        $log = new LogAmicumFront("EchoTest");

        $result = (object)array();

        try {
            $log->addLog("Начало выполнения метода");

            $result = $data_post;

            WebsocketController::SendMessageToWebSocket('unityEdge', $data_post);
            WebsocketController::SendMessageToWebSocket('unityWorker', $data_post);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Конец выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());

    }


    /**
     * EditEdge - Метод редактирования выработки
     * Входные данные:
     *      edge            - текущая создаваемая/редактируемая выработка
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
     *      conjunctions    - повороты текущей создаваемой выработки
     *      mergeInfo       - затронутые изменениями выработки
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=EditEdge&subscribe=&data={}
     * Выходные данные:
     *
     */
    public static function EditEdge($data_post = NULL)
    {
        $log = new LogAmicumFront("EditEdge");

        $edge_changes_id = null;
        $result = array(
            'edge' => null,
            'conjunctions' => null,
            'mergeInfo' => null,
        );

        try {
            $log->addLog("Начало выполнения метода");

//            $data_post = '{"edge":{"edge_id":"-1","place_id":"210740","place_title":"Заезд под бункер 3 гор.","conjunction_start_id":"276026","conjunction_end_id":"277652","xStart":"1","yStart":"2","zStart":"3","xEnd":"4","yEnd":"5","zEnd":"6","place_object_id":"10","place_object_title":"","plast_id":"2108","plast_title":"","type_place_title":"","edge_type_title":"","edge_type_id":"1","lenght":"0","height":"0","width":"0","angle":"0","section":"0","danger_zona":"0","color_edge":"-1","color_edge_rus":"","conveyor":"-1","mine_id":"290","mine_title":"","conveyor_tag":"","set_point_ch":"0","set_point_co":"0","type_place_id":"-1","color_hex":"000000","shape_edge_id":"-1","shape_edge_title":"","type_shield_id":"-1","type_shield_title":"","company_department_id":"-1","company_department_title":"","company_department_date":"","company_department_state":"0"},"conjunctions":[{"id":-1,"title":"","excavations":[{"id":-1,"title":""}]}],"mergeInfo":{"-1":{"status":"change","edge_id":-1,"place_id":0,"place_title":null,"conjunction_start_id":432126,"conjunction_end_id":485374,"xStart":"15851,74","yStart":"9238,007","zStart":"-14283,24","xEnd":"15877,1","yEnd":"9238,007","zEnd":"-14283,73","place_object_id":0,"place_object_title":null,"plast_id":0,"plast_title":null,"type_place_title":null,"edge_type_title":null,"edge_type_id":0,"lenght":"25,36511","height":"1","width":"1","angle":"0","section":"1","danger_zona":0,"color_edge":0,"color_edge_rus":null,"conveyor":0,"mine_id":290,"mine_title":null,"conveyor_tag":null,"set_point_ch":0,"set_point_co":0,"type_place_id":0,"color_hex":"#0084FF","shape_edge_id":0,"shape_edge_title":null,"type_shield_id":0,"type_shield_title":null,"company_department_id":0,"company_department_title":null,"company_department_date":"02.08.2023","company_department_state":0}}}';

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];


            if (
                !property_exists($post, 'edge') ||
                !property_exists($post, 'conjunctions') ||
                !property_exists($post, 'mergeInfo') ||
                $post->conjunctions == '' ||
                $post->mergeInfo == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $edgeEdit = $post->edge;
            $conjunctions = $post->conjunctions;
            $mergeInfo = $post->mergeInfo;
            $edge_id_src = null;

            /** СОХРАНЯЕМ ВЫРАБОТКУ РЕДАКТИРУЕМУ ИЗ МОДАЛЬНОГО ОКНА */
            if ($edgeEdit and !empty($edgeEdit)) {
                $edge_id_src = $edgeEdit->edge_id;
                $response = EdgeMainController::AddChangeEdge($edgeEdit);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception("Ошибка сохранения сведений о выработке");
                }
                $edgeEdit = $response['Items'];

                if ($mergeInfo and !empty($mergeInfo)) {
                    $edgeEdit->status = 'change';
                } else {
                    $edgeEdit->status = 'add';
                }
                $result['edge'] = $edgeEdit;
                $result['mergeInfo'][$edge_id_src] = $edgeEdit;

                $edge_changes_id = $response['edge_changes_id'];
            }

            /** СОХРАНЯЕМ ВЫРАБОТКИ ЗАТРОНУТЫЕ ПРИ РЕДАКТИРОВАНИИ */
            foreach ($mergeInfo as $edge) {
                switch ($edge->status) {
                    case 'add' :
                    case 'change' :
                    {
                        $log->addLog("Перебор: добавление/изменение");
                        if ($edge->edge_id != $edge_id_src) {
                            $response = EdgeMainController::AddChangeEdge($edge, $edge_changes_id);
                            $log->addLogAll($response);
                            if ($response['status'] != 1) {
                                throw new Exception("Ошибка сохранения сведений о выработке");
                            }
                            $result['mergeInfo'][$edge->edge_id] = $response['Items'];
                        }
                        break;
                    }
                    case 'delete' :
                    {
                        $log->addLog("Перебор: удаление");
                        $response = EdgeMainController::DeleteEdge($edge->edge_id, $edge->mine_id);
                        $log->addLogAll($response);
                        if ($response['status'] != 1) {
                            throw new Exception("Ошибка удаления сведений о выработке");
                        }
                        $result['mergeInfo'][$edge->edge_id] = $edge;
                        break;
                    }
                    default:
                        throw new Exception("Неизвестный тип обработки изменений");
                }
            }
            $log->addLog("Закончил сохранение");
            $result['conjunctions'] = $conjunctions;


            $response = WebsocketController::SendMessageToWebSocket('unity',
                array(
                    'type' => 'editEdge',
                    'message' => $result
                )
            );
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка отправки данных на вебсокет (editEdgeUnity)');
            }
            $log->addLog("Отправил на вебсокеты");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * ReverseEdge - Метод отмены действия по выработке
     * Входные данные:
     *      edge_id - идентификатор выработки
     *      mine_id - идентификатор шахты
     * Выходные данные:
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=ReverseEdge&subscribe=&data={}
     * Выходные данные:
     *
     */
    public static function ReverseEdge($data_post = NULL)
    {
        $log = new LogAmicumFront("ReverseEdge");

        $result = array(
            'edge' => null,
            'conjunctions' => null,
            'mergeInfo' => null,
        );

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];


            if (
                !property_exists($post, 'edge_id') ||
                !property_exists($post, 'mine_id') ||
                $post->edge_id == '' ||
                $post->mine_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $mine_id = $post->mine_id;
            $edge_id = $post->edge_id;

            $response = EdgeHistoryController::ReplaceEdges($mine_id, $edge_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка отмены последних действий с выработкой');
            }

            $result['mergeInfo'] = $response['Items'];


            $response = WebsocketController::SendMessageToWebSocket('unity',
                array(
                    'type' => 'editEdge',
                    'message' => $result
                )
            );
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка отправки данных на вебсокет (editEdgeUnity)');
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetHistoryEditEdge - Метод возвращает шахту на тот момент и изменения до того момента
     * Входные данные:
     *      mine_id - идентификатор шахты
     *      date_start - дата от
     *      date_end - дата до
     * Выходные данные:
     *      Items:
     *          mine_thereat: [ - шахта на тот момент (массив edge)
     *              [
     *                  {
     *                      edge_id:
     *                      place_id:
     *                      place_title:
     *                      conjunction_start_id:
     *                      conjunction_end_id:
     *                      xStart:
     *                      yStart:
     *                      zStart:
     *                      xEnd:
     *                      yEnd:
     *                      zEnd:
     *                      lenght:
     *                      height:
     *                      width:
     *                      section:
     *                      type_shield_id:
     *                      shape_edge_id:
     *                      danger_zona:
     *                      conveyor:
     *                      angle:
     *                      conveyor_tag:
     *                      value_ch:
     *                      value_co:
     *                      company_department_id:
     *                      company_department_date:
     *                      company_department_state:
     *                      plast_id:
     *                  }
     *                  ...
     *              ]
     *          mine_history: { - массив изменённых параметров от data_start до data_stop выработок (массив параметров)
     *              [date_time]: {
     *                  [edge_id]: {
     *                      [parameter_id]:value
     *                      ...
     *                  }
     *                  ...
     *
     *              }
     *              ...
     *          }
     *          conjunction_add: { - массив поворотов добавленных выработок от data_start до data_stop
     *              (edge_id): {
     *                  edge_id:
     *                  conjunction_start_id:
     *                  conjunction_end_id:
     *                  xStart:
     *                  yStart:
     *                  zStart:
     *                  xEnd:
     *                  yEnd:
     *                  zEnd:
     *              }
     *              ...
     *          }
     *          time_zone: - текущая AMICUM_TIME_ZONE
     *          date_start_unix: - date_start в цифровом формате
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetHistoryEditEdge&subscribe=&data={"mine_id" : 290, "date_start" : "2020-07-29 08:39:41", "date_end": "2023-07-29 08:39:41"}
     *
     */
    public static function GetHistoryEditEdge($data_post = NULL)
    {
        $log = new LogAmicumFront("GetHistoryEditEdge");

        $result = array(
            'mine_date_start' => null,
            'mine_history' => null,
            'conjunction_add' => null,
            'time_zone' => null,
            'date_start_unix' => null
        );

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'mine_id') || $post->mine_id == '' ||
                !property_exists($post, 'date_start') || $post->date_start == '' ||
                !property_exists($post, 'date_end') || $post->date_end == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $mine_id = $post->mine_id;
            $date_start = $post->date_start;
            $date_end = $post->date_end;

            $edges = EdgeBasicController::getEdgeSchemeByDate($mine_id, $date_start);

            if ($edges) {
                $result['mine_date_start'] = $edges;
            } else {
                $result['mine_date_start'] = array();
            }

            $edge_parameter = (new Query())
                ->select([
                    'date_time',
                    'edge_parameter.edge_id',
                    'parameter_id',
                    'value'
                ])
                ->from('edge_parameter_handbook_value')
                ->innerJoin('edge_parameter', 'edge_parameter.id = edge_parameter_handbook_value.edge_parameter_id')
                ->innerJoin('view_edge_mine_main', 'view_edge_mine_main.edge_id = edge_parameter.edge_id')
                ->where(['mine_id' => $mine_id])
                ->andWhere("date_time BETWEEN '$date_start' and '$date_end'")
                ->andWhere('NOT parameter_id = ' . ParamEnum::COMPANY_ID)
                ->all();

            $edges_p_s_1 = array();
            foreach ($edge_parameter as $item) {
                $mine_history[strtotime($item['date_time'] . AMICUM_TIME_ZONE)][$item['edge_id']][$item['parameter_id']] = $item['value'];
                if ($item['parameter_id'] == ParamEnum::STATE && $item['value'] == 1) {
                    $edges_p_s_1[] = $item['edge_id'];
                }
            }

            $start = date('Y-m-d', strtotime($post->date_start));
            $end = date('Y-m-d', strtotime($post->date_end));
            $edge_parameter_cd_id = (new Query())
                ->select([
                    'date_time',
                    'edge_parameter.edge_id',
                    'parameter_id',
                    'value'
                ])
                ->from('edge_parameter_handbook_value')
                ->innerJoin('edge_parameter', 'edge_parameter.id = edge_parameter_handbook_value.edge_parameter_id')
                ->innerJoin('view_edge_mine_main', 'view_edge_mine_main.edge_id = edge_parameter.edge_id')
                ->where(['mine_id' => $mine_id])
                ->andWhere("date_time BETWEEN '$start 00:00:00' and '$end 23:59:59'")
                ->andWhere(['parameter_id' => ParamEnum::COMPANY_ID])
                ->all();

            foreach ($edge_parameter_cd_id as $item) {
                $mine_history[strtotime($item['date_time'] . AMICUM_TIME_ZONE)][$item['edge_id']][$item['parameter_id']] = $item['value'];
            }

            $result['conjunction_add'] = (object)array();
//            $log->addData($edges_p_s_1, '$edges_p_s_1', __LINE__);
            if (!empty($edges_p_s_1)) {
                $edges_add = (new Query())
                    ->select([
                        'edge.id as edge_id',
                        'conjunction_start.id AS conjunction_start_id',
                        'conjunction_end.id AS conjunction_end_id',
                        'conjunction_start.x AS xStart',
                        'conjunction_start.y AS yStart',
                        'conjunction_start.z AS zStart',
                        'conjunction_end.x AS xEnd',
                        'conjunction_end.y AS yEnd',
                        'conjunction_end.z AS zEnd'
                    ])
                    ->from(['edge'])
                    ->innerJoin('conjunction conjunction_start', 'edge.conjunction_start_id = conjunction_start.id')
                    ->innerJoin('conjunction conjunction_end', 'edge.conjunction_end_id = conjunction_end.id')
                    ->indexBy('edge_id')
                    ->where(['edge.id' => $edges_p_s_1])
                    ->all();
                $result['conjunction_add'] = $edges_add;
            }


            $result['time_zone'] = AMICUM_TIME_ZONE;
            $result['date_start_unix'] = (strtotime($date_start . AMICUM_TIME_ZONE));

            if (isset($mine_history)) {
                ksort($mine_history);
                $result['mine_history'] = $mine_history;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SaveUnityConfig - Метод сохранения конфигурации Unity
     * Входные данные:
     *      mine_id - идентификатор шахты
     *      unity_config_json - json с конфигурацией
     *
     * Выходные данные:
     *      Item{
     *      mine_id - идентификатор шахты
     *      unity_config_json - json с конфигурацией
     *      }
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=SaveUnityConfig&subscribe=&data={}
     *
     */
    public static function SaveUnityConfig($data_post = NULL)
    {
        $log = new LogAmicumFront("SaveUnityConfig");

        $result = array(
            'mine_id' => null,
            'unity_config_json' => null
        );

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'mine_id') || $post->mine_id == '' ||
                !property_exists($post, 'unity_config_json') || $post->unity_config_json == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $mine_id = $post->mine_id;
            $unity_config_json = $post->unity_config_json;

            $unity_config = UnityConfig::findOne(['mine_id' => $mine_id]);
            if (!$unity_config) {
                $unity_config = new UnityConfig();
                $unity_config->mine_id = $mine_id;
            }
            $unity_config->json_unity_config = $unity_config_json;
            if (!$unity_config->save()) {
                $log->addData($unity_config->errors, '$unity_config->errors', __LINE__);
                throw new Exception("Ошибка сохранения Unity конфигурации шахты $mine_id. Модели UnityConfig");
            }

            $result['mine_id'] = $unity_config->mine_id;
            $result['unity_config_json'] = $unity_config->json_unity_config;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetUnityConfig - Метод получения конфигурации Unity
     * Обязательные входные данные:
     *      mine_id - идентификатор шахты
     *
     * Выходные данные:
     *      Item{
     *      mine_id - идентификатор шахты
     *      unity_config_json - json с конфигурацией
     *      }
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetUnityConfig&subscribe=&data={"mine_id": 290}
     *
     */
    public static function GetUnityConfig($data_post = NULL)
    {

        $log = new LogAmicumFront("GetUnityConfig");

        $result = array(
            'mine_id' => null,
            'unity_config_json' => null
        );

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'mine_id') || $post->mine_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $mine_id = $post->mine_id;

            $unity_config = UnityConfig::findOne(['mine_id' => $mine_id]);
            if (!$unity_config) {
                $json_unity = null;
            } else {
                $json_unity = $unity_config->json_unity_config;
            }
            $result['mine_id'] = $mine_id;
            $result['unity_config_json'] = $json_unity;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());

    }

    /**
     * GetPlaceListByMineId - Метод получения списка мест по ключу шахты
     * Входные данные:
     *      mine_id - идентификатор шахты
     *
     * Выходные данные:
     *      Items :
     *          place_id {
     *              place_id - ключ места
     *              place_title - название места
     *          }
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetPlaceListByMineId&subscribe=&data={"mine_id": 290}
     *
     */
    public static function GetPlaceListByMineId($data_post = NULL)
    {

        $log = new LogAmicumFront("GetPlaceListByMineId");

        $result = array();

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'mine_id') || $post->mine_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $mine_id = $post->mine_id;

            $place_list = (new Query())
                ->select([
                    'id AS place_id',
                    'title AS place_title'
                ])
                ->from('place')
                ->where(['mine_id' => $mine_id])
                ->indexBy('place_id')
                ->all();

            $result = $place_list;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());

    }

    /**
     * GetWorkersMineCheckin - Метод получения массива зарегистрированных работников по ключу шахты
     * Входные данные:
     *      mine_id - идентификатор шахты
     *
     * Выходные данные:
     *      Items :{
     *          [worker_id] {
     *              worker_id           - ключ работника
     *              worker_object_id    - ключ классификации работника
     *              object_id           - ключ типового объекта
     *              stuff_number        - табельный номер
     *              full_name           - ФИО работника
     *              position_title      - название должности
     *              department_title    - название департамента
     *              gender              - пол
     *              mine_id             - ключ шахты
     *              parameters {        - массив параметров
     *                  [parameter_id:parameter_type_id] {
     *                      worker_id               - ключ работника
     *                      worker_parameter_id     - ключ параметров рабочего
     *                      parameter_id            - ключ параметра
     *                      parameter_type_id       - ключ типа параметра
     *                      date_time               - дата и время параметра
     *                      value                   - значение параметра
     *                      status_id               - статус параметра
     *                  },
     *                  ...
     *              }
     *          }
     *          ...
     *      }
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetWorkersMineCheckin&subscribe=&data={"mine_id": 290}
     *
     */
    public static function GetWorkersMineCheckin($data_post = NULL)
    {

        $log = new LogAmicumFront("GetWorkersMineCheckin");

        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'mine_id') || $post->mine_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $mine_id = $post->mine_id;

            $log->addData(COD, "Текущий режим ЦОД: ", __LINE__);

            if (!COD) {
                $workers = (new WorkerCacheController())->getWorkerMineHash($mine_id);
            } else {
                $workers = WorkerBasicController::getWorkerMine($mine_id);
            }

            if ($workers) {
                foreach ($workers as $worker) {
                    $result[$worker['worker_id']] = $worker;
                    $result[$worker['worker_id']]['parameters'] = null;
                }

                $response = self::GetWorkerParameterValue(json_encode(['worker_ids' => array_keys($result)]));
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception("Ошибка получения параметров работника");
                }
                $parameters = $response['Items'];

                if ($parameters) {
                    foreach ($parameters as $parameter) {
                        if ($parameter['value']) {
                            $result[$parameter['worker_id']]['parameters'][$parameter['parameter_id'] . ':' . $parameter['parameter_type_id']] = $parameter;
                        }
                    }
                }

                foreach ($result as $worker) {
                    if (!$result[$worker['worker_id']]['parameters']) {
                        $result[$worker['worker_id']]['parameters'] = (object)array();
                    }
                }
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());

    }

    /**
     * GetWorkerParameterValue - Метод получения массива параметров по работникам
     * Входные данные:
     *      workers_id = [worker_id] - массив ключей работников
     *
     * Выходные данные:
     *      Items :{
     *          {
     *              worker_id               - ключ работника
     *              worker_parameter_id     - ключ параметров рабочего
     *              parameter_id            - ключ параметра
     *              parameter_type_id       - ключ типа параметра
     *              date_time               - дата и время параметра
     *              value                   - значение параметра
     *              status_id               - статус параметра
     *          }
     *          ...
     *      }
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetWorkerParameterValue&subscribe=&data={"workers_id": [1000522,2911758,2053110]}
     *
     */
    public static function GetWorkerParameterValue($data_post = NULL)
    {

        $log = new LogAmicumFront("GetWorkerParameterValue");
        $result = array();
        $parameters = array();

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'worker_ids') || $post->worker_ids == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $worker_ids = $post->worker_ids;

            if (!COD) {
                $worker_cache_controller = (new WorkerCacheController());
                foreach ($worker_ids as $worker_id) {
                    $parameters_cache = $worker_cache_controller->multiGetParameterValueHash($worker_id);
                    if ($parameters_cache) {
                        $parameters = array_merge($parameters, $parameters_cache);
                    }
                }
            } else {
                $worker_ids = implode(',', $worker_ids);
                $parameters = WorkerBasicController::getWorkerParameterValue($worker_ids, '*', '*');
            }

            $result = $parameters;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());

    }

    /**
     * GetPlaceSearch - Метод получения массива шахта->пласт->место->выработка
     * Входные данные:
     *      mines_id - ключи шахты
     *
     * Выходные данные:
     *      Items :{
     *          [mine_id] {
     *              mine_id,
     *              mine_title
     *              plasts {
     *                  [plast_id] {
     *                      plast_id,
     *                      plast_title
     *                      place: {
     *                          [place_id] {
     *                              place_id,
     *                              place_title
     *                              edges [edges_id]
     *                          },
     *                          ...
     *                      }
     *                  },
     *                  ...
     *              }
     *          },
     *          ...
     *      }
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetPlaceSearch&subscribe=&data={"mines_id": [1,2,3]}
     *
     */
    public static function GetPlaceSearch($data_post = NULL)
    {

        $log = new LogAmicumFront("GetPlaceSearch");
        $result = array();

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'mines_id') || $post->mines_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $mines_id = $post->mines_id;

            $mines = Mine::find()
                ->innerJoinWith('places.plast')
                ->innerJoinWith('places.edges.lastStatusEdge')
                ->where(['mine.id' => $mines_id])
                ->all();

            foreach ($mines as $model_mine) {
                $mine['mine_id'] = $model_mine->id;
                $mine['mine_title'] = $model_mine->title;
                $plasts = array();
                foreach ($model_mine->places as $model_place) {
                    if ($model_place->plast) {
                        $plast_id = $model_place->plast->id;
                        $plast_title = $model_place->plast->title;
                    } else {
                        $plast_id = -1;
                        $plast_title = "Без пласта";
                    }
                    $plasts[$plast_id]['id'] = $plast_id;
                    $plasts[$plast_id]['title'] = $plast_title;
                    $plasts[$plast_id]['places'][$model_place->id]['id'] = $model_place->id;
                    $plasts[$plast_id]['places'][$model_place->id]['title'] = $model_place->title;
                    $edges = array();
                    foreach ($model_place->edges as $edge) {
                        if (!isset($edge->lastStatusEdge) || $edge->lastStatusEdge->status_id == 1) {
                            $edges[] = $edge->id;
                        }
                    }
                    $plasts[$plast_id]['places'][$model_place->id]['edges'] = $edges;
                }
                $mine['plasts'] = $plasts;
                $result[$model_mine->id] = $mine;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());

    }

    /**
     * GetSensorsMine - Метод получения массива привязанных к шахте сенсоров
     * Входные данные:
     *  Обязательные:
     *      mine_id - идентификатор шахты
     *  Не обязательные:
     *      sensor_id - вернет данные конкретного сенсора
     *
     * Выходные данные:
     *      Items :{
     *          [sensor_id] {
     *              sensor_id,
     *              sensor_title,
     *              object_id,
     *              object_title,
     *              object_type_id,
     *              mine_id
     *              parameters {
     *                  [parameter_id:parameter_type_id] {
     *                      sensor_id,
     *                      sensor_parameter_id,
     *                      parameter_id,
     *                      parameter_type_id,
     *                      date_time,
     *                      value,
     *                      status_id
     *                  }
     *                  ...
     *              }
     *          }
     *          ...
     *      }
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetSensorsMine&subscribe=&data={"mine_id": 290}
     *
     */
    public static function GetSensorsMine($data_post = NULL)
    {

        $log = new LogAmicumFront("GetSensorsMine");

        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'mine_id') || $post->mine_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $mine_id = $post->mine_id;
            if (isset($post->sensor_id) && $post->sensor_id != '') {
                $sensor_id = $post->sensor_id;
            } else {
                $sensor_id = '*';
            }

            $log->addData(COD, "Текущий режим ЦОД: ", __LINE__);

            if (!COD) {
                $sensors = (new SensorCacheController())->getSensorMineHash($mine_id, $sensor_id);
            } else {
                $sensors = SensorBasicController::getSensorMain($mine_id, $sensor_id);
            }

            if (!$sensors) {
                throw new Exception("Не нашли сенсоры в шахте $mine_id");
            }

            foreach ($sensors as $sensor) {
                $result[$sensor['sensor_id']] = $sensor;
            }

            $response = self::GetSensorsParameters(json_encode(['sensors_id' => array_keys($result)]));
            $log->addLogAll($response);
            $parameters = $response['Items'];

            if ($parameters) {
                foreach ($parameters as $parameter) {
                    if ($parameter['value']) {
                        $result[$parameter['sensor_id']]['parameters'][$parameter['parameter_id'] . ':' . $parameter['parameter_type_id']] = $parameter;
                    }
                }
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());

    }

    /**
     * GetSensorsParameters - Метод получения массива параметров сенсоров
     * Входные данные:
     *  Обязательные:
     *      sensors_id          - идентификаторы сенсоров
     * Выходные данные:
     *      Items :{
     *          {
     *              sensor_id,
     *              sensor_parameter_id,
     *              parameter_id,
     *              parameter_type_id,
     *              date_time,
     *              value,
     *              status_id
     *          }
     *          ...
     *      }
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetSensorsParameters&subscribe=&data={}
     *
     */
    public static function GetSensorsParameters($data_post = NULL)
    {

        $log = new LogAmicumFront("GetSensorsParameters");

        $result = array();
        $parameters = array();

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'sensors_id') || $post->sensors_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $sensors_id = $post->sensors_id;

            $log->addData(COD, "Текущий режим ЦОД: ", __LINE__);

            if (!COD) {
                $sensor_cache_controller = new SensorCacheController();
                foreach ($sensors_id as $sensor_id) {
                    $parameters_cache = $sensor_cache_controller->multiGetParameterValueHash($sensor_id);
                    if ($parameters_cache) {
                        $parameters = array_merge($parameters, $parameters_cache);
                    }
                }
            } else {
                $sensors_id = implode(',', $sensors_id);
                $parameters = SensorBasicController::getSensorParameterHandbookValue($sensors_id);
                $parameters = array_merge($parameters, SensorBasicController::getSensorParameterValue($sensors_id));
            }

            $result = $parameters;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());

    }

    /**
     * GetEquipmentMine - Метод получения массива привязанного оборудования к шахте
     * Входные данные:
     *  Обязательные:
     *      mine_id - идентификатор шахты
     *
     * Выходные данные:
     *      Items :{
     *          [equipment_id] {
     *              equipment_id,
     *              equipment_title,
     *              object_id,
     *              object_title,
     *              object_type_id,
     *              mine_id
     *              parameters {
     *                  [parameter_id:equipment_parameter_id] {
     *                      equipment_id,
     *                      equipment_parameter_id,
     *                      parameter_id,
     *                      parameter_type_id,
     *                      date_time,
     *                      value,
     *                      status_id
     *                  }
     *                  ...
     *              }
     *          }
     *          ...
     *      }
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetEquipmentMine&subscribe=&data={"mine_id": 290}
     *
     */
    public static function GetEquipmentMine($data_post = NULL)
    {

        $log = new LogAmicumFront("GetEquipmentMine");

        $result = array();

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'mine_id') || $post->mine_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $mine_id = $post->mine_id;

            $response = EquipmentCacheController::GetEquipmentMineDetail($mine_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Не смог получить сведения об оборудовании');
            }
            $equipments = $response['Items'];

            if ($equipments) {
                foreach ($equipments as $equipment) {
                    $result[$equipment['equipment_id']] = $equipment;
                }
            }

            $response = EquipmentCacheController::GetEquipmentParameters();
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Не смог получить сведения об оборудовании');
            }
            $parameters = $response['Items'];

            if ($parameters) {
                foreach ($parameters as $parameter) {
                    if (isset($result[$parameter['equipment_id']])) {
                        $result[$parameter['equipment_id']]['parameters'][$parameter['parameter_id'] . ':' . $parameter['parameter_type_id']] = $parameter;
                    }
                }
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());

    }

    /**
     * GetHistoryWorkers - Метод получения истории по работнику
     * Входные данные:
     *  Обязательные:
     *      date_start  - дата от
     *      date_end    - дата до
     *
     *      worker_id   - идентификатор работника
     *      или
     *      mine_id     - идентификатор шахты
     *
     *  Не обязательные:
     *      delta_t     - дельта времени+- в минутах
     *
     * Выходные данные:
     *      Items :{
     *          workers {
     *              [worker_id] {
     *                  worker_id           - ключ работника
     *                  worker_object_id    - ключ классификации работника
     *                  object_id           - ключ типового объекта
     *                  stuff_number        - табельный номер
     *                  full_name           - ФИО работника
     *                  position_title      - название должности
     *                  department_title    - название департамента
     *                  gender              - пол
     *                  mine_id             - ключ шахты
     *              },
     *              ...
     *          },
     *          history_workers {
     *              [date_time] {
     *                  [worker_id] {
     *                      [parameter_id:parameter_type_id]:value
     *                  }
     *              }
     *          }
     *      }
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetHistoryWorkers&subscribe=&data={"worker_id":1,"date_start":"2023-08-13 00:00:00","date_end":"2024-08-13 00:00:00"}
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetHistoryWorkers&subscribe=&data={"mine_id":1,"date_start":"2023-08-13 00:00:00"}
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetHistoryWorkers&subscribe=&data={"mine_id":1,"date_end":"2023-08-13 00:00:00"}
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetHistoryWorkers&subscribe=&data={"mine_id":1,"date_start":"2023-08-13 00:00:00", "delta_t":10}
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\UnityManager&method=GetHistoryWorkers&subscribe=&data={"mine_id":1,"date_end":"2023-08-13 00:00:00", "delta_t":10}
     */
    public static function GetHistoryWorkers($data_post = NULL)
    {

        $log = new LogAmicumFront("GetHistoryWorkers");

        $result = array();
        $delta_t = 5;// 5 минут

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            // шахта или работник
            if (isset($post->worker_id) && $post->worker_id != '') {
                $filter = ['worker_id' => $post->worker_id];
            } else if (isset($post->mine_id) && $post->mine_id != '') {
                $filter = ['value' => $post->mine_id];
            } else {
                throw new Exception("Входные параметры не переданы");
            }

            // дельта времени
            if (isset($post->delta_t) && $post->delta_t != '') {
                $delta_t = $post->delta_t;
            }

            // получение $date_start и $date_end
            if (isset($post->date_start) && $post->date_start != '' && isset($post->date_end) && $post->date_end != '') {
                $date_start = date("Y-m-d H:i:s", strtotime($post->date_start));
                $date_end = date("Y-m-d H:i:s", strtotime($post->date_end));
            } else if (isset($post->date_start) && $post->date_start != '') {
                $date_start = date("Y-m-d H:i:s", strtotime($post->date_start));
                $date_end = date("Y-m-d H:i:s", strtotime("+$delta_t minutes", strtotime($date_start)));
            } else if (isset($post->date_end) && $post->date_end != '') {
                $date_end = date("Y-m-d H:i:s", strtotime($post->date_end));
                $date_start = date("Y-m-d H:i:s", strtotime("-$delta_t minutes", strtotime($date_end)));
            } else {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $w_p_value_date = (new Query())
                ->select([
                    'worker_parameter_id',
                    'date_time',
                    'value'
                ])
                ->from('worker_parameter_value')
                ->where("date_time between '$date_start' and '$date_end'")
                ->andWhere("value != -1");

            $workers_id = (new Query())
                ->select([
                    'worker_id',
                    'worker_parameter_value.value as mine_id'
                ])
                ->distinct()
                ->from(['worker_parameter_value' => $w_p_value_date])
                ->innerJoin('worker_parameter', 'worker_parameter.id = worker_parameter_value.worker_parameter_id and parameter_id = ' . ParamEnum::MINE_ID)
                ->innerJoin('worker_object', 'worker_object.id = worker_parameter.worker_object_id')
                ->where($filter);

            $workers = (new Query())
                ->select([
                    'worker_object.worker_id as worker_id',
                    'worker_object.id as worker_object_id',
                    'object_id',
                    'worker.tabel_number as stuff_number',
                    'first_name',
                    'last_name',
                    'patronymic',
                    'position.title as position_title',
                    'department.title as department_title',
                    'gender',
                    'workers_id.mine_id as mine_id'
                ])
                ->from(['workers_id' => $workers_id])
                ->innerJoin('worker_object', 'worker_object.worker_id = workers_id.worker_id')
                ->innerJoin('worker', 'worker.id = worker_object.worker_id')
                ->innerJoin('employee', 'employee.id = worker.employee_id')
                ->innerJoin('position', 'position.id = worker.position_id')
                ->innerJoin('company_department', 'company_department.id = worker.company_department_id')
                ->innerJoin('department', 'department.id = company_department.department_id')
                ->all();

            foreach ($workers as $worker) {
                $worker_obj['worker_id'] = $worker['worker_id'];
                $worker_obj['worker_object_id'] = $worker['worker_object_id'];
                $worker_obj['object_id'] = $worker['object_id'];
                $worker_obj['stuff_number'] = $worker['stuff_number'];
                $full_name = Assistant::GetFullName($worker['first_name'], $worker['patronymic'], $worker['last_name']);
                $worker_obj['full_name'] = $full_name;
                $worker_obj['position_title'] = $worker['position_title'];
                $worker_obj['department_title'] = $worker['department_title'];
                $worker_obj['gender'] = $worker['gender'];
                $worker_obj['mine_id'] = $worker['mine_id'];
                $result['workers'][$worker['worker_id']] = $worker_obj;
            }

            $parameter_values = (new Query())
                ->select([
                    'worker_object.worker_id',
                    'date_time',
                    'parameter_id',
                    'parameter_type_id',
                    'value'
                ])
                ->from('worker_object')
                ->innerJoin(['workers_id' => $workers_id], 'workers_id.worker_id = worker_object.worker_id')
                ->innerJoin('worker_parameter', 'worker_parameter.worker_object_id = worker_object.id')
                ->innerJoin(['worker_parameter_value' => $w_p_value_date], 'worker_parameter_value.worker_parameter_id = worker_parameter.id')
                ->all();

            foreach ($parameter_values as $parameter_values) {
                $history_workers[strtotime($parameter_values['date_time'] . AMICUM_TIME_ZONE)][$parameter_values['worker_id']][$parameter_values['parameter_id'] . ':' . $parameter_values['parameter_type_id']] = $parameter_values['value'];
            }

            if (isset($history_workers)) {
                ksort($history_workers);
                $result['history_workers'] = $history_workers;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());

    }
}
