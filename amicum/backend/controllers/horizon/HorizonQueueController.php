<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\horizon;

use backend\controllers\Assistant;
use Exception;
use frontend\controllers\system\LogAmicumFront;
use Throwable;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;

/**
 * Class HorizonQueueController
 *
 * Предназначен для укладки в кэш пакетов (в JSON формате) от службы, а также
 * дальнейшего их забора и обработки. По сути, реализует очередь обработки пакетов.
 *
 * Сделано для того, чтобы служба не висела, ожидая обработки пакета и получения
 * ответа от сервера.
 *
 * Пакеты хранятся в кэше redis, отдельно для каждого net_id
 *
 * @package backend\controllers
 */
class HorizonQueueController extends Controller
{
    // buildQueueCacheKey       - Генерация ключа для кэша очереди пакетов
    // amicum_lPush             - Добавление элемента в начало списка в кэше
    // amicum_rPop              - Получение элемента из конца списка в кэше
    // PushToQuery              - Метод укладывания данных в очередь по ip
    // PullFromQuery            - Получение последнего пакета из очереди
    // actionPushToQueue        - Метод укладывания данных в очередь
    // actionPullFromQueue      - Метод получения данных из очередь
    // actionGetQueueSize       - возвращает длину конкретной очереди
    // actionGetQueuesSize      - возвращает длину всех очередей
    // actionRemoveQueues       - очищает все очереди
    // amicum_rDelHash          - метод удаления ключа из кеша

    /**
     * Префикс для ключей кэша очереди
     */
    const QUEUE_CACHE_KEY_PREFIX = 'StQu';

    /**
     * actionPushToQueue - Метод укладывания данных в очередь
     * Пример: 127.0.0.1/admin/horizon/horizon-queue/push-to-queue?ip=172.16.59.42&json={'ff':212}
     */
    public function actionPushToQueue()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $log = new LogAmicumFront("actionPushToQueue");

