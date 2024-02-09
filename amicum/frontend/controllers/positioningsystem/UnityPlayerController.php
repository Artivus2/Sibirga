<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\positioningsystem;

use backend\controllers\Assistant as BackEndAssistant;
use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\SensorBasicController;
use frontend\controllers\Assistant;
use PHP_CodeSniffer\Util\Cache;
use Yii;
use yii\db\Query;
use yii\web\Response;

class UnityPlayerController extends \yii\web\Controller
{
    // actionGetWorkers                 - Метод получения списка работников по историческим данным
    // actionWorkerMovementHistory      - Метод получения списка передвижения работника, по времени и до указанной даты для построения маршрута в 3D схеме
    // actionGetSensors                 - МЕТОД ПОЛУЧЕНИЯ ИСТОРИЧЕСКИХ ДАННЫХ СПИСКА СЕНСОРОВ
    // actionGetSensorsParameters       - Метод получения исторических данных значений параметров сенсора по периоду и до указанной даты.
    // GetWorkersHistoricalData         - МЕТОД ПОЛУЧЕНИЯ ИСТОРИЧЕСКИХ ДАННЫХ РАБОТНИКА(ОВ) ДО УКАЗАННОЙ ДАТЫ
    // GetWorkersHistoricalDataPeriod   - МЕТОД ПОЛУЧЕНИЯ ИСТОРИЧЕСКИХ ДАННЫХ ДАТЧИКА(ОВ) ДО УКАЗАННОГО ПЕРИОДА
    // GetWorkersParametersLastValues   - Метод получения данных работников до указанной даты (последние до указанной даты)
    // GetWorkersParametersValuesPeriod - Метод получения данных работников до указанной даты (последние до указанной даты)
    // actionGetWorkersParameters       - Метод получения исторических данных значений параметров работника по периоду и до указанной даты.
    // actionGetEquipments              - МЕТОД ПОЛУЧЕНИЯ ИСТОРИЧЕСКИХ ДАННЫХ СПИСКА ОБОРУДОВАНИЯ
    // actionGetEquipmentsParameters    - Метод получения исторических данных значений параметров оборудования по периоду и до указанной даты.
    // actionEquipmentMovementHistory   - Метод получения списка передвижения оборудования, по времени и до указанной даты для построения маршрута в 3D схеме

