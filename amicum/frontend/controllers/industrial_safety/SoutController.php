<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers\industrial_safety;

use backend\controllers\Assistant as BackendAssistant;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\handbooks\DepartmentController;
use frontend\models\Attachment;
use frontend\models\CheckingSoutType;
use frontend\models\Company;
use frontend\models\ContingentFromSout;
use frontend\models\ContingentHarmfulFactorSout;
use frontend\models\PlaceType;
use frontend\models\PlannedSout;
use frontend\models\PlannedSoutCompanyExpert;
use frontend\models\PlannedSoutWorkingPlace;
use frontend\models\ResearchIndex;
use frontend\models\ResearchType;
use frontend\models\Sout;
use frontend\models\SoutAttachment;
use frontend\models\SoutResearch;
use frontend\models\Worker;
use frontend\models\WorkingPlace;
use Throwable;
use Yii;
use yii\db\Query;

class SoutController extends \yii\web\Controller
{
    // GetCheckingSoutType          - Справочник типов проверки СОУТ/ПК
    // GetPlaceTypes                - Справочник типов мест
    // GetCompanyExpert             - Справочник компаний экспертов
    // GetResearch                  - Справочник факторов и их показателей
    // SaveSout                     - Метод сохранения СОУТ/ПК для таблицы
    // GetSout                      - Метод получения данных для таблицы по СОУТ/ПК
    // DeleteSout                   - Метод удаления СОУТ/ПК
    // GetPlannedSout               - Метод получения планового СОУТ/ПК
    // SavePlannedSout              - Метод сохранения планового СОУТ/ПК
    // DeletePlannedSout            - Метод удаления планового СОУТ/ПК
    // SaveNewResearch              - метод сохранения справочника параметров СОУТ

