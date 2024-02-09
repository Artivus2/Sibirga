<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers;

use backend\controllers\const_amicum\ParamEnum;
use backend\controllers\const_amicum\StatusEnumController;
use backend\controllers\serviceamicum\PredExamController;
use backend\controllers\StrataJobController;
use Exception;
use frontend\controllers\handbooks\DepartmentController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Employee;
use frontend\models\MoDopusk;
use frontend\models\MoResult;
use frontend\models\PhysicalEsmo;
use frontend\models\PredExamHistory;
use frontend\models\Worker;
use frontend\models\WorkerObject;
use frontend\models\WorkerParameter;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Application;
use yii\web\Controller;
use yii\web\Response;

class IntegrationController extends Controller
{

    #region Блок структуры контроллера
    // GetCompany               - Метод получения справочника компаний
    // GetEmployeeWorker        - Метод получения свзяки worker (работника) и employee (данные по работнику)
    // GetWorkerCard            - Метод получения пропусков сотрудников
    // SynchronizationEsmo      - синхронизация предсменных МО с системы ЭСМО Квазар
    // CalcEsmo                 - метод расчета времени прохождения ЭСМО
    // actionSetASMO            - метод записи данных о МО из внешней системы (разработан для колмар)

    // actionGetQuiz - метод записи данных предсменном тестировании из внешней системы

    const EXAM_START = 1;                                                                                               // контроль знаний начат
    const EXAM_END = 2;                                                                                                 // контроль знаний окончен
    const EXAM_BREAK = 3;                                                                                               // контроль знаний прерван

