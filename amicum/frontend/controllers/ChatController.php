<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers;

use backend\controllers\Assistant;
use backend\controllers\chat\ChatCacheModel;
use backend\controllers\chat\ChatDatabaseModel;
use backend\controllers\const_amicum\StatusEnumController;
use Exception;
use frontend\controllers\Assistant as FrontAssistant;
use frontend\controllers\Assistant as FrontendAssistant;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\ChatMember;
use frontend\models\ChatMessageFavorites;
use frontend\models\ChatMessagePinned;
use frontend\models\ChatMessageReciever;
use frontend\models\ChatRecieverHistory;
use frontend\models\ChatRoom;
use frontend\models\Worker;
use Throwable;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\Controller;

// TODO: проверка прав при удалении сообщений/чатов
// TODO: периодически синхронизировать кэш через cron

class CHAT_ROLE
{
    const ADMIN = 1;        // администратор
    const MEMBER = 2;       // участник
    const WATCHER = 3;      // наблюдатель
}

class ChatController extends Controller
{
    // методы чата
    // actionInitCache                      - Инициализация кэша чата
    // actionGetContactInfo                 - Получение информации о выбранном контакте
    // actionGetGroupInfo                   - Получение информации о выбранной группе
    // actionGetReceiversInChat             - Получение получателей сообщения для конкретного чата
    // actionGetContacts                    - Получение контактов чата
    // actionAttachPicture                  - Сохранение вложенного изображения
    // actionDeleteChatRoom                 - Удаление чата
    // actionClearChatMessages              - Удаление всех сообщений из чата
    // actionDeleteMessage                  - Удаление сообщения
    // actionAddMemberToChat                - Добавление работника к чату
    // actionAddMembersToChat               - Добавление работников к чату
    // actionSetMessageRecieverLastStatus   - Обновление значения последнего статуса сообщения у его получателя.
    // actionGetRoomsByWorker               - Получение чатов, в которых участвует работник, по его идентификатору
    // actionGetMessagesArchiveByRoom       - Получение архивных сообщений по идентификатору комнаты чата
    // actionGetMessagesByRoom              - Получение сообщений по идентификатору комнаты чата
    // actionNewRoom                        - Создание группы (комнаты) чата
    // actionNewChat                        - Создание индивидуального чата (только для двух участников)
    // actionNewMessage                     - Добавление нового сообщения в БД и кэш
    // actionEditTitleGroupChat             - изменить название группы
    // actionAddWorkerInGroupChat           - добавить работника в групповой чат
    // actionDelWorkerFromGroupChat         - удалить работника из групповой чат
    // actionEditWorkerInGroupChat          - изменить статус работника в групповой чат
    // actionGetLastMemberStatus            - получить список статусов членов чата
    // actionEditStatusMessage              - изменить статус сообщения на прочитанное
    // actionEditPinnedMessageWorker        - изменить закрепление сообщения в чате
    // actionAddFavoritesMessageWorker      - добавить сообщение в избранные у пользователя
    // actionDelFavoritesMessageWorker      - удалить сообщение из избранных у пользователя
    // actionGetFavoritesMessageWorker      - получить список избранных сообщений

    /** МЕТОДЫ РАБОТЫ С ЧАТОМ В МОБИЛЬНОЙ ВЕРСИИ НАРЯДНОЙ СИСТЕМЫ */
    // AddNewRoom                           - Метод добавление новой комнаты чата при создании ее в рамках отчета по наряду
    // GetMessagesChatByRoomId              - Метод получения списка сообщений по ключу комнаты
    // SaveMessageChat                      - Метод сохранения сообщения Чата
    // DeleteMessageChat                    - Метод удаления сообщения Чата


    /**
     * actionNewMessage - Добавление нового сообщения в БД и кэш
     *
     * Необходимые POST поля:
     *   text               - текст сообщения. В случае если есть вложение, то в нем хранится имя файла
     *   id                 - ключ сообщения
     *   sender_worker_id   - идентификатор работника-отправителя сообщения
     *   chat_room_id       - идентификатор комнаты чата
     *   attachment_type    - тип вложения. Может быть пустым
     *   attachment         - При получении (либо BLOB, либо текст, либо пустое) Путь к вложению при возврате. Может быть пустым
     *   attachment_title   - Название вложения
     *
     * @param null $post_json - строка с параметрами метода в json формате
     *
     * @return array
     *   Items - идентификаторы записей из таблицы chat_message_reciever (связки сообщения и его получателя)
     * @example Воркер с идентификатором 123 отправляет сообщение в чат с идентификатором 4
     *   без вложений
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionNewMessage&data={"text":"Привет!","sender_worker_id":123,"chat_room_id":4,"attachment_type":"","attachment":""}&subscribe=
     *
     */
    public static function actionNewMessage($post_json = null)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $new_message_id = -1;
        $added_messages_ids = array();

