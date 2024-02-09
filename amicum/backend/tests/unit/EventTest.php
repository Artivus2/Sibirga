<?php

use \backend\controllers\EventBasicController;
use \backend\controllers\cachemanagers\EventCacheController;

class EventTest extends \Codeception\Test\Unit
{
    public function _before()
    {
        Yii::$app->db->createCommand()
            ->insert('event', [
                'id' => 1,
                'title' => 'Unit Test Event',
                'object_id' => 33
            ])->execute();
    }

    /**
     * Тестируем: EventCacheController::createEventJournalEntry
     * По сценарию: вставка корректной записи
     * Ожидаем: новая запись в таблице event_journal
     */
    public function testCreateEventJournalEntry()
    {
        $response = EventBasicController::createEventJournalEntry(
            1,
            2,
            3,
            4,
            '2019-08-14 08:25:00',
            5,
            6,
            7,
            8,
            9,
            '10',
            '11',
            1,
            null
        );
        $this->assertEquals(1, $response['status'], print_r($response, true));
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM event_journal where event_id = 1'
        )->queryOne();
        $this->assertInternalType('array', $db_result);
        $this->assertEquals(1, $db_result['event_id']);
        $this->assertEquals(2, $db_result['main_id']);
        $this->assertEquals(3, $db_result['edge_id']);
        $this->assertEquals(4, $db_result['value']);
        $this->assertEquals('2019-08-14 08:25:00', $db_result['date_time']);
        $this->assertEquals(5, $db_result['xyz']);
        $this->assertEquals(6, $db_result['status_id']);
        $this->assertEquals(7, $db_result['parameter_id']);
        $this->assertEquals(8, $db_result['object_id']);
        $this->assertEquals(9, $db_result['mine_id']);
        $this->assertEquals('10', $db_result['object_title']);
        $this->assertEquals('11', $db_result['object_table']);
    }

    /**
     * Тестируем: EventCacheController::createEventStatusEntry
     * По сценарию: вставка корректной записи
     * Ожидаем: новая запись в таблице event_status
     */
    public function testCreateEventStatusEntry()
    {
        Yii::$app->db->createCommand()->insert('event_journal', [
            'id' => 1,
            'event_id' => 1,
            'main_id' => 2,
            'edge_id' => 3,
            'value' => 4,
            'date_time' => '2019-08-14 08:25:00',
            'xyz' => 5,
            'status_id' => 6,
            'parameter_id' => 7,
            'object_id' => 8,
            'mine_id' => 9,
            'object_title' => '10',
            'object_table' => '11'
        ])->execute();
        $response = EventBasicController::createEventStatusEntry(
            1, 38, '2019-08-14 08:56:00'
        );
        $this->assertEquals(1, $response['status'], print_r($response, true));
        $db_result = Yii::$app->db->createCommand(
            'SELECT * FROM event_status where event_journal_id = 1'
        )->queryOne();
        $this->assertInternalType('array', $db_result);
        $this->assertEquals(38, $db_result['status_id']);
        $this->assertEquals('2019-08-14 08:56:00', $db_result['datetime']);
    }

    /**
     * Тестируем: EventCacheController::buildCacheKey
     * По сценарию: генерация различных ключей кэша
     * Ожидаем: корректные ключи
     */
    public function testBuildCacheKey()
    {
        $this->assertEquals('Ev:*:*:*', EventCacheController::buildCacheKey('*','*','*'));
        $this->assertEquals('Ev:290:*:*', EventCacheController::buildCacheKey(290,'*','*'));
        $this->assertEquals('Ev:0:0:0', EventCacheController::buildCacheKey(0,0,0));
        $this->assertEquals('Ev::0:0', EventCacheController::buildCacheKey(null,0,0));
        $this->assertEquals('Ev::0:0', EventCacheController::buildCacheKey(false,0,0));
    }

    /**
     * Тестируем: EventCacheController::getEventsList
     * По сценарию:
     *   1) получение всех событий из пустого кэша
     *   2) получение событий из непустого кэша
     * Ожидаем:
     *   1) статус работы метода = 0
     *   2) статус = 1, получен массив событий
     */
    public function testGetEventsList()
    {
        $keys = Yii::$app->redis_event->scan(0, 'MATCH', 'Ev:*', 'COUNT', '10000000')[1];
        if ($keys) {
            foreach ($keys as $key) {
                Yii::$app->redis_event->del($key);
            }
        }

        $event_cache_controller = new EventCacheController();

        $result = $event_cache_controller->getEventsList();
        $this->assertEquals(0, $result['status'], print_r($result, true));
        //$this->assertFalse($result['Items']);

        $event_cache_controller->setEvent(
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, null
        );

        $expected = array([
            'event_id' => 2,
            'main_id' => 3,
            'edge_id' => 5,
            'value' => 6,
            'value_status_id' => 7,
            'date_time' => 8,
            'event_status_id' => 4,
            'mine_id' => 1,
            'xyz' => 9,
            'parameter_id' => 10,
            'object_id' => 11,
            'object_title' => 12,
            'object_table' => 13,
            'event_journal_id' => 14
        ]);
        $result = $event_cache_controller->getEventsList();
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $this->assertEquals($expected, $result['Items']);
    }

    /**
     * Тестируем: EventCacheController::getEvent
     * По сценарию:
     *   1) получение события из пустого кэша
     *   2) получение события из непустого кэша
     * Ожидаем:
     *   1) статус работы метода = 0
     *   2) статус = 1, получено событие
     */
    public function testGetEvent()
    {
        $keys = Yii::$app->redis_event->scan(0, 'MATCH', 'Ev:*', 'COUNT', '10000000')[1];
        if ($keys) {
            foreach ($keys as $key) {
                Yii::$app->redis_event->del($key);
            }
        }

        $event_cache_controller = new EventCacheController();

        $result = $event_cache_controller->getEvent(1, 2, 3);
        $this->assertEquals(0, $result['status'], print_r($result, true));
        //$this->assertFalse($result['Items']);

        $event_cache_controller->setEvent(
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, null
        );

        $expected = array(
            'event_id' => 2,
            'main_id' => 3,
            'edge_id' => 5,
            'value' => 6,
            'value_status_id' => 7,
            'date_time' => 8,
            'event_status_id' => 4,
            'mine_id' => 1,
            'xyz' => 9,
            'parameter_id' => 10,
            'object_id' => 11,
            'object_title' => 12,
            'object_table' => 13,
            'event_journal_id' => 14
        );
        $result = $event_cache_controller->getEvent(1, 2, 3);
        $this->assertEquals(1, $result['status'], print_r($result, true));
        $this->assertEquals($expected, $result['Items']);
    }

}
