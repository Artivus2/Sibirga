<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\notification;

use backend\controllers\Assistant as BackendAssistant;
use backend\controllers\const_amicum\DepartmentTypeEnum;
use backend\controllers\const_amicum\StatusEnumController;
use backend\controllers\const_amicum\TypeBriefingEnumController;
use DateTime;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\handbooks\DepartmentController;
use frontend\controllers\industrial_safety\CheckingController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Audit;
use frontend\models\Briefing;
use frontend\models\Checking;
use frontend\models\CheckKnowledge;
use frontend\models\Company;
use frontend\models\CompanyDepartment;
use frontend\models\EventPb;
use frontend\models\Expertise;
use frontend\models\Injunction;
use frontend\models\MedReport;
use frontend\models\NotificationStatus;
use frontend\models\PhysicalSchedule;
use frontend\models\Siz;
use frontend\models\Worker;
use frontend\models\WorkerObject;
use frontend\models\WorkerSiz;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;

class NotificationController extends Controller
{
    #region Структура контроллера
    // GetCheckingPlan                      - Получает список плановых аудитов по идентификатору участка
    // GetInjunctionsNotifications          - Получение данных для уведомлений по предписаниям
    // GetPabNotifications                  - Получение данных для уведомлений по ПАБ
    // GetNextExpertise                     - Получает список экспертиз промышленной безопасности по идентификатору участка
    // GetReplacementSIZ                    - Получает список СИЗ, у которых скоро выйдет срок (за 3 дня до замены СИЗа) по идентификатору участка работника
    // GetCheckup                           - Возвращает данные для блоку уведомлений в части "Запланирован медосмотр"
    // GetInstructionNotifications          - Определяет  тип оставшегося времени до инструктажа (повторного) для людей из подразделения
    // GetCheckKnowledge                    - Получение  данных для уведомлений "Запланировано обучение"
    // GetCertification                     - Метод получения аттестации по идентификатору работника для блока "Назначенная аттестация"
    // GetInquiry                           - Метод получения данных о несчастном случае, аварии или инциденте
    // GetNotificationAll                   - Возвращает все уведомления для блоку уведомлений (используется для мобилки)
    // GetNotificationPersonal              - Возвращает персональные уведомления

    // WriteOffSiz                          - Списание СИЗ
    // ExtentionSIZ                         - Продление средств индивидуальной защиты
    #endregion

    #region Блок констант
    const NEW_INJUNCTION = 57;
    const VISIBLE = 16;
    const INJ_IN_PROGRESS = 58;
    const INJ_EXPIRED = 60;
    const DAY_TO_VISIBLE_SIZ = 3;
    /**@var int статус: Выдан СИЗ */
    const SIZ_ISSUED = 64;
    /**@var int статус: Продлён СИЗ */
    const SIZ_EXTENDED = 65;
    /**@var int статус: Списан СИЗ */
    const SIZ_DECOMMISSIONED = 66;
    /**@var int статус: Актуальный */
    const STATUS_ACTIVE = 1;

    /**@var int сотрудник работает в данную дату (working_type_id из таблицы grafic_table_date_plan) */
    const WORK = 1;
    /**@var int тип уведомления желтый. разница между текущей датой и датой последнего Инструктажа > 76 */
    const TYPE_YELLOW = 2;
    /**@var int тип уведомления красный. разница между текущей датой и датой последнего Инструктажа > 90 */
    const TYPE_RED = 1;
    /**@var int тип инструктажа. 2 - значит он повторный */
    const TYPE_BRIEFING_TWO = 2;
    /**@var int количество дней для первого типа инструктажа */
    const DAY_TYPE_ONE = 76;
    const DAY_TYPE_TWO = 90;
    const TYPE_NOT = 3;

    #endregion

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод NotificationInjunction() - получение данных для уведомлений по предписаниям
     * @param null $data_post - JSON  идентификатором участка
     * @return array - массив со структурой: [injunction_id]
     *                                                  injunction_id:
     *                                                  date_first_status:
     *                                                  status_id:
     *
     * @package frontend\controllers\notification
     *
     * @example http://amicum/read-manager-amicum?controller=notification\Notification&method=GetInjunctionsNotifications&subscribe=&data={"company_department_id":802}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 06.08.2019 14:53
     */
    public static function GetInjunctionsNotifications($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $memory_size = array();
        $notifiction_injunction = array();                                                                              // Промежуточный результирующий массив
        try {
            if ($data_post !== NULL && $data_post !== '') {
                $warnings[] = 'GetInjunctionsNotifications. Данные успешно переданы';
                $warnings[] = 'GetInjunctionsNotifications. Входной массив данных' . $data_post;
            } else {
                throw new Exception('GetInjunctionsNotifications. Данные с фронта не получены');
            }
            $start_mem = memory_get_usage();
            $memory_size[] = 'start_mem - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'start_mem PEAK - ' . (memory_get_peak_usage()) / 1024;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetInjunctionsNotifications. Декодировал входные параметры';

            if (                                                                                                        // Проверяем наличие в нем нужных нам полей
            property_exists($post_dec, 'company_department_id')
            ) {
                $warnings[] = 'GetInjunctionsNotifications.Данные с фронта получены';
                $company_department_id = $post_dec->company_department_id;
            } else {
                throw new Exception('GetInjunctionsNotifications. Переданы некорректные входные параметры');
            }

            $response = DepartmentController::GetAttachDeprtmentByUpper($company_department_id);
            $warnings[] = $response['warnings'];
            $errors[] = $response['errors'];
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения вложенных департаментов');
            }
            $company_departments = $response['Items'];

            /******************** Поиск предписаний которые имеют статус: Новое, В работе, Просрочено ********************/
            $found_data_injunctions = Injunction::find()
                ->joinWith('injunctionStatuses')
                ->joinWith('firstInjunctionStatuses')
                ->where(['injunction.company_department_id' => $company_departments])
                ->andWhere(['IN', 'injunction.status_id', [self::NEW_INJUNCTION, self::INJ_IN_PROGRESS, self::INJ_EXPIRED]])
                ->andWhere(['injunction.kind_document_id' => CheckingController::KIND_INJUNCTION])
                ->limit(50000)
                ->all();
            $memory_size[] = 'Injunction mem - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'Injunction mem PEAK - ' . (memory_get_peak_usage()) / 1024;
            /******************** Если предписания найдены тогда перебираем их и формируем результирующий массив ********************/
            if ($found_data_injunctions) {
                foreach ($found_data_injunctions as $injunction) {
                    if (isset($injunction->firstInjunctionStatuses->date_time)) {
                        $date_status_format = date('d.m.Y', strtotime($injunction->firstInjunctionStatuses->date_time));
                        $date_status = $injunction->firstInjunctionStatuses->date_time;
                    } else {
                        $date_status_format = null;
                        $date_status = null;
                    }
                    $notifiction_injunction[$injunction->id]['injunction_id'] = $injunction->id;
                    $notifiction_injunction[$injunction->id]['date_first_status'] = $date_status;
                    $notifiction_injunction[$injunction->id]['date_first_status_format'] = $date_status_format;
                    $notifiction_injunction[$injunction->id]['status_id'] = $injunction->status_id;
                }
            }

            $memory_size[] = 'Отработал перебор - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'Отработал перебор PEAK - ' . (memory_get_peak_usage()) / 1024;
        } catch (Throwable $exception) {
            $errors[] = 'GetInjunctionsNotifications. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        if (!isset($notifiction_injunction)) {
            $notifiction_injunction = (object)array();
        }
        $warnings[] = $memory_size;
        return array('Items' => $notifiction_injunction, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetPabNotifications() - получение данных для уведомлений по ПАБ
     * @param null $data_post - JSON  идентификатором участка
     * @return array - массив со следующей стуруктурой: [checking_{checking_id}_{observation_number}]
     *                                                                  injunction_id:
     *                                                                  date_time:
     *                                                                  [auditor]
     *                                                                        worker_full_name:
     *                                                                        worker_role_title:
     *                                                                        worker_staff_number:
     *                                                                  [violator]
     *                                                                        worker_full_name:
     *                                                                        worker_role_title:
     *                                                                        worker_staff_number:
     *                                                                  inj_vio_id:
     *                                                                  [correct_measures]
     *                                                                          [correct_measure_id]
     *                                                                                  correct_measure_id:
     *                                                                                  correct_measure_status_id:
     *
     * @package frontend\controllers\notification
     *
     * @example http://amicum/read-manager-amicum?controller=notification\Notification&method=GetPabNotifications&subscribe=&data={%22company_department_id%22:802}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 06.08.2019 17:17
     */
    public static function GetPabNotifications($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок

        $violator_info = array();
        $memory_size = array();
        try {
            if ($data_post !== NULL && $data_post !== '') {
                $warnings[] = 'NotificationPab. Данные успешно переданы';
                $warnings[] = 'NotificationPab. Входной массив данных' . $data_post;
            }
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'NotificationPab. Декодировал входные параметры';
            if (
                property_exists($post_dec, 'company_department_id')
            )                                                                                                       // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'NotificationPab.Данные с фронта получены';
                $company_department_id = $post_dec->company_department_id;
            } else {
                throw new Exception('NotificationPab. Переданы некорректные входные параметры');
            }
            $start_mem = memory_get_usage();
            $memory_size[] = 'start_mem - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'start_mem PEAK - ' . (memory_get_peak_usage()) / 1024;

            $response = DepartmentController::GetAttachDeprtmentByUpper($company_department_id);
            $warnings[] = $response['warnings'];
            $errors[] = $response['errors'];
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения вложенных департаментов');
            }
            $company_departments = $response['Items'];

            /******************** Ищем ПАБы по участку ********************/
            $found_data_pab = Checking::find()
                ->joinWith(['checkingWorkerTypes checking_worker_type' => function ($check) {
                    $check->joinWith(['worker worker_checking_worker_type' => function ($worker) {
                        $worker->joinWith('employee employee_checking_worker_type');
                        $worker->joinWith('position position_checking_worker_type');
                    }]);
                }])
                ->joinWith(['injunctions inj' => function ($inj) {
                    $inj->andWhere(['inj.status_id' => 57]);
                }])
                ->joinWith('injunctions.firstInjunctionStatuses')
                ->joinWith('injunctions.lastInjunctionStatuses')
                ->joinWith('injunctions.injunctionViolations.violators')
                ->joinWith('injunctions.injunctionViolations.correctMeasures')
                ->where(['injunction.company_department_id' => $company_departments])
                ->andWhere(['injunction.kind_document_id' => CheckingController::KIND_PAB])
                ->limit(50000)
                ->all();
            $memory_size[] = 'Выгрузили проверки - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'Выгрузили проверки PEAK - ' . (memory_get_peak_usage()) / 1024;
            /******************** Ищем данные по работнику: роль, ФИО, табельный номер индексируя по идентификатору работника ********************/
            $roles = WorkerObject::find()
                ->select(['worker_object.worker_id as worker_id', 'role.title as role_title',
                    'CONCAT(employee.last_name, " ", employee.first_name, " ", employee.patronymic) as full_name',
                    'worker.tabel_number as worker_tabel_number'])
                ->innerJoin('worker', 'worker.id = worker_object.worker_id')
                ->innerJoin('employee', 'worker.employee_id = employee.id')
                ->innerJoin('role', 'worker_object.role_id = role.id')
                ->asArray()
                ->indexBy('worker_id')
                ->all();
            $memory_size[] = 'Выгрузили worker_object - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'Выгрузили worker_object PEAK - ' . (memory_get_peak_usage()) / 1024;
            /******************** Если ПАБ найден тогда перебираем и формируем результирующий массив ********************/
            if ($found_data_pab) {
                /******************** Перебор проверок ********************/
                foreach ($found_data_pab as $checking_item) {
                    /******************** Перебор работников по типам на проверке ********************/
                    foreach ($checking_item->checkingWorkerTypes as $checkingWorkerType) {
                        /**
                         * Если тип работника аудитор тогда записываем информацию о нём
                         */
                        if ($checkingWorkerType->worker_type_id == CheckingController::WORKER_TYPE_AUDITOR) {
                            /**
                             * Если у работника есть worker_object тогда берём данные от туда, если же нет, тогда
                             * берём данные из checkingWorkerType, а вместо роли пока стоит заглушка
                             */
                            if (isset($roles[$checkingWorkerType->worker_id])) {
                                $full_name = $roles[$checkingWorkerType->worker_id]['full_name'];
                                $role_title = $roles[$checkingWorkerType->worker_id]['role_title'];
                                $worker_staff_number = $roles[$checkingWorkerType->worker_id]['worker_tabel_number'];
                            } else {
                                $full_name = "{$checkingWorkerType->worker->employee->last_name} {$checkingWorkerType->worker->employee->first_name} {$checkingWorkerType->worker->employee->patronymic}";
                                $role_title = '-';//TODO 06.09.2019 rudov: заглушка
                                $worker_staff_number = $checkingWorkerType->worker->tabel_number;
                            }
                            $auditor['worker_full_name'] = $full_name;
                            $auditor['worker_role_title'] = $role_title;
                            $auditor['worker_staff_number'] = $worker_staff_number;
                        }
                    }
                    /******************** Перебор ПАБов ********************/
                    foreach ($checking_item->injunctions as $injunction) {
                        /**
                         * Дата первого статуса =  дата создания предписания
                         */
//                        $date_first = date('d.m.Y', strtotime($injunction->firstInjunctionStatuses->date_time));
                        foreach ($injunction->injunctionViolations as $injunctionViolation) {
                            $com = "checking_{$checking_item->id}_$injunction->observation_number";

                            foreach ($injunctionViolation->violators as $violator) {
                                if (isset($roles[$violator['worker']['id']])) {
                                    $role_title = $roles[$violator['worker']['id']]['role_title'];
                                } else {
                                    $role_title = '-';
                                }
                                $violator_info['worker_id'] = $violator['worker']['id'];
                                $violator_info['worker_role_title'] = $role_title;
                                $full_name_violator = "{$violator['workerEmployee']['last_name']} {$violator['workerEmployee']['first_name']} {$violator['workerEmployee']['patronymic']}";
                                $violator_info['worker_full_name'] = $full_name_violator;
                                $violator_info['worker_position_title'] = $violator['workerPosition']['title'];
                                $violator_info['worker_staff_number'] = $violator['worker']['tabel_number'];
                            }
//                            foreach ($injunctionViolation->violators as $violator) {
//                                /**
//                                 * Если у нарушителя есть worker_object тогда берём данные от туда, если же нету
//                                 * и нарушитель записан в $checkingWorkerType берём данные от туда,
//                                 * иначе берём данные из violtor
//                                 */
//                                if (isset($roles[$violator->worker_id])) {
//                                    $full_name = $roles[$violator->worker_id]['full_name'];
//                                    $role_title = $roles[$violator->worker_id]['role_title'];
//                                    $worker_staff_number = $roles[$violator->worker_id]['worker_tabel_number'];
//                                } else {
//                                    if (!$violator_info) {
//                                        $full_name = "{$violator->worker->employee->last_name} {$violator->worker->employee->first_name} {$violator->worker->employee->patronymic}";
//                                        $role_title = '-';//TODO 06.09.2019 rudov: заглушка
//                                        $worker_staff_number = $violator->worker->tabel_number;
//
//                                    } else {
//                                        $full_name = $violator_info['worker_full_name'];
//                                        $role_title = $violator_info['worker_role_title'];
//                                        $worker_staff_number = $violator_info['worker_staff_number'];
//                                    }
//                                }
//                                $violator_pab['worker_full_name'] = $full_name;
//                                $violator_pab['worker_role_title'] = $role_title;
//                                $violator_pab['worker_staff_number'] = $worker_staff_number;
//                            }
                            $notifiction_pab[$com]['injunction_id'] = $injunction->id;
                            if (isset($injunction->firstInjunctionStatuses->date_time)) {
                                $date_status_format = date('d.m.Y', strtotime($injunction->firstInjunctionStatuses->date_time));
                            } else {
                                $date_status_format = date('d.m.Y', strtotime($injunction->checking->date_time_start));
                            }
                            $notifiction_pab[$com]['date_time'] = $date_status_format;
                            $notifiction_pab[$com]['auditor'] = $auditor;
                            $notifiction_pab[$com]['violator'] = $violator_info;
                            $notifiction_pab[$com]['inj_vio_id'] = $injunctionViolation->id;
                            /******************** Перебор корректирующих мероприятий ********************/
                            foreach ($injunctionViolation->correctMeasures as $correctMeasure) {
                                $notifiction_pab[$com]['correct_measures'][$correctMeasure->id]['correct_measure_id'] = $correctMeasure->id;
                                $notifiction_pab[$com]['correct_measures'][$correctMeasure->id]['correct_measure_status_id'] = $correctMeasure->status_id;
                            }
                        }
                    }
                }
            }

            $memory_size[] = 'Сделали перебор - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'Сделали перебор PEAK - ' . (memory_get_peak_usage()) / 1024;
        } catch (Throwable $exception) {
            $errors[] = 'NotificationPab. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $memory_size;
        if (!isset($notifiction_pab)) {
            $notifiction_pab = (object)array();
        }

        return array('Items' => $notifiction_pab, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetCheckingPlan() - Получает список плановых аудитов по идентификатору участка
     * @param null $data_post - JSON с идентификатором участка
     * @return array
     *
     * @package frontend\controllers\notification
     *
     * @example http://amicum/read-manager-amicum?controller=notification\Notification&method=GetCheckingPlan&subscribe=&data={%22company_department_id%22:802}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 08.08.2019 16:21
     */
    public static function GetCheckingPlan($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $memory_size = array();
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetCheckingPlan. Данные с фронта не получены');
            }
            $warnings[] = 'GetCheckingPlan. Данные успешно переданы';
            $warnings[] = 'GetCheckingPlan. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetCheckingPlan. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'company_department_id')
            )                                                                                                       // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetCheckingPlan. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetCheckingPlan.Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $start_mem = memory_get_usage();
            $memory_size[] = 'start mem - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'start mem PEAK - ' . (memory_get_peak_usage()) / 1024;
            $date = date('Y-m-d', strtotime(BackendAssistant::GetDateNow()));

            $response = DepartmentController::GetAttachDeprtmentByUpper($company_department_id);
            $warnings[] = $response['warnings'];
            $errors[] = $response['errors'];
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения вложенных департаментов');
            }
            $company_departments = $response['Items'];

