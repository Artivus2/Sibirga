<?php

/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers;

use backend\controllers\Assistant;
use backend\controllers\Assistant as BackendAssistant;
use backend\controllers\cachemanagers\WorkerCacheController;
use backend\controllers\OpcController;
use backend\controllers\SensorBasicController;
use backend\controllers\StrataJobController;
use Exception;
use frontend\controllers\handbooks\DepartmentController;
use frontend\controllers\handbooks\HandbookRegulationController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Checking;
use frontend\models\Mine;
use frontend\models\Order;
use frontend\models\SensorParameter;
use frontend\models\SituationJournal;
use frontend\models\SituationJournalSituationSolution;
use frontend\models\SituationSolution;
use frontend\models\SituationSolutionHystory;
use frontend\models\SituationSolutionStatus;
use frontend\models\SolutionCard;
use frontend\models\SolutionCardStatus;
use frontend\models\SolutionOperation;
use frontend\models\SolutionOperationStatus;
use frontend\models\Worker;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;
use yii\web\Response;

class EdsFrontController extends Controller
{
    const STATUS_ACTUAL = 1;                                                                                            // Активный статус
    // данный контроллер предназначен СОУР/ЕДС, а так же содержит методы симуляции данных

    /**  STRATA */
    // actionSetGasPackageStrata        -   метод симуляции пакета Strata газа CH4
    // actionSetGasPackageMikon         -   метод симуляции пакета MIKON газа CH4

    /** СОУР */
    // GetSituationSolution             -   метод получения активного (значит его еще делают) конкретного решения ситуациипо его ключу
    // DeleteSituationSolution          -   метод удаления решения/устранения
    // DeleteSolutionCard               -   метод удаления карточки решения/устранения
    // DeleteSolutionOperation          -   метод удаления карточки решения/устранения
    // ChangeStatusSolutionCard         -   метод изменения статуса карточки решения/устранения ситуации
    // ChangeStatusSituationSolution    -   метод изменения статуса конкретного решения ситуации
    // ChangeStatusSolutionOperation    -   метод изменения статуса операции карточки решения/устранения ситуации
    // SaveSituationSolution            -   метод сохранения конкретного решения ситуации без карточек (карточки сохраняются отдельно
    // SaveSolutionCard                 -   метод сохранения карточки решения ситуации при создании на основе регламента
    // SaveSolutionCardEdit             -   метод сохранения карточки решения ситуации при редактировании
    // GetInjunctionByPlace             -   Метод получения списка действующих предписаний по месту
    // GetWorkersByPlace                -   Метод получения списка людей находящихся в данный момент в данном месте
    // CreateSolutionBySituation        -   метод генерации регламента на основе ключа ситуации
    // EditSolutionOperation            -   метод изменения операции карточки решения/устранения ситуации
    // GetDispatcherStatistics          -   метод получения индивидуальных показателей диспетчера при решении ситуаций
    // GetJournalSituationByYear        -   метод получения журнала ситуаций на год
    // GetDangerLevelMines              -   получение статистики по ситуациям по году
    // GetInjunctionsByCompany          -   метод получения статистики предписаний по подразделению
    // GetOrderByCompany                -   метод получения статистики нарядов по подразделению
    // GetJournalSituationByMonth       -   метод получения журнала ситуаций по конкретной дате на месяц

    /** ВЕБ СОКЕТ ПОДПИСКИ */
    // situationEliminationSaveSituationSolution            -   подписка обновляющая решение/устранение ситуации (создание/обновление решения без карточек)
    // situationEliminationNewSituationSolution             -   подписка добавляющая решение/устранение ситуации сгенерированное на основе ситуации и журнала ситуации
    // situationEliminationSaveSolutionCard                 -   подписка добавляющая карточку решение/устранение ситуации при создании карточки на основе решения с 0
    // situationEliminationSaveSolutionCardEdit             -   подписка добавляющая карточку решение/устранение ситуации при редактировании
    // situationEliminationDeleteSituationSolution          -   подписка удаляющая решение/устранение ситуации
    // situationEliminationDeleteSolutionCard               -   подписка удаляющая карточку решения/устранения ситуации
    // situationEliminationDeleteSolutionOperation          -   подписка удаляющая операцию карточки решения/устранения ситуации
    // situationEliminationChangeStatusSolutionCard         -   подписка меняющая статус карточки решение/устранение ситуации
    // situationEliminationChangeStatusSituationSolution    -   подписка меняющая статус решение/устранение ситуации
    // situationEliminationChangeStatusSolutionOperation    -   подписка меняющая статус операции карточки решение/устранение ситуации
    // situationEliminationEditSolutionOperation            -   подписка меняющая операцию карточки решение/устранение ситуации

    /**
     * Метод actionSetGasPackageStrata() - метод симуляции пакета Strata газа CH4
     * value - интересующее значение газа метана в нормальном понимании (%)
     * sensor_id - ключ сенсора
     * @package frontend\controllers
     * @example http://127.0.0.1/eds-front/set-gas-package-strata?value=5&sensor_id=27294
     *
     * @author Якимов М.Н.
     * Created date: on 01.02.2020
     */
    public function actionSetGasPackageStrata()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();

