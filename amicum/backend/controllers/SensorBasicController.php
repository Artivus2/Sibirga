<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace backend\controllers;

use backend\controllers\cachemanagers\SensorCacheController;
use Exception;
use frontend\models\Sensor;
use frontend\models\SensorParameter;
use frontend\models\SensorParameterHandbookValue;
use frontend\models\SensorParameterValue;
use frontend\models\TypeObjectParameter;
use Throwable;
use Yii;
use yii\db\Query;

class SensorBasicController
{
    // getSensorParameter                       -   метод получения ключа конкретного параметра сенсора по входным параметрам из БД
    // addSensorParameter                       -   метод добовление ключа конкретного параметра сенсора по входным параметрам в БД
    // addSensorParameterValue                  -   Метод добавления значений в таблицу sensor_parameter_value.
    // addSensorParameterHandbookValue          -   Метод добавления значений в таблицу sensor_parameter_handbook_value.
    // addMain                                  -   Метод создания главного айди в БД
    // addSensor                                -   Метод добавления сенсора в БД
    // getListSensorParameters                  -   метод получения списка сенсоров с их параметрами для выпадашки в конкретных объектах
    // FindSensorType                           -   метод поиска типа сенсора параметр 338 для фронта - когда они не передают его при создании
    // FindSensorASUTP                          -   метод поиска АСУТП сенсора параметр 337 для фронта - когда они не передают его при создании
    // GetPictureForObject                      -   Метод возвращает путь к картинке для конкретного объекта для параметра 168
    // getLastSensorParameterHandbookValue      -   Метод получения последних значений справочных значений из представления view_initSensorParameterHandbookValue
    // getLastSensorParameterValue              -   Метод получения последних значений вычисляемых/измеряемых значений из представления view_initSensorParameterValue
    // getSensorParameterHandbookValue()        -   метод получения справочных значений параметров сенсоров в БД SensorParameterHandbookValue
    // getSensorParameterValue()                -   метод получения вычисляемых значений параметров сенсоров в БД SensorParameterValue
    // getSensorMain()                          -   метод получения списка сенсоров по шахте(SensorMine) из БД на текущий момент времени
    // getSensorParameterHandbookValueByDate    -   метод получения справочных значений параметров сенсоров в БД SensorParameterHandbookValue на заданную дату
    // getSensorParameterValueByDate            -   метод получения измеренных/вычисленных значений параметров сенсоров в БД SensorParameterValue на заданную дату

    public function actionIndex()
    {
        echo "Метод " . __NAMESPACE__ . get_class() . " " . __FUNCTION__;
    }

