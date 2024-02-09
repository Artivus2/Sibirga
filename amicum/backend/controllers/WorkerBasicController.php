<?php

namespace backend\controllers;


use Exception;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\WorkerParameterHandbookValue;
use frontend\models\WorkerParameterSensor;
use frontend\models\WorkerParameterValue;
use frontend\models\WorkerParameterValueTemp;
use Throwable;
use yii\db\Query;

/**
 * Класс по работе с методами работников. Вся логика происходит для работы с БД.
 * Другие лишние методы писать в этом классе нельзя.
 * Class WorkerBasicController
 * @package backend\controllers
 */
class WorkerBasicController
{

    // getWorkerMine                        - метод получения списка работников по конкретной шахте на текущий момент времени(getWorkerMine)
    // addWorkerParameterValue              - метод добавления вычисляемых значений в таблицу worker_parameter_value
    // addWorkerParameterValueTemp          - метод добавления новых строк в таблицу worker_parameter_value_temp
    // addWorkerParameterHandbookValue      - метод добавления справочных значений в таблицу worker_parameter_handbook_value
    // getWorkerParameterValue()            - метод получения вычисляемых значений параметров работников в БД WorkerParameterValue
    // getWorkerParameterHandbookValue()    - метод получения справочных значений параметров работников в БД WorkerParameterHandbookValue

    /**
     * Название метода: addWorkerParameterValue()
     * Назначение метода: метод добавления вычисляемых значений в таблицу worker_parameter_value
     *
     * Входные обязательные параметры:
     * @param     $worker_parameter_id -идентификатор параметра работника
     * @param     $value - значение
     * @param     $shift - смена
     * @param     $status_id - ИД статуса
     *
     * Входные необязательные параметры
     * @param int $date_time - дата
     * @param int $date_time_work - дата и время
     * @return int|array - если данные успешно сохранились в БД, то возвращает id, иначе массив ошибок
     * @package backend\controllers
     * @example $this->addWorkerParameterValue(1245, 'тест', '1', 19)
     * @example $this->addWorkerParameterValue(1245, 'тест', '1', 19, '2019-04-06')
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 10:36
     */
    public static function addWorkerParameterValue($worker_parameter_id, $value, $shift, $status_id, $date_time = -1, $date_time_work = -1): array|int
    {
        if ($date_time == -1) {
            $date_time = date('Y:m:d H:i:s', strtotime(Assistant::GetDateNow()));
        }
        if ($date_time_work == -1) {
            $date_time_work = date('Y-m-d');
        }
        $workerParameterValue = new WorkerParameterValue();
        $workerParameterValue->worker_parameter_id = $worker_parameter_id;
        $workerParameterValue->value = (string)$value;
        $workerParameterValue->shift = (string)$shift;
        $workerParameterValue->status_id = $status_id;
        $workerParameterValue->date_work = $date_time_work;
        $workerParameterValue->date_time = $date_time;
        if ($workerParameterValue->save()) {
            $workerParameterValue->refresh();
            return $workerParameterValue->id;
        }
        return $workerParameterValue->errors;
    }

    /**
     * Название метода: addWorkerParameterValueTemp()
     * Назначение метода: метод добавления новых строк в таблицу worker_parameter_value_temp
     *
     * Входные обязательные параметры:
     * @param     $worker_parameter_id -идентификатор параметра работника
     * @param     $value - значение параметра
     * @param     $shift - номер смены
     * @param     $status_id - идентификатор статуса
     *
     * Входные необязательные параметры
     * @param int $date_time - дата и время получения значения параметра
     * @param int $date_time_work дата смены
     * @return int|array - если данные успешно сохранились в БД, то возвращает id, иначе массив ошибок
     * @package backend\controllers
     * @example WorkerBasicController::addWorkerParameterValueTemp(1245, 'тест', '1', 19)
     * @example WorkerBasicController::addWorkerParameterValueTemp(1245, 'тест', '1', 19, '2019-04-06')
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 10:36
     */
    public static function addWorkerParameterValueTemp($worker_parameter_id, $value, $shift, $status_id, $date_time = -1, $date_time_work = -1): array|int
    {
        if ($date_time == -1) {
            $date_time = date('Y:m:d H:i:s', strtotime(Assistant::GetDateNow()));

        }
        if ($date_time_work == -1) {
            $date_time_work = date('Y-m-d');
        }
        $workerParameterValue = new WorkerParameterValueTemp();
        $workerParameterValue->worker_parameter_id = $worker_parameter_id;
        $workerParameterValue->value = (string)$value;
        $workerParameterValue->shift = (string)$shift;
        $workerParameterValue->status_id = $status_id;
        $workerParameterValue->date_work = $date_time_work;
        $workerParameterValue->date_time = $date_time;
        if ($workerParameterValue->save()) {
            $workerParameterValue->refresh();
            return $workerParameterValue->id;
        }
        return $workerParameterValue->errors;
    }

