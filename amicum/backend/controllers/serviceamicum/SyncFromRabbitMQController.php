<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\serviceamicum;

use backend\controllers\Assistant;
use backend\controllers\const_amicum\DepartmentTypeEnum;
use backend\controllers\queuemanagers\RabbitController;
use backend\models\RabbitMq;
use Exception;
use frontend\controllers\HandbookCachedController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Company;
use frontend\models\CompanyDepartment;
use frontend\models\Department;
use frontend\models\Employee;
use frontend\models\Position;
use frontend\models\User;
use frontend\models\Worker;
use frontend\models\WorkerObject;
use SimpleXMLElement;
use Throwable;
use Yii;
use yii\db\Query;

class SyncFromRabbitMQController
{

    /** ОБЩИЕ МЕТОДЫ */
    // saveMessageRabbitMQ()    - Метод обработки сообщений rabbitMQ
    // syncDivision()           - Метод синхронизации подразделений
    // createCompany()          - Метод создания компании
    // syncEmployee()           - Метод синхронизации персонала
    // syncGetAccountAD()       - Метод запроса синхронизации учетных записей персонала AD
    // syncAccountAD()          - Метод синхронизации/создания учетных записей персонала AD

    /** МЕТОДЫ СИНХРОНИЗАЦИИ СОБЫТИЙ ЗА РАЗ */
    // syncAllDepartment()      - Метод синхронизации всех событий в части ДЕПАРТАМЕНТА событий 1С
    // syncAllEmployee()        - Метод синхронизации всех событий в части ПЕРСОНАЛА событий 1С
    // syncAllAccountAD()       - Метод синхронизации/создания всех событий в части учетных записей персонала AD


    /**
     * Метод saveMessageRabbitMQ() - Метод обработки сообщений rabbitMQ
     * @param $queue_name - название очереди из которой идет синхронизация
     * @param $message - сообщение синхронизации
     * @return array
     */
    public static function saveMessageRabbitMQ($queue_name, $message): array
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("saveMessageRabbitMQ");

        try {
            $log->addLog("Начало выполнения метода");
            $log->addData($queue_name, '$queue_name:', __LINE__);
            $log->addData($message, '$message:', __LINE__);

            $rabbit = new RabbitMq();
            $rabbit->message = $message;
            $rabbit->queue_name = $queue_name;
            $rabbit->date_time_create = Assistant::GetDateNow();
            $rabbit->status = null;

            if (!$rabbit->save()) {
//                $log->addData($rabbit->errors, '$rabbit_errors', __LINE__);
                throw new Exception("Не смог сохранить модель RabbitMq");
            }
            $count_record++;

            $log->addLog("количество добавляемых записей: " . $count_record);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization($count_record);

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод syncDivision() - Метод синхронизации подразделений
     * алгоритм:
     *      получить последнюю дату синхронизации из справочника компаний
     *      получить новые данные из таблицы синхронизации для очереди DIVISION с последней даты синхронизации
     *      проверить наличие записей и если есть, то декодировать json из 1С
     *      проверить наличие данного подразделения в справочнике
     *      если его нет, то перейти к созданию, если есть, то перейти к изменению
     *      проверить наличие родителя в записи синхронизации (рекурсия)
     *      если есть родитель, то проверить его наличие в БД, если нет, то создать, иначе взять его настоящий айди
     *      создать компанию
     *      создать подразделение
     *      создать связь компании и подразделения
     *      посчитать запись
     *      записать лог
     *      перейти к другой записи
     * Тестовый метод: 127.0.0.1/synchronization-front/sync-division
     */
    public static function syncDivision(): array
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

//        ini_set('max_execution_time', -1);
//        ini_set('memory_limit', "10500M");

        // Стартовая отладочная информация
        $log = new LogAmicumFront("syncDivision");

        try {
            $log->addLog("Начало выполнения метода");

            // получить последнюю дату синхронизации из справочника компаний
            $maxDateTimeSync = (new Query())
                ->select('max(date_time_sync)')
                ->from('company')
                ->scalar();

            $log->addData($maxDateTimeSync, '$maxDateTimeSync:', __LINE__);

            if ($maxDateTimeSync) {
                $maxDateTimeSync = ['>', "date_time_create", $maxDateTimeSync];
            }
//            $maxDateTimeSync = ['=', "date_time_create", "2021-04-09 13:46:04"];

            // получить новые данные из таблицы синхронизации для очереди DIVISION с последней даты синхронизации
            $messages = (new Query())
                ->select('*')
                ->from("rabbit_mq")
                ->where($maxDateTimeSync)
                ->andWhere(['or',
                    ['queue_name' => 'division.EXAMPLE'],
                    ['queue_name' => 'division.СОУР.KZB'],
                    ['queue_name' => 'divison.СОУР.KZB']
                ])
                ->orderBy("date_time_create ASC")
                ->all();

//            $log->addData($messages, '$messages:', __LINE__);

            // проверить наличие записей и если есть, то декодировать json из 1С
            foreach ($messages as $message) {
                $division = json_decode($message['message']);

                if (!$division) {
                    $log->addData($message, '$message:', __LINE__);
                    $log->addData($division, '$division:', __LINE__);
                    throw new Exception("Ошибка декодирования json строки подразделения");
                }

                $response = self::createCompany($division, $message['date_time_create']);
                if ($response['status'] === 0) {
                    $log->addLogAll($response);
                    throw new Exception('Ошибка создания подразделения');
                }
                $count_record++;
            }

            // посчитать запись
            // записать лог
            // перейти к другой записи

            $log->addLog("количество обработанных записей: " . $count_record);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization($count_record);

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод createCompany() - Метод создания компании
     * @param $division - подразделение на создание
     * @param $date_time_create - дата синхронизации
     * @return array
     */
    public static function createCompany($division, $date_time_create): array
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_add_company = 0;                                                                                         // количество добавленных компаний
        $count_add_department = 0;                                                                                      // количество добавленных подразделений
        $count_add_comp_dep = 0;                                                                                        // количество добавленных связок департаментов и подразделений
        $count_edit = 0;                                                                                                // количество измененных записей

        $company_id = -1;

        // Стартовая отладочная информация
        $log = new LogAmicumFront("createCompany");

        try {
            $log->addLog("Начало выполнения метода");

//            $log->addData($division,'$division',__LINE__);

            // проверить наличие родителя в записи синхронизации (рекурсия)
            if (property_exists($division, 'Родитель') and $division->Родитель->Ссылка != "00000000-0000-0000-0000-000000000000") {
                $log->addLog("Есть родитель");
                // если есть родитель, то проверить его наличие в БД, если нет, то создать, иначе взять его настоящий айди
                $parent_company = Company::findOne(['link_1c' => $division->Родитель->Ссылка]);
                if ($parent_company) {
                    $log->addLog("Нашел родителя в справочнике");
                    $upper_company = $parent_company->id;
                } else {
                    $log->addData($division->Родитель->Ссылка, '$division->Родитель->Ссылка', __LINE__);
                    //throw new Exception('Ошибка очереди синхронизации');
                    $log->addLog("Родителя нет в справочнике - создаем");
                    $response = self::createCompany($division->Родитель, $date_time_create);
                    if ($response['status'] === 0) {
                        $log->addLogAll($response);
                        throw new Exception('Ошибка создания родительского подразделения');
                    }
                    $upper_company = $response['company_id'];
                }
            } else {
                $log->addLog("Родителя нет");

                // СОЗДАЮ КОМПАНИЮ
                $parent_company = null;
                if (property_exists($division, 'Владелец')) {
                    $parent_company = Company::findOne(['link_1c' => $division->Владелец->Ссылка]);
                    $title_new = trim($division->Владелец->Наименование);
                    $link_new = $division->Владелец->Ссылка;
                } else {
                    $title_new = trim($division->Наименование);
                    $link_new = $division->Ссылка;
                }

                if (!$parent_company) {
                    $log->addLog("компании не было в справочнике, создаю новую");
                    $parent_company = new Company();
                    $count_add_company++;
                    $parent_company->title = $title_new;
                    $parent_company->date_time_sync = $date_time_create;
                    $parent_company->link_1c = $link_new;

                    if (!$parent_company->save()) {
                        $log->addData($parent_company->errors, '$parrent_company_errors', __LINE__);
                        throw new Exception("Не смог сохранить модель Company");
                    }
                }
                $upper_company = $parent_company->id;
            }
            $log->addLog("Нашел ключ родителя: " . $upper_company);

            // проверить наличие данного подразделения в справочнике
            // если его нет, то перейти к созданию, если есть, то перейти к изменению

            // СОЗДАЮ КОМПАНИЮ
            $company = Company::findOne(['link_1c' => $division->Ссылка]);
            if (!$company) {
                $log->addLog("компании не было в справочнике, создаю новую");
                $company = new Company();
                $count_add_company++;
            }

            $company->title = trim($division->Наименование);
            $company->upper_company_id = $upper_company;
            $company->date_time_sync = $date_time_create;
            $company->link_1c = $division->Ссылка;

            if (!$company->save()) {
                $log->addData($company->errors, '$company_errors', __LINE__);
                throw new Exception("Не смог сохранить модель Company");
            }
            $count_edit++;

            $company_id = $company->id;

            // СОЗДАЮ ДЕПАРТАМЕНТ
            $department = Department::findOne(['id' => $company_id]);
            if (!$department) {
                $log->addLog("Департамента не было в справочнике, создаю новый");
                $department = new Department();
                $count_add_department++;
            }

            $department->id = $company_id;
            $department->title = $division->Наименование;

            if (!$department->save()) {
                $log->addData($department->errors, '$department_errors', __LINE__);
                throw new Exception("Не смог сохранить модель Department");
            }
            $count_edit++;

            $department_id = $department->id;

            // СОЗДАЮ СВЯЗКУ ДЕПАРТАМЕНТА И КОМПАНИИ
            $com_dep = CompanyDepartment::findOne(['id' => $company_id]);
            if (!$com_dep) {
                $com_dep = new CompanyDepartment();
                $com_dep->department_type_id = DepartmentTypeEnum::OTHER;
                $count_add_comp_dep++;
            }
            $com_dep->id = $company_id;
            $com_dep->department_id = $department_id;
            $com_dep->company_id = $company_id;

            if (!$com_dep->save()) {
                $log->addData($com_dep->errors, '$com_dep_errors', __LINE__);
                throw new Exception("Не смог сохранить модель CompanyDepartment");
            }
            $count_edit++;

            HandbookCachedController::clearDepartmentCache();

            $log->addLog("количество добавляемых компаний: " . $count_add_company);
            $log->addLog("количество добавляемых подразделений: " . $count_add_department);
            $log->addLog("количество добавляемых связок компаний и подразделений: " . $count_add_comp_dep);
            $log->addLog("количество измененных записей: " . $count_edit);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
            $log->addData($division, '$division', __LINE__);
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result, 'company_id' => $company_id], $log->getLogAll());
    }

