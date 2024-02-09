<?php

namespace backend\controllers\cachemanagers;


use backend\controllers\Assistant;
use frontend\controllers\system\LogAmicumFront;
use Yii;
use yii\db\Exception;

class LogCacheController
{
    /**
     * В разделе хранятся методы для ведение логов по результатам работы важных методов системы AMICUM
     * Метод для контроля логов => 127.0.0.1:98/cache-getter/logs?cache_key=Log*
     * Метод для рассшифровки значения => http://10.36.51.8/admin/test/convert-from-json?json=""
     * Метод http://10.36.51.8/cache-getter/flush-cache-logs?cache_key="Конкретный ключ или Log* для очистки всего лога"
     * Ошибки разделяются на два типа:
     * 1 - ошибки с малым приоритетом(не критичные)
     * 2 - это ошибки которые имеют высокий приоритет, то есть требуется немедленно исправить
     * Пример вызова:
     * $response = (new LogCacheController())->[один из нижестоящих методов. Например, setOpcLogValue]('Название метода', 'результат выполнения метода');
     * Пример вызова методов в случае вызова метода в разделе cath(Исключение):
     * $response = (new LogCacheController())->[один из нижестоящих методов. Например, setStrataLogValue]('Название метода', 'результат выполнение метода' 'приоритет ошибки(1 или 2 в соответствие важности метода)');
     */
    // buildLogKey                      - метод создания ключа amicum_mSet - Метод вставки значений в кэш командами редиса
    // amicum_rDel                      - метод удаления конкретного ключа из кэша логов
    // amicum_mDel                      - метод удаления всех значении из кэша лога
    // setOpcLogValue                   - Метод укладки значения по результатам выполнения методах OPC в кэш логов
    // setStrataLogValue                - Метод укладки значения по результатам выполнения методах сервиса strata в кэш логов
    // setModbusLogValue                - Метод укладки значения по результатам выполнения методах сервиса MudBus в кэш логов
    // setSnmpLogValue                  - Метод укладки значения по результатам выполнения методах сервиса Snmp в кэш логов
    // setSynchronizationLogValue       - Метод укладки значения по результатам выполнения методах синхронизации данных с внешних серверов в кэш логов
    // setEquipmentLogValue             - Метод укладки значения по результатам выполнения методах связанных с оборудованиями
    // setEventLogValue                 - Метод укладки значения по результатам выполнения методах связанных с событиями
    // setGasLogValue                   - Метод укладки значения по результатам выполнения методах связанных с газами
    // setHandbooksLogValue             - Метод укладки значения по результатам выполнения методах связанных с справочниками
    // setEdgeLogValue                  - Метод для логирование методов связанных с edge
    // getLogJournalFromCache           - Метод получения данных журнала логов из кеша по строке
    // getLogJournalFromCacheSource     - Метод получения данных журнала логов из кеша без декодирования json
    // amicum_rSet                      - Метод получения значений из кэш командами редиса.
    // amicum_mGet                      - метод получения значение из кеш по заданным ключам
    // getKeyLogJournalFromCache        - Метод получения ключей журнала логов из кеша


    public static $main_key_log = 'Log';                                                                                // главный ключ лога
    public static $main_key_log_strata = 'LogStr';                                                                      // ключ логов сервиса strata
    public static $main_key_log_opc = 'LogOpc';                                                                         // ключ логов сервиса OPC
    public static $main_key_log_modbus = 'LogMod';                                                                      // ключ логов сервиса ModBus
    public static $main_key_log_snmp = 'LogSnmp';                                                                       // ключ логов сервиса SNMP
    public static $main_key_log_synchronization = 'LogSy';                                                              // ключ логов методов синхронизации
    public static $main_key_log_equipment = 'LogEq';                                                                    // ключ логов методов по работе с оборудованиям
    public static $main_key_log_event = 'LogEv';                                                                        // ключ логов методов по работе с евентов
    public static $main_key_log_gas = 'LogGas';                                                                         // ключ логов методов по работе с газов
    public static $key_log_handbooks = 'LogHandbooks';                                                                  // ключ логов методов по работе с справочников
    public static $key_log_edge = 'LogEdge';                                                                            // ключ лог edge

