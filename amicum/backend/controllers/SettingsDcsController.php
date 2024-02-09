<?php

namespace backend\controllers;
//ob_start();


use backend\controllers\cachemanagers\LogCacheController;
use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\cachemanagers\ServiceCache;
use backend\controllers\const_amicum\EventEnumController;
use frontend\models\BpdPackageInfo;
use frontend\models\ConnectString;
use frontend\models\SensorParameter;
use frontend\models\SensorParameterValue;
use Yii;
use yii\httpclient\Exception;
use yii\web\Response;

class SettingsDcsController extends \yii\web\Controller
{
    // actionGetModbusConfig     - Получение конфигурации ССД
    // actionGetModbusData       - центральный етод для записи в кеш и в БД данных полученных со службы сбора данных БПД-3
    // debugMessage              - вывод отладочных сообщений на экран при работе кода, в местах вызова
    // saveBpdPackage            - Метод записи пакетов БПД-3 в таблицу bpd_package_info в исходном виде, как они приходят со службы
    // AddCacheSensorIP()        - Метод добавляет в кеш ключ сенсора по ключу службы сбора данных и по айпи опрашиваемого устройства для устройств круппы Modbus
    // writeSensorParameterValue - Метод записи обычных параметров БПД в БД с проверкой на старые значения
    // writeBpdCharge            - Метод записи параметра Заряд БПД
    // writeBpdState             - Метод бработки и записи параметра Состояние

    public $is_debug = false;

