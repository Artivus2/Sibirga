<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers;

use Exception;
use frontend\models\Equipment;
use frontend\models\EquipmentParameter;
use frontend\models\EquipmentParameterHandbookValue;
use frontend\models\EquipmentParameterSensor;
use frontend\models\EquipmentParameterValue;
use frontend\models\Main;
use Throwable;
use Yii;
use yii\db\Query;

/**
 * Класс по работе с методами оборудований в БД. Вся логика происходит для работы с БД.
 * Другие лишние методы писать в этом классе нельзя.
 * Class EquipmentBasicController
 * @package backend\controllers
 */
class EquipmentBasicController
{
    // getEquipmentMain                     - метод получения списка оборудования по шахте(EquipmentMine) из БД на текущий момент времени
    // getEquipmentParameterValue           - метод получения вычисляемых значений параметров оборудования  в БД EquipmentParameterValue
    // getEquipmentParameterHandbookValue   - метод получения справочных значений параметров оборудования в БД EquipmentParameterHandbookValue
    // addMain                              - метод создания главного айди в БД
    // addEquipment                         - Метод добавления оборудования в БД.
    // addEquipmentParameterHandbookValue   - Метод добавления значений в таблицу sensor_parameter_handbook_value.
    // addEquipmentParameterValue           - метод добавления вычисляемых значений параметров оборудования
    // addEquipmentParameter                - метод добовление ключа конкретного параметра сенсора по входным параметрам в БД
    // addEquipmentParameterSensor          - метод привязки метки к оборудованию
    // addEquipmentBatch                    - Метод добавления оборудования в БД массовыми параметрами.

