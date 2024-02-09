<?php


use backend\controllers\StrataJobController;

class EnvironmentalPacketTest extends \Codeception\Test\Unit
{
    public function _before()
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
    }

    public function _after()
    {
        // Очистка кэша сенсоров
        $keys = Yii::$app->redis_sensor->scan(0, 'MATCH', '*1000000*', 'COUNT', '10000000')[1];
        foreach ($keys as $key) {
            Yii::$app->redis_sensor->del($key);
        }

        // Очистка кэша воркеров
        $keys = Yii::$app->redis_worker->scan(0, 'MATCH', 'WoMi:290:1', 'COUNT', '10000000')[1];
        $keys = array_merge($keys, Yii::$app->redis_worker->scan(0, 'MATCH', 'WoPa:1:*', 'COUNT', '10000000')[1]);
        foreach ($keys as $key) {
            Yii::$app->redis_worker->del($key);
        }
    }

    /**
     * Тестируем: StrataJobController::saveEnvironmentalPacket
     *
     * По сценарию: пришёл пакет с показаниями CH4. Значения в пределах нормы.
     * К светильнику привязан воркер.
     *
     * Ожидаем:
     *   1) Запись значений параметров сенсора в БД
     *   2) Запись значений параметров сенсора в кэш
     *   3) Генерация событий в БД
     *   4) Генерация событий в кэше
     *   5) Запись значений параметров воркера в БД
     *   6) Запись значений параметров воркера в кэш
     *   7) Добавление записи по концентрации газа в отчётную таблицу
     */
    public function testSaveEnvironmentalPacketCH4()
    {
        $packet_object = (object) [
            'sequenceNumber' => '1',
            'sourceNode' => '12',
            'parametersCount' => 2,
            'timestamp' => '2019-08-08 11:50:00',
            'parameters' => array(
                (object) [
                    'id' => 100,
                    'value' => (object) [
                        'gasReading' => '70',
                        'sensorModuleType' => '20'
                    ]
                ],
                (object) [
                    'id' => 101,
                    'value' => (object) [
                        'totalDigits' => '0',
                        'decimalDigits' => '0'
                    ]
                ],
            )
        ];
        $mine_id = '290';

        $result = StrataJobController::saveEnvironmentalPacket(new \backend\controllers\EnvironmentalSensor($packet_object), $mine_id);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        //fwrite(STDERR, print_r($result, true));

        // 1) параметры сенсора в БД
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM `view_sensor_parameter_value_only_main` where sensor_id = 1000000'
        )->queryAll();
        //fwrite(STDOUT, print_r($db_result, true));
        $db_result = \yii\helpers\ArrayHelper::map($db_result, 'parameter_type_id', 'value', 'parameter_id');
        $this->assertArrayNotHasKey(83, $db_result, 'Наличие параметра 83 БД');
        $this->assertArrayNotHasKey(122, $db_result, 'Наличие параметра 122 БД');
        $this->assertArrayNotHasKey(269, $db_result, 'Наличие параметра 269 БД');
        $this->assertEquals(1, $db_result[164][3], 'Состояние БД');
        $this->assertEquals(0.7, $db_result[99][2], 'Концентрация CH4 БД');
        $this->assertEquals(0, $db_result[386][2], 'Превышение концентрации CH4 БД');
        unset($db_result);

        // 2) Запись значений параметров сенсора в кэш
        $sensor_parameter_value_list_cache = (new \backend\controllers\cachemanagers\SensorCacheController())->multiGetParameterValue(1000000, '*', '*');
        foreach ($sensor_parameter_value_list_cache as $sensor_parameter_value_cache) {
            $cache_result[$sensor_parameter_value_cache['parameter_id']][$sensor_parameter_value_cache['parameter_type_id']] = $sensor_parameter_value_cache['value'];
        }
        $this->assertArrayNotHasKey(83, $cache_result, 'Наличие параметра 83 кэш');
        $this->assertArrayNotHasKey(122, $cache_result, 'Наличие параметра 122 кэш');
        $this->assertArrayNotHasKey(269, $cache_result, 'Наличие параметра 269 кэш');
        $this->assertEquals(1, $cache_result[164][3], 'Состояние кэш');
        $this->assertEquals(0.7, $cache_result[99][2], 'Концентрация CH4 кэш');
        $this->assertEquals(0, $cache_result[386][2], 'Превышение концентрации CH4 кэш');
        unset($cache_result);

        // 3) Генерация событий в БД
        $query = Yii::$app->db->createCommand(
            'SELECT * FROM `view_event_journal` where main_id = 1'
        )->queryAll();
        $event_db_result = array();
        foreach ($query as $event) {
            $event_db_result[$event['event_id']] = $event;
        }
        //fwrite(STDERR, print_r($event_db_result, true));
        //$this->assertArrayHasKey(22409, $event_db_result, 'Проверка события "Превышение CH4 со светильника"');
        /*$this->assertEquals(0, $event_db_result[22409]['parameter_value']);
        $this->assertEquals(52, $event_db_result[22409]['status_id']);
        $this->assertEquals(45, $event_db_result[22409]['event_type_id']);*/

        // 4) Генерация событий в кэше
        /*$event_cache_result = (new \backend\controllers\cachemanagers\EventCacheController())->getEvent($mine_id, 7137, 1)['Items'];
        $this->assertEquals(0, $event_db_result[22409]['parameter_value']);
        $this->assertEquals(52, $event_db_result[22409]['status_id']);
        $this->assertEquals(45, $event_db_result[22409]['event_type_id']);*/

        // 5) Запись значений параметров воркера в БД
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM `view_worker_parameters_last_values` where worker_id = 1'
        )->queryAll();
        $db_result = \yii\helpers\ArrayHelper::map($db_result, 'parameter_type_id', 'value', 'parameter_id');
        $this->assertArrayNotHasKey(83, $db_result, 'Наличие параметра 83 БД');
        $this->assertArrayNotHasKey(122, $db_result, 'Наличие параметра 122 БД');
        $this->assertArrayNotHasKey(269, $db_result, 'Наличие параметра 269 БД');
        $this->assertEquals(0.7, $db_result[99][2], 'Концентрация CH4 БД');
        $this->assertEquals(0, $db_result[386][2], 'Превышение концентрации CH4 БД');

        // 6) Запись значений параметров воркера в кэш
        $cache_parameters = (new \backend\controllers\cachemanagers\WorkerCacheController())->multiGetParameterValue(1, '*', '*');
        foreach ($cache_parameters as $cache_parameter) {
            $cache_result[$cache_parameter['parameter_id']][$cache_parameter['parameter_type_id']] = $cache_parameter['value'];
        }
        $this->assertArrayNotHasKey(83, $db_result, 'Наличие параметра 83 БД');
        $this->assertArrayNotHasKey(122, $db_result, 'Наличие параметра 122 БД');
        $this->assertArrayNotHasKey(269, $db_result, 'Наличие параметра 269 БД');
        $this->assertEquals(0.7, $db_result[99][2], 'Концентрация CH4 БД');
        $this->assertEquals(0, $db_result[386][2], 'Превышение концентрации CH4 БД');

        // 7) Добавление записи по концентрации газа в отчётную таблицу
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM `summary_report_sensor_gas_concentration` where sensor_id = 1000000'
        )->queryAll();
        $db_result = $db_result[0];
        $this->assertEquals(1000000, $db_result['sensor_id'], 'sensor_id');
        $this->assertEquals('Unit Test Lamp', $db_result['sensor_title'], 'sensor_title');
        $this->assertEquals(99, $db_result['parameter_id'], 'parameter_id');
        $this->assertEquals(0.7, $db_result['gas_fact_value'], 'gas_fact_value');
        $this->assertEquals(1, $db_result['edge_gas_nominal_value'], 'edge_gas_nominal_value');
        $this->assertEquals('2019-08-08 11:50:00', $db_result['date_time'], 'date_time');
        $this->assertNull($db_result['edge_id'], 'edge_id');
        $this->assertEmpty($db_result['place_title'], 'place_title');
        $this->assertEquals('%', $db_result['unit_title'], 'unit_title');
        $this->assertNull($db_result['place_id'], 'place_id');
        $this->assertEquals('Концентрация метана (CH4)', $db_result['parameter_title'], 'parameter_title');
    }
}
