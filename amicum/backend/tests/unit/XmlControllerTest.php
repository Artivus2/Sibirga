<?php

use frontend\controllers\XmlController;

class XmlControllerTest extends \Codeception\Test\Unit
{
    /**
     * Тестируем: XmlController::getSmsSendingList
     * По сценарию: в таблице нет номеров для рассылки по данному событию
     * Ожидаем: false
     */
    public function testGetEmptySmsSendingListReturnsFalse()
    {
        $result = XmlController::getSmsSendingList(32167);
        $this->assertFalse($result);
    }

    /**
     * Тестируем: XmlController::getSmsSendingList
     * По сценарию: в таблице только один номер, удовлетворяющий условиям
     * Ожидаем: массив из одного элемента
     */
    public function testGetSmsSendingListWithOneNumber()
    {
        Yii::$app->db->createCommand()
            ->insert('event', [
                'id' => 1,
                'title' => 'Unit Test Event',
                'object_id' => 33
            ])->execute();

        Yii::$app->db->createCommand()
            ->insert('xml_model', [
                'id' => 2,
                'title' => 'Unit Test Sms'
            ])->execute();

        $date_start = date('Y-m-d H:i:s', strtotime('-1 day'));
        $date_end = date('Y-m-d H:i:s', strtotime('+1 day'));

        // Актуальный номер
        Yii::$app->db->createCommand()
            ->insert('xml_config', [
                'id' => 10000,
                'xml_model_id' => 2,
                'xml_send_type_id' => 5,
                'address' => '+79050788309',
                'time_period' => 0,
                'time_unit_id' => 1,
                'date_start' => $date_start,
                'date_end' => $date_end,
                'event_id' => 1
            ])->execute();

        $result = XmlController::getSmsSendingList(1);
        $expected = ['+79050788309'];
        $this->assertEquals($expected, $result);
    }

    /**
     * Тестируем: XmlController::getSmsSendingList
     * По сценарию: в таблице n номеров, удовлетворяющих условиям.
     * Ожидаем: массив из номеров
     */
    public function testGetSmsSendingListWithNNumbers()
    {
        Yii::$app->db->createCommand()
            ->insert('event', [
                'id' => 1,
                'title' => 'Unit Test Event',
                'object_id' => 33
            ])->execute();

        Yii::$app->db->createCommand()
            ->insert('xml_model', [
                'id' => 2,
                'title' => 'Unit Test Sms'
            ])->execute();

        $date_start = date('Y-m-d H:i:s', strtotime('-1 day'));
        $date_end = date('Y-m-d H:i:s', strtotime('+1 day'));

        // Актуальные номера
        Yii::$app->db->createCommand()
            ->insert('xml_config', [
                'id' => 10000,
                'xml_model_id' => 2,
                'xml_send_type_id' => 5,
                'address' => '+79050788309',
                'time_period' => 0,
                'time_unit_id' => 1,
                'date_start' => $date_start,
                'date_end' => $date_end,
                'event_id' => 1
            ])->execute();
        Yii::$app->db->createCommand()
            ->insert('xml_config', [
                'id' => 20000,
                'xml_model_id' => 2,
                'xml_send_type_id' => 5,
                'address' => '+9876543210',
                'time_period' => 0,
                'time_unit_id' => 1,
                'date_start' => $date_start,
                'date_end' => $date_end,
                'event_id' => 1
            ])->execute();

        // Адрес рассылки с истёкшей датой
        $expired_date_start = date('Y-m-d H:i:s', strtotime('-2 day'));
        $expired_date_end = date('Y-m-d H:i:s', strtotime('-1 day'));
        Yii::$app->db->createCommand()
            ->insert('xml_config', [
                'id' => 30000,
                'xml_model_id' => 2,
                'xml_send_type_id' => 5,
                'address' => '+711111111',
                'time_period' => 0,
                'time_unit_id' => 1,
                'date_start' => $expired_date_start,
                'date_end' => $expired_date_end,
                'event_id' => 1
            ])->execute();

        // Адрес рассылки с ещё не настпившей датой начала
        $future_date_start = date('Y-m-d H:i:s', strtotime('+1 day'));
        $future_date_end = date('Y-m-d H:i:s', strtotime('+2 day'));
        Yii::$app->db->createCommand()
            ->insert('xml_config', [
                'id' => 40000,
                'xml_model_id' => 2,
                'xml_send_type_id' => 5,
                'address' => '+722222111',
                'time_period' => 0,
                'time_unit_id' => 1,
                'date_start' => $future_date_start,
                'date_end' => $future_date_end,
                'event_id' => 1
            ])->execute();

        $result = XmlController::getSmsSendingList(1);
        $expected = ['+79050788309', '+9876543210'];
        $this->assertEquals($expected, $result);
    }