    const GENDER_WOMAN = 'Ж';
    const GENDER_MAN = 'М';
    const SOUT_TYPE_SOUT = 1;
    const SOUT_TYPE_PK = 2;

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetCheckingSoutType() - Справочник типов проверки СОУТ/ПК
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Sout&method=GetCheckingSoutType&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 09.12.2019 10:14
     */
    public static function GetCheckingSoutType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetCheckingSoutType. Начало метода';
        try {
            $result = CheckingSoutType::find()
                ->select(['id', 'title'])
                ->indexBy('id')
                ->asArray()
                ->all();
        } catch (Throwable $exception) {
            $errors[] = 'GetCheckingSoutType. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetCheckingSoutType. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetPlaceTypes() - Справочник типов мест
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Sout&method=GetPlaceTypes&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 09.12.2019 10:43
     */
    public static function GetPlaceTypes()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetPlaceTypes. Начало метода';
        try {
            $result = PlaceType::find()
                ->select(['id', 'title'])
                ->asArray()
                ->indexBy('id')
                ->all();
        } catch (Throwable $exception) {
            $errors[] = 'GetPlaceTypes. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetPlaceTypes. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetResearch() - Справочник факторов и их показателей
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Sout&method=GetResearch&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 09.12.2019 10:49
     */
    public static function GetResearch()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetResearch. Начало метода';
        try {
            $researches = ResearchType::find()
                ->joinWith('researchIndices')
                ->all();
            if (!empty($researches)) {
                foreach ($researches as $research) {
                    $research_type_id = $research->id;
                    $research_type_title = $research->title;
                    $result[$research_type_id]['research_type_id'] = $research_type_id;
                    $result[$research_type_id]['research_type_title'] = $research_type_title;
                    $result[$research_type_id]['indexes'] = array();
                    foreach ($research->researchIndices as $researchIndex) {
                        $research_index_id = $researchIndex->id;
                        $result[$research_type_id]['indexes'][$research_index_id]['research_index_id'] = $research_index_id;
                        $result[$research_type_id]['indexes'][$research_index_id]['research_index_title'] = $researchIndex->title;
                    }
                    if (empty($result[$research_type_id]['indexes'])) {
                        $result[$research_type_id]['indexes'] = (object)array();
                    }
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetResearch. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetResearch. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetSout() - Метод получения данных для таблицы по СОУТ/ПК
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Sout&method=GetSout&subscribe=&data={"company_department_id":20028766,"year":2019,"month":12}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 09.12.2019 11:56
     */
    public static function GetSout($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $sout_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'GetSout. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetSout. Не переданы входные параметры');
            }
            $warnings[] = 'GetSout. Данные успешно переданы';
            $warnings[] = 'GetSout. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetSout. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'year') ||
                !property_exists($post_dec, 'month')
            )                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetSout. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetSout. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception('GetSout. Ошибка получения вложенных департаментов' . $company_department_id);
            }
            $year = $post_dec->year;
            $month = $post_dec->month;
            $date_start = $year . '-' . $month . '-01';
            $cal_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $date_end = $year . '-' . $month . '-' . $cal_day;

            $souts = Sout::find()
                ->joinWith('companyDepartment.company')
                ->joinWith('companyExpert')
                ->joinWith('placeType')
                ->joinWith('place')
                ->joinWith('role')
                ->joinWith('soutType')
                ->joinWith('contingentFromSouts.contingentHarmfulFactorSouts.harmfulFactors')
                ->joinWith('soutAttachments')
                ->joinWith('soutResearches.research.researchType')
                ->where(['in', 'sout.company_department_id', $company_departments])
                ->andWhere(['between', 'sout.date', $date_start, $date_end])
                ->all();
            $counter = 0;
            if (!empty($souts)) {
                foreach ($souts as $sout) {
                    $comp_dep_id = $sout->company_department_id;
                    $sout_data['soutData'][$counter]['sout_id'] = $sout->id;
                    $sout_data['soutData'][$counter]['soutNumber'] = $sout->number;
                    $sout_data['soutData'][$counter]['type_id'] = $sout->sout_type_id;
                    $sout_data['soutData'][$counter]['type'] = $sout->soutType->title;
                    $sout_data['soutData'][$counter]['company_department_id'] = $comp_dep_id;
                    $sout_data['soutData'][$counter]['company_title'] = $sout->companyDepartment->company->title;
                    $sout_data['soutData'][$counter]['place_id'] = $sout->place_id;
                    $sout_data['soutData'][$counter]['place_title'] = $sout->place->title;
                    $sout_data['soutData'][$counter]['place_type_id'] = $sout->place_type_id;
                    $sout_data['soutData'][$counter]['place_type_title'] = $sout->placeType->title;
                    $sout_data['soutData'][$counter]['role_title'] = $sout->role->title;
                    $sout_data['soutData'][$counter]['role_id'] = $sout->role_id;
                    $sout_data['soutData'][$counter]['soutWorkers'] = $sout->count_worker;
                    $sout_data['soutData'][$counter]['companyExpert_id'] = $sout->company_expert_id;
                    $sout_data['soutData'][$counter]['companyExpert'] = $sout->companyExpert->title;
                    $sout_data['soutData'][$counter]['class'] = (float)$sout->class;
                    $sout_data['soutData'][$counter]['date'] = $sout->date;
                    $sout_data['soutData'][$counter]['date_format'] = date('d.m.Y', strtotime($sout->date));
                    $sout_data['soutData'][$counter]['files'] = array();
                    $counter_files = 0;
                    foreach ($sout->soutAttachments as $soutAttachment) {
                        $sout_data['soutData'][$counter]['files'][$counter_files]['sout_attachment_id'] = $soutAttachment->id;
                        $sout_data['soutData'][$counter]['files'][$counter_files]['attachment_id'] = $soutAttachment->attachment->id;
                        $sout_data['soutData'][$counter]['files'][$counter_files]['attachment_title'] = $soutAttachment->attachment->title;
                        $sout_data['soutData'][$counter]['files'][$counter_files]['attachment_path'] = $soutAttachment->attachment->path;
                        $sout_data['soutData'][$counter]['files'][$counter_files]['attachment_type'] = $soutAttachment->attachment->attachment_type;
                        $sout_data['soutData'][$counter]['files'][$counter_files]['attachment_status'] = null;
                        $counter_files++;
                    }
//                    if (empty($sout_data['soutData'][$counter]['files'])){
//                        $sout_data['soutData'][$counter]['files'] = (object)array();
//                    }
//                    $counter_research = 0;
                    $sout_data['soutData'][$counter]['research'] = array();
                    foreach ($sout->soutResearches as $soutResearch) {
                        $research_type_id = $soutResearch->research->researchType->id;
                        $research_id = $soutResearch->research->id;
                        $sout_data['soutData'][$counter]['research'][$research_type_id]['research_type_id'] = $soutResearch->research->researchType->id;
                        $sout_data['soutData'][$counter]['research'][$research_type_id]['research_type_title'] = $soutResearch->research->researchType->title;
                        $sout_data['soutData'][$counter]['research'][$research_type_id]['indexes'][$research_id]['sout_research_id'] = $soutResearch->id;
                        $sout_data['soutData'][$counter]['research'][$research_type_id]['indexes'][$research_id]['research_index_id'] = $soutResearch->research_id;
                        $sout_data['soutData'][$counter]['research'][$research_type_id]['indexes'][$research_id]['status_id'] = $soutResearch->status_id;
                        $sout_data['soutData'][$counter]['research'][$research_type_id]['indexes'][$research_id]['sout_research_status'] = null;
//                        $counter_research++;
                    }
                    if (empty($sout_data['soutData'][$counter]['research'])) {
                        $sout_data['soutData'][$counter]['research'] = (object)array();
                    }
                    foreach ($sout->contingentFromSouts as $contingentFromSout) {
                        $counter_harmful_factors = 0;
                        foreach ($contingentFromSout->contingentHarmfulFactorSouts as $contingentHarmfulFactorSout) {
                            $factor_id = $contingentHarmfulFactorSout->harmfulFactors->id;
                            $factor_title = $contingentHarmfulFactorSout->harmfulFactors->title;
                            $sout_data['soutData'][$counter]['factors_of_contingent'][$counter_harmful_factors]['factor_id'] = $factor_id;
                            $sout_data['soutData'][$counter]['factors_of_contingent'][$counter_harmful_factors]['factor_title'] = $factor_title;
                            $counter_harmful_factors++;
                        }
                    }
                    if (empty($sout_data['soutData'][$counter]['factors_of_contingent'])) {
                        $sout_data['soutData'][$counter]['factors_of_contingent'] = array();
                    }
                    $counter++;
                }
            }
            $statistic_research = self::GetStatisticResearch($company_departments, $date_end, $date_start);
            if ($statistic_research['status'] == 1) {
                $warnings[] = $statistic_research['warnings'];
                $sout_data['statistic_research'] = $statistic_research['Items'];
            } else {
                $errors[] = $statistic_research['errors'];
                $warnings[] = $statistic_research['warnings'];
                throw new Exception('GetSout. Ошибка при получении статистики по инструментальным исследованиям');
            }
            $statistic_sout = self::GetStatisticSout($company_departments, $date_end, $date_start);
            if ($statistic_sout['status'] == 1) {
                $warnings[] = $statistic_sout['warnings'];
                $sout_data['statistic_sout'] = $statistic_sout['Items'];
            } else {
                $errors[] = $statistic_sout['errors'];
                $warnings[] = $statistic_sout['warnings'];
                throw new Exception('GetSout. Ошибка при получении статистики по СОУТ/ПК');
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetSout. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetSout. Конец метода';
        $result = $sout_data;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SaveSout() - Метод сохранения СОУТ/ПК для таблицы
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Sout&method=SaveSout&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 09.12.2019 13:42
     */
    public static function SaveSout($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $contingent_sout_id = null;
        $method_name = 'SaveSout';
//        $data_post = '{"sout_id":null,"soutNumber":"8df7-77","type_id":1,"type":"Спецальная оценка условий труда","company_department_id":20028766,"company_title":"УКТ","place_id":6181,"place_title":"Порож. уг. ветвь ск. ств. 3 гор.","place_type_id":1,"place_type_title":"Поверхность","role_title":"ГМ","role_id":181,"soutWorkers":120,"companyExpert_id":10,"companyExpert":"Новая","class":"3.1","date":"2019-12-10","date_format":"10.12.2019","files":[],"research":{"1":{"research_type_id":1,"research_type_title":"Инструментальные исследования физических факторов","indexes":{"4":{"sout_research_id":22,"research_index_id":4,"status_id":95,"sout_research_status":null},"1":{"sout_research_id":23,"research_index_id":1,"status_id":95,"sout_research_status":null},"2":{"sout_research_id":24,"research_index_id":2,"status_id":96,"sout_research_status":null},"3":{"sout_research_id":25,"research_index_id":3,"status_id":96,"sout_research_status":null}}}}}';
        $warnings[] = 'SaveSout. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveSout. Не переданы входные параметры');
            }
            $warnings[] = 'SaveSout. Данные успешно переданы';
            $warnings[] = 'SaveSout. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $session = Yii::$app->session;
            $warnings[] = 'SaveSout. Декодировал входные параметры';
            if (!property_exists($post_dec, 'soutNumber') ||
                !property_exists($post_dec, 'sout_id') ||
                !property_exists($post_dec, 'type_id') ||
                !property_exists($post_dec, 'year') ||
                !property_exists($post_dec, 'month') ||
                !property_exists($post_dec, 'place_id') ||
                !property_exists($post_dec, 'place_type_id') ||
                !property_exists($post_dec, 'class') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'role_id') ||
                !property_exists($post_dec, 'soutWorkers') ||
                !property_exists($post_dec, 'companyExpert_id') ||
                !property_exists($post_dec, 'date') ||
                !property_exists($post_dec, 'files') ||
                !property_exists($post_dec, 'research')
            )                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('SaveSout. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveSout. Данные с фронта получены';

            $year = $post_dec->year;
            $month = $post_dec->month;
            $date_start = $year . '-' . $month . '-01';
            $cal_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $date_end = $year . '-' . $month . '-' . $cal_day;

            $soutNumber = $post_dec->soutNumber;
            $sout_id = $post_dec->sout_id;
            $type_id = $post_dec->type_id;
            $place_id = $post_dec->place_id;
            $place_type_id = $post_dec->place_type_id;
            $company_department_id = $post_dec->company_department_id;
            $role_id = $post_dec->role_id;
            $soutWorkers = $post_dec->soutWorkers;
            $companyExpert_id = $post_dec->companyExpert_id;
            $date = $post_dec->date;
            $class = $post_dec->class;
            $files = $post_dec->files;
            $research = $post_dec->research;
            if (property_exists($post_dec, 'factors_of_contingent')) {
                $factors_of_contingent = $post_dec->factors_of_contingent;
            }
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception('GetSout. Ошибка получения вложенных департаментов' . $company_department_id);
            }

            $sout_data = Sout::findOne(['id' => $sout_id]);
            if (empty($sout_data)) {
                $sout_data = new Sout();
            }
            $sout_data->sout_type_id = $type_id;
            $sout_data->place_id = $place_id;
            $sout_data->place_type_id = $place_type_id;
            $sout_data->company_department_id = $company_department_id;
            $sout_data->date = date('Y-m-d', strtotime($date));
            $sout_data->number = $soutNumber;
            $sout_data->company_expert_id = $companyExpert_id;
            $sout_data->role_id = $role_id;
            $sout_data->count_worker = $soutWorkers;
            $sout_data->class = (string)$class;
            if ($sout_data->save()) {
                $warnings[] = 'SaveSout. Данные СОУТ/ПК успешно сохранены';
                $sout_data->refresh();
                $sout_id = $sout_data->id;
                $post_dec->sout_id = $sout_id;
            } else {
                $errors[] = $sout_data->errors;
                throw new Exception('SaveSout. Ошибка при сохранении СОУТ/ПК');
            }
            if (!empty($files)) {
                foreach ($files as $key => $file) {
                    if ($file->attachment_status == 'new') {
//                        $attachment_id = $file->attachment_id;
                        $normalize_path = Assistant::UploadFile($file->attachment_path, $file->attachment_title, 'attachment', $file->attachment_type);
                        $add_attachment = new Attachment();
                        $add_attachment->title = $file->attachment_title;
                        $add_attachment->attachment_type = $file->attachment_type;
                        $add_attachment->path = $normalize_path;
                        $add_attachment->date = BackendAssistant::GetDateFormatYMD();
                        $add_attachment->worker_id = $session['worker_id'];
                        $add_attachment->section_title = 'ОТ и ПБ/Спецальная оценка условий труда и производственный контроль';
                        if ($add_attachment->save()) {
                            $warnings[] = 'SaveIndustrialSafetyExpertise. Вложение успешно сохранено';
                            $add_attachment->refresh();
                            $attachment_id = $add_attachment->id;
                            $post_dec->{"files"}[$key]->attachment_path = $normalize_path;
                            $post_dec->{"files"}[$key]->attachment_id = $attachment_id;
                            $file->attachment_status = "";
                        } else {
                            $errors[] = $add_attachment->errors;
                            throw new Exception('SaveSout. Ошибка при сохранени вложения');
                        }
                        $add_sout_attachment = new SoutAttachment();
                        $add_sout_attachment->sout_id = $sout_id;
                        $add_sout_attachment->attachment_id = $attachment_id;
                        if ($add_sout_attachment->save()) {
                            $warnings[] = 'SaveSout. Связка вложения и СОУТ/ПК успешно сохранена';
                            $add_sout_attachment->refresh();
                            //                        $post_dec->{"files"}->{$key}->sout_attachment_id = $add_sout_attachment->id;
                        } else {
                            $errors[] = $add_sout_attachment->errors;
                            throw new Exception('SaveSout. Ошибка при сохранении связки вложения и СОУТ/ПК');
                        }
                    } elseif ($file->attachment_status == 'del') {
                        SoutAttachment::deleteAll(['id' => $file->sout_attachment_id]);
                    }
                }
            }
            SoutResearch::deleteAll(['sout_id' => $sout_id]);
            if (!empty($research)) {
                foreach ($research as $key_type => $research_type_item) {
                    foreach ($research_type_item->indexes as $key_research => $research_item) {
                        $add_sout_research = new SoutResearch();
                        $add_sout_research->sout_id = $sout_id;
                        $add_sout_research->research_id = $research_item->research_index_id;
                        $add_sout_research->status_id = $research_item->status_id;
                        if ($add_sout_research->save()) {
                            $warnings[] = 'SaveSout. Показатели для СОУТ/ПК успешно сохранены';
                            $add_sout_research->refresh();
                            $post_dec->{"research"}->{$key_type}->{"indexes"}->{$key_research}->sout_research_id = $add_sout_research->id;
                        } else {
                            $errors[] = $add_sout_research->errors;
                            throw new Exception('SaveSout. Ошибка при сохранении показателей СОУТ/ПК');
                        }
                    }
                }
            }
            if (isset($factors_of_contingent) && !empty($factors_of_contingent)) {
                $contingent_sout = ContingentFromSout::findOne(['company_department_id' => $company_department_id, 'role_id' => $role_id]);
                if (empty($contingent_sout)) {
                    $contingent_sout = new ContingentFromSout();
                    $contingent_sout->company_department_id = $company_department_id;
                    $contingent_sout->role_id = $role_id;
                }
                $contingent_sout->sout_id = $sout_id;
                if (!$contingent_sout->save()) {
                    $errors[] = $contingent_sout->errors;
                    throw new Exception($method_name . '. Ошибка при сохранении контингента СОУТа');
                }
                $contingent_sout->refresh();
                $contingent_sout_id = $contingent_sout->id;
                unset($contingent_sout);
                /**
                 * Перебор факторов для формирования массива на добавление
                 */
                ContingentHarmfulFactorSout::deleteAll(['contingent_from_sout_id' => $contingent_sout_id]);//TODO 06.03.2020 rudov: переместить выше когда будут передаваться факторы

                foreach ($factors_of_contingent as $factor) {
                    $contingent_factors_batch_array[] = [$contingent_sout_id, $factor->factor_id];
                }

            }

            $post_dec->{"statistic"} = (object)array();
            $statistic_research = self::GetStatisticResearch($company_departments, $date_end, $date_start);
            if ($statistic_research['status'] == 1) {
                $warnings[] = $statistic_research['warnings'];
//                $post_dec->{"statistic"}->{'statistic_research'} = (object)array();
                $post_dec->{"statistic"}->{'statistic_research'} = $statistic_research['Items'];
            } else {
                $errors[] = $statistic_research['errors'];
                $warnings[] = $statistic_research['warnings'];
                throw new Exception('SaveSout. Ошибка при получении статистики по инструментальным исследованиям');
            }
            $year = date("Y", strtotime($date));                                                                 // год за который строится статистика
            $month = date("m", strtotime($date));                                                                // месяц за который строится статистика

            $count_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);                                       // количество дней в месяце
            $date_end = date('Y-m-d', strtotime($year . '-' . $month . '-' . $count_day));                  // период за месяц до конца месяца
            $date_start = date('Y-m-d', strtotime($year . '-' . $month . '-01'));                           // период за месяц до конца месяца

