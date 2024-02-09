<?php

namespace frontend\controllers\positioningsystem;
//ob_start();

use frontend\models\AccessCheck;
use frontend\models\ObjectType;
use frontend\models\Plast;
use frontend\models\PlastFunction;
use frontend\models\PlastParameter;
use frontend\models\PlastParameterHandbookValue;
use frontend\models\PlastParameterSensor;
use frontend\models\PlastParameterValue;
use frontend\models\TypeObjectFunction;
use frontend\models\TypeObjectParameter;
use frontend\models\TypeObjectParameterHandbookValue;
use frontend\models\TypicalObject;
use Yii;
use yii\db\Query;
use yii\web\Response;

class SpecificMassifController extends SpecificObjectController
{

    //горный массив
    public function actionIndex()
    {
        return $this->render('index');
    }

    /*
     * Входные данные:
     * title (string) - наименование горного массива
     * object_id (int) - id типового объекта
     * Выходные данные:
    *  result (array) - массив горных массивов, массив ошибок и id горного массива
     * */
    public function actionAddSpecificObject()
    {
        $errors = array();                                                                                              //создаем массив для ошибок
        $check_title = 0;                                                                                                 //флаг проверки на существование такого названия в базе
        $check_input_parameters = 1;                                                                                      //флаг проверки входных параметров
        $flag_done = 0;                                                                                                   //флаг успешности выполнения
        $debug_flag = 0;                                                                                                  //отладочный флаг
        $specific_title = "";
        $kind_id = null;
        $object_type_id = null;
        $object_id = null;
        $main_specific_id = null;
        $specific_array = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 86)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();                                                                             //получение данных от ajax-запроса
                if (isset($post['title']) and $post['title'] != "") {                                                    //проверка на наличие входных данных а именно на наличие такого названия
                    $specific_title = $post['title'];                                                                           //название нового конкретного объекта, который создаем
                    $sql_filter = 'title="' . $specific_title . '"';
                    $plasts = (new Query())//запрос напрямую из базы по таблице Plast
                    ->select(
                        [
                            'id',
                            'title'
                        ])
                        ->from(['plast'])
                        ->where($sql_filter)
                        ->one();
                    if ($plasts) {
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


                if ($check_input_parameters == 1 and $check_title == 1) {                                                            //все нужные входные данные есть и название не существует в базе
                    if ($debug_flag == 1) echo nl2br("----вход в выполнение функции по добавлению объекта =" . "\n");


                    $main_specific_id = $this->actionAddPlast($specific_title, $object_id);                                     //создаем/изменяем запись с таблице Plast
                    if ($debug_flag == 1) echo nl2br("----создан горный массив =" . $main_specific_id . "\n");

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
                            $plast_parameter_id = $this->actionAddPlastParameter($main_specific_id, 162, 1); //параметр наименование
                            $plast_parameter_value = $this->actionAddPlastParameterHandbookValue($plast_parameter_id, $specific_title, 1, date("Y-m-d H:i:s"));//сохранение значения параметра
                            if ($plast_parameter_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 162";
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

    /*
     * метод удаления трубы
     * Входные данные
     * specific_id
     * kind_object_id
     * object_type_id
     * object_id
     *
     * */
    public function actionDeleteSpecificObject()
    {
        $debug_flag = 1;
        $errors = array();
        $toDelete = true;                                                                                               //переменная-флажок разрешающая удаление
        $objectKinds = null;
        $specificObjects = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 88)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();                                                                             //получение данных от ajax-запроса
                if (isset($post['specific_id']) and $post['specific_id'] != "" and
                    isset($post['kind_object_id']) and $post['kind_object_id'] != "" and
                    isset($post['object_type_id']) and $post['object_type_id'] != "" and
                    isset($post['object_id']) and $post['object_id'] != "") {                                                     //если все данные переданы
                    if ($debug_flag == 1) $toDelete = true;
                    $specific_id = $post['specific_id'];
                    $kind_object_id = $post['kind_object_id'];
                    $type_object_id = $post['object_type_id'];
                    $object_id = $post['object_id'];
                    $specificObject = Plast::findOne($specific_id);
                    if ($specificObject) {                                                                                        //если объект существует
                        if ($toDelete) {
                            PlastFunction::deleteAll(['plast_id' => $specific_id]);                                             //удаляем функции у сенсора

                            $specific_parameters = PlastParameter::findAll(['plast_id' => $specific_id]);                       //ищем параметры на удаление
                            foreach ($specific_parameters as $specific_parameter) {
                                PlastParameterValue::deleteAll(['plast_parameter_id' => $specific_parameter->id]);              //удаляем измеренные или вычесленные значения
                                PlastParameterHandbookValue::deleteAll(['plast_parameter_id' => $specific_parameter->id]);      //удаляем справочные значения
                                PlastParameter::deleteAll(['id' => $specific_parameter->id]);                                   //удаляем сам параметр сенсора
                            }
                            Plast::deleteAll(['id' => $specific_id]);                                                           //удаляем сам сенсор
                        } else $errors[] = "Нельзя удалить объект из-за наличия значений у параметров объекта " . $specific_id;
                    }
                    $specificObjects = parent::buildSpecificObjectArray($kind_object_id, $type_object_id, $object_id);              //построение списка типовых объектов
                } else {
                    $errors[] = "Данные не переданы";
                }                                                                          //сохранить соответствующую ошибку
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";$this->redirect('/' );
        }
        $result = array('errors' => $errors, 'specificObjects' => $specificObjects);                                    //составить результирующий массив как массив полученных массивов
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;                                                                            //вернуть AJAX-запросу данные и ошибки
    }

    /*
    * метод перемещения трубы
     * Входные данные:
     * specific_id
     * kind_object_id
     * object_type_id
     * object_id
     * new_object_id
    * */
    public function actionMoveSpecificObject()
    {
        $errors = array();
        $objectKinds = array();
        $newObjectKinds = array();

        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 89)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['specific_id']) && isset($post['kind_object_id']) && isset($post['object_type_id'])
                    && isset($post['object_id']) && isset($post['new_object_id'])) {      //если все данные переданы

                    $specific_id = $post['specific_id'];                                                                          //айдишник конкретного объекта
                    $object_id = $post['object_id'];                                                                              //старый айдишник типового объекта
                    $new_object_id = $post['new_object_id'];                                                                      //новый айдишник типового объекта
                    $kind_object_id = $post['kind_object_id'];                                                                    //вид типового объекта
                    $object_type_id = $post['object_type_id'];                                                                    //Айдишник типа типового объекта

                    $specificObject = Plast::findOne($specific_id);
                    $specificObject->object_id = $new_object_id;
                    if ($specificObject->save()) {
                        $objectKinds = parent::buildSpecificObjectArray($kind_object_id, $object_type_id, $object_id);
                        $newObjectKinds = parent::buildSpecificObjectArray($kind_object_id, $object_type_id, $new_object_id);
                    } else {
                        $errors[] = "Не удалось переместить объект";
                    }
                } else {
                    $errors[] = "Данные не переданы";
                }//если не переданы сохранить соответствующую ошибку
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";$this->redirect('/' );
        }
        $result = array('errors' => $errors, 'specificObjects' => $objectKinds, 'newSpecificObjects' => $newObjectKinds);   //составить результирующий массив как массив полученных массивов
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;                                                                            //вернуть AJAX-запросу данные и ошибки
    }

    /*
    * функция редактирования конкретных объектов
     * Входные данные:
     * title
     * specific_id
     * kind_object_id
     * object_type_id
     * object_id
    * */
    public function actionEditSpecificObject()
    {
        $errors = array();
        $objectKinds = null;
        $specificObjects = array();
        $specificParameters = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 87)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['title']) && isset($post['specific_id'])
                    && isset($post['kind_object_id']) && isset($post['object_type_id']) && isset($post['object_id'])) {          //проверка на передачу данных

                    $new_object_title = $post['title'];                                                                           //название конкретного объекта - новое
                    $specific_id = $post['specific_id'];                                                                          //айдишник конкретного объекта
                    $object_id = $post['object_id'];                                                                              //старый айдишник типового объекта
                    $kind_object_id = $post['kind_object_id'];                                                                    //вид типового объекта
                    $object_type_id = $post['object_type_id'];                                                                    //Айдишник типа типового объекта

                    $object = Plast::findOne($specific_id);                                                                     //найти объект по id
                    if ($object) {                                                                                                //если объект существует
                        $existingObject = Plast::findOne(['title' => $new_object_title]);                                         //найти объект по названию, чтобы не было дублирующих
                        if (!$existingObject) {                                                                                   //если не найден
                            $object->title = $new_object_title;                                                                            //сохранить в найденный по id параметр название
                            if ($object->save()) {                                                                                //если объет сохранился
                                $plast_parameter_id = $this->actionAddPlastParameter($specific_id, 162, 1); //параметр наименование
                                $plast_parameter_value = $this->actionAddPlastParameterHandbookValue($plast_parameter_id, $new_object_title);//сохранение значения параметра
                                if ($plast_parameter_id == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 162";
                                $specificObjects = parent::buildSpecificObjectArray($kind_object_id, $object_type_id, $object_id);//обновить массив типовых объектов
                                $specificParameters = parent::buildSpecificParameterArray($specific_id, "plast");
                            } else $errors[] = "Ошибка сохранения";                                                               //если не сохранился, сохранить соответствующую ошибку
                        } else $errors[] = "Объект с таким названием уже существует";                                             //если найден объект по названию, сохранить соответствующую ошибку
                    } else $errors[] = "Объекта с id " . $specific_id . " не существует";                                             //если не найден объект по id, сохранить соответствующую ошибку
                } else $errors[] = "Данные не переданы";                                                                           //если не заданы входные параметры сохранить соответствующую ошибку
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";$this->redirect('/' );
        }
        $result = array('errors' => $errors, 'specificObjects' => $specificObjects, "specificParameters" => $specificParameters);                                    //составить результирующий массив как массив полученных массивов
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //вернуть AJAX-запросу данные и ошибки
        Yii::$app->response->data = $result;
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
        $objectParameters = null;         //название таблицы в которую пишем
        $objects = array();
        $session = Yii::$app->session;
        $session->open();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 92)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['parameter_values_array']) && isset($post['specific_object_id'])) {

                    $parameterValues = json_decode($post['parameter_values_array'], true);                                                         //массив параметров и их значений
                    $specific_id = $post['specific_object_id'];                                                                 //айдишник конкретного объекта
                    $object_id = Plast::findOne(['id' => $specific_id])->object_id;
                    $object_type_id = TypicalObject::findOne(['id' => $object_id])->object_type_id;
                    $object_kind_id = ObjectType::findOne(['id' => $object_type_id])->kind_object_id;
                    if ($parameterValues) {
                        foreach ($parameterValues as $parameter) {
                            if ($parameter['parameterValue'] != "") {
                                if ($parameter['parameterStatus'] == 'sensor') {
                                    if (isset($parameter['parameterValue'])) {
                                        $sensor_parameter_sensor = new PlastParameterSensor();                                     //записываем значения параметров со вкладки измеренные
                                        $sensor_parameter_sensor->plast_parameter_id = (int)$parameter['specificParameterId'];
                                        $sensor_parameter_sensor->date_time = date("Y-m-d H:i:s");
                                        $sensor_parameter_sensor->sensor_id = (int)$parameter['parameterValue'];                    //айди сенсора - получилась рекурсия по базе, но принцип не страшно
//                                var_dump($sensor_parameter_sensor);
                                        if (!$sensor_parameter_sensor->save()) {                                                    //если не сохранилась
                                            $errors[] = "Измеряемое значение " . $parameter['specificParameterId'] . " не сохранено. Идентификатор объекта " . $specific_id;//сохранить соответствующую ошибку
                                        }
                                    }
                                } else if ($parameter['parameterStatus'] == 'place' || $parameter['parameterStatus'] == 'edge') {
                                    $specific_parameter_handbook_value = new PlastParameterValue();                      //создать новое значение справочного параметра
                                    $specific_parameter_handbook_value->plast_parameter_id = (int)$parameter['specificParameterId'];
                                    $specific_parameter_handbook_value->date_time = date("Y-m-d H:i:s");
                                    $specific_parameter_handbook_value->value = (string)$parameter['parameterValue'];             //сохранить новое значение, текущую метку времени, типовой параметр и статус
                                    $specific_parameter_handbook_value->status_id = 1;
                                    if (!$specific_parameter_handbook_value->save()) {//если не сохранилась
                                        $errors[] = "значение параметра " . $parameter['parameterId'] . " не сохранено. specificParameterId = " . $parameter['specificParameterId'] . "Идентификатор объекта " . $specific_id;//сохранить соответствующую ошибку
                                    }
                                } else if ($parameter['parameterStatus'] == 'handbook') {
                                    $specific_parameter_handbook_value = new PlastParameterHandbookValue();                      //создать новое значение справочного параметра
                                    $specific_parameter_handbook_value->plast_parameter_id = (int)$parameter['specificParameterId'];
                                    $specific_parameter_handbook_value->date_time = date("Y-m-d H:i:s");
                                    $specific_parameter_handbook_value->value = (string)$parameter['parameterValue'];             //сохранить новое значение, текущую метку времени, типовой параметр и статус
                                    $specific_parameter_handbook_value->status_id = 1;
                                    if (!$specific_parameter_handbook_value->save()) {//если не сохранилась
                                        $errors[] = "Справочное значение " . $parameter['specificParameterId'] . " не сохранено. Идентификатор объекта " . $specific_id;//сохранить соответствующую ошибку
                                    }
                                    //сохраняем значение параметров в базовые справочники объекта
                                    if ($parameter['parameterId'] == 162) {                                                    //параметр наименование
                                        $plast_value = $this->actionUpdatePlastValuesString($specific_id, "title", $parameter['parameterValue']);
                                        if ($plast_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 162";
                                    }
                                    if ($parameter['parameterId'] == 274) {                                                    //параметр тип объекта
                                        $plast_value = $this->actionUpdatePlastValuesInt($specific_id, "object_id", $parameter['parameterValue']);
                                        if ($plast_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 274";
                                    }
                                } else if ($parameter['parameterStatus'] == 'manual') {
                                    $specific_parameter_handbook_value = new PlastParameterValue();                      //создать новое значение справочного параметра
                                    $specific_parameter_handbook_value->plast_parameter_id = (int)$parameter['specificParameterId'];
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
                    $plast = Plast::findOne($specific_id);//найти объект
                    if ($plast) {//если найден, то построить массив объектов, если нет, то сохранить ошибку
                        $objectParameters = parent::buildSpecificParameterArray($specific_id, 'plast');
                    } else {
                        $errors[] = "Объект с id " . $specific_id . " не найден";
                    }
                    $objects = parent::buildSpecificObjectArray($object_kind_id, $object_type_id, $object_id);
                } else {
                    $errors[] = "Данные не переданы";
                }//сохранить соответствующую ошибку
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";$this->redirect('/' );
        }
        $result = array('errors' => $errors, 'objectProps' => $objectParameters, 'objects' => $objects);//составить результирующий массив как массив полученных массивов
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;                                                                            //вернуть AJAX-запросу данные и ошибки
    }

    //метод копирует параметры типового параметра в параметры конкретного объекта - нужен для создания конкретного объекта по шаблону типового объекта
    private function actionCopyTypicalParametersToSpecific($typical_object_id, $specific_object_id)
    {
        $debug_flag = 0;                                                                                                  //отладочный флаг
        $flag_done = 1;                                                                                                   //флаг успешного выполнения метода
        if ($debug_flag == 1) {
            echo nl2br("зашли в функцию копирования параметров из типового объекта" . "\n");
        }
        //копирование параметров справочных
        if ($type_object_parameters = TypeObjectParameter::find()->where(['object_id' => $typical_object_id, 'parameter_type_id' => 1])->all())                           //Находим все параметры типового объекта
        {
            if ($debug_flag == 1) {
                echo nl2br("зашли в условие, когда найдены параметры типового объекта" . "\n");
            }
            foreach ($type_object_parameters as $type_object_parameter) {
                //создаем новый параметр у конкретного объекта
                $plast_parameter_id = $this->actionAddPlastParameter($specific_object_id, $type_object_parameter->parameter_id, $type_object_parameter->parameter_type_id);

                //ищем последние справочное значения параметра типового объекта и копируем их в значение справочное конкретного объекта
                if ($plast_parameter_id
                    and $typical_object_parameter_handbook_values = TypeObjectParameterHandbookValue::find()
                        ->where(['type_object_parameter_id' => $type_object_parameter->id])
                        ->orderBy(['date_time' => SORT_DESC])
                        ->one())
                    $flag_done = $this->actionAddPlastParameterHandbookValue($plast_parameter_id, $typical_object_parameter_handbook_values->value, $typical_object_parameter_handbook_values->status_id);
            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись справочных параметров" . "\n");

        //копирование функций типового объекта
        //находим функции типового объекта
        if ($type_object_functions = TypeObjectFunction::find()->where(['object_id' => $typical_object_id])->all()) {
            foreach ($type_object_functions as $type_object_function) {
                $plast_function_id = $this->actionAddPlastFunction($specific_object_id, $type_object_function->func_id);
                if ($plast_function_id == -1) $flag_done = -1;
            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись функций" . "\n");
        return $flag_done;
    }

    //сохранение функций трубы
    public function actionAddPlastFunction($plast_id, $function_id)
    {
        $debug_flag = 0;
        if ($debug_flag == 1) echo nl2br("----зашел в функцию создания функции трубы  =" . $plast_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        if ($plast_function = PlastFunction::find()->where(['plast_id' => $plast_id, 'function_id' => $function_id])->one()) {
            return $plast_function->id;
        } else {
            $plast_function_new = new PlastFunction();
            $plast_function_new->plast_id = $plast_id;                                                                  //айди горного массива
            $plast_function_new->function_id = $function_id;                                                            //айди функции
            //статус значения

            if ($plast_function_new->save()) return $plast_function_new->id;
            else return -1;
        }
    }

    //функция добавления функции трубы с post с фронта
    public function actionAddPlastFunctionFront()
    {
        $errors = array();
        $functionsArray = array();
        $session = Yii::$app->session;
        $session->open();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 94)) {                                       //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['specificObjectId']) && isset($post['specificObjectId']) && isset($post['functionId']) && isset($post['functionId'])) {
                    $plast_id = $post['specificObjectId'];
                    $function_id = $post['functionId'];
                    $plast_function = $this->actionAddPlastFunction($plast_id, $function_id);
                    if ($plast_function == -1) $errors[] = "не удалось сохранить параметр";
                    $functionsArray = parent::buildSpecificFunctionArray($plast_id, "plast");
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

    //обновление значения функции привязанной к конкретной трубе
    public function actionUpdatePlastFunction($quipment_function_id, $plast_id, $function_id)
    {
        $debug_flag = 0;
        if ($debug_flag == 1) echo nl2br("----зашел в функцию редактирования функции оборудования  =" . $plast_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        $plast_function_update = PlastFunction::find()->where(['id' => $quipment_function_id])->one();
        if ($plast_function_update) {
            $plast_function_update->plast_id = $plast_id;                                                                      //айди трубы
            $plast_function_update->function_id = $function_id;                                                                  //айди функции

            if ($plast_function_update->save()) {
                $functionsArray = parent::buildSpecificFunctionArray($plast_id, "plast");                     //создаем список функций на возврат
                $result = array('funcArray' => $functionsArray);
                echo json_encode($result);
                return $plast_function_update->id;
            } else return -1;
        }
    }

    //функция удаления функции трубы с post с фронта
    public function actionDeletePlastFunction()
    {
        $object_functions = array();
        $errors = array();
        $session = Yii::$app->session;
        $session->open();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 95)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['specific_object_id']) && isset($post['specific_object_id']) && isset($post['specific_function_id']) && isset($post['specific_function_id'])) {
                    $plast_id = $post['specific_object_id'];
                    $plast_function_id = $post['specific_function_id'];
                    PlastFunction::deleteAll(['id' => $plast_function_id]);


                    $objects = (new Query())
                        ->select(
                            [
                                'function_type_title functionTypeTitle',
                                'function_type_id functionTypeId',
                                'plast_function_id id',
                                'function_id',
                                'plast_id',
                                'func_title functionTitle',
                                'func_script_name scriptName'
                            ])
                        ->from(['view_plast_function'])
                        ->where('plast_id = ' . $plast_id)
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
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";$this->redirect('/' );
        }

        $result = array('objectFunctions' => $object_functions, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }


    //метод создания конкретного объекта в его базовой таблице
    public function actionAddPlast($specific_title, $object_id)
    {
        $plast_id = parent::actionAddEntryMain('plast');                                                    //создаем запись в таблице Main
        if (!is_int($plast_id)) return -1;
        else {
            $newSpecificObject = new Plast();                                                                           //сохраняем все данные в нужной модели
            $newSpecificObject->id = $plast_id;                                                                         //айдишнек новой созданной трубы
            $newSpecificObject->title = $specific_title;
            $newSpecificObject->object_id = $object_id;                                                                 //id типового объекта
            if (!$newSpecificObject->save()) return -1;                                                                 //проверка на сохранение нового объекта
            else return $newSpecificObject->id;
        }
    }

    //создание параметра конкретной трубы
    public function actionAddPlastParameter($plast_id, $parameter_id, $parameter_type_id)
    {
        $debug_flag = 0;

        if ($debug_flag == 1) echo nl2br("----зашел в функцию создания параметров кабеля  =" . $plast_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        if ($plast_parameter = PlastParameter::find()->where(['plast_id' => $plast_id, 'parameter_id' => $parameter_id, 'parameter_type_id' => $parameter_type_id])->one()) {
            if ($debug_flag == 1) {
                echo nl2br("----зашли в условие, когда найдены параметры пласта \n");
            }
            return $plast_parameter->id;
        } else {
            if ($debug_flag == 1) {
                echo nl2br("----зашли в условие, когда не найдены параметры пласта, и создается новый параметр. \n");
            }
            $plast_parameter_new = new PlastParameter();
            $plast_parameter_new->plast_id = $plast_id;                                                        //айди водоснабжения
            $plast_parameter_new->parameter_id = $parameter_id;                                                      //айди параметра
            $plast_parameter_new->parameter_type_id = $parameter_type_id;                                            //айди типа параметра

            if ($plast_parameter_new->save()) return $plast_parameter_new->id;
            else return (-1); //"Ошибка сохранения значения параметра пласта" . $plast_id->id;
        }
    }

    //метод удаления параметров
    public function actionDeleteSpecificParameter()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $errors = array();
        $paramsArray = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 91)) {                                        //если пользователю разрешен доступ к функции
                if (isset($post['action_type']) and $post['action_type'] and
                    isset($post['specific_object_id']) and $post['specific_object_id']) {

                    $actionType = $post['action_type'];
                    $specificObjectId = $post['specific_object_id'];
                    if ($actionType == "local") {
                        if (isset($post['specific_parameter_id']) and $post['specific_parameter_id']) {
                            $specificParameterId = $post['specific_parameter_id'];
                            PlastParameterSensor::deleteAll(['plast_parameter_id' => $specificParameterId]);
                            PlastParameterHandbookValue::deleteAll(['plast_parameter_id' => $specificParameterId]);
                            PlastParameterValue::deleteAll(['plast_parameter_id' => $specificParameterId]);
                            PlastParameter::deleteAll(['id' => $specificParameterId]);
                            $paramsArray = $this->buildSpecificParameterArray($specificObjectId, 'plast');
                        } else {
                            $errors[] = "не передан specific_parameter_id";
                        }
                    } else {
                        if (isset($post['parameter_id']) and $post['parameter_id']) {
                            $parameterId = $post['parameter_id'];
                            $parameters = PlastParameter::find()->where(['parameter_id' => $parameterId])->all();
                            foreach ($parameters as $parameter) {
                                PlastParameterSensor::deleteAll(['plast_parameter_id' => $parameter->id]);
                                PlastParameterHandbookValue::deleteAll(['plast_parameter_id' => $parameter->id]);
                                PlastParameterValue::deleteAll(['plast_parameter_id' => $parameter->id]);
                            }
                            PlastParameter::deleteAll(['parameter_id' => $parameterId, 'plast_id' => $specificObjectId]);
                            $paramsArray = $this->buildSpecificParameterArray($specificObjectId, 'plast');
                        } else {
                            $errors[] = "не передан parameter_id";
                        }
                    }
                } else {
                    $errors[] = "не переданы данные action_type = " . $post['action_type'] . " или specific_object_id = " . $post['specific_object_id'];
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

    //добавление нового параметра сенсора из страницы фронтэнда
    public function actionAddPlastParameterOne()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $specific_id = null;
        $errors = array();
        $paramsArray = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 90)) {                                        //если пользователю разрешен доступ к функции
                if (isset($post['id']) && isset($post['parameter_id']) && isset($post['parameter_type_id'])) {
                    $specific_id = $post['id'];
                    $parameter_id = $post['parameter_id'];
                    $parameter_type_id = $post['parameter_type_id'];

                    $plast_parameter = $this->actionAddPlastParameter($specific_id, $parameter_id, $parameter_type_id);
                    if ($plast_parameter == -1) $errors[] = "не удалось сохранить параметр";

                    $paramsArray = parent::buildSpecificParameterArray($specific_id, 'plast');
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

    //сохранение справочного значения конкретного параметра трубы
    public function actionAddPlastParameterHandbookValue($plast_parameter_id, $value, $status_id = 1, $date_time = 1)
    {
        $plast_parameter_handbook_value = new PlastParameterHandbookValue();
        $plast_parameter_handbook_value->plast_parameter_id = $plast_parameter_id;
        if ($date_time == 1) $plast_parameter_handbook_value->date_time = date("Y-m-d H:i:s", strtotime("-1 second"));
        else $plast_parameter_handbook_value->date_time = date("Y-m-d H:i:s", strtotime("-1 second"));
        $plast_parameter_handbook_value->value = strval($value);
        $plast_parameter_handbook_value->status_id = $status_id;

        if (!$plast_parameter_handbook_value->save()) {
            return (-1);
        } else return 1;
    }

    //сохраняет базовые Текстовые параметры в базовый справочник
    public function actionUpdatePlastValuesString($specific_id, $name_field, $value)
    {
        $plast_update = Plast::findOne(['id' => $specific_id]);
        $plast_update->$name_field = (string)$value;
        if (!$plast_update->save()) return -1;
        else return 1;
    }

    //сохраняет базовые Числовые параметры в базовый справочник
    public function actionUpdatePlastValuesInt($specific_id, $name_field, $value)
    {
        $plast_update = Plast::findOne(['id' => $specific_id]);
        $plast_update->$name_field = (int)$value;
        if (!$plast_update->save()) return -1;
        else return 1;
    }

    //метод поиска части параметров типового объекта для переноса в базовую таблицу конкретного объекта
    public function actionFindTypicalParametersToPlast($object_id, $parameter_id, $parameter_type_id = 1)
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

    //метод притягивания труб и оборудования для заполнения поля main_to/from_id
    public function actionGetSensorEquipment()
    {
        $objects = (new Query())//запрос напрямую из базы по вьюшке view_personal_areas
        ->select(
            [
                'main_id',
                'object_title'
            ])
            ->from(['view_main_object_energy'])
            ->all();
        echo json_encode($objects);
        return $objects;
    }


}
