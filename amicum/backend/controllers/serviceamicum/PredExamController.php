<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\serviceamicum;

use Exception;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\KindExam;
use frontend\models\PredExamHistory;
use frontend\models\SapKindExamMv;
use frontend\models\SapPredExamHistoryFullMv;
use Throwable;
use Yii;
use yii\db\Query;


class PredExamController
{
    // MainPredExam         - главный метод запуска синхронизации данных Предсменного экзаменатора

    // SynchPredExam        - главный метод синхронизации таблиц предсменного экзаменатора
    // CopyPredExam         - главный метод копирования таблиц предсменного экзаменатора

    // predExamCopyTable    - универсальный метод копирования данных предсменного экзаменатора из Oracle в промежуточные таблицы MySQL

    // synhKindExam         - метод синхронизирует данные из сап справочника видов экзаменов
    // synhPredExamHistory  - метод синхронизирует данные из сап история тестирования работников

    // SavePredExam         - метод массового сохранения предсменных проверок знаний в БД


    public $oracle_db;


    /**
     * констурктор для подключения к БД ОРАКЛ - интеграционный слой
     */
    public function __construct()
    {
//        $this->oracle_db = oci_connect(
//            HOST_BATCHQAS_USER_NAME,
//            HOST_BATCHQAS_USER_PWD,
//            '(DESCRIPTION =(ADDRESS = (PROTOCOL = TCP)(HOST =' . HOST_BATCHQAS . ')(PORT = 1521))(CONNECT_DATA =(SERVER =default)(SERVICE_NAME =' . HOST_BATCHQAS_SERVICE_NAME . ')))',
//            'AL32UTF8');

        $this->oracle_db = oci_connect(
            HOST_BATCHPROD_USER_NAME,
            HOST_BATCHPROD_USER_PWD,
            '(DESCRIPTION =(ADDRESS = (PROTOCOL = TCP)(HOST =' . HOST_BATCHPROD . ')(PORT = 1521))(CONNECT_DATA =(SERVER =default)(SERVICE_NAME =' . HOST_BATCHPROD_SERVICE_NAME . ')))',
            'AL32UTF8');
    }

    /**
     * MainPredExam - главный метод запуска синхронизации данных Предсменного экзаменатора
     */
    public function MainPredExam()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("MainPredExam");

