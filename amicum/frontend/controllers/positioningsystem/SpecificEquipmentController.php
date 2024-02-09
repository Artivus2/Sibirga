<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers\positioningsystem;
//ob_start();

use backend\controllers\cachemanagers\EquipmentCacheController;
use backend\controllers\EquipmentBasicController;
use backend\controllers\EquipmentMainController;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\AccessCheck;
use frontend\models\Asmtp;
use frontend\models\Attachment;
use frontend\models\Equipment;
use frontend\models\EquipmentAttachment;
use frontend\models\EquipmentFunction;
use frontend\models\EquipmentParameter;
use frontend\models\EquipmentParameterHandbookValue;
use frontend\models\EquipmentParameterSensor;
use frontend\models\EquipmentParameterValue;
use frontend\models\EquipmentUnity;
use frontend\models\GroupAlarm;
use frontend\models\KindObject;
use frontend\models\KindParameter;
use frontend\models\Main;
use frontend\models\ObjectType;
use frontend\models\Place;
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

class SpecificEquipmentController extends SpecificObjectController
{

    // actionInitEquipmentMain                  - Метод инициализации кеша оборудования по всей шахте
    // AddEquipmentParameterValue               - Сохранение значения конкретного параметра оборудования
    // GetListConveyorEquipments                - Метод получения справочника конвейеров для построения модального окна в Unity
    // actionAddSpecificObject                  - Метод сохранения конкретного оборудования
    // actionAddEquipment                       - Метод создания конкретного объекта в его базовой таблице
    // actionCopyTypicalParametersToSpecific    - Метод копирует параметры типового параметра в параметры конкретного объекта - нужен для создания конкретного объекта по шаблону типового объекта
    // actionAddEquipmentParameter              - Создание параметра конкретного оборудования
    // actionAddEquipmentParameterHandbookValue - Сохранение справочного значения конкретного параметра оборудования
    // AddEquipmentParameterValue               - Сохранение значения конкретного параметра оборудования
    // actionAddEquipmentParameterSensor        - Сохранение привязки сенсора к конкретному параметру оборудования
    // actionAddEquipmentFunction               - Сохранение функций оборудования
    // actionLoadCommonInfo                     - Функция отправки данных для заполнения вкладки общие сведения
    // buildCommonInfoArray                     - Функция формирования данных для заполнения вкладки общие сведения
    // actionSearchEquipments                   - Метод поиска оборудования в справочнике конкретных объектов
    // actionEditSpecificObject                 - Функция редактирования конкретных объектов
    // actionMoveSpecificObject                 - Метод перемещения оборудования
    // actionGetSensors                         - Метод получения списка сенсоров привязанных к оборудованию
    // actionSetLampToEquipment                 - Метод привязки метки оборудования к оборудованию
    // actionDeleteSpecificObject               - Метод удаления оборудования
    // actionSaveSpecificParametersValues       - Функция сохранения значений с вкладки справочника конкретных объектов
    // actionSaveCommonInfo                     - Метод сохранения фотографии оборудования
    // actionSaveCommonInfoValues               - Метод сохранения общих сведений
    // actionUploadPicture                      - Метод загрузки изображения оборудования
    // actionUpdateEquipmentValuesString        - Сохраняет базовые Текстовые параметры в базовый справочник
    // actionUpdateEquipmentValuesInt           - Сохраняет базовые Числовые параметры в базовый справочник
    // actionAddEquipmentParameterOne           - Добавление нового параметра оборудования из страницы frontend
    // actionDeleteSpecificParameter            - Метод удаления параметров для оборудования
    // actionAddEquipmentFunctionFront          - Функция добавления функции оборудования с post с фронта
    // actionDeleteEquipmentFunction            - Функция удаления функции оборудования с post с фронта
    // buildEquipmentParameterArray             - Функция построения параметров конкретного объекта
    // AddMain                                  - Метод добавления ключа в таблицу Main
    // SaveEquipment                            - Метод сохранения сведений об оборудовании с 3D схемы шахты
    // GetEquipmentWithUnity                    - Метод получение сведений об оборудовании с 3D схемы шахты

    public function actionIndex(): string
    {
        return $this->render('index');
    }

