<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\positioningsystem;
//ob_start();


use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\cachemanagers\ServiceCache;
use backend\controllers\CoordinateController;
use backend\controllers\SensorBasicController;
use backend\controllers\SensorMainController;
use backend\controllers\StrataJobController;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\AccessCheck;
use frontend\models\Asmtp;
use frontend\models\GroupAlarm;
use frontend\models\KindParameter;
use frontend\models\ObjectType;
use frontend\models\Place;
use frontend\models\Sensor;
use frontend\models\SensorConnectString;
use frontend\models\SensorFunction;
use frontend\models\SensorParameter;
use frontend\models\SensorParameterHandbookValue;
use frontend\models\SensorParameterSensor;
use frontend\models\SensorParameterValue;
use frontend\models\SensorType;
use frontend\models\TypeObjectFunction;
use frontend\models\TypeObjectParameter;
use frontend\models\TypeObjectParameterHandbookValue;
use frontend\models\TypeObjectParameterValue;
use frontend\models\TypicalObject;
use Throwable;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\Response;


class SpecificSensorController extends SpecificObjectController
{
    // actionInitSensorMain             - метод инициализации кеша сенсоров по всей шахте
    // actionGetSensorOpcTags           - метод получения списка тегов OPC
    // actionDeleteSpecificObjectBase   - функция удаления сенсора

    /*автоматизированная система*/
    public function actionIndex()
    {
        return $this->render('index');
    }

