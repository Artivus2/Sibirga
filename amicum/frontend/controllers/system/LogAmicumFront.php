<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers\system;


use backend\controllers\Assistant;
use backend\controllers\cachemanagers\LogCacheController;
use Exception;
use frontend\models\AmicumSynchronization;
use Throwable;
use yii\web\Controller;

/**
 * Class LogAmicumFront - класс логирования выполнения методов системы АМИКУМ
 * Конфиг в bootstrap:
 *      define('AMICUM_DEBUG_FRONTEND_STATUS', true);                                                                   // Включение  сообщений отладки встроенной в АМИКУМ
 *      define('AMICUM_DEBUG_DATA_FRONTEND_STATUS', true);                                                              // Включение данных отладки встроенной в АМИКУМ
 * @package frontend\controllers\system
 */
class LogAmicumFront extends Controller
{
    private $method_name;                                                                                               // название логируемого метода
    private $debug_data;                                                                                                // блок отладочных данных
    private $debug;                                                                                                     // блок отладочных сообщений
    private $warnings;                                                                                                  // блок предупреждений
    private $errors;                                                                                                    // блок ошибок
    private $status;                                                                                                    // статус выполнения скрипта
    private $log_id = null;                                                                                             // ключ записи лога
    private $duration_summary = null;                                                                                   // общая продолжительность выполнения скрипта
    private $microtime_start;                                                                                           // начало выполнения скрипта
    private $microtime_current;                                                                                         // текущая отметка времени выполнения скрипта
    private $date_time_debug_start;                                                                                     // время начала выполнения метода
    private $date_time_debug_end;                                                                                       // время окончания выполнения скрипта
    private $isLogEnabled;                                                                                              // состояние включенности логов сообщений
    private $isLogDateEnabled;                                                                                          // состояние включенности логов данных

    /**
     * LogAmicum constructor.
     * @param string $method_name - название метода
     * @param bool $logEnable - принудительное включение логов для метода
     */
    public function __construct(string $method_name, bool $logEnable = false)
    {
        $this->initLog();

        if (!$logEnable) {
            $this->isLogEnabled = defined('AMICUM_DEBUG_FRONTEND_STATUS') ? AMICUM_DEBUG_FRONTEND_STATUS : false;
            $this->isLogDateEnabled = defined('AMICUM_DEBUG_DATA_FRONTEND_STATUS') ? AMICUM_DEBUG_DATA_FRONTEND_STATUS : false;
        } else {
            $this->isLogEnabled = true;
            $this->isLogDateEnabled = true;
        }

        $this->method_name = $method_name . ". ";                                                                       // название логируемого метода

        $this->microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $this->microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $this->date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
    }

