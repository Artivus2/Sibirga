<?php


namespace backend\controllers\cachemanagers;

use Throwable;
use Yii;
use yii\db\Query;

/**
 * Class ServiceCache
 * @package backend\controllers\cachemanagers
 */
class ServiceCache
{
    // Методы работы с net_id
    // buildNetworkKey			- метод создания ключа для сетевых идентификаторов по id сетИд и sensor_id для кэша SensorNetworkID
    // initSensorNetwork        - метод инициализации кэша SensorNetwork из кэша
    // initSensorNetworkFromDb  - метод инициализации кэша SensorNetwork из БД
    // getSensorByNetworkId     - метод получения id сенсора по его сетевому идентификатору
    // setSensorByNetworkId     - метод добавления сенсора для сетевого идентифкатора
    // delSensorNetworkId       - метод очистки кэша SensorNetwork
    // amicum_flushall          - метод очистки кеша сервисного
    // removeAll                - Метод полного удаления сервисного кеша. Очищает все кэши связанные с сервисом

    // Методы работы с ip шлюзов
    // buildGatewayKey          - генерация ключа кэша по ip адресу шлюза для хранения id сенсора
    // getSensorIdByIp          - метод получения идентификатора сенсора по его IP адресу

    public $redis_cache;

    /** @var string Шаблон ключа кэша связей network_id и sensor_id */
    public static $sensor_network_key = 'SeNet';

    /** @var string Шаблон ключа кэша связей ip адреса шлюза и sensor_id */
    public static $gateway_sensor_id_key = 'Gate';


    //ключи статусов ССД в кэше
    public static $dcs_name = "dcs_status";
    public static $strataStatus = 'strataStatus';                                                                                        //ключ ССД страты
    public static $opcMikonStatus = 'opcMikonStatus';                                                                                    //ключ CCД OPC по стац датчикам (микон)
    public static $bpdStatus = 'bpdStatus';                                                                                  //ключ CCД БДП-3
    public static $snmpStatus = 'snmpStatus';                                                                      //ключ CCД SNMP (комутаторы)
    public static $opcEquipmentStatus = 'opcEquipmentStatus';                                                                        //ключ ССД OPC оп оборудованимя

    public function __construct()
    {
        $this->redis_cache = Yii::$app->redis_service;
    }


    /**
     * Название метода: buildNetworkKey()
     * Назначение метода: Метод создания ключа для кэша SensorNetworkID
     *
     * Входные параметры:
     *
     * @param $network_id - сетевой идентификатор сенсора
     * @param $sensor_id - идентификатор сенсора
     *
     * @return string созданный ключ
     *
     * @example $this->buildNetworkKey(310)
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 30.05.2019 16:51
     */
    public static function buildNetworkKey($network_id, $sensor_id)
    {
        return self::$sensor_network_key . ':' . $network_id . ':' . $sensor_id;
    }


    /**
     * Генерация ключа кэша по ip адресу шлюза для хранения id сенсора
     * @param $ip
     * @return string
     */
    public static function buildGatewayKey($ip)
    {
        return self::$gateway_sensor_id_key . ':' . $ip;
    }


    /**
     * метод генерация ключа для статус запуска ССД в кэш
     * @param $mine_id -   ключ шахты
     */
    public static function buildDcsKey($mine_id)
    {
        return self::$dcs_name . ':' . $mine_id;
    }


    /**
     * /**
     * Название метода: initSensorNetwork()
     * Назначение метода: Метод инициализации кэша SensorNetwork из кэша
     *
     * Входные не обязательные параметры:
     * @return  array|bool - массив данных если данные обнаружены, иначе false
     * @package backend\controllers\cachemanagers
     *
     * @example (new SensorCacheController())->initSensorNetworkFromDb();
     * @example (new SensorCacheController())->initSensorNetworkFromDb('sensor_id = 210');
     * @example (new SensorCacheController())->initSensorNetworkFromDb('network_id = 741');
     * @example (new SensorCacheController())->initSensorNetworkFromDb('network_id = 741 AND sensor_id = 210');
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 05.06.2019 14:18
     */
    public function initSensorNetwork()
    {
        try {
            $networks = (new SensorCacheController())->multiGetParameterValueHash('*', 88, 1);
            $cache_networks = [];
            if ($networks !== FALSE) {
                foreach ($networks as $network) {
                    $network_cache_key = self::buildNetworkKey($network['value'], $network['sensor_id']);
                    $cache_networks[$network_cache_key] = $network['sensor_id'];
                }
                $this->amicum_mSet($cache_networks);
                unset($cache_networks, $networks);
                return true;
            }
        } catch (Throwable $ex) {

        }
        return false;
    }


