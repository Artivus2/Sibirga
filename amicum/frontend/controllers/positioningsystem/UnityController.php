<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\positioningsystem;

use backend\controllers\Alias;
use backend\controllers\cachemanagers\EdgeCacheController;
use backend\controllers\cachemanagers\LogCacheController;
use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\cachemanagers\WorkerCacheController;
use backend\controllers\CoordinateController;
use backend\controllers\EdgeBasicController;
use backend\controllers\EdgeMainController;
use backend\controllers\EquipmentBasicController;
use backend\controllers\EquipmentMainController;
use backend\controllers\PackData;
use backend\controllers\SensorBasicController;
use backend\controllers\SensorMainController;
use backend\controllers\StrataJobController;
use backend\controllers\WorkerBasicController;
use backend\controllers\WorkerMainController;
use DateTime;
use DateTimeZone;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\HandbookCachedController;
use frontend\controllers\handbooks\HandbookTypicalObjectController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\AccessCheck;
use frontend\models\Conjunction;
use frontend\models\Edge;
use frontend\models\EdgeFunction;
use frontend\models\EdgeParameter;
use frontend\models\EdgeParameterHandbookValue;
use frontend\models\EdgeParameterValue;
use frontend\models\EdgeStatus;
use frontend\models\Equipment;
use frontend\models\EquipmentParameter;
use frontend\models\ForbiddenZone;
use frontend\models\Main;
use frontend\models\Mine;
use frontend\models\OrderPlacePath;
use frontend\models\Path;
use frontend\models\PathEdge;
use frontend\models\Place;
use frontend\models\Sensor;
use frontend\models\SensorParameter;
use frontend\models\SensorParameterHandbookValue;
use frontend\models\SensorParameterValue;
use frontend\models\TypicalObject;
use frontend\models\ViewEdgeParameterHandbookValueMaxDateForMerge;
use frontend\models\ViewTypeObjectParameterHandbookValueMaxDateMain;
use frontend\models\WorkerParameter;
use Throwable;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;

class UnityController extends Controller
{

    // actionEditSensorInfo         - Метод добавления, перемещения сенсора в 3D схеме. в БД и в КЕШ
    // actionForceCheckOut          - Метод принудительной выписки работника из шахты
    // actionGetCommunicationList   - Метод получения схемы передачи данных между узлами связи
    // actionGetTypical3dModel      - Метод получения списка путей 3d моделей типовых объектов, если задан входной параметр object_id
    // actionMergeEdge              - Метод редактирования горной выработки
    // actionSaveEquipmentOnScheme  - Метод добавления, перемещения оборудования в 3D схеме, в БД и в КЕШ
    // actionSendMessages           - Метод принятия сообщений от диспетчера и записи его в бд и в кеш РАБОТНИКУ
    // actionSendMessagesSensor     - Метод принятия сообщений от диспетчера и записи его в бд и в кеш СЕНСОРУ
    // actionEditEdge               - Метод редактирования выработки
    // actionGetEdge                - По запросу клиента формирует данные по конкретной заправшиваемой ветви
    // actionGetMineCameraRotation  - Метод получения поворот камеры у заданной шахты
    // actionDeleteEdgeFromMine()   - Метод каскадного удаления выработки из кэша и из БД.
    // actionGetSensorsParameters   - Метод получения параметров сенсора из кэша или БД, а именно 3 параметра - статус,
    //                                  местоположение и позиция (ПКМ на схеме шахты информация о сенсоре)
    // actionDeleteEquipmentFromMine- Метод удаления оборудования со схемы шахты
    // actionDeleteSensorFromMine   - Метод удаления датчика из шахты (привязки датчик-шахта)
    // getLayersUnity               - Функция получения списка слоев Unity на основе списка типовых объектов


    use Alias;
    use PackData;

    public function actionIndex()
    {
        $session = Yii::$app->session;
        $mine_id = $session['userMineId'];
        $typicalObjects = HandbookTypicalObjectController::getTypicalObjectArray()['Items'];
        $objectInfo = HandbookTypicalObjectController::getTypicalObjectArray()['objectInfo'];
        $kindObjectIdsForInit = [1, 2, 3, 5];
        $objectIdsForInit = (new Query())
            ->select('object.id')
            ->from('object')
            ->innerJoin('object_type', 'object_type.id=object.object_type_id')
            ->where(['kind_object_id' => $kindObjectIdsForInit])
            ->column();
        $place = Place::find()
            ->select(['title', 'id'])
            ->asArray()->all();
        //$ex = $this->actionGetWorkers();
//        $sensorList = $this->SendSensorAc();
        return $this->render('index', [
            //'ex' => $ex,
            'mine_id' => $mine_id,
            'typicalObjects' => $typicalObjects,
            'objectInfo' => $objectInfo,
            'kindObjectIdsForInit' => $kindObjectIdsForInit,
            'objectIdsForInit' => $objectIdsForInit,
            'place' => $place
//            'sensorList' => $sensorList
        ]);
//        return $this->render('index');
    }


    /**
     * Название метода: actionMergeEdge()
     * @throws \yii\base\InvalidConfigException
     *
     * Документация на портале: http://192.168.1.4/products/community/modules/forum/posts.aspx?&t=180&p=1#198
     *
     * @package app\controllers
     * МЕТОД СОЗДАНИЯ ВЫРАБОТКИ (ДОБАВЛЕНИЕ В БД и в кэш)
     * Есть 6 типа СОЗДАНИЯ ВЫРАБОТКИ:
     * 1. ПОЛЕ - ПОЛЕ
     * 2. СОПРЯЖЕНИЕ - ПОЛЕ
     * 3. ВЫРАБОТКА - ВЫРАБОТКА
     *    Алгоритм:
     *        1. Создается основная выработка(edge)
     *        1. Из старой первой выработки создается новая выработка(ребро 1.1), то есть первая (старая) выработка разбывается на 2 части
     *        3. Копируются параметры из старой первой выработки в новую (ребро 1.1).
     *        3. Создается ребро 1.2 и копируются параметры из старой первой выработки и удаляется старая вырабтка(первая)
     *        1. Из старой второй выработки создается новая выработка(ребро 2.1), то есть вторая (старая) выработка разбывается на 2 части
     *        3. Копируются параметры из старой второй выработки в новую (ребро 2.2).
     *        3. Создается ребро 2.2 и копируются параметры из старой второй выработки и удаляется старая вырабтка(вторая)
     * 4. ВЫРАБОТКА - ПОЛЕ
     * 5. СОПРЯЖЕНИЕ - СОПРЯЖЕНИЕ
     * 6. ВЫРАБОТКА - СОПРЯЖЕНИЕ
     * Входные параметры:
     * Количество входных параметров зависит от типа добавляемой выработки.
     *
     * При добавлении выработки, выработка добавляется в кэш. Данные в кэш записываются с помощью очереди.
     * Добавление в кэш с помощь очереди можно отключить, используя переменную $use_add_by_queue = 0.
     * По умолчанию с помощью очереди добавляются в кэш.
     *
     * @url http://localhost/unity/merge-edge
     * @url http://localhost/unity/merge-edge?place_title=&place_id=6623&place_object_id=80&place_plast_id=2103&height=2&width=1&length=329&danger_zone=1&conveyor=1&section=2&color_edge=PinkEdgeMaterial&edge_type_id=34&edge_mine_id=290&conjunction_start_x=15085.6&conjunction_start_y=-343.858&conjunction_start_z=-12483.7&edge_type_add=6&conjunction_end_x=15414.65&conjunction_end_y=-331.9308&conjunction_end_z=-12489.51&edge1_id=26031&edge2_id=0&edge1_conjunction_start_id=0&edge2_conjunction_end_id=19578
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 15.01.2019 13:47
     * @since ver1.1
     */
    public function actionMergeEdge()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $microtime_start = microtime(true);
            $warnings[] = 'actionMergeEdge. Начало выполнения метода';
//            ini_set('memory_limit', '2048M');
//            ini_set('log_errors', 1);
//            ini_set('post_max_size', '200M');
//            ini_set('upload_max_filesize', '200M');
            $debug_log = array();
            $edge_cache_controller = new EdgeCacheController();
            $success_param_insert = array();
            $errors = array();
            $edges_places = array();                                                                                    // Массив местоположений выработок, участвующих при добавлении новой выработки
            $edges_places_unic = array();
            $debug_cache_flag = 0;
            $debug_flag = 0;
            $resolution_for_add = true;
            $edge_new_id_to_send = null;
            $edge_new_place_id = null;
            $add_edge_list = array();                                                                                   // массив выработок на добавление
            $delete_edge_list = array();                                                                                // массив выработок на удаление
            $change_edge_list = array();                                                                                // массив выработок на изменение
            $session = Yii::$app->session;                                                                              // старт сессии
            $session->open();                                                                                           // открыть сессию
            if (!isset($session['sessionLogin'])) {                                                                     // если пользователь авторизован
                throw new Exception('actionMergeEdge. Время сессии закончилось. Требуется повторный ввод пароля');
            }
            if (!AccessCheck::checkAccess($session['sessionLogin'], 75)) {                                       //если у пользователя есть право на добавление выработки
                throw new Exception('actionMergeEdge. Недостаточно прав для совершения данной операции');
            }

            $post = Assistant::GetServerMethod();                                                                       // метод принимает данные из модели для фильтрации запроса.
//                echo "<pre>";
//                print_r($post);
//                echo "</pre>";
//        if($debug_flag == 1) $post = $this->DebugValues();
            $sql_filter = '';                                                                                           // фильтр запроса, т.к. данных в запросе много, то по умолчанию возвращается только данные за текущие сутки,
            $post_flag = 0;
            $edge_type_add = 0;                                                                                         // вид построения выработки
            $flag_done = 0;                                                                                             // флаг успешной записи новых выработок
            $edge11_lenght = '0';                                                                                       // длина edge 11 после разбиения
            $edge12_lenght = '0';                                                                                       // длина edge 12 после разбиения
            $edge21_lenght = '0';                                                                                       // длина edge 21 после разбиения
            $edge22_lenght = '0';                                                                                       // длина edge 22 после разбиения

            /**************************************   ОБЪЯВЛЕНИЕ ПЕРЕМЕННЫХ        ****************************************/
            {
                $edge_new_place_title = '';                                                                             // ребро новое название места
                $edge_new_place_mine_id = -1;                                                                           // ребро новое ID шахты для места
                $edge_new_place_object_id = -1;                                                                         // ребро новое ID типового объекта для создания места
                $edge_new_place_plast_id = -1;                                                                          // ребро новое ID пласта
                $edge_new_id = 0;                                                                                       // ребро новое ID ребра
                $edge_new_height = 0;                                                                                   // ребро новое высота
                $edge_new_width = 0;                                                                                    // ребро новое ширина
                $edge_new_length = 0;                                                                                   // ребро новое протяженность
                $edge_new_danger_zone = 0;                                                                              // ребро новое опасная зона
                $edge_new_conveyor = 0;                                                                                 // ребро новое опасная зона
                $edge_new_section = 0;                                                                                  // ребро новое сечение
                $edge_new_color_edge = 0;                                                                               // ребро новое цвет
                $edge_new_edge_type_id = 0;                                                                             // ребро новое тип ребра
                $edge_new_mine_id = 0;                                                                                  // ребро новое ID шахты
                $edge_type_add = -1;                                                                                    // тип добавляемой выработки
                $edge1_place_id = -1;                                                                                   // местоположение старой выработки
                $edge1_conjunction_start_id = 0;
                $edge1_conjunction_end_id = 0;
                $edge1_conjunction_start_x = 0;
                $edge1_conjunction_start_y = 0;
                $edge1_conjunction_start_z = 0;
                $edge1_conjunction_end_x = 0;
                $edge1_conjunction_end_y = 0;
                $edge1_conjunction_end_z = 0;
                $edge2_conjunction_start_id = 0;
                $edge2_conjunction_end_id = 0;
                $edge2_conjunction_start_x = 0;
                $edge2_conjunction_start_y = 0;
                $edge2_conjunction_start_z = 0;
                $edge2_conjunction_end_x = 0;
                $edge2_conjunction_end_y = 0;
                $edge2_conjunction_end_z = 0;
                $edge2_place_id = -1;
                $edge1_edge_type_id = -1;
                $edge_new_id_to_send = -1;
                $edge_new_place_id = -1;                                                                                // id типа места вновь создаваемого edge
                $find_object_by_id = -1;
                $edge1_edge_type_id = -1;
                $edge2_edge_type_id = -1;
                $edge_type_add = -1;
                $edge_new_place_object_id = -1;
                $edge_new_conveyor_tag = null;
                $edge_new_value_co = -1;
                $edge_new_value_ch = -1;
            }


            if (isset($post['edge_type_add']) and $post['edge_type_add'] != '') {
                $edge_type_add = $post['edge_type_add'];
            } else {
                throw new Exception('actionMergeEdge. Не передан тип добавления выработки или имеет пустое значение');
            }
            /*****************************        ХАРАКТЕРИСТИКИ ВЫРАБОТКИ - РЕБРА       ******************************/
            if ($edge_type_add > 0) {
                if (!isset($post['place_id'])) {
                    $edge_type_add = -1;
                    $errors[] = 'Параметр place_id не передан';
                } else {
                    $edge_new_place_id = (int)$post['place_id'];                                                        // ребро новое ID места
                    $edge_new_place_title = $post['place_title'];                                                       // ребро новое название места
                    $edge_new_place_mine_id = (int)$post['edge_mine_id'];                                               // ребро новое ID шахты для места
                    $edge_new_place_object_id = (int)$post['place_object_id'];                                          // ребро новое ID типового объекта для создания места
                    $edge_new_place_plast_id = (int)$post['place_plast_id'];                                            // ребро новое ID пласта
                    $edge_new_id = -1;                                                                                  // ребро новое ID ребра
                    $edge_new_height = $post['height'];                                                                 // ребро новое высота
                    $edge_new_width = $post['width'];                                                                   // ребро новое ширина
                    $edge_new_length = $post['length'];                                                                 // ребро новое протяженность
                    $edge_new_danger_zone = $post['danger_zone'];                                                       // ребро новое опасная зона
                    $edge_new_conveyor = $post['conveyor'];                                                             // ребро новое опасная зона
                    $edge_new_section = $post['section'];                                                               // ребро новое сечение
                    $edge_new_color_edge = $post['color_edge'];                                                         // ребро новое цвет
                    $edge_new_edge_type_id = (int)$post['edge_type_id'];                                                // ребро новое тип ребра
                    $edge_new_mine_id = $post['edge_mine_id'];                                                          // ребро новое ID шахты

                    if (isset($post['conveyor_tag']) and $post['conveyor_tag'] != '') {
                        $edge_new_conveyor_tag = $post['conveyor_tag'];                                                 // тег конвейера если он есть
                    }

                    $edge_new_value_co = $post['set_point_co'];                                                         // ребро значение угарного газа
                    $edge_new_value_ch = $post['set_point_ch'];                                                         // ребро значение метана


                }
            } else {
                throw new Exception('actionMergeEdge. Неизвестный тип добавления выработки');
            }

            /******************************    ДОБАВЛЕНИЕ (ОПРЕДЕЛЕНИЕ) МЕСТОПОЛОЖЕНИЯ     ****************************/
            {
                if ($edge_new_place_id == (-1)) {                                                                       // если местоположение новое, то создадим его
                    $find_object_by_id = TypicalObject::findOne(['id' => $edge_new_place_object_id]);
                    if ($find_object_by_id) {
                        $find_object_by_id = $find_object_by_id->id;
                        /****************************** СОЗДАНИЕ МЕСТА, ЕСЛИ ЕГО НЕТ В БД   ***************************/
                        $exists_place = Place::findOne(['title' => $edge_new_place_title, 'mine_id' => $edge_new_mine_id]);// проверяем, если такое место, получаем название
                        if (!$exists_place)                                                                             // если место не найдено, то создадим
                        {
                            $edge_new_place_id = $this->actionAddPlace($edge_new_place_title, $edge_new_place_mine_id, $edge_new_place_object_id, $edge_new_place_plast_id); //создание нового места
                            if (is_array($edge_new_place_id)) {
                                $errors[] = 'Ошибка сохранения места';
                                $edge_new_place_id = -1;
                            } else {
                                $create_place_parameter_and_handbook_value = SpecificPlaceController::CopyTypicalParametersToSpecificPlacePublic($find_object_by_id, $edge_new_place_id); // копируем все place_parameter_value, place_parameter_handbook_value в edge
                                if ($create_place_parameter_and_handbook_value == -1) {
                                    $errors[] = "Ошибка копирования типовых параметров объкта edge в place_id = $edge_new_place_id";
                                    $edge_new_place_id = -1;
                                }
                            }
                        } else                                                                                          // если место найдено, то получаем не менялся ли тип места и пласт у местоположения, если поменялись, то редактируем
                        {
                            /****************** РЕДАКТИРОВАНИЕ МЕСТОПОЛОЖЕНИЯ ******************************************/
                            if ($edge_new_place_object_id != $exists_place->object_id or $edge_new_place_plast_id != $exists_place->plast_id) // если тип место или пласт поменялось, то редактируем
                            {
                                $exists_place->object_id = $edge_new_place_object_id;
                                $exists_place->plast_id = $edge_new_place_plast_id;
                                if ($exists_place->save()) {

                                    $edge_new_place_id = $exists_place->id;
                                } else {
                                    $errors[] = 'Ошибка редактирования типа места и и пласта у конкретного местоположения';
                                    $errors['place-error'] = $exists_place->errors;
                                    $edge_new_place_id = -1;
                                }
                            } else  $edge_new_place_id = $exists_place->id;
                        }
                    } else {
                        $errors[] = "Указанного объекта object_id = $edge_new_place_object_id нет в БД";
                        $edge_new_place_id = -1;
                    }
                } else {
                    /***********************    ОПРЕДЕЛЕНИЕ МЕСТОПОЛОЖЕНИЯ      ****************************************/
                    $place = Place::findOne(['id' => $edge_new_place_id]);                                              // получаем место с указанным id
                    if (!$place)                                                                                        // если не нашли такого места, то выводим ошибку
                    {
                        $errors[] = 'Неизвестный ID местоположения. Такого места с таким идентификатором нет в БД';
                        $edge_new_place_id = -1;
                    } else {
                        /****************** РЕДАКТИРОВАНИЕ МЕСТОПОЛОЖЕНИЯ ******************************************/
                        if ($edge_new_place_object_id != $place->object_id or $edge_new_place_plast_id != $place->plast_id) // если тип место или пласт поменялось, то редактируем
                        {
                            $success_param_insert[] = 'Пласт или объект местоположения поменлся';
                            $place->object_id = $edge_new_place_object_id;
                            $place->plast_id = $edge_new_place_plast_id;
                            if ($place->save()) {
                                $success_param_insert[] = 'Отредактировал параметры местоположения';
                                $edge_new_place_id = $place->id;
                            } else {
                                $errors[] = 'Ошибка редактирования типа места и и пласта у конкретного местоположения';
                                $errors['place-error'] = $place->errors;
                                $edge_new_place_id = -1;
                            }
                        } else {
                            $success_param_insert[] = 'Никаких изменений нет в параметров местопложения';
                            $edge_new_place_id = $place->id;
                        }
                    }
                }
            }

            /*********************************    СОЗДАНИЕ ВЫРАБОТКИ В ПОЛЕ (ТИП: ПЕРВЫЙ)    **************************/
            if ($edge_type_add == 1 and $edge_new_place_id > 0) {
                /*********************    ПОЛУЧЕНИЕ ДАННЫХ    *******************************/
                //новое ребро - которое создаем
                //вершина 1
                $edge_new_conjunction_start_id = 0;                                                                     // ребро новое сопряжение начало
                $edge_new_conjunction_start_x = $post['conjunction_start_x'];                                           // ребро новое X старт
                $edge_new_conjunction_start_y = $post['conjunction_start_y'];                                           // ребро новое Y старт
                $edge_new_conjunction_start_z = $post['conjunction_start_z'];                                           // ребро новое Z старт
                //вершина2
                $edge_new_conjunction_end_id = 0;                                                                       // ребро новое сопряжение конец
                $edge_new_conjunction_end_x = $post['conjunction_end_x'];                                               // ребро новое X конец
                $edge_new_conjunction_end_y = $post['conjunction_end_y'];                                               // ребро новое Y конец
                $edge_new_conjunction_end_z = $post['conjunction_end_z'];                                               // ребро новое Z конец
                $edge_new_length = pow((pow(($edge_new_conjunction_start_x - $edge_new_conjunction_end_x), 2) + pow(($edge_new_conjunction_start_y - $edge_new_conjunction_end_y), 2) + pow(($edge_new_conjunction_start_z - $edge_new_conjunction_end_z), 2)), 0.5);

                /*********************    СОЗДАНИЕ САМОЙ ВЫРАБОТКИ (РЕБРА)    ***************/
                if ($debug_flag == 1) echo nl2br('зашел в построение выработки в поле ' . "\n");

                $response = SpecificConjunctionController::AddConjunction($edge_new_mine_id, $edge_new_conjunction_start_x, $edge_new_conjunction_start_y, $edge_new_conjunction_start_z);
                if ($response['status'] == 1) {
                    $edge_new_conjunction_start_id = $response['conjunction_id'];
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception('actionMergeEdge. Ошибка добавления сопряжения');
                }
                $response = SpecificConjunctionController::AddConjunction($edge_new_mine_id, $edge_new_conjunction_end_x, $edge_new_conjunction_end_y, $edge_new_conjunction_end_z);
                if ($response['status'] == 1) {
                    $edge_new_conjunction_end_id = $response['conjunction_id'];
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception('actionMergeEdge. Ошибка добавления сопряжения');
                }
                $response = EdgeBasicController::AddEdge($edge_new_place_id, $edge_new_conjunction_start_id, $edge_new_conjunction_end_id, $edge_new_edge_type_id);
                if ($response['status'] == 1) {
                    $edge_new_id = $response['edge_id'];
                    $add_edge_list[] = $response['edge_id'];
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception('actionMergeEdge. Ошибка добавления выработки в БД выработки');
                }
//                            $edge_new_id = self::actionAddEdge($edge_new_place_id, $edge_new_conjunction_start_id, $edge_new_conjunction_end_id, $edge_new_edge_type_id);
                $mas_edge_id = array();
                $mas_edge_id[] = $edge_new_id;
                $response = EdgeHistoryController::AddEdgeChange($mas_edge_id);
                if ($response['status'] != 1) {
                    $errors[] = $edge_change['errors'];
                }

                /*********   Копирование типовых параметров place в конкретный edge     *********/
                if ($find_object_by_id) {
                    $response = $this->actionCopyTypicalParametersToSpecificToEdge($find_object_by_id, $edge_new_id);// копируем все place_parameter_value, place_parameter_handbook_value в edge
                    if ($response['status'] != 1) {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        throw new Exception("actionMergeEdge. Ошибка копирования типовых параметров объкта места в edge_id = $edge_new_id");
                    }
                }
                $edge_new_id_to_send = $edge_new_id;
                /****************** Создание параметров выработки   *******************/
                {
                    $success_param_insert = $this->InsertParamsOnCreateEdge($edge_new_id, $edge_new_length, $edge_new_height, $edge_new_width, $edge_new_section, $edge_new_danger_zone, $edge_new_conveyor, $edge_new_color_edge, 1, $edge_new_value_co, $edge_new_value_ch, $edge_new_conveyor_tag);
                }
                /************** ДОБАВЛЯЕМ НОВУЮ ВЫРАБОТКУ В КЭШ (Параметры, значения, схема)   *****/
                $debug_log[] = $edge_cache_controller->runInit($edge_new_mine_id, $edge_new_id_to_send);
            }
            /*********************************    СОЗДАНИЕ ТУПИКОВОЙ ВЫРАБОТКИ (ТИП: ВТОРОЙ)    ***********************/
            if ($edge_type_add == 2 and $edge_new_place_id > 0) {

                /*********************    ПОЛУЧЕНИЕ ДАННЫХ    *******************************/
                //ребро 1
                //вершина 1.1
                $edge1_conjunction_start_id = $post['edge1_conjunction_start_id'];                                      // ребро 1 сопряжение начало
                if ($conjunctions = Conjunction::find()->where(['id' => $edge1_conjunction_start_id])->one()) {
                    $edge1_conjunction_start_x = $conjunctions->x;                                                      // ребро 1 X старт
                    $edge1_conjunction_start_y = $conjunctions->y;                                                      // ребро 1 Y старт
                    $edge1_conjunction_start_z = $conjunctions->z;                                                      // ребро 1 Z старт
                }
                //новое ребро - которое создаем
                //вершина2
                $edge_new_conjunction_end_id = 0;                                                                       // ребро новое сопряжение конец
                $edge_new_conjunction_end_x = $post['conjunction_end_x'];                                               // ребро новое X конец
                $edge_new_conjunction_end_y = $post['conjunction_end_y'];                                               // ребро новое Y конец
                $edge_new_conjunction_end_z = $post['conjunction_end_z'];                                               // ребро новое Z конец
                $edge_new_length = pow((pow(($edge1_conjunction_start_x - $edge_new_conjunction_end_x), 2) + pow(($edge1_conjunction_start_y - $edge_new_conjunction_end_y), 2) + pow(($edge1_conjunction_start_z - $edge_new_conjunction_end_z), 2)), 0.5);

                /*********************    СОЗДАНИЕ САМОЙ ВЫРАБОТКИ (РЕБРА)    ***************/
                if ($debug_flag == 1) echo nl2br('зашел в построение тупиковой выработки' . "\n");
                $response = SpecificConjunctionController::AddConjunction($edge_new_mine_id, $edge_new_conjunction_end_x, $edge_new_conjunction_end_y, $edge_new_conjunction_end_z);
                if ($response['status'] == 1) {
                    $edge_new_conjunction_end_id = $response['conjunction_id'];
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception('actionMergeEdge. Ошибка добавления сопряжения');
                }
                $response = EdgeBasicController::AddEdge($edge_new_place_id, $edge1_conjunction_start_id, $edge_new_conjunction_end_id, $edge_new_edge_type_id);
                if ($response['status'] == 1) {
                    $edge_new_id = $response['edge_id'];
                    $add_edge_list[] = $response['edge_id'];
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception('actionMergeEdge. Ошибка добавления выработки в БД выработки');
                }

                /*********   Копирование типовых параметров place в конкретный edge     *********/
                if ($find_object_by_id) {
                    $response = $this->actionCopyTypicalParametersToSpecificToEdge($find_object_by_id, $edge_new_id);// копируем все place_parameter_value, place_parameter_handbook_value в edge
                    if ($response['status'] != 1) {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        throw new Exception("actionMergeEdge. Ошибка копирования типовых параметров объкта места в edge_id = $edge_new_id");
                    }
                }
                $edge_new_id_to_send = $edge_new_id;
                /****************** Создание параметров выработки   *******************/
                {
                    $success_param_insert = $this->InsertParamsOnCreateEdge($edge_new_id, $edge_new_length, $edge_new_height, $edge_new_width, $edge_new_section, $edge_new_danger_zone, $edge_new_conveyor, $edge_new_color_edge, 1, $edge_new_value_co, $edge_new_value_ch, $edge_new_conveyor_tag);
                }
                /***************************** ЗАПИСЫВАЕМ ИЗМЕНЕНИЯ ВЫРАБОТОК *****************************************/
                $mas_edge_id = array();
                $mas_edge_id[] = $edge_new_id_to_send;
                $edge_change = EdgeHistoryController::AddEdgeChange($mas_edge_id);
                /***************************** ДОБАВЛЯЕМ НОВУЮ ВЫРАБОТКУ В КЭШ (Параметры, значения, схема)    ************/
                $debug_log[] = $edge_cache_controller->runInit($edge_new_mine_id, $edge_new_id_to_send);
            }

            /*********************************    СОЗДАНИЕ ВЫРАБОТКИ (ВЫРАБОТКА - ВЫРАБОТКА) (ТИП: ТРЕТИЙ) ************/
            if ($edge_type_add == 3 and $edge_new_place_id > 0) {
                /*********************    ПОЛУЧЕНИЕ ДАННЫХ    *******************************/
                {
                    $edge1_conjunction_start_x = 0;                                                                     // ребро 1 X старт
                    $edge1_conjunction_start_y = 0;                                                                     // ребро 1 Y старт
                    $edge1_conjunction_start_z = 0;                                                                     // ребро 1 Z старт
                    $edge1_conjunction_end_x = 0;                                                                       // ребро 1 X конец
                    $edge1_conjunction_end_y = 0;                                                                       // ребро 1 Y конец
                    $edge1_conjunction_end_z = 0;                                                                       // ребро 1 Z конец
                    $edge1_place_id = -1;
                    //новое ребро - которое создаем
                    //вершина 1
                    $edge_new_conjunction_start_id = 0;                                                                 // ребро новое сопряжение начало
                    $edge_new_conjunction_start_x = $post['conjunction_start_x'];                                       // ребро новое X старт
                    $edge_new_conjunction_start_y = $post['conjunction_start_y'];                                       // ребро новое Y старт
                    $edge_new_conjunction_start_z = $post['conjunction_start_z'];                                       // ребро новое Z старт
                    //верш
                    $edge_new_conjunction_end_id = 0;                                                                   // ребро новое сопряжение конец
                    $edge_new_conjunction_end_x = $post['conjunction_end_x'];                                           // ребро новое X конец
                    $edge_new_conjunction_end_y = $post['conjunction_end_y'];                                           // ребро новое Y конец
                    $edge_new_conjunction_end_z = $post['conjunction_end_z'];                                           // ребро новое Z конец

                    //ребро 1
                    $edge1_id = $post['edge1_id'];                                                                      // ребро 1 ID ребра

                    if ($edges = Edge::find()->with('place')->where(['id' => $edge1_id])->one()) {
                        $edge1_conjunction_start_id = $edges->conjunction_start_id;                                     // ребро 1 сопряжение начало
                        $edge1_edge_type_id = $edges->edge_type_id;                                                     // ребро 1 тип ребра/выработки
                        $edge1_place_id = $edges->place_id;                                                             // ребро 1 ID места
                        $edge1_object_id = $edges->place->object_id;
                        //вершина 1.1
                        if ($conjunctions = Conjunction::find()->where(['id' => $edge1_conjunction_start_id])->one()) {
                            $edge1_conjunction_start_x = $conjunctions->x;                                              // ребро 1 X старт
                            $edge1_conjunction_start_y = $conjunctions->y;                                              // ребро 1 Y старт
                            $edge1_conjunction_start_z = $conjunctions->z;                                              // ребро 1 Z старт
                        }
                        //вершина 1.2
                        $edge1_conjunction_end_id = $edges->conjunction_end_id;                                         // ребро 1 сопряжение конец
                        if ($conjunctions = Conjunction::find()->where(['id' => $edge1_conjunction_end_id])->one()) {
                            $edge1_conjunction_end_x = $conjunctions->x;                                                // ребро 1 X конец
                            $edge1_conjunction_end_y = $conjunctions->y;                                                // ребро 1 Y конец
                            $edge1_conjunction_end_z = $conjunctions->z;                                                // ребро 1 Z конец
                        }
                        if ($edge_parameter_id = EdgeParameter::find()->where(['edge_id' => $edges->id, 'parameter_id' => 164, 'parameter_type_id' => 1])->one())//ищем 164 параметр у старого edga
                        {
                            $edge_param_handbook_id = ObjectFunctions::AddObjectParameterHandbookValue('edge', $edge_parameter_id->id, 1, 19, 1);//пишем 19 -неактуальна для старого edga
                            if ($edge_param_handbook_id == -1)                                                          // если не сохранилась, то пишем ошибку
                            {
                                $errors[] = 'Не удалось сохранить справочное значение выработки(актуальность)' . $edges->id;
                            }
                        }
                        $edge_status = new EdgeStatus();                                                                // в таблицу статусов edge пишем так же статус старой выработки, что она неактуальная
                        $edge_status->edge_id = $edges->id;
                        $edge_status->status_id = 19;
                        $edge_status->date_time = date('Y-m-d H:i:s.U', strtotime('-1 second'));
                        if ($edge_status->save()) {
                            $delete_edge_list[] = $edges->id;
                        } else {
                            $errors[] = 'Ошибка сохранения статуса выработки в таблице edge_status' . $edges->id;
                        }
                    }
                    //ребро 2
                    $edge2_id = $post['edge2_id'];                                                                         //ребро 2 ID ребра

                    if ($edges = Edge::find()->with('place')->where(['id' => $edge2_id])->one()) {
                        $edge2_conjunction_start_id = $edges->conjunction_start_id;                                      //ребро 2 сопряжение начало
                        $edge2_edge_type_id = $edges->edge_type_id;                                                          //ребро 2 тип ребра
                        $edge2_place_id = $edges->place_id;                                                          //ребро 2 ID места
                        $edge2_object_id = $edges->place->object_id;
                        //вершина 2.1
                        if ($conjunctions = Conjunction::find()->where(['id' => $edge2_conjunction_start_id])->one()) {
                            $edge2_conjunction_start_x = $conjunctions->x;                                        //ребро 2 X старт
                            $edge2_conjunction_start_y = $conjunctions->y;                                        //ребро 2 Y старт
                            $edge2_conjunction_start_z = $conjunctions->z;                                        //ребро 2 Z старт
                        }
                        //вершина 2.2
                        $edge2_conjunction_end_id = $edges->conjunction_end_id;                                          //ребро 2 сопряжение конец
                        if ($conjunctions = Conjunction::find()->where(['id' => $edge2_conjunction_end_id])->one()) {
                            $edge2_conjunction_end_x = $conjunctions->x;                                            //ребро 2 X конец
                            $edge2_conjunction_end_y = $conjunctions->y;                                            //ребро 2  конец
                            $edge2_conjunction_end_z = $conjunctions->z;                                            //ребро  Z конец
                        }
                        if ($edge_parameter_id = EdgeParameter::find()->where(['edge_id' => $edges->id, 'parameter_id' => 164, 'parameter_type_id' => 1])->one())//ищем 164 параметр у старого edga2
                        {
                            $edge_param_handbook_id = ObjectFunctions::AddObjectParameterHandbookValue('edge', $edge_parameter_id->id, 1, 19, 1);//пишем 19 -неактуальна для старого edga2
                            if ($edge_param_handbook_id == -1)                                              //если не сохранилась, то пишем ошибку
                            {
                                $errors[] = 'Не удалось сохранить справочное значение выработки(актуальность)' . $edges->id;
                            }
                        }
                        $edge_status = new EdgeStatus();                                                    // в таблицу статусов edge пишем так же старой выработки2 что она неактуальная
                        $edge_status->edge_id = $edges->id;
                        $edge_status->status_id = 19;
                        $edge_status->date_time = date('Y-m-d H:i:s.U', strtotime('-1 second'));
                        if ($edge_status->save()) {
                            $delete_edge_list[] = $edges->id;
                        } else {
                            $errors[] = 'Ошибка сохранения статуса выработки в таблице edge_status' . $edges->id;
                        }
                    }
                    $edge_new_length = pow((pow(($edge_new_conjunction_start_x - $edge_new_conjunction_end_x), 2) + pow(($edge_new_conjunction_start_y - $edge_new_conjunction_end_y), 2) + pow(($edge_new_conjunction_start_z - $edge_new_conjunction_end_z), 2)), 0.5);
                    $edge11_lenght = pow((pow(($edge_new_conjunction_start_x - $edge1_conjunction_start_x), 2) + pow(($edge_new_conjunction_start_y - $edge1_conjunction_start_y), 2) + pow(($edge_new_conjunction_start_z - $edge1_conjunction_start_z), 2)), 0.5);
                    $edge12_lenght = pow((pow(($edge_new_conjunction_start_x - $edge1_conjunction_end_x), 2) + pow(($edge_new_conjunction_start_y - $edge1_conjunction_end_y), 2) + pow(($edge_new_conjunction_start_z - $edge1_conjunction_end_z), 2)), 0.5);
                    $edge21_lenght = pow((pow(($edge_new_conjunction_end_x - $edge2_conjunction_start_x), 2) + pow(($edge_new_conjunction_end_y - $edge2_conjunction_start_y), 2) + pow(($edge_new_conjunction_end_z - $edge2_conjunction_start_z), 2)), 0.5);
                    $edge22_lenght = pow((pow(($edge_new_conjunction_end_x - $edge2_conjunction_end_x), 2) + pow(($edge_new_conjunction_end_y - $edge2_conjunction_end_y), 2) + pow(($edge_new_conjunction_end_z - $edge2_conjunction_end_z), 2)), 0.5);
                }

                /*********************    СОЗДАНИЕ САМОЙ ВЫРАБОТКИ (РЕБРА)    ***************/
                {
                    $response = SpecificConjunctionController::AddConjunction($edge_new_mine_id, $edge_new_conjunction_start_x, $edge_new_conjunction_start_y, $edge_new_conjunction_start_z);
                    if ($response['status'] == 1) {
                        $edge_new_conjunction_start_id = $response['conjunction_id'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new Exception('actionMergeEdge. Ошибка добавления сопряжения');
                    }

                    $response = SpecificConjunctionController::AddConjunction($edge_new_mine_id, $edge_new_conjunction_end_x, $edge_new_conjunction_end_y, $edge_new_conjunction_end_z);
                    if ($response['status'] == 1) {
                        $edge_new_conjunction_end_id = $response['conjunction_id'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new Exception('actionMergeEdge. Ошибка добавления сопряжения');
                    }

                    /*********   Копирование типовых параметров place в конкретный edge     *********/
                    if ($find_object_by_id) {
                        $response = $this->actionCopyTypicalParametersToSpecificToEdge($find_object_by_id, $edge_new_id);// копируем все place_parameter_value, place_parameter_handbook_value в edge
                        if ($response['status'] != 1) {
                            $errors[] = $response['errors'];
                            $warnings[] = $response['warnings'];
                            throw new Exception("actionMergeEdge. Ошибка копирования типовых параметров объкта места в edge_id = $edge_new_id");
                        }
                    }
                    $response = EdgeBasicController::AddEdge($edge_new_place_id, $edge_new_conjunction_start_id, $edge_new_conjunction_end_id, $edge_new_edge_type_id);
                    if ($response['status'] == 1) {
                        $edge_new_id = $response['edge_id'];
                        $add_edge_list[] = $response['edge_id'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new Exception('actionMergeEdge. Ошибка добавления выработки в БД выработки');
                    }
//                                $edge_new_id = self::actionAddEdge($edge_new_place_id, $edge_new_conjunction_start_id, $edge_new_conjunction_end_id, $edge_new_edge_type_id);
                    $edge_new_id_to_send = $edge_new_id;
                    /****************** Создание параметров выработки   *******************/
                    {
                        $success_param_insert = $this->InsertParamsOnCreateEdge($edge_new_id, $edge_new_length, $edge_new_height, $edge_new_width, $edge_new_section, $edge_new_danger_zone, $edge_new_conveyor, $edge_new_color_edge, 1, $edge_new_value_co, $edge_new_value_ch, $edge_new_conveyor_tag);
                    }
                    /******************* РЕБРО 1.1  *******************/
                    {
                        $response = EdgeBasicController::AddEdge($edge1_place_id, $edge1_conjunction_start_id, $edge_new_conjunction_start_id, $edge1_edge_type_id);    //копирование параметров и их значений новой ветви
                        if ($response['status'] == 1) {
                            $edge_new_id = $response['edge_id'];
                            $add_edge_list[] = $response['edge_id'];
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                        } else {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                            throw new Exception('actionMergeEdge. Ошибка добавления выработки в БД выработки');
                        }
//                                    $edge_new_id = self::actionAddEdge($edge1_place_id, $edge1_conjunction_start_id, $edge_new_conjunction_start_id, $edge1_edge_type_id);    //копирование параметров и их значений новой ветви
                        $edge_new_id_1_1 = $edge_new_id;
                        /*********   Копирование типовых параметров place в конкретный edge     *********/
                        if ($edge1_object_id) {
                            $response = $this->actionCopyTypicalParametersToSpecificToEdge($edge1_object_id, $edge_new_id);// копируем все place_parameter_value, place_parameter_handbook_value в edge
                            if ($response['status'] != 1) {
                                $errors[] = $response['errors'];
                                $warnings[] = $response['warnings'];
                                throw new Exception("actionMergeEdge. Ошибка копирования типовых параметров объкта места в edge_id = $edge_new_id");
                            }
                        }
                        $response = self::actionCopyEdge($edge_new_id, $edge1_id, 'copy', $edge11_lenght, 1);
                        if ($response['status'] != 1) {
                            $errors[] = $response['errors'];
                            $warnings[] = $response['warnings'];
                            throw new Exception("actionMergeEdge. Ошибка копирования параметров объкта места в edge_id = $edge1_id");
                        }

                    }
                    /******************* РЕБРО 1.2  *******************/
                    {
                        $response = EdgeBasicController::AddEdge($edge1_place_id, $edge1_conjunction_end_id, $edge_new_conjunction_start_id, $edge1_edge_type_id);  //копирование параметров и их значений новой ветви
                        if ($response['status'] == 1) {
                            $edge_new_id = $response['edge_id'];
                            $add_edge_list[] = $response['edge_id'];
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                        } else {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                            throw new Exception('actionMergeEdge. Ошибка добавления выработки в БД выработки');
                        }
                        $edge_new_id_1_2 = $edge_new_id;
                        /*********   Копирование типовых параметров place в конкретный edge     *********/
                        if ($edge1_object_id) {
                            $response = $this->actionCopyTypicalParametersToSpecificToEdge($edge1_object_id, $edge_new_id);// копируем все place_parameter_value, place_parameter_handbook_value в edge
                            if ($response['status'] != 1) {
                                $errors[] = $response['errors'];
                                $warnings[] = $response['warnings'];
                                throw new Exception("actionMergeEdge. Ошибка копирования типовых параметров объкта места в edge_id = $edge_new_id");
                            }
                        }
                        $response = self::actionCopyEdge($edge_new_id, $edge1_id, 'copy', $edge12_lenght, 1);
                        if ($response['status'] != 1) {
                            $errors[] = $response['errors'];
                            $warnings[] = $response['warnings'];
                            throw new Exception("actionMergeEdge. Ошибка копирования параметров объкта места в edge_id = $edge1_id");
                        }

                    }

                    /******************* РЕБРО 2.1  *******************/
                    {
                        $response = EdgeBasicController::AddEdge($edge2_place_id, $edge2_conjunction_start_id, $edge_new_conjunction_end_id, $edge2_edge_type_id);
                        if ($response['status'] == 1) {
                            $edge_new_id = $response['edge_id'];
                            $add_edge_list[] = $response['edge_id'];
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                        } else {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                            throw new Exception('actionMergeEdge. Ошибка добавления выработки в БД выработки');
                        }
                        $edge_new_id_2_1 = $edge_new_id;
                        /*********   Копирование типовых параметров place в конкретный edge     *********/
                        if ($edge2_object_id) {
                            $response = $this->actionCopyTypicalParametersToSpecificToEdge($edge2_object_id, $edge_new_id);// копируем все place_parameter_value, place_parameter_handbook_value в edge
                            if ($response['status'] != 1) {
                                $errors[] = $response['errors'];
                                $warnings[] = $response['warnings'];
                                throw new Exception("actionMergeEdge. Ошибка копирования типовых параметров объкта места в edge_id = $edge_new_id");
                            }
                        }
                        $response = self::actionCopyEdge($edge_new_id, $edge2_id, 'copy', $edge21_lenght, 1);                                                             //копирование параметров и их значений новой ветви
                        if ($response['status'] != 1) {
                            $errors[] = $response['errors'];
                            $warnings[] = $response['warnings'];
                            throw new Exception("actionMergeEdge. Ошибка копирования параметров объкта места в edge_id = $edge2_id");
                        }

                    }

                    /******************* РЕБРО 2.2  *******************/
                    {
                        $response = EdgeBasicController::AddEdge($edge2_place_id, $edge2_conjunction_end_id, $edge_new_conjunction_end_id, $edge2_edge_type_id);
                        if ($response['status'] == 1) {
                            $edge_new_id = $response['edge_id'];
                            $add_edge_list[] = $response['edge_id'];
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                        } else {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                            throw new Exception('actionMergeEdge. Ошибка добавления выработки в БД выработки');
                        }
                        $edge_new_id_2_2 = $edge_new_id;
                        /*********   Копирование типовых параметров place в конкретный edge     *********/
                        if ($edge2_object_id) {
                            $response = $this->actionCopyTypicalParametersToSpecificToEdge($edge2_object_id, $edge_new_id);// копируем все place_parameter_value, place_parameter_handbook_value в edge
                            if ($response['status'] != 1) {
                                $errors[] = $response['errors'];
                                $warnings[] = $response['warnings'];
                                throw new Exception("actionMergeEdge. Ошибка копирования типовых параметров объкта места в edge_id = $edge_new_id");
                            }
                        }
                        $response = self::actionCopyEdge($edge_new_id, $edge2_id, 'copy', $edge22_lenght, 1);                                                             //копирование параметров и их значений новой ветви
                        if ($response['status'] != 1) {
                            $errors[] = $response['errors'];
                            $warnings[] = $response['warnings'];
                            throw new Exception("actionMergeEdge. Ошибка копирования параметров объкта места в edge_id = $edge1_id");
                        }


                        /***************************** ЗАПИСЫВАЕМ ИЗМЕНЕНИЯ ВЫРАБОТОК *****************************************/
                        $mas_edge_id = array();
                        $mas_edge_id[] = $edge1_id;
                        $mas_edge_id[] = $edge2_id;
                        $mas_edge_id[] = $edge_new_id_to_send;
                        $mas_edge_id[] = $edge_new_id_1_1;
                        $mas_edge_id[] = $edge_new_id_1_2;
                        $mas_edge_id[] = $edge_new_id_2_1;
                        $mas_edge_id[] = $edge_new_id_2_2;
                        $edge_change = EdgeHistoryController::AddEdgeChange($mas_edge_id);


                        /***************************** ДОБАВЛЯЕМ НОВУЮ ВЫРАБОТКУ В КЭШ (Параметры, значения, схема)    ************/
                        $flag_cache_done = $edge_cache_controller->runInit($edge_new_mine_id, $edge_new_id_to_send)['status'];
                        $flag_cache_done = $edge_cache_controller->runInit($edge_new_mine_id, $edge_new_id_1_1)['status'];
                        $flag_cache_done = $edge_cache_controller->runInit($edge_new_mine_id, $edge_new_id_1_2)['status'];
                        $flag_cache_done = $edge_cache_controller->runInit($edge_new_mine_id, $edge_new_id_2_1)['status'];
                        $flag_cache_done = $edge_cache_controller->runInit($edge_new_mine_id, $edge_new_id_2_2)['status'];
                        /***************************** ПЕРЕБРАСЫВАЕМ СЕНСОРЫ СО СТАРОЙ ВЫРАБОТКИ В НОВУЮ  **********************/
                        $response = self::EdgeReplaceSensors($edge1_id, $edge_new_id_1_1, $edge_new_id_1_2, $edge_new_mine_id);
                        if ($response['status'] == 1) {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                        } else {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                        }
                        /***************************** ПЕРЕБРАСЫВАЕМ СЕНСОРЫ СО СТАРОЙ ВЫРАБОТКИ В НОВУЮ  **********************/
                        $response = self::EdgeReplaceSensors($edge2_id, $edge_new_id_2_1, $edge_new_id_2_2, $edge_new_mine_id);
                        if ($response['status'] == 1) {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                        } else {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                        }
                        /***************************** УДАЛЯЕМ СТАРУЮ ВЫРАБОТКУ ИЗ КЭША   *****************************************/
                        $flag_cache_done = EdgeMainController::DeleteEdge($edge1_id, $edge_new_mine_id)['status'];
                        $flag_cache_done = EdgeMainController::DeleteEdge($edge2_id, $edge_new_mine_id)['status'];
                    }

                }
            }

            /*********************************    СОЗДАНИЕ ВЫРАБОТКИ В СЕРЕДИНЕ И В ПОЛЕ (ТИП: ЧЕТВЕРТЫЙ) *************/
            if ($edge_type_add == 4 and $edge_new_place_id > 0) {
                /*********************    ПОЛУЧЕНИЕ ДАННЫХ    *******************************/
                {
                    //новое ребро - которое создаем
                    //вершина 1
                    $edge_new_conjunction_start_id = 0;                                                                         //ребро новое сопряжение начало
                    $edge_new_conjunction_start_x = $post['conjunction_start_x'];                                      //ребро новое X старт
                    $edge_new_conjunction_start_y = $post['conjunction_start_y'];                                      //ребро новое Y старт
                    $edge_new_conjunction_start_z = $post['conjunction_start_z'];                                      //ребро новое Z старт
                    //вершина2
                    $edge_new_conjunction_end_id = 0;                                                                           //ребро новое сопряжение конец
                    $edge_new_conjunction_end_x = $post['conjunction_end_x'];                                          //ребро новое X конец
                    $edge_new_conjunction_end_y = $post['conjunction_end_y'];                                          //ребро новое Y конец
                    $edge_new_conjunction_end_z = $post['conjunction_end_z'];                                          //ребро новое Z конец

                    //ребро 1
                    $edge1_id = $post['edge1_id'];                                                                         //ребро 1 ID ребра

                    if ($edges = Edge::find()->with('place')->where(['id' => $edge1_id])->one()) {
                        $edge1_conjunction_start_id = $edges->conjunction_start_id;                                      //ребро 1 сопряжение начало
                        $edge1_edge_type_id = $edges->edge_type_id;                                                          //ребро 1 тип ребра/выработки
                        $edge1_place_id = $edges->place_id;                                                          //ребро 1 ID места
                        $edge1_object_id = $edges->place->object_id;
                        //вершина 1.1
                        if ($conjunctions = Conjunction::find()->where(['id' => $edge1_conjunction_start_id])->one()) {
                            $edge1_conjunction_start_x = $conjunctions->x;                                        //ребро 1 X старт
                            $edge1_conjunction_start_y = $conjunctions->y;                                        //ребро 1 Y старт
                            $edge1_conjunction_start_z = $conjunctions->z;                                        //ребро 1 Z старт
                        }
                        //вершина 1.2
                        $edge1_conjunction_end_id = $edges->conjunction_end_id;                                          //ребро 1 сопряжение конец
                        if ($conjunctions = Conjunction::find()->where(['id' => $edge1_conjunction_end_id])->one()) {
                            $edge1_conjunction_end_x = $conjunctions->x;                                            //ребро 1 X конец
                            $edge1_conjunction_end_y = $conjunctions->y;                                            //ребро 1 Y конец
                            $edge1_conjunction_end_z = $conjunctions->z;                                            //ребро 1 Z конец
                        }
                        if ($edge_parameter_id = EdgeParameter::find()->where(['edge_id' => $edges->id, 'parameter_id' => 164, 'parameter_type_id' => 1])->one())//ищем 164 параметр у старого edga
                        {
                            $edge_param_handbook_id = ObjectFunctions::AddObjectParameterHandbookValue('edge', $edge_parameter_id->id, 1, 19, 1);//пишем 19 -неактуальна для старого edga
                            if ($edge_param_handbook_id == -1)                                                          //если не сохранилась, то пишем ошибку
                            {
                                $errors[] = 'Не удалось сохранить справочное значение выработки(актуальность)' . $edges->id;
                            }
                        }
                        $edge_status = new EdgeStatus();                                                                // в таблицу статусов edge пишем так же статус старой выработки, что она неактуальная
                        $edge_status->edge_id = $edges->id;
                        $edge_status->status_id = 19;
                        $edge_status->date_time = date('Y-m-d H:i:s.U', strtotime('-1 second'));
                        if ($edge_status->save()) {
                            $delete_edge_list[] = $edges->id;
                        } else {
                            $errors[] = 'Ошибка сохранения статуса выработки в таблице edge_status' . $edges->id;
                        }
                    }


                    $edge_new_length = pow((pow(($edge_new_conjunction_start_x - $edge_new_conjunction_end_x), 2) + pow(($edge_new_conjunction_start_y - $edge_new_conjunction_end_y), 2) + pow(($edge_new_conjunction_start_z - $edge_new_conjunction_end_z), 2)), 0.5);
                    $edge11_lenght = pow((pow(($edge_new_conjunction_start_x - $edge1_conjunction_start_x), 2) + pow(($edge_new_conjunction_start_y - $edge1_conjunction_start_y), 2) + pow(($edge_new_conjunction_start_z - $edge1_conjunction_start_z), 2)), 0.5);
                    $edge12_lenght = pow((pow(($edge_new_conjunction_start_x - $edge1_conjunction_end_x), 2) + pow(($edge_new_conjunction_start_y - $edge1_conjunction_end_y), 2) + pow(($edge_new_conjunction_start_z - $edge1_conjunction_end_z), 2)), 0.5);
                }

                /*********************    СОЗДАНИЕ САМОЙ ВЫРАБОТКИ (РЕБРА)    ***************/
                {
                    if ($debug_flag == 1) echo nl2br('зашел в построение сбойки ' . "\n");

                    $response = SpecificConjunctionController::AddConjunction($edge_new_mine_id, $edge_new_conjunction_start_x, $edge_new_conjunction_start_y, $edge_new_conjunction_start_z);
                    if ($response['status'] == 1) {
                        $edge_new_conjunction_start_id = $response['conjunction_id'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new Exception('actionMergeEdge. Ошибка добавления сопряжения');
                    }

                    $response = SpecificConjunctionController::AddConjunction($edge_new_mine_id, $edge_new_conjunction_end_x, $edge_new_conjunction_end_y, $edge_new_conjunction_end_z);
                    if ($response['status'] == 1) {
                        $edge_new_conjunction_end_id = $response['conjunction_id'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new Exception('actionMergeEdge. Ошибка добавления сопряжения');
                    }

                    $response = EdgeBasicController::AddEdge($edge_new_place_id, $edge_new_conjunction_start_id, $edge_new_conjunction_end_id, $edge_new_edge_type_id);
                    if ($response['status'] == 1) {
                        $edge_new_id = $response['edge_id'];
                        $add_edge_list[] = $response['edge_id'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new Exception('actionMergeEdge. Ошибка добавления выработки в БД выработки');
                    }
//                                $edge_new_id = self::actionAddEdge($edge_new_place_id, $edge_new_conjunction_start_id, $edge_new_conjunction_end_id, $edge_new_edge_type_id);
                    /*********   Копирование типовых параметров place в конкретный edge     *********/
                    if ($find_object_by_id) {
                        $response = $this->actionCopyTypicalParametersToSpecificToEdge($find_object_by_id, $edge_new_id);// копируем все place_parameter_value, place_parameter_handbook_value в edge
                        if ($response['status'] != 1) {
                            $errors[] = $response['errors'];
                            $warnings[] = $response['warnings'];
                            throw new Exception("actionMergeEdge. Ошибка копирования типовых параметров объкта места в edge_id = $edge_new_id");
                        }
                    }
                    $edge_new_id_to_send = $edge_new_id;
                    /****************** Создание параметров выработки   *******************/
                    {
                        $success_param_insert = $this->InsertParamsOnCreateEdge($edge_new_id, $edge_new_length, $edge_new_height, $edge_new_width, $edge_new_section, $edge_new_danger_zone, $edge_new_conveyor, $edge_new_color_edge, 1, $edge_new_value_co, $edge_new_value_ch, $edge_new_conveyor_tag);
                    }


                    /******************* РЕБРО 1.1 *******************/
                    {
                        $response = EdgeBasicController::AddEdge($edge1_place_id, $edge1_conjunction_start_id, $edge_new_conjunction_start_id, $edge1_edge_type_id);
                        if ($response['status'] == 1) {
                            $edge_new_id = $response['edge_id'];
                            $add_edge_list[] = $response['edge_id'];
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                        } else {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                            throw new Exception('actionMergeEdge. Ошибка добавления выработки в БД выработки');
                        }
                        $edge_new_id_1_1 = $edge_new_id;

                        /*********   Копирование типовых параметров place в конкретный edge     *********/
                        if ($edge1_object_id) {
                            $response = $this->actionCopyTypicalParametersToSpecificToEdge($edge1_object_id, $edge_new_id);// копируем все place_parameter_value, place_parameter_handbook_value в edge
                            if ($response['status'] != 1) {
                                $errors[] = $response['errors'];
                                $warnings[] = $response['warnings'];
                                throw new Exception("actionMergeEdge. Ошибка копирования типовых параметров объкта места в edge_id = $edge_new_id");
                            }
                        }
                        $response = self::actionCopyEdge($edge_new_id, $edge1_id, 'copy', $edge11_lenght, 1);                                                             //копирование параметров и их значений новой ветви
                        if ($response['status'] != 1) {
                            $errors[] = $response['errors'];
                            $warnings[] = $response['warnings'];
                            throw new Exception("actionMergeEdge. Ошибка копирования параметров объкта места в edge_id = $edge1_id");
                        }
                    }

                    /******************* РЕБРО 1.2 *******************/
                    {
                        $response = EdgeBasicController::AddEdge($edge1_place_id, $edge1_conjunction_end_id, $edge_new_conjunction_start_id, $edge1_edge_type_id);
                        if ($response['status'] == 1) {
                            $edge_new_id = $response['edge_id'];
                            $add_edge_list[] = $response['edge_id'];
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                        } else {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                            throw new Exception('actionMergeEdge. Ошибка добавления выработки в БД выработки');
                        }
//                                    $edge_new_id = self::actionAddEdge($edge1_place_id, $edge1_conjunction_end_id, $edge_new_conjunction_start_id, $edge1_edge_type_id);
                        $edge_new_id_1_2 = $edge_new_id;

                        /*********   Копирование типовых параметров place в конкретный edge     *********/
                        if ($edge1_object_id) {
                            $response = $this->actionCopyTypicalParametersToSpecificToEdge($edge1_object_id, $edge_new_id);// копируем все place_parameter_value, place_parameter_handbook_value в edge
                            if ($response['status'] != 1) {
                                $errors[] = $response['errors'];
                                $warnings[] = $response['warnings'];
                                throw new Exception("actionMergeEdge. Ошибка копирования типовых параметров объкта места в edge_id = $edge_new_id");
                            }
                        }
                        $response = self::actionCopyEdge($edge_new_id, $edge1_id, 'copy', $edge12_lenght, 1);                                                             //копирование параметров и их значений новой ветви
                        if ($response['status'] != 1) {
                            $errors[] = $response['errors'];
                            $warnings[] = $response['warnings'];
                            throw new Exception("actionMergeEdge. Ошибка копирования параметров объкта места в edge_id = $edge1_id");
                        }


                        /***************************** ЗАПИСЫВАЕМ ИЗМЕНЕНИЯ ВЫРАБОТОК *****************************************/
                        $mas_edge_id = array();
                        $mas_edge_id[] = $edge1_id;
                        $mas_edge_id[] = $edge_new_id_to_send;
                        $mas_edge_id[] = $edge_new_id_1_1;
                        $mas_edge_id[] = $edge_new_id_1_2;
                        $edge_change = EdgeHistoryController::AddEdgeChange($mas_edge_id);

                        /***************************** ДОБАВЛЯЕМ НОВУЮ ВЫРАБОТКУ В КЭШ (Параметры, значения, схема)    ************/
                        $flag_cache_done = $edge_cache_controller->runInit($edge_new_mine_id, $edge_new_id_to_send)['status'];
                        $flag_cache_done = $edge_cache_controller->runInit($edge_new_mine_id, $edge_new_id_1_1)['status'];
                        $flag_cache_done = $edge_cache_controller->runInit($edge_new_mine_id, $edge_new_id_1_2)['status'];
                        /***************************** ПЕРЕБРАСЫВАЕМ СЕНСОРЫ СО СТАРОЙ ВЫРАБОТКИ В НОВУЮ  **********************/
                        $response = self::EdgeReplaceSensors($edge1_id, $edge_new_id_1_1, $edge_new_id_1_2, $edge_new_mine_id);
                        if ($response['status'] == 1) {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                        } else {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                        }
                        /***************************** УДАЛЯЕМ СТАРУЮ ВЫРАБОТКУ ИЗ КЭША   *****************************************/
                        $flag_cache_done = EdgeMainController::DeleteEdge($edge1_id, $edge_new_mine_id)['status'];

                    }
                }
            }

            /*********************************    СОЗДАНИЕ ВЫРАБОТКИ (СОПРЯЖЕНИЕ-СОПРЯЖЕНИЕ) (ТИП: ПЯТЫЙ) *************/
            if ($edge_type_add == 5 and $edge_new_place_id > 0) {

                /*********************    ПОЛУЧЕНИЕ ДАННЫХ    *******************************/
                {
                    //ребро 1
                    //вершина 1.1
                    $edge1_conjunction_start_id = $post['edge1_conjunction_start_id'];                                      //ребро 1 сопряжение начало
                    if ($conjunctions = Conjunction::find()->where(['id' => $edge1_conjunction_start_id])->one()) {
                        $edge1_conjunction_start_x = $conjunctions->x;                                        //ребро 1 X старт
                        $edge1_conjunction_start_y = $conjunctions->y;                                        //ребро 1 Y старт
                        $edge1_conjunction_start_z = $conjunctions->z;                                        //ребро 1 Z старт
                    }

                    $edge2_conjunction_end_id = $post['edge2_conjunction_end_id'];                                      //ребро 1 сопряжение начало
                    if ($conjunctions = Conjunction::find()->where(['id' => $edge2_conjunction_end_id])->one()) {
                        $edge2_conjunction_end_x = $conjunctions->x;                                        //ребро 1 X старт
                        $edge2_conjunction_end_y = $conjunctions->y;                                        //ребро 1 Y старт
                        $edge2_conjunction_end_z = $conjunctions->z;                                        //ребро 1 Z старт
                    }
                    $edge_new_length = pow((pow(($edge1_conjunction_start_x - $edge2_conjunction_end_x), 2) + pow(($edge1_conjunction_start_y - $edge2_conjunction_end_y), 2) + pow(($edge1_conjunction_start_z - $edge2_conjunction_end_z), 2)), 0.5);
                }

                /*********************    СОЗДАНИЕ САМОЙ ВЫРАБОТКИ (РЕБРА)    ***************/
                {
                    if ($debug_flag == 1) echo nl2br('зашел в построение тупиковой выработки' . "\n");
                    $response = EdgeBasicController::AddEdge($edge_new_place_id, $edge1_conjunction_start_id, $edge2_conjunction_end_id, $edge_new_edge_type_id);
                    if ($response['status'] == 1) {
                        $edge_new_id = $response['edge_id'];
                        $add_edge_list[] = $response['edge_id'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new Exception('actionMergeEdge. Ошибка добавления выработки в БД выработки');
                    }
//                                $edge_new_id = self::actionAddEdge($edge_new_place_id, $edge1_conjunction_start_id, $edge2_conjunction_end_id, $edge_new_edge_type_id);
                    /*********   Копирование типовых параметров place в конкретный edge     *********/
                    {
                        if ($find_object_by_id) {
                            $response = $this->actionCopyTypicalParametersToSpecificToEdge($find_object_by_id, $edge_new_id);// копируем все place_parameter_value, place_parameter_handbook_value в edge
                            if ($response['status'] != 1) {
                                $errors[] = $response['errors'];
                                $warnings[] = $response['warnings'];
                                throw new Exception("actionMergeEdge. Ошибка копирования типовых параметров объкта места в edge_id = $edge_new_id");
                            }
                        }
                    }
                    $edge_new_id_to_send = $edge_new_id;
                    /****************** Создание параметров выработки   *******************/
                    {
                        $success_param_insert = $this->InsertParamsOnCreateEdge($edge_new_id, $edge_new_length, $edge_new_height, $edge_new_width, $edge_new_section, $edge_new_danger_zone, $edge_new_conveyor, $edge_new_color_edge, 1, $edge_new_value_co, $edge_new_value_ch, $edge_new_conveyor_tag);
                    }

                    /***************************** ЗАПИСЫВАЕМ ИЗМЕНЕНИЯ ВЫРАБОТОК *****************************************/
                    $mas_edge_id = array();
                    $mas_edge_id[] = $edge_new_id;
                    $edge_change = EdgeHistoryController::AddEdgeChange($mas_edge_id);
                    /***************************** ДОБАВЛЯЕМ НОВУЮ ВЫРАБОТКУ В КЭШ (Параметры, значения, схема)    ************/
                    {
                        $flag_cache_done = $edge_cache_controller->runInit($edge_new_mine_id, $edge_new_id)['status'];
                    }

                }
            }
            /*********************************    СОЗДАНИЕ ВЫРАБОТКИ (ВЫРАБОТКА-СОПРЯЖЕНИЕ) (ТИП: ШЕСТОЙ) *************/
            if ($edge_type_add == 6 and $edge_new_place_id > 0) {
                /*********************    ПОЛУЧЕНИЕ ДАННЫХ    *******************************/
                {
                    //новое ребро - которое создаем
                    //вершина 1
                    $edge_new_conjunction_start_id = 0;                                                                         //ребро новое сопряжение начало
                    $edge_new_conjunction_start_x = $post['conjunction_start_x'];                                      //ребро новое X старт
                    $edge_new_conjunction_start_y = $post['conjunction_start_y'];                                      //ребро новое Y старт
                    $edge_new_conjunction_start_z = $post['conjunction_start_z'];                                      //ребро новое Z старт
                    //вершина 2
                    $edge2_conjunction_end_id = $post['edge2_conjunction_end_id'];                                      //сопряжение начало
                    if ($conjunctions = Conjunction::find()->where(['id' => $edge2_conjunction_end_id])->one()) {
                        $edge2_conjunction_end_x = $conjunctions->x;                                        //сопряжение 2 X конец
                        $edge2_conjunction_end_y = $conjunctions->y;                                        //сопряжение 2 Y конец
                        $edge2_conjunction_end_z = $conjunctions->z;                                        //сопряжение 2 Z конец
                    }
                    if ($edge2_conjunction_end_x == $post['conjunction_start_x'] && $edge2_conjunction_end_y == $post['conjunction_start_y'] && $edge2_conjunction_end_z == $post['conjunction_start_z']) {
                        $edge_new_conjunction_start_x = $post['conjunction_end_x'];                                      //ребро новое X старт
                        $edge_new_conjunction_start_y = $post['conjunction_end_y'];                                      //ребро новое Y старт
                        $edge_new_conjunction_start_z = $post['conjunction_end_z'];                                      //ребро новое Z старт
                    }

                    //ребро 1
                    $edge1_id = $post['edge1_id'];                                                                         //ребро 1 ID ребра

                    if ($edges = Edge::find()->with('place')->where(['id' => $edge1_id])->one()) {
                        $edge1_conjunction_start_id = $edges->conjunction_start_id;                                      //ребро 1 сопряжение начало
                        $edge1_edge_type_id = $edges->edge_type_id;                                                          //ребро 1 тип ребра/выработки
                        $edge1_place_id = $edges->place_id;                                                          //ребро 1 ID места
                        $edge1_object_id = $edges->place->object_id;
                        //вершина 1.1
                        if ($conjunctions = Conjunction::find()->where(['id' => $edge1_conjunction_start_id])->one()) {
                            $edge1_conjunction_start_x = $conjunctions->x;                                        //ребро 1 X старт
                            $edge1_conjunction_start_y = $conjunctions->y;                                        //ребро 1 Y старт
                            $edge1_conjunction_start_z = $conjunctions->z;                                        //ребро 1 Z старт
                        }
                        //вершина 1.2
                        $edge1_conjunction_end_id = $edges->conjunction_end_id;                                          //ребро 1 сопряжение конец
                        if ($conjunctions = Conjunction::find()->where(['id' => $edge1_conjunction_end_id])->one()) {
                            $edge1_conjunction_end_x = $conjunctions->x;                                            //ребро 1 X конец
                            $edge1_conjunction_end_y = $conjunctions->y;                                            //ребро 1 Y конец
                            $edge1_conjunction_end_z = $conjunctions->z;                                            //ребро 1 Z конец
                        }
                        if ($edge_parameter_id = EdgeParameter::find()->where(['edge_id' => $edges->id, 'parameter_id' => 164, 'parameter_type_id' => 1])->one())//ищем 164 параметр у старой выработки
                        {
                            $edge_param_handbook_id = ObjectFunctions::AddObjectParameterHandbookValue('edge', $edge_parameter_id->id, 1, 19, 1);//записываем в справочник что старая выработка стала не актульной(19)
                            if ($edge_param_handbook_id == -1)                                              //если не сохранилось в бд то пишем ошибку
                            {
                                $errors[] = 'Не удалось сохранить справочное значение выработки(актуальность)' . $edges->id;
                            }
                        }
                        $edge_status = new EdgeStatus();                                                    //в таблицу статаусов edgей пишем что выработка стала не актуальной
                        $edge_status->edge_id = $edges->id;
                        $edge_status->status_id = 19;
                        $edge_status->date_time = date('Y-m-d H:i:s.U', strtotime('-1 second'));
                        if ($edge_status->save()) {
                            $delete_edge_list[] = $edges->id;
                        } else {
                            $errors[] = 'Ошибка сохранения статуса выработки в таблице edge_status' . $edges->id;
                        }
                    }


                    $edge_new_length = pow((pow(($edge_new_conjunction_start_x - $edge2_conjunction_end_x), 2) + pow(($edge_new_conjunction_start_y - $edge2_conjunction_end_y), 2) + pow(($edge_new_conjunction_start_z - $edge2_conjunction_end_z), 2)), 0.5);
                    $edge11_lenght = pow((pow(($edge_new_conjunction_start_x - $edge1_conjunction_start_x), 2) + pow(($edge_new_conjunction_start_y - $edge1_conjunction_start_y), 2) + pow(($edge_new_conjunction_start_z - $edge1_conjunction_start_z), 2)), 0.5);
                    $edge12_lenght = pow((pow(($edge_new_conjunction_start_x - $edge1_conjunction_end_x), 2) + pow(($edge_new_conjunction_start_y - $edge1_conjunction_end_y), 2) + pow(($edge_new_conjunction_start_z - $edge1_conjunction_end_z), 2)), 0.5);
                }

                /*********************    СОЗДАНИЕ САМОЙ ВЫРАБОТКИ (РЕБРА)    ***************/
                {
                    if ($debug_flag == 1) echo nl2br('зашел в построение сбойки ' . "\n");

                    $response = SpecificConjunctionController::AddConjunction($edge_new_mine_id, $edge_new_conjunction_start_x, $edge_new_conjunction_start_y, $edge_new_conjunction_start_z);
                    if ($response['status'] == 1) {
                        $edge_new_conjunction_start_id = $response['conjunction_id'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new Exception('actionMergeEdge. Ошибка добавления сопряжения');
                    }
                    /*********   Копирование типовых параметров place в конкретный edge     *********/
                    {
                        if ($find_object_by_id) {
                            $response = $this->actionCopyTypicalParametersToSpecificToEdge($find_object_by_id, $edge_new_id);// копируем все place_parameter_value, place_parameter_handbook_value в edge
                            if ($response['status'] != 1) {
                                $errors[] = $response['errors'];
                                $warnings[] = $response['warnings'];
                                throw new Exception("actionMergeEdge. Ошибка копирования типовых параметров объкта места в edge_id = $edge_new_id");
                            }
                        }
                    }
                    $response = EdgeBasicController::AddEdge($edge_new_place_id, $edge_new_conjunction_start_id, $edge2_conjunction_end_id, $edge_new_edge_type_id);
                    if ($response['status'] == 1) {
                        $edge_new_id = $response['edge_id'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new Exception('actionMergeEdge. Ошибка добавления выработки в БД выработки');
                    }
//                                $edge_new_id = self::actionAddEdge($edge_new_place_id, $edge_new_conjunction_start_id, $edge2_conjunction_end_id, $edge_new_edge_type_id);
                    $edge_new_id_to_send = $edge_new_id;
                    /****************** Создание параметров выработки   *******************/
                    {
                        $success_param_insert = $this->InsertParamsOnCreateEdge($edge_new_id, $edge_new_length, $edge_new_height, $edge_new_width, $edge_new_section, $edge_new_danger_zone, $edge_new_conveyor, $edge_new_color_edge, 1, $edge_new_value_co, $edge_new_value_ch, $edge_new_conveyor_tag);
                    }


                    /******************* РЕБРО 1.1 *******************/
                    {
                        $response = EdgeBasicController::AddEdge($edge1_place_id, $edge1_conjunction_start_id, $edge_new_conjunction_start_id, $edge1_edge_type_id);
                        if ($response['status'] == 1) {
                            $edge_new_id = $response['edge_id'];
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                        } else {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                            throw new Exception('actionMergeEdge. Ошибка добавления выработки в БД выработки');
                        }
//                                    $edge_new_id = self::actionAddEdge($edge1_place_id, $edge1_conjunction_start_id, $edge_new_conjunction_start_id, $edge1_edge_type_id);
                        $edge_new_id_1_1 = $edge_new_id;
                        $response = self::actionCopyEdge($edge_new_id, $edge1_id, 'copy', $edge11_lenght, 1);                                                             //копирование параметров и их значений новой ветви
                        if ($response['status'] != 1) {
                            $errors[] = $response['errors'];
                            $warnings[] = $response['warnings'];
                            throw new Exception("actionMergeEdge. Ошибка копирования параметров объкта места в edge_id = $edge1_id");
                        }
                    }

                    /******************* РЕБРО 1.2 *******************/
                    {
                        $response = EdgeBasicController::AddEdge($edge1_place_id, $edge1_conjunction_end_id, $edge_new_conjunction_start_id, $edge1_edge_type_id);
                        if ($response['status'] == 1) {
                            $edge_new_id = $response['edge_id'];
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                        } else {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                            throw new Exception('actionMergeEdge. Ошибка добавления выработки в БД выработки');
                        }
//                                    $edge_new_id = self::actionAddEdge($edge1_place_id, $edge1_conjunction_end_id, $edge_new_conjunction_start_id, $edge1_edge_type_id);
                        $edge_new_id_1_2 = $edge_new_id;
                        $response = self::actionCopyEdge($edge_new_id, $edge1_id, 'copy', $edge12_lenght, 1);                                                             //копирование параметров и их значений новой ветви
                        if ($response['status'] != 1) {
                            $errors[] = $response['errors'];
                            $warnings[] = $response['warnings'];
                            throw new Exception("actionMergeEdge. Ошибка копирования параметров объкта места в edge_id = $edge2_id");
                        }


                        /***************************** ЗАПИСЫВАЕМ ИЗМЕНЕНИЯ ВЫРАБОТОК *****************************************/
                        $mas_edge_id = array();
                        $mas_edge_id[] = $edge1_id;
                        $mas_edge_id[] = $edge_new_id_to_send;
                        $mas_edge_id[] = $edge_new_id_1_1;
                        $mas_edge_id[] = $edge_new_id_1_2;
                        $edge_change = EdgeHistoryController::AddEdgeChange($mas_edge_id);

                        /***************************** ДОБАВЛЯЕМ НОВУЮ ВЫРАБОТКУ В КЭШ (Параметры, значения, схема)    ************/
                        {
                            $flag_cache_done = $edge_cache_controller->runInit($edge_new_mine_id, $edge_new_id_to_send)['status'];
                            $flag_cache_done = $edge_cache_controller->runInit($edge_new_mine_id, $edge_new_id_1_1)['status'];
                            $flag_cache_done = $edge_cache_controller->runInit($edge_new_mine_id, $edge_new_id_1_2)['status'];
                        }
                        /***************************** ПЕРЕБРАСЫВАЕМ СЕНСОРЫ СО СТАРОЙ ВЫРАБОТКИ В НОВУЮ  **********************/
                        $response = self::EdgeReplaceSensors($edge1_id, $edge_new_id_1_1, $edge_new_id_1_2, $edge_new_mine_id);
                        if ($response['status'] == 1) {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                        } else {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                        }
                        /***************************** УДАЛЯЕМ СТАРУЮ ВЫРАБОТКУ ИЗ КЭША   *****************************************/
                        $flag_cache_done = EdgeMainController::DeleteEdge($edge1_id, $edge_new_mine_id)['status'];
                    }
                }
            }

            if ($edge1_place_id != -1) {
                $edges_places[] = $edge1_place_id;
            }
            if ($edge2_place_id != -1) {
                $edges_places[] = $edge2_place_id;
            }
            $edges_places[] = $edge_new_place_id;
            $edges_places_unic = array_unique($edges_places);

        } catch (Throwable $exception) {
            $errors[] = 'actionMergeEdge. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $items['add'] = $add_edge_list;
        $items['delete'] = $delete_edge_list;
        $items['change'] = $change_edge_list;
        $items['test'] = 'Raw';
        $warnings[] = 'actionMergeEdge. Окончил выполнение метода';
        $warnings = [];
        $result_array_to_return = array('Items' => $items, 'success_params_insert' => $success_param_insert, 'errors' => $errors, 'edge_id' => $edge_new_id_to_send, 'places_ids' => $edges_places_unic, 'status' => $status, 'warnings' => $warnings);
        unset($debug_log, $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_array_to_return;
    }

// http://localhost/unity/test4?edge1=1&edge1_1=1&edge1_2=1&mine_id=1
    public static function EdgeReplaceSensors($edge1, $edge1_1, $edge1_2, $mine_id, $edge_old_x = null, $edge_old_y = null, $edge_old_z = null, $edge_start_con = null)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'EdgeReplaceSensors. Начало выполнения метода';
        //Инициализируем переменные координат выработок
        $edge_old_x1 = 0;                                                                                               //координата начала делимой выработки x
        $edge_old_y1 = 0;                                                                                               //координата начала делимой выработки y
        $edge_old_z1 = 0;                                                                                               //координата начала делимой выработки z
        $edge_old_x2 = 0;                                                                                               //координата конца делимой выработки x
        $edge_old_y2 = 0;                                                                                               //координата конца делимой выработки y
        $edge_old_z2 = 0;                                                                                               //координата конца делимой выработки z

        $edge_1_1_x1 = 0;                                                                                               //координата начала новой 1 выработки x
        $edge_1_1_y1 = 0;                                                                                               //координата начала новой 1 выработки y
        $edge_1_1_z1 = 0;                                                                                               //координата начала новой 1 выработки z
        $edge_1_1_x2 = 0;                                                                                               //координата конца новой 1 выработки x
        $edge_1_1_y2 = 0;                                                                                               //координата конца новой 1 выработки y
        $edge_1_1_z2 = 0;                                                                                               //координата конца новой 1 выработки z

        $edge_1_2_x1 = 0;                                                                                               //координата начала новой 2 выработки x
        $edge_1_2_y1 = 0;                                                                                               //координата начала новой 2 выработки y
        $edge_1_2_z1 = 0;                                                                                               //координата начала новой 2 выработки z
        $edge_1_2_x2 = 0;                                                                                               //координата конца новой 2 выработки x
        $edge_1_2_y2 = 0;                                                                                               //координата конца новой 2 выработки y
        $edge_1_2_z2 = 0;                                                                                               //координата конца новой 2 выработки z
        try {
            if (isset($edge1) and $edge1 != ""                                                                              // Проверяем что пришли все данные в метод
                and isset($edge1_1) and $edge1_1 != ""
                and isset($edge1_2) and $edge1_2 != ""
                and isset($mine_id) and $mine_id != "") {
                $warnings[] = 'EdgeReplaceSensors. Все входные параметры переданы';
            } else {
                throw new Exception("EdgeReplaceSensors. Не переданы все параметры");
            }
            $edge_cache_controller = (new EdgeCacheController());
            $array_xyz_edge1 = $edge_cache_controller->getEdgeScheme($mine_id, $edge1);                                   //Ищем в кеше или в БД координаты разбиваемой выработки
            if (!$array_xyz_edge1)                                                                                  //Если у выработки нашли координаты то записываем их в переменные
            {
                throw new Exception("EdgeReplaceSensors. Для делимой выработки  =" . $edge1 . " нет координат начала и конца");
            }
            if ($edge_start_con != null and $edge_start_con = 1) {
                $edge_old_x1 = $edge_old_x;
                $edge_old_y1 = $edge_old_y;
                $edge_old_z1 = $edge_old_z;
                $edge_old_x2 = $array_xyz_edge1["xEnd"];
                $edge_old_y2 = $array_xyz_edge1["yEnd"];
                $edge_old_z2 = $array_xyz_edge1["zEnd"];
            } elseif ($edge_start_con != null and $edge_start_con = 0) {
                $edge_old_x1 = $array_xyz_edge1["xStart"];
                $edge_old_y1 = $array_xyz_edge1["yStart"];
                $edge_old_z1 = $array_xyz_edge1["zStart"];
                $edge_old_x2 = $edge_old_x;
                $edge_old_y2 = $edge_old_y;
                $edge_old_z2 = $edge_old_z;
            } else {
                $edge_old_x1 = $array_xyz_edge1["xStart"];
                $edge_old_y1 = $array_xyz_edge1["yStart"];
                $edge_old_z1 = $array_xyz_edge1["zStart"];
                $edge_old_x2 = $array_xyz_edge1["xEnd"];
                $edge_old_y2 = $array_xyz_edge1["yEnd"];
                $edge_old_z2 = $array_xyz_edge1["zEnd"];
            }

//todo переписать метод по получения списка сенсоров по искомому сенсору из кеша

            $sensors = (new Query())//Ищем в БД все сенсоры находящиеся на разбиваемой выработки
            ->select(['sensor_id'])
                ->from('view_sensor_parameter_edge_id_fill')
                ->where(['edge_id' => $edge1])
                ->all();
            if ($sensors)                                                                                               //Если у выработки нашли принадлежащие ей датчики
            {
                $array_xyz_edge1_1 = $edge_cache_controller->getEdgeScheme($mine_id, $edge1_1);                                   //Ищем в кеше или в БД координаты разбиваемой выработки
                if ($array_xyz_edge1_1 != -1)                                                                            //Если у выработки нашли координаты то записываем их в переменные
                {
                    $edge_1_1_x1 = $array_xyz_edge1_1["xStart"];
                    $edge_1_1_y1 = $array_xyz_edge1_1["yStart"];
                    $edge_1_1_z1 = $array_xyz_edge1_1["zStart"];
                    $edge_1_1_x2 = $array_xyz_edge1_1["xEnd"];
                    $edge_1_1_y2 = $array_xyz_edge1_1["yEnd"];
                    $edge_1_1_z2 = $array_xyz_edge1_1["zEnd"];
                } else {
                    throw new Exception("EdgeReplaceSensors. Для выработки  =" . $edge1_1 . "нет координат начала и конца");
                }
                $array_xyz_edge1_2 = $edge_cache_controller->getEdgeScheme($mine_id, $edge1_2);
                if ($array_xyz_edge1_2 != -1)                                                                            //Если у выработки нашли координаты то записываем их в переменные
                {
                    $edge_1_2_x1 = $array_xyz_edge1_2["xStart"];
                    $edge_1_2_y1 = $array_xyz_edge1_2["yStart"];
                    $edge_1_2_z1 = $array_xyz_edge1_2["zStart"];
                    $edge_1_2_x2 = $array_xyz_edge1_2["xEnd"];
                    $edge_1_2_y2 = $array_xyz_edge1_2["yEnd"];
                    $edge_1_2_z2 = $array_xyz_edge1_2["zEnd"];
                } else {
                    throw new Exception("EdgeReplaceSensors. Для выработки  =" . $edge1_2 . "нет координат начала и конца");
                }

                $date_time_now = \backend\controllers\Assistant::GetDateNow();
                $sensor_cache_controller = (new SensorCacheController());

                foreach ($sensors as $sensor)                                                                       //Начинаем перебирать сенсоры
                {
                    $sensor_cache = $sensor_cache_controller->getSensorMineBySensorOneHash($mine_id, $sensor['sensor_id']);
                    if ($sensor_cache) {
                        $warnings[] = 'EdgeReplaceSensors. сенсор найден в кеше' . $sensor['sensor_id'];
                    } else {
                        throw new Exception("EdgeReplaceSensors.нет сенсора в списке кеша " . $sensor['sensor_id']);
                    }

                    $parameter_type_id = SensorCacheController::isStaticSensor($sensor_cache['object_type_id']);
                    $sensors83 = $sensor_cache_controller->getParameterValueHash($sensor['sensor_id'], 83, $parameter_type_id);                  //Ищем координаты сенсора в кеше или в БД
                    $sensors269 = $sensor_cache_controller->getParameterValueHash($sensor['sensor_id'], 269, $parameter_type_id);          //Ищем значение выработки которой принадлежит сенсор в кеше или в БД
                    if ($sensors83 and $sensors269)                                                      //Если координаты и выработку нашли
                    {
                        $warnings[] = 'EdgeReplaceSensors. Параметры сенсора найдены' . $sensor['sensor_id'];
                    } else {
                        throw new Exception("EdgeReplaceSensors.Нет координат у текущего сенсора с id = " . $sensor['sensor_id']);
                    }

                    $coordinates = explode(",", $sensors83['value']);                                       //разбиваем  значение из строки в массив разделенный запятой и записываем их в соответсвующие переменные
                    $sensor_x = $coordinates[0];
                    $sensor_y = $coordinates[1];
                    $sensor_z = $coordinates[2];

                    /************* ищем выработку на какую будем перемещать текущий сенсор */
                    $L1 = sqrt(pow(($sensor_x - $edge_old_x1), 2) + pow(($sensor_y - $edge_old_y1), 2) + pow(($sensor_z - $edge_old_z1), 2));         // длина от начала разбиваемой выработки до сенсора
                    $L2 = sqrt(pow(($edge_old_x2 - $sensor_x), 2) + pow(($edge_old_y2 - $sensor_y), 2) + pow(($edge_old_z2 - $sensor_z), 2));         // длина от конца разбиваемой выработки до сенсора
                    $L3 = sqrt(pow(($edge_old_x2 - $edge_old_x1), 2) + pow(($edge_old_y2 - $edge_old_y1), 2) + pow(($edge_old_z2 - $edge_old_z1), 2));// длина разбиваемой выработки
                    $L4 = sqrt(pow(($edge_1_1_x2 - $edge_1_1_x1), 2) + pow(($edge_1_1_y2 - $edge_1_1_y1), 2) + pow(($edge_1_1_z2 - $edge_1_1_z1), 2));// длина первой выработки
                    $L5 = sqrt(pow(($edge_1_2_x2 - $edge_1_2_x1), 2) + pow(($edge_1_2_y2 - $edge_1_2_y1), 2) + pow(($edge_1_2_z2 - $edge_1_2_z1), 2));// длина второй выработки
                    $DL1 = $L1 / $L3;                                                                             //отношение длины от начала делимой выработки к длине всей делимой выработки
                    $DL2 = $L2 / $L3;                                                                             //отношение длины от конца делимой выработки к длине всей делимой выработки
                    $DL3 = $L4 / ($L4 + $L5);                                                                       //отношение длины первой выработки к сумме длин первой выработки и второй
                    $DL = 0;                                                                                    //инициализируем переменную в которой будем хранить отношение на которое нужно умножить длину двух выработок
                    $L = 0;                                                                                       //инициализируем переменую в которой будем хранить длину выработки на которую будем перемещать текущий сенсор
                    $new_edge_id = 0;                                                                           //инициализируем переменую в которой будем хранить ключ выработки на которую будем перемещать текущий сенсор
                    if ($DL1 <= $DL3)                                                                              //если отношение от начала делимой выработки до сенсора меньше чем отношение длины первой выработки к сумме длин первой выработки и второй
                    {                                                                                           //значит текущий сенсор будем перемещать на первую выработку
                        $DL = $DL1;                                                                             //запоминаем отношение длины от начала делимой выработки к длине всей делимой выработки
                        $L = $L4;                                                                               //запоминаем длину выработки на которую будем перемещать текущий сенсор
                        $new_edge_id = $edge1_1;                                                                //запоминаем ключ выработки на которую будем перемещать текущий сенсор
                    } else {
                        $DL = $DL2;                                                                             //запоминаем длины от конца делимой выработки к длине всей делимой выработки
                        $L = $L5;                                                                               //запоминаем длину выработки на которую будем перемещать текущий сенсор
                        $new_edge_id = $edge1_2;                                                                //запоминаем ключ выработки на которую будем перемещать текущий сенсор
                    }
                    $R = $DL * ($L4 + $L5);                                                                         //находим длинну на которую будем перемещать сенсор на выбранной выработке
                    $new_DL = $R / $L;                                                                            //запоминаем отношение в котором по делилась наша выработка
                    $new_sensor_x = 0;                                                                          //инициализируем новую переменную для сенсора x
                    $new_sensor_y = 0;                                                                          //инициализируем новую переменную для сенсора y
                    $new_sensor_z = 0;                                                                          //инициализируем новую переменную для сенсора z
                    if ($new_edge_id == $edge1_1)                                                                //если выработка на которую перемещаем первая
                    {                                                                                           //ищем новые координаты по формуле
                        $new_sensor_x = ($edge_1_1_x1 + $new_DL * $edge_1_1_x2) / (1 + $new_DL);
                        $new_sensor_y = ($edge_1_1_y1 + $new_DL * $edge_1_1_y2) / (1 + $new_DL);
                        $new_sensor_z = ($edge_1_1_z1 + $new_DL * $edge_1_1_z2) / (1 + $new_DL);
                    } else                                                                                        //если выработка на которую перемещаем вторая
                    {                                                                                           //ищем новые координаты по формуле
                        $new_sensor_x = ($edge_1_2_x1 + $new_DL * $edge_1_2_x2) / (1 + $new_DL);
                        $new_sensor_y = ($edge_1_2_y1 + $new_DL * $edge_1_2_y2) / (1 + $new_DL);
                        $new_sensor_z = ($edge_1_2_z1 + $new_DL * $edge_1_2_z2) / (1 + $new_DL);
                    }
                    $new_string_xyz = $new_sensor_x . ',' . $new_sensor_y . ',' . $new_sensor_z;                        //создаем строку для записи  в кеш и БД из новых координат сенсора

                    if ($parameter_type_id == 1)                                                    //если значение координат справочное то меняем значение в поле handbook
                    {
                        SensorBasicController::addSensorParameterHandbookValue($sensors83['sensor_parameter_id'], $new_string_xyz, 1, $date_time_now);
                        SensorBasicController::addSensorParameterHandbookValue($sensors269['sensor_parameter_id'], $new_edge_id, 1, $date_time_now);
                    } else {
                        SensorBasicController::addSensorParameterValue($sensors83['sensor_parameter_id'], $new_string_xyz, 1, $date_time_now);
                        SensorBasicController::addSensorParameterValue($sensors269['sensor_parameter_id'], $new_edge_id, 1, $date_time_now);
                    }

                    $sensor_cache_controller->setSensorParameterValueHash($sensors83['sensor_id'], -1, $new_string_xyz, $sensors83['parameter_id'], $sensors83['parameter_type_id'], 1, $date_time_now);
                    $sensor_cache_controller->setSensorParameterValueHash($sensors269['sensor_id'], -1, $new_edge_id, $sensors269['parameter_id'], $sensors269['parameter_type_id'], 1, $date_time_now);
                }
            } else {
                $warnings[] = "EdgeReplaceSensors. В БД нет сенсоров для выработки = " . $edge1;
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "EdgeReplaceSensors. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    //создание нового места в базе данных
    //01.05.2019г. Дополнил метод возвратом значения
    private function actionAddPlace($place_title, $mine_id, $object_id, $plast_id)
    {
        $errors = array();
        if ($place_title != '' && $mine_id != '' && $object_id != '' && $plast_id != '') {
            $debug_flag = 0;
            //создаем новый объект
            $main_id = new Main();
            $main_id->table_address = 'place';                                                                               //адрес таблицы в которой искать главный id
            $main_id->db_address = 'amicum2';                                                                               //имя базы данных в которой лежит таблица
            $main_id->save();

            //создаем новое место
            if ($main_id->save()) {
                $place_id = $main_id->id;
                $place = new Place();
                //записать новые данные
                $place->id = $place_id;
                $place->title = (string)$place_title;                                                                       //название места
                $place->mine_id = $mine_id;                                                                                 //шахта в которой находится место
                $place->object_id = $object_id;                                                                             //тип места
                $place->plast_id = $plast_id;                                                                               //тип пласта
                //создаем проверки
                if ($debug_flag == 1) echo nl2br('id места ' . $place_id . "\n");
                if ($debug_flag == 1) echo nl2br('название места ' . $place_title . "\n");
                if ($debug_flag == 1) echo nl2br('id шахты ' . $mine_id . "\n");
                if ($debug_flag == 1) echo nl2br('id типового объекта ' . $object_id . "\n");
                if ($debug_flag == 1) echo nl2br('id пласта ' . $plast_id . "\n");

                //Сохранить место
                if ($place->save()) return $place_id;                                                                       //если сохранилась, вернуть id
                else {
                    $errors[] = 'Ошибка добавление место в модели Place';
                    $errors[] = $place->errors;
                    return $errors;
                }
                HandbookCachedController::clearPlaceCache();
            } else {
                $errors[] = 'Ошибка добавления объекта в модели Main';
                $errors[] = $main_id->errors;
                return $errors;
            }
        } else {
            $errors[] = 'Некоторые параметры имеют пустое значение';
            return $errors;
        }
    }



    // Сохранение нового ребра/ветки на основе сопряжений и места
    //01.05.2019г. Дополнил метод возвратом значения
    public static function actionAddEdge($place_id, $conjunction_start_id, $conjunction_end_id, $edge_type_id)
    {
        $errors = array();
        if ($place_id != '' and $conjunction_start_id != '' and $conjunction_end_id != '' and $edge_type_id != '') // если входные параметры не пустые
        {
            $debug_flag = 0;
            $edge = new Edge();
            //записать впустое сопряжение конкретные данные
            $edge->conjunction_start_id = $conjunction_start_id;                                                        //сопряжение начало
            $edge->conjunction_end_id = $conjunction_end_id;                                                            //сопряжение конец
            $edge->place_id = $place_id;                                                                                //название места
            $edge->edge_type_id = $edge_type_id;                                                                        //тип ветви (выработка, вертикальный ствол/скважина)
            $edge_id = $edge->id;
            if ($debug_flag == 1) echo nl2br('id новой ветви ' . $edge_id . "\n");
            if ($debug_flag == 1) echo nl2br('id споряжение старт ' . $conjunction_start_id . "\n");
            if ($debug_flag == 1) echo nl2br('id сопряжение енд ' . $conjunction_end_id . "\n");
            if ($debug_flag == 1) echo nl2br('id места ' . $place_id . "\n");
            if ($debug_flag == 1) echo nl2br('id типа ветви ' . $edge_type_id . "\n");
            $edge_id = $edge->id;
            //Сохранить сопряжение
            if ($edge->save()) {
                $edge_id = $edge->id;                                                                                   //забираем id-edge
                $edge_status = new EdgeStatus();
                $edge_status->edge_id = $edge_id;
                $edge_status->status_id = 1;                                                                            // записываем статус 1 (актульная)
                $edge_status->date_time = date('Y-m-d H:i:s.U', strtotime('-1 second'));
                if ($edge_status->save()) {
                    return $edge->id;                                                                                   // если сохранилась, вернуть id
                } else {
                    $errors[] = "Ошибка сохранения статуса выработки $edge_id. ";
                    $errors[] = $edge_status->errors;
                    return $errors;
                }
            } else {
                $errors[] = "Ошибка сохранения выработки $edge_id. Возсможно place_id не тот или же другие параметры";
                $errors[] = $edge->errors;
                $errors['conjunction_start_id'] = $conjunction_start_id;
                $errors['conjunction_end_id'] = $conjunction_end_id;
                $errors['place_id'] = $place_id;
                return $errors;
            }
        } else {
            $errors[] = 'Некоторые параметры имеют пустое значение';
            return $errors;
        }
    }

    /**
     * Метод получения даты и времени по UTC
     * Created by: Одилов О.У. on 20.11.2018 14:51
     */
    public function actionGetDateTimeUtc()
    {
        $date_utc = new DateTime('now', new DateTimeZone('UTC'));
        $date_time_utc = $date_utc->format('Y-m-d H:i:s');                                                        # Saturday, 18-Apr-15 03:23:46 UTC
        return ($date_time_utc);
    }

    /**
     * Метод получения даты и времени по UTC
     * Created by: Одилов О.У. on 20.11.2018 14:51
     */
    public function actionGetDateTimeCurrent()
    {
        $date_time_current = Assistant::GetDateTimeNow();

        Yii::$app->response->format = Response::FORMAT_RAW;                                                             // формат возвращаемых данных json методом yii2
        Yii::$app->response->data = $date_time_current;
    }

    /**
     * actionGetMineCameraRotation - Метод получения поворот камеры у заданной шахты
     * Created by: Якимов М.Н. on 13.11.2018 16:13
     */
    public function actionGetMineCameraRotation()
    {
        $errors = array();
        $mine_result = array();
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
                $mine_result[] = array('mine_id' => -1, 'x' => $mine['x'], 'y' => $mine['y'], 'z' => $mine['z']);
            } else {
                $mine_result[] = $mine;
            }
        }
        if (!empty($mine_result)) {
            $errors[] = 'Для заданной шахты не указан поворот камеры в БД';
        }
        $result = array('Items' => $mine_result, 'errors' => $errors);
//        return json_encode($result);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                   //формат возвращаемых данных json методом yii2
        Yii::$app->response->data = $result;
    }


    //формирует текущие СВЕДЕНИЯ о персонале для списка людей в Unity             ВОЗВРАЩАЕТ СПИСОК ВСЕХ worker В КЕШЕ методом POST
    //зарегистрированных на шахте (взявших светильник с зарядного стола
    //используется для первичного построения модели Unity
    //при передаче конкретного id ворека может вернуть только его сведения
    //http://....../unity/get-workers?mine_id=290
    //http://....../unity/get-workers?mine_id=290&worker_id=1090193
    public function actionGetWorkers()
    {
        $log = new LogAmicumFront("actionGetWorkers");
        $post = Assistant::GetServerMethod();                                                                           //получение данных от ajax-запроса
        $new_worker_list = array();                                                                                     //массив список worker
        $workers_count = null;
        try {
            $log->addLog("Начал выполнять метод");
            if (!isset($post['mine_id']) or $post['mine_id'] == '') {                                                   //проверка на наличие входного параметра со стороны frontend

                throw new Exception('actionGetWorkers. не передан входной параметра');
            }

            $mine_id = $post['mine_id'];
            if ($mine_id == -1) {
                $mine_id = '*';
            }


            if (!COD) {
                $log->addLog("Не ЦОД, данные из кеша");
                $worker_list = (new WorkerCacheController())->multiGetWorkerCheckMineHash($mine_id);
            } else {
                $log->addLog("ЦОД, данные из БД");
                $worker_list = WorkerBasicController::getWorkerMine($mine_id);
            }

            $new_worker_list = array();                                                                                 // если в кеше нет ни каких данных, то возвращаем пустой новый массив worker

            if ($worker_list) {                                                                                         // проверяем наличие данных в кеше по заданной шахте - людей которые зачекинились/зарядились в ламповой

                $log->addLog("Список полон, делаем обработку");

                if (isset($post['worker_id']) && $post['worker_id'] != '') {                                            // если заданы входные конкретные ключи worker, то из всего списка вернется информация только по ним

                    $log->addLog("Формируем данные по конкретному человеку");

                    $array_worker_id = $post['worker_id'];                                                              // список id запрашиваемого воркера
                    $gotWorkers = explode(',', $array_worker_id);                                               // список запрашиваемых worker может приходить через запятую и по каждому из запрашиваемых worker получаем данные из кеша
                    foreach ($worker_list as $worker) {
                        $worker_handbook[$worker['worker_id']] = $worker;
                    }
                    foreach ($gotWorkers as $worker) {                                                                  // пробегаемся по всем запрашиваемым воркерам из списка запроса
                        if (isset($worker_handbook[$worker])) {                                                         // если в worker листе есть worker запрашиваемые нами, то мы формируем по нему новый массив данных - копируем массив сведений по нему
                            $new_worker_list[] = $worker_handbook[$worker];                                             // по запрашиваемым спискам worker получаем данные
                        }
                    }
                } else {
                    $log->addLog("Формируем данные по списку шахты");

                    if (isset($post['tab']) && $post['tab'] != '') {                                                    // если данные нужны для вкладки смена
                        $log->addLog("Данные для вкладки");
                        $company_department_list = (new Query())
                            ->select('
                            company.id as company_id,
                            company.title as company_title,
                            department.id as department_id,
                            department.title as department_title,
                            worker.id as worker_id')
                            ->from('worker')
                            ->innerJoin('company_department', 'worker.company_department_id=company_department.id')
                            ->innerJoin('company', 'company.id=company_department.company_id')
                            ->innerJoin('department', 'department.id=company_department.department_id')
                            ->limit(10000)
                            ->all();
                        foreach ($company_department_list as $company_department) {
                            $company_department_array[$company_department['worker_id']] = $company_department;
                        }

                        unset($company_department_list);

                        foreach ($worker_list as $worker) {
                            $worker_array = array_merge($worker, $company_department_array[$worker['worker_id']]);
                            $new_worker_list[] = $worker_array;
                        }

                    } else {
                        $log->addLog("Данные для Unity");
                        foreach ($worker_list as $worker) {
                            if ($worker['position_title'] and mb_strlen($worker['position_title']) > 40) {
                                $worker['position_title'] = mb_substr($worker['position_title'], 0, 40) . "...";
                            }

                            if ($worker['department_title'] and mb_strlen($worker['department_title']) > 43) {
                                $worker['department_title'] = mb_substr($worker['department_title'], 0, 43) . "...";
                            }
                            $new_worker_list[] = $worker;
                        }
                    }
                }
            }
            $log->addLog("Закончил подготовку списка людей");

            $workers_count = self::getEmployeeCountByPlace($mine_id, $worker_list);                                     // вызов функции расчета количества шахтеров в шахте и на поверхности

            $log->addLog("Закончил расчет списка людей в шахте");
        } catch (Exception $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Закончил выполнять метод");

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $new_worker_list, 'employee_count' => $workers_count === null ? (object)array() : $workers_count['employee_count']], $log->getLogOnlyStatus());
    }

    //метод сортировки массива работников для страницы смены на схеме
    public function SortWorkersForShiftTab($worker_list)
    {
        $new_worker_list = array();                                                                                     //массив список worker
        if ($worker_list)                                                                                               //если переданный список существует
        {
            ArrayHelper::multisort($worker_list, ['company_title', 'department_title'], [SORT_ASC, SORT_ASC]);    //соритируем массив по названию компаний и названию подразделений
            $i = -1;                                                                                                    //объявляем переменные для цикла
            $j = -1;
            $k = -1;
            $t = -1;
            $company_id = -1;
            $department_id = -1;
            $position = '';
            $worker_id = -1;
            foreach ($worker_list as $worker)                                                                           //начинаем перебирать массив
            {
                if ($i == -1 or $worker['company_id'] != $company_id)                                                   //если это первый заход или или идентификтор компании сменился
                {
                    $i++;
                    $j = -1;

                    $new_worker_list[$i]['company_id'] = $worker['company_id'];                                         //то пишем новый инденитификатор компании
                    $new_worker_list[$i]['company_title'] = $worker['company_title'];                                   //и название компании в результирующий массив
                }
                $company_id = $worker['company_id'];                                                                    //запоминаем идентификатор компании чтоб на следующеей итерации сравнить с предыдущем
                if ($j == -1 or $worker['department_id'] != $department_id)                                             //если это первый заход или идентификтор подразделения сменился
                {
                    $j++;
                    $k = -1;
                    $new_worker_list[$i]['departments'][$j]['department_id'] = $worker['department_id'];                //записываем в результирующий массив иденификтор подразделения
                    $new_worker_list[$i]['departments'][$j]['department_title'] = $worker['department_title'];          //и название

                }
                $department_id = $worker['department_id'];                                                              //запоминаем идентификатор подразделения чтоб на следующеей итерации сравнить с предыдущем
                if ($k == -1 or $worker['position_title'] != $position)                                                 //если это первый заход или название позиции сменилась
                {
                    $k++;
                    $t = -1;
                    $new_worker_list[$i]['departments'][$j]['positions'][$k]['position_title'] = $worker['position_title'];//то записываем в результирующий массив
                }
                $position = $worker['position_title'];                                                                  //запоминаем название позиции чтоб на следующеей итерации сравнить с предыдущем
                if ($t == -1 or $worker['worker_id'] != $worker_id)                                                     //если это первый заход или иденификатор worker сменился
                {
                    $t++;
                    $new_worker_list[$i]['departments'][$j]['positions'][$k]['workers'][$t]['worker_id'] = $worker['worker_id'];//записываем иденификатор worker
                    $new_worker_list[$i]['departments'][$j]['positions'][$k]['workers'][$t]['full_name'] = $worker['full_name'];    //записывем его ФИО
                    $new_worker_list[$i]['departments'][$j]['positions'][$k]['workers'][$t]['staff_number'] = $worker['stuff_number'];//Записываем его табельный номер
                }
                $worker_id = $worker['worker_id'];                                                                      //запоминаем идентификатор работника чтоб на следующеей итерации сравнить с предыдущем
            }
        }
        return $new_worker_list;                                                                                        //отдаем результат
    }

    /**
     * Метод подсчета количества людей, находящихся в шахте, либо на
     * поверхности,  а также разделяет по конпаниям
     * Используется на 3D схеме.
     * в 18 параметре 1 - Заполярная, 2 - Воркутинская -  данная часть хранится в справочнике group_alarm
     * @param $mine_id
     * @return array
     */
    public static function getEmployeeCount($mine_id, $workers_lists)
    {
        $errors = array();    //объявляем пустой массив ошибок
        $response = null;
        $warnings = array();
        $flag = false;
        $status = 1;
        $result_worker_count = array();

        // Счетчики кол-ва людей
        $worker_count_on_shift = 0;//кол-ва работников в смене
        $workers_count_guests = 0; //количество прочих работников на обеех предприятиях

        $worker_count_in_mine_first_company = 0;//кол-ва работников в шахте
        $worker_count_on_surface_first_company = 0;//кол-ва работников на поверхности
        $errors_count_first_company = 0; //ошибочные значения 358 параметра

        $worker_count_in_mine_second_company = 0;//кол-ва работников в шахте
        $worker_count_on_surface_second_company = 0;//кол-ва работников на поверхности
        $errors_count_second_company = 0; //ошибочные значения 358 параметра
        try {
            if ($mine_id == 290) {
                $flag = true;
            }
            /**
             * Получаем список worker для анализа шахты
             */
//            $workers_lists = (new WorkerCacheController())->getWorkerMine($mine_id);
            if (!$workers_lists) {
                throw new Exception('getEmployeeCount. Список работников пуст');
            }

            $warnings[] = 'getEmployeeCount. В кеше есть список работников';

            /**
             * Получение значений параметра 358/2 для всех worker
             */
            if (!COD) {
                $workers_parameter_358_lists = (new WorkerCacheController())->multiGetParameterValueHash('*', 358, 2);
            } else {
                $workers_parameter_358_lists = WorkerBasicController::getWorkerParameterValue('*', 358, 2);
            }
            if ($workers_parameter_358_lists === false) {
                $warnings[] = 'getEmployeeCount. В кеше не нашел 358/2';
            }
            foreach ($workers_parameter_358_lists as $workers_parameter_list) {
                $workers_parameter_358_array[$workers_parameter_list['worker_id']] = $workers_parameter_list;
            }
            unset($workers_parameter_358_lists);
            if ($flag) {
                /**
                 * Получение значений параметра 18/ для всех worker
                 */
                if (!COD) {
                    $workers_parameter_18_lists = (new WorkerCacheController())->multiGetParameterValueHash('*', 18, 1);
                } else {
                    $workers_parameter_18_lists = WorkerBasicController::getWorkerParameterHandbookValue('*', 18);
                }
                if ($workers_parameter_18_lists === false) {
                    $warnings[] = 'getEmployeeCount. В кеше не нашел 18/1';
                }
                foreach ($workers_parameter_18_lists as $workers_parameter_list) {
                    $workers_parameter_18_array[$workers_parameter_list['worker_id']] = $workers_parameter_list;
                }
                unset($workers_parameter_18_lists);
            }
            /**
             * Получаем список 358 праметров для работников
             */
            foreach ($workers_lists as $workers_list) {
                if ($workers_list['mine_id'] == $mine_id) {
                    if (isset($workers_parameter_358_array[$workers_list['worker_id']])) {
                        $worker_count_on_shift++;
                        switch ($workers_parameter_358_array[$workers_list['worker_id']]['value']) {
                            case 'Underground':
                                if ($flag) {
                                    if (isset($workers_parameter_18_array[$workers_list['worker_id']]) and $workers_parameter_18_array[$workers_list['worker_id']]['value'] == 1) {
                                        $worker_count_in_mine_first_company++;
                                    } else if (isset($workers_parameter_18_array[$workers_list['worker_id']]) and $workers_parameter_18_array[$workers_list['worker_id']]['value'] == 2) {
                                        $worker_count_in_mine_second_company++;
                                    } else {
                                        $workers_count_guests++;
                                    }
                                } else {
                                    $worker_count_in_mine_first_company++;
                                }

                                break;
                            case 'Surface':
                                if ($flag) {
                                    if (isset($workers_parameter_18_array[$workers_list['worker_id']]) and $workers_parameter_18_array[$workers_list['worker_id']]['value'] == 1) {
                                        $worker_count_on_surface_first_company++;
                                    } else if (isset($workers_parameter_18_array[$workers_list['worker_id']]) and $workers_parameter_18_array[$workers_list['worker_id']]['value'] == 2) {
                                        $worker_count_on_surface_second_company++;
                                    } else {
                                        $workers_count_guests++;
                                    }
                                } else {
                                    $worker_count_on_surface_first_company++;
                                }
                                break;
                            default:
                                if ($flag) {
                                    if (isset($workers_parameter_18_array[$workers_list['worker_id']]) and $workers_parameter_18_array[$workers_list['worker_id']]['value'] == 1) {
                                        $errors_count_first_company++;
                                    } else if (isset($workers_parameter_18_array[$workers_list['worker_id']]) and $workers_parameter_18_array[$workers_list['worker_id']]['value'] == 2) {
                                        $errors_count_second_company++;
                                    } else {
                                        $workers_count_guests++;
                                    }
                                } else {
                                    $errors_count_first_company++;
                                }
                                break;
                        }
                    } else {
                        $errors[] = 'getEmployeeCount. У работника ' . $workers_list['worker_id'] . ' не заполнен 358 параметр';
                    }

                }
            }
            if ($flag) {
                $result_worker_count[] = array(
                    'mine_title' => '1',
                    'worker_count_on_shift' => $worker_count_on_shift,
                    'worker_count_in_mine' => $worker_count_in_mine_first_company,
                    'worker_count_on_surface' => $worker_count_on_surface_first_company,
                    'workers_count_guests' => $workers_count_guests,
                );

                $result_worker_count[] = array(
                    'mine_title' => '2',
                    'worker_count_on_shift' => $worker_count_on_shift,
                    'worker_count_in_mine' => $worker_count_in_mine_second_company,
                    'worker_count_on_surface' => $worker_count_on_surface_second_company,
                    'workers_count_guests' => $workers_count_guests,
                );
            } else {
                $result_worker_count[] = array(
                    'mine_title' => '0',
                    'worker_count_on_shift' => $worker_count_on_shift,
                    'worker_count_in_mine' => $worker_count_in_mine_first_company,
                    'worker_count_on_surface' => $worker_count_on_surface_first_company,
                    'workers_count_guests' => $workers_count_guests,
                );
            }
            $warnings[] = "Не смог определить где человек: " . $errors_count_first_company;
        } catch (Throwable $ex) {
            $status = 0;
            $errors[] = 'getEmployeeCount. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = 'строка с ошибкой ' . $ex->getLine();
        }

        return array('status' => $status, 'errors' => $errors, 'employee_count' => $result_worker_count, 'warnings' => $warnings);
    }

    /**
     * метод копирует параметры типового параметра в параметры конкретного объекта - нужен для создания конкретного объекта по шаблону типового объекта
     * $typical_object_id - object_id у place
     * $specific_object_id - edge_id у place
     **/
    public function actionCopyTypicalParametersToSpecificToEdge($typical_object_id, $specific_object_id)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $edge_param_val_to_cache = array();
        $edge_param__hand_val_to_cache = array();
        try {                                                                                                 //флаг успешного выполнения метода
            $warnings[] = 'actionCopyTypicalParametersToSpecificToEdge. Начал выполнять метод';
            //копирование параметров справочных
            $type_object_parameters = ViewTypeObjectParameterHandbookValueMaxDateMain::find()
                ->where(['object_id' => $typical_object_id, 'parameter_type_id' => 1])
                ->all();

            if ($type_object_parameters)                           //Находим все параметры типового объекта
            {
                $date_time = date('Y-m-d H:i:s.U');
                foreach ($type_object_parameters as $type_object_parameter) {
                    //создаем новый параметр у конкретного объекта
                    $edge_parameter_id = self::actionAddEdgeParameter($specific_object_id, $type_object_parameter->parameter_id, $type_object_parameter->parameter_type_id);

                    $edge_param__hand_val_to_cache[] = array(
                        $edge_parameter_id,
                        $date_time,
                        $type_object_parameter->value,
                        1
                    );
                }
            }
            /**
             * Массовая вставка измеренных параметров edge
             */
            if ($edge_param_val_to_cache) {
                $insert_result = Yii::$app->db->createCommand()->batchInsert('edge_parameter_value',
                    ['edge_parameter_id', 'date_time', 'value', 'status_id'], $edge_param_val_to_cache)->execute();
                $warnings[] = 'actionCopyTypicalParametersToSpecificToEdge. Массовая вставка обычных параметров: ' . $insert_result;
            }
            /**
             * Массовая вставка справочных параметров edge
             */
            if ($edge_param__hand_val_to_cache) {
                $insert_result = Yii::$app->db->createCommand()->batchInsert('edge_parameter_handbook_value',
                    ['edge_parameter_id', 'date_time', 'value', 'status_id'], $edge_param__hand_val_to_cache)->execute();
                $warnings[] = 'actionCopyTypicalParametersToSpecificToEdge. Массовая вставка справочных параметров: ' . $insert_result;
            }


//            //копирование функций типового объекта
//            //находим функции типового объекта
//            if ($type_object_functions = TypeObjectFunction::find()->where(['object_id' => $typical_object_id])->all()) {
//                foreach ($type_object_functions as $type_object_function) {
//                    $edge_function_id = self::actionAddEdgeFunction($specific_object_id, $type_object_function->func_id);
//                    if ($edge_function_id == -1) $flag_done = -1;
//                }
//            }
        } catch (Throwable $exception) {
            $errors[] = 'actionCopyTypicalParametersToSpecificToEdge. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = 'actionCopyTypicalParametersToSpecificToEdge. Закончил выполнять метод';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    //сохранение функций ветви выработки
    public static function actionAddEdgeFunction($edge_id, $function_id)
    {
        $errors = array();
        if ($edge_id != '' && $function_id != '') {
            $debug_flag = 0;
            if ($debug_flag == 1) echo nl2br('----зашел в функцию создания функции ветви  =' . $edge_id . "\n");

            $edge_function = new EdgeFunction();
            $edge_function->edge_id = $edge_id;                                             //id параметра ветви
            $edge_function->function_id = $function_id;                                                             //id функции
            //статус значения
            if ($edge_function->save()) return 1;
            else {
                $errors[] = 'Ошибка сохранения значения параметра ' . $edge_function->id;
                return -1;
            }
        } else {
            $errors[] = 'Некоторые параметры имеют пустое значение';
        }
    }

    //сохранение параметров для заданного ребра/ветки
    public static function actionAddEdgeParameter($edge_id, $parameter_id, $parameter_type_id)
    {

        $errors = array();
        if ($edge_id != '' and $parameter_id != '' and $parameter_type_id != '') // если входные параметры не пустые
        {
            $edge_parameter_id = EdgeParameter::findOne(['edge_id' => $edge_id, 'parameter_id' => $parameter_id, 'parameter_type_id' => $parameter_type_id]);
            if (!$edge_parameter_id) {
                $edge_parameter = new EdgeParameter();
                $edge_parameter->edge_id = $edge_id;                                                                            //id ветви
                $edge_parameter->parameter_id = $parameter_id;                                                                  //параметр id
                $edge_parameter->parameter_type_id = $parameter_type_id;                                                        //тип параметра (справочный измеренный вычесленный
                //Сохранить параметра
                if ($edge_parameter->save()) return $edge_parameter->id;
                else {
                    $errors[] = 'Ошибка сохранения параметров ветви ' . $edge_id;
                }
            } else return $edge_parameter_id->id;
        } else {
            $errors[] = 'Некоторые параметры имеют пустое значение';
        }
    }

    //сохранение справочного значения конкретного параметра ветви
    public static function actionAddEdgeParameterHandbookValue($edge_parameter_id, $value, $status_id, $date_time)
    {
        $edge_parameter_handbook_value = new EdgeParameterHandbookValue();
        $edge_parameter_handbook_value->edge_parameter_id = $edge_parameter_id;
        if ($date_time == 1) $edge_parameter_handbook_value->date_time = \backend\controllers\Assistant::GetDateNow();
        else $edge_parameter_handbook_value->date_time = $date_time;
        $edge_parameter_handbook_value->value = (string)$value;
        $edge_parameter_handbook_value->status_id = $status_id;
        if (!$edge_parameter_handbook_value->save()) {
            return (-1);
        } else return 1;
    }

    //сохранение значений параметров измеренных VALUE
    public static function actionAddEdgeParameterValueMeasure($edge_parameter_id, $value, $status_id, $date_time)
    {
        $errors = array();
        if ($edge_parameter_id != '' and $value != '' and $status_id != '' and $date_time != '') // если входные параметры не пустые
        {

            $edge_parameter_value = new EdgeParameterValue();

            $edge_parameter_value->edge_parameter_id = $edge_parameter_id;                                             //id параметра ветви
            $edge_parameter_value->date_time = $date_time;                                                             //время текущее
            $edge_parameter_value->value = (string)$value;                                                                     //значение справочное
            $edge_parameter_value->status_id = $status_id;                                                             //статус значения

            if ($edge_parameter_value->save()) return 1;
            else {
                $errors[] = 'Ошибка сохранения значения параметра ' . $edge_parameter_id;
            }
        } else {
            $errors[] = 'Некоторые параметры имеют пустое значение';
        }
    }

    //копирование параметров и функций в рамках конкретной выработки и ветви
    public static function actionCopyEdge($edge_new_id, $edgeTek_id, $cut_copy, $edge_lenght, $edge_status)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $edge_param_val_to_cache = array();
        $edge_param__hand_val_to_cache = array();
        $result = array();
        try {
            $warnings[] = 'actionCopyEdge. Начал выполнять метод';
            //копирование параметров справочных
            $edgeParameters = ViewEdgeParameterHandbookValueMaxDateForMerge::find()->where(['edge_id' => $edgeTek_id, 'parameter_type_id' => 1])->all();
            if ($edgeParameters)                           //Находим все параметры этой ветки/ребра
            {
                $date_time = date('Y-m-d H:i:s.U');
                foreach ($edgeParameters as $edgeParameter) {
                    $edgeParameter_id = self::actionAddEdgeParameter($edge_new_id, $edgeParameter->parameter_id, $edgeParameter->parameter_type_id);
                    if ($edgeParameter->parameter_id == 151) {
                        $edge_param__hand_val_to_cache[] = array(
                            $edgeParameter_id,
                            $date_time,
                            (string)$edge_lenght,
                            $edgeParameter->status_id
                        );
                    } elseif ($edgeParameter->parameter_id == 164) {
                        $edge_param__hand_val_to_cache[] = array(
                            $edgeParameter_id,
                            $date_time,
                            (string)$edge_status,
                            $edgeParameter->status_id
                        );
                    } else {
                        $edge_param__hand_val_to_cache[] = array(
                            $edgeParameter_id,
                            $date_time,
                            (string)$edgeParameter->value,
                            $edgeParameter->status_id
                        );
                    }
                }
            }

            /**
             * Массовая вставка измеренных параметров edge
             */
            if ($edge_param_val_to_cache) {
                $insert_result = Yii::$app->db->createCommand()->batchInsert('edge_parameter_value',
                    ['edge_parameter_id', 'date_time', 'value', 'status_id'], $edge_param_val_to_cache)->execute();
                $warnings[] = 'actionCopyTypicalParametersToSpecificToEdge. Массовая вставка обычных параметров: ' . $insert_result;
            }
            /**
             * Массовая вставка справочных параметров edge
             */
            if ($edge_param__hand_val_to_cache) {
                $insert_result = Yii::$app->db->createCommand()->batchInsert('edge_parameter_handbook_value',
                    ['edge_parameter_id', 'date_time', 'value', 'status_id'], $edge_param__hand_val_to_cache)->execute();
                $warnings[] = 'actionCopyTypicalParametersToSpecificToEdge. Массовая вставка справочных параметров: ' . $insert_result;
            }


//            //echo "прошел запись справочных параметров";
//            //копирование параметров обычных
//            if ($edgeParameters = ViewEdgeParameterValueMaxDateForMerge::find()->where(['edge_id' => $edgeTek_id])->andWhere('parameter_type_id <> 1')->all())                           //Находим все параметры этой ветки/ребра
//            {
//                foreach ($edgeParameters as $edgeParameter) {
//                    $edgeParameter_id = self::actionAddEdgeParameter($edge_new_id, $edgeParameter->parameter_id, $edgeParameter->parameter_type_id);
//                    $flag_done = self::actionAddEdgeParameterValueMeasure($edgeParameter_id, $edgeParameter->value, $edgeParameter->status_id, date('Y-m-d H:i:s.U'));
//                }
//            }

        } catch (Throwable $exception) {
            $errors[] = 'actionCopyEdge. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = 'actionCopyEdge. Закончил выполнять метод';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    //сохранение значений параметров справочных HANDBOOK VALUE
    public static function actionAddEdgeParameterValue($edge_parameter_id, $value, $status_id, $date_time)
    {
        $errors = array();
        if ($edge_parameter_id != '' and $value != '' and $status_id != '' and $date_time != '') // если входные параметры не пустые
        {
            $debug_flag = 0;
            if ($debug_flag == 1) echo nl2br('----зашел в функцию создания значений параметра  =' . $edge_parameter_id . "\n");

            $edge_parameter_value = new EdgeParameterHandbookValue();
            $edge_parameter_value->edge_parameter_id = $edge_parameter_id;                                             //id параметра ветви
            $edge_parameter_value->date_time = $date_time;                                                             //время текущее
            $edge_parameter_value->value = (string)$value;                                                             //значение справочное
            $edge_parameter_value->status_id = $status_id;                                                             //статус значения

            if ($debug_flag == 1) echo nl2br('параметр edge ' . $edge_parameter_id . "\n");
            if ($debug_flag == 1) echo nl2br('дата время ' . date('Y-m-d H:i:s.U') . "\n");
            if ($debug_flag == 1) echo nl2br('Значение ' . $value . "\n");
            if ($debug_flag == 1) echo nl2br('Статус ' . $status_id . "\n");

            if ($edge_parameter_value->save()) return 1;
            else {
                if ($debug_flag == 1) echo nl2br('------------------------ ' . "\n");
                if ($debug_flag == 1) echo(var_dump($edge_parameter_value->errors));
                if ($debug_flag == 1) echo nl2br('------------------------- ' . "\n");
                if ($debug_flag == 1) echo nl2br('Ошибка сохранения значения параметра ' . $edge_parameter_id . "\n");
                $errors[] = 'Ошибка сохранения значения параметра ' . $edge_parameter_id;
            }
        } else {
            $errors[] = 'Некоторые параметры имеют пустое значение';
        }
    }

    public function InsertParamsOnCreateEdge($edge_new_id, $edge_new_lenght, $edge_new_height, $edge_new_width, $edge_new_section, $edge_new_danger_zona, $edge_new_conveyor, $edge_new_color_edge, $edge_status, $edge_new_value_co, $edge_new_value_ch, $edge_new_conveyor_tag = null)
    {

        $success_param_insert = array();
        $flag_done = EdgeBasicController::addEdgeParameterWithHandbookValue($edge_new_id, 151, 1, $edge_new_lenght, 1, 1)['status'];
        $success_param_insert['1-151'] = $flag_done;
        $flag_done = EdgeBasicController::addEdgeParameterWithHandbookValue($edge_new_id, 128, 1, $edge_new_height, 1, 1)['status'];
        $success_param_insert['1-128'] = $flag_done;
        $flag_done = EdgeBasicController::addEdgeParameterWithHandbookValue($edge_new_id, 129, 1, $edge_new_width, 1, 1)['status'];
        $success_param_insert['1-129'] = $flag_done;
        $flag_done = EdgeBasicController::addEdgeParameterWithHandbookValue($edge_new_id, 130, 1, $edge_new_section, 1, 1)['status'];
        $success_param_insert['1-130'] = $flag_done;
        $flag_done = EdgeBasicController::addEdgeParameterWithHandbookValue($edge_new_id, 131, 1, $edge_new_danger_zona, 1, 1)['status'];
        $success_param_insert['1-131'] = $flag_done;
        $flag_done = EdgeBasicController::addEdgeParameterWithHandbookValue($edge_new_id, 442, 1, $edge_new_conveyor, 1, 1)['status'];
        $success_param_insert['1-442'] = $flag_done;
        $flag_done = EdgeBasicController::addEdgeParameterWithHandbookValue($edge_new_id, 132, 1, $edge_new_color_edge, 1, 1)['status'];
        $success_param_insert['1-132'] = $flag_done;
        $flag_done = EdgeBasicController::addEdgeParameterWithHandbookValue($edge_new_id, 164, 1, $edge_status, 1, 1)['status'];
        $success_param_insert['1-164'] = $flag_done;
        $flag_done = EdgeBasicController::addEdgeParameterWithHandbookValue($edge_new_id, 264, 1, $edge_new_value_co, 1, 1)['status'];
        $success_param_insert['1-264'] = $flag_done;
        $flag_done = EdgeBasicController::addEdgeParameterWithHandbookValue($edge_new_id, 263, 1, $edge_new_value_ch, 1, 1)['status'];
        $success_param_insert['1-263'] = $flag_done;
        if ($edge_new_conveyor_tag != null) {
            $flag_done = EdgeBasicController::addEdgeParameterWithHandbookValue($edge_new_id, 389, 1, $edge_new_conveyor_tag, 1, 1)['status'];
            $success_param_insert['1-389'] = $flag_done;
        }

        return $success_param_insert;
    }


//формирует текущие ПАРАМЕТРЫ о персонале для списка людей в Unity             ВОЗВРАЩАЕТ СПИСОК ВСЕХ worker В КЕШЕ методом POST
//зарегистрированных на шахте (взявших светильник с зарядного стола)
//используется для первичного построения модели Unity
//при передаче конкретного id ворека может вернуть только его сведения
//ВАЖНО!!!!
//ДАННЫЙ МЕТОД ВОЗВРАЩАЕТ МАССИВ - ОДНИМ СПИСКОМ!!!!

//http://127.0.0.1/unity/get-workers-parameters
    public function actionGetWorkersParameters()
    {
        $log = new LogAmicumFront("actionGetWorkersParameters");

//        ini_set('max_execution_time', 300);
//        ini_set('memory_limit', '1024M');

        $worker_results = array();
        $worker_group_result = array();
        $worker_parameter_value_list = array();                                                                         //создаем пустой список значений параметров worker

        try {
            $log->addLog('Начал выполнять метод');

            $post = Assistant::GetServerMethod();                                                                       //получение данных от ajax-запроса

            if (isset($post['mine_id']) && $post['mine_id'] != '') {                                                    //проверяем наличие ключа шахты в запросе от frontend
                $mine_id = $post['mine_id'];
                if ($mine_id == -1) {
                    $mine_id = '*';
                }
            } else {
                throw new Exception('Не передан входной параметр');                                                                                  //ключ от frontend не получен, потому формируем ошибку
            }

            $log->addLog('Входные параметры получены');
            $log->addLog('Начал получать кеш работников');

            if (!COD) {
                $worker_mines = (new WorkerCacheController())->getWorkerMineHash($mine_id);
            } else {
                $worker_mines = WorkerBasicController::getWorkerMine($mine_id);
            }

            $log->addLog('Получил кеш работников');

            if (!$worker_mines) {
                throw new Exception('кеш работников шахты пуст');                                                                                  //ключ от frontend не получен, потому формируем ошибку
            }

            $log->addLog('Кеш работников заполнен');

            $workers_parameters = array(
                9,                  // Температура
                18,                 // Предприятие
                20,                 // Кислород
                22,                 // Запыленность
                24,                 // Скорость воздуха
                26,                 // Водород
                83,                 // Координата
                98,                 // СО
                99,                 // СН4
                122,                // Место
//                164,                // Состояние
                323,                // Флаг сигнал SOS
                357,                // Флаг сигнал об аварии
                386,                // Превышение концентрации метана
                387,                // Превышение удельной доли углекислого газа
                436,                // Человек в опасной зоне
                439,                // Отправил сигнал SOS
                440,                // Получил сигнал SOS
            );

            /**
             * Получаю все параметры всех worker из кеша и пепелопачиваю их метод надо переделать на запрос параметров, только нужных worker
             */
//            $full_parameters = (new WorkerCacheController())->multiGetParameterValue('*', '*');

            $filter_parameter = '(9, 18, 20, 22, 24, 26, 83, 98, 99, 122, 323, 357, 386, 387, 436, 439, 440)';

            $full_parameters = (new Query())
                ->select(['worker_id', 'worker_parameter_id', 'parameter_id', 'parameter_type_id', 'date_time', 'value', 'status_id'])
                ->from('view_initWorkerParameterHandbookValue')
                ->andwhere('parameter_id in ' . $filter_parameter)
                ->all();

            $full_parameters = array_merge($full_parameters, (new Query())
                ->select(['worker_id', 'worker_parameter_id', 'parameter_id', 'parameter_type_id', 'date_time', 'value', 'status_id'])
                ->from('view_initWorkerParameterValue')
                ->andwhere('parameter_id in ' . $filter_parameter)
                ->all());

            if (!$full_parameters) {
                throw new Exception('Кеш параметров работников шахты пуст');
            }

            $log->addLog('Полный кеш параметров работника получен');

            foreach ($full_parameters as $full_parameter) {
                if (!isset($full_parameter['worker_id'])) {
                    //TODO сделать запись в лог
                    continue;
                }
                $workerList[$full_parameter['worker_id']][$full_parameter['parameter_id']] = $full_parameter;
            }

            /**
             * Фильтруем только тех кто нужен
             */
            foreach ($worker_mines as $worker_mine) {
                foreach ($workers_parameters as $workers_parameter) {
                    if (isset($workerList[$worker_mine['worker_id']][$workers_parameter])) {
                        $worker_parameter_value_list[] = $workerList[$worker_mine['worker_id']][$workers_parameter];
                    }
                }
            }
            unset($worker_mines);
            unset($full_parameters);

            $log->addLog('Получил кеш параметров работника');

            /**
             * Приводит формат данных к без NULL
             */

            if ($worker_parameter_value_list) {
                foreach ($worker_parameter_value_list as $workers_parameter) {
                    if ($workers_parameter['value'] !== null) {
                        $worker_result['worker_id'] = $workers_parameter['worker_id'];
                        $worker_result['worker_parameter_id'] = $workers_parameter['worker_parameter_id'];
                        $worker_result['parameter_id'] = $workers_parameter['parameter_id'];
                        $worker_result['parameter_type_id'] = $workers_parameter['parameter_type_id'];
                        $worker_result['date_time'] = date("Y-m-d H:i:s", strtotime($workers_parameter['date_time']));
                        if (
                            $workers_parameter['parameter_id'] == 164 and
                            $workers_parameter['parameter_type_id'] == 3 and
                            strtotime(Assistant::GetDateTimeNow()) - strtotime($workers_parameter['date_time']) > 300
                        ) {
                            $worker_result['value'] = -1;
                        } else {
                            $worker_result['value'] = $workers_parameter['value'];
                        }
                        $worker_group_result[$workers_parameter['worker_id']][$workers_parameter['parameter_type_id']][$workers_parameter['parameter_id']] = $worker_result;
                        $worker_results[] = $worker_result;
                        unset($worker_result);
                    }
                }
            }

            $log->addLog('Закончил формировать массив на отправку');

        } catch (Throwable $ex) {                                                                                       //обрабатываем исключение
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //формат возвращаемых данных json методом yii2
        Yii::$app->response->data = array_merge(['Items' => $worker_results, 'groupItems' => $worker_group_result], $log->getLogAll());
    }

    //формирует список пластов для  Unity
    //используется при создании горной выработки
    public function actionGetPlast()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $sql_filter = '';
        //фильтр пластов
        if (isset($post['plast_id']) && $post['plast_id'] != '') {
            $sql_filter .= 'id=' . $post['plast_id'] . '';
            //$sql_filter = 'plast_id=930018';
        }
        $plast_lists = (new Query())//запрос напрямую из базы по вьюшке view_personal_areas
        ->select(
            [
                'id',
                'title',
                'object_id'
            ])
            ->from(['plast'])
            ->where($sql_filter)->all();

        $plast_list_arr = array();

        if ($plast_lists) {
            $i = 0;
            foreach ($plast_lists as $plast_list) {
                $plast_list_arr[$i]['i'] = $i;
                $plast_list_arr[$i]['id'] = $plast_list['id'];
                $plast_list_arr[$i]['title'] = $plast_list['title'];
                $plast_list_arr[$i]['object_id'] = $plast_list['object_id'];
                $i++;
            }
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $plast_lists;
        //echo json_encode($plast_lists);

    }

    //формирует список типов мест для  Unity
    //используется при создании горной выработки =название типа мест
    public function actionGetTypePlace()
    {
        $errors = array();

        $sql_filter = '(object_type_id = 111 or object_type_id = 112 or object_type_id = 110 or object_type_id = 115)';

        $type_object_list = (new Query())
            ->select(
                [
                    'object_id',
                    'object_title',
                    'object_type_title',
                    'kind_object_title'
                ])
            ->from(['view_type_object'])
            ->where($sql_filter)
            ->orderBy(['object_title' => SORT_ASC])->all();
        if (isset($type_object_list)) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            Yii::$app->response->data = $type_object_list;
        } else {
            Yii::$app->response->format = Response::FORMAT_JSON;
            Yii::$app->response->data = $type_object_list;
        }
    }

    //формирует список типов горных выработок  Unity
    //используется при создании горной выработки = тип горной выработки

    public function actionGetTypeEdge()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $sql_filter = '';
        if (isset($post['edge_type_id']) && $post['edge_type_id'] != '') {
            $sql_filter = 'id=' . $post['edge_type_id'] . '';
        }
        $edge_type_list = (new Query())
            ->select(
                [
                    'id',
                    'title'
                ])
            ->from(['edge_type'])
            ->where($sql_filter)
            ->orderBy(['title' => SORT_ASC])->all();
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $edge_type_list;
        //echo json_encode($edge_type_list);

    }

    // actionGetEdge по запросу клиента формирует данные по конкретной запрашиваемой ветви
    //используется в Unity для вкладки сведения по конкретной выработке
    public function actionGetEdge()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $sql_filter = '';

        //фильтр по выработке
        if (isset($post['id']) && $post['id'] != '') $sql_filter = 'edge_id=' . $post['id'] . '';
        //$sql_filter = 'edge_id=930018';

        $edge = array((new Query())//запрос напрямую из базы по вьюшке view_personal_areas
        ->select(
            [
                'view_initEdgeScheme.*',
                'view_initEdgeScheme.conveyor_tag as conveyor_tag',                                                          //псевдоним нужен для правильной передачи название параметра на фронт
                'view_initEdgeScheme.value_ch as set_point_ch',                                                              //псевдоним нужен для правильной передачи название параметра на фронт
                'view_initEdgeScheme.value_co as set_point_co',                                                              //псевдоним нужен для правильной передачи название параметра на фронт
                'view_initEdgeScheme.place_object_id as type_place_id'
            ])
            ->from(['view_initEdgeScheme'])
            ->where($sql_filter)->one());
        //echo json_encode($edge);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $edge;
    }

    /*
     * Метод для получения списка текстур из справочника текстур
     * для выпадающего списка
     */
    public function actionGetTextureList()
    {
        $texture_list = (new Query)
            ->select([
                'id',
                'texture',
                'title'
            ])
            ->from('unity_texture')
            ->orderBy(['title' => SORT_ASC])->all();
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $texture_list;                                                                    // отправляем обратно ввиде ajax формат
    }

    /**
     * Метод получения сопряжений
     */
    public function actionGetConjunction()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $errors = array();
        $conjunction_start_ids = array();
        $conjunction_end_ids = array();
        $conjunctions = array();
//        $post['edge_id'] = 22150;
        if (isset($post['edge_id']) && $post['edge_id'] != '') {
            $edge_id = $post['edge_id'];
            $edges = (new Query())
                ->select([
                    'id', 'conjunction_start_id', 'conjunction_end_id'
                ])
                ->from('edge')
                ->where(['id' => $edge_id])
                ->one();

            if ($edges) {
                $edges_conjunction_start_id = $edges['conjunction_start_id'];
                $edges_conjunction_end_id = $edges['conjunction_end_id'];
                $conjunction_in_edges = (new Query())
                    ->select([
                        'edge_id', 'conjunction_start_id', 'conjunction_end_id', 'conjunction_start_title', 'conjunction_end_title', 'place_id', 'place_title'
                    ])
                    ->from('view_edge_conjunction_place_small')
                    ->where(['conjunction_start_id' => $edges_conjunction_start_id])
                    ->orWhere(['conjunction_end_id' => $edges_conjunction_end_id])
                    ->orWhere(['conjunction_start_id' => $edges_conjunction_end_id])
                    ->orWhere(['conjunction_end_id' => $edges_conjunction_start_id])
//                    ->andWhere('edge_id != '.$edges['id'])
                    ->all();
                $i = -1;
                $j = 0;
                foreach ($conjunction_in_edges as $conjunction) {
                    $current_conjunction_start_id = $conjunction['conjunction_start_id'];
                    if ($current_conjunction_start_id == $edges_conjunction_start_id or $current_conjunction_start_id == $edges_conjunction_end_id) {
                        if ($i == -1 or $conjunction_start_ids[$i]['id'] != $conjunction['conjunction_start_id']) {
                            $i++;
                            $conjunction_start_ids[$i]['id'] = $conjunction['conjunction_start_id'];
                            $conjunction_start_ids[$i]['title'] = $conjunction['conjunction_start_title'];
                            $j = 0;
                        }
                        $conjunction_start_ids[$i]['excavations'][$j]['id'] = $conjunction['place_id'];
                        $conjunction_start_ids[$i]['excavations'][$j]['title'] = $conjunction['place_title'];
                        $j++;
                    }
                }
                $i = -1;
                $j = 0;
                foreach ($conjunction_in_edges as $conjunction) {
                    $current_conjunction_end_id = $conjunction['conjunction_end_id'];
                    if ($current_conjunction_end_id == $edges_conjunction_start_id or $current_conjunction_end_id == $edges_conjunction_end_id) {
                        if ($i == -1 or $conjunction_end_ids[$i]['id'] != $conjunction['conjunction_end_id']) {
                            $i++;
                            $conjunction_end_ids[$i]['id'] = $conjunction['conjunction_end_id'];
                            $conjunction_end_ids[$i]['title'] = $conjunction['conjunction_end_title'];
                            $j = 0;
                        }
                        $conjunction_end_ids[$i]['excavations'][$j]['id'] = $conjunction['place_id'];
                        $conjunction_end_ids[$i]['excavations'][$j]['title'] = $conjunction['place_title'];
                        $j++;
                    }
                }
                $conjunctions = array_unique(array_merge($conjunction_start_ids, $conjunction_end_ids), SORT_REGULAR);
            } else {
                $errors[] = 'Нет такой выработки';
            }

        } else {
            $errors[] = 'Параметр edge_id не получен или имеет пустое значение';
        }
        $result = array('conjunctions' => array_merge(array(), $conjunctions), 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Метод получения списка оборудования для выбранной шахты
     * 127.0.0.1/unity/get-equipment-list-for-selected-mine?mine_id=290
     */
    public function actionGetEquipmentListForSelectedMine()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();
        $warnings[] = 'actionGetEquipmentListForSelectedMine. Начал выполнять метод';
        try {
            $post = Assistant::GetServerMethod();                                                                           // переменная для получения запросов с сервера
            $errors = array();                                                                                            // массив для хранения ошибок
            $equipment_new = array();
            if (isset($post['mine_id']) && $post['mine_id'])                                                                // если был отправлен запрос на получени списка сенсоров
            {
                $mine_id = $post['mine_id'];
                $warnings[] = 'actionGetEquipmentListForSelectedMine. ПРоверил входные параметры';
            } else                                                                                                          // если данные не переданы, то выводим ошибку
            {
                throw new Exception('actionGetEquipmentListForSelectedMine. Параметр mine_id не получен или имеет пустое значение');                 // выводим ошибку
            }

            $equipment_list = (new \backend\controllers\cachemanagers\EquipmentCacheController())->getEquipmentMine($mine_id);
            if ($equipment_list)                                                                                      // если данные были получены
            {
                $warnings[] = 'actionGetEquipmentListForSelectedMine. Список сенсоров в кеше заполнен';
            } else                                                                                                        // если данные не были получены, то выводим ошибку
            {
                throw new Exception('actionGetEquipmentListForSelectedMine. Кеш списка сенсоров пуст');                 // выводим ошибку
            }
            $i = -1;
            $j = 0;
            $warnings[] = $equipment_list;
            $warnings[] = 'actionGetEquipmentListForSelectedMine. Количество сортируемых элементов в списке';

            $equipment_list = self::SortArrayFromCache($equipment_list, 'object_title', 'equipment_title');//сортируем наш кеш по 2 параметрам

            foreach ($equipment_list as $equipment) {                                                                     //перебираем сенсоры
                if ($i == -1 or $equipment_new[$i]['object_title'] != $equipment['object_title']) {
                    $i++;
                    $equipment_new[$i]['object_id'] = $equipment['object_id'];
                    $equipment_new[$i]['object_title'] = $equipment['object_title'];
                    $j = 0;
                }
                $equipment_new[$i]['equipments'][$j]['equipment_id'] = $equipment['equipment_id'];
                $equipment_new[$i]['equipments'][$j]['equipment_title'] = $equipment['equipment_title'];
                $j++;
            }


        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionGetEquipmentListForSelectedMine. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'actionGetEquipmentListForSelectedMine. окончил выполнять метод';
        $result = array('errors' => $errors, 'equipment_list' => $equipment_new, 'status' => $status, 'warnings' => $warnings,);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result; // отправляем обратно ввиде FORMAT_JSON
    }

    /**
     * Сортировка массива по 2 параметрам
     * @param $sort_array - массив для сортировки
     * @param $key1 - параметр сортировки №1(Например "object_title"). Верхний уровень группировки
     * @param $key2 - параметр сортировки №2(Например "sensor_title"). Нижний уровень группировки
     * @return $sort_array - отсортированный массив
     */
    public static function SortArrayFromCache($sort_array, $key1, $key2)
    {
        $keys_sort_array = array();                                                                                     //создаем массив параметров сортировки
        $keys_sort_array[] = $key1;                                                                                       //добавляем первый параметр сортировки в массив
        $keys_sort_array[] = $key2;                                                                                       //добавляем второй параметр сортировки в массив
        usort($sort_array, function ($x, $y) use ($keys_sort_array)                                               //вызываем функцию сортировки по заданным значениям
        {
            return $x[$keys_sort_array[0]] > $y[$keys_sort_array[0]] ? 1                                                //сравниваем по 1 параметру
                : ($x[$keys_sort_array[0]] < $y[$keys_sort_array[0]] ? -1
                    : ($x[$keys_sort_array[1]] > $y[$keys_sort_array[1]] ? 1                                            //сравниваем по 2 параметру
                        : ($x[$keys_sort_array[1]] < $y[$keys_sort_array[1]] ? -1
                            : 0)));
        });
        return $sort_array;                                                                                             //возвращаем отсортированный массив
    }

    /**
     * Метод вывода списка ветвть в зависимости от место
     * Место в зависимости от пласта
     * Пласт в зависимости от шахты
     * Выводит шахту по конкретной id
     * Выводить в древовидной структуре
     *
     * рефакторинг:
     * выполнил Якимов М.Н.
     * причина: хз какой долбаеб этол написал, но это работало очень долго и не правильно.
     * входные параметры:
     *      mine_id    "290"                    ключ шахтного поля
     *      sender    "Frontend"                кто запрашивает
     *      caller    "startBuildPlacesTab"     откуда запрашивается (название метода)
     * выходные параметры:
     *          []    -  массив шахт
     *              id          290                 - ключ шахтного поля
     *              title       "Заполярная-2"      - название шахты
     *              plasts      […]                 - список пластов
     *                  []                              массив пластов
     *                      id          2108                - ключ пласта
     *                      title       "Полевая"           - название пласта
     *                      place       […]                 - список мест (такое название не мое, исторически)
     *                          []                              - массив мест
     *                              id          6181                                    - ключ места
     *                              title       "Порож. уг. ветвь ск. ств. 3 гор."      - назание места
     *                              edges       […]                                     - список выработок
     *                                  []
     *                                      id      22143                                   -ключ выработки
     */
    public function actionPlaceSearch()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $mine_array = array();
        $filter = null;
        try {
            $warnings[] = "actionPlaceSearch. Начал выполнять метод";

            $post = Assistant::GetServerMethod();                                                                   //получение данных от ajax-запроса
            if (isset($post['mine_id']) and $post['mine_id'] != '') {                                                              //если передан id ветви
                $mine_id = $post['mine_id'];
                if ($mine_id != -1) {
                    $filter = 'mine.id=' . $mine_id;
                }
                $warnings[] = "actionPlaceSearch. Получил входные параметры $mine_id";
            } else {
                throw new Exception("actionPlaceSearch. Параметры не переданы");
            }

            // получаем список edge для искомой шахты и пласты
            $mines = Mine::find()
                ->innerJoinWith('places.plast')
                ->innerJoinWith('places.edges.lastStatusEdge')
                ->where($filter)
                ->all();
            foreach ($mines as $mine) {
                $plasts = array();
                // собираем объект пласт -> место -> выработка
                // если у места нет пласта, то кидаем его в прочее
                // такой костыль, что бы из объекта сделать массив на беке - долбанутое решение, но оно есть.
                foreach ($mine->places as $place) {
                    if ($place->plast) {
                        $plast_id = $place->plast->id;
                        $plast_title = $place->plast->title;
                    } else {
                        $plast_id = -1;
                        $plast_title = "Без пласта";
                    }
                    $plast_objs[$plast_id]['id'] = $plast_id;
                    $plast_objs[$plast_id]['title'] = $plast_title;
                    $plast_objs[$plast_id]['places'][$place->id]['id'] = $place->id;
                    $plast_objs[$plast_id]['places'][$place->id]['title'] = $place->title;
                    $edges = array();
                    foreach ($place->edges as $edge) {
                        if (!isset($edge->lastStatusEdge) or (isset($edge->lastStatusEdge) and $edge->lastStatusEdge->status_id == 1)) {
                            $edges[] = array('id' => $edge->id);
                        }
                    }
                    $plast_objs[$plast_id]['places'][$place->id]['edges'] = $edges;
                }
//                $warnings[]=$plast_objs;
                //  переделываем из объекта в массив
                $plast_array = array();
                if (isset($plast_objs)) {
                    foreach ($plast_objs as $plast) {
                        $places_array = array();
                        foreach ($plast['places'] as $place) {
                            $places_array[] = $place;
                        }
                        $plast_array[] = array('id' => $plast['id'], 'title' => $plast['title'], 'place' => $places_array);
                    }
                }
                $mine_array[] = array('id' => $mine->id, 'title' => $mine->title, 'plasts' => $plast_array);

            }

            $result = $mine_array;

        } catch (Throwable $exception) {
            $errors[] = "actionPlaceSearch. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "actionPlaceSearch. Закончил выполнять метод";
//        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);  // исторически (фронт ждет данные в таком виде)
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $mine_array;

//        $array_of_plasts_id = array();
//        $post = Yii::$app->request->post();                                                                             // переменная для получения ajax-запросов
//        $errors = array();                                                                                            // массив для хранения ошибок
//        $array_of_mines_plasts_place_edge = array();                                                                    // массив для хранения всех найденных данных ( с группировкой)
//        if (isset($post['mine_id']) && $post['mine_id'] != '')                                                           // если параметр получен и не имеет пустое значение, то находим в модели Place полученное значение
//        {
//            $mine_id_for_seach = $post['mine_id'];                                                                      // сохраняем в новую переменную.
//            $found_mine = Mine::findOne(['id' => $mine_id_for_seach]);                                                  // находим шахту
//            $mine_index = 0;
//            if ($found_mine)                                                                                            // проверяем, найдена ли шахта
//            {
//                $array_of_mines_plasts_place_edge[$mine_index]['id'] = $found_mine->id;                                 // сохраним id шахты (1 уровень)
//                $array_of_mines_plasts_place_edge[$mine_index]['title'] = $found_mine->title;                           // сохраним название шахты
//                $plasts_ids = (new Query())// массив id пластов. Так как у места пласта может быть несколько мест, выборку из таблицы place сделать не получиться. Для
//                ->select('plast.id')// для этого нужно выбрать все id пластов по mine_id, добавить в новый массив и выборку сделать в модели plast по id и выбрать id, title
//                ->from('place')
//                    ->join('JOIN', 'plast', 'plast.id = place.plast_id')
//                    ->where('place.mine_id = ' . $mine_id_for_seach)
//                    ->groupBy('plast_id')
//                    ->all();
//                foreach ($plasts_ids as $plast_id)                                                                      // перебираем все значения массива $plasts_ids, в котором хранятся id-шники plast
//                {
//                    $array_of_plasts_id[] = $plast_id['id'];                                                            // добавим в новый массив
//                }
//                $array_of_plasts_ids = implode(',', $array_of_plasts_id);                                          // преобразуем массив в строку, разделяя запятой
//                $plasts = (new Query())// получаем id и название пласта
//                ->select('id, title')
//                    ->from('plast')
//                    ->where('id IN (' . $array_of_plasts_ids . ')')// id-шники находятся в массиве $plasts_ids
//                    ->orderBy('title')
//                    ->all();
//                $plast_index = 0;
//                foreach ($plasts as $plast)                                                                             // Для каждого пласта находим место
//                {
//                    $array_of_mines_plasts_place_edge[$mine_index]['plasts'][$plast_index]['id'] = $plast['id'];        // добавим id пласта (2 уровень)
//                    $array_of_mines_plasts_place_edge[$mine_index]['plasts'][$plast_index]['title'] = $plast['title'];  // добавим название пласта
//                    $places = (new Query())// по plast_id находим все места
//                    ->select('id, title')
//                        ->from('place')
//                        ->where('plast_id = ' . $plast['id'] . ' and place.mine_id = ' . $mine_id_for_seach)
//                        ->orderBy('title')
//                        ->all();
//                    $place_index = 0;
//                    foreach ($places as $place)                                                                         // для каждого места находим ветви/ребра
//                    {
//                        $array_of_mines_plasts_place_edge[$mine_index]['plasts'][$plast_index]['place'][$place_index]['id'] = $place['id']; // добавим id места (3 уровень)
//                        $array_of_mines_plasts_place_edge[$mine_index]['plasts'][$plast_index]['place'][$place_index]['title'] = $place['title']; // добавим название места (3 уровень)
//                        $edges = (new Query())// для каждого место находим ветви/ребра
//                        ->select('id')
//                            ->from('edge')
//                            ->where('place_id = ' . $place['id'])
//                            ->all();
//                        $edge_index = 0;
//                        foreach ($edges as $edge)                                                                       // в цикле добавим id ветви в новый массив
//                        {
//                            $array_of_mines_plasts_place_edge[$mine_index]['plasts'][$plast_index]['place'][$place_index]['edges'][$edge_index]['id'] = $edge['id']; // добавим id ветви
//                            $edge_index++;
//                        }
//                        $place_index++;
//                    }
//                    $plast_index++;
//                }
//                $mine_index++;
//                //echo json_encode($array_of_mines_plasts_place_edge);
//                Yii::$app->response->format = Response::FORMAT_JSON;                                           
//                Yii::$app->response->data = $array_of_mines_plasts_place_edge;                                        // отправляем обратно ввиде ajax формат
//            } else {
//                $errors[] = 'Указанная шахта не найдена';
//            }
//        } else                                                                                                            // если параметр не получен или имеет пустое значение, выводим ошибку
//        {
//            $errors[] = 'Параметр place_title не получен или имеет пустое значение';                                    // выводим ошибку
//        }
    }

//формирует текущие СВЕДЕНИЯ об сенсорах для Unity
//зарегистрированных на шахте (имеющих значение параметра 122) фактически расположенных в шахте и налицие сведений о их местоположении
//используется для первичного построения модели Unity
//при передаче конкретного id оборудования может вернуть только его сведения
    public function actionGetSensors()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $sql_filter = 'true';
        //фильтр по шахте
        if (isset($post['mine_id']) && $post['mine_id'] != '') $sql_filter .= ' AND mine_id=' . $post['mine_id'] . '';
        if (isset($post['sensor_id']) && $post['sensor_id'] != '') $sql_filter .= ' AND sensor_id=' . $post['sensor_id'] . '';

        $errors = array();
        $sensors_list = array();
        $nodes = array(45, 46, 113, 105, 90, 91);                                                                       // id узлов связи
        try {
            $sensors_list = (new Query())//запрос напрямую из базы по вьюшке view_personal_areas
            ->select(
                [
                    'mine_id',
                    'sensor_id',
                    'sensor_title',
                    'object_id',
                    'object_type_id'
                ])
                ->from(['`view_sensor_main_unity`'])
                ->where($sql_filter)
                ->all();
            $index = 0;
            foreach ($sensors_list as $sensor)                                                                          // в цикле проверяем,
            {
                if (array_search($sensor['object_id'], $nodes))                                                          // если текущий объект== узел связи, то есть значение $sensor['object_id'] ищем в массиве $nodes = array(45,46,113,105,90,91);
                {
                    $sensors_list[$index]['object_type_id'] = '001';                                                         // то указываем, что это узель связи
                }
                $index++;
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
        $result = array('Items' => $sensors_list, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * actionGetSensorsParameters - Метод получения параметров сенсора из кэша или БД, а именно 3 параметра - статус,
     *                          местоположение и позиция (ПКМ на схеме шахты информация о сенсоре)
     * Метод принимает GET/POST.
     * unity/get-sensors-parameters?mine_id=290&sensor_id=115919
     * Created by: Якимов М.Н.
     */
    public function actionGetSensorsParameters()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $sensor_image = null;
        $sensor_place_title = '-';
        $sensor_title = '-';
        $sensor_image = '';

        try {
            /**
             * Блок обработки входных параметров
             */
            $place_id = -1;
            $post = Assistant::GetServerMethod();
            if (isset($post['mine_id']) && $post['mine_id'] != '') {
                $mine_id = $post['mine_id'];                                                                          //записываем сенсор id для того, что бы потом найти place для этого сенсора
            } else {
                throw new Exception("actionGetSensorsParameters. Не передан входной параметр mine_id");
            }
            if (isset($post['sensor_id']) && $post['sensor_id'] != '') {
                $sensor_id = $post['sensor_id'];                                                                          //записываем сенсор id для того, что бы потом найти place для этого сенсора
            } else {
                throw new Exception("actionGetSensorsParameters. Не передан входной параметр sensor_id");
            }

            /*********************** ПОЛУЧЕМ КОНКРЕТНЫЙ СЕНСОР  С КОНКРЕТНЫМИ ПАРАМЕТРАМИ ***********/
            if (!COD) {
                $sensor_cache_controller = (new SensorCacheController());
                $sensor = $sensor_cache_controller->getSensorMineBySensorOneHash($mine_id, $sensor_id); //Находим сенсор в главном списке кэша SensorMine, то есть ли сенсор вооще в шахте
                if ($sensor !== false)                                                                  // если  сенсор есть в шахте, то получаем его статус, местоположения и xyz
                {
                    $warnings[] = 'actionGetSensorsParameters. Нашел сенсор в кеше SensorMine';
                } else {
                    throw new Exception('actionGetSensorsParameters. Не нашел сенсор в кеше SensorMine');
                }
            } else {
                $sensors = SensorBasicController::getSensorMain($mine_id, $sensor_id);
                if (!$sensors)                                                                  // если  сенсор есть в шахте, то получаем его статус, местоположения и xyz
                {
                    throw new Exception('actionGetSensorsParameters. Не нашел сенсор в кеше SensorMine');
                }
                $sensor = $sensors[0];
            }

            $sensor_object_type_id = $sensor['object_type_id'];
            $sensor_object_id = $sensor['object_id'];
            $sensor_title = $sensor['sensor_title'];
            $warnings[] = "actionGetSensorsParameters. Типой объект датчика равен $sensor_object_id";

            $parameter_type_id = SensorCacheController::isStaticSensor($sensor_object_type_id);

            $warnings[] = "actionGetSensorsParameters. Параметр тайп id равен $parameter_type_id";
            /**************             ПОЛУЧАЕМ Состояние СЕНСОРА                      **********/
            if (!COD) {
                $warnings[] = "КЕШ!";
                $sensor_status = $sensor_cache_controller->getParameterValueHash($sensor_id, 164, 3);
                $warnings[] = 'actionGetSensorsParameters. ответ от параметра 164';
                $warnings[] = $sensor_status;

                if ($sensor_status !== false) {
                    $result[] = $this->AddSensorParamValuesToArray2($sensor_status, $sensor_object_id);
                } else {
                    $warnings[] = "actionGetSensorsParameters. Для датчика с sensor_id = $sensor_id не найден параметр 164 - статус (нет такого ключа кэша)";
                }
                /**************             ПОЛУЧАЕМ МЕСТОПОЛОЖЕНИЯ СЕНСОРА                      **********/
                $sensor_status = $sensor_cache_controller->getParameterValueHash($sensor_id, 122, $parameter_type_id);
                $warnings[] = 'actionGetSensorsParameters. ответ от параметра 122';
                $warnings[] = $sensor_status;
                if ($sensor_status !== false) {
                    $sensor_parameter_value = $this->AddSensorParamValuesToArray2($sensor_status, $sensor_object_id);
                    $place_id = $sensor_parameter_value['value'];
                    $result[] = $sensor_parameter_value;
                } else {
                    $warnings[] = "actionGetSensorsParameters. Для датчика с sensor_id = $sensor_id не найден параметр 122 - местоположение (нет такого ключа кэша)";
                }

                /**************             ПОЛУЧАЕМ ПОЗИЦИЙ СЕНСОРА                      **************/
                $sensor_status = $sensor_cache_controller->getParameterValueHash($sensor_id, 83, $parameter_type_id);
                if ($sensor_status !== false) {
                    $result[] = $this->AddSensorParamValuesToArray2($sensor_status, $sensor_object_id);
                } else {
                    $warnings[] = "actionGetSensorsParameters.Для датчика с sensor_id = $sensor_id не найден параметр 83 - статус (нет такого ключа кэша)";
                }
            } else {
                $warnings[] = "БД!";
                if ($parameter_type_id == 1) {
                    $parameters_122_83 = (new Query())
                        ->select(['sensor_id', 'sensor_parameter_id', 'parameter_id', 'parameter_type_id', 'date_time', 'value', 'status_id'])
                        ->from('view_initSensorParameterHandbookValue')
                        ->where('parameter_id=83 or parameter_id=122')
                        ->andWhere('sensor_id=' . $sensor_id)
                        ->all();
                } else {
                    $parameters_122_83 = (new Query())
                        ->select(['sensor_id', 'sensor_parameter_id', 'parameter_id', 'parameter_type_id', 'date_time', 'value', 'status_id'])
                        ->from('view_initSensorParameterValue')
                        ->where('parameter_id=83 or parameter_id=122')
                        ->andwhere('parameter_type_id = 2')
                        ->andWhere('sensor_id=' . $sensor_id)
                        ->all();
                }

                if ($parameters_122_83) {
                    foreach ($parameters_122_83 as $parameter) {
                        $result[] = $this->AddSensorParamValuesToArray2($parameter, $sensor_object_id);
                        if ($parameter['parameter_id'] == 122) {
                            $place_id = $parameter['value'];
                        }
                    }
                } else {
                    $warnings[] = "actionGetSensorsParameters.Для датчика с sensor_id = $sensor_id не найден параметр 83,122 - координата/место (нет такого ключа кэша)";
                }
                $warnings[] = $result;

                $parameter_164 = (new Query())
                    ->select(['sensor_id', 'sensor_parameter_id', 'parameter_id', 'parameter_type_id', 'date_time', 'value', 'status_id'])
                    ->from('view_initSensorParameterValue')
                    ->where('parameter_id=164')
                    ->andwhere('parameter_type_id = 3')
                    ->all();

                if ($parameter_164) {
                    $result[] = $this->AddSensorParamValuesToArray2($parameter_164[0], $sensor_object_id);
                } else {
                    $warnings[] = "actionGetSensorsParameters.Для датчика с sensor_id = $sensor_id не найден параметр 164 - статус (нет такого ключа кэша)";
                }

            }
            /******     ПОЛУЧЕНИЕ НАЗВАНИЕ сенсора и place  */
            if ($place_id != '' && $place_id != -1) {
                $place = Place::findOne(['id' => $place_id]);
                if ($place) {
                    $sensor_place_title = $place->title;
                } else {
                    $warnings[] = "actionGetSensorsParameters. Название датчика с place_id = $place_id не найдено";
                }
            }
            /**************             ПОЛУЧАЕМ ПУТЬ ДО КАРТИНКИ СЕНСОРА                      **************/
            $sensor_image = SpecificSensorController::GetPictureForObject($sensor_object_id);
//                            $sensor_status = (new SensorCacheController())->getParameterValue($sensor_id,3,1);
//                            if ($sensor_status != false) {
//                                $sensor_image = $sensor_status['value'];
//                            } else {
//                                $debug_info[] = "Для датчика с sensor_id = $sensor_id не найден параметр 83 - статус (нет такого ключа кэша)";
//                            }
        } catch (Throwable $ex) {
            $errors[] = "actionGetSensorsParameters. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result_main = array('Items' => $result,
            'sensor_place_title' => $sensor_place_title,
            'sensor_image' => $sensor_image,
            'sensor_title' => $sensor_title,
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод получения параметров оборудования из кэша, а именно 3 параметра - статус, местополдожение и позиция
     * Метод принимает GET/POST.
     * unity/get-equipments-parameters?mine_id=290&equipment_id=115919
     * Copy paste: Файзуллоев А.Э.
     */
    public function actionGetEquipmentsParameters()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $equipment_image = null;
        $equipment_place_title = '-';
        $equipment_title = '-';
        $equipment_image = '';

        try {
            /**
             * Блок обработки входных параметров
             */
            $post = Assistant::GetServerMethod();
            if (isset($post['mine_id']) && $post['mine_id'] != '') {
                $mine_id = $post['mine_id'];
            } else {
                throw new Exception("actionGetEquipmentsParameters. Не передан входной параметр mine_id");
            }
            if (isset($post['equipment_id']) && $post['equipment_id'] != '') {
                $equipment_id = $post['equipment_id'];
            } else {
                throw new Exception("actionGetEquipmentsParameters. Не передан входной параметр equipment_id");
            }
            $equipment_cache_controller = (new \backend\controllers\cachemanagers\EquipmentCacheController());
            $equipment = $equipment_cache_controller->getEquipmentMineByEquipmentOne($mine_id, $equipment_id);
            if ($equipment !== false) {
                $warnings[] = 'actionGetEquipmentsParameters. Нашел оборудования в кеше';
            } else {
                throw new Exception('actionGetEquipmentsParameters. Не нашел оборудования в кеше EqMi');
            }
            $equipment_object_id = $equipment['object_id'];
            $equipment_title = $equipment['equipment_title'];
            $warnings[] = "actionGetEquipmentsParameters. Типой объект датчика равен $equipment_object_id";
            $parameter_type_id = 2;
            /**************             ПОЛУЧАЕМ Состояние оборудования                      **********/
            $equipment_status = $equipment_cache_controller->getParameterValue($equipment_id, 164, 3);

            $warnings[] = 'actionGetEquipmentsParameters. ответ от параметра 164';
            $warnings['equipment_status'] = $equipment_status;

            if ($equipment_status !== false) {
                $warnings[] = 'actionGetEquipmentsParameters. найден обородувания в гланый кэш ';
                $result[] = $this->AddEquipmentParamValuesToArray($equipment_status, $equipment_object_id);
            } else {
                $warnings[] = "actionGetEquipmentsParameters. Для датчика с equipment_id = $equipment_id не найден параметр 164 - статус (нет такого ключа кэша)";
            }
            /**************             ПОЛУЧАЕМ МЕСТОПОЛОЖЕНИЯ оборудования                      **********/
            $equipment_status = $equipment_cache_controller->getParameterValue($equipment_id, 122, $parameter_type_id);
            $warnings[] = 'actionGetEquipmentsParameters. ответ от параметра 122';
            //$warnings[] = $equipment_status;
            if ($equipment_status !== false) {
                $equipment_parameter_value = $this->AddEquipmentParamValuesToArray($equipment_status, $equipment_object_id);
                $place_id = $equipment_parameter_value['value'];
                $result[] = $equipment_parameter_value;
            } else {
                $warnings[] = "actionGetEquipmentsParameters. Для датчика с equipment_id = $equipment_id не найден параметр 122 - местоположение (нет такого ключа кэша)";
            }
            /******     ПОЛУЧЕНИЕ НАЗВАНИЕ оборудования и place  */
            if ($place_id != '' && $place_id != -1) {
                $place = Place::findOne(['id' => $place_id]);
                if ($place) {
                    $equipment_place_title = $place->title;
                } else {
                    $warnings[] = "actionGetEquipmentsParameters. Название датчика с place_id = $place_id не найдено";
                }
            }
            /**************             ПОЛУЧАЕМ ПОЗИЦИЙ оборудования                      **************/
            $equipment_status = $equipment_cache_controller->getParameterValue($equipment_id, 83, $parameter_type_id);
            if ($equipment_status !== false) {
                $result[] = $this->AddEquipmentParamValuesToArray($equipment_status, $equipment_object_id);
            } else {
                $warnings[] = "actionGetEquipmentsParameters. Для датчика с equipment_id = $equipment_id не найден параметр 83 - статус (нет такого ключа кэша)";
            }

            /**************             ПОЛУЧАЕМ ПУТЬ ДО КАРТИНКИ оборудования                      **************/
            $equipment_image = SpecificSensorController::GetPictureForObject($equipment_object_id); //TODO допустим он возврашает.... в будущем надо уточнить у МН

        } catch (Throwable $ex) {
            $errors[] = "actionGetEquipmentsParameters. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result_main = array('Items' => $result,
            'equipment_place_title' => $equipment_place_title,
            'equipment_image' => $equipment_image,
            'equipment_title' => $equipment_title,
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод возврата тупиковых выраболток
     * Тупиковые выработки находятся во вюшках
     * Автор: ОДИЛОВ О.У.
     */
    public function actionGetTupicEdge()
    {
        $post = Yii::$app->request->post();
        $errors = array();
        $tupic_edges = array();
        if (isset($post['mine_id']) and $post['mine_id'] != '') {
            $mine_id = $post['mine_id'];
            $mine = Mine::findOne(['id' => $mine_id]);
            if ($mine) {
                $tupic_edges = (new Query())
                    ->select(['conjuction_id'])
                    ->from('view_conjuction_sum')
                    ->where(['mine_id' => $mine_id])
                    ->all();
                if (!$tupic_edges) $errors[] = 'В БД нет тупиковых выработок';
            } else {
                $errors[] = 'Неправильный ID шахты. Шахта с таким ID нет в БД';
            }
        } else $errors[] = 'Шахта не указана';
        $result = array('Items' => $tupic_edges, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Метод вывода списка узлов связи для добавления узлов связи
     */
    public function actionSendSensorOnSchema()
    {
        $snsrs = (new Query())//ищем в БД все сенсоры которые можно устанавливать на схему(кроме мобильных датчиков)
        ->select('sensor_id, sensor_title, object_id, mine_id, mine_title, image')
            ->from('view_sensor_main_all')
            ->where('object_type_id != 12')
            ->orderBy(['sensor_title' => SORT_ASC])
            ->all();
        $sensorList = array();                                                                                          //массив для результата
        $i = 0;
        foreach ($snsrs as $snsor) {                                                                                    //начинаем перебирать массив сенсоров
            $sensorList[$i]['id'] = $snsor['sensor_id'];
            $sensorList[$i]['title'] = $snsor['sensor_title'];
            $sensorList[$i]['object_id'] = $snsor['object_id'];
            if ($snsor['mine_id'] != NULL and $snsor['mine_id'] != '')                                                  // проверяем, существует ли узел связи по sensor_id в шахте (Потому что объект либо есть в шахте, либо его вообще нет)
            {
                $sensorList[$i]['node_exist'] = 'true';                                                                 // если есть, то добавим
            } else {
                $sensorList[$i]['node_exist'] = 'false';
            }
            $sensorList[$i]['image'] = $snsor['image'];
            $i++;
        }
        ArrayHelper::multisort($sensorList, 'title', SORT_ASC);                                                         //сортируем массив по названию датчиков
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $sensorList;                                                                        //отдаем результат
    }

    /**
     * метод по получению списка всего оборудования со статусом "установлено" или нет
     */
    public function actionSendEquipmentOnSchema()
    {
        $equipments = (new Query())//ищем в БД все сенсоры которые можно устанавливать на схему(кроме мобильных датчиков)
        ->select('equipment_id, equipment_title, object_id, mine_id, mine_title, image')
            ->from('view_equipment_main_all')
            ->orderBy(['equipment_title' => SORT_ASC])
            ->all();
        $equipmentList = array();                                                                                          //массив для результата
        $i = 0;
        foreach ($equipments as $equipment) {                                                                                    //начинаем перебирать массив сенсоров
            $equipmentList[$i]['id'] = $equipment['equipment_id'];
            $equipmentList[$i]['title'] = $equipment['equipment_title'];
            $equipmentList[$i]['object_id'] = $equipment['object_id'];
            if ($equipment['mine_id'] != NULL and $equipment['mine_id'] != '')                                                  // проверяем, существует ли узел связи по sensor_id в шахте (Потому что объект либо есть в шахте, либо его вообще нет)
            {
                $equipmentList[$i]['equipment_exist'] = 'true';                                                                 // если есть, то добавим
            } else {
                $equipmentList[$i]['equipment_exist'] = 'false';
            }
            $equipmentList[$i]['image'] = $equipment['image'];
            $i++;
        }
        ArrayHelper::multisort($equipmentList, 'title', SORT_ASC);                                                         //сортируем массив по названию датчиков
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $equipmentList;                                                                        //отдаем результат
    }

    /**
     * Метод получения списка сенсоров для выбранной шахты
     * 127.0.0.1/unity/get-sensor-list-for-selected-mine?mine_id=290
     */
    public function actionGetSensorListForSelectedMine()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();
        $warnings[] = 'actionGetSensorListForSelectedMine. Начал выполнять метод';
        try {
            $post = Assistant::GetServerMethod();                                                                           // переменная для получения запросов с сервера
            $errors = array();                                                                                            // массив для хранения ошибок
            $sensors_new = array();
            if (isset($post['mine_id']) && $post['mine_id'])                                                                // если был отправлен запрос на получени списка сенсоров
            {
                $mine_id = $post['mine_id'];
                $warnings[] = 'actionGetSensorListForSelectedMine. ПРоверил входные параметры';
            } else                                                                                                          // если данные не переданы, то выводим ошибку
            {
                throw new Exception('actionGetSensorListForSelectedMine. Параметр mine_id не получен или имеет пустое значение');                 // выводим ошибку
            }

            $sensor_list = (new SensorCacheController())->getSensorMineHash($mine_id);
            if ($sensor_list)                                                                                      // если данные были получены
            {
                $warnings[] = 'actionGetSensorListForSelectedMine. Список сенсоров в кеше заполнен';
            } else                                                                                                        // если данные не были получены, то выводим ошибку
            {
                throw new Exception('actionGetSensorListForSelectedMine. Кеш списка сенсоров пуст');                 // выводим ошибку
            }
            $i = -1;
            $j = 0;
            $sensor_list = self::SortArrayFromCache($sensor_list, 'object_title', 'sensor_title');//сортируем наш кеш по 2 параметрам
            foreach ($sensor_list as $sensor) {                                                                     //перебираем сенсоры
                if ($i == -1 or $sensors_new[$i]['object_title'] != $sensor['object_title']) {
                    $i++;
                    $sensors_new[$i]['object_id'] = $sensor['object_id'];
                    $sensors_new[$i]['object_title'] = $sensor['object_title'];
                    $j = 0;
                }
                $sensors_new[$i]['sensors'][$j]['sensor_id'] = $sensor['sensor_id'];
                $sensors_new[$i]['sensors'][$j]['sensor_title'] = $sensor['sensor_title'];
                $j++;
            }


        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionGetSensorListForSelectedMine. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'actionGetSensorListForSelectedMine. окончил выполнять метод';
        $result = array('errors' => $errors, 'objects' => $sensors_new, 'status' => $status, 'warnings' => $warnings,);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result; // отправляем обратно ввиде FORMAT_JSON
    }

    /**
     * Метод получения значения параметра конкретного объекта (Сенсор, работник, выработка) за определенный период времени
     * Возвращает данные параметра какого-то конкретного объекта в промежуток какого-то времени.
     * Получает данные из object_parameter_handbook_value или object_parameter_value в  зависимости от типа параметра.
     * Обязательные параметры:
     *      $post['parameters'] - параметры в виде "2-99, 2-98"
     *      $post['specific_object_name'] - название объекта (sensor или worker)
     *      $post['specific_object_id'] - идентификатор объекта (sensor_id или worker_id )
     *      date_start - дата начало
     *      date_end - дата завершения
     * Пример вызова:
     * http://localhost/unity/get-object-values?specific_object_name=worker&parameters=2-99&specific_object_id=2030093&date_end=2018-12-31&date_start=2018-01-10
     * http://localhost/unity/get-object-values?specific_object_name=sensor&parameters=2-99,2-98&specific_object_id=2030093&date_end=2018-12-31&date_start=2018-01-10
     * Выборка идет через процедуру  GetObjectValues();
     * Created by: Одилов О.У. on 27.11.2018 16:59
     */
    public function actionGetObjectValues()
    {
        $log = new LogAmicumFront("actionGetObjectValues");

        $post = Assistant::GetServerMethod();

        $object_values = array();
        $limit_trend_value = 0;

        try {
            $log->addLog("Начала выполнять метод");
            $log->addData($post, '$post', __LINE__);

            if (isset($post['specific_object_name']) and $post['specific_object_name'] != ''
                and isset($post['specific_object_id']) and $post['specific_object_id'] != ''
                and isset($post['parameters']) and $post['parameters'] != ''
                and isset($post['date_start']) and $post['date_start'] != ''
                and isset($post['date_end']) and $post['date_end'] != ''
                and isset($post['alarm_parameter_id']) and $post['alarm_parameter_id'] != ''
                and isset($post['parameter_id']) and $post['parameter_id'] != ''
                and isset($post['parameter_type_id']) and $post['parameter_type_id'] != ''
            ) {
                $specific_object_name = $post['specific_object_name'];                                                  // название таблицы где искать основной объект
                $specific_object_id = $post['specific_object_id'];                                                      // ключ конкретного объекта
                $alarm_parameter_id = $post['alarm_parameter_id'];                                                      // ключ параметра уставки
                $date_start = date("Y-m-d H:i:s", strtotime($post['date_start']));                               // дата начала выборки
                $date_end = date("Y-m-d H:i:s", strtotime($post['date_end']));                                   // дата окончания выборки
                $log->addData($date_start, '$date_start', __LINE__);
                $log->addData($date_end, '$date_end', __LINE__);
            } else {
                throw new Exception("Не все параметры переданы");
            }

            $parameters = Assistant::AddConditionForParameters($post['parameters'], 'parameters');
            $log->addData($parameters, '$parameters', __LINE__);

            if ($specific_object_name == 'sensor') {
                $object_values = (new Query())
                    ->select([
                        'sensor_parameter.sensor_id  as object_id',
                        'sensor_parameter_id as object_parameter_id',
                        'sensor_parameter.parameter_id as parameter_id',
                        'sensor_parameter.parameter_type_id as parameter_type_id',
                        'sensor_parameter_value.id as object_parameter_value_id',
                        'sensor_parameter_value.value as value',
                        'sensor_parameter_value.date_time as date_time'
                    ])
                    ->from('sensor_parameter')
                    ->innerJoin('sensor_parameter_value', 'sensor_parameter_value.sensor_parameter_id = sensor_parameter.id')
                    ->where(['sensor_parameter.sensor_id' => $specific_object_id])
                    ->andWhere($parameters)
                    ->andWhere(['BETWEEN', 'date_time', $date_start, $date_end])
                    ->orderBy(['date_time' => SORT_ASC])
                    ->all();

                $object_values = array_merge($object_values, (new Query())
                    ->select([
                        'sensor_parameter.sensor_id  as object_id',
                        'sensor_parameter_id as object_parameter_id',
                        'sensor_parameter.parameter_id as parameter_id',
                        'sensor_parameter.parameter_type_id as parameter_type_id',
                        'sensor_parameter_value_history.id as object_parameter_value_id',
                        'sensor_parameter_value_history.value as value',
                        'sensor_parameter_value_history.date_time as date_time'
                    ])
                    ->from('sensor_parameter')
                    ->innerJoin('sensor_parameter_value_history', 'sensor_parameter_value_history.sensor_parameter_id = sensor_parameter.id')
                    ->where(['sensor_parameter.sensor_id' => $specific_object_id])
                    ->andWhere($parameters)
                    ->andWhere(['BETWEEN', 'date_time', $date_start, $date_end])
                    ->orderBy(['date_time' => SORT_ASC])
                    ->all()
                );

                if ($object_values) {
                    $sensor_place = (new Query())
                        ->select([
                            'sensor_parameter_value.date_time as date_time',
                            'place.id as place_id',
                            'place.title as place_title'
                        ])
                        ->from('sensor_parameter')
                        ->innerJoin('sensor_parameter_value', 'sensor_parameter_value.sensor_parameter_id = sensor_parameter.id')
                        ->innerJoin('place', 'place.id = sensor_parameter_value.value')
                        ->where([
                            'sensor_parameter.sensor_id' => $specific_object_id,
                            'sensor_parameter.parameter_id' => 122,
                            'sensor_parameter.parameter_type_id' => 2
                        ])
                        ->andWhere(['BETWEEN', 'date_time', $date_start, $date_end])
                        ->indexBy('date_time')
                        ->all();

                    $sensor_place = array_merge($sensor_place, (new Query())
                        ->select([
                            'sensor_parameter_value_history.date_time as date_time',
                            'place.id as place_id',
                            'place.title as place_title'
                        ])
                        ->from('sensor_parameter')
                        ->innerJoin('sensor_parameter_value_history', 'sensor_parameter_value_history.sensor_parameter_id = sensor_parameter.id')
                        ->innerJoin('place', 'place.id = sensor_parameter_value_history.value')
                        ->where([
                            'sensor_parameter.sensor_id' => $specific_object_id,
                            'sensor_parameter.parameter_id' => 122,
                            'sensor_parameter.parameter_type_id' => 2
                        ])
                        ->andWhere(['BETWEEN', 'date_time', $date_start, $date_end])
                        ->indexBy('date_time')
                        ->all()
                    );

                    foreach ($object_values as &$sensor_value) {
                        if ($sensor_place && isset($sensor_place[$sensor_value['date_time']])) {
                            $sensor_value['place_id'] = $sensor_place[$sensor_value['date_time']]['place_id'];
                            $sensor_value['place_title'] = $sensor_place[$sensor_value['date_time']]['place_title'];
                        } else {
                            $sensor_value['place_id'] = '-';
                            $sensor_value['place_title'] = '-';
                        }
                    }
                }

                /** ПОЛУЧЕНИЕ УСТАВКИ СЕНСОРА */
                $limit_trend = SensorBasicController::getSensorParameterHandbookValueByDate($date_end, $specific_object_id, $alarm_parameter_id);
                if ($limit_trend) {
                    $limit_trend_value = $limit_trend[0]['value'];
                }

            } else if ($specific_object_name == 'worker') {
                $log->addLog("Ищу данные по работнику");
                $object_values = (new Query())
                    ->select([
                        'worker_object.worker_id  as object_id',
                        'worker_parameter_id as object_parameter_id',
                        'worker_parameter.parameter_id as parameter_id',
                        'worker_parameter.parameter_type_id as parameter_type_id',
                        'worker_parameter_value.id as object_parameter_value_id',
                        'worker_parameter_value.value as value',
                        'worker_parameter_value.date_time as date_time'
                    ])
                    ->from('worker_object')
                    ->innerJoin('worker_parameter', 'worker_parameter.worker_object_id = worker_object.id')
                    ->innerJoin('worker_parameter_value', 'worker_parameter_value.worker_parameter_id = worker_parameter.id')
                    ->where(
                        ['or',
                            ['worker_object.worker_id' => $specific_object_id],
                            ['worker_object.id' => $specific_object_id],
                        ]
                    )
                    ->andWhere($parameters)
                    ->andWhere(['BETWEEN', 'date_time', $date_start, $date_end])
                    ->orderBy(['date_time' => SORT_ASC])
                    ->all();

                $object_values = array_merge($object_values, (new Query())
                    ->select([
                        'worker_object.worker_id  as object_id',
                        'worker_parameter_id as object_parameter_id',
                        'worker_parameter.parameter_id as parameter_id',
                        'worker_parameter.parameter_type_id as parameter_type_id',
                        'worker_parameter_value_history.id as object_parameter_value_id',
                        'worker_parameter_value_history.value as value',
                        'worker_parameter_value_history.date_time as date_time'
                    ])
                    ->from('worker_object')
                    ->innerJoin('worker_parameter', 'worker_parameter.worker_object_id = worker_object.id')
                    ->innerJoin('worker_parameter_value_history', 'worker_parameter_value_history.worker_parameter_id = worker_parameter.id')
                    ->where(['or',
                        ['worker_object.worker_id' => $specific_object_id],
                        ['worker_object.id' => $specific_object_id],
                    ])
                    ->andWhere($parameters)
                    ->andWhere(['BETWEEN', 'date_time', $date_start, $date_end])
                    ->orderBy(['date_time' => SORT_ASC])
                    ->all()
                );

                if ($object_values) {
                    $worker_place = (new Query())
                        ->select([
                            'worker_parameter_value.date_time as date_time',
                            'place.id as place_id',
                            'place.title as place_title'
                        ])
                        ->from('worker_object')
                        ->innerJoin('worker_parameter', 'worker_parameter.worker_object_id = worker_object.id')
                        ->innerJoin('worker_parameter_value', 'worker_parameter_value.worker_parameter_id = worker_parameter.id')
                        ->innerJoin('place', 'place.id = worker_parameter_value.value')
                        ->where(['or',
                            ['worker_object.worker_id' => $specific_object_id],
                            ['worker_object.id' => $specific_object_id],
                        ])
                        ->andWhere([
                            'worker_parameter.parameter_id' => 122,
                            'worker_parameter.parameter_type_id' => 2
                        ])
                        ->andWhere(['BETWEEN', 'date_time', $date_start, $date_end])
                        ->indexBy('date_time')
                        ->all();

                    $worker_place = array_merge($worker_place, (new Query())
                        ->select([
                            'worker_parameter_value_history.date_time as date_time',
                            'place.id as place_id',
                            'place.title as place_title'
                        ])
                        ->from('worker_object')
                        ->innerJoin('worker_parameter', 'worker_parameter.worker_object_id = worker_object.id')
                        ->innerJoin('worker_parameter_value_history', 'worker_parameter_value_history.worker_parameter_id = worker_parameter.id')
                        ->innerJoin('place', 'place.id = worker_parameter_value_history.value')
                        ->where(['or',
                            ['worker_object.worker_id' => $specific_object_id],
                            ['worker_object.id' => $specific_object_id],
                        ])
                        ->andWhere([
                            'worker_parameter.parameter_id' => 122,
                            'worker_parameter.parameter_type_id' => 2
                        ])
                        ->andWhere(['BETWEEN', 'date_time', $date_start, $date_end])
                        ->indexBy('date_time')
                        ->all()
                    );
                    foreach ($object_values as &$worker_value) {
                        if ($worker_place && isset($worker_place[$worker_value['date_time']])) {
                            $worker_value['place_id'] = $worker_place[$worker_value['date_time']]['place_id'];
                            $worker_value['place_title'] = $worker_place[$worker_value['date_time']]['place_title'];
                        } else {
                            $worker_value['place_id'] = '-';
                            $worker_value['place_title'] = '-';
                        }
                    }
                }

                /** ПОЛУЧЕНИЕ УСТАВКИ ДАТЧИКА РАБОТНИКА
                 * ВАЖНО! для людей уставка может быть разной, т.к. светильник может быть в разных выработках с разными уставками
                 */
                if ($alarm_parameter_id == 263) {                                                                       // уставка СН4
                    $limit_trend_value = 1;
                } else if ($alarm_parameter_id == 264) {                                                                // уставка СО
                    $limit_trend_value = 17;
                } else if ($alarm_parameter_id == 23) {                                                                 // уставка запыленность
                    $limit_trend_value = 150;
                } else if ($alarm_parameter_id == 21) {                                                                 // уставка О2
                    $limit_trend_value = 20;
                } else if ($alarm_parameter_id == 31) {                                                                 // уставка СО2
                    $limit_trend_value = 1;
                }

            } else if ($specific_object_name == 'equipment') {
                $object_values = (new Query())
                    ->select([
                        'equipment_parameter_value.id as object_parameter_value_id',
                        'equipment_parameter_value.equipment_parameter_id as object_parameter_id',
                        'equipment_parameter_value.date_time as date_time',
                        'equipment_parameter_value.value'
                    ])
                    ->from('equipment_parameter')
                    ->innerJoin('equipment_parameter_value', 'equipment_parameter_value.equipment_parameter_id = equipment_parameter.id')
                    ->where(['equipment_parameter.equipment_id' => $specific_object_id])
                    ->andWhere(['parameter_id' => 164, 'parameter_type_id' => 3])
                    ->andWhere(['BETWEEN', 'date_time', $date_start, $date_end])
                    ->orderBy(['date_time' => SORT_ASC])
                    ->all();

                $equipment_place = (new Query())
                    ->select([
                        'equipment_parameter_value.id as object_parameter_value_id',
                        'equipment_parameter_value.date_time as date_time',
                        'place.id as place_id',
                        'place.title as place_title'
                    ])
                    ->from('equipment_parameter')
                    ->innerJoin('equipment_parameter_value', 'equipment_parameter_value.equipment_parameter_id = equipment_parameter.id')
                    ->innerJoin('place', 'place.id = equipment_parameter_value.value')
                    ->where([
                        'equipment_parameter.equipment_id' => $specific_object_id,
                        'equipment_parameter.parameter_id' => 122,
                        'equipment_parameter.parameter_type_id' => 2
                    ])
                    ->indexBy('date_time')
                    ->all();
                foreach ($object_values as &$equipment_value) {
                    if ($equipment_place && isset($equipment_place[$equipment_value['date_time']])) {
                        $equipment_value['place_id'] = $equipment_place[$equipment_value['date_time']]['place_id'];
                        $equipment_value['place_title'] = $equipment_place[$equipment_value['date_time']]['place_title'];
                    } else {
                        $equipment_value['place_id'] = '-';
                        $equipment_value['place_title'] = '-';
                    }
                }

                /** ПОЛУЧЕНИЕ УСТАВКИ ДАТЧИКА ОБОРУДОВАНИЯ
                 * ВАЖНО! для оборудования уставка может быть разной, т.к. светильник может быть в разных выработках с разными уставками
                 */
                if ($alarm_parameter_id == 263) {                                                                       // уставка СН4
                    $limit_trend_value = 1;
                } else if ($alarm_parameter_id == 264) {                                                                // уставка СО
                    $limit_trend_value = 17;
                } else if ($alarm_parameter_id == 23) {                                                                 // уставка запыленность
                    $limit_trend_value = 150;
                } else if ($alarm_parameter_id == 21) {                                                                 // уставка О2
                    $limit_trend_value = 20;
                } else if ($alarm_parameter_id == 31) {                                                                 // уставка СО2
                    $limit_trend_value = 1;
                }
            } else {
                throw new Exception("Неверный тип объекта");
            }

        } catch (Throwable $ex) {                                                                                       //обрабатываем исключение
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //формат возвращаемых данных json методом yii2
        Yii::$app->response->data = array_merge(['Items' => $limit_trend_value, 'specific_parameter_values' => $object_values, "limit_trend_value" => $limit_trend_value], $log->getLogAll());
    }

    /**
     * Название метода: CascadeEdgeDelete()
     * @param $mine_id
     * @param $sql_filter
     * @param $use_del_by_queue - переменная указывает на то, что использовать очередь при удалении из кэша
     * Если указать 0, то данные сразу удаляются из кэша.
     * Если указать 1, то данные удяляются из кэша с помощью очереди!
     * ПО УМОЛЧАНИЮ ДАННЫЕ УДАЛЯЮТСЯ СРАЗУ ИЗ КЭША
     * @return array
     *
     * Входные необязательные параметры
     *
     * @url
     *
     * @throws \yii\db\Exception
     * Документация на портале:
     * @package app\controllers
     * МЕТОД КАСКАДНОГО УДАЛЕНИЯ ВЫРАБОТКИ С ПАРАМЕТРА И СО ВСЕМИ ЗНАЧЕНИЯ
     *
     * Входные обязательные параметры:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 17.01.2019 13:54
     * @since ver2.0
     */
    public static function CascadeEdgeDelete($mine_id, $sql_filter)
    {
        $errors = array();

        try {
            $edges = (new Query())
                ->select('id as edge_id')
                ->from('edge')
                ->where($sql_filter)
                ->all();
            if ($edges) {
                $edges_ids = implode(',', array_column($edges, 'edge_id'));
                /**************************     УДАЛЕНИЕ СПРАВОЧНЫХ ЗНАЧЕНИЙ    *******************************************/
                $sql_handbook = "DELETE FROM edge_parameter_handbook_value where edge_parameter_id in (SELECT id from edge_parameter where edge_id in ($edges_ids))";
                $errors[] = Yii::$app->db->createCommand($sql_handbook)->execute();                                        //выполнить запрос
                /**************************     УДАЛЕНИЕ ЗНАЧЕНИЙ  ВЫРАБОТКИ  *******************************************/
                $sql_value = "DELETE FROM edge_parameter_value where edge_parameter_id in (SELECT id from edge_parameter where edge_id in ($edges_ids))";
                $errors[] = Yii::$app->db->createCommand($sql_value)->execute();
                /**************************     УДАЛЕНИЕ ПАРАМЕТРОВ ВЫРАБОТКИ    *******************************************/
                $sql_parameters = "DELETE FROM edge_parameter  where edge_id in ($edges_ids)";
                $errors[] = Yii::$app->db->createCommand($sql_parameters)->execute();
                /*************************      УДАЛЕНИЕ СЕНСОРОВ У ВЫРАБОТОК(parameter 269=-1)******************/
                $sql_sensors = "DELETE FROM edge_parameter  where edge_id in ($edges_ids)";
                /*************************      УДАЛЕНИЕ СТАТУСОВ У ВЫРАБОТОК******************/
                $sql_status = "DELETE FROM edge_status  where edge_id in ($edges_ids)";
                $errors[] = Yii::$app->db->createCommand($sql_status)->execute();
                /**************************     УДАЛЕНИЕ ВЫРАБОТОК  *******************************************/
                $sql_edges = "DELETE FROM edge where id in ($edges_ids)";
                $errors[] = Yii::$app->db->createCommand($sql_edges)->execute();

                /**
                 * Удаление из кэша
                 */
                foreach ($edges as $edge) {
                    $edge_id = $edge['edge_id'];
                    $edge_mine_id = $mine_id;
                    $flag_done = (new EdgeCacheController())->delEdgeMine($edge_mine_id, $edge_id);
                    if ($flag_done) {
                        $warnings[] = 'CascadeEdgeDelete. Выработка удалена в главном кеше';
                    } else {
                        $warnings[] = 'CascadeEdgeDelete. Выбранной выработки не было в кэше EdMi';
                    }

                    $flag_done = (new EdgeCacheController())->delEdgeScheme($edge_mine_id, $edge_id);
                    if ($flag_done) {
                        $warnings[] = 'CascadeEdgeDelete. Выработка удалена из кеша схемы шахты';
                    } else {
                        $warnings[] = 'CascadeEdgeDelete. Выбранной выработки не было в кэше EdSch';
                    }

                    $flag_done = (new EdgeCacheController())->delParameterValue($edge_id);
                    if ($flag_done) {
                        $warnings[] = 'CascadeEdgeDelete. Параметры выработки удалены из кеша схемы шахты';
                    } else {
                        $warnings[] = 'CascadeEdgeDelete. У выбранной выработки не было параметров в кэше EdPa';
                    }
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'DeleteEdge. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        return $errors;
    }

    /**
     * Название метода: actionSendMessages()
     * Метод принятия сообщений от диспетчера и записи его в бд и в кеш
     * @package frontend\controllers\positioningsystem
     *
     * Входные обязательные параметры:
     * $msq - строка json с данными для отправки сообщений
     *
     * @see
     * @example $temp_post ='{"type":"text","dest":["1090193","1092513","1092350"],"text":"wad"}';
     *          $temp_post ='{"type":"alarm","dest":["worker_id","1092513","1092350"],"text":"wad"}';
     * http://127.0.0.1/unity/send-messages?msg={%22type%22:%22text%22,%22dest%22:[%221090193%22,%221092513%22,%221092350%22],%22text%22:%22%D0%9F%D1%80%D0%B8%D0%B2%D0%B5%D1%82%20%E2%84%961%22}&senderId=70003762
     * http://127.0.0.1/unity/send-messages?msg={"type":"text","dest":["1090193","1092513","1092350"],"text":"wad"}&senderId=1090193
     * @author Якимов М.Н.
     * Created date: on 24.07.2019 17:19
     * @since ver
     */
    public static function actionSendMessages()
    {
        $result = array();
        $status = 1;
        $errors = array();
        $warnings = array();
        try {
            $warnings[] = 'actionSendMessages. Начало выполнения метода';
            $post = Assistant::GetServerMethod();                                                                           //получаем данные с post

            if (isset($post['msg']) && $post['msg'] != '' &&
                isset($post['senderId']) && $post['senderId'] != ''
            ) {
                $msg_array = json_decode($post['msg']);                                                                     //декодируем строку из json
                $sender = (int)$post['senderId'];                                                                     //декодируем строку из json
                $warnings[] = 'actionSendMessages. Распарсил данные с фронта';
                $warnings[] = $msg_array;
            } else {
                throw new Exception('actionSendMessages. Не переданы данные с фронта');
            }
            $response = StrataJobController::AddMessage($msg_array->dest, $msg_array->text, $msg_array->type, $sender);                                 //вызываем метод добавления в кеш и в бд сообщений
            if ($response['status'] == 1) {
                $result[] = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception('actionSendMessages. Не смог обработать сообщения');
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'actionSendMessages. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        $warnings[] = 'actionSendMessages. Закончил выполнение метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Название метода: actionSendMessagesSensor()
     * actionSendMessagesSensor - Метод принятия сообщений от диспетчера и записи его в бд и в кеш
     * @package frontend\controllers\positioningsystem
     *
     * Входные обязательные параметры:
     * $msq - строка json с данными для отправки сообщений
     *
     * @see
     * @example $temp_post ='{"type":"text","dest":["1090193","1092513","1092350"],"text":"wad"}';
     *          $temp_post ='{"type":"alarm","dest":["sensor_id","1092513","1092350"],"text":"wad"}';
     * http://127.0.0.1/unity/send-messages-sensor?msg={%22type%22:%22text%22,%22dest%22:[%221090193%22,%221092513%22,%221092350%22],%22text%22:%22%D0%9F%D1%80%D0%B8%D0%B2%D0%B5%D1%82%20%E2%84%961%22}&senderId=70003762
     * http://127.0.0.1/unity/send-messages-sensor?msg={"type":"text","dest":["27606","27632","27513"],"text":"wad"}&senderId=1090193
     * @author Якимов М.Н.
     * Created date: on 24.07.2019 17:19
     * @since ver
     */
    public static function actionSendMessagesSensor()
    {
        $result = array();
        $status = 1;
        $errors = array();
        $warnings = array();
        try {
            $warnings[] = 'actionSendMessagesSensor. Начало выполнения метода';
            $post = Assistant::GetServerMethod();                                                                           //получаем данные с post

            if (isset($post['msg']) && $post['msg'] != '' &&
                isset($post['senderId']) && $post['senderId'] != ''
            ) {
                $msg_array = json_decode($post['msg']);                                                                     //декодируем строку из json
                $sender = (int)$post['senderId'];                                                                     //декодируем строку из json
                $warnings[] = 'actionSendMessagesSensor. Распарсил данные с фронта';
                $warnings[] = $msg_array;
            } else {
                throw new Exception('actionSendMessagesSensor. Не переданы данные с фронта');
            }
            $response = StrataJobController::AddMessageSensor($msg_array->dest, $msg_array->text, $msg_array->type, $sender);                                 //вызываем метод добавления в кеш и в бд сообщений
            if ($response['status'] == 1) {
                $result[] = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception('actionSendMessagesSensor. Не смог обработать сообщения');
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'actionSendMessagesSensor. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        $warnings[] = 'actionSendMessagesSensor. Закончил выполнение метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод добавления параметров для сенсора. Получает массив и стороит данные для в нужном виде
     * Написал это метод, для того, чтобы не дублировать код
     * @param $sensor_find_array
     * @return mixed
     * Created by: Одилов О.У. on 29.11.2018 15:05
     */
    public function AddSensorParamValuesToArray($sensor_find_array)
    {
        $date_time_name = 'date_time_work';
        $value_name = 'value';
        if ($sensor_find_array['parameter_type_id'] == 1) {
            $date_time_name = 'handbook_date_time_work';
            $value_name = 'handbook_value';
        }
        $sensors_parameters_list['sensor_id'] = $sensor_find_array['sensor_id'];
        $sensors_parameters_list['object_id'] = $sensor_find_array['object_id'];
        $sensors_parameters_list['type_parameter_parameter_id'] = $sensor_find_array['type_parameter_parameter_id'];
        $sensors_parameters_list['value'] = $sensor_find_array[$value_name];
        $sensors_parameters_list['date_time_work'] = $sensor_find_array[$date_time_name];
        return $sensors_parameters_list;
    }

    public function AddSensorParamValuesToArray2($sensor_find_array, $object_id)
    {

        $sensors_parameters_list['sensor_id'] = $sensor_find_array['sensor_id'];
        $sensors_parameters_list['object_id'] = $object_id;
        $sensors_parameters_list['type_parameter_parameter_id'] = $sensor_find_array['parameter_type_id'] . '-' . $sensor_find_array['parameter_id'];
        $sensors_parameters_list['value'] = $sensor_find_array['value'];
        $sensors_parameters_list['date_time_work'] = $sensor_find_array['date_time'];
        return $sensors_parameters_list;
    }

    /**
     * Метод добавления параметров для оборудования. Получает массив и стороит данные для в нужном виде
     * Написал это метод, для того, чтобы не дублировать код
     * @param $equipment_find_array
     * @param $object_id
     * @return mixed
     * Created by: Файзуллоев А.Э. Date 26.12.2019
     */
    public function AddEquipmentParamValuesToArray($equipment_find_array, $object_id)
    {

        $equipment_parameters_list['equipment_id'] = $equipment_find_array['equipment_id'];
        $equipment_parameters_list['object_id'] = $object_id;
        $equipment_parameters_list['type_parameter_parameter_id'] = $equipment_find_array['parameter_type_id'] . '-' . $equipment_find_array['parameter_id'];
        $equipment_parameters_list['value'] = $equipment_find_array['value'];
        $equipment_parameters_list['date_time_work'] = $equipment_find_array['date_time'];
        return $equipment_parameters_list;
    }

    /**
     * actionEditSensorInfo - Метод добавления, перемещения сенсора в 3D схеме. в БД и в КЕШ
     * Название метода: actionEditSensorInfo()
     * Входные обязательные поля параметры:
     *  $post["sensor_id"] - идентификатор сенсора
     *  $post["edge_id"] - идентификатор Edge (выработки).
     *  $post["XYZ"] - позиция
     *  $post["mine_id"] - идентификатор шахты
     * Метод разделяется на 2 части:
     *  1. Добавление узлов связи, то есть в одной части добавлятся такие типовые объекты как:
     *     "Передача информации", "Датчик", "Обработка информации"
     *  2. Добавление только измеряемых значений.
     * При добавлении значений параметров сенсора, параметра или самого сенсора в кэш используется очередь,
     * то есть добавление идет путем очереди.
     * @url http://localhost/unity/edit-sensor-info
     * @url http://localhost/unity/edit-sensor-info?sensor_id=15013&XYZ=16054.83%2C-771.8235%2C-12787.17&edge_id=25923&mine_id=290
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 09.06.2019
     * @since 2.0
     */
    public function actionEditSensorInfo()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();
        $errors = array();
        $flag_done = 0;
        $debug_flag = 0;
        try {
            $response = null;
            $session = Yii::$app->session;
            $session->open();
            /**
             * Проверка наличия сессии пользователя
             */
            if (isset($session['sessionLogin'])) {
                $warnings[] = 'actionEditSensorInfo. Сессия в порядке';//если в сессии есть логин
            } else {
                $errors[] = 'Время сессии закончилось. Требуется повторный ввод пароля';
                $this->redirect('/');
                throw new Exception('actionEditSensorInfo. Сессия закончилась');
            }

            /**
             * Наличие прав пользователя
             */
            if (AccessCheck::checkAccess($session['sessionLogin'], 79)) {
                $warnings[] = 'actionEditSensorInfo. Права на выполнение операции в порядке';
            } else {
                throw new Exception('actionEditSensorInfo. Недостаточно прав для совершения данной операции');
            }

            $post = Assistant::GetServerMethod();
            if (isset($post['sensor_id']) && $post['sensor_id'] != '' &&
                isset($post['edge_id']) && $post['edge_id'] != '' &&
                isset($post['XYZ']) && $post['XYZ'] != '' &&
                isset($post['mine_id']) && $post['mine_id'] != '') {

                $warnings[] = 'actionEditSensorInfo. Прошел проверку на входные параметры';
                $sensor_id = $post['sensor_id'];
                $edge_id = $post['edge_id'];
                $mine_id = $post['mine_id'];
                $XYZ = $post['XYZ'];
                $warnings[] = 'actionEditSensorInfo. переданы параметры: sensor_id = ' . $post['sensor_id'] .
                    'edge_id = ' . $post['edge_id'] . 'coordinates = ' . $post['XYZ'] . 'mine_id = ' . $post['mine_id'];
            } else {
                throw new Exception('actionEditSensorInfo. Не передан один из параметров: sensor_id = ' . $post['sensor_id'] .
                    'edge_id = ' . $post['edge_id'] . 'coordinates = ' . $post['XYZ']);
            }

            /**
             * Инициализация сенсора в кеше
             */
            $sensor_main_cache = (new SensorCacheController())->getSensorMineBySensorHash($sensor_id);
            if ($sensor_main_cache === false) {
                $warnings[] = 'actionEditSensorInfo. Кеш оборудования сусто. Начинаю инициализацию';
                /**
                 * проверяем есть или нет у оборудования параметр шахты и если нет то создаем
                 */
                $response = SensorMainController::GetOrSetSensorParameter($sensor_id, 346, 1);
                if ($response['status'] == 1) {
                    $sensor_parameter_id = $response['sensor_parameter_id'];
                    $warnings[] = $response['warnings'];
                    $warnings[] = "actionEditSensorInfo. Параметр шахты сенсора $sensor_id инициализировал $sensor_parameter_id";
                    $status *= $response['status'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception("actionEditSensorInfo. Ошибка инициализации параметра шахты 346/1 сенсора $sensor_id");
                }
                /**
                 * инициализирую кеш сенсора
                 */
                $response = SensorMainController::initSensorInCache($sensor_id);
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $warnings[] = 'actionEditSensorInfo. Кеш сенсора инициализировал';
                    $status *= $response['status'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception("actionEditSensorInfo. Ошибка инициализации кеша сенсора $sensor_id");
                }
            } else {
                $warnings[] = 'actionEditSensorInfo. Кеш сенсора БЫЛ инициализирован ранее';
            }
            /**
             * Блок поиска шахты по переданному edge_id
             */
            $edges = Edge::findOne(['id' => $edge_id]);                                                           //находим по ветви/ребру/выработке id места в котором будет стоять объект - сенсор
            if ($edges) {
                $warnings[] = 'actionEditSensorInfo. Место найдено в БД в edge';
                $place_id = $edges->place_id;
            } else {
                throw new Exception('actionEditSensorInfo. Не корректный кеш схемы! За переданным edge_id не существует в БД реального edge_id: ' . $edge_id);
            }
            unset($edges);

            /**
             * Блок поиска шахты по найденному place_id
             */
            $places = Place::findOne(['id' => $place_id]);
            if ($places) {
                $warnings[] = 'actionEditSensorInfo. Шахта найдена в БД в place';
                $mine_id = $places->mine_id;
            } else {
                throw new Exception('actionEditSensorInfo. За найденным place_id не существует в БД реального place_id');
            }
            unset($places);

            /**
             * Блок поиска сенсора в БД
             */
            $sensor = Sensor::findOne(['id' => $sensor_id]);
            if ($sensor) {
                $warnings[] = "actionEditSensorInfo. Сенсор $sensor_id найден в БД";
                $object_id = $sensor->object_id;
                $sensor_title = $sensor->title;
            } else {
                throw new Exception("actionEditSensorInfo. Сенсор $sensor_id в БД не найден");
            }
            unset($sensor);

            /**
             * Блок определения типового объекта устанавливаемого сенсора
             */
            $typical_object = TypicalObject::find()
                ->where(['id' => $object_id])
                ->with('objectType')
                ->limit(1)
                ->one();
            if (!$typical_object) {
                throw new Exception("actionEditSensorInfo. Типовой объект $object_id не найден в БД в таблице object");
            }
            $object_title = $typical_object->title;
            $object_type_id = $typical_object->object_type_id;
            $object_type_title = $typical_object->objectType->title;
            $object_kind_id = $typical_object->objectType->kind_object_id;
            $warnings[] = 'actionEditSensorInfo. Подготовил набор базовых параметров для обратного построения справочника';
            $warnings[] = "actionEditSensorInfo. ИД Типового объекта: $object_id";
            $warnings[] = "actionEditSensorInfo. Название типового объекта: $object_type_title";
            $warnings[] = "actionEditSensorInfo. Тип типового объекта: $object_type_id";
            $warnings[] = "actionEditSensorInfo. Вид типового объекта: $object_kind_id";
            if ($object_type_id == 22 || $object_type_id == 116 || $object_type_id == 95 || $object_type_id == 96 || $object_type_id == 28) {
                $parameter_type_id = 1;
            } else {
                $parameter_type_id = 2;
            }
            /**
             * инициализируем дату
             */
            $date_now = \backend\controllers\Assistant::GetDateNow();

            /**
             * Записываем местораcположение сенсора в БД
             */
            $response = SensorMainController::GetOrSetSensorParameter($sensor_id, 122, $parameter_type_id);
            if ($response['status'] == 1) {
                $sensor_parameter_id = $response['sensor_parameter_id'];
                $warnings[] = $response['warnings'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("actionEditSensorInfo. Ошибка получения или сохранение параметра 122 сенсора $sensor_id");
            }
            if ($parameter_type_id == 1) {
                $response = SensorBasicController::addSensorParameterHandbookValue($sensor_parameter_id, $place_id, 1, $date_now);
            } elseif ($parameter_type_id == 2) {
                $response = SensorBasicController::addSensorParameterValue($sensor_parameter_id, $place_id, 1, $date_now);
            }
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("actionEditSensorInfo. Ошибка сохранения Значения: $place_id параметра 122 сенсора: $sensor_id");
            }
            // создаем массив для вставки разовой в кеш
            $sensor_parameter_value_to_caches[] = SensorCacheController::buildStructureSensorParametersValue(
                $sensor_id, $sensor_parameter_id, 122, $parameter_type_id,
                $date_now, $place_id, 1);

            /**
             * Записываем ветвь сенсора в БД
             */
            $response = SensorMainController::GetOrSetSensorParameter($sensor_id, 269, $parameter_type_id);
            if ($response['status'] == 1) {
                $sensor_parameter_id = $response['sensor_parameter_id'];
                $warnings[] = $response['warnings'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("actionEditSensorInfo. Ошибка получения или сохранение параметра 269 сенсора $sensor_id");
            }
            if ($parameter_type_id == 1) {
                $response = SensorBasicController::addSensorParameterHandbookValue($sensor_parameter_id, $edge_id, 1, $date_now);
            } elseif ($parameter_type_id == 2) {
                $response = SensorBasicController::addSensorParameterValue($sensor_parameter_id, $edge_id, 1, $date_now);
            }
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("actionEditSensorInfo. Ошибка сохранения Значения: $edge_id параметра 269 сенсора: $sensor_id");
            }
            // создаем массив для вставки разовой в кеш
            $sensor_parameter_value_to_caches[] = SensorCacheController::buildStructureSensorParametersValue(
                $sensor_id, $sensor_parameter_id, 269, $parameter_type_id,
                $date_now, $edge_id, 1);

            /**
             * Записываем координату сенсора в БД
             */
            $response = SensorMainController::GetOrSetSensorParameter($sensor_id, 83, $parameter_type_id);
            if ($response['status'] == 1) {
                $sensor_parameter_id = $response['sensor_parameter_id'];
                $warnings[] = $response['warnings'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("actionEditSensorInfo. Ошибка получения или сохранение параметра 83 сенсора $sensor_id");
            }
            if ($parameter_type_id == 1) {
                $response = SensorBasicController::addSensorParameterHandbookValue($sensor_parameter_id, $XYZ, 1, $date_now);
            } elseif ($parameter_type_id == 2) {
                $response = SensorBasicController::addSensorParameterValue($sensor_parameter_id, $XYZ, 1, $date_now);
            }
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("actionEditSensorInfo. Ошибка сохранения Значения: $XYZ параметра 83 сенсора: $sensor_id");
            }
            // создаем массив для вставки разовой в кеш
            $sensor_parameter_value_to_caches[] = SensorCacheController::buildStructureSensorParametersValue(
                $sensor_id, $sensor_parameter_id, 83, $parameter_type_id,
                $date_now, $XYZ, 1);

            /**
             * блок переноса сенсора в новую шахту если таковое требуется
             * если шахта есть, то делаем перенос или инициализацию, в зависимости от описанного выше
             */

            $sensor_to_cache = SensorCacheController::buildStructureSensor($sensor_id, $sensor_title, $object_id, $object_title, $object_type_id, $mine_id);
            $response = SensorMainController::AddMoveSensorMineInitDB($sensor_to_cache);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $warnings[] = 'actionEditSensorInfo. обновил главный кеш сенсора';
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('actionEditSensorInfo. Не смог обновить главный кеш сенсора' . $sensor_id);
            }
            /**
             * Записываем шахту в БД
             */
            $response = SensorMainController::GetOrSetSensorParameter($sensor_id, 346, $parameter_type_id);
            if ($response['status'] == 1) {
                $sensor_parameter_id = $response['sensor_parameter_id'];
                $warnings[] = $response['warnings'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("actionEditSensorInfo. Ошибка получения или сохранение параметра 346 сенсора $sensor_id");
            }
            if ($parameter_type_id == 1) {
                $response = SensorBasicController::addSensorParameterHandbookValue($sensor_parameter_id, $mine_id, 1, $date_now);
            } elseif ($parameter_type_id == 2) {
                $response = SensorBasicController::addSensorParameterValue($sensor_parameter_id, $mine_id, 1, $date_now);
            }
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("actionEditSensorInfo. Ошибка сохранения Значения: $mine_id параметра 346 сенсора: $sensor_id");
            }
            // создаем массив для вставки разовой в кеш
            $sensor_parameter_value_to_caches[] = SensorCacheController::buildStructureSensorParametersValue(
                $sensor_id, $sensor_parameter_id, 346, $parameter_type_id,
                $date_now, $mine_id, 1);

            /**
             * обновление параметров сенсора в кеше
             */
            $response = (new SensorCacheController)->multiSetSensorParameterValueHash($sensor_parameter_value_to_caches);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $warnings[] = 'actionEditSensorInfo. обновил параметры сенсора в кеше';
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('actionEditSensorInfo. Не смог обновить параметры в кеше сенсора' . $sensor_id);
            }
            unset($sensor_parameter_value_to_caches);

            if ($object_type_id == 22) {
                $response = (new CoordinateController())->updateSensorGraph($sensor_id, $mine_id, $sensor_title);  //метод обновления графа для сенсора
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $warnings[] = "actionSaveSpecificParametersValuesBase. обновил граф сенсора в кеше";
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception("actionSaveSpecificParametersValuesBase.ошибка обновления графа сенсора в кеше" . $sensor_id);
                }
            }

        } catch (Throwable $e) {
            $status = 0;
            $sensor_mine_id = null;
            $errors[] = 'getSensorUniversalLastMine.Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
            $data_to_log = array('response' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
            LogCacheController::setEdgeLogValue('actionEditSensorInfo', $data_to_log, 1);
        }
        $warnings[] = 'actionEditSensorInfo. Вышел из метода';
        $result_main = array('response' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return json_encode($result_main);
    }


//сохранение значения конкретного параметра сенсора
    private function actionAddSensorParameterValue($sensor_parameter_id, $value, $status_id)
    {
        $error[] = array();
        if ($sensor_parameter_id != '' && $value != '' && $status_id != '') {
            $sensor_parameter_value = new SensorParameterValue();
            $sensor_parameter_value->sensor_parameter_id = $sensor_parameter_id;
            $sensor_parameter_value->date_time = date('Y-m-d H:i:s');
            $sensor_parameter_value->value = (string)$value;
            $sensor_parameter_value->status_id = $status_id;

            if (!$sensor_parameter_value->save()) {
                $error[] = 'Не удалось сохранить параметр';
                echo "Не удалось сохранить параметр \n";
                return (-1);
            } else return 1;
        } else {
            $errors[] = 'Некоторые параметры имеют пустое значение';
        }
    }

    /**
     * Сохранение параметров сенсора в БД в таблицу справочник
     * @param $sensor_parameter_id
     * @param $value
     * @param $status_id
     * @return int
     */
    private function actionAddSensorParameterHandbookValue($sensor_parameter_id, $value, $status_id)
    {
        $error[] = array();
        if ($sensor_parameter_id != '' && $value != '' && $status_id != '') {
            $sensor_parameter_handbook_value = new SensorParameterHandbookValue();
            $sensor_parameter_handbook_value->sensor_parameter_id = $sensor_parameter_id;
            $sensor_parameter_handbook_value->date_time = date('Y-m-d H:i:s');
            $sensor_parameter_handbook_value->value = (string)$value;
            $sensor_parameter_handbook_value->status_id = $status_id;

            if (!$sensor_parameter_handbook_value->save()) {
                $error[] = 'Не удалось сохранить параметр';
                echo "Не удалось сохранить параметр \n";
                return (-1);
            } else return 1;
        } else {
            $errors[] = 'Некоторые параметры имеют пустое значение';
        }
    }


    /**
     * actionDeleteSensorFromMine - Метод удаления датчика из шахты (привязки датчик-шахта)
     * Название метода: actionDeleteSensorFromMine()
     * @package app\controllers\
     * Входные обязательные параметры:
     * $post['sensor_id'] - идентификатор сенсора
     *
     * @url http://localhost/unity/delete-sensor-from-mine
     * @url http://localhost/unity/delete-sensor-from-mine?sensor_id=15102
     *
     * Документация на портале: http://192.168.1.4/products/community/modules/forum/posts.aspx?&t=187&p=1#205
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 16.01.2019 9:40
     * @since ver0.2
     */
    public function actionDeleteSensorFromMine()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();

        $response = false;

        try {
            $warnings[] = 'actionDeleteSensorFromMine. Начало выполнения метода';
            $session = Yii::$app->session;                                                                                  //старт сессии
            //$session->open();                                                                                               //открыть сессию
            if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
                $warnings[] = 'actionDeleteSensorFromMine. Сессия есть';
            } else {
                throw new Exception('actionDeleteSensorFromMine. Время сессии закончилось. Требуется повторный ввод пароля');
            }
            if (AccessCheck::checkAccess($session['sessionLogin'], 80)) {                                        //если пользователю разрешен доступ к функции
                $warnings[] = 'actionDeleteSensorFromMine. Прав достаточно';
            } else {
                throw new Exception('actionDeleteSensorFromMine. Недостаточно прав для совершения данной операции');
            }

            $post = Assistant::GetServerMethod();                                         //получаем данные методом POST
            if (!isset ($post['sensor_id'])) {                                           //если данные не получены
                $errors[] = 'actionDeleteSensorFromMine. Отсутствуют входные параметры';                            //записать сообщение об ошибке
            } else {                                                                      //если данные получены
                $sensorId = json_decode($post['sensor_id']);                            //декодируем данные
                $object_type_id = (new Query())
                    ->select([
                        'object_type_id'
                    ])
                    ->from(['`view_sensor_object_main`'])
                    ->where('sensor_id=' . $sensorId)
                    ->one();

                $parameter_type_id = SensorCacheController::isStaticSensor($object_type_id['object_type_id']);

                $sensorParameter = SensorParameter::findOne                                                     //находим параметр требуемого датчика, соответствующий местоположению (местоположение)
                (
                    ['sensor_id' => $sensorId,
                        'parameter_id' => 122,
                        'parameter_type_id' => $parameter_type_id]
                );
                if ($sensorParameter) {
                    $flag = SpecificSensorController::AddSensorParameterHandbookValue('sensor', $sensorParameter->id, 1, '-1', 1);
                    if ($flag == -1) {
                        throw new Exception('actionDeleteSensorFromMine. Произошла ошибка при удалении местоположения (place)');
                    }
                }

                $sensorParameter = SensorParameter::findOne                                                     //находим параметр требуемого датчика, соответствующий местоположению (местоположение)
                (
                    ['sensor_id' => $sensorId,
                        'parameter_id' => 83,
                        'parameter_type_id' => $parameter_type_id]
                );
                if ($sensorParameter) {
                    $flag = SpecificSensorController::AddSensorParameterHandbookValue('sensor', $sensorParameter->id, 1, '-1', 1);
                    if ($flag == -1) {
                        throw new Exception('actionDeleteSensorFromMine. Произошла ошибка при удалении координат');
                    }
                }

                $sensorParameter = SensorParameter::findOne                                                     //находим параметр требуемого датчика, соответствующий местоположению (местоположение)
                (
                    ['sensor_id' => $sensorId,
                        'parameter_id' => 269,
                        'parameter_type_id' => $parameter_type_id]
                );
                if ($sensorParameter) {
                    $flag = SpecificSensorController::AddSensorParameterHandbookValue('sensor', $sensorParameter->id, 1, '-1', 1);
                    if ($flag == -1) {
                        throw new Exception('actionDeleteSensorFromMine. Произошла ошибка при удалении ветви / ребра');
                    }
                }

                $sensorParameter = SensorParameter::findOne                                                     //находим параметр требуемого датчика, соответствующий местоположению (местоположение)
                (
                    ['sensor_id' => $sensorId,
                        'parameter_id' => 346,
                        'parameter_type_id' => $parameter_type_id]
                );
                if ($sensorParameter) {
                    $flag = SpecificSensorController::AddSensorParameterHandbookValue('sensor', $sensorParameter->id, 1, '-1', 1);
                    if ($flag == -1) {
                        throw new Exception('actionDeleteSensorFromMine. Произошла ошибка при удалении наименование шахтного поля (Mine)');
                    }
                }

                $sensor_cache_controller = new SensorCacheController();
                $sensor_cache_controller->delInSensorMineHash($sensorId, AMICUM_DEFAULT_MINE);
                $sensor_cache_controller->delParameterValueHash($sensorId);
                (new CoordinateController())->delGraph($sensorId);                                                      //метод удаления графа для сенсора
                $response = true;
            }

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionDeleteSensorFromMine. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'actionDeleteSensorFromMine. Закончил выполнение метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings,
            'response' => $response);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Получение информации о сопряжении по id
     *
     */
    public function actionGetConjunctionInfo()
    {
        $post = Yii::$app->request->post();
        $errors = array();
        $result = array();
        $conjunction = array();
        //$post['conj_id'] = 3135;
        if (isset($post['conj_id']) and $post['conj_id'] != '') {
            $conjunction = (new Query())
                ->select([
                    'conjunction.id',
                    'conjunction.title as title',
                    'conjunction.x',
                    'conjunction.z',
                    'conjunction.y',
                    'concat(edge1.place_id, ",", edge2.place_id) place_ids'
                ])
                ->from('conjunction')
//                ->leftJoin()
                ->leftJoin('edge edge1', 'edge1.conjunction_start_id = conjunction.id')
                ->leftJoin('edge edge2', 'edge2.conjunction_end_id = conjunction.id')
                ->where(['conjunction.id' => $post['conj_id']])
                ->one();
        } else {
            $errors[] = 'Не задан id сопряжения';
        }
        $result = array('errors' => $errors, 'conjunction' => $conjunction);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: actionDeleteEdgeFromMine() - Метод каскадного удаления выработки из кэша и из БД.
     * Метод каскадного удаления выработки из кэша и из БД.
     * Данные сначала удаляются из БД, потом по умолчанию с помощью очереди удаляяются из кэша.
     * @throws \yii\db\Exception
     * Документация на портале: http://192.168.1.4/products/community/modules/forum/posts.aspx?&t=181&p=1#199
     * @package app\controllers
     * Входные обязательные параметры:
     * $post['edgeId'] - идентификатор выработки
     * $post['mine_id'] - идентификатор шахты
     * @url http://localhost/unity/delete-edge-from-mine?
     * @url http://localhost/unity/delete-edge-from-mine?edgeId=25425&mine_id=290
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 15.01.2019 14:24
     * @since ver1.1
     * @
     */
    public static function actionDeleteEdgeFromMine()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $items = array();                                                                                               // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $warnings[] = 'actionDeleteEdgeFromMine. Начал выполнять метод';
            $session = Yii::$app->session;
            $session->open();
            $result = array();
            if (!isset($session['sessionLogin'])) {                                                                     // если в сессии есть логин
                $errors[] = 'actionDeleteEdgeFromMine. Время сессии закончилось. Требуется повторный ввод пароля';
                throw new Exception('actionDeleteEdgeFromMine. Время сессии закончилось. Требуется повторный ввод пароля');
            }
            if (AccessCheck::checkAccess($session['sessionLogin'], 77)) {                                       // если пользователю разрешен доступ к функции
                $warnings[] = 'actionDeleteEdgeFromMine. Прав для выполнения достаточно';
            } else {
                throw new Exception('actionDeleteEdgeFromMine. Недостаточно прав для совершения данной операции');
            }

            $post = Assistant::GetServerMethod();                                                                       // получение данных от ajax-запроса
            if (isset($post['edgeId']) && $post['edgeId'] != '' and isset($post['mine_id']) and $post['mine_id'] != '') {//если передан id ветви
                $edge_id = $post['edgeId'];
                $mine_id = $post['mine_id'];
                $warnings[] = "actionDeleteEdgeFromMine. Получил входные параметры $edge_id и $mine_id";
            } else {
                throw new Exception('actionDeleteEdgeFromMine. Параметры не переданы');
            }

            /**
             * Блок удаления выработки
             */
            $response = EdgeMainController::DeleteEdge($edge_id, $mine_id);
            if ($response['status'] == 1) {
                $result = $response['Items'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $warnings[] = 'actionEditSensorInfo. Удалил выработку';
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("actionEditSensorInfo. Не смог удалить выработку $edge_id");
            }
            $edges[] = $edge_id;
            $items['add'] = array();
            $items['delete'] = $edges;
            $items['change'] = array();
            $items['test'] = 'Raw';
        } catch (Throwable $exception) {
            $errors[] = 'actionDeleteEdgeFromMine. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
            $data_to_log = array('Items' => $items, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
            LogCacheController::setEdgeLogValue('actionDeleteEdgeFromMine', $data_to_log, 1);
        }
        $warnings[] = 'actionDeleteEdgeFromMine. Закончил выполнять метод';
        $result_main = array('Items' => $items, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * actionEditEdge - Метод редактирования выработки
     * Название метода: actionEditEdge()
     * @package app\controllers\UnityController
     * если передать новый place id, то эдж будет привязан к нему
     * если place id будет старый, то изменений не будет
     * при создании нового place типовые параметры не копируется
     *
     * При добавлении значений в кэш используется очередь. Чтобы выключить эту функцию, необходмо в
     * переменную $use_add_by_queue = 0. По умолчанию данные в кэш добавляются с помощью очереди
     *
     * Входные обязательные параметры:
     * $post['place_id'] - идентификатор местоположения
     * $post['edge_id'] - идентификатор выработки
     * $post['parameter_values_array'] - массив параметров
     * $post['mine_id'] - идентификтор шахты
     *
     * @url http://localhost/unity/edit-edge
     * @url http://localhost/unity/edit-edge,title=&place_id=6223-10&edge_id=26079&mine_id=290&edge_type_id=29&place_type_id=88&plast_id=2106&parameter_values_array%5B0%5D%5Bparameter_specific_status%5D=1&parameter_values_array%5B0%5D%5BparameterValue%5D=574&parameter_values_array%5B0%5D%5BparameterId%5D=128&parameter_values_array%5B1%5D%5Bparameter_specific_status%5D=1&parameter_values_array%5B1%5D%5BparameterValue%5D=298.77884990021&parameter_values_array%5B1%5D%5BparameterId%5D=151&parameter_values_array%5B2%5D%5Bparameter_specific_status%5D=1&parameter_values_array%5B2%5D%5BparameterValue%5D=47&parameter_values_array%5B2%5D%5BparameterId%5D=129&parameter_values_array%5B3%5D%5Bparameter_specific_status%5D=1&parameter_values_array%5B3%5D%5BparameterValue%5D=85&parameter_values_array%5B3%5D%5BparameterId%5D=130&parameter_values_array%5B4%5D%5Bparameter_specific_status%5D=1&parameter_values_array%5B4%5D%5BparameterValue%5D=1&parameter_values_array%5B4%5D%5BparameterId%5D=131&parameter_values_array%5B5%5D%5Bparameter_specific_status%5D=1&parameter_values_array%5B5%5D%5BparameterValue%5D=1&parameter_values_array%5B5%5D%5BparameterId%5D=442&parameter_values_array%5B6%5D%5Bparameter_specific_status%5D=1&parameter_values_array%5B6%5D%5BparameterValue%5D=BrownEdgeMaterial&parameter_values_array%5B6%5D%5BparameterId%5D=132
     *
     * Документация на портале: http://192.168.1.4/products/community/modules/forum/posts.aspx?&t=185&p=1#203
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 15.01.2019 16:58
     * @since ver0.2
     */
    public function actionEditEdge()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $items = array();
        $response_action = array();                                                                                     // пустой массив для хранения обратного ответа на сторону сервера
        try {
            $warnings[] = 'actionEditEdge. Начал выполнять метод';

            $session = Yii::$app->session;                                                                              // старт сессии
            $session->open();                                                                                           // открыть сессию
            if (!isset($session['sessionLogin'])) {                                                                     // если в сессии есть логин
                $errors[] = 'actionEditEdge. Время сессии закончилось. Требуется повторный ввод пароля';
                throw new Exception('actionEditEdge. Время сессии закончилось. Требуется повторный ввод пароля');
            }
            if (AccessCheck::checkAccess($session['sessionLogin'], 76)) {                                        //если пользователю разрешен доступ к функции
                $warnings[] = 'actionEditEdge. Прав для выполнения достаточно';
            } else {
                throw new Exception('actionEditEdge. Недостаточно прав для совершения данной операции');
            }                                      //если пользователю разрешен доступ к функции

            $post = Assistant::GetServerMethod();                                                                       // получение данных от ajax-запроса
            if (isset($post['place_id']) && $post['place_id'] != '' and
                isset($post['edge_id']) && $post['edge_id'] != '' and
                isset($post['parameter_values_array']) && $post['parameter_values_array'] != '' and
                isset($post['mine_id']) && $post['mine_id'] != '') {
                $edge_id = (int)$post['edge_id'];                                                                       // сохраним в переменную полученный place_id
                $place_id = (int)$post['place_id'];                                                                     // сохраним в переменную полученный edge_id
                $mine_id = (int)$post['mine_id'];
                $parameters_array = $post['parameter_values_array'];
                $warnings[] = 'actionEditEdge. Входные параметры получены';
            } else {
                throw new Exception('actionEditEdge. Параметры входные не переданы');
            }

            $edge = Edge::findOne(['id' => $edge_id]);
            if ($edge) {
                $warnings[] = "actionEditEdge. Выработка в БД найдена $edge_id";
            } else {
                throw new Exception("actionEditEdge. нет такого edge в базе данных, ошибочный эдж со стороны фронт энд $edge_id");
            }

            if (isset($post['title']) && $post['title'] != '') $new_place_title = $post['title'];                       // получим название нового места
            else $new_place_title = false;


            if (isset($post['place_type_id']) && $post['place_type_id']) $place_object_id = $post['place_type_id'];     // получим id типового объекта выработки, если задан, то будем обновлять данные
            else $place_object_id = false;

            if (isset($post['plast_id']) && $post['plast_id']) $place_plast_id = $post['plast_id'];                     // получим id пласта, если задан, то будем обновлять данные
            else $place_plast_id = false;


            if (isset($post['edge_type_id']) && $post['edge_type_id']) $edge_type_id = $post['edge_type_id'];           // получим id типа ветви, если задан, то будем обновлять данные
            else $edge_type_id = false;


            if (isset($post['conjunction_1']) && $post['conjunction_1'] != '')
                $conjunction_start = explode(',', $post['conjunction_1']);                                      // получим данные о стартовой позиции выработки
            else
                $conjunction_start = false;

            if (isset($post['conjunction_2']) && $post['conjunction_2'] != '')
                $conjunction_end = explode(',', $post['conjunction_2']);                                        // получим данные о конечной позиции выработки
            else
                $conjunction_end = false;


            if ($place_id == -1 && $place_object_id != false && $new_place_title != false) {                            // если на стороне фронт задали только текст нового места, при этом id place равен -1, то мы создаем место
                // Создаем id в главной таблице main
                $main_id = new Main();
                $main_id->table_address = 'place';                                                                      // адрес таблицы в которой искать главный id
                $main_id->db_address = 'amicum2';                                                                       // имя базы данных в которой лежит таблица
                if ($main_id->save()) {
                } else {
                    $errors[] = $main_id->errors;
                    throw new Exception('actionEditEdge. ошибка создания главного id для place. Ошибка сохранения модели Main');
                }
                $place = new Place();                                                                                   // создаем новый объект место
                $place->id = $main_id->id;
                $place->title = $new_place_title;                                                                       // записываем новое место
                $place->object_id = $place_object_id;                                                                   // записываем обджект id нового place
                if ($place_plast_id != false) $place->plast_id = $place_plast_id;                                       // если задан записываем пласт
                $place->mine_id = $mine_id;                                                                             // если задан записываем шахту
                if (!$place->save()) {
                    $errors[] = $place->errors;
                    throw new Exception('actionEditEdge. ошибка создания нового места. Ошибка сохранения модели Place');
                }
                HandbookCachedController::clearPlaceCache();
                $edge->place_id = $place->id;
                $place_id = $place->id;

            } elseif ($edge->place_id != $place_id) {                                                                   // если на стороне фронта изменили id горной выработки, при выборе места из выпадашки, то мы его просто перезаписываем на то что пришло
                $place = Place::findOne(['id' => $place_id]);                                                           // Находим местонахождение по ID
                if ($place)                                                                                             // если заданный place id есть в базе, то мы его спокойно пишем в эдж
                {
                    $edge->place_id = $place_id;
                } else {
                    throw new Exception('actionEditEdge. Заданный place id не существует в базе данных');
                }
            } else {
                if ($place_plast_id != true and $place_object_id != true) {
                    throw new Exception('actionEditEdge. Тип или пласт места не передан');
                }
                $place = Place::findOne(['id' => $place_id]);                                                           // Находим местонахождение по ID
                if ($place->object_id != $place_object_id or $place->plast_id != $place_plast_id) {                     // если объект у местоположения менялся, то отредактируем
                    $place->object_id = $place_object_id;
                    $place->plast_id = $place_plast_id;
                    if (!$place->save()) {
                        $errors[] = $place->errors;
                        throw new Exception('actionEditEdge. Ошибка редактирования тип места или пласт местоположения. Ошибка сохранения модели Place');
                    }
                    HandbookCachedController::clearPlaceCache();
                }
            }
            if ($edge_type_id != false) $edge->edge_type_id = $edge_type_id;                                            // сохраняем тип эжа в базу данных

            if ($conjunction_start != false) {                                                                          // Если стартовая позиция задана изменяем данные x, y, z и сохраняем данные выработки
                $conjunction = Conjunction::findOne(['id' => $edge->conjunction_start_id]);
                $conjunction->x = $conjunction_start[0];
                $conjunction->y = $conjunction_start[2];
                $conjunction->z = $conjunction_start[1];
                if (!$conjunction->save()) {
                    $errors[] = $conjunction->errors;
                    throw new Exception("actionEditEdge. Ошибка сохранения начала поворота выработки $conjunction->id. Ошибка сохранения модели Conjunction");
                }
            }

            if ($conjunction_end != false) {                                                                            // Если конечная позиция задана изменяем данные x, y, z и сохраняем данные выработки
                $conjunction = Conjunction::findOne(['id' => $edge->conjunction_end_id]);
                $conjunction->x = $conjunction_end[0];
                $conjunction->y = $conjunction_end[2];
                $conjunction->z = $conjunction_end[1];
                if (!$conjunction->save()) {
                    $errors[] = $conjunction->errors;
                    throw new Exception("actionEditEdge. Ошибка сохранения начала поворота выработки $conjunction->id. Ошибка сохранения модели Conjunction");
                }
            }

            if (!$edge->save()) {
                $errors[] = $edge->errors;
                throw new Exception("actionEditEdge. Ошибка сохранения edge $edge->id. Ошибка сохранения модели Edge");
            }

            if ($new_place_title != false) {
                $response = EdgeBasicController::addEdgeParameterWithHandbookValue($edge->id, 162, 1, $new_place_title, 1, 1);
                if ($response['status'] == 0) {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new Exception('actionEditEdge. ошибка сохранения параметра edge 162');
                }
                $edge_param_to_cache[] = $response['edge_param_to_cache'];
            }
            if ($place_object_id != false) {
                $response = EdgeBasicController::addEdgeParameterWithHandbookValue($edge->id, 274, 1, $place_object_id, 1, 1);
                if ($response['status'] == 0) {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new Exception('actionEditEdge. ошибка сохранения параметра edge 274');
                }
                $edge_param_to_cache[] = $response['edge_param_to_cache'];
            }
            if ($place_plast_id != false) {
                $response = EdgeBasicController::addEdgeParameterWithHandbookValue($edge->id, 347, 1, $place_plast_id, 1, 1);
                if ($response['status'] == 0) {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new Exception('actionEditEdge. ошибка сохранения параметра edge 347');
                }
                $edge_param_to_cache[] = $response['edge_param_to_cache'];
            }
            if ($edge_type_id != false) {
                $response = EdgeBasicController::addEdgeParameterWithHandbookValue($edge->id, 443, 1, $edge_type_id, 1, 1);
                if ($response['status'] == 0) {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new Exception('actionEditEdge. ошибка сохранения параметра edge 443');
                }
                $edge_param_to_cache[] = $response['edge_param_to_cache'];
            }
            $response = EdgeBasicController::addEdgeParameterWithHandbookValue($edge->id, 346, 1, $mine_id, 1, 1);
            if ($response['status'] == 0) {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception('actionEditEdge. ошибка сохранения параметра edge 346');
            }
            $edge_param_to_cache[] = $response['edge_param_to_cache'];

            foreach ($parameters_array as $parameter_values)                                                            // В цикле перебираем параметры
            {
                $edge_id_in_edge_parameter = EdgeParameter::findOne(['edge_id' => $edge_id, 'parameter_id' => $parameter_values['parameterId']]);  // Находим параметры ветви в модели EdgeParameter по id - параметра и по id- ветви
                if (!$edge_id_in_edge_parameter)                                                                        // если ветви не существует, то добавим новую ветву
                {
                    $edge_id_in_edge_parameter = new EdgeParameter();                                                   // Создадим объект класса модели EdgeParameter
                    $edge_id_in_edge_parameter->edge_id = $edge_id;                                                     // Добавим id  ветви
                    $edge_id_in_edge_parameter->parameter_id = $parameter_values['parameterId'];                        // Добавим id-параметра
                    $edge_id_in_edge_parameter->parameter_type_id = 1;                                                  // Добавим id  типа параметра
                    if (!$edge_id_in_edge_parameter->save())                                                            // Если новые данные были сохранены
                    {
                        $errors[] = $edge_id_in_edge_parameter->errors;
                        throw new Exception("actionEditEdge. Ошибка сохранения параметров ветви $edge_id. Модели EdgeParameter");
                    }
                }
                $date_time = date('Y-m-d H:i:s');
                $edge_parameter_handbook_value_add = new EdgeParameterHandbookValue();
                $edge_parameter_handbook_value_add->edge_parameter_id = $edge_id_in_edge_parameter->id;
                $edge_parameter_handbook_value_add->date_time = $date_time;
                if ($parameter_values['parameterValue']) {
                    $edge_parameter_handbook_value_add->value = $parameter_values['parameterValue'];
                } else {
                    $edge_parameter_handbook_value_add->value = '-1';
                }
                $edge_parameter_handbook_value_add->status_id = 1;
                if ($edge_parameter_handbook_value_add->save()) {
                    $response_action[] = 'Данные успешно добавлены';
                } else {
                    $errors[] = $edge_parameter_handbook_value_add->errors;
                    throw new Exception('actionEditEdge. Oшибка добавления справочных значений в модель EdgeParameterHandbookValue');
                }

                $edge_param_to_cache[] = EdgeCacheController::buildStructureEdgeParametersValue(
                    $edge_id,
                    $edge_id_in_edge_parameter->id,
                    $parameter_values['parameterId'],
                    1,
                    $date_time,
                    $parameter_values['parameterValue'],
                    1
                );
            }

            /**
             * Блок сохранения данных в кеш - схема шахты
             */
            $edge_cache_controller = new EdgeCacheController();
            $edge_schema = $edge_cache_controller->initEdgeScheme($mine_id, $edge_id);
            if (!$edge_schema) {
                throw new Exception('actionEditEdge. Oшибка инициализации выработки в кеше схема шахты');
            } else {
                $warnings[] = 'actionEditEdge. кеш схемы шахты edge сохранен';
            }

            /**
             * Блок сохранения данных в кеш - главный кеш выработок - это сомнительная операция. Но оставлена на всякий случай Якимов М.Н. если будет тормозить то убрать.
             */
            $edge_mine = $edge_cache_controller->initEdgeMine($mine_id, $edge_id);
            if (!$edge_mine) {
                throw new Exception('actionEditEdge. Oшибка инициализации выработки в главном кеше выработок');
            } else {
                $warnings[] = 'actionEditEdge. главный кеш edge сохранен';
            }
            /**
             * Блок сохранения параметров и значений выработки в кеш
             */
            if ($edge_param_to_cache) {
                $response = $edge_cache_controller->multiSetEdgeParameterValue($edge_param_to_cache);
                if ($response['status'] == 0) {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new Exception('actionEditEdge. ошибка сохранения параметра edge в кеш Массовая вставка');
                } else {
                    $warnings[] = 'actionEditEdge. Кеш параметров edge сохранен';
                    $warnings[] = $edge_param_to_cache;
                }
            }
            $edges[] = $edge_id;
            $items['add'] = array();
            $items['delete'] = array();
            $items['change'] = $edges;
            $items['test'] = 'Raw';
        } catch (Throwable $exception) {
            $errors[] = 'actionEditEdge. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = 'actionEditEdge. Закончил выполнять метод';
        $result_main = array('Items' => $items, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'ResponseAction' => $response_action);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    public function actionGetPlaceListForDropdown()
    {
        $post = Assistant::GetServerMethod();
        $sql_filter = '';
        if (isset($post['mine_id']) && $post['mine_id'] != '' && $post['mine_id'] != '-1') {
            $mine_id = $post['mine_id'];
            $sql_filter = "mine_id = $mine_id";
        }
        $places = (new Query())
            ->select([
                'place.id id',
                'place.title title',
                'plast.id plast_id',
                'plast.title plast_title',
                'object.id object_id',
                'object.title object_title'
            ])
            ->from('place')
            ->leftJoin('plast', 'place.plast_id = plast.id')
            ->leftJoin('object', 'place.object_id = object.id')
            ->where($sql_filter)
            ->orderBy('title')
            ->all();
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $places;
    }


//метод нужен для вывода текущей информации о параметрах и их значениях у сенсоров
//при нажатии на конкретный сенсор и переходе на вкладку параметры выводится информация о последних измеренных,
//вычесленных или справочных значениях
//метод возвращает массив следующего содержания: sensor_id, parameter_title, Unit_title, value, typeparameter


// Вызываемую вьюшку исправил Якимов М.Н. 18.12.2018

    public function actionGetSensorsParametersNowArray()
        //public function actionTest()
    {

        $post = Assistant::GetServerMethod(); //получение данных от ajax-запроса
        $sql_filter = '';                                                                                               // переменная для создания фильтра в MySQL запросе
        $sensor_parameter_list_result = array();                                                                        // создаем пустой массив результирующих значений
        $errors = array();                                                                                              // массив ошибок для передачи во frontend
        $flag_filter = -1;                                                                                              // флаг проверки наличия входного параметра от frontend, т.к. запрос по всем сенсорам может выполняться очень долго, то поставлено ограничение на один конкретный сенсор
        if (isset($post['sensor_id']) && $post['sensor_id'] != '') {
            $sql_filter .= ' sensor_id=' . $post['sensor_id'] . '';                                                     // создание фильтра для вьюшки  по конкретному сенсору, если сенсор не задан то возвращается пустой массив с ошибкой
            $flag_filter = 1;                                                                                           // условие фильтрациии есть, запрос может выполняться
        } else {
            $errors[] = 'не задан конкретный сенсор, запрос не выполнялся';                                             // запись массива ошибок для передачи на frontend
            $flag_filter = 0;                                                                                           // обнуление флага фильтра для обработки случая когда не задан фильтр с frontend
        }

        if ($flag_filter == 1) {
            try {
                $sensor_parameter_list = (new Query())//запрос напрямую из базы по вьюшке view_personal_areas
                ->select(
                    [
                        'parameter_id',
                        'parameter_title',      //название параметра справочного
                        'parameter_type_id',    //тип параметра конкретного
                        'unit_title',           //единицы измерения параметра
                        'date_time_work',       //время измерения или вычисления значения конкретного параметра
                        'value',                //значение измеренного или вычисленного конкретного параметра крайнее
                        'handbook_value',       //значение справочного конкретного параметра крайнее
                        'handbook_date_time_work'//время создания справочного конкретного параметра крайнее
                    ])
                    ->from(['view_sensor_parameter_value_detail_main'])//представление с крайними значениями конкретного параметра конкретного сенсора
                    ->where($sql_filter)
                    ->orderBy(['parameter_id' => SORT_DESC, 'parameter_type_id' => SORT_DESC])
                    ->all();
                if (!$sensor_parameter_list) {
                    $errors[] = 'Запрос выполнился, нет данных по запрошенному сенсору в БД';                           //запрос не выполнился по той или иной причине
                } else {
                    $j = -1;                                                                                               //индекс создания результирующего запроса
                    $parameter_id_tek = 0;                                                                                //текущий параметер id
                    $type_parameter_id_tek = 0;                                                                           //текущий тип параметра 1 справочный, 2 измеренный, 3 вычисленный
                    $parameter_value_array = array();                                                                     //массив значений параметров по типам
                    $parameter_date_array = array();                                                                      //массив дат параметров по типам
                    $sensor_parameter_tek = array();                                                                      //список текущих значений полей сенсора

                    foreach ($sensor_parameter_list as $sensor_parameter_row) {
                        if ($parameter_id_tek != $sensor_parameter_row['parameter_id']) {
                            if ($j != -1) {
                                $sensor_parameter_list_result[$j]['parameter_id'] = $sensor_parameter_tek['parameter_id'];
                                $sensor_parameter_list_result[$j]['parameter_title'] = $sensor_parameter_tek['parameter_title'];
                                $sensor_parameter_list_result[$j]['unit_title'] = $sensor_parameter_tek['unit_title'];
                                $sensor_parameter_list_result[$j]['value'] = $parameter_value_array;
                                $sensor_parameter_list_result[$j]['date_time'] = $parameter_date_array;
                            }

                            $j++;

                            $sensor_parameter_tek['parameter_id'] = $sensor_parameter_row['parameter_id'];
                            $sensor_parameter_tek['parameter_title'] = $sensor_parameter_row['parameter_title'];
                            $sensor_parameter_tek['unit_title'] = $sensor_parameter_row['unit_title'];

                            $type_parameter_id_tek = $sensor_parameter_row['parameter_type_id'];
                            $parameter_id_tek = $sensor_parameter_row['parameter_id'];

                            $parameter_value_array[0] = '-1';                                                               //справочное значение
                            $parameter_value_array[1] = '-1';                                                               //измеренное значение
                            $parameter_value_array[2] = '-1';                                                               //вычисленное значение
                            //не используется
                            $parameter_date_array[0] = '-1';                                                                //дата ввода справочного значения
                            $parameter_date_array[1] = '-1';                                                                //дата измерения значения
                            $parameter_date_array[2] = '-1';                                                                //дата вычисления значения
                            if ($type_parameter_id_tek == 2) {
                                $comp = (float)$sensor_parameter_row['value'];                                            //$comp- временная переменная для для сравнения
                                if ($comp === $sensor_parameter_row['value']) {                                                //сравнивает значение и тип переменных с $comp
                                    $parameter_value_array[1] = self::RoundFloat($sensor_parameter_row['value'], 2);  //Метод округления значения до 2-го занака
                                    if ($sensor_parameter_row['date_time_work'] != -1) $parameter_date_array[1] = date('d.m.Y H:i:s', strtotime($sensor_parameter_row['date_time_work']));
                                } else {
                                    $parameter_value_array[1] = $sensor_parameter_row['value'];
                                    if ($sensor_parameter_row['date_time_work'] != -1) $parameter_date_array[1] = date('d.m.Y H:i:s', strtotime($sensor_parameter_row['date_time_work']));
                                }
                            } elseif ($type_parameter_id_tek == 1) {
                                $parameter_value_array[0] = $sensor_parameter_row['handbook_value'];
                                if ($sensor_parameter_row['handbook_date_time_work'] != -1) $parameter_date_array[0] = date('d.m.Y H:i:s', strtotime($sensor_parameter_row['handbook_date_time_work']));
                            } elseif ($type_parameter_id_tek == 3) {
                                $parameter_value_array[2] = $sensor_parameter_row['value'];
                                if ($sensor_parameter_row['date_time_work'] != -1) $parameter_date_array[2] = date('d.m.Y H:i:s', strtotime($sensor_parameter_row['date_time_work']));
                            } else {
                                $errors[] = 'Недокументированный тип параметра';
                            }
                        } else {
                            $type_parameter_id_tek = $sensor_parameter_row['parameter_type_id'];
                            $parameter_id_tek = $sensor_parameter_row['parameter_id'];
                            if ($type_parameter_id_tek == 2) {
                                $parameter_value_array[1] = self::RoundFloat($sensor_parameter_row['value'], 2);
                                if ($sensor_parameter_row['date_time_work'] != -1) $parameter_date_array[1] = date('d.m.Y H:i:s', strtotime($sensor_parameter_row['date_time_work']));
                            } elseif ($type_parameter_id_tek == 1) {
                                $parameter_value_array[0] = $sensor_parameter_row['handbook_value'];
                                if ($sensor_parameter_row['handbook_date_time_work'] != -1) $parameter_date_array[0] = date('d.m.Y H:i:s', strtotime($sensor_parameter_row['handbook_date_time_work']));
                            } elseif ($type_parameter_id_tek == 3) {
                                $parameter_value_array[2] = $sensor_parameter_row['value'];
                                if ($sensor_parameter_row['date_time_work'] != -1) $parameter_date_array[2] = date('d.m.Y H:i:s', strtotime($sensor_parameter_row['date_time_work']));
                            } else {
                                $errors[] = 'Недокументированный тип параметра';
                            }

                        }
                    }
                    //запись последнего значения по строкам
                    $sensor_parameter_list_result[$j]['parameter_id'] = $sensor_parameter_tek['parameter_id'];
                    $sensor_parameter_list_result[$j]['parameter_title'] = $sensor_parameter_tek['parameter_title'];
                    $sensor_parameter_list_result[$j]['unit_title'] = $sensor_parameter_tek['unit_title'];
                    $sensor_parameter_list_result[$j]['value'] = $parameter_value_array;
                    $sensor_parameter_list_result[$j]['date_time'] = $parameter_date_array;


                }
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        ArrayHelper::multisort($sensor_parameter_list_result, 'parameter_title', SORT_ASC);
        $result = array('sensor_list' => $sensor_parameter_list_result, 'errors' => $errors, 'flag_filter' => $flag_filter);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Метод округляет числа с плавающей точкой типа float, double
     * @param $input_value
     * @param $precision - точность (количестко округляемых чисел после запятой)
     * @return float|string
     */
    public function RoundFloat($input_value, $precision)
    {
        $res = '';
        $value = '';
        if ($input_value)                                                                                            //проверяем пришла ли нам строка
        {
            $values_round = explode(',', $input_value);                                                    //разбиваем строку на подстроки
            for ($i = 0; $i < count($values_round); $i++)                                                            //проходим по всем строкам
            {
                if ($i != count($values_round) && $i != 0)                                                             //ставим запятую кроме начала и конца возвращаемой строки
                {
                    $res .= ', ';
                }
                if (is_float((float)$values_round[$i]))                                                              //если входящее число имеет тип с плавающей точкой
                {
                    $value = round($values_round[$i], $precision);                                                  // то округляем число до нужных нам знаков
                    $res .= $value;
                } else {
                    $res .= $values_round[$i];                                                                      //добавляем тоже число что и было
                }
            }
            return $res;                                                                                            //возвращаем строку
        } else return $res;
    }


// actionForceCheckOut - метод принудительной выписки работника из шахты
// входные параметры:
//  $mine_id    - шахта с которой выписываем работника, модет быть не задана
//  $worker_id  - ключ работника, должен быть обязательно задан
// выходные параметры:
//  типовой набор
// пример использования: 127.0.0.1/unity/force-check-out?worker_id=2913862&mine_id=290
    public function actionForceCheckOut()
    {

        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();                                                                                              //массив предупреждений

        try {
            $post = Assistant::GetServerMethod();
            if (isset($post['worker_id']) and isset($post['mine_id']) and
                $post['worker_id'] != '' and $post['mine_id'] != ''
            ) {
                $worker_id = $post['worker_id'];
                $mine_id = $post['mine_id'];
                $warnings[] = 'actionForceCheckOut. Входные параметры переданы ' . $worker_id . ' ' . $mine_id;
            } else {
                throw new Exception('actionForceCheckOut. Входные параметры не переданы' . $post['worker_id'] . ' ' . $post['mine_id']);
            }
            $warnings[] = 'actionForceCheckOut. Начало выполнения метода';
            /**
             * инициализируем кеш работников
             */
            $worker_cache_controller = new WorkerCacheController();
            $datetime = \backend\controllers\Assistant::GetDateNow();
            $shift_info = StrataJobController::getShiftDateNum($datetime); //получаем смену
            /**
             * Проверяем на наличие работника в шахте
             */
            $worker = $worker_cache_controller->getWorkerMineHash($mine_id, $worker_id);
            if ($worker) {
                $response = WorkerMainController::moveWorkerMineInitCache($worker_id, -1);
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $warnings[] = 'actionForceCheckOut. Переместил работника в пустую шахту';
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception('actionForceCheckOut. Не смог перенести работника в пустую шахту');
                }
            } else {
//                throw new \Exception("actionForceCheckOut. в кеше шахты $mine_id нет такого работника $worker_id");
            }
            /**
             * сохраняем в кеш параметр шахты работника на -1
             */
            $worker_parameter = $worker_cache_controller->getParameterValueHash($worker_id, 346, 2);
            if ($worker_parameter) {
                $worker_parameter['value'] = -1;
                $response = $worker_cache_controller->setParameterValueHash($worker_id, $worker_parameter);
                if ($response) {
                    $warnings[] = 'actionForceCheckOut. Сменил значение в КЕШЕ параметра  шахты у работника -1';
                } else {
                    throw new Exception('actionForceCheckOut. Не удалось установить в КЕШЕ новое значение парамтера шахты -1');
                }
            } else {
                $worker_parameter = WorkerParameter::findOne(['worker_object_id' => $worker_id, 'parameter_id' => 346, 'parameter_type_id' => 2]);
                if ($worker_parameter) {
                    $worker_parameter['worker_parameter_id'] = $worker_parameter->id;
                } else {
                    throw new Exception("actionForceCheckOut. У работника в КЕШЕ нет последней шахты $worker_id");
                }
            }
            /**
             * пишем в БД новый параметр шахты
             */
            $response = WorkerBasicController::addWorkerParameterValue($worker_parameter['worker_parameter_id'], 0, $shift_info['shift_num'], 1, $datetime, $datetime);
            if ($response) {
                $warnings[] = 'actionForceCheckOut. Сменил значение параметра шахты у работника в БД на -1';
            } else {
                throw new Exception('actionForceCheckOut. Не удалось установить новое значение параметра шахта в БД у работника на -1');
            }
            /**
             * сохраняем в кеш параметр статуса спуска у рабоника на 0
             */
            $worker_parameter = $worker_cache_controller->getParameterValueHash($worker_id, 158, 2);
            if ($worker_parameter) {
                $worker_parameter['value'] = 0;
                $response = $worker_cache_controller->setParameterValueHash($worker_id, $worker_parameter);
                if ($response) {
                    $warnings[] = 'actionForceCheckOut. Сменил значение в КЕШЕ параметра статуса спуска у работника на 0';
                } else {
                    throw new Exception('actionForceCheckOut. Не удалось установить в КЕШЕ новое значение парамтера статуса спуска у работника на 0');
                }
            } else {
                $worker_parameter = WorkerParameter::findOne(['worker_object_id' => $worker_id, 'parameter_id' => 158, 'parameter_type_id' => 2]);
                if ($worker_parameter) {
                    $worker_parameter['worker_parameter_id'] = $worker_parameter->id;
                } else {
                    throw new Exception("actionForceCheckOut. У работника в КЕШЕ нет последней шахты $worker_id");
                }
            }
            /**
             * пишем в БД новый статус спуска у работника
             */
            $response = WorkerBasicController::addWorkerParameterValue($worker_parameter['worker_parameter_id'], 0, $shift_info['shift_num'], 1, $datetime, $datetime);
            if ($response) {
                $warnings[] = 'actionForceCheckOut. Сменил значение параметра статуса спуска у работника в БД на 0';
            } else {
                throw new Exception('actionForceCheckOut. Не удалось установить новое значение парамтера статуса спуска в БД у работника на 0');
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionForceCheckOut. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'actionForceCheckOut. Закончил выполнение метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;

    }

    public function actionReverseEdge()
    {
        $post = Assistant::GetServerMethod();
        $result = null;
        if (isset($post['edge_id']) and $post['edge_id'] != '' and isset($post['mine_id']) and $post['mine_id'] != '') {
            $edge_id = $post['edge_id'];
            $mine_id = $post['mine_id'];
            $response = EdgeHistoryController::ReplaceEdges($mine_id, $edge_id);
            if ($response['status'] != 1) {
                $result = $response['errors'];
            } else {
                $result = $response['Items'];
            }
            Yii::$app->response->format = Response::FORMAT_JSON;
            Yii::$app->response->data = $result;
        }
    }

    /**
     * Название метода: actionGetEdgeHistory()
     * Метод возвращает массив с выработками которые надо добавить на схему или удалить
     * @author Якимов М.Н.
     * Created date: on 19.02.2020 13:55
     *
     * Входные обязательные параметры:
     * mine_id - идентификактор шахты
     * date_now - дата от которой строить( т.е. дата на которую сейчас построенна схема)
     * date_need - дата на которую нужно строить( т.е. дата на которую мы хотим построить схему)
     * Пример вызова:
     * http://localhost/unity/get-edge-history?mine_id=270&date_now=2019-05-01%2023:25:01&date_need=2019-04-30%2007:25:01
     * @since ver
     */
    public function actionGetEdgeHistory()
    {
        // Стартовая отладочная информация
        $method_name = 'actionGetEdgeHistory';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        try {
            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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

            $post = Assistant::GetServerMethod();
            if (isset($post['mine_id']) and $post['mine_id'] != '' and
                isset($post['date_now']) and $post['date_now'] != '' and
                isset($post['date_need']) and $post['date_need'] != '')                                                     //проверяем чтоб все параметры переданны
            {
                $mine_id = $post['mine_id'];                                                                                //сохраняем в переменую идентификатор шахты
                $day_now = $post['date_now'];                                                                               //сохраняем в переменую дату от которой хотим получить данные(т.е. это дата того что сейчас отображается на схеме)
                $day_need = $post['date_need'];                                                                             //сохраняем в переменую дату на которую хотим получить данные(т.е. дата на которую надо построить схему)
                $errors = array();                                                                                          //массив ошибок
                $result = array();
            } else {
                $res = array('errors' => $errors);
                throw new Exception($method_name . '. Не переданы входящие данные');

            }
//            $edges_mine_value_list_now = Assistant::CallProcedure("GetEdgeHistory_new('$day_now', $mine_id)");              //ищем все выработки от даты которую хотим получить(т.е. это те выработки которые сейчас отображаются на схеме)

            $edges_mine_value_list_now = (new Query())
                ->select('edge.id as edge_id')
                ->from('edge')
                ->innerJoin('place', 'place.id = edge.place_id')
                ->innerJoin('edge_status', 'edge.id = edge_status.edge_id and edge_status.status_id=1')
                ->innerJoin(
                    "(select edge_status1.edge_id as edge_id, max(edge_status1.date_time) as max_date_time from (
                    select edge_id, date_time from edge_status where date_time<='" . $day_now . "'
                    ) edge_status1 group by edge_id) edge_status_last",
                    'edge_status_last.edge_id=edge_status.edge_id and edge_status_last.max_date_time=edge_status.date_time'
                )
//                ->where(['edge_status.status_id' => 1])
                ->andWhere(['mine_id' => $mine_id])
                ->indexBy('edge_id')
                ->all();

            /** Отладка */
            $description = 'Получил текущий список выработок';                                                                      // описание текущей отладочной точки
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

//            $edges_mine_value_list_need = Assistant::CallProcedure("GetEdgeHistory_new('$day_need', $mine_id)");            //ищем выработки за нужную нам дату

            $edges_mine_value_list_need = (new Query())
                ->select('edge.id as edge_id')
                ->from('edge')
                ->innerJoin('place', 'place.id = edge.place_id')
                ->innerJoin('edge_status', 'edge.id = edge_status.edge_id and edge_status.status_id=1')
                ->innerJoin(
                    "(select edge_status1.edge_id as edge_id, max(edge_status1.date_time) as max_date_time from (
                    select edge_id, date_time from edge_status where date_time<='" . $day_need . "'
                    ) edge_status1 group by edge_id) edge_status_last",
                    'edge_status_last.edge_id=edge_status.edge_id and edge_status_last.max_date_time=edge_status.date_time'
                )
//                ->where(['edge_status.status_id' => 1])
                ->andWhere(['mine_id' => $mine_id])
                ->indexBy('edge_id')
                ->all();
            /** Отладка */
            $description = 'Получил cписок выработок на дату';                                                                      // описание текущей отладочной точки
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
//            $warnings[]=$edges_mine_value_list_need;
            if (!$edges_mine_value_list_need or !$edges_mine_value_list_now)                                              //если такие выработки есть
            {
                $res = array('errors' => $errors);
                throw new Exception($method_name . '. Нет данных');
            }

            /**   ищем все выработки которые надо добавить */
            $Add_edges = array();                                                                                   //массив в который будем писать все выработки которые надо будет добавить на схему
            foreach ($edges_mine_value_list_need as $elem) {                                                        //перебираем массив нужных выработок
                if (!isset($edges_mine_value_list_now[$elem['edge_id']])) {                                               //если такая выработка есть на схеме то флаг переводим в положение не добавлять на схему так как она уже есть на схеме
                    $Add_edges[] = $elem;
                }
            }
            /**   ищем все выработки которые надо удалить */
            $delete_edges = array();                                                                                //массив в который будем писать все выработки которые надо будет удалить со схемы
            foreach ($edges_mine_value_list_now as $elem) {                                                         //перебираем массив текущих выработок
                if (!isset($edges_mine_value_list_need[$elem['edge_id']])) {                                               //если такая выработка есть на схеме то флаг переводим в положение не добавлять на схему так как она уже есть на схеме
                    $delete_edges[] = $elem;
                }
            }
            $res = array('add' => $Add_edges, 'delete' => $delete_edges, 'errors' => $errors);                       //формируем результат
            /** Метод окончание */

        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */


        $result_main = array(
            'json' => array(                                                                        //формируем массив в том виде который нужен unity
                'service_info' => array(
                    'operation_type' => '1',
                    'transaction' => '1',
                    'action_url' => 'unity'
                ),
                'data' => array(
                    'Items' => $res
                )
            ),
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Название метода: actionGetEdgeShema()
     * Метод получения схемы шахты из кеша EdSch:
     * @package frontend\controllers\positioningsystem
     *
     * Входные обязательные параметры:
     *
     * mine_id - id шахты по которой будет строится схема
     *
     * Входные необязательные параметры
     *
     * @see
     * @example
     * http://localhost/unity/get-edge-shema?mine_id=290
     * @author fidchenkoM
     * Created date: on 26.06.2019 11:22
     * @since ver
     */
    public static function actionGetEdgeShema()
    {
        $errors = array();    //объявляем пустой массив ошибок
        $result = array();
        $warnings = array();
        $status = 1;
        try {
            /**
             * Блок проверки наличия входных параметров и их парсинга
             */
            $post = Assistant::GetServerMethod(); //получение данных от ajax-запроса
            if (!isset($post['mine_id']) or $post['mine_id'] == '') {                                                         //массив параметров и их значений
                throw new Exception('actionGetEdgeShema. Входные параметры со страницы frontend не переданы');
            }

            $mine_id = $post['mine_id'];
            $warnings[] = "actionGetEdgeShema. Получен входной массив mine_id $mine_id";
            /**
             * Блок поиска выработок для схемы из кеша
             */
            $response = EdgeMainController::GetShema($mine_id);
            if ($response['status'] != 1) {
                throw new Exception('actionGetEdgeShema. Ошибка в методе получения кеша выработок в методе EdgeMainController::GetShema');
            }

            $warnings[] = $response['warnings'];
            $warnings[] = 'actionGetEdgeShema. Выполнил метод получения выработок из кеша';

            $result = $response['result'];

        } catch (Throwable $ex) {
            $status = 0;
            $errors[] = 'actionGetEdgeShema.Исключение: ';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
        }
        $result = array('errors' => $errors, 'Items' => $result, 'status' => $status, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: actionAddPath()
     * Метод добавления маршрута в БД
     *
     * @package frontend\controllers\positioningsystem
     *
     * Входные обязательные параметры:
     *
     * mine_id        - id шахты
     * title          - название маршрута
     * edges          - массив с id выработками данного маршрута
     *
     * @see
     * @example
     *
     * @author fidchenkoM
     * Created date: on 01.07.2019 14:43
     * @since ver
     */
    public function actionAddPath()
    {
        $errors = array();    //объявляем пустой массив ошибок
        $result = array();
        $warnings = array();
        $status = 1;
        try {
            /**
             * блок проверки наличия входных параметров и их парсинга
             */
            $post = Assistant::GetServerMethod(); //получение данных от ajax-запроса
            if (isset($post['mine_id']) && $post['mine_id'] != '' &&
                isset($post['title']) && $post['title'] != '' &&
                isset($post['edges']) && $post['edges'] != '') {                                                                                                           //массив параметров и их значений
                $title = $post['title'];
                $mine_id = $post['mine_id'];
                $edges = $post['edges'];
                $warnings[] = "actionAddPath. Получен входной массив title= $title";
            } else {
                throw new Exception('actionAddPath. Входные параметры со страницы frontend не переданы');
            }

            /**
             * блок проверки массива edge на существование на схеме шахты
             */
            foreach ($edges as $edge) {
                $new_edges = (new EdgeCacheController())->getEdgeScheme($mine_id, $edge);
                if ($new_edges == false) {
                    throw new Exception('actionAddPath. Переданный edge_id = $edge не найден на схеме');
                }
            }
            /**
             * блок сохранения маршрута c выработками
             */
            $response = self::SavePath($title, $edges);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $warnings[] = 'actionAddPath.Выполнил метод SavePath сохранение маршрута в БД';
            } else {
                $errors[] = 'actionAddPath. Ошибка сохранения маршрута';
                throw new Exception('actionAddPath. Ошибка сохранения маршрута');
            }

        } catch (Throwable $ex) {
            $status = 0;
            $errors[] = 'actionAddPath.Исключение: ';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
        }
        $result = array('errors' => $errors, 'Items' => $result, 'status' => $status, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: actionAddPathWithOrder()
     * Метод Добавления маршрута с привязкой к наряду
     * @package frontend\controllers\positioningsystem
     *
     * Входные обязательные параметры:
     * mine_id            - id шахты
     * title              - название маршрута
     * order_place_id     - id места наряда
     * edges              - массив id выработок данного маршрута
     * @see
     * @example
     *
     * @author fidchenkoM
     * Created date: on 01.07.2019 15:17
     * @since ver
     */
    public function actionAddPathWithOrder()
    {
        $errors = array();    //объявляем пустой массив ошибок
        $result = array();
        $warnings = array();
        $status = 1;
        try {
            /**
             * блок проверки наличия входных параметров и их парсинга
             */
            $post = Assistant::GetServerMethod(); //получение данных от ajax-запроса
            if (isset($post['mine_id']) && $post['mine_id'] != '' &&
                isset($post['title']) && $post['title'] != '' &&
                isset($post['order_place_id']) && $post['order_place_id'] != '' &&
                isset($post['edges']) && $post['edges'] != '') {                                                                                                           //массив параметров и их значений
                $title = $post['title'];
                $mine_id = $post['mine_id'];
                $order_place_id = $post['order_place_id'];
                $edges = $post['edges'];
                $warnings[] = "actionAddPathWithOrder. Получен входной массив title= $title order_place_id = $order_place_id mine_id $mine_id";
            } else {
                throw new Exception('actionAddPathWithOrder. Входные параметры со страницы frontend не переданы');
            }
            /**
             * блок проверки массива edge на существование на схеме шахты
             */
            foreach ($edges as $edge) {
                $new_edges = (new EdgeCacheController())->getEdgeScheme($mine_id, $edge);
                if ($new_edges == false) {
                    throw new Exception('actionAddPath. Переданный edge_id = $edge не найден на схеме');
                }
            }
            /**
             * блок сохранения маршрута c выработками
             */
            $response = self::SavePath($title, $edges);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $path_id = $response['Items'];
                $warnings[] = 'actionAddPathWithOrder.Выполнил метод SavePath сохранение маршрута в БД';
            } else {
                $errors[] = 'actionAddPathWithOrder. Ошибка сохранения маршрута';
                throw new Exception('actionAddPathWithOrder. Ошибка сохранения маршрута');
            }

            /**
             * блок сохранения маршрута к наряду
             */
            $response = self::SavePathToOrder($order_place_id, $path_id);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $warnings[] = 'actionAddPathWithOrder.Выполнил метод SavePathToOrder сохранение маршрута в БД';
            } else {
                $errors[] = 'actionAddPathWithOrder. Ошибка сохранения маршрута';
                throw new Exception('actionAddPathWithOrder. Ошибка сохранения маршрута');
            }
        } catch (Throwable $ex) {
            $status = 0;
            $errors[] = 'actionAddPathWithOrder.Исключение: ';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
        }
        $result = array('errors' => $errors, 'Items' => $result, 'status' => $status, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: SavePathToOrder()
     * Сохранение привязки маршрута к наряду
     *
     * @param $order_place_id - id наряда_места
     * @param $path_id - id маршрута
     * @return array|null
     *
     *
     * @package frontend\controllers\positioningsystem
     *
     *
     * @author fidchenkoM
     * Created date: on 01.07.2019 15:21
     * @since ver
     */
    public static function SavePathToOrder($order_place_id, $path_id)
    {
        $errors = array();    //объявляем пустой массив ошибок
        $result = array();
        $warnings = array();
        $status = 1;
        try {
            /**
             * блок сохранения переданного маршрута к переданному наряду
             */
            $order_place_path = new OrderPlacePath();
            $order_place_path->path_id = $path_id;
            $order_place_path->order_place_id = $order_place_id;
            if (!$order_place_path->save()) {
                $errors[] = $order_place_path->errors;
                throw new Exception('SavePathToOrder. Не смог сохранить запись в таблицу order_place_path');
            }
            $warnings[] = "SavePathToOrder.Выполнил Сохрание в таблицу order_place_path с id $order_place_path->id";
            $warnings[] = 'SavePathToOrder.Выполнил метод SavePathToOrder сохранение привязки маршрута к наряду в БД';
        } catch (Throwable $ex) {
            $status = 0;
            $errors[] = 'SavePathToOrder.Исключение: ';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
        }
        $result = array('errors' => $errors, 'status' => $status, 'warnings' => $warnings);
        return $result;
    }

    /**
     * Название метода: SavePath()
     * Метод сохранения маршрута с выработками в БД
     * @param $title - название маршрута
     * @param $edges - массив с id выработками данного маршрута
     * @return array|null  массив с ошибками, в Items запишется path_id
     *
     * @package frontend\controllers\positioningsystem
     *
     *
     * @author fidchenkoM
     * Created date: on 01.07.2019 14:59
     * @since ver
     */
    public static function SavePath($title, $edges)
    {
        $errors = array();    //объявляем пустой массив ошибок
        $result = array();
        $warnings = array();
        $status = 1;
        try {
            /**
             * блок сохранения маршрута
             */
            $path = new Path();
            $path->title = $title;
            if (!$path->save()) {
                $errors[] = $path->errors;
                throw new Exception('SavePath. Не смог сохранить запись в таблицу path');
            }
            $warnings[] = "SavePath. Сохранил запись в таблицу path c id = $path->id";
            /**
             * блок сохранения выработок маршрута
             */
            $edges_list = array();
            $new_edges_list = array();
            foreach ($edges as $edge) {
                $edges_list['path_id'] = $path->id;
                $edges_list['edge_id'] = $edge;
                $new_edges_list[] = $edges_list;

            }
            $warnings[] = 'SavePath. Сформировал массив для массовой вставки в таблицу path_edge';
            $insert_result = Yii::$app->db->createCommand()->batchInsert('path_edge',
                ['path_id', 'edge_id'], $new_edges_list)->execute();
            $warnings[] = 'SavePath. Количество вставленных записей в path_edge ' . $insert_result;
        } catch (Throwable $ex) {
            $status = 0;
            $errors[] = 'SavePath.Исключение: ';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
        }
        $result = array('errors' => $errors, 'Items' => $path->id, 'status' => $status, 'warnings' => $warnings);
        return $result;
    }

    /**
     * Название метода: actionDeletePath()
     * Метод удаления маршрута с проверкой на привязку к наряду
     * @package frontend\controllers\positioningsystem
     *
     * Входные обязательные параметры:
     * path_id   - id маршрута
     *
     * @author fidchenkoM
     * Created date: on 01.07.2019 15:54
     * @since ver
     */
    public function actionDeletePath()
    {
        $errors = array();                                                                                              //объявляем пустой массив ошибок
        $result = array();
        $warnings = array();
        $status = 1;
        try {
            /**
             * блок проверки наличия входных параметров и их парсинга
             */
            $post = Assistant::GetServerMethod(); //получение данных от ajax-запроса
            if (isset($post['path_id']) && $post['path_id'] != '') {                                                                                                           //массив параметров и их значений
                $path_id = $post['path_id'];
                $warnings[] = "actionDeletePath. Получен входной массив path_id= $path_id ";
            } else {
                throw new Exception('actionDeletePath. Входные параметры со страницы frontend не переданы');
            }
            /**
             * блок поиска привязки маршрута к наряду
             */
            $order_place_path = OrderPlacePath::find()->where(['path_id' => $path_id])->one();
            if ($order_place_path) {
                $errors[] = 'actionDeletePath. Данный маршрут привязан к наряду.';
                throw new Exception('actionDeletePath. Данный маршрут привязан к наряду.');
            }
            /**
             * блок удаления маршрута
             */
            $response = self::DeletePath($path_id);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $warnings[] = 'actionDeletePath.Выполнил метод DeletePath удаления маршрута в БД';
            } else {
                $errors[] = 'actionDeletePath. Ошибка удаления маршрута';
                throw new Exception('actionDeletePath. Ошибка удаления маршрута');
            }
        } catch (Throwable $ex) {
            $status = 0;
            $errors[] = 'actionDeletePath.Исключение: ';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
        }
        $result = array('errors' => $errors, 'Items' => $result, 'status' => $status, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: DeletePath()
     * Удаление маршрута если он не привязан к наряду
     * @param $path_id - id маршрута
     * @return array|null
     *
     * Входные необязательные параметры
     *
     * @package frontend\controllers\positioningsystem
     *
     * Входные обязательные параметры:
     * @see
     * @example
     *
     * @author fidchenkoM
     * Created date: on 01.07.2019 15:48
     * @since ver
     */
    public static function DeletePath($path_id)
    {
        $errors = array();                                                                                              //объявляем пустой массив ошибок
        $result = array();
        $warnings = array();
        $status = 1;
        try {
            /**
             * блок удаления маршрута
             */
            PathEdge::deleteAll(['path_id' => $path_id]);
            Path::deleteAll(['id' => $path_id]);
        } catch (Throwable $ex) {
            $status = 0;
            $errors[] = 'DeletePath.Исключение: ';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
        }
        $result = array('errors' => $errors, 'status' => $status, 'warnings' => $warnings);
        return $result;
    }

    /**
     * Название метода: actionBindingPathToOrder()
     * Метод привязки маршрута к наряду
     * @package frontend\controllers\positioningsystem
     *
     * Входные обязательные параметры:
     * path_id              - id маршрута
     * order_place_id       - id наряда места
     * @author fidchenkoM
     * Created date: on 01.07.2019 16:35
     * @since ver
     */
    public function actionBindingPathToOrder()
    {
        $errors = array();                                                                                              //объявляем пустой массив ошибок
        $result = array();
        $warnings = array();
        $status = 1;
        try {
            /**
             * блок проверки наличия входных параметров и их парсинга
             */
            $post = Assistant::GetServerMethod(); //получение данных от ajax-запроса
            if (isset($post['path_id']) && $post['path_id'] != '' &&
                isset($post['order_place_id']) && $post['order_place_id'] != '') {                                                                                                           //массив параметров и их значений
                $path_id = $post['path_id'];
                $order_place_id = $post['order_place_id'];
                $warnings[] = "actionBindingPathToOrder. Получен входной массив path_id= $path_id order_place_id = $order_place_id";
            } else {
                throw new Exception('actionBindingPathToOrder. Входные параметры со страницы frontend не переданы');
            }
            /**
             * Блок проверки уже привязки маршрута к наряду
             */
            $order_place_path = OrderPlacePath::find()->where(['order_place_id' => $order_place_id, 'path_id' => $path_id])->one();
            if (!$order_place_path) {
                $response = self::BindingPath($order_place_id, $path_id);
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $warnings[] = 'actionBindingPathToOrder.Выполнил метод BindingPath привязки маршрута в БД';
                } else {
                    $errors[] = 'actionBindingPathToOrder. Ошибка привязки маршрута';
                    $errors[] = $response['errors'];
                    throw new Exception('actionBindingPathToOrder. Ошибка привязки маршрута');
                }
            } else {
                $errors[] = 'actionBindingPathToOrder.Данный маршрут уже привязан к данному наряду';
                throw new Exception('actionBindingPathToOrder. Данный маршрут уже привязан к данному наряду');
            }
        } catch (Throwable $ex) {
            $status = 0;
            $errors[] = 'actionBindingPathToOrder.Исключение: ';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
        }
        $result = array('errors' => $errors, 'status' => $status, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: BindingPath()
     * Метод привязки маршрута к наряду
     * @param $order_place_id - id наряда места
     * @param $path_id - id маршрута
     * @return array|null
     *
     * @package frontend\controllers\positioningsystem
     *
     *
     * @author fidchenkoM
     * Created date: on 01.07.2019 16:34
     * @since ver
     */
    public static function BindingPath($order_place_id, $path_id)
    {
        $errors = array();                                                                                              //объявляем пустой массив ошибок
        $result = array();
        $warnings = array();
        $status = 1;
        try {
            $new_order_place_path = new OrderPlacePath();
            $new_order_place_path->path_id = $path_id;
            $new_order_place_path->order_place_id = $order_place_id;
            if (!$new_order_place_path->save()) {
                $errors[] = $new_order_place_path->errors;
                throw new Exception('BindingPath. Не смог сохранить запись в таблицу order_place_id');
            }
        } catch (Throwable $ex) {
            $status = 0;
            $errors[] = 'BindingPath.Исключение: ';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
        }
        $result = array('errors' => $errors, 'status' => $status, 'warnings' => $warnings);
        return $result;
    }

    /**
     * Название метода: actionGetPath()
     * Метод получения всех маршрутов с их выработками
     * @package frontend\controllers\positioningsystem
     *
     * Входные обязательные параметры:
     * - нет передаваемых параметров
     *
     * @author fidchenkoM
     * Created date: on 02.07.2019 7:52
     * @since ver
     */
    public function actionGetPath()
    {
        $errors = array();                                                                                              //объявляем пустой массив ошибок
        $result = array();
        $warnings = array();
        $status = 1;

        $result_items = array();
        try {
            $paths = Path::find()->with('pathEdges')->all();
            foreach ($paths as $item) {
                $temp_result = array();
                $temp_result['path_id'] = $item->id;
                $temp_result['title'] = $item->title;
                foreach ($item->pathEdges as $edge) {
                    $temp_result['edges'][] = $edge->edge_id;
                }
                $result_items[] = $temp_result;
            }
        } catch (Throwable $ex) {
            $status = 0;
            $errors[] = 'actionGetPath.Исключение: ';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
        }
        $result = array('errors' => $errors, 'Items' => $result_items, 'status' => $status, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }


    /**
     * Название метода: actionGetForbiddenZone()
     * Метод получения массива запретных зон
     * @package frontend\controllers\positioningsystem
     *
     *
     * @example  http://localhost/unity/get-forbidden-zone
     *
     * @author fidchenkoM
     * Created date: on 02.07.2019 10:54
     * @since ver
     */
    public function actionGetForbiddenZone()
    {
        $errors = array();                                                                                              //объявляем пустой массив ошибок
        $result = array();
        $warnings = array();
        $status = 1;
        $result_items = array();
        try {
            /**
             * Блок получения запретных зон
             */
            $forb_zone = ForbiddenZone::find()
                ->joinWith('forbiddenEdges')
                ->joinWith('forbiddenZaprets.forbiddenType')
                ->joinWith('forbiddenZaprets.forbiddenTimes')
//                ->joinWith([
//                    'forbiddenZaprets.forbiddenTimes' => function ($query) {
//                        $query->andWhere(['forbidden_time.status_id'=> 19]);
//                             },
//                ])
                ->orderBy(['forbidden_zone.id' => SORT_ASC])->all();
            /**
             * Блок формирования массива
             */
            foreach ($forb_zone as $zone) {
                $temp_result = array();
                $i = 0;
                $temp_result['zone_id'] = $zone->id;
                $temp_result['zone_title'] = $zone->title;
                foreach ($zone->forbiddenZaprets as $zapret) {
                    $temp_result['forbidden'][$i]['zapret_id'] = $zapret->id;
                    $temp_result['forbidden'][$i]['description'] = $zapret->description;
                    $temp_result['forbidden'][$i]['type'] = $zapret->forbidden_type_id;
                    $temp_result['forbidden'][$i]['date_time_create'] = $zapret->date_time_create;
                    $j = 0;
                    foreach ($zapret->forbiddenTimes as $forb_time) {
                        $temp_result['forbidden'][$i]['time_interval'][$j]['time_id'] = $forb_time->id;
                        $temp_result['forbidden'][$i]['time_interval'][$j]['date_start'] = $forb_time->date_start;
                        $temp_result['forbidden'][$i]['time_interval'][$j]['date_end'] = $forb_time->date_end;
                        $temp_result['forbidden'][$i]['time_interval'][$j]['status_id'] = $forb_time->status_id;
                        $j++;
                    }
                    $i++;
                }
                foreach ($zone->forbiddenEdges as $edge) {
                    $temp_result['edges']['edge_id'][] = $edge->edge_id;

                }
                $result_items[] = $temp_result;
            }
        } catch (Throwable $ex) {
            $status = 0;
            $errors[] = 'actionGetForbiddenZone.Исключение: ';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
        }
        $result = array('errors' => $errors, 'Items' => $result_items, 'status' => $status, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }


    /**
     * actionGetCommunicationList - Метод получения схемы передачи данных между узлами связи
     * Входные параметры:
     *      Обязательные:
     *          mine_id     - id шахты.
     * Выходные параметр:
     *          sensor_1    - узел связи от которого идет сигнал
     *          sensor_2    - узел связи к которому идет сигнал
     *          signal_level- уровень сигнала
     * Пример запроса:
     * http://127.0.0.1/unity/get-communication-list?mine_id=290
     * Сделал: Якимов М.Н.
     */
    public function actionGetCommunicationList()
    {
        $log = new LogAmicumFront("actionGetCommunicationList");
        $path = [];

        try {
            $log->addLog("Начало выполнение метода");

            /**
             * Блок обработки входных данных
             */
            $post = Assistant::GetServerMethod();
            if (isset($post['mine_id']) and $post['mine_id'] != '') {
                $mine_id = $post['mine_id'];
                $log->addLog("Получили входные данные $mine_id");
            } else {
                throw new Exception('Обязательный входной параметр mine_id не передан');
            }

            /**
             * Блок получения узлов связи из кеша по заданной шахте
             */
            $sensor_cache_controller = new SensorCacheController();
            $sensors = $sensor_cache_controller->getSensorMineHash($mine_id);
            if (!$sensors) {
                throw new Exception('actionGetCommunicationList. Кеш сенсоров шахты ' . $mine_id . ' пуст');
            }
            $log->addLog("Получил список сенсоров шахты");

            /**
             * Блок получения параметров 310 и 312 узлов связи из кеша
             */
//            $response = $sensor_cache_controller->getSensorsParametersValues($mine_id, '*', '310:2, 312:2, 88:1');
            $sphv = SensorBasicController::getSensorParameterHandbookValue('*', 88);
            $spv = (new Query())
                ->select([
                    'sensor_id',
                    'sensor_parameter_id',
                    'parameter_id',
                    'parameter_type_id',
                    'date_time',
                    'value',
                    'status_id'
                ])
                ->from('view_initSensorParameterValue')
                ->where('(parameter_id=310 or parameter_id=312) and parameter_type_id=2')
                ->all();
            $sensor_parameters = array_merge($sphv, $spv);

            if (empty($sensor_parameters)) {
                throw new Exception('БД параметров сенсоров шахты ' . $mine_id . ' пуст');
            }

            $log->addLog("Список параметров 310 и 312  тип 2 сенсоров шахты получен");
            /**
             * создаем справочник значений
             */
            foreach ($sensor_parameters as $sensor_parameter) {
                $sensor_parameter_array[$sensor_parameter['sensor_id']][$sensor_parameter['parameter_id']] = $sensor_parameter;
                if ($sensor_parameter['value'] != NULL and $sensor_parameter['parameter_id'] == 88) {
                    $network_sensor_array[$sensor_parameter['value']] = $sensor_parameter['sensor_id'];
                }
            }

            $log->addLog("Получил параметры для построения связей");

            /**
             * Блок фильтрации параметров сенсоров заданной шахты
             */
            $j = 0;
            foreach ($sensors as $sensor) {
                /**
                 * блок получения параметра 310 - сосед
                 */
                if (
                    $sensor['object_id'] == 45 or
                    $sensor['object_id'] == 46 or
                    $sensor['object_id'] == 105 or
                    $sensor['object_id'] == 90 or
                    $sensor['object_id'] == 91
                ) {
                    /**
                     * получаем уровень сигнала для связки 312
                     */
                    if (isset($sensor_parameter_array[$sensor['sensor_id']]['312'])) {
                        $signal_level = $sensor_parameter_array[$sensor['sensor_id']]['312']['value'];
                    } else {
                        $signal_level = -100;
                    }

                    /**
                     * упаковываем исходных массив
                     */
                    if (isset($sensor_parameter_array[$sensor['sensor_id']]['310'])) {
                        $network_id_soseda = $sensor_parameter_array[$sensor['sensor_id']]['310']['value'];
                        if ($network_id_soseda != NULL and isset($network_sensor_array[$network_id_soseda])) {
                            $sensor_id_naighbour = $network_sensor_array[$network_id_soseda];
                            $path[$j]['sensor_1'] = $sensor['sensor_id'];
                            $path[$j]['sensor_2'] = $sensor_id_naighbour;
                            $path[$j]['signal_level'] = $signal_level;
                            $j++;
                        }
//                        else {
//                            $log->addLog("Cетевой адрес у узла не задан $network_id_soseda или нет соседа у сенсора " . $sensor['sensor_id']);
//                        }
                    }
//                    else {
//                        $log->addLog('У сенсора ' . $sensor['sensor_id'] . ' не задан 310 параметр или он пуст');
//                    }
                }
            }

            $log->addLog("Закончил выполнение метода");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return json_encode(array_merge(['Items' => $path], $log->getLogAll()));
    }

    // формирует схему шахты - ветви и параметры для схемы для Unity             ВОЗВРАЩАЕТ СПИСОК ВСЕХ ПАРАМЕТРОВ ВЕТВИ ИЗ КЕШа методом POST
    // ВАЖНО!!!!
    // ДАННЫЙ МЕТОД ВОЗВРАЩАЕТ МАССИВ!!!!
    // метод работает так: если данных в кеше нет, то он их выгребает из бд и заполняет кеш, затем он считывает кеш
    // и заполняет результирующий массив
    // если данные есть в кеше, то сразу заполняет результирующий массив
    // сделано так, для того, что бы проверять как заполнен кеш, т.к. возможны проблемы с ним.
    // Разработал Якимов М.Н.
    // Дополнил Одилов О.У.
    // Входные параметры:
    // mine_id  - ключ шахтного поля
    // edge_id  - ключ ребра
    // Выходной массив:
    //         ['edge_id']
    //              |-  ['mine_id']               - id шахты
    //              |-  ['edge_id']               - id ветви
    //              |-  ['place_id']              - id места
    //              |-  ['place_title']           - название места
    //              |-  ['conjunction_start_id']  - id сопряжения старт
    //              |-  ['conjunction_end_id']    - id сопряжения конец
    //              |-  ['xStart']                - координата начала сопряжения X
    //              |-  ['yStart']                - координата начала сопряжения Y
    //              |-  ['zStart']                - координата начала сопряжения Z
    //              |-  ['xEnd']                  - координата конца сопряжения X
    //              |-  ['yEnd']                  - координата конца сопряжения Y
    //              |-  ['zEnd']                  - координата конца сопряжения Z
    //              |-  ['place_object_id']       - тип места - типовой объект по месту
    //              |-  ['danger_zona']           - параметр опасная зона
    //              |-  ['color_edge']            - параметр цвет выработки
    //              |-  ['color_edge_rus']        - параметр цвет выработки по русски
    //              |-  ['conveyor']              - параметр наличия конвейера в данном эдже
    //              |-  ['conveyor_tag']          - тег конвейера для остановки в случае обнаружения движения
    //              |-  ['value_ch']              - уставка в выработке по СН4
    //              |-  ['value_co']              - уставка в выработке по СО
    //              |-  ['date_time']             - дата создания выработки - используется как статус актуальности
    // http://...../cache-getter/get-edge-shema-cache?mine_id=290
    // http://...../cache-getter/get-edge-shema-cache?mine_id=290&edge_id=22143
    public static function actionGetEdgeShemaCache()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $startTime = microtime(true);
        // костыль сделал Якимов М.Н. - метод требуется полностью переписать
        //получение данных от ajax-запроса
        try {
            $warnings[] = "actionGetEdgeShemaCache. Начал выполнять метод";
            $result = array();

            $post = Assistant::GetServerMethod();             //получение данных от ajax-запроса

            if (!isset($post['mine_id']) or $post['mine_id'] == '') {                                                              //если передан id ветви
                throw new Exception("actionGetEdgeShemaCache. Параметры не переданы");
            }

            $mine_id = $post['mine_id'];
            if ($mine_id == -1) {
                $mine_id = '*';
            }
            $warnings[] = "actionGetEdgeShemaCache. Получил входные параметры $mine_id";

            if (isset($post['edge_id']) and $post['edge_id'] != '') {                                                              //если передан id ветви
                $edge_id = $post['edge_id'];
                $warnings[] = "actionGetEdgeShemaCache. Получил входные параметры $edge_id";
            } else {
                $edge_id = '*';
                $warnings[] = "actionGetEdgeShemaCache. Edge_id Не передан. выборка по всей схеме";
            }

            $warnings[] = $post;
            $warnings[] = $edge_id;

            if (!COD) {
                $edge_cache_controller = (new EdgeCacheController());
                /**
                 * блок получения выработки
                 */
                $edges = $edge_cache_controller->multiGetEdgesSchema($mine_id, $edge_id);
                if (!$edges) {
                    throw new Exception("actionGetEdgeShemaCache. Не удалось получить из кеша схему горных выработок");
                }
            } else {
                $edges = EdgeBasicController::getEdgeScheme($mine_id, $edge_id);
            }
            $result = $edges;
        } catch (Throwable $exception) {
            $errors[] = "actionGetEdgeShemaCache. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $warnings[] = "actionGetEdgeShemaCache. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => []);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * МЕТОД СОЕДИНЕНИЯ (ОБЪЕДИНЕНИЯ) ПОВОРОТОВ.
     * АЛГОРИТМ: Находим полученный id поворота в выработках, если у выработки начало либо конец поворота == полученному id повороту, то редактируем
     *  ---- $post['conjuction_set'] => то на что нужно заменить
     *  ---- $post['conjuction_replace'] => то на что нужно заменить  $post['conjuction_set']
     * Автор: Якимов М.Н.
     */
    public function actionCombineConjunction()
    {
        $post = Assistant::GetServerMethod();
        $errors = array();
        $flag_done = true;
        $add_edge_list = array();                                                                                         // массив выработок на добавление
        $delete_edge_list = array();                                                                                      // массив выработок на удаление
        $change_edge_list = array();
        $place_id = array();
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();

        // костыль сделал Якимов М.Н. - метод требуется полностью переписать. т.к. не используется история сохранения выработок

        try {
            $warnings[] = "actionCombineConjunction. Начал выполнять метод";
            if (isset($post['conjuction_set']) and $post['conjuction_set'] != '' and isset($post['conjuction_replace']) and $post['conjuction_replace'] != '') {
                $conjuction_set = $post['conjuction_set'];
                $conjuction_replace = $post['conjuction_replace'];
            } else                                                                                                        // если данных нет в БД
            {
                throw new Exception("actionCombineConjunction. Переданы не все входные параметры");
            }

            $edges_conjuctions = Edge::find()
                ->join('JOIN', 'edge_status', 'edge_status.edge_id = edge.id and edge_status.status_id=1')
                ->where(['conjunction_start_id' => $conjuction_replace])
                ->orWhere(['conjunction_end_id' => $conjuction_replace])
                ->all();
//            Assistant::PrintR($edges_conjuctions);
            if (!$edges_conjuctions) {                                                                                    // если нашли данные, то редактируем для каждой выработки указанный поворот
                throw new Exception("actionCombineConjunction. В БД ни у одной выработки нет указанного поворота");
            }
            /********************           Редактирование в таблице  ********************************/
            foreach ($edges_conjuctions as $edge) {
                $edge_id = $edge->id;
                $edge_conjuction_start_id = $edge->conjunction_start_id;
                $edge_conjuction_end_id = $edge->conjunction_end_id;
                if ($edge_conjuction_start_id == $conjuction_replace)                                               // если начало выработки == полученному id повороту, то редактируем
                {
                    $edge->conjunction_start_id = $conjuction_set;
                    if (!$edge->save()) {
                        $errors[] = $edge->errors;
                        throw new Exception("actionCombineConjunction. Ошибка сохранения conjunction_start_id у выработки с ID = $edge_id");
                    } else {
                        $warnings[] = "actionCombineConjunction. Поменял у edge_id = $edge_id conjunction_start_id на $conjuction_set";
                        $place_id[] = $edge->place_id;
                    }
                }
                if ($edge_conjuction_end_id == $conjuction_replace)                                               // если начало выработки == полученному id повороту, то редактируем
                {
                    $edge->conjunction_end_id = $conjuction_set;
                    if (!$edge->save()) {
                        $errors[] = $edge->errors;
                        throw new Exception("actionCombineConjunction. Ошибка сохранения conjunction_start_id у выработки с ID = $edge_id");
                    } else {
                        $warnings[] = "actionCombineConjunction. Поменял у edge_id = $edge_id conjunction_end_id на $conjuction_set ";
                        $place_id[] = $edge->place_id;
                    }
                }
            }
            $edge_cache_controller = (new EdgeCacheController());
            foreach ($edges_conjuctions as $edge) {
                $place = Place::findOne(['id' => $edge->place_id]);
                if ($place) {
                    $flag_cache_done = $edge_cache_controller->runInit($place->mine_id, $edge->id)['status'];
                    $change_edge_list[] = $edge->id;
                }
            }


        } catch (Throwable $exception) {
            $errors[] = "actionCombineConjunction. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $warnings[] = "actionCombineConjunction. Закончил выполнять метод";
        $items['add'] = $add_edge_list;
        $items['delete'] = $delete_edge_list;
        $items['change'] = $change_edge_list;
        $items['test'] = 'Raw';
        $result = array(
            'place_id' => $place_id,
            'errors' => $errors,
            'Items' => $items,
            'warnings' => $warnings,
            'status' => $status
        );
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * actionGetTypical3dModel - метод получения списка путей 3d моделей типовых объектов, если задан входной параметр object_id ,
     * то получение пути по конкретной 3D модели типового объекта
     * входные параметры:
     *      object_id       - ключ типового объетка (необязательный параметр)
     * выходные параметры:
     *      [object_id]     - ключ типового объетка (необязательный параметр)
     *          path:       - путь до 3D модели типогово объекта
     * Разработал:
     *  Якимов М.Н. 15.08.2019 в 11:30
     * Пример использования:
     *  127.0.0.1/unity/get-typical3d-model?object_id=4
     *  127.0.0.1/unity/get-typical3d-model
     */
    public function actionGetTypical3dModel()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();

        try {
            $warnings[] = "actionGetTypical3dModel. Начал выполнять метод";
            /**
             * Блок обработки входных параметров
             */
            $post = Assistant::GetServerMethod();
            if (isset($post['object_id']) && $post['object_id'] != '') {
                $object_id = $post['object_id'];                                                                          //записываем сенсор id для того, что бы потом найти place для этого сенсора
            } else {
                $object_id = "";
            }

            $model3d_paths = (new Query())//Ищем в БД все сенсоры находящиеся на разбиваемой выработки
            ->select([
                'object_id',
                'value'
            ])
                ->from('view_Get3DModelTypicalObjectLast')
                ->filterWhere(['object_id' => $object_id])
                ->indexBy('object_id')
                ->all();

            $result = $model3d_paths;
        } catch (Throwable $ex) {
            $errors[] = "actionGetSensorsParameters. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $warnings[] = "actionGetTypical3dModel. Закончил выполнять метод";
        $result_main = array('Items' => $result,
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    public function actionGetWebSocketParam()
    {
        $result_main = 'ws://' . AMICUM_CONNECT_STRING_WEBSOCKET . '/ws';
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * actionSaveEquipmentOnScheme - Метод добавления, перемещения оборудования в 3D схеме, в БД и в КЕШ
     * Название метода: actionSaveEquipmentOnScheme()
     * Входные обязательные поля параметры:
     *  $post["equipment_id"] - идентификатор оборудования
     *  $post["edge_id"] - идентификатор Edge (выработки).
     *  $post["XYZ"] - позиция
     *  $post["mine_id"] - идентификатор шахты
     * Пример вызова: http://127.0.0.1:98/unity/save-equipment-on-scheme?equipment_id=186730&XYZ=13904.5%2C-799.1%2C-11771.3&edge_id=138484&mine_id=290
     * Created date: 2020.01.28
     */
    public function actionSaveEquipmentOnScheme()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();
        $errors = array();
        try {
            $response = null;
            $session = Yii::$app->session;
            $session->open();
            /**
             * Проверка наличия сессии пользователя
             */
            if (isset($session['sessionLogin'])) {
                $warnings[] = 'actionSaveEquipmentOnScheme. Сессия в порядке';//если в сессии есть логин
            } else {
                $errors[] = 'Время сессии закончилось. Требуется повторный ввод пароля';
                $this->redirect('/');
                throw new Exception('actionSaveEquipmentOnScheme. Сессия закончилась');
            }

            /**
             * Наличие прав пользователя
             */
            if (AccessCheck::checkAccess($session['sessionLogin'], 79)) {
                $warnings[] = 'actionSaveEquipmentOnScheme. Права на выполнение операции в порядке';
            } else {
                throw new Exception('actionSaveEquipmentOnScheme. Недостаточно прав для совершения данной операции');
            }

            $post = Assistant::GetServerMethod();
            if (isset($post['equipment_id']) && $post['equipment_id'] != '' &&
                isset($post['edge_id']) && $post['edge_id'] != '' &&
                isset($post['XYZ']) && $post['XYZ'] != '') {

                $warnings[] = 'actionSaveEquipmentOnScheme. Прошел проверку на входные параметры';
                $equipment_id = $post['equipment_id'];
                $edge_id = $post['edge_id'];
//                $mine_id = $post['mine_id'];
                $XYZ = $post['XYZ'];
                $warnings[] = 'actionSaveEquipmentOnScheme. переданы параметры: equipment_id = ' . $post['equipment_id'] .
                    'edge_id = ' . $post['edge_id'] . 'coordinates = ' . $post['XYZ'];
            } else {
                throw new Exception('actionSaveEquipmentOnScheme. Не передан один из параметров: equipment_id = ' . $post['equipment_id'] .
                    'edge_id = ' . $post['edge_id'] . 'coordinates = ' . $post['XYZ']);
            }

            /**
             * Инициализация сенсора в кеше
             */
            $equipment_main_cache = (new \backend\controllers\cachemanagers\EquipmentCacheController())->getEquipmentMineByEquipment($equipment_id);
            if ($equipment_main_cache === false) {
                $warnings[] = 'actionSaveEquipmentOnScheme. Кеш сенсора нет. Начинаю инициализацию';
                /**
                 * проверяем есть или нет у сенсора параметр шахты и если нет то создаем
                 */
                $response = EquipmentMainController::getOrSetEquipmentParameter($equipment_id, 346, 1);

                if ($response['status'] == 1) {
                    $equipment_parameter_id = $response['equipment_parameter_id'];
                    $warnings[] = $response['warnings'];
                    $warnings[] = "actionSaveEquipmentOnScheme. Параметр шахты сенсора $equipment_id инициализировал $equipment_parameter_id";
                    $status *= $response['status'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception("actionSaveEquipmentOnScheme. Ошибка инициализации параметра шахты 346/1 сенсора $equipment_id");
                }
                /**
                 * инициализирую кеш оборудования
                 */
                $response = EquipmentMainController::initEquipmentInCache($equipment_id);
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $warnings[] = 'actionSaveEquipmentOnScheme. Кеш оборудование инициализировал';
                    $status *= $response['status'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception("actionSaveEquipmentOnScheme. Ошибка инициализации кеша сенсора $equipment_id");
                }
            } else {
                $warnings[] = 'actionSaveEquipmentOnScheme. Кеш сенсора БЫЛ инициализирован ранее';
            }
            /**
             * Блок поиска шахты по переданному edge_id
             */
            $edges = Edge::findOne(['id' => $edge_id]);                                                           //находим по ветви/ребру/выработке id места в котором будет стоять объект - сенсор
            if ($edges) {
                $warnings[] = 'actionSaveEquipmentOnScheme. Место найдено в БД в edge';
                $place_id = $edges->place_id;
            } else {
                throw new Exception('actionSaveEquipmentOnScheme. Не корректный кеш схемы! За переданным edge_id не существует в БД реального edge_id: ' . $edge_id);
            }
            unset($edges);

            /**
             * Блок поиска шахты по найденному place_id
             */
            $places = Place::findOne(['id' => $place_id]);
            if ($places) {
                $warnings[] = 'actionSaveEquipmentOnScheme. Шахта найдена в БД в place';
                $mine_id = $places->mine_id;
            } else {
                throw new Exception('actionSaveEquipmentOnScheme. За найденным place_id не существует в БД реального place_id');
            }
            unset($places);

            /**
             * Блок поиска оборудования в БД
             */
            $equipment = Equipment::findOne(['id' => $equipment_id]);
            if ($equipment) {
                $warnings[] = "actionSaveEquipmentOnScheme. Оборудование $equipment_id найден в БД";
                $object_id = $equipment->object_id;
                $equipment_title = $equipment->title;
            } else {
                throw new Exception("actionSaveEquipmentOnScheme. Оборудование $equipment_id в БД не найден");
            }
            unset($equipment);

            /**
             * Блок определения типового объекта устанавливаемого оборудования
             */
            $typical_object = TypicalObject::find()
                ->where(['id' => $object_id])
                ->with('objectType')
                ->limit(1)
                ->one();
            if (!$typical_object) {
                throw new Exception("actionSaveEquipmentOnScheme. Типовой объект $object_id не найден в БД в таблице object");
            }
            $object_title = $typical_object->title;
            $object_type_id = $typical_object->object_type_id;
            $object_type_title = $typical_object->objectType->title;
            $object_kind_id = $typical_object->objectType->kind_object_id;
            $warnings[] = 'actionSaveEquipmentOnScheme. Подготовил набор базовых параметров для обратного построения справочника';
            $warnings[] = "actionSaveEquipmentOnScheme. ИД Типового объекта: $object_id";
            $warnings[] = "actionSaveEquipmentOnScheme. Название типового объекта: $object_type_title";
            $warnings[] = "actionSaveEquipmentOnScheme. Тип типового объекта: $object_type_id";
            $warnings[] = "actionSaveEquipmentOnScheme. Вид типового объекта: $object_kind_id";

            $parameter_type_id = 2;

            /**
             * инициализируем дату
             */
            $date_now = \backend\controllers\Assistant::GetDateNow();

            /**
             * Записываем местораcположение сенсора в БД
             */

            $response = EquipmentMainController::getOrSetEquipmentParameter($equipment_id, 122, $parameter_type_id);
            if ($response['status'] == 1) {
                $equipment_parameter_id = $response['equipment_parameter_id'];
                $warnings[] = $response['warnings'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("actionSaveEquipmentOnScheme. Ошибка получения или сохранение параметра 122 сенсора $equipment_id");
            }

            $response = EquipmentBasicController::addEquipmentParameterValue($equipment_parameter_id, $place_id, 1, $date_now);

            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("actionSaveEquipmentOnScheme. Ошибка сохранения Значения: $place_id параметра 122 сенсора: $equipment_id");
            }
            // создаем массив для вставки разовой в кеш
            $equipment_parameter_value_to_caches[] = \backend\controllers\cachemanagers\EquipmentCacheController::buildStructureEquipmentParametersValue(
                $equipment_id, $equipment_parameter_id, 122, $parameter_type_id,
                $date_now, $place_id, 1);

            /**
             * Записываем ветвь оборудования в БД
             */
            $response = EquipmentMainController::GetOrSetEquipmentParameter($equipment_id, 269, $parameter_type_id);
            if ($response['status'] == 1) {
                $equipment_parameter_id = $response['equipment_parameter_id'];
                $warnings[] = $response['warnings'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("actionSaveEquipmentOnScheme. Ошибка получения или сохранение параметра 269 сенсора $equipment_id");
            }

            $response = EquipmentBasicController::addEquipmentParameterValue($equipment_parameter_id, $edge_id, 1, $date_now);

            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("actionSaveEquipmentOnScheme. Ошибка сохранения Значения: $edge_id параметра 269 оборудования: $equipment_id");
            }
            // создаем массив для вставки разовой в кеш
            $equipment_parameter_value_to_caches[] = \backend\controllers\cachemanagers\EquipmentCacheController::buildStructureEquipmentParametersValue(
                $equipment_id, $equipment_parameter_id, 269, $parameter_type_id,
                $date_now, $edge_id, 1);

            /**
             * Записываем координату оборудования в БД
             */
            $response = EquipmentMainController::GetOrSetEquipmentParameter($equipment_id, 83, $parameter_type_id);
            if ($response['status'] == 1) {
                $equipment_parameter_id = $response['equipment_parameter_id'];
                $warnings[] = $response['warnings'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("actionSaveEquipmentOnScheme. Ошибка получения или сохранение параметра 83 сенсора $equipment_id");
            }

            $response = EquipmentBasicController::addEquipmentParameterValue($equipment_parameter_id, $XYZ, 1, $date_now);

            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("actionSaveEquipmentOnScheme. Ошибка сохранения Значения: $XYZ параметра 83 сенсора: $equipment_id");
            }
            // создаем массив для вставки разовой в кеш
            $equipment_parameter_value_to_caches[] = \backend\controllers\cachemanagers\EquipmentCacheController::buildStructureEquipmentParametersValue(
                $equipment_id, $equipment_parameter_id, 83, $parameter_type_id,
                $date_now, $XYZ, 1);

            /**
             * блок переноса оборудования в новую шахту если таковое требуется
             * если шахта есть, то делаем перенос или инициализацию, в зависимости от описанного выше
             */

            $equipment_to_cache = \backend\controllers\cachemanagers\EquipmentCacheController::buildStructureEquipment($equipment_id, $equipment_title, $object_id, $object_title, $object_type_id, $mine_id);
            $response = EquipmentMainController::AddMoveEquipmentMineInitDB($equipment_to_cache);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $warnings[] = 'actionSaveEquipmentOnScheme. обновил главный кеш оборудования';
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('actionSaveEquipmentOnScheme. Не смог обновить главный кеш оборудования' . $equipment_id);
            }
            /**
             * Записываем шахту в БД
             */
            $response = EquipmentMainController::GetOrSetEquipmentParameter($equipment_id, 346, $parameter_type_id);
            if ($response['status'] == 1) {
                $equipment_parameter_id = $response['equipment_parameter_id'];
                $warnings[] = $response['warnings'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("actionSaveEquipmentOnScheme. Ошибка получения или сохранение параметра 346 оборудования $equipment_id");
            }

            $response = EquipmentBasicController::addEquipmentParameterValue($equipment_parameter_id, $mine_id, 1, $date_now);

            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("actionSaveEquipmentOnScheme. Ошибка сохранения Значения: $mine_id параметра 346 оборудования: $equipment_id");
            }
            // создаем массив для вставки разовой в кеш
            $equipment_parameter_value_to_caches[] = \backend\controllers\cachemanagers\EquipmentCacheController::buildStructureEquipmentParametersValue(
                $equipment_id, $equipment_parameter_id, 346, $parameter_type_id,
                $date_now, $mine_id, 1);

            /**
             * обновление параметров оборудования в кеше
             */
            $response = (new \backend\controllers\cachemanagers\EquipmentCacheController)->multiSetEquipmentParameterValue($equipment_id, $equipment_parameter_value_to_caches);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $warnings[] = 'actionSaveEquipmentOnScheme. обновил параметры оборудования в кеше';
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('actionSaveEquipmentOnScheme. Не смог обновить параметры в кеше оборудования' . $equipment_id);
            }
            unset($equipment_parameter_value_to_caches);
            /*
                        if ($object_type_id == 22) {
                            $response = (new CoordinateController())->updateSensorGraph($equipment_id, $mine_id);  //метод обновления графа для оборудования
                            if ($response['status'] == 1) {
                                $warnings[] = $response['warnings'];
                                $warnings[] = "actionSaveSpecificParametersValuesBase. обновил граф оборудования в кеше";
                            } else {
                                $warnings[] = $response['warnings'];
                                $errors[] = $response['errors'];
                                throw new \Exception("actionSaveSpecificParametersValuesBase.ошибка обновления графа оборудования в кеше" . $equipment_id);
                            }
                        }
            */
        } catch (Throwable $e) {
            $status = 0;
            $equipment_mine_id = null;
            $errors[] = 'actionSaveEquipmentOnScheme.Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'actionSaveEquipmentOnScheme. Вышел из метода';
        $result_main = array('response' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
        //return json_encode($result_main);
    }

    /**
     * actionDeleteEquipmentFromMine - Метод удаления оборудования со схемы шахты
     * Название метода: actionDeleteEquipmentFromMine()
     * @package app\controllers\
     * Входные обязательные параметры:
     * $post['equipment_id'] - идентификатор оборудования
     *
     * @url http://localhost/unity/delete-equipment-from-mine
     * @url http://localhost/unity/delete-equipment-from-mine?equipment_id=15102
     * Created date: on 16.01.2019 9:40
     * @since ver0.2
     */
    public function actionDeleteEquipmentFromMine()
    {
        $errors = array();                                                                                              //массив ошибок
        $status = 1;                                                                                                    //состояние выполнения метода
        $result = null;
        $warnings = array();

        try {
            $warnings[] = 'actionDeleteEquipmentFromMine. Начало выполнения метода';
            $session = Yii::$app->session;                                                                              //старт сессии
            //$session->open();                                                                                         //открыть сессию
            if (isset($session['sessionLogin'])) {                                                                      //если в сессии есть логин
                $warnings[] = 'actionDeleteEquipmentFromMine. Сессия есть';
            } else {
                throw new Exception('actionDeleteEquipmentFromMine. Время сессии закончилось. Требуется повторный ввод пароля');
            }
            if (AccessCheck::checkAccess($session['sessionLogin'], 1000)) {                                     //если пользователю разрешен доступ к функции access_id=1000 (Удаление оборудования со схемы 3Д)
                $warnings[] = 'actionDeleteEquipmentFromMine. Прав достаточно';
            } else {
                throw new Exception('actionDeleteEquipmentFromMine. Недостаточно прав для совершения данной операции');
            }

            $post = Assistant::GetServerMethod();                                                                       //получаем данные методом POST
            if (!isset($post['equipment_id']) or !isset ($post['mine_id'])) {                                           //если данные не получены
                throw new Exception('actionDeleteEquipmentFromMine. Отсутствуют входные параметры');
            }

            $equipment_id = $post['equipment_id'];
            $mine_id = $post['mine_id'];

            $equipmentParameter = EquipmentParameter::findOne(['equipment_id' => $equipment_id, 'parameter_id' => 122, 'parameter_type_id' => 2]); //находим параметр требуемого датчика, соответствующий местоположению (местоположение)
            if ($equipmentParameter) {
                $response = SpecificEquipmentController::AddEquipmentParameterValue($equipmentParameter->id, -1, 1, 1);
                if ($response['status'] != 1) {
                    $errors = $response['errors'];
                    $warnings = $response['warnings'];
                    throw new Exception('actionDeleteEquipmentFromMine. Произошла ошибка при удалении местоположения (place)');
                }
            }

            $equipmentParameter = EquipmentParameter::findOne(['equipment_id' => $equipment_id, 'parameter_id' => 83, 'parameter_type_id' => 2]);   //находим параметр требуемого датчика, соответствующий местоположению (местоположение)
            if ($equipmentParameter) {
                $response = SpecificEquipmentController::AddEquipmentParameterValue($equipmentParameter->id, -1, 1, 1);
                if ($response['status'] != 1) {
                    $errors = $response['errors'];
                    $warnings = $response['warnings'];
                    throw new Exception('actionDeleteEquipmentFromMine. Произошла ошибка при удалении координат');
                }
            }

            $equipmentParameter = EquipmentParameter::findOne(['equipment_id' => $equipment_id, 'parameter_id' => 269, 'parameter_type_id' => 2]);     //находим параметр требуемого датчика, соответствующий местоположению (местоположение)
            if ($equipmentParameter) {
                $response = SpecificEquipmentController::AddEquipmentParameterValue($equipmentParameter->id, -1, 1, 1);
                if ($response['status'] != 1) {
                    $errors = $response['errors'];
                    $warnings = $response['warnings'];
                    throw new Exception('actionDeleteEquipmentFromMine. Произошла ошибка при удалении ветви / ребра');
                }
            }

            $equipmentParameter = EquipmentParameter::findOne(['equipment_id' => $equipment_id, 'parameter_id' => 346, 'parameter_type_id' => 2]);      //находим параметр требуемого датчика, соответствующий местоположению (местоположение)
            if ($equipmentParameter) {
                $response = SpecificEquipmentController::AddEquipmentParameterValue($equipmentParameter->id, -1, 1, 1);
                if ($response['status'] != 1) {
                    $errors = $response['errors'];
                    $warnings = $response['warnings'];
                    throw new Exception('actionDeleteEquipmentFromMine. Произошла ошибка при удалении шахтного поля (Mine)');
                }
            }

            $equipment_cache_controller = new \backend\controllers\cachemanagers\EquipmentCacheController();
            $equipment_cache_controller->delInEquipmentMine($equipment_id, $mine_id);
            $equipment_cache_controller->delParameterValue($equipment_id);

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionDeleteEquipmentFromMine. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'actionDeleteEquipmentFromMine. Закончил выполнение метода';

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * getLayersUnity - Функция получения списка слоев Unity на основе списка типовых объектов
     * Входные параметры отсутствуют
     * Выходные параметры:
     *          objectsKinds
     *             {}
     *                 layer_id            - (int)      – id вида объекта;
     *                 layer_title         - (string)   – наименование слоя
     *                 isRoot              - (boolean)  – родительский элемент (корневой)
     *                 parendID            - (int)      – id родителя
     *                 system_id           - (int)      – id системный
     *                 sequence_number     - (int)      – порядок сортировки
     *                 objectTypesInGroupe - (array)    — массив типовых объектов в типе объектов;
     *                      []
     *                         “layer_id” (int) – id типового объекта;
     * 127.0.0.1/unity/get-layers-unity
     * http://127.0.0.1/read-manager-amicum?controller=positioningsystem\Unity&method=actionGetLayersUnity&subscribe=&data={}
     */
    public static function actionGetLayersUnity($data_post = null)
    {

        $log = new LogAmicumFront("getTypicalObjectArray");
        $status = 1;
        try {
            // получаем список типовых объектов по видам и типам
            $typical_objects = (new Query())
                ->select(
                    '
                kind_object.id as kind_object_id,
                kind_object.title as kind_object_title,
                object.id as object_id,
                object.title as object_title,
                '
                )
                ->from('object')
                ->innerJoin('object_type', 'object.object_type_id=object_type.id')
                ->innerJoin('kind_object', 'object_type.kind_object_id=kind_object.id')
                ->all();

            // получаем список шаблонов для типовых объектов
            foreach ($typical_objects as $typical_object) {
                $layer_id = (int)$typical_object['kind_object_id'] * (-1);

                $objectsKinds[$layer_id]['layer_title'] = $typical_object['kind_object_title'];
                $objectsKinds[$layer_id]['layer_id'] = $layer_id;
                $objectsKinds[$layer_id]['parendID'] = 0;
                $objectsKinds[$layer_id]['isRoot'] = true;
                $objectsKinds[$layer_id]['system_id'] = 0;
                $objectsKinds[$layer_id]['sequence_number'] = 0;
                $objectsKinds[$layer_id]['objectTypesInGroupe'][] = (int)$typical_object['object_id'];

                $object_id = (int)$typical_object['object_id'];
                $objectsKinds[$object_id]['layer_title'] = $typical_object['object_title'];
                $objectsKinds[$object_id]['layer_id'] = (int)$typical_object['object_id'];
                $objectsKinds[$object_id]['parendID'] = $layer_id;
                $objectsKinds[$object_id]['isRoot'] = false;
                $objectsKinds[$object_id]['system_id'] = $layer_id;
                $objectsKinds[$object_id]['sequence_number'] = 0;
                $objectsKinds[$object_id]['objectTypesInGroupe'][] = (int)$typical_object['object_id'];

            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
            $status = 0;
        }
        if (!isset($objectsKinds)) {
            $objectsKinds = (object)array();
        }

//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = array('Items' => $objectsKinds, 'status' => $status, 'errors' => [], 'warnings' => []);
        return array('Items' => $objectsKinds, 'status' => $status, 'errors' => [], 'warnings' => []);
    }

    /**
     * getEmployeeCountByPlace - Метод подсчета количества людей, находящихся в шахте, либо на поверхности,  а также разделяет по компаниям, Используется на 3D схеме.
     * в 18 параметре 1 - Заполярная, 2 - Воркутинская -  данная часть хранится в справочнике group_alarm
     * @param $mine_id
     * @return array
     */
    public static function getEmployeeCountByPlace($mine_id, $workers_lists)
    {
        $log = new LogAmicumFront("getEmployeeCountByPlace");

        $flag = false;
        $result_worker_count = array();

        // Счетчики кол-ва людей
        $worker_count_on_shift = 0;                                                                                     // кол-ва работников в смене
        $workers_count_guests = 0;                                                                                      // количество прочих работников в целом по предприятию

        $worker_count_in_mine_first_company = 0;                                                                        // кол-ва работников в шахте
        $worker_count_on_surface_first_company = 0;                                                                     // кол-ва работников на поверхности
        $worker_count_lamp_first_company = 0;                                                                           // количество работников в ламповой
        $errors_count_first_company = 0;                                                                                // ошибочные значения 358 параметра

        $worker_count_in_mine_second_company = 0;                                                                       // кол-ва работников в шахте
        $worker_count_on_surface_second_company = 0;                                                                    // кол-ва работников на поверхности
        $worker_count_lamp_second_company = 0;                                                                          // количество работников в ламповой
        $errors_count_second_company = 0;                                                                               // ошибочные значения 358 параметра
        try {
            $log->addLog("Начал выполнять метод");
            if ($mine_id == 290) {
                $flag = true;
                $log->addLog("Заполярная-2 - делим людей на пополам");
            }
            /**
             * Проверяем список worker для анализа количества людей в шахте
             */
            if (!$workers_lists) {
                throw new Exception('getEmployeeCount. Список работников пуст');
            }

            $log->addLog("Есть работники для анализа");

            /**
             * Получение значений параметра 358/2 для всех worker
             */
            if (!COD) {
                $workers_parameter_122_lists = (new WorkerCacheController())->multiGetParameterValueHash('*', 122, 2);
                $log->addLog("У нас шахта - берем место из кеша");
            } else {
                $workers_parameter_122_lists = WorkerBasicController::getWorkerParameterValue('*', 122, 2);
                $log->addLog("У нас цод - берем место из БД");
            }
            if ($workers_parameter_122_lists === false) {
                $log->addLog("Кеша 122/2 отсутствует");
            }

            foreach ($workers_parameter_122_lists as $workers_parameter_list) {
                $workers_parameter_122_array[$workers_parameter_list['worker_id']] = $workers_parameter_list;
            }
            unset($workers_parameter_122_lists);
            $log->addLog("Сделал справочник из параметров работника");

            if ($flag) {
                /**
                 * Получение значений параметра 18/ для всех worker
                 */
                if (!COD) {
                    $workers_parameter_18_lists = (new WorkerCacheController())->multiGetParameterValueHash('*', 18, 1);
                } else {
                    $workers_parameter_18_lists = WorkerBasicController::getWorkerParameterHandbookValue('*', 18);
                }
                if ($workers_parameter_18_lists === false) {
                    $log->addLog("В кеше не нашел разделение на предприятия 18/1");
                }
                foreach ($workers_parameter_18_lists as $workers_parameter_list) {
                    $workers_parameter_18_array[$workers_parameter_list['worker_id']] = $workers_parameter_list;
                }
                unset($workers_parameter_18_lists);
            }

            $kind_places = Place::find()
                ->select([
                    'place.id as place_id',
                    'object.id as object_id',
                    'object_type.kind_object_id as kind_object_id'
                ])
                ->innerJoin('object', 'place.object_id = object.id')
                ->innerJoin('object_type', 'object_type.id = object.object_type_id')
                ->andFilterWhere(['object_type.kind_object_id' => [2, 6]])
                ->groupBy(['place_id', 'kind_object_id'])
                ->asArray()
                ->all();

            $kind_places_hand = null;
            foreach ($kind_places as $kind_place) {
                $kind_places_hand[$kind_place['place_id']] = $kind_place;
            }

            $log->addLog("Сделал справочник видов мест");

            /**
             * Получаем список 358 праметров для работников
             */
            foreach ($workers_lists as $workers_list) {
                if ($workers_list['mine_id'] == $mine_id) {
                    if (isset($workers_parameter_122_array[$workers_list['worker_id']])) {
                        $worker_count_on_shift++;
                        $place_id = $workers_parameter_122_array[$workers_list['worker_id']]['value'];
                        $kind_object = 0;
                        $object_id = 0;
                        if (isset($kind_places_hand[$place_id])) {
                            $kind_object = $kind_places_hand[$place_id]['kind_object_id'];
                            $object_id = $kind_places_hand[$place_id]['object_id'];
                        }
                        $log->addLog($place_id . " " . $kind_object);
                        switch ($kind_object) {
                            case 2:// горные выработки
                                if ($flag) {
                                    if (isset($workers_parameter_18_array[$workers_list['worker_id']]) and $workers_parameter_18_array[$workers_list['worker_id']]['value'] == 1) {
                                        $worker_count_in_mine_first_company++;
                                    } else if (isset($workers_parameter_18_array[$workers_list['worker_id']]) and $workers_parameter_18_array[$workers_list['worker_id']]['value'] == 2) {
                                        $worker_count_in_mine_second_company++;
                                    } else {
                                        $worker_count_in_mine_first_company++;
                                    }
                                } else {
                                    $worker_count_in_mine_first_company++;
                                }

                                break;
                            case 6: // поверхность
                                if ($flag) {
                                    if (isset($workers_parameter_18_array[$workers_list['worker_id']]) and $workers_parameter_18_array[$workers_list['worker_id']]['value'] == 1) {
                                        if ($object_id != 80) {
                                            $worker_count_on_surface_first_company++;
                                        } else {
                                            $worker_count_lamp_first_company++;
                                        }
                                    } else if (isset($workers_parameter_18_array[$workers_list['worker_id']]) and $workers_parameter_18_array[$workers_list['worker_id']]['value'] == 2) {
                                        if ($object_id != 80) {
                                            $worker_count_on_surface_second_company++;
                                        } else {
                                            $worker_count_lamp_second_company++;
                                        }
                                    } else {
                                        $worker_count_on_surface_first_company++;
                                    }
                                } else {
                                    if ($object_id != 80) {
                                        $worker_count_on_surface_first_company++;
                                    } else {
                                        $worker_count_lamp_first_company++;
                                    }
                                }
                                break;
                            default:
                                if ($flag) {
                                    if (isset($workers_parameter_18_array[$workers_list['worker_id']]) and $workers_parameter_18_array[$workers_list['worker_id']]['value'] == 1) {
                                        $errors_count_first_company++;
                                    } else if (isset($workers_parameter_18_array[$workers_list['worker_id']]) and $workers_parameter_18_array[$workers_list['worker_id']]['value'] == 2) {
                                        $errors_count_second_company++;
                                    } else {
                                        $errors_count_first_company++;
                                    }
                                } else {
                                    $errors_count_first_company++;
                                }
                                break;
                        }
                    } else {
                        $errors_count_first_company++;
                        $log->addError('getEmployeeCount. У работника ' . $workers_list['worker_id'] . ' не заполнен 358 параметр', __LINE__);
                    }
                }
            }
            if ($flag) {
                $result_worker_count[] = array(
                    'mine_title' => '1',
                    'worker_count_on_shift' => $worker_count_on_shift,
                    'worker_count_in_mine' => $worker_count_in_mine_first_company,
                    'worker_count_on_surface' => $worker_count_on_surface_first_company,
                    'workers_count_guests' => $errors_count_first_company,
                    'worker_count_lamp' => $worker_count_lamp_first_company,
                    'errors_count_company' => $errors_count_first_company,
                );

                $result_worker_count[] = array(
                    'mine_title' => '2',
                    'worker_count_on_shift' => $worker_count_on_shift,
                    'worker_count_in_mine' => $worker_count_in_mine_second_company,
                    'worker_count_on_surface' => $worker_count_on_surface_second_company,
                    'workers_count_guests' => $errors_count_second_company,
                    'worker_count_lamp' => $worker_count_lamp_second_company,
                    'errors_count_company' => 0,
                );
            } else {
                $result_worker_count[] = array(
                    'mine_title' => '0',
                    'worker_count_on_shift' => $worker_count_on_shift,
                    'worker_count_in_mine' => $worker_count_in_mine_first_company,
                    'worker_count_on_surface' => $worker_count_on_surface_first_company,
                    'workers_count_guests' => $errors_count_first_company,
                    'worker_count_lamp' => $worker_count_lamp_first_company,
                    'errors_count_company' => $errors_count_first_company,
                );
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result_worker_count, 'employee_count' => $result_worker_count,], $log->getLogAll());
    }
}
