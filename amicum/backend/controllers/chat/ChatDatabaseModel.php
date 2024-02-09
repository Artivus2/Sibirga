<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\chat;

// Модели чата
use backend\controllers\Assistant;
use frontend\models\ChatMember;
use frontend\models\ChatMessage;
use frontend\models\ChatRecieverHistory;
use frontend\models\ChatRoom;
use frontend\models\ChatMessageReciever;

use yii\db\Exception;
use yii\db\Query;
use Yii;
use yii\helpers\ArrayHelper;

class ChatDatabaseModel implements ChatModelInterface
{

    // getChatActiveMembers - Получение активных участников конкретного чата из БД

    /**
     * Добавление нового сообщения в базу данных
     * @param string $text Текст сообщения
     * @param int $sender_worker_id Идентификатор работника, отправившего сообщение
     * @param int $room_id Идентификатор комнаты чата
     * @param string $date_time Дата создания сообщения
     * @param string $attachment_type Тип вложения
     * @param string $attachment Вложение (путь к файлу, цитата и т.д.)
     * @return mixed Идентификатор сообщения, при успешном добавлении
     * @throws Exception при ошибке добавления записи в БД
     */
    public function newMessage($text, $sender_worker_id, $room_id, $date_time, $attachment_type = null, $attachment = null)
    {
        $new_message = new ChatMessage();
        $new_message->primary_message = $text;
        $new_message->sender_worker_id = $sender_worker_id;
        $new_message->chat_room_id = (int)$room_id;
        $new_message->chat_attachment_type_id = ($attachment_type == null) ? null : (int)$attachment_type;
        $new_message->attachment = $attachment;
        $new_message->date_time = $date_time;
        if (!$new_message->save()) {
            throw new Exception(print_r($new_message->errors, true));
        }
        $new_message->refresh();
        return $new_message->id;
    }

    /**
     * Добавление новой комнаты чата в базу данных
     * @param string $title Название комнаты
     * @param int $type_id Идентификатор типа комнаты
     * @param string $creation_date Дата создания
     * @return mixed Идентификатор комнаты, при успешном добавлении
     * @throws Exception при ошибке добавления записи в БД
     */
    public function newRoom($title, $type_id, $creation_date)
    {
        $new_room = new ChatRoom();
        $new_room->title = $title;
        $new_room->chat_type_id = $type_id;
        $new_room->creation_date = $creation_date;
        if (!$new_room->save()) {
            throw new Exception(print_r($new_room->errors, true));
        }
        $new_room->refresh();
        return $new_room->id;
    }

    /**
     * Добавление нового участника чата в базу данных
     * @param int $chat_room_id Идентификатор чата/комнаты
     * @param int $worker_id Идентификатор работника
     * @param string $creation_date Дата создания
     * @param int $status_id ???
     * @param int $chat_role_id Идентификатор роли в чате
     * @return int Идентификатор участника чата, при успешном добавлении
     * @throws Exception при ошибке добавления записи в БД
     */
    public function newMember($chat_room_id, $worker_id, $creation_date, $status_id, $chat_role_id)
    {
        $new_member = ChatMember::findOne(['chat_room_id' => $chat_room_id, 'worker_id' => $worker_id]);

        if (!$new_member) {
            $new_member = new ChatMember();
            $new_member->chat_room_id = $chat_room_id;
            $new_member->worker_id = $worker_id;
            $new_member->creation_date = $creation_date;
            $new_member->status_id = $status_id;
            $new_member->chat_role_id = $chat_role_id;
            if (!$new_member->save()) {
                throw new Exception(print_r($new_member->errors, true));
            }
            $new_member->refresh();
        }

        return $new_member->id;
    }

    /**
     * Добавление получателя для сообщения
     * @param int $chat_message_id Идентификатор сообщения
     * @param int $worker_id Идентификатор работника-получателя
     * @param int $status_id_last Идентификатор последнего статуса сообщения
     * @param int $chat_room_id Идентификатор комнаты чата
     * @return mixed Идентификатор получателя сообщения, при успешном добавлении
     * @throws Exception при ошибке добавления записи в БД
     */
    public function newMessageReciever($chat_message_id, $worker_id, $status_id_last, $chat_room_id)
    {
        $new_message_reciever = new ChatMessageReciever();
        $new_message_reciever->chat_message_id = $chat_message_id;
        $new_message_reciever->worker_id = $worker_id;
        $new_message_reciever->status_id_last = $status_id_last;
        $new_message_reciever->chat_message_chat_room_id = $chat_room_id;
        if (!$new_message_reciever->save()) {
            throw new Exception(print_r($new_message_reciever->errors, true));
        }
        $new_message_reciever->refresh();
        return $new_message_reciever->id;
    }

