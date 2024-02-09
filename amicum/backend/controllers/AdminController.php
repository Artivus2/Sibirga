<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers;

use backend\controllers\const_amicum\ParamEnum;
use Exception;
use frontend\controllers\HandbookCachedController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Briefer;
use frontend\models\EdgeParameterHandbookValue;
use frontend\models\Employee;
use frontend\models\GraficTabelDatePlan;
use frontend\models\Worker;
use frontend\models\WorkerObject;
use frontend\models\WorkerParameter;
use Throwable;
use Yii;
use yii\web\Controller;
use yii\web\Response;

class AdminController extends Controller
{
    /** КОНТРОЛЛЕР ПО СЕРВИСНЫМ ОПЕРАЦИЯС С СИСТЕМОЙ ИЛИ БД */
    // actionKillProcessBd          - Метод убывает все процессы в БД по указанному хосту
    // MergeWorker                  - Метод объединения работников созданных в ручную и созданных при интеграции (ручные удаляются)
    // UpdateFieldInTable           - Метод обновления поля в таблице
    // FixEdgeParameterHandbook     - Метод исправляет параметры в базе данных


    /**
     * @param string $host - адрес сервера запросы которого надо убить
     * @throws \yii\db\Exception
     */
    public function actionKillProcessBd($host = "")
    {

        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $method_name = "actionKillProcessBd";
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $debug = array();
        try {
            $processlist_before_killing_ = Yii::$app->db->createCommand('show processlist')->queryAll();
            $warnings["list before kill"] = $processlist_before_killing_;

            foreach ($processlist_before_killing_ as $process) {
                $id = explode(':', $process['Host']);
                $warnings[] = $id[0];
                // $warnings[] = $id[1];
                if ($id === $host) {

                    Yii::$app->db->createCommand('kill ' . $process['Id'])->execute();
                }
                $warnings['kill roc :'] = $process['Host'];
                $warnings[] = $process['Id'];
            }
            $processlist_after_killing_ = Yii::$app->db->createCommand('show processlist')->queryAll();
            $warnings["list after kill"] = $processlist_after_killing_;

        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result_m = array('items' => '', 'status' => $status, 'warnings' => $warnings, 'debug' => $debug, 'errors' => $errors);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_m;
    }

    /**
     * MergeWorker - Метод объединения работников созданных в ручную и созданных при интеграции (ручные удаляются)
     * Входные параметры:
     *      employee_id_src - ключ работника, который нужно оставить
     *      employee_id_delete - ключ работника, который нужно удалить
     * Пример: http://127.0.0.1/admin/read-manager-amicum?controller=Admin&method=MergeWorker&subscribe=&data={"employee_id_src":62085,"employee_id_delete":2912940} - точечно
     * Пример: http://127.0.0.1/admin/read-manager-amicum?controller=Admin&method=MergeWorker&subscribe=&data={"employee_id_src":null,"employee_id_delete":null} - все подряд
     */
    public static function MergeWorker($data_post = NULL)
    {

        $log = new LogAmicumFront("MergeWorker");
        $result = null;                                                                                                 // результирующий массив (если требуется)

        try {

            $log->addLog("Начало выполнение метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (
                !property_exists($post, 'employee_id_src') ||
                !property_exists($post, 'employee_id_delete')
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $employee_id_src = $post->employee_id_src;
            $employee_id_delete = $post->employee_id_delete;
            if ($employee_id_src and $employee_id_delete and $employee_id_src == $employee_id_delete) {
                throw new Exception("Переданы одинаковые работники");
            }

            $filter_src = [];
            $filter_delete = [];
            if ($employee_id_src and $employee_id_delete) {
                $filter_src = ['employee.id' => $employee_id_src];
                $filter_delete = ['employee.id' => $employee_id_delete];

            }

            $double_employees = Employee::find()
                ->select(['first_name', 'patronymic', 'last_name', 'birthdate', 'count(id) as count_id'])
                ->where($filter_src)
                ->orWhere($filter_delete)
                ->groupBy(['first_name', 'patronymic', 'last_name', 'birthdate'])
                ->having(['>', 'count_id', '1'])
                ->all();

//            $log->addData($double_employees, '$double_employees', __LINE__);

            $log->addLog("Получил список дублей");

            foreach ($double_employees as $double_employee) {
                $employee_link_1c = Employee::find()
                    ->joinWith('workers')
                    ->where(['first_name' => $double_employee['first_name'], 'patronymic' => $double_employee['patronymic'], 'last_name' => $double_employee['last_name'], 'birthdate' => $double_employee['birthdate']])
                    ->andWhere(['is not', 'employee.link_1c', null])
                    ->one();
                $log->addData($employee_link_1c, '$employee_link_1c', __LINE__);
                if (!$employee_link_1c) {
                    continue;                                                                                           // если у человека нет ссылки 1С, то пропустить
                }

                if (!$employee_link_1c->workers) {
                    throw new Exception("У работника нет ключа человека");
                }

//                $i = -1;
//                $is_permit_merge = 1;
//                foreach ($employee_link_1c->workers as $worker_src) {
//                    $i++;
//                    if ($i > 1) {
////                        $is_permit_merge = 0;
//                        $log->addLog("У человека больше 1 должности");
////                        throw new Exception("У человека больше 1 должности");
//                    }
//                }
//                if ($is_permit_merge) {
                if ($employee_link_1c->workers[0]['id'] != $employee_link_1c->id) {
                    $log->addLog("Ключ человека и работника не совпадают");
//                        $list_models = [
//                            ['model_name' => 'WorkerObject', 'field' => 'id'],
//                            ['model_name' => 'Worker', 'field' => 'id']
//                        ];
//                        foreach ($list_models as $list_model) {
//                            $response = self::UpdateFieldInTable($list_model['model_name'], $list_model['field'], $employee_link_1c->workers[0]['id'], $employee_link_1c->id);
//                            $log->addLogAll($response);
//                            if ($response['status'] != 1) {
//                                throw new Exception('Ошибка замены значения в ' . $list_model['model_name']);
//                            }
//                        }
                    $worker_id_src = $employee_link_1c->workers[0]['id'];
                } else {
                    $worker_id_src = $employee_link_1c->id;
                }

                /** Проверяем у работника worker_object_id */
                $worker_object_src = WorkerObject::find()->where(['worker_id' => $worker_id_src])->one();
                if (!$worker_object_src) {
                    $worker_object_src = new WorkerObject ();
                    $worker_object_src->id = $worker_id_src;
                    $worker_object_src->worker_id = $worker_id_src;
                    $worker_object_src->object_id = 25;
                    $worker_object_src->role_id = 9;
                    if (!$worker_object_src->save()) {
                        $log->addData($worker_object_src->errors, '$worker_object_src->errors', __LINE__);
                        throw new Exception('Ошибка сохранения модели WorkerObject');
                    }

                } else {
                    if ($worker_object_src->id != $worker_id_src) {
                        $response = self::UpdateFieldInTable('WorkerObject', 'id', $worker_object_src->id, $worker_id_src);
                        if ($response['status'] != 1) {
                            $log->addLogAll($response);
                            throw new Exception('Ошибка объединения ключей WorkerObject');
                        }
                    }
                }

                $employee_id_src = $employee_link_1c->id;

                $list_models = [
                    ['model_name' => 'Attachment', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'AuditWorker', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'Briefer', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'Briefing', 'field' => 'instructor_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'Brigade', 'field' => 'brigader_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'BrigadeWorker', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'Chane', 'field' => 'chaner_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'ChaneWorker', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'ChatMember', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'ChatMemberConfig', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'ChatMessage', 'field' => 'sender_worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'ChatMessageFavorites', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'ChatMessagePinned', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'ChatMessageReciever', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'CheckKnowledgeWorker', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'CheckingPlan', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'CheckingWorkerType', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'CompanyDepartmentWorkerVgk', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'CorrectMeasures', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'DepartmentParameterSummaryWorkerSettings', 'field' => 'employee_id', 'value_src' => $employee_id_src],
                    ['model_name' => 'Document', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'DocumentEventPb', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'DocumentEventPbStatus', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'DocumentPhysicalStatus', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'EventJournalGilty', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'EventJournalStatus', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'EventPbWorker', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'Examination', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'Expertise', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'ForbiddenZapret', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'ForbiddenZapretStatus', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'GraficTabelDateFact', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'GraficTabelDatePlan', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'GraficTabelStatus', 'field' => 'worker_object_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'GraphicList', 'field' => 'worker_created_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'GraphicRepair', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'GraphicStatus', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'Injunction', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'InjunctionStatus', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'InquiryPb', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'MedReport', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'NormSizNeed', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'OccupationalIllness', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'OperationWorker', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'OrderHistory', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'OrderItem', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'OrderItemStatus', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'OrderItemWorker', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'OrderItemWorkerInstructionPb', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'OrderItemWorkerVgk', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'OrderItrDepartment', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'OrderOperationWorkerStatus', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'OrderPermit', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'OrderPermitStatus', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'OrderPermitWorker', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'OrderRelationStatus', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'OrderRouteWorker', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'OrderStatus', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'OrderWorkerCoordinate', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'OrderWorkerVgk', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'Physical', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'PhysicalEsmo', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'PhysicalWorker', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'PredExamHistory', 'field' => 'worker_id', 'value_src' => (string)$worker_id_src],
                    ['model_name' => 'RestrictionOrder', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'RouteTemplate', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'ShiftWorker', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'SituationJournalGilty', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'SituationJournalStatus', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'SituationStatus', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'SolutionCard', 'field' => 'responsible_worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'SolutionCardStatus', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'SolutionOperation', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'SolutionOperationStatus', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'SituationSolutionStatus', 'field' => 'responsible_worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'StopPb', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'StopPbStatus', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'Storage', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'SummaryReportEndOfShift', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'SummaryReportTimeSpent', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'SummaryReportTimeTableReport', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'TextMessage', 'field' => 'sender_worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'TextMessage', 'field' => 'reciever_worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'UpdateArchiveWorker', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'UpdateWish', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'User', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'Violator', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'WorkModeCompany', 'field' => 'creater_worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'WorkModeWorker', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'WorkerCard', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'WorkerCollection', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'WorkerFunction', 'field' => 'worker_object_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'WorkerObjectRole', 'field' => 'worker_object_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'WorkerParameter', 'field' => 'worker_object_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'WorkerSiz', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'ZipperJournal', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                    ['model_name' => 'ZipperJournalSendStatus', 'field' => 'worker_id', 'value_src' => $worker_id_src],
                ];

                $employees_to_delete = Worker::find()
                    ->joinWith('employee')
                    ->where(['first_name' => $double_employee['first_name'], 'patronymic' => $double_employee['patronymic'], 'last_name' => $double_employee['last_name'], 'birthdate' => $double_employee['birthdate']])
                    ->andWhere(
                        ['or',
                            ['is', 'worker.link_1c', null],
                            ['is', 'employee.link_1c', null]
                        ])
                    ->all();
                $log->addData($employees_to_delete, '$employees_to_delete', __LINE__);

                $log->addLog("Начал обрабатывать дубль");

                WorkerParameter::deleteAll(['worker_object_id' => $worker_id_src]);

                $log->addLog("Удалил параметры исходного работника, в которого объединяем");

                foreach ($employees_to_delete as $employee_to_delete) {
                    $worker_id_delete_item = $employee_to_delete->id;
                    $employee_id_delete_item = $employee_to_delete->employee->id;

                    Briefer::deleteAll(['worker_id' => $worker_id_delete_item]);
                    GraficTabelDatePlan::deleteAll(['worker_id' => $worker_id_delete_item]);

                    foreach ($list_models as $list_model) {
                        if ($list_model['model_name'] == 'DepartmentParameterSummaryWorkerSettings') {
                            $response = self::UpdateFieldInTable($list_model['model_name'], $list_model['field'], $employee_id_delete_item, $list_model['value_src']);
                        } else {
                            $response = self::UpdateFieldInTable($list_model['model_name'], $list_model['field'], $worker_id_delete_item, $list_model['value_src']);
                        }

                        if ($response['status'] != 1) {
                            $log->addLogAll($response);
                            throw new Exception('Ошибка замены значения в ' . $list_model['model_name']);
                        }
                    }
                    WorkerObject::deleteAll(['worker_id' => $worker_id_delete_item]);
                    Worker::deleteAll(['id' => $worker_id_delete_item]);
                    if ($employee_id_src != $employee_id_delete_item) {
                        Employee::deleteAll(['id' => $employee_id_delete_item]);
                    }
                }
                $log->addLog("Обработал 1 дубль");
            }

//                throw new Exception('отладочный стоп');

            HandbookCachedController::clearWorkerCache();

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }


        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * updateFieldInTable - Метод обновления поля в таблице
     * @param $model_name - имя модели, в которой нужно произвести обновление
     * @param $field - поля, в котором меняем
     * @param $value_src - значение, которое ищем
     * @param $value_dest - Значение, на которое меняем
     * @return array|null[]
     */
    private static function UpdateFieldInTable($model_name, $field, $value_src, $value_dest)
    {
        $log = new LogAmicumFront("UpdateFieldInTable");
        $result = null;                                                                                                 // результирующий массив (если требуется)

        try {
            $log->addLog("Начало выполнение метода");

            $model = new ("\\frontend\\models\\" . $model_name);

            $models = $model::findAll([$field => $value_src]);
            foreach ($models as $item) {
                $item->$field = $value_dest;
                if (!$item->save()) {
                    $log->addData($value_src, '$value_src', __LINE__);
                    $log->addData($value_dest, '$value_dest', __LINE__);
                    $log->addData($item->errors, '$item->errors', __LINE__);
                    throw new Exception('Ошибка сохранения модели ' . $model_name);
                }
//                $item->refresh();
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * FixEdgeParameterHandbook - Метод исправляет справочные параметры выработки в базе данных
     * замена запятых на точки в:
     * - edge_parameter_handbook_value:LENGTH,ANGLE,HEIGHT,WIDTH,SECTION,LEVEL_CO,LEVEL_CH4
     * @example http://127.0.0.1/admin/read-manager-amicum?controller=Admin&method=FixEdgeParameterHandbook&subscribe=&data={}
     */
    public static function FixEdgeParameterHandbook()
    {
        $log = new LogAmicumFront("FixEdgeParameterHandbook");
        $count_record = 0;

        try {
            $log->addLog("Начало выполнение метода");

            $parameter_id = [
                ParamEnum::LENGTH,
                ParamEnum::ANGLE,
                ParamEnum::HEIGHT,
                ParamEnum::WIDTH,
                ParamEnum::SECTION,
                ParamEnum::LEVEL_CO,
                ParamEnum::LEVEL_CH4
            ];

            $edge_parameter_h_v = EdgeParameterHandbookValue::find()
                ->joinWith('edgeParameter')
                ->where(['parameter_id' => $parameter_id])
                ->andWhere("value like '%,%'");


            foreach ($edge_parameter_h_v->each(2000) as $item) {
                $count_record++;
                $item->value = str_replace(',', '.', $item->value);

                if (!$item->save()) {
                    $log->addData($item->errors, '$item->errors', __LINE__);
                    throw new Exception("Ошибка сохранения модели EdgeParameterHandbookValue");
                }

            }

            $log->addData($count_record, "Количество обработанных данных", __LINE__);


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => []], $log->getLogAll());
    }

}