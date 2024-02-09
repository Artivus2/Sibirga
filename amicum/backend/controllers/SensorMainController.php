<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace backend\controllers;

use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\cachemanagers\ServiceCache;
use frontend\models\Sensor;
use frontend\models\SensorParameter;
use frontend\models\SensorParameterHandbookValue;
use frontend\models\TypicalObject;
use yii\db\Exception;
use yii\db\Query;

class SensorMainController extends \yii\web\Controller
{

    // initSensorInCache                -   Метод инициализации конкретного сенсора в кеше.
    // getSensorStaticLastMine          -   Получение значения последний шахты для стационарных сенсоров (узлов связи, блоков питания и т.д.).
    // getSensorUniversalLastMine       -   Получение значения последний шахты для Любых сенсоров
    // buildParameterKey                -   создать ключ параметра сенсора
    // buildSensorMineKey               -   создать ключ сенсора
    // GetOrSetSensorParameter          -   Метод возвращает значение конкретного параметра сенсора по заданным параметрам, если нет то создает в БД
    // IsChangeSensorParameterValue     -   метод проверки необходимости записи измеренного или полученного значения в БД
    // getListOpcParameters             -   метод получения списка OPC с их параметрами для выпадашки в конкретных объектах
    // AddMoveSensorMineInitDB          -   Метод переноса сенсора между кешами SensorMine с инициализацией базовых сведений из БД
    // moveSensorMineInitCache          -   Метод переноса сенсора между кешами SensorMine, без инициализации базовых сведений из БД сенсора
    // CancelByEditForSensor      -   Метод удаляет последнее событие у группы сенсоров по парамтрам - 83, 122, 269, 346 (местонахождение, шахта, координаты, выработка)

    public static $sensor_mine_cache_key = 'SeMi';
    public static $sensor_parameter_cache_key = 'SePa';
    public static $sensor_binding_key = 'SenObj';
    public static $sen_par_sen_key = 'SeTags';

    const PARAMETER_TYPE = 1;
    const PARAMETER_XYZ = 83;
    const PARAMETER_PLACE = 122;
    const PARAMETER_EDGE = 269;
    const PARAMETER_MINE = 346;

