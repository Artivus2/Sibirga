<?php

namespace frontend\controllers\industrial_safety;

use backend\controllers\Assistant as BackendAssistant;
use DateTime;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\handbooks\DepartmentController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Siz;
use frontend\models\SizStore;
use frontend\models\WorkerSiz;
use Throwable;
use Yii;
use yii\web\Controller;

class SizController extends Controller
{
    // GetStoreWorkerSiz                    - Получение данных СИЗ на складе
    // getJournalSiz                        - Метод получения данных о работниках и их СИЗах, относящихся к определенному департаменту и его нижестоящим подразделениям
    // GetSizMain                           - Метод получения главного объекта по СИЗ
    // SaveWorkerSiz                        - Сохранение привязки сиз к работника

    const SIZ_ISSUED = 64;
    const SIZ_EXTENDED = 65;


    /**
     * Входная структура:
     *
     *
     * Выходная структура:
     *
     * siz
     *      [siz_id]
     *          siz_id
     *          season_id
     *          season_title
     *          wear_period
     *          comment
     *          given_count
     *          unit_id
     *          [workers_siz]
     *              size
     *              worker_tabel_number
     *              worker_first_name
     *              worker_last_name
     *              worker_patronymic
     *              role
     *              - место работы
     *              give
     *
     *
     */