    /**
     * Добавление новой записи в истории получения сообщений (новый статус
     * конкретного сообщения для конкретного получателя)
     * @param int $chat_message_reciever_id Идентификатор получателя сообщения
     * @param int $status_id Идентификатор статуса сообщения
     * @param string $date_time Дата и время смены статуса
     * @return int Идентификатор записи из истории получения сообщений
     * (таблица chat_reciever_history)
     * @throws Exception при ошибке добавления записи в БД
     */
    public function newMessageStatus($chat_message_reciever_id, $status_id, $date_time)
    {
        $chat_reciever_history = new ChatRecieverHistory();
        $chat_reciever_history->chat_message_reciever_id = $chat_message_reciever_id;
        $chat_reciever_history->status_id = $status_id;
        $chat_reciever_history->date_time = $date_time;
        if (!$chat_reciever_history->save()) {
            throw new Exception(print_r($chat_reciever_history->errors, true));
        }
        $chat_reciever_history->refresh();
        return $chat_reciever_history->id;
    }

    /**
     * Получение всех участников конкретного чата из БД
     * @param int $chat_room_id Идентификатор комнаты чата
     * @return array|bool массив идентификаторов воркеров в данном чате. Если нет
     * работников, удовлетворяющих условию, то вернёт false
     */
    public function getChatMembers($chat_room_id)
    {
        $query = (new Query())
            ->select([
                'chat_member.worker_id as worker_id',
                'chat_member.status_id as chat_member_status_id',
                'employee.last_name as last_name',
                'employee.first_name as first_name',
                'employee.patronymic as patronymic'
            ])
            ->from('chat_member')
            ->innerJoin('worker', 'worker.id = chat_member.worker_id')
            ->innerJoin('employee', 'employee.id = worker.employee_id')
            ->where([
                'chat_room_id' => $chat_room_id
            ])
            ->limit(100)
            ->all();

        if (!empty($query)) {
            return $query;
        }
        return false;
    }

    /**
     * getChatActiveMembers - Получение активных участников конкретного чата из БД
     * @param int $chat_room_id Идентификатор комнаты чата
     * @return array|bool массив идентификаторов воркеров в данном чате. Если нет
     * работников, удовлетворяющих условию, то вернёт false
     */
    public function getChatActiveMembers($chat_room_id)
    {
        $query = (new Query())
            ->select([
                'chat_member.worker_id as worker_id',
                'chat_member.status_id as chat_member_status_id',
                'employee.last_name as last_name',
                'employee.first_name as first_name',
                'employee.patronymic as patronymic'
            ])
            ->from('chat_member')
            ->innerJoin('worker', 'worker.id = chat_member.worker_id')
            ->innerJoin('employee', 'employee.id = worker.employee_id')
            ->where([
                'chat_room_id' => $chat_room_id,
                'chat_member.status_id' => 1
            ])
            ->limit(100)
            ->all();

        if (!empty($query)) {
            return $query;
        }
        return false;
    }

    /**
     * Получение сообщений по идентификатору чата
     * @param int $chat_room_id Идентификатор чата
     * @param null $message_id Идентификатор сообщения до которого делается выборка
     * Если не указыван, то выборка возвращает последние значения в таблице
     * @return array Массив записей из таблицы chat_message. Если нет записей,
     * удовлетворяющих условию, то вернёт пустой массив.
     */
    public function getMessagesByRoom($chat_room_id, $message_id = null)
    {
        $query = (new Query())
            ->select([
                'chat_message.id as id',
                'chat_message.id as message_id',
                'chat_message.primary_message as primary_message',
                'chat_message.sender_worker_id as sender_worker_id',
                'chat_message.chat_attachment_type_id as chat_attachment_type_id',
                'chat_message.attachment as attachment',
                'chat_message.date_time as date_time',
            ])
            ->from('chat_message')
            ->where([
                'chat_room_id' => $chat_room_id
            ])
            ->orderBy([
                'chat_message.date_time' => SORT_DESC
            ]);

        if ($message_id !== null && $message_id !== '') {
            $query->andWhere(['<', 'chat_message.id', $message_id]);
        }

        return $query->all();
    }

