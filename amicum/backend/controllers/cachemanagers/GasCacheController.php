<?php


namespace backend\controllers\cachemanagers;

use Yii;

/**
 * Class OpcCache
 * @package backend\controllers\cachemanagers
 */
class GasCacheController
{

    // методы c кеш алгоритма сравнения показаний индивидуальных и стационарныз датчиков газа

    // setZonesEdgeMine - положить в кеш зону срабатывания проверки сравнения показаний индивидуальных и стационарных газов
    // getZonesEdgeMine - забрать из кеша зону срабатывания проверки сравнения показаний индивидуальных и стационарных газов
    // buildKeyZonesEdge - построение ключа кеша списка выработок с указанием сенсоров на них

    // setConjuctionSprMine - положить в кеш справочник сопряжений с указанием координат
    // getConjuctionSprMine - забрать из кеша справочник сопряжений с указанием координат
    // buildKeyConjuctionSpr - построение ключа кеша справочника сопражений

    // setShemaConjuctionEdge - положить в кеш связь сопряжений с указанием ветви
    // getShemaConjuctionEdge - забрать из кеша связь сопряжений с указанием ветви
    // buildKeyShemaConjuctionEdge - построение ключа кеша связи сопряжений с указанием ветви

    public $redis_cache;
    public static $zone_edge_cache_key = 'ZonesEdge';                           // кеш выработок в указанием стоящих на них сенсоров
    public static $conjunction_spr_cache_key = 'ConjuctionSpr';                 // кеш справочник сопряжений с координатами
    public static $shema_conjuction_edge_cache_key = 'ShemaConjuctionEdge';     // кеш связи сопряжений с указанием ветви

    public function __construct()
    {
        $this->redis_cache = Yii::$app->redis_gas;
    }

    // setZonesEdgeMine - положить в кеш зону срабатывания проверки сравнения показаний индивидуальных и стационарных газов
    // входные параметры:
    //      mine_id         - ключ шахты
    //      $zones_edges    - список выработок в указанием стоящих на них сенсоров
    public function setZonesEdgeMine($mine_id, $zones_edges)
    {
        $key = self::buildKeyZonesEdge($mine_id);
        return $this->amicum_rSet($key, $zones_edges);
    }
    // getZonesEdgeMine - забрать из кеша зону срабатывания проверки сравнения показаний индивидуальных и стационарных газов
    // входные параметры:
    //      mine_id     - ключ шахты
    public function getZonesEdgeMine($mine_id)
    {
        $key = self::buildKeyZonesEdge($mine_id);
        $zones_edge = $this->amicum_rGet($key);
        if ($zones_edge) {
            return $zones_edge;
        }
        return false;
    }

    // buildKeyZonesEdge - построение ключа кеша списка выработок с указанием сенсоров на них
    public static function buildKeyZonesEdge($key)
    {
        return $key = self::$zone_edge_cache_key . ":" . $key;
    }

    // setConjuctionSprMine - положить в кеш справочник сопряжений с указанием координат
    // входные параметры:
    //      mine_id         - ключ шахты
    //      $conjuction_spr - справочник сопряжений и их координат
    public function setConjuctionSprMine($mine_id, $conjuction_spr)
    {
        $key = self::buildKeyConjuctionSpr($mine_id);
        return $this->amicum_rSet($key, $conjuction_spr);
    }
    // getConjuctionSprMine - забрать из кеша справочник сопряжений с указанием координат
    // входные параметры:
    //      mine_id     - ключ шахты для которой получаем список ветвей с сенсорами
    public function getConjuctionSprMine($mine_id)
    {
        $key = self::buildKeyConjuctionSpr($mine_id);
        $zones_edge = $this->amicum_rGet($key);
        if ($zones_edge) {
            return $zones_edge;
        }
        return false;
    }

    // buildKeyConjuctionSpr - построение ключа кеша справочника сопражений
    public static function buildKeyConjuctionSpr($key)
    {
        return $key = self::$conjunction_spr_cache_key . ":" . $key;
    }