    public $redis_cache;

    public function __construct()
    {
        $this->redis_cache = Yii::$app->redis_log;
    }

    public function actionIndex()
    {
        echo 'Класс ' . __METHOD__;
    }


    /**
     * buildLogKey - метод создания ключа
     * Если при выполнение метода было выбошено исключение, то сгенируруется ключ для кэша ошибки (Log:*:*)
     * выходные данные
     * $redis_cache_key - готовый ключ для кеша
     * @param string $name_method название метод который выполнился, так или иначе
     * @param string $key ключ под которым будет храниться информация
     * @param string $error_key ключ ошибки, если 1 то не критично, иначе если 2 то ошибка требует немедленного рассмотрения
     * @return string
     */
    public static function buildLogKey($name_method = '', $key = 'Log', $error_key = ''): string
    {
        $date = Assistant::GetDateNow();
        if ($error_key == '') {
            return $key . ':' . $name_method . ':' . $date;
        } else {
            return $key . ':' . $name_method . ':' . $error_key . ':' . $date;
        }
    }
    /**
     * buildTypeLogKey - метод создания ключа по ошибке
     * @param string $name_method - название метод который выполнился, так или иначе
     * @param string $key - ключ под которым будет храниться информация
     * @param string $error - название ошибки
     * @return string
     */
    public static function buildTypeLogKey($name_method = '', $key = 'Log', $error = ''): string
    {
        return $key . ':' . $name_method . ':' . $error;
    }

    /**
     * amicum_rSet - Метод получения значений из кэш командами редиса.
     * они добавляюся как есть
     * @param string $key ключ кеша
     * @param string $value значение для кэше
     * @param null $dependency
     * @return Exception
     */
    public function amicum_rSet($key, $value, $dependency = null)
    {
        $value = serialize([$value, $dependency]);
        $data[] = $key;
        $data[] = $value;
        return $this->redis_cache->executeCommand('set', $data);
    }


    /**
     * amicum_mGet - метод получения значение из кеш по заданым ключам
     * @param $keys - ключей для которых надо получить значения с кэша
     * @return array|bool если метод успешно выполнился то возврашается требуемых ключей, иначе false
     */
    public function amicum_mGet($keys)
    {
        $result = array();
        $m_gets = $this->redis_cache->executeCommand('mget', $keys);
        if ($m_gets) {
            foreach ($m_gets as $m_get) {
                $result[] = unserialize($m_get)[0];
            }
            return $result;
        }
        return false;
    }


    /**
     * amicum_rDel - метод удаления конкретного ключа из кэша логов
     * @param $key - ключ которого надо удалить
     * @return bool
     */
    public function amicum_rDel($key)
    {
        if ($key) {
            $this->redis_cache->executeCommand('del', $key);
            return true;
        }
        return false;
    }

    /**
     * amicum_mDel - метод удаления всех значении из кэша лога
     * @param array $keys массив ключей которых надо удалить
     * @return bool
     */
    public function amicum_mDel($keys)
    {
        if ($keys) {
            foreach ($keys as $key) {
                $key1 = array();
                $key1[] = $key;
                $this->redis_cache->executeCommand('del', $key1);
            }
            return true;
        }
        return false;
    }

    /**
     * setOpcLogValue - Метод укладки значения по результатам выполнения методах OPC в кэш логов
     * @param string $name_method имя метода
     * @param array $value_to_cache массив который  являеться результатом метода
     * @param string $error возможние ошибки при выполнение метода
     * @return array
     */
    public static function setOpcLogValue($name_method, $value_to_cache = [], $error = '')
    {
        $status = 1;
        $errors = array();
        $result = false;
        try {

            if (isset($value_to_cache)) {
                if ($error !== '') {
                    $building_key = self::buildLogKey($name_method, self::$main_key_log_opc, $error);
                } else {
                    $building_key = self::buildLogKey($name_method, self::$main_key_log_opc);
                }
                $result = (new LogCacheController)->amicum_rSet($building_key, json_encode($value_to_cache));
            } else {
                throw new Exception('setValue. Не удалось сохранить логи в кэш по результатам выполнения метода. Не переданы параметры');
            }
        } catch (Exception $exception) {
            $status = 1;
            $errors['err_msg'] = $exception->getMessage();
            $errors['err_file'] = $exception->getFile();
            $errors['err_line'] = $exception->getLine();

        }

        return array('status' => $status, 'error' => $errors, 'result' => $result);
    }