    /**
     * Название метода: addSensor()
     * Назначение метода: Метод добавления сенсора в БД.
     *
     * Входные обязательные параметры:
     * @param $sensor_title - название сенсора
     * @param $sensor_type_id - тип сенсора
     * @param $asmtp_id - идентифкатор asmpt
     * @param $object_id - ИД объекта, к которму относится сенсор
     * @param $network_id - сетевой идентификатор метки
     *
     * Входные необязательные параметры
     * @param int $mine_id - идентфикатор шахты.
     *
     * @return array|int если данные успешно были добавлены, то ID нового сенсора, иначе ошибку
     * @package backend\controllers
     *
     * @example addSensor("Sensor", 4, 4,12) - добавление в БД
     * @example addSensor("Sensor", 4, 4,12, true, 290) -  добавление в БД и в кэш
     *
     * Документация на портале:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 27.05.2019 13:17
     */
    public static function addSensor($sensor_title, $object_id, $asmtp_id = -1, $sensor_type_id = -1, $mine_id = -1, $network_id = 0)
    {
        $result = array();                                                                                                // промежуточный результирующий массив
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                              // массив предупреждений
        $status = 1;
        $sensor_parameter_id_network = -1;
        $sensor_id = false;
        $sensor_parameter_handbook_values = false;
        try {
            Yii::$app->db_amicum2->createCommand('SET SESSION wait_timeout = 28800;')->execute();
            Yii::$app->db->createCommand('SET SESSION wait_timeout = 28800;')->execute();
            $sensor = Sensor::findOne(['title' => $sensor_title]);
            if ($sensor) {
                throw new Exception('addSensor. Сенсор с таким названием существует. Если ошибка в ССД Strata, значит у сенсора не задан Сетевой адрес (Параметр 88). Название сенсора: ' . $sensor_title
                    . " Ключ сенсора: " . $sensor->id);
            }

            /**
             * создание главного айди объекта - сенсора
             */
            $response = MainBasicController::addMain('sensor');
            if ($response['status'] == 1) {
                $main_id = $response['main_id'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('addSensor. Ошибка сохранения главного айди сенсора');
            }

            /**
             * блок поиска типа сенсора если он не задан - обычно это у фронт энд
             */
            if ($sensor_type_id == -1) {
                $sensor_type_id = self::FindSensorType($object_id);
                if ($sensor_type_id === false or $sensor_type_id == -1) {
                    $sensor_type_id = 9;
                    $warnings[] = 'addSensor. Тип АСУТП не был найден в БД у типового объекта, потому заносим в прочее';
                }
            }
            /**
             * блок поиска АСУТП если он не задан - обычно это у фронт энд
             */
            if ($asmtp_id == -1) {
                $asmtp_id = self::FindSensorASUTP($object_id);
                if ($asmtp_id === false || $asmtp_id == -1) {
                    $asmtp_id = 27;
                    $warnings[] = "addSensor. АСУТП не был найден в БД у типового объекта, потому заносим в прочее";
                }
            }

            /**
             * сохранение самого сенсора и его полей
             */
            $sensor = new Sensor();
            $sensor->id = $main_id;
            $sensor->title = $sensor_title;
            $sensor->sensor_type_id = $sensor_type_id;
            $sensor->asmtp_id = $asmtp_id;
            $sensor->object_id = $object_id;
            if (!$sensor->save())                                                                                                // если данные успешно сохранились в БД, то веозвращаем id
            {
                $errors[] = $sensor->errors;
                throw new Exception('addSensor. Ошибка сохранения сенсора');
            }

            $sensor_id = $sensor->id;
            $warnings[] = "addSensor. Сенсор сохранен и ключ равен $sensor_id";
            $date_now = Assistant::GetDateNow();                                                                // текущая дата и время вставки
            /**
             * сохранение в БД параметра Наименование
             */
            $response = self::addSensorParameter($sensor_id, 162, 1);
            if ($response['status'] == 1) {
                $sensor_parameter_id = $response['sensor_parameter_id'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("addSensor. Ошибка сохранения параметра 162 сенсора $sensor_id");
            }
            $response = self::addSensorParameterHandbookValue($sensor_parameter_id, $sensor_title, 1, $date_now);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("addSensor. Ошибка сохранения Значения: $sensor_title параметра 162 сенсора: $sensor_id");
            }
            // создаем массив для вставки разовой в кеш
            $sensor_parameter_handbook_value['sensor_id'] = $sensor_id;
            $sensor_parameter_handbook_value['sensor_parameter_id'] = $sensor_parameter_id;
            $sensor_parameter_handbook_value['parameter_id'] = 162;
            $sensor_parameter_handbook_value['parameter_type_id'] = 1;
            $sensor_parameter_handbook_value['date_time'] = $date_now;
            $sensor_parameter_handbook_value['value'] = $sensor_title;
            $sensor_parameter_handbook_value['status_id'] = 1;
            $sensor_parameter_handbook_values[] = $sensor_parameter_handbook_value;

            /**
             * сохранение в БД параметра Типовой объект
             */
            $response = self::addSensorParameter($sensor_id, 274, 1);
            if ($response['status'] == 1) {
                $sensor_parameter_id = $response['sensor_parameter_id'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("addSensor. Ошибка сохранения параметра 274 (типовой объект) сенсора $sensor_id");
            }
            $response = self::addSensorParameterHandbookValue($sensor_parameter_id, $object_id, 1, $date_now);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("addSensor. Ошибка сохранения Значения: $object_id параметра 274 (типовой объект) сенсора: $sensor_id");
            }
            // создаем массив для вставки разовой в кеш
            $sensor_parameter_handbook_value['sensor_id'] = $sensor_id;
            $sensor_parameter_handbook_value['sensor_parameter_id'] = $sensor_parameter_id;
            $sensor_parameter_handbook_value['parameter_id'] = 274;
            $sensor_parameter_handbook_value['parameter_type_id'] = 1;
            $sensor_parameter_handbook_value['date_time'] = $date_now;
            $sensor_parameter_handbook_value['value'] = $object_id;
            $sensor_parameter_handbook_value['status_id'] = 1;
            $sensor_parameter_handbook_values[] = $sensor_parameter_handbook_value;
            /**
             * сохранение в БД параметра АСУТП
             */
            $response = self::addSensorParameter($sensor_id, 337, 1);
            if ($response['status'] == 1) {
                $sensor_parameter_id = $response['sensor_parameter_id'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("addSensor. Ошибка сохранения параметра 337 (АСУТП) сенсора $sensor_id");
            }
            $response = self::addSensorParameterHandbookValue($sensor_parameter_id, $asmtp_id, 1, $date_now);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("addSensor. Ошибка сохранения Значения: $asmtp_id параметра 337 (АСУТП) сенсора: $sensor_id");
            }
            // создаем массив для вставки разовой в кеш
            $sensor_parameter_handbook_value['sensor_id'] = $sensor_id;
            $sensor_parameter_handbook_value['sensor_parameter_id'] = $sensor_parameter_id;
            $sensor_parameter_handbook_value['parameter_id'] = 337;
            $sensor_parameter_handbook_value['parameter_type_id'] = 1;
            $sensor_parameter_handbook_value['date_time'] = $date_now;
            $sensor_parameter_handbook_value['value'] = $asmtp_id;
            $sensor_parameter_handbook_value['status_id'] = 1;
            $sensor_parameter_handbook_values[] = $sensor_parameter_handbook_value;
            /**
             * сохранение в БД параметра Тип датчика АСУТП
             */
            $response = self::addSensorParameter($sensor_id, 338, 1);
            if ($response['status'] == 1) {
                $sensor_parameter_id = $response['sensor_parameter_id'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("addSensor. Ошибка сохранения параметра 338 (Тип датчика АСУТП) сенсора $sensor_id");
            }
            $response = self::addSensorParameterHandbookValue($sensor_parameter_id, $sensor_type_id, 1, $date_now);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("addSensor. Ошибка сохранения Значения: $sensor_type_id параметра 338 (Тип датчика АСУТП) сенсора: $sensor_id");
            }
            // создаем массив для вставки разовой в кеш
            $sensor_parameter_handbook_value['sensor_id'] = $sensor_id;
            $sensor_parameter_handbook_value['sensor_parameter_id'] = $sensor_parameter_id;
            $sensor_parameter_handbook_value['parameter_id'] = 338;
            $sensor_parameter_handbook_value['parameter_type_id'] = 1;
            $sensor_parameter_handbook_value['date_time'] = $date_now;
            $sensor_parameter_handbook_value['value'] = $sensor_type_id;
            $sensor_parameter_handbook_value['status_id'] = 1;
            $sensor_parameter_handbook_values[] = $sensor_parameter_handbook_value;
            /**
             * сохранение в БД параметра 2Д модель
             */
            $response = self::addSensorParameter($sensor_id, 168, 1);
            if ($response['status'] == 1) {
                $sensor_parameter_id = $response['sensor_parameter_id'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("addSensor. Ошибка сохранения параметра 168 (2Д модель) сенсора $sensor_id");
            }
            $image_path = self::GetPictureForObject($object_id);
            $response = self::addSensorParameterHandbookValue($sensor_parameter_id, $image_path, 1, $date_now);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("addSensor. Ошибка сохранения Значения: $image_path параметра 168 (2Д модель) сенсора: $sensor_id");
            }
            // создаем массив для вставки разовой в кеш
            $sensor_parameter_handbook_value['sensor_id'] = $sensor_id;
            $sensor_parameter_handbook_value['sensor_parameter_id'] = $sensor_parameter_id;
            $sensor_parameter_handbook_value['parameter_id'] = 168;
            $sensor_parameter_handbook_value['parameter_type_id'] = 1;
            $sensor_parameter_handbook_value['date_time'] = $date_now;
            $sensor_parameter_handbook_value['value'] = $image_path;
            $sensor_parameter_handbook_value['status_id'] = 1;
            $sensor_parameter_handbook_values[] = $sensor_parameter_handbook_value;

            /**
             * сохранение в БД параметра Сетевой идентификатор
             * 45    Узел связи A
             * 46    Узел связи C
             * 47    Светильник ЛУЧ-4
             * 90    Узел связи A-ХР
             * 91    Узел связи C-ХР
             * 102    Светильник Cap Lamp Strata
             * 103    Метка StrataCommTrac TAG SCT-TAG-03
             * 104    Метка Strata прочее
             * 105    Узел связи C прочее
             */
            if (in_array($object_id, [45, 46, 47, 90, 91, 102, 103, 104, 105])) {
                $response = self::addSensorParameter($sensor_id, 88, 1);
                if ($response['status'] == 1) {
                    $sensor_parameter_id = $response['sensor_parameter_id'];
                    $sensor_parameter_id_network = $response['sensor_parameter_id'];
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $status *= $response['status'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception("addSensor. Ошибка сохранения параметра 88 сенсора $sensor_id");
                }
                $response = self::addSensorParameterHandbookValue($sensor_parameter_id, $network_id, 1, $date_now);
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $status *= $response['status'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception("addSensor. Ошибка сохранения Значения: 0 параметра 88 сенсора: $sensor_id");
                }
                // создаем массив для вставки разовой в кеш
                $sensor_parameter_handbook_value['sensor_id'] = $sensor_id;
                $sensor_parameter_handbook_value['sensor_parameter_id'] = $sensor_parameter_id;
                $sensor_parameter_handbook_value['parameter_id'] = 88;
                $sensor_parameter_handbook_value['parameter_type_id'] = 1;
                $sensor_parameter_handbook_value['date_time'] = $date_now;
                $sensor_parameter_handbook_value['value'] = 0;
                $sensor_parameter_handbook_value['status_id'] = 1;
                $sensor_parameter_handbook_values[] = $sensor_parameter_handbook_value;
            }

            /**
             * сохранение в БД параметра Тип лампы, если нужно
             */
            if ($object_id == 47) {
                $response = self::addSensorParameter($sensor_id, 459, 1);
                if ($response['status'] == 1) {
                    $sensor_parameter_id = $response['sensor_parameter_id'];
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $status *= $response['status'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception("addSensor. Ошибка сохранения параметра 459 сенсора $sensor_id");
                }
                $response = self::addSensorParameterHandbookValue($sensor_parameter_id, 'Постоянная', 1, $date_now);
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $status *= $response['status'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception("addSensor. Ошибка сохранения Значения: 'Постоянная' параметра 459 сенсора: $sensor_id");
                }
                // создаем массив для вставки разовой в кеш
                $sensor_parameter_handbook_value['sensor_id'] = $sensor_id;
                $sensor_parameter_handbook_value['sensor_parameter_id'] = $sensor_parameter_id;
                $sensor_parameter_handbook_value['parameter_id'] = 459;
                $sensor_parameter_handbook_value['parameter_type_id'] = 1;
                $sensor_parameter_handbook_value['date_time'] = $date_now;
                $sensor_parameter_handbook_value['value'] = 'Постоянная';
                $sensor_parameter_handbook_value['status_id'] = 1;
                $sensor_parameter_handbook_values[] = $sensor_parameter_handbook_value;
            }

            /**
             * метод определения типа сенсора (если он стационар, то 1, иначе 2)
             * !!! исключил, т.к. нужно все же сразу оба значения инициализировать - так и быстрее и пользы больше
             */
//                $object_type = TypicalObject::findOne(['id' => $object_id]);
//                if (!$object_type) {
//                    $errors[] = "addSensor. Ошибка поиска типового объекта в TypicalObject";
//                    throw new \Exception("addSensor. Типового объекта не существует $object_id");
//                }
//                $object_type_id = $object_type['object_type_id'];
//                $parameter_type_id = 2;
//                if ($object_type_id == 22 || $object_type_id == 116 || $object_type_id == 95 || $object_type_id == 96 || $object_type_id == 28) {
//                    $parameter_type_id = 1;
//                }

            /**
             * сохранение в БД параметра Шахтное поле справочный
             */
            $response = self::addSensorParameter($sensor_id, 346, 1);
            if ($response['status'] == 1) {
                $sensor_parameter_id = $response['sensor_parameter_id'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("addSensor. Ошибка сохранения параметра 346(Шахтное поле) сенсора $sensor_id");
            }
            /**
             * этот блок пишу, чтобы знать с какой шахты первый раз прошла инициализация - актуально для меток
             */
            $response = self::addSensorParameterHandbookValue($sensor_parameter_id, $mine_id, 1, $date_now);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("addSensor. Ошибка сохранения Значения: $mine_id параметра 346(Шахтное поле) сенсора: $sensor_id");
            }
            // создаем массив для вставки разовой в кеш
            $sensor_parameter_handbook_value['sensor_id'] = $sensor_id;
            $sensor_parameter_handbook_value['sensor_parameter_id'] = $sensor_parameter_id;
            $sensor_parameter_handbook_value['parameter_id'] = 346;
            $sensor_parameter_handbook_value['parameter_type_id'] = 1;
            $sensor_parameter_handbook_value['date_time'] = $date_now;
            $sensor_parameter_handbook_value['value'] = $mine_id;
            $sensor_parameter_handbook_value['status_id'] = 1;
            $sensor_parameter_handbook_values[] = $sensor_parameter_handbook_value;

            /**
             * сохранение в БД параметра Шахтное поле измеренный - это чтобы следующие разы сразу был этот показатель для меток
             */
            $response = self::addSensorParameter($sensor_id, 346, 2);
            if ($response['status'] == 1) {
                $sensor_parameter_id = $response['sensor_parameter_id'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("addSensor. Ошибка сохранения параметра 346(Шахтное поле) сенсора $sensor_id");
            }
            /**
             * этот блок пишу, чтобы знать с какой шахты первый раз прошла инициализация
             */
            $response = self::addSensorParameterValue($sensor_parameter_id, $mine_id, 1, $date_now);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("addSensor. Ошибка сохранения Значения: $mine_id параметра 346(Шахтное поле) сенсора: $sensor_id");
            }
            // создаем массив для вставки разовой в кеш
            $sensor_parameter_handbook_value['sensor_id'] = $sensor_id;
            $sensor_parameter_handbook_value['sensor_parameter_id'] = $sensor_parameter_id;
            $sensor_parameter_handbook_value['parameter_id'] = 346;
            $sensor_parameter_handbook_value['parameter_type_id'] = 2;
            $sensor_parameter_handbook_value['date_time'] = $date_now;
            $sensor_parameter_handbook_value['value'] = $mine_id;
            $sensor_parameter_handbook_value['status_id'] = 1;
            $sensor_parameter_handbook_values[] = $sensor_parameter_handbook_value;

            /**
             * сохранение в БД параметра Состояние Сенсора
             */
            $response = self::addSensorParameter($sensor_id, 164, 1);
            if ($response['status'] == 1) {
                $sensor_parameter_id = $response['sensor_parameter_id'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("addSensor. Ошибка сохранения параметра 164 сенсора $sensor_id");
            }
            $response = self::addSensorParameterHandbookValue($sensor_parameter_id, 1, 1, $date_now);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("addSensor. Ошибка сохранения Значения: 1 параметра 164 сенсора: $sensor_id");
            }
            // создаем массив для вставки разовой в кеш
            $sensor_parameter_handbook_value['sensor_id'] = $sensor_id;
            $sensor_parameter_handbook_value['sensor_parameter_id'] = $sensor_parameter_id;
            $sensor_parameter_handbook_value['parameter_id'] = 164;
            $sensor_parameter_handbook_value['parameter_type_id'] = 1;
            $sensor_parameter_handbook_value['date_time'] = $date_now;
            $sensor_parameter_handbook_value['value'] = 1;
            $sensor_parameter_handbook_value['status_id'] = 1;
            $sensor_parameter_handbook_values[] = $sensor_parameter_handbook_value;

            //копирование типовых параметров в конкретный объект
            $response = self::copyTypicalParametersToSensor($object_id, $sensor_id);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("addSensor. Ошибка при копировании типовых параметров у сенсора: $sensor_id");
            }
            unset($response);
            unset($sensor_parameter_id);
            unset($date_now);
            unset($mine_id);
            unset($sensor);

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'addSensor. Исключение';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'addSensor. Вышел с метода';
        return array('Items' => $result, 'sensor_id' => $sensor_id, 'sensor_parameter_handbook_value' => $sensor_parameter_handbook_values, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'sensor_parameter_id_network' => $sensor_parameter_id_network);
    }

    //метод копирует параметры типового параметра в параметры конкретного объекта - нужен для создания конкретного объекта по шаблону типового объекта
    private static function copyTypicalParametersToSensor($typical_object_id, $sensor_id)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        try {
            $microtime_start = microtime(true);
            //копирование параметров справочных
            $type_object_parameters = TypeObjectParameter::find()->where(['object_id' => $typical_object_id])->all();
            $sensor_parameters = SensorParameter::find()->where(['sensor_id' => $sensor_id])->asArray()->all();
            foreach ($sensor_parameters as $parameter) {
                $sp_handbook[$parameter['parameter_type_id']][$parameter['parameter_id']] = $parameter;
            }
            if ($type_object_parameters)                                                                                                               //Находим все параметры типового объекта
            {
                foreach ($type_object_parameters as $type_object_parameter) {
                    if (!isset($sp_handbook[$type_object_parameter->parameter_type_id][$type_object_parameter->parameter_id])) {
                        $sensor_parameter = new SensorParameter();
                        $sensor_parameter->parameter_id = $type_object_parameter->parameter_id;
                        $sensor_parameter->parameter_type_id = $type_object_parameter->parameter_type_id;
                        $sensor_parameter->sensor_id = $sensor_id;
                        if (!$sensor_parameter->save()) {
                            $errors[] = $sensor_parameter->errors;
                            throw new Exception("copyTypicalParametersToSensor. Не удалось сохранить SensorParameter");
                        }
                    }
                }
            }
        } catch (Throwable $ex) {
            $errors[] = "copyTypicalParametersToSensor. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result_main = array(
            'Items' => $result,
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings);
        return $result_main;
    }

    // FindSensorType - метод поиска типа сенсора параметр 338 для фронта - когда они не передают его при создании
    private static function FindSensorType($object_id)
    {
        $sensor_type = (new Query())
            ->select('value')
            ->from('view_GetLastTypeSensorTypeObject')
            ->where([
                'object_id' => $object_id
            ])
            ->scalar();
        return $sensor_type;
    }

    // FindSensorASUTP - метод поиска АСУТП сенсора параметр 337 для фронта - когда они не передают его при создании
    private static function FindSensorASUTP($object_id)
    {
        $sensor_asutp = (new Query())
            ->select('value')
            ->from('view_GetLastAsutpSensorTypeObject')
            ->where([
                'object_id' => $object_id
            ])
            ->scalar();
        return $sensor_asutp;
    }

    /*
     * GetPictureForObject - Метод возвращает путь к картинке для конкретного объекта для параметра 168
     */
    public static function GetPictureForObject($object_id)
    {
        $img_path = "";
        switch ($object_id) {
            case 104:
                $img_path = "/img/2d_models/specific_objects/sensors/metkaProch.png";
                break;
            case 45:
                $img_path = "/img/2d_models/specific_objects/sensors/NodeA.png";
                break;
            case 46:
            case 105:
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
     * Название метода: deleteSensorAll()
     * Назначение метода: Метод удаления сенсора из БД со всеми параметрами и значеними параметров из БД и в кэше.
     * Если указать параметр $del_cache = true, то данные удаляются и из кэша.
     *
     * Входные обязательные параметры:
     * @param $sensor_id - идентификатор сенсора
     *
     * Входные необязательные параметры
     * @param boolean $del_cache - удалит ли данные из кэше. По умолчанию не удаляются данные из кэша
     *
     * @package backend\controllers
     * @example  $this->deleteSensorAll(310, false) // удалить только из бд
     * @example (new SensorBasicController())->deleteSensorAll(310, true) // удалить из бд и из кэша
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 28.05.2019 16:11
     */
    public function deleteSensorAll($sensor_id, $del_cache = false)
    {
        Sensor::deleteAll(['id' => $sensor_id]);
        if ($del_cache == true) {
            $sensor_cache_controller = new SensorCacheController();
            $sensor_cache_controller->delInSensorMineHash($sensor_id, AMICUM_DEFAULT_MINE);
            $sensor_cache_controller->delParameterValueHash($sensor_id);
        }
    }

    /**
     * Название метода: deleteSensorParameter()
     * Назначение метода: Метод удаление параметров сенсора со значениями из БД и из кэша.
     * Если указать параметр $del_cache = true, то данные удаляются и из кэша.
     *
     * Входные обязательные параметры:
     * @param $sensor_id - идентификатор сенсора
     * @param boolean $del_cache - удалит ли данные из кэше. По умолчанию не удаляются данные из кэша
     *
     * Входные необязательные параметры
     * @param string $parameter_id - идентифкатор параметра. По умолчанию удаляет все парамтеры
     * @param string $parameter_type_id - идентификатор типа параметра. По умолчанию удаляет все типы парамтеров
     *
     * @package backend\controllers
     *
     * @example $this->deleteSensorParameter(310, false, $parameter_id = '*', $parameter_type_id = '*') // удалить только из бд
     * @example (new SensorBasicController())->deleteSensorParameter(310, false, $parameter_id = '*', $parameter_type_id = '*') // удалить только из бд
     * @example (new SensorBasicController())->deleteSensorParameter(310, true, $parameter_id = '*', $parameter_type_id = '*') // удалить из бд и в кэше
     *
     * Документация на портале:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 28.05.2019 16:00
     */
    public function deleteSensorParameter($sensor_id, $del_cache = false, $parameter_id = '*', $parameter_type_id = '*')
    {
        $sql_filter = 'sensor_id = ' . $sensor_id;
        if ($parameter_id !== '*') {
            $sql_filter .= ' AND parameter_id = ' . $parameter_id;
        }
        if ($parameter_type_id !== '*') {
            $sql_filter .= ' AND parameter_type_id = ' . $parameter_type_id;
        }
        SensorParameter::deleteAll($sql_filter);
        if ($del_cache == true) {
            (new SensorCacheController())->delParameterValueHash($sensor_id, $parameter_id, $parameter_type_id);
        }
    }

    /**
     * Название метода: addSensorParameterValue()
     * Назначение метода: Метод добавления значений в таблицу sensor_parameter_value.
     * Если указать $add_cache = true, то данные добавляются в кэш.
     * Так как значения параметра сенсора хранится еще в кэше SensorMine, можно и там изменить это значения
     * указав параметр $change_param_name = название параметра. Например если добавляем значение для параметра 122, то
     * $change_param_name = 'place_id'
     *
     * Входные обязательные параметры:
     * @param $sensor_parameter_id - идентификатор sensor_parameter
     * @param $value - значение
     * @param $status_id - статус
     *
     * Входные необязательные параметры
     * @param string $date_time - дата и время. По умолчанию текущее дата и время
     * @return array|int - если все успешно, то возвращает id, иначе массив ошибок
     *
     * @example SensorBasicController::addSensorParameterValue(43289, 'Новый координат', 19, $date_time = 'now()', $add_cache = true, $change_param_name = 'xyz');
     * @example SensorBasicController::addSensorParameterValue(43289, '6718', 19, $date_time = 'now()', $add_cache = true, $change_param_name = 'place_id');
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 29.05.2019 9:46
     */
    public static function addSensorParameterValue($sensor_parameter_id, $value, $status_id, $date_time = -1)
    {
        $sensor_parameter_value_id = -1;                                                                                  //ключ конкретного значения параметра
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = "addSensorParameterValue.Начал выполнять метод";
        try {
            if ($date_time == -1) {
                $date_time = Assistant::GetDateNow();
            }
            $sensor_parameter_value = new SensorParameterValue();
            $sensor_parameter_value->sensor_parameter_id = $sensor_parameter_id;
            $sensor_parameter_value->value = (string)$value;
            $sensor_parameter_value->status_id = $status_id;
            $sensor_parameter_value->date_time = $date_time;
            if ($sensor_parameter_value->save()) {
                $sensor_parameter_value->refresh();
                $sensor_parameter_value_id = $sensor_parameter_value->id;
                $status *= 1;
                $warnings[] = 'addSensorParameterValue. Значение сохранено в БД';
            } else {
                $errors[] = "addSensorParameterValue. Ошибка сохранения модели SensorParameterValue";
                $errors[] = $sensor_parameter_value->errors;
                throw new Exception("addSensorParameterValue. Сохранение данных в модель окончилось с ошибкой");
            }
        } catch (Throwable $e) {
            $status = 0;
            $sensor_parameter_value_id = null;
            $errors[] = "addSensorParameterValue.Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "addSensorParameterValue.Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'sensor_parameter_value_id' => $sensor_parameter_value_id);
        return $result_main;
    }

    /**
     * Название метода: addSensorParameterHandbookValue()
     * Назначение метода: Метод добавления значений в таблицу sensor_parameter_handbook_value.
     * Если указать $add_cache = true, то данные добавляются в кэш.
     * Так как значения параметра сенсора хранится еще в кэше SensorMine, можно и там изменить это значения
     * указав параметр $change_param_name = название параметра. Например если добавляем значение для параметра 122, то
     * $change_param_name = 'place_id'
     *
     * Входные обязательные параметры:
     * @param $sensor_parameter_id - идентификатор sensor_parameter
     * @param $value - значение
     * @param $status_id - статус
     *
     * Входные необязательные параметры
     * @param string $date_time - дата и время. По умолчанию текущее дата и время
     * @return array|int - если все успешно, то возвращает id, иначе массив ошибок
     *
     * @example SensorBasicController::addSensorParameterValue(43289, 'Новый координат', 19, $date_time = 'now()', $add_cache = true, $change_param_name = 'xyz');
     * @example SensorBasicController::addSensorParameterValue(43289, '6718', 19, $date_time = 'now()', $add_cache = true, $change_param_name = 'place_id');
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 29.05.2019 9:46
     */
    public static function addSensorParameterHandbookValue($sensor_parameter_id, $value, $status_id, $date_time = -1)
    {
        $sensor_parameter_value_id = -1;                                                                                  //ключ конкретного значения параметра
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = "addSensorParameterHandbookValue.Начал выполнять метод";
        try {
            if ($date_time == -1) {
                $date_time = Assistant::GetDateNow();
            }
            $sensor_parameter_value = new SensorParameterHandbookValue();
            $sensor_parameter_value->sensor_parameter_id = $sensor_parameter_id;
            $sensor_parameter_value->value = (string)$value;
            $sensor_parameter_value->status_id = $status_id;
            $sensor_parameter_value->date_time = $date_time;
            if ($sensor_parameter_value->save()) {
                $sensor_parameter_value->refresh();
                $sensor_parameter_value_id = $sensor_parameter_value->id;
                $status *= 1;
                $warnings[] = "addSensorParameterHandbookValue. Начал выполнять метод";
            } else {
                $errors[] = "addSensorParameterHandbookValue. Ошибка сохранения модели SensorParameterHandbookValue";
                $errors[] = $sensor_parameter_value->errors;
                throw new Exception("addSensorParameterHandbookValue. Сохранение данных в модель окончилось с ошибкой");
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "addSensorParameterHandbookValue.Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "addSensorParameterHandbookValue.Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'sensor_parameter_value_id' => $sensor_parameter_value_id);
        return $result_main;
    }

    /**
     * Название метода: getLastSensorParameterValue()
     * Назначение метода: getLastSensorParameterValue() - Метод получения последних значений вычисляемых значений из представления view_initSensorParameterValue
     *
     * Входные обязательные параметры:
     * @param $sensor_id - идентификатор конкретного сенсора
     *
     * Входные необязательные параметры
     * @param string $condition - фильтр поиска. Можно задать фильтр поиска.
     *
     * @return array|bool возвращает массив данных если они есть, иначе false
     *
     * @package backend\controllers
     *
     * @example $this->getLastSensorParameterValue(310);
     * @example $this->getLastSensorParameterValue(310, 'parameter_id = 122 OR parameter_id = 83');
     *
     * Документация на портале:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 29.05.2019 11:07
     */
    public function getLastSensorParameterValue($sensor_id, $condition = '')
    {
        $sql_filter = "sensor_id = $sensor_id";
        if ($condition != '') $sql_filter .= ' AND ' . $condition;
        $sensor_parameter_values = (new Query())
            ->select([
                'sensor_id',
                'sensor_parameter_id',
                'parameter_id',
                'parameter_type_id',
                'date_time',
                'value',
                'status_id'
            ])
            ->from('view_initSensorParameterValue')
            ->where($sql_filter)
            ->all();
        if ($sensor_parameter_values) {
            return $sensor_parameter_values;
        }
        return false;
    }

    /**
     * Название метода: getLastSensorParameterHandbookValue()
     * Назначение метода: getLastSensorParameterHandbookValue - Метод получения последних значений правочных значений из представления view_initSensorParameterHandbookValue
     *
     * Входные обязательные параметры:
     * @param $sensor_id - идентификатор конкретного сенсора
     *
     * Входные необязательные параметры
     * @param string $condition - фильтр поиска. Можно задать фильтр поиска.
     *
     * @return array|bool возвращает массив данных если они есть, иначе false
     *
     * @package backend\controllers
     *
     * @example $this->getLastSensorParameterValue(310);
     * @example $this->getLastSensorParameterValue(310, 'parameter_id = 122 OR parameter_id = 83');
     *
     * Документация на портале:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 29.05.2019 11:07
     */
    public function getLastSensorParameterHandbookValue($sensor_id, $condition = '')
    {
        $sql_filter = "sensor_id = $sensor_id";
        if ($condition != '') $sql_filter .= ' AND ' . $condition;
        $sensor_parameter_values = (new Query())
            ->select([
                'sensor_id',
                'sensor_parameter_id',
                'parameter_id',
                'parameter_type_id',
                'date_time',
                'value',
                'status_id'
            ])
            ->from('view_initSensorParameterHandbookValue')
            ->where($sql_filter)
            ->all();
        if ($sensor_parameter_values) {
            return $sensor_parameter_values;
        }
        return false;
    }

    // getSensorParameter - метод получения ключа конкретного параметра сенсора по входным параметрам из БД
    // входные параметры:
    //      $sensor_id          - ключ сенсора
    //      $parameter_id       - ключ параметра
    //      $parameter_type_id  - ключ типа параметра
    // выходные параметры:
    //      типовой набор параметров
    //      sensor_parameter_id - ключ конкретного параметра сенсора
    // пример использования : $response =
    // разработал: Якимов М.Н
    // дата: 02.06.2019
    public static function getSensorParameter($sensor_id, $parameter_id, $parameter_type_id)
    {
        $sensor_parameter_id = -1;
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'getSensorParameter. Начало выполнения метода';
        try {
            $sensor_parameters = SensorParameter::findOne(['parameter_type_id' => $parameter_type_id, 'parameter_id' => $parameter_id, 'sensor_id' => $sensor_id]);
            if ($sensor_parameters) {
                $sensor_parameter_id = $sensor_parameters['id'];
                $warnings[] = "getSensorParameter. Ключ конкретного параметра сенсора равен $sensor_parameter_id для сенсора $sensor_id и параметра $parameter_id и типа параметра $parameter_type_id";
                $status *= 1;
            } else {
                $response = self::addSensorParameter($sensor_id, $parameter_id, $parameter_type_id);
                if ($response['status'] == 1) {
                    $sensor_parameter_id = $response['sensor_parameter_id'];
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $status *= $response['status'];

                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception("getSensorParameter. Для сенсора $sensor_id не существует привязки к нему параметра $parameter_id и типа параметра $parameter_type_id");
                }
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'getSensorParameter. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'getSensorParameter. Закончил выполнение метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'sensor_parameter_id' => $sensor_parameter_id);
        return $result_main;
    }

    // addSensorParameter - метод добовление ключа конкретного параметра сенсора по входным параметрам в БД
    // входные параметры:
    //      $sensor_id          - ключ сенсора
    //      $parameter_id       - ключ параметра
    //      $parameter_type_id  - ключ типа параметра
    // выходные параметры:
    //      типовой набор параметров
    //      sensor_parameter_id - ключ конкретного параметра сенсора
    // пример использования : $response = self::addSensorParameter($sensor_id, $parameter_id, $parameter_type_id);
    // разработал: Якимов М.Н
    // дата: 02.06.2019
    public static function addSensorParameter($sensor_id, $parameter_id, $parameter_type_id)
    {
        $sensor_parameter_id = -1;
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'addSensorParameter. Начало выполнения метода';
        try {                                                                         //Если параметр не был найден
            $sensorParameter = new SensorParameter();                                                                   //создать новый объект модели SensorParameter
            $sensorParameter->sensor_id = $sensor_id;                                                                    //сохранить все поля
            $sensorParameter->parameter_id = $parameter_id;
            $sensorParameter->parameter_type_id = $parameter_type_id;
            if ($sensorParameter->save()) {                                           //сохранить модель в БД
                $sensorParameter->refresh();
                $sensor_parameter_id = $sensorParameter->id;
            } else {
                $errors[] = 'addSensorParameter. Ошибка сохранения модели SensorParameter';
                $errors[] = $sensorParameter->errors;
                throw new Exception("addSensorParameter. Для сенсора $sensor_id не удалось создать привязку параметра $parameter_id и типа параметра $parameter_type_id");
            }

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'addSensorParameter. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'addSensorParameter. Закончил выполнение метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'sensor_parameter_id' => $sensor_parameter_id);
        return $result_main;
    }


    /**
     * Название метода: getSensorMain() - метод получения списка сенсоров по шахте(SensorMine) из БД на текущий момент времени
     * Назначение метода: метод получения списка сенсоров по шахте(SensorMine) из БД на текущий момент времени
     * беруться только зачекиненые оборудования
     * Входные обязательные параметры:
     * @param int $mine_id - идентификатор шахты
     *
     * Входные необязательные параметры
     * @param int $sensor_id - идентификатор оборудования. Если указать конкретный, то только данные одного оборудования
     * добавляет в кэш
     *
     * @return array|bool - возвращает массив данных если есть, иначе возвращает false
     *
     * @package backend\controllers\cachemanagers
     *
     * @example
     *
     * @author Якимов М.Н.
     * Created date: on 31.05.2019 10:03
     */
    public static function getSensorMain($mine_id, $sensor_id = '*')
    {

        $sql_filter = "mine_id = $mine_id";
        if ($sensor_id != '*') $sql_filter .= " AND sensor_id = $sensor_id";

        $sensors = (new Query())
            ->select(
                [
                    'sensor_id',
                    'sensor_title',
                    'object_id',
                    'object_title',
                    'object_type_id',
                    'mine_id'
                ])
            ->from(['view_initSensorMine'])
            ->where($sql_filter)
            ->all();

        return $sensors;
    }

    /**
     * Название метода: getSensorParameterValue() - метод получения вычисляемых значений параметров сенсоров в БД SensorParameterValue
     *
     * Входные необязательные параметры
     * @param $sensor_id - идентификатор оборудования.
     * @param $parameter_id - ключ параметра
     * @param $parameter_type_id - ключ типа параметра
     *
     * @return array/bool возвращает true при успешном добавлении в кэш, иначе false
     *
     *
     *
     * @author Якимов М.Н.
     * Created date: on 31.05.2019 11:51
     */
    public static function getSensorParameterValue($sensor_id = '*', $parameter_id = '*', $parameter_type_id = '*')
    {
        $sql_filter = 'parameter_type_id = ' . $parameter_type_id;

        if ($parameter_type_id == '*') {
            $sql_filter = "parameter_type_id in (2, 3)";
        }

        if ($parameter_id !== '*') {
            $sql_filter .= " and parameter_id in ($parameter_id)";
        }

        if ($sensor_id !== '*') {
            $sql_filter .= " and sensor_id in ($sensor_id)";
        }

        $sensor_parameter_values = (new Query())
            ->select([
                'sensor_id',
                'sensor_parameter_id',
                'parameter_id',
                'parameter_type_id',
                'date_time',
                'value',
                'status_id'
            ])
            ->from('view_initSensorParameterValue')
            ->where($sql_filter)
            ->all();

        return $sensor_parameter_values;
    }

    /**
     * Название метода: getSensorParameterHandbookValue() - метод получения справочных значений параметров сенсоров в БД SensorParameterHandbookValue
     *
     * Входные необязательные параметры
     * @param $sensor_id - идентификатор оборудования.
     * @param $parameter_id - ключ параметра
     *
     * @return array/bool возвращает true при успешном добавлении в кэш, иначе false
     *
     *
     *
     * @author Якимов М.Н.
     * Created date: on 31.05.2019 11:51
     */
    public static function getSensorParameterHandbookValue($sensor_id = '*', $parameter_id = '*')
    {
        $sql_filter = 'parameter_type_id = 1';

        if ($parameter_id !== '*') {
            $sql_filter .= " and parameter_id in ($parameter_id)";
        }

        if ($sensor_id !== '*') {
            $sql_filter .= " and sensor_id in ($sensor_id)";
        }

        $sensor_parameter_handbook_values = (new Query())
            ->select([
                'sensor_id',
                'sensor_parameter_id',
                'parameter_id',
                'parameter_type_id',
                'date_time',
                'value',
                'status_id'
            ])
            ->from('view_initSensorParameterHandbookValue')
            ->where($sql_filter)
            ->all();

        return $sensor_parameter_handbook_values;
    }

    /**
     * Название метода: getSensorParameterHandbookValueByDate() - метод получения справочных значений параметров сенсоров в БД SensorParameterHandbookValue на заданную дату
     *
     * Входные необязательные параметры
     * @param $date_time_end - дата, на которую получаем значения
     * @param $sensor_id - идентификатор сенсора.
     * @param $parameter_id - ключ параметра ли строка или массиви
     * @param $object_id - ключ типового объекта или строка или массив
     * @param $object_type_id - ключ типа типового объекта или строка или массив
     *
     * @return array/bool возвращает true при успешном добавлении в кэш, иначе false
     *
     *
     *
     * @author Якимов М.Н.
     * Created date: on 29.01.2021 11:51
     */
    public static function getSensorParameterHandbookValueByDate($date_time_end, $sensor_id = '*', $parameter_id = '*', $object_id = '*', $object_type_id = '*')
    {
        $sql_filter = 'sensor_parameter.parameter_type_id = 1';

        if ($parameter_id !== '*') {
            if (is_array($parameter_id)) {
                $sql_filter .= " and parameter_id in (" . implode(",", $parameter_id) . ")";
            } else {
                $sql_filter .= " and parameter_id = $parameter_id";
            }
        }

        if ($object_id !== '*') {
            if (is_array($object_id)) {
                $sql_filter .= " and object_id in (" . implode(",", $object_id) . ")";
            } else {
                $sql_filter .= " and object_id = $object_id";
            }
        }

        if ($object_type_id !== '*') {
            if (is_array($object_type_id)) {
                $sql_filter .= " and object_type_id in (" . implode(",", $object_type_id) . ")";
            } else {
                $sql_filter .= " and object_type_id = $object_type_id";
            }
        }

        if ($sensor_id !== '*') {
            $sql_filter .= " and sensor_id = $sensor_id";
        }

        $date_time_end = date("Y-m-d H:i:s.U", strtotime($date_time_end));

        $sensor_parameter_handbook_values = (new Query())
            ->select('
                        sensor.id as sensor_id,
                        sensor.title as sensor_title,
                        object.id as object_id,
                        object.title as object_title,
                        sensor_parameter.parameter_id as parameter_id,
                        sensor_parameter_handbook_value.value as value
                    ')
            ->from('sensor')
            ->innerJoin('object', 'sensor.object_id=object.id')
            ->innerJoin('sensor_parameter', 'sensor_parameter.sensor_id=sensor.id')
            ->innerJoin('sensor_parameter_handbook_value', 'sensor_parameter_handbook_value.sensor_parameter_id=sensor_parameter.id')
            ->innerJoin("(
                        select sensor_parameter_handbook_value_to_date.sensor_parameter_id, max(sensor_parameter_handbook_value_to_date.date_time) as max_date_time
                        from (
                            select sensor_parameter_id, date_time from sensor_parameter_handbook_value where date_time<='" . $date_time_end . "'
                        ) sensor_parameter_handbook_value_to_date 
                        group by sensor_parameter_handbook_value_to_date.sensor_parameter_id
                    ) sensor_parameter_handbook_value_max",
                'sensor_parameter_handbook_value_max.sensor_parameter_id=sensor_parameter_handbook_value.sensor_parameter_id and sensor_parameter_handbook_value_max.max_date_time=sensor_parameter_handbook_value.date_time'
            )
            ->where($sql_filter)
            ->all();

        return $sensor_parameter_handbook_values;
    }

    /**
     * Название метода: getSensorParameterValueByDate() - метод получения измеренных/вычисленных значений параметров сенсоров в БД SensorParameterHandbookValue на заданную дату
     *
     * Входные необязательные параметры
     * @param $date_time_end - дата, на которую получаем значения
     * @param $sensor_id - идентификатор сенсора.
     * @param $parameter_id - ключ параметра или строка или массив
     * @param $object_id - ключ типового объекта или строка или массив
     * @param $object_type_id - ключ типа типового объекта или строка или массив
     *
     * @return array/bool возвращает true при успешном добавлении в кэш, иначе false
     *
     *
     *
     * @author Якимов М.Н.
     * Created date: on 29.01.2021 11:51
     */
    public static function getSensorParameterValueByDate($date_time_end, $sensor_id = '*', $parameter_id = '*', $object_id = '*', $object_type_id = '*')
    {
        $sql_filter = 'sensor_parameter.parameter_type_id in (2,3)';

        if ($parameter_id !== '*') {
            if (is_array($parameter_id)) {
                $sql_filter .= " and parameter_id in (" . implode(",", $parameter_id) . ")";
            } else {
                $sql_filter .= " and parameter_id = $parameter_id";
            }
        }

        if ($object_id !== '*') {
            if (is_array($object_id)) {
                $sql_filter .= " and object_id in (" . implode(",", $object_id) . ")";
            } else {
                $sql_filter .= " and object_id = $object_id";
            }
        }

        if ($object_type_id !== '*') {
            if (is_array($object_type_id)) {
                $sql_filter .= " and object_type_id in (" . implode(",", $object_type_id) . ")";
            } else {
                $sql_filter .= " and object_type_id = $object_type_id";
            }
        }

        if ($sensor_id !== '*') {
            $sql_filter .= " and sensor_id = $sensor_id";
        }

        $date_time_end = date("Y-m-d H:i:s.U", strtotime($date_time_end));

        $sensor_parameter_values = (new Query())
            ->select('
                        sensor.id as sensor_id,
                        sensor.title as sensor_title,
                        object.id as object_id,
                        object.title as object_title,
                        sensor_parameter.parameter_id as parameter_id,
                        sensor_parameter_value.value as value
                    ')
            ->from('sensor')
            ->innerJoin('object', 'sensor.object_id=object.id')
            ->innerJoin('sensor_parameter', 'sensor_parameter.sensor_id=sensor.id')
            ->innerJoin('sensor_parameter_value', 'sensor_parameter_value.sensor_parameter_id=sensor_parameter.id')
            ->innerJoin("(
                        select sensor_parameter_value_to_date.sensor_parameter_id, max(sensor_parameter_value_to_date.date_time) as max_date_time
                        from (
                            select sensor_parameter_id, date_time from sensor_parameter_value where date_time<='" . $date_time_end . "'
                        ) sensor_parameter_value_to_date 
                        group by sensor_parameter_value_to_date.sensor_parameter_id
                    ) sensor_parameter_value_max",
                'sensor_parameter_value_max.sensor_parameter_id=sensor_parameter_value.sensor_parameter_id and sensor_parameter_value_max.max_date_time=sensor_parameter_value.date_time'
            )
            ->where($sql_filter)
            ->all();

        return $sensor_parameter_values;
    }
}