    /**
     * Название метода: actionGetWorkers()
     * Метод получения списка работников по историческим данным
     * Метод можно вызвать с помощью GET/POST запросов.
     * Если указан GET, то POST не принимается
     * Входные параметры:
     *      Необязательные:
     *          date_time - При указание лишь date_time, выводится информация, до данной даты. Если не указать, то
     *          возвращает последние данные которые есть без условии даты и времени
     *          worker_id - id работника, по которому будет выведена информация. Если не указать, то возвращает всех работников
     *          mine_id - идентфииктор шахты. Если не указать, то возвращает по всем шахтам
     * POST - http://localhost/unity-player/get-workers - возвращает всех работников по параметру 346 (шахта) только по последним данным
     * GET - http://localhost/unity-player/get-workers?mine_id=290&date_time=2018-10-10 - последние данные до указанной даты
     * GET(по конкретному работнику) - http://localhost/unity-player/get-workers?worker_id=1801
     * GET(по конкретному работнику и по конкретной шахте) - http://localhost/unity-player/get-workers?worker_id=1801&mine_id=290
     * GET(по конкретному работнику и по конкретной шахте и до указанной даты ) -
     * http://localhost/unity-player/get-workers?worker_id=1801&mine_id=290&date_time=2018-12-31
     * Created by: Одилов О.У. on 12.12.2018 16:58
     */
    public function actionGetWorkers()
    {
        $post = Assistant::GetServerMethod();
        $errors = array();
        $warnings = array();
        $date_time = '';         //Флаг даты окончания (используется в промежутках)
        $worker_id = -1;
        $worker_condition = '';
        $mine_id = -1;
        $warnings[] = 'actionGetWorkers. Начал выполнять метод';
        if (isset($post['worker_id']) && $post['worker_id'] != '')                                                      //Проверка, на заданный worker_id
        {
            $worker_id = $post['worker_id'];
            $worker_condition = Assistant::AddConditionOperator($worker_condition, " worker.id = $worker_id");
            $warnings[] = "actionGetWorkers. Проверил входные параметры. Worker_id задан и равен $worker_id";
        } else {
            $warnings[] = 'actionGetWorkers. Проверил входные параметры. Worker_id не задан';
        }

        if (isset($post['date_time']) && $post['date_time'] != '')                                                      //Проверка даты
        {
            $date_time = Assistant::GetDateWithMicroseconds($post['date_time']);
            $warnings[] = "actionGetWorkers. Проверил входные параметры. date_time задан и равен $date_time";
        } else {
            $warnings[] = 'actionGetWorkers. Проверил входные параметры. date_time не задан';
        }

        if (isset($post['mine_id']) && $post['mine_id'] != '')                                                      //Проверка, на заданный worker_id
        {
            $mine_id = $post['mine_id'];
            $worker_condition = Assistant::AddConditionOperator($worker_condition, " mine.id = $mine_id ", 'AND');
            $warnings[] = "actionGetWorkers. Проверил входные параметры. mine_id задан и равен $mine_id";
        } else {
            $warnings[] = 'actionGetWorkers. Проверил входные параметры. mine_id не задан';
        }
        $warnings[] = 'actionGetWorkers. Запускаю запрос к БД';
        $workers = Assistant::CallProcedure("GetWorkersMine('$worker_condition', '$date_time')");
        if (!$workers) {
            $warnings[] = 'actionGetWorkers. Запрос не нашел данных по заданному условию';
            $errors[] = 'Нет данных по заданному условию';
        } else {
            $warnings[] = 'actionGetWorkers. Данные есть, смотри результат';
        }

        $warnings[] = 'actionGetWorkers. Закончил выполнять метод';
        $result = array('Items' => $workers, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /***
     * Метод получения списка передвижения работника, по времени и до указанной даты для построения маршрута в 3D схеме
     * Метод используется для построения пути передвижения.
     * Метод можно вызвать с помощью GET/POST запросов.
     * Если указать только дату начало (date_start), то ВОЗВРАЩАЕТ данные по периоду
     * Если указать только дату начало (date_start) и дату конец (date_end), то ВОЗВРАЩАЕТ последниЕ данныЕ до указанной даты
     * Если указан GET, то POST не принимается
     *  Входные параметры:
     *      Обязательные:
     *          mine_id - id шахты.
     *          date_start - Дата с какого момента будут показываться данные Значение до 1 секунды.
     *          worker_id - id работника, по которому будет выведена информация.
     *      Необязательные:
     *          date_end - дата до какого момента будут показываться данные. Значение до 1 секунды.
     * POST - http://localhost/unity-player/worker-movement-history
     * GET - http://localhost/unity-player/worker-movement-history?mine_id=290&date_start=2018-06-06%12:25:00&date_end=2018-11-30%12:25:00&worker_id=2913553 - возврат данных по периоду
     * GET - http://localhost/unity-player/worker-movement-history?mine_id=290&worker_id=2913553&date_start=2018-09-27%2007:09:06.936424 - возврат последних данных до указанной даты
     * Создан: Аксенов И.Ю.
     * Одилов О.У.
     * Добавил загрузку из процедуры
     * Добавил возможность получения последних данных до конкретной даты
     * Возвращает одномерный массив
     */
    public function actionWorkerMovementHistory()
    {
        $query = new Query();
        $post = Assistant::GetServerMethod();
        $errors = array();
        $history_movement = array();
        $warnings = array();
        $warnings[] = 'actionWorkerMovementHistory. Начал выполнять метод';
        if ((isset($post['mine_id']) && $post['mine_id'] != '')                                                         //Проверка, на заданные входные параметры
            && (isset($post['date_start']) && $post['date_start'] != '')
            && (isset($post['worker_id']) && $post['worker_id'] != '')) {

            $mine_id = $post['mine_id'];
            $worker_id = $post['worker_id'];
            $date_start = $post['date_start'];
            $date_end = $post['date_end'];
            $date_start = date('Y-m-d H:i:s', strtotime("$date_start -1 sec"));                            //Дата начала, берется с начала суток
            $date_end = date('Y-m-d H:i:s', strtotime("$date_end +1 sec"));                            //Дата окончания, берется с начала суток
            $warnings[] = 'actionWorkerMovementHistory. Получаю данные из вьюшки';
            $history_movements = $query
                ->select('worker_id, parameter_id, parameter_type_id, date_time as date_time, value')
                ->from('view_worker_movement_and_gaz_history')
                ->where("(date_time between '$date_start' and '$date_end')
                        and worker_id = $worker_id and value != '0.000000,0.000000,0.000000'")
                ->all();
            $history_movements = array_merge($history_movements, $query
                ->select('worker_id, parameter_id, parameter_type_id, date_time as date_time, value')
                ->from('view_worker_movement_and_gaz_history_archive')
                ->where("(date_time between '$date_start' and '$date_end')
                        and worker_id = $worker_id and value != '0.000000,0.000000,0.000000'")
                ->all());

            if ($history_movements) {
                foreach ($history_movements as $history_movement_item) {
                    $history_movement_nand[$history_movement_item['worker_id']][$history_movement_item['parameter_id']][$history_movement_item['parameter_type_id']][$history_movement_item['date_time']] = $history_movement_item;
                }
            }
            if (isset($history_movement_nand)) {
                foreach ($history_movement_nand as $worker) {
                    foreach ($worker as $parameter) {
                        foreach ($parameter as $parameter_type) {
                            foreach ($parameter_type as $date_time) {
                                $history_movement[] = $date_time;
                            }
                        }
                    }
                }
            }
        } else {
            $errors[] = 'Параметры не переданы';
        }

        $warnings[] = 'actionWorkerMovementHistory. Закончил выполнять метод';
        $result = array('Items' => $history_movement, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //формат возвращаемых данных json методом yii2
        Yii::$app->response->data = $result;                                                                          //сам возврат данных во фронт энд
    }


    /**
     * Название метода: actionGetSensors()
     * МЕТОД ПОЛУЧЕНИЯ ИСТОРИЧЕСКИХ ДАННЫХ СПИСКА СЕНСОРОВ
     * ДАННЫЕ ПОЛУЧАЮТСЯ ИЗ БД В ЗАВИСИМОСТИ ОТ ПОЗЗИЦИИ (83 ПАРАМЕТР) И МЕСТОПОЛОЖЕНИЯ (122 ПАРАМЕТР)
     * ПРИНИМАЕТ ПАРАМЕТРЫ МЕТОДОМ POST И GET
     * ЕСЛИ НЕ УКАЗАТЬ sensor_id , то возвращает все сенсоры до указанной даты
     * ЕСЛИ УКАЗАТЬ sensor_id , то возвращает данные одного сенсора до указанной даты
     * По умолчание возвращает данные последние для всех сенсоров
     * Если дату указать, то возвращает данные до укащанной даты
     * Входные параметры:
     *      Обязательные:
     *          mine_id - id шахты.
     *      Необязательные:
     *          sensor_id - вернет данные конкретного сенсора
     *          object_id - возвращает данные в зависимости от объекта сенсора (БПД, Узелы связи и тд)
     *          object_type_id - возвращает данные в зависимости от типа объекта сенсора (Датчики, Передача информации )
     * СПОСОБЫ ПОЛУЧЕНИЯ:
     * ДО УКАЗАННОЙ ДАТЫ (ВСЕ ДАТЧИКИ В ЗАВИСИМОСТИ ОТ ТИПА ОБЪЕКТА): http://localhost/unity-player/get-sensors?mine_id=290&date_time=2018-10-10&object_type_id=22
     * ДО УКАЗАННОЙ ДАТА ДЛЯ КОНКРЕТНОГО ДАТЧИКА  ДАТЧИКИ В ЗАВИСИМОСТИ ОТ ТИПА ОБЪЕКТА http://localhost/unity-player/get-sensors?mine_id=290&date_time=2018-10-10&object_type_id=22&sensor_id=6862
     * ДО УКАЗАННОЙ ДАТЫ (ВСЕ ДАТЧИКИ В ЗАВИСИМОСТИ ОТ ОБЪЕКТА): http://localhost/unity-player/get-sensors?mine_id=290&date_time=2018-10-10&object_id=49
     * ДО УКАЗАННОЙ ДАТА ДЛЯ КОНКРЕТНОГО ДАТЧИКА  ДАТЧИКИ В ЗАВИСИМОСТИ ОТ ОБЪЕКТА http://localhost/unity-player/get-sensors?mine_id=290&date_time=2018-10-10&object_id=49&sensor_id=6862
     * Created by: Одилов О.У. on 19.12.2018 11:12
     */
    public function actionGetSensors()
    {
        $post = Assistant::GetServerMethod();

        $errors = array();
        $warnings = array();
        $response = array();
        $sensors = array();
        $sensor_id = '*';

        $sensor_condition = '';
        $date_time = '';
        $warnings[] = 'actionGetSensors. Начал выполнять метод';
        if (isset($post['sensor_id']) && $post['sensor_id'] != '')                                                       //если передан sensor_id то записываем его в переменную
        {
            $sensor_id = $post['sensor_id'];
            $sensor_condition = " AND sensor.id = $sensor_id";
            $warnings[] = "actionGetSensors. Проверил входные данные. sensor_id есть и равен $sensor_id";
        } else {
            $warnings[] = 'actionGetSensors. Проверил входные данные. sensor_id нет';
        }

        if (isset($post['date_time']) && $post['date_time'] != '') {
            $date_time = Assistant::GetDateWithMicroseconds($post['date_time']);
            $warnings[] = "actionGetSensors. Проверил входные данные. date_time есть и равен $date_time";
        } else {
            $warnings[] = 'actionGetSensors. Проверил входные данные. date_time нет';
        }

        if (isset($post['object_id']) && $post['object_id'] != '') {
            $object_id = $post['object_id'];
            $sensor_condition .= " AND object.id = $object_id ";
            $warnings[] = "actionGetSensors. Проверил входные данные. object_id есть и равен $object_id";
        } else {
            $warnings[] = 'actionGetSensors. Проверил входные данные. object_id нет';
        }

        if (isset($post['object_type_id']) && $post['object_type_id'] != '') {
            $object_type_id = $post['object_type_id'];
            $sensor_condition .= " AND object.object_type_id = $object_type_id ";
            $warnings[] = "actionGetSensors. Проверил входные данные. object_type_id есть и равен $object_type_id";
        } else {
            $warnings[] = 'actionGetSensors. Проверил входные данные. object_type_id нет';
        }

        if (isset($post['mine_id']) && $post['mine_id'] != '') {
            $mine_id = $post['mine_id'];
            $warnings[] = "actionGetSensors. Проверил входные данные. mine_id есть и равен $mine_id";
            if ($date_time == '')                                                                                        // если дата не указана, то получаем данные из КЭША
            {
                if (!COD) {
                    $sensors_cache = (new SensorCacheController())->getSensorMineHash($mine_id, $sensor_id);
                } else {
                    $sensors_cache = SensorBasicController::getSensorMain($mine_id, $sensor_id);
                }
                if ($sensors_cache) {
                    foreach ($sensors_cache as $sensor) {
                        if ($sensor['sensor_id']) {
                            $sensors[] = $sensor;
                        }
                    }
                    $response[] = 'Получил данные из кэша, так как дата не указана ';
                    $warnings[] = 'actionGetSensors. Данные получены из кеша';
                } else {
                    $response[] = 'Кеш сенсоров пуст';
                    $warnings[] = 'actionGetSensors. кеш сенсоров пуст';
                }
            } else                                                                                                        // если указана дата, то получаем данные из БД
            {
                //todo переделать метод на новые методы
                $sensor_condition = " mine.id = $mine_id " . $sensor_condition;
                $sensors = Assistant::CallProcedure("GetSensorsMine('$sensor_condition','$date_time')");
                $response[] = 'Получил данные из БД с помощью процедур';
                $warnings[] = 'actionGetSensors. Данные получены из БАЗЫ ДАННЫХ';
            }
        } else {
            $errors[] = 'Не передан идентификатор шахты';
            $warnings[] = 'actionGetSensors. Проверил входные данные. mine_id нет';
            $errors['post'] = $post;
        }
        $warnings[] = 'actionGetSensors. Закончил выполнять метод';
        if (!$sensors) {
            $sensors = array();
        }
        $result = array('Items' => $sensors, 'errors' => $errors, 'response' => $response, 'warnings' => $warnings);
        //Yii::$app->response->format = Response::FORMAT_JSON;
        //Yii::$app->response->data = $result;
        return json_encode($result);
    }

// Закомментировал Сырцев А.П. 04.09.2019
// Если никому не нужно, то удалить
//    /**
//     * МЕТОД ПОЛУЧЕНИЯ ИСТОРИЧЕСКИХ ДАННЫХ ДАТЧИКА(ОВ) ДО УКАЗАННОЙ ДАТЫ
//     * @param $sensor_id - идентификатор датчика. Если указать -1, то вернет все данные датчиков до указанного дня
//     *             а если указать конкретный датчик, то вернет данные указанного датчика до указанной даты
//     * @param $date_time - дата и время. можно указать до миллисекнд
//     * @param $parameter_id - идентификатор параметра
//     * @param $object_id - это не совсем идентификатор объекта. Значение этой переменной будеть зависать от переменной $object_condition
//     * @param $object_condition - в этой переменной указаывается по какому столбцу производить поиск(например: object_id, object_type_id)
//     * @param $parameter_type_id - тип параметра
//     * @return array|mixed возвращает массив датчиков
//     * Created by: Одилов О.У. on 07.12.2018 9:40
//     */
//    public function GetSensorsHistoricalData($sensor_id, $object_id, $object_condition, $date_time, $parameter_id, $parameter_type_id)
//    {
//        $sensors = array();
//        /***** ПОЛУЧАЕМ ДАННЫЕ ВСЕХ ДАТЧИКОВ ДО УКАЗАННОЙ ДАТЫ *****/
//        if ($sensor_id == -1)                                                                                            // если не указанан конкретный сенсор, то возвращаем всех сенсоров
//        {
//            $sensors = Assistant::CallProcedure("GetSensorsHistoricalData(-1, $object_id, '$object_condition', '$date_time', $parameter_id, $parameter_type_id)");
//        } /***** ПОЛУЧАЕМ ДАННЫЕ ТОЛЬКО ОДНОГО ДАТЧИКА ДО УКАЗАННОЙ ДАТЫ *****/
//        else                                                                                                            // если указанан конкретный сенсор, то возвращаем только одного сенсора
//        {
//            $sensors = Assistant::CallProcedure("GetSensorsHistoricalData($sensor_id, $object_id, '$object_condition', '$date_time', $parameter_id, $parameter_type_id)");
//        }
////        $sensors = Assistant::SetObjectValueParameterType($sensors);
//        return $sensors;
//    }
//
//    /**
//     * МЕТОД ПОЛУЧЕНИЯ ИСТОРИЧЕСКИХ ДАННЫХ ДАТЧИКА(ОВ) ДО УКАЗАННОГО ПЕРИОДА
//     * @param $sensor_id - идентификатор датчика. Если указать -1, то вернет все данные датчиков ДО УКАЗАННОГО ПЕРИОДА
//     *             а если указать конкретный датчик, то вернет данные указанного датчика ДО УКАЗАННОГО ПЕРИОДА
//     * @param $date_time_start - начало дата/время. можно указать до миллисекнд
//     * @param $date_time_end - конец дата/время. можно указать до миллисекнд
//     * @param $parameter_id - идентификатор параметра
//     * @param $object_id - это не совсем идентификатор объекта. Значение этой переменной будеть зависать от переменной $object_condition
//     * @param $object_condition - в этой переменной указаывается по какому столбцу производить поиск(например: object_id, object_type_id)
//     * @param $parameter_type_id - тип параметра
//     * @return array|mixed возвращает массив датчиков
//     * Created by: Одилов О.У. on 07.12.2018 9:47
//     */
//    public function GetSensorsHistoricalDataPeriod($sensor_id, $object_id, $object_condition, $date_time_start, $date_time_end, $parameter_id, $parameter_type_id)
//    {
//        $sensors = array();
//        /***** ПОЛУЧАЕМ ДАННЫЕ ВСЕХ ДАТЧИКОВ ДО УКАЗАННОГО ПЕРИОДА *****/
//        if ($sensor_id == -1)                                                                                            // если не указанан конкретный сенсор, то возвращаем всех сенсоров
//        {
//            $sensors = Assistant::CallProcedure("GetSensorsHistoricalDataPeriod(-1, $object_id, '$object_condition', '$date_time_start', '$date_time_end',  $parameter_id, $parameter_type_id)");
//        } /***** ПОЛУЧАЕМ ДАННЫЕ ТОЛЬКО ОДНОГО ДАТЧИКА ДО УКАЗАННОГО ПЕРИОДА *****/
//        else                                                                                                            // если указанан конкретный сенсор, то возвращаем только одного сенсора
//        {
//            $sensors = Assistant::CallProcedure("GetSensorsHistoricalDataPeriod($sensor_id, $object_id, '$object_condition', '$date_time_start', '$date_time_end',  $parameter_id, $parameter_type_id)");
//        }
////        $sensors = Assistant::SetObjectValueParameterType($sensors);// это уже использовать не нужно
//        return $sensors;
//    }

    /**
     * Название метода: actionGetSensorsParameters()
     * Метод получения исторических данных значений параметров сенсора по периоду и до указанной даты.
     * Есть ограничение на получения данных по периоду, то есть больше 3 дней данные получить нельзя
     * Даныые получаем из БД, с помощью процедур Mysql.
     * Метод принимает следующие параметры
     * Необязательные поля:
     *      $post['parameters'] - пеерменная для хранения параметров и типов. Необходимо передать в виде "1-122, 2-83, 3-164, 1-105"
     *      Чтобы получить все параметры нужно указать parameters=""
     *      $post['date_time'] - дата/время/миллисекунды.
     *      $post['sensor_id'] - идентификатор датчика. Если его не указать, то вернет данные всех датчиков, иначе только для одного
     *      $post['date_time_end'] - то возвращает данные по периоду.
     *
     * Для того, чтобы получить данные по периоду нужно указать $post['date_time'] и $post['date_time_end']. и чтобы получить для конкретного сенсора, нужно sensor_id указать.
     * Если хотите получить все параметры, то в переменной нужно указать  $post['parameters'] = -1
     * Если хотите получить все сенсоры, то в переменную sensor_id можно и не отправлять
     *
     * Чтобы получить даныне для конкретного сенсора нужно указать конкретный сенсор. Например sensor_id=310 иначе указывать не нужно
     *
     * -----------------------------------------------------------------------------------------------------------------
     *
     * ПРИМЕРЫ ВЫЗОВА ДАННЫХ ПО УМОЛЧАНИЮ
     *
     * http://localhost/unity-player/get-sensors-parameters - возвращает для всех параметров последние данные по указанным по умолчанию параметрам
     * http://localhost/unity-player/get-sensors-parameters?mine_id=290 - возвращает для всех параметров последние данные по указанным по умолчанию параметрам
     *
     * Метод по умолчанию возвращает для следующих параметров. "83:1,83:2,164:3,447:2,448";
     * Указанные параметры для всех сенсоров (до указнной даты)
     * http://localhost/unity-player/get-sensors-parameters?date_time=2018-12-12
     *
     * Указанные параметры для конкретного ссенсора (до указнной даты)
     * http://localhost/unity-player/get-sensors-parameters?date_time=2018-12-12&sensor_id=26354
     *
     * Указанные параметры для всех сенсоров (ПО ПЕРИОДУ)
     * http://localhost/unity-player/get-sensors-parameters?date_time=2018-09-12&date_time=2018-12-12
     *
     * Указанные параметры для конкретного сенсора (ПО ПЕРИОДУ)
     * http://localhost/unity-player/get-sensors-parameters?date_time=2018-09-12&date_time=2018-12-12&sensor_id=26354
     *
     * -----------------------------------------------------------------------------------------------------------------
     *
     * ПРИМЕРЫ ПОЛУЧЕНИЯ ПО УКАЗАННОМУ ПЕРИОДУ
     *
     * По периоду (все сенсоры)
     * http://localhost/unity-player/get-sensors-parameters?date_time=2018-01-12&sensor_id=26358&date_time_end=2019-01-01
     * http://localhost/unity-player/get-sensors-parameters?parameters=83:1,83:2,164:3,447:2,448:2&date_time=2018-09-27%2010:16:51.118772&date_time_end=2018-09-27%2010:20:51.118772
     * По периоду (все сенсоры) с указанием конкретных параметров
     * http://localhost/unity-player/get-sensors-parameters?date_time=2018-01-12&parameters=83:1,83:2,164:3,447:2,448&sensor_id=26358&date_time_end=2019-01-01
     *
     * По периоду с указанием конертного сенсора
     * http://localhost/unity-player/get-sensors-parameters?date_time=2018-01-12&sensor_id=26358&date_time_end=2019-01-01&sensor_id=26358
     *
     * -----------------------------------------------------------------------------------------------------------------
     * ПРИМЕРЫ ПОЛУЧЕНИЯ ДО УКАЗАННОГО ДНЯ(ПОСЛЕДНИЕ ТОЛЬКО)
     *
     * Данные до указанного дня
     * http://localhost/unity-player/get-sensors-parameters?date_time=2018-12-12&parameters=83:1,83:2,164:3,447:2,448
     *
     * Данные до указанного дня для конкретного датчика
     * http://localhost/unity-player/get-sensors-parameters?date_time=2018-12-12&parameters=83:1,83:2,164:3,447:2,448&sensor_id=26358
     *
     * Данные до указанного дня для конкретного датчика и все параметры
     *  http://localhost/unity-player/get-sensors-parameters?date_time=2018-12-12&sensor_id=26358
     * Документация на портале: http://192.168.1.3/products/community/modules/forum/posts.aspx?&t=8&p=1#72
     * Created by: Одилов О.У. on 06.12.2018 16:48
     */
    public function actionGetSensorsParameters()
    {
//        ini_set('max_execution_time', 200);
//        ini_set('memory_limit', '300M');

        // Стартовая отладочная информация
        $method_name = 'actionGetSensorsParameters';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackEndAssistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        $post = Assistant::GetServerMethod();
        $date_limit = 3;
        $sensor_group_result = array();                                                                                                //массив ошибок
        $response = array();
        $date_time = '';
        $mine_id = -1;
        $sensors = array();
        $parameters = '1:83,2:83,3:164,2:447,2:448';
        $sensor_condition = '';
        $sensor_id = '*';
        $date_time_end = '';

        try {
            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            if (isset($post['sensor_id']) && $post['sensor_id'] != '')                                                       //если передан sensor_id то записываем его в переменную
            {
                $sensor_id = $post['sensor_id'];
                $sensor_condition = " sensor.id = $sensor_id ";
                $warnings[] = "actionGetSensorsParameters. Проверил входные данные. sensor_id есть и равен $sensor_id";
            } else {
                $warnings[] = 'actionGetSensorsParameters. Проверил входные данные. sensor_id нет';
            }
            if (isset($post['date_time_end']) && $post['date_time_end'] != '') {
                $date_time_end = Assistant::GetDateWithMicroseconds($post['date_time_end']);
                $warnings[] = "actionGetSensorsParameters. Проверил входные данные. date_time_end есть и равен $date_time_end";
            } else {
                $warnings[] = 'actionGetSensorsParameters. Проверил входные данные. date_time_end нет';
            }

            if (isset($post['parameters']) && $post['parameters'] != '') {
                $parameters = $post['parameters'];
                $warnings[] = "actionGetSensorsParameters. Проверил входные данные. parameters есть и равен $parameters";
            } else {
                $warnings[] = 'actionGetSensorsParameters. Проверил входные данные. parameters нет';
            }

            if (isset($post['mine_id']) && $post['mine_id'] != '') {
                $mine_id = $post['mine_id'];
                $warnings[] = "actionGetSensorsParameters. Проверил входные данные. mine_id есть и равен $mine_id";
            } else {
                $warnings[] = 'actionGetSensorsParameters. Проверил входные данные. mine_id нет';
            }

            if (isset($post['date_time']) && $post['date_time'] != '') {
                $date_time = Assistant::GetDateWithMicroseconds($post['date_time']);
                $warnings[] = "actionGetSensorsParameters. Проверил входные данные. date_time есть и равен $date_time";
            } else {
                $warnings[] = 'actionGetSensorsParameters. Проверил входные данные. date_time нет';
            }

            /************************* ПОЛУЧАЕМ ДАННЫЕ ДО УКАЗАННОЙ ДАТЫ   **************************************/
            if ($date_time_end == '')                                                                                        // если указано только одна дата, то возвращаем данные до указанной даты (без периода)
            {
                if ($date_time == '' && $mine_id != -1) {
                    $warnings[] = 'actionGetSensorsParameters. получаю данные из кеша';

//                    ini_set('max_execution_time', 300);
//                    ini_set('memory_limit', '1024M');

                    $warnings[] = 'actionGetSensorsParameters. Начал выполнять метод';

                    $microtime_start = microtime(true);

                    $warnings[] = 'actionGetSensorsParameters. Начал получать кеш сенсоров ' . $duration_method = round(microtime(true) - $microtime_start, 6);
                    if (!COD) {
                        $sensor_mines = (new SensorCacheController())->getSensorMineHash($mine_id, $sensor_id);
                    } else {
                        $sensor_mines = SensorBasicController::getSensorMain($mine_id, $sensor_id);
                    }
                    $warnings[] = 'actionGetSensorsParameters. получил кеш сенсоров ' . $duration_method = round(microtime(true) - $microtime_start, 6);
                    if ($sensor_mines) {
                        $warnings[] = 'actionGetSensorsParameters. кеш работников шахты есть';
                    } else {
                        throw new \Exception('actionGetSensorsParameters. кеш сенсоров шахты пуст');                                                                                  //ключ от фронт энда не получен, потому формируем ошибку
                    }

                    /** Отладка */
                    $description = 'Получил список сенсоров для возврата с кеша';                                                                      // описание текущей отладочной точки
                    $description = $method_name . ' ' . $description;
                    $warnings[] = $description;                                                                                     // описание текущей отладочной точки
                    $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
                    $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
                    $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
                    $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
                    $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
                    $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
                    $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
                    $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
                    $microtime_current = microtime(true);
                    /** Окончание отладки */

                    $sensor_parameters = array(
                        9,                  // температура
                        83,                 // координата
                        98,                 // СО
                        99,                 // СН4
                        20,                 // кислород
                        22,                 // запыленность
                        24,                 // скорость воздуха
                        26,                 // водород
                        164,                // состояние
                        386,                // Превышение концентрации метана
                        387,                // Превышение удельной доли углекислого газа
                        447,                // Процент уровня заряда батареи узла связи
                        448                 // Процент уровня заряда батареи метки
                    );

                    /**
                     * получаю все параметры всех воркеров из кеша и пепелопачиваю их метод надо переделать на запрос параметров, только нужных воркеров
                     */
//                    throw new \Exception('actionGetSensorsParameters. отладочный стоп');
//                    $full_parameters = (new SensorCacheController())->multiGetParameterValue('*', '*');

                    $filter_parameter = '(9, 20, 22, 24, 26, 83, 98, 99, 164, 386, 387, 447, 448)';

                    $full_parameters = (new Query())
                        ->select(['sensor_id', 'sensor_parameter_id', 'parameter_id', 'parameter_type_id', 'date_time', 'value', 'status_id'])
                        ->from('view_initSensorParameterHandbookValue')
                        ->andwhere('parameter_id in ' . $filter_parameter)
                        ->all();

                    $full_parameters = array_merge($full_parameters, (new Query())
                        ->select(['sensor_id', 'sensor_parameter_id', 'parameter_id', 'parameter_type_id', 'date_time', 'value', 'status_id'])
                        ->from('view_initSensorParameterValue')
                        ->andwhere('parameter_id in ' . $filter_parameter)
                        ->all());

                    /** Отладка */
                    $description = 'Получил данные с кеша';                                                                      // описание текущей отладочной точки
                    $description = $method_name . ' ' . $description;
                    $warnings[] = $description;                                                                                     // описание текущей отладочной точки
                    $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
                    $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
                    $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
                    $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
                    $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
                    $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
                    $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
                    $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
                    $microtime_current = microtime(true);
                    /** Окончание отладки */

                    if ($full_parameters) {

                        $warnings[] = 'actionGetSensorsParameters. Полный кеш параметров сенсоров получен';
                        foreach ($full_parameters as $full_parameter) {
                            $sensorList[$full_parameter['sensor_id']][$full_parameter['parameter_id']][$full_parameter['parameter_type_id']] = $full_parameter;
                        }
                    } else {
                        throw new \Exception('actionGetSensorsParameters. кеш параметров работников шахты пуст');
                    }

                    /**
                     * фильтруем только тех кто нужен
                     */
                    foreach ($sensor_mines as $sensor_mine) {
                        for ($i = 1; $i <= 3; $i++) {
                            foreach ($sensor_parameters as $sensor_parameter) {
                                if (isset($sensorList[$sensor_mine['sensor_id']][$sensor_parameter][$i]['value'])) {
                                    /**
                                     * блок фильтрации параметров стационарных датчиков газа
                                     */
                                    $sensor_result['sensor_id'] = $sensorList[$sensor_mine['sensor_id']][$sensor_parameter][$i]['sensor_id'];
                                    $sensor_result['object_id'] = $sensor_mine['object_id'];
                                    $sensor_result['object_type_id'] = $sensor_mine['object_type_id'];
                                    $sensor_result['sensor_parameter_id'] = $sensorList[$sensor_mine['sensor_id']][$sensor_parameter][$i]['sensor_parameter_id'];
                                    $sensor_result['parameter_id'] = $sensorList[$sensor_mine['sensor_id']][$sensor_parameter][$i]['parameter_id'];
                                    $sensor_result['parameter_type_id'] = $sensorList[$sensor_mine['sensor_id']][$sensor_parameter][$i]['parameter_type_id'];
                                    $sensor_result['date_time'] = $sensorList[$sensor_mine['sensor_id']][$sensor_parameter][$i]['date_time'];
                                    if (
                                        $sensor_result['parameter_id'] == 164 and
                                        $sensor_result['parameter_type_id'] == 3 and
                                        strtotime(Assistant::GetDateTimeNow()) - strtotime($sensor_result['date_time']) > 300
                                    ) {
                                        $sensor_result['value'] = -1;
                                    } else {
                                        $sensor_result['value'] = $sensorList[$sensor_mine['sensor_id']][$sensor_parameter][$i]['value'];
                                    }
                                    $sensors[] = $sensor_result;
                                }
                            }
                        }
                    }

                    unset($sensor_mines, $full_parameters);
                } else {
                    $warnings[] = "actionGetSensorsParameters. Условия перед отправкой запроса sensor_condition $sensor_condition";
                    $warnings[] = "actionGetSensorsParameters. Условия перед отправкой запроса parameters $parameters";
                    $warnings[] = "actionGetSensorsParameters. Условия перед отправкой запроса date_time $date_time";
                    $sensors = Assistant::GetSensorsParametersLastValues($sensor_condition, $parameters, $date_time);
                    if (!$sensors) {
                        $errors[] = 'Нет данных по заданному условию';
                        $warnings[] = 'actionGetSensorsParameters. Данных по сенсорам до указанной даты в БАЗЕ ДАННЫХ нет';
                    }
                    $response[] = "actionGetSensorsParameters. Получение данных датчика(ов) до $date_time";
                    $warnings[] = 'actionGetSensorsParameters. Получил данные до указанной даты из БАЗЫ ДАННЫХ';
                }
            } /************************* ПОЛУЧАЕМ ДАННЫЕ ПО УКАЗАННОМУ ПЕРИОДУ   **************************************/
            else                                                                                                            // если указано только две дата, то возвращаем данные до ДО УКАЗАННОГО ПЕРИОДА
            {
                $warnings[] = 'actionGetSensorsParameters. Получая данные из БД';
                $date_time_diff = Assistant::DateTimeDiff($date_time, $date_time_end, 'd');
                if ($date_time_diff > $date_limit) {
                    $errors[] = "actionGetSensorsParameters. Система не позволяет получить исторические даныне датчика(ов) по периоду больше $date_limit (трех) дней";
                    $warnings[] = 'actionGetSensorsParameters. Получение данных за указанный период возможно только за 3 дня';
                } else {
                    $query = new Query();
                    $warnings[] = 'actionGetSensorsParameters. Все ок начинаю запрос';
                    $parameters = '1-83,2-83,3-164,2-447,2-448';

                    $date_start = date('Y-m-d H:i:s', strtotime("$date_time -1 sec"));                            //Дата начала, берется с начала суток
                    $date_end = date('Y-m-d H:i:s', strtotime("$date_time_end +1 sec"));                            //Дата окончания, берется с начала суток
                    $warnings[] = 'actionGetSensorsParameters. Получаю данные из вьюшки';
                    $sensors = $query
                        ->select('sensor_id, parameter_id, parameter_type_id, date_time as date_time, value')
                        ->from('view_sensor_movement_and_gaz_history')
                        ->where("(date_time between '$date_start' and '$date_end')
                        and sensor_id = $sensor_id and value != '0.000000,0.000000,0.000000'")
                        ->all();
                    $sensors = array_merge($sensors, $query
                        ->select('sensor_id, parameter_id, parameter_type_id, date_time as date_time, value')
                        ->from('view_sensor_movement_and_gaz_history_archive')
                        ->where("(date_time between '$date_start' and '$date_end')
                        and sensor_id = $sensor_id and value != '0.000000,0.000000,0.000000'")
                        ->all());


                    if (!$sensors) {
                        $errors[] = 'actionGetSensorsParameters. Нет данных по заданному условию';
                        $warnings[] = 'actionGetSensorsParameters. Данные в БАЗЕ ДАННЫХ за указанный период не найдены';
                    }
                    $response[] = "actionGetSensorsParameters. Получение данных датчика(ов) по периоду с $date_time по $date_time_end";
                    $warnings[] = 'actionGetSensorsParameters. Получены данные за указанный период из БАЗЫ ДАННЫХ';
                }
            }
            /**
             * приводит формат данных к без NULL
             */
            if ($sensors) {
                foreach ($sensors as $sensor) {
//                    if (
//                    (
//                        $sensor['parameter_id'] != 164 and
//                        $sensor['object_id'] != 47 and
//                        $sensor['object_id'] != 48 and
//                        $sensor['object_id'] != 104
//                    ) and (
//                        $sensor['parameter_id'] != 98 and
//                        $sensor['parameter_id'] != 99 and
//                        $sensor['object_id'] != 46 and
//                        $sensor['object_id'] != 90 and
//                        $sensor['object_id'] != 45
//                    )
//                    ) {
                    $sensor_result['sensor_id'] = $sensor['sensor_id'];
                    $sensor_result['object_id'] = $sensor['object_id'];
                    $sensor_result['object_type_id'] = $sensor['object_type_id'];
                    $sensor_result['sensor_parameter_id'] = $sensor['sensor_parameter_id'];
                    $sensor_result['parameter_id'] = $sensor['parameter_id'];
                    $sensor_result['parameter_type_id'] = $sensor['parameter_type_id'];
                    $sensor_result['date_time'] = $sensor['date_time'];
                    if (
                        $sensor['value'] !== null and
                        $sensor['parameter_type_id'] == 3 and
                        (
                            $sensor['parameter_id'] == 9 or
                            $sensor['parameter_id'] == 20 or
                            $sensor['parameter_id'] == 22 or
                            $sensor['parameter_id'] == 24 or
                            $sensor['parameter_id'] == 26 or
                            $sensor['parameter_id'] == 98 or
                            $sensor['parameter_id'] == 99
                        )
                    ) {
                        $sensor_result['value'] = str_replace(",", ".", $sensor['value']);
                    } else if ($sensor['value'] !== null) {
                        $sensor_result['value'] = $sensor['value'];
                    } else {
                        $sensor_result['value'] = '';
                    }
                    $sensor_results[] = $sensor_result;

                    $sensor_group_result[$sensor['object_id']][$sensor['sensor_id']][$sensor['parameter_type_id']][$sensor['parameter_id']] = $sensor_result;
                    unset($sensor_result);
//                    }
                }
                $sensors = $sensor_results;
                unset($sensor_results);
            }
            /** Метод окончание */

            /** Метод окончание */


        } catch (\Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(BackEndAssistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);

        $warnings[] = 'actionGetSensorsParameters. Закончил выполнять метод';
        $result_main = array(
            'status' => $status,
            'Items' => $sensors,
            'groupItems' => $sensor_group_result,
            'errors' => $errors,
            'warnings' => $warnings,
            'response' => $response,
            'debug' => $debug
        );
        return json_encode($result_main);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = $result_main;
    }


    /*------------------------------   РАБОТА С ИСТОРИЧЕСКИМИ ДАННЫМИ РАБОТНИКОВ  ------------------------------------*/

    /**
     * МЕТОД ПОЛУЧЕНИЯ ИСТОРИЧЕСКИХ ДАННЫХ РАБОТНИКА(ОВ) ДО УКАЗАННОЙ ДАТЫ
     * @param $worker_id - идентификатор датчика. Если указать -1, то вернет все данные РАБОТНИКОВ до указанного дня
     *             а если указать конкретный датчик, то вернет данные указанного РАБОТНИКА до указанной даты
     * @param $date_time - дата и время. можно указать до миллисекунд
     * @return array|mixed возвращает массив датчиков
     * Created by: Одилов О.У. on 07.12.2018 10:37
     */
    public
    function GetWorkersHistoricalData($worker_id, $date_time)
    {
        $workers = array();
        /***** ПОЛУЧАЕМ ДАННЫЕ ВСЕХ РАБОТНИКОВ ДО УКАЗАННОЙ ДАТЫ *****/
        if ($worker_id == -1)                                                                                            // если не указанан конкретный сенсор, то возвращаем всех сенсоров
        {
            $workers = Assistant::CallProcedure("GetWorkersHistoricalDataAllParameters(-1, '$date_time')");
        } /***** ПОЛУЧАЕМ ДАННЫЕ ТОЛЬКО ОДНОГО РАБОТНИКА ДО УКАЗАННОЙ ДАТЫ *****/
        else                                                                                                            // если указанан конкретный сенсор, то возвращаем только одного сенсора
        {
            $workers = Assistant::CallProcedure("GetWorkersHistoricalDataAllParameters($worker_id, '$date_time')");
        }
//        $workers = Assistant::SetObjectValueParameterType($workers);
        return $workers;
    }

    /**
     * МЕТОД ПОЛУЧЕНИЯ ИСТОРИЧЕСКИХ ДАННЫХ ДАТЧИКА(ОВ) ДО УКАЗАННОГО ПЕРИОДА
     * @param $worker_id - идентификатор датчика. Если указать -1, то вернет все данные датчиков ДО УКАЗАННОГО ПЕРИОДА
     *             а если указать конкретный датчик, то вернет данные указанного датчика ДО УКАЗАННОГО ПЕРИОДА
     * @param $date_time_start - начало дата/время. можно указать до миллисекнд
     * @param $date_time_end - конец дата/время. можно указать до миллисекнд
     * @return array|mixed возвращает массив датчиков
     * Created by: Одилов О.У. on 07.12.2018 9:47
     */
    public
    function GetWorkersHistoricalDataPeriod($worker_id, $date_time_start, $date_time_end)
    {
        $workers = array();
        /***** ПОЛУЧАЕМ ДАННЫЕ ВСЕХ РАБОТНИКОВ ДО УКАЗАННОГО ПЕРИОДА *****/
        if ($worker_id == -1)                                                                                            // если не указанан конкретный работник, то возвращаем всех работников
        {
            $workers = Assistant::CallProcedure("GetWorkersHistoricalDataPeriodAllParameters(-1, '$date_time_start', '$date_time_end')");
        } /***** ПОЛУЧАЕМ ДАННЫЕ ТОЛЬКО ОДНОГО РАБОТНИКА ДО УКАЗАННОГО ПЕРИОДА*****/
        else                                                                                                            // если указанан конкретный работник, то возвращаем только одного работника
        {
            $workers = Assistant::CallProcedure("GetWorkersHistoricalDataPeriodAllParameters($worker_id, '$date_time_start', '$date_time_end')");
        }
//        $workers = Assistant::SetObjectValueParameterType($workers);
        return $workers;
    }


    /**
     * Название метода: GetWorkersParametersLastValues()
     * Метод получения данных работников до указанной даты (последние до указанной даты)
     * Метод вызывает процедуру GetWorkersParametersLastValuesOptimized();
     * @param $worker_condition - условие фильтра для таблицы worker и worker_object.
     *  Например: $worker_condition = "worker_id = 100075 and worker_object.id = 33 OR и тд'
     *  Если оставить пустым то возвращает всех работников.
     * @param $parameter_condition - условие фильтра для worker_parameter.
     *  Например: $parameter_condition = "worker_parameter.id = 654 AND (parameter_id = 83 and parameter_type = 1)".
     *  Если оставить пустым то возвращает все из таблицы worker_parameter и все параметры
     * @param $date_time - до какой даты получить данные. Если оставить пустым, то возвращает последние данные буз указания даты
     * @return array
     * Created by: Одилов О.У. on 18.12.2018 8:29
     */
    public
    function GetWorkersParametersLastValues($worker_condition, $parameter_condition, $date_time)
    {
        $parameters = Assistant::AddConditionForParameters($parameter_condition);
        $parameter_condition = $parameters['parameters'];
        $parameter_type_table = $parameters['parameter_type_table'];
        return Assistant::CallProcedure("GetWorkersParametersLastValuesOptimized('$worker_condition', '$parameter_condition', '$date_time', '$parameter_type_table')");
    }

    /**
     * Название метода: GetWorkersParametersLastValues()
     * Метод получения данных работников до указанной даты (последние до указанной даты)
     * Метод вызывает процедуру GetWorkersParametersValuesOptimizedPeriod();
     * @param $worker_condition - условие фильтра для таблицы worker и worker_object.
     *  Например: $worker_condition = "worker_id = 100075 and worker_object.id = 33 OR и тд'
     *  Если оставить пустым то возвращает всех работников.
     * @param $parameter_condition - условие фильтра для worker_parameter.
     *  Например: $parameter_condition = "worker_parameter.id = 654 AND (parameter_id = 83 and parameter_type = 1)".
     *  Если оставить пустым то возвращает все из таблицы worker_parameter и все параметры
     * @param $date_time - дата начало периода
     * @param $date_time_end - дата завершения периода.
     * @return array - массив работников
     * Created by: Одилов О.У. on 18.12.2018 8:29
     */
    public
    function GetWorkersParametersValuesPeriod($worker_condition, $parameter_condition, $date_time, $date_time_end)
    {
        $parameters = Assistant::AddConditionForParameters($parameter_condition);
        $parameter_condition = $parameters['parameters'];
        $parameter_type_table = $parameters['parameter_type_table'];
        return Assistant::CallProcedure("GetWorkersParametersValuesOptimizedPeriod('$worker_condition', '$parameter_condition', '$date_time', '$date_time_end','$parameter_type_table')");
    }


    /**
     * Название метода: actionGetWorkersParameters()
     * Метод получения исторических данных значений параметров работника по периоду и до указанной даты.
     * Есть ограничение на получения данных по периоду, то есть больше 3 дней данные получить нельзя
     * Даныые получаем из БД, с помощью процедур Mysql.
     * Метод по умолчания возвращает все параметры всех работников и только последние.
     * Если вы хотите получить данные по условию, то условие нужно указать в параметрах. Нужен конкретный работник, значит указываем
     * конкретного работника, нужен конкретные параметры, указываем параметры.
     * Метод принимает следующие параметры
     * Необязательные поля:
     *      $post['parameters'] - пеерменная для хранения параметров и типов. Необходимо передать в виде "1-122, 2-83, 3-164, 1-105"
     *      Чтобы получить все параметры нужно указать parameters=""
     *      $post['date_time'] - дата/время/миллисекунды.
     *      $post['sensor_id'] - идентификатор работника. Если его не указать, то вернет данные всех работников, иначе только для одного
     *      $post['date_time_end'] - то возвращает данные по периоду.
     *
     * Для того, чтобы получить данные по периоду нужно указать $post['date_time'] и $post['date_time_end']. и чтобы получить для конкретного работника, нужно worke_id указать.
     * Если хотите получить все параметры, то в переменной нужно указать  $post['parameters'] = ""
     * Если хотите получить все сенсоры, то в переменную worker_id можно и не отправлять
     *
     * Чтобы получить даныне для конкретного работника нужно указать конкретного работник . Например worker_id =310 иначе указывать не нужно
     *
     * ------------------------------------ Примеры вызова метода  -----------------------------------------------------
     * Метод по умолчания возвращает данные последние для всех параметров работника со всем параметрами последние все и
     * по периоду для всех параметров все данные соответствующие.
     * --- Примеры вызова по умолчанию:
     * Последние данные работников для каждого параметра
     * http://localhost/unity-player/get-workers-parameters
     *
     * ---- Примеры вызова с указанием условий
     * - Последние данные всех работников с указанием нескольких параметров.
     * http://localhost/unity-player/get-workers-parameters?parameters=2-83, 2-98, 1-447
     *
     * - Последние данные конкретного работника с указанием параметров:
     * http://localhost/unity-player/get-workers-parameters?parameters=2-83,%202-98,%201-447&worker_id=1090193
     *
     * - Все последние параметры конкретного работника
     * http://localhost/unity-player/get-workers-parameters?worker_id=1090193
     *
     * ---- Получения данных по периоду.
     * Для получения данных по периоду обязательно необходимо указать дату начало периода и дату завершения периоду
     *
     * - Данные всех работников по периоду:
     * http://localhost/unity-player/get-workers-parameters?date_time=2018-11-25&date_time_end=2018-11-28
     *
     * - Данные конкретного работника по периоду:
     * http://localhost/unity-player/get-workers-parameters?date_time=2018-11-25&date_time_end=2018-11-28&worker_id=1090193
     *
     * - Данные конкретного работника по периоду с указанием параметров:
     * http://localhost/unity-player/get-workers-parameters?date_time=2018-11-25&date_time_end=2018-11-28&worker_id=1090193&parameters="2-83"
     * Документация на портале: http://192.168.1.3/products/community/modules/forum/posts.aspx?&t=8&p=1#46
     * Created by: Одилов О.У. on 12.12.2018 13:03
     */
    public
    function actionGetWorkersParameters()
    {
//        ini_set('max_execution_time', 600);
//        ini_set('memory_limit', '2000M');
        $post = Assistant::GetServerMethod();
        $date_limit = 3;
        $errors = array();
        $response = array();
        $warnings = array();
        $workers = array();
        $worker_id = -1;
        $worker_group_result = array();                                                                                                //массив ошибок
        $status = 1;
        $worker_condition = '';
        $parameter_condition = '';
        $date_time = '';
        $date_time_end = '';
        $warnings[] = 'actionGetWorkersParameters. Начал выполнять метод';
        if (isset($post['worker_id']) && $post['worker_id'] != '')                                                       //если передан worker_id то записываем его в переменную
        {
            $worker_id = $post['worker_id'];
            $worker_condition = "worker.id = $worker_id";
            $warnings[] = "actionGetWorkersParameters. Проверка входных параметров. worker_id задан и равен worker_id= $worker_id";
        } else {
            $warnings[] = 'actionGetWorkersParameters. Проверка входных параметров. worker_id не задан';
        }

        if (isset($post['date_time_end']) && $post['date_time_end'] != '') {
            $date_time_end = Assistant::GetDateWithMicroseconds($post['date_time_end']);
            $warnings[] = "actionGetWorkersParameters. Проверка входных параметров. date_time_end задан и равен date_time_end= $date_time_end";
        } else {
            $warnings[] = 'actionGetWorkersParameters. Проверка входных параметров. date_time_end не задан';
        }

        if (isset($post['date_time']) && $post['date_time'] != '') {
            $date_time = Assistant::GetDateWithMicroseconds($post['date_time']);
            $warnings[] = "actionGetWorkersParameters. Проверка входных параметров. date_time_end задан и равен date_time= $date_time";
        } else {
            $warnings[] = 'actionGetWorkersParameters. Проверка входных параметров. date_time не задан';
        }

        if (isset($post['parameters']) && $post['parameters'] != '') {
            $parameter_condition = $post['parameters'];
            $warnings[] = "actionGetWorkersParameters. Проверка входных параметров. parameters задан и равен parameters= $parameter_condition";
        } else {
            $warnings[] = 'actionGetWorkersParameters. Проверка входных параметров. parameters не задан';
        }

        if ($date_time_end == '')                                                                                        // если указано только одна дата, то возвращаем данные до указанной даты (без периода)
        {
            $workers = $this->GetWorkersParametersLastValues($worker_condition, $parameter_condition, $date_time);
            if (!$workers) {
                $errors[] = 'Нет данных по заданному условию';
                $status = 0;
            }
            $response[] = "Получение последних данных работника(ов) до $date_time";
            $warnings[] = 'actionGetWorkersParameters. Получены последние значения по работникам до указанной даты из БД';
        } /************************** ПОЛУЧАЕМ ДАННЫЕ РАБОТНИКА ПО УКАЗАННОМУ ПЕРИОДУ   *****************************/
        else                                                                                                            // если указано только две дата, то возвращаем данные до ДО УКАЗАННОГО ПЕРИОДА
        {
            $date_time_diff = Assistant::DateTimeDiff($date_time, $date_time_end, 'd');
            if ($date_time_diff > $date_limit) {
                $errors[] = "Система не позволяет получить исторические даныне работников по периоду больше $date_limit (трех) дней";
            } else {
                $workers = $this->GetWorkersParametersValuesPeriod($worker_condition, $parameter_condition, $date_time, $date_time_end);
                if (!$workers) {
                    $errors[] = 'Нет данных по заданному условию';
                    $status = 0;
                }
                $response[] = "Получение данных работника(ов) по периоду с $date_time по $date_time_end";
                $warnings[] = "actionGetWorkersParameters. Получены Данные за период с $date_time по $date_time_end";
            }
        }
        /**
         * приводит формат данных к без NULL
         */
        if ($workers) {
            foreach ($workers as $worker) {
                $worker_result['worker_id'] = $worker['worker_id'];
                $worker_result['worker_object_id'] = $worker['worker_object_id'];
                $worker_result['worker_parameter_id'] = $worker['worker_parameter_id'];
                $worker_result['parameter_id'] = $worker['parameter_id'];
                $worker_result['parameter_type_id'] = $worker['parameter_type_id'];
                if ($worker['value'] !== null) {
                    $worker_result['value'] = $worker['value'];
                } else {
                    $worker_result['value'] = -1;
                }
                $worker_result['date_time'] = $worker['date_time'];
                $worker_results[] = $worker_result;
                $worker_group_result[$worker['worker_id']][$worker['parameter_type_id']][$worker['parameter_id']] = $worker_result;
                unset($worker_result);
            }
            $workers = $worker_results;
            unset($worker_results);
        }
        $warnings[] = 'actionGetWorkersParameters. Закончил выполнять метод';
        $result = array(
            'status' => $status,
            'Items' => $workers,
            'groupItems' => $worker_group_result,
            'errors' => $errors,
            'response' => $response,
            'warnings' => $warnings
        );
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = $result;
        return json_encode($result);
    }

    /**
     * Название метода: actionGetEquipments()
     * МЕТОД ПОЛУЧЕНИЯ ИСТОРИЧЕСКИХ ДАННЫХ СПИСКА ОБОРУДОВАНИЯ
     * ДАННЫЕ ПОЛУЧАЮТСЯ ИЗ БД В ЗАВИСИМОСТИ ОТ ПОЗЗИЦИИ (83 ПАРАМЕТР) И МЕСТОПОЛОЖЕНИЯ (122 ПАРАМЕТР)
     * ПРИНИМАЕТ ПАРАМЕТРЫ МЕТОДОМ POST И GET
     * ЕСЛИ НЕ УКАЗАТЬ equipment_id , то возвращает все оборудование до указанной даты
     * ЕСЛИ УКАЗАТЬ equipment_id , то возвращает данные одного оборудования до указанной даты
     * По умолчание возвращает данные последние для всего оборудования
     * Если дату указать, то возвращает данные до укащанной даты
     * Входные параметры:
     *      Обязательные:
     *          mine_id - id шахты.
     *      Необязательные:
     *          equipment_id - вернет данные конкретного оборудования
     *          object_id - возвращает данные в зависимости от объекта сенсора (БПД, Узелы связи и тд)
     *          object_type_id - возвращает данные в зависимости от типа объекта сенсора (Датчики, Передача информации )
     * СПОСОБЫ ПОЛУЧЕНИЯ:
     * http://10.36.52.8/unity-player/get-equipments?mine_id=270&equipment_id=147466&date_time=2019-09-04
     * Copied by: Сырцее А.П. on 04.09.2019
     */
    public
    function actionGetEquipments()
    {
        $post = Assistant::GetServerMethod();

        $errors = array();
        $warnings = array();
        $response = array();
        $equipments = array();
        $equipment_id = '*';

        $equipment_condition = '';
        $date_time = '';
        $warnings[] = 'actionGetEquipments. Начал выполнять метод';
        if (isset($post['equipment_id']) && $post['equipment_id'] != '')                                                       //если передан sensor_id то записываем его в переменную
        {
            $equipment_id = $post['equipment_id'];
            $equipment_condition = " AND equipment.id = $equipment_id";
            $warnings[] = "actionGetEquipments. Проверил входные данные. equipment_id есть и равен $equipment_id";
        } else {
            $warnings[] = 'actionGetEquipments. Проверил входные данные. equipment_id нет';
        }

        if (isset($post['date_time']) && $post['date_time'] != '') {
            $date_time = Assistant::GetDateWithMicroseconds($post['date_time']);
            $warnings[] = "actionGetEquipments. Проверил входные данные. date_time есть и равен $date_time";
        } else {
            $warnings[] = 'actionGetEquipments. Проверил входные данные. date_time нет';
        }

        if (isset($post['object_id']) && $post['object_id'] != '') {
            $object_id = $post['object_id'];
            $equipment_condition .= " AND object.id = $object_id ";
            $warnings[] = "actionGetEquipments. Проверил входные данные. object_id есть и равен $object_id";
        } else {
            $warnings[] = 'actionGetEquipments. Проверил входные данные. object_id нет';
        }

        if (isset($post['object_type_id']) && $post['object_type_id'] != '') {
            $object_type_id = $post['object_type_id'];
            $equipment_condition .= " AND object.object_type_id = $object_type_id ";
            $warnings[] = "actionGetEquipments. Проверил входные данные. object_type_id есть и равен $object_type_id";
        } else {
            $warnings[] = 'actionGetEquipments. Проверил входные данные. object_type_id нет';
        }

        if (isset($post['mine_id']) && $post['mine_id'] != '') {
            $mine_id = $post['mine_id'];
            $warnings[] = "actionGetEquipments. Проверил входные данные. mine_id есть и равен $mine_id";
            if ($date_time == '')                                                                                        // если дата не указана, то получаем данные из КЭША
            {
                $equipments = (new \backend\controllers\cachemanagers\EquipmentCacheController())->getEquipmentMine($mine_id);
                $response[] = 'Получил данные из кэша, так как дата не указана ';
                $warnings[] = 'actionGetEquipments. Данные получены из кеша';
            } else                                                                                                        // если указана дата, то получаем данные из БД
            {
                //todo переделать метод на новые методы
                $equipment_condition = " mine.id = $mine_id " . $equipment_condition;
                $equipments = Assistant::CallProcedure("GetEquipmentsMine('$equipment_condition','$date_time')");
                $response[] = 'Получил данные из БД с помощью процедур';
                $warnings[] = 'actionGetEquipments. Данные получены из БАЗЫ ДАННЫХ';
            }
        } else {
            $errors[] = 'Не передан идентификатор шахты';
            $warnings[] = 'actionGetEquipments. Проверил входные данные. mine_id нет';
            $errors['post'] = $post;
        }
        $warnings[] = 'actionGetEquipments. Закончил выполнять метод';
        if (!$equipments) {
            $equipments = array();
        }
        $result = array('Items' => $equipments, 'errors' => $errors, 'response' => $response, 'warnings' => $warnings);
        //Yii::$app->response->format = Response::FORMAT_JSON;
        //Yii::$app->response->data = $result;
        return json_encode($result);
    }

    /**
     * Название метода: actionGetEquipmentsParameters()
     * Метод получения исторических данных значений параметров оборудования по периоду и до указанной даты.
     * Есть ограничение на получения данных по периоду, то есть больше 3 дней данные получить нельзя
     * Даныые получаем из БД, с помощью процедур Mysql.
     * Метод принимает следующие параметры
     * Необязательные поля:
     *      $post['parameters'] - пеерменная для хранения параметров и типов. Необходимо передать в виде "1-122, 2-83, 3-164, 1-105"
     *      Чтобы получить все параметры нужно указать parameters=""
     *      $post['date_time'] - дата/время/миллисекунды.
     *      $post['equipment_id'] - идентификатор оборудования. Если его не указать, то вернет данные всего оборудования, иначе только для одного
     *      $post['date_time_end'] - то возвращает данные по периоду.
     *
     * Для того, чтобы получить данные по периоду нужно указать $post['date_time'] и $post['date_time_end'].
     * Чтобы получить для конкретного оборудования, нужно equipment_id указать.
     * Если хотите получить все параметры, то в переменной нужно указать  $post['parameters'] = -1
     * Если хотите получить всё оборудование, то переменную equipment_id можно и не отправлять
     *
     * Чтобы получить даныне для конкретного оборудования нужно указать конкретный айди.
     * Например equipment_id=310 иначе указывать не нужно
     *
     * -----------------------------------------------------------------------------------------------------------------
     *
     * ПРИМЕРЫ ВЫЗОВА ДАННЫХ ПО УМОЛЧАНИЮ
     *
     * http://localhost/unity-player/get-equipments-parameters - возвращает для всех параметров последние данные по указанным по умолчанию параметрам
     * http://localhost/unity-player/get-equipments-parameters?mine_id=290 - возвращает для всех параметров последние данные по указанным по умолчанию параметрам
     *
     * Метод по умолчанию возвращает для следующих параметров. "83:1,83:2,164:3,447:2,448";
     * Указанные параметры для всего оборудования (до указнной даты)
     * http://localhost/unity-player/get-equipments-parameters?date_time=2018-12-12
     *
     * Указанные параметры для конкретного оборудования (до указнной даты)
     * http://localhost/unity-player/get-equipments-parameters?date_time=2018-12-12&sensor_id=26354
     *
     * Указанные параметры для всего оборудования (ПО ПЕРИОДУ)
     * http://localhost/unity-player/get-equipments-parameters?date_time=2018-09-12&date_time=2018-12-12
     *
     * Указанные параметры для конкретного оборудования (ПО ПЕРИОДУ)
     * http://localhost/unity-player/get-equipments-parameters?date_time=2018-09-12&date_time=2018-12-12&equipment_id=26354
     *
     * -----------------------------------------------------------------------------------------------------------------
     *
     * ПРИМЕРЫ ПОЛУЧЕНИЯ ПО УКАЗАННОМУ ПЕРИОДУ
     *
     * По периоду (все оборудование)
     * http://localhost/unity-player/get-equipments-parameters?date_time=2018-01-12&equipment_id=26358&date_time_end=2019-01-01
     * http://localhost/unity-player/get-equipments-parameters?parameters=83:1,83:2,164:3,447:2,448:2&date_time=2018-09-27%2010:16:51.118772&date_time_end=2018-09-27%2010:20:51.118772
     * По периоду (все оборудование) с указанием конкретных параметров
     * http://localhost/unity-player/get-equipments-parameters?date_time=2018-01-12&parameters=83:1,83:2,164:3,447:2,448&equipment_id=26358&date_time_end=2019-01-01
     *
     * По периоду с указанием конертного оборудования
     * http://localhost/unity-player/get-equipments-parameters?date_time=2018-01-12&equipment_id=26358&date_time_end=2019-01-01&sensor_id=26358
     *
     * -----------------------------------------------------------------------------------------------------------------
     * ПРИМЕРЫ ПОЛУЧЕНИЯ ДО УКАЗАННОГО ДНЯ(ПОСЛЕДНИЕ ТОЛЬКО)
     *
     * Данные до указанного дня
     * http://localhost/unity-player/get-equipments-parameters?date_time=2018-12-12&parameters=83:1,83:2,164:3,447:2,448
     *
     * Данные до указанного дня для конкретного оборудования
     * http://localhost/unity-player/get-equipments-parameters?date_time=2018-12-12&parameters=83:1,83:2,164:3,447:2,448&equipment_id=26358
     *
     * Данные до указанного дня для конкретного оборудования и все параметры
     *  http://localhost/unity-player/get-equipments-parameters?date_time=2018-12-12&equipment_id=26358
     */
    public
    function actionGetEquipmentsParameters()
    {
//        ini_set('max_execution_time', 600);
//        ini_set('memory_limit', '2000M');
        $post = Assistant::GetServerMethod();
        $date_limit = 3;
        $errors = array();                                                                                                //массив ошибок
        $equipment_group_result = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $warnings = array();
        $response = array();
        $date_time = '';
        $mine_id = -1;
        $equipments = array();
        $parameters = '1:83,2:83,3:164,2:447,2:448';
        $equipment_condition = '';
        $equipment_id = '*';
        $date_time_end = '';

        try {
            if (isset($post['equipment_id']) && $post['equipment_id'] != '')                                                       //если передан sensor_id то записываем его в переменную
            {
                $equipment_id = $post['equipment_id'];
                $equipment_condition = " equipment.id = $equipment_id ";
                $warnings[] = "actionGetEquipmentsParameters. Проверил входные данные. equipment_id есть и равен $equipment_id";
            } else {
                $warnings[] = 'actionGetEquipmentsParameters. Проверил входные данные. equipment_id нет';
            }
            if (isset($post['date_time_end']) && $post['date_time_end'] != '') {
                $date_time_end = Assistant::GetDateWithMicroseconds($post['date_time_end']);
                $warnings[] = "actionGetEquipmentsParameters. Проверил входные данные. date_time_end есть и равен $date_time_end";
            } else {
                $warnings[] = 'actionGetEquipmentsParameters. Проверил входные данные. date_time_end нет';
            }

            if (isset($post['parameters']) && $post['parameters'] != '') {
                $parameters = $post['parameters'];
                $warnings[] = "actionGetEquipmentsParameters. Проверил входные данные. parameters есть и равен $parameters";
            } else {
                $warnings[] = 'actionGetEquipmentsParameters. Проверил входные данные. parameters нет';
            }

            if (isset($post['mine_id']) && $post['mine_id'] != '') {
                $mine_id = $post['mine_id'];
                $warnings[] = "actionGetEquipmentsParameters. Проверил входные данные. mine_id есть и равен $mine_id";
            } else {
                $warnings[] = 'actionGetEquipmentsParameters. Проверил входные данные. mine_id нет';
            }

            if (isset($post['date_time']) && $post['date_time'] != '') {
                $date_time = Assistant::GetDateWithMicroseconds($post['date_time']);
                $warnings[] = "actionGetEquipmentsParameters. Проверил входные данные. date_time есть и равен $date_time";
            } else {
                $warnings[] = 'actionGetEquipmentsParameters. Проверил входные данные. date_time нет';
            }

            /************************* ПОЛУЧАЕМ ДАННЫЕ ДО УКАЗАННОЙ ДАТЫ   **************************************/
            if ($date_time_end == '')                                                                                        // если указано только одна дата, то возвращаем данные до указанной даты (без периода)
            {
                if ($date_time == '' && $mine_id != -1) {
                    $warnings[] = 'actionGetEquipmentsParameters. получаю данные из кеша';

//                    ini_set('max_execution_time', 300);
//                    ini_set('memory_limit', '1024M');

                    $warnings[] = 'actionGetEquipmentsParameters. Начал выполнять метод';

                    $microtime_start = microtime(true);

                    $warnings[] = 'actionGetEquipmentsParameters. Начал получать кеш оборудования ' . $duration_method = round(microtime(true) - $microtime_start, 6);
                    $equipment_mines = (new \backend\controllers\cachemanagers\EquipmentCacheController())->getEquipmentMine($mine_id, $equipment_id);

                    $warnings[] = 'actionGetEquipmentsParameters. получил кеш оборудования ' . $duration_method = round(microtime(true) - $microtime_start, 6);
                    if ($equipment_mines) {
                        $warnings[] = 'actionGetEquipmentsParameters. кеш оборудования есть';
                    } else {
                        throw new \Exception('actionGetEquipmentsParameters. кеш оборудования шахты пуст');                                                                                  //ключ от фронт энда не получен, потому формируем ошибку
                    }
                    $equipment_parameters = array(
                        9,                  // температура
                        83,                 // координата
                        98,                 // СО
                        99,                 // СН4
                        20,                 // кислород
                        22,                 // запыленность
                        24,                 // скорость воздуха
                        26,                 // водород
                        164,                // состояние
                        386,                // Превышение концентрации метана
                        387,                // Превышение удельной доли углекислого газа
                        447,                // Процент уровня заряда батареи узла связи
                        448                 // Процент уровня заряда батареи метки
                    );
                    /**
                     * получаю все параметры всего оборудования из кеша и пепелопачиваю их.
                     * метод надо переделать на запрос параметров, только нужного оборудования
                     */
                    $full_parameters = (new \backend\controllers\cachemanagers\EquipmentCacheController())->multiGetParameterValue('*', '*');
                    if ($full_parameters) {

                        $warnings[] = 'actionGetEquipmentsParameters. Полный кеш параметров оборудования получен';
                        foreach ($full_parameters as $full_parameter) {
                            $equipmentList[$full_parameter['sensor_id']][$full_parameter['parameter_id']][$full_parameter['parameter_type_id']] = $full_parameter;
                        }
                    } else {
                        throw new \Exception('actionGetEquipmentsParameters. кеш параметров оборудования шахты пуст');
                    }

                    /**
                     * фильтруем только тех кто нужен
                     */
                    foreach ($equipment_mines as $equipment_mine) {
                        for ($i = 1; $i <= 3; $i++) {
                            foreach ($equipment_parameters as $equipment_parameter) {
                                if (isset($equipmentList[$equipment_mine['equipment_id']][$equipment_parameter][$i]['value'])) {
                                    /**
                                     * блок фильтрации параметров стационарных датчиков газа
                                     */
                                    $equipment_result['equipment_id'] = $equipmentList[$equipment_mine['equipment_id']][$equipment_parameter][$i]['equipment_id'];
                                    $equipment_result['object_id'] = $equipment_mine['object_id'];
                                    $equipment_result['object_type_id'] = $equipment_mine['object_type_id'];
                                    $equipment_result['equipment_parameter_id'] = $equipmentList[$equipment_mine['equipment_id']][$equipment_parameter][$i]['equipment_parameter_id'];
                                    $equipment_result['parameter_id'] = $equipmentList[$equipment_mine['equipment_id']][$equipment_parameter][$i]['parameter_id'];
                                    $equipment_result['parameter_type_id'] = $equipmentList[$equipment_mine['equipment_id']][$equipment_parameter][$i]['parameter_type_id'];
                                    $equipment_result['date_time'] = $equipmentList[$equipment_mine['equipment_id']][$equipment_parameter][$i]['date_time'];
                                    $equipment_result['value'] = $equipmentList[$equipment_mine['equipment_id']][$equipment_parameter][$i]['value'];
                                    $equipments[] = $equipment_result;
                                }
                            }
                        }
                    }

                    unset($equipment_mines, $full_parameters);
                } else {
                    $warnings[] = "actionGetEquipmentsParameters. Условия перед отправкой запроса equipment_condition $equipment_condition";
                    $warnings[] = "actionGetEquipmentsParameters. Условия перед отправкой запроса parameters $parameters";
                    $warnings[] = "actionGetEquipmentsParameters. Условия перед отправкой запроса date_time $date_time";
                    $equipments = Assistant::GetEquipmentsParametersLastValues($equipment_condition, $parameters, $date_time);
                    if (!$equipments) {
                        $errors[] = 'Нет данных по заданному условию';
                        $warnings[] = 'actionGetEquipmentsParameters. Данных по оборудованию до указанной даты в БАЗЕ ДАННЫХ нет';
                    }
                    $response[] = "actionGetEquipmentsParameters. Получение данных оборудования до $date_time";
                    $warnings[] = 'actionGetEquipmentsParameters. Получил данные до указанной даты из БАЗЫ ДАННЫХ';
                }
            } /************************* ПОЛУЧАЕМ ДАННЫЕ ПО УКАЗАННОМУ ПЕРИОДУ   **************************************/
            else                                                                                                            // если указано только две дата, то возвращаем данные до ДО УКАЗАННОГО ПЕРИОДА
            {
                $date_time_diff = Assistant::DateTimeDiff($date_time, $date_time_end, 'd');
                if ($date_time_diff > $date_limit) {
                    $errors[] = "actionGetEquipmentsParameters. Система не позволяет получить исторические данные оборудования по периоду больше $date_limit (трех) дней";
                    $warnings[] = 'actionGetEquipmentsParameters. Получение данных за указанный период возможно только за 3 дня';
                } else {
                    $sensors = Assistant::GetEquipmentsParametersValuesPeriod($equipment_condition, $parameters, $date_time, $date_time_end);
                    if (!$sensors) {
                        $errors[] = 'actionGetEquipmentsParameters. Нет данных по заданному условию';
                        $warnings[] = 'actionGetEquipmentsParameters. Данные в БАЗЕ ДАННЫХ за указанный период не найдены';
                    }
                    $response[] = "actionGetEquipmentsParameters. Получение данных оборудования по периоду с $date_time по $date_time_end";
                    $warnings[] = 'actionGetEquipmentsParameters. Получены данные за указанный период из БАЗЫ ДАННЫХ';
                }
            }
            /**
             * приводит формат данных к без NULL
             */
            if ($equipments) {
                foreach ($equipments as $equipment) {
                    $equipment_result['equipment_id'] = $equipment['equipment_id'];
                    $equipment_result['object_id'] = $equipment['object_id'];
                    $equipment_result['object_type_id'] = $equipment['object_type_id'];
                    $equipment_result['equipment_parameter_id'] = $equipment['equipment_parameter_id'];
                    $equipment_result['parameter_id'] = $equipment['parameter_id'];
                    $equipment_result['parameter_type_id'] = $equipment['parameter_type_id'];
                    $equipment_result['date_time'] = $equipment['date_time'];
                    if ($equipment['value'] !== null) {
                        $equipment_result['value'] = $equipment['value'];
                    } else {
                        $equipment_result['value'] = '';
                    }
                    $equipment_results[] = $equipment_result;

                    $equipment_group_result[$equipment['object_id']][$equipment['equipment_id']][$equipment['parameter_type_id']][$equipment['parameter_id']] = $equipment_result;
                    unset($equipment_result);
                }
                $equipments = $equipment_results;
                unset($equipment_results);
            }
        } catch
        (\Throwable $ex) {
            $errors[] = 'actionGetEquipmentsParameters. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $warnings[] = 'actionGetEquipmentsParameters. Закончил выполнять метод';
        $result_main = array(
            'status' => $status,
            'Items' => $equipments,
            'groupItems' => $equipment_group_result,
            'errors' => $errors,
            'warnings' => $warnings,
            'response' => $response
        );
        return json_encode($result_main);
        //Yii::$app->response->format = Response::FORMAT_JSON;
        //Yii::$app->response->data = $result_main;
    }

    /***
     * Метод получения списка передвижения оборудования, по времени и до указанной даты для построения маршрута в 3D схеме
     * Метод используется для построения пути передвижения.
     * Метод можно вызвать с помощью GET/POST запросов.
     * Если указать только дату начало (date_start), то ВОЗВРАЩАЕТ данные по периоду
     * Если указать только дату начало (date_start) и дату конец (date_end), то ВОЗВРАЩАЕТ последниЕ данныЕ до указанной даты
     * Если указан GET, то POST не принимается
     *  Входные параметры:
     *      Обязательные:
     *          mine_id - id шахты.
     *          date_start - Дата с какого момента будут показываться данные Значение до 1 секунды.
     *          equipment_id - id работника, по которому будет выведена информация.
     *      Необязательные:
     *          date_end - дата до какого момента будут показываться данные. Значение до 1 секунды.
     * POST - http://localhost/unity-player/equipment-movement-history
     * GET - http://localhost/unity-player/equipment-movement-history?mine_id=290&date_start=2018-06-06%12:25:00&date_end=2018-11-30%12:25:00&equipment_id=2913553 - возврат данных по периоду
     * GET - http://localhost/unity-player/equipment-movement-history?mine_id=290&equipment_id=2913553&date_start=2018-09-27%2007:09:06.936424 - возврат последних данных до указанной даты
     * Создан: Аксенов И.Ю.
     * Одилов О.У.
     * Добавил загрузку из процедуры
     * Добавил возможность получения последних данных до конкретной даты
     * Возвращает одномерный массив
     */
    public function actionEquipmentMovementHistory()
    {
        $query = new Query();
        $post = Assistant::GetServerMethod();
        $errors = array();
        $history_movement = array();
        $warnings = array();
        $parameter_coord = 83;
        $parameter_co = 98;
        $parameter_ch4 = 99;
        $date_start = '';
        $date_end = '';
        $warnings[] = 'actionEquipmentMovementHistory. Начал выполнять метод';
        if ((isset($post['mine_id']) && $post['mine_id'] != '')                                                         //Проверка, на заданные входные параметры
            && (isset($post['date_start']) && $post['date_start'] != '')
            && (isset($post['equipment_id']) && $post['equipment_id'] != '')) {
            $mine_id = $post['mine_id'];
            $equipment_id = $post['equipment_id'];
            $date_start = $post['date_start'];
            $date_start = date('Y-m-d H:i:s', strtotime("$date_start -1 sec"));                            //Дата начала, берется с начала суток
            if (isset($post['date_end']) && $post['date_end'] != '') {
                $date_end = $post['date_end'];
                $date_end = date('Y-m-d H:i:s', strtotime("$date_end +1 sec"));                            //Дата окончания, берется с начала суток
                $sql_select = 'mine_id, equipment_id, parameter_id, parameter_type_id, date_time as date_time, value';                  // Получаем данные конкретного работника в период времени, если указано date_end
                $warnings[] = 'actionEquipmentMovementHistory. Получаю данные из вьюшки';
                $history_movement = $query
                    ->select($sql_select)
                    ->from('view_equipment_movement_and_gaz_history')
                    ->where("(date_time between '$date_start' and '$date_end')
                        and mine_id = $mine_id and equipment_id = $equipment_id and value != '0.000000,0.000000,0.000000'
                        and (parameter_id = $parameter_coord or parameter_id = $parameter_co or parameter_id = $parameter_ch4)")
                    ->all();
            } else                                                                                                        // Получаем последние данные конкретного работника до указанного дня, если НЕ указано date_end
            {
                $warnings[] = 'actionEquipmentMovementHistory. Получаю данные из БД с хранимки';
                $sql_query = "CALL EquipmentMovementHistory($mine_id, $equipment_id, '$date_start')";
                $history_movement = Yii::$app->db->createCommand($sql_query)->queryAll();
            }
        } else {
            $errors[] = 'Параметры не переданы';
        }
        $warnings[] = 'actionEquipmentMovementHistory. Закончил выполнять метод';
        $result = array('Items' => $history_movement, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //формат возвращаемых данных json методом yii2
        Yii::$app->response->data = $result;                                                                          //сам возврат данных во фронт энд
    }

}