        try {
            $log->addLog("Начало выполнения метода");

            // копируем все данные с интеграционного слоя
            $response = $this->CopyPredExam();
            if ($response['status'] === 0) {
                $log->addLogAll($response);
                throw new Exception('при выполнении главного метода копирования данных ТОРО');
            }

            // синхронизируем полученные данные с интеграционного слоя
            $response = self::SynchPredExam();
            if ($response['status'] === 0) {
                $log->addLogAll($response);
                throw new Exception('при выполнении главного метода синхронизации данных ТОРО');
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * CopyPredExam - главный метод копирования таблиц Предсменного экзаменатора
     */
    public function CopyPredExam()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("CopyPredExam");

        try {
            $log->addLog("Начало выполнения метода");


            $response = $this->copyTable("IDB01.THEME_MV", "sap_kind_exam_mv",
                [
                    "ID",                                                                                               // ключ справочника видов экзаменов
                    "NAME",                                                                                             // название вида экзамена
                    "QUANTITY",                                                                                         // количество правильных ответов как проходной бал
                    "TO_CHAR(DATE_CREATED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_CREATED",                                   // дата создания записи
                    "TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED"                                  // дата изменения записи
                ],
                [
                    "id",                                                                                               // ключ справочника видов экзаменов
                    "name",                                                                                             // название вида экзамена
                    "quantity",                                                                                         // количество правильных ответов как проходной бал
                    "date_created",                                                                                     // дата создания записи
                    "date_modified"                                                                                     // дата изменения записи
                ]
            );
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('при выполнении метода копирования данных SAP_KIND_EXAM_MV (справочник видов экзаменов)');
            }
            $count_record += $response['count_all'];

            $response = $this->copyTable("IDB01.SAP_PRED_EXAM_HISTORY_FULL_MV", "sap_pred_exam_history_full_mv",
                [
                    "PERSONAL_NUMBER",                                                                                  // ключ работника
                    "TO_CHAR(START_TEST_TIME, 'YYYY-MM-DD HH24:MI:SS') AS START_TEST_TIME",                             // дата и время старта экзамена
                    "COUNT_RIGHT",                                                                                      // количество правильных ответов
                    "COUNT_FALSE",                                                                                      // количество не правильных ответов
                    "POINTS",                                                                                           // количество баллов
                    "SAP_KIND_EXAM_ID",                                                                                 // ключ справочника вида экзамена
                    "TO_CHAR(DATE_CREATED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_CREATED",                                   // дата создания записи
                    "TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED"                                  // дата изменения записи
                ],
                [
                    "personal_number",                                                                                  // ключ работника
                    "start_test_time",                                                                                  // дата и время старта экзамена
                    "count_right",                                                                                      // количество правильных ответов
                    "count_false",                                                                                      // количество не правильных ответов
                    "points",                                                                                           // количество баллов
                    "sap_kind_exam_id",                                                                                 // ключ справочника вида экзамена
                    "date_created",                                                                                     // дата создания записи
                    "date_modified"                                                                                     // дата изменения записи
                ]
            );
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('при выполнении метода копирования данных SAP_PRED_EXAM_HISTORY_FULL_MV (история тестирования)');
            }
            $count_record += $response['count_all'];
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");
        $log->saveLogSynchronization($count_record);

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод predExamCopyTable() - универсальный метод копирования данных Предсменного экзаменатора из Oracle в промежуточные таблицы MySQL
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
    public function copyTable($table_name_from = "", $table_name_to = "", $columns_from = [], $columns_to = [], $where = "")
    {
        // Стартовая отладочная информация
        $method_name = 'predExamCopyTable. ' . $table_name_to . ". ";                                                                               // название логируемого метода
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
            $query_string = "SELECT " . implode(',', $columns_from) . " FROM " . $table_name_from . " " . $where;
            $warnings[] = $method_name . 'where: ' . $query_string;
            $query = oci_parse($conn_oracle, $query_string);
            oci_execute($query);
            $count = 0;
            $count_all = 0;

//            $del_full_count = Yii::$app->db->createCommand()->delete($table_name_to)->execute();                           // очищаем промежуточную таблицу для вставки даных
//            $warnings[] = $method_name . "удалил $del_full_count записей из таблицы $table_name_to";
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

        return array('Items' => 1, 'count_all' => $count_all, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    /**
     * SynchPredExam - главный метод синхронизации истории Предсменного экзаменатора
     * пример: http://127.0.0.1/super-test/synch-pred-exam
     */
    public static function SynchPredExam()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("SynchPredExam");

        try {
            $log->addLog("Начало выполнения метода");

            $response = self::synhKindExam();
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('при выполнении метода синхронизации данных kind_exam (справочник видов предсменных экзаменов)');
            }
            $count_record += $response['count_all'];

            $response = self::synhPredExamHistory();
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('при выполнении метода синхронизации данных pred_exam_history (предсменных экзаменов)');
            }
            $count_record += $response['count_all'];

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization($count_record);

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод synhKindExam() - метод синхронизирует данные из сап справочника видов экзаменов
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
    public static function synhKindExam()
    {
        // Стартовая отладочная информация
        $method_name = 'synhKindExam. ';                                                                               // название логируемого метода
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

            $filter_max_date_modified = [];
            $filter_max_date_synch_amicum = [];
            // 1. получить последнюю дату синхронизации из справочника оборудования АМИКУМ
            $maxDateUpdate = (new Query())
                ->select('max(date_modified)')
                ->from('kind_exam')
                ->scalar();
            $warnings[] = $method_name . "Максимальная дата синхронизации записи: " . $maxDateUpdate;

            if ($maxDateUpdate) {
                $filter_max_date_modified = "DATE_MODIFIED>'" . $maxDateUpdate . "'";
                $filter_max_date_synch_amicum = "date_modified>'" . $maxDateUpdate . "'";
                $warnings[] = $method_name . 'filter_max_date_modified: ' . $filter_max_date_modified;
            }

            // 2. Получить данные с последней даты синхронизации из sap_kind_exam_mv, а если даты нет, то полностью все
            $sap_kind_exams = SapKindExamMv::find()
                ->where($filter_max_date_modified)
                ->asArray()
                ->orderBy('DATE_MODIFIED')
                ->all();

            $warnings[] = $method_name . 'Количество записей для синхронизации: ' . count($sap_kind_exams);

            // 3. Получить весь справочник видов экзаменов из АМИКУМ для проверки наличия данной записи
            $kind_exam_to_update = KindExam::find()
                ->indexBy('sap_id')
                ->asArray()
                ->all();

            $count_upd = 0;
            $count_add = 0;
            $count_add_temp = 0;

            // 4. Начать перебор и либо создать оборудование, а если оно есть то обновить по нему данные в справочнике оборудования и в таблицах параметров и их значений
            $parent_amicum = [];
            foreach ($sap_kind_exams as $sap_kind_exam) {
                $count_all++;
                if (isset($kind_exam_to_update[$sap_kind_exam['id']])) {
                    // то обновляем
                    $kind_exam_upd = KindExam::findOne(['id' => $kind_exam_to_update[$sap_kind_exam['id']]['id']]);
                    $kind_exam_upd->name = $sap_kind_exam['name'];
                    $kind_exam_upd->quantity = $sap_kind_exam['quantity'];
                    $kind_exam_upd->date_modified = $sap_kind_exam['date_modified'];
                    $count_upd++;
                } else {
                    // то создаем
                    $kind_exam_adds[] = array(
                        'name' => $sap_kind_exam['name'],
                        'quantity' => $sap_kind_exam['quantity'],
                        'date_created' => $sap_kind_exam['date_created'],
                        'date_modified' => $sap_kind_exam['date_modified'],
                        'sap_id' => $sap_kind_exam['id']
                    );

                    if ($count_add_temp == 2000) {
                        $insert_full = Yii::$app->db->createCommand()->batchInsert('kind_exam', ['name', 'quantity', 'date_created', 'date_modified', 'sap_id'], $kind_exam_adds)->execute();
                        if ($insert_full === 0) {
                            throw new \Exception($method_name . 'Записи в таблицу kind_exam не добавлены');
                        } else {
                            $warnings[] = $method_name . "добавил - $insert_full - записей в таблицу kind_exam";
                        }

                        $kind_exam_adds = [];
                        $count_add_temp = 0;
                    }
                    $count_add_temp++;
                    $count_add++;
                }
            }

            if (isset($kind_exam_adds) && !empty($kind_exam_adds)) {
                $insert_full = Yii::$app->db->createCommand()->batchInsert('kind_exam', ['name', 'quantity', 'date_created', 'date_modified', 'sap_id'], $kind_exam_adds)->execute();
                if ($insert_full === 0) {
                    throw new \Exception($method_name . 'Записи в таблицу kind_exam не добавлены');
                } else {
                    $warnings[] = $method_name . "добавил - $insert_full - записей в таблицу kind_exam";
                }
            }

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

        return array('Items' => 1, 'count_all' => $count_all, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }


    /**
     * Метод synhPredExamHistory() - метод синхронизирует данные из сап история тестирования работников
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
    public static function synhPredExamHistory()
    {
        // Стартовая отладочная информация
        $method_name = 'synhPredExamHistory. ';                                                                               // название логируемого метода
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

            $filter_max_date_modified = [];
            $filter_max_date_synch_amicum = [];
            // 1. получить последнюю дату синхронизации из справочника оборудования АМИКУМ
            $maxDateUpdate = (new Query())
                ->select('max(date_modified)')
                ->from('pred_exam_history')
                ->scalar();
            $warnings[] = $method_name . "Максимальная дата синхронизации записи: " . $maxDateUpdate;

            if ($maxDateUpdate) {
                $filter_max_date_modified = "DATE_MODIFIED>'" . $maxDateUpdate . "'";
                $filter_max_date_synch_amicum = "date_modified>'" . $maxDateUpdate . "'";
                $warnings[] = $method_name . 'filter_max_date_modified: ' . $filter_max_date_modified;
            }

            // 2. Получить данные с последней даты синхронизации из sap_kind_exam_mv, а если даты нет, то полностью все
            $sap_pred_exams_history = SapPredExamHistoryFullMv::find()
                ->where($filter_max_date_modified)
                ->asArray()
                ->orderBy('DATE_MODIFIED')
                ->all();

            $warnings[] = $method_name . 'Количество записей для синхронизации: ' . count($sap_pred_exams_history);

            // 3. Получить весь справочник видов экзаменов из АМИКУМ для проверки наличия данной записи
            $pred_exams_history_to_update = PredExamHistory::find()
                ->indexBy('sap_id')
                ->asArray()
                ->all();

            $kind_exam_to_update = KindExam::find()
                ->indexBy('sap_id')
                ->asArray()
                ->all();

            $count_upd = 0;
            $count_add = 0;
            $count_add_temp = 0;

            // 4. Начать перебор и либо создать оборудование, а если оно есть то обновить по нему данные в справочнике оборудования и в таблицах параметров и их значений
            $parent_amicum = [];
            foreach ($sap_pred_exams_history as $sap_pred_exam_history) {
                $count_all++;
                if (isset($pred_exams_history_to_update[$sap_pred_exam_history['id']])) {
                    // то обновляем
                    $pred_exam_history_upd = PredExamHistory::findOne(['id' => $pred_exams_history_to_update[$sap_pred_exam_history['id']]['id']]);
                    $pred_exam_history_upd->employee_id = $sap_pred_exam_history['personal_number'];
                    $pred_exam_history_upd->start_test_time = $sap_pred_exam_history['start_test_time'];
                    $pred_exam_history_upd->count_right = $sap_pred_exam_history['count_right'];
                    $pred_exam_history_upd->count_false = $sap_pred_exam_history['count_false'];
                    $pred_exam_history_upd->points = $sap_pred_exam_history['points'];
                    $pred_exam_history_upd->sap_kind_exam_id = $kind_exam_to_update[$sap_pred_exam_history['sap_kind_exam_id']]['id'];
                    $pred_exam_history_upd->date_created = $sap_pred_exam_history['date_created'];
                    $pred_exam_history_upd->date_modified = $sap_pred_exam_history['date_modified'];
                    $pred_exam_history_upd->sap_id = $sap_pred_exam_history['id'];
                    $count_upd++;
                } else {
                    // то создаем
                    $pred_exam_history_adds[] = array(
                        'employee_id' => $sap_pred_exam_history['personal_number'],
                        'start_test_time' => $sap_pred_exam_history['start_test_time'],
                        'count_right' => $sap_pred_exam_history['count_right'],
                        'count_false' => $sap_pred_exam_history['count_false'],
                        'points' => $sap_pred_exam_history['points'],
                        'sap_kind_exam_id' => $kind_exam_to_update[$sap_pred_exam_history['sap_kind_exam_id']]['id'],
                        'date_created' => $sap_pred_exam_history['date_created'],
                        'date_modified' => $sap_pred_exam_history['date_modified'],
                        'sap_id' => $sap_pred_exam_history['id']
                    );

                    if ($count_add_temp == 2000) {
                        $insert_full = Yii::$app->db->createCommand()->batchInsert('pred_exam_history',
                            ['employee_id', 'start_test_time', 'count_right', 'count_false', 'points', 'sap_kind_exam_id', 'date_created', 'date_modified', 'sap_id'],
                            $pred_exam_history_adds)->execute();
                        if ($insert_full === 0) {
                            throw new \Exception($method_name . 'Записи в таблицу pred_exam_history не добавлены');
                        } else {
                            $warnings[] = $method_name . "добавил - $insert_full - записей в таблицу pred_exam_history";
                        }

                        $pred_exam_history_adds = [];
                        $count_add_temp = 0;
                    }
                    $count_add_temp++;
                    $count_add++;
                }
            }

            if (isset($pred_exam_history_adds) && !empty($pred_exam_history_adds)) {
                $insert_full = Yii::$app->db->createCommand()->batchInsert('pred_exam_history',
                    ['employee_id', 'start_test_time', 'count_right', 'count_false', 'points', 'sap_kind_exam_id', 'date_created', 'date_modified', 'sap_id'],
                    $pred_exam_history_adds)->execute();
                if ($insert_full === 0) {
                    throw new \Exception($method_name . 'Записи в таблицу kind_exam не добавлены');
                } else {
                    $warnings[] = $method_name . "добавил - $insert_full - записей в таблицу kind_exam";
                }
            }

            $warnings[] = $method_name . 'Количество созданного оборудования: ' . $count_add;
            $warnings[] = $method_name . 'Количество обновленного оборудования: ' . $count_upd;

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

        return array('Items' => 1, 'count_all' => $count_all, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    /**
     * SavePredExam - метод массового сохранения предсменных проверок знаний в БД
     * Входной объект:
     *      pred_exams:         - массив предсменных проверок знаний
     *          []
     *              mine_id             - ключ шахтного поля
     *              employee_id         - ключ работника
     *              mo_session_id       - ключ сессии медицинского осмотра
     *              start_test_time     - время начала предсменного тестирования
     *              status_id           - ключ статуса (экзамен начат, идет, закончен)
     *              sap_kind_exam_id    - ключ вида тестирования (внешний справочник)
     *              count_right         - количество правильных ответов
     *              count_false         - количество не правильных ответов
     *              question_count      - количество вопросов
     *              points              - количество баллов
     *              sap_id              - ключ интеграции (quiz_session_id)
     *              date_created        - дата создания
     *              date_modified       - дата изменения
     * Выходной объект:
     *      Items - количество вставленных записей
     */
    public static function SavePredExam($pred_exams)
    {
        $log = new LogAmicumFront("SavePredExam");
        $result = array();

        try {
            $log->addLog("Начал выполнять метод");


            $global_insert_param_val = Yii::$app->db->queryBuilder->batchInsert('pred_exam_history', ['mine_id', 'employee_id', 'mo_session_id', 'start_test_time', 'status_id', 'sap_kind_exam_id', 'count_right', 'count_false', 'question_count', 'points', 'sap_id', 'date_created', 'date_modified'], $pred_exams);
            $insert_result_to_MySQL = Yii::$app->db->createCommand($global_insert_param_val . " ON DUPLICATE KEY UPDATE
                `status_id` = VALUES (`status_id`), 
                `question_count` = VALUES (`question_count`), 
                `count_right` = VALUES (`count_right`), 
                `count_false` = VALUES (`count_false`),
                `points` = VALUES (`points`)
                ")->execute();
            $log->addLog('Количество вставленных записей в worker_parameter_value: ' . $insert_result_to_MySQL);

            $result = $insert_result_to_MySQL;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнять метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

}


