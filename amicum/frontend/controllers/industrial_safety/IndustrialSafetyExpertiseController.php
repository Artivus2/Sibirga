<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\industrial_safety;

use backend\controllers\Assistant as BackendAssistant;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\handbooks\DepartmentController;
use frontend\models\Attachment;
use frontend\models\CompanyExpert;
use frontend\models\Expertise;
use frontend\models\ExpertiseCompanyExpert;
use frontend\models\ExpertiseEquipment;
use frontend\models\ExpertiseHistory;
use frontend\models\IndustrialSafetyObject;
use frontend\models\IndustrialSafetyObjectType;
use Throwable;
use Yii;
use yii\web\Controller;

class IndustrialSafetyExpertiseController extends Controller
{

    // GetIndustrialSafetyObject                    - Справочник объектов ЭПБ
    // GetIndustrialSafetyExpertise                 - Получение экспертиз промышленной безопасности
    // SaveIndustrialSafetyExpertise                - Сохранение ЭПБ
    // SaveIndustrialSafetyObject                   - Метод добавления нового объекта ЭПБ в "проектной документации"
    // GetExpertiseCompanyExpert                    - Метод получения данных для страницы "Учёт компаний-экспертов"
    // SaveExpertiseCompanyExpert                   - Метод внесения сведений об ЭПБ на странице "Учёт компаний-экспертов"
    // SaveCompanyExpert                            - Метод сохранения компании эксперта
    // ChangeStatusIndustrialSafetyExpertise        - Смена статуса ЭПБ
    // GetCompanyExpert                             - Справочник компаний экспертов
    // DeleteExpertiseCompanyExpert                 - Метод удаления экспертизы компании эксперта
    // DeleteExpertise                              - Метод удаления экспертизы

