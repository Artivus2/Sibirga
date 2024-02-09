<?php


namespace backend\controllers;

use backend\models\StrataActionLog;
use frontend\models\AmicumStatistic;
use frontend\models\AmicumSynchronization;
use Yii;
use yii\web\Controller;

//Контроллер для методов журналирования действий и результатов работ других методов и контроллеров
class LogAmicum extends Controller
{

    // методы:
    // LogEventAmicum()             - метод журналирования запросов пользователей и сведений о выполнении их
    // LogAccessAmicum()            - метод журналирования нарушений прав доступа к информации
    // LogEventStrata()             - метод журналирования запросов к методам Strata и сведений о выполнении их
    // LogAmicumStrata()            - Логирование методов страты
    // LogWebSocket()               - метод для записи логов websocket в бд
    // LogAmicumSynchronization()   - Логирование синхронизаций
    // LogAmicumStatistic()         - Логирование скриптов модуля статистики

    //метод журналирования запросов пользователей и сведений о выполнении их
    //входные параметры:
    //$source_event     - источник, который сгенерировал события. ОБЯЗАТЕЛЬНЫЙ ПАРАМЕТР
    //$duration_method  - продолжительность выполнения запроса
    //$result           - результат, который вернул запрашиваемый метод
    //$errors_insert    - ошибки, при выполнении метода
    //$tabel_number     - табельный номер клиента выполняющего запрос
    //date_time         - дата и время наступления события
    //$post             - набор входных параметров
    //
    //выходные параметры:
    //status            - статус выполнения 0 есть ошибки метод не выполнен, 1 выполнено без ошибок. Параметр текстовый
    //errors            - массив ошибок в виде текстовых строк
    //Якимов М.Н.
    //02.05.2019
    public static function LogEventAmicum($source_event = "", $date_time = "", $duration_method = "", $post = "", $resultMethod = "", $errors_insert = "", $tabel_number = "")
    {
        $status = 1;                                                                                                      //статус выполнения метода
        $errors = array();                                                                                                //массив ошибок в результирующем массива
        $result_main = array('status' => '', 'errors' => '');                                                                  //результирующий массив на возврат                                                                                                //массив ошибок на возврат из метода
        if ($source_event != "") {
            try {
                $status = Yii::$app->db_amicum_log->createCommand                                                       //записываем в базу логов в таблицу user_action_log
                (
                    "INSERT INTO user_action_log (table_number, post, result, errors, duration, metod_amicum,date_time)
					VALUE ('$tabel_number', '$post',  '$resultMethod', '$errors_insert', '$duration_method','$source_event','$date_time')"
                )->execute();
                $status *= 1;
            } catch (\Exception $e) {
                $status = 0;                                                                                   //обнуляем статус даже если были какие либо успешные резалты
                $errors[] = $e->getMessage();
            }
        } else {
            $status = 0;
            $errors[] = "Не передан обязательный параметр source_event, или он пуст";
        }
        $result_main = array('status' => $status, 'errors' => $errors);
        return $result_main;
    }

    //метод журналирования нарушений прав доступа к информации
    //входные параметры:
    //date_time     - дата и время наступления события
    //$session_amicum       - сессия пользователя
    //$tabel_number     - табельный номер клиента выполняющего запрос
    //$access_status        - статус доступа к данным - 0/запрещен, 1/разершен
    //
    //выходные параметры:
    //status            - статус выполнения 0 есть ошибки метод не выполнен, 1 выполнено без ошибок. Параметр текстовый
    //errors            - массив ошибок в виде текстовых строк
    //Якимов М.Н.
    //02.05.2019
    public static function LogAccessAmicum($date_time, $session_amicum = "", $tabel_number = "", $access_status = 0)
    {
        $status = 1;                                                                                                      //статус выполнения метода
        $errors = array();                                                                                                //массив ошибок в результирующем массива
        $result_main = array('status' => '', 'errors' => '');                                                                  //результирующий массив на возврат                                                                                                //массив ошибок на возврат из метода
        if ($session_amicum != "") {
            try {
                $session_amicum = json_encode($session_amicum);
                $status = Yii::$app->db_amicum_log->createCommand                                                       //записываем в базу логов доступа в таблицу user_access_log
                (
                    "INSERT INTO user_access_log (session_amicum, date_time, tabel_number,access_status)
					VALUE ('$session_amicum', '$date_time','$tabel_number',$access_status)"
                )->execute();
                $status *= 1;
            } catch (\Exception $e) {
                $status = 0;                                                                                              //обнуляем статус даже если были какие либо успешные резалты
                $errors[] = $e->getMessage();
            }
        } else {
            $status = 0;
            $errors[] = "Не передан обязательный параметр session_amicum, или он пуст";
        }
        $result_main = array('status' => $status, 'errors' => $errors);
        return $result_main;
    }

