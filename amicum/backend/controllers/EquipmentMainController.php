<?php


namespace backend\controllers;

use backend\controllers\cachemanagers\EquipmentCacheController;
use frontend\models\EquipmentParameter;
use frontend\models\TypicalObject;

class EquipmentMainController
{
    // moveEquipmentMineInitCache - Метод переноса работников между кешами EquipmentMine, без инициализации базовых сведений из БД работника

    /**
     * Возвращает массив с информацией о привязке оборудования к сенсору.
     * Сначала обращается к кешу. Если в кеше нет данных, то выполняет запрос к
     * базе данных.
     *
     * В текущем виде возвращает массив вида:
     * [
     *  'sensor_id',
     *  'equipment_id'
     * ]
     *
     * @param int $sensor_id идентификатор сенсора
     * @return bool|mixed false, если нет данных
     *
     * @example EquipmentMainController::getEquipmentInfoBySensorId(123456)
     *
     * @author Сырцев А.П.
     * @since 04.06.2019
     */
    public static function getEquipmentInfoBySensorId($sensor_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = false;
        $warnings = array();
        try {
            $equipment_cache_controller = new EquipmentCacheController();
            $equipment_sensor_response = $equipment_cache_controller->getSensorEquipment($sensor_id);
            if ($equipment_sensor_response === false) {
                $errors[] = 'getEquipmentInfoBySensorId. Светильник не привязан к оборудованию';
//                $equipment_sensor_response = $equipment_cache_controller->initSensorEquipment('sensor_id = ' . $sensor_id)[0];
//                if ($equipment_sensor_response) {
//                    $result = $equipment_sensor_response[0];
//                }
            } else {
                $result = $equipment_sensor_response;
            }
        } catch (\Throwable $exception) {
            $status = 0;
            $errors[] = 'getEquipmentInfoBySensorId. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Возвращает последнее значение конкретного параметра у данного оборудования.
     * Сначала обращается к кешу. Если в кеше нет данных, то выполняет запрос к
     * базе данных.
     *
     * В текущем виде возвращает массив вида:
     * [
     *  'equipment_parameter_id',
     *  'parameter_id',
     *  'parameter_type_id',
     *  'date_time',
     *  'value',
     *  'status_id'
     * ]
     *
     * @param int $equipment_id идентификатор воркера
     * @param int $parameter_id идентификатор параметра
     * @param int $parameter_type_id идентификатор типа параметра
     * @return mixed false, если нет данных
     *
     * @example EquipmentMainController::getEquipmentParameterLastValue(1,2,3)
     *
     * @author Сырцев А.П.
     * @since 04.06.2019
     */
    public static function getEquipmentParameterLastValue($equipment_id, $parameter_id, $parameter_type_id)
    {
        $equipment_cache_controller = new EquipmentCacheController();
        $equipment_parameter_value = $equipment_cache_controller->getParameterValue($equipment_id, $parameter_id, $parameter_type_id);
        if ($equipment_parameter_value === false) {
            if ($parameter_type_id == 1) {
                $equipment_parameter_value = $equipment_cache_controller->initEquipmentParameterHandbookValue(
                    -1,
                    "equipment_id = $equipment_id AND parameter_type_id = $parameter_type_id AND parameter_id = $parameter_id"
                )[0];
            } else {
                $equipment_parameter_value = $equipment_cache_controller->initEquipmentParameterValue(
                    -1,
                    "equipment_id = $equipment_id AND parameter_type_id = $parameter_type_id AND parameter_id = $parameter_id"
                )[0];
            }

            $equipment_cache_controller->setParameterValue($equipment_id, $equipment_parameter_value);
        }

        return $equipment_parameter_value;
    }

    /**
     * Добавляет новую запись в таблицу equipment_parameter.
     * В таблице содержатся привязки объектов оборудования к их параметрам
     *
     * Возвращает массив вида:
     * [
     *  'Items',
     *  'status',
     *  'errors',
     *  'warnings',
     *  'equipment_parameter_id'
     * ]
     *
     * @param int $equipment_id идентификатор воркера
     * @param int $parameter_id идентификатор параметра
     * @param int $parameter_type_id идентификатор типа параметра
     * @return array
     *
     * @example EquipmentMainController::createEquipmentParameter(1,2,3)
     *
     * @author Сырцев А.П.
     * @since 04.06.2019
     */
    public static function createEquipmentParameter($equipment_id, $parameter_id, $parameter_type_id)
    {
        $equipment_parameter_id = -1;
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = "createEquipmentParameter. Начало выполнения метода";
        try {                                                                         //Если параметр не был найден
            $equipment_parameter = new EquipmentParameter();
            $equipment_parameter->equipment_id = $equipment_id;                                                                    //сохранить все поля
            $equipment_parameter->parameter_id = $parameter_id;
            $equipment_parameter->parameter_type_id = $parameter_type_id;
            if ($equipment_parameter->save()) {                                           //сохранить модель в БД
                $equipment_parameter->refresh();
                $equipment_parameter_id = $equipment_parameter->id;
            } else {
                $errors[] = 'Ошибка сохранения модели EquipmentParameter';
                $errors[] = $equipment_parameter->errors;
                throw new \Exception("createEquipmentParameter. Для сенсора $equipment_id не удалось создать привязку параметра $parameter_id и типа параметра $parameter_type_id");
            }

        } catch (\Throwable $e) {
            $status = 0;
            $errors[] = "createEquipmentParameter. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "createEquipmentParameter. Закончил выполнение метода";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'equipment_parameter_id' => $equipment_parameter_id);
        return $result_main;
    }


    /**
     * Возвращает идентификатор привязки воркера к конкретному параметру из
     * таблицы equipment_parameter.
     * Изначально обращается к кешу. Если в кеше нет данных, то выполняется
     * запрос к БД. Если в БД нет данных, то создается новая привязка параметра
     * к воркеру
     *
     * @param int $equipment_id идентификатор воркера
     * @param int $parameter_id идентификатор параметра
     * @param int $parameter_type_id идентификатор типа параметра
     * @return array
     *
     * @example EquipmentMainController::GetOrSetEquipmentParameter(1,2,3);
     *
     * @author Сырцев А.П.
     * @since 04.06.2019
     */
    public static function getOrSetEquipmentParameter($equipment_id, $parameter_id, $parameter_type_id)
    {
        $equipment_parameter_id = -1;
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'getOrSetEquipmentParameter. Начало выполнения метода';
        try {
            $equipment_cache_controller = new EquipmentCacheController();
            $equipment_parameter_value = $equipment_cache_controller->getParameterValue($equipment_id, $parameter_id, $parameter_type_id);
            if (!$equipment_parameter_value) {
                $equipment_parameters = EquipmentParameter::findOne(['parameter_type_id' => $parameter_type_id, 'parameter_id' => $parameter_id, 'equipment_id' => $equipment_id]);
                if ($equipment_parameters) {
                    $equipment_parameter_id = $equipment_parameters['id'];
                    $warnings[] = "getOrSetEquipmentParameter. Ключ конкретного параметра оборудования равен $equipment_parameter_id для оборудования $equipment_id и параметра $parameter_id и типа параметра $parameter_type_id";
                    $status *= 1;
                } else {
                    // создаем конкретный параметр в базе данных
                    $response = self::createEquipmentParameter($equipment_id, $parameter_id, $parameter_type_id);
                    if ($response['status'] == 1) {
                        $equipment_parameter_id = $response['equipment_parameter_id'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        $status *= $response['status'];
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new \Exception("GetOrSetEquipmentParameter. Для оборудования $equipment_id не существует привязки к нему параметра $parameter_id и типа параметра $parameter_type_id");
                    }
                }
            }
            else{
                $equipment_parameter_id = $equipment_parameter_value['equipment_parameter_id'];
                $status *= 1;
                $warnings[] = "GetOrSetEquipmentParameter.Значение конкретного параметра $equipment_parameter_id найдено в кеше";
            }
        } catch (\Throwable $e) {
            $status = 0;
            $errors[] = 'GetOrSetEquipmentParameter. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'GetOrSetEquipmentParameter. Закончил выполнение метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'equipment_parameter_id' => $equipment_parameter_id);
        return $result_main;
    }
    // moveEquipmentMineInitCache - Метод переноса оборудования между кешами EquipmentMine, без инициализации базовых сведений из БД работника
    // инициализирует новый кеш по старому значению с учетом новой шахты
    // если находит предыдущее значение параметра шахтного поля у работника, то удаляет его,
    // инициализирует новый кеш по старому значению с учетом новой шахты
    // потому сперва нужно переместить главный кеш, а затем сменить значение параметра этой шахты на другое
    // !!!!!! СМЕНЫ ЗНАЧЕНИЯ ПАРАМЕТРА 346 ЗДЕСЬ НЕТ!!! НАДО ДЕЛАТЬ ОТДЕЛЬНО!!!!
    //
    // разработал: Якимов М.Н.
    public static function moveEquipmentMineInitCache($equipment_id, $mine_id_new)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = "moveEquipmentMineInitCache. Начал выполнять метод";
        try {
            /**
             * блок получения старого значения главного кеша
             */
            $warnings[] = "moveEquipmentMineInitCache. Ищу текущий главный кеш сенсора " . $equipment_id;
            $equipments = (new EquipmentCacheController())->getEquipmentMineByEquipment($equipment_id);
            if ($equipments) {
                $warnings[] = "moveEquipmentMineInitCache. главный кеш сенсора получен: ";
                $warnings = $equipments;
            } else {
                throw new \Exception("moveEquipmentMineInitCache. Главный кеш сенсора не инициализирован. Не смог получить главный кеш сенсора: " . $equipment_id);
            }

            /**
             * Проверяем сменилась ли шахта, и если сменилась, то удаляем старый кеш
             */
            foreach ($equipments as $equipment_del) {
                $mine_id_last = $equipment_del['mine_id'];
                if ($mine_id_last != $mine_id_new) {
                    (new EquipmentCacheController())->delInEquipmentMine($equipment_id, $mine_id_last);
                    $warnings[] = "moveEquipmentMineInitCache. Удалил старый главный кеш сенсора" . $equipment_id;
                } else {
                    $warnings[] = "moveEquipmentMineInitCache. значение параметра шахтное поле сенсора не получено или не изменилось, старый главный кеш не удалялся" . $equipment_id;
                }


                //перепаковываю старый кеш в новый
                $equipment=EquipmentCacheController::buildStructureEquipment(
                    $equipment_del['equipment_id'],$equipment_del['equipment_title'],
                    $equipment_del['object_id'],$equipment_del['object_title'],
                    $equipment_del['object_type_id'],$mine_id_new);
            }

            /**
             * инициализируем новый кеш
             */
            $response = (new EquipmentCacheController())->addEquipment($equipment);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $warnings[] = 'moveEquipmentMineInitCache. Добавил сенсор в главный кеш';
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception("moveEquipmentMineInitCache. Не смог добавить сенсор в главный кеш");
            }
            unset($equipment);
            unset($mine_id_last);
            unset($mine_id_new);

        } catch (\Throwable $e) {
            $status = 0;
            $errors[] = "moveEquipmentMineInitCache. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "moveEquipmentMineInitCache. Выполнение метода закончил";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // AddMoveEquipmentMineInitDB - Метод переноса работника между кешами EquipmentMine с инициализацией базовых сведений из БД
    // если находит предыдущее значение параметра шахтного поля у работника, то проверяет сменилось ли оно или нет,
    // если сменилось, то удаляет старый кеш
    // инициализирует работников по новым значения
    //
    // разработал: Якимов М.Н.
    public static function AddMoveEquipmentMineInitDB($equipment)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = "AddMoveEquipmentMineInitDB. Начал выполнять метод";
        try {

            $equipmentCacheController = new EquipmentCacheController();
            $mine_id_new = $equipment['mine_id'];
            $equipment_id = $equipment['equipment_id'];
            /**
             * блок получения предыдущего значения парамтера шахтное поле работника
             */

            $warnings[] = "AddMoveEquipmentMineInitDB. Ищу предыдущее значение параметра шахтное поле у работника " . $equipment_id;
            $response = $equipmentCacheController->getParameterValue($equipment_id, 346, 2);
            if ($response) {
                $mine_id_last = $response['value'];
            } else {
                $mine_id_last=false;
            }
            /**
             * ПРоверяем сменилась ли шахта, и если сменилась, то удаляем старый кеш
             */
            if ($mine_id_last != false and $mine_id_last != $mine_id_new) {
                $equipmentCacheController->delEquipmentMine($equipment_id, $mine_id_last);
                $warnings[] = "AddMoveEquipmentMineInitDB. Удалил старый главный кеш работника" . $equipment_id;
            } else {
                $warnings[] = "AddMoveEquipmentMineInitDB. значение параметра шахтное поле работника не получено или не изменилось, старый главный кеш не удалялся" . $equipment_id;
            }
            /**
             * инициализируем новый кеш
             */
            $response = $equipmentCacheController->addEquipment($equipment);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $warnings[] = 'AddMoveEquipmentMineInitDB. Добавил работника в главный кеш';
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception("AddMoveEquipmentMineInitDB. Не смог добавить работника в главный кеш");
            }
            unset($equipment);
            unset($mine_id_last);
            unset($mine_id_new);

        } catch (\Throwable $e) {
            $status = 0;
            $errors[] = "AddMoveEquipmentMineInitDB. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "AddMoveEquipmentMineInitDB. Выполнение метода закончил";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * getSensorUniversalLastMine - Получение значения последний шахты для Любого работника
     * @param $equipment_id -   идентификатор работника
     * @return array         -   значение параметра работника, в котором лежит шахта
     * @return $parameter_type_id  -   тип параметра, в котором лежит шахта данного работника
     * @author Якимов М.Н.
     * @since 02.06.2019 Написан метод
     */
    public static function getEquipmentUniversalLastMine($equipment_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        try {

            $warnings[] = "getSensorUniversalLastMine. Начал выполнять метод";

            $equipment_mine = (new EquipmentCacheController())->getParameterValue($equipment_id, 346, 2);
            if ($equipment_mine) {
                $equipment_mine_id = $equipment_mine['value'];
                $status *= 1;
                $warnings[] = "getSensorUniversalLastMine. Получил последнюю шахту $equipment_mine_id";
            } else {
                $warnings[] = "getSensorUniversalLastMine. в кеше нет последнего значения шахты";
                $status *= 1;
                $equipment_mine_id = false;
            }

        } catch (\Throwable $e) {
            $status = 0;
            $equipment_mine_id = null;
            $errors[] = "getSensorUniversalLastMine.Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "getEquipmentUniversalLastMine. Вышел из метода";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'mine_id' => $equipment_mine_id);
        return $result_main;
    }
    // IsChangeEquipmentParameterValue - метод проверки необходимости записи измеренного или полученного значения в БД
    // входные параметры:
    //      $equipment_id                  -   ключ рабоника
    //      $parameter_id               -   ключ параметра сенсора
    //      $parameter_type_id          -   ключ типа параметра сенсора
    //      $parameter_value            -   проверяемое значение параметра сенсора
    //      $parameter_value_date_time  -   дата проверяемого значения параметра сенсора
    // выходные параметры:
    //      $flag_save              - флаг статуса записи в БД 0 - не записывать, 1 записывать
    //      $parameter_status_id    - статус предыдущего значения параметра в кеше  -используется для проверки изменения статуса горной выработки при сохранении в equipment_collection
    //      стандартный набор
    // разработал: Якимов М.Н. 14.06.2019
    public static function IsChangeEquipmentParameterValue($equipment_id, $parameter_id, $parameter_type_id, $parameter_value, $parameter_value_date_time, $equipment_parameter_value_cache_array = null)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        $flag_save = -1;
        $parameter_status_id=-1; // используется для проверки изменения статуса выработки с разрешенной на запрещенную - дабы разрешать независимо писать в БД от каких либо условий
        $warnings[] = "IsChangeEquipmentParameterValue. Начал выполнять метод";
        /**
         * Блок проверки на изменение значение параметра
         */
        try {
            /**
             * блок проверки наличия переданного ранее последнего значения в данный метод. если его нет, то ищем обычным способом из кеша, в любом другом случае считаем, что последнего значения нет
             * почему так: метод работаает штатным образом, когда надо проверить на изменение одного параметра. Но есди надо его использовать в методах массовой вставки, то в него передаются заранее все последние значения по данному сенсору
             */
            if ($equipment_parameter_value_cache_array) {
                if (isset($equipment_parameter_value_cache_array[$equipment_id][$parameter_type_id][$parameter_id])) {
                    //берем из полученных значений при массовой вставке
                    $equipment_parameter_value = $equipment_parameter_value_cache_array[$equipment_id][$parameter_type_id][$parameter_id]; // получаем предыдущее значение параметра/тега
                    unset($equipment_parameter_value_cache_array);
                }
                else{
                    $equipment_parameter_value=false;
                }
            } else {
                //берем из кеша напрямки
                $equipment_parameter_value = (new EquipmentCacheController())->getParameterValue($equipment_id, $parameter_id, $parameter_type_id); // получаем предыдущее значение параметра/тега
            }

            if ($equipment_parameter_value) {
                $parameter_status_id=$equipment_parameter_value['status_id'];
                $delta_time = strtotime($parameter_value_date_time) - strtotime($equipment_parameter_value['date_time']);
                $warnings[] = "IsChangeEquipmentParameterValue. текущая дата " . strtotime($parameter_value_date_time);
                $warnings[] = "IsChangeEquipmentParameterValue. ПРошлая дата " . strtotime($equipment_parameter_value['date_time']);
                $value_last = $equipment_parameter_value['value'];
                if ($parameter_value != $value_last) {
                    /**
                     * проверка на число - для не чисел пишем сразу, для чисел делаем проверку и откидываем дребезг значений
                     */
                    $warnings[] = "IsChangeEquipmentParameterValue. проверка на число";
                    $warnings[] = "IsChangeEquipmentParameterValue. Текущее значение число? (если нет значения, то строка): " . is_numeric($parameter_value);
                    $warnings[] = "IsChangeEquipmentParameterValue. Предыдущее значение число? (если нет значения, то строка): " . is_numeric($value_last);

                    if (is_numeric($parameter_value) and is_numeric($value_last))                                              // проверяем входные значения числа или нет
                    {
                        /**
                         * получаем максимальное значение данного параметра/тега для того, что бы вычислить погрешность
                         * в случае если полученное справочное значение число, то выполняем проверку
                         * иначе просто пишем в БД
                         * Проверка - если изменения текущего значения от пердыдущего меньше 0,01 - 1%, то в БД не пишем
                         */
                        $criteriy_handbook_values = (new EquipmentCacheController())->getParameterValue($equipment_id, $parameter_id, 1); // получаем уставку параметра тега
                        //максимальное значение существует в кеше, оно число и не равно 0. иначе просто пишем в БД
                        if ($criteriy_handbook_values and is_numeric($criteriy_handbook_values['value']) and $criteriy_handbook_values['value'] != 0) {
                            $criteriy_handbook_value = $criteriy_handbook_values['value'];
                            $warnings[] = "IsChangeEquipmentParameterValue. Значение число. Для параметра $parameter_id сенсора задана уставка $criteriy_handbook_value и она не 0. пишем в БД";
                            $accuracy = abs($parameter_value / $criteriy_handbook_value - $value_last / $criteriy_handbook_value);
                            if ($accuracy > 0.01) {
                                $warnings[] = "IsChangeEquipmentParameterValue. Изменение числа $accuracy больше 0,01 (1%). пишем в БД";
                                $flag_save = 1;   //значение поменялось, пишем сразу
                            } else {
                                $warnings[] = "IsChangeEquipmentParameterValue. Изменение числа $accuracy меньше или равно 0,01 (1%). БД не пишем";
                                $flag_save = 0;   //значение поменялось, пишем сразу
                            }
                        } else {
                            $warnings[] = "IsChangeEquipmentParameterValue. Значение число. Для параметра $parameter_id сенсора НЕ задано максимальное значение в его справочном параметра. пишем в БД";
                            $flag_save = 1;   //значение поменялось, пишем сразу
                        }
                    } else {
                        $warnings[] = "IsChangeEquipmentParameterValue. Значение НЕ число. Значение параметра $parameter_id сенсора не число и оно изменилось. пишем в БД";
                        $flag_save = 1;   //значение поменялось, пишем сразу
                    }
                } elseif ($delta_time >= 60) {
                    $warnings[] = "IsChangeEquipmentParameterValue. Дельта времени: " . $delta_time;
                    $warnings[] = "IsChangeEquipmentParameterValue. Прошло больше 1 минуты с последней записи в БД. Пишем в БД";
                    $warnings[] = "IsChangeEquipmentParameterValue. Старое время: " . $equipment_parameter_value['date_time'];
                    $warnings[] = "IsChangeEquipmentParameterValue. Новое время: " . $parameter_value_date_time;
                    $flag_save = 1;   //прошло больше 5 минут с последней записи в БД, пишем сразу
                } else {
                    $flag_save = 0;
                    $warnings[] = "IsChangeEquipmentParameterValue. Значение не поменялось и время не прошло больше 1 минуты $delta_time";
                }
            } else {
                $warnings[] = "IsChangeEquipmentParameterValue. Нет предыдущих значений по параметру $parameter_id. пишем в БД сразу";
                $flag_save = 1;       //нет предыдущих данных, пишем сразу
            }
        } catch (\Throwable $ex) {
            $errors[] = "IsChangeEquipmentParameterValue. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        $warnings[] = "IsChangeEquipmentParameterValue. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'flag_save' => $flag_save,'parameter_status_id'=>$parameter_status_id);
        return $result_main;
    }
    /**
     * initEquipmentInCache - Метод инициализации конкретного оборудования в кэше.
     * Применяется в тех случаях, когда сенсор не существует в кеше если ключ
     * шахты не задан, то инициализация кеша сенсора осуществляется на базе того, что задано в шахте
     * @param $sensor_id -   идентификатор сенсора инициализируемого сенсора
     * @param $mine_id -   идентификатор шахты
     * @return $mine_id         -   значение параметра сенсора, в котором лежит шахта
     * @author Файзуллоев А.Э
     * @since 02.06.2019 Написан метод
     */
    public static function initEquipmentInCache($equipment_id, $mine_id = -1)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений

        try {
            $warnings[] = 'initEquipmentInCache. Начал выполнять метод';

            /**
             * инициализируем кеш измеренных параметров оборудования
             */
            $eqipmentCacheController = new EquipmentCacheController();
            $response = $eqipmentCacheController->initEquipmentParameterValue($equipment_id);
            if ($response === false)
            {
                throw new \Exception('Провел инициализации вычисляемых значений параметров оборудования в кэше EquipmentParameter');
            }
            /**
             * инициализируем кеш справочных параметров оборудования
             */
            $response = $eqipmentCacheController->initEquipmentParameterHandbookValue($equipment_id);
            if ($response === false)
            {
                throw new \Exception('Провел инициализации справочных значений параметров оборудования в кэше EquipmentParameter');
            }

            if ($mine_id == -1) {


                $equipment_object = $eqipmentCacheController->getParameterValue($equipment_id, 274, 1);

                if ($equipment_object) {
                    $sensor_type_object = TypicalObject::findOne(['id' => $equipment_object['value']]);
                    if ($sensor_type_object) {
                        $object_type_id = $sensor_type_object['object_type_id'];
                        if ($object_type_id == 22 || $object_type_id == 116 || $object_type_id == 95 || $object_type_id == 96 || $object_type_id == 28) {
                            $sensor_parameter_value_mine = $eqipmentCacheController->getParameterValue($equipment_id, 346, 1);
                        } else {
                            $sensor_parameter_value_mine = $eqipmentCacheController->getParameterValue($equipment_id, 346, 2);
                        }

                        if ($sensor_parameter_value_mine) {
                            $mine_id = $sensor_parameter_value_mine['value'];
                        } else {
                            throw new \Exception("initEquipmentInCache. Параметр шахты не сконфигурирован для оборудования $equipment_id должным образом ключ ");
                        }
                    } else {
                        throw new \Exception('initEquipmentInCache. Типа типового объекта для object_id= ' . $equipment_object['value'] . " не существует");
                    }
                } else {
                    throw new \Exception("initEquipmentInCache. Параметр 274 (тип оборудования) object_type_id для сенсора $equipment_id не сконфигурирован");
                }
            }

            /**
             * инициализируем кеш оборудования
             */
            $response = $eqipmentCacheController->initEquipmentMain($mine_id, $equipment_id);
            $result = $response['Items'];
            $status *= $response['status'];
            $errors[] = $response['errors'];
            $warnings[] = $response['warnings'];

        } catch (\Throwable $e) {
            $status = 0;
            $errors[] = 'initEquipmentInCache.Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

}
