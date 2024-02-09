<?php

use backend\controllers\StrataJobController;

class StrataControllerTest extends \Codeception\Test\Unit
{
    /**
     * Тестируем: StrataJobController::getShiftDateNum
     * По сценарию:
     *   В метод переданы корректные аргументы
     * Ожидаем:
     *   Получить дату и номер смены
     */
    public function testGetShiftDateNumWithCorrectArguments()
    {
        $expected = array(
            'shift_date' => '2019-07-31',
            'shift_num' => 4
        );
        $result = StrataJobController::getShiftDateNum('2019-08-01 04:51:14');
        $this->assertEquals($expected, $result);
    }

}