    /**
     * Метод syncEmployee() - Метод синхронизации персонала
     * алгоритм:
     *      получить последнюю дату синхронизации из справочника компаний
     *      получить новые данные из таблицы синхронизации для очереди EMPLOYEE с последней даты синхронизации
     *      проверить наличие записей и если есть, то декодировать json из 1С
     *      посчитать запись
     *      записать лог
     *      перейти к другой записи
     * Тестовый метод: 127.0.0.1/synchronization-front/sync-employee
     */
    public static function syncEmployee(): array
    {
        $message = null;
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $people = null;                                                                                                 // обрабатываемое в текущий момент сообщение синхронизации
        $count_record = 0;                                                                                              // количество обработанных записей
        $count_add_employee = 0;                                                                                        // количество добавленных записей людей
        $count_add_worker = 0;                                                                                          // количество добавленных записей работников
        $count_add_worker_object = 0;                                                                                   // количество добавленных записей работников объектов
        $count_add_position = 0;                                                                                        // количество добавленных записей должностей
        $count_error_company = 0;                                                                                       // количество не найденных подразделений
        $count_fio_tabel = 0;                                                                                           // Нашел по ФИО и табельному номеру
        $count_fio_birthdate = 0;                                                                                       // Нашел по ФИО и дате рождения
        $count_link_1c = 0;                                                                                             // Нашел по ссылке 1с

//        ini_set('max_execution_time', -1);
//        ini_set('memory_limit', "10500M");

        // Стартовая отладочная информация
        $log = new LogAmicumFront("syncEmployee");

        try {
            $log->addLog("Начало выполнения метода");

            // получить последнюю дату синхронизации из справочника работников
            $maxDateTimeSync = (new Query())
                ->select('max(date_time_sync)')
                ->from('worker')
                ->scalar();

            $log->addData($maxDateTimeSync, '$maxDateTimeSync:', __LINE__);

            if ($maxDateTimeSync) {
                $maxDateTimeSync = ['>', "date_time_create", $maxDateTimeSync];
            }
//            $maxDateTimeSync = ['=', "date_time_create", "2021-04-09 13:46:04"];

            // получить новые данные из таблицы синхронизации для очереди employee.СОУР.KZB с последней даты синхронизации
            $messages = (new Query())
                ->select('*')
                ->from("rabbit_mq")
                ->where($maxDateTimeSync)
                ->andWhere(['or',
                    ['queue_name' => 'employee.EXAMPLE'],
                    ['queue_name' => 'employee.СОУР.KZB'],
                ])
                ->orderBy("date_time_create ASC")
                ->all();

//            $log->addData($messages, '$messages:', __LINE__);

            // проверить наличие записей и если есть, то декодировать json из 1С
            foreach ($messages as $message) {
                $people = json_decode($message['message']);

                if (!$people) {
                    $log->addData($message, '$message:', __LINE__);
                    $log->addData($people, '$people:', __LINE__);
                    throw new Exception("Ошибка декодирования json строки подразделения");
                }
                $date_time_create = $message['date_time_create'];

                // проверяем наличие подразделения у работника
                if ($people->Подразделение->Ссылка == "00000000-0000-0000-0000-000000000000") {
                    $comp_dep = Company::findOne(['link_1c' => $people->Организация->Ссылка]);
                    if (!$comp_dep) {
                        continue;
                        throw new Exception("Ошибка получения организации у работника");
                    }
                } else {
                    $comp_dep = Company::findOne(['link_1c' => $people->Подразделение->Ссылка]);
                    if (!$comp_dep) {
                        $count_error_company++;
                        continue;
                        throw new Exception("Ошибка получения подразделения у работника");
                    }
                }
                $comp_dep_id = $comp_dep->id;


                // разделить ФИО на Ф И О
                $fio = explode(" ", str_replace("  ", " ", $people->ФизическоеЛицо->Наименование));

                $j = 0;

                $f = "";            // фамилия
                $i = "";            // имя
                $o = "";            // отчество
                foreach ($fio as $item) {
                    switch ($j) {
                        case 0:
                            $f = $item;
                            break;
                        case 1:
                            $i = $item;
                            break;
                        default:
                            $o = $o . $item . " ";
                            break;
                    }
                    $j++;
                }
                if ($f == "" or $i == "") {
                    continue;
                    throw new Exception("Фамилия или Имя не может быть пустым");
                }

                /******************* Обновление строк в таблице Employee *******************/
                if (property_exists($people->ФизическоеЛицо, "ДатаРождения")) {
                    $date_birthdate = $people->ФизическоеЛицо->ДатаРождения;
                } else {
                    $date_birthdate = date("Y-m-d");
                }
                $date_birthdate = date("Y-m-d", strtotime($date_birthdate));
                $stuff_number = trim($people->ТабельныйНомер);

                $employee = Employee::findOne(['link_1c' => $people->ФизическоеЛицо->Ссылка]);

                if (!$employee) {
                    $employee = Employee::findOne(['birthdate' => $date_birthdate, 'last_name' => $f, 'first_name' => $i, 'patronymic' => $o]);
                    if ($employee) {
                        $count_fio_birthdate++;
                    }
                } else {
                    $count_link_1c++;
                }

                if (!$employee) {
                    $employee = Employee::find()
                        ->innerJoin('worker', 'worker.employee_id=employee.id')
                        ->where(['worker.tabel_number' => $stuff_number, 'last_name' => $f, 'first_name' => $i, 'patronymic' => $o])
                        ->one();
                    if ($employee) {
                        $count_fio_tabel++;
                    }
                }

                if (!$employee) {
                    $count_add_employee++;
                    $employee_id = Assistant::addMain('employee');
                    if ($employee_id == -1) {
                        throw new Exception("Ошибка создания главного id в таблице Main для Employee");
                    }

                    $employee = new Employee();
                    $employee->id = $employee_id;
                } else {
                    $employee_id = $employee->id;
                }

                $employee->last_name = $f;
                $employee->first_name = $i;
                $employee->patronymic = $o;
                $employee->birthdate = $date_birthdate;
                $employee->link_1c = $people->ФизическоеЛицо->Ссылка;
                $employee->date_time_sync = $date_time_create;
                if ($people->ФизическоеЛицо->Пол == "Мужской") {
                    $employee->gender = 'М';
                } else {
                    $employee->gender = 'Ж';
                }

                if (!$employee->save()) {
                    $log->addData($employee->errors, '$employee_errors', __LINE__);
                    throw new Exception("Не смог сохранить модель Employee");
                }

                /******************* Обновление/проверка строк в таблице должностей Position *******************/
                if ($people->Должность->Ссылка != "00000000-0000-0000-0000-000000000000" and $people->Должность->Ссылка != "") {
                    $position = Position::findOne(['link_1c' => $people->Должность->Ссылка]);
                    if (!$position) {
                        $count_add_position++;
                        $position = new Position();
                        $position->title = $people->Должность->Наименование;
                        $position->link_1c = $people->Должность->Ссылка;
                        $position->date_time_sync = $date_time_create;

                        if (!$position->save()) {
                            $log->addData($position->errors, '$position_errors', __LINE__);
                            throw new Exception("Не смог сохранить модель Position");
                        }
                    }

                    $position_id = $position->id;
                } else {
                    $position_id = 1;
                }

                /******************* Обновление строк в таблице Worker *******************/
                // ищем работника синхронизации
                $worker = Worker::findOne(['link_1c' => $people->Ссылка]);
                if (!$worker) {
                    $log->addData($people->Ссылка, 'Ссылки не было', __LINE__);
                    $worker = Worker::findOne(['employee_id' => $employee_id, 'position_id' => $position_id, 'company_department_id' => $comp_dep_id, 'date_start' => date("Y-m-d", strtotime($people->ДатаПриема))]);
                    if (!$worker) {
                        $count_add_worker++;
                        $worker_id = Assistant::addMain('worker');
                        if ($worker_id == -1) {
                            throw new Exception("Ошибка создания главного id в таблице Main для Worker");
                        }
                        $worker = new Worker();
                    } else {
                        $worker_id = $worker->id;
                    }
                } else {
                    $worker_old = Worker::findOne(['employee_id' => $employee_id, 'position_id' => $position_id, 'company_department_id' => $comp_dep_id, 'date_start' => date("Y-m-d", strtotime($people->ДатаПриема))]);
                    if ($worker_old and $worker_old->link_1c != $people->Ссылка) {

                        $worker->link_1c = "";
                        if (!$worker->save()) {
                            throw new Exception("Не смог сохранить модель Worker");
                        }
                        $worker = $worker_old;
                    }
                    $worker_id = $worker->id;
                }

                $worker->id = $worker_id;
                $worker->employee_id = $employee_id;
                $worker->tabel_number = $stuff_number;
                $worker->position_id = $position_id;
                $worker->company_department_id = $comp_dep_id;
                $worker->date_start = date("Y-m-d", strtotime($people->ДатаПриема));
                $worker->date_end = $people->ДатаУвольнения == "0001-01-01T00:00:00" ? date("Y-m-d", strtotime("2099-12-31")) : date("Y-m-d", strtotime($people->ДатаУвольнения));
                $worker->link_1c = $people->Ссылка;
                $worker->date_time_sync = $date_time_create;
                if (!$worker->save() and $people->ДатаПриема != "0001-01-01T00:00:00") {
                    $log->addData($worker->errors, '$worker_errors', __LINE__);
                    throw new Exception("Не смог сохранить модель Worker");
                }


                /******************* Обновление строк в таблице WorkerObject1 *******************/
                $worker_object = WorkerObject::findOne(['worker_id' => $worker_id]);
                if (!$worker_object) {
                    $count_add_worker_object++;
                    $worker_object = new WorkerObject();
                }
                $worker_object->id = $worker_id;
                $worker_object->worker_id = $worker_id;
                $worker_object->object_id = 25;
                if (!$worker_object->save()) {
                    $log->addData($worker_object->errors, '$worker_object_errors', __LINE__);
                    throw new Exception("Не смог сохранить модель WorkerObject");
                }
                $count_record++;
//                throw new Exception("Отладочный стоп");
            }

            // посчитать запись
            // записать лог
            // перейти к другой записи

            HandbookCachedController::clearWorkerCache();


        } catch (Throwable $ex) {
            $log->addData($message, '$message', __LINE__);
            $log->addData($people, '$people', __LINE__);
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("количество обработанных записей: " . $count_record);
        $log->addLog("количество добавленных записей людей: " . $count_add_employee);
        $log->addLog("количество добавленных записей работников: " . $count_add_worker);
        $log->addLog("количество добавленных записей работников объектов: " . $count_add_worker_object);
        $log->addLog("количество добавленных записей должностей: " . $count_add_position);
        $log->addLog("количество не найденных подразделений: " . $count_error_company);
        $log->addLog("Нашел по ФИО и табельному номеру: " . $count_fio_tabel);
        $log->addLog("Нашел по ФИО и дате рождения: " . $count_fio_birthdate);
        $log->addLog("Нашел по ссылке 1с: " . $count_link_1c);

        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization($count_record);

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод syncGetAccountAD() - Метод запроса синхронизации учетных записей персонала AD
     * получить список работников - их ссылок,
     * создать подключение к RabbitMQ
     * перебирая ссылки всех работников направить сообщения в rpc на обработку
     * Тестовый метод: 127.0.0.1/synchronization-front/sync-get-account-ad
     */
    public static function syncGetAccountAD(): array
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_row = 0;
        $log = new LogAmicumFront("syncGetAccountAD");

        try {
            $log->addLog("Начало выполнения метода");

            $workers = Worker::find()
                ->select('id, link_1c')
                ->where('link_1c is not null and link_1c!=""')
                ->asArray()
                ->all();
            if ($workers) {
                $config = [
                    'host' => '10.0.18.136',
                    'port' => 5672,
                    'user' => 'ykuz_external',
                    'password' => 'deukecrh',
                    'exchangeName' => 'amicum.ad',
                    'queueName' => 'rpc.it.sdesk',
                    'replyQueueName' => 'amicum.ad',
                    'vhost' => 'staging',
                    'durable_queue' => 1,
                    'auto_delete' => 4,
                    'dsn' => 'amqp://ykuz_external:deukecrh@10.0.18.136:5672/staging',
                ];
                $log->addData($config, '$config', __LINE__);
                $rabbit = new RabbitController($config);
            }
            foreach ($workers as $worker) {
                $count_row++;
                $payload = '<FixedStructure xmlns="http://v8.1c.ru/8.1/data/core" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                            <Property name="method">
                                <Value xsi:type="xs:string">get_user_info</Value>
                            </Property>
                            <Property name="params">
                                <Value xsi:type="Structure">
                                    <Property name="employeeGUID">
                                        <Value xsi:type="xs:string">' . $worker['link_1c'] . '</Value>
                                    </Property>
                                </Value>
                            </Property>
                        </FixedStructure>';

                $message = [
                    'method' => 'get_user_info',
                    'correlation_id' => $worker['id'],
                    'payload' => $payload
                ];

                $result = $rabbit->pushMessage($message, 0, 0, 1);
            }
            $log->addLog("Обработал записей: $count_row");
            $log->addLog("Окончание выполнения метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод syncAccountAD() - Метод синхронизации/создания учетных записей персонала AD
     * получить данные из таблицы rabbit_mq по очереди amicum.ad
     * распарсить xml
     * преобразовать нужные данные из json
     * если сообщение содержит данные пользователя, то создать его
     * Тестовый метод: 127.0.0.1/synchronization-front/sync-account-ad
     */
    public static function syncAccountAD(): array
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_add = 0;                                                                                                 // количество добавленных строк
        $count_upd = 0;                                                                                                 // количество обновленных строк
        $count_row = 0;                                                                                                 // общее количество обработанных строк
        $count_error = 0;                                                                                               // количество строк обработанных с ошибкой - не смог распарстить json
        $count_left = 0;                                                                                                // количество левых пакетов
        $count_link_1c_error = 0;                                                                                       // количество не найденных работников по ссылке 1с
        $pass_to_insert = [];
        $log = new LogAmicumFront("syncAccountAD");

        try {
            $log->addLog("Начало выполнения метода");

            // получить последнюю дату синхронизации из справочника пользователей
            $maxDateTimeSync = (new Query())
                ->select('max(date_time_sync)')
                ->from('user')
                ->scalar();

            $log->addData($maxDateTimeSync, '$maxDateTimeSync:', __LINE__);

            if ($maxDateTimeSync) {
                $maxDateTimeSync = ['>', "date_time_create", $maxDateTimeSync];
            }
//            $maxDateTimeSync = ['=', "date_time_create", "2021-04-09 13:46:04"];

            // получить новые данные из таблицы синхронизации для очереди amicum.ad с последней даты синхронизации
            $messages = (new Query())
                ->select('*')
                ->from("rabbit_mq")
                ->where($maxDateTimeSync)
                ->andWhere(['or',
                    ['queue_name' => 'amicum.ad'],
                ])
                ->orderBy("date_time_create ASC")
                ->all();

//            $log->addData($messages, '$messages:', __LINE__);

            // проверить наличие записей и если есть, то декодировать json из 1С
            foreach ($messages as $message) {
                $count_row++;
                $xml = new SimpleXMLElement($message['message']);
                $date_time_create = $message['date_time_create'];

//                $log->addData($xml,'$xml',__LINE__);

                $log->addData($xml->Property[1]->attributes()->name, '$attributes', __LINE__);
                if (property_exists($xml, 'Property') and
                    isset($xml->Property[1]) and
                    $xml->Property[1]->attributes()->name == "result" and
                    $xml->Property[1]->Value->Property[0]->attributes()->name == 'status' and
                    $xml->Property[1]->Value->Property[0]->Value != 'error'
                ) {
                    $json_raw = $xml->Property[1]->Value->Property[1]->Value;

//                    $log->addData($json_raw, '$json_raw', __LINE__);

                    $json = json_decode($json_raw);

//                    $log->addData($json, '$json', __LINE__);

                    if ($json) {
                        $link_1c = $json->Сотрудники[0]->Сотрудник->Ссылка;
                        $login_ad = $json->ДанныеAD->PrincipalName;
                        $email = $json->ЭлектроннаяПочта;

                        $worker = Worker::findOne(['link_1c' => $link_1c]);
                        if ($worker and $login_ad) {
                            $worker_id = $worker->id;

                            $flag = false;                                                                              // флаг, true - новый пользователь, false - найденный
                            $count_row++;
                            $new_data_for_user = User::find()
                                ->where(['worker_id' => $worker_id])
                                ->orWhere(['login' => $login_ad])
                                ->one();
                            if (empty($new_data_for_user)) {
                                $new_data_for_user = new User();

                                $new_data_for_user->workstation_id = 12;                                                // гостевая
                                $new_data_for_user->default = 1;
                                $new_data_for_user->worker_id = $worker_id;
                                $flag = true;
                            }
                            $new_data_for_user->login = $login_ad;                                       // логином будет логин от AD точнее то что идёт после указания сервера
                            $new_data_for_user->email = $email;
                            $new_data_for_user->user_ad_id = $login_ad;
                            $new_data_for_user->props_ad_upd = $email;
                            $new_data_for_user->date_time_sync = $date_time_create;
                            if (!$new_data_for_user->save()) {
                                $log->addData($json, '$json', __LINE__);
                                $log->addData($worker_id, '$worker_id', __LINE__);
                                $log->addData($new_data_for_user->errors, '$new_data_for_user->errors', __LINE__);
                                throw new Exception('Ошибка при изменении данных по работнику');
                            }
                            $count_upd++;
                            $new_data_for_user->refresh();
                            $user_id = $new_data_for_user->id;
                            $user_login = $new_data_for_user->login;
                            unset($new_data_for_user);
                            if ($flag) {
                                $guest_pass = ' KbgbplhbxtcndjT,extt{thGjl,thti';
                                $pass = crypt($guest_pass, '$5$rounds=5000$' . dechex(crc32($user_login)) . '$') . "\n";        //Выполнить хеширование пароля методом SHA-256
                                $check_sum = dechex(crc32($guest_pass));
                                $date = date('Y-m-d H:i:s');
                                $pass_to_insert[] = [
                                    $user_id,
                                    $date,
                                    $pass,
                                    $check_sum
                                ];
                                $count_add++;
                            }
                            if ($count_add == 2000) {
                                if (!empty($pass_to_insert)) {
                                    $log->addLog("Количество добавленных записей: " . $count_add);

                                    $batch_inserted = Yii::$app->db->createCommand()
                                        ->batchInsert('user_password', ['user_id', 'date_time', 'password', 'check_sum'], $pass_to_insert)
                                        ->execute();
                                    if ($batch_inserted == 0) {
                                        throw new Exception('Ошибка при сохранении массива паролей');
                                    }
                                    unset($batch_inserted);
                                    $pass_to_insert = array();
                                    $count_add = 0;
                                }
                            }
                        } else {
                            $count_link_1c_error++;
                            $log->addData($link_1c, '$link_1c', __LINE__);
                            $log->addData($login_ad, '$login_ad', __LINE__);
                            $log->addData($email, '$email', __LINE__);
                        }
                    } else {
                        $count_error++;
                        $log->addData($json_raw, '$json_raw', __LINE__);
                    }
                } else {
                    $count_left++;
                }
            }

            unset($users_update_data);
            unset($workers);
            unset($new_user_data);

            $log->addLog("Добавили данные для пользователей / Создали новых пользователей, добавили основный массив паролей");

            if (!empty($pass_to_insert)) {
                $log->addLog("Количество добавленных записей: " . $count_add);
                $batch_inserted = Yii::$app->db->createCommand()
                    ->batchInsert('user_password', ['user_id', 'date_time', 'password', 'check_sum'], $pass_to_insert)
                    ->execute();
                if ($batch_inserted == 0) {
                    throw new Exception('Ошибка при сохранении массива паролей');
                }
                unset($pass_to_insert);
            }

            $log->addLog("Добавили остатки паролей");


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Обработал записей: $count_row");
        $log->addLog("Ошибка распарсивания json записей: $count_error");
        $log->addLog("Количество не найденных работников по ссылке 1с: $count_link_1c_error");
        $log->addLog("Количество обновленных записей: $count_upd");
        $log->addLog("Количество левых пакетов: $count_left");
        $log->addLog("Окончание выполнения метода");
        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * Метод syncAllDepartment() - Метод синхронизации всех событий в части ДЕПАРТАМЕНТА событий 1С
     * Тестовый метод: 127.0.0.1/synchronization-front/sync-all
     */
    public static function syncAllDepartment(): array
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

//        ini_set('max_execution_time', -1);
//        ini_set('memory_limit', "10500M");

        // Стартовая отладочная информация
        $log = new LogAmicumFront("syncAllDepartment");

        try {
            $log->addLog("Начало выполнения метода");

            // получить последнюю дату синхронизации из справочника компаний
            $maxDateTimeSync = (new Query())
                ->select('max(date_time_sync)')
                ->from('company')
                ->scalar();

            $log->addData($maxDateTimeSync, '$maxDateTimeSync:', __LINE__);

            if ($maxDateTimeSync) {
                $maxDateTimeSync = ['>', "date_time_create", $maxDateTimeSync];
            }
//            $maxDateTimeSync = ['=', "date_time_create", "2021-04-09 13:46:04"];

            // получить новые данные из таблицы синхронизации для очереди DIVISION с последней даты синхронизации
            $messages = (new Query())
                ->select('*')
                ->from("rabbit_mq")
                ->where($maxDateTimeSync)
                ->andWhere(['queue_name' => 'hrm_masterdata.ewb'])
                ->andWhere('message like "{\r\n__class_: _department_%"')
                ->orderBy("date_time_create ASC")
                ->all();
            //$log->addData($messages, '$messages:', __LINE__);


            // проверить наличие записей и если есть, то декодировать json из 1С
            foreach ($messages as $message) {
                $division = json_decode($message['message']);
//                $log->addData($division, '$division:', __LINE__);

                if (!$division) {
                    $log->addData($message, '$message:', __LINE__);
                    $log->addData($division, '$division:', __LINE__);
                    throw new Exception("Ошибка декодирования json строки подразделения");
                }

                $response = self::createCompany($division, $message['date_time_create']);
                if ($response['status'] === 0) {
                    $log->addLogAll($response);
                    throw new Exception('Ошибка создания подразделения');
                }
                $count_record++;
            }

            // посчитать запись
            // записать лог
            // перейти к другой записи

            $log->addLog("количество обработанных записей: " . $count_record);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization($count_record);

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод syncAllEmployee() - Метод синхронизации всех событий в части ПЕРСОНАЛА событий 1С
     * Тестовый метод: 127.0.0.1/synchronization-front/sync-all
     */
    public static function syncAllEmployee(): array
    {
        $message = null;
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $people = null;                                                                                                 // обрабатываемое в текущий момент сообщение синхронизации
        $count_record = 0;                                                                                              // количество обработанных записей
        $count_add_employee = 0;                                                                                        // количество добавленных записей людей
        $count_add_worker = 0;                                                                                          // количество добавленных записей работников
        $count_add_worker_object = 0;                                                                                   // количество добавленных записей работников объектов
        $count_add_position = 0;                                                                                        // количество добавленных записей должностей

//        ini_set('max_execution_time', -1);
//        ini_set('memory_limit', "10500M");

        // Стартовая отладочная информация
        $log = new LogAmicumFront("syncAllEmployee", true);

        try {
            $log->addLog("Начало выполнения метода");

            // получить последнюю дату синхронизации из справочника работников
            $maxDateTimeSync = (new Query())
                ->select('max(date_time_sync)')
                ->from('worker')
                ->scalar();

            $log->addData($maxDateTimeSync, '$maxDateTimeSync:', __LINE__);

            if ($maxDateTimeSync) {
                $maxDateTimeSync = ['>', "date_time_create", $maxDateTimeSync];
            }
//            $maxDateTimeSync = ['=', "date_time_create", "2021-04-09 13:46:04"];

            // получить новые данные из таблицы синхронизации для очереди employee.СОУР.KZB с последней даты синхронизации
            $messages = (new Query())
                ->select('*')
                ->from("rabbit_mq")
                ->where($maxDateTimeSync)
                ->andWhere(['queue_name' => 'hrm_masterdata.ewb'])
                ->andWhere('message like "{\r\n__class_: _employee_%"')
                ->orderBy("date_time_create ASC")
                ->all();

//            $log->addData($messages, '$messages:', __LINE__);

            // проверить наличие записей и если есть, то декодировать json из 1С
            foreach ($messages as $message) {
                $comp_dep = null;
                $people = json_decode($message['message']);

                if (!$people) {
                    $log->addData($message, '$message:', __LINE__);
                    $log->addData($people, '$people:', __LINE__);
                    throw new Exception("Ошибка декодирования json строки подразделения");
                }
                $date_time_create = $message['date_time_create'];

                // проверяем наличие подразделения у работника
                if ($people->Подразделение->Ссылка == "00000000-0000-0000-0000-000000000000" or !Company::findOne(['link_1c' => $people->Подразделение->Ссылка])) {
                    $comp_dep = Company::findOne(['link_1c' => $people->Организация->Ссылка]);
                    if (!$comp_dep) {
                        throw new Exception("Ошибка получения организации у работника");
                    }

                    $department = Department::findOne(['id' => $comp_dep->id]);
                    if (!$department) {

                        $department = new Department();
                        $department->id = $comp_dep->id;
                        $department->title = $comp_dep->title;

                        if (!$department->save()) {
                            $log->addData($department->errors, '$department_errors', __LINE__);
                            throw new Exception("Не смог сохранить модель Department");
                        }

                        // СОЗДАЮ СВЯЗКУ ДЕПАРТАМЕНТА И КОМПАНИИ
                        $company_department = new CompanyDepartment();
                        $company_department->id = $comp_dep->id;
                        $company_department->company_id = $comp_dep->id;
                        $company_department->department_id = $comp_dep->id;
                        $company_department->department_type_id = DepartmentTypeEnum::OTHER;

                        if (!$company_department->save()) {
                            $log->addData($company_department->errors, '$com_dep_errors', __LINE__);
                            throw new Exception("Не смог сохранить модель CompanyDepartment");
                        }
                        $department = NULL;
                        $company_department = NULL;
                    }
                } else {
                    $comp_dep = Company::findOne(['link_1c' => $people->Подразделение->Ссылка]);
                    if (!$comp_dep) {
                        $log->addData($people, '$people:', __LINE__);
                        throw new Exception("Ошибка получения подразделения у работника");
                    }
                }
                $comp_dep_id = $comp_dep->id;


                // разделить ФИО на Ф И О
                if ($people->ФизическоеЛицо->Наименование) {
                    $fio = explode(" ", str_replace("  ", " ", $people->ФизическоеЛицо->Наименование));
                } else {
                    $fio = explode(" ", str_replace("  ", " ", $people->Наименование));
                }

                $j = 0;

                $f = "";            // фамилия
                $i = "";            // имя
                $o = "";            // отчество
                foreach ($fio as $item) {
                    switch ($j) {
                        case 0:
                            $f = $item;
                            break;
                        case 1:
                            $i = $item;
                            break;
                        default:
                            $o = $o . $item . " ";
                            break;
                    }
                    $j++;
                }
                if ($f == "" or $i == "") {
                    continue;
//                    throw new Exception("Фамилия или Имя не может быть пустым");
                }

                /******************* Обновление строк в таблице Employee *******************/
                $employee = Employee::findOne(['link_1c' => $people->ФизическоеЛицо->Ссылка]);
                if (!$employee) {
                    $count_add_employee++;
                    $employee_id = Assistant::addMain('employee');
                    if ($employee_id == -1) {
                        throw new Exception("Ошибка создания главного id в таблице Main для Employee");
                    }

                    $employee = new Employee();
                    $employee->id = $employee_id;
                } else {
                    $employee_id = $employee->id;
                }

                $employee->last_name = $f;
                $employee->first_name = $i;
                $employee->patronymic = $o;
                if (property_exists($people->ФизическоеЛицо, "ДатаРождения")) {
                    $date_birthdate = $people->ФизическоеЛицо->ДатаРождения;
                } else {
                    $date_birthdate = date("Y-m-d");
                }
                $employee->birthdate = date("Y-m-d", strtotime($date_birthdate));
                $employee->link_1c = $people->ФизическоеЛицо->Ссылка;
                $employee->date_time_sync = $date_time_create;
                if ($people->ФизическоеЛицо->Пол == "Мужской") {
                    $employee->gender = 'М';
                } else {
                    $employee->gender = 'Ж';
                }

                if (!$employee->save()) {
                    $log->addData($employee->errors, '$employee_errors', __LINE__);
                    throw new Exception("Не смог сохранить модель Employee");
                }

                /******************* Обновление/проверка строк в таблице должностей Position *******************/
                if ($people->Должность->Ссылка != "00000000-0000-0000-0000-000000000000" and $people->Должность->Ссылка != "") {
                    $position = Position::findOne(['link_1c' => $people->Должность->Ссылка]);
                    if (!$position) {
                        $count_add_position++;
                        $position = new Position();
                        $position->title = $people->Должность->Наименование;
                        $position->link_1c = $people->Должность->Ссылка;
                        $position->date_time_sync = $date_time_create;

                        if (!$position->save()) {
                            $log->addData($position->errors, '$position_errors', __LINE__);
                            throw new Exception("Не смог сохранить модель Position");
                        }
                    }

                    $position_id = $position->id;
                } else {
                    $position_id = 1;
                }

                /******************* Обновление строк в таблице Worker *******************/
                // ищем работника синхронизации
//                $worker = Worker::findOne(['link_1c' => $people->Ссылка]);
//                if (!$worker) {
//                    $worker = Worker::findOne(['employee_id' => $employee_id, 'position_id' => $position_id, 'company_department_id' => $comp_dep_id, 'date_start' => date("Y-m-d", strtotime($people->ДатаПриема)),]);
//                    if (!$worker) {
//                        $count_add_worker++;
//                        $worker_id = Assistant::addMain('worker');
//                        if ($worker_id == -1) {
//                            throw new Exception("Ошибка создания главного id в таблице Main для Worker");
//                        }
//                        $worker = new Worker();
//                    } else {
//                        $worker_id = $worker->id;
//                    }
//                } else {
//                    $worker_id = $worker->id;
//                }

                $worker = Worker::findOne(['link_1c' => $people->Ссылка]);
                if (!$worker) {
                    $log->addData($people->Ссылка, 'Ссылки не было', __LINE__);
                    $worker = Worker::findOne(['employee_id' => $employee_id, 'position_id' => $position_id, 'company_department_id' => $comp_dep_id, 'date_start' => date("Y-m-d", strtotime($people->ДатаПриема))]);
                    if (!$worker) {
                        $count_add_worker++;
                        $worker_id = Assistant::addMain('worker');
                        if ($worker_id == -1) {
                            throw new Exception("Ошибка создания главного id в таблице Main для Worker");
                        }
                        $worker = new Worker();
                    } else {
                        $worker_id = $worker->id;
                    }
                } else {
                    $worker_old = Worker::findOne(['employee_id' => $employee_id, 'position_id' => $position_id, 'company_department_id' => $comp_dep_id, 'date_start' => date("Y-m-d", strtotime($people->ДатаПриема))]);
                    if ($worker_old and $worker_old->link_1c != $people->Ссылка) {

                        $worker->link_1c = "";
                        if (!$worker->save()) {
                            throw new Exception("Не смог сохранить модель Worker");
                        }
                        $worker = $worker_old;
                    }
                    $worker_id = $worker->id;
                }


                $worker->id = $worker_id;
                $worker->employee_id = $employee_id;
                $worker->tabel_number = trim($people->ТабельныйНомер) ? trim($people->ТабельныйНомер) : (string)$employee_id;
                $worker->position_id = $position_id;
                $worker->company_department_id = $comp_dep_id;
                $worker->date_start = date("Y-m-d", strtotime($people->ДатаПриема));
                $worker->date_end = $people->ДатаУвольнения == "0001-01-01T00:00:00" ? date("Y-m-d", strtotime("2099-12-31")) : date("Y-m-d", strtotime($people->ДатаУвольнения));
                $worker->link_1c = $people->Ссылка;
                $worker->date_time_sync = $date_time_create;
                if (!$worker->save() and $people->ДатаПриема != "0001-01-01T00:00:00") {
                    $log->addData($worker->errors, '$worker_errors', __LINE__);
                    throw new Exception("Не смог сохранить модель Worker");
                }


                /******************* Обновление строк в таблице WorkerObject1 *******************/
                $worker_object = WorkerObject::findOne(['worker_id' => $worker_id]);
                if (!$worker_object) {
                    $count_add_worker_object++;
                    $worker_object = new WorkerObject();
                }
                $worker_object->id = $worker_id;
                $worker_object->worker_id = $worker_id;
                $worker_object->object_id = 25;
                $worker_object->role_id = 9;
                if (!$worker_object->save()) {
                    $log->addData($worker_object->errors, '$worker_object_errors', __LINE__);
                    throw new Exception("Не смог сохранить модель WorkerObject");
                }
                $count_record++;
            }

            // посчитать запись
            // записать лог
            // перейти к другой записи

            HandbookCachedController::clearWorkerCache();

            $log->addLog("количество обработанных записей: " . $count_record);
            $log->addLog("количество добавленных записей людей: " . $count_add_employee);
            $log->addLog("количество добавленных записей работников: " . $count_add_worker);
            $log->addLog("количество добавленных записей работников объектов: " . $count_add_worker_object);
            $log->addLog("количество добавленных записей должностей: " . $count_add_position);
        } catch (Throwable $ex) {
            $log->addData($message, '$message', __LINE__);
            $log->addData($people, '$people', __LINE__);
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization($count_record);

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод syncAllAccountAD() - Метод синхронизации/создания всех событий в части учетных записей персонала AD
     * Тестовый метод: 127.0.0.1/synchronization-front/sync-get-account-ad
     */
    public static function syncAllAccountAD(): array
    {
//        ini_set('max_execution_time', -1);
//        ini_set('memory_limit', "10500M");
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_add = 0;                                                                                                 // количество добавленных строк
        $count_upd = 0;                                                                                                 // количество обновленных строк
        $count_row = 0;                                                                                                 // общее количество обработанных строк
        $count_error = 0;                                                                                               // количество строк обработанных с ошибкой - не смог распарстить json
        $count_left = 0;                                                                                                // количество левых пакетов
        $count_link_1c_error = 0;                                                                                       // количество не найденных работников по ссылке 1с
        $pass_to_insert = [];
        $log = new LogAmicumFront("syncAllAccountAD", true);

        try {
            $log->addLog("Начало выполнения метода");

            // получить последнюю дату синхронизации из справочника пользователей
            $maxDateTimeSync = (new Query())
                ->select('max(date_time_sync)')
                ->from('user')
                ->scalar();

            $log->addData($maxDateTimeSync, 'syncAllAccountAD.$maxDateTimeSync:', __LINE__);

            if ($maxDateTimeSync) {
                $maxDateTimeSync = ['>', "date_time_create", $maxDateTimeSync];
            }
//            $maxDateTimeSync = ['=', "date_time_create", "2021-04-09 13:46:04"];

            // получить новые данные из таблицы синхронизации для очереди amicum.ad с последней даты синхронизации
            $messages = (new Query())
                ->select('*')
                ->from("rabbit_mq")
                ->where($maxDateTimeSync)
                ->andWhere(['queue_name' => 'ito_masterdata.ewb'])
                ->andWhere('message like "{\r\n__class_: _activeDirectoryUser_%"')
                ->orderBy("date_time_create ASC")
                ->all();

//            $log->addData($messages, '$messages:', __LINE__);

            // проверить наличие записей и если есть, то декодировать json из 1С
            foreach ($messages as $message) {
                $count_row++;
                $date_time_create = $message['date_time_create'];

//                $log->addData($xml,'$xml',__LINE__);


                $json = json_decode($message['message']);

//                    $log->addData($json, '$json', __LINE__);

                if ($json and count($json->employees) and isset($json->employees[0])) {
                    $link_1c = $json->employees[0]->Ссылка;
                    $login_ad = $json->sAMAccountName;
                    $email = "";

                    $worker = Worker::findOne(['link_1c' => $link_1c]);
                    if ($worker and $login_ad) {
                        $worker_id = $worker->id;

                        $flag = false;                                                                              // флаг, true - новый пользователь, false - найденный
                        $new_data_for_user = User::find()
                            ->where(['worker_id' => $worker_id])
                            ->orWhere(['login' => $login_ad])
                            ->one();
                        if (empty($new_data_for_user)) {
                            $new_data_for_user = new User();

                            $new_data_for_user->workstation_id = 12;                                                // гостевая
                            $new_data_for_user->default = 1;
                            $new_data_for_user->worker_id = $worker_id;
                            $flag = true;
                        }
                        $new_data_for_user->login = $login_ad;                                       // логином будет логин от AD точнее то что идёт после указания сервера
                        $new_data_for_user->email = $email;
                        $new_data_for_user->user_ad_id = $login_ad;
                        $new_data_for_user->props_ad_upd = $email;
                        $new_data_for_user->date_time_sync = $date_time_create;
                        if (!$new_data_for_user->save()) {
                            $log->addData($json, '$json', __LINE__);
                            $log->addData($worker_id, '$worker_id', __LINE__);
                            $log->addData($new_data_for_user->errors, '$new_data_for_user->errors', __LINE__);
                            throw new Exception('Ошибка при изменении данных по работнику');
                        }
                        $count_upd++;
                        $new_data_for_user->refresh();
                        $user_id = $new_data_for_user->id;
                        $user_login = $new_data_for_user->login;
                        unset($new_data_for_user);
                        if ($flag) {
                            $guest_pass = ' KbgbplhbxtcndjT,extt{thGjl,thti';
                            $pass = crypt($guest_pass, '$5$rounds=5000$' . dechex(crc32($user_login)) . '$') . "\n";        //Выполнить хеширование пароля методом SHA-256
                            $check_sum = dechex(crc32($guest_pass));
                            $date = date('Y-m-d H:i:s');
                            $pass_to_insert[] = [
                                $user_id,
                                $date,
                                $pass,
                                $check_sum
                            ];
                            $count_add++;
                        }
                        if ($count_add == 2000) {
                            if (!empty($pass_to_insert)) {
                                $log->addLog("Количество добавленных записей: " . $count_add);

                                $batch_inserted = Yii::$app->db->createCommand()
                                    ->batchInsert('user_password', ['user_id', 'date_time', 'password', 'check_sum'], $pass_to_insert)
                                    ->execute();
                                if ($batch_inserted == 0) {
                                    throw new Exception('Ошибка при сохранении массива паролей');
                                }
                                unset($batch_inserted);
                                $pass_to_insert = array();
                                $count_add = 0;
                            }
                        }
                    } else {
                        $count_link_1c_error++;
//                        $log->addData($link_1c, '$link_1c', __LINE__);
//                        $log->addData($login_ad, '$login_ad', __LINE__);
//                        $log->addData($email, '$email', __LINE__);
                    }
                } else {
                    $count_error++;
                    $log->addData($json, '$json', __LINE__);
                }

            }

            unset($users_update_data);
            unset($workers);
            unset($new_user_data);

            $log->addLog("Добавили данные для пользователей / Создали новых пользователей, добавили основный массив паролей");

            if (!empty($pass_to_insert)) {
                $log->addLog("Количество добавленных записей: " . $count_add);
                $batch_inserted = Yii::$app->db->createCommand()
                    ->batchInsert('user_password', ['user_id', 'date_time', 'password', 'check_sum'], $pass_to_insert)
                    ->execute();
                if ($batch_inserted == 0) {
                    throw new Exception('Ошибка при сохранении массива паролей');
                }
                unset($pass_to_insert);
            }

            $log->addLog("Добавили остатки паролей");


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Обработал записей: $count_row");
        $log->addLog("Ошибка распарсивания json записей: $count_error");
        $log->addLog("Количество не найденных работников по ссылке 1с: $count_link_1c_error");
        $log->addLog("Количество обновленных записей: $count_upd");
        $log->addLog("Количество левых пакетов: $count_left");
        $log->addLog("Окончание выполнения метода");
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод SyncAll() - синхронизация всех событий из 1С RabbitMQ Колмар
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/sync-all
     * @author Якимов М.Н.
     * Created date: on 08.03.2022
     */
    public function SyncAll()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionSyncAll", true);
        try {
            $response = SyncFromRabbitMQController::syncAllDepartment();
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка синхронизации департаментов');
            }
            $response = SyncFromRabbitMQController::syncAllEmployee();
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка синхронизации персонала');
            }
            $response = SyncFromRabbitMQController::syncAllAccountAD();
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка синхронизации учетных записей пользователей');
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->saveLogSynchronization();

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}