    /**
     * setTypeLogValue - Метод укладки значения результата выполнения метода по ошибке
     * @param string $name_method - имя метода
     * @param array $value_to_cache - массив, который является результатом метода
     * @param string $error - название ошибки
     * @param string $key_log - имя метода
     * @return array
     */
    public static function setTypeLogValue($name_method = '', $value_to_cache = [], $error = '', $key_log = '')
    {
        $status = 1;
        $errors = array();
        $result = false;
        try {
            if (isset($value_to_cache)) {
                $building_key = self::buildTypeLogKey($name_method, $key_log, $error);
                $result = (new LogCacheController)->amicum_rSet($building_key, json_encode($value_to_cache));
            } else {
                throw new Exception('setValue. Не удалось сохранить логи в кэш по результатам выполнения метода. Не переданы параметры');
            }
        } catch (Exception $exception) {
            $status = 1;
            $errors['err_msg'] = $exception->getMessage();
            $errors['err_file'] = $exception->getFile();
            $errors['err_line'] = $exception->getLine();
        }
        return array('status' => $status, 'error' => $errors, 'result' => $result);
    }

    /**
     * setStrataTypeLogValue - Метод укладки значения результата выполнения метода по ошибке для сервиса strata
     * @param string $name_method - имя метода
     * @param array $value_to_cache - массив, который является результатом метода
     * @param string $error - название ошибки
     * @return array
     */
    public static function setStrataTypeLogValue($name_method = '', $value_to_cache = [], $error = '')
    {
        return self::setTypeLogValue($name_method, $value_to_cache, $error, self::$main_key_log_strata);
    }

    /**
     * setStrataLogValue - Метод укладки значения по результатам выполнения методах сервиса strata в кэш логов
     * @param string $name_method - имя метода
     * @param array $value_to_cache - массив, который является результатом метода
     * @param string $error - возможные ошибки при выполнении метода
     * @return array
     */
    public static function setStrataLogValue($name_method = '', $value_to_cache = [], $error = '')
    {
        $status = 1;
        $errors = array();
        $result = false;
        try {

            if (isset($value_to_cache)) {
                if ($error !== '') {
                    $building_key = self::buildLogKey($name_method, self::$main_key_log_strata, $error);
                } else {
                    $building_key = self::buildLogKey($name_method, self::$main_key_log_strata);
                }
                $result = (new LogCacheController)->amicum_rSet($building_key, json_encode($value_to_cache));
            } else {
                throw new Exception('setValue. Не удалось сохранить логи в кэш по результатам выполнения метода. Не переданы параметры');
            }
        } catch (Exception $exception) {
            $status = 1;
            $errors['err_msg'] = $exception->getMessage();
            $errors['err_file'] = $exception->getFile();
            $errors['err_line'] = $exception->getLine();
        }

        return array('status' => $status, 'error' => $errors, 'result' => $result);
    }

    /**
     * setLogValue - Метод укладки значения по результатам выполнения методов
     * @param string $name_method - имя метода
     * @param array $value_to_cache - массив, который является результатом метода
     * @param string $error - возможные ошибки при выполнении метода
     * @return array
     */
    public static function setLogValue($name_method = '', $value_to_cache = [], $error = '')
    {
        $status = 1;
        $errors = array();
        $result = false;
        try {

            if (isset($value_to_cache)) {
                if ($error !== '') {
                    $building_key = self::buildLogKey($name_method, self::$main_key_log, $error);
                } else {
                    $building_key = self::buildLogKey($name_method, self::$main_key_log);
                }
                $result = (new LogCacheController)->amicum_rSet($building_key, json_encode($value_to_cache));
            } else {
                throw new Exception('setValue. Не удалось сохранить логи в кэш по результатам выполнения метода. Не переданы параметры');
            }
        } catch (Exception $exception) {
            $status = 1;
            $errors['err_msg'] = $exception->getMessage();
            $errors['err_file'] = $exception->getFile();
            $errors['err_line'] = $exception->getLine();
        }

        return array('status' => $status, 'error' => $errors, 'result' => $result);
    }