    #endregion
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetCompany() - Метод получения справочника компаний
     * @return array
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=Integration&method=GetCompany&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 20.12.2019 8:39
     */
    public static function GetCompany()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetCompany';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $result = (new Query())
                ->select('*')
                ->from('company')
                ->all();
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetEmployeeWorker() - Метод получения свзяки worker и employee
     * @return array
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=Integration&method=GetEmployeeWorker&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 20.12.2019 8:38
     */
    public static function GetEmployeeWorker()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetEmployeeWorker';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $workers = (new Query())
                ->select('worker.*,employee.*,position.title as position_title')
                ->from('worker')
                ->innerJoin('employee', 'employee.id = worker.employee_id')
                ->innerJoin('position', 'position.id = worker.position_id')
                ->all();
            $counter = 0;
            foreach ($workers as $worker_item) {
                $result[$counter] = $worker_item;
                $result[$counter]['pass'] = null;
                $counter++;
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetWorkerCard() - Метод получения пропусков сотрудников
     * @return array
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=Integration&method=GetWorkerCard&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.12.2019 10:04
     */
    public static function GetWorkerCard()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetWorkerCard';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $result = (new Query())
                ->select('*')
                ->from('worker_card')
                ->all();
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);;
    }

    /**
     * Метод SynchronizationEsmo() - синхронизация предсменных МО с системы ЭСМО Квазар
     * входные параметры:
     *      нет
     * выходные параметры:
     *      стандартный набор
     * алгоритм:
     * 1. Получить дату последней синхронизации ЭСМО
     * 2. Получить список медосмотров с даты последней синхронизации ЭСМО
     * 3. Уложить медосмотр по нужному параметру для конкретного человека
     * 4. Уложить дату начала и окончания МО при успешном прохождении
     *
     *
     * пример: http://127.0.0.1/super-test/test-post-esmo
     * 127.0.0.1/synchronization-front/update-esmo
     */
    public static function SynchronizationEsmo()
    {

//        ini_set('max_execution_time', -1);
//        ini_set('mysqlnd.connect_timeout', 1440000);
//        ini_set('default_socket_timeout', 1440000);
//        ini_set('mysqlnd.net_read_timeout', 1440000);
//        ini_set('mysqlnd.net_write_timeout', 1440000);
//        ini_set('memory_limit', "10500M");

        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("SynchronizationEsmo");

        $count_esmo = 0;                                                                                                // 'count_esmo. Количество записей с ЭСМО'
        $count_group_worker = 0;                                                                                        // 'count_group_worker. Количество групп на поиск' => $count_group_worker,
        $count_found_worker = 0;                                                                                        // 'count_group_worker. Количество найденных работников'
        $count_worker_object = 0;                                                                                       // 'count_worker_object Количество созданных конкретных работников'
        $count_worker_parameter = 0;                                                                                    // 'count_worker_parameter Количество созданных параметров работников'
        $count_worker = 0;                                                                                              // 'count_worker. Количество обработанных работников у нас всего'

        $workers_without_sap = array();                                                                                 // список работников, которых нет в сап
        $mo_esmo_error = array();                                                                                       // Медосмотры, оформленные задним числом
        try {
            $log->addLog("Начало выполнения метода");

            $max_value = PhysicalEsmo::find()                                                                          //получение максимального номера синхронизации
            ->where(['mine_id' => ESMO_MINE])
                ->max('date_time_start');

            if ($max_value === NULL) {                                                                                  //получение текущего номера синхронизации
                $date_start_synch_format = date("d.m.Y H:i:s", strtotime("2020-01-01 00:00:00"));
                $date_start_synch = date("Y-m-d H:i:s", strtotime("2020-01-01 00:00:00"));
            } else {
                $date_start_synch_format = date("d.m.Y H:i:s", strtotime($max_value . '+1 seconds'));
                $date_start_synch = date("Y-m-d H:i:s", strtotime($max_value . '+1 seconds'));
            }
            $date_end = date("d.m.Y H:i:s", strtotime(\backend\controllers\Assistant::GetDateNow() . '+10 seconds'));

            $arrContextOptions = array(
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ),
            );
            $get_string = 'https://' . ESMO . '/services/vorkuta/get/get_mo_data/json/?data_from=' . rawurlencode($date_start_synch_format) . '&data_to=' . rawurlencode($date_end);
            $response = file_get_contents($get_string, false, stream_context_create($arrContextOptions));
//            $response = json_decode($response, true);

            $log->addData(ESMO_MINE, 'ESMO_MINE:', __LINE__);
            $log->addData(ESMO, 'ESMO:', __LINE__);
            $log->addData($get_string, '$get_string:', __LINE__);
            $log->addData($max_value, '$max_value:', __LINE__);
            $log->addData($date_start_synch_format, '$date_start_synch_format:', __LINE__);
            $log->addData($date_start_synch, '$date_start_synch:', __LINE__);
            $log->addData($date_end, '$date_end:', __LINE__);

            $esmos_without_group = json_decode($response);
            $workers_for_search_obj = array();
            // получаем список обрабатываемых работников
            foreach ($esmos_without_group as $esmo) {
                $workers_for_search_obj[$esmo->uid] = $esmo->uid;
                $esmos[$esmo->uid][$esmo->time_MO] = $esmo;
            }
            $filter = array();
            if (!empty($workers_for_search_obj)) {
                if (count($workers_for_search_obj) < 500) {
                    $log->addLog("Работников было меньше 500");
                    foreach ($workers_for_search_obj as $worker) {
                        $workers_for_search[] = $worker;
                    }
                    $filter = ['worker.id' => $workers_for_search];
                }
                $log->addLog("Фильтр для выборки людей");
//            $warnings[] = $filter;

                //формируем справочники результатов МО и справочник допусков МО
                $mo_dopusk_handbook = MoDopusk::find()->indexBy('id')->all();
                $mo_result_handbook = MoResult::find()->indexBy('id')->all();

                // получаем список параметров работников СТАТУС ПРОХОЖДЕНИЯ ЭСМО
                $worker_parameters = (new Query())
                    ->select('
                        worker.id as worker_id,
                        worker_object.id as worker_object_id,
                        worker_parameter.id as worker_parameter_id
                ')
                    ->from('worker')
                    ->leftJoin('worker_object', 'worker_object.worker_id = worker.id')
                    ->leftJoin('worker_parameter', 'worker_parameter.worker_object_id = worker_object.id and parameter_type_id=2 and parameter_id=' . ParamEnum::MO)
                    ->FilterWhere($filter)
                    ->indexBy('worker_id')
                    ->all();
                $log->addLog("Получил список людей и параметров 928 из БД");
//            $warnings[] = $worker_parameters;
                $count_found_worker = count($worker_parameters);
                $count_esmo = count($esmos);
                $count_group_worker = count($workers_for_search_obj);

                foreach ($esmos as $worker) {
                    foreach ($worker as $esmo) {

                        //проверяем справочник результатов МО и справочник допусков МО
                        if (!isset($mo_dopusk_handbook[$esmo->mo_dopusk_id]) and $esmo->mo_dopusk_id) {
                            $mo_dopusk[$esmo->mo_dopusk_id]['id'] = $esmo->mo_dopusk_id;
                            $mo_dopusk[$esmo->mo_dopusk_id]['title'] = $esmo->mo_dopusk_text;
                        }
                        if (!isset($mo_result_handbook[$esmo->mo_result_id]) and $esmo->mo_result_id) {
                            $mo_result[$esmo->mo_result_id]['id'] = $esmo->mo_result_id;
                            $mo_result[$esmo->mo_result_id]['title'] = $esmo->mo_result_text;
                        }

                        // проверяем у нас наличие этого работника если работника нет, то пропускаем его
                        // проверяем наличие у работника 928 параметра - и если нет, то создаем его
                        $date_time_start = date("Y-m-d H:i:s", strtotime($esmo->time_MO));
                        if (
                            isset($worker_parameters[$esmo->uid]) and // человек существует у нас в БД
                            ($date_time_start >= $date_start_synch) and // это не подложный МО
                            $esmo->mo_result_id != 1                                                                        // МО должен быть закончен
                        ) {
                            $count_worker++;
                            // если у работника нет конкретного worker_object, то создаем его
                            if (!$worker_parameters[$esmo->uid]['worker_object_id']) {
                                $count_worker_object++;
                                $save_worker_object = new WorkerObject();
                                $save_worker_object->id = $worker_parameters[$esmo->uid]['worker_id'];
                                $save_worker_object->worker_id = $worker_parameters[$esmo->uid]['worker_id'];
                                $save_worker_object->object_id = 25;
                                $save_worker_object->role_id = 9;
                                if (!$save_worker_object->save()) {
                                    $errors[] = $save_worker_object->errors;
                                    throw new Exception("Запись с таким номером не добавлена" . $esmo->uid);
                                }
//                        $warnings[] = $method_name . ". Создал worker_object_id для работника " . $esmo->uid;
                            }

                            // если у работника нет параметра, то создаем его
                            if (!$worker_parameters[$esmo->uid]['worker_parameter_id']) {
                                $count_worker_parameter++;
                                $save_worker_parameter = new WorkerParameter();
                                $save_worker_parameter->worker_object_id = $worker_parameters[$esmo->uid]['worker_id'];
                                $save_worker_parameter->parameter_id = ParamEnum::MO;
                                $save_worker_parameter->parameter_type_id = 2;
                                if (!$save_worker_parameter->save()) {
                                    $errors[] = $save_worker_parameter->errors;
                                    throw new Exception("Ошибка добавления параметра 928 работника " . $esmo->uid);
                                }
                                $save_worker_parameter->refresh();
                                $worker_parameters[$esmo->uid]['worker_object_id'] = $worker_parameters[$esmo->uid]['worker_id'];
                                $worker_parameters[$esmo->uid]['worker_parameter_id'] = $save_worker_parameter->id;

//                        $warnings[] = $method_name . ". Создал worker_parameter_id для работника " . $esmo->uid;
                            }

                            if ($esmo->mo_dopusk_id == 2) {
                                $status_id = 100;                     // Медосмотр пройден - годен
                            } else if ($esmo->mo_dopusk_id == 3) {
                                $status_id = 101;                     // Медосмотр не прошел - не годен
                            } else if ($esmo->mo_dopusk_id == 4) {
                                $status_id = 102;                     // Медосмотр пройден частично - прерван
                            } else {
                                $status_id = 103;                     // Медосмотр статус прочее/ начат
                            }
                            // создаем массив для массовой вставки параметров в БД


                            $responseShift = StrataJobController::getShiftDateNum($date_time_start);
                            $shift = $responseShift['shift_num'];
                            $date_work = $responseShift['shift_date'];
                            $worker_parameter_to_db[] = array(
                                'worker_parameter_id' => $worker_parameters[$esmo->uid]['worker_parameter_id'],
                                'date_time' => $date_time_start,
                                'value' => $esmo->mo_dopusk_id,
                                'status_id' => $status_id,
                                'shift' => $shift,
                                'date_work' => $date_work,
                            );

                            // создаем массив для массовой вставки МО в таблицу physical_esmo БД
                            $date_time_end = null;
                            if ($esmo->time_MO_end) {
                                $date_time_end = date("Y-m-d H:i:s", strtotime($esmo->time_MO_end));
                            }
                            $mo_result_id = null;
                            if ($esmo->mo_result_id != 0) {
                                $mo_result_id = $esmo->mo_result_id;
                            }
                            $mo_dopusk_id = null;
                            if ($esmo->mo_dopusk_id != 0) {
                                $mo_dopusk_id = $esmo->mo_dopusk_id;
                            }
                            if ((int)$esmo->systolic) {
                                $systolic = $esmo->systolic;
                            } else {
                                $systolic = null;
                            }
                            if ((int)$esmo->pulse) {
                                $pulse = $esmo->pulse;
                            } else {
                                $pulse = null;
                            }
                            if ((int)$esmo->diastolic) {
                                $diastolic = $esmo->diastolic;
                            } else {
                                $diastolic = null;
                            }
                            $worker_parameter_to_esmo_db[] = array(
                                'worker_id' => $esmo->uid,
                                'date_time_start' => $date_time_start,
                                'date_time_end' => $date_time_end,
                                'mo_result_id' => $mo_result_id,
                                'mo_dopusk_id' => $mo_dopusk_id,
                                'terminal_name' => $esmo->terminal_name,
                                'question' => $esmo->question,
                                'alko' => $esmo->alko,
                                'systolic' => $systolic,
                                'diastolic' => $diastolic,
                                'pulse' => $pulse,
                                'temperature' => $esmo->temperature,
                                'tk' => $esmo->tk,
                                'mine_id' => ESMO_MINE,
                                'mo_id' => $esmo->mo_id,
                            );
                        } else if (isset($worker_parameters[$esmo->uid]) and ($date_time_start < $date_start_synch)) {
                            $mo_esmo_error[] = $esmo;
                        } else {
                            $workers_without_sap[] = $esmo;
                        }
                    }
                }
            } else {
                $log->addLog("Список пуст");
            }
            /**
             * {
             * "fio":"Григоренко Татьяна Борисовна",
             * "uid":"esmo_15e7301c5ac529e11aa27b945564246f",
             * "dr":"21.01.1963",
             * "time_MO":"11.02.2020 17:27",
             * "time_MO_end":"11.02.2020 17:27",
             * "time_dopusk_end":0,
             * "mo_id":"1963",
             * "doctor_comment":null,
             * "mo_result_id":"3",
             * "mo_result_text":"Осмотр окончен, отриц.",
             * "mo_dopusk_id":0,
             * "mo_dopusk_text":null,
             * "time_now":"20.02.2020 10:12:40",
             * "terminal_name":"Терминал №21",
             * "question":"Нет",
             * "alko":"0",
             * "systolic":"164",
             * "diastolic":"90",
             * "pulse":"89",
             * "temperature":"36.10",
             * "tk":1}
             **/

            if (isset($mo_dopusk)) {
                $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert('mo_dopusk', ['id', 'title'], $mo_dopusk)->execute();
                $log->addLog('Количество вставленных записей в mo_dopusk: ' . $insert_result_to_MySQL);
            }

            if (isset($mo_result)) {
                $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert('mo_result', ['id', 'title'], $mo_result)->execute();
                $log->addLog('Количество вставленных записей в mo_result: ' . $insert_result_to_MySQL);
            }

            if (isset($worker_parameter_to_esmo_db)) {
                $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert('physical_esmo',
                    ['worker_id', 'date_time_start', 'date_time_end', 'mo_result_id', 'mo_dopusk_id', 'terminal_name', 'question', 'alko', 'systolic', 'diastolic', 'pulse', 'temperature', 'tk', 'mine_id', 'mo_id'],
                    $worker_parameter_to_esmo_db)->execute();
                $log->addLog('Количество вставленных записей в physical_esmo: ' . $insert_result_to_MySQL);
            }


            if (isset($worker_parameter_to_db)) {
                $global_insert_param_val = Yii::$app->db->queryBuilder->batchInsert('worker_parameter_value', ['worker_parameter_id', 'date_time', 'value', 'status_id', 'shift', 'date_work'], $worker_parameter_to_db);
                $insert_result_to_MySQL = Yii::$app->db->createCommand($global_insert_param_val . " ON DUPLICATE KEY UPDATE
                `worker_parameter_id` = VALUES (`worker_parameter_id`), `date_time` = VALUES (`date_time`), `value` = VALUES (`value`), `status_id` = VALUES (`status_id`), `date_work` = VALUES (`date_work`)")->execute();
                $log->addLog('Количество вставленных записей в worker_parameter_value: ' . $insert_result_to_MySQL);
            }

//            $result = $esmos;
            /** Метод окончание */


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        /** Отладка */

        $log->addData(array(
            'count_esmo. Количество записей с ЭСМО' => $count_esmo,
            'count_group_worker. Количество групп на поиск' => $count_group_worker,
            'count_group_worker. Количество найденных работников' => $count_found_worker,
            'count_worker_object. Количество созданных конкретных работников' => $count_worker_object,
            'count_worker_parameter. Количество созданных параметров работников' => $count_worker_parameter,
            'count_worker. Количество обработанных работников у нас всего' => $count_worker,
            'workers_without_sap. Работники которых нет в сап (не верные пропуска)' => $workers_without_sap,
            '$mo_esmo_error. Медосмотры оформленные задним числом' => $mo_esmo_error,
        ), '$warnings:', __LINE__);

        $log->saveLogSynchronization($count_worker);

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод CalcEsmo() - метод расчета времени прохождения ЭСМО
     * входные параметры:
     *      month
     *      year
     *      company_department_id
     * выходные параметры:
     *      worker_id               - ключ работника
     * company_title           - название депратамента в котором трудится работник
     * last_name               - фамилия работника
     * first_name              - имя работника
     * patronymic              - отчество работника
     * position_title          - должность работника
     * date_time_start         - первый раз когда работник начал проходить медосомтр в запрашиваемом периоде
     * date_time_end           - когда последний раз работник закончил проходить мед осмотр в запрашиваемом периоде
     * duration_second         - суммарная продолжительность в секундах за период
     * duration_hours          - суммарная продолжительность медосмотра в часах за период
     * duration_hours_round    - округленная продолжительность медосмотра в часах за период (2 знака)
     *
     *      стандартный набор
     * алгоритм:
     *
     *
     *
     * пример: http://127.0.0.1/super-test/test-post-esmo
     */
    // http://127.0.0.1/read-manager-amicum?controller=Integration&method=CalcEsmo&subscribe=&data={%22company_department_id%22:4029720,%22year%22:%222020%22,%22month%22:%2202%22}
    public static function CalcEsmo($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'CalcEsmo';                                                                           // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

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
            /** Метод начало */
            $warnings[] = $method_name . '. Начало метода';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'year') ||                                                         // год
                !property_exists($post_dec, 'company_department_id') ||                                                         // год
                !property_exists($post_dec, 'month'))                                                         // период 'month/year'
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';

            $year = $post_dec->year;
            $month = $post_dec->month;
            $company_department_id = $post_dec->company_department_id;

            // ищем вложенные подразделения
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception($method_name . '. Ошибка получения вложенных департаментов' . $company_department_id);
            }

            $esmos = (new Query())
                ->select('
                worker.id as worker_id,
                date_time_start,
                date_time_end,
                company.title as company_title,
                employee.last_name,
                employee.first_name,
                employee.patronymic,
                position.title as position_title
                ')
                ->from('physical_esmo')
                ->innerJoin('worker', 'worker.id=physical_esmo.worker_id')
                ->innerJoin('employee', 'employee.id=worker.employee_id')
                ->innerJoin('position', 'position.id=worker.position_id')
                ->innerJoin('company_department', 'company_department.id=worker.company_department_id')
                ->innerJoin('company', 'company.id=company_department.company_id')
                ->andWhere("YEAR(physical_esmo.date_time_start)='" . $year . "'")
                ->andWhere(['MONTH(physical_esmo.date_time_start)' => $month])
                ->andWhere(['in', 'worker.company_department_id', $company_departments])
                ->andWhere(['mo_dopusk_id' => 1])
                ->orderBy(['date_time_start' => SORT_ASC])
//                ->limit(1)
                ->all();

            foreach ($esmos as $esmo) {
                if (!isset($result_esmo[$esmo['worker_id']])) {
                    $result_esmo[$esmo['worker_id']]['worker_id'] = $esmo['worker_id'];
                    $result_esmo[$esmo['worker_id']]['date_time_start'] = $esmo['date_time_start'];
                    $result_esmo[$esmo['worker_id']]['company_title'] = $esmo['company_title'];
                    $result_esmo[$esmo['worker_id']]['last_name'] = $esmo['last_name'];
                    $result_esmo[$esmo['worker_id']]['first_name'] = $esmo['first_name'];
                    $result_esmo[$esmo['worker_id']]['patronymic'] = $esmo['patronymic'];
                    $result_esmo[$esmo['worker_id']]['position_title'] = $esmo['position_title'];
                    $result_esmo[$esmo['worker_id']]['duration_second'] = 0;
                }

                $duration = strtotime($esmo['date_time_end']) - strtotime($esmo['date_time_start']);
                if ($duration > 120 or $duration == 0) {
                    $duration = 120;
                }
                $result_esmo[$esmo['worker_id']]['duration_second'] += $duration;
                $result_esmo[$esmo['worker_id']]['date_time_end'] = $esmo['date_time_end'];
            }

            if (isset($result_esmo)) {
                foreach ($result_esmo as $esmo) {
                    $result_esmo[$esmo['worker_id']]['duration_hours'] = $esmo['duration_second'] / 60 / 60;
                    $result_esmo[$esmo['worker_id']]['duration_hours_round'] = round($esmo['duration_second'] / 60 / 60, 2);
                }
            }

            if (isset($result_esmo)) {
                $result = $result_esmo;
            } else {
                $result = (object)array();
            }
            /** Метод окончание */


        } catch (Throwable $ex) {
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
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);

        return array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }

    /**
     * actionSetASMO - метод записи данных о МО из внешней системы
     * Входные данные
     *      date_time_mo - дата проведения МО в формате 2022-04-01 15:36:12
     *      staff_number - табельный номер в текстовом виде
     *      result_mo - результат проведения осмотра 1 - разрешен, 2 - запрещен, 3 - уклонился
     * @example http://87.103.211.83:7777/integration/set-asmo?result_mo=1&staff_number=ГОКИ2736&date_time_mo=2022-04-01%2015:36:12
     * @example 127.0.0.1/integration/set-asmo?result_mo=1&staff_number=ГОКИ2736&date_time_mo=2022-04-01 15:36:12
     */
    public function actionSetAsmo()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionSetAsmo");

        try {
            $post = Assistant::GetServerMethod();
            if (!isset($post['staff_number']) or $post['staff_number'] == "") {
                throw new Exception("Не передан табельный номер сотрудника");
            }

            if (!isset($post['date_time_mo']) or $post['date_time_mo'] == "") {
                throw new Exception("Не передана дата проведения медосмотра");
            }

            if (!isset($post['result_mo']) or $post['result_mo'] == "" or $post['result_mo'] == 0) {
                throw new Exception("Не передана результат медосмотра");
            }

            $tabel_number = $post['staff_number'];
            $mo_dopusk_id = $post['result_mo'];
            $date_time_start = $post['date_time_mo'];

            if (
                $mo_dopusk_id == 1 or // разрешен
                $mo_dopusk_id == 2 or // запрещен
                $mo_dopusk_id == 3    // уклонился
            ) {
                $log->addLog("Ключ результата МО верный");
            } else {
                throw new Exception("Не верный ключ результата МО");
            }

            $worker = Worker::findOne(['tabel_number' => $tabel_number]);
            if ($worker) {
                $worker_id = $worker->id;

                $physical_esmo = PhysicalEsmo::findOne(["worker_id" => $worker_id, "date_time_start" => $date_time_start]);
                if (!$physical_esmo) {
                    $physical_esmo = new PhysicalEsmo();
                    $log->addLog("Новый МО");
                } else {
                    $log->addLog("МО был, обновляем");
                }
                $physical_esmo->worker_id = $worker_id;
                $physical_esmo->date_time_start = $date_time_start;
                $physical_esmo->mo_result_id = 4;
                $physical_esmo->mo_dopusk_id = $mo_dopusk_id;
                $physical_esmo->mine_id = ESMO_MINE;

                if (!$physical_esmo->save()) {
                    $log->addData($physical_esmo->errors, '$physical_esmo->errors', __LINE__);
                    throw new Exception("Ошибка сохранения результата МО PhysicalEsmo");
                }

                $log->addLog("МО сохранен");
            } else {
                $log->addLog("Работник с табельным номером: $tabel_number не найден");
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization();
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionGetQuiz - метод записи данных предсменном тестировании из внешней системы
     * Входные данные
     *      date_time - дата проведения проверки знания в формате 2022-04-01 15:36:12
     * @example 127.0.0.1/integration/get-quiz
     */
    public function actionGetQuiz()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        $count_record_all = 0;                                                                                          // записей на обработку всего
        $count_record_to_add = 0;                                                                                       // записей на добавление всего
        $count_record_error = 0;                                                                                        // записей с ошибкой (нет работника)

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionGetQuiz");

        try {

            $max_value = PredExamHistory::find()
                ->where(['mine_id' => QUIZ_MINE])
                ->max('start_test_time');

            if ($max_value === NULL) {
                $date_start_synch_format = date("d.m.Y H:i:s", strtotime("2023-08-01 00:00:00"));
                $date_start_synch = date("Y-m-d H:i:s", strtotime("2023-08-01 00:00:00"));
            } else {
                $date_start_synch_format = date("d.m.Y H:i:s", strtotime($max_value . '-120 seconds'));
                $date_start_synch = date("Y-m-d H:i:s", strtotime($max_value . '-120 seconds'));
//                $date_start_synch_format = "05.09.2023 09:28";
//                $date_start_synch ="2023-09-05 09:28";
            }

            $date_end_synch_format = date("d.m.Y H:i:s", strtotime(Assistant::GetDateTimeNow() . '+2 minutes'));

            $arrContextOptions = array(
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ),
            );
            $get_string = 'https://' . QUIZ . '/services/esmo_gateway/get/report_quiz_sessions/json/?data_from=' . rawurlencode($date_start_synch_format) . '&data_to=' . rawurlencode($date_end_synch_format);
            $response = file_get_contents($get_string, false, stream_context_create($arrContextOptions));

            $log->addData(QUIZ, 'QUIZ:', __LINE__);
            $log->addData($get_string, '$get_string:', __LINE__);
            $log->addData($max_value, '$max_value:', __LINE__);
            $log->addData($date_start_synch_format, '$date_start_synch_format:', __LINE__);
            $log->addData($date_start_synch, '$date_start_synch:', __LINE__);
            $log->addData($date_end_synch_format, '$date_end_synch_format:', __LINE__);

            $response = Assistant::jsonDecodeAmicum($response);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $pred_exams = $response['Items'];
//            $log->addData($pred_exams, '$pred_exams', __LINE__);

            $date_time_now = Assistant::GetDateTimeNow();

            if (!empty($pred_exams)) {
                $workers_src = Worker::find()
                    ->innerJoinWith('employee')
                    ->asArray()->where('employee.link_1c is not null')->all();
                if (!$workers_src) {
                    throw new Exception('Справочник работников пуст');
                }
                foreach ($workers_src as $worker) {
//                    $employee = $worker['employee'];
//                    $trim_link_1c = mb_substr($employee['link_1c'], 0, -4);
//                    $workers[$trim_link_1c] = $worker;
                    $workers[$worker['employee']['link_1c']] = $worker;
                }
            }
//            $log->addData($workers, '$workers', __LINE__);

            foreach ($pred_exams as $pred_exam) {
                $count_record_all++;
                if ($pred_exam->status != self::EXAM_BREAK and isset($workers[$pred_exam->personal_uid])) {
                    $count_record_to_add++;
                    $status_id = ($pred_exam->status == self::EXAM_START) ? StatusEnumController::EXAM_START : StatusEnumController::EXAM_END;

                    $pred_exams_to_db[] = array(
                        'mine_id' => QUIZ_MINE,
                        'employee_id' => $workers[$pred_exam->personal_uid]['employee_id'],
                        'mo_session_id' => $pred_exam->mo_session_id,
                        'start_test_time' => date("Y-m-d H:i:s", strtotime($pred_exam->time)),
                        'status_id' => $status_id,
                        'sap_kind_exam_id' => 1,                                                                        // Предсменное тестирование Квазар
                        'count_right' => $pred_exam->cnt_correct,
                        'count_false' => (int)$pred_exam->question_count - (int)$pred_exam->cnt_correct,
                        'question_count' => $pred_exam->question_count,
                        'points' => (float)$pred_exam->cnt_correct,
                        'sap_id' => $pred_exam->quiz_session_id,
                        'date_created' => $date_time_now,
                        'date_modified' => $date_time_now,
                    );
                } else {
                    if (!isset($workers[$pred_exam->personal_uid])) {
                        $count_record_error++;
                    }
                }
            }

            $log->addData($count_record_all, 'Количество записей всего', __LINE__);
            $log->addData($count_record_to_add, 'Количество записей на вставку в БД', __LINE__);
            $log->addData($count_record_error, 'Количество записей с несуществующими работниками', __LINE__);

            if (isset($pred_exams_to_db)) {
                $response = PredExamController::SavePredExam($pred_exams_to_db);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception("Ошибка массового сохранения предсменных экзаменов в БД");
                }
                $log->addData($response['Items'], 'Количество вставленных записей в БД', __LINE__);
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization();

        if (Yii::$app instanceof Application) {                                                                // if (Yii::$app instanceof \yii\console\Application)
            Yii::$app->response->format = Response::FORMAT_JSON;
            Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
        } else {
            return array_merge(['Items' => $result], $log->getLogAll());
        }
    }

}