    /**
     * Тестируем: XmlController::getSmsSendingList
     * По сценарию: в таблице n номеров, удовлетворяющих условиям.
     * Ожидаем: массив из номеров
     */
    public function testGetSmsSendingListWithNNumbersByGroup()
    {
        Yii::$app->db->createCommand()
            ->insert('event', [
                'id' => 1,
                'title' => 'Unit Test Event',
                'object_id' => 33
            ])->execute();

        Yii::$app->db->createCommand()
            ->insert('xml_model', [
                'id' => 2,
                'title' => 'Unit Test Sms'
            ])->execute();

        $date_start = date('Y-m-d H:i:s', strtotime('-1 day'));
        $date_end = date('Y-m-d H:i:s', strtotime('+1 day'));

        // Актуальные номера
        Yii::$app->db->createCommand()
            ->insert('xml_config', [
                'id' => 10000,
                'xml_model_id' => 2,
                'xml_send_type_id' => 5,
                'address' => '+79050788309',
                'time_period' => 0,
                'time_unit_id' => 1,
                'date_start' => $date_start,
                'date_end' => $date_end,
                'event_id' => 1,
                'group_alarm_id' => 1
            ])->execute();
        Yii::$app->db->createCommand()
            ->insert('xml_config', [
                'id' => 20000,
                'xml_model_id' => 2,
                'xml_send_type_id' => 5,
                'address' => '+9876543210',
                'time_period' => 0,
                'time_unit_id' => 1,
                'date_start' => $date_start,
                'date_end' => $date_end,
                'event_id' => 1,
                'group_alarm_id' => 2
            ])->execute();

        // Адрес рассылки с истёкшей датой
        $expired_date_start = date('Y-m-d H:i:s', strtotime('-2 day'));
        $expired_date_end = date('Y-m-d H:i:s', strtotime('-1 day'));
        Yii::$app->db->createCommand()
            ->insert('xml_config', [
                'id' => 30000,
                'xml_model_id' => 2,
                'xml_send_type_id' => 5,
                'address' => '+711111111',
                'time_period' => 0,
                'time_unit_id' => 1,
                'date_start' => $expired_date_start,
                'date_end' => $expired_date_end,
                'event_id' => 1,
                'group_alarm_id' => 1
            ])->execute();

        // Адрес рассылки с ещё не настпившей датой начала
        $future_date_start = date('Y-m-d H:i:s', strtotime('+1 day'));
        $future_date_end = date('Y-m-d H:i:s', strtotime('+2 day'));
        Yii::$app->db->createCommand()
            ->insert('xml_config', [
                'id' => 40000,
                'xml_model_id' => 2,
                'xml_send_type_id' => 5,
                'address' => '+722222111',
                'time_period' => 0,
                'time_unit_id' => 1,
                'date_start' => $future_date_start,
                'date_end' => $future_date_end,
                'event_id' => 1,
                'group_alarm_id' => 2
            ])->execute();

        $result = XmlController::getSmsSendingList(1, 1);
        $expected = ['+79050788309'];
        $this->assertEquals($expected, $result);
    }

