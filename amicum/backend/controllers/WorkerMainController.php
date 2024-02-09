<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers;

use backend\controllers\cachemanagers\WorkerCacheController;
use Exception;
use frontend\controllers\handbooks\DepartmentController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Place;
use frontend\models\Status;
use frontend\models\Worker;
use frontend\models\WorkerParameter;
use Throwable;
use Yii;
use yii\db\Query;

class WorkerMainController
{

    // moveWorkerMineInitCache      - Метод переноса работников между кешами WorkerMine, без инициализации базовых сведений из БД работника
    // IsChangeWorkerParameterValue - метод проверки необходимости записи измеренного или полученного значения в БД
    // addWorkerCollection - метод добовление сведений по работнику в сводный отчет история местоположения персонала и транспорта
    // bindWorkerToEnterprise Метод создание привзяка каждого сотрудника к предприятиям в кэш и в БД (sensor_parameter_value)

    /**
     * Возвращает массив с информацией о привязке воркера к сенсору.
     * Сначала обращается к кешу. Если в кеше нет данных, то выполняет запрос к
     * базе данных.
     *
     * В текущем виде возвращает массив вида:
     * [
     *  'sensor_id',
     *  'worker_id'
     * ]
     *
     * @param int $sensor_id идентификатор сенсора
     * @return bool|mixed false, если нет данных
     *
     * @example WorkerMainController::getWorkerInfoBySensorId(123456)
     *
     * @author Сырцев А.П.
     * @since 04.06.2019
     */
    public static function getWorkerInfoBySensorId($sensor_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = false;
        $warnings = array();
        try {
            $worker_cache_controller = new WorkerCacheController();
            $worker_sensor_response = $worker_cache_controller->getSensorWorker($sensor_id);
            if ($worker_sensor_response === false) {
                $errors[] = 'getWorkerInfoBySensorId. Светильник не привязан к работнику. Sensor_id=' . $sensor_id;
//                $worker_sensor_response = $worker_cache_controller->initSensorWorker('sensor_id = ' . $sensor_id)[0];
//                if ($worker_sensor_response) {
//                    $result = $worker_sensor_response[0];
//                }
            } else {
                $result = $worker_sensor_response;
            }

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'getWorkerInfoBySensorId. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Возвращает последнее значение конкретного параметра у данного воркера.
     * Сначала обращается к кешу. Если в кеше нет данных, то выполняет запрос к
     * базе данных.
     *
     * В текущем виде возвращает массив вида:
     * [
     *  'worker_parameter_id',
     *  'parameter_id',
     *  'parameter_type_id',
     *  'date_time',
     *  'value',
     *  'status_id'
     * ]
     *
     * @param int $worker_id идентификатор воркера
     * @param int $parameter_id идентификатор параметра
     * @param int $parameter_type_id идентификатор типа параметра
     * @return mixed false, если нет данных
     *
     * @example WorkerMainController::getWorkerParameterLastValue(1,2,3)
     *
     * @author Сырцев А.П.
     * @since 04.06.2019
     */
    public static function getWorkerParameterLastValue($worker_id, $parameter_id, $parameter_type_id)
    {
        $worker_cache_controller = new WorkerCacheController();
        $worker_parameter_value = $worker_cache_controller->getParameterValueHash($worker_id, $parameter_id, $parameter_type_id);
        if ($worker_parameter_value === false) {
            if ($parameter_type_id == 1) {
                $worker_parameter_value = $worker_cache_controller->initWorkerParameterHandbookValueHash($worker_id, "parameter_type_id = $parameter_type_id AND parameter_id = $parameter_id");
            } else {
                $worker_parameter_value = $worker_cache_controller->initWorkerParameterValueHash($worker_id, "parameter_type_id = $parameter_type_id AND parameter_id = $parameter_id");
            }

            if ($worker_parameter_value) {
                $worker_parameter_value = $worker_parameter_value[0];
            } else {
                $response = self::createWorkerParameter($worker_id, $parameter_id, $parameter_type_id);
                if ($response['status'] == 1) {
                    $worker_parameter_value = WorkerCacheController::buildStructureWorkerParametersValue($worker_id, $response['$worker_parameter_id'], $parameter_id, $parameter_type_id, Assistant::GetDateNow(), 0, 1);
                } else {
                    $worker_parameter_value = false;
                }
            }
        }

        return $worker_parameter_value;
    }

    /**
     * Добавляет новую запись в таблицу worker_parameter.
     * В таблице содержатся привязки объектов воркеров к их параметрам
     *
     * Возвращает массив вида:
     * [
     *  'Items',
     *  'status',
     *  'errors',
     *  'warnings',
     *  'worker_parameter_id'
     * ]
     *
     * @param int $worker_id идентификатор воркера
     * @param int $parameter_id идентификатор параметра
     * @param int $parameter_type_id идентификатор типа параметра
     * @return array
     *
     * @example WorkerMainController::createWorkerParameter(1,2,3)
     *
     * @author Сырцев А.П.
     * @since 04.06.2019
     */
    public static function createWorkerParameter($worker_id, $parameter_id, $parameter_type_id)
    {
        $worker_parameter_id = -1;
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'createWorkerParameter. Начало выполнения метода';
        try {                                                                         //Если параметр не был найден
            $worker_object_id = (new Query())
                ->select('id')
                ->from('worker_object')
                ->where([
                    'worker_id' => $worker_id
                ])
                ->limit(1)
                ->scalar();

            $warnings['$worker_object_id'] = $worker_object_id;

            if ($worker_object_id === false)
                throw new Exception(__FUNCTION__ . '. В БД не существует worker_object с таким worker_id ' . $worker_id);

            $worker_parameter = new WorkerParameter();
            $worker_parameter->worker_object_id = (int)$worker_object_id;                                                                    //сохранить все поля
            $worker_parameter->parameter_id = $parameter_id;
            $worker_parameter->parameter_type_id = $parameter_type_id;
            if ($worker_parameter->save()) {                                           //сохранить модель в БД
                $worker_parameter->refresh();
                $worker_parameter_id = $worker_parameter->id;
            } else {
                $errors[] = 'Ошибка сохранения модели WorkerParameter';
                $errors[] = $worker_parameter->errors;
                throw new Exception("createWorkerParameter. Для сенсора $worker_id не удалось создать привязку параметра $parameter_id и типа параметра $parameter_type_id");
            }

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'createWorkerParameter. Исключение 1: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
            $errors[__FUNCTION__ . ' parameters'] = [
                '$worker_id' => $worker_id,
                '$parameter_id' => $parameter_id,
                '$parameter_type_id' => $parameter_type_id
            ];
        }
        $warnings[] = 'createWorkerParameter. Закончил выполнение метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'worker_parameter_id' => $worker_parameter_id);
    }


    /**
     * Возвращает идентификатор привязки воркера к конкретному параметру из
     * таблицы worker_parameter.
     * Изначально обращается к кешу. Если в кеше нет данных, то выполняется
     * запрос к БД. Если в БД нет данных, то создается новая привязка параметра
     * к воркеру
     *
     * @param int $worker_id идентификатор воркера
     * @param int $parameter_id идентификатор параметра
     * @param int $parameter_type_id идентификатор типа параметра
     * @return array
     *
     * @example WorkerMainController::GetOrSetWorkerParameter(1,2,3);
     *
     * @author Сырцев А.П.
     * @since 04.06.2019
     */
    public static function getOrSetWorkerParameter($worker_id, $parameter_id, $parameter_type_id)
    {
        $worker_parameter_id = -1;
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'getOrSetWorkerParameter. Начало выполнения метода';
        try {
            $worker_cache_controller = new WorkerCacheController();
            $worker_parameter_value = $worker_cache_controller->getParameterValueHash($worker_id, $parameter_id, $parameter_type_id);
            if (!$worker_parameter_value) {
                $worker_object_id = (new Query())
                    ->select('id')
                    ->from('worker_object')
                    ->where([
                        'worker_id' => $worker_id
                    ])
                    ->limit(1)
                    ->scalar();
                $worker_parameters = WorkerParameter::findOne(['parameter_type_id' => $parameter_type_id, 'parameter_id' => $parameter_id, 'worker_object_id' => $worker_object_id]);
                if ($worker_parameters) {
                    $worker_parameter_id = $worker_parameters['id'];
                    $warnings[] = "getOrSetWorkerParameter. Ключ конкретного параметра воркера равен $worker_parameter_id для воркера $worker_id и параметра $parameter_id и типа параметра $parameter_type_id";
                    $status *= 1;
                } else {
                    // создаем конкретный параметр в базе данных
                    $response = self::createWorkerParameter($worker_id, $parameter_id, $parameter_type_id);
                    if ($response['status'] == 1) {
                        $worker_parameter_id = $response['worker_parameter_id'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        $status *= $response['status'];
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new Exception("GetOrSetWorkerParameter. Для воркера $worker_id не существует привязки к нему параметра $parameter_id и типа параметра $parameter_type_id");
                    }
                }
            } else {
                $worker_parameter_id = $worker_parameter_value['worker_parameter_id'];
                $status *= 1;
                $warnings[] = "GetOrSetWorkerParameter.Значение конкретного параметра $worker_parameter_id найдено в кеше";
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'GetOrSetWorkerParameter. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
            $errors[__FUNCTION__ . ' parameters'] = [
                '$worker_id' => $worker_id,
                '$parameter_id' => $parameter_id,
                '$parameter_type_id' => $parameter_type_id
            ];
        }
        $warnings[] = 'GetOrSetWorkerParameter. Закончил выполнение метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'worker_parameter_id' => $worker_parameter_id);
    }
    // moveWorkerMineInitCache - Метод переноса работников между кешами WorkerMine, без инициализации базовых сведений из БД работника
    // инициализирует новый кеш по старому значению с учетом новой шахты
    // если находит предыдущее значение параметра шахтного поля у работника, то удаляет его,
    // инициализирует новый кеш по старому значению с учетом новой шахты
    // потому сперва нужно переместить главный кеш, а затем сменить значение параметра этой шахты на другое
    // !!!!!! СМЕНЫ ЗНАЧЕНИЯ ПАРАМЕТРА 346 ЗДЕСЬ НЕТ!!! НАДО ДЕЛАТЬ ОТДЕЛЬНО!!!!
    //
    // разработал: Якимов М.Н.
    public static function moveWorkerMineInitCache($worker_id, $mine_id_new)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = "moveWorkerMineInitCache. Начал выполнять метод";
        try {
            /**
             * блок получения старого значения главного кеша
             */
            $warnings[] = "moveWorkerMineInitCache. Ищу текущий главный кеш работника " . $worker_id;
            $worker_del = (new WorkerCacheController())->getWorkerMineByWorkerHash($worker_id);
            if ($worker_del) {
                $warnings[] = "moveWorkerMineInitCache. главный кеш работника получен: ";
                $warnings = $worker_del;
            } else {
                throw new Exception("moveWorkerMineInitCache. Главный кеш работника не инициализирован. Не смог получить главный кеш работника: " . $worker_id);
            }

            /**
             * Проверяем сменилась ли шахта, и если сменилась, то удаляем старый кеш
             */

            $mine_id_last = $worker_del['mine_id'];
            if ($mine_id_last != $mine_id_new) {
                (new WorkerCacheController())->delInWorkerMineHash($worker_id, $mine_id_last);
                $warnings[] = "moveWorkerMineInitCache. Удалил старый главный кеш работника" . $worker_id;
            } else {
                $warnings[] = "moveWorkerMineInitCache. значение параметра шахтное поле работника не получено или не изменилось, старый главный кеш не удалялся" . $worker_id;
            }


            //перепаковываю старый кеш в новый
            $worker = WorkerCacheController::buildStructureWorker($worker_del['worker_id'], $worker_del['worker_object_id'],
                $worker_del['object_id'], $worker_del['stuff_number'], $worker_del['full_name'],
                $mine_id_new, $worker_del['position_title'], $worker_del['department_title'],
                $worker_del['gender']);


            /**
             * инициализируем новый кеш
             */
            $response = (new WorkerCacheController())->addWorkerHash($worker);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $warnings[] = 'moveWorkerMineInitCache. Добавил работника в главный кеш';
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("moveWorkerMineInitCache. Не смог добавить работника в главный кеш");
            }
            unset($worker);
            unset($mine_id_last);
            unset($mine_id_new);

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "moveWorkerMineInitCache. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "moveWorkerMineInitCache. Выполнение метода закончил";

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // AddMoveWorkerMineInitDB - Метод переноса работника между кешами WorkerMine с инициализацией базовых сведений из БД
    // если находит предыдущее значение параметра шахтного поля у работника, то проверяет сменилось ли оно или нет,
    // если сменилось, то удаляет старый кеш
    // инициализирует работников по новым значения
    //
    // разработал: Якимов М.Н.
    public static function AddMoveWorkerMineInitDB($worker)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждени
        $warnings[] = "AddMoveWorkerMineInitDB. Начал выполнять метод";
        try {

            $workerCacheController = new WorkerCacheController();
            $mine_id_new = $worker['mine_id'];
            $worker_id = $worker['worker_id'];
            /**
             * блок получения предыдущего значения парамтера шахтное поле работника
             */

            $warnings[] = "AddMoveWorkerMineInitDB. Ищу предыдущее значение параметра шахтное поле у работника " . $worker_id;
            $response = $workerCacheController->getParameterValueHash($worker_id, 346, 2);
            if ($response) {
                $mine_id_last = $response['value'];
            } else {
                $mine_id_last = false;
            }
            /**
             * ПРоверяем сменилась ли шахта, и если сменилась, то удаляем старый кеш
             */
            if ($mine_id_last != false and $mine_id_last != $mine_id_new) {
                $workerCacheController->delWorkerMineHash($worker_id, $mine_id_last);
                $warnings[] = "AddMoveWorkerMineInitDB. Удалил старый главный кеш работника" . $worker_id;
            } else {
                $warnings[] = "AddMoveWorkerMineInitDB. значение параметра шахтное поле работника не получено или не изменилось, старый главный кеш не удалялся" . $worker_id;
            }
            /**
             * инициализируем новый кеш
             */
            $response = $workerCacheController->addWorkerHash($worker);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $warnings[] = 'AddMoveWorkerMineInitDB. Добавил работника в главный кеш';
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("AddMoveWorkerMineInitDB. Не смог добавить работника в главный кеш");
            }
            unset($worker);
            unset($mine_id_last);
            unset($mine_id_new);

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "AddMoveWorkerMineInitDB. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "AddMoveWorkerMineInitDB. Выполнение метода закончил";

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * getSensorUniversalLastMine - Получение значения последний шахты для Любого работника
     * @param $worker_id -   идентификатор работника
     * @return array         -   значение параметра работника, в котором лежит шахта
     * @return $parameter_type_id  -   тип параметра, в котором лежит шахта данного работника
     * @author Якимов М.Н.
     * @since 02.06.2019 Написан метод
     */
    public static function getWorkerUniversalLastMine($worker_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        try {

            $warnings[] = "getSensorUniversalLastMine. Начал выполнять метод";

            $worker_mine = (new WorkerCacheController())->getParameterValueHash($worker_id, 346, 2);
            if ($worker_mine) {
                $worker_mine_id = $worker_mine['value'];
                $status *= 1;
                $warnings[] = "getSensorUniversalLastMine. Получил последнюю шахту $worker_mine_id";
            } else {
                $warnings[] = "getSensorUniversalLastMine. в кеше нет последнего значения шахты";
                $status *= 1;
                $worker_mine_id = false;
            }

        } catch (Throwable $e) {
            $status = 0;
            $worker_mine_id = null;
            $errors[] = "getSensorUniversalLastMine.Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "getWorkerUniversalLastMine. Вышел из метода";

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'mine_id' => $worker_mine_id);
    }

    // IsChangeWorkerParameterValue - метод проверки необходимости записи измеренного или полученного значения в БД
    // входные параметры:
    //      $worker_id                  -   ключ работника
    //      $parameter_id               -   ключ параметра сенсора
    //      $parameter_type_id          -   ключ типа параметра сенсора
    //      $parameter_value            -   проверяемое значение параметра сенсора
    //      $parameter_value_date_time  -   дата проверяемого значения параметра сенсора
    // выходные параметры:
    //      $flag_save              - флаг статуса записи в БД 0 - не записывать, 1 записывать
    //      $parameter_status_id    - статус предыдущего значения параметра в кеше - используется для проверки изменения статуса горной выработки при сохранении в worker_collection
    //      стандартный набор
    // разработал: Якимов М.Н. 14.06.2019
    public static function IsChangeWorkerParameterValue($worker_id, $parameter_id, $parameter_type_id, $parameter_value, $parameter_value_date_time, $worker_parameter_value_cache_array = null)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        $flag_save = -1;
        $parameter_status_id = -1; // используется для проверки изменения статуса выработки с разрешенной на запрещенную - дабы разрешать независимо писать в БД от каких либо условий
        $warnings[] = "IsChangeWorkerParameterValue. Начал выполнять метод";
        /**
         * Блок проверки на изменение значение параметра
         */
        try {
            /**
             * блок проверки наличия переданного ранее последнего значения в данный метод. если его нет, то ищем обычным способом из кеша, в любом другом случае считаем, что последнего значения нет
             * почему так: метод работаает штатным образом, когда надо проверить на изменение одного параметра. Но есди надо его использовать в методах массовой вставки, то в него передаются заранее все последние значения по данному сенсору
             */
            if ($worker_parameter_value_cache_array) {
                if (isset($worker_parameter_value_cache_array[$worker_id][$parameter_type_id][$parameter_id])) {        //берем из полученных значений при массовой вставке
                    $worker_parameter_value = $worker_parameter_value_cache_array[$worker_id][$parameter_type_id][$parameter_id]; // получаем предыдущее значение параметра/тега
                    unset($worker_parameter_value_cache_array);
                } else {
                    $worker_parameter_value = false;
                }
            } else {                                                                                                    //берем из кеша напрямки
                $worker_parameter_value = (new WorkerCacheController())->getParameterValueHash($worker_id, $parameter_id, $parameter_type_id); // получаем предыдущее значение параметра/тега
            }

            if ($worker_parameter_value) {
                $parameter_status_id = $worker_parameter_value['status_id'];
                $delta_time = strtotime($parameter_value_date_time) - strtotime($worker_parameter_value['date_time']);
                $warnings[] = "IsChangeWorkerParameterValue. Текущая дата: " . strtotime($parameter_value_date_time);
                $warnings[] = "IsChangeWorkerParameterValue. Прошлая дата: " . strtotime($worker_parameter_value['date_time']);

                $value_last = $worker_parameter_value['value'];

                $warnings[] = "IsChangeWorkerParameterValue. Текущее значение: " . $parameter_value;
                $warnings[] = "IsChangeWorkerParameterValue. Предыдущее значение: " . $value_last;
                if ($parameter_value != $value_last) {
                    /**
                     * проверка на число - для не чисел пишем сразу, для чисел делаем проверку и откидываем дребезг значений
                     */
                    $warnings[] = "IsChangeWorkerParameterValue. проверка на число";
                    $warnings[] = "IsChangeWorkerParameterValue. Текущее значение число? (если нет значения, то строка): " . is_numeric($parameter_value);
                    $warnings[] = "IsChangeWorkerParameterValue. Предыдущее значение число? (если нет значения, то строка): " . is_numeric($value_last);

                    if (is_numeric($parameter_value) and is_numeric($value_last))                                       // проверяем входные значения числа или нет
                    {
                        /**
                         * получаем максимальное значение данного параметра/тега для того, что бы вычислить погрешность
                         * в случае если полученное справочное значение число, то выполняем проверку
                         * иначе просто пишем в БД
                         * Проверка - если изменения текущего значения от пердыдущего меньше 0,01 - 1%, то в БД не пишем
                         */
                        $criteriy_handbook_values = (new WorkerCacheController())->getParameterValueHash($worker_id, $parameter_id, 1); // получаем уставку параметра тега

                        if (                                                                                            //максимальное значение существует в кеше, оно число и не равно 0. иначе просто пишем в БД
                            $criteriy_handbook_values and is_numeric($criteriy_handbook_values['value']) and $criteriy_handbook_values['value'] != 0
                        ) {
                            $criteriy_handbook_value = $criteriy_handbook_values['value'];
                            $warnings[] = "IsChangeWorkerParameterValue. Значение число. Для параметра $parameter_id сенсора задана уставка $criteriy_handbook_value и она не 0. пишем в БД";
                            $accuracy = abs($parameter_value / $criteriy_handbook_value - $value_last / $criteriy_handbook_value);
                            if ($accuracy > 0.01) {                                                                     //значение поменялось, пишем сразу
                                $warnings[] = "IsChangeWorkerParameterValue. Изменение числа $accuracy больше 0,01 (1%). пишем в БД";
                                $flag_save = 1;
                            } else {                                                                                    //значение поменялось, пишем сразу
                                $warnings[] = "IsChangeWorkerParameterValue. Изменение числа $accuracy меньше или равно 0,01 (1%). БД не пишем";
                                $flag_save = 0;
                            }
                        } else {                                                                                        //значение поменялось, пишем сразу
                            $warnings[] = "IsChangeWorkerParameterValue. Значение число. Для параметра $parameter_id сенсора НЕ задано максимальное значение в его справочном параметра. пишем в БД";
                            $flag_save = 1;
                        }
                    } else {                                                                                            //значение поменялось, пишем сразу
                        $warnings[] = "IsChangeWorkerParameterValue. Значение НЕ число. Значение параметра $parameter_id сенсора не число и оно изменилось. пишем в БД";
                        $flag_save = 1;
                    }
                } elseif ($delta_time >= 300 or $delta_time < 0) {                                                      //прошло больше 5 минут с последней записи в БД, пишем сразу
                    $warnings[] = "IsChangeWorkerParameterValue. Дельта времени: " . $delta_time;
                    $warnings[] = "IsChangeWorkerParameterValue. Прошло больше 5 минут с последней записи в БД или пришел старый пакет. Пишем в БД";
                    $warnings[] = "IsChangeWorkerParameterValue. Старое время: " . $worker_parameter_value['date_time'];
                    $warnings[] = "IsChangeWorkerParameterValue. Новое время: " . $parameter_value_date_time;
                    $flag_save = 1;
                } else {
                    $flag_save = 0;
                    $warnings[] = "IsChangeWorkerParameterValue. Значение не поменялось и время не прошло больше 1 минуты $delta_time";
                }
            } else {
                $warnings[] = "IsChangeWorkerParameterValue. Нет предыдущих значений по параметру $parameter_id. пишем в БД сразу";
                $flag_save = 1;       //нет предыдущих данных, пишем сразу
            }
        } catch (Throwable $ex) {
            $errors[] = "IsChangeWorkerParameterValue. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        $warnings[] = "IsChangeWorkerParameterValue. Закончил выполнять метод";

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'flag_save' => $flag_save, 'parameter_status_id' => $parameter_status_id);
    }
    // addWorkerCollection - метод добовление сведений по работнику в сводный отчет история местоположения персонала и транспорта
    // Описание: метод работает так,определяеся последнее местоположение сенсора и дата когда оно обновлялось,
    // если место изменилось, то сразу пишем в БД, если место не изменилось, то в БД не пишем.
    // в данную таблицу фактически попадают только переходы между местами/зонами, а не вся локейшены, которые приходят.
    // если прошло больше 5 минут, то даже если не сменился плейс происходит запись данных при вызове этой функции
    // входные параметры:
    //      $worker_id              - ключ работника или оборудования
    //      $worker_status_id       - статус работника/обоурдования в данном месте Запретная/разрешенная и т.д.
    //      $place_id               - место в котором находится объект
    //      $date_time              - время измерения/получения координаты
    // выходные параметры:
    //      типовой набор параметров
    //      sensor_parameter_id     - ключ конкретного параметра сенсора
    //      worker_model            - модель работника
    // пример использования :
    // разработал: Якимов М.Н
    // дата: 14.06.2019
    public static function addWorkerCollection($worker_id, $worker_status_id, $place_id, $date_time)
    {
        $log = new LogAmicumFront("addWorkerCollection");

        $sensor_parameter_id = -1;
        $result = null;
        $flag_save = 0;
        $worker_model = null;

        try {
            $log->addLog("Начало выполнения метода");

            //требуемая структура
            /**
             * stat_id          - worker_status_id /разрешенная/запрещенная
             * status_worker    - worker_status_title - разрешенная/запрещенная
             *
             * date_work        - date_time время создания сообщения
             * kind_id          - place_kind_object_id
             * titleKind        - place_kind_object_title
             * type_id          - place_type_object_id
             * titleType        - place_type_object_title
             * titlePlace       - place_title
             *
             * titleObject      - worker_object_title
             * dep_id           - company_department_id
             * titleDepartment  - department_title*
             * titleCompany     - company_title
             * last_name        - full_name
             *
             */

            /**
             * получаем информацию о последнем записанном месте из кеша
             * блок проверки необходимости записи значения параметра в БД
             */
            $response = WorkerMainController::IsChangeWorkerParameterValue($worker_id, 122, 2, $place_id, $date_time);
            $log->addLogAll($response);

            if ($response['status'] != 1) {
                throw new Exception("Ошибка обработки флага сохранения значения в БД для работника $worker_id");
            }

            if ($response['flag_save']) {
                $flag_save = 1;
                $log->addLog("Запись в БД по причине смены параметра места или по времени последней записи");
            } else if ($response['parameter_status_id'] != -1 and $worker_status_id != $response['parameter_status_id']) {
                $flag_save = 1;

                $log->addLog("Запись в БД по причине смены статуса с 16 на 15. Последний статус: " . $response['parameter_status_id'] . ". Текущий статус: " . $worker_status_id);
            } else {
                $log->addLog("Запись в БД по причине смены статуса не производится. Последний статус: " . $response['parameter_status_id'] . ". Текущий статус: " . $worker_status_id);
            }

            $log->addLog("Флаг сохранения в БД получен и равен $flag_save");


            /** если статус  */
            if ($flag_save == 1) {
                /**
                 * Собираем входные параметры в массив
                 */
                $summary_report_add['stat_id'] = $worker_status_id;
                $status_model = Status::findOne(['id' => (int)$worker_status_id]);
                if ($status_model) {
                    $summary_report_add['status_worker'] = $status_model->title;
                } else {
                    throw new Exception("Для статуса $worker_status_id не удалось найти и его модель");
                }

                /**
                 * блок вычисления смены и даты смены
                 */
                $response = Assistant::GetShiftByDateTime($date_time);
                $summary_report_add['date_work'] = $response['date_work'];
                $summary_report_add['smena'] = $response['shift_title'];
                $summary_report_add['date_time_work'] = $date_time;

                /**
                 * Собираем информацию по месту в которое хотим писать
                 */
                if ($place_id == 'empty' or $place_id == "" or $place_id == -1) {
                    throw new Exception("место для работника : " . $worker_id . " не сконфигурировано и равно : " . $place_id);
                }

                $place_model = Place::find()
                    ->joinWith('object.objectType.kindObject')
                    ->where('place.id=' . $place_id)
                    ->limit(1)
                    ->one();
                if ($place_model) {
                    $summary_report_add['main_kind_place_id'] = $place_model->object->objectType->kindObject->id;
                    $summary_report_add['kind_id'] = $place_model->object->objectType->id;
                    $summary_report_add['titleKind'] = $place_model->object->objectType->title;
                    $summary_report_add['type_id'] = $place_model->object->id;
                    $summary_report_add['titleType'] = $place_model->object->title;
                    $summary_report_add['place_id'] = $place_id;
                    $summary_report_add['titlePlace'] = $place_model->title;
                } else {
                    throw new Exception("Для места $place_id не удалось найти типовой объект и его модель");
                }

                /**
                 * получаем Оставшуюся инфу о работнике, необходимую для записи в кеш
                 */
                $worker_model = Worker::find()
                    ->joinWith('companyDepartment.department')
                    ->joinWith('companyDepartment.company')
                    ->joinWith('employee')
                    ->joinWith('workerObjects.object')
                    ->where('worker.id=' . $worker_id)
                    ->limit(1)
                    ->one();

                if ($worker_model) {
                    if ($worker_model->workerObjects) {
                        $summary_report_add['titleObject'] = $worker_model->workerObjects[0]->object->title;
                    } else {
                        throw new Exception("Для работника $worker_id не задан его тип");
                    }
                    $summary_report_add['dep_id'] = $worker_model->companyDepartment->department->id;
                    $summary_report_add['titleDepartment'] = $worker_model->companyDepartment->department->title;
                    $summary_report_add['titleCompany'] = $worker_model->companyDepartment->company->title;
                    $summary_report_add['last_name'] = $worker_model->employee->last_name . " " . $worker_model->employee->first_name . " " . $worker_model->employee->patronymic;
                    $summary_report_add['worker_id'] = $worker_id;
                } else {
                    throw new Exception("Для работника $worker_id не удалось найти типовой объект и его модель");
                }
                /**
                 * сохраняем в БД в таблицу worker_collection значение
                 */
                $log->addLog("Начинаю массовую вставку значения в БД");

                $summary_report_add_array[] = $summary_report_add;

                $insert_result = Yii::$app->db->createCommand()->batchInsert('worker_collection',
                    [
                        'stat_id', 'status_worker', 'date_work', 'smena', 'date_time_work',
                        'main_kind_place_id', 'kind_id', 'titleKind',
                        'type_id', 'titleType', 'place_id', 'titlePlace', 'titleObject',
                        'dep_id', 'titleDepartment', 'titleCompany',
                        'last_name', 'worker_id'], $summary_report_add_array)->execute();

                if ($insert_result == 0) {
                    $log->addLog("Значение уже было в БД");
                }

                $result = $summary_report_add;
            } else {
                $log->addLog("Значение не изменилось, сохранять не надо");
            }
        } catch (Exception $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Закончил выполнение метода");

        return array_merge(['Items' => $result, 'sensor_parameter_id' => $sensor_parameter_id, 'worker_model' => $worker_model], $log->getLogAll());
    }

    /***
     * bindWorkerToEnterprise:  Метод создание привзяка каждого сотрудника к предприятиям в кэш воркеров и в БД (sensor_parameter_value)
     * $first_enterprise_value      - ключ первого подразделенеия (company_department_id)       (Заполярка  4029926)
     * $second_enterprise_value     - ключ второго подразделения (company_department_id)        (Воркутинка 4029860)
     * $default_value               - ключ подразделения по умолчанию, если не смог разделить человеков не туда, не туда Гость
     * Parameter(s): $first_enterprise_value, $second_enterprise_value, $default_value = '-1'
     * Return: $result = array('Items' => $items, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
     * date time: 2020:01:16
     * Creator: Fayzulloev A.E.
     * пример: http://127.0.0.1/super-test/bind-worker-to-enterprise
     */
    public static function bindWorkerToEnterprise($first_enterprise_value, $second_enterprise_value, $default_value = '-1')
    {

        $status = 1;
        $errors = array();
        $warnings = array();
        $response = null;
        $array_w_p_to_base = array();
        $array_departments_value_to_db = array();
        $array_departments_value_to_cache = array();
        $date_time_now = date('Y:m:d H:i:s', strtotime(Assistant::GetDateNow()));
        $method_name = "bindWorkerToEnterprise";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта
        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                         // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            // запись в БД начала выполнения скрипта
            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            /*** Получаем вложные департаменты первого предприятия ***/
            $response = DepartmentController::FindDepartment($first_enterprise_value);
            if ($response['status'] == 1) {
                $below_first_departments_array = $response['Items'];
                foreach ($below_first_departments_array as $below_first_department_item) {
                    $below_first_departments[$below_first_department_item] = $below_first_department_item;
                }
                $warnings[] = "bindWorkerToEnterprise. Получил выложные департаменты " . $first_enterprise_value;
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception('bindWorkerToEnterprise. Ошибка получения вложенных департаментов' . $first_enterprise_value);
            }
            unset($response);
            unset($below_first_departments_array);
            $count_all = count($below_first_departments);
            /** Отладка */
            $description = 'Получил вложные департаменты первого предприятия';                                             // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                         // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;  // количество обработанных записей
            $microtime_current = microtime(true);

            /*** Получаем вложные департаменты второго предприятия ***/
            $response = DepartmentController::FindDepartment($second_enterprise_value);
            if ($response['status'] == 1) {
                $below_second_departments_array = $response['Items'];
                foreach ($below_second_departments_array as $below_second_department_item) {
                    $below_second_departments[$below_second_department_item] = $below_second_department_item;
                }
                $warnings[] = "bindWorkerToEnterprise. Получил выложные департаменты " . $second_enterprise_value;
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception('bindWorkerToEnterprise. Ошибка получения вложенных департаментов' . $second_enterprise_value);
            }
            unset($response);
            unset($below_second_departments_array);
            $count_all = count($below_second_departments);
            /** Отладка */
            $description = 'Получил вложные департаменты второго предприятия';                                             // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                         // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;  // количество обработанных записей
            /*** Проверяем работники и worker_object, если не существует, то создаем worker_object***/
            $workers = (new Query())
                ->select('worker.id')
                ->from('worker')
                ->leftJoin('worker_object', 'worker.id=worker_object.worker_id')
                ->where('worker_object.id is null')
                ->all();

            // подготавливаем массив для массовой вставки конктреных работников в БД
            foreach ($workers as $worker) {
                $worker_item['worker_id'] = $worker['id'];
                $worker_item['object_id'] = 25;
                $worker_item['role_id'] = 9;
                $worker_array[] = $worker_item;
            }
            unset($worker_item);
            // массово за раз вставляем в БД конкретных работников
            if (isset($worker_array)) {
                $warnings[] = "Вставка данных в worker_object";
                $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert('worker_object', ['worker_id', 'object_id', 'role_id'], $worker_array)->execute();
                $warnings[] = "закончил вставку данных в worker_object";
                if (!$insert_result_to_MySQL) {
                    throw new Exception('bindWorkerToEnterprise. Ошибка массовой вставки конкретных работников в БД (worker_object) ' . $insert_result_to_MySQL);
                }
                $count_all = $insert_result_to_MySQL;
            }

            /** Отладка */
            $description = 'Вставил в БД работников у которых не было worker_object_id';                                             // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                         // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;  // количество обработанных записей
            unset($workers);
            unset($worker_array);


            // создаем работникам параметры у которых их нет
            $worker_objects = (new Query())
                ->select('worker_object.id as worker_object_id')
                ->from('worker_object')
                ->leftJoin('view_worker_parameter_18_1', 'worker_object.id=view_worker_parameter_18_1.worker_object_id')
                ->where('view_worker_parameter_18_1.id is null')
                ->all();

            foreach ($worker_objects as $worker_object) {
                $worker_object_item = array(
                    'worker_object_id' => (int)$worker_object['worker_object_id'],
                    'parameter_id' => 18,
                    'parameter_type_id' => 1,
                );
                $worker_object_array[] = $worker_object_item;
            }
            unset($worker_object_item);

            /*** Создаем параметры 18/1 для каждого воркера ***/
            if (isset($worker_object_array)) {
                $warnings[] = "bindWorkerToEnterprise. Начинаю вставку данных в worker_parameter";

                $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert('worker_parameter', ['worker_object_id', 'parameter_id', 'parameter_type_id'], $worker_object_array)->execute();
                $warnings[] = "Завкончил вставку данных в worker_parameter";
                if (!$insert_result_to_MySQL) {
                    throw new Exception('bindWorkerToEnterprise. Ошибка массовой вставки ПАРАМЕТРОВ конкретных работников в БД (worker_parameter) ' . $insert_result_to_MySQL);
                }
                $count_all = $insert_result_to_MySQL;
            }
            /** Отладка */

            $description = ' Создаем в БД параметры 18/1 для каждого воркера';                                             // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                         // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;  // количество обработанных записей
            unset($worker_object_array);
            unset($insert_result_to_MySQL);


            /*** Получаем список воркеров, их департаментов и их параметры 18/1 ***/
            $workers = Worker::find()
                ->select(['worker.id as worker_id', 'view_worker_parameter_18_1.worker_object_id', 'view_worker_parameter_18_1.id as worker_parameter_id', 'worker.company_department_id'])
                ->innerJoin('worker_object', 'worker.id = worker_object.id')
                ->innerJoin('view_worker_parameter_18_1', 'view_worker_parameter_18_1.worker_object_id = worker_object.id')
                ->asArray()
                ->all();
            $warnings[] = "bindWorkerToEnterprise. Получил воркеров из БД";

            if (!$workers) {
                throw new Exception('bindWorkerToEnterprise. Список работников в БД пуст ');
            }

            // получаем текущее значение 18 параметра из кеша
            $WorkerCacheController = (new WorkerCacheController());
            $response = $WorkerCacheController->multiGetParameterValueHash('*', 18, 1);
            if (!empty($response)) {
                foreach ($response as $response_item) {
                    $workers_paramter_from_cache[$response_item['worker_id']] = $response_item['value'];
                }
            } else {
                $warnings[] = 'bindWorkerToEnterprise. В кеше не найдены значение по 18 параметру';
            }
            unset($response);
            /*** Сравнение полученных департаментов работников предприятий со списком вложенных департаментов ***/
            $warnings[] = "bindWorkerToEnterprise. Начел сравневать предприятие";
            foreach ($workers as $worker) {
                if (isset($below_first_departments[$worker['company_department_id']])) {
                    //$warnings[] = "найден воркер из 51".$worker['worker_id'];
                    $enterprise_id = 1;
                } elseif (isset($below_second_departments[$worker['company_department_id']])) {
                    $enterprise_id = 2;
                    //$warnings[] = "найден воркер из 59".$worker['worker_id'];
                } else {
                    $enterprise_id = $default_value;
                }
                $workers['count_workers'] = count($workers);
                // если текущее значение 18 параметра отличаются, то пишем в бд и кеш
                if ($enterprise_id !== isset($workers_paramter_from_cache[$worker['worker_id']])) {
                    //$warnings[] = "У работника ". $worker['worker_id']. "поменлось значение".". Было ".$workers_paramter_from_cache[$worker['worker_id']]. "Стало ".$enterprise_id;
                    $array_departments_value_to_db[] = array('worker_parameter_id' => $worker['worker_parameter_id'], 'date_time' => $date_time_now, 'value' => $enterprise_id, 'status_id' => 1);
                    $array_departments_value_to_cache[] = array(
                        'worker_id' => $worker['worker_id'],
                        'worker_parameter_id' => $worker['worker_parameter_id'],
                        'parameter_id' => 18,
                        'parameter_type_id' => 1,
                        'date_time' => $date_time_now,
                        'value' => $enterprise_id,
                        'status_id' => 1,
                    );
                }
            }
            unset($workers_paramter_from_cache);
            unset($workers);
            unset($below_first_departments);
            unset($below_second_departments);

            /*** Вставка данных в БД ***/
            $warnings[] = "bindWorkerToEnterprise. Начел вставку в БД в worker_parameter";
            if (!empty($array_departments_value_to_db)) {
                $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert('worker_parameter_handbook_value', ['worker_parameter_id', 'date_time', 'value', 'status_id'], $array_departments_value_to_db)->execute();
                if (!$insert_result_to_MySQL) {
                    $errors[] = 'bindWorkerToEnterprise. Ошибка массовой вставки ЗНАЧЕНИЙ ПАРАМЕТРа 18 конкретных работников в БД (worker_parameter_handbook_value) ' . $insert_result_to_MySQL;
                }
                $count_all = $insert_result_to_MySQL;
            }
            /** Отладка */

            $description = 'Всатавляем в БД воркеров у которых сменилось 18/1 параметр';                                             // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                         // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;  // количество обработанных записей

            /*** Вставка значение в кэш воркеров предварительно удаляя старые данные по параметру 18 (предприятия) ***/
            $warnings[] = "bindWorkerToEnterprise. Начинаю вставки значение в кэш";
            if ($array_departments_value_to_cache) {
                $response = $WorkerCacheController->delParameterValueHash('*', 18, 1);
                if ($response['status'] == 1) {
                    $warnings[] = "bindWorkerToEnterprise. Данные из кеше по 18 параметр удалены";
                }
                $response = $WorkerCacheController->multiSetWorkerParameterValueHash($array_departments_value_to_cache);

                if ($response['status'] != 1) {
                    throw new Exception("bindWorkerToEnterprise. Не удаловсь выставить данные в кэш работников");
                }
                $warnings[] = "bindWorkerToEnterprise. Данные из кеше по 18 обновлены";
            } else {
                $errors[] = "bindWorkerToEnterprise. Не удалось сохранить данные воркеров в кеш так как данные для кеша не составлены";
                throw new Exception('bindWorkerToEnterprise. Получен пустой массив для отправки в кэш');
            }
            $count_all = count($array_departments_value_to_cache);
            $description = 'Вставил в кэш воркеров у которых сменилось 18/1 параметр';                                             // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                         // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;  // количество обработанных записей

            /** Отладка */
            $description = 'Окончание метода';                                                                              // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                         // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;  // количество обработанных записей

            $warnings[] = "bindWorkerToEnterprise успешно выполнился";


        } catch (Throwable $throwable) {

            $errors[] = "bindWorkerToEnterprise. Исключение";
            $status = 0;
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getFile();
            $errors[] = $throwable->getLine();

        }

        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);
        $result = array('Items' => [], 'status' => $status, 'warnings' => $warnings, 'errors' => $errors, 'debug' => $debug);
        return $result;

    }


}