    // addMain - метод создания главного айди в БД
    private static function addMain($table_address, $db_address = 'amicum2')
    {
        $result = array();                                                                                                // промежуточный результирующий массив
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                              // массив предупреждений
        $status = 1;
        $main_id = false;
        $warnings[] = "addMain. Зашел в метод";
        try {
            $main = new Main();
            $main->table_address = $table_address;
            $main->db_address = $db_address;
            if ($main->save()) {
                $main_id = $main->id;
                $warnings[] = "addMain. Главный ключ сохранен и равен $main_id";
            } else {
                $errors[] = "addMain. Ошибка сохранения модели Main";
                $errors[] = $main->errors;
                throw new Exception("addMain. Ошибка создания главного ключа");
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'addMain. Исключение';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        unset($main);
        $warnings[] = "addMain. Вышел с метода";
        return array('Items' => $result, 'main_id' => $main_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Название метода: addEquipmentParameterHandbookValue()
     * Назначение метода: Метод добавления значений в таблицу sensor_parameter_handbook_value.
     * Если указать $add_cache = true, то данные добавляются в кэш.
     * Так как значения параметра сенсора хранится еще в кэше SensorMine, можно и там изменить это значения
     * указав параметр $change_param_name = название параметра. Например если добавляем значение для параметра 122, то
     * $change_param_name = 'place_id'
     *
     * Входные обязательные параметры:
     * @param $equipment_parameter_id - идентификатор sensor_parameter
     * @param $value - значение
     * @param $status_id - статус
     *
     * Входные необязательные параметры
     * @param $date_time - дата и время. По умолчанию текущее дата и время
     * @return array|int - если все успешно, то возвращает id, иначе массив ошибок
     *
     * @example SensorBasicController::addEquipmentParameterHandbookValue(43289, 'Новый координат', 19, $date_time = 'now()', $add_cache = true, $change_param_name = 'xyz');
     * @example SensorBasicController::addEquipmentParameterHandbookValue(43289, '6718', 19, $date_time = 'now()', $add_cache = true, $change_param_name = 'place_id');
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 29.05.2019 9:46
     */
    public static function addEquipmentParameterHandbookValue($equipment_parameter_id, $value, $status_id, $date_time = -1)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();//массив предупреждений
        $warnings[] = "addEquipmentParameterHandbookValue.Начал выполнять метод";
        try {
            if ($date_time == -1) {
                $date_time = Assistant::GetDateNow();
            }
            $equipment_parameter_value = new EquipmentParameterHandbookValue();
            $equipment_parameter_value->equipment_parameter_id = $equipment_parameter_id;
            $equipment_parameter_value->value = (string)$value;
            $equipment_parameter_value->status_id = $status_id;
            $equipment_parameter_value->date_time = $date_time;
            if ($equipment_parameter_value->save()) {
                $equipment_parameter_value->refresh();
                $equipment_parameter_value_id = $equipment_parameter_value->id;
                $status *= 1;
                $warnings[] = "addEquipmentParameterHandbookValue. Начал выполнять метод";
            } else {
                $errors[] = "addEquipmentParameterHandbookValue. Ошибка сохранения модели SensorParameterHandbookValue";
                $errors[] = $equipment_parameter_value->errors;
                throw new Exception("addEquipmentParameterHandbookValue. Сохранение данных в модель окончилось с ошибкой");
            }
        } catch (Throwable $e) {
            $status = 0;
            $equipment_parameter_value_id = null;
            $errors[] = "addEquipmentParameterHandbookValue.Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "addEquipmentParameterHandbookValue.Закончил выполнять метод";
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'equipment_parameter_value_id' => $equipment_parameter_value_id);
    }

    /**
     * Название метода: addEquipment() - Метод добавления оборудования в БД.
     * Назначение метода: Метод добавления оборудования в БД.
     *
     * Входные обязательные параметры:
     * @param $equipment_title - название сенсора
     * @param $object_id - ИД объекта, к которму относится сенсор
     * @param $parent_equipment_id - идентфикатор вышестоящего оборудования.
     * @param $inventory_number - инвентарный номер оборудования
     * @param $sap_id - ключ оборудования с сап
     * @param $date_time_sync - дата и время синхронизации записи
     *
     * Входные необязательные параметры
     * @param int $mine_id - идентфикатор шахты.
     *
     * @return array|int если данные успешно были добавлены, то ID нового сенсора, иначе ошибку
     * @package backend\controllers
     *
     * @example addEquipment("Sensor", 4, 4,12) - добавление в БД
     * @example addEquipment("Sensor", 4, 4,12, true, 290) -  добавление в БД и в кэш
     *
     * Документация на портале:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 27.05.2019 13:17
     */
    public static function addEquipment($equipment_title, $object_id, $parent_equipment_id, $mine_id = -1, $inventory_number = "", $sap_id = null, $date_time_sync = "")
    {
        $method_name = 'addEquipment. ';                                                                               // название логируемого метода
        $result = array();                                                                                              // промежуточный результирующий массив
        $equipment_parameter_handbook_values = null;
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                            // массив предупреждений
        $status = 1;
        $equipment_id = false;
        try {
            /**
             * создание главного айди объекта - сенсора
             */
            $response = self::addMain('equipment');
            if ($response['status'] == 1) {
                $main_id = $response['main_id'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . "Ошибка сохранения главного айди сенсора");
            }

            /**
             * сохранение самого сенсора и его полей
             */
            $equipment = new Equipment();
            $equipment->id = $main_id;
            $equipment->title = $equipment_title;
            $equipment->object_id = $object_id;
            $equipment->parent_equipment_id = $parent_equipment_id;
            if ($inventory_number != "") {
                $equipment->inventory_number = $inventory_number;
            }
            if ($sap_id) {
                $equipment->sap_id = $sap_id;
            }
            if ($date_time_sync != "") {
                $equipment->date_time_sync = $date_time_sync;
            }

            if ($equipment->save())                                                                                     // если данные успешно сохранились в БД, то веозвращаем id
            {
                $warnings[] = $method_name . " галвную модель сохранил $equipment->id";
            } else {
                $errors[] = $method_name . "Ошибка сохранения модели Sensor";
                $errors[] = $equipment->errors;
                throw new Exception($method_name . "Ошибка сохранения сенсора");                                // возвращаем ошибки
            }

            $equipment_id = $equipment->id;                                                                             // возвращаем идентификатор нового сенсора
            $warnings[] = $method_name . "Сенсор сохранен и ключ равен $equipment_id";
            $date_now = Assistant::GetDateNow();                                                                        // текущая дата и время вставки
            /**
             * сохранение в БД параметра Наименование
             */
            $response = self::addEquipmentParameter($equipment_id, 162, 1);
            if ($response['status'] == 1) {
                $equipment_parameter_id = $response['equipment_parameter_id'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . "Ошибка сохранения параметра 162 сенсора $equipment_id");
            }

            // создаем массив для вставки разовой в кеш
            $equipment_parameter_handbook_values[] = array(
                'equipment_id' => $equipment_id,
                'equipment_parameter_id' => $equipment_parameter_id,
                'parameter_id' => 162,
                'parameter_type_id' => 1,
                'date_time' => $date_now,
                'value' => $equipment_title,
                'status_id' => 1,
            );
            $equipment_parameter_handbook_values_to_db[] = array(
                'equipment_parameter_id' => $equipment_parameter_id,
                'date_time' => $date_now,
                'value' => $equipment_title,
                'status_id' => 1
            );

            /**
             * сохранение в БД параметра Типовой объект
             */
            $response = self::addEquipmentParameter($equipment_id, 274, 1);
            if ($response['status'] == 1) {
                $equipment_parameter_id = $response['equipment_parameter_id'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . "Ошибка сохранения параметра 274 (типовой объект) сенсора $equipment_id");
            }

            // создаем массив для вставки разовой в кеш
            $equipment_parameter_handbook_values[] = array(
                'equipment_id' => $equipment_id,
                'equipment_parameter_id' => $equipment_parameter_id,
                'parameter_id' => 274,
                'parameter_type_id' => 1,
                'date_time' => $date_now,
                'value' => $object_id,
                'status_id' => 1,
            );
            $equipment_parameter_handbook_values_to_db[] = array(
                'equipment_parameter_id' => $equipment_parameter_id,
                'date_time' => $date_now,
                'value' => $object_id,
                'status_id' => 1
            );
            /**
             * сохранение в БД параметра 2Д модель
             */
            $response = self::addEquipmentParameter($equipment_id, 168, 1);
            if ($response['status'] == 1) {
                $equipment_parameter_id = $response['equipment_parameter_id'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . "Ошибка сохранения параметра 168 (2Д модель) сенсора $equipment_id");
            }
            $image_path = SensorBasicController::GetPictureForObject($object_id);
            // создаем массив для вставки разовой в кеш
            $equipment_parameter_handbook_values[] = array(
                'equipment_id' => $equipment_id,
                'equipment_parameter_id' => $equipment_parameter_id,
                'parameter_id' => 168,
                'parameter_type_id' => 1,
                'date_time' => $date_now,
                'value' => $image_path,
                'status_id' => 1,
            );
            $equipment_parameter_handbook_values_to_db[] = array(
                'equipment_parameter_id' => $equipment_parameter_id,
                'date_time' => $date_now,
                'value' => $image_path,
                'status_id' => 1
            );

            /**
             * сохранение в БД параметра Шахтное поле справочный
             */
            $response = self::addEquipmentParameter($equipment_id, 346, 1);
            if ($response['status'] == 1) {
                $equipment_parameter_id = $response['equipment_parameter_id'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . "Ошибка сохранения параметра 346(Шахтное поле) сенсора $equipment_id");
            }
            /**
             * этот блок пишу, чтобы знать с какой шахты первый раз прошла инициализация - актуально для меток
             */
            // создаем массив для вставки разовой в кеш
            $equipment_parameter_handbook_values[] = array(
                'equipment_id' => $equipment_id,
                'equipment_parameter_id' => $equipment_parameter_id,
                'parameter_id' => 346,
                'parameter_type_id' => 1,
                'date_time' => $date_now,
                'value' => $mine_id,
                'status_id' => 1,
            );
            $equipment_parameter_handbook_values_to_db[] = array(
                'equipment_parameter_id' => $equipment_parameter_id,
                'date_time' => $date_now,
                'value' => $mine_id,
                'status_id' => 1
            );

            /**
             * сохранение в БД параметра Шахтное поле измеренный - это чтобы следующие разы сразу был этот показатель для меток
             */
            $response = self::addEquipmentParameter($equipment_id, 346, 2);
            if ($response['status'] == 1) {
                $equipment_parameter_id = $response['equipment_parameter_id'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . "Ошибка сохранения параметра 346(Шахтное поле) сенсора $equipment_id");
            }
            /**
             * этот блок пишу, чтобы знать с какой шахты первый раз прошла инициализация
             */

            // создаем массив для вставки разовой в кеш
            $equipment_parameter_handbook_values[] = array(
                'equipment_id' => $equipment_id,
                'equipment_parameter_id' => $equipment_parameter_id,
                'parameter_id' => 346,
                'parameter_type_id' => 2,
                'date_time' => $date_now,
                'value' => $mine_id,
                'status_id' => 1,
            );
            $equipment_parameter_values_to_db[] = array(
                'equipment_parameter_id' => $equipment_parameter_id,
                'date_time' => $date_now,
                'value' => 1,
                'status_id' => 1
            );

            /**
             * сохранение в БД параметра Состояние Оборудования
             */
            $response = self::addEquipmentParameter($equipment_id, 164, 3);
            if ($response['status'] == 1) {
                $equipment_parameter_id = $response['equipment_parameter_id'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . "Ошибка сохранения параметра 164 сенсора $equipment_id");
            }

            // создаем массив для вставки разовой в кеш
            $equipment_parameter_handbook_values[] = array(
                'equipment_id' => $equipment_id,
                'equipment_parameter_id' => $equipment_parameter_id,
                'parameter_id' => 164,
                'parameter_type_id' => 3,
                'date_time' => $date_now,
                'value' => 1,
                'status_id' => 1,
            );
            $equipment_parameter_values_to_db[] = array(
                'equipment_parameter_id' => $equipment_parameter_id,
                'date_time' => $date_now,
                'value' => 1,
                'status_id' => 1
            );

            $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert(
                'equipment_parameter_handbook_value',
                ['equipment_parameter_id', 'date_time', 'value', 'status_id'],
                $equipment_parameter_handbook_values_to_db
            )->execute();
            if (!$insert_result_to_MySQL) {
                throw new \Exception($method_name . 'Ошибка массовой вставки в equipment_parameter_handbook_value' . $insert_result_to_MySQL);
            }
            $warnings[] = "закончил вставку данных в equipment_parameter_handbook_value";

            $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert(
                'equipment_parameter_value',
                ['equipment_parameter_id', 'date_time', 'value', 'status_id'],
                $equipment_parameter_values_to_db
            )->execute();
            if (!$insert_result_to_MySQL) {
                throw new \Exception($method_name . 'Ошибка массовой вставки в equipment_parameter_value' . $insert_result_to_MySQL);
            }
            $warnings[] = "закончил вставку данных в equipment_parameter_value";

            unset($response);
            unset($equipment_parameter_id);
            unset($date_now);
            unset($mine_id);
            unset($equipment);

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'addEquipment. Исключение';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = $method_name . "Вышел с метода";

        return array('Items' => $result, 'equipment_id' => $equipment_id, 'equipment_parameter_handbook_value' => $equipment_parameter_handbook_values, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * Название метода: addEquipmentParameterValue()
     * Назначение метода: метод добавления вычисляемых значений параметров оборудования
     *
     * Входные обязательные параметры:
     * @param $equipment_parameter_id - идентификатор параметра оборудования
     * @param $value - значение
     * @param $status_id - статус параметра
     *
     * @param $date_time - дата и время. По умолчанию текущая дата и время.
     * @return int|array Если данные успешно сохранились, то возвращает id, иначе false
     *
     * Входные необязательные параметры
     * @package backend\controllers
     *
     * @example $this->addEquipmentParameterValue(4785, 'Pop', 19);
     * @example $this->addEquipmentParameterValue(4785, 'Pop', 19, '2019-05-06 09:50:78');
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 06.06.2019 15:02
     */
    public static function addEquipmentParameterValue($equipment_parameter_id, $value, $status_id, $date_time = 1)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();

        try {
            if ($date_time == 1) {
                $date_time = Assistant::GetDateNow();
            }
            $equipment_parameter_value = new EquipmentParameterValue();
            $equipment_parameter_value->equipment_parameter_id = $equipment_parameter_id;
            $equipment_parameter_value->value = (string)$value;
            $equipment_parameter_value->status_id = $status_id;
            $equipment_parameter_value->date_time = $date_time;
            if ($equipment_parameter_value->save()) {
                $equipment_parameter_value->refresh();
                $equipment_parameter_value_id = $equipment_parameter_value->id;
                $warnings[] = 'addEquipmentParameterValue. Значение сохранено в БД';
            } else {
                $errors[] = 'addEquipmentParameterValue. Ошибка сохранения модели EquipmentParameterValue';
                $errors[] = $equipment_parameter_value->errors;
                throw new Exception('addEquipmentParameterValue. Сохранение данных в модель окончилось с ошибкой');
            }
        } catch (Throwable $exception) {
            $status = 0;
            $equipment_parameter_value_id = null;
            $errors[] = 'addEquipmentParameterValue.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $result, 'status' => $status, 'errors' => $errors,
            'warnings' => $warnings, 'equipment_parameter_value_id' => $equipment_parameter_value_id);
    }

    // addEquipmentParameter - метод добовление ключа конкретного параметра сенсора по входным параметрам в БД
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
    public static function addEquipmentParameter($equipment_id, $parameter_id, $parameter_type_id)
    {
        $equipment_parameter_id = -1;
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = "addEquipmentParameter. Начало выполнения метода";
        try {                                                                         //Если параметр не был найден
            $equipmentParameter = new EquipmentParameter();                                                                   //создать новый объект модели SensorParameter
            $equipmentParameter->equipment_id = $equipment_id;                                                                    //сохранить все поля
            $equipmentParameter->parameter_id = $parameter_id;
            $equipmentParameter->parameter_type_id = $parameter_type_id;
            if ($equipmentParameter->save()) {                                           //сохранить модель в БД
                $equipmentParameter->refresh();
                $equipment_parameter_id = $equipmentParameter->id;
            } else {
                $errors[] = 'addEquipmentParameter. Ошибка сохранения модели SensorParameter';
                $errors[] = $equipmentParameter->errors;
                throw new Exception("addEquipmentParameter. Для сенсора $equipment_id не удалось создать привязку параметра $parameter_id и типа параметра $parameter_type_id");
            }

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "addEquipmentParameter. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "addEquipmentParameter. Закончил выполнение метода";
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'equipment_parameter_id' => $equipment_parameter_id);
    }

    // addEquipmentParameterSensor - метод привязки метки к оборудованию
    public static function addEquipmentParameterSensor($equipment_parameter_id, $sensor_id, $date_time = -1)
    {
        $equipment_parameter_sensor_id = -1;                                                                                  //ключ конкретного значения параметра
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = "addEquipmentParameterSensor.Начал выполнять метод";
        try {
            if ($date_time == -1) {
                $date_time = Assistant::GetDateNow();
            }
            $equipment_parameter_sensor = new EquipmentParameterSensor();
            $equipment_parameter_sensor->equipment_parameter_id = $equipment_parameter_id;
            $equipment_parameter_sensor->sensor_id = $sensor_id;
            $equipment_parameter_sensor->date_time = $date_time;
            if ($equipment_parameter_sensor->save()) {
                $equipment_parameter_sensor->refresh();
                $equipment_parameter_sensor_id = $equipment_parameter_sensor->id;
                $status *= 1;
                $warnings[] = "addEquipmentParameterSensor. Начал выполнять метод";
            } else {
                $errors[] = "addEquipmentParameterSensor. Ошибка сохранения модели EquipmentParameterSensor";
                $errors[] = $equipment_parameter_sensor->errors;
                throw new Exception("addEquipmentParameterSensor. Сохранение данных в модель окончилось с ошибкой");
            }
        } catch (Throwable $e) {
            $status = 0;
            $sensor_parameter_value_id = null;
            $errors[] = "addEquipmentParameterSensor.Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "addEquipmentParameterSensor.Закончил выполнять метод";
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, '$equipment_parameter_sensor_id' => $equipment_parameter_sensor_id);
    }


    /**
     * Название метода: getEquipmentMain()
     * Назначение метода: метод получения списка оборудования по шахте(EquipmentMine) из БД на текущий момент времени
     * беруться только зачекиненые оборудования
     * Входные обязательные параметры:
     * @param $mine_id - идентификатор шахты
     *
     * Входные необязательные параметры
     * @param $equipment_id - идентификатор оборудования. Если указать конкретный, то только данные одного оборудования
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
    public static function getEquipmentMain($mine_id, $equipment_id = '*')
    {
        $sql_filter = null;

        if ($mine_id != '*') {
            $sql_filter = "mine_id = $mine_id";
        }

        if ($equipment_id != '*' and $mine_id != '*') {
            $sql_filter .= " AND equipment_id = $equipment_id";
        } else if ($equipment_id != '*') {
            $sql_filter = " equipment_id = $equipment_id";
        }

        return (new Query())
            ->select(
                [
                    'equipment_id',
                    'equipment_title',
                    'object_id',
                    'object_title',
                    'object_type_id',
                    'mine_id'
                ])
            ->from(['view_initEquipmentMine'])
            ->where($sql_filter)
            ->all();
    }

    /**
     * Название метода: getEquipmentParameterValue() - метод получения вычисляемых значений параметров оборудования в БД EquipmentParameterValue
     *
     * Входные необязательные параметры
     * @param $equipment_id - идентификатор оборудования.
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
    public static function getEquipmentParameterValue($equipment_id = '*', $parameter_id = '*', $parameter_type_id = 2)
    {
        $sql_filter = 'parameter_type_id = ' . $parameter_type_id;

        if ($parameter_type_id == '*') {
            $sql_filter = "parameter_type_id in (2, 3)";
        }

        if ($parameter_id !== '*') {
            $sql_filter .= " and parameter_id = $parameter_id";
        }

        if ($equipment_id !== '*') {
            $sql_filter .= " and equipment_id = $equipment_id";
        }

        return (new Query())
            ->select([
                'equipment_id',
                'equipment_parameter_id',
                'parameter_id',
                'parameter_type_id',
                'date_time',
                'value',
                'status_id'
            ])
            ->from('view_initEquipmentParameterValue')
            ->where($sql_filter)
            ->all();
    }

    /**
     * Название метода: getEquipmentParameterHandbookValue() - метод получения справочных значений параметров оборудования в БД EquipmentParameterHandbookValue
     *
     * Входные необязательные параметры
     * @param $equipment_id - идентификатор оборудования.
     * @param $parameter_id - ключ параметра
     *
     * @return array/bool возвращает true при успешном добавлении в кэш, иначе false
     *
     *
     *
     * @author Якимов М.Н.
     * Created date: on 31.05.2019 11:51
     */
    public static function getEquipmentParameterHandbookValue($equipment_id = '*', $parameter_id = '*')
    {
        $sql_filter = 'parameter_type_id = 1';

        if ($parameter_id !== '*') {
            $sql_filter .= " and parameter_id = $parameter_id";
        }

        if ($equipment_id !== '*') {
            $sql_filter .= " and equipment_id = $equipment_id";
        }

        return (new Query())
            ->select([
                'equipment_id',
                'equipment_parameter_id',
                'parameter_id',
                'parameter_type_id',
                'date_time',
                'value',
                'status_id'
            ])
            ->from('view_initEquipmentParameterHandbookValue')
            ->where($sql_filter)
            ->all();
    }

    /**
     * Название метода: addEquipmentBatch - Метод добавления оборудования в БД массовыми параметрами.
     *
     * Входные обязательные параметры:
     * @param $equipment_title - название сенсора
     * @param $object_id - ИД объекта, к которму относится сенсор
     * @param $parent_equipment_id - идентфикатор вышестоящего оборудования.
     * @param $inventory_number - инвентарный номер оборудования
     * @param $sap_id - ключ оборудования с сап
     * @param $date_time_sync - дата и время синхронизации записи
     *
     * Входные необязательные параметры
     * @param int $mine_id - идентфикатор шахты.
     *
     * @return array|int если данные успешно были добавлены, то ID нового сенсора, иначе ошибку
     * @package backend\controllers
     *
     * @example addEquipment("Sensor", 4, 4,12) - добавление в БД
     * @example addEquipment("Sensor", 4, 4,12, true, 290) -  добавление в БД и в кэш
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 27.05.2019 13:17
     */
    public static function addEquipmentBatch($equipment_title, $object_id, $parent_equipment_id, $mine_id = -1, $inventory_number = "", $sap_id = null, $date_time_sync = "")
    {
        $method_name = 'addEquipmentBatch. ';                                                                           // название логируемого метода
        $result = array();                                                                                              // промежуточный результирующий массив
        $equipment_parameter_handbook_values = null;
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                            // массив предупреждений
        $status = 1;
        $equipment_id = false;
        try {
            /**
             * создание главного айди объекта - сенсора
             */
            $response = self::addMain('equipment');
            if ($response['status'] == 1) {
                $main_id = $response['main_id'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . "Ошибка сохранения главного айди сенсора");
            }

            /**
             * сохранение самого сенсора и его полей
             */
            $equipment = new Equipment();
            $equipment->id = $main_id;
            $equipment->title = $equipment_title;
            $equipment->object_id = $object_id;
            $equipment->parent_equipment_id = $parent_equipment_id;
            if ($inventory_number != "") {
                $equipment->inventory_number = $inventory_number;
            }
            if ($sap_id) {
                $equipment->sap_id = $sap_id;
            }
            if ($date_time_sync != "") {
                $equipment->date_time_sync = $date_time_sync;
            }

            if ($equipment->save())                                                                                     // если данные успешно сохранились в БД, то веозвращаем id
            {
                $warnings[] = $method_name . " галвную модель сохранил $equipment->id";
            } else {
                $errors[] = $method_name . "Ошибка сохранения модели Sensor";
                $errors[] = $equipment->errors;
                throw new Exception($method_name . "Ошибка сохранения сенсора");                                // возвращаем ошибки
            }

            $equipment_id = $equipment->id;                                                                             // возвращаем идентификатор нового сенсора
            $warnings[] = $method_name . "Сенсор сохранен и ключ равен $equipment_id";
            $date_now = Assistant::GetDateNow();                                                                        // текущая дата и время вставки

            $equipment_parameters[] = array(
                'parameter_id' => 162,
                'parameter_type_id' => 1,
                'equipment_id' => $equipment_id,
            );

            $equipment_parameters[] = array(
                'parameter_id' => 274,
                'parameter_type_id' => 1,
                'equipment_id' => $equipment_id,
            );

            $equipment_parameters[] = array(
                'parameter_id' => 168,
                'parameter_type_id' => 1,
                'equipment_id' => $equipment_id,
            );

            $equipment_parameters[] = array(
                'parameter_id' => 346,
                'parameter_type_id' => 1,
                'equipment_id' => $equipment_id,
            );

            $equipment_parameters[] = array(
                'parameter_id' => 346,
                'parameter_type_id' => 2,
                'equipment_id' => $equipment_id,
            );

            $equipment_parameters[] = array(
                'parameter_id' => 164,
                'parameter_type_id' => 3,
                'equipment_id' => $equipment_id,
            );

            $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert(
                'equipment_parameter',
                ['parameter_id', 'parameter_type_id', 'equipment_id'],
                $equipment_parameters
            )->execute();
            if (!$insert_result_to_MySQL) {
                throw new \Exception($method_name . 'Ошибка массовой вставки в equipment_parameter' . $insert_result_to_MySQL);
            }
            $warnings[] = "закончил вставку данных в equipment_parameter";

            unset($equipment_parameters);

            $equipment_parameters = EquipmentParameter::find()->where(['equipment_id' => $equipment_id])->all();

            foreach ($equipment_parameters as $equipment_parameter) {
                $equipment_parameters_hand[$equipment_parameter['parameter_id']][$equipment_parameter['parameter_type_id']] = $equipment_parameter['id'];
            }


            // создаем массив для вставки разовой в кеш
            $equipment_parameter_handbook_values[] = array(
                'equipment_id' => $equipment_id,
                'equipment_parameter_id' => $equipment_parameters_hand[162][1],
                'parameter_id' => 162,
                'parameter_type_id' => 1,
                'date_time' => $date_now,
                'value' => $equipment_title,
                'status_id' => 1,
            );
            $equipment_parameter_handbook_values_to_db[] = array(
                'equipment_parameter_id' => $equipment_parameters_hand[162][1],
                'date_time' => $date_now,
                'value' => $equipment_title,
                'status_id' => 1
            );


            // создаем массив для вставки разовой в кеш
            $equipment_parameter_handbook_values[] = array(
                'equipment_id' => $equipment_id,
                'equipment_parameter_id' => $equipment_parameters_hand[274][1],
                'parameter_id' => 274,
                'parameter_type_id' => 1,
                'date_time' => $date_now,
                'value' => $object_id,
                'status_id' => 1,
            );
            $equipment_parameter_handbook_values_to_db[] = array(
                'equipment_parameter_id' => $equipment_parameters_hand[274][1],
                'date_time' => $date_now,
                'value' => $object_id,
                'status_id' => 1
            );

            $image_path = SensorBasicController::GetPictureForObject($object_id);
            // создаем массив для вставки разовой в кеш
            $equipment_parameter_handbook_values[] = array(
                'equipment_id' => $equipment_id,
                'equipment_parameter_id' => $equipment_parameters_hand[168][1],
                'parameter_id' => 168,
                'parameter_type_id' => 1,
                'date_time' => $date_now,
                'value' => $image_path,
                'status_id' => 1,
            );
            $equipment_parameter_handbook_values_to_db[] = array(
                'equipment_parameter_id' => $equipment_parameters_hand[168][1],
                'date_time' => $date_now,
                'value' => $image_path,
                'status_id' => 1
            );


            /**
             * этот блок пишу, чтобы знать с какой шахты первый раз прошла инициализация - актуально для меток
             */
            // создаем массив для вставки разовой в кеш
            $equipment_parameter_handbook_values[] = array(
                'equipment_id' => $equipment_id,
                'equipment_parameter_id' => $equipment_parameters_hand[346][1],
                'parameter_id' => 346,
                'parameter_type_id' => 1,
                'date_time' => $date_now,
                'value' => $mine_id,
                'status_id' => 1,
            );
            $equipment_parameter_handbook_values_to_db[] = array(
                'equipment_parameter_id' => $equipment_parameters_hand[346][1],
                'date_time' => $date_now,
                'value' => $mine_id,
                'status_id' => 1
            );


            /**
             * этот блок пишу, чтобы знать с какой шахты первый раз прошла инициализация
             */

            // создаем массив для вставки разовой в кеш
            $equipment_parameter_handbook_values[] = array(
                'equipment_id' => $equipment_id,
                'equipment_parameter_id' => $equipment_parameters_hand[346][2],
                'parameter_id' => 346,
                'parameter_type_id' => 2,
                'date_time' => $date_now,
                'value' => $mine_id,
                'status_id' => 1,
            );
            $equipment_parameter_values_to_db[] = array(
                'equipment_parameter_id' => $equipment_parameters_hand[346][2],
                'date_time' => $date_now,
                'value' => 1,
                'status_id' => 1
            );


            // создаем массив для вставки разовой в кеш
            $equipment_parameter_handbook_values[] = array(
                'equipment_id' => $equipment_id,
                'equipment_parameter_id' => $equipment_parameters_hand[164][3],
                'parameter_id' => 164,
                'parameter_type_id' => 3,
                'date_time' => $date_now,
                'value' => 1,
                'status_id' => 1,
            );
            $equipment_parameter_values_to_db[] = array(
                'equipment_parameter_id' => $equipment_parameters_hand[164][3],
                'date_time' => $date_now,
                'value' => 1,
                'status_id' => 1
            );

            $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert(
                'equipment_parameter_handbook_value',
                ['equipment_parameter_id', 'date_time', 'value', 'status_id'],
                $equipment_parameter_handbook_values_to_db
            )->execute();
            if (!$insert_result_to_MySQL) {
                throw new \Exception($method_name . 'Ошибка массовой вставки в equipment_parameter_handbook_value' . $insert_result_to_MySQL);
            }
            $warnings[] = "закончил вставку данных в equipment_parameter_handbook_value";

            $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert(
                'equipment_parameter_value',
                ['equipment_parameter_id', 'date_time', 'value', 'status_id'],
                $equipment_parameter_values_to_db
            )->execute();
            if (!$insert_result_to_MySQL) {
                throw new \Exception($method_name . 'Ошибка массовой вставки в equipment_parameter_value' . $insert_result_to_MySQL);
            }
            $warnings[] = "закончил вставку данных в equipment_parameter_value";

            unset($response);
            unset($equipment_parameter_id);
            unset($date_now);
            unset($mine_id);
            unset($equipment);

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'addEquipment. Исключение';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = $method_name . "Вышел с метода";

        return array('Items' => $result, 'equipment_id' => $equipment_id, 'equipment_parameter_handbook_value' => $equipment_parameter_handbook_values, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }
}
