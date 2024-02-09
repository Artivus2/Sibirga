<?php

namespace backend\controllers\horizon;

use backend\controllers\Assistant;
use Exception;
use frontend\controllers\system\LogAmicumFront;
use Throwable;
use Yii;

class DaemonHorizonCache
{
    // buildDcsMapKeyHash               - Метод создания ключа службы, в котором будут храниться сведения о процессах запущенных на сервере
    // addDaemonHash                    - Метод добавление сведений о демане в кеш
    // getDaemonHash                    - метод получения демонов из кеша по ключу службы сбора данных
    // initDaemons                      - центральный метод инициализации демонов с проверкой их статусов
    // addDaemon                        - метод добавления демона к обработке очереди

    // amicum_rSetHash                  - Метод добавление данных в кеш
    // amicum_rGetMapHash               - Метод получения данных из кеша по ключу
    // amicum_rGetHash                  - Метод получение значения из кэша по мапе на прямую из редис

    public static $dcs_map_cache_key = 'DM';
    public $cache;
    private $pid;
    private $command;

    public function __construct()
    {
        $this->cache = Yii::$app->redis_service;
    }

    /**
     * buildDaemon - метод создания структуры демона, хранящейся в кеше
     * @param $mine_id - ключ шахтного поля
     * @param $net_id - сетевой адрес считывателя
     * @param $title - название считывателя
     * @param $pid - пид демона
     * @param $date_time_create - дата создания
     * @param $date_time_update - дата обновления статуса
     * @return array
     */
    public static function buildDaemon($mine_id, $net_id, $title, $pid, $date_time_create = null, $date_time_update = null)
    {
        $daemon['net_id'] = $net_id;
        $daemon['title'] = $title;
        $daemon['pid'] = $pid;
        $daemon['mine_id'] = $mine_id;
        $daemon['date_time_create'] = $date_time_create;
        $daemon['date_time_update'] = $date_time_update;
        return $daemon;
    }

