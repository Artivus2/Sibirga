<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\queuemanagers;

use frontend\controllers\system\LogAmicumFront;
use Throwable;
use Yii;

/**
 * Class RedisQueueController
 *
 * Предназначен для укладки в кэш пакетовинтеграции с внешними системами, а также
 * дальнейшего их забора и обработки. По сути, реализует очередь обработки пакетов.
 *
 * Пакеты хранятся в кэше redis, отдельно для каждого net_id
 *
 * @package backend\controllers
 */
class RedisQueueController
{
    // buildQueueCacheKey       - Генерация ключа для кэша очереди пакетов
    // amicum_lPush             - Добавление элемента в начало списка в кэше
    // amicum_rPop              - Получение элемента из конца списка в кэше
    // amicum_flushall          - Метод очистки кеша очереди
    // PushToQuery              - Метод укладывания данных в очередь по ip
    // PullFromQuery            - Получение последнего пакета из очереди
    // SizeQuery                - Метод получения размера очереди


    /**
     * Префикс для ключей кэша очереди
     */
    const QUEUE_CACHE_KEY_PREFIX = 'ReQu';

    public $rabbit_cache;                                                                                               //название кеша
    public $name_integration_query = "working_hours";                                                                                     // название очереди интеграции

    public function __construct($name_integration_query = null)
    {
        //$this->rabbit_cache = Yii::$app->redis;
        if ($name_integration_query) {
            $this->name_integration_query = $name_integration_query;
        }
    }

    public function actionIndex()
    {
        echo 'Класс ' . __METHOD__;
    }

    /**
     * buildQueueCacheKey - Генерация ключа для кэша очереди пакетов
     * @return string сгенерированный ключ
     */
    public function buildQueueCacheKey()
    {
        return self::QUEUE_CACHE_KEY_PREFIX . ':' . $this->name_integration_query;
    }

    /**
     * amicum_lPush - Добавление элемента в начало списка в кэше
     * @param string $key Ключ
     * @param mixed $value Значение
     * @return string Количество сообщений в очереди
     */
    private function amicum_lPush($cache, $key, $value)
    {
        $data = [$key, $value];
        return $cache->executeCommand('lpush', $data);
    }

    /**
     * amicum_rPop - Получение элемента из конца списка в кэше
     * @param string $key - Ключ
     * @return mixed - Значение последнего элемента или null, если ключ не существует
     */
    private static function amicum_rPop($cache, $key)
    {
        $data = [$key];
        return $cache->executeCommand('rpop', $data);
    }

    /**
     * amicum_rSize - Получение размера очереди
     * @param string $key - Ключ очереди
     */
    private static function amicum_rSize($cache, $key)
    {
        $data = [$key];
        return $cache->executeCommand('llen', $data);
    }

    /**
     * PushToQuery - Метод укладывания данных в очередь по key
     * @param $value - значение укладываемое в очередь
     * @return array|null[]
     */
    public function PushToQuery($value)
    {
        $log = new LogAmicumFront("PushToQuery");

        try {
            $log->addLog("Начал выполнять метод");

            $cache_key = $this->buildQueueCacheKey();
            $result = self::amicum_lPush($this->rabbit_cache, $cache_key, $value);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
            $result = false;
        }
        $log->addLog("Закончил выполнять метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * PullFromQuery - Получение последнего пакета из очереди интеграции
     * @return array - Последний пакет на обработку. Если таких пакетов нет, то вернёт пустой массив
     */
    public function PullFromQuery()
    {
        $result = array();
        $log = new LogAmicumFront("PullFromQuery");
        try {
            $cache_key = $this->buildQueueCacheKey();
            $result = self::amicum_rPop($this->rabbit_cache, $cache_key);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * amicum_flushall - Метод очистки кеша очереди интеграции
     */
    public function amicum_flushall()
    {
        Yii::$app->redis_rabbit->executeCommand('flushall');

        if (REDIS_REPLICA_MODE === true) {
            $redis_replica = new yii\redis\Connection();
            $redis_replica->hostname = REDIS_REPLICA_HOSTNAME;
            $redis_replica->port = Yii::$app->redis_rabbit->port;
            $redis_replica->executeCommand('flushall');
        }
    }

    /**
     * SizeQuery - Метод получения размера очереди интеграции
     */
    public function SizeQuery()
    {
        $result = array();
        $log = new LogAmicumFront("SizeQuery");
        try {
            $cache_key = $this->buildQueueCacheKey();
            $result = self::amicum_rSize($this->rabbit_cache, $cache_key);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }

}