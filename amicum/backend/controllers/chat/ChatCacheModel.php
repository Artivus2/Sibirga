<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\chat;

use backend\controllers\Assistant;
use Yii;
use yii\helpers\ArrayHelper;

class ChatCacheModel// implements ChatModelInterface
{
    public $cache_engine;

    public function __construct($cache_engine = null)
    {
        if ($cache_engine === null) {
            $this->cache_engine = new ChatCacheEngine();
        } else {
            $this->cache_engine = $cache_engine;
        }
    }

    /**
     * Добавление нового сообщения в кэш
     * @param string $text Текст сообщения
     * @param int $sender_worker_id Идентификатор воркера отправителя
     * @param int $room_id Идентификатор чата
     * @param string $date_time Дата добавления сообщения
     * @param int $id Идентификатор сообщения
     * @param null $attachment_type Тип вложения
     * @param null $attachment Вложение
     * @return mixed Результат сохранения в кэш
     *
     * @warning Сигнатура метода отличается от сигнатуры интерфейса аргументом $id.
     * Следует учитывать это при использовании.
     */
    public function newMessage($text, $sender_worker_id, $room_id, $date_time, $id, $attachment_type = null, $attachment = null)
    {
        $cache_key = ChatCacheBuilder::buildChatMessageKey($room_id, $id);
        $cache_value = ChatCacheBuilder::buildChatMessageStructure(
            $id, $text, $sender_worker_id, $room_id, $attachment_type, $attachment, $date_time
        );
        return $this->cache_engine->amicum_rSet($cache_key, $cache_value);
    }

    /**
     * Создание новой группы/комнаты (чата) в кэше
     * @param string $title Название комнаты чата
     * @param int $type_id Идентификатор типа чата
     * @param string $creation_date Дата создания
     * @param int $chat_id Идентификатор чата
     * @return mixed Результат сохранения в кэш
     */
    public function newRoom($title, $type_id, $creation_date, $chat_id)
    {
        $key = ChatCacheBuilder::buildChatRoomKey($chat_id);
        $value = ChatCacheBuilder::buildChatRoomStructure(
            $chat_id, $title, $creation_date, $type_id
        );
        return $this->cache_engine->amicum_rSet($key, $value);
    }

    /**
     * Создание участника в кэше
     * @param int $id Идентификатор участника в БД
     * @param int $room_id Идентификатор комнаты
     * @param int $worker_id Идентификатор работника
     * @param int $creation_date Дата создания
     * @param int $status_id Идентификатор статуса ???
     * @param int $role_id Идентификатор роли в чате
     * @return mixed Результат сохранения в кэш
     */
    public function newMember($id, $room_id, $worker_id, $creation_date, $status_id, $role_id)
    {
        $key = ChatCacheBuilder::buildChatMemberKey($room_id, $worker_id);
        $value = ChatCacheBuilder::buildChatMemberStructure(
            $id, $room_id, $worker_id, $creation_date, $status_id, $role_id
        );
        return $this->cache_engine->amicum_rSet($key, $value);
    }

    /**
     * Получение сообщений по идентификатору чата
     * @param int $chat_room_id Идентификатор чата
     * @return bool Массив сообщений или false, если в кэше пусто
     */
    public function getMessagesByRoom($chat_room_id)
    {
        $cache_key_filter = ChatCacheBuilder::buildChatMessageKey($chat_room_id, '*');
        $cache_keys = $this->cache_engine->cache->scan(0, 'MATCH', $cache_key_filter, 'COUNT', '10000000')[1];
        if ($cache_keys) {
            return $this->cache_engine->amicum_mGet($cache_keys);
        }

        return false;
    }

    /**
     * Получение всех чатов, в которых находится данный воркер из кэша
     * @param int $worker_id идентификатор работника
     * @return bool массив чатов. Если их нет, то вернёт false
     */
    public function  getRoomsByWorker($worker_id)
    {
        $cache_key_filter = ChatCacheBuilder::buildChatRoomKey($worker_id, '*');
        $cache_keys = $this->cache_engine->cache->scan(0, 'MATCH', $cache_key_filter, 'COUNT', '10000000')[1];

        if ($cache_keys) {
            return $this->cache_engine->amicum_mGet($cache_keys);
        }

        return false;
    }

