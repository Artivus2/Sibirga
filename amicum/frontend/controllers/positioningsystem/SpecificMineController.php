<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers\positioningsystem;
//ob_start();

use frontend\controllers\Assistant;
use frontend\models\Mine;
use frontend\models\MineFunction;
use frontend\models\MineParameter;
use frontend\models\MineParameterHandbookValue;
use frontend\models\MineParameterSensor;
use frontend\models\MineParameterValue;
use frontend\models\ObjectType;
use frontend\models\TypeObjectFunction;
use frontend\models\TypeObjectParameter;
use frontend\models\TypeObjectParameterHandbookValue;
use frontend\models\TypicalObject;
use Yii;
use yii\db\Query;
use yii\web\Response;

/**
 * Класс управления шахтой
 * Управление ее данными
 * Class SpecificMineController
 * @package app\controllers
 */
class SpecificMineController extends SpecificObjectController
{
    /*шахта шахтное поле*/
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод добавления шахты с параметрами
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 26.10.2018 13:45
     */
    public function actionAddSpecificObject()
    {

        $errors = array();                                                                                              //создаем массив для ошибок
        $warnings = array();                                                                                              //создаем массив для ошибок
        $post = Yii::$app->request->post();                                                                             //получение данных от ajax-запрос
        $check_title = 0;                                                                                                 //флаг проверки на существование такого названия в базе
        $specific_title = "";
        $object_id = -1;
        $company_id = -1;
        $check_input_parameters = 1;                                                                                      //флаг проверки входных параметров
        $flag_done = 0;                                                                                                   //флаг успешности выполнения
        $kind_id = -1;
        $main_specific_id = -1;
        $mine_parameter_id = -1;
        $object_type_id = -1;
        if (isset($post['title']) and $post['title'] != "") {                                                               //проверка на наличие входных данных а именно на наличие такого названия
            $specific_title = $post['title'];                                                                           //название нового конкретного объекта, который создаем
            $sql_filter = 'title="' . $specific_title . '"';
            $mines = (new Query())                                                                                      //запрос напрямую из базы по таблице Mine
            ->select(
                [
                    'id',
                    'title'
                ])
                ->from(['mine'])
                ->where($sql_filter)
                ->one();
            if ($mines) {
                $errors[] = "Объект с именем " . $specific_title . " уже существует";
                $check_title = -1;
            } else $check_title = 1;                                                                                        //название не существует в базе, можно добавлять объект
        } else {
            $errors[] = "Не все входные данные есть в методе POST";
            $check_input_parameters = -1;
        }
        if (isset($post['object_id']) and $post['object_id'] != "" and
            isset($post['company_id']) and $post['company_id'] != "") {                                                       //проверка на наличие входных данных а именно на наличие типового объекта, который копируется
            $object_id = $post['object_id'];                                                                            //айдишник типового объекта
            $sql_filter = 'object_id=' . $object_id . '';
            $company_id = $post['company_id'];
            $typical_objects = (new Query())                                                                            //запрос напрямую из базы по вьюшке view_personal_areas
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
            } else {
                $kind_id = $typical_objects["kind_object_id"];                                                          //вид типового объекта ИД
                $object_type_id = $typical_objects["object_type_id"];                                                   //тип типового объекта ИД
            }
        } else {
            $errors[] = "Не все входные данные есть в методе POST";
            $check_input_parameters = -1;
        }

        if ($check_input_parameters == 1 and $check_title == 1) {                                                             //все нужные входные данные есть и название не существует в базе
            $main_specific_id = $this->actionAddMine($specific_title, $object_id, $company_id);     //создаем/изменяем запись с таблице Mine
            if ($main_specific_id == -1) {
                $errors[] = "Ошибка сохранения шахты в базовой таблице:" . $specific_title;
            } else {                                                                                                   //если сохранили шахту, то копируем справочные значения и функции типового объекта
                $flag_done = $this->actionCopyTypicalParametersToSpecificMine($object_id, $main_specific_id);
//                var_dump($flag_done);die;
//                return -1;
                if ($flag_done == -1) {
                    $errors[] = "Ошибка копирования параметров и значений типового объекта в конкретный:" . $main_specific_id;
                } else {
                    //сохраняем значения параметров из базовой таблицы в параметры базового лбъекта
                    $response = $this->AddMineParameter($main_specific_id, 162, 1); //параметр наименование
                    if ($response['status'] == 1) {
                        $mine_parameter_id = $response['id'];
                    } else {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        $mine_parameter_id = -1;
                    }
                    $response = $this->actionAddMineParameterHandbookValue($mine_parameter_id, $specific_title, 1, date("Y-m-d H:i:s"));//сохранение значения параметра
                    if ($response['status'] == 1) {
                        $mine_parameter_handbook_value_id = $response['id'];
                    } else {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        $mine_parameter_handbook_value_id = -1;
                    }

                    $response = $this->AddMineParameter($main_specific_id, 274, 1); //параметр тип объекта
                    if ($response['status'] == 1) {
                        $mine_parameter_id = $response['id'];
                    } else {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        $mine_parameter_id = -1;
                    }
                    $response = $this->actionAddMineParameterHandbookValue($mine_parameter_id, $object_id, 1, date("Y-m-d H:i:s"));//сохранение значения параметра
                    if ($response['status'] == 1) {
                        $mine_parameter_handbook_value_id = $response['id'];
                    } else {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        $mine_parameter_handbook_value_id = -1;
                    }


                    $response = $this->AddMineParameter($main_specific_id, 348, 1); //параметр наименование предприятия
                    if ($response['status'] == 1) {
                        $mine_parameter_id = $response['id'];
                    } else {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        $mine_parameter_id = -1;
                    }
                    $response = $this->actionAddMineParameterHandbookValue($mine_parameter_id, $company_id, 1, date("Y-m-d H:i:s"));//сохранение значения параметра
                    if ($response['status'] == 1) {
                        $mine_parameter_handbook_value_id = $response['id'];
                    } else {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        $mine_parameter_handbook_value_id = -1;
                    }
                }
            }
        }
        $specific_array = parent::buildSpecificObjectArray($kind_id, $object_type_id, $object_id);                      //вызываем функция построения массива конкретных объектов нужного типа
        $result = array('specificArray' => $specific_array, 'errors' => $errors, 'warnings' => $warnings, 'specific_id' => $main_specific_id);    //создаем массив для передачи данных по ajax запросу со значениями и ошибками
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * Метод добавления шахты.
     * Шахта добавляется, если название шахты не дублируется в БД
     * @param $specific_title - название шахты
     * @param $object_id - id объекта
     * @param $company_id - id предприятия
     * @return int|string - id добавленной шахты
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 24.10.2018 13:58
     */
    public function actionAddMine($specific_title, $object_id, $company_id)
    {
        $new_mine_id = -1;
        $search_mine = ObjectFunctions::SearchOne("mine", "title = '$specific_title'");                  // Находим шахту по названию (название шахты не должны быть одинаковыми)
        if ($search_mine == -1)                                                                                          // если шахту не нашли, то добавим ее
        {
            $object_columns = "title, object_id, company_id";                                                           // поля таблицы
            $values = "'$specific_title', $object_id, $company_id";                                                     // значения таблицы
            $new_mine_id = ObjectFunctions::AddObjectMain("mine", $object_columns, $values, 'mine'); // добавим в БД
        } else                                                                                                            // если шахта найдена, то вернем id  шахты
        {
            $search_mine = $search_mine['id'];
            $new_mine_id = $search_mine;
        }
        return $new_mine_id;
    }