        try {
            $log->addLog("Начало выполнения метода");
            $post = Assistant::GetServerMethod();
            $json = $post['json'];
            $ip = $post['ip'];

            $response = self::PushToQuery($ip, $json);
            $log->addLogAll($response);
            $result = $response['Items'];

            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * PushToQuery - Метод укладывания данных в очередь по ip
     * @param $net_id - сетевой адрес считывателя
     * @param $jsonPack - пакет с данными
     * @return array|null[]
     */
    public static function PushToQuery($net_id, $jsonPack)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("PushToQuery");

        try {
            $log->addLog("Начал выполнять метод");

            $cache_key = self::buildQueueCacheKey($net_id);
            $result = self::amicum_lPush($cache_key, $jsonPack);

            $log->addLog("Закончил выполнять метод");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * PushToQuery - Метод убивания очреди
     * @param $net_id - сетевой адрес считывателя
     * @return array|null[]
     */
    public static function killQuery($net_id)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("killQuery");

        try {
            $log->addLog("Начал выполнять метод");

            $cache_key = self::buildQueueCacheKey($net_id);
            $result = self::amicum_rDelHash($cache_key);

            $log->addLog("Закончил выполнять метод");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * buildQueueCacheKey - Генерация ключа для кэша очереди пакетов
     * @param int $net_id - сетевой адрес считывателя
     * @return string сгенерированный ключ
     */
    public static function buildQueueCacheKey($net_id)
    {
        return self::QUEUE_CACHE_KEY_PREFIX . ':' . $net_id;
    }

    /**
     * amicum_lPush - Добавление элемента в начало списка в кэше
     * @param string $key Ключ
     * @param mixed $value Значение
     */
    private static function amicum_lPush($key, $value)
    {
        $cache = Yii::$app->redis_yii2;
        $data = [$key, $value];
        $response = $cache->executeCommand('lpush', $data);
        return $response;
    }

    /**
     * actionPullFromQueue - Метод получения данных из очередь
     * Пример: 127.0.0.1/admin/horizon/horizon-queue/pull-from-queue?net_id=2:256
     */
    public function actionPullFromQueue()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $log = new LogAmicumFront("actionPullFromQueue");

        try {
            $log->addLog("Начало выполнения метода");
            $post = Assistant::GetServerMethod();
            $net_id = $post['net_id'];

            $response = self::PullFromQuery($net_id);
            $log->addLogAll($response);
            $result = $response['Items'];

            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * PullFromQuery - Получение последнего пакета из очереди
     * @return array последний пакет на обработку. Если таких пакетов нет, то вернёт пустой массив
     */
    public static function PullFromQuery($net_id, $cache_key = null)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("PullFromQuery");
        try {
            if (!$cache_key) {
                $cache_key = self::buildQueueCacheKey($net_id);
            }
            $result = self::amicum_rPop($cache_key);
            unset($cache_key);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * amicum_rPop - Получение элемента из конца списка в кэше
     * @param string $key Ключ
     * @return mixed значение последнего элемента или null, если ключ не существует
     */
    private static function amicum_rPop($key)
    {
        $cache = Yii::$app->redis_yii2;
        $data = [$key];
        return $cache->executeCommand('rpop', $data);
    }

    /**
     * actionGetQueueSize - возвращает длину конкретной очереди
     * Пример: 127.0.0.1/admin/horizon/horizon-queue/get-queue-size?net_id=2:252
     */
    public function actionGetQueueSize()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $log = new LogAmicumFront("actionGetQueueSize");

        try {
            $log->addLog("Начало выполнения метода");
            $post = Assistant::GetServerMethod();
            $net_id = $post['net_id'];

            $response = self::GetQueueSizeByIp($net_id);
            if (!$response['status']) {
                throw new Exception("Ошибка получения длины очереди");
            }

            $result = [
                'net_id' => $net_id,
                'size' => $response['Items']
            ];

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetQueueSize - возвращает длину конкретной очереди
     * Пример: 127.0.0.1/admin/horizon/horizon-queue/get-queue-size?net_id=2:652
     */
    public static function GetQueueSizeByIp($net_id)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $log = new LogAmicumFront("GetQueueSize");

        try {
            $log->addLog("Начало выполнения метода");

            $cache_key = self::buildQueueCacheKey($net_id);
            $result = self::amicum_llen($cache_key);

            $log->addLog("Окончание выполнения метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Получение длины списка
     * @param string $key Ключ
     * @return int длина списка. Если ключа не существует, возвращает 0
     */
    private static function amicum_llen($key)
    {
        $data = [$key];
        return Yii::$app->redis_yii2->executeCommand('llen', $data);
    }

    /**
     * Получение длины списка
     * @param string $key Ключ
     * @return int длина списка. Если ключа не существует, возвращает 0
     */
    private static function amicum_keys($key)
    {
        $key = [$key];
        return Yii::$app->redis_yii2->executeCommand('keys', $key);
    }

    /**
     * actionGetQueuesSize - возвращает длину всех очередей
     * Пример: 127.0.0.1/admin/horizon/horizon-queue/get-queues-size
     */
    public function actionGetQueuesSize()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $log = new LogAmicumFront("actionGetQueuesSize");

        try {
            $log->addLog("Начало выполнения метода");
            $gateways = (new Query())
                ->select('sensor_title, sensor_id, mine_id, net_id')
                ->from('vw_sensor_mine_by_sensor_type')
                ->where(["sensor_type_id" => 11])
                ->all();

            $all_size = 0;
            foreach ($gateways as $gateway) {
                $cache_key = self::buildQueueCacheKey($gateway['net_id']);
                $queue_size = $this->amicum_llen($cache_key);

                $result[] = [
                    'queue_cache_key' => $cache_key,
                    'size' => $queue_size
                ];
                $all_size += $queue_size;
            }
            if ($result) {
                ArrayHelper::multisort($result, 'size', SORT_DESC);
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['all' => $all_size, 'Items' => $result], $log->getLogAll());
    }

    /**
     * actionRemoveQueues - очищает все очереди
     * Пример: 127.0.0.1/admin/horizon/horizon-queue/remove-queues
     */
    public function actionRemoveQueues()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $log = new LogAmicumFront("actionGetQueuesSize");

        try {
            $log->addLog("Начало выполнения метода");
            $gateways = (new Query())
                ->select('sensor_title, sensor_id, mine_id, net_id')
                ->from('vw_sensor_mine_by_sensor_type')
                ->where(["sensor_type_id" => 11])
                ->all();

            foreach ($gateways as $gateway) {
                $cache_key = self::buildQueueCacheKey($gateway['net_id']);
                $status_del = $this->amicum_rDelHash($cache_key);

                $result[] = [
                    'queue_cache_key' => $cache_key,
                    'status_del' => $status_del
                ];
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * amicum_rDelHash - метод удаления ключа из кеша
     * @param $map
     * @param $key
     */
    public function amicum_rDelHash($key)
    {
        $key1[] = $key;
        return Yii::$app->redis_yii2->executeCommand('del', $key1);
    }

    /**
     * Укладка пакета в очередь
     * @param int $net_id сетевой идентификатор объекта
     * @param string $jsonPack пакет в формате JSON
     */
    public function push($net_id, $jsonPack)
    {
        $cache_key = self::buildQueueCacheKey($net_id);
        try {
            $this->amicum_lPush($cache_key, $jsonPack);
        } catch (Throwable $exception) {
            // TODO: узнать куда писать логи
        }
    }

    /**
     * Получение последних пакетов из всех очередей
     * @return array список последних пакетов на обработку. Если таких пакетов
     * нет, то вернёт пустой массив
     */
    public function popAll()
    {
        $json_packets_list = [];

        $cache_keys_filter = self::buildQueueCacheKey('*', '*');
        $cache_keys = Yii::$app->redis_yii2->scan(0, 'MATCH', $cache_keys_filter, 'COUNT', '10000000')[1];
        if ($cache_keys) {
            foreach ($cache_keys as $cache_key) {
                try {
                    $packet = $this->amicum_rPop($cache_key);
                    if ($packet != null) {
                        $json_packets_list[] = $packet;
                    }
                } catch (Throwable $exception) {
                    // TODO: узнать куда писать логи
                };
            }
        }

        return $json_packets_list;
    }

    /**
     * Возвращает длину конкретной очереди или массив из длин всех очередей, если
     * не передан параметр $net_id
     * @param string|int $net_id Сетевой идентификатор объекта
     *
     * @param bool $assoc указывает в каком формате возвращать данные.
     * Если true - вернёт ассоциативным массивом:
     * [
     *  [queue_cache_key_1] => size_1,
     *  [queue_cache_key_2] => size_2,
     *  ...
     * ]
     * Если false - вернёт обычным массивом:
     * [
     *  [0] => ['queue_cache_key' => 1, 'size' => 1],
     *  [1] => ['queue_cache_key' => 2, 'size' => 2],
     *  ...
     * ]
     *
     * @return array массив с информацией о длинах списков
     */
    public function getQueueSize($net_id = '*', $assoc = false)
    {
        $status_list = [];

        $cache_keys_filter = self::buildQueueCacheKey($net_id);
        $cache_keys = Yii::$app->redis_yii2->scan(0, 'MATCH', $cache_keys_filter, 'COUNT', '10000000')[1];
        if ($cache_keys) {
            foreach ($cache_keys as $cache_key) {
                try {
                    $queue_size = $this->amicum_llen($cache_key);
                    if ($queue_size !== null) {
                        if ($assoc === true) {
                            $status_list[$cache_key] = $queue_size;
                        } else {
                            $status_list[] = [
                                'queue_cache_key' => $cache_key,
                                'size' => $queue_size
                            ];
                        }
                    }
                } catch (Throwable $exception) {
                    // TODO: узнать куда писать логи
                }
            }
        }

        return $status_list;
    }

    /**
     * Получение элементов списка из кэша
     * @param string $key Ключ
     * @param int $start Индекс начала
     * @param int $stop Индекс конца
     * @return mixed Массив значений списка из кэша. Если кэш пуст, то вернёт
     * пустой массив
     */
    private function amicum_lrange($key, $start, $stop)
    {
        $data = [$key, $start, $stop];
        return Yii::$app->redis_yii2->executeCommand('lrange', $data);
    }

    /** метод получения ключей очередей
     * @return array|int[]|null[]
     */
    public static function getQuriesKeys($mine_id)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $log = new LogAmicumFront("getQuriesKeys");

        try {
            $log->addLog("Начало выполнения метода");

            $cache_key = self::buildQueueCacheKey($mine_id . ":*");
            $result = self::amicum_keys($cache_key);

            $log->addLog("Окончание выполнения метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}