    /**
     * Обновление значения последнего статуса сообщения у его получателя в кэше.
     * @param int $chat_message_id Идентификатор сообщения в чате
     * @param int $worker_id Идентификатор работника-получателя
     * @param int $status_id Идентификатор нового статуса, который сохраняем
     */
    public function setMessageRecieverLastStatus($chat_message_id, $worker_id, $status_id)
    {
        $cache_key = ChatCacheBuilder::buildMessageStatusKey($chat_message_id, $worker_id);
        $cache_value = ChatCacheBuilder::buildMessageStatusStructure($status_id);
        $this->cache_engine->amicum_rSet($cache_key, $cache_value);
        $this->cache_engine->amicum_expire($cache_key, 86400);
    }

    /**
     * Удаление сообщения из кэша
     * @param $message_id
     */
    public function deleteMessage($message_id)
    {
        // Очистка кэша сообщений
        $cache_key_filter = ChatCacheBuilder::buildChatMessageKey('*', $message_id);
        $cache_keys = $this->cache_engine->cache->scan(0, 'MATCH', $cache_key_filter, 'COUNT', '10000000')[1];

        if ($cache_keys) {
            $this->cache_engine->amicum_mDel($cache_keys);
        }

        // Очистка кэша статусов сообщений
        $cache_key_filter = ChatCacheBuilder::buildMessageStatusKey($message_id, '*');
        $cache_keys = $this->cache_engine->cache->scan(0, 'MATCH', $cache_key_filter, 'COUNT', '10000000')[1];

        if ($cache_keys) {
            $this->cache_engine->amicum_mDel($cache_keys);
        }
    }

    /**
     * Удаление чата из кэша.
     * @param int $room_id Идентификатор комнаты чата
     */
    public function deleteChatRoom($room_id)
    {
        // Очистка кэша участников
        $cache_key_filter = ChatCacheBuilder::buildChatRoomKey('*', $room_id);
        $cache_keys = $this->cache_engine->cache->scan(0, 'MATCH', $cache_key_filter, 'COUNT', '10000000')[1];

        if ($cache_keys) {
            $this->cache_engine->amicum_mDel($cache_keys);
        }

        // Очистка кэша сообщений
        $cache_key_filter = ChatCacheBuilder::buildChatMessageKey($room_id, '*');
        $cache_keys = $this->cache_engine->cache->scan(0, 'MATCH', $cache_key_filter, 'COUNT', '10000000')[1];

        if ($cache_keys) {
            $this->cache_engine->amicum_mDel($cache_keys);
        }
    }

    /**
     * Удаление всех сообщений чата из кэша
     * @param int $room_id Идентификатор комнаты чата
     */
    public function clearChatRoomMessages($room_id)
    {
        $cache_key_filter = ChatCacheBuilder::buildChatRoomKey($room_id, '*');
        $cache_keys = $this->cache_engine->cache->scan(0, 'MATCH', $cache_key_filter, 'COUNT', '10000000')[1];

        if ($cache_keys) {
            $messages = $this->cache_engine->amicum_mGet($cache_keys);
            $messages_ids = ArrayHelper::getColumn($messages, 'id');
            $this->cache_engine->amicum_mDel($cache_keys);
        }

        if (isset($messages_ids)) {
            foreach ($messages_ids as $message_id) {
                $cache_key_filter = ChatCacheBuilder::buildMessageStatusKey($message_id, '*');
                $cache_keys = $this->cache_engine->cache->scan(0, 'MATCH', $cache_key_filter, 'COUNT', '10000000')[1];

                if ($cache_keys) {
                    $this->cache_engine->amicum_mDel($cache_keys);
                }
            }
        }
    }


    public function getUnreadMessageCountByWorker($worker_id, $chat_id)
    {
        $cache_key = ChatCacheBuilder::buildRoomUnreadedKey($worker_id, $chat_id);
        return $this->cache_engine->amicum_rGet($cache_key);
    }