    /**
     * setModbusLogValue - Метод укладки значения по результатам выполнения методах сервиса MudBus в кэш логов
     * @param string $name_method имя метода
     * @param array $value_to_cache массив который  являеться результатом метода
     * @param string $error возможние ошибки при выполнение метода
     * @return array
     */
    public static function setModbusLogValue($name_method = '', $value_to_cache = [], $error = '')
    {
        $status = 1;
        $errors = array();
        $result = false;
        try {

            if (isset($value_to_cache)) {
                if ($error !== '') {
                    $building_key = self::buildLogKey($name_method, self::$main_key_log_modbus, $error);
                } else {
                    $building_key = self::buildLogKey($name_method, self::$main_key_log_modbus);
                }
                $result = (new LogCacheController)->amicum_rSet($building_key, json_encode($value_to_cache));
            } else {
                throw new Exception('setValue. Не удалось сохранить логи в кэш по результатам выполнения метода. Не переданы параметры');
            }
        } catch (Exception $exception) {
            $status = 1;
            $errors['err_msg'] = $exception->getMessage();
            $errors['err_file'] = $exception->getFile();
            $errors['err_line'] = $exception->getLine();

        }

        return array('status' => $status, 'error' => $errors, 'result' => $result);
    }

    /**
     * setSnmpLogValue - Метод укладки значения по результатам выполнения методах сервиса Snmp в кэш логов
     * @param string $name_method имя метода
     * @param array $value_to_cache массив который  являеться результатом метода
     * @param string $error возможние ошибки при выполнение метода
     * @return array
     */
    public static function setSnmpLogValue($name_method = '', $value_to_cache = [], $error = '')
    {
        $status = 1;
        $errors = array();
        $result = false;
        try {

            if (isset($value_to_cache)) {
                if ($error !== '') {
                    $building_key = self::buildLogKey($name_method, self::$main_key_log_snmp, $error);
                } else {
                    $building_key = self::buildLogKey($name_method, self::$main_key_log_snmp);
                }
                $result = (new LogCacheController)->amicum_rSet($building_key, json_encode($value_to_cache));
            } else {
                throw new Exception('setValue. Не удалось сохранить логи в кэш по результатам выполнения метода. Не переданы параметры');
            }
        } catch (Exception $exception) {
            $status = 1;
            $errors['err_msg'] = $exception->getMessage();
            $errors['err_file'] = $exception->getFile();
            $errors['err_line'] = $exception->getLine();

        }

        return array('status' => $status, 'error' => $errors, 'result' => $result);
    }

    /**
     * setSynchronizationLogValue - Метод укладки значения по результатам выполнения методах сервиса синхронизации данных с внешных серверов в кэш логов
     * @param string $name_method имя метода
     * @param array $value_to_cache массив который  являеться результатом метода
     * @param string $error возможние ошибки при выполнение метода
     * @return array
     */
    public static function setSynchronizationLogValue($name_method = '', $value_to_cache = [], $error = '')
    {
        $status = 1;
        $errors = array();
        $result = false;
        try {

            if (isset($value_to_cache)) {
                if ($error !== '') {
                    $building_key = self::buildLogKey($name_method, self::$main_key_log_synchronization, $error);
                } else {
                    $building_key = self::buildLogKey($name_method, self::$main_key_log_synchronization);
                }
                $result = (new LogCacheController)->amicum_rSet($building_key, json_encode($value_to_cache));
            } else {
                throw new Exception('setValue. Не удалось сохранить логи в кэш по результатам выполнения метода. Не переданы параметры');
            }
        } catch (Exception $exception) {
            $status = 1;
            $errors['err_msg'] = $exception->getMessage();
            $errors['err_file'] = $exception->getFile();
            $errors['err_line'] = $exception->getLine();

        }

        return array('status' => $status, 'error' => $errors, 'result' => $result);
    }


