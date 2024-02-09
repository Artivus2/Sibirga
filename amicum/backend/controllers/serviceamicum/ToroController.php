<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\serviceamicum;

use backend\controllers\Assistant;
use backend\controllers\const_amicum\StatusEnumController;
use backend\controllers\EquipmentBasicController;
use backend\controllers\LogAmicum;
use frontend\models\Equipment;
use frontend\models\SapEquiOut;
use Throwable;
use Yii;
use yii\db\Query;


class ToroController
{
    // MainToro         - главный метод запуска синхронизации данных ТОРО

    // SynchToro        - главный метод синхронизации таблиц ТОРО
    // CopyToro         - главный метод копирования таблиц ТОРО

    // toroCopyTable    - универсальный метод копирования данных ТОРО из Oracle в промежуточные таблицы MySQL

    // synhEquipment    - метод синхронизирует данные из SAP ТОРО в части справочника оборудований


    public $oracle_db;


    /**
     * констурктор для подключения к БД ОРАКЛ - интеграционный слой
     */
    public function __construct()
    {
        $this->oracle_db = oci_connect(
            HOST_BATCHQAS_USER_NAME,
            HOST_BATCHQAS_USER_PWD,
            '(DESCRIPTION =(ADDRESS = (PROTOCOL = TCP)(HOST =' . HOST_BATCHQAS . ')(PORT = 1521))(CONNECT_DATA =(SERVER =default)(SERVICE_NAME =' . HOST_BATCHQAS_SERVICE_NAME . ')))',
            'AL32UTF8');
    }

    /**
     * MainToro - главный метод запуска синхронизации данных ТОРО
     */
    public function MainToro()
    {
        // Стартовая отладочная информация
        $method_name = 'MainToro. ';                                                                                    // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                       // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                     // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                   // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                           // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            // копируем все данные с интеграционного слоя
            $response = $this->CopyToro();
            if ($response['status'] === 0) {
                $errors = array_merge($errors, $response['errors']);
                $warnings = array_merge($warnings, $response['warnings']);
                throw new \Exception($method_name . 'при выполнении главного метода копирования данных ТОРО');
            }

            // синхронизируем полученные данные с интеграционного слоя
            $response = self::SynchToro();
            if ($response['status'] === 0) {
                $errors = array_merge($errors, $response['errors']);
                $warnings = array_merge($warnings, $response['warnings']);
                throw new \Exception($method_name . 'при выполнении главного метода синхронизации данных ТОРО');
            }


        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                           // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description; // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result_main;
    }

