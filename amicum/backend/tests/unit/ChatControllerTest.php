<?php

use backend\controllers\chat\ChatDatabaseModel;
use backend\controllers\StatusEnumController;
use frontend\controllers\ChatController;
use yii\helpers\ArrayHelper;

// TODO: проверка участников чата в кэше

class ChatControllerTest extends \Codeception\Test\Unit
{
    public function _before()
    {
        Yii::$app->db->createCommand()->insert('department', [
            'id' => 10,
            'title' => 'Testing department'
        ])->execute();
        Yii::$app->db->createCommand()->insert('company', [
            'id' => 10,
            'title' => 'Testing company'
        ])->execute();
        Yii::$app->db->createCommand()->insert('company_department', [
            'id' => 1000,
            'department_id' => 10,
            'company_id' => 10,
            'department_type_id' => 5
        ])->execute();

        // Создание воркера 1
        Yii::$app->db->createCommand()->insert('employee', [
            'id' => 100, 'last_name' => 'last1', 'first_name' => 'first1',
            'patronymic' => 'pat1', 'gender' => 'М', 'birthdate' => '1984-08-08'
        ])->execute();
        Yii::$app->db->createCommand()->insert('worker', [
            'id' => 10, 'employee_id' => 100, 'position_id' => 1,
            'company_department_id' => 1000, 'tabel_number' => 10,
            'date_start' => '2018-08-08', 'date_end' => '2021-08-08'
        ])->execute();

        // Создание воркера 2
        Yii::$app->db->createCommand()->insert('employee', [
            'id' => 200, 'last_name' => 'last2', 'first_name' => 'first2',
            'patronymic' => 'pat2', 'gender' => 'М', 'birthdate' => '1984-09-09'
        ])->execute();
        Yii::$app->db->createCommand()->insert('worker', [
            'id' => 20, 'employee_id' => 200, 'position_id' => 1,
            'company_department_id' => 1000, 'tabel_number' => 20,
            'date_start' => '2018-09-09', 'date_end' => '2021-09-09'
        ])->execute();

        // Создание воркера 3
        Yii::$app->db->createCommand()->insert('employee', [
            'id' => 300, 'last_name' => 'last3', 'first_name' => 'first3',
            'patronymic' => 'pat3', 'gender' => 'М', 'birthdate' => '1985-09-09'
        ])->execute();
        Yii::$app->db->createCommand()->insert('worker', [
            'id' => 30, 'employee_id' => 300, 'position_id' => 1,
            'company_department_id' => 1000, 'tabel_number' => 30,
            'date_start' => '2018-10-09', 'date_end' => '2021-10-09'
        ])->execute();

        // Создание воркера 4
        Yii::$app->db->createCommand()->insert('employee', [
            'id' => 400, 'last_name' => 'last4', 'first_name' => 'first4',
            'patronymic' => 'pat4', 'gender' => 'М', 'birthdate' => '1986-09-09'
        ])->execute();
        Yii::$app->db->createCommand()->insert('worker', [
            'id' => 40, 'employee_id' => 400, 'position_id' => 1,
            'company_department_id' => 1000, 'tabel_number' => 40,
            'date_start' => '2018-10-10', 'date_end' => '2021-10-10'
        ])->execute();
    }

    public function _after()
    {
        $cache = Yii::$app->redis_service;
        $cache_key_pattern = 'Ch*';
        $cache_keys = $cache->scan(0, 'MATCH', $cache_key_pattern, 'COUNT', '10000000')[1];
        if ($cache_keys) {
            foreach($cache_keys as $key) {
                $cache->del($key);
            }
        }
    }

    /**
     * Тестируем: ChatController::actionNewMessage
     * По сценарию: в комнате 2 участника. Один из них отправляет сообщение без
     *   вложения.
     * Ожидаем: Соответствующие записи в таблицах chat_message, chat_message_reciever,
     *   chat_reciever_history и поля в кэше
     */
    public function testActionNewMessage()
    {
        // Создание чата
        Yii::$app->db->createCommand()->insert('chat_room', [
            'id' => 1,
            'title' => 'Unit Test Chat',
            'creation_date' => '2019-08-26 13:00:00',
            'chat_type_id' => 1
        ])->execute();

        // Добавление участников в чат
        Yii::$app->db->createCommand()->insert('chat_member', [
            'chat_room_id' => 1,
            'worker_id' => 10,
            'creation_date' => '2019-08-26 13:00:00',
            'status_id' => 11,
            'chat_role_id' => 2
        ])->execute();
        Yii::$app->db->createCommand()->insert('chat_member', [
            'chat_room_id' => 1,
            'worker_id' => 20,
            'creation_date' => '2019-08-26 13:00:00',
            'status_id' => 21,
            'chat_role_id' => 2
        ])->execute();

        // Тест создания сообщения
        $params = json_encode(array(
            'text' => 'Msg',
            'sender_worker_id' => 10,
            'chat_room_id' => 1,
            'attachment_type' => '',
            'attachment' => ''
        ));
        $result = ChatController::actionNewMessage($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));

