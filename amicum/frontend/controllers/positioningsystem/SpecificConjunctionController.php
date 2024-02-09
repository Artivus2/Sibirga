<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\positioningsystem;
//ob_start();

use backend\controllers\cachemanagers\EdgeCacheController;
use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\const_amicum\TypicalObjectEnumController;
use backend\controllers\EdgeBasicController;
use backend\controllers\EdgeMainController;
use backend\controllers\MainBasicController;
use backend\controllers\SensorBasicController;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\AccessCheck;
use frontend\models\Conjunction;
use frontend\models\ConjunctionFunction;
use frontend\models\ConjunctionParameter;
use frontend\models\ConjunctionParameterHandbookValue;
use frontend\models\ConjunctionParameterSensor;
use frontend\models\ConjunctionParameterValue;
use frontend\models\Main;
use frontend\models\ObjectType;
use frontend\models\TypeObjectFunction;
use frontend\models\TypeObjectParameter;
use frontend\models\TypeObjectParameterHandbookValue;
use frontend\models\TypicalObject;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Response;

class SpecificConjunctionController extends SpecificObjectController
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
        $object_id = null;
        $object_type_id = null;
        $kind_id = null;
        $specific_array = array();
        $specific_title = null;
        $main_specific_id = null;
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 86)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();                                                                             //получение данных от ajax-запроса
                if (isset($post['title']) and $post['title'] != "") {                                                               //проверка на наличие входных данных а именно на наличие такого названия
                    $specific_title = $post['title'];                                                                           //название нового конкретного объекта, который создаем
                    $sql_filter = 'title="' . $specific_title . '"';
                    $conjunctions = (new Query())//запрос напрямую из базы по таблице Conjunction
                    ->select(
                        [
                            'id',
                            'title'
                        ])
                        ->from(['conjunction'])
                        ->where($sql_filter)
                        ->one();
                    if ($conjunctions) {
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
                if (isset($post['coord_x']) and $post['coord_x'] != "" and
                    isset($post['coord_y']) and $post['coord_y'] != "" and
                    isset($post['coord_z']) and $post['coord_z'] != "") {
                    $coordinata_x = $post['coord_x'];
                    $coordinata_y = $post['coord_y'];
                    $coordinata_z = $post['coord_z'];
                } else {
                    $errors[] = "Не все входные данные есть в методе POST";
                    $check_input_parameters = -1;
                    if ($debug_flag == 1) echo nl2br("----проверка на наличие координат. входные данные есть =" . $check_input_parameters . "\n");
                    if ($debug_flag == 1) echo nl2br("----координата x есть = " . isset($coordinata_x) .
                        " координата y есть = " . isset($coordinata_y) .
                        " координата z есть = " . isset($coordinata_z) . "\n");
                }

                if ($check_input_parameters == 1 and $check_title == 1) {                                                             //все нужные входные данные есть и название не существует в базе
                    if ($debug_flag == 1) echo nl2br("----вход в выполнение функции по добавлению объекта =" . "\n");


                    $main_specific_id = $this->actionAddConjunction($specific_title, $object_id, $coordinata_x, $coordinata_y, $coordinata_z);

                    if ($main_specific_id == -1) {
                        $errors[] = "Ошибка сохранения сопряжения  в базовой таблице";
                    }
//                    $main_specific_id = $this->actionAddConjunction($specific_title, $object_id, $coordinata_x, $coordinata_y, $coordinata_z);    //создаем/изменяем запись с таблице Conjunction
                    if ($debug_flag == 1) echo nl2br("----создано сопряжение =" . $main_specific_id . "\n");

                    if ($main_specific_id == -1) {
                        $errors[] = "Ошибка сохранения сопряжения продукции в базовой таблице:" . $specific_title;
                        if ($debug_flag == 1) echo nl2br("----зашел в ошибку. mainspecificid  = 1 " . $main_specific_id . "\n");
                    } else {                                                                                                    //если сохранили электропродукцию, то копируем справочные значения и функции типового объекта
                        $flag_done = $this->actionCopyTypicalParametersToSpecific($object_id, $main_specific_id);
                        if ($flag_done == -1) {
                            $errors[] = "Ошибка копирования параметров и значений типового объекта в конкретный:" . $main_specific_id;
                            if ($debug_flag == 1) echo nl2br("----ошибка копирования параметров типового объекта в конкретные =" . $main_specific_id . "\n");
                        } else {
                            //сохраняем значения параметров из базовой таблицы в параметры базового объекта
                            $conjunction_parameter_id = $this->actionAddConjunctionParameter($main_specific_id, 162, 1); //параметр наименование
                            $conjunction_parameter_value = $this->actionAddConjunctionParameterHandbookValue($conjunction_parameter_id, $specific_title, 1, date("Y-m-d H:i:s"));//сохранение значения параметра
                            if ($conjunction_parameter_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 162";
                            if ($debug_flag == 1) echo nl2br("----зашел в else. начал сохранять параметры " . $main_specific_id . "\n");
                        }
                        $specific_array = self::buildSpecificObjectArray($kind_id, $object_type_id, $object_id);//вызываем функция построения массива конкретных объектов нужного типа
                    }
                } else {
                    $errors[] = "Поворот с именем " . $specific_title . " уже существует";
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

    //создание параметра конкретной трубы
    public function actionAddConjunctionParameter($conjunction_id, $parameter_id, $parameter_type_id)
    {
        $debug_flag = 0;

        if ($debug_flag == 1) echo nl2br("----зашел в функцию создания параметров кабеля  =" . $conjunction_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        if ($conjunction_parameter = ConjunctionParameter::find()->where(['conjunction_id' => $conjunction_id, 'parameter_id' => $parameter_id, 'parameter_type_id' => $parameter_type_id])->one()) {
            if ($debug_flag == 1) {
                echo nl2br("----зашли в условие, когда найдены параметры кабеля \n");
            }
            return $conjunction_parameter->id;
        } else {
            if ($debug_flag == 1) {
                echo nl2br("----зашли в условие, когда не найдены параметры кабеля, и создается новый параметр. \n");
            }
            $conjunction_parameter_new = new ConjunctionParameter();
            $conjunction_parameter_new->conjunction_id = $conjunction_id;                                                        //айди водоснабжения
            $conjunction_parameter_new->parameter_id = $parameter_id;                                                      //айди параметра
            $conjunction_parameter_new->parameter_type_id = $parameter_type_id;                                            //айди типа параметра

            if ($conjunction_parameter_new->save()) return $conjunction_parameter_new->id;
            else return (-1); //"Ошибка сохранения значения параметра поворота" . $conjunction_id->id;
        }
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
                $conjunction_parameter_id = $this->actionAddConjunctionParameter($specific_object_id, $type_object_parameter->parameter_id, $type_object_parameter->parameter_type_id);

                //ищем последние справочное значения параметра типового объекта и копируем их в значение справочное конкретного объекта
                if ($conjunction_parameter_id
                    and $typical_object_parameter_handbook_values = TypeObjectParameterHandbookValue::find()
                        ->where(['type_object_parameter_id' => $type_object_parameter->id])
                        ->orderBy(['date_time' => SORT_DESC])
                        ->one())
                    $flag_done = $this->actionAddConjunctionParameterHandbookValue($conjunction_parameter_id, $typical_object_parameter_handbook_values->value, $typical_object_parameter_handbook_values->status_id);
            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись справочных параметров" . "\n");

        //копирование функций типового объекта
        //находим функции типового объекта
        if ($type_object_functions = TypeObjectFunction::find()->where(['object_id' => $typical_object_id])->all()) {
            foreach ($type_object_functions as $type_object_function) {
                $conjunction_function_id = $this->actionAddConjunctionFunction($specific_object_id, $type_object_function->func_id);
                if ($conjunction_function_id == -1) $flag_done = -1;
            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись функций" . "\n");
        return $flag_done;
    }

    //сохранение функций трубы
    public function actionAddConjunctionFunction($conjunction_id, $function_id)
    {
        $debug_flag = 0;
        if ($debug_flag == 1) echo nl2br("----зашел в функцию создания функции трубы  =" . $conjunction_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        if ($conjunction_function = ConjunctionFunction::find()->where(['conjunction_id' => $conjunction_id, 'function_id' => $function_id])->one()) {
            return $conjunction_function->id;
        } else {
            $conjunction_function_new = new ConjunctionFunction();
            $conjunction_function_new->conjunction_id = $conjunction_id;                                                                  //айди трубы
            $conjunction_function_new->function_id = $function_id;                                                            //айди функции
            //статус значения

            if ($conjunction_function_new->save()) return $conjunction_function_new->id;
            else return -1;
        }
    }

    //сохранение справочного значения конкретного параметра трубы
    public function actionAddConjunctionParameterHandbookValue($conjunction_parameter_id, $value, $status_id = 1, $date_time = 1)
    {
        $conjunction_parameter_handbook_value = new ConjunctionParameterHandbookValue();
        $conjunction_parameter_handbook_value->conjunction_parameter_id = $conjunction_parameter_id;
        if ($date_time == 1) $conjunction_parameter_handbook_value->date_time = date("Y-m-d H:i:s", strtotime("-1 second"));
        else $conjunction_parameter_handbook_value->date_time = $date_time;
        $conjunction_parameter_handbook_value->value = strval($value);
        $conjunction_parameter_handbook_value->status_id = $status_id;

        if (!$conjunction_parameter_handbook_value->save()) {
            return (-1);
        } else return 1;
    }

    //метод создания конкретного объекта в его базовой таблице
    public function actionAddConjunction($specific_title, $object_id, $coordinata_x, $coordinata_y, $coordinata_z)
    {
        $conjunction_id = self::actionAddEntryMain('conjunction');                                        //создаем запись в таблице Main
        $session = Yii::$app->session;
        $mine_id = $session['userMineId'];                                                                              //получаем id шахты

        if (!is_int($conjunction_id)) return -1;
        else {
            $newSpecificObject = new Conjunction();
            $newSpecificObject->id = $conjunction_id;                                                                   //айдишнек новой созданной трубы
            if ($specific_title === null) {
                $newSpecificObject->title = 'Поворот ' . $conjunction_id;
            } else {
                $newSpecificObject->title = $specific_title;
            }
            $newSpecificObject->object_id = $object_id;                                                                 //id типового объекта
            $newSpecificObject->x = $coordinata_x;
            $newSpecificObject->y = $coordinata_y;
            $newSpecificObject->z = $coordinata_z;
            $newSpecificObject->mine_id = $mine_id;

            if (!$newSpecificObject->save()) return -1;                                                                 //проверка на сохранение нового объекта
            else return $newSpecificObject->id;

        }
    }

    /**
     * AddConjunction - Создание в базе данных сопряжения/вершины с привязкой к конкретной шахте
     * Входные параметры:
     * @param $mine_id - ключ шахты
     * @param $conjunction_x - координата x поворота
     * @param $conjunction_y - координата y поворота
     * @param $conjunction_z - координата z поворота
     *
     * Выходные параметры:
     *      conjunction_id  - ключ созданного поворота
     *      conjunction     - модель сопряжения
     */
    public static function AddConjunction($mine_id, $conjunction_x, $conjunction_y, $conjunction_z)
    {
        $log = new LogAmicumFront("AddConjunction");
        $conjunction_id = -1;
        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            $response = MainBasicController::addMain('conjunction');
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка сохранения главного id выработки");
            }

            $conjunction_id = $response['main_id'];

            $conjunction = new Conjunction();
            $conjunction->id = $conjunction_id;
            $conjunction->mine_id = $mine_id;                                                                           // id шахты
            $conjunction->x = (float)str_replace(',', '.', (string)$conjunction_x);                                                                           // сопряжение координата Х
            $conjunction->y = (float)str_replace(',', '.', (string)$conjunction_y);                                                                           // сопряжение координата Y
            $conjunction->z = (float)str_replace(',', '.', (string)$conjunction_z);                                                                           // сопряжение координата Z
            $conjunction->object_id = TypicalObjectEnumController::CONJUNCTION;                                         // ИД типового объекта 12 (сопряжение)
            $conjunction->title = 'Поворот ' . $conjunction_id;                                                         // Название сопряжение по id и дополнительному слову сопряжение

            if (!$conjunction->save()) {
                $log->addData($conjunction->errors, '$conjunction->errors', __LINE__);
                throw new Exception('Ошибка сохранения поворота Conjunction');
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result, 'conjunction_id' => $conjunction_id, 'conjunction' => $conjunction], $log->getLogAll());
    }

    /*
    * функция редактирования поворотов
    * */
    public function actionEditConjunction()
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
                    && isset($post['kind_object_id']) && isset($post['object_type_id']) && isset($post['object_id'])
                    and isset($post['coordX']) and $post['coordX'] != "" and isset($post['coordY']) and $post['coordY'] != ""
                    and isset($post['coordZ']) and $post['coordZ'] != "") {          //проверка на передачу данных

                    $new_object_title = strval($post['title']);                                                                           //название конкретного объекта - новое
                    $specific_id = (int)$post['specific_id'];                                                                          //айдишник конкретного объекта
                    $object_id = (int)$post['object_id'];                                                                              //старый айдишник типового объекта
                    $kind_object_id = (int)$post['kind_object_id'];                                                                    //вид типового объекта
                    $object_type_id = (int)$post['object_type_id'];                                                                    //Айдишник типа типового объекта
                    $coordx = (int)$post['coordX'];
                    $coordy = (int)$post['coordY'];
                    $coordz = (int)$post['coordZ'];

                    $object = Conjunction::findOne($specific_id);                                                                     //найти объект по id
                    if ($object) {                                                                                                //если объект существует
                        if ($object->title !== $new_object_title) {
                            $existingObject = Conjunction::findOne(['title' => $new_object_title]);                                         //найти объект по названию, чтобы не было дублирующих
                            if (!$existingObject) {                                                                                   //если не найден
                                $object->title = $new_object_title;                                                                            //сохранить в найденный по id параметр название
                                if ($object->save()) {                                                                                //если объет сохранился
                                    $conjunction_parameter_id = $this->actionAddConjunctionParameter($specific_id, 162, 1); //параметр наименование
                                    if ($conjunction_parameter_id == -1) {
                                        $errors[] = "Ошибка добавления параметра 162 (Наименование)для указанного поворота (ConjunctionParameter)";
                                    } else {
                                        $conjunction_parameter_value = $this->actionAddConjunctionParameterHandbookValue($conjunction_parameter_id, $new_object_title, 1, 1);//сохранение значения параметра
                                        if ($conjunction_parameter_value === (-1)) {
                                            $errors[] = "Ошибка добавления справочного значения параметра 162 (Наименование) для указанного поворота (ConjunctionParameterHandbookValue)";
                                        }
                                    }
                                } else {
                                    $errors[] = "Ошибка сохранения названия поворота";
                                }
                            } else {
                                $errors[] = "Поворот с таким названием уже существует";
                            }
                        }


                        //-----------------------------------------------------------------------------------------

                        /**********************************     РЕДАКТИРОВАНИЕ КООРДИНАТА X     *******************************/

                        if ($object->x != $coordx)                                                        // если координат x у данного поворота поменялись, то редактируем
                        {
                            $object->x = $coordx;                                                          // добавим нвое значение координата x для поворота
                            $flag_done = $object->save();                                                       // сохраняем
                            if ($flag_done)                                                                                  // если данные сохранились
                            {
                                $conjunction_parameter_id = $this->actionAddConjunctionParameter($specific_id, 349, 1); //параметр координат X
                                if ($conjunction_parameter_id) {
                                    $conjunction_parameter_value = $this->actionAddConjunctionParameterHandbookValue($conjunction_parameter_id, $coordx);//сохранение значения параметра
                                    if (!$conjunction_parameter_value) {
                                        $errors[] = "Ошибка добавления справочного значения для указанного поворота (ConjunctionParameterHandbookValue)";
                                    }
                                } else {
                                    $errors[] = "Ошибка добавления параметра 349 (координат X)для указанного поворота (ConjunctionParameter)";
                                }
                            }
                        }

                        /**********************************     РЕДАКТИРОВАНИЕ КООРДИНАТА Y     *******************************/

                        if ($object->y != $coordy)                                                        // если координат x у данного поворота поменялись, то редактируем
                        {
                            $object->y = $coordy;                                                          // добавим нвое значение координата x для поворота
                            $flag_done = $object->save();                                                       // сохраняем
                            if ($flag_done)                                                                                  // если данные сохранились
                            {
                                $conjunction_parameter_id = $this->actionAddConjunctionParameter($specific_id, 350, 1); //параметр координат X
                                if ($conjunction_parameter_id) {
                                    $conjunction_parameter_value = $this->actionAddConjunctionParameterHandbookValue($conjunction_parameter_id, $coordy);//сохранение значения параметра
                                    if (!$conjunction_parameter_value) {
                                        $errors[] = "Ошибка добавления справочного значения для указанного поворота (ConjunctionParameterHandbookValue)";
                                    }
                                } else {
                                    $errors[] = "Ошибка добавления параметра 350(координат Y) для указанного поворота (ConjunctionParameter)";
                                }
                            }
                        }

                        /**********************************     РЕДАКТИРОВАНИЕ КООРДИНАТА Z     *******************************/

                        if ($object->z != $coordz)                                                        // если координат x у данного поворота поменялись, то редактируем
                        {
                            $object->z = $coordz;                                                          // добавим нвое значение координата x для поворота
                            $flag_done = $object->save();                                                       // сохраняем
                            if ($flag_done)                                                                                  // если данные сохранились
                            {
                                $conjunction_parameter_id = $this->actionAddConjunctionParameter($specific_id, 351, 1); //параметр координат X
                                if ($conjunction_parameter_id) {
                                    $conjunction_parameter_value = $this->actionAddConjunctionParameterHandbookValue($conjunction_parameter_id, $coordz);//сохранение значения параметра
                                    if (!$conjunction_parameter_value) {
                                        $errors[] = "Ошибка добавления справочного значения для указанного поворота (ConjunctionParameterHandbookValue)";
                                    }
                                } else {
                                    $errors[] = "Ошибка добавления параметра 351 (координат Z) для указанного поворота (ConjunctionParameter)";
                                }
                            }
                        }


                        //--------------
                        $specificObjects = self::buildSpecificObjectArray($kind_object_id, $object_type_id, $object_id);//обновить массив типовых объектов
                        $specificParameters = parent::buildSpecificParameterArray($specific_id, "conjunction");
                        //если не сохранился, сохранить соответствующую ошибку
                        //если найден объект по названию, сохранить соответствующую ошибку
                    } else $errors[] = "Поворота с id " . $specific_id . " не существует";                                              //если не найден объект по id, сохранить соответствующую ошибку
                } else {
                    $errors[] = "Данные не переданы";
                }                                                                           //если не заданы входные параметры сохранить соответствующую ошибку
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        $result = array('errors' => $errors, 'specificObjects' => $specificObjects, 'specificParameters' => $specificParameters);                                            //составить результирующий массив как массив полученных массивов
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;                                                                            //вернуть AJAX-запросу данные и ошибки
    }

    /*
   * метод перемещения трубы
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

                    $specificObject = Conjunction::findOne($specific_id);
                    $specificObject->object_id = $new_object_id;
                    if ($specificObject->save()) {
                        $objectKinds = parent::buildSpecificObjectArray($kind_object_id, $object_type_id, $object_id);
                        $newObjectKinds = parent::buildSpecificObjectArray($kind_object_id, $object_type_id, $new_object_id);
                    } else $errors[] = "Не удалось переместить объект";
                } else {
                    $errors[] = "Данные не переданы";
                }//если не переданы сохранить соответствующую ошибку
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        $result = array('errors' => $errors, 'specificObjects' => $objectKinds, 'newSpecificObjects' => $newObjectKinds);   //составить результирующий массив как массив полученных массивов
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;                                                                            //вернуть AJAX-запросу данные и ошибки
    }

    /*
     * метод удаления трубы
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
                    $specificObject = Conjunction::findOne($specific_id);
                    if ($specificObject) {                                                                                        //если объект существует
                        if ($toDelete) {
                            ConjunctionFunction::deleteAll(['conjunction_id' => $specific_id]);                                             //удаляем функции у сенсора

                            $specific_parameters = ConjunctionParameter::findAll(['conjunction_id' => $specific_id]);                       //ищем параметры на удаление
                            foreach ($specific_parameters as $specific_parameter) {
                                ConjunctionParameterValue::deleteAll(['conjunction_parameter_id' => $specific_parameter->id]);              //удаляем измеренные или вычесленные значения
                                ConjunctionParameterHandbookValue::deleteAll(['conjunction_parameter_id' => $specific_parameter->id]);      //удаляем справочные значения
                                ConjunctionParameter::deleteAll(['id' => $specific_parameter->id]);                                   //удаляем сам параметр сенсора
                            }
                            Conjunction::deleteAll(['id' => $specific_id]);                                                           //удаляем сам сенсор
                        } else $errors[] = "Нельзя удалить объект из-за наличия значений у параметров объекта " . $specific_id;
                    }
                    $specificObjects = parent::buildSpecificObjectArray($kind_object_id, $type_object_id, $object_id);              //построение списка типовых объектов
                } else $errors[] = "Данные не переданы";                                                                          //сохранить соответствующую ошибку
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        $result = array('errors' => $errors, 'specificObjects' => $specificObjects);                                    //составить результирующий массив как массив полученных массивов
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;                                                                            //вернуть AJAX-запросу данные и ошибки

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

                    if ($actionType === 'local') {
                        if (isset($post['specific_parameter_id']) and $post['specific_parameter_id'] != "") {
                            $specificParameterId = $post['specific_parameter_id'];
                            ConjunctionParameterSensor::deleteAll(['conjunction_parameter_id' => $specificParameterId]);
                            ConjunctionParameterHandbookValue::deleteAll(['conjunction_parameter_id' => $specificParameterId]);
                            ConjunctionParameterValue::deleteAll(['conjunction_parameter_id' => $specificParameterId]);
                            ConjunctionParameter::deleteAll(['id' => $specificParameterId]);
                            $paramsArray = $this->buildSpecificParameterArray($specificObjectId, 'conjunction');
                        } else {
                            $errors[] = "Не передан conjunction_parameter_id";
                        }
                    } else {
                        if (isset($post['parameter_id']) and $post['parameter_id'] != "") {
                            $parameterId = $post['parameter_id'];
                            $parameters = ConjunctionParameter::find()->where(['parameter_id' => $parameterId])->all();
                            foreach ($parameters as $parameter) {
                                ConjunctionParameterSensor::deleteAll(['conjunction_parameter_id' => $parameter->id]);
                                ConjunctionParameterHandbookValue::deleteAll(['conjunction_parameter_id' => $parameter->id]);
                                ConjunctionParameterValue::deleteAll(['conjunction_parameter_id' => $parameter->id]);
                            }
                            ConjunctionParameter::deleteAll(['parameter_id' => $parameterId, 'conjunction_id' => $specificObjectId]);
                            $paramsArray = $this->buildSpecificParameterArray($specificObjectId, 'conjunction');
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

    //добавление нового параметра сенсора из страницы фронтэнда
    public function actionAddConjunctionParameterOne()
    {
        $specific_id = null;
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
                    $conjunction_parameter = $this->actionAddConjunctionParameter($specific_id, $parameter_id, $parameter_type_id);
                    if ($conjunction_parameter == -1) $errors[] = "не удалось сохранить параметр";
                    $paramsArray = parent::buildSpecificParameterArray($specific_id, 'conjunction');
                } else {
                    $errors[] = "Не все данные переданы";
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

    /*
 * функция сохранения значений с вкладки
 * $post['table_name'] - имя таблицы
 * $post['parameter_values_array'] - массив значений
 * $post['specificObjectId'] - id конкретного объекта
 * */
    public function actionSaveSpecificParametersValues()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $errors = array();
        $objectParameters = null;
        $specific_parameters = array();
        $objects = array();
        $session = Yii::$app->session;
        $session->open();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 92)) {                                        //если пользователю разрешен доступ к функции
                if (isset($post['parameter_values_array']) && isset($post['specific_object_id'])) {

                    $parameterValues = json_decode($post['parameter_values_array'], true);                                                         //массив параметров и их значений
                    $table_name = "conjunction";                                                                                      //название таблицы в которую пишем
                    $specific_id = $post['specific_object_id'];                                                                 //айдишник конкретного объекта
                    $object_id = Conjunction::findOne(['id' => $specific_id])->object_id;
                    $object_type_id = TypicalObject::findOne(['id' => $object_id])->object_type_id;
                    $object_kind_id = ObjectType::findOne(['id' => $object_type_id])->kind_object_id;
                    if ($parameterValues) {
                        // var_dump($parameterValues);die();
                        foreach ($parameterValues as $parameter) {
                            if ($parameter['parameterValue'] != '') {
                                if ($parameter['parameterStatus'] == 'sensor') {
                                    if (isset($parameter['parameterValue'])) {
                                        $sensor_parameter_sensor = new ConjunctionParameterSensor();                                     //записываем значения параметров со вкладки измеренные
                                        $sensor_parameter_sensor->conjunction_parameter_id = (int)$parameter['specificParameterId'];
                                        $sensor_parameter_sensor->date_time = date("Y-m-d H:i:s");
                                        $sensor_parameter_sensor->sensor_id = (int)$parameter['parameterValue'];                    //айди сенсора - получилась рекурсия по базе, но принцип не страшно
                                        //var_dump($sensor_parameter_sensor);
                                        if (!$sensor_parameter_sensor->save()) {                                                    //если не сохранилась
                                            $errors[] = "Измеряемое значение " . $parameter['specificParameterId'] . " не сохранено. Идентификатор объекта " . $specific_id;//сохранить соответствующую ошибку
                                        }
                                    }
                                } else if ($parameter['parameterStatus'] == 'place' || $parameter['parameterStatus'] == 'edge') {
                                    $specific_parameter_handbook_value = new ConjunctionParameterValue();                      //создать новое значение справочного параметра
                                    $specific_parameter_handbook_value->conjunction_parameter_id = (int)$parameter['specificParameterId'];
                                    $specific_parameter_handbook_value->date_time = date("Y-m-d H:i:s");
                                    $specific_parameter_handbook_value->value = (string)$parameter['parameterValue'];             //сохранить новое значение, текущую метку времени, типовой параметр и статус
                                    $specific_parameter_handbook_value->status_id = 1;
                                    if (!$specific_parameter_handbook_value->save()) {//если не сохранилась
                                        $errors[] = "значение параметра " . $parameter['parameterId'] . " не сохранено. specificParameterId = " . $parameter['specificParameterId'] . "Идентификатор объекта " . $specific_id;//сохранить соответствующую ошибку
                                    }
                                } else if ($parameter['parameterStatus'] === 'handbook') {
                                    $specific_parameter_handbook_value = new ConjunctionParameterHandbookValue();                      //создать новое значение справочного параметра
                                    $specific_parameter_handbook_value->conjunction_parameter_id = (int)$parameter['specificParameterId'];
                                    $specific_parameter_handbook_value->date_time = date("Y-m-d H:i:s");
                                    $specific_parameter_handbook_value->value = (string)$parameter['parameterValue'];             //сохранить новое значение, текущую метку времени, типовой параметр и статус
                                    $specific_parameter_handbook_value->status_id = 1;
                                    if (!$specific_parameter_handbook_value->save()) {//если не сохранилась
                                        $errors[] = "Справочное значение " . $parameter['specificParameterId'] . " не сохранено. Идентификатор объекта " . $specific_id;//сохранить соответствующую ошибку
                                    }
                                    //сохраняем значение параметров в базовые справочники объекта
                                    if ($parameter['parameterId'] == 162) {                                                    //параметр наименование
                                        $conjunction_value = $this->actionUpdateConjunctionValuesString($specific_id, "title", $parameter['parameterValue']);
                                        if ($conjunction_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 162";
                                    }
                                    if ($parameter['parameterId'] == 274) {                                                    //параметр тип объекта
                                        $conjunction_value = $this->actionUpdateConjunctionValuesInt($specific_id, "object_id", $parameter['parameterValue']);
                                        if ($conjunction_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 274";
                                    }
                                    if ($parameter['parameterId'] == 349) {                                                    //параметр тип объекта
                                        $conjunction_value = $this->actionUpdateConjunctionValuesInt($specific_id, "x", $parameter['parameterValue']);
                                        if ($conjunction_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 349";
                                    }
                                    if ($parameter['parameterId'] == 350) {                                                    //параметр тип объекта
                                        $conjunction_value = $this->actionUpdateConjunctionValuesInt($specific_id, "y", $parameter['parameterValue']);
                                        if ($conjunction_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 350";
                                    }
                                    if ($parameter['parameterId'] == 351) {                                                    //параметр тип объекта
                                        $conjunction_value = $this->actionUpdateConjunctionValuesInt($specific_id, "z", $parameter['parameterValue']);
                                        if ($conjunction_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 351";
                                    }
                                } else if ($parameter['parameterStatus'] == 'manual') {
                                    $specific_parameter_handbook_value = new ConjunctionParameterValue();                      //создать новое значение справочного параметра
                                    $specific_parameter_handbook_value->conjunction_parameter_id = (int)$parameter['specificParameterId'];
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
                    $conjunction = Conjunction::findOne($specific_id);//найти объект
                    if ($conjunction) {//если найден, то построить массив объектов, если нет, то сохранить ошибку
                        $specific_parameters = parent::buildSpecificParameterArray($specific_id, $table_name);
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
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        $result = array('errors' => $errors, 'objectProps' => $specific_parameters, 'objects' => $objects);//составить результирующий массив как массив полученных массивов
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;                                                                            //вернуть AJAX-запросу данные и ошибки
    }

    //сохраняет базовые Текстовые параметры в базовый справочник
    public function actionUpdateConjunctionValuesString($specific_id, $name_field, $value)
    {
        $conjunction_update = Conjunction::findOne(['id' => $specific_id]);
        $conjunction_update->$name_field = (string)$value;
        if (!$conjunction_update->save()) return -1;
        else return 1;
    }

    //сохраняет базовые Числовые параметры в базовый справочник
    public function actionUpdateConjunctionValuesInt($specific_id, $name_field, $value)
    {
        $conjunction_update = Conjunction::findOne(['id' => $specific_id]);
        $conjunction_update->$name_field = (int)$value;
        if (!$conjunction_update->save()) return -1;
        else return 1;
    }

    //функция добавления функции трубы с post с фронта
    public function actionAddConjunctionFunctionFront()
    {
        $errors = array();
        $functionsArray = array();
        $session = Yii::$app->session;
        $session->open();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 94)) {                                       //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['specificObjectId']) && isset($post['specificObjectId']) && isset($post['functionId']) && isset($post['functionId'])) {
                    $conjunction_id = $post['specificObjectId'];
                    $function_id = $post['functionId'];
                    $conjunction_function = $this->actionAddConjunctionFunction($conjunction_id, $function_id);
                    if ($conjunction_function == -1) $errors[] = "не удалось сохранить параметр";
                    $functionsArray = parent::buildSpecificFunctionArray($conjunction_id, "conjunction");

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

    //функция удаления функции трубы с post с фронта
    public function actionDeleteConjunctionFunction()
    {
        $errors = array();
        $object_functions = array();
        $session = Yii::$app->session;
        $session->open();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 95)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['specific_object_id']) && $post['specific_object_id'] != "" && isset($post['specific_function_id']) && $post['specific_function_id'] != "") {
                    $conjunction_id = $post['specific_object_id'];
                    $conjunction_function_id = $post['specific_function_id'];
                    ConjunctionFunction::deleteAll(['id' => $conjunction_function_id]);

                    $objects = (new Query())
                        ->select(
                            [
                                'function_type_title functionTypeTitle',
                                'function_type_id functionTypeId',
                                'conjunction_function_id id',
                                'function_id',
                                'conjunction_id',
                                'func_title functionTitle',
                                'func_script_name scriptName'
                            ])
                        ->from(['view_conjunction_function'])
                        ->where('conjunction_id = ' . $conjunction_id)
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

    /**
     * МЕТОД КАСКАДНОГО УДАЛЕНИЯ ПОВОРОТА
     * @param $conjuction_id - id поворота
     * Автор: ОДИЛОВ О.У. 15-10-2018
     */
    public static function CascadeRemoveConjuction($conjuction_id)
    {
        $sql_remove_command = "CALL CascadeRemoveConjuction($conjuction_id)";
        $sql_query_res = Yii::$app->db->createCommand($sql_remove_command)->execute();
        return $sql_query_res;
    }

    /*
  * функция редактирования конкретных объектов
  * */
    public function actionEditSpecificObject()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $errors = array();
        $places = array();
        $edges_for_update = array();

        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 78)) {                                        //если пользователю разрешен доступ к функции
                $objectKinds = null;
                $flag_exists_id = 0;
                $flag_exists_title = 0;
                $flag_existsX = 0;
                $flag_existsY = 0;
                $flag_existsZ = 0;
                $conjuction_title = '';
                $conjuctionX = '';
                $conjuctionId = '';
                $conjuctionY = '';
                $conjuctionZ = '';
                $conjuctionX_old = '';
                $conjuctionY_old = '';
                $conjuctionZ_old = '';
                if (isset($post['specific_id']) and $post['specific_id'] != "")                                              // если передан id поворота
                {
                    $flag_exists_id = 1;
                    $conjuctionId = $post['specific_id'];                                                                     // id поворота
                }
                if (isset($post['conjuction_title']) and $post['conjuction_title'] != "")                                        // если передано наименование
                {
                    $flag_exists_title = 1;
                    $conjuction_title = $post['conjuction_title'];                                                                        // наименование поворота
                }
                if (isset($post['conjuctionX']) and $post['conjuctionX'] != "")                                                  // если передан координат X
                {
                    $flag_existsX = 1;
                    $conjuctionX = $post['conjuctionX'];                                                                        // координат X
                }
                if (isset($post['conjuctionY']) and $post['conjuctionY'] != "")                                                  // если передан координат Y
                {
                    $flag_existsY = 1;
                    $conjuctionY = $post['conjuctionY'];                                                                        // координат Y
                }
                if (isset($post['conjuctionZ']) and $post['conjuctionZ'] != "")                                                  // если передан координат Z
                {
                    $flag_existsZ = 1;
                    $conjuctionZ = $post['conjuctionZ'];                                                                        // координат Z
                }
                if ($flag_exists_id)                                                                                             // если передан id поворота
                {
                    $existingConjuction = Conjunction::findOne(['id' => $conjuctionId]);                                // находим conjuction по id
                    if ($existingConjuction)                                                                                     // если есть поворот по указанному id
                    {
                        /** записываеем в переменные старые координаты. Нужны для перемешения сенсоров с старой выработки в новую */
                        $conjuctionX_old = $existingConjuction->x;
                        $conjuctionY_old = $existingConjuction->y;
                        $conjuctionZ_old = $existingConjuction->z;
                        $new_conjunction_id = $this->actionAddConjunction(null, $existingConjuction->object_id, $conjuctionX, $conjuctionY, $conjuctionZ);    //создаем запись в таблице Conjunction
                        if ($new_conjunction_id == -1) {
                            $errors[] = "Ошибка сохранения сопряжения  в базовой таблице";
                        } else {
                            $edges = (new Query())//забираем все ребра которые принадлежат данному конджакшену
                            ->select(
                                [
                                    'edge_id',
                                    'place_id',
                                    'conjunction_start_id',
                                    'conjunction_end_id',
                                    'xStart',
                                    'yStart',
                                    'zStart',
                                    'xEnd',
                                    'yEnd',
                                    'zEnd',
                                    'edge_type_id',
                                    'mine_id'
                                ])
                                ->from(['view_edge_conjunction_place_for_merge'])
                                ->where('conjunction_start_id = ' . $conjuctionId . ' or conjunction_end_id = ' . $conjuctionId)
                                ->all();
                            $mas_edge_id_change = array();                                                                  //массив edge_id выработок которые относятся к данному изменению
                            foreach ($edges as $edge) {
                                if ($edge['conjunction_start_id'] == $conjuctionId) {
                                    $edge_new_length = pow((pow(($conjuctionX - $edge['xEnd']), 2) + pow(($conjuctionY - $edge['yEnd']), 2) + pow(($conjuctionZ - $edge['zEnd']), 2)), 0.5);//рассчитываем новую длину выработки
                                    $response = EdgeBasicController::AddEdge($edge['place_id'], $edge['conjunction_end_id'], $new_conjunction_id, $edge['edge_type_id']);  //создаем новую выработку
                                    if ($response['status'] == 1) {
                                        $edge_new_id = $response['edge_id'];
                                        $warnings[] = $response['warnings'];
                                        $errors[] = $response['errors'];
                                    } else {
                                        $warnings[] = $response['warnings'];
                                        $errors[] = $response['errors'];
                                        throw new Exception("actionEditSpecificObject. Ошибка сохранения  выработки");
                                    }
//                                    $edge_new_id = UnityController::actionAddEdge( $edge['place_id'], $edge['conjunction_end_id'], $new_conjunction_id,  $edge['edge_type_id']);  //создаем новую выработку
                                    UnityController::actionCopyEdge($edge_new_id, $edge['edge_id'], 'copy', $edge_new_length, 1);//копирование параметров и их значений новой ветви
                                    $flag_cache_done = (new EdgeCacheController())->runInit($edge['mine_id'], $edge_new_id)['status'];
                                    self::ReplaceSensorsCurrentEdge($edge['edge_id'], $edge_new_id, $edge['mine_id'], $conjuctionX_old, $conjuctionY_old, $conjuctionZ_old, 1);
                                    EdgeMainController::DeleteEdge($edge['edge_id'], $edge['mine_id']);                         //удаляем старую выработку
                                    $mas_edge_id_change[] = $edge_new_id;
                                } else {
                                    $edge_new_length = pow((pow(($conjuctionX - $edge['xStart']), 2) + pow(($conjuctionY - $edge['yStart']), 2) + pow(($conjuctionZ - $edge['zStart']), 2)), 0.5);//рассчитываем новую длину выработки
                                    $response = EdgeBasicController::AddEdge($edge['place_id'], $new_conjunction_id, $edge['conjunction_start_id'], $edge['edge_type_id']); //создаем новую выработку
                                    if ($response['status'] == 1) {
                                        $edge_new_id = $response['edge_id'];
                                        $warnings[] = $response['warnings'];
                                        $errors[] = $response['errors'];
                                    } else {
                                        $warnings[] = $response['warnings'];
                                        $errors[] = $response['errors'];
                                        throw new Exception("actionEditSpecificObject. Ошибка сохранения  выработки");
                                    }
//                                    $edge_new_id = UnityController::actionAddEdge( $edge['place_id'], $new_conjunction_id, $edge['conjunction_start_id'],  $edge['edge_type_id']); //создаем новую выработку
                                    UnityController::actionCopyEdge($edge_new_id, $edge['edge_id'], 'copy', $edge_new_length, 1);//копирование параметров и их значений новой ветви
                                    $flag_cache_done = (new EdgeCacheController())->runInit($edge['mine_id'], $edge_new_id)['status'];
                                    self::ReplaceSensorsCurrentEdge($edge['edge_id'], $edge_new_id, $edge['mine_id'], $conjuctionX_old, $conjuctionY_old, $conjuctionZ_old, 0);
                                    EdgeMainController::DeleteEdge($edge['edge_id'], $edge['mine_id']);//удаляем старую выработку
                                    $mas_edge_id_change[] = $edge_new_id;
                                }
                                $places[] = $edge['place_id'];
                                $edges_for_update[] = $edge['edge_id'];
                                $mas_edge_id_change[] = $edge['edge_id'];
                            }
                            EdgeHistoryController::AddEdgeChange($mas_edge_id_change);                                  //записываем изменения по выработкам
                        }

                    } else {                                                                                                       //  если нет такого поворота по указанному id
                        $errors[] = "Указанного поворота не существует";
                    }
                }

            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        if (isset($post['update_only_coordinates'])) {
            $result = array('errors' => $errors, 'places' => $places, 'edges' => $edges_for_update);//составить результирующий массив как массив полученных массивов
            Yii::$app->response->format = Response::FORMAT_JSON;                                                   // формат json
            Yii::$app->response->data = $result;
        } else {
            if (isset($post['title']) && isset($post['specific_id'])
                && isset($post['kind_object_id']) && isset($post['object_type_id']) && isset($post['object_id'])) {          //проверка на передачу данных
                $object_id = $post['object_id'];                                                                              //старый айдишник типового объекта
                $kind_object_id = $post['kind_object_id'];                                                                    //вид типового объекта
                $object_type_id = $post['object_type_id'];                                                                    //Айдишник типа типового объекта
                $specificObjects = parent::buildSpecificObjectArray($kind_object_id, $object_type_id, $object_id);//обновить массив типовых объектов
                $specificParameters = parent::buildSpecificParameterArray($conjuctionId, "conjunction");
            } else $errors[] = "Ошибка обновления массив типовых объектов после редактирования поворота. Параметры не переданы";                                                                           //если не заданы входные параметры сохранить соответствующую ошибку
            $result = array('errors' => $errors, 'specificObjects' => $specificObjects, "specificParameters" => $specificParameters);                                    //составить результирующий массив как массив полученных массивов
//            echo json_encode($result);                                                                                      //вернуть AJAX-запросу данные и ошибки
            Yii::$app->response->format = Response::FORMAT_JSON;
            Yii::$app->response->data = $result;
        }
    }

    /**
     * Метод переноса сенсоров при изменении координат сопряжения
     * @param $edge_old
     * @param $edge_new
     * @param $mine_id
     * @param null $edge_old_x
     * @param null $edge_old_y
     * @param null $edge_old_z
     * @param null $edge_start_con
     * @return array
     */
    public static function ReplaceSensorsCurrentEdge($edge_old, $edge_new, $mine_id, $edge_old_x = null, $edge_old_y = null, $edge_old_z = null, $edge_start_con = null)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'EdgeReplaceSensors. Начало выполнения метода';
        try {                                                                                          //массив с отладочной информацией
            if (isset($edge_old) and $edge_old != ""                                                                              // Проверяем что пришли все данные в метод
                and isset($edge_new) and $edge_new != ""
                and isset($mine_id) and $mine_id != "") {
                $edge_cache_controller = (new EdgeCacheController());
                $sensor_cache_controller = (new SensorCacheController());
                $warnings[] = "ReplaceSensorsCurrentEdge. Входные параметры переданы все";
            } else {
                throw new Exception("ReplaceSensorsCurrentEdge. Не переданы все параметры");
            }

            $array_xyz_edge1 = $edge_cache_controller->getEdgeScheme($mine_id, $edge_old);
            if ($array_xyz_edge1 != -1)                                                                                  //Если у выработки нашли координаты то записываем их в переменные
            {
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

            } else {
                throw new Exception("ReplaceSensorsCurrentEdge. Для делимой выработки  =" . $edge_old . " нет координат начала и конца");
            }

            $sensors = (new Query())//Ищем в БД все сеносоры находящиеся на разбиваемой выработки
            ->select(['sensor_id'])
                ->from('view_sensor_parameter_edge_id_fill')
                ->where(['edge_id' => $edge_old])
                ->all();
            if ($sensors) {
                $warnings[] = "В БД есть сенсоры для выработки = " . $edge_old;
            } else {
                $warnings[] = "В БД нет сенсоров для выработки = " . $edge_old;
            }
            $array_xyz_edge1_1 = $edge_cache_controller->getEdgeScheme($mine_id, $edge_new);
            if ($array_xyz_edge1_1 != -1)                                                                            //Если у выработки нашли координаты то записываем их в переменные
            {
                $edge_new_x2 = $array_xyz_edge1_1["xStart"];
                $edge_new_y2 = $array_xyz_edge1_1["yStart"];
                $edge_new_z2 = $array_xyz_edge1_1["zStart"];
                $edge_new_x1 = $array_xyz_edge1_1["xEnd"];
                $edge_new_y1 = $array_xyz_edge1_1["yEnd"];
                $edge_new_z1 = $array_xyz_edge1_1["zEnd"];
            } else {
                throw new Exception("ReplaceSensorsCurrentEdge. Для выработки  =" . $edge_new . "нет координат начала и конца");
            }


            $date_time_now = \backend\controllers\Assistant::GetDateNow();
            foreach ($sensors as $sensor)                                                                       //Начинаем перебирать сенсоры
            {
                $sensor_cache = $sensor_cache_controller->getSensorMineBySensorOneHash($mine_id, $sensor['sensor_id']);
                if ($sensor_cache) {
                    $warnings[] = 'ReplaceSensorsCurrentEdge. сенсор найден в кеше' . $sensor['sensor_id'];
                } else {
                    throw new Exception("ReplaceSensorsCurrentEdge.нет сенсора в списке кеша " . $sensor['sensor_id']);
                }

                $parameter_type_id = SensorCacheController::isStaticSensor($sensor_cache['object_type_id']);
                $sensors83 = $sensor_cache_controller->getParameterValueHash($sensor['sensor_id'], 83, $parameter_type_id);                  //Ищем координаты сенсора в кеше или в БД
                $sensors269 = $sensor_cache_controller->getParameterValueHash($sensor['sensor_id'], 269, $parameter_type_id);          //Ищем значение выработки которой принадлежит сенсор в кеше или в БД
                if ($sensors83 and $sensors269)                                                      //Если координаты и выработку нашли
                {
                    $warnings[] = 'ReplaceSensorsCurrentEdge. Параметры сенсора найдены' . $sensor['sensor_id'];
                } else {
                    throw new Exception("ReplaceSensorsCurrentEdge.Нет координат у текущего сенсора с id = " . $sensor['sensor_id']);
                }
                if ($sensors83 and $sensors269)                                                      //Если координаты и выработку нашли
                {
                    $warnings[] = 'ReplaceSensorsCurrentEdge. Параметры сенсора найдены' . $sensor['sensor_id'];
                } else {
                    throw new Exception("ReplaceSensorsCurrentEdge. Нет координат у текущего сенсора с id = " . $sensor['sensor_id']);
                }

                $coordinates = explode(",", $sensors83['value']);                                       //разбиваем  значение из строки в массив разделенный запятой и записываем их в соответсвующие переменные
                $sensor_x = $coordinates[0];
                $sensor_y = $coordinates[1];
                $sensor_z = $coordinates[2];

                /************* ищем выработку на какую будем перемещать текущий сенсор */
                $L1 = sqrt(pow(($sensor_x - $edge_old_x1), 2) + pow(($sensor_y - $edge_old_y1), 2) + pow(($sensor_z - $edge_old_z1), 2));         // длина от начала разбиваемой выработки до сенсора
                $L3 = sqrt(pow(($edge_old_x2 - $edge_old_x1), 2) + pow(($edge_old_y2 - $edge_old_y1), 2) + pow(($edge_old_z2 - $edge_old_z1), 2));// длина старой выработки
                $DL = abs($L1 / ($L3 - $L1));                                                                         //отношение длины от начала делимой выработки к длине всей делимой выработки
                $new_sensor_x = ($edge_new_x1 + $DL * $edge_new_x2) / (1 + $DL);
                $new_sensor_y = ($edge_new_y1 + $DL * $edge_new_y2) / (1 + $DL);
                $new_sensor_z = ($edge_new_z1 + $DL * $edge_new_z2) / (1 + $DL);
                $new_string_xyz = $new_sensor_x . ',' . $new_sensor_y . ',' . $new_sensor_z;                        //создаем строку для записи  в кеш и БД из новых координат сенсора
                if ($parameter_type_id == 1)                                                    //если значение координат справочное то меняем значение в поле handbook
                {
                    SensorBasicController::addSensorParameterHandbookValue($sensors83['sensor_parameter_id'], $new_string_xyz, 1, $date_time_now);
                    SensorBasicController::addSensorParameterHandbookValue($sensors269['sensor_parameter_id'], $edge_new, 1, $date_time_now);
                } else {
                    SensorBasicController::addSensorParameterValue($sensors83['sensor_parameter_id'], $new_string_xyz, 1, $date_time_now);
                    SensorBasicController::addSensorParameterValue($sensors269['sensor_parameter_id'], $edge_new, 1, $date_time_now);
                }

                $sensor_cache_controller->setSensorParameterValueHash($sensors83['sensor_id'], -1, $new_string_xyz, $sensors83['parameter_id'], $sensors83['parameter_type_id'], 1, $date_time_now);
                $sensor_cache_controller->setSensorParameterValueHash($sensors269['sensor_id'], -1, $edge_new, $sensors269['parameter_id'], $sensors269['parameter_type_id'], 1, $date_time_now);

            }


        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "ReplaceSensorsCurrentEdge. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * метод изменения координат поворота на схеме шахты
     */
    public function actionEditConjunctionUnity()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $places = array();
        $add_edge_list = array();                                                                                         // массив выработок на добавление
        $delete_edge_list = array();                                                                                      // массив выработок на удаление
        $change_edge_list = array();
        $edges_for_update = array();

        try {
            $session = Yii::$app->session;                                                                                  //старт сессии
            $session->open();
            if (!isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
                throw new Exception("actionEditConjunctionUnity. Время сессии закончилось. Требуется повторный ввод пароля");
            }

            if (!AccessCheck::checkAccess($session['sessionLogin'], 78)) {                                        //если пользователю разрешен доступ к функции
                throw new Exception("actionEditConjunctionUnity. Недостаточно прав для совершения данной операции");
            }

            $post = Assistant::GetServerMethod(); //получение данных от ajax-запроса

            $objectKinds = null;
            $flag_exists_id = 0;


            if (isset($post['specific_id']) and $post['specific_id'] != "")                                              // если передан id поворота
            {
                $conjuctionId = $post['specific_id'];                                                                     // id поворота
            } else {
                throw new Exception("actionEditConjunctionUnity. Не передан входной параметр specific_id");
            }

            if (isset($post['mine_id']) and $post['mine_id'] != "")                                              // если передан id поворота
            {
                $mine_id = $post['mine_id'];                                                                     // id поворота
            } else {
                throw new Exception("actionEditConjunctionUnity. Не передан входной параметр mine_id");
            }

            if (isset($post['conjuctionX']) and $post['conjuctionX'] != "")                                                  // если передан координат X
            {
                $conjuctionX = $post['conjuctionX'];                                                                        // координат X
            } else {
                throw new Exception("actionEditConjunctionUnity. Не передан входной параметр conjuctionX");
            }
            if (isset($post['conjuctionY']) and $post['conjuctionY'] != "")                                                  // если передан координат Y
            {
                $conjuctionY = $post['conjuctionY'];                                                                        // координат Y
            } else {
                throw new Exception("actionEditConjunctionUnity. Не передан входной параметр conjuctionY");
            }
            if (isset($post['conjuctionZ']) and $post['conjuctionZ'] != "")                                                  // если передан координат Z
            {
                $conjuctionZ = $post['conjuctionZ'];                                                                        // координат Z
            } else {
                throw new Exception("actionEditConjunctionUnity. Не передан входной параметр conjuctionZ");
            }

            $existingConjuction = Conjunction::findOne(['id' => $conjuctionId]);                                // находим conjuction по id
            if (!$existingConjuction)                                                                                     // если есть поворот по указанному id
            {
                throw new Exception("actionEditConjunctionUnity. Указанного поворота не существует");                                                                             //  если нет такого поворота по указанному id
            }

            /** записываеем в переменные старые координаты. Нужны для перемешения сенсоров с старой выработки в новую */
            $conjuctionX_old = $existingConjuction->x;
            $conjuctionY_old = $existingConjuction->y;
            $conjuctionZ_old = $existingConjuction->z;


            $response = self::AddConjunction($mine_id, $conjuctionX, $conjuctionY, $conjuctionZ);
            if ($response['status'] == 1) {
                $new_conjunction_id = $response['conjunction_id'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('actionMergeEdge. Ошибка добавления сопряжения');
            }

            $edges = (new Query())//забираем все ребра которые принадлежат данному конджакшену
            ->select(
                [
                    'edge_id',
                    'place_id',
                    'conjunction_start_id',
                    'conjunction_end_id',
                    'xStart',
                    'yStart',
                    'zStart',
                    'xEnd',
                    'yEnd',
                    'zEnd',
                    'edge_type_id',
                    'mine_id'
                ])
                ->from(['view_edge_conjunction_place_for_merge'])
                ->where('conjunction_start_id = ' . $conjuctionId . ' or conjunction_end_id = ' . $conjuctionId)
                ->all();
            $mas_edge_id_change = array();                                                                  //массив edge_id выработок которые относятся к данному изменению
            foreach ($edges as $edge) {
                if ($edge['conjunction_start_id'] == $conjuctionId) {
                    $edge_new_length = pow((pow(($conjuctionX - $edge['xEnd']), 2) + pow(($conjuctionY - $edge['yEnd']), 2) + pow(($conjuctionZ - $edge['zEnd']), 2)), 0.5);//рассчитываем новую длину выработки
                    $response = EdgeBasicController::AddEdge($edge['place_id'], $edge['conjunction_end_id'], $new_conjunction_id, $edge['edge_type_id']);  //создаем новую выработку
                    $edge_start_con = 1;
                } else {
                    $edge_new_length = pow((pow(($conjuctionX - $edge['xStart']), 2) + pow(($conjuctionY - $edge['yStart']), 2) + pow(($conjuctionZ - $edge['zStart']), 2)), 0.5);//рассчитываем новую длину выработки
                    $response = EdgeBasicController::AddEdge($edge['place_id'], $new_conjunction_id, $edge['conjunction_start_id'], $edge['edge_type_id']); //создаем новую выработку
                    $edge_start_con = 0;
                }

                if ($response['status'] == 1) {
                    $edge_new_id = $response['edge_id'];
                    $add_edge_list[] = $edge_new_id;
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception("actionEditSpecificObject. Ошибка сохранения  выработки");
                }

                UnityController::actionCopyEdge($edge_new_id, $edge['edge_id'], 'copy', $edge_new_length, 1);//копирование параметров и их значений новой ветви
                $flag_cache_done = (new EdgeCacheController())->runInit($edge['mine_id'], $edge_new_id)['status'];
                self::ReplaceSensorsCurrentEdge($edge['edge_id'], $edge_new_id, $edge['mine_id'], $conjuctionX_old, $conjuctionY_old, $conjuctionZ_old, $edge_start_con);
                EdgeMainController::DeleteEdge($edge['edge_id'], $edge['mine_id']);//удаляем старую выработку
                $delete_edge_list[] = $edge['edge_id'];
                $mas_edge_id_change[] = $edge_new_id;

                $places[] = $edge['place_id'];
                $edges_for_update[] = $edge['edge_id'];
                $mas_edge_id_change[] = $edge['edge_id'];
            }
            EdgeHistoryController::AddEdgeChange($mas_edge_id_change);                                  //записываем изменения по выработкам

        } catch (Throwable $ex) {
            $errors[] = "actionGetSensorsParameters. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        $items['add'] = $add_edge_list;
        $items['delete'] = $delete_edge_list;
        $items['change'] = $change_edge_list;
        $items['test'] = 'Raw';

        $result_main = array('Items' => $items,
            'places' => $places,
            'edges' => $edges_for_update,
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }
}