    /**
     * Метод GetStoreWorkerSiz() - Получение данных СИЗ на складе
     *
     * @param null $data_post
     * @return array
     * @package frontend\controllers\industrial_safety
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Siz&method=GetStoreWorkerSiz&subscribe=&data={%22company_department_id%22:802}
     *
     * Created date: on 18.07.2019 13:25
     */
    public static function GetStoreWorkerSiz($data_post = NULL)
    {
        $status = 1;                                                                                                        // Флаг успешного выполнения метода
        $warnings = array();                                                                                                // Массив предупреждений
        $errors = array();                                                                                                  // Массив ошибок
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetStoreWorkerSiz. Данные с фронта не получены');
            }
            $warnings[] = 'GetStoreWorkerSiz. Данные успешно переданы';
            $warnings[] = 'GetStoreWorkerSiz. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);
            // Декодируем входной массив данных
            $warnings[] = 'GetStoreWorkerSiz. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'company_department_id'))
            ) {
                throw new Exception('GetStoreWorkerSiz. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей
            $company_department_id = $post_dec->company_department_id;
            $sizs = Siz::find()
                ->joinWith('sizKind')
                ->joinWith('sizStores')
                ->where(['company_department_id' => $company_department_id])
                ->orderBy(['siz_store.fact_value' => SORT_DESC])
                ->limit(2000)
                ->all();

            foreach ($sizs as $siz) {
                $siz_kind_id = $siz->sizKind->id;
                $siz_kind_title = $siz->sizKind->title;
                $siz_id = $siz->id;
                $store_result[$siz_id]['siz_kind_id'] = $siz_kind_id;
                $store_result[$siz_id]['siz_kind_title'] = $siz_kind_title;
                $store_result[$siz_id]['siz_id'] = $siz_id;
                $store_result[$siz_id]['siz_title'] = $siz->title;
                $store_result[$siz_id]['company_department_id'] = $siz->sizStores[0]->company_department_id;
                if (isset($siz->sizStores[0])) {
                    $store_result[$siz_id]['plan_value'] = $siz->sizStores[0]->plan_value;
                } else {
                    $store_result[$siz_id]['plan_value'] = 0;
                }
                if (isset($siz->sizStores[0])) {
                    $store_result[$siz_id]['fact_value'] = $siz->sizStores[0]->fact_value;
                } else {
                    $store_result[$siz_id]['fact_value'] = 0;
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetStoreWorkerSiz. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        if (!isset($store_result) or !$store_result) {
            $store_result = (object)array();
        }

        return array('Items' => $store_result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GivenWorkerSiz() - Получение данных выданных СИЗ работникам
     * @param null $data_post
     * @return array
     * @package frontend\controllers\industrial_safety
     * @example
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 18.07.2019 13:25
     */
    public static function GivenWorkerSiz($data_post = NULL)
    {
        $status = 1;                                                                                                        // Флаг успешного выполнения метода
        $warnings = array();                                                                                                // Массив предупреждений
        $errors = array();                                                                                                  // Массив ошибок
        $given_siz_count = array();                                                                                   // Промежуточный результирующий массив
        try {
            if ($data_post !== NULL && $data_post !== '')                                                                   // Проверяем передана ли входная JSON строка
            {
                $warnings[] = 'GivenWorkerSiz. Данные успешно переданы';
                $warnings[] = 'GivenWorkerSiz. Входной массив данных' . $data_post;
            } else {
                throw new Exception('GivenWorkerSiz. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                            // Декодируем входной массив данных
            $warnings[] = 'GivenWorkerSiz. Декодировал входные параметры';
            if (
                property_exists($post_dec, 'monitoring_year') &&
                property_exists($post_dec, 'monitoring_month') &&
                property_exists($post_dec, 'company_department_id')
            )                                                             // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'GivenWorkerSiz. Данные с фронта получены';
            } else {
                throw new Exception('GivenWorkerSiz. Переданы некорректные входные параметры');
            }

            /******************** ПРОВЕРЯЕМ ПЕРЕДАННЫЕ ФИЛЬТРЫ ********************/


            /******************** ПОДСЧЕТ СУММЫ ВЫДАННЫХ СИЗ РАБОТНИКАМ  ********************/

            /******************** ПОДСЧЕТ СУММЫ КОЛИЧЕСТВА СИЗ ПОДЛЕЖАЩИХ ЗАМЕНЕ В БЛИЗЖАЙШЕМ МЕСЯЦЕ  ********************/


        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result = $given_siz_count;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * getJournalSiz - Метод получения данных о работниках и их СИЗах, относящихся к определенному департаменту и его нижестоящим подразделениям
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Siz&method=getJournalSiz&subscribe=&data={%22company_department_id%22:20028766,%22chosen_date%22:{%22date%22:%222019-09-30T17:00:00.000Z%22,%22year%22:2019,%22monthNumber%22:10,%22numberDays%22:31,%22monthTitle%22:%22%D0%9E%D0%BA%D1%82%D1%8F%D0%B1%D1%80%D1%8C%22}}
     */
    public static function getJournalSiz($data_post = NULL)
    {
        $errors = array();
        $status = 1;
        $warnings = array();
        $result = array();
        $list_siz = array();
        $siz_by_company_department = array();
        $siz_by_workers = array();
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('getObjectSiz. Данные с фронта не получены');
            }
            $warnings[] = 'getObjectSiz. Данные успешно переданы';
            $warnings[] = 'getObjectSiz. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);
            // Декодируем входной массив данных
            $warnings[] = 'getObjectSiz. Декодировал входные параметры';
            if (
                !(
                    property_exists($post_dec, 'company_department_id') and
                    property_exists($post_dec, 'chosen_date')
                )
            ) {
                throw new Exception('getObjectSiz. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей
            $company_department_id = $post_dec->company_department_id;
            $chosen_date = $post_dec->chosen_date;
            $warnings[] = 'getObjectSiz. Данные с фронта получены';
            $count_data[] = $company_department_id;
            $new_upper = $company_department_id;                                                                                   // список компаний по которым будем исскать нижестоящие
            /****************** Ищем все нижестоящие подразделения ******************/
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception('GetJournalInquiry. Ошибка получения вложенных департаментов' . $company_department_id);
            }

            $warnings[] = $chosen_date;
            $count_day = cal_days_in_month(CAL_GREGORIAN, $chosen_date->monthNumber, $chosen_date->year);                                  // количество дней в месяце
            $date_time = date('Y-m-d', strtotime($chosen_date->year . '-' . $chosen_date->monthNumber . '-' . $count_day));                 // период за месяц до конца месяца

            // получаем список сизов с работниками их департаментами
            $list_siz = WorkerSiz::find()
                ->joinWith('workerSizStatuses')
                ->joinWith('worker')
                ->joinWith('worker.employee')
                ->joinWith('worker.position')
                ->joinWith('worker.companyDepartment')
                ->joinWith('worker.companyDepartment.company')
                ->joinWith('siz')
                ->joinWith('siz.unit')
                ->joinWith('siz.season')
                ->joinWith('siz.sizKind')
                ->joinWith('siz.sizSubgroup')
                ->joinWith('siz.sizSubgroup.sizGroup')
//                ->innerJoin('document','document.id=siz.document_id')
                ->where(['in', 'worker.company_department_id', $company_departments])
                ->andWhere(['or',
                        ['and',
                            'date_issue<="' . $date_time . '"',
                            'date_write_off>="' . $date_time . '"'
                        ],
                        ['and',
                            'worker_siz.date_issue<="' . $date_time . '"',
                            'worker_siz.status_id in (64, 65)'
                        ]
                    ]
                )
                ->all();
            $warnings[] = "getObjectSiz. Список СИЗ из БД";
//            $warnings[] = $list_siz;
            if ($list_siz) {
                foreach ($list_siz as $worker_siz) {
                    $company_department_id = $worker_siz->worker->company_department_id;
                    $worker_id = $worker_siz->worker_id;
                    $worker_siz_id = $worker_siz->id;
                    $siz_by_workers[$company_department_id]['company_department_id'] = $company_department_id;
                    $siz_by_workers[$company_department_id]['company_title'] = $worker_siz->worker->companyDepartment->company->title;

                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['worker_id'] = $worker_id;
                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['position_id'] = $worker_siz->worker->position_id;
                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['worker_tabel_number'] = $worker_siz->worker->tabel_number;
                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['position_title'] = $worker_siz->worker->position->title;
                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['last_name'] = $worker_siz->worker->employee->last_name;
                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['first_name'] = $worker_siz->worker->employee->first_name;
                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['patronymic'] = $worker_siz->worker->employee->patronymic;

                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['worker_siz_id'] = $worker_siz_id;
                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['size'] = $worker_siz->size;
                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['count_issued_siz'] = $worker_siz->count_issued_siz;
                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['date_issue'] = $worker_siz->date_issue;
                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['date_write_off'] = $worker_siz->date_write_off;
                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['date_issue_format'] = date("d.m.Y", strtotime($worker_siz->date_issue));
                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['date_write_off_format'] = date("d.m.Y", strtotime($worker_siz->date_write_off));
                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['status_id'] = $worker_siz->status_id;

                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['siz_id'] = $worker_siz->siz_id;
                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['siz_title'] = $worker_siz->siz->title;
                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['unit_siz_title'] = $worker_siz->siz->unit->short;
                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['wear_period'] = $worker_siz->siz->wear_period;
                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['season_title'] = $worker_siz->siz->season->title;
                    $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['kind_title'] = $worker_siz->siz->sizKind->title;
                    if ($worker_siz->siz->sizSubgroup) {
                        $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['siz_subgroup_id'] = $worker_siz->siz->sizSubgroup->id;
                        $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['siz_subgroup_title'] = $worker_siz->siz->sizSubgroup->title;
                        $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['siz_group_id'] = $worker_siz->siz->sizSubgroup->sizGroup->id;
                        $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['siz_group_title'] = $worker_siz->siz->sizSubgroup->sizGroup->title;
                    } else {
                        $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['siz_subgroup_id'] = null;
                        $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['siz_subgroup_title'] = "";
                        $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['siz_group_id'] = null;
                        $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['siz_group_title'] = "";
                    }
                    foreach ($worker_siz->workerSizStatuses as $worker_siz_status) {
                        $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['statuses']['worker_siz_status_id'] = $worker_siz_status->id;
                        $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['statuses']['date'] = $worker_siz_status->date;
                        $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['statuses']['comment'] = $worker_siz_status->comment;
                        $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['statuses']['percentage_wear'] = $worker_siz_status->percentage_wear;
                        $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['statuses']['status_id'] = $worker_siz_status->status_id;

                    }
                    if (!isset($siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['statuses'])) {
                        $siz_by_workers[$company_department_id]['worker_list'][$worker_id]['siz_list'][$worker_siz_id]['statuses'] = (object)array();
                    }
//                    $result[$siz['company_department_id']]['worker_list'][$siz['worker_id']]['siz_list'][$siz['siz_id']]['doc_title'] = $siz['doc_title'];
                }
                $count_issued = array();
                //******************** Массив обратный предидущему перебору ********************/
                foreach ($list_siz as $worker_siz) {
                    $company_department_id = $worker_siz->worker->company_department_id;
                    $worker_id = $worker_siz->worker_id;
                    $worker_siz_id = $worker_siz->id;
                    $siz_id = $worker_siz->siz->id;
                    if (!isset($count_issued[$siz_id])) {
                        $count_issued[$siz_id] = 1;
                    }
                    $siz_by_company_department[$company_department_id]['company_department_id'] = $company_department_id;
                    $siz_by_company_department[$company_department_id]['company_title'] = $worker_siz->worker->companyDepartment->company->title;
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['siz_id'] = $siz_id;
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['siz_title'] = $worker_siz->siz->title;
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['comment'] = $worker_siz->siz->comment;
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['unit_siz_title'] = $worker_siz->siz->unit->short;
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['kind_title'] = $worker_siz->siz->sizKind->title;
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['season_title'] = $worker_siz->siz->season->title;
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['wear_period'] = $worker_siz->siz->wear_period;
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['count_issued'] = $count_issued[$siz_id];

                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['document_title'] = '-';//Заглушка
                    if ($worker_siz->siz->sizSubgroup) {
                        $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['siz_subgroup_id'] = $worker_siz->siz->sizSubgroup->id;
                        $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['siz_subgroup_title'] = $worker_siz->siz->sizSubgroup->title;
                        $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['siz_group_id'] = $worker_siz->siz->sizSubgroup->sizGroup->id;
                        $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['siz_group_title'] = $worker_siz->siz->sizSubgroup->sizGroup->title;
                    } else {
                        $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['siz_subgroup_id'] = null;
                        $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['siz_subgroup_title'] = "";
                        $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['siz_group_id'] = null;
                        $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['siz_group_title'] = "";
                    }
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['worker_siz'][$worker_siz_id]['worker_id'] = $worker_id;
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['worker_siz'][$worker_siz_id]['worker_siz_id'] = $worker_siz_id;
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['worker_siz'][$worker_siz_id]['position_title'] = $worker_siz->worker->position->title;
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['worker_siz'][$worker_siz_id]['worker_tabel_number'] = $worker_siz->worker->tabel_number;
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['worker_siz'][$worker_siz_id]['last_name'] = $worker_siz->worker->employee->last_name;
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['worker_siz'][$worker_siz_id]['first_name'] = $worker_siz->worker->employee->first_name;
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['worker_siz'][$worker_siz_id]['patronymic'] = $worker_siz->worker->employee->patronymic;
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['worker_siz'][$worker_siz_id]['size'] = $worker_siz->size;
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['worker_siz'][$worker_siz_id]['count_issued_siz'] = $worker_siz->count_issued_siz;
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['worker_siz'][$worker_siz_id]['date_issue'] = $worker_siz->date_issue;
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['worker_siz'][$worker_siz_id]['date_write_off'] = $worker_siz->date_write_off;
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['worker_siz'][$worker_siz_id]['date_issue_format'] = date('d.m.Y', strtotime($worker_siz->date_issue));
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['worker_siz'][$worker_siz_id]['date_write_off_format'] = date('d.m.Y', strtotime($worker_siz->date_write_off));
                    $siz_by_company_department[$company_department_id]['siz_list'][$siz_id]['worker_siz'][$worker_siz_id]['status_id'] = $worker_siz->status_id;
                    $count_issued[$siz_id]++;
                }
            } else {
                $warnings[] = "getObjectSiz. Список СИЗ пуст для данного подразделенеия";
                $siz_by_company_department = (object)array();
                $siz_by_workers = (object)array();
                $company_departments = (object)array();
            }
            $warnings[] = "getObjectSiz. Окончил выполнять метод";
        } catch (Throwable $ex) {
            $errors[] = 'getObjectSiz. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        $result['siz_by_siz'] = $siz_by_company_department;
        $result['siz_by_workers'] = $siz_by_workers;
        $result['company_departments'] = $company_departments;

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * GetSizMain - Метод получения главного объекта по СИЗ
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Siz&method=GetSizMain&subscribe=&data={%22company_department_id%22:%20%224029937%22,%22chosen_date%22:%2244%22}
     */
    public static function GetSizMain($data_post = NULL)
    {
        $errors = array();
        $status = 1;
        $warnings = array();
        $result = array();
        $company_departments = array();

        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetSizMain. Данные с фронта не получены');
            }
            $warnings[] = 'GetSizMain. Данные успешно переданы';
            $warnings[] = 'GetSizMain. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);
            // Декодируем входной массив данных
            $warnings[] = 'GetSizMain. Декодировал входные параметры';
            if (
                !(
                    property_exists($post_dec, 'company_department_id') and
                    property_exists($post_dec, 'chosen_date')
                )
            ) {
                throw new Exception('GetSizMain. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей
            $chosen_date = $post_dec->chosen_date;


            $count_day = cal_days_in_month(CAL_GREGORIAN, $chosen_date->monthNumber, $chosen_date->year);                                  // количество дней в месяце
            $date = date('Y-m-d', strtotime($chosen_date->year . '-' . $chosen_date->monthNumber . '-' . $count_day));                 // период за месяц до конца месяца

            $warnings[] = $date;
            $response = self::getJournalSiz($data_post);
            if ($response['status'] == 1) {
                $result['journalSiz'] = $response['Items']['siz_by_workers'];
                $result['journalSiz_by_siz'] = $response['Items']['siz_by_siz'];
                $company_departments = $response['Items']['company_departments'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception('GetSizMain. Ошибка получения главного списка СИЗ');
            }
            $warnings[] = 'GetSizMain. Данные с фронта получены';
            /******************** БЛОК ВЫДАННЫХ СИЗов ********************/
            $siz_given = self::SizGiven($date, $company_departments);
            if ($siz_given['status'] == 1) {
                $result['statisticForPeople']['siz_given'] = $siz_given['Items'];
            } else {
                $errors[] = $siz_given['errors'];
                throw new Exception('GetSizMain. Ошибка получение блока выданных сизов');
            }
            $warnings[] = $siz_given['warnings'];

            /******************** БЛОК СИЗов ПОДЛЕЖАЩИХ ЗАМЕНЕ В БЛИЖАЙШИЙ МЕСЯЦ ********************/
            $siz_need_to_remove = self::SizToReplacement($company_departments);
            if ($siz_need_to_remove['status'] == 1) {
                $result['statisticForPeople']['siz_need_remove'] = $siz_need_to_remove['Items'];
            } else {
                $errors[] = $siz_need_to_remove['errors'];
                throw new Exception('GetSizMain. Ошибка получение блока СИЗов подлежащих замене ближайший месяц');
            }
            $warnings[] = $siz_need_to_remove['warnings'];

            /******************** БЛОК СИЗов В НАЛИЧИИ НА СКЛАДЕ ********************/
            $siz_on_store = self::GetSizOnStore($company_departments);
            if ($siz_on_store['status'] == 1) {
                $result['statisticForStore']['siz_on_store'] = $siz_on_store['Items'];
            } else {
                $errors[] = $siz_on_store['errors'];
                throw new Exception('GetSizMain. Ошибка получение блока СИЗов в наличии на складе');
            }
            $warnings[] = $siz_on_store['warnings'];

            /******************** БЛОК СИЗов КОТОРЫЕ НЕОБХОДИМО ЗАКУПИТЬ В БЛИЖАЙШИЙ МЕСЯЦ ********************/
            $siz_need_by_to_store = self::GetSizNeedByToStore($company_departments);
            if ($siz_need_by_to_store['status'] == 1) {
                $result['statisticForStore']['siz_need_by_to_store'] = $siz_need_by_to_store['Items'];
            } else {
                $errors[] = $siz_need_by_to_store['errors'];
                throw new Exception('GetSizMain. Ошибка получение блока необходимо закупить СИЗ в ближайший месяц');
            }
            $warnings[] = $siz_need_by_to_store['warnings'];

            /******************** БЛОК СТАТИСТИКИ ПО СИЗам ********************/
            $siz_statistic = self::GetStatisticSiz($date,$company_departments);
            $warnings[] = $siz_statistic['warnings'];
            if ($siz_statistic['status'] == 1) {
                $result['statisticForStore']['siz_statistic'] = $siz_statistic['Items'];
            } else {
                $errors[] = $siz_statistic['errors'];
                throw new Exception('GetSizMain. Ошибка получение блока статистика СИЗ');
            }
            $warnings[] = $siz_statistic['warnings'];

            $warnings[] = "GetSizMain. Окончил выполнять метод";
        } catch (Throwable $ex) {
            $errors[] = 'GetSizMain. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        if (!isset($result['journalSiz'])) {
            $result['journalSiz'] = (object)array();
        }

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SizGivenAndReplacement() - количество СИЗ выданно работникам
     * @param $company_department_id
     * @param $date_time
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 07.10.2019 10:47
     */
    public static function SizGiven($date_time, $company_departments)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = 0;
        $warnings[] = 'SizGivenAndReplacement. Начало метода';
        try {
            $warnings[] = 'SizGivenAndReplacement. Получение списка выданых СИЗов';
            $count_given_siz = WorkerSiz::find()
                ->select('count(worker_siz.id) as count_worker_siz')
                ->innerJoin('worker', 'worker_siz.worker_id = worker.id')
                ->where(['in', 'worker.company_department_id', $company_departments])
                ->andWhere(['or',
                        ['and',
//                            'month(date_issue)<=' . (int)date("m", strtotime($date_time)),
//                            'year(date_issue)<=' . (int)date("Y", strtotime($date_time)),
//                            'month(date_write_off)>=' . (int)date("m", strtotime($date_time)),
//                            'year(date_write_off)>=' . (int)date("Y", strtotime($date_time))
                            'date_issue<="' . $date_time . '"',
                            'date_write_off>="' . $date_time . '"'
                        ],
                        ['and',
                            'date_issue<="' . $date_time . '"',
                            'worker_siz.status_id in (64, 65)'
                        ]
                    ]
                )
                ->scalar();
            if ($count_given_siz != false) {
                $result = (int)$count_given_siz;
                $warnings[] = 'SizGivenAndReplacement. Количество выданных СИЗов записано';
            } else {
                $result = 0;
                $warnings[] = 'SizGivenAndReplacement. Нет выданных СИЗов';
            }

        } catch (Throwable $exception) {
            $errors[] = 'SizGiven. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SizGivenAndReplacement. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SizReplacement() - количество СИЗов необходимых к замене в ближайщий месяц
     * @param $company_department_id - идентификатор участка
     * @return array - массив со следующей структурой: siz_need_remove:
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 07.10.2019 10:48
     */
    public static function SizToReplacement($company_departments)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = 0;
        $date_now = date('Y-m-d', strtotime(BackendAssistant::GetDateNow()));
        $warnings[] = 'SizReplacement. Начало метода';
        try {
            $warnings[] = 'SizGivenAndReplacement. Получение количества СИЗов со сроком замены ближайший месяц';
            $count_replacement_siz = WorkerSiz::find()
                ->select([
                    'worker_siz.date_write_off as date_write_off',
                    "datediff(worker_siz.date_write_off,'{$date_now}') as diff_date",
                    'siz.id as siz_id',
                    'worker.id as worker_id',
                    'worker_siz.id as worker_siz_id',
                    'worker_siz.status_id as worker_siz_status_id'
                ])
                ->innerJoin('worker', 'worker_siz.worker_id = worker.id')
                ->innerJoin('siz', 'siz.id = worker_siz.siz_id')
                ->where(['in', 'worker.company_department_id', $company_departments])
                ->andWhere(['IN', 'worker_siz.status_id', [self::SIZ_ISSUED, self::SIZ_EXTENDED]])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date_now],
                    ['is', 'worker.date_end', null]
                ])
                ->andWhere(['!=', 'siz.wear_period', 0])
                ->andWhere(['!=', 'siz.wear_period', 36])
                ->having(['<', 'diff_date', 0])
                ->asArray()
                ->count();
            $result = (int)$count_replacement_siz;
            $warnings[] = 'SizGivenAndReplacement. Количество СИЗов для замены успешно записано';
        } catch (Throwable $exception) {
            $errors[] = 'SizReplacement. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SizReplacement. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetSizOnStore() - СИЗы на складе
     * @param $company_department_id - идентификатор участка
     * @return array - масив со следующей структурой: siz_on_store:
     *                                                siz_need_by_to_store:
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 07.10.2019 10:44
     */
    public static function GetSizOnStore($company_departments)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = 0;
        $warnings[] = 'GetSizOnStore. Начало метода';
        try {
            $warnings[] = 'GetSizOnStore. Получение СИЗов в наличии на складе';
            $siz_on_store = SizStore::find()
                ->where(['in', 'company_department_id', $company_departments])
                ->andWhere(['!=', 'fact_value', 0])
                ->count();
            $result = (int)$siz_on_store;

        } catch (Throwable $exception) {
            $errors[] = 'GetSizOnStore. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetSizOnStore. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetSizNeedByToStore() - получение количества СИЗов котоыре необходимо закупить в ближайший месяц
     * @param $company_department_id -идентификатор участка
     * @return array - массив со следующей структурой: siz_need_by_to_store:
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 07.10.2019 11:01
     */
    public static function GetSizNeedByToStore($company_departments)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = 0;
        $warnings[] = 'GetSizNeedByToStore. Начало метода';
        try {
            $warnings[] = 'GetSizOnStore. Получение СИЗов которые необходимо закупить в ближайший месяц';
            $siz_need_by_to_store = SizStore::find()
                ->where(['in', 'company_department_id', $company_departments])
                ->andWhere(['=', 'fact_value', 0])
                ->count();
            $result = (int)$siz_need_by_to_store;
        } catch (Throwable $exception) {
            $errors[] = 'GetSizNeedByToStore. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetSizNeedByToStore. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetStatisticSiz() - Статистика по СИЗам
     * @param null $company_department_id - идентификатор участка по которому нажна статистика
     * @return array - массив выходных данных со следующей структурой: [siz_id]
     *                                                                     siz_id:
     *                                                                     siz_title:
     *                                                                     count_all_siz:
     *                                                                     count_siz_yellow:
     *                                                                     count_siz_red:
     *                                                                     count_siz_normal:
     *
     * @package frontend\controllers\industrial_safety
     *
     * Входные обязательные параметры:
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 08.10.2019 9:47
     */
    public static function GetStatisticSiz($date_time,$company_departments)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $siz_counters = array();
        $interest_result = array();
        $date_now = date('Y-m-d', strtotime(BackendAssistant::GetDateTimeNow()));
        $warnings[] = 'GetStatisticSiz. Начало метода';
        try {
            $warnings[] = 'GetStatisticSiz. Получение списка всех СИЗов';
            $get_all = Siz::find()
                ->select([
                    'worker_siz.worker_id as worker_id',
                    'siz.id as siz_id',
                    'siz.wear_period as wear_period',
                    'siz.title as siz_title',
                    'worker_siz.date_write_off as date_write_off',
                    "datediff(worker_siz.date_write_off,'{$date_now}') as diff_date"
                ])
                ->innerJoin('worker_siz', 'worker_siz.siz_id = siz.id')
                ->innerJoin('worker', 'worker_siz.worker_id = worker.id')
                ->where(['in', 'worker.company_department_id', $company_departments])
                ->andWhere(['or',
                        ['and',
                            'worker_siz.date_issue<="' . $date_time . '"',
                            'worker_siz.date_write_off>="' . $date_time . '"'
                        ],
                        ['and',
                            'worker_siz.date_issue<="' . $date_time . '"',
                            'worker_siz.status_id in (64, 65)'
                        ]
                    ]
                )
                ->asArray()
                ->all();
            $warnings[] = 'GetStatisticSiz. Перебор с целью формирования количества по каждому пункту';
            $warnings[] = $get_all;

            foreach ($get_all as $siz) {
                $siz_counters[$siz['siz_id']]['siz_id'] = $siz['siz_id'];
                $siz_counters[$siz['siz_id']]['siz_title'] = $siz['siz_title'];
                $siz_counters[$siz['siz_id']]['wear_period'] = $siz['wear_period'];

                if (isset($siz_counters[$siz['siz_id']]['count_all_siz'])) {
                    $siz_counters[$siz['siz_id']]['count_all_siz']++;
                } else {
                    $siz_counters[$siz['siz_id']]['count_all_siz'] = 1;
                }

                if (!isset($siz_counters[$siz['siz_id']]['count_siz_yellow'])) {
                    $siz_counters[$siz['siz_id']]['count_siz_yellow'] = 0;
                }
                if (!isset($siz_counters[$siz['siz_id']]['count_siz_red'])) {
                    $siz_counters[$siz['siz_id']]['count_siz_red'] = 0;
                }
                if (!isset($siz_counters[$siz['siz_id']]['count_siz_normal'])) {
                    $siz_counters[$siz['siz_id']]['count_siz_normal'] = 0;
                }

                // предупредительная сигнализация
                // аварийная сигнализация
                // нормальное состояние
                if ($siz['diff_date'] <= 30 and $siz['diff_date'] >= 0 and $siz['wear_period'] != 36 and $siz['wear_period'] != 0) {
                    $siz_counters[$siz['siz_id']]['count_siz_yellow']++;
                } elseif ($siz['diff_date'] < 0 and $siz['wear_period'] != 36 and $siz['wear_period'] != 0) {
                    $siz_counters[$siz['siz_id']]['count_siz_red']++;
                } elseif ($siz['diff_date'] > 30 or $siz['wear_period'] == 36 or $siz['wear_period'] == 0) {
                    $siz_counters[$siz['siz_id']]['count_siz_normal']++;
                }
            }

            $warnings[] = 'GetStatisticSiz. Перебор с целью формирования количества по каждому пункту';
            foreach ($siz_counters as $siz_counter) {
                $interest_result[$siz_counter['siz_id']]['siz_id'] = $siz_counter['siz_id'];
                $interest_result[$siz_counter['siz_id']]['siz_title'] = $siz_counter['siz_title'];
                $interest_result[$siz_counter['siz_id']]['wear_period'] = $siz_counter['wear_period'];
                $interest_result[$siz_counter['siz_id']]['count_all_siz'] = $siz_counter['count_all_siz'];
                $interest_result[$siz_counter['siz_id']]['percent_yellow'] = round(($siz_counter['count_siz_yellow'] / $siz_counter['count_all_siz']) * 100);
                $interest_result[$siz_counter['siz_id']]['percent_red'] = round(($siz_counter['count_siz_red'] / $siz_counter['count_all_siz']) * 100);
                $interest_result[$siz_counter['siz_id']]['percent_normal'] = round(($siz_counter['count_siz_normal'] / $siz_counter['count_all_siz']) * 100);
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetStatisticSiz. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $result = $interest_result;
        if (empty($result)) {
            $result = (object)array();
        }
        $warnings[] = 'GetStatisticSiz. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SaveWorkerSiz() - Сохранение привязки сиз к работника
     * входной объект:
     *  worker_sizs:        - объект на сохранение
     *      worker_id                   - ключ работника
     *      company_department_id       - ключ подразделения
     *      sizs:
     *          []
     *              worker_siz_id           - ключ привязки сиз к работнику
     *              siz_id                  - ключ сиз
     *              size                    - размер сиз
     *              count_issued_siz        - количество продлений сиз
     *              date_issue              - дата выдачи сиз
     *              date_write_off          - дата списания сиз
     *              status_id               - статус сиз
     *              date_return             - дата возврата сиз
     * @example 127.0.0.1/read-manager-amicum?controller=industrial_safety\Siz&method=SaveWorkerSiz&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveWorkerSiz($data_post = NULL)
    {
        $log = new LogAmicumFront("SaveWorkerSiz");

        $siz_statistic_for_store['siz_statistic'] = (object)array();
        $siz_statistic_for_store['siz_need_by_to_store'] = 0;
        $siz_statistic_for_store['siz_on_store'] = 0;

        $siz_statistic_for_people['siz_given'] = 0;
        $siz_statistic_for_people['siz_need_remove'] = 0;
        $result = [];
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'worker_sizs') or
                !property_exists($post_dec, 'statistic_chosen_date') or
                !property_exists($post_dec, 'statistic_company_department_id')
            )                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $worker_siz_status = [];

            $statistic_company_department_id = $post_dec->statistic_company_department_id;
            $chosen_date = $post_dec->statistic_chosen_date;
            $worker_sizs_post = $post_dec->worker_sizs;
            $worker_id = $worker_sizs_post->worker_id;

            $count_day = cal_days_in_month(CAL_GREGORIAN, $chosen_date->monthNumber, $chosen_date->year);                                  // количество дней в месяце
            $date = date('Y-m-d', strtotime($chosen_date->year . '-' . $chosen_date->monthNumber . '-' . $count_day));                 // период за месяц до конца месяца

            if (property_exists($worker_sizs_post, 'sizs')) {

                $handbook_siz = Siz::find()->indexBy('id')->all();

                foreach ($worker_sizs_post->sizs as $key => $siz) {
                    if ($siz->status == "del") {
                        WorkerSiz::deleteAll(['id' => $siz->worker_siz_id]);
//                        unset($post_dec->worker_sizs->sizs[$key]);
                    } else if ($siz->status == "add" or $siz->status == "edit") {
                        if ($siz->status == "add") {
                            $new_worker_siz = new WorkerSiz();
                        } else {
                            $new_worker_siz = WorkerSiz::findOne(['id' => $siz->worker_siz_id]);
                        }
                        $new_worker_siz->worker_id = $worker_id;
                        $new_worker_siz->company_department_id = $worker_sizs_post->company_department_id;
                        $new_worker_siz->siz_id = $siz->siz_id;
                        $new_worker_siz->size = $siz->size;
                        $new_worker_siz->count_issued_siz = $siz->count_issued_siz;
                        $new_worker_siz->date_issue = date("Y-m-d H:i:s", strtotime($siz->date_issue));
                        $new_worker_siz->date_write_off = date("Y-m-d H:i:s", strtotime($siz->date_write_off));
                        $new_worker_siz->date_return = date("Y-m-d H:i:s", strtotime($siz->date_return));
                        $new_worker_siz->status_id = $siz->status_id;

                        if (!$new_worker_siz->save()) {
                            $log->addData($new_worker_siz->errors, '$new_worker_siz->errors', __LINE__);
                            throw new Exception("Ошибка сохранения модели привязки сиз к работнику WorkerSiz");
                        }

                        $new_worker_siz->refresh();
                        $worker_siz_id = $new_worker_siz->id;

                        if (isset($handbook_siz[$siz->siz_id]) and $handbook_siz[$siz->siz_id]['wear_period']) {
                            $date_start_wear = new DateTime($siz->date_issue);
                            $date_end_wear = new DateTime($siz->date_write_off);
                            $difference = $date_start_wear->diff($date_end_wear);
                            $percentage_wear = (int)((double)$difference->format('%y.%m') / $handbook_siz[$siz->siz_id]['wear_period']) * 100;

                            $worker_siz_status[] = array(
                                'worker_siz_id' => $worker_siz_id,
                                'date' => Assistant::GetDateTimeNow(),
                                'comment' => null,
                                'percentage_wear' => $percentage_wear,
                                'status_id' => $siz->status_id,
                            );
                        }

                        $post_dec->worker_sizs->sizs[$key]->worker_siz_id = $worker_siz_id;
                        $post_dec->worker_sizs->sizs[$key]->date_write_off_format = date("d.m.Y", strtotime($siz->date_write_off));
                        $post_dec->worker_sizs->sizs[$key]->date_issue_format = date("d.m.Y", strtotime($siz->date_issue));
                        $post_dec->worker_sizs->sizs[$key]->date_return_format = date("d.m.Y", strtotime($siz->date_return));
                        $post_dec->worker_sizs->sizs[$key]->status = "";
                    }
                }

                if (!empty($worker_siz_status)) {
                    $insert_worker_siz_status = Yii::$app->db_amicum2->queryBuilder->batchInsert('worker_siz_status', ['worker_siz_id', 'date', 'comment', 'percentage_wear', 'status_id'], $worker_siz_status);
                    $count_insert_param_val = Yii::$app->db_amicum2->createCommand($insert_worker_siz_status)->execute();
                    $log->addData($count_insert_param_val, "Вставил в БД данных");
                }

                $response = self::GetStatisticSiz($date,[$statistic_company_department_id]);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка при получении блока статистика СИЗ');
                }
                $siz_statistic_for_store['siz_statistic'] = $response['Items'];

                $response = self::GetSizNeedByToStore([$statistic_company_department_id]);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка при получении статистики СИЗ необходимо закупить СИЗ в ближайший месяц');
                }
                $siz_statistic_for_store['siz_need_by_to_store'] = $response['Items'];

                $response = self::GetSizOnStore([$statistic_company_department_id]);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка при получении статистики СИЗ в наличии на складе');
                }
                $siz_statistic_for_store['siz_on_store'] = $response['Items'];

                $response = self::SizToReplacement([$statistic_company_department_id]);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка при получении статистики по СИЗ замены');
                }
                $siz_statistic_for_people['siz_need_remove'] = $response['Items'];

                $response = self::SizGiven($date, [$statistic_company_department_id]);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка при получении статистики по выданным СИЗ');
                }
                $siz_statistic_for_people['siz_given'] = $response['Items'];
            }

            $result = array(
                'siz_for_save' => $post_dec,
                'statisticForStore' => $siz_statistic_for_store,
                'statisticForPeople' => $siz_statistic_for_people
            );
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}
