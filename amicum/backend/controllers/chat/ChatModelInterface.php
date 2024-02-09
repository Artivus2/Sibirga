<?php


namespace backend\controllers\chat;


interface ChatModelInterface
{
    /**
     * Создание нового сообщения
     * @param string $text Текст сообщения.
     * @param int $sender_worker_id Идентификатор воркера, отправившего сообщение
     * @param int $room_id Идентификатор комнаты чата
     * @param string $date_time Дата создания сообщения
     * @param int $attachment_type Тип вложения
     * @param string $attachment Вложение (путь к файлу, цитата и т.д.)
     * @return mixed
     */
    public function newMessage($text, $sender_worker_id, $room_id, $date_time, $attachment_type = null, $attachment = null);

    /**
     * Создание новой группы/комнаты (чата)
     * @param string $title Название комнаты чата
     * @param int $type_id Идентификатор типа комнаты
     * @param string $creation_date Дата создания
     * @return mixed
     */
    public function newRoom($title, $type_id, $creation_date);

    /**
     * Добавление нового участника чата
     * @param int $chat_room_id Идентификатор чата/комнаты
     * @param int $worker_id Идентификатор работника
     * @param string $creation_date Дата создания
     * @param int $status_id ???
     * @param int $chat_role_id Идентификатор роли в чате
     * @return mixed
     */
    public function newMember($chat_room_id, $worker_id, $creation_date, $status_id, $chat_role_id);

    /**
     * Добавление получателя для сообщения
     * @param int $chat_message_id Идентификатор сообщения
     * @param int $worker_id Идентификатор работника-получателя
     * @param int $status_id_last Идентификатор последнего статуса сообщения
     * @param int $chat_room_id Идентификатор комнаты чата
     * @return mixed
     */
    public function newMessageReciever($chat_message_id, $worker_id, $status_id_last, $chat_room_id);

    /**
     * Добавление новой записи в истории получения сообщений (новый статус
     * конкретного сообщения для конкретного получателя)
     * @param int $chat_message_reciever_id Идентификатор получателя сообщения
     * @param int $status_id Идентификатор статуса сообщения
     * @param string $date_time Дата и время смены статуса
     * @return mixed
     */
    public function newMessageStatus($chat_message_reciever_id, $status_id, $date_time);

    /**
     * Получение всех участников конкретного чата
     * @param int $chat_room_id Идентификатор чата
     * @return mixed
     */
    public function getChatMembers($chat_room_id);

    /**
     * Получение сообщений по идентификатору чата
     * @param int $chat_room_id Идентификатор чата
     * @param int $message_id Идентификатор сообщения до которого делается выборка
     * @return mixed
     */
    public function getMessagesByRoom($chat_room_id, $message_id);

    /**
     * Получение всех чатов в которых находится данный воркер
     * @param int $worker_id идентификатор работника
     * @return mixed
     */
    public function getRoomsByWorker($worker_id);

    /**
     * Обновление значения последнего статуса сообщения у его получателя.
     * @param int $chat_message_id Идентификатор сообщения в чате
     * @param int $worker_id Идентификатор работника-получателя
     * @param int $status_id Идентификатор нового статуса, который сохраняем
     * @return mixed
     */
    public function setMessageRecieverLastStatus($chat_message_id, $worker_id, $status_id);

    /**
     * Получение количества непрочитанных сообщений по каждому чату для
     * конкретного работника
     * @param int $worker_id Идентификатор работника
     * @param array $chats_ids Массив идентификаторов чатов, для которых искать
     * количество непрочитанных сообщений
     * @return array ассоциативный массив, в котором ключ - идентификатор чата,
     * а значение - количество непрочитанных сообщений в нём. Если чатов нет, то
     * возвращает пустой массив. В результате содержатся только массивы, в которых
     * есть непрочитанные сообщения
     */
    public function getUnreadMessageCountByWorker($worker_id, $chats_ids = []);

    /**
     * Удаление сообщения
     * @param int $message_id Идентификатор сообщения
     * @return mixed
     */
    public function deleteMessage($message_id);

    /**
     * Удаление переписки/комнаты чата
     * @param int $chat_room_id Идентификатор чата
     * @return mixed
     */
    public function deleteChatRoom($chat_room_id);
}