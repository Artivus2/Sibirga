<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\positioningsystem;
//ob_start();

use backend\controllers\Assistant as BackAssistant;
use backend\controllers\cachemanagers\EdgeCacheController;
use backend\controllers\const_amicum\ParamEnum;
use backend\controllers\const_amicum\ParameterTypeEnumController;
use backend\controllers\const_amicum\ShapeEdgeEnumController;
use backend\controllers\const_amicum\StatusEnumController;
use backend\controllers\const_amicum\TypeShieldEnumController;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\AccessCheck;
use frontend\models\Asmtp;
use frontend\models\Edge;
use frontend\models\EdgeFunction;
use frontend\models\EdgeParameter;
use frontend\models\EdgeParameterHandbookValue;
use frontend\models\EdgeParameterValue;
use frontend\models\EdgeStatus;
use frontend\models\KindParameter;
use frontend\models\Main;
use frontend\models\ObjectType;
use frontend\models\Place;
use frontend\models\SensorType;
use frontend\models\TypicalObject;
use Throwable;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\Response;

class SpecificEdgeController extends SpecificObjectController
{
    // addEdge          - Метод создания ветви и его параметров

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод добавления параметров для Edge
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 23.10.2018 11:20
     */
    public function actionAddEdgeParameter()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();

        $new_edge_parameter_id = -1;
        $array_of_parameters_list = array();