    /**
     * CopyToro - главный метод копирования таблиц ТОРО
     */
    public function CopyToro()
    {
        // Стартовая отладочная информация
        $method_name = 'CopyToro. ';                                                                                      // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                       // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                     // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                   // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                           // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $response = $this->toroCopyTable("ERPPM.EQUI_OUT", "sap_equi_out",
                [
                    "EQUNR AS equipment_id",                                                                            // номер оборудования
                    "HEQUI AS parent_equipment_id",                                                                     // номер оборудования родителя
                    "EQKTX AS equipment_title",                                                                         // название оборудования
                    "EQTYP",                                                                                            // тип единицы оборудования
                    "INVNR as inventory_number",                                                                        // инвентарный номер
                    "ANLNR",                                                                                            // основной номер оборудования
                    "ANLUN",                                                                                            // субномер номер оборудования
                    "TPLNR",                                                                                            // код технического места
                    "BUKRS",                                                                                            // балансовая единица
                    "TO_CHAR(DATAB, 'YYYY-MM-DD HH24:MI:SS') AS DATAB",                                                 // дата начал действия оборудования
                    "TO_CHAR(DATBI, 'YYYY-MM-DD HH24:MI:SS') AS DATBI",                                                 // дата окончания действия оборудования
                    "TO_CHAR(INBDT, 'YYYY-MM-DD HH24:MI:SS') AS INBDT",                                                 // дата ввода в эксплуатацию
                    "TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED"                                  // дата изменения записи
                ],
                [
                    "equipment_id",                                                                                     // номер оборудования
                    "parent_equipment_id",                                                                              // номер оборудования родителя
                    "equipment_title",                                                                                  // название оборудования
                    "EQTYP",                                                                                            // тип единицы оборудования
                    "inventory_number",                                                                                 // инвентарный номер
                    "ANLNR",                                                                                            // основной номер оборудования
                    "ANLUN",                                                                                            // субномер номер оборудования
                    "TPLNR",                                                                                            // код технического места
                    "BUKRS",                                                                                            // балансовая единица
                    "DATAB",                                                                                            // дата начал действия оборудования
                    "DATBI",                                                                                            // дата окончания действия оборудования
                    "INBDT",                                                                                            // дата ввода в эксплуатацию
                    "DATE_MODIFIED"                                                                                     // дата изменения записи
                ],
                $where = " TPLNR like 'VU-%'");
            if ($response['status'] === 0) {
                $errors = array_merge($errors, $response['errors']);
                $warnings = array_merge($warnings, $response['warnings']);
                $debug = array_merge($debug, $response['debug']);
                throw new \Exception($method_name . 'при выполнении метода копирования данных EQUI_OUT (справочник оборудования)');
            } else {
                $debug = array_merge($debug, $response['debug']);
                $warnings = array_merge($warnings, $response['warnings']);
            }
        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                           // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description; // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result_main;
    }

    /**
     * Метод toroCopyTable() - универсальный метод копирования данных ТОРО из Oracle в промежуточные таблицы MySQL
     * Выходные параметры:
     * (стандартный массив выходных данных)
     *
     * @param string $table_name_from - имя таблицы, из которой копируем
     * @param string $table_name_to - имя таблицы, в которую копируем
     * @param array $columns_from - столбцы таблицы, из которой надо скопировать
     * @param array $columns_to - столбцы таблицы, в которую надо скопировать
     * @param string $where - условие выборки
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 03.10.2019 11:17
     */
    public function toroCopyTable($table_name_from = "", $table_name_to = "", $columns_from = [], $columns_to = [], $where = "")
    {
        // Стартовая отладочная информация
        $method_name = 'toroCopyTable. ' . $table_name_to . ". ";                                                                               // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                       // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                     // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                   // текущая отметка времени выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        try {
//            ini_set('max_execution_time', -1);
//            ini_set('mysqlnd.connect_timeout', 1440000);
//            ini_set('default_socket_timeout', 1440000);
//            ini_set('mysqlnd.net_read_timeout', 1440000);
//            ini_set('mysqlnd.net_write_timeout', 1440000);
//            ini_set('memory_limit', "10500M");


            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $maxDateUpdate = (new Query())
                ->select('max(DATE_MODIFIED)')
                ->from($table_name_to)
                ->scalar();
            $warnings[] = $method_name . "Максимальная дата для обработки записи: " . $maxDateUpdate;

            if ($where != "") {
                $where = "WHERE " . $where;
            }

            if ($maxDateUpdate) {
                if ($where != "") {
                    $where .= " AND ";
                } else {
                    $where = "WHERE ";
                }
                $where .= "DATE_MODIFIED>TO_DATE('" . $maxDateUpdate . "','YYYY-MM-DD HH24:MI:SS')";
            }
            $warnings[] = $method_name . 'where: ' . $where;
            $conn_oracle = $this->oracle_db;
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                $warnings[] = $method_name . 'Соединение с Oracle не выполнено';
            } else {
                $warnings [] = $method_name . 'Соединение с Oracle установлено';
            }

            /** Отладка */
            $description = 'Получил максимальную дату';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $query_string = "SELECT " . implode(',', $columns_from) . " FROM " . $table_name_from . " " . $where;
            $warnings[] = $method_name . 'where: ' . $query_string;
            $query = oci_parse($conn_oracle, $query_string);
            oci_execute($query);
            $count = 0;
            $count_all = 0;

