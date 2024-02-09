<?php

use \backend\controllers\sms\SmsSender;

class SmsTest extends \Codeception\Test\Unit
{
    /**
     * Тестируем: SmsSender::encodeToSmsString
     * По сценарию: парсинг строки для СМС сообщения.
     *   Строка содержит кириллицу и спецсимволы.
     * Ожидаем: строка, переведённая в транслит, без символов в скобках, с
     *   одним пробелом между словами.
     */
    public function testEncodeToSmsString()
    {
        $this->assertEquals('Z-LUCh-4 No 1-85', SmsSender::encodeToSmsString('Z-ЛУЧ-4 № 1-85 (Net ID 660351)'));
        $this->assertEquals('PKLU bloka No1', SmsSender::encodeToSmsString('ПКЛУ блока №1'));
        $this->assertEquals('ABC outside', SmsSender::encodeToSmsString('ABC (Test1(even deeper) yes (this (works) too)) outside (((ins)id)e)'));
        $this->assertEmpty(SmsSender::encodeToSmsString(''));
    }
}