            $statistic_sout = self::GetStatisticSout($company_departments, $date_start, $date_end);
            if ($statistic_sout['status'] == 1) {
                $warnings[] = $statistic_sout['warnings'];
                $post_dec->{"statistic"}->{'statistic_sout'} = $statistic_sout['Items'];
            } else {
                $errors[] = $statistic_sout['errors'];
                $warnings[] = $statistic_sout['warnings'];
                throw new Exception('SaveSout. Ошибка при получении статистики по СОУТ/ПК');
            }
            if (isset($contingent_factors_batch_array) && !empty($contingent_factors_batch_array)) {
                $inserted_factors_of_contingent = Yii::$app->db->createCommand()
                    ->batchInsert('contingent_harmful_factor_sout', [
                        'contingent_from_sout_id',
                        'harmful_factors_id'], $contingent_factors_batch_array)
                    ->execute();
                if ($inserted_factors_of_contingent == 0) {
                    throw new Exception($method_name . '. Ошибка при сохранении факторов контингенту СОУТа');
                }
            }
            $result = $post_dec;
        } catch (Throwable $exception) {
            $errors[] = 'SaveSout. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors[] = $exception->getFile();
            $status *= 0;
        }
        $warnings[] = 'SaveSout. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeleteSout() - Метод удаления СОУТ/ПК
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Sout&method=DeleteSout&subscribe=&data={"sout_id":5}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 09.12.2019 15:26
     */
    public static function DeleteSout($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // Промежуточный результирующий массив

        $warnings[] = 'DeleteSout. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('DeleteSout. Не переданы входные параметры');
            }
            $warnings[] = 'DeleteSout. Данные успешно переданы';
            $warnings[] = 'DeleteSout. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'DeleteSout. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'sout_id') ||
                !property_exists($post_dec, 'year') ||
                !property_exists($post_dec, 'month')
            )                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('DeleteSout. Переданы некорректные входные параметры');
            }
            $warnings[] = 'DeleteSout. Данные с фронта получены';
            $sout_id = $post_dec->sout_id;

            $year = $post_dec->year;
            $month = $post_dec->month;
            $date_start = $year . '-' . $month . '-01';
            $cal_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $date_end = $year . '-' . $month . '-' . $cal_day;

            $sout = Sout::findOne(['id' => $sout_id]);
            if (!empty($sout)) {
                $company_department_id = $sout->company_department_id;
                $response = DepartmentController::FindDepartment($company_department_id);
                if ($response['status'] == 1) {
                    $company_departments = $response['Items'];
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                } else {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new Exception('DeleteSout. Ошибка получения вложенных департаментов' . $company_department_id);
                }
                if ($sout->delete()) {
                    $warnings[] = 'DeleteSout. Удаление записи СОУТ прошло успешно';
                } else {
                    $errors[] = $sout->errors;
                    throw new Exception('DeleteSout. Ошибка при удалении записи СОУТ');
                }
                $statistic_research = self::GetStatisticResearch($company_departments, $date_end, $date_start);
                if ($statistic_research['status'] == 1) {
                    $warnings[] = $statistic_research['warnings'];
                    $result['statistic_research'] = $statistic_research['Items'];
                } else {
                    $errors[] = $statistic_research['errors'];
                    $warnings[] = $statistic_research['warnings'];
                    throw new Exception('DeleteSout. Ошибка при получении статистики по инструментальным исследованиям');
                }

                $year = date("Y", strtotime(Assistant::GetDateTimeNow()));                                                                                    // год за который строится статистика
                $month = date("m", strtotime(Assistant::GetDateTimeNow()));                                                                                  // месяц за который строится статистика
                $count_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);                                  // количество дней в месяце
                $date_end = date('Y-m-d', strtotime($year . '-' . $month . '-' . $count_day));            // конец месяца
                $date_start = date('Y-m-d', strtotime($year . '-' . $month . '-01'));                         // начало месяца

                $statistic_sout = self::GetStatisticSout($company_departments, $date_start, $date_end);
                if ($statistic_sout['status'] == 1) {
                    $warnings[] = $statistic_sout['warnings'];
                    $result['statistic_sout'] = $statistic_sout['Items'];
                } else {
                    $errors[] = $statistic_sout['errors'];
                    $warnings[] = $statistic_sout['warnings'];
                    throw new Exception('DeleteSout. Ошибка при получении статистики по СОУТ/ПК');
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'DeleteSout. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'DeleteSout. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    public static function GetStatisticResearch($company_departments, $date_end, $date_start)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $current_sout_id = null;
        $warnings[] = 'GetStatisticResearch. Начало метода';
        try {

            $souts = Sout::find()
                ->joinWith('soutResearches.research.researchType')
                ->where(['in', 'sout.company_department_id', $company_departments])
                ->andWhere(['between', 'sout.date', $date_start, $date_end])
                ->all();
            foreach ($souts as $item) {
                foreach ($item->soutResearches as $soutResearch) {
                    $type_id = $soutResearch->research->research_type_id;
                    $index_title = $soutResearch->research->title;
//                    $index_id = $soutResearch->research->id;
                    $result[$type_id]['research_type_id'] = $type_id;
                    $result[$type_id]['research_type_title'] = $soutResearch->research->researchType->title;
                    $result[$type_id]['indexes'][$index_title]['index_title'] = $index_title;
                    $result[$type_id]['indexes'][$index_title]['index_id'] = $soutResearch->research->id;
                    if (isset($result[$type_id]['indexes'][$index_title]['count'])) {
                        $result[$type_id]['indexes'][$index_title]['count'] += (int)$item->count_worker;
                    } else {
                        $result[$type_id]['indexes'][$index_title]['count'] = (int)$item->count_worker;
                    }
                    if ($soutResearch->status_id == 96) {
                        if (isset($result[$type_id]['indexes'][$index_title]['count_bad_research'])) {
                            $result[$type_id]['indexes'][$index_title]['count_bad_research'] += (int)$item->count_worker;
                        } else {
                            $result[$type_id]['indexes'][$index_title]['count_bad_research'] = (int)$item->count_worker;
                        }
                    }
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetStatisticResearch. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetStatisticResearch. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    public static function GetStatisticSout($company_departments, $date_end, $date_start)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $date_now = date('Y-m-d', strtotime(BackendAssistant::GetDateNow()));
        $warnings[] = 'GetStatisticSout. Начало метода';
        try {
            $souts = Sout::find()
                ->select(['cst.title', 'sum(sout.count_worker) as count', 'cst.id as cst_id'])
                ->innerJoin('checking_sout_type cst', 'sout.sout_type_id = cst.id')
                ->groupBy('title,cst_id')
                ->where(['in', 'sout.company_department_id', $company_departments])
                ->andWhere(['between', 'sout.date', $date_start, $date_end])
                ->asArray()
                ->all();
            $result['statistic_by_type'] = array();
            if (!empty($souts)) {
                foreach ($souts as $sout) {
                    $result['statistic_by_type'][$sout['cst_id']]['sout_type_id'] = $sout['cst_id'];
                    $result['statistic_by_type'][$sout['cst_id']]['sout_type_title'] = $sout['title'];
                    $result['statistic_by_type'][$sout['cst_id']]['count'] = $sout['count'];
                }
            }
//            else{
//                $result['statistic_by_type'][1]['sout_type_id'] = 1;
//                $result['statistic_by_type'][1]['sout_type_title'] = 'Спецальная оценка условий труда';
//                $result['statistic_by_type'][1]['count'] = 0;
//                $result['statistic_by_type'][2]['sout_type_id'] = 2;
//                $result['statistic_by_type'][2]['sout_type_title'] = 'Производственный контроль';
//                $result['statistic_by_type'][2]['count'] = 0;
//            }
            $found_worker = (new Query())
                ->select('count(worker.id) as count_worker, employee.gender')
                ->from('worker')
                ->innerJoin('employee', 'employee.id=worker.employee_id')
                ->where(['in', 'worker.company_department_id', $company_departments])
                ->andWhere(['<=', 'worker.date_start', $date_now])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date_now],
                    ['is', 'worker.date_end', null]
                ])
                ->groupBy('employee.gender')
                ->indexBy('gender')
                ->all();
            $result['count_worker_woman'] = 0;                                                                      // количество работников мужчин в подразделении
            $result['count_worker_man'] = 0;                                                                        // количество работников женщин в подразделении
            if (isset($found_worker['М'])) {
                $result['count_worker_man'] = (int)$found_worker['М']['count_worker'];
            }
            if (isset($found_worker['Ж'])) {
                $result['count_worker_woman'] = (int)$found_worker['Ж']['count_worker'];
            }
            $result['count_worker'] = $result['count_worker_woman'] + $result['count_worker_man'];        // всего работников в подразделении
            unset($found_worker);

            $planned_sout = PlannedSout::find()
                ->select(['planned_sout.date_start'])
                ->where(['in', 'planned_sout.company_department_id', $company_departments])
                ->andWhere(['planned_sout.sout_type_id' => self::SOUT_TYPE_SOUT])
                ->orderBy('planned_sout.date_start ASC')
                ->limit(1)
                ->scalar();
            $planned_pk = PlannedSout::find()
                ->select(['planned_sout.date_start'])
                ->where(['in', 'planned_sout.company_department_id', $company_departments])
                ->andWhere(['planned_sout.sout_type_id' => self::SOUT_TYPE_PK])
                ->orderBy('planned_sout.date_start ASC')
                ->limit(1)
                ->scalar();
            if ($planned_sout == false) {
                $planned_sout_date = null;
            } else {
                $planned_sout_date = date('d.m.Y', strtotime($planned_sout));
            }
            if ($planned_pk == false) {
                $planned_pk_date = null;
            } else {
                $planned_pk_date = date('d.m.Y', strtotime($planned_pk));
            }

            $result['planned_sout_date'] = $planned_sout_date;
            $result['planned_pk_date'] = $planned_pk_date;
        } catch (Throwable $exception) {
            $errors[] = 'GetStatisticSout. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetStatisticSout. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetPlannedSout() - Метод получения планового СОУТ/ПК
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Sout&method=GetPlannedSout&subscribe=&data={"year":2019}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 10.12.2019 14:22
     */
    public static function GetPlannedSout($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $planned_sout = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'GetPlannedSout. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetPlannedSout. Не переданы входные параметры');
            }
            $warnings[] = 'GetPlannedSout. Данные успешно переданы';
            $warnings[] = 'GetPlannedSout. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetPlannedSout. Декодировал входные параметры';
            if (!property_exists($post_dec, 'year'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetPlannedSout. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetPlannedSout. Данные с фронта получены';
            $year = $post_dec->year;
            $date_start = $year . '-01-01';
            $date_end = $year . '-12-31';
            $planned_data = PlannedSout::find()
                ->joinWith('soutType')
                ->joinWith('companyDepartment.company')
                ->joinWith('plannedSoutKind')
                ->joinWith('plannedSoutCompanyExperts.companyExpert')
                ->joinWith('plannedSoutWorkingPlaces.workingPlace')
                ->where(['between', 'planned_sout.date_start', $date_start, $date_end])
                ->all();
            if (!empty($planned_data)) {
                $planned_sout_counter = 0;
                foreach ($planned_data as $plan_item) {
                    $plan_sout_id = $plan_item->id;
                    $planned_sout_object = array();
                    $planned_sout_object['id'] = $plan_sout_id;
                    $planned_sout_object['kind_id'] = $plan_item->planned_sout_kind_id;
                    $planned_sout_object['kind_title'] = $plan_item->plannedSoutKind->title;
                    $planned_sout_object['company_department_id'] = $plan_item->company_department_id;
                    $planned_sout_object['company_department_title'] = $plan_item->companyDepartment->company->title;
                    $planned_sout_object['date_start'] = date('d.m.Y', strtotime($plan_item->date_start));
                    $planned_sout_object['date_end'] = date('d.m.Y', strtotime($plan_item->date_end));
                    $planned_sout_object['day_start'] = $plan_item->day_start;
                    $planned_sout_object['day_end'] = $plan_item->day_end;
                    $planned_sout_object['checking_type_id'] = $plan_item->sout_type_id;
                    $planned_sout_object['checking_type_title'] = $plan_item->soutType->title;
                    $planned_sout_object['workers_place_count'] = $plan_item->workers_place_count;
                    $planned_sout_object['selected_workers_place'] = $plan_item->selected_workers;
                    $planned_sout_object['company_experts'] = array();

                    foreach ($plan_item->plannedSoutCompanyExperts as $plannedSoutCompanyExpert) {
                        $planned_company_expert_id = $plannedSoutCompanyExpert->company_expert_id;
                        $planned_sout_object['company_experts'][$planned_company_expert_id]['company_expert_id'] = $planned_company_expert_id;
                        $planned_sout_object['company_experts'][$planned_company_expert_id]['company_expert_title'] = $plannedSoutCompanyExpert->companyExpert->title;
                    }
                    if (empty($planned_sout_object['company_experts'])) {
                        $planned_sout_object['company_experts'] = array();
                    }
                    $counter = 0;
                    $planned_sout_object['working_place'] = array();
                    foreach ($plan_item->plannedSoutWorkingPlaces as $plannedSoutWorkingPlace) {
                        $place_id = $plannedSoutWorkingPlace->workingPlace->place_id;
                        $planned_sout_object['working_place'][$place_id]['place_id'] = $place_id;
                        $planned_sout_object['working_place'][$place_id]['place_title'] = $plannedSoutWorkingPlace->workingPlace->place->title;
                        $planned_sout_object['working_place'][$place_id]['type_place_id'] = $plannedSoutWorkingPlace->workingPlace->place_type_id;
                        $planned_sout_object['working_place'][$place_id]['type_place_title'] = $plannedSoutWorkingPlace->workingPlace->placeType->title;
                        $planned_sout_object['working_place'][$place_id]['profession'][$counter]['profession_title'] = $plannedSoutWorkingPlace->workingPlace->role->title;
                        $planned_sout_object['working_place'][$place_id]['profession'][$counter]['profession_id'] = $plannedSoutWorkingPlace->workingPlace->role_id;
                        $planned_sout_object['working_place'][$place_id]['profession'][$counter]['profession_count'] = $plannedSoutWorkingPlace->count_worker;
                        $counter++;
                    }
                    if (empty($planned_sout_object['working_place'])) {
                        $planned_sout_object['working_place'] = array();
                    }
                    $planned_sout[$planned_sout_counter] = $planned_sout_object;
                    $planned_sout_counter++;
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetPlannedSout. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetPlannedSout. Конец метода';
        return array('Items' => $planned_sout, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SavePlannedSout() - Метод сохранения планового СОУТ/ПК
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Sout&method=SavePlannedSout&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 10.12.2019 19:02
     */
    public static function SavePlannedSout($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $planned_sout_id = null;
        $post_dec = array();
//        $data_post = '{"id":null,"kind_id":1,"kind_title":"Запланирован(-а) СОУТ/ПК","company_department_id":20028766,"date_start":"2019-12-10","date_start_format":"10.12.2019","date_end":"2019-12-20","date_end_format":"20.12.2019","day_start":343,"day_end":353,"checking_type_id":1,"checking_type_title":"Спецальная оценка условий труда","workers_place_count":100,"selected_workers_place":95,"company_experts":{"4":{"company_expert_id":4,"company_expert_title":"Ростелеком"},"10":{"company_expert_id":10,"company_expert_title":"Новая"}},"working_place":{"6181":{"place_id":6181,"place_title":"Порож. уг. ветвь ск. ств. 3 гор.","type_place_id":1,"type_place_title":"Поверхность","profession":[{"profession_title":"Прочее","profession_id":9,"profession_count":50},{"profession_title":"Проходчик","profession_id":185,"profession_count":45}]}}}';
        $warnings[] = 'SavePlannedSout. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SavePlannedSout. Не переданы входные параметры');
            }
            $warnings[] = 'SavePlannedSout. Данные успешно переданы';
            $warnings[] = 'SavePlannedSout. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'SavePlannedSout. Декодировал входные параметры';
            if (!property_exists($post_dec, 'id') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'date_start') ||
                !property_exists($post_dec, 'date_end') ||
                !property_exists($post_dec, 'day_start') ||
                !property_exists($post_dec, 'day_end') ||
                !property_exists($post_dec, 'checking_type_id') ||
                !property_exists($post_dec, 'kind_id') ||
                !property_exists($post_dec, 'workers_place_count') ||
                !property_exists($post_dec, 'selected_workers_place') ||
                !property_exists($post_dec, 'company_experts') ||
                !property_exists($post_dec, 'working_place')
            )                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('SavePlannedSout. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SavePlannedSout. Данные с фронта получены';
            $id = $post_dec->id;
            $company_department_id = $post_dec->company_department_id;
            $date_start = date('Y-m-d', strtotime($post_dec->date_start));
            $date_end = date('Y-m-d', strtotime($post_dec->date_end));
            $day_start = $post_dec->day_start;
            $day_end = $post_dec->day_end;
            $checking_type_id = $post_dec->checking_type_id;
            $planned_sout_kind_id = $post_dec->kind_id;
            $workers_place_count = $post_dec->workers_place_count;
            $selected_workers_place = $post_dec->selected_workers_place;
            $company_experts = $post_dec->company_experts;
            $working_place = $post_dec->working_place;
            $planned_sout = PlannedSout::findOne(['id' => $id]);
            if (empty($planned_sout)) {
                $planned_sout = new PlannedSout();
            }
            $planned_sout->date_start = $date_start;
            $planned_sout->date_end = $date_end;
            $planned_sout->day_start = $day_start;
            $planned_sout->day_end = $day_end;
            $planned_sout->sout_type_id = $checking_type_id;
            $planned_sout->company_department_id = $company_department_id;
            $planned_sout->planned_sout_kind_id = $planned_sout_kind_id;
            $planned_sout->selected_workers = $selected_workers_place;
            $planned_sout->workers_place_count = $workers_place_count;
            if ($planned_sout->save()) {
                $warnings[] = 'SavePlannedSout. Плановый график успешно сохранён';
                $planned_sout->refresh();
                $planned_sout_id = $planned_sout->id;
                $post_dec->id = $planned_sout_id;
            } else {
                $errors[] = $planned_sout->errors;
                throw new Exception('SavePlannedSout. Ошибка при сохранении планового графика прохождения СОУТ/ПК');
            }
            foreach ($company_experts as $company_expert) {
                $inserted_planned_comp_experts[] = [$planned_sout_id, $company_expert->company_expert_id];
            }
            PlannedSoutCompanyExpert::deleteAll(['planned_sout_id' => $planned_sout_id]);
            if (isset($inserted_planned_comp_experts) && !empty($inserted_planned_comp_experts)) {
                $result_inserted_planned_sout_comp_expert = Yii::$app->db
                    ->createCommand()
                    ->batchInsert('planned_sout_company_expert',
                        [
                            'planned_sout_id',
                            'company_expert_id'
                        ], $inserted_planned_comp_experts)
                    ->execute();
                if ($result_inserted_planned_sout_comp_expert != 0) {
                    $warnings[] = 'SavePlannedSout. Свзяка планового СОУТ/ПК и компаний эксперта успешно сохранена';
                } else {
                    throw new Exception('SavePlannedSout. Ошибка при сохранении плановго СОУТ/ПК и компаний эксперта');
                }
            }
            PlannedSoutWorkingPlace::deleteAll(['planned_sout_id' => $planned_sout_id]);
//            Assistant::PrintR($working_place);
//            die;
            foreach ($working_place as $w_o_item) {

                foreach ($w_o_item->profession as $proffesion) {

                    $working_place = WorkingPlace::findOne(['company_department_id' => $company_department_id, 'place_id' => $w_o_item->place_id, 'place_type_id' => $w_o_item->type_place_id, 'role_id' => $proffesion->profession_id]);
                    if (empty($working_place)) {
                        $new_working_place = new WorkingPlace();
                        $new_working_place->company_department_id = $company_department_id;
                        $new_working_place->place_id = $w_o_item->place_id;
                        $new_working_place->place_type_id = $w_o_item->type_place_id;
                        $new_working_place->role_id = $proffesion->profession_id;
                        if ($new_working_place->save()) {
                            $warnings[] = 'SavePlannedSout. Раочее место успешно сохранено';
                            $new_working_place->refresh();
                            $working_place_id = $new_working_place->id;
                        } else {
                            $errors[] = $new_working_place->errors;
                            throw new Exception('SavePlannedSout. Ошибка при сохранении рабочего места');
                        }
                    } else {
                        $working_place_id = $working_place->id;
                    }
                    $inserted_planned_working_place[] = [$planned_sout_id, $working_place_id, $proffesion->profession_count];
                }
            }
            if (isset($inserted_planned_working_place) && !empty($inserted_planned_working_place)) {
                $result_inserted_planned_working_place = Yii::$app->db
                    ->createCommand()
                    ->batchInsert('planned_sout_working_place', [
                        'planned_sout_id',
                        'working_place_id',
                        'count_worker'
                    ], $inserted_planned_working_place)
                    ->execute();
                if ($result_inserted_planned_working_place != 0) {
                    $warnings[] = 'SavePlannedSout. Свзяка планового СОУТ/ПК и рабочего места успешно сохранена';
                } else {
                    throw new Exception('SavePlannedSout. Ошибка при сохранении плановго СОУТ/ПК и рабочего места');
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'SavePlannedSout. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SavePlannedSout. Конец метода';
        return array('Items' => $post_dec, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeletePlannedSout() - Метод удаления планового СОУТ/ПК
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Sout&method=DeletePlannedSout&subscribe=&data={"id":18}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 10.12.2019 19:27
     */
    public static function DeletePlannedSout($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'DeletePlannedSout. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('DeletePlannedSout. Не переданы входные параметры');
            }
            $warnings[] = 'DeletePlannedSout. Данные успешно переданы';
            $warnings[] = 'DeletePlannedSout. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'DeletePlannedSout. Декодировал входные параметры';
            if (!property_exists($post_dec, 'id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('DeletePlannedSout. Переданы некорректные входные параметры');
            }
            $warnings[] = 'DeletePlannedSout. Данные с фронта получены';
            $planned_sout_id = $post_dec->id;
            PlannedSout::deleteAll(['id' => $planned_sout_id]);
        } catch (Throwable $exception) {
            $errors[] = 'DeletePlannedSout. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'DeletePlannedSout. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    public static function GetDepartmentListWithWorkersRoles()
    {
        $result = array();                                                                                                // промежуточный результирующий массив
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                              // массив предупреждений
        $status = 1;                                                                                                      //  состояния выполнения скрипта
        try {
            $warnings[] = "GetDepartmentListWithWorkersRoles. Проверил входные данные";

            // получаем список всех 0 департаментов/компаний
            $companies = Company::find()
                ->where('upper_company_id is null')
                ->asArray()
                ->all();
            if ($companies === false) {
                throw new Exception("GetDepartmentListWithWorkersRoles. Список компаний пуст");
            }

            // получаем список людей и их депратаментов со всей нужной служебной информации
            $all_list_departments_with_workers = (new Query())
                ->select('*')
                ->from('view_getworkerswithdepartments')
                ->all();
            if ($all_list_departments_with_workers === false) {
                throw new Exception("GetDepartmentListWithWorkersRoles. Список работников пуст");
            }
            // группируем работников в департаменты
            foreach ($all_list_departments_with_workers as $worker) {
                $worker_group_by_company_id[$worker['company_department_id']][] = $worker;
            }
            unset($worker);
            unset($all_list_departments_with_workers);

            // получаем список вложенных компаний
            $attachment_companies = Company::find()
                ->select(
                    'company.id as id,
                    company.title as title,
                    upper_company_id'
                )
                ->where('upper_company_id is not null')
                ->asArray()
                ->all();
            if ($attachment_companies === false) {
                $warnings[] = "GetDepartmentListWithWorkersRoles. Список вложенных компаний пуст";
            }
            // группируем работников в департаменты
            foreach ($attachment_companies as $attachment_company) {
                $company_by_upper_company_id[$attachment_company['upper_company_id']][] = $attachment_company;
            }
            unset($attachment_company);
            unset($attachment_companies);

            foreach ($companies as $company) {
                $list_companys[] = self::getCompanyAttachment($company, $worker_group_by_company_id, $company_by_upper_company_id);
            }
            $result['id'] = 1;
            $result['title'] = "Список работников";
            $result['children'] = $list_companys;
            $result['is_chosen'] = 0;

        } catch (Throwable $exception) {
            $errors[] = "GetDepartmentListWithWorkersRoles. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "GetDepartmentListWithWorkersRoles. Вышел с метода";
        return array(
            'Items' => $result,
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings
        );
    }

    /**
     * Метод GetDepartmentListWithWorkingWorkersRoles() - Получение участков с работающими людьми по профессиям
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      "year": 2020
     * }
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *     "Items": {
     *         "id": 1,
     *         "title": "Список работников",
     *         "children": [{
     *                 "id": "4029720",                                                                                 - идентификатор учатка
     *                 "title": "АО \"Воркутауголь\"",                                                                  - наименование участка
     *                 "parent": null,                                                                                  - есть ли родитель
     *                 "count_worker": 76,                                                                              - количество работников
     *                 "profession": {                                                                                  - количество людей на роли
     *                     "9": {                                                                                       - идентификатор роли
     *                         "role_id": "9",                                                                          - идентификатор роли
     *                         "count": 1                                                                               - количество людей на данной роли
     *                     }
     *                 }
     *                 ],
     *                 "children": [{}]                                                                                 - низлежащие участки
     *             }
     *         ]
     *     }
     * }
     *
     * АЛГОРИТМ:
     * 1.    Начало метода
     * 2.    Сформировать дату на конец переданного года
     * 3.    Получаем писок всех компаний у которых нет родителя
     * 4.    Получаем список людей и их депратаментов со всей нужной служебной информации
     * 5.    Группируем работников в департаменты
     * 6.    Получаем список всех компаний у которых есть родитель (вложенных)
     * 7.    Группируем работников в департаменты
     * 8.    Вызываем рекурсивный метод записи вложенных компаний с работниками
     * 9.    Конец метода
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Sout&method=GetDepartmentListWithWorkingWorkersRoles&subscribe=&data={"year":2020}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 01.04.2020 17:29
     */
    public static function GetDepartmentListWithWorkingWorkersRoles($data_post = NULL)
    {
        $result = array();                                                                                                // промежуточный результирующий массив
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                              // массив предупреждений
        $status = 1;                                                                                                      //  состояния выполнения скрипта
        $method_name = 'GetDepartmentListWithWorkingWorkersRoles';
        $microtime_start = 0;
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'year'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . ". Проверил входные данные";
            $year = $post_dec->year;

            // получаем список всех 0 департаментов/компаний
            $companies = Company::find()
                ->where('upper_company_id is null')
                ->asArray()
                ->all();
            if ($companies === false) {
                throw new Exception("GetDepartmentListWithWorkingWorkersRoles. Список компаний пуст");
            }

            // получаем список людей и их депратаментов со всей нужной служебной информации
            $all_list_departments_with_workers = Worker::find()
                ->select(['worker.id as worker_id',
                    'worker_object.role_id',
                    'worker.date_end',
                    'worker.tabel_number as stuff_number',
                    'position.id as position_id',
                    'position.title as position_title',
                    'position.qualification as qualification',
                    'company_department.id as company_department_id',
                    'department.title as department_title',
                    'department.id as department_id',
                    'company.id as company_id',
                    'company.title as company_title',
                    'company.upper_company_id as upper_company_id',
                ])
                ->innerJoin('worker_object', 'worker_object.worker_id = worker.id')
                ->innerJoin('employee', 'employee.id = worker.employee_id')
                ->innerJoin('position', 'position.id = worker.position_id')
                ->innerJoin('company_department', 'company_department.id = worker.company_department_id')
                ->innerJoin('company', 'company.id = company_department.company_id')
                ->innerJoin('department', 'department.id = company_department.department_id')
                ->where(['or',
                    ['>=', 'year(worker.date_end)', $year],
                    ['is', 'worker.date_end', null]
                ])
                ->asArray()
                ->all();
            if (empty($all_list_departments_with_workers)) {
                throw new Exception($method_name . '. Массив работников пуст на ' . $year . ' год');
            }
//            Assistant::PrintR(count($all_list_departments_with_workers));
//            die;
            if ($all_list_departments_with_workers === false) {
                throw new Exception("GetDepartmentListWithWorkingWorkersRoles. Список работников пуст");
            }
            // группируем работников в департаменты
            foreach ($all_list_departments_with_workers as $worker) {
                $worker_group_by_company_id[$worker['company_department_id']][] = $worker;
            }
            unset($worker);
            unset($all_list_departments_with_workers);

            // получаем список вложенных компаний
            $attachment_companies = Company::find()
                ->select(
                    'company.id as id,
                    company.title as title,
                    upper_company_id'
                )
                ->where('upper_company_id is not null')
                ->asArray()
                ->all();
            if ($attachment_companies === false) {
                $warnings[] = "GetDepartmentListWithWorkingWorkersRoles. Список вложенных компаний пуст";
            }
            // группируем работников в департаменты
            foreach ($attachment_companies as $attachment_company) {
                $company_by_upper_company_id[$attachment_company['upper_company_id']][] = $attachment_company;
            }
            unset($attachment_company);
            unset($attachment_companies);

            foreach ($companies as $company) {
                $list_companys[] = self::getCompanyWithWorkerRoles($company, $worker_group_by_company_id, $company_by_upper_company_id);
            }
            $result['id'] = 1;
            $result['title'] = "Список работников";
            $result['children'] = $list_companys;
            $result['is_chosen'] = 0;

        } catch (Throwable $exception) {
            $errors[] = "GetDepartmentListWithWorkingWorkersRoles. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "GetDepartmentListWithWorkingWorkersRoles. Вышел с метода";
        return array(
            'Items' => $result,
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings
        );
    }

    public static function getCompanyWithWorkerRoles($company, $worker_group_by_company_id, $company_by_upper_company_id)
    {
        $list_company['id'] = $company['id'];
        $list_company['title'] = $company['title'];
        $list_company['parent'] = $company['upper_company_id'];
        /**
         * блок проверки работников внутри подразделения
         */
        $count_worker_in_dep = 0;
        $with_role = array();
        if (isset($worker_group_by_company_id[$company['id']])) {
            foreach ($worker_group_by_company_id[$company['id']] as $worker) {
                $count_worker_in_dep++;
                if ($worker['role_id'] != null) {
                    $with_role[$worker['role_id']]['role_id'] = $worker['role_id'];
                    if (isset($with_role[$worker['role_id']]['count'])) {
                        $with_role[$worker['role_id']]['count']++;
                    } else {
                        $with_role[$worker['role_id']]['count'] = 1;
                    }
                }
            }
        }
        $list_company['count_worker'] = $count_worker_in_dep;
        $list_company['profession'] = $with_role;

        /**
         * блок проверки подразделений внутри подразделения
         */
        if (isset($company_by_upper_company_id[$company['id']])) {
            foreach ($company_by_upper_company_id[$company['id']] as $child_company) {
                $response = self::getCompanyWithWorkerRoles($child_company, $worker_group_by_company_id, $company_by_upper_company_id);
                $list_company['children'][] = $response;
                $list_company['count_worker'] += $response['count_worker'];

                /**
                 * передача на верхний уровень общее количество профессии по ролям
                 * к примеру (ГРП - 90)
                 *
                 * $profession
                 * role_id: 9,
                 * count: 10
                 */
                foreach ($response['profession'] as $key => $profession) {
                    if (isset($list_company['profession'][$profession['role_id']])) {
                        $list_company['profession'][$profession['role_id']]['count'] += $profession['count'];
                    } else {
                        foreach ($response['profession'] as $prof) {
                            $list_company['profession'][$prof['role_id']] = $prof;
                        }
                    }
                }
            }
        } else {
            $list_company['children'] = array();
        }

        return $list_company;
    }

    public static function getCompanyAttachment($company, $worker_group_by_company_id, $company_by_upper_company_id)
    {
        $list_company['id'] = $company['id'];
        $list_company['title'] = $company['title'];
        $list_company['parent'] = $company['upper_company_id'];
//        $list_company['is_chosen'] = 2;
        /**
         * блок проверки работников внутри подразделения
         */
        $count_worker_in_dep = 0;
        $with_role = array();
        $workers = array();
        if (isset($worker_group_by_company_id[$company['id']])) {

//            $list_company['workers_test'] = $worker_group_by_company_id[$company['id']];

            foreach ($worker_group_by_company_id[$company['id']] as $worker) {
                if (isset($worker['stuff_number'])) {
                    $workers[] = [
                        'id' => $worker['worker_id'],
                        'worker_full_name' => $worker['last_name'] . ' ' . $worker['first_name'] . ' ' . $worker['patronymic'],
                        'worker_position_id' => $worker['position_id'],
                        'position_title' => $worker['position_title'],
                        'qualification' => $worker['qualification'],
                        'stuff_number' => $worker['stuff_number'],
                        'worker_role_id' => $worker['role_id'],
                    ];
                }
//                $worker_groups_temp['id'] = $worker['worker_id'];
//                $worker_groups_temp['worker_id'] = $worker['worker_id'];
//                $worker_groups_temp['title'] = $worker['last_name'] . ' ' . $worker['first_name'] . ' ' . $worker['patronymic'];
//                $worker_groups_temp['worker_full_name'] = $worker['last_name'] . ' ' . $worker['first_name'] . ' ' . $worker['patronymic'];
//                $worker_groups_temp['worker_position_id'] = $worker['position_id'];
//                $worker_groups_temp['last_name'] = $worker['last_name'];
//                $worker_groups_temp['first_name'] = $worker['first_name'];
//                $worker_groups_temp['patronymic'] = $worker['patronymic'];
//                $worker_groups_temp['position_title'] = $worker['position_title'];
//                $worker_groups_temp['qualification'] = $worker['qualification'];
//                $worker_groups_temp['stuff_number'] = $worker['stuff_number'];
//                $worker_groups_temp['worker_role_id'] = $worker['role_id'];
//                $worker_groups_temp['is_chosen'] = 1;
//                $list_company['children'][] = $worker_groups_temp;
//                unset($worker_groups_temp);

                $count_worker_in_dep++;
                if ($worker['role_id'] != null) {
                    $with_role[$worker['role_id']]['role_id'] = $worker['role_id'];
                    if (isset($with_role[$worker['role_id']]['count'])) {
                        $with_role[$worker['role_id']]['count']++;
                    } else {
                        $with_role[$worker['role_id']]['count'] = 1;
                    }
                }
            }
        }
        $list_company['count_worker'] = $count_worker_in_dep;
        $list_company['profession'] = $with_role;
        $list_company['workers'] = $workers;

        /**
         * блок проверки подразделений внутри подразделения
         */
        if (isset($company_by_upper_company_id[$company['id']])) {
            foreach ($company_by_upper_company_id[$company['id']] as $child_company) {
                $response = self::getCompanyAttachment($child_company, $worker_group_by_company_id, $company_by_upper_company_id);
                $list_company['children'][] = $response;
                $list_company['count_worker'] += $response['count_worker'];

                /**
                 * передача на верхний уровень общее количество профессии по ролям
                 * к примеру (ГРП - 90)
                 *
                 * $profession
                 * role_id: 9,
                 * count: 10
                 */
                foreach ($response['profession'] as $key => $profession) {
                    if (isset($list_company['profession'][$profession['role_id']])) {
                        $list_company['profession'][$profession['role_id']]['count'] += $profession['count'];
                    } else {
                        foreach ($response['profession'] as $prof) {
                            $list_company['profession'][$prof['role_id']] = $prof;
                        }
                    }
                }
            }
        } else {
            $list_company['children'] = array();
        }

        return $list_company;
    }

    /**
     * Метод GetSoutByClass() - Получения профессий и количество сотрудников назначенных на неё по классам
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Sout&method=GetSoutByClass&subscribe=&data={"company_department_id":20028766,"year":2019,"month":12}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.12.2019 9:19
     */
    public static function GetSoutByClass($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'GetSoutByClass';
        $sout_by_class = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'year') ||
                !property_exists($post_dec, 'month'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $year = $post_dec->year;
            $month = $post_dec->month;
            $date_start = $year . '-' . $month . '-01';
            $cal_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $date_end = $year . '-' . $month . '-' . $cal_day;
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
            $souts = Sout::find()
                ->joinWith('role')
                ->where(['in', 'company_department_id', $company_departments])
                ->andWhere(['between', 'sout.date', $date_start, $date_end])
                ->all();
            $sout_by_class['souts']['1'] = array();
            $sout_by_class['souts']['2'] = array();
            $sout_by_class['souts']['3.1'] = array();
            $sout_by_class['souts']['3.2'] = array();
            $sout_by_class['souts']['3.3'] = array();
            $sout_by_class['souts']['3.4'] = array();
            $sout_by_class['souts']['4'] = array();
            $sout_by_class['all_workers'] = 0;
            foreach ($souts as $sout) {
                $sout_class = $sout->class;
                $warnings['classes'][] = $sout->class;
                $role_id = $sout->role_id;
                $sout_by_class['souts'][$sout_class]['sout_class'] = $sout_class;
                $sout_by_class['souts'][$sout_class]['roles'][$role_id]['role_id'] = $role_id;
                $sout_by_class['souts'][$sout_class]['roles'][$role_id]['role_title'] = $sout->role->title;
                if (isset($sout_by_class['souts'][$sout_class]['roles'][$role_id]['count'])) {
                    $sout_by_class['souts'][$sout_class]['roles'][$role_id]['count'] += $sout['count_worker'];
                } else {
                    $sout_by_class['souts'][$sout_class]['roles'][$role_id]['count'] = $sout['count_worker'];
                }
                $sout_by_class['all_workers'] += $sout['count_worker'];
            }
            if (empty($sout_by_class['souts']['4'])) {
                $sout_by_class['souts']['4'] = (object)array();
            }
            if (empty($sout_by_class['souts']['1'])) {
                $sout_by_class['souts']['1'] = (object)array();
            }
            if (empty($sout_by_class['souts']['2'])) {
                $sout_by_class['souts']['2'] = (object)array();
            }
            if (empty($sout_by_class['souts']['3.1'])) {
                $sout_by_class['souts']['3.1'] = (object)array();
            }
            if (empty($sout_by_class['souts']['3.2'])) {
                $sout_by_class['souts']['3.2'] = (object)array();
            }
            if (empty($sout_by_class['souts']['3.3'])) {
                $sout_by_class['souts']['3.3'] = (object)array();
            }
            if (empty($sout_by_class['souts']['3.4'])) {
                $sout_by_class['souts']['3.4'] = (object)array();
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $sout_by_class, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // SaveNewResearch - метод сохранения справочника параметров СОУТ
    // входные данные:
    //      research:
    //                  research_index_id           -   ключ показателя СОУТ
    //                  research_index_title        -   наименование показателя СОУТ
    //                  research_type_id            -   ключ раздела исследований СОУТ
    //
    // выходные данные:
    //      тот же объект, только с правильными айдишниками
    // Разработал: Якимов М.Н.
    // дата разработки: 07.03.2020
    // прмер использования:
    // http://127.0.0.1/read-manager-amicum?controller=PhysicalSchedule&method=SaveNewResearch&subscribe=&data={%harmfulFactor%22:1}
    public static function SaveNewResearch($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'SaveNewResearch';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = null;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
//        $session = Yii::$app->session;                                                                                  // текущая сессия

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

            // запись в БД начала выполнения скрипта
            // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//                $date_time_debug_start, $date_time_debug_end, $log_id,
//                $duration_summary, $max_memory_peak, $count_all);
//            if ($response['status'] === 1) {
//                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
//            } else {
//                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
//            }

            /** Метод начало */
            $warnings[] = 'SaveNewResearch. Начало метода';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveNewResearch. Не переданы входные параметры');
            }
            $warnings[] = 'SaveNewResearch. Данные успешно переданы';
            $warnings[] = 'SaveNewResearch. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'SaveNewResearch. Декодировал входные параметры';
            if (
            !property_exists($post_dec, 'research')
            ) {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveNewResearch. Данные с фронта получены';
            $research = $post_dec->research;                                                          // ключ заключения медицинского осмотра

            // ищем медицинское заключение
            $save_research_factor = ResearchIndex::findOne(['id' => $research->research_index_id]);

            // если его нет, то создаем (Хотел сделать поиск на уже существующую проверку МО, что бы в нее дописывать)
            if (!$save_research_factor) {
                $save_research_factor = new ResearchIndex();

            }

            $save_research_factor->title = $research->research_index_title;
            $save_research_factor->research_type_id = $research->research_type_id;
            if ($save_research_factor->save()) {
                $save_research_factor->refresh();
                $research->research_index_id = $save_research_factor->id;

                $warnings[] = 'SaveNewResearch. Данные успешно сохранены в модель ResearchIndex';
            } else {
                $errors[] = $save_research_factor->errors;
                throw new Exception('SaveNewResearch. Ошибка сохранения модели ResearchIndex');
            }
            $result = $research;
            /** Отладка */
            $description = 'Закончил основной метод метода';                                                                      // описание текущей отладочной точки
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
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                       // время окончания выполнения метода
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
}
