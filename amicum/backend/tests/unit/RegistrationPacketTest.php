<?php

use backend\controllers\MinerNodeCheckInOut;
use backend\controllers\StrataJobController;

class RegistrationPacketTest extends \Codeception\Test\Unit
{
    public function _before()
    {
        /**
         * Создание шлюза, с которого "придёт" пакет
         */
        Yii::$app->db->createCommand()  // Объект метки
        ->insert('sensor', [
            'id' => 2000000,
            'title' => 'Unit Test Gateway',
            'sensor_type_id' => 1,
            'asmtp_id' => 1,
            'object_id' => 90
        ])->execute();
        Yii::$app->db->createCommand()  // Тип объекта
        ->insert('sensor_parameter', [
            'id' => 2100000,
            'sensor_id' => 2000000,
            'parameter_id' => 274,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение типа объекта
        ->insert('sensor_parameter_handbook_value', [
            'id' => 2110000,
            'sensor_parameter_id' => 2100000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '90',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Координаты
        ->insert('sensor_parameter', [
            'id' => 2200000,
            'sensor_id' => 2000000,
            'parameter_id' => 83,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение координат
        ->insert('sensor_parameter_handbook_value', [
            'id' => 2210000,
            'sensor_parameter_id' => 2200000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '15.000,16.000,17.000',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Плейс
        ->insert('sensor_parameter', [
            'id' => 2300000,
            'sensor_id' => 2000000,
            'parameter_id' => 122,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение плейса
        ->insert('sensor_parameter_handbook_value', [
            'id' => 2310000,
            'sensor_parameter_id' => 2300000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '5',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Эдж
        ->insert('sensor_parameter', [
            'id' => 2400000,
            'sensor_id' => 2000000,
            'parameter_id' => 269,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение эджа
        ->insert('sensor_parameter_handbook_value', [
            'id' => 2410000,
            'sensor_parameter_id' => 2400000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '8',
            'status_id' => 1
        ])->execute();

        /**
         * Создание строки подключения к шлюзу
         */
        Yii::$app->db->createCommand()
        ->insert('connect_string', [
            'id' => 1,
            'title' => 'Unit Test Connect String',
            'ip' => '0.0.0.1',
            'connect_string' => 'port=2101',
            'Settings_DCS_id' => 1380,
            'source_type' => 'Strata'
        ])->execute();
        Yii::$app->db->createCommand()
            ->insert('sensor_connect_string', [
                'id' => 1,
                'sensor_id' => 2000000,
                'connect_string_id' => 1,
                'date_time' => date('Y-m-d H:i:s.u')
            ])->execute();

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
        Yii::$app->db->createCommand()  // Значение сетевого идентификатора
        ->insert('sensor_parameter_handbook_value', [
            'id' => 1110000,
            'sensor_parameter_id' => 1100000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '12',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Тип объекта
        ->insert('sensor_parameter', [
            'id' => 1200000,
            'sensor_id' => 1000000,
            'parameter_id' => 274,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение типа объекта
        ->insert('sensor_parameter_handbook_value', [
            'id' => 1210000,
            'sensor_parameter_id' => 1200000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '47',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Идентификатор шахты
        ->insert('sensor_parameter', [
            'id' => 1300000,
            'sensor_id' => 1000000,
            'parameter_id' => 346,
            'parameter_type_id' => 2
        ])->execute();
        Yii::$app->db->createCommand()  // Значение идентификатора шахты
        ->insert('sensor_parameter_value', [
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

        (new \backend\controllers\cachemanagers\SensorCacheController())->initSensorParameterHandbookValue(1000000);
        (new \backend\controllers\cachemanagers\SensorCacheController())->initSensorParameterHandbookValue(2000000);
    }

    public function _after()
    {
        // Очистка кэша сенсоров
        $keys = Yii::$app->redis_sensor->scan(0, 'MATCH', '*1000000*', 'COUNT', '10000000')[1];
        $keys = array_merge($keys, Yii::$app->redis_sensor->scan(0, 'MATCH', '*2000000*', 'COUNT', '10000000')[1]);
        foreach ($keys as $key) {
            Yii::$app->redis_sensor->del($key);
        }

        Yii::$app->redis_service->del('Gate:0.0.0.1');

        // Очистка кэша воркеров
        $keys = Yii::$app->redis_worker->scan(0, 'MATCH', 'WoMi:290:1', 'COUNT', '10000000')[1];
        $keys = array_merge($keys, Yii::$app->redis_worker->scan(0, 'MATCH', 'WoPa:1:*', 'COUNT', '10000000')[1]);
        foreach ($keys as $key) {
            Yii::$app->redis_worker->del($key);
        }
    }

    /**
     * Тестируем: StrataJobController::saveRegistrationPacket
     *
     * По сценарию: пришёл пакет регистрации. Все значения нормальные. Шлюз, от
     * которого получен пакет стоит на схеме (есть значения параметров 83, 122, 269).
     * К светильнику привязан воркер.
     *
     * Ожидаем:
     *   1) Запись значений параметров сенсора в БД
     *   2) Запись значений параметров сенсора в кэш
     *   3) Генерация нормальных событий в БД
     *   4) Генерация нормальных событий в кэше
     *   5) Запись значений параметров воркера в БД
     *   6) Запись значений параметров воркера в кэш
     *   7) Наличие воркера в кэше зачекиненных
     *   Значения параметров координат, эджа и плейса берутся от шлюза, с
     *   которого пришёл пакет.
     *   Процент заряда батареи светильника = 100%
     */
    public function testSaveRegistrationPacketCheckin()
    {
        $packet_object = (object) [
            'timestamp' => date('Y-m-d H:i:s.u'),
            'sequenceNumber' => '1',
            'batteryVoltage' => '4.100',
            'sourceNode' => '12',
            'checkIn' => '1'
        ];
        $mine_id = 290;
        $ip_addr = '0.0.0.1';

        $result = StrataJobController::saveRegistrationPacket(new MinerNodeCheckInOut($packet_object), $mine_id, $ip_addr);
        $this->assertEquals(1, $result['status'], print_r($result,true));

        // 1) Параметры сенсора БД
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM `view_sensor_parameter_value_only_main` where sensor_id = 1000000'
        )->queryAll();
        //fwrite(STDOUT, print_r($db_result, true));
        $db_result = \yii\helpers\ArrayHelper::map($db_result, 'parameter_type_id', 'value', 'parameter_id');
        $this->assertEquals(1, $db_result[164][3], 'Состояние');
        $this->assertEquals('4.100', $db_result[95][2], 'Напряжение батареи');
        $this->assertEquals('5', $db_result[122][2], 'Плейс');
        $this->assertEquals('15.000,16.000,17.000', $db_result[83][2], 'Координаты');
        $this->assertEquals('8', $db_result[269][2], 'Эдж');
        $this->assertEquals('1', $db_result[158][2], 'Статус спуска');
        $this->assertEquals('100', $db_result[448][2], 'Процент заряда батареи');
        unset($db_result);

        // 2) Параметры сенсора кэш
        $sensor_parameter_value_list_cache = (new \backend\controllers\cachemanagers\SensorCacheController())->multiGetParameterValue(1000000, '*', '*');
        foreach ($sensor_parameter_value_list_cache as $sensor_parameter_value_cache) {
            $cache_result[$sensor_parameter_value_cache['parameter_id']][$sensor_parameter_value_cache['parameter_type_id']] = $sensor_parameter_value_cache['value'];
        }
        $this->assertEquals(1, $cache_result[164][3], 'Состояние');
        $this->assertEquals('4.100', $cache_result[95][2], 'Напряжение батареи');
        $this->assertEquals('5', $cache_result[122][2], 'Плейс');
        $this->assertEquals('15.000,16.000,17.000', $cache_result[83][2], 'Координаты');
        $this->assertEquals('8', $cache_result[269][2], 'Эдж');
        $this->assertEquals('1', $cache_result[158][2], 'Статус спуска');
        $this->assertEquals('100', $cache_result[448][2], 'Процент заряда батареи');
        unset($cache_result);

        // 3) События БД
        $query = Yii::$app->db->createCommand(
            'SELECT * FROM `view_event_journal` where main_id = 1000000'
        )->queryAll();
        $event_db_result = array();
        foreach ($query as $event) {
            $event_db_result[$event['event_id']] = $event;
        }
        $this->assertArrayHasKey(EventEnumController::LAMP_LOW_BATTERY, $event_db_result, 'Проверка события "Низкий заряд батареи светильника"');
        $this->assertEquals(100, $event_db_result[EventEnumController::LAMP_LOW_BATTERY]['parameter_value']);
        $this->assertEquals(52, $event_db_result[EventEnumController::LAMP_LOW_BATTERY]['status_id']);
        $this->assertEquals(45, $event_db_result[EventEnumController::LAMP_LOW_BATTERY]['event_type_id']);

        // 4) События кэш
        $event_cache_result = (new \backend\controllers\cachemanagers\EventCacheController())->getEvent($mine_id, 7137, 1000000)['Items'];
        $this->assertEquals(100, $event_cache_result['value']);
        $this->assertEquals(52, $event_cache_result['event_status_id']);
        $this->assertEquals(45, $event_cache_result['value_status_id']);

        // 5) Параметры воркера БД
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM `view_worker_parameters_last_values` where worker_id = 1'
        )->queryAll();
        $db_result = \yii\helpers\ArrayHelper::map($db_result, 'parameter_type_id', 'value', 'parameter_id');
        $this->assertEquals('1', $db_result[158][2], 'Статус спуска');
        $this->assertEquals('5', $db_result[122][2], 'Плейс');
        $this->assertEquals('15.000,16.000,17.000', $db_result[83][2], 'Координаты');
        $this->assertEquals('8', $db_result[269][2], 'Эдж');
        $this->assertEquals('290', $db_result[346][2], 'Идентификатор шахты');

        // 6) Параметры воркера кэш
        $cache_parameters = (new \backend\controllers\cachemanagers\WorkerCacheController())->multiGetParameterValue(1, '*', '*');
        foreach ($cache_parameters as $cache_parameter) {
            $cache_result[$cache_parameter['parameter_id']][$cache_parameter['parameter_type_id']] = $cache_parameter['value'];
        }
        $this->assertEquals('1', $cache_result[158][2], 'Статус спуска');
        $this->assertEquals('5', $cache_result[122][2], 'Плейс');
        $this->assertEquals('15.000,16.000,17.000', $cache_result[83][2], 'Координаты');
        $this->assertEquals('8', $cache_result[269][2], 'Эдж');
        $this->assertEquals('290', $cache_result[346][2], 'Идентификатор шахты');

        // 7) Проверка наличия воркера в кэше зачекиненных
        $checkin_result = (new \backend\controllers\cachemanagers\WorkerCacheController())->getWorkerMineByWorkerOne($mine_id, 1);
        $this->assertNotFalse($checkin_result);
    }

    /**
     * Тестируем: StrataJobController::saveRegistrationPacket
     *
     * По сценарию: пришёл пакет разрегистрации. Все значения нормальные. Шлюз, от
     * которого получен пакет стоит на схеме (есть значения параметров 83, 122, 269).
     * К светильнику привязан воркер.
     *
     * Ожидаем:
     *   1) Запись значений параметров сенсора в БД
     *   2) Запись значений параметров сенсора в кэш
     *   3) Генерация нормальных событий в БД
     *   4) Генерация нормальных событий в кэше
     *   5) Запись значений параметров воркера в БД
     *   6) Запись значений параметров воркера в кэш
     *   7) Отсутствие воркера в кэше зачекиненных
     *   8) Отсутствие сенсора в кэше шахты
     *   9) Запись выхода воркера в отчётную таблицу
     *   Значения параметров координат, эджа и плейса берутся от шлюза, с
     *   которого пришёл пакет.
     *   Процент заряда батареи светильника = 100%
     */
    public function testSaveRegistrationPacketCheckout()
    {
        $mine_id = 290;
        $ip_addr = '0.0.0.1';

        // Имитация чекина (setUp)
        $packet_object = (object) [
            'timestamp' => '2019-08-07 16:00:00.000',
            'sequenceNumber' => '1',
            'batteryVoltage' => '4.100',
            'sourceNode' => '12',
            'checkIn' => '1'
        ];
        StrataJobController::saveRegistrationPacket(new MinerNodeCheckInOut($packet_object), $mine_id, $ip_addr);

        // Тест
        $packet_object = (object) [
            'timestamp' => '2019-08-07 17:00:00.000',
            'sequenceNumber' => '2',
            'batteryVoltage' => '4.100',
            'sourceNode' => '12',
            'checkIn' => '0'
        ];

        $result = StrataJobController::saveRegistrationPacket(new MinerNodeCheckInOut($packet_object), $mine_id, $ip_addr);
        $this->assertEquals(1, $result['status'], print_r($result,true));

        // 1) Параметры сенсора БД
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM `view_sensor_parameter_value_only_main` where sensor_id = 1000000'
        )->queryAll();
        //fwrite(STDOUT, print_r($db_result, true));
        $db_result = \yii\helpers\ArrayHelper::map($db_result, 'parameter_type_id', 'value', 'parameter_id');
        $this->assertEquals(0, $db_result[164][3], 'Состояние');
        $this->assertEquals('4.100', $db_result[95][2], 'Напряжение батареи');
        $this->assertEquals('5', $db_result[122][2], 'Плейс');
        $this->assertEquals('15.000,16.000,17.000', $db_result[83][2], 'Координаты');
        $this->assertEquals('8', $db_result[269][2], 'Эдж');
        $this->assertEquals('0', $db_result[158][2], 'Статус спуска');
        $this->assertEquals('100', $db_result[448][2], 'Процент заряда батареи');

        // 2) Параметры сенсора кэш
        $sensor_parameter_value_list_cache = (new \backend\controllers\cachemanagers\SensorCacheController())->multiGetParameterValue(1000000, '*', '*');
        foreach ($sensor_parameter_value_list_cache as $sensor_parameter_value_cache) {
            $cache_result[$sensor_parameter_value_cache['parameter_id']][$sensor_parameter_value_cache['parameter_type_id']] = $sensor_parameter_value_cache['value'];
        }
        $this->assertEquals(0, $cache_result[164][3], 'Состояние');
        $this->assertEquals('4.100', $cache_result[95][2], 'Напряжение батареи');
        $this->assertEquals('5', $cache_result[122][2], 'Плейс');
        $this->assertEquals('15.000,16.000,17.000', $cache_result[83][2], 'Координаты');
        $this->assertEquals('8', $cache_result[269][2], 'Эдж');
        $this->assertEquals('0', $cache_result[158][2], 'Статус спуска');
        $this->assertEquals('100', $cache_result[448][2], 'Процент заряда батареи');

        unset($cache_result);

        // 3) События БД
        $query = Yii::$app->db->createCommand(
            'SELECT * FROM `view_event_journal` where main_id = 1000000'
        )->queryAll();
        $event_db_result = array();
        foreach ($query as $event) {
            $event_db_result[$event['event_id']] = $event;
        }
        $this->assertArrayHasKey(EventEnumController::LAMP_LOW_BATTERY, $event_db_result, 'Проверка события "Низкий заряд батареи светильника"');
        $this->assertEquals(100, $event_db_result[EventEnumController::LAMP_LOW_BATTERY]['parameter_value']);
        $this->assertEquals(52, $event_db_result[EventEnumController::LAMP_LOW_BATTERY]['status_id']);
        $this->assertEquals(45, $event_db_result[EventEnumController::LAMP_LOW_BATTERY]['event_type_id']);

        // 4) События кэш
        $event_cache_result = (new \backend\controllers\cachemanagers\EventCacheController())->getEvent($mine_id, 7137, 1000000)['Items'];
        $this->assertEquals(100, $event_cache_result['value']);
        $this->assertEquals(52, $event_cache_result['event_status_id']);
        $this->assertEquals(45, $event_cache_result['value_status_id']);

        // 5) Параметры воркера БД
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM `view_worker_parameters_last_values` where worker_id = 1'
        )->queryAll();
        $db_result = \yii\helpers\ArrayHelper::map($db_result, 'parameter_type_id', 'value', 'parameter_id');
        $this->assertEquals('0', $db_result[158][2], 'Статус спуска');
        $this->assertEquals('5', $db_result[122][2], 'Плейс');
        $this->assertEquals('15.000,16.000,17.000', $db_result[83][2], 'Координаты');
        $this->assertEquals('8', $db_result[269][2], 'Эдж');
        $this->assertEquals('290', $db_result[346][2], 'Идентификатор шахты');

        // 6) Параметры воркера кэш
        $cache_parameters = (new \backend\controllers\cachemanagers\WorkerCacheController())->multiGetParameterValue(1, '*', '*');
        foreach ($cache_parameters as $cache_parameter) {
            $cache_result[$cache_parameter['parameter_id']][$cache_parameter['parameter_type_id']] = $cache_parameter['value'];
        }
        $this->assertEquals('0', $cache_result[158][2], 'Статус спуска');
        $this->assertEquals('5', $cache_result[122][2], 'Плейс');
        $this->assertEquals('15.000,16.000,17.000', $cache_result[83][2], 'Координаты');
        $this->assertEquals('8', $cache_result[269][2], 'Эдж');
        $this->assertEquals('290', $cache_result[346][2], 'Идентификатор шахты');

        // 7) Проверка отсутствия воркера в кэше зачекиненных
        $checkin_result = (new \backend\controllers\cachemanagers\WorkerCacheController())->getWorkerMineByWorkerOne($mine_id, 1);
        $this->assertFalse($checkin_result, 'Наличие в кэше зачекиненных');

        // 8) Проверка отсутствия сенсора в кэше шахты
        $sensor_checkout_result = (new \backend\controllers\cachemanagers\SensorCacheController())->getSensorMineBySensorOne($mine_id, 1000000);
        $this->assertFalse($sensor_checkout_result, 'Наличие в кэше сенсоров');

        // 9) Запись выхода воркера в отчётную таблицу
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM `summary_report_end_of_shift` where worker_id = 1'
        )->queryAll();
        $db_result = $db_result[0];
        $this->assertEquals('2019-08-07', $db_result['date_work'], 'Дата смены');
        $this->assertEquals('2019-08-07 17:00:00', $db_result['date_time'], 'Время выхода');
        $this->assertEquals('Frontend Developer Outstanding', $db_result['FIO'], 'ФИО');
        $this->assertEquals('1', $db_result['worker_object_id'], 'worker_object_id');
        $this->assertEquals('Прочее', $db_result['department_title'], 'department_title');
        $this->assertEquals('Прочее', $db_result['company_title'], 'company_title');
        $this->assertEquals('TestWorker', $db_result['tabel_number'], 'tabel_number');
        $this->assertEquals('2', $db_result['smena'], 'smena');
        $this->assertEquals('1', $db_result['worker_id'], 'worker_id');
        $this->assertEquals('1', $db_result['department_id'], 'department_id');
        $this->assertEquals('101', $db_result['company_id'], 'company_id');
    }
}
