<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\handbooks;
//ob_start();

use backend\controllers\Assistant;
use Exception;
use frontend\controllers\Assistant as FrontendAssistant;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\AccessCheck;
use frontend\models\Asmtp;
use frontend\models\Company;
use frontend\models\FunctionType;
use frontend\models\KindObject;
use frontend\models\KindParameter;
use frontend\models\Mine;
use frontend\models\ObjectModel;
use frontend\models\ObjectType;
use frontend\models\Parameter;
use frontend\models\ParameterType;
use frontend\models\Place;
use frontend\models\Plast;
use frontend\models\Sensor;
use frontend\models\SensorType;
use frontend\models\TypeObjectFunction;
use frontend\models\TypeObjectParameter;
use frontend\models\TypeObjectParameterFunction;
use frontend\models\TypeObjectParameterHandbookValue;
use frontend\models\TypeObjectParameterSensor;
use frontend\models\TypeObjectParameterValue;
use frontend\models\TypicalObject;
use frontend\models\Unit;
use frontend\models\ViewTypeObjectParameterHandbookValueMaxDateMain;
use Throwable;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;

class HandbookTypicalObjectController extends Controller
{
    // GetKindObject                    - Получение справочника видов типовых объектов
    // SaveKindObject                   - Сохранение нового вида типового объекта
    // DeleteKindObject                 - Удаление вида типового объекта

    // GetObjectType()                  - Получение справочника типов типовых объектов
    // SaveObjectType()                 - Сохранение справочника типов типовых объектов
    // DeleteObjectType()               - Удаление справочника типов типовых объектов

    // getTypicalObjectArray            - Функция построения массива типовых объектов

    // actionDeleteParameter            - Функция удаления параметра типового объекта


    public $generalParameters = array(160, 161, 104, 274, 163, 165, 168, 169);

    public function actionIndex()
    {
        $objectKinds = $this->buildTypicalObjectArray();
        $func_array = (new Query())
            ->select([
                'func.id as id',
                'func.title as title',
                'function_type.title as type',
                'function_type.id as typeId'
            ])
            ->from('func')
            ->leftJoin('function_type', 'function_type.id = func.function_type_id')
            ->orderBy(['title' => SORT_ASC])
            ->all();
        $parameterTypes = ParameterType::find()
            ->select(['title', 'id'])
            ->orderBy(['id' => SORT_ASC])
            ->asArray()->all();

        //получить все виды параметров
        $kinds = KindParameter::find()->all();

        //объявить массив для параметров
        $objectProps = array();
        $i = 0;
        //для каждого вида параметров
        foreach ($kinds as $kind) {
            //сохранить id и название вида
            $objectProps[$i]['id'] = $kind->id;
            $objectProps[$i]['title'] = $kind->title;
            $i++;
        }
        ArrayHelper::multisort($objectProps, ['title'], SORT_ASC);
        //получить все единицы измерения
        $units = (new Query())
            ->select('id, title, short')
            ->from('unit')
            ->orderBy('title ASC')
            ->all();
        $sensorList = Sensor::find()
            ->select(['title', 'id'])
            ->asArray()
            ->all();
        ArrayHelper::multisort($sensorList, ['title'], SORT_ASC);
        $func_obj_arr = array();
        foreach ($objectKinds as $kind) {
            if (isset($kind['object_types'])) {
                foreach ($kind['object_types'] as $type) {
                    $z = 0;
                    if (isset($type['objects'])) {
                        foreach ($type['objects'] as $obj) {
                            $func_obj_arr[$z] = $this->buildTypeObjectFunctionArray($obj['id']);
                        }
                    }
                }
            }
        }
        $functionTypes = FunctionType::find()->all();
        $newFunctionTypes = array();
        $s = 0;
        foreach ($functionTypes as $functionType) {
            $newFunctionTypes[$s]['id'] = $functionType->id;
            $newFunctionTypes[$s]['title'] = $functionType->title;
            $s++;
        }

        $asmtp = Asmtp::find()->asArray()->all();
        $sensorType = SensorType::find()->asArray()->all();
        $plast = Plast::find()->asArray()->all();
        $mine = Mine::find()->asArray()->all();
        $company = Company::find()->asArray()->all();
        $place = Place::find()->asArray()->all();

        return $this->render('index', [
            'objectKinds' => $objectKinds,//массив видов объектов, типов объектов и типовых объектов
            'functions' => $func_array,//массив функций
            'parameterTypes' => $parameterTypes,//типы параметра
            'objectProps' => $objectProps,//массив видов параметров
            'units' => $units,//массив единиц измерения
            'sensorList' => $sensorList,//массив датчиков
//            'func_obj_arr' => $func_obj_arr,//массив функций для каждого объекта
            'functionTypes' => $newFunctionTypes,//массив типов функций
            'asmtp' => $asmtp,//массив АСУТП
            'sensorType' => $sensorType,//массив типов сенсоров,
            'mine' => $mine,//массив шахт
            'plast' => $plast,//массив пластов,
            'company' => $company,//массив компаний
            'placeArray' => $place//массив мест
        ]);
    }

    /*
     * Функция построения массива типовых объектов
     * Входные параметры отсутствуют
     * Выходные параметры:
     * - $objectsKinds (array) – массив видов объектов;
     * - $objectsKinds[i][“id”] (int) – id вида объекта;
     * - $objectsKinds[i][“title”] (string) – наименование вида объектов;
     * - $objectsKinds[i][“img”] (string) – путь до иконки вида объекта;
     * - $objectsKinds[i][“object_types”] (array) – массив типов объектов у вида объектов;
     * - $objectsKinds[i][“object_types”][j][“id”] (int) – id типа объектов;
     * - $objectsKinds[i][“object_types”][j][“title”] (string) — наименование типа объектов;
     * - $objectsKinds[i][“object_types”][j][“objects”] (array) — массив типовых объектов в типе объектов;
     * - $objectsKinds[i][“object_types”][j][“objects”][k][“id”] (int) – id типового объекта;
     * - $objectsKinds[i][“object_types”][j][“objects”][k][“title”] (string) – наименование типового объекта.
     */
    public function buildTypicalObjectArray()
    {

        // получаем список типовых объектов по видам и типам
        $typical_objects = (new Query())
            ->select(
                '
                kind_object.id as kind_object_id,
                kind_object.title as kind_object_title,
                kind_object.kind_object_ico as kind_object_ico,
                object_type.id as object_type_id,
                object_type.title as object_type_title,
                object.id as object_id,
                object.title as object_title,
                object.object_table as object_table
                '
            )
            ->from('kind_object')
            ->leftJoin('object_type', 'object_type.kind_object_id=kind_object.id')
            ->leftJoin('object', 'object.object_type_id=object_type.id')
            ->where('object_type.id!=50')
            ->all();

        // получаем список шаблонов для типовых объектов
        $typical_patterns = ViewTypeObjectParameterHandbookValueMaxDateMain::find()->where(['parameter_id' => 161, 'parameter_type_id' => 1])->asArray()->indexBy('object_id')->all();

        foreach ($typical_objects as $typical_object) {
            $kind_object_id = $typical_object['kind_object_id'];

            $objectsKinds[$kind_object_id]['id'] = $kind_object_id;
            $objectsKinds[$kind_object_id]['title'] = $typical_object['kind_object_title'];
            $objectsKinds[$kind_object_id]['img'] = $typical_object['kind_object_ico'];
            if ($typical_object['object_type_id']) {
                $object_type_id = $typical_object['object_type_id'];
                $objectsKinds[$kind_object_id]['object_types'][$object_type_id]['id'] = $object_type_id;
                $objectsKinds[$kind_object_id]['object_types'][$object_type_id]['title'] = $typical_object['object_type_title'];
                if ($typical_object['object_id']) {
                    $object_id = $typical_object['object_id'];
                    $objectsKinds[$kind_object_id]['object_types'][$object_type_id]['objects'][$object_id]['id'] = $object_id;
                    $objectsKinds[$kind_object_id]['object_types'][$object_type_id]['objects'][$object_id]['title'] = $typical_object['object_title'];
                    $objectsKinds[$kind_object_id]['object_types'][$object_type_id]['objects'][$object_id]['object_table'] = $typical_object['object_table'];
                    if (isset($typical_patterns[$object_id])) {
                        $objectsKinds[$kind_object_id]['object_types'][$object_type_id]['objects'][$object_id]['pattern'] = $typical_patterns[$object_id]['value'];
                    }
                }
            }
        }


        //объявить пустой массив
        $arrayKinds = array();
        $i = 0;
        //для всех видов объектов
        foreach ($objectsKinds as $kind) {
            //сохранить в массив id, название и адрес иконки вида объектов
            $arrayKinds[$i]['id'] = $kind['id'];
            $arrayKinds[$i]['title'] = $kind['title'];
            $arrayKinds[$i]['img'] = $kind['img'];

            //если в виде есть типы объекта
            if (isset($kind['object_types'])) {
                $j = 0;
                //для каждого типа
                foreach ($kind['object_types'] as $type) {
                    //сохранить id и название типа объектов
                    $arrayKinds[$i]['object_types'][$j]['id'] = $type['id'];
                    $arrayKinds[$i]['object_types'][$j]['title'] = $type['title'];
                    //если в типе есть объекты
                    if (isset($type['objects'])) {
                        $k = 0;
                        //для каждого объекта
                        foreach ($type['objects'] as $object) {
                            //сохранить id и название объекта
                            $arrayKinds[$i]['object_types'][$j]['objects'][$k]['id'] = $object['id'];
                            $arrayKinds[$i]['object_types'][$j]['objects'][$k]['title'] = $object['title'];
                            $arrayKinds[$i]['object_types'][$j]['objects'][$k]['object_table'] = $object['object_table'] ? $object['object_table'] : '';

                            if (isset($object['pattern'])) {
                                $arrayKinds[$i]['object_types'][$j]['objects'][$k]['pattern'] = $object['pattern'];
                            }

                            $k++;
                        }
                        ArrayHelper::multisort($arrayKinds[$i]['object_types'][$j]['objects'], 'title', [SORT_ASC]);
                    }
                    $j++;
                }
                ArrayHelper::multisort($arrayKinds[$i]['object_types'], ['title'], [SORT_ASC]);
            }
            $i++;
        }
        //вернуть построенный массив
        return $arrayKinds;
    }