        try {
//            EventJournal::deleteAll();
//            SituationJournal::deleteAll();
//            (new SituationCacheController())->removeAll();
//            (new EventCacheController())->removeAll();
            // получаем данные с фронта
            $post = Assistant::GetServerMethod();                                                                         //получение данных от ajax-запрос

            // проверяем наличие на входе значения газа сенсора
            if (isset($post['value']) and $post['value'] != "") {
                $sensor_value = str_replace(',', '.', $post['value']) * 100;
            } else {
                $sensor_value = 560;
            }

            // получаем текущую дату и время
            $date_now = Assistant::GetDateNow();

            // проверяем наличие на входе ключа сенсора
            if (isset($post['sensor_id']) and $post['sensor_id'] != "") {
                $sensor_id = $post['sensor_id'];
            } else {
                $sensor_id = 26451;
            }

            // ищем сетевой идентификатор по ключу сенсора
            $sensor_net_id_obj = (new SensorBasicController)->getLastSensorParameterHandbookValue($sensor_id, ' parameter_id = 88 AND parameter_type_id = 1');
            if ($sensor_net_id_obj) {
                $sensor_net_id = $sensor_net_id_obj[0]['value'];
            } else {
                throw new Exception("actionSetGasPackageStrata. Для сенсора $sensor_id не существует привязки к нему параметра сетевого идентификтаора");
            }

            // готовим пакет для эмуляции
            $packet_object = (object)[
                'sequenceNumber' => '1',                                                                                // номер секции, не имеет практического значения (но нужно для сбора сообщения из разных кусков)
                'sourceNode' => $sensor_net_id,                                                                         // сетевой идентификатор узла связи
                'parametersCount' => 2,                                                                                 // количество параметров передаваемых в пакете
                'timestamp' => $date_now,                                                                               // время в которое был получен пакет
                'parameters' => array(                                                                                  // блок параметров
                    (object)[
                        'id' => 100,                                                                                    // ключ параметра (бесполезен в данном случае) изначально это sensor_parameter_id. Нужен для записи на прямую в БД без поиска ключа, но в методах не используется)
                        'value' => (object)[
                            'gasReading' => $sensor_value,                                                              // значение газа полученное из пакета (но его еще надо обработать,  т.к. для CO надо делить на 10000, а CH4 надо делить на 100)
                            'sensorModuleType' => '20'                                                                  // Тип датчика (какой газ измеряет CO(0) или CH4(20))
                        ]
                    ],
                    (object)[                                                                                           // блок нужен для правильного вычисления значения самого газа (эта часть по сути параметры для вышестоящего параметра) Но пример рукожопства конкретного программиста
                        'id' => 101,                                                                                    // ключ параметра (бесполезен в данном случае) изначально это sensor_parameter_id. Нужен для записи на прямую в БД без поиска ключа, но в методах не используется)
                        'value' => (object)[
                            'totalDigits' => '0',                                                                       // количество знаков перед запятой
                            'decimalDigits' => '0'                                                                      // количество знаков после запятой
                        ]
                    ],
                )
            ];
            // задаем ключ шахты
            $mine_id = '290';

//            throw new Exception("actionSetGasPackageStrata. отладочный стоп");
            // вызываем метод по обработке пакета газов страта
            $response = StrataJobController::saveEnvironmentalPacket(new \backend\controllers\EnvironmentalSensor($packet_object), $mine_id);

            $result[] = $response['Items'];
            $errors[] = $response['errors'];
            $warnings[] = $response['warnings'];
            $status *= $response['status'];
            $debug[] = $response['debug'];


//            throw new Exception('отладочный стоп');
            sleep(2);

            // получаем текущую дату и время
            $date_now = Assistant::GetDateNow();

            // готовим пакет для эмуляции
            $packet_object = (object)[
                'sequenceNumber' => '1',                                                                                // номер секции, не имеет практического значения (но нужно для сбора сообщения из разных кусков)
                'sourceNode' => $sensor_net_id,                                                                         // сетевой идентификатор узла связи
                'parametersCount' => 2,                                                                                 // количество параметров передаваемых в пакете
                'timestamp' => $date_now,                                                                               // время в которое был получен пакет
                'parameters' => array(                                                                                  // блок параметров
                    (object)[
                        'id' => 100,                                                                                    // ключ параметра (бесполезен в данном случае) изначально это sensor_parameter_id. Нужен для записи на прямую в БД без поиска ключа, но в методах не используется)
                        'value' => (object)[
                            'gasReading' => $sensor_value + 1,                                                          // значение газа полученное из пакета (но его еще надо обработать,  т.к. для CO надо делить на 10000, а CH4 надо делить на 100)
                            'sensorModuleType' => '20'                                                                  // Тип датчика (какой газ измеряет CO(0) или CH4(20))
                        ]
                    ],
                    (object)[                                                                                           // блок нужен для правильного вычисления значения самого газа (эта часть по сути параметры для вышестоящего параметра) Но пример рукожопства конкретного программиста
                        'id' => 101,                                                                                    // ключ параметра (бесполезен в данном случае) изначально это sensor_parameter_id. Нужен для записи на прямую в БД без поиска ключа, но в методах не используется)
                        'value' => (object)[
                            'totalDigits' => '0',                                                                       // количество знаков перед запятой
                            'decimalDigits' => '0'                                                                      // количество знаков после запятой
                        ]
                    ],
                )
            ];
            // задаем ключ шахты
            $mine_id = '290';

//            throw new Exception("actionSetGasPackageStrata. отладочный стоп");
            // вызываем метод по обработке пакета газов страта
            $response = StrataJobController::saveEnvironmentalPacket(new \backend\controllers\EnvironmentalSensor($packet_object), $mine_id);

            $result[] = $response['Items'];
            $errors[] = $response['errors'];
            $warnings[] = $response['warnings'];
            $status *= $response['status'];
            $debug[] = $response['debug'];

            sleep(2);

            // получаем текущую дату и время
            $date_now = Assistant::GetDateNow();

            // готовим пакет для эмуляции
            $packet_object = (object)[
                'sequenceNumber' => '1',                                                                                // номер секции, не имеет практического значения (но нужно для сбора сообщения из разных кусков)
                'sourceNode' => $sensor_net_id,                                                                         // сетевой идентификатор узла связи
                'parametersCount' => 2,                                                                                 // количество параметров передаваемых в пакете
                'timestamp' => $date_now,                                                                               // время в которое был получен пакет
                'parameters' => array(                                                                                  // блок параметров
                    (object)[
                        'id' => 100,                                                                                    // ключ параметра (бесполезен в данном случае) изначально это sensor_parameter_id. Нужен для записи на прямую в БД без поиска ключа, но в методах не используется)
                        'value' => (object)[
                            'gasReading' => 0,                                                                          // Значение газа полученное из пакета (но его еще надо обработать,  т.к. для CO надо делить на 10000, а CH4 надо делить на 100)
                            'sensorModuleType' => '20'                                                                  // Тип датчика (какой газ измеряет CO(0) или CH4(20))
                        ]
                    ],
                    (object)[                                                                                           // блок нужен для правильного вычисления значения самого газа (эта часть по сути параметры для вышестоящего параметра) Но пример рукожопства конкретного программиста
                        'id' => 101,                                                                                    // ключ параметра (бесполезен в данном случае) изначально это sensor_parameter_id. Нужен для записи на прямую в БД без поиска ключа, но в методах не используется)
                        'value' => (object)[
                            'totalDigits' => '0',                                                                       // количество знаков перед запятой
                            'decimalDigits' => '0'                                                                      // количество знаков после запятой
                        ]
                    ],
                )
            ];
            // задаем ключ шахты
            $mine_id = '290';

//            throw new Exception("actionSetGasPackageStrata. отладочный стоп");
            // вызываем метод по обработке пакета газов страта
            $response = StrataJobController::saveEnvironmentalPacket(new \backend\controllers\EnvironmentalSensor($packet_object), $mine_id);

            $result[] = $response['Items'];
            $errors[] = $response['errors'];
            $warnings[] = $response['warnings'];
            $status *= $response['status'];
            $debug[] = $response['debug'];

        } catch (Throwable $exception) {
            $errors[] = 'actionSetGasPackageStrata. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionSetGasPackageMikon() - метод симуляции пакета MIKON газа CH4
     * value        - интересующее значение газа метана в нормальном понимании (%)
     * sensor_id    - ключ OPC сенсора
     * parameter_id - ключ тега
     * @package frontend\controllers
     *
     * @example 127.0.0.1/eds-front/set-gas-package-mikon?value=4&sensor_id=383588&parameter_id=1454                    - проверка концентрации метана
     * @example http://127.0.0.1/eds-front/set-gas-package-mikon?value=0,6&sensor_id=1178179&parameter_id=1396          - проверка запыленности
     * @example http://127.0.0.1/eds-front/set-gas-package-mikon?value=0,6&sensor_id=1178179&parameter_id=1396&quality=bad - проверка события отказ датчика
     *
     * @author Якимов М.Н.
     * Created date: on 01.02.2020
     */
    public function actionSetGasPackageMikon()
    {
        /**
         * select *
         * from sensor_parameter
         * join parameter on parameter_id=parameter.id
         * left join sensor_parameter_sensor on sensor_parameter.id=sensor_parameter_sensor.sensor_parameter_id
         * left join sensor_parameter s1 on s1.id=sensor_parameter_sensor.sensor_parameter_id_source
         * left join parameter p1 on s1.parameter_id=p1.id
         * where sensor_parameter.sensor_id=382844;
         */
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();
        $debug_data = array();

        try {
            // получаем данные с фронта
            $post = Assistant::GetServerMethod();                                                                         //получение данных от ajax-запрос

            // проверяем наличие на входе значения газа сенсора
            if (isset($post['value']) and $post['value'] != "") {
                $sensor_value = str_replace(',', '.', $post['value']);
            } else {
                $sensor_value = 560;
            }

            // получаем текущую дату и время
            $date_now = Assistant::GetDateNow();

            // проверяем наличие на входе ключа сенсора
            if (isset($post['sensor_id']) and $post['sensor_id'] != "") {
                $sensor_id = $post['sensor_id'];
            } else {
                $sensor_id = 115978;
            }

            // проверяем наличие на входе ключа сенсора
            if (isset($post['quality']) and $post['quality'] != "") {
                $quality = $post['quality'];
            } else {
                $quality = 'good';
            }

            // проверяем наличие на входе ключа тега
            if (isset($post['parameter_id']) and $post['parameter_id'] != "") {
                $parameter_id = $post['parameter_id'];
                $sensor_parameter = SensorParameter::findOne(['sensor_id' => $sensor_id, 'parameter_id' => $parameter_id, 'parameter_type_id' => 2]);
                if ($sensor_parameter) {
                    $sensor_parameter_id = $sensor_parameter['id'];
                } else {
                    throw new Exception("actionSetGasPackageMikon. Для сенсора $sensor_id не существует конкретного запрашиваемого параметра $parameter_id");
                }
            } else {
                $parameter_id = 670;
                $sensor_parameter_id = 621538;
            }

            // готовим пакет для эмуляции
            $opc_tags_value = (object)[
                'TagName' => 'CH10_KUSH10',                                                                             // parameter_title наименование тега
                'TagValue' => $sensor_value,                                                                            // значение конкретного тега
                'TagDate' => $date_now,                                                                                 // дата считывания значения тега (datetime)
                'TimeStamp' => '0001-01-01T00:00:00',                                                                   //
                'parameter_id' => $parameter_id,                                                                        // ключ названия тега
                'parameter_type_id' => '2',                                                                             // ключ типа параметра тега 1-справочное/2-измеренное/3-вычисленное
                'Quality' => $quality,                                                                                  // качество тега (status_id - после трансформации) badWaitingForInitialData/good
                'sensor_parameter_id' => $sensor_parameter_id,                                                          // ключ конкретного значения конкретного тега
                'sensor_id' => $sensor_id                                                                               // ключ сенсора OPC сервера
            ];
//$warnings[]=$opc_tags_value;
//            throw new Exception("actionSetGasPackageStrata. отладочный стоп");
            // вызываем метод по обработке пакета газов страта
            $response = (new OpcController(1, 1))->SetTagValue($opc_tags_value);

            $result = $response['Items'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];
            $status = $response['status'];
            $debug = $response['debug'];
            $debug_data = $response['debug_data'];

        } catch (Throwable $exception) {
            $errors[] = 'actionSetGasPackageMikon. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('debug_data' => $debug_data, 'Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // SaveSituationSolution - метод сохранения конкретного решения ситуации
    // входные данные:
    //  situation_solution: {}
    //      situation_solution_id           - ключ решения в целом
    //      situation_id                    - ключ ситуации
    //      situation_title                 - название ситуации
    //      situation_journal_id            - ключ журнала ситуации
    //      solution_date_time_start        - время начала решения ситуации
    //      regulation_time                 - регламентное время устранения ситуации
    //      status_id: null,                - последний статус (выполнена или нет)
    //      situation_journal:
    //                   {situation_journal_id}
    //                          situation_journal_situation_solution_id - ключ привязки решения и журнала ситуации
    //                          situation_journal_id                    - ключ журнала ситуации
    //      situationSolutionStatuses:      - история изменения статуса решения/устранения ситуации
    //                  {situation_solution_status_id}
    //                          situation_solution_status_id:   - ключ статуса решения/устранения ситуации в целом
    //                          responsible_worker_id:          - ключ работника изменившего карточку решения
    //                          responsible_position_id         - ключ должности работника изменившего карточку решения
    //                          status_id: null,                - статус (выполнена или нет)
    //                          date_time:                      - дата и время изменения
    //                          description:                    - описание изменения
    //      situationPlaces                 - группа мест ситуации
    //                  {place_id}
    //                          place_id: null,                         // ключ места ситуации
    //                          place_title: "",                        // название места ситуации
    //                          injunctions: {},                        // список предписаний в данном месте
    //                                {injunction_id}                       - ключ предписания
    //                                        injunction_id                        - ключ предписания
    //                                        injunction_date                      - дата предписания
    //                                        instruct_id_ip                       - ключ пункта предписания sap
    //                                        injunction_status_id                 - статус предписания
    //                                        injunction_place_id                  - ключ места на которое выписано предписание
    //                                        injunction_company_department_id     - ключ подразделения на которое выписано предписание
    //                                        correct_measure: {}                  - корректирующие мероприятия
    //                                            {correct_measure_id}                 - ключ корректирующих мероприятий
    //                                                    correct_measure_id               - ключ корректирующих мероприятий
    //                                                    operation_id                     - ключ операции
    //                                                    operation_title                  - название операции
    //                                                    unit_short_title                 - сокращенные единицы измерения операции
    //                                                    correct_measures_value           - значение/количество операции
    //                                                    correct_measure_status_id        - статус операции
    //                                                    correct_measure_date_time        - дата операции
    //                          workers: {},                            // список работников в данном месте/выработке
    //                                {worker_id}                           - ключ работника
    //                                      worker_id:                          - ключ работника
    //      regulationActions               - регламеты устранения ситуации
    // выходные данные:
    //      - типовой набор
    //      - обновленный входной объект
    // пример: http://127.0.0.1/read-manager-amicum?controller=EdsFront&method=SaveSituationSolution&subscribe=&data={"situation_solution":{}}
    public static function SaveSituationSolution($data_post = NULL)
    {
//        $data_post ='{"situation_solution":{"situation_solution_id":9,"regulation_time":10,"solution_date_time_start":"2020-06-05 12:00:00.123456","status_id":59,"situation_journal_id":8,"situation_id":2,"situation_title":"Отказ ЛУЧ-4","situation_journal":{"2":{"situation_journal_situation_solution_id":1,"situation_journal_id":2,"situation_id":2},"8":{"situation_journal_situation_solution_id":5,"situation_journal_id":8,"situation_id":2}},"solutionActions":{"1":{"situation_solution_id":1,"solution_title":"заголовок действия","solution_parent_id":null,"solution_parent_end_flag":0,"solution_number":1,"solution_type":"auto","child_action_id_positive":null,"child_action_id_negative":null,"is_changed":false,"x":null,"y":null,"responsible_position_id":3569,"responsible_worker_id":1,"status_id":58,"date_time":"2020-06-05 12:00:00.123457","regulation_time":5,"solution_date_time_start":"2020-06-05 12:00:00.123458","finish_flag_mode":"auto","expired_indicator_flag":0,"expired_indicator_mode":"auto","solutionStatuses":{"1":{"solution_card_status_id":1,"worker_id":100002,"date_time":"2020-06-05 12:00:00.123456","status_id":58,"description":"описание 5"}},"solutionOperations":{"1":{"solution_operation_id":1,"date_time":"2020-06-05 12:00:00.123456","description":"описание из операции","operation_type":"auto","status_id":58,"equipment_id":null,"worker_id":null,"position_id":null,"company_department_id":null,"solutionOperationStatuses":{"1":{"solution_operation_status_id":1,"worker_id":100002,"status_id":58,"date_time":"2020-06-05 12:00:00.123456","description":"описание 3"},"2":{"solution_operation_status_id":2,"worker_id":100002,"status_id":59,"date_time":"2020-06-05 12:00:00.123459","description":"описание 4"}}},"2":{"solution_operation_id":2,"date_time":"2020-06-05 12:00:00.823456","description":"описание из операции2","operation_type":"auto","status_id":59,"equipment_id":null,"worker_id":null,"position_id":null,"company_department_id":null,"solutionOperationStatuses":{}}}},"2":{"situation_solution_id":2,"solution_title":"заголовок действия2","solution_parent_id":1,"solution_parent_end_flag":0,"solution_number":2,"solution_type":"auto","child_action_id_positive":null,"child_action_id_negative":null,"is_changed":false,"x":null,"y":null,"responsible_position_id":3569,"responsible_worker_id":1,"status_id":59,"date_time":"2020-06-06 12:00:00.123457","regulation_time":5,"solution_date_time_start":"2020-06-05 12:00:00.123458","finish_flag_mode":"auto","expired_indicator_flag":0,"expired_indicator_mode":"auto","solutionOperations":{},"solutionStatuses":{}}},"situationSolutionStatuses":{"1":{"situation_solution_status_id":1,"responsible_position_id":3569,"responsible_worker_id":100002,"date_time":"2020-06-05 12:00:00.123456","description":"описание 2","status_id":58},"2":{"situation_solution_status_id":2,"responsible_position_id":3569,"responsible_worker_id":100002,"date_time":"2020-06-05 12:00:00.123456","description":"описание 1","status_id":59}},"situationPlaces":{"214244":{"place_id":214244,"place_title":"Ламповая","injunctions":{"6650739":{"injunction_id":"6650739","injunction_date":"2020-07-15 14:07:00","instruct_id_ip":null,"injunction_status_id":"57","injunction_place_id":"214244","injunction_company_department_id":"20000638","correct_measure":{"286754":{"correct_measure_id":"286754","operation_id":"26","operation_title":"Устранить","unit_short_title":"-","correct_measures_value":"0","correct_measure_status_id":"57","correct_measure_date_time":"2020-07-15 14:00:00"}}}},"workers":{"9876543":{"worker_id":"9876543"},"1090193":{"worker_id":"1090193"},"1091756":{"worker_id":"1091756"},"1092595":{"worker_id":"1092595"},"1092680":{"worker_id":"1092680"},"1092691":{"worker_id":"1092691"},"1092767":{"worker_id":"1092767"},"1092880":{"worker_id":"1092880"},"1093146":{"worker_id":"1093146"},"1093420":{"worker_id":"1093420"},"1093458":{"worker_id":"1093458"},"1093743":{"worker_id":"1093743"},"1093917":{"worker_id":"1093917"},"1093922":{"worker_id":"1093922"},"1094014":{"worker_id":"1094014"},"1094024":{"worker_id":"1094024"},"1094033":{"worker_id":"1094033"},"1094097":{"worker_id":"1094097"},"1094099":{"worker_id":"1094099"},"1094139":{"worker_id":"1094139"},"1094359":{"worker_id":"1094359"},"1094456":{"worker_id":"1094456"},"1094565":{"worker_id":"1094565"},"1094641":{"worker_id":"1094641"},"1094733":{"worker_id":"1094733"},"1094737":{"worker_id":"1094737"},"1094758":{"worker_id":"1094758"},"1094803":{"worker_id":"1094803"},"1094906":{"worker_id":"1094906"},"1095020":{"worker_id":"1095020"},"1095039":{"worker_id":"1095039"},"1095105":{"worker_id":"1095105"},"2020150":{"worker_id":"2020150"},"2020256":{"worker_id":"2020256"},"2020304":{"worker_id":"2020304"},"2020353":{"worker_id":"2020353"},"2020434":{"worker_id":"2020434"},"2020454":{"worker_id":"2020454"},"2020469":{"worker_id":"2020469"},"2020524":{"worker_id":"2020524"},"2020756":{"worker_id":"2020756"},"2020812":{"worker_id":"2020812"},"2020877":{"worker_id":"2020877"},"2021132":{"worker_id":"2021132"},"2021334":{"worker_id":"2021334"},"2021651":{"worker_id":"2021651"},"2021733":{"worker_id":"2021733"},"2022701":{"worker_id":"2022701"},"2022908":{"worker_id":"2022908"},"2023202":{"worker_id":"2023202"},"2023990":{"worker_id":"2023990"},"2024049":{"worker_id":"2024049"},"2025452":{"worker_id":"2025452"},"2025530":{"worker_id":"2025530"},"2030061":{"worker_id":"2030061"},"2030093":{"worker_id":"2030093"},"2030143":{"worker_id":"2030143"},"2030312":{"worker_id":"2030312"},"2030523":{"worker_id":"2030523"},"2030581":{"worker_id":"2030581"},"2031075":{"worker_id":"2031075"},"2031089":{"worker_id":"2031089"},"2031154":{"worker_id":"2031154"},"2031171":{"worker_id":"2031171"},"2031469":{"worker_id":"2031469"},"2031512":{"worker_id":"2031512"},"2031548":{"worker_id":"2031548"},"2031675":{"worker_id":"2031675"},"2031702":{"worker_id":"2031702"},"2031808":{"worker_id":"2031808"},"2031908":{"worker_id":"2031908"},"2032082":{"worker_id":"2032082"},"2032131":{"worker_id":"2032131"},"2032332":{"worker_id":"2032332"},"2032477":{"worker_id":"2032477"},"2032566":{"worker_id":"2032566"},"2032604":{"worker_id":"2032604"},"2033148":{"worker_id":"2033148"},"2033209":{"worker_id":"2033209"},"2033573":{"worker_id":"2033573"},"2034442":{"worker_id":"2034442"},"2034582":{"worker_id":"2034582"},"2034938":{"worker_id":"2034938"},"2034940":{"worker_id":"2034940"},"2035611":{"worker_id":"2035611"},"2036005":{"worker_id":"2036005"},"2036020":{"worker_id":"2036020"},"2036310":{"worker_id":"2036310"},"2036456":{"worker_id":"2036456"},"2036460":{"worker_id":"2036460"},"2036503":{"worker_id":"2036503"},"2037701":{"worker_id":"2037701"},"2039338":{"worker_id":"2039338"},"2039446":{"worker_id":"2039446"},"2039559":{"worker_id":"2039559"},"2039789":{"worker_id":"2039789"},"2039886":{"worker_id":"2039886"},"2043506":{"worker_id":"2043506"},"2050255":{"worker_id":"2050255"},"2050320":{"worker_id":"2050320"},"2050341":{"worker_id":"2050341"},"2050447":{"worker_id":"2050447"},"2050594":{"worker_id":"2050594"},"2050638":{"worker_id":"2050638"},"2050730":{"worker_id":"2050730"},"2050936":{"worker_id":"2050936"},"2050990":{"worker_id":"2050990"},"2050996":{"worker_id":"2050996"},"2050998":{"worker_id":"2050998"},"2051245":{"worker_id":"2051245"},"2051271":{"worker_id":"2051271"},"2051391":{"worker_id":"2051391"},"2051438":{"worker_id":"2051438"},"2051440":{"worker_id":"2051440"},"2051536":{"worker_id":"2051536"},"2051566":{"worker_id":"2051566"},"2051739":{"worker_id":"2051739"},"2051893":{"worker_id":"2051893"},"2051898":{"worker_id":"2051898"},"2051957":{"worker_id":"2051957"},"2051971":{"worker_id":"2051971"},"2052033":{"worker_id":"2052033"},"2052137":{"worker_id":"2052137"},"2052227":{"worker_id":"2052227"},"2052274":{"worker_id":"2052274"},"2052321":{"worker_id":"2052321"},"2052366":{"worker_id":"2052366"},"2052381":{"worker_id":"2052381"},"2052781":{"worker_id":"2052781"},"2052878":{"worker_id":"2052878"},"2053065":{"worker_id":"2053065"},"2070034":{"worker_id":"2070034"},"2082004":{"worker_id":"2082004"},"2082572":{"worker_id":"2082572"},"2086005":{"worker_id":"2086005"},"2086027":{"worker_id":"2086027"},"2086087":{"worker_id":"2086087"},"2086089":{"worker_id":"2086089"},"2086110":{"worker_id":"2086110"},"2086190":{"worker_id":"2086190"},"2087240":{"worker_id":"2087240"},"2091655":{"worker_id":"2091655"},"2190909":{"worker_id":"2190909"},"2221647":{"worker_id":"2221647"},"2223125":{"worker_id":"2223125"},"2904056":{"worker_id":"2904056"},"2904118":{"worker_id":"2904118"},"2904631":{"worker_id":"2904631"},"2904865":{"worker_id":"2904865"},"2905888":{"worker_id":"2905888"},"2906328":{"worker_id":"2906328"},"2906351":{"worker_id":"2906351"},"2906368":{"worker_id":"2906368"},"2906559":{"worker_id":"2906559"},"2906560":{"worker_id":"2906560"},"2906789":{"worker_id":"2906789"},"2907296":{"worker_id":"2907296"},"2907343":{"worker_id":"2907343"},"2907595":{"worker_id":"2907595"},"2908377":{"worker_id":"2908377"},"2908589":{"worker_id":"2908589"},"2908855":{"worker_id":"2908855"},"2908861":{"worker_id":"2908861"},"2909069":{"worker_id":"2909069"},"2909103":{"worker_id":"2909103"},"2909298":{"worker_id":"2909298"},"2909309":{"worker_id":"2909309"},"2909361":{"worker_id":"2909361"},"2909391":{"worker_id":"2909391"},"2909432":{"worker_id":"2909432"},"2910064":{"worker_id":"2910064"},"2910182":{"worker_id":"2910182"},"2910204":{"worker_id":"2910204"},"2910206":{"worker_id":"2910206"},"2910409":{"worker_id":"2910409"},"2910422":{"worker_id":"2910422"},"2910605":{"worker_id":"2910605"},"2910629":{"worker_id":"2910629"},"2910706":{"worker_id":"2910706"},"2911110":{"worker_id":"2911110"},"2911123":{"worker_id":"2911123"},"2911211":{"worker_id":"2911211"},"2911294":{"worker_id":"2911294"},"2911509":{"worker_id":"2911509"},"2911579":{"worker_id":"2911579"},"2911663":{"worker_id":"2911663"},"2911772":{"worker_id":"2911772"},"2912098":{"worker_id":"2912098"},"2912175":{"worker_id":"2912175"},"2912179":{"worker_id":"2912179"},"2912195":{"worker_id":"2912195"},"2912279":{"worker_id":"2912279"},"2912299":{"worker_id":"2912299"},"2912300":{"worker_id":"2912300"},"2912489":{"worker_id":"2912489"},"2912586":{"worker_id":"2912586"},"2912589":{"worker_id":"2912589"},"2912594":{"worker_id":"2912594"},"2912596":{"worker_id":"2912596"},"2912652":{"worker_id":"2912652"},"2912666":{"worker_id":"2912666"},"2912673":{"worker_id":"2912673"},"2912695":{"worker_id":"2912695"},"2912711":{"worker_id":"2912711"},"2912714":{"worker_id":"2912714"},"2912715":{"worker_id":"2912715"},"2912962":{"worker_id":"2912962"},"2913190":{"worker_id":"2913190"},"2913219":{"worker_id":"2913219"},"2913271":{"worker_id":"2913271"},"2913272":{"worker_id":"2913272"},"2913285":{"worker_id":"2913285"},"2913362":{"worker_id":"2913362"},"2913416":{"worker_id":"2913416"},"2913545":{"worker_id":"2913545"},"2913613":{"worker_id":"2913613"},"2913615":{"worker_id":"2913615"},"2913659":{"worker_id":"2913659"},"2913697":{"worker_id":"2913697"},"2913744":{"worker_id":"2913744"},"2913845":{"worker_id":"2913845"},"2913882":{"worker_id":"2913882"},"2913950":{"worker_id":"2913950"},"2913981":{"worker_id":"2913981"},"2913989":{"worker_id":"2913989"},"2914000":{"worker_id":"2914000"},"2914019":{"worker_id":"2914019"},"2914050":{"worker_id":"2914050"},"2914098":{"worker_id":"2914098"},"2914159":{"worker_id":"2914159"},"2914161":{"worker_id":"2914161"},"2914194":{"worker_id":"2914194"},"2914231":{"worker_id":"2914231"},"2914280":{"worker_id":"2914280"},"2914311":{"worker_id":"2914311"},"2914319":{"worker_id":"2914319"},"2914342":{"worker_id":"2914342"},"2914343":{"worker_id":"2914343"},"2914350":{"worker_id":"2914350"},"2914357":{"worker_id":"2914357"},"2914373":{"worker_id":"2914373"},"2914413":{"worker_id":"2914413"},"2914594":{"worker_id":"2914594"},"2914656":{"worker_id":"2914656"},"2914726":{"worker_id":"2914726"},"2914822":{"worker_id":"2914822"},"2915184":{"worker_id":"2915184"},"2915648":{"worker_id":"2915648"},"2915649":{"worker_id":"2915649"},"2915801":{"worker_id":"2915801"},"2915861":{"worker_id":"2915861"},"2915863":{"worker_id":"2915863"},"2916015":{"worker_id":"2916015"},"2916059":{"worker_id":"2916059"},"2916064":{"worker_id":"2916064"},"2916067":{"worker_id":"2916067"},"2916092":{"worker_id":"2916092"},"2916104":{"worker_id":"2916104"},"2916177":{"worker_id":"2916177"},"2916178":{"worker_id":"2916178"},"2916250":{"worker_id":"2916250"},"2916304":{"worker_id":"2916304"},"2916501":{"worker_id":"2916501"},"2916634":{"worker_id":"2916634"},"2916668":{"worker_id":"2916668"},"2916687":{"worker_id":"2916687"},"2916704":{"worker_id":"2916704"},"2916732":{"worker_id":"2916732"},"2916772":{"worker_id":"2916772"},"2916921":{"worker_id":"2916921"},"70004784":{"worker_id":"70004784"}}}},"regulationActions":{"19":{"regulation_id":19,"regulation_title":"кывфв","situation_id":2,"regulation_actions":{"359":{"action_id":359,"action_parent_id":0,"action_title":"фвфывыфвфыв","action_parent_end_flag":1,"action_number":0,"action_type":"positive","x":553,"y":100,"responsible_position_id":5749,"regulation_time":11,"regulation_exchange_id":null,"finish_flag_mode":"auto","expired_indicator_flag":0,"expired_indicator_mode":"auto","go_to_another_regulation_flag":1,"go_to_another_regulation_mode":"auto","child_action_id_positive":360,"child_action_id_negative":null,"plan_new_action_flag":0,"operations":{"28135":{"action_operation_id":28135,"operation_id":138,"operation_type":"manual","workers":{},"equipments":{}}}},"360":{"action_id":360,"action_parent_id":359,"action_title":"111","action_parent_end_flag":0,"action_number":1,"action_type":"positive","x":221,"y":186,"responsible_position_id":5751,"regulation_time":10,"regulation_exchange_id":null,"finish_flag_mode":"auto","expired_indicator_flag":0,"expired_indicator_mode":"auto","go_to_another_regulation_flag":1,"go_to_another_regulation_mode":"auto","child_action_id_positive":null,"child_action_id_negative":null,"plan_new_action_flag":0,"operations":{"28136":{"action_operation_id":28136,"operation_id":141,"operation_type":"manual","equipments":{"14181":{"action_operation_equipment_id":14181,"equipment_id":355796},"14182":{"action_operation_equipment_id":14182,"equipment_id":1179955}},"workers":{"4778":{"action_operation_position_id":4778,"position_id":6085,"company_department_id":null},"4779":{"action_operation_position_id":4779,"position_id":5241,"company_department_id":null},"4780":{"action_operation_position_id":4780,"position_id":6085,"company_department_id":4029938},"4781":{"action_operation_position_id":4781,"position_id":5241,"company_department_id":null}}}}}}},"23":{"regulation_id":23,"regulation_title":"Нахождение шахтера в запретной зоне","situation_id":2,"regulation_actions":{"369":{"action_id":369,"action_parent_id":0,"action_title":"Средствами системы позиционирования горный диспетчер идентифицирует шахтера","action_parent_end_flag":1,"action_number":0,"action_type":"positive","x":642,"y":100,"responsible_position_id":2162992500,"regulation_time":10,"regulation_exchange_id":null,"finish_flag_mode":"auto","expired_indicator_flag":0,"expired_indicator_mode":"auto","go_to_another_regulation_flag":1,"go_to_another_regulation_mode":"auto","child_action_id_positive":370,"child_action_id_negative":null,"plan_new_action_flag":0,"operations":{"28145":{"action_operation_id":28145,"operation_id":201697,"operation_type":"manual","workers":{},"equipments":{}}}},"370":{"action_id":370,"action_parent_id":369,"action_title":"ГД формирует средствами системы позиционирования текстовое сообщение с указанием нахождения в запретной зоне","action_parent_end_flag":0,"action_number":1,"action_type":"positive","x":112,"y":222.233,"responsible_position_id":2162992500,"regulation_time":2,"regulation_exchange_id":null,"finish_flag_mode":"auto","expired_indicator_flag":0,"expired_indicator_mode":"auto","go_to_another_regulation_flag":1,"go_to_another_regulation_mode":"auto","child_action_id_positive":371,"child_action_id_negative":null,"plan_new_action_flag":0,"operations":{"28146":{"action_operation_id":28146,"operation_id":201697,"operation_type":"manual","workers":{},"equipments":{}}}},"371":{"action_id":371,"action_parent_id":370,"action_title":"Внести данные в журнал","action_parent_end_flag":0,"action_number":2,"action_type":"positive","x":112,"y":314.467,"responsible_position_id":2162992500,"regulation_time":-1,"regulation_exchange_id":null,"finish_flag_mode":"auto","expired_indicator_flag":0,"expired_indicator_mode":"auto","go_to_another_regulation_flag":1,"go_to_another_regulation_mode":"auto","child_action_id_positive":372,"child_action_id_negative":null,"plan_new_action_flag":0,"operations":{"28147":{"action_operation_id":28147,"operation_id":201697,"operation_type":"manual","workers":{},"equipments":{}}}},"372":{"action_id":372,"action_parent_id":371,"action_title":"Направить сообщение РВН на смене","action_parent_end_flag":0,"action_number":3,"action_type":"positive","x":112,"y":364.467,"responsible_position_id":2162992500,"regulation_time":3,"regulation_exchange_id":null,"finish_flag_mode":"auto","expired_indicator_flag":0,"expired_indicator_mode":"auto","go_to_another_regulation_flag":1,"go_to_another_regulation_mode":"auto","child_action_id_positive":373,"child_action_id_negative":null,"plan_new_action_flag":0,"operations":{"28148":{"action_operation_id":28148,"operation_id":201697,"operation_type":"manual","workers":{},"equipments":{}}}},"373":{"action_id":373,"action_parent_id":372,"action_title":"Контролировать устранение События","action_parent_end_flag":0,"action_number":4,"action_type":"positive","x":112,"y":414.467,"responsible_position_id":4571,"regulation_time":360,"regulation_exchange_id":null,"finish_flag_mode":"auto","expired_indicator_flag":0,"expired_indicator_mode":"auto","go_to_another_regulation_flag":1,"go_to_another_regulation_mode":"auto","child_action_id_positive":374,"child_action_id_negative":null,"plan_new_action_flag":0,"operations":{"28149":{"action_operation_id":28149,"operation_id":201697,"operation_type":"manual","workers":{},"equipments":{}}}},"374":{"action_id":374,"action_parent_id":373,"action_title":"В случае если Событие не устранено, информируем руководство участка для принятия мер","action_parent_end_flag":0,"action_number":5,"action_type":"positive","x":112,"y":464.467,"responsible_position_id":4559,"regulation_time":-1,"regulation_exchange_id":null,"finish_flag_mode":"auto","expired_indicator_flag":0,"expired_indicator_mode":"auto","go_to_another_regulation_flag":1,"go_to_another_regulation_mode":"auto","child_action_id_positive":null,"child_action_id_negative":null,"plan_new_action_flag":0,"operations":{"28150":{"action_operation_id":28150,"operation_id":201697,"operation_type":"manual","workers":{},"equipments":{}}}}}}}},"status":1,"errors":[[],[],[]],"warnings":["GetSituationSolution.  Начало выполнения метода","GetSituationSolution. Данные успешно переданы","GetSituationSolution. Входной массив данных{\"situation_solution_id\":1}","GetSituationSolution. Декодировал входные параметры","GetSituationSolution. Данные с фронта получены",["GetInjunctionByPlace. Получение информации по предписаниям"],["GetWorkersByPlace. Получение информации по предписаниям"],["GetRegulationActionsListBySituations. Начало метода","GetRegulationActionsListBySituations. Конец метода"],"GetSituationSolution.  Окончание выполнения метода"],"debug":[{"Items":true,"status":1,"errors":[],"warnings":["checkPermissionUser Начало выполнения метода","checkPermissionUser. Данные успешно переданы","checkPermissionUser Окончание выполнения метода"]},96,"все ок, права есть",{"description":["GetSituationSolution.  Начало выполнения метода","GetSituationSolution.  Окончание выполнения метода"],"memory_peak":["6966.6484375 GetSituationSolution.  Начало выполнения метода","8915.6875 GetSituationSolution.  Окончание выполнения метода"],"memory":["6756.375 GetSituationSolution.  Начало выполнения метода","8860.453125 GetSituationSolution.  Окончание выполнения метода"],"durationSummary":["0.000159 GetSituationSolution.  Начало выполнения метода","9.92585 GetSituationSolution.  Окончание выполнения метода"],"durationCurrent":["0.000163 GetSituationSolution.  Начало выполнения метода","9.925688 GetSituationSolution.  Окончание выполнения метода"],"number_row_affected":["Кол-во записей: 0шт. GetSituationSolution.  GetSituationSolution.  Начало выполнения метода","Кол-во записей: 0шт. GetSituationSolution.  GetSituationSolution.  Окончание выполнения метода"]}]}';
        // Стартовая отладочная информация
        $method_name = 'SaveSituationSolution. ';                                                                       // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                       // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                     // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                   // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                           // время начала выполнения метода
        $date_time_debug_end = null;

        //базовые параметры скрипта
        $errors = array();
        $save_solution = (object)array();
        $status = 1;
        $warnings = array();
        $count_add = 0;
        $count_add_full = 0;
        $count_update = 0;

        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
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

            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . 'Не переданы входные параметры');
            }
            $warnings[] = $method_name . 'Данные успешно переданы';
            $warnings[] = $method_name . 'Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . 'Декодировал входные параметры';
            if (!property_exists($post_dec, 'situation_solution'))                                                     // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . 'Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . 'Данные с фронта получены';

            $save_solution = $post_dec->situation_solution;

            // сохраняем главное решение
            $solution = SituationSolution::findOne(['id' => $save_solution->situation_solution_id]);
            if (!$solution) {
                $solution = new SituationSolution();
            }

            $solution->regulation_time = $save_solution->regulation_time;
            $solution->solution_date_time_start = $save_solution->solution_date_time_start;
            if ($solution->save()) {
                $situation_solution_id = $solution->id;
                $save_solution->situation_solution_id = $situation_solution_id;
            } else {
                $errors[] = $solution->errors;
                throw new Exception($method_name . '. Ошибка при сохранении главного решения SituationSolution');
            }

            $date_time = Assistant::GetDateNow();
            // сохраняем решение в БД в формате JSON
            $solution_hystory = new SituationSolutionHystory();
            $solution_hystory->situation_solution_id = $situation_solution_id;
            $solution_hystory->situation_solution_json = $data_post;
            $solution_hystory->date_time = $date_time;
            if (!$solution_hystory->save()) {
                $errors[] = $solution_hystory->errors;
                throw new Exception($method_name . '. Ошибка при сохранении json решения SituationSolutionHystory');
            }

            //сохраняем новый статус решения
            $session = Yii::$app->session;
            $solution_status = new SituationSolutionStatus();
            $solution_status->situation_solution_id = $situation_solution_id;
            $solution_status->responsible_position_id = $session['position_id'];
            $solution_status->responsible_worker_id = $session['worker_id'];
            $solution_status->status_id = $save_solution->status_id;
            $solution_status->date_time = $date_time;
            $solution_status->description = "";
            if ($solution_status->save()) {
                $situation_solution_status_id = $solution_status->id;
            } else {
                $errors[] = $solution_status->errors;
                throw new Exception($method_name . '. Ошибка при сохранении статуса решения SituationSolutionStatus');
            }

            $save_solution->situationSolutionStatuses->{$situation_solution_status_id} = (object)[
                'situation_solution_status_id' => $situation_solution_status_id,
                'responsible_position_id' => $session['position_id'],
                'responsible_worker_id' => $session['worker_id'],
                'date_time' => $date_time,
                'description' => "",
                'status_id' => $save_solution->status_id
            ];

            // сохраняем связь журнала ситуаций и решений
            $situation_journal_situation_solutions = SituationJournalSituationSolution::find()->where(['situation_solution_id' => $situation_solution_id])->indexBy('situation_journal_id')->asArray()->all();
            foreach ($save_solution->situation_journal as $item) {
                $situation_ids[] = $item->situation_id;
                $situation_journal_id = $item->situation_journal_id;

                if (!isset($situation_journal_situation_solutions[$situation_journal_id])) {
                    $situation_journal_situation_solution = new SituationJournalSituationSolution();

                    $situation_journal_situation_solution->situation_solution_id = $situation_solution_id;
                    $situation_journal_situation_solution->situation_journal_id = $situation_journal_id;
                    if ($situation_journal_situation_solution->save()) {
                        $situation_journal_situation_solution_id = $situation_journal_situation_solution->id;
                        $item->situation_journal_situation_solution_id = $situation_journal_situation_solution_id;
                    } else {
                        $errors[] = $situation_journal_situation_solution->errors;
                        throw new Exception($method_name . '. Ошибка при сохранении главного решения SituationJournalSituationSolution');
                    }
                }
            }

            // формируем массив ключей мест для поиска предписаний и людей в данном месте
            if (isset($save_solution->situationPlaces)) {
                foreach ($save_solution->situationPlaces as $place) {
                    if (!isset($place_ids)) {
                        $place_ids = $place->place_id;
                    } else {
                        $place_ids .= ", " . $place->place_id;
                    }
                }
                $date_time = Assistant::GetDateNow();

                // получаем список действующих предписаний в данных местах
                $response = self::GetInjunctionByPlace($place_ids, $date_time);
                if ($response['status'] == 1) {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    $injuncions_by_places = $response['Items'];
                    foreach ($injuncions_by_places as $key => $place_id) {
                        $save_solution->situationPlaces->{$key}->injunctions = $place_id;
                    }
                } else {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new Exception($method_name . "ошибка получения списка предписаний по местам");
                }

                // считаем людей в данном месте на текущий момент времени
                $response = self::GetWorkersByPlace($place_ids);
                if ($response['status'] == 1) {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    $workers_by_places = $response['Items'];
                    foreach ($workers_by_places as $key => $place_id) {
                        $save_solution->situationPlaces->{$key}->workers = $place_id;
                    }
                } else {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new Exception($method_name . "ошибка получения списка работников по местам");
                }
            }

            if (isset($situation_ids)) {
                $response = HandbookRegulationController::GetRegulationActionsListBySituations($situation_ids);
                if ($response['status'] == 1) {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    $save_solution->regulationActions = $response['Items'];
                } else {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new Exception($method_name . "ошибка получения списка регламентов по ситуациям");
                }
            } else {
                $result['regulationActions'] = (object)array();
            }

            $response = WebsocketController::SendMessageToWebSocket('situationEliminationSaveSituationSolution', $save_solution);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка отправки данных на вебсокет');
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
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $ex) {
            $errors[] = $method_name . 'Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => $save_solution, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    // GetSituationSolution - метод получения активного (значит его еще делают) конкретного решения ситуациипо его ключу
    // отличие от архивного в способе получения информации по предписаниям и количеству людей до конкретной даты или по текущее время
    // входные данные:
    //      situation_solution_id           - ключ решения ситуации
    // выходные данные:
    //      situation_solution_id           - ключ решения в целом
    //      situation_id                    - ключ ситуации
    //      situation_title                 - название ситуации
    //      situation_journal_id            - ключ журнала ситуации
    //      solution_date_time_start        - время начала решения ситуации
    //      regulation_time                 - регламентное время устранения ситуации
    //      status_id: null,                - последний статус (выполнено или нет решение)
    //      situation_journal:
    //                   {situation_journal_id}
    //                          situation_journal_situation_solution_id - ключ привязки решения и журнала ситуации
    //                          situation_journal_id                    - ключ журнала ситуации
    //                          situation_id                            - ключ ситуации
    //      solutionActions:                - карточки решения ситуации
    //              {}
    //                          solution_id: null,                      // id карты решения/устранения
    //                          solution_title: '',                     // название решения/устранения
    //                          solution_parent_id: null,               // id карточки родителя решения/устранения
    //                          solution_parent_end_flag: 0,            // флаг первого/последнего действия (2 - последнее действие, 1 - первое действие, 0 - обычное действие)
    //                          solution_number: 0,                     // номер карточки по порядку в решении
    //                          solution_type: 'positive',              // тип карточки решения - действие в срок (карточка положительная), действие просрочено, карточка отрицательная
    //                          child_action_id_positive: null,         // ключ действия при положительном исходе по решению карточки
    //                          child_action_id_negative: null,         // ключ действия при отрицательном исходе по решению карточки
    //                          x: 0,                                   // координата абсциссы карточки решения/устранения
    //                          y: 0,                                   // координата ординаты карточки решения/устранения
    //                          responsible_position_id: null,          // ключ должности последнего сменившего статус
    //                          responsible_worker_id: null,            // ключ последнего ответственного сменившего статус (id работника - табельный)
    //                          responsible_worker_full_name: null,     // ФИО последнего ответственного сменившего статус
    //                          status_id: null,                        // ключ последнего статуса
    //                          date_time: "",                          // дата и время последнего изменения статуса
    //                          regulation_time: '-1',                  // регламентное время выполнения действия (-1 до устранения события/ любое целое цисло)
    //                          solution_date_time_start: "",           // начало выполенния действия (используется для определения остатка на устранение решения)
    //                          finish_flag_mode: 'auto',               // тип действия завершения (auto - автоматическое действие, manual - ручное)
    //                          expired_indicator_flag: 0,              // флаг установки индикатора просрочки действия
    //                          expired_indicator_mode: 'auto',         // тип действия просрочки (auto - автоматическое действие, manual - ручное)
    //                          description: "",                        // комментарий ответственного при изменении/неизменении статуса
    //                          solutionStatuses: {},                   // история изменения статуса решения/устранения ситуации
    //                                  {solution_card_status_id}
    //                                      solution_card_status_id:        - ключ статуса карточки решения
    //                                      worker_id:                      - ключ работника изменившего карточку решения
    //                                      status_id: null,                - статус карточки (выполнена или нет)
    //                                      date_time:                      - дата и время изменения
    //                                      description:                    - описание изменения
    //                          solutionOperations: {},                 // список операций выполняемых/выполненных во время устранения ситуации
    //                                  {solution_operation_id}
    //                                              solution_operation_id: null,            // id привязки операции к решению/устранению ситуации
    //                                              operation_id: null,                     // id операции
    //                                              operation_title: null,                  // название операции
    //                                              description: "",                        // описание операции
    //                                              operation_type: 'manual',               // тип действия (manual - ручное, auto - автоматическое)
    //                                              on_shift: 1,                            // оповещать работника на смене или первого на участке с такой должностью
    //                                              status_id: null,                        // статус операции (выполнена или нет)
    //                                              equipment_id: null,                     // ключ оборудования
    //                                              equipment_title: null,                  // название оборудования
    //                                              worker_id: null,                        // ключ работника
    //                                              worker_full_name: null,                 // ФИО работника с табельным номером
    //                                              position_id: null,                      // ключ должности
    //                                              position_title: null,                   // название должности
    //                                              company_department_id: null,            // ключ департамента работника
    //                                              company_title: null,                    // название компании
    //                                              date_time: "",                          // дата и время последнего изменения статуса
    //                                              solutionOperationStatuses: {}           // статусы изменения операции решения
    //                                                      {solution_operation_status_id}
    //                                                              solution_operation_status_id        - ключ статуса изменения операции решения
    //                                                              worker_id:                          - ключ работника изменившего операцию решения
    //                                                              status_id: null,                    - статус операции (выполнена или нет)
    //                                                              date_time:                          - дата и время изменения
    //                                                              description:                        - описание изменения
    //      situationSolutionStatuses:      - история изменения статуса решения/устранения ситуации
    //                  {situation_solution_status_id}
    //                          situation_solution_status_id:   - ключ статуса решения/устранения ситуации в целом
    //                          responsible_worker_id:          - ключ работника изменившего карточку решения
    //                          responsible_position_id         - ключ должности работника изменившего карточку решения
    //                          status_id: null,                - статус (выполнена или нет)
    //                          date_time:                      - дата и время изменения
    //                          description:                    - описание изменения
    //      situationPlaces                 - группа мест ситуации
    //                  {place_id}
    //                          place_id: null,                         // ключ места ситуации
    //                          place_title: "",                        // название места ситуации
    //                          injunctions: {},                        // список предписаний в данном месте
    //                                {injunction_id}              - ключ предписания
    //                                        injunction_id                        - ключ предписания
    //                                        injunction_date                      - дата предписания
    //                                        instruct_id_ip                       - ключ пункта предписания sap
    //                                        injunction_status_id                 - статус предписания
    //                                        injunction_place_id                  - ключ места на которое выписано предписание
    //                                        injunction_company_department_id     - ключ подразделения на которое выписано предписание
    //                                        correct_measure: {}                  - корректирующие мероприятия
    //                                            {correct_measure_id}                 - ключ корректирующих мероприятий
    //                                                    correct_measure_id               - ключ корректирующих мероприятий
    //                                                    operation_id                     - ключ операции
    //                                                    operation_title                  - название операции
    //                                                    unit_short_title                 - сокращенные единицы измерения операции
    //                                                    correct_measures_value           - значение/количество операции
    //                                                    correct_measure_status_id        - статус операции
    //                                                    correct_measure_date_time        - дата операции
    //                          workers: {},                            // список работников в данном месте/выработке
    //                                {worker_id}                           - ключ работника
    //                                      worker_id:                          - ключ работника
    //      regulationActions               - регламеты устранения ситуации
    // пример: http://127.0.0.1/read-manager-amicum?controller=EdsFront&method=GetSituationSolution&subscribe=&data={"situation_solution_id":1}
    public static function GetSituationSolution($data_post = NULL)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("GetSituationSolution");

        try {
            $log->addLog("Начало выполнения метода");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            if (!property_exists($post_dec, 'situation_solution_id'))                                                     // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('Переданы некорректные входные параметры');
            }
            $log->addLog("Данные с фронта получены");

            $situation_solution_id = $post_dec->situation_solution_id;
            $solutions = SituationSolution::find()
                ->joinWith('solutionCards.solutionOperations.solutionOperationStatuses')                            // статусы операций
                ->joinWith('solutionCards.solutionOperations.operation')                                            // справочник операций
                ->joinWith('solutionCards.solutionOperations.equipment')                                            // справочник оборудования
                ->joinWith('solutionCards.solutionOperations.position')                                             // справочник должностей
                ->joinWith('solutionCards.solutionOperations.companyDepartment.company')                            // справочник компаний
                ->joinWith('solutionCards.solutionOperations.worker.employee')                                      // справочник работников для операций
                ->joinWith('solutionCards.solutionCardStatuses')                                                    // статусы карточек
                ->joinWith('solutionCards.worker.employee1')                                                       // справочник работников для карточек
                ->joinWith('situationSolutionStatuses.status')                                                      // справочник статусов
                ->joinWith('situationJournalSituationSolutions.situationJournal.situation')                         // данные по ситуации
                ->joinWith('situationJournalSituationSolutions.situationJournal.situationJournalZones.edge.place')  // зона ситуации
                ->where(['situation_solution.id' => $situation_solution_id])
                ->all();
            $log->addLog("Данные с БД получены");

            // заполняем решение
            if ($solutions) {
                foreach ($solutions as $solution) {
                    $result['situation_solution_id'] = $solution['id'];
                    $result['regulation_time'] = $solution['regulation_time'];
                    $result['solution_date_time_start'] = $solution['solution_date_time_start'];
                    $result['status_id'] = null;
                    $result['status_title'] = "";
                    if ($solution['situationJournalSituationSolutions']) {
                        foreach ($solution['situationJournalSituationSolutions'] as $item) {
                            $result['situation_journal_id'] = $item['situation_journal_id'];

                            $result['situation_id'] = $item['situationJournal']['situation_id'];
                            $result['situation_title'] = $item['situationJournal']['situation']['title'];
                            $situation_journal_ids[] = $item['situation_journal_id'];                                     // списко ключей фактических ситуаций
                            $situation_ids[] = $item['situationJournal']['situation_id'];                                 // список ключей ситуаций

                            $result['situation_journal'][$item['situation_journal_id']]['situation_journal_situation_solution_id'] = $item['id'];
                            $result['situation_journal'][$item['situation_journal_id']]['situation_journal_id'] = $item['situation_journal_id'];
                            $result['situation_journal'][$item['situation_journal_id']]['situation_id'] = $item['situationJournal']['situation_id'];
                        }
                    } else {
                        $result['situation_journal_id'] = null;
                        $result['situation_id'] = null;
                        $result['situation_title'] = "";
                        $result['situation_journal'] = (object)array();
                    }

                    // заполняем карточки решений
                    if ($solution['solutionCards']) {
                        foreach ($solution['solutionCards'] as $solution_card) {
                            $result['solutionActions'][$solution_card['id']]['solution_id'] = $solution_card['id'];
                            $result['solutionActions'][$solution_card['id']]['solution_title'] = $solution_card['title'];
                            $result['solutionActions'][$solution_card['id']]['solution_parent_id'] = $solution_card['solution_parent_id'];
                            $result['solutionActions'][$solution_card['id']]['solution_parent_end_flag'] = $solution_card['solution_parent_end_flag'];
                            $result['solutionActions'][$solution_card['id']]['solution_number'] = $solution_card['solution_number'];
                            $result['solutionActions'][$solution_card['id']]['solution_type'] = $solution_card['solution_type'];
                            $result['solutionActions'][$solution_card['id']]['child_action_id_positive'] = $solution_card['child_action_id_positive'];
                            $result['solutionActions'][$solution_card['id']]['child_action_id_negative'] = $solution_card['child_action_id_negative'];
                            $result['solutionActions'][$solution_card['id']]['is_changed'] = false;
                            $result['solutionActions'][$solution_card['id']]['x'] = $solution_card['x'];
                            $result['solutionActions'][$solution_card['id']]['y'] = $solution_card['y'];
                            $result['solutionActions'][$solution_card['id']]['responsible_position_id'] = $solution_card['responsible_position_id'];
                            $result['solutionActions'][$solution_card['id']]['responsible_worker_id'] = $solution_card['responsible_worker_id'];
                            $result['solutionActions'][$solution_card['id']]['responsible_worker_full_name'] = $solution_card['worker'] ?
                                ($solution_card['worker']['employee']['last_name'] . " " . $solution_card['worker']['employee']['first_name'] . " " . $solution_card['worker']['employee']['patronymic']) :
                                "";
                            $result['solutionActions'][$solution_card['id']]['status_id'] = $solution_card['status_id'];
                            $result['solutionActions'][$solution_card['id']]['date_time'] = $solution_card['date_time'];
                            $result['solutionActions'][$solution_card['id']]['regulation_time'] = $solution_card['regulation_time'];
                            $result['solutionActions'][$solution_card['id']]['solution_date_time_start'] = $solution_card['solution_date_time_start'];
                            $result['solutionActions'][$solution_card['id']]['finish_flag_mode'] = $solution_card['finish_flag_mode'];
                            $result['solutionActions'][$solution_card['id']]['expired_indicator_flag'] = $solution_card['expired_indicator_flag'];
                            $result['solutionActions'][$solution_card['id']]['expired_indicator_mode'] = $solution_card['expired_indicator_mode'];
                            $result['solutionActions'][$solution_card['id']]['description'] = $solution_card['description'];

                            // заполняем статусы карточек решений
                            if ($solution_card['solutionCardStatuses']) {
                                foreach ($solution_card['solutionCardStatuses'] as $solution_card_status) {
                                    $result['solutionActions'][$solution_card['id']]['solutionStatuses'][$solution_card_status['id']]['solution_card_status_id'] = $solution_card_status['id'];
                                    $result['solutionActions'][$solution_card['id']]['solutionStatuses'][$solution_card_status['id']]['worker_id'] = $solution_card_status['worker_id'];
                                    $result['solutionActions'][$solution_card['id']]['solutionStatuses'][$solution_card_status['id']]['date_time'] = $solution_card_status['date_time'];
                                    $result['solutionActions'][$solution_card['id']]['solutionStatuses'][$solution_card_status['id']]['status_id'] = $solution_card_status['status_id'];
                                    $result['solutionActions'][$solution_card['id']]['solutionStatuses'][$solution_card_status['id']]['description'] = $solution_card_status['description'];
                                }
                            }

                            // заполняем у карточек решений их операции
                            if ($solution_card['solutionOperations']) {
                                foreach ($solution_card['solutionOperations'] as $solution_operation) {
                                    $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['solution_operation_id'] = $solution_operation['id'];
                                    $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['date_time'] = $solution_operation['date_time'];
                                    $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['description'] = $solution_operation['description'];
                                    $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['operation_id'] = $solution_operation['operation_id'];
                                    $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['operation_title'] = $solution_operation['operation'] ? $solution_operation['operation']['title'] : "";
                                    $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['operation_type'] = $solution_operation['operation_type'];
                                    $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['on_shift'] = $solution_operation['on_shift'];
                                    $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['status_id'] = $solution_operation['status_id'];
                                    $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['equipment_id'] = $solution_operation['equipment_id'];
                                    $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['equipment_title'] = $solution_operation['equipment'] ? $solution_operation['equipment']['title'] : "";
                                    $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['worker_id'] = $solution_operation['worker_id'];
                                    $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['worker_full_name'] = $solution_operation['worker'] ?
                                        ($solution_operation['worker']['employee']['last_name'] . " " . $solution_operation['worker']['employee']['first_name'] . " " . $solution_operation['worker']['employee']['patronymic'] . " | " . $solution_operation['worker']['tabel_number']) :
                                        "";
                                    $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['position_id'] = $solution_operation['position_id'];
                                    $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['position_title'] = $solution_operation['position'] ? $solution_operation['position']['title'] : "";
                                    $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['company_department_id'] = $solution_operation['company_department_id'];
                                    $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['company_title'] = $solution_operation['companyDepartment'] ? $solution_operation['companyDepartment']['company']['title'] : "";
                                    $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['date_time'] = $solution_operation['date_time'];
                                    if ($solution_operation['solutionOperationStatuses']) {
                                        foreach ($solution_operation['solutionOperationStatuses'] as $solution_operation_status) {
                                            $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['solutionOperationStatuses'][$solution_operation_status['id']]['solution_operation_status_id'] = $solution_operation_status['id'];
                                            $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['solutionOperationStatuses'][$solution_operation_status['id']]['worker_id'] = $solution_operation_status['worker_id'];
                                            $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['solutionOperationStatuses'][$solution_operation_status['id']]['status_id'] = $solution_operation_status['status_id'];
                                            $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['solutionOperationStatuses'][$solution_operation_status['id']]['date_time'] = $solution_operation_status['date_time'];
                                            $result['solutionActions'][$solution_card['id']]['solutionOperations'][$solution_operation['id']]['solutionOperationStatuses'][$solution_operation_status['id']]['description'] = $solution_operation_status['description'];
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        $result['solutionActions'] = (object)array();
                    }

                    // заполняем статусы решения/устранения ситуации
                    if ($solution['situationSolutionStatuses']) {
                        foreach ($solution['situationSolutionStatuses'] as $situation_solution_status) {
                            $result['situationSolutionStatuses'][$situation_solution_status['id']]['situation_solution_status_id'] = $situation_solution_status['id'];
                            $result['situationSolutionStatuses'][$situation_solution_status['id']]['responsible_position_id'] = $situation_solution_status['responsible_position_id'];
                            $result['situationSolutionStatuses'][$situation_solution_status['id']]['responsible_worker_id'] = $situation_solution_status['responsible_worker_id'];
                            $result['situationSolutionStatuses'][$situation_solution_status['id']]['date_time'] = $situation_solution_status['date_time'];
                            $result['situationSolutionStatuses'][$situation_solution_status['id']]['description'] = $situation_solution_status['description'];
                            $result['situationSolutionStatuses'][$situation_solution_status['id']]['status_id'] = $situation_solution_status['status_id'];
                            $result['situationSolutionStatuses'][$situation_solution_status['id']]['status_title'] = $situation_solution_status['status']['title'];
                            $result['status_id'] = $situation_solution_status['status_id'];
                            $result['status_title'] = $situation_solution_status['status']['title'];
                        }
                    } else {
                        $result['situationSolutionStatuses'] = (object)array();
                    }

                    // заполняем зону ситуации - считаем в ней людей и предписания
                    if (isset($solution['situationJournalSituationSolutions'])) {
                        // формируем первичный выходной список зоны ситуации
                        foreach ($solution['situationJournalSituationSolutions'] as $item) {
                            foreach ($item['situationJournal']['situationJournalZones'] as $edge) {
                                $place_id = $edge['edge']['place_id'];
                                if ($place_id) {
                                    $result['situationPlaces'][$place_id]['place_id'] = $place_id;
                                    if (isset($edge['edge']['place'])) {
                                        $result['situationPlaces'][$place_id]['place_title'] = $edge['edge']['place']['title'];
                                    } else {
                                        $result['situationPlaces'][$place_id]['place_title'] = "";
                                    }
                                    $result['situationPlaces'][$place_id]['injunctions'] = (object)array();
                                    $result['situationPlaces'][$place_id]['workers'] = (object)array();
                                }
                            }
                        }

                        // формируем массив ключей мест для поиска предписаний и людей в данном месте
                        if (isset($result['situationPlaces'])) {
                            foreach ($result['situationPlaces'] as $place) {
                                if (!isset($place_ids)) {
                                    $place_ids = $place['place_id'];
                                } else {
                                    $place_ids .= ", " . $place['place_id'];
                                }
                            }
                            $date_time = Assistant::GetDateNow();

                            // получаем список действующих предписаний в данных местах
                            $response = self::GetInjunctionByPlace($place_ids, $date_time);
                            $log->addLogAll($response);
                            if ($response['status'] === 0) {
                                throw new Exception('ошибка получения списка предписаний по местам');
                            }

                            $injuncions_by_places = $response['Items'];
                            foreach ($injuncions_by_places as $key => $place_id) {
                                $result['situationPlaces'][$key]['injunctions'] = $place_id;
                            }

                            // считаем людей в данном месте на текущий момент времени
                            $response = self::GetWorkersByPlace($place_ids);
                            $log->addLogAll($response);
                            if ($response['status'] === 0) {
                                throw new Exception('ошибка получения списка работников по местам');
                            }

                            $workers_by_places = $response['Items'];
                            foreach ($workers_by_places as $key => $place_id) {
                                $result['situationPlaces'][$key]['workers'] = $place_id;
                            }
                        }


                    } else {
                        $result['situationPlaces'] = (object)array();
                    }

                    if (isset($situation_ids)) {
                        $response = HandbookRegulationController::GetRegulationActionsListBySituations($situation_ids);
                        $log->addLogAll($response);
                        if ($response['status'] === 0) {
                            throw new Exception('ошибка получения списка регламентов по ситуациям');
                        }
                        $result['regulationActions'] = $response['Items'];
                    } else {
                        $result['regulationActions'] = (object)array();
                    }
                }

                // проверяем на наличие всей структуры и в случае отсутствия какого либо элемента восстанавливаем ее
                foreach ($result['solutionActions'] as $solution_card) {
                    if (!isset($solution_card['solutionOperations'])) {
                        $result['solutionActions'][$solution_card['solution_id']]['solutionOperations'] = (object)array();
                    } else {
                        foreach ($solution_card['solutionOperations'] as $solution_operation) {
                            if (!isset($solution_operation['solutionOperationStatuses'])) {
                                $result['solutionActions'][$solution_card['solution_id']]['solutionOperations'][$solution_operation['solution_operation_id']]['solutionOperationStatuses'] = (object)array();
                            }
                        }
                    }
                    if (!isset($solution_card['solutionStatuses'])) {
                        $result['solutionActions'][$solution_card['solution_id']]['solutionStatuses'] = (object)array();
                    }
                }
            } else {
                $result['situation_solution_id'] = null;
                $result['situation_journal_id'] = -1;
                $result['situation_id'] = null;
                $result['situation_title'] = "";
                $result['regulation_time'] = 0;
                $result['solution_date_time_start'] = Assistant::GetDateNow();
                $result['solutionActions'] = (object)array();
                $result['regulationActions'] = (object)array();
                $result['situationPlaces'] = (object)array();
                $result['situationSolutionStatuses'] = (object)array();
            }

            /** Отладка */
            $log->addLog("Окончание выполнения метода");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    // DeleteSituationSolution - метод удаления решения/устранения
    // входные данные:
    //      situation_solution_id                     - ключ решения ситуации
    // выходные данные:

    // пример: http://127.0.0.1/read-manager-amicum?controller=EdsFront&method=DeleteSituationSolution&subscribe=&data={"situation_solution_id":2}
    public static function DeleteSituationSolution($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'DeleteSituationSolution. ';                                                                                 // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                       // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                     // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                   // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                           // время начала выполнения метода
        $date_time_debug_end = null;

        //базовые параметры скрипта
        $errors = array();
        $result = array();
        $status = 1;
        $warnings = array();
        $count_add = 0;
        $count_add_full = 0;
        $count_update = 0;

        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
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

            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . 'Не переданы входные параметры');
            }
            $warnings[] = $method_name . 'Данные успешно переданы';
            $warnings[] = $method_name . 'Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . 'Декодировал входные параметры';
            if (!property_exists($post_dec, 'situation_solution_id'))                                                     // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . 'Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . 'Данные с фронта получены';

            $situation_solution_id = $post_dec->situation_solution_id;

            $result = SituationSolution::deleteAll(['id' => $situation_solution_id]);

            $response = WebsocketController::SendMessageToWebSocket('situationEliminationDeleteSituationSolution', $post_dec);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка отправки данных на вебсокет');
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
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $ex) {
            $errors[] = $method_name . 'Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => $result, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    /**
     * Название метода: GetInjunctionByPlace()
     * GetInjunctionByPlace - Метод получения списка действующих предписаний по месту
     * @param string $places - массив мест Place_ids
     * @param string $date_time - дата на котору получаем список предписаний
     * @return array - массив предписаний не выполненных на участке
     *
     * //      {place_id}                   - ключ места
     * //          {injunction_id}              - ключ предписания
     * //                  injunction_id                        - ключ предписания
     * //                  injunction_date                      - дата предписания
     * //                  instruct_id_ip                       - ключ пункта предписания sap
     * //                  injunction_status_id                 - статус предписания
     * //                  injunction_place_id                  - ключ места на которое выписано предписание
     * //                  injunction_company_department_id     - ключ подразделения на которое выписано предписание
     * //                  correct_measure: {}                  - корректирующие мероприятия
     * //                      {correct_measure_id}                 - ключ корректирующих мероприятий
     * //                              correct_measure_id               - ключ корректирующих мероприятий
     * //                              operation_id                     - ключ операции
     * //                              operation_title                  - название операции
     * //                              unit_short_title                 - сокращенные единицы измерения операции
     * //                              correct_measures_value           - значение/количество операции
     * //                              correct_measure_status_id        - статус операции
     * //                              correct_measure_date_time        - дата операции
     * @author Якимов М.Н.
     * Created date: on 19.06.2019 8:57
     * @since ver
     */
    public static function GetInjunctionByPlace($places, $date_time)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // Промежуточный результирующий массив
        try {
            $warnings[] = 'GetInjunctionByPlace. Получение информации по предписаниям';
            $found_injunctions = Yii::$app->db->createCommand("
                SELECT  injunction.place_id, injunction.company_department_id, injunction.id as id, injunction.instruct_id_ip as instruct_id_ip, 
                checking.date_time_start as date_time, injunction_status.status_id,
                correct_measures.id as correct_measure_id, operation.title as operation_title,
                correct_measures.status_id as correct_measure_status_id, correct_measures.date_time as  correct_measure_date_time,
                correct_measures.correct_measures_value, correct_measures.operation_id, unit.short as unit_short_title
                FROM injunction
                INNER JOIN checking ON checking.id = injunction.checking_id
                INNER JOIN injunction_violation ON injunction.id = injunction_violation.injunction_id
                LEFT JOIN correct_measures ON injunction_violation.id = correct_measures.injunction_violation_id
                LEFT JOIN operation ON correct_measures.operation_id = operation.id
                LEFT JOIN unit ON unit.id=operation.unit_id
                INNER JOIN injunction_status ON injunction_status.injunction_id = injunction.id
                INNER JOIN (SELECT max(date_time) as max_date_time, injunction_id FROM injunction_status WHERE date_time <= '" . $date_time . "' GROUP BY injunction_id) inj_statys_max
                    ON inj_statys_max.max_date_time=injunction_status.date_time AND inj_statys_max.injunction_id = injunction_status.injunction_id
                WHERE 
                injunction.place_id in (" . $places . ") 
                AND ((injunction_status.status_id!=59) OR (DATE_FORMAT(injunction_status.date_time, '%Y-%m-%d')<='" . date("Y-m-d", strtotime($date_time)) . "'))
                AND (injunction.kind_document_id != 2)
                ")->queryAll();
            if (!empty($found_injunctions)) {
                foreach ($found_injunctions as $injunction) {
                    $result[$injunction['place_id']][$injunction['id']]['injunction_id'] = $injunction['id'];
                    $result[$injunction['place_id']][$injunction['id']]['injunction_date'] = $injunction['date_time'];
                    $result[$injunction['place_id']][$injunction['id']]['instruct_id_ip'] = $injunction['instruct_id_ip'];
                    $result[$injunction['place_id']][$injunction['id']]['injunction_status_id'] = $injunction['status_id'];
                    $result[$injunction['place_id']][$injunction['id']]['injunction_place_id'] = $injunction['place_id'];
                    $result[$injunction['place_id']][$injunction['id']]['injunction_company_department_id'] = $injunction['company_department_id'];
                    $result[$injunction['place_id']][$injunction['id']]['correct_measure'] = array();
                    if ($injunction['correct_measure_id']) {
                        $result[$injunction['place_id']][$injunction['id']]['correct_measure'][$injunction['correct_measure_id']]['correct_measure_id'] = $injunction['correct_measure_id'];
                        $result[$injunction['place_id']][$injunction['id']]['correct_measure'][$injunction['correct_measure_id']]['operation_id'] = $injunction['operation_id'];
                        $result[$injunction['place_id']][$injunction['id']]['correct_measure'][$injunction['correct_measure_id']]['operation_title'] = $injunction['operation_title'];
                        $result[$injunction['place_id']][$injunction['id']]['correct_measure'][$injunction['correct_measure_id']]['unit_short_title'] = $injunction['unit_short_title'];
                        $result[$injunction['place_id']][$injunction['id']]['correct_measure'][$injunction['correct_measure_id']]['correct_measures_value'] = $injunction['correct_measures_value'];
                        $result[$injunction['place_id']][$injunction['id']]['correct_measure'][$injunction['correct_measure_id']]['correct_measure_status_id'] = $injunction['correct_measure_status_id'];
                        $result[$injunction['place_id']][$injunction['id']]['correct_measure'][$injunction['correct_measure_id']]['correct_measure_date_time'] = $injunction['correct_measure_date_time'];
                    }
                }
                foreach ($result as $place_id) {
                    foreach ($place_id as $injunction) {
                        if (empty($injunction['correct_measure'])) {
                            $result[$injunction['injunction_place_id']][$injunction['injunction_id']]['correct_measure'] = (object)array();
                        }
                    }
                }
            } else {
                $result = (object)array();
            }

        } catch (Throwable $exception) {
            $errors[] = "GetInjunctionByPlace. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Название метода: GetWorkersByPlace()
     * GetWorkersByPlace - Метод получения списка людей находящихся в данный момент в данном месте
     * @param array $places - массив мест Place_ids
     * @return array - массив предписаний не выполненных на участке
     *                          {place_id}                              - ключ места
     * //                                {worker_id}                           - ключ работника
     * //                                      worker_id:                          - ключ работника
     * @author Якимов М.Н.
     * Created date: on 19.06.2019 8:57
     * @since ver
     */
    public static function GetWorkersByPlace($places)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // Промежуточный результирующий массив
        try {
            $warnings[] = 'GetWorkersByPlace. Получение информации по местам и людям в них';
            $found_workers = Yii::$app->db->createCommand("
                SELECT  worker_id, value as place_id
                FROM view_initWorkerParameterValue
                WHERE parameter_id=122 and parameter_type_id=2 and value in (" . $places . ") 
                ")->queryAll();
            if (!empty($found_workers)) {
                foreach ($found_workers as $worker) {
                    $result[$worker['place_id']][$worker['worker_id']]['worker_id'] = $worker['worker_id'];
                }
            } else {
                $result = (object)array();
            }

        } catch (Throwable $exception) {
            $errors[] = "GetWorkersByPlace. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // SaveSolutionCard - метод сохранения карточки решения ситуации
    // входные данные:
    //  solution_card:
    //      situation_solution_id           - ключ решения в целом
    //      solution_id: null,                      // id карты решения/устранения
    //      solution_title: '',                     // название решения/устранения
    //      solution_parent_id: null,               // id карточки родителя решения/устранения
    //      solution_parent_end_flag: 0,            // флаг первого/последнего действия (2 - последнее действие, 1 - первое действие, 0 - обычное действие)
    //      solution_number: 0,                     // номер карточки по порядку в решении
    //      solution_type: 'positive',              // тип карточки решения - действие в срок (карточка положительная), действие просрочено, карточка отрицательная
    //      child_action_id_positive: null,         // ключ действия при положительном исходе по решению карточки
    //      child_action_id_negative: null,         // ключ действия при отрицательном исходе по решению карточки
    //      x: 0,                                   // координата абсциссы карточки решения/устранения
    //      y: 0,                                   // координата ординаты карточки решения/устранения
    //      responsible_position_id: null,          // ключ должности последнего сменившего статус
    //      responsible_worker_id: null,            // ключ последнего ответственного сменившего статус (id работника - табельный)
    //      status_id: null,                        // ключ последнего статуса
    //      date_time: "",                          // дата и время последнего изменения статуса
    //      regulation_time: '-1',                  // регламентное время выполнения действия (-1 до устранения события/ любое целое цисло)
    //      solution_date_time_start: "",           // начало выполенния действия (используется для определения остатка на устранение решения)
    //      finish_flag_mode: 'auto',               // тип действия завершения (auto - автоматическое действие, manual - ручное)
    //      expired_indicator_flag: 0,              // флаг установки индикатора просрочки действия
    //      expired_indicator_mode: 'auto',         // тип действия просрочки (auto - автоматическое действие, manual - ручное)
    ///     description: "",                        // комментарий ответственного при изменении/неизменении статуса
    //      solutionStatuses: {},                   // история изменения статуса решения/устранения ситуации
    //              {solution_card_status_id}
    //                  solution_card_status_id:        - ключ статуса карточки решения
    //                  worker_id:                      - ключ работника изменившего карточку решения
    //                  status_id: null,                - статус карточки (выполнена или нет)
    //                  date_time:                      - дата и время изменения
    //                  description:                    - описание изменения
    //      operations:                  // операции
    //              operation_id: null,                     // id операции
    //              operation_type: 'manual',               // тип действия (manual - ручное, auto - автоматическое)
    //              equipments: {},                         // список оборудования
    //                  equipment_id: null,                     // id оборудования
    //              workers: {}                             // список сотрудников
    //                  position_id: null,                      // id должности сотрудника
    //                  worker_id: null,                        // id сотрудника
    //                  company_department_id: null             // id участка сотрудника
    //                  on_shift: 1,                            // оповещать работника на смене или первого на участке с такой должностью
    // выходные данные:
    //      - типовой набор
    //  solution_card:
    //      situation_solution_id                   // ключ решения в целом
    //      solution_id: null,                      // id карты решения/устранения
    //      solution_title: '',                     // название решения/устранения
    //      solution_parent_id: null,               // id родителя решения/устранения
    //      solution_child_id: null,                // id наследника решения/устранения (по сути ключ который разрываем - куда вставляем карточку новую)
    //      solution_parent_end_flag: 0,            // флаг первого/последнего действия (2 - последнее действие, 1 - первое действие, 0 - обычное действие)
    //      solution_number: 0,                     // номер карточки по порядку в решении
    //      solution_type: 'positive',              // тип карточки решения - действие в срок (карточка положительная), действие просрочено, карточка отрицательная
    //      child_action_id_positive: null,         // ключ действия при положительном исходе по решению карточки
    //      child_action_id_negative: null,         // ключ действия при отрицательном исходе по решению карточки
    //      x: 0,                                   // координата абсциссы карточки решения/устранения
    //      y: 0,                                   // координата ординаты карточки решения/устранения
    //      responsible_position_id: null,          // ключ должности последнего сменившего статус
    //      responsible_worker_id: null,            // ключ последнего ответственного сменившего статус (id работника - табельный)
    //      status_id: null,                        // ключ последнего статуса
    //      date_time: "",                          // дата и время последнего изменения статуса
    //      regulation_time: '-1',                  // регламентное время выполнения действия (-1 до устранения события/ любое целое цисло)
    //      solution_date_time_start: "",           // начало выполенния действия (используется для определения остатка на устранение решения)
    //      finish_flag_mode: 'auto',               // тип действия завершения (auto - автоматическое действие, manual - ручное)
    //      expired_indicator_flag: 0,              // флаг установки индикатора просрочки действия
    //      expired_indicator_mode: 'auto',         // тип действия просрочки (auto - автоматическое действие, manual - ручное)
    //      description: "",                        // комментарий ответственного при изменении/неизменении статуса
    //      solutionStatuses: {},                   // история изменения статуса решения/устранения ситуации
    //              {solution_card_status_id}
    //                  solution_card_status_id:        - ключ статуса карточки решения
    //                  worker_id:                      - ключ работника изменившего карточку решения
    //                  status_id: null,                - статус карточки (выполнена или нет)
    //                  date_time:                      - дата и время изменения
    //                  description:                    - описание изменения
    //      solutionOperations: {},                 // список операций выполняемых/выполненных во время устранения ситуации
    //              {solution_operation_id}
    //                          solution_operation_id: null,            // id привязки операции к решению/устранению ситуации
    //                          operation_id: null,                     // id операции
    //                          description: "",                        // описание операции
    //                          operation_type: 'manual',               // тип действия (manual - ручное, auto - автоматическое)
    //                          on_shift: 1,                            // оповещать работника на смене или первого на участке с такой должностью
    //                          status_id: null,                        // статус операции (выполнена или нет)
    //                          equipment_id: null,                     // ключ оборудования
    //                          worker_id: null,                        // ключ работника
    //                          position_id: null,                      // ключ должности
    //                          company_department_id: null,            // ключ департамента работника
    //                          date_time: "",                          // дата и время последнего изменения статуса
    //                          solutionOperationStatuses: {}           // статусы изменения операции решения
    // пример: http://127.0.0.1/read-manager-amicum?controller=EdsFront&method=SaveSolutionCard&subscribe=&data={"solution_card":{}}
    public static function SaveSolutionCard($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'SaveSolutionCard. ';                                                                       // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                       // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                     // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                   // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                           // время начала выполнения метода
        $date_time_debug_end = null;

        //базовые параметры скрипта
        $errors = array();
        $save_solution_card = (object)array();
        $status = 1;
        $warnings = array();
        $count_add = 0;
        $count_add_full = 0;
        $count_update = 0;

        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
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

            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . 'Не переданы входные параметры');
            }
            $warnings[] = $method_name . 'Данные успешно переданы';
            $warnings[] = $method_name . 'Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . 'Декодировал входные параметры';
            if (!property_exists($post_dec, 'solution_card'))                                                     // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . 'Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . 'Данные с фронта получены';

            $save_solution_card = $post_dec->solution_card;

            // сохраняем новую карточку - которую только что добавили
            $solution_card = SolutionCard::findOne(['id' => $save_solution_card->solution_id]);
            if (!$solution_card) {
                $solution_card = new SolutionCard();
            }

            $date_time = Assistant::GetDateNow();
            $session = Yii::$app->session;

            $solution_card->situation_solution_id = $save_solution_card->situation_solution_id;
            $solution_card->title = $save_solution_card->solution_title;
            $solution_card->solution_parent_id = $save_solution_card->solution_parent_id;
            $solution_card->solution_parent_end_flag = $save_solution_card->solution_parent_end_flag;
            $solution_card->solution_number = $save_solution_card->solution_number;
            $solution_card->solution_type = $save_solution_card->solution_type;
            $solution_card->child_action_id_positive = $save_solution_card->child_action_id_positive;
            $solution_card->child_action_id_negative = $save_solution_card->child_action_id_negative;
            $solution_card->x = $save_solution_card->x;
            $solution_card->y = $save_solution_card->y;
            $solution_card->responsible_position_id = $session['position_id'];
            $solution_card->responsible_worker_id = $session['worker_id'];
            $solution_card->status_id = $save_solution_card->status_id;
            $solution_card->date_time = $date_time;
            $solution_card->regulation_time = $save_solution_card->regulation_time;
            $solution_card->solution_date_time_start = $save_solution_card->solution_date_time_start;
            $solution_card->finish_flag_mode = $save_solution_card->finish_flag_mode;
            $solution_card->expired_indicator_flag = $save_solution_card->expired_indicator_flag;
            $solution_card->expired_indicator_mode = $save_solution_card->expired_indicator_mode;
            $solution_card->description = "";

            if ($solution_card->save()) {
                $solution_card_id = $solution_card->id;
                $save_solution_card->solution_id = $solution_card_id;
            } else {
                $errors[] = $solution_card->errors;
                throw new Exception($method_name . '. Ошибка при сохранении карточки решения SolutionCard');
            }

            // изменяем дочернюю карточку
            $child_solution_card = SolutionCard::findOne(['id' => $save_solution_card->solution_child_id]);
            if ($child_solution_card) {
                $child_solution_card->solution_parent_id = $solution_card_id;
                if (!$child_solution_card->save()) {
                    $errors[] = $child_solution_card->errors;
                    throw new Exception($method_name . '. Ошибка при сохранении дочерней карточки решения SolutionCard метод выполняется при разрыве карточек');
                }
            }

            // изменяем родительскую карточку
            $parent_solution_card = SolutionCard::findOne(['id' => $save_solution_card->solution_parent_id]);
            if ($parent_solution_card) {
                $parent_solution_card->child_action_id_positive = $solution_card_id;
                if (!$parent_solution_card->save()) {
                    $errors[] = $parent_solution_card->errors;
                    throw new Exception($method_name . '. Ошибка при сохранении родительской карточки решения SolutionCard метод выполняется при разрыве карточек');
                }
            }


            //сохраняем новый статус карточки решения
            $solution_card_status = new SolutionCardStatus();
            $solution_card_status->solution_card_id = $solution_card_id;
            $solution_card_status->worker_id = $session['worker_id'];
            $solution_card_status->status_id = $save_solution_card->status_id;
            $solution_card_status->date_time = $date_time;
            $solution_card_status->description = "";
            if ($solution_card_status->save()) {
                $solution_card_status_id = $solution_card_status->id;
            } else {
                $errors[] = $solution_card_status->errors;
                throw new Exception($method_name . '. Ошибка при сохранении статуса карточки решения SolutionCardStatus');
            }
            //      solutionStatuses: {},                   // история изменения статуса решения/устранения ситуации
            //              {solution_card_status_id}
            //                  solution_card_status_id:        - ключ статуса карточки решения
            //                  worker_id:                      - ключ работника изменившего карточку решения
            //                  status_id: null,                - статус карточки (выполнена или нет)
            //                  date_time:                      - дата и время изменения
            //                  description:                    - описание изменения

            $save_solution_card->solutionStatuses->{$solution_card_status_id} = (object)[
                'solution_card_status_id' => $solution_card_status_id,
                'worker_id' => $session['worker_id'],
                'date_time' => $date_time,
                'description' => "",
                'status_id' => $save_solution_card->status_id
            ];

            // сохраняем связь журнала ситуаций и решений
            $save_solution_card->solutionOperations = (object)array();

            foreach ($save_solution_card->operations as $operation) {

                foreach ($operation->equipments as $equipment) {
                    $solution_operation = new SolutionOperation();
                    $solution_operation->solution_card_id = $solution_card_id;
                    $solution_operation->operation_id = $operation->operation_id;
//                    $solution_operation->description = "";
                    $solution_operation->operation_type = $operation->operation_type;
//                    $solution_operation->on_shift = null;
//                    $solution_operation->status_id = null;
                    $solution_operation->equipment_id = $equipment->equipment_id;
//                    $solution_operation->worker_id = null;
//                    $solution_operation->position_id = null;
//                    $solution_operation->company_department_id = null;
                    $solution_operation->date_time = $date_time;

                    if ($solution_operation->save()) {
                        $solution_operation_id = $solution_operation->id;
                        $operation->situation_journal_situation_solution_id = $solution_operation_id;
                    } else {
                        $errors[] = $solution_operation->errors;
                        throw new Exception($method_name . '. Ошибка при сохранении операции карточки решения SolutionOperation equipments');
                    }

                    $save_solution_card->solutionOperations->{$solution_operation_id} = (object)[
                        'solution_operation_id' => $solution_operation_id,
                        'operation_id' => $operation->operation_id,
                        'description' => "",
                        'operation_type' => $operation->operation_type,
                        'on_shift' => null,
                        'status_id' => null,
                        'equipment_id' => $equipment->equipment_id,
                        'worker_id' => null,
                        'position_id' => null,
                        'company_department_id' => null,
                        'date_time' => $date_time,
                        'solutionOperationStatuses' => (object)array()
                    ];
                }

                foreach ($operation->workers as $worker) {
                    $solution_operation = new SolutionOperation();
                    $solution_operation->solution_card_id = $solution_card_id;
                    $solution_operation->operation_id = $operation->operation_id;
//                    $solution_operation->description = "";
                    $solution_operation->operation_type = $operation->operation_type;
                    $solution_operation->on_shift = $worker->on_shift;
//                    $solution_operation->status_id = null;
//                    $solution_operation->equipment_id = null;
                    $solution_operation->worker_id = $worker->worker_id;
                    $solution_operation->position_id = $worker->position_id;
                    $solution_operation->company_department_id = $worker->company_department_id;
                    $solution_operation->date_time = $date_time;

                    if ($solution_operation->save()) {
                        $solution_operation_id = $solution_operation->id;
                        $operation->situation_journal_situation_solution_id = $solution_operation_id;
                    } else {
                        $errors[] = $solution_operation->errors;
                        throw new Exception($method_name . '. Ошибка при сохранении операции карточки решения SolutionOperation workers');
                    }

                    $save_solution_card->solutionOperations->{$solution_operation_id} = (object)[
                        'solution_operation_id' => $solution_operation_id,
                        'operation_id' => $operation->operation_id,
                        'description' => "",
                        'operation_type' => $operation->operation_type,
                        'on_shift' => $worker->on_shift,
                        'status_id' => null,
                        'equipment_id' => null,
                        'worker_id' => $worker->worker_id,
                        'position_id' => $worker->position_id,
                        'company_department_id' => $worker->company_department_id,
                        'date_time' => $date_time,
                        'solutionOperationStatuses' => (object)array()
                    ];
                }
            }

            unset($save_solution_card->operations);

            $response = WebsocketController::SendMessageToWebSocket('situationEliminationSaveSolutionCard', $save_solution_card);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка отправки данных на вебсокет');
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
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $ex) {
            $errors[] = $method_name . 'Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => $save_solution_card, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    // ChangeStatusSolutionCard - метод изменения статуса карточки решения/устранения ситуации
    // входные данные:
    //  solution_card:
    //      situation_solution_id                   // ключ решения в целом
    //      solution_id: null,                      // id карты решения/устранения
    //      status_id: null,                        // ключ последнего статуса
    //      description: "",                        // комментарий ответственного исполнителя
    // выходные данные:
    // пример: http://127.0.0.1/read-manager-amicum?controller=EdsFront&method=ChangeStatusSolutionCard&subscribe=&data={"solution_card":{"description":"опа опа","situation_solution_id":1,"solution_id":1,"status_id":50}}
    public static function ChangeStatusSolutionCard($data_post = NULL)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("ChangeStatusSolutionCard");

        $save_solution_card = (object)array();

        try {
            $log->addLog("Начало выполнения метода");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $log->addData($data_post, '$data_post', __LINE__);

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $log->addLog("Декодировал входные параметры");

            if (!property_exists($post_dec, 'solution_card'))                                                     // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $log->addLog("Данные с фронта получены");

            $save_solution_card = $post_dec->solution_card;
            $solution_card_id = $save_solution_card->solution_id;

            if (!property_exists($save_solution_card, 'situation_solution_id')) {
                throw new Exception('Не передано поле situation_solution_id');
            }

            // ищем карточку с решением и меняем ее статус
            $solution_card = SolutionCard::findOne(['id' => $solution_card_id]);
            if (!$solution_card) {
                throw new Exception('Ошибка при поиске карточки решения SolutionCard на изменение статуса');
            }

            $date_time = Assistant::GetDateNow();
            $session = Yii::$app->session;

            $solution_card->responsible_position_id = $session['position_id'];
            $solution_card->responsible_worker_id = $session['worker_id'];
            $solution_card->status_id = $save_solution_card->status_id;
            $solution_card->description = $save_solution_card->description;
            $solution_card->date_time = $date_time;

            if (!$solution_card->save()) {
                $log->addData($solution_card->errors, '$solution_card->errors', __LINE__);
                throw new Exception('Ошибка при сохранении карточки решения SolutionCard');
            }

            $log->addLog("Сохранил карточку");

            //сохраняем новый статус карточки решения
            $solution_card_status = new SolutionCardStatus();
            $solution_card_status->solution_card_id = $solution_card_id;
            $solution_card_status->worker_id = $session['worker_id'];
            $solution_card_status->status_id = $save_solution_card->status_id;
            $solution_card_status->date_time = $date_time;
            $solution_card_status->description = $save_solution_card->description;
            if ($solution_card_status->save()) {
                $solution_card_status_id = $solution_card_status->id;
                $save_solution_card->date_time = $date_time;
            } else {
                $log->addData($solution_card_status->errors, '$solution_card_status->errors', __LINE__);
                throw new Exception('Ошибка при сохранении статуса карточки решения SolutionCardStatus');
            }
            //      solutionStatuses: {},                   // история изменения статуса решения/устранения ситуации
            //              {solution_card_status_id}
            //                  solution_card_status_id:        - ключ статуса карточки решения
            //                  worker_id:                      - ключ работника изменившего карточку решения
            //                  status_id: null,                - статус карточки (выполнена или нет)
            //                  date_time:                      - дата и время изменения
            //                  description:                    - описание изменения

            $log->addLog("Сохранил статус карточки");

            if (!property_exists($save_solution_card, "solutionStatuses")) {
                $save_solution_card->solutionStatuses = (object)array();
            }
            $save_solution_card->solutionStatuses->{$solution_card_status_id} = (object)[
                'solution_card_status_id' => $solution_card_status_id,
                'worker_id' => $session['worker_id'],
                'date_time' => $date_time,
                'description' => $save_solution_card->description,
                'status_id' => $save_solution_card->status_id
            ];

            $response = WebsocketController::SendMessageToWebSocket('situationEliminationChangeStatusSolutionCard', $save_solution_card);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка отправки данных на вебсокет');
            }
            $log->addLog("Отправил данные на вебсокет");

            $log->addLog("Окончил выполнение метода");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $save_solution_card], $log->getLogAll());
    }

    // ChangeStatusSituationSolution - метод изменения статуса конкретного решения ситуации
    // входные данные:
    //  situation_solution: {}
    //      situation_solution_id           - ключ решения в целом
    //      status_id: null,                - последний статус (выполнена или нет)
    // выходные данные:
    //      - типовой набор
    //      - обновленный входной объект
    // пример: http://127.0.0.1/read-manager-amicum?controller=EdsFront&method=ChangeStatusSituationSolution&subscribe=&data={"situation_solution":{"status_id":50,"situation_solution_id":1}}
    public static function ChangeStatusSituationSolution($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'ChangeStatusSituationSolution. ';                                                                       // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                       // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                     // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                   // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                           // время начала выполнения метода
        $date_time_debug_end = null;

        //базовые параметры скрипта
        $errors = array();
        $save_solution = (object)array();
        $status = 1;
        $warnings = array();
        $count_add = 0;
        $count_add_full = 0;
        $count_update = 0;

        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
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

            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . 'Не переданы входные параметры');
            }
            $warnings[] = $method_name . 'Данные успешно переданы';
            $warnings[] = $method_name . 'Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . 'Декодировал входные параметры';
            if (!property_exists($post_dec, 'situation_solution'))                                                     // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . 'Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . 'Данные с фронта получены';

            $save_solution = $post_dec->situation_solution;
            $situation_solution_id = $save_solution->situation_solution_id;
            $status_id = $save_solution->status_id;

            // сохраняем главное решение
            $solution = SituationSolution::findOne(['id' => $situation_solution_id]);
            if (!$solution) {
                throw new Exception($method_name . '. Решение ситуации не найдено');
            }

            $date_time = Assistant::GetDateNow();


            //сохраняем новый статус решения
            $session = Yii::$app->session;
            $solution_status = new SituationSolutionStatus();
            $solution_status->situation_solution_id = $situation_solution_id;
            $solution_status->responsible_position_id = $session['position_id'];
            $solution_status->responsible_worker_id = $session['worker_id'];
            $solution_status->status_id = $status_id;
            $solution_status->date_time = $date_time;
            $solution_status->description = "";
            if ($solution_status->save()) {
                $situation_solution_status_id = $solution_status->id;
            } else {
                $errors[] = $solution_status->errors;
                throw new Exception($method_name . '. Ошибка при сохранении статуса решения SituationSolutionStatus');
            }

            if (!property_exists($save_solution, "situationSolutionStatuses")) {
                $save_solution->situationSolutionStatuses = (object)array();
            }

            $save_solution->situationSolutionStatuses->{$situation_solution_status_id} = (object)[
                'situation_solution_status_id' => $situation_solution_status_id,
                'responsible_position_id' => $session['position_id'],
                'responsible_worker_id' => $session['worker_id'],
                'date_time' => $date_time,
                'description' => "",
                'status_id' => $save_solution->status_id
            ];


            $response = WebsocketController::SendMessageToWebSocket('situationEliminationChangeStatusSituationSolution', $save_solution);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка отправки данных на вебсокет');
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
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $ex) {
            $errors[] = $method_name . 'Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => $save_solution, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }


    // ChangeStatusSolutionOperation - метод изменения статуса операции карточки решения/устранения ситуации
    // входные данные:
    //  solution_operation:
    //      situation_solution_id                   // ключ решения в целом
    //      solution_id: null,                      // id карты решения/устранения
    //      solution_operation_id: null,            // id операции карты решения/устранения
    //      status_id: null,                        // ключ последнего статуса
    //      worker_id: null,                        // ключ работника на которого назначена операция
    // выходные данные:
    // пример: http://127.0.0.1/read-manager-amicum?controller=EdsFront&method=ChangeStatusSolutionOperation&subscribe=&data={"solution_operation":{"situation_solution_id":1,"solution_id":1,"status_id":50,"solution_operation_id":1}}
    public static function ChangeStatusSolutionOperation($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'ChangeStatusSolutionCard. ';                                                                    // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                       // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                     // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                   // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                           // время начала выполнения метода
        $date_time_debug_end = null;

        //базовые параметры скрипта
        $errors = array();
        $solution_operation = (object)array();
        $status = 1;
        $warnings = array();
        $count_add = 0;
        $count_add_full = 0;
        $count_update = 0;
        $save_solution_operation = null;
        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
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

            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . 'Не переданы входные параметры');
            }
            $warnings[] = $method_name . 'Данные успешно переданы';
            $warnings[] = $method_name . 'Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . 'Декодировал входные параметры';
            if (!property_exists($post_dec, 'solution_operation'))                                                     // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . 'Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . 'Данные с фронта получены';

            $save_solution_operation = $post_dec->solution_operation;
            $solution_operation_id = $save_solution_operation->solution_operation_id;
            $worker_id = $save_solution_operation->worker_id;
            $status_id = $save_solution_operation->status_id;

            if (!property_exists($save_solution_operation, 'situation_solution_id')) {
                throw new Exception($method_name . '. Не передано поле situation_solution_id');
            }

            if (!property_exists($save_solution_operation, 'solution_id')) {
                throw new Exception($method_name . '. Не передано поле solution_id');
            }
            $date_time = Assistant::GetDateNow();
            $session = Yii::$app->session;

            // ищем операцию карточки решения и меняем ее статус
            $solution_operation = SolutionOperation::findOne(['id' => $solution_operation_id]);
            if (!$solution_operation) {
                throw new Exception($method_name . '. Ошибка при поиске операции карточки решения SolutionOperation на изменение статуса');
            }

            $solution_operation->status_id = $status_id;
            $solution_operation->date_time = $date_time;
            $solution_operation->worker_id = $worker_id;

            if (!$solution_operation->save()) {
                $errors[] = $solution_operation->errors;
                throw new Exception($method_name . '. Ошибка при сохранении операции карточки решения SolutionOperation');
            }

            //сохраняем новый статус карточки решения
            $solution_operation_status = new SolutionOperationStatus();
            $solution_operation_status->solution_operation_id = $solution_operation_id;
            $solution_operation_status->worker_id = $session['worker_id'];
            $solution_operation_status->status_id = $status_id;
            $solution_operation_status->date_time = $date_time;
            $solution_operation_status->description = "";
            if ($solution_operation_status->save()) {
                $solution_operation_status_id = $solution_operation_status->id;
            } else {
                $errors[] = $solution_operation_status->errors;
                throw new Exception($method_name . '. Ошибка при сохранении статуса операции карточки решения SolutionOperationStatus');
            }

            // solutionOperationStatuses: {}           // статусы изменения операции решения
            //         {solution_operation_status_id}
            //                 solution_operation_status_id        - ключ статуса изменения операции решения
            //                 worker_id:                          - ключ работника изменившего операцию решения
            //                 status_id: null,                    - статус операции (выполнена или нет)
            //                 date_time:                          - дата и время изменения
            //                 description:                        - описание изменения

            if (!property_exists($save_solution_operation, "solutionOperationStatuses")) {
                $save_solution_operation->solutionOperationStatuses = (object)array();
            }
            $save_solution_operation->solutionOperationStatuses->{$solution_operation_status_id} = (object)[
                'solution_operation_status_id' => $solution_operation_status_id,
                'worker_id' => $session['worker_id'],
                'date_time' => $date_time,
                'description' => "",
                'status_id' => $status_id
            ];

            $response = WebsocketController::SendMessageToWebSocket('situationEliminationChangeStatusSolutionOperation', $save_solution_operation);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка отправки данных на вебсокет');
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
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $ex) {
            $errors[] = $method_name . 'Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => $save_solution_operation, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    // функция сравнения массивов значений между собой
    function compareArray($v1, $v2)
    {
        if ($v1 == $v2) {
            return 0;
        }
        if ($v1 > $v2) return 1;
        return -1;
    }

    // CreateSolutionBySituation - метод генерации регламента на основе ключа ситуации
    // входные данные:
    //  situation_solution: {}
    //      situation_id           - ключ ситуации
    //      situation_journal_id   - ключ журнала ситуации
    // выходные данные:
    //      - типовой набор
    //      - объкт как при get
    // пример: 127.0.0.1/eds-front/create-solution-by-situation?situation_id=1&situation_journal_id=2
    public static function CreateSolutionBySituation($situation_id, $situation_journal_id)
    {
        // Стартовая отладочная информация
        $method_name = 'CreateSolutionBySituation. ';                                                                   // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                       // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                     // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                   // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                           // время начала выполнения метода
        $date_time_debug_end = null;

        //базовые параметры скрипта
        $errors = array();
        $new_situation_solution = (object)array();
        $status = 1;
        $warnings = array();
        $count_add = 0;
        $count_add_full = 0;
        $count_update = 0;
        $worker_cache_controller = new WorkerCacheController();

        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
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

            // находим регламент для данной ситуации

            $response = HandbookRegulationController::GetRegulationActionsListBySituations($situation_id);
            $errors[] = $response['errors'];
            $warnings[] = $response['warnings'];
            if ($response['status'] != 1) {
                throw new Exception($method_name . "ошибка получения списка регламентов по ситуациям");
            }
            $regulations = $response['Items'];

            if (!count((array)$regulations)) {
                throw new Exception($method_name . ". У ситуации нет регламента на устранение");
            }

            $date_time = Assistant::GetDateNow();
            // создаем на основе регламента путь решения по положительному пути

            // ищем первую карточку
            $action_parent_end_flag = 0;                                                                                // флаг первого/последнего действия в регламенте, 0 - не задано или карточка по середине
            foreach ($regulations as $regulation) {
                foreach ($regulation['regulation_actions'] as $action) {
                    // если у действия нет родителя и у него стоит флаг начальной карточки
                    if (!$action['action_parent_id'] and $action['action_parent_end_flag'] == 1) {
                        $action_id = $action['action_id'];                                                              // первая карточка в регламенте
                        $action_parent_end_flag = $action['action_parent_end_flag'];                                    // флаг текущего места карточки (первая - 1, последняя -2, или в середине - 0)
                        $regulation_id = $regulation['regulation_id'];                                                  // ключ регламента
                        break;
                    }
                }
            }

            if (!isset($action_id)) {
                throw new Exception($method_name . '. У регламента не задано начальное действие');
            }

            $actions = $regulations[$regulation_id]['regulation_actions'];                                              // для удобства делаем доступ к регламенту сразу
            // ищем положительный путь
            $count_get_action = 0;                                                                                      // счетчик ограничителя по циклу
            $regulation_true_path = array();                                                                            // линейный путь из верных действий
            $regulation_time = 0;                                                                                       // суммарное регламентное время устранения ситуации
            while ($count_get_action < 1000 and $action_parent_end_flag != 2 and $action_id) {
                if (isset($actions[$action_id])) {
                    $regulation_true_path[] = $actions[$action_id];
                    $action_parent_end_flag = $actions[$action_id]['action_parent_end_flag'];                           // флаг текущего места карточки (первая - 1, последняя -2, или в середине - 0)
                    $regulation_time += $actions[$action_id]['regulation_time'];                                                    // суммарное регламентное время устранения ситуации
                    if ($action_id == $actions[$action_id]['child_action_id_positive']) {
                        $errors[] = $method_name . "Ключ регламента regulation_id: " . $regulation_id;
                        $errors[] = $actions[$action_id];
                        throw new Exception($method_name . '. Петля карточек в регламенте');
                    }
                    $action_id = $actions[$action_id]['child_action_id_positive'];                                      // ключ доччерней карточки
                }
                $count_get_action++;
            }

            $warnings[] = $method_name . " результат по построению правильного маршрута count_get_action: " . $count_get_action;
            $warnings[] = $method_name . " результат по построению правильного маршрута action_parent_end_flag: " . $action_parent_end_flag;
            $warnings[] = $method_name . " результат по построению правильного маршрута action_id: " . $action_id;

            if (!isset($regulation_true_path)) {
                throw new Exception($method_name . '. прямой положительный путь в регламенте не найден');
            }

            // сохраняем главное решение

            $solution = new SituationSolution();

            $solution->regulation_time = $regulation_time;
            $solution->solution_date_time_start = $date_time;
            if ($solution->save()) {
                $situation_solution_id = $solution->id;
            } else {
                $errors[] = $solution->errors;
                throw new Exception($method_name . '. Ошибка при сохранении главного решения SituationSolution');
            }

            //сохраняем новый статус решения
            $session = Yii::$app->session;
            $solution_status = new SituationSolutionStatus();
            $solution_status->situation_solution_id = $situation_solution_id;
            $solution_status->responsible_position_id = 1;                                                              // системная учетная запись
            $solution_status->responsible_worker_id = 1;                                                                // системная учетная запись
            $solution_status->status_id = 57;                                                                           // 57 - Новое
            $solution_status->date_time = $date_time;
            $solution_status->description = "";
            if (!$solution_status->save()) {
                $errors[] = $solution_status->errors;
                throw new Exception($method_name . '. Ошибка при сохранении статуса решения SituationSolutionStatus');
            }
            $situation_solution_status_id = $solution_status->id;

            // получаем шахту на основе журнала ситуаций $situation_journal_id
            $situation_journal = SituationJournal::findOne(['id' => $situation_journal_id]);
            if (!$situation_journal) {
                throw new Exception($method_name . '. В жунале ситуаций нет переданной ситуации');
            }
            $mine_id = $situation_journal['mine_id'];                                                                   // ключ шахты, нужен для определения работника, которого включать в действие

            // сохраняем связь журнала ситуаций и решений
            $situation_journal_situation_solution = new SituationJournalSituationSolution();

            $situation_journal_situation_solution->situation_solution_id = $situation_solution_id;
            $situation_journal_situation_solution->situation_journal_id = $situation_journal_id;
            if (!$situation_journal_situation_solution->save()) {
                $errors[] = $situation_journal_situation_solution->errors;
                throw new Exception($method_name . '. Ошибка при сохранении главного решения SituationJournalSituationSolution');
            }

            // создаем карточки решения на его основе найденного пути
            $solution_parent_id = null;                                                                                 // ключ родителя первой карточки
            $solution_number = 1;                                                                                       // порядковый номер действия
            foreach ($regulation_true_path as $action) {


                // сохраняем новую карточку - которую только что добавили
                $solution_card = new SolutionCard();

                $solution_card->situation_solution_id = $situation_solution_id;
                $solution_card->title = $action['action_title'];
                $solution_card->solution_parent_id = $solution_parent_id;
                $solution_card->solution_parent_end_flag = $action['action_parent_end_flag'];
                $solution_card->solution_number = $solution_number;
                $solution_card->solution_type = $action['action_type'];
                $solution_card->child_action_id_positive = null;
                $solution_card->child_action_id_negative = null;
                $solution_card->x = null;
                $solution_card->y = null;
                $solution_card->responsible_position_id = 1;
                $solution_card->responsible_worker_id = 1;
                $solution_card->status_id = 57;
                $solution_card->date_time = $date_time;
                $solution_card->regulation_time = $action['regulation_time'];
                $solution_card->solution_date_time_start = null;
                $solution_card->finish_flag_mode = $action['finish_flag_mode'];
                $solution_card->expired_indicator_flag = $action['expired_indicator_flag'];
                $solution_card->expired_indicator_mode = $action['expired_indicator_mode'];
                $solution_card->description = "";

                if ($solution_card->save()) {
                    $solution_card_id = $solution_card->id;

                    // обновляем родителскую карточку в части дочернего элемента
                    if ($solution_parent_id) {
                        $parent_solution_card = SolutionCard::findOne(['id' => $solution_parent_id]);
                        if ($parent_solution_card) {
                            $parent_solution_card->child_action_id_positive = $solution_card_id;
                            if (!$parent_solution_card->save()) {
                                $errors[] = $parent_solution_card->errors;
                                throw new Exception($method_name . '. Ошибка при сохранении связи родительской карточки к дочерней SolutionCard');
                            }
                        } else {
                            throw new Exception($method_name . '. Ошибка поиска родителя для обновления дочернего элемента связи SolutionCard');
                        }
                    }

                    $solution_parent_id = $solution_card_id;
                    $solution_number++;
                } else {
                    $errors[] = $solution_card->errors;
                    throw new Exception($method_name . '. Ошибка при сохранении карточки решения SolutionCard');
                }

                //сохраняем новый статус карточки решения
                $solution_card_status = new SolutionCardStatus();
                $solution_card_status->solution_card_id = $solution_card_id;
                $solution_card_status->worker_id = 1;                                                                   // системная учетная запись
                $solution_card_status->status_id = 57;                                                                  // 57 - новое
                $solution_card_status->date_time = $date_time;
                $solution_card_status->description = "";
                if (!$solution_card_status->save()) {
                    $errors[] = $solution_card_status->errors;
                    throw new Exception($method_name . '. Ошибка при сохранении статуса карточки решения SolutionCardStatus');
                }

                foreach ($action['operations'] as $operation) {
                    $flag_blank_operation = false;                                                                      // если операция не назначена ни на работника, ни на подразделение, ни на оборудование, то заполнять ее в самом конце
                    foreach ($operation['equipments'] as $equipment) {
                        $flag_blank_operation = true;
                        $solution_operation = new SolutionOperation();
                        $solution_operation->solution_card_id = $solution_card_id;
                        $solution_operation->operation_id = $operation['operation_id'];
//                    $solution_operation->description = "";
                        $solution_operation->operation_type = $operation['operation_type'];
//                    $solution_operation->status_id = null;
                        $solution_operation->equipment_id = $equipment['equipment_id'];
//                    $solution_operation->worker_id = null;
//                    $solution_operation->position_id = null;
//                    $solution_operation->company_department_id = null;
                        $solution_operation->date_time = $date_time;

                        if (!$solution_operation->save()) {
                            $errors[] = $solution_operation->errors;
                            throw new Exception($method_name . '. Ошибка при сохранении операции карточки решения SolutionOperation equipments');
                        }

                    }

                    foreach ($operation['workers'] as $worker) {
                        $flag_blank_operation = true;
                        $workers = array();
                        $filter = array();
                        if ($worker['company_department_id']) {
                            // ищем работников по должностям и подразделеню
                            $filter = array(
                                'company_department_id' => $worker['company_department_id'],
                                'position_id' => $worker['position_id']
                            );
                        } else {
                            // ищем работников по должностям
                            $filter = array(
                                'position_id' => $worker['position_id']
                            );
                        }
//                        $find_workers=null;
                        // ищем первого работника на участке
                        $find_workers = Worker::find()
                            ->select('id')
                            ->where($filter)
                            ->andWhere(['or',
                                ['>', 'worker.date_end', $date_time],
                                ['is', 'worker.date_end', null]
                            ])
                            ->asArray()
                            ->column();
                        if ($find_workers) {
                            if ($worker['on_shift']) {
                                // ищем работников на смене
                                // получаем из кеша список работников на смене
                                $worker_mines = $worker_cache_controller->getWorkerMineHash($mine_id);
                                if ($worker_mines) {
                                    $workers_cache = array_column($worker_mines, 'worker_id');
//                                    $workers = array_uintersect($workers_cache, $find_workers, 'compareArray');
                                    $workers = array_intersect($workers_cache, $find_workers);
                                } else {
                                    $warnings[] = $method_name . ". Работники на смене не найдены, т.к. кеш пуст";
                                    $warnings[] = $method_name . ". Шахта: " . $mine_id;
                                    $warnings[] = $method_name . ". Ключ журнала ситуаций: " . $situation_journal_id;
                                    $warnings[] = $method_name . ". Ключ решения: " . $situation_solution_id;
                                    $workers[] = null; //TODO не дописал
                                }
                            } else {
                                // ищем по всем работникам
                                $workers = $find_workers;
                            }
                        } else {
                            $warnings[] = $method_name . ". Работники не найдены по фильтру: ";
                            $warnings[] = $filter;
                            $workers[] = null;
                        }
                        $warnings[] = $method_name . ". Список работников для генерации операций: ";
                        $warnings[] = $workers;
                        foreach ($workers as $worker_id) {
                            $solution_operation = new SolutionOperation();
                            $solution_operation->solution_card_id = $solution_card_id;
                            $solution_operation->operation_id = $operation['operation_id'];
//                    $solution_operation->description = "";
                            $solution_operation->operation_type = $operation['operation_type'];
                            $solution_operation->on_shift = $worker['on_shift'];
//                    $solution_operation->status_id = null;
//                    $solution_operation->equipment_id = null;
                            $solution_operation->worker_id = $worker_id;
                            $solution_operation->position_id = $worker['position_id'];
                            $solution_operation->company_department_id = $worker['company_department_id'];
                            $solution_operation->date_time = $date_time;

                            if (!$solution_operation->save()) {
                                $errors[] = $solution_operation->errors;
                                throw new Exception($method_name . '. Ошибка при сохранении операции карточки решения SolutionOperation workers');
                            }
                        }
                    }

                    if (!$flag_blank_operation) {                                                                       // если операция ни кому не назначена, то добавляем ее пустой
                        $solution_operation = new SolutionOperation();
                        $solution_operation->solution_card_id = $solution_card_id;
                        $solution_operation->operation_id = $operation['operation_id'];
//                        $solution_operation->description = "";
                        $solution_operation->operation_type = $operation['operation_type'];
//                        $solution_operation->status_id = null;
//                        $solution_operation->equipment_id = null;
//                        $solution_operation->worker_id = null;
//                        $solution_operation->position_id = null;
//                        $solution_operation->company_department_id = null;
                        $solution_operation->date_time = $date_time;

                        if (!$solution_operation->save()) {
                            $errors[] = $solution_operation->errors;
                            throw new Exception($method_name . '. Ошибка при сохранении операции карточки решения SolutionOperation бланк');
                        }
                    }
                }
            }


            $response = self::GetSituationSolution('{"situation_solution_id":' . $situation_solution_id . '}');
            if ($response['status'] == 1) {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                $new_situation_solution = $response['Items'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception($method_name . "ошибка получения нового сгенерированного решения ситуации");
            }

            $response = WebsocketController::SendMessageToWebSocket('situationEliminationNewSituationSolution', $new_situation_solution);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка отправки данных на вебсокет');
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
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $ex) {
            $errors[] = $method_name . 'Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => $new_situation_solution, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    // actionCreateSolutionBySituation - тестовый отладочный метод по созданию решения на основе ситуации
    // пример: 127.0.0.1/eds-front/create-solution-by-situation?situation_id=1&situation_journal_id=2
    public function actionCreateSolutionBySituation()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();
        $method_name = "actionCreateSolutionBySituation. ";

        try {
            // получаем данные с фронта
            $post = Assistant::GetServerMethod();                                                                         //получение данных от ajax-запрос
            $situation_id = $post['situation_id'];
            $situation_journal_id = $post['situation_journal_id'];

            $response = self::CreateSolutionBySituation($situation_id, $situation_journal_id);
            if ($response['status'] == 1) {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                $result = $response['Items'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception($method_name . "ошибка создания решения по ситуации");
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . 'Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }


    // SaveSolutionCardEdit - метод сохранения карточки решения ситуации при редактировании
    // входные данные:
    //  solution_card:
//      situation_solution_id           - ключ решения в целом
    //      solution_id: null,                      // id карты решения/устранения
    //      solution_title: '',                     // название решения/устранения
    //      solution_parent_id: null,               // id родителя решения/устранения
    //      solution_child_id: null,                // id наследника решения/устранения (по сути ключ который разрываем - куда вставляем карточку новую)
    //      solution_parent_end_flag: 0,            // флаг первого/последнего действия (2 - последнее действие, 1 - первое действие, 0 - обычное действие)
    //      solution_number: 0,                     // номер карточки по порядку в решении
    //      solution_type: 'positive',              // тип карточки решения - действие в срок (карточка положительная), действие просрочено, карточка отрицательная
    //      child_action_id_positive: null,         // ключ действия при положительном исходе по решению карточки
    //      child_action_id_negative: null,         // ключ действия при отрицательном исходе по решению карточки
    //      x: 0,                                   // координата абсциссы карточки решения/устранения
    //      y: 0,                                   // координата ординаты карточки решения/устранения
    //      responsible_position_id: null,          // ключ должности последнего сменившего статус
    //      responsible_worker_id: null,            // ключ последнего ответственного сменившего статус (id работника - табельный)
    //      status_id: null,                        // ключ последнего статуса
    //      date_time: "",                          // дата и время последнего изменения статуса
    //      regulation_time: '-1',                  // регламентное время выполнения действия (-1 до устранения события/ любое целое цисло)
    //      solution_date_time_start: "",           // начало выполенния действия (используется для определения остатка на устранение решения)
    //      finish_flag_mode: 'auto',               // тип действия завершения (auto - автоматическое действие, manual - ручное)
    //      expired_indicator_flag: 0,              // флаг установки индикатора просрочки действия
    //      expired_indicator_mode: 'auto',         // тип действия просрочки (auto - автоматическое действие, manual - ручное)
    //      description: "",                        // комментарий ответственного при изменении/неизменении статуса
    //      solutionStatuses: {},                   // история изменения статуса решения/устранения ситуации
    //              {solution_card_status_id}
    //                  solution_card_status_id:        - ключ статуса карточки решения
    //                  worker_id:                      - ключ работника изменившего карточку решения
    //                  status_id: null,                - статус карточки (выполнена или нет)
    //                  date_time:                      - дата и время изменения
    //                  description:                    - описание изменения
    // выходные данные:
    //      - типовой набор
    //  solution_card:
    //      situation_solution_id           - ключ решения в целом
    //      solution_id: null,                      // id карты решения/устранения
    //      solution_title: '',                     // название решения/устранения
    //      solution_parent_id: null,               // id родителя решения/устранения
    //      solution_child_id: null,                // id наследника решения/устранения (по сути ключ который разрываем - куда вставляем карточку новую)
    //      solution_parent_end_flag: 0,            // флаг первого/последнего действия (2 - последнее действие, 1 - первое действие, 0 - обычное действие)
    //      solution_number: 0,                     // номер карточки по порядку в решении
    //      solution_type: 'positive',              // тип карточки решения - действие в срок (карточка положительная), действие просрочено, карточка отрицательная
    //      child_action_id_positive: null,         // ключ действия при положительном исходе по решению карточки
    //      child_action_id_negative: null,         // ключ действия при отрицательном исходе по решению карточки
    //      x: 0,                                   // координата абсциссы карточки решения/устранения
    //      y: 0,                                   // координата ординаты карточки решения/устранения
    //      responsible_position_id: null,          // ключ должности последнего сменившего статус
    //      responsible_worker_id: null,            // ключ последнего ответственного сменившего статус (id работника - табельный)
    //      status_id: null,                        // ключ последнего статуса
    //      date_time: "",                          // дата и время последнего изменения статуса
    //      regulation_time: '-1',                  // регламентное время выполнения действия (-1 до устранения события/ любое целое цисло)
    //      solution_date_time_start: "",           // начало выполенния действия (используется для определения остатка на устранение решения)
    //      finish_flag_mode: 'auto',               // тип действия завершения (auto - автоматическое действие, manual - ручное)
    //      expired_indicator_flag: 0,              // флаг установки индикатора просрочки действия
    //      expired_indicator_mode: 'auto',         // тип действия просрочки (auto - автоматическое действие, manual - ручное)
    //      description: "",                        // комментарий ответственного при изменении/неизменении статуса
    //      solutionStatuses: {},                   // история изменения статуса решения/устранения ситуации
    //              {solution_card_status_id}
    //                  solution_card_status_id:        - ключ статуса карточки решения
    //                  worker_id:                      - ключ работника изменившего карточку решения
    //                  status_id: null,                - статус карточки (выполнена или нет)
    //                  date_time:                      - дата и время изменения
    //                  description:                    - описание изменения
    // пример: http://127.0.0.1/read-manager-amicum?controller=EdsFront&method=SaveSolutionCardEdit&subscribe=&data={"solution_card":{}}
    public static function SaveSolutionCardEdit($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'SaveSolutionCardEdit. ';                                                                       // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                       // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                     // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                   // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                           // время начала выполнения метода
        $date_time_debug_end = null;

        //базовые параметры скрипта
        $errors = array();
        $save_solution_card = (object)array();
        $status = 1;
        $warnings = array();
        $count_add = 0;
        $count_add_full = 0;
        $count_update = 0;

        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
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

            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . 'Не переданы входные параметры');
            }
            $warnings[] = $method_name . 'Данные успешно переданы';
            $warnings[] = $method_name . 'Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . 'Декодировал входные параметры';
            if (!property_exists($post_dec, 'solution_card'))                                                     // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . 'Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . 'Данные с фронта получены';

            $save_solution_card = $post_dec->solution_card;

            // сохраняем новую карточку - которую только что добавили
            $solution_card = SolutionCard::findOne(['id' => $save_solution_card->solution_id]);
            if (!$solution_card) {
                $solution_card = new SolutionCard();
            }

            $date_time = Assistant::GetDateNow();
            $session = Yii::$app->session;

            $solution_card->situation_solution_id = $save_solution_card->situation_solution_id;
            $solution_card->title = $save_solution_card->solution_title;
            $solution_card->solution_parent_id = $save_solution_card->solution_parent_id;
            $solution_card->solution_parent_end_flag = $save_solution_card->solution_parent_end_flag;
            $solution_card->solution_number = $save_solution_card->solution_number;
            $solution_card->solution_type = $save_solution_card->solution_type;
            $solution_card->child_action_id_positive = $save_solution_card->child_action_id_positive;
            $solution_card->child_action_id_negative = $save_solution_card->child_action_id_negative;
            $solution_card->x = $save_solution_card->x;
            $solution_card->y = $save_solution_card->y;
            $solution_card->responsible_position_id = $session['position_id'];
            $solution_card->responsible_worker_id = $session['worker_id'];
            $solution_card->status_id = $save_solution_card->status_id;
            $solution_card->date_time = $date_time;
            $solution_card->regulation_time = $save_solution_card->regulation_time;
            $solution_card->solution_date_time_start = $save_solution_card->solution_date_time_start;
            $solution_card->finish_flag_mode = $save_solution_card->finish_flag_mode;
            $solution_card->expired_indicator_flag = $save_solution_card->expired_indicator_flag;
            $solution_card->expired_indicator_mode = $save_solution_card->expired_indicator_mode;
            $solution_card->description = "";

            if ($solution_card->save()) {
                $solution_card_id = $solution_card->id;
                $save_solution_card->solution_id = $solution_card_id;
            } else {
                $errors[] = $solution_card->errors;
                throw new Exception($method_name . '. Ошибка при сохранении карточки решения SolutionCard');
            }

            // изменяем дочернюю карточку
            $child_solution_card = SolutionCard::findOne(['id' => $save_solution_card->solution_child_id]);
            if ($child_solution_card) {
                $child_solution_card->solution_parent_id = $solution_card_id;
                if (!$child_solution_card->save()) {
                    $errors[] = $child_solution_card->errors;
                    throw new Exception($method_name . '. Ошибка при сохранении дочерней карточки решения SolutionCard метод выполняется при разрыве карточек');
                }
            }

            // изменяем родительскую карточку
            $parent_solution_card = SolutionCard::findOne(['id' => $save_solution_card->solution_parent_id]);
            if ($parent_solution_card) {
                $parent_solution_card->child_action_id_positive = $solution_card_id;
                if (!$parent_solution_card->save()) {
                    $errors[] = $parent_solution_card->errors;
                    throw new Exception($method_name . '. Ошибка при сохранении родительской карточки решения SolutionCard метод выполняется при разрыве карточек');
                }
            }


            //сохраняем новый статус карточки решения
            $solution_card_status = new SolutionCardStatus();
            $solution_card_status->solution_card_id = $solution_card_id;
            $solution_card_status->worker_id = $session['worker_id'];
            $solution_card_status->status_id = $save_solution_card->status_id;
            $solution_card_status->date_time = $date_time;
            $solution_card_status->description = "";
            if ($solution_card_status->save()) {
                $solution_card_status_id = $solution_card_status->id;
            } else {
                $errors[] = $solution_card_status->errors;
                throw new Exception($method_name . '. Ошибка при сохранении статуса карточки решения SolutionCardStatus');
            }
            //      solutionStatuses: {},                   // история изменения статуса решения/устранения ситуации
            //              {solution_card_status_id}
            //                  solution_card_status_id:        - ключ статуса карточки решения
            //                  worker_id:                      - ключ работника изменившего карточку решения
            //                  status_id: null,                - статус карточки (выполнена или нет)
            //                  date_time:                      - дата и время изменения
            //                  description:                    - описание изменения

            $save_solution_card->solutionStatuses->{$solution_card_status_id} = (object)[
                'solution_card_status_id' => $solution_card_status_id,
                'worker_id' => $session['worker_id'],
                'date_time' => $date_time,
                'description' => "",
                'status_id' => $save_solution_card->status_id
            ];

            // сохраняем связь журнала ситуаций и решений
//            $save_solution_card->solutionOperations = (object)array();

            $response = WebsocketController::SendMessageToWebSocket('situationEliminationSaveSolutionCardEdit', $save_solution_card);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка отправки данных на вебсокет');
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
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $ex) {
            $errors[] = $method_name . 'Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => $save_solution_card, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    // DeleteSolutionCard - метод удаления карточки решения/устранения
    // входные данные:
    //      situation_solution_id           - ключ решения ситуации
    //      solution_id                     - ключ карточки решения ситуации
    // выходные данные:

    // пример: http://127.0.0.1/read-manager-amicum?controller=EdsFront&method=DeleteSolutionCard&subscribe=&data={"solution_id":2,"situation_solution_id":2}
    public static function DeleteSolutionCard($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'DeleteSolutionCard. ';                                                                                 // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                       // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                     // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                   // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                           // время начала выполнения метода
        $date_time_debug_end = null;

        //базовые параметры скрипта
        $errors = array();
        $result = array();
        $status = 1;
        $warnings = array();
        $count_add = 0;
        $count_add_full = 0;
        $count_update = 0;

        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
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

            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . 'Не переданы входные параметры');
            }
            $warnings[] = $method_name . 'Данные успешно переданы';
            $warnings[] = $method_name . 'Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . 'Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'solution_id') or
                !property_exists($post_dec, 'situation_solution_id')
            )                                                     // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . 'Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . 'Данные с фронта получены';

            $solution_id = $post_dec->solution_id;

            $result = SolutionCard::deleteAll(['id' => $solution_id]);

            $response = WebsocketController::SendMessageToWebSocket('situationEliminationDeleteSolutionCard', $post_dec);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка отправки данных на вебсокет');
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
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $ex) {
            $errors[] = $method_name . 'Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => $result, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }


    // EditSolutionOperation - метод изменения операции карточки решения/устранения ситуации
    // входные данные:
    //  solution_operation:
    //      solution_operation_id: null,            // id привязки операции к решению/устранению ситуации
    //      operation_id: null,                     // id операции
    //      description: "",                        // описание операции
    //      operation_type: 'manual',               // тип действия (manual - ручное, auto - автоматическое)
    //      on_shift: 1,                            // оповещать работника на смене или первого на участке с такой должностью
    //      status_id: null,                        // статус операции (выполнена или нет)
    //      equipment_id: null,                     // ключ оборудования
    //      worker_id: null,                        // ключ работника
    //      position_id: null,                      // ключ должности
    //      company_department_id: null,            // ключ департамента работника
    //      date_time: "",                          // дата и время последнего изменения статуса
    //      solutionOperationStatuses: {}           // статусы изменения операции решения
    // выходные данные:
    // пример: http://127.0.0.1/read-manager-amicum?controller=EdsFront&method=EditSolutionOperation&subscribe=&data={"solution_operation":{"situation_solution_id":1,"solution_id":1,"status_id":50,"solution_operation_id":1}}
    public static function EditSolutionOperation($data_post = NULL)
    {
        $log = new LogAmicumFront("EditSolutionOperation");

        //базовые параметры скрипта

        $solution_operation = (object)array();

        $count_add = 0;
        $count_add_full = 0;
        $count_update = 0;

        try {
            $log->addLog("Начало выполнения метода");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $log->addLog("Данные успешно переданы");

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $log->addLog("Декодировал входные параметр");

            if (!property_exists($post_dec, 'solution_operation'))                                              // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $log->addLog("Данные с фронта получены");

            $save_solution_operation = $post_dec->solution_operation;
            $solution_operation_id = $save_solution_operation->solution_operation_id;

            $date_time = Assistant::GetDateNow();
            $session = Yii::$app->session;

            // ищем операцию карточки решения и меняем ее статус
            $solution_operation = SolutionOperation::findOne(['id' => $solution_operation_id]);
            if (!$solution_operation) {
                $solution_operation = new SolutionOperation();
            }

            //      solution_operation_id: null,            // id привязки операции к решению/устранению ситуации
            //      operation_id: null,                     // id операции
            //      description: "",                        // описание операции
            //      operation_type: 'manual',               // тип действия (manual - ручное, auto - автоматическое)
            //      on_shift: 1,                            // оповещать работника на смене или первого на участке с такой должностью
            //      status_id: null,                        // статус операции (выполнена или нет)
            //      equipment_id: null,                     // ключ оборудования
            //      worker_id: null,                        // ключ работника
            //      position_id: null,                      // ключ должности
            //      company_department_id: null,            // ключ департамента работника
            //      date_time: "",                          // дата и время последнего изменения статуса
            //      solutionOperationStatuses: {}           // статусы изменения операции решения
            if (!$save_solution_operation->status_id) {
                $status_id = 111;
            } else {
                $status_id = $save_solution_operation->status_id;
            }

            $solution_operation->operation_id = $save_solution_operation->operation_id;
            $solution_operation->description = $save_solution_operation->description;
            $solution_operation->operation_type = $save_solution_operation->operation_type;
            $solution_operation->on_shift = $save_solution_operation->on_shift;
            $solution_operation->status_id = $status_id;
            $solution_operation->equipment_id = $save_solution_operation->equipment_id;
            $solution_operation->worker_id = $save_solution_operation->worker_id;
            $solution_operation->position_id = $save_solution_operation->position_id;
            $solution_operation->solution_card_id = $save_solution_operation->solution_card_id;
            $solution_operation->company_department_id = $save_solution_operation->company_department_id;
            $solution_operation->date_time = $date_time;

            if (!$solution_operation->save()) {
                $log->addData($solution_operation->errors, '$solution_operation->errors', __LINE__);
                throw new Exception('Ошибка при сохранении операции карточки решения SolutionOperation');
            }

            $solution_operation->refresh();
            $save_solution_operation->solution_operation_id = $solution_operation->id;

            //сохраняем новый статус карточки решения
            $solution_operation_status = new SolutionOperationStatus();
            $solution_operation_status->solution_operation_id = $save_solution_operation->solution_operation_id;
            $solution_operation_status->worker_id = $session['worker_id'];
            $solution_operation_status->status_id = $status_id;
            $solution_operation_status->date_time = $date_time;
            $solution_operation_status->description = "";
            if (!$solution_operation_status->save()) {
                $log->addData($solution_operation_status->errors, '$solution_operation_status->errors', __LINE__);
                throw new Exception('Ошибка при сохранении статуса операции карточки решения SolutionOperationStatus');
            }

            $solution_operation_status_id = $solution_operation_status->id;

            // solutionOperationStatuses: {}           // статусы изменения операции решения
            //         {solution_operation_status_id}
            //                 solution_operation_status_id        - ключ статуса изменения операции решения
            //                 worker_id:                          - ключ работника изменившего операцию решения
            //                 status_id: null,                    - статус операции (выполнена или нет)
            //                 date_time:                          - дата и время изменения
            //                 description:                        - описание изменения

            if (!property_exists($save_solution_operation, "solutionOperationStatuses")) {
                $save_solution_operation->solutionOperationStatuses = (object)array();
            }
            $save_solution_operation->solutionOperationStatuses->{$solution_operation_status_id} = (object)[
                'solution_operation_status_id' => $solution_operation_status_id,
                'worker_id' => $session['worker_id'],
                'date_time' => $date_time,
                'description' => "",
                'status_id' => $save_solution_operation->status_id
            ];

            $response = WebsocketController::SendMessageToWebSocket('situationEliminationEditSolutionOperation', $save_solution_operation);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка отправки данных на вебсокет');
            }

            $log->addLog("Окончание выполнения метода");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $save_solution_operation], $log->getLogAll());
    }


    // DeleteSolutionOperation - метод удаления карточки решения/устранения
    // входные данные:
    //      situation_solution_id           - ключ решения ситуации
    //      solution_id                     - ключ карточки решения ситуации
    //      solution_operation_id           - ключ операции карточки решения ситуации
    // выходные данные:

    // пример: http://127.0.0.1/read-manager-amicum?controller=EdsFront&method=DeleteSolutionOperation&subscribe=&data={"solution_operation_id":2}
    public static function DeleteSolutionOperation($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'DeleteSolutionOperation. ';                                                                                 // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                       // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                     // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                   // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                           // время начала выполнения метода
        $date_time_debug_end = null;

        //базовые параметры скрипта
        $errors = array();
        $result = array();
        $status = 1;
        $warnings = array();
        $count_add = 0;
        $count_add_full = 0;
        $count_update = 0;

        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
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

            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . 'Не переданы входные параметры');
            }
            $warnings[] = $method_name . 'Данные успешно переданы';
            $warnings[] = $method_name . 'Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . 'Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'solution_id') or
                !property_exists($post_dec, 'solution_operation_id') or
                !property_exists($post_dec, 'situation_solution_id')
            ) {
                throw new Exception($method_name . 'Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . 'Данные с фронта получены';

            $solution_operation_id = $post_dec->solution_operation_id;

            $result = SolutionOperation::deleteAll(['id' => $solution_operation_id]);

            $response = WebsocketController::SendMessageToWebSocket('situationEliminationDeleteSolutionOperation', $post_dec);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка отправки данных на вебсокет');
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
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $ex) {
            $errors[] = $method_name . 'Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => $result, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    // GetDispatcherStatistics - метод получения индивидуальных показателей диспетчера при решении ситуаций
    // входные данные:
    //      worker_id                   - ключ диспетчера
    // выходные данные:
    //      solved_situations_percent   - % своевременно решеных ситуаций
    //      average_speed               - Средняя скорость решения ситуации
    // пример: http://127.0.0.1/read-manager-amicum?controller=EdsFront&method=GetDispatcherStatistics&subscribe=&data={"worker_id":2}
    public static function GetDispatcherStatistics($data_post = NULL)
    {
        $result = array(
            'solved_situations_percent' => 0,
            'average_speed' => 0,
        );                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("GetDispatcherStatistics");

        try {
            $log->addLog("Начало выполнения метода");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $log->addLog("Данные успешно переданы");
            $log->addData($data_post, '$data_post', __LINE__);

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'worker_id')
            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $worker_id = $post_dec->worker_id;

            $count_all_solution = SolutionCard::find()
                ->where(['responsible_worker_id' => $worker_id])
                ->count();

            $count_expired_solution = SolutionCard::find()
                ->where(['responsible_worker_id' => $worker_id])
                ->andWhere(['expired_indicator_flag' => 1])
                ->count();

            $log->addData($count_all_solution, '$count_all_solution', __LINE__);
            $log->addData($count_expired_solution, '$count_expired_solution', __LINE__);

            if ($count_all_solution and $count_expired_solution) {
                $result['solved_situations_percent'] = round(($count_expired_solution / $count_all_solution * 100), 1);
            } else {
                $result['solved_situations_percent'] = 0;
            }

            $log->addLog("Посчитал % решенных ситуаций");

            $average_speed = (new Query())
                ->select('AVG(TIMEDIFF(`date_time`, `solution_date_time_start`)) as average_speed')
                ->from('solution_card')
                ->where('solution_date_time_start is not null')
                ->scalar();

            if ($average_speed) {
                $result['average_speed'] = round($average_speed, 1);
            } else {
                $result['average_speed'] = 0;
            }


            $log->addLog("Окончание выполнения метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    // GetJournalSituationByYear - метод получения журнала ситуаций на год
    // входные параметры:
    //      year                    - год на который получаем журнал
    //      mine_id                 - ключ шахты
    // выходной объект:
    //  {situation_journal_id}
    //      situation_id:                                                   - ключ ситуации
    //      situation_title                                                 - название ситуации
    //      group_situation_id                                              - ключ группы ситуации
    //      group_situation_title                                           - название группы ситуации
    //      status_checked                                                  - статус проверки (1 выполнена, 0 не выполнена)
    //      situation_journal_id	    "1578570"                           - ключ журнала ситуаций
    //      situation_date_time_create          "2019-09-19 09:24:46"       - дата создания ситуации
    //      situation_date_time_create_format	"19.09.2019 09:24:46"       - форматированная дата создания ситуации
    //      situation_date_time_start           "2019-09-19 09:24:46"       - дата начала ситуации
    //      situation_date_time_start_format	"19.09.2019 09:24:46"       - форматированная дата начала ситуации
    //      situation_date_time_end             "2019-09-19 09:24:46"       - дата окончания ситуации
    //      situation_date_time_end_format	    "19.09.2019 09:24:46"       - форматированная дата окончания ситуации
    //      situation_status_id	        "1502783"                           - ключ журнала статусов ситуации
    //      status_id	                "38"                                - ключ последней ситуации
    //      kind_reason_id	            "1"                                 - ключ причины ситуации
    //      worker_id	                "1"                                 - ключ работника
    //      description	                "выапывап"                          - описание ситуации
    //      status_date_time	        "19.09.2019 10:23:46"               - дата изменения статуса ситуации
    //      duration	                null/60 (мин)                       - продолжительность ситуации (если статус не 40 и не 52 то считается)
    //      places:                                                         - зона опасной ситуации
    //          {place_id}
    //              place_id                                                    - ключ места в котором произошла ситуация
    //              place_title                                                 - наименование места в котором произошло событие
    // разработал: Якимов М.Н.
    // дата: 07.12.2019г
    // пример:
    // http://127.0.0.1/read-manager-amicum?controller=EdsFront&method=GetJournalSituationByYear&subscribe=&data={%22year%22:%222020%22,%22mine_id%22:%22270%22}
    public static function GetJournalSituationByYear($data_post = NULL)
    {
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("GetJournalSituationByYear");
        $result = (object)array();
        try {
            $log->addLog("Начало выполнения метода");

            /** Метод начало */
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('Данные с фронта не получены');
            }
            $log->addLog("Данные успешно переданы");
            $log->addLog('Входной массив данных' . $data_post);

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $log->addLog("Декодировал входные параметры");

            if (
                !(property_exists($post_dec, 'year')) ||
                !(property_exists($post_dec, 'month')) ||
                !(property_exists($post_dec, 'mine_id'))

            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $log->addLog("Данные с фронта получены и они правильные");

            $mine_id = $post_dec->mine_id;
            $year = $post_dec->year;
            $month = $post_dec->month+1;

            $filter_mine = array();
            if ($mine_id != -1) {
                $filter_mine = ['situation_journal.mine_id' => $mine_id];
            }

            $filter_month = "MONTH(situation_journal.date_time)=" . $month;
            $filter_year = "YEAR(situation_journal.date_time)=" . $year;

            // получаем события из заданный период из таблицы $situation_journal
            $situation_journal = SituationJournal::find()
                ->select([
                    'situation_journal.id as id',
                    'situation.id as situation_id',
                    'situation.title as situation_title',
                    'situation_journal.status_id as status_id_situation',
                    'situation_journal.date_time as date_time_create',
                    'situation_journal.date_time_start as date_time_start',
                    'situation_journal.date_time_end as date_time_end',
                    'situation_status.id as situation_status_id',
                    'situation_status.kind_reason_id as kind_reason_id',
                    'situation_status.worker_id as worker_id',
                    'situation_status.description as description',
                    'situation_status.date_time as status_date_time',
                    'situation_solution.regulation_time as regulation_time',
                    'status.title as status_title'
                ])
                ->joinWith('situationJournalZones.edge.place')
                ->joinWith('situation.groupSituation')
                ->joinWith('situationJournalSituationSolutions.situationSolution')
                ->joinWith('situationStatuses.worker.employee')
                ->innerJoin('status', 'situation_journal.status_id = status.id')
                ->where($filter_year)
                ->andWhere($filter_month)
                ->andFilterWhere($filter_mine)
                ->orderBy(['status_date_time' => SORT_ASC, 'date_time_create' => SORT_ASC])
                ->asArray()
                ->all();

            $log->addData($situation_journal, '$situation_journal', __LINE__);
            $log->addLog("Получил данные с БД");

            foreach ($situation_journal as $situation_ab) {
                $count_record++;
                $sit_journal_id = $situation_ab['id'];
                $journal_AB[$sit_journal_id]['situation_id'] = $situation_ab['situation_id'];
                $journal_AB[$sit_journal_id]['situation_title'] = $situation_ab['situation_title'];
                $journal_AB[$sit_journal_id]['group_situation_id'] = $situation_ab['situation']['groupSituation']['id'];
                $journal_AB[$sit_journal_id]['group_situation_title'] = $situation_ab['situation']['groupSituation']['title'];
                $journal_AB[$sit_journal_id]['status_checked'] = 1;
                $situation_date_time = $situation_ab['date_time_create'];
                $journal_AB[$sit_journal_id]['date_time_create'] = $situation_ab['date_time_create'];
                if ($situation_ab['date_time_create']) {
                    $journal_AB[$sit_journal_id]['date_time_create_format'] = date('d.m.Y H:i:s', strtotime($situation_ab['date_time_create']));
                }
                $journal_AB[$sit_journal_id]['date_time_start'] = $situation_ab['date_time_start'];
                if ($situation_ab['date_time_start']) {
                    $journal_AB[$sit_journal_id]['date_time_start_format'] = date('d.m.Y H:i:s', strtotime($situation_ab['date_time_start']));
                } else {
                    $journal_AB[$sit_journal_id]['date_time_start_format'] = "";
                }
                $journal_AB[$sit_journal_id]['date_time_end'] = $situation_ab['date_time_end'];

                if ($situation_ab['date_time_end']) {
                    $journal_AB[$sit_journal_id]['date_time_end_format'] = date('d.m.Y H:i:s', strtotime($situation_ab['date_time_end']));
                    $date_time_end = $situation_ab['date_time_end'];
                } else {
                    $journal_AB[$sit_journal_id]['date_time_end_format'] = "";
                    $date_time_end = BackendAssistant::GetDateNow();
                }

                $journal_AB[$sit_journal_id]['regulation_time'] = $situation_ab['regulation_time'];
                $journal_AB[$sit_journal_id]['situation_journal_id'] = $sit_journal_id;
                $journal_AB[$sit_journal_id]['status_id'] = $situation_ab['status_id_situation'];
                $journal_AB[$sit_journal_id]['status_title'] = $situation_ab['status_title'];
//                $journal_AB[$sit_journal_id]['situation_status_id'] = $situation_ab['situation_status_id'];
//                $journal_AB[$sit_journal_id]['status_date_time'] = $situation_ab['status_date_time'];
//                $journal_AB[$sit_journal_id]['worker_id'] = $situation_ab['worker_id'];
//                $journal_AB[$sit_journal_id]['kind_reason_id'] = $situation_ab['kind_reason_id'];
//                $journal_AB[$sit_journal_id]['description'] = $situation_ab['description'];
                if (isset($situation_ab['situationStatuses'])) {
                    foreach ($situation_ab['situationStatuses'] as $situation_statuse) {
                        $journal_AB[$sit_journal_id]['situation_status_id'] = $situation_statuse['id'];
                        $journal_AB[$sit_journal_id]['status_date_time'] = $situation_statuse['date_time'];
                        $journal_AB[$sit_journal_id]['worker_id'] = $situation_statuse['worker_id'];
                        if (isset($situation_statuse['worker']) and isset($situation_statuse['worker']['employee'])) {
                            $journal_AB[$sit_journal_id]['worker_full_name'] = $situation_statuse['worker']['employee']['last_name'] . " " . $situation_statuse['worker']['employee']['first_name'] . " " . $situation_statuse['worker']['employee']['patronymic'];
                        } else {
                            $journal_AB[$sit_journal_id]['worker_full_name'] = "";
                        }
                        $journal_AB[$sit_journal_id]['kind_reason_id'] = $situation_statuse['kind_reason_id'];
                        $journal_AB[$sit_journal_id]['description'] = $situation_statuse['description'];
                    }
                }

                $journal_AB[$sit_journal_id]['duration'] = round((strtotime($date_time_end) - strtotime($situation_date_time)) / 60, 0);
//                $journal_AB[$sit_journal_id]['durationFloat'] = (strtotime($date_time_end) - strtotime($situation_date_time)) / 60;


                // получение списка мест и выработок
                if ($situation_ab['situationJournalZones']) {
                    foreach ($situation_ab['situationJournalZones'] as $situationZone) {
                        $journal_AB[$sit_journal_id]['places'][$situationZone['edge']['place']['id']] =
                            array('place_id' => $situationZone['edge']['place']['id'],
                                'place_title' => $situationZone['edge']['place']['title']);

                    }
                } else {
                    $journal_AB[$sit_journal_id]['places'] = (object)array();
                }

                // получение решений ситуаций
                if ($situation_ab['situationJournalSituationSolutions']) {
                    foreach ($situation_ab['situationJournalSituationSolutions'] as $situationSolution) {
                        $journal_AB[$sit_journal_id]['situation_solution_id'] = $situationSolution['situation_solution_id'];
                    }
                } else {
                    $journal_AB[$sit_journal_id]['situation_solution_id'] = null;
                }

            }
            if (isset($journal_AB)) {
                $result = $journal_AB;
            }

            $log->addLog("Окончание выполнения метода", $count_record);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    // GetDangerLevelMines - получение статистики по ситуациям по году
    // метод ждет:
    //      year:       2019        - год, на который формируем статистику
    //      mine_id:    290         - шахта, по которой нужна статистика (не обязательный параметр)
    // выходной массив:
    //      mine_id                                 - ключ шахты
    //      mine_title                              - название шахты
    //      danger_level_full                       - максимальный ровень риска при котором случается тяжелый случай
    //      total_situations_count                  - общее количество ситуаций за год
    //      danger_level_by_mine                    - уровень опасности за год
    //      statistic_situation_by_year             - количество ситуаций по месяцам
    //              [0,0,0,0,0,0,0,0,0,0,0,0]           - на каждый месяц количество
    //      danger_level_by_year                    - уровень опасности по месяцам
    //              [0,0,0,0,0,0,0,0,0,0,0,0]           - на каждый месяц уровень риска
    // http://127.0.0.1/read-manager-amicum?controller=EdsFront&method=GetDangerLevelMines&subscribe=&data={%22year%22:%222020%22}
    // http://127.0.0.1/read-manager-amicum?controller=EdsFront&method=GetDangerLevelMines&subscribe=&data={%22year%22:%222020%22,%22mine_id%22:%22290%22}
    public static function GetDangerLevelMines($data_post = NULL)
    {
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("GetDangerLevelMines");
        $result = (object)array();
        try {
            $log->addLog("Начало выполнения метода");
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('Данные с фронта не получены');
            }
            $log->addLog("Данные успешно переданы");
            $log->addLog("Входной массив данных" . $data_post);

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $log->addLog("Декодировал входные параметры");

            if (
                !property_exists($post_dec, 'year')
            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $log->addLog("Данные с фронта получены и они правильные");

            $year = $post_dec->year;

            $filter_mine_mine_id = array();
            $filter_mine_id = array();

            if (property_exists($post_dec, 'mine_id') and (int)$post_dec->mine_id != -1) {
                $filter_mine_mine_id = ['mine_id' => $post_dec->mine_id];
                $filter_mine_id = ['id' => $post_dec->mine_id];
            }

            $mines = Mine::find()->where($filter_mine_id)->asArray()->all();

            $log->addLog("Получил список шахт");

            if ($mines) {
                // получаем общее количество ситуаций по месяцам
                $total_situations_count_raws = SituationJournal::find()
                    ->select([
                        'COUNT(situation_journal.id) as count_situation_all',
                        'MONTH(situation_journal.date_time) as month_count',
                        'situation_journal.mine_id as mine_id'
                    ])
                    ->where("YEAR(situation_journal.date_time)='" . $year . "'")
                    ->andFilterWhere($filter_mine_mine_id)
                    ->groupBy('mine_id, month_count')
                    ->asArray()
                    ->all();
                foreach ($total_situations_count_raws as $raw) {
                    $total_situations_count[$raw['mine_id']][$raw['month_count']] = $raw;
                }
                unset($total_situations_count_raws);

                foreach ($mines as $mine) {
                    for ($i = 1; $i <= 12; $i++) {
                        $situation_result[$mine['id']]['mine_id'] = $mine['id'];
                        $situation_result[$mine['id']]['mine_title'] = $mine['title'];
                        $situation_result[$mine['id']]['danger_level_full'] = 1000;

                        if (!isset($situation_result[$mine['id']]['total_situations_count'])) {
                            $situation_result[$mine['id']]['total_situations_count'] = 0;
                        }

                        if (isset($total_situations_count[$mine['id']][$i])) {
                            $situation_result[$mine['id']]['statistic_situation_by_year'][] = (int)$total_situations_count[$mine['id']][$i]['count_situation_all'];
                            if ($situation_result[$mine['id']]['total_situations_count'] < (int)$total_situations_count[$mine['id']][$i]['count_situation_all']) {
                                $situation_result[$mine['id']]['total_situations_count'] = (int)$total_situations_count[$mine['id']][$i]['count_situation_all'];
                            }
                            $situation_result[$mine['id']]['danger_level_by_year'][] =
                                round(
                                    ($total_situations_count[$mine['id']][$i]['count_situation_all'] /
                                        $situation_result[$mine['id']]['danger_level_full']) * 100,
                                    1
                                );
                        } else {
                            $situation_result[$mine['id']]['statistic_situation_by_year'][] = 0;
                            $situation_result[$mine['id']]['danger_level_by_year'][] = 0;
                        }
                    }
                    $situation_result[$mine['id']]['danger_level_by_mine'] = round(($situation_result[$mine['id']]['total_situations_count'] / ($situation_result[$mine['id']]['danger_level_full'] * 12)) * 100, 1);
                }
                unset($total_situations_count);

                $log->addLog("получил общее количество ситуаций по месецам");
            }


            $result = $situation_result;

            $log->addLog("Окончание выполнения метода", $count_record);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    // GetInjunctionsByCompany - метод получения статистики предписаний по подразделению
    // метод ждет:
    //      company_id:     290             - подразделение, на которое нужно получить список предписаний
    //      date_time:      "2020-11-31"    - дата, по которую получаем статистику с начала года
    // выходной массив:
    //      company_department_id                   - ключ подразделения
    //      company_title                           - название подразделения
    //      count_new                               - количество предписаний со статусом новое
    //      count_work                              - количество предписаний со статусом в работе
    //      count_expired                           - количество предписаний со статусом просрочено
    //      count_done                              - количество предписаний со статусом выполнено

    // http://127.0.0.1/read-manager-amicum?controller=EdsFront&method=GetInjunctionsByCompany&subscribe=&data={%22company_id%22:%224029720%22,%22date_time%22:%222020-11-31%22}
    public static function GetInjunctionsByCompany($data_post = NULL)
    {
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("GetInjunctionsByCompany");
        $result = (object)array();
        try {
            $log->addLog("Начало выполнения метода");
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('Данные с фронта не получены');
            }
            $log->addLog("Данные успешно переданы");
            $log->addLog("Входной массив данных" . $data_post);

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $log->addLog("Декодировал входные параметры");

            if (
                !property_exists($post_dec, 'company_id') ||
                !property_exists($post_dec, 'date_time')
            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $log->addLog("Данные с фронта получены и они правильные");

            $comp_dep_id = $post_dec->company_id;
            $date_time = date("Y-m-d", strtotime($post_dec->date_time));
            $year = date("Y", strtotime($post_dec->date_time));

            if ($comp_dep_id == 501) {
                $response = DepartmentController::FindDepartment(4029926);
                if ($response['status'] === 0) {
                    $log->addLogAll($response);
                    throw new Exception('Ошибка при получении вложенных участков');
                }
                $company_departments = $response['Items'];
                $response = DepartmentController::FindDepartment(4029860);
                if ($response['status'] === 0) {
                    $log->addLogAll($response);
                    throw new Exception('Ошибка при получении вложенных участков');
                }
                $company_departments = array_merge($company_departments, $response['Items']);
            } else {
                $response = DepartmentController::FindDepartment($comp_dep_id);
                if ($response['status'] === 0) {
                    $log->addLogAll($response);
                    throw new Exception('Ошибка при получении вложенных участков');
                }
                $company_departments = $response['Items'];
            }

            $log->addLog("Получил список департаментов вложенных");
            unset($response);

            // 1	Предписание
            // 2	ПАБ
            // 3	Предписание РТН
            // 4	Нарушение/несоответствие
            $kind_documents = [1, 3];

            $found_data_injunction = Checking::find()
                ->joinWith('companyDepartment.company')
                ->joinWith('injunctions')
                ->where(['in', 'injunction.kind_document_id', $kind_documents])
                ->andWhere('YEAR(checking.date_time_start)=' . $year)
                ->andWhere(['<=', 'checking.date_time_start', $date_time . ' 23:59:59'])
                ->andWhere(['in', 'checking.company_department_id', $company_departments])
                ->all();

            $log->addLog("Получил список предписаний");

            if ($found_data_injunction) {
                foreach ($found_data_injunction as $checking) {
                    $comp_dep_id = $checking->company_department_id;
                    if (!isset($archive_injunction[$comp_dep_id])) {
                        $archive_injunction[$comp_dep_id]['company_department_id'] = $comp_dep_id;
                        $archive_injunction[$comp_dep_id]['company_title'] = $checking->companyDepartment->company->title;
                        $archive_injunction[$comp_dep_id]['count_new'] = 0;
                        $archive_injunction[$comp_dep_id]['count_work'] = 0;
                        $archive_injunction[$comp_dep_id]['count_expired'] = 0;
                        $archive_injunction[$comp_dep_id]['count_done'] = 0;
                    }

                    foreach ($checking->injunctions as $injunction) {
                        switch ($injunction->status_id) {
                            case 57:
                                $archive_injunction[$comp_dep_id]['count_new']++;
                                break;
                            case 58:
                                $archive_injunction[$comp_dep_id]['count_work']++;
                                break;
                            case 59:
                                $archive_injunction[$comp_dep_id]['count_done']++;
                                break;
                            default:
                                $archive_injunction[$comp_dep_id]['count_expired']++;
                        }
                    }
                }
                unset($found_data_injunction);
            }

            if (isset($archive_injunction)) {
                $result = $archive_injunction;
            }

            $log->addLog("Окончание выполнения метода", $count_record);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    // GetOrderByCompany - метод получения статистики нарядов по подразделению
    // метод ждет:
    //      company_id:     290             - подразделение, на которое нужно получить список предписаний
    //      date_time:      "2020-11-31"    - дата, по которую получаем статистику с начала года
    // выходной массив:
    //      company_department_id                   - ключ подразделения
    //      company_title                           - название подразделения
    //      shifts                                  - смены
    //          shift_id                                - ключ смены
    //          shift_title                             - название смены
    //          order_id                                - ключ наряда
    //          workers                                 - работники уникальные на смене
    //              {worker_id}                                 - ключ работника
    //      workers                                 - работники уникальные на сутках
    //          {worker_id}                             - ключ работника
    //      count_workers_by_day                    - количество уникальных работников на сутках
    //      count_workers_by_shift                  - количество уникальных работников на сменах

    // http://127.0.0.1/read-manager-amicum?controller=EdsFront&method=GetOrderByCompany&subscribe=&data={%22company_id%22:%224029720%22,%22date_time%22:%222020-02-28%22}
    public static function GetOrderByCompany($data_post = NULL)
    {
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("GetOrderByCompany");
        $result = (object)array();
        try {
            $log->addLog("Начало выполнения метода");
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('Данные с фронта не получены');
            }
            $log->addLog("Данные успешно переданы");
            $log->addLog("Входной массив данных" . $data_post);

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $log->addLog("Декодировал входные параметры");

            if (
                !property_exists($post_dec, 'company_id') ||
                !property_exists($post_dec, 'date_time')
            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $log->addLog("Данные с фронта получены и они правильные");

            $comp_dep_id = $post_dec->company_id;
            $date_time = date("Y-m-d", strtotime($post_dec->date_time));

            if ($comp_dep_id == 501) {
                $response = DepartmentController::FindDepartment(4029926);
                if ($response['status'] === 0) {
                    $log->addLogAll($response);
                    throw new Exception('Ошибка при получении вложенных участков');
                }
                $company_departments = $response['Items'];
                $response = DepartmentController::FindDepartment(4029860);
                if ($response['status'] === 0) {
                    $log->addLogAll($response);
                    throw new Exception('Ошибка при получении вложенных участков');
                }
                $company_departments = array_merge($company_departments, $response['Items']);
            } else {
                $response = DepartmentController::FindDepartment($comp_dep_id);
                if ($response['status'] === 0) {
                    $log->addLogAll($response);
                    throw new Exception('Ошибка при получении вложенных участков');
                }
                $company_departments = $response['Items'];
            }


            $log->addLog("Получил список департаментов вложенных");
            unset($response);


            $found_orders = Order::find()
                ->joinWith('shift')
                ->joinWith('companyDepartment.company')
                ->joinWith('orderPlaces.orderOperations.operationWorkers')
                ->andWhere(['=', 'order.date_time_create', $date_time])
                ->andWhere(['in', 'order.company_department_id', $company_departments])
                ->asArray()
                ->all();

            $log->addLog("Получил список нарядов");

            if ($found_orders) {
                foreach ($found_orders as $order) {
                    $comp_dep_id = $order['company_department_id'];

                    $archive_orders[$comp_dep_id]['company_department_id'] = $comp_dep_id;
                    $archive_orders[$comp_dep_id]['company_title'] = $order['companyDepartment']['company']['title'];
                    $shift_id = $order['shift_id'];
                    $archive_orders[$comp_dep_id]['shifts'][$shift_id]['shift_id'] = $order['shift']['id'];
                    $archive_orders[$comp_dep_id]['shifts'][$shift_id]['shift_title'] = $order['shift']['title'];
                    $archive_orders[$comp_dep_id]['shifts'][$shift_id]['order_id'] = $order['id'];

                    if (isset($order['orderPlaces'])) {
                        foreach ($order['orderPlaces'] as $order_place) {
                            if (isset($order_place['orderOperations'])) {
                                foreach ($order_place['orderOperations'] as $order_operation) {
                                    if (isset($order_operation['operationWorkers'])) {
                                        foreach ($order_operation['operationWorkers'] as $operation_worker) {
                                            $archive_orders[$comp_dep_id]['workers'][$operation_worker['worker_id']] = $operation_worker['worker_id'];
                                            $archive_orders[$comp_dep_id]['shifts'][$shift_id]['workers'][$operation_worker['worker_id']] = $operation_worker['worker_id'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                unset($found_orders);

                foreach ($archive_orders as $comp_dep) {
                    if (isset($comp_dep['workers'])) {
                        $archive_orders[$comp_dep['company_department_id']]['count_workers_by_day'] = count($comp_dep['workers']);
                    } else {
                        $archive_orders[$comp_dep['company_department_id']]['count_workers_by_day'] = 0;
                    }
                    foreach ($comp_dep['shifts'] as $shift) {
                        if (!isset($archive_orders[$comp_dep['company_department_id']]['count_workers_by_shift'])) {
                            $archive_orders[$comp_dep['company_department_id']]['count_workers_by_shift'] = 0;
                        }

                        if (isset($shift['workers'])) {
                            $archive_orders[$comp_dep['company_department_id']]['count_workers_by_shift'] += count($shift['workers']);
                        }
                    }
                }
            }

            if (isset($archive_orders)) {
                $result = $archive_orders;
            }

            $log->addLog("Окончание выполнения метода", $count_record);
        } catch
        (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    // GetJournalSituationByMonth - метод получения журнала ситуаций по конкретной дате на месяц
    // входные параметры:
    //      date_time:              "2020-11-31"    - дата, из которой берем месяц и год для получения статистики на месяц
    //      mine_id                 290             - ключ шахты
    // выходной объект:
    //         {asmtp_id}:
    //             mine_id: 1,                                          - ключ шахты
    //             mine_title: 1,                                       - название шахты
    //             asmtp_id: 1,                                         - ключ автоматизированной системы
    //             asmtp_title: 'Strata система позиционирования',      - название автоматизированной системы
    //             asmtp_count: '45',                                   - количество ситуаций в автоматизированной системе
    //             situations:                                          - статистика по ситуациям
    //                 {situation_id}:                                          - ключ ситуации
    //                     situation_id: 60,                                        - ключ ситуации
    //                     situation_title: 'Отказ ЛУЧ-4',                          - название ситуации
    //                     situation_count: '5',                                    - количество ситуаций в системе
    //                     events:                                                  - события
    //                         {event_id}:                                              - ключ события
    //                              event_id: 1,                                                - ключ события
    //                              event_title: 'Остановка ВМП',                               - название события
    //                              event_count: '5'                                            - количество событий
    // разработал: Якимов М.Н.
    // дата: 07.12.2019г
    // пример:
    // http://127.0.0.1/read-manager-amicum?controller=EdsFront&method=GetJournalSituationByMonth&subscribe=&data={%22date_time%22:%222020-05-12%22,%22mine_id%22:%22290%22}
    public static function GetJournalSituationByMonth($data_post = NULL)
    {
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("GetJournalSituationByMonth");
        $result = (object)array();
        try {
            $log->addLog("Начало выполнения метода");

            /** Метод начало */
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('Данные с фронта не получены');
            }
            $log->addLog("Данные успешно переданы");
            $log->addLog('Входной массив данных' . $data_post);

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $log->addLog("Декодировал входные параметры");

            if (
                !(property_exists($post_dec, 'date_time')) ||
                !(property_exists($post_dec, 'mine_id'))

            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $log->addLog("Данные с фронта получены и они правильные");

            $year = date("Y", strtotime($post_dec->date_time));
            $month = date("m", strtotime($post_dec->date_time));
            $mine_id = $post_dec->mine_id;

            $filter_mine = array();
            if ($mine_id != -1 and $mine_id != 1) {
                $filter_mine = ['mine_id' => $mine_id];
            }

            // Расчет статистики по АСУТП системам их ситуациям и событиям
            $asmtps = (new Query())
                ->select('
                    mine_id, mine_title,
                    situation_id, situation_title,
                    event_id, event_title,
                    situation_journal_id,
                    event_journal_id,
                    asmtp_id, asmtp_title,
                    year_situation,
                    month_situation,
                   
               ')
                ->from('view_get_sour_statistic_asmtp_with_month')
                ->groupBy('mine_id, mine_title,
                    situation_id, situation_title,
                    event_id, event_title,
                    situation_journal_id,
                    event_journal_id,
                    asmtp_id, asmtp_title,
                    year_situation')
                ->where('year_situation=' . $year)
                ->andWhere('month_situation=' . $month)
                ->andFilterWhere($filter_mine)
                ->all();

            $log->addLog("Данные с БД получены");

            foreach ($asmtps as $asmtp) {
                $asmtp_id = $asmtp['asmtp_id'];
                $situation_id = $asmtp['situation_id'];
                $event_id = $asmtp['event_id'];

                $statistic[$asmtp_id]['mine_id'] = $asmtp['mine_id'];
                $statistic[$asmtp_id]['mine_title'] = $asmtp['mine_title'];
                $statistic[$asmtp_id]['asmtp_id'] = $asmtp_id;
                $statistic[$asmtp_id]['asmtp_title'] = $asmtp['asmtp_title'];
                $statistic[$asmtp_id]['asmtp_count'] = 0;
                $statistic[$asmtp_id]['situations'][$situation_id]['situation_id'] = $situation_id;
                $statistic[$asmtp_id]['situations'][$situation_id]['situation_title'] = $asmtp['situation_title'];
                $statistic[$asmtp_id]['situations'][$situation_id]['situation_count'] = 0;
                $statistic[$asmtp_id]['situations'][$situation_id]['situation_journals'][$asmtp['situation_journal_id']] = $asmtp['situation_journal_id'];
                $statistic[$asmtp_id]['situation_journals'][$asmtp['situation_journal_id']] = $asmtp['situation_journal_id'];
                $situation_journals[$asmtp['situation_journal_id']] = $asmtp['situation_journal_id'];
                $statistic[$asmtp_id]['situations'][$situation_id]['events'][$event_id]['event_id'] = $event_id;
                $statistic[$asmtp_id]['situations'][$situation_id]['events'][$event_id]['event_title'] = $asmtp['event_title'];
                $statistic[$asmtp_id]['situations'][$situation_id]['events'][$event_id]['event_journals'][$asmtp['event_journal_id']] = $asmtp['event_journal_id'];
                $statistic[$asmtp_id]['situations'][$situation_id]['events'][$event_id]['event_count'] = 0;
            }

            $log->addLog("Первичная обработка");

            if (isset($statistic)) {
                foreach ($statistic as $asmtp) {
                    $statistic[$asmtp['asmtp_id']]['asmtp_count'] = count($asmtp['situation_journals']);
                    $statistic[$asmtp['asmtp_id']]['all_asmtp_count'] = count($situation_journals);
                    foreach ($asmtp['situations'] as $situation) {
                        $statistic[$asmtp['asmtp_id']]['situations'][$situation['situation_id']]['situation_count'] += count($situation['situation_journals']);
//                            unset($statistic[$asmtp['asmtp_id']]['situations'][$situation['situation_id']]['situation_journals']);
                        foreach ($situation['events'] as $event) {
                            $statistic[$asmtp['asmtp_id']]['situations'][$situation['situation_id']]['events'][$event['event_id']]['event_count'] = count($event['event_journals']);
                            unset($statistic[$asmtp['asmtp_id']]['situations'][$situation['situation_id']]['events'][$event['event_id']]['event_journals']);
                        }
                    }
                }

            }

            if (isset($statistic)) {
                $result = $statistic;
            }

            $log->addLog("Окончание выполнения метода", $count_record);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}
