<?php


use backend\controllers\StrataJobController;

class HeartbeatPacketTest extends \Codeception\Test\Unit
{
    public function _before()
    {
        /**
         * Создание узла связи Unit Test C:
         *   id: 900000
         *   object_id: 46
         *   net_id: 2
         */
        $c_fix_net_id = '2';
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
        Yii::$app->db->createCommand()  // Тип объекта
        ->insert('sensor_parameter', [
            'id' => 920000,
            'sensor_id' => 900000,
            'parameter_id' => 274,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение типа объекта
        ->insert('sensor_parameter_handbook_value', [
            'id' => 921000,
            'sensor_parameter_id' => 920000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '46',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Тип объекта
        ->insert('sensor_parameter', [
            'id' => 930000,
            'sensor_id' => 900000,
            'parameter_id' => 346,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение типа объекта
        ->insert('sensor_parameter_handbook_value', [
            'id' => 931000,
            'sensor_parameter_id' => 930000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '290',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Координаты
        ->insert('sensor_parameter', [
            'id' => 940000,
            'sensor_id' => 900000,
            'parameter_id' => 83,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение координат
        ->insert('sensor_parameter_handbook_value', [
            'id' => 941000,
            'sensor_parameter_id' => 940000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '15.000,15.000,15.000',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Эдж
        ->insert('sensor_parameter', [
            'id' => 950000,
            'sensor_id' => 900000,
            'parameter_id' => 269,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение эджа
        ->insert('sensor_parameter_handbook_value', [
            'id' => 951000,
            'sensor_parameter_id' => 950000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '2',
            'status_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Плейс
        ->insert('sensor_parameter', [
            'id' => 960000,
            'sensor_id' => 900000,
            'parameter_id' => 122,
            'parameter_type_id' => 1
        ])->execute();
        Yii::$app->db->createCommand()  // Значение плейса
        ->insert('sensor_parameter_handbook_value', [
            'id' => 961000,
            'sensor_parameter_id' => 960000,
            'date_time' => date('Y-m-d H:i:s.u'),
            'value' => '3',
            'status_id' => 1
        ])->execute();

        (new \backend\controllers\cachemanagers\SensorCacheController())->initSensorParameterHandbookValue(900000);
        (new \backend\controllers\cachemanagers\ServiceCache())->initSensorNetwork();
    }

    public function _after()
    {
        // Удаление кэша сенсоров
        $keys = Yii::$app->redis_sensor->scan(0, 'MATCH', '*900000*', 'COUNT', '10000000')[1];
        foreach ($keys as $key) {
            Yii::$app->redis_sensor->del($key);
        }
    }

    /**
     * Тестируем: StrataJobController::saveHeartbeatPacket
     *
     * По сценарию: Узел связи поставлен на схеме (есть значения параметров 83, 122, 269).
     * Все значения в пакете нормальные
     *
     * Ожидаем:
     *   1) Запись значений параметров в БД
     *   2) Запись значений параметров в кэш
     *   3) Генерация нормальных событий в БД
     *   4) Генерация нормальных событий в кэше
     */
    public function testSaveHeartbeatPacket()
    {
        $packet_object = (object) [
            'timestamp' => date('Y-m-d H:i:s.u'),
            'sequenceNumber' => '1',
            'batteryVoltage' => '5.100',
            'sourceNode' => '2',
            'routingRootNodeAddress' => '3',
            'routingParentNode' => (object) [
                'address' => '4',
                'rssi' => '-40'
            ],
            'neighborTableFull' => '5',
            'neighborCount' => '6',
            'routingRootHops' => '7',
            'timingRootNodeAddress' => '8',
            'timingParentNode' => (object) [
                'address' => '9',
                'rssi' => '-90'
            ],
            'timingRootHops' => '10',
            'lostRoutingParent' => '11',
            'lostTimingParent' => '12',
            'routingChangeParents' => '13',
            'timingChangeParents' => '14',
            'routingAboveThresh' => '15',
            'timingAboveThresh' => '16',
            'queueOverfow' => '17',
            'netEntryCount' => '18',
            'minNumberIdleSlots' => '19',
            'listenDuringTransmit' => '20',
            'netEntryReason' => '21',
            'grantparentBlocked' => '22',
            'parentTimeoutExpired' => '23',
            'cycleDetection' => '24',
            'noIdleSlots' => '25',
            'cc1110' => '26',
            'pic' => '27',
            'numberOfHeartbeats' => '26'
        ];
        $mine_id = 290;

        $result = StrataJobController::saveHeartbeatPacket(
            new \backend\controllers\CommunicationNodeHeartbeat($packet_object),
            $mine_id
            );
        $this->assertEquals(1, $result['status'], print_r($result, true));

        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM `view_sensor_parameter_value_only_main` where sensor_id = 900000'
        )->queryAll();
        $db_result = \yii\helpers\ArrayHelper::map($db_result, 'parameter_type_id', 'value', 'parameter_id');

        // 1) Значения параметров БД
        $this->assertEquals(40, $db_result[447][2], 'Процент заряда батареи');
        $this->assertEquals(1, $db_result[164][3], 'Состояние');
        $this->assertEquals('5.100', $db_result[95][2], 'Напряжение питания');
        $this->assertEquals(4, $db_result[310][2], 'Ближайший сосед маршрутизации');
        $this->assertEquals('-40', $db_result[312][2], 'Уровень сигнала до ближайшего соседа маршрутизации');
        $this->assertEquals(3, $db_result[313][2], 'Шлюз маршрутизации');
        $this->assertEquals(7, $db_result[359][2], 'Количество транзитных узлов до шлюза маршрутизации');
        $this->assertEquals(9, $db_result[314][2], 'Ближайший сосед синхронизации');
        $this->assertEquals('-90', $db_result[315][2], 'Уровень сигнала до ближайшего соседа синхронизации');
        $this->assertEquals(8, $db_result[316][2], 'Шлюз синхронизации');
        $this->assertEquals(10, $db_result[360][2], 'Количество транзитных узлов до шлюза синхронизации');
        $this->assertEquals(5, $db_result[361][2], 'Таблица соседних узлов заполнена');
        $this->assertEquals(6, $db_result[362][2], 'Количество соседних узлов');
        $this->assertEquals(12, $db_result[363][2], 'Количество потерянных родителей синхронизации');
        $this->assertEquals(11, $db_result[364][2], 'Количество потерянных родителей маршрутизации');
        $this->assertEquals(14, $db_result[365][2], 'Количество смененных родителей синхронизации');
        $this->assertEquals(13, $db_result[366][2], 'Количество смененных родителей маршрутизации');
        $this->assertEquals(15, $db_result[367][2], 'Родитель маршрутизации выше индикатора мощности принятого сигнала');
        $this->assertEquals(16, $db_result[368][2], 'Родитель синхронизации выше индикатора мощности принятого сигнала');
        $this->assertEquals(17, $db_result[369][2], 'Значение переполнения очереди');
        $this->assertEquals(18, $db_result[370][2], 'Количество входов в сеть');
        $this->assertEquals(19, $db_result[371][2], 'Минимальное количество свободных интервалов');
        $this->assertEquals(20, $db_result[372][2], 'Прослушивание во время передачи');
        $this->assertEquals(21, $db_result[373][2], 'Причина входа в сеть');
        $this->assertEquals(22, $db_result[374][2], 'Прародитель заблокирован');
        $this->assertEquals(23, $db_result[375][2], 'Время ожидания роодителя превышено');
        $this->assertEquals(24, $db_result[376][2], 'Обнаружен цикл');
        $this->assertEquals(25, $db_result[377][2], 'Нет свободных интервалов');
        $this->assertEquals(26, $db_result[355][2], 'Версия СС1110');
        $this->assertEquals(27, $db_result[354][2], 'Версия PIC');
        $this->assertEquals(26, $db_result[311][2], 'Количество отправленных heartbeat-сообщений');

        // 2) Значения параметров кэш
        $sensor_parameter_value_list_cache = (new \backend\controllers\cachemanagers\SensorCacheController())->multiGetParameterValue(900000, '*', '*');
        foreach ($sensor_parameter_value_list_cache as $sensor_parameter_value_cache) {
            $cache_result[$sensor_parameter_value_cache['parameter_id']][$sensor_parameter_value_cache['parameter_type_id']] = $sensor_parameter_value_cache['value'];
        }
        $this->assertEquals(40, $cache_result[447][2], 'Процент заряда батареи');
        $this->assertEquals(1, $cache_result[164][3], 'Состояние');
        $this->assertEquals('5.100', $cache_result[95][2], 'Напряжение питания');
        $this->assertEquals(4, $cache_result[310][2], 'Ближайший сосед маршрутизации');
        $this->assertEquals('-40', $cache_result[312][2], 'Уровень сигнала до ближайшего соседа маршрутизации');
        $this->assertEquals(3, $cache_result[313][2], 'Шлюз маршрутизации');
        $this->assertEquals(7, $cache_result[359][2], 'Количество транзитных узлов до шлюза маршрутизации');
        $this->assertEquals(9, $cache_result[314][2], 'Ближайший сосед синхронизации');
        $this->assertEquals('-90', $cache_result[315][2], 'Уровень сигнала до ближайшего соседа синхронизации');
        $this->assertEquals(8, $cache_result[316][2], 'Шлюз синхронизации');
        $this->assertEquals(10, $cache_result[360][2], 'Количество транзитных узлов до шлюза синхронизации');
        $this->assertEquals(5, $cache_result[361][2], 'Таблица соседних узлов заполнена');
        $this->assertEquals(6, $cache_result[362][2], 'Количество соседних узлов');
        $this->assertEquals(12, $cache_result[363][2], 'Количество потерянных родителей синхронизации');
        $this->assertEquals(11, $cache_result[364][2], 'Количество потерянных родителей маршрутизации');
        $this->assertEquals(14, $cache_result[365][2], 'Количество смененных родителей синхронизации');
        $this->assertEquals(13, $cache_result[366][2], 'Количество смененных родителей маршрутизации');
        $this->assertEquals(15, $cache_result[367][2], 'Родитель маршрутизации выше индикатора мощности принятого сигнала');
        $this->assertEquals(16, $cache_result[368][2], 'Родитель синхронизации выше индикатора мощности принятого сигнала');
        $this->assertEquals(17, $cache_result[369][2], 'Значение переполнения очереди');
        $this->assertEquals(18, $cache_result[370][2], 'Количество входов в сеть');
        $this->assertEquals(19, $cache_result[371][2], 'Минимальное количество свободных интервалов');
        $this->assertEquals(20, $cache_result[372][2], 'Прослушивание во время передачи');
        $this->assertEquals(21, $cache_result[373][2], 'Причина входа в сеть');
        $this->assertEquals(22, $cache_result[374][2], 'Прародитель заблокирован');
        $this->assertEquals(23, $cache_result[375][2], 'Время ожидания роодителя превышено');
        $this->assertEquals(24, $cache_result[376][2], 'Обнаружен цикл');
        $this->assertEquals(25, $cache_result[377][2], 'Нет свободных интервалов');
        $this->assertEquals(26, $cache_result[355][2], 'Версия СС1110');
        $this->assertEquals(27, $cache_result[354][2], 'Версия PIC');
        $this->assertEquals(26, $cache_result[311][2], 'Количество отправленных heartbeat-сообщений');

        // 3) События БД
        $query = Yii::$app->db->createCommand(
            'SELECT * FROM `view_event_journal` where main_id = 900000'
        )->queryAll();
        $event_db_result = array();
        foreach ($query as $event) {
            $event_db_result[$event['event_id']] = $event;
        }
        $this->assertArrayHasKey(22408, $event_db_result, 'Проверка события "Низкий заряд батареек"');
        $this->assertEquals(40, $event_db_result[22408]['parameter_value'], 'Значение события в БД');
        $this->assertEquals(52, $event_db_result[22408]['status_id'], 'Статус события в кэше');
        $this->assertEquals(45, $event_db_result[22408]['event_type_id'], 'Статус значения события в кэше');

        // 4) События кэш
        $event_cache_result = (new \backend\controllers\cachemanagers\EventCacheController())->getEvent($mine_id, 22408, 900000)['Items'];
        $this->assertEquals(40, $event_cache_result['value'], 'Значение события в кэше');
        $this->assertEquals(52, $event_cache_result['event_status_id'], 'Статус события в кэше');
        $this->assertEquals(45, $event_cache_result['value_status_id'], 'Статус значения события в кэше');

    }
}