    /**
     * setEquipmentLogValue - Метод укладки значения по результатам выполнения методах связаних с оборудованиями
     * @param string $name_method имя метода
     * @param array $value_to_cache массив который  являеться результатом метода
     * @param string $error возможние ошибки при выполнение метода
     * @return array
     */
    public static function setEquipmentLogValue($name_method = '', $value_to_cache = [], $error = '')
    {
        $status = 1;
        $errors = array();
        $result = false;
        try {

            if (isset($value_to_cache)) {
                if ($error !== '') {
                    $building_key = self::buildLogKey($name_method, self::$main_key_log_equipment, $error);
                } else {
                    $building_key = self::buildLogKey($name_method, self::$main_key_log_equipment);
                }
                $result = (new LogCacheController)->amicum_rSet($building_key, json_encode($value_to_cache));
            } else {
                throw new Exception('setValue. Не удалось сохранить логи в кэш по результатам выполнения метода. Не переданы параметры');
            }
        } catch (Exception $exception) {
            $status = 1;
            $errors['err_msg'] = $exception->getMessage();
            $errors['err_file'] = $exception->getFile();
            $errors['err_line'] = $exception->getLine();

        }

        return array('status' => $status, 'error' => $errors, 'result' => $result);
    }

    /**
     * setEventLogValue - Метод укладки значения по результатам выполнения методах связаних с событиями
     * @param string $name_method имя метода
     * @param array $value_to_cache массив который  являеться результатом метода
     * @param string $error возможние ошибки при выполнение метода
     * @return array
     */
    public static function setEventLogValue($name_method = '', $value_to_cache = [], $error = '')
    {
        $status = 1;
        $errors = array();
        $result = false;
        try {

            if (isset($value_to_cache)) {
                if ($error !== '') {
                    $building_key = self::buildLogKey($name_method, self::$main_key_log_event, $error);
                } else {
                    $building_key = self::buildLogKey($name_method, self::$main_key_log_event);
                }
                $result = (new LogCacheController)->amicum_rSet($building_key, json_encode($value_to_cache));
            } else {
                throw new Exception('setValue. Не удалось сохранить логи в кэш по результатам выполнения метода. Не переданы параметры');
            }
        } catch (Exception $exception) {
            $status = 1;
            $errors['err_msg'] = $exception->getMessage();
            $errors['err_file'] = $exception->getFile();
            $errors['err_line'] = $exception->getLine();

        }

        return array('status' => $status, 'error' => $errors, 'result' => $result);
    }

    /**
     * setGasLogValue - Метод укладки значения по результатам выполнения методах связаних с газами
     * @param string $name_method имя метода
     * @param array $value_to_cache массив который  являеться результатом метода
     * @param string $error возможние ошибки при выполнение метода
     * @return array
     */
    public static function setGasLogValue($name_method = '', $value_to_cache = [], $error = '')
    {
        $status = 1;
        $errors = array();
        $result = false;
        try {

            if (isset($value_to_cache)) {
                if ($error !== '') {
                    $building_key = self::buildLogKey($name_method, self::$main_key_log_gas, $error);
                } else {
                    $building_key = self::buildLogKey($name_method, self::$main_key_log_gas);
                }
                $result = (new LogCacheController)->amicum_rSet($building_key, json_encode($value_to_cache));
            } else {
                throw new Exception('setValue. Не удалось сохранить логи в кэш по результатам выполнения метода. Не переданы параметры');
            }
        } catch (Exception $exception) {
            $status = 1;
            $errors['err_msg'] = $exception->getMessage();
            $errors['err_file'] = $exception->getFile();
            $errors['err_line'] = $exception->getLine();

        }

        return array('status' => $status, 'error' => $errors, 'result' => $result);
    }

    /**
     * setHandbooksLogValue - Метод укладки значения по результатам выполнения методах связаних с справочниками
     * @param string $name_method имя метода
     * @param array $value_to_cache массив который  являеться результатом метода
     * @param string $error возможние ошибки при выполнение метода
     * @return array
     */
    public static function setHandbooksLogValue($name_method = '', $value_to_cache = [], $error = '')
    {
        $status = 1;
        $errors = array();
        $result = false;
        try {

            if (isset($value_to_cache)) {
                if ($error !== '') {
                    $building_key = self::buildLogKey($name_method, self::$key_log_handbooks, $error);
                } else {
                    $building_key = self::buildLogKey($name_method, self::$key_log_handbooks);
                }
                $result = (new LogCacheController)->amicum_rSet($building_key, json_encode($value_to_cache));
            } else {
                throw new Exception('setValue. Не удалось сохранить логи в кэш по результатам выполнения метода. Не переданы параметры');
            }
        } catch (Exception $exception) {
            $status = 1;
            $errors['err_msg'] = $exception->getMessage();
            $errors['err_file'] = $exception->getFile();
            $errors['err_line'] = $exception->getLine();

        }

        return array('status' => $status, 'error' => $errors, 'result' => $result);
    }

