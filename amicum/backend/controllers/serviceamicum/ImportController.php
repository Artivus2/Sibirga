<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\serviceamicum;

use backend\controllers\Assistant;
use backend\controllers\const_amicum\DepartmentTypeEnum;
use Exception;
use frontend\controllers\HandbookCachedController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Company;
use frontend\models\CompanyDepartment;
use frontend\models\Department;
use frontend\models\Employee;
use frontend\models\Position;
use frontend\models\Worker;
use frontend\models\WorkerObject;
use Throwable;

class ImportController
{
    // ImportEmployeeFromExcel()         - Метод импорта данных о работниках из Excel


    /**
     * Метод ImportEmployeeFromExcel() - Метод импорта данных о работниках из Excel
     * Тестовый метод: 127.0.0.1/synchronization-front/import-employee-from-excel
     * 127.0.0.1/read-manager-amicum?controller=SynchronizationFront&method=ImportEmployeeFromExcel&subscribe=&data={}
     */
    public static function ImportEmployeeFromExcel($data_post)
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
        $log = new LogAmicumFront("ImportEmployeeFromExcel", true);

        try {
            $log->addLog("Начало выполнения метода");
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }

            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных

            if (!property_exists($post_dec, 'employees'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $employees_excel = $post_dec->employees;

            $log->addLog("Получил данные с фронта");

            $comp_dep = Company::findOne(['title' => "Импорт"]);
            if (!$comp_dep) {

                $company = new Company();
                $company->title = "Импорт";

                if (!$company->save()) {
                    $log->addData($company->errors, '$company_errors', __LINE__);
                    throw new Exception("Не смог сохранить модель Company");
                }

                $department = new Department();
                $department->id = $company->id;
                $department->title = "Импорт";

                if (!$department->save()) {
                    $log->addData($department->errors, '$department_errors', __LINE__);
                    throw new Exception("Не смог сохранить модель Department");
                }

                // СОЗДАЮ СВЯЗКУ ДЕПАРТАМЕНТА И КОМПАНИИ
                $company_department = new CompanyDepartment();
                $company_department->id = $company->id;
                $company_department->company_id = $company->id;
                $company_department->department_id = $company->id;
                $company_department->department_type_id = DepartmentTypeEnum::OTHER;

                if (!$company_department->save()) {
                    $log->addData($company_department->errors, '$com_dep_errors', __LINE__);
                    throw new Exception("Не смог сохранить модель CompanyDepartment");
                }

                $comp_dep_id = $company_department->id;

                $company = NULL;
                $department = NULL;
                $company_department = NULL;
                $log->addLog("Компанию импорт создал");
            } else {
                $comp_dep_id = $comp_dep->id;
                $comp_dep = NULL;
                $log->addLog("Компания импорт была");
            }

            $log->addLog("Количество добавляемых записей: " . count($employees_excel));

            // проверить наличие записей и если есть, то декодировать json из 1С
            foreach ($employees_excel as $employee_excel) {
                $dataWorkStart = date("Y-m-d", strtotime($employee_excel->dataWorkStart));
                $fio_full = $employee_excel->fio;
                $staffNumber = $employee_excel->staffNumber;
                $positionTitle = $employee_excel->positionTitle;
                $companyTitle = $employee_excel->companyTitle;
                $birthday = date("Y-m-d", strtotime($employee_excel->birthday));


                // разделить ФИО на Ф И О
                $fio = explode(" ", str_replace("  ", " ", $fio_full));

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
//                    continue;
                    throw new Exception("Фамилия или Имя не могут быть пустыми");
                }

                /******************* Обновление строк в таблице Employee *******************/
                $employee = Employee::findOne(['last_name' => $f, 'first_name' => $i, 'patronymic' => $o, 'birthdate' => $birthday]);
                if (!$employee) {
                    $count_add_employee++;
                    $employee_id = Assistant::addMain('employee');
                    if ($employee_id == -1) {
                        throw new Exception("Ошибка создания главного id в таблице Main для Employee");
                    }

                    $employee = new Employee();
                    $employee->id = $employee_id;
                } else {
//                    $log->addData($employee_excel,'$employee_excel', __LINE__);
                    continue;
//                    $employee_id = $employee->id;
                }

                $employee->last_name = $f;
                $employee->first_name = $i;
                $employee->patronymic = $o;
                $employee->birthdate = $birthday;
                $employee->gender = 'М';

                if (!$employee->save()) {
                    $log->addData($employee->errors, '$employee_errors', __LINE__);
                    throw new Exception("Не смог сохранить модель Employee");
                }

                /******************* Обновление/проверка строк в таблице должностей Position *******************/
                $position = Position::findOne(['title' => $positionTitle]);
                if (!$position) {
                    $position_id = 1;
                } else {
                    $position_id = $position->id;
                }

                /******************* Обновление строк в таблице Worker1 *******************/
                // ищем работника синхронизации
                $worker = Worker::findOne(['employee_id' => $employee_id]);
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

                $worker->id = $worker_id;
                $worker->employee_id = $employee_id;
                $worker->tabel_number = $staffNumber;
                $worker->position_id = $position_id;
                $worker->company_department_id = $comp_dep_id;
                $worker->date_start = $dataWorkStart;
                $worker->date_end = date("Y-m-d", strtotime("2099-12-31"));

                if (!$worker->save()) {
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
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization($count_record);

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}