    const TYPE_DOCUMENTATION = 3;

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetIndustrialSafetyObject() - Справочник объектов ЭПБ
     * @return array
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * "object_types": {
     *     "1": {
     *         "industrial_safety_object_type_id": 1,
     *         "industrial_safety_object_type_title": "Технические устройства",
     *         "industrial_safety_objects": {
     *             "1": {
     *                 "industrial_safety_object_id": 1,
     *                 "industrial_safety_object_title": "КП-21"
     *             },
     *             "2": {
     *                 "industrial_safety_object_id": 2,
     *                 "industrial_safety_object_title": "Узел связи С"
     *             }
     *         }
     *     },
     *     "2": {
     *         "industrial_safety_object_type_id": 2,
     *         "industrial_safety_object_type_title": "Здания и сооружения",
     *         "industrial_safety_objects": {
     *             "6": {
     *                 "industrial_safety_object_id": 6,
     *                 "industrial_safety_object_title": "тестовое"
     *             }
     *         }
     *     },
     *     "3": {
     *         "industrial_safety_object_type_id": 3,
     *         "industrial_safety_object_type_title": "Проектная документация",
     *         "industrial_safety_objects": {}
     *     }
     * }
     *
     *
     *
     * @package frontend\controllers\industrial_safety
     *
     * Входные обязательные параметры:
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\IndustrialSafetyExpertise&method=GetIndustrialSafetyObject&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 03.12.2019 11:02
     */
    public static function GetIndustrialSafetyObject()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetIndustrialSafetyObject. Начало метода';
        try {
            $get_object_types = IndustrialSafetyObjectType::find()
                ->joinWith('industrialSafetyObjects')
                ->all();
            foreach ($get_object_types as $object_type) {
                $object_type_id = $object_type->id;
                $object_type_title = $object_type->title;
                $result['object_types'][$object_type_id]['industrial_safety_object_type_id'] = $object_type_id;
                $result['object_types'][$object_type_id]['industrial_safety_object_type_title'] = $object_type_title;
                $result['object_types'][$object_type_id]['industrial_safety_objects'] = array();
                foreach ($object_type->industrialSafetyObjects as $industrialSafetyObject) {
                    $industrialSafetyObject_id = $industrialSafetyObject->id;
                    $industrialSafetyObject_title = $industrialSafetyObject->title;
                    $result['object_types'][$object_type_id]['industrial_safety_objects'][$industrialSafetyObject_id]['industrial_safety_object_id'] = $industrialSafetyObject_id;
                    $result['object_types'][$object_type_id]['industrial_safety_objects'][$industrialSafetyObject_id]['industrial_safety_object_title'] = $industrialSafetyObject_title;
                }
                if (empty($result['object_types'][$object_type_id]['industrial_safety_objects'])) {
                    $result['object_types'][$object_type_id]['industrial_safety_objects'] = (object)array();
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetIndustrialSafetyObject. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetIndustrialSafetyObject. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetIndustrialSafetyExpertise() - Получение экспертиз промышленной безопасности
     * @param null $data_post
     * @return array
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      "company_department_id": 20028748
     * }
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *     "1": {
     *         "industrial_safety_object_type_id": 1,                                                                   - идентификатор типа объекта
     *         "industrial_safety_object_type_title": "Технические устройства",                                         - наименование типа объекта
     *         "expertises": {                                                                                          - массив экспертиз
     *             "10": {                                                                                              - идентификатор экспертизы
     *                 "expertise_id": 10,                                                                              - идентификатор экспертизы
     *                 "inventory_number": "12",                                                                        - инвентарный номер объекта
     *                 "industrial_safety_object_id": 2,                                                                - идентификатор объекта
     *                 "industrial_safety_object_title": "Узел связи С",                                                - наименование объекта
     *                 "company_department_id": 20028748,                                                               - идентификатор участка
     *                 "company_title": "АИСиРЭО",                                                                      - наименование участка
     *                 "date_issue": "2019-12-11",                                                                      - дата выдачи ЭПБ
     *                 "date_issue_format": "12.2019",                                                                  - дата когда проводилась экспертиза
     *                 "date_last_expertise": "2019-12-02",                                                             - Дата последний экспертизы ЭПБ
     *                 "date_last_expertise_format": "02.12.2019",                                                      - формматированный вывод даты последний экспертизы ЭПБ
     *                 "wear_period": 5,                                                                                - срок действия ЭПБ
     *                 "date_next_expertise": "2024-12-02",                                                             - дата следующей ЭПБ
     *                 "date_next_expertise_format": "12.2024",                                                         - форматированный вывод даты следующей ЭПБ
     *                 "status_id": 62,                                                                                 - идентификатор статуса
     *                 "status_title": "Пройдена",                                                                      - наименование статуса
     *                 "full_name": "Петров И. И.",                                                                     - ФИО ответственного (ФИО)
     *                 "stuff_number": "2050735",                                                                       - табельный номер ответственного
     *                 "worker_id": 2050735,                                                                            - идентификатор ответственного
     *                 "attachment": {},                                                                                - массив вложений
     *                 "history": {                                                                                     - История проведения ЭПБ
     *                     "56": {
     *                         "history_id": 56,                                                                        - идентификатор исторической ЭПБ
     *                         "expertise_id": 10,                                                                      - идентификатор экспертизы
     *                         "date": "02.09.2019",                                                                    - дата проведения ЭПБ
     *                         "wear_period": 5,                                                                        - срок действия ЭПБ
     *                         "date_next_expertise": "09.2024",                                                        - дата следующей ЭПБ
     *                         "status_id": 62,                                                                         - идентификатор статуса
     *                         "status_title": "Пройдена",                                                              - наименование статуса
     *                         "attachment": {}                                                                         - массив вложений
     *                     }
     *                 },
     *                 "equipments": {                                                                                  - массив оборудований экспертизы
     *                     "141498": {                                                                                  - идентификатор оборудования
     *                         "equipment_id": 141498,                                                                  - идентификатор оборудования
     *                         "equipment_title": "2KM-138"                                                             - наименование оборудования
     *                     },
     *                     "187318": {
     *                         "equipment_id": 187318,
     *                         "equipment_title": "Компрессор ДЭН-45ШМ (№470)"
     *                     }
     *                 }
     *             }
     *         },
     *         "counter": 5,                                                                                            - количество по объекту
     *         "statistic": {                                                                                           - статистика по процентам
     *             "62": 100                                                                                            - 62 (Пройдена)
     *         },
     *     "2": {
     *         "industrial_safety_object_type_id": 2,                                                                   - второй тип объекта "Здания и сооружения"
     *         "industrial_safety_object_type_title": "Здания и сооружения",
     *         "expertises": [],
     *         "counter": 0,
     *         "statistic": []
     *     },
     *     "3": {
     *         "industrial_safety_object_type_id": 3,                                                                   - третий тип объекта "Проектная документация"
     *         "industrial_safety_object_type_title": "Проектная документация",
     *         "expertises": [],
     *         "counter": 0,
     *         "statistic": []
     *     }
     * }
     *
     * АЛГОРИТМ:
     * 1. Выгрузить все вложенные участки по переданному
     * 2. Выгрзуить все типы объектов и сфромировамать базовый массив
     * 3. Выгрузить все экспертизы по всем вложенным участкам
     * 4. Перебор полученных данных
     *    4.1 Заполнение данными типа объкта
     *    4.2 Перебор    объектов
     *        4.2.1 Заполнение данными объкта
     *        4.2.2 Если нет ответственного заполнить пустыми данными
     *        4.2.3 Заполнение данными о вложении
     *        4.2.4 Заполнение историческимих данных
     * 5. Конец перебора
     *
     * @package frontend\controllers\industrial_safety
     *
     * Входные обязательные параметры:
     * @example  http://amicum/read-manager-amicum?controller=industrial_safety\IndustrialSafetyExpertise&method=GetIndustrialSafetyExpertise&subscribe=&data={"company_department_id":20028748}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 03.12.2019 14:17
     */
    public static function GetIndustrialSafetyExpertise($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $expertise_info = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'GetIndustrialSafetyExpertise. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetIndustrialSafetyExpertise. Не переданы входные параметры');
            }
            $warnings[] = 'GetIndustrialSafetyExpertise. Данные успешно переданы';
            $warnings[] = 'GetIndustrialSafetyExpertise. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetIndustrialSafetyExpertise. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetIndustrialSafetyExpertise. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetIndustrialSafetyExpertise. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('GetIndustrialSafetyExpertise. Возникла ошибка при получении вложенных участков');
            }
            $get_ind_safety_obj_type = IndustrialSafetyObjectType::find()
                ->all();
            if (!empty($get_ind_safety_obj_type)) {
                foreach ($get_ind_safety_obj_type as $exprtise_type) {
                    $exprtise_type_id = $exprtise_type->id;
                    $exprtise_type_title = $exprtise_type->title;
                    $expertise_info[$exprtise_type_id]['industrial_safety_object_type_id'] = $exprtise_type_id;
                    $expertise_info[$exprtise_type_id]['industrial_safety_object_type_title'] = $exprtise_type_title;
                    $expertise_info[$exprtise_type_id]['expertises'] = array();
                    $expertise_info[$exprtise_type_id]['counter'] = 0;
                    $expertise_info[$exprtise_type_id]['statistic'] = array();
                }
            }
            $get_exprtises = IndustrialSafetyObjectType::find()
                ->joinWith(['industrialSafetyObjects.expertises' => function ($expertise) use ($company_departments) {
                    $expertise->where(['in', 'expertise.company_department_id', $company_departments]);
                }])
                ->joinWith('industrialSafetyObjects.expertises.companyDepartment.company')
                ->joinWith('industrialSafetyObjects.expertises.expertiseHistories.attachment')
                ->joinWith('industrialSafetyObjects.expertises.expertiseEquipments')
                ->joinWith('industrialSafetyObjects.expertises.worker.position')
                ->joinWith('industrialSafetyObjects.expertises.worker.employee')
                ->all();
            if (!empty($get_exprtises)) {
                foreach ($get_exprtises as $exprtise_type) {
                    $exprtise_type_id = $exprtise_type->id;
                    $exprtise_type_title = $exprtise_type->title;
                    $expertise_info[$exprtise_type_id]['industrial_safety_object_type_id'] = $exprtise_type_id;
                    $expertise_info[$exprtise_type_id]['industrial_safety_object_type_title'] = $exprtise_type_title;
                    $expertise_info[$exprtise_type_id]['expertises'] = array();
                    foreach ($exprtise_type->industrialSafetyObjects as $industrialSafetyObject) {
                        foreach ($industrialSafetyObject->expertises as $expertise) {
                            $exprtise_id = $expertise->id;
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['expertise_id'] = $exprtise_id;
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['inventory_number'] = $expertise->inventory_number;
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['industrial_safety_object_id'] = $industrialSafetyObject->id;
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['industrial_safety_object_title'] = $industrialSafetyObject->title;
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['company_department_id'] = $expertise->company_department_id;
                            if (isset($expertise->companyDepartment->company)) {
                                $company_title = $expertise->companyDepartment->company->title;
                            } else {
                                $company_title = null;
                            }
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['company_title'] = $company_title;
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['date_issue'] = $expertise->date_issue;
                            if ($expertise->date_issue != null) {
                                $format_date_issue = date('d.m.Y', strtotime($expertise->date_issue));
                            } else {
                                $format_date_issue = null;
                            }
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['date_issue_format'] = $format_date_issue;
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['date_last_expertise'] = $expertise->date_last_expertise;
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['date_last_expertise_format'] = date('d.m.Y', strtotime($expertise->date_last_expertise));
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['wear_period'] = $expertise->wear_period;
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['date_next_expertise'] = $expertise->date_next_expertise;
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['date_next_expertise_format'] = date('m.Y', strtotime($expertise->date_next_expertise));
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['status_id'] = $expertise->status_id;
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['status_title'] = $expertise->status->title;
                            if ($expertise->worker_id != null) {
                                $name = mb_substr($expertise->worker->employee->first_name, 0, 1);
                                $patronymic = mb_substr($expertise->worker->employee->patronymic, 0, 1);
                                $full_name = "{$expertise->worker->employee->last_name} {$name}. {$patronymic}.";
                                $stuff_number = $expertise->worker->tabel_number;
                                $worker_id = $expertise->worker->id;
                            } else {
                                $full_name = null;
                                $stuff_number = null;
                                $worker_id = null;
                            }
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['full_name'] = $full_name;
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['stuff_number'] = $stuff_number;
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['worker_id'] = $worker_id;
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['attachment'] = array();
                            if (isset($expertise->attachment->path)) {
                                $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['attachment']['attachment_id'] = $expertise->attachment->id;
                                $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['attachment']['attachment_title'] = $expertise->attachment->title;
                                $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['attachment']['attachment_type'] = $expertise->attachment->attachment_type;
                                $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['attachment']['attachment_path'] = $expertise->attachment->path;
                                $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['attachment']['attachment_status'] = null;
                            } else {
                                $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['attachment'] = (object)array();
                            }
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['history'] = array();
                            foreach ($expertise->expertiseHistories as $expertiseHistory) {
                                $expertiseHistory_id = $expertiseHistory->id;
                                $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['history'][$expertiseHistory_id]['history_id'] = $expertiseHistory_id;
                                $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['history'][$expertiseHistory_id]['expertise_id'] = $exprtise_id;
                                $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['history'][$expertiseHistory_id]['date'] = date('d.m.Y', strtotime($expertiseHistory->date));
                                $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['history'][$expertiseHistory_id]['wear_period'] = $expertiseHistory->wear_period;
                                $date_next_expertise = date('d.m.Y', strtotime($expertiseHistory->date . "+$expertiseHistory->wear_period year"));
                                $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['history'][$expertiseHistory_id]['date_next_expertise'] = $date_next_expertise;
                                $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['history'][$expertiseHistory_id]['status_id'] = $expertiseHistory->status_id;
                                $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['history'][$expertiseHistory_id]['status_title'] = $expertiseHistory->status->title;
//                        $expertise_info[$type_id]['expertises'][$exprtise_id]['history'][$expertiseHistory_id]['attachment'] = array();
                                if (isset($expertiseHistory->attachment->path)) {
                                    $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['history'][$expertiseHistory_id]['attachment']['attachment_id'] = $expertiseHistory->attachment->id;
                                    $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['history'][$expertiseHistory_id]['attachment']['attachment_title'] = $expertiseHistory->attachment->title;
                                    $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['history'][$expertiseHistory_id]['attachment']['attachment_type'] = $expertiseHistory->attachment->attachment_type;
                                    $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['history'][$expertiseHistory_id]['attachment']['attachment_path'] = $expertiseHistory->attachment->path;
                                    $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['history'][$expertiseHistory_id]['attachment']['attachment_status'] = null;
                                } else {
                                    $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['history'][$expertiseHistory_id]['attachment'] = (object)array();
                                }
                            }
                            if (empty($expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['history'])) {
                                $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['history'] = (object)array();
                            }
                            if ($expertise->status_id == 62 or $expertise->status_id == 63) {
                                if (isset($expertise_info[$exprtise_type_id]['counter'])) {
                                    $expertise_info[$exprtise_type_id]['counter']++;
                                } else {
                                    $expertise_info[$exprtise_type_id]['counter'] = 1;
                                }
                            }
                            $expertise_info[$exprtise_type_id]['statistic'] = array();
                            $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['equipments'] = array();
                            foreach ($expertise->expertiseEquipments as $expertiseEquipment) {
                                $eq_id = $expertiseEquipment->equipment_id;
                                $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['equipments'][$eq_id]['equipment_id'] = $eq_id;
                                $expertise_info[$exprtise_type_id]['expertises'][$exprtise_id]['equipments'][$eq_id]['equipment_title'] = $expertiseEquipment->equipment->title;
                            }
                        }
                    }
                }
            }