    /**
     * initDaemons - центральный метод инициализации демонов с проверкой их статусов
     * @param $mine_id - ключ шахтного поля
     */
    public function initDaemons($mine_id)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $log = new LogAmicumFront("initDaemons");
        try {
            // получить из кеша по ключу службы сбора данных шлюзы на запуску демонов
            $daemons = $this->getDaemonHash($mine_id);
            if ($daemons and is_array($daemons)) {
                foreach ($daemons as $daemon) {
                    // проверить статус запущен или нет демон,
                    // если нет, то запустить
                    if (!is_array($daemon['pid'])) {
                        if ($daemon['pid'] and !$this->status($daemon['pid'])) {
                            $this->stop($daemon['pid']);
                            $daemon['pid'] = [];
                        }
                    }

                    if (!empty($daemon['pid'])) {
                        foreach ($daemon['pid'] as $pid) {
                            if ($this->status($pid)) {
                                $this->stop($pid);
                            }
                        }
                    }

                    $daemon['pid'] = [$this->start($daemon['net_id'], $mine_id)];
                    $daemon['date_time_update'] = Assistant::GetDateNow();

                    $response = $this->addDaemonHash($mine_id, $daemon);

                    $log->addLogAll($response);
                    if (!$response['status']) {
                        throw new Exception("Ошибка добавления сведений в кеш демонов");
                    }

                }
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * getDaemonHash - метод получения демонов из кеша по ключу службы сбора данных
     * @param $mine_id - ключ шахтного поля
     * @param $net_id - сетевой адрес считывателя
     * @return array|false
     */
    public function getDaemonHash($mine_id, $net_id = '*')
    {
        $dcs_map_key = self::buildDcsMapKeyHash($mine_id);
        $daemons = $this->amicum_rGetMapHash($this->cache, $dcs_map_key);

        if ($daemons and $net_id != '*') {
            foreach ($daemons as $daemon) {
                if ($daemon['net_id'] == $net_id) {
                    $result = $daemon;
                }
            }
        } else {
            $result = $daemons;
        }

        if (!isset($result) or !$daemons) {
            return false;
        }
        return $result;
    }

    /**
     * buildDcsMapKeyHash - Метод создания ключа кеша, в котором будут храниться сведения о процессах запущенных на сервере
     * @param $mine_id - ключ службы сбора данных
     * @return string
     */
    public static function buildDcsMapKeyHash($mine_id)
    {
        return self::$dcs_map_cache_key . ':' . $mine_id;
    }

    /**
     * amicum_rGetMapHash - Метод получения данных из кеша по ключу
     * @param $cache
     * @param $key
     * @return array|false
     */
    public function amicum_rGetMapHash($cache, $key)
    {
        $key1[] = $key;
        $mgets = $cache->executeCommand('hvals', $key1);
        if ($mgets) {
            foreach ($mgets as $mget) {
                $result[] = unserialize($mget);
            }
            return $result;
        }
        return false;
    }

    /**
     * Получить статус демона
     * @return bool
     */
    public function status($pid)
    {
        $command = 'ps -p ' . $pid;
        exec($command, $op);
        if (!isset($op[1])) return false;
        else return true;
    }

    /**
     * остановить демона
     * @return bool
     */
    public function stop($pid)
    {
        $command = 'kill ' . $pid;
        exec($command);
        if ($this->status($pid) == false) return true;
        else return false;
    }

    /**
     * Запуск демона в работу
     * @param $mine_id - ключ шахтного поля
     * @param $net_id - сетевой адрес считывателя
     * @return int - пид процесса
     */
    private function start($net_id, $mine_id = 0, $slave = 0)
    {
        $command = 'nohup ' . PHP_INTERPRETATOR . ' ' . YII_CONSOLE_PATH . ' strata-queue-command-line/run-queue-horizon ' . $net_id . ' ' . $mine_id . ' ' . $slave . ' > /dev/null 2>&1 & echo $!';
        exec($command, $op);
        return (int)$op[0];
    }

    /**
     * addDaemonHash - добавление сведений о демоне в кеш
     * @param $mine_id - ключ службы сбора данных
     * @param $daemon - объект демона
     * @return array
     */
    public function addDaemonHash($mine_id, $daemon)
    {
        $log = new LogAmicumFront("addDaemonHash");
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                            // массив предупреждений
        $result = null;                                                                                                 // результат вставки в кеш
        $status = 1;                                                                                                    // состояние выполнения метода
        try {
            $dcs_map_key = self::buildDcsMapKeyHash($mine_id);
            $result = $this->amicum_rSetHash($this->cache, $dcs_map_key, $daemon['net_id'], $daemon);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * amicum_rSetHash - добавление данных в кеш
     * @param $cache - кеш, в который добавляем
     * @param $map_key - ключ хэша, в который добавляем
     * @param $key - ключ, который добавляем
     * @param $value - сами данные, которые добавляем
     * @return mixed
     */
    private function amicum_rSetHash($cache, $map_key, $key, $value)
    {
        $data[] = $map_key;
        $data[] = $key;
        $data[] = serialize($value);
        $msets = $cache->executeCommand('hset', $data);
        return $msets;
    }

    /**
     * amicum_rGetHash - Метод получение значения из кэша по мапе на прямую из редис
     */
    public function amicum_rGetHash($cache, $map, $key)
    {
        $key1[] = $map;
        $key1[] = $key;
        $value = $cache->executeCommand('hget', $key1);

        if ($value) {
            $value = unserialize($value);
            return $value;
        }
        return false;
    }

    /**
     * addDaemon - метод добавления демона к обработке очереди
     * @param $mine_id - ключ шахтного поля
     * @param $net_id - сетевой адрес считывателя
     * @return array|null[]
     */
    public function addDaemon($mine_id, $net_id)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $log = new LogAmicumFront("addDaemon");
        try {
            $daemon = $this->getDaemonHash($mine_id, $net_id);
            if (empty($daemon)) {
                throw new Exception("Ошибка получения демона из кеша по айпи");
            }

            if (!is_array($daemon['pid'])) {
                $daemon['pid'] = [$daemon['pid']];
            }
            $daemon['pid'][] = $this->start($net_id, $mine_id, 1);

            $response = $this->addDaemonHash($mine_id, $daemon);
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception("Ошибка добавления сведений в кеш демонов");
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * killDaemons - центральный метод остановки демонов
     * @param $mine_id - ключ службы сбора данных
     * @param $net_id - айпи адрес службы которую надо остановить
     */
    public function killDaemons($mine_id, $net_id = null)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $log = new LogAmicumFront("killDaemons");
        try {
            // получить из кеша по ключу службы сбора данных шлюзы на запуску демонов
            $daemons = $this->getDaemonHash($mine_id);
            if ($daemons and is_array($daemons)) {
                foreach ($daemons as $daemon) {
                    if (!$net_id or $net_id == $daemon['net_id']) {
                        $count_record++;
                        if (!is_array($daemon['pid'])) {
                            $status = $this->stop($daemon['pid']);
                            $log->addLog("Статус остановки демона: " . $daemon['title'] . ". PID " . $daemon['pid'] . ". Статус остановки: " . $status);
                        } else {
                            if (!empty($daemon['pid'])) {
                                foreach ($daemon['pid'] as $pid) {
                                    $status = $this->stop($pid);
                                    $log->addLog("Статус остановки демона: " . $daemon['title'] . ". PID " . $pid . ". Статус остановки: " . $status);
                                }
                            }
                        }

                        $daemon['pid'] = [];
                        $daemon['date_time_update'] = Assistant::GetDateNow();


                        $response = $this->addDaemonHash($mine_id, $daemon);
                        $log->addLogAll($response);
                        if (!$response['status']) {
                            throw new Exception("Ошибка добавления сведений в кеш демонов");
                        }
                    }
                }
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        return array_merge(['Items' => $result], $log->getLogAll());
    }
}

?>