    //метод журналирования запросов к методам Strata и сведений о выполнении их
    //входные параметры:
    //$source_event     - источник, который сгенерировал события. ОБЯЗАТЕЛЬНЫЙ ПАРАМЕТР
    //$duration_method  - продолжительность выполнения запроса
    //$result           - результат, который вернул запрашиваемый метод
    //$errors_insert    - ошибки, при выполнении метода
    //$tabel_number     - табельный номер клиента выполняющего запрос
    //date_time         - дата и время наступления события
    //$post             - набор входных параметров
    //
    //выходные параметры:
    //status            - статус выполнения 0 есть ошибки метод не выполнен, 1 выполнено без ошибок. Параметр текстовый
    //errors            - массив ошибок в виде текстовых строк
    //Якимов М.Н.
    //02.05.2019
    public static function LogEventStrata($source_event = "", $date_time = "", $duration_method = "", $post = "", $resultMethod = "", $errors_insert = "", $tabel_number = "")
    {
        $status = 1;                                                                                                      //статус выполнения метода
        $errors = array();                                                                                                //массив ошибок в результирующем массива
        $result_main = array('status' => '', 'errors' => '');                                                                  //результирующий массив на возврат                                                                                                //массив ошибок на возврат из метода
        if ($source_event != "") {
            try {
                $status = Yii::$app->db_amicum_log->createCommand                                                       //записываем в базу логов в таблицу user_action_log
                (
                    "INSERT INTO strata_action_log (table_number, post, result, errors, duration, metod_amicum, date_time)
					VALUE ('$tabel_number', '$post',  '$resultMethod', '$errors_insert', '$duration_method','$source_event','$date_time')"
                )->execute();
                $status *= 1;
            } catch (\Exception $e) {
                $status = 0;                                                                                   //обнуляем статус даже если были какие либо успешные резалты
                $errors[] = $e->getMessage();
            }
        } else {
            $status = 0;
            $errors[] = 'Не передан обязательный параметр source_event, или он пуст';
        }
        $result_main = array('status' => $status, 'errors' => $errors);
        return $result_main;
    }

    /**
     * метод для записи логов websocket в бд
     * пример вызова: LogWebSocket("ошибка в вебсокете", "время ошибки")
     * @param string $error_string текcт ошибки
     * @param $date_time время ошибки
     * @return array
     * разработал: Fayzulloev A.
     */
    public static function LogWebSocket($error_string, $date_time)
    {
        $status = 1;
        $errors = array();
        $result = array();
        $warning = array();
        if ($error_string != "" && isset($date_time)) {
            try {
                $warning[] = Yii::$app->db_amicum_log->createCommand
                (
                    "insert into ws_log (error_string, date_time) value ('$error_string','$date_time')"
                )->execute();

            } catch (\Throwable $exception) {
                $status = 0;
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
            }
        } else {
            $errors[] = "LogWebSocket пишет :: Исключение. Не задано выходной параметер метода";
            $status = 0;
        }

        $result = array('status' => $status, 'warnings' => $warning, 'errors' => $errors);
        return $result;
    }