    /**
     * Метод добавления точки в лог
     * @param string $description - описание текущей отладочной точки
     * @param int $count_all - количество обработанных строк
     */
    public function addLog(string $description, int $count_all = null)
    {
        if ($this->isLogEnabled) {
            $description = $this->method_name . ' ' . $description;
            $this->warnings[] = $description;                                                                               // описание текущей отладочной точки
            $this->debug['description'][] = $description;                                                                   // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $this->debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                          // текущее пиковое значение использованной памяти
            $this->debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                      // текущее количество использованной памяти
            $this->duration_summary = round(microtime(true) - $this->microtime_start, 6);               // общая продолжительность выполнения скрипта
            $this->debug['durationSummary'][] = $this->duration_summary . ' ' . $description;                               // итоговая продолжительность выполнения скрипта
            $this->debug['durationCurrent'][] = round(microtime(true) - $this->microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $this->debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . ' ' . $description; // количество обработанных записей
            $this->microtime_current = microtime(true);
            $this->date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                     // время окончания выполнения метода
        }
    }

    /**
     * Метод добавления точки в лог
     * @param string $description - описание текущей отладочной точки
     * @param string $line - строка, в которой произошла ошибка
     * @param int $count_all - количество обработанных строк
     */
    public function addError(string $description, string $line, int $count_all = null)
    {
        $this->addLog("Исключение", $count_all);
        $this->errors[] = $this->method_name . "Исключение: ";
        $this->errors[] = $description;
        $this->errors[] = "Строка: " . $line;
        $this->status = 0;
    }

    /**
     * Метод добавления точки в лог
     * @param $data - содержимое переменной
     * @param string $name_variable - название переменной
     * @param string|null $line - строка, в которой выводили данные
     */
    public function addData($data, string $name_variable = null, string $line = null)
    {
        if ($this->isLogDateEnabled) {
            $this->debug_data[] = [
                'date_time' => date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow())),
                'name_variable' => $name_variable,
                'line' => $line,
                'data' => $data
            ];
        }
    }

    /**
     * Метод возврата всех логов
     * @return array
     */
    public function getLogAll(): array
    {
        return [
            "debug" => $this->debug,
            "debug_data" => $this->debug_data,
            "warnings" => $this->warnings,
            "errors" => $this->errors,
            "status" => $this->status
        ];
    }

    /**
     * Метод возврата сокращенных логов
     * @return array
     */
    public function getLogShort(): array
    {
        return [
            "errors" => $this->errors,
            "status" => $this->status,
        ];
    }

    /**
     * Метод возврата структуры лога с заполненным только статусом
     * @return array
     */
    public function getLogOnlyStatus(): array
    {
        return [
            "debug" => [],
            "debug_data" => [],
            "warnings" => [],
            "errors" => [],
            "status" => $this->status,
        ];
    }

    /**
     * Метод возврата Предупреждений логов
     * @return array
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Метод возврата Предупреждений логов
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Метод возврата всех логов
     * @param $logs - объект с логами из какого либо метода
     * Входной объект:
     *      debug - список отладочной информации
     *      errors - список ошибок
     *      warnings - список сообщений
     */
    public function addLogAll($logs)
    {
        if (isset($logs['debug'])) {
            if (isset($logs['debug']['description'])) $this->debug['description'] = array_merge($this->debug['description'], $logs['debug']['description']);
            if (isset($logs['debug']['memory_peak'])) $this->debug['memory_peak'] = array_merge($this->debug['memory_peak'], $logs['debug']['memory_peak']);
            if (isset($logs['debug']['memory'])) $this->debug['memory'] = array_merge($this->debug['memory'], $logs['debug']['memory']);
            if (isset($logs['debug']['durationSummary'])) $this->debug['durationSummary'] = array_merge($this->debug['durationSummary'], $logs['debug']['durationSummary']);
            if (isset($logs['debug']['durationCurrent'])) $this->debug['durationCurrent'] = array_merge($this->debug['durationCurrent'], $logs['debug']['durationCurrent']);
            if (isset($logs['debug']['number_row_affected'])) $this->debug['number_row_affected'] = array_merge($this->debug['number_row_affected'], $logs['debug']['number_row_affected']);
        }
        if (isset($logs['debug_data'])) $this->debug_data = array_merge($this->debug_data, $logs['debug_data']);
        if (isset($logs['errors'])) $this->errors = array_merge($this->errors, $logs['errors']);
        if (isset($logs['warnings']) and is_array($logs['warnings'])) $this->warnings = array_merge($this->warnings, $logs['warnings']);
    }

    /**
     * Метод записи журнал логов синхронизации в БД
     * @param int $number_rows_affected - количество обработанных записей
     */
    public function saveLogSynchronization($number_rows_affected = 0)
    {
        try {
            $debug_main = [];
            if ($this->isLogDateEnabled) {
                $debug_main = array_merge($this->debug, $this->debug_data);
            } else {
                $debug_main = $this->debug;
            }
            $amicum_synchronization = new AmicumSynchronization();
            $amicum_synchronization->method_name = $this->method_name;
            $amicum_synchronization->date_time_start = $this->date_time_debug_start;
            $amicum_synchronization->date_time_end = $this->date_time_debug_end;
            $amicum_synchronization->duration = $this->duration_summary;
            $amicum_synchronization->max_memory_peak = memory_get_peak_usage() / 1024;
            $amicum_synchronization->debug = json_encode($debug_main);
            $amicum_synchronization->warnings = json_encode($this->warnings);
            $amicum_synchronization->errors = json_encode($this->errors);
            $amicum_synchronization->number_rows_affected = $number_rows_affected;

            if (!$amicum_synchronization->save()) {
                $this->addData($amicum_synchronization->errors, '$amicum_synchronization_errors', __LINE__);
                $this->addError("Не смог записать данные в журнал логов БД", __LINE__);
            }
        } catch (Throwable $ex) {
            $this->addError("Не смог записать данные в журнал логов БД", __LINE__);
        }
    }

    /**
     * Метод очистки логов
     */
    public function initLog()
    {
        $this->status = 1;

        $this->debug_data = array();                                                                                    // блок отладочных данных
        $this->debug['description'] = array();                                                                          // сообщение
        $this->debug['memory_peak'] = array();                                                                          // пиковая память
        $this->debug['memory'] = array();                                                                               // текущая выделенная память
        $this->debug['durationSummary'] = array();                                                                      // суммарная продолжительность
        $this->debug['durationCurrent'] = array();                                                                      // продолжительность конкретного этапа
        $this->debug['number_row_affected'] = array();                                                                  // количество обработанных строк

        $this->warnings = array();                                                                                      // блок предупреждений
        $this->errors = array();                                                                                        // блок ошибок
    }

    /**
     * saveLogInCache - Метод записи лога в кеш
     */
    public function saveLogInCache()
    {
        try {
            $response = LogCacheController::setLogValue($this->method_name, $this->getLogAll());
            if ($response['status'] != 1) {
                $this->addData($response['errors'], 'LogCacheController', __LINE__);
                throw new Exception("Не смог записать данные в кеш логов");
            }
        } catch (Throwable $ex) {
            $this->addError("Не смог записать данные в кеш логов ", __LINE__);
        }
    }

    /**
     * saveLogInGasCache - Метод записи лога в кеш газов
     */
    public function saveLogInGasCache()
    {
        try {
            $response = LogCacheController::setGasLogValue($this->method_name, $this->getLogAll());
            if ($response['status'] != 1) {
                $this->addData($response['errors'], 'LogCacheController', __LINE__);
                throw new Exception("Не смог записать данные в кеш логов газа");
            }
        } catch (Throwable $ex) {
            $this->addError("Не смог записать данные в кеш логов газа", __LINE__);
        }
    }

    /**
     * saveOpcLogValueCache - Метод записи лога в кеш OPC
     */
    public function saveOpcLogValueCache()
    {
        try {
            $response = LogCacheController::setOpcLogValue($this->method_name, $this->getLogAll());
            if ($response['status'] != 1) {
                $this->addData($response['errors'], 'LogCacheController', __LINE__);
                throw new Exception("Не смог записать данные в кеш логов OPC");
            }
        } catch (Throwable $ex) {
            $this->addError("Не смог записать данные в кеш логов OPC", __LINE__);
        }
    }

}