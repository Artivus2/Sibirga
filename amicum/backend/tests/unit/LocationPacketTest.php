<?php

use backend\controllers\StrataJobController;

class LocationPacketTest extends \Codeception\Test\Unit
{
    // Подготовка тестового окружения (setUp)
    // Оказалось, что работа с базой таким образом не влияет на данные в ней.
    // Т.е. созданные записи не остаются, удаление не происходит.
    // При записи в кэш, все данные остаются в нём
    public function _before()
    {
        /**
         * Создание узла связи Unit Test C:
         *   id: 900000
         *   object_id: 46
         *   net_id: 1
         *   xyz: '15.000,15.000,15.000'
         *   place: 2
         *   edge: 3
         */
        $c_fix_net_id = '1';
        Yii::$app->db->createCommand()  // Объект узла связи
        ->insert('sensor', [
            'id' => 900000,
            'title' => 'Unit Test C',
            'sensor_type_id' => 1,
            'asmtp_id' => 1,
            'object_id' => 46
        ])->execute();
        Yii::$app->db->createCommand()  // Сетевой идентификатор
        ->insert('sensor_parameter', [
            'id' => 910000,
            'sensor_id' => 900000,
            'parameter_id' => 88,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение сетевого идентификатора
        ->insert('sensor_parameter_handbook_value', [
            'id' => 911000,
            'sensor_parameter_id' => 910000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => $c_fix_net_id,
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Координаты
        ->insert('sensor_parameter', [
            'id' => 920000,
            'sensor_id' => 900000,
            'parameter_id' => 83,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение координат
        ->insert('sensor_parameter_handbook_value', [
            'id' => 921000,
            'sensor_parameter_id' => 920000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '15.000,15.000,15.000',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Эдж
        ->insert('sensor_parameter', [
            'id' => 930000,
            'sensor_id' => 900000,
            'parameter_id' => 269,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение эджа
        ->insert('sensor_parameter_handbook_value', [
            'id' => 931000,
            'sensor_parameter_id' => 930000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '2',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Плейс
        ->insert('sensor_parameter', [
            'id' => 940000,
            'sensor_id' => 900000,
            'parameter_id' => 122,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение плейса
        ->insert('sensor_parameter_handbook_value', [
            'id' => 941000,
            'sensor_parameter_id' => 940000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '3',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Идентификатор шахты
        ->insert('sensor_parameter', [
            'id' => 950000,
            'sensor_id' => 900000,
            'parameter_id' => 346,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение идентификатора шахты
        ->insert('sensor_parameter_handbook_value', [
            'id' => 951000,
            'sensor_parameter_id' => 950000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '270',
            'status_id' => 1
        ])->execute();

        /**
         * Создание узла связи Unit Test C-2:
         *   id: 800000
         *   object_id: 46
         *   net_id: 2
         *   xyz: '45.000,45.000,45.000'
         *   place: 2
         *   edge: 3
         */
        Yii::$app->db->createCommand()  // Объект узла связи
        ->insert('sensor', [
            'id' => 800000,
            'title' => 'Unit Test C-2',
            'sensor_type_id' => 1,
            'asmtp_id' => 1,
            'object_id' => 46
        ])->execute();
        Yii::$app->db->createCommand()  // Сетевой идентификатор
        ->insert('sensor_parameter', [
            'id' => 810000,
            'sensor_id' => 800000,
            'parameter_id' => 88,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение сетевого идентификатора
        ->insert('sensor_parameter_handbook_value', [
            'id' => 811000,
            'sensor_parameter_id' => 810000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '2',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Координаты
        ->insert('sensor_parameter', [
            'id' => 820000,
            'sensor_id' => 800000,
            'parameter_id' => 83,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение координат
        ->insert('sensor_parameter_handbook_value', [
            'id' => 821000,
            'sensor_parameter_id' => 820000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '45.000,45.000,45.000',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Эдж
        ->insert('sensor_parameter', [
            'id' => 830000,
            'sensor_id' => 800000,
            'parameter_id' => 269,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение эджа
        ->insert('sensor_parameter_handbook_value', [
            'id' => 831000,
            'sensor_parameter_id' => 830000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '2',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Плейс
        ->insert('sensor_parameter', [
            'id' => 840000,
            'sensor_id' => 800000,
            'parameter_id' => 122,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение плейса
        ->insert('sensor_parameter_handbook_value', [
            'id' => 841000,
            'sensor_parameter_id' => 840000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '3',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Идентификатор шахты
        ->insert('sensor_parameter', [
            'id' => 850000,
            'sensor_id' => 800000,
            'parameter_id' => 346,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение идентификатора шахты
        ->insert('sensor_parameter_handbook_value', [
            'id' => 851000,
            'sensor_parameter_id' => 850000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '270',
            'status_id' => 1
        ])->execute();

        /**
         * Создание светильника Unit Test Lamp:
         *   id: 1000000
         *   object_id: 47
         *   net_id: 12
         *   mine_id: 270
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
            'value' => '270',
            'status_id' => 1
        ])->execute();

        /**
         * Создание плейса
         */
        Yii::$app->db->createCommand()  // Значение идентификатора шахты
        ->insert('place', [
            'id' => 3,
            'title' => 'Unit Place',
            'mine_id' => 270,
            'object_id' => 10,
            'plast_id' => 2108
        ])->execute();

        /**
         * Создание эджа в кэше
         */
        $key = 'EdSch:270:2';
        $edge_info = serialize(array([
            'edge_id' => 2,
            'place_id' => 3,
            'place_title' => 'Unit Place Test',
            'conjunction_start_id' => 4,
            'conjunction_end_id' => 5,
            'xStart' => '0.000',
            'yStart' => '0.000',
            'zStart' => '0.000',
            'xEnd' => '50.000',
            'yEnd' => '50.000',
            'zEnd' => '50.000',
            'place_object_id' => 6,
            'danger_zona' => 0,
            'mine_id' => 270,
            'conveyor' => 0]
        ));
        $data[] = $key;
        $data[] = $edge_info;
        Yii::$app->redis_edge->executeCommand('set', $data);

        /**
         * Инициализация данных в кэше
         */
        (new \backend\controllers\cachemanagers\SensorCacheController())->initSensorParameterHandbookValue(800000);
        $result = (new \backend\controllers\cachemanagers\SensorCacheController())->initSensorMain(270, 800000);
        //fwrite(STDOUT, print_r($result, true));
        (new \backend\controllers\cachemanagers\SensorCacheController())->initSensorParameterHandbookValue(900000);
        (new \backend\controllers\cachemanagers\SensorCacheController())->initSensorMain(270, 900000);
        (new \backend\controllers\cachemanagers\SensorCacheController())->initSensorParameterHandbookValue(1000000);
        (new \backend\controllers\cachemanagers\ServiceCache())->initSensorNetwork();
    }

    // Очистка тестового окружения (tearDown)
    public function _after()
    {
        // Удаление кэша сенсоров
        $keys = Yii::$app->redis_sensor->scan(0, 'MATCH', '*900000*', 'COUNT', '10000000')[1];
        $keys = array_merge($keys, Yii::$app->redis_sensor->scan(0, 'MATCH', '*1000000*', 'COUNT', '10000000')[1]);
        $keys = array_merge($keys, Yii::$app->redis_sensor->scan(0, 'MATCH', '*800000*', 'COUNT', '10000000')[1]);
        foreach ($keys as $key) {
            Yii::$app->redis_sensor->del($key);
        }

        // Удаление кэша эджей
        Yii::$app->redis_edge->del('EdSch:270:2');

        // Очистка кэша воркеров
        $keys = Yii::$app->redis_worker->scan(0, 'MATCH', 'WoMi:270:1', 'COUNT', '10000000')[1];
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
        $keys = Yii::$app->redis_event->scan(0, 'MATCH', 'Ev:270:*:1', 'COUNT', '10000000')[1];
        if ($keys) {
            foreach ($keys as $key) {
                Yii::$app->redis_worker->del($key);
            }
        }

    }

    /**
     * Тестируем: StrataJobController::saveLocationPacket
     *
     * По сценарию: обработка пакета с одним услышанным узлом и без привязки к
     * воркеру или оборудованию. Все значения параметров являются нормальными.
     * На выработке нет конвейера.
     *
     * Ожидаем:
     *   1) Запись значений параметров в БД
     *   2) Запись значений параметров в кэш
     *   3) Генерация нормальных событий в БД
     *   4) Генерация нормальных событий в кэше
     *   При одном услышанном узле значения параметров координат, эджа и плейса
     *   берутся от данного узла связи, независимо от уровня сигнала.
     *   Процент заряда батареи = 100%
     */
    public function testSaveLocationPacketNoObjectsOneGateway()
    {
        $packet_object = (object) [
            'timestamp' => date('Y-m-d H:i:s.u'),
            'batteryVoltage' => '4.100',
            'networkId' => '12',
            'alarmFlag' => '0',
            'emergencyFlag' => '0',
            'surfaceFlag' => 'Underground',
            'movingFlag' => 'Moving',
            'nodes' => [
                (object)['address' => '1', 'rssi' => '-50']
            ]
        ];
        $mine_id = 270;
        $ip_addr = '127.0.0.1';

        $result = StrataJobController::saveLocationPacket(new \backend\controllers\MinerNodeLocation($packet_object), $mine_id, $ip_addr);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM `view_sensor_parameter_value_only_main` where sensor_id = 1000000'
        )->queryAll();
        //fwrite(STDOUT, print_r($db_result, true));
        $db_result = \yii\helpers\ArrayHelper::map($db_result, 'parameter_type_id', 'value', 'parameter_id');

        // 1) БД
        $this->assertEquals($packet_object->batteryVoltage, $db_result[95][2]);
        $this->assertEquals(1, $db_result[164][3]);
        $this->assertEquals('15.000,15.000,15.000', $db_result[83][2]);
        $this->assertEquals('3', $db_result[122][2]);
        $this->assertEquals('2', $db_result[269][2]);
        $this->assertEquals($mine_id, $db_result[346][2]);
        $this->assertEquals(100, $db_result[448][2]);
        $this->assertEquals(1, $db_result[158][2]);

        // 2) Кэш
        $sensor_parameter_value_list_cache = (new \backend\controllers\cachemanagers\SensorCacheController())->multiGetParameterValue(1000000, '*', '*');
        foreach ($sensor_parameter_value_list_cache as $sensor_parameter_value_cache) {
            $cache_result[$sensor_parameter_value_cache['parameter_id']][$sensor_parameter_value_cache['parameter_type_id']] = $sensor_parameter_value_cache['value'];
        }
        $this->assertEquals($packet_object->batteryVoltage, $cache_result[95][2]);
        $this->assertEquals(1, $cache_result[164][3]);
        $this->assertEquals('15.000,15.000,15.000', $cache_result[83][2]);
        $this->assertEquals('3', $cache_result[122][2]);
        $this->assertEquals('2', $cache_result[269][2]);
        $this->assertEquals($mine_id, $cache_result[346][2]);
        $this->assertEquals(100, $cache_result[448][2]);
        $this->assertEquals(1, $cache_result[158][2]);

        // 3) События в БД
        $query = Yii::$app->db->createCommand(
            'SELECT * FROM `view_event_journal` where main_id = 1000000'
        )->queryAll();
        $event_db_result = array();
        foreach ($query as $event) {
            $event_db_result[$event['event_id']] = $event;
        }
        //fwrite(STDOUT, print_r($event_db_result, true));
        $this->assertArrayHasKey(EventEnumController::LAMP_LOW_BATTERY, $event_db_result, 'Проверка события "Низкий заряд батареи светильника"');
        $this->assertEquals(100, $event_db_result[EventEnumController::LAMP_LOW_BATTERY]['parameter_value']);
        $this->assertEquals(52, $event_db_result[EventEnumController::LAMP_LOW_BATTERY]['status_id']);
        $this->assertEquals(45, $event_db_result[EventEnumController::LAMP_LOW_BATTERY]['event_type_id']);

        // 4) События в кэше
        $event_cache_result = (new \backend\controllers\cachemanagers\EventCacheController())->getEvent($mine_id, 7137, 1000000)['Items'];
        $this->assertEquals(100, $event_cache_result['value']);
        $this->assertEquals(52, $event_cache_result['event_status_id']);
        $this->assertEquals(45, $event_cache_result['value_status_id']);

    }

    /**
     * Тестируем: StrataJobController::saveLocationPacket
     *
     * По сценарию: обработка пакета с двумя услышанными узлами (с одинаковым
     * уровнем сигнала) и с привязкой к воркеру. Воркер подал сигнал СОС.
     * На выработке нет конвейера, следовательно скорость движения не расчитывается.
     * Воркер находится не в ламповой.
     *
     * Ожидаем:
     *   1) Запись значений параметров сенсора в БД
     *   2) Запись значений параметров сенсора в кэш
     *   3) Генерация событий в БД
     *   4) Генерация событий в кэше
     *   5) Запись значений параметров воркера в БД
     *   6) Запись значений параметров воркера в кэш
     *   7) Добавление записи о нахождении воркера в отчётную таблицу
     *   8) Добавление воркера в кэш зачекиненных
     *   Значения параметров координат, эджа и плейса вычисляются и при одинаковом
     *   уровне сигнала точка должна быть ровно между узлов.
     *   Процент заряда батареи = 100%
     */
    public function testSaveLocationPacketWorkerObjectTwoGateways()
    {
        //$this->fail('Тестовое окружение для теста не настроено.
        //Нужно положить в кэш графы сенсоров, т.е. нужно создать тестовые выработки');

        /**
         * Создание воркера, привязанного к сенсору
         */
        Yii::$app->db->createCommand()
            ->insert('worker', [
                'id' => 1,
                'employee_id' => 1301,
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

        $result = (new \backend\controllers\CoordinateController())->buildGraph(270);
        //fwrite(STDERR, print_r($result, true));

        $packet_object = (object) [
            'timestamp' => '2019-08-09 13:57:00.321675',
            'batteryVoltage' => '4.100',
            'networkId' => '12',
            'alarmFlag' => '1',
            'emergencyFlag' => '0',
            'surfaceFlag' => 'Underground',
            'movingFlag' => 'Moving',
            'nodes' => [
                (object)['address' => '1', 'rssi' => '-50'],
                (object)['address' => '2', 'rssi' => '-50']
            ]
        ];
        $mine_id = 270;
        $ip_addr = '127.0.0.1';
        $result = StrataJobController::saveLocationPacket(new \backend\controllers\MinerNodeLocation($packet_object), $mine_id, $ip_addr);
        //fwrite(STDOUT, print_r($result, true));
        $this->assertEquals(1, $result['status'], print_r($result, true));

        // 1) Параметры сенсора БД
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM `view_sensor_parameter_value_only_main` where sensor_id = 1000000'
        )->queryAll();
        //fwrite(STDOUT, print_r($db_result, true));
        $db_result = \yii\helpers\ArrayHelper::map($db_result, 'parameter_type_id', 'value', 'parameter_id');
        $this->assertEquals($packet_object->batteryVoltage, $db_result[95][2]);
        $this->assertEquals(1, $db_result[164][3]);
        $this->assertEquals('30,30,30', $db_result[83][2]);
        $this->assertEquals('3', $db_result[122][2]);
        $this->assertEquals('2', $db_result[269][2]);
        $this->assertEquals($mine_id, $db_result[346][2]);
        $this->assertEquals(100, $db_result[448][2]);
        $this->assertEquals(1, $db_result[158][2]);
        unset($db_result);

        // 2) Параметры сенсора Кэш
        $sensor_parameter_value_list_cache = (new \backend\controllers\cachemanagers\SensorCacheController())->multiGetParameterValue(1000000, '*', '*');
        foreach ($sensor_parameter_value_list_cache as $sensor_parameter_value_cache) {
            $cache_result[$sensor_parameter_value_cache['parameter_id']][$sensor_parameter_value_cache['parameter_type_id']] = $sensor_parameter_value_cache['value'];
        }
        $this->assertEquals($packet_object->batteryVoltage, $cache_result[95][2]);
        $this->assertEquals(1, $cache_result[164][3]);
        $this->assertEquals('30,30,30', $cache_result[83][2]);
        $this->assertEquals('3', $cache_result[122][2]);
        $this->assertEquals('2', $cache_result[269][2]);
        $this->assertEquals($mine_id, $cache_result[346][2]);
        $this->assertEquals(100, $cache_result[448][2]);
        $this->assertEquals(1, $cache_result[158][2]);
        unset($cache_result);

        // 3) События в БД
        $query = Yii::$app->db->createCommand(
            'SELECT * FROM `view_event_journal` where main_id = 1000000 or main_id = 1'
        )->queryAll();
        //fwrite(STDOUT, print_r($query, true));
        $event_db_result = array();
        foreach ($query as $event) {
            $event_db_result[$event['event_id']] = $event;
        }
        $this->assertArrayHasKey(EventEnumController::LAMP_LOW_BATTERY, $event_db_result, 'Проверка события "Низкий заряд батареи светильника"');
        $this->assertEquals(100, $event_db_result[EventEnumController::LAMP_LOW_BATTERY]['parameter_value']);
        $this->assertEquals(52, $event_db_result[EventEnumController::LAMP_LOW_BATTERY]['status_id']);
        $this->assertEquals(45, $event_db_result[EventEnumController::LAMP_LOW_BATTERY]['event_type_id']);

        $this->assertArrayNotHasKey(7126, $event_db_result, 'Проверка события "Превышение скорости движения"');

        $this->assertArrayHasKey(7129, $event_db_result, 'Проверка события "Человек без движения"');
        $this->assertEquals('Moving', $event_db_result[7129]['parameter_value']);
        $this->assertEquals(52, $event_db_result[7129]['status_id']);
        $this->assertEquals(45, $event_db_result[7129]['event_type_id']);

        $this->assertArrayHasKey(7139, $event_db_result, 'Проверка события "Человек в опасной зоне"');
        $this->assertEquals(3, $event_db_result[7139]['parameter_value']);
        $this->assertEquals(52, $event_db_result[7139]['status_id']);
        $this->assertEquals(45, $event_db_result[7139]['event_type_id']);

        $this->assertArrayHasKey(7127, $event_db_result, 'Проверка события "Сигнал СОС"');
        $this->assertEquals(1, $event_db_result[7127]['parameter_value']);
        $this->assertEquals(38, $event_db_result[7127]['status_id']);
        $this->assertEquals(44, $event_db_result[7127]['event_type_id']);


        // 4) События в кэше
        $event_cache_result = (new \backend\controllers\cachemanagers\EventCacheController())->getEvent($mine_id, 7137, 1000000)['Items'];
        $this->assertEquals(100, $event_cache_result['value']);
        $this->assertEquals(52, $event_cache_result['event_status_id']);
        $this->assertEquals(45, $event_cache_result['value_status_id']);

        $event_cache_result = (new \backend\controllers\cachemanagers\EventCacheController())->getEvent($mine_id, 7126, 1)['Items'];
        $this->assertFalse($event_cache_result);

        $event_cache_result = (new \backend\controllers\cachemanagers\EventCacheController())->getEvent($mine_id, 7129, 1)['Items'];
        //fwrite(STDOUT, print_r($event_cache_result, true));
        $this->assertEquals('Moving', $event_cache_result['value']);
        $this->assertEquals(52, $event_cache_result['event_status_id']);
        $this->assertEquals(45, $event_cache_result['value_status_id']);

        $event_cache_result = (new \backend\controllers\cachemanagers\EventCacheController())->getEvent($mine_id, 7139, 1)['Items'];
        $this->assertEquals(3, $event_cache_result['value']);
        $this->assertEquals(52, $event_cache_result['event_status_id']);
        $this->assertEquals(45, $event_cache_result['value_status_id']);

        $event_cache_result = (new \backend\controllers\cachemanagers\EventCacheController())->getEvent($mine_id, 7127, 1)['Items'];
        $this->assertEquals(1, $event_cache_result['value']);
        $this->assertEquals(38, $event_cache_result['event_status_id']);
        $this->assertEquals(44, $event_cache_result['value_status_id']);


        // 5) Параметры воркера БД
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM `view_worker_parameters_last_values` where worker_id = 1'
        )->queryAll();
        $db_result = \yii\helpers\ArrayHelper::map($db_result, 'parameter_type_id', 'value', 'parameter_id');
        $this->assertEquals(270, $db_result[346][2], 'Идентификатор шахты БД');
        $this->assertEquals('Underground', $db_result[358][2], 'На поверхности/под землёй БД');
        $this->assertEquals('30,30,30', $db_result[83][2], 'Координаты БД');
        $this->assertEquals('2', $db_result[269][2]);
        $this->assertEquals('3', $db_result[122][2]);
        $this->assertEquals('Moving', $db_result[356][2], 'В движении/без движения');
        $this->assertEquals(1, $db_result[323][2], 'SOS');
        $this->assertEquals(1, $db_result[158][2], 'Checkin');
        unset($db_result);

        // 6) Параметры воркера кэш
        $cache_parameters = (new \backend\controllers\cachemanagers\WorkerCacheController())->multiGetParameterValue(1, '*', '*');
        foreach ($cache_parameters as $cache_parameter) {
            $cache_result[$cache_parameter['parameter_id']][$cache_parameter['parameter_type_id']] = $cache_parameter['value'];
        }
        $this->assertEquals(270, $cache_result[346][2], 'Идентификатор шахты кэш');
        $this->assertEquals('Underground', $cache_result[358][2], 'На поверхности/под землёй кэш');
        $this->assertEquals('30,30,30', $cache_result[83][2], 'Координаты кэш');
        $this->assertEquals('2', $cache_result[269][2]);
        $this->assertEquals('3', $cache_result[122][2]);
        $this->assertEquals('Moving', $cache_result[356][2], 'В движении/без движения кэш');
        $this->assertEquals(1, $cache_result[323][2], 'SOS кэш');
        $this->assertEquals(1, $cache_result[158][2], 'Checkin кэш');
        unset($cache_result);

        // 7) Добавление записи о нахождении воркера в отчётную таблицу
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM `worker_collection` where worker_id = 1'
        )->queryAll();
        $db_result = $db_result[0];
        $this->assertEquals('Admin Админ Админович', $db_result['last_name']);
        $this->assertEquals('2019-08-09', $db_result['date_work']);
        $this->assertEquals('2019-08-09 13:57:00', $db_result['date_time_work']);
        $this->assertEquals(3, $db_result['place_id']);

        // 8) Добавление воркера в кэш зачекиненных
        $checkin_result = (new \backend\controllers\cachemanagers\WorkerCacheController())->getWorkerMineByWorkerOne($mine_id, 1);
        $this->assertNotFalse($checkin_result);


    }
}