    // setShemaConjuctionEdge - положить в кеш связь сопряжений с указанием ветви
    // входные параметры:
    //      mine_id     - ключ шахты
    public function setShemaConjuctionEdge($mine_id, $shema_mine_conj_repac)
    {
        $key = self::buildKeyShemaConjuctionEdge($mine_id);
        return $this->amicum_rSet($key, $shema_mine_conj_repac);
    }

    // getShemaConjuctionEdge - забрать из кеша связь сопряжений с указанием ветви
    // входные параметры:
    //      mine_id     - ключ шахты
    public function getShemaConjuctionEdge($mine_id)
    {
        $key = self::buildKeyShemaConjuctionEdge($mine_id);
        $zones_edge = $this->amicum_rGet($key);
        if ($zones_edge) {
            return $zones_edge;
        }
        return false;
    }

    // buildKeyShemaConjuctionEdge - построение ключа кеша связи сопряжений с указанием ветви
    public static function buildKeyShemaConjuctionEdge($key)
    {
        return $key = self::$shema_conjuction_edge_cache_key . ":" . $key;
    }

    /**
     * amicum_mSet - Метод вставки значений в кэш командами редиса.
     * Аналогичен методу set(), только ключи не преобразуются в какой-либо формат,
     * они добавляюся как есть
     * @param $items
     * @param null $dependency
     * @return mixed
     */
    public function amicum_mSet($items, $dependency = null)
    {
        $data = [];
        foreach ($items as $key => $value) {
            $value = serialize([$value, $dependency]);
            $data[] = $key;
            $data[] = $value;
        }
        $msets = $this->redis_cache->executeCommand('mset', $data);

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'mset', $data);
        }

        return $msets;
    }

    /**
     * amicum_mSet - Метод вставки значений в кэш командами редиса.
     * Аналогичен методу set(), только ключи не преобразуются в какой-либо формат,
     * они добавляюся как есть
     * @param $key
     * @param $value
     * @param null $dependency
     * @return mixed
     */
    public function amicum_rSet($key, $value, $dependency = null)
    {
        $value = serialize([$value, $dependency]);
        $data[] = $key;
        $data[] = $value;

        $msets = $this->redis_cache->executeCommand('set', $data);

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'set', $data);
        }

        return $msets;
    }

    public function amicum_repRedis($hostname, $port, $command_redis,$data)
    {
        $errors = array();
        $warnings = array();
        $status = 1;
        $result = array();

        $warnings[] = 'amicum_repRedis. Начало метода';
        $microtime_start = microtime(true);
        try {
            $redis_replica = new yii\redis\Connection();
            $redis_replica->hostname = $hostname;
            $redis_replica->port = $port;
            $result = $redis_replica->executeCommand($command_redis, $data);
        } catch (\Throwable $exception) {
            $status = 0;
            $errors[] = 'amicum_repRedis. Исключение:';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'amicum_repRedis. Конец метода';
        return array('Items' => $result, 'warnings' => $warnings, 'errors' => $errors, 'status' => $status);
    }

    // amicum_mGet - метод получения данных с редис за один раз методами редиса
    public function amicum_mGet($keys)
    {
        $mgets = $this->redis_cache->executeCommand('mget', $keys);
        if ($mgets) {
            foreach ($mgets as $mget) {
                $result[] = unserialize($mget)[0];
            }
            return $result;
        }
        return false;
    }

    /**
     * Метод получение значения из кэша на прямую из редис
     *
     * @param $key
     * @return bool
     */
    public function amicum_rGet($key)
    {
        $key1[] = $key;
        $value = $this->redis_cache->executeCommand('get', $key1);

        if ($value) {
            $value = unserialize($value)[0];
            return $value;
        }
        return false;
    }

    /**
     * Метод удаления по указанным ключам
     */
    public function amicum_mDel($keys)
    {
        //Todo: сделать проверку в будущем на возвращаемые из redis
        if($keys)
        {
            foreach ($keys as $key)
            {
                $key1=array();
                $key1[] = $key;
                $value = $this->redis_cache->executeCommand('del', $key1);

                if (REDIS_REPLICA_MODE === true) {
                    $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'del', $key1);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Метод удаления по указанному ключу
     */
    public function amicum_rDel($key)
    {
        $key1[] = $key;
        $value = $this->redis_cache->executeCommand('del', $key1);
        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'del', $key1);
        }
    }
}