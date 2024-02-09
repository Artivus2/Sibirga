<?php

namespace frontend\controllers\positioningsystem;
//ob_start();

use frontend\models\AccessCheck;
use frontend\models\ObjectType;
use frontend\models\PpsMine;
use frontend\models\PpsMineFunction;
use frontend\models\PpsMineParameter;
use frontend\models\PpsMineParameterHandbookValue;
use frontend\models\PpsMineParameterSensor;
use frontend\models\PpsMineParameterValue;
use frontend\models\TypeObjectFunction;
use frontend\models\TypeObjectParameter;
use frontend\models\TypeObjectParameterHandbookValue;
use frontend\models\TypicalObject;
use Yii;
use yii\db\Query;
use yii\web\Response;

class SpecificPpsController extends SpecificObjectController
{
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
        $specific_array = array();
        $kind_id = null;
        $specific_title = null;
        $object_id = null;
        $main_from_id = null;
        $main_specific_id = null;
        $main_to_id = null;
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 86)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();                                                                             //получение данных от ajax-запроса
                if (isset($post['title']) and $post['title'] != "" and
                    isset($post['main_from_id']) and $post['main_from_id'] != "" and
                    isset($post['main_to_id']) and $post['main_to_id'] != "") {                                                    //проверка на наличие входных данных а именно на наличие такого названия
                    $specific_title = $post['title'];                                                                           //название нового конкретного объекта, который создаем
                    $main_from_id = $post['main_from_id'];                                                                      //название нового конкретного объекта, который создаем
                    $main_to_id = $post['main_to_id'];                                                                          //название нового конкретного объекта, который создаем
                    $sql_filter = 'title="' . $specific_title . '"';
                    $pps_mines = (new Query())//запрос напрямую из базы по таблице PpsMine
                    ->select(
                        [
                            'id',
                            'title'
                        ])
                        ->from(['pps_mine'])
                        ->where($sql_filter)
                        ->one();
                    if ($pps_mines) {
                        $errors[] = "Объект с именем " . $specific_title . " уже существует";
                        $check_title = -1;
                    } else $check_title = 1;                                                                                        //название не существует в базе, можно добавлять объект
                    if ($debug_flag == 1) echo nl2br("----прошел проверку на наличие такого тега (1 тега нет)  =" . $check_title . "\n");
                } else {
                    $errors[] = "Не все входные данные есть в методе POST";
                    $check_input_parameters = -1;
                    if ($debug_flag == 1) echo nl2br("----проверка на наличие тега /проверка на наличие входных данных 1 входные данные есть =" . $check_input_parameters . "\n");
                }

                if (isset($post['object_id']) and $post['object_id'] != "") {                                                          //проверка на наличие входных данных а именно на наличие типового объекта, который копируется
                    $object_id = $post['object_id'];                                                                            //айдишник типового объекта
                    $sql_filter = 'object_id=' . $object_id . '';
                    $typical_objects = (new Query())//запрос напрямую из базы по вьюшке view_type_object
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
                } else {
                    $errors[] = "Не все входные данные есть в методе POST";
                    $check_input_parameters = -1;
                    if ($debug_flag == 1) echo nl2br("----проверка на наличие типового объекта входные данные есть =" . $check_input_parameters . "\n");
                }


                if ($check_input_parameters == 1 and $check_title == 1) {                                                             //все нужные входные данные есть и название не существует в базе
                    if ($debug_flag == 1) echo nl2br("----вход в выполнение функции по добавлению объекта =" . "\n");


                    $main_specific_id = $this->actionAddPpsMine($specific_title, $object_id, $main_from_id, $main_to_id);    //создаем/изменяем запись с таблице PpsMine
                    if ($debug_flag == 1) echo nl2br("----создана труба =" . $main_specific_id . "\n");

                    if ($main_specific_id == -1) {
                        $errors[] = "Ошибка сохранения электрической продукции в базовой таблице:" . $specific_title;
                        if ($debug_flag == 1) echo nl2br("----зашел в ошибку. mainspecificid  = 1 " . $main_specific_id . "\n");
                    } else {                                                                                                    //если сохранили электропродукцию, то копируем справочные значения и функции типового объекта
                        $flag_done = $this->actionCopyTypicalParametersToSpecific($object_id, $main_specific_id);
                        if ($flag_done == -1) {
                            $errors[] = "Ошибка копирования параметров и значений типового объекта в конкретный:" . $main_specific_id;
                            if ($debug_flag == 1) echo nl2br("----ошибка копирования параметров типового объекта в конкретные =" . $main_specific_id . "\n");
                        } else {
                            //сохраняем значения параметров из базовой таблицы в параметры базового объекта
                            $pps_mine_parameter_id = $this->actionAddPpsMineParameter($main_specific_id, 162, 1); //параметр наименование
                            $pps_mine_parameter_value = $this->actionAddPpsMineParameterHandbookValue($pps_mine_parameter_id, $specific_title, 1, date("Y-m-d H:i:s"));//сохранение значения параметра
                            if ($pps_mine_parameter_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 162";
                            if ($debug_flag == 1) echo nl2br("----зашел в else. начал сохранять параметры " . $main_specific_id . "\n");
                        }
                    }
                    $specific_array = parent::buildSpecificObjectArray($kind_id, $object_type_id, $object_id);//вызываем функция построения массива конкретных объектов нужного типа
                } else {
                    $errors[] = "Объект с именем " . $specific_title . " уже существует";
                }
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";$this->redirect('/' );
        }
        $result = array('specificArray' => $specific_array, 'errors' => $errors, 'specific_id' => $main_specific_id);//создаем массив для передачи данных по ajax запросу со значениями и ошибками
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;

    }
    //метод создания конкретного объекта в его базовой таблице
    public function actionAddPpsMine($specific_title, $object_id, $main_from_id, $main_to_id)
    {
        $pps_mine_id = parent::actionAddEntryMain('pps_mine');                                              //создаем запись в таблице Main
        if (!is_int($pps_mine_id)) return -1;
        else {
            $newSpecificObject = new PpsMine();                                                                      //сохраняем все данные в нужной модели
            $newSpecificObject->id = $pps_mine_id;                                                                   //айдишнек новой созданной трубы
            $newSpecificObject->title = $specific_title;
            $newSpecificObject->object_id = $object_id;                                                                 //id типового объекта
            $newSpecificObject->main_from_id = $main_from_id;                                                           //из какого объекта
            $newSpecificObject->main_to_id = $main_to_id;                                                               //к какому объекту
            if (!$newSpecificObject->save()) return -1;                                                                 //проверка на сохранение нового объекта
            else return $newSpecificObject->id;
        }
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
                $pps_mine_parameter_id = $this->actionAddPpsMineParameter($specific_object_id, $type_object_parameter->parameter_id, $type_object_parameter->parameter_type_id);

                //ищем последние справочное значения параметра типового объекта и копируем их в значение справочное конкретного объекта
                if ($pps_mine_parameter_id
                    and $typical_object_parameter_handbook_values = TypeObjectParameterHandbookValue::find()
                        ->where(['type_object_parameter_id' => $type_object_parameter->id])
                        ->orderBy(['date_time' => SORT_DESC])
                        ->one())
                    $flag_done = $this->actionAddPpsMineParameterHandbookValue($pps_mine_parameter_id, $typical_object_parameter_handbook_values->value, $typical_object_parameter_handbook_values->status_id);
            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись справочных параметров" . "\n");

        //копирование функций типового объекта
        //находим функции типового объекта
        if ($type_object_functions = TypeObjectFunction::find()->where(['object_id' => $typical_object_id])->all()) {
            foreach ($type_object_functions as $type_object_function) {
                $pps_mine_function_id = $this->actionAddPpsMineFunction($specific_object_id, $type_object_function->func_id);
                if ($pps_mine_function_id == -1) $flag_done = -1;
            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись функций" . "\n");
        return $flag_done;
    }

    //создание параметра конкретной трубы
    public function actionAddPpsMineParameter($pps_mine_id, $parameter_id, $parameter_type_id)
    {
        $debug_flag = 0;

        if ($debug_flag == 1) echo nl2br("----зашел в функцию создания параметров трубы  =" . $pps_mine_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        if ($pps_mine_parameter = PpsMineParameter::find()->where(['pps_mine_id' => $pps_mine_id, 'parameter_id' => $parameter_id, 'parameter_type_id' => $parameter_type_id])->one()) {
            return $pps_mine_parameter->id;
        }
        $pps_mine_parameter_new = new PpsMineParameter();
        $pps_mine_parameter_new->pps_mine_id = $pps_mine_id;                                                        //айди водоснабжения
        $pps_mine_parameter_new->parameter_id = $parameter_id;                                                      //айди параметра
        $pps_mine_parameter_new->parameter_type_id = $parameter_type_id;                                            //айди типа параметра

        if ($pps_mine_parameter_new->save()) return $pps_mine_parameter_new->id;
        else return (-1); //"Ошибка сохранения значения параметра водоснабжения" . $pps_mine_id->id;

    }

    //сохранение справочного значения конкретного параметра трубы
    public function actionAddPpsMineParameterHandbookValue($pps_mine_parameter_id, $value, $status_id = 1, $date_time = 1)
    {
        $pps_mine_parameter_handbook_value = new PpsMineParameterHandbookValue();
        $pps_mine_parameter_handbook_value->pps_mine_parameter_id = $pps_mine_parameter_id;
        if ($date_time == 1) $pps_mine_parameter_handbook_value->date_time = date("Y-m-d H:i:s");
        else $pps_mine_parameter_handbook_value->date_time = $date_time;
        $pps_mine_parameter_handbook_value->value = strval($value);
        $pps_mine_parameter_handbook_value->status_id = $status_id;

        if (!$pps_mine_parameter_handbook_value->save()) {
            return (-1);
        } else return 1;
    }

    //сохранение функций трубы
    public function actionAddPpsMineFunction($pps_mine_id, $function_id)
    {
        $debug_flag = 0;
        if ($debug_flag == 1) echo nl2br("----зашел в функцию создания функции трубы  =" . $pps_mine_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        if ($pps_mine_function = PpsMineFunction::find()->where(['pps_mine_id' => $pps_mine_id, 'function_id' => $function_id])->one()) {
            return $pps_mine_function->id;
        } else {
            $pps_mine_function_new = new PpsMineFunction();
            $pps_mine_function_new->pps_mine_id = $pps_mine_id;                                                                  //айди трубы
            $pps_mine_function_new->function_id = $function_id;                                                            //айди функции
            //статус значения

            if ($pps_mine_function_new->save()) return $pps_mine_function_new->id;
            else return -1;
        }
    }

    //добавление нового параметра сенсора из страницы фронтэнда
    public function actionAddPpsMineParameterOne()
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
                    $pps_mine_parameter = $this->actionAddPpsMineParameter($specific_id, $parameter_id, $parameter_type_id);
                    if ($pps_mine_parameter == -1) $errors[] = "не удалось сохранить параметр";
                    $paramsArray = parent::buildSpecificParameterArray($specific_id, 'pps_mine');

                } else {
                    $errors[] = "Не все данные переданы";
                }
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";$this->redirect('/' );
        }
        $result = array('paramArray' => $paramsArray, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /*
 * функция сохранения значений с вкладки
 * $post['table_name'] - имя таблицы
 * $post['parameter_values_array'] - массив значений
 * $post['specificObjectId'] - id конкретного объекта
 * */
    // TODO: не сохраняет справочные параметры
    public function actionSaveSpecificParametersValues()
    {
        $errors = array();
        $specific_parameters = array();
        $objects = array();
        $session = Yii::$app->session;
        $session->open();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 92)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['parameter_values_array']) && isset($post['specific_object_id'])) {

                    $parameterValues = json_decode($post['parameter_values_array'], true);                                //массив параметров и их значений
                    $specific_id = $post['specific_object_id'];                                                                 //айдишник конкретного объекта
                    $object_id = PpsMine::findOne(['id' => $specific_id])->object_id;
                    $object_type_id = TypicalObject::findOne(['id' => $object_id])->object_type_id;
                    $object_kind_id = ObjectType::findOne(['id' => $object_type_id])->kind_object_id;
                    if ($parameterValues) {
                        foreach ($parameterValues as $parameter) {
                            if ($parameter['parameterValue'] != "") {
                                if ($parameter['parameterStatus'] == 'sensor') {
                                    if (isset($parameter['parameterValue']) && $parameter['parameterValue'] != -1) {
                                        $pps_parameter_sensor = new PpsMineParameterSensor();                                     //записываем значения параметров со вкладки измеренные
                                        $pps_parameter_sensor->pps_mine_parameter_id = (int)$parameter['specificParameterId'];
                                        $pps_parameter_sensor->date_time = date("Y-m-d H:i:s");
                                        $pps_parameter_sensor->sensor_id = (int)$parameter['parameterValue'];                    //айди сенсора - получилась рекурсия по базе, но принцип не страшно
//                                var_dump($pps_parameter_sensor);
                                        if (!$pps_parameter_sensor->save()) {                                                    //если не сохранилась
                                            $errors[] = "Измеряемое значение " . $parameter['specificParameterId'] . " не сохранено. Идентификатор объекта " . $specific_id;//сохранить соответствующую ошибку
                                        }
                                    }
                                } else if ($parameter['parameterStatus'] == 'place' || $parameter['parameterStatus'] == 'edge') {
                                    $pps_parameter_handbook_value = new PpsMineParameterValue();                      //создать новое значение справочного параметра
                                    $pps_parameter_handbook_value->pps_mine_parameter_id = (int)$parameter['specificParameterId'];
                                    $pps_parameter_handbook_value->date_time = date("Y-m-d H:i:s");
                                    $pps_parameter_handbook_value->value = (string)$parameter['parameterValue'];             //сохранить новое значение, текущую метку времени, типовой параметр и статус
                                    $pps_parameter_handbook_value->status_id = 1;
                                    if (!$pps_parameter_handbook_value->save()) {//если не сохранилась
                                        $errors[] = "значение параметра " . $parameter['parameterId'] . " не сохранено. specificParameterId = " . $parameter['specificParameterId'] . "Идентификатор объекта " . $specific_id;//сохранить соответствующую ошибку
                                    }
                                } else if ($parameter['parameterStatus'] == 'handbook') {
                                    $pps_parameter_handbook_value = new PpsMineParameterHandbookValue();                      //создать новое значение справочного параметра
                                    $pps_parameter_handbook_value->pps_mine_parameter_id = (int)$parameter['specificParameterId'];
                                    $pps_parameter_handbook_value->date_time = date("Y-m-d H:i:s");
                                    $pps_parameter_handbook_value->value = (string)$parameter['parameterValue'];             //сохранить новое значение, текущую метку времени, типовой параметр и статус
                                    $pps_parameter_handbook_value->status_id = 1;
                                    if (!$pps_parameter_handbook_value->save()) {//если не сохранилась
                                        $errors[] = "Справочное значение " . $parameter['specificParameterId'] . " не сохранено. Идентификатор объекта " . $specific_id;//сохранить соответствующую ошибку
                                    }
                                    //сохраняем значение параметров в базовые справочники объекта
                                    if ($parameter['parameterId'] == 162) {                                                    //параметр наименование
                                        $pps_value = $this->actionUpdatePpsMineValuesString($specific_id, "title", $parameter['parameterValue']);
                                        if ($pps_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 162";
                                    }
                                    if ($parameter['parameterId'] == 274) {                                                    //параметр тип объекта
                                        $pps_value = $this->actionUpdatePpsMineValuesInt($specific_id, "object_id", $parameter['parameterValue']);
                                        if ($pps_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 274";
                                    }
                                } else if ($parameter['parameterStatus'] == 'manual') {
                                    $specific_parameter_handbook_value = new PpsMineParameterValue();                      //создать новое значение справочного параметра
                                    $specific_parameter_handbook_value->pps_mine_parameter_id = (int)$parameter['specificParameterId'];
                                    $specific_parameter_handbook_value->date_time = date("Y-m-d H:i:s");
                                    $specific_parameter_handbook_value->value = (string)$parameter['parameterValue'];             //сохранить новое значение, текущую метку времени, типовой параметр и статус
                                    $specific_parameter_handbook_value->status_id = 1;
                                    if (!$specific_parameter_handbook_value->save()) {//если не сохранилась
                                        $errors[] = "значение параметра " . $parameter['parameterId'] . " не сохранено. specificParameterId = " . $parameter['specificParameterId'] . "Идентификатор объекта " . $specific_id;//сохранить соответствующую ошибку
                                    }
                                }
                            }
                        }
                    }
                    $pps_mine = PpsMine::findOne($specific_id);//найти объект
                    if ($pps_mine) {//если найден, то построить массив объектов, если нет, то сохранить ошибку
                        $specific_parameters = parent::buildSpecificParameterArray($specific_id, 'pps_mine');
                    } else {
                        $errors[] = "Объект с id " . $specific_id . " не найден";
                    }
                    $objects = parent::buildSpecificObjectArray($object_kind_id, $object_type_id, $object_id);
                } else $errors[] = "Данные не переданы";//сохранить соответствующую ошибку
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";$this->redirect('/' );
        }
        $result = array('errors' => $errors, 'objectProps' => $specific_parameters, 'objects' => $objects);//составить результирующий массив как массив полученных массивов
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;                                                                            //вернуть AJAX-запросу данные и ошибки
    }

    //сохраняет базовые Текстовые параметры в базовый справочник
    public function actionUpdatePpsMineValuesString($specific_id, $name_field, $value)
    {
        $pps_mine_update = PpsMine::findOne(['id' => $specific_id]);
        $pps_mine_update->$name_field = (string)$value;
        if (!$pps_mine_update->save()) return -1;
        else return 1;
    }

    //сохраняет базовые Числовые параметры в базовый справочник
    public function actionUpdatePpsMineValuesInt($specific_id, $name_field, $value)
    {
        $pps_mine_update = PpsMine::findOne(['id' => $specific_id]);
        $pps_mine_update->$name_field = (int)$value;
        if (!$pps_mine_update->save()) return -1;
        else return 1;
    }

    //метод удаления параметров для противопожарных систем
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
                            PpsMineParameterSensor::deleteAll(['pps_mine_parameter_id' => $specificParameterId]);
                            PpsMineParameterHandbookValue::deleteAll(['pps_mine_parameter_id' => $specificParameterId]);
                            PpsMineParameterValue::deleteAll(['pps_mine_parameter_id' => $specificParameterId]);
                            PpsMineParameter::deleteAll(['id' => $specificParameterId]);
                            $paramsArray = $this->buildSpecificParameterArray($specificObjectId, 'pps_mine');
                        } else {
                            $errors[] = "Не передан pps_mine_parameter_id";
                        }
                    } else {
                        if (isset($post['parameter_id']) and $post['parameter_id'] != "") {
                            $parameterId = $post['parameter_id'];
                            $parameters = PpsMineParameter::find()->where(['parameter_id' => $parameterId])->all();
                            foreach ($parameters as $parameter) {
                                PpsMineParameterSensor::deleteAll(['pps_mine_parameter_id' => $parameter->id]);
                                PpsMineParameterHandbookValue::deleteAll(['pps_mine_parameter_id' => $parameter->id]);
                                PpsMineParameterValue::deleteAll(['pps_mine_parameter_id' => $parameter->id]);
                            }
                            PpsMineParameter::deleteAll(['parameter_id' => $parameterId, 'pps_mine_id' => $specificObjectId]);
                            $paramsArray = $this->buildSpecificParameterArray($specificObjectId, 'pps_mine');
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
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";$this->redirect('/' );
        }
        $result = array('paramArray' => $paramsArray, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    //функция добавления функции трубы с post с фронта
    public function actionAddPpsMineFunctionFront()
    {
        $errors = array();
        $functionsArray = array();
        $session = Yii::$app->session;
        $session->open();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 94)) {                                       //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['specificObjectId']) && isset($post['specificObjectId']) && isset($post['functionId']) && isset($post['functionId'])) {
                    $pps_mine_id = $post['specificObjectId'];
                    $function_id = $post['functionId'];
                    $pps_mine_function = $this->actionAddPpsMineFunction($pps_mine_id, $function_id);
                    if ($pps_mine_function == -1) $errors[] = "не удалось сохранить параметр";
                    $functionsArray = parent::buildSpecificFunctionArray($pps_mine_id, "pps_mine");

                } else {
                    $errors[] = "Данные не переданы";
                }
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";$this->redirect('/' );
        }
        $result = array('objectFunctions' => $functionsArray, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;                                                                          //вернуть AJAX-запросу данные и ошибки
    }


    //функция удаления функции трубы с post с фронта
    public function actionDeletePpsMineFunction()
    {
        $debug_flag = 0;
        $object_functions = array();
        $errors = array();
        $session = Yii::$app->session;
        $session->open();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 95)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['specific_object_id']) && $post['specific_object_id'] != "" && isset($post['specific_function_id']) && $post['specific_function_id'] != "") {
                    $pps_mine_id = $post['specific_object_id'];
                    $pps_mine_function_id = $post['specific_function_id'];
                    PpsMineFunction::deleteAll(['id' => $pps_mine_function_id]);

                    if ($debug_flag == 1) echo nl2br("----удалили связку в PpsMineFunction функцию " . $pps_mine_id . "\n");

                    $objects = (new Query())
                        ->select(
                            [
                                'function_type_title functionTypeTitle',
                                'function_type_id functionTypeId',
                                'pps_mine_function_id id',
                                'function_id',
                                'pps_mine_id',
                                'func_title functionTitle',
                                'func_script_name scriptName'
                            ])
                        ->from(['view_pps_mine_function'])
                        ->where('pps_mine_id = ' . $pps_mine_id)
                        ->orderBy("function_type_id")
                        ->all();
                    $i = -1;
                    $j = 0;
                    if ($debug_flag == 1) echo nl2br("----нашли объекты " . $pps_mine_id . "\n");

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
                $errors[] =  "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";$this->redirect('/' );
        }


        $result = array('objectFunctions' => $object_functions, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }


}