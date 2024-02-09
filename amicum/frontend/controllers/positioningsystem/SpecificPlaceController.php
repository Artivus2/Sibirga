<?php

namespace frontend\controllers\positioningsystem;
//ob_start();

use backend\controllers\Assistant as BackAssistant;
use backend\controllers\EdgeBasicController;
use backend\controllers\EdgeMainController;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\HandbookCachedController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\AccessCheck;
use frontend\models\Main;
use frontend\models\ObjectType;
use frontend\models\Place;
use frontend\models\PlaceFunction;
use frontend\models\PlaceParameter;
use frontend\models\PlaceParameterHandbookValue;
use frontend\models\PlaceParameterSensor;
use frontend\models\PlaceParameterValue;
use frontend\models\TypeObjectFunction;
use frontend\models\TypeObjectParameter;
use frontend\models\TypeObjectParameterHandbookValue;
use frontend\models\TypicalObject;
use Throwable;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\Response;

class SpecificPlaceController extends SpecificObjectController
{

    // addPlace             - Метод создания места и его параметров

    /*места*/
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionAddSpecificObject()
    {
        $errors = array();                                                                                              //создаем массив для ошибок
        $check_title = 0;                                                                                                 //флаг проверки на существование такого названия в базе
        $check_input_parameters = 1;                                                                                      //флаг проверки входных параметров
        $flag_done = 0;                                                                                                   //флаг успешности выполнения
        $debug_flag = 0;                                                                                                  //отладочный флаг
        $kind_id = null;
        $object_type_id = null;
        $object_id = null;
        $specific_title = null;
        $main_specific_id = null;
        $mine_id = null;
        $specific_array = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 86)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();                                                                             //получение данных от ajax-запроса
                if (isset($post['title']) and $post['title'] != "") {                                                               //проверка на наличие входных данных а именно на наличие такого названия
                    $specific_title = $post['title'];                                                                           //название нового конкретного объекта, который создаем
                    $sql_filter = 'title="' . $specific_title . '"';
                    $places = (new Query())//запрос напрямую из базы по таблице Place
                    ->select(
                        [
                            'id',
                            'title'
                        ])
                        ->from(['place'])
                        ->where($sql_filter)
                        ->one();
                    if ($places) {
                        $errors[] = "Объект с именем " . $specific_title . " уже существует";
                        $check_title = -1;
                    } else $check_title = 1;                                                                                        //название не существует в базе, можно добавлять объект
                    if ($debug_flag == 1) echo nl2br("----прошел проверку на наличие такого тега (1 тега нет)  =" . $check_title . "\n");
                } else {
                    $errors[] = "Не передано название выработки";
                    $check_input_parameters = -1;
                    if ($debug_flag == 1) echo nl2br("----проверка на наличие тега /проверка на наличие входных данных 1 входные данные есть =" . $check_input_parameters . "\n");
                }

                if (isset($post['object_id']) and $post['object_id'] != "" and
                    isset($post['mine_id']) and $post['mine_id'] != "") {                                                       //проверка на наличие входных данных а именно на наличие типового объекта, который копируется
                    $object_id = $post['object_id'];                                                                            //айдишник типового объекта
                    $sql_filter = 'object_id=' . $object_id . '';
                    $typical_objects = (new Query())//запрос напрямую из базы по вьюшке view_personal_areas
                    ->select(
                        [
                            'object_id',                                                                                        //айди типового объекта
                            'object_type_id',                                                                                   //айди типа типового объекта
                            'kind_object_id'                                                                                    //айди вида типового объекта
                        ])
                        ->from(['view_type_object'])
                        ->where($sql_filter)
                        ->one();
                    if (!$typical_objects) {
                        $errors[] = "Типовой объект: " . $object_id . " не существует";
                        $check_input_parameters = -1;
                        if ($debug_flag == 1) echo nl2br("----проверка на типовой объект - он не существует =" . $object_id . "\n");
                    } else {
                        $kind_id = $typical_objects["kind_object_id"];                                                          //вид типового объекта ИД
                        $object_type_id = $typical_objects["object_type_id"];                                                   //тип типового объекта ИД
                        if ($debug_flag == 1) echo nl2br("----проверка на типовой объект - он существует =" . $object_id . "\n");
                    }
                    $mine_id = $post['mine_id'];                                                                                  //id шахты
                } else {
                    $errors[] = "Не все входные данные есть в методе POST";
                    $check_input_parameters = -1;
                    if ($debug_flag == 1) echo nl2br("----проверка на наличие типового объекта входные данные есть =" . $check_input_parameters . "\n");
                }
                if (isset($post['plast_id']) and $post['plast_id'] != "") {                                                       //проверка на наличие входных данных а именно на наличие пласта в передаче данных, который копируется
                    $plast_id = $post['plast_id'];                                                                                  //id пласта
                } else $plast_id = (-1);