        // Проверка сохранения данных в таблицу chat_message
        $db_message = Yii::$app->db->createCommand(
            'SELECT * FROM chat_message'
        )->queryOne();
        $this->assertNotFalse($db_message);
        $this->assertEquals('Msg', $db_message['primary_message']);
        $this->assertEquals(10, $db_message['sender_worker_id']);
        $this->assertEquals(1, $db_message['chat_room_id']);
        $this->assertEmpty($db_message['chat_attachment_type_id']);
        $this->assertEmpty($db_message['attachment']);

        // Проверка сохранения данных в таблицу chat_message_reciever
        $db_reciever = Yii::$app->db->createCommand(
            'SELECT * FROM chat_message_reciever'
        )->queryOne();
        $this->assertNotFalse($db_reciever, print_r($db_reciever, true));
        $this->assertEquals($db_message['id'], $db_reciever['chat_message_id']);
        $this->assertEquals(20, $db_reciever['worker_id']);
        $this->assertEquals(29/*StatusEnumController::MSG_SENDED*/, $db_reciever['status_id_last']);
        $this->assertEquals(1, $db_reciever['chat_message_chat_room_id']);

        // Проверка сохранения данных в таблицу chat_reciever_history
        $db_history = Yii::$app->db->createCommand(
            'SELECT * FROM chat_reciever_history'
        )->queryOne();
        $this->assertNotFalse($db_history);
        $this->assertEquals($db_reciever['id'], $db_history['chat_message_reciever_id']);
        $this->assertEquals(29/*StatusEnumController::MSG_SENDED*/, $db_history['status_id']);

        // Проверка сохранения данных в кэш сообщений
        $cache_value = Yii::$app->redis_service->executeCommand('get', ['ChMsg:1:'.$db_message['id']]);
        $cache_value = unserialize($cache_value)[0];
        $this->assertEquals($db_message['id'], $cache_value['id']);
        $this->assertEquals('Msg', $cache_value['primary_message']);
        $this->assertEquals(10, $cache_value['sender_worker_id']);
        $this->assertEquals(1, $cache_value['chat_room_id']);
        $this->assertEquals('', $cache_value['chat_attachment_type_id']);
        $this->assertEquals('', $cache_value['attachment']);

