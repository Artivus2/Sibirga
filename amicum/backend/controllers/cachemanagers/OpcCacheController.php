<?php


namespace backend\controllers\cachemanagers;

use Yii;

// buildTagKey                  -   создать ключ параметра тэга
// buildSensorKey               -   создать ключ тэга
// buildStructureTag            -   Метод создания структуры тэга и его значения в кеше
// multiDelTag                  -   Метод удаления тегов по ключу OPC серверу
// amicum_mGet                  -   метод получения данных с редис за один раз методами редиса
// amicum_mSet                  -   Метод вставки значений в кэш командами редиса.

class OpcCacheController
{
    public static $tag_key = 'Opc';

    public $redis_cache;

    public function __construct()
    {
        $this->redis_cache = Yii::$app->redis_sensor_parameter;
    }

    public function actionIndex()
    {
        echo 'Класс ' . __METHOD__;
    }

    /**
     * @param $sensor_id - идентификатор сенсора. Если указать '*', то возвращает все сенсоры
     * @param $parameter_id - идентификатор параметра. Если указать '*', то возвращает все параметры
     * @param $parameter_type_id - идентификатор типа параметра
     * @param $sensor_tag_name - Название тэга
     * @param $sensor_parameter_value - Значение тэга тэга
     * @return string созданный ключ кэша в виде SensorParameter:sensor_id:parameter_id:parameter_type_id
     *
     * @package backend\controllers\cachemanagers
     * Название метода: buildTagKey()
     * Назначение метода: Метод создания ключа кэша для списка сенсорово с их значениями (SensorParameter)
     *
     * Входные обязательные параметры:
     * @author Rasul <fai@pfsz.ru>
     */
    public static function buildTagKey($sensor_id = '*', $parameter_id = '*', $parameter_type_id = '*')
    {
        return self::$tag_key . ':' . $sensor_id . ':' . $parameter_id . ':' . $parameter_type_id;
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
     * amicum_mSet - Метод вставки значений в кэш командами редиса. Аналогичен методу set(), только ключи не преобразуются в какой-либо формат,
     * они добавляюся как есть
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

    /**
     * amicum_mSet - Метод вставки значений в кэш командами редиса. Аналогичен методу set(), только ключи не преобразуются в какой-либо формат,
     * они добавляюся как есть
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


    // buildStructureTag - Метод создания структуры тэга и его значения в кеше
    // сделан, что бы легче было создавать массив для массовой вставки
    // входние параметры
    //      $sensor_id                   - айди сеносора
    //      $sensor_tag_name             - имя тэга
    //      $sensor_parameter_value      - значения тэга
    //      $parameter_id                - айди параметра
    //      $parameter_type_id           - тип параметра
    // вsходние параметры:
    //      $sensor_parameter_value_to_cache - структруризованный массив параметров тэга
    // разработал: Файзуллоев А.Э.
    public static function buildStructureTag($sensor_id, $sensor_tag_name, $sensor_parameter_value, $parameter_id, $parameter_type_id)
    {
        $sensor_parameter_value_to_cache['sensor_id'] = $sensor_id;
        $sensor_parameter_value_to_cache['sensor_tag_name'] = $sensor_tag_name;
        $sensor_parameter_value_to_cache['sensor_parameter_value'] = $sensor_parameter_value;
        $sensor_parameter_value_to_cache['parameter_id'] = $parameter_id;
        $sensor_parameter_value_to_cache['parameter_type_id'] = $parameter_type_id;

        return $sensor_parameter_value_to_cache;
    }

    // multiGetTag - метод получения всех теголв по заданному ключе OPC сервера
    // входние параметры:
    //      $sensor_id - ключ кеша OPC
    // выходние параметры
    //      $sensor_id                   - айди сеносора
    //      $sensor_tag_name             - имя тэга
    //      $sensor_parameter_value      - значения тэга
    //      $parameter_id                - айди параметра
    //      $parameter_type_id           - тип параметра
    // разработал: Файзуллоев А.Э.
    public function multiGetTag($sensor_id)
    {
        // Находим в кеше все ключи, соответствующие условию (3 параметр в функции scan)
        $tag_key = self::buildTagKey($sensor_id);
        $keys = $this->redis_cache->scan(0, 'MATCH', $tag_key, 'COUNT', '10000000')[1];
        if ($keys) {
            $tag_values = $this->amicum_mGet($keys);
            return $tag_values;
        }
        return null;
    }

    // multiDelTag - Метод удаления тегов по ключу OPC серверу
    // входние параметры:
    // $sensor_id - ключ кеша OPC
    // разработал: Файзуллоев А.Э.
    public function multiDelTag($sensor_id)
    {
        $tag_key = self::buildTagKey($sensor_id);
        $keys = $this->redis_cache->scan(0, 'MATCH', $tag_key, 'COUNT', '10000000')[1];
        if ($keys) {
            $this->amicum_mDel($keys);                                                                                  // Очищаем кеш тегов, которые требуется отправить
        }
    }
}