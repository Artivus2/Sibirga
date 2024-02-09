<?php

use backend\controllers\StrataJobController;

class TextMessageTest extends \Codeception\Test\Unit
{
    public function _before()
    {
        Yii::$app->redis_service->del('packages');
    }

    public function _after()
    {
        Yii::$app->redis_service->del('packages');

        $keys = Yii::$app->redis_worker->scan(0, 'MATCH', 'WoMi:290:1', 'COUNT', '10000000')[1];
        if ($keys) {
            foreach ($keys as $key) {
                Yii::$app->redis_worker->del($key);
            }
        }
        $keys = Yii::$app->redis_worker->scan(0, 'MATCH', 'WoPa:1:*', 'COUNT', '10000000')[1];
        if ($keys) {
            foreach ($keys as $key) {
                Yii::$app->redis_worker->del($key);
            }
        }
    }

    /**
     * Тестируем: StrataJobController::AddMessage
     * По сценарию:
     *   Отправка текстового сообщения воркерам, к которым не привязаны лампы
     *   (или они отсутствуют в базе)
     * Ожидаем:
     *   Сообщения на отправку не добавляются ни в базу, ни в кэш
     */
    public function testAddTextMessageToNonExistingWorkers()
    {
        $workers = [1, 2];
        $text = 'Unit Test Text';
        $type = 'text';
        $sender = 70003762;
        $result = StrataJobController::AddMessage($workers, $text, $type, $sender);
        $this->assertEquals(0, $result['status'], print_r($result, true));

        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM text_message where reciever_worker_id in (1, 2)'
        )->queryAll();
        $this->assertEmpty($db_result);