    /**
     * Получение сообщений по идентификатору чата вместе с их статусами
     * @param int $chat_room_id Идентификатор чата
     * @param null $message_id Идентификатор сообщения до которого делается выборка
     * Если не указан, то выборка возвращает все значения в таблице
     * @return array Массив записей из таблицы chat_message. Если нет записей,
     * удовлетворяющих условию, то вернёт пустой массив.
     */
    public function getMessagesWithStatusesByRoomWorker($chat_room_id, $message_id = null)
    {
        $session = Yii::$app->session;
        $worker_id = $session['worker_id'];
        // Получение последнего статуса сообщения, независимо от того, в каком
        // чате оно отправлено (индивидуальном или групповом)
        $last_status = (new Query())
            ->select('MAX(id) as id, chat_message_id, max(worker_id), status_id_last')
            ->from('chat_message_reciever')
            ->groupBy(['chat_message_id', 'status_id_last']);

        // Получение сообщений вместе с их последними статусами
        $query = (new Query())
            ->select([
                'chat_message.id as id',
                'chat_message.primary_message as primary_message',
                'chat_message.sender_worker_id as sender_worker_id',
                'CONCAT(employee.last_name, " ", LEFT(IFNULL(employee.first_name, ""),1), ". ", LEFT(IFNULL(employee.patronymic, ""),1), ". ") as worker_full_name',
                'chat_message.chat_attachment_type_id as chat_attachment_type_id',
                'chat_message.attachment as attachment',
                'chat_message.date_time as date_time',
                'chat_message_reciever.status_id_last as status_id_last',
                'chat_message_favorites.id as chat_message_favorites_id',
            ])
            ->from('chat_message')
            ->leftJoin('chat_message_reciever', 'chat_message.id = chat_message_reciever.chat_message_id')
            ->leftJoin('chat_message_favorites', 'chat_message.id = chat_message_favorites.chat_message_id and chat_message_favorites.worker_id=' . $worker_id)
            ->innerJoin('worker', 'worker.id = chat_message.sender_worker_id')
            ->innerJoin('employee', 'employee.id = worker.employee_id')
            ->where([
                'chat_room_id' => $chat_room_id
            ])
            ->andWhere([
                'or',
                ['chat_message_reciever.id' => (new Query())
                    ->select('id')
                    ->from($last_status)
                    ->column()],
                ['chat_message_reciever.id' => null]
            ])
            ->orderBy([
                'chat_message.date_time' => SORT_DESC
            ]);

        if ($message_id !== null && $message_id !== '') {
            $query->andWhere(['<', 'chat_message.id', $message_id]);
        }

        return $query->all();
    }

    /**
     * Получение всех чатов в которых находится данный воркер из БД c указанием закреплённый чат или нет (если нет записи о закреплении по умолчанию "не закреплён" (is_pinned = 0))
     * @param int $worker_id идентификатор работника
     * @return array|bool массив чатов с данным воркером. Если нет
     * чатов, удовлетворяющих условию, то вернёт пустой массив
     */
    public function getRoomsByWorker($worker_id)
    {
        return (new Query())
            ->select([
                'chat_room.id as id',
                'chat_room.title as title',
                'chat_room.creation_date as creation_date',
                'chat_room.chat_type_id as chat_type_id',
                'if(chat_message_pinned.is_pinned is not null,chat_message_pinned.is_pinned,0) as is_pinned',
            ])
            ->from('chat_member')
            ->innerJoin('chat_room', 'chat_room.id = chat_member.chat_room_id')
            ->leftJoin('chat_message_pinned', 'chat_message_pinned.chat_room_id = chat_room.id')
            ->where([
                'chat_member.worker_id' => $worker_id
            ])
            ->all();
    }