        /**
         * Tear down
         */
        Yii::$app->redis_service->del('ChMsg:1:'.$db_message['id']);
    }

    /**
     * Тестируем: ChatController::actionNewMessage
     * По сценарию: в комнате 3 участника. Один из них отправляет сообщение без
     *   вложения.
     * Ожидаем: Соответствующие записи в таблицах chat_message, chat_message_reciever,
     *   chat_reciever_history
     */
    public function testActionNewGroupMessage()
    {
        // Создание чата
        Yii::$app->db->createCommand()->insert('chat_room', [
            'id' => 1,
            'title' => 'Unit Test GroupChat',
            'creation_date' => '2019-08-26 13:00:00',
            'chat_type_id' => 2
        ])->execute();

        // Добавление участников в чат
        Yii::$app->db->createCommand()->insert('chat_member', [
            'chat_room_id' => 1,
            'worker_id' => 10,
            'creation_date' => '2019-08-26 13:00:00',
            'status_id' => 11,
            'chat_role_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()->insert('chat_member', [
            'chat_room_id' => 1,
            'worker_id' => 20,
            'creation_date' => '2019-08-26 13:00:00',
            'status_id' => 21,
            'chat_role_id' => 2
        ])->execute();
        Yii::$app->db->createCommand()->insert('chat_member', [
            'chat_room_id' => 1,
            'worker_id' => 30,
            'creation_date' => '2019-08-26 13:00:00',
            'status_id' => 31,
            'chat_role_id' => 2
        ])->execute();

        // Тест создания сообщения
        $params = json_encode(array(
            'text' => 'Msg',
            'sender_worker_id' => 10,
            'chat_room_id' => 1,
            'attachment_type' => '',
            'attachment' => ''
        ));
        $result = ChatController::actionNewMessage($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));

        // Проверка сохранения данных в таблицу chat_message
        $db_message = Yii::$app->db->createCommand(
            'SELECT * FROM chat_message'
        )->queryOne();
        $this->assertNotFalse($db_message);
        $this->assertEquals('Msg', $db_message['primary_message']);
        $this->assertEquals(10, $db_message['sender_worker_id']);
        $this->assertEquals(1, $db_message['chat_room_id']);
        $this->assertEmpty($db_message['chat_attachment_type_id']);
        $this->assertEmpty($db_message['attachment']);

        // Проверка сохранения данных в таблицу chat_message_reciever
        $db_reciever = Yii::$app->db->createCommand(
            "SELECT * FROM chat_message_reciever where chat_message_id = {$db_message['id']}"
        )->queryAll();
        $this->assertNotFalse($db_reciever, print_r($db_reciever, true));
        $db_reciever = ArrayHelper::index($db_reciever, 'worker_id');
        $this->assertEquals($db_message['id'], $db_reciever[20]['chat_message_id']);
        $this->assertEquals(20, $db_reciever[20]['worker_id']);
        $this->assertEquals(29/*StatusEnumController::MSG_SENDED*/, $db_reciever[20]['status_id_last']);
        $this->assertEquals(1, $db_reciever[20]['chat_message_chat_room_id']);
        $this->assertEquals($db_message['id'], $db_reciever[30]['chat_message_id']);
        $this->assertEquals(30, $db_reciever[30]['worker_id']);
        $this->assertEquals(29/*StatusEnumController::MSG_SENDED*/, $db_reciever[30]['status_id_last']);
        $this->assertEquals(1, $db_reciever[30]['chat_message_chat_room_id']);

        // Проверка сохранения данных в таблицу chat_reciever_history
        $db_history = Yii::$app->db->createCommand(
            "SELECT * FROM chat_reciever_history where chat_message_reciever_id in ({$db_reciever[20]['id']}, {$db_reciever[30]['id']})"
        )->queryAll();
        $this->assertNotFalse($db_history);
        $db_history = ArrayHelper::index($db_history, 'chat_message_reciever_id');
        $this->assertEquals($db_reciever[20]['id'], $db_history[$db_reciever[20]['id']]['chat_message_reciever_id']);
        $this->assertEquals(29/*StatusEnumController::MSG_SENDED*/, $db_history[$db_reciever[20]['id']]['status_id']);
        $this->assertEquals($db_reciever[30]['id'], $db_history[$db_reciever[30]['id']]['chat_message_reciever_id']);
        $this->assertEquals(29/*StatusEnumController::MSG_SENDED*/, $db_history[$db_reciever[30]['id']]['status_id']);

        // Проверка сохранения данных в кэш сообщений
        $cache_value = Yii::$app->redis_service->executeCommand('get', ['ChMsg:1:'.$db_message['id']]);
        $cache_value = unserialize($cache_value)[0];
        $this->assertEquals($db_message['id'], $cache_value['id']);
        $this->assertEquals('Msg', $cache_value['primary_message']);
        $this->assertEquals(10, $cache_value['sender_worker_id']);
        $this->assertEquals(1, $cache_value['chat_room_id']);
        $this->assertEquals('', $cache_value['chat_attachment_type_id']);
        $this->assertEquals('', $cache_value['attachment']);

        /**
         * Tear down
         */
        Yii::$app->redis_service->executeCommand('del', ['ChMsg:1:'.$db_message['id']]);
    }

    /**
     * Тестируем: ChatController::actionNewChat
     * По сценарию: создание индивидуального чата
     * Ожидаем: Соответствующие записи в таблицах chat_room, chat_member
     */
    public function testCreateNewChat()
    {
        // Тест создания чата
        $params = json_encode(array(
            'sender_worker_id' => 10,
            'reciever_worker_id' => 20
        ));
        $result = ChatController::actionNewChat($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));

        // Проверка сохранения данных в таблицу chat_room
        $db_room = Yii::$app->db->createCommand(
            'SELECT * FROM chat_room where title like "Чат 10 и 20"'
        )->queryOne();
        $this->assertNotFalse($db_room);
        $this->assertEquals(1, $db_room['chat_type_id']);

        // Проверка сохранения данных в кэш чата
        $cache_value = Yii::$app->redis_service->executeCommand('get', ['ChRoom:'.$db_room['id']]);
        $cache_value = unserialize($cache_value)[0];
        $this->assertEquals($db_room['id'], $cache_value['id']);
        $this->assertEquals('Чат 10 и 20', $cache_value['title']);
        $this->assertEquals(1, $cache_value['chat_type_id']);

        $cache_value = Yii::$app->redis_service->executeCommand('get', ['ChRoom:'.$db_room['id']]);
        $cache_value = unserialize($cache_value)[0];
        $this->assertEquals($db_room['id'], $cache_value['id']);
        $this->assertEquals('Чат 10 и 20', $cache_value['title']);
        $this->assertEquals(1, $cache_value['chat_type_id']);

        // Проверка сохранения данных в таблицу chat_member
        $db_members = Yii::$app->db->createCommand(
            'SELECT * FROM chat_member where worker_id in (10, 20)'
        )->queryAll();
        $this->assertNotEmpty($db_members);
        $db_members = ArrayHelper::index($db_members, 'worker_id');
        $this->assertEquals($db_room['id'], $db_members[10]['chat_room_id']);
        $this->assertEquals($db_room['creation_date'], $db_members[10]['creation_date']);
        $this->assertEquals(1, $db_members[10]['status_id']);
        $this->assertEquals(2, $db_members[10]['chat_role_id']);
        $this->assertEquals($db_room['id'], $db_members[20]['chat_room_id']);
        $this->assertEquals($db_room['creation_date'], $db_members[20]['creation_date']);
        $this->assertEquals(1, $db_members[20]['status_id']);
        $this->assertEquals(2, $db_members[20]['chat_role_id']);

        // Проверка сохранения данных участников в кэш