    /*
     * Функция построения массива параметров типовых объектов
     * Входные параметры:
     * - $objectId (int) - id типового объекта, для которого запрашиваются параметры
     * Выходные параметры:
     * - $objectProps (array) – массив групп параметров типового объекта (по сути вкладок);
     * - $objectProps[i][“id”] (int) – id вида параметров;
     * - $objectProps[i][“title”] (string) – наименование вида параметров;
     * - $objectProps[i][“id”][“params”] (array) – массив параметров вида параметров;
     * - $objectProps[i][“id”][“params”][j][“id”] (int) – id параметра;
     * - $objectProps[i][“id”][“params”][j][“title”] (int) – наименование параметра;
     * - $objectProps[i][“id”][“params”][j][“typeId”] (int) – id типа(вычисленный/измеренный/справочный) параметра;
     * - $objectProps[i][“id”][“params”][j][“typeTitle”] (string) – наименование типа параметра;
     * - $objectProps[i][“id”][“params”][j][“units”] (string) – единица измерения;
     * - $objectProps[i][“id”][“params”][j][“typeObjectParameterId”] (int) – id типового параметра объекта;
     * - $objectProps[i][“id”][“params”][j][“value”] (string) – значение параметра.
     */
    public function buildTypeObjectParametersArray(int $objectId)
    {
        //получить все виды параметров
        $kinds = KindParameter::find()->all();
        //объявить массив для параметров
        $objectProps = array();
        $i = 0;
        //для каждого вида параметров
        foreach ($kinds as $kind) {
            //сохранить id и название вида
            $objectProps[$i]['id'] = $kind->id;
            $objectProps[$i]['title'] = $kind->title;
            //если есть параметры
            if ($parameters = $kind->parameters) {
                $j = 0;
                //для каждого параметра
                foreach ($parameters as $parameter) {
                    //если есть типовые параметры переданного объекта
                    if ($typeObjParameters = $parameter->getTypeObjectParameters()->where(['object_id' => $objectId])
                        ->orderBy(['parameter_type_id' => SORT_ASC])->all()) {
                        $objectProps[$i]['params'][$j]['id'] = $parameter->id;
                        $objectProps[$i]['params'][$j]['title'] = $parameter->title;
                        //получить id и название единицы измерения
                        $objectProps[$i]['params'][$j]['units'] = $parameter->unit->short;
                        $objectProps[$i]['params'][$j]['unitsId'] = $parameter->unit_id;
                        $k = 0;
                        //для каждого типового параметра
                        foreach ($typeObjParameters as $typeObjParameter) {
                            $objectProps[$i]['params'][$j]['types'][$k]['id'] = $typeObjParameter->parameter_type_id;
                            $objectProps[$i]['params'][$j]['types'][$k]['title'] =
                                $typeObjParameter->parameterType->title;
                            $objectProps[$i]['params'][$j]['types'][$k]['typeObjectParameterId'] = $typeObjParameter->id;
                            switch ($typeObjParameter->parameter_type_id) {
                                case 1/*Справочный*/ :

                                    $lastVal = TypeObjectParameterHandbookValue::find()
                                        ->where('type_object_parameter_id = ' . $typeObjParameter->id)
                                        ->orderBy(['date_time' => SORT_DESC])->one();
                                    if ($lastVal) {
                                        $objectProps[$i]['params'][$j]['types'][$k]['value'] = $lastVal->value;
                                        if ($parameter->id == 122) {
                                            if ($placeTitle = Place::findOne($lastVal->value)) {
                                                $objectProps[$i]['params'][$j]['types'][$k]['placeTitle'] = $placeTitle->title;
                                            } else {
                                                $objectProps[$i]['params'][$j]['types'][$k]['placeTitle'] = '';
                                            }
                                        } else if ($parameter->id == 337) {
                                            $asmtpTitle = $lastVal->value == '-1' ? '-1' : ASMTP::findOne((int)$lastVal->value)->title;
                                            $objectProps[$i]['params'][$j]['types'][$k]['asmtpTitle'] = $asmtpTitle;
                                        } else if ($parameter->id == 338) {
                                            $sensorTypeTitle = $lastVal->value == '-1' ? '-1' : SensorType::findOne((int)$lastVal->value)->title;
                                            $objectProps[$i]['params'][$j]['types'][$k]['sensorTypeTitle'] = $sensorTypeTitle;
                                        } else if (($parameter->id == 274) && $objectTitle = TypicalObject::findOne(['id' => (int)$lastVal->value])) {
                                            $objectProps[$i]['params'][$j]['types'][$k]['objectTitle'] = $objectTitle->title;
                                        }
                                    }
                                    break;
                                case 2/*Измеряемый*/ :
                                    $lastVal = TypeObjectParameterSensor::find()
                                        ->where('type_object_parameter_id = ' . $typeObjParameter->id)
                                        ->orderBy(['date_time' => SORT_DESC])->one();

                                    if (isset($lastVal->sensor_id)) {
                                        //echo "sensor id is ".$lastVal->sensor_id;
                                        $objectProps[$i]['params'][$j]['types'][$k]['value'] = $lastVal->sensor_id;

                                        if ($parameter->id == 122) {
                                            if ($placeTitle = Place::findOne($lastVal->sensor_id)) {
                                                $objectProps[$i]['params'][$j]['types'][$k]['placeTitle'] = $placeTitle->title;
                                            } else {
                                                $objectProps[$i]['params'][$j]['types'][$k]['placeTitle'] = '';
                                            }
                                        } else if ($parameter->id != 269 && $parameter->id != 122) {
                                            if ($sensorTitle = Sensor::findOne(['id' => $lastVal->sensor_id])) {
                                                $objectProps[$i]['params'][$j]['types'][$k]['sensorTitle'] = $sensorTitle->title;
                                            } else {
                                                $objectProps[$i]['params'][$j]['types'][$k]['sensorTitle'] = '';
                                            }
                                        }
                                    }
                                    break;
                                default:
//                                    $lastVal = TypeObjectParameterValue::find()
//                                        ->where("id = ".$typeObjParameter->id)
//                                        ->orderBy(['date_time'=>SORT_DESC])->one();
//                                    if($lastVal){
//                                        $objectProps[$i]['params'][$j]['types'][$k]["value"] = $lastVal->value;
//                                    }
//                                    break;
                            }

                            $k++;
                        }
                        ArrayHelper::multisort($objectProps[$i]['params'], ['title'], [SORT_ASC]);
                        $j++;
                    }

                }
            }
            $i++;
        }
        ArrayHelper::multisort($objectProps, ['title'], [SORT_ASC]);
        return $objectProps;
    }

    /**
     * actionGetParameters -метод получения параметров
     **/
    public function actionGetParameters()
    {
        $log = new LogAmicumFront("actionGetParameters");
        $parametersArray = array();
        try {
            $log->addLog("Начало выполнения метода");
            $post = Assistant::GetServerMethod();

            if (!isset($post['kindId'])) {
                throw new Exception("Входные данные не переданы");
            }

            $parametersArray = Parameter::find()
                ->where(["kind_parameter_id" => $post['kindId']])
                ->asArray()
                ->orderBy(['title' => SORT_ASC])
                ->all();

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Окончание выполнения метода");

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['params_array' => $parametersArray], $log->getLogAll());;
    }