//            $del_full_count = Yii::$app->db->createCommand()->delete($table_name_to)->execute();                           // очищаем промежуточную таблицу для вставки даных
//            $warnings[] = $method_name . "удалил $del_full_count записей из таблицы $table_name_to";

            /** Отладка */
            $description = 'Получил данные с oracle';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                        //цикл по массиву строк запроса
            {
                $query_result[] = $row;
                $count++;
                $count_all++;
                /**
                 * Значение счётчика = 3000
                 *      да?     Массово добавить данные в промежуточную таблицу($table_name_to)
                 *              Очистить массив для вставки данных
                 *              Обнулить счётчик
                 *      нет?    Пропусить
                 */
                if ($count == 3000) {
                    $insert_full = Yii::$app->db->createCommand()->batchInsert($table_name_to, $columns_to, $query_result)->execute();
                    if ($insert_full === 0) {
                        throw new \Exception($method_name . 'Записи в таблицу ' . $table_name_to . ' не добавлены');
                    } else {
                        $warnings[] = $method_name . "добавил - $insert_full - записей в таблицу $table_name_to";
                    }
                    $query_result = [];
                    $count = 0;
                }
            }

            if (isset($query_result) && !empty($query_result)) {
                $insert_full = Yii::$app->db->createCommand()->batchInsert($table_name_to, $columns_to, $query_result)->execute();
                if ($insert_full === 0) {
                    throw new \Exception($method_name . 'Записи в таблицу ' . $table_name_to . ' не добавлены');
                } else {
                    $warnings[] = $method_name . "добавил - $insert_full - записей в таблицу $table_name_to";
                }
            }
            $warnings[] = $method_name . "количество добавляемых записей: " . $count_all;
            $warnings[] = $method_name . "Закончил выполнять метод.";
        } catch (Throwable $ex) {
            $errors[] = $method_name . 'Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                           // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description; // количество обработанных записей
        /** Окончание отладки */

        return array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    /**
     * SynchToro - главный метод синхронизации таблиц ТОРО
     * пример: http://127.0.0.1/super-test/synch-toro
     */
    public static function SynchToro()
    {
        // Стартовая отладочная информация
        $method_name = 'SynchToro. ';                                                                                      // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                       // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                     // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                   // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                           // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $response = self::synhEquipment();
            if ($response['status'] === 0) {
                $errors = array_merge($errors, $response['errors']);
                $warnings = array_merge($warnings, $response['warnings']);
                $debug = array_merge($debug, $response['debug']);
                throw new \Exception($method_name . 'при выполнении метода синхронизации данных EQUI_OUT (справочник оборудования)');
            } else {
                $debug = array_merge($debug, $response['debug']);
                $warnings = array_merge($warnings, $response['warnings']);
            }

        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                           // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description; // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result_main;
    }

    /**
     * Метод synhEquipment() - метод синхронизирует данные из SAP TORO в части справочника оборудований
     * алгоритм:
     * 1. получить последнюю дату синхронизации из справочника оборудования АМИКУМ
     * 2. Получить данные с последней даты синхронизации из sap_equi_out, а если даты нет, то полностью все
     * 3. Получить весь справочник оборудования из АМИКУМ для проверки наличя данной записи
     * 4. Начать перебор и либо создать оборудование, а если оно есть то обновить по нему данные в справочнике оборудования и в таблицах параметров и их значений
     * Входные параметры:
     * (метод не требует входных параметров)
     *
     * Выходные параметры:
     * (стандартный массив выходных данных)
     *
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 03.10.2019 11:17
     */
    public static function synhEquipment()
    {
        // Стартовая отладочная информация
        $method_name = 'synhEquipment. ';                                                                               // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                       // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                     // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                   // текущая отметка времени выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        try {
//            ini_set('max_execution_time', -1);
//            ini_set('mysqlnd.connect_timeout', 1440000);
//            ini_set('default_socket_timeout', 1440000);
//            ini_set('mysqlnd.net_read_timeout', 1440000);
//            ini_set('mysqlnd.net_write_timeout', 1440000);
//            ini_set('memory_limit', "10500M");

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $filter_max_date_synch_sap = [];
            $filter_max_date_synch_amicum = [];
            // 1. получить последнюю дату синхронизации из справочника оборудования АМИКУМ
            $maxDateUpdate = (new Query())
                ->select('max(date_time_sync)')
                ->from('equipment')
                ->scalar();
            $warnings[] = $method_name . "Максимальная дата синхронизации записи: " . $maxDateUpdate;

            if ($maxDateUpdate) {
                $filter_max_date_synch_sap = "DATE_MODIFIED>'" . $maxDateUpdate . "'";
                $filter_max_date_synch_amicum = "date_time_sync>'" . $maxDateUpdate . "'";
                $warnings[] = $method_name . 'filter_max_date_synch_sap: ' . $filter_max_date_synch_sap;
            }

            // 2. Получить данные с последней даты синхронизации из sap_equi, а если даты нет, то полностью все
            $sap_equipments = SapEquiOut::find()
                ->where($filter_max_date_synch_sap)
                ->asArray()
//                ->limit(10)
                ->orderBy('DATE_MODIFIED')
                ->all();

            $warnings[] = $method_name . 'Количество записей для синхронизации: ' . count($sap_equipments);

            // 3. Получить весь справочник оборудования из АМИКУМ для проверки наличя данной записи
            $equipments_to_update = Equipment::find()
                ->indexBy('sap_id')
                ->asArray()
                ->all();

            $count_upd = 0;
            $count_add = 0;

            // 4. Начать перебор и либо создать оборудование, а если оно есть то обновить по нему данные в справочнике оборудования и в таблицах параметров и их значений
            $parent_amicum = [];
            foreach ($sap_equipments as $sap_equipment) {
                if (isset($equipments_to_update[$sap_equipment['equipment_id']])) {
                    // то обновляем
                    $equipment_upd = Equipment::findOne(['id'=>$equipments_to_update[$sap_equipment['equipment_id']]['id']]);
                    $equipment_upd->title = $sap_equipment['equipment_title'];
                    $equipment_upd->inventory_number = $sap_equipment['inventory_number'];
                    $equipment_upd->date_time_sync = $sap_equipment['DATE_MODIFIED'];
                    $count_upd++;
                } else {
                    // то создаем
                    $response = EquipmentBasicController::addEquipmentBatch(
                        $sap_equipment['equipment_title'],
                        StatusEnumController::SAP_TYPICAL_OBJECT,
                        $sap_equipment['parent_equipment_id'],
                        $mine_id = -1,
                        $sap_equipment['inventory_number'],
                        $sap_equipment['equipment_id'],
                        $sap_equipment['DATE_MODIFIED']);
                    if ($response['status'] === 0) {
                        $errors = array_merge($errors, $response['errors']);
                        $warnings = array_merge($errors, $response['warnings']);
                        throw new \Exception($method_name . 'при выполнении метода синхронизации данных EQUI_OUT (справочник оборудования)');
                    }
                    if ($sap_equipment['parent_equipment_id']) {
                        $parent_amicum[$sap_equipment['parent_equipment_id']] = $response['equipment_id'];
                    }
                    $count_add++;
                }
            }

            $count_error_parent_key = 0;
            if (!empty($parent_amicum)) {
                $equipments_sap = Equipment::find()
                    ->indexBy('sap_id')
                    ->asArray()
                    ->all();
                // 3. Получить весь справочник оборудования из АМИКУМ для обновления родителя
                $equipments_to_update = Equipment::find()
                    ->where('parent_equipment_id is not null')
                    ->andWhere($filter_max_date_synch_amicum)
                    ->all();
                foreach ($equipments_to_update as $equipment) {
                    if (isset($equipments_sap[$equipment->parent_equipment_id])) {
                        $equipment->parent_equipment_id = $equipments_sap[$equipment->parent_equipment_id]['id'];
                        if (!$equipment->save()) {                                                                      //сохранить модель в БД
                            $errors[] = $method_name . 'Ошибка сохранения модели Equipment';
                            $errors[] = $equipment->errors;
                        }
                    } else {
                        $count_error_parent_key++;
                    }
                }
            }

            $warnings[] = $method_name . 'Количество обновлений родителя с ошибками: ' . $count_error_parent_key;
            $warnings[] = $method_name . 'Количество связей с родителями: ' . count($parent_amicum);
            $warnings[] = $method_name . 'Количество созданных записей: ' . $count_add;
            $warnings[] = $method_name . 'Количество обновленных записей: ' . $count_upd;

        } catch (Throwable $ex) {
            $errors[] = $method_name . 'Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                           // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description; // количество обработанных записей
        /** Окончание отладки */

        return array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

}


