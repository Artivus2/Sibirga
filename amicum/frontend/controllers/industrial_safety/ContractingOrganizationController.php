<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers\industrial_safety;

use backend\controllers\Assistant as BackendAssistant;
use backend\controllers\cachemanagers\WorkerCacheController;
use backend\controllers\const_amicum\DepartmentTypeEnum;
use backend\controllers\sms\SmsSender;
use backend\controllers\WorkerMainController;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\HandbookCachedController;
use frontend\controllers\handbooks\HandbookEmployeeController;
use frontend\models\AccessCheck;
use frontend\models\Attachment;
use frontend\models\CheckKnowledge;
use frontend\models\Company;
use frontend\models\CompanyDepartment;
use frontend\models\CompanyDepartmentAttachment;
use frontend\models\CompanyDepartmentInfo;
use frontend\models\CompanyDepartmentRoute;
use frontend\models\ContractingOrganization;
use frontend\models\ContractorCompany;
use frontend\models\Employee;
use frontend\models\Injunction;
use frontend\models\OperationWorker;
use frontend\models\PlaceCompanyDepartment;
use frontend\models\ReasonCheckKnowledge;
use frontend\models\ShiftDepartment;
use frontend\models\ShiftMine;
use frontend\models\ShiftWorker;
use frontend\models\TypicalObject;
use frontend\models\Worker;
use frontend\models\WorkerObject;
use frontend\models\WorkerParameter;
use frontend\models\WorkerParameterHandbookValue;
use Throwable;
use Yii;
use yii\db\Query;