        $cache_result = Yii::$app->redis_service->get('packages');
        $this->assertNull($cache_result);
    }

    /**
     * Тестируем: StrataJobController::AddMessage
     * По сценарию:
     *   Отправка текстового сообщения воркерам, к которым привязаны лампы
     * Ожидаем:
     *   1) Сообщения на отправку добавляются в базу
     *   2) Сообщения на отправку добавляются в кэш
     *   3) Сохранение параметров воркера в БД
     *   4) Сохранение параметров воркера в кэш
     */
    public function testAddTextMessageToExistingWorkers()
    {
        /**
         * Создание светильника Unit Test Lamp:
         *   id: 1000000
         *   object_id: 47
         *   net_id: 12
         *   mine_id: 290
         */
        Yii::$app->db->createCommand()  // Объект метки
        ->insert('sensor', [
            'id' => 1000000,
            'title' => 'Unit Test Lamp',
            'sensor_type_id' => 1,
            'asmtp_id' => 1,
            'object_id' => 47
        ])->execute();
        Yii::$app->db->createCommand()  // Сетевой идентификатор
        ->insert('sensor_parameter', [
            'id' => 1100000,
            'sensor_id' => 1000000,
            'parameter_id' => 88,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Тип объекта
        ->insert('sensor_parameter', [
            'id' => 1200000,
            'sensor_id' => 1000000,
            'parameter_id' => 274,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Идентификатор шахты
        ->insert('sensor_parameter', [
            'id' => 1300000,
            'sensor_id' => 1000000,
            'parameter_id' => 346,
            'parameter_type_id' => 2
        ])->execute();
        Yii::$app->db->createCommand()  // Значение сетевого идентификатора
        ->insert('sensor_parameter_handbook_value', [
            'id' => 1110000,
            'sensor_parameter_id' => 1100000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '12',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение типа объекта
        ->insert('sensor_parameter_handbook_value', [
            'id' => 1210000,
            'sensor_parameter_id' => 1200000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '47',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение идентификатора шахты
        ->insert('sensor_parameter_handbook_value', [
            'id' => 1310000,
            'sensor_parameter_id' => 1300000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '290',
            'status_id' => 1
        ])->execute();

        /**
         * Создание воркера, привязанного к сенсору
         */
        Yii::$app->db->createCommand()
            ->insert('worker', [
                'id' => 1,
                'employee_id' => 901,
                'position_id' => 1,
                'company_department_id' => '801',
                'tabel_number' => 'TestWorker',
                'date_start' => '2019-08-07'
            ])->execute();
        Yii::$app->db->createCommand()
            ->insert('worker_object', [
                'id' => 1,
                'worker_id' => 1,
                'object_id' => 25,
                'role_id' => 1
            ])->execute();
        Yii::$app->db->createCommand()
            ->insert('worker_parameter', [
                'id' => 50000,
                'worker_object_id' => 1,
                'parameter_id' => 83,
                'parameter_type_id' => 2
            ])->execute();
        Yii::$app->db->createCommand()
            ->insert('worker_parameter_sensor', [
                'id' => 10000,
                'worker_parameter_id' => 50000,
                'sensor_id' => 1000000,
                'date_time' => date('Y-m-d H:i:s.u'),
                'type_relation_sensor' => 1
            ])->execute();

        $result = StrataJobController::AddMessage([1], 'Unit Test Text', 'text', 70003762);
        $this->assertEquals(1, $result['status'], print_r($result, true));

        // 1) Сообщение в БД
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM text_message where reciever_worker_id  = 1'
        )->queryOne();
        $this->assertNull($db_result['sender_sensor_id']);
        $this->assertEquals(1000000, $db_result['reciever_sensor_id']);
        $this->assertEquals(70003762, $db_result['sender_worker_id']);
        $this->assertEquals(1, $db_result['reciever_worker_id']);
        $this->assertEquals('surface', $db_result['sender_network_id']);
        $this->assertEquals(12, $db_result['reciever_network_id']);
        $this->assertEquals('Unit Test Text', $db_result['message']);
        $this->assertEquals(StatusEnumController::MSG_SENDED, $db_result['status_id']);
        $this->assertLessThan(200, $db_result['message_id']);
        $this->assertGreaterThanOrEqual(0, $db_result['message_id']);
        $this->assertEquals('text', $db_result['message_type']);
        unset($db_result);

        // 2) Сообщение в кэше
        $cache_result = Yii::$app->redis_service->lrange('packages', 0, 5)[0];
        $this->assertJson($cache_result, print_r($cache_result, true));
        $cache_result = json_decode($cache_result, true);
        $this->assertEquals(12, $cache_result['network_id']);
        $this->assertLessThan(200, $cache_result['message_id']);
        $this->assertGreaterThanOrEqual(0, $cache_result['message_id']);
        $this->assertEquals('text', $cache_result['type']);
        unset($cache_result);

        // 3) Параметры воркера БД
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM `view_worker_parameters_last_values` where worker_id = 1'
        )->queryAll();
        $db_result = \yii\helpers\ArrayHelper::map($db_result, 'parameter_type_id', 'value', 'parameter_id');
        $this->assertEquals(StatusEnumController::MSG_SENDED, $db_result[ParameterEnumController::TEXT_MSG_FLAG][ParameterTypeEnumController::MEASURED]);
        unset($db_result);

        // 4) Параметры воркера кэш
        $cache_parameters = (new \backend\controllers\cachemanagers\WorkerCacheController())->multiGetParameterValue(1, '*', '*');
        foreach ($cache_parameters as $cache_parameter) {
            $cache_result[$cache_parameter['parameter_id']][$cache_parameter['parameter_type_id']] = $cache_parameter['value'];
        }
        $this->assertEquals(StatusEnumController::MSG_SENDED, $cache_result[ParameterEnumController::TEXT_MSG_FLAG][ParameterTypeEnumController::MEASURED]);
        unset($cache_result);
    }

    /**
     * Тестируем: StrataJobController::AddMessage
     * По сценарию:
     *   Отправка аварийного сообщения воркерам, к которым привязаны лампы
     * Ожидаем:
     *   1) Сообщения на отправку добавляются в базу
     *   2) Сообщения на отправку добавляются в кэш
     *   3) Сохранение параметров воркера в БД
     *   4) Сохранение параметров воркера в кэш
     */
    public function testAddAlarmMessageToExistingWorkers()
    {
        /**
         * Создание светильника Unit Test Lamp:
         *   id: 1000000
         *   object_id: 47
         *   net_id: 12
         *   mine_id: 290
         */
        Yii::$app->db->createCommand()  // Объект метки
        ->insert('sensor', [
            'id' => 1000000,
            'title' => 'Unit Test Lamp',
            'sensor_type_id' => 1,
            'asmtp_id' => 1,
            'object_id' => 47
        ])->execute();
        Yii::$app->db->createCommand()  // Сетевой идентификатор
        ->insert('sensor_parameter', [
            'id' => 1100000,
            'sensor_id' => 1000000,
            'parameter_id' => 88,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Тип объекта
        ->insert('sensor_parameter', [
            'id' => 1200000,
            'sensor_id' => 1000000,
            'parameter_id' => 274,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Идентификатор шахты
        ->insert('sensor_parameter', [
            'id' => 1300000,
            'sensor_id' => 1000000,
            'parameter_id' => 346,
            'parameter_type_id' => 2
        ])->execute();
        Yii::$app->db->createCommand()  // Значение сетевого идентификатора
        ->insert('sensor_parameter_handbook_value', [
            'id' => 1110000,
            'sensor_parameter_id' => 1100000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '12',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение типа объекта
        ->insert('sensor_parameter_handbook_value', [
            'id' => 1210000,
            'sensor_parameter_id' => 1200000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '47',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение идентификатора шахты
        ->insert('sensor_parameter_handbook_value', [
            'id' => 1310000,
            'sensor_parameter_id' => 1300000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '290',
            'status_id' => 1
        ])->execute();

        /**
         * Создание воркера, привязанного к сенсору
         */
        Yii::$app->db->createCommand()
            ->insert('worker', [
                'id' => 1,
                'employee_id' => 901,
                'position_id' => 1,
                'company_department_id' => '801',
                'tabel_number' => 'TestWorker',
                'date_start' => '2019-08-07'
            ])->execute();
        Yii::$app->db->createCommand()
            ->insert('worker_object', [
                'id' => 1,
                'worker_id' => 1,
                'object_id' => 25,
                'role_id' => 1
            ])->execute();
        Yii::$app->db->createCommand()
            ->insert('worker_parameter', [
                'id' => 50000,
                'worker_object_id' => 1,
                'parameter_id' => 83,
                'parameter_type_id' => 2
            ])->execute();
        Yii::$app->db->createCommand()
            ->insert('worker_parameter_sensor', [
                'id' => 10000,
                'worker_parameter_id' => 50000,
                'sensor_id' => 1000000,
                'date_time' => date('Y-m-d H:i:s.u'),
                'type_relation_sensor' => 1
            ])->execute();

        $result = StrataJobController::AddMessage([1], 'Unit Test Alarm', 'alarm', 70003762);
        $this->assertEquals(1, $result['status'], print_r($result, true));

        // 1) Сообщение в БД
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM text_message where reciever_worker_id  = 1'
        )->queryOne();
        $this->assertNull($db_result['sender_sensor_id']);
        $this->assertEquals(1000000, $db_result['reciever_sensor_id']);
        $this->assertEquals(70003762, $db_result['sender_worker_id']);
        $this->assertEquals(1, $db_result['reciever_worker_id']);
        $this->assertEquals('surface', $db_result['sender_network_id']);
        $this->assertEquals(12, $db_result['reciever_network_id']);
        $this->assertEquals('Unit Test Alarm', $db_result['message']);
        $this->assertEquals(StatusEnumController::MSG_SENDED, $db_result['status_id']);
        $this->assertGreaterThanOrEqual(200, $db_result['message_id']);
        $this->assertEquals('alarm', $db_result['message_type']);
        unset($db_result);

        // 2) Сообщение в кэше
        $cache_result = Yii::$app->redis_service->lrange('packages', 0, 5)[0];
        $this->assertJson($cache_result, print_r($cache_result, true));
        $cache_result = json_decode($cache_result, true);
        $this->assertEquals(12, $cache_result['network_id']);
        $this->assertGreaterThanOrEqual(200, $cache_result['message_id']);
        $this->assertEquals('alarm', $cache_result['type']);
        unset($cache_result);

        // 3) Параметры воркера БД
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM `view_worker_parameters_last_values` where worker_id = 1'
        )->queryAll();
        $db_result = \yii\helpers\ArrayHelper::map($db_result, 'parameter_type_id', 'value', 'parameter_id');
        $this->assertEquals(StatusEnumController::MSG_SENDED, $db_result[ParameterEnumController::TEXT_MSG_FLAG][ParameterTypeEnumController::MEASURED]);
        $this->assertEquals(StatusEnumController::ALARM_SENDED, $db_result[ParameterEnumController::ALARM_SIGNAL_FLAG][ParameterTypeEnumController::MEASURED]);
        unset($db_result);

        // 4) Параметры воркера кэш
        $cache_parameters = (new \backend\controllers\cachemanagers\WorkerCacheController())->multiGetParameterValue(1, '*', '*');
        foreach ($cache_parameters as $cache_parameter) {
            $cache_result[$cache_parameter['parameter_id']][$cache_parameter['parameter_type_id']] = $cache_parameter['value'];
        }
        $this->assertEquals(StatusEnumController::MSG_SENDED, $cache_result[ParameterEnumController::TEXT_MSG_FLAG][ParameterTypeEnumController::MEASURED]);
        $this->assertEquals(StatusEnumController::ALARM_SENDED, $cache_result[ParameterEnumController::ALARM_SIGNAL_FLAG][ParameterTypeEnumController::MEASURED]);
        unset($cache_result);
    }

    /**
     * Тестируем: StrataJobController::SaveMessageAck
     * По сценарию:
     *   Подтверждение доставки аварийного сообщения
     * Ожидаем:
     *   1) Изменение статуса сообщения в таблице text_message
     *   2) Сохранение параметров воркера в БД
     *   3) Сохранение параметров воркера в кэш
     */
    public function testSaveMessageAck()
    {
        /**
         * Создание светильника Unit Test Lamp:
         *   id: 1000000
         *   object_id: 47
         *   net_id: 12
         *   mine_id: 290
         */
        Yii::$app->db->createCommand()  // Объект метки
        ->insert('sensor', [
            'id' => 1000000,
            'title' => 'Unit Test Lamp',
            'sensor_type_id' => 1,
            'asmtp_id' => 1,
            'object_id' => 47
        ])->execute();
        Yii::$app->db->createCommand()  // Сетевой идентификатор
        ->insert('sensor_parameter', [
            'id' => 1100000,
            'sensor_id' => 1000000,
            'parameter_id' => 88,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Тип объекта
        ->insert('sensor_parameter', [
            'id' => 1200000,
            'sensor_id' => 1000000,
            'parameter_id' => 274,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Идентификатор шахты
        ->insert('sensor_parameter', [
            'id' => 1300000,
            'sensor_id' => 1000000,
            'parameter_id' => 346,
            'parameter_type_id' => 2
        ])->execute();
        Yii::$app->db->createCommand()  // Значение сетевого идентификатора
        ->insert('sensor_parameter_handbook_value', [
            'id' => 1110000,
            'sensor_parameter_id' => 1100000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '12',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение типа объекта
        ->insert('sensor_parameter_handbook_value', [
            'id' => 1210000,
            'sensor_parameter_id' => 1200000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '47',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение идентификатора шахты
        ->insert('sensor_parameter_handbook_value', [
            'id' => 1310000,
            'sensor_parameter_id' => 1300000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '290',
            'status_id' => 1
        ])->execute();

        /**
         * Создание воркера, привязанного к сенсору
         */
        Yii::$app->db->createCommand()
            ->insert('worker', [
                'id' => 1,
                'employee_id' => 901,
                'position_id' => 1,
                'company_department_id' => '801',
                'tabel_number' => 'TestWorker',
                'date_start' => '2019-08-07'
            ])->execute();
        Yii::$app->db->createCommand()
            ->insert('worker_object', [
                'id' => 1,
                'worker_id' => 1,
                'object_id' => 25,
                'role_id' => 1
            ])->execute();
        Yii::$app->db->createCommand()
            ->insert('worker_parameter', [
                'id' => 50000,
                'worker_object_id' => 1,
                'parameter_id' => 83,
                'parameter_type_id' => 2
            ])->execute();
        Yii::$app->db->createCommand()
            ->insert('worker_parameter_sensor', [
                'id' => 10000,
                'worker_parameter_id' => 50000,
                'sensor_id' => 1000000,
                'date_time' => date('Y-m-d H:i:s.u'),
                'type_relation_sensor' => 1
            ])->execute();

        Yii::$app->db->createCommand()->insert('text_message', [
            'reciever_sensor_id' => 1000000,
            'reciever_worker_id' => 1,
            'sender_network_id' => 'surface',
            'reciever_network_id' => 12,
            'message' => 'Unit Test Msg',
            'status_id' => StatusEnumController::MSG_SENDED,
            'message_id' => 205,
            'datetime' => '2019-08-20 16:44:00',
            'message_type' => 'alarm'
        ])->execute();

        $result = StrataJobController::SaveMessageAck('2019-08-20 16:44:30', 205, 12);
        $this->assertEquals(1, $result['status'], print_r($result, true));

        // 1) Сообщение в БД
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM text_message where reciever_worker_id  = 1'
        )->queryOne();
        $this->assertNull($db_result['sender_sensor_id']);
        $this->assertEquals(1000000, $db_result['reciever_sensor_id']);
        //$this->assertEquals(70003762, $db_result['sender_worker_id']);
        $this->assertNull($db_result['sender_worker_id']);
        $this->assertEquals(1, $db_result['reciever_worker_id']);
        $this->assertEquals('surface', $db_result['sender_network_id']);
        $this->assertEquals(12, $db_result['reciever_network_id']);
        $this->assertEquals('Unit Test Msg', $db_result['message']);
        $this->assertEquals(StatusEnumController::MSG_DELIVERED, $db_result['status_id']);
        $this->assertEquals(205, $db_result['message_id']);
        $this->assertEquals('alarm', $db_result['message_type']);
        unset($db_result);

        // 2) Параметры воркера БД
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM `view_worker_parameters_last_values` where worker_id = 1'
        )->queryAll();
        $db_result = \yii\helpers\ArrayHelper::map($db_result, 'parameter_type_id', 'value', 'parameter_id');
        $this->assertEquals(StatusEnumController::MSG_DELIVERED, $db_result[ParameterEnumController::TEXT_MSG_FLAG][ParameterTypeEnumController::MEASURED]);
        $this->assertEquals(StatusEnumController::ALARM_DELIVERED, $db_result[ParameterEnumController::ALARM_SIGNAL_FLAG][ParameterTypeEnumController::MEASURED]);
        unset($db_result);

        // 3) Параметры воркера кэш
        $cache_parameters = (new \backend\controllers\cachemanagers\WorkerCacheController())->multiGetParameterValue(1, '*', '*');
        foreach ($cache_parameters as $cache_parameter) {
            $cache_result[$cache_parameter['parameter_id']][$cache_parameter['parameter_type_id']] = $cache_parameter['value'];
        }
        $this->assertEquals(StatusEnumController::MSG_DELIVERED, $cache_result[ParameterEnumController::TEXT_MSG_FLAG][ParameterTypeEnumController::MEASURED]);
        $this->assertEquals(StatusEnumController::ALARM_DELIVERED, $cache_result[ParameterEnumController::ALARM_SIGNAL_FLAG][ParameterTypeEnumController::MEASURED]);
        unset($cache_result);
    }

    /**
     * Тестируем: StrataJobController::SaveMessageRead
     * По сценарию:
     *   Подтверждение прочтения аварийного сообщения
     * Ожидаем:
     *   1) Изменение статуса сообщения в таблице text_message
     *   2) Сохранение параметров воркера в БД
     *   3) Сохранение параметров воркера в кэш
     */
    public function testSaveMessageRead()
    {
        /**
         * Создание светильника Unit Test Lamp:
         *   id: 1000000
         *   object_id: 47
         *   net_id: 12
         *   mine_id: 290
         */
        Yii::$app->db->createCommand()  // Объект метки
        ->insert('sensor', [
            'id' => 1000000,
            'title' => 'Unit Test Lamp',
            'sensor_type_id' => 1,
            'asmtp_id' => 1,
            'object_id' => 47
        ])->execute();
        Yii::$app->db->createCommand()  // Сетевой идентификатор
        ->insert('sensor_parameter', [
            'id' => 1100000,
            'sensor_id' => 1000000,
            'parameter_id' => 88,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Тип объекта
        ->insert('sensor_parameter', [
            'id' => 1200000,
            'sensor_id' => 1000000,
            'parameter_id' => 274,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Идентификатор шахты
        ->insert('sensor_parameter', [
            'id' => 1300000,
            'sensor_id' => 1000000,
            'parameter_id' => 346,
            'parameter_type_id' => 2
        ])->execute();
        Yii::$app->db->createCommand()  // Значение сетевого идентификатора
        ->insert('sensor_parameter_handbook_value', [
            'id' => 1110000,
            'sensor_parameter_id' => 1100000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '12',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение типа объекта
        ->insert('sensor_parameter_handbook_value', [
            'id' => 1210000,
            'sensor_parameter_id' => 1200000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '47',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение идентификатора шахты
        ->insert('sensor_parameter_handbook_value', [
            'id' => 1310000,
            'sensor_parameter_id' => 1300000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '290',
            'status_id' => 1
        ])->execute();

        /**
         * Создание воркера, привязанного к сенсору
         */
        Yii::$app->db->createCommand()
            ->insert('worker', [
                'id' => 1,
                'employee_id' => 901,
                'position_id' => 1,
                'company_department_id' => '801',
                'tabel_number' => 'TestWorker',
                'date_start' => '2019-08-07'
            ])->execute();
        Yii::$app->db->createCommand()
            ->insert('worker_object', [
                'id' => 1,
                'worker_id' => 1,
                'object_id' => 25,
                'role_id' => 1
            ])->execute();
        Yii::$app->db->createCommand()
            ->insert('worker_parameter', [
                'id' => 50000,
                'worker_object_id' => 1,
                'parameter_id' => 83,
                'parameter_type_id' => 2
            ])->execute();
        Yii::$app->db->createCommand()
            ->insert('worker_parameter_sensor', [
                'id' => 10000,
                'worker_parameter_id' => 50000,
                'sensor_id' => 1000000,
                'date_time' => date('Y-m-d H:i:s.u'),
                'type_relation_sensor' => 1
            ])->execute();

        Yii::$app->db->createCommand()->insert('text_message', [
            'reciever_sensor_id' => 1000000,
            'reciever_worker_id' => 1,
            'sender_network_id' => 'surface',
            'reciever_network_id' => 12,
            'message' => 'Unit Test Msg',
            'status_id' => StatusEnumController::MSG_DELIVERED,
            'message_id' => 205,
            'datetime' => '2019-08-20 16:44:30',
            'message_type' => 'alarm'
        ])->execute();

        $result = StrataJobController::SaveMessageRead('2019-08-20 16:45:00', 205, 12);
        $this->assertEquals(1, $result['status'], print_r($result, true));

        // 1) Сообщение в БД
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM text_message where reciever_worker_id  = 1'
        )->queryOne();
        $this->assertNull($db_result['sender_sensor_id']);
        $this->assertEquals(1000000, $db_result['reciever_sensor_id']);
        //$this->assertEquals(70003762, $db_result['sender_worker_id']);
        $this->assertNull($db_result['sender_worker_id']);
        $this->assertEquals(1, $db_result['reciever_worker_id']);
        $this->assertEquals('surface', $db_result['sender_network_id']);
        $this->assertEquals(12, $db_result['reciever_network_id']);
        $this->assertEquals('Unit Test Msg', $db_result['message']);
        $this->assertEquals(StatusEnumController::MSG_READED, $db_result['status_id']);
        $this->assertEquals(205, $db_result['message_id']);
        $this->assertEquals('alarm', $db_result['message_type']);
        unset($db_result);

        // 2) Параметры воркера БД
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM `view_worker_parameters_last_values` where worker_id = 1'
        )->queryAll();
        $db_result = \yii\helpers\ArrayHelper::map($db_result, 'parameter_type_id', 'value', 'parameter_id');
        $this->assertEquals(StatusEnumController::MSG_READED, $db_result[ParameterEnumController::TEXT_MSG_FLAG][ParameterTypeEnumController::MEASURED]);
        $this->assertEquals(StatusEnumController::ALARM_READED, $db_result[ParameterEnumController::ALARM_SIGNAL_FLAG][ParameterTypeEnumController::MEASURED]);
        unset($db_result);

        // 3) Параметры воркера кэш
        $cache_parameters = (new \backend\controllers\cachemanagers\WorkerCacheController())->multiGetParameterValue(1, '*', '*');
        foreach ($cache_parameters as $cache_parameter) {
            $cache_result[$cache_parameter['parameter_id']][$cache_parameter['parameter_type_id']] = $cache_parameter['value'];
        }
        $this->assertEquals(StatusEnumController::MSG_READED, $cache_result[ParameterEnumController::TEXT_MSG_FLAG][ParameterTypeEnumController::MEASURED]);
        $this->assertEquals(StatusEnumController::ALARM_READED, $cache_result[ParameterEnumController::ALARM_SIGNAL_FLAG][ParameterTypeEnumController::MEASURED]);
        unset($cache_result);
    }
}