    /*
     * Функция добавления параметра типовому объекту
     * Входные параметры
     * - $post['objectId'] (int) - идентификатор типового объекта
     * - $post['parameterId'] (int) - идентификатор параметра
     * - $post['parameterTypeId'] (int) - идентификатор типа параметра
     * Выходные параметры: массив параметров для типового объекта $post['objectId'] из buildTypeObjectParametersArray
     */
    public function actionAddParameter()
    {
        $errors = array();
        $objectParameters = null;
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 818)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //если передан id типового объекта
                if (isset($post['objectId'])) {
                    //найти типовой объект
                    $object = TypicalObject::findOne($post['objectId']);
                    //если не найден
                    if (!$object) {
                        //сохранить соответствующую ошибку
                        $errors[] = 'Типового объекта c id = ' . $post['objectId'] . ' не существует';
                    }
                } //если id типового объекта не передан
                else {
                    //сохранить соответствующую ошибку
                    $errors[] = 'id типового объекта не задан';
                }
                //если передан id параметра
                if (isset($post['parameterId'])) {
                    //найти параметр
                    $parameter = Parameter::findOne($post['parameterId']);
                    //если не найден
                    if (!$parameter) {
                        //сохранить соответствующую ошибку
                        $errors[] = 'Параметра c id = ' . $post['parameterId'] . ' не существует';
                    }
                } //если не передан
                else {
                    //сохранить соответствующую ошибку
                    $errors[] = 'id параметра не задан';
                }
                //если передан id типа параметра
                if (isset($post['parameterTypeId'])) {
                    //найти тип параметра
                    $parameterType = ParameterType::findOne($post['parameterTypeId']);
                    //если не найден
                    if (!$parameterType) {
                        //сохранить соответствующую ошибку
                        $errors[] = 'Типа параметра c id = ' . $post['parameterTypeId'] . ' не существует';
                    }
                } //если не передан
                else {
                    //сохранить соответствующую ошибку
                    $errors[] = 'id типа параметра не задан';
                }
                //найти типовой параметр объекта по полученным id
                $typeObjectParameter = TypeObjectParameter::findOne(
                    [
                        'object_id' => $post['objectId'],
                        'parameter_id' => $post['parameterId'],
                        'parameter_type_id' => $post['parameterTypeId'],
                    ]);
                //если не найден
                if (!$typeObjectParameter) {
                    //создать новый
                    $typeObjectParameter = new TypeObjectParameter();
                    //сохранить переданные id
                    $typeObjectParameter->object_id = $post['objectId'];
                    $typeObjectParameter->parameter_id = $post['parameterId'];
                    $typeObjectParameter->parameter_type_id = $post['parameterTypeId'];
                    //если модель сохранилась
                    if ($typeObjectParameter->save()) {
                        //получить массив типовых параметров объекта
                        $objectParameters = $this->buildTypeObjectParametersArray($post['objectId']);
                    } //если не сохраниласт
                    else {
                        //сохранить соответствующую ошибку
                        $errors[] = 'Не удалось сохранить';
                    }
                } //если найден
                else {
                    //сохранить соответствующую ошибку
                    $errors[] = 'Такой параметр объекта уже существует с id ' . $typeObjectParameter->id;
                }
            } else {
                $errors[] = 'Недостаточно прав для совершения данной операции';
            }
        } else {
            $errors[] = 'Время сессии закончилось. Требуется повторный ввод пароля';
            $this->redirect('/');
        }
        //составить результирующий массив как массив полученных массивов
        $result = array('errors' => $errors, 'objectProps' => $objectParameters);
        //вернуть AJAX-запросу данные и ошибки
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //вернуть AJAX-запросу данные и ошибки
        Yii::$app->response->data = $result;

    }

    /*
     * actionDeleteParameter - Функция удаления параметра типового объекта
     * Входные параметры:
     * - $post['typeObjectParameterId'] - идентификатор типового параметра объекта
     * Выходные параметры: массив параметров для типового объекта $post['objectId'] из buildTypeObjectParametersArray
     */
    public function actionDeleteParameter()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionDeleteParameter");

        try {
            $objectParameters = null;
            $session = Yii::$app->session;                                                                              // старт сессии
            $session->open();                                                                                           // открыть сессию
            if (!isset($session['sessionLogin'])) {                                                                      // если в сессии есть логин
                $this->redirect('/');
                throw new Exception("Недостаточно прав, закончилась сессия");
            }

            if (!AccessCheck::checkAccess($session['sessionLogin'], 817)) {                                                //если пользователю разрешен доступ к функции
                throw new Exception("Недостаточно прав для совершения данной операции");
            }

            $post = Yii::$app->request->post(); //получение данных от ajax-запроса
            if (!isset($post['type'])) {
                throw new Exception("Не задан тип удаления");
            }
            if (!isset($post['parameterId'])) {
                throw new Exception("Не задан параметр на удаление");
            }

            if ($post['type'] == 'local') {
                //есди задан id типового параметра объекта
                if (isset($post['typeObjectParameterId'])) {
                    //найти типовой параметр объекта
                    TypeObjectParameter::deleteAll(['id' => $post['typeObjectParameterId']]);
                    TypeObjectParameterHandbookValue::deleteAll(['type_object_parameter_id' => $post['typeObjectParameterId']]);
                    TypeObjectParameterValue::deleteAll(['type_object_parameter_id' => $post['typeObjectParameterId']]);
                    TypeObjectParameterSensor::deleteAll(['type_object_parameter_id' => $post['typeObjectParameterId']]);
                } else {                                                                                                //если не задан
                    throw new Exception("Типовой параметр не задан");
                }
            } else if ($post['type'] == 'global') {                                                                     //если задан тип global
                //найти параметр объекта
                $parametersArray = TypeObjectParameter::findAll(array('parameter_id' => $post['parameterId'], 'object_id' => $post['objectId']));

                if ($parametersArray) {
                    foreach ($parametersArray as $single) {
                        TypeObjectParameterHandbookValue::deleteAll(['type_object_parameter_id' => $single->id]);
                        TypeObjectParameterValue::deleteAll(['type_object_parameter_id' => $single->id]);
                        TypeObjectParameterSensor::deleteAll(['type_object_parameter_id' => $single->id]);

                        if (!$single->delete()) {
                            throw new Exception("Не удалось удалить параметр");
                        }
                    }
                } else {
                    throw new Exception("Нет привязанных параметров");
                }
            }
            $objectParameters = $this->buildTypeObjectParametersArray($post['objectId']);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        //вернуть AJAX-запросу данные и ошибки
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //вернуть AJAX-запросу данные и ошибки
        Yii::$app->response->data = array_merge(['Items' => $result, 'objectProps' => $objectParameters], $log->getLogAll());

    }

    /*
     * Функция добавления типового объекта
     * Входные параметры:
     * - $post['objectTypeId'] (int) - идентификатор типа объекта
     * - $post['title'] (string) - название типового объекта
     * Выходные параметры: массив типовых объектов из buildTypicalObjectArray
     */
    public function actionAddObject()
    {
        $errors = array();
        $objectKinds = null;
        $newObjId = null;
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 69)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //если все данные переданы
                if (isset($post['title']) && $post['title'] != '' && isset($post['objectTypeId']) && $post['objectTypeId'] != '') {
                    //найти тип объекта
                    $objectType = ObjectType::findOne($post['objectTypeId']);
                    //если найден
                    if ($objectType) {
                        //найти объект с полученным названием
                        $object = TypicalObject::findOne(['title' => $post['title']]);
                        //если не найден
                        if (!$object) {
                            //создать новый объект
                            $object = new TypicalObject();
                            //сохранить в него название и ссылку на тип
                            $object->title = (string)$post['title'];
                            $object->object_type_id = (int)$post['objectTypeId'];
                            if (isset($post['object_table']) && $post['object_table'] != '') {
                                $object->object_table = (string)$post['object_table'];
                            }
                            //если объект сохранился
                            if ($object->save()) {
                                $newObjId = $object->id;
                                //перестроить массив видов объектов
                                $objectKinds = $this->buildTypicalObjectArray();
                                foreach ($this->generalParameters as $generalParameter) {
                                    $model = new TypeObjectParameter();
                                    $model->object_id = $object->id;
                                    $model->parameter_id = $generalParameter;
                                    $model->parameter_type_id = 1;
                                    if (!$model->save()) {
                                        $errors[] = 'Не удалось сохранить общий параметр ' . $generalParameter . ' у объекта ' . $object->id;
                                    } else if ($generalParameter == 274) {
                                        $value = new TypeObjectParameterHandbookValue();
                                        $value->type_object_parameter_id = (int)$model->id;
                                        $value->date_time = Assistant::GetDateNow();
                                        $value->value = (string)$object->id;
                                        $value->status_id = 1;
                                        if (!$value->save()) {
                                            $errors[] = 'Не удалось сохранить значение типового объекта ' . $model->id . ' у объекта ' . $object->id;
                                        }
                                    }
                                }
                            } //если не сохранился
                            else {
                                //сохранить соответствующую ошибку
                                $errors[] = 'Ошибка сохранения';
                            }
                        } //если найден
                        else {
                            //сохранить соответствующую ошибку
                            $errors[] = 'Объект с таким названием уже существует с id ' . $object->id;
                        }
                    } //если не найден
                    else {
                        //сохранить соответствующую ошибку
                        $errors[] = 'Типа объектов с id ' . $post['objectTypeId'] . ' не существует';
                    }
                } //если не все
                else {
                    //сохранить соответствующую ошибку
                    $errors[] = 'Данные не переданы';
                }
            } else {
                $errors[] = 'Недостаточно прав для совершения данной операции';
            }
        } else {
            $errors[] = 'Время сессии закончилось. Требуется повторный ввод пароля';
            $this->redirect('/');
        }
        //составить результирующий массив как массив полученных массивов
        $result = array('errors' => $errors, 'objectKinds' => $objectKinds, 'typical_object_id' => $newObjId);
        //вернуть AJAX-запросу данные и ошибки
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //вернуть AJAX-запросу данные и ошибки
        Yii::$app->response->data = $result;
    }

    /*
     * Функция редактирования типовых объектов
     * Входные параметры:
     * - $post['objectId'] (int) - id типового объекта
     * - $post['title'] (string) - новое название типового объекта
     * Выходные параметры: массив типовых объектов из buildTypicalObjectArray
     */
    public function actionEditObject()
    {
        $errors = array();
        $objectKinds = null;
        $extraInfo = array();
        $response = '';
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 70)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //если все данные переданы
                if (isset($post['title']) && isset($post['objectId'])) {
                    //найти объект по id
                    $object = TypicalObject::findOne($post['objectId']);

                    //если найден
                    if ($object) {
                        if ($object->title === (string)$post['title']) {

                            if ((isset($post['table_name']) && $post['table_name'] != '') && $object->object_table != (string)$post['table_name']) {

                                $object->object_table = (string)$post['table_name'];
                                //если объет сохранился
                                if (!$object->save()) {

                                    //обновить массив типовых объектов
                                    //сохранить соответствующую ошибку
                                    $errors[] = 'Ошибка сохранения';
                                } //если не сохранился
                                else {
                                    $response = "Поле 'Таблица' успешно изменено";
                                }
                            } else {
                                $extraInfo[] = 'Данные не изменились';
                            }
                        } else {

                            //найти объект по названию
                            $existingObject = TypicalObject::findOne(['title' => $post['title']]);
                            //если не найден
                            if (!$existingObject) {

                                //сохранить в найденный по id параметр название
                                $object->title = $post['title'];
                                if (isset($post['table_name']) && $post['table_name'] != '') {
                                    $object->object_table = (string)$post['table_name'];
                                }
                                //если объет сохранился
                                if (!$object->save()) {
                                    //сохранить соответствующую ошибку
                                    $errors[] = 'Ошибка сохранения';

                                } //если не сохранился
                                else {
                                    $response = 'Типовой объект успешно отредактирован';
                                }
                            } //если найден объект по названию
                            else {
                                //сохранить соответствующую ошибку
                                $errors[] = 'Объект с таким названием уже существует';
                            }
                        }
                    } //если не найден объект по id
                    else {
                        //сохранить соответствующую ошибку
                        $errors[] = 'Объекта с id ' . $post['objectId'] . ' не существует';
                    }
                } //если не заданы
                else {
                    //сохранить соответствующую ошибку
                    $errors[] = 'Данные не переданы';
                }
            } else {
                $errors[] = 'Недостаточно прав для совершения данной операции';
            }
        } else {
            $errors[] = 'Время сессии закончилось. Требуется повторный ввод пароля';
            $this->redirect('/');
        }
        //обновить массив типовых объектов
        $objectKinds = $this->buildTypicalObjectArray();
        //составить результирующий массив как массив полученных массивов
        $result = array('errors' => $errors, 'objectKinds' => $objectKinds, 'extraInfo' => $extraInfo, 'response' => $response);
        //вернуть AJAX-запросу данные и ошибки
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //вернуть AJAX-запросу данные и ошибки
        Yii::$app->response->data = $result;
    }

    /*
     * Функция удаления типового объекта
     * Входные параметры:
     * - $post['objectId'] (int) - id удаляемого типового объекта
     * Выходные параметры: массив типовых объектов из buildTypicalObjectArray
     */
    public function actionDeleteObject()
    {
        $errors = array();
        $objectKinds = null;
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 71)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //если все данные переданы
                if (isset($post['objectId'])) {
                    $object = TypicalObject::findOne($post['objectId']);
                    if ($object) {
                        ObjectModel::deleteAll('object_id=:object_id', [':object_id' => $object->id]);            //удаляем модели
                        TypeObjectFunction::deleteAll('object_id=:object_id', [':object_id' => $object->id]);            //удаляем функции
                        $type_object_parameters = TypeObjectParameter::findAll(['object_id' => $object->id]);                         //ищем параметры на удаление
                        foreach ($type_object_parameters as $type_object_parameter) {
                            TypeObjectParameterValue::deleteAll('type_object_parameter_id=:type_object_parameter_id', [':type_object_parameter_id' => $type_object_parameter->id]);            //удаляем измеренные или вычесленные значения
                            TypeObjectParameterHandbookValue::deleteAll('type_object_parameter_id=:type_object_parameter_id', [':type_object_parameter_id' => $type_object_parameter->id]);    //удаляем справочные значения
                            TypeObjectParameterSensor::deleteAll('type_object_parameter_id=:type_object_parameter_id', [':type_object_parameter_id' => $type_object_parameter->id]);                       //удаляем привязанные сенсоры к сенсорам
                            TypeObjectParameter::deleteAll('id=:id', [':id' => $type_object_parameter->id]);                                                                    //удаляем сам параметр сенсора
                        }

                        TypicalObject::deleteAll('id=:id', [':id' => $object->id]);


                        $objectKinds = $this->buildTypicalObjectArray();

                    }
                } else {//если не переданы
                    $errors[] = 'Данные не переданы';//сохранить соответствующую ошибку
                }
            } else {
                $errors[] = 'Недостаточно прав для совершения данной операции';
            }
        } else {
            $errors[] = 'Время сессии закончилось. Требуется повторный ввод пароля';
            $this->redirect('/');
        }
        $result = array('errors' => $errors, 'objectKinds' => $objectKinds);//составить результирующий массив как массив полученных массивов
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //вернуть AJAX-запросу данные и ошибки
        Yii::$app->response->data = $result;
    }

    /*
     * Функция перемещения типового объекта в другой тип объекта
     * Входные параметры:
     * - $post['objectId'] (int) - id типового объекта
     * - $post['newObjectTypeId'] (int) - id нового типа объекта
     * Выходные параметры: массив типовых объектов из buildTypicalObjectArray
     */
    public function actionMoveObject()
    {
        $errors = array();
        $objectKinds = null;
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 84)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //если все данные переданы
                if (isset($post['newObjectTypeId']) && isset($post['objectId'])) {
                    //найти тип объектов по id
                    $newObjectType = ObjectType::findOne($post['newObjectTypeId']);
                    //если найден
                    if ($newObjectType) {
                        //найти типовой объект по id
                        $object = TypicalObject::findOne($post['objectId']);
                        //если найден
                        if ($object) {
                            //сохранить новый id типа объекта
                            $object->object_type_id = $post['newObjectTypeId'];
                            //если сохранился
                            if ($object->save()) {
                                //обновить массив типовых объектов
                                $objectKinds = $this->buildTypicalObjectArray();
                            } //если не сохранился
                            else {
                                //сохранить соответствующую ошибку
                                $errors[] = 'Ошибка сохранения';
                            }
                        } //если объект не найден
                        else {
                            //сохранить соответствующую ошибку
                            $errors[] = 'Объекта с id ' . $post['objectId'] . ' не существует';
                        }
                    } //если тип объектов не найден
                    else {
                        //сохранить соответствующую ошибку
                        $errors[] = 'Типа объекта с id ' . $post['newObjectTypeId'] . ' не существует';
                    }
                } //если не переданы
                else {
                    //сохранить соответствующую ошибку
                    $errors[] = 'Данные не переданы';
                }
            } else {
                $errors[] = 'Недостаточно прав для совершения данной операции';
            }
        } else {
            $errors[] = 'Время сессии закончилось. Требуется повторный ввод пароля';
            $this->redirect('/');
        }
        //составить результирующий массив как массив полученных массивов
        $result = array('errors' => $errors, 'objectKinds' => $objectKinds);
        //вернуть AJAX-запросу данные и ошибки
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /*
     * Функция копирования объекта
     * Входные параметры:
     * - $post['objectId'] (int) - id типового объекта
     * - $post['title'] (string) - название нового типового объекта
     * - $post['newObjectTypeId'] (int) - id нового типа объекта
     * Выходные параметры: массив типовых объектов из buildTypicalObjectArray
     */
    public function actionCopyObject()
    {
        $errors = array();
        $objectKinds = null;
        $objectProps = null;
        $newObjectId = null;
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 85)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //если все данные переданы
                if (isset($post['newObjectTypeId'], $post['objectId'], $post['title'])) {
                    //найти тип объектов по id
                    $newObjectType = ObjectType::findOne($post['newObjectTypeId']);
                    //если найден
                    if ($newObjectType) {
                        //найти типовой объект по id
                        $object = TypicalObject::findOne($post['objectId']);
                        //если найден
                        if ($object) {
                            //найти существующий типовой объект по названию
                            $existingObject = TypicalObject::findOne(['title' => $post['title']]);
                            //если не найден
                            if (!$existingObject) {
                                //создать новый объект
                                $newObject = new TypicalObject();
                                //сохранить в него название и id типа
                                $newObject->title = $post['title'];
                                $newObject->object_type_id = $post['newObjectTypeId'];
                                //если сохранился
                                if ($newObject->save()) {
                                    $newObjectId = $newObject->id;
                                    //если к объекту привязаны параметры
                                    if ($typeObjectParameters = $object->typeObjectParameters) {
                                        //для каждого параметра объекта
                                        foreach ($typeObjectParameters as $typeObjectParameter) {
                                            //создать новый типовой параметр для нового объекта
                                            $newTypeObjectParameter = new TypeObjectParameter();
                                            //приявзать id нового объекта, id параметра и типа параметра копируемого объекта
                                            $newTypeObjectParameter->object_id = $newObject->id;
                                            $newTypeObjectParameter->parameter_id = $typeObjectParameter->parameter_id;
                                            $newTypeObjectParameter->parameter_type_id = $typeObjectParameter->parameter_type_id;
                                            //если параметр сохранился
                                            if ($newTypeObjectParameter->save()) {
                                                //определить тип значения параметра,
                                                switch ($typeObjectParameter->parameter_type_id) {
                                                    case 1/*Справочный*/ :
                                                        $lastVal = TypeObjectParameterHandbookValue::find()
                                                            ->where('id = ' . $typeObjectParameter->id)
                                                            ->orderBy(['date_time' => SORT_DESC])->one();
                                                        if ($lastVal) {
                                                            //создать новое значение параметра
                                                            $newValue = new TypeObjectParameterHandbookValue();
                                                            //сохранить новое значение, текущую метку времени, типовой параметр и статус
                                                            $newValue->value = $lastVal->value;
                                                            $newValue->date_time = Assistant::GetDateNow();
                                                            $newValue->type_object_parameter_id = $typeObjectParameter->id;
                                                            $newValue->status_id = $lastVal->status_id;
                                                            //если не сохранилась
                                                            if (!$newValue->save()) {
                                                                //сохранить соответствующую ошибку
                                                                $errors[] = 'Справочное значение ' . $lastVal->value . ' не сохранено';
                                                            }
                                                        }
                                                        break;
                                                    case 3/*Вычисляемый*/ :
                                                        $lastVal = TypeObjectParameterFunction::find()
                                                            ->where('id = ' . $typeObjectParameter->id)
                                                            ->orderBy(['date_time' => SORT_DESC])->one();
                                                        if ($lastVal) {
                                                            //создать новое значение параметра
                                                            $newValue = new TypeObjectParameterFunction();
                                                            //сохранить новое значение, текущую метку времени, типовой параметр и статус
                                                            $newValue->function_id = $lastVal->function_id;
                                                            $newValue->date_time = Assistant::GetDateNow();
                                                            $newValue->type_object_parameter_id = $typeObjectParameter->id;
                                                            //если не сохранилась
                                                            if (!$newValue->save()) {
                                                                //сохранить соответствующую ошибку
                                                                $errors[] = 'Вычисляемое значение (функция) ' . $lastVal->function_id . ' не сохранено';
                                                            }
                                                        }
                                                        break;
                                                    case 2/*Измеряемый*/ :
                                                        $lastVal = TypeObjectParameterSensor::find()
                                                            ->where('id = ' . $typeObjectParameter->id)
                                                            ->orderBy(['date_time' => SORT_DESC])->one();
                                                        if ($lastVal) {
                                                            //создать новое значение параметра
                                                            $newValue = new TypeObjectParameterSensor();
                                                            //сохранить новое значение, текущую метку времени, типовой параметр и статус
                                                            $newValue->sensor_id = $lastVal->sensor_id;
                                                            $newValue->date_time = Assistant::GetDateNow();
                                                            $newValue->type_object_parameter_id = $typeObjectParameter->id;
                                                            //если не сохранилась
                                                            if (!$newValue->save()) {
                                                                //сохранить соответствующую ошибку
                                                                $errors[] = 'Измеряемое значение (датчик) ' . $lastVal->sensor_id . ' не сохранено';
                                                            }
                                                        }
                                                        break;
                                                    //                                            default:
                                                    //                                                $lastVal = TypeObjectParameterValue::find()
                                                    //                                                    ->where("id = ".$typeObjectParameter->id)
                                                    //                                                    ->orderBy(['date_time'=>SORT_DESC])->one();
                                                    //                                                if($lastVal){
                                                    //                                                    //создать новое значение параметра
                                                    //                                                    $newValue = new TypeObjectParameterValue();
                                                    //                                                    //сохранить новое значение, текущую метку времени, типовой параметр и статус
                                                    //                                                    $newValue->value = $lastVal->value;
                                                    //                                                    $newValue->date_time = date("Y-m-d H:i:s");
                                                    //                                                    $newValue->type_object_parameter_id = $typeObjectParameter->id;
                                                    //                                                    //если не сохранилась
                                                    //                                                    if(!$newValue->save()){
                                                    //                                                        //сохранить соответствующую ошибку
                                                    //                                                        $errors[] = "Значение (датчик) ".$lastVal->value." не сохранено";
                                                    //                                                    }
                                                    //                                                }
                                                    //                                                break;
                                                }
                                            } else {
                                                //сохранить соответствующую ошибку
                                                $errors[] = 'Параметр ' . $typeObjectParameter->id . ' не скопирован';
                                            }
                                        }
                                    }
                                } else {
                                    //сохранить соответствующую ошибку
                                    $errors[] = "Объект '" . $post['title'] . "' не создан";
                                }
                            } else {
                                //сохранить соответствующую ошибку
                                $errors[] = "Объект с названием '" . $post['title'] . "' уже существует";
                            }
                        } else {
                            //сохранить соответствующую ошибку
                            $errors[] = "Объект с id '" . $post['objectId'] . "' не найден";
                        }
                    } else {
                        //сохранить соответствующую ошибку
                        $errors[] = "Тип объектов с id '" . $post['newObjectTypeId'] . "' не найден";
                    }
                } else {
                    //сохранить соответствующую ошибку
                    $errors[] = 'Данные не переданы';
                }
                //обновить массив типовых объектов
                $objectKinds = $this->buildTypicalObjectArray();
                $objectProps = $this->buildTypeObjectParametersArray($newObjectId);
            } else {
                $errors[] = 'Недостаточно прав для совершения данной операции';
            }
        } else {
            $errors[] = 'Время сессии закончилось. Требуется повторный ввод пароля';
            $this->redirect('/');
        }
        //составить результирующий массив как массив полученных массивов
        $result = array('errors' => $errors, 'objectKinds' => $objectKinds, 'object_id' => $newObjectId, 'objectProps' => $objectProps);
        //вернуть AJAX-запросу данные и ошибки
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /*
     * Функция отображения параметров типового объекта
     * Входные параметры:
     * - $post['objectId'] (int) – id типового объекта
     * Выходные параметры: массив параметров для типового объекта $post['objectId'] из buildTypeObjectParametersArray
     */
    public function actionShowParameters()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $errors = array();
        $objectParameters = null;
        $objectFunctions = null;
        //если передан id типового объекта
        if (isset($post['objectId'])) {
            //найти типовой объект
            $object = TypicalObject::findOne($post['objectId']);
            //если найден
            if ($object) {
                //получить массив параметров
                $objectParameters = $this->buildTypeObjectParametersArray($post['objectId']);
                $objectFunctions = $this->buildTypeObjectFunctionArray($post['objectId']);
            } //если не найден
            else {
                //сохранить соответствующую ошибку
                $errors[] = 'Не найден типовой объект с id ' . $post['objectId'];
            }
        } //если не переданы
        else {
            //сохранить соответствующую ошибку
            $errors[] = 'Данные не переданы';
        }
        //составить результирующий массив как массив полученных массивов
        $result = array('errors' => $errors, 'objectProps' => $objectParameters, 'objectFunctions' => $objectFunctions);
        //вернуть AJAX-запросу данные и ошибки
        echo json_encode($result);
    }

    /*
     * Функция сохранения всех значений параметров
     * Входные параметры:
     * - $post['objectId'] (int) - id типового объекта
     * - $post['allIds'] (string) – id'шники типовых параметров объектов, разделенные символом ☭
     * - $post['allValues'] (string) – значения типовых параметров объектов, разделенне символом ☭
     * Выходные параметры: массив параметров для типового объекта $post['objectId'] из buildTypeObjectParametersArray
     */
    public function actionSaveParametersValues()
    {
        $errors = array();
        $objectParameters = null;
        $session = Yii::$app->session;
        $session->open();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 83)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['parameter_values_array']) && isset($post['object_id'])) {
                    $parameterValues = $post['parameter_values_array'];
                    $objectId = $post['object_id'];

                    foreach ($parameterValues as $parameter) {
                        if (isset($parameter['parameterSensorStatus'])) {

                            $newValue = new TypeObjectParameterSensor();//создать новое значение параметра
                            $newValue->sensor_id = (int)$parameter['parameterValue'];//сохранить новое значение, текущую метку времени, типовой параметр и статус
                            $newValue->date_time = Assistant::GetDateNow();
                            $newValue->type_object_parameter_id = (int)$parameter['typeObjectParameterId'];
                            if (!$newValue->save()) {//если не сохранилась
                                $errors[] = 'Измеряемое значение ' . $parameter['typeObjectParameterId'] . ' не сохранено. Идентификатор объекта ' . $objectId;//сохранить соответствующую ошибку
                            }

                        } else {
                            $newValue = new TypeObjectParameterHandbookValue();//создать новое значение параметра
                            $newValue->value = (string)$parameter['parameterValue'];//сохранить новое значение, текущую метку времени, типовой параметр и статус
                            $newValue->date_time = Assistant::GetDateNow();
                            $newValue->type_object_parameter_id = $parameter['typeObjectParameterId'];
                            $newValue->status_id = 1;
                            if (!$newValue->save()) {//если не сохранилась
                                $errors[] = 'Справочное значение ' . $parameter['typeObjectParameterId'] . ' не сохранено. Идентификатор объекта ' . $objectId;//сохранить соответствующую ошибку
                            }
                        }
                    }
                    $object = TypicalObject::findOne($objectId);//найти объект
                    if ($object) {//если найден
                        $objectParameters = $this->buildTypeObjectParametersArray($objectId);
                    } else {
                        $errors[] = 'Объект с id ' . $objectId . ' не найден';
                    }
                } else {
                    //сохранить соответствующую ошибку
                    $errors[] = 'Данные не переданы';
                }
            } else {
                $errors[] = 'Недостаточно прав для совершения данной операции';
            }
        } else {
            $errors[] = 'Время сессии закончилось. Требуется повторный ввод пароля';
            $this->redirect('/');
        }
        $result = array('errors' => $errors, 'objectProps' => $objectParameters);//составить результирующий массив как массив полученных массивов
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /*
     * Функция отката значений параметров
     * Входные параметры:
     * - $post['objectId'] (int) - id типового объекта
     * Выходные параметры: массив параметров для типового объекта $post['objectId'] из buildTypeObjectParametersArray
     */
    public function actionRollbackParameters()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $errors = array();
        $objectParameters = null;
        //если передан id типового объекта
        if (isset($post['objectId'])) {
            //найти типовой объект
            $object = TypicalObject::findOne($post['objectId']);
            //если найден
            if ($object) {
                //получить массив параметров
                $objectParameters = $this->buildTypeObjectParametersArray($post['objectId']);
            } //если не найден
            else {
                //сохранить соответствующую ошибку
                $errors[] = 'Не найден типовой объект с id ' . $post['objectId'];
            }
        } //если не переданы
        else {
            //сохранить соответствующую ошибку
            $errors[] = 'Данные не переданы';
        }
        //составить результирующий массив как массив полученных массивов
        $result = array('errors' => $errors, 'objectProps' => $objectParameters);
        //вернуть AJAX-запросу данные и ошибки
        echo json_encode($result);
    }

    /*
     * функция сборки массива функций
     * Входные данные:
     * $objectId (int) - id типового объекта
     * Выходные данные:
     * $objectFunctions (arr) - массив функций и их видов
     * */
    public function buildTypeObjectFunctionArray(int $objectId)
    {
        $object_functions = array();
        if (isset($objectId)) {
            //получить все параметры типового объекта
            $objects = (new Query())
                ->select(
                    [
                        'type_object_function.id id',
                        'func.title functionTitle',
                        'func.id functionId',
                        'func.func_script_name scriptName',
                        'function_type.title functionTypeTitle',
                        'func.function_type_id functionTypeId',
                    ])
                ->from(['type_object_function'])
                ->leftJoin('func', 'type_object_function.func_id = func.id')
                ->leftJoin('function_type', 'function_type.id = func.function_type_id')
                ->where('type_object_function.object_id = ' . $objectId)
                ->orderBy('functionTypeId')
                ->all();
//            echo "<pre>";
//            var_dump($objects);
//            echo "</pre>";
            //объявить массив для функций

            $i = -1;
            $j = 0;

            foreach ($objects as $object) {
                if ($i == -1 || $object_functions[$i]['id'] != $object['functionTypeId']) {
                    $i++;
                    $object_functions[$i]['id'] = $object['functionTypeId'];
                    $object_functions[$i]['title'] = $object['functionTypeTitle'];
                    $j = 0;

                }
                $object_functions[$i]['funcs'][$j]['id'] = $object['id'];
                $object_functions[$i]['funcs'][$j]['title'] = $object['functionTitle'];
                $object_functions[$i]['funcs'][$j]['script_name'] = $object['scriptName'];
                $j++;
                if (count($object_functions[$i]['funcs']) > 0) {
                    ArrayHelper::multisort($object_functions[$i]['funcs'], 'title', SORT_ASC);
                }
            }


            ArrayHelper::multisort($object_functions, 'title', SORT_ASC);
//            echo "<pre>";
//            var_dump($object_functions);
//            echo "</pre>";
        }

        return $object_functions;
    }

    /*
     * функция добавления записи
     * */
    public function actionAddTypeObjectFunction()
    {
        $errors = array();
        $objectFunctions = array();
        $session = Yii::$app->session;
        $session->open();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 96)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                if (isset($post['objectId']) && isset($post['functionId'])) {
                    $typeObjectFunction = TypeObjectFunction::find()->where([
                        'object_id' => $post['objectId'],
                        'func_id' => $post['functionId']
                    ])->one();
                    if (!$typeObjectFunction) {
                        $typeObjectFunction = new TypeObjectFunction();
                        $typeObjectFunction->object_id = $post['objectId'];
                        $typeObjectFunction->func_id = $post['functionId'];
                        if ($typeObjectFunction->save()) {
                            $objectFunctions = $this->buildTypeObjectFunctionArray($post['objectId']);
                        } else {
                            $errors[] = 'Ошибка сохранения';
                        }
                    } else {
                        $errors[] = 'Функция уже была привязана';
                    }
                } else {
                    $errors[] = 'Типа объектов с id ' . $post['objectId'] . ' не существует';
                }
            } else {
                $errors[] = 'Недостаточно прав для совершения данной операции';
            }
        } else {
            $errors[] = 'Время сессии закончилось. Требуется повторный ввод пароля';
            $this->redirect('/');
        }
        $result = array('errors' => $errors, 'objectFunctions' => $objectFunctions);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /*
     * метод удаления функции типового объекта
     * */
    public function actionDeleteTypeObjectFunction()
    {
        $errors = array();
        $objectFunctions = array();
        $session = Yii::$app->session;
        $session->open();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 97)) {                                        //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                if (isset($post['typeObjectFunctionId'])) {
                    $object = TypeObjectFunction::findOne($post['typeObjectFunctionId']);
                    if ($object) {
                        if ($object->delete()) {
                            $objectFunctions = $this->buildTypeObjectFunctionArray($object->object_id);
                        } else {
                            $errors[] = 'Ошибка удаления';
                        }
                    } else {
                        $errors[] = 'Объекта с id ' . $object->object_id . ' не существует';
                    }
                } else {
                    //сохранить соответствующую ошибку
                    $errors[] = 'Данные не переданы';
                }
            } else {
                $errors[] = 'Недостаточно прав для совершения данной операции';
            }
        } else {
            $errors[] = 'Время сессии закончилось. Требуется повторный ввод пароля';
            $this->redirect('/');
        }
        //составить результирующий массив как массив полученных массивов
        $result = array('errors' => $errors, 'objectFunctions' => $objectFunctions);
        //вернуть AJAX-запросу данные и ошибки
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /*
    * Функция привязки параметра Текущее местоположение типовому объекту и записи значения в БД
    * Входные данные:
    * objectId (int) - id типового объекта
    * sensorId (string) - id датчика
    * Выходные данные:
    * objectParameters (array) - массив параметров
    */
    public function actionSaveLocationData()
    {
        $post = Yii::$app->request->post();
        $errors = array();
        $objectParameters = array();
        if (isset($post['objectId'])) {
            //найти типовой объект
            $object = TypicalObject::findOne($post['objectId']);
            //если не найден
            if (!$object) {
                //сохранить соответствующую ошибку
                $errors[] = 'Типового объекта c id = ' . $post['objectId'] . ' не существует';
            }
        } //если id типового объекта не передан
        else {
            //сохранить соответствующую ошибку
            $errors[] = 'id типового объекта не задан';
        }
        $location_parameter_id = 83; // Местоположение (XYZ)
        $measured_parameter_type_id = 2; // Измеряемый
        $typeObjectParameter = TypeObjectParameter::findOne([
            'parameter_id' => $location_parameter_id,
            'object_id' => $post['objectId'],
            'parameter_type_id' => $measured_parameter_type_id]);
        //если не найден
        if (!$typeObjectParameter) {
            //создать новый
            $newTypeObjectParameter = new TypeObjectParameter();
            //сохранить переданные id
            $newTypeObjectParameter->object_id = $post['objectId'];
            $newTypeObjectParameter->parameter_id = $location_parameter_id;
            $newTypeObjectParameter->parameter_type_id = $measured_parameter_type_id;
            //если модель сохранилась
            if ($newTypeObjectParameter->save()) {
                //получить массив типовых параметров объекта
                $lastVal = TypeObjectParameterSensor::find()
                    ->where('type_object_parameter_id = ' . $newTypeObjectParameter->id)
                    ->orderBy(['date_time' => SORT_DESC])->one();
                if ($post['sensorId'] != '') {
                    if ($lastVal) {
                        if ($post['sensorId'] != $lastVal->sensor_id) {
                            //создать новое значение параметра
                            $newValue = new TypeObjectParameterSensor();
                            //сохранить новое значение, текущую метку времени, типовой параметр и статус
                            $newValue->sensor_id = $post['sensorId'];
                            $newValue->date_time = Assistant::GetDateNow();
                            $newValue->type_object_parameter_id = $newTypeObjectParameter->id;
                            //если не сохранилась
                            if (!$newValue->save()) {
                                //сохранить соответствующую ошибку
                                $errors[] = 'Измеряемое значение (датчик) ' . $post['sensorId'] . ' не сохранено';
                            }
                        }
                    } else {
                        //создать новое значение параметра
                        $newValue = new TypeObjectParameterSensor();
                        //сохранить новое значение, текущую метку времени, типовой параметр и статус
                        $newValue->sensor_id = $post['sensorId'];
                        $newValue->date_time = Assistant::GetDateNow();
                        $newValue->type_object_parameter_id = $newTypeObjectParameter->id;
                        //если не сохранилась
                        if (!$newValue->save()) {
                            //сохранить соответствующую ошибку
                            $errors[] = 'Измеряемое значение (датчик) ' . $post['sensorId'] . ' не сохранено';
                        }
                    }
                    $objectParameters = $this->buildTypeObjectParametersArray($post['objectId']);
                }

            } //если не сохраниласт
            else {
                //сохранить соответствующую ошибку
                $errors[] = 'Не удалось сохранить';
            }
        } //если найден
        else {
            $lastVal = TypeObjectParameterSensor::find()
                ->where('type_object_parameter_id = ' . $typeObjectParameter->id)
                ->orderBy(['date_time' => SORT_DESC])->one();
            if ($post['sensorId'] != '') {
                if ($lastVal) {
                    if ($post['sensorId'] != $lastVal->sensor_id) {
                        //создать новое значение параметра
                        $newValue = new TypeObjectParameterSensor();
                        //сохранить новое значение, текущую метку времени, типовой параметр и статус
                        $newValue->sensor_id = $post['sensorId'];
                        $newValue->date_time = Assistant::GetDateNow();
                        $newValue->type_object_parameter_id = $typeObjectParameter->id;
                        //если не сохранилась
                        if (!$newValue->save()) {
                            //сохранить соответствующую ошибку
                            $errors[] = 'Измеряемое значение (датчик) ' . $post['sensorId'] . ' не сохранено';
                        }
                    }
                } else {
                    //создать новое значение параметра
                    $newValue = new TypeObjectParameterSensor();
                    //сохранить новое значение, текущую метку времени, типовой параметр и статус
                    $newValue->sensor_id = $post['sensorId'];
                    $newValue->date_time = Assistant::GetDateNow();
                    $newValue->type_object_parameter_id = $typeObjectParameter->id;
                    //если не сохранилась
                    if (!$newValue->save()) {
                        //сохранить соответствующую ошибку
                        $errors[] = 'Измеряемое значение (датчик) ' . $post['sensorId'] . ' не сохранено';
                    }
                }
                $objectParameters = $this->buildTypeObjectParametersArray($post['objectId']);
            }
        }
        //составить результирующий массив как массив полученных массивов
        $result = array('errors' => $errors, 'objectProps' => $objectParameters);
        //вернуть AJAX-запросу данные и ошибки
        echo json_encode($result);
    }

    /*
     * Функция добавения параметра
     * Входные параметры:
     * - $post['title'] - (string) название нового параметра
     * - $post['unit'] - (int) id единицы измерения нового параметра
     * - $post['kind'] - (int) id вида нового параметра
     * Выходные параметры: результат выполнения метода buildArray в формате json
     */
    public function actionAddNewParameter()
    {
        //найти параметр с полученным названием
        $errors = array();
        $parametersArray = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 643)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                $parameter = Parameter::find()->where(['title' => $post['title']])->one();
                //если параметр не найден
                if (!$parameter) {
                    //создать новый параметр
                    $parameter = new Parameter();
                    //сохранить название параметра
                    $parameter->title = $post['title'];
                    //если передано назвние единицы измерения
                    if ($post['unit_id']) {
                        //найти единицу измерения
                        $unit = Unit::findOne($post['unit_id']);
                        //если найдена
                        if ($unit) {
                            //сохранить единицу измерения
                            $parameter->unit_id = $unit->id;
                        } //если не найдена
                        else {
                            //сообщить об этом
                            $errors[] = 'Такой единицы измерения не существует';
                        }
                    } //если не передано
                    else {
                        //сообщить об этом
                        $errors[] = 'Единица измерения не указана';
                    }
                    //если передан вид параметра
                    if ($post['kind_id']) {
                        //найти вид параметра
                        $kind = KindParameter::findOne($post['kind_id']);
                        //если найден
                        if ($kind) {
                            //сохранить вид параметра
                            $parameter->kind_parameter_id = $kind->id;
                        } //если не найден
                        else {
                            //сообщить об этом
                            $errors[] = 'Такого вида параметров не существует';
                        }
                    } //если не передано
                    else {
                        //сообщить об этом
                        $errors[] = 'Вид параметра не указан';
                    }
                    //если парамтер сохранился
                    if ($parameter->save()) {
                        //получить обновленный массив параметров для конкретного вида параметров
                        $parametersArray = $this->buildArray($post['kind_id']);
                    } //если не сохранилось
                    else {
                        //сообщить об этом
                        $errors[] = 'Ошибка сохранения';
                    }
                } //если параметр найден
                else {
                    //сообщить об этом
                    $errors[] = 'Такой параметр уже существует';
                }
            } else {
                $errors[] = 'Недостаточно прав для совершения данной операции';
            }
        } else {
            $errors[] = 'Время сессии закончилось. Требуется повторный ввод пароля';
            $this->redirect('/');
        }
        $result = array('errors' => $errors, 'params_array' => $parametersArray);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //вернуть AJAX-запросу данные и ошибки
        Yii::$app->response->data = $result;
    }

    /*
     * Функция построения массива параметров
     * Входные параметры отсутствуют.
     * Выходные параметры:
     * - $parameters - (array) массив параметров
     * |--[$i] - (array) - ассоциативный массив свойств $i-го параметра
     *    |--['id'] - (int) - идентификатор $i-го параметра
     *    |--['iterator'] - (int) порядковый номер $i-го параметра
     *    |--['title'] - (string) название $i-го параметра
     *    |--['unit'] - (string) единица измерения $i-го параметра
     *    |--['kind'] - (string) вид $i-го параметра
     */
    public function buildArray(int $kindId)
    {
        $kindParameter = KindParameter::findOne($kindId);
        //найти все параметры
//        $parameters = Parameter::find()->orderBy('title')->all();
        //создать пустой массив для сохранения панраметров
        $parameters_array = array();
        $i = 0;
        //Для всех параметров
        if ($kindParameter !== null && $parameters = $kindParameter->parameters) {
            ArrayHelper::multisort($parameters, 'title', SORT_ASC);
            foreach ($parameters as $parameter) {
                //создать пустой массив для хранения свойств параметра
                $parameters_array[$i] = array();
                //сохранить свойства
                $parameters_array[$i]['id'] = $parameter->id;
                $parameters_array[$i]['iterator'] = $i + 1;
                $parameters_array[$i]['title'] = $parameter->title;
                $parameters_array[$i]['unit'] = $parameter->unit->title;
                $parameters_array[$i]['kind'] = $parameter->kindParameter->title;
                $i++;
            }
        }
//        ArrayHelper::multisort($parameters_array, 'title', SORT_ASC);
        //вернуть параметр
        return $parameters_array;
    }

    /*
     * метод притягивания значений АСУТП
     * */
    public function actionAsmtpParameter()
    {
        $itemParameter = array();
        $parameters = TypeObjectParameter::find()->where(['parameter_id' => 337])->all();
        foreach ($parameters as $parameter) {
            if ($value = $parameter->getTypeObjectParameterHandbookValues()->orderBy(['date_time' => SORT_DESC])->one()) {
                $itemParameter[] = $value;
            }
        }
    }

    //метод сохранения фотографии
    public function actionSaveCommonInfoImage()
    {
        $errors = array();
        $post = Yii::$app->request->post();                                                                             //получение данных от ajax-запроса
        $url = null;                                                                                                    //объявляем переменную для хранения пути изображения для передачи на фронтэнд         //отладочная информация о принимаемых данных
        $props = array();
        $linux_flag = 1;
        if ($_FILES['imageFile']['size'] > 0 && isset($post['image_type']) && $post['image_type'] != '') {
            $file = $_FILES['imageFile'];                                                                               //записываем в переменную полученные данные
            $post = Yii::$app->request->post();
            $image_type = $post['image_type'];                                                                          //записываем в переменную полученные данные
            $object_id = (int)$post['object_id'];                                                                        //записываем в переменную полученные данные
            $upload_dir = 'img/2d_models/typical_objects/equipment/';                                                                       //объявляем и инициируем переменную для хранения пути к папке с изображениями
            $tmp_name = explode(' ', $post['object_name']);
            $result_str = '';
            foreach ($tmp_name as $substr) {
                $result_str = $result_str !== '' ? $result_str . '_' . $substr : $result_str . $substr;
            }
            $uploaded_file = $upload_dir . $result_str . '_' . Assistant::GetDateNow() . '.' . $image_type;
//            print_r($uploaded_file);
            if ($linux_flag === 1) {
                chmod($upload_dir, 0750);
//                chmod($uploaded_file, 0750);
            }
            if (!move_uploaded_file($file['tmp_name'], $uploaded_file))                                                 //если удалось сохранить переданный файл в указанную директорию
            {
                echo 'не удалось сохранить файл';
            } else {
                if ($linux_flag === 1) {
                    chmod($upload_dir, 0750);
                    chmod($uploaded_file, 0750);
                }
                if ($parameter = TypeObjectParameter::findOne(['parameter_id' => 168, 'object_id' => $object_id])) {
                    $model = new TypeObjectParameterHandbookValue();
                    $model->type_object_parameter_id = (int)$parameter->id;
                    $model->date_time = Assistant::GetDateNow();
                    $model->value = $uploaded_file;
                    $model->status_id = 1;
                    if (!$model->save()) {
                        $errors[] = 'Не удалось сохранить параметр - ' . $parameter->id;
                    } else {
                        $url = $uploaded_file;

                    }
                } else {
                    $newParameter = new TypeObjectParameter();
                    $newParameter->object_id = $object_id;
                    $newParameter->parameter_id = 168;
                    $newParameter->parameter_type_id = 1;
                    if (!$newParameter->save()) {
                        $errors[] = 'не удалось сохранить параметр';
                    } else {
                        $newParameterValue = new TypeObjectParameterHandbookValue();
                        $newParameterValue->type_object_parameter_id = $newParameter->id;
                        $newParameterValue->value = $uploaded_file;
                        $newParameterValue->date_time = Assistant::GetDateNow();
                        $newParameterValue->status_id = 1;
                        if (!$newParameterValue->save()) {
                            $errors[] = 'не удалось сохранить значение нового параметра 2D модель';
                        } else {
                            $url = $uploaded_file;
                        }
                    }
                }

                $props = $this->buildTypeObjectParametersArray($object_id);
            }
        }

        $result = array('errors' => $errors, 'url' => $url, 'paramArray' => $props);
        echo json_encode($result);
    }

    //метод сохранения общих сведений
    public function actionSaveCommonInfoValues()
    {
        $errors = array();
        $post = Yii::$app->request->post();
        $common_parameters = $post['info_array'];
        $props = array();
        $object_id = null;
        if (isset($post['info_array']) && isset($post['object_id']) && $post['object_id'] != '') {
            $object_id = (int)$post['object_id'];
            foreach ($common_parameters as $parameter) {
//            var_dump($parameter);
                if ($parameter['typeObjectParameter'] != null) {
                    $model = new TypeObjectParameterHandbookValue();
                    $model->type_object_parameter_id = (int)$parameter['typeObjectParameter'];
                    $model->date_time = Assistant::GetDateNow();
                    $model->value = $parameter['parameterValue'] === '' ? 'empty' : $parameter['parameterValue'];
                    $model->status_id = 1;
                    if (!$model->save()) {
                        $errors[] = 'Не удалось сохранить параметр - ' . $parameter['parameterId'];
                    }
                } else {
                    $existedParameter = TypeObjectParameter::findOne(['parameter_id' => $parameter['parameterId'], 'object_id' => $object_id, 'parameter_type_id' => 1]);
                    if ($existedParameter) {
                        $existedValue = new TypeObjectParameterHandbookValue();
                        $existedValue->type_object_parameter_id = $existedParameter->id;
                        $existedValue->date_time = Assistant::GetDateNow();
                        $existedValue->value = $parameter['parameterValue'];
                        $existedValue->status_id = 1;
                        if (!$existedValue->save()) {
                            $errors[] = 'не удалось сохранить значение существующего параметра';
                        }
                    } else {
                        $typeObjParameter = new TypeObjectParameter();
                        $typeObjParameter->object_id = $object_id;
                        $typeObjParameter->parameter_id = (int)$parameter['parameterId'];
                        $typeObjParameter->parameter_type_id = 1;
                        if (!$typeObjParameter->save()) {
                            $errors[] = 'не удалось сохранить новый параметр';
                        } else {
                            $newValue = new TypeObjectParameterHandbookValue();
                            $newValue->type_object_parameter_id = $typeObjParameter->id;
                            $newValue->date_time = Assistant::GetDateNow();
                            $newValue->value = $parameter['parameterValue'];
                            $newValue->status_id = 1;
                            if (!$newValue->save()) {
                                $errors[] = 'не удалось сохранить новое значение';
                            }
                        }
                    }
                }
            }
            $props = $this->buildTypeObjectParametersArray($object_id);
        } else {
            $errors[] = 'не переданы параметры';
        }
        $result = array('errors' => $errors, 'paramArray' => $props);
        echo json_encode($result);
    }

    /*функия копирует все параметры типового объекта в конкретные*/
    public function actionCopyTypicalObjectParametersToSpecificObjectParameters()
    {
        set_time_limit(0);                                                                                              //отключаем максимальное время выполнения скрипта
        $post = Yii::$app->request->post();                                                                             //Получить данные методом post
        if (isset($post['objectId']) && $post['objectId'] != '') {                                                          //если передан id типового объекта
            $typicalParameters = (new Query())//выбрать типовые параметры типового объекта
            ->select(['parameter_id', 'parameter_type_id'])
                ->from('type_object_parameter')
                ->where('object_id = ' . $post['objectId'])
                ->all();
            $objectTable = (new Query())//выбрать таблицу, соответствующую типовому объекту
            ->select(['table_address'])
                ->from('view_object_table')
                ->where('object_id = ' . $post['objectId'])
                ->one();
            $typicalHandbookParameterValues = (new Query())//Получить все последние значения справочных параметров
            ->select('par.parameter_id,par.parameter_type_id, val.value')
                ->from('type_object_parameter as par')
                ->leftJoin('type_object_parameter_handbook_value as val', 'par.id = val.type_object_parameter_id')
                ->where(
                    '(`type_object_parameter_id`,`date_time`) in 
                    (select `type_object_parameter_id`, max(date_time)
                    from `type_object_parameter_handbook_value`
                    group by `type_object_parameter_id`)'
                )
                ->andWhere('object_id = ' . $post['objectId'])
                ->all();
            $tableName = $objectTable['table_address'];                                                                 //сохранить название таблицы объекта
            //echo $tableName.' ';
            $tableParameterName = $tableName == 'worker_object' ? 'worker_parameter' : $tableName . '_parameter';         //сохранить название таблицы параметров
            //echo $tableParameterName.' ';
            $objectParameters = (new Query())//получить все параметры объектов
            ->select(['obj.id', 'par.parameter_id', 'par.parameter_type_id'])
                ->from($tableName . ' as obj')
                ->leftJoin($tableParameterName . ' as par', 'obj.id = par.' . $tableName . '_id')
                ->orderBy($tableName . '_id')
                ->all();
            $objectParametersArray = array();                                                                           //массив для сгруппированных по объектам параметров
            $i = 0;
            /*echo "<pre>";
            var_dump($objectParameters);
            echo "</pre>";*/
            $lastObjId = -1;                                                                                            //последний рассмотренный объект изначально отсутствует
            foreach ($objectParameters as $objPar) {                                                                      //для каждого параметра объекта
                if ($lastObjId != $objPar['id']) {                                                            //если рассматриваемый объект не совпадает с последним рассмотренным
                    $lastObjId = $objPar['id'];                                                             //переопределить его
                    $i = 0;                                                                                               //и обнулить итератор
                    $objectParametersArray[$lastObjId] = array();
                }
                if ($objPar['parameter_id']) {
                    $item['parameter_id'] = $objPar['parameter_id'];                                                        //создать объект с параметром и его типом
                    $item['parameter_type_id'] = $objPar['parameter_type_id'];
                    $objectParametersArray[$lastObjId][$i] = $item;                                                         //сохранить параметр по объекту
                    $i++;
                }
            }
            /*echo "<pre>";
            var_dump($objectParametersArray);
            echo "</pre>";*/
            $insertPar = 'insert into ' . $tableParameterName . ' (' . $tableName . '_id,parameter_id,parameter_type_id) values ';
            $values = '';
            foreach ($objectParametersArray as $objId => $objParameters) {                                                 //для каждого конкретного объекта получить массив его параметров
                foreach ($typicalParameters as $parameter) {                                                             //для каждого типового параметра
                    if (!$this->isObjectInArray($parameter, $objParameters)) {                                             //если типовой параметр не сохранен для объекта
                        $values .= '(' . $objId . ',' . $parameter['parameter_id'] . ',' . $parameter['parameter_type_id'] . '),';//добавить параметр в запрос на сохранение
                    }
                }
            }
            if ($values !== '') {
                $insertPar .= $values;
                //echo $insertPar."______________\n";

                $insertPar = substr($insertPar, 0, -1);                                                                       //избавиться от последней запятой в запросе
                $res = Yii::$app->db_amicum2->createCommand($insertPar)->execute();                                                 //выполнить команду на вставку
            } else {
                $res = null;
            }
            //echo $insertPar;
            if ($res) {                                                                                                   //если строки были вставлены
                $handbookObjectParameters = (new Query())//получить записи о справочных параметрах объект
                ->select('id, parameter_id')
                    ->from($tableParameterName)
                    ->where('parameter_type_id = 1')
                    ->all();
                $insertVal = 'insert into ' . $tableParameterName . '_handbook_value ('
                    . $tableParameterName . '_id,date_time,value,status_id) values ';
                $hvals = '';
                foreach ($handbookObjectParameters as $par) {                                                           //для каждого справочного параметра объекта
                    foreach ($typicalHandbookParameterValues as $val) {                                                 //для каждого справочного значения типового объекта
                        if ($par['parameter_id'] == $val['parameter_id']) {                                               //если параметр объекта совпал с параметром справочного значения
                            $hvals .= '(' . $par['id'] . ",'" . Assistant::GetDateNow() . "','" . $val['value'] . "',1),";          //добавить значение на вставку
                        }
                    }
                }
                if ($hvals !== '') {
                    $insertVal .= $hvals;
                    $insertVal = substr($insertVal, 0, -1);
                    if (Yii::$app->db_amicum2->createCommand($insertVal)->execute()) {
                        Yii::$app->response->format = Response::FORMAT_JSON;
                        Yii::$app->response->data = 'Типовые параметры успешно скопированы';
                    } else {
                        Yii::$app->response->format = Response::FORMAT_JSON;
                        Yii::$app->response->data = 'Не удалось скопировать типовые параметры';
                    }
                } else {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    Yii::$app->response->data = 'Нет параметров справочного типа';
                }

            } else {
                Yii::$app->response->format = Response::FORMAT_JSON;
                Yii::$app->response->data = 'Нет параметров для копирования';
            }
        }
    }

    /*Функция проверяет, есть ли в массиве типовых параметров параметр типового объекта*/
    public function isObjectInArray($object, $array)
    {
        foreach ($array as $item) {
            if ($item['parameter_id'] == $object['parameter_id'] &&
                $item['parameter_type_id'] == $object['parameter_type_id']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Метод загрузки 2Д изображения на сервер
     * разработал Якимов М.Н.
     */
    public function actionUploadFile()
    {
        $url = null;
        $file_path = null;
        $object_id = null;
        $upload_dir = 'img/2d_models/typical_objects/equipment/';
        $status = 1;
        $warnings = array();
        $type_obj_parameters = array();
        $errors = array();
        $result = array();

        try {
            $warnings[] = 'actionUploadFile. Начал выполнять метод';

            $post = Assistant::GetServerMethod();
            if (isset($_FILES['file']) && isset($post['title']) && $post['title'] != '' && isset($post['type'])          //Проверяем принимаемые с Фронта, параметры
                && $post['type'] != '' && isset($post['object_id']) && $post['object_id'] != '') {
                $file = $_FILES['file'];
                $object_title = $post['title'];
                $image_type = $post['type'];
                $object_id = $post['object_id'];
            } else {
                throw new Exception('actionUploadFile. Не все параметры переданы');
            }

            $file_path = Assistant::UploadPicture($file, $upload_dir, $object_title, $image_type);                   //Вызываем метод сохранения изображеня в папку
            if ($file_path == -1) {
                throw new Exception('actionUploadFile. Не удалось сохранить файл');
            }
            $warnings[] = 'actionUploadFile. Сохранил файл на диск сервера';

            $type_obj_picture_parameter = TypeObjectParameter::findOne(['object_id' => $object_id, 'parameter_id' => 168, 'parameter_type_id' => 1]); //Поиск параметра в базе
            if (!$type_obj_picture_parameter) {
                $type_obj_picture_parameter = new TypeObjectParameter();
                $type_obj_picture_parameter->object_id = $object_id;
                $type_obj_picture_parameter->parameter_id = 168;
                $type_obj_picture_parameter->parameter_type_id = 1;
                if ($type_obj_picture_parameter->save()) {
                    $type_obj_picture_parameter->refresh();
                } else {
                    $errors[] = $type_obj_picture_parameter->errors;
                    throw new Exception('actionUploadFile. Не удалось создать параметр URL для типового объекта');
                }
            }

            $type_obj_handbook_value = new TypeObjectParameterHandbookValue();
            $type_obj_handbook_value->type_object_parameter_id = $type_obj_picture_parameter->id;
            $type_obj_handbook_value->date_time = Assistant::GetDateNow();
            $type_obj_handbook_value->value = $file_path;
            $type_obj_handbook_value->status_id = 1;
            if (!$type_obj_handbook_value->save()) {
                $errors[] = $type_obj_handbook_value->errors;
                throw new Exception('actionUploadFile. Не удалось сохранить значение картинки в справочных значениях TypeObjectParameterHandbookValue');
            }

            $type_obj_parameters = $object_id === null ? array() : $this->buildTypeObjectParametersArray($object_id);

        } catch (Throwable $ex) {
            $errors[] = 'actionUploadFile. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $warnings[] = 'actionUploadFile. Закончил выполнять метод';
        $result_main = array('Items' => $result,
            'url' => $file_path,
            'parameters' => $type_obj_parameters,
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод загрузки 3Д изображения на сервер
     * разработал Якимов М.Н.
     */
    public function actionUploadModel()
    {
        $url = null;
        $file_path = null;
        $object_id = null;
        $upload_dir = 'img/3d_models/equipment/';
        $status = 1;
        $warnings = array();
        $type_obj_parameters = array();
        $errors = array();
        $result = array();

        $microtime_start = microtime(true);
        try {
            $warnings[] = 'actionUploadModel. Начал выполнять метод';

            $post = Assistant::GetServerMethod();
            if (isset($_FILES['file']) && isset($post['title']) && $post['title'] != '' && isset($post['type'])          //Проверяем принимаемые с Фронта, параметры
                && $post['type'] != '' && isset($post['object_id']) && $post['object_id'] != '') {
                $file = $_FILES['file'];
                $object_title = $post['title'];
                $image_type = $post['type'];
                $object_id = $post['object_id'];
            } else {
                throw new Exception('actionUploadModel. Не все параметры переданы');
            }

            $file_path = Assistant::UploadPicture($file, $upload_dir, $object_title, $image_type);                   //Вызываем метод сохранения изображеня в папку
            if ($file_path == -1) {
                throw new Exception('actionUploadModel. Не удалось сохранить файл');
            }
            $warnings[] = 'actionUploadModel. Сохранил файл на диск сервера';

            $type_obj_picture_parameter = TypeObjectParameter::findOne(['object_id' => $object_id, 'parameter_id' => 169, 'parameter_type_id' => 1]); //Поиск параметра в базе
            if (!$type_obj_picture_parameter) {
                $type_obj_picture_parameter = new TypeObjectParameter();
                $type_obj_picture_parameter->object_id = $object_id;
                $type_obj_picture_parameter->parameter_id = 169;
                $type_obj_picture_parameter->parameter_type_id = 1;
                if ($type_obj_picture_parameter->save()) {
                    $type_obj_picture_parameter->refresh();
                } else {
                    $errors[] = $type_obj_picture_parameter->errors;
                    throw new Exception('actionUploadModel. Не удалось создать параметр URL для типового объекта');
                }
            }

            $type_obj_handbook_value = new TypeObjectParameterHandbookValue();
            $type_obj_handbook_value->type_object_parameter_id = $type_obj_picture_parameter->id;
            $type_obj_handbook_value->date_time = Assistant::GetDateNow();
            $type_obj_handbook_value->value = $file_path;
            $type_obj_handbook_value->status_id = 1;
            if (!$type_obj_handbook_value->save()) {
                $type_obj_handbook_value->errors;
                throw new Exception('actionUploadModel. Не удалось сохранить значение картинки в справочных значениях TypeObjectParameterHandbookValue');
            }

            $type_obj_parameters = $object_id === null ? array() : $this->buildTypeObjectParametersArray($object_id);

        } catch (Throwable $ex) {
            $errors[] = 'actionUploadModel. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $warnings[] = 'actionUploadModel. Закончил выполнять метод';
        $result_main = array('Items' => $result,
            'url' => $file_path,
            'parameters' => $type_obj_parameters,
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

//    /**
//     * метод получения Типов объекта
//     * Created by: Фидченко М.В. on 06.12.2018 11:40
//     */
//    public static function GetObjectType()
//    {
//        $errors = array();
//        $ObjectsTypes = (new Query())
//            ->select([
//                'id',
//                'title'
//            ])
//            ->from('object_type')
//            ->all();
//        if ($ObjectsTypes) {
//            $result = array('errors' => $errors, 'objects_types' => $ObjectsTypes);
//            Yii::$app->response->format = Response::FORMAT_JSON;
//            Yii::$app->response->data = $result;
//        } else {
//            $errors[] = 'Нет данных в БД';
//            $result = array('errors' => $errors, 'objects_types' => $ObjectsTypes);
//            Yii::$app->response->format = Response::FORMAT_JSON;
//            Yii::$app->response->data = $result;
//        }
//    }

    /**
     * Метод GetKindObject() - Получение справочника видов типовых объектов
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,                                                    // идентификатор вида типового объекта
     *      "title":"Оборудование",                                        // наименование вида типового объекта
     *      "kind_object_type":"Оборудование",                            // вид типа объекта (Место/прочее)
     *      "kind_object_ico":"img/typical_objects/1.svg",                // путь до иконки
     * ]
     * warnings:{}                                                      // массив предупреждений
     * errors:{}                                                        // массив ошибок
     * status:1                                                         // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookTypicalObject&method=GetKindObject&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 11:38
     */
    public static function GetKindObject()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetKindObject';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $kind_object_data = KindObject::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($kind_object_data)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник видов типовых объектов пуст';
            } else {
                $result = $kind_object_data;
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод SaveKindObject() - Сохранение нового вида типового объекта
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "kind_object":
     *  {
     *      "kind_object_id":-1,                                            // идентификатор вида типового объекта
     *      "kind_object_type":"Оборудование"                                // вид типа объекта (Место/прочее)
     *      "title":"Оборудование"                                            // наименование вида типового объекта
     *      "img_src":"blob"                                                // путь до картники/blob
     *      "img_title":"Наименование оборудования"                            // наименование файла
     *      "img_flag":"new"                                                // флаг ("new" = новый файл на сохранение)
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "kind_object_id":11,                                            // идентификатор сохранённой причины проверки знаний
     *      "kind_object_type":"Оборудование"                                // сохранённый вид типа объекта (Место/прочее)
     *      "title":"Оборудование"                                            // сохранённое наименование вида типового объекта
     *      "img_src":"blob"                                                // сохранённый путь до картники/blob
     *      "img_title":"Наименование оборудования"                            // сохранённое наименование файла
     *      "img_flag":"new"                                                // флаг ("new" = новый файл на сохранение)
     * }
     * warnings:{}                                                          // массив предупреждений
     * errors:{}                                                            // массив ошибок
     * status:1                                                             // статус выполнения метода
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 11:48
     */
    public static function SaveKindObject($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveKindObject';
        $chat_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_object'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $kind_object_id = $post_dec->kind_object->kind_object_id;
            $kind_object_type = $post_dec->kind_object->kind_object_type;
            $title = $post_dec->kind_object->title;
            $img_src = $post_dec->kind_object->img_src;
            $img_name = $post_dec->kind_object->img_title;
            $img_flag = $post_dec->kind_object->img_flag;
            $kind_object = KindObject::findOne(['id' => $kind_object_id]);
            if (empty($kind_object)) {
                $kind_object = new KindObject();
            }
            $kind_object->title = $title;
            $kind_object->kind_object_type = $kind_object_type;
            if ($img_flag == 'new') {
                $file_path = FrontendAssistant::UploadFile($img_src, $img_name, 'typical_objects');
                $kind_object->kind_object_ico = $file_path;
            }
            if ($kind_object->save()) {
                $kind_object->refresh();
                $chat_type_data['kind_object_id'] = $kind_object->id;
                $chat_type_data['kind_object_type'] = $kind_object->kind_object_type;
                $chat_type_data['title'] = $kind_object->title;
                $chat_type_data['img_src'] = $kind_object->kind_object_ico;
                $chat_type_data['img_title'] = $img_name;
            } else {
                $errors[] = $kind_object->errors;
                throw new Exception($method_name . '. Ошибка при сохранении нового вида несчастного случая');
            }
            unset($kind_object);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $chat_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteKindObject() - Удаление вида типового объекта
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "kind_object_id": 11             // идентификатор удаляемого вида типового объекта
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookTypicalObject&method=DeleteKindObject&subscribe=&data={"kind_object_id":11}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 11:49
     */
    public static function DeleteKindObject($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteKindObject';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_object_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $kind_object_id = $post_dec->kind_object_id;
            $del_kind_object = KindObject::deleteAll(['id' => $kind_object_id]);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    // GetObjectType()      - Получение справочника типов типовых объектов
    // SaveObjectType()     - Сохранение справочника типов типовых объектов
    // DeleteObjectType()   - Удаление справочника типов типовых объектов

    /**
     * Метод GetObjectType() - Получение справочника типов типовых объектов
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                            // ключ типового объекта
     *      "title":"ACTION",                   // название типового объекта
     *      "kind_object_id":"-1",              // ключ вида типового объекта
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookTypicalObject&method=GetObjectType&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetObjectType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetObjectType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_object_type = ObjectType::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_object_type)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник типов типовых объектов пуст';
            } else {
                $result = $handbook_object_type;
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SaveObjectType() - Сохранение справочника типов типовых объектов
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "object_type":
     *  {
     *      "object_type_id":-1,                // ключ типового объекта
     *      "title":"ACTION",                   // название типового объекта
     *      "kind_object_id":"-1",              // ключ вида типового объекта
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "object_type_id":-1,                // ключ типового объекта
     *      "title":"ACTION",                   // название типового объекта
     *      "kind_object_id":"-1",              // ключ вида типового объекта
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookTypicalObject&method=SaveObjectType&subscribe=&data={"object_type":{"object_type_id":-1,"title":"ACTION","kind_object_id":"-1"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveObjectType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveObjectType';
        $handbook_object_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'object_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_object_type_id = $post_dec->object_type->object_type_id;
            $title = $post_dec->object_type->title;
            $kind_object_id = $post_dec->object_type->kind_object_id;
            $new_handbook_object_type_id = ObjectType::findOne(['id' => $handbook_object_type_id]);
            if (empty($new_handbook_object_type_id)) {
                $new_handbook_object_type_id = new ObjectType();
            }
            $new_handbook_object_type_id->title = $title;
            $new_handbook_object_type_id->kind_object_id = $kind_object_id;
            if ($new_handbook_object_type_id->save()) {
                $new_handbook_object_type_id->refresh();
                $handbook_object_type_data['object_type_id'] = $new_handbook_object_type_id->id;
                $handbook_object_type_data['title'] = $new_handbook_object_type_id->title;
                $handbook_object_type_data['kind_object_id'] = $new_handbook_object_type_id->kind_object_id;
            } else {
                $errors[] = $new_handbook_object_type_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника типов типовых объектов');
            }
            unset($new_handbook_object_type_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_object_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteObjectType() - Удаление справочника типов типовых объектов
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "object_type_id": 98             // идентификатор справочника типов типовых объектов
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookTypicalObject&method=DeleteObjectType&subscribe=&data={"object_type_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteObjectType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteObjectType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'object_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_object_type_id = $post_dec->object_type_id;
            $del_handbook_object_type = ObjectType::deleteAll(['id' => $handbook_object_type_id]);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /////////////////////////////////////////////////

    /**
     * Метод GetTypicalObjects() - Получение справочника типовых объектов
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                            // ключ типового объекта
     *      "title":"ACTION",                   // название типового объекта
     *      "object_type_id":"-1",              // ключ типа типового объекта
     *      "object_table":"-1",              // таблица для создания конкретных объектов типового объекта
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookTypicalObject&method=GetTypicalObjects&subscribe=&data={}
     *
     * @author Курбанов И. С. (скопипастил и  отредактировал методы Якимова М. Н.)
     * Created date: on 08.06.2020 16:10
     */
    public static function GetTypicalObjects()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = (object)array();
        $method_name = 'GetTypicalObjects';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_objects = (new Query())
                ->select('*')
                ->from('object')
                ->indexBy('id')
                ->all();
            if (!empty($handbook_objects)) {
                $result = $handbook_objects;
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /*
     * getTypicalObjectArray - Функция построения массива типовых объектов
     * Входные параметры отсутствуют
     * Выходные параметры:
     * - $objectsKinds (array) – массив видов объектов;
     * - $objectsKinds[i][“id”] (int) – id вида объекта;
     * - $objectsKinds[i][“title”] (string) – наименование вида объектов;
     * - $objectsKinds[i][“img”] (string) – путь до иконки вида объекта;
     * - $objectsKinds[i][“object_types”] (array) – массив типов объектов у вида объектов;
     * - $objectsKinds[i][“object_types”][j][“id”] (int) – id типа объектов;
     * - $objectsKinds[i][“object_types”][j][“title”] (string) — наименование типа объектов;
     * - $objectsKinds[i][“object_types”][j][“objects”] (array) — массив типовых объектов в типе объектов;
     * - $objectsKinds[i][“object_types”][j][“objects”][k][“id”] (int) – id типового объекта;
     * - $objectsKinds[i][“object_types”][j][“objects”][k][“title”] (string) – наименование типового объекта.
     */
    public static function getTypicalObjectArray($data_post = null)
    {
        $objectsKinds = [];
        $objectInfo = [];
        $log = new LogAmicumFront("getTypicalObjectArray");
        try {
            // получаем список типовых объектов по видам и типам
            $typical_objects = (new Query())
                ->select(
                    '
                kind_object.id as kind_object_id,
                kind_object.title as kind_object_title,
                kind_object.kind_object_ico as kind_object_ico,
                object_type.id as object_type_id,
                object_type.title as object_type_title,
                object.id as object_id,
                object.title as object_title,
                object.object_table as object_table
                '
                )
                ->from('object')
                ->innerJoin('object_type', 'object.object_type_id=object_type.id')
                ->innerJoin('kind_object', 'object_type.kind_object_id=kind_object.id')
                ->all();

            // получаем список шаблонов для типовых объектов

            foreach ($typical_objects as $typical_object) {
                $kind_object_id = $typical_object['kind_object_id'];

                $objectsKinds[$kind_object_id]['id'] = $kind_object_id;
                $objectsKinds[$kind_object_id]['title'] = $typical_object['kind_object_title'];
                $objectsKinds[$kind_object_id]['img'] = $typical_object['kind_object_ico'];
                if ($typical_object['object_type_id']) {
                    $object_type_id = $typical_object['object_type_id'];
                    $objectsKinds[$kind_object_id]['object_types'][$object_type_id]['id'] = $object_type_id;
                    $objectsKinds[$kind_object_id]['object_types'][$object_type_id]['title'] = $typical_object['object_type_title'];
                    if ($typical_object['object_id']) {
                        $object_id = $typical_object['object_id'];
                        $objectsKinds[$kind_object_id]['object_types'][$object_type_id]['objects'][$object_id]['id'] = $object_id;
                        $objectsKinds[$kind_object_id]['object_types'][$object_type_id]['objects'][$object_id]['title'] = $typical_object['object_title'];
                        $objectsKinds[$kind_object_id]['object_types'][$object_type_id]['objects'][$object_id]['object_table'] = $typical_object['object_table'];
                        $objectInfo[$object_id]['object_id'] = $object_id;
                        $objectInfo[$object_id]['object_type_id'] = $object_type_id;
                        $objectInfo[$object_id]['kind_object_id'] = $kind_object_id;
                    }
                }
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $objectsKinds, 'objectInfo' => $objectInfo], $log->getLogAll());
    }
}