        try {
            $warnings[] = __FUNCTION__ . '. Начало метода, параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            // проверка на наличие текста сообщения, отправителя, ключа комнаты и типа сообщения как параметров
            $post_valid = isset($post['text'], $post['sender_worker_id'], $post['chat_room_id'], $post['attachment_type']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            // проверка на наличие заполенного отправителя
            if (empty($post['sender_worker_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор воркера отправителя');
            }

            // проверка на наличие заполенного ключа комнаты
            if (empty($post['chat_room_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор комнаты чата');
            }

            // проверка на наличие заполенного текстового сообщения и типа сообщения
            if ($post['text'] === '' && empty($post['attachment_type'])) {
                throw new Exception(__FUNCTION__ . '. Нельзя отправить пустое сообщение');
            }

            $attachment = "";                                                                                           // вложение, при типе вложения 1/2/3/4(изображение, видео, аудио, файл) - ожидается BLOB, 5(цитата) - ожидается текст
            $attachment_title = "AmicumUnknownFile";                                                                    // вложение, при типе вложения 1/2/3/4(изображение, видео, аудио, файл) - ожидается BLOB, 5(цитата) - ожидается текст
            if (isset($post['attachment'])) {
                $attachment = $post['attachment'];                                                                      // вложение с фронта BLOB
                $attachment_title = $post['attachment_title'];                                                          // название вложения
            }

            $text = $post['text'];                                                                                      // само текстовое сообщение
            $sender_worker_id = $post['sender_worker_id'];                                                              // отправитель текстового сообщения
            $chat_room_id = $post['chat_room_id'];                                                                      // ключ комнаты
            $chat_attachment_type_id = $post['attachment_type'];                                                        // тип вложения 1 - изображение, 2 - видео, 3 - аудио, 4 - файлы, 5 цитата, null или "" - без вложения

            /**
             * Проверка на наличие вложения и загрузка файла в систему при его наличие
             * если тип вложения 1 или 2, проверяем на наличие самого вложения, а затем сохраняем его на сервер, получаем путь
             * и записываем его в само вложение
             */
            if ($chat_attachment_type_id == 1 or $chat_attachment_type_id == 2 or
                $chat_attachment_type_id == 3 or $chat_attachment_type_id == 4) {
                if (!$attachment) {
                    throw new Exception(__FUNCTION__ . '. Отсутствует отправляемое вложение');
                }
                $attachment = FrontendAssistant::UploadFileChat($attachment, $attachment_title, 'chat_message');
            }


            /**=================================================================
             * Проверка роли отправителя сообщения
             * ===============================================================*/
            $chat_member = ChatMember::find()
                ->with('chatRole')
                ->joinWith('worker.employee')
                ->where([
                    'chat_room_id' => $chat_room_id,
                    'worker_id' => $sender_worker_id
                ])
                ->limit(1)
                ->one();
            if ($chat_member === null) {
                throw new Exception(__FUNCTION__ . '. В чате нет такого участника');
            }
            if ($chat_member->chatRole->title === 'Наблюдатель') {
                throw new Exception(__FUNCTION__ . '. Наблюдатели не могут общаться в этой комнате');
            }
            $warnings[] = __FUNCTION__ . '. Проверена роль отправителя';

            $current_date = Assistant::GetDateNow();
            $chat_database = new ChatDatabaseModel();
            $chat_cache = new ChatCacheModel();
            /**=================================================================
             * Добавление нового сообщения в БД
             * ===============================================================*/
            try {
                $worker_full_name = $chat_member->worker->employee->last_name . ' ' . mb_substr($chat_member->worker->employee->first_name, 0, 1) . ". " . mb_substr($chat_member->worker->employee->patronymic, 0, 1) . ".";
                $new_message_id = $chat_database->newMessage($text, $sender_worker_id, $chat_room_id, $current_date, $chat_attachment_type_id, $attachment);
            } catch (Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка при добавлении сообщения в БД';
                throw $exception;
            }
            $warnings[] = __FUNCTION__ . '. Сообщение добавлено в БД';

            /**=================================================================
             * Добавление нового сообщения в кэш
             * ===============================================================*/
            /*try {
                $result  = $chat_cache->newMessage($text, $sender_worker_id, $chat_room_id, $current_date, $new_message_id, $chat_attachment_type_id, $attachment);
            } catch (\Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка при добавлении сообщения в кэш';
                throw $exception;
            }
            $warnings[] = __FUNCTION__ . '. Сообщение добавлено в кэш';*/

            /**=================================================================
             * Добавление получателя сообщения и запись статуса в историю
             * ===============================================================*/
            $recievers_ids = $chat_database->getChatActiveMembers($chat_room_id);
            if ($recievers_ids !== false) {
                $recievers_ids = ArrayHelper::getColumn($recievers_ids, 'worker_id');
            } else {
                throw new Exception(__FUNCTION__ . '. В группе нет участников');
            }

            foreach ($recievers_ids as $reciever_id) {
                if ($reciever_id != $sender_worker_id) {
                    try {
                        $message_reciever_id = $chat_database->newMessageReciever($new_message_id, $reciever_id, 29/*StatusEnumController::MSG_SENDED*/, $chat_room_id);
                        $added_messages_ids[] = $chat_database->newMessageStatus($message_reciever_id, 29/*StatusEnumController::MSG_SENDED*/, $current_date);
                    } catch (Throwable $exception) {
                        $errors[] = __FUNCTION__ . '. Ошибка добавления получателя сообщения или его статуса';
                        throw $exception;
                    }
                }
            }
            $warnings[] = __FUNCTION__ . '. Получатели сообщения и статусы сохранены';

            /**=================================================================
             * Отправка сообщения на вебсокет
             * ===============================================================*/
            try {
                $ws_msg = json_encode(array(
                    'clientType' => 'server',
                    'actionType' => 'publish',
                    'clientId' => 'server',
                    'subPubList' => [$chat_room_id],
                    'messageToSend' => json_encode(array(
                        'type' => 'chat_message_send',
                        'message' => json_encode(array(
                            'room_id' => $chat_room_id,                                                                 // ключ комнаты
                            'id' => $new_message_id,                                                                    // ключ сообщения
                            'sender_worker_id' => $sender_worker_id,                                                    // ключ отправителя
                            'worker_full_name' => $worker_full_name,                                                    // Фамилия И.О. работника
                            'primary_message' => $text,                                                                 // текст сообщения
                            'chat_attachment_type_id' => $chat_attachment_type_id,                                      // тип сообщения (изображение, видео, цитата)
                            'attachment' => $attachment,                                                                // вложение
                            'date_time' => $current_date                                                                // дата и время сообщения
                        ))
                    ))
                ));
                WebsocketController::actionSendMsg('ws://' . AMICUM_CONNECT_STRING_WEBSOCKET . '/ws', $ws_msg);
            } catch (Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка отправки сообщения на вебсокет сервер';
                throw $exception;
            }


            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $added_messages_ids, 'message_id' => $new_message_id, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }


    /**
     * actionNewChat - Создание индивидуального чата (только для двух участников)
     * Необходимые POST поля:
     *   sender_worker_id - идентификатор работника-отправителя сообщения
     *   reciever_worker_id - идентификатор работника-получателя сообщения
     *
     * @param null $post_json - строка с параметрами метода в json формате
     *
     * @return array
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionNewChat&data={"sender_worker_id":321,"reciever_worker_id":123}&subscribe=
     *
     */
    public static function actionNewChat($post_json = null)
    {
        $status = 1;
        $errors = array();
        $warnings = array();

        $chat_id = -1;

        try {
            $warnings[] = __FUNCTION__ . '. Начало метода, параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['sender_worker_id'], $post['reciever_worker_id']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['sender_worker_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор воркера отправителя');
            }

            if (empty($post['reciever_worker_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор воркера получателя');
            }

            $sender_worker_id = $post['sender_worker_id'];
            $reciever_worker_id = $post['reciever_worker_id'];

            $current_date = Assistant::GetDateNow();
            $chat_title = "Чат $sender_worker_id и $reciever_worker_id";
            $create_members = false;

            $chat_database = new ChatDatabaseModel();
            $chat_cache = new ChatCacheModel();
            /**=================================================================
             * Создание чата в БД, если его нет
             * ===============================================================*/
            try {
                $chat = (new Query())
                    ->select('id')
                    ->from('chat_room')
                    ->where(['title' => $chat_title])
                    ->orWhere(['title' => "Чат $reciever_worker_id и $sender_worker_id"])
                    ->one();
                if ($chat) {
                    $chat_id = $chat['id'];
                    $warnings[] = __FUNCTION__ . '. Чат найден в БД';
                } else {
                    $chat_id = (string)$chat_database->newRoom($chat_title, 1 /*индивидуальный*/, $current_date);
                    $create_members = true;
                    $warnings[] = __FUNCTION__ . '. Чат добавлен в БД';
                }
            } catch (Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка создания индивидуального чата в БД';
                throw $exception;
            }

            /**=================================================================
             * Создание чата в кэше
             * ===============================================================*/
            /*try {
                $chat_cache->newRoom($chat_title, 1, $current_date, $chat_id);
            } catch (\Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка создания индивидуального чата в кэше';
                throw $exception;
            }
            $warnings[] = __FUNCTION__ . '. Чат добавлен в кэш';*/

            /**=================================================================
             * Создание участников чата в БД и кэше
             * ===============================================================*/
            try {
                if ($create_members) {
                    $member_id = $chat_database->newMember($chat_id, $sender_worker_id, $current_date, 1, 2 /*Участник*/);
                    //$chat_cache->newMember($member_id, $chat_id, $sender_worker_id, $current_date, 1, 2 /*Участник*/);
                    $member_id = $chat_database->newMember($chat_id, $reciever_worker_id, $current_date, 1, 2 /*Участник*/);
                    //$chat_cache->newMember($member_id, $chat_id, $sender_worker_id, $current_date, 1, 2 /*Участник*/);
                }
            } catch (Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка добавления участников чата';
                throw $exception;
            }
            $warnings[] = __FUNCTION__ . '. Участники добавлены в БД и кэш';

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $chat_id, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }


    /**
     * actionNewRoom - Создание группы (комнаты) чата
     * Необходимые POST поля:
     *   title - название комнаты чата
     *   workers_ids - идентификаторы участников
     * @param null $post_json - строка с параметрами метода в json формате
     *
     * @return array
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionNewRoom&data={"title":"Мой чат","workers_ids":[123,456,789]}&subscribe=
     *
     */
    public static function actionNewRoom($post_json = null)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $session = Yii::$app->session;

        try {
            $warnings[] = __FUNCTION__ . '. Начало метода, параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['title'], $post['workers_ids']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['title'])) {
                throw new Exception(__FUNCTION__ . '. Не передано название комнаты');
            }

            if (empty($post['workers_ids'])) {
                throw new Exception(__FUNCTION__ . '. Не переданы идентификаторы участников');
            }

            $title = $post['title'];
            $worker_ids = $post['workers_ids'];

            $current_date = Assistant::GetDateNow();

            $chat_database = new ChatDatabaseModel();
            $chat_cache = new ChatCacheModel();

            /**=================================================================
             * Создание комнаты в БД
             * ===============================================================*/
            try {
                $chat_id = $chat_database->newRoom($title, 2 /*групповой*/, $current_date);
            } catch (Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка создания индивидуального чата';
                throw $exception;
            }
            $warnings[] = __FUNCTION__ . '. Комната добавлена в БД';

            /**=================================================================
             * Создание комнаты в кэше
             * ===============================================================*/
//            try {
//                $chat_cache->newRoom($title, 2 /*групповой*/, $current_date, $chat_id);
//            } catch (\Throwable $exception) {
//                $errors[] = __FUNCTION__ . '. Ошибка создания индивидуального чата';
//                throw $exception;
//            }
//            $warnings[] = __FUNCTION__ . '. Комната добавлена в кэш';

            /**=================================================================
             * Создание участников
             * ===============================================================*/
            try {
                // в группе получателей не должен быть администратор
                $member_id = $chat_database->newMember($chat_id, $session['worker_id'], $current_date, 1, 1 /*Администратор*/);
                unset($worker_ids[$session['worker_id']]);
                foreach ($worker_ids as $worker) {
                    $member_id = $chat_database->newMember($chat_id, $worker['worker_id'], $current_date, 1, 2 /*Участник*/);
                    //$chat_cache->newMember($member_id, $chat_id, $worker_id, $current_date, 1, 2 /*Участник*/);
                }
            } catch (Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка добавления участников чата';
                throw $exception;
            }
            $warnings[] = __FUNCTION__ . '. Участники добавлены в БД';

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => '', 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }


    /**
     * actionGetMessagesByRoom - Получение сообщений по идентификатору комнаты чата
     * Необходимые POST поля:
     *   chat_room_id - идентификатор комнаты чата
     *   worker_id - идентификатор работника-пользователя по которому будут
     *               искаться статусы сообщений.
     *
     * Если не указывать параметр даты, то берётся текущая дата и время
     *
     * @param null $post_json строка с параметрами метода в json формате
     *
     * @return array
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionGetMessagesByRoom&data={"chat_room_id":124}&subscribe=
     *
     */
    public static function actionGetMessagesByRoom($post_json = null)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $messages = array();


        try {
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($post_json, true);
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['chat_room_id']/*, $post['worker_id']*/);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['chat_room_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор чата');
            }

            /*if (empty($post['worker_id'])) {
                throw new \Exception(__FUNCTION__ . '. Не передан идентификатор работника - пользователя чата');
            }*/

            $chat_room_id = $post['chat_room_id'];
            //$worker_id = $post['worker_id'];

            // TODO: нужна ли проверка?
            // Если убрать проверку, то при отсутствии чата возвращает пустой массив
            // в поле messages результирующего массива
            if (!ChatRoom::find()->where(['id' => $chat_room_id])->exists()) {
                throw new Exception(__FUNCTION__ . ". Нет чата с таким идентификатором $chat_room_id");
            }

            $chat_database = new ChatDatabaseModel();
            $chat_cache = new ChatCacheModel();

            /**=================================================================
             * Получение сообщений из кэша
             * ===============================================================*/
            /*try {
                $messages = $chat_cache->getMessagesByRoom($chat_room_id);
            } catch (\Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка получения сообщений из кэша';
                throw $exception;
            }*/

            /**=================================================================
             * Получение сообщений из БД
             * ===============================================================*/
            //if ($messages === false) {
            try {
                //$messages = $chat_database->getMessagesByRoom($chat_room_id);
                $messages = $chat_database->getMessagesWithStatusesByRoomWorker($chat_room_id/*, $worker_id*/);
                $warnings[] = __FUNCTION__ . '. Сообщения получены из БД';
//                $warnings[] = $messages;
            } catch (Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка получения сообщений из БД';
                throw $exception;
            }
            //}

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $messages, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = array('Items' => $messages, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }


    /**
     * actionGetMessagesArchiveByRoom - Получение архивных сообщений по идентификатору комнаты чата
     * Необходимые POST поля:
     *   chat_room_id - идентификатор комнаты чата
     *   message_id - дата, до которой выбираются последние сообщения
     *
     * Если не указывать параметр даты, то берётся текущая дата и время
     *
     * @param null $post_json - строка с параметрами метода в json формате
     *
     * @return array
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionGetMessagesArchiveByRoom&data={"chat_room_id":124,"message_id"="32"}&subscribe=
     *
     */
    public static function actionGetMessagesArchiveByRoom($post_json = null)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $messages = array();

        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['chat_room_id'], $post['date_time']);
            if (!$post_valid) {
                throw new Exception('. Не все входные параметры инициализированы');
            }

            if (empty($post['chat_room_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор чата');
            }

            if (empty($post['message_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор последнего сообщения');
            }

            $chat_room_id = $post['chat_room_id'];
            $message_id = $post['message_id'];

            // TODO: нужна ли проверка?
            // Если убрать проверку, то при отсутствии чата возвращает пустой массив
            // в поле messages результирующего массива
            if (!ChatRoom::find()->where(['id' => $chat_room_id])->exists()) {
                throw new Exception(__FUNCTION__ . ". Нет чата с таким идентификатором $chat_room_id");
            }

            $chat_database = new ChatDatabaseModel();

            /**=================================================================
             * Получение сообщений из БД
             * ===============================================================*/
            try {
                $messages = $chat_database->getMessagesByRoom($chat_room_id, $message_id);
                $warnings[] = $messages;
            } catch (Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка получения архивных сообщений из БД';
                throw $exception;
            }
            $warnings[] = __FUNCTION__ . '. Сообщения получены';

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $messages, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }


    /**
     * actionGetRoomsByWorker - Получение чатов, в которых участвует работник, по его идентификатору
     * Необходимые POST поля:
     *   worker_id - идентификатор работника
     *
     * @param null $post_json - строка с параметрами метода в json формате
     *
     * @return array(
     *   'Items' => array (
     *     array(
     *       'id',                  // идентификатор комнаты
     *       'title',               // название комнаты
     *       'creation_date',       // дата создания комнаты
     *       'chat_type_id',        // тип комнаты
     *       'last_message_text',   // текст последнего сообщения
     *       'unread',              // количество непрочитанных сообщений
     *     ),
     *     ...
     *   ),
     *   'status',
     *   'warnings',
     *   'errors'
     * )
     * Items - массив комнат чата
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionGetRoomsByWorker&data={"worker_id":123}&subscribe=
     *
     */
    public static function actionGetRoomsByWorker($post_json = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $rooms = array();

        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['worker_id']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['worker_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор воркера');
            }

            $worker_id = $post['worker_id'];

            $chat_database = new ChatDatabaseModel();
            $chat_cache = new ChatCacheModel();

            /**=================================================================
             * Получение чатов из кэша
             * ===============================================================*/
            /*try {
                $warnings[] = __FUNCTION__ . '. Ищу чаты в кэше';
                $rooms = $chat_cache->getRoomsByWorker($worker_id);
            } catch (\Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка получения чатов по идентификатору воркера из кэша';
                throw $exception;
            }*/

            /**=================================================================
             * Получение чатов из БД, если в кэше не было
             * ===============================================================*/
            //if ($rooms === false) {
            try {
                $warnings[] = __FUNCTION__ . '. Ищу чаты в БД';
                $rooms = $chat_database->getRoomsByWorker($worker_id);
            } catch (Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка получения чатов по идентификатору воркера из БД';
                throw $exception;
            }
            //}

            /**=================================================================
             * Пост-обработка полученных комнат чата:
             * Изменение названия индивидуальных чатов;
             * добавление последнего сообщения;
             * сортировка по дате последнего сообщения
             * ===============================================================*/
            foreach ($rooms as &$room) {
                // Изменение названия индивидуальных чатов
                if ($room['chat_type_id'] == 1/*Индивидуальный*/) {
                    $response = self::actionGetReceiversInChat(json_encode(array(
                        'sender_worker_id' => $worker_id,
                        'room_id' => $room['id']
                    )));
                    if ($response['status'] == 1) {
                        // Костыльно, но Паше нужны и полные имена и только фамилии с инициалами
                        if (isset($response['Items'][0])) {
                            $warnings['$response'] = $response;
//                            $name_parts = explode(' ', $response['Items'][0]['full_name']);
                            $room_title = $response['Items'][0]['last_name'];
                            if ($response['Items'][0]['first_name']) {
                                $room_title .= ' ' . mb_substr($response['Items'][0]['first_name'], 0, 1) . '.';
                            }
                            if ($response['Items'][0]['patronymic']) {
                                $room_title .= mb_substr($response['Items'][0]['patronymic'], 0, 1) . '.';
                            }
                            $room['title'] = $room_title;
                            $room['receiver_worker_id'] = $response['Items'][0]['worker_id'];
                        }
                    }
                }

                // добавление последнего сообщения
                $last_message = $chat_database->getLastMessageByRoomId($room['id']);
                if ($last_message === false) {
                    $room['last_message']['text'] = '';
                    $room['last_message']['date_time'] = '';
                } else {
                    $room['last_message']['text'] = $last_message['text'];
                    $room['last_message']['date_time'] = $last_message['date_time'];
                }
            }

            // сортировка по дате последнего сообщения
            usort($rooms, static function ($a, $b) {
                if ($a['last_message']['date_time'] == '' || $b['last_message']['date_time'] == '') {
                    return 0;
                }

                if (strtotime($a['last_message']['date_time']) < strtotime($b['last_message']['date_time'])) {
                    return 1;
                }

                return -1;
            });

            $rooms = ArrayHelper::index($rooms, 'id');

            /**=================================================================
             * Получение количества непрочитанных сообщений по каждому чату
             * ===============================================================*/
            try {
                $unread_messages = $chat_database->getUnreadMessageCountByWorker(
                    $worker_id, ArrayHelper::getColumn($rooms, 'id')
                );
                foreach ($rooms as &$room) {
                    $room['unread'] = array_key_exists($room['id'], $unread_messages) ?
                        $unread_messages[$room['id']] : 0;
                }
            } catch (Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка получения количества непрочитанных сообщений';
                throw $exception;
            }

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $rooms, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = array('Items' => $rooms, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }


    /**
     * actionSetMessageRecieverLastStatus - Обновление значения последнего статуса сообщения у его получателя.
     * Необходимые POST поля:
     *   chat_message_id - идентификатор сообщения чата
     *   worker_id - идентификатор работника-получателя
     *   status_id - идентификатор нового статуса, который сохраняем
     *
     * Возможные статусы:
     *   28 - Сообщение доставлено
     *   29 - Сообщение отправлено
     *   30 - Сообщение прочитано
     * TODO: возможно стоит добавить проверку прямо внутрь метода на эти статусы
     *
     * @param null $post_json - строка с параметрами метода в json формате
     *
     * @return array
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionSetMessageRecieverLastStatus&data={"chat_message_id":13,"worker_id":123,"status_id":28}&subscribe=
     *
     */
    public static function actionSetMessageRecieverLastStatus($post_json = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();

        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['chat_message_id'], $post['worker_id'], $post['status_id']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['chat_message_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор сообщения чата');
            }

            if (empty($post['worker_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор работника-получателя');
            }

            if (empty($post['status_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор нового статуса, который сохраняем');
            }

            $chat_message_id = $post['chat_message_id'];
            $worker_id = $post['worker_id'];
            $status_id = $post['status_id'];

            $chat_database = new ChatDatabaseModel();
            $chat_cache = new ChatCacheModel();

            /**=================================================================
             * Смена последнего статуса в таблице chat_message_reciever
             * ===============================================================*/
            try {
                $chat_message_reciever_id = $chat_database->setMessageRecieverLastStatus($chat_message_id, $worker_id, $status_id);
            } catch (Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка обновления статуса в таблице chat_message_reciever';
                throw $exception;
            }
            $warnings[] = __FUNCTION__ . '. ID последнего статуса: ' . $chat_message_reciever_id;

            /**=================================================================
             * Смена последнего статуса в кэше
             * ===============================================================*/
            /*try {
                $chat_cache->setMessageRecieverLastStatus($chat_message_id, $worker_id, $status_id);
            } catch (\Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка обновления статуса в кэше';
                throw $exception;
            }*/

            /**=================================================================
             * Добавление статуса в историю получения сообщений
             * ===============================================================*/
            $current_time = Assistant::GetDateNow();
            try {
                $chat_database->newMessageStatus($chat_message_reciever_id, $status_id, $current_time);
            } catch (Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка добавления статуса в историю получения сообщений';
            }

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => '', 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }


    /**
     * actionAddMemberToChat - Добавление работника к чату
     * Необходимые POST поля:
     *      worker_id       - идентификатор работника
     *      chat_room_id    - идентификатор комнаты чата
     *      chat_role_id    - ключ роли чата работника
     * @param null $post_json
     * @return array
     *
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionAddMemberToChat&data={"worker_id":123,"chat_room_id":28}&subscribe=
     */
    public static function actionAddMemberToChat($post_json = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $chat_member_id = -1;

        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['worker_id'], $post['chat_room_id'], $post['chat_role_id']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['worker_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор работника');
            }

            if (empty($post['chat_room_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор чата');
            }

            if (empty($post['chat_role_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор роли члена группового чата');
            }

            $worker_id = $post['worker_id'];
            $chat_room_id = $post['chat_room_id'];
            $chat_role_id = $post['chat_role_id'];

            /**=================================================================
             * Добавление воркера в чат в БД
             * ===============================================================*/
            $curr_date = Assistant::GetDateNow();
            $chat_database = new ChatDatabaseModel();
            try {
                $chat_member_id = $chat_database->newMember($chat_room_id, $worker_id, $curr_date, 1, $chat_role_id);
            } catch (Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка добавления участника в чат в БД';
                throw $exception;
            }
            $warnings[] = __FUNCTION__ . '. ID нового участника в БД: ' . $chat_member_id;

            /**=================================================================
             * Смена типа чата на групповой, если нужно
             * ===============================================================*/
            // TODO: вынести в ChatDatabaseModel
            $chat = ChatRoom::findOne($chat_room_id);
            if ($chat) {
                if ($chat->chat_type_id == 1/*Индивидуальный*/) {
                    // Смена типа
                    $chat->chat_type_id = 2; // Групповой

                    // Смена имени
                    $members = $chat_database->getChatMembers($chat_room_id);
                    $last_names = ArrayHelper::getColumn($members, 'sender_last_name');
                    $chat_title = implode(', ', $last_names);
                    $chat->title = $chat_title;
                    if (!$chat->save()) {
                        throw new Exception(__FUNCTION__ . '. Ошибка изменения чата в БД при добавлении нового участника');
                    }
                    $warnings[] = __FUNCTION__ . '. Тип чата c ID ' . $chat->id . ' изменён на групповой';
                }
            }

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method_parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $chat_member_id, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }

    /**
     * actionAddMembersToChat - Добавление работников к чату
     * Необходимые POST поля:
     *   workers            - список идентификаторов работника
     *      []
     *          worker_id
     *          chat_role_id
     *   chat_room_id                       -   идентификатор комнаты чата
     *
     * @param null $post_json
     * @return array
     *
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionAddMembersToChat&data={"workers":{worker_id}],"chat_room_id":28}&subscribe=
     */
    public static function actionAddMembersToChat($post_json = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $chat_member_id = -1;

        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['workers'], $post['chat_room_id']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['workers'])) {
                throw new Exception(__FUNCTION__ . '. Не передан список добовляемых работников');
            }

            if (empty($post['chat_room_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор чата');
            }

            $workers = $post['workers'];
            $chat_room_id = $post['chat_room_id'];

            /**=================================================================
             * Добавление воркера в чат в БД
             * ===============================================================*/
            $curr_date = Assistant::GetDateNow();
            $chat_database = new ChatDatabaseModel();
            try {
                $workers_to_insert = array();
                foreach ($workers as $worker) {
                    $workers_to_insert[] = array(
                        'chat_room_id' => $chat_room_id,
                        'worker_id' => $worker['worker_id'],
                        'creation_date' => $curr_date,
                        'status_id' => 1,
                        'chat_role_id' => $worker['chat_role_id'],
                    );
                }

                $result_add_chat_members = Yii::$app->db->createCommand()
                    ->batchInsert('chat_member', ['chat_room_id', 'worker_id', 'creation_date', 'status_id', 'chat_role_id'], $workers_to_insert)//массовая вставка в БД
                    ->execute();
                if ($result_add_chat_members != 0) {
                    $warnings[] = __FUNCTION__ . '. Связка маршрута и выработок успешно сохранена ';
                } else {
                    throw new Exception(__FUNCTION__ . '. Ошибка при добавлении связки маршрута и выработок');
                }

//                $chat_member_ids = ChatMember::find()->select('id)->where(['chat_member_id' => $chat_room_id])->column();
            } catch (Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка добавления участника в чат в БД';
                throw $exception;
            }
            $warnings[] = __FUNCTION__ . '. ID нового участника в БД: ' . $chat_member_id;

            /**=================================================================
             * Смена типа чата на групповой, если нужно
             * ===============================================================*/
            // TODO: вынести в ChatDatabaseModel
            $chat = ChatRoom::findOne($chat_room_id);
            if ($chat) {
                if ($chat->chat_type_id == 1/*Индивидуальный*/) {
                    // Смена типа
                    $chat->chat_type_id = 2; // Групповой

                    // Смена имени
                    $members = $chat_database->getChatMembers($chat_room_id);
                    $last_names = ArrayHelper::getColumn($members, 'sender_last_name');
                    $chat_title = implode(', ', $last_names);
                    $chat->title = $chat_title;
                    if (!$chat->save()) {
                        throw new Exception(__FUNCTION__ . '. Ошибка изменения чата в БД при добавлении нового участника');
                    }
                    $warnings[] = __FUNCTION__ . '. Тип чата c ID ' . $chat->id . ' изменён на групповой';
                }
            }

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method_parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $chat_member_id, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }


    /**
     * actionDeleteMessage - Удаление сообщения
     * Необходимые POST поля:
     *   message_id - идентификатор сообщения
     *
     * @param null $post_json - строка с параметрами метода в json формате
     * @return array
     *
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionDeleteMessage&data={"message_id":123}&subscribe=
     */
    public static function actionDeleteMessage($post_json = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();

        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['message_id']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['message_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор сообщения чата');
            }

            $post_valid = isset($post['chat_room_id']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['chat_room_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор комнаты чата');
            }

            $message_id = $post['message_id'];
            $chat_room_id = $post['chat_room_id'];

            $chat_database = new ChatDatabaseModel();
//            $chat_cache = new ChatCacheModel();

            /**=================================================================
             * Удаление сообщения из БД
             * ===============================================================*/
            try {
                $response = $chat_database->deleteMessage($message_id);
                $warnings['after_delete_message'] = $response;
                if (!$response) {
                    throw new Exception(__FUNCTION__ . '. Ошибка удаления сообщения из БД');
                }
                /**=================================================================
                 * Отправка сообщения на вебсокет
                 * ===============================================================*/

                $ws_msg = json_encode(array(
                    'clientType' => 'server',
                    'actionType' => 'publish',
                    'clientId' => 'server',
                    'subPubList' => [$chat_room_id],
                    'messageToSend' => json_encode(array(
                        'type' => 'chat_message_delete',
                        'message' => json_encode(array(
                            'room_id' => $chat_room_id,                                                                 // ключ комнаты
                            'id' => $message_id,                                                                    // ключ сообщения
                        ))
                    ))
                ));
                WebsocketController::actionSendMsg('ws://' . AMICUM_CONNECT_STRING_WEBSOCKET . '/ws', $ws_msg);

            } catch (Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка удаления сообщения из БД';
                throw $exception;
            }
            $warnings[] = __FUNCTION__ . '. Сообщение удалено из БД';

            /**=================================================================
             * Удаление сообщения из кэша
             * ===============================================================*/
            /*try {
                $chat_cache->deleteMessage($message_id);
            } catch (\Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка удаления сообщения из кэша';
                throw $exception;
            }
            $warnings[] = __FUNCTION__ . '. Сообщение удалено из кэша';*/

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => '', 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }


    /**
     * actionClearChatMessages - Удаление всех сообщений из чата
     * Необходимые POST поля:
     *   chat_room_id - идентификатор сообщения
     *
     * @param null $post_json - строка с параметрами метода в json формате
     * @return array
     *
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionClearChatMessages&data={"chat_room_id":123}&subscribe=
     */
    public static function actionClearChatMessages($post_json = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();

        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['chat_room_id']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['chat_room_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор сообщения чата');
            }

            $chat_room_id = $post['chat_room_id'];

            $chat_database = new ChatDatabaseModel();
            $chat_cache = new ChatCacheModel();

            /**=================================================================
             * Нахождение всех сообщений чата
             * ===============================================================*/
            $chat_messages_ids = array();
            try {
                $chat_messages = $chat_database->getMessagesByRoom($chat_room_id);
                if (!empty($chat_messages))
                    $chat_messages_ids = ArrayHelper::getColumn($chat_messages, 'id');
            } catch (Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка получения всех сообщений чата';
                throw $exception;
            }
            $warnings[] = __FUNCTION__ . '. Получены сообщения чата';

            /**=================================================================
             * Удаление всех сообщений чата из БД
             * ===============================================================*/
            try {
                $deleted_message_count = $chat_database->deleteMessage($chat_messages_ids);
            } catch (Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка удаления сообщений из БД';
                throw $exception;
            }
            $warnings[] = __FUNCTION__ . '. Сообщения удалены из БД';

            /**=================================================================
             * Удаление всех сообщений чата в кэше
             * ===============================================================*/
            /*try {
                $chat_cache->clearChatRoomMessages($chat_room_id);
            } catch (\Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка удаления сообщений в кэше';
                throw $exception;
            }
            $warnings[] = __FUNCTION__ . '. Сообщения удалены из кэша';*/

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => '', 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }


    /**
     * actionDeleteChatRoom - Удаление чата
     * Необходимые POST поля:
     *   chat_room_id - идентификатор комнаты
     *
     * @param null $post_json - строка с параметрами метода в json формате
     * @return array
     *
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionDeleteChatRoom&data={"chat_room_id":123}&subscribe=
     */
    public static function actionDeleteChatRoom($post_json = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();

        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['chat_room_id']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['chat_room_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор сообщения чата');
            }

            $chat_room_id = $post['chat_room_id'];
            $chat_database = new ChatDatabaseModel();
            $chat_cache = new ChatCacheModel();

            /**=================================================================
             * Удаление всех сообщений чата из БД
             * ===============================================================*/
            try {
                $deleted_message_count = $chat_database->deleteChatRoom($chat_room_id);
            } catch (Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка удаления сообщений из БД';
                throw $exception;
            }
            $warnings[] = __FUNCTION__ . '. Сообщения удалены из чата в БД';

            /**=================================================================
             * Удаление всех сообщений чата из кэша
             * ===============================================================*/
            /*try {
                $chat_cache->deleteChatRoom($chat_room_id);
            } catch (\Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка удаления сообщений из кэша';
                throw $exception;
            }
            $warnings[] = __FUNCTION__ . '. Сообщения удалены из чата в кэше';*/

        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $deleted_message_count, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }

