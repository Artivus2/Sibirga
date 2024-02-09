<?php


namespace backend\controllers;

use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\cachemanagers\ServiceCache;
use Yii;
use yii\base\InvalidValueException;
use yii\db\Query;
use yii\httpclient\Exception;
use yii\web\Controller;
use yii\web\Response;

class SnmpController extends Controller
{
    /**
     * Получение строк подключения к устройствам по протоколу SNMP
     * Необходимые POST поля:
     *   dcs_id - идентификатор службы сбора данных
     *
     * @example
     * http://127.0.0.1/admin/snmp/get-connect-strings?dcs_id=1390
     */
    public static function actionGetConnectStrings()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $connect_strings = array();

        try {
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            $post = Assistant::GetServerMethod();

            $post_valid = isset($post['dcs_id']);
            if (!$post_valid) {
                throw new \InvalidArgumentException(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['dcs_id'])) {
                throw new \InvalidArgumentException(__FUNCTION__ . '. Не передан идентификатор службы сбора данных');
            }

            $dcs_id = $post['dcs_id'];

            /**=================================================================
             * Получение строк подключения
             * ===============================================================*/
            $connect_strings = (new Query())
                ->select([
                    'connect_string.ip as ip',
                    'sensor_connect_string.sensor_id as sensor_id'
                ])
                ->from('connect_string')
                ->innerJoin('sensor_connect_string', 'sensor_connect_string.connect_string_id = connect_string.id')
                ->where([
                    'connect_string.source_type' => 'SNMP',
                    'connect_string.Settings_DCS_id' => $dcs_id
                ])
                ->all();

            if (!$connect_strings)
                throw new InvalidValueException(__FUNCTION__ . '. В базе нет строк подключения');

        } catch (InvalidValueException $exception) {
            $status = 0;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors[__FUNCTION__ . ' parameters'] = [
                'dcs_id' => $dcs_id
            ];
        } catch (\Throwable $exception) {
            $status = 0;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $result = array('Items' => $connect_strings, 'status' => $status,
            'warnings' => $warnings, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }


    /**
     * Сохранение параметра "Состояние" для сенсора.
     * Необходимые POST поля:
     *   sensor_id - идентификатор сенсора
     *   value - значение параметра "Состояние". Возможные значения: 0, 1, 2
     *
     * Выполняет роль прототипа для службы опроса устройств по протоколу SNMP.
     * Пока неизвестно какие адреса отвечают за какие переменные, чтобы вытащить
     * нужные.
     *
     * @example
     * http://127.0.0.1/admin/snmp/update-sensor-state?sensor_id=28044&value=1
     * http://127.0.0.1/admin/snmp/update-sensor-state?sensor_id=28044&value=1&mine_id=139119
     */
    public static function actionUpdateSensorState()
    {
        $status = 1;
        $warnings = array();
        $errors = array();

        try {
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            $post = Assistant::GetServerMethod();

            $post_valid = isset($post['paramets']);
            if (!$post_valid) {
                throw new \InvalidArgumentException(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['paramets'])) {
                throw new \InvalidArgumentException(__FUNCTION__ . '. Не передан Json с параметрами');
            }

            $parameters = json_decode($post['paramets'], true);
            if ($parameters === null) {
                throw new \Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post['paramets']);
            }

            $sensor_id = $parameters['sensor_id'];
            $value = $parameters['value'];

            if (!in_array($value, [0, 1, 2])) {
                throw new \InvalidArgumentException(__FUNCTION__ . '. Передано неправльное значение параметра: ' . $value);
            }

            $mine_id = AMICUM_DEFAULT_MINE;
            if (isset($parameters['mine_id']) && $parameters['mine_id'] != '') {
                $mine_id = $parameters['mine_id'];
            }

            /**=================================================================
             * Поиск сенсора в кэше. Если надо, то его инициализация
             * ===============================================================*/
            $sensor_cache_controller = new SensorCacheController();
            if ($sensor_cache_controller->getSensorMineBySensorHash($sensor_id) === false) {
                $response = SensorMainController::initSensorInCache($sensor_id);
                if ($response['status'] == 1) {
                    //$warnings[] = $response['warnings'];
                } else {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new \Exception(__FUNCTION__ . '. Ошибка при инициализации сенсора: ' . $sensor_id);
                }
            } else {
                $response = SensorMainController::moveSensorMineInitCache($sensor_id, $mine_id);
                if ($response['status'] == 1) {
                    //$warnings[] = $response['warnings'];
                } else {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new \Exception(__FUNCTION__ . ". Ошибка при перемещении сенсора $sensor_id из кеша шахты");
                }
            }

            /**=================================================================
             * Сохранение параметра "Состояние", если оно больше неактуально
             * ===============================================================*/
            $date_time = Assistant::GetDateNow();
            $response = StrataJobController::saveSensorParameter($sensor_id, 3, 164, $value, $date_time, 1);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                throw new \Exception(__FUNCTION__ . ". Ошибка при перемещении сенсора $sensor_id из кеша шахты");
            }


        } catch (\Throwable $exception) {
            $status = 0;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $result = array('Items' => '', 'status' => $status,
            'warnings' => $warnings, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }


    /**
     * Сохранение параметров для сенсора
     * Необходимые POST поля:
     *   arg - JSON строка с информацией о параметрах. Содержит:
     *     mine_id - идентификатор шахты (необязательно)
     *     sensor_id - идентификатор сенсора
     *     parameters - массив с объектами параметров.
     *
     * Элемент parameters выглядит следующим образом (пример):
     * [
     *   'type' : 'state'
     *   'value' : 2
     * ]
     *
     * @example
     * http://127.0.0.1/admin/snmp/save-sensor-parameters?arg={"sensor_id":"28044","mine_id":"290","parameters":[{"type":"state","value":"1"},{"type":"voltage","value":"12,34"}]}
     */
    public static function actionSaveSensorParameters()
    {
        $status = 1;
        $warnings = array();
        $errors = array();

        try {
            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            $post = Assistant::GetServerMethod();

            $post_valid = isset($post['arg']);
            if (!$post_valid) {
                throw new \InvalidArgumentException(__FUNCTION__ . '. Не все входные параметры инициализированы');
            }

            if (empty($post['arg'])) {
                throw new \InvalidArgumentException(__FUNCTION__ . '. Не передан Json с параметрами');
            }

            $arg = json_decode($post['arg'], true);
            if ($arg === null) {
                throw new \Exception(__FUNCTION__ . '. Получен невалидный json: ' . $post['arg']);
            }

            $parameters = $arg['parameters'];
            $sensor_id = $arg['sensor_id'];

            $mine_id = AMICUM_DEFAULT_MINE;
            if (isset($arg['mine_id']) && $arg['mine_id'] != '') {
                $mine_id = $arg['mine_id'];
            }
            //если нет разрешения на запись, то метод не выполняется
            if (!(new ServiceCache())->CheckDcsStatus($mine_id, 'snmpStatus')) {
                throw new Exception("Нет разрешения на запись");
            }

            /**=================================================================
             * Поиск сенсора в кэше. Если надо, то его инициализация
             * ===============================================================*/
            $sensor_cache_controller = new SensorCacheController();
            if ($sensor_cache_controller->getSensorMineBySensorHash($sensor_id) === false) {
                $response = SensorMainController::initSensorInCache($sensor_id);
                if ($response['status'] == 1) {
                    //$warnings[] = $response['warnings'];
                } else {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new \Exception(__FUNCTION__ . '. Ошибка при инициализации сенсора: ' . $sensor_id);
                }
            } else {
                $response = SensorMainController::moveSensorMineInitCache($sensor_id, $mine_id);
                if ($response['status'] == 1) {
                    //$warnings[] = $response['warnings'];
                } else {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new \Exception(__FUNCTION__ . ". Ошибка при перемещении сенсора $sensor_id из кеша шахты");
                }
            }

            /**=================================================================
             * Сохранение параметров
             * ===============================================================*/
            /**
             * получаем за раз все последние значения по сенсору из кеша
             */
            $start = microtime(true);
            $sensor_cache_controller = new SensorCacheController();
            $sensor_parameter_value_list_cache = $sensor_cache_controller->multiGetParameterValueHash($sensor_id, '*', '*', true);
            foreach ($sensor_parameter_value_list_cache as $sensor_parameter_value_cache) {
                $sensor_parameter_value_cache_array[$sensor_parameter_value_cache['sensor_id']][$sensor_parameter_value_cache['parameter_type_id']][$sensor_parameter_value_cache['parameter_id']] = $sensor_parameter_value_cache;
            }
            //$warnings[__FUNCTION__ . '. получаем за раз все последние значения по сенсору из кеша'] = microtime(true) - $start;

            $date_time = Assistant::GetDateNow();

            // Перебор параметров, полученных из службы и подготовка структуры
            // для укладки данных в БД и кэш
            foreach ($parameters as $parameter) {
                switch ($parameter['type']) {
                    case 'temperature':
                        $parameter_id = 9;
                        $parameter_type_id = 2;
                        break;
                    case 'voltage':
                        $parameter_id = 95;
                        $parameter_type_id = 2;
                        $parameter['value'] /= 100;
                        break;
                    case 'voltage_port_1':
                        $parameter_id = 685;
                        $parameter_type_id = 2;
                        break;
                    case 'name_conn_port':
                        $parameter_id = 695;
                        $parameter_type_id = 2;
                        break;
                    case 'used_memory':
                        $parameter_id = 698;
                        $parameter_type_id = 2;
                        break;
                    case 'state':
                        $parameter_id = 164;
                        $parameter_type_id = 3;
                        break;
                    default:
                        $warnings[] = __FUNCTION__ . '. Получен неизвестный тип параметра: ' . $parameter['type'];
                        continue 2; // пропускаем итерацию цикла
                }
                $response = StrataJobController::saveSensorParameterBatch($sensor_id, $parameter_type_id, $parameter_id, $parameter['value'], $date_time, 1, $sensor_parameter_value_cache_array);
                if ($response['status'] == 1) {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    if ($response['date_to_cache']) {
                        $date_to_cache[] = $response['date_to_cache'];
                    }
                    if ($response['date_to_db']) {
                        $date_to_db[] = $response['date_to_db'];
                    }
                } else {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new \Exception(__FUNCTION__ . '. Ошибка сохранения параметра ' . $parameter_id);
                }
            }

            /**=================================================================
             * блок массовой вставки значений в БД
             * =================================================================*/
            $start = microtime(true);
            if (isset($date_to_db)) {
                Yii::$app->db->createCommand()->batchInsert('sensor_parameter_value',
                    ['sensor_parameter_id', 'date_time', 'value', 'status_id'],
                    $date_to_db)->execute();
            }
            //$warnings[__FUNCTION__ . '. блок массовой вставки значений в БД'] = microtime(true) - $start;

            /**=================================================================
             * блок массовой вставки значений в кеш
             * =================================================================*/
            $start = microtime(true);
            if (isset($date_to_cache)) {
                //$warnings[] = $sensor_id;
                $ask_from_method = $sensor_cache_controller->multiSetSensorParameterValueHash($date_to_cache);
                if ($ask_from_method['status'] == 1) {
                    //$warnings[] = $ask_from_method['warnings'];
                    //$warnings[] = 'saveLocationPacketSensorParameters. обновил параметры сенсора в кеше';
                } else {
                    //$warnings[] = $ask_from_method['warnings'];
                    $errors[] = $ask_from_method['errors'];
                    throw new \Exception(__FUNCTION__ . '. Не смог обновить параметры в кеше сенсора' . $sensor_id);
                }
            }
            //$warnings[__FUNCTION__ . '. блок массовой вставки значений в кеш'] = microtime(true) - $start;


        } catch (\Throwable $exception) {
            $status = 0;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $result = array('Items' => '', 'status' => $status,
            'warnings' => $warnings, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }


    // actionGetListTags        - Метод получения списка тегов для OPC сервера по конкретному сенсор_айди
    // actionGetConfig          - Метод получения конфигурации службы OPC из БД на основе идентификатора службы
    // actionSetTagValue        - Метод сохранения значения тегов в БД с OPC службы


    // actionGetListTags - Метод получения списка тегов для OPC сервера по конкретному сенсор_айди
    // входные параметры:
    //  sensor_id - ключ OPC сервеа - фактически это ключ сенсора
    // выходные параметры:
    //  items:      - результат работы метода
    //      tag_name:   - имя опрашиваемого тега
    //          sensor_id           - ключ сенсора OPC сервера
    //          sensor_parameter_id - ключ конкретного значения конкретного тега
    //          parameter_id        - ключ названия тега
    //          parameter_type_id   - ключ типа параметра тега 1-справочное/2-измеренное/3-вычисленное
    //  status:     - состояние по работе метода (0 - выполнен с ошибками, 1 выполнен полностью и без ошибок)
    //  errors:     - массив ошибок при работе метода
    //  warnings:   - массив предупреждений и отладочной информации при работе метода
    // разработал: Якимов М.Н.
    // дата разработки: 30.05.2019
    // пример вызова: http://127.0.0.1/admin/opc/get-list-tags?sensor_id=1350
    public function actionGetListTags()
    {
        $post = Assistant::GetServerMethod();
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();

        try {
            if (isset($post['sensor_id']) and $post['sensor_id'] != "") {
                $sensor_id = $post['sensor_id'];
                $warnings[] = 'actionGetListTegs. Передан входной параметр сенсор_айди: ' . $sensor_id;
                $warnings[] = 'actionGetListTegs. Получение данных из БД.';

                $sensor_tags_list = (new Query())
                    ->select([
                        'sensor_id',
                        'sensor_parameter.id as sensor_parameter_id',
                        'parameter_id',
                        'parameter_type_id',
                        'parameter.title as tag_name'
                    ])
                    ->from('sensor_parameter')
                    ->innerJoin('parameter', 'sensor_parameter.parameter_id=parameter.id')
                    ->where([
                        'sensor_id' => $sensor_id,
                        'parameter_type_id' => 2
                    ])
                    ->limit(25000)
                    ->all();

                if ($sensor_tags_list) {
                    foreach ($sensor_tags_list as $sensor_tags) {
                        $result[$sensor_tags['tag_name']]['sensor_id'] = $sensor_tags['sensor_id'];
                        $result[$sensor_tags['tag_name']]['sensor_parameter_id'] = $sensor_tags['sensor_parameter_id'];
                        $result[$sensor_tags['tag_name']]['parameter_id'] = $sensor_tags['parameter_id'];
                        $result[$sensor_tags['tag_name']]['parameter_type_id'] = $sensor_tags['parameter_type_id'];
                    }

                    /**
                     * Проверяем наличие детальных сведений о сенсоре в кеше, если нет, то инициализируем сенсор в кеше со всеми параметрами
                     */
                    $sensor_cache = (new SensorCacheController())->getSensorMineBySensorOneHash(-1, $sensor_id);
                    $warnings[] = "actionSetTagValue. Ключ сенсора: ";
                    if (!$sensor_cache) {
                        $warnings[] = "actionSetTagValue. Ключ сенсора:  в кеше не существует";
                        $warnings[] = "actionSetTagValue. Инициализируем параметры сенсора $sensor_id в кеше";
                        $response = SensorMainController::initSensorInCache($sensor_id);                                    //получить шахту mine_id для искомого сенсора
                        if ($response['status'] == 1) {
                            $result = $response['Items'];
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                            $warnings[] = "actionSetTagValue. Кеш сенсора $sensor_id инициализирован";
                        } else {
                            $errors[] = $response['errors'];
                            $warnings[] = $response['warnings'];
                            throw new \Exception("actionSetTagValue. Кеш сенсора $sensor_id не инициализирован");
                        }
                    } else {
                        $warnings[] = "actionSetTagValue. Кеш сенсора $sensor_id уже существовал";
                    }


                } else {
                    throw new \Exception('actionGetListTegs. по запрашиваемому sensor_id, теги не найдены');
                }
            } else {
                throw new \Exception('actionGetListTegs. Не передан входной параметр sensor_id');
            }
        } catch (\Throwable $ex) {
            $errors[] = "actionGetListTegs. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;

    }

    // actionGetConfig - Метод получения конфигурации службы OPC из БД на основе идентификатора службы
    // входные параметры:
    //  $opc_title - название строки подключения OPC сервера
    // выходные параметры:
    //  items:      - результат работы метода
    //      ip              - IP адрес OPC сервера
    //      connect_string  - название OPC сервера к которому подключаемся
    //      sensor_id       - ключ сенсора, в котором в параметрах лежат нужные нам теги
    //  status:     - состояние по работе метода (0 - выполнен с ошибками, 1 выполнен полностью и без ошибок)
    //  errors:     - массив ошибок при работе метода
    //  warnings:   - массив предупреждений и отладочной информации при работе метода
    // разработал: Якимов М.Н.
    // дата разработки: 30.05.2019
    // пример вызова: http://127.0.0.1/admin/opc/get-config?opc_title=OPC%20Test

    public function actionGetConfig()
    {
        $post = Assistant::GetServerMethod();
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();

        try {
            if (isset($post['opc_title']) and $post['opc_title'] != "") {
                $opc_title = $post['opc_title'];
                $warnings[] = 'actionGetConfig. Передан входной параметр: ' . $opc_title;
                $warnings[] = 'actionGetConfig. Получение данных из БД.';

                $connect_string = (new Query())
                    ->select([
                        'ip',
                        'connect_string',
                        'sensor_id'
                    ])
                    ->from('connect_string')
                    ->innerJoin('sensor_connect_string', 'sensor_connect_string.connect_string_id=connect_string.id')
                    ->where([
                        'title' => $opc_title,
                        'source_type' => "OPC"
                    ])
                    ->limit(1)
                    ->one();
                if (!$connect_string) {
                    throw new \Exception('actionGetConfig. По переданному названию OPC сервера, привязанного сенсора не существует');
                }

            } else {
                throw new \Exception('actionGetConfig. Не передан входной параметр opc_title');
            }
        } catch (\Throwable $ex) {
            $errors[] = "actionGetConfig. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $connect_string, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    //TODO ЧТО ЭТОТ МЕТОД ЗДЕСЬ ДЕЛАЕТ?
    // actionSetTagValue - Метод сохранения значения тегов в БД с OPC службы
    // входные параметры:
    //      opc_tags_value:   - имя опрашиваемого тега
    //                  sensor_id           - ключ сенсора OPC сервера
    //                  sensor_parameter_id - ключ конкретного значения конкретного тега
    //                  parameter_id        - ключ названия тега
    //                  parameter_type_id   - ключ типа параметра тега 1-справочное/2-измеренное/3-вычисленное
    //                  TagDate             - дата считывания значения тега (datetime)
    //                  TagValue            - значение конкретного тега
    //                  Quality             - качество тега (status_id - после трансформации)
    // выходные параметры:
    //  items:      - результат работы метода
    //  status:     - состояние по работе метода (0 - выполнен с ошибками, 1 выполнен полностью и без ошибок)
    //  errors:     - массив ошибок при работе метода
    //  warnings:   - массив предупреждений и отладочной информации при работе метода
    // разработал: Якимов М.Н.
    // дата разработки: 30.05.2019
    // пример вызова: http://127.0.0.1/admin/opc/set-tag-value?opc_tags_value={%22TagName%22:%22storage.time.reg12%22,%22TagValue%22:%221%22,%22TagDate%22:%222019-06-07%2009:00:00.630020%22,%22TimeStamp%22:%220001-01-01T00:00:00%22,%22parameter_id%22:%22466%22,%22parameter_type_id%22:%222%22,%22Quality%22:%22badWaitingForInitialData%22,%22sensor_parameter_id%22:%22584652%22,%22sensor_id%22:%221350%22}
    public function actionSetParametrsValues()
    {
        $post = Assistant::GetServerMethod();
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        $warnings[] = "actionSetTagValue. Начал выполнять метод";
        try {
            //$post['opc_tags_value'] = json_decode('{"TagName":"storage.time.reg12","TagValue":"1","TagDate":"2019-06-07 09:00:00.630020","TimeStamp":"0001-01-01T00:00:00","parameter_id":"466","parameter_type_id":"2","Quality":"badWaitingForInitialData","sensor_parameter_id":"584652","sensor_id":"1350"}');
            if (isset($post['opc_tags_value']) and $post['opc_tags_value'] != "") {
                $opc_tags_value = json_decode($post['opc_tags_value']);
                $warnings[] = 'actionSetTagValue. Передан входной параметр';
                $result['parameter_title'] = $opc_tags_value->TagName;
                $result['sensor_parameter_value'] = $opc_tags_value->TagValue;
                $result['sensor_parameter_id'] = $opc_tags_value->sensor_parameter_id;
                $result['parameter_id'] = $opc_tags_value->parameter_id;
                $result['parameter_type_id'] = $opc_tags_value->parameter_type_id;
                $result['sensor_parameter_date_time'] = $opc_tags_value->TagDate;
                $result['sensor_id'] = $opc_tags_value->sensor_id;
                $result['Quality'] = $opc_tags_value->Quality;

                $parameter_title = $opc_tags_value->TagName;                                                            // название тега - он же parameter_title
                $value = $opc_tags_value->TagValue;                                                                     // значение измеренного тега
                $sensor_parameter_id = $opc_tags_value->sensor_parameter_id;                                            // конкретный ключ измеренного тега
                $tag_datetime = $opc_tags_value->TagDate;                                                             // дата и время в которое пришло измеренное значение
                $parameter_id = $opc_tags_value->parameter_id;                                                          // ключ параметра тега
                $parameter_type_id = $opc_tags_value->parameter_type_id;                                                // тип параметра тега
                $quality = $opc_tags_value->Quality;                                                                    // качество полученного значения
                $sensor_id = $opc_tags_value->sensor_id;                                                                // ключ сенсора

                /**
                 * Блок качество полученного значения
                 */
                if ($quality == "good" or $quality == "uncertainEUExceeded[low]") {
                    $sensor_parameter_status_id = 1;
                } else {
                    $sensor_parameter_status_id = 19;
                    $tag_datetime = Assistant::GetDateNow();
                    $value = -1;
                }

                /**
                 * Проверяем наличие детальных сведений о сенсоре в кеше, если нет, то инициализируем сенсор в кеше со всеми параметрами
                 */
                $sensor_cache = (new SensorCacheController())->getSensorMineBySensorOneHash(-1, $sensor_id);//получаем сенсор из кеша
                $warnings[] = "actionSetTagValue. Ключ сенсора: ";
                if (!$sensor_cache) {
                    $warnings[] = "actionSetTagValue. Ключ сенсора:  в кеше не существует";
                    $warnings[] = "actionSetTagValue. Инициализируем параметры сенсора $sensor_id в кеше";
                    $response = SensorMainController::initSensorInCache($sensor_id);                                    //получить шахту mine_id для искомого сенсора
                    if ($response['status'] == 1) {
                        $result = $response['Items'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        $warnings[] = "actionSetTagValue. Кеш сенсора $sensor_id инициализирован";
                    } else {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        throw new \Exception("actionSetTagValue. Кеш сенсора $sensor_id не инициализирован");
                    }
                } else {
                    $warnings[] = "actionSetTagValue. Кеш сенсора $sensor_id уже существовал";
                }

                /**
                 * блок проверки необходимости записи значения параметра в БД и кеш
                 */
                $response = SensorMainController::IsChangeSensorParameterValue($sensor_id, $parameter_id, $parameter_type_id, $value, $tag_datetime);
                if ($response['status'] == 1) {
                    $flag_save = $response['flag_save'];
                    $warnings[] = $response['warnings'];
                    $warnings[] = "actionSetTagValue. Флаг сохранения в БД получен и рачен $flag_save";
                    $errors[] = $response['errors'];
                    $warnings[] = "actionSetTagValue. Кеш сенсора $sensor_id инициализирован";
                } else {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new \Exception("actionSetTagValue. Ошибка обработки флага сохранения значения в БД и кеш для сенсора $sensor_id");
                }

                /**
                 * блок записи значения параметра в БД и в кеш
                 */
                if ($flag_save == 1) {
                    //пишем значение в БД
                    /**
                     * Запись значения тега в БД
                     */
                    $response = SensorBasicController::addSensorParameterValue($sensor_parameter_id, $value, $sensor_parameter_status_id, $tag_datetime);
                    if ($response['status'] == 1) {
                        $sensor_parameter_value_id = $response['sensor_parameter_value_id'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        $warnings[] = "actionSetTagValue. Сохранение значения тега OPC в БД. Ключ добавленного значения $sensor_parameter_value_id";
                    } else {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        throw new \Exception("actionSetTagValue. Сохранение значения тега не удалось");
                    }
                    $status *= 1;
                    /**
                     * запись значение тега в кеш
                     */
                    $response = (new SensorCacheController())->setSensorParameterValueHash($sensor_id, $sensor_parameter_id, $value, $parameter_id, $parameter_type_id, $sensor_parameter_status_id, $tag_datetime);
                    if ($response['status'] == 1) {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        $status *= $response['status'];
                        $warnings[] = "actionSetTagValue. Значение сенсора $sensor_id Состояние $parameter_id записано в кеш";
                    } else {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        throw new \Exception("actionSetTagValue. Значение сенсора $sensor_id Состояние $parameter_id НЕ записано в кеш");
                    }
                }
            } else {
                throw new \Exception('actionSetTagValue. Не передан входной параметр opc_tags_value');
            }
        } catch (\Throwable $ex) {
            $errors[] = "actionSetTagValue. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;

    }

}