    /**
     * Обновление значения последнего статуса сообщения у его получателя в БД.
     * Поле status_id_last в таблице chat_message_reciever
     *
     * @param int $chat_message_id Идентификатор сообщения в чате
     * @param int $worker_id Идентификатор работника-получателя
     * @param int $status_id Идентификатор нового статуса, который сохраняем
     *
     * @return bool|mixed идентификатор изменённой записи при успешном обновлении
     * статуса в БД; false, если запись не найдена в БД
     *
     * @throws \Exception при ошибке обновления записи в БД
     */
    public function setMessageRecieverLastStatus($chat_message_id, $worker_id, $status_id)
    {
        $chat_message_reciever = ChatMessageReciever::findOne([
            'chat_message_id' => $chat_message_id,
            'worker_id' => $worker_id,
        ]);
        if ($chat_message_reciever === null) {
            throw new \Exception("В таблице chat_message_reciever нет записи с chat_message_id = $chat_message_id и worker_id = $worker_id");
        }

        $chat_message_reciever->status_id_last = $status_id;
        if (!$chat_message_reciever->save()) {
            throw new \Exception(print_r($chat_message_reciever->errors, true));
        }

        return $chat_message_reciever->id;
    }

    /**
     * Получение количества непрочитанных сообщений по каждому чату для
     * конкретного работника из БД
     * @param int $worker_id Идентификатор работника
     * @param array $chats_ids Массив идентификаторов чатов, для которых искать
     * количество непрочитанных сообщений
     * @return array ассоциативный массив, в котором ключ - идентификатор чата,
     * а значение - количество непрочитанных сообщений в нём. Если чатов нет, то
     * возвращает пустой массив. В результате содержатся только массивы, в которых
     * есть непрочитанные сообщения
     */
    public function getUnreadMessageCountByWorker($worker_id, $chats_ids = [])
    {
        $result = array();

        // Если не передан список идентификаторов чатов - берём все чаты воркера
        if (empty($chats_ids)) {
            $worker_rooms = $this->getRoomsByWorker($worker_id);
            $chats_ids = ArrayHelper::getColumn($worker_rooms, 'id');
            unset($worker_rooms);
        }

        $result = (new Query())
            ->select([
                'count(id) as count',
                'chat_message_chat_room_id'
            ])
            ->from('chat_message_reciever')
            ->where([
                'worker_id' => $worker_id,
                'status_id_last' => [28, 29],
                'chat_message_chat_room_id' => $chats_ids
            ])
            ->groupBy('chat_message_chat_room_id')
            ->all();
        $result = ArrayHelper::map($result, 'chat_message_chat_room_id', 'count');

        return $result;
    }

    /**
     * Удаление сообщения из БД.
     * ВАЖНО! Метод предполагает, что у связей с таблицей chat_message выставлена
     * опция CASCADE при удалении.
     *
     * @param int|array $message_id Идентификатор сообщения или массив идентификаторов
     * @return int|mixed Количество удалённых строк
     * @throws Exception при ошибке удаления записи
     */
    public function deleteMessage($message_id)
    {
        return (new Query())
            ->createCommand()->delete('chat_message', [
                'id' => $message_id
            ])
            ->execute();
    }

    /**
     * Удаление чата из БД.
     * ВАЖНО! Метод предполагает, что у связей с таблицей chat_room выставлена
     * опция CASCADE при удалении.
     *
     * @param int|array $chat_room_id Идентификатор переписки/комнаты чата
     * @return int|mixed Количество удалённых строк
     * @throws Exception при ошибке удаления записи
     */
    public function deleteChatRoom($chat_room_id)
    {
        return (new Query())
            ->createCommand()->delete('chat_room', [
                'id' => $chat_room_id
            ])
            ->execute();
    }


    /**
     * Получение последнего сообщения из комнаты чата
     * @param int $room_id идентификатор комнаты чата
     * @return array|bool
     */
    public function getLastMessageByRoomId($room_id)
    {
        return (new Query())
            ->select([
                'chat_message.id as id',
                'chat_message.primary_message as text',
                'chat_message.sender_worker_id as sender_worker_id',
                'chat_message.chat_room_id as chat_room_id',
                'chat_message.chat_attachment_type_id as chat_attachment_type_id',
                'chat_message.attachment as attachment',
                'chat_message.date_time as date_time'
            ])
            ->from('chat_room')
            ->innerJoin('chat_message', 'chat_message.chat_room_id = chat_room.id')
            ->where([
                'chat_room.id' => $room_id,
            ])
            ->orderBy(['chat_message.date_time' => SORT_DESC])
            ->limit(1)
            ->one();
    }
}