    /**
     * GetListConveyorEquipments - Метод получения справочника конвейеров для построения модального окна в Unity
     * @param null $data_post
     * @example 127.0.0.1/read-manager-amicum?controller=positioningsystem\SpecificEquipment&method=GetListConveyorEquipments&subscribe=&data={}
     */
    public static function GetListConveyorEquipments($data_post = null): array
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("GetListConveyorEquipments");
        try {
            $cache = Yii::$app->cache;
            $key = "GetListConveyorEquipments";
            $keyHash = "GetListConveyorEquipmentsHash";
            $equipments = $cache->get($key);
            if (!$equipments) {
                $log->addLog("Кеша не было, получаю данные из БД");

                $equipments = (new Query())
                    ->select("
                equipment.id as id,
                equipment.title as title,
                equipment.parent_equipment_id as parent_equipment_id,
                equipment.inventory_number as inventory_number,
                object.id as object_id,
                object.title as object_title,
                view_equipment_parameter_handbook_value_maxDate_main.value as img_src
                ")
                    ->from('equipment')
                    ->innerJoin('object', 'object.id=equipment.object_id')
                    ->leftJoin('equipment_parameter', 'equipment.id=equipment_parameter.equipment_id')
                    ->leftJoin('view_equipment_parameter_handbook_value_maxDate_main', 'equipment_parameter.id=view_equipment_parameter_handbook_value_maxDate_main.equipment_parameter_id')
                    ->where(['object_type_id' => 93, 'parameter_id' => 168, 'parameter_type_id' => 1])
                    ->indexBy('id')
                    ->all();
                $hash = md5(json_encode($equipments));
                $cache->set($keyHash, $hash, 60 * 60 * 24);
                $cache->set($key, $equipments, 60 * 60 * 24);   // 60 * 60 * 24 = сутки
            } else {
                $log->addLog("Кеш был");
                $hash = $cache->get($keyHash);
            }
            $result['handbook'] = $equipments;
            $result['hash'] = $hash;


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionAddSpecificObject - Метод сохранения конкретного оборудования
     */
    public function actionAddSpecificObject()
    {
        $errors = array();                                                                                              //создаем массив для ошибок
        $check_title = 0;                                                                                               //флаг проверки на существование такого названия в базе
        $check_input_parameters = 1;                                                                                    //флаг проверки входных параметров
        $warnings = array();
        $main_specific_id = null;
        $specific_title = null;
        $parent_equipment_id = null;                                                                                    // ключ вышестоящего оборудования (родительского)
        $object_id = null;
        $specific_array = null;
        $kind_id = null;
        $object_type_id = null;
        $session = Yii::$app->session;                                                                                  // старт сессии
        $session->open();                                                                                               // открыть сессию
        if (isset($session['sessionLogin'])) {                                                                          // если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 86)) {                                       // если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();                                                                     // получение данных от ajax-запроса
                if (
                    isset($post['title']) and $post['title'] != ""
                ) {                                                                                                     // проверка на наличие входных данных, а именно на наличие такого названия
                    $specific_title = $post['title'];                                                                   // название нового конкретного объекта, который создаем
                    $sql_filter = 'title="' . $specific_title . '"';
                    if (isset($post['parent_equipment_id'])) {
                        $parent_equipment_id = $post['parent_equipment_id'];                                            // ключ родительского оборудования
                        if ($parent_equipment_id == "") {
                            $parent_equipment_id = null;
                        }
                    }

                    $equipments = (new Query())//запрос напрямую из базы по таблице Equipment
                    ->select(
                        [
                            'id',
                            'title'
                        ])
                        ->from(['equipment'])
                        ->where($sql_filter)
                        ->one();
                    if ($equipments) {
                        $errors[] = "Объект с именем " . $specific_title . " уже существует";
                        $check_title = -1;
                    } else $check_title = 1;                                                                            // название не существует в базе, можно добавлять объект
                } else {
                    $errors[] = "Не все входные данные есть в методе POST";
                    $check_input_parameters = -1;
                }

                if (isset($post['object_id']) and $post['object_id'] != "") {                                           // проверка на наличие входных данных, а именно на наличие типового объекта, который копируется
                    $object_id = $post['object_id'];                                                                    // id типового объекта
                    $sql_filter = 'object_id=' . $object_id;
                    $typical_objects = (new Query())
                        ->select(
                            [
                                'object_id',                                                                            // id типового объекта
                                'object_type_id',                                                                       // id типа типового объекта
                                'kind_object_id'                                                                        // id вида типового объекта
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
                    $warnings[] = "actionAddSpecificObjectBase. Начинаю сохранять в базу";
                    $new_errors = EquipmentBasicController::addEquipment($specific_title, $object_id, $parent_equipment_id);
                    if ($new_errors['status'] == 1) {
                        $warnings[] = "actionAddSpecificObjectBase. Сохранил в БД успешно, Инициализирую кеш параметров оборудования";
                        $main_specific_id = $new_errors['equipment_id'];
                        $equipment_id = $new_errors['equipment_id'];
                        $warnings[] = $new_errors['warnings'];
                        $equipment_parameter_handbook_value = $new_errors['equipment_parameter_handbook_value'];
                        $warnings[] = $equipment_parameter_handbook_value;
                        $response = (new EquipmentCacheController)->multiSetEquipmentParameterValue($equipment_id, $equipment_parameter_handbook_value);
                        $warnings[] = $response['warnings'];

                        if ($response['status'] == 1) {
                            $warnings[] = $response['warnings'];
                            $warnings[] = "actionAddSpecificObjectBase. Инициализировал кеш параметров оборудования.Инициализирую главный кеш оборудования";
                            $response = (new EquipmentCacheController)->initEquipmentMain(-1, $equipment_id);
                            $warnings[] = $response['warnings'];
                            if ($response['status'] == 1) {
                                $warnings[] = "actionAddSpecificObjectBase. Успешно закончил инициализацию главного кеша оборудования";
                                unset($new_errors);
                                $new_errors['errors'] = array();
                                unset($errors);
                                $errors = array();
                            } else {
                                $errors[] = $response['errors'];
                            }
                        }
                    }
                    foreach ($new_errors['errors'] as $err) {
                        $errors[] = $err;
                    }
                    $specific_array = parent::buildSpecificObjectArray($kind_id, $object_type_id, $object_id);//вызываем функция построения массива конкретных объектов нужного типа
                } else {
                    $errors[] = "Объект с именем " . $specific_title . " уже существует";
                }
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

    // actionAddEquipment - метод создания конкретного объекта в его базовой таблице
    public function actionAddEquipment($equipment_title, $object_id): int
    {
        $equipment_id = parent::actionAddEntryMain('equipment');                                          //создаем запись в таблице Main
        if (!is_int($equipment_id)) return -1;
        else {
            $newSpecificObject = new Equipment();//сохраняем все данные в нужной модели
            $newSpecificObject->id = $equipment_id;
            $newSpecificObject->title = $equipment_title;
            $newSpecificObject->object_id = $object_id;
            if (!$newSpecificObject->save()) return -1;                                                                      //проверка на сохранение нового объекта
            else return $newSpecificObject->id;
        }
    }

    // actionCopyTypicalParametersToSpecific - метод копирует параметры типового параметра в параметры конкретного объекта - нужен для создания конкретного объекта по шаблону типового объекта
    private function actionCopyTypicalParametersToSpecific($typical_object_id, $specific_object_id): int
    {
        $flag_done = 1;                                                                                                   //флаг успешного выполнения метода
        //копирование параметров справочных
        if ($type_object_parameters = TypeObjectParameter::find()->where(['object_id' => $typical_object_id, 'parameter_type_id' => 1])->all())                           //Находим все параметры типового объекта
        {
            foreach ($type_object_parameters as $type_object_parameter) {
                //создаем новый параметр у конкретного объекта
                $equipment_parameter_id = $this->actionAddEquipmentParameter($specific_object_id, $type_object_parameter->parameter_id, $type_object_parameter->parameter_type_id);

                //ищем последние справочное значения параметра типового объекта и копируем их в справочное значение конкретного объекта
                if ($equipment_parameter_id
                    and $typical_object_parameter_handbook_values = TypeObjectParameterHandbookValue::find()
                        ->where(['type_object_parameter_id' => $type_object_parameter->id])
                        ->orderBy(['date_time' => SORT_DESC])
                        ->one())
                    $flag_done = $this->actionAddEquipmentParameterHandbookValue($equipment_parameter_id, $typical_object_parameter_handbook_values->value, $typical_object_parameter_handbook_values->status_id);
            }
        }
        if ($type_object_parameters = TypeObjectParameter::find()->where(['object_id' => $typical_object_id, 'parameter_type_id' => 2])->all())                           //Находим все параметры типового объекта
        {
            foreach ($type_object_parameters as $type_object_parameter) {
                //создаем новый параметр у конкретного объекта
                $equipment_parameter_id = $this->actionAddEquipmentParameter($specific_object_id, $type_object_parameter->parameter_id, $type_object_parameter->parameter_type_id);

                //ищем последние справочное значения параметра типового объекта и копируем их в справочное значение конкретного объекта
                if ($equipment_parameter_id and
                    $typical_object_parameter_sensor = TypeObjectParameterValue::find()
                        ->where(['type_object_parameter_id' => $type_object_parameter->id])
                        ->orderBy(['date_time' => SORT_DESC])
                        ->one()) {
                    $flag_done = $this->actionAddEquipmentParameterSensor($equipment_parameter_id, $typical_object_parameter_sensor->value);
                }
            }
        }


        //копирование функций типового объекта
        //находим функции типового объекта
        if ($type_object_functions = TypeObjectFunction::find()->where(['object_id' => $typical_object_id])->all()) {
            foreach ($type_object_functions as $type_object_function) {
                $equipment_function_id = $this->actionAddEquipmentFunction($specific_object_id, $type_object_function->func_id);
                if ($equipment_function_id == -1) $flag_done = -1;
            }
        }
        return $flag_done;
    }

    // actionAddEquipmentParameter - создание параметра конкретного оборудования
    // делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем id
    public function actionAddEquipmentParameter($equipment_id, $parameter_id, $parameter_type_id)
    {
        if ($equipment_parameter = EquipmentParameter::find()->where(['equipment_id' => $equipment_id, 'parameter_id' => $parameter_id, 'parameter_type_id' => $parameter_type_id])->one()) {
            return $equipment_parameter->id;
        } else {
            $equipment_parameter_new = new EquipmentParameter();
            $equipment_parameter_new->equipment_id = $equipment_id;                                                     // id оборудования
            $equipment_parameter_new->parameter_id = $parameter_id;                                                     // id параметра
            $equipment_parameter_new->parameter_type_id = $parameter_type_id;                                           // id типа параметра

            if ($equipment_parameter_new->save()) {
                $equipment_parameter_new->refresh();
                return $equipment_parameter_new->id;
            } else return (-1);                                                                                         // "Ошибка сохранения значения параметра оборудования" . $equipment_id->id;
        }
    }

    // actionAddEquipmentParameterHandbookValue - сохранение справочного значения конкретного параметра оборудования
    public function actionAddEquipmentParameterHandbookValue($equipment_parameter_id, $value, $status_id = 1, $date_time = 1): int
    {
        $equipment_parameter_handbook_value = new EquipmentParameterHandbookValue();
        $equipment_parameter_handbook_value->equipment_parameter_id = $equipment_parameter_id;
        if ($date_time == 1) $equipment_parameter_handbook_value->date_time = \backend\controllers\Assistant::GetDateNow();
        else $equipment_parameter_handbook_value->date_time = $date_time;
        $equipment_parameter_handbook_value->value = strval($value);
        $equipment_parameter_handbook_value->status_id = $status_id;

        if (!$equipment_parameter_handbook_value->save()) {
            return (-1);
        } else return 1;
    }

    // AddEquipmentParameterValue - сохранение значения конкретного параметра оборудования
    public static function AddEquipmentParameterValue($equipment_parameter_id, $value, $status_id = 1, $date_time = 1): array
    {
        // Стартовая отладочная информация
        $method_name = 'AddEquipmentParameterValue';                                                                    // название метода

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $debug = array();                                                                                               // блок отладочной информации
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        try {
            $equipment_parameter_value = new EquipmentParameterValue();
            $equipment_parameter_value->equipment_parameter_id = $equipment_parameter_id;
            if ($date_time == 1) {
                $equipment_parameter_value->date_time = \backend\controllers\Assistant::GetDateNow();
            } else {
                $equipment_parameter_value->date_time = $date_time;
            }
            $equipment_parameter_value->value = strval($value);
            $equipment_parameter_value->status_id = $status_id;

            if (!$equipment_parameter_value->save()) {
                $errors[] = $equipment_parameter_value->errors;
                throw new Exception($method_name . '. Не смог записать значение в БД');
            }
        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }

    // actionAddEquipmentParameterSensor - сохранение привязки сенсора к конкретному параметру оборудования
    public function actionAddEquipmentParameterSensor($equipment_parameter_id, $sensor_id, $date_time = 1): int
    {
        $equipment_parameter_sensor = new EquipmentParameterSensor();
        $equipment_parameter_sensor->equipment_parameter_id = (int)$equipment_parameter_id;
        if ($date_time == 1) $equipment_parameter_sensor->date_time = date("Y-m-d H:i:s", strtotime("-1 second"));
        else $equipment_parameter_sensor->date_time = $date_time;
        $equipment_parameter_sensor->sensor_id = (int)$sensor_id;
        if (!$equipment_parameter_sensor->save()) {
            return (-1);
        } else return 1;
    }

    // actionAddEquipmentFunction - сохранение функций оборудования
    public function actionAddEquipmentFunction($equipment_id, $function_id)
    {
        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем id
        if ($equipment_function = EquipmentFunction::find()->where(['equipment_id' => $equipment_id, 'function_id' => $function_id])->one()) {
            return $equipment_function->id;
        } else {
            $equipment_function_new = new EquipmentFunction();
            $equipment_function_new->equipment_id = $equipment_id;                                                      // id оборудования
            $equipment_function_new->function_id = $function_id;                                                        // id функции

            if ($equipment_function_new->save()) return $equipment_function_new->id;
            else return -1;
        }
    }

    // actionLoadCommonInfo - функция отправки данных для заполнения вкладки общие сведения
    public function actionLoadCommonInfo()
    {
        $log = new LogAmicumFront("actionLoadCommonInfo");
        $result = array();

        $params = array();

        try {
            $log->addLog("Начал выполнять метод");

            $post = Yii::$app->request->post();

            if (!isset($post['specific_id']) or $post['specific_id'] == "") {
                throw new Exception("Не передан идентификатор оборудования");
            }

            $equipment_id = (int)$post['specific_id'];
            $params = $this->buildCommonInfoArray($equipment_id);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result, 'params' => $params], $log->getLogAll());
    }

    // buildCommonInfoArray - функция формирования данных для заполнения вкладки общие сведения
    public function buildCommonInfoArray(int $equipment_id)
    {
        $arr_of_handbook_params = array(104, 160, 162, 163, 165, 168);
        $props = array();
        $i = 0;
        foreach ($arr_of_handbook_params as $single) {
            $props[$i]['parameter_id'] = $single;

            if ($equipment_parameter = EquipmentParameter::findOne(['equipment_id' => $equipment_id, 'parameter_id' => $single, 'parameter_type_id' => 1])) {
                $props[$i]['specific_object_parameter_id'] = $equipment_parameter->id;
                if ($equipment_parameter_handbook_value = EquipmentParameterHandbookValue::find()->where(['equipment_parameter_id' => $equipment_parameter->id])->orderBy(['date_time' => SORT_DESC])->one()) {
                    $props[$i]['value'] = $equipment_parameter_handbook_value->value;
                }
            }
            $i++;
        }
        return $props;
    }

    // actionSearchEquipments - метод поиска оборудования в справочнике конкретных объектов
    public function actionSearchEquipments()
    {
        $post = Yii::$app->request->post();
        $errors = array();
        $specific_object_array = array();
        $typical_objects = array();
        $object_types = array();
        $empty_title = false;
        $search_query = "";
        $eq_title = "";
        if (isset($post['title']) and $post['title'] != "") {
            $search_query = strval($post['title']);
            $kind_object = KindObject::findOne(1);//вид объектов - Оборудование
            foreach ($kind_object->objectTypes as $object_type) {
//                echo $object_type->title."\n";
                foreach ($object_type->objects as $object) {
//                    echo $object->title."\n";
                    $i = 0;
                    $specific_objects = Equipment::find()
                        ->where(["equipment.object_id" => $object->id])
                        ->filterWhere(['like', 'equipment.title', $search_query])
                        ->all();

                    foreach ($specific_objects as $equipment) {
                        //echo $equipment->title."\n";
//                            $eq_title = explode($search_query,$equipment->title);
//                            var_dump($eq_title);
//                            $nt = $eq_title[0]."<span class='marked'>".$search_query."</span>".isset($eq_title[1]) ? $eq_title[1] : '';
                        $specific_object_array[$i]['id'] = $equipment->id;
                        $specific_object_array[$i]['title'] = $equipment->title;
//                            var_dump(Main::findOne($equipment->id));
                        if ($main_equipment = Main::findOne($equipment->id)) {
                            $specific_object_array[$i]['table_name'] = $main_equipment->table_address;
                        }

                        $i++;
                    }
                }
                ArrayHelper::multisort($specific_object_array, 'title', SORT_ASC);
            }
            $j = -1;
            foreach ($specific_object_array as $specific_object) {
                $object = TypicalObject::findOne(['id' => Equipment::findOne(['id' => $specific_object['id']])->object_id]);
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
        } else if ($post['title'] == "") {
            $empty_title = "true";
        } else {
            $errors[] = "не передан параметр поиска";
        }
//        var_dump($object_types);
//        return;
        $result = array('errors' => $errors, 'object_types' => $object_types, 'empty_title' => $empty_title);
        echo json_encode($result);
    }

    /**
     * actionEditSpecificObject - функция редактирования конкретных объектов
     */
    public function actionEditSpecificObject()
    {
        $errors = array();
        $objectKinds = null;
        $specificObjects = null;
        $specificParameters = null;
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 87)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['title']) && isset($post['specific_id'])
                    && isset($post['kind_object_id']) && isset($post['object_type_id']) && isset($post['object_id'])) { // проверка на передачу данных

                    $new_object_title = $post['title'];                                                                 // название конкретного объекта - новое
                    $specific_id = $post['specific_id'];                                                                // id конкретного объекта
                    $object_id = $post['object_id'];                                                                    // старый id типового объекта
                    $kind_object_id = $post['kind_object_id'];                                                          // вид типового объекта
                    $object_type_id = $post['object_type_id'];                                                          // id типа типового объекта

                    $object = Equipment::findOne($specific_id);                                                         // найти объект по id
                    if ($object) {                                                                                      // если объект существует
                        $existingObject = Equipment::findOne(['title' => $new_object_title]);                           // найти объект по названию, чтобы не было дублирующих
                        if (!$existingObject) {                                                                         // если не найден
                            $object->title = $new_object_title;                                                         // сохранить в найденный по id параметр название
                            if ($object->save()) {                                                                      // если объект сохранился
                                $equipment_parameter_id = $this->actionAddEquipmentParameter($specific_id, 162, 1); //параметр наименование
                                $equipment_parameter_value = $this->actionAddEquipmentParameterHandbookValue($equipment_parameter_id, $new_object_title, 1, 1);//сохранение значения параметра
                                if ($equipment_parameter_id == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 162";
                                $specificObjects = parent::buildSpecificObjectArray($kind_object_id, $object_type_id, $object_id);//обновить массив типовых объектов

                                $response = self::buildEquipmentParameterArray($specific_id);
                                if ($response['status'] == 1) {
                                    $specificParameters = $response['Items'];
                                }

                            } else $errors[] = "Ошибка сохранения";                                                               //если не сохранился, сохранить соответствующую ошибку
                        } else $errors[] = "Объект с таким названием уже существует";                                             //если найден объект по названию, сохранить соответствующую ошибку
                    } else $errors[] = "Объекта с id " . $specific_id . " не существует";                                              //если не найден объект по id, сохранить соответствующую ошибку
                } else $errors[] = "Данные не переданы";                                                                           //если не заданы входные параметры сохранить соответствующую ошибку
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }

        $result = array('errors' => $errors, 'specificObjects' => $specificObjects, 'specificParameters' => $specificParameters);                                            //составить результирующий массив как массив полученных массивов
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;                                                                                 //вернуть AJAX-запросу данные и ошибки
    }

    /**
     * actionMoveSpecificObject - метод перемещения оборудования
     */
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
                    && isset($post['object_id']) && isset($post['new_object_id'])) {                                            //если все данные переданы

                    $specific_id = $post['specific_id'];                                                                          //id конкретного объекта
                    $object_id = $post['object_id'];                                                                              //старый id типового объекта
                    $new_object_id = $post['new_object_id'];                                                                      //новый id типового объекта
                    $kind_object_id = $post['kind_object_id'];                                                                    //вид типового объекта
                    $object_type_id = $post['object_type_id'];                                                                    //id типа типового объекта
                    //новый id типа типового объекта

                    $specificObject = Equipment::findOne($specific_id);
                    $newSpecificId = $new_object_id;
                    $specificObject->object_id = $new_object_id;
                    if ($specificObject->save()) {
                        $objectKinds = parent::buildSpecificObjectArray($kind_object_id, $object_type_id, $object_id);
                        $newObjectKinds = parent::buildSpecificObjectArray($kind_object_id, $object_type_id, $new_object_id);
                    } else $errors[] = "Не удалось переместить объект";
                } else $errors[] = "Данные не переданы";//если не переданы сохранить соответствующую ошибку
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        $result = array('errors' => $errors, 'specificObjects' => $objectKinds, 'newSpecificObjects' => $newObjectKinds);   //составить результирующий массив как массив полученных массивов
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * actionGetSensors - метод получения списка сенсоров привязанных к оборудованию
     */
    public function actionGetSensors()
    {
        $post = Assistant::GetServerMethod();
        $debug_info = array();
        $equipment_sensor = null;
        $sensors = (new Query())
            ->select('id, title')
            ->from('sensor')
            ->where('object_id = 159')
            ->orderBy('title ASC')
            ->all();
        if (isset($post['equipment_id']) and $post['equipment_id'] !== "") {
            $equipment_id = (int)$post['equipment_id'];
            $debug_info[] = array('equipment_id' => $equipment_id);
            $equipmentSetSensor = (new Query())
                ->select('sensor.id id, sensor.title title')
                ->from('sensor')
                ->join('join', 'view_equipment_last_sensor', 'sensor.id = view_equipment_last_sensor.sensor_id')
                ->where(['view_equipment_last_sensor.equipment_id' => $equipment_id])
                ->one();
            $debug_info[] = array('equipmentSensor' => $equipmentSetSensor);
            if ($equipmentSetSensor) {
                $debug_info[] = array('point1' => "Entered if condition");
                $equipment_sensor = array('id' => $equipmentSetSensor['id'], 'title' => $equipmentSetSensor['title']);
            } else {
                $debug_info[] = array('point2' => "Entered else statement");
            }
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array('sensors' => $sensors, 'attached_sensor' => $equipment_sensor, 'debug_info' => $debug_info);
    }


    // actionSetLampToEquipment - метод привязки метки оборудования к оборудованию
    public function actionSetLampToEquipment()
    {
        $post = Assistant::GetServerMethod();
        $errors = array();
        $debug_info = array();
        $info = array();
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (isset($post['equipment_id']) && $post['equipment_id'] !== "" && isset($post['sensor_id']) && $post['sensor_id'] !== '') {
            $equipment_id = (int)$post['equipment_id'];
            $sensor_id = (int)$post['sensor_id'];
            $parameter_id = 83;
            $parameter_type_id = 2;

            $equipmentParameterId = $this->actionAddEquipmentParameter($equipment_id, $parameter_id, $parameter_type_id);
            if ($equipmentParameterId !== -1) {
                $anyEquipmentParameterModel = (new Query())
                    ->select('*')
                    ->from('view_equipment_last_sensor')
                    ->where(['sensor_id' => $sensor_id])
                    ->andWhere('equipment_id != ' . $equipment_id)
                    ->one();
                $currentEquipment = (new Query())
                    ->select('*')
                    ->from('view_equipment_last_sensor')
                    ->where(['sensor_id' => $sensor_id])
                    ->andWhere(['equipment_id' => $equipment_id])
                    ->one();
                $debug_info[] = $anyEquipmentParameterModel;
                if ($anyEquipmentParameterModel) {
                    $debug_info[] = array('equipment_parameter_id' => $anyEquipmentParameterModel['equipment_parameter_id'], 'sensor_id' => $anyEquipmentParameterModel['sensor_id'], 'date_time' => $anyEquipmentParameterModel['date_time']);
                    $errors[] = "Невозможно привязать текущую метку, так как она привязана к другому оборудованию - " . $anyEquipmentParameterModel['equipment_title'];
                } else if ($currentEquipment) {
                    $info[] = "Метка уже привязана к этому оборудованию";
                } else {
                    $equipmentParameterSensorModel = new EquipmentParameterSensor();
                    $equipmentParameterSensorModel->equipment_parameter_id = $equipmentParameterId;
                    $equipmentParameterSensorModel->sensor_id = $sensor_id;
                    $equipmentParameterSensorModel->date_time = date('Y-m-d H:i:s');
                    if (!$equipmentParameterSensorModel->save()) {
                        $errors[] = 'Возникла ошибка при сохранении идентификатора метки в таблицу equipment_parameter_sensor';
                    }
                    $equipment_cache_controller = (new EquipmentCacheController());
                    $es_key = $equipment_cache_controller->setSensorEquipment($sensor_id, $equipment_id);
                    if (!$es_key) {
                        $errors[] = 'Возникла ошибка при сохранении идентификатора метки в кеш привязки оборудования к сенсору';
                    }
                }
            } else {
                $errors[] = "Возникла ошибка при сохранении добавлении параметра к оборудованию";
            }
        } else {
            $errors[] = "Не все данные переданы";
        }
        if (isset($post['active_page']) and $post['active_page']) {
            if ($post['active_page'] == "equipment-sensor") {
                $equipments = array();
                if (isset($post['search'])) {
                    $equipments = EquipmentSensorController::getEquipmentsSensors((string)$post['search']);
                }
                Yii::$app->response->data = array('errors' => $errors, 'debug_info' => $debug_info, 'info' => $info, 'equipments' => $equipments);
            }
        } else {
            Yii::$app->response->data = array('errors' => $errors, 'debug_info' => $debug_info, 'info' => $info);
        }
    }

    /**
     * actionDeleteSpecificObject - метод удаления оборудования
     */
    public function actionDeleteSpecificObject()
    {
        $post = Yii::$app->request->post();                                                                             //получение данных от ajax-запроса
        $errors = array();
        $warnings = array();
        $toDelete = true;                                                                                               //переменная-флажок разрешающая удаление
        $objectKinds = null;
        $specificObjects = null;
        $kind_object_id = null;
        $type_object_id = null;
        $object_id = null;
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 88)) {                                        //если пользователю разрешен доступ к функции
                if (isset($post['specific_id']) and $post['specific_id'] != "" and
                    isset($post['kind_object_id']) and $post['kind_object_id'] != "" and
                    isset($post['object_type_id']) and $post['object_type_id'] != "" and
                    isset($post['object_id']) and $post['object_id'] != "") {                                           // если все данные переданы
                    $specific_id = $post['specific_id'];
                    $kind_object_id = $post['kind_object_id'];
                    $type_object_id = $post['object_type_id'];
                    $object_id = $post['object_id'];
                    $specificObject = Equipment::findOne($specific_id);
                    if ($specificObject) {                                                                              // если объект существует
                        if ($toDelete) {
                            EquipmentFunction::deleteAll(['equipment_id' => $specific_id]);                             // удаляем функции у оборудования

                            $specific_parameters = EquipmentParameter::findAll(['equipment_id' => $specific_id]);       // ищем параметры на удаление
                            foreach ($specific_parameters as $specific_parameter) {
                                EquipmentParameterValue::deleteAll(['equipment_parameter_id' => $specific_parameter->id]);// удаляем измеренные или вычисленные значения
                                EquipmentParameterHandbookValue::deleteAll(['equipment_parameter_id' => $specific_parameter->id]);// удаляем справочные значения
                                EquipmentParameter::deleteAll(['id' => $specific_parameter->id]);                       // удаляем сам параметр оборудования
                            }
                            Equipment::deleteAll(['id' => $specific_id]);                                               // удаляем само оборудование
                            $equipmentCacheController = new EquipmentCacheController();
                            $equipmentCacheController->delInEquipmentMine($specific_id);
                            $equipment_del = $equipmentCacheController->delParameterValue($specific_id);
                            $warnings = $equipment_del['warnings'];
                            $errors = array_merge($errors, $equipment_del['errors']);
                        } else $errors[] = "Нельзя удалить объект из-за наличия значений у параметров объекта " . $specific_id;
                    }
                } else $errors[] = "Данные не переданы";                                                                // сохранить соответствующую ошибку

                $specificObjects = parent::buildSpecificObjectArray($kind_object_id, $type_object_id, $object_id);      // построение списка типовых объектов
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        $result = array('errors' => $errors, 'specificObjects' => $specificObjects, 'warnings' => $warnings);           // составить результирующий массив как массив полученных массивов
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * actionSaveSpecificParametersValues - функция сохранения значений с вкладки справочника конкретных объектов
     * $post['table_name'] - имя таблицы
     * $post['parameter_values_array'] - массив значений
     * $post['specificObjectId'] - id конкретного объекта
     */
    public function actionSaveSpecificParametersValues(): Response
    {
        $result = array();                                                                                              // промежуточный результирующий массив
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                            // массив предупреждений
        $eq_p_h_v_to_db = false;
        $eq_p_v_to_db = false;
        $eq_p_s_to_db = false;
        $status = 1;
        $common_info = array();
        $mine_id = false;
        $response = array();
        $objectParameters = null;
//        $object_id = null;
//        $object_type_id = null;
//        $object_kind_id = null;
        $equipment_id = null;
        $objects = array();
        $warnings[] = "actionSaveSpecificParametersValues. Начал выполнять метод";
        try {
            /**
             * Блок проверки прав пользователя
             */
            $session = Yii::$app->session;
            $session->open();
            $warnings[] = "actionSaveSpecificParametersValues. Начинаю проверять права пользователя";
            if (isset($session['sessionLogin'])) {                                                                      // если в сессии есть логин
                if (!AccessCheck::checkAccess($session['sessionLogin'], 92)) {
                    throw new Exception("actionSaveSpecificParametersValues. Недостаточно прав для совершения данной операции");
                }
            } else {
                $errors[] = "actionSaveSpecificParametersValues. Время сессии закончилось. Требуется повторный ввод пароля";
                $this->redirect('/');
                throw new Exception("actionSaveSpecificParametersValues. Время сессии закончилось. Требуется повторный ввод пароля");
            }

            /**
             * Блок проверки наличия входных параметров
             */
            $post = Yii::$app->request->post(); //получение данных от ajax-запроса
            if (isset($post['parameter_values_array']) and isset($post['specific_object_id'])) {
                $parameterValues = json_decode($post['parameter_values_array'], true);                         // массив параметров и их значений
                $equipment_id = $post['specific_object_id'];
                $date_time = \backend\controllers\Assistant::GetDateNow();
                $warnings[] = "actionSaveSpecificParametersValues. Проверил блок входных параметров. редактируемое оборудование: $equipment_id";
            } else {
                throw new Exception("actionSaveSpecificParametersValues. Входные параметры со страницы фронт энд не переданы");
            }

            /**
             * Проверяем наличие исправляемого оборудования в БД и получаем его обязательные параметры из БД и
             * инициализируем модель оборудования для последующего сохранения базовых параметров в исходную таблицу Equipment
             */
            $equipment = Equipment::findOne(['id' => $equipment_id]);
            if (!$equipment) {
                throw new Exception("actionSaveSpecificParametersValues. Редактируемое оборудование отсутствует в БД");
            }
            $equipment_title = $equipment['title'];

            /**
             * Проверяем или инициализируем входные параметры перед вставкой
             * делаем проверку на вставку значений параметра. Если не задано, то прописываем -1.
             */
            if ($parameterValues) {
                /**
                 * Перепаковываем параметры оборудования для массовой вставки за раз
                 */
                foreach ($parameterValues as $parameter) {
                    if ($parameter['parameterValue'] != "" or $parameter['parameterValue'] != "empty") {
                        $equipment_parameter_value = '-1';
                    }
                    $parameter_id = (int)$parameter['parameterId'];
                    $parameter_type_id = $parameter['parameterTypeId'];
                    $parameter_value = $parameter['parameterValue'];
                    $equipment_parameter_id = $parameter['specificParameterId'];
                    switch ($parameter['parameterStatus']) {
                        case 'handbook':
                            // готовим к сохранению значение параметров в базовые справочники объекта
                            switch ($parameter_id) {
                                case 162:       // параметр наименование
                                    /**
                                     * Проверка имени оборудования при сохранении оборудования на дубли
                                     */
                                    $equipment_title_check = Equipment::findOne('title=$parameter_value');
                                    if (!$equipment_title_check) {
                                        $equipment->title = (string)$parameter_value;
                                        $equipment_title = (string)$parameter_value;
                                    } else {
                                        $errors[] = "actionSaveSpecificParametersValues. Сохраняемое имя оборудования $parameter_value уже существует в БД. Основное имя в базовом справочнике не изменено";
                                    }
                                    break;
                                case 274:       // параметр типовой объект
                                    $equipment->object_id = (int)$parameter_value;
                                    $object_id = (int)$parameter_value;
                                    /**
                                     * Блок инициализации сведений о типовом объекте оборудования при его сохранении, для того, что бы потом можно было
                                     * вывести его правильно в списке и сохранить верно сведения в главный кеш оборудования
                                     */
                                    $typical_object = TypicalObject::find()
                                        ->with('objectType')
                                        ->where(['id' => $object_id])
                                        ->limit(1)
                                        ->one();
                                    if (!$typical_object) {
                                        throw new Exception("actionSaveSpecificParametersValues. Типовой объект $object_id не найден в БД в таблице object");
                                    }
                                    $object_title = $typical_object->title;
                                    $object_type_id = $typical_object->object_type_id;
                                    $object_type_title = $typical_object->objectType->title;
                                    $warnings[] = $typical_object->objectType;
                                    $object_kind_id = $typical_object->objectType->kind_object_id;
                                    $warnings[] = "actionSaveSpecificParametersValues. Подготовил набор базовых параметров для обратного построения справочника";
                                    $warnings[] = "actionSaveSpecificParametersValues. ИД Типового объекта: $object_id";
                                    $warnings[] = "actionSaveSpecificParametersValues. Название типового объекта: $object_type_title";
                                    $warnings[] = "actionSaveSpecificParametersValues. Тип типового объекта: $object_type_id";
                                    $warnings[] = "actionSaveSpecificParametersValues. Вид типового объекта: $object_kind_id";
                                    break;
                                case 104:       // параметр тип датчика АСУТП
                                    $equipment->inventory_number = (string)$parameter_value;
                                    break;
                                case 346:       // параметр шахтное поле - инициализируется переменная здесь, для определения необходимости инициализации кеша шахта - для статичных сенсоров
                                    $mine_id = (int)$parameter_value;
                                    break;
                            }
                            /**
                             * Сохранение самого значения в оборудование параметр Value
                             */
                            $eq_p_h_v['equipment_parameter_id'] = (int)$equipment_parameter_id;
                            $eq_p_h_v['date_time'] = $date_time;
                            $eq_p_h_v['value'] = (string)$parameter_value;
                            $eq_p_h_v['status_id'] = 1;
                            $eq_p_h_v_to_db[] = $eq_p_h_v;
                            //$warnings[] = "actionSaveSpecificParametersValues. Значение параметра " . $parameter['parameterId'] . " сохранено. specificParameterId = " . $parameter['specificParameterId'] . "Идентификатор объекта " . equipment_id;//сохранить соответствующую ошибку
                            // создаем массив для вставки разовой в кеш
                            $equipment_parameter_value_to_caches[] = EquipmentCacheController::buildStructureEquipmentParametersValue(
                                $equipment_id, $equipment_parameter_id,
                                $parameter_id, $parameter_type_id,
                                $date_time, $parameter_value, 1);
                            break;
                        case 'sensor':
                            /**
                             * Сохранение привязки оборудования к сенсору (тип параметра 2 - (метка) в кеш SeEq) (тип параметра 3 - (OPC) в кеш SePaEq)
                             */
                            $eq_p_s['equipment_parameter_id'] = (int)$equipment_parameter_id;
                            $eq_p_s['date_time'] = $date_time;
                            $eq_p_s['sensor_id'] = (int)$parameter_value;
                            $eq_p_s_to_db[] = $eq_p_s;

                            $warnings[] = "actionSaveSpecificParametersValues. ПРИВЯЗКА ТЕГА OPC " . $parameter_type_id;
                            $eq_p_s_to_cache = EquipmentCacheController::buildStructureEquipmentParameterSensor((int)$parameter_value, $equipment_id, $parameter_id, (int)$equipment_parameter_id);
                            $eq_p_s_to_cache3[] = $eq_p_s_to_cache;

                            break;
                        case 'manual':
                        case 'calc':
                        case 'edge':
                        case 'place':
                        switch ($parameter_id) {

                            case 346:       // параметр шахтное поле - инициализируется переменная здесь, для определения необходимости инициализации кеша шахта - для статичных сенсоров
                                $mine_id = (int)$parameter_value;
                                break;
                        }
                        /**
                         * Сохранение самого значения в оборудование параметр валуе
                         */
                        $eq_p_v['equipment_parameter_id'] = (int)$equipment_parameter_id;
                        $eq_p_v['date_time'] = $date_time;
                        $eq_p_v['value'] = (string)$parameter_value;
                        $eq_p_v['status_id'] = 1;
                        $eq_p_v_to_db[] = $eq_p_v;
                        //$warnings[] = "actionSaveSpecificParametersValues. Значение параметра " . $parameter['parameterId'] . " сохранено. specificParameterId = " . $parameter['specificParameterId'] . "Идентификатор объекта " . $equipment_id;//сохранить соответствующую ошибку
                        // создаем массив для вставки разовой в кеш
                        $equipment_parameter_value_to_caches[] = EquipmentCacheController::buildStructureEquipmentParametersValue(
                            $equipment_id, $equipment_parameter_id,
                            $parameter_id, $parameter_type_id,           //важно!!!! с фронта может прилетать тип параметра 4, что, то же самое, что и 2. что бы работал кеш, поставил жестко 2. Якимов М.Н.
                            $date_time, $parameter_value, 1);
//                            }
                            break;
                        default:
                            $errors[] = "actionSaveSpecificParametersValues. Неизвестный статус параметра при сохранении. parameterStatus:" . $parameter['parameterStatus'];
                    }
                }
                /**
                 * Сохраняем массово Equipment_parameter_handbook_value
                 */
                if ($eq_p_h_v_to_db) {
                    $insert_result = Yii::$app->db->createCommand()->batchInsert('equipment_parameter_handbook_value',
                        ['equipment_parameter_id', 'date_time', 'value', 'status_id'], $eq_p_h_v_to_db)->execute();
                    $warnings[] = "actionSaveSpecificParametersValues. Количество вставленных записей в Equipment_parameter_handbook_value" . $insert_result;
                } else {
                    $warnings[] = "actionSaveSpecificParametersValues. Значения в Equipment_parameter_handbook_valueS параметров не было сохранено";
                }
                unset($eq_p_h_v_to_db);

                /**
                 * Сохраняем массово Equipment_parameter_sensor
                 */
                if ($eq_p_s_to_db) {
                    $insert_result = Yii::$app->db->createCommand()->batchInsert('equipment_parameter_sensor',
                        ['equipment_parameter_id', 'date_time', 'sensor_id'], $eq_p_s_to_db)->execute();
                    $warnings[] = "actionSaveSpecificParametersValues. Количество вставленных записей в Sensor_parameter_sensor" . $insert_result;
                } else {
                    $warnings[] = "actionSaveSpecificParametersValues. Значения в equipment_parameter_sensorS параметров не было сохранено";
                }
                unset($eq_p_s_to_db);

                /**
                 * Сохраняем массово Equipment_parameter_value
                 */
                if ($eq_p_v_to_db) {
                    $insert_result = Yii::$app->db->createCommand()->batchInsert('equipment_parameter_value',
                        ['equipment_parameter_id', 'date_time', 'value', 'status_id'], $eq_p_v_to_db)->execute();
                    $warnings[] = "actionSaveSpecificParametersValues. Количество вставленных записей в equipment_parameter_value" . $insert_result;
                } else {
                    $warnings[] = "actionSaveSpecificParametersValues. Значения в equipment_parameter_valueS параметров не было сохранено";
                }
                unset($eq_p_v_to_db);

                /**
                 * Сохранение базовой модели в БД
                 */
                if (!$equipment->save())                                    //если не сохранилась
                {
                    $errors[] = "actionSaveSpecificParametersValues. Ошибка сохранения модели Equipment: $equipment_id";
                    $errors[] = $equipment->errors;
                } else {
                    $warnings[] = "actionSaveSpecificParametersValues. Модель оборудования $equipment_id в БД успешно сохранена ";
                }
                /**
                 * Важный комментарий!!!! если у оборудования нет параметра 346 - шахтное поле, то для него кеш EquipmentMine не инициализируется
                 * совсем, это значит, что для того, что бы оборудование попало в кеш для него должен существовать хотя бы пустой параметр 346
                 * соответственно перенос оборудования из шахты в шахту осуществляется только при изменении параметра 346
                 * соответственно инициализация оборудования первичная происходит при записи как раз этого самого параметра 346.
                 * если этого параметра нет, то мы базовые сведения о сенсоре просто сохраняем в БД
                 * Если же у оборудования есть параметр 346, и мы меняем базовые сведения об этом параметре, то мы их применим при записи параметра 346.
                 * важно!!! при записи параметра 346 сведения в данном методе всегда берутся из БД!
                 * В то время как перенос сенсоров при работе служб может осуществляться и без забора данных с БД - путем получения старого значения кеша
                 * EquipmentMine и записи всего, что там в новый, но с учетом измененной шахты
                 *
                 * ЕЩЕ РАЗ:
                 * перенос шахты для этого метода возможен только при инициализации оборудования из БД
                 * перенос шахты из служб сбора данных, где не меняются базовые сведения возможен через старое значение в кеше
                 */

                /**
                 * Определяем какую шахту писать - для разных типов сенсоров она разная, для стационарных берем с handbook
                 * для подвижных берем с value
                 */

                /**
                 * Блок переноса оборудования в новую шахту если таковое требуется
                 * если шахта есть, то делаем перенос или инициализацию, в зависимости от описанного выше
                 */
                if ($mine_id) {
                    $equipment_to_cache = EquipmentCacheController::buildStructureEquipment($equipment_id, $equipment_title, $object_id, $object_title, $object_type_id, $mine_id);
                    $ask_from_method = EquipmentMainController::AddMoveEquipmentMineInitDB($equipment_to_cache);
                    if ($ask_from_method['status'] == 1) {
                        $warnings[] = $ask_from_method['warnings'];
                        $warnings[] = "actionSaveSpecificParametersValues. обновил главный кеш оборудования";
                    } else {
                        $warnings[] = $ask_from_method['warnings'];
                        $errors[] = $ask_from_method['errors'];
                        throw new Exception("actionSaveSpecificParametersValues. Не смог обновить главный кеш оборудования" . $equipment_id);
                    }
                }

                /**
                 * Обновление параметров оборудования в кеше
                 */
                $equipment_cache_controller = new EquipmentCacheController();
                $ask_from_method = $equipment_cache_controller->multiSetEquipmentParameterValue($equipment_id, $equipment_parameter_value_to_caches);
                if ($ask_from_method['status'] == 1) {
                    $warnings[] = $ask_from_method['warnings'];
                    $warnings[] = "actionSaveSpecificParametersValues. обновил параметры оборудования в кеше";
                } else {
                    $warnings[] = $ask_from_method['warnings'];
                    $errors[] = $ask_from_method['errors'];
                    throw new Exception("actionSaveSpecificParametersValues. Не смог обновить параметры в кеше оборудования" . $equipment_id);
                }

                /**
                 * Обновление привязки оборудования к сенсорам в кеше
                 */
                if (isset($eq_p_s_to_cache2)) {
                    $ask_from_method = $equipment_cache_controller->multiSetEquipmentSensor($eq_p_s_to_cache2);
                    if ($ask_from_method['status'] == 1) {
                        $warnings[] = $ask_from_method['warnings'];
                        $warnings[] = "actionSaveSpecificParametersValues. обновил привязку оборудования к сенсорам в кеше";
                    } else {
                        $warnings[] = $ask_from_method['warnings'];
                        $errors[] = $ask_from_method['errors'];
                        throw new Exception("actionSaveSpecificParametersValues. Не смог обновить в кеше привязку оборудования к сенсорам" . $equipment_id);
                    }
                } else {
                    $warnings[] = "actionSaveSpecificParametersValues. ПРИВЯЗОК МЕТОК К ОБОРУДОВАНИЮ НЕ БЫЛО";
                }

                /**
                 * Обновление привязки оборудования по параметрам к сенсорам в кеше
                 */
                if (isset($eq_p_s_to_cache3)) {
                    $equipment_cache_controller->delSensorEquipmentParameter($equipment_id, '*');
                    $ask_from_method = $equipment_cache_controller->multiSetEquipmentParameterSensor($eq_p_s_to_cache3);
                    if ($ask_from_method['status'] == 1) {
                        $warnings[] = $ask_from_method['warnings'];
                        $warnings[] = "actionSaveSpecificParametersValues. обновил привязку оборудования по параметрам к сенсорам в кеше";
                    } else {
                        $warnings[] = $ask_from_method['warnings'];
                        $errors[] = $ask_from_method['errors'];
                        throw new Exception("actionSaveSpecificParametersValues. Не смог обновить в кеше привязку оборудования по параметрам к сенсорам" . $equipment_id);
                    }
                } else {
                    $warnings[] = "actionSaveSpecificParametersValues. ПРИВЯЗОК ТЕГО К ОБОРУДОВАНИЮ НЕ БЫЛО";
                }

            } else {
                $warnings[] = 'actionSaveSpecificParametersValues. Массив параметров на сохранение пуст. изменений в БД сделано не было';
            }
            /**
             * Блок построения выходных параметров после сохранения параметров
             * 1. Строим данные по самому сенсору
             * 2. строим данные для меню
             */
            $response = self::buildEquipmentParameterArray($equipment_id);
            if ($response['status'] == 1) {
                $objectParameters = $response['Items'];
            }

            /**
             * Код ниже с блоком if нужен для поиска object_type_id и object_kind_id для случая, когда не передан типовой объект как параметр 274
             */
            if ($equipment_id) {
                $new_equipment = Equipment::find()
                    ->where(['id' => $equipment_id])
                    ->with('object')
                    ->with('object.objectType')
                    ->with('object.objectType.kindObject')
                    ->limit(1)
                    ->one();

                $objects = parent::buildSpecificObjectArray($new_equipment->object->objectType->kindObject->id, $new_equipment->object->objectType->id, $new_equipment->object->id);
                $common_info = $this->buildCommonInfoArray($equipment_id);
            }

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionSaveSpecificParametersValues. Исключение';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "actionSaveSpecificParametersValues. Вышел с метода";
        $result_main = array('response' => $response, 'errors' => $errors, 'warnings' => $warnings, 'objectProps' => $objectParameters, 'objects' => $objects, 'common_info' => $common_info);//составить результирующий массив как массив полученных массивов         //вернуть AJAX-запросу данные и ошибки
        return $this->asJson($result_main);
    }

    // actionSaveCommonInfo - метод сохранения фотографии оборудования
    public function actionSaveCommonInfo()
    {
        $log = new LogAmicumFront("actionSaveCommonInfo");
        $result = array();
        $params = array();
        $url = null;                                                                                                    // путь до изображения

        try {
            $post = Yii::$app->request->post();                                                                         // получение данных от ajax-запроса

            if (
                $_FILES['imageFile']['size'] > 5250000 or
                !isset($post['image_type']) or
                $post['image_type'] == ""
            ) {
                throw new Exception("Файл весит больше 5 МБ или выбран файл с расширением, отличным от JPEG, PNG");
            }

            $file = $_FILES['imageFile'];                                                                               // записываем в переменную полученные данные
            $post = Yii::$app->request->post();
            $image_type = $post['image_type'];                                                                          // записываем в переменную полученные данные
            $specific_id = $post['specific_id'];                                                                        // записываем в переменную полученные данные
            $upload_dir = 'img/2d_models/specific_objects/equipment/';                                                  // объявляем и инициируем переменную для хранения пути к папке с изображениями

            $tmp_name = explode(' ', $post['equipment_name']);
            $result_str = "";
            foreach ($tmp_name as $substr) {
                $result_str = strlen($result_str) > 0 ? $result_str . "_" . $substr : $result_str . $substr;
            }
            $uploaded_file = $upload_dir . $result_str . " " . date('d-m-Y H-i-s') . "." . $image_type;          // задаем имя файла

            if (!move_uploaded_file($file['tmp_name'], $uploaded_file)) {                                               // если удалось сохранить переданный файл в указанную директорию
                throw new Exception("Не удалось сохранить файл");
            }

            $url = $uploaded_file;

            $parameter = EquipmentParameter::findOne(['parameter_id' => 168, 'equipment_id' => $specific_id, 'parameter_type_id' => 1]);

            if (!$parameter) {
                $new_param_id = $this->actionAddEquipmentParameter($specific_id, 168, 1);
                if ($new_param_id == -1) {
                    throw new Exception("Ошибка при создании параметра 168");
                }
            } else {
                $new_param_id = $parameter->id;
            }

            $flag_done = $this->actionAddEquipmentParameterHandbookValue($new_param_id, $uploaded_file, 1, date("Y-m-d H:i:s"));
            if ($flag_done == -1) {
                throw new Exception("Ошибка сохранения параметра 168");
            }

            $response = self::buildEquipmentParameterArray($specific_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка получения параметров конкретного оборудования");
            }
            $params = $response['Items'];

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $url, 'url' => $url, 'parameters' => $params], $log->getLogAll());
    }

    // actionSaveCommonInfoValues - метод сохранения общих сведений
    public function actionSaveCommonInfoValues()
    {
        $log = new LogAmicumFront("actionSaveCommonInfoValues");
        $result = array();

        $props = array();
        $common_info = array();
        $equipments = array();

        try {
            $log->addLog("Начал выполнять метод");

            $post = Yii::$app->request->post();

            if (!isset($post['specific_id']) or $post['specific_id'] == "" or !isset($post['info'])) {
                throw new Exception("Не переданы входные параметры");
            }

            $infos = $post['info'];
            $equipment_id = (int)$post['specific_id'];

            $equipment = Equipment::find()
                ->joinWith('object.objectType')
                ->where(['equipment.id' => $equipment_id])
                ->asArray()
                ->one();

            if (!$equipment) {
                throw new Exception("Нет запрашиваемого оборудования в БД");
            }

            $object_id = $equipment['object_id'];
            $object_type_id = $equipment['object']['object_type_id'];
            $object_kind_id = $equipment['object']['objectType']['kind_object_id'];

            foreach ($infos as $info) {
                $existedParam = EquipmentParameter::findOne([
                    'equipment_id' => $equipment_id,
                    'parameter_id' => $info['parameter'],
                    'parameter_type_id' => 1,
                ]);

                if (!$existedParam) {
                    $existedParam = new EquipmentParameter();
                    $existedParam->equipment_id = $equipment_id;
                    $existedParam->parameter_id = (int)$info['parameter'];
                    $existedParam->parameter_type_id = 1;
                    if (!$existedParam->save()) {
                        $log->addData($existedParam->errors, '$existedParam->errors', __LINE__);
                        throw new Exception("Не удалось создать новый параметр EquipmentParameter");
                    }
                    $existedParam->refresh();
                }


                $equipment_param_handbook = new EquipmentParameterHandbookValue();

                $equipment_param_handbook->equipment_parameter_id = $existedParam->id;

                if (isset($info['parameterValue']) and $info['parameterValue'] != "") {
                    $equipment_param_handbook->value = $info['parameterValue'];
                } else {
                    $equipment_param_handbook->value = "-1";
                }

                $equipment_param_handbook->date_time = date('Y-m-d H:i:s');
                $equipment_param_handbook->status_id = 1;

                if (!$equipment_param_handbook->save()) {
                    $log->addData($equipment_param_handbook->errors, '$equipment_param_handbook->errors', __LINE__);
                    throw new Exception("Не удалось сохранить значение нового параметра");
                }

                if ($equipment_param_handbook->equipmentParameter->parameter_id == 162) {
                    $flag = $this->actionUpdateEquipmentValuesString($equipment_id, "title", $equipment_param_handbook->value);
                    if ($flag == -1) {
                        throw new Exception("Не удалось редактировать наименование оборудования");
                    }
                }

                if ($equipment_param_handbook->equipmentParameter->parameter_id == 104) {
                    $flag = $this->actionUpdateEquipmentValuesString($equipment_id, "inventory_number", $equipment_param_handbook->value);
                    if ($flag == -1) {
                        throw new Exception("Не удалось редактировать наименование оборудования");
                    }
                }
            }

            $equipments = parent::buildSpecificObjectArray($object_kind_id, $object_type_id, $object_id);
            $log->addLog("Получил список оборудования");

            $response = self::buildEquipmentParameterArray($equipment_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка получения списка параметров сохраняемого оборудования");
            }
            $props = $response['Items'];

            $common_info = $this->buildCommonInfoArray($equipment_id);
            $log->addLog("Получил общие сведения сохраняемого оборудования");


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $props, 'paramArray' => $props, 'equipments' => $equipments, 'common_info' => $common_info], $log->getLogAll());
    }

    /**
     * actionUploadPicture - Метод загрузки изображения оборудования
     * При сохранении файла добавляется запись в базу:
     * При отсутствии 168 параметра, добавляется новый параметр, и заносится его значение.
     * Если параметр был, отправить значение.
     * @param $file - файл с изображением.
     * @param $equipment_id - id оборудования.
     * @param $equipment_title - название оборудования.
     * @param $image_type - формат изображения.
     */
    public function actionUploadPicture()
    {
        $log = new LogAmicumFront("actionUploadPicture");
        $result = array();

        $equipment_parameters = array();
        $url = null;

        try {
            $post = Yii::$app->request->post();

            if (
                !isset($_FILES['file']) or
                !isset($post['title']) or $post['title'] == "" or
                !isset($post['type']) or $post['type'] == "" or
                !isset($post['equipment_id']) or $post['equipment_id'] == ""
            ) {
                throw new Exception("Не все параметры переданы");
            }

            $file = $_FILES['file'];
            $equipment_title = $post['title'];
            $image_type = $post['type'];
            $equipment_id = $post['equipment_id'];
            $upload_dir = 'img/2d_models/specific_objects/equipment/';

            $equipment_url_path = Assistant::UploadPicture($file, $upload_dir, $equipment_title, $image_type);                   // Вызываем метод сохранения изображения в папку

            if ($equipment_url_path == -1) {
                throw new Exception("Не удалось сохранить файл");
            }

            $url = $equipment_url_path;

            $equipment_picture_parameter = EquipmentParameter::findOne(['equipment_id' => $equipment_id, 'parameter_id' => 168, 'parameter_type_id' => 1]);
            if (!$equipment_picture_parameter) {
                $equipment_parameter_id = $this->actionAddEquipmentParameter($equipment_id, 168, 1);  //Создаем параметр в базе

                if ($equipment_parameter_id == -1) {
                    throw new Exception("Не удалось сохранить новый параметр");
                }
            } else {
                $equipment_parameter_id = $equipment_picture_parameter->id;
            }

            $equipment_handbook_value = EquipmentParameterHandbookValue::find()
                ->where(['equipment_parameter_id' => $equipment_parameter_id])
                ->orderBy(['date_time' => SORT_DESC])
                ->one();

            if (!$equipment_handbook_value or ($equipment_handbook_value and $equipment_handbook_value->value != $equipment_url_path)) {
                $equipment_new_handbook_value = $this->actionAddEquipmentParameterHandbookValue($equipment_parameter_id, $equipment_url_path, 1, date('Y-m-d H:i:s'));   //Сохранение значения параметра в базу

                if ($equipment_new_handbook_value == -1) {
                    throw new Exception("Не удалось сохранить справочное значение");
                }
            }


            $response = self::buildEquipmentParameterArray($equipment_id);

            if ($response['status'] != 1) {
                throw new Exception("Не удалось получить список конкретных параметров оборудования");
            }

            $equipment_parameters = $response['Items'];


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $url, "url" => $url, 'parameters' => $equipment_parameters], $log->getLogAll());
    }

    // actionUpdateEquipmentValuesString - сохраняет базовые Текстовые параметры в базовый справочник
    public function actionUpdateEquipmentValuesString($specific_id, $name_field, $value): int
    {
        $equipment_update = Equipment::findOne(['id' => $specific_id]);
        $equipment_update->$name_field = (string)$value;
        if (!$equipment_update->save()) return -1;
        else return 1;
    }

// actionUpdateEquipmentValuesInt - сохраняет базовые Числовые параметры в базовый справочник
    public function actionUpdateEquipmentValuesInt($specific_id, $name_field, $value): int
    {
        $equipment_update = Equipment::findOne(['id' => $specific_id]);
        $equipment_update->$name_field = (int)$value;
        if (!$equipment_update->save()) return -1;
        else return 1;
    }

// actionAddEquipmentParameterOne - добавление нового параметра оборудования из страницы frontend
    public function actionAddEquipmentParameterOne()
    {
        $errors = array();
        $paramsArray = array();
        $session = Yii::$app->session;                                                                                  // старт сессии
        $session->open();                                                                                               // открыть сессию
        if (isset($session['sessionLogin'])) {                                                                          // если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 90)) {                                       // если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['id']) && isset($post['parameter_id']) && isset($post['parameter_type_id'])) {
                    $specific_id = $post['id'];
                    $parameter_id = $post['parameter_id'];
                    $parameter_type_id = $post['parameter_type_id'];
                    $equipment_parameter = $this->actionAddEquipmentParameter($specific_id, $parameter_id, $parameter_type_id);
                    if ($equipment_parameter == -1) $errors[] = "не удалось сохранить параметр";

                    $response = self::buildEquipmentParameterArray($specific_id);
                    if ($response['status'] == 1) {
                        $paramsArray = $response['Items'];
                    }

                } else {
                    $errors[] = "Не все параметры переданы";
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
        Yii::$app->response->data = $result;                                                                                 //вернуть AJAX-запросу данные и ошибки
    }

    // actionDeleteSpecificParameter - метод удаления параметров для противопожарных систем
    public function actionDeleteSpecificParameter()
    {
        $paramsArray = array();
        $errors = array();
        $warnings = array();
        $common_info = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 91)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['table_name']) and $post['table_name'] != "" and
                    isset($post['action_type']) and $post['action_type'] != "" and
                    isset($post['specific_object_id']) and $post['specific_object_id'] != "") {

                    $table_name = $post['table_name'];
                    $actionType = $post['action_type'];
                    $specificObjectId = $post['specific_object_id'];
                    $equipmentCacheController = new EquipmentCacheController();
                    if ($actionType == "local") {
                        if (isset($post['specific_parameter_id']) and $post['specific_parameter_id'] != "") {
                            $specificParameterId = $post['specific_parameter_id'];
                            $equipment_parameter = EquipmentParameter::findOne(['id' => $specificParameterId]);
                            /**
                             * Для параметра 168 2д модель мы не удаляем параметр, а только очищаем значение, так как
                             * на 3д схеме 2д модель берется из типового объекта, поэтому сам параметр должен остаться
                             * для остальных параметров удаляем всё
                             */
                            if ($equipment_parameter->parameter_id == 168) {
                                EquipmentParameterHandbookValue::deleteAll(['equipment_parameter_id' => $specificParameterId]);
                                $equipment_del = $equipmentCacheController->delParameterValue($equipment_parameter->equipment_id, $equipment_parameter->parameter_id, $equipment_parameter->parameter_type_id);
                                $warnings[] = $equipment_del['warnings'];
                                $errors = array_merge($errors, $equipment_del['errors']);
                            } else {
                                EquipmentParameterSensor::deleteAll(['equipment_parameter_id' => $specificParameterId]);
                                EquipmentParameterHandbookValue::deleteAll(['equipment_parameter_id' => $specificParameterId]);
                                EquipmentParameterValue::deleteAll(['equipment_parameter_id' => $specificParameterId]);
                                EquipmentParameter::deleteAll(['id' => $specificParameterId]);
                                $equipment_del = $equipmentCacheController->delParameterValue($equipment_parameter->equipment_id, $equipment_parameter->parameter_id, $equipment_parameter->parameter_type_id);
                                $warnings[] = $equipment_del['warnings'];
                                $errors = array_merge($errors, $equipment_del['errors']);
                                $equipment_del = $equipmentCacheController->delSensorEquipmentParameter($equipment_parameter->equipment_id, $equipment_parameter->parameter_id);
                                $warnings[] = $equipment_del;
                                if ($equipment_parameter->parameter_id == 83) {
                                    $equipment_del = $equipmentCacheController->delSensorEquipment($equipment_parameter->equipment_id);
                                    $warnings[] = $equipment_del;
                                }
                            }

                            $response = self::buildEquipmentParameterArray($specificObjectId);
                            if ($response['status'] == 1) {
                                $paramsArray = $response['Items'];
                            }
                        } else {
                            $errors[] = "Не передан equipment_parameter_id";
                        }
                    } else {
                        if (isset($post['parameter_id']) and $post['parameter_id'] != "") {
                            $parameterId = $post['parameter_id'];
                            /**
                             * Для параметра 168 2д модель мы не удаляем параметр, а только очищаем значение, так как
                             * на 3д схеме 2д модель берется из типового объекта, поэтому сам параметр должен остаться
                             * для остальных параметров удаляем всё
                             */
                            if ($parameterId == 168) {
                                $equipment_parameter = EquipmentParameter::findOne(['equipment_id' => $specificObjectId, 'parameter_id' => 168, 'parameter_type_id' => 1]);
                                if ($equipment_parameter) {
                                    EquipmentParameterHandbookValue::deleteAll(['equipment_parameter_id' => $equipment_parameter->id]);
                                    $equipment_del = $equipmentCacheController->delParameterValue($equipment_parameter->equipment_id, $equipment_parameter->parameter_id, $equipment_parameter->parameter_type_id);
                                    $warnings[] = $equipment_del['warnings'];
                                    $errors = array_merge($errors, $equipment_del['errors']);
                                }
                            } else {
                                $equipment_parameters = EquipmentParameter::find()->where(['parameter_id' => $parameterId])->all();
                                foreach ($equipment_parameters as $parameter) {
                                    EquipmentParameterSensor::deleteAll(['equipment_parameter_id' => $parameter->id]);
                                    EquipmentParameterHandbookValue::deleteAll(['equipment_parameter_id' => $parameter->id]);
                                    EquipmentParameterValue::deleteAll(['equipment_parameter_id' => $parameter->id]);
                                    $equipment_del = $equipmentCacheController->delParameterValue($parameter->equipment_id, $parameter->parameter_id, $parameter->parameter_type_id);
                                    $warnings[] = $equipment_del['warnings'];
                                    $errors = array_merge($errors, $equipment_del['errors']);
                                }
                                EquipmentParameter::deleteAll(['parameter_id' => $parameterId, 'equipment_id' => $specificObjectId]);
                            }

                            $response = self::buildEquipmentParameterArray($specificObjectId);
                            if ($response['status'] == 1) {
                                $paramsArray = $response['Items'];
                            }

                        } else {
                            $errors[] = "не передан parameter_id";
                        }
                    }
                    $common_info = $this->buildCommonInfoArray($specificObjectId);
                } else {
                    $errors[] = "не все параметры переданы";
                }
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        $result = array('paramArray' => $paramsArray, 'errors' => $errors, 'common_info' => $common_info, 'warnings' => $warnings);
        echo json_encode($result);
    }

