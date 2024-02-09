<?php

namespace frontend\controllers\industrial_safety;

use backend\controllers\Assistant as BackendAssistant;
use DateTime;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\handbooks\DepartmentController;
use frontend\models\Attachment;
use frontend\models\OccupationalIllness;
use frontend\models\OccupationalIllnessAttachment;
use frontend\models\ReasonOccupationalIllness;
use frontend\models\Worker;
use Throwable;

class OccupationalIllnessController extends \yii\web\Controller
{
    // GetReasonOccupationalIllness             - Справочник причин профзаболеваний
    // GetOccupationalIllness                   - Метод получения данных для страницы "Учёт профзаболеваний"
    // GetOccupationalIllnessOne                - метод получения одного профзаболевания по его айди
    // GetRangeAge                              - Диапазон возраста
    // GetRangeExperience                       - Диапазон стажа
    // SaveOccupationalIllness                  - Метод сохранения профзаболевания
    // DeleteOccupationalIllness                - Метод удаления профессионального
    // GetWorkerData                            - Получение информации о сотруднике

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetReasonOccupationalIllness() - Справочник причин профзаболеваний
     * @return array - массив со структурой: [reason_occupational_illness_id]
     *                                                                  id:
     *                                                                  title:
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\OccupationalIllness&method=GetReasonOccupationalIllness&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.11.2019 14:08
     */
    public static function GetReasonOccupationalIllness()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetReasonOccupationalIllness. Начало метода';
        try {
            $result = ReasonOccupationalIllness::find()
                ->select(['id', 'title'])
                ->indexBy('id')
                ->asArray()
                ->all();
        } catch (Throwable $exception) {
            $errors[] = 'GetReasonOccupationalIllness. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetReasonOccupationalIllness. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод GetRangeAge() - Диапазон возраста
     * @param $year - количество лет
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.11.2019 15:20
     */
    public static function GetRangeAge($year)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetRangeAge. Начало метода';
        try {
            if ($year >= 0 && $year <= 40) {
                $result = '< 40';
            } elseif ($year >= 41 && $year <= 45) {
                $result = '41 - 45';
            } elseif ($year >= 46 && $year <= 50) {
                $result = '46 - 50';
            } elseif ($year >= 51 && $year <= 55) {
                $result = '51 - 55';
            } elseif ($year >= 56 && $year <= 60) {
                $result = '56 - 60';
            } elseif ($year > 60) {
                $result = '> 60';
            } else {
                $result = 'Ошибка';
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetRangeAge. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetRangeAge. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetRangeExperience() - Диапазон стажа
     * @param $exp
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * Входные обязательные параметры:
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.11.2019 15:34
     */
    public static function GetRangeExperience($exp)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetRangeExperience. Начало метода';
        try {
            if ($exp >= 0 && $exp <= 5) {
                $result = '< 5';
            } elseif ($exp >= 6 && $exp <= 10) {
                $result = '6 - 10';
            } elseif ($exp >= 11 && $exp <= 15) {
                $result = '11 - 15';
            } elseif ($exp >= 16 && $exp <= 20) {
                $result = '16 - 20';
            } elseif ($exp >= 21 && $exp <= 25) {
                $result = '21 - 25';
            } elseif ($exp >= 26 && $exp <= 30) {
                $result = '26 - 30';
            } elseif ($exp > 30) {
                $result = '> 30';
            } else {
                $result = 'Ошибка';
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetRangeExperience. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetRangeExperience. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод SaveOccupationalIllness() - Метод сохранения профзаболевания
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\OccupationalIllness&method=SaveOccupationalIllness&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 27.11.2019 11:17
     */
    public static function SaveOccupationalIllness($data_post = NULL)
    {
        $method_name = "SaveOccupationalIllness";
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $occ_illness = array();                                                                                // Промежуточный результирующий массив
        $result = array();
        /**
         * полный набор (без документов)
         */
//        $data_post = '{"occupational_illness_id":-1,"worker_id":2910081,"position_id":1749120005,"year":32,"birthdate":"1987-11-26","gender":"М","installed":"Центр короче","experience":11,"reason_occupational_illness_id":2,"diagnosis":"Диагноз","date_act":"2019-11-26","state_at_act":"Состояние на акте","state_at_date":"2019-11-26","state_now":"Состояние сейчас","occupational_illness_attachment_id":-1,"attachment_id":-1,"attachment_title":"Наименование документа","attachment_type":"docx","attachment_path":"тут блоб","attachment_status":"keke"}';
//        $data_post = '{"occupational_illness_id":11,"worker_id":2910081,"position_id":1000286,"year":45,"birthdate":"26.11.1987","gender":"М","installed":"Центр короче","experience":11,"reason_occupational_illness_id":2,"diagnosis":"Диагноз","date_act":"26.11.2019","state_at_act":null,"state_at_date":null,"state_now":null ,"occupational_illness_attachment_id":-1,"attachment_id":-1,"attachment_title":"Наименование документа","attachment_type":"docx","attachment_path":"тут блоб","attachment_status":"keke"}';
//        $data_post = '{"occupational_illness_id":-1,"company_department_id":"20000675","worker_id":"2911640","position_id":"5911","year":"29","birthdate":"11.10.1990","gender":"ж","installed":"Центр+профпатологии","experience":"7","reason_occupational_illness_id":"1","reason_occupational_illness":"Углепородная+пыль","diagnosis":"132","date_act":"05.03.2020","state_at_act":null,"state_at_date":null,"state_now":"1","worker_status":true,"position_experience":1,"document":{"occupational_illness_attachment_id":-1,"attachment_id":-1,"attachment_title":null,"attachment_type":null,"attachment_path":null,"attachment_status":null}}';
        $warnings[] = 'SaveOccupationalIllness. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveOccupationalIllness. Не переданы входные параметры');
            }
            $warnings[] = 'SaveOccupationalIllness. Данные успешно переданы';
            $warnings[] = 'SaveOccupationalIllness. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
//            $warnings[] = $post_dec;
            $session = \Yii::$app->session;
            $warnings[] = 'SaveOccupationalIllness. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'occupational_illness_id') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'company_department_id_for_statistic') ||
                !property_exists($post_dec, 'worker_id') ||
                !property_exists($post_dec, 'position_id') ||
                !property_exists($post_dec, 'year') ||
                !property_exists($post_dec, 'birthdate') ||
                !property_exists($post_dec, 'gender') ||
                !property_exists($post_dec, 'installed') ||
                !property_exists($post_dec, 'experience') ||
                !property_exists($post_dec, 'reason_occupational_illness_id') ||
                !property_exists($post_dec, 'diagnosis') ||
                !property_exists($post_dec, 'date_act') ||
                !property_exists($post_dec, 'state_at_act') ||
                !property_exists($post_dec, 'state_at_date') ||
                !property_exists($post_dec, 'state_now') ||
                !property_exists($post_dec, 'worker_status') ||
                !property_exists($post_dec, 'position_experience') ||
                !property_exists($post_dec, 'document')
//                !property_exists($post_dec,'attachment_title')&&
//                !property_exists($post_dec,'attachment_type')&&
//                !property_exists($post_dec,'attachment_path')&&
//                !property_exists($post_dec,'attachment_status')
            )                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('SaveOccupationalIllness. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveOccupationalIllness. Данные с фронта получены';
            $worker_id = $post_dec->worker_id;
            $position_id = $post_dec->position_id;
            $age = $post_dec->year;
            $birthdate = date('Y-m-d', strtotime($post_dec->birthdate));
            $gender = $post_dec->gender;
            $experience = $post_dec->experience;
            $reason_occupational_illness_id = $post_dec->reason_occupational_illness_id;
            $installed = $post_dec->installed;
            $diagnosis = $post_dec->diagnosis;
            $state_on_act = $post_dec->state_at_act;
            $company_department_id = $post_dec->company_department_id;
            $company_department_id_for_statistic = $post_dec->company_department_id_for_statistic;
            $worker_status = $post_dec->worker_status;
            $position_experience = $post_dec->position_experience;
            if ($post_dec->state_at_date != null) {
                $state_on_date = date('Y-m-d', strtotime($post_dec->state_at_date));
            } else {
                $state_on_date = null;
            }
            $state_now = $post_dec->state_now;
            $date_act = date('Y-m-d', strtotime($post_dec->date_act));
            $document = $post_dec->document;
//    		$attachment_title = $post_dec->attachment_title;
//    		$attachment_type = $post_dec->attachment_type;
//    		$attachment_path = $post_dec->attachment_path;
//    		$attachment_status = $post_dec->attachment_status;
            $occupational_illness_id = $post_dec->occupational_illness_id;
            $occupational_illness_attachment_id = $document->occupational_illness_attachment_id;
            $occ_illness = OccupationalIllness::findOne(['id' => $occupational_illness_id]);
            if (empty($occ_illness)) {
                $occ_illness = new OccupationalIllness();
            }
            $occ_illness->worker_id = $worker_id;
            $occ_illness->position_id = $position_id;
            $occ_illness->age = $age;
            $occ_illness->birthdate = $birthdate;
            $occ_illness->gender = $gender;
            $occ_illness->experience = $experience;
            $occ_illness->reason_occupational_illness_id = $reason_occupational_illness_id;
            $occ_illness->diagnosis = $diagnosis;
            $occ_illness->state_on_act = $state_on_act;
            $occ_illness->state_on_date = $state_on_date;
            $occ_illness->state_now = $state_now;
            $occ_illness->date_act = $date_act;
            $occ_illness->installed = $installed;
            $occ_illness->company_department_id = $company_department_id;
            if ($worker_status == false) {
                $worker_status = 0;
            } else {
                $worker_status = 1;
            }
            $occ_illness->worker_status = $worker_status;
            $occ_illness->position_experience = $position_experience;
            if ($occ_illness->save()) {
                $warnings[] = 'SaveOccupationalIllness. Добавление профзаболевания прошло успешно';
                $occ_illness->refresh();
                $occ_illness_id = $occ_illness->id;
                $post_dec->occupational_illness_id = $occ_illness->id;
            } else {
                $errors[] = $occ_illness->errors;
                throw new Exception('SaveOccupationalIllness. Ошибка при сохранениии профзаболевания');
            }
            if ($document->attachment_status == 'new') {
                $normalize_path = Assistant::UploadFile($document->attachment_path, $document->attachment_title, 'attachment', $document->attachment_type);
                $add_attachment = new Attachment();
                $add_attachment->path = $normalize_path;
                $add_attachment->date = BackendAssistant::GetDateFormatYMD();
                $add_attachment->worker_id = $session['worker_id'];
                $add_attachment->section_title = 'ОТ и ПБ/Учет профзаболеваний';
                $add_attachment->title = $document->attachment_title;
                $add_attachment->attachment_type = $document->attachment_type;
//                $add_attachment->sketch = $attachment_path;
                if ($add_attachment->save()) {
                    $warnings[] = 'SaveOccupationalIllness. Вложение успешно сохранено';
                    $add_attachment->refresh();
                    $post_dec->attachment_path = $add_attachment->path;
                    $attachment_id = $add_attachment->id;
                } else {
                    $errors[] = $add_attachment->errors;
                    throw new Exception('SaveOccupationalIllness. Ошибка при сохранении вложения');
                }
                $add_occ_illness_attachemnt = new OccupationalIllnessAttachment();
                $add_occ_illness_attachemnt->attachment_id = $attachment_id;
                $add_occ_illness_attachemnt->occupational_illness_id = $occ_illness_id;
                if ($add_occ_illness_attachemnt->save()) {
                    $warnings[] = 'SaveOccupationalIllness. Связка вложения и профзаболевания успешно сохранена';
                    $add_occ_illness_attachemnt->refresh();
                    $post_dec->occupationa_illness_attachment_id = $add_occ_illness_attachemnt->id;
                } else {
                    $errors[] = $add_occ_illness_attachemnt->errors;
                    throw new Exception('SaveOccupationalIllness. Ошибка при сохранении связки вложения и профзаболевания');
                }
            } elseif ($document->attachment_status == 'del') {
                $del = OccupationalIllnessAttachment::deleteAll(['id' => $occupational_illness_attachment_id]);
            }
//            $result = $post_dec;

//            $json_occ_illness = json_encode(array('occupational_illness_id' => $occ_illness_id));
            $response = self::GetOccupationalIllnessOne($occ_illness_id);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $result['occupationalIllnessOne'] = $response['Items'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $result['occupationalIllnessOne'] = (object)array();
            }
            $response = DepartmentController::FindDepartment($company_department_id_for_statistic);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка при получении списка вложенных участков');
            }

            $result['statistic'] = self::GetStatisticOccupationalIllness($company_departments);
        } catch (Throwable $exception) {
            $errors[] = 'SaveOccupationalIllness. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SaveOccupationalIllness. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteOccupationalIllness() - Метод удаления профессионального заболевания
     * @param null $data_post - JSON с идентификатором профзаболевания
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\OccupationalIllness&method=DeleteOccupationalIllness&subscribe=&data={"occupational_illness_id":8}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 27.11.2019 16:22
     */
    public static function DeleteOccupationalIllness($data_post = NULL)
    {
        $method_name = "DeleteOccupationalIllness";
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $del_occ_illness = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'DeleteOccupationalIllness. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('DeleteOccupationalIllness. Не переданы входные параметры');
            }
            $warnings[] = 'DeleteOccupationalIllness. Данные успешно переданы';
            $warnings[] = 'DeleteOccupationalIllness. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'DeleteOccupationalIllness. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'occupational_illness_id') ||
                !property_exists($post_dec, 'company_department_id')
            )                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('DeleteOccupationalIllness. Переданы некорректные входные параметры');
            }
            $warnings[] = 'DeleteOccupationalIllness. Данные с фронта получены';
            $occupational_illness_id = $post_dec->occupational_illness_id;
            $company_department_id = $post_dec->company_department_id;
            $del_occupational_illness_id = OccupationalIllness::deleteAll(['id' => $occupational_illness_id]);

            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка при получении списка вложенных участков');
            }

            $del_occ_illness['statistic'] = self::GetStatisticOccupationalIllness($company_departments);

        } catch (Throwable $exception) {
            $errors[] = 'DeleteOccupationalIllness. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'DeleteOccupationalIllness. Конец метода';
        $result = $del_occ_illness;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetWorkerData() - Получение информации о сотруднике
     * @param null $data_post - JSON с идентификатором работника
     * @return array массив со следующей структурой: company_title:
     *                                               position_title:
     *                                               gender:
     *                                               birthdate:
     *                                               date_start_work:
     *                                               age:
     *                                               experience:
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\OccupationalIllness&method=GetWorkerData&subscribe=&data={"worker_id":2052631}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 29.11.2019 15:50
     */
    public static function GetWorkerData($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $worker_data = array();                                                                                         // Промежуточный результирующий массив
        $warnings[] = 'GetWorkerData. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetWorkerData. Не переданы входные параметры');
            }
            $warnings[] = 'GetWorkerData. Данные успешно переданы';
            $warnings[] = 'GetWorkerData. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetWorkerData. Декодировал входные параметры';
            if (!property_exists($post_dec, 'worker_id'))                                                       // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetWorkerData. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetWorkerData. Данные с фронта получены';
            $worker_id = $post_dec->worker_id;

            $get_worker_data = Worker::find()
                ->select([
                    'company.title as company_title',
                    'company_department.id as company_department_id',
                    'position.title as position_title',
                    'position.id as position_id',
                    'employee.gender',
                    'employee.birthdate',
                    'worker.date_start as date_start_work',
                    'worker.date_end as date_end_work',
                    'worker.employee_id as employee_id'
                ])
                ->innerJoin('company_department', 'company_department.id = worker.company_department_id')
                ->innerJoin('company', 'company.id = company_department.company_id')
                ->innerJoin('position', 'position.id = worker.position_id')
                ->innerJoin('employee', 'employee.id = worker.employee_id')
                ->where(['worker.id' => $worker_id])
                ->asArray()
                ->one();
            $worker_data['company_department_id'] = $get_worker_data['company_department_id'];
            $worker_data['company_title'] = $get_worker_data['company_title'];
            $worker_data['position_id'] = $get_worker_data['position_id'];
            $worker_data['position_title'] = $get_worker_data['position_title'];
            $worker_data['gender'] = $get_worker_data['gender'];
            $worker_data['birthdate'] = date('d.m.Y', strtotime($get_worker_data['birthdate']));
            $worker_data['date_start_work'] = date('d.m.Y', strtotime($get_worker_data['date_start_work']));
            $birthdate = new DateTime($get_worker_data['birthdate']);
            $date_start = new DateTime($get_worker_data['date_start_work']);
            $today = BackendAssistant::GetDateFormatYMD();
            $date_now = new DateTime(BackendAssistant::GetDateFormatYMD());
            $diff_age = $date_now->diff($birthdate);

            $worker_data['age'] = $diff_age->format('%y');
            if ($get_worker_data['date_end_work'] === null || $get_worker_data['date_end_work'] > $today) {
                $flag = true;
            } else {
                $flag = false;
                $date_now = new DateTime($get_worker_data['date_end_work']);
            }
            $diff_exp = $date_now->diff($date_start);
            $worker_data['experience'] = $diff_exp->format('%y');
            $worker_data['worker_status'] = $flag;
            $employee_opportunity = Worker::find()                                                                      // Находим минимальную по дате запись о работнике, то есть когда его только приняли
            ->select(['worker.date_start AS worker_date_start', 'worker.date_end AS worker_date_end'])
                ->where(['worker.id' => $worker_id])
                ->orderBy('worker.date_start ASC')
                ->asArray()
                ->one();

            $date_end = date('Y-m-d H:i:s', strtotime($employee_opportunity['worker_date_end']));
            if ($date_end > $today) {
                $date_end = $today;
            }
            $date_start_position = new DateTime(date('Y-m-d H:i:s', strtotime($employee_opportunity['worker_date_start'])));
            $date_end_position = new DateTime($date_end);
            $diff_position = $date_end_position->diff($date_start_position);
            $diff_position_format = $diff_position->format('%y');
            $worker_data['position_experience'] = $diff_position_format;

        } catch (Throwable $exception) {
            $errors[] = 'GetWorkerData. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetWorkerData. Конец метода';
        return array('Items' => $worker_data, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    //TODO 22.01.2020 rudov: когда фронтэнд будет готов передавать участок сменить название функции  на GetOccupationalIllness

    // GetOccupationalIllness - метод получения списка профзаболеваний
    public static function GetOccupationalIllness($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetOccupationalIllness';
        $occupational_illness_id = null;
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
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка при получении списка вложенных участков');
            }
            if (property_exists($post_dec, 'occupational_illness_id')) {
                $occupational_illness_id = $post_dec->occupational_illness_id;
            }
            $occ_illness = OccupationalIllness::find()
                ->joinWith('worker.employee')
                ->joinWith('position')
                ->joinWith('companyDepartment.company')
                ->joinWith('reasonOccupationalIllness')
                ->joinWith('occupationalIllnessAttachments.attachment')
                ->joinWith('occupationalIllnessAttachments')
                ->where(['in', 'occupational_illness.company_department_id', $company_departments])
                ->filterWhere(['occupational_illness.id' => $occupational_illness_id])
                ->all();
            $result['table'] = array();
            $counter = 0;
            if ($occ_illness) {
                foreach ($occ_illness as $illness_item) {
                    $comp_dep_id = $illness_item->companyDepartment->company->id;
                    $comp_dep_title = $illness_item->companyDepartment->company->title;
                    $worker_id = $illness_item->worker->id;
                    $occ_illness_id = $illness_item->id;

                    $result['table'][$counter]['occupational_illness_id'] = $occ_illness_id;
                    $result['table'][$counter]['worker_id'] = $worker_id;
                    $result['table'][$counter]['stuff_number'] = $illness_item->worker->tabel_number;
                    $name = mb_substr($illness_item->worker->employee->first_name, 0, 1);
                    $patronymic = mb_substr($illness_item->worker->employee->patronymic, 0, 1);
                    $result['table'][$counter]['full_name'] = "{$illness_item->worker->employee->last_name} {$name}. {$patronymic}.";
                    $result['table'][$counter]['company_department_id'] = $comp_dep_id;
                    $result['table'][$counter]['company_title'] = $comp_dep_title;
                    $result['table'][$counter]['gender'] = $illness_item->gender;
                    $result['table'][$counter]['birthdate'] = date('d.m.Y', strtotime($illness_item->birthdate));
                    $result['table'][$counter]['year'] = $illness_item->age;
                    $range_year = '< 40';
                    $range_year_result = self::GetRangeAge($illness_item->age);
                    if ($range_year_result['status'] == 1) {
                        $range_year = $range_year_result['Items'];
                        $warnings[] = $range_year_result['warnings'];
                    } else {
                        $warnings[] = $range_year_result['warnings'];
                        $errors[] = $range_year_result['errors'];
                    }
                    $result['table'][$counter]['range_year'] = $range_year;
                    $result['table'][$counter]['experience'] = $illness_item->experience;
                    $range_exp_result = self::GetRangeExperience($illness_item->experience);
                    if ($range_exp_result['status'] == 1) {
                        $range_exp = $range_exp_result['Items'];
                        $warnings[] = $range_exp_result['warnings'];
                    } else {
                        $warnings[] = $range_exp_result['warnings'];
                        $errors[] = $range_exp_result['errors'];
                    }
                    $result['table'][$counter]['range_experience'] = $range_exp;
                    $date_end_work = $illness_item->worker->date_end;
                    if ($date_end_work == null) {
                        $flag = true;
                    } elseif (strtotime($illness_item->worker->date_end) > strtotime(BackendAssistant::GetDateFormatYMD())) {
                        $flag = true;
                    } else {
                        $flag = false;
                    }
                    $result['table'][$counter]['flag'] = $flag;
                    $result['table'][$counter]['position_id'] = $illness_item->position->id;
                    $result['table'][$counter]['position_title'] = $illness_item->position->title;
                    $result['table'][$counter]['reason_occupational_illness'] = $illness_item->reasonOccupationalIllness->title;
                    $result['table'][$counter]['reason_occupational_illness_id'] = $illness_item->reasonOccupationalIllness->id;
                    $result['table'][$counter]['diagnosis'] = $illness_item->diagnosis;
                    $result['table'][$counter]['installed'] = $illness_item->installed;
                    $result['table'][$counter]['date_act'] = date('d.m.Y', strtotime($illness_item->date_act));
                    $result['table'][$counter]['state_now'] = $illness_item->state_now;
                    if ($illness_item->worker_status === 0) {
                        $worker_status = false;
                    } else {
                        $worker_status = true;
                    }
                    $result['table'][$counter]['worker_status'] = $worker_status;
                    $result['table'][$counter]['position_experience'] = $illness_item->position_experience;
                    if ($illness_item->state_on_date !== null) {
                        $state_on_date = date('d.m.Y', strtotime($illness_item->state_on_date));
                    } else {
                        $state_on_date = null;
                    }
                    $result['table'][$counter]['state_at_act'] = $illness_item->state_on_act;
                    $result['table'][$counter]['state_at_date'] = $state_on_date;
                    $result['table'][$counter]['document'] = array();
                    foreach ($illness_item->occupationalIllnessAttachments as $occupationalIllnessAttachment) {
                        $occupationalIllnessAttachment_id = $occupationalIllnessAttachment->id;
                        $result['table'][$counter]['document']['occupational_illness_attachment_id'] = $occupationalIllnessAttachment->id;
                        $result['table'][$counter]['document']['attachment_id'] = $occupationalIllnessAttachment->attachment->id;
                        $result['table'][$counter]['document']['attachment_title'] = $occupationalIllnessAttachment->attachment->title;
                        $result['table'][$counter]['document']['attachment_type'] = $occupationalIllnessAttachment->attachment->attachment_type;
                        $result['table'][$counter]['document']['attachment_path'] = $occupationalIllnessAttachment->attachment->path;
                        $result['table'][$counter]['document']['attachment_status'] = null;
                    }
                    if (empty($result['table'][$counter]['document'])) {
                        $result['table'][$counter]['document']['occupational_illness_attachment_id'] = null;
                        $result['table'][$counter]['document']['attachment_id'] = null;
                        $result['table'][$counter]['document']['attachment_title'] = null;
                        $result['table'][$counter]['document']['attachment_type'] = null;
                        $result['table'][$counter]['document']['attachment_path'] = null;
                        $result['table'][$counter]['document']['attachment_status'] = null;
                    }
                    $counter++;
                }
            }
            if (empty($result['table'])) {
                $result['table'] = array();
            }
            $get_statistic_occupational_illness = self::GetStatisticOccupationalIllness($company_departments);
            $result['statistic'] = $get_statistic_occupational_illness;
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

    // GetOccupationalIllnessOne - метод получения одного профзаболевания по его айди
    //  входные параметры:
    //      $occupational_illness_id    - ключ профзаболевания
    //  выходной объект
    //  table:
    //    []
    //      occupational_illness_id	        71                          - ключ профзаболевания
    //      worker_id	                    1095123                     - ключ работника
    //      stuff_number	                1095123                     - табельный номер работника
    //      full_name	                    - Гомой Иваном А -. -.      - ФИО работника
    //      company_department_id	        1                           - ключ подразделения
    //      company_title	                Ростехнадзор                - наименование подразделения
    //      gender	                        М                           - гендерный признак
    //      birthdate	                    01.01.1970                  - дата рождения работника
    //      year	                        50                          - полных лет
    //      range_year	                    46 - 50                     - диапазон возраста
    //      experience	                    50                          - стаж
    //      range_experience	            > 30                        - диапазон стажа
    //      flag	                        true                        - хз что это такое
    //      position_id	                    1001572                     - ключ должности
    //      position_title	                Инспектор                   - наименование должности
    //      reason_occupational_illness	    Тяжесть                     - наименвоание вредного фактора
    //      reason_occupational_illness_id	3                           - ключ вредного фактора
    //      diagnosis	                    1                           - заключительный диагноз
    //      installed	                    Центр профпатологии         - кем установлено профзаболевание
    //      date_act	                    02.03.2020                  - дата акта
    //      state_now	                    1                           - состояние на сейчас
    //      state_at_act	                1                           - состояние на дату
    //      state_at_date	                02.03.2020                  - дата на которую определяется состояни
    //      document	{…}                                             - вложенный документ
    //          occupational_illness_attachment_id	null                    - ключ вложения профзаболевания
    //          attachment_id	null                                        - ключ вложения
    //          attachment_title	null                                    - наименование вложения
    //          attachment_type	null                                        - тип вложения
    //          attachment_path	null                                        - путь до вложения
    //          attachment_status	null                                    - статус вложения (del - на удаление)
    // разработал: Якимов М.Н. (метод скопирован с метода GetOccupationalIllness и убран лишний код)
    // дата: 02.03.2020
    public static function GetOccupationalIllnessOne($occupational_illness_id = null)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetOccupationalIllnessOne';

        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($occupational_illness_id == NULL && $occupational_illness_id == -1) {
                throw new Exception($method_name . '. Не корректный ключ профзаболевания = ' . $occupational_illness_id);
            }
            $warnings[] = $method_name . '. Данные успешно переданы';

            $occ_illness = OccupationalIllness::find()
                ->joinWith('worker.employee')
                ->joinWith('position')
                ->joinWith('companyDepartment.company')
                ->joinWith('reasonOccupationalIllness')
                ->joinWith('occupationalIllnessAttachments.attachment')
                ->joinWith('occupationalIllnessAttachments')
                ->where(['occupational_illness.id' => $occupational_illness_id])
                ->all();
            $result['table'] = array();
            $counter = 0;
            if ($occ_illness) {
                foreach ($occ_illness as $illness_item) {
                    $comp_dep_id = $illness_item->companyDepartment->company->id;
                    $comp_dep_title = $illness_item->companyDepartment->company->title;
                    $worker_id = $illness_item->worker->id;
                    $occ_illness_id = $illness_item->id;

                    $result['table'][$counter]['occupational_illness_id'] = $occ_illness_id;
                    $result['table'][$counter]['worker_id'] = $worker_id;
                    $result['table'][$counter]['stuff_number'] = $illness_item->worker->tabel_number;
                    $name = mb_substr($illness_item->worker->employee->first_name, 0, 1);
                    $patronymic = mb_substr($illness_item->worker->employee->patronymic, 0, 1);
                    $result['table'][$counter]['full_name'] = "{$illness_item->worker->employee->last_name} {$name}. {$patronymic}.";
                    $result['table'][$counter]['company_department_id'] = $comp_dep_id;
                    $result['table'][$counter]['company_title'] = $comp_dep_title;
                    $result['table'][$counter]['gender'] = $illness_item->gender;
                    $result['table'][$counter]['birthdate'] = date('d.m.Y', strtotime($illness_item->birthdate));
                    $result['table'][$counter]['year'] = $illness_item->age;
                    $range_year = '< 40';
                    $range_year_result = self::GetRangeAge($illness_item->age);
                    if ($range_year_result['status'] == 1) {
                        $range_year = $range_year_result['Items'];
                        $warnings[] = $range_year_result['warnings'];
                    } else {
                        $warnings[] = $range_year_result['warnings'];
                        $errors[] = $range_year_result['errors'];
                    }
                    $result['table'][$counter]['range_year'] = $range_year;
                    $result['table'][$counter]['experience'] = $illness_item->experience;
                    $range_exp_result = self::GetRangeExperience($illness_item->experience);
                    if ($range_exp_result['status'] == 1) {
                        $range_exp = $range_exp_result['Items'];
                        $warnings[] = $range_exp_result['warnings'];
                    } else {
                        $warnings[] = $range_exp_result['warnings'];
                        $errors[] = $range_exp_result['errors'];
                    }
                    $result['table'][$counter]['range_experience'] = $range_exp;
                    $date_end_work = $illness_item->worker->date_end;
                    if ($date_end_work == null) {
                        $flag = true;
                    } elseif (strtotime($illness_item->worker->date_end) > strtotime(BackendAssistant::GetDateFormatYMD())) {
                        $flag = true;
                    } else {
                        $flag = false;
                    }
                    $result['table'][$counter]['flag'] = $flag;
                    $result['table'][$counter]['position_id'] = $illness_item->position->id;
                    $result['table'][$counter]['position_title'] = $illness_item->position->title;
                    $result['table'][$counter]['reason_occupational_illness'] = $illness_item->reasonOccupationalIllness->title;
                    $result['table'][$counter]['reason_occupational_illness_id'] = $illness_item->reasonOccupationalIllness->id;
                    $result['table'][$counter]['diagnosis'] = $illness_item->diagnosis;
                    $result['table'][$counter]['installed'] = $illness_item->installed;
                    $result['table'][$counter]['date_act'] = date('d.m.Y', strtotime($illness_item->date_act));
                    $result['table'][$counter]['state_now'] = $illness_item->state_now;
                    if ($illness_item->state_on_date !== null) {
                        $state_on_date = date('d.m.Y', strtotime($illness_item->state_on_date));
                    } else {
                        $state_on_date = null;
                    }
                    $result['table'][$counter]['state_at_act'] = $illness_item->state_on_act;
                    $result['table'][$counter]['state_at_date'] = $state_on_date;
                    if ($illness_item->worker_status === 0) {
                        $worker_status = false;
                    } else {
                        $worker_status = true;
                    }
                    $result['table'][$counter]['worker_status'] = $worker_status;
                    $result['table'][$counter]['position_experience'] = $illness_item->position_experience;
                    $result['table'][$counter]['document'] = array();
                    foreach ($illness_item->occupationalIllnessAttachments as $occupationalIllnessAttachment) {
                        $occupationalIllnessAttachment_id = $occupationalIllnessAttachment->id;
                        $result['table'][$counter]['document']['occupational_illness_attachment_id'] = $occupationalIllnessAttachment->id;
                        $result['table'][$counter]['document']['attachment_id'] = $occupationalIllnessAttachment->attachment->id;
                        $result['table'][$counter]['document']['attachment_title'] = $occupationalIllnessAttachment->attachment->title;
                        $result['table'][$counter]['document']['attachment_type'] = $occupationalIllnessAttachment->attachment->attachment_type;
                        $result['table'][$counter]['document']['attachment_path'] = $occupationalIllnessAttachment->attachment->path;
                        $result['table'][$counter]['document']['attachment_status'] = null;
                    }
                    if (empty($result['table'][$counter]['document'])) {
                        $result['table'][$counter]['document']['occupational_illness_attachment_id'] = null;
                        $result['table'][$counter]['document']['attachment_id'] = null;
                        $result['table'][$counter]['document']['attachment_title'] = null;
                        $result['table'][$counter]['document']['attachment_type'] = null;
                        $result['table'][$counter]['document']['attachment_path'] = null;
                        $result['table'][$counter]['document']['attachment_status'] = null;
                    }
                    $counter++;
                }
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

// http://amicum/read-manager-amicum?controller=industrial_safety\OccupationalIllness&method=GetStatisticOccupationalIllness&subscribe=&data={}
    private static function GetStatisticOccupationalIllness($company_departments)
    {
        $date_now = BackendAssistant::GetDateFormatYMD();

        /******************** СТАТИТСИКА ЛЮДЕЙ ********************/
        $found_worker = Worker::find()
            ->select('count(worker.id) as count_worker, employee.gender')
            ->innerJoin('employee', 'employee.id = worker.employee_id')
            ->where(['in', 'worker.company_department_id', $company_departments])
            ->andWhere(['<=', 'worker.date_start', $date_now])
            ->andWhere(['or',
                ['>', 'worker.date_end', $date_now],
                ['is', 'worker.date_end', null]
            ])
            ->asArray()
            ->groupBy('employee.gender')
            ->indexBy('gender')
            ->all();

        $result['main_statistic']['count_worker_women'] = 0;
        $result['main_statistic']['count_worker_men'] = 0;
        if (isset($found_worker['М'])) {
            $result['main_statistic']['count_worker_men'] = (int)$found_worker['М']['count_worker'];
        }
        if (isset($found_worker['Ж'])) {
            $result['main_statistic']['count_worker_women'] = (int)$found_worker['Ж']['count_worker'];
        }
        $result['main_statistic']['count_worker'] = $result['main_statistic']['count_worker_women'] + $result['main_statistic']['count_worker_men'];

        /******************** СТАТИСТИКА СОТРУДНИКОВ С ВЫЯВЛЕННЫМИ ПРОФЗАБОЛЕВАНИЯМИ ********************/
        $count_illness_worker = OccupationalIllness::find()
            ->select(['worker.id as worker_id', 'employee.gender'])
            ->innerJoin('worker', 'occupational_illness.worker_id = worker.id')
            ->innerJoin('employee', 'worker.employee_id = employee.id')
            ->where(['in', 'occupational_illness.company_department_id', $company_departments])
            ->groupBy('employee.gender,worker.id')
//            ->andWhere("YEAR(occupational_illness.date_act)='" . $year . "'")
//            ->andFilterWhere(['MONTH(occupational_illness.date_act)' => $month])
//            ->indexBy('gender')
            ->asArray()
            ->all();

        $result['workers_with_disease']['count_worker_with_illness'] = 0;
        $result['workers_with_disease']['count_worker_with_illness_men'] = 0;
        $result['workers_with_disease']['count_worker_with_illness_women'] = 0;
        foreach ($count_illness_worker as $item) {
            $result['workers_with_disease']['count_worker_with_illness']++;
            if ($item['gender'] == 'М') {
                $result['workers_with_disease']['count_worker_with_illness_men']++;
            } elseif ($item['gender'] == 'Ж') {
                $result['workers_with_disease']['count_worker_with_illness_women']++;
            }
        }
        /******************** КОЛИЧЕСТВО ПРОФЗАБОЛЕВАНИЙ ********************/
        $count_illness = OccupationalIllness::find()
            ->select('count(occupational_illness.id)')
            ->where(['in', 'occupational_illness.company_department_id', $company_departments])
//                ->andWhere(['<', 'occupational_illness.date_act', $date])
//            ->andWhere("YEAR(occupational_illness.date_act)='" . $year . "'")
//            ->andFilterWhere(['MONTH(occupational_illness.date_act)' => $month])
            ->scalar();
        $result['count_occupational_illness'] = (int)$count_illness;
        /******************** СТАТИСИКА ПО ПРОЦЕНТАМ ********************/
        $precent_occ_illness = OccupationalIllness::find()
            ->select('reason_occupational_illness_id, reason_occupational_illness.title as reason_occupational_illness_title, count(occupational_illness.id) as reason_occupational_illness_value')
            ->innerJoin('reason_occupational_illness', 'reason_occupational_illness.id = occupational_illness.reason_occupational_illness_id')
            ->where(['in', 'occupational_illness.company_department_id', $company_departments])
//                ->andWhere(['<', 'occupational_illness.date_act', $date])
//            ->andWhere("YEAR(occupational_illness.date_act)='" . $year . "'")
//            ->andFilterWhere(['MONTH(occupational_illness.date_act)' => $month])
            ->asArray()
            ->groupBy('reason_occupational_illness_id, reason_occupational_illness_title')
            ->indexBy('reason_occupational_illness_id')
            ->all();
        foreach ($precent_occ_illness as $item) {
            $found_worker_percent[$item['reason_occupational_illness_title']] = round(($item['reason_occupational_illness_value'] / $result['count_occupational_illness']) * 100, 1);
//            $found_worker_percent[$item['reason_occupational_illness_id']]['reason_occupational_illness_title'] = $item['reason_occupational_illness_title'];
//            $found_worker_percent[$item['reason_occupational_illness_id']]['reason_occupational_illness_value'] = (int)$item['reason_occupational_illness_value'];
//            $found_worker_percent[$item['reason_occupational_illness_id']]['reason_occupational_illness_id'] = $item['reason_occupational_illness_id'];
        }
        if (!isset($found_worker_percent)) {
            $result['disease_percentage'] = (object)array();
        } else {
            $result['disease_percentage'] = $found_worker_percent;
        }
        unset($found_worker);
        return $result;
    }
}