class ContractingOrganizationController extends \yii\web\Controller
{
    // GetContractorCompany                 - Метод получения справочника компаний подрядчиков
    // GetReasonCheckKnowledge              - Метод получения справочнка причин проверки знаний
    // GetContractingOrganization           - Метод получения данных для таблицы "Подрядные организации"
    // SaveContractingOrganization          - Метод сохранение данных проверки знаний подрядной организацией
    // DeleteContractingOrganization        - Метод удаления проверки знаний подрядной организацией
    // SaveContractorCompany                - Метод сохранения компании подрядчика в справочник
    // SaveReasonCheckKnowledge             - Метод сохранения причины проверки знаний в справочник
    // SaveCompanyDepartmentInfo            - Сохранение информации о подрядной организации
    // SavingWorker                         - Метод сохранение работника и возврат нужной структуры
    // GetContractingCompany                - Получение информации о компании подрядчике и проверке знаний работников
    // SaveCompanyDepartmentInfo            - Сохранение информации о подрядной организации

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetContractorCompany() - Метод получения справочника компаний подрядчиков
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\ContractingOrganization&method=GetContractorCompany&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 12.12.2019 10:49
     */
    public static function GetContractorCompany()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetContractorCompany. Начало метода';
        try {
            $result = ContractorCompany::find()
                ->select(['id', 'title'])
                ->indexBy('id')
                ->asArray()
                ->all();
        } catch (Throwable $exception) {
            $errors[] = 'GetContractorCompany. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetContractorCompany. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetReasonCheckKnowledge() - Метод получения справочнка причин проверки знаний
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\ContractingOrganization&method=GetReasonCheckKnowledge&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 12.12.2019 10:49
     */
    public static function GetReasonCheckKnowledge()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetReasonCheckKnowledge. Начало метода';
        try {
            $result = ReasonCheckKnowledge::find()
                ->select(['id', 'title'])
                ->indexBy('id')
                ->asArray()
                ->all();
            if (empty($result)) {
                $result = (object)array();
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetReasonCheckKnowledge. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetReasonCheckKnowledge. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetContractingOrganization() - Метод получения данных для таблицы "Подрядные организации"
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\ContractingOrganization&method=GetContractingOrganization&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 12.12.2019 11:24
     */
    public static function GetContractingOrganization()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetContractingOrganization. Начало метода';
        try {
            $conracting_organizations = ContractingOrganization::find()
                ->joinWith('worker.employee')
                ->joinWith('role')
                ->joinWith('reasonCheckKnowledge')
                ->joinWith('companyDepartment.company')
                ->all();
            if (!empty($conracting_organizations)) {
                foreach ($conracting_organizations as $conracting_organization) {
                    if ($conracting_organization->role->type == 3) {
                        $role_type = 2;
                    } elseif ($conracting_organization->role->type < 3) {
                        $role_type = 1;
                    } else {
                        $role_type = 2;
                    }
                    $conracting_organization_id = $conracting_organization->id;
                    if (isset($role_type)) {
                        $result['roles'][$role_type]['role_type'] = $role_type;
                        $result['roles'][$role_type]['conracting_organizations'][$conracting_organization_id]['contracting_organization_id'] = $conracting_organization_id;
                        $result['roles'][$role_type]['conracting_organizations'][$conracting_organization_id]['worker_id'] = $conracting_organization->worker_id;
                        $result['roles'][$role_type]['conracting_organizations'][$conracting_organization_id]['stuff_number'] = $conracting_organization->worker->tabel_number;
                        $name = mb_substr($conracting_organization->worker->employee->first_name, 0, 1);
                        $patronymic = mb_substr($conracting_organization->worker->employee->patronymic, 0, 1);
                        $result['roles'][$role_type]['conracting_organizations'][$conracting_organization_id]['full_name'] = "{$conracting_organization->worker->employee->last_name} {$name}. {$patronymic}.";
                        $result['roles'][$role_type]['conracting_organizations'][$conracting_organization_id]['role_id'] = $conracting_organization->role_id;
                        $result['roles'][$role_type]['conracting_organizations'][$conracting_organization_id]['role_title'] = $conracting_organization->role->title;
                        $result['roles'][$role_type]['conracting_organizations'][$conracting_organization_id]['company_department_id'] = $conracting_organization->company_department_id;
                        $result['roles'][$role_type]['conracting_organizations'][$conracting_organization_id]['company_title'] = $conracting_organization->companyDepartment->company->title;
                        $result['roles'][$role_type]['conracting_organizations'][$conracting_organization_id]['reason_check_knowledge_id'] = $conracting_organization->reason_check_knowledge_id;
                        $result['roles'][$role_type]['conracting_organizations'][$conracting_organization_id]['reason_check_knowledge_title'] = $conracting_organization->reasonCheckKnowledge->title;
                        $result['roles'][$role_type]['conracting_organizations'][$conracting_organization_id]['number_certificate'] = $conracting_organization->number_certificate;
                        $result['roles'][$role_type]['conracting_organizations'][$conracting_organization_id]['date'] = $conracting_organization->date;
                        $result['roles'][$role_type]['conracting_organizations'][$conracting_organization_id]['date_format'] = date('d.m.Y', strtotime($conracting_organization->date));
                        $result['roles'][$role_type]['conracting_organizations'][$conracting_organization_id]['licence_date_start'] = $conracting_organization->date_start;
                        $result['roles'][$role_type]['conracting_organizations'][$conracting_organization_id]['licence_date_start_format'] = date('d.m.Y', strtotime($conracting_organization->date_start));
                    }
                }
            }
            $statistic = self::GetStatisticContractingOrganization();
            if ($statistic['status'] == 1) {
                $result['statistic'] = $statistic['Items'];
                $warnings[] = $statistic['warnings'];
            } else {
                $warnings[] = $statistic['warnings'];
                $errors[] = $statistic['errors'];
                throw new Exception('GetContractingOrganization. Ошибка при получении статистики');
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetContractingOrganization. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetContractingOrganization. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SaveContractingOrganization() - Метод сохранение данных проверки знаний подрядной организацией
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\ContractingOrganization&method=SaveContractingOrganization&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 12.12.2019 11:36
     */
    public static function SaveContractingOrganization($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $another_role = null;
//        $data_post = '{"contracting_organization_id":null,"worker_id":2915911,"stuff_number":"2052752","full_name":"Гантимуров В. Г.","role_id":181,"role_title":"ГМ","contractor_company_id":1,"contractor_company_title":"ООО \"Эксперт-партнер-холдинг\"","reason_check_knowledge_id":1,"reason_check_knowledge_title":"Переодическая","number_certificate":"№ 00564","date":"2019-12-12","date_format":"12.12.2019"}';
        $warnings[] = 'SaveContractingOrganization. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveContractingOrganization. Не переданы входные параметры');
            }
            $warnings[] = 'SaveContractingOrganization. Данные успешно переданы';
            $warnings[] = 'SaveContractingOrganization. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'SaveContractingOrganization. Декодировал входные параметры';
            if (!property_exists($post_dec, 'contracting_organization_id') ||
                !property_exists($post_dec, 'worker_id') ||
                !property_exists($post_dec, 'role_id') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'reason_check_knowledge_id') ||
                !property_exists($post_dec, 'number_certificate') ||
                !property_exists($post_dec, 'date')
            )                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('SaveContractingOrganization. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveContractingOrganization. Данные с фронта получены';
            $contracting_organization_id = $post_dec->contracting_organization_id;
            $worker_id = $post_dec->worker_id;
            $role_id = $post_dec->role_id;
            $company_department_id = $post_dec->company_department_id;
            $reason_check_knowledge_id = $post_dec->reason_check_knowledge_id;
            $number_certificate = $post_dec->number_certificate;
            $date = date('Y-m-d', strtotime($post_dec->date));
            $get_diff = ContractingOrganization::findOne(['worker_id' => $worker_id]);
            if (!empty($get_diff)) {
                if ($get_diff->role_id != $role_id) {
                    $another_role = 'Такой человек был уже записан под другой ролью! Роль: ' . $get_diff->role->title;
                }
            }

            if (empty($another_role)) {
                $contracting_organization = ContractingOrganization::findOne(['id' => $contracting_organization_id]);
                if (empty($contracting_organization)) {
                    $contracting_organization = new ContractingOrganization();
                }
                $contracting_organization->worker_id = $worker_id;
                $contracting_organization->company_department_id = $company_department_id;
                $contracting_organization->reason_check_knowledge_id = $reason_check_knowledge_id;
                $contracting_organization->role_id = $role_id;
                $contracting_organization->number_certificate = $number_certificate;
                $contracting_organization->date = $date;
                if ($contracting_organization->save()) {
                    $warnings[] = 'SaveContractingOrganization. Данные по подрядной организации успешно сохранены';
                    $contracting_organization->refresh();
                    $post_dec->contracting_organization_id = $contracting_organization->id;
                    if ($contracting_organization->role->type == 3) {
                        $post_dec->role_type = 2;
                    } elseif ($contracting_organization->role->type < 3) {
                        $post_dec->role_type = 1;
                    } else {
                        $post_dec->role_type = 2;
                    }
                } else {
                    $errors[] = $contracting_organization->errors;
                    throw new Exception('SaveContractingOrganization. Ошибка при сохранении данных по подрядной организации');
                }
            } else {
                $errors[] = $another_role;
                $status = 0;
            }
            $statistic = self::GetStatisticContractingOrganization();
            if ($statistic['status'] == 1) {
//                $post_dec->{'statistic'} = (object)array();
                $post_dec->{'statistic'} = $statistic['Items'];
                $warnings[] = $statistic['warnings'];
            } else {
                $warnings[] = $statistic['warnings'];
                $errors[] = $statistic['errors'];
                throw new Exception('GetContractingOrganization. Ошибка при получении статистики');
            }
        } catch (Throwable $exception) {
            $errors[] = 'SaveContractingOrganization. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SaveContractingOrganization. Конец метода';
        return array('Items' => $post_dec, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeleteContractingOrganization() - Метод удаления проверки знаний подрядной организацией
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\ContractingOrganization&method=DeleteContractingOrganization&subscribe=&data={"contracting_organization_id":2}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 12.12.2019 11:37
     */
    public static function DeleteContractingOrganization($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'DeleteContractingOrganization. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('DeleteContractingOrganization. Не переданы входные параметры');
            }
            $warnings[] = 'DeleteContractingOrganization. Данные успешно переданы';
            $warnings[] = 'DeleteContractingOrganization. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'DeleteContractingOrganization. Декодировал входные параметры';
            if (!property_exists($post_dec, 'contracting_organization_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('DeleteContractingOrganization. Переданы некорректные входные параметры');
            }
            $warnings[] = 'DeleteContractingOrganization. Данные с фронта получены';
            $contracting_organization_id = $post_dec->contracting_organization_id;
            $del_contracting_organization_id = ContractingOrganization::deleteAll(['id' => $contracting_organization_id]);
            $statistic = self::GetStatisticContractingOrganization();
            if ($statistic['status'] == 1) {
                $result['statistic'] = $statistic['Items'];
                $warnings[] = $statistic['warnings'];
            } else {
                $warnings[] = $statistic['warnings'];
                $errors[] = $statistic['errors'];
                throw new Exception('GetContractingOrganization. Ошибка при получении статистики');
            }
        } catch (Throwable $exception) {
            $errors[] = 'DeleteContractingOrganization. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'DeleteContractingOrganization. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SaveContractorCompany() - Метод сохранения компании подрядчика в справочник
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\ContractingOrganization&method=SaveContractorCompany&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 12.12.2019 11:46
     */
    public static function SaveContractorCompany($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
//        $data_post = '{"title":"Тестовая компания-подрядчик"}';
        $warnings[] = 'SaveContractorCompany. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveContractorCompany. Не переданы входные параметры');
            }
            $warnings[] = 'SaveContractorCompany. Данные успешно переданы';
            $warnings[] = 'SaveContractorCompany. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'SaveContractorCompany. Декодировал входные параметры';
            if (!property_exists($post_dec, 'title'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('SaveContractorCompany. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveContractorCompany. Данные с фронта получены';
            $title = $post_dec->title;
            $add_contractor_company = new ContractorCompany();
            $add_contractor_company->title = $title;
            if ($add_contractor_company->save()) {
                $warnings[] = 'SaveContractorCompany. Сохранение комнпании-подрядчика прошло успешно';
                $add_contractor_company->refresh();
                $post_dec->id = $add_contractor_company->id;
            } else {
                $errors[] = $add_contractor_company->errors;
                throw new Exception('SaveContractorCompany. Ошибка при сохранении компании-подрядчика');
            }
        } catch (Throwable $exception) {
            $errors[] = 'SaveContractorCompany. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SaveContractorCompany. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SaveReasonCheckKnowledge() - Метод сохранения причины проверки знаний в справочник
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\ContractingOrganization&method=SaveReasonCheckKnowledge&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 12.12.2019 11:51
     */
    public static function SaveReasonCheckKnowledge($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
//        $data_post = '{"title":"Очень серездная прчина"}';
        $warnings[] = 'SaveReasonCheckKnowledge. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveReasonCheckKnowledge. Не переданы входные параметры');
            }
            $warnings[] = 'SaveReasonCheckKnowledge. Данные успешно переданы';
            $warnings[] = 'SaveReasonCheckKnowledge. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'SaveReasonCheckKnowledge. Декодировал входные параметры';
            if (!property_exists($post_dec, 'title'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('SaveReasonCheckKnowledge. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveReasonCheckKnowledge. Данные с фронта получены';
            $title = $post_dec->title;
            $add_reason_check_knowledge = new ReasonCheckKnowledge();
            $add_reason_check_knowledge->title = $title;
            if ($add_reason_check_knowledge->save()) {
                $warnings[] = 'SaveReasonCheckKnowledge. Причина проверки знаний успешно сохранена';
                $add_reason_check_knowledge->refresh();
                $post_dec->id = $add_reason_check_knowledge->id;
            } else {
                $errors[] = $add_reason_check_knowledge->errors;
                throw new Exception('SaveReasonCheckKnowledge. Ошибка при сохранении причины проверки знаний');
            }
        } catch (Throwable $exception) {
            $errors[] = 'SaveReasonCheckKnowledge. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SaveReasonCheckKnowledge. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    public static function GetStatisticContractingOrganization()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetStatisticContractingOrganization. Начало метода';
        try {
            $count_worker = ContractingOrganization::find()
                ->select(['contracting_organization.worker_id'])
                ->groupBy('contracting_organization.worker_id')
                ->count();
            if (!empty($count_worker)) {
                $result['count_worker'] = $count_worker;
            } else {
                $result['count_worker'] = 0;
            }
            $count_worker_by_role = ContractingOrganization::find()
                ->select(['count(contracting_organization.worker_id) as count,contracting_organization.role_id as role,role.title as role_title'])
                ->innerJoin('role', 'role.id = contracting_organization.role_id')
                ->groupBy('contracting_organization.worker_id,role')
                ->asArray()
                ->all();
            foreach ($count_worker_by_role as $worker_by_role) {
                $result['workers_by_role'][$worker_by_role['role']]['role_id'] = $worker_by_role['role'];
                $result['workers_by_role'][$worker_by_role['role']]['role_title'] = $worker_by_role['role_title'];
                if (isset($result['workers_by_role'][$worker_by_role['role']]['count'])) {
                    $result['workers_by_role'][$worker_by_role['role']]['count']++;
                } else {
                    $result['workers_by_role'][$worker_by_role['role']]['count'] = 1;
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetStatisticContractingOrganization. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetStatisticContractingOrganization. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetContractingCompany() - Получение информации о компании подрядчике и проврке знаний работниках
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\ContractingOrganization&method=GetContractingCompany&subscribe=&data={"company_department_id":20028766}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 27.12.2019 8:13
     */
    public static function GetContractingCompany($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'GetContractingCompany';
        $contract_company = array();                                                                                // Промежуточный результирующий массив
        $workers = array();
        $check_knowledge_protocols = array();
        $reason_check_knowledge_title = null;
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $contracting_companies = CompanyDepartment::find()
                ->joinWith('departmentType')
                ->joinWith('company')
                ->joinWith(['companyDepartmentInfos' => function ($comp_dep_info) {
                    $comp_dep_info->joinWith(['headWorker head_worker' => function ($head_worker) {
                        $head_worker->joinWith('employee head_worker_employee');
                    }]);
                    $comp_dep_info->joinWith(['responsibleSafeWorkWorker responsible_safe_work_worker' => function ($responsible_safe_work_worker) {
                        $responsible_safe_work_worker->joinWith('employee responsile_safe_work_employee');
                    }]);
                }])
                ->joinWith('workers.employee')
                ->joinWith('workers.position')
                ->joinWith('workers.workerObjects.role')
                ->joinWith('companyDepartmentRoutes')
                ->joinWith('companyDepartmentAttachments.companyDepartmentAttachmentType')
                ->joinWith('companyDepartmentAttachments.attachment')
                ->joinWith('placeCompanyDepartments.place')
                ->where(['company_department.id' => $company_department_id])
                ->all();
            if (!empty($contracting_companies)) {
                foreach ($contracting_companies as $contracting_company) {
                    $comp_dep_id = $contracting_company->id;
                    $company_title = $contracting_company->company->title;
                    $comp_dep_type_id = $contracting_company->department_type_id;
                    $comp_dep_type_title = $contracting_company->departmentType->title;
                    $contract_company['company_department_id'] = $comp_dep_id;
                    $contract_company['company_title'] = $company_title;
                    $contract_company['company_department_info'] = array();
                    foreach ($contracting_company->companyDepartmentInfos as $companyDepartmentInfo) {
                        $contract_company['company_department_info']['company_title'] = $company_title;
                        $contract_company['company_department_info']['company_department_info_id'] = $companyDepartmentInfo->id;
                        $contract_company['company_department_info']['legal_address'] = $companyDepartmentInfo->legal_address;
                        $contract_company['company_department_info']['phone_fax'] = $companyDepartmentInfo->phone_fax;
                        $contract_company['company_department_info']['head_worker_id'] = $companyDepartmentInfo->head_worker_id;
                        if (!empty($companyDepartmentInfo->head_worker_id)) {
                            $full_name_head = "{$companyDepartmentInfo->headWorker->employee->last_name} {$companyDepartmentInfo->headWorker->employee->first_name} {$companyDepartmentInfo->headWorker->employee->patronymic}";
                        } else {
                            $full_name_head = null;
                        }
                        $contract_company['company_department_info']['full_name'] = $full_name_head;
                        $contract_company['company_department_info']['head_phone_number'] = $companyDepartmentInfo->head_phone_number;
                        $contract_company['company_department_info']['activity_type'] = $companyDepartmentInfo->activitty_type;
                        $contract_company['company_department_info']['danger_factor'] = $companyDepartmentInfo->danger_factor;
                        $contract_company['company_department_info']['border_company_department'] = $companyDepartmentInfo->border_company_department;
                        $contract_company['company_department_info']['work_schedule'] = $companyDepartmentInfo->work_schedule;
                        $contract_company['company_department_info']['responsible_safe_work_worker_id'] = $companyDepartmentInfo->responsible_safe_work_worker_id;
                        if (!empty($companyDepartmentInfo->responsible_safe_work_worker_id)) {
                            $full_name_res = "{$companyDepartmentInfo->responsibleSafeWorkWorker->employee->last_name} {$companyDepartmentInfo->responsibleSafeWorkWorker->employee->first_name} {$companyDepartmentInfo->responsibleSafeWorkWorker->employee->patronymic}";
                        } else {
                            $full_name_res = null;
                        }
                        $contract_company['company_department_info']['full_name_responsible_safe_work'] = $full_name_res;
                        $contract_company['company_department_info']['responsible_safe_work_phone_number'] = $companyDepartmentInfo->responsible_safe_work_phone_number;
                        $contract_company['company_department_info']['date_start'] = $companyDepartmentInfo->date_start;
                        if (!empty($companyDepartmentInfo->date_start)) {
                            $date_start = date('d.m.Y', strtotime($companyDepartmentInfo->date_start));
                        } else {
                            $date_start = null;
                        }
                        $contract_company['company_department_info']['date_start_format'] = $date_start;
                        $contract_company['company_department_info']['date_end'] = $companyDepartmentInfo->date_end;
                        if (!empty($companyDepartmentInfo->date_end)) {
                            $date_end = date('d.m.Y', strtotime($companyDepartmentInfo->date_end));
                        } else {
                            $date_end = null;
                        }
                        $contract_company['company_department_info']['date_end_format'] = $date_end;
                    }

                    $contract_company['company_department_info']['company_department_routes'] = array();
                    foreach ($contracting_company->companyDepartmentRoutes as $companyDepartmentRoute) {
                        $company_department_route_id = $companyDepartmentRoute->id;
                        $contract_company['company_department_info']['company_department_routes'][$company_department_route_id]['company_department_route_id'] = $company_department_route_id;
                        $contract_company['company_department_info']['company_department_routes'][$company_department_route_id]['title'] = $companyDepartmentRoute->title;
                        $contract_company['company_department_info']['company_department_routes'][$company_department_route_id]['way_of_movement'] = $companyDepartmentRoute->way_of_movement;
                    }
                    if (empty($contract_company['company_department_info']['company_department_routes'])) {
                        $contract_company['company_department_info']['company_department_routes'] = (object)array();
                    }
                    $contract_company['company_department_info']['company_department_places'] = array();
                    foreach ($contracting_company->placeCompanyDepartments as $placeCompanyDepartment) {
                        $place_company_department_id = $placeCompanyDepartment->id;
                        $place_id = $placeCompanyDepartment->place_id;
                        $place_title = $placeCompanyDepartment->place->title;
                        $contract_company['company_department_info']['company_department_places'][$place_company_department_id]['place_company_department_id'] = $place_company_department_id;
                        $contract_company['company_department_info']['company_department_places'][$place_company_department_id]['place_id'] = $place_id;
                        $contract_company['company_department_info']['company_department_places'][$place_company_department_id]['place_title'] = $place_title;
                    }
                    if (empty($contract_company['company_department_info']['company_department_places'])) {
                        $contract_company['company_department_info']['company_department_places'] = (object)array();
                    }
                    $contract_company['company_department_info']['company_department_attachment'] = array();
                    foreach ($contracting_company->companyDepartmentAttachments as $companyDepartmentAttachment) {
                        $company_department_attachment_id = $companyDepartmentAttachment->id;
                        $contract_company['company_department_info']['company_department_attachment'][$company_department_attachment_id]['company_department_attachment_id'] = $company_department_attachment_id;
                        $contract_company['company_department_info']['company_department_attachment'][$company_department_attachment_id]['company_department_attachment_type_id'] = $companyDepartmentAttachment->company_department_attachment_type_id;
                        $contract_company['company_department_info']['company_department_attachment'][$company_department_attachment_id]['company_department_attachment_type_title'] = $companyDepartmentAttachment->companyDepartmentAttachmentType->title;
                        $attachment_id = $companyDepartmentAttachment->attachment_id;
//                        $warnings[] = $method_name . '.айди вложения';
//                        $warnings[] = $companyDepartmentAttachment->attachment_id;
//                        $warnings[] = $companyDepartmentAttachment;
                        if (!empty($companyDepartmentAttachment->attachment)) {
                            $attachment_title = $companyDepartmentAttachment->attachment->title;
                            $attachment_path = $companyDepartmentAttachment->attachment->path;
                            $attachment_type = $companyDepartmentAttachment->attachment->attachment_type;
                        } else {
                            $attachment_title = null;
                            $attachment_path = null;
                            $attachment_type = null;
                        }
                        $contract_company['company_department_info']['company_department_attachment'][$company_department_attachment_id]['attachment_id'] = $attachment_id;
                        $contract_company['company_department_info']['company_department_attachment'][$company_department_attachment_id]['attachment_title'] = $attachment_title;
                        $contract_company['company_department_info']['company_department_attachment'][$company_department_attachment_id]['attachment_path'] = $attachment_path;
                        $contract_company['company_department_info']['company_department_attachment'][$company_department_attachment_id]['attachment_type'] = $attachment_type;
                        $contract_company['company_department_info']['company_department_attachment'][$company_department_attachment_id]['attachment_status'] = null;
                        $contract_company['company_department_info']['company_department_attachment'][$company_department_attachment_id]['title'] = $companyDepartmentAttachment->title;
                        $contract_company['company_department_info']['company_department_attachment'][$company_department_attachment_id]['date'] = $companyDepartmentAttachment->date;
                        if (!empty($companyDepartmentAttachment->date)) {
                            $date_atachment = date('d.m.Y', strtotime($companyDepartmentAttachment->date));
                        } else {
                            $date_atachment = null;
                        }
                        $contract_company['company_department_info']['company_department_attachment'][$company_department_attachment_id]['date_format'] = $date_atachment;
                        $contract_company['company_department_info']['company_department_attachment'][$company_department_attachment_id]['licence_date_start'] = $companyDepartmentAttachment->date_start;
                        if (!empty($companyDepartmentAttachment->date_start)) {
                            $licence_date_start = date('d.m.Y', strtotime($companyDepartmentAttachment->date_start));
                        } else {
                            $licence_date_start = null;
                        }
                        $contract_company['company_department_info']['company_department_attachment'][$company_department_attachment_id]['licence_date_start_format'] = $licence_date_start;
                    }
                    if (empty($contract_company['company_department_info']['company_department_attachment'])) {
                        $contract_company['company_department_info']['company_department_attachment'] = (object)array();
                    }
                    if (empty($contract_company['company_department_info'])) {
                        $contract_company['company_department_info'] = (object)array();
                    }
                    $contract_company['workers'] = array();
                    foreach ($contracting_company->workers as $worker) {
                        $worker_id = $worker->id;
                        $workers[] = $worker_id;
                        $contract_company['workers'][$worker_id]['worker_id'] = $worker_id;
                        $contract_company['workers'][$worker_id]['stuff_number'] = $worker->tabel_number;
                        $name = mb_substr($worker->employee->first_name, 0, 1);
                        if (!empty($worker->employee->patronymic)) {
                            $patronymic = mb_substr($worker->employee->patronymic, 0, 1);
                            $full_name = "{$worker->employee->last_name} {$name}. {$patronymic}.";
                        } else {
                            $patronymic = null;
                            $full_name = "{$worker->employee->last_name} {$name}.";
                        }
                        $contract_company['workers'][$worker_id]['full_name'] = $full_name;
                        $contract_company['workers'][$worker_id]['position_title'] = $worker->position->title;
                        if (isset($worker->workerObjects[0]->role->id)) {
                            $role_id = $worker->workerObjects[0]->role_id;
                            $role_title = $worker->workerObjects[0]->role->title;
                            $role_type = $worker->workerObjects[0]->role->type;
                        } else {
                            $role_id = null;
                            $role_title = null;
                            $role_type = null;
                        }
                        $contract_company['workers'][$worker_id]['role_id'] = $role_id;
                        $contract_company['workers'][$worker_id]['role_title'] = $role_title;
                        $contract_company['workers'][$worker_id]['role_type'] = $role_type;
                        $contract_company['workers'][$worker_id]['check_knowledge'] = array();
//                        $contract_company['workers'][$worker_id]['type_check_knowledge_id'] = null;
//                        $contract_company['workers'][$worker_id]['type_check_knowledge_title'] = null;
//                        $contract_company['workers'][$worker_id]['date'] = null;
//                        $contract_company['workers'][$worker_id]['date_format'] = null;
//                        $contract_company['workers'][$worker_id]['reason_check_knowledge_id'] = null;
//                        $contract_company['workers'][$worker_id]['reason_check_knowledge_title'] = null;
//                        $contract_company['workers'][$worker_id]['protocols'] = array();
                    }
                }
                // получение сведений о проверке зний у работников подрядных организаций
                $check_knowledges = CheckKnowledge::find()
                    ->joinWith('checkKnowledgeWorkers.worker')
                    ->joinWith('checkProtocols')
                    ->joinWith('typeCheckKnowledge')
                    ->joinWith('reasonCheckKnowledge')
                    ->where(['check_knowledge.company_department_id' => $company_department_id])
                    ->andWhere(['in', 'worker_id', $workers])
                    ->andWhere(['in', 'check_knowledge.type_check_knowledge_id', [1, 2]])
                    ->orderBy('check_knowledge.date ASC')
                    ->all();
//                    Assistant::PrintR($check_knowledges);die;
                if (!empty($check_knowledges)) {
                    // обрабатываем проверки знаний по работникам
                    foreach ($check_knowledges as $check_knowledge) {
                        $check_knowledge_id = $check_knowledge->id;
                        $date_check_knowledge = $check_knowledge->date;
                        $type_check_knowledge_id = $check_knowledge->type_check_knowledge_id;
                        $type_check_knowledge_title = $check_knowledge->typeCheckKnowledge->title;
                        $reason_check_knowledge_id = $check_knowledge->reason_check_knowledge_id;
                        if (isset($check_knowledge->reasonCheckKnowledge->title) && !empty($check_knowledge->reasonCheckKnowledge->title)) {
                            $reason_check_knowledge_title = $check_knowledge->reasonCheckKnowledge->title;
                        } else {
                            $reason_check_knowledge_title = null;
                        }
//                        $reason_check_knowledge_title = $reason_check_knowledge_title;
                        // обрабатываем сведения о протоколах проверки знаний
                        foreach ($check_knowledge->checkProtocols as $checkProtocol) {
                            $check_protocol_id = $checkProtocol->id;
                            $check_knowledge_protocols[$check_knowledge_id]['check_protocol_id'] = $check_protocol_id;
                            $check_knowledge_protocols[$check_knowledge_id]['attachment_id'] = $checkProtocol->attachment_id;
                            if ($checkProtocol->attachment) {
                                $check_knowledge_protocols[$check_knowledge_id]['attachment_path'] = $checkProtocol->attachment->path;
                                $check_knowledge_protocols[$check_knowledge_id]['attachment_title'] = $checkProtocol->attachment->title;
                                $check_knowledge_protocols[$check_knowledge_id]['attachment_type'] = $checkProtocol->attachment->attachment_type;
                            } else {
                                $check_knowledge_protocols[$check_knowledge_id]['attachment_path'] = "";
                                $check_knowledge_protocols[$check_knowledge_id]['attachment_title'] = "";
                                $check_knowledge_protocols[$check_knowledge_id]['attachment_type'] = "";
                            }
                        }
                        // обрабатываем сведения о работниках в проверке знаний
                        foreach ($check_knowledge->checkKnowledgeWorkers as $checkKnowledgeWorker) {
                            $worker_id = $checkKnowledgeWorker->worker_id;
                            $knowledges[$worker_id]['check_knowledge']['type_check_knowledge_id'] = $type_check_knowledge_id;
                            $knowledges[$worker_id]['check_knowledge']['type_check_knowledge_title'] = $type_check_knowledge_title;
                            $knowledges[$worker_id]['check_knowledge']['date'] = $date_check_knowledge;
                            $knowledges[$worker_id]['check_knowledge']['date_format'] = date('d.m.Y', strtotime($date_check_knowledge));
                            $knowledges[$worker_id]['check_knowledge']['reason_check_knowledge_id'] = $reason_check_knowledge_id;
                            $knowledges[$worker_id]['check_knowledge']['reason_check_knowledge_title'] = $reason_check_knowledge_title;
                            // если у работника нет протокола проверки знаний, то пишем пустой протокол
                            if (!empty($check_knowledge_protocols and isset($check_knowledge_protocols[$check_knowledge_id]))) {
                                $knowledges[$worker_id]['check_knowledge']['protocol'] = $check_knowledge_protocols[$check_knowledge_id];
                            } else {
                                $knowledges[$worker_id]['check_knowledge']['protocol'] = array();
                            }
                        }
                    }
//                    foreach ($knowledges as $worker_id=>$knowledge) {
//                        if(isset($contract_company['workers'][$worker_id])){
//                            $contract_company['workers'][$worker_id]['check_knowledge'] = $knowledge['check_knowledge'];
//                        }else{
//                            $contract_company['workers'][$worker_id]['check_knowledge'] = (object)array();
//                        }
//                    }
                    foreach ($contract_company['workers'] as $worker_id => $worker) {
                        if (isset($knowledges[$worker_id])) {
                            $contract_company['workers'][$worker_id]['check_knowledge'] = $knowledges[$worker_id]['check_knowledge'];
                        } else {
                            $contract_company['workers'][$worker_id]['check_knowledge'] = (object)array();
                        }
                    }
                }
            }
            $statistic = self::GetStatisticCheckKnowledge($company_department_id);
            if ($statistic['status'] == 1) {
                $contract_company['statistic'] = $statistic['Items'];
                $warnings[] = $statistic['warnings'];
            } else {
                $warnings[] = $statistic['warnings'];
                $errors[] = $statistic['errors'];
                throw new Exception($method_name . '. Ошибка при выполнении метода статистики');
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $contract_company;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SavingWorker() - Метод сохранение работника и возврат нужной структуры
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\ContractingOrganization&method=SavingWorker&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 27.12.2019 8:51
     */
    public static function SavingWorker($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SavingWorker';
        $result = array();
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'last_name') ||
                !property_exists($post_dec, 'first_name') ||
                !property_exists($post_dec, 'patronymic') ||
                !property_exists($post_dec, 'birth_date') ||
                !property_exists($post_dec, 'gender') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'department_type_id') ||
                !property_exists($post_dec, 'position_id') ||
                !property_exists($post_dec, 'staff_number') ||
                !property_exists($post_dec, 'file') ||
                !property_exists($post_dec, 'file_name') ||
                !property_exists($post_dec, 'file_extension') ||
                !property_exists($post_dec, 'height') ||
                !property_exists($post_dec, 'date_start') ||
                !property_exists($post_dec, 'date_end') ||
                !property_exists($post_dec, 'vgk_status') ||
                !property_exists($post_dec, 'work_mode') ||
                !property_exists($post_dec, 'type_obj') ||
                !property_exists($post_dec, 'role_id') ||
                !property_exists($post_dec, 'pass_number')
            )                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $first_name = $post_dec->first_name;
            $last_name = $post_dec->last_name;
            $patronymic = $post_dec->patronymic;
            $birth_date = strtotime($post_dec->birth_date);
            $gender = $post_dec->gender;
            $company_department_id = $post_dec->company_department_id;
            $department_type_id = $post_dec->department_type_id;
            $position_id = $post_dec->position_id;
            $staff_number = $post_dec->staff_number;
            if (!empty($post_dec->file)) {
                $file = $post_dec->file;
            } else {
                $file = null;
            }
//            Assistant::PrintR($post_dec);
//            Assistant::PrintR('kek');die;
            $file_name = $post_dec->file_name;
            $file_extension = $post_dec->file_extension;
            $height = $post_dec->height;
            $date_start = $post_dec->date_start;
            $date_end = $post_dec->date_end;
            $vgk_status = $post_dec->vgk_status;
            $work_mode = $post_dec->work_mode;
            $type_obj = $post_dec->type_obj;
            $role_id = $post_dec->role_id;
            $pass_number = $post_dec->pass_number;
            if (empty($work_mode)) {
                $work_mode = 1;
            }
            if (empty($role_id)) {
                $role_id = 9;
            }
            $found_comp_dep = CompanyDepartment::findOne(['id' => $company_department_id]);
            if (!empty($found_comp_dep)) {
                $company_id = $found_comp_dep->company_id;
                $department_id = $found_comp_dep->department_id;
            } else {
                throw new Exception($method_name . '. нет такого участа');
            }
            if (property_exists($post_dec, 'worker_id')) {
                $worker_id = $post_dec->worker_id;
                $edit_worker_data = self::EditWorker($worker_id, $first_name, $last_name, $patronymic, $birth_date, $gender,
                    $date_start, $date_end, $type_obj, $position_id, $staff_number, $file_name, $file_extension, $role_id,
                    $vgk_status, $department_type_id, $company_department_id, $file);
                if ($edit_worker_data['status'] == 1) {
                    $result = $edit_worker_data['Items'];
                    $warnings[] = $edit_worker_data['warnings'];
                } else {
                    $warnings[] = $edit_worker_data['warnings'];
                    $errors[] = $edit_worker_data['errors'];
                    throw new Exception($method_name . '. Ошибка при изменении данных сотрудника');
                }
            } else {
                $save_data = self::SaveWorkerData($first_name, $last_name, $patronymic, $birth_date, $gender, $company_id, $department_id, $company_department_id,
                    $department_type_id, $position_id, $staff_number, $file, $file_name, $file_extension, $height, $date_start,
                    $date_end, $vgk_status, $work_mode, $type_obj, $role_id, $pass_number);
                if ($save_data['status'] == 1) {
                    $result = $save_data['Items']['contracting_company'];
                    $warnings[] = $save_data['warnings'];
                } else {
                    $warnings[] = $save_data['warnings'];
                    $errors[] = $save_data['errors'];
                    throw new Exception($method_name . '. Ошибка при добавлении сотрудника');
                }
            }
            $statistic = self::GetStatisticCheckKnowledge($company_department_id);
            if ($statistic['status'] == 1) {
                $result['statistic'] = $statistic['Items'];
                $warnings[] = $statistic['warnings'];
            } else {
                $warnings[] = $statistic['warnings'];
                $errors[] = $statistic['errors'];
                throw new Exception($method_name . '. Ошибка при выполнении метода статистики');
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SaveCompanyDepartmentInfo() - Сохранение информации о подрядной организации
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 27.12.2019 13:04
     */
    public static function SaveCompanyDepartmentInfo($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveCompanyDepartmentInfo';
        $result = array();
        $warnings[] = $method_name . '. Начало метода';
        $session = \Yii::$app->session;
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'company_title') ||
                !property_exists($post_dec, 'company_department_info_id') ||
                !property_exists($post_dec, 'legal_address') ||
                !property_exists($post_dec, 'phone_fax') ||
                !property_exists($post_dec, 'head_worker_id') ||
                !property_exists($post_dec, 'head_phone_number') ||
                !property_exists($post_dec, 'activity_type') ||
                !property_exists($post_dec, 'danger_factor') ||
                !property_exists($post_dec, 'border_company_department') ||
                !property_exists($post_dec, 'work_schedule') ||
                !property_exists($post_dec, 'responsible_safe_work_worker_id') ||
                !property_exists($post_dec, 'responsible_safe_work_phone_number') ||
                !property_exists($post_dec, 'date_start') ||
                !property_exists($post_dec, 'date_end') ||
                !property_exists($post_dec, 'company_department_routes') ||
                !property_exists($post_dec, 'company_department_places') ||
                !property_exists($post_dec, 'company_department_attachment')
            )                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $company_department_info_id = $post_dec->company_department_info_id;
            $company_title = $post_dec->company_title;
            $company_department_id = $post_dec->company_department_id;
            $legal_address = $post_dec->legal_address;
            $phone_fax = $post_dec->phone_fax;
            $head_worker_id = $post_dec->head_worker_id;
            $head_phone_number = $post_dec->head_phone_number;
            $activitty_type = $post_dec->activity_type;
            $danger_factor = $post_dec->danger_factor;
            $border_company_department = $post_dec->border_company_department;
            $work_schedule = $post_dec->work_schedule;
            $responsible_safe_work_worker_id = $post_dec->responsible_safe_work_worker_id;
            $responsible_safe_work_phone_number = $post_dec->responsible_safe_work_phone_number;
            $company_department_routes = $post_dec->company_department_routes;
            $company_department_places = $post_dec->company_department_places;
            $company_department_attachments = $post_dec->company_department_attachment;
            $company_department = CompanyDepartment::findOne(['id' => $company_department_id]);
            if (!empty($company_department)) {
                $company = Company::findOne(['id' => $company_department->company_id]);
                unset($company_department);
                if (!empty($company)) {
                    $company->title = $company_title;
                    if ($company->save()) {
                        $warnings[] = $method_name . '. Название компании успешно изменено';
                    } else {
                        $errors[] = $company->errors;
                        throw new Exception($method_name . '. Ошибка при смене названия компании');
                    }
                }
            }

            if (!empty($post_dec->date_start)) {
                $date_start = date('Y-m-d', strtotime($post_dec->date_start));
            } else {
                $date_start = null;
            }
            if (!empty($post_dec->date_end)) {
                $date_end = date('Y-m-d', strtotime($post_dec->date_end));
            } else {
                $date_end = null;
            }
            $company_department_info = CompanyDepartmentInfo::findOne(['id' => $company_department_info_id]);
            if (empty($company_department_info)) {
                $company_department_info = new CompanyDepartmentInfo();
            }
            $company_department_info->company_department_id = $company_department_id;
            $company_department_info->legal_address = $legal_address;
            $company_department_info->phone_fax = $phone_fax;
            $company_department_info->head_worker_id = $head_worker_id;
            $company_department_info->head_phone_number = $head_phone_number;
            $company_department_info->activitty_type = $activitty_type;
            $company_department_info->danger_factor = $danger_factor;
            $company_department_info->border_company_department = $border_company_department;
            $company_department_info->work_schedule = $work_schedule;
            $company_department_info->responsible_safe_work_worker_id = $responsible_safe_work_worker_id;
            $company_department_info->responsible_safe_work_phone_number = $responsible_safe_work_phone_number;
            $company_department_info->date_start = $date_start;
            $company_department_info->date_end = $date_end;
            if ($company_department_info->save()) {
                $warnings[] = $method_name . '. Информация о подрядной организации успешно сохранена';
            } else {
                $errors[] = $company_department_info->errors;
                throw new Exception($method_name . '. Ошибка при сохранение информации о подрядной организации');
            }
            $del_routes = CompanyDepartmentRoute::deleteAll(['company_department_id' => $company_department_id]);
            if (!empty($company_department_routes)) {
                foreach ($company_department_routes as $company_department_route) {
                    $comp_dep_route_batch_array[] = [$company_department_id, $company_department_route->title, $company_department_route->way_of_movement];
                }
                if (!empty($comp_dep_route_batch_array)) {
                    $comp_dep_route_inserted = \Yii::$app->db->createCommand()
                        ->batchInsert('company_department_route',
                            [
                                'company_department_id',
                                'title',
                                'way_of_movement'
                            ], $comp_dep_route_batch_array)
                        ->execute();
                    if ($comp_dep_route_inserted != 0) {
                        $warnings[] = $method_name . '. Было добавлено:' . $comp_dep_route_inserted . ' маршрутов';
                    } else {
                        throw new Exception($method_name . '. Ошибка при добавлении маршрутов передвижения работников');
                    }
                }
            }
            $del_places = PlaceCompanyDepartment::deleteAll(['company_department_id' => $company_department_id]);
            if (!empty($company_department_places)) {
                foreach ($company_department_places as $company_department_place) {
                    $comp_dep_places_batch_array[] = [$company_department_place->place_id, $company_department_id];
                }
                if (!empty($comp_dep_places_batch_array)) {
                    $comp_dep_palce_inserted = \Yii::$app->db->createCommand()
                        ->batchInsert('place_company_department',
                            [
                                'place_id',
                                'company_department_id'
                            ], $comp_dep_places_batch_array)
                        ->execute();
                    if ($comp_dep_palce_inserted != 0) {
                        $warnings[] = $method_name . '. Было добавлено:' . $comp_dep_palce_inserted . ' мест, на которые допускается организация';
                    } else {
                        throw new Exception($method_name . '. Ошибка при добавлении мест на которые допускается организации');
                    }
                }
            }
//            $del_attachements = CompanyDepartmentAttachment::deleteAll(['company_department_id'=>$company_department_id]);
            if (!empty($company_department_attachments)) {
                foreach ($company_department_attachments as $company_department_attachment) {
                    $attahcment_id = $company_department_attachment->attachment_id;
                    if (!empty($company_department_attachment->date)) {
                        $date_attachment = date('Y-m-d', strtotime($company_department_attachment->date));
                    } else {
                        $date_attachment = null;
                    }
                    if (!empty($company_department_attachment->licence_date_start)) {
                        $date_start = date('Y-m-d', strtotime($company_department_attachment->licence_date_start));
                    } else {
                        $date_start = null;
                    }

                    $comp_dep_attachment = CompanyDepartmentAttachment::findOne(['id' => $company_department_attachment->company_department_attachment_id]);
                    if (empty($comp_dep_attachment)) {
                        $comp_dep_attachment = new CompanyDepartmentAttachment();
                        $comp_dep_attachment->company_department_id = $company_department_id;
                        $comp_dep_attachment->company_department_attachment_type_id = $company_department_attachment->company_department_attachment_type_id;
                    }
                    $comp_dep_attachment->title = $company_department_attachment->title;
                    $comp_dep_attachment->date = $date_attachment;
                    $comp_dep_attachment->date_start = $date_start;
                    if ($company_department_attachment->attachment_status == 'new') {
                        $add_attachment = new Attachment();
                        $normalize_path = Assistant::UploadFile($company_department_attachment->attachment_path, $company_department_attachment->attachment_title, 'attachment', $company_department_attachment->attachment_type);
                        $add_attachment->path = $normalize_path;
                        $add_attachment->date = BackendAssistant::GetDateFormatYMD();
                        $add_attachment->worker_id = $session['worker_id'];
                        $add_attachment->section_title = 'ОТ и ПБ/Подрядные организации';
                        $add_attachment->title = $company_department_attachment->attachment_title;
                        $add_attachment->attachment_type = $company_department_attachment->attachment_type;
                        if ($add_attachment->save()) {
                            $warnings[] = $method_name . '. Вложение успешно сохранено';
                            $add_attachment->refresh();
                            $attahcment_id = $add_attachment->id;
                        } else {
                            $errors[] = $add_attachment->errors;
                            throw new Exception($method_name . '. Ошибка при сохранении вложения');
                        }
//                        $company_department_attachment_batch_array[] = [
//                            $company_department_attachment->title,
//                            $date_attachment,
//                            $date_start,
//                            $company_department_id,
//                            $company_department_attachment->attahcment_id,
//                            $company_department_attachment->company_department_attachment_type_id
//                        ];
                    } elseif ($company_department_attachment->attachment_status == 'del') {
                        $del_attachment = CompanyDepartmentAttachment::deleteAll(['id' => $company_department_attachment->company_department_attachment_id]);
                        $attahcment_id = null;
                    } elseif ($company_department_attachment->attachment_status == 'update') {
                        $add_attachment = new Attachment();
                        $normalize_path = Assistant::UploadFile($company_department_attachment->attachment_path, $company_department_attachment->attachment_title, 'attachment', $company_department_attachment->attachment_type);
                        $add_attachment->path = $normalize_path;
                        $add_attachment->date = BackendAssistant::GetDateFormatYMD();
                        $add_attachment->worker_id = $session['worker_id'];
                        $add_attachment->section_title = 'ОТ и ПБ/Подрядные организации';
                        $add_attachment->title = $company_department_attachment->attachment_title;
                        $add_attachment->attachment_type = $company_department_attachment->attachment_type;
                        if ($add_attachment->save()) {
                            $warnings[] = $method_name . '. Вложение успешно сохранено';
                            $add_attachment->refresh();
                            $attahcment_id = $add_attachment->id;
                        } else {
                            $errors[] = $add_attachment->errors;
                            throw new Exception($method_name . '. Ошибка при сохранении вложения');
                        }
                    }
                    $comp_dep_attachment->attachment_id = $attahcment_id;
                    if (!$comp_dep_attachment->save()) {
                        $errors[] = $comp_dep_attachment->errors;
                        throw new Exception($method_name . '. Ошибка при изменении наименования или даты связки вложения');
                    }
                    unset($comp_dep_attachment);
                }
//                if (isset($company_department_attachment_batch_array) && !empty($company_department_attachment_batch_array)){
//                    $company_department_attachment_inserted = \Yii::$app->db->createCommand()
//                        ->batchInsert('company_department_attachment',[
//                            'title',
//                            'date',
//                            'date_start',
//                            'company_department_id',
//                            'attachment_id',
//                            'company_department_attachment_type_id'
//                        ],$company_department_attachment_batch_array)
//                        ->execute();
//                    if ($company_department_attachment_inserted != 0){
//                        $warnings[] = $method_name.'. '.$company_department_attachment_inserted.' документа(ов) было добавлено';
//                    }else{
//                        throw new Exception($method_name . '. Ошибка при сохранении вложения участка');
//                    }
//                }
            }
            $json_comp_dep = json_encode(['company_department_id' => $company_department_id]);
            $company_department_info = self::GetContractingCompany($json_comp_dep);
            if ($company_department_info['status'] == 1) {
                $result = $company_department_info['Items']['company_department_info'];
                $warnings[] = $company_department_info['warnings'];
            } else {
                $warnings[] = $company_department_info['warnings'];
                $errors[] = $company_department_info['errors'];
                throw new Exception($method_name . '. Ошибка при получении информации о подрядной организации');
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

    public static function GetStatisticCheckKnowledge($company_department_id)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetStatisticCheckKnowledge';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $count_worker = Worker::find()
                ->select(['count(worker.id)'])
                ->where(['worker.company_department_id' => $company_department_id])
                ->count();
            if (!empty($count_worker)) {
                $result['count_worker'] = $count_worker;
            } else {
                $result['count_worker'] = 0;
            }
            $count_by_roles = Worker::find()
                ->select(['position.title', 'count(position.id) as count'])
                ->innerJoin('position', 'position.id = worker.position_id')
                ->groupBy('position.title')
                ->where(['worker.company_department_id' => $company_department_id])
                ->asArray()
                ->all();
            if (isset($count_by_roles) && !empty($count_by_roles)) {
                $result['count_by_roles'] = $count_by_roles;
            } else {
                $result['count_by_roles'] = (object)array();
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
     * Метод EditWorker() - Описание метода
     * @param $worker_id - идентификатор работника
     * @param $first_name - имя
     * @param $last_name - фаимилия
     * @param $patronymic - отчество
     * @param $birth_date - дата рождения
     * @param $gender - пол
     * @param $date_start - дата начала работы
     * @param $date_end - дата окончания работы
     * @param $type_obj - тип работника (Подземный, Поверхностный)
     * @param $position_id - идентификатор профессии
     * @param $tabel_number - табельный номер
     * @param $file_name - наименование фотографии
     * @param $file_extension - расширение фотографии
     * @param $role_id - идентификатор роли
     * @param $vgk_status - статус ВГК
     * @param $department_type_id - идентификатор типа департамента
     * @param $company_department_id - идентификатор участка
     * @param $file - файл с фотографией работника
     * @return array                    - массив:
     *                                          Items - role_id
     *                                                  role_title
     *                                                  role_type_id
     *                                                  position_id
     *                                                  position_title
     *                                                  stuff_number
     *                                                  full_name
     *
     *
     *
     *
     * @package frontend\controllers\industrial_safety
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.02.2020 15:43
     */
    public static function EditWorker($worker_id, $first_name, $last_name, $patronymic, $birth_date, $gender,
                                      $date_start, $date_end, $type_obj, $position_id, $tabel_number,
                                      $file_name, $file_extension, $role_id, $vgk_status, $department_type_id, $company_department_id, $file)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'EditWorker';
        $warnings[] = $method_name . '. Начало метода';
        $handbookEmployee = new HandbookEmployeeController(1, false);
        try {
            $worker = Worker::findOne(['id' => $worker_id]);
            if (empty($worker)) {
                throw new Exception($method_name . "Редактируемого работника $worker_id нет в БД");
            }
            $employee = $worker->employee;
            $employee->first_name = $first_name;
            $employee->last_name = $last_name;
            $employee->patronymic = $patronymic;
            $employee->birthdate = date('Y-m-d H:i:s', $birth_date);
            $employee->gender = $gender;
            if (!$employee->save()) {
                $errors[] = $employee->errors;
                throw new Exception($method_name . '. Не удалось сохранить employee');
            }
            $name = mb_substr($employee->first_name, 0, 1);
            if (!empty($employee->patronymic)) {
                $patronymic = mb_substr($employee->patronymic, 0, 1);
                $full_name = "{$employee->last_name} {$name}. {$patronymic}.";
            } else {
                $full_name = "{$employee->last_name} {$name}.";
            }
            unset($employee);
            $result['worker_id'] = $worker_id;
            $result['fullname'] = $full_name;

//            $plan_shift = PlanShift::findOne($work_mode);
//            if ($plan_shift) {
//                //сохранить его
//                $shift_worker = new ShiftWorker();
//                $shift_worker->date_time = date('Y-m-d H:i:s',strtotime(BackendAssistant::GetDateNow()));
//                $shift_worker->plan_shift_id = $work_mode;
//                $shift_worker->worker_id = $worker->id;
//                if (!$shift_worker->save()) {
//                    $errors[] = "не удалось сохранить shift_worker";
//                }
//            }
//            $warnings[] = 'EditWorker. сохранил смену работника';

            $warnings[] = 'EditWorker. начинаю получать типовой объект.';
            //получить класс рабочего
            $object = TypicalObject::findOne(['title' => $type_obj]);
            $warnings[] = 'EditWorker. Типовой объект получен';
            $worker_object = WorkerObject::findOne(['worker_id' => $worker->id]);
            if (!$worker_object) {
                $worker_object = new WorkerObject();
                $maxWorkerObject = WorkerObject::find()->max('id');
                //Привязать идентификатор типового объекта (тип работы)
                $worker_object->id = $maxWorkerObject ? $maxWorkerObject + 1 : 1;
            }
            $worker_object->object_id = $object->id;
            $warnings[] = 'EditWorker. Уложил для сохранения типовой объект работника в воркер обджект';
            //Привязать id работника
            $worker_object->worker_id = $worker->id;
            if (!empty($role_id))                     //если передали роль работника
            {
                $worker_object->role_id = $role_id;                                                //записываем его роль
            } else                                                                                           //иначе пишем что роль Прочее
            {
                $worker_object->role_id = 1;
            }
            $warnings[] = 'EditWorker. Приступаю к сохранении модели работника';
            //Сохранить модель
            if (!$worker_object->save()) {
                $errors[] = $worker_object->errors;
                throw new Exception("EditWorker. Ошибка сохранения модели WorkerObject");
            }
            $worker_object_id = $worker_object->id;
            $result['role_id'] = $worker_object->role_id;
            $result['role_title'] = $worker_object->role->title;
            $result['role_type_id'] = $worker_object->role->type;
            $worker->tabel_number = $tabel_number;
            $worker->date_start = date("Y-m-d H:i:s", strtotime($date_start));
            $worker->date_end = date("Y-m-d H:i:s", strtotime($date_end));
            $worker->vgk = $vgk_status;
            $company_id = $worker->companyDepartment->company_id;
            $department_id = $worker->companyDepartment->department_id;
            if (empty($department_type_id)) {
                $department_type_id = DepartmentTypeEnum::OTHER;
            }
            //найти привязку подразделения к предприятию
            $company_department = CompanyDepartment::find()
                ->where([
                    'company_id' => $company_id,
                    'department_id' => $department_id,
                ])
                ->one();
            //если такой привязки нет
            if (!$company_department) {
                //создать ее
                $company_department = new CompanyDepartment();
                $company_department->id = $company_id;
                $company_department->company_id = $company_id;
                $company_department->department_id = $department_id;
                $company_department->department_type_id = $department_type_id;
                if (!$company_department->save()) {
                    $errors[] = "не удалось сохранить company_department";
                }
                $workMode = new ShiftDepartment();
                $workMode->company_department_id = $company_department->id;
                $companyWorkMode = ShiftMine::findOne(['company_id' => $company_id]);
                $workMode->plan_shift_id = $companyWorkMode->plan_shift_id;
                $workMode->date_time = date("Y-m-d H:i:s");
                if (!$workMode->save()) {
                    $errors[] = "не удалось сохранить workmode";
                }
            } else {
                //Создать новую модель CompanyDepartment
                $company_department->department_type_id = $department_type_id;
                //Сохранить модель
                if (!$company_department->save()) {
                    $errors[] = $company_department->errors;
                    throw new Exception("EditWorker. Не удалось сохранить привязку подразделения к компании CompanyDepartment");
                }
            }
            $worker->company_department_id = $company_department_id;
            $result['company_department_id'] = $company_department_id;
            $worker->position_id = $position_id;
            unset($company_department);
            //TODO 23.01.2020 rudov:  Рост решили что не будет меняться тута
//            if (!empty($height)) {
//                $handbookEmployee = new HandbookEmployeeController(1);
//                $tabel_flag = $handbookEmployee->actionAddWorkerParameter($worker_object, 1, 1);
//                //вызвать функцию сохранения значения справочного параметра
//                $handbookEmployee->saveWorkerParameterHandbookValue($worker_object, 1, $height);                   //параметр рост = id=1
//
//            }
            $full_name = $last_name . '_' . $first_name . (isset($patronymic) ? ('_' . $patronymic) : '');
            if (!empty($file)) {
                $response = self::SaveFileFromWorker($worker_object_id, $file, $full_name, $file_extension);
                if (!empty($response['errors'])) {
                    $errors[] = $response['errors'];
                    throw new Exception($method_name . '. Ошибка при сохранении файла');
                }
//                $url = $response['url'];
            } else {
//                $url = 'kek';
            }
//            Assistant::PrintR($url);
//            Assistant::PrintR('kek');
//            die;
            //TODO 23.01.2020 rudov: заполнение пропуска тоже решили что не будет, оставлен на всякий случай
//            if (!empty($pass_number)) {
//                $tabel_flag = $handbookEmployee->actionAddWorkerParameter($worker_object, 2, 1);
//                //вызвать функцию сохранения значения справочного параметра
//                $handbookEmployee->saveWorkerParameterHandbookValue($worker_object, 2, $pass_number);              //параметр номер пропуска = id=2
//            }
            //вызвать функцию сохранения значения справочного параметра
            $tabel_flag = $handbookEmployee->actionAddWorkerParameter($worker_object, 392, 1);
            $handbookEmployee->saveWorkerParameterHandbookValue($worker_object, 392, $tabel_number);              //параметр табельный номер = id=392
            /**
             * блок переноса сенсора в новую шахту если таковое требуется
             * если шахта есть, то делаем перенос или инициализацию, в зависимости от описанного выше
             */
            $warnings[] = "EditWorker. Ищу шахту у воркера";
            $response = (new WorkerCacheController())->getParameterValueHash($worker_id, 158, 2);
            if ($response) {
                $checkin = $response['value'];
                $warnings[] = "EditWorker. Статус спуска $checkin";
            } else {
                $warnings[] = "EditWorker. у воркера нет статуса спуска";
                $checkin = false;
            }
            $response = (new WorkerCacheController())->getParameterValueHash($worker_id, 346, 2);

            if ($response) {
                $mine_id = $response['value'];
                $warnings[] = "EditWorker. Шахта есть и она $mine_id";
            } else {
                $warnings[] = "EditWorker. Шахты нет у воркера";
                $mine_id = false;
            }
            if ($mine_id and $checkin == 1) {
                $warnings[] = "EditWorker. Ищу сведения у работника";
                $workers = (new Query())
                    ->select(
                        [
                            'position_title',
                            'department_title',
                            'first_name',
                            'last_name',
                            'patronymic',
                            'gender',
                            'stuff_number',
                            'worker_object_id',
                            'worker_id',
                            'object_id',
                            'mine_id',
                            'checkin_status'
                        ])
                    ->from(['view_initWorkerMineCheckin'])
                    ->where(['mine_id' => $mine_id, 'worker_id' => $worker_id])
                    ->one();
                if ($workers) {
                    $worker_to_cache = WorkerCacheController::buildStructureWorker(
                        $worker_id,
                        $workers['worker_object_id'],
                        $workers['object_id'],
                        $workers['stuff_number'],
                        $workers['last_name'] . " " . $workers['first_name'] . " " . $workers['patronymic'],
                        $workers['mine_id'],
                        $workers['position_title'],
                        $workers['department_title'],
                        $workers['gender']);
                    $ask_from_method = WorkerMainController::AddMoveWorkerMineInitDB($worker_to_cache);
                    if ($ask_from_method['status'] == 1) {
                        $warnings[] = $ask_from_method['warnings'];
                        $warnings[] = "actionSaveSpecificParametersValuesBase. Добавил шахту для работника в кэш";
                    } else {
                        $warnings[] = $ask_from_method['warnings'];
                        $errors[] = $ask_from_method['errors'];
                        throw new Exception(" EditWorker. WorkerMainController::AddMoveWorkerMineInitDB. Ошибка добавления" . $worker_id);
                    }
                } else {
                    $warnings[] = "Не смог найти данные работника в БД";
                }
            }
            if ($worker->save()) {
                $warnings[] = $method_name . '. Модель работника сохранена';
            } else {
                $errors[] = $worker->errors;
                throw new Exception($method_name . '. Ошибка при сохранении модели работника');
            }
            $result['position_id'] = $worker->position_id;
            $result['position_title'] = $worker->position->title;
            $result['stuff_number'] = $worker->tabel_number;
            HandbookCachedController::clearDepartmentCache();
            HandbookCachedController::clearWorkerCache();
            (new WorkerCacheController())->initWorkerParameterHandbookValue($worker_id);
            (new WorkerCacheController())->initWorkerParameterValue($worker_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeleteWorker() - Метод удаления сотрудника
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 23.01.2020 17:17
     */
    public static function DeleteWorker($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteWorker';
        $result = array();                                                                                // Промежуточный результирующий массив
        $session = Yii::$app->session;
        $session->open();
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'worker_id') ||
                !property_exists($post_dec, 'company_department_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';

            $worker_id = $post_dec->worker_id;
            $company_department_id = $post_dec->company_department_id;

            if (!isset($session['sessionLogin'])) {
                $errors[] = $method_name . '. Время сессии истекло';
            }
            if (!AccessCheck::checkAccess($session['sessionLogin'], 26)) {                                                //если пользователю разрешен доступ к функции
                $errors[] = $method_name . '. Недостаточно прав для совершения данной операции';
                throw new Exception($method_name . '. Недостаточно прав для совершения данной операции');
            }

            $worker = Worker::findOne(['id' => $worker_id]);
            if (empty($worker)) {
                throw new Exception($method_name . '. Указанного сотрудника не существует');
            }

            $injunction_worker = Injunction::findOne(['worker_id' => $worker_id]);
            if ($injunction_worker) {
                throw new Exception($method_name . '. Удаление не возможно, на сотрудника выписано предписание');
            }

            $order_operation_worker = OperationWorker::findOne(['worker_id' => $worker_id]);
            if ($order_operation_worker) {
                throw new Exception($method_name . '. Удаление не возможно, на сотрудника выдавался наряд');
            }

            Employee::deleteAll(['id' => $worker->employee_id]);

            /************** Удаление работника из кэша ****************************/
            $workerCacheController = new WorkerCacheController();

            $worker_cache_del = $workerCacheController->delParameterValueHash($worker_id);
            $errors = array_merge($errors, $worker_cache_del['errors']);
            $workerCacheController->delWorkerMineHash($worker_id);


            // Статистика по инструктажам
            $statistic = self::GetStatisticCheckKnowledge($company_department_id);
            if ($statistic['status'] == 1) {
                $result['statistic'] = $statistic['Items'];
                $warnings[] = $statistic['warnings'];
            } else {
                $warnings[] = $statistic['warnings'];
                $errors[] = $statistic['errors'];
                throw new Exception($method_name . '. Ошибка при получении статистики');
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SaveFileFromWorker() - Метод сохранения файла фотографии работника
     * @param $worker_object_id - идентификатор объекта работника
     * @param $file - файл (блоб)
     * @param $file_name - наименование файла
     * @return array - массив следующего вида:  errors  - массив ошибок
     *                                          url     - путь до файла
     *
     * @package frontend\controllers\industrial_safety
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.02.2020 14:06
     */
    public static function SaveFileFromWorker($worker_object_id, $file, $file_name, $file_extension)
    {
        $errors = array();
        $url = '';
        $handbookEmployeeController = new HandbookEmployeeController(1, false);
        $date_now = date('d-m-Y H-i-s.U');
        $full_name_translit = SmsSender::encodeToSmsString($file_name);
        $uploaded_file = Yii::getAlias('@app') . '/web/img/miners/' . $date_now . '_' . $full_name_translit . '.' . $file_extension;                              //объявляем и инициируем переменную для хранения названия файла, состоящего из
        $file_path = '/img/miners/' . $date_now . '_' . $full_name_translit . '.' . $file_extension;
        $content = base64_decode($file);
        $result_save_file = file_put_contents($uploaded_file, $content);
        if (!empty($result_save_file)) {
            $workerPhotoParameter = WorkerParameter::findOne(['worker_object_id' => $worker_object_id, 'parameter_id' => 3, 'parameter_type_id' => 1]);                 //ищем параметр Фотография у данного worker_object'a
            if (!empty($workerPhotoParameter)) {
                $workerHandbookValue = WorkerParameterHandbookValue::find()//ищем значение этого параметра
                ->where(['worker_parameter_id' => $workerPhotoParameter->id])
                    ->orderBy(['date_time' => SORT_DESC])
                    ->one();
                if (isset($workerHandbookValue)) {                                                                      //если такое значение есть
                    if ($workerHandbookValue->value != $file_path) {                                                    //если найденное значение не совпадает с создаваемым
                        //то создаем новую запись в таблице WorkerHandbookParameterValue
                        $workerNewHandbookValueFlag = $handbookEmployeeController->actionAddWorkerParameterHandbookValue($workerPhotoParameter->id, $file_path, 1, date('Y-m-d H:i:s'));
                        if ($workerNewHandbookValueFlag == -1) {                                                        //если флаг выполнения функции добавления записи в таблицу равен -1
                            $errors[] = 'не удалось сохранить справочное значение';                                     //сохраняем ошибку в массиве ошибок
                        }
                        //иначе ищем созданную запись
                        $workerNewHandbookValue = WorkerParameterHandbookValue::find()->where(['worker_parameter_id' => $workerPhotoParameter->id])->orderBy(['date_time' => SORT_DESC])->one();
                        $url = $workerNewHandbookValue->value;                                                          //записываем в переменную $url значение пути до изображения
                    }
                } else {                                                                                                //если у сотрудника не было еще загружено ни одного фото
                    //то создаем запись в таблице WorkerHandbookParameterValue
                    $workerNewHandbookValueFlag = $handbookEmployeeController->actionAddWorkerParameterHandbookValue($workerPhotoParameter->id, $file_path, 1, date('Y-m-d H:i:s'));
                    if ($workerNewHandbookValueFlag == -1) {                                                            //если флаг выполнения функции добавления записи в таблицу равен -1
                        $errors[] = "не удалось сохранить новое справочное значение (первое для этого worker'a)";       //сохраняем ошибку в массиве ошибок
                    }
                    //иначе ищем созданную запись
                    $workerNewHandbookValue = WorkerParameterHandbookValue::findOne(['worker_parameter_id' => $workerPhotoParameter->id]);
                    $url = $workerNewHandbookValue->value;                                                              //записываем в переменную $url значение пути до изображения
                }
            } else {
                //то создаем новую запись в таблице WorkerParameter
                $workerNewPhotoParameterFlag = $handbookEmployeeController->actionAddWorkerParameter($worker_object_id, 3, 1);

                if ($workerNewPhotoParameterFlag == -1) {                                                       //если флаг выполнения функции добавления записи в таблицу равен -1
                    $errors[] = 'не удалось сохранить новый параметр';                                          //сохраняем ошибку в массиве ошибок
                }
                //иначе ищем созданную запись
                $workerNewPhotoParameter = WorkerParameter::findOne(['id' => $workerNewPhotoParameterFlag]);
                //сохраняем значение этого параметра в таблице WorkerParameterHandbookValue
                $workerNewPhotoParameterHandbookValueFlag = $handbookEmployeeController->actionAddWorkerParameterHandbookValue($workerNewPhotoParameter->id, $file_path, 1, date('Y-m-d H:i:s'));
                if ($workerNewPhotoParameterHandbookValueFlag == -1) {                                          //если флаг выполнения функции добавления записи в таблицу равен -1
                    $errors[] = 'не удалось сохранить значение нового параметра';                               //сохраняем ошибку в массиве ошибок
                }
                $workerNewPhotoParameterHandbookValue = WorkerParameterHandbookValue::findOne(['worker_parameter_id' => $workerNewPhotoParameter->id]);
                $url = $workerNewPhotoParameterHandbookValue->value;
            }
        } else {
            $errors[] = 'Не удалось сохранить файл';
        }
        return array('errors' => $errors, 'url' => $url);                                                                                      //возвращаем на фронтэнд сериализованный получившийся массив
    }

    /**
     * Метод SaveWorkerData() - Метод сохранения нового работника
     * @param $first_name - имя
     * @param $last_name - фамилия
     * @param $patronymic - отчество
     * @param $birth_date - дата рождения
     * @param $gender - пол
     * @param $company_id - идентифкатор компании
     * @param $department_id - идентификатор департамента
     * @param $company_department_id - идентификатор связки департамента и подразделения
     * @param $department_type_id - идентификатро типа департамента
     * @param $position_id - идентификатор профессии
     * @param $staff_number - табельный номер
     * @param $file - фотография (формата blob)
     * @param $file_name - наименование фотографии
     * @param $file_extension - расширение фотографии
     * @param $height - рост
     * @param $date_start - дата начала работы
     * @param $date_end - дата окончания работ
     * @param $vgk_status - статус ВГК
     * @param $work_mode - пусто
     * @param $type_obj - Тип работника (Подземный, Поверхостный)
     * @param $role_id - Роль работника
     * @param $pass_number - номер пропуска (не реализовано)
     * @return array                - массив:
     *                                      Items       -   worker_id - идентификатор работника
     *                                                      photo_url - путь до фотографии работника
     *                                                      arrWorkers - массив работников
     *                                                      arrMines - массив шахт
     *                                                      contracting_company - данные для подрядной организации по работнику
     *                                      warnings    - предупреждения (ход выполнения метода)
     *                                      errors      - массив ошибок
     *                                      status      - статус выполнения метода
     *
     * @package frontend\controllers\industrial_safety
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.02.2020 15:19
     */
    public static function SaveWorkerData($first_name, $last_name, $patronymic, $birth_date, $gender, $company_id,
                                          $department_id, $company_department_id, $department_type_id, $position_id, $staff_number, $file,
                                          $file_name, $file_extension, $height, $date_start, $date_end, $vgk_status,
                                          $work_mode, $type_obj, $role_id, $pass_number)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveWorkerData';
        $saving_data = array();                                                                                // Промежуточный результирующий массив
        $isset_tabel_number = true;
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbookEmployeeController = new HandbookEmployeeController(1, false);
            $employee = Employee::findOne([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'patronymic' => $patronymic,
                'birthdate' => date('Y-m-d', $birth_date),
            ]);
            //Если такого человека нет
            if (!$employee) {
                //Создать новую модель Employee
                $employee = new Employee();
                //Сохранить ФИО, пол и дату рождения
                $employee->first_name = $first_name;
                $employee->last_name = $last_name;
                $employee->patronymic = $patronymic;
                $employee->birthdate = date('Y-m-d', $birth_date);
                $employee->gender = $gender;
                //Сохранить модель
                if (!$employee->save()) {
                    $errors[] = $employee->errors;
                    throw new Exception("SaveWorkerData. Не удалось сохранить сотрудника в employee Employee");
                }
            }

            $full_name = $employee->last_name . '_' . $employee->first_name . (isset($employee->patronymic) ? ('_' . $employee->patronymic) : '');


            //Запросить подразделение предприятия по полученным идентификаторам подразделения и предприятия
            $company_department = CompanyDepartment::findOne(['id' => $company_id]);

            //Создать новую модель CompanyDepartment
            $company_department->department_type_id = $department_type_id;
            //Сохранить модель
            if (!$company_department->save()) {
                $errors[] = $company_department->errors;
                throw new Exception("SaveWorkerData. Не удалось сохранить привязку подразделения к компании CompanyDepartment");
            }

            //Запросить работника по табельному номеру
            $worker = Worker::find()->where(['tabel_number' => $staff_number])->one();
            $worker_from_employee = Worker::find()->where(['id' => $employee->id])->one();
            if ($worker_from_employee) {
                if ($worker and $worker_from_employee and $worker_from_employee['tabel_number'] !== $worker['tabel_number']) {
                    $errors[] = 'SaveWorkerData. Сотрудник есть в системе с другим табельным номером';
                    $status = 0;
                    $worker_id = null;
//                    throw new Exception("SaveWorkerData. Сотрудник есть в системе с другим табельным номером");
                    $isset_tabel_number = false;
                } else {
                    $isset_tabel_number = true;
                }
            }

            //Если работника нет
            if ($worker) {
                throw new Exception("SaveWorkerData. Сотрудник с таким табельным номером уже есть в БД");
            }


            if ($isset_tabel_number) {
                $worker = new Worker();//Создать новую модель Worker
                $worker->id = (int)$employee->id;//Привязать человека
                $worker->employee_id = (int)$employee->id;//Привязать человека
                $worker->company_department_id = $company_department_id;                                               //Привязать подразделение предприятия
                $worker->position_id = $position_id;//Привязать должность
                $worker->tabel_number = $staff_number;//Сохранить табельный номер
                $worker->date_start = date('Y-m-d', strtotime($date_start));//Сохранить дату начала работы в нужном формате
                if (empty($date_end)) {
                    $date_end = '9999-12-31';
                }
                $worker->date_end = $date_end;//Если задана дата окончания работы, сохранить ее
                $worker->vgk = $vgk_status;//Если задана дата окончания работы, сохранить ее
                if (!$worker->save()) {
                    $errors[] = $worker->errors;
                    throw new Exception("SaveWorkerData. Не удалось сохранить нового worker'a");
                }//Сохранить модель
                $worker->refresh();
                $worker_id = $worker->id;
                $saving_data['worker_id'] = $worker_id;
                $shift_worker = new ShiftWorker();
                $shift_worker->worker_id = $worker->id;//сохранить id работника
                $shift_worker->plan_shift_id = $work_mode;//сохранить id режима работы
                $shift_worker->date_time = date('Y-m-d H:i:s');
                if (!$shift_worker->save()) {
                    $errors[] = $shift_worker->errors;
                    throw new Exception("SaveWorkerData. Не удалось сохранить режим работы у сотрудника ShiftWorker");
                }
                $worker_object = new WorkerObject();//Создать новую модель WorkerObject
                $object = TypicalObject::find()->where(['title' => $type_obj])->one();//получить класс рабочего
                $worker_object->id = (int)$employee->id;//Привязать идентификатор типового объекта (тип работы)
                $worker_object->object_id = $object->id;
                $worker_object->worker_id = $worker->id;//Привязать id работника
                $worker_object->role_id = $role_id;
                if (!$worker_object->save()) {                                                                            //Сохранить модель
                    $errors[] = $worker_object->errors;
                    throw new Exception("SaveWorkerData. Не удалось сохранить привязку worker_object WorkerObject");
                }
                $object_id = $worker_object->object_id;
                $worker_object_id = $worker_object->id;
                $response = $handbookEmployeeController->actionCopyTypicalParametersToWorker($object_id, $worker_object_id);//копирование типового объекта в конкретного работника
                if ($response['status'] == 0) {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new Exception("SaveWorkerData. Не удалось скопировать типовые параметры работника");
                }//сохраняем значения параметров из базовой таблицы в параметры базового объекта
                $worker_parameter_id = $handbookEmployeeController->actionAddWorkerParameter($worker_object_id, 1, 1);//параметр рост
                if ($height != '') {
                    $worker_parameter_value = $handbookEmployeeController->actionAddWorkerParameterHandbookValue($worker_parameter_id, $height, 1, date('Y-m-d H:i:s'));//сохранение значения параметра
                    if ($worker_parameter_value == -1)
                        $errors[] = 'SaveWorkerData. Ошибка сохранения значения параметров базового справочника в параметрах: 1';
                }
                $worker_parameter_id = $handbookEmployeeController->actionAddWorkerParameter($worker_object_id, 2, 1);//параметр номер пропуска
                if (!empty($pass_number)) {
                    $worker_parameter_value = $handbookEmployeeController->actionAddWorkerParameterHandbookValue($worker_parameter_id, $pass_number, 1, date('Y-m-d H:i:s'));//сохранение значения параметра
                    if ($worker_parameter_value == -1)
                        $errors[] = 'SaveWorkerData. Ошибка сохранения значения параметров базового справочника в параметрах: 2';
                }
                $worker_parameter_id = $handbookEmployeeController->actionAddWorkerParameter($worker_object_id, 392, 1);//параметр Табельный номер
                if (!empty($staff_number)) {
                    $worker_parameter_value = $handbookEmployeeController->actionAddWorkerParameterHandbookValue($worker_parameter_id, $staff_number, 1, date('Y-m-d H:i:s'));//сохранение значения параметра
                    if ($worker_parameter_value == -1)
                        $errors[] = 'SaveWorkerData. Ошибка сохранения значения параметров базового справочника в параметрах: 392';
                }//                        $worker_parameter_id = $this->actionAddWorkerParameter($worker_object_id, 3, 1); //параметр фото
                //                        if ($post['photo'] != "") {
                //                            $worker_parameter_value = $this->actionAddWorkerParameterHandbookValue($worker_parameter_id, $post['photo'], 1, 1);//сохранение значения параметра
                //                            if ($worker_parameter_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 3";
                //                        }
                $worker_parameter_id = $handbookEmployeeController->actionAddWorkerParameter($worker_object_id, 274, 1);//параметр типовой объект
                $worker_parameter_value = $handbookEmployeeController->actionAddWorkerParameterHandbookValue($worker_parameter_id, $object_id, 1, date('Y-m-d H:i:s.U'));//сохранение значения параметра
                if ($worker_parameter_value == -1)
                    $errors[] = 'SaveWorkerData. Ошибка сохранения значения параметров базового справочника в параметрах: 274';
                if (isset($file) && !empty($file)) {
                    $response = self::SaveFileFromWorker($worker_object_id, $file, $full_name, $file_extension);
                    if (!empty($response['errors'])) {
                        $errors[] = $response['errors'];
                        throw new Exception($method_name . '. Ошибка при сохранении файла');
                    }
                    $saving_data['photo_url'] = $response['url'];
                } else {
                    $saving_data['photo_url'] = '';
                }
                $saving_data['arrWorkers'] = $handbookEmployeeController->getWorkersFromView($company_id, is_array($company_department) ? (int)$company_department['id'] : (int)$company_department->id);
                $saving_data['arrMines'] = $handbookEmployeeController::GetCompanyDepartmentForHandbook()['model'];
//                $full_name = $employee->last_name . ' ' . mb_substr($employee->first_name ,0,1). (isset($employee->patronymic) ? (' ' . mb_substr($employee->patronymic,0,1)) : '');
                $name = mb_substr($employee->first_name, 0, 1);
                if (!empty($employee->patronymic)) {
                    $patronymic = mb_substr($employee->patronymic, 0, 1);
                    $full_name = "{$employee->last_name} {$name}. {$patronymic}.";
                } else {
                    $full_name = "{$employee->last_name} {$name}.";
                }
//                $patronymic = mb_substr($employee->patronymic,0,1);
//                $full_name = "{$employee->last_name} {$name}. {$patronymic}.";
                $saving_data['contracting_company'] = [
                    'worker_id' => $worker_id ? $worker_id : null,
                    'stuff_number' => $worker->tabel_number,
                    'full_name' => $full_name,
                    'position_title' => $worker->position->title,
                    'role_id' => $role_id,
                    'role_title' => $worker_object->role->title,
                    'role_type' => $worker_object->role->type,
                    'check_knowledge' => array()];
                (new WorkerCacheController())->initWorkerParameterHandbookValue($worker_id);
                (new WorkerCacheController())->initWorkerParameterValue($worker_id);
            }
            HandbookCachedController::clearWorkerCache();
            HandbookCachedController::clearDepartmentCache();

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $saving_data;

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    public static function Test()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'Test';
        $warnings[] = $method_name . '. Начало метода';
        try {
//            $result = DepartmentController::GetUpperCompanies([4029936]);
//            $result = ViolationType::deleteAll(['>','id',129]);
//            $result = Checking::deleteAll(['is not','nn_id',null]);
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

    // http://10.36.52.8/read-manager-amicum?controller=industrial_safety\ContractingOrganization&method=TestLdapConnection&subscribe=&data={}
    public static function TestLdapConnection()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'TestLdapConnection';
        $warnings[] = $method_name . '. Начало метода';
        try {
            putenv('LDAPTLS_REQCERT=allow');
            $ad = ldap_connect(AD_HOST, AD_PORT)
            or die("Couldn't connect to AD!");
            ldap_set_option($ad, LDAP_OPT_PROTOCOL_VERSION, AD_VERSION_PROTOCOL);
            ldap_set_option($ad, LDAP_OPT_REFERRALS, 0);
            $user = 'severstal\ekvn';
            $pass = 'P@ssw0rd';
            $ldapbind = ldap_bind($ad, $user, $pass);
            if ($ldapbind) {
                $result = 'LDAP bind successful...';
            } else {
                $result = 'LDAP bind failed...';
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
}