    // TODO: Раз уж метод называется "прикрепить картинку", то добавить в него
    //  проверку на типы сохраняемых файлов. Иначе переименовать метод
    /**
     * actionAttachPicture - Сохранение вложенного изображения
     * Необходимые POST поля:
     *   file - загруженное изображение
     *
     * @warning Вызывается без read-manager'а
     *
     * @return array(
     *   Items - путь к файлу на сервере, если функция выполнилась успешно, false, если произошла ошибка.
     *   status - статус выполнения функции (1/0)
     *   warnings - логи выполнения
     *   errors - сообщения об ошибках
     * )
     */
    public static function actionAttachPicture()
    {
        $status = 1;
        $warnings = array();
        $errors = array();

        $result = false;

        $post = Assistant::GetServerMethod();
        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($_FILES, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            $post_valid = isset($_FILES['file']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception(__FUNCTION__ . '. Ошибка загрузки файла. Код ошибки: ' . $_FILES['file']['error']);
            }

            // Получаем информацию о файле
            $file = $_FILES['file'];
            $file_name_parts = explode('.', $file['name']);
            $file_extension = strtolower(end($file_name_parts));

            // Генерируем уникальное имя для сохранения
            $file_new_name = md5(time() . $file['name']) . '.' . $file_extension;

            // Путь, куда будет сохранён файл
            $upload_dir = 'chat/attachments/images/';
            $upload_file_dest = $upload_dir . $file_new_name;

            /**=================================================================
             * Сохранение файла на сервере
             * ===============================================================*/
            if (move_uploaded_file($file['tmp_name'], $upload_file_dest)) {
                $result = $upload_file_dest;
                $warnings[] = __FUNCTION__ . '. Файл сохранён на сервере';
            } else {
                $err_msg = __FUNCTION__ . '. Ошибка сохранения файла на сервере.
                Убедитесь, что директория ' . $upload_dir . ' существует и доступна для записи серверу';
                $errors[] = $err_msg;
                throw new Exception($err_msg);
            }

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $result, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }


    /**
     * actionGetContacts - Получение контактов чата
     *
     * @return array(
     *   Items - массив контактов. Если они не найдены, то пустой массив
     *   [
     *
     *   ]
     *   status - статус выполнения функции (1/0)
     *   warnings - логи выполнения
     *   errors - сообщения об ошибках
     * )
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionGetContacts&data=&subscribe=
     *
     */
    public static function actionGetContacts()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $contacts = array();

        try {
            $warnings[] = __FUNCTION__ . '. Начало метода.';
            $contacts = (new Query())
                ->select([
                    'worker.id as worker_id',
                    'worker.tabel_number as tabel_number',
                    'employee.last_name as last_name',
                    'employee.first_name as first_name',
                    'employee.patronymic as patronymic',
                    'position.title as position_title'
                ])
                ->from('(select user1.worker_id from user user1 group by user1.worker_id) user')
                ->innerJoin('worker', 'worker.id = user.worker_id')
                ->innerJoin('employee', 'employee.id = worker.employee_id')
                ->innerJoin('position', 'position.id = worker.position_id')
//                ->groupBy('worker_id, tabel_number, last_name, first_name, patronymic, position_title')
                ->all();
            foreach ($contacts as &$contact) {
                $contact['full_name'] = $contact['last_name'] . ' ' . $contact['first_name'] . ' ' . $contact['patronymic'];
                unset($contact['last_name'], $contact['first_name'], $contact['patronymic']);
            }

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $contacts, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }


    /**
     * actionGetReceiversInChat - Получение получателей сообщения для конкретного чата
     * Необходимые POST поля:
     *   sender_worker_id - идентификатор воркера отправителя
     *   room_id - идентификатор комнаты чата
     *
     * @return array(
     *   Items - массив идентификаторов воркеров-получателей сообщения
     *   status - статус выполнения функции (1/0)
     *   warnings - логи выполнения
     *   errors - сообщения об ошибках
     * )
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionGetReceiversInChat&data={"sender_worker_id":13,"room_id":1}&subscribe=
     *
     */
    public static function actionGetReceiversInChat($post_json = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $receivers = array();

        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['sender_worker_id'], $post['room_id']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['sender_worker_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор воркера отправителя');
            }

            if (empty($post['room_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор комнаты чата');
            }

            $sender_worker_id = $post['sender_worker_id'];
            $room_id = $post['room_id'];

            $chat_database = new ChatDatabaseModel();

            /**=================================================================
             * Выборка участников чата из БД
             * ===============================================================*/
            try {
                $receivers = $chat_database->getChatMembers($room_id);
            } catch (Throwable $exception) {
                $errors[] = __FUNCTION__ . '. Ошибка получения участников чата из БД';
                throw $exception;
            }
            $warnings[] = __FUNCTION__ . '. Получил список участников чата из БД';

            /**=================================================================
             * Удаление отправителя из списка участников для получения списка
             * получателей
             * ===============================================================*/
            foreach ($receivers as $key => $receiver) {
                if ($receiver['worker_id'] == $sender_worker_id) {
                    unset($receivers[$key]);
                } else {
                    // перепаковка для Паши
                    $full_name = $receiver['last_name'] . ' ' . $receiver['first_name'] . ' ' . $receiver['patronymic'];
                    $receivers[$key] = array(
                        'worker_id' => $receiver['worker_id'],
                        'worker_status_id' => $receiver['chat_member_status_id'],
                        'full_name' => $full_name,
                        'last_name' => $receiver['last_name'],
                        'first_name' => $receiver['first_name'],
                        'patronymic' => $receiver['patronymic']
                    );
                }
            }
            $receivers = array_values($receivers);

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $receivers, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }


    /**
     * actionGetContactInfo - Получение информации о выбранном контакте
     * Необходимые POST поля:
     *   worker_id - идентификатор воркера
     *
     * @return array(
     *   Items - массив с информацией о контакте
     *   Пример:
     *   {
     *     'fio' : "Истрати Николай Павлович",
     *     'tabel_number' : "2081323",
     *     'company_title' : "Участок по ремонту электрооборудования",
     *     'department_title' : "Слесарь-ремонтник 5 разряда",
     *     'position_title' : "2081323",
     *   }
     *   status - статус выполнения функции (1/0)
     *   warnings - логи выполнения
     *   errors - сообщения об ошибках
     * )
     *
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionGetContactInfo&data={"worker_id":13}&subscribe=
     */
    public static function actionGetContactInfo($post_json = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $contact_info = array();

        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['worker_id']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['worker_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор воркера отправителя');
            }

            $worker_id = $post['worker_id'];

            /**=================================================================
             * Получение информации о контакте
             * ===============================================================*/
            $contact_info = (new Query())
                ->select([
                    'employee.last_name',
                    'employee.first_name',
                    'employee.patronymic',
                    'worker.tabel_number',
                    'worker.id as worker_id',
                    'company.title as company_title',
                    'department.title as department_title',
                    'position.title as position_title'
                ])
                ->from('worker')
                ->innerJoin('employee', 'employee.id = worker.employee_id')
                ->innerJoin('company_department', 'company_department.id = worker.company_department_id')
                ->innerJoin('company', 'company.id = company_department.company_id')
                ->innerJoin('department', 'department.id = company_department.department_id')
                ->innerJoin('position', 'position.id = worker.position_id')
                ->where([
                    'worker.id' => $worker_id
                ])
                ->limit(1)
                ->one();
            if ($contact_info === false)
                throw new Exception(__FUNCTION__ . '. Не найдена информация по worker_id ' . $worker_id);

            /**=================================================================
             * Пост-обработка информации о контакте:
             * склейка ФИО
             * ===============================================================*/
            $contact_info['fio'] = $contact_info['last_name'] . ' ' .
                $contact_info['first_name'] . ' ' .
                $contact_info['patronymic'];
            unset($contact_info['last_name'], $contact_info['first_name'], $contact_info['patronymic']);

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $contact_info, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = array('Items' => $contact_info, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }


    /**
     * actionInitCache - Инициализация кэша чата.
     */
    public function actionInitCache()
    {
        // Выборка данных
        $rooms = (new Query())
            ->select('*')
            ->from('chat_room')
            ->all();

        $members = (new Query())
            ->select('*')
            ->from('chat_member')
            ->all();

        $db = new ChatDatabaseModel();
        $cache = new ChatCacheModel();

        // Очистка кэша
        $cache_key_pattern = 'Ch*';
        $cache_keys = $cache->cache_engine->cache->scan(0, 'MATCH', $cache_key_pattern, 'COUNT', '10000000')[1];
        if ($cache_keys) {
            foreach ($cache_keys as $key) {
                $cache->cache_engine->cache->del($key);
            }
        }

        // Заполнение кэша чатов
        foreach ($rooms as $room) {
            $cache->newRoom($room['title'], $room['chat_type_id'], $room['creation_date'], $room['id']);
            // Заполнение кэша сообщений для комнаты
            $messages = $db->getMessagesByRoom($room['id']);
            foreach ($messages as $message) {
                $cache->newMessage($message['primary_message'], $message['sender_worker_id'], $room['id'],
                    $message['date_time'], $message['id'], $message['chat_attachment_type_id'], $message['attachment']);
            }
        }
        $log[] = 'Кэш чатов заполнен';

        // Заполнение кэша участников
        foreach ($members as $member) {
            $cache->newMember($member['id'], $member['chat_room_id'], $member['worker_id'], $member['creation_date'],
                $member['status_id'], $member['chat_role_id']);
        }
        $log[] = 'Кэш участников чатов заполнен';
    }


    /**
     * actionGetGroupInfo - Получение информации о выбранной группе
     * Необходимые POST поля:
     *   chat_room_id - идентификатор комнаты
     *
     * @return array(
     *   Items - массив с информацией о контакте
     *   Пример:
     *   {
     *      chat_room_id        "110"                                               // ключ комнаты
     *      chat_room_title        "Чат 2050735 и 1011488"                             // название комнаты
     *      chat_type_id        "1"                                                 // тип чата
     *          members:                                                                // список участников комнаты
     *              {worker_chat_role_id}
     *                  worker_chat_role_id:        "2"                                 // роль работника в чате (1 администратор, 2 участник, 3 наблюдатель)
     *                  workers:
     *                      {worker_id}
     *                          worker_id            "1011488"                           // ключ работника
     *                          worker_full_name    "Пузанов Анатолий Анатольевич"      // ФИО
     *                          tabel_number        "1011488"                           // табельный номер
     *                          worker_status_id    "1"                                 // статус в чате работника (активный, бан)
     *   }
     *   status - статус выполнения функции (1/0)
     *   warnings - логи выполнения
     *   errors - сообщения об ошибках
     * )
     *
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionGetGroupInfo&data={"room_id":110}&subscribe=
     */
    public static function actionGetGroupInfo($post_json = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $groupInfo = array();

        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['chat_room_id']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['chat_room_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор комнаты');
            }

            $chat_room_id = $post['chat_room_id'];

            /**=================================================================
             * Получение информации о контакте
             * ===============================================================*/
            $groupInfo = (new Query())
                ->select([
                    'employee.last_name as last_name',
                    'employee.first_name as first_name',
                    'employee.patronymic as patronymic',
                    'worker.tabel_number as worker_tabel_number',
                    'chat_room.id as chat_room_id',
                    'chat_room.title as chat_room_title',
                    'chat_room.creation_date as chat_room_date_creation',
                    'chat_room.chat_type_id as chat_type_id',
                    'chat_member.worker_id as worker_id',
                    'chat_member.status_id as worker_status_id',
                    'chat_member.chat_role_id as worker_chat_role_id'
                ])
                ->from('chat_room')
                ->leftJoin('chat_member', 'chat_room.id = chat_member.chat_room_id')
                ->leftJoin('worker', 'worker.id = chat_member.worker_id')
                ->leftJoin('employee', 'employee.id = worker.employee_id')
                ->where([
                    'chat_room.id' => $chat_room_id
                ])
                ->all();
            if ($groupInfo === false)
                throw new Exception(__FUNCTION__ . '. Не найдена информация по room_id ' . $chat_room_id);

            foreach ($groupInfo as $chat_room) {
                /**=================================================================
                 * Пост-обработка информации о контакте:
                 * склейка ФИО
                 * ===============================================================*/
                $fio = $chat_room['last_name'] . ' ' .
                    $chat_room['first_name'] . ' ' .
                    $chat_room['patronymic'];

                $info_result['chat_room_id'] = $chat_room['chat_room_id'];
                $info_result['chat_room_title'] = $chat_room['chat_room_title'];
                $info_result['chat_type_id'] = $chat_room['chat_type_id'];
                $info_result['members'][$chat_room['worker_chat_role_id']]['worker_chat_role_id'] = $chat_room['worker_chat_role_id'];
                $info_result['members'][$chat_room['worker_chat_role_id']]['workers'][$chat_room['worker_id']]['worker_id'] = $chat_room['worker_id'];
                $info_result['members'][$chat_room['worker_chat_role_id']]['workers'][$chat_room['worker_id']]['worker_full_name'] = $fio;
                $info_result['members'][$chat_room['worker_chat_role_id']]['workers'][$chat_room['worker_id']]['tabel_number'] = $chat_room['worker_tabel_number'];
                $info_result['members'][$chat_room['worker_chat_role_id']]['workers'][$chat_room['worker_id']]['worker_status_id'] = $chat_room['worker_status_id'];
            }

            if (!isset($info_result)) {
                $result = (object)array();
            } else {
                $result = $info_result;
            }

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $result, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = array('Items' => $groupInfo, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }

    /**
     * actionEditTitleGroupChat - изменить название группы
     * Необходимые POST поля:
     *   room_id        - идентификатор комнаты
     *   room_title     - новое наименование комнаты
     *
     * @return array(
     *   Items - массив с информацией о контакте
     *   status - статус выполнения функции (1/0)
     *   warnings - логи выполнения
     *   errors - сообщения об ошибках
     * )
     *
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionEditTitleGroupChat&data={"room_id":110}&subscribe=
     */
    public static function actionEditTitleGroupChat($post_json = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();

        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['room_id'], $post['room_title']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['room_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор комнаты');
            }

            if (empty($post['room_title'])) {
                throw new Exception(__FUNCTION__ . '. Не передано новое название комнаты');
            }

            $room_id = $post['room_id'];
            $room_title = $post['room_title'];

            $save_room = ChatRoom::findOne(['id' => $room_id]);
            if (!$save_room) {
                throw new Exception(__FUNCTION__ . '. Отсутствует запрашиваема группа чата');
            }

            $save_room->title = $room_title;

            if (!$save_room->save()) {
                $errors[] = $save_room->errors;
                throw new Exception(__FUNCTION__ . '. Ошибка сохранения модели комнаты чата ChatRoom');
            }

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $result, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = array('Items' => $groupInfo, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }


    /**
     * actionAddWorkerInGroupChat - добавить работника в групповой чат
     * Необходимые POST поля:
     *   room_id        - идентификатор комнаты
     *   worker_id      - ключ работника
     *   chat_role_id   - ключ роли работника в чате
     *
     * @return array(
     *   Items - массив с информацией о контакте
     *   status - статус выполнения функции (1/0)
     *   warnings - логи выполнения
     *   errors - сообщения об ошибках
     * )
     *
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionAddWorkerInGroupChat&data={"room_id":110}&subscribe=
     */
    public static function actionAddWorkerInGroupChat($post_json = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();

        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['room_id'], $post['worker_id'], $post['chat_role_id']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['room_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор комнаты');
            }

            if (empty($post['worker_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан ключ работника');
            }

            if (empty($post['chat_role_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан ключ типа роли работника');
            }

            $room_id = $post['room_id'];
            $worker_id = $post['worker_id'];
            $chat_role_id = $post['chat_role_id'];

            $chat_member_exist = ChatMember::findOne(['worker_id' => $worker_id, 'chat_room_id' => $room_id]);
            if (!$chat_member_exist) {
                $save_chat_member = new ChatMember();
                $save_chat_member->chat_room_id = $room_id;
                $save_chat_member->worker_id = $worker_id;
                $save_chat_member->creation_date = Assistant::GetDateNow();
                $save_chat_member->status_id = 1;
                $save_chat_member->chat_role_id = $chat_role_id;

                if (!$save_chat_member->save()) {
                    $errors[] = $save_chat_member->errors;
                    throw new Exception(__FUNCTION__ . '. Ошибка сохранения модели  ChatMember');
                }
            }

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $result, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }

    /**
     * actionEditWorkerInGroupChat - изменить статус работника в групповой чат
     * Необходимые POST поля:
     *   chat_room_id           - идентификатор комнаты
     *   worker_id              - ключ работника
     *   worker_status_id       - ключ статуса работника (активный или нет)
     *
     * @return array(
     *   Items - массив с информацией о контакте
     *   status - статус выполнения функции (1/0)
     *   warnings - логи выполнения
     *   errors - сообщения об ошибках
     * )
     *
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionEditWorkerInGroupChat&data={"room_id":110}&subscribe=
     */
    public static function actionEditWorkerInGroupChat($post_json = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();

        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['chat_room_id'], $post['worker_id'], $post['worker_status_id']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['chat_room_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор комнаты');
            }

            if (empty($post['worker_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан ключ работника');
            }

            if (empty($post['worker_status_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан ключ статуса работника в чате');
            }

            $room_id = $post['chat_room_id'];
            $worker_id = $post['worker_id'];
            $worker_status_id = $post['worker_status_id'];

            $save_chat_member_status = ChatMember::findOne(['worker_id' => $worker_id, 'chat_room_id' => $room_id]);
            if (!$save_chat_member_status) {
                throw new Exception(__FUNCTION__ . '. Работника нет в групповом чате');
            }

            $save_chat_member_status->status_id = $worker_status_id;


            if (!$save_chat_member_status->save()) {
                $errors[] = $save_chat_member_status->errors;
                throw new Exception(__FUNCTION__ . '. Ошибка сохранения модели ChatMember');
            }

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $result, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = array('Items' => $groupInfo, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }

    /**
     * actionDelWorkerFromGroupChat - удалить работника из групповой чат
     * Необходимые POST поля:
     *   room_id        - идентификатор комнаты
     *   worker_id      - ключ работника
     *   chat_role_id   - ключ роли работника в чате
     *
     * @return array(
     *   Items - массив с информацией о контакте
     *   status - статус выполнения функции (1/0)
     *   warnings - логи выполнения
     *   errors - сообщения об ошибках
     * )
     *
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionDelWorkerFromGroupChat&data={"room_id":110}&subscribe=
     */
    public static function actionDelWorkerFromGroupChat($post_json = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();

        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['room_id'], $post['worker_id']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['room_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан идентификатор комнаты');
            }

            if (empty($post['worker_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан ключ работника');
            }

            $room_id = $post['room_id'];
            $worker_id = $post['worker_id'];

            $chat_member = ChatMember::findOne(['worker_id' => $worker_id, 'chat_room_id' => $room_id]);
            if (!$chat_member) {
                throw new Exception(__FUNCTION__ . '. Работника не существует в групповом чате');
            }

            $chat_member->status_id = 19;

            if (!$chat_member->save()) {
                $errors[] = $chat_member->errors;
                throw new Exception(__FUNCTION__ . '. Ошибка сохранения модели  ChatMember');
            }

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $result, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = array('Items' => $groupInfo, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }

    // actionGetLastMemberStatus - получить список статусов членов чата
    // входные параметры:
    //      worker_id - ключ работника - необязательный параметр
    // выходные параметры:
    //      {worker_id}             - ключ работника
    //          worker_id:              - ключ работника
    //          date_time_last:         - дата последнего запроса в системе
    // алгоритм:
    //      1. Получить список последних дат для каждого пользователя из таблицы user_action_log
    //      2. из списка user_action_log получить ключ работника и дату последнего ВЫПОЛНЕНИЯ ЗАПРОСА
    //      3. сформировать выходной массив
    // разработал: Якимов М.Н.
    // дата 18.02.2020
    // пример: http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionGetLastMemberStatus&data=&subscribe=
    public static function actionGetLastMemberStatus($post_json = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $session = Yii::$app->session;
        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ';
            $filter = array();
            if ($post_json != null and $post_json != '') {
                $post = json_decode($post_json, true);
                if (isset($post['worker_id'])) {
                    $worker_id = $post['worker_id'];
                    $filter = array("table_number" => $worker_id);
                }
            }
            $last_date_time = (new Query(''))
                ->select('
                    table_number as worker_id,
                    max(date_time) as date_time_last
                ')
                ->from('amicum2_log.user_action_log')
                ->filterWhere($filter)
                ->groupBy('worker_id')
                ->limit(6000)
                ->indexBy('worker_id')
                ->all();
            $result = $last_date_time;
            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $result, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = array('Items' => $groupInfo, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }

    /**
     * actionEditStatusMessage - изменить статус сообщения на прочитанное
     * Необходимые POST поля:
     *   chat_messages          -   список сообщений на изменение статуса
     *          {chat_message_id}
     *              chat_message_id        - идентификатор сообщения
     *              status_id              - статус сообщения
     *
     * @return array(
     *   Items - массив с информацией о контакте
     *   status - статус выполнения функции (1/0)
     *   warnings - логи выполнения
     *   errors - сообщения об ошибках
     * )
     *
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionEditStatusMessage&data={"room_id":110}&subscribe=
     */
    public static function actionEditStatusMessage($post_json = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $session = Yii::$app->session;
        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['chat_messages']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['chat_messages'])) {
                throw new Exception(__FUNCTION__ . '. Не передан объект сообщения');
            }

            $chat_messages = $post['chat_messages'];
            $worker_id = $session['worker_id'];

            foreach ($chat_messages as $chat_message) {
                $chat_message_reciver = ChatMessageReciever::findOne(['chat_message_id' => $chat_message['chat_message_id'], 'worker_id' => $worker_id]);
                if (!$chat_message_reciver) {
                    throw new Exception(__FUNCTION__ . '. Нет в БД (ChatMessageReciever) такого сообщения');
                }
                $chat_message_reciver->status_id_last = $chat_message['status_id'];
                if (!$chat_message_reciver->save()) {
                    $errors[] = $chat_message_reciver->errors;
                    throw new Exception(__FUNCTION__ . '. ошибка сохранения статуса в модели ChatMessageReciever');
                }

                $chat_reciever_history = new ChatRecieverHistory();
                $chat_reciever_history->status_id = $chat_message['status_id'];
                $chat_reciever_history->chat_message_reciever_id = $chat_message_reciver->id;
                $chat_reciever_history->date_time = Assistant::GetDateNow();
                if (!$chat_reciever_history->save()) {
                    $errors[] = $chat_reciever_history->errors;
                    throw new Exception(__FUNCTION__ . '. ошибка сохранения истории статуса в модели ChatRecieverHistory');
                }
            }


            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $result, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = array('Items' => $groupInfo, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }

    /**
     * actionEditPinnedMessageWorker - изменить закрепление комнаты
     * Необходимые POST поля:
     *              chat_room_id        - идентификатор комнаты
     *              is_pinned              - статус сообщения (закреплено или нет)
     *
     * @return array(
     *   Items - массив с информацией о контакте
     *   status - статус выполнения функции (1/0)
     *   warnings - логи выполнения
     *   errors - сообщения об ошибках
     * )
     *
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionEditPinnedMessageWorker&data={"chat_room_id":110,"is_pinned":0}&subscribe=
     */
    public static function actionEditPinnedMessageWorker($post_json = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $session = Yii::$app->session;
        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = (object)json_decode($post_json, true);

            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post->chat_room_id, $post->is_pinned);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (!property_exists($post, 'chat_room_id')) {
                throw new Exception(__FUNCTION__ . '. Не передан ключ сообщения');
            }

            if (!property_exists($post, 'is_pinned')) {
                throw new Exception(__FUNCTION__ . '. Не передан статус закрепления сообщения');
            }

            $chat_room_id = $post->chat_room_id;
            $is_pinned = $post->is_pinned;
            $worker_id = $session['worker_id'];

            $chat_message_pinned = ChatMessagePinned::findOne(['chat_room_id' => $chat_room_id, 'worker_id' => $worker_id]);
            if (!$chat_message_pinned) {
                $chat_message_pinned = new ChatMessagePinned();
            }
            $chat_message_pinned->chat_room_id = $chat_room_id;
            $chat_message_pinned->worker_id = $worker_id;
            $chat_message_pinned->is_pinned = $is_pinned;
            if (!$chat_message_pinned->save()) {
                $errors[] = $chat_message_pinned->errors;
                throw new Exception(__FUNCTION__ . '. ошибка сохранения статуса закрепления в модели ChatMessagePinned');
            }

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $result, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = array('Items' => $groupInfo, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }

    /**
     * actionAddFavoritesMessageWorker - добавить сообщение в избранные у пользователя
     * Необходимые POST поля:
     *              chat_message_id        - идентификатор сообщения
     *
     * @return array(
     *   Items - массив с информацией о контакте
     *   status - статус выполнения функции (1/0)
     *   warnings - логи выполнения
     *   errors - сообщения об ошибках
     * )
     *
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionAddFavoritesMessageWorker&data={"chat_message_id":110}&subscribe=
     */
    public static function actionAddFavoritesMessageWorker($post_json = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $session = Yii::$app->session;
        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['chat_message_id']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['chat_message_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан ключ сообщения');
            }


            $chat_message_id = $post['chat_message_id'];
            $worker_id = $session['worker_id'];

            $chat_message_favorites = ChatMessageFavorites::findOne(['chat_message_id' => $chat_message_id, 'worker_id' => $worker_id]);
            if (!$chat_message_favorites) {
                $chat_message_favorites = new ChatMessageFavorites();
            }
            $chat_message_favorites->chat_message_id = $chat_message_id;
            $chat_message_favorites->worker_id = $worker_id;
            if (!$chat_message_favorites->save()) {
                $errors[] = $chat_message_favorites->errors;
                throw new Exception(__FUNCTION__ . '. ошибка сохранения статуса закрепления в модели ChatMessageFavorites');
            }

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $result, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = array('Items' => $groupInfo, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }

    /**
     * actionDelFavoritesMessageWorker - удалить сообщение из избранных у пользователя
     * Необходимые POST поля:
     *              chat_message_id        - идентификатор сообщения
     * @return array(
     *   Items - массив с информацией о контакте
     *   status - статус выполнения функции (1/0)
     *   warnings - логи выполнения
     *   errors - сообщения об ошибках
     * )
     *
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionAddFavoritesMessageWorker&data={"chat_message_id":110}&subscribe=
     */
    public static function actionDelFavoritesMessageWorker($post_json = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $session = Yii::$app->session;
        try {
            $warnings[] = __FUNCTION__ . '. Начало метода. Параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }

            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['chat_message_id']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['chat_message_id'])) {
                throw new Exception(__FUNCTION__ . '. Не передан ключ сообщения');
            }


            $chat_message_id = $post['chat_message_id'];
            $worker_id = $session['worker_id'];

            $chat_message_favorites = ChatMessageFavorites::deleteAll(['chat_message_id' => $chat_message_id, 'worker_id' => $worker_id]);
            if (!$chat_message_favorites) {
                throw new Exception(__FUNCTION__ . '. ошибка удаления избранного сообщения - нет в БД');
            }

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors['Method parameters'] = $post_json;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $result, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = array('Items' => $groupInfo, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }

    /**
     * actionGetFavoritesMessageWorker - получить список избранных сообщений
     * Необходимые POST поля:
     * @return array(
     *   Items - массив с информацией о контакте
     *   status - статус выполнения функции (1/0)
     *   warnings - логи выполнения
     *   errors - сообщения об ошибках
     * )
     *
     * @example
     * http://127.0.0.1/read-manager-amicum?controller=Chat&method=actionGetFavoritesMessageWorker&data={}&subscribe=
     */
    public static function actionGetFavoritesMessageWorker($post_json = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $session = Yii::$app->session;
        try {

            $worker_id = $session['worker_id'];
            $warnings[] = $worker_id;
            $chat_message_favorites = (new Query())
                ->select(['chat_message.id as chat_message_id',
                    'chat_message.primary_message as primary_message',
                    'chat_message.sender_worker_id as sender_worker_id',
                    'chat_message.chat_room_id as chat_room_id',
                    'chat_message.chat_attachment_type_id as chat_attachment_type_id',
                    'chat_message.attachment as attachment',
                    'chat_message.date_time as date_time',
                    'employee.last_name as last_name',
                    'employee.first_name as first_name',
                    'employee.patronymic as patronymic',
                    'chat_message_favorites.id as chat_message_favorites_id'
                ])
                ->from('chat_message_favorites')
                ->innerJoin('chat_message', 'chat_message.id = chat_message_favorites.chat_message_id')
                ->innerJoin('worker', 'worker.id = chat_message.sender_worker_id')
                ->innerJoin('employee', 'employee.id = worker.employee_id')
                ->where(['chat_message_favorites.worker_id' => $worker_id])
                ->all();

            if ($chat_message_favorites) {
                $result = $chat_message_favorites;
            } else {
                $result = array();
            }

            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;

            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $result, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = array('Items' => $groupInfo, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }

    /**
     * Метод отправки сообщения горному мастеру, вызывается их Журнала оператора АБ через контекстное меню над событием
     * у работника
     * @param null $post_json
     */
    public static function actionSendMessageToGM($post_json = null)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $method_name = "actionSendMessageToGM";
        try {
            $warnings[] = __FUNCTION__ . '. Начало метода, параметры: ' . print_r($post_json, true);
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            if ($post_json === null || $post_json === '') {
                throw new Exception(__FUNCTION__ . '. Данные с фронта не получены');
            }
            $post = json_decode($post_json, true);
            if ($post === null) {
                throw new Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post_json);
            }

            $post_valid = isset($post['sender_worker_id'], $post['reciever_worker_id']);
            if (!$post_valid) {
                throw new Exception(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['message'])) {
                throw new Exception(__FUNCTION__ . '. Не передан текст сообщения');
            }

            $sender_worker_id = $post['sender_worker_id'];
            $receiver_worker_id = $post['reciever_worker_id'];
            $message = $post['message'];

            $chat = self::actionNewChat($post_json);
            if ($chat['status'] === 1) {
                $warnings[] = $chat['warnings'];
                $chat_room_id = $chat['Items'];
                $warnings['chat_room_id'] = $chat_room_id;

                $post_json_for_new_message = json_encode(array(
                    "chat_room_id" => $chat_room_id,
                    "text" => $message,
                    "sender_worker_id" => $sender_worker_id,
                    "attachment" => "",
                    "attachment_type" => 6,
                    "attachment_title" => ""));

                $new_message = self::actionNewMessage($post_json_for_new_message);

                if ($new_message["status"] === 1) {
                    $status = 1;
                    $warnings[] = $new_message['warnings'];
                } else {
                    $status *= 0;
                    $errors[] = $new_message['errors'];
                    $warnings[] = $new_message['warnings'];
                }
            } else {
                $status *= 0;
                $errors[] = $chat['errors'];
                $warnings[] = $chat['warnings'];
            }


        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        return array('Items' => array(), 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }

    /**
     * AddNewRoom() - Метод добавление новой комнаты чата при создании ее в рамках отчета по наряду
     * входные данные:
     *      title           - название комнаты
     *      chat_type_id    - тип комнаты (1 - индивидуальный, 2 - групповой)
     * выходные данные:
     *      chat_room_id    - ключ комнаты
     *      title           - наименование комнаты
     *      creation_date   - дата создания комнаты
     *      chat_type_id    - ключ типа комнаты
     *      members         - список участников чата
     *          {worker_id}     - ключ работника
     *              worker_id       - ключ работника
     *              full_name       - ФИО работника
     *              short_name      - ФИО работника сокращенное
     *              position_title  - должность работника
     *              tabel_number    - табельный номер работника
     *              chat_role_id    - ключ роли работника
     *      messages        - список сообщений чата (пустой)
     * @example http://127.0.0.1/read-manager-amicum?controller=Chat&method=AddNewRoom&subscribe=&data={"title":"наряд отчет 1","chat_type_id":1}
     */
    public static function AddNewRoom($data_post = NULL): array
    {
        $result = array(
            'chat_room_id' => -1,
            'title' => "",
            'creation_date' => "",
            'chat_type_id' => -1,
            'members' => null,
            'messages' => null,
        );

        $count_record = 0;

        $log = new LogAmicumFront("AddNewRoom");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'title') ||
                !property_exists($post, 'chat_type_id') ||
                $post->title == '' ||
                $post->chat_type_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $session = Yii::$app->session;
            $worker_id = $session['worker_id'];
            $chat_database = new ChatDatabaseModel();
            $chat_type_id = $post->chat_type_id;
            $date_time = Assistant::GetDateTimeNow();
            $title = $post->title . $date_time;

            $chat_room_id = $chat_database->newRoom($title, $chat_type_id, $date_time);

            $result = array(
                'chat_room_id' => $chat_room_id,
                'title' => $title,
                'creation_date' => $date_time,
                'chat_type_id' => $chat_type_id,
                'members' => null,
                'messages' => null,
            );


            $chat_database->newMember($chat_room_id, $worker_id, $date_time, StatusEnumController::ACTUAL, CHAT_ROLE::ADMIN);


            $result['members'][$worker_id] = array(
                'worker_id' => $worker_id,
                'full_name' => $session['userFullName'],
                'short_name' => FrontAssistant::GetShortFullName($session['first_name'], $session['patronymic'], $session['last_name']),
                'position_title' => $session['position_title'],
                'tabel_number' => $session['userStaffNumber'],
                'chat_role_id' => CHAT_ROLE::ADMIN,
            );

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * GetMessagesChatByRoomId() - Метод получения списка сообщений по ключу комнаты
     * входные данные:
     *      chat_room_id    - ключ комнаты
     * выходные данные:
     *      messages        - список сообщений чата
     *          []
     *              message_id                  - ключ сообщения
     *              primary_message             - текст сообщения
     *              sender_worker_id            - ключ отправителя
     *              chat_attachment_type_id     - ключ типа вложения/сообщения
     *              attachment                  - вложение/ссылка на вложение
     *              date_time                   - дата и время отправки сообщения
     * @example http://127.0.0.1/read-manager-amicum?controller=Chat&method=GetMessagesChatByRoomId&subscribe=&data={"chat_room_id":122}
     */
    public static function GetMessagesChatByRoomId($data_post = NULL): array
    {
        $result = array(
            'messages' => [],
        );

        $count_record = 0;

        $log = new LogAmicumFront("GetMessagesChatByRoomId");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'chat_room_id') ||
                $post->chat_room_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $chat_database = new ChatDatabaseModel();
            $chat_room_id = $post->chat_room_id;

            $messages['messages'] = $chat_database->getMessagesByRoom($chat_room_id);

            $workers_filter = [];
            foreach ($messages['messages'] as $message) {
                $workers_filter[] = $message['sender_worker_id'];
            }

            $workers = (new Query())
                ->select(['worker.id as worker_id', 'first_name', 'last_name', 'patronymic'])
                ->from('worker')
                ->innerJoin('employee', 'employee.id=worker.employee_id')
                ->where(['worker.id' => $workers_filter])
                ->indexBy('worker_id')
                ->all();


            foreach ($messages['messages'] as $message) {
                if (isset($workers[$message['sender_worker_id']])) {
                    $message['short_name'] = FrontAssistant::GetShortFullName($workers[$message['sender_worker_id']]['first_name'], $workers[$message['sender_worker_id']]['patronymic'], $workers[$message['sender_worker_id']]['last_name']);
                } else {
                    $message['short_name'] = "";
                }
                $result['messages'][] = $message;
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * SaveMessageChat() - Метод сохранения сообщения Чата
     * входные данные:
     *      message         - объект сообщения чата
     *          chat_room_id                - ключ сообщения
     *          primary_message             - текст сообщения
     *          sender_worker_id            - ключ отправителя
     *          chat_attachment_type_id     - ключ типа вложения/сообщения
     *          attachment                  - вложение ссылка/блоб
     *          attachment_title            - название вложения
     *          date_time                   - дата и время отправки сообщения
     * выходные данные:
     *      message         - сообщение чата
     *          message_id                  - ключ сообщения
     *          primary_message             - текст сообщения
     *          sender_worker_id            - ключ отправителя
     *          chat_attachment_type_id     - ключ типа вложения/сообщения
     *          attachment                  - вложение/ссылка на вложение
     *          date_time                   - дата и время отправки сообщения
     * @example http://127.0.0.1/read-manager-amicum?controller=Chat&method=SaveMessageChat&subscribe=&data={"message":{"chat_room_id":122,"primary_message":"hgjkghjk","sender_worker_id":1,"chat_attachment_type_id":6,"attachment":null,"attachment_title":"fxdchgxdcfh","date_time":"2002-02-02"}}
     */
    public static function SaveMessageChat($data_post = NULL): array
    {
        $result = array(
            'messages' => [],
        );

        $count_record = 0;

        $log = new LogAmicumFront("SaveMessageChat");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'message') ||
                $post->message == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $session = Yii::$app->session;
            $worker_id = $session['worker_id'];
            $date_time = Assistant::GetDateTimeNow();
            $chat_database = new ChatDatabaseModel();
            $message = $post->message;

            /** ДОБАВЛЕНИЕ ЧЛЕНА ЧАТА */
            $chat_database->newMember($message->chat_room_id, $worker_id, $date_time, StatusEnumController::ACTUAL, CHAT_ROLE::ADMIN);


            /** СОХРАНЕНИЕ СООБЩЕНИЯ */
//            array(
//                "chat_room_id" => $chat_room_id,
//                "text" => $message,
//                "sender_worker_id" => $sender_worker_id,
//                "attachment" => "",
//                "attachment_type" => 6,
//                "attachment_title" => "")
            $message->attachment_type = $message->chat_attachment_type_id;
            $message->text = $message->primary_message;

            $response = self::actionNewMessage(json_encode($message));
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения нового сообщения');
            }

            $message->message_id = $response['message_id'];

            $result = (array)$message;

            $result['members'][$worker_id] = array(
                'worker_id' => $worker_id,
                'full_name' => $session['userFullName'],
                'short_name' => FrontAssistant::GetShortFullName($session['first_name'], $session['patronymic'], $session['last_name']),
                'position_title' => $session['position_title'],
                'tabel_number' => $session['userStaffNumber'],
                'chat_role_id' => CHAT_ROLE::ADMIN,
            );

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * DeleteMessageChat() - Метод удаления сообщения Чата
     * входные данные:
     *      message_id      - ключ сообщения чата
     *      chat_room_id    - ключ комнаты чата
     * выходные данные:
     * @example http://127.0.0.1/read-manager-amicum?controller=Chat&method=DeleteMessageChat&subscribe=&data={"message_id":413,"chat_room_id":122}
     */
    public static function DeleteMessageChat($data_post = NULL): array
    {
        $result = array(
            'messages' => [],
        );

        $count_record = 0;

        $log = new LogAmicumFront("DeleteMessageChat");

        try {
            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'message_id') ||
                !property_exists($post, 'chat_room_id') ||
                $post->chat_room_id == '' ||
                $post->message_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $message_id = $post->message_id;
            $chat_room_id = $post->chat_room_id;

            $response = self::actionDeleteMessage(json_encode(array('message_id' => $message_id, 'chat_room_id' => $chat_room_id)));
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка удаления сообщения");
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}
