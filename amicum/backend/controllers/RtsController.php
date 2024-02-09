<?php

namespace backend\controllers;

use backend\controllers\cachemanagers\LogCacheController;
use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\cachemanagers\ServiceCache;
use Exception;
use frontend\controllers\system\LogAmicumFront;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;
use yii\web\Response;

class RtsController extends Controller
{

    // actionGetConfig                  - Метод получения конфигурации службы OPC из БД на основе идентификатора службы
    // actionGetListTags                - Метод получения списка тегов для RTS сервера по конкретному сенсор_айди
    // actionSetTagValue                - Метод сохранения значения тегов в БД с RTS службы
    // SetTagValue                      - Метод сохранения значения тегов в БД с RTS службы
    // saveRtsSensor                    - Метод сохранения значения сенсора полученного с RTS службы

    // actionGetConfig - Метод получения конфигурации службы RTS из БД на основе идентификатора службы
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
        $connect_string = [];

        try {
            if (isset($post['rts_title']) and $post['rts_title'] != "") {
                $rts_title = $post['rts_title'];
                $warnings[] = 'actionGetConfig. Передан входной параметр: ' . $rts_title;
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
                        'title' => $rts_title,
                        'source_type' => "RTS"
                    ])
                    ->limit(1)
                    ->one();
                if (!$connect_string) {
                    throw new Exception('actionGetConfig. По переданному названию OPC сервера, привязанного сенсора не существует');
                }

            } else {
                throw new Exception('actionGetConfig. Не передан входной параметр rts_title');
            }
        } catch (Throwable $ex) {
            $errors[] = "actionGetConfig. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array('Items' => $connect_string, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // actionGetListTags - Метод получения списка тегов для RTS сервера по конкретному сенсор_айди
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
    // пример вызова: http://127.0.0.1/admin/rts/get-list-tags?sensor_id=3159854
    public function actionGetListTags()
    {
        $log = new LogAmicumFront("actionGetListTags");

        $sensor_tags_list_cut = array();

        try {
            $log->addLog("Начал выполнять метод");
            $post = Assistant::GetServerMethod();
            if (
                isset($post['sensor_id']) and $post['sensor_id'] != ""
            ) {
                $sensor_id = $post['sensor_id'];
            } else {
                throw new Exception('Не передан входной параметр sensor_id');
            }
            $log->addLog("Передан входной параметр sensor_id: " . $sensor_id);

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
                ->andWhere('parameter_id!=346 and parameter_id!=164')
                ->limit(25000)
                ->all();

            if (!$sensor_tags_list) {
                throw new Exception('по запрашиваемому sensor_id, теги не найдены');
            }

            $tag_ids = [];
            foreach ($sensor_tags_list as $tag) {
                $tag_ids[] = $tag['parameter_id'];
            }

            $sensor_tags_range_list = (new Query())
                ->select([
                    'sensor_id',
                    'sensor_parameter_id',
                    'parameter_id',
                    'parameter_type_id',
                    'date_time',
                    'value',
                    'status_id'
                ])
                ->from('view_initSensorParameterHandbookValue')
                ->where(['parameter_id' => $tag_ids])
                ->andWhere(['sensor_id' => $sensor_id])
                ->indexBy('parameter_id')
                ->all();

            foreach ($sensor_tags_list as $tag) {
                if (strlen($tag['tag_name']) > 4) {
                    if (isset($sensor_tags_range_list[$tag['parameter_id']])) {
                        $tag_range = str_replace('.', ',', $sensor_tags_range_list[$tag['parameter_id']]['value']);
                    } else {
                        $tag_range = 0;
                    }

                    $sig_name = mb_substr($tag['tag_name'], 3);
                    // разбиваем тег на части
                    $tag_params = explode("_", $sig_name);

                    if (count($tag_params) == 6) {
                        $device_id = $tag_params[3];
                        $sig_id = $tag_params[5];
                        $sig_type = $tag_params[0];
                        $sig_type_id = $tag_params[4];
                    } else {
                        $device_id = 0;
                        $sig_id = 0;
                        $sig_type = "";
                        $sig_type_id = 0;
                    }

                    $sensor_tags_list_cut[] = array(
                        'sensor_id' => $tag['sensor_id'],
                        'sensor_tag_name' => $sig_name,
                        'sensor_parameter_id' => $tag['sensor_parameter_id'],
                        'parameter_id' => $tag['parameter_id'],
                        'parameter_type_id' => $tag['parameter_type_id'],
                        'tag_range' => $tag_range,
                        'sig_name' => $sig_name,
                        'sig_type' => $sig_type,
                        'device_id' => $device_id,
                        'sig_type_id' => $sig_type_id,
                        'sig_id' => $sig_id
                    );
                }
            }
            unset($sensor_tags_list);
            unset($sensor_tags_range_list);
            unset($tag_ids);

            /**
             * Проверяем наличие детальных сведений о сенсоре в кеше, если нет, то инициализируем сенсор в кеше со всеми параметрами
             */
            $sensor_cache_controller = new SensorCacheController();
            $sensor_cache = $sensor_cache_controller->getSensorMineBySensorHash($sensor_id);
            if (!$sensor_cache) {
                $log->addLog("Инициализируем параметры сенсора $sensor_id в кеше");

                $response = SensorMainController::initSensorInCache($sensor_id);                                        //получить шахту mine_id для искомого сенсора
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception(" Кеш сенсора $sensor_id не инициализирован");
                }
            } else {
                $log->addLog("Кеш сенсора $sensor_id уже существовал");
            }

            /**
             * инициализируем кеш привязок параметров сенсоров
             */
            $sensor_parameter_sensor = $sensor_cache_controller->multiGetSenParSenTag();
            if (!$sensor_parameter_sensor) {
                $log->addLog("Инициализируем кеш привязок параметров сенсора");

                $response = $sensor_cache_controller->initSensorParameterSensor();                                      //получить шахту mine_id для искомого сенсора
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Кеш сенсора привязок не инициализирован');
                }
            } else {
                $log->addLog("Кеш сенсора привязок уже существовал");
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $sensor_tags_list_cut], $log->getLogAll());
    }

    // actionSetTagValue - Метод сохранения значения тегов в БД с RTS службы
    // входные параметры:
    //      opc_tags_value:   - имя опрашиваемого тега
    //                  sensor_id           - ключ сенсора OPC сервера
    //                  sensor_parameter_id - ключ конкретного значения конкретного тега
    //                  parameter_id        - ключ названия тега
    //                  parameter_type_id   - ключ типа параметра тега 1-справочное/2-измеренное/3-вычисленное
    //                  TagValue            - значение конкретного тега
    //                  Quality             - качество тега (status_id - после трансформации)
    // выходные параметры:
    //  items:      - результат работы метода
    //  status:     - состояние по работе метода (0 - выполнен с ошибками, 1 выполнен полностью и без ошибок)
    //  errors:     - массив ошибок при работе метода
    //  warnings:   - массив предупреждений и отладочной информации при работе метода
    // разработал: Якимов М.Н.
    // дата разработки: 30.05.2019
    // пример вызова: http://127.0.0.1/admin/rts/set-tag-value?opc_tags_value={"sig_name":"TR_SIG_0_20_9_1080","sig_type":"TR","device_id":20,"sig_type_id":9,"sig_id":1080,"TagValue":"-1","Quality":"bad","sensor_id":"3159854","sensor_tag_name":"TR_SIG_0_20_9_1080","sensor_parameter_id":"2550131","parameter_id":"5045","parameter_type_id":"2","sensor_parameter_value":null,"tag_range":1}
    public function actionSetTagValue()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $status = 1;
        $opc_tags_value = [];
        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionRtsSetTagValue");

        try {
            $log->addLog("Начало выполнения метода");
            $post = Assistant::GetServerMethod();

            //$post['opc_tags_value'] = json_decode('{"TagName":"storage.time.reg12","TagValue":"1","TagDate":"2019-06-07 09:00:00.630020","parameter_id":"466","parameter_type_id":"2","Quality":"badWaitingForInitialData","sensor_parameter_id":"584652","sensor_id":"1350"}');
            if (isset($post['opc_tags_value']) && $post['opc_tags_value'] != '') {
                $opc_tags_value = json_decode($post['opc_tags_value']);
                $log->addData($post['opc_tags_value'], '$post[opc_tags_value]', __LINE__);
                $log->addData($opc_tags_value, '$opc_tags_value', __LINE__);
                $log->addLog("Передан входной параметр");

                $mine_id = AMICUM_DEFAULT_MINE;
                if (isset($opc_tags_value->mine_id)) {
                    $mine_id = $opc_tags_value->mine_id;
                }
                //если нет разрешения на запись, то метод не выполняется
                if (!(new ServiceCache())->CheckDcsStatus($mine_id, 'opcMikonStatus')) {
                    $log->addData($mine_id, '$mine_id', __LINE__);
                    throw new Exception("Нет разрешения на запись");
                }
                $response = $this->SetTagValue($opc_tags_value);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка сохранения тегов');
                }
            } else {
                throw new Exception('Не передан входной параметр opc_tags_value');
            }

            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
            LogCacheController::setOpcLogValue('actionRtsSetTagValue', array_merge(['Items' => $result], $log->getLogAll()), 2);
            LogAmicum::LogAmicumStrata("actionRtsSetTagValue", $opc_tags_value, $log->getWarnings(), $log->getErrors());
            $status = 0;
        }