    /**
     * Название метода: addWorkerParameterHandbookValue()
     * Назначение метода: метод добавления справочных значений в таблицу worker_parameter_handbook_value
     *
     * Входные обязательные параметры:
     * @param     $worker_parameter_id -идентификатор параметра работника
     * @param     $value - значение
     * @param     $status_id - ИД статуса
     * Входные необязательные параметры
     * @param $date_time - дата и время
     * @return array - если данные успешно сохранились в БД, то возвращает id, иначе массив ошибок
     * @package backend\controllers
     *
     * @example $this->addWorkerParameterHandbookValue(45454, 'mot', 19);
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 04.06.2019 10:38
     */
    public static function addWorkerParameterHandbookValue($worker_parameter_id, $value, $status_id, $date_time = -1): array
    {
        $log = new LogAmicumFront("addWorkerParameterHandbookValue");
        $result = null;
        $worker_parameter_handbook_value_id = -1;

        try {
            $log->addLog("Начало выполнения метода");

            if ($date_time == -1) {
                $date_time = date('Y:m:d H:i:s', strtotime(Assistant::GetDateNow()));
            }
            $w_p_h_v = new WorkerParameterHandbookValue();
            $w_p_h_v->worker_parameter_id = $worker_parameter_id;
            $w_p_h_v->value = (string)$value;
            $w_p_h_v->status_id = $status_id;
            $w_p_h_v->date_time = $date_time;

            if (!$w_p_h_v->save()) {
                $log->addData($w_p_h_v->errors, '$w_p_h_v->errors', __LINE__);
                throw new Exception("Ошибка сохранения модели WorkerParameterHandbookValue");
            }

            $w_p_h_v->refresh();
            $worker_parameter_handbook_value_id = $w_p_h_v->id;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(["Items" => $worker_parameter_handbook_value_id, "worker_parameter_handbook_value_id" => $worker_parameter_handbook_value_id], $log->getLogAll());
    }

    public static function addWorkerParameterSensor($worker_parameter_id, $sensor_id, $type_relation_sensor, $date_time = -1)
    {
        $worker_parameter_sensor_id = -1;                                                                                  //ключ конкретного значения параметра
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'addWorkerParameterSensor.Начал выполнять метод';
        try {
            if ($date_time == -1) {
                $date_time = Assistant::GetDateNow();
            }
            $worker_parameter_sensor = new WorkerParameterSensor();
            $worker_parameter_sensor->worker_parameter_id = $worker_parameter_id;
            $worker_parameter_sensor->sensor_id = $sensor_id;
            $worker_parameter_sensor->date_time = $date_time;
            $worker_parameter_sensor->type_relation_sensor = $type_relation_sensor;
            if ($worker_parameter_sensor->save()) {
                $worker_parameter_sensor->refresh();
                $worker_parameter_sensor_id = $worker_parameter_sensor->id;
                $status *= 1;
                $warnings[] = 'addWorkerParameterSensor. Начал выполнять метод';
            } else {
                $errors[] = 'addWorkerParameterSensor. Ошибка сохранения модели WorkerParameterSensor';
                $errors[] = $worker_parameter_sensor->errors;
                throw new Exception('addWorkerParameterSensor. Сохранение данных в модель окончилось с ошибкой');
            }
        } catch (Throwable $e) {
            $status = 0;
            $sensor_parameter_value_id = null;
            $errors[] = 'addWorkerParameterSensor.Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
            $errors[__FUNCTION__ . ' parameters'] = [
                '$worker_parameter_id' => $worker_parameter_id,
                '$sensor_id' => $sensor_id,
                '$type_relation_sensor' => $type_relation_sensor,
                '$date_time' => $date_time
            ];
        }
        $warnings[] = 'addWorkerParameterSensor.Закончил выполнять метод';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, '$worker_parameter_sensor_id' => $worker_parameter_sensor_id);
    }

