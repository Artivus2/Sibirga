<?php

namespace backend\controllers;

use backend\controllers\cachemanagers\EdgeCacheController;
use backend\controllers\cachemanagers\EquipmentCacheController;
use backend\controllers\cachemanagers\GasCacheController;
use backend\controllers\cachemanagers\LogCacheController;
use backend\controllers\cachemanagers\OpcCacheController;
use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\cachemanagers\ServiceCache;
use backend\controllers\const_amicum\EventEnumController;
use backend\controllers\const_amicum\StatusEnumController;
use Exception;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Parameter;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;
use yii\web\Response;

class OpcController extends Controller
{

    // actionGetListTags                - Метод получения списка тегов для OPC сервера по конкретному сенсор_айди
    // actionGetConfig                  - Метод получения конфигурации службы OPC из БД на основе идентификатора службы
    // actionSetTagValue                - Метод сохранения значения тегов в БД с OPC службы
    // actionSetEquipmentValue          - Метод сохранения значения тегов в БД с OPC АСУТП службы
    // actionBuildGraph                 - Метод построения графа выработок для стационарных датчиков газа
    // findAllConjunction20M            - метод поиска всех эджей от текущего конжакшена для определения зоны влияния датчика CH4
    // calcDistance                     - метод расчета расстояния между двумя точками
    // actionCalcGasValueStaticMovement - метод вычисления попадания светильника индивидуального в зону действия
    // actionSetTagInstall              - метод записи значения сенсора в кеш
    // actionGetTagInstall              - метод для получения список тэгов на установку на OPC сервер
    // actionSetEquipmentValue()        - метод для записи значения оборудования по аналогии actionSetTagValue
    // actionCutTagsName                - Метод добавления в наименование тэга префикс с помощью которого можно разлечить к какой шахте относиться тэг
    // actionMsetTagsValue              - метод по получении значения тэгов за раз
    // saveOpcSensor                    - Метод сохранения значения сенсора полученного с OPC службы