    /**
     * getSensorStaticLastMine -Получение значения последний шахты для стационарных сенсоров (узлов связи, блоков питания и т.д.).
     * Сначала ищется в кеше, если в кеше, нет значения, тоберется из базы и заносится в кеш
     * @param $sensor_id -   идентификатор сенсора
     * @return $mine_id         -   значение параметра сенсора, в котором лежит шахта
     * @author Якимов М.Н.
     * @since 02.06.2019 Написан метод
     */
    public static function getSensorStaticLastMine($sensor_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений

        try {
            $warnings[] = 'getSensorStaticLastMine. Начал выполнять метод';
            $sensor_mine_id = null;

            $sensor_cache_controller = new SensorCacheController();
            $sensorParameterMine = $sensor_cache_controller->getParameterValueHash($sensor_id, 346, 1);
            $warnings[] = $sensorParameterMine;
            if ($sensorParameterMine === false) {
                $warnings[] = 'getSensorStaticLastMine. в кеше значения не было, начал забирать из БД';
                $sensorParameterMine = (new Query())
                    ->select([
                        'mine_id',
                        'sensor_parameter_id'
                    ])
                    ->from('view_GetSensorLastMine')
                    ->where(['sensor_id' => $sensor_id])
                    ->one();
                if ($sensorParameterMine) {
                    $sensor_mine_id = (int)$sensorParameterMine['mine_id'];
                    $sensor_parameter_id_mine = $sensorParameterMine['sensor_parameter_id'];
                    $warnings[] = 'getSensorStaticLastMine. Уложил значение в кеш';
                    $response = $sensor_cache_controller->setSensorParameterValueHash($sensor_id, $sensor_parameter_id_mine, $sensor_mine_id, 346, 1);
                    if ($response['status'] == 1) {
                        $status *= 1;
                    } else {
                        $errors[] = $response['errors'];
                        throw new \Exception('getSensorStaticLastMine. Ошибка записи заначения шахты для сенсора в кеш');
                    }
                } else {
                    throw new \Exception("getSensorStaticLastMine. Для сенсора $sensor_id не сконфигурирован параметр Шахта (Mine_id)");
                }
            } else {
                $warnings[] = 'getSensorStaticLastMine. Значение шахты сенсора найдено в кеше';
                $sensor_mine_id = (int)$sensorParameterMine['value'];
            }
        } catch (\Throwable $e) {
            $status = 0;
            $sensor_mine_id = null;
            $errors[] = 'getSensorStaticLastMine.Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'mine_id' => $sensor_mine_id);
        return $result_main;
    }

    /**
     * getSensorUniversalLastMine - Получение значения последний шахты для Любых сенсоров
     * @param $sensor_id -   идентификатор сенсора
     * @return $mine_id         -   значение параметра сенсора, в котором лежит шахта
     * @return $parameter_type_id  -   тип параметра, в котором лежит шахта данного сенсора
     * @author Якимов М.Н.
     * @since 02.06.2019 Написан метод
     */
    public static function getSensorUniversalLastMine($sensor_id, $object_type_id = -1)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $parameter_type_id = -1;
        try {
            $warnings[] = "getSensorUniversalLastMine. Начал выполнять метод";
            /**
             * если в метод не передан сразу тип типового объекта сенсора, то ищем его в бд сами,
             */
            if ($object_type_id == -1) {
                $warnings[] = "getSensorUniversalLastMine. Тип типового объекта в метод не передан ищем в БД сами";
                $sensors = (new Query())
                    ->select([
                        'object_type_id'
                    ])
                    ->from('sensor')
                    ->innerJoin('object', 'object.id = sensor.object_id')
                    ->where('sensor.id = ' . $sensor_id)
                    ->limit(1)
                    ->one();

                if ($sensors) {
                    $warnings[] = "getSensorUniversalLastMine. Сенсор нашли получаем его тип типового объекта";
                    $warnings[] = "getSensorUniversalLastMine. Нашел сенсор в БД";
                    $warnings[] = "getSensorUniversalLastMine. Начинаю перебирать";
                    $warnings[] = "getSensorUniversalLastMine. Зашел в перебор";
                    $object_type_id = $sensors['object_type_id'];
                } else {
                    throw new \Exception("getSensorUniversalLastMine. Заданного сенсора нет в БД $sensor_id не смог определить тип типового объекта");
                }
            }

            $parameter_type_id = 2;
            if ($object_type_id == 22 || $object_type_id == 116 || $object_type_id == 95 || $object_type_id == 96 || $object_type_id == 28) {
                $parameter_type_id = 1;
            }

            $sensor_mine = (new SensorCacheController())->getParameterValueHash($sensor_id, 346, $parameter_type_id);
            if ($sensor_mine) {
                $sensor_mine_id = $sensor_mine['value'];
                $status *= 1;
                $warnings[] = "getSensorUniversalLastMine. Получил последнюю шахту $sensor_mine_id и ее тип параметера $parameter_type_id";
            } else {
                $warnings[] = "getSensorUniversalLastMine. в кеше нет последнего значения шахты";
                $status *= 1;
                $sensor_mine_id = false;
            }

        } catch (\Throwable $e) {
            $status = 0;
            $sensor_mine_id = null;
            $errors[] = "getSensorUniversalLastMine.Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "getSensorUniversalLastMine. Вышел из метода";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'mine_id' => $sensor_mine_id, 'parameter_type_id' => $parameter_type_id);
        return $result_main;
    }

    // AddMoveSensorMineInitDB - Метод переноса сенсора между кешами SensorMine с инициализацией базовых сведений из БД
    // если находит предыдущее значение параметра шахтного поля у сенсора, то проверяет сменилось ли оно или нет,
    // если сменилось, то удаляет старый кеш
    // инициализирует сенсор по новым значения
    //
    // разработал: Якимов М.Н.
    public static function AddMoveSensorMineInitDB($sensor)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = "moveSensorMineInitDB. Начал выполнять метод";
        try {
            $mine_id_new = $sensor['mine_id'];
            $sensor_id = $sensor['sensor_id'];
            /**
             * блок получения предыдущего значения парамтера шахтное поле сенсора
             */

            $warnings[] = "moveSensorMineInitDB. Ищу предыдущее значение параметра шахтное поле у сенсора " . $sensor_id;
            $response = self::getSensorUniversalLastMine($sensor_id, $sensor['object_type_id']);
            if ($response['status'] == 1) {
                $mine_id_last = $response['mine_id'];
                $parameter_type_id = $response['parameter_type_id'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($mine_id_last === false) {
                    $warnings[] = "moveSensorMineInitDB. Кеш значений параметра сенсора шахтное поле пуст";
                }
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception("moveSensorMineInitDB. Не смог получить предыдущее значение шахтного поля для сенсора" . $sensor_id);
            }
            /**
             * ПРоверяем сменилась ли шахта, и если сменилась, то удаляем старый кеш
             */
            if ($mine_id_last != false and $mine_id_last != $mine_id_new) {
                (new SensorCacheController())->delInSensorMineHash($sensor_id, $mine_id_last);
                $warnings[] = "moveSensorMineInitDB. Удалил старый главный кеш сенсора" . $sensor_id;
            } else {
                $warnings[] = "moveSensorMineInitDB. значение параметра шахтное поле сенсора не получено или не изменилось, старый главный кеш не удалялся" . $sensor_id;
            }
            /**
             * инициализируем новый кеш
             */
            $response = (new SensorCacheController())->addSensorHash($sensor);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $warnings[] = 'moveSensorMineInitDB. Добавил сенсор в главный кеш';
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception("moveSensorMineInitDB. Не смог добавить сенсор в главный кеш");
            }
            unset($sensor);
            unset($mine_id_last);
            unset($mine_id_new);

        } catch (\Throwable $e) {
            $status = 0;
            $errors[] = "moveSensorMineInitDB. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "moveSensorMineInitDB. Выполнение метода закончил";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // moveSensorMineInitCache - Метод переноса сенсора между кешами SensorMine, без инициализации базовых сведений из БД сенсора
    // инициализирует новый кеш по старому значению с учетом новой шахты
    // если находит предыдущее значение параметра шахтного поля у сенсора, то удаляет его,
    // инициализирует новый кеш по старому значению с учетом новой шахты
    // потому сперва нужно переместить главный кеш, а затем сменить значение параметра этой шахты на другое
    // !!!!!! СМЕНЫ ЗНАЧЕНИЯ ПАРАМЕТРА 346 ЗДЕСЬ НЕТ!!! НАДО ДЕЛАТЬ ОТДЕЛЬНО!!!!
    //
    // разработал: Якимов М.Н.
    public static function moveSensorMineInitCache($sensor_id, $mine_id_new)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = 'moveSensorMineInitCache. Начал выполнять метод';
        try {
            $sensor_cache_controller = new SensorCacheController();
            /**
             * блок получения старого значения главного кеша
             */
            $warnings[] = 'moveSensorMineInitCache. Ищу текущий главный кеш сенсора ' . $sensor_id;
            $sensor_del = $sensor_cache_controller->getSensorMineBySensorHash($sensor_id);
            if ($sensor_del) {
                $warnings[] = 'moveSensorMineInitCache. главный кеш сенсора получен: ';
                $warnings = $sensor_del;
            } else {
                throw new \Exception('moveSensorMineInitCache. Главный кеш сенсора не инициализирован. Не смог получить главный кеш сенсора: ' . $sensor_id);
            }

            /**
             * Проверяем сменилась ли шахта, и если сменилась, то удаляем старый кеш
             */
            $mine_id_last = $sensor_del['mine_id'];
            if ($mine_id_last != $mine_id_new) {
                $sensor_cache_controller->delInSensorMineHash($sensor_id, $mine_id_last);
                $warnings[] = 'moveSensorMineInitCache. Удалил старый главный кеш сенсора' . $sensor_id;
            } else {
                $warnings[] = 'moveSensorMineInitCache. значение параметра шахтное поле сенсора не получено или не изменилось, старый главный кеш не удалялся. sensor_id: ' . $sensor_id;
            }
            //перепаковываю старый кеш в новый
            $sensor = SensorCacheController::buildStructureSensor(
                $sensor_del['sensor_id'], $sensor_del['sensor_title'],
                $sensor_del['object_id'], $sensor_del['object_title'],
                $sensor_del['object_type_id'], $mine_id_new);


            /**
             * инициализируем новый кеш
             */
            $response = $sensor_cache_controller->addSensorHash($sensor);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $warnings[] = 'moveSensorMineInitCache. Добавил сенсор в главный кеш';
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('moveSensorMineInitCache. Не смог добавить сенсор в главный кеш');
            }
            unset($sensor, $mine_id_last, $mine_id_new);

        } catch (\Throwable $e) {
            $status = 0;
            $errors[] = 'moveSensorMineInitCache. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'moveSensorMineInitCache. Выполнение метода закончил';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * @param $sensor_id - идентификатор сенсора. Если указать '*', то возвращает все сенсоры
     * @param $parameter_id - идентификатор параметра. Если указать '*', то возвращает все параметры
     * @param $parameter_type_id - идентификатор типа параметра
     * @return string созданный ключ кэша в виде SensorParameter:sensor_id:parameter_id:parameter_type_id
     *
     * @package backend\controllers\cachemanagers
     * Название метода: buildParameterKey()
     * Назначение метода: Метод создания ключа кэша для списка сенсорово с их значениями (SensorParameter)
     *
     * Входные обязательные параметры:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 24.05.2019 13:05
     */
    public static function buildParameterKey($sensor_id, $parameter_id, $parameter_type_id)
    {
        return self::$sensor_parameter_cache_key . ':' . $sensor_id . ':' . $parameter_id . ':' . $parameter_type_id;
    }


    public static function buildSensorMineKey($mine_id, $sensor_id)
    {
        return self::$sensor_mine_cache_key . ':' . $mine_id . ':' . $sensor_id;
    }

    /**
     * initSensorInCache - Метод инициализации конкретного сенсора в кеше.
     * Применяется в тех случаях, когда сенсор не существует в кеше если ключ
     * шахты не задан, то инициализация кеша сенсора осуществляется на базе того, что задано в шахте
     * @param $sensor_id -   идентификатор сенсора инициализируемого сенсора
     * @param $mine_id -   идентификатор шахты
     * @return $mine_id         -   значение параметра сенсора, в котором лежит шахта
     * @author Якимов М.Н.
     * @since 02.06.2019 Написан метод
     */
    public static function initSensorInCache($sensor_id, $mine_id = -1)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений

        try {
            $warnings[] = 'initSensorInCache. Начал выполнять метод';

            /**
             * инициализируем кеш измеренных параметров сенсора
             */
            $sensorCacheController = new SensorCacheController();
            $response = $sensorCacheController->initSensorParameterValueHash($sensor_id);
            $result = $response['Items'];
            $status *= $response['status'];
            $errors[] = $response['errors'];
            $warnings[] = $response['warnings'];

            /**
             * инициализируем кеш справочных параметров сенсора
             */
            $response = $sensorCacheController->initSensorParameterHandbookValueHash($sensor_id);
            $result = $response['Items'];
            $status *= $response['status'];
            $errors[] = $response['errors'];
            $warnings[] = $response['warnings'];

            if ($mine_id == -1) {

                $sensor_cache_controller = new SensorCacheController();
                $sensor_object = $sensor_cache_controller->getParameterValueHash($sensor_id, 274, 1);

                if (!$sensor_object) {
                    throw new \Exception("initSensorInCache. Параметр 274 (тип сенсора) object_type_id для сенсора $sensor_id не сконфигурирован");
                }

                $sensor_type_object = TypicalObject::findOne(['id' => $sensor_object['value']]);
                if (!$sensor_type_object) {
                    throw new \Exception('initSensorInCache. Типа объекта сенсора: ' . $sensor_id . ' с object_id= ' . $sensor_object['value'] . " не существует");
                }

                $object_type_id = $sensor_type_object['object_type_id'];
                if ($object_type_id == 22 || $object_type_id == 116 || $object_type_id == 95 || $object_type_id == 96 || $object_type_id == 28) {
                    $sensor_parameter_value_mine = $sensor_cache_controller->getParameterValueHash($sensor_id, 346, 1);
                } else {
                    $sensor_parameter_value_mine = $sensor_cache_controller->getParameterValueHash($sensor_id, 346, 2);
                }

                if ($sensor_parameter_value_mine) {
                    $mine_id = $sensor_parameter_value_mine['value'];
                } else {
                    throw new \Exception("initSensorInCache. Параметр шахты не сконфигурирован для сенсора $sensor_id должным образом ключ ");
                }


            }

            /**
             * инициализируем кеш сенсора
             */
            $response = $sensorCacheController->initSensorMainHash($mine_id, $sensor_id);
            $result = $response['Items'];
            $status *= $response['status'];
            $errors[] = $response['errors'];
            $warnings[] = $response['warnings'];

        } catch (\Throwable $e) {
            $status = 0;
            $errors[] = 'initSensorInCache.Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * GetOrSetSensorParameter - Метод возвращает значение конкретного параметра
     * сенсора по заданным параметрам, если нет то создает в БД.
     * сперва из кеша, если там нет, то из БД, если там нет, то создает
     * // входные параметры:
     * //      $sensor_id          - ключ сенсора
     * //      $parameter_id       - ключ параметра
     * //      $parameter_type_id  - ключ типа параметра
     * // выходные параметры:
     * //      типовой набор параметров
     * //      sensor_parameter_id - ключ конкретного параметра сенсора
     * // пример использования : $response = SensorMainController::GetOrSetSensorParameter($sensor_id, $parameter_id, $parameter_type_id);
     * // разработал: Якимов М.Н
     * // дата: 02.06.2019
     */
    public static function GetOrSetSensorParameter($sensor_id, $parameter_id, $parameter_type_id)
    {
        $sensor_parameter_id = -1;
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'GetOrSetSensorParameter. Начало выполнения метода';
        try {
            $sensor_parameters = (new SensorCacheController())->getParameterValueHash($sensor_id, $parameter_id, $parameter_type_id);
            if (!$sensor_parameters) {
                $sensor_parameters = SensorParameter::findOne(['parameter_type_id' => $parameter_type_id, 'parameter_id' => $parameter_id, 'sensor_id' => $sensor_id]);
                if ($sensor_parameters) {
                    $sensor_parameter_id = $sensor_parameters['id'];
                    $warnings[] = "GetOrSetSensorParameter. Ключ конкретного параметра сенсора равен $sensor_parameter_id для сенсора $sensor_id и параметра $parameter_id и типа параметра $parameter_type_id";
                    $status *= 1;
                } else {
                    // создаем конкретный параметр в базе данных
                    $response = SensorBasicController::addSensorParameter($sensor_id, $parameter_id, $parameter_type_id);
                    if ($response['status'] == 1) {
                        $sensor_parameter_id = $response['sensor_parameter_id'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        $status *= $response['status'];

                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new \Exception("GetOrSetSensorParameter. Для сенсора $sensor_id не существует привязки к нему параметра $parameter_id и типа параметра $parameter_type_id");
                    }
                }
            } else {
                $sensor_parameter_id = $sensor_parameters['sensor_parameter_id'];
                $status *= 1;
                $warnings[] = "GetOrSetSensorParameter.Значение конкретного параметра $sensor_parameter_id найдено в кеше";
            }
        } catch (\Throwable $e) {
            $status = 0;
            $errors[] = 'GetOrSetSensorParameter. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'GetOrSetSensorParameter. Закончил выполнение метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'sensor_parameter_id' => $sensor_parameter_id);
        return $result_main;
    }

    /**
     * Название метода: getOrSetParameterValue()
     * Назначение метода: метод получения последних значений параметра сенсора из кэша, если в кэше нет, то из БД
     * Метод получает данные из кэша. Если кэш пустой, то получаем из БД, и добавляет в кэш, то есть запускает метод инициализации
     * значения параметра сенсора указывая конкретные параметры
     *
     * Входные обязательные параметры:
     * @param $sensor_id - идентифкатор сенсора
     * @param $parameter_id - идентификатор параметра
     * @param $parameter_type_id - идентификатор типа параметра
     *
     * @return array массив данных вида:
     * [
     *  'Items' =>
     *      [
     *          'sensor_id',
     *          'sensor_parameter_id',
     *          'parameter_id',
     *          'parameter_type_id',
     *          'date_time',
     *          'value',
     *          'status_id'
     *      ],
     *  'errors' => []
     * ]
     *
     * @package backend\controllers
     *
     * @example (new SensorMainController(Yii::$app->id, Yii::$app))->getOrSetParameterValue(26989, 164, 3);
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 03.06.2019 14:27
     */
    public function getOrSetParameterValue($sensor_id, $parameter_id, $parameter_type_id)
    {
        $sensorCacheController = new SensorCacheController();
        $warnings = array();
        $sensor_parameter_value = $sensorCacheController->getParameterValueHash($sensor_id, $parameter_id, $parameter_type_id);// получаем данные из кэша
        if ($sensor_parameter_value === FALSE)                                                                            // проверяем, есть ли данные в кэше, если нет, то берем из БД и добавляем в кэш
        {
            $warnings[] = 'Нет данных в кэше';
            $warnings[] = 'Получаю данные из БД';
            if ($parameter_type_id == 1) {
                $sensor_parameter_value = $sensorCacheController->initSensorParameterHandbookValueHash($sensor_id, "parameter_type_id = $parameter_type_id AND parameter_id = $parameter_id");
            } else {
                $sensor_parameter_value = $sensorCacheController->initSensorParameterValueHash($sensor_id, "parameter_type_id = $parameter_type_id AND parameter_id = $parameter_id");
            }
            return array('warnings' => $sensor_parameter_value['warnings'], 'Items' => $sensor_parameter_value['Items'][0], 'errors' => $sensor_parameter_value['errors']);
        }
        return array('Items' => $sensor_parameter_value, 'errors' => array());
    }

    /**
     * Название метода: getOrSetSensorByNetworkId()
     * Назначение метода: метод получения ИД сенсора по его сетевому идентификатору.
     * Метод проверяет кэш, если есть в кэше, то возващает id сенсора конкретного, иначе если в кэше нет, то
     * запускает метод инициализации кэша SensorNetwork передавая сетевой идентификатор, и заново получает данные из кэша.
     * Если данные в кэше нет,то возвращает false
     *
     * Входные обязательные параметры:
     * @param $network_id - сетевой идентификатор
     *
     * @return mixed ИД сенсора если есть, иначе false
     *
     * @package backend\controllers
     *
     * @example $this->getOrSetSensorByNetworkId(661025);
     * @example $this->getOrSetSensorByNetworkId('*') - Получение всех сенсоров
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 05.06.2019 13:58
     */
    public static function getOrSetSensorByNetworkId($network_id)
    {
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();
        $sensor_id = false;
        $warnings[] = "getOrSetSensorByNetworkId. Начал выполнять метод поиска sensor_id по net_id: $network_id";
        try {
            $sensorCacheController = new ServiceCache();
            $sensor_id = $sensorCacheController->getSensorByNetworkId($network_id);
            if ($sensor_id === false) {
                $warnings[] = 'getOrSetSensorByNetworkId. Кеш пуст ищу в БД';
                $sensor_id = $sensorCacheController->initSensorNetworkFromDb($network_id);
                if ($sensor_id === False) {
                    $warnings[] = 'getOrSetSensorByNetworkId. Ни в БД ни в кеше данного net_id  не существует. Нужно создать сенсор с 0';
                }
            } else {
                $warnings[] = 'getOrSetSensorByNetworkId. Кеш был';
            }
        } catch (\Exception $e) {
            $status = 0;
            $errors[] = 'getOrSetSensorByNetworkId. Исключение';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "getOrSetSensorByNetworkId. Закончил выполнять метод. sensor_id = $sensor_id";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'sensor_id' => $sensor_id);
        return $result_main;
    }

    /**DeleteSensorFromShema - удаление сенсора со схемы
     * @param $mine_id
     * @param $sensor_id
     * @return array
     */
    public static function DeleteSensorFromShema($mine_id, $sensor_id)
    {
        $sensor_cache = new SensorCacheController();
        $temp_sensor = $sensor_cache->getSensorMineHash($mine_id, $sensor_id);                                              //ищем наш сенсор в кеше
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();
        if ($temp_sensor != false) {
            if ($temp_sensor[0]['object_type_id'] == 22 ||
                $temp_sensor[0]['object_type_id'] == 95 ||
                $temp_sensor[0]['object_type_id'] == 96 ||
                $temp_sensor[0]['object_type_id'] == 116 ||
                $temp_sensor[0]['object_type_id'] == 28) {
                /**************             ПОЛУЧАЕМ МЕСТОПОЛОЖЕНИЯ СЕНСОРА                      **********/
                $sensor_parameter_122_value = $sensor_cache->getParameterValueHash($sensor_id, 122, 1);
                if ($sensor_parameter_122_value != false) {
                    $save_value = SensorBasicController::addSensorParameterHandbookValue($sensor_parameter_122_value['sensor_parameter_id'], '-1', 1);
                    $status *= $save_value['status'];
                    $warnings[] = $save_value['warnings'];
                    $errors[] = $save_value['errors'];
                } else {
                    $errors[] = 'Не нашел в кеше 122 справочный параметр для sensor_id = ' . $sensor_id;
                }
                /**************             ПОЛУЧАЕМ place СЕНСОРА                      **********/
                $sensor_parameter_269_value = $sensor_cache->getParameterValueHash($sensor_id, 269, 1);
                if ($sensor_parameter_269_value != false) {
                    $save_value = SensorBasicController::addSensorParameterHandbookValue($sensor_parameter_269_value['sensor_parameter_id'], '-1', 1);
                    $status *= $save_value['status'];
                    $warnings[] = $save_value['warnings'];
                    $errors[] = $save_value['errors'];
                } else {
                    $errors[] = 'DeleteSensorFromShema. Не нашел в кеше 269 справочный параметр для sensor_id = ' . $sensor_id;
                }
                /**************             ПОЛУЧАЕМ xyz СЕНСОРА                      **********/
                $sensor_parameter_83_value = $sensor_cache->getParameterValueHash($sensor_id, 83, 1);
                if ($sensor_parameter_83_value != false) {
                    $save_value = SensorBasicController::addSensorParameterHandbookValue($sensor_parameter_83_value['sensor_parameter_id'], '-1', 1);
                    $status *= $save_value['status'];
                    $warnings[] = $save_value['warnings'];
                    $errors[] = $save_value['errors'];
                } else {
                    $errors[] = 'DeleteSensorFromShema. Не нашел в кеше 83 справочный параметр для sensor_id = ' . $sensor_id;
                }
                /**************             ПОЛУЧАЕМ наименование шахтного поля СЕНСОРА                      **********/
                $sensor_parameter_346_value = $sensor_cache->getParameterValueHash($sensor_id, 346, 1);
                if ($sensor_parameter_346_value != false) {
                    $save_value = SensorBasicController::addSensorParameterHandbookValue($sensor_parameter_346_value['sensor_parameter_id'], '-1', 1);
                    $status *= $save_value['status'];
                    $warnings[] = $save_value['warnings'];
                    $errors[] = $save_value['errors'];
                } else {
                    $errors[] = 'DeleteSensorFromShema. Не нашел в кеше 346 справочный параметр для sensor_id = ' . $sensor_id;
                }
            } else {
                /**************             ПОЛУЧАЕМ МЕСТОПОЛОЖЕНИЯ СЕНСОРА                      **********/
                $sensor_parameter_122_value = $sensor_cache->getParameterValueHash($sensor_id, 122, 2);
                if ($sensor_parameter_122_value != false) {
                    $save_value = SensorBasicController::addSensorParameterValue($sensor_parameter_122_value['sensor_parameter_id'], '-1', 1);
                    $status *= $save_value['status'];
                    $warnings[] = $save_value['warnings'];
                    $errors[] = $save_value['errors'];
                } else {
                    $errors[] = 'DeleteSensorFromShema. Не нашел в кеше 122 параметр для sensor_id = ' . $sensor_id;
                }
                /**************             ПОЛУЧАЕМ place СЕНСОРА                      **********/
                $sensor_parameter_269_value = $sensor_cache->getParameterValueHash($sensor_id, 269, 2);
                if ($sensor_parameter_269_value != false) {
                    $save_value = SensorBasicController::addSensorParameterValue($sensor_parameter_269_value['sensor_parameter_id'], '-1', 1);
                    $status *= $save_value['status'];
                    $warnings[] = $save_value['warnings'];
                    $errors[] = $save_value['errors'];
                } else {
                    $errors[] = 'DeleteSensorFromShema. Не нашел в кеше 269 параметр для sensor_id = ' . $sensor_id;
                }
                /**************             ПОЛУЧАЕМ xyz СЕНСОРА                      **********/
                $sensor_parameter_83_value = $sensor_cache->getParameterValueHash($sensor_id, 83, 2);
                if ($sensor_parameter_83_value != false) {
                    $save_value = SensorBasicController::addSensorParameterValue($sensor_parameter_83_value['sensor_parameter_id'], '-1', 1);
                    $status *= $save_value['status'];
                    $warnings[] = $save_value['warnings'];
                    $errors[] = $save_value['errors'];
                } else {
                    $errors[] = 'DeleteSensorFromShema. Не нашел в кеше 83 параметр для sensor_id = ' . $sensor_id;
                }
                /**************             ПОЛУЧАЕМ наименование шахтного поля СЕНСОРА                      **********/
                $sensor_parameter_346_value = $sensor_cache->getParameterValueHash($sensor_id, 346, 2);
                if ($sensor_parameter_346_value != false) {
                    $save_value = SensorBasicController::addSensorParameterValue($sensor_parameter_346_value['sensor_parameter_id'], '-1', 1);
                    $status *= $save_value['status'];
                    $warnings[] = $save_value['warnings'];
                    $errors[] = $save_value['errors'];
                } else {
                    $errors[] = 'DeleteSensorFromShema. Не нашел в кеше 346 параметр для sensor_id = ' . $sensor_id;
                }
            }
            /**
             * удаляем сенсор из кеша
             */
            $sensor_cache->delInSensorMineHash($sensor_id, $mine_id);
            $sensor_cache->delParameterValueHash($sensor_id);
        } else {
            $errors[] = 'DeleteSensorFromShema. Нет в кеше узла с sensor_id = ' . $sensor_id;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // IsChangeSensorParameterValue - метод проверки необходимости записи измеренного или полученного значения в БД
    // входные параметры:
    //      $sensor_id                  -   ключ сенсора
    //      $parameter_id               -   ключ параметра сенсора
    //      $parameter_type_id          -   ключ типа параметра сенсора
    //      $parameter_value            -   проверяемое значение параметра сенсора
    //      $parameter_value_date_time  -   дата проверяемого значения параметра сенсора
    //      $sensor_parameter_value_lists_cache -   последние значения параметров из кеша по конкретному сенсору
    // выходные параметры:
    //      flag_save           - флаг статуса записи в БД 0 - не записывать, 1 записывать
    //      стандартный набор
    // разработал: Якимов М.Н. 08.06.2019
    public static function IsChangeSensorParameterValue($sensor_id, $parameter_id, $parameter_type_id, $parameter_value, $parameter_value_date_time, $sensor_parameter_value_cache_array = null)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        $flag_save = -1;
        $warnings[] = "IsChangeSensorParameterValue. Начал выполнять метод";
        /**
         * Блок проверки на изменение значение параметра
         */
        try {
            /**
             * блок проверки наличия переданного ранее последнего значения в данный метод. если его нет, то ищем обычным способом из кеша, в любом другом случае считаем, что последнего значения нет
             * почему так: метод работаает штатным образом, когда надо проверить на изменение одного параметра. Но есди надо его использовать в методах массовой вставки, то в него передаются заранее все последние значения по данному сенсору
             */
            if ($sensor_parameter_value_cache_array) {
                if (isset($sensor_parameter_value_cache_array[$sensor_id][$parameter_type_id][$parameter_id])) {
                    //берем из полученных значений при массовой вставке
                    $sensor_parameter_value = $sensor_parameter_value_cache_array[$sensor_id][$parameter_type_id][$parameter_id]; // получаем предыдущее значение параметра/тега
                    unset($sensor_parameter_value_cache_array);
                } else {
                    $sensor_parameter_value = false;
                }
            } else {
                //берем из кеша напрямки
                $sensor_parameter_value = (new SensorCacheController())->getParameterValueHash($sensor_id, $parameter_id, $parameter_type_id); // получаем предыдущее значение параметра/тега
            }
            if ($sensor_parameter_value) {
                $warnings[] = "IsChangeSensorParameterValue. До проверок $sensor_id $parameter_id $parameter_type_id Текущее значение: $parameter_value Предыдущее значение: " . $sensor_parameter_value['value'];
                $delta_time = strtotime($parameter_value_date_time) - strtotime($sensor_parameter_value['date_time']);
                $warnings[] = "IsChangeSensorParameterValue. текущая дата " . strtotime($parameter_value_date_time);
                $warnings[] = "IsChangeSensorParameterValue. Прошлая дата " . strtotime($sensor_parameter_value['date_time']);
                if (!is_numeric($sensor_parameter_value['value'])) {
                    $value_last = str_replace(",", ".", $sensor_parameter_value['value']);
                } else {
                    $value_last = $sensor_parameter_value['value'];
                }

                $warnings[] = "IsChangeSensorParameterValue. После проверок $sensor_id $parameter_id $parameter_type_id Текущее значение: $parameter_value Предыдущее значение: " . $sensor_parameter_value['value'];

                if ($parameter_value != $value_last) {
                    /**
                     * проверка на число - для не чисел пишем сразу, для чисел делаем проверку и откидываем дребезг значений
                     */
                    $warnings[] = "IsChangeSensorParameterValue. проверка на число";
                    $warnings[] = "IsChangeSensorParameterValue. Текущее значение число? (если нет значения, то строка): " . is_numeric($parameter_value);
                    $warnings[] = "IsChangeSensorParameterValue. Предыдущее значение число? (если нет значения, то строка): " . is_numeric($value_last);

                    if (is_numeric($parameter_value) and is_numeric($value_last))                                              // проверяем входные значения числа или нет
                    {
                        /**
                         * получаем максимальное значение данного параметра/тега для того, что бы вычислить погрешность
                         * в случае если полученное справочное значение число, то выполняем проверку
                         * иначе просто пишем в БД
                         * Проверка - если изменения текущего значения от пердыдущего меньше 0,01 - 1%, то в БД не пишем
                         */
                        $tag_handbook_values = (new SensorCacheController())->getParameterValueHash($sensor_id, $parameter_id, 1); // получаем уставку параметра тега
                        //максимальное значение существует в кеше, оно число и не равно 0. иначе просто пишем в БД
                        if ($tag_handbook_values and is_numeric($tag_handbook_values['value']) and $tag_handbook_values['value'] != 0) {
                            $tag_handbook_value = $tag_handbook_values['value'];
                            $warnings[] = "IsChangeSensorParameterValue. Значение число. Для параметра $parameter_id сенсора задана уставка $tag_handbook_value и она не 0. пишем в БД";
                            $accuracy = abs($parameter_value / $tag_handbook_value - $value_last / $tag_handbook_value);
                            if ($accuracy > 0.01) {
                                $warnings[] = "IsChangeSensorParameterValue. Изменение числа $accuracy больше 0,01 (1%). пишем в БД";
                                $flag_save = 1;   //значение поменялось, пишем сразу
                            } else {
                                $warnings[] = "IsChangeSensorParameterValue. Изменение числа $accuracy меньше или равно 0,01 (1%). БД не пишем";
                                $flag_save = 0;   //значение поменялось, пишем сразу
                            }
                        } else {
                            $warnings[] = "IsChangeSensorParameterValue. Значение число. Для параметра $parameter_id сенсора НЕ задано максимальное значение в его справочном параметра. пишем в БД";
                            $flag_save = 1;   //значение поменялось, пишем сразу
                        }
                    } else {
                        $warnings[] = "IsChangeSensorParameterValue. Значение НЕ число. Значение параметра $parameter_id сенсора не число и оно изменилось. пишем в БД";
                        $flag_save = 1;   //значение поменялось, пишем сразу
                    }
                } elseif ($delta_time >= 50) {
                    $warnings[] = "IsChangeSensorParameterValue. Дельта времени: " . $delta_time;
                    $warnings[] = "IsChangeSensorParameterValue. Прошло больше 1 минуты с последней записи в БД. Пишем в БД";
                    $warnings[] = "IsChangeSensorParameterValue. Старое время: " . $sensor_parameter_value['date_time'];
                    $warnings[] = "IsChangeSensorParameterValue. Новое время: " . $parameter_value_date_time;
                    $flag_save = 1;   //прошло больше 5 минут с последней записи в БД, пишем сразу
                } else {
                    $flag_save = 0;
                    $warnings[] = "IsChangeSensorParameterValue. Значение не поменялось и время не прошло больше 1 минуты $delta_time. Пришло время: $parameter_value_date_time, а в кэше: " . $sensor_parameter_value['date_time'];
                }
            } else {
                $warnings[] = "IsChangeSensorParameterValue. Нет предыдущих значений по параметру $parameter_id. пишем в БД сразу";
                $flag_save = 1;       //нет предыдущих данных, пишем сразу
            }
        } catch (\Throwable $ex) {
            $errors[] = "IsChangeSensorParameterValue. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        $warnings[] = "IsChangeSensorParameterValue. Закончил выполнять метод";
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'flag_save' => $flag_save);
    }

    // getListOpcParameters - метод получения списка OPC с их параметрами для выпадашки в конкретных объектах
    // входные параметры:
    // выходные параметры:
    //      типовой набор параметров
    //
    // пример использования : $response = self::getListSensorParameters();
    // разработал: Якимов М.Н
    // дата: 08.06.2019
    public static function GetListOpcParameters()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $list_parameters_with_sensors = array();
        $result = array();
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = "getListSensorParameters. Начало выполнения метода";
        try {                                                                         //Если параметр не был найден
            $sensor_full_list = (new Query())
                ->select(
                    '
                    sensor.id as sensor_id,
                    sensor.title as sensor_title,
                    sensor_parameter.id as sensor_parameter_id,
                    sensor_parameter.parameter_id as parameter_id,
                    sensor_parameter.parameter_type_id as parameter_type_id,
                    parameter_type.title as parameter_type_title,
                    parameter.title as parameter_title
                    '
                )
                ->from('sensor')
                ->innerJoin('sensor_parameter', 'sensor_parameter.sensor_id=sensor.id')
                ->innerJoin('parameter', 'sensor_parameter.parameter_id=parameter.id')
                ->innerJoin('parameter_type', 'sensor_parameter.parameter_type_id=parameter_type.id')
                ->where('parameter_type_id=2 and (object_id=155 or object_id=289)')
                ->limit(50000)
                ->orderBy('sensor.title')
                ->all();
            //$warnings[]=$sensor_full_list;
            foreach ($sensor_full_list as $sensor_full) {
                $sensor_parameter_id = $sensor_full['sensor_parameter_id'];
                $list_parameters_with_sensors[$sensor_parameter_id]['sensor_id'] = $sensor_full['sensor_id'];
            }
            /**
             * Делаем группировку по сенсорам, т.к. группировка yii методами на больших объемах данных ломается и не эффективна
             */
            $flag_save = -1;
            $sensor_id = -1;
//            $sensor_title = "";
            $params_array_tek = array();
            foreach ($sensor_full_list as $sensor_full) {
                if ($sensor_id != $sensor_full['sensor_id']) {
                    if ($sensor_id == -1) {                                                    // первый заход инициализируем переменные
                        $sensor_id = $sensor_full['sensor_id'];
                        $sensor_title = $sensor_full['sensor_title'];
                        $sensor_parameter_list['parameters'] = array();
                        $params_array_tek[$sensor_full['sensor_parameter_id']]['sensor_parameter_id'] = $sensor_full['sensor_parameter_id'];
                        $params_array_tek[$sensor_full['sensor_parameter_id']]['parameter_id'] = $sensor_full['parameter_id'];
                        $params_array_tek[$sensor_full['sensor_parameter_id']]['parameter_title'] = $sensor_full['parameter_title'];
                        $params_array_tek[$sensor_full['sensor_parameter_id']]['parameter_type_id'] = $sensor_full['parameter_type_id'];
                        $params_array_tek[$sensor_full['sensor_parameter_id']]['parameter_type_title'] = $sensor_full['parameter_type_title'];
                    } else {
                        $sensor_parameter_list['id'] = $sensor_id;                          // если сенсор изменился, то пишем в результирующий массив
                        $sensor_parameter_list['title'] = $sensor_title;
                        $sensor_parameter_list['parameters'] = $params_array_tek;
                        unset($params_array_tek);
                        $params_array_tek = array();
                        $result[] = $sensor_parameter_list;
                        unset($sensor_parameter_list);
                        $sensor_id = $sensor_full['sensor_id'];
                        $sensor_title = $sensor_full['sensor_title'];
                        $flag_save = 1;
                    }
                } else {
                    //собираем параметры в один массив для последующей выгрузки
                    $params_array_tek[$sensor_full['sensor_parameter_id']]['sensor_parameter_id'] = $sensor_full['sensor_parameter_id'];
                    $params_array_tek[$sensor_full['sensor_parameter_id']]['parameter_id'] = $sensor_full['parameter_id'];
                    $params_array_tek[$sensor_full['sensor_parameter_id']]['parameter_title'] = $sensor_full['parameter_title'];
                    $params_array_tek[$sensor_full['sensor_parameter_id']]['parameter_type_id'] = $sensor_full['parameter_type_id'];
                    $params_array_tek[$sensor_full['sensor_parameter_id']]['parameter_type_title'] = $sensor_full['parameter_type_title'];
                    $flag_save = 0;
                }
            }
            if ($flag_save == 0) {                                              // дозаписываем последний сенсор
                $sensor_parameter_list['id'] = $sensor_id;
                $sensor_parameter_list['title'] = $sensor_title;
                $sensor_parameter_list['parameters'] = $params_array_tek;
                unset($params_array_tek);
                $result[] = $sensor_parameter_list;
                unset($sensor_parameter_list);
            }

        } catch (\Throwable $e) {
            $status = 0;
            $errors[] = "getListSensorParameters. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "getListSensorParameters. Закончил выполнение метода";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'list_parameters_sensors' => $list_parameters_with_sensors);
        unset($result);
        return $result_main;
    }


    /** Метод CancelByEditForSensor() - удаляет последнее событие у группы сенсоров по парамтрам - 83, 122, 269, 346 (местонахождение, шахта, координаты, выработка)
     * Входные параметры: JSON с массивом id-сенсоров
     * Тестирование с помощью метода actionTestCancelByEditForSensor - http://localhost/read-manager-amicum?controller=SuperTest&method=actionTestCancelByEditForSensor&subscribe=&data={}
     * Выходные данные по результату - количество удаленных записей
     *
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Сreated date: on 27.08.2019 9:00
     *
     */
    public static function CancelByEditForSensor($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $result = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'CancelByEditForSensor. Начало метода';
        try {
            /****************** Проверка входных данных  ******************/
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('');
            }
            $warnings[] = 'CancelByEditForSensor. Данные успешно переданы';
            $warnings[] = 'CancelByEditForSensor. Входной массив данных';
            $warnings[] = $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'CancelByEditForSensor. Декодировал входные параметры';
            if (
                (!property_exists($post_dec, 'sensors_ids'))                                                       // Проверяем наличие в нем нужных нам полей
            ) {
                throw new Exception('CancelByEditForSensor. Во входно массиве нет нужно sensor_ids');
            }
            $warnings[] = 'CancelByEditForSensor. Данные с фронта получены';
            $sensors_ids = $post_dec->sensors_ids;

            if (!($sensors_ids !== NULL && $sensors_ids !== '')) {
                throw new Exception('CancelByEditForSensor. Данные (sensors_ids не получен)');
            }
            $warnings[] = 'CancelByEditForSensor. Данные успешно переданы';
            /****************** Выборка по необходимым параметрам ******************/
            $parameters = [83, 122, 269, 346];                                                                          //Параметры, по которым удаляются последние изменения

            $sensor_parameters = Sensor::find()
                ->select(['view_sensor_parameter_handbook_value_maxDate_main.sensor_parameter_handbook_value_id as id'])
                ->innerJoin('sensor_parameter', 'sensor.id=sensor_parameter.sensor_id ')
                ->innerJoin('view_sensor_parameter_handbook_value_maxDate_main', 'view_sensor_parameter_handbook_value_maxDate_main.sensor_parameter_id=sensor_parameter.id')
                ->where(['in', 'sensor.id', $sensors_ids])
                ->andWhere(['in', 'parameter_id', $parameters])
                ->asArray()
                ->all();
            if (!($sensor_parameters)) {                                                                                //проверка что данные есть
                throw new Exception('CancelByEditForSensor. Данных по параметрам 83, 122, 269, 346 у сенсору нет');
            }

            /****************** Делаем массив с id по которым необходимо удалить ******************/
            $sensor_parameters_del = array();

            foreach ($sensor_parameters as $sensor_parameter) {
                $sensor_parameters_del[] = (int)$sensor_parameter['id'];
            }
            $warnings[] = 'CancelByEditForSensor. Данные по параметрам 83, 122, 269, 346 у сенсора найдены';
            /****************** удаление ******************/
            $flag_delete = SensorParameterHandbookValue::deleteAll(['in', 'sensor_parameter_handbook_value.id', $sensor_parameters_del]);
            $warnings[] = "CancelByEditForSensor. состояние метода удаления значений параметров сенсора $flag_delete";  //Результат выполнения удаления
            $result = $flag_delete;
        } catch (\Throwable $exception) {
            $errors[] = 'CancelByEditForSensor. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $warnings[] = 'CancelByEditForSensor. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);    //формирование твета
        return $result_main;
    }

}