            /******************** Получение ближайшего планового аудита, отсортированный по дате в порядке возрастания ********************/
            $first_audit = Audit::find()
                ->select('audit.date_time as date')
                ->where(['company_department_id' => $company_departments])
                ->andWhere(['>=', 'date_time', $date])
                ->orderBy('date_time ASC')
                ->limit(1)
                ->scalar();
            $memory_size[] = 'Взяли даты первого аудита - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'Взяли даты первого аудита PEAK - ' . (memory_get_peak_usage()) / 1024;
            /**
             * Если по участку есть аудиты тогда получаем все аудиты на дату первого планового аудита
             */
            if ($first_audit) {
                /**
                 * Поиск всех плановых аудитов на дату ближайшего аудита
                 */
                $found_audits = Audit::find()
                    ->select([
                        'audit.id as audit_id',
                        'place.title as place_title',
                        'audit_place.id as audit_place_id',
                        'audit.date_time as audit_date_time'
                    ])
                    ->innerJoin('audit_place', 'audit_place.audit_id = audit.id')
                    ->innerJoin('place', 'audit_place.place_id = place.id')
                    ->where(['audit.company_department_id' => $company_departments])
                    ->andWhere(['audit.date_time' => $first_audit])
                    ->limit(50000)
                    ->asArray()
                    ->all();
                $memory_size[] = 'Выгрузили все аудиты на эту дату - ' . (memory_get_usage() - $start_mem) / 1024;
                $memory_size[] = 'Выгрузили все аудиты на эту дату PEAK - ' . (memory_get_peak_usage()) / 1024;
                /******************** Перебор плановых аудитов ********************/
                foreach ($found_audits as $audit) {
                    $checking_plan[$audit['audit_place_id']]['audit_id'] = $audit['audit_id'];
                    $checking_plan[$audit['audit_place_id']]['checking_plan_id'] = $audit['audit_place_id'];
                    $checking_plan[$audit['audit_place_id']]['date'] = $audit['audit_date_time'];
                    $checking_plan[$audit['audit_place_id']]['date_format'] = date('d.m.Y', strtotime($audit['audit_date_time']));
                    $checking_plan[$audit['audit_place_id']]['place_title'] = $audit['place_title'];
                }
                $memory_size[] = 'Выполнили перебор - ' . (memory_get_usage() - $start_mem) / 1024;
                $memory_size[] = 'Выполнили перебор PEAK - ' . (memory_get_peak_usage()) / 1024;
            }
            /**
             * Если не найдено никаких аудитов
             */

        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $memory_size;
        if (!isset($checking_plan)) {
            $checking_plan = (object)array();
        }

        return array('Items' => $checking_plan, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetNextExpertise() - Получает список экспертиз промышленной безопасности по идентификатору участка
     * @param null $data_post - JSON с идентификатором участка
     * @return array - массив с данными: [expertise_id]
     *                                            equipment_title:
     *                                            next_date_expertise:
     *
     *
     * @package frontend\controllers\notification
     *
     * @example http://amicum/read-manager-amicum?controller=notification\Notification&method=GetNextExpertise&subscribe=&data={%22company_department_id%22:802}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 05.09.2019 12:52
     */
    public static function GetNextExpertise($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'GetNextExpertise. Начало метода';
        $memory_size = array();
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetNextExpertise. Не переданы входные параметры');
            }
            $warnings[] = 'GetNextExpertise. Данные успешно переданы';
            $warnings[] = 'GetNextExpertise. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetNextExpertise. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id'))                                          // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetNextExpertise. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetNextExpertise. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $start_mem = memory_get_usage();
            $memory_size[] = 'start mem - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'start mem PEAK - ' . (memory_get_peak_usage()) / 1024;

            $response = DepartmentController::GetAttachDeprtmentByUpper($company_department_id);
            $warnings[] = $response['warnings'];
            $errors[] = $response['errors'];
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения вложенных департаментов');
            }
            $company_departments = $response['Items'];

            /******************** Поиск всех ЭПБ для участка ********************/
            $expertises = Expertise::find()
                ->select(['iso.title', 'isot.title as type_title', 'TIMESTAMPDIFF(MONTH,curdate(),expertise.date_next_expertise) as diff,expertise.id as exp_id', 'expertise.date_next_expertise'])
                ->innerJoin('industrial_safety_object iso', 'expertise.industrial_safety_object_id = iso.id')
                ->innerJoin('industrial_safety_object_type isot', 'iso.industrial_safety_object_type_id = isot.id')
                ->orderBy('expertise.date_next_expertise')
                ->asArray()
                ->where(['in', 'expertise.status_id', [62, 63]])
                ->andWhere(['expertise.company_department_id' => $company_departments])
                ->having('diff <= 6')
                ->asArray()
                ->all();
            $memory_size[] = 'Выгрузили ЭПБ - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'Выгрузили ЭПБ PEAK - ' . (memory_get_peak_usage()) / 1024;
//            Assistant::PrintR($expertises);
//            die;
            if (!empty($expertises)) {
                foreach ($expertises as $expertise) {
                    if ($expertise['diff'] <= 6 and $expertise['diff'] >= 1) {
                        $next_expertise[$expertise['exp_id']]['object_title'] = $expertise['title'];
                        $next_expertise[$expertise['exp_id']]['object_type_title'] = $expertise['type_title'];
                        $next_expertise[$expertise['exp_id']]['date_next_expertise'] = $expertise['date_next_expertise'];
                        $next_expertise[$expertise['exp_id']]['date_next_expertise_format'] = date('d.m.Y', strtotime($expertise['date_next_expertise']));
                        $next_expertise[$expertise['exp_id']]['flag'] = 'yellow';
                    } elseif ($expertise['diff'] <= 1) {
                        $next_expertise[$expertise['exp_id']]['object_title'] = $expertise['title'];
                        $next_expertise[$expertise['exp_id']]['object_type_title'] = $expertise['type_title'];
                        $next_expertise[$expertise['exp_id']]['date_next_expertise'] = $expertise['date_next_expertise'];
                        $next_expertise[$expertise['exp_id']]['date_next_expertise_format'] = date('d.m.Y', strtotime($expertise['date_next_expertise']));
                        $next_expertise[$expertise['exp_id']]['flag'] = 'red';
                    }
                }
                $memory_size[] = 'Выполнили перебор - ' . (memory_get_usage() - $start_mem) / 1024;
                $memory_size[] = 'Выполнили перебор PEAK - ' . (memory_get_peak_usage()) / 1024;
            }

        } catch (Throwable $exception) {
            $errors[] = 'GetNextExpertise. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetNextExpertise. Конец метода';
        $warnings[] = $memory_size;

        if (!isset($next_expertise)) {
            $next_expertise = (object)array();
        }
        return array('Items' => $next_expertise, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetReplacementSIZ() - Получает список СИЗ, у которых скоро выйдет срок (за 3 дня до замены СИЗа) по
     *                             идентификатору участка работника
     * @param null $data_post - JSON с идентификатором частка
     * @return array - массив с данными: [worker_id]
     *                                           date_replacement:
     *                                           siz_number:
     *                                           siz_title:
     *                                           full_name:
     *                                           position_title:
     *                                           worker_siz_id:
     *
     * @package frontend\controllers\notification
     *
     * @example http://amicum/read-manager-amicum?controller=notification\Notification&method=GetReplacementSIZ&subscribe=&data={%22company_department_id%22:802}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 06.09.2019 9:17
     */
    public static function GetReplacementSIZ($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'GetReplacementSIZ. Начало метода';
        $date_now = date('Y-m-d', strtotime(BackendAssistant::GetDateNow()));
        $memory_size = array();
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetReplacementSIZ. Не переданы входные параметры');
            }
            $warnings[] = 'GetReplacementSIZ. Данные успешно переданы';
            $warnings[] = 'GetReplacementSIZ. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetReplacementSIZ. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id'))                                          // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetReplacementSIZ. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetReplacementSIZ. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $start_mem = memory_get_usage();
            $memory_size[] = 'start mem - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'start mem PEAK - ' . (memory_get_peak_usage()) / 1024;

            $response = DepartmentController::GetAttachDeprtmentByUpper($company_department_id);
            $warnings[] = $response['warnings'];
            $errors[] = $response['errors'];
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения вложенных департаментов');
            }
            $company_departments = $response['Items'];
            /**
             *  Поиск СИЗов работников у которых дата списания меньше либо равны 3 дням
             */
            $found_worker_siz = WorkerSiz::find()
                ->select([
                    'worker_siz.date_write_off as date_write_off',
                    "datediff(worker_siz.date_write_off,'$date_now') as diff_date",
                    'employee.last_name as last_name',
                    'employee.first_name as first_name',
                    'employee.patronymic as patronymic',
                    'siz.title as siz_title',
                    'position.title as position_title',
                    'siz.id as siz_id',
                    'worker.id as worker_id',
                    'worker_siz.id as worker_siz_id',
                    'worker_siz.status_id as worker_siz_status_id',
                    'worker_siz.count_issued_siz as count_issued_siz',
                    'unit.short as unit_short',
                    'siz.wear_period as siz_wear_period',
                    'worker_siz.date_issue as worker_siz_data_issue'
                ])
                ->innerJoin('worker', 'worker_siz.worker_id = worker.id')
                ->innerJoin('position', 'position.id = worker.position_id')
                ->innerJoin('employee', 'employee.id = worker.employee_id')
                ->innerJoin('siz', 'siz.id = worker_siz.siz_id')
                ->innerJoin('unit', 'unit.id = siz.unit_id')
                ->where(['worker.company_department_id' => $company_departments])
                ->andWhere(['IN', 'worker_siz.status_id', [self::SIZ_ISSUED, self::SIZ_EXTENDED]])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date_now],
                    ['is', 'worker.date_end', null]
                ])
                ->having(['<=', 'diff_date', self::DAY_TO_VISIBLE_SIZ])
                ->asArray()
                ->all();
            $memory_size[] = 'Выполнили выборку worker_siz - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'Выполнили выборку worker_siz PEAK - ' . (memory_get_peak_usage()) / 1024;
            /**
             * Если есть СИЗы у которых дата замены >= 3 дням
             */
            if ($found_worker_siz) {
                /******************** Перебор найденных СИЗов ********************/
                foreach ($found_worker_siz as $worker_siz) {
                    if ($worker_siz['diff_date'] < 0) {
                        $flag = false;
                    } else {
                        $flag = true;
                    }
                    $name = mb_substr($worker_siz['first_name'], 0, 1);
                    $family_name = mb_substr($worker_siz['patronymic'], 0, 1);
                    $full_name = "{$worker_siz['last_name']} $name.$family_name.";
                    $replacement_siz[$worker_siz['worker_id']][$worker_siz['worker_siz_id']]['flag'] = $flag;
                    $replacement_siz[$worker_siz['worker_id']][$worker_siz['worker_siz_id']]['date_replacement'] = date('Y-m-d', strtotime($worker_siz['date_write_off']));
                    $replacement_siz[$worker_siz['worker_id']][$worker_siz['worker_siz_id']]['date_replacement_format'] = date('d.m.Y', strtotime($worker_siz['date_write_off']));
                    $replacement_siz[$worker_siz['worker_id']][$worker_siz['worker_siz_id']]['siz_number'] = $worker_siz['siz_id'];
                    $replacement_siz[$worker_siz['worker_id']][$worker_siz['worker_siz_id']]['siz_title'] = $worker_siz['siz_title'];
                    $replacement_siz[$worker_siz['worker_id']][$worker_siz['worker_siz_id']]['full_name'] = $full_name;
                    $replacement_siz[$worker_siz['worker_id']][$worker_siz['worker_siz_id']]['worker_id'] = $worker_siz['worker_id'];
                    $replacement_siz[$worker_siz['worker_id']][$worker_siz['worker_siz_id']]['check'] = false;
                    $replacement_siz[$worker_siz['worker_id']][$worker_siz['worker_siz_id']]['position_title'] = $worker_siz['position_title'];
                    $replacement_siz[$worker_siz['worker_id']][$worker_siz['worker_siz_id']]['worker_siz_id'] = $worker_siz['worker_siz_id'];
                    $replacement_siz[$worker_siz['worker_id']][$worker_siz['worker_siz_id']]['count_issued_siz'] = $worker_siz['count_issued_siz'];
                    $replacement_siz[$worker_siz['worker_id']][$worker_siz['worker_siz_id']]['worker_siz_status_id'] = $worker_siz['worker_siz_status_id'];
                    $replacement_siz[$worker_siz['worker_id']][$worker_siz['worker_siz_id']]['unit_short_title'] = $worker_siz['unit_short'];
                    $wear_period = (int)$worker_siz['siz_wear_period'];
                    if ($wear_period == 0) {
                        $wear_period = '-';
                    } else {
                        $wear_period = $wear_period . ' мес.';
                    }
                    $replacement_siz[$worker_siz['worker_id']][$worker_siz['worker_siz_id']]['wear_period'] = $wear_period;
                    $replacement_siz[$worker_siz['worker_id']][$worker_siz['worker_siz_id']]['data_issue'] = $worker_siz['worker_siz_data_issue'];
                    $replacement_siz[$worker_siz['worker_id']][$worker_siz['worker_siz_id']]['data_issue_format'] = date('d.m.Y', strtotime($worker_siz['worker_siz_data_issue']));
                }
                $memory_size[] = 'Выполнили перебор найденных СИЗ - ' . (memory_get_usage() - $start_mem) / 1024;
                $memory_size[] = 'Выполнили перебор найденных СИЗ PEAK - ' . (memory_get_peak_usage()) / 1024;
            } else {
                $errors[] = 'GetReplacementSIZ. Нет СИЗов, у которых дата замены >= 3 дням';
            }
            if (!isset($replacement_siz)) {
                $replacement_siz = (object)array();
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetReplacementSIZ. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetReplacementSIZ. Конец метода';
        $warnings[] = $memory_size;

        if (!isset($replacement_siz)) {
            $replacement_siz = (object)array();
        }
        return array('Items' => $replacement_siz, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * Метод WriteOffSiz() - Списание средств индивидуальной защиты
     * @param null $data_post - JSON с данными: base_extention - основание списания СИЗ,
     *                                          workers - массив людей у которых надо списать СИЗ
     * @return array - стандартный массив выходных данных
     *
     * @package frontend\controllers\notification
     *
     * @example amicum/read-manager-amicum?controller=notification\Notification&method=WriteOffSiz&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 06.09.2019 15:39
     */
    public static function WriteOffSiz($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $write_off = array();                                                                                            // Промежуточный результирующий массив
        $worker_siz_status = array();
        /**
         * Тестовый набор входных данных
         */
//        $data_post = '{"base_extention":"Основание списания СИЗ","workers":[1]}';
        $warnings[] = 'WriteOffSiz. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('WriteOffSiz. Не переданы входные параметры');
            }
            $warnings[] = 'WriteOffSiz. Данные успешно переданы';
            $warnings[] = 'WriteOffSiz. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'WriteOffSiz. Декодировал входные параметры';
            if (!property_exists($post_dec, 'base_extension') ||
                !property_exists($post_dec, 'workers'))                                                          // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('WriteOffSiz. Переданы некорректные входные параметры');
            }
            $warnings[] = 'WriteOffSiz. Данные с фронта получены';
            $base_extension = $post_dec->base_extension;
            $workers = $post_dec->workers;
            $date = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));
            $find_siz = Siz::find()
                ->select(['worker_siz.id as worker_siz_id',
                    'siz.wear_period as siz_wear_period',
                    'worker_siz.date_issue as date_issue',
                    'worker_siz.date_write_off as date_write_off'])
                ->innerJoin('worker_siz', 'siz.id = worker_siz.siz_id')
                ->where(['IN', 'worker_siz.status_id', [self::SIZ_ISSUED, self::SIZ_EXTENDED]])
                ->indexBy('worker_siz_id')
                ->asArray()
//                ->limit(50000)
                ->all();
            foreach ($workers as $worker) {
                if (isset($find_siz[$worker])) {
                    $date_start_wear = new DateTime($find_siz[$worker]['date_issue']);
                    $date_end_wear = new DateTime($find_siz[$worker]['date_write_off']);
                    $difference = $date_start_wear->diff($date_end_wear);
                    $precent = 0;
                    if ($find_siz[$worker]['siz_wear_period'] != 0) {
                        $precent = (int)((double)$difference->format('%y.%m') / $find_siz[$worker]['siz_wear_period']) * 100;
                    }
                    $worker_siz_status[] = [
                        'worker_siz_id' => $worker,
                        'date' => $date,
                        'comment' => $base_extension,
                        'percentage_wear' => (int)$precent,
                        'status_id' => self::SIZ_DECOMMISSIONED
                    ];
                } else {
                    $errors[] = 'Нет такой связки работника с СИЗом';
                }
            }
            $update_all_worker_siz_status = WorkerSiz::updateAll(['status_id' => self::SIZ_DECOMMISSIONED, 'date_write_off' => $date], ['in', 'id', $workers]);
            if ($update_all_worker_siz_status != 0) {
                $warnings[] = 'WriteOffSiz. Статусы у связки работника и СИЗа успешно обновлены';
            } else {
                throw new Exception('WriteOffSiz. Ошибка при обновлении статусов у связки работника и СИЗа');
            }
            $batch_worker_siz_status = Yii::$app->db->createCommand()->batchInsert('worker_siz_status',
                ['worker_siz_id', 'date', 'comment', 'percentage_wear', 'status_id'], $worker_siz_status)->execute();
            if ($batch_worker_siz_status != 0) {
                $warnings[] = 'WriteOffSiz. Статусы успешно добавлены';
            } else {
                throw new Exception('WriteOffSiz. Ошибка при добавлении статусов');
            }
        } catch (Throwable $exception) {
            $errors[] = 'WriteOffSiz. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors[] = $exception->getFile();
            $status *= 0;
        }
        $warnings[] = 'WriteOffSiz. Конец метода';

        return array('Items' => $write_off, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод ExtentionSIZ() - Продление средств индивидуальной защиты
     * @param null $data_post - JSON  с входными даннными: base_extention - основание проверки
     *                                                     count_extention - количетсво дней продленияв
     *                                                     workers - массив людей которым продляют СИЗы
     * @return array - стандартный массив выходных данных
     *
     * @package frontend\controllers\notification
     *
     * @example http://amicum/read-manager-amicum?controller=notification\Notification&method=ExtensionSIZ&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 10.09.2019 13:19
     */
    public static function ExtensionSIZ($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $extetion = array();                                                                                // Промежуточный результирующий массив
        $worker_siz_status = array();
        $result = array();
        $date_now = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));
        $warnings[] = 'ExtensionSIZ. Начало метода';
        /**
         * Тестовый набор входных данных
         */