//        $cache_value = Yii::$app->redis_service->executeCommand('get', ['ChMemb:'.$db_room['id'].':10']);
//        $cache_value = unserialize($cache_value)[0];
//        $this->assertEquals($db_room['id'], $cache_value['chat_room_id'], print_r($cache_value, true));
//        $this->assertEquals(10, $cache_value['worker_id']);
//        $this->assertEquals(1, $cache_value['status_id']);
//        $this->assertEquals(2, $cache_value['chat_role_id']);
//
//        $cache_value = Yii::$app->redis_service->executeCommand('get', ['ChMemb:'.$db_room['id'].':20']);
//        $cache_value = unserialize($cache_value)[0];
//        $this->assertEquals($db_room['id'], $cache_value['chat_room_id']);
//        $this->assertEquals(20, $cache_value['worker_id']);
//        $this->assertEquals(1, $cache_value['status_id']);
//        $this->assertEquals(2, $cache_value['chat_role_id']);

        /**
         * Tear down         */
        Yii::$app->redis_service->executeCommand('del', ['ChRoom:10:'.$db_room['id']]);
        Yii::$app->redis_service->executeCommand('del', ['ChRoom:20:'.$db_room['id']]);
//        Yii::$app->redis_service->executeCommand('del', ['ChMemb:'.$db_room['id'].':10']);
//        Yii::$app->redis_service->executeCommand('del', ['ChMemb:'.$db_room['id'].':20']);
    }

    /**
     * Тестируем: ChatController::actionNewRoom
     * По сценарию: создание комнаты группового чата
     * Ожидаем: Соответствующие записи в таблицах chat_room, chat_member
     */
    public function testCreateNewChatRoom()
    {
        // Тест создания группового чата
        $params = json_encode(array(
            'title' => 'Room unit',
            'workers_ids' => [10, 20, 30],
        ));
        $result = ChatController::actionNewRoom($params);
        //fwrite(STDOUT, print_r($result, true));
        $this->assertEquals(1, $result['status'], print_r($result, true));

        // Проверка сохранения данных в таблицу chat_room
        $db_room = Yii::$app->db->createCommand(
            'SELECT * FROM chat_room where title like "Room unit"'
        )->queryOne();
        $this->assertNotFalse($db_room);
        $this->assertEquals(2, $db_room['chat_type_id']);

        // Проверка сохранения данных в таблицу chat_member
        $db_members = Yii::$app->db->createCommand(
            'SELECT * FROM chat_member where worker_id in (10, 20, 30)'
        )->queryAll();
        $this->assertNotEmpty($db_members);
        $db_members = ArrayHelper::index($db_members, 'worker_id');
        $this->assertEquals($db_room['id'], $db_members[10]['chat_room_id']);
        $this->assertEquals($db_room['creation_date'], $db_members[10]['creation_date']);
        $this->assertEquals(1, $db_members[10]['status_id']);
        $this->assertEquals(2, $db_members[10]['chat_role_id']);
        $this->assertEquals($db_room['id'], $db_members[20]['chat_room_id']);
        $this->assertEquals($db_room['creation_date'], $db_members[20]['creation_date']);
        $this->assertEquals(1, $db_members[20]['status_id']);
        $this->assertEquals(2, $db_members[20]['chat_role_id']);
        $this->assertEquals($db_room['id'], $db_members[30]['chat_room_id']);
        $this->assertEquals($db_room['creation_date'], $db_members[30]['creation_date']);
        $this->assertEquals(1, $db_members[30]['status_id']);
        $this->assertEquals(2, $db_members[30]['chat_role_id']);

        /**
         * Tear down
         */
        Yii::$app->redis_service->del('ChRoom:10:'.$db_members[10]['chat_room_id']);
        Yii::$app->redis_service->del('ChRoom:20:'.$db_members[10]['chat_room_id']);
        Yii::$app->redis_service->del('ChRoom:30:'.$db_members[10]['chat_room_id']);
    }

    /**
     * Тестируем: ChatController::actionGetMessagesByRoom
     * По сценариям:
     *   1) Получение списка сообщений из пустого чата
     *   2) Получение списка сообщений из чата с сообщениями
     *   3) Получение списка сообщений из несуществующего чата
     * Ожидаем:
     *   1) Пустой массив
     *   2) Массив с сообщениями (текст, вложение, ФИО отправителя)
     *   3) Ошибка
     *
     * Тест зависит от данных в БД, т.к. лень писать тестовое окружение по созданию воркеров
     *
     * @depends testCreateNewChatRoom
     * @depends testActionNewGroupMessage
     */
    public function testGetMessagesByRoom()
    {
        // Получение списка сообщений из несуществующего чата
        $params = json_encode(array(
            'chat_room_id' => 666,
            'date_time' => ''
        ));
        $result = ChatController::actionGetMessagesByRoom($params);
        $this->assertEquals(0, $result['status'], print_r($result, true));

        // Создание чата
        $params = json_encode(array(
            'sender_worker_id' => 10,
            'reciever_worker_id' => 20,
        ));
        $result = ChatController::actionNewChat($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $chat_id = Yii::$app->db->createCommand(
            'SELECT id from chat_room where title like "Чат 10 и 20"'
        )->queryScalar();

        // Получение списка сообщений из пустого чата
        $params = json_encode(array(
            'chat_room_id' => $chat_id,
            'date_time' => ''
        ));
        $result = ChatController::actionGetMessagesByRoom($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $this->assertEmpty($result['Items']);

        // Добавление сообщений в чат
        // Тест создания сообщения
        $params = json_encode(array(
            'text' => 'Привет',
            'sender_worker_id' => 10,
            'chat_room_id' => $chat_id,
            'attachment_type' => null,
            'attachment' => null
        ));
        $result = ChatController::actionNewMessage($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $params = json_encode(array(
            'text' => 'Лови фотку',
            'sender_worker_id' => 20,
            'chat_room_id' => $chat_id,
            'attachment_type' => 1,
            'attachment' => '/img/doom'
        ));
        $result = ChatController::actionNewMessage($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));

        // Получение списка сообщений из чата с сообщениями
        $params = json_encode(array(
            'chat_room_id' => $chat_id,
            'date_time' => ''
        ));
        $result = ChatController::actionGetMessagesByRoom($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $messages = ArrayHelper::index($result['Items'], 'sender_worker_id');

        $this->assertEquals('Привет', $messages[10]['primary_message'], print_r($messages, true));
        $this->assertEmpty($messages[10]['chat_attachment_type_id']);
        $this->assertEmpty($messages[10]['attachment']);

        $this->assertEquals('Лови фотку', $messages[20]['primary_message']);
        $this->assertEquals(1, $messages[20]['chat_attachment_type_id']);
        $this->assertEquals('/img/doom', $messages[20]['attachment']);

        /**
         * Tear down
         */
        Yii::$app->redis_service->del('ChRoom:10:'.$chat_id);
        Yii::$app->redis_service->del('ChRoom:20:'.$chat_id);
    }

    /**
     * Тестируем: ChatController::actionGetRoomsByWorker
     * По сценарию:
     *   1) воркер (id = 10) находится в одном групповом чате и двух
     *      индивидуальных.
     *   2) воркер не находится ни в одном чате
     * Ожидаем:
     *   1) массив с записями с информацией трёх чатов
     *   2) пустой массив
     *
     * @depends testCreateNewChatRoom
     */
    public function testGetRoomsByWorker()
    {
        // Создание чатов с воркером
        $params = json_encode(array(
            'sender_worker_id' => 10,
            'reciever_worker_id' => 20,
        ));
        $result = ChatController::actionNewChat($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));

        $params = json_encode(array(
            'sender_worker_id' => 30,
            'reciever_worker_id' => 10,
        ));
        $result = ChatController::actionNewChat($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));

        $params = json_encode(array(
            'title' => 'Room unit',
            'workers_ids' => [10, 20, 30],
        ));
        $result = ChatController::actionNewRoom($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));

        // Получение идентификаторов чатов из БД
        $result_db = Yii::$app->db->createCommand(
            'SELECT * FROM chat_room where title like :title1
                or title like :title2
                or title like :title3'
        )
            ->bindValue(':title1', 'Чат 10 и 20')
            ->bindValue(':title2', 'Чат 30 и 10')
            ->bindValue(':title3', 'Room unit')
            ->queryAll();
        $result_db = ArrayHelper::index($result_db, 'id');

        $params = json_encode(array(
            'worker_id' => 10
        ));
        $result = ChatController::actionGetRoomsByWorker($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));

        // Отдельная проверка отсутствия непрочитанных сообщений
        // Удаляем это поле, потому что его нет в ожидаемой выборке из БД.
        foreach($result['Items'] as &$chat_room) {
            $this->assertEquals(0, $chat_room['unread']);
            unset($chat_room['unread']);
        }

        $result = ArrayHelper::index($result['Items'], 'id');
        foreach ($result_db as $key => $item) {
            $this->assertEquals($item['title'], $result[$key]['title']);
            $this->assertEquals($item['id'], $result[$key]['id']);
            $this->assertEquals($item['chat_type_id'], $result[$key]['chat_type_id']);
        }

        // 2) Получение чатов у воркера, который не находится ни в одном чате
        $params = json_encode(array(
            'worker_id' => 765
        ));
        $result = ChatController::actionGetRoomsByWorker($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $this->assertEmpty($result['Items'], print_r($result['Items'], true));
    }

    /**
     * Тестируем: ChatController::actionSetMessageRecieverLastStatus
     * По сценарию: в индивидуальном чате создано сообщение. Последовательно
     *   изменяем его статус на "доставлено" и "прочитано"
     * Ожидаем: обновление поля status_id_last в таблице chat_message_reciever,
     *   новые записи в истории получения сообщений в таблице chat_reciever_history
     *
     * @depends testActionNewMessage
     */
    public function testSetMessageRecieverLastStatus()
    {
        // Создание чата
        $params = json_encode(array(
            'sender_worker_id' => 10,
            'reciever_worker_id' => 20,
        ));
        $result = ChatController::actionNewChat($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $chat_id = Yii::$app->db->createCommand(
            'SELECT id from chat_room where title like "Чат 10 и 20"'
        )->queryScalar();

        // Добавление сообщений в чат
        $params = json_encode(array(
            'text' => 'Привет',
            'sender_worker_id' => 10,
            'chat_room_id' => $chat_id,
            'attachment_type' => null,
            'attachment' => null
        ));
        $result = ChatController::actionNewMessage($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $message_id = Yii::$app->db->createCommand(
            'SELECT id from chat_message where sender_worker_id = 10'
        )->queryScalar();

        // Смена статуса на 28 - сообщение доставлено
        $params = json_encode(array(
            'chat_message_id' => $message_id,
            'worker_id' => 20,
            'status_id' => 28,
        ));
        $result = ChatController::actionSetMessageRecieverLastStatus($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));

        // Проверяем обновление статуса
        $result_status_db = Yii::$app->db->createCommand(
            "SELECT * FROM chat_message_reciever where chat_message_id = $message_id"
        )->queryOne();
        $this->assertEquals(28, $result_status_db['status_id_last'], print_r($result_status_db, true));

        // Проверяем новые записи в истории получения сообщений
        $result_history_db = Yii::$app->db->createCommand(
            "SELECT * FROM chat_reciever_history where chat_message_reciever_id = {$result_status_db['id']}
              and status_id = 28"
        )->queryOne();
        $this->assertNotFalse($result_history_db);

        // Смена статуса на 30 - сообщение прочитано
        $params = json_encode(array(
            'chat_message_id' => $message_id,
            'worker_id' => 20,
            'status_id' => 30,
        ));
        $result = ChatController::actionSetMessageRecieverLastStatus($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));

        // Проверяем обновление статуса
        $result_status_db = Yii::$app->db->createCommand(
            "SELECT * FROM chat_message_reciever where chat_message_id = $message_id"
        )->queryOne();
        $this->assertEquals(30, $result_status_db['status_id_last'], print_r($result_status_db, true));

        // Проверяем новые записи в истории получения сообщений
        $result_history_db = Yii::$app->db->createCommand(
            "SELECT * FROM chat_reciever_history where chat_message_reciever_id = {$result_status_db['id']}
              and status_id = 30"
        )->queryOne();
        $this->assertNotFalse($result_history_db);
    }

    /**
     * Тестируем: ChatController::actionAddMemberToChat
     * По сценарию:
     *   1) Добавляем нового участника в индивидуальный чат
     *   2) Добавляем нового участника в групповой чат
     * Ожидаем:
     *   1) Новая запись в таблице chat_member. Тип чата сменяется на "групповой".
     *     Название чата меняется на список фамилий.
     *   2) Новая запись в таблице chat_member.
     *
     * @warnings Тест зависит от БД, т.к. лень писать тестовое окружение на создание
     *  сотрудников и воркеров
     */
    public function testAddMemberToChat()
    {
        // Создание чата
        $params = json_encode(array(
            'sender_worker_id' => 10,
            'reciever_worker_id' => 20,
        ));
        $result = ChatController::actionNewChat($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $chat_id = Yii::$app->db->createCommand(
            'SELECT id from chat_room where title like "Чат 10 и 20"'
        )->queryScalar();

        // 1) Добавление участника в индивидуальный чат
        $params = json_encode(array(
            'worker_id' => 30,
            'chat_room_id' => $chat_id,
        ));
        $result = ChatController::actionAddMemberToChat($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $new_member = Yii::$app->db->createCommand(
            'SELECT * from chat_member where worker_id = 30'
        )->queryOne();
        $this->assertEquals($new_member['id'], $result['Items']);
        $this->assertEquals($chat_id, $new_member['chat_room_id']);
        $this->assertEquals(1, $new_member['status_id']);
        $this->assertEquals(2, $new_member['chat_role_id']);

        $chat = Yii::$app->db->createCommand(
            'SELECT * from chat_room where id = ' . $chat_id
        )->queryOne();
        $this->assertEquals('last1, last2, last3', $chat['title']);
        $this->assertEquals(2, $chat['chat_type_id']);

        // 2) Добавление участника в групповой чат
        $params = json_encode(array(
            'worker_id' => 40,
            'chat_room_id' => $chat_id,
        ));
        $result = ChatController::actionAddMemberToChat($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $new_member = Yii::$app->db->createCommand(
            'SELECT * from chat_member where worker_id = 40'
        )->queryOne();
        $this->assertEquals($new_member['id'], $result['Items']);
        $this->assertEquals($chat_id, $new_member['chat_room_id']);
        $this->assertEquals(1, $new_member['status_id']);
        $this->assertEquals(2, $new_member['chat_role_id']);

        $chat = Yii::$app->db->createCommand(
            'SELECT * from chat_room where id = ' . $chat_id
        )->queryOne();
        $this->assertEquals('last1, last2, last3', $chat['title']);
        $this->assertEquals(2, $chat['chat_type_id']);
    }

    /**
     * Тестируем: ChatDatabaseModel::getUnreadMessageCountByWorker
     * По сценарию: воркер (id = 10) находится в 4 чатах - 2 индивидуальных и 2 групповых, с
     * прочитанными и не прочитанными сообщениями соответственно
     *
     * @depends testActionNewMessage
     * @depends testActionNewGroupMessage
     * @depends testCreateNewChat
     * @depends testSetMessageRecieverLastStatus
     */
    public function testGetUnreadMessageCount()
    {
        $expected = array();
        // Создание индивидуального чата c непрочитанными сообщениями
        $params = json_encode(array(
            'sender_worker_id' => 10,
            'reciever_worker_id' => 20,
        ));
        $result = ChatController::actionNewChat($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $chat_id = Yii::$app->db->createCommand(
            'SELECT id from chat_room where title like "Чат 10 и 20"'
        )->queryScalar();
        // Добавление сообщений в чат
        $params = json_encode(array(
            'text' => 'Привет',
            'sender_worker_id' => 20,
            'chat_room_id' => $chat_id,
            'attachment_type' => null,
            'attachment' => null
        ));
        $result = ChatController::actionNewMessage($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $expected[$chat_id] = 1;

        // Создание индивидуального чата без непрочитанных сообщениями
        $params = json_encode(array(
            'sender_worker_id' => 10,
            'reciever_worker_id' => 30,
        ));
        $result = ChatController::actionNewChat($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $chat_id = Yii::$app->db->createCommand(
            'SELECT id from chat_room where title like "Чат 10 и 30"'
        )->queryScalar();
        // Добавление сообщений в чат
        $params = json_encode(array(
            'text' => 'Привет',
            'sender_worker_id' => 30,
            'chat_room_id' => $chat_id,
            'attachment_type' => null,
            'attachment' => null
        ));
        $result = ChatController::actionNewMessage($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $message_id = Yii::$app->db->createCommand(
            "SELECT id from chat_message where sender_worker_id = 30 and chat_room_id = $chat_id"
        )->queryScalar();
        // Смена статуса на 30 - сообщение прочитано
        $params = json_encode(array(
            'chat_message_id' => $message_id,
            'worker_id' => 10,
            'status_id' => 30,
        ));
        $result = ChatController::actionSetMessageRecieverLastStatus($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));

        // Создание группового чата с непрочитанными сообщениями
        $params = json_encode(array(
            'title' => 'Unread Chat Room',
            'workers_ids' => [10, 20, 30],
        ));
        $result = ChatController::actionNewRoom($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $chat_id = Yii::$app->db->createCommand(
            'SELECT id from chat_room where title like "Unread Chat Room"'
        )->queryScalar();
        // Тест создания сообщения
        $params = json_encode(array(
            'text' => 'Msg',
            'sender_worker_id' => 20,
            'chat_room_id' => $chat_id,
            'attachment_type' => '',
            'attachment' => ''
        ));
        $result = ChatController::actionNewMessage($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $params = json_encode(array(
            'text' => 'Msg',
            'sender_worker_id' => 30,
            'chat_room_id' => $chat_id,
            'attachment_type' => '',
            'attachment' => ''
        ));
        $result = ChatController::actionNewMessage($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $expected[$chat_id] = 2;

        // Создание группового чата без непрочитанных сообщений
        $params = json_encode(array(
            'title' => 'Read Chat Room',
            'workers_ids' => [10, 20, 40],
        ));
        $result = ChatController::actionNewRoom($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $chat_id = Yii::$app->db->createCommand(
            'SELECT id from chat_room where title like "Read Chat Room"'
        )->queryScalar();
        // Тест создания сообщения
        $params = json_encode(array(
            'text' => 'Msg',
            'sender_worker_id' => 40,
            'chat_room_id' => $chat_id,
            'attachment_type' => '',
            'attachment' => ''
        ));
        $result = ChatController::actionNewMessage($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $message_id = Yii::$app->db->createCommand(
            "SELECT id from chat_message where sender_worker_id = 40 and chat_room_id = $chat_id"
        )->queryScalar();
        // Смена статуса на 30 - сообщение прочитано
        $params = json_encode(array(
            'chat_message_id' => $message_id,
            'worker_id' => 10,
            'status_id' => 30,
        ));
        $result = ChatController::actionSetMessageRecieverLastStatus($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));

        // Тест
        $chat_db = new ChatDatabaseModel();
        $result = $chat_db->getUnreadMessageCountByWorker(10);
        $this->assertEquals($expected, $result, print_r($result, true), 0.0, 10, true);

        $result = $chat_db->getUnreadMessageCountByWorker(100);
        $this->assertEmpty($result);
    }

    /**
     * Тестируем: ChatController::actionDeleteMessage
     * По сценарию: в индивидуальном чате создано сообщение. Удаляем его
     * Ожидаем: удаление записи из таблицы chat_message и связанных таблиц
     *   chat_message_reciever и chat_reciever_history
     *
     * @depends testActionNewMessage
     * @depends testCreateNewChat
     */
    public function testDeleteMessage()
    {
        // Создание чата
        $params = json_encode(array(
            'sender_worker_id' => 10,
            'reciever_worker_id' => 20,
        ));
        $result = ChatController::actionNewChat($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $chat_id = Yii::$app->db->createCommand(
            'SELECT id from chat_room where title like "Чат 10 и 20"'
        )->queryScalar();

        // Добавление сообщений в чат
        $params = json_encode(array(
            'text' => 'Привет',
            'sender_worker_id' => 10,
            'chat_room_id' => $chat_id,
            'attachment_type' => null,
            'attachment' => null
        ));
        $result = ChatController::actionNewMessage($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $message_id = Yii::$app->db->createCommand(
            'SELECT id from chat_message where sender_worker_id = 10'
        )->queryScalar();

        $chat_message_reciever_id = Yii::$app->db->createCommand(
            "SELECT id FROM chat_message_reciever where chat_message_id = {$message_id}"
        )->queryScalar();
        $chat_reciever_history_id = Yii::$app->db->createCommand(
            "SELECT id FROM chat_reciever_history where chat_message_reciever_id = {$chat_message_reciever_id}"
        )->queryScalar();

        // Удаление сообщения
        $params = json_encode(array(
            'message_id' => $message_id
        ));
        $result = ChatController::actionDeleteMessage($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));

        // Проверка удаления
        $result_db = Yii::$app->db->createCommand(
            "SELECT * FROM chat_message where id = {$message_id}"
        )->queryAll();
        $this->assertEmpty($result_db);

        $result_db = Yii::$app->db->createCommand(
            "SELECT * FROM chat_message_reciever where id = {$chat_message_reciever_id}"
        )->queryAll();
        $this->assertEmpty($result_db);

        $result_db = Yii::$app->db->createCommand(
            "SELECT * FROM chat_reciever_history where id = {$chat_reciever_history_id}"
        )->queryAll();
        $this->assertEmpty($result_db);
    }

    /**
     * Тестируем: ChatController::actionClearChatMessages
     * По сценарию: Индивидуальный чат с двумя сообщениями. Удаляем все сообщения
     * Ожидаем: удаление записей из таблицы chat_message и связанных таблиц
     *   chat_message_reciever и chat_reciever_history
     */
    public function testClearChatMessages()
    {
        // Создание чата
        $params = json_encode(array(
            'sender_worker_id' => 10,
            'reciever_worker_id' => 20,
        ));
        $result = ChatController::actionNewChat($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $chat_id = Yii::$app->db->createCommand(
            'SELECT id from chat_room where title like "Чат 10 и 20"'
        )->queryScalar();

        // Добавление сообщений в чат
        $params = json_encode(array(
            'text' => 'Привет',
            'sender_worker_id' => 10,
            'chat_room_id' => $chat_id,
            'attachment_type' => null,
            'attachment' => null
        ));
        $result = ChatController::actionNewMessage($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $message_id1 = Yii::$app->db->createCommand(
            'SELECT id from chat_message where sender_worker_id = 10'
        )->queryScalar();
        $chat_message_reciever_id1 = Yii::$app->db->createCommand(
            "SELECT id FROM chat_message_reciever where chat_message_id = {$message_id1}"
        )->queryScalar();
        $chat_reciever_history_id1 = Yii::$app->db->createCommand(
            "SELECT id FROM chat_reciever_history where chat_message_reciever_id = {$chat_message_reciever_id1}"
        )->queryScalar();

        $params = json_encode(array(
            'text' => 'Лови фотку',
            'sender_worker_id' => 20,
            'chat_room_id' => $chat_id,
            'attachment_type' => 1,
            'attachment' => '/img/doom'
        ));
        $result = ChatController::actionNewMessage($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $message_id2 = Yii::$app->db->createCommand(
            'SELECT id from chat_message where sender_worker_id = 20'
        )->queryScalar();
        $chat_message_reciever_id2 = Yii::$app->db->createCommand(
            "SELECT id FROM chat_message_reciever where chat_message_id = {$message_id2}"
        )->queryScalar();
        $chat_reciever_history_id2 = Yii::$app->db->createCommand(
            "SELECT id FROM chat_reciever_history where chat_message_reciever_id = {$chat_message_reciever_id2}"
        )->queryScalar();

        // Очистка сообщений чата
        $params = json_encode(array(
            'chat_room_id' => $chat_id,
        ));
        $result = ChatController::actionClearChatMessages($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));

        // Проверка удаления
        $result_db = Yii::$app->db->createCommand(
            "SELECT * FROM chat_message where id in ({$message_id1}, {$message_id2})"
        )->queryAll();
        $this->assertEmpty($result_db, print_r($result_db, true));

        $result_db = Yii::$app->db->createCommand(
            "SELECT * FROM chat_message_reciever where id in ({$chat_message_reciever_id1}, {$chat_message_reciever_id2})"
        )->queryAll();
        $this->assertEmpty($result_db, print_r($result_db, true));

        $result_db = Yii::$app->db->createCommand(
            "SELECT * FROM chat_reciever_history where id in ({$chat_reciever_history_id1}, {$chat_reciever_history_id2})"
        )->queryAll();
        $this->assertEmpty($result_db);
    }

    /**
     * Тестируем: ChatController::actionDeleteChatRoom
     * По сценарию: Создан индивидуальный чат. Удаляем его
     * Ожидаем: удаление записей из таблицы chat_room и связанных таблиц
     */
    public function testDeleteChatRoom()
    {
        // Создание чата
        $params = json_encode(array(
            'sender_worker_id' => 10,
            'reciever_worker_id' => 20,
        ));
        $result = ChatController::actionNewChat($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $chat_id = Yii::$app->db->createCommand(
            'SELECT id from chat_room where title like "Чат 10 и 20"'
        )->queryScalar();

        // Удаление чата
        $params = json_encode(array(
            'chat_room_id' => $chat_id,
        ));
        $result = ChatController::actionDeleteChatRoom($params);
        $this->assertEquals(1, $result['status'], print_r($result, true));

        $result_db = Yii::$app->db->createCommand(
            "SELECT * FROM chat_room where id = {$chat_id}"
        )->queryAll();
        $this->assertEmpty($result_db);
    }
}
