<?php

namespace frontend\controllers\positioningsystem;
//ob_start();

use frontend\models\AccessCheck;
use frontend\models\EnergyMine;
use frontend\models\EnergyMineFunction;
use frontend\models\EnergyMineParameter;
use frontend\models\EnergyMineParameterHandbookValue;
use frontend\models\TypeObjectFunction;
use frontend\models\TypeObjectParameter;
use frontend\models\TypeObjectParameterHandbookValue;
use Yii;
use yii\db\Query;
use yii\web\Response;

class SpecificEnergyController extends SpecificObjectController
{
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionAddSpecificObject()
    {
        $errors = array();                                                                                              //создаем массив для ошибок
        $post = Yii::$app->request->post();                                                                             //получение данных от ajax-запроса
        $check_title = 0;                                                                                                 //флаг проверки на существование такого названия в базе
        $check_input_parameters = 1;                                                                                      //флаг проверки входных параметров
        $flag_done = 0;                                                                                                   //флаг успешности выполнения
        $debug_flag = 0;                                                                                                  //отладочный флаг
        $specific_array = array();
        $kind_id = null;
        $specific_title = null;
        $object_id = null;
        $object_type_id = null;
        $main_from_id = null;
        $main_specific_id = null;
        $main_to_id = null;
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 86)) {                                        //если пользователю разрешен доступ к функции
                if (isset($post['title']) and $post['title'] != "" and
                    isset($post['main_from_id']) and $post['main_from_id'] != "" and
                    isset($post['main_to_id']) and $post['main_to_id'] != "") {                                                    //проверка на наличие входных данных а именно на наличие такого названия
                    $specific_title = $post['title'];                                                                           //название нового конкретного объекта, который создаем
                    $main_from_id = $post['main_from_id'];                                                                      //название нового конкретного объекта, который создаем
                    $main_to_id = $post['main_to_id'];                                                                          //название нового конкретного объекта, который создаем
                    $sql_filter = 'title="' . $specific_title . '"';
                    $energy_mines = (new Query())//запрос напрямую из базы по таблице EnergyMine
                    ->select(
                        [
                            'id',
                            'title'
                        ])
                        ->from(['energy_mine'])
                        ->where($sql_filter)
                        ->one();
                    if ($energy_mines) {
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

                    $main_specific_id = $this->actionAddEnergyMine($specific_title, $object_id, $main_from_id, $main_to_id);    //создаем/изменяем запись с таблице EnergyMine
                    if ($debug_flag == 1) echo nl2br("----создан кабель =" . $main_specific_id . "\n");

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
                            $energy_mine_parameter_id = $this->actionAddEnergyMineParameter($main_specific_id, 162, 1); //параметр наименование
                            $energy_mine_parameter_value = $this->actionAddEnergyMineParameterHandbookValue($energy_mine_parameter_id, $specific_title, 1, date("Y-m-d H:i:s"));//сохранение значения параметра
                            if ($energy_mine_parameter_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 162";
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
    public function actionAddEnergyMine($specific_title, $object_id, $main_from_id, $main_to_id)
    {
        $energy_mine_id = parent::actionAddEntryMain('energy_mine');                                              //создаем запись в таблице Main
        if (!is_int($energy_mine_id)) return -1;
        else {
            $newSpecificObject = new EnergyMine();                                                                      //сохраняем все данные в нужной модели
            $newSpecificObject->id = $energy_mine_id;                                                                   //айдишнек новой созданной трубы
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
                $energy_mine_parameter_id = $this->actionAddEnergyMineParameter($specific_object_id, $type_object_parameter->parameter_id, $type_object_parameter->parameter_type_id);

                //ищем последние справочное значения параметра типового объекта и копируем их в значение справочное конкретного объекта
                if ($energy_mine_parameter_id
                    and $typical_object_parameter_handbook_values = TypeObjectParameterHandbookValue::find()
                        ->where(['type_object_parameter_id' => $type_object_parameter->id])
                        ->orderBy(['date_time' => SORT_DESC])
                        ->one())
                    $flag_done = $this->actionAddEnergyMineParameterHandbookValue($energy_mine_parameter_id, $typical_object_parameter_handbook_values->value, $typical_object_parameter_handbook_values->status_id);
            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись справочных параметров" . "\n");

        //копирование функций типового объекта
        //находим функции типового объекта
        if ($type_object_functions = TypeObjectFunction::find()->where(['object_id' => $typical_object_id])->all()) {
            foreach ($type_object_functions as $type_object_function) {
                $energy_mine_function_id = $this->actionAddEnergyMineFunction($specific_object_id, $type_object_function->func_id);
                if ($energy_mine_function_id == -1) $flag_done = -1;
            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись функций" . "\n");
        return $flag_done;
    }

    //создание параметра конкретной трубы
    public function actionAddEnergyMineParameter($energy_mine_id, $parameter_id, $parameter_type_id)
    {
        $debug_flag = 0;

        if ($debug_flag == 1) echo nl2br("----зашел в функцию создания параметров кабеля  =" . $energy_mine_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        if ($energy_mine_parameter = EnergyMineParameter::find()->where(['energy_mine_id' => $energy_mine_id, 'parameter_id' => $parameter_id, 'parameter_type_id' => $parameter_type_id])->one()) {
            if ($debug_flag == 1) {
                echo nl2br("----зашли в условие, когда найдены параметры кабеля \n");
            }
            return $energy_mine_parameter->id;
        } else {
            if ($debug_flag == 1) {
                echo nl2br("----зашли в условие, когда не найдены параметры кабеля, и создается новый параметр. \n");
            }
            $energy_mine_parameter_new = new EnergyMineParameter();
            $energy_mine_parameter_new->energy_mine_id = $energy_mine_id;                                                        //айди водоснабжения
            $energy_mine_parameter_new->parameter_id = $parameter_id;                                                      //айди параметра
            $energy_mine_parameter_new->parameter_type_id = $parameter_type_id;                                            //айди типа параметра

            if ($energy_mine_parameter_new->save()) return $energy_mine_parameter_new->id;
            else return (-1); //"Ошибка сохранения значения параметра водоснабжения" . $energy_mine_id->id;
        }
    }

    //сохранение справочного значения конкретного параметра трубы
    public function actionAddEnergyMineParameterHandbookValue($energy_mine_parameter_id, $value, $status_id = 1, $date_time = 1)
    {
        $energy_mine_parameter_handbook_value = new EnergyMineParameterHandbookValue();
        $energy_mine_parameter_handbook_value->energy_mine_parameter_id = $energy_mine_parameter_id;
        if ($date_time == 1) $energy_mine_parameter_handbook_value->date_time = date("Y-m-d H:i:s", strtotime("-1 second"));
        else $energy_mine_parameter_handbook_value->date_time = $date_time;
        $energy_mine_parameter_handbook_value->value = strval($value);
        $energy_mine_parameter_handbook_value->status_id = $status_id;

        if (!$energy_mine_parameter_handbook_value->save()) {
            return (-1);
        } else return 1;
    }

    //сохранение функций трубы
    public function actionAddEnergyMineFunction($energy_mine_id, $function_id)
    {
        $debug_flag = 0;
        if ($debug_flag == 1) echo nl2br("----зашел в функцию создания функции трубы  =" . $energy_mine_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        if ($energy_mine_function = EnergyMineFunction::find()->where(['energy_mine_id' => $energy_mine_id, 'function_id' => $function_id])->one()) {
            return $energy_mine_function->id;
        } else {
            $energy_mine_function_new = new EnergyMineFunction();
            $energy_mine_function_new->energy_mine_id = $energy_mine_id;                                                                  //айди трубы
            $energy_mine_function_new->function_id = $function_id;                                                            //айди функции
            //статус значения

            if ($energy_mine_function_new->save()) return $energy_mine_function_new->id;
            else return -1;
        }
    }
}