    /**
     * actionGetModbusConfig - Получение конфигурации ССД
     * Суть метода: в метод передается ключ службы сбора данных DCS_ID и по данному ключу происходит поиск всех строк подключения в БД,
     * после того как строки подключения по типу источника MODBUS и сенсоры, которые за строками подклчючения числятся, найдены
     * Вся структура укладывается в кеш ConnectString_[IP адрес опрашиваемого устройства], в дальнейшем по айпи идет поиск сенсора в кеше и возвращается в метод для записи значений
     * Укладываение в кеш конфигурации на опрос происходит при каждом запуске этого метода
     * входные данные:
     *      DCS_ID  - ключ службы сбора данных с БПД3
     * выходные данные:
     *      ip              - IP адрес устройства, которое нужно опросить
     *      connectString   - строка подключения (в ней хранятся вспомогательные параметры для подключения, например port)
     *      sourceType      - тип источника (планировалось, что служба будет универсальная и потому может передавать за раз все подключенния, например, OPC, Strata)
     *      sensor_id       - ключ сенсора, который опрашивается
     * Используемый кеш ConnectString_[IP адрес опрашиваемого устройства]
     * Пример использования: http://127.0.0.1/admin/settings-dcs/get-modbus-config?DCS_ID=1400
     */
    public function actionGetModbusConfig()
    {
        $result = array();                                                                                              // результирующий ответ от метода к службе
        $get = Assistant::GetServerMethod();
        if (isset($get['DCS_ID']) and $get['DCS_ID'] != "") {
            $dcs_id = $get['DCS_ID'];
            $connects_array = array();                                                                                  // массив строк подключения для опроса службой
            $arr_cache_ip = array();                                                                                    // массив для массовой вставки ключей в кеш
            //Получаем объект Settings dcs
            $settingsDCS = ConnectString::find()
                ->where([
                    'Settings_DCS_id' => $dcs_id,
                    'source_type' => 'MODBUS'
                ])
                ->with('sensorConnectString')
                ->limit(100000)
                ->all();

            if ($settingsDCS) {
                $i = 0;
                foreach ($settingsDCS as $connectString)                                                                //Переходим к connect string
                {
                    $connects_array[$i] = array();                                                                      //Создаем массив
                    $connects_array[$i]['ip'] = $connectString->ip;                                                     // Получение ip
                    $connects_array[$i]['connectString'] = $connectString->connect_string;                              // Получение connect_string
                    $connects_array[$i]['sourceType'] = $connectString->source_type;                                    // Получение source_type
                    $connects_array[$i]['sensor_id'] = $connectString->sensorConnectString['sensor_id'];                // Получение sensor_id
                    $i++;
                    $key = 'ConnectString_' . $connectString->ip;
                    $arr_cache_ip[$key] = $connectString->sensorConnectString['sensor_id'];
                }

                (new ServiceCache())->amicum_mSet($arr_cache_ip);
                $result = $connects_array;
            } else {
                $result = "actionGetModbusConfig. Конфигурация для запрашиваемой службы сбора данных не найдена";
            }
        } else {
            $result = "actionGetModbusConfig. Входной параметр DCS_ID не задан";
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * actionGetModbusData - центральный етод для записи в кеш и в БД данных полученных со службы сбора данных БПД-3
     * Суть метода: полученные данные приходят в трех синхронных массивах, в одном массиве список ключей параметров (tagName), во втором список значений измеренных парамтетров (Value)
     * в третьем массиве список дат, в которые измеряли параметры (dateTime).
     * Sensor_id опрашиваемого устройства определяется по IP адресу, формально за одним IP адресом может находиться несколько сенсоров
     * Входные параметры:
     *      tagName     - список ключей измеренных параметров
     *      tagValue    - список значений измеренных параметров
     *      dateTime    - время, в которое измерили параметры
     *      IP          - IP адрес опрашиваемого устройства
     *      port        - порт опрашиваемого устройства
     *      dcsId       - ключ службы сбора данных
     * Пример запроса:
     * http://..../settings-dcs/get-modbus-data?mine_id=290&ip=172.22.59.10&port=501&dcsId=1390&tagName=164~457~455~164~159~166~167~204~170~201~175~174~173~172~171~451~454~456~453~206~205~452~450~218~203~202~207~208~209&tagValue=0~1~1~1~0~0~0~0~0~0~0~0~0~0~0~0~0~0~0~0~0~0~0~0~0~0~0~0&dateTime=2018-08-30%2020:48:05~2018-08-30%2017:48:05~2018-08-30%2017:48:05~2018-08-30%2017:48:05~2018-08-30%2017:48:05~2018-08-30%2017:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05~2018-08-30%2020:48:05
     * Для тестирования создания событий:
     * http://127.0.0.1/admin/settings-dcs/get-modbus-data?mine_id=290&ip=172.22.51.1&port=501&dcsId=1400&tagName=164&tagValue=2&dateTime=2018-09-30 14:25:00
     */
    public function actionGetModbusData()
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        $warnings[] = "actionGetModbusData. Начал выполнять метод";
        try {
            $post = Assistant::GetServerMethod();                                                                              //Получение данных методом POST
            if (!isset($post['tagName']) or !isset($post['tagValue']) or !isset($post['dateTime'])) {
                throw new \Exception('actionGetModbusData. Входные параметры не переданы');
            }
            $tag_name = explode('~', $post['tagName']);                                                             //Разделяем и сохраняем id тега
            $tag_value = explode('~', $post['tagValue']);                                                           //Разделяем и сохраняем значение тега
            $tag_datetime = explode('~', $post ['dateTime']);                                                       //Разделяем и сохраняем время записи тега
            $connect_str = $post['port'];                                                                                   //Сохраняем connect string
            $ip = $post['ip'];                                                                                              //Сохраняем IP
            $dcs_id = $post['dcsId'];
            $mine_id = AMICUM_DEFAULT_MINE;
            if (isset($post['mine_id']))
            {
                $mine_id = $post['mine_id'];
            }
//            LogCacheController::setModbusLogValue('actionGetModbusData', $mine_id);
            //если нет разрешения на запись, то метод не выполняется
            if(!(new ServiceCache())->CheckDcsStatus($mine_id, 'bpdStatus'))
            {
                throw new Exception( "Нет разрешения на запись");
            }

            $tag_name_string = $post['tagName'];
            $tag_value_string = $post['tagValue'];

            $this->debugMessage('Получили данные, вызываем метод обработки', $this->is_debug);                     //вывод отладочного сообщения на экран
            //$this->saveBpdPackage($tag_name_string, $tag_value_string, $tag_datetime[0], $ip);                                 // запись исходного пакета в БД
            $response = $this->processModbusData($tag_name, $tag_value, $tag_datetime, $ip, $connect_str, $dcs_id);                     //Вызываем метод обработки данных
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $warnings[] = "actionGetModbusData. Успешно сохранил все параметры сенсора";
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new \Exception("actionGetModbusData. Не смог сохранить параметры сенсора");
            }
        } catch (\Throwable $ex) {
            $errors[] = "actionGetModbusData. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
            $data_to_log_cache = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
            LogCacheController::setModbusLogValue('actionGetModbusData', $data_to_log_cache);
        }
        $warnings[] = "actionGetModbusData. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод для обработки полученных с БПД-3 данных
     * @param $tag_name - массив с именами тегов
     * @param $tag_value - массив с значениями
     * @param $tag_datetime - массив с временами
     * @param $ip - ip адрес
     * @param $port - порт
     * @param $dcs_id - id ссд
     */
    public function ProcessModbusData($tag_name, $tag_value, $tag_datetime, $ip, $port, $dcs_id)
    {

        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // результирующий массив
        $sensor_parameter_to_cache = null;                                                                                              // результирующий массив

        try {
//            echo '<pre>';                                                                                                   // блок вывода отладочной информации в консоль службы
//            var_dump($tag_name);
//            var_dump($tag_value);
//            var_dump($tag_datetime);
//            var_dump($ip);
//            var_dump($port);
//            var_dump($dcs_id);
//            echo '</pre>';


            //$cache->flush();
            $key = 'ConnectString_' . $ip;                                                                              //формируем ключ для поиска sensor_id в кеше по ip
            $warnings[] = "ProcessModbusData. Начинаю получать строку подключения из кеша";

            $service_cache = (new ServiceCache());
            $sensor_id = $service_cache->amicum_rGet($key);                                                                      //получаем значение сенсора в кеше по IP адресу опрашиваемого устройства
            if ($sensor_id === false) {
                $warnings[] = "ProcessModbusData. Строки подключения нет в кеше. Начинаю добавление в кеш";
                $response = $this->AddCacheSensorIP($dcs_id, $ip);
                if ($response['status'] == 1) {
                    $sensor_id = $response['Items'];
                    $warnings[] = $response['warnings'];

                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new \Exception("ProcessModbusData. В кеше и в БД привязка IP к сенсору не задана");
                }
            } else {
                $warnings[] = "ProcessModbusData. Строки подключения есть в кеше сенсор_айди = $sensor_id";
            }


            if (!is_null($sensor_id)) {
                /**
                 * Ищем шахту сенсора
                 */
                $response = SensorMainController::getSensorStaticLastMine($sensor_id);                                  //получить шахту mine_id для искомого сенсора
                if ($response['status'] == 1) {
                    $mine_id = $response['mine_id'];
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $warnings[] = "ProcessModbusData. Значение параметра сенсора шахта найдено и равно $mine_id";
                } else {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new \Exception("ProcessModbusData. Параметр шахты не сконфигурирован для сенсора $sensor_id должным образом");
                }

                /**
                 * Проверяем наличие детальных сведений о сенсоре в кеше, если нет, то инициализируем сенсор в кеше со всеми параметрами
                 */
                //создаем ключ для получения сенсора из кеша
                $sensor_cache = (new SensorCacheController())->getSensorMineBySensorOneHash($mine_id, $sensor_id);

                if (!$sensor_cache) {
                    $warnings[] = "ProcessModbusData. Ключ сенсора:  в кеше не существует";
                    $warnings[] = "ProcessModbusData. Инициализируем параметры сенсора $sensor_id в кеше";
                    $response = SensorMainController::initSensorInCache($sensor_id);                                  //получить шахту mine_id для искомого сенсора
                    if ($response['status'] == 1) {
                        $result = $response['Items'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        $warnings[] = "ProcessModbusData. Кеш сенсора $sensor_id инициализирован";
                    } else {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        throw new \Exception("ProcessModbusData. Кеш сенсора $sensor_id не инициализирован");
                    }
                } else {
                    $warnings[] = "ProcessModbusData. Кеш сенсора $sensor_id уже существовал";
                }
                $sensor_cache_controller = (new SensorCacheController());
                // Сохранение параметров
                foreach ($tag_name as $i => $parameter_id) {                                                                     //Для каждого id тега (id тега в качестве i)
                    // проверка на касячное время со службы сбора данных
                    if ($tag_datetime[$i] == -1 or $tag_datetime[$i] == false or $tag_datetime[$i] == "") {
                        $warnings[] = "writeBpdCharge. Со службы пришло пустое время!!!! :" . $tag_datetime[$i];
                        $tag_datetime[$i] = Assistant::GetDateNow();
                        $warnings[] = "writeBpdCharge. НОВОЕ ВРЕМЯ: " . $tag_datetime[$i];
                    }
                    switch ($parameter_id) {
                        case 164:                                                                                       //параметр состояние
                            /**
                             * Блок записи состояния блока питания
                             */
                            //получаем из кеша/БД/создаем в БД конкретный параметер сенсора
                            $response = SensorMainController::GetOrSetSensorParameter($sensor_id, $parameter_id, '3');
                            if ($response['status'] == 1) {
                                $warnings[] = $response['warnings'];
                                $errors[] = $response['errors'];
                                $status *= $response['status'];
                                $sensor_parameter_id = $response['sensor_parameter_id'];

                                $response = $this->writeBpdState($sensor_parameter_id, $sensor_id, $parameter_id, $tag_value[$i], $tag_datetime[$i], $mine_id); //Вызываем метод записи состояния
                                if ($response['status'] == 1) {
                                    $result = $response['Items'];
                                    $warnings[] = $response['warnings'];
                                    $errors[] = $response['errors'];
                                    $status *= $response['status'];
                                    $warnings[] = "ProcessModbusData. Значение сенсора $sensor_id Состояние 164 записано в БД";
                                } else {
                                    $errors[] = $response['errors'];
                                    $warnings[] = $response['warnings'];
                                    throw new \Exception("ProcessModbusData. Значение сенсора $sensor_id Состояние 164 НЕ записано в БД");
                                }

                                $response = $sensor_cache_controller->setSensorParameterValueHash($sensor_id, $sensor_parameter_id, $tag_value[$i], 164, 3, 1, $tag_datetime[$i]);
                                if ($response['status'] == 1) {
                                    $result = $response['Items'];
                                    $warnings[] = $response['warnings'];
                                    $errors[] = $response['errors'];
                                    $status *= $response['status'];
                                    $warnings[] = "ProcessModbusData. Значение сенсора $sensor_id Состояние 164 записано в кеш";
                                } else {
                                    $errors[] = $response['errors'];
                                    $warnings[] = $response['warnings'];
                                    throw new \Exception("ProcessModbusData. Значение сенсора $sensor_id Состояние 164 НЕ записано в кеш");
                                }
                            } else {
                                $warnings[] = $response['warnings'];
                                $errors[] = $response['errors'];
                                throw new \Exception("ProcessModbusData. Не удалось получеть ключ конкретного параметра и создать в БД тоже не удалось");
                            }
                            break;
                        case 170:
                            /**
                             * Блок записи значения заряда АКБ БПД-3
                             */
                            //получаем из кеша/БД/создаем в БД конкретный параметер сенсора
                            $response = SensorMainController::GetOrSetSensorParameter($sensor_id, $parameter_id, '3');
                            if ($response['status'] == 1) {
                                $warnings[] = $response['warnings'];
                                $errors[] = $response['errors'];
                                $status *= $response['status'];
                                $sensor_parameter_id = $response['sensor_parameter_id'];

                                $response = $this->writeBpdCharge($sensor_parameter_id, $sensor_id, $parameter_id, $tag_value[$i], $tag_datetime[$i], $mine_id); //Вызываем метод записи заряда блока питания
                                if ($response['status'] == 1) {
                                    $result = $response['Items'];
                                    $warnings[] = $response['warnings'];
                                    $errors[] = $response['errors'];
                                    $status *= $response['status'];
                                    $warnings[] = "ProcessModbusData. Значение сенсора $sensor_id Состояние 170 записано в БД";
                                } else {
                                    $errors[] = $response['errors'];
                                    $warnings[] = $response['warnings'];
                                    throw new \Exception("ProcessModbusData. Значение сенсора $sensor_id Состояние 170 НЕ записано в БД");
                                }

                                $response = $sensor_cache_controller->setSensorParameterValueHash($sensor_id, $sensor_parameter_id, $tag_value[$i], 170, 3, 1, $tag_datetime[$i]);
                                if ($response['status'] == 1) {
                                    $result = $response['Items'];
                                    $warnings[] = $response['warnings'];
                                    $errors[] = $response['errors'];
                                    $status *= $response['status'];
                                    $warnings[] = "ProcessModbusData. Значение сенсора $sensor_id Состояние 170 записано в кеш";
                                } else {
                                    $errors[] = $response['errors'];
                                    $warnings[] = $response['warnings'];
                                    throw new \Exception("ProcessModbusData. Значение сенсора $sensor_id Состояние 170 НЕ записано в кеш");
                                }
                            } else {
                                $warnings[] = $response['warnings'];
                                $errors[] = $response['errors'];
                                throw new \Exception("ProcessModbusData. Не удалось получеть ключ конкретного параметра и создать в БД тоже не удалось");
                            }
                            break;
                        default:
                            /**
                             * Блок записи остальных значений в БД
                             */
                            //получаем из кеша/БД/создаем в БД конкретный параметер сенсора
                            $response = SensorMainController::GetOrSetSensorParameter($sensor_id, $parameter_id, '2');
                            if ($response['status'] == 1) {
                                $warnings[] = $response['warnings'];
                                $errors[] = $response['errors'];
                                $status *= $response['status'];
                                $sensor_parameter_id = $response['sensor_parameter_id'];

                                $response = $this->writeSensorParameterValue($sensor_parameter_id, $sensor_id, $parameter_id, $tag_value[$i], $tag_datetime[$i], 2); //Вызываем метод записи заряда блока питания
                                if ($response['status'] == 1) {
                                    $result = $response['Items'];
                                    $warnings[] = $response['warnings'];
                                    $errors[] = $response['errors'];
                                    $status *= $response['status'];
                                    $warnings[] = "ProcessModbusData. Значение сенсора $sensor_id Состояние $parameter_id записано в БД";
                                } else {
                                    $errors[] = $response['errors'];
                                    $warnings[] = $response['warnings'];
                                    throw new \Exception("ProcessModbusData. Значение сенсора $sensor_id Состояние $parameter_id НЕ записано в БД");
                                }
                                $sensor_parameter_to_cache[] = $sensor_cache_controller::buildStructureSensorParametersValue($sensor_id, $sensor_parameter_id, $parameter_id, 2, $tag_datetime[$i], $tag_value[$i], 1);
                            } else {
                                $warnings[] = $response['warnings'];
                                $errors[] = $response['errors'];
                                throw new \Exception("ProcessModbusData. Не удалось получеть ключ конкретного параметра и создать в БД тоже не удалось");
                            }
                    }
                }
                /**
                 * массовая вставка в кеш значений параметров блоков питания
                 */
                if ($sensor_parameter_to_cache) {
                    $response = $sensor_cache_controller->multiSetSensorParameterValueHash($sensor_parameter_to_cache);
                    if ($response['status'] == 1) {
                        $result = $response['Items'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        $status *= $response['status'];
                        $warnings[] = "ProcessModbusData. Значение сенсора $sensor_id Состояние $parameter_id записано в кеш";
                    } else {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        throw new \Exception("ProcessModbusData. Значение сенсора $sensor_id Состояние $parameter_id НЕ записано в кеш");
                    }
                } else {
                    $warnings[] = "ProcessModbusData. Массовая вставка в кеш не осуществлялась, т.к. не переданы параметры для вставки. Причина - ни один из массовых параметров не изменился или не был передан в метод";
                }
                $result = "all ok";
            } else {
                $this->debugMessage('Sensor не найден не в кеше не в бд ' . $ip . ' не найден', $this->is_debug);
            }
        } catch (\Throwable $e) {
            $status = 0;
            $errors[] = "ProcessModbusData. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = $result_main;
    }


    /**
     * writeBpdCharge - Метод записи параметра Заряд БПД
     * @param $sensor_parameter_id - ключ конкретного параметра датчика
     * @param $sensor_id - ключ сенсора
     * @param $parameter_id - ключ справочного параметра сенсора
     * @param $value - значение
     * @param $tag_datetime - время измерения
     * @param $mine_id - ключ шахты
     */
    public function writeBpdCharge($sensor_parameter_id, $sensor_id, $parameter_id, $value, $tag_datetime, $mine_id)
    {
        //Если id параметра == 170
        $errors = array();                                                                                                //массив ошибок
        $flag_save = 0;                                                                                                   //флаг сохранения значения и генерации события
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = "writeBpdCharge.Начал выполнять метод";
        try {
            /**
             * проверяем значение 170 параметра и исходя из этого меняем его статус_айди и событие которое нужно генерировать
             */
            switch ($value) {
                case '20-40':
                    //Генерируем событие "Отказ ИБП3 Связь отсутсвует"
                    $warnings[] = "writeBpdCharge. БПД-3 Низкий заряд АКБ 20-40%";
                    $status_id = StatusEnumController::EMERGENCY_VALUE;
                    $event_id = EventEnumController::BPD_LOW_BATTERY;
                    break;

                default:
                    $warnings[] = "writeBpdCharge. БПД-3 Низкий заряд АКБ в норме";
                    $status_id = StatusEnumController::NORMAL_VALUE;
                    $event_id = EventEnumController::BPD_LOW_BATTERY;
                    break;
            }
            /**
             * получаем место установки сенсора, в данном случае блока питания для привязки к нему события
             * еслди его нет, то пишем значение -1
             */
            $edge_value = (new SensorCacheController())->getParameterValueHash($sensor_id, 269, 1);
            if ($edge_value) {
                $edge_id = (int)$edge_value['value'];
            } else {
                $edge_id = -1;
            }

            /**
             * Блок проверки на изменение значение параметра
             */
            //Получем старое значение sensor_parameter_value. Если его нет, то сразу пишем в БД
            // если оно есть, то сравниваем с предыдущим и если оно не равно, то пишем в БД
            // если они равны и прошло меньше 5 минут, то не пишем, в ином случае пишем
            // во всех случаях генерируем события
            $sensor_parameter_value = (new SensorCacheController())->getParameterValueHash($sensor_id, $parameter_id, 3);
            $delta_time = strtotime($tag_datetime) - strtotime($sensor_parameter_value['date_time']);
            if ($sensor_parameter_value) {
                if ($value != $sensor_parameter_value['value']) {
                    $warnings[] = "writeBpdCharge. Значение параметра 170 сенсора изменилось. пишем в БД";
                    $flag_save = 1;   //значение поменялось, пишем сразу
                } elseif ($delta_time >= 60) {
                    $warnings[] = "writeBpdCharge. Дельта времени: " . $delta_time;
                    $warnings[] = "writeBpdCharge. Прошло больше 1 минуты с последней записи в БД. Пишем в БД";
                    $warnings[] = "writeBpdCharge. Старое время: " . $sensor_parameter_value['date_time'];
                    $warnings[] = "writeBpdCharge. Новое время: " . $tag_datetime;
                    $flag_save = 1;   //прошло больше 5 минут с последней записи в БД, пишем сразу
                } else {
                    $flag_save = 0;
                    $warnings[] = "writeBpdCharge. Значение не поменялось и время не прошло больше 1 минуты";
                }
            } else {
                $warnings[] = "writeBpdCharge. Нет предыдущих значений по параметру 170. пишем в БД сразу";
                $flag_save = 1;       //нет предыдущих данных, пишем сразу
            }

            /**
             * блок записи значения параметра в БД и генерации события
             */
            if ($flag_save == 1) {
                //пишем значение в БД
                $response = SensorBasicController::addSensorParameterValue($sensor_parameter_id, $value, $status_id, $tag_datetime);
                if ($response['status'] == 1) {
                    $sensor_parameter_value_id = $response['sensor_parameter_value_id'];
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $warnings[] = "writeBpdCharge. Сохранение значения состояния БПД3 прошло успешно. Ключ добавленного значения $sensor_parameter_value_id";
                } else {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new \Exception("writeBpdCharge. Сохранение значения состояния БПД3 не удалось");
                }
                //генерируем событие
                $response = EventMainController::createEventFor("sensor", $sensor_id, $event_id, $value,
                    $tag_datetime, $status_id, $parameter_id, $mine_id, StatusEnumController::EVENT_RECEIVED, $edge_id);
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $warnings[] = "writeBpdState. Событие успешно сохранено";
                } else {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new \Exception("writeBpdState. Ошибка сохранения события");
                }
                $status *= 1;
            }
        } catch (\Throwable $e) {
            $status = 0;
            $errors[] = "writeBpdCharge.Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "writeBpdCharge.Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * writeSensorParameterValue - Метод записи обычных параметров БПД в БД с проверкой на старые значения
     * @param $sensor_id - id датчика
     * @param $parameter_id - id параметра
     * @param $value - значение
     * @param $tag_datetime - время
     * @param $ip - ip адрес
     * @param $port - порт
     * @param $dcs_id - id ссд
     */
    public function writeSensorParameterValue($sensor_parameter_id, $sensor_id, $parameter_id, $value, $tag_datetime, $parameter_type_id)
    {
        //Если id параметра == $parameter_id
        $errors = array();                                                                                                //массив ошибок
        $flag_save = 0;                                                                                                   //флаг сохранения значения и генерации события
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = "writeSensorParameterValue.Начал выполнять метод";
        try {
            /**
             * проверяем значение $parameter_id параметра и исходя из этого меняем его статус_айди и событие которое нужно генерировать
             */
            $status_id = 1;

            /**
             * Блок проверки на изменение значение параметра
             */
            //Получем старое значение sensor_parameter_value. Если его нет, то сразу пишем в БД
            // если оно есть, то сравниваем с предыдущим и если оно не равно, то пишем в БД
            // если они равны и прошло меньше 5 минут, то не пишем, в ином случае пишем
            // во всех случаях генерируем события
            //todo вытащть этот блок в отдельный метод - используется в других местах
            $sensor_parameter_value = (new SensorCacheController())->getParameterValueHash($sensor_id, $parameter_id, $parameter_type_id);
            $delta_time = strtotime($tag_datetime) - strtotime($sensor_parameter_value['date_time']);
            if ($sensor_parameter_value) {
                if ($value != $sensor_parameter_value['value']) {
                    $warnings[] = "writeSensorParameterValue. Значение параметра $parameter_id сенсора изменилось. пишем в БД";
                    $flag_save = 1;   //значение поменялось, пишем сразу
                } elseif ($delta_time >= 60) {
                    $warnings[] = "writeSensorParameterValue. Дельта времени: " . $delta_time;
                    $warnings[] = "writeSensorParameterValue. Прошло больше 1 минуты с последней записи в БД. Пишем в БД";
                    $warnings[] = "writeSensorParameterValue. Старое время: " . $sensor_parameter_value['date_time'];
                    $warnings[] = "writeSensorParameterValue. Новое время: " . $tag_datetime;
                    $flag_save = 1;   //прошло больше 5 минут с последней записи в БД, пишем сразу
                } else {
                    $flag_save = 0;
                    $warnings[] = "writeSensorParameterValue. Значение не поменялось и время не прошло больше 1 минуты";
                }
            } else {
                $warnings[] = "writeSensorParameterValue. Нет предыдущих значений по параметру $parameter_id. пишем в БД сразу";
                $flag_save = 1;       //нет предыдущих данных, пишем сразу
            }

            /**
             * блок записи значения параметра в БД и генерации события
             */
            if ($flag_save == 1) {
                //пишем значение в БД
                $response = SensorBasicController::addSensorParameterValue($sensor_parameter_id, $value, $status_id, $tag_datetime);
                if ($response['status'] == 1) {
                    $sensor_parameter_value_id = $response['sensor_parameter_value_id'];
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $warnings[] = "writeSensorParameterValue. Сохранение значения состояния БПД3 прошло успешно. Ключ добавленного значения $sensor_parameter_value_id";
                } else {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new \Exception("writeSensorParameterValue. Сохранение значения состояния БПД3 не удалось");
                }
                $status *= 1;
            }
        } catch (\Throwable $e) {
            $status = 0;
            $errors[] = "writeSensorParameterValue.Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "writeSensorParameterValue.Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод записи sensor_parameter
     * @param $sensor_id
     * @param $parameter_id
     * @param $parameter_type_id
     */
    public function writeSensorParameter($sensor_id, $parameter_id, $parameter_type_id)
    {
        $sensor_parameter = new SensorParameter();
        $sensor_parameter->sensor_id = $sensor_id;
        $sensor_parameter->parameter_id = $parameter_id;
        $sensor_parameter->parameter_type_id = $parameter_type_id;
        if (!$sensor_parameter->save()) {
            echo nl2br("Ошибка создания sensor_parameter в БД \n");
            var_dump($sensor_parameter->errors);
        }
    }

    /**
     * writeBpdState - Метод обработки и записи параметра Состояние
     * @param $sensor_id - id датчика
     * @param $parameter_id - id параметра
     * @param $value - значение
     * @param $tag_datetime - время
     * @param $ip - ip адрес
     * @param $port - порт
     * @param $dcs_id - id ссд
     */
    public function writeBpdState($sensor_parameter_id, $sensor_id, $parameter_id, $value, $tag_datetime, $mine_id)
    {
        //Если id параметра == 164
        $errors = array();                                                                                                //массив ошибок
        $flag_save = 0;                                                                                                   //флаг сохранения значения и генерации события
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = "writeBpdState.Начал выполнять метод";
        try {
            /**
             * проверяем значение 164 параметра и исходя из этого меняем его статус_айди и событие которое нужно генерировать
             */
            switch ($value) {
                case '0':
                    //Генерируем событие "Отказ ИБП3 Связь отсутсвует"
                    $warnings[] = "writeBpdState. Генерируем событие Отказ ИБП3 Связь отсутсвует";
                    $status_id = StatusEnumController::EMERGENCY_VALUE;
                    $event_id = EventEnumController::BPD_STOP;
                    break;
                case '1':
                    //Генерируем событие "Подано внешнее питание БПД-3"
                    $warnings[] = "writeBpdState. Генерируем событие. Подано внешнее питание БПД-3";
                    $status_id = StatusEnumController::NORMAL_VALUE;
                    $event_id = EventEnumController::BPD_STOP;
                    break;
                case '2':
                    //Генерируем событие "Отказ ИБП3. Отсутствует внешнее питание"
                    $warnings[] = "writeBpdState. Генерируем событие Отказ ИБП3. Отсутствует внешнее питание";
                    $status_id = StatusEnumController::EMERGENCY_VALUE;
                    $event_id = EventEnumController::BPD_STOP;
                    break;
                default:
                    $warnings[] = "writeBpdState. Не верное значение параметра 164";
                    $status_id = StatusEnumController::NOT_ACTUAL;
                    $event_id = EventEnumController::BPD_STOP;
                    break;
            }
            /**
             * получаем место установки сенсора, в данном случае блока питания для привязки к нему события
             * если его нет, то пишем значение -1
             */
            $edge_value = (new SensorCacheController())->getParameterValueHash($sensor_id, 269, 1);
            if ($edge_value) {
                $edge_id = (int)$edge_value['value'];
            } else {
                $edge_id = -1;
            }

            /**
             * Блок проверки на изменение значение параметра
             */
            //Получем старое значение sensor_parameter_value. Если его нет, то сразу пишем в БД
            // если оно есть, то сравниваем с предыдущим и если оно не равно, то пишем в БД
            // если они равны и прошло меньше 5 минут, то не пишем, в ином случае пишем
            // во всех случаях генерируем события
            $sensor_parameter_value = (new SensorCacheController())->getParameterValueHash($sensor_id, $parameter_id, 3);
            $delta_time = strtotime($tag_datetime) - strtotime($sensor_parameter_value['date_time']);
            if ($sensor_parameter_value) {
                if ($value != $sensor_parameter_value['value']) {
                    $warnings[] = "writeBpdState. Значение параметра 164 сенсора изменилось. пишем в БД";
                    $flag_save = 1;   //значение поменялось, пишем сразу
                } elseif ($delta_time >= 60) {
                    $warnings[] = "writeBpdState. Дельта времени: " . $delta_time;
                    $warnings[] = "writeBpdState. Прошло больше 1 минуты с последней записи в БД. Пишем в БД";
                    $warnings[] = "writeBpdState. Старое время: " . $sensor_parameter_value['date_time'];
                    $warnings[] = "writeBpdState. Новое время: " . $tag_datetime;
                    $flag_save = 1;   //прошло больше 5 минут с последней записи в БД, пишем сразу
                } else {
                    $flag_save = 0;
                    $warnings[] = "writeBpdState. Значение не поменялось и время не прошло больше 1 минуты";
                }
            } else {
                $warnings[] = "writeBpdState. Нет предыдущих значений по параметру 164. пишем в БД сразу";
                $flag_save = 1;       //нет предыдущих данных, пишем сразу
            }

            /**
             * блок записи значения параметра в БД и генерации события
             */
            if ($flag_save == 1) {
                //пишем значение в БД
                $response = SensorBasicController::addSensorParameterValue($sensor_parameter_id, $value, $status_id, $tag_datetime);
                if ($response['status'] == 1) {
                    $sensor_parameter_value_id = $response['sensor_parameter_value_id'];
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $warnings[] = "writeBpdState. Сохранение значения состояния БПД3 прошло успешно. Ключ добавленного значения $sensor_parameter_value_id";
                } else {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new \Exception("writeBpdState. Сохранение значения состояния БПД3 не удалось");
                }
                //генерируем событие
                $response = EventMainController::createEventFor("sensor", $sensor_id, $event_id, $value,
                    $tag_datetime, $status_id, $parameter_id, $mine_id, StatusEnumController::EVENT_RECEIVED, $edge_id);
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $warnings[] = "writeBpdState. Событие успешно сохранено";
                } else {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new \Exception("writeBpdState. Ошибка сохранения события");
                }
                $status *= 1;
            }
        } catch (\Throwable $e) {
            $status = 0;
            $errors[] = "writeBpdState.Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "writeBpdState.Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод записи данных в таблицу sensor_parameter_value
     * @param $ip
     * @param $sensor
     * @param $parameter
     * @param $sensor_parameter_id
     * @param $tag_datetime
     * @param $tag_value
     * @param $status_id
     * @return int | false
     */
    public static function writeModbusValues($ip, $sensor, $parameter, $sensor_parameter_id, $tag_datetime, $tag_value, $status_id)
    {
        //$this->debugMessage("В методе записи значений", $this->is_debug);
        echo nl2br("IP: $ip | Sensor_parameter ID: $sensor_parameter_id | sensor: $sensor | parameter: $parameter | tag_datetime: $tag_datetime | value: $tag_value | status_id: $status_id \n");
        //$date = date('Y-m-d H:i:s.U', strtotime($tag_datetime));
        $sensor_parameter_value = new SensorParameterValue();
        $sensor_parameter_value->sensor_parameter_id = $sensor_parameter_id;                                            //Сохраняем sensor-parameter_id
        //$sensor_parameter_value->date_time = $date;                                                   //Сохраняем время получения тега
        $sensor_parameter_value->date_time = $tag_datetime;                                                   //Сохраняем время получения тега
        $sensor_parameter_value->value = (string)$tag_value;                                                            //Сохраняем значение тега
        $sensor_parameter_value->status_id = $status_id;                                                                //Сохраняем статус
        if ($sensor_parameter_value->save()) {
            return $sensor_parameter_value->id;
        }
        echo nl2br("Ошибка записи значений в БД \n");
        var_dump($sensor_parameter_value->errors);
        return false;
    }

    // debugMessage - вывод отладочных сообщений на экран при работе кода, в местах вызова
    // если глобальная переменная $is_debug включена, то осуществляется вывод отладочных сообщений в местах вызова данной функции
    public function debugMessage($message, $is_debug)
    {
        if ($is_debug) {
            echo nl2br($message . "\n");
        }
    }

    /**
     * saveBpdPackage - Метод записи пакетов БПД-3 в таблицу bpd_package_info в исходном виде, как они приходят со службы
     * используется для отладки работы службы и в случае появления ошибок для воспроизведения поведения методов обработки данных
     * @param $parameter - строка с параметрами перечисленными через ~
     * @param $value - строка с значениями перечисленными через ~
     * @param $date_time - временная метка
     * @param $ip - ip адрес БПД-3
     * Пример запроса:
     * http://..../settings-dcs/save-bpd-package?parameter=164&value=1&date_time=2018-09-21%2006:23:58.562&ip=172.22.59.10
     */
    public function saveBpdPackage($parameter, $value, $date_time, $ip)
    {
        $package = $parameter . $value;
        $date = date('Y-m-d H:i:s.U', strtotime($date_time));
        $bpd_package = new BpdPackageInfo();
        $bpd_package->ip = $ip;
        $bpd_package->package = $package;
        $bpd_package->date_time = $date;
        if (!$bpd_package->save()) {
            echo nl2br("Ошибка записи значений в bpd_package_info \n");
            var_dump($bpd_package->errors);
        }
    }

    /**
     * Название метода: AddCacheSensorIP()
     * Метод добавляет в кеш ключ сенсора по ключу службы сбора данных и по айпи опрашиваемого устройства для устройств круппы Modbus
     * @param $dcs_id
     *
     * @package backend\controllers
     *
     * Входные обязательные параметры:
     * $dcs_id  - идентификатор dcs
     * $ip      - айпи айдрес опрашиваемого устройства
     * выходные параметры:
     *      sensor_id   -   ключ сенсора
     *      стандартный набор
     * @see
     * @example http://127.0.0.1/admin/settings-dcs/test?dcs_id=1400&ip=172.22.51.445
     * @example http://127.0.0.1/admin/settings-dcs/test?dcs_id=1400&ip=172.22.51.44
     *
     * @author Якимов М.Н.
     * Created date: on 02.06.2019 15:24
     * @since ver
     */
    public function AddCacheSensorIP($dcs_id, $ip)
    {
        $warnings[] = "AddCacheSensorIP. Начинаю добавление в кеш строки подключения для службы $dcs_id и для IP = $ip";
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // результирующий массив
        try {
            $connects_array = array();                                                                                  // массив строк подключения для опроса службой
            $arr_cache_ip = array();                                                                                    // массив для массовой вставки ключей в кеш

            $settingsDCS = ConnectString::find()
                ->where([
                    'Settings_DCS_id' => $dcs_id,
                    'source_type' => 'MODBUS',
                    'ip' => $ip
                ])
                ->with('sensorConnectString')
                ->limit(1)
                ->one();

            if ($settingsDCS) {
                $warnings[] = "AddCacheSensorIP. нашел строку подключения. Пакую в кеш.";
                $key = 'ConnectString_' . $settingsDCS->ip;
                $sensor_id = $settingsDCS->sensorConnectString['sensor_id'];

                (new ServiceCache())->amicum_rSet($key, $sensor_id);
                $warnings[] = "AddCacheSensorIP. Запоковал в кеш ключ $key и значение сенсора $sensor_id";
                $result = $sensor_id;
            } else {
                throw new \Exception("AddCacheSensorIP. В кеше и в БД привязка IP к сенсору не задана");
            }
        } catch (\Throwable $e) {
            $status = 0;
            $errors[] = "AddCacheSensorIP. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "AddCacheSensorIP. Метод завершил";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод для проверки отправки SMS сообщения
     * Принимает параметры методом get:
     * num - номер телефона в формате 7**********
     * msg - текст сообщения
     * Пример запроса:
     * http://..../settings-dcs/test-sms-send?num=7**********&msg=Привет
     *
     */
    public function actionTestSmsSend()
    {
        $get = Yii::$app->request->get();
        $phone_number = $get['num'];
        $text_message = $get['msg'];

        include 'SmsSender.php';
        $send_sms = new \SmsSend();

        echo nl2br("Отправляю сообщение на номер : $phone_number \n");
        echo nl2br("Текст сообщения : $text_message \n");

        echo nl2br('Вызов функции sendSmsMessage');
        $send_sms->sendSmsMessage($phone_number, $text_message);

    }

//    public function actionTest()
//    {
//        $get = Assistant::GetServerMethod();
//
//        $dcs_id = $get['dcs_id'];
//        $ip = $get['ip'];
//        $result = $this->AddCacheSensorIP($dcs_id, $ip);
//
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = $result;
//    }
}