    /**
     * Название метода: initSensorNetworkFromDb()
     * Назначение метода: Метод инициализации кэша SensorNetwork из БД
     *
     * @param $net_id - сетевой идентификатор сенсора
     *
     * @return  array|bool - массив данных если данные обнаружены, иначе false
     * @package backend\controllers\cachemanagers
     *
     * @example (new SensorCacheController())->initSensorNetworkFromDb();
     * @example (new SensorCacheController())->initSensorNetworkFromDb('sensor_id = 210');
     * @example (new SensorCacheController())->initSensorNetworkFromDb('network_id = 741');
     * @example (new SensorCacheController())->initSensorNetworkFromDb('network_id = 741 AND sensor_id = 210');
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 05.06.2019 14:13
     */
    public function initSensorNetworkFromDb($net_id)
    {
        Yii::$app->db_amicum2->createCommand('SET SESSION wait_timeout = 28800;')->execute();
        $sensor_network = Yii::$app->db_amicum2->createCommand("SELECT id as sensor_id, value as network_id FROM view_sensor_network_id WHERE value='$net_id' limit 1 ;")->queryAll();
//        $sensor_network = (new Query())
//            ->select([
//                'id as sensor_id',
//                'value as network_id'
//            ])
//            ->from('view_sensor_network_id')
//            ->where([
//                'value' => $net_id
//            ])
//            ->limit(1)
//            ->one();

        if ($sensor_network and isset($sensor_network[0])) {
            $sensor_id = $sensor_network[0]['sensor_id'];
            $sensor_network_key = self::buildNetworkKey($sensor_network[0]['network_id'], $sensor_id);
            $this->amicum_rSet($sensor_network_key, $sensor_id);
            return $sensor_id;
        }
        return false;
    }


    /**
     * Название метода: getSensorByNetworkId()
     * Назначение метода: Метод получения id сенсора по его сетевому идентификатору.
     * Указать значение параметра '*', то поиск и получение будет по всем идентификаторам
     *
     * Входные обязательные параметры:
     *
     * @param $network_id - сетевой идентификатор сенсора.
     *
     * @return mixed если данных нет, то false, иначе массив данных (МНОГОМЕРНЫЙ МАССИВ)
     *
     * @example $this->getSensorByNetworkId(310)
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 30.05.2019 16:52
     */
    public function getSensorByNetworkId($network_id)
    {
        $network_cache_key = self::buildNetworkKey($network_id, '*');
        $keys = $this->redis_cache->scan(0, 'MATCH', $network_cache_key, 'COUNT', '10000000')[1];
        if ($keys) {
            return $this->amicum_rGet($keys[0]);
        }
        return false;
    }

    /**
     * Название метода: setSensorByNetworkId()
     * Назначение метода: Метод добавления сенсора для сетевого идентифкатора.
     * Сначала у конктретного сетевого идентифкатора удаляем предыдущие сенсоры, потом добавляем для него новый сенсор
     * Указать значение параметра '*', то поиск и получение будет по всем идентификаторам
     *
     * Входные обязательные параметры:
     *
     * @param $network_id - сетевой идентификатор сенсора
     * @param $sensor_id - идентификатор сенсора
     *
     * @return mixed если данных нет, то false, иначе массив данных (МНОГОМЕРНЫЙ МАССИВ)
     *
     * @example $this->setSensorByNetworkId(124545б 310)
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 30.05.2019 16:52
     */
    public function setSensorByNetworkId($network_id, $sensor_id)
    {
        /**
         * удаляем все привязанные сетевые айди к данному сенсору
         */
        $this->delSensorNetworkId('*', $sensor_id);
        /**
         * удаляем все привязанные сенсоры к данному сетевому айди
         */
        $this->delSensorNetworkId($network_id);

        $network_cache_key = self::buildNetworkKey($network_id, $sensor_id);

        return $this->amicum_rSet($network_cache_key, $sensor_id);
    }