//        $data_post = '{"base_extension":"Основание продления СИЗ","count_extension":7,"workers":[1]}';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('ExtentionSIZ. Не переданы входные параметры');
            }
            $warnings[] = 'ExtensionSIZ. Данные успешно переданы';
            $warnings[] = 'ExtensionSIZ. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'ExtensionSIZ. Декодировал входные параметры';
            if (!property_exists($post_dec, 'base_extension') ||
                !property_exists($post_dec, 'count_extension') ||
                !property_exists($post_dec, 'workers'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('ExtensionSIZ. Переданы некорректные входные параметры');
            }
            $warnings[] = 'ExtensionSIZ. Данные с фронта получены';
            $base_extension = $post_dec->base_extension;
            $count_extension = $post_dec->count_extension;
            $workers = $post_dec->workers;
            $find_siz = Siz::find()
                ->select(['worker_siz.id as worker_siz_id',
                    'siz.wear_period as siz_wear_period',
                    'worker_siz.date_issue as date_issue',
                    'worker_siz.date_write_off as date_write_off',
                    'siz.id as siz_id',
                    'worker_siz.worker_id as worker_id',
                    'worker_siz.size as worker_siz_size',
                    'worker_siz.count_issued_siz as worker_siz_count_issued_siz'])
                ->innerJoin('worker_siz', 'siz.id = worker_siz.siz_id')
                ->where(['worker_siz.status_id' => self::SIZ_ISSUED])
                ->indexBy('worker_siz_id')
                ->asArray()
                ->limit(50000)
                ->all();

            foreach ($workers as $worker) {

                $worker_int = (int)$worker;
                if (isset($find_siz[$worker_int])) {
                    $date_start_wear = new DateTime($find_siz[$worker_int]['date_issue']);
                    $date_end_wear = new DateTime($find_siz[$worker_int]['date_write_off']);
                    $difference = $date_start_wear->diff($date_end_wear);
                    $precent = 0;
                    if ($find_siz[$worker_int]['siz_wear_period'] != 0) {
                        $precent = (int)((double)$difference->format('%y.%m') / $find_siz[$worker_int]['siz_wear_period']) * 100;
                    }
                    $date_write_off_old = $find_siz[$worker_int]['date_write_off'];
                    $date_write_off_new = date('Y-m-d', strtotime($date_write_off_old . "$count_extension day"));
                    $warnings['write_off_old'] = $date_write_off_old;
                    $warnings['date_write_new'] = $date_write_off_new;
                    $extetion[] = [
                        $find_siz[$worker_int]['worker_siz_id'],
                        $find_siz[$worker_int]['siz_id'],
                        $find_siz[$worker_int]['worker_id'],
                        $find_siz[$worker_int]['worker_siz_size'],
                        $find_siz[$worker_int]['worker_siz_count_issued_siz'],
                        $find_siz[$worker_int]['date_issue'],
                        $date_write_off_new,
                        self::SIZ_EXTENDED
                    ];
                    $worker_siz_status[] = [
                        $worker,
                        $date_now,
                        $base_extension,
                        $precent,
                        self::SIZ_EXTENDED
                    ];
                }
            }
            if (!empty($extetion)) {
                $sql_to_insert = Yii::$app->db->queryBuilder->batchInsert('worker_siz',
                    ['id', 'siz_id', 'worker_id', 'size', 'count_issued_siz', 'date_issue', 'date_write_off', 'status_id'],
                    $extetion);
                $result_query_change_data = Yii::$app->db
                    ->createCommand($sql_to_insert . " ON DUPLICATE KEY UPDATE `date_write_off` = VALUES (`date_write_off`), `status_id` = VALUES (`status_id`)")
                    ->execute();
                if ($result_query_change_data != 0) {
                    $warnings[] = 'ExtensionSIZ. Данные успешно обновлены';
                } else {
                    throw new Exception('ExtentionSIZ. При обновлении данных произошла ошибка');
                }
            }
            if (!empty($worker_siz_status)) {
                $batch_worker_siz_status = Yii::$app->db
                    ->createCommand()
                    ->batchInsert('worker_siz_status', [
                        'worker_siz_id', 'date', 'comment', 'percentage_wear', 'status_id'
                    ], $worker_siz_status)
                    ->execute();
                if ($batch_worker_siz_status != 0) {
                    $warnings[] = 'ExtensionSIZ. Статусы успешно добавлены';
                } else {
                    throw new Exception('ExtentionSIZ. Ошибка при добавлении статусов');
                }
            }

            $extesion_worker_siz = WorkerSiz::findAll(['in', 'id', $workers]);
            foreach ($extesion_worker_siz as $worker_siz) {
                $result[$worker_siz->id]['worker_siz_id'] = $worker_siz->id;
                $result[$worker_siz->id]['date_time_write_off'] = $worker_siz->date_write_off;
                $result[$worker_siz->id]['worker_siz_status_id'] = $worker_siz->status_id;
            }
        } catch (Throwable $exception) {
            $errors[] = 'ExtensionSIZ. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'ExtensionSIZ. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetCheckup() - Возвращает данные для блоку уведомлений в части "Запланирован медосмотр"
     * @param null $data_post - JSON с идентификатором участка
     * @return array - список объектов со следующей структурой:
     *      {worker_id}
     *         worker_id                - ключ работника
     *         worker_full_name         - ФИО
     *         worker_staff_number      - табельный номер работника
     *         worker_position_title    - должность
     *         checkup_date_start       - дата начала медосмотра
     *         checkup_date_end         - дата окончания медосмотра
     *         flag                     - true  - если до окончания срока медосмотра осталось 2 недели или менее, то возвращается ораньжевый цвет
     *                                  | false - иначе срок замены просрочен, то возвращается красный цвет
     *                                  | null  - во всех остальных случаях
     *
     * @package frontend\controllers\notification
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=notification\Notification&method=GetCheckup&subscribe=&data={%22company_department_id%22:801}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 11.09.2019 11:56
     */
    public static function GetCheckup($data_post = NULL)
    {
        $log = new LogAmicumFront("GetCheckup");

        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $log->addLog("Данные успешно переданы");

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            if (
                !property_exists($post_dec, 'company_department_id')
            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $company_department_id = $post_dec->company_department_id;

            $response = DepartmentController::GetAttachDeprtmentByUpper($company_department_id);
            $warnings[] = $response['warnings'];
            $errors[] = $response['errors'];
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения вложенных департаментов');
            }
            $company_departments = $response['Items'];
            /**
             * Получение текущих: месяца, дня, года
             */
            $date_now = date('Y-m-d', strtotime(BackendAssistant::GetDateNow()));

            /**
             * Отнимаем от текущей даты 14 дней для показа уведомлений
             */
            $mk_date_plan = date('Y-m-d', strtotime($date_now . "- 14 days"));

            $physical_schedules = PhysicalSchedule::find()
                ->select([
                    'physical_worker.worker_id as worker_id',
                    'position.title as position_title',
                    'physical_schedule.company_department_id as company_department_id',
                    'physical_schedule.date_start as date_start',
                    'physical_schedule.date_end as date_end',
                    'worker.tabel_number as worker_tabel_number',
                    'employee.first_name as emp_first_name',
                    'employee.last_name as emp_last_name',
                    'employee.patronymic as emp_patronymic',
                    'physical_worker_date.id as physical_worker_date_id'
                ])
                ->innerJoin('physical_worker', 'physical_worker.physical_schedule_id = physical_schedule.id')
                ->leftJoin('physical_worker_date', 'physical_worker.id = physical_worker_date.physical_worker_id')
                ->innerJoin('worker', 'physical_worker.worker_id = worker.id')
                ->innerJoin('position', 'worker.position_id = position.id')
                ->innerJoin('employee', 'employee.id = worker.employee_id')
//                ->where([
//                    'physical_schedule.status_id' => self::STATUS_ACTIVE])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date_now],
                    ['is', 'worker.date_end', null]
                ])
                ->andWhere(['physical_schedule.company_department_id' => $company_departments])
                ->asArray()
                ->all();

            $log->addLog("Выгрузили из графика медосмотров");

            $med_report = MedReport::find()
                ->innerJoin('physical_worker_date', 'physical_worker_date.id = med_report.physical_worker_date')
                ->indexBy(
                    function ($report) {
                        return $report['worker_id'] . '_' . $report['physical_worker_date'];
                    }
                )
                ->all();

            $log->addLog("Выгрузили все результаты медосмотров");

            if ($physical_schedules) {
                foreach ($physical_schedules as $physical_schedule) {
                    $name = mb_substr($physical_schedule['emp_first_name'], 0, 1);
                    $patronymic = mb_substr($physical_schedule['emp_patronymic'], 0, 1);
                    $full_name = $physical_schedule['emp_last_name'];
                    $full_name = $full_name . " " . ($name ? $name . "." : "");
                    $full_name = $full_name . " " . ($patronymic ? $patronymic . "." : "");
                    /**
                     * Начинаем показывать уведомление за 2 недели до начала медосмотра у человека
                     * И показываем до тех пор, пока у этого человека не появиться заключение медосмотра
                     */
                    $checkup[$physical_schedule['worker_id']]['worker_id'] = $physical_schedule['worker_id'];
                    $checkup[$physical_schedule['worker_id']]['worker_full_name'] = $full_name;
                    $checkup[$physical_schedule['worker_id']]['worker_date_end'] = $physical_schedule['date_end'];
                    $checkup[$physical_schedule['worker_id']]['worker_staff_number'] = $physical_schedule['worker_tabel_number'];
                    $checkup[$physical_schedule['worker_id']]['worker_position_title'] = $physical_schedule['position_title'];
                    $checkup[$physical_schedule['worker_id']]['checkup_date_start'] = date('d.m.Y', strtotime($physical_schedule['date_start']));
                    $checkup[$physical_schedule['worker_id']]['checkup_date_end'] = date('d.m.Y', strtotime($physical_schedule['date_end']));

                    // true - если до окончания срока медосмотра осталось 2 недели или менее, то возвращается ораньжевый цвет
                    // false - иначе срок замены просрочен, то возвращается красный цвет
                    // null - во всех остальных случаях

                    if ($physical_schedule['date_start'] >= $mk_date_plan) {
                        $checkup[$physical_schedule['worker_id']]['flag'] = true;
                    } elseif (!isset($med_report[$physical_schedule['worker_id'] . '_' . $physical_schedule['physical_worker_date_id']])) {
                        $checkup[$physical_schedule['worker_id']]['flag'] = false;
                    } else {
                        $checkup[$physical_schedule['worker_id']]['flag'] = null;
                    }
                }
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!isset($checkup)) {
            $checkup = (object)array();
        }

        $log->addLog("Конец метода");

        return array_merge(['Items' => $checkup], $log->getLogAll());
    }


    /**
     * Метод GetInstructionNotifications() - Определяет  тип оставшегося времени до инструктажа (повторного) для людей из подразделения
     * @param null $data_post - company_department_id
     * @return array
     *                     название типа к которому относится
     *                         - id   [
     *                              - name                - имя+инициалы сотрудника
     *                              - tabel_number        - табельный номер
     *                              - date_time           - дата последнего инструктажа
     *                              - type_notification   - тип уведомления
     *                                      ]
     *Выходные параметры:
     *
     *
     * @package frontend\controllers\notification
     *Входные обязательные параметры:  company_department_id
     * @example localhost/read-manager-amicum?controller=notification\Notification&method=GetInstructionNotifications&subscribe=&data={%22company_department_id%22:20028766}
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 16.09.2019 9:43
     */
    public static function GetInstructionNotifications($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $list_briefers = array();
        $memory_size = array();
        $warnings[] = 'GetInstructionNotifications. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetInstructionNotifications. Данные с фронта не получены');
            }
            $warnings[] = 'GetInstructionNotifications. Данные успешно переданы';
            $warnings[] = 'GetInstructionNotifications. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetInstructionNotifications. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'company_department_id'))
            ) {
                throw new Exception('GetInstructionNotifications. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'GetInstructionNotifications. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $start_mem = memory_get_usage();
            $memory_size[] = 'start mem - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'start mem PEAK - ' . (memory_get_peak_usage()) / 1024;
            $today = date('Y-m-d', strtotime(BackendAssistant::GetDateNow()));
            // $today = '2019-08-25';

            $response = DepartmentController::GetAttachDeprtmentByUpper($company_department_id);
            $warnings[] = $response['warnings'];
            $errors[] = $response['errors'];
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения вложенных департаментов');
            }
            $company_departments = $response['Items'];

            // люди по подразделению, которые работающие (не уволены) + работают на сегодня-день
            $workers = Worker::find()
                ->select(' worker.employee_id, employee.last_name, employee.first_name, employee.patronymic, worker.tabel_number,position.title, worker.id')
                ->innerJoin('employee', 'worker.employee_id = employee.id')
                ->innerJoin('position', 'worker.position_id = position.id')
                ->innerJoin('grafic_tabel_date_plan', 'grafic_tabel_date_plan.worker_id = worker.id')
                ->innerJoin('grafic_tabel_main', 'grafic_tabel_date_plan.grafic_tabel_main_id = grafic_tabel_main.id')
                ->where(['grafic_tabel_main.company_department_id' => $company_departments])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $today],
                    ['is', 'worker.date_end', null]
                ])
                ->andWhere(['grafic_tabel_date_plan.date_time' => $today])
                ->andWhere(['grafic_tabel_date_plan.working_time_id' => self::WORK])
                ->asArray()
                ->all();
            $memory_size[] = 'Выгрузили работающих работинков подразделения - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'Выгрузили работающих работинков подразделения PEAK - ' . (memory_get_peak_usage()) / 1024;
            // Assistant::PrintR($workers);
            if ($workers) {         //выполняем действия если такие работники вообще есть
                foreach ($workers as $worker) {                                      //формируем список
                    $worker_ids[] = $worker['id'];
                }
                $memory_size[] = 'Сформировали из них массив - ' . (memory_get_usage() - $start_mem) / 1024;
                $memory_size[] = 'Сформировали из них массив PEAK - ' . (memory_get_peak_usage()) / 1024;

                //вытаскиваем по этим сотрудникам последние графики
                $briefings = Briefing::find()
                    ->select(' briefer.worker_id, max(briefing.date_time) as max_date_briefing, worker.employee_id,
                 employee.last_name, employee.first_name, employee.patronymic, worker.tabel_number,position.title')
                    ->andWhere(['in', 'briefer.worker_id', $worker_ids])
                    ->andWhere(['briefing.type_briefing_id' => self::TYPE_BRIEFING_TWO])
                    ->innerJoin('briefer', 'briefer.briefing_id = briefing.id')
                    ->innerJoin('worker', 'briefer.worker_id = worker.id')
                    ->innerJoin('employee', 'worker.employee_id = employee.id')
                    ->innerJoin('position', 'worker.position_id = position.id')
                    ->groupBy('briefer.worker_id')
                    ->asArray()
                    ->all();
                $memory_size[] = 'Выгрузили нИнструктажи по работникам - ' . (memory_get_usage() - $start_mem) / 1024;
                $memory_size[] = 'Выгрузили нИнструктажи по работникам PEAK - ' . (memory_get_peak_usage()) / 1024;
                foreach ($briefings as $briefing) {
                    array_push($list_briefers, $briefing['worker_id']);                                          //запоминаем сотрудников, которые прошли инструктаж
                    $between_date = (strtotime($today) - strtotime($briefing['max_date_briefing'])) / (60 * 60 * 24);
                    $first_name = mb_substr($briefing['first_name'], 0, 1);                                                //делаем красиво фамилию и инициалы
                    $patronymic = mb_substr($briefing['patronymic'], 0, 1) . '.';
                    $name = "{$briefing['last_name']} $first_name.$patronymic";
                    if ($between_date < self::DAY_TYPE_TWO && $between_date > self::DAY_TYPE_ONE) {//95
                        $type_notification[$briefing['tabel_number']] =
                            [
                                'name' => $name,
                                'tabel_number' => $briefing['tabel_number'],
                                'position' => $briefing['title'],
                                'date' => date('d.m.Y', strtotime($briefing['max_date_briefing'] . '+3 month')),
                                'type_notification' => self::TYPE_YELLOW
                            ];
                    } else if ($between_date > self::DAY_TYPE_TWO) {
                        $type_notification[$briefing['tabel_number']] =
                            [
                                'name' => $name,
                                'tabel_number' => $briefing['tabel_number'],
                                'position' => $briefing['title'],
                                'date' => date('d.m.Y', strtotime($briefing['max_date_briefing'] . '+3 month')),
                                'type_notification' => self::TYPE_RED
                            ];
                    }
                }
                $memory_size[] = 'Выполнили пребор инструктажей - ' . (memory_get_usage() - $start_mem) / 1024;
                $memory_size[] = 'Выполнили пребор инструктажей PEAK - ' . (memory_get_peak_usage()) / 1024;
                $list_briefer = array_diff($worker_ids, $list_briefers);
                foreach ($workers as $worker) {
                    if (in_array($worker['id'], $list_briefer)) {
                        $first_name = mb_substr($worker['first_name'], 0, 1);                                                //делаем красиво фамилию и инициалы
                        $patronymic = mb_substr($worker['patronymic'], 0, 1);
                        $name = "{$worker['last_name']} $first_name.$patronymic.";
                        $type_notification[$worker['tabel_number']] = ['name' => $name, 'tabel_number' => $worker['tabel_number'], 'position' => $worker['title'],
                            'date' => 'Инструктаж не проводился', 'type_notification' => self::TYPE_NOT];
                    }

                }

            } else {
                $warnings[] = 'GetInstructionNotifications. Сотрудников, которые работают сегодня (не уволены) не найдено';
            }


        } catch (Throwable $exception) {
            $errors[] = 'GetInstructionNotifications. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetInstructionNotifications. Конец метода';
        $warnings[] = $memory_size;

        if (!isset($type_notification)) {
            $type_notification = (object)array();
        }

        return array('Items' => $type_notification, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetCheckKnowledge() - Получение  данных для уведомлений "Запланировано обучение"
     * @param null $data_post - JSON с данными: идентификатор участка
     * @return array - массив со структурой: [worker_id]
     *                                             worker_id:
     *                                             full_name:
     *                                             stuff_number:
     *                                             position_title:
     *                                             date_check_knowledge:
     *                                             date_check_knowledge_formated:
     *
     * @throws Exception
     * @example http://amicum/read-manager-amicum?controller=notification\Notification&method=GetCheckKnowledge&subscribe=&data={%22company_department_id%22:4029938}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 21.10.2019 15:34
     * @package frontend\controllers\notification
     *
     */
    public static function GetCheckKnowledge($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок

        $memory_size = array();
        $date_now = new DateTime(date('Y-m-d', strtotime(BackendAssistant::GetDateNow())));
        $for_select_date = date('Y-m-d', strtotime(BackendAssistant::GetDateNow()));
        $warnings[] = 'GetCheckKnowledge. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetCheckKnowledge. Не переданы входные параметры');
            }
            $warnings[] = 'GetCheckKnowledge. Данные успешно переданы';
            $warnings[] = 'GetCheckKnowledge. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetCheckKnowledge. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetCheckKnowledge. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetCheckKnowledge. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $start_mem = memory_get_usage();
            $memory_size[] = 'start mem - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'start mem PEAK - ' . (memory_get_peak_usage()) / 1024;

            $response = DepartmentController::GetAttachDeprtmentByUpper($company_department_id);
            $warnings[] = $response['warnings'];
            $errors[] = $response['errors'];
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения вложенных департаментов');
            }
            $company_departments = $response['Items'];

            $found_check_knowledge_workers = CheckKnowledge::find()
                ->select('
                        check_knowledge.id                          as check_knowledge_id,
                        ckw.worker_id                               as check_worker_id,
                        check_knowledge.company_department_id       as check_knowledge_company_department_id,
                        check_knowledge.type_check_knowledge_id     as type_check_knowledge_id,
                        check_knowledge.date                        as check_knowledge_date,
                        em.first_name                               as first_name,
                        em.last_name                                as last_name,
                        em.patronymic                               as patronymic,
                        po.title                                    as position_title,
                        w.tabel_number                              as tabel_number
                ')
                ->leftJoin('check_knowledge_worker ckw', 'check_knowledge.id = ckw.check_knowledge_id')
                ->leftJoin('worker w', 'ckw.worker_id = w.id')
                ->leftJoin('employee em', 'w.employee_id = em.id')
                ->leftJoin('position po', 'w.position_id = po.id')
                ->leftJoin('check_protocol cp', 'check_knowledge.id = cp.check_knowledge_id')
                ->where(['check_knowledge.company_department_id' => $company_departments])
                ->andWhere(['in', 'check_knowledge.type_check_knowledge_id', [1, 2]])
                ->andWhere(['or',
                    ['>', 'w.date_end', $for_select_date],
                    ['is', 'w.date_end', null]
                ])
                ->orderBy('check_knowledge.date ASC')
                ->indexBy('check_worker_id')
                ->asArray()
                ->all();
            $memory_size[] = 'Выгрузили проверку знаний - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'Выгрузили проверку знаний PEAK - ' . (memory_get_peak_usage()) / 1024;
            if (isset($found_check_knowledge_workers)) {
                foreach ($found_check_knowledge_workers as $check_knowledge_worker) {
                    if ($check_knowledge_worker['type_check_knowledge_id'] == 2) {
                        $next_date = date('Y-m-d', strtotime($check_knowledge_worker['check_knowledge_date'] . "+3 year"));
                    } else {
                        $next_date = date('Y-m-d', strtotime($check_knowledge_worker['check_knowledge_date'] . "+1 year"));
                    }
                    $dateTime = new DateTime(date('Y-m-d', strtotime($next_date)));

                    $diff_date = $date_now->diff($dateTime);
                    $format_diff = $diff_date->format('%r%a');
                    if ($format_diff <= 14 && $format_diff >= 0) {
                        $name = mb_substr($check_knowledge_worker['first_name'], 0, 1);
                        $patronymic = mb_substr($check_knowledge_worker['patronymic'], 0, 1);
                        $check_knowledge[$check_knowledge_worker['check_worker_id']]['worker_id'] = $check_knowledge_worker['check_worker_id'];
                        $check_knowledge[$check_knowledge_worker['check_worker_id']]['full_name'] = "{$check_knowledge_worker['last_name']} $name. $patronymic.";
                        $check_knowledge[$check_knowledge_worker['check_worker_id']]['stuff_number'] = $check_knowledge_worker['tabel_number'];
                        $check_knowledge[$check_knowledge_worker['check_worker_id']]['position_title'] = $check_knowledge_worker['position_title'];
                        $check_knowledge[$check_knowledge_worker['check_worker_id']]['date_check_knowledge'] = $next_date;
                        $check_knowledge[$check_knowledge_worker['check_worker_id']]['date_check_knowledge_formated'] = date('d.m.Y', strtotime($next_date));
                        $check_knowledge[$check_knowledge_worker['check_worker_id']]['flag'] = true;
                    } elseif ($format_diff < 0) {
                        $name = mb_substr($check_knowledge_worker['first_name'], 0, 1);
                        $patronymic = mb_substr($check_knowledge_worker['patronymic'], 0, 1);
                        $check_knowledge[$check_knowledge_worker['check_worker_id']]['worker_id'] = $check_knowledge_worker['check_worker_id'];
                        $check_knowledge[$check_knowledge_worker['check_worker_id']]['full_name'] = "{$check_knowledge_worker['last_name']} $name. $patronymic.";
                        $check_knowledge[$check_knowledge_worker['check_worker_id']]['stuff_number'] = $check_knowledge_worker['tabel_number'];
                        $check_knowledge[$check_knowledge_worker['check_worker_id']]['position_title'] = $check_knowledge_worker['position_title'];
                        $check_knowledge[$check_knowledge_worker['check_worker_id']]['date_check_knowledge'] = $next_date;
                        $check_knowledge[$check_knowledge_worker['check_worker_id']]['date_check_knowledge_formated'] = date('d.m.Y', strtotime($next_date));
                        $check_knowledge[$check_knowledge_worker['check_worker_id']]['flag'] = false;
                    }
                }
                $memory_size[] = 'Выполнили перебор - ' . (memory_get_usage() - $start_mem) / 1024;
                $memory_size[] = 'Выполнили перебор PEAK - ' . (memory_get_peak_usage()) / 1024;
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetCheckKnowledge. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetCheckKnowledge. Конец метода';
        $warnings[] = $memory_size;

        if (!isset($check_knowledge)) {
            $check_knowledge = (object)array();
        }
        return array('Items' => $check_knowledge, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetCertification() - Метод получения аттестации по идентификатору работника для блока "Назначенная аттестация"
     * @param null $data_post
     * @return array
     *
     * @throws Exception
     * @example http://amicum/read-manager-amicum?controller=notification\Notification&method=GetCertification&subscribe=&data={%22company_department_id%22:20028748}
     *
     * @package frontend\controllers\notification
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 22.10.2019 9:42
     */
    public static function GetCertification($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $memory_size = array();
        $date_now = new DateTime(date('Y-m-d', strtotime(BackendAssistant::GetDateNow())));
        $for_select_date = date('Y-m-d', strtotime(BackendAssistant::GetDateNow()));
        $warnings[] = 'GetCertification. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetCertification. Не переданы входные параметры');
            }
            $warnings[] = 'GetCertification. Данные успешно переданы';
            $warnings[] = 'GetCertification. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetCertification. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetCertification. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetCertification. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $start_mem = memory_get_usage();
            $memory_size[] = 'start mem - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'start mem PEAK - ' . (memory_get_peak_usage()) / 1024;

            $response = DepartmentController::GetAttachDeprtmentByUpper($company_department_id);
            $warnings[] = $response['warnings'];
            $errors[] = $response['errors'];
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения вложенных департаментов');
            }
            $company_departments = $response['Items'];

            $found_check_certification = CheckKnowledge::find()
                ->select('
                        check_knowledge.id                          as check_knowledge_id,
                        ckw.worker_id                               as check_worker_id,
                        check_knowledge.company_department_id       as check_knowledge_company_department_id,
                        check_knowledge.type_check_knowledge_id     as type_check_knowledge_id,
                        check_knowledge.date                        as check_knowledge_date,
                        em.first_name                               as first_name,
                        em.last_name                                as last_name,
                        em.patronymic                               as patronymic,
                        po.title                                    as position_title,
                        w.tabel_number                              as tabel_number
                ')
                ->leftJoin('check_knowledge_worker ckw', 'check_knowledge.id = ckw.check_knowledge_id')
                ->leftJoin('worker w', 'ckw.worker_id = w.id')
                ->leftJoin('employee em', 'w.employee_id = em.id')
                ->leftJoin('position po', 'w.position_id = po.id')
                ->leftJoin('check_protocol cp', 'check_knowledge.id = cp.check_knowledge_id')
                ->where(['check_knowledge.company_department_id' => $company_departments])
                ->andWhere(['in', 'check_knowledge.type_check_knowledge_id', 3])
                ->andWhere(['or',
                    ['>', 'w.date_end', $for_select_date],
                    ['is', 'w.date_end', null]
                ])
//                ->andWhere(['check_worker_id'=>2052709])
                ->orderBy('check_knowledge.date ASC')
                ->indexBy('check_worker_id')
                ->asArray()
                ->all();
            $memory_size[] = 'Выгрузили аттестации - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'Выгрузили аттестации PEAK - ' . (memory_get_peak_usage()) / 1024;
//            Assistant::PrintR($found_check_knowledge_workers);
//            die;
            if (isset($found_check_certification)) {
                foreach ($found_check_certification as $check_cerifiaction) {
                    $next_date = date('Y-m-d', strtotime($check_cerifiaction['check_knowledge_date'] . "+3 year"));
                    $dateTime = new DateTime(date('Y-m-d', strtotime($next_date)));
                    $diff_date = $date_now->diff($dateTime);
                    $format_diff = $diff_date->format('%r%a');

                    if ($format_diff <= 14 && $format_diff >= 0) {
                        $name = mb_substr($check_cerifiaction['first_name'], 0, 1);
                        $patronymic = mb_substr($check_cerifiaction['patronymic'], 0, 1);
                        $check_knowledge[$check_cerifiaction['check_worker_id']]['worker_id'] = $check_cerifiaction['check_worker_id'];
                        $check_knowledge[$check_cerifiaction['check_worker_id']]['full_name'] = "{$check_cerifiaction['last_name']} $name. $patronymic.";
                        $check_knowledge[$check_cerifiaction['check_worker_id']]['stuff_number'] = $check_cerifiaction['tabel_number'];
                        $check_knowledge[$check_cerifiaction['check_worker_id']]['position_title'] = $check_cerifiaction['position_title'];
                        $check_knowledge[$check_cerifiaction['check_worker_id']]['date_check_knowledge'] = $next_date;
                        $check_knowledge[$check_cerifiaction['check_worker_id']]['date_check_knowledge_formated'] = date('d.m.Y', strtotime($next_date));
                        $check_knowledge[$check_cerifiaction['check_worker_id']]['flag'] = true;
                    } elseif ($format_diff < 0) {
                        $name = mb_substr($check_cerifiaction['first_name'], 0, 1);
                        $patronymic = mb_substr($check_cerifiaction['patronymic'], 0, 1);
                        $check_knowledge[$check_cerifiaction['check_worker_id']]['worker_id'] = $check_cerifiaction['check_worker_id'];
                        $check_knowledge[$check_cerifiaction['check_worker_id']]['full_name'] = "{$check_cerifiaction['last_name']} $name. $patronymic.";
                        $check_knowledge[$check_cerifiaction['check_worker_id']]['stuff_number'] = $check_cerifiaction['tabel_number'];
                        $check_knowledge[$check_cerifiaction['check_worker_id']]['position_title'] = $check_cerifiaction['position_title'];
                        $check_knowledge[$check_cerifiaction['check_worker_id']]['date_check_knowledge'] = $next_date;
                        $check_knowledge[$check_cerifiaction['check_worker_id']]['date_check_knowledge_formated'] = date('d.m.Y', strtotime($next_date));
                        $check_knowledge[$check_cerifiaction['check_worker_id']]['flag'] = false;
                    }
                }
                $memory_size[] = 'Выполнили перебор - ' . (memory_get_usage() - $start_mem) / 1024;
                $memory_size[] = 'Выполнили перебор PEAK - ' . (memory_get_peak_usage()) / 1024;
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetCertification. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetCertification. Конец метода';
        if (empty($check_knowledge)) {
            $check_knowledge = (object)array();
        }
        $warnings[] = $memory_size;

        return array('Items' => $check_knowledge, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetInquiry() - Метод получения данных о несчастном случае, аварии или инциденте
     * @param null $data_post - JSON с идентификатором участка
     * @return array - массив со структурой: [inquiry_pb_id]
     *                                              inquiry_pb_id:
     *                                              case_pb:
     *                                              date_time_create:
     *                                              date_time_create_format:
     *                                              description:
     *
     * @package frontend\controllers\notification
     *
     * @example http://amicum/read-manager-amicum?controller=notification\Notification&method=GetInquiry&subscribe=&data={%22company_department_id%22:20028748}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.10.2019 9:39
     */
    public static function GetInquiry($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок

        $memory_size = array();
        $date_now = BackendAssistant::GetDateFormatYMD();
        $warnings[] = 'GetInquiry. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetInquiry. Не переданы входные параметры');
            }
            $warnings[] = 'GetInquiry. Данные успешно переданы';
            $warnings[] = 'GetInquiry. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetInquiry. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetInquiry. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetInquiry. Данные с фронта получены';
            $start_mem = memory_get_usage();
            $memory_size[] = 'start mem - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'start mem PEAK - ' . (memory_get_peak_usage()) / 1024;
            $company_department_id = $post_dec->company_department_id;
            $date_start = $date_now . ' 00:00:00';
            $date_end = $date_now . ' 23:59:59';

            $response = DepartmentController::GetAttachDeprtmentByUpper($company_department_id);
            $warnings[] = $response['warnings'];
            $errors[] = $response['errors'];
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения вложенных департаментов');
            }
            $company_departments = $response['Items'];

            $found_events_pb = EventPb::find()
                ->joinWith('casePb')
                ->joinWith('inquiryPb')
                ->where(['event_pb.company_department_id' => $company_departments])
                ->andWhere(['>=', 'event_pb.date_time_event', $date_start])
                ->andWhere(['<=', 'event_pb.date_time_event', $date_end])
                ->andWhere(['in', 'event_pb.case_pb_id', [1, 2, 3]])
                ->all();

            if (!empty($found_events_pb)) {
                foreach ($found_events_pb as $event_pb) {
                    $event_pb_id = $event_pb->id;
                    $result_inquiry_pb[$event_pb_id]['event_pb_id'] = $event_pb->id;
                    $result_inquiry_pb[$event_pb_id]['case_pb'] = $event_pb->casePb->title;
                    $result_inquiry_pb[$event_pb_id]['date_time_create'] = $event_pb->date_time_event;
                    $result_inquiry_pb[$event_pb_id]['date_time_create_format'] = date('d.m.Y H:i', strtotime($event_pb->date_time_event));
                    $result_inquiry_pb[$event_pb_id]['description'] = '';
                    if (isset($event_pb->inquiryPb->description_event_pb) && !empty($event_pb->inquiryPb->description_event_pb)) {
                        $result_inquiry_pb[$event_pb_id]['description'] = $event_pb->inquiryPb->description_event_pb;
                    }
                }
            }
            $memory_size[] = 'Выгрузили несчастные слуаи - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'Выгрузили несчастные слуаи PEAK - ' . (memory_get_peak_usage()) / 1024;
        } catch (Throwable $exception) {
            $errors[] = 'GetInquiry. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetInquiry. Конец метода';
        $warnings[] = $memory_size;

        if (!isset($result_inquiry_pb)) {
            $result_inquiry_pb = (object)array();
        }

        return array('Items' => $result_inquiry_pb, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    // CheckRestriction - метод проверки наличия ограничений у работников/работника на текущую дату
    // входные данные:
    //      workers                                 - список работников на проверку ограничений
    //          [worker_id]                             - массив ключей работника
    //      date_time                               - дата на которую получаем ограничения
    //      company_department_id                   - ключ подразделения
    // выходные данные:
    //      restriction_siz:                        - ограничения СИЗ
    //          {worker_id}                             - массив работников имеющих ограничения
    //              worker_id:                              - ключ работника
    //              sizes:                                  - список просроченных СИЗ
    //                  {siz_id}
    //                      siz_id:                                 - ключ сиз
    //                      siz_title:                              - наименование сиз
    //                      date_issue:                             - дата выдачи
    //                      wear_period:                            - период носки
    //                      siz_number:                             - номер СИЗ
    //                      date_replacement:                       - дата замены
    //      restriction_pb:                         - ограничения по предписаниям не в работе
    //          {injunction_id}                 -ключ предписания
    //              injunction_id:                  - ключ предписания
    //              instruct_id:                    - ключ предписания внутреннего ПАБ
    //              rostex_number:                  - ключ предписания РТН ПАБ
    //              place_title:                    - наименование места где было выдано предписание
    //              place_id:                       - ключ места
    //              status_id:                      - ключ статус предписания
    //              status_title:                   - наименвоание статуса предписания
    //              checking_date_time:             - дата проведения проверки
    //              checking_date_time_format:      - дата проведения проверки форматированная
    //              observation_number:             - под номер предписания
    //              injunction_violations:          - список нарушений
    //                      {injunction_violation_id}
    //                              injunction_violation_id     - ключ нарушения
    //                              violation_title             - описание нарушения
    //                              kind_stop_pb_id             - ключ вида остановки
    //                              kind_stop_pb_title          - наименование вида остановки
    //      restriction_briefing:                   - ограничения по инструктажам
    //          {worker_id}                 - ключ работника
    //                  worker_id:                  - ключ работника
    //                  type_briefings:             - ограничения по видам инструктажей
    //                          {type_briefing_id}          - ключ вида инструктажа
    //                                  type_briefing_id:       -   ключ вида инструктажа
    //                                  restriction:            -   ограничение
    //      restriction_medical:                    - ограничения по медосмотрам
    //          {worker_id}                 - ключ работника
    //                  worker_id:               - ключ работника
    //                  restriction:             - ограничения по медицинским показаниям
    //                  med_report_result_id:    - ключ медицинского заключения
    //      restriction_check_knowledge:            - ограничения по проверке знаний
    //          {worker_id}                 - ключ работника
    //                  worker_id:                  - ключ работника
    //                  type_check_knowledge:             - ограничения по видам проверки знаний
    //                          {type_check_knowledge_id}          - ключ вида проверки знаний
    //                                  type_check_knowledge_id:       -   ключ вида проверки знаний
    //                                  restriction:                    -   ограничение
    //      restriction_esmo:                       - ограничения по ЭСМО
    //          {worker_id}:
    //                  worker_id: -1               - ключ работника
    //                  mo_dopusk_id: -1            - ключ результата
    //                  mo_dopusk_title: ""         - результат
    //                  date_time_start: ""         - дата медосмотра
    //      restriction_check_test_shift:           - ограничения по проверке знаний на предсменном экзаменаторе
    //          {worker_id}:
    //                   worker_id: -1               - ключ работника
    //                   result_title: ""            - текстовое описание результатат тестирования
    //                   count_false: 1              - количество ошибочных ответов
    //                   count_right: 1              - количество правильных ответов
    //                   points: 2                   - оценка накопительная плохих баллов
    //                   date_time_start: ""         - дата и время предсменного тестирования
    // алгоритм:
    //  1. Получаем массив работников по которым ищем ограничения
    //  2. Получаем ограничения по СИЗ                      - у работника есть просроченные СИЗ на заданную дату
    //  3. Получаем ограничения по ПБ                       - список предписаний не в работе (но остановочные)
    //  4. Получение ограничений по инструктажам            - у работников не проведен инструктаж, или инструктаж закончился.
    //  5. Получение ограничений по медицинским осмотрам    - у работника есть ограничение на работу в шахте
    //  6. Получение ограничений по проверке знаний         - у работников не пройдена проверка знаний.
    // Пример: http://127.0.0.1/read-manager-amicum?controller=notification\Notification&method=CheckRestriction&subscribe=&data={"date_time":null,"company_department_id":4029894,"workers":[2016678, 2915737]}
    public static function CheckRestriction($data_post = NULL)
    {
        $log = new LogAmicumFront("CheckRestriction");
        $result = array(
            'restriction_check_test_shift' => (object)array(),
            'restriction_esmo' => (object)array(),
            'restriction_siz' => (object)array(),
            'restriction_pb' => (object)array(),
            'restriction_briefing' => (object)array(),
            'restriction_medical' => (object)array(),
            'restriction_check_knowledge' => (object)array(),
        );

        try {
            $log->addLog("Начало выполнение метода");

            /** Метод начало */
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }

            $log->addLog("Данные успешно переданы");
            $log->addLog("Входной массив данных" . $data_post);
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            if (
                !property_exists($post_dec, 'workers') ||                                                      // массив работников
                !property_exists($post_dec, 'company_department_id') ||                                        // ключ подразделениея
                !property_exists($post_dec, 'date_time'))                                                      // дата на которую надо получить ограничения
            {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $workers = $post_dec->workers;
            $date = $post_dec->date_time;
            $company_department_id = $post_dec->company_department_id;

            $response = DepartmentController::GetAttachDeprtmentByUpper($company_department_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения вложенных департаментов');
            }
            $company_departments = $response['Items'];

            if (!$date) {
                $date = BackendAssistant::GetDateTimeNow();
            }
            $date_start = date('Y-m-d H:i:s', strtotime($date . '-14 hours'));
            $date = date('Y-m-d H:i:s', strtotime($date . '+6 hours'));

//            $log->addData($workers, "Список работников искомых workers", __LINE__);
//            $log->addData($date_start, "Дата от которой получаем данные date_start", __LINE__);
//            $log->addData($date, "Дата до которой получаем данные date", __LINE__);

            // 2. Получаем ограничения по СИЗ -  у работника есть просроченные СИЗ на заданную дату
            //              siz_id:                                 - ключ сиз
            //              siz_title:                              - наименование сиз
            //              date_issue:                             - дата выдачи
            //              date_replacement:                       - дата замены
            //              wear_period:                            - период носки
            //              siz_number:                             - номер СИЗ
            //              worker_id:                              - ключ работника
            $siz_need = (new Query())
                ->select('
                    siz.id as siz_id,
                    siz.title as siz_title,
                    worker_siz.date_issue as date_issue,
                    worker_siz.date_write_off as date_replacement,
                    siz.wear_period as wear_period,
                    siz.id as siz_number,
                    worker_siz.worker_id as worker_id
                ')
                ->from('worker_siz')
                ->innerJoin('siz', 'worker_siz.siz_id=siz.id')
                ->innerJoin('worker', 'worker_siz.worker_id=worker.id')
                ->where(['in', 'worker_siz.worker_id', $workers])
                ->andWhere('status_id!=66')
                ->andWhere('wear_period<36')
                ->andWhere('wear_period!=0')
                ->andWhere(['is not', 'wear_period', null])
                ->andWhere(['<=', 'worker_siz.date_issue', $date])
                ->andWhere(['<=', 'worker_siz.date_write_off', $date])
                ->all();
            foreach ($siz_need as $worker_siz) {
                $siz_need_result[$worker_siz['worker_id']]['worker_id'] = $worker_siz['worker_id'];
                $siz_need_result[$worker_siz['worker_id']]['sizes'][$worker_siz['siz_id']]['siz_id'] = $worker_siz['siz_id'];
                $siz_need_result[$worker_siz['worker_id']]['sizes'][$worker_siz['siz_id']]['siz_title'] = $worker_siz['siz_title'];
                $siz_need_result[$worker_siz['worker_id']]['sizes'][$worker_siz['siz_id']]['date_issue'] = $worker_siz['date_issue'];
                $siz_need_result[$worker_siz['worker_id']]['sizes'][$worker_siz['siz_id']]['date_replacement'] = $worker_siz['date_replacement'];
                $siz_need_result[$worker_siz['worker_id']]['sizes'][$worker_siz['siz_id']]['wear_period'] = $worker_siz['wear_period'];
                $siz_need_result[$worker_siz['worker_id']]['sizes'][$worker_siz['siz_id']]['siz_number'] = $worker_siz['siz_number'];
            }

            if (!isset($siz_need_result)) {
                $result['restriction_siz'] = (object)array();
            } else {
                $result['restriction_siz'] = $siz_need_result;
            }


            //  3. Получаем ограничения по ПБ   -   список предписаний не в работе (но остановочные)
            //  injunction_id:                  - ключ предписания
            //  instruct_id:                    - ключ предписания внутреннего ПАБ
            //  rostex_number:                  - ключ предписания РТН ПАБ
            //  place_title:                    - наименование места где было выдано предписание
            //  place_id:                       - ключ места
            //  status_id:                      - ключ статус предписания
            //  status_title:                   - наименвоание статуса предписания
            //  checking_date_time:             - дата проведения проверки
            //  checking_date_time_format:      - дата проведения проверки форматированная
            //  observation_number:             - под номер предписания
            //  injunction_violations:          - список нарушений
            //          {injunction_violation_id}
            //                  injunction_violation_id     - ключ нарушения
            //                  violation_title             - описание нарушения
            //                  kind_stop_pb_id             - ключ вида остановки
            //                  kind_stop_pb_title          - наименование вида остановки
            $injunctions = (new Query())
                ->select('
                    injunction.id as injunction_id,
                    injunction_violation.id as injunction_violation_id,
                    checking.instruct_id as instruct_id,
                    checking.rostex_number as rostex_number,
                    place.title as place_title,
                    injunction.place_id as place_id,
                    injunction.status_id as status_id,
                    status.title as status_title,
                    injunction.observation_number as observation_number,
                    checking.date_time_start as checking_date_time,
                    violation.title as violation_title,
                    stop_pb.kind_stop_pb_id as kind_stop_pb_id,
                    kind_stop_pb.title as kind_stop_pb_title
                ')
                ->from('injunction')
                ->innerJoin('status', 'injunction.status_id=status.id')
                ->innerJoin('place', 'injunction.place_id=place.id')
                ->innerJoin('injunction_violation', 'injunction_violation.injunction_id=injunction.id')
                ->innerJoin('violation', 'violation.id=injunction_violation.violation_id')
                ->innerJoin('checking', 'injunction.checking_id=checking.id')
                ->innerJoin('stop_pb', 'stop_pb.injunction_violation_id=injunction_violation.id')
                ->innerJoin('kind_stop_pb', 'stop_pb.kind_stop_pb_id=kind_stop_pb.id')
                ->where(['in', 'injunction.company_department_id', $company_departments])
                ->andWhere('injunction.status_id!=59')
                ->andWhere(['injunction.kind_document_id' => [1, 3]])
                ->all();
            foreach ($injunctions as $injunction) {
                $restriction_pb[$injunction['injunction_id']]['injunction_id'] = $injunction['injunction_id'];
                $restriction_pb[$injunction['injunction_id']]['instruct_id'] = $injunction['instruct_id'];
                $restriction_pb[$injunction['injunction_id']]['rostex_number'] = $injunction['rostex_number'];
                $restriction_pb[$injunction['injunction_id']]['place_title'] = $injunction['place_title'];
                $restriction_pb[$injunction['injunction_id']]['place_id'] = $injunction['place_id'];
                $restriction_pb[$injunction['injunction_id']]['status_id'] = $injunction['status_id'];
                $restriction_pb[$injunction['injunction_id']]['status_title'] = $injunction['status_title'];
                $restriction_pb[$injunction['injunction_id']]['checking_date_time'] = $injunction['checking_date_time'];
                $restriction_pb[$injunction['injunction_id']]['checking_date_time_format'] = date("d.m.Y", strtotime($injunction['checking_date_time']));
                $restriction_pb[$injunction['injunction_id']]['observation_number'] = $injunction['observation_number'];
                $restriction_pb[$injunction['injunction_id']]['injunction_violations'][$injunction['injunction_violation_id']]['injunction_violation_id'] = $injunction['injunction_violation_id'];
                $restriction_pb[$injunction['injunction_id']]['injunction_violations'][$injunction['injunction_violation_id']]['violation_title'] = $injunction['violation_title'];
                $restriction_pb[$injunction['injunction_id']]['injunction_violations'][$injunction['injunction_violation_id']]['kind_stop_pb_id'] = $injunction['kind_stop_pb_id'];
                $restriction_pb[$injunction['injunction_id']]['injunction_violations'][$injunction['injunction_violation_id']]['kind_stop_pb_title'] = $injunction['kind_stop_pb_title'];
            }
            if (!isset($restriction_pb)) {
                $result['restriction_pb'] = (object)array();
            } else {
                $result['restriction_pb'] = $restriction_pb;
            }

            //  4. Получение ограничений по инструктажам    - у работников не проведен инструктаж, или инструктаж закончился.
            //  {worker_id}                 - ключ работника
            //          worker_id:                  - ключ работника
            //          type_briefings:             - ограничения по видам инструктажей
            //                  {type_briefing_id}          - ключ вида инструктажа
            //                          type_briefing_id:       -   ключ вида инструктажа
            //                          restriction:            -   ограничение


            // type_briefing.title as type_briefing_title,
            // briefing_reason.title as briefing_reason_title,
            $briefings = (new Query())
                ->select('
                    briefer.worker_id as worker_id,
                    briefer.status_id as briefer_status_id,
                    briefing.type_briefing_id as type_briefing_id,
                    briefing.briefing_reason as briefing_reason_id,
                    briefing.date_time as briefing_date_time,
                    briefer.date_time as briefer_date_time
                ')
                ->from('briefer')
                ->innerJoin('briefing', 'briefing.id=briefer.briefing_id')
//                ->innerJoin('type_briefing', 'type_briefing.id=briefing.type_briefing_id')
//                ->innerJoin('briefing_reason', 'briefing_reason.id=briefing.briefing_reason')
                ->where(['in', 'briefer.worker_id', $workers])
                ->andWhere
                ([
                    'or',
                    ['<=', 'briefing.date_time', $date],//today
                    ['is', 'briefing.date_time', NULL]
                ])
                ->orderBy(['briefing.date_time' => SORT_ASC])
                ->all();

            foreach ($briefings as $briefing) {
                $briefing_workers[$briefing['worker_id']]['worker_id'] = $briefing['worker_id'];
                $briefing_workers[$briefing['worker_id']]['type_briefing'][$briefing['type_briefing_id']]['briefing_date_time'] = $briefing['briefing_date_time'];
//                $briefing_workers[$briefing['worker_id']]['type_briefing'][$briefing['type_briefing_id']]['briefing_date_time_format'] = date("d.m.Y", strtotime($briefing['briefing_date_time']));
                $briefing_workers[$briefing['worker_id']]['type_briefing'][$briefing['type_briefing_id']]['type_briefing_id'] = $briefing['type_briefing_id'];
//                $briefing_workers[$briefing['worker_id']]['type_briefing'][$briefing['type_briefing_id']]['type_briefing_title'] = $briefing['type_briefing_title'];
                $briefing_workers[$briefing['worker_id']]['type_briefing'][$briefing['type_briefing_id']]['briefer_status_id'] = $briefing['briefer_status_id'];
                $briefing_workers[$briefing['worker_id']]['type_briefing'][$briefing['type_briefing_id']]['briefing_reason_id'] = $briefing['briefing_reason_id'];
//                $briefing_workers[$briefing['worker_id']]['type_briefing'][$briefing['type_briefing_id']]['briefing_reason_title'] = $briefing['briefing_reason_title'];
                $briefing_workers[$briefing['worker_id']]['type_briefing'][$briefing['type_briefing_id']]['briefer_date_time'] = $briefing['briefer_date_time'];
//                $briefing_workers[$briefing['worker_id']]['type_briefing'][$briefing['type_briefing_id']]['briefer_date_time_format'] = date("d.m.Y", strtotime($briefing['briefer_date_time']));
            }


            // 1	Первичный по ОТ и ПБ
            // 2	Повторный по ОТ и ПБ
            // 3	Внеплановый по ОТ и ПБ
            // 4	Целевой по ОТ и ПБ
            // 5	Противопожарный
            // 6	Вводный
            foreach ($workers as $worker) {
                if (!isset($briefing_workers[$worker]['type_briefing'][TypeBriefingEnumController::FIRST])) {
                    $restriction_briefing[$worker]['worker_id'] = $worker;
                    $restriction_briefing[$worker]['type_briefings'][TypeBriefingEnumController::FIRST]['restriction'] = "Отсутствует первичный инструктаж по ОТ и ПБ";
                    $restriction_briefing[$worker]['type_briefings'][TypeBriefingEnumController::FIRST]['type_briefing_id'] = TypeBriefingEnumController::FIRST;
                } else {
                    if (
                        (
                            strtotime($briefing_workers[$worker]['type_briefing'][TypeBriefingEnumController::FIRST]['briefer_date_time'] . '+90 days') < strtotime($date) and !isset($briefing_workers[$worker]['type_briefing'][TypeBriefingEnumController::REPEAT])
                        ) or
                        (
                            isset($briefing_workers[$worker]['type_briefing'][TypeBriefingEnumController::REPEAT]) and strtotime($briefing_workers[$worker]['type_briefing'][TypeBriefingEnumController::REPEAT]['briefer_date_time'] . '+90  days') < strtotime($date)
                        )
                    ) {
                        $restriction_briefing[$worker]['worker_id'] = $worker;
                        $restriction_briefing[$worker]['type_briefings'][TypeBriefingEnumController::REPEAT]['restriction'] = "Отсутствует повторный инструктаж по ОТ и ПБ";
                        $restriction_briefing[$worker]['type_briefings'][TypeBriefingEnumController::REPEAT]['type_briefing_id'] = TypeBriefingEnumController::REPEAT;
                    }
                }

                if (isset($briefing_workers[$worker]['type_briefing'][TypeBriefingEnumController::UNPLANNED]) and $briefing_workers[$worker]['type_briefing'][TypeBriefingEnumController::UNPLANNED]['briefer_status_id'] != StatusEnumController::BRIEFING_FAMILIAR) { // 69 - ознакомлен с инструктажем
                    $restriction_briefing[$worker]['worker_id'] = $worker;
                    $restriction_briefing[$worker]['type_briefings'][TypeBriefingEnumController::UNPLANNED]['restriction'] = "Отсутствует отметка о проведении внепланового инструктажа по ОТ и ПБ";
                    $restriction_briefing[$worker]['type_briefings'][TypeBriefingEnumController::UNPLANNED]['type_briefing_id'] = TypeBriefingEnumController::UNPLANNED;
                }

                if (isset($briefing_workers[$worker]['type_briefing'][TypeBriefingEnumController::TARGET]) and $briefing_workers[$worker]['type_briefing'][TypeBriefingEnumController::TARGET]['briefer_status_id'] != StatusEnumController::BRIEFING_FAMILIAR) { // 69 - ознакомлен с инструктажем
                    $restriction_briefing[$worker]['worker_id'] = $worker;
                    $restriction_briefing[$worker]['type_briefings'][TypeBriefingEnumController::TARGET]['restriction'] = "Отсутствует отметка о проведении целевого инструктажа по ОТ и ПБ";
                    $restriction_briefing[$worker]['type_briefings'][TypeBriefingEnumController::TARGET]['type_briefing_id'] = TypeBriefingEnumController::TARGET;
                }

                if (!isset($briefing_workers[$worker]['type_briefing'][TypeBriefingEnumController::FIRE_FIGHTING])) {
                    $restriction_briefing[$worker]['worker_id'] = $worker;
                    $restriction_briefing[$worker]['type_briefings'][TypeBriefingEnumController::FIRE_FIGHTING]['restriction'] = "Отсутствует противопожарный инструктаж";
                    $restriction_briefing[$worker]['type_briefings'][TypeBriefingEnumController::FIRE_FIGHTING]['type_briefing_id'] = TypeBriefingEnumController::FIRE_FIGHTING;
                }

                if (!isset($briefing_workers[$worker]['type_briefing'][TypeBriefingEnumController::PREFATORY])) {
                    $restriction_briefing[$worker]['worker_id'] = $worker;
                    $restriction_briefing[$worker]['type_briefings'][TypeBriefingEnumController::PREFATORY]['restriction'] = "Отсутствует вводный инструктаж";
                    $restriction_briefing[$worker]['type_briefings'][TypeBriefingEnumController::PREFATORY]['type_briefing_id'] = TypeBriefingEnumController::PREFATORY;
                }

            }

            if (!isset($restriction_briefing)) {
                $result['restriction_briefing'] = (object)array();
            } else {
                $result['restriction_briefing'] = $restriction_briefing;
            }

            //  5. Получение ограничений по медицинским осмотрам - у работника есть ограничение на работу в шахте
            //  {worker_id}                 - ключ работника
            //          worker_id:               - ключ работника
            //          restriction:             - ограничения по медицинским показаниям
            //          med_report_result_id:    - ключ медицинского заключения
            $medical = (new Query())
                ->select('
                    med_report.worker_id as worker_id,
                    med_report.med_report_result_id as med_report_result_id,
                    med_report_result.title as med_report_result_title
                ')
                ->from('med_report')
                ->innerJoin('med_report_result', 'med_report_result.id=med_report.med_report_result_id')
                ->where(['in', 'med_report.worker_id', $workers])
                ->andWhere(['<=', 'med_report.med_report_date', $date])
                ->andWhere([
                    'or',
                    ['>=', 'med_report.date_next', $date],
                    ['is', 'med_report.date_next', NULL]
                ])
                ->andWhere(['or', 'med_report.med_report_result_id != 1', 'med_report.med_report_result_id != 9'])
                ->indexBy('worker_id')
                ->all();

            foreach ($workers as $worker) {
                $restriction_medical[$worker]['worker_id'] = $worker;
                if (!isset($medical[$worker])) {
                    $restriction_medical[$worker]['restriction'] = "Отсутствует медосмотр";
                    $restriction_medical[$worker]['med_report_result_id'] = -1;
                } else {
                    $restriction_medical[$worker]['restriction'] = $medical[$worker]['med_report_result_title'];
                    $restriction_medical[$worker]['med_report_result_id'] = $medical[$worker]['med_report_result_id'];
                }
            }

            if (!isset($restriction_medical)) {
                $result['restriction_medical'] = (object)array();
            } else {
                $result['restriction_medical'] = $restriction_medical;
            }


            $response = self::GetCheckKnowledgeByWorker($workers, $date);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения проверок знаний по работникам');
            }
            $result['restriction_check_knowledge'] = $response['Items'];

            // АСМО:
            // {worker_id}:
            //      worker_id: -1               - ключ работника
            //      mo_dopusk_id: -1            - ключ результата
            //      mo_dopusk_title: ""         - результат
            //      date_time_start: ""         - дата медосмотра
            $esmos_worker = (new Query())
                ->select('
                    worker.id as worker_id,
                    physical_esmo_final.mo_dopusk_id as mo_dopusk_id,
                    physical_esmo_final.date_time_start as date_time_start,
                    mo_dopusk.title as mo_dopusk_title
                   ')
                ->from('worker')
                ->leftJoin("(select physical_esmo.worker_id,
                                        physical_esmo.mo_dopusk_id,
                                        physical_esmo.date_time_start
                                    from physical_esmo
                                    inner Join 
                                        (select physical_esmo_filter.worker_id, max(physical_esmo_filter.date_time_start) as max_date_time_start
                                                from
                                                    (SELECT physical_esmo.worker_id, physical_esmo.date_time_start FROM physical_esmo where date_time_start>='" . $date_start . "' and date_time_start<='" . $date . "') physical_esmo_filter
                                        group by physical_esmo_filter.worker_id) physical_esmo_max on physical_esmo_max.worker_id = physical_esmo.worker_id and physical_esmo_max.max_date_time_start = physical_esmo.date_time_start) physical_esmo_final", 'worker.id=physical_esmo_final.worker_id')
                ->leftJoin('mo_dopusk', 'mo_dopusk.id=physical_esmo_final.mo_dopusk_id')
                ->where(['in', 'worker.id', $workers])
//                ->andWhere(['or',
//                    'physical_esmo_final.physical_esmo_final.mo_dopusk_id!=1',
//                    'physical_esmo_final.physical_esmo_final.mo_dopusk_id is null']
//                )
                ->all();
            foreach ($esmos_worker as $worker_esmo) {
                if (!$worker_esmo['mo_dopusk_id'] or $worker_esmo['mo_dopusk_id'] != 1) {
                    $restriction_esmo[$worker_esmo['worker_id']]['worker_id'] = $worker_esmo['worker_id'];
                    if ($worker_esmo['mo_dopusk_id']) {
                        $restriction_esmo[$worker_esmo['worker_id']]['mo_dopusk_id'] = $worker_esmo['mo_dopusk_id'];
                        $restriction_esmo[$worker_esmo['worker_id']]['mo_dopusk_title'] = $worker_esmo['mo_dopusk_title'];
                        $restriction_esmo[$worker_esmo['worker_id']]['date_time_start'] = $worker_esmo['date_time_start'];
                    } else {
                        $restriction_esmo[$worker_esmo['worker_id']]['mo_dopusk_id'] = null;
                        $restriction_esmo[$worker_esmo['worker_id']]['mo_dopusk_title'] = "Предсменный медицинский осмотр не проходил";
                        $restriction_esmo[$worker_esmo['worker_id']]['date_time_start'] = "";
                    }
                }
            }
            if (!isset($restriction_esmo)) {
                $result['restriction_esmo'] = (object)array();
            } else {
                $result['restriction_esmo'] = $restriction_esmo;
            }

            /** ОТКЛЮЧИЛ ПРЕДЫДУЩИЕ ОГРАНИЧЕНИЯ */
            $result = array(
                'restriction_check_test_shift' => (object)array(),
                'restriction_esmo' => (object)array(),
                'restriction_siz' => (object)array(),
                'restriction_pb' => (object)array(),
                'restriction_briefing' => (object)array(),
                'restriction_medical' => (object)array(),
                'restriction_check_knowledge' => (object)array(),
            );

            /** ОГРАНИЧЕНИЯ ВКЛЮЧЕНЫ ТОЛЬКО ДЛЯ УЧАСТКОВ ТРАНСПОРТА */
            $department_type = CompanyDepartment::findOne(['id' => $company_department_id]);
            if ($department_type and $department_type['department_type_id'] == DepartmentTypeEnum::TRANSPORT) {

                // Предсменный экзаменатор
                // {worker_id}:
                //      worker_id: -1               - ключ работника
                //      result_title: ""            - текстовое описание результата тестирования
                //      count_false: 1              - количество ошибочных ответов
                //      count_right: 1              - количество правильных ответов
                //      points: 2                   - оценка накопительная плохих баллов
                //      date_time_start: ""         - дата и время предсменного тестирования
                $pred_exam_workers = (new Query())
                    ->select('
                    worker.id as worker_id,
                    pred_exam_final.start_test_time as start_test_time,
                    pred_exam_final.count_false as count_false,
                    pred_exam_final.count_right as count_right,
                    pred_exam_final.points as points,
    
                   ')
                    ->from('worker')
                    ->innerJoin('employee', 'employee.id=worker.employee_id')
                    ->leftJoin("(SELECT pred_exam_history.employee_id,
                                        pred_exam_history.points,
                                        pred_exam_history.count_right,
                                        pred_exam_history.count_false,
                                        pred_exam_history.start_test_time
                                    FROM pred_exam_history
                                    INNER JOIN 
                                        (SELECT pred_exam_filter.employee_id, max(pred_exam_filter.start_test_time) as max_start_test_time
                                                FROM
                                                    (SELECT pred_exam_history.employee_id, pred_exam_history.start_test_time FROM pred_exam_history where start_test_time>='" . $date_start . "' and start_test_time<='" . $date . "' AND status_id=" . StatusEnumController::EXAM_END . ") pred_exam_filter 
                                                GROUP BY pred_exam_filter.employee_id) pred_exam_max on pred_exam_max.employee_id = pred_exam_history.employee_id and pred_exam_max.max_start_test_time = pred_exam_history.start_test_time) pred_exam_final", 'employee.id=pred_exam_final.employee_id')
                    ->where(['in', 'worker.id', $workers])
                    ->all();

//                $log->addData($pred_exam_workers, '$pred_exam_workers', __LINE__);
                foreach ($pred_exam_workers as $pred_exam_worker) {
                    if ($pred_exam_worker['start_test_time']) {
                        if ($pred_exam_worker['points'] < 2) {
                            $restriction_pred_exam[$pred_exam_worker['worker_id']]['worker_id'] = $pred_exam_worker['worker_id'];
                            $restriction_pred_exam[$pred_exam_worker['worker_id']]['count_false'] = $pred_exam_worker['count_false'];
                            $restriction_pred_exam[$pred_exam_worker['worker_id']]['count_right'] = $pred_exam_worker['count_right'];
                            $restriction_pred_exam[$pred_exam_worker['worker_id']]['points'] = $pred_exam_worker['points'];
                            $restriction_pred_exam[$pred_exam_worker['worker_id']]['date_time_start'] = date("d.m.Y H:i:s", strtotime($pred_exam_worker['start_test_time']));
                            $restriction_pred_exam[$pred_exam_worker['worker_id']]['result_title'] = "Тест провален. Предсменный экзаменатор окончен отрицательно";
                        } else {
//                            $restriction_pred_exam[$pred_exam_worker['worker_id']]['worker_id'] = $pred_exam_worker['worker_id'];
//                            $restriction_pred_exam[$pred_exam_worker['worker_id']]['count_false'] = $pred_exam_worker['count_false'];
//                            $restriction_pred_exam[$pred_exam_worker['worker_id']]['count_right'] = $pred_exam_worker['count_right'];
//                            $restriction_pred_exam[$pred_exam_worker['worker_id']]['points'] = $pred_exam_worker['points'];
//                            $restriction_pred_exam[$pred_exam_worker['worker_id']]['date_time_start'] = $pred_exam_worker['start_test_time'];
//                            $restriction_pred_exam[$pred_exam_worker['worker_id']]['result_title'] = "Тест пройден. Предсменный экзаменатор окончен положительно";
                        }
                    } else {
                        $restriction_pred_exam[$pred_exam_worker['worker_id']]['worker_id'] = $pred_exam_worker['worker_id'];
                        $restriction_pred_exam[$pred_exam_worker['worker_id']]['count_false'] = "-";
                        $restriction_pred_exam[$pred_exam_worker['worker_id']]['count_right'] = "-";
                        $restriction_pred_exam[$pred_exam_worker['worker_id']]['points'] = "-";
                        $restriction_pred_exam[$pred_exam_worker['worker_id']]['result_title'] = "Предсменный экзаменатор не проходил";
                        $restriction_pred_exam[$pred_exam_worker['worker_id']]['date_time_start'] = "-";
                    }

                }
                if (!isset($restriction_pred_exam)) {
                    $result['restriction_check_test_shift'] = (object)array();
                } else {
                    $result['restriction_check_test_shift'] = $restriction_pred_exam;
                }
            }


            /** Метод окончание */


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetCheckKnowledgeByWorker - Метод получения проверок знаний/аттестаций
     * @param $workers - массив работников, по которым делаем проверку
     * @param $date - дата, на которую делаем проверку
     * @return array
     */
    public static function GetCheckKnowledgeByWorker($workers, $date, $type_check_knowledge_id = 1): array
    {
        $result = null;
        $check_knowledge_workers = null;
        $restriction_check_knowledge = null;
        $count_record = 0;

        $log = new LogAmicumFront("GetCheckKnowledgeByWorker");

        try {
            $log->addLog("Начало выполнения метода");

            //  6. Получение ограничений по проверке знаний    - у работников не пройдена проверка знаний.
            //  {worker_id}                 - ключ работника
            //          worker_id:                  - ключ работника
            //          type_check_knowledge:             - ограничения по видам проверки знаний
            //                  {type_check_knowledge_id}          - ключ вида проверки знаний
            //                          type_check_knowledge_id:       -   ключ вида проверки знаний
            //                          restriction:                    -   ограничение

            $check_knowledges = (new Query())
                ->select('
                    check_knowledge_worker.worker_id as worker_id,
                    check_knowledge_worker.status_id as check_knowledge_worker_status_id,
                    check_knowledge.type_check_knowledge_id as type_check_knowledge_id,
                    check_knowledge.reason_check_knowledge_id as reason_check_knowledge_id,
                    reason_check_knowledge.title as reason_check_knowledge_title,
                    check_knowledge.date as check_knowledge_date
                ')
                ->from('check_knowledge_worker')
                ->innerJoin('check_knowledge', 'check_knowledge.id=check_knowledge_worker.check_knowledge_id')
                ->innerJoin('reason_check_knowledge', 'check_knowledge.reason_check_knowledge_id=reason_check_knowledge.id')
                ->where(['in', 'check_knowledge_worker.worker_id', $workers])
//                ->andWhere([
//                    'or',
//                    ['>=', 'check_knowledge.date', $date],
//                    ['is', 'check_knowledge.date', NULL]
//                ])
                ->andWhere(['check_knowledge.type_check_knowledge_id' => $type_check_knowledge_id])
//                ->andWhere(['check_knowledge_worker.status_id' => 79])
                ->orderBy(['check_knowledge.date' => SORT_ASC])
                ->all();

            foreach ($check_knowledges as $check_knowledge) {
                $check_knowledge_workers[$check_knowledge['worker_id']]['worker_id'] = $check_knowledge['worker_id'];
                $check_knowledge_workers[$check_knowledge['worker_id']]['type_check_knowledge'][$check_knowledge['type_check_knowledge_id']]['check_knowledge_date'] = $check_knowledge['check_knowledge_date'];
                $check_knowledge_workers[$check_knowledge['worker_id']]['type_check_knowledge'][$check_knowledge['type_check_knowledge_id']]['type_check_knowledge_id'] = $check_knowledge['type_check_knowledge_id'];
                $check_knowledge_workers[$check_knowledge['worker_id']]['type_check_knowledge'][$check_knowledge['type_check_knowledge_id']]['check_knowledge_worker_status_id'] = $check_knowledge['check_knowledge_worker_status_id'];
                $check_knowledge_workers[$check_knowledge['worker_id']]['type_check_knowledge'][$check_knowledge['type_check_knowledge_id']]['reason_check_knowledge_id'] = $check_knowledge['reason_check_knowledge_id'];
                $check_knowledge_workers[$check_knowledge['worker_id']]['type_check_knowledge'][$check_knowledge['type_check_knowledge_id']]['reason_check_knowledge_title'] = $check_knowledge['reason_check_knowledge_title'];
                $check_knowledge_workers[$check_knowledge['worker_id']]['type_check_knowledge'][$check_knowledge['type_check_knowledge_id']]['check_knowledge_worker_date'] = $check_knowledge['check_knowledge_date'];
            }


            foreach ($workers as $worker) {
                if (!isset($check_knowledge_workers[$worker]['type_check_knowledge'][1])) {
                    $restriction_check_knowledge[$worker]['worker_id'] = $worker;
                    $restriction_check_knowledge[$worker]['type_check_knowledges'][1]['restriction'] = "У работника отсутствует действующая проверка знаний";
                    $restriction_check_knowledge[$worker]['type_check_knowledges'][1]['type_check_knowledge_id'] = 1;
                } else {
                    if (strtotime($check_knowledge_workers[$worker]['type_check_knowledge'][1]['check_knowledge_worker_date'] . '+1825 days') < strtotime($date)) {
                        $restriction_check_knowledge[$worker]['worker_id'] = $worker;
                        $restriction_check_knowledge[$worker]['type_check_knowledges'][2]['restriction'] = "Отсутствует повторный инструктаж по ОТ и ПБ";
                        $restriction_check_knowledge[$worker]['type_check_knowledges'][2]['type_check_knowledge_id'] = 2;
                    }
                }

            }

            $result = $restriction_check_knowledge;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (!$result) {
            $result = (object)array();
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result, 'check_knowledge_workers' => $check_knowledge_workers], $log->getLogAll());
    }

    /**
     * Метод GetNotificationAll() - Возвращает все уведомления для блоку уведомлений (используется для мобилки) в части:
     *      - "Запланирован медосмотр"
     * @param null $data_post - JSON с идентификатором участка
     * @return array - список объектов со следующей структурой:
     * {
     *      "id": "siz",
     *      "title": "Необходима замена СИЗ",
     *      "notifications": []
     * },
     * {
     *      "id": "medicalExam",
     *      "title": "Запланированный медицинский осмотр",
     *      "notifications": []
     * },
     * {
     *      "id": "ppkPab",
     *      "title": "Выдан ПАБ",
     *      "notifications": []
     * },
     * {
     *      "id": "audit",
     *      "title": "Запланирован аудит",
     *      "notifications": []
     * },
     * {
     *      "id": "check_knowledge",
     *      "title": "Запланирована проверка знаний",
     *      "notifications": []
     * },
     * {
     *      "id": "briefing",
     *      "title": "Необходимо пройти инструктаж",
     *      "notifications": []
     * },
     * {
     *      "id": "ppkInjunction",
     *      "title": "Выдано предписание",
     *      "notifications": []
     * }
     *      notification         - уведомление
     *              restriction_id           - уникальный ключ для type_restriction в таблице notification_status
     *              type_restriction         - notification_status.type_restriction (siz,medicalExam,ppkPab,audit,check_knowledge,briefing,ppkInjunction)
     *              status_id                - статус уведомления (прочитан-19 или нет-1)
     *
     *              worker_id                - ключ работника
     *              worker_full_name         - ФИО
     *              worker_staff_number      - табельный номер работника
     *              worker_position_title    - должность
     *
     *              siz_id                   - ключ СИЗ
     *              siz_title                - название СИЗ
     *
     *              checkup_date_start       - дата начала медосмотра
     *              checkup_date_end         - дата окончания медосмотра
     *
     *              flag                     - true  - если до окончания срока медосмотра осталось 2 недели или менее, то возвращается оранжевый цвет
     *                                       | false - иначе срок замены просрочен, то возвращается красный цвет
     *                                       | null  - во всех остальных случаях
     *
     *              checking_id              - ключ проверки
     *
     *              ppk_id                   - ключ ППК
     *              ppk_date_time            - дата ППК
     *              ppk_status_id            - ключ статуса ППК Выдано, просрочено, выполнено и т.д.
     *
     *              injunction_id            - ключ нарушения
     *
     *              audit_id                 - ключ запланированной проверки
     *              audit_place_id           - ключ места
     *              audit_place_title        - название места
     *              audit_date_time          - дата запланированного аудита
     *
     *              check_knowledge_date_time- дата проверки знаний
     *
     *              briefing_date_time       - дата запланированного инструктажа
     *
     * @package frontend\controllers\notification
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=notification\Notification&method=GetNotificationAll&subscribe=&data={%22company_id%22:4029938}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 11.09.2019 11:56
     */
    public static function GetNotificationAll($data_post = NULL)
    {
        $log = new LogAmicumFront("GetNotificationAll");
        $result = array(
            "siz" => null,
            "medicalExam" => null,
            "ppkPab" => null,
            "audit" => null,
            "check_knowledge" => null,
            "briefing" => null,
            "ppkInjunction" => null,
        );
        $restriction = "";
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $log->addLog("Данные успешно переданы");

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            if (
                !property_exists($post_dec, 'company_id')
            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }
            $company_id = $post_dec->company_id;

            $response = DepartmentController::FindDepartment($company_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения вложенных департаментов');
            }
            $company_departments = $response['Items'];

            /**
             * Получение текущих: месяца, дня, года
             */
            $date_now = date('Y-m-d', strtotime(BackendAssistant::GetDateNow()));

            /**
             * Отнимаем от текущей даты 14 дней для показа уведомлений
             */
            $mk_date_plan = date('Y-m-d', strtotime($date_now . "- 14 days"));

            // 1. Медицинский осмотр групповой
            $physical_schedules = PhysicalSchedule::find()
                ->select([
                    'physical_worker.worker_id as worker_id',
                    'position.title as position_title',
                    'physical_schedule.company_department_id as company_id',
                    'physical_schedule.date_start as date_start',
                    'physical_schedule.date_end as date_end',
                    'worker.tabel_number as worker_tabel_number',
                    'employee.first_name as first_name',
                    'employee.last_name as last_name',
                    'employee.patronymic as patronymic',
                    'physical_worker_date.id as physical_worker_date_id',
                    'notification_status.status_id as status_id'
                ])
                ->innerJoin('physical_worker', 'physical_worker.physical_schedule_id = physical_schedule.id')
                ->leftJoin('physical_worker_date', 'physical_worker.id = physical_worker_date.physical_worker_id')
                ->innerJoin('worker', 'physical_worker.worker_id = worker.id')
                ->innerJoin('position', 'worker.position_id = position.id')
                ->innerJoin('employee', 'employee.id = worker.employee_id')
                ->leftJoin('notification_status', "notification_status.restriction_id = worker.id AND notification_status.type_restriction = 'medicalExam'")
//                ->where([
//                    'physical_schedule.status_id' => self::STATUS_ACTIVE])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date_now],
                    ['is', 'worker.date_end', null]
                ])
                ->andWhere(['physical_schedule.company_department_id' => $company_id])
                ->asArray()
                ->all();

            $log->addLog("Выгрузили из графика медосмотров");

            $med_report = MedReport::find()
                ->innerJoin('physical_worker_date', 'physical_worker_date.id = med_report.physical_worker_date')
                ->indexBy(
                    function ($report) {
                        return $report['worker_id'] . '_' . $report['physical_worker_date'];
                    }
                )
                ->all();

            $log->addLog("Выгрузили все результаты медосмотров");

            if ($physical_schedules) {
                foreach ($physical_schedules as $physical_schedule) {

                    /**
                     * Начинаем показывать уведомление за 2 недели до начала медосмотра у человека
                     * И показываем до тех пор пока у этого человека не появиться заключение медосмотра
                     */

                    // true - если до окончания срока медосмотра осталось 2 недели или менее, то возвращается оранжевый цвет
                    // false - иначе срок замены просрочен, то возвращается красный цвет
                    // null - во всех остальных случаях
                    $flag = null;
                    if ($physical_schedule['date_start'] >= $mk_date_plan) {
                        $flag = true;
                    } elseif (!isset($med_report[$physical_schedule['worker_id'] . '_' . $physical_schedule['physical_worker_date_id']])) {
                        $flag = false;
                    }

                    if (!$physical_schedule['status_id']) {
                        $physical_schedule['status_id'] = 1;
                    }

                    $checkup[] = array(
                        'flag' => $flag,
                        'worker_id' => $physical_schedule['worker_id'],
                        'worker_full_name' => Assistant::GetShortFullName($physical_schedule['first_name'], $physical_schedule['patronymic'], $physical_schedule['last_name']),
                        'worker_date_end' => $physical_schedule['date_end'],
                        'worker_staff_number' => $physical_schedule['worker_tabel_number'],
                        'worker_position_title' => $physical_schedule['position_title'],
                        'checkup_date_start' => date('d.m.Y', strtotime($physical_schedule['date_start'])),
                        'checkup_date_end' => date('d.m.Y', strtotime($physical_schedule['date_end'])),
                        'status_id' => $physical_schedule['status_id'],
                        'restriction_id' => $physical_schedule['worker_id'],
                        'type_restriction' => 'medicalExam'
                    );

                }

                $result['medicalExam'] = array(
                    "id" => 'medicalExam',
                    "title" => "Запланированный медицинский осмотр",
                    "notifications" => $checkup
                );

            }

            // 2. Необходима замена СИЗ
            $siz_need = (new Query())
                ->select('
                    siz.id as siz_id,
                    siz.title as siz_title,
                    worker_siz.date_issue as date_issue,
                    worker_siz.date_write_off as date_write_off,
                    siz.wear_period as wear_period,
                    worker_siz.worker_id as worker_id,
                    worker.tabel_number as worker_tabel_number,
                    position.title as position_title,
                    employee.first_name as first_name,
                    employee.last_name as last_name,
                    employee.patronymic as patronymic,
                    notification_status.status_id as status_id
                ')
                ->from('worker_siz')
                ->innerJoin('siz', 'worker_siz.siz_id=siz.id')
                ->innerJoin('worker', 'worker_siz.worker_id=worker.id')
                ->innerJoin('position', 'worker.position_id = position.id')
                ->innerJoin('employee', 'employee.id = worker.employee_id')
                ->leftJoin('notification_status', "notification_status.restriction_id = siz.id AND notification_status.type_restriction = 'siz'")
                ->where(['worker.company_department_id' => $company_departments])
                ->andWhere('worker_siz.status_id!=66')
                ->andWhere('wear_period<36')
                ->andWhere('wear_period!=0')
                ->andWhere(['is not', 'wear_period', null])
                ->andWhere(['<=', 'worker_siz.date_issue', $date_now])
                ->andWhere(['<=', 'worker_siz.date_write_off', $date_now])
                ->all();
            if ($siz_need) {
                foreach ($siz_need as $worker_siz) {

                    $flag = null;
                    $restriction = "";


                    if (strtotime($worker_siz['date_write_off'] . '+14 days') > strtotime($date_now)) {
                        $restriction = "Срок эксплуатации СИЗ подходит к концу";
                        $flag = false;
                    } else if ($worker_siz['date_write_off'] > strtotime($date_now)) {
                        $restriction = "СИЗ просрочен";
                        $flag = true;
                    }

                    if (!$worker_siz['status_id']) {
                        $worker_siz['status_id'] = 1;
                    }

                    $sizs[] = array(
                        'worker_id' => $worker_siz['worker_id'],
                        'worker_full_name' => Assistant::GetShortFullName($worker_siz['first_name'], $worker_siz['patronymic'], $worker_siz['last_name']),
                        'worker_staff_number' => $worker_siz['worker_tabel_number'],
                        'worker_position_title' => $worker_siz['position_title'],

                        'siz_id' => $worker_siz['siz_id'],
                        'siz_title' => $worker_siz['siz_title'],

                        'checkup_date_start' => $worker_siz['date_issue'],
                        'checkup_date_end' => $worker_siz['date_write_off'],
                        'restriction' => $restriction,

                        'flag' => $flag,
                        'status_id' => $worker_siz['status_id'],
                        'restriction_id' => $worker_siz['siz_id'],
                        'type_restriction' => 'siz',
                    );
                }

                $result['siz'] = array(
                    "id" => 'siz',
                    "title" => "Необходима замена СИЗ",
                    "notifications" => $sizs
                );
            }

            // 3. Выдан ПАБ
            $found_data_pab = Checking::find()
                ->joinWith('injunctions.injunctionViolations.violators.worker.employee')
                ->joinWith('injunctions.injunctionViolations.violators.worker.position')
                ->where(['worker.company_department_id' => $company_departments])
                ->andWhere(['injunction.kind_document_id' => CheckingController::KIND_PAB])
                ->andWhere(['injunction.status_id' => [CheckingController::STATUS_NEW, CheckingController::STATUS_IN_JOB]])
                ->limit(50000)
                ->all();

            if ($found_data_pab) {
                foreach ($found_data_pab as $checking_item) {
                    foreach ($checking_item['injunctions'] as $injunction) {
                        foreach ($injunction->injunctionViolations as $injunctionViolation) {
                            foreach ($injunctionViolation->violators as $violator) {
                                $violators_id = $violator['id'];
                            }
                        }
                    }
                }

                $ppkPab_status_id = (new Query())
                    ->select('
                        status_id,
                        restriction_id
                    ')
                    ->from('notification_status')
                    ->where(['type_restriction' => 'ppkPab'])
                    ->andWhere(['restriction_id' => $violators_id])
                    ->indexBy('restriction_id')
                    ->all();

                foreach ($found_data_pab as $checking_item) {
                    foreach ($checking_item->injunctions as $injunction) {
                        foreach ($injunction->injunctionViolations as $injunctionViolation) {
                            foreach ($injunctionViolation->violators as $violator) {

                                if (isset($ppkPab_status_id[$violator['id']])) {
                                    $status_id = $ppkPab_status_id[$violator['id']]['status_id'];
                                } else {
                                    $status_id = 1;
                                }

                                $notifiction_pab[] = array(
                                    'worker_id' => $violator['worker_id'],
                                    'worker_full_name' => Assistant::GetShortFullName($violator['worker']['employee']['first_name'], $violator['worker']['employee']['patronymic'], $violator['worker']['employee']['last_name']),
                                    'worker_staff_number' => $violator['worker']['tabel_number'],
                                    'worker_position_title' => $violator['worker']['position']['title'],

                                    'checking_id' => $checking_item['id'],

                                    'ppk_id' => $injunction['id'],
                                    'ppk_date_time' => $checking_item['date_time_start'],

                                    'ppk_status_id' => $injunction['status_id'],

                                    'status_id' => $status_id,
                                    'restriction_id' => $violator['id'],
                                    'type_restriction' => 'ppkPab'
                                );
                            }
                        }
                    }
                }

                $result['ppkPab'] = array(
                    "id" => 'ppkPab',
                    "title" => "Выдан ПАБ",
                    "notifications" => $notifiction_pab
                );

            }

            // 4. Запланирован Аудит
            $found_audits = Audit::find()
                ->select([
                    'audit.id as audit_id',
                    'place.title as place_title',
                    'audit_place.id as audit_place_id',
                    'audit.date_time as audit_date_time',
                    'notification_status.status_id as status_id'
                ])
                ->innerJoin('audit_place', 'audit_place.audit_id = audit.id')
                ->innerJoin('place', 'audit_place.place_id = place.id')
                ->leftJoin('notification_status', "notification_status.restriction_id = audit.id AND notification_status.type_restriction = 'audit'")
                ->where(['audit.company_department_id' => $company_departments])
                ->andWhere(['>=', 'audit.date_time', $date_now])
                ->orderBy('audit.date_time ASC')
                ->limit(5)
                ->asArray()
                ->all();

            if ($found_audits) {
                /******************** Перебор плановых аудитов ********************/
                foreach ($found_audits as $audit) {
                    if (!$audit['status_id']) {
                        $audit['status_id'] = 1;
                    }
                    $checking_plan[] = array(
                        'audit_id' => $audit['audit_id'],
                        'audit_place_id' => $audit['audit_place_id'],
                        'audit_place_title' => $audit['place_title'],
                        'audit_date_time' => $audit['audit_date_time'],
                        'status_id' => $audit['status_id'],
                        'restriction_id' => $audit['audit_id'],
                        'type_restriction' => 'audit',
                    );
                }

                $result['audit'] = array(
                    "id" => 'audit',
                    "title" => "Запланирован аудит",
                    "notifications" => $checking_plan
                );
            }

            // Список людей в подразделении
            $hand_workers = [];
            $workers = (new Query())
                ->select('
                    worker.id as worker_id,
                    worker.tabel_number as worker_tabel_number,
                    position.title as position_title,
                    employee.first_name as first_name,
                    employee.last_name as last_name,
                    employee.patronymic as patronymic,
                    check_k_n_status.status_id as check_knowledge_status_id,
                    briefing_n_status.status_id as briefing_status_id
                ')
                ->from('worker')
                ->innerJoin('position', 'worker.position_id = position.id')
                ->innerJoin('employee', 'employee.id = worker.employee_id')
                ->leftJoin('notification_status check_k_n_status', "check_k_n_status.restriction_id = worker.id AND check_k_n_status.type_restriction = 'check_knowledge'")
                ->leftJoin('notification_status briefing_n_status', "briefing_n_status.restriction_id = worker.id AND briefing_n_status.type_restriction = 'briefing'")
                ->where(["company_department_id" => $company_departments])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date_now],
                    ['is', 'worker.date_end', null]
                ])
                ->indexBy("worker_id")
                ->all();

            // 5. Проверка знаний
            if ($workers) {
                foreach ($workers as $worker) {
                    $hand_workers[] = $worker['worker_id'];
                }

                $check_knowledges = (new Query())
                    ->select('
                    check_knowledge_worker.worker_id as worker_id,
                    max(check_knowledge.date) as max_check_knowledge_date
                ')
                    ->from('check_knowledge_worker')
                    ->innerJoin('check_knowledge', 'check_knowledge.id=check_knowledge_worker.check_knowledge_id')
                    ->where(['in', 'check_knowledge_worker.worker_id', $hand_workers])
                    ->andWhere(['check_knowledge_worker.status_id' => 79])                                                  // сдал
                    ->groupBy(['worker_id'])
                    ->indexBy('worker_id')
                    ->all();

                foreach ($workers as $worker) {
                    $flag = true;
                    $check_knowledge_date = null;

                    if (!$worker['check_knowledge_status_id']) {
                        $worker['check_knowledge_status_id'] = 1;
                    }

                    if (!isset($check_knowledges[$worker['worker_id']])) {
                        $restriction = "Проверка знаний не осуществлялась ни разу";
                        $flag = false;
                    } else if (strtotime($check_knowledges[$worker['worker_id']]['max_check_knowledge_date'] . '+1825 days') < strtotime($date_now)) {
                        $restriction = "Отсутствует действующая проверка знаний (> 5лет с аттестации)";
                        $check_knowledge_date = $check_knowledges[$worker['worker_id']]['max_check_knowledge_date'];
                        $flag = false;
                    }

                    if (!$flag) {
                        $check_knowledge[] = array(
                            'worker_id' => $worker['worker_id'],
                            'worker_full_name' => Assistant::GetShortFullName($worker['first_name'], $worker['patronymic'], $worker['last_name']),
                            'worker_staff_number' => $worker['worker_tabel_number'],
                            'worker_position_title' => $worker['position_title'],

                            'check_knowledge_date_time' => $check_knowledge_date,
                            'restriction' => $restriction,

                            'flag' => $flag,
                            'status_id' => $worker['check_knowledge_status_id'],
                            'restriction_id' => $worker['worker_id'],
                            'type_restriction' => 'check_knowledge'
                        );
                    }
                }

                if (isset($check_knowledge)) {
                    $result['check_knowledge'] = array(
                        "id" => 'check_knowledge',
                        "title" => "Инструктажи/проверка знаний",
                        "notifications" => $check_knowledge
                    );
                }
            }

            // 6. Инструктажи
            if ($workers) {
                $briefings = Briefing::find()
                    ->select(' briefer.worker_id, max(briefing.date_time) as max_date_briefing')
                    ->andWhere(['in', 'briefer.worker_id', $hand_workers])
                    ->andWhere(['briefing.type_briefing_id' => self::TYPE_BRIEFING_TWO])
                    ->innerJoin('briefer', 'briefer.briefing_id = briefing.id')
                    ->groupBy('briefer.worker_id')
                    ->indexBy('briefer.worker_id')
                    ->all();

                foreach ($workers as $worker) {
                    $flag = true;
                    $briefing_date = null;

                    if (!$worker['briefing_status_id']) {
                        $worker['briefing_status_id'] = 1;
                    }

                    if (isset($briefings[$worker['worker_id']])) {
                        $between_date = (strtotime($date_now) - strtotime($briefings[$worker['worker_id']]['max_date_briefing'])) / (60 * 60 * 24);
                    }

                    if (!isset($briefings[$worker['worker_id']])) {
                        $restriction = "Инструктаж не осуществлялся ни разу";
                        $flag = false;
                    } else if ($between_date < self::DAY_TYPE_TWO && $between_date > self::DAY_TYPE_ONE) {
                        $restriction = "Необходимо провести плановый инструктаж";
                        $briefing_date = $briefings[$worker['worker_id']]['max_date_briefing'];
                        $flag = false;
                    } else if ($between_date > self::DAY_TYPE_TWO) {
                        $restriction = "Ежеквартальный инструктаж закончился";
                        $briefing_date = $briefings[$worker['worker_id']]['max_date_briefing'];
                        $flag = false;
                    }

                    if (!$flag) {
                        $check_briefing[] = array(
                            'worker_id' => $worker['worker_id'],
                            'worker_full_name' => Assistant::GetShortFullName($worker['first_name'], $worker['patronymic'], $worker['last_name']),
                            'worker_staff_number' => $worker['worker_tabel_number'],
                            'worker_position_title' => $worker['position_title'],

                            'briefing_date_time' => $briefing_date,
                            'restriction' => $restriction,

                            'flag' => $flag,
                            'status_id' => $worker['briefing_status_id'],
                            'restriction_id' => $worker['worker_id'],
                            'type_restriction' => 'briefing'
                        );
                    }
                }

                if (isset($check_briefing)) {
                    $result['briefing'] = array(
                        "id" => 'briefing',
                        "title" => "Необходимо пройти инструктаж",
                        "notifications" => $check_briefing
                    );
                }
            }


            // 7. Предписание
            $found_data_inj = (new Query())
                ->select('
                    checking.id as checking_id,
                    injunction.id as injunction_id,
                    notification_status.status_id as status_id
                ')
                ->from('injunction')
                ->leftJoin('checking', 'checking.id = injunction.checking_id')
                ->leftJoin('notification_status', "notification_status.restriction_id = injunction.id AND notification_status.type_restriction = 'ppkInjunction'")
                ->where(['injunction.company_department_id' => $company_departments])
                ->andWhere(['injunction.status_id' => [CheckingController::STATUS_NEW, CheckingController::STATUS_IN_JOB]])
                ->andWhere('injunction.status_id!=59')
                ->andWhere(['injunction.kind_document_id' => [1, 3]])
                ->limit(50000)
                ->all();

            if ($found_data_inj) {
                foreach ($found_data_inj as $checking_item) {
                    if (!$checking_item['status_id']) {
                        $checking_item['status_id'] = 1;
                    }

                    $notifiction_inj[] = array(
                        'checking_id' => $checking_item['checking_id'],
                        'injunction_id' => $checking_item['injunction_id'],

                        'status_id' => $checking_item['status_id'],
                        'restriction_id' => $checking_item['injunction_id'],
                        'type_restriction' => 'ppkInjunction'
                    );
                }

                $result['ppkInjunction'] = array(
                    "id" => 'ppkInjunction',
                    "title" => "Выдано предписание",
                    "notifications" => $notifiction_inj
                );
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }


        $log->addLog("Конец метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод GetNotificationPersonal() - Возвращает персональные уведомления
     * @param null $data_post - JSON с идентификатором участка
     * @return array - список объектов со следующей структурой:
     * {
     *      "id": "siz",
     *      "title": "Необходима замена СИЗ",
     *      "notifications": []
     * },
     * {
     *      "id": "medicalExam",
     *      "title": "Запланированный медицинский осмотр",
     *      "notifications": []
     * },
     * {
     *      "id": "ppkPab",
     *      "title": "Выдан ПАБ",
     *      "notifications": []
     * },
     * {
     *      "id": "check_knowledge",
     *      "title": "Запланирована проверка знаний",
     *      "notifications": []
     * },
     * {
     *      "id": "briefing",
     *      "title": "Необходимо пройти инструктаж",
     *      "notifications": []
     * },
     *      notification         - уведомление
     *              restriction_id           - уникальный ключ для type_restriction в таблице notification_status
     *              type_restriction         - notification_status.type_restriction (siz,medicalExam,ppkPab,audit,check_knowledge,briefing,ppkInjunction)
     *              status_id                - статус уведомления (прочитан-19 или нет-1)
     *
     *              worker_id                - ключ работника
     *              worker_full_name         - ФИО
     *              worker_staff_number      - табельный номер работника
     *              worker_position_title    - должность
     *
     *              siz_id                   - ключ СИЗ
     *              siz_title                - название СИЗ
     *
     *              checkup_date_start       - дата начала медосмотра
     *              checkup_date_end         - дата окончания медосмотра
     *
     *              flag                     - true  - если до окончания срока медосмотра осталось 2 недели или менее, то возвращается оранжевый цвет
     *                                       | false - иначе срок замены просрочен, то возвращается красный цвет
     *                                       | null  - во всех остальных случаях
     *
     *              checking_id              - ключ проверки
     *
     *              ppk_id                   - ключ ППК
     *              ppk_date_time            - дата ППК
     *              ppk_status_id            - ключ статуса ППК Выдано, просрочено, выполнено и т.д.
     *
     *              injunction_id            - ключ нарушения
     *
     *              audit_id                 - ключ запланированной проверки
     *              audit_place_id           - ключ места
     *              audit_place_title        - название места
     *              audit_date_time          - дата запланированного аудита
     *
     *              check_knowledge_date_time- дата проверки знаний
     *
     *              briefing_date_time       - дата запланированного инструктажа
     *
     * @package frontend\controllers\notification
     *
     * @example 127.0.0.1/read-manager-amicum?controller=notification\Notification&method=GetNotificationPersonal&subscribe=&data={"company_id":4029938,"worker_id":2050328}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 11.09.2019 11:56
     */
    public static function GetNotificationPersonal($data_post = NULL)
    {
        $log = new LogAmicumFront("GetNotificationPersonal");
        $result = array(
            "siz" => null,
            "medicalExam" => null,
            "ppkPab" => null,
            "check_knowledge" => null,
            "briefing" => null,
        );
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $log->addLog("Данные успешно переданы");

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            if (
                !property_exists($post_dec, 'company_id') ||
                !property_exists($post_dec, 'worker_id')
            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }
            $company_id = $post_dec->company_id;
            $worker_id = $post_dec->worker_id;

            $response = DepartmentController::FindDepartment($company_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения вложенных департаментов');
            }
            $company_departments = $response['Items'];

            /**
             * Получение текущих: месяца, дня, года
             */
            $date_now = date('Y-m-d', strtotime(BackendAssistant::GetDateNow()));

            /**
             * Отнимаем от текущей даты 14 дней для показа уведомлений
             */
            $mk_date_plan = date('Y-m-d', strtotime($date_now . "- 14 days"));

            // 1. Медицинский осмотр персональный
            $physical_schedules = PhysicalSchedule::find()
                ->select([
                    'physical_worker.worker_id as worker_id',
                    'position.title as position_title',
                    'physical_schedule.company_department_id as company_id',
                    'physical_schedule.date_start as date_start',
                    'physical_schedule.date_end as date_end',
                    'worker.tabel_number as worker_tabel_number',
                    'employee.first_name as first_name',
                    'employee.last_name as last_name',
                    'employee.patronymic as patronymic',
                    'physical_worker_date.id as physical_worker_date_id',
                    'notification_status.status_id as status_id'
                ])
                ->innerJoin('physical_worker', 'physical_worker.physical_schedule_id = physical_schedule.id')
                ->leftJoin('physical_worker_date', 'physical_worker.id = physical_worker_date.physical_worker_id')
                ->innerJoin('worker', 'physical_worker.worker_id = worker.id')
                ->innerJoin('position', 'worker.position_id = position.id')
                ->innerJoin('employee', 'employee.id = worker.employee_id')
                ->leftJoin('notification_status', "notification_status.restriction_id = worker.id AND notification_status.type_restriction = 'medicalExam'")
//                ->where([
//                    'physical_schedule.status_id' => self::STATUS_ACTIVE])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date_now],
                    ['is', 'worker.date_end', null]
                ])
                ->andWhere(['physical_schedule.company_department_id' => $company_id])
                ->andWhere(['physical_worker.worker_id' => $worker_id])
                ->asArray()
                ->all();

            $log->addLog("Выгрузили из графика медосмотров");

            $med_report = MedReport::find()
                ->innerJoin('physical_worker_date', 'physical_worker_date.id = med_report.physical_worker_date')
                ->indexBy(
                    function ($report) {
                        return $report['worker_id'] . '_' . $report['physical_worker_date'];
                    }
                )
                ->all();

            $log->addLog("Выгрузили все результаты медосмотров");

            if ($physical_schedules) {
                foreach ($physical_schedules as $physical_schedule) {

                    /**
                     * Начинаем показывать уведомление за 2 недели до начала медосмотра у человека
                     * И показываем до тех пор пока у этого человека не появиться заключение медосмотра
                     */

                    // true - если до окончания срока медосмотра осталось 2 недели или менее, то возвращается оранжевый цвет
                    // false - иначе срок замены просрочен, то возвращается красный цвет
                    // null - во всех остальных случаях
                    $flag = null;
                    if ($physical_schedule['date_start'] >= $mk_date_plan) {
                        $flag = true;
                    } elseif (!isset($med_report[$physical_schedule['worker_id'] . '_' . $physical_schedule['physical_worker_date_id']])) {
                        $flag = false;
                    }

                    if (!$physical_schedule['status_id']) {
                        $physical_schedule['status_id'] = 1;
                    }

                    $checkup[] = array(
                        'flag' => $flag,
                        'worker_id' => $physical_schedule['worker_id'],
                        'worker_full_name' => Assistant::GetShortFullName($physical_schedule['first_name'], $physical_schedule['patronymic'], $physical_schedule['last_name']),
                        'worker_date_end' => $physical_schedule['date_end'],
                        'worker_staff_number' => $physical_schedule['worker_tabel_number'],
                        'worker_position_title' => $physical_schedule['position_title'],
                        'checkup_date_start' => date('d.m.Y', strtotime($physical_schedule['date_start'])),
                        'checkup_date_end' => date('d.m.Y', strtotime($physical_schedule['date_end'])),
                        'status_id' => $physical_schedule['status_id'],
                        'restriction_id' => $physical_schedule['worker_id'],
                        'type_restriction' => 'medicalExam'
                    );

                }

                $result['medicalExam'] = array(
                    "id" => 'medicalExam',
                    "title" => "Запланированный медицинский осмотр",
                    "notifications" => $checkup
                );

            }

            // 2. Необходима замена СИЗ
            $siz_need = (new Query())
                ->select('
                    siz.id as siz_id,
                    siz.title as siz_title,
                    worker_siz.date_issue as date_issue,
                    worker_siz.date_write_off as date_write_off,
                    siz.wear_period as wear_period,
                    worker_siz.worker_id as worker_id,
                    worker.tabel_number as worker_tabel_number,
                    position.title as position_title,
                    employee.first_name as first_name,
                    employee.last_name as last_name,
                    employee.patronymic as patronymic,
                    notification_status.status_id as status_id
                ')
                ->from('worker_siz')
                ->innerJoin('siz', 'worker_siz.siz_id=siz.id')
                ->innerJoin('worker', 'worker_siz.worker_id=worker.id')
                ->innerJoin('position', 'worker.position_id = position.id')
                ->innerJoin('employee', 'employee.id = worker.employee_id')
                ->leftJoin('notification_status', "notification_status.restriction_id = siz.id AND notification_status.type_restriction = 'siz'")
                ->where(['worker.company_department_id' => $company_departments])
                ->andWhere(['worker_siz.worker_id' => $worker_id])
                ->andWhere('worker_siz.status_id!=66')
                ->andWhere('wear_period<36')
                ->andWhere('wear_period!=0')
                ->andWhere(['is not', 'wear_period', null])
                ->andWhere(['<=', 'worker_siz.date_issue', $date_now])
                ->andWhere(['<=', 'worker_siz.date_write_off', $date_now])
                ->all();
            if ($siz_need) {
                foreach ($siz_need as $worker_siz) {

                    $flag = null;
                    $restriction = "";


                    // true - просрочен
                    // false - предаварийный - подходит к концу
                    // null - все норм
                    if (strtotime($worker_siz['date_write_off'] . '+14 days') > strtotime($date_now)) {
                        $restriction = "Срок эксплуатации СИЗ подходит к концу";
                        $flag = false;
                    } else if ($worker_siz['date_write_off'] > strtotime($date_now)) {
                        $restriction = "СИЗ просрочен";
                        $flag = true;
                    }

                    if (!$worker_siz['status_id']) {
                        $worker_siz['status_id'] = 1;
                    }

                    $sizs[] = array(
                        'worker_id' => $worker_siz['worker_id'],
                        'worker_full_name' => Assistant::GetShortFullName($worker_siz['first_name'], $worker_siz['patronymic'], $worker_siz['last_name']),
                        'worker_staff_number' => $worker_siz['worker_tabel_number'],
                        'worker_position_title' => $worker_siz['position_title'],

                        'siz_id' => $worker_siz['siz_id'],
                        'siz_title' => $worker_siz['siz_title'],

                        'checkup_date_start' => $worker_siz['date_issue'],
                        'checkup_date_end' => $worker_siz['date_write_off'],
                        'restriction' => $restriction,

                        'flag' => $flag,
                        'status_id' => $worker_siz['status_id'],
                        'restriction_id' => $worker_siz['siz_id'],
                        'type_restriction' => 'siz'
                    );
                }

                $result['siz'] = array(
                    "id" => 'siz',
                    "title" => "Необходима замена СИЗ",
                    "notifications" => $sizs
                );
            }

            // 3. Выдан ПАБ
            $found_data_pab = Checking::find()
                ->joinWith('injunctions.injunctionViolations.violators.worker.employee')
                ->joinWith('injunctions.injunctionViolations.violators.worker.position')
                ->where(['worker.company_department_id' => $company_departments])
                ->andWhere(['worker.id' => $worker_id])
                ->andWhere(['injunction.kind_document_id' => CheckingController::KIND_PAB])
                ->andWhere(['injunction.status_id' => [CheckingController::STATUS_NEW, CheckingController::STATUS_IN_JOB]])
                ->limit(50000)
                ->all();

            if ($found_data_pab) {
                foreach ($found_data_pab as $checking_item) {
                    foreach ($checking_item['injunctions'] as $injunction) {
                        foreach ($injunction->injunctionViolations as $injunctionViolation) {
                            foreach ($injunctionViolation->violators as $violator) {
                                $violators_id = $violator['id'];
                            }
                        }
                    }
                }
                $ppkPab_status_id = (new Query())
                    ->select('
                        status_id,
                        restriction_id
                    ')
                    ->from('notification_status')
                    ->where(['type_restriction' => 'ppkPab'])
                    ->andWhere(['restriction_id' => $violators_id])
                    ->indexBy('restriction_id')
                    ->all();

                foreach ($found_data_pab as $checking_item) {
                    foreach ($checking_item->injunctions as $injunction) {
                        foreach ($injunction->injunctionViolations as $injunctionViolation) {
                            foreach ($injunctionViolation->violators as $violator) {

                                if (isset($ppkPab_status_id[$violator['id']])) {
                                    $status_id = $ppkPab_status_id[$violator['id']]['status_id'];
                                } else {
                                    $status_id = 1;
                                }

                                $notifiction_pab[] = array(
                                    'worker_id' => $violator['worker_id'],
                                    'worker_full_name' => Assistant::GetShortFullName($violator['worker']['employee']['first_name'], $violator['worker']['employee']['patronymic'], $violator['worker']['employee']['last_name']),
                                    'worker_staff_number' => $violator['worker']['tabel_number'],
                                    'worker_position_title' => $violator['worker']['position']['title'],

                                    'checking_id' => $checking_item['id'],

                                    'ppk_id' => $injunction['id'],
                                    'ppk_date_time' => $checking_item['date_time_start'],

                                    'ppk_status_id' => $injunction['status_id'],

                                    'status_id' => $status_id,
                                    'restriction_id' => $violator['id'],
                                    'type_restriction' => 'ppkPab'
                                );
                            }
                        }
                    }
                }

                $result['ppkPab'] = array(
                    "id" => 'ppkPab',
                    "title" => "Выдан ПАБ",
                    "notifications" => $notifiction_pab
                );

            }

            // Список людей в подразделении
            $hand_workers = [];
            $workers = (new Query())
                ->select('
                    worker.id as worker_id,
                    worker.tabel_number as worker_tabel_number,
                    position.title as position_title,
                    employee.first_name as first_name,
                    employee.last_name as last_name,
                    employee.patronymic as patronymic,
                    check_k_n_status.status_id as check_knowledge_status_id,
                    briefing_n_status.status_id as briefing_status_id
                ')
                ->from('worker')
                ->innerJoin('position', 'worker.position_id = position.id')
                ->innerJoin('employee', 'employee.id = worker.employee_id')
                ->leftJoin('notification_status check_k_n_status', "check_k_n_status.restriction_id = worker.id AND check_k_n_status.type_restriction = 'check_knowledge'")
                ->leftJoin('notification_status briefing_n_status', "briefing_n_status.restriction_id = worker.id AND briefing_n_status.type_restriction = 'briefing'")
                ->where(["company_department_id" => $company_departments])
                ->andWhere(['worker.id' => $worker_id])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date_now],
                    ['is', 'worker.date_end', null]
                ])
                ->indexBy("worker_id")
                ->all();

            // 5. Проверка знаний
            if ($workers) {
                foreach ($workers as $worker) {
                    $hand_workers[] = $worker['worker_id'];
                }

                $check_knowledges = (new Query())
                    ->select('
                    check_knowledge_worker.worker_id as worker_id,
                    max(check_knowledge.date) as max_check_knowledge_date
                ')
                    ->from('check_knowledge_worker')
                    ->innerJoin('check_knowledge', 'check_knowledge.id=check_knowledge_worker.check_knowledge_id')
                    ->where(['in', 'check_knowledge_worker.worker_id', $hand_workers])
                    ->andWhere(['check_knowledge_worker.worker_id' => $worker_id])
                    ->andWhere(['check_knowledge_worker.status_id' => 79])                                                  // сдал
                    ->groupBy(['worker_id'])
                    ->indexBy('worker_id')
                    ->all();

                foreach ($workers as $worker) {
                    $flag = true;
                    $check_knowledge_date = null;

                    if (!$worker['check_knowledge_status_id']) {
                        $worker['check_knowledge_status_id'] = 1;
                    }

                    if (!isset($check_knowledges[$worker['worker_id']])) {
                        $restriction = "Проверка знаний не осуществлялась ни разу";
                        $flag = false;
                    } else if (strtotime($check_knowledges[$worker['worker_id']]['max_check_knowledge_date'] . '+1825 days') < strtotime($date_now)) {
                        $restriction = "Отсутствует действующая проверка знаний (> 5лет с аттестации)";
                        $check_knowledge_date = $check_knowledges[$worker['worker_id']]['max_check_knowledge_date'];
                        $flag = false;
                    }

                    if (!$flag) {
                        $check_knowledge[] = array(
                            'worker_id' => $worker['worker_id'],
                            'worker_full_name' => Assistant::GetShortFullName($worker['first_name'], $worker['patronymic'], $worker['last_name']),
                            'worker_staff_number' => $worker['worker_tabel_number'],
                            'worker_position_title' => $worker['position_title'],

                            'check_knowledge_date_time' => $check_knowledge_date,
                            'restriction' => $restriction,

                            'flag' => $flag,
                            'status_id' => $worker['check_knowledge_status_id'],
                            'restriction_id' => $worker['worker_id'],
                            'type_restriction' => 'check_knowledge'
                        );
                    }
                }

                if (isset($check_knowledge)) {
                    $result['check_knowledge'] = array(
                        "id" => 'check_knowledge',
                        "title" => "Инструктажи/проверка знаний",
                        "notifications" => $check_knowledge
                    );
                }
            }

            // 6. Инструктажи
            if ($workers) {
                $briefings = Briefing::find()
                    ->select(' briefer.worker_id, max(briefing.date_time) as max_date_briefing')
                    ->andWhere(['in', 'briefer.worker_id', $hand_workers])
                    ->andWhere(['briefing.type_briefing_id' => self::TYPE_BRIEFING_TWO])
                    ->andWhere(['briefer.worker_id' => $worker_id])
                    ->innerJoin('briefer', 'briefer.briefing_id = briefing.id')
                    ->groupBy('briefer.worker_id')
                    ->indexBy('briefer.worker_id')
                    ->all();

                foreach ($workers as $worker) {
                    $flag = true;
                    $briefing_date = null;

                    if (!$worker['briefing_status_id']) {
                        $worker['briefing_status_id'] = 1;
                    }

                    if (isset($briefings[$worker['worker_id']])) {
                        $between_date = (strtotime($date_now) - strtotime($briefings[$worker['worker_id']]['max_date_briefing'])) / (60 * 60 * 24);
                    }

                    if (!isset($briefings[$worker['worker_id']])) {
                        $restriction = "Инструктаж не осуществлялся ни разу";
                        $flag = false;
                    } else if ($between_date < self::DAY_TYPE_TWO && $between_date > self::DAY_TYPE_ONE) {
                        $restriction = "Необходимо провести плановый инструктаж";
                        $briefing_date = $briefings[$worker['worker_id']]['max_date_briefing'];
                        $flag = false;
                    } else if ($between_date > self::DAY_TYPE_TWO) {
                        $restriction = "Ежеквартальный инструктаж закончился";
                        $briefing_date = $briefings[$worker['worker_id']]['max_date_briefing'];
                        $flag = false;
                    }

                    if (!$flag) {
                        $check_briefing[] = array(
                            'worker_id' => $worker['worker_id'],
                            'worker_full_name' => Assistant::GetShortFullName($worker['first_name'], $worker['patronymic'], $worker['last_name']),
                            'worker_staff_number' => $worker['worker_tabel_number'],
                            'worker_position_title' => $worker['position_title'],

                            'briefing_date_time' => $briefing_date,
                            'restriction' => $restriction,

                            'flag' => $flag,
                            'status_id' => $worker['briefing_status_id'],
                            'restriction_id' => $worker['worker_id'],
                            'type_restriction' => 'briefing'
                        );
                    }
                }

                if (isset($check_briefing)) {
                    $result['briefing'] = array(
                        "id" => 'briefing',
                        "title" => "Необходимо пройти инструктаж",
                        "notifications" => $check_briefing
                    );
                }
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }


        $log->addLog("Конец метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод ChangeNotificationPersonal() - сохранение таблицы notification_status
     * @param null $data_post - Входные параметры:
     *      worker_id
     *      restrictions_id
     *      type_restriction
     *      status_id
     * @return array - Выходные параметры:
     *      [restriction_id]: {
     *          id
     *          worker_id
     *          date_time
     *          restriction_id
     *          type_restriction
     *          status_id
     *      }
     *      ...
     * @example 127.0.0.1/read-manager-amicum?controller=notification\Notification&method=ChangeNotificationPersonal&subscribe=&data={"worker_id":2051056,"restrictions_id":[10578931, 3641204],"type_restriction":"ppkPab","status_id":19}
     */
    public static function ChangeNotificationPersonal($data_post = NULL)
    {
        $log = new LogAmicumFront("ChangeNotificationPersonal");
        $result = array();

        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $log->addLog("Данные успешно переданы");

            $post_dec = json_decode($data_post);
            $log->addData($post_dec);

            if (
                !property_exists($post_dec, 'worker_id') ||
                !property_exists($post_dec, 'restrictions_id') ||
                !property_exists($post_dec, 'type_restriction') ||
                !property_exists($post_dec, 'status_id')
            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }
            $worker_id = $post_dec->worker_id;
            $restrictions_id = $post_dec->restrictions_id;
            $type_restriction = $post_dec->type_restriction;
            $status_id = $post_dec->status_id;

            $notifications_status = NotificationStatus::find()
                ->where([
                    'worker_id' => $worker_id,
                    'restriction_id' => $restrictions_id,
                    'type_restriction' => $type_restriction
                ])
                ->indexBy('restriction_id')
                ->all();

            foreach ($restrictions_id as $restriction_id) {

                if (!isset($notifications_status[$restriction_id])) {
                    $log->addLog("Не нашли notification_status worker_id = $worker_id");
                    $notifications_status[$restriction_id] = new NotificationStatus();
                }
                $notifications_status[$restriction_id]->worker_id = $worker_id;
                $notifications_status[$restriction_id]->restriction_id = $restriction_id;
                $notifications_status[$restriction_id]->type_restriction = $type_restriction;
                $notifications_status[$restriction_id]->status_id = $status_id;
                $notifications_status[$restriction_id]->date_time = Assistant::GetDateTimeNow();

                if (!$notifications_status[$restriction_id]->save()) {
                    $log->addData($notifications_status[$restriction_id]->errors, '$notifications_status[$restriction_id]->errors', __LINE__);
                    throw new Exception("Ошибка сохранения notification_status. Модели NotificationStatus");
                }

            }

            $result = $notifications_status;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Конец метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

}