                if ($check_input_parameters == 1 and $check_title == 1) {                                                             //все нужные входные данные есть и название не существует в базе
                    if ($debug_flag == 1) echo nl2br("----вход в выполнение функции по добавлению объекта =" . "\n");

                    $main_specific_id = $this->actionAddPlace($specific_title, $object_id, $mine_id, $plast_id);     //создаем/изменяем запись с таблице Place
                    if ($debug_flag == 1) echo nl2br("----создано место =" . $main_specific_id . "\n");

                    if ($main_specific_id == -1) {
                        $errors[] = "Ошибка сохранения места в базовой таблице:" . $specific_title;
                    } else {                                                                                                    //если сохранили место, то копируем справочные значения и функции типового объекта
                        $flag_done = $this->actionCopyTypicalParametersToSpecific($object_id, $main_specific_id);
                        if ($flag_done == -1) {
                            $errors[] = "Ошибка копирования параметров и значений типового объекта в конкретный:" . $main_specific_id;
                            if ($debug_flag == 1) echo nl2br("----ошибка копирования параметров типового объекта в конкретные =" . $main_specific_id . "\n");
                        } else {
                            //сохраняем значения параметров из базовой таблицы в параметры базового лбъекта
                            $place_parameter_id = self::actionAddPlaceParameter($main_specific_id, 162, 1); //параметр наименование
                            $place_parameter_value = self::actionAddPlaceParameterHandbookValue($place_parameter_id, $specific_title, 1, date("Y-m-d H:i:s"));//сохранение значения параметра
                            if ($place_parameter_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 162";

                            $place_parameter_id = self::actionAddPlaceParameter($main_specific_id, 347, 1); //параметр пласт
                            $place_parameter_value = self::actionAddPlaceParameterHandbookValue($place_parameter_id, $plast_id, 1, date("Y-m-d H:i:s"));//сохранение значения параметра
                            if ($place_parameter_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 347";

                            $place_parameter_id = self::actionAddPlaceParameter($main_specific_id, 346, 1); //параметр шахта
                            $place_parameter_value = self::actionAddPlaceParameterHandbookValue($place_parameter_id, $mine_id, 1, date("Y-m-d H:i:s"));//сохранение значения параметра
                            if ($place_parameter_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 346";
                        }
                        $specific_array = parent::buildSpecificObjectArray($kind_id, $object_type_id, $object_id);//вызываем функция построения массива конкретных объектов нужного типа
                    }
                } else {
                    $errors[] = "Вырабоотка с именем " . $specific_title . " уже существует";
                }
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        $result = array('specificArray' => $specific_array, 'errors' => $errors, 'specific_id' => $main_specific_id);//создаем массив для передачи данных по ajax запросу со значениями и ошибками
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: actionDeleteSpecificObject()
     * @package app\controllers
     * Метод удаления места. При удалении места удаляются привязанные выработки, со всеми параметрами из
     * значения из кэша с помощью очереди по умолчанию
     * Входные обязательные параметры:
     * $post['specific_id'] - идентификатор местопложения
     * $post['kind_object_id'] - вид объекта
     * $post['object_type_id'] - тип объекта
     * $post['object_id'] - идентификатор объекта
     * Входные необязательные параметры
     *
     * @url http://localhost/specific-place/delete-specific-object
     * @url http://localhost/specific-place/delete-specific-object?specific_id=6582&table_name=place&kind_object_id=2&object_type_id=111&object_id=10
     *
     * Документация на портале:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 17.01.2019 14:30
     * @since ver2.0
     */
    public function actionDeleteSpecificObject()
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();

        $specificObjects = array();

        $warnings[] = 'actionDeleteSpecificObject. Начало метода';
        try {
            $session = Yii::$app->session;                                                                                  //старт сессии
            $session->open();                                                                                               //открыть сессию
            if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
                if (AccessCheck::checkAccess($session['sessionLogin'], 88)) {                                        //если пользователю разрешен доступ к функции
                    $post = Assistant::GetServerMethod();

                    $arguments_are_valid = isset($post['specific_id']) && $post['specific_id'] != '' &&
                        isset($post['kind_object_id']) && $post['kind_object_id'] != '' &&
                        isset($post['object_type_id']) && $post['object_type_id'] != '' &&
                        isset($post['object_id']) && $post['object_id'] != '';

                    if ($arguments_are_valid) {
                        $specific_id = $post['specific_id'];
                        $kind_object_id = $post['kind_object_id'];
                        $type_object_id = $post['object_type_id'];
                        $object_id = $post['object_id'];
                        $specificObject = Place::findOne($specific_id);
                        if ($specificObject) {
                            $mine_id = $specificObject->mine_id;
                            PlaceFunction::deleteAll(['place_id' => $specific_id]);                                             //удаляем функции у сенсора

                            $specific_parameters = PlaceParameter::findAll(['place_id' => $specific_id]);                       //ищем параметры на удаление
                            foreach ($specific_parameters as $specific_parameter) {
                                PlaceParameterValue::deleteAll(['place_parameter_id' => $specific_parameter->id]);              //удаляем измеренные или вычесленные значения
                                PlaceParameterHandbookValue::deleteAll(['place_parameter_id' => $specific_parameter->id]);      //удаляем справочные значения
                                PlaceParameter::deleteAll(['id' => $specific_parameter->id]);                                   //удаляем сам параметр сенсора
                            }

                            /********************************* УДАЛЕНИЯ ВЫРАБОТОК   ******************************************/
                            // TODO: замена параметра у сенсоров на данных выработках (отвязывание сенсоров от эджей)
                            UnityController::CascadeEdgeDelete($mine_id, "place_id = $specific_id"); // удаляем из БД и из кэша
                            Place::deleteAll(['id' => $specific_id]);                                                           //удаляем сам сенсор
                        }
                        $specificObjects = self::buildSpecificObjectArray($kind_object_id, $type_object_id, $object_id);              //построение списка типовых объектов
                    } else {
                        $errors[] = 'Данные не переданы';
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
            $errors[] = 'actionDeleteSpecificObject. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'actionDeleteSpecificObject. Конец метода';

        $result = array('Items' => $result, 'status' => $status,
            'warnings' => $warnings, 'errors' => $errors,
            'specificObjects' => $specificObjects);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /*
    * метод перемещения места
    * */
    public function actionMoveSpecificObject()
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();

        $objectKinds = array();
        $newObjectKinds = array();

        $warnings[] = 'actionMoveSpecificObject. Начало метода';
        try {
            $session = Yii::$app->session;                                                                                  //старт сессии
            $session->open();                                                                                               //открыть сессию
            if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
                if (AccessCheck::checkAccess($session['sessionLogin'], 89)) {                                        //если пользователю разрешен доступ к функции
                    $post = Assistant::GetServerMethod();
                    if (isset($post['specific_id']) && isset($post['kind_object_id']) && isset($post['object_type_id'])
                        && isset($post['object_id']) && isset($post['new_object_id'])) {                                            //если все данные переданы

                        $place_id = $post['specific_id'];                                                                          //айдишник конкретного объекта
                        $object_id = $post['object_id'];                                                                              //старый айдишник типового объекта
                        $new_object_id = $post['new_object_id'];                                                                      //новый айдишник типового объекта
                        $kind_object_id = $post['kind_object_id'];                                                                    //вид типового объекта
                        $object_type_id = $post['object_type_id'];                                                                    //Айдишник типа типового объекта

                        $specificObject = Place::findOne($place_id);
                        if ($specificObject) {
                            $specificObject->object_id = $new_object_id;
                            if ($specificObject->save()) {
                                // Изменение параметра 274 у эджей
                                $edges = (new Query())
                                    ->select('id')
                                    ->from('edge')
                                    ->where([
                                        'place_id' => $place_id
                                    ])
                                    ->all();
                                if ($edges) {
                                    foreach ($edges as $edge) {
                                        $edge_parameter_id = EdgeBasicController::addEdgeParameter($edge['id'], 274, 1);
                                        EdgeBasicController::addEdgeParameterHandbookValue($edge_parameter_id, $new_object_id, 1);
                                    }
                                } else {
                                    $warnings[] = 'actionMoveSpecificObject. Не найдены выработки для плейса ' . $place_id;
                                }

                                // Изменение параметра 274 у плейса
                                $place_parameter = PlaceParameter::findOne([
                                    'place_id' => $place_id,
                                    'parameter_id' => 274,
                                    'parameter_type_id' => 1
                                ]);
                                if ($place_parameter == null) {
                                    $place_parameter = new PlaceParameter();
                                    $place_parameter->place_id = $place_id;
                                    $place_parameter->parameter_id = 274;
                                    $place_parameter->parameter_type_id = 1;
                                    $place_parameter->save();
                                    $place_parameter->update();
                                }
                                if ($place_parameter->id) {
                                    $place_parameter_value = new PlaceParameterHandbookValue();
                                    $place_parameter_value->place_parameter_id = $place_parameter->id;
                                    $place_parameter_value->value = $new_object_id;
                                    $place_parameter_value->date_time = BackAssistant::GetDateNow();
                                    $place_parameter_value->status_id = 1;
                                    if (!$place_parameter_value->save()) {
                                        $errors[] = 'actionMoveSpecificObject. Ошибка сохранения place_parameter_handbook_value';
                                        $errors[] = $place_parameter_value->errors;
                                    }
                                } else {
                                    $errors[] = 'actionMoveSpecificObject. Не найден place_parameter';
                                }

                                // Обновление списка в левой части экрана
                                $objectKinds = self::buildSpecificObjectArray($kind_object_id, $object_type_id, $object_id);
                                $newObjectKinds = self::buildSpecificObjectArray($kind_object_id, $object_type_id, $new_object_id);
                            } else {
                                $errors[] = 'actionMoveSpecificObject. Не удалось переместить объект';
                            }
                        } else {
                            $errors[] = 'actionMoveSpecificObject. Не найден плейс в БД';
                        }
                    } else {
                        $errors[] = 'actionMoveSpecificObject. Данные не переданы';
                    }
                } else {
                    $errors[] = 'actionMoveSpecificObject. Недостаточно прав для совершения данной операции';
                }
            } else {
                $errors[] = 'actionMoveSpecificObject. Время сессии закончилось. Требуется повторный ввод пароля';
                $this->redirect('/');
            }
        } catch (\Throwable $exception) {
            $status = 0;
            $errors[] = 'actionMoveSpecificObject. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'actionMoveSpecificObject. Конец метода';

        $result = array('Items' => $result, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors,
            'specificObjects' => $objectKinds, 'newSpecificObjects' => $newObjectKinds);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /*
    * функция редактирования конкретных объектов
    * */
    public function actionEditSpecificObjectBase()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $errors = array();
        $objectKinds = null;
        $specificObjects = array();
        $specificParameters = array();
        if (isset($post['title']) && isset($post['specific_id'])
            && isset($post['kind_object_id']) && isset($post['object_type_id']) && isset($post['object_id'])) {          //проверка на передачу данных

            $new_object_title = $post['title'];                                                                           //название конкретного объекта - новое
            $specific_id = $post['specific_id'];                                                                          //айдишник конкретного объекта
            $object_id = $post['object_id'];                                                                              //старый айдишник типового объекта
            $kind_object_id = $post['kind_object_id'];                                                                    //вид типового объекта
            $object_type_id = $post['object_type_id'];                                                                    //Айдишник типа типового объекта

            $object = Place::findOne($specific_id);                                                                     //найти объект по id
            if ($object) {                                                                                                //если объект существует
                $existingObject = Place::findOne(['title' => $new_object_title]);                                         //найти объект по названию, чтобы не было дублирующих
                if (!$existingObject) {                                                                                   //если не найден
                    $object->title = $new_object_title;                                                                            //сохранить в найденный по id параметр название
                    if ($object->save()) {                                                                                //если объет сохранился
                        $place_parameter_id = self::actionAddPlaceParameter($specific_id, 162, 1); //параметр наименование
                        $place_parameter_value = self::actionAddPlaceParameterHandbookValue($place_parameter_id, $new_object_title, 1, 1);//сохранение значения параметра
                        if ($place_parameter_id == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 162";
                        $specificObjects = parent::buildSpecificObjectArray($kind_object_id, $object_type_id, $object_id);//обновить массив типовых объектов
                        $specificParameters = parent::buildSpecificParameterArray($specific_id, 'place');
                    } else $errors[] = "Ошибка сохранения";                                                               //если не сохранился, сохранить соответствующую ошибку
                } else $errors[] = "Объект с таким названием уже существует";                                             //если найден объект по названию, сохранить соответствующую ошибку
            } else $errors[] = "Объекта с id " . $specific_id . " не существует";                                              //если не найден объект по id, сохранить соответствующую ошибку
        } else$errors[] = "Данные не переданы";                                                                           //если не заданы входные параметры сохранить соответствующую ошибку

        $result = array('errors' => $errors, 'specificObjects' => $specificObjects, 'specificParameters' => $specificParameters);                                            //составить результирующий массив как массив полученных массивов
        echo json_encode($result);                                                                                      //вернуть AJAX-запросу данные и ошибки
    }

    /*
 * функция сохранения значений с вкладки
 * $post['table_name'] - имя таблицы
 * $post['parameter_values_array'] - массив значений
 * $post['specificObjectId'] - id конкретного объекта
 * */
    public function actionSaveSpecificParametersValues()
    {
        $errors = array();
        $objectParameters = null;
        $objects = array();
        $specific_parameters = array();
        $session = Yii::$app->session;
        $session->open();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 92)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['parameter_values_array']) && isset($post['specific_object_id'])) {

                    $parameterValues = (array)json_decode($post['parameter_values_array'], true);                                                         //массив параметров и их значений
                    $specific_id = $post['specific_object_id'];                                                                 //айдишник конкретного объекта
                    $object_id = Place::findOne(['id' => $specific_id])->object_id;
                    $mine_id = Place::findOne(['id' => $specific_id])->mine_id;
                    $object_type_id = TypicalObject::findOne(['id' => $object_id])->object_type_id;
                    $object_kind_id = ObjectType::findOne(['id' => $object_type_id])->kind_object_id;
                    if ($parameterValues) {
                        foreach ($parameterValues as $parameter) {
                            if ($parameter['parameterValue'] != "") {
                                if ($parameter['parameterStatus'] == 'sensor') {
                                    if (isset($parameter['parameterValue'])) {
                                        $place_parameter_sensor = new PlaceParameterSensor();                                     //записываем значения параметров со вкладки измеренные
                                        $place_parameter_sensor->place_parameter_id = (int)$parameter['specificParameterId'];
                                        $place_parameter_sensor->date_time = date("Y-m-d H:i:s");
                                        $place_parameter_sensor->sensor_id = (int)$parameter['parameterValue'];                    //айди сенсора - получилась рекурсия по базе, но принцип не страшно
//                                var_dump($sensor_parameter_sensor);
                                        if (!$place_parameter_sensor->save()) {                                                    //если не сохранилась
                                            $errors[] = "Измеряемое значение " . $parameter['specificParameterId'] . " не сохранено. Идентификатор объекта " . $specific_id;//сохранить соответствующую ошибку
                                        }
                                    }
                                } else if ($parameter['parameterStatus'] == 'place' || $parameter['parameterStatus'] == 'edge') {
                                    $specific_parameter_handbook_value = new PlaceParameterValue();                      //создать новое значение справочного параметра
                                    $specific_parameter_handbook_value->place_parameter_id = (int)$parameter['specificParameterId'];
                                    $specific_parameter_handbook_value->date_time = date("Y-m-d H:i:s");
                                    $specific_parameter_handbook_value->value = (string)$parameter['parameterValue'];             //сохранить новое значение, текущую метку времени, типовой параметр и статус
                                    $specific_parameter_handbook_value->status_id = 1;
                                    if (!$specific_parameter_handbook_value->save()) {//если не сохранилась
                                        $errors[] = "значение параметра " . $parameter['parameterId'] . " не сохранено. specificParameterId = " . $parameter['specificParameterId'] . "Идентификатор объекта " . $specific_id;//сохранить соответствующую ошибку
                                    }
                                } else if ($parameter['parameterStatus'] == 'handbook') {
                                    $specific_parameter_handbook_value = new PlaceParameterHandbookValue();                      //создать новое значение справочного параметра
                                    $specific_parameter_handbook_value->place_parameter_id = (int)$parameter['specificParameterId'];
                                    $specific_parameter_handbook_value->date_time = date("Y-m-d H:i:s");
                                    $specific_parameter_handbook_value->value = (string)$parameter['parameterValue'];             //сохранить новое значение, текущую метку времени, типовой параметр и статус
                                    $specific_parameter_handbook_value->status_id = 1;
                                    if (!$specific_parameter_handbook_value->save()) {//если не сохранилась
                                        $errors[] = "Справочное значение " . $parameter['specificParameterId'] . " не сохранено. Идентификатор объекта " . $specific_id;//сохранить соответствующую ошибку
                                    }
                                    //сохраняем значение параметров в базовые справочники объекта
                                    if ($parameter['parameterId'] == 162) {                                                    //параметр наименование
                                        $place_value = $this->actionUpdatePlaceValuesString($specific_id, "title", $parameter['parameterValue']);
                                        if ($place_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 162";
                                        $response = EdgeMainController::EditParametersValuesEdges(162, 1, 118, $specific_id, $parameter['parameterValue'], $mine_id);
                                        if ($response['status'] == 1) {
                                            $warnings[] = "actionSaveSpecificParametersValues.Сохранил изменения 162 параметра для выработок принадлежавших place_id = $specific_id";
                                        } else {
                                            $errors[] = $response['errors'];
                                            $errors[] = "actionSaveSpecificParametersValues. Ошибка  сохранения изменения параметра 162 в кеше для выработок";
                                            throw new \Exception("actionSaveSpecificParametersValues. Ошибка  сохранения изменения параметра 162 в кеше для выработок");
                                        }
                                    }
                                    if ($parameter['parameterId'] == 274) {                                                    //параметр тип объекта
                                        $place_value = $this->actionUpdatePlaceValuesInt($specific_id, "object_id", $parameter['parameterValue']);
                                        if ($place_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 274";
                                        $response = EdgeMainController::EditParametersValuesEdges(274, 1, 118, $specific_id, $parameter['parameterValue'], $mine_id);
                                        if ($response['status'] == 1) {
                                            $warnings[] = "actionSaveSpecificParametersValues.Сохранил изменения 274 параметра для выработок принадлежавших place_id = $specific_id";
                                        } else {
                                            $errors[] = $response['errors'];
                                            $errors[] = "actionSaveSpecificParametersValues. Ошибка  сохранения изменения параметра 274 в кеше для выработок";
                                            throw new \Exception("actionSaveSpecificParametersValues. Ошибка  сохранения изменения параметра 274 в кеше для выработок");
                                        }
                                    }
                                    if ((int)$parameter['parameterId'] == 346) {                                                    //параметр шахта
                                        $place_value = $this->actionUpdatePlaceValuesInt($specific_id, "mine_id", $parameter['parameterValue']);
                                        if ($place_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 346";
                                        $response = EdgeMainController::EditParametersValuesEdges(346, 1, 118, $specific_id, $parameter['parameterValue'], $mine_id);
                                        if ($response['status'] == 1) {
                                            $warnings[] = "actionSaveSpecificParametersValues.Сохранил изменения 346 параметра для выработок принадлежавших place_id = $specific_id";
                                        } else {
                                            $errors[] = $response['errors'];
                                            $errors[] = "actionSaveSpecificParametersValues. Ошибка  сохранения изменения параметра 346 в кеше для выработок";
                                            throw new \Exception("actionSaveSpecificParametersValues. Ошибка  сохранения изменения параметра 346 в кеше для выработок");
                                        }
                                    }
                                    if ((int)$parameter['parameterId'] == 347) {                                                    //параметр пласт
                                        $place_value = $this->actionUpdatePlaceValuesInt($specific_id, "plast_id", $parameter['parameterValue']);
                                        if ($place_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 347";
                                        $response = EdgeMainController::EditParametersValuesEdges(347, 1, 118, $specific_id, $parameter['parameterValue'], $mine_id);
                                        if ($response['status'] == 1) {
                                            $warnings[] = "actionSaveSpecificParametersValues.Сохранил изменения 347 параметра для выработок принадлежавших place_id = $specific_id";
                                        } else {
                                            $errors[] = $response['errors'];
                                            $errors[] = "actionSaveSpecificParametersValues. Ошибка  сохранения изменения параметра 347 в кеше для выработок";
                                            throw new \Exception("actionSaveSpecificParametersValues. Ошибка  сохранения изменения параметра 347 в кеше для выработок");
                                        }
                                    }
                                } else if ($parameter['parameterStatus'] == 'manual') {
                                    $specific_parameter_value = new PlaceParameterValue();                      //создать новое значение справочного параметра
                                    $specific_parameter_value->place_parameter_id = (int)$parameter['specificParameterId'];
                                    $specific_parameter_value->date_time = date("Y-m-d H:i:s");
                                    $specific_parameter_value->value = (string)$parameter['parameterValue'];             //сохранить новое значение, текущую метку времени, типовой параметр и статус
                                    $specific_parameter_value->status_id = 1;
                                    if (!$specific_parameter_value->save()) {//если не сохранилась
                                        $errors[] = "значение параметра " . $parameter['parameterId'] . " не сохранено. specificParameterId = " . $parameter['specificParameterId'] . "Идентификатор объекта " . $specific_id;//сохранить соответствующую ошибку
                                    }
                                }
                            }
                        }
                    }
                    $place = Place::findOne((int)$specific_id);//найти объект
                    if ($place) {//если найден, то построить массив объектов, если нет, то сохранить ошибку
                        $specific_parameters = parent::buildSpecificParameterArray($specific_id, 'place');
                    } else {
                        $errors[] = "Объект с id " . $specific_id . " не найден";
                    }
                    echo "<hr>";
                    var_dump($object_kind_id);
                    echo "<hr>";
                    var_dump($object_type_id);
                    echo "<hr>";
                    var_dump($object_id);
                    $objects = parent::buildSpecificObjectArray($object_kind_id, $object_type_id, $object_id);
                } else {
                    $errors[] = "Данные не переданы";
                }//сохранить соответствующую ошибку
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        $result = array('errors' => $errors, 'objectProps' => $specific_parameters, 'objects' => $objects);//составить результирующий массив как массив полученных массивов
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;                                                                            //вернуть AJAX-запросу данные и ошибки
    }

    //метод копирует параметры типового параметра в параметры конкретного объекта - нужен для создания конкретного объекта по шаблону типового объекта
    private function actionCopyTypicalParametersToSpecific($typical_object_id, $specific_object_id)
    {
        $debug_flag = 0;                                                                                                  //отладочный флаг
        $flag_done = 1;                                                                                                   //флаг успешного выполнения метода
        //копирование параметров справочных
        if ($type_object_parameters = TypeObjectParameter::find()->where(['object_id' => $typical_object_id, 'parameter_type_id' => 1])->all())                           //Находим все параметры типового объекта
        {
            foreach ($type_object_parameters as $type_object_parameter) {
                //создаем новый параметр у конкретного объекта
                $place_parameter_id = self::actionAddPlaceParameter($specific_object_id, $type_object_parameter->parameter_id, $type_object_parameter->parameter_type_id);

                //ищем последние справочное значения параметра типового объекта и копируем их в значение справочное конкретного объекта
                if ($place_parameter_id
                    and $typical_object_parameter_handbook_values = TypeObjectParameterHandbookValue::find()
                        ->where(['type_object_parameter_id' => $type_object_parameter->id])
                        ->orderBy(['date_time' => SORT_DESC])
                        ->one())
                    $flag_done = self::actionAddPlaceParameterHandbookValue($place_parameter_id, $typical_object_parameter_handbook_values->value, $typical_object_parameter_handbook_values->status_id, 1);
            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись справочных параметров" . "\n");

        //копирование функций типового объекта
        //находим функции типового объекта
        if ($type_object_functions = TypeObjectFunction::find()->where(['object_id' => $typical_object_id])->all()) {
            foreach ($type_object_functions as $type_object_function) {
                $place_function_id = self::actionAddPlaceFunction($specific_object_id, $type_object_function->func_id);
                if ($place_function_id == -1) $flag_done = -1;
            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись функций" . "\n");
        return $flag_done;
    }

    //сохранение функций места
    public static function actionAddPlaceFunction($place_id, $function_id)
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

    //функция добавления функции места с post с фронта
    public function actionAddPlaceFunctionFront()
    {
        $errors = array();
        $functionsArray = array();
        $session = Yii::$app->session;
        $session->open();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 94)) {                                       //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['specificObjectId']) && isset($post['specificObjectId']) && isset($post['functionId']) && isset($post['functionId'])) {
                    $place_id = $post['specificObjectId'];
                    $function_id = $post['functionId'];
                    $place_function = self::actionAddPlaceFunction($place_id, $function_id);
                    if ($place_function == -1) $errors[] = "не удалось сохранить параметр";
                    $functionsArray = parent::buildSpecificFunctionArray($place_id, "place");
                } else {
                    $errors[] = "Данные не переданы";
                }
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        $result = array('objectFunctions' => $functionsArray, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;                                                                          //вернуть AJAX-запросу данные и ошибки
    }

    //обновление значения функции привязанной к конкретному месту
    public function actionUpdatePlaceFunction($place_function_id, $place_id, $function_id)
    {
        $debug_flag = 0;
        if ($debug_flag == 1) echo nl2br("----зашел в функцию редактирования функции оборудования  =" . $place_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        $place_function_update = PlaceFunction::find()->where(['id' => $place_function_id])->one();
        if ($place_function_update) {
            $place_function_update->place_id = $place_id;                                                               //айди места
            $place_function_update->function_id = $function_id;                                                         //айди функции

            if ($place_function_update->save()) {
                $functionsArray = parent::buildSpecificFunctionArray($place_id, "place");                     //создаем список функций на возврат
                $result = array('funcArray' => $functionsArray);
                echo json_encode($result);
                return $place_function_update->id;
            } else return -1;
        }
    }


    //функция удаления функции места с post с фронта
    public function actionDeletePlaceFunction()
    {
        $errors = array();
        $object_functions = array();
        $session = Yii::$app->session;
        $session->open();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 95)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['specific_object_id']) && $post['specific_object_id'] != "" && isset($post['specific_function_id']) && $post['specific_function_id'] != "") {
                    $place_id = $post['specific_object_id'];
                    $place_function_id = $post['specific_function_id'];
                    PlaceFunction::deleteAll('id=:place_function_id', [':place_function_id' => $place_function_id]);

                    $object_functions = array();
                    $objects = (new Query())
                        ->select(
                            [
                                'function_type_title functionTypeTitle',
                                'function_type_id functionTypeId',
                                'place_function_id id',
                                'function_id',
                                'place_id',
                                'func_title functionTitle',
                                'func_script_name scriptName'
                            ])
                        ->from(['view_place_function'])
                        ->where('place_id = ' . $place_id)
                        ->orderBy("function_type_id")
                        ->all();

                    $i = -1;
                    $j = 0;

                    foreach ($objects as $object) {
                        if ($i == -1 or $object_functions[$i]['id'] != $object['functionTypeId']) {
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
                    $errors[] = "Данные не переданы";
                }
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        $result = array('objectFunctions' => $object_functions, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }


    //метод создания конкретного объекта в его базовой таблице
    public function actionAddPlace($place_title, $object_id, $mine_id, $plast_id)
    {
        $place_id = parent::actionAddEntryMain('place');                                                    //создаем запись в таблице Main
        if (!is_int($place_id)) return -1;
        else {
            $newSpecificObject = new Place();//сохраняем все данные в нужной модели
            $newSpecificObject->id = $place_id;                                                                         //айдишнек нового созданного места
            $newSpecificObject->title = $place_title;
            $newSpecificObject->object_id = (int)$object_id;                                                                 //id типового объекта
            $newSpecificObject->mine_id = $mine_id;                                                                     //id шахты
            if ($plast_id != -1) {                                                                                         //id пласта, если -1 то не задано, иначе пишем то, что есть
                $newSpecificObject->plast_id = $plast_id;
            }
            HandbookCachedController::clearPlaceCache();
            if (!$newSpecificObject->save()) return -1;                                                                      //проверка на сохранение нового объекта
            else return $newSpecificObject->id;
        }
    }

    //создание параметра конкретного места
    public static function actionAddPlaceParameter($place_id, $parameter_id, $parameter_type_id)
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

    //метод удаления параметров
    public function actionDeleteSpecificParameter()
    {
        $paramsArray = array();
        $errors = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 91)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['action_type']) and $post['action_type'] != "" and
                    isset($post['specific_object_id']) and $post['specific_object_id'] != "") {

                    $actionType = $post['action_type'];
                    $specificObjectId = $post['specific_object_id'];

                    if ($actionType == "local") {
                        if (isset($post['specific_parameter_id']) and $post['specific_parameter_id'] != "") {
                            $specificParameterId = $post['specific_parameter_id'];
                            PlaceParameterSensor::deleteAll(['place_parameter_id' => $specificParameterId]);
                            PlaceParameterHandbookValue::deleteAll(['place_parameter_id' => $specificParameterId]);
                            PlaceParameterValue::deleteAll(['place_parameter_id' => $specificParameterId]);
                            PlaceParameter::deleteAll(['id' => $specificParameterId]);
                            $paramsArray = $this->buildSpecificParameterArray($specificObjectId, 'place');
                        } else {
                            $errors[] = "Не передан place_parameter_id";
                        }
                    } else {
                        if (isset($post['parameter_id']) and $post['parameter_id'] != "") {
                            $parameterId = $post['parameter_id'];
                            $parameters = PlaceParameter::find()->where(['parameter_id' => $parameterId])->all();
                            foreach ($parameters as $parameter) {
                                PlaceParameterSensor::deleteAll(['place_parameter_id' => $parameter->id]);
                                PlaceParameterHandbookValue::deleteAll(['place_parameter_id' => $parameter->id]);
                                PlaceParameterValue::deleteAll(['place_parameter_id' => $parameter->id]);
                            }
                            PlaceParameter::deleteAll(['parameter_id' => $parameterId, 'place_id' => $specificObjectId]);
                            $paramsArray = $this->buildSpecificParameterArray($specificObjectId, 'place');
                        } else {
                            $errors[] = "не передан parameter_id";
                        }
                    }
                } else {
                    $errors[] = "не передан action_type или specific_object_id";
                }
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        $result = array('paramArray' => $paramsArray, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    //добавление нового параметра места из страницы фронтэнда
    public function actionAddPlaceParameterOne()
    {

        $errors = array();
        $paramsArray = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 90)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['id']) && isset($post['parameter_id']) && isset($post['parameter_type_id'])) {
                    $specific_id = $post['id'];
                    $parameter_id = $post['parameter_id'];
                    $parameter_type_id = $post['parameter_type_id'];

                    $place_parameter = self::actionAddPlaceParameter($specific_id, $parameter_id, $parameter_type_id);
                    if ($place_parameter == -1) $errors[] = "не удалось сохранить параметр";
                    $paramsArray = parent::buildSpecificParameterArray($specific_id, 'place');
                }
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        $result = array('paramArray' => $paramsArray, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    //сохранение справочного значения конкретного параметра места
    public static function actionAddPlaceParameterHandbookValue($place_parameter_id, $value, $status_id, $date_time)
    {
        $place_parameter_handbook_value = new PlaceParameterHandbookValue();
        $place_parameter_handbook_value->place_parameter_id = $place_parameter_id;
        if ($date_time == 1) $place_parameter_handbook_value->date_time = date("Y-m-d H:i:s", strtotime("-1 second"));
        else $place_parameter_handbook_value->date_time = $date_time;
        $place_parameter_handbook_value->value = strval($value);
        $place_parameter_handbook_value->status_id = $status_id;

        if (!$place_parameter_handbook_value->save()) {
            return (-1);
        } else return 1;
    }

    //сохраняет базовые Текстовые параметры в базовый справочник
    public function actionUpdatePlaceValuesString($specific_id, $name_field, $value)
    {
        $place_update = Place::findOne(['id' => $specific_id]);
        $place_update->$name_field = (string)$value;
        if (!$place_update->save()) return -1;
        else return 1;
    }

    //сохраняет базовые Числовые параметры в базовый справочник
    public function actionUpdatePlaceValuesInt($specific_id, $name_field, $value)
    {
        $place_update = Place::findOne(['id' => $specific_id]);
        $place_update->$name_field = (int)$value;
        if (!$place_update->save()) return -1;
        else return 1;
    }

    //метод поиска части параметров типового объекта для переноса в базовую таблицу конкретного объекта
    public function actionFindTypicalParametersToPlaceBase($object_id, $parameter_id, $parameter_type_id)
    {
        if ($parameter_type_id == 1) {
            $object_parameter_id = TypeObjectParameter::find()//ищем параметр тпового объекта
            ->where(['object_id' => $object_id, 'parameter_id' => $parameter_id, 'parameter_type_id' => $parameter_type_id])
                ->one();
            if ($object_parameter_id) {
                $object_id = TypeObjectParameterHandbookValue::find()//ищем последнее значение типового объекта
                ->where(['type_object_parameter_id' => $object_parameter_id])
                    ->orderBy(['date_time' => SORT_DESC])
                    ->one()
                    ->value;
                if ($object_id) return $object_id;
                else return -1;
            } else return -1;
        } else return -1;


    }

    /**
     * Метод поиска конкретных объектов для объекта "Горная среда"
     */
    public function actionSearchMountainEnvironment()
    {
        $post = Yii::$app->request->post();                                                                             // массив для получения ajaх-запросов
        $errors = array();

        $specific_object_array = array();
        $typical_objects = array();
        $object_types = array();
        $empty_title = false;
        $search_query = "";
        $f_title = "";
        $eq_title = "";
        if (isset($post['title']) and $post['title'] != "") {
            $search_query = strval($post['title']);
            $specific_object_array = (new Query())
                ->select('main_id as id, title, object_id, table_address as table_name')
                ->from('view_search_mountain')
                ->where("title like '%" . $search_query . "%'")
                ->andWhere('kind_object_id = 2')
                ->orderBy("object_id asc")
                ->all();
            $f_title = count($specific_object_array);
            $j = -1;
            foreach ($specific_object_array as $specific_object) {
                if ($specific_object['table_name'] === 'place') {
                    $edges_array = (new Query())
                        ->select('id')
                        ->from('edge')
                        ->where(['place_id' => $specific_object['id']])
                        ->all();

                    foreach ($edges_array as $edge) {
                        $specific_object['edges'][] = array('id' => $edge['id'], 'table_name' => 'edge');

                    }
                }
                $object = TypicalObject::findOne($specific_object['object_id']);
                if ($j == -1 or $typical_objects[$j]['id'] != $object->id) {
                    $j++;
//                    echo $object->object_table."\n";
                    $typical_objects[$j]['id'] = $object->id;
                    $typical_objects[$j]['title'] = $object->title;
                    $typical_objects[$j]['object_type_id'] = $object->object_type_id;
                    $typical_objects[$j]['specific_objects'] = array();
                    if ($pattern = $object->getTypeObjectParameters()->where([
                        'parameter_id' => 161,
                        'parameter_type_id' => 1,
                        'object_id' => $object->id])
                        ->one()) {
                        if (isset($pattern->getTypeObjectParameterHandbookValues()->orderBy(['date_time' => SORT_DESC])->one()->value)) {
                            $typical_objects[$j]['pattern'] = $pattern->getTypeObjectParameterHandbookValues()->orderBy(['date_time' => SORT_DESC])->one()->value;
                        }
                    }
                }
                $typical_objects[$j]['specific_objects'][] = $specific_object;
                ArrayHelper::multisort($typical_objects[$j]['specific_objects'], 'title', SORT_ASC);
            }
//            print_r($typical_objects);
            ArrayHelper::multisort($typical_objects, 'object_type_id', SORT_ASC);
            $k = -1;
            foreach ($typical_objects as $object) {

                $object_type = ObjectType::findOne($object['object_type_id']);
                if ($k == -1 or $object_types[$k]['id'] != $object_type->id) {
                    $k++;
                    $object_types[$k]['id'] = $object_type->id;
                    $object_types[$k]['title'] = $object_type->title;
//                    ArrayHelper::multisort($object_types[$k]['objects'], 'title', SORT_ASC);
                    $object_types[$k]['objects'] = array();
                }
                $object_types[$k]['objects'][] = $object;
                ArrayHelper::multisort($object_types[$k]['objects'], 'title', SORT_ASC);
            }
            ArrayHelper::multisort($object_types, 'title', SORT_ASC);
        } else if ($post['title'] == "") {
            $empty_title = "true";
        } else {
            $errors[] = "не передан параметр поиска";
        }
        $result = array('errors' => $errors, 'object_types' => $object_types, 'empty_title' => $empty_title, 'count' => $f_title);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    public static function CopyTypicalParametersToSpecificPlacePublic($typical_object_id, $specific_object_id)
    {
        $debug_flag = 0;                                                                                                  //отладочный флаг
        $flag_done = 1;                                                                                                   //флаг успешного выполнения метода
        //копирование параметров справочных
        if ($type_object_parameters = TypeObjectParameter::find()->where(['object_id' => $typical_object_id, 'parameter_type_id' => 1])->all())                           //Находим все параметры типового объекта
        {
            foreach ($type_object_parameters as $type_object_parameter) {
                //создаем новый параметр у конкретного объекта
                $place_parameter_id = self::actionAddPlaceParameter($specific_object_id, $type_object_parameter->parameter_id, $type_object_parameter->parameter_type_id);

                //ищем последние справочное значения параметра типового объекта и копируем их в значение справочное конкретного объекта
                if ($place_parameter_id
                    and $typical_object_parameter_handbook_values = TypeObjectParameterHandbookValue::find()
                        ->where(['type_object_parameter_id' => $type_object_parameter->id])
                        ->orderBy(['date_time' => SORT_DESC])
                        ->one())
                    $flag_done = self::actionAddPlaceParameterHandbookValue($place_parameter_id, $typical_object_parameter_handbook_values->value, $typical_object_parameter_handbook_values->status_id, 1);
            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись справочных параметров" . "\n");

        //копирование функций типового объекта
        //находим функции типового объекта
        if ($type_object_functions = TypeObjectFunction::find()->where(['object_id' => $typical_object_id])->all()) {
            foreach ($type_object_functions as $type_object_function) {
                $place_function_id = self::actionAddPlaceFunction($specific_object_id, $type_object_function->func_id);
                if ($place_function_id == -1) $flag_done = -1;
            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись функций" . "\n");
        return $flag_done;
    }

    public function actionRead($a, $b)
    {
        echo $a * $b;
    }

    /**
     * addPlace - Метод создания места и его параметров
     * @param $place_title - название места
     * @param $mine_id - ключ шахты
     * @param $plast_id - ключ пласта
     * @return array
     */
    public static function addPlace($place_title, $mine_id, $plast_id)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $place_id = -1;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $place_param_values = [];                                                                                        // сохраненные параметры с ключами
        // Стартовая отладочная информация
        $log = new LogAmicumFront("addPlace");

        try {

            $log->addLog("Начало выполнения метода");
            $log->addData($place_title, '$place_title', __LINE__);
            $log->addData($mine_id, '$mine_id', __LINE__);


            if (is_null($place_title)) {
                throw new Exception("В исходной таблице у ветви нет названия");
            }

            $place_id = self::AddMain('place');
            if ($place_id == -1) {
                throw new Exception("Не смог сохранить модель Main для таблицы place");
            }
            $place = new Place();
            $place->id = $place_id;
            $place->title = $place_title;
            $place->mine_id = $mine_id;
            $place->object_id = 10;
            $place->plast_id = $plast_id;
            if (!$place->save()) {
                $log->addData($place->errors, '$$place_errors', __LINE__);
                throw new Exception("Не смог сохранить модель Place");
            }

            $place_params[] = array('place_id' => $place_id, 'parameter_type_id' => 1, 'parameter_id' => 162);          // название места
            $place_params[] = array('place_id' => $place_id, 'parameter_type_id' => 1, 'parameter_id' => 346);          // ключ шахты
            $place_params[] = array('place_id' => $place_id, 'parameter_type_id' => 1, 'parameter_id' => 347);          // ключ пласта


            $insert_full = Yii::$app->db->createCommand()->batchInsert('place_parameter', ['place_id', 'parameter_type_id', 'parameter_id'], $place_params)->execute();
            if ($insert_full === 0) {
                throw new Exception("Ошибка сохранения параметров места");
            }
            unset($place_params);

            $log->addLog("добавил - $insert_full - записей в таблицу place_parameter");

            $place_params = PlaceParameter::find()->where(['place_id' => $place_id, 'parameter_type_id' => 1])->asArray()->all();
            foreach ($place_params as $place_param) {
                $place_params_save[$place_param['parameter_id']] = $place_param['id'];
            }

            $log->addLog("Получил и сформировал справочник параметров мест");

            $place_param_values[] = array('place_parameter_id' => $place_params_save[162], 'date_time' => BackAssistant::GetDateNow(), 'value' => $place_title, 'status_id' => 1);          // название места
            $place_param_values[] = array('place_parameter_id' => $place_params_save[346], 'date_time' => BackAssistant::GetDateNow(), 'value' => $mine_id, 'status_id' => 1);          // название шахты
            $place_param_values[] = array('place_parameter_id' => $place_params_save[347], 'date_time' => BackAssistant::GetDateNow(), 'value' => $plast_id, 'status_id' => 1);          // название пласта

            HandbookCachedController::clearPlaceCache();
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result, 'place_param_values'=>$place_param_values, 'place_id'=> $place_id], $log->getLogAll());
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