    /**
     * Метод LogAmicumSynchronization() - Логирование синхронизаций
     * @param $method_name - название метода
     * @param null $date_time_start - дата и время начала выполнения метода
     * @param null $date_time_end - дата и время окончания выполнения метода
     * @param int $duration - длительность выполнения
     * @param int $max_memory_peak - память в пике
     * @param array $debugMethod - блок дебаг
     * @param array $warningsMethod - блок предупреждений
     * @param array $errorsMethod - блок ошибок
     * @param int $number_row_affected - количество затронутых строк
     * @return array
     *
     * @package backend\controllers
     *
     * Входные обязательные параметры:
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 15.01.2020 11:53
     */
    public static function LogAmicumSynchronization(
        $method_name,
        $debugMethod,
        $warningsMethod,
        $errorsMethod,
        $date_time_start,
        $date_time_end = null,
        $log_id = null,
        $duration = 0,
        $max_memory_peak = 0,
        $number_row_affected = 0
    )
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $errors = array();
        $warnings = array();
        $result = array();
        try {
            $warnings[] = "LogAmicumSynchronization. Начал выполнять метод";
            if ($log_id) {
                $warnings[] = "LogAmicumSynchronization. Поиск ключа - обновление записи лога";
                $amicum_synchronization = AmicumSynchronization::findOne(['id' => $log_id]);
                if (!$amicum_synchronization) {
                    throw new \Exception('LogAmicumSynchronization. Ошибка поиска ключа логов');
                }
                $amicum_synchronization->date_time_end = $date_time_end;
                $amicum_synchronization->duration = $duration;
                $amicum_synchronization->max_memory_peak = $max_memory_peak;
                $amicum_synchronization->debug = json_encode($debugMethod);
                $amicum_synchronization->warnings = json_encode($warningsMethod);
                $amicum_synchronization->errors = json_encode($errorsMethod);
                $amicum_synchronization->number_rows_affected = $number_row_affected;
            } else {
                $warnings[] = "LogAmicumSynchronization. Ключ пуст, запись лога новая";
                $amicum_synchronization = new AmicumSynchronization();
                $amicum_synchronization->method_name = $method_name;
                $amicum_synchronization->date_time_start = $date_time_start;
            }

            if (!$amicum_synchronization->save()) {
                $errors[] = $amicum_synchronization->errors;
                throw new \Exception('LogAmicumSynchronization. Ошибка при сохранении логов');
            }
            $result = $amicum_synchronization->id;
        } catch (\Throwable $exception) {
            $status = 0;
            $errors[] = "LogAmicumSynchronization. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        $warnings[] = "LogAmicumSynchronization. Закончил выполнять метод";
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод LogAmicumStatistic() - Логирование скриптов модуля статистики
     * @param $method_name - название метода
     * @param null $date_time_start - дата и время начала выполнения метода
     * @param null $date_time_end - дата и время окончания выполнения метода
     * @param int $duration - длительность выполнения
     * @param int $max_memory_peak - память в пике
     * @param array $debugMethod - блок дебаг
     * @param array $warningsMethod - блок предупреждений
     * @param array $errorsMethod - блок ошибок
     * @param int $number_row_affected - количество затронутых строк
     * @return array
     *
     * @package backend\controllers
     *
     * Входные обязательные параметры:
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 15.01.2020 11:53
     */
    public static function LogAmicumStatistic(
        $method_name,
        $debugMethod,
        $warningsMethod,
        $errorsMethod,
        $date_time_start,
        $date_time_end = null,
        $log_id = null,
        $duration = 0,
        $max_memory_peak = 0,
        $number_row_affected = 0

    )
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $errors = array();
        $warnings = array();
        $result = array();
        try {
            $warnings[] = "LogAmicumStatistic. Начал выполнять метод";
            if ($log_id) {
                $warnings[] = "LogAmicumStatistic. Поиск ключа - обновление записи лога";
                $amicum_statistic = AmicumStatistic::findOne(['id' => $log_id]);
                if (!$amicum_statistic) {
                    throw new \Exception('LogAmicumStatistic. Ошибка поиска ключа логов');
                }
                $amicum_statistic->date_time_end = $date_time_end;
                $amicum_statistic->duration = $duration;
                $amicum_statistic->max_memory_peak = $max_memory_peak;
                $amicum_statistic->debug = json_encode($debugMethod);
                $amicum_statistic->warnings = json_encode($warningsMethod);
                $amicum_statistic->errors = json_encode($errorsMethod);
                $amicum_statistic->number_rows_affected = $number_row_affected;
            } else {
                $warnings[] = "LogAmicumStatistic. Ключ пуст, запись лога новая";
                $amicum_statistic = new AmicumStatistic();
                $amicum_statistic->method_name = $method_name;
                $amicum_statistic->date_time_start = $date_time_start;
            }

            if (!$amicum_statistic->save()) {
                $errors[] = $amicum_statistic->errors;
                throw new \Exception('LogAmicumStatistic. Ошибка при сохранении логов');
            }
            $result = $amicum_statistic->id;
        } catch (\Throwable $exception) {
            $status = 0;
            $errors[] = "LogAmicumStatistic. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        $warnings[] = "LogAmicumStatistic. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод LogAmicumStrata - Логирование методов страты
     * @param $method_amicum - название метода который обрабатывался
     * @param int $duration - длительность выполнения
     * @param int $table_number - ключ работника под чьей учеткой произошло событие
     * @param array $post - массив на входе в метод
     * @param array $warnings_method - предупреждения метода
     * @param array $errors_method - ошибки метода
     * @return array
     *
     * @package backend\controllers
     *
     * Входные обязательные параметры:
     * @example
     *
     * @author Якимов М.Н.
     * Created date: on 15.01.2020 11:53
     */
    public static function LogAmicumStrata($method_amicum, $post, $warnings_method, $errors_method, $duration = 0, $table_number = null)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $errors = array();                                                                                              // массив ошибок при выполнении метода
        $warnings = array();                                                                                            // массив предупреждений при выполнении метода
        $result = array();                                                                                              // результирующи массив
        try {
            $warnings[] = "LogAmicumStrata. Начал выполнять метод";

            $strata_action_log_new = new StrataActionLog();

            $strata_action_log_new->table_number = $table_number;
            $strata_action_log_new->date_time = Assistant::GetDateTimeNow();
            $strata_action_log_new->post = json_encode($post);
            $strata_action_log_new->result = json_encode($warnings_method);
            $strata_action_log_new->errors = json_encode($errors_method);
            $strata_action_log_new->duration = $duration;
            $strata_action_log_new->metod_amicum = $method_amicum;

            if (!$strata_action_log_new->save()) {
                $errors[] = $strata_action_log_new->errors;
                throw new \Exception('LogAmicumStrata. Ошибка при сохранении логов StrataActionLog');
            }
        } catch (\Throwable $exception) {
            $status = 0;
            $errors[] = "LogAmicumStrata. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        $warnings[] = "LogAmicumStrata. Закончил выполнять метод";

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }
}