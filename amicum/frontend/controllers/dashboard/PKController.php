<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\dashboard;

use backend\controllers\webSocket\AmicumWebSocketClient;
use Exception;
use frontend\controllers\handbooks\DepartmentController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Checking;
use frontend\models\Injunction;
use frontend\models\SummaryReportEndOfShift;
use Throwable;
use yii\web\Controller;

class PKController extends Controller
{
    // GetInjunctionDashBoard           - метод получения проверок с предписаниями по производственному контролю
    // GetNNDashBoard                   - метод получения проверок с нарушениями/несоответствиями по производственному контролю
    // GetPabDashBoard                  - метод получения ПАБ

    // GetStatisticInjunctionDashBoard  - метод получения статистики по предписаниям и проверкам
    // GetStatisticPabDashBoard         - метод получения статистики по ПАБ
    // GetStatisticNNDashBoard          - метод получения статистики по нарушениям/несоответствиям


    /**
     * GetInjunctionDashBoard - метод получения проверок с предписаниями по производственному контролю
     * Входной объект:
     *      sourceClientId          - ключ Web Socket клиента
     *      requestId               - ключ запроса
     *      mine_id                 - ключ шахтного поля
     *      company_department_id   - ключ подразделения
     *      data_start              - дата начала выборки
     *      data_end                - дата окончания выборки
     * Выходной объект:
     *      []
     *          auditors:                                                                                                       - список аудиторов
     *              []
     *                  first_name:                  "Не заполнено"                                                             - имя аудитора
     *                  last_name:                   "Не заполнено"                                                             - фамилия аудитора
     *                  patronymic:                  "Не заполнено"                                                             - отчество аудитора
     *                  worker_id:                   1                                                                          - ключ аудитора
     *                  tabel_number:                "1"                                                                        - табельный номер аудитора
     *                  position_id:                 3679                                                                       - ключ должности аудитора
     *                  position_title:              "Электрослесарь (слесарь) дежурный и по ремонту оборудования 1 разряда"    - должность аудитора
     *                  descent_status:              true                                                                       - факт спуска аудитора в шахту
     *          check_date:                  "2021-10-20 16:40:00"                                                              - дата проведения проверки
     *          check_type_id:               1                                                                                  - ключ типа проверки (в данном случае плановая)
     *          check_type_title:            "Плановая"                                                                         - тип проверки
     *          place_id:                    136                                                                                - ключ места
     *          place_title:                 "ВВС"                                                                              - название места
     *          company_department_id:       20028763                                                                           - ключ проверяемого подразделения
     *          department_title:            "УВТ"                                                                              - название проверяемого подразделения
     *          injunction_id:               10578938                                                                           - ключ предписания
     *          ppk_id:                      10578938                                                                           - ключ предписания (нужен как универсальная часть)
     *          appearing_date:              "2022-01-14 14:54:30"                                                              - дата создания документа проверки
     *          violations:                                                                                                     - нарушения
     *              []
     *                  injunctionViolation_id:      8458124                                                                    - ключ конкретного нарушения
     *                  violation_id:                3264656                                                                    - ключ описания нарушения
     *                  violation_title:             "ВВС нарушение сильное"                                                    - описание нарушения
     *                  violation_type_id:           36                                                                         - ключ типа нарушения
     *                  violation_type_title:        "Вентиляторные установки (ВМП, ВГП)"                                       - название типа нарушения
     *          work_stoppage_status:        false                                                                              - была или нет остановка по результатам проверки
     *
     * @param $data_post
     * @return null[]
     * @example 127.0.0.1/read-manager-amicum?controller=dashboard\pk&method=GetInjunctionDashBoard&subscribe=&data={"sourceClientId":1,"requestId":1,"mine_id":290,"company_department_id":201,"data_start":"2020-05-01","data_end":"2020-05-31"}
     * @example http://127.0.0.1/read-manager-amicum?controller=dashboard\pk&method=GetInjunctionDashBoard&subscribe=&data={%22sourceClientId%22:1,%22requestId%22:1,%22mine_id%22:250,%22company_department_id%22:20028763,%22data_start%22:%222020-05-01%22,%22data_end%22:%222022-05-31%22}
     * @example http://127.0.0.1/read-manager-amicum?controller=dashboard\pk&method=GetInjunctionDashBoard&subscribe=&data={%22sourceClientId%22:1,%22requestId%22:1,%22mine_id%22:-1,%22company_department_id%22:20028763,%22data_start%22:%222020-05-01%22,%22data_end%22:%222021-05-31%22}
     */
    public static function GetInjunctionDashBoard($data_post = null)
    {
        $result = array('data' => null, 'settings' => null, 'requestId' => null);                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("GetInjunctionDashBoard");
        try {

            $log->addLog("Начал выполнять метод");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'sourceClientId') ||
                !property_exists($post_dec, 'requestId') ||
                !property_exists($post_dec, 'mine_id') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'data_start') ||
                !property_exists($post_dec, 'data_end')) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $sourceClientId = $post_dec->sourceClientId;
            $requestId = $post_dec->requestId;
            $result['requestId'] = $requestId;
            $mine_id = $post_dec->mine_id;
            $company_department_id = $post_dec->company_department_id;
            $data_start = date("Y-m-d H:i:s", strtotime($post_dec->data_start));
            $data_end = date("Y-m-d H:i:s", strtotime($post_dec->data_end));
            $filters = [];

            if ($company_department_id > -1) {
                $response = DepartmentController::FindDepartment($company_department_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка получения вложенных департаментов: ' . $company_department_id);
                }
                // ВАЖНО!!!!! При расчете статистики, может быть расхождение
                // предписание может быть выписано на один участок, а ответственный может быть с другого участка!
                // Критично для ПАБ - если человек ушел на другой участок, то его ПАБ на новом участке отображаться не будет
                // в Ином случае, если сделать, то получиться, что ПАБ он заработал на новом переведенном участке
                // Иными словами нужно определиться, что мы контролируем - ПАБ как косяк человека, Или ПАБы как косяк руководителя
                // НО!!! по предписания - они закрепляется за участком, а не за ответственным
                // НО тут есть косяк!!! если предписание РТН, то оно выписывается на шахту, но не на участок! иметь это ввиду.
                $filters['checking.company_department_id'] = $response['Items'];
            }

            if ($mine_id > -1) {
                $filters['place.mine_id'] = $mine_id;
            }

            /**
             * Справочник спустившихся в шахту
             */
            $descents_handbook = SummaryReportEndOfShift::find()
                ->andWhere('date_time>="' . $data_start . '"')
                ->andWhere('date_time<="' . $data_end . '"')
                ->indexBy(function ($report) {
                    return $report['worker_id'] . '_' . date("Y-m-d", strtotime($report['date_time']));
                })
                ->asArray()
                ->all();

            /**
             * Список предписаний
             */
            $injunctions = Injunction::find()
                ->innerJoinWith('checking')                                                                         // проверка
                ->innerJoinWith('place')                                                                            // место
                ->joinWith('status')                                                                                // статусы
                ->joinWith('checking.checkingWorkerTypes.worker.employee')
                ->joinWith('checking.checkingWorkerTypes.worker.position')
                ->joinWith('checking.checkingType')                                                                 // плановые/неплановые
                ->joinWith('companyDepartment.company')                                                             // подразделение косячник
                ->joinWith('injunctionViolations.violation.violationType')                                          // нарушения
                ->joinWith('injunctionViolations.stopPbs')                                                          // остановки
                ->where($filters)
                ->andWhere('checking.date_time_start>="' . $data_start . '"')
                ->andWhere('checking.date_time_end<="' . $data_end . '"')
                ->andWhere(['injunction.kind_document_id' => [1, 3, 5]])                                                              // 1 - предписание, 3 - предписание РТН, 5 - рапорт
                ->all();

            /**
             * Заполнение выходного объекта
             */
            foreach ($injunctions as $injunction) {
                $auditors = [];
                $check_date = $injunction->checking->date_time_start;
                if (isset($injunction->checking->checkingWorkerTypes)) {
                    foreach ($injunction->checking->checkingWorkerTypes as $staff) {
                        if ($staff->worker_type_id == 1) {                                                              // 1 - инспектор
                            $auditors[] = array(
                                'first_name' => $staff->worker->employee->first_name,
                                'last_name' => $staff->worker->employee->last_name,
                                'patronymic' => $staff->worker->employee->patronymic,
                                'worker_id' => $staff->worker_id,
                                'tabel_number' => $staff->worker->tabel_number,
                                'position_id' => $staff->worker->position_id,
                                'position_title' => $staff->worker->position->title,
                                'descent_status' => isset($descents_handbook[$staff->worker_id . '_' . date("Y-m-d", strtotime($check_date))]),
                            );
                        }
                    }
                }

                $violations = [];
                $work_stoppage_status = false;
                if (isset($injunction->injunctionViolations)) {
                    foreach ($injunction->injunctionViolations as $injunctionViolation) {
                        if (!$work_stoppage_status) {
                            $work_stoppage_status = !empty($injunctionViolation->stopPbs);
                        }

                        $violations[] = [
                            'injunctionViolation_id' => $injunctionViolation->id,
                            'violation_id' => $injunctionViolation->violation->id,
                            'violation_title' => $injunctionViolation->violation->title,
                            'violation_type_id' => $injunctionViolation->violation->violationType->id,
                            'violation_type_title' => $injunctionViolation->violation->violationType->title
                        ];
                    }
                }

                $result['data'][] = [
                    'auditors' => $auditors,
                    'check_date' => $check_date,
                    'check_type_id' => $injunction->checking->checking_type_id,
                    'check_type_title' => $injunction->checking->checkingType->title,
                    'place_id' => $injunction->place_id,
                    'place_title' => $injunction->place->title,
                    'status_id' => $injunction->status_id,
                    'status_title' => $injunction->status->title,
                    'company_department_id' => $injunction->company_department_id,
                    'department_title' => $injunction->companyDepartment->company->title,
                    'injunction_id' => $injunction->id,
                    'ppk_id' => $injunction->id,
                    'appearing_date' => $injunction->checking->date_time_create,
                    'violations' => $violations,
                    'work_stoppage_status' => $work_stoppage_status,
                ];
            }

            $result['requestId'] = $requestId;

            if (property_exists($post_dec, 'settings')) {
                $result['settings'] = $post_dec->settings;
            }

            (new AmicumWebSocketClient)->sendDataClient($result, $sourceClientId);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Закончил выполнять метод");
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetNNDashBoard - метод получения проверок с нарушениями/несоответствиями по производственному контролю
     * Входной объект:
     *      sourceClientId          - ключ Web Socket клиента
     *      requestId               - ключ запроса
     *      mine_id                 - ключ шахтного поля
     *      company_department_id   - ключ подразделения
     *      data_start              - дата начала выборки
     *      data_end                - дата окончания выборки
     * Выходной объект:
     *      []
     *          auditors:                                                                                                       - список аудиторов
     *              []
     *                  first_name:                  "Не заполнено"                                                             - имя аудитора
     *                  last_name:                   "Не заполнено"                                                             - фамилия аудитора
     *                  patronymic:                  "Не заполнено"                                                             - отчество аудитора
     *                  worker_id:                   1                                                                          - ключ аудитора
     *                  tabel_number:                "1"                                                                        - табельный номер аудитора
     *                  position_id:                 3679                                                                       - ключ должности аудитора
     *                  position_title:              "Электрослесарь (слесарь) дежурный и по ремонту оборудования 1 разряда"    - должность аудитора
     *                  descent_status:              true                                                                       - факт спуска аудитора в шахту
     *          check_date:                  "2021-10-20 16:40:00"                                                              - дата проведения аудита
     *          check_type_id:               1                                                                                  - ключ типа проверки (в данном случае плановая)
     *          check_type_title:            "Плановая"                                                                         - тип проверки
     *          place_id:                    136                                                                                - ключ места
     *          place_title:                 "ВВС"                                                                              - название места
     *          company_department_id:       20028763                                                                           - ключ проверяемого подразделения
     *          department_title:            "УВТ"                                                                              - название проверяемого подразделения
     *          nn_id:               10578938                                                                                   - ключ нарушения/несоответствия
     *          ppk_id:                      10578938                                                                           - ключ нарушения/несоответствия (нужен как универсальная часть)
     *          appearing_date:              "2022-01-14 14:54:30"                                                              - дата создания документа проверки
     *          violations:                                                                                                     - нарушения
     *              []
     *                  injunctionViolation_id:      8458124                                                                    - ключ конкретного нарушения
     *                  violation_id:                3264656                                                                    - ключ описания нарушения
     *                  violation_title:             "ВВС нарушение сильное"                                                    - описание нарушения
     *                  violation_type_id:           36                                                                         - ключ типа нарушения
     *                  violation_type_title:        "Вентиляторные установки (ВМП, ВГП)"                                       - название типа нарушения
     *          work_stoppage_status:        false                                                                              - была или нет остановка по результатам проверки
     *
     * @param $data_post
     * @return null[]
     * @example 127.0.0.1/read-manager-amicum?controller=dashboard\pk&method=GetNNDashBoard&subscribe=&data={"sourceClientId":1,"requestId":1,"mine_id":290,"company_department_id":20000526,"data_start":"2020-05-01","data_end":"2020-05-31"}
     * @example http://127.0.0.1/read-manager-amicum?controller=dashboard\pk&method=GetNNDashBoard&subscribe=&data={%22sourceClientId%22:1,%22requestId%22:1,%22mine_id%22:250,%22company_department_id%22:20000526,%22data_start%22:%222020-05-01%22,%22data_end%22:%222022-05-31%22}
     * @example http://127.0.0.1/read-manager-amicum?controller=dashboard\pk&method=GetNNDashBoard&subscribe=&data={%22sourceClientId%22:1,%22requestId%22:1,%22mine_id%22:-1,%22company_department_id%22:20000526,%22data_start%22:%222020-05-01%22,%22data_end%22:%222021-05-31%22}
     */
    public static function GetNNDashBoard($data_post = null)
    {
        $result = array('data' => null, 'settings' => null, 'requestId' => null);                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("GetNNDashBoard");
        try {

            $log->addLog("Начал выполнять метод");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'sourceClientId') ||
                !property_exists($post_dec, 'requestId') ||
                !property_exists($post_dec, 'mine_id') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'data_start') ||
                !property_exists($post_dec, 'data_end')) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $sourceClientId = $post_dec->sourceClientId;
            $requestId = $post_dec->requestId;
            $result['requestId'] = $requestId;
            $mine_id = $post_dec->mine_id;
            $company_department_id = $post_dec->company_department_id;
            $data_start = date("Y-m-d H:i:s", strtotime($post_dec->data_start));
            $data_end = date("Y-m-d H:i:s", strtotime($post_dec->data_end));
            $filters = [];

            if ($company_department_id > -1) {
                $response = DepartmentController::FindDepartment($company_department_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка получения вложенных департаментов: ' . $company_department_id);
                }
                // ВАЖНО!!!!! При расчете статистики, может быть расхождение
                // предписание может быть выписано на один участок, а ответственный может быть с другого участка!
                // Критично для ПАБ - если человек ушел на другой участок, то его ПАБ на новом участке отображаться не будет
                // в Ином случае, если сделать, то получиться, что ПАБ он заработал на новом переведенном участке
                // Иными словами нужно определиться, что мы контролируем - ПАБ как косяк человека, Или ПАБы как косяк руководителя
                // НО!!! по предписания - они закрепляется за участком, а не за ответственным
                // НО тут есть косяк!!! если предписание РТН, то оно выписывается на шахту, но не на участок! иметь это ввиду.
                $filters['checking.company_department_id'] = $response['Items'];
            }

            if ($mine_id > -1) {
                $filters['place.mine_id'] = $mine_id;
            }

            /**
             * Справочник спустившихся в шахту
             */
            $descents_handbook = SummaryReportEndOfShift::find()
                ->andWhere('date_time>="' . $data_start . '"')
                ->andWhere('date_time<="' . $data_end . '"')
                ->indexBy(function ($report) {
                    return $report['worker_id'] . '_' . date("Y-m-d", strtotime($report['date_time']));
                })
                ->asArray()
                ->all();

            /**
             * Список нарушений/несоответствий
             */
            $nns = Injunction::find()
                ->innerJoinWith('checking')                                                                         // проверка
                ->innerJoinWith('place')                                                                            // место
                ->joinWith('status')                                                                                // статусы
                ->joinWith('checking.checkingWorkerTypes.worker.employee')
                ->joinWith('checking.checkingWorkerTypes.worker.position')
                ->joinWith('checking.checkingType')                                                                 // плановые/неплановые
                ->joinWith('companyDepartment.company')                                                             // подразделение косячник
                ->joinWith('injunctionViolations.violation.violationType')                                          // нарушения
                ->joinWith('injunctionViolations.stopPbs')                                                          // остановки
                ->where($filters)
                ->andWhere('checking.date_time_start>="' . $data_start . '"')
                ->andWhere('checking.date_time_end<="' . $data_end . '"')
                ->andWhere(['injunction.kind_document_id' => [4]])                                                                 // 4 - нарушения/несоответствия
                ->all();

            /**
             * Заполнение выходного объекта
             */
            foreach ($nns as $nn) {
                $auditors = [];
                $check_date = $nn->checking->date_time_start;
                if (isset($nn->checking->checkingWorkerTypes)) {
                    foreach ($nn->checking->checkingWorkerTypes as $staff) {
                        if ($staff->worker_type_id == 1) {                                                              // 1 - аудитор
                            $auditors[] = array(
                                'first_name' => $staff->worker->employee->first_name,
                                'last_name' => $staff->worker->employee->last_name,
                                'patronymic' => $staff->worker->employee->patronymic,
                                'worker_id' => $staff->worker_id,
                                'tabel_number' => $staff->worker->tabel_number,
                                'position_id' => $staff->worker->position_id,
                                'position_title' => $staff->worker->position->title,
                                'descent_status' => isset($descents_handbook[$staff->worker_id . '_' . date("Y-m-d", strtotime($check_date))]),
                            );
                        }
                    }
                }

                $violations = [];
                $work_stoppage_status = false;
                if (isset($nn->injunctionViolations)) {
                    foreach ($nn->injunctionViolations as $injunctionViolation) {
                        if (!$work_stoppage_status) {
                            $work_stoppage_status = !empty($injunctionViolation->stopPbs);
                        }

                        $violations[] = [
                            'injunctionViolation_id' => $injunctionViolation->id,
                            'violation_id' => $injunctionViolation->violation->id,
                            'violation_title' => $injunctionViolation->violation->title,
                            'violation_type_id' => $injunctionViolation->violation->violationType->id,
                            'violation_type_title' => $injunctionViolation->violation->violationType->title
                        ];
                    }
                }

                $result['data'][] = [
                    'auditors' => $auditors,
                    'check_date' => $check_date,
                    'check_type_id' => $nn->checking->checking_type_id,
                    'check_type_title' => $nn->checking->checkingType->title,
                    'place_id' => $nn->place_id,
                    'place_title' => $nn->place->title,
                    'status_id' => $nn->status_id,
                    'status_title' => $nn->status->title,
                    'company_department_id' => $nn->company_department_id,
                    'department_title' => $nn->companyDepartment->company->title,
                    'nn_id' => $nn->id,
                    'ppk_id' => $nn->id,
                    'appearing_date' => $nn->checking->date_time_create,
                    'violations' => $violations,
                    'work_stoppage_status' => $work_stoppage_status,
                    'requestId' => $requestId,
                ];
            }

            $result['requestId'] = $requestId;

            if (property_exists($post_dec, 'settings')) {
                $result['settings'] = $post_dec->settings;
            }

            (new AmicumWebSocketClient)->sendDataClient($result, $sourceClientId);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Закончил выполнять метод");
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetPabDashBoard - метод получения ПАБ
     * Входной объект:
     *      sourceClientId          - ключ Web Socket клиента
     *      requestId               - ключ запроса
     *      mine_id                 - ключ шахтного поля
     *      company_department_id   - ключ подразделения
     *      data_start              - дата начала выборки
     *      data_end                - дата окончания выборки
     * Выходной объект:
     *      []
     *          auditors:                                                                                                       - список аудиторов
     *              []
     *                  first_name:                  "Не заполнено"                                                             - имя аудитора
     *                  last_name:                   "Не заполнено"                                                             - фамилия аудитора
     *                  patronymic:                  "Не заполнено"                                                             - отчество аудитора
     *                  worker_id:                   1                                                                          - ключ аудитора
     *                  tabel_number:                "1"                                                                        - табельный номер аудитора
     *                  position_id:                 3679                                                                       - ключ должности аудитора
     *                  position_title:              "Электрослесарь (слесарь) дежурный и по ремонту оборудования 1 разряда"    - должность аудитора
     *                  descent_status:              true                                                                       - факт спуска аудитора в шахту
     *          violators:                                                                                                      - список нарушителей
     *              []
     *                  first_name:                  "Не заполнено"                                                             - имя аудитора
     *                  last_name:                   "Не заполнено"                                                             - фамилия аудитора
     *                  patronymic:                  "Не заполнено"                                                             - отчество аудитора
     *                  worker_id:                   1                                                                          - ключ аудитора
     *                  tabel_number:                "1"                                                                        - табельный номер аудитора
     *                  position_id:                 3679                                                                       - ключ должности аудитора
     *                  position_title:              "Электрослесарь (слесарь) дежурный и по ремонту оборудования 1 разряда"    - должность аудитора
     *                  descent_status:              true                                                                       - факт спуска нарушителя в шахту
     *          check_date:                  "2021-10-20 16:40:00"                                                              - дата проведения аудита
     *          check_type_id:               1                                                                                  - ключ типа проверки (в данном случае плановая)
     *          check_type_title:            "Плановая"                                                                         - тип проверки
     *          place_id:                    136                                                                                - ключ места
     *          place_title:                 "ВВС"                                                                              - название места
     *          company_department_id:       20028763                                                                           - ключ проверяемого подразделения
     *          department_title:            "УВТ"                                                                              - название проверяемого подразделения
     *          pab_id:                      10578938                                                                           - ключ поведенческого аудита безопасности
     *          ppk_id:                      10578938                                                                           - ключ нарушения/несоответствия (нужен как универсальная часть)
     *          appearing_date:              "2022-01-14 14:54:30"                                                              - дата создания документа проверки
     *          violations:                                                                                                     - нарушения
     *              []
     *                  injunctionViolation_id:      8458124                                                                    - ключ конкретного нарушения
     *                  violation_id:                3264656                                                                    - ключ описания нарушения
     *                  violation_title:             "ВВС нарушение сильное"                                                    - описание нарушения
     *                  violation_type_id:           36                                                                         - ключ типа нарушения
     *                  violation_type_title:        "Вентиляторные установки (ВМП, ВГП)"                                       - название типа нарушения
     *          work_stoppage_status:        false                                                                              - была или нет остановка по результатам проверки
     *
     * @param $data_post
     * @return null[]
     * @example 127.0.0.1/read-manager-amicum?controller=dashboard\pk&method=GetPabDashBoard&subscribe=&data={"sourceClientId":1,"requestId":1,"mine_id":290,"company_department_id":20037707,"data_start":"2019-05-01","data_end":"2022-05-31"}
     * @example http://127.0.0.1/read-manager-amicum?controller=dashboard\pk&method=GetPabDashBoard&subscribe=&data={%22sourceClientId%22:1,%22requestId%22:1,%22mine_id%22:290,%22company_department_id%22:20037707,%22data_start%22:%222019-05-01%22,%22data_end%22:%222022-05-31%22}
     * @example http://127.0.0.1/read-manager-amicum?controller=dashboard\pk&method=GetPabDashBoard&subscribe=&data={%22sourceClientId%22:1,%22requestId%22:1,%22mine_id%22:-1,%22company_department_id%22:20037707,%22data_start%22:%222019-05-01%22,%22data_end%22:%222021-05-31%22}
     */
    public static function GetPabDashBoard($data_post = null)
    {
        $result = array('data' => null, 'settings' => null, 'requestId' => null);                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("GetPabDashBoard");
        try {

            $log->addLog("Начал выполнять метод");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'sourceClientId') ||
                !property_exists($post_dec, 'requestId') ||
                !property_exists($post_dec, 'mine_id') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'data_start') ||
                !property_exists($post_dec, 'data_end')) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $sourceClientId = $post_dec->sourceClientId;
            $requestId = $post_dec->requestId;
            $result['requestId'] = $requestId;
            $mine_id = $post_dec->mine_id;
            $company_department_id = $post_dec->company_department_id;
            $data_start = date("Y-m-d H:i:s", strtotime($post_dec->data_start));
            $data_end = date("Y-m-d H:i:s", strtotime($post_dec->data_end));
            $filters = [];

            if ($company_department_id > -1) {
                $response = DepartmentController::FindDepartment($company_department_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка получения вложенных департаментов: ' . $company_department_id);
                }
                // ВАЖНО!!!!! При расчете статистики, может быть расхождение
                // предписание может быть выписано на один участок, а ответственный может быть с другого участка!
                // Критично для ПАБ - если человек ушел на другой участок, то его ПАБ на новом участке отображаться не будет
                // в Ином случае, если сделать, то получиться, что ПАБ он заработал на новом переведенном участке
                // Иными словами нужно определиться, что мы контролируем - ПАБ как косяк человека, Или ПАБы как косяк руководителя
                // НО!!! по предписания - они закрепляется за участком, а не за ответственным
                // НО тут есть косяк!!! если предписание РТН, то оно выписывается на шахту, но не на участок! иметь это ввиду.
                $filters['injunction.company_department_id'] = $response['Items'];
            }

            if ($mine_id > -1) {
                $filters['place.mine_id'] = $mine_id;
            }

            /**
             * Справочник спустившихся в шахту
             */
            $descents_handbook = SummaryReportEndOfShift::find()
                ->andWhere('date_time>="' . $data_start . '"')
                ->andWhere('date_time<="' . $data_end . '"')
                ->indexBy(function ($report) {
                    return $report['worker_id'] . '_' . date("Y-m-d", strtotime($report['date_time']));
                })
                ->asArray()
                ->all();

            /**
             * Список ПАБов
             */
            $pabs = Injunction::find()
                ->innerJoinWith('checking')                                                                         // проверка
                ->innerJoinWith('place')                                                                            // место
                ->joinWith('status')                                                                                // статусы
                ->joinWith('checking.checkingWorkerTypes.worker.employee')
                ->joinWith('checking.checkingWorkerTypes.worker.position')
                ->joinWith('checking.checkingType')                                                                 // плановые/неплановые
                ->joinWith('companyDepartment.company')                                                             // подразделение косячник
                ->joinWith('injunctionViolations.violation.violationType')                                          // нарушения
                ->joinWith('injunctionViolations.stopPbs')                                                          // остановки
                ->where($filters)
                ->andWhere('checking.date_time_start>="' . $data_start . '"')
                ->andWhere('checking.date_time_end<="' . $data_end . '"')
                ->andWhere(['injunction.kind_document_id' => [2]])                                                                 // 2 - ПАБ
                ->all();

            /**
             * Заполнение выходного объекта
             */
            foreach ($pabs as $pab) {
                $auditors = [];                                                                                         // аудиторы
                $violators = [];                                                                                        // нарушители
                $check_date = $pab->checking->date_time_start;
                if (isset($pab->checking->checkingWorkerTypes)) {
                    foreach ($pab->checking->checkingWorkerTypes as $staff) {
                        if ($staff->worker_type_id == 1) {                                                              // 1 - аудитор
                            $auditors[] = array(
                                'first_name' => $staff->worker->employee->first_name,
                                'last_name' => $staff->worker->employee->last_name,
                                'patronymic' => $staff->worker->employee->patronymic,
                                'worker_id' => $staff->worker_id,
                                'tabel_number' => $staff->worker->tabel_number,
                                'position_id' => $staff->worker->position_id,
                                'position_title' => $staff->worker->position->title,
                                'descent_status' => isset($descents_handbook[$staff->worker_id . '_' . date("Y-m-d", strtotime($check_date))]),
                            );
                        }
                        if ($staff->worker_type_id == 4) {                                                              // 4 - нарушитель
                            $violators[] = array(
                                'first_name' => $staff->worker->employee->first_name,
                                'last_name' => $staff->worker->employee->last_name,
                                'patronymic' => $staff->worker->employee->patronymic,
                                'worker_id' => $staff->worker_id,
                                'tabel_number' => $staff->worker->tabel_number,
                                'position_id' => $staff->worker->position_id,
                                'position_title' => $staff->worker->position->title,
                                'descent_status' => isset($descents_handbook[$staff->worker_id . '_' . date("Y-m-d", strtotime($check_date))]),
                            );
                        }
                    }
                }

                $violations = [];
                $work_stoppage_status = false;
                if (isset($pab->injunctionViolations)) {
                    foreach ($pab->injunctionViolations as $injunctionViolation) {
                        if (!$work_stoppage_status) {
                            $work_stoppage_status = !empty($injunctionViolation->stopPbs);
                        }

                        $violations[] = [
                            'injunctionViolation_id' => $injunctionViolation->id,
                            'violation_id' => $injunctionViolation->violation->id,
                            'violation_title' => $injunctionViolation->violation->title,
                            'violation_type_id' => $injunctionViolation->violation->violationType->id,
                            'violation_type_title' => $injunctionViolation->violation->violationType->title
                        ];
                    }
                }

                $result['data'][] = [
                    'auditors' => $auditors,
                    'violators' => $violators,
                    'check_date' => $check_date,
                    'check_type_id' => $pab->checking->checking_type_id,
                    'check_type_title' => $pab->checking->checkingType->title,
                    'place_id' => $pab->place_id,
                    'place_title' => $pab->place->title,
                    'status_id' => $pab->status_id,
                    'status_title' => $pab->status->title,
                    'company_department_id' => $pab->company_department_id,
                    'department_title' => $pab->companyDepartment->company->title,
                    'pab_id' => $pab->id,
                    'ppk_id' => $pab->id,
                    'appearing_date' => $pab->checking->date_time_create,
                    'violations' => $violations,
                    'work_stoppage_status' => $work_stoppage_status,
                    'requestId' => $requestId,
                ];
            }

            $result['requestId'] = $requestId;

            if (property_exists($post_dec, 'settings')) {
                $result['settings'] = $post_dec->settings;
            }

            (new AmicumWebSocketClient)->sendDataClient($result, $sourceClientId);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Закончил выполнять метод");
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetStatisticInjunctionDashBoard - метод получения статистики по предписаниям и проверкам
     * Входной объект:
     *      sourceClientId          - ключ Web Socket клиента
     *      requestId               - ключ запроса
     *      mine_id                 - ключ шахтного поля
     *      company_department_id   - ключ подразделения
     *      data_start              - дата начала выборки
     *      data_end                - дата окончания выборки
     * Выходной объект:
     *      overallChecks:          14,                                     - Всего проведено проверок
     *      overallInjunctions:     13,                                     - Выдано предписаний
     *      overallWorkStoppage:    3,                                      - Предписания с приостановкой работ
     *      violationTypes:                                                 - нарушения по направлениям с расчетом количества
     *          {violation_type_id}:                                                 - ключ направления нарушения
     *              violation_type_id:      1,                                       - ключ направления нарушения
     *              violation_type_title:   "Противопожарная защита (ППЗ)",          - название направления нарушения
     *              count:                  19,                                      - количество нарушений по направлению нарушения
     * @param $data_post
     * @return null[]
     * @example 127.0.0.1/read-manager-amicum?controller=dashboard\pk&method=GetPabDashBoard&subscribe=&data={"sourceClientId":1,"requestId":1,"mine_id":290,"company_department_id":20037707,"data_start":"2019-05-01","data_end":"2022-05-31"}
     * @example http://127.0.0.1/read-manager-amicum?controller=dashboard\pk&method=GetPabDashBoard&subscribe=&data={%22sourceClientId%22:1,%22requestId%22:1,%22mine_id%22:290,%22company_department_id%22:20037707,%22data_start%22:%222019-05-01%22,%22data_end%22:%222022-05-31%22}
     * @example http://127.0.0.1/read-manager-amicum?controller=dashboard\pk&method=GetPabDashBoard&subscribe=&data={%22sourceClientId%22:1,%22requestId%22:1,%22mine_id%22:-1,%22company_department_id%22:20037707,%22data_start%22:%222019-05-01%22,%22data_end%22:%222021-05-31%22}
     */
    public static function GetStatisticInjunctionDashBoard($data_post = null)
    {
        $result = array('data' => null, 'settings' => null, 'requestId' => null);                                                                                                // результирующий массив (если требуется)
        $log = new LogAmicumFront("GetStatisticInjunctionDashBoard");
        try {

            $log->addLog("Начал выполнять метод");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'sourceClientId') ||
                !property_exists($post_dec, 'requestId') ||
                !property_exists($post_dec, 'mine_id') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'data_start') ||
                !property_exists($post_dec, 'data_end')) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $sourceClientId = $post_dec->sourceClientId;
            $requestId = $post_dec->requestId;
            $result['requestId'] = $requestId;
            $mine_id = $post_dec->mine_id;
            $company_department_id = $post_dec->company_department_id;
            $data_start = date("Y-m-d H:i:s", strtotime($post_dec->data_start));
            $data_end = date("Y-m-d H:i:s", strtotime($post_dec->data_end));
            $filters = [];

            if ($company_department_id > -1) {
                $response = DepartmentController::FindDepartment($company_department_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка получения вложенных департаментов: ' . $company_department_id);
                }
                // ВАЖНО!!!!! При расчете статистики, может быть расхождение
                // предписание может быть выписано на один участок, а ответственный может быть с другого участка!
                // Критично для ПАБ - если человек ушел на другой участок, то его ПАБ на новом участке отображаться не будет
                // в Ином случае, если сделать, то получиться, что ПАБ он заработал на новом переведенном участке
                // Иными словами нужно определиться, что мы контролируем - ПАБ как косяк человека, Или ПАБы как косяк руководителя
                // НО!!! по предписания - они закрепляется за участком, а не за ответственным
                // НО тут есть косяк!!! если предписание РТН, то оно выписывается на шахту, но не на участок! иметь это ввиду.
                $filters['checking.company_department_id'] = $response['Items'];
            }

            if ($mine_id > -1) {
                $filters['place.mine_id'] = $mine_id;
            }

            /**
             * Список проверок
             */
            $count_checkings = Checking::find()
                ->innerJoinWith('checkingPlaces.place')                                                            // место
                ->where($filters)
                ->andWhere(['checking.kind_document_id' => [1, 3, 5]])                                                              // 1 - предписание, 3 - предписание РТН, 5 - рапорт
                ->andWhere('checking.date_time_start>="' . $data_start . '"')
                ->andWhere('checking.date_time_end<="' . $data_end . '"')
                ->count();

            $data = [
                'overallChecks' => $count_checkings,
                'overallInjunctions' => 0,
                'overallWorkStoppage' => 0,
                'violations' => [],
            ];

            /**
             * Список предписаний
             */
            $injunctions = Injunction::find()
                ->innerJoinWith('checking')                                                                        // проверки
                ->innerJoinWith('place')                                                                           // место
                ->joinWith('injunctionViolations.violation.violationType')
                ->joinWith('injunctionViolations.stopPbs')
                ->where($filters)
                ->andWhere('checking.date_time_start>="' . $data_start . '"')
                ->andWhere('checking.date_time_end<="' . $data_end . '"')
                ->andWhere(['injunction.kind_document_id' => [1, 3, 5]])                                                              // 1 - предписание, 3 - предписание РТН, 5 - рапорт
                ->all();

            /**
             * Заполнение выходного объекта
             */
            foreach ($injunctions as $injunction) {
                $data['overallInjunctions']++;

                $work_stoppage_status = false;
                if (isset($injunction->injunctionViolations)) {
                    foreach ($injunction->injunctionViolations as $injunctionViolation) {
                        if (!$work_stoppage_status) {
                            $work_stoppage_status = !empty($injunctionViolation->stopPbs);
                            $data['overallWorkStoppage']++;
                        }

                        if (!isset($data['violationTypes'][$injunctionViolation->violation->violationType->id])) {
                            $data['violationTypes'][$injunctionViolation->violation->violationType->id] = [
                                'violation_type_id' => $injunctionViolation->violation->violationType->id,
                                'violation_type_title' => $injunctionViolation->violation->violationType->title,
                                'count' => 0,
                            ];
                        }

                        $data['violationTypes'][$injunctionViolation->violation->violationType->id]['count']++;
                    }
                }
            }

            $result['data'] = $data;
            $result['requestId'] = $requestId;

            if (property_exists($post_dec, 'settings')) {
                $result['settings'] = $post_dec->settings;
            }

            (new AmicumWebSocketClient)->sendDataClient($result, $sourceClientId);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Закончил выполнять метод");
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetStatisticPabDashBoard - метод получения статистики по ПАБ
     * Входной объект:
     *      sourceClientId          - ключ Web Socket клиента
     *      requestId               - ключ запроса
     *      mine_id                 - ключ шахтного поля
     *      company_department_id   - ключ подразделения
     *      data_start              - дата начала выборки
     *      data_end                - дата окончания выборки
     * Выходной объект:
     *      violationTypes:                                             - нарушения по направлениям с расчетом количества
     *          {violation_type_id}:                                                 - ключ направления нарушения
     *              violation_type_id:      1,                                       - ключ направления нарушения
     *              violation_type_title:   "Противопожарная защита (ППЗ)",          - название направления нарушения
     *              count:                  19,                                      - количество нарушений по направлению нарушения
     * @param $data_post
     * @return null[]
     * @example 127.0.0.1/read-manager-amicum?controller=dashboard\pk&method=GetStatisticPabDashBoard&subscribe=&data={"sourceClientId":1,"requestId":1,"mine_id":290,"company_department_id":20037707,"data_start":"2019-05-01","data_end":"2022-05-31"}
     * @example http://127.0.0.1/read-manager-amicum?controller=dashboard\pk&method=GetStatisticPabDashBoard&subscribe=&data={%22sourceClientId%22:1,%22requestId%22:1,%22mine_id%22:290,%22company_department_id%22:20037707,%22data_start%22:%222019-05-01%22,%22data_end%22:%222022-05-31%22}
     * @example http://127.0.0.1/read-manager-amicum?controller=dashboard\pk&method=GetStatisticPabDashBoard&subscribe=&data={%22sourceClientId%22:1,%22requestId%22:1,%22mine_id%22:-1,%22company_department_id%22:20037707,%22data_start%22:%222019-05-01%22,%22data_end%22:%222021-05-31%22}
     */
    public static function GetStatisticPabDashBoard($data_post = null)
    {
        $result = array('data' => null, 'settings' => null, 'requestId' => null);                                                                                                // результирующий массив (если требуется)
        $log = new LogAmicumFront("GetStatisticPabDashBoard");
        try {

            $log->addLog("Начал выполнять метод");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'sourceClientId') ||
                !property_exists($post_dec, 'requestId') ||
                !property_exists($post_dec, 'mine_id') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'data_start') ||
                !property_exists($post_dec, 'data_end')) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $sourceClientId = $post_dec->sourceClientId;
            $requestId = $post_dec->requestId;
            $result['requestId'] = $requestId;
            $mine_id = $post_dec->mine_id;
            $company_department_id = $post_dec->company_department_id;
            $data_start = date("Y-m-d H:i:s", strtotime($post_dec->data_start));
            $data_end = date("Y-m-d H:i:s", strtotime($post_dec->data_end));
            $filters = [];

            if ($company_department_id > -1) {
                $response = DepartmentController::FindDepartment($company_department_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка получения вложенных департаментов: ' . $company_department_id);
                }
                // ВАЖНО!!!!! При расчете статистики, может быть расхождение
                // предписание может быть выписано на один участок, а ответственный может быть с другого участка!
                // Критично для ПАБ - если человек ушел на другой участок, то его ПАБ на новом участке отображаться не будет
                // в Ином случае, если сделать, то получиться, что ПАБ он заработал на новом переведенном участке
                // Иными словами нужно определиться, что мы контролируем - ПАБ как косяк человека, Или ПАБы как косяк руководителя
                // НО!!! по предписания - они закрепляется за участком, а не за ответственным
                // НО тут есть косяк!!! если предписание РТН, то оно выписывается на шахту, но не на участок! иметь это ввиду.
                $filters['checking.company_department_id'] = $response['Items'];
            }

            if ($mine_id > -1) {
                $filters['place.mine_id'] = $mine_id;
            }

            $data = [];

            /**
             * Список предписаний
             */
            $pabs = Injunction::find()
                ->innerJoinWith('checking')                                                                        // проверки
                ->innerJoinWith('place')                                                                           // место
                ->joinWith('injunctionViolations.violation.violationType')
                ->joinWith('injunctionViolations.stopPbs')
                ->where($filters)
                ->andWhere('checking.date_time_start>="' . $data_start . '"')
                ->andWhere('checking.date_time_end<="' . $data_end . '"')
                ->andWhere(['injunction.kind_document_id' => 2])                                                        // 2 - ПАБ
                ->all();

            /**
             * Заполнение выходного объекта
             */
            foreach ($pabs as $pab) {
                if (isset($pab->injunctionViolations)) {
                    foreach ($pab->injunctionViolations as $injunctionViolation) {
                        if (!isset($data['violationTypes'][$injunctionViolation->violation->id])) {
                            $data['violationTypes'][$injunctionViolation->violation->violationType->id] = [
                                'violation_type_id' => $injunctionViolation->violation->violationType->id,
                                'violation_type_title' => $injunctionViolation->violation->violationType->title,
                                'count' => 0,
                            ];
                        }

                        $data['violationTypes'][$injunctionViolation->violation->violationType->id]['count']++;
                    }
                }
            }

            $result['data'] = $data;
            $result['requestId'] = $requestId;

            if (property_exists($post_dec, 'settings')) {
                $result['settings'] = $post_dec->settings;
            }

            (new AmicumWebSocketClient)->sendDataClient($result, $sourceClientId);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Закончил выполнять метод");
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetStatisticNNDashBoard - метод получения статистики по нарушениям/несоответствиям
     * Входной объект:
     *      sourceClientId          - ключ Web Socket клиента
     *      requestId               - ключ запроса
     *      mine_id                 - ключ шахтного поля
     *      company_department_id   - ключ подразделения
     *      data_start              - дата начала выборки
     *      data_end                - дата окончания выборки
     * Выходной объект:
     *      violationTypes:                                             - нарушения по направлениям с расчетом количества
     *          {violation_type_id}:                                                 - ключ направления нарушения
     *              violation_type_id:      1,                                       - ключ направления нарушения
     *              violation_type_title:   "Противопожарная защита (ППЗ)",          - название направления нарушения
     *              count:                  19,                                      - количество нарушений по направлению нарушения
     * @param $data_post
     * @return null[]
     * @example 127.0.0.1/read-manager-amicum?controller=dashboard\pk&method=GetStatisticNNDashBoard&subscribe=&data={"sourceClientId":1,"requestId":1,"mine_id":290,"company_department_id":20037707,"data_start":"2019-05-01","data_end":"2022-05-31"}
     * @example http://127.0.0.1/read-manager-amicum?controller=dashboard\pk&method=GetStatisticNNDashBoard&subscribe=&data={%22sourceClientId%22:1,%22requestId%22:1,%22mine_id%22:290,%22company_department_id%22:20037707,%22data_start%22:%222019-05-01%22,%22data_end%22:%222022-05-31%22}
     * @example http://127.0.0.1/read-manager-amicum?controller=dashboard\pk&method=GetStatisticNNDashBoard&subscribe=&data={%22sourceClientId%22:1,%22requestId%22:1,%22mine_id%22:-1,%22company_department_id%22:20037707,%22data_start%22:%222019-05-01%22,%22data_end%22:%222021-05-31%22}
     */
    public static function GetStatisticNNDashBoard($data_post = null)
    {
        $result = array('data' => null, 'settings' => null, 'requestId' => null);                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("GetStatisticNNDashBoard");
        try {

            $log->addLog("Начал выполнять метод");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'sourceClientId') ||
                !property_exists($post_dec, 'requestId') ||
                !property_exists($post_dec, 'mine_id') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'data_start') ||
                !property_exists($post_dec, 'data_end')) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $sourceClientId = $post_dec->sourceClientId;
            $requestId = $post_dec->requestId;
            $result['requestId'] = $requestId;
            $mine_id = $post_dec->mine_id;
            $company_department_id = $post_dec->company_department_id;
            $data_start = date("Y-m-d H:i:s", strtotime($post_dec->data_start));
            $data_end = date("Y-m-d H:i:s", strtotime($post_dec->data_end));
            $filters = [];

            if ($company_department_id > -1) {
                $response = DepartmentController::FindDepartment($company_department_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка получения вложенных департаментов: ' . $company_department_id);
                }
                // ВАЖНО!!!!! При расчете статистики, может быть расхождение
                // предписание может быть выписано на один участок, а ответственный может быть с другого участка!
                // Критично для ПАБ - если человек ушел на другой участок, то его ПАБ на новом участке отображаться не будет
                // в Ином случае, если сделать, то получиться, что ПАБ он заработал на новом переведенном участке
                // Иными словами нужно определиться, что мы контролируем - ПАБ как косяк человека, Или ПАБы как косяк руководителя
                // НО!!! по предписания - они закрепляется за участком, а не за ответственным
                // НО тут есть косяк!!! если предписание РТН, то оно выписывается на шахту, но не на участок! иметь это ввиду.
                $filters['checking.company_department_id'] = $response['Items'];
            }

            if ($mine_id > -1) {
                $filters['place.mine_id'] = $mine_id;
            }

            $data = [];

            /**
             * Список предписаний
             */
            $nns = Injunction::find()
                ->innerJoinWith('checking')                                                                        // проверки
                ->innerJoinWith('place')                                                                           // место
                ->joinWith('injunctionViolations.violation.violationType')
                ->joinWith('injunctionViolations.stopPbs')
                ->where($filters)
                ->andWhere('checking.date_time_start>="' . $data_start . '"')
                ->andWhere('checking.date_time_end<="' . $data_end . '"')
                ->andWhere(['injunction.kind_document_id' => 4])                                                        // 2 - н/н
                ->all();

            /**
             * Заполнение выходного объекта
             */
            foreach ($nns as $nn) {
                if (isset($nn->injunctionViolations)) {
                    foreach ($nn->injunctionViolations as $injunctionViolation) {
                        if (!isset($data['violationTypes'][$injunctionViolation->violation->id])) {
                            $data['violationTypes'][$injunctionViolation->violation->violationType->id] = [
                                'violation_type_id' => $injunctionViolation->violation->violationType->id,
                                'violation_type_title' => $injunctionViolation->violation->violationType->title,
                                'count' => 0,
                            ];
                        }

                        $data['violationTypes'][$injunctionViolation->violation->violationType->id]['count']++;
                    }
                }
            }

            $result['data'] = $data;
            $result['requestId'] = $requestId;

            if (property_exists($post_dec, 'settings')) {
                $result['settings'] = $post_dec->settings;
            }

            (new AmicumWebSocketClient)->sendDataClient($result, $sourceClientId);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Закончил выполнять метод");
        return array_merge(['Items' => $result], $log->getLogAll());
    }
}