    /**
     * Тестируем: XmlController::getEmailSendingList
     * По сценарию: в таблице нет адресов для рассылки по данному событию
     * Ожидаем: false
     */
    public function testGetEmptyEmailSendingListReturnsFalse()
    {
        $result = XmlController::getEmailSendingList(32167);
        $this->assertFalse($result);
    }

    /**
     * Тестируем: XmlController::getEmailSendingList
     * По сценарию: в таблице только один адрес, удовлетворяющий условиям
     * Ожидаем: массив из одного элемента
     */
    public function testGetEmailSendingListWithOneAddress()
    {
        Yii::$app->db->createCommand()
            ->insert('event', [
                'id' => 1,
                'title' => 'Unit Test Event',
                'object_id' => 33
            ])->execute();

        Yii::$app->db->createCommand()
            ->insert('xml_model', [
                'id' => 2,
                'title' => 'Unit Test Email'
            ])->execute();

        $date_start = date('Y-m-d H:i:s', strtotime('-1 day'));
        $date_end = date('Y-m-d H:i:s', strtotime('+1 day'));

        // Актуальный номер
        Yii::$app->db->createCommand()
            ->insert('xml_config', [
                'id' => 10000,
                'xml_model_id' => 2,
                'xml_send_type_id' => 1,
                'address' => 'test@mail.ru',
                'time_period' => 0,
                'time_unit_id' => 1,
                'date_start' => $date_start,
                'date_end' => $date_end,
                'event_id' => 1
            ])->execute();

        $result = XmlController::getEmailSendingList(1);
        $expected = ['test@mail.ru'];
        $this->assertEquals($expected, $result);
    }

    /**
     * Тестируем: XmlController::getEmailSendingList
     * По сценарию: в таблице n адресов, удовлетворяющих условиям.
     * Ожидаем: массив из адресов
     */
    public function testGetEmailSendingListWithNAddresses()
    {
        Yii::$app->db->createCommand()
            ->insert('event', [
                'id' => 1,
                'title' => 'Unit Test Event',
                'object_id' => 33
            ])->execute();

        Yii::$app->db->createCommand()
            ->insert('xml_model', [
                'id' => 2,
                'title' => 'Unit Test Email'
            ])->execute();

        $date_start = date('Y-m-d H:i:s', strtotime('-1 day'));
        $date_end = date('Y-m-d H:i:s', strtotime('+1 day'));

        // Актуальные номера
        Yii::$app->db->createCommand()
            ->insert('xml_config', [
                'id' => 10000,
                'xml_model_id' => 2,
                'xml_send_type_id' => 1,
                'address' => 'test@mail.ru',
                'time_period' => 0,
                'time_unit_id' => 1,
                'date_start' => $date_start,
                'date_end' => $date_end,
                'event_id' => 1
            ])->execute();
        Yii::$app->db->createCommand()
            ->insert('xml_config', [
                'id' => 20000,
                'xml_model_id' => 2,
                'xml_send_type_id' => 1,
                'address' => 'unit@gmail.ru',
                'time_period' => 0,
                'time_unit_id' => 1,
                'date_start' => $date_start,
                'date_end' => $date_end,
                'event_id' => 1
            ])->execute();

        // Адрес рассылки с истёкшей датой
        $expired_date_start = date('Y-m-d H:i:s', strtotime('-2 day'));
        $expired_date_end = date('Y-m-d H:i:s', strtotime('-1 day'));
        Yii::$app->db->createCommand()
            ->insert('xml_config', [
                'id' => 30000,
                'xml_model_id' => 2,
                'xml_send_type_id' => 1,
                'address' => 'expired@ramler.ri',
                'time_period' => 0,
                'time_unit_id' => 1,
                'date_start' => $expired_date_start,
                'date_end' => $expired_date_end,
                'event_id' => 1
            ])->execute();

        // Адрес рассылки с ещё не настпившей датой начала
        $future_date_start = date('Y-m-d H:i:s', strtotime('+1 day'));
        $future_date_end = date('Y-m-d H:i:s', strtotime('+2 day'));
        Yii::$app->db->createCommand()
            ->insert('xml_config', [
                'id' => 40000,
                'xml_model_id' => 2,
                'xml_send_type_id' => 1,
                'address' => 'future@ss.dd',
                'time_period' => 0,
                'time_unit_id' => 1,
                'date_start' => $future_date_start,
                'date_end' => $future_date_end,
                'event_id' => 1
            ])->execute();

        $result = XmlController::getEmailSendingList(1);
        $expected = ['test@mail.ru', 'unit@gmail.ru'];
        $this->assertEquals($expected, $result);
    }