    // actionAddEquipmentFunctionFront - функция добавления функции оборудования с post с фронта
    public function actionAddEquipmentFunctionFront()
    {
        $errors = array();
        $functionsArray = array();
        $session = Yii::$app->session;
        $session->open();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 94)) {                                       //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (
                    isset($post['specificObjectId']) &&
                    isset($post['functionId'])
                ) {
                    $equipment_id = $post['specificObjectId'];
                    $function_id = $post['functionId'];

                    $equipment_function = $this->actionAddEquipmentFunction($equipment_id, $function_id);
                    if ($equipment_function == -1) $errors[] = "не удалось сохранить параметр";
                    $functionsArray = parent::buildSpecificFunctionArray($equipment_id, "equipment");

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
        Yii::$app->response->data = $result;
    }

    // actionDeleteEquipmentFunction - функция удаления функции оборудования с post с фронта
    public function actionDeleteEquipmentFunction()
    {
        $errors = array();
        $object_functions = array();
        $session = Yii::$app->session;
        $session->open();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 95)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['specific_object_id']) && $post['specific_object_id'] != "" &&
                    isset($post['specific_function_id']) && $post['specific_function_id'] != "") {
                    $equipment_id = $post['specific_object_id'];
                    $equipment_function_id = $post['specific_function_id'];
                    EquipmentFunction::deleteAll('id=:equipment_function_id', [':equipment_function_id' => $equipment_function_id]);
                    $object_functions = parent::buildSpecificFunctionArray($equipment_id, 'equipment');
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

    // actionInitEquipmentMain - метод инициализации кеша оборудования по всей шахте
    // входные параметры
    //      mine_id - ключ шахты
    //  выходные параметры:
    //      стандартный набор
    // пример использования: 127.0.0.1/positioningsystem/specific-equipment/init-equipment-main?mine_id=290
    // разработал Якимов М.Н.
    // дата создания 10.08.2019
    public function actionInitEquipmentMain()
    {
        $errors = array();                                                                                              // массив ошибок
        $status = array();                                                                                              // состояние выполнения метода
        $result = array();
        $warnings = array();                                                                                            // массив предупреждений
        $warnings[] = "actionInitEquipmentMain. Начало выполнения метода";
        try {
            $post = Assistant::GetServerMethod();
            $mine_id = $post['mine_id'];
            $cache_equipment = Yii::$app->cache_equipment;
            $cache_equipment->flush();
            $warnings[] = "actionInitEquipmentMain. Сбросил кеш cache_equipment";
            $response = (new EquipmentCacheController())->runInit($mine_id);
            $errors[] = $response['errors'];
            $status = $response['status'];

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionInitEquipmentMain. Исключение';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "actionInitEquipmentMain. Закончил выполнение метода";

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * buildEquipmentParameterArray - функция построения параметров конкретного объекта
     */
    public static function buildEquipmentParameterArray($equipment_id): array
    {
        $log = new LogAmicumFront("buildEquipmentParameterArray");
        $result = array();

        try {
            $log->addLog("Начал выполнять метод");
            /**
             * Получение последних справочных параметров сенсора
             */
            $equipment_parameter_values = array();
            $equipment_parameter_values_handbook = (new Query())
                ->select('*')
                ->from('view_GetEquipmentParameterHandbookWithLastValue')
                ->where(['equipment_id' => $equipment_id])
                ->all();
            if ($equipment_parameter_values_handbook) {
                $equipment_parameter_values = array_merge($equipment_parameter_values, $equipment_parameter_values_handbook);
            }
            $equipment_parameter_values_measure = (new Query())
                ->select('*')
                ->from('view_GetEquipmentParameterWithLastValue')
                ->where(['equipment_id' => $equipment_id])
                ->all();
            if ($equipment_parameter_values_measure) {
                $equipment_parameter_values = array_merge($equipment_parameter_values, $equipment_parameter_values_measure);
            }

            //return $sensor_parameter_values;

            foreach ($equipment_parameter_values as $epv) {
                $group_equipment_parameter[$epv['kind_parameter_id']][$epv['parameter_id']]['parameter_type_id'][] = $epv;
                $group_equipment_parameter[$epv['kind_parameter_id']][$epv['parameter_id']]['parameter_id'] = $epv['parameter_id'];
                $group_equipment_parameter[$epv['kind_parameter_id']][$epv['parameter_id']]['parameter_title'] = $epv['parameter_title'];
                $group_equipment_parameter[$epv['kind_parameter_id']][$epv['parameter_id']]['units'] = $epv['units'];
                $group_equipment_parameter[$epv['kind_parameter_id']][$epv['parameter_id']]['units_id'] = $epv['units_id'];
            }


            if (isset($group_equipment_parameter)) {
                $equipment_parameter_sensor = (new Query())
                    ->select('*')
                    ->from('view_GetEquipmentParameterSensorMain')
                    ->where(['equipment_id' => $equipment_id])
                    ->indexBy('equipment_parameter_id')
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
                if (isset($group_equipment_parameter[$kind->id])) {
                    $j = 0;
                    foreach ($group_equipment_parameter[$kind->id] as $parameter) {
                        $kind_parameters['params'][$j]['id'] = (int)$parameter['parameter_id'];
                        $kind_parameters['params'][$j]['title'] = $parameter['parameter_title'];
                        $kind_parameters['params'][$j]['units'] = $parameter['units'];
                        $kind_parameters['params'][$j]['units_id'] = $parameter['units_id'];
                        $k = 0;
                        foreach ($parameter['parameter_type_id'] as $parameter_type) {//перебираем конкретный параметр
                            $kind_parameters['params'][$j]['specific'][$k]['id'] = (int)$parameter_type['parameter_type_id'];//id типа параметра
                            $kind_parameters['params'][$j]['specific'][$k]['title'] = $parameter_type['parameter_type_title'];//название параметра
                            $kind_parameters['params'][$j]['specific'][$k]['specificObjectParameterId'] = (int)$parameter_type['equipment_parameter_id'];//id параметра конкретного объекта
                            $kind_parameters['params'][$j]['specific'][$k]['value'] = $parameter_type['value'];

                            switch ($parameter_type['parameter_type_id']) {
                                case 1:
                                    if ($parameter_type['parameter_id'] == 337) {//название АСУТП

                                        $asmtpTitle = $parameter_type['value'] == -1 ? '' : ASMTP::findOne((int)$parameter_type['value'])->title;
                                        $kind_parameters['params'][$j]['specific'][$k]['asmtpTitle'] = $asmtpTitle;
                                    } else if ($parameter_type['parameter_id'] == 338) {//ТИП сенсора

                                        $sensorTypeTitle = $parameter_type['value'] == -1 ? '' : SensorType::findOne((int)$parameter_type['value'])->title;
                                        $kind_parameters['params'][$j]['specific'][$k]['sensorTypeTitle'] = $sensorTypeTitle;
                                    } else if ($parameter_type['parameter_id'] == 274) {// Типовой объект

                                        if ($objectTitle = TypicalObject::findOne($parameter_type['value'])) {
                                            $kind_parameters['params'][$j]['specific'][$k]['objectTitle'] = $objectTitle->title;
                                        }
                                    } else if ($parameter_type['parameter_id'] == 122) {
                                        if ($placeTitle = Place::findOne($parameter_type['value'])) {// Название места
                                            $kind_parameters['params'][$j]['specific'][$k]['placeTitle'] = $placeTitle->title;
                                        } else {
                                            $kind_parameters['params'][$j]['specific'][$k]['placeTitle'] = '';
                                        }
                                    } else if ($parameter_type['parameter_id'] == 523) {
                                        if ($alarm_group_title = GroupAlarm::findOne($parameter_type['value'])) { // Название группы оповещения
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
                                    $kind_parameters['params'][$j]['specific'][$k]['id'] = 5;//id типа параметра
                                    $kind_parameters['params'][$j]['specific'][$k]['title'] = 'Привязка датчика';//название параметра
                                    $kind_parameters['params'][$j]['specific'][$k]['specificObjectParameterId'] = $parameter_type['equipment_parameter_id'];//id параметра кон
                                    if (isset($equipment_parameter_sensor[$parameter_type['equipment_parameter_id']]) && $equipment_parameter_sensor !== false) {
                                        $kind_parameters['params'][$j]['specific'][$k]['sensor_id'] = $equipment_parameter_sensor[$parameter_type['equipment_parameter_id']]['sensor_id'];
                                    } else {
                                        $kind_parameters['params'][$j]['specific'][$k]['sensor_id'] = -1;
                                    }
                                    break;
                            }
                            $k++;
                        }
                        $j++;
                    }
                    ArrayHelper::multisort($kind_parameters['params'], 'title');
                }
                $result[] = $kind_parameters;
            }

            ArrayHelper::multisort($result, 'title');
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SaveEquipment - Метод сохранения сведений об оборудовании с 3D схемы шахты
     * Входные параметры:
     *       equipment: {
     *              id: null,                       - ключ оборудования
     *              title: "",                      - название оборудования
     *              object_id: null,                - ключ типового объекта оборудования
     *              object_title: "",               - название типового объекта оборудования
     *              img_src: "",                    - путь до картинки оборудования
     *              parent_equipment_id: null,      - ключ родительского оборудования
     *              inventory_number: null,         - инвентарный номер оборудования
     *              description: "",                - описание/комментарий оборудования
     *              attachments: [],                - вложения оборудования - паспорта, РЭ и т.д.
     *              place_id: null,                 - место расположения оборудования
     *              place_title: "",                - название места
     *              sections: {                     - секции конвейера
     *                  1: {
     *                      section_id: 1,          - ключ секции оборудования
     *                      xyzStart: {             - координата старта секции оборудования
     *                          x: 1,
     *                          y: 1,
     *                          z: 1
     *                      },
     *                      xyzEnd: {               - координата окончания секции оборудования
     *                          x: 1,
     *                          y: 1,
     *                          z: 1
     *                      },
     *                      scaleStartPivot: {      - вектор направления точки старта секции оборудования
     *                          x: 1,
     *                          y: 1,
     *                          z: 1
     *                      },
     *                      scaleEndPivot: {        - вектор направления окончания секции оборудования
     *                          x: 1,
     *                          y: 1,
     *                          z: 1
     *                      },
     *                      edge_id: null,          - ключ ветви, на которой стоит секция
     *                      place_id: null,         - ключ места, на которой стоит секция оборудования
     *                      place_title: "",        - название места, на которой стоит секция оборудования
     *                  },
     *              },
     *       },
     *
     * @param $data_post
     */
    public static function SaveEquipment($data_post = null): array
    {
        $result = null;
        $log = new LogAmicumFront("SaveEquipment", true);
        try {
            $log->addLog("Начало метода");
            if ($data_post == null && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $log->addData($data_post, '$data_post', __LINE__);

            $post_dec = json_decode($data_post);
            $log->addData($post_dec, '$post_dec', __LINE__);

            if (!property_exists($post_dec, 'equipment'))                                                       // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('Переданы некорректные входные параметры');
            }
            $equipment_front = $post_dec->equipment;

            $equipment = Equipment::findOne(['id' => $equipment_front->id]);
            if (!$equipment and $equipment_front->id != null) {
                $equipment = new Equipment();

            } else if ($equipment_front->id == null) {
                throw new Exception ("Ключ оборудования null, ошибка формирования объекта оборудования");
            }
            $equipment->id = $equipment_front->id;
            $equipment->title = $equipment_front->title;
            $equipment->object_id = $equipment_front->object_id;
            $equipment->parent_equipment_id = $equipment_front->parent_equipment_id;
            $equipment->inventory_number = $equipment_front->inventory_number;
            if (!$equipment->save()) {
                $log->addData($equipment->errors, '$equipment->errors', __LINE__);
                throw new Exception("Ошибка сохранения модели Equipment");
            }

            $log->addLog("Сохранил само оборудование в БД и изменения в нем");

            /** ПРОВЕРКА НА ПОЛНОТУ ПАРАМЕТРОВ ОБОРУДОВАНИЯ*/
            $equipment_params_add = [];
            $equipment_param_hand = [];

            $equipment_params = EquipmentParameter::find()
                ->where(['equipment_id' => $equipment->id])
                ->asArray()
                ->all();
            foreach ($equipment_params as $item) {
                $equipment_param_hand[$item['parameter_id']][$item['parameter_type_id']] = $item;
            }

            $params_hand = [162, 274, 104, 346, 122, 269, 40];
            $params_value = [346, 122, 269];
            $params_measure = [164];

            foreach ($params_hand as $item) {
                if (!isset($equipment_param_hand[$item][1])) {
                    $equipment_params_add[] = array('equipment_id' => $equipment->id, 'parameter_id' => $item, 'equipment_type_id' => 1);
                }
            }
            foreach ($params_value as $item) {
                if (!isset($equipment_param_hand[$item][2])) {
                    $equipment_params_add[] = array('equipment_id' => $equipment->id, 'parameter_id' => $item, 'equipment_type_id' => 2);
                }
            }
            foreach ($params_measure as $item) {
                if (!isset($equipment_param_hand[$item][3])) {
                    $equipment_params_add[] = array('equipment_id' => $equipment->id, 'parameter_id' => $item, 'equipment_type_id' => 3);
                }
            }

            if (!empty($equipment_params_add)) {
                $log->addLog("Начинаю вставку данных в equipment_parameter");
                $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert('equipment_parameter', ['equipment_id', 'parameter_id', 'parameter_type_id'], $equipment_params_add)->execute();
                if (!$insert_result_to_MySQL) {
                    throw new Exception('Ошибка массовой вставки ПАРАМЕТРОВ конкретного оборудования в БД (equipment_parameter) ' . $insert_result_to_MySQL);
                }
            }
            $log->addLog("Проверил и создал недостающие параметры оборудования");

            $equipment_parameter_value_hand = [];
            $equipment_parameter_values = EquipmentBasicController::getEquipmentParameterValue($equipment->id, '*', '*');
            foreach ($equipment_parameter_values as $item) {
                $equipment_parameter_value_hand[$item['parameter_id']][$item['parameter_type_id']] = $item;
            }

            $equipment_parameter_handbook_value_hand = [];
            $equipment_parameter_handbook_values = EquipmentBasicController::getEquipmentParameterHandbookValue($equipment->id);
            foreach ($equipment_parameter_handbook_values as $item) {
                $equipment_parameter_handbook_value_hand[$item['parameter_id']] = $item;
            }

            $equipment_parameter_values_add = [];
            $equipment_parameter_handbook_values_add = [];
            $date_now = \backend\controllers\Assistant::GetDateNow();

            if ($equipment_parameter_handbook_value_hand[274]['value'] != $equipment->object_id) {                      // ТИПОВОЙ ОБЪЕКТ
                $equipment_parameter_handbook_values_add[] = array(
                    'equipment_parameter_id' => $equipment_parameter_handbook_value_hand[274]['equipment_parameter_id'],
                    'date_time' => $date_now,
                    'value' => $equipment->object_id,
                    'status_id' => 1,
                );
            }

            if ($equipment_parameter_handbook_value_hand[104]['value'] != $equipment->inventory_number) {               // ИНВЕНТАРНЫЙ НОМЕР
                $equipment_parameter_handbook_values_add[] = array(
                    'equipment_parameter_id' => $equipment_parameter_handbook_value_hand[104]['equipment_parameter_id'],
                    'date_time' => $date_now,
                    'value' => $equipment->inventory_number,
                    'status_id' => 1,
                );
            }

            if ($equipment_parameter_handbook_value_hand[162]['value'] != $equipment->title) {                          // НАИМЕНОВАНИЕ
                $equipment_parameter_handbook_values_add[] = array(
                    'equipment_parameter_id' => $equipment_parameter_handbook_value_hand[162]['equipment_parameter_id'],
                    'date_time' => $date_now,
                    'value' => $equipment->title,
                    'status_id' => 1,
                );
            }

            if ($equipment_parameter_handbook_value_hand[40]['value'] != $equipment_front->description) {               // Комментарий/описание
                $equipment_parameter_handbook_values_add[] = array(
                    'equipment_parameter_id' => $equipment_parameter_handbook_value_hand[40]['equipment_parameter_id'],
                    'date_time' => $date_now,
                    'value' => $equipment_front->description,
                    'status_id' => 1,
                );
            }

            if ($equipment_parameter_handbook_value_hand[122]['value'] != $equipment_front->place_id) {                 // МЕСТО
                $equipment_parameter_handbook_values_add[] = array(
                    'equipment_parameter_id' => $equipment_parameter_handbook_value_hand[122]['equipment_parameter_id'],
                    'date_time' => $date_now,
                    'value' => $equipment_front->place_id,
                    'status_id' => 1,
                );
            }

            if ($equipment_parameter_value_hand[122][2]['value'] != $equipment_front->place_id) {                       // МЕСТО
                $equipment_parameter_values_add[] = array(
                    'equipment_parameter_id' => $equipment_parameter_value_hand[122][2]['equipment_parameter_id'],
                    'date_time' => $date_now,
                    'value' => $equipment_front->place_id,
                    'status_id' => 1,
                );
            }

            $mine_id = -1;
            $place = Place::findOne(['id' => $equipment_front->place_id]);
            if ($place) {
                $mine_id = $place->mine_id;
            }

            if ($equipment_parameter_handbook_value_hand[346]['value'] != $mine_id) {                                   // ШАХТА
                $equipment_parameter_handbook_values_add[] = array(
                    'equipment_parameter_id' => $equipment_parameter_handbook_value_hand[346]['equipment_parameter_id'],
                    'date_time' => $date_now,
                    'value' => $mine_id,
                    'status_id' => 1,
                );
            }

            if ($equipment_parameter_value_hand[346][2]['value'] != $mine_id) {                                         // ШАХТА
                $equipment_parameter_values_add[] = array(
                    'equipment_parameter_id' => $equipment_parameter_value_hand[346][2]['equipment_parameter_id'],
                    'date_time' => $date_now,
                    'value' => $mine_id,
                    'status_id' => 1,
                );
            }

            if (!empty($equipment_parameter_values_add)) {
                $log->addLog("Начинаю вставку данных в equipment_parameter_value");
                $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert('equipment_parameter_value', ['equipment_parameter_id', 'date_time', 'value', 'status_id'], $equipment_parameter_values_add)->execute();
                if (!$insert_result_to_MySQL) {
                    throw new Exception('Ошибка массовой вставки ЗНАЧЕНИЙ параметров конкретного оборудования в БД (equipment_parameter_value) ' . $insert_result_to_MySQL);
                }
            }

            if (!empty($equipment_parameter_handbook_values_add)) {
                $log->addLog("Начинаю вставку данных в equipment_parameter_handbook_value");
                $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert('equipment_parameter_handbook_value', ['equipment_parameter_id', 'date_time', 'value', 'status_id'], $equipment_parameter_handbook_values_add)->execute();
                if (!$insert_result_to_MySQL) {
                    throw new Exception('Ошибка массовой вставки ЗНАЧЕНИЙ СПРАВЧОНЫХ параметров конкретного оборудования в БД (equipment_parameter_handbook_value) ' . $insert_result_to_MySQL);
                }
            }

            $log->addLog("Закончил проверку на изменение значений параметров и их вставку в БД");

            /******************** СОХРАНЕНИЕ ВЛОЖЕНИЙ ********************/
            $session = Yii::$app->session;
            if (isset($equipment_front->attachments) && !empty($equipment_front->attachments)) {
                foreach ($equipment_front->attachments as $key => $attachment) {

                    if ($attachment->attachment_status == 'new') {

                        $file_path_attachment = Assistant::UploadFile($attachment->attachment_src, $attachment->attachment_title, 'attachment', $attachment->attachment_type);
                        $add_attachment = new Attachment();
                        $add_attachment->path = $file_path_attachment;
                        $add_attachment->title = $attachment->attachment_title;
                        $add_attachment->attachment_type = $attachment->attachment_type;
                        $add_attachment->date = $date_now;
                        $add_attachment->worker_id = $session['worker_id'];
                        $add_attachment->section_title = 'Схема шахты';
                        if (!$add_attachment->save()) {
                            $log->addData($add_attachment->errors, '$add_attachment->errors', __LINE__);
                            throw new Exception('Во время добавления вложения произошла ошибка.');
                        }
                        $add_attachment->refresh();
                        $add_equipment_attachment = new EquipmentAttachment();
                        $add_equipment_attachment->equipment_id = $equipment->id;
                        $add_equipment_attachment->attachment_id = $add_attachment->id;
                        if (!$add_equipment_attachment->save()) {
                            $log->addData($add_equipment_attachment->errors, '$add_equipment_attachment->errors', __LINE__);
                            throw new Exception('Во время добавления связки вложения и нарушения произошла ошибка.');
                        }

                        $post_dec->equipment->attachments->{$key}->attachment_id = $add_attachment->id;
                        $post_dec->equipment->attachments->{$key}->document_attachment_id = $add_equipment_attachment->id;
                        $post_dec->equipment->attachments->{$key}->attachment_status = "";
                        $post_dec->equipment->attachments->{$key}->attachment_src = $file_path_attachment;

                    } elseif ($attachment->attachment_status == 'change') {

                        $file_path_attachment = Assistant::UploadFile($attachment->attachment_src, $attachment->attachment_title, 'attachment', $attachment->attachment_type);
                        $edit_attachment = Attachment::findOne(['id' => $attachment->attachment_id]);
                        $edit_attachment->path = $file_path_attachment;
                        $edit_attachment->date = $date_now;
                        $edit_attachment->title = $attachment->attachment_title;
                        $edit_attachment->attachment_type = $attachment->attachment_type;
                        $edit_attachment->worker_id = $session['worker_id'];
                        $edit_attachment->section_title = 'Схема шахты';
                        if (!$edit_attachment->save()) {
                            $log->addData($edit_attachment->errors, '$add_attachment->errors', __LINE__);
                            throw new Exception('Во время добавления вложения произошла ошибка.');
                        }
                        $edit_attachment->refresh();

                        $post_dec->equipment->attachments->{$key}->attachment_id = $edit_attachment->id;
                        $post_dec->equipment->attachments->{$key}->attachment_status = "";
                        $post_dec->equipment->attachments->{$key}->attachment_src = $file_path_attachment;

                    } elseif ($attachment->attachment_status == 'del') {
                        $del_inj_attachment = EquipmentAttachment::deleteAll(['equipment_id' => $equipment->id, 'attachment_id' => $attachment->attachment_id]);
                        unset($post_dec->equipment->attachments->{$key});
                    }
                }
            }

            /** СОХРАНЯЕМ КОНФИГУРАЦИЮ ИЗ ЮНИТИ */
            $equipment_unity = EquipmentUnity::findOne(['equipment_id' => $equipment->id]);
            if (!$equipment_unity) {
                $equipment_unity = new EquipmentUnity();
            }
            $equipment_unity->equipment_id = $equipment->id;
            $equipment_unity->config_json = json_encode($equipment_front->sections);
            if (!$equipment_unity->save()) {
                $log->addData($equipment_unity->errors, '$equipment_unity->errors', __LINE__);
                throw new Exception('Ошибка сохранения конфигурации оборудования схемы шахты');
            }
            $result = $post_dec->equipment;
            $equipment_cache = new EquipmentCacheController();
            $equipment_cache->initEquipmentMain($mine_id, $equipment->id);
            $equipment_cache->initEquipmentParameterHandbookValue($equipment->id);
            $equipment_cache->initEquipmentParameterValue($equipment->id);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Закончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * AddMain - Метод добавления ключа в таблицу Main
     * @param $table_address
     * @return int
     * @throws Exception
     */
    public static function AddMain($table_address): int
    {
        $main = new Main();
        $main->db_address = "amicum2";
        $main->table_address = $table_address;

        if (!$main->save()) {
            throw new Exception("Ошибка создания ключа главного объекта для таблицы: " . $table_address);
        }

        return $main->id;
    }

    /**
     * GetEquipmentWithUnity - Метод получение сведений об оборудовании с 3D схемы шахты
     * Входные параметры:
     *       equipment: {
     *              id: null,                       - ключ оборудования
     *              title: "",                      - название оборудования
     *              object_id: null,                - ключ типового объекта оборудования
     *              object_title: "",               - название типового объекта оборудования
     *              img_src: "",                    - путь до картинки оборудования
     *              parent_equipment_id: null,      - ключ родительского оборудования
     *              inventory_number: null,         - инвентарный номер оборудования
     *              description: "",                - описание/комментарий оборудования
     *              attachments: [],                - вложения оборудования - паспорта, РЭ и т.д.
     *              place_id: null,                 - место расположения оборудования
     *              place_title: "",                - название места
     *              sections: {                     - секции конвейера
     *                  1: {
     *                      section_id: 1,          - ключ секции оборудования
     *                      xyzStart: {             - координата старта секции оборудования
     *                          x: 1,
     *                          y: 1,
     *                          z: 1
     *                      },
     *                      xyzEnd: {               - координата окончания секции оборудования
     *                          x: 1,
     *                          y: 1,
     *                          z: 1
     *                      },
     *                      scaleStartPivot: {      - вектор направления точки старта секции оборудования
     *                          x: 1,
     *                          y: 1,
     *                          z: 1
     *                      },
     *                      scaleEndPivot: {        - вектор направления окончания секции оборудования
     *                          x: 1,
     *                          y: 1,
     *                          z: 1
     *                      },
     *                      edge_id: null,          - ключ ветви, на которой стоит секция
     *                      place_id: null,         - ключ места, на которой стоит секция оборудования
     *                      place_title: "",        - название места, на которой стоит секция оборудования
     *                  },
     *              },
     *       },
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\SpecificEquipment&method=GetEquipmentWithUnity&subscribe=&data={%22equipment_id%22:355874}
     * @param $data_post
     */
    public static function GetEquipmentWithUnity($data_post = null): array
    {
        $result = null;
        $log = new LogAmicumFront("GetEquipmentWithUnity", true);
        try {
            $log->addLog("Начало метода");
            if ($data_post == null && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $log->addData($data_post, '$data_post', __LINE__);

            $post_dec = json_decode($data_post);
            $log->addData($post_dec, '$post_dec', __LINE__);

            if (!property_exists($post_dec, 'equipment_id')) {
                throw new Exception('Переданы некорректные входные параметры');
            }
            $equipment_id = $post_dec->equipment_id;

            $equipment = Equipment::find()
                ->joinWith('object')
                ->joinWith('equipmentAttachments.attachment')
                ->joinWith('equipmentUnity')
                ->where(['equipment.id' => $equipment_id])
                ->asArray()
                ->one();
            if ($equipment) {
                $result['id'] = $equipment['id'];
                $result['title'] = $equipment['title'];
                $result['parent_equipment_id'] = $equipment['parent_equipment_id'];
                $result['inventory_number'] = $equipment['inventory_number'];
                $result['object_id'] = $equipment['object']['id'];
                $result['object_title'] = $equipment['object']['title'];

                $equipment_parameter_handbook_value_hand = [];
                $equipment_parameter_handbook_values = EquipmentBasicController::getEquipmentParameterHandbookValue($equipment_id);
                foreach ($equipment_parameter_handbook_values as $item) {
                    $equipment_parameter_handbook_value_hand[$item['parameter_id']] = $item;
                }

                if (isset($equipment_parameter_handbook_value_hand[168])) {
                    $result['img_src'] = $equipment_parameter_handbook_value_hand[168]['value'];
                } else {
                    $result['img_src'] = null;
                }

                if (isset($equipment_parameter_handbook_value_hand[40])) {
                    $result['description'] = $equipment_parameter_handbook_value_hand[40]['value'];
                } else {
                    $result['description'] = null;
                }

                if (isset($equipment_parameter_handbook_value_hand[122])) {
                    $result['place_id'] = $equipment_parameter_handbook_value_hand[122]['value'];
                    $place = Place::findOne(['id' => $result['place_id']]);
                    if ($place) {
                        $result['place_title'] = $place->title;
                    } else {
                        $result['place_title'];
                    }
                } else {
                    $result['place_id'] = null;
                }

                if (isset($equipment['equipmentUnity'])) {
                    $result['sections'] = json_decode($equipment['equipmentUnity']['config_json']);
                } else {
                    $result['sections'] = (object)array();
                }

                if (isset($equipment['equipmentAttachments'])) {
                    foreach ($equipment['equipmentAttachments'] as $attach) {
                        $result['attachments'][$attach['id']]['document_attachment_id'] = $attach['id'];
                        $result['attachments'][$attach['id']]['attachment_id'] = $attach['attachment_id'];
                        $result['attachments'][$attach['id']]['attachment_status'] = "";
                        $result['attachments'][$attach['id']]['attachment_src'] = $attach['attachment']['path'];
                        $result['attachments'][$attach['id']]['attachment_title'] = $attach['attachment']['title'];
                        $result['attachments'][$attach['id']]['attachment_type'] = $attach['attachment']['attachment_type'];
                        $result['attachments'][$attach['id']]['attachment_size'] = 0;
                    }
                } else {
                    $result['attachments'] = (object)array();
                }
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Закончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

}