//        LogCacheController::setOpcLogValue('actionRtsSetTagValue', array_merge(['Items' => $result], $log->getLogAll()), 2);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    // SetTagValue - Метод сохранения значения тегов в БД с RTS службы
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
    // пример вызова: $this->SetTagValue($opc_tags_value)
    public function SetTagValue($opc_tags_value)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)

        // Стартовая отладочная информация
        $log = new LogAmicumFront("SetTagValue");

        try {
            $log->addLog("Начало выполнения метода");

            if (isset($result['parameter_title'])) {
                $result['parameter_title'] = $opc_tags_value->TagName;
            } else {
                $log->addLog("не передан выходной параметр tage_name. Не значительно");
            }
            $result['sensor_parameter_value'] = $opc_tags_value->TagValue;
            $result['sensor_parameter_id'] = $opc_tags_value->sensor_parameter_id;
            $result['parameter_id'] = $opc_tags_value->parameter_id;
            $result['parameter_type_id'] = $opc_tags_value->parameter_type_id;
            $result['opc_sensor_id'] = $opc_tags_value->sensor_id;
            $result['Quality'] = $opc_tags_value->Quality;

            //$parameter_title = $opc_tags_value->TagName;                                                              // название тега - он же parameter_title
            $value = (string)str_replace(',', '.', $opc_tags_value->TagValue);                             // значение измеренного тега
            $sensor_parameter_id = $opc_tags_value->sensor_parameter_id;                                                // конкретный ключ измеренного тега
            $tag_datetime = Assistant::GetDateNow();                                                                    // дата и время в которое пришло измеренное значение
            $parameter_id = $opc_tags_value->parameter_id;                                                              // ключ параметра тега
            $parameter_type_id = $opc_tags_value->parameter_type_id;                                                    // тип параметра тега
            $quality = $opc_tags_value->Quality;                                                                        // качество полученного значения
            $opc_sensor_id = $opc_tags_value->sensor_id;                                                                    // ключ OPC

            /**
             * Блок качество полученного значения
             */
            $log->addData(substr($quality, 0, 4), "обрезка", __LINE__);
            if ($quality == 'good' ||
                (strlen($quality) > 4 and substr($quality, 0, 4) == 'good') ||
                $quality == 'uncertainEUExceeded[low]'
            ) {
                $sensor_parameter_status_id = 1;
            } else {
                $sensor_parameter_status_id = 19;
                $tag_datetime = Assistant::GetDateNow();
                $value = -1;
            }

            /**
             * Проверяем наличие детальных сведений об OPC в кеше, если нет, то инициализируем сенсор в кеше со всеми параметрами
             */
            $sensor_cache_controller = (new SensorCacheController());
            $opc_sensor_cache = $sensor_cache_controller->getSensorMineBySensorHash($opc_sensor_id);
            if (!$opc_sensor_cache) {
                $log->addLog("Инициализируем параметры OPC сенсора $opc_sensor_id в кеше");
                $response = SensorMainController::initSensorInCache($opc_sensor_id);                                        //получить шахту mine_id для искомого сенсора
                $log->addLogAll($response);
                if ($response['status'] === 0) {
                    throw new Exception("Кеш OPC сенсора $opc_sensor_id не инициализирован");
                }
                $log->addLog("Кеш OPC сенсора $opc_sensor_id инициализирован");
            } else {
                $log->addLog("Кеш OPC сенсора $opc_sensor_id уже существовал");
            }

            $log->addLog("Проверили наличие детальных сведений об OPC сенсоре в кеше");

            /**
             * блок проверки необходимости записи значения параметра в БД и кеш
             */
            $response = SensorMainController::IsChangeSensorParameterValue($opc_sensor_id, $parameter_id, $parameter_type_id, $value, $tag_datetime);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception("Ошибка обработки флага сохранения значения в БД и кеш для сенсора $opc_sensor_id");
            }

            $flag_save = $response['flag_save'];

            $log->addLog("Флаг сохранения в БД получен и равен: $flag_save");
            $log->addLog("Кеш OPC сенсора $opc_sensor_id инициализирован");
            $log->addLog("блок проверки необходимости записи значения параметра в БД и кеш");

            /**
             * блок записи значения параметра в БД и в кеш
             */
            if ($flag_save == 1) {
                //пишем значение в БД
                /**
                 * Запись значения тега в БД
                 */
                $response = SensorBasicController::addSensorParameterValue($sensor_parameter_id, $value, $sensor_parameter_status_id, $tag_datetime);
                $log->addLogAll($response);
                if ($response['status'] === 0) {
                    throw new Exception("Сохранение значения тега не удалось");
                }

                $sensor_parameter_value_id = $response['sensor_parameter_value_id'];

                $log->addLog("Сохранение значения тега OPC в БД. Ключ добавленного значения $sensor_parameter_value_id");
                $log->addLog("Запись значения тега в БД");

                /**
                 * запись значение тега в кеш
                 */
                $response = $sensor_cache_controller->setSensorParameterValueHash($opc_sensor_id, $sensor_parameter_id, $value, $parameter_id, $parameter_type_id, $sensor_parameter_status_id, $tag_datetime);
                $log->addLogAll($response);
                if ($response['status'] === 0) {
                    throw new Exception("Значение сенсора $opc_sensor_id параметр $parameter_id НЕ записано в кеш");
                }

                $log->addLog("Запись значения тега в кеш");

                /**
                 * Ищем сенсоры, к которым привязан тег в кеше
                 */
                $sensor_parameter_sensors = $sensor_cache_controller->multiGetSenParSenTag($sensor_parameter_id, '*');

                $log->addLog("выгреб привязку тегов из кеша");

                if ($sensor_parameter_sensors) {
                    $log->addLog("Нашел привязки к данному сенсору в кеше");
                    $log->addData($sensor_parameter_sensors, '$sensor_parameter_sensors', __LINE__);
                    /**
                     * записываем значение тега в сенсор, к которому приязан этот тег
                     */
                    foreach ($sensor_parameter_sensors as $sensor_parameter_sensor) {
                        $response = self::saveRtsSensor($sensor_parameter_sensor, $sensor_cache_controller, $value, $sensor_parameter_status_id, $tag_datetime);
                        $log->addLogAll($response);
                        if ($response['status'] === 0) {
//                            throw new Exception("Не смог сохранить сенсор");
                            $log->addLog("Не смог сохранить сенсор");
                        }
                    }
                } else {
                    $log->addLog("Не нашел привязку к данному параметру тега к сенсору в кеше");
                }
            }
            /** Метод окончание */


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
            LogCacheController::setOpcLogValue('actionSetTagValue', array_merge(['Items' => $result], $log->getLogAll()), '2');
        }

        if (isset($sensor_parameter_status_id) and $sensor_parameter_status_id == 19) {
            LogCacheController::setOpcLogValue('actionSetTagValue', array_merge(['Items' => $result], $log->getLogAll()), '2');
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    // saveRtsSensor - Метод сохранения значения сенсора полученного с RTS службы
    // входные параметры:
    //      opc_tags_value:   - имя опрашиваемого тега
    //                  $sensor_parameter_sensor        - привязка тега OPC к реальному сенсору
    //                      sensor_id                       - ключ сенсора, к которому привязан тег
    //                      sensor_parameter_id             - ключ конкретного параметра сенсора, к которому привязан тег
    //                      parameter_id                    - параметр сенсора
    //                  $sensor_cache_controller        - кеш
    //                  $value                          - значение тега
    //                  $sensor_parameter_status_id     - статус тега
    //                  $tag_datetime                   - время измерения тега
    // выходные параметры:
    //  items:      - результат работы метода
    //  status:     - состояние по работе метода (0 - выполнен с ошибками, 1 выполнен полностью и без ошибок)
    //  errors:     - массив ошибок при работе метода
    //  warnings:   - массив предупреждений и отладочной информации при работе метода
    // разработал: Якимов М.Н.
    // дата разработки: 30.05.2019
    // пример вызова: $this->SetTagValue($opc_tags_value)
    public static function saveRtsSensor($sensor_parameter_sensor, $sensor_cache_controller, $value, $sensor_parameter_status_id, $tag_datetime)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)

        // Стартовая отладочная информация
        $log = new LogAmicumFront("saveRtsSensor");

        try {
            $log->addLog("Начало выполнения метода");

            /**
             * сперва получаем по сенсору ключ двух параметеров X и 164
             * начинаем для X
             */
            $sensor_id = $sensor_parameter_sensor['sensor_id'];
            $sensor_parameter_id = $sensor_parameter_sensor['sensor_parameter_id'];
            $parameter_id_target_sensor = $sensor_parameter_sensor['parameter_id'];                                     // справочный параметр сенсора в который мы пишем значение
            /**
             * проверяем налицие кеша у сенсора, если что его инициализируем
             */
            $sensor_cache = $sensor_cache_controller->getSensorMineBySensorHash($sensor_id);
            if (!$sensor_cache) {

                $log->addLog("Инициализируем параметры сенсора $sensor_id в кеше");

                $response = SensorMainController::initSensorInCache($sensor_id);                                        //получить шахту mine_id для искомого сенсора
                $log->addLogAll($response);
                if ($response['status'] === 0) {
                    throw new Exception("Кеш сенсора $sensor_id не инициализирован");
                }
            } else {
                $log->addLog("Кеш сенсора $sensor_id уже существовал");
            }

            /**
             * Запись значения параметра сенсора X в БД
             */
            $response = SensorBasicController::addSensorParameterValue($sensor_parameter_id, $value, $sensor_parameter_status_id, $tag_datetime);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception("Сохранение значения параметра X не удалось");
            }

            /**
             * Запись значение тега в кеш
             */
            $response = $sensor_cache_controller->setSensorParameterValueHash($sensor_id, $sensor_parameter_id, $value, $parameter_id_target_sensor, 3, $sensor_parameter_status_id, $tag_datetime);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception("Значение сенсора $sensor_id Состояние $sensor_parameter_id НЕ записано в кеш");
            }


            $log->addLog("Сенсор: " . $sensor_id);
            $log->addLog("Значение: " . $value);


            /**
             * Записываем состояние привязанного сенсора 164
             */
            $response = SensorMainController::GetOrSetSensorParameter($sensor_id, 164, 3);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception("Не смог получить из кеша, а так же создать в базе данных для парамтера 164 sensor_parameter_id");
            }

            $sensor_parameter_id = $response['sensor_parameter_id'];

            /**
             * определяем состояние привзанного параметра на основе значения статуса тега.
             * Если состояние 1 то и статус у сенсора 1.
             * Если статус тега равен 19, то значение состяоние равно 0.
             */
            if ($sensor_parameter_status_id == 1) {
                $value = 1;
            } else {
                $value = 0;
            }
            /**
             * Запись значения параметра сенсора 164 в БД
             */
            $response = SensorBasicController::addSensorParameterValue($sensor_parameter_id, $value, $sensor_parameter_status_id, $tag_datetime);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception("Сохранение значения параметра 164 не удалось");
            }

            /**
             * запись значение тега в кеш
             */

            $response = $sensor_cache_controller->setSensorParameterValueHash($sensor_id, $sensor_parameter_id, $value, 164, 3, $sensor_parameter_status_id, $tag_datetime);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception("Значение сенсора $sensor_id Состояние 164 НЕ записано в кеш");
            }

            $log->addLog("Уложил одно значение и в кеш и в БД и сделал событие");


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
            LogCacheController::setOpcLogValue('actionSetTagValue', array_merge(['Items' => $result], $log->getLogAll()), '2');
        }

        $log->addLog("Окончание выполнения метода");

        //LogCacheController::setOpcLogValue('actionSetTagValue', array_merge(['Items' => $result], $log->getLogAll()));

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}