            $response_statistic = self::GetIndustrialSafetyStatistic($company_departments);
            if ($response_statistic['status'] == 1) {
                $result_statistic = $response_statistic['Items'];
                $warnings[] = $response_statistic['warnings'];
            } else {
                $warnings[] = $response_statistic['warnings'];
                $errors[] = $response_statistic['errors'];
                throw new Exception('GetIndustrialSafetyExpertise. Возникла ошибка при получении статистики');
            }
            if (isset($result_statistic) and !empty($result_statistic)) {
                foreach ($result_statistic as $key => $statistic) {
                    $expertise_info[$key]['statistic'] = $statistic;
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetIndustrialSafetyExpertise. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetIndustrialSafetyExpertise. Конец метода';
        if (empty($expertise_info)) {
            $expertise_info = (object)array();
        }
        $result = $expertise_info;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetIndustrialSafetyStatistic() - Получение статистики по типам объектов ЭПБ используется в методе GetIndustrialSafetyExpertise
     * @param $company_departments
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 05.12.2019 10:35
     */
    public static function GetIndustrialSafetyStatistic($company_departments)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetIndustrialSafetyStatistic. Начало метода';
        try {
            $another_result = null;
            $result[1] = array();
            $result[2] = array();
            $result[3] = array();
            $get_statistic = IndustrialSafetyObject::find()
                ->select(['industrial_safety_object.industrial_safety_object_type_id as type',
                    'count(e.id) as count_expertise',
                    'e.status_id'])
                ->innerJoin('expertise e', 'e.industrial_safety_object_id = industrial_safety_object.id')
                ->where(['in', 'e.company_department_id', $company_departments])
                ->andWhere(['in', 'e.status_id', [62, 63]])
                ->groupBy(['industrial_safety_object_type_id', 'status_id'])
                ->asArray()
                ->all();
            foreach ($get_statistic as $statistic_item) {
                $another_result[$statistic_item['type']][$statistic_item['status_id']] = $statistic_item['count_expertise'];
            }
            $get_count_by_type = IndustrialSafetyObject::find()
                ->select(['industrial_safety_object.industrial_safety_object_type_id as type',
                    'count(e.id) as count_expertise'])
                ->innerJoin('expertise e', 'e.industrial_safety_object_id = industrial_safety_object.id')
                ->where(['in', 'e.company_department_id', $company_departments])
                ->andWhere(['in', 'e.status_id', [62, 63]])
                ->groupBy(['industrial_safety_object_type_id'])
                ->asArray()
                ->all();
            foreach ($get_count_by_type as $count_by_type) {
                foreach ($another_result[$count_by_type['type']] as $key => $stat_for_type) {
                    if ($count_by_type['count_expertise'] != 0) {
                        $result[$count_by_type['type']][$key] = round(($stat_for_type / $count_by_type['count_expertise']) * 100, 2);
                    } else {
                        $result[$count_by_type['type']][$key] = 0;
                    }
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetIndustrialSafetyStatistic. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetIndustrialSafetyStatistic. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SaveIndustrialSafetyExpertise() - Сохранение ЭПБ
     * @param null $data_post
     * @return array
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *     "expertise_id": null,                                                                                        - идентификатор экспертизы
     *     "company_department_id": 20028748,                                                                           - идентификатор участка
     *     "company_title": "АИСиРЭО",                                                                                  - наименование участка
     *     "inventory_number": "87цуек8у7",                                                                             - инвентарный номер объекта ЭПБ
     *     "date_issue": "17.04.2020",                                                                                  - дата ЭПБ
     *     "date_issue_format": "17.04.2020",                                                                           - отформатированная дата ЭПБ
     *     "industrial_safety_object_id": 6,                                                                            - идентификатор объекта ЭПБ
     *     "industrial_safety_object_title": "тестовое",                                                                - наименование объекта ЭПБ
     *     "status_id": 62,                                                                                             - идентификатор статуса (62 = Пройдена)
     *     "wear_period": "5",                                                                                          - срок действия ЭПБ
     *     "worker_id": "70003934",                                                                                     - идентификатор ответственного
     *     "full_name": "Левченко+Валентина+Андреевна",                                                                 - ФИО ответственного
     *     "date_last_expertise": "17.04.2020",                                                                         - Дата последней экспертизы
     *     "date_last_expertise_format": "17.04.2020",                                                                  - форматированная дата последней экспертизы
     *     "attachment_id": null,                                                                                       - идентификатор вложения
     *     "attachment_title": "Construction_Worker_3-768x768.jpg",                                                     - наименование вложения
     *     "attachment_path": "",                                                                                       - путь до вложения
     *     "attachment_type": "jpg",                                                                                    - тип вложения
     *     "attachment_status": "new",                                                                                  - статус вложения (new = сохранить)
     *     "history": {},                                                                                               - история прохождения ЭПБ
     *     "equipments": {                                                                                              - массив оборудования
     *         "-1": {                                                                                                  - идентификатор оборудования
     *             "equipment_id": -1,                                                                                  - идентификатор оборудования
     *             "equipment_title": null                                                                              - наименование оборудования
     *         }
     *     }
     * }
     *
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * В результате сохранения изменяются идентификаторы на корректные и этот массив возвращается обратно на фронт
     *
     * АЛГОРИТМ:
     * 1. Получаем все вложенные участки
     * 2. Поиск экспертизы по идентификатору
     *        Не найдено?    Создать новую экспертизу
     * 3. Получение  экспертизы по: инвентарному номеру и объекту ЭПБ
     *        Найдено? Записать ошибку о том, что объект с таким инвентарным номером уже существует
     * 4. Заполняем данные предписания
     * 5. Если статус вложенияч = 'new'
     *    5.1 Сохранить вложение
     * 5. Сохраняем экспертизу
     * 6. Проверка на пустсоту массив истории экспертизу
     *    6.1. Перебор данных истории экспертиз
     *        6.1.1 Найти историю экспертизы по идентификатору
     *                Не найдено? Создать новую историю ЭПБ
     *        6.1.2 Заполняем данными историю ЭПБ
     *        6.1.3 Проверяем статус вложение
     *                'new'? Сохранить вложение
     *        6.1.4 Сохранить историю экспертизы
     * 8. Конец перебора
     *
     * @package frontend\controllers\industrial_safety
     *
     * Входные обязательные параметры:
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\IndustrialSafetyExpertise&method=SaveIndustrialSafetyExpertise&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 03.12.2019 15:17
     */
    public static function SaveIndustrialSafetyExpertise($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $expertise_saving = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'SaveIndustrialSafetyExpertise. Начало метода';
        $session = Yii::$app->session;
        $expertise_equipments = array();
//    	$data_post = '{"expertise_id":null,"company_department_id":20028766,"inventory_number":"E392184/332","date_issue":null,"industrial_safety_object_id":1,"status_id":62,"wear_period":2,"worker_id":null,"date_last_expertise":"2019-12-03","attachment_id":null,"attachment_title":"title","attachment_path":"bloooob","attachment_type":"docx","attachment_status":"keke","history":{}}';
        try {
            $transaction = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveIndustrialSafetyExpertise. Не переданы входные параметры');
            }
            $warnings[] = 'SaveIndustrialSafetyExpertise. Данные успешно переданы';
            $warnings[] = 'SaveIndustrialSafetyExpertise. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'SaveIndustrialSafetyExpertise. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id') ||
//                !property_exists($post_dec, 'flag') &&
                !property_exists($post_dec, 'expertise_id') ||
                !property_exists($post_dec, 'worker_id') ||
                !property_exists($post_dec, 'inventory_number') ||
                !property_exists($post_dec, 'status_id') ||
                !property_exists($post_dec, 'date_issue') ||
                !property_exists($post_dec, 'industrial_safety_object_id') ||
                !property_exists($post_dec, 'wear_period') ||
                !property_exists($post_dec, 'date_last_expertise') ||
                !property_exists($post_dec, 'attachment_id') ||
                !property_exists($post_dec, 'attachment_title') ||
                !property_exists($post_dec, 'attachment_type') ||
                !property_exists($post_dec, 'attachment_path') ||
                !property_exists($post_dec, 'attachment_status') ||
                !property_exists($post_dec, 'history'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('SaveIndustrialSafetyExpertise. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveIndustrialSafetyExpertise. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('GetIndustrialSafetyExpertise. Возникла ошибка при получении вложенных участков');
            }
//            $flag = $post_dec->flag;
            $expertise_id = $post_dec->expertise_id;
            $inventory_number = $post_dec->inventory_number;
            $worker_id = $post_dec->worker_id;
            $date_issue = $post_dec->date_issue;
            $industrial_safety_object_id = $post_dec->industrial_safety_object_id;
            $wear_period = $post_dec->wear_period;
            $date_last_expertise = $post_dec->date_last_expertise_format;
            $status_id = $post_dec->status_id;
            $history = $post_dec->history;
            $attachment_expert_id = $post_dec->attachment_id;
            $attachment_title = $post_dec->attachment_title;
            $attachment_type = $post_dec->attachment_type;
            $attachment_path = $post_dec->attachment_path;
            $attachment_status = $post_dec->attachment_status;

            $expertise = Expertise::findOne(['id' => $expertise_id]);
            if (empty($expertise)) {
                $expertise = new Expertise();
            }

            $get_ind_safety_exp = Expertise::findOne(['inventory_number' => $inventory_number, 'industrial_safety_object_id' => $industrial_safety_object_id]);
            if ($inventory_number == $expertise->inventory_number && $expertise->industrial_safety_object_id == $industrial_safety_object_id) {
                $get_ind_safety_exp = array();
            }

            if (empty($get_ind_safety_exp)) {
                $expertise->company_department_id = $company_department_id;
                if ($date_issue != null) {
                    $date_issue = date('Y-m-d', strtotime($date_issue));
                }
                $expertise->date_issue = $date_issue;
                $expertise->status_id = $status_id;
                $expertise->inventory_number = $inventory_number;
                $expertise->industrial_safety_object_id = $industrial_safety_object_id;
                $expertise->wear_period = $wear_period;
                if ($date_last_expertise) {
                    $expertise->date_last_expertise = date('Y-m-d', strtotime($date_last_expertise));
                } else {
                    throw new Exception('SaveIndustrialSafetyExpertise. Не верный формат даты последней экспертизы. Дата пуста');
                }
                $date_next_expertise = date('Y-m-d', strtotime($date_last_expertise . "+$wear_period year"));
                $expertise->date_next_expertise = $date_next_expertise;
                if ($attachment_status == 'new') {
                    $normalize_path = Assistant::UploadFile($attachment_path, $attachment_title, 'attachment', $attachment_type);
                    $add_attachment_expertise = new Attachment();
                    $add_attachment_expertise->title = $attachment_title;
                    $add_attachment_expertise->attachment_type = $attachment_type;
                    $add_attachment_expertise->path = $normalize_path;
                    $add_attachment_expertise->date = BackendAssistant::GetDateFormatYMD();
                    $add_attachment_expertise->worker_id = $session['worker_id'];
                    $add_attachment_expertise->section_title = 'ОТ и ПБ/Учет проведения и планирования экспертизы промышленной безопасности';
                    if ($add_attachment_expertise->save()) {
                        $warnings[] = 'SaveIndustrialSafetyExpertise. Вложение успешно сохранено';
                        $add_attachment_expertise->refresh();
                        $attachment_expert_id = $add_attachment_expertise->id;
                        $post_dec->attachment = (object)array();
                        $post_dec->attachment->attachment_path = $normalize_path;
                        $post_dec->attachment->attachment_id = $attachment_expert_id;
                        $post_dec->attachment->attachment_title = $attachment_title;
                        $post_dec->attachment->attachment_type = $attachment_type;
                        $post_dec->attachment->attachment_status = null;
                        unset($post_dec->attachment_path, $post_dec->attachment_id, $post_dec->attachment_title, $post_dec->attachment_type, $post_dec->attachment_status);
                    } else {
                        $errors[] = $add_attachment_expertise->errors;
                        throw new Exception('SaveIndustrialSafetyExpertise. Ошибка при сохранени влоежения');
                    }
                } elseif ($post_dec->attachment_id != null) {
                    $post_dec->attachment = (object)array();
                    $post_dec->attachment->attachment_path = $post_dec->attachment_path;
                    $post_dec->attachment->attachment_id = $post_dec->attachment_id;
                    $post_dec->attachment->attachment_title = $post_dec->attachment_title;
                    $post_dec->attachment->attachment_type = $post_dec->attachment_type;
                    $post_dec->attachment->attachment_status = null;
                    unset($post_dec->attachment_path, $post_dec->attachment_id, $post_dec->attachment_title, $post_dec->attachment_type, $post_dec->attachment_status);
                } else {
                    $post_dec->attachment = (object)array();
                    unset($post_dec->attachment_path, $post_dec->attachment_id, $post_dec->attachment_title, $post_dec->attachment_type, $post_dec->attachment_status);
                }
                $expertise->worker_id = $worker_id;
                $expertise->attachment_id = $attachment_expert_id;
                if ($expertise->save()) {
                    $warnings[] = 'SaveIndustrialSafetyExpertise. Экспертиза успешно сохранена';
                    $expertise->refresh();
                    $expertise_id = $expertise->id;
                    $post_dec->expertise_id = $expertise->id;
                    $get_ind_safety_obj = IndustrialSafetyObject::find()
                        ->select(['industrial_safety_object_type_id'])
                        ->where(['id' => $post_dec->industrial_safety_object_id])
                        ->scalar();
                    $expertise_saving['industrial_safety_object_type_id'] = $get_ind_safety_obj;
                    $post_dec->status_title = $expertise->status->title;
                    $post_dec->stuff_number = $expertise->worker->tabel_number;
                    $post_dec->date_next_expertise = $expertise->date_next_expertise;
                    $post_dec->date_next_expertise_format = date('m.Y', strtotime($expertise->date_next_expertise));
                } else {
                    $errors[] = $expertise->errors;
                    throw new Exception('SaveIndustrialSafetyExpertise. Ошибка при сохранении экспертизы');
                }
                if (isset($history)) {
                    foreach ($history as $key => $history_item) {
                        $history_expertise = ExpertiseHistory::findOne(['id' => $history_item->history_id]);
                        if (empty($history_expertise)) {
                            $history_expertise = new ExpertiseHistory();
                        } else {
                            $expertise_id = $history_item->expertise_id;
                        }
                        $history_expertise->expertise_id = $expertise_id;
                        $history_expertise->date = date('Y-m-d', strtotime($history_item->date));
                        $history_expertise->status_id = $history_item->status_id;
                        $history_expertise->wear_period = $wear_period;
                        $attachment_id = null;
                        $errors['history'][] = $history_item;
                        if (isset($history_item->attachment) && !empty($history_item->attachment)) {
                            if (isset($history_item->attachment->attachment_title) && !empty($history_item->attachment->attachment_title)) {
                                $attachment_id = $history_item->attachment->attachment_id;
                                if ($history_item->attachment->attachment_status == 'new') {
//                                    $attachment_id = $history_item->attachment->attachment_id;
                                    $normalize_path = Assistant::UploadFile($history_item->attachment->attachment_path, $history_item->attachment->attachment_title, 'attachment', $history_item->attachment->attachment_type);
                                    $add_attachment = new Attachment();
                                    $add_attachment->title = $history_item->attachment->attachment_title;
                                    $add_attachment->attachment_type = $history_item->attachment->attachment_type;
                                    $add_attachment->path = $normalize_path;
                                    $add_attachment->date = BackendAssistant::GetDateFormatYMD();
                                    $add_attachment->worker_id = $session['worker_id'];
                                    $add_attachment->section_title = 'ОТ и ПБ/Учет проведения и планирования экспертизы промышленной безопасности';
                                    if ($add_attachment->save()) {
                                        $warnings[] = 'SaveIndustrialSafetyExpertise. Вложение успешно сохранено';
                                        $add_attachment->refresh();
                                        $attachment_id = $add_attachment->id;
                                        //                                    $post_dec->{"history"}->{$key}->attachment = (object)array();
                                        $post_dec->{"history"}->{$key}->attachment->attachment_id = $attachment_id;
                                        $post_dec->{"history"}->{$key}->attachment->attachment_path = $normalize_path;
                                        $post_dec->{"history"}->{$key}->attachment->attachment_title = $history_item->attachment->attachment_title;
                                        $post_dec->{"history"}->{$key}->attachment->attachment_type = $history_item->attachment->attachment_type;
                                        $post_dec->{"history"}->{$key}->attachment->attachment_status = null;
                                    } else {
                                        $errors[] = $add_attachment->errors;
                                        throw new Exception('SaveIndustrialSafetyExpertise. Ошибка при сохранени влоежения');
                                    }
                                }
                            }
                        }
                        $history_expertise->attachment_id = $attachment_id;
                        if ($history_expertise->save()) {
                            $warnings[] = 'SaveIndustrialSafetyExpertise. История для ЭПБ успешно сохранена';
                            $history_expertise->refresh();
//                        $post_dec->{"history"}->{$key} = (object)array();
                            $post_dec->{"history"}->{$key}->{"history_id"} = $history_expertise->id;
                            $post_dec->{"history"}->{$key}->{"expertise_id"} = $expertise_id;
                            $post_dec->{"history"}->{$key}->{"wear_period"} = $wear_period;
                            $post_dec->{"history"}->{$key}->{"date_next_expertise"} = date('d.m.Y', strtotime($history_expertise->date . "+$wear_period year"));
                            $post_dec->{"history"}->{$key}->status_id = $history_item->status_id;
//                        $post_dec->{"history"}->{$key}->{"status"} = $history_item->status_id;
                            $post_dec->{"history"}->{$key}->{"status_title"} = $history_expertise->status->title;
                        } else {
                            $errors[] = $history_expertise->errors;
                            throw new Exception('SaveIndustrialSafetyExpertise. Ошибка при сохранении истории для ЭПБ');
                        }

                    }
                }
            } else {
                $errors[] = 'Такой инвентарный номер с таким объектом уже существует';
                $status = 0;
//                throw new \Exception('Такой инвентарный номер с таким объектом уже существует');
            }
            $transaction->commit();
            $expertise_saving['saving_obj'] = $post_dec;
            $response_statistic = self::GetIndustrialSafetyStatistic($company_departments);
            if ($response_statistic['status'] == 1) {
                $expertise_saving['statisitc'] = $response_statistic['Items'];
                $warnings[] = $response_statistic['warnings'];
            } else {
                $warnings[] = $response_statistic['warnings'];
                $errors[] = $response_statistic['errors'];
                throw new Exception('GetIndustrialSafetyExpertise. Возникла ошибка при получении статистики');
            }
            if (property_exists($post_dec, 'equipments')) {
                $equipments = $post_dec->equipments;
                ExpertiseEquipment::deleteAll(['expertise_id' => $expertise_id]);
                if (isset($equipments) && !empty($equipments)) {
                    foreach ($equipments as $equipment) {
                        if ($equipment->equipment_id > 0) {
                            $expertise_equipments[] = [$expertise_id, $equipment->equipment_id];
                        }
                    }
                }
            }
            if (isset($expertise_equipments) && !empty($expertise_equipments)) {
                $inserted_expertise_eq = Yii::$app->db
                    ->createCommand()
                    ->batchInsert('expertise_equipment', ['expertise_id', 'equipment_id'], $expertise_equipments)
                    ->execute();
                if ($inserted_expertise_eq == 0) {
                    throw new Exception('SaveIndustrialSafetyExpertise. Ошибка при сохранении оборудований экспертизы');
                }
            }
        } catch (Throwable $exception) {
            $transaction->rollBack();
            $errors[] = 'SaveIndustrialSafetyExpertise. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SaveIndustrialSafetyExpertise. Конец метода';
        return array('Items' => $expertise_saving, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SaveIndustrialSafetyObject() - Метод добавления нового объекта ЭПБ в "проектной документации"
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\IndustrialSafetyExpertise&method=SaveIndustrialSafetyObject&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 04.12.2019 7:55
     */
    public static function SaveIndustrialSafetyObject($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $post_dec = null;                                                                                              // Массив ошибок
        $warnings[] = 'SaveIndustrialSafetyObject. Начало метода';
        try {
            $transaction = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveIndustrialSafetyObject. Не переданы входные параметры');
            }
            $warnings[] = 'SaveIndustrialSafetyObject. Данные успешно переданы';
            $warnings[] = 'SaveIndustrialSafetyObject. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'SaveIndustrialSafetyObject. Декодировал входные параметры';
            if (!property_exists($post_dec, 'industrial_safety_object_title') ||
                !property_exists($post_dec, 'industrial_safety_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('SaveIndustrialSafetyObject. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveIndustrialSafetyObject. Данные с фронта получены';
            $title = $post_dec->industrial_safety_object_title;
            $type_id = $post_dec->industrial_safety_type_id;
            $add_industrial_safety_object = new IndustrialSafetyObject();
            $add_industrial_safety_object->title = $title;
            $add_industrial_safety_object->industrial_safety_object_type_id = $type_id;
            if ($add_industrial_safety_object->save()) {
                $warnings[] = 'SaveIndustrialSafetyObject. Новый объект ЭПБ успешно сохранён';
                $add_industrial_safety_object->refresh();
                $post_dec->industrial_safety_object_id = $add_industrial_safety_object->id;
            } else {
                $errors[] = $add_industrial_safety_object->errors;
                throw new Exception('SaveIndustrialSafetyObject. Ошибка при добавлении нового объекта ЭПБ');
            }
            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            $errors[] = 'SaveIndustrialSafetyObject. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SaveIndustrialSafetyObject. Конец метода';
        $result = $post_dec;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetExpertiseCompanyExpert() - Метод получения данных для страницы "Учёт компаний-экспертов"
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\IndustrialSafetyExpertise&method=GetExpertiseCompanyExpert&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 04.12.2019 11:52
     */
    public static function GetExpertiseCompanyExpert()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetExpertiseComapnyExpert. Начало метода';
        try {
            $get_expertise_company_expert = ExpertiseCompanyExpert::find()
                ->joinWith('companyExpert')
                ->joinWith('attachment')
                ->all();
            foreach ($get_expertise_company_expert as $expertise_company_expert) {
                $expertise_company_expert_id = $expertise_company_expert->id;
                $result['accounting_company_expert'][$expertise_company_expert_id]['expertise_company_expert_id'] = $expertise_company_expert_id;
                $result['accounting_company_expert'][$expertise_company_expert_id]['number_expertise'] = $expertise_company_expert->number_expertise;
                $result['accounting_company_expert'][$expertise_company_expert_id]['company_expert_id'] = $expertise_company_expert->companyExpert->id;
                $result['accounting_company_expert'][$expertise_company_expert_id]['company_expert_title'] = $expertise_company_expert->companyExpert->title;
                $result['accounting_company_expert'][$expertise_company_expert_id]['company_expert_address'] = $expertise_company_expert->companyExpert->address;
                $result['accounting_company_expert'][$expertise_company_expert_id]['date_expertise'] = date('d.m.Y', strtotime($expertise_company_expert->date_expertise));
                $result['accounting_company_expert'][$expertise_company_expert_id]['attachment'] = array();
                if (isset($expertise_company_expert->attachment->path)) {
                    $result['accounting_company_expert'][$expertise_company_expert_id]['attachment']['attachment_id'] = $expertise_company_expert->attachment->id;
                    $result['accounting_company_expert'][$expertise_company_expert_id]['attachment']['attachment_title'] = $expertise_company_expert->attachment->title;
                    $result['accounting_company_expert'][$expertise_company_expert_id]['attachment']['attachment_type'] = $expertise_company_expert->attachment->attachment_type;
                    $result['accounting_company_expert'][$expertise_company_expert_id]['attachment']['attachment_path'] = $expertise_company_expert->attachment->path;
                    $result['accounting_company_expert'][$expertise_company_expert_id]['attachment']['attachment_status'] = $expertise_company_expert->attachment->path;
                } else {
                    $result['accounting_company_expert'][$expertise_company_expert_id]['attachment'] = (object)array();
                }
                if (isset($result['count'])) {
                    $result['count']++;
                } else {
                    $result['count'] = 1;
                }
            }
            $respose_statistic = self::GetExpertiseCompanyExpertStatistic();
            if ($respose_statistic['status'] == 1) {
                $result['statisitic'] = $respose_statistic['Items'];
                $warnings[] = $respose_statistic['warnings'];
            } else {
                $warnings[] = $respose_statistic['warnings'];
                $errors[] = $respose_statistic['errors'];
                throw new Exception('GetExpertiseComapnyExpert. Ошибка при получении статистики');
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetExpertiseComapnyExpert. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetExpertiseComapnyExpert. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SaveExpertiseCompanyExpert() - Метод внесения сведений об ЭПБ на странице "Учёт компаний-экспертов"
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\IndustrialSafetyExpertise&method=SaveExpertiseCompanyExpert&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 04.12.2019 12:21
     */
    public static function SaveExpertiseCompanyExpert($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $post_dec = null;
        $attachment_id = null;
        $session = Yii::$app->session;
//        $data_post = '{"number_expertise":4511545,"expertise_company_expert_id":-1,"date_expertise":"2019-12-04","company_expert_id":3,"attachment":{}}';
        $warnings[] = 'SaveExpertiseCompanyExpert. Начало метода';
        try {
            $transaction = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveExpertiseCompanyExpert. Не переданы входные параметры');
            }
            $warnings[] = 'SaveExpertiseCompanyExpert. Данные успешно переданы';
            $warnings[] = 'SaveExpertiseCompanyExpert. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'SaveExpertiseCompanyExpert. Декодировал входные параметры';
            if (!property_exists($post_dec, 'number_expertise') ||
                !property_exists($post_dec, 'expertise_company_expert_id') ||
                !property_exists($post_dec, 'date_expertise') ||
                !property_exists($post_dec, 'company_expert_id') ||
                !property_exists($post_dec, 'attachment')
            ) {
                throw new Exception('SaveExpertiseCompanyExpert. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveExpertiseCompanyExpert. Данные с фронта получены';
            $number_expertise = $post_dec->number_expertise;
            $expertise_company_expert_id = $post_dec->expertise_company_expert_id;
            $date_expertise = $post_dec->date_expertise;
            $company_expert_id = $post_dec->company_expert_id;
            $attachment = $post_dec->attachment;

            $add_accounting_company_expert = ExpertiseCompanyExpert::findOne(['id' => $expertise_company_expert_id]);
            if (empty($add_accounting_company_expert)) {
                $add_accounting_company_expert = new ExpertiseCompanyExpert();
            }
            $add_accounting_company_expert->company_expert_id = $company_expert_id;
            $add_accounting_company_expert->number_expertise = $number_expertise;
            $add_accounting_company_expert->date_expertise = $date_expertise;
            if (isset($attachment->attachment_status) && $attachment->attachment_status == 'new') {
                $normalize_path = Assistant::UploadFile($attachment->attachment_path, $attachment->attachment_title, 'attachment', $attachment->attachment_type);
                $add_attachment = new Attachment();
                $add_attachment->title = $attachment->attachment_title;
                $add_attachment->attachment_type = $attachment->attachment_type;
                $add_attachment->path = $normalize_path;
                $add_attachment->date = BackendAssistant::GetDateFormatYMD();
                $add_attachment->worker_id = $session['worker_id'];
                $add_attachment->section_title = 'ОТ и ПБ/Учет проведения и планирования экспертизы промышленной безопасности';
                if ($add_attachment->save()) {
                    $warnings[] = 'SaveExpertiseCompanyExpert. Вложение успешно сохранено';
                    $add_attachment->refresh();
                    $attachment_id = $add_attachment->id;
                    $post_dec->attachment->attachment_id = $attachment_id;
                    $post_dec->attachment->attachment_path = $add_attachment->path;
                } else {
                    $errors[] = $add_attachment->errors;
                    throw new Exception('SaveExpertiseCompanyExpert. Ошибка при сохранени влоежения');
                }
            }
            $add_accounting_company_expert->attachment_id = $attachment_id;
            if ($add_accounting_company_expert->save()) {
                $warnings[] = 'SaveExpertiseCompanyExpert. Сведения об ЭПБ успешно внесены';
                $add_accounting_company_expert->refresh();
                $post_dec->expertise_company_expert_id = $add_accounting_company_expert->id;
            } else {
                $errors[] = $add_accounting_company_expert->errors;
                throw new Exception('SaveExpertiseCompanyExpert. Ошибка при внесении сведений об ЭПБ');
            }
            $transaction->commit();
            $respose_statistic = self::GetExpertiseCompanyExpertStatistic();
            if ($respose_statistic['status'] == 1) {
                $post_dec->statisitic = $respose_statistic['Items'];
                $warnings[] = $respose_statistic['warnings'];
            } else {
                $warnings[] = $respose_statistic['warnings'];
                $errors[] = $respose_statistic['errors'];
                throw new Exception('GetExpertiseComapnyExpert. Ошибка при получении статистики');
            }
        } catch (Throwable $exception) {
            $transaction->rollBack();
            $errors[] = 'SaveExpertiseCompanyExpert. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SaveExpertiseCompanyExpert. Конец метода';
        $result = $post_dec;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SaveCompanyExpert() - Метод сохранения компании эксперта
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\IndustrialSafetyExpertise&method=SaveCompanyExpert&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 04.12.2019 13:15
     */
    public static function SaveCompanyExpert($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $post_dec = null;
        $warnings[] = 'SaveCompanyExpert. Начало метода';
        try {
            $transaction = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveCompanyExpert. Не переданы входные параметры');
            }
            $warnings[] = 'SaveCompanyExpert. Данные успешно переданы';
            $warnings[] = 'SaveCompanyExpert. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'SaveCompanyExpert. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_expert_title') ||
                !property_exists($post_dec, 'company_expert_address'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('SaveCompanyExpert. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveCompanyExpert. Данные с фронта получены';
            $company_expert_title = $post_dec->company_expert_title;
            $company_expert_address = $post_dec->company_expert_address;

            $add_company_expert = new CompanyExpert();
            $add_company_expert->title = $company_expert_title;
            $add_company_expert->address = $company_expert_address;
            if ($add_company_expert->save()) {
                $warnings[] = 'SaveCompanyExpert. Компания эксперт успешно сохранена';
                $add_company_expert->refresh();
                $post_dec->company_expert_id = $add_company_expert->id;
            } else {
                $errors[] = $add_company_expert->errors;
                throw new Exception('SaveCompanyExpert. Ошибка при сохранении компании эксперта');
            }
            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            $errors[] = 'SaveCompanyExpert. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SaveCompanyExpert. Конец метода';
        $result = $post_dec;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод ChangeStatusIndustrialSafetyExpertise() - Смена статуса ЭПБ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\IndustrialSafetyExpertise&method=ChangeStatusIndustrialSafetyExpertise&subscribe=&data={"expertise_id":21,"status_id":66,"company_department_id":20028766}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 05.12.2019 10:36
     */
    public static function ChangeStatusIndustrialSafetyExpertise($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $post_dec = null;
        $warnings[] = 'ChangeStatusIndustrialSafetyExpertise. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('ChangeStatusIndustrialSafetyExpertise. Не переданы входные параметры');
            }
            $warnings[] = 'ChangeStatusIndustrialSafetyExpertise. Данные успешно переданы';
            $warnings[] = 'ChangeStatusIndustrialSafetyExpertise. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'ChangeStatusIndustrialSafetyExpertise. Декодировал входные параметры';
            if (!property_exists($post_dec, 'expertise_id') ||
                !property_exists($post_dec, 'status_id') ||
                !property_exists($post_dec, 'company_department_id')
            )                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('ChangeStatusIndustrialSafetyExpertise. Переданы некорректные входные параметры');
            }
            $warnings[] = 'ChangeStatusIndustrialSafetyExpertise. Данные с фронта получены';
            $expertise_id = $post_dec->expertise_id;
            $status_id = $post_dec->status_id;
            $company_department_id = $post_dec->company_department_id;

            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('GetIndustrialSafetyExpertise. Возникла ошибка при получении вложенных участков');
            }
            $change_expertise_status_id = Expertise::findOne(['id' => $expertise_id]);
            $change_expertise_status_id->status_id = $status_id;
            if ($change_expertise_status_id->save()) {
                $warnings[] = 'ChangeStatusIndustrialSafetyExpertise. Смена статуса экспертизы прошла успешно';
                $post_dec->status_title = $change_expertise_status_id->status->title;
            } else {
                $errors[] = $change_expertise_status_id->errors;
                throw new Exception('ChangeStatusIndustrialSafetyExpertise. Ошибка при смене статуса экспертизы');
            }
            $response_statistic = self::GetIndustrialSafetyStatistic($company_departments);
            if ($response_statistic['status'] == 1) {
                $post_dec->statisitc = $response_statistic['Items'];
                $warnings[] = $response_statistic['warnings'];
            } else {
                $warnings[] = $response_statistic['warnings'];
                $errors[] = $response_statistic['errors'];
                throw new Exception('GetIndustrialSafetyExpertise. Возникла ошибка при получении статистики');
            }
        } catch (Throwable $exception) {
            $errors[] = 'ChangeStatusIndustrialSafetyExpertise. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'ChangeStatusIndustrialSafetyExpertise. Конец метода';
        $result = $post_dec;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetExpertiseCompanyExpertStatistic() - Статистика для блока "Учёт компаний экспертов"
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * Входные обязательные параметры:
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 05.12.2019 10:57
     */
    public static function GetExpertiseCompanyExpertStatistic()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetExpertiseCompanyExpertStatistic. Начало метода';
        try {
            $statistics = ExpertiseCompanyExpert::find()
                ->select(['ce.title as company_expert_title', 'count(expertise_company_expert.id) as count_expertise_company_expert'])
                ->innerJoin('company_expert ce', 'expertise_company_expert.company_expert_id = ce.id')
                ->groupBy('company_expert_title')
                ->asArray()
                ->all();
            $count_exp_comp_exp = ExpertiseCompanyExpert::find()
                ->select(['count(expertise_company_expert.id) as count_expertise_company_expert'])
                ->count();
            if (!empty($statistics)) {
                foreach ($statistics as $statistic) {
                    $result[$statistic['company_expert_title']]['company_expert_title'] = $statistic['company_expert_title'];
                    $result[$statistic['company_expert_title']]['count_expertise_company_expert'] = $statistic['count_expertise_company_expert'];
                    if (!empty($count_exp_comp_exp)) {
                        $result[$statistic['company_expert_title']]['percentage'] = round(($statistic['count_expertise_company_expert'] / $count_exp_comp_exp) * 100, 2);
                    } else {
                        $result[$statistic['company_expert_title']]['percentage'] = 0;
                    }
                }
            } else {
                $result = (object)array();
            }

        } catch (Throwable $exception) {
            $errors[] = 'GetExpertiseCompanyExpertStatistic. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetExpertiseCompanyExpertStatistic. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetCompanyExpert() - Справочник компаний экспертов
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\IndustrialSafetyExpertise&method=GetCompanyExpert&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 05.12.2019 11:40
     */
    public static function GetCompanyExpert()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetCompanyExpert. Начало метода';
        try {
            $result = CompanyExpert::find()
                ->select([
                    'id as company_expert_id',
                    'title  as company_expert_title',
                    'address  as company_expert_address'
                ])
                ->asArray()
                ->indexBy('company_expert_id')
                ->all();
        } catch (Throwable $exception) {
            $errors[] = 'GetCompanyExpert. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetCompanyExpert. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeleteExpertiseCompanyExpert() - Метод удаления экспертизы компании эксперта
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\IndustrialSafetyExpertise&method=DeleteExpertiseCompanyExpert&subscribe=&data={"expertise_company_expert_id":2}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 05.12.2019 13:46
     */
    public static function DeleteExpertiseCompanyExpert($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $post_dec = null;
        $warnings[] = 'DeleteExpertiseCompanyExpert. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('DeleteExpertiseCompanyExpert. Не переданы входные параметры');
            }
            $warnings[] = 'DeleteExpertiseCompanyExpert. Данные успешно переданы';
            $warnings[] = 'DeleteExpertiseCompanyExpert. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'DeleteExpertiseCompanyExpert. Декодировал входные параметры';
            if (!property_exists($post_dec, 'expertise_company_expert_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('DeleteExpertiseCompanyExpert. Переданы некорректные входные параметры');
            }
            $warnings[] = 'DeleteExpertiseCompanyExpert. Данные с фронта получены';
            $expertise_company_expert_id = $post_dec->expertise_company_expert_id;
            ExpertiseCompanyExpert::deleteAll(['id' => $expertise_company_expert_id]);
            $respose_statistic = self::GetExpertiseCompanyExpertStatistic();
            if ($respose_statistic['status'] == 1) {
                $post_dec->statisitic = $respose_statistic['Items'];
                $warnings[] = $respose_statistic['warnings'];
            } else {
                $warnings[] = $respose_statistic['warnings'];
                $errors[] = $respose_statistic['errors'];
                throw new Exception('GetExpertiseComapnyExpert. Ошибка при получении статистики');
            }
        } catch (Throwable $exception) {
            $errors[] = 'DeleteExpertiseCompanyExpert. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'DeleteExpertiseCompanyExpert. Конец метода';
        $result = $post_dec;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeleteExpertise() - Метод удаления экспертизы
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * Входные обязательные параметры:
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\IndustrialSafetyExpertise&method=DeleteExpertise&subscribe=&data={"expertise_id":23,"company_department_id":20028766}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 05.12.2019 16:44
     */
    public static function DeleteExpertise($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $post_dec = null;
        $warnings[] = 'DeleteExpertise. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('DeleteExpertise. Не переданы входные параметры');
            }
            $warnings[] = 'DeleteExpertise. Данные успешно переданы';
            $warnings[] = 'DeleteExpertise. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'DeleteExpertise. Декодировал входные параметры';
            if (!property_exists($post_dec, 'expertise_id') ||
                !property_exists($post_dec, 'company_department_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('DeleteExpertise. Переданы некорректные входные параметры');
            }
            $warnings[] = 'DeleteExpertise. Данные с фронта получены';
            $expertise_id = $post_dec->expertise_id;
            $company_department_id = $post_dec->company_department_id;
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('GetIndustrialSafetyExpertise. Возникла ошибка при получении вложенных участков');
            }
            Expertise::deleteAll(['id' => $expertise_id]);

            $response_statistic = self::GetIndustrialSafetyStatistic($company_departments);
            if ($response_statistic['status'] == 1) {
                $post_dec->statisitc = $response_statistic['Items'];
                $warnings[] = $response_statistic['warnings'];
            } else {
                $warnings[] = $response_statistic['warnings'];
                $errors[] = $response_statistic['errors'];
                throw new Exception('GetIndustrialSafetyExpertise. Возникла ошибка при получении статистики');
            }
        } catch (Throwable $exception) {
            $errors[] = 'DeleteExpertise. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'DeleteExpertise. Конец метода';
        $result = $post_dec;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }
}