    /**
     * Метод каскадного удаления шахты.
     * Метод удаляет все
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 26.10.2018 14:00
     */
    public function actionDeleteSpecificObject()
    {
        $post = Yii::$app->request->post();                                                                             //получение данных от ajax-запроса
        $errors = array();
        $specific_objects = array();
        $toDelete = true;                                                                                               //переменная-флажок разрешающая удаление
        $objectKinds = null;
        if (isset($post['specific_id']) and $post['specific_id'] != "" and
            isset($post['kind_object_id']) and $post['kind_object_id'] != "" and
            isset($post['object_type_id']) and $post['object_type_id'] != "" and
            isset($post['object_id']) and $post['object_id'] != "") {                                                                                                               //если все данные переданы
            $specific_id = $post['specific_id'];                                                                        //получаем id-шахты
            $kind_object_id = $post['kind_object_id'];                                                                  //получаем id-вида объекта
            $type_object_id = $post['object_type_id'];                                                                  //получаем id-тип объетка
            $object_id = $post['object_id'];                                                                            //получем id- объекта
            $specificObject = ObjectFunctions::SearchOne("mine", "id = $specific_id");
            if ($specificObject != -1)                                                                                   //если шахта существует
            {
                if ($toDelete)                                                                                           // флаг удаления
                {
                    ObjectFunctions::DeleteFromTable("mine_function", "mine_id = $specific_id");                        // удаляем все функции шахты
                    /**------------ удаляем mine_parameter */
                    $specific_parameters = (new Query())->select('*')->from('mine_parameter')->where(['mine_id' => $specific_id])->all();//находим все параметры шахты
                    foreach ($specific_parameters as $specific_parameter) {
                        $mine_parameter_id = $specific_parameter['id'];
                        ObjectFunctions::DeleteFromTable("mine_parameter_value", "mine_parameter_id = $mine_parameter_id");//удаляем неизмеряемые параметры шахты
                        ObjectFunctions::DeleteFromTable("mine_parameter_handbook_value", "mine_parameter_id = $mine_parameter_id");///удаляем справочные значения
                        ObjectFunctions::DeleteFromTable("mine_parameter", "id = $mine_parameter_id");                  //удаляем параметры шахты
                    }
                    /**------------ удаляем place связанные с удаляемой шахты */
                    $places = (new Query())->select('*')->from('place')->where(['mine_id' => $specific_id])->all();     //находим все места шахты
                    foreach ($places as $place) {
                        $place_id = $place['id'];
                        $specific_parameters = (new Query())->select('*')->from('place_parameter')->where(['place_id' => $place_id])->all();//находим все параметры места
                        foreach ($specific_parameters as $specific_parameter) {
                            $place_parameter_id = $specific_parameter['id'];
                            ObjectFunctions::DeleteFromTable("place_parameter_value", "place_parameter_id = $place_parameter_id");//удаляем неизмеряемые параметры place
                            ObjectFunctions::DeleteFromTable("place_parameter_handbook_value", "place_parameter_id = $place_parameter_id");///удаляем справочные значения
                            ObjectFunctions::DeleteFromTable("place_parameter", "id = $place_parameter_id");            //удаляем  параметры place
                        }

                    }
                    ObjectFunctions::DeleteFromTable("place", "mine_id = $specific_id");                                // удаляем местоположения, которые входят в эту шахту

                    /**-------------------------- удаляем всех работников удаляемой шахты----------------*/
                    $workers = (new Query())->select('*')->from('worker')->where(['mine_id' => $specific_id])->all();   //ищем работников шахты
                    foreach ($workers as $worker) {
                        $worker_id = $worker['id'];
                        $worker_object_id = (new Query())->select('id')->from('worker_object')->where(['worker_id' => $worker_id])->one();//находим id объекта воркера
                        $worker_parameters = (new Query())->select('*')->from('worker_parameter')->where(['worker_object_id' => $worker_object_id])->all();//находим все параметры воркера
                        foreach ($worker_parameters as $worker_parameter) {
                            $worker_parameter_id = $worker_parameter['id'];
                            ObjectFunctions::DeleteFromTable("worker_parameter_value", "worker_parameter_id = $worker_parameter_id");//удаляем неизмеряемые параметры воркера
                            ObjectFunctions::DeleteFromTable("worker_parameter_handbook_value", "worker_parameter_id = $worker_parameter_id");///удаляем справочные значения
                            ObjectFunctions::DeleteFromTable("worker_parameter_sensor", "worker_parameter_id = $worker_parameter_id");            //удаляем  параметры сенсора воркера
                            ObjectFunctions::DeleteFromTable("worker_parameter", "id = $worker_parameter_id");          //удаляем  параметры воркера
                            ObjectFunctions::DeleteFromTable("worker_object", "id = $worker_id");                       //удаляем  параметры объекта воркера
                        }
                        ObjectFunctions::DeleteFromTable("worker", "id = $worker_id");                                  //удаляем воркера
                    }

                    /**------------------------- удаляем повороты удаляемой шахты-------------------- */
                    $conjunctions = (new Query())->select('*')->from('conjunction')->where(['mine_id' => $specific_id])->all();//находим повороты шахты
                    foreach ($conjunctions as $conjunction) {
                        $conjunction_id = $conjunction['id'];
                        ObjectFunctions::DeleteFromTable("conjunction_function", "conjunction_id = $conjunction_id");   //удаляем функции поворота
                        $conjunction_parameters = (new Query())->select('*')->from('conjunction_parameter')->where(['conjunction_id' => $conjunction_id])->all();//находим параметры поворота
                        foreach ($conjunction_parameters as $conjunction_parameter) {
                            $conjunction_parameter_id = $conjunction_parameter['id'];
                            ObjectFunctions::DeleteFromTable("conjunction_parameter_value", "conjunction_parameter_id = $conjunction_parameter_id");//удаляем неизмеряемые параметры
                            ObjectFunctions::DeleteFromTable("conjunction_parameter_handbook_value", "conjunction_parameter_id = $conjunction_parameter_id");///удаляем справочные значения
                            ObjectFunctions::DeleteFromTable("conjunction_parameter_sensor", "conjunction_parameter_id = $conjunction_parameter_id");            //удаляем  параметры сенсора поворота
                            ObjectFunctions::DeleteFromTable("conjunction_parameter", "id = $conjunction_parameter_id");//удаляем  параметры поворота
                        }
                        ObjectFunctions::DeleteFromTable("conjunction", "id = $conjunction_id");                        //удаляем поворот
                    }
                    ObjectFunctions::DeleteFromTable("mine", "id = $specific_id");                                      // удаляем шахту
                    $errors['success'] = "Шахта удалена со всеми параметрами и значениями  параметров.";
                    $specific_objects = parent::buildSpecificObjectArray($kind_object_id, $type_object_id, $object_id); //построение списка типовых объектов
                }
            } else {
                $errors[] = "Указанной шахт не существует";
            }
        } else {
            $errors[] = "Данные не переданы";
        }

        $result = array('errors' => $errors, 'specificObjects' => $specific_objects);                                   //составить результирующий массив как массив полученных массивов
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * МЕТОД ПЕРЕМЕЩЕНИЯ ШАХТЫ
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 26.10.2018 15:40
     */
    public function actionMoveSpecificObject()
    {
        $post = Assistant::GetServerMethod(); //получение данных от ajax-запроса
        $errors = array();
        $specific_objects = null;
        $new_object_kinds = array();
        if (isset($post['specific_id']) AND $post['specific_id'] != "" AND //если все данные переданы
            isset($post['kind_object_id']) AND $post['kind_object_id'] != "" AND
            isset($post['object_type_id']) AND $post['object_type_id'] != "" AND
            isset($post['object_id']) AND $post['object_id'] != "" AND
            isset($post['new_object_id']) AND $post['new_object_id'] != "") {
            $specific_id = $post['specific_id'];                                                                          //айдишник конкретного объекта
            $object_id = $post['object_id'];                                                                              //старый айдишник типового объекта
            $new_object_id = $post['new_object_id'];                                                                      //новый айдишник типового объекта
            $kind_object_id = $post['kind_object_id'];                                                                    //вид типового объекта
            $object_type_id = $post['object_type_id'];                                                                    //Айдишник типа типового объекта
//            $new_object_type_id=$post['new_object_type_id'];                                                            //новый Айдишник типа типового объекта

            $specific_object = ObjectFunctions::SearchOne("mine", "id = $specific_id");
            if ($specific_object != -1 and $specific_object['object_id'] != $new_object_id) {
                $update_result = ObjectFunctions::SqlUpdate("mine", "object_id = $new_object_id", "id = $specific_id");// отредактирует данные
                if ($update_result) {
                    $errors['success'][] = "Шахта успешно перемещена";
                    $specific_objects = parent::buildSpecificObjectArray($kind_object_id, $object_type_id, $object_id);
                    $new_object_kinds = parent::buildSpecificObjectArray($kind_object_id, $object_type_id, $new_object_id);
                } else $errors[] = "Не удалось переместить объект";
            } else {
                $errors[] = "Указанной шахты не существует";
            }
        } else $errors[] = "Данные не переданы";//если не переданы сохранить соответствующую ошибку

        $result = array('errors' => $errors, 'specificObjects' => $specific_objects, 'newSpecificObjects' => $new_object_kinds);   //составить результирующий массив как массив полученных массивов
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Метод редактирования конкретного объекта, точнее Шахты
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 26.10.2018 10:07
     */
    public function actionEditSpecificObjectBase()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $errors = array();
        $object_kinds = null;
        $specific_objects = array();
        $specific_parameters = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                          //если в сессии есть логин
//            if (AccessCheck::checkAccess($session['sessionLogin'], 89))                                                 //если пользователю разрешен доступ к функции
//            {
            if (isset($post['title']) and $post['title'] != "" and // название
                isset($post['specific_id']) and $post['specific_id'] != "" and // id шахты
                isset($post['kind_object_id']) and $post['kind_object_id'] != "" and // вид типового объекта
                isset($post['object_type_id']) and $post['object_type_id'] != "" and // тип объекта
                isset($post['object_id']) and $post['object_id'] != "")                                             // id объекта
            {
                $new_object_title = $post['title'];                                                                   //название конкретного объекта - новое
                $specific_id = $post['specific_id'];                                                                  //айдишник конкретного объекта
                $object_id = $post['object_id'];                                                                      //старый айдишник типового объекта
                $kind_object_id = $post['kind_object_id'];                                                            //вид типового объекта
                $object_type_id = $post['object_type_id'];                                                            //Айдишник типа типового объекта

                $object = Mine::findOne($specific_id);                                                              //находим шахту по id
                if ($object)                                                                                         //если шахта существует
                {
                    if ($object->title != $new_object_title)                                                         // если полученное название не совпадает с название конкретной шахты в БД, то отредактируем
                    {
                        $object->title = $new_object_title;                                                         //сохранить в найденный по id параметр название
                        if ($object->save())                                                                         // если название сохранилось, то добавим параметры для указанной шахты
                        {
                            $response = $this->AddMineParameter($specific_id, 162, 1); // добавляем параметр наименование
                            if ($response['status'] == 1) {
                                $mine_parameter_id = $response['id'];
                            } else {
                                $errors[] = $response['errors'];
                                $warnings[] = $response['warnings'];
                                $mine_parameter_id = -1;
                            }
                            $response = $this->actionAddMineParameterHandbookValue($mine_parameter_id, $new_object_title, 1, 1);//сохранение значения параметра
                            if ($response['status'] == 1) {
                                $mine_parameter_handbook_value_id = $response['id'];
                            } else {
                                $errors[] = $response['errors'];
                                $warnings[] = $response['warnings'];
                                $mine_parameter_handbook_value_id = -1;
                            }
                            $specific_objects = parent::buildSpecificObjectArray($kind_object_id, $object_type_id, $object_id);//обновить массив типовых объектов
                            $specific_parameters = parent::buildSpecificParameterArray($specific_id, 'mine');
                        } else $errors[] = "Ошибка сохранения";                                                               //если не сохранился, сохранить соответствующую ошибку

                    } else {
                        $errors[] = "Шахта с таким названием уже существует";
                    }                                             //если найден объект по названию, сохранить соответствующую ошибку
                } else $errors[] = "Шахты с id " . $specific_id . " не существует";                                              //если не найден объект по id, сохранить соответствующую ошибку
            } else $errors[] = "Данные не переданы";                                                                           //если не заданы входные параметры сохранить соответствующую ошибку
//            }
//            else
//            {
//                $errors[] =  "Недостаточно прав для совершения данной операции";
//            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        $result = array('errors' => $errors, 'specificObjects' => $specific_objects, 'specificParameters' => $specific_parameters);   //составить результирующий массив как массив полученных массивов
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Метод добавления значений параметров шахты в БД и редактирования параметров таблицы Mine.
     * Если в HandbookVal поменяет пользователь данные, то и в таблице mine должны меняться
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 26.10.2018 16:35
     */
    public function actionSaveSpecificParametersValues()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $errors = array();
        $objectParameters = array();
        $objects = array();
        $specific_parameters = array();
        if (isset($post['parameter_values_array']) AND $post['parameter_values_array'] != "" AND
            isset($post['table_name']) AND $post['table_name'] != "" AND
            isset($post['specific_object_id']) AND $post['specific_object_id'] != "") {
            $mine_id = $post['specific_object_id'];                                                                  //айдишник конкретного объекта
            $object_id = Mine::findOne($mine_id)->object_id;
            $object_type_id = TypicalObject::findOne($object_id)->object_type_id;
            $object_kind_id = ObjectType::findOne($object_type_id)->kind_object_id;
            $parameterValues = json_decode($post['parameter_values_array'], true);                                                         //массив параметров и их значений
            $tableName = "mine";                                                                                      //название таблицы в которую пишем
            $specific_id = $post['specific_object_id'];                                                                 //айдишник конкретного объекта
            //print_r($parameterValues);
            if ($parameterValues) {
                foreach ($parameterValues as $parameter) {
                    if ($parameter['parameterValue'] != "") {
                        if (isset($parameter['parameterStatus'])) {
                            $specific_parameter_id = (int)$parameter['specificParameterId'];
                            $parameter_id = (int)$parameter['parameterId'];
                            $value = (string)$parameter['parameterValue'];
                            $id = ObjectFunctions::AddObjectParameterHandbookValue("$tableName", $specific_parameter_id, 1, $value, 1);
                            if (!$id) {                                                                                           //если не сохранилась
                                $errors[] = "Справочное значение " . $specific_parameter_id . " не сохранено. Идентификатор объекта " . $specific_id;//сохранить соответствующую ошибку
                            }
                            //сохраняем значение параметров в базовые справочники объекта
                            if ($parameter_id == 162)                                                           //параметр наименование
                            {

                                $mine_value = $this->actionUpdateMineValuesString($specific_id, "title", $value);
                                if ($mine_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: $parameter_id";
                            }
                            if ($parameter_id == 274) {                                                    //параметр тип объекта
                                $mine_value = $this->actionUpdateMineValuesInt($specific_id, "object_id", $value);
                                if ($mine_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: $parameter_id";
                            }
                            if ($parameter_id == 348)                                                           //параметр название компании
                            {
                                $mine_value = $this->actionUpdateMineValuesInt($specific_id, "company_id", $value);
                                if ($mine_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: $parameter_id";
                            }
                        } else echo "нет статус";
                    }
                }
                $mine = Mine::findOne($specific_id);//найти объект
                if ($mine)                                                                                                   //если найден, то построить массив объектов, если нет, то сохранить ошибку
                {
                    $specific_parameters = parent::buildSpecificParameterArray($specific_id, $tableName);
                } else $errors[] = "Шахта с id " . $specific_id . " не найден";
                $objects = parent::buildSpecificObjectArray($object_kind_id, $object_type_id, $object_id);
            }
        } else {
            $errors[] = "Данные не переданы";
            $errors['post'] = $post;
        }
        $result = array('errors' => $errors, 'objectProps' => $specific_parameters, 'objects' => $objects);//составить результирующий массив как массив полученных массивов
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    //метод копирует параметры типового параметра в параметры конкретного объекта - нужен для создания конкретного объекта по шаблону типового объекта
    public function actionCopyTypicalParametersToSpecificMine($typical_object_id, $specific_object_id)
    {
        $debug_flag = 0;                                                                                                  //отладочный флаг
        $flag_done = 1;                                                                                                   //флаг успешного выполнения метода
        //копирование параметров справочных
        if ($type_object_parameters = TypeObjectParameter::find()->where(['object_id' => $typical_object_id, 'parameter_type_id' => 1])->all())                           //Находим все параметры типового объекта
        {
            foreach ($type_object_parameters as $type_object_parameter) {
                //создаем новый параметр у конкретного объекта
                $response = $this->AddMineParameter($specific_object_id, $type_object_parameter->parameter_id, $type_object_parameter->parameter_type_id);
                if ($response['status'] == 1) {
                    $mine_parameter_id = $response['id'];
                } else {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    $mine_parameter_id = -1;
                }
                //ищем последние справочное значения параметра типового объекта и копируем их в значение справочное конкретного объекта
                if ($mine_parameter_id
                    and $typical_object_parameter_handbook_values = TypeObjectParameterHandbookValue::find()
                        ->where(['type_object_parameter_id' => $type_object_parameter->id])
                        ->orderBy(['date_time' => SORT_DESC])
                        ->one()) {

                    $response = $this->actionAddMineParameterHandbookValue($mine_parameter_id, $typical_object_parameter_handbook_values->value, $typical_object_parameter_handbook_values->status_id, 1);
                    if ($response['status'] == 1) {
                        $flag_done *= $response['status'];
                        $mine_parameter_handbook_value_id = $response['id'];
                    } else {
                        $flag_done *= $response['status'];
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        $mine_parameter_handbook_value_id = -1;
                    }
                }
            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись справочных параметров" . "\n");

        //копирование функций типового объекта
        //находим функции типового объекта
        if ($type_object_functions = TypeObjectFunction::find()->where(['object_id' => $typical_object_id])->all()) {
            foreach ($type_object_functions as $type_object_function) {
                $mine_function_id = $this->AddMineFunction($specific_object_id, $type_object_function->func_id);
                if ($mine_function_id == -1) $flag_done = -1;
            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись функций" . "\n");
        if (isset($flag_done["max(id)"])) {
            $flag_done = $flag_done["max(id)"];
        }
        return $flag_done;
        $type = new TypeObjectParameter();
        $type->parameter = 2;
        $type->save();
    }


    /**
     * Функция добавления функций шахты (Только шахту добавляет без каких либо параметров)
     * @param $mine_id
     * @param $function_id
     * @return int
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 24.10.2018 14:35
     */
    public function AddMineFunction($mine_id, $function_id)
    {
        $new_mine_function_id = ObjectFunctions::AddObjectFunction("mine", $mine_id, $function_id);
        return $new_mine_function_id;
    }

    /**
     * Метод добавления функция шахты с помощью Ajax-запросов
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 24.10.2018 14:39
     */
    public function actionAddMineFunction()
    {
        $post = Assistant::GetServerMethod(); //получение данных от ajax-запроса
        $mine_id = -1;
        $mine_function = -1;
        $mine_functions = array();
        $errors = array();
        if (isset($post['specificObjectId']) && $post['specificObjectId'] != "" &&
            isset($post['functionId']) && $post['functionId'] != "") {
            $mine_id = $post['specificObjectId'];
            $function_id = $post['functionId'];
            $mine_function = $this->AddMineFunction($mine_id, $function_id);
            if ($mine_function == -1) {
                $errors[] = "Ошибка сохранения функции для указанной шахты";
            } else {
                $mine_functions = $this->buildSpecificFunctionArray($mine_id, "mine");
            }
        } else {
            $errors[] = "Не переданы все параметры";
            $errors['post'] = $post;
        }
        $result = array('errors' => $errors, 'objectFunctions' => $mine_functions, 'd' => $mine_function);
        Yii::$app->response->format = Response::FORMAT_JSON; // формат json
        Yii::$app->response->data = $result; // отправляем обратно ввиде FORMAT_JSON
    }

    /**
     * Метод редактирования функции шахты
     * @param $mine_function_id - id функции шахты
     * @param $mine_id - id шахты
     * @param $function_id - id функции
     * Created by: Одилов О.У. on 24.10.2018 15:46
     */
    public function actionUpdateMineFunction($mine_function_id, $mine_id, $function_id)
    {
        $errors = array();
        $mine_functions = array();
        $mine_function_updated_id = -1;
        $mine_function_update = MineFunction::findOne(['id' => $mine_function_id]);
        if ($mine_function_update)                                                                                       // если есть, то отредактируем
        {
            $mine_function_update->mine_id = $mine_id;                                                                  //айди шахты
            $mine_function_update->function_id = $function_id;                                                          //айди функции
            if ($mine_function_update->save()) {
                $mine_functions = $this->buildSpecificFunctionArray($mine_id, "mine");                      //создаем список функций на возврат
                $mine_function_updated_id = $mine_function_update->id;
            } else {
                $errors[] = "ошибка редактирования функций шахты";
                $errors[] = $mine_function_update;
            }
        } else {
            $errors[] = "По указанному id = $mine_function_id функция не найдна";
            $errors[] = $mine_function_update;
        }
        $result = array('errors' => $errors, 'objectFunctions' => $mine_functions, 'mine_function_updated_id' => $mine_function_updated_id);
        Yii::$app->response->format = Response::FORMAT_JSON; // формат json
        Yii::$app->response->data = $result; // отправляем обратно ввиде FORMAT_JSON
    }


    /**
     * Метод удаления конкретной функции шахты
     * Метож возвращает массив функций
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 24.10.2018 11:09
     */
    public function actionDeleteMineFunction()
    {
        $post = Assistant::GetServerMethod(); //получение данных от ajax-запроса
        $errors = array();
        $object_functions = array();
        if (isset($post['specific_object_id']) && $post['specific_object_id'] != "" &&
            isset($post['specific_function_id']) && $post['specific_function_id'] != "") {
            $mine_id = $post['specific_object_id'];
            $mine_function_id = $post['specific_function_id'];
            ObjectFunctions::DeleteFromTable("mine_function", "mine_id = $mine_id and id = $mine_function_id");
            $object_functions = array();
            $objects = (new Query())
                ->select(
                    [
                        'function_type_title functionTypeTitle',
                        'function_type_id functionTypeId',
                        'mine_function_id id',
                        'function_id',
                        'mine_id',
                        'func_title functionTitle',
                        'func_script_name scriptName'
                    ])
                ->from(['view_mine_function'])
                ->where('mine_id = ' . $mine_id)
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
        }
        $result = array('objectFunctions' => $object_functions, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON; // формат json
        Yii::$app->response->data = $result; // отправляем обратно ввиде FORMAT_JSON
    }

    /**
     * Метод добавления параметров шахты.
     * Метод добавляет параметр, если его нет в БД у шахты.
     * @param $mine_id - id  шахты
     * @param $parameter_id - id параметра
     * @param $parameter_type_id - id типа параметра
     * @return array|int|string id добавленного параметра или массив ошибок
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 24.10.2018 14:01
     */
    public function AddMineParameter($mine_id, $parameter_id, $parameter_type_id)
    {
        $mine_parameter_id = -1;
        $status = 1;
        $warnings = array();                                                                                            // Массив предупреждений
        $result = array();
        $errors = array();
        $warnings[] = "AddMineParameter. Начал выполнять метод";
        try {
            $mine_parameter=MineParameter::findOne(
                ['parameter_id'=>$parameter_id, 'parameter_type_id'=>$parameter_type_id,'mine_id'=>$mine_id]
            );
            if(!$mine_parameter){
                $mine_parameter = new MineParameter();
            }
            $mine_parameter->parameter_id = $parameter_id;
            $mine_parameter->parameter_type_id = $parameter_type_id;
            $mine_parameter->mine_id = $mine_id;
            if (!$mine_parameter->save()) {
                $errors[]=$mine_parameter->errors;
                throw new \Exception("AddMineParameter. Не смог сохранить модель MineParameter");
            } else {
                $mine_parameter->refresh();
                $mine_parameter_id = $mine_parameter->id;
            }
        } catch (\Throwable $exception) {
            $errors[] = "AddMineParameter. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "AddMineParameter. Закончил выполнять метод";
        $result = array('status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'id' => $mine_parameter_id);
        return $result;
    }

    /**
     * Метод добавления датчика в шахту (mine_sensor_parameter)
     * @param $mine_parameter_id - id параметра шахты
     * @param $sensor_id - id датчика
     * @param $date_time - дата и время (если указать 1, то автоматически добавляется тек. дата/время)
     * @return int|string - если все добавилось, то id, иначе -1
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 29.10.2018 10:11
     */
    public function AddMineParameterSensor($mine_parameter_id, $sensor_id, $date_time)
    {
        if ($date_time == 1) {
            $date_format = "Y-m-d H:i:s";
            $date_time = date($date_format);
        }
        $mine_parameter_sensor_columns = "mine_parameter_id, sensor_id, date_time";
        $values = ("$mine_parameter_id, $sensor_id, '$date_time'");
        $mine_parameter_sensor_id = ObjectFunctions::InsertIntoTable("mine_parameter_sensor", $mine_parameter_sensor_columns, $values);
        return $mine_parameter_sensor_id;
    }

    /**
     * Метод добавления датчика в шахту (mine_sensor_parameter) со фронта
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 29.10.2018 10:44
     */
    public function actionAddMineParameterSensor()
    {
        $post = Yii::$app->request->get();
        $errors = array();
        $mine_parameter_sensor_id = -1;
        if (isset($post['mine_parameter_id']) AND $post['mine_parameter_id'] AND
            isset($post['sensor_id']) AND $post['sensor_id'] AND
            isset($post['date_time']) AND $post['date_time']) {
            $mine_parameter_id = $post['mine_parameter_id'];
            $sensor_id = $post['sensor_id'];
            $date_time = $post['date_time'];
            $mine_parameter_sensor_id = $this->AddMineParameterSensor($mine_parameter_id, $sensor_id, $date_time);
            if ($mine_parameter_sensor_id == -1) {
                $errors[] = "Ошибка добавления параметр 'sensor' в mine_parameter_sensor";
            }
        } else {
            $errors[] = "Не все параметры были переданы";
        }
        $result = array('errors' => $errors, 'mine_parameter_sensor_id' => $mine_parameter_sensor_id);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            // формат json
        Yii::$app->response->data = $result;                                                                          // отправляем обратно ввиде FORMAT_JSON
    }

    //метод удаления параметров для шахт
    public function actionDeleteSpecificParameter()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $errors = array();
        $paramsArray = array();
        if (isset($post['table_name']) and $post['table_name'] and
            isset($post['action_type']) and $post['action_type'] and
            isset($post['specific_object_id']) and $post['specific_object_id']) {

            $table_name = $post['table_name'];
            $actionType = $post['action_type'];
            $specificObjectId = $post['specific_object_id'];

            if ($actionType == "local") {
                if (isset($post['specific_parameter_id']) and $post['specific_parameter_id'] != "") {
                    $specificParameterId = $post['specific_parameter_id'];
                    MineParameterSensor::deleteAll(['mine_parameter_id' => $specificParameterId]);
                    MineParameterHandbookValue::deleteAll(['mine_parameter_id' => $specificParameterId]);
                    MineParameterValue::deleteAll(['mine_parameter_id' => $specificParameterId]);
                    MineParameter::deleteAll(['id' => $specificParameterId]);
                    $paramsArray = $this->buildSpecificParameterArray($specificObjectId, $table_name);
                } else {
                    $errors[] = "не передан mine_parameter_sensor_id";
                }
            } else {
                if (isset($post['parameter_id']) and $post['parameter_id'] != "") {
                    $parameterId = $post['parameter_id'];
                    $parameters = MineParameter::find()->where(['parameter_id' => $parameterId])->all();
                    foreach ($parameters as $parameter) {
                        MineParameterSensor::deleteAll(['mine_parameter_id' => $parameter->id]);
                        MineParameterHandbookValue::deleteAll(['mine_parameter_id' => $parameter->id]);
                        MineParameterValue::deleteAll(['mine_parameter_id' => $parameter->id]);
                    }
                    MineParameter::deleteAll(['parameter_id' => $parameterId, 'mine_id' => $specificObjectId]);
                    $paramsArray = $this->buildSpecificParameterArray($specificObjectId, $table_name);
                } else {
                    $errors[] = "не передан mine_parameter_id";
                }
            }
        } else {
            $errors[] = "не передан table_name, или action_type, или specific_object_id";
        }
        $result = array('paramArray' => $paramsArray, 'errors' => $errors);
        echo json_encode($result);
    }

    /**
     * Метод добавления параметров шахты на строне фронта
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 29.10.2018 9:46
     */
    public function actionAddMineParameter()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $post = Assistant::GetServerMethod(); //получение данных от ajax-запроса
            $errors = array();
            $params_array = array();
            if (isset($post['id']) and $post['id'] and
                isset($post['parameter_id']) and $post['parameter_id'] and
                isset($post['parameter_type_id']) and $post['parameter_type_id']) {
            } else {
                throw new \Exception("actionAddMineParameter. Не все входные параметры переданы");
            }
            $specific_id = $post['id'];
            $parameter_id = $post['parameter_id'];
            $parameter_type_id = $post['parameter_type_id'];
            $mine_parameter = new MineParameter();
            $mine_parameter->parameter_id = $parameter_id;
            $mine_parameter->parameter_type_id = $parameter_type_id;
            $mine_parameter->mine_id = $specific_id;
            if (!$mine_parameter->save()) {
                throw new \Exception("actionAddMineParameter. Не смог сохранить модель MineParameter");
            }
            $params_array = parent::buildSpecificParameterArray($specific_id, "mine");
        } catch (\Throwable $exception) {
            $errors[] = "actionAddMineParameter. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "actionDeleteEdgeFromMine. Закончил выполнять метод";
        $result = array('paramArray' => $params_array, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON; // формат json
        Yii::$app->response->data = $result; // отправляем обратно ввиде FORMAT_JSON
    }

    /**
     * Метод сохранение справочного значения конкретного параметра шахты
     * @param $mine_parameter_id - id паарметра шахты
     * @param $value - значение
     * @param $status_id - статус
     * @param $date_time - дата и время
     * @return array|int|string
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 26.10.2018 10:46
     */
    public function actionAddMineParameterHandbookValue($mine_parameter_id, $value, $status_id, $date_time)
    {
        $mine_parameter_handbook_value_id = -1;
        $status = 1;
        $warnings = array();                                                                                            // Массив предупреждений
        $result = array();
        $errors = array();
        $warnings[] = "actionAddMineParameterHandbookValue. Начал выполнять метод";
        try {
            if ($date_time == 1) $date_time = date("Y-m-d H:i:s", strtotime(\backend\controllers\Assistant::GetDateNow()."-1seconds"));
            $mine_parameter_handbook_value = new MineParameterHandbookValue();
            $mine_parameter_handbook_value->mine_parameter_id = $mine_parameter_id;
            $mine_parameter_handbook_value->date_time = $date_time;
            $mine_parameter_handbook_value->value = $value;
            $mine_parameter_handbook_value->status_id = $status_id;
            if (!$mine_parameter_handbook_value->save()) {
                $errors[] = $mine_parameter_handbook_value->errors;
                throw new \Exception("actionAddMineParameterHandbookValue. Не смог сохранить модель MineParameterHandbookValue");
            } else {
                $mine_parameter_handbook_value->refresh();
                $mine_parameter_handbook_value_id = $mine_parameter_handbook_value->id;
            }
        } catch (\Throwable $exception) {
            $errors[] = "actionAddMineParameterHandbookValue. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "actionAddMineParameterHandbookValue. Закончил выполнять метод";
        $result = array('status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'id' => $mine_parameter_handbook_value_id);
        return $result;

    }

    /**
     * сохраняет базовые Текстовые параметры в базовый справочник
     * @param $specific_id
     * @param $name_field
     * @param $value
     * @return int
     * Created by: Одилов О.У. on 29.10.2018 10:54
     */
    public function actionUpdateMineValuesString($specific_id, $name_field, $value)
    {
        $mine_update = Mine::findOne(['id' => $specific_id]);
        $mine_update->$name_field = (string)$value;
        if (!$mine_update->save()) return -1;
        else return 1;
    }

    /**
     * сохраняет базовые Числовые параметры в базовый справочник
     * @param $specific_id
     * @param $name_field
     * @param $value
     * @return int
     * Created by: Одилов О.У. on 29.10.2018 10:55
     */
    public function actionUpdateMineValuesInt($specific_id, $name_field, $value)
    {
        $mine_update = Mine::findOne(['id' => $specific_id]);
        $mine_update->$name_field = (int)$value;
        if (!$mine_update->save()) return -1;
        else return 1;
    }

    /**
     * Метод выборки списка шахт
     * Created by: Одилов О.У. on 29.10.2018 10:58
     * @example http://127.0.0.1/positioningsystem/specific-mine/get-mines-with-parameters
     */
    public function actionGetMinesWithParameters()
    {
        $mines = (new Query())                                                                                          // создаем экземпляр класса
        ->select('id, title, company_id')                                                                              //выбираем только идентификатор и название шахты
        ->from('mine')                                                                                       // с таблицы "Шахты"
        ->orderBy('title')                                                                                 // отсортируем по названию шахты
        ->all();                                                                                                    // выбираем все
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $mines;
    }

    public function actionSearchSpecificPlace()
    {
        $post = Assistant::GetServerMethod(); //получение данных от ajax-запроса
        $errors = array();
        $result = array();
        $sql_filter = '';

        if (isset($post['title']) and $post['title']) {
            $search = (string)$post['title'];
            $sql_filter = "( title like '%$search%')";
            $list_places = (new Query())                                                                                          // создаем экземпляр класса
            ->select('*')
                ->from('view_field_place')
                ->where($sql_filter)
                ->all();

            $index = -1;
            $j = -1;
            $k = -1;
            $i = -1;
            foreach ($list_places as $item) {
                $object_type_title = $item['object_type_title'];
                if ($index == -1 OR $result[$index]['title'] != $object_type_title) {
                    $index++;
                    $result[$index]['id'] = $item['object_type_id'];
                    $result[$index]['title'] = $item['object_type_title'];
                    $j = -1;
                }
                if ($j == -1 OR $result[$index]['objects'][$j]['id'] != $item['object_id']) {
                    $k = -1;
                    $i = -1;
                    $j++;
                    $k++;
                    $result[$index]['objects'][$j]['id'] = $item['object_id'];
                    $result[$index]['objects'][$j]['title'] = $item['object_title'];
                    $result[$index]['objects'][$j]['pattern'] = $item['pattern'];
                    $result[$index]['objects'][$j]['specific_objects'][$k]['id'] = $item['place_id'];
                    $result[$index]['objects'][$j]['specific_objects'][$k]['title'] = $item['title'];
                    $result[$index]['objects'][$j]['specific_objects'][$k]['table_name'] = $item['object_table'];

                } else {
                    if ($result[$index]['objects'][$j]['specific_objects'][$k]['id'] != $item['place_id']) {
                        $k++;
                        $result[$index]['objects'][$j]['specific_objects'][$k]['id'] = $item['place_id'];
                        $result[$index]['objects'][$j]['specific_objects'][$k]['title'] = $item['title'];
                        $result[$index]['objects'][$j]['specific_objects'][$k]['table_name'] = $item['object_table'];
                    }


                }
                if ($item['object_table'] == 'place') {
                    $i++;
                    $result[$index]['objects'][$j]['specific_objects'][$k]['edges'][$i]['id'] = $item['id'];
                    $result[$index]['objects'][$j]['specific_objects'][$k]['edges'][$i]['table_name'] = 'edge';
                }


            }
            $result = array('errors' => $errors, 'object_types' => $result);
            Yii::$app->response->format = Response::FORMAT_JSON;
            Yii::$app->response->data = $result;
        }
    }
}