    /**
     * setEdgeLogValue - Метод логирование методоа связанных с Edge (выроботками)
     * @param string $name_method имя метода
     * @param array $value_to_cache массив который  являеться результатом метода
     * @param string $error возможние ошибки при выполнение метода
     * @return array
     */
    public static function setEdgeLogValue($name_method = '', $value_to_cache = [], $error = '')
    {
        $status = 1;
        $errors = array();
        $result = false;
        try {

            if (isset($value_to_cache)) {
                if ($error !== '') {
                    $building_key = self::buildLogKey($name_method, self::$key_log_edge, $error);
                } else {
                    $building_key = self::buildLogKey($name_method, self::$key_log_edge);
                }
                $result = (new LogCacheController)->amicum_rSet($building_key, json_encode($value_to_cache));
            } else {
                throw new Exception('setValue. Не удалось сохранить логи в кэш по результатам выполнения метода. Не переданы параметры');
            }
        } catch (Exception $exception) {
            $status = 1;
            $errors['err_msg'] = $exception->getMessage();
            $errors['err_file'] = $exception->getFile();
            $errors['err_line'] = $exception->getLine();

        }

        return array('status' => $status, 'error' => $errors, 'result' => $result);
    }

    /**
     * getLogJournalFromCache - Метод получения данных журнала логов из кеша
     * @param $cache_key - искомый ключ/ключи
     * @return array
     */
    public function getLogJournalFromCache($cache_key)
    {
        $result = [];                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("getLogJournalFromCache");

        try {

            $redis = $this->redis_cache;
            $keys = $redis->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1];

            $log->addLog("Количество запрашиваемых ключей: " . count($keys));
            $log->addData($cache_key, '$cache_key', __LINE__);
            $log->addData($keys, '$keys', __LINE__);

            if ($keys) {
                $response_from_cache = $this->amicum_mGet($keys);
                foreach ($response_from_cache as $cache_item) {
                    $result[] = json_decode($cache_item);
                }
            } else {
                $log->addLog("Нет кеша с таким ключом");
            }
        } catch (Exception $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * getKeyLogJournalFromCache - Метод получения ключей журнала логов из кеша
     * @param $cache_key - искомый ключ/ключи
     * @return array
     */
    public function getKeyLogJournalFromCache($cache_key)
    {
        $result = [];                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("getKeyLogJournalFromCache");

        try {

            $redis = $this->redis_cache;
            $keys = $redis->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1];

            $log->addLog("Количество запрашиваемых ключей: " . count($keys));
            $log->addData($cache_key, '$cache_key', __LINE__);
            $log->addData($keys, '$keys', __LINE__);

            if (!$keys) {
                $log->addLog("Нет кеша с таким ключом");
            }
        } catch (Exception $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * getLogJournalFromCacheSource - Метод получения данных журнала логов из кеша без декодирования json
     * @param $cache_key - искомый ключ/ключи
     * @return array
     */
    public function getLogJournalFromCacheSource($cache_key)
    {
        $result = null;
        $response_from_cache = [];
        $log = new LogAmicumFront("getLogJournalFromCacheSource");

        try {

            $redis = $this->redis_cache;
            $keys = $redis->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1];

            $log->addLog("Количество запрашиваемых ключей: " . count($keys));
            $log->addData($keys, '$keys', __LINE__);

            if ($keys) {
                $response_from_cache = $this->amicum_mGet($keys);
            } else {
                $log->addLog("Нет кеша с таким ключом");
            }
        } catch (Exception $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $response_from_cache], $log->getLogAll());
    }
}