    /**
     * Тестируем: XmlController::getEmailSendingList
     * По сценарию: в таблице n адресов, удовлетворяющих условиям.
     * Ожидаем: массив из адресов
     */
    public function testGetEmailSendingListWithNAddressesByGroup()
    {
        Yii::$app->db->createCommand()
            ->insert('event', [
                'id' => 1,
                'title' => 'Unit Test Event',
                'object_id' => 33
            ])->execute();

        Yii::$app->db->createCommand()
            ->insert('xml_model', [
                'id' => 2,
                'title' => 'Unit Test Email'
            ])->execute();

        $date_start = date('Y-m-d H:i:s', strtotime('-1 day'));
        $date_end = date('Y-m-d H:i:s', strtotime('+1 day'));

        // Актуальные номера
        Yii::$app->db->createCommand()
            ->insert('xml_config', [
                'id' => 10000,
                'xml_model_id' => 2,
                'xml_send_type_id' => 1,
                'address' => 'test@mail.ru',
                'time_period' => 0,
                'time_unit_id' => 1,
                'date_start' => $date_start,
                'date_end' => $date_end,
                'event_id' => 1,
                'group_alarm_id' => 1
            ])->execute();
        Yii::$app->db->createCommand()
            ->insert('xml_config', [
                'id' => 20000,
                'xml_model_id' => 2,
                'xml_send_type_id' => 1,
                'address' => 'unit@gmail.ru',
                'time_period' => 0,
                'time_unit_id' => 1,
                'date_start' => $date_start,
                'date_end' => $date_end,
                'event_id' => 1,
                'group_alarm_id' => 2
            ])->execute();

        // Адрес рассылки с истёкшей датой
        $expired_date_start = date('Y-m-d H:i:s', strtotime('-2 day'));
        $expired_date_end = date('Y-m-d H:i:s', strtotime('-1 day'));
        Yii::$app->db->createCommand()
            ->insert('xml_config', [
                'id' => 30000,
                'xml_model_id' => 2,
                'xml_send_type_id' => 1,
                'address' => 'expired@ramler.ri',
                'time_period' => 0,
                'time_unit_id' => 1,
                'date_start' => $expired_date_start,
                'date_end' => $expired_date_end,
                'event_id' => 1,
                'group_alarm_id' => 1
            ])->execute();

        // Адрес рассылки с ещё не настпившей датой начала
        $future_date_start = date('Y-m-d H:i:s', strtotime('+1 day'));
        $future_date_end = date('Y-m-d H:i:s', strtotime('+2 day'));
        Yii::$app->db->createCommand()
            ->insert('xml_config', [
                'id' => 40000,
                'xml_model_id' => 2,
                'xml_send_type_id' => 1,
                'address' => 'future@ss.dd',
                'time_period' => 0,
                'time_unit_id' => 1,
                'date_start' => $future_date_start,
                'date_end' => $future_date_end,
                'event_id' => 1,
                'group_alarm_id' => 2
            ])->execute();

        $result = XmlController::getEmailSendingList(1, 1);
        $expected = ['test@mail.ru'];
        $this->assertEquals($expected, $result);
    }
}