    /**
     * Название метода: getWorkerMine()
     * Назначение метода: метод получения списка работников по конкретной шахте на текущий момент времени(getWorkerMine)
     * берутся только зарегистрированные работники
     * Входные обязательные параметры:
     * @param int $mine_id - идентификатор шахты
     *
     * Входные необязательные параметры
     * @param int $worker_id - идентификатор работника. Если указать конкретный, то только данные одного работника
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
    public static function getWorkerMine($mine_id, $worker_id = '*')
    {
        $sql_filter = null;

        if ($mine_id != '*') {
            $sql_filter = "mine_id = $mine_id";
        }

        if ($worker_id != '*' and $mine_id != '*') {
            $sql_filter .= " AND worker_id = $worker_id";
        } else if ($worker_id != '*') {
            $sql_filter = " worker_id = $worker_id";
        }


        $workers = (new Query())
            ->select(
                [
                    'position_title',
                    'department_title',
                    'first_name',
                    'last_name',
                    'patronymic',
                    'gender',
                    'stuff_number',
                    'worker_object_id',
                    'worker_id',
                    'object_id',
                    'mine_id',
                    'checkin_status'
                ])
            ->from(['view_initWorkerMineCheckin'])
            ->where($sql_filter)
            ->all();

        if ($workers) {
            foreach ($workers as $worker) {
                $worker_data[] = array(
                    // получаем ИД типизированного работника из БД
                    'worker_id' => (int)$worker['worker_id'],
                    'worker_object_id' => (int)$worker['worker_object_id'],
                    'object_id' => (int)$worker['object_id'],
                    'stuff_number' => $worker['stuff_number'],
                    'full_name' => $worker['last_name'] . " " . $worker['first_name'] . " " . $worker['patronymic'],
                    'position_title' => $worker['position_title'],
                    'department_title' => $worker['department_title'],
                    'gender' => $worker['gender'],
                    'mine_id' => (int)$worker['mine_id'],
                );
            }
            return $worker_data;
        }
        return false;
    }

    /**
     * Название метода: getWorkerParameterValue() - метод получения вычисляемых значений параметров работников в БД WorkerParameterValue
     *
     * Входные необязательные параметры
     * @param $worker_id - идентификатор оборудования.
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
    public static function getWorkerParameterValue($worker_id = '*', $parameter_id = '*', $parameter_type_id = 2)
    {
        $sql_filter = 'parameter_type_id = ' . $parameter_type_id;

        if ($parameter_type_id == '*') {
            $sql_filter = "parameter_type_id in (2, 3)";
        }

        if ($parameter_id !== '*') {
            $sql_filter .= " and parameter_id in ($parameter_id)";
        }

        if ($worker_id !== '*') {
            $sql_filter .= " and worker_id in ($worker_id)";
        }

        $worker_parameter_values = (new Query())
            ->select([
                'worker_id',
                'worker_parameter_id',
                'parameter_id',
                'parameter_type_id',
                'date_time',
                'value',
                'status_id'
            ])
            ->from('view_initWorkerParameterValue')
            ->where($sql_filter)
            ->all();

        return $worker_parameter_values;
    }

    /**
     * Название метода: getWorkerParameterHandbookValue() - метод получения справочных значений параметров работников в БД WorkerParameterHandbookValue
     *
     * Входные необязательные параметры
     * @param $worker_id - идентификатор оборудования.
     * @param $parameter_id - ключ параметра
     *
     * @return array/bool возвращает true при успешном добавлении в кэш, иначе false
     *
     *
     *
     * @author Якимов М.Н.
     * Created date: on 31.05.2019 11:51
     */
    public static function getWorkerParameterHandbookValue($worker_id = '*', $parameter_id = '*'): array
    {
        $sql_filter = 'parameter_type_id = 1';

        if ($parameter_id !== '*') {
            $sql_filter .= " and parameter_id = $parameter_id";
        }

        if ($worker_id !== '*') {
            $sql_filter .= " and worker_id = $worker_id";
        }

        $worker_parameter_handbook_values = (new Query())
            ->select([
                'worker_id',
                'worker_parameter_id',
                'parameter_id',
                'parameter_type_id',
                'date_time',
                'value',
                'status_id'
            ])
            ->from('view_initWorkerParameterHandbookValue')
            ->where($sql_filter)
            ->all();

        return $worker_parameter_handbook_values;
    }
}