    /*
     * функция удаления оборудования
     * */
    public function actionDeleteSpecificObject()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $errors = array();
        $objectKinds = null;
        if (isset($post['specific_id']) && isset($post['table_name'])
            && isset($post['kind_object_id']) && isset($post['object_type_id'])
            && isset($post['object_id'])) {//если все данные переданы
            $toDelete = true;//переменная-флажок разрешающая удаление
            $specificId = $post['specific_id'];
            $specificObject = Sensor::findOne($post['specific_id']);
            if ($specificObject) {//если объект существует
                $parameters = SensorParameter::findAll(['sensor_id' => $specificId]);
                $functions = SensorFunction::findAll(['sensor_id' => $specificId]);
//                if($parameters){
//                    foreach ($parameters as $parameter) {
//                        if($parameter->$tableNameGetParameterValue()->all()){
//                            $toDelete = false;
//                            break;
//                        }
//                    }
//                }
                if ($functions) {
                    foreach ($functions as $function) {
                        $function->delete();
                    }
                }
                if ($toDelete) {
                    foreach ($parameters as $parameter) {
                        if ($values = $parameter->getSensorParameterValues()->all()) {
                            foreach ($values as $value) {
                                $value->delete();
                            }
                        }
                        if ($handbookValues = $parameter->getSensorParameterHandbookValues()->all()) {
                            foreach ($handbookValues as $handbookValue) {
                                $handbookValue->delete();
                            }
                        }
                        if ($sensors = $parameter->getSensorParameterSensors()->all()) {
                            foreach ($sensors as $sensor) {
                                $sensor->delete();
                            }
                        }
                        $parameter->delete();
                    }

                    $specificObject->delete();
                } else {
                    $errors[] = "Нельзя удалить объект из-за наличия значений у параметров объекта " . $specificObject->id;
                }
            }
        } else {
            $errors[] = "Данные не переданы";//сохранить соответствующую ошибку
        }
        $objectKinds = $this->buildSpecificObjectArray($post['kind_object_id'], $post['object_type_id'], $post['object_id']);
        $result = array('errors' => $errors, 'specificObjects' => $objectKinds);//составить результирующий массив как массив полученных массивов
        echo json_encode($result);//вернуть AJAX-запросу данные и ошибки
    }

    /*
     * функция редактирования конкретных объектов
     * */
    public function actionEditSpecificObject()
    {
        $errors = array();
        $objectKinds = null;
        $specificParameters = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 87)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['title']) && isset($post['specific_id'])
                    && isset($post['kind_object_id']) && isset($post['object_type_id']) && isset($post['object_id'])) {          //проверка на передачу данных
                    $specificId = $post['specific_id'];
                    $title = $post['title'];
                    $object = Sensor::findOne($specificId);                                                                     //найти объект по id
                    if ($object) {                                                                                                //если объект существует
                        $existingObject = Sensor::findOne(['title' => $post['title']]);                                         //найти объект по названию, чтобы не было дублирующих
                        if (!$existingObject) {                                                                                   //если не найден
                            $object->title = $title;                                                                            //сохранить в найденный по id параметр название
                            if ($object->save()) {                                                                                //если объет сохранился
                                $objectKinds = parent::buildSpecificObjectArray($post['kind_object_id'], $post['object_type_id'], $post['object_id']);//обновить массив типовых объектов
                                $response = self::buildsensorParameterArray($specificId);
                                if ($response['status'] == 1) {
                                    $specificParameters = $response['Items'];
                                } else {
                                    $errors[] = $response['errors'];
                                }
                            } else {
                                $errors[] = "Ошибка сохранения";
                            }                                                               //если не сохранился, сохранить соответствующую ошибку
                        } else {
                            $errors[] = "Объект с таким названием уже существует";
                        }                                             //если найден объект по названию, сохранить соответствующую ошибку
                    } else {
                        $errors[] = "Объекта с id " . $specificId . " не существует";
                    }                                              //если не найден объект по id, сохранить соответствующую ошибку
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
        $result = array('errors' => $errors, 'objectKinds' => $objectKinds, 'specificParameters' => $specificParameters);                                            //составить результирующий массив как массив полученных массивов
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;                                                                            //вернуть AJAX-запросу данные и ошибки
    }

    //!!!!
    //т.к. я заебался ждать пока будет разработан контроллер для создания сенсоров,  который будет работать так как надо
    // то все методы ниже были написаны Якимовы М.Н. за ночь 09.06.2018
    public function actionAddSpecificObjectBase()
    {
        $errors = array();//создаем массив для ошибок
        $warnings = array();
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $check_title = 0;                                                                                                 //флаг проверки на существование такого названия в базе
        $check_input_parameters = 1;                                                                                      //флаг проверки входных параметров
        $flag_done = 0;                                                                                                   //флаг успешности выполнения
        $debug_flag = 0;                                                                                                  //отладочный флаг
        $main_specific_id = -1;
        $object_id = null;
        $specific_title = null;
        $kind_id = null;
        $object_type_id = null;
        $object_id = null;
        $specific_array = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 86)) {                                        //если пользователю разрешен доступ к функции
                if (isset($post['title']) and $post['title'] != "") {                                                               //проверка на наличие входных данных а именно на наличие такого названия
                    $specific_title = $post['title'];                                                                                //название нового конкретного объекта, который создаем
                    $sql_filter = "title='" . $specific_title . "'";
                    $sensors = (new Query())//запрос напрямую из базы по таблице Sensor
                    ->select(
                        [
                            'id',
                            'title'
                        ])
                        ->from(['sensor'])
                        ->where($sql_filter)
                        ->one();
                    if ($sensors) {
                        $errors[] = "Объект с именем " . $specific_title . " уже существует";
                        $check_title = -1;
                    } else $check_title = 1;                                                                                       //название не существует в базе, можно добавлять объект
                    if ($debug_flag == 1) echo nl2br("----прошел проверку на наличие такого тега (1 тега нет)  =" . $check_title . "\n");
                } else {
                    $errors[] = "Не все входные данные есть в методе POST";
                    $check_input_parameters = -1;
                    if ($debug_flag == 1) echo nl2br("----проверка на наличие тега /проверка на наличие входных данных 1 входные данные есть =" . $check_input_parameters . "\n");
                }

                if (isset($post['object_id']) and $post['object_id'] != "") {                                                       //проверка на наличие входных данных а именно на наличие типового объекта, который копируется
                    $object_id = $post['object_id'];                                                                             //айдишник типового объекта
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
                        $kind_id = $typical_objects["kind_object_id"];                                                               //вид типового объекта ИД
                        $object_type_id = $typical_objects["object_type_id"];                                                         //тип типового объекта ИД
                        if ($debug_flag == 1) echo nl2br("----проверка на типовой объект - он существует =" . $object_id . "\n");
                    }
                } else {
                    $errors[] = "Не все входные данные есть в методе POST";
                    $check_input_parameters = -1;
                    if ($debug_flag == 1) echo nl2br("----проверка на наличие типового объекта входные данные есть =" . $check_input_parameters . "\n");
                }
                if ($check_input_parameters == 1 and $check_title == 1) {                                                             //все нужные входные данные есть и название не существует в базе
                    $warnings[] = "actionAddSpecificObjectBase. Начинаю сохранять в базу";
                    $new_errors = SensorBasicController::addSensor($specific_title, $object_id);
                    if ($new_errors['status'] == 1) {
                        $warnings[] = "actionAddSpecificObjectBase. Сохранил в БД успешно, Инициаизирую кеш параметро сенсора";

                        $sensor_id = $new_errors['sensor_id'];
                        $warnings[] = $new_errors['warnings'];
                        $sensor_parameter_handbook_value = $new_errors['sensor_parameter_handbook_value'];
                        $warnings[] = $sensor_parameter_handbook_value;
                        $response = (new SensorCacheController)->multiSetSensorParameterValueHash($sensor_parameter_handbook_value);
                        if ($response['status'] == 1) {
                            $warnings[] = $response['warnings'];
                            $main_specific_id = $new_errors['sensor_id'];
                            $warnings[] = "actionAddSpecificObjectBase. Инициализировал кеш параметров сенсора.Инициализирую главный кеш сенсора";
                            $response = (new SensorCacheController)->initSensorMainHash(-1, $sensor_id);
                            if ($response['status'] == 1) {
                                $warnings[] = $response['warnings'];
                                $warnings[] = "actionAddSpecificObjectBase. Успешно закончил инициализирвоать главный кеш сенсора";
                                unset($new_errors);
                                $new_errors['errors'] = array();
                                unset($errors);
                                $errors = array();
                            } else {
                                $warnings[] = $response['warnings'];
                                $errors[] = $response['errors'];
                            }
                        } else {
                            $warnings[] = $response['warnings'];
                        }
                    }
                    foreach ($new_errors['errors'] as $err) {
                        $errors[] = $err;
                    }
                } else {
                    $errors[] = "Объект с именем " . $specific_title . " уже существует";
                }
                $specific_array = parent::buildSpecificObjectArray($kind_id, $object_type_id, $object_id);//вызываем функция построения массива конкретных объектов нужного типа
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        $result = array('specificArray' => $specific_array, 'errors' => $errors, 'specific_id' => $main_specific_id, 'warnings' => $warnings);//создаем массив для передачи данных по ajax запросу со значениями и ошибками
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }


    /**
     * Название метода: methodDeleteSensor()
     * Метод удаления конкретного сенсора и всех связанных с ним параметров
     * Вынес в отдельный метод из actionDeleteSpecificObjectBase(), так как понадобился в другом методе
     *
     *
     * @param $sensor_id
     * @return array
     *
     * Входные необязательные параметры
     *
     * @package app\controllers
     *
     * Входные обязательные параметры:
     * @see
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 08.04.2019 11:00
     * @since ver
     */
    public static function methodDeleteSensor($sensor_id)
    {
        $errors = array();
        $sensor = Sensor::findOne($sensor_id);
        if ($sensor) {                                                                                                                                              //если объект существует

            SensorFunction::deleteAll('sensor_id=:sensor_id', [':sensor_id' => $sensor_id]);                                                               //удаляем функции у сенсора

            $sensor_parameters = SensorParameter::findAll(['sensor_id' => $sensor_id]);                                                                             //ищем параметры на удаление
            foreach ($sensor_parameters as $sensor_parameter) {
                SensorParameterValue::deleteAll('sensor_parameter_id=:sensor_parameter_id', [':sensor_parameter_id' => $sensor_parameter->id]);             //удаляем измеренные или вычесленные значения
                SensorParameterHandbookValue::deleteAll('sensor_parameter_id=:sensor_parameter_id', [':sensor_parameter_id' => $sensor_parameter->id]);     //удаляем справочные значения
                SensorParameterSensor::deleteAll('sensor_parameter_id=:sensor_parameter_id', [':sensor_parameter_id' => $sensor_parameter->id]);            //удаляем привязанные сенсоры к сенсорам
                SensorParameter::deleteAll('id=:id', [':id' => $sensor_parameter->id]);                                                                     //удаляем сам параметр сенсора
            }
            SensorConnectString::deleteAll('sensor_id=:sensor_id', [':sensor_id' => $sensor_id]);                                                           //удаляем строку подключения сенсора

            Sensor::deleteAll('id=:id', [':id' => $sensor_id]);                                                                                             //удаляем сам сенсор

            $sensor_cache_controller = (new SensorCacheController());
            $sensor_cache_controller->delParameterValueHash($sensor_id);
            $sensor_cache_controller->delInSensorMineHash($sensor_id, AMICUM_DEFAULT_MINE);

        } else {
            $errors[] = "Сенсора с идентификатором " . $sensor_id . " нет в базе данных";
        }
        return $errors;
    }

    //метод создания конкретного объекта в его базовой таблице
    public static function actionAddSensorBase($sensor_title, $object_id, $asmtp_id, $sensor_type_id)
    {
//        echo "sensor_title = ".$sensor_title."\n";
//        echo "object_id = ".$object_id."\n";
//        echo "asmtp_id = ".$asmtp_id."\n";
//        echo "sensor_type_id = ".$sensor_type_id."\n";
        $sensor_id = parent::actionAddEntryMain('sensor');                                          //создаем запись в таблице Main
//        echo "sensor id is $sensor_id\n";
        if (!is_int($sensor_id)) return -1;
        else {
            $newSpecificObject = new Sensor();//сохраняем все данные в нужной модели
            $newSpecificObject->id = $sensor_id;
            $newSpecificObject->title = $sensor_title;
            $newSpecificObject->asmtp_id = $asmtp_id;
            $newSpecificObject->sensor_type_id = $sensor_type_id;
            $newSpecificObject->object_id = $object_id;
//            var_dump($newSpecificObject);
            if (!$newSpecificObject->save()) return -1;                                                                      //проверка на сохранение нового объекта
            else return $newSpecificObject->id;
        }
    }

    //метод поиска части параметров типового объекта для переноса в базовую таблицу конкретного объекта
    public static function actionFindTypicalParametersToSensorBase($object_id, $parameter_id, $parameter_type_id)
    {
//        echo "object_id = ".$object_id."\n";
//        echo "parameter_id = ".$parameter_id."\n";
//        echo "parameter_type_id = ".$parameter_type_id."\n";

        if ($parameter_type_id == 1) {
            $object_parameter_id = TypeObjectParameter::find()//ищем параметр тпового объекта
            ->where(['object_id' => $object_id, 'parameter_id' => $parameter_id, 'parameter_type_id' => $parameter_type_id])
                ->one();
//            var_dump($object_parameter_id);
            if ($object_parameter_id) {
                $object_id = TypeObjectParameterHandbookValue::find()//ищем последнее значение типового объекта
                ->where(['type_object_parameter_id' => $object_parameter_id->id])
                    ->orderBy(['date_time' => SORT_DESC])
                    ->one();
                if ($object_id) return $object_id->value;
                else return -1;
            } else return -1;
        } else return -1;
    }

    //метод копирует параметры типового параметра в параметры конкретного объекта - нужен для создания конкретного объекта по шаблону типового объекта
    public static function actionCopyTypicalParametersToSpecific($typical_object_id, $specific_object_id)
    {
        $debug_flag = 0;                                                                                                  //отладочный флаг
        $flag_done = 1;                                                                                                   //флаг успешного выполнения метода
        //копирование параметров справочных
        if ($type_object_parameters = TypeObjectParameter::find()->where(['object_id' => $typical_object_id, 'parameter_type_id' => 1])->all())                           //Находим все параметры типового объекта
        {
            foreach ($type_object_parameters as $type_object_parameter) {
                //создаем новый параметр у конкретного объекта
                $sensor_parameter_id = self::actionAddSensorParameter($specific_object_id, $type_object_parameter->parameter_id, $type_object_parameter->parameter_type_id);

                //ищем последние справочное значения параметра типового объекта и копируем их в значение справочное конкретного объекта
                if ($sensor_parameter_id
                    and $typical_object_parameter_handbook_values = TypeObjectParameterHandbookValue::find()
                        ->where(['type_object_parameter_id' => $type_object_parameter->id])
                        ->orderBy(['date_time' => SORT_DESC])
                        ->one())
                    $flag_done = self::actionAddSensorParameterHandbookValue($sensor_parameter_id, $typical_object_parameter_handbook_values->value, $typical_object_parameter_handbook_values->status_id, 1);
            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись справочных параметров" . "\n");

        if ($type_object_parameters = TypeObjectParameter::find()->where(['object_id' => $typical_object_id, 'parameter_type_id' => 2])->all())                           //Находим все параметры типового объекта
        {
            foreach ($type_object_parameters as $type_object_parameter) {
                //создаем новый параметр у конкретного объекта
                $sensor_parameter_id = self::actionAddSensorParameter($specific_object_id, $type_object_parameter->parameter_id, $type_object_parameter->parameter_type_id);

                //ищем последние справочное значения параметра типового объекта и копируем их в значение справочное конкретного объекта
                if ($sensor_parameter_id
                    and $typical_object_parameter_values = TypeObjectParameterValue::find()
                        ->where(['type_object_parameter_id' => $type_object_parameter->id])
                        ->orderBy(['date_time' => SORT_DESC])
                        ->one())
                    $flag_done = self::actionAddSensorParameterValue($sensor_parameter_id, $typical_object_parameter_values->value, $typical_object_parameter_values->status_id, 1);
            }
        }
        if ($type_object_parameters = TypeObjectParameter::find()->where(['object_id' => $typical_object_id, 'parameter_type_id' => 3])->all())                           //Находим все параметры типового объекта
        {
            foreach ($type_object_parameters as $type_object_parameter) {
                //создаем новый параметр у конкретного объекта
                $sensor_parameter_id = self::actionAddSensorParameter($specific_object_id, $type_object_parameter->parameter_id, $type_object_parameter->parameter_type_id);

            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись справочных параметров" . "\n");
        //копирование функций типового объекта
        //находим функции типового объекта
        if ($type_object_functions = TypeObjectFunction::findAll(['object_id' => $typical_object_id])) {

            foreach ($type_object_functions as $type_object_function) {
                //echo "список функций ". var_dump($type_object_function);
                //echo "список функций ". var_dump($type_object_function->func_id);
                $sensor_function_id = self::actionAddSensorFunction($specific_object_id, $type_object_function->func_id);
                if ($sensor_function_id == -1) $flag_done = -1;
            }
        }
        if ($debug_flag == 1) echo nl2br("прошел запись функций" . "\n");
        return $flag_done;
    }

    //создание параметра конкретного сенсора
    public static function actionAddSensorParameter($sensor_id, $parameter_id, $parameter_type_id)
    {
        $debug_flag = 0;

        if ($debug_flag == 1) echo nl2br("----зашел в функцию создания параметров сенсора  =" . $sensor_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        if ($sensor_parameter = SensorParameter::find()
            ->where
            (['parameter_type_id' => (int)$parameter_type_id,
                'parameter_id' => (int)$parameter_id,
                'sensor_id' => (int)$sensor_id])
            ->one()) {
            return $sensor_parameter->id;
        } else {
            $sensor_parameter_new = new SensorParameter();
            $sensor_parameter_new->sensor_id = (int)$sensor_id;                                                                 //айди сенсора
            $sensor_parameter_new->parameter_id = (int)$parameter_id;                                                           //айди параметра
            $sensor_parameter_new->parameter_type_id = (int)$parameter_type_id;                                                 //айди типа параметра

            if ($sensor_parameter_new->save()) {
                $sensor_parameter_new->refresh();
                return $sensor_parameter_new->id;
            } else return (-1); //"Ошибка сохранения значения параметра сенсора" . $sensor_id->id;
        }
    }

    //метод удаления параметров для сенсоров

    /**
     * Название метода: actionDeleteSensorParameterBase()
     * @package app\controllers
     * Метод удаления параметр сенсора.
     * С помощью этого метода можно удалить только один тип параметра, и сам параметр.
     * При удалении данные удаляются по умолчанию из кэша с помощью очереди.
     * Входные обязательные параметры:
     * $post['action_type'] - тип действия. Если local - то удаление только значений типа параметраю
     *    Если 'global'  - удаление параметра со всеми значениями и самого параметра
     * ['specific_object_id'] - идентификатор сенсора.
     * Входные необязательные параметры
     * $post['specific_parameter_id'] - идентификатор sensor_parameter_id
     * $post['parameter_id'] - идентификатор параметра
     *
     * @url http://localhost/specific-sensor/delete-sensor-parameter-base
     * @url http://localhost/specific-sensor/delete-sensor-parameter-base?table_name=sensor&parameter_id=448&action_type=global&specific_parameter_id=&specific_object_id=8266
     *
     * Документация на портале: http://192.168.1.4/products/community/modules/forum/posts.aspx?&t=193&p=1#211
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 17.01.2019 8:59
     * @since ver0.2
     */
    public function actionDeleteSensorParameterBase()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
//        ini_set('max_execution_time', 600000);
//        ini_set('memory_limit', "20500M");
        $paramsArray = array();
        try {
            $warnings[] = "actionDeleteSensorParameterBase. Начал выполнять метод";
            /** @var $use_add_by_queue - переменная указывает на то, что использовать очередь при добавлении в кэш
             * Если указать 0, то данные добавляются сразу в кэш.
             * Если указать 1, то данные добавляются в кэш с помощью очереди!
             * Нам нужно, чтоб данные добавились в кэш путем очереди, поэтому включем этот параметр, указывая 1.
             */
            $use_add_by_queue = 1;
            $session = Yii::$app->session;                                                                                  //старт сессии
            $session->open();                                                                                               //открыть сессию
            if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
                if (AccessCheck::checkAccess($session['sessionLogin'], 91)) {                                        //если пользователю разрешен доступ к функции
                    $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                    if (isset($post['action_type']) and $post['action_type'] != "" and
                        isset($post['specific_object_id']) and $post['specific_object_id'] != "") {
                        $sensorCacheController = new SensorCacheController();
                        $actionType = $post['action_type'];
                        $specificObjectId = $post['specific_object_id'];
                        $specificParameterId = $post['specific_parameter_id'];
                        if ($actionType == "local") {
                            if (isset($post['specific_parameter_id']) and $post['specific_parameter_id'] != "") {


                                /***** Удаляем значений конкретного параметра сенсора из кэша *****************************/
                                $sensor_parameter = SensorParameter::findOne(['id' => $specificParameterId]);
                                $parameter_id = $sensor_parameter->parameter_id;
                                $parameter_type_id = $sensor_parameter->parameter_type_id;
                                $sensor_del_cache = $sensorCacheController->delParameterValueHash($specificObjectId, $parameter_id, $parameter_type_id);
                                $errors = array_merge($sensor_del_cache['errors'], $errors);
                                $warnings[] = $sensor_del_cache['warnings'];
                                if ($parameter_id == 346) {
                                    $sensorCacheController->delInSensorMineHash($specificObjectId, AMICUM_DEFAULT_MINE);
                                }
                                /***** Удаляем из БД *******************************************************************/
                                SensorParameterSensor::deleteAll(['sensor_parameter_id' => $specificParameterId]);
                                SensorParameterHandbookValue::deleteAll(['sensor_parameter_id' => $specificParameterId]);
                                SensorParameterValue::deleteAll(['sensor_parameter_id' => $specificParameterId]);
                                SensorParameter::deleteAll(['id' => $specificParameterId]);
                                $response = self::buildsensorParameterArray($specificObjectId);
                                if ($response['status'] == 1) {
                                    $paramsArray = $response['Items'];
                                } else {
                                    $errors[] = $response['errors'];
                                }
                            } else {
                                $errors[] = "Не передан sensor_parameter_id";
                            }
                        } else {
                            if (isset($post['parameter_id']) and $post['parameter_id'] != "") {
                                $parameterId = $post['parameter_id'];
                                $parameters = SensorParameter::find()->where(['parameter_id' => $parameterId, 'sensor_id' => $specificObjectId])->all();
                                foreach ($parameters as $parameter) {
                                    SensorParameterSensor::deleteAll(['sensor_parameter_id' => $parameter->id]);
                                    SensorParameterHandbookValue::deleteAll(['sensor_parameter_id' => $parameter->id]);
                                    SensorParameterValue::deleteAll(['sensor_parameter_id' => $parameter->id]);
                                }
                                SensorParameter::deleteAll(['parameter_id' => $parameterId, 'sensor_id' => $specificObjectId]);
                                /**
                                 * Удаление из кэша
                                 */
                                $sensor_del_cache = $sensorCacheController->delParameterValueHash($specificObjectId, $parameterId);
                                $errors = array_merge($sensor_del_cache['errors'], $errors);
                                $warnings[] = $sensor_del_cache['warnings'];
                                if ($parameterId == 346) {
                                    $sensorCacheController->delInSensorMineHash($specificObjectId, AMICUM_DEFAULT_MINE);
                                }
                                $response = self::buildsensorParameterArray($specificObjectId);
                                if ($response['status'] == 1) {
                                    $paramsArray = $response['Items'];
                                } else {
                                    $errors[] = $response['errors'];
                                }
                            } else {
                                $errors[] = "не передан parameter_id";
                            }
                        }
                        $sensorCacheController->delSenParSenTag('*', $specificParameterId);

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
        } catch (Throwable $exception) {
            $errors[] = "actionDeleteSensorParameterBase. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result = array('Items' => "", 'paramArray' => $paramsArray, 'errors' => $errors, 'warnings' => $warnings, 'status' => $status);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    //добавление нового параметра сенсора из страницы фронтэнда
    public function actionAddSensorParameterBase()
    {
        $sensor_id = null;
        $errors = array();
        $paramsArray = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 90)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['id']) && isset($post['parameter_id']) && isset($post['parameter_type_id'])) {
                    $sensor_id = $post['id'];
                    $parameter_id = $post['parameter_id'];
                    $parameter_type_id = $post['parameter_type_id'];

                    $sensor_parameter = $this->actionAddSensorParameter($sensor_id, $parameter_id, $parameter_type_id);

                    $response = (new SensorCacheController())->setSensorParameterValueHash($sensor_id, $sensor_parameter, 0, $parameter_id, $parameter_type_id);
                    if ($response['status'] == 1) {
                        $warnings[] = "actionAddSensorParameterBase. Сохранение значения параметра 164 в БД. Ключ добавленного значения $sensor_parameter";
                    } else {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        throw new Exception('actionAddSensorParameterBase. Не смог создать в кеше новый параметр');
                    }
                    if ($sensor_parameter == -1) $errors[] = "не удалось сохранить параметр";
                    $response = self::buildsensorParameterArray($sensor_id);
                    if ($response['status'] == 1) {
                        $paramsArray = $response['Items'];
                    } else {
                        $errors[] = $response['errors'];
                    }

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

    //сохранение значения конкретного параметра сенсора
    public static function actionAddSensorParameterValue($sensor_parameter_id, $value, $status_id, $date_time)
    {
        $sensor_parameter_value_place = new SensorParameterValue();
        $sensor_parameter_value_place->sensor_parameter_id = $sensor_parameter_id;
        if ($date_time == 1) $sensor_parameter_value_place->date_time = date("Y-m-d H:i:s", strtotime("-1 second"));
        else $sensor_parameter_value_place->date_time = $date_time;
        $sensor_parameter_value_place->value = strval($value);
        $sensor_parameter_value_place->status_id = $status_id;

        if (!$sensor_parameter_value_place->save()) {
            return (-1);
        } else {
            self::addSensorParameterValueToCache($sensor_parameter_id, $value, $date_time, $status_id);
            return 1;
        }
    }

    /**
     * сохранение справочного значения конкретного параметра сенсора
     * @param $sensor_parameter_id
     * @param $value
     * @param $status_id
     * @param $date_time
     * @return int
     */
    public static function actionAddSensorParameterHandbookValue($sensor_parameter_id, $value, $status_id, $date_time)
    {
        $sensor_parameter_handbook_value = new SensorParameterHandbookValue();
        $sensor_parameter_handbook_value->sensor_parameter_id = $sensor_parameter_id;
        if ($date_time == 1) $sensor_parameter_handbook_value->date_time = date("Y-m-d H:i:s", strtotime("-1 second"));
        else $sensor_parameter_handbook_value->date_time = $date_time;
        $sensor_parameter_handbook_value->value = strval($value);
        $sensor_parameter_handbook_value->status_id = $status_id;

        if (!$sensor_parameter_handbook_value->save()) {
            return (-1);
        } else {
            return 1;
        }
    }

    public static function actionAddSensorParameterHandbookValueDatabase($sensor_parameter_id, $value, $status_id, $date_time)
    {
        $sensor_parameter_handbook_value = new SensorParameterHandbookValue();
        $sensor_parameter_handbook_value->sensor_parameter_id = $sensor_parameter_id;
        if ($date_time == 1) $sensor_parameter_handbook_value->date_time = date("Y-m-d H:i:s", strtotime("-1 second"));
        else $sensor_parameter_handbook_value->date_time = $date_time;
        $sensor_parameter_handbook_value->value = strval($value);
        $sensor_parameter_handbook_value->status_id = $status_id;

        if (!$sensor_parameter_handbook_value->save()) {
            return (-1);
        } else {
            return 1;
        }
    }

    //Функция добавления в кеш значений параметра сенсора
    public static function addSensorParameterValueToCache($sensor_parameter_id, $value, $date_time, $status_id)
    {
        $sensor_parameters = (new Query())
            ->select('*')
            ->from('sensor_parameter')
            ->where('id = ' . $sensor_parameter_id)
            ->one();
        $sensor_id = $sensor_parameters['sensor_id'];
        $typeParameterParameterId = $sensor_parameters['parameter_type_id'] . '-' . $sensor_parameters['parameter_id'];
        //$sensor = Sensor::findOne(['id' => $sensor_id]);
        $sensor = (new Query())
            ->select('*')
            ->from('sensor')
            ->where(['id' => $sensor_id])
            ->one();
        /*$sensor_mine = (new Query())
            ->select('mine_id')
            ->from('view_sensor_main')
            ->where('sensor_id = ' . $sensor_id)
            ->one();*/
        $sensor_mine = (new Query())
            ->select('mine_id')
            ->from('view_sensor_mine_unity')
            ->where(['sensor_id' => $sensor_id])
            ->one();
        StrataJobController::saveSensorParameterToCache($sensor, $typeParameterParameterId, $sensor_parameter_id, $value, $date_time, $status_id);
    }

    //сохранение функций сенсора
    public static function actionAddSensorFunction($sensor_id, $function_id)
    {
        $debug_flag = 0;
        if ($debug_flag == 1) echo nl2br("----зашел в функцию создания функции сенсоров  =" . $sensor_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        if ($sensor_function = SensorFunction::find()->where(['sensor_id' => $sensor_id, 'function_id' => $function_id])->one()) {
            return $sensor_function->id;
        } else {
            $sensor_function_new = new SensorFunction();
            $sensor_function_new->sensor_id = $sensor_id;                                                                      //айди сенсора
            $sensor_function_new->function_id = $function_id;                                                                  //айди функции
            //статус значения

            if ($sensor_function_new->save()) return $sensor_function_new->id;
            else return -1;
        }
    }

    //функция добавления функции сенсору с post с фронта
    public function actionAddSensorFunctionFront()
    {
        $errors = array();
        $functionsArray = array();
        $session = Yii::$app->session;
        $session->open();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 94)) {                                       //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['specificObjectId']) && isset($post['specificObjectId']) && isset($post['functionId']) && isset($post['functionId'])) {
                    $sensor_id = $post['specificObjectId'];
                    $function_id = $post['functionId'];
                    $sensor_function = $this->actionAddSensorFunction($sensor_id, $function_id);
                    if ($sensor_function == -1) $errors[] = "не удалось сохранить параметр";
                    $functionsArray = parent::buildSpecificFunctionArray($sensor_id, "sensor");
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

    //обновление значения функции привязанной к конкретному сенсору
    public function actionUpdateSensorFunction($sensor_function_id, $sensor_id, $function_id)
    {
        $debug_flag = 0;
        if ($debug_flag == 1) echo nl2br("----зашел в функцию редактирования функции сенсоров  =" . $sensor_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        $sensor_function_update = SensorFunction::find()->where(['id' => $sensor_function_id])->one();
        if ($sensor_function_update) {
            $sensor_function_update->sensor_id = $sensor_id;                                                                      //айди сенсора
            $sensor_function_update->function_id = $function_id;                                                                  //айди функции

            if ($sensor_function_update->save()) {
                $functionsArray = parent::buildSpecificFunctionArray($sensor_id, "sensor");                     //создаем список функций на возврат
                $result = array('funcArray' => $functionsArray);
                echo json_encode($result);
                return $sensor_function_update->id;
            } else return -1;
        }
    }


    //функция удаления функции сенсору с post с фронта
    public function actionDeleteSensorFunction()
    {

        $object_functions = array();
        $errors = array();
        $session = Yii::$app->session;
        $session->open();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 95)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['specific_object_id']) && isset($post['specific_object_id']) && isset($post['specific_function_id']) && isset($post['specific_function_id'])) {
                    $sensor_id = $post['specific_object_id'];
                    $sensor_function_id = $post['specific_function_id'];
                    SensorFunction::deleteAll('id=:sensor_function_id', [':sensor_function_id' => $sensor_function_id]);

                    $objects = (new Query())
                        ->select(
                            [
                                'function_type_title functionTypeTitle',
                                'function_type_id functionTypeId',
                                'sensor_function_id id',
                                'function_id',
                                'sensor_id',
                                'func_title functionTitle',
                                'func_script_name scriptName'
                            ])
                        ->from(['view_sensor_function'])
                        ->where('sensor_id = ' . $sensor_id)
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

    /*
     * actionDeleteSpecificObjectBase - функция удаления сенсора
     * */
    /**
     * Название метода: actionDeleteSpecificObjectBase()
     * @package app\controllers
     *
     * Входные обязательные параметры:
     *
     * Входные необязательные параметры
     *
     * @url http://localhost/specific-sensor/delete-specific-object-base
     * @url http://localhost/specific-sensor/delete-specific-object-base?specific_id=19787&table_name=sensor&kind_object_id=4&object_type_id=12&object_id=47
     *
     * Документация на портале:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 17.01.2019 8:47
     * @since ver0.2
     */
    public function actionDeleteSpecificObjectBase()
    {
        $method_name = "actionDeleteSpecificObjectBase. ";

        $errors = array();
        $status = 1;
        $warnings = array();
        $objectKinds = null;
        $specificObjects = array();

        try {
            $session = Yii::$app->session;                                                                                  //старт сессии
            $session->open();                                                                                               //открыть сессию
            $warnings[] = $method_name . "Начал выполнять метод";
            if (!isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин

                $errors[] = "actionDeleteSpecificObjectBase. Время сессии закончилось. Требуется повторный ввод пароля";
                $this->redirect('/');
                return 1;
            }

            if (!AccessCheck::checkAccess($session['sessionLogin'], 88)) {                                        //если пользователю разрешен доступ к функции
                throw new Exception($method_name . 'Недостаточно прав для совершения данной операции');
            }

            $post = Yii::$app->request->post();                                                                             //получение данных от ajax-запроса
            if (isset($post['specific_id']) and $post['specific_id'] != "" and
                isset($post['kind_object_id']) and $post['kind_object_id'] != "" and
                isset($post['object_type_id']) and $post['object_type_id'] != "" and
                isset($post['object_id']) and $post['object_id'] != "") {                                                      //если все данные переданы
                $warnings[] = $method_name . "Получил входные данные";
            } else {
                throw new Exception($method_name . 'Входные данные не переданы');
            }


            $sensor_id = $post['specific_id'];
            $kind_object_id = $post['kind_object_id'];
            $type_object_id = $post['object_type_id'];
            $object_id = $post['object_id'];

            $sensor_parameter_ids_with_tags = (new Query())
                ->select('sensor_parameter.id as id')
                ->from('sensor_parameter')
                ->innerJoin('sensor_parameter_sensor', 'sensor_parameter_sensor.sensor_parameter_id=sensor_parameter.id')
                ->where(['sensor_id' => $sensor_id])
                ->column();

            $sensorCacheController = new SensorCacheController();
            foreach ($sensor_parameter_ids_with_tags as $sensor_parameter_id) {
                $sensorCacheController->delSenParSenTag('*', $sensor_parameter_id);
            }

            $errors = SpecificSensorController::methodDeleteSensor($sensor_id);
            $specificObjects = parent::buildSpecificObjectArray($kind_object_id, $type_object_id, $object_id);                  //построение списка типовых объектов

            $sensorCacheController->delInSensorMineHash($sensor_id, AMICUM_DEFAULT_MINE);
            $response = $sensorCacheController->delParameterValueHash($sensor_id);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
            } else {
                $errors = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception($method_name . 'метод delParameterValueHash завершлся с ошибкой');
            }


            $service_cache = new ServiceCache();
            $warnings[] = $service_cache->delSensorNetworkId('*', $sensor_id);

            (new CoordinateController())->delGraph($sensor_id);

        } catch (Throwable $ex) {
            $errors[] = $method_name . 'Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array('errors' => $errors, 'specificObjects' => $specificObjects, 'warnings' => $warnings, 'status' => $status);
    }

    /*
     * функция перемещения сенсора
     * */
    public function actionMoveSpecificObjectBase()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();
        $objectKinds = null;
        $newObjectKinds = null;
        $warnings[] = "actionMoveSpecificObjectBase. Выполнение метода начал";
        try {
            $post = Yii::$app->request->post(); //получение данных от ajax-запроса
            /**
             * блок проверки входных параметров
             */
            if (isset($post['specific_id']) && isset($post['kind_object_id']) && isset($post['object_type_id'])
                && isset($post['object_id']) && isset($post['new_object_id'])) {

                $object_id = $post['object_id'];                                                                              //старый айдишник типового объекта
                $kind_object_id = $post['kind_object_id'];                                                                    //вид типового объекта
                $sensor_id = $post['specific_id'];
                $new_object_id = $post['new_object_id'];
                $warnings[] = "actionMoveSpecificObjectBase. Проверка входных параметров прошла успешно";
            } else {//если не переданы
                throw new Exception("actionMoveSpecificObjectBase. Входные данные не переданы");
            }
            /**
             * Сохранение значения типового объекта сенсора в БД
             */
            $sensor = Sensor::findOne(['id' => $sensor_id]);

            $sensor->object_id = $new_object_id;
            if ($sensor->save()) {
                $warnings[] = "actionMoveSpecificObjectBase. Модель успешно сохранена. Сенсор перемещен";
            } else {
                $errors[] = "actionMoveSpecificObjectBase. Ошибка модели: ";
                $errors[] = $sensor->errors;
                throw new Exception("actionMoveSpecificObjectBase. не удалось сохранить модель Sensor");
            }
            /**
             * блок смены в БД значения параметра Типовой объект сенсора
             */
            $warnings[] = "actionMoveSpecificObjectBase. Записываю значение типового объекта сенсора в конкретном параметре этого сенсора";
            //получаю сенсор параметер айди
            $response = SensorBasicController::getSensorParameter($sensor_id, 274, 1);
            if ($response['status'] == 1) {
                $sensor_parameter_id = $response['sensor_parameter_id'];
                $warnings[] = $response['warnings'];
                $warnings[] = "actionMoveSpecificObjectBase. Получил из БД у сенсора $sensor_id текущее значение sensor_parameter_id: $sensor_parameter_id";
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("actionMoveSpecificObjectBase. Не смог получить у сенсора $sensor_id sensor_parameter_id");
            }
            $date_time = \backend\controllers\Assistant::GetDateNow();
            $response = SensorBasicController::addSensorParameterHandbookValue($sensor_parameter_id, $new_object_id, 1, $date_time);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $warnings[] = "actionMoveSpecificObject. формируем массив для вставки в кеш";
                $sensor_parameter_handbook_value[] = (new SensorCacheController())->buildStructureSensorParametersValue($sensor_id, $sensor_parameter_id,
                    274, 1, $date_time, $new_object_id, 1);
                $warnings[] = $sensor_parameter_handbook_value;

                $warnings[] = "actionMoveSpecificObjectBase. Сохранил в БД значение типового объекта сенсора";
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("actionMoveSpecificObjectBase. Не смог сохранить значение $new_object_id типового объекта сенсора $sensor_id в БД sensor_parameter_id: $sensor_parameter_id");
            }
            /**
             * блок смены в кеше значения параметра Типовой объект сенсора
             */
            $response = (new SensorCacheController)->multiSetSensorParameterValueHash($sensor_parameter_handbook_value);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $warnings[] = "actionMoveSpecificObjectBase. Инициализировал кеш параметр 274 сенсора";
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception("actionMoveSpecificObjectBase. Ошибка массового создания параметров сенсора $sensor_id в кеше ");
            }

            /**
             * Блок инициализации сведений о типовом объекте сенсора при его сохранении, для того, что бы потом можно было
             * вывести его правильно в списке и сохранить верно сведения в главный кеш сенсора
             */
            $typical_object = TypicalObject::find()
                ->where(['id' => $new_object_id])
                ->with('objectType')
                ->limit(1)
                ->one();
            if (!$typical_object) {
                throw new Exception("actionMoveSpecificObjectBase. Типовой объект $new_object_id не найден в БД в таблице object");
            }
            $object_title = $typical_object->title;
            $object_type_id = $typical_object->object_type_id;
            $object_type_title = $typical_object->objectType->title;
            $object_kind_id = $typical_object->objectType->kind_object_id;
            $warnings[] = "actionMoveSpecificObjectBase. Подготовил набор базовых параметров для обратного построения справочника";
            $warnings[] = "actionMoveSpecificObjectBase. ИД Типового объекта: $new_object_id";
            $warnings[] = "actionMoveSpecificObjectBase. Название типового объекта: $object_type_title";
            $warnings[] = "actionMoveSpecificObjectBase. Тип типового объекта: $object_type_id";
            $warnings[] = "actionMoveSpecificObjectBase. Вид типового объекта: $object_kind_id";

            /**
             * блок поиска последнего значения шахты сенсора - нужен для инициализации сенсора в конкретной шахте
             */
            $response = SensorMainController::getSensorUniversalLastMine($sensor_id, $object_type_id);
            if ($response['status'] == 1) {
                $mine_id = $response['mine_id'];
                $warnings[] = $response['warnings'];
                $warnings[] = "actionMoveSpecificObjectBase. получил текущую шахту сенсора" . $sensor_id;
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("actionMoveSpecificObjectBase. Не смог найти шахту сенсора: " . $sensor_id);
            }
            /**
             * блок сохранения значения в кеше, если в кеше есть последнее значение шахты сенсора, то значит сенсор инициализирован
             * иначе у него нет сенсора и потому ему не надо менять кеш. а если шахта есть, значит он его инициализирует через перемщение/добавление сенсора
             */
            if ($mine_id) {
                $sensor_to_cache = SensorCacheController::buildStructureSensor($sensor_id, $sensor['title'],
                    $new_object_id, $object_title, $object_type_id, $mine_id);
                $response = SensorMainController::AddMoveSensorMineInitDB($sensor_to_cache);
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $warnings[] = "actionMoveSpecificObjectBase. обновил главный кеш сенсора";
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception("actionMoveSpecificObjectBase. Не смог обновить главный кеш сенсора" . $sensor_id);
                }
            }

            /**
             * блок построения выходных параметров
             */
            $objectKinds = parent::buildSpecificObjectArray($kind_object_id, $object_type_id, $object_id);              //это надо разобраться зачем и почему - помоему это хрень
            $newObjectKinds = parent::buildSpecificObjectArray($kind_object_id, $object_type_id, $new_object_id);   //это надо разобраться зачем и почему - помоему это хрень
        } catch (Throwable $e) {
            $status = 0;
//            $errors[] = "actionMoveSpecificObjectBase. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $warnings[] = "actionMoveSpecificObject. Выполнение метода закончил";
        $result_main = array('status' => $status, 'errors' => $errors, 'specificObjects' => $objectKinds, 'newSpecificObjects' => $newObjectKinds, 'warnings' => $warnings);           //составить результирующий массив как массив полученных массивов
        return json_encode($result_main);

//
//
//        $errors = array();
//        $objectKinds = array();
//        $cacheParValues = Yii::$app->cache;                                                                             //инициализация КЭШа
//        $newObjectKinds = array();
//        $session = Yii::$app->session;                                                                                  //старт сессии
//        $session->open();                                                                                               //открыть сессию
//        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
//            if (AccessCheck::checkAccess($session['sessionLogin'], 89)) {                                        //если пользователю разрешен доступ к функции
//                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
//                if (isset($post['specific_id']) && isset($post['kind_object_id']) && isset($post['object_type_id'])
//                    && isset($post['object_id']) && isset($post['new_object_id'])) {      //если все данные переданы
//
//                    $specific_id = $post['specific_id'];                                                                          //айдишник конкретного объекта
//                    $object_id = $post['object_id'];                                                                              //старый айдишник типового объекта
//                    $new_object_id = $post['new_object_id'];                                                                      //новый айдишник типового объекта
//                    $kind_object_id = $post['kind_object_id'];                                                                    //вид типового объекта
//                    $object_type_id = $post['object_type_id'];                                                                    //Айдишник типа типового объекта
//
//                    $specificObject = Sensor::findOne($specific_id);
//                    $newSpecificId = $new_object_id;
//                    $specificObject->object_id = $newSpecificId;
//
//                    if ($specificObject->save()) {
//                        if ($new_object_id == 47) {
//                            $sensor_id = $specificObject->id;
//                            $sensor_parameter_id = self::actionAddSensorParameter($sensor_id, 459, 1);
//                            self::actionAddSensorParameterHandbookValue($sensor_parameter_id, "Постоянная", 1, date("Y-m-d H:i:s"));
//                        }
//                        $objectKinds = parent::buildSpecificObjectArray($kind_object_id, $object_type_id, $object_id);              //это надо разобраться зачем и почему - помоему это хрень
//                        $newObjectKinds = parent::buildSpecificObjectArray($kind_object_id, $object_type_id, $new_object_id);   //это надо разобраться зачем и почему - помоему это хрень
//                        if (isset($session['userMineId'])) {
//                            //$mine_id = $session['userMineId'];
//                            /*$sql_filter = 'mine_id = ' . $mine_id.' and sensor_id = '.$specific_id;
//                            $sensor_list = (new Query())//запрос напрямую из базы по вьюшке view_personal_areas
//                            ->select(
//                                [
//                                    'mine_id',
//                                    'mine_title',
//                                    'sensor_id',
//                                    'sensor_title',
//                                    'object_title',
//                                    'object_id',
//                                    'object_type_id',
//                                    'image'
//                                ])
//                                ->from(['view_sensor_main_all'])
//                                ->where($sql_filter)
//                                ->one();*/
//                            /*if ($cacheParValues->exists('SensorMine_' . $mine_id))
//                            {
//                                $SensorList = $cacheParValues->get('SensorMine_' . $mine_id);                                             //получить список id всех ветвей шахты
//                                if($SensorList)
//                                {
//                                    $index = array_search($specific_id, array_column($SensorList, 'sensor_id'));
//                                    if ($index === false)                                                               // ищем в массиве выработок из кэша, полученный edge_id. Если edge_id не найден в кэше
//                                    {
//                                        $SensorList[]= $sensor_list;
//                                        $cacheParValues->set('SensorMine_' . $mine_id, $SensorList);
//                                    }
//                                    else                                                                                                    // иначе если выработка в кэше по edge_id не найдена, то
//                                    {
//                                        $SensorList[$index]= $sensor_list;
//                                        $cacheParValues->set('SensorMine_' . $mine_id, $SensorList);
//                                    }
//                                }
//                                else
//                                {
//                                    $temp = CacheGetterController::addSensorMainToCache($mine_id);
//                                }
//                            }
//                            else
//                            {
//                                $temp = CacheGetterController::addSensorMainToCache($mine_id);
//                            }*/
//                            $sensor = StrataJobController::getSensorMineRecordDatabase($specific_id);
//                            if ($sensor) {
//                                Scheduler::enqueue(array(
//                                    "frontend\controllers\CacheGetterController::AddSensorToCache",
//                                    $sensor
//                                ));
//                            }
//                        }
//
//                    } else $errors[] = "Не удалось переместить объект";
//                } else {
//                    $errors[] = "Данные не переданы";
//                }//если не переданы сохранить соответствующую ошибку
//            } else {
//                $errors[] = "Недостаточно прав для совершения данной операции";
//            }
//        } else {
//            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
//            $this->redirect('/');
//        }
//        $result = array('errors' => $errors, 'specificObjects' => $objectKinds, 'newSpecificObjects' => $newObjectKinds);           //составить результирующий массив как массив полученных массивов
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = $result;                                                                            //вернуть AJAX-запросу данные и ошибки
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
        $cacheParValues = Yii::$app->cache;
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 87)) {                                        //если пользователю разрешен доступ к функции
                if (isset($post['title']) && isset($post['specific_id'])
                    && isset($post['kind_object_id']) && isset($post['object_type_id']) && isset($post['object_id'])) {          //проверка на передачу данных

                    $new_object_title = $post['title'];                                                                               //название конкретного объекта - новое
                    $specific_id = $post['specific_id'];                                                                          //айдишник конкретного объекта
                    $object_id = $post['object_id'];                                                                              //старый айдишник типового объекта
                    $kind_object_id = $post['kind_object_id'];                                                                    //вид типового объекта
                    $object_type_id = $post['object_type_id'];                                                                    //Айдишник типа типового объекта

                    $object = Sensor::findOne($specific_id);                                                                     //найти объект по id
                    if ($object) {                                                                                                //если объект существует
                        $existingObject = Sensor::findOne(['title' => $new_object_title]);                                         //найти объект по названию, чтобы не было дублирующих
                        if (!$existingObject) {                                                                                   //если не найден
                            $object->title = $new_object_title;                                                                            //сохранить в найденный по id параметр название
                            if ($object->save()) {                                                                                //если объет сохранился
                                $sensor_parameter_id = $this->actionAddSensorParameter($specific_id, 162, 1); //параметр наименование
                                $sensor_parameter_value = $this->actionAddSensorParameterHandbookValue($sensor_parameter_id, $new_object_title, 1, 1);//сохранение значения параметра
                                if ($sensor_parameter_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 162";
                                $specificObjects = parent::buildSpecificObjectArray($kind_object_id, $object_type_id, $object_id);//обновить массив типовых объектов
                                $response = self::buildsensorParameterArray($specific_id);
                                if ($response['status'] == 1) {
                                    $specificParameters = $response['Items'];
                                } else {
                                    $errors[] = $response['errors'];
                                }
                            } else {
                                $errors[] = "Ошибка сохранения";
                            }                                                               //если не сохранился, сохранить соответствующую ошибку
                        } else {
                            $errors[] = "Объект АС с таким названием уже существует";
                        }                                             //если найден объект по названию, сохранить соответствующую ошибку
                    } else {
                        $errors[] = "Объекта с id " . $specific_id . " не существует";
                    }                                              //если не найден объект по id, сохранить соответствующую ошибку
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
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //вернуть AJAX-запросу данные и ошибки
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: actionSaveSpecificParametersValuesBase()
     * Входные параметры:
     * Функция сохранения значений с вкладки
     * $post['table_name'] - имя таблицы
     * $post['parameter_values_array'] - массив значений
     * $post['specificObjectId'] - id конкретного объекта
     * Кэш добавляется только с помощью очередь!
     * Функция добавляет значений параметров сенсора в кэш используя очередь.
     * Добавление кэш используя очередь сработает, по условии, что со фронта будет получена шахта сенсора,
     *  иначе значений параметров не сохраняются в кэш
     *
     * @url http://192.168.1.5/specific-sensor/save-specific-parameters-values-base?
     * Документация на портале: http://192.168.1.4/products/community/modules/forum/posts.aspx?&t=173&p=1#191
     * @author Якимов М.Н.
     * Created date: on 11.01.2019 9:55
     */
    public function actionSaveSpecificParametersValuesBase()
    {
        $result = array();                                                                                                // промежуточный результирующий массив
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                              // массив предупреждений
        $s_p_h_v_to_db = false;
        $s_p_v_to_db = false;
        $s_p_s_to_db = false;
        $status = 1;
        $response = array();
        $sensor_parameter_sensor_to_caches = array();
        $objectParameters = null;
        $objects = array();
        $warnings[] = 'actionSaveSpecificParametersValuesBase. Начал выполнять метод';
        try {
            /**
             * блок проверки прав пользователя
             */
            $session = Yii::$app->session;
            $session->open();
            $warnings[] = 'actionSaveSpecificParametersValuesBase. Начинаю проверять права пользователя';
            if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
                if (!AccessCheck::checkAccess($session['sessionLogin'], 92)) {
                    throw new Exception('actionSaveSpecificParametersValuesBase. Недостаточно прав для совершения данной операции');
                }
            } else {
                $errors[] = 'actionSaveSpecificParametersValuesBase. Время сессии закончилось. Требуется повторный ввод пароля';
                $this->redirect('/');
                throw new Exception('actionSaveSpecificParametersValuesBase. Время сессии закончилось. Требуется повторный ввод пароля');
            }

            /**
             * блок проверки наличия входных параметров и их распарсивание
             */
            $post = Yii::$app->request->post(); //получение данных от ajax-запроса
            if (isset($post['parameter_values_array'], $post['specific_object_id'])) {
                $parameterValues = json_decode($post['parameter_values_array'], true);                                                         //массив параметров и их значений
                $sensor_id = $post['specific_object_id'];
                $date_time = \backend\controllers\Assistant::GetDateNow();
                $warnings[] = "actionSaveSpecificParametersValuesBase. ПРоверил блок входных параметров. редактируемый сенсор: $sensor_id";
            } else {
                throw new Exception('actionSaveSpecificParametersValuesBase. Входные параметры со страницы фронт энд не переданы');
            }

            /**
             * проверяем наличие исправляемого сенсора в БД и получаем его обязательные параметры из БД и
             * инициализируем модель сенсора для последующего сохранения базовых параметров в исходную таблицу Sensors
             */
            $sensor = Sensor::findOne(['id' => $sensor_id]);
            if (!$sensor) {
                throw new Exception('actionSaveSpecificParametersValuesBase. Редактируемый сенсор отсутствует в БД');
            }
            $sensor_title = $sensor['title'];

            /**
             * проверяем или инициализируем входные параметры перед вставкой
             * делаем проверку на вставку значений параметра. Если не задано, то прописываем -1.
             */
            if ($parameterValues) {
                /**
                 * Перепаковываем параметры сенсора для массовой вставки за раз
                 */
                foreach ($parameterValues as $parameter) {
                    if ($parameter['parameterValue'] == '' || $parameter['parameterValue'] == 'empty') {
                        $parameter_value = '-1';
                    } else {
                        $parameter_value = $parameter['parameterValue'];
                    }
                    $parameter_id = (int)$parameter['parameterId'];
                    $parameter_type_id = $parameter['parameterTypeId'];

                    $sensor_parameter_id = $parameter['specificParameterId'];
                    switch ($parameter['parameterStatus']) {
                        case 'handbook':
                            // готовим к сохранению значение параметров в базовые справочники объекта
                            switch ($parameter_id) {
                                case 162:       // параметр наименование
                                    /**
                                     * проверка имени сенсора при сохранении сенсора на дубли
                                     */
                                    $sensor_title_check = Sensor::findOne('title=$parameter_value');
                                    if (!$sensor_title_check) {
                                        $sensor->title = (string)$parameter_value;
                                        $sensor_title = (string)$parameter_value;
                                    } else {
                                        $errors[] = "actionSaveSpecificParametersValuesBase. Сохраняемое имя сенсора $parameter_value уже существует в БД";
                                    }
                                    break;
                                case 274:       // параметр типовой объект
                                    $sensor->object_id = (int)$parameter_value;
                                    $object_id = (int)$parameter_value;
                                    /**
                                     * Блок инициализации сведений о типовом объекте сенсора при его сохранении, для того, что бы потом можно было
                                     * вывести его правильно в списке и сохранить верно сведения в главный кеш сенсора
                                     */
                                    $typical_object = TypicalObject::find()
                                        ->where(['id' => $object_id])
                                        ->with('objectType')
                                        ->limit(1)
                                        ->one();
                                    if (!$typical_object) {
                                        throw new Exception("actionSaveSpecificParametersValuesBase. Типовой объект $object_id не найден в БД в таблице object");
                                    }
                                    $object_title = $typical_object->title;
                                    $object_type_id = $typical_object->object_type_id;
                                    $object_type_title = $typical_object->objectType->title;
                                    $object_kind_id = $typical_object->objectType->kind_object_id;
                                    $warnings[] = 'actionSaveSpecificParametersValuesBase. Подготовил набор базовых параметров для обратного построения справочника';
                                    $warnings[] = "actionSaveSpecificParametersValuesBase. ИД Типового объекта: $object_id";
                                    $warnings[] = "actionSaveSpecificParametersValuesBase. Название типового объекта: $object_type_title";
                                    $warnings[] = "actionSaveSpecificParametersValuesBase. Тип типового объекта: $object_type_id";
                                    $warnings[] = "actionSaveSpecificParametersValuesBase. Вид типового объекта: $object_kind_id";
                                    break;
                                case 337:       // параметр АСУТП
                                    $sensor->asmtp_id = (int)$parameter_value;
                                    break;
                                case 338:       // параметр тип датчика АСУТП
                                    $sensor->sensor_type_id = (int)$parameter_value;
                                    break;
                                case 346:       // параметр шахтное поле - инициализируется переменная здесь, для определения небходимости инициализации кеша шахта - для статичных сенсоров
                                    $mine_id_static_sensor = (int)$parameter_value;
                                    break;
                                case 88:        //сетевой айди метки позиционирования
                                    /**
                                     * ищем есть ли у кого еще такой сетефой адйи и если есть то выкидываем ошибку
                                     */
                                    $sensor_net_ids = (new Query())
                                        ->select([
                                            'sensor_id',
                                            'network_id',
                                            'sensor_title'
                                        ])
                                        ->from('view_GetLastSensorsNetId')
                                        ->where([
                                            'network_id' => $parameter_value
                                        ])
                                        ->limit(1)
                                        ->one();
                                    $warnings[] = 'actionSaveSpecificParametersValuesBase.  существующие сетевые адреса';
                                    $warnings[] = $sensor_net_ids;
                                    if ($sensor_net_ids and $sensor_net_ids['sensor_id'] != $sensor_id) {
                                        throw new Exception('actionSaveSpecificParametersValuesBase. Данный сетевой адрес network_id (88) уже назначен другому сенсору: ' . $sensor_net_ids['sensor_title']);
                                    }
                                    (new ServiceCache())->setSensorByNetworkId($parameter_value, $sensor_id);     //устанавливаем новое значение сетевого идентификатора на данный сенсор
                                    break;
                            }
                            /**
                             * сохранение самого значения в сенсор параметр Value
                             */
                            $s_p_h_v['sensor_parameter_id'] = (int)$sensor_parameter_id;
                            $s_p_h_v['date_time'] = $date_time;
                            $s_p_h_v['value'] = (string)$parameter_value;
                            $s_p_h_v['status_id'] = 1;
                            $s_p_h_v_to_db[] = $s_p_h_v;
                            //$warnings[] = "actionSaveSpecificParametersValuesBase. Значение параметра " . $parameter['parameterId'] . " сохранено. specificParameterId = " . $parameter['specificParameterId'] . "Идентификатор объекта " . $sensor_id;//сохранить соответствующую ошибку
                            // создаем массив для вставки разовой в кеш
                            $sensor_parameter_value_to_caches[] = SensorCacheController::buildStructureSensorParametersValue(
                                $sensor_id, $sensor_parameter_id,
                                $parameter_id, $parameter_type_id,
                                $date_time, $parameter_value, 1);
                            break;
                        case 'sensor':
                            /**
                             * сохранение самого значения в сенсор параметр валуе
                             */
                            $s_p_s['sensor_parameter_id'] = (int)$sensor_parameter_id;
                            $s_p_s['date_time'] = $date_time;
                            $s_p_s['sensor_parameter_id_source'] = (int)$parameter_value;
                            $s_p_s_to_db[] = $s_p_s;
                            // создаем массив для вставки разовой в кеш
                            $sensor_parameter_sensor_to_caches[] = SensorCacheController::buildStructureTags(
                                $parameter_value, $sensor_id, $sensor_parameter_id, $parameter_id);
                            break;
                        case 'manual':
                        case 'calc':
                        case 'edge':
                        case 'place':
                            switch ($parameter_id) {
                                case 346:       // параметр шахтное поле - инициализируется переменная здесь, для определения небходимости инициализации кеша шахта - для подвижных сенсоров
                                    $mine_id_movment_sensor = (int)$parameter_value;
                                    break;
                            }
                            /**
                             * сохранение самого значения в сенсор параметр валуе
                             */
                            $s_p_v['sensor_parameter_id'] = (int)$sensor_parameter_id;
                            $s_p_v['date_time'] = $date_time;
                            $s_p_v['value'] = (string)$parameter_value;
                            $s_p_v['status_id'] = 1;
                            $s_p_v_to_db[] = $s_p_v;
                            //$warnings[] = "actionSaveSpecificParametersValuesBase. Значение параметра " . $parameter['parameterId'] . " сохранено. specificParameterId = " . $parameter['specificParameterId'] . "Идентификатор объекта " . $sensor_id;//сохранить соответствующую ошибку
                            // создаем массив для вставки разовой в кеш
                            $sensor_parameter_value_to_caches[] = SensorCacheController::buildStructureSensorParametersValue(
                                $sensor_id, $sensor_parameter_id,
                                $parameter_id, $parameter_type_id,
                                $date_time, $parameter_value, 1);
//                            }
                            break;
                        default:
                            $errors[] = 'actionSaveSpecificParametersValuesBase. Неизвестный статус параметра при сохранении. parameterStatus:' . $parameter['parameterStatus'];
                    }
                }
                /**
                 * Сохраняем массово Sensor_parameter_handbook_value
                 */
                if ($s_p_h_v_to_db) {
                    $insert_result = Yii::$app->db->createCommand()->batchInsert('sensor_parameter_handbook_value',
                        ['sensor_parameter_id', 'date_time', 'value', 'status_id'], $s_p_h_v_to_db)->execute();
                    $warnings[] = 'actionSaveSpecificParametersValuesBase. Количество вставленных записей в Sensor_parameter_handbook_value' . $insert_result;
                } else {
                    $warnings[] = 'actionSaveSpecificParametersValuesBase. Значения в Sensor_parameter_handbook_valueS параметров не было сохранено';
                }
                unset($s_p_h_v_to_db);

                /**
                 * Сохраняем массово Sensor_parameter_sensor
                 */
                if ($s_p_s_to_db) {
                    $insert_result = Yii::$app->db->createCommand()->batchInsert('sensor_parameter_sensor',
                        ['sensor_parameter_id', 'date_time', 'sensor_parameter_id_source'], $s_p_s_to_db)->execute();
                    $warnings[] = 'actionSaveSpecificParametersValuesBase. Количество вставленных записей в Sensor_parameter_sensor' . $insert_result;
                } else {
                    $warnings[] = 'actionSaveSpecificParametersValuesBase. Значения в Sensor_parameter_sensorS параметров не было сохранено';
                }
                unset($s_p_s_to_db);

                /**
                 * Сохраняем массово Sensor_parameter_value
                 */
                if ($s_p_v_to_db) {
                    $insert_result = Yii::$app->db->createCommand()->batchInsert('sensor_parameter_value',
                        ['sensor_parameter_id', 'date_time', 'value', 'status_id'], $s_p_v_to_db)->execute();
                    $warnings[] = 'actionSaveSpecificParametersValuesBase. Количество вставленных записей в Sensor_parameter_value' . $insert_result;
                } else {
                    $warnings[] = 'actionSaveSpecificParametersValuesBase. Значения в Sensor_parameter_valueS параметров не было сохранено';
                }
                unset($s_p_v_to_db);

                /**
                 * сохранение базовой модели в БД
                 */
                if (!$sensor->save())                                    //если не сохранилась
                {
                    $errors[] = "actionSaveSpecificParametersValuesBase. Ошибка сохранения модели Sensor: $sensor_id";
                    $errors[] = $sensor->errors;
                } else {
                    $warnings[] = "actionSaveSpecificParametersValuesBase. Модель сенсора $sensor_id в БД успешно сохранена ";
                }
                /**
                 * Важный комментарий!!!! если у сенсора нет параметра 346 - шахтное поле, то для него кеш sensorMine не инициализируется
                 * совсем, это значит, что для того, что бы сенсор попал в кеш для него должен существовать хотя бы пустой параметр 346
                 * соответственно перенос сенсора из шахты в шахту осуществляется только при изменении параметра 346
                 * соответственно инициализация сенсора первичная происходит при записи как раз этого самого параметра 346.
                 * если этого параметра нет, то мы базовые сведения о сенсоре просто сохраняем в БД
                 * Если же у сенсора есть параметр 346, и мы меняем базовые сведения об этом параметре, то мы их применим при записи параметра 346.
                 * важно!!! при записи параметра 346 сведения в данном методе всегда беруться из БД!
                 * в то время как перенос сенсоров при работе служб может осуществляется и без забора данных с БД - путем получения старого значения кеша
                 * sensorMine и записи всего, что там в новый но с учетом измененной шахты
                 *
                 * ЕЩЕ РАЗ:
                 * перенос шахты для этого метода возможен только при инициализации сенсора из БД
                 * перенос шахты из служб сбора данных, где не меняются базовые сведения возможен через старое значение в кеше
                 */

                /**
                 * определяем какую шахту писать - для разных типов сенсоров она разная, для стационарных берем с хэндбука
                 * для подвижных берем с вэлью
                 */
                if ($object_type_id == 22 || $object_type_id == 116 || $object_type_id == 95 || $object_type_id == 96 || $object_type_id == 28) {
                    if (isset($mine_id_static_sensor)) {
                        $mine_id = $mine_id_static_sensor;
                    } else {
                        $mine_id = false;
                    }
                } else {
                    if (isset($mine_id_movment_sensor)) {
                        $mine_id = $mine_id_movment_sensor;
                    } else {
                        $mine_id = false;
                    }
                }

                /**
                 * блок переноса сенсора в новую шахту если таковое требуется
                 * если шахта есть, то делаем перенос или инициализацию, в зависимости от описанного выше
                 */
                if ($mine_id) {
                    $sensor_to_cache = SensorCacheController::buildStructureSensor($sensor_id, $sensor_title, $object_id, $object_title, $object_type_id, $mine_id);
                    $ask_from_method = SensorMainController::AddMoveSensorMineInitDB($sensor_to_cache);
                    if ($ask_from_method['status'] == 1) {
                        $warnings[] = $ask_from_method['warnings'];
                        $warnings[] = 'actionSaveSpecificParametersValuesBase. обновил главный кеш сенсора';
                    } else {
                        $warnings[] = $ask_from_method['warnings'];
                        $errors[] = $ask_from_method['errors'];
                        throw new Exception('actionSaveSpecificParametersValuesBase. Не смог обновить главный кеш сенсора' . $sensor_id);
                    }
                }

                /**
                 * обновление параметров сенсора в кеше
                 */
                $ask_from_method = (new SensorCacheController)->multiSetSensorParameterValueHash($sensor_parameter_value_to_caches);

                if ($ask_from_method['status'] == 1) {
                    $warnings[] = $ask_from_method['warnings'];
                    $warnings[] = 'actionSaveSpecificParametersValuesBase. обновил параметры сенсора в кеше';
                } else {
                    $warnings[] = $ask_from_method['warnings'];
                    $errors[] = $ask_from_method['errors'];
                    throw new Exception('actionSaveSpecificParametersValuesBase. Не смог обновить параметры в кеше сенсора' . $sensor_id);
                }

                /**
                 * блок обновления графа сенсоров
                 */
                if ($object_type_id == 22) {
                    $ask_from_method = (new CoordinateController())->updateSensorGraph($sensor_id, $mine_id, $sensor_title);                               // метод обновления графа для сенсора
                    if ($ask_from_method['status'] == 1) {
                        $warnings[] = $ask_from_method['warnings'];
                        $warnings[] = 'actionSaveSpecificParametersValuesBase. обновил граф сенсора в кеше';
                    } else {
                        $warnings[] = $ask_from_method['warnings'];
                        $errors[] = $ask_from_method['errors'];
                        throw new Exception('actionSaveSpecificParametersValuesBase.ошибка обновления графа сенсора в кеше' . $sensor_id);
                    }
                }

                /**
                 * Блок массовой вставки привязки параметра к параметру сенсора
                 */
                $ask_from_method = (new SensorCacheController)->multiSetSenParSenTag($sensor_parameter_sensor_to_caches);
                if ($ask_from_method['status'] == 1) {
                    $warnings[] = $ask_from_method['warnings'];
                    $warnings[] = 'actionSaveSpecificParametersValuesBase. обновил параметры привязки сенсора в кеше';
                } else {
                    $warnings[] = $ask_from_method['warnings'];
                    $errors[] = $ask_from_method['errors'];
                    throw new Exception('actionSaveSpecificParametersValuesBase. Не смог обновить привязки параметра в кеше сенсора' . $sensor_id);
                }
            } else {
                $warnings[] = 'actionSaveSpecificParametersValuesBase. Массив параметров на сохранение пуст. изменений в БД сделано не было';
            }
            /**
             * блок построения выходных параметров после сохранения параметров
             * 1. Строим инфу по самому сенсору
             * 2. строим инфу для менюшки
             */
            $response = self::buildsensorParameterArray($sensor_id);
            if ($response['status'] == 1) {
                $objectParameters = $response['Items'];
            } else {
                $errors[] = $response['errors'];
            }
            $objects = parent::buildSpecificObjectArray($object_kind_id, $object_type_id, $object_id);

        } catch (Throwable $e) {
            $status = 0;
//            $errors[] = 'actionSaveSpecificParametersValuesBase. Исключение';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'actionSaveSpecificParametersValuesBase. Вышел с метода';
        $result_main = array('response' => $response, 'errors' => $errors, 'warnings' => $warnings, 'objectProps' => $objectParameters, 'objects' => $objects);//составить результирующий массив как массив полученных массивов         //вернуть AJAX-запросу данные и ошибки
        return $this->asJson($result_main);
    }

    //
    private static function camelCase($str)
    {
        $words = explode('_', $str);
        $newStr = '';
        foreach ($words as $key => $word) {
            $newStr .= $key == 0 ? $word : mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
        }
        return $newStr;
    }

    /**
     * buildSensorParameterArray - Метод построения параметров конкретного объекта
     **/
    public static function buildSensorParameterArray($sensor_id)
    {
        $log = new LogAmicumFront("buildSensorParameterArray");
        $result = array();

        try {
            $log->addLog("Начал выполнять метод");

            /**
             * Получение последних справочных параметров сенсора
             */
            $sensor_parameter_values = array();
            $sensor_parameter_values_handbook = (new Query())
                ->select('*')
                ->from('view_GetSensorParameterHandbookWithLastValue')
                ->where(['sensor_id' => $sensor_id])
                ->all();
            if ($sensor_parameter_values_handbook) {
                $sensor_parameter_values = array_merge($sensor_parameter_values, $sensor_parameter_values_handbook);
            }
            $sensor_parameter_values_measure = (new Query())
                ->select('*')
                ->from('view_GetSensorParameterWithLastValue')
                ->where(['sensor_id' => $sensor_id])
                ->all();
            if ($sensor_parameter_values_measure) {
                $sensor_parameter_values = array_merge($sensor_parameter_values, $sensor_parameter_values_measure);
            }

            foreach ($sensor_parameter_values as $spv) {
                $group_sensor_parameter[$spv['kind_parameter_id']][$spv['parameter_id']]['parameter_type_id'][] = $spv;
                $group_sensor_parameter[$spv['kind_parameter_id']][$spv['parameter_id']]['parameter_id'] = $spv['parameter_id'];
                $group_sensor_parameter[$spv['kind_parameter_id']][$spv['parameter_id']]['parameter_title'] = $spv['parameter_title'];
                $group_sensor_parameter[$spv['kind_parameter_id']][$spv['parameter_id']]['units'] = $spv['units'];
                $group_sensor_parameter[$spv['kind_parameter_id']][$spv['parameter_id']]['units_id'] = $spv['units_id'];
            }

            if (isset($group_sensor_parameter)) {
                $sensor_parameter_sensor = (new Query())
                    ->select('*')
                    ->from('view_GetSensorParameterSensorMain')
                    ->where(['sensor_id' => $sensor_id])
                    ->indexBy('sensor_parameter_id')
                    ->all();
            }

            /**
             * Получение видов параметров
             */
            $kinds = KindParameter::find()->all();
            if (!$kinds) {
                throw new Exception('Нет видов параметров');
            }

            /**
             * Генерация структуры для отправки на фронт
             */
            $kind_parameters = array();
            foreach ($kinds as $kind) {
                $kind_parameters['id'] = $kind->id;
                $kind_parameters['title'] = $kind->title;
                $kind_parameters['params'] = array();
                if (isset($group_sensor_parameter[$kind->id])) {
                    $j = 0;
                    foreach ($group_sensor_parameter[$kind->id] as $parameter) {
                        $kind_parameters['params'][$j]['id'] = (int)$parameter['parameter_id'];
                        $kind_parameters['params'][$j]['title'] = $parameter['parameter_title'];
                        $kind_parameters['params'][$j]['units'] = $parameter['units'];
                        $kind_parameters['params'][$j]['units_id'] = $parameter['units_id'];
                        $k = 0;
                        foreach ($parameter['parameter_type_id'] as $parameter_type) {                                  // перебираем конкретный параметр
                            $kind_parameters['params'][$j]['specific'][$k]['id'] = (int)$parameter_type['parameter_type_id'];// id типа параметра
                            $kind_parameters['params'][$j]['specific'][$k]['title'] = $parameter_type['parameter_type_title'];// название параметра
                            $kind_parameters['params'][$j]['specific'][$k]['specificObjectParameterId'] = (int)$parameter_type['sensor_parameter_id'];//id параметра конкретного объекта
                            $kind_parameters['params'][$j]['specific'][$k]['value'] = $parameter_type['value'];

                            switch ($parameter_type['parameter_type_id']) {
                                case 1:
                                    if ($parameter_type['parameter_id'] == 337) {                                       // название АСУТП
                                        if ($asmtpTitle = ASMTP::findOne((int)$parameter_type['value'])) {              // Название места
                                            $kind_parameters['params'][$j]['specific'][$k]['asmtpTitle'] = $asmtpTitle->title;
                                        } else {
                                            $kind_parameters['params'][$j]['specific'][$k]['asmtpTitle'] = '';
                                        }
                                    } else if ($parameter_type['parameter_id'] == 338) {//ТИП сенсора
                                        if ($sensorTypeTitle = SensorType::findOne((int)$parameter_type['value'])) {    // Название места
                                            $kind_parameters['params'][$j]['specific'][$k]['sensorTypeTitle'] = $sensorTypeTitle->title;
                                        } else {
                                            $kind_parameters['params'][$j]['specific'][$k]['sensorTypeTitle'] = '';
                                        }
                                    } else if ($parameter_type['parameter_id'] == 274) {                                // Типовой объект
                                        if ($objectTitle = TypicalObject::findOne($parameter_type['value'])) {
                                            $kind_parameters['params'][$j]['specific'][$k]['objectTitle'] = $objectTitle->title;
                                        }
                                    } else if ($parameter_type['parameter_id'] == 122) {
                                        if ($placeTitle = Place::findOne($parameter_type['value'])) {                   // Название места
                                            $kind_parameters['params'][$j]['specific'][$k]['placeTitle'] = $placeTitle->title;
                                        } else {
                                            $kind_parameters['params'][$j]['specific'][$k]['placeTitle'] = '';
                                        }
                                    } else if ($parameter_type['parameter_id'] == 18 || $parameter_type['parameter_id'] == 523) {                                  /*ParameterEnumController::ALARM_GROUP*/
                                        if ($alarm_group_title = GroupAlarm::findOne($parameter_type['value'])) {       // Название группы оповещения
                                            $kind_parameters['params'][$j]['specific'][$k]['alarmGroupTitle'] = $alarm_group_title->title;
                                        } else {
                                            $kind_parameters['params'][$j]['specific'][$k]['alarmGroupTitle'] = '';
                                        }
                                    }
                                    break;
                                case 2:
                                    if ($parameter_type['value']) {
                                        $kind_parameters['params'][$j]['specific'][$k]['value'] = $parameter_type['value'];
                                    } else {
                                        $kind_parameters['params'][$j]['specific'][$k]['value'] = '-1';
                                    }
                                    break;
                                case 3:
                                    if ($parameter_type['value']) {
                                        $kind_parameters['params'][$j]['specific'][$k]['value'] = $parameter_type['value'];
                                    } else {
                                        $kind_parameters['params'][$j]['specific'][$k]['value'] = '-1';
                                    }
                                    $k++;
                                    $kind_parameters['params'][$j]['specific'][$k]['id'] = 4;                           //id типа параметра
                                    $kind_parameters['params'][$j]['specific'][$k]['title'] = 'Привязка параметра';     //название параметра
                                    $kind_parameters['params'][$j]['specific'][$k]['specificObjectParameterId'] = $parameter_type['sensor_parameter_id'];//id параметра кон
                                    if (isset($sensor_parameter_sensor[$parameter_type['sensor_parameter_id']]) && $sensor_parameter_sensor !== false) {
                                        $kind_parameters['params'][$j]['specific'][$k]['sensor_parameter_id'] = $sensor_parameter_sensor[$parameter_type['sensor_parameter_id']]['sensor_parameter_id_source'];
                                    } else {
                                        $kind_parameters['params'][$j]['specific'][$k]['sensor_parameter_id'] = -1;
                                    }
                                    break;
                            }
                            $k++;
                        }
                        $j++;
                    }
                    ArrayHelper::multisort($kind_parameters['params'], 'title', SORT_ASC);
                }
                $result[] = $kind_parameters;
            }

            ArrayHelper::multisort($result, 'title', SORT_ASC);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    //сохраняет базовые Текстовые параметры в базовый справочник
    public function actionUpdateSensorValuesStingBase($sensor_id, $name_field, $value)
    {
        $sensor_update = Sensor::findOne(['id' => $sensor_id]);
        $sensor_update->$name_field = (string)$value;
        if (!$sensor_update->save()) return -1;
        else return 1;
    }

    //сохраняет базовые Числовые параметры в базовый справочник
    public function actionUpdateSensorValuesIntBase($sensor_id, $name_field, $value)
    {
        $sensor_update = Sensor::findOne(['id' => $sensor_id]);
        $sensor_update->$name_field = (int)$value;
        if (!$sensor_update->save()) return -1;
        else return 1;
    }

    public function actionAddPictureToSensors()
    {
//        $post = Yii::$app->request->post();
        $errors = array();
        $response = array();
        $sensors = Sensor::find()->all();
        $img_path = "";
        foreach ($sensors as $sensor) {
            $sensor_parameter = SensorParameter::findOne(['sensor_id' => $sensor->id, 'parameter_id' => 168, 'parameter_type_id' => 1]);
            $img_path = self::GetPictureForObject($sensor->object_id);
            if ($sensor_parameter) {
                $sensor_parameter_value = self::actionAddSensorParameterHandbookValue($sensor_parameter->id, $img_path, 1, date("Y-m-d H:i:s"));
                if ($sensor_parameter_value == -1) {
                    $errors[] = "не удалось сохранить значение 2д модели узла " . $sensor->id;
                } else {
                    $response[] = "запись прошла успешно для сенсора = " . $sensor->id;
                }
            } else {
                $new_sensor_parameter = $this->actionAddSensorParameter($sensor->id, 168, 1);
                if ($new_sensor_parameter === -1) {
                    $errors[] = "не удалось создать новый параметр sensor_parameter";
                } else {
                    $response[] = "создали связку sensor_parameter для узла " . $sensor->id;
                    $new_sensor_parameter_value = self::actionAddSensorParameterHandbookValue($new_sensor_parameter, $img_path, 1, date("Y-m-d H:i:s"));
                    if ($new_sensor_parameter_value == -1) {
                        $errors[] = "не удалось сохранить значение 2д модели узла " . $sensor->id;
                    } else {
                        $response[] = "запись прошла успешно для сенсора = " . $sensor->id;
                    }
                }
            }
        }
        $result = array('errors' => $errors, 'response' => $response);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    public function actionAddConditionParameter()
    {
        $errors = array();
        $logs = array();
        $sensors = (new Query())
            ->select('id')
            ->from('sensor')
            ->where('object_id = 49')
            ->all();
//        var_dump($sensors);
//        return;
        foreach ($sensors as $sensor) {
//            $logs[] = "в цикле по бпд";
            $sensor_parameter = SensorParameter::findOne(['sensor_id' => $sensor['id'], 'parameter_type_id' => 2, 'parameter_id' => 164]);
            if ($sensor_parameter) {
                $logs[] = "есть измеряемый параметр состояния";
                SensorParameterValue::deleteAll(['sensor_parameter_id' => $sensor_parameter->id]);
                //$logs[] = "удалили измеряемые значения ".$sensor_parameter->id;
                if (!$sensor_parameter->delete()) {
                    $errors[] = "ошибка удаления sensor_parameter " . $sensor_parameter->id;
                } else {
                    $logs[] = "измеряемый параметр удален " . $sensor_parameter->id;
                }
            }
            $old_sp = SensorParameter::findOne(['sensor_id' => $sensor['id'], 'parameter_id' => 164, 'parameter_type_id' => 3]);
            if ($old_sp) {

                $logs[] = "такая привязка уже есть " . $old_sp->id;

            } else {
                $new_sp = self::actionAddSensorParameter($sensor['id'], 164, 3);
                if ($new_sp == -1) {
                    $errors[] = "ошибка создания справочного параметра ";
                } else {
                    $logs[] = "параметр добавлен " . $new_sp;
                }
            }

        }
        $logs[] = "выход из цикла";
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array('errors' => $errors, 'logs' => $logs);
    }

    public function actionSetToleranceParameter()
    {
        $errors = array();
        $logs = array();
        $sensors = Sensor::find()->where(['object_id' => 49])->orWhere(['object_id' => 156])->all();
        foreach ($sensors as $sensor) {
            $sensor_parameter = SensorParameter::findOne(['sensor_id' => $sensor->id, 'parameter_type_id' => 2, 'parameter_id' => 437]);
            if ($sensor_parameter) {
                SensorParameterValue::deleteAll(['sensor_parameter_id' => $sensor_parameter->id]);
                $logs[] = "удалили измеряемые значения";
                if (!$sensor_parameter->delete()) {
                    $errors[] = "ошибка удаления sensor_parameter " . $sensor_parameter->id;
                } else {
                    $logs[] = "измеряемый параметр удален";
                }
            }
            $old_sp = SensorParameter::findOne(['sensor_id' => $sensor->id, 'parameter_id' => 437, 'parameter_type_id' => 1]);
            if ($old_sp) {
                $old_hv = self::actionAddSensorParameterHandbookValue($old_sp->id, '1', 1, date('Y-m-d H:i:s'));
                if ($old_hv == -1) {
                    $errors[] = "ошибка добавления справочного значения";
                }
            } else {
                $new_sp = self::actionAddSensorParameter($sensor->id, 437, 1);
                if ($new_sp == -1) {
                    $errors[] = "ошибка создания справочного параметра";
                } else {
                    $sp_hv = self::actionAddSensorParameterHandbookValue($new_sp, '1', 1, date('Y-m-d H:i:s'));
                    if ($sp_hv == -1) {
                        $errors[] = "ошибка добавления нового справочного значения";
                    }
                }
            }

        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array('errors' => $errors, 'logs' => $logs);
    }

    /**
     * Метод для поиска сенсоров во вкладке "Автоматизированная система" на странице конкретных объектов
     * TODO: оптимизировать
     */
    public function actionSearchSensors()
    {
        $post = Yii::$app->request->post();
        $errors = array();
        $specific_object_array = array();
        $typical_objects = array();
        $object_types = array();
        $empty_title = false;
        $count_eq_title = 0;
        $search_query = "";
        $count_f_title = 0;
        if (isset($post['title']) and $post['title'] != "") {
            $search_query = strval($post['title']);

            $specific_object_array = Sensor::find()
                ->select("id,title,object_id, ('sensor') as table_name")
                ->filterWhere(['like', 'title', $search_query])
                ->orderBy(['object_id' => SORT_ASC])
                ->asArray()
                ->all();
            $count_eq_title = count($specific_object_array);
            $count_f_title = count($specific_object_array);
            $j = -1;

            foreach ($specific_object_array as $specific_object) {
                $object = TypicalObject::findOne(['id' => Sensor::findOne(['id' => $specific_object['id']])->object_id]);
                if ($j == -1 or $typical_objects[$j]['id'] != $object->id) {
                    $j++;
                    $typical_objects[$j]['id'] = $object->id;
                    $typical_objects[$j]['title'] = $object->title;
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

            }
            ArrayHelper::multisort($typical_objects, 'title', SORT_ASC);
//            print_r($typical_objects);
            $k = -1;
            foreach ($typical_objects as $object) {

                $object_type = ObjectType::findOne(['id' => TypicalObject::findOne(['id' => $object['id']])->object_type_id]);

                if ($k == -1 or $object_types[$k]['id'] != $object_type->id) {
                    $k++;
                    $object_types[$k]['id'] = $object_type->id;
                    $object_types[$k]['title'] = $object_type->title;
                    $object_types[$k]['objects'] = array();
                }
                $object_types[$k]['objects'][] = $object;
            }

            ArrayHelper::multisort($object_types, 'title', SORT_ASC);
            $k = -1;
            $temp = array();
            $j = 0;
            foreach ($object_types as $object) {
                $object_id = $object['id'];
                if ($k == -1 or $temp[$k]['id'] != $object_id) {
                    $k++;
                    $temp[$k]['id'] = $object['id'];
                    $temp[$k]['title'] = $object['title'];
                    $temp[$k]['objects'] = array();
                    $j = -1;
                }
                foreach ($object['objects'] as $item) {
                    $j++;
                    $temp[$k]['objects'][$j]['id'] = $item['id'];
                    $temp[$k]['objects'][$j]['title'] = $item['title'];
                    $temp[$k]['objects'][$j]['specific_objects'] = $item['specific_objects'];

                }


            }

            $object_types = $temp;
//            print_r($object_types);
        } else if ($post['title'] == "") {
            $empty_title = "true";
        } else {
            $errors[] = "не передан параметр поиска";
        }
//        var_dump($object_types);
//        return;
        $result = array('errors' => $errors, 'object_types' => $object_types, 'empty_title' => $empty_title, 'count' => $count_eq_title, 'final' => $count_f_title);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Метод добавления значения параметров датчика в справочник
     */
    public static function AddSensorParameterHandbookValue($object_table_name, $object_parameter_id, $date_time, $value, $status_id)
    {
        $id = ObjectFunctions::AddObjectParameterHandbookValue($object_table_name, $object_parameter_id, $date_time, $value, $status_id);
        return $id;
    }

    /**
     * Метод добавления значения параметров датчика
     */
    public static function AddSensorParameterValue($object_table_name, $object_parameter_id, $date_time, $value, $status_id)
    {
        $id = ObjectFunctions::AddObjectParameterValue($object_table_name, $object_parameter_id, $date_time, $value, $status_id);
        return $id;
    }


    /*
     * Метод возвращает путь к картинке для конкретного объекта
     */
    public static function GetPictureForObject($object_id)
    {
        $img_path = "";
        switch ($object_id) {
            case 45:
                $img_path = "/img/2d_models/specific_objects/sensors/NodeA.png";
                break;
            case 46:
                $img_path = "/img/2d_models/specific_objects/sensors/NodeC.png";
                break;
            case 49:
                $img_path = "/img/2d_models/specific_objects/sensors/bpd3.png";
                break;
            case 90:
                $img_path = "/img/2d_models/specific_objects/sensors/NodeA_XP.png";
                break;
            case 91:
                $img_path = "/img/2d_models/specific_objects/sensors/NodeC_XP.png";
                break;
            case 156:
                $img_path = "/img/2d_models/specific_objects/sensors/mikrotik.png";
                break;
            default:
                $img_path = "-1";
                break;
        }
        return $img_path;
    }


    /**
     * Название метода: GetSensorsParametersLastValues()
     * @param $sensor_condition - условие поиска сенсоров. Можно найти конкретного сенсора по условии sensor.id = 310.
     * Примеры использования переменной:
     *      sensor.id = 310 and object_id = 49 OR object_type_id = 22
     * В этой переменной можно писать любые фильтры которых можно сделать по табличке sensors и object
     * @param $parameter_condition - условия параметра поиска. Если указать -1, то возвращает все параметры.
     *      Если есть конкретные параметры, то нужно указать в виде: виде "1-122, 2-83, 3-164, 1-105"
     * @param $date_time - дата/время начало
     * @return array
     * Created by: Одилов О.У. on 14.12.2018 14:35
     */
    public static function GetSensorsParametersLastValues($sensor_condition, $parameter_condition, $date_time)
    {
        $parameters = Assistant::AddConditionForParameters($parameter_condition);
        $parameter_condition = $parameters['parameters'];
        $parameter_type_table = $parameters['parameter_type_table'];
        $sensors = array();
        /***** ПОЛУЧАЕМ ДАННЫЕ ВСЕХ ДАТЧИКОВ ДО УКАЗАННОЙ ДАТЫ *****/
        if ($sensor_condition == "-1")                                                                                            // если не указанан конкретный сенсор, то возвращаем всех сенсоров
        {
            $sensors = Assistant::CallProcedure("GetSensorsParametersLastValuesOptimized('-1', '$parameter_condition', '$date_time', '$parameter_type_table')");
        } /***** ПОЛУЧАЕМ ДАННЫЕ ТОЛЬКО ОДНОГО ДАТЧИКА ДО УКАЗАННОЙ ДАТЫ *****/
        else                                                                                                            // если указанан конкретный сенсор, то возвращаем только одного сенсора
        {
            $sensors = Assistant::CallProcedure("GetSensorsParametersLastValuesOptimized('$sensor_condition', '$parameter_condition', '$date_time', '$parameter_type_table')");
        }
        return $sensors;
    }

    /**
     * Название метода: GetSensorsParametersValuesPeriod()
     * @param $sensor_condition - условие поиска сенсоров. Можно найти конкретного сенсора по условии sensor.id = 310.
     * Примеры использования переменной:
     *      sensor.id = 310 and object_id = 49 OR object_type_id = 22
     * В этой переменной можно писать любые фильтры которых можно сделать по табличке sensors и object
     * @param $parameter_condition - условия параметра поиска. Если указать -1, то возвращает все параметры.
     *      Если есть конкретные параметры, то нужно указать в виде: виде "1-122, 2-83, 3-164, 1-105"
     * @param $date_time_start - дата/время начало
     * @param $date_time_end - конец даты и времени
     * @return array
     * Created by: Одилов О.У. on 14.12.2018 15:56
     */
    public static function GetSensorsParametersValuesPeriod($sensor_condition, $parameter_condition, $date_time_start, $date_time_end)
    {
        $sensors = array();
        $parameters = Assistant::AddConditionForParameters($parameter_condition);
        $parameter_condition = $parameters['parameters'];
        $parameter_type_table = $parameters['parameter_type_table'];
        /***** ПОЛУЧАЕМ ДАННЫЕ ВСЕХ ДАТЧИКОВ ДО УКАЗАННОЙ ДАТЫ *****/
        if ($sensor_condition == "-1")                                                                                            // если не указанан конкретный сенсор, то возвращаем всех сенсоров
        {
            $sensors = Assistant::CallProcedure("GetSensorsParametersValuesOptimizedPeriod('-1', '$parameter_condition', '$date_time_start', '$date_time_end' ,'$parameter_type_table')");
        } /***** ПОЛУЧАЕМ ДАННЫЕ ТОЛЬКО ОДНОГО ДАТЧИКА ДО УКАЗАННОЙ ДАТЫ *****/
        else                                                                                                            // если указанан конкретный сенсор, то возвращаем только одного сенсора
        {
            $sensors = Assistant::CallProcedure("GetSensorsParametersValuesOptimizedPeriod('$sensor_condition', '$parameter_condition', '$date_time_start',  '$date_time_end' ,'$parameter_type_table')");
        }
        return $sensors;
    }

    // actionInitSensorMain - метод инициализации кеша сенсоров по всей шахте
    // входные параметры
    //      mine_id - ключ шахты
    //  выходные параметры:
    //      стандартный набор
    // пример использования: 127.0.0.1/positioningsystem/specific-sensor/init-sensor-main?mine_id=290
    // разработал Якимов М.Н.
    // дата создания 09.08.2019
    public function actionInitSensorMain()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = "actionInitSensorMain. Начало выполнения метода";
        try {
            $post = Assistant::GetServerMethod();
            $mine_id = $post['mine_id'];
            $sensor_cache_controller = new SensorCacheController();
            $sensor_cache_controller->amicum_flushall();
            $response = $sensor_cache_controller->runInitHash($mine_id);
            $errors[] = $response['errors'];
            $warnings[] = $response['warnings'];
            $status *= $response['status'];
            unset($response);
        } catch (Throwable $e) {
            $status = 0;
//            $errors[] = 'actionInitSensorMain. Исключение';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "actionInitSensorMain. Закончил выполнение метода";

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        unset($result);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result_main;

    }

    public function actionGetSensorOpcTags()
    {
        $response = SensorMainController::getListOpcParameters();
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $response['Items'];
    }

}
