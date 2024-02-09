<?php


use backend\controllers\CoordinateController;
use Codeception\Test\Unit;


class CoordinateControllerTest extends Unit
{
    /**
     * Тестируем: CoordinateController::calculateSpeed
     * По сценарию: в метод переданы аргументы в неверном формате
     * Ожидаем: Исключение с сообщением "Входные данные не соответствуют формату"
     */
    public function testCalculateSpeedInvalidArgumentsThrowsException()
    {
        try {
            $result = CoordinateController::calculateSpeed(
                'Data', '0-0-0 0:0:0',
                '0.0,0.0,5.0', 'Set'
            );
        } catch (\Exception $exception) {
            $expected = 'Входные данные не соответствуют формату';
            $actual = $exception->getMessage();
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * Тестируем: CoordinateController::calculateSpeed
     * По сценарию: в метод переданы точки с одинаковыми метками времени
     * Ожидаем: Исключение с сообщением "Разница во времени равна 0"
     */
    public function testCalculateSpeedTimeDiffZeroThrowsException()
    {
        try {
            $result = CoordinateController::calculateSpeed(
                '0.0,0.0,0.0', '0-0-0 0:0:0',
                '80.0,50.0,5.0', '0-0-0 0:0:0'
            );
        } catch (\Exception $exception) {
            $expected = 'Разница во времени равна 0';
            $actual = $exception->getMessage();
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * Тестируем: CoordinateController::calculateSpeed
     * По сценарию: в метод переданы точки с разницей времени > 1 минуты
     * Ожидаем: Исключение с сообщением "Время между точками превышает 1 минуту"
     */
    public function testCalculateSpeedTimeDiffOverMinuteThrowsException()
    {
        try {
            $result = CoordinateController::calculateSpeed(
                '0.0,0.0,0.0', '0-0-0 0:0:0',
                '80.0,50.0,5.0', '0-0-0 1:0:0'
            );
        } catch (\Exception $exception) {
            $expected = 'Время между точками превышает 1 минуту';
            $actual = $exception->getMessage();
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * Тестируем: CoordinateController::calculateSpeed
     * По сценарию: человек прошёл больше 300 метров
     * Ожидаем: Исключение с сообщением "Пройденное расстояние превышает 300 метров"
     */
    public function testCalculateSpeedDistanceOver300ThrowsException()
    {
        try {
            $result = CoordinateController::calculateSpeed(
                '0.0,0.0,0.0', '0-0-0 0:0:0',
                '1000.0,1000.0,1000.0', '0-0-0 0:1:0'
            );
        } catch (\Exception $exception) {
            $expected = 'Пройденное расстояние превышает 300 метров';
            $actual = $exception->getMessage();
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * Тестируем: CoordinateController::calculateSpeed
     * По сценариям:
     *  1) человек прошёл 5 метров за 5 секунд;
     *  2) Человек прошёл ~5.39 метров за 6 секунд
     * Ожидаем:
     *  1) Скорость человека = 1 м/с
     *  2) Скорость человека = ~0.9 м/с
     */
    public function testCalculateSpeedCorrect()
    {
        $result = CoordinateController::calculateSpeed(
            '0.0,0.0,0.0', '0-0-0 0:0:0',
            '0.0,0.0,5.0', '0-0-0 0:0:5'
        );
        $this->assertEquals(1, $result);

        $result = CoordinateController::calculateSpeed(
            '1.0,1.0,2.0', '0-0-0 0:0:0.3215',
            '3.0,4.0,6.0', '0-0-0 0:0:6.123458'
        );
        $this->assertEquals(0.9, $result);
    }

    /**
     * Тестируем: CoordinateController::buildMineGraphCacheKey
     * По сценарию:
     *   На вход функции подан корректный аргумент
     * Ожидаем:
     *   Сформированный ключ для кэша
     */
    public function testBuildMineGraphCacheKey()
    {
        $mine_id = 290;
        $expected = CoordinateController::MINE_GRAPH_CACHE_BASE . ':' . $mine_id;
        $result = CoordinateController::buildMineGraphCacheKey($mine_id);
        $this->assertEquals($expected, $result);
    }

    /**
     * Тестируем: CoordinateController::buildEdgesCacheKey
     * По сценарию:
     *   На вход функции подан корректный аргумент
     * Ожидаем:
     *   Сформированный ключ для кэша
     */
    public function testBuildEdgesCacheKey()
    {
        $mine_id = 290;
        $expected = CoordinateController::EDGES_CACHE_BASE . ':' . $mine_id;
        $result = CoordinateController::buildEdgesCacheKey($mine_id);
        $this->assertEquals($expected, $result);
    }

    /**
     * Тестируем: CoordinateController::buildSensorGraphCacheKey
     * По сценарию:
     *   На вход функции подан корректный аргумент
     * Ожидаем:
     *   Сформированный ключ для кэша
     */
    public function testBuildSensorGraphCacheKey()
    {
        $sensor_id = 32167;
        $expected = CoordinateController::SENSOR_GRAPH_CACHE_BASE . ':' . $sensor_id;
        $result = CoordinateController::buildSensorGraphCacheKey($sensor_id);
        $this->assertEquals($expected, $result);
    }

    /**
     * Тестируем: CoordinateController::calculateProjectionDotOnEdge
     * По сценарию:
     *   На вход функции поданы корректный аргументы
     * Ожидаем:
     *   Координаты точки, снормированной на ребро
     */
    public function testCalculateProjectionDotOnEdgeWithCorrectArguments()
    {
        $dot = array(
            'x' => 3, 'y' => 3, 'z' => 0
        );

        $edge = array(
            'xStart' => 0, 'yStart' => 0, 'zStart' => 0,
            'xEnd' => 7, 'yEnd' => 0, 'zEnd' => 0,
        );

        $expected = array(
            'x' => 3, 'y' => 0, 'z' => 0
        );

        $result = CoordinateController::calculateProjectionDotOnEdge($dot, $edge)['normal_need_dot'];
        $this->assertEquals($expected, $result);
    }

    /**
     * Тестируем: CoordinateController::calculateProjectionDotOnEdge
     * По сценарию:
     *   На вход функции поданы корректный аргументы
     * Ожидаем:
     *   Координаты точки, снормированной на ребро
     */
    public function testCalculateProjectionDotOnEdgeOutOfEdge()
    {
        $dot = array(
            'x' => 2, 'y' => 2, 'z' => 0
        );

        $edge = array(
            'xStart' => 4, 'yStart' => 1, 'zStart' => 0,
            'xEnd' => 7, 'yEnd' => 1, 'zEnd' => 0,
        );

        $result = CoordinateController::calculateProjectionDotOnEdge($dot, $edge);
        $this->assertEquals(0, $result['status']);
        $this->assertEmpty($result['normal_need_dot']);
    }

    /**
     * Тестируем: CoordinateController::calculateProjectionDotOnEdge
     * По сценарию:
     *   Координаты начала и конца выработки, на которой вычисляем проекцию точки,
     *   совпадают
     * Ожидаем:
     *   Выработка является точкой, поэтому ожидаем координаты начала выработки
     */
    public function testCalculateProjectionDotOnEdgeWithZeroLength()
    {
        $dot = array(
            'x' => 3, 'y' => 3, 'z' => 0
        );

        $edge = array(
            'xStart' => 0, 'yStart' => 0, 'zStart' => 0,
            'xEnd' => 0, 'yEnd' => 0, 'zEnd' => 0,
        );

        $expected = array(
            'x' => 0, 'y' => 0, 'z' => 0
        );

        $result = CoordinateController::calculateProjectionDotOnEdge($dot, $edge)['normal_need_dot'];
        $this->assertEquals($expected, $result);
    }

    /**
     * Тестируем: CoordinateController::calculateProjectionDotOnEdge
     * По сценарию:
     *   На вход функции подаём невалидные аргументы
     * Ожидаем:
     *   Статус в результирующем массиве будет = 0 (из-за организации структуры кода проекта)
     */
    public function testCalculateProjectionDotOnEdgeWithInvalidArgumentsThrowsException()
    {
        $dot = array(
            'x' => 3, 'y' => 3.45, 'z' => 'string'
        );

        $edge = array(
            'xStart' => 'What', 'yStart' => 'is', 'zStart' => 'wrong',
            'xEnd' => 'with', 'yEnd' => 'you', 'zEnd' => '?',
        );

        $expected_status = 0;
        $expected_normal_need_dot = array();
        $expected_error_msg = 'calculateProjectionDotOnEdge. Исключение';

        $result = CoordinateController::calculateProjectionDotOnEdge($dot, $edge);
        $this->assertEquals($expected_status, $result['status']);
        $this->assertEquals($expected_normal_need_dot, $result['normal_need_dot']);
        $this->assertContains($expected_error_msg, $result['errors']);
    }

    /**
     * Тестируем: CoordinateController::calculateDistanceToVector
     * По сценарию:
     *   На вход функции подаём валидные аргументы
     * Ожидаем:
     *   Расстояние от точки до вектора
     */
    public function testCalculateDistanceToVectorWithCorrectArguments()
    {
        $dot = array(
            'x' => 3, 'y' => 3, 'z' => 0
        );

        $vector_start = array(
            'x' => 0, 'y' => 0, 'z' => 0
        );

        $vector_end = array(
            'x' => 7, 'y' => 0, 'z' => 0,
        );

        $expected_distance = 3;
        $result = CoordinateController::calculateDistanceToVector($dot, $vector_start, $vector_end);
        $this->assertEquals($expected_distance, $result);
    }

    /**
     * Тестируем: CoordinateController::calculateDistanceToVector
     * По сценарию:
     *   На вход функции подаём невалидные аргументы
     * Ожидаем:
     *   Выброс исключения
     */
    public function testCalculateDistanceToVectorWithInvalidArguments()
    {
        $dot = array(
            'x' => 3, 'y' => 3.46, 'z' => 'string'
        );

        $vector_start = array(
            'x' => 'i', 'y' => 'j', 'z' => 'k'
        );

        $vector_end = array(
            'x' => 7, 'y' => 0, 'z' => 0,
        );

        try {
            CoordinateController::calculateDistanceToVector($dot, $vector_start, $vector_end);
        } catch (Throwable $exception) {
            fwrite(STDOUT, print_r($exception->getMessage(), true));
            return;
        }

        $this->fail('Exception was not raised');
    }

    /**
     * Тестируем: CoordinateController::calculateDistanceToVector
     * По сценарию:
     *   Нахождение расстояние до вектора, у которого совпадают координаты
     *   начала и конца
     * Ожидаем:
     *   Т.к. вектор в данном случае является точкой, то находится расстояние
     *   между двумя точками
     */
    public function testCalculateDistanceToVectorWithZeroLength()
    {
        $dot = array(
            'x' => 3, 'y' => 1, 'z' => 1
        );

        $vector_start = array(
            'x' => 1, 'y' => 1, 'z' => 1
        );

        $vector_end = array(
            'x' => 1, 'y' => 1, 'z' => 1,
        );

        $expected_distance = 2;
        $result = CoordinateController::calculateDistanceToVector($dot, $vector_start, $vector_end);
        $this->assertEquals($expected_distance, $result);
    }

    /**
     * Тестируем: CoordinateController::findPossibleDotsCombinations
     *   Т.к. функция только составляет комбинации и не зависит от типа элементов
     *   в массивах, то можно подавать массивы с произвольнымы типами аргументов.
     * По сценарию:
     *   Даны два массива:
     *     array('A1', 'A2', 'A3')
     *     array('B1', 'B2')
     * Ожидаем:
     *   Множество комбинаций точек:
     *     array(
     *       array('A1', 'B1')
     *       array('A1', 'B2')
     *       array('A2', 'B1')
     *       array('A2', 'B2')
     *       array('A3', 'B1')
     *       array('A3', 'B2')
     *     )
     */
    public function testFindPossibleDotsCombinations()
    {
        $possible_dots_on_nodes = array(
            ['A1', 'A2', 'A3'],
            ['B1', 'B2']
        );

        $expected = array(
            ['A1', 'B1'],
            ['A1', 'B2'],
            ['A2', 'B1'],
            ['A2', 'B2'],
            ['A3', 'B1'],
            ['A3', 'B2'],
        );

        $result = CoordinateController::findPossibleDotsCombinations($possible_dots_on_nodes)['combinations'];
        $this->assertEquals($expected, $result);
    }
}