    // actionGetListTags - Метод получения списка тегов для OPC сервера по конкретному сенсор_айди
    // входные параметры:
    //  sensor_id - ключ OPC сервера - фактически это ключ сенсора
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
    // пример вызова: http://127.0.0.1/admin/opc/get-list-tags?sensor_id=1350&mine_id=290
    public function actionGetListTags()
    {
        $post = Assistant::GetServerMethod();
        $status = 1;
        $errors = array();
        $warnings = array();
        $sensor_tags_list_cut = array();

        try {
            if (
                isset($post['sensor_id']) and $post['sensor_id'] != "" and
                isset($post['mine_id']) and $post['mine_id'] != ""
            ) {
                $sensor_id = $post['sensor_id'];
                $mine_id = $post['mine_id'];
            } else {
                throw new Exception('actionGetListTegs. Не передан входной параметр sensor_id или mine_id');
            }
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
                ->andWhere('parameter_id!=346 and parameter_id!=164')
                ->limit(25000)
                ->all();
            if ($sensor_tags_list) {

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
                        $sensor_tags_list_cut[] = array(
                            'sensor_id' => $tag['sensor_id'],
                            'sensor_tag_name' => mb_substr($tag['tag_name'], 3),
                            'sensor_parameter_id' => $tag['sensor_parameter_id'],
                            'parameter_id' => $tag['parameter_id'],
                            'tag_range' => $tag_range,
                            'parameter_type_id' => $tag['parameter_type_id']
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
                    $warnings[] = "actionGetListTags. Инициализируем параметры сенсора $sensor_id в кеше";
                    $response = SensorMainController::initSensorInCache($sensor_id);                                    //получить шахту mine_id для искомого сенсора
                    if ($response['status'] == 1) {
                        //$result[] = $response['Items'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        $warnings[] = "actionGetListTags. Кеш сенсора $sensor_id инициализирован";
                    } else {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        throw new Exception("actionGetListTags. Кеш сенсора $sensor_id не инициализирован");
                    }
                } else {
                    $warnings[] = "actionGetListTags. Кеш сенсора $sensor_id уже существовал";
                }
                /**
                 * инициализируем кеш привязок параметров сенсоров
                 */
                $sensor_parameter_sensor = $sensor_cache_controller->multiGetSenParSenTag();
                if (!$sensor_parameter_sensor) {
                    $warnings[] = 'actionGetListTags. Инициализируем кеш привязок параметров сенсора';
                    $response = $sensor_cache_controller->initSensorParameterSensor();                                    //получить шахту mine_id для искомого сенсора
                    if ($response['status'] == 1) {
//                        $result[] = $response['Items'];
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        $warnings[] = 'actionGetListTags. Кеш сенсора привязок инициализирован';
                    } else {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        throw new Exception('actionGetListTags. Кеш сенсора привязок не инициализирован');
                    }
                } else {
                    $warnings[] = 'actionGetListTags. Кеш сенсора привязок уже существовал';
                }
                /**
                 * инициализируем кеш для расчета зон сенсоров
                 */
                $response = $this->actionBuildGraph($mine_id);
                if ($response['status'] == 1) {
//                    $result[] = $response['Items'];
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $warnings[] = 'actionGetListTags. Граф зон построен и инициализирован в кеше';
                } else {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new Exception('actionGetListTags. Ошибка инициализации графа зон выработок и его кеша');
                }

            } else {
                throw new Exception('actionGetListTegs. по запрашиваемому sensor_id, теги не найдены');
            }

        } catch (Throwable $ex) {
            $errors[] = 'actionGetListTegs. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
//        unset($errors);
//        unset($warnings);
//        $warnings = array();
//        $errors = array();
        $result_main = array('Items' => $sensor_tags_list_cut, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
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
        $connect_string = [];

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
                    throw new Exception('actionGetConfig. По переданному названию OPC сервера, привязанного сенсора не существует');
                }

            } else {
                throw new Exception('actionGetConfig. Не передан входной параметр opc_title');
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
    // пример вызова: http://127.0.0.1:98/admin/opc/set-tag-value?opc_tags_value={%22TagName%22:%22storage.time.reg12%22,%22TagValue%22:%221%22,%22TagDate%22:%222019-06-07%2009:00:00.630020%22,%22TimeStamp%22:%220001-01-01T00:00:00%22,%22parameter_id%22:%22466%22,%22parameter_type_id%22:%222%22,%22Quality%22:%22badWaitingForInitialData%22,%22sensor_parameter_id%22:%22584652%22,%22sensor_id%22:%221350%22}
    // пример вызова: http://10.8.54.50/admin/opc/set-tag-value?opc_tags_value={%22TagName%22:%22storage.time.reg12%22,%22TagValue%22:%221%22,%22TagDate%22:%222021-05-05%2015:50:00.630020%22,%22TimeStamp%22:%220001-01-01T00:00:00%22,%22parameter_id%22:%225251%22,%22parameter_type_id%22:%222%22,%22Quality%22:%22good%22,%22sensor_parameter_id%22:%222440807%22,%22sensor_id%22:%222%22,%22mine_id%22:2}
    // http://10.8.54.50/admin/opc/set-tag-value?opc_tags_value={%22TagName%22:%22storage.time.reg12%22,%22TagValue%22:%224%22,%22TagDate%22:%222021-09-30%2011:33:00.630020%22,%22TimeStamp%22:%220001-01-01T00:00:00%22,%22parameter_id%22:%225245%22,%22parameter_type_id%22:%222%22,%22Quality%22:%22good%22,%22sensor_parameter_id%22:%222440801%22,%22sensor_id%22:%222%22,%22mine_id%22:2}
    public function actionSetTagValue()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $status = 1;
        $opc_tags_value = [];
        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionSetTagValue");

        try {
            $log->addLog("Начало выполнения метода");
            $post = Assistant::GetServerMethod();

            //$post['opc_tags_value'] = json_decode('{"TagName":"storage.time.reg12","TagValue":"1","TagDate":"2019-06-07 09:00:00.630020","TimeStamp":"0001-01-01T00:00:00","parameter_id":"466","parameter_type_id":"2","Quality":"badWaitingForInitialData","sensor_parameter_id":"584652","sensor_id":"1350"}');
            if (isset($post['opc_tags_value']) && $post['opc_tags_value'] != '') {
                $opc_tags_value = json_decode($post['opc_tags_value']);
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
            LogCacheController::setOpcLogValue('actionSetTagValue', array_merge(['Items' => $result], $log->getLogAll()), 2);
            LogAmicum::LogAmicumStrata("actionSetTagValue", $opc_tags_value, $log->getWarnings(), $log->getErrors());
            $status = 0;
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array('Items' => [], 'status' => $status, 'errors' => [], 'warnings' => []);
//        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    // SetTagValue - Метод сохранения значения тегов в БД с OPC службы
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
                $log->addLog("не передан выходной параметер tage_name. Не значительно");
            }
            $result['sensor_parameter_value'] = $opc_tags_value->TagValue;
            $result['sensor_parameter_id'] = $opc_tags_value->sensor_parameter_id;
            $result['parameter_id'] = $opc_tags_value->parameter_id;
            $result['parameter_type_id'] = $opc_tags_value->parameter_type_id;
            $result['sensor_parameter_date_time'] = $opc_tags_value->TimeStamp;
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
             * Блок проверки необходимости записи значения параметра в БД и кеш
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
                     * Записываем значение тега в сенсор, к которому привязан этот тег
                     */
                    foreach ($sensor_parameter_sensors as $sensor_parameter_sensor) {
                        $response = self::saveOpcSensor($sensor_parameter_sensor, $sensor_cache_controller, $value, $sensor_parameter_status_id, $tag_datetime);
                        $log->addLogAll($response);
                        if ($response['status'] != 1) {
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

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    // saveOpcSensor - Метод сохранения значения сенсора полученного с OPC службы
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
    public static function saveOpcSensor($sensor_parameter_sensor, $sensor_cache_controller, $value, $sensor_parameter_status_id, $tag_datetime)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)

        // Стартовая отладочная информация
        $log = new LogAmicumFront("saveOpcSensor");

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
             * запись значение тега в кеш
             */
            $response = $sensor_cache_controller->setSensorParameterValueHash($sensor_id, $sensor_parameter_id, $value, $parameter_id_target_sensor, 3, $sensor_parameter_status_id, $tag_datetime);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception("Значение сенсора $sensor_id Состояние $sensor_parameter_id НЕ записано в кеш");
            }

            /**
             * Получение аварийной уставки для датчика и генерация события,
             * если есть превышение
             */
            $sensor_parameter_value_list_cache = $sensor_cache_controller->multiGetParameterValueHash($sensor_id, '*', '*');
            $sensor_parameter_value_cache_array = [];
            if ($sensor_parameter_value_list_cache !== false) {
                foreach ($sensor_parameter_value_list_cache as $sensor_parameter_value_cache) {
                    $sensor_parameter_value_cache_array[$sensor_parameter_value_cache['sensor_id']][$sensor_parameter_value_cache['parameter_type_id']][$sensor_parameter_value_cache['parameter_id']] = $sensor_parameter_value_cache;
                }
            }
            if (isset($sensor_parameter_value_cache_array[$sensor_id][1][346])) {
                $mine_id = $sensor_parameter_value_cache_array[$sensor_id][1][346]['value'];
            } else {
                $mine_id = false;
            }
            if (isset($sensor_parameter_value_cache_array[$sensor_id][1][269])) {
                $edge_id = $sensor_parameter_value_cache_array[$sensor_id][1][269]['value'];
            } else {
                $edge_id = false;
            }

            $log->addLog("Сенсор: " . $sensor_id);
            $log->addLog("Значение: " . $value);

            if (
                isset($sensor_parameter_value_cache_array[$sensor_id][1][274]) and
                $sensor_parameter_value_cache_array[$sensor_id][1][274]['value'] == 28 and
                isset($sensor_parameter_value_cache_array[$sensor_id][1][263])
            ) {
                $gas_limit = $sensor_parameter_value_cache_array[$sensor_id][1][263]['value'];                          // Концентрация метана (CH) уставка аварийная
                $event_id = EventEnumController::CH4_EXCESS_STAC;                                                       // Превышение концентрации газа CH4
                $log->addLog("Датчик CH4 - уставка: " . $gas_limit);

            } else if (
                isset($sensor_parameter_value_cache_array[$sensor_id][1][274]) and
                $sensor_parameter_value_cache_array[$sensor_id][1][274]['value'] == 27 and
                isset($sensor_parameter_value_cache_array[$sensor_id][1][264])
            ) {
                $gas_limit = $sensor_parameter_value_cache_array[$sensor_id][1][264]['value'];                          // Удельная доля угарного газа (CO) уставка аварийная
                $event_id = EventEnumController::CO_EXCESS_STAC;                                                        // Превышение концентрации газа CO
                $log->addLog("Датчик СО - уставка: " . $gas_limit);

            } else if (
                isset($sensor_parameter_value_cache_array[$sensor_id][1][274]) and
                $sensor_parameter_value_cache_array[$sensor_id][1][274]['value'] == 271 and
                isset($sensor_parameter_value_cache_array[$sensor_id][1][23])
            ) {
                $gas_limit = $sensor_parameter_value_cache_array[$sensor_id][1][23]['value'];                           // Удельная масса пыли уставка аварийная
                $event_id = EventEnumController::DUST_EXCESS_STAC;                                                      // Превышение удельной массы пыли
                $log->addLog("Датчик ПЫЛИ - уставка: " . $gas_limit);

            } else {
                $gas_limit = false;
                $event_id = null;
                $log->addLog("Уставки нет");
            }

            if (isset($sensor_parameter_value_cache_array[$sensor_id][1][83])) {
                $xyz = $sensor_parameter_value_cache_array[$sensor_id][1][83]['value'];
            } else {
                $xyz = false;
            }

            if ($gas_limit !== false && $mine_id !== false && $edge_id !== false && $xyz !== false) {
                $log->addLog("Начинаю проверку на необходимость генерации события");
                if ($value > $gas_limit) {
                    $value_to_record = StatusEnumController::EMERGENCY_VALUE;
                    $status_to_record = StatusEnumController::EVENT_RECEIVED;
                } else {
                    $value_to_record = StatusEnumController::NORMAL_VALUE;
                    $status_to_record = StatusEnumController::EVENT_ELIMINATED_BY_SYSTEM;
                }

                if ($gas_limit != false and $value != -1 and ($value != "5" or $event_id == EventEnumController::CO_EXCESS_STAC)) {
                    $log->addLog("Запускаем проверку на необходимость генерации события. Значение != 5 или CO");
                    $response = EventMainController::createEventForWorkerGas('sensor', $sensor_id, $event_id, $value, $tag_datetime, $value_to_record, $parameter_id_target_sensor, $mine_id, $status_to_record, $edge_id, $xyz);
                    $log->addLogAll($response);
                } else {
                    $log->addLog("Значение == 5");
                    if (isset($sensor_parameter_value_cache_array[$sensor_id][1][1008]['value']) and $sensor_parameter_value_cache_array[$sensor_id][1][1008]['value'] == "5") {
                        $log->addLog("Событие норм, в журнал не пишем");
                    }
                }
            } else {
                $log->addLog("У сенсора нет параметра(-ов) в кэше: 346, 269 или 263 или 83");
            }

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
             * Запись значение тега в кеш
             */

            $response = $sensor_cache_controller->setSensorParameterValueHash($sensor_id, $sensor_parameter_id, $value, 164, 3, $sensor_parameter_status_id, $tag_datetime);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception("Значение сенсора $sensor_id Состояние 164 НЕ записано в кеш");
            }

            $log->addLog("Уложил одно значение и в кеш и в БД и сделал событие");

            if ($sensor_parameter_status_id == 19) {
                $value_to_record = StatusEnumController::EMERGENCY_VALUE;                                               // Аварийное значение
                $status_to_record = StatusEnumController::EVENT_RECEIVED;                                               // Событие получено
            } else {
                $value_to_record = StatusEnumController::NORMAL_VALUE;                                                  // Нормальное значение
                $status_to_record = StatusEnumController::EVENT_ELIMINATED_BY_SYSTEM;                                   // Событие снято системой
            }

            $event_id = EventEnumController::CH4_CRUSH_STAC;                                                            // Отказ стационарного датчика

            $log->addLog("Запускаем проверку на необходимость генерации события Отказа датчика");

            if ($mine_id) {
                $response = EventMainController::createEventForWorkerGas(
                    'sensor',
                    $sensor_id,
                    $event_id,
                    $value,
                    $tag_datetime,
                    $value_to_record,
                    $parameter_id_target_sensor,
                    $mine_id,
                    $status_to_record,
                    $edge_id,
                    $xyz);
                $log->addLogAll($response);
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
            LogCacheController::setOpcLogValue('actionSetTagValue', array_merge(['Items' => $result], $log->getLogAll()), '2');
        }

        $log->addLog("Окончание выполнения метода");

        //LogCacheController::setOpcLogValue('actionSetTagValue', array_merge(['Items' => $result], $log->getLogAll()));

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    // actionBuildGraph - Метод построения графа выработок для стационарных датчиков газа
    // входные параметры:
    //      mine_id     - ключ шахты для которой строим граф выработок
    // выходные параметры:
    //  items:      - результат работы метода
    //  status:     - состояние по работе метода (0 - выполнен с ошибками, 1 выполнен полностью и без ошибок)
    //  errors:     - массив ошибок при работе метода
    //  warnings:   - массив предупреждений и отладочной информации при работе метода
    // разработал: Якимов М.Н.
    // дата разработки: 17.06.2019
    // пример вызова: http://127.0.0.1/admin/opc/build-graph?mine_id=290
    public function actionBuildGraph($mine_id = '')
    {

        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        $shema_mine_conj_repac = array();
        $warnings[] = 'actionBuildGraph. Начал выполнять метод';
        try {

            /**
             * блок проверки входных параметров
             */
            if ($mine_id == '') {
                $post = Assistant::GetServerMethod();
                if (isset($post['mine_id']) && $post['mine_id'] != '') {
                    $mine_id = $post['mine_id'];
                    $warnings[] = "actionBuildGraph. Передан не пустой входной параметр mine_id $mine_id";
                } else {
                    throw new Exception('actionBuildGraph. Не передан входной параметр mine_id');
                }
            }
            /**
             * получаем граф выработок шахты из кеша, если его там нет, то строим с нуля
             */
            $shema_mine_edges = (new EdgeCacheController())->multiGetEdgeScheme($mine_id);
            if ($shema_mine_edges === false) {
                $shema_mine_edges = (new EdgeCacheController())->initEdgeScheme($mine_id);
                if ($shema_mine_edges === false) {
                    throw new Exception('actionBuildGraph. Не удалось получить схему шахты ни из БД ни из кеша mine_id ' . $mine_id);
                }
                $warnings[] = 'actionBuildGraph. Получил схему шахты из БД';
            } else {
                $warnings[] = 'actionBuildGraph. Получил схему шахты из кеша';
            }
            /**
             * перепаковываем схему на нужные парамтеры, лишние параметры удаляем
             */
            foreach ($shema_mine_edges as $shema_mine_edge) {
                unset($shema_mine_edge['place_title']);
                unset($shema_mine_edge['place_object_id']);
                unset($shema_mine_edge['danger_zona']);
                unset($shema_mine_edge['color_edge']);
                unset($shema_mine_edge['color_edge_rus']);
                unset($shema_mine_edge['conveyor']);
                unset($shema_mine_edge['conveyor_tag']);
                unset($shema_mine_edge['value_ch']);
                unset($shema_mine_edge['value_co']);
                unset($shema_mine_edge['date_time']);
                unset($shema_mine_edge['mine_id']);
                //длина ребра в метрах
                $shema_mine_edge['edge_lenght'] = self::calcDistance(
                    $shema_mine_edge['xStart'], $shema_mine_edge['yStart'], $shema_mine_edge['zStart'],
                    $shema_mine_edge['xEnd'], $shema_mine_edge['yEnd'], $shema_mine_edge['zEnd']
                );
                $shema_mine_edge_repac[$shema_mine_edge['edge_id']] = $shema_mine_edge;
                /**
                 * перепаковываем сопряжения в новый массив - получаем связи каждого сопряжения и делаем справочник сопряжений
                 */
                $shema_mine_conj_repac[$shema_mine_edge['conjunction_start_id']][$shema_mine_edge['conjunction_end_id']]['conjunction_id'] = $shema_mine_edge['conjunction_end_id'];
                $shema_mine_conj_repac[$shema_mine_edge['conjunction_start_id']][$shema_mine_edge['conjunction_end_id']]['edge_id'] = $shema_mine_edge['edge_id'];
                $conjunction_id = $shema_mine_edge['conjunction_start_id'];
                $conjunction_spr[$conjunction_id]['conjunction_id'] = $conjunction_id;
                $conjunction_spr[$conjunction_id]['x'] = $shema_mine_edge['xStart'];
                $conjunction_spr[$conjunction_id]['y'] = $shema_mine_edge['yStart'];
                $conjunction_spr[$conjunction_id]['z'] = $shema_mine_edge['zStart'];

                $shema_mine_conj_repac[$shema_mine_edge['conjunction_end_id']][$shema_mine_edge['conjunction_start_id']]['conjunction_id'] = $shema_mine_edge['conjunction_start_id'];
                $shema_mine_conj_repac[$shema_mine_edge['conjunction_end_id']][$shema_mine_edge['conjunction_start_id']]['edge_id'] = $shema_mine_edge['edge_id'];
                $conjunction_id = $shema_mine_edge['conjunction_end_id'];
                $conjunction_spr[$conjunction_id]['conjunction_id'] = $conjunction_id;
                $conjunction_spr[$conjunction_id]['x'] = $shema_mine_edge['xEnd'];
                $conjunction_spr[$conjunction_id]['y'] = $shema_mine_edge['yEnd'];
                $conjunction_spr[$conjunction_id]['z'] = $shema_mine_edge['zEnd'];
            }
            unset($shema_mine_edges);
            unset($shema_mine_edge);
            $gas_cache_controller = (new GasCacheController());
//            /**
//             * Укладываем связь сопряжений с указанием ветви в кеш
//             */
//            $response = $gas_cache_controller->setShemaConjuctionEdge($mine_id, $shema_mine_conj_repac);
//            if ($response) {
//                $warnings[] = 'actionBuildGraph. успешно уложил справочник сопряжений с координатами в кеш ConjuctionSpr';
//            } else {
//                throw new \Exception('actionBuildGraph. Ошибка укладывания справочника сопряжений с координатами в кеш ConjuctionSpr');
//            }
//            /**
//             * Укладываем справочник сопряжений в кеш
//             */
//            $response = $gas_cache_controller->setConjuctionSprMine($mine_id, $conjunction_spr);
//            if ($response) {
//                $warnings[] = 'actionBuildGraph. успешно уложил справочник сопряжений с координатами в кеш ConjuctionSpr';
//            } else {
//                throw new \Exception('actionBuildGraph. Ошибка укладывания справочника сопряжений с координатами в кеш ConjuctionSpr');
//            }

            /**
             * Структура масссива по каждому edge
             *      edge_id                    "23252"
             * place_id                "6372"
             * conjunction_start_id    "3740"
             * conjunction_end_id        "3669"
             * xStart                    "13291.3"
             * yStart                    "-594.4"
             * zStart                    "-10284.2"
             * xEnd                    "13278.5"
             * yEnd                    "-595.4"
             * zEnd                    "-10283.4"
             * edge_lenght                12.863902984708146
             */

            /**
             * получаем список сенсоров из кеша и ищем все стационарные датчики CH4
             */
            $sensor_cache_controller = new SensorCacheController();
            $sensor_mines = $sensor_cache_controller->getSensorMineHash($mine_id);
            if ($sensor_mines === false) {
                throw new Exception('actionBuildGraph. Не удалось получить сенсоры из кеша mine_id ' . $mine_id);
            } else {
                $warnings[] = 'actionBuildGraph. Получил сенсоры шахты из кеша';
            }
            /**
             * ищем только стационарные датчики метана типовой объект=28
             */
            foreach ($sensor_mines as $sensor_mine) {
                if ($sensor_mine['object_id'] == 28) {
                    $sensor_id = $sensor_mine['sensor_id'];
                    $sensor_XYZ = $sensor_cache_controller->getParameterValueHash($sensor_id, 83, 1);
                    $sensor_edge = $sensor_cache_controller->getParameterValueHash($sensor_id, 269, 1);
                    if (
                        $sensor_XYZ and
                        $sensor_XYZ['value'] != "" and
                        $sensor_XYZ['value'] and
                        $sensor_XYZ['value'] != 'empty' and
                        $sensor_XYZ['value'] != 'Empty' and
                        $sensor_edge and
                        $sensor_edge['value'] != "" and
                        $sensor_edge['value'] and
                        $sensor_edge['value'] != 'empty' and
                        $sensor_edge['value'] != 'Empty'
                    ) {
                        $sensor_CH4[$sensor_id]['sensor_id'] = $sensor_id;
                        $sensor_CH4[$sensor_id]['sensor_title'] = $sensor_mine['sensor_title'];
                        $sensor_CH4[$sensor_id]['sensor_XYZ'] = $sensor_XYZ['value'];
                        $coordinates = explode(",", $sensor_XYZ['value']);
                        $sensor_CH4[$sensor_id]['x'] = $coordinates[0];
                        $sensor_CH4[$sensor_id]['y'] = $coordinates[1];
                        $sensor_CH4[$sensor_id]['z'] = $coordinates[2];
                        $sensor_CH4[$sensor_id]['sensor_edge_id'] = $sensor_edge['value'];
                    }
                }
            }
            unset($sensor_mines);

            /**
             * находим в графе edge_id на который ставим сенсор и находим связанные conjunction_id
             * перепаковываем в другой вид
             */
            $graph_without_sensor_edge = array();
            if (isset($sensor_CH4)) {
                foreach ($sensor_CH4 as $sensor_item) {
                    if (isset($shema_mine_edge_repac[$sensor_item['sensor_edge_id']])) {
                        $graph_for_search[$sensor_item['sensor_id']]['edge_id'] = $shema_mine_edge_repac[$sensor_item['sensor_edge_id']]['edge_id'];
                        $graph_for_search[$sensor_item['sensor_id']]['sensor_id'] = $sensor_item['sensor_id'];
                        $graph_for_search[$sensor_item['sensor_id']]['conjunction_id'][] = $shema_mine_edge_repac[$sensor_item['sensor_edge_id']]['conjunction_start_id'];
                        $graph_for_search[$sensor_item['sensor_id']]['conjunction_id'][] = $shema_mine_edge_repac[$sensor_item['sensor_edge_id']]['conjunction_end_id'];
//                /**
//                 * Удаляем ребро на котором стоит сенсор из графа поиска путей
//                 */
//                $conjunction_id_start = $shema_mine_edge_repac[$sensor_item['sensor_edge_id']]['conjunction_start_id'];
//                $conjunction_id_end = $shema_mine_edge_repac[$sensor_item['sensor_edge_id']]['conjunction_end_id'];
//                $warnings[] = "actionBuildGraph. Удаляем путь. Сопряжение начала $conjunction_id_start";
//                $warnings[] = "actionBuildGraph. Удаляем путь. Сопряжение конца $conjunction_id_end";
//                if (isset($shema_mine_conj_repac[$conjunction_id_start][$conjunction_id_end])) {
//                    $graph_without_sensor_edge[] = $shema_mine_conj_repac[$conjunction_id_start][$conjunction_id_end];
//                }
//                if (isset($shema_mine_conj_repac[$conjunction_id_end][$conjunction_id_start])) {
//                    $graph_without_sensor_edge[] = $shema_mine_conj_repac[$conjunction_id_end][$conjunction_id_start];
//                }
//                unset(
//                    $shema_mine_conj_repac[$conjunction_id_start][$conjunction_id_end],
//                    $shema_mine_conj_repac[$conjunction_id_end][$conjunction_id_start]
//                );
                    } else {
                        $errors[] = "actionBuildGraph. Сенсор" . $sensor_item['sensor_id'] . ' ' . $sensor_item['sensor_title'] . " стоит на несуществющей выработке:" . $sensor_item['sensor_edge_id'];
                    }
                }
                /**
                 * блок проверки создания графов с сенсорами на дальность не больше 20 метров от стационарного датчика
                 * находим все все сопряжения, которые не дальше 20 метров от стационара
                 * фактически определяем возможные последовательные пути - цель получить зоны для поиска сенсора по эджу при получении
                 * пакета локации индивидуального датчика
                 */
                foreach ($graph_for_search as $graph_sensor_paths) {
                    foreach ($graph_sensor_paths['conjunction_id'] as $graph_path) {
                        $sensor_id = $graph_sensor_paths['sensor_id'];
                        $edge_id = $graph_sensor_paths['edge_id'];
                        $xS = $sensor_CH4[$graph_sensor_paths['sensor_id']]['x'];
                        $yS = $sensor_CH4[$graph_sensor_paths['sensor_id']]['y'];
                        $zS = $sensor_CH4[$graph_sensor_paths['sensor_id']]['z'];
                        $xyzS = $sensor_CH4[$graph_sensor_paths['sensor_id']]['sensor_XYZ'];
                        $sensor_edge_id = $sensor_CH4[$graph_sensor_paths['sensor_id']]['sensor_edge_id'];

                        $xE = $conjunction_spr[$graph_path]['x'];
                        $yE = $conjunction_spr[$graph_path]['y'];
                        $zE = $conjunction_spr[$graph_path]['z'];

                        $distance = self::calcDistance($xS, $yS, $zS, $xE, $yE, $zE);
                        $distancesSensor[$graph_sensor_paths['sensor_id'] . '-' . $graph_path][] = $distance;
                        if ($distance <= 10) {
                            $warnings[] = "actionBuildGraph. Делаем новый поиск.Дистанция <20м = $distance";
                            // блок поиска эджей в которых расстояние от искомого сенсора будет до 20 метров с учетом уже
                            // пройденной дистанции
                            $response = self::findAllConjunction20M($graph_path, 20 - $distance, $shema_mine_conj_repac, $conjunction_spr, 0);
                            $edges = $response['edges'];
                            $warnings[] = $response['warnings'];
                            foreach ($edges as $edge_id) {
                                $graph_edge_by_sensor_result[$edge_id]['edge_id'] = $edge_id;
                                $graph_edge_by_sensor_result[$edge_id]['sensors'][$sensor_id]['sensor_id'] = $sensor_id;
                                $graph_edge_by_sensor_result[$edge_id]['sensors'][$sensor_id]['x'] = $xS;
                                $graph_edge_by_sensor_result[$edge_id]['sensors'][$sensor_id]['y'] = $yS;
                                $graph_edge_by_sensor_result[$edge_id]['sensors'][$sensor_id]['z'] = $zS;
                                $graph_edge_by_sensor_result[$edge_id]['sensors'][$sensor_id]['xyz'] = $xyzS;
                                $graph_edge_by_sensor_result[$edge_id]['sensors'][$sensor_id]['edge_id'] = $sensor_edge_id;
                            }
                        } else {
                            $warnings[] = "actionBuildGraph. Идем к следующему пути.Дистанция $edge_id >20м = $distance";
                            $graph_edge_by_sensor_result[$edge_id]['edge_id'] = $edge_id;
                            $graph_edge_by_sensor_result[$edge_id]['sensors'][$sensor_id]['sensor_id'] = $sensor_id;
                            $graph_edge_by_sensor_result[$edge_id]['sensors'][$sensor_id]['x'] = $xS;
                            $graph_edge_by_sensor_result[$edge_id]['sensors'][$sensor_id]['y'] = $yS;
                            $graph_edge_by_sensor_result[$edge_id]['sensors'][$sensor_id]['z'] = $zS;
                            $graph_edge_by_sensor_result[$edge_id]['sensors'][$sensor_id]['xyz'] = $xyzS;
                            $graph_edge_by_sensor_result[$edge_id]['sensors'][$sensor_id]['edge_id'] = $sensor_edge_id;
                        }
                    }
                }
                /**
                 * Укладываем зоны выработок в кеш
                 */
                $response = $gas_cache_controller->setZonesEdgeMine($mine_id, $graph_edge_by_sensor_result);
                if ($response) {
                    $warnings[] = 'actionBuildGraph. успешно уложил ветви с сенсорами в кеш ZonesEdge';
                } else {
                    throw new Exception('actionBuildGraph. Ошибка укладывания ветвей с сенсорами в кеш ZonesEdge');
                }
                $result['sensor_ch4'] = $sensor_CH4;
                $result['graph_for_search'] = $graph_for_search;
                $result['distancesSensor'] = $distancesSensor;
                $result['graph_edge_by_sensor_result'] = $graph_edge_by_sensor_result;
//            $result['graph_without_sensor_edge'] = $graph_without_sensor_edge;
            }


            $result['shema_repac'] = $shema_mine_edge_repac;
            $result['shema_mine_conj_repac'] = $shema_mine_conj_repac;
            $result['conjuction_spr'] = $conjunction_spr;


            unset(
                $shema_mine_edge_repac,
                $shema_mine_conj_repac,
                $conjunction_spr,
                $sensor_CH4,
                $graph_for_search,
                $distancesSensor,
                $graph_edge_by_sensor_result,
                $graph_without_sensor_edge
            );

        } catch (Throwable $ex) {
            $errors[] = 'actionBuildGraph. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
        return $result_main;
    }

    // actionCalcGasValueStaticMovement - метод вычисления попадания светильника индивидуального в зону действия
    // стационарного светильника
    // входные параметры:
    //      $sensor_id                          -   ключ сенсора индивидуального светильника - метки
    //      $edge_id                            -   ключ ветви на которой находится светильник
    //      $XYZ                                -   координата светильника
    //      $mine_id                            -   ключ шахты на которой находится светильник
    //      $gas_concentration                  -   концентрация газа со светильника
    // пример использования 127.0.0.1/admin/opc/calc-gas-value-static-movement?lamp_sensor_id=27542&lamp_edge_id=139104&XYZ=13130.71,-726.08,-11758.25&mine_id=290&lamp_CH4_value=1.5
    public static function actionCalcGasValueStaticMovement($lamp_sensor_id, $lamp_edge_id, $XYZ, $mine_id, $lamp_CH4_value)
    {
        $status = 1;
        $error_name = 0;
        $errors = array();
        $warnings = array();
        $result = array();
        $warnings[] = 'actionCalcGasValueStaticMovement. Начал выполнять метод';
        try {

            $warnings[] = "actionCalcGasValueStaticMovement. Переданы входные параметры sensor_id: $lamp_sensor_id edge_id: $lamp_edge_id XYZ: $XYZ mine_id: $mine_id";
            /**
             * получить список зон с датчиками
             */
            $gas_cache_controller = (new GasCacheController());
            $zone_edge_mine = $gas_cache_controller->getZonesEdgeMine($mine_id);
            if ($zone_edge_mine) {
                $warnings[] = 'actionCalcGasValueStaticMovement. кеш списка зон сенсоров есть';
                //$warnings[] = $zone_edge_mine;
                //$warnings[] = $zone_edge_mine;
            } else {
                throw new Exception('actionCalcGasValueStaticMovement. кеш списка зон сенсоров пуст');
            }
            /**
             * блок проверки светильника на наличие в зоне стационара
             */
            if (isset($zone_edge_mine[$lamp_edge_id])) {
                $warnings[] = 'actionCalcGasValueStaticMovement. Нашел зону стационарного сенсора';
                /**
                 * блок распарсивания координаты светильника
                 */
                $sensor_coordinates = explode(',', $XYZ);
                $lamp_CH4_x = $sensor_coordinates[0];
                $lamp_CH4_y = $sensor_coordinates[1];
                $lamp_CH4_z = $sensor_coordinates[2];
                $lamp_CH4_xyz = $XYZ;
            } else {
                $error_name = 1147;
                throw new Exception('actionCalcGasValueStaticMovement. светильник находится вне зоны стационарного датчика по ветвям');
            }

//            /**
//             * получить справочник сопряжений и их координат
//             */
//            $conjunction_spr = $gas_cache_controller->getConjuctionSprMine($mine_id);
//            if ($conjunction_spr) {
//                $warnings[] = 'actionCalcGasValueStaticMovement. кеш справочника сопряжений есть';
//                //$warnings[] = $conjunction_spr;
//            } else {
//                throw new \Exception('actionCalcGasValueStaticMovement. кеш справочника сопряжений пуст');
//            }


            /**
             * блок обработки расстояния от сенсора до светильника
             */
            foreach ($zone_edge_mine[$lamp_edge_id]['sensors'] as $static_sensor) {
                $static_CH4_x = $static_sensor['x'];
                $static_CH4_y = $static_sensor['y'];
                $static_CH4_z = $static_sensor['z'];
                $static_CH4_xyz = $static_sensor['xyz'];
                $static_CH4_sensor_id = $static_sensor['sensor_id'];
                $static_CH4_edge_id = $static_sensor['edge_id'];
                $distance = self::calcDistance($lamp_CH4_x, $lamp_CH4_y, $lamp_CH4_z, $static_CH4_x, $static_CH4_y, $static_CH4_z);
                $warnings[] = "actionCalcGasValueStaticMovement. Кратчайшее растояние равно $distance метров";
                if ($distance <= 14) {
                    $warnings[] = 'actionCalcGasValueStaticMovement. Кратчайшее растояние меньше 20 метров';
                    /**
                     * получаем показания стационарного датчика метана
                     */
                    $sensor_cache_controller = new SensorCacheController();
                    $static_CH4_values = $sensor_cache_controller->getParameterValueHash($static_CH4_sensor_id, 99, 3);
                    if (!$static_CH4_values or $static_CH4_values['value'] == -1 or $static_CH4_values['value'] > 2) {
                        //!!!!! ВАЖНО не факт что проверка будет последовательной. не уверен что $static_CH4_values['value']==-1 - правильно в этом контектсе
                        $warnings[] = 'actionCalcGasValueStaticMovement. У стационарного датчика нет показаний метана ' . $static_CH4_sensor_id;
                    } else {
                        /**
                         * получаем показания стационарного датчика
                         */
                        $date_time_now = Assistant::GetDateNow();                                                       // текущая дата и время
                        $static_CH4_value = str_replace(',', '.', $static_CH4_values['value']);                                              // показания стационарного датчика метана
                        //$static_CH4_sensor_parameter_id = $static_CH4_values['sensor_parameter_id'];
                        //$static_CH4_date_time = $static_CH4_values['date_time'];
                        $delta_time = strtotime($date_time_now) - strtotime($static_CH4_values['date_time']);           // расчет разбега времени
                        if ($delta_time > 120) {
                            $warnings[] = 'actionCalcGasValueStaticMovement. У стационарного датчика данные не актуальные ' . $static_CH4_sensor_id;
                        } else {
                            $warnings[] = 'actionCalcGasValueStaticMovement. Данные актуальные, можем сравнивать показания' . $static_CH4_sensor_id;
                            /**
                             * проверяем данные по дате на актуальность
                             */

                            $delta_value = abs((float)$lamp_CH4_value - (float)$static_CH4_value);
                            $lamp_CH4_value = str_replace(',', '.', $lamp_CH4_value);
                            if ($delta_value > 0.5) {
                                $warnings[] = "actionCalcGasValueStaticMovement. Генерируем событие проверьте датчики CH4 стационар=$static_CH4_value и CH4_lamp=$lamp_CH4_value";
                                $warnings[] = "actionCalcGasValueStaticMovement. Ключ CH4 стационар=$static_CH4_sensor_id";
                                $warnings[] = "actionCalcGasValueStaticMovement. Ключ CH4 лампы=$lamp_sensor_id";

                                /**
                                 * Генерация событий для датчиков
                                 */
                                $response = EventMainController::createCompareEvent($static_CH4_sensor_id, $lamp_sensor_id, EventEnumController::GAS_DIFFERENCE,
                                    round($static_CH4_value, 1), $lamp_CH4_value,
                                    $date_time_now, StatusEnumController::EMERGENCY_VALUE, 99,
                                    $mine_id, StatusEnumController::EVENT_RECEIVED, $static_CH4_edge_id, $lamp_edge_id, $static_CH4_xyz, $lamp_CH4_xyz);


                                if ($response['status'] == 1) {
                                    $warnings[] = $response['warnings'];
                                    $warnings[] = __METHOD__ . '. Событие успешно сохранено';
                                } else {
                                    $errors[] = $response['errors'];
                                    $warnings[] = $response['warnings'];
                                    throw new Exception(__METHOD__ . '. Ошибка сохранения события лампы');
                                }
                            } else {
                                $warnings[] = "actionCalcGasValueStaticMovement. Датчики в пределах нормы CH4 стационар=$static_CH4_value и CH4_lamp=$lamp_CH4_value";
                            }
                        }
                    }
                } else {
                    $warnings[] = 'actionCalcGasValueStaticMovement. Кратчайшее расстояние больше 20 метров';
                }
            }

        } catch (Throwable $ex) {
            $errors[] = 'actionCalcGasValueStaticMovement. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
            if ($error_name != 1147) {
                $data_to_log_gas = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
                LogCacheController::setGasLogValue('actionCalcGasValueStaticMovement', $data_to_log_gas, '2');
            }
        }
        $warnings[] = 'actionCalcGasValueStaticMovement. Закончил выполнять метод';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        //        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = $result_main;
    }

    // calcDistance - метод расчета расстояния между двумя точками
    public static function calcDistance($xStart, $yStart, $zStart, $xEnd, $yEnd, $zEnd)
    {
        $distance = pow((pow(($xStart - $xEnd), 2) + pow(($yStart - $yEnd), 2) + pow(($zStart - $zEnd), 2)), 0.5);
        return $distance;
    }

    // findAllConjunction20M - метод поиска всех эджей от текущего конжакшена для определения зоны влияния датчика CH4
    // $conjunction_id          - искомое сопряжение
    // $distance_undo           - остаточное расстояние для поиска путей
    // $shema_mine_conj_repac   - остаточный граф поиска путей
    // $conjunction_spr         - справочник сопряжений с координатами
    public static function findAllConjunction20M($conjunction_id, $distance_undo, $shema_mine_conj_repac, $conjunction_spr, $iteration)
    {
        if ($iteration > 3000) {
            return array('edges' => null, 'warnings' => "findAllConjunction20M. Превышена глубина сканирования возможных эджей");
        }
        $edges = array();
        $warnings = array();
        $warnings[] = "findAllConjunction20M. начал выполнять заход сопряжения $conjunction_id с итерацией $iteration";
        $conjunctions_for_search = $shema_mine_conj_repac[$conjunction_id];
        foreach ($conjunctions_for_search as $conjunction_id_end_item) {

            $xS = $conjunction_spr[$conjunction_id]['x'];
            $yS = $conjunction_spr[$conjunction_id]['y'];
            $zS = $conjunction_spr[$conjunction_id]['z'];
            $conjunction_id_end = $conjunction_id_end_item['conjunction_id'];
            $xE = $conjunction_spr[$conjunction_id_end]['x'];
            $yE = $conjunction_spr[$conjunction_id_end]['y'];
            $zE = $conjunction_spr[$conjunction_id_end]['z'];
            $edge_id = $shema_mine_conj_repac[$conjunction_id][$conjunction_id_end]['edge_id'];

            $distance = self::calcDistance($xS, $yS, $zS, $xE, $yE, $zE);
            $warnings[] = "findAllConjunction20M. расчетное расстояние $distance между $conjunction_id и $conjunction_id_end";
            if ($distance <= $distance_undo) {
                $warnings[] = "findAllConjunction20M. Делаем новый поиск.Дистанция $distance_undo > $distance";
                // блок поиска эджей в которых расстояние от искомого сенсора будет до $distance_undo метров с учетом уже
                // пройденной дистанции
                $iteration++;
                unset($shema_mine_conj_repac[$conjunction_id][$conjunction_id_end]);
                unset($shema_mine_conj_repac[$conjunction_id_end][$conjunction_id]);
                $response = self::findAllConjunction20M($conjunction_id_end, $distance_undo - $distance, $shema_mine_conj_repac, $conjunction_spr, $iteration);
                $edges = array_merge($edges, $response['edges']);
                $warnings[] = $response['warnings'];
            } else {
                $warnings[] = "findAllConjunction20M. Идем к следующему пути.Дистанция $edge_id: $distance_undo < $distance";
                $edges[] = $edge_id;
            }
        }
        $warnings[] = "findAllConjunction20M. закончил выполнять заход сопряжения $conjunction_id с итерацией $iteration";
        unset($conjunction_id);
        unset($distance);
        unset($shema_mine_conj_repac);

        return array('edges' => $edges, 'warnings' => $warnings);
    }


    // actionSetTagInstall - метод записи значения сенсора в кеш
    // входные параметры:
    //      $sensor_id                   - айди сеносора
    //      $sensor_tag_name             - имя тэга
    //      $sensor_parameter_value      - значения тэга
    //      $parameter_id                - айди параметра
    //      $parameter_type_id           - тип параметра
    // выходные параметры:
    //      $sensor_id                   - айди сеносора
    //      $sensor_tag_name             - имя тэга
    //      $sensor_parameter_value      - значения тэга
    //      $parameter_id                - айди параметра
    //      $parameter_type_id           - тип параметра
    // пример использования: http://127.0.0.1:98/admin/opc/set-tag-install?sensor_id=1350&sensor_tag_name=storage.time.reg02&sensor_parameter_value=09.01.111&parameter_id=463&parameter_type_id=2
    // Написал: Фазуллоев А.Э.
    public static function actionSetTagInstall()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'actionSetTagInstall. Начало выполнения метода';
        try {
            /**
             * блок обработки входных параметров
             */
            $post = Assistant::GetServerMethod();
            $all_parameters_has_been_set = isset(
                $post['sensor_id'], $post['sensor_tag_name'],
                $post['parameter_id'], $post['sensor_parameter_value'],
                $post['parameter_type_id']
            );
            if ($all_parameters_has_been_set &&
                ($post['sensor_id'] != '') &&
                ($post['sensor_tag_name'] != '') &&
                ($post['parameter_id'] != '') &&
                ($post['sensor_parameter_value'] != '') &&
                ($post['parameter_type_id'] != '')
            ) {
                $sensor_id = $post['sensor_id'];
                $sensor_tag_name = $post['sensor_tag_name'];
                $parameter_id = $post['parameter_id'];
                $sensor_parameter_value = $post['sensor_parameter_value'];
                $parameter_type_id = $post['parameter_type_id'];
                $warnings[] = 'actionSetTagInstall. Входные параметры получены';
            } else {
                throw new Exception('actionSetTagInstall. Не переданы входные параметры полностью: sensor_id sensor_tag_name parameter_id sensor_parameter_value parameter_type_id');
            }

            /**
             * Сохранение тега в кэше
             */
            $response = self::writeTagOnServer($sensor_id, $sensor_tag_name, $sensor_parameter_value, $parameter_id, $parameter_type_id);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                throw new Exception('actionSetTagInstall. Ошибка сохранения тега в кэше');
            }

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionSetTagInstall. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = 'actionSetTagInstall. Закончил выполнение метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        //      Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = $result_main;
        return json_encode($result_main);
    }

    /**
     * Запись тега в кэш, откуда он впоследствии забирается службой OPC
     * и отправляется на OPC сервер
     * @param int $sensor_id идентификатор сенсора
     * @param string $tag_name наименование тега
     * @param string $tag_value значение тега
     * @param int $parameter_id идентфикатор параметра
     * @param int $parameter_type_id идентификатор типа параметра
     * @return array
     */
    public static function writeTagOnServer($sensor_id, $tag_name, $tag_value, $parameter_id, $parameter_type_id)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        //TODO перенапраляю метод так как он пака не нужен
        return $result;
        $warnings[] = 'writeTagOnServer. Начало метода';
        try {
            /**
             * Установка значение тега в кеше OPC
             */

            $opc_cache_controller = new OpcCacheController();
            $warnings[] = "writeTagOnServer. Параметр: $parameter_id Тип параметра: $parameter_type_id Имя тэга: $tag_name Значение: $tag_value";
            $tag_structure = OpcCacheController::buildStructureTag($sensor_id, $tag_name, $tag_value, $parameter_id, $parameter_type_id);
            $key = OpcCacheController::buildTagKey($sensor_id, $parameter_id, $parameter_type_id);
            $warnings[] = "writeTagOnServer. ключ для вставки $key";
            $opc_cache_controller->amicum_rSet($key, $tag_structure);
            $warnings[] = 'writeTagOnServer. Сохранил в кеш';

            $warnings[] = 'writeTagOnServer. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'writeTagOnServer. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
    }

    // actionGetTagInstall - метод для получения список тэгов на установку на OPC сервер
    // входные параметры:
    //      $sensor_id   -   ключ OPC сервера
    // выходные параметры:
    //      $sensor_id
    //      $sensor_tag_name
    //      $sensor_parameter_value
    //      $parameter_id
    //      $parameter_type_id
    // пример вызова: http://127.0.0.1:98/admin/opc/get-tag-install?sensor_id=1350
    // Написал: Фазуллоев А.Э.
    public static function actionGetTagInstall()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'actionGetTagInstall. Начало выполнения метода';
        try {
            /**
             * блок обработки входных параметров
             */
            $post = Assistant::GetServerMethod();
            if (
                isset($post['sensor_id']) and $post['sensor_id'] != ""
            ) {
                $sensor_id = $post['sensor_id'];
                $warnings[] = "actionGetTagInstall. Входные параметры получены";
            } else {
                throw new Exception("actionGetTagInstall. Не переданы входные параметры полностью: sensor_id");
            }

            /**
             * блок получения списка тегов OPC сервера
             */
            $opc_cache_controller = new OpcCacheController();
            $list_tags = $opc_cache_controller->multiGetTag($sensor_id);
            if ($list_tags) {
                $result = $list_tags;
                /**
                 * блок очистки кеша по факту получения тегов
                 */
                $opc_cache_controller->multiDelTag($sensor_id);
            } else {
                $warnings[] = "actionGetTagInstall. Кеш тегов OPC сервера пуст";
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "actionGetTagInstall. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "actionGetTagInstall. Закончил выполнение метода";
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

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
    //  status:     - состояние по работе метода (0 - выполнен с ошибками, 1 выполнен полностью и без ошибок)
    //  errors:     - массив ошибок при работе метода
    //  warnings:   - массив предупреждений и отладочной информации при работе метода
    // разработал: Файзуллоев А.Э.
    // дата разработки: ~ 03.12.2019
    // пример вызова: /admin/opc/set-equipment-value?opc_tags_value={"TagName":"DEP.R.BUK_43_43","TagValue":"0","TimeStamp":"2019-12-03 04:51:58.629000","Quality":"good","sensor_parameter_id":"632596","parameter_type_id":"2","parameter_id":"940","sensor_id":"139238","sensor_parameter_value":null,"sensor_tag_name":null}
    public function actionSetEquipmentValue()
    {
        $method_name = "actionSetEquipmentValue";                                                                                                //массив ошибок
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();                                                                                                //массив результать метода
        $warnings = array();                                                                                              //массив предупреждений
        $start = microtime(true);
        try {
            $warnings[] = 'actionSetEquipmentValue. Начало выполнения метода';
            $post = Assistant::GetServerMethod();

            if (isset($post['opc_tags_value']) && $post['opc_tags_value'] != '') {
                $opc_tags_value = json_decode($post['opc_tags_value']);
                $warnings[] = 'actionSetEquipmentValue. Передан входной параметр';
                $result['parameter_title'] = $opc_tags_value->TagName;
                $result['sensor_parameter_value'] = $opc_tags_value->TagValue;
                $result['sensor_parameter_id'] = $opc_tags_value->sensor_parameter_id;
                $result['parameter_id'] = $opc_tags_value->parameter_id;
                $result['parameter_type_id'] = $opc_tags_value->parameter_type_id;
                $result['sensor_parameter_date_time'] = $opc_tags_value->TimeStamp;
                $result['sensor_id'] = $opc_tags_value->sensor_id;
                $result['Quality'] = $opc_tags_value->Quality;
                $value = (string)str_replace(',', '.', $opc_tags_value->TagValue);                       // значение измеренного тега
                $sensor_parameter_id = $opc_tags_value->sensor_parameter_id;                                            // конкретный ключ измеренного тега
                $tag_datetime = Assistant::GetDateNow();                                                                // дата и время в которое пришло измеренное значение
                $quality = $opc_tags_value->Quality;                                                                    // качество полученного значения
                $sensor_id = $opc_tags_value->sensor_id;                                                                // ключ сенсора
                $parameter_type_id = $opc_tags_value->parameter_type_id;
            } else {
                throw new Exception('actionSetEquipmentValue. Не передан входной параметр opc_tags_value');
            }
            $mine_id = AMICUM_DEFAULT_MINE;
            if (isset($opc_tags_value->mine_id)) {
                $mine_id = $opc_tags_value->mine_id;
            }
            //если нет разрешения на запись, то метод не выполняется
            if (!(new ServiceCache())->CheckDcsStatus($mine_id, 'opcEquipmentStatus')) {
                throw new Exception("Нет разрешения на запись");
            }
            /**
             * блок проверки качество полученного значения
             */
            if ($quality == 'good') {
                $sensor_parameter_status_id = 1;
            } else if ($quality == 'bad') {
                $sensor_parameter_status_id = 19;
                $tag_datetime = Assistant::GetDateNow();
            } else {
                throw new Exception('Получен неопределенное качество: ' . $quality);
            }

            /**
             * блок проверки наличе значение по оборудовнию в кеше. Если нет то инициализируем
             */
            $sensor_cache_controller = (new SensorCacheController());
            $to_get_sensor_id = $sensor_cache_controller->multiGetSenParSenTag($sensor_parameter_id, '*'); // получаем sensor_id конкретного тега
            if ($to_get_sensor_id) {
                $sensor_id = $to_get_sensor_id[0]['sensor_id'];
                $warnings[] = "Получил sensor_id $sensor_id";
            } else {
                $errors[] = "actionSetEquipmentValue. в кеше сенсоров нет сенсора по sensor_parameter_id: $sensor_parameter_id";
                throw new Exception('actionSetEquipmentValue. sensor_id не получен');
            }

            $equipmen_cache_controller = (new EquipmentCacheController());
            $to_get_equipment_ids = $equipmen_cache_controller->getSensorEquipmentParameter($sensor_id, '*', '*');               //получаем equipmen_id по sensor_id из кеша


            if ($to_get_equipment_ids) {
                $warnings[__FUNCTION__ . ' Есть привязанное к тегу оборудование'] = microtime(true) - $start;
                foreach ($to_get_equipment_ids as $to_get_equipment_id) {
                    $equipment_id = $to_get_equipment_id['equipment_id'];
                    $equipment_parameter_id = $to_get_equipment_id['equipment_parameter_id'];
                    $parameter_id = $to_get_equipment_id['parameter_id'];
                    /**
                     * Проверяем, изменилось ли значение по сравнению с предыдущим значением, если не изменилось то нет смысла добавить эти значения в бд и кэш
                     */
                    $response = EquipmentMainController::IsChangeEquipmentParameterValue($equipment_id, $parameter_id, $parameter_type_id, $value, $tag_datetime);
                    if ($response['status'] == 1) {
                        $flag_save = $response['flag_save'];
                        $warnings[] = $response['warnings'];
                        $warnings[] = "actionSetEquipmentValue. Флаг сохранения в БД получен и рачен $flag_save";
                        $errors[] = $response['errors'];
                        $warnings[] = "actionSetEquipmentValue. Кеш сенсора $sensor_id инициализирован";
                    } else {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        throw new Exception("actionSetEquipmentValue. Ошибка обработки флага сохранения значения в БД и кеш для сенсора $sensor_id");
                    }


                    /**
                     * блок проверки значания
                     */
                    if ($parameter_id == 164) {
                        if ($value >= 16) {
                            $value = 1;// если 16,32,64 то работает
                        } else if ($value == 1) {
                            $value = 1; // если равно 1 то это комбайн и он работает
                        } else if ($value == 0 and $value !== true and $value !== 'True' and $value !== false and $value !== 'False') {
                            if ($quality == "good") {
                                $value = 2; // в случае коибайнов 0 означает что доступен но не работает
                            } else {
                                $value = 0; // значение 0 не работает или не доступен
                            }
                        } else {
                            if ($value === true or $value == 'True' or $value == 'true') {
                                $value = 1;
                            } else if ($value === false or $value == 'False' or $value == 'false') {
                                $value = 0;
                            } else {
                                $value = 2; //2 - доступен но не работает
                            }

                        }
                        //если параметер "Ток дейсвующий" то округляем до 2 значения после запятая так как оно слышком большое
                    } elseif ($parameter_id == 20) {

                        $value_round = round($value, 2);
                        $value = $value_round;

                    } else {
                        $warnings[] = "actionSetEquipmentValue. Параметр тега равен $parameter_id";
                    }

                    if ($flag_save !== 1) {
                        /**
                         * блок укладка значения в кеш параметров оборудования
                         */
                        if ($equipment_id != "" and $equipment_parameter_id != -1 and $equipment_parameter_id) {

                            $response = $equipmen_cache_controller->setEquipmentParameterValue($equipment_id, $equipment_parameter_id, $parameter_id, 3, $tag_datetime, $value, $sensor_parameter_status_id);
                            if ($response['status'] == 1) {
                                $warnings[] = "actionSetEquipmentValue. Уложил данные в кеш параметров оборудования";
                                $warnings[] = $response['warnings'];
                            } else {
                                $errors[] = "actionSetEquipmentValue. Ошибка при укладке данные в кеш параметров оборудования";
                                $errors[] = $response['errors'];
                            }

                            /**
                             * блок вставки значения в БД
                             */
                            $warnings[] = "actionSetEquipmentValue. Вставка значения в БД";
                            $data_to_db[] = [
                                'equipment_parameter_id' => $equipment_parameter_id,
                                'date_time' => $tag_datetime,
                                'value' => $value,
                                'status_id' => $sensor_parameter_status_id];
                        }
                    } else {
                        $warnings[] = "Значение $value не изминилось, не пишем в кэш";
                    }

                }
                if ($flag_save !== 1) {
                    if (isset($data_to_db)) {
                        $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert('equipment_parameter_value', ['equipment_parameter_id', 'date_time', 'value', 'status_id'], $data_to_db)->execute();
                        if (!$insert_result_to_MySQL) {
                            throw new Exception($method_name . '. Ошибка массовой вставки в БД в таблицу equipment_parameter_value' . $insert_result_to_MySQL);
                        }
                        $warnings[__FUNCTION__ . ' блок вставки значения в БД '] = microtime(true) - $start;
                    }
                } else {
                    $warnings[] = "Значение $value не изминилось, не пишем в БД";
                }
            } else {
                $warnings[__FUNCTION__ . ' Нет привязанного к тегу оборудования '] = microtime(true) - $start;
            }

            $warnings[__FUNCTION__ . ' Закончил выполнение метода'] = microtime(true) - $start;
        } catch
        (Throwable $exception) {
            $status = 0;
            $errors[] = "Исключение. actionSetEquipmentValue";
            $errors[] = $exception->getMessage();
        }
        $main_resul = array('status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $main_resul;
    }

    /**
     * actionAddPrefixTagsName - Метод добовления в наименование тэга префикс с помощью котого можно разлечитт к какому шахту относиться тэг
     * ВАЖНО: перед запуском метода обязательно запустит ниже выложенный скрипт sql и убедится в том, что в запросе не нечего кроме как наменование тэга
     * например, "Наименование шахтного поля (Main)" добавить условию  ->andWhere('parameter_id != 346')
     * если имеется, то в условии запроса нужно добавить его в исключение, дабы не поменять название важного параметра
     * SELECT sensor_id, sensor_parameter.id
     * AS sensor_parameter_id, parameter_id, parameter_type_id, parameter.title
     * AS tag_name FROM sensor_parameter
     * INNER JOIN parameter ON sensor_parameter.parameter_id=parameter.id
     * WHERE (sensor_id=115978) AND (parameter_type_id=2) LIMIT 25000
     *
     * Za - Заполярная
     * Ko - Комсамольская
     * Va - Воргашорская
     * Vo - Воркутинская
     * sensor_id - айдишник ССД OPC.
     * sensor_id можно получить вызывая метод http://127.0.0.1:98/admin/opc/get-config?opc_title=ССД OPC "Имя шахты для которого нужно найти сенсор айди без клвычек"
     * Примем вызова:http://127.0.0.1:98/admin/opc/add-prefix-tags-name?sensor_id=115978&mine_prefix=Za
     */
    public function actionAddPrefixTagsName()
    {
        // базовые входные параметры скрипта
        $name_method = 'actionAddPrefixTagsName';
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        $list_tags_after_update = array(); //список тэгов после обновления
        $post = Assistant::GetServerMethod();

        try {
            /* throw new \Exception($name_method."А ты проверил запрос SELECT sensor_id, sensor_parameter.id
          AS sensor_parameter_id, parameter_id, parameter_type_id, parameter.title
             AS tag_name FROM sensor_parameter
                 INNER JOIN parameter ON sensor_parameter.parameter_id=parameter.id
                     WHERE (sensor_id=115978) AND (parameter_type_id=2) LIMIT 25000");*/
            $warnings[] = $name_method . ' Начел выполнения';
            //блок валидация данных
            if (isset($post['sensor_id']) and $post['sensor_id'] != "" and
                isset($post['mine_prefix']) and $post['mine_prefix'] != ""
            ) {
                $sensor_id = $post['sensor_id'];

                $mine_prefix = $post['mine_prefix'];
            } else {
                throw new Exception('actionAddPrefixTagsName. Не передан входной параметр sensor_id или префикс шахты');
            }

            //получения списко имеющихся OPC тэгов
            $sensor_tags_list = (new Query())
                ->select([
                    'parameter_id',
                    'parameter.title as tag_name'
                ])
                ->from('sensor_parameter')
                ->innerJoin('parameter', 'sensor_parameter.parameter_id=parameter.id')
                ->where([
                    'sensor_id' => $sensor_id,
                    'parameter_type_id' => 2,

                ])
                ->andWhere('parameter_id != 346')
                ->limit(25000)
                ->all();
            if ($sensor_tags_list) {
                $warnings[] = $name_method . ' Получил список тэгов из БД:';
                $warnings[] = $sensor_tags_list;
            }
            $warnings[] = $name_method . ' Начинаю присваивать префикс по каждому тэгу';
            $count = 0;
            foreach ($sensor_tags_list as $tag) {
                $count++;
                $list_tags_after_update[] = array('parameter_id' => $tag['parameter_id'], 'tag_name' => $mine_prefix . '-' . $tag['tag_name']);
            }
            $warnings[] = $name_method . ' Количество обновленных тэгов = ' . $count;
            $warnings[] = $name_method . ' Список тэгов после обработки:';
            $warnings[] = $list_tags_after_update;

            $count = 0;
            $warnings[] = $name_method . ' Начинаю обновить обработанных тэгов в БД по одной';

            foreach ($list_tags_after_update as $tag) {
                $count++;

                $parameters = Parameter::findOne($tag['parameter_id']);
                $parameters->title = $tag['tag_name'];
                $parameters->update();

            }
            $warnings[] = $name_method . ' Количество обновлённых тэгов после обновления в БД = ' . $count;


            $warnings[] = $name_method . ' Закончил выполнения';
        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = $name_method . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }
        $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;

    }

    // СutPrefixTagsName - метод для отрезания префикса с названия OPC тэга
    // $list_tags - массив параметров тэга на обработку
    //'sensor_id' = айдишник ССД опс
    //'tag_name' = имю тэга
    //'sensor_parameter_id' = сенчор параеметр тэга
    //'parameter_id'  = параметр айди тэга
    //'parameter_type_id' = тип паремтр тэга
    //пример вызова: $this->CutPrefixTagsName($list_tags);
    public function CutPrefixTagsName($list_tags)
    {
        // базовые входные параметры скрипта
        $name_method = 'cutPrefixTagsName';
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = array();                                                                                                 // результирующий массив (если требуется)
        $status = 1;
        $count = 0;

        try {

            $warnings[] = $name_method . " Начало";
//            $warnings[] = $list_tags;

            foreach ($list_tags as $tag) {
                $result[] = array(
                    'sensor_id' => $tag['sensor_id'],
                    'tag_name' => mb_substr($tag['tag_name'], 3),
                    'sensor_parameter_id' => $tag['sensor_parameter_id'],
                    'parameter_id' => $tag['parameter_id'],
                    'parameter_type_id' => $tag['parameter_type_id']
                );
                $count++;
            }

            $warnings[] = $name_method . " Количество обработаных тэгов = " . $count;
            $warnings[] = $name_method . " Конец";
        } catch (Throwable $throwable) {
            $status = 0;
            $errors[] = $name_method . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();
        }

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * actionMsetTagsValue - метод по получении значения тэгов за раз
     * Описание:
     * Метод вызывается  из ССД OPC и получает массив значении тэгов (сенсоров)
     * и дальше массово получает все значении этих тэгов из кэш и массово сделает вставку в БД и кэш.
     *
     * Пример вызова: 127.0.0.1/admin/opc/mset-tags-value?sensor_id=377012&mine_id=290&array_tags_value=["{\"TagName\":null,\"TagValue\":\"0\",\"TimeStamp\":\"2020-06-25 06:49:27.000000\",\"Quality\":\"good\",\"sensor_parameter_id\":\"1000412\",\"parameter_type_id\":\"2\",\"parameter_id\":\"480\",\"sensor_id\":\"377012\",\"sensor_parameter_value\":null,\"sensor_tag_name\":null,\"tag_range\":null}","{\"TagName\":null,\"TagValue\":\"5\",\"TimeStamp\":\"2020-06-25 06:49:30.000000\",\"Quality\":\"uncertainEUExceeded[low]\",\"sensor_parameter_id\":\"1000490\",\"parameter_type_id\":\"2\",\"parameter_id\":\"482\",\"sensor_id\":\"377012\",\"sensor_parameter_value\":null,\"sensor_tag_name\":null,\"tag_range\":null}","{\"TagName\":null,\"TagValue\":\"0,219\",\"TimeStamp\":\"2020-06-25 06:49:30.000000\",\"Quality\":\"good\",\"sensor_parameter_id\":\"1000496\",\"parameter_type_id\":\"2\",\"parameter_id\":\"484\",\"sensor_id\":\"377012\",\"sensor_parameter_value\":null,\"sensor_tag_name\":null,\"tag_range\":null}"]
     */
    public function actionMsetTagsValue()
    {
        $log = new LogAmicumFront("actionMsetTagsValue");

        try {
            $log->addLog('Начало выполнения метода');

            $post = Assistant::GetServerMethod();

            if (!isset($post['sensor_id']) or !isset($post['array_tags_value'])) {
                throw new Exception("Не переданы выходные параметры метода");
            }

            $sensor_id_opc = $post['sensor_id'];
            $mine_id = AMICUM_DEFAULT_MINE;
            if (isset($post['mine_id'])) {
                $mine_id = $post['mine_id'];
            }
            //если нет разрешения на запись, то метод не выполняется
            if (!(new ServiceCache())->CheckDcsStatus($mine_id, 'opcMikonStatus')) {
                throw new Exception("Нет разрешения на запись");
            }
            $array_tags_value = json_decode($post['array_tags_value']);
            /**
             * Проверяем наличие детальных сведений о сенсоров в кеше, если нет, то инициализируем сенсоров в кеше со всеми параметрами
             */
            /**
             * Получение аварийной уставки для датчика и генерация события,
             * если есть превышение
             */
            $sensor_cache_controller = (new SensorCacheController());
            $sensor_parameter_value_list_cache = $sensor_cache_controller->multiGetParameterValueHash();
            $sensor_parameter_value_cache_array = [];
            if ($sensor_parameter_value_list_cache !== false) {
                foreach ($sensor_parameter_value_list_cache as $sensor_parameter_value_cache) {
                    $sensor_parameter_value_cache_array[$sensor_parameter_value_cache['sensor_id']][$sensor_parameter_value_cache['parameter_type_id']][$sensor_parameter_value_cache['parameter_id']] = $sensor_parameter_value_cache;
                }
            }
            unset($sensor_parameter_value_list_cache);


            if (!isset($sensor_parameter_value_cache_array[$sensor_id_opc])) {
                throw new Exception("Cенсор $sensor_id_opc не существует");
            }

            $log->addLog('Проверили наличие детальных сведений о сенсоре в кеше, если нет, то инициализируем сенсор в кеше со всеми параметрами');

            // Готовим справочник
            $sensor_parameter_sensors = $sensor_cache_controller->multiGetSenParSenTag();
            foreach ($sensor_parameter_sensors as $sensor_parameter_sensor) {
                $hand_sensor_parameter_sensors[$sensor_parameter_sensor['sensor_parameter_id_source']] = $sensor_parameter_sensor;
            }
            unset($sensor_parameter_sensors);

            foreach ($array_tags_value as $array_tag_value_json) {
                $array_tag_value = json_decode($array_tag_value_json);

                $sensor_parameter_id_tag = $array_tag_value->sensor_parameter_id;                                       // конкретный ключ измеренного тега
                $tag_datetime = Assistant::GetDateNow();                                                                // дата и время в которое пришло измеренное значение

                $parameter_id = $array_tag_value->parameter_id;                                                         // ключ параметра тега
                $parameter_type_id = $array_tag_value->parameter_type_id;                                               // тип параметра тега
                $quality = $array_tag_value->Quality;                                                                   // качество полученного значения
                $value = $array_tag_value->TagValue;                                                                    // качество полученного значения

                /**
                 * Подготовим массив для массовой вставки значений тегов в БД
                 */
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


                $data_to_mset_in_db[] = array(
                    "sensor_parameter_id" => $sensor_parameter_id_tag,
                    "date_time" => $tag_datetime,
                    "value" => $value,
                    "status_id" => $sensor_parameter_status_id
                );

                /**
                 * Готовим структуру и данные для массовой вставки в кэш
                 */
                $data_to_mset_cache[] = SensorCacheController::buildStructureSensorParametersValue($sensor_id_opc, $sensor_parameter_id_tag, $parameter_id, $parameter_type_id, $tag_datetime, $value, $sensor_parameter_status_id);

                /**
                 * Получаем сенсоры, к которым привязаны тэги в кеше
                 */
                if (isset($hand_sensor_parameter_sensors[$sensor_parameter_id_tag])) {
                    $sensor_id = $hand_sensor_parameter_sensors[$sensor_parameter_id_tag]['sensor_id'];
                    $sensor_parameter_id = $hand_sensor_parameter_sensors[$sensor_parameter_id_tag]['sensor_parameter_id'];
                    $parameter_id_target_sensor = $hand_sensor_parameter_sensors[$sensor_parameter_id_tag]['parameter_id']; // справочный параметр сенсора в который мы пишем значение
                    /**
                     * Готовим массив для массовой вставки в БД для параметра 99
                     */
                    $data_to_mset_in_db[] = array(
                        "sensor_parameter_id" => $sensor_parameter_id,
                        "date_time" => $tag_datetime,
                        "value" => $value,
                        "status_id" => $sensor_parameter_status_id
                    );

                    /**
                     * Готовим массив для массовой вставки в кэш для 99-го параметра
                     */
                    $data_to_mset_cache[] = SensorCacheController::buildStructureSensorParametersValue($sensor_id, $sensor_parameter_id, $parameter_id_target_sensor, 3, $tag_datetime, $value, $sensor_parameter_status_id);


                    /**
                     * Записываем состояние привязанного сенсора 164
                     */
                    if (isset($sensor_parameter_value_cache_array[$sensor_id][3][164])) {
                        $sensor_parameter_id_164 = $sensor_parameter_value_cache_array[$sensor_id][3][164]['sensor_parameter_id'];
                        $log->addLog("Получил из справочника sensor_parameter_id $sensor_parameter_id_164");
                    } else {
                        $response = SensorMainController::GetOrSetSensorParameter($sensor_id, 164, 3);
                        $log->addLogAll($response);
                        if ($response['status'] == 1) {
                            $sensor_parameter_id_164 = $response['sensor_parameter_id'];
                            $log->addLog("Получил из кеша или БД sensor_parameter_id $sensor_parameter_id_164");
                        } else {
                            unset($sensor_parameter_id_164);
                        }
                    }
                    /**
                     * Определяем состояние привязанного параметра на основе значения статуса тега.
                     * Если состояние 1, то и статус у сенсора 1.
                     * Если статус тега равен 19, то значение состояние равно 0.
                     */
                    if (isset($sensor_parameter_id_164)) {
                        if ($sensor_parameter_status_id == 1) {
                            $value = 1;
                        } else {
                            $value = 0;
                        }
                        /**
                         * Готовим значения параметра сенсора 164 для массовой вставки в БД
                         */
                        $data_to_mset_in_db[] = array(
                            "sensor_parameter_id" => $sensor_parameter_id_164,
                            "date_time" => $tag_datetime,
                            "value" => $value,
                            "status_id" => $sensor_parameter_status_id
                        );

                        /**
                         * Готовим структуру и массив для массовой вставки в кэш
                         */
                        $data_to_mset_cache[] = SensorCacheController::buildStructureSensorParametersValue($sensor_id, $sensor_parameter_id_164, 164, 3, $tag_datetime, $value, $sensor_parameter_status_id);
                    }
                } else {
                    $log->addLog("Не нашел sensor_parameter_id тэга $sensor_parameter_id_tag в кеше всех привязок к конкретным параметрам");
                }

            }
            $log->addLog("Закончил обработку тегов");

            if (isset($data_to_mset_in_db)) {
                $result_Binsert = Yii::$app->db->createCommand()->batchInsert('sensor_parameter_value', ['sensor_parameter_id', 'date_time', 'value', 'status_id'], $data_to_mset_in_db)->execute();
                if (!$result_Binsert) {
                    throw new Exception("Ошибка при массовой вставке в БД");
                }
                unset($data_to_mset_in_db);

                $log->addLog("Закончил массовую вставку значений тэгов в БД независимо от того что менялось значение или нет");

                unset($result_Binsert);
            }
            /**
             * Запись значение тега в кеш
             */
            if (isset($data_to_mset_cache)) {
                $response = $sensor_cache_controller->multiSetSensorParameterValueHash($data_to_mset_cache);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception("Не удалось записать  значение тэгов в кэш");
                }

                unset($data_to_mset_cache);

                $log->addLog("Закончил массовую вставку значений тегов в кеш");
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
            $log->saveOpcLogValueCache();
        }

        $log->addLog("Окончание выполнения метода");

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => ""], $log->getLogAll());
    }

    /**
     * actionSetErrorToCache - метод отправляет ошибок из ССД OPC в кэш OPC
     * $errors - ошибки который отравляет OPC ССД
     * пример - http://127.0.0.1/admin/opc/set-error-to-cache?errors="ошибка"
     */
    public function actionSetErrorToCache($errors = "")
    {
        $data_to_log = array('errors' => $errors);
        //LogCacheController::setOpcLogValue('actionSetErrorToCache', $data_to_log, '2');
    }
}