    public function setUnreadMessageCountByWorker($worker_id, $chat_id, $unread_msg_count)
    {
        $cache_key = ChatCacheBuilder::buildRoomUnreadedKey($worker_id, $chat_id);
        $cache_value = ChatCacheBuilder::buildRoomUnreadedStructure($unread_msg_count);
        $this->cache_engine->amicum_rSet($cache_key, $cache_value);
        $this->cache_engine->amicum_expire($cache_key, 60);
    }

    // TODO: учесть удаление кэша статусов сообщений
    /**
     * Обновление кэша сообщений. Выдергивает из кэша сообщения из всех чатов и
     * оставляет только последние 20
     * @return array|void
     */
    public function update()
    {
        $all_messages = $this->getMessagesByRoom('*');
        if (!$all_messages)
            return;

        $all_chat_messages = ArrayHelper::map($all_messages, 'id', 'date_time', 'chat_room_id');
        foreach ($all_chat_messages as $room_id => &$chat_messages) {
            uasort($chat_messages, static function ($a, $b) {
                if ($a == $b) {
                    return 0;
                }
                return (strtotime($a) > strtotime($b)) ? -1 : 1;
            });
            $chat_messages = array_slice($chat_messages, 10, null, true);
            foreach ($chat_messages as $message_id => $date_time) {
                $cache_key = ChatCacheBuilder::buildChatMessageKey($room_id, $message_id);
                $this->cache_engine->amicum_rDel($cache_key);
            }
        }

        return $all_chat_messages;
    }
}

/**
 * Class ChatCacheEngine
 * Содержит методы работы с кэшем, специфичные для амикума
 * @package backend\controllers\chat
 */
class ChatCacheEngine
{
    public $cache;

    public function __construct($cache = null)
    {
        if ($cache === null) {
            $this->cache = Yii::$app->redis_service;
        } else {
            $this->cache = $cache;
        }
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
        $msets = $this->cache->executeCommand('mset', $data);

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->cache->port, 'mset', $data);
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

        $msets = $this->cache->executeCommand('set', $data);

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->cache->port, 'set', $data);
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
        $mgets = $this->cache->executeCommand('mget', $keys);
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
        $value = $this->cache->executeCommand('get', $key1);

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
                $value = $this->cache->executeCommand('del', $key1);
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
        $value = $this->cache->executeCommand('del', $key1);
    }

    /**
     * Задание ключу времени жизни
     * @param $key
     * @param $duration
     */
    public function amicum_expire($key, $duration)
    {
        $data = [$key, $duration];
        $this->cache->executeCommand('expire', $data);

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->cache->port, 'expire', $data);
        }

    }

    /**
     * Добавление элемента в список в кэше
     * @param string $key Ключ
     * @param $value - значение
     */
    public function amicum_lpush($key, $value)
    {
        $data = [$key, $value];
        $this->cache->executeCommand('lpush', $data);

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->cache->port, 'lpush', $data);
        }
    }

    /**
     * "Обрезка" списка в кэше
     * @param string $key Ключ
     * @param int $start Индекс начала обрезки
     * @param int $stop Индекс конца обрезки
     */
    public function amicum_ltrim($key, $start, $stop)
    {
        $data = [$key, $start, $stop];
        $this->cache->executeCommand('ltrim', $data);

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->cache->port, 'ltrim', $data);
        }
    }

    /**
     * Получение элементов списка из кэша
     * @param string $key Ключ
     * @param int $start Индекс начала
     * @param int $stop Индекс конца
     * @return mixed Массив значений списка из кэша. Если кэш пуст, то вернёт
     * пустой массив
     */
    public function amicum_lrange($key, $start, $stop)
    {
        $data = [$key, $start, $stop];
        return $this->cache->executeCommand('lrange', $data);
    }
}

class ChatCacheBuilder
{
    public static $chat_message_key = 'ChMsg';
    public static $chat_room_key = 'ChRoom';
    public static $chat_member_key = 'ChMemb';
    public static $chat_message_status_key = 'ChMsgStat';
    public static $chat_room_unreaded = 'ChUnread';

    /**
     * Построение ключа кэша для хранения сообщений
     * @param int $chat_id Идентификатор чата
     * @param int $message_id Идентификатор
     * @return string ключ
     */
    public static function buildChatMessageKey($chat_id, $message_id)
    {
        return self::$chat_message_key . ':' . $chat_id . ':' . $message_id;
    }