    /**
     * Название метода: delSensorNetworkId()
     * Назначение метода: метод очистки кэша SensorNetwork.
     * По умолчанию полностью очищает кэш. Если указать конктреный параметр, то по нему удалет кэш
     *
     * Входные необязательные параметры
     *
     * @param string $network_id - сетевой ИД
     * @param string $sensor_id - ИД сенсора
     *
     * @return bool - возвращает false если данные не находит для удаления, иначе true
     *
     * @package backend\controllers\cachemanagers
     *
     * @example $this->delSensorNetworkId() - очистка кэша SensorNetworkId
     * @example $this->delSensorNetworkId('*', 1234) - удаление из кэша SensorNetworkId по ИД сенсора
     * @example $this->delSensorNetworkId(789, 1234) - удаление из кэша SensorNetworkId по ИД сенсора и сетевой ИД
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 05.06.2019 14:04
     */
    public function delSensorNetworkId($network_id = '*', $sensor_id = '*')
    {
        $network_cache_key = self::buildNetworkKey($network_id, $sensor_id);
        $keys = $this->redis_cache->scan(0, 'MATCH', $network_cache_key, 'COUNT', '10000000')[1];
        if ($keys) {
            return $this->amicum_mDel($keys);
        }
        return false;
    }


    /**
     * Метод получения идентификатора сенсора по его IP адресу.
     * @param $ip - IP адрес
     * @return mixed    - Идентификатор сенсора
     */
    public function getSensorIdByIp($ip)
    {
        $cache_key = self::buildGatewayKey($ip);
        $sensor_id = $this->amicum_rGet($cache_key);                             // Забираем значение из кеша
        if ($sensor_id === false) {                                             // Если в кеше пусто
            // Делаем запрос к базе
            $sensor_id = (new Query())
                ->select('sensor_connect_string.sensor_id')
                ->from('sensor_connect_string')
                ->leftJoin('connect_string', 'connect_string.id = sensor_connect_string.connect_string_id')
                ->where([
                    'connect_string.ip' => $ip
                ])
                ->scalar();

            $this->amicum_rSet($cache_key, $sensor_id);                          // Заносим в кеш идентификатор сенсора
            return $sensor_id;                                                  // Возвращаем идентификатор сенсора
        }
        return $sensor_id;                                                      // Возвращаем идентификатор сенсора
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

    public function amicum_repRedis($hostname, $port, $command_redis, $data)
    {
        $errors = array();
        $warnings = array();
        $status = 1;
        $result = array();

        $warnings[] = 'amicum_repRedis. Начало метода';
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
        if ($keys) {
            foreach ($keys as $key) {
                $key1 = array();
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

    /***
     * Метод получения из кэше сообщении кторых надо отправить на ЛУЧ
     * Примичание: метод нечего не вернет если ССД Strata запушена, так как она удлает их после того как отправляет их на ЛУЧ
     */
    public function getMessagesFromCache()
    {
        $value = array();
        $len = $this->redis_cache->llen();
        if ($len !== 0) {
            $value = $this->redis_cache->lrange('packages', '0 ' . $len);
        } else {
            $value[] = 'getMessagesFromCache. Кэш пусть. Сообщение на отправку не имеется';
        }
        return $value;
    }

    /**
     * Название метода: removeAll()
     * Назначение метода: Метод полного удаления сервисного кеша. Очищает все кэши связанные с сервисом, а именно:
     *    -- $gateway_sensor_id_key
     *    -- $sensor_network_key
     * @example EquipmentCacheController::removeAll();
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 31.05.2019 13:57
     */
    public function removeAll()
    {
        $gateway_sensor_keys = $this->redis_cache->scan(0, 'MATCH', self::$gateway_sensor_id_key . ':*', 'COUNT', '10000000')[1];
        if ($gateway_sensor_keys)
            $this->amicum_mDel($gateway_sensor_keys);

        $sensor_network_keys = $this->redis_cache->scan(0, 'MATCH', self::$sensor_network_key . ':*', 'COUNT', '10000000')[1];
        if ($sensor_network_keys)
            $this->amicum_mDel($sensor_network_keys);

    }

    // amicum_flushall - метод очистки кеша сервисного
    public function amicum_flushall()
    {
        $this->redis_cache->executeCommand('flushall');

        if (REDIS_REPLICA_MODE === true) {
            // главный кеш
            $redis_replica = new yii\redis\Connection();
            $redis_replica->hostname = REDIS_REPLICA_HOSTNAME;
            $redis_replica->port = $this->redis_cache->port;
            $redis_replica->executeCommand('flushall');
        }
    }

    /**
     * метод добовления устанвоки значения статуса ССД в кэш
     * @param $key
     * @param $dcs_name
     * @param $value
     * @return mixed
     */

    public function amicum_rSetHash($map_key, $key, $value)
    {

        $data[] = $map_key;
        $data[] = $key;
        $data[] = serialize($value);

        $msets = $this->redis_cache->executeCommand('hset', $data);

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedisHash(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'hset', $data);
        }


        return $msets;
    }

    public function amicum_repRedisHash($hostname, $port, $command_redis, $data)
    {
        $errors = array();
        $warnings = array();
        $status = 1;
        $result = array();

        $warnings[] = 'amicum_repRedisHash. Начало метода';
        try {
            $redis_replica = new yii\redis\Connection();
            $redis_replica->hostname = $hostname;
            $redis_replica->port = $port;
            $result = $redis_replica->executeCommand($command_redis, $data);
        } catch (\Throwable $exception) {
            $status = 0;
            $errors[] = 'amicum_repRedisHash. Исключение:';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'amicum_repRedisHash. Конец метода';
        return array('Items' => $result, 'warnings' => $warnings, 'errors' => $errors, 'status' => $status);
    }

    /**
     * метод массовой уставноки значения
     */
    public function amicum_mSetHash($items)
    {
        $msets = 0;
        foreach ($items as $map_key => $values) {
            $data[] = $map_key;
            foreach ($values as $key => $value) {
                $data[] = $key;
                $data[] = serialize($value);
            }
            $msets = 1;
            $this->redis_cache->executeCommand('hset', $data);
            if (REDIS_REPLICA_MODE === true) {
                $this->amicum_repRedisHash(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'hset', $data);
            }
        }
        return $msets;
    }

    /**
     * метод для получения статуса ССД по конретному ключу
     * @param $map
     * @param $key
     * @return bool
     */
    public function amicum_rGetHash($map, $key)
    {
        $map_key[] = $map;
        $map_key[] = $key;
        $status = $this->redis_cache->executeCommand('hget', $map_key);
        if ($status) {
            return unserialize($status);
        }
        return false;
    }

    /**
     * метод для проверки статуса ССД по конретному ключу
     * @param $dcs_name - название службы сбора данных
     * @param $mine_id - ключ шахты
     * @return bool
     */
    public function CheckDcsStatus($mine_id, $dcs_name)
    {
        $map = self::buildDcsKey($mine_id);
        return $this->amicum_rGetHash($map, $dcs_name);
    }


    /**
     * ChangeDcsStatus - метод изминения статус разришения на запись ССД службам
     * @param $status_dcses
     * @param $mine_id
     */
    public function ChangeDcsStatus($status_dcses, $mine_id)
    {

        $dcs_keys[] = 'strataStatus';
        $dcs_keys[] = 'opcMikonStatus';
        $dcs_keys[] = 'bpdStatus';
        $dcs_keys[] = 'snmpStatus';
        $dcs_keys[] = 'opcEquipmentStatus';
        $array_to_set = null;
        foreach ($dcs_keys as $dcs_name) {
            $map = self::buildDcsKey($mine_id);
            $array_to_set[$map][$dcs_name] = $status_dcses;
        }
        return $this->amicum_mSetHash($array_to_set);
    }

}