        $warnings[] = 'actionAddEdgeParameter. Начало метода';
        try {
            $post = Assistant::GetServerMethod();

            $argument_are_valid = isset($post['id'], $post['parameter_id'], $post['parameter_type_id']) &&
                $post['id'] != '' && $post['parameter_id'] != '' && $post['parameter_type_id'] != '';

            if ($argument_are_valid) {
                $edge_id = (int)$post['id'];
                $parameter_id = (int)$post['parameter_id'];
                $parameter_type_id = (int)$post['parameter_type_id'];
                $new_edge_parameter_id = ObjectFunctions::AddObjectParameter('edge_parameter', $edge_id, 'edge_id', $parameter_id, $parameter_type_id);
                if ($new_edge_parameter_id == -1) {
                    $errors[] = "Ошибка добавление параметра $parameter_id";
                } else {
                    $array_of_parameters_list = $this->buildSpecificParameterArray($edge_id, 'edge');                       // получаем список оборудования
                }
            } else {
                $errors[] = 'Некоторые параметры были пустыми или не были получены';
            }
        } catch (\Throwable $exception) {
            $status = 0;
            $errors[] = 'actionAddEdgeParameter. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'actionAddEdgeParameter. Конец метода';

        $result = array('Items' => $result, 'status' => $status,
            'warnings' => $warnings, 'errors' => $errors,
            'edge_parameter_id' => (int)$new_edge_parameter_id, 'paramArray' => $array_of_parameters_list);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: actionDeleteSpecificParameter()
     * @package app\controllers
     * Метод удаления параметров конкретной выработки.
     * С помощью этого метода можно удалить только один тип параметра(все типовые параметры), и сам параметр.
     * При удалении данные удаляются по умолчанию из кэша с помощью очереди.
     *
     * Входные обязательные параметры:
     * $post['action_type'] - тип действия. Если local - то удаление только значений типа параметра
     *    Если 'global'  - удаление параметра со всеми значениями и самого параметра
     * ['specific_object_id'] - идентификатор сенсора.
     * Входные необязательные параметры
     * $post['specific_parameter_id'] - идентификатор sensor_parameter_id
     * $post['parameter_id'] - идентификатор параметра
     *
     * @url http://localhost/specific-edge/delete-specific-parameter
     * @url http://localhost/specific-edge/delete-specific-parameter?table_name=edge&parameter_id=&action_type=local&specific_parameter_id=32308&specific_object_id=15299
     *
     * Документация на портале:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 17.01.2019 15:54
     */
    public function actionDeleteSpecificParameter()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();

        $paramsArray = array();

        $warnings[] = 'actionDeleteSpecificParameter. Начало метода';
        try {
            $session = Yii::$app->session;
            $session->open();
            if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
                if (AccessCheck::checkAccess($session['sessionLogin'], 91)) {                                        //если пользователю разрешен доступ к функции
                    $post = Assistant::GetServerMethod();                                                                            // переменная для получения запросов от ajax-запроса
                    if (isset($post['action_type']) && $post['action_type'] != '' &&
                        isset($post['specific_object_id']) && $post['specific_object_id'] != '') {

                        $actionType = $post['action_type'];
                        $specificObjectId = $post['specific_object_id'];
                        /******** Удаление одного типа параметра со всеми значениями у конкретного параметра сенсора *******/
                        if ($actionType === 'local') {
                            if (isset($post['specific_parameter_id']) && $post['specific_parameter_id'] !== '') {
                                $specificParameterId = $post['specific_parameter_id'];
                                $edge_parameter = EdgeParameter::findOne($specificParameterId);
                                /***** Удаляем значений конкретного тип параметра+параметра выработки из кэша *************/
                                /**
                                 * Для того, чтобы удалить из кэша, необходимо получить параметр и тип параметра выработки
                                 */
                                if ($edge_parameter) {
                                    $parameter_id = $edge_parameter->parameter_id;
                                    $parameter_type_id = $edge_parameter->parameter_type_id;

                                    $flag_done = (new EdgeCacheController())->delParameterValue($specificObjectId, $parameter_id, $parameter_type_id);
                                    if ($flag_done) {
                                        $warnings[] = 'actionDeleteSpecificParameter. Параметры выработки удалены из кеша схемы шахты';
                                    } else {
                                        $warnings[] = 'В кэше нет данных по параметру';
                                    }
                                }
                                /***** Удаляем значений конкретного тип параметра+параметра выработки из БД *************/
                                EdgeParameterHandbookValue::deleteAll(['edge_parameter_id' => $specificParameterId]);
                                EdgeParameterValue::deleteAll(['edge_parameter_id' => $specificParameterId]);
                                EdgeParameter::deleteAll(['id' => $specificParameterId]);
                            } else {
                                $errors[] = 'Не передан edge_parameter_id';
                            }
                        } /******* Удаление параметра со всеми значениями, со всеми типами параметров конкретного сенсора****/
                        elseif (isset($post['parameter_id']) && $post['parameter_id'] !== '') {
                            $parameterId = $post['parameter_id'];

                            $flag_done = (new EdgeCacheController())->delParameterValue($specificObjectId, $parameterId);
                            if ($flag_done) {
                                $warnings[] = 'actionDeleteSpecificParameter. Параметры выработки удалены из кеша схемы шахты';
                            } else {
                                $warnings[] = 'В кэше нет данных по параметру';
                            }

                            $edge_parameters = EdgeParameter::find()->where(['parameter_id' => $parameterId, 'edge_id' => $specificObjectId])->all();
                            foreach ($edge_parameters as $edge_parameter) {
                                EdgeParameterHandbookValue::deleteAll(['edge_parameter_id' => $edge_parameter->id]);
                                EdgeParameterValue::deleteAll(['edge_parameter_id' => $edge_parameter->id]);
                            }
                            EdgeParameter::deleteAll(['parameter_id' => $parameterId, 'edge_id' => $specificObjectId]);
                        } else {
                            $errors[] = 'не передан parameter_id';
                        }
                        $paramsArray = $this->buildSpecificParameterArray($specificObjectId, 'edge');
                    } else {
                        $errors[] = 'не передан action_type или specific_object_id';
                    }
                } else {
                    $errors[] = 'Недостаточно прав для совершения данной операции';
                }
            } else {
                $errors[] = 'Время сессии закончилось. Требуется повторный ввод пароля';
                $this->redirect('/');
            }
        } catch (\Throwable $exception) {
            $status = 0;
            $errors[] = 'actionDeleteSpecificParameter. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'actionDeleteSpecificParameter. Конец метода';

        $result = array(
            'Items' => $result, 'status' => $status,
            'warnings' => $warnings, 'errors' => $errors,
            'paramArray' => $paramsArray
        );
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    //метод GET!!!!
    //метод добавляет заданный параметр в параметры конкретного объекта а в случае если задано значение то устанавливает его
    //метод работает только на справочные значени
    //http://127.0.0.1/specific-edge/add-current-parameters-to-edge-with-value-get?parameter_id=263&value=1 - метан уставка
    //http://127.0.0.1/specific-edge/add-current-parameters-to-edge-with-value-get?parameter_id=264&value=17 - СО уставка
    public function actionAddCurrentParametersToEdgeWithValueGet()
    {
        $post = Yii::$app->request->post();
        $flag_param = 0;                                                                                                  //флаг наличия парамтера
        $flag_value = 0;                                                                                                  //флаг наличия значения
        if (isset($post['parameter_id']) && $post['parameter_id'] != "") {                                                 //проверяем входящие данные со стороны фронта в данном случае параметр айди
            $parameter_id = $post['parameter_id'];                                                                      //объявляем ключ параметра из фронт энда
            $flag_param = 1;
        }
        if (isset($post['value']) && $post['value'] != "") {
            $handbook_value = $post['value'];                                                                          //объявляем значение устанавливаемое этому параметру
            $flag_value = 1;
        }
        $errors = array();                                                                                                //объявляем пустой массив ошибок
        $flag_done = 0;                                                                                                   //флаг успешного выполнения метода
        //копирование параметров справочных
        $edges = (new Query())                                                                                         //получаем все плейсы на шахте
        ->select(
            ['id'])
            ->from(['edge'])
            ->all();

        if ($edges and $flag_param == 1)                                                                               //если плэйс существует, то делаем выборку
        {
            foreach ($edges as $edge) {
                //создаем новый параметр у конкретного места который получили с фронт энд стороны
                $edge_parameter_id = $this->AddEdgeParameter($edge['id'], $parameter_id, 1);
                if ($edge_parameter_id and isset($handbook_value)) {                                                       //если плэейс параметр айди создан и существует, то записываем его значение
                    $flag_done = $this->AddEdgeParameterHandbookValue($edge_parameter_id, $handbook_value, 1, 1);
                } else {
                    if (!$edge_parameter_id) $errors[] = "Параметер " . $parameter_id . " для плейс айди: " . $edge['id'] . " не создан";
                }
            }
        } else {
            if (!$edges) $errors[] = "Плейсев  в базе данных нет. Параметр и значения не установлены";
            if ($flag_param == 0) $errors[] = "Параметр не задан на стороне фронта";
        }
        $result = array('Состояние добавления: ' => $flag_done, 'количество обработанных плейсов ' => count($edges), 'errors' => $errors);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                                   //формат возвращаемых данных json методом yii2
        Yii::$app->response->data = $result;
    }

    //создание параметра конкретного эджа
    private function AddEdgeParameter($edge_id, $parameter_id, $parameter_type_id)
    {
        $debug_flag = 0;

        if ($debug_flag == 1) echo nl2br("----зашел в функцию создания параметров места  =" . $edge_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        if ($edge_parameter = EdgeParameter::find()->where(['edge_id' => $edge_id, 'parameter_id' => $parameter_id, 'parameter_type_id' => $parameter_type_id])->one()) {
            return $edge_parameter->id;
        } else {
            $edge_parameter_new = new EdgeParameter();
            $edge_parameter_new->edge_id = $edge_id;                                                                 //айди эджа
            $edge_parameter_new->parameter_id = $parameter_id;                                                         //айди параметра
            $edge_parameter_new->parameter_type_id = $parameter_type_id;                                               //айди типа параметра

            if ($edge_parameter_new->save()) return $edge_parameter_new->id;
            else return (-1); //"Ошибка сохранения значения параметра эджа" . $place_id->id;
        }
    }

    /**
     * Метод добавления значений для параметра ветки
     */
    public function actionAddEdgeParameterHandbookValue()
    {
        $post = \Yii::$app->request->post();                                                                            // переменная для получения запросов от ajax-запроса
        $errors = array();                                                                                              // массив ошибок
        $array_of_parameters_list = array();                                                                            // массив для получения списка параметров
        if (isset($post['params_array']) && $post['params_array'] != "" &&                                                // Если был получен запрос и массив не пустой
            isset($post['specific_object_id']) && $post['specific_object_id'] != "") {
            $specific_object_id = $post['specific_object_id'];
            $array_of_parameters = $post['params_array'];                                                               // сохраним в новую переменную массив полученного от ajax-запроса
            foreach ($array_of_parameters as $parameter) {
                $edge_parameter_id = $parameter['edge_parameter_id'];
                $value = $parameter['value'];
                $edge_param_handbook_id = ObjectFunctions::AddObjectParameterHandbookValue("edge", $edge_parameter_id, 1, $value, 1);
                if ($edge_param_handbook_id == -1) {
                    $errors[] = "Ошибка добавления значений для параметров выработки = $specific_object_id";
                    $errors['insert-error-result'] = $edge_param_handbook_id;
                }
            }
            $array_of_parameters_list = parent::buildSpecificParameterArray($specific_object_id, "edge");                          // получаем список параметров
        } else                                                                                                            // если не был получен запрос и параметры были пустыми
        {
            $errors[] = "Некоторые параметры были пустыми или не были получены";                                        // выводим ошибку
        }
        $result = array("errors" => $errors, 'parameter_list' => $array_of_parameters_list);
        Yii::$app->response->format = Response::FORMAT_JSON; // формат json
        Yii::$app->response->data = $result; // отправляем обратно ввиде FORMAT_JSON
    }

    /**
     * Метод добавления функций для объекта
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 23.10.2018 15:18
     */
    public function actionAddEdgeFunction()
    {
        $post = Yii::$app->request->post();
        $errors = array();
        $functionsArray = array();
        $new_function_id = 0;
        if (isset($post['specificObjectId']) && $post['specificObjectId'] != "" &&
            isset($post['functionId']) && $post['functionId'] != "") {
            $specific_object_id = $post['specificObjectId'];
            $function_id = $post['functionId'];
            $new_function_id = ObjectFunctions::AddObjectFunction('edge', $specific_object_id, $function_id);
            if ($new_function_id == -1) {
                $errors[] = "Ошибка добавления функции для edge_id = $specific_object_id";
            } else {
                $functionsArray = parent::buildSpecificFunctionArray($specific_object_id, "edge");
            }
        } else {
            $errors[] = 'Некоторые параметры не были переданы';
        }
        $result = array('objectFunctions' => $functionsArray, 'errors' => $errors, 'debug_info' => $post);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //формат возвращаемых данных json методом yii2
        Yii::$app->response->data = $result;
    }

    /**
     * Метод удаления функции объекта
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 23.10.2018 15:53
     */
    public function actionDeleteEdgeFunction()
    {
        $post = Yii::$app->request->post();
        $errors = array();
        $object_functions = array();
        $debug_info = array();
        if (isset($post['specific_object_id']) and $post['specific_object_id'] != "" and
            isset($post['specific_function_id']) and $post['specific_function_id'] != "") {
            $edge_id = $post['specific_object_id'];
            $function_id = $post['specific_function_id'];
//            $delete_result = ObjectFunctions::DeleteFromTable('edge_function', "edge_id = $edge_id and function_id = $function_id");
//            $debug_info['delete-result'] = $delete_result;

            EdgeFunction::deleteAll('id=:edge_function_id', [':edge_function_id' => $function_id]);
            $objects = (new Query())
                ->select(
                    [
                        'function_type_title functionTypeTitle',
                        'function_type_id functionTypeId',
                        'edge_function_id id',
                        'function_id',
                        'func_title functionTitle',
                        'func_script_name scriptName'
                    ])
                ->from(['view_edge_function'])
                ->where('edge_id = ' . $edge_id)
                ->orderBy('function_type_id')
                ->all();

            $i = -1;
            $j = 0;

            foreach ($objects as $object) {
                if ($i === -1 || $object_functions[$i]['id'] !== $object['functionTypeId']) {
                    $i++;
                    $object_functions[$i]['id'] = $object['functionTypeId'];
                    $object_functions[$i]['title'] = $object['functionTypeTitle'];
                    $j = 0;
                }
                $object_functions[$i]['funcs'][$j]['id'] = $object['id'];
                $object_functions[$i]['funcs'][$j]['title'] = $object['functionTitle'];
                $object_functions[$i]['funcs'][$j]['script_name'] = $object['scriptName'];
                $j++;

            }
        } else {
            $errors[] = 'Некоторые параметры не были переданы';
            $debug_info['post'] = $post;
        }
        $result = array('errors' => $errors, 'debug_info' => $debug_info, 'objectFunctions' => $object_functions);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //формат возвращаемых данных json методом yii2
        Yii::$app->response->data = $result;
    }

    /**
     * Метод сохранения значений параметров выработки
     * Created by: Одилов О.У. on 23.10.2018 16:44
     */
    public function actionSaveSpecificParametersValues()
    {
        $post = Assistant::GetServerMethod(); //получение данных от ajax-запроса
        $errors = array();
        $objectParameters = null;
        $objects = array();
        $specific_parameters = array();
        if (isset($post['parameter_values_array'], $post['specific_object_id'])) {

            $parameterValues = (array)json_decode($post['parameter_values_array'], true);                                                         //массив параметров и их значений
            $tableName = 'place';                                                                                      //название таблицы в которую пишем
//            $tableName = "edge";                                                                                      //название таблицы в которую пишем
            $edge_id = $post['specific_object_id'];                                                                 //айдишник конкретного объекта
            $place_id = Edge::findOne(['id' => $edge_id])->place_id;
            $object_id = Place::findOne(['id' => $place_id])->object_id;
            $object_type_id = TypicalObject::findOne(['id' => $object_id])->object_type_id;
            $object_kind_id = ObjectType::findOne(['id' => $object_type_id])->kind_object_id;
            /** @var $edge_mine_id_db - шахта выработки, получаемая со фронта */
            $edge_mine_id_db = -1;
            if ($parameterValues) {
                $edge_parameters_for_cache = [];

                foreach ($parameterValues as $parameter) {
                    if ($parameter['parameterValue'] != '') {
                        if ($parameter['parameterId'] == 346)                                                            // получаем шахту выработки
                        {
                            $edge_mine_id_db = $parameter['parameterValue'];
                        }

                        if ($parameter['parameterStatus'] == 'place' || $parameter['parameterStatus'] == 'edge' || $parameter['parameterStatus'] == 'manual') {
                            $specific_parameter_handbook_value = new EdgeParameterValue();                      //создать новое значение справочного параметра
                            $specific_parameter_handbook_value->edge_parameter_id = (int)$parameter['specificParameterId'];
                            $specific_parameter_handbook_value->date_time = date('Y-m-d H:i:s');
                            $specific_parameter_handbook_value->value = (string)$parameter['parameterValue'];             //сохранить новое значение, текущую метку времени, типовой параметр и статус
                            $specific_parameter_handbook_value->status_id = 1;
                            if ($specific_parameter_handbook_value->save()) {
                                $edge_parameters_for_cache[] = EdgeCacheController::buildStructureEdgeParametersValue(
                                    $edge_id,
                                    $parameter['specificParameterId'],
                                    $parameter['parameterId'],
                                    $parameter['parameterTypeId'],
                                    $specific_parameter_handbook_value->date_time,
                                    $parameter['parameterValue'],
                                    $specific_parameter_handbook_value->status_id
                                );
                            } else {
                                $errors[] = 'значение параметра ' . $parameter['parameterId'] . ' не сохранено. specificParameterId = ' . $parameter['specificParameterId'] . 'Идентификатор объекта ' . $edge_id;//сохранить соответствующую ошибку
                            }
                        } else if ($parameter['parameterStatus'] == 'handbook') {
//                            	echo "Сохранил параметр ".$parameter['parameterId']."-------";
                            $specific_parameter_handbook_value = new EdgeParameterHandbookValue();                      //создать новое значение справочного параметра
                            $specific_parameter_handbook_value->edge_parameter_id = (int)$parameter['specificParameterId'];
                            $specific_parameter_handbook_value->date_time = date('Y-m-d H:i:s');
                            $specific_parameter_handbook_value->value = (string)$parameter['parameterValue'];             //сохранить новое значение, текущую метку времени, типовой параметр и статус
                            $specific_parameter_handbook_value->status_id = 1;
                            if ($specific_parameter_handbook_value->save()) {
                                $edge_parameters_for_cache[] = EdgeCacheController::buildStructureEdgeParametersValue(
                                    $edge_id,
                                    $parameter['specificParameterId'],
                                    $parameter['parameterId'],
                                    $parameter['parameterTypeId'],
                                    $specific_parameter_handbook_value->date_time,
                                    $parameter['parameterValue'],
                                    $specific_parameter_handbook_value->status_id
                                );

                            } else {
                                $errors[] = 'Справочное значение ' . $parameter['specificParameterId'] . ' не сохранено. Идентификатор объекта ' . $edge_id;//сохранить соответствующую ошибку
                            }
                        }
                    }
                }

                $edge = Edge::findOne((int)$edge_id);//найти объект
                if ($edge) {//если найден, то построить массив объектов, если нет, то сохранить ошибку
                    $specific_parameters = parent::buildSpecificParameterArray($edge_id, $tableName);
                } else {
                    $errors[] = 'Объект с id ' . $edge_id . ' не найден';
                }
                $objects = parent::buildSpecificObjectArray($object_kind_id, $object_type_id, $object_id);
            } else {
                $errors[] = 'Данные не переданы';
            }//сохранить соответствующую ошибку

            $result = array('errors' => $errors, 'objectProps' => $specific_parameters, 'objects' => $objects);//составить результирующий массив как массив полученных массивов
            echo json_encode($result);//вернуть AJAX-запросу данные и ошибки
        }
    }

    //сохраняет базовые Текстовые параметры в базовый справочник
    public function actionUpdateEdgeValuesString($specific_id, $name_field, $value)
    {
        $place_update = Place::findOne(['id' => $specific_id]);
        $place_update->$name_field = (string)$value;
        if (!$place_update->save()) return -1;
        else return 1;
    }

    //сохраняет базовые Числовые параметры в базовый справочник
    public function actionUpdateEdgeValuesInt($specific_id, $name_field, $value)
    {
        var_dump($specific_id);
        $place_update = Place::findOne(['id' => $specific_id]);
        var_dump($place_update);
        $place_update->$name_field = (int)$value;
        if (!$place_update->save()) return -1;
        else return 1;
    }

    /*
         * функция построения параметров конкретного объекта
         * */
    public static function buildEdgeParameterArray($specificObjectId)
    {
        $paramsArray = array();//массив для сохранения параметров
        $modelName = "getEdgeParameters";//динамическое построение имени для поиска параметров
        $tableNameParameterValue = "getEdgeParameterValues";
        $tableNameParameterHandbookValue = "getEdgeParameterHandbookValues";
        $nameId = "edge_id";//динамическое построение имени столбца с id

        $kinds = KindParameter::find()
            ->with('parameters')
            ->with('parameters.unit')
            ->all();//находим все виды параметров
        $i = 0;
        if ($specificObjectId) {//если передан id конкретного объекта
            foreach ($kinds as $kind) {//перебираем все виды параметров
                $paramsArray[$i]['id'] = $kind->id;//сохраняем id вида параметров
                $paramsArray[$i]['title'] = $kind->title;//сохраняем имя вида параметра
                if ($parameters = $kind->parameters) {//если у вида параметра есть параметры
                    $j = 0;
                    foreach ($parameters as $parameter) {//перебираем все параметры
                        if ($specificObjParameters = $parameter->$modelName()
                            ->where([$nameId => $specificObjectId])->orderBy(["parameter_type_id" => SORT_ASC])->all()) {//если есть типовые параметры переданного объекта
                            $paramsArray[$i]['params'][$j]["id"] = $parameter->id;//сохраняем id параметра
                            $paramsArray[$i]['params'][$j]["title"] = $parameter->title;//сохраняем наименование параметра
                            $paramsArray[$i]['params'][$j]["units"] = $parameter->unit->short;//сохраняем единицу измерения
                            $paramsArray[$i]['params'][$j]["units_id"] = $parameter->unit_id;//сохраняем id единицы измерения
                            $k = 0;
                            foreach ($specificObjParameters as $specificObjParameter) {//перебираем конкретный параметр
                                $paramsArray[$i]['params'][$j]['specific'][$k]['id'] = $specificObjParameter->parameter_type_id;//id типа параметра
                                $paramsArray[$i]['params'][$j]['specific'][$k]['title'] = $specificObjParameter->parameterType->title;//название параметра
                                $paramsArray[$i]['params'][$j]['specific'][$k]['specificObjectParameterId'] = $specificObjParameter->id;//id параметра конкретного объекта

                                switch ($specificObjParameter->parameter_type_id) {
                                    case 1:
                                        if ($value = $specificObjParameter->$tableNameParameterHandbookValue()->orderBy(['date_time' => SORT_DESC])->one()) {
                                            $paramsArray[$i]['params'][$j]['specific'][$k]['value'] = $value->value;//сохраняем справочное значение
                                            if ($parameter->id == 337) {//название АСУТП

                                                $asmtpTitle = $value->value == -1 ? "" : ASMTP::findOne((int)$value->value)->title;
                                                $paramsArray[$i]['params'][$j]['specific'][$k]['asmtpTitle'] = $asmtpTitle;
                                            } else if ($parameter->id == 338) {//ТИП сенсора

                                                $sensorTypeTitle = $value->value == -1 ? "" : SensorType::findOne((int)$value->value)->title;
                                                $paramsArray[$i]['params'][$j]['specific'][$k]['sensorTypeTitle'] = $sensorTypeTitle;
                                            } else if ($parameter->id == 274) {// Типовой объект

                                                if ($objectTitle = TypicalObject::findOne($value->value)) {
                                                    $paramsArray[$i]['params'][$j]['specific'][$k]['objectTitle'] = $objectTitle->title;
                                                }
                                            } else if ($parameter->id == 122) {
                                                if ($placeTitle = Place::findOne($value->value)) {// Название места
                                                    $paramsArray[$i]['params'][$j]['specific'][$k]['placeTitle'] = $placeTitle->title;
                                                } else {
                                                    $paramsArray[$i]['params'][$j]['specific'][$k]['placeTitle'] = "";
                                                }
                                            }
                                        }
                                        break;
                                    case 2:

                                        if ($valueFromParameterValue = $specificObjParameter->$tableNameParameterValue()->orderBy(['date_time' => SORT_DESC])->one()) {
                                            $paramsArray[$i]['params'][$j]['specific'][$k]['value'] = $valueFromParameterValue->value;
                                        } else {
                                            $paramsArray[$i]['params'][$j]['specific'][$k]['value'] = "-1";
                                        }

                                        break;
                                    case 3:
                                        if ($valueFromParameterValue = $specificObjParameter->$tableNameParameterValue()->orderBy(['date_time' => SORT_DESC])->one()) {
                                            $paramsArray[$i]['params'][$j]['specific'][$k]['value'] = $valueFromParameterValue->value;
                                        } else {
                                            $paramsArray[$i]['params'][$j]['specific'][$k]['value'] = "-1";
                                        }
                                        $k++;
                                        $paramsArray[$i]['params'][$j]['specific'][$k]['id'] = 4;//id типа параметра
                                        $paramsArray[$i]['params'][$j]['specific'][$k]['title'] = "Привязка параметра";//название параметра
                                        $paramsArray[$i]['params'][$j]['specific'][$k]['specificObjectParameterId'] = $specificObjParameter->id;//id параметра кон
                                        break;

                                }
                                $k++;
                            }
                            $j++;
                        }
                    }
                    ArrayHelper::multisort($paramsArray[$i]['params'], 'title', SORT_ASC);
                }
                $i++;
            }
        }
        ArrayHelper::multisort($paramsArray, 'title', SORT_ASC);
        return $paramsArray;
    }

    //
    private static function camelCase($str)
    {
        $words = explode('_', $str);
        $newStr = '';
        foreach ($words as $key => $word) {
            $newStr .= $key == 0 ? $word : mb_convert_case($word, MB_CASE_TITLE, "UTF-8");
        }
        return $newStr;
    }

    /**
     * addEdge - Метод создания ветви и его параметров
     * @param $place_id - ключе места
     * @param $edge_type_id - ключ типа ветви
     * @param $fullStartNodeId - ключ стартового узла ветви
     * @param $fullEndNodeId - ключ конечного узла ветви
     * @param $ventilation_current_id - текущий ключ вентиляции
     * @param $ventilation_id - ключ ветви вентиляции
     * @param $place_title - название места
     * @param $mine_id - ключ шахты
     * @param $plast_id - ключ пласта
     * @param $section - сечение ветви
     * @param $height - высота ветви
     * @param $width - ширина ветви
     * @param $type_shield - тип крепи
     * @param $angle - угол
     * @param $shape_edge_id - форма выработки
     * @param $color_hex - цвет выработки
     * @return array
     */
    public static function addEdge($place_id, $edge_type_id, $fullStartNodeId, $fullEndNodeId, $ventilation_current_id, $ventilation_id,
                                   $place_title, $mine_id, $plast_id, $section, $height, $width, $date_time_now = 1, $type_shield = TypeShieldEnumController::STONE, $angle = 0, $shape_edge_id = ShapeEdgeEnumController::RECTANGLE, $color_hex = "#000000")
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $edge_id = -1;                                                                                                  // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $edge_param_values = [];                                                                                        // сохраненные параметры с ключами


        $log = new LogAmicumFront("addEdge");

        try {

            $log->addLog("Начало выполнения метода");
//            $log->addData($place_id, '$place_id', __LINE__);
//            $log->addData($edge_type_id, '$edge_type_id', __LINE__);
//            $log->addData($fullStartNodeId, '$fullStartNodeId', __LINE__);
//            $log->addData($fullEndNodeId, '$fullEndNodeId', __LINE__);
//            $log->addData($ventilation_current_id, '$ventilation_current_id', __LINE__);
//            $log->addData($ventilation_id, '$ventilation_id', __LINE__);

            if ($date_time_now == 1) {
                $date_time_now = BackAssistant::GetDateNow();
            }

            $edge_id = self::AddMain('edge');
            if ($edge_id == -1) {
                throw new Exception("Не смог сохранить модель Main для таблицы edge");
            }
            $edge = new Edge();
            $edge->place_id = $place_id;
            $edge->edge_type_id = $edge_type_id;
            $edge->conjunction_start_id = $fullStartNodeId;
            $edge->conjunction_end_id = $fullEndNodeId;
            $edge->id = $edge_id;
            $edge->ventilation_current_id = $ventilation_current_id;
            $edge->ventilation_id = $ventilation_id;
            if (!$edge->save()) {
                $log->addData($edge->errors, '$edge_errors', __LINE__);
                throw new Exception("Не смог сохранить модель Edge");
            }

            $edge_status = new EdgeStatus();
            $edge_status->edge_id = $edge_id;
            $edge_status->date_time = $date_time_now;
            $edge_status->status_id = 1;
            if (!$edge_status->save()) {
                $log->addData($edge_status->errors, '$edge_status_errors', __LINE__);
                throw new Exception("Не смог сохранить модель EdgeStatus");
            }

            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::TITLE);               // Название ветви
            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::EDGE_TYPE_ID);        // Ключ типа ветви
            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::PLACE_ID);            // Ключ места
            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::PLAST_ID);            // Ключ пласта
            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::MINE_ID);             // Ключ шахты
            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::SECTION);             // Сечение ветви
            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::LEVEL_CH4);           // Концентрация метана
            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::LEVEL_CO);            // Концентрация СО
            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::HEIGHT);              // Высота ветви
            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::WIDTH);               // Ширина ветви
            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::LENGTH);              // Длина ветви
            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::TEXTURE);             // Текстура модели
            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::ANGLE);               // Угол ветви
            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::DANGER_ZONA);         // Опасная зона
            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::SHAPE_EDGE_ID);       // Структура ветви
            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::CONVEYOR);            // Конвейер ветви
            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::CONVEYOR_TAG);        // Тег конвейера ветви
            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::TYPE_SHIELD_ID);      // Крепь выработки
            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::COLOR_HEX);           // Цвет ветви
            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::COMPANY_ID);          // Ответственное подразделение
            $edge_params[] = array('edge_id' => $edge_id, 'parameter_type_id' => ParameterTypeEnumController::REFERENCE, 'parameter_id' => ParamEnum::STATE);               // Статус


            $insert_full = Yii::$app->db->createCommand()->batchInsert('edge_parameter', ['edge_id', 'parameter_type_id', 'parameter_id'], $edge_params)->execute();
            if ($insert_full === 0) {
                throw new Exception("Ошибка сохранения параметров ветви");
            }
            unset($edge_params);

            $log->addLog("добавил - $insert_full - записей в таблицу edge_parameter");

            $edge_params = EdgeParameter::find()->where(['edge_id' => $edge_id, 'parameter_type_id' => 1])->asArray()->all();
            foreach ($edge_params as $edge_param) {
                $edge_params_save[$edge_param['parameter_id']] = $edge_param['id'];
            }

            $log->addLog("Получил и сформировал справочник параметров мест");

            $edge_param_values[] = array('edge_parameter_id' => $edge_params_save[ParamEnum::TITLE], 'date_time' => $date_time_now, 'value' => $place_title, 'status_id' => StatusEnumController::ACTUAL);            // Название ветви
            $edge_param_values[] = array('edge_parameter_id' => $edge_params_save[ParamEnum::EDGE_TYPE_ID], 'date_time' => $date_time_now, 'value' => $edge_type_id, 'status_id' => StatusEnumController::ACTUAL);    // Ключ типа ветви
            $edge_param_values[] = array('edge_parameter_id' => $edge_params_save[ParamEnum::PLACE_ID], 'date_time' => $date_time_now, 'value' => $place_id, 'status_id' => StatusEnumController::ACTUAL);            // Ключ места
            $edge_param_values[] = array('edge_parameter_id' => $edge_params_save[ParamEnum::PLAST_ID], 'date_time' => $date_time_now, 'value' => $plast_id, 'status_id' => StatusEnumController::ACTUAL);            // Ключ пласта
            $edge_param_values[] = array('edge_parameter_id' => $edge_params_save[ParamEnum::MINE_ID], 'date_time' => $date_time_now, 'value' => $mine_id, 'status_id' => StatusEnumController::ACTUAL);              // Ключ шахты
            $edge_param_values[] = array('edge_parameter_id' => $edge_params_save[ParamEnum::SECTION], 'date_time' => $date_time_now, 'value' => $section, 'status_id' => StatusEnumController::ACTUAL);              // Сечение ветви
            $edge_param_values[] = array('edge_parameter_id' => $edge_params_save[ParamEnum::LEVEL_CH4], 'date_time' => $date_time_now, 'value' => 1, 'status_id' => StatusEnumController::ACTUAL);                   // Концентрация метана
            $edge_param_values[] = array('edge_parameter_id' => $edge_params_save[ParamEnum::LEVEL_CO], 'date_time' => $date_time_now, 'value' => '0.0017', 'status_id' => StatusEnumController::ACTUAL);             // Концентрация СО
            $edge_param_values[] = array('edge_parameter_id' => $edge_params_save[ParamEnum::HEIGHT], 'date_time' => $date_time_now, 'value' => $height, 'status_id' => StatusEnumController::ACTUAL);                // Высота ветви
            $edge_param_values[] = array('edge_parameter_id' => $edge_params_save[ParamEnum::WIDTH], 'date_time' => $date_time_now, 'value' => $width, 'status_id' => StatusEnumController::ACTUAL);                  // Ширина ветви
            $edge_param_values[] = array('edge_parameter_id' => $edge_params_save[ParamEnum::TYPE_SHIELD_ID], 'date_time' => $date_time_now, 'value' => $type_shield, 'status_id' => StatusEnumController::ACTUAL);   // тип крепи
            $edge_param_values[] = array('edge_parameter_id' => $edge_params_save[ParamEnum::ANGLE], 'date_time' => $date_time_now, 'value' => $angle, 'status_id' => StatusEnumController::ACTUAL);                  // угол
            $edge_param_values[] = array('edge_parameter_id' => $edge_params_save[ParamEnum::SHAPE_EDGE_ID], 'date_time' => $date_time_now, 'value' => $shape_edge_id, 'status_id' => StatusEnumController::ACTUAL);  // структура ветви - форма
            $edge_param_values[] = array('edge_parameter_id' => $edge_params_save[ParamEnum::COLOR_HEX], 'date_time' => $date_time_now, 'value' => $color_hex, 'status_id' => StatusEnumController::ACTUAL);          // Цвет ветви
            $edge_param_values[] = array('edge_parameter_id' => $edge_params_save[ParamEnum::TEXTURE], 'date_time' => $date_time_now, 'value' => "CapitalEdgeMaterial", 'status_id' => StatusEnumController::ACTUAL); // Текстура модели
            $edge_param_values[] = array('edge_parameter_id' => $edge_params_save[ParamEnum::STATE], 'date_time' => $date_time_now, 'value' => StatusEnumController::ACTUAL, 'status_id' => StatusEnumController::ACTUAL); // состояние выработки

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result, 'edge_param_values' => $edge_param_values, 'edge_id' => $edge_id], $log->getLogAll());
    }

    public static function AddMain($table_address)
    {
        $main = new Main();
        $main->db_address = "amicum3";
        $main->table_address = $table_address;
        if ($main->save()) {
            return $main->id;
        } else {
            return -1;
        }
    }
}