    /**
     * Построение структуры кэша сообщения
     * @param int $id Идентификатор сообщения
     * @param string $text Текст сообщения
     * @param int $sender_worker_id Идентификатор воркера-отправителя
     * @param int $room_id Идентификатор чата
     * @param int $attachment_type_id Идентификатор типа вложения
     * @param string $attachment Вложение
     * @param string $date_time Дата отправки сообщения
     * @return array значение для укладки в кэш
     */
    public static function buildChatMessageStructure($id, $text, $sender_worker_id,
                                                     $room_id, $attachment_type_id, $attachment, $date_time)
    {
        return array(
            'id' => $id,
            'primary_message' => $text,
            'sender_worker_id' => $sender_worker_id,
            'chat_room_id' => $room_id,
            'chat_attachment_type_id' => $attachment_type_id,
            'attachment' => $attachment,
            'date_time' => $date_time
        );
    }

    /**
     * Построение ключа кэша для хранения чатов
     * @param int $chat_id Идентификатор чата
     * @return string ключ
     */
    public static function buildChatRoomKey($chat_id)
    {
        return self::$chat_room_key . ':' . $chat_id;
    }

    /**
     * Построение структуры кэша чата
     * @param int $id идентификатор сообщения
     * @param string $title название чата
     * @param string $creation_date дата создания чата
     * @param int $chat_type_id идентификатор типа чата
     * @return array значение для укладки в кэш
     */
    public static function buildChatRoomStructure($id, $title, $creation_date, $chat_type_id)
    {
        return array(
            'id' => $id,
            'title' => $title,
            'creation_date' => $creation_date,
            'chat_type_id' => $chat_type_id
        );
    }

    /**
     * Построение ключа кэша для хранения участников чатов
     * @param int $chat_id идентификатор чата
     * @param int $worker_id идентификатор участника
     * @return string ключ
     */
    public static function buildChatMemberKey($chat_id, $worker_id)
    {
        return self::$chat_member_key . ':' . $chat_id . ':' . $worker_id;
    }

    /**
     * Построение структуры кэша участника чата
     * @param int $id идентификатор участника чата
     * @param int $chat_id идентификатор чата
     * @param int $worker_id идентификатор работника
     * @param string $creation_date дата добавления
     * @param int $status_id идентификатор статуса ???
     * @param int $role_id идентификатор роли участника в чате
     * @return array значение для укладки в кэш
     */
    public static function buildChatMemberStructure($id, $chat_id, $worker_id, $creation_date, $status_id, $role_id)
    {
        return array(
            'id' => $id,
            'chat_room_id' => $chat_id,
            'worker_id' => $worker_id,
            'creation_date' => $creation_date,
            'status_id' => $status_id,
            'chat_role_id' => $role_id
        );
    }

    /**
     * Построение ключа кэша для хранения статуса сообщения
     * @param int $message_id идентификатор сообщения
     * @param int $worker_id идентификатор работника-получателя
     * @return string ключ
     */
    public static function buildMessageStatusKey($message_id, $worker_id)
    {
        return self::$chat_message_status_key . ':' . $message_id . ':' . $worker_id;
    }

    /**
     * Построение структуры кэша статуса сообщения
     * @param int $status_id Идентификатор статуса
     * @return mixed значение для укладки в кэш
     */
    public static function buildMessageStatusStructure($status_id)
    {
        return $status_id;
    }

    /**
     * Построение ключа кэша для хранения количества непрочитанных сообщений в
     * конкретном чате для данного воркера
     * @param int $worker_id идентификатор работника
     * @param int $room_id идентификатор комнаты чата
     * @return string ключ кэша
     */
    public static function buildRoomUnreadedKey($worker_id, $room_id)
    {
        return self::$chat_room_unreaded . ':' . $worker_id . ':' . $room_id;
    }

    /**
     * Построение структуры кэша количества непрочитанных сообщений
     * @param int $unread_msg_count количество непрочитанных сообщений
     * @return mixed значение для укладки в кэш
     */
    public static function buildRoomUnreadedStructure($unread_msg_count)
    {
        return $unread_msg_count;
    }
}