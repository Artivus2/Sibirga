<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers\industrial_safety;

use backend\controllers\Assistant as BackendAssistant;
use DateTime;
use frontend\controllers\Assistant;
use frontend\controllers\handbooks\DepartmentController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Attachment;
use frontend\models\FireFightingEquipment;
use frontend\models\FireFightingEquipmentDocuments;
use frontend\models\FireFightingEquipmentSpecific;
use frontend\models\FireFightingEquipmentSpecificStatus;
use frontend\models\FireFightingObject;
use Throwable;
use Yii;
use yii\console\Exception;
use yii\web\Controller;

class FireFightingEquipmentController extends Controller
{

    #region Навигация по контроллеру
    //  GetFireFightEquipmentSpecific             - Метод получения данных для блока "Спецификация средств пожарной базопасности"
    //  SaveFireFightSpecificObject               - Метод сохранения из модального окна "Добавить объект" средства пожарной безопасности
    //  SaveFireFightingEquipmentSpecific         - Метод сохранения средства пожарной безопасности
    //  GetFireFightingByObjects                  - Метод получения данных для выпадающего списка в модальном окне "Добавить средство пожарной безопасности"
    //  HandbookFireFightingEquipments            - Справочник средств пожарной безопасности
    //  GraficReplacementFireFightingEquipment    - Метод возврата графика заменты ТО
    //  WriteOffFireFightingEquipment             - Списание средства пожарной безопасности
    //  GetFireFightingStatistic                  - Метод предназначенный для блока статистики в "Контроль наличия средств пожарной безопасности"
    //  GetFireFightingBySpecific                 - Метод получения данных для выпадающего списка в модальном окне "Техническое обслуживание"
    //  MaintenanceFireFightingEquipment          - Техническое обслуживание средства пожарной безопасности
    //  DeleteFireFightingSpecific                - Метод удаления конкретного средства пожарной безопасности
    //  DeleteFireFighting                        - Метод удаления средства пожарной безопасности
    //  SaveFireFightingEquipment                 - Сохранение средства пожарной безопасности в справочник
    #endregion

    #region Блок констант
    const STATUS_FIRE_FIGHTING_ISSUED = 64;
    const WRITE_OFF_FF_EQ = 66;
    const MAINTENCNCE_FF_EQ_SPEC = 65;

    #endregion

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetFireFightEquipmentSpecific() - Метод получения данных для блока "Спецификация средств пожарной базопасности"
     * @param null $data_post - JSON с данными: идентификатор участка, объект даты
     * @return array - массив со следующей структурой: [company_department_id]
     *                                                          company_department_id:
     *                                                          company_title:
     *                                                          [places]
     *                                                              [place_id]
     *                                                                   place_id:
     *                                                                   place_title:
     *                                                                   [by_type]                                       -если есть
     *                                                                        [fire_fighting_equipment_type_id]
     *                                                                                      type_id:
     *                                                                                      type_title:
     *                                                                                      count_issued_fact:
     *                                                                                      count_issued_plan:
     *                                                                                      wear_period:
     *                                                                                      unit_title:
     *                                                                                      [fire_fighting_equipment_data]
     *                                                                                                  fire_fighting_equipment_id:
     *                                                                                                  inventory_number:
     *                                                                                                  title:
     *                                                                                                  commissioning_date_start:
     *                                                                                                  commissioning_date_formated:
     *                                                                                                  commissioning_date_end:
     *                                                                                                  commissioning_date_end_formated:
     *                                                                                                  status_id:
     *                                                                   [fire_fighting_equipment_data]
     *                                                                                 wear_period:
     *                                                                                 unit_title:
     *                                                                                 count_issued_fact:
     *                                                                                 count_issued_plan:
     *                                                                                 fire_fighting_equipment_id:
     *                                                                                 inventory_number:
     *                                                                                 title:
     *                                                                                 commissioning_date_start:
     *                                                                                 commissioning_date_formated:
     *                                                                                 commissioning_date_end:
     *                                                                                 commissioning_date_end_formated:
     *                                                                                 status_id:
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\FireFightingEquipment&method=GetFireFightEquipmentSpecific&subscribe=&data={%22company_department_id%22:20028766,%22chosen_date%22:{%22date%22:%222019-09-30T17:00:00.000Z%22,%22year%22:2019,%22monthNumber%22:10,%22numberDays%22:31,%22monthTitle%22:%22%D0%9E%D0%BA%D1%82%D1%8F%D0%B1%D1%80%D1%8C%22}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.10.2019 16:24
     */
    public static function GetFireFightEquipmentSpecific($data_post = null)
    {
        $status = 1; // Флаг успешного выполнения метода
        $warnings = array(); // Массив предупреждений
        $errors = array(); // Массив ошибок
        $result = array(); // Массив ошибок
        $fire_list = array();
        $place_id = null;
        $warnings[] = 'GetFireFightEquipmentSpecific. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('getObjectSiz. Данные с фронта не получены');
            }
            $warnings[] = 'GetFireFightEquipmentSpecific. Данные успешно переданы';
            $warnings[] = 'GetFireFightEquipmentSpecific. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);
// Декодируем входной массив данных
            $warnings[] = 'GetFireFightEquipmentSpecific. Декодировал входные параметры';
            if (
                !(
                    property_exists($post_dec, 'company_department_id') &&
                    property_exists($post_dec, 'chosen_date')
                )
            ) {
                throw new Exception('getObjectSiz. Переданы некорректные входные параметры');
            }
            $company_department_id = $post_dec->company_department_id;
            $chosen_date = $post_dec->chosen_date;
            /*
            * из объекта $chosen_date берём: месяц и год, количество дней в месяце и получаем дату
            */
            $month = $chosen_date->monthNumber;
            $year = $chosen_date->year;
            $days = $chosen_date->numberDays;
            $date = "$year-$month-$days";
//            $date_to_write_off = "$year-$month-01";
            /*
            * получаем список всех вложенных участков
            */
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new \Exception('GetFireFightEquipmentSpecific. Ошибка получения вложенных департаментов' . $company_department_id);
            }
            if (property_exists($post_dec, 'place_id')) {
                $place_id = $post_dec->place_id;
            }
            /*
            * Выборка на основе пришедших параметров с связями: По типам средства пожаротушения
            * Связка документов и средств пожаротушения
            * Места
            * Условные единицы
            * Участок
            * Специфика средств пожаротушения
            */
            $get_fire_fights = FireFightingObject::find()
                ->joinWith('fireFightingEquipment.unit')
                ->joinWith('place')
                ->joinWith('companyDepartment.company')
                ->joinWith('fireFightingEquipmentSpecifics.fireFightingEquipmentDocuments.attachment')
                ->where(['in', 'fire_fighting_object.company_department_id', $company_departments])
                ->andFilterWhere(['fire_fighting_object.place_id' => $place_id])
                ->asArray()
                ->all();

            foreach ($get_fire_fights as $ffo) {
                $title = $ffo['fireFightingEquipment']['title'];
                $unit_title = $ffo['fireFightingEquipment']['unit']['title'];

                $company_department_id = $ffo['company_department_id'];
                $company_title = $ffo['companyDepartment']['company']['title'];
                $place_title = $ffo['place']['title'];
                $place_id = $ffo['place_id'];
                $object_id = $ffo['id'];


                $fire_list[$company_department_id]['places'][$place_id]['place_id'] = $place_id;
                $fire_list[$company_department_id]['company_department_id'] = $company_department_id;
                $fire_list[$company_department_id]['company_department_title'] = $company_title;
                $fire_list[$company_department_id]['places'][$place_id]['place_title'] = $place_title;
                $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['fire_fighting_object_id'] = $object_id;
                $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['fire_fighting_equipment_id'] = $ffo['fire_fighting_equipment_id'];
                $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['fire_fighting_object_title'] = $title;
                $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['unit_title'] = $unit_title;
                $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['count_issued_fact'] = $ffo['count_issued_fact'];
                $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['count_issued_plan'] = $ffo['count_issued_plan'];
                $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['specific_objects'] = array();
                if (isset($ffo['fireFightingEquipmentSpecifics'])) {
                    foreach ($ffo['fireFightingEquipmentSpecifics'] as $getFireFightingEquipmentSpecific) {
                        if (
                            (
                                date('Y', strtotime($getFireFightingEquipmentSpecific['date_issue'])) < $year
                                or
                                (
                                    date('Y', strtotime($getFireFightingEquipmentSpecific['date_issue'])) == $year and
                                    date('m', strtotime($getFireFightingEquipmentSpecific['date_issue'])) <= $month
                                )
                            ) and (
                                date('Y', strtotime($getFireFightingEquipmentSpecific['date_write_off'])) > $year
                                or
                                (
                                    date('Y', strtotime($getFireFightingEquipmentSpecific['date_write_off'])) == $year and
                                    date('m', strtotime($getFireFightingEquipmentSpecific['date_write_off'])) >= $month
                                )
                            ) or (
                                date('m', strtotime($getFireFightingEquipmentSpecific['date_issue'])) == $month &&
                                date('Y', strtotime($getFireFightingEquipmentSpecific['date_issue'])) == $year
                            )
                        ) {
                            $specific_object_id = $getFireFightingEquipmentSpecific['id'];
                            $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['specific_objects'][$specific_object_id]['fire_fighting_equipment_specific_id'] = $specific_object_id;
                            $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['specific_objects'][$specific_object_id]['inventory_number'] = $getFireFightingEquipmentSpecific['inventory_number'];
                            $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['specific_objects'][$specific_object_id]['wear_period'] = $getFireFightingEquipmentSpecific['wear_period'];
                            $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['specific_objects'][$specific_object_id]['date_issue'] = $getFireFightingEquipmentSpecific['date_issue'];
                            $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['specific_objects'][$specific_object_id]['date_issue_formated'] = date('d.m.Y', strtotime($getFireFightingEquipmentSpecific['date_issue']));
                            $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['specific_objects'][$specific_object_id]['date_write_off'] = $getFireFightingEquipmentSpecific['date_write_off'];
                            if ($getFireFightingEquipmentSpecific['date_write_off'] == null) {
                                $date_write_off_formated = null;
                            } else {
                                $date_write_off_formated = date('d.m.Y', strtotime($getFireFightingEquipmentSpecific['date_write_off']));
                            }
                            $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['specific_objects'][$specific_object_id]['date_write_off_formated'] = $date_write_off_formated;
                            $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['specific_objects'][$specific_object_id]['description'] = $getFireFightingEquipmentSpecific['description'];
                            $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['specific_objects'][$specific_object_id]['status_id'] = $getFireFightingEquipmentSpecific['status_id'];
                            if (isset($getFireFightingEquipmentSpecific['fireFightingEquipmentDocuments']) and !empty($getFireFightingEquipmentSpecific['fireFightingEquipmentDocuments'])) {
                                foreach ($getFireFightingEquipmentSpecific['fireFightingEquipmentDocuments'] as $ff_eq_document) {
                                    $attachment_id = $ff_eq_document['attachment_id'];
                                    $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['specific_objects'][$specific_object_id]['documents'][$attachment_id]['document_id'] = $ff_eq_document['attachment']['id'];
                                    $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['specific_objects'][$specific_object_id]['documents'][$attachment_id]['document_path'] = $ff_eq_document['attachment']['path'];
                                    $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['specific_objects'][$specific_object_id]['documents'][$attachment_id]['document_title'] = $ff_eq_document['attachment']['title'];
                                    $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['specific_objects'][$specific_object_id]['documents'][$attachment_id]['document_type'] = $ff_eq_document['attachment']['attachment_type'];
                                    $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['specific_objects'][$specific_object_id]['documents'][$attachment_id]['document_blob'] = null;
                                    $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['specific_objects'][$specific_object_id]['documents'][$attachment_id]['document_status'] = '';
                                }
                            } else {
                                $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['specific_objects'][$specific_object_id]['documents'] = (object)array();
                            }
                        }
                    }
                }
                if (empty($fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['specific_objects'])) {
                    $fire_list[$company_department_id]['places'][$place_id]['fire_fighting_objects'][$object_id]['specific_objects'] = (object)array();
                }

            }

            /*
            * Перебор полученных данных по средствам пожаротушения
            */

            if (!isset($fire_list) or empty($fire_list)) {
                $result['listFireGear'] = (object)array();
            } else {
                $result['listFireGear'] = $fire_list;
            }

            $result_statistic = self::GetFireFightingStatistic($company_departments, $date);
            if ($result_statistic['status'] == 1) {
                $result['statistic'] = $result_statistic['Items'];
                $warnings[] = $result_statistic['warnings'];
            } else {
                $errors[] = $result_statistic['errors'];
                $warnings[] = $result_statistic['warnings'];
                throw new \Exception('GetFireFightEquipmentSpecific. Возникла ошибка при получении статистики');
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetFireFightEquipmentSpecific. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetFireFightEquipmentSpecific. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SaveFireFightSpecificObjectlast() - Метод сохранения из модального окна "Добавить объект" - старый метод, только добавляет
     * @param null $data_post - JSON с данными: идентификатор участка, идеинтификатор места, объекты (которые необходимо добавить)
     * @return array - стандартный массив выходных данных
     *
     * @package frontend\controllers\industrial_safety
     *
     * Входные обязательные параметры:
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\FireFightingEquipment&method=SaveFireFightSpecificObjectlast&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.10.2019 16:22
     */
    public static function SaveFireFightSpecificObjectlast($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $saved_specific = array();                                                                                // Промежуточный результирующий массив
        $ff_eq = array();
        $insert_fire_fight_equipment = array();
        $warnings[] = 'SaveFireFightSpecificObject. Начало метода';
//        $data_post = '{"company_department_id":501,"place_id":6181,"objects":{"1":{"fire_fighting_equipment_id":1,"count_issued_plan":97},"2":{"fire_fighting_equipment_id":3,"count_issued_plan":52},"3":{"fire_fighting_equipment_id":5,"count_issued_plan":40},"4":{"fire_fighting_equipment_id":7,"count_issued_plan":8}}}';
        try {
            $transaction = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception('SaveFireFightSpecific. Не переданы входные параметры');
            }
            $warnings[] = 'SaveFireFightSpecificObject. Данные успешно переданы';
            $warnings[] = 'SaveFireFightSpecificObject. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'SaveFireFightSpecificObject. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'place_id') ||
                !property_exists($post_dec, 'objects'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception('SaveFireFightSpecificObject. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveFireFightSpecificObject. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $place_id = $post_dec->place_id;
            $objects = $post_dec->objects;
            foreach ($objects as $object) {
                $insert_fire_fight_equipment[] = [
                    $object->fire_fighting_equipment_id,
                    $object->count_issued_plan,
                    $company_department_id,
                    $place_id
                ];
                $ff_eq[] = $object->fire_fighting_equipment_id;
            }

            if (!empty($insert_fire_fight_equipment)) {
                $batch_fire_fight_object = Yii::$app->db
                    ->createCommand()
                    ->batchInsert(
                        'fire_fighting_object',
                        [
                            'fire_fighting_equipment_id',
                            'count_issued_plan',
                            'company_department_id',
                            'place_id'
                        ], $insert_fire_fight_equipment)
                    ->execute();
                if ($batch_fire_fight_object != 0) {
                    $warnings[] = 'SaveFireFightSpecificObject. Объекты успешно добавлены';
                } else {
                    throw new \Exception('SaveFireFightSpecificObject. Ошибка при добавлении объектов');
                }
            }
            $transaction->commit();
//            $json_to_get = json_decode(array('place_id'=>$place_id,'company_department_id'=>$company_department_id,'chosen_date'=>$chosen_date);

            $get_saved_object = FireFightingObject::find()
                ->joinWith('fireFightingEquipment.unit')
                ->joinWith('place')
                ->joinWith('companyDepartment.company')
                ->where(['fire_fighting_object.company_department_id' => $company_department_id])
                ->andWhere(['fire_fighting_object.place_id' => $place_id])
                ->andWhere(['in', 'fire_fighting_equipment.id', $ff_eq])
                ->all();
            if (isset($get_saved_object)) {
                foreach ($get_saved_object as $object) {
                    $comp_dep_id = $object->company_department_id;
                    $place_id = $object->place_id;
                    $fire_fighting_obj_id = $object->id;
                    $saved_specific[$comp_dep_id]['company_department_id'] = $comp_dep_id;
                    $saved_specific[$comp_dep_id]['company_title'] = $object->companyDepartment->company->title;
                    $saved_specific[$comp_dep_id]['places'][$place_id]['place_id'] = $place_id;
                    $saved_specific[$comp_dep_id]['places'][$place_id]['place_title'] = $object->place->title;
                    $saved_specific[$comp_dep_id]['places'][$place_id]['fire_fighting_objects'][$fire_fighting_obj_id]['fire_fighting_object_id'] = $fire_fighting_obj_id;
                    $saved_specific[$comp_dep_id]['places'][$place_id]['fire_fighting_objects'][$fire_fighting_obj_id]['fire_fighting_object_title'] = $object->fireFightingEquipment->title;
                    $saved_specific[$comp_dep_id]['places'][$place_id]['fire_fighting_objects'][$fire_fighting_obj_id]['unit_title'] = $object->fireFightingEquipment->unit->title;
                    $saved_specific[$comp_dep_id]['places'][$place_id]['fire_fighting_objects'][$fire_fighting_obj_id]['count_issued_fact'] = $object->count_issued_fact;
                    $saved_specific[$comp_dep_id]['places'][$place_id]['fire_fighting_objects'][$fire_fighting_obj_id]['count_issued_plan'] = $object->count_issued_plan;
                    $saved_specific[$comp_dep_id]['places'][$place_id]['fire_fighting_objects'][$fire_fighting_obj_id]['specific_objects'] = (object)array();
                }
            }
        } catch (Throwable $exception) {
            $transaction->rollBack();
            $errors[] = 'SaveFireFightSpecificObject. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SaveFireFightSpecificObject. Конец метода';
        $result = $saved_specific;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * SaveFireFightSpecificObject - метод сохранения видов и средств пожаротешения
     * Пример вызова:
     *      http://127.0.0.1/read-manager-amicum?controller=industrial_safety\FireFightingEquipment&method=SaveFireFightSpecificObject&subscribe=&data={%22company_department_id%22:101,%22company_title%22:%22Прочее%22,%22places%22:{%224%22:{%22status%22:%22delete%22,%22place_id%22:4,%22place_title%22:%22БИС № 2%22,%22fire_fighting_objects%22:{%2236%22:{%22status%22:%22delete%22,%22fire_fighting_equipment_id%22:%2232%22,%22fire_fighting_object_title%22:%22Веревки+пожарные+спасательные%22,%22unit_title%22:%22Штук%22,%22fire_fighting_object_id%22:36,%22unit_title%22:%22Штук%22,%22count_issued_fact%22:null,%22count_issued_plan%22:12,%22specific_objects%22:{}}}}}}
     * Выходной/выходной объект:
     *      company_department_id       101             - ключ департамента
     *      company_title               "Прочее"        - название департамента
     *      places                                      - список мест
     *          {4}                                         - ключ места
     *              place_id                4                   - ключ места
     *              place_title             "БИС № 2"           - название места
     *              status                  ""                  - статус удаления (если == delete, то СПТ по данному месту и департаменту будут удалены
     *              fire_fighting_objects                       - список СПТ
     *                  {36}                                        - ключ СПТ
     *                      fire_fighting_object_id         36                                  - ключ СПТ
     *                      fire_fighting_equipment_id      "32"                                - ключ оборудования СПТ
     *                      fire_fighting_object_title      "Веревки пожарные спасательные"     - название оборудования СПТ
     *                      unit_title                      "Штук"                              - название ед.изм оборубования СПТ
     *                      count_issued_plan               12                                  - количество средств СПТ по плану
     *                      count_issued_fact               null                                - количество средств СПТ по факту
     *                      status                          ""                                  - статус удаления (если == delete, то СПТ по данному месту и департаменту будут удалены
     *                      specific_objects                {}                                  - список конкретных СПТ
     *                          {28}                                                                  - ключ конкретного СПТ
     *                              fire_fighting_equipment_specific_id    28                               - ключ конкретного СПТ
     *                              inventory_number    "549"                                               - инвентарный номер конкретного спт
     *                              wear_period    55                                                       - период использования/эксплуатации
     *                              date_issue    "2021-05-06"                                              - дата ввода в эксплуатацию
     *                              date_issue_formated    "06.05.2021"                                     - форматированная дата ввода в эксплуатацию
     *                              date_write_off    "2076-05-06"                                          - дата списания конкретного СПТ
     *                              date_write_off_formated    "06.05.2076"                                 - форматированная дата списания конкретного спт
     *                              description    ""                                                       - описание
     *                              status_id    64                                                         - статус конкретного спт
     *                              documents    Object { }                                                 - список вложений
     */
    public static function SaveFireFightSpecificObject($data_post = NULL)
    {
        $result = [];
        $log = new LogAmicumFront("SaveFireFightSpecificObject");
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }

            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных

            $log->addData($post_dec, '$post_dec', __LINE__);
            $log->addLog("Декодировал входные параметры");
            if (!property_exists($post_dec, 'company_department_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $company_department_id = $post_dec->company_department_id;
            $spt = $post_dec;
            $session = Yii::$app->session;

            $ffo = FireFightingObject::findOne([
                'company_department_id' => $company_department_id,
                'fire_fighting_equipment_id' => $spt->fire_fighting_equipment_id,
                'place_id' => $spt->place_id,]);

            if (!$ffo) {
                $ffo = new FireFightingObject();
            }

            $ffo->fire_fighting_equipment_id = $spt->fire_fighting_equipment_id;
            $ffo->count_issued_plan = $spt->count_issued_plan;
            $ffo->count_issued_fact = $spt->count_issued_fact;
            $ffo->company_department_id = $company_department_id;
            $ffo->place_id = $spt->place_id;

            if (!$ffo->save()) {
                $log->addData($ffo->errors, '$ffo->errors', __LINE__);
                throw new Exception('Ошибка сохранения СПТ. Модель FireFightingObject');
            }
            $post_dec->fire_fighting_object_id = $ffo->id;

            if (isset($spt->specific_objects)) {
                foreach ($spt->specific_objects as $key_spo => $spo) {
                    if (property_exists($spo, 'status') and $spo->status == "delete") {
                        FireFightingEquipmentSpecific::deleteAll(["id" => $spo->fire_fighting_equipment_specific_id]);
                        unset($post_dec->specific_objects->$key_spo);
                    } else {
                        $ffes = FireFightingEquipmentSpecific::findOne(['id' => $spo->fire_fighting_equipment_specific_id]);

                        if (!$ffes) {
                            $ffes = new FireFightingEquipmentSpecific();
                        }

                        $ffes->fire_fighting_object_id = $ffo->id;
                        $ffes->inventory_number = $spo->inventory_number;
                        $ffes->wear_period = $spo->wear_period;
                        $ffes->date_issue = $spo->date_issue;
                        $ffes->date_write_off = $spo->date_write_off;
                        $ffes->description = $spo->description;
                        $ffes->status_id = $spo->status_id;

                        if (!$ffes->save()) {
                            $log->addData($ffes->errors, '$ffes->errors', __LINE__);
                            throw new Exception('Ошибка сохранения конкретного СПТ. Модель FireFightingEquipmentSpecific');
                        }
                        $post_dec->specific_objects[$key_spo]->fire_fighting_equipment_specific_id = $ffes->id;

                        $ffes_status = new FireFightingEquipmentSpecificStatus();
                        $ffes_status->status_id = $spo->status_id;
                        $ffes_status->date_time = Assistant::GetDateTimeNow();
                        $ffes_status->fire_fighting_equipment_specific_id = $ffes->id;

                        if (!$ffes_status->save()) {
                            $log->addData($ffes_status->errors, '$ffes_status->errors', __LINE__);
                            throw new Exception('Ошибка сохранения статуса конкретного СПТ. Модель FireFightingEquipmentSpecificStatus');
                        }

                        // сохранить вложение
                        if (!empty($spo->documents)) {
                            foreach ($spo->documents as $key_attach => $document) {
                                $ff_eq_spec_doc_id = $document->document_id;
                                $attachment = Attachment::findOne(['id' => $ff_eq_spec_doc_id]);
                                if (!empty($attachment)) {
                                    if ($document->document_status == 'del') {
                                        FireFightingEquipmentDocuments::deleteAll(['fire_fighting_equipment_specific_id' => $ffes->id]);
                                        unset($post_dec->specific_objects[$key_spo]->documents->$key_attach);
                                    }
                                }
                                if ($document->document_status != 'del') {
                                    if ($document->document_id <= 0) {
                                        $normalize_path = Assistant::UploadFile($document->document_blob, $document->document_title, 'attachment', $document->document_type);
                                        $add_attachment = new Attachment();
                                        $add_attachment->path = $normalize_path;
                                        $add_attachment->date = BackendAssistant::GetDateFormatYMD();
                                        $add_attachment->worker_id = $session['worker_id'];
                                        $add_attachment->section_title = 'ОТ и ПБ/Контроль наличия средств пожарной безопасности';
                                        $add_attachment->attachment_type = $document->document_type;
                                        $add_attachment->title = $document->document_title;
                                        if ($add_attachment->save()) {
                                            $warnings[] = 'SaveFireFightingEquipmentSpecific. Вложение успешно сохранено';
                                            $add_attachment->refresh();
                                            $add_attachment_id = $add_attachment->id;
                                            $post_dec->specific_objects[$key_spo]->documents->$key_attach->document_path = $add_attachment->path;
                                            $post_dec->specific_objects[$key_spo]->documents->$key_attach->document_id = $add_attachment->id;
                                            $post_dec->specific_objects[$key_spo]->documents->$key_attach->document_title = $add_attachment->title;
                                            $post_dec->specific_objects[$key_spo]->documents->$key_attach->document_type = $document->document_type;
                                            $post_dec->specific_objects[$key_spo]->documents->$key_attach->document_blob = null;
                                            $post_dec->specific_objects[$key_spo]->documents->$key_attach->document_status = $document->document_status;
                                        } else {
                                            $log->addData($add_attachment->errors, '$add_attachment->errors', __LINE__);
                                            throw new Exception('Ошибка при сохранении вложения');
                                        }

                                        if ($document->document_title != null) {
                                            $ff_eq_doc[] = [$ffes->id, $add_attachment_id];
                                        }

                                    } else {
                                        $post_dec->specific_objects[$key_spo]->documents->$key_attach->document_path = $document->document_path;
                                        $post_dec->specific_objects[$key_spo]->documents->$key_attach->document_id = $document->document_id;
                                        $post_dec->specific_objects[$key_spo]->documents->$key_attach->document_title = $document->document_title;
                                        $post_dec->specific_objects[$key_spo]->documents->$key_attach->document_type = $document->document_type;
                                        $post_dec->specific_objects[$key_spo]->documents->$key_attach->document_blob = null;
                                        $post_dec->specific_objects[$key_spo]->documents->$key_attach->document_status = $document->document_status;
                                    }
                                }
                            }

                        }

                    }
                }
            }

            if (isset($ff_eq_doc)) {
                $result_ff_eq_doc = Yii::$app->db
                    ->createCommand()
                    ->batchInsert('fire_fighting_equipment_documents', [
                        'fire_fighting_equipment_specific_id',
                        'attachment_id'
                    ], $ff_eq_doc)
                    ->execute();
                if (!$result_ff_eq_doc) {
                    throw new Exception('Ошибка при добавлении свзки вложения и средства пожарной безопасности');
                }
            }

            $result = $post_dec;

            if (property_exists($post_dec, 'chosen_date')) {
                $chosen_date = $post_dec->chosen_date;
                $month = $chosen_date->monthNumber;
                $year = $chosen_date->year;
                $days = $chosen_date->numberDays;
                $date = "$year-$month-$days";

                $response = DepartmentController::FindDepartment($company_department_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка получения вложенных департаментов' . $company_department_id);
                }
                $company_departments = $response['Items'];
                /*
                * из объекта $chosen_date берём: месяц и год, количество дней в месяце и получаем дату
                */


                $result_statistic = self::GetFireFightingStatistic($company_departments, $date);
                $log->addLogAll($response);
                if ($result_statistic['status'] != 1) {
                    throw new Exception('Возникла ошибка при получении статистики');
                }

                $result->statistic = $result_statistic['Items'];
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод SaveFireFightingEquipmentSpecific() - Метод сохранения средства пожарной безопасности
     * @param null $data_post - JSON с массивом данных - [specifics_equipments]
     *                                                              [iterator]
     *                                                                  fire_fighting_object_id:
     *                                                                  inventory_number:
     *                                                                  wear_period:
     *                                                                  date_issue:
     *                                                                  date_write_off:
     *                                                                  description:
     *                                                                  [documents]
     *                                                                          [iterator]
     *                                                                                document_id:
     * @return array - стнадратный массив выходных даннных
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\FireFightingEquipment&method=SaveFireFightingEquipmentSpecific&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.10.2019 15:21
     */
    public static function SaveFireFightingEquipmentSpecific($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $equipment_specific = array();                                                                                // Промежуточный результирующий массив
        $inserted_ff_eq_status = array();
        $inventory_numbers = array();
        $ff_eq_doc = array();
        $session = Yii::$app->session;
        $warnings[] = 'SaveFireFightingEquipmentSpecific. Начало метода';
//        $data_post = '{"fire_fighting_object_id":27,"specific_objects":{"1":{"fire_fighting_equipment_id":null,"inventory_number":112461,"wear_period":5.5,"date_issue":"2018-12-17","date_write_off":"2023-12-17","description":null,"document":{"document_id":null,"document_path":"/img/attachment/111_08-10-2019 15-44-17.1570524257.oxps","document_title":"111","document_type":"oxps","document_blob":null,"document_status":""}},"2":{"fire_fighting_equipment_id":null,"inventory_number":112462,"wear_period":5.5,"date_issue":"2018-12-17","date_write_off":"2023-12-17","description":null,"document":{"document_id":null,"document_path":"/img/attachment/111_08-10-2019 15-44-17.1570524257.oxps","document_title":"111","document_type":"oxps","document_blob":null,"document_status":""}},"3":{"fire_fighting_equipment_id":null,"inventory_number":112463,"wear_period":5.5,"date_issue":"2018-12-17","date_write_off":"2023-12-17","description":null,"document":{"document_id":null,"document_path":"/img/attachment/111_08-10-2019 15-44-17.1570524257.oxps","document_title":"111","document_type":"oxps","document_blob":null,"document_status":""}},"4":{"fire_fighting_equipment_id":null,"inventory_number":112464,"wear_period":5.5,"date_issue":"2018-12-17","date_write_off":"2023-12-17","description":null,"document":{"document_id":null,"document_path":"/img/attachment/111_08-10-2019 15-44-17.1570524257.oxps","document_title":"111","document_type":"oxps","document_blob":null,"document_status":""}},"5":{"fire_fighting_equipment_id":null,"inventory_number":112465,"wear_period":5.5,"date_issue":"2018-12-17","date_write_off":"2023-12-17","description":null,"document":{"document_id":null,"document_path":"/img/attachment/111_08-10-2019 15-44-17.1570524257.oxps","document_title":"111","document_type":"oxps","document_blob":null,"document_status":""}},"6":{"fire_fighting_equipment_id":null,"inventory_number":112466,"wear_period":5.5,"date_issue":"2018-12-17","date_write_off":"2023-12-17","description":null,"document":{"document_id":null,"document_path":"/img/attachment/111_08-10-2019 15-44-17.1570524257.oxps","document_title":"111","document_type":"oxps","document_blob":null,"document_status":""}},"7":{"fire_fighting_equipment_id":null,"inventory_number":112467,"wear_period":5.5,"date_issue":"2018-12-17","date_write_off":"2023-12-17","description":null,"document":{"document_id":null,"document_path":"/img/attachment/111_08-10-2019 15-44-17.1570524257.oxps","document_title":"111","document_type":"oxps","document_blob":null,"document_status":""}}}}';
        try {
            $transaction = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception('SaveFireFightingEquipmentSpecific. Не переданы входные параметры');
            }
            $warnings[] = 'SaveFireFightingEquipmentSpecific. Данные успешно переданы';
            $warnings[] = 'SaveFireFightingEquipmentSpecific. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'SaveFireFightingEquipmentSpecific. Декодировал входные параметры';
            if (!property_exists($post_dec, 'specific_objects') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'date'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception('SaveFireFightingEquipmentSpecific. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveFireFightingEquipmentSpecific. Данные с фронта получены';
            $specific_objects = $post_dec->specific_objects;
            $chosen_date = $post_dec->date;
            $month = $chosen_date->monthNumber;
            $year = $chosen_date->year;
            $days = $chosen_date->numberDays;
            $date = "$year-$month-$days";
            /**
             * Получаем список всех вложенных участков
             */
            $company_department_id = $post_dec->company_department_id;
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new \Exception('SaveFireFightingEquipmentSpecific. Ошибка получения вложенных департаментов' . $company_department_id);
            }


            foreach ($specific_objects as $specific_object) {
                if ($specific_object->flag == 'new') {
                    $inventory_numbers[] = (string)$specific_object->inventory_number;
                }
            }

            $found_inventory_numbers = FireFightingEquipmentSpecific::find()
                ->where(['in', 'inventory_number', $inventory_numbers])
                ->all();
            if (!empty($found_inventory_numbers)) {
                foreach ($found_inventory_numbers as $found_inventory_number) {
                    $inv_numbers_errors[] = $found_inventory_number->inventory_number;
                }
            }

            if (empty($inv_numbers_errors)) {
                foreach ($specific_objects as $specific_object) {
                    $fire_fighting_object_id = $specific_object->fire_fighting_object_id;
                    $ff_eq_spec = FireFightingEquipmentSpecific::findOne(['id' => $specific_object->fire_fighting_equipment_id]);
                    if ($ff_eq_spec == null) {
                        $ff_eq_spec = new FireFightingEquipmentSpecific();
                        /******************** Обновляем фактическое значение объекта  если это добавление нового СПБ ********************/
                        $get_object_fire_fighting = FireFightingObject::findOne(['id' => $fire_fighting_object_id]);
                        if ($get_object_fire_fighting != null) {
                            if ($get_object_fire_fighting->count_issued_fact == null) {
                                $get_object_fire_fighting->count_issued_fact = 1;
                            } else {
                                $get_object_fire_fighting->updateCounters(['count_issued_fact' => 1]);
                            }
                            if ($get_object_fire_fighting->save()) {
                                $warnings[] = 'SaveFireFightingEquipmentSpecific. Счётчик фактический обновлён';
                            } else {
                                $errors[] = $get_object_fire_fighting->errors;
                                throw new \Exception('SaveFireFightingEquipmentSpecific. Ошика при сохранении счётчика');
                            }
                            $equipment_specific['objects'][$fire_fighting_object_id]['object_id'] = $fire_fighting_object_id;
                            $equipment_specific['objects'][$fire_fighting_object_id]['count_issued_fact'] = $get_object_fire_fighting->count_issued_fact;
                        }
                    }
                    $find_inv_number = FireFightingEquipmentSpecific::findOne(['inventory_number' => $specific_object->inventory_number]);
                    if ($specific_object->inventory_number == $ff_eq_spec->inventory_number) {
                        $find_inv_number = array();
                    }
                    if (empty($find_inv_number)) {
                        $ff_eq_spec->fire_fighting_object_id = $fire_fighting_object_id;
                        $ff_eq_spec->inventory_number = (string)$specific_object->inventory_number;
                        $ff_eq_spec->wear_period = $specific_object->wear_period;
                        $ff_eq_spec->date_issue = date('Y-m-d', strtotime($specific_object->date_issue));
                        $ff_eq_spec->date_write_off = date('Y-m-d', strtotime($specific_object->date_write_off));
                        $ff_eq_spec->description = $specific_object->description;
                        $ff_eq_spec->status_id = self::STATUS_FIRE_FIGHTING_ISSUED;
                        if ($ff_eq_spec->save()) {
                            $warnings[] = 'SaveFireFightingEquipmentSpecific. Средство пожарной безопасности успешно добавлено';
                            $ff_eq_spec->refresh();
                            $ff_eq_spec_id = $ff_eq_spec->id;
                            $equipment_specific['specific_objects'][$ff_eq_spec_id]['fire_fighting_object_id'] = $fire_fighting_object_id;
                            $equipment_specific['specific_objects'][$ff_eq_spec_id]['fire_fighting_equipment_specific_id'] = $ff_eq_spec_id;
                            $equipment_specific['specific_objects'][$ff_eq_spec_id]['inventory_number'] = $specific_object->inventory_number;
                            $equipment_specific['specific_objects'][$ff_eq_spec_id]['wear_period'] = $specific_object->wear_period;
                            $equipment_specific['specific_objects'][$ff_eq_spec_id]['date_issue'] = $specific_object->date_issue;
                            $equipment_specific['specific_objects'][$ff_eq_spec_id]['date_issue_formated'] = date('d.m.Y', strtotime($specific_object->date_issue));
                            $equipment_specific['specific_objects'][$ff_eq_spec_id]['date_write_off'] = $specific_object->date_write_off;
                            $equipment_specific['specific_objects'][$ff_eq_spec_id]['date_write_off_formated'] = date('d.m.Y', strtotime($specific_object->date_write_off));
                            $equipment_specific['specific_objects'][$ff_eq_spec_id]['description'] = $specific_object->description;
                            $equipment_specific['specific_objects'][$ff_eq_spec_id]['status_id'] = self::STATUS_FIRE_FIGHTING_ISSUED;
                            $equipment_specific['specific_objects'][$ff_eq_spec_id]['documents'] = array();
                        } else {
                            $errors[] = $ff_eq_spec->errors;
                            throw new \Exception('SaveFireFightingEquipmentSpecific. Возникла ошибка при добавлении средства пожарной безопасности');
                        }
                        if (!empty($specific_object->documents)) {
                            foreach ($specific_object->documents as $document) {
                                $ff_eq_spec_doc_id = $document->document_id;
                                $attachment = Attachment::findOne(['id' => $ff_eq_spec_doc_id]);
                                if (!empty($attachment)) {
                                    if ($document->document_status == 'del') {
                                        FireFightingEquipmentDocuments::deleteAll(['fire_fighting_equipment_specific_id' => $ff_eq_spec_id]);
                                    }
                                }
                                if ($document->document_id <= 0) {
                                    $normalize_path = Assistant::UploadFile($document->document_blob, $document->document_title, 'attachment', $document->document_type);
                                    $add_attachment = new Attachment();
                                    $add_attachment->path = $normalize_path;
                                    $add_attachment->date = BackendAssistant::GetDateFormatYMD();
                                    $add_attachment->worker_id = $session['worker_id'];
                                    $add_attachment->section_title = 'ОТ и ПБ/Контроль наличия средств пожарной безопасности';
                                    $add_attachment->attachment_type = $document->document_type;
                                    $add_attachment->title = $document->document_title;
                                    if ($add_attachment->save()) {
                                        $warnings[] = 'SaveFireFightingEquipmentSpecific. Вложение успешно сохранено';
                                        $add_attachment->refresh();
                                        $add_attachment_id = $add_attachment->id;
                                        $equipment_specific['specific_objects'][$ff_eq_spec_id]['documents'][$add_attachment_id]['document_path'] = $add_attachment->path;
                                        $equipment_specific['specific_objects'][$ff_eq_spec_id]['documents'][$add_attachment_id]['document_id'] = $add_attachment->id;
                                        $equipment_specific['specific_objects'][$ff_eq_spec_id]['documents'][$add_attachment_id]['document_title'] = $add_attachment->title;
                                        $equipment_specific['specific_objects'][$ff_eq_spec_id]['documents'][$add_attachment_id]['document_type'] = $document->document_type;
                                        $equipment_specific['specific_objects'][$ff_eq_spec_id]['documents'][$add_attachment_id]['document_blob'] = null;
                                        $equipment_specific['specific_objects'][$ff_eq_spec_id]['documents'][$add_attachment_id]['document_status'] = $document->document_status;
                                    } else {
                                        $errors[] = $add_attachment->errors;
                                        throw new \Exception('SaveFireFightingEquipmentSpecific. Ошибка при сохранении вложения');
                                    }

                                    if ($document->document_title != null) {
                                        $ff_eq_doc[] = [$ff_eq_spec_id, $add_attachment_id];
                                    }
                                    if (empty($equipment_specific['specific_objects'][$ff_eq_spec_id]['document'])) {
                                        $equipment_specific['specific_objects'][$ff_eq_spec_id]['document'] = (object)array();
                                    }
                                    if (isset($ff_eq_spec_id)) {
                                        $date_time_now = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));
                                        $inserted_ff_eq_status[] = [$ff_eq_spec_id, self::MAINTENCNCE_FF_EQ_SPEC, $date_time_now];
                                    }

                                } else {
                                    $equipment_specific['specific_objects'][$ff_eq_spec_id]['documents'][$document->document_id]['document_path'] = $document->document_path;
                                    $equipment_specific['specific_objects'][$ff_eq_spec_id]['documents'][$document->document_id]['document_id'] = $document->document_id;
                                    $equipment_specific['specific_objects'][$ff_eq_spec_id]['documents'][$document->document_id]['document_title'] = $document->document_title;
                                    $equipment_specific['specific_objects'][$ff_eq_spec_id]['documents'][$document->document_id]['document_type'] = $document->document_type;
                                    $equipment_specific['specific_objects'][$ff_eq_spec_id]['documents'][$document->document_id]['document_blob'] = null;
                                    $equipment_specific['specific_objects'][$ff_eq_spec_id]['documents'][$document->document_id]['document_status'] = $document->document_status;
                                }
                            }
                        }
                    } else {
                        $errors[] = 'Такой инвентарный номер уже существует';
                        $status = 0;
                    }
                }
            } else {
                $status = 0;
                $implode_inventory_numbers = implode(', ', $inventory_numbers);
                $errors[] = 'Такие инвентарные номера уже существуют: ' . $implode_inventory_numbers;
            }
            if (!empty($ff_eq_doc)) {
                $result_ff_eq_doc = Yii::$app->db
                    ->createCommand()
                    ->batchInsert('fire_fighting_equipment_documents', [
                        'fire_fighting_equipment_specific_id',
                        'attachment_id'
                    ], $ff_eq_doc)
                    ->execute();
                if ($result_ff_eq_doc != 0) {
                    $warnings[] = 'SaveFireFightingEquipmentSpecific. Связка вложения и средства пожарной безопасности успешно добавлено';
                } else {
                    throw new \Exception('SaveFireFightingEquipmentSpecific. Ошибка при добавлении свзки вложения и средства пожарной безопасности');
                }
            }
            if (!empty($inserted_ff_eq_status)) {
                $result_ff_eq_status = Yii::$app->db
                    ->createCommand()
                    ->batchInsert('fire_fighting_equipment_specific_status', [
                        'fire_fighting_equipment_specific_id',
                        'status_id',
                        'date_time'
                    ], $inserted_ff_eq_status)
                    ->execute();
                if ($result_ff_eq_status != 0) {
                    $warnings[] = 'SaveFireFightingEquipmentSpecific. Статусы средств пожарной безопасности успешно добавлены';
                } else {
                    throw new \Exception('SaveFireFightingEquipmentSpecific. Ошибка при сохранениис статусов пожарной безопасности');
                }
            }
            $result_statistic = self::GetFireFightingStatistic($company_departments, $date);
            if ($result_statistic['status'] == 1) {
                $equipment_specific['statistic'] = $result_statistic['Items'];
                $warnings[] = $result_statistic['warnings'];
            } else {
                $errors[] = $result_statistic['errors'];
                $warnings[] = $result_statistic['warnings'];
                throw new \Exception('SaveFireFightingEquipmentSpecific. Возникла ошибка при получении статистики');
            }
            $transaction->commit();
        } catch
        (Throwable $exception) {
            $transaction->rollBack();
            $errors[] = 'SaveFireFightingEquipmentSpecific. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SaveFireFightingEquipmentSpecific. Конец метода';
        $result = $equipment_specific;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'inv_numbers_errors' => $inventory_numbers);
        return $result_main;
    }

    /**
     * Метод GetFireFightingByObjects() - Метод получения данных для выпадающего списка в модальном окне "Добавить средство пожарной безопасности"
     * @param null $data_post - JSON с данными - идентификатор участка, идентификатор места
     * @return array - массив выходных данных со структурой: [fire_fighting_object_id]
     *                                                                      fire_fighting_object_id:
     *                                                                      fire_fighting_equipment_title:
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\FireFightingEquipment&method=GetFireFightingByObjects&subscribe=&data={%22company_department_id%22:501,%22place_id%22:6181}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.10.2019 15:52
     */
    public static function GetFireFightingByObjects($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetFireFightingByObjects. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception('GetFireFightingByObjects. Не переданы входные параметры');
            }
            $warnings[] = 'GetFireFightingByObjects. Данные успешно переданы';
            $warnings[] = 'GetFireFightingByObjects. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetFireFightingByObjects. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'place_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception('GetFireFightingByObjects. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetFireFightingByObjects. Данные с фронта получены';
            $place_id = $post_dec->place_id;
            $company_department_id = $post_dec->company_department_id;
            /*
             * Получение всех объектов по участку и месту
             */
            $fire_fighting_objects = FireFightingObject::find()
                ->innerJoinWith('fireFightingEquipment')
                ->where(['place_id' => $place_id, 'company_department_id' => $company_department_id])
                ->all();
            /*
             * Формируется структура со списком наименование оборудования для выпадашки модального окна "Добавить средство пожарной безопасности"
             */
            foreach ($fire_fighting_objects as $fire_fighting_object) {
                $result[$fire_fighting_object->id]['fire_fighting_object_id'] = $fire_fighting_object->id;
                $result[$fire_fighting_object->id]['fire_fighting_equipment_title'] = $fire_fighting_object->fireFightingEquipment->title;
            }

        } catch (Throwable $exception) {
            $errors[] = 'GetFireFightingByObjects. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetFireFightingByObjects. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод HandbookFireFightingEquipments() - Справочник средств пожарной безопасности
     * @return array - массив со структурой: [fire_fighting_equipment_id]
     *                                                      fire_fighting_equipment_id:
     *                                                      fire_fighting_equipment_title:
     *                                                      unit_title:
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\FireFightingEquipment&method=HandbookFireFightingEquipments&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.10.2019 16:04
     */
    public static function HandbookFireFightingEquipments()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $fire_fighting_equipments = array();
        $warnings[] = 'HandbookFireFightingEquipments. Начало метода';
        try {
            $fire_fighting_equipments = FireFightingEquipment::find()
                ->select([
                    'fire_fighting_equipment.id as fire_fighting_equipment_id',
                    'fire_fighting_equipment.title as fire_fighting_equipment_title',
                    'unit.title as unit_title'])
                ->innerJoin('unit', 'fire_fighting_equipment.unit_id = unit.id')
                ->asArray()
                ->indexBy('fire_fighting_equipment_id')
                ->all();
            if (!$fire_fighting_equipments) {
                $fire_fighting_equipments = (object)array();
            }
        } catch (Throwable $exception) {
            $errors[] = 'HandbookFireFightingEquipments. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'HandbookFireFightingEquipments. Конец метода';

        return array('Items' => $fire_fighting_equipments, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GraficReplacementFireFightingEquipment() - Метод возврата графика заменты ТО
     * @param null $data_post - JSON с годом на который нужно вернуть график
     * @return array - массивы выходных данных со структурой: [month]
     *                                                            month:
     *                                                            [fire_fightning_equipments]
     *                                                                          [day]
     *                                                                              fire_fightning_specific_id:
     *                                                                              fire_fightning_equipment_title:
     *                                                                              inventory_number:
     *                                                                              company_title:
     *                                                                              place_title:
     *                                                                              date_write_off:
     *                                                                              date_write_off_formated:
     *                                                                              status_id:
     *
     * @throws \Exception
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\FireFightingEquipment&method=GraficReplacementFireFightingEquipment&subscribe=&data={%22year%22:%222019%22}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.10.2019 16:47
     * @package frontend\controllers\industrial_safety
     *
     */
    public static function GraficReplacementFireFightingEquipment($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $date_now = new DateTime(date('Y-m-d', strtotime(BackendAssistant::GetDateNow())));
        $warnings[] = 'GraficReplacementFireFightingEquipment. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception('GraficReplacementFireFightingEquipment. Не переданы входные параметры');
            }
            $warnings[] = 'GraficReplacementFireFightingEquipment. Данные успешно переданы';
            $warnings[] = 'GraficReplacementFireFightingEquipment. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GraficReplacementFireFightingEquipment. Декодировал входные параметры';
            if (!property_exists($post_dec, 'year'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception('GraficReplacementFireFightingEquipment. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GraficReplacementFireFightingEquipment. Данные с фронта получены';
            $date_start = date('Y-m-d', strtotime($post_dec->year . '-01-01'));
            $date_end = date('Y-m-d', strtotime($post_dec->year . '-12-31'));
            /*
             * Формирую заготовку
             */
            for ($i = 1; $i <= 12; $i++) {
                if ($i < 10) {
                    $i = (int)'0' . $i;
                }
                $fire_fighting_eq[$i]['month'] = $i;
                $fire_fighting_eq[$i]['fire_fightning_equipments'] = array();
            }
            /*
             * Получение данных по оборудованиям с датой списания между 1 января переданного года и
             *                                                          31 декабря переданного года
             */
            $fire_fighting_equipments = FireFightingEquipmentSpecific::find()
                ->joinWith('fireFightingObject.fireFightingEquipment')
                ->joinWith('fireFightingObject.place')
                ->joinWith('fireFightingObject.companyDepartment.company')
                ->where(['between', 'fire_fighting_equipment_specific.date_write_off', $date_start, $date_end])
                ->andWhere(['in', 'fire_fighting_equipment_specific.status_id', [64, 65]])
                ->all();
            if (isset($fire_fighting_equipments)) {
                foreach ($fire_fighting_equipments as $fire_fighting_equipment) {
                    $date_write_off = new DateTime($fire_fighting_equipment->date_write_off);
                    $diff_date = $date_now->diff($date_write_off);
                    $diff_by_day = $diff_date->format('%r%a');
                    if ($diff_by_day <= 3 && $diff_by_day > 0) {
                        $flag = '#ffcc07';
                    } elseif ($diff_by_day < 0) {
                        $flag = '#b5596e';
                    }
                    if (isset($flag)) {
                        $month = date('m', strtotime($fire_fighting_equipment->date_write_off));
                        $day = (int)date('d', strtotime($fire_fighting_equipment->date_write_off));
                        $fire_fighting_equipment_id = $fire_fighting_equipment->id;
                        $company_title = $fire_fighting_equipment->fireFightingObject->companyDepartment->company->title;
                        $place_title = $fire_fighting_equipment->fireFightingObject->place->title;
                        $fire_fighting_eq[$month]['fire_fightning_equipments'][$day][$fire_fighting_equipment_id]['fire_fightning_specific_id'] = $fire_fighting_equipment_id;
                        $fire_fighting_eq[$month]['fire_fightning_equipments'][$day][$fire_fighting_equipment_id]['fire_fightning_equipment_title'] = $fire_fighting_equipment->fireFightingObject->fireFightingEquipment->title;
                        $fire_fighting_eq[$month]['fire_fightning_equipments'][$day][$fire_fighting_equipment_id]['inventory_number'] = $fire_fighting_equipment->inventory_number;
                        $fire_fighting_eq[$month]['fire_fightning_equipments'][$day][$fire_fighting_equipment_id]['company_title'] = $company_title;
                        $fire_fighting_eq[$month]['fire_fightning_equipments'][$day][$fire_fighting_equipment_id]['place_title'] = $place_title;
                        $fire_fighting_eq[$month]['fire_fightning_equipments'][$day][$fire_fighting_equipment_id]['date_write_off'] = $fire_fighting_equipment->date_write_off;
                        $fire_fighting_eq[$month]['fire_fightning_equipments'][$day][$fire_fighting_equipment_id]['flag'] = $flag;
                        $fire_fighting_eq[$month]['fire_fightning_equipments'][$day][$fire_fighting_equipment_id]['date_write_off_formated'] = date('d.m.Y', strtotime($fire_fighting_equipment->date_write_off));
                        $fire_fighting_eq[$month]['fire_fightning_equipments'][$day][$fire_fighting_equipment_id]['status_id'] = $fire_fighting_equipment->status_id;
                    }

                }
            }
            $found_all_TO = FireFightingEquipmentSpecificStatus::find()
                ->joinWith('fireFightingEquipmentSpecific.fireFightingObject.fireFightingEquipment')
                ->joinWith('fireFightingEquipmentSpecific.fireFightingObject.place')
                ->joinWith('fireFightingEquipmentSpecific.fireFightingObject.companyDepartment.company')
                ->where(['fire_fighting_equipment_specific_status.status_id' => 65])
                ->andWhere(['between', 'fire_fighting_equipment_specific.date_issue', $date_start, $date_end])
                ->all();
            if (!empty($found_all_TO)) {
                foreach ($found_all_TO as $ff_eq_with_TO) {
                    $month = date('m', strtotime($ff_eq_with_TO->date_time));
                    $day = (int)date('d', strtotime($ff_eq_with_TO->date_time));
                    $fire_fighting_equipment_id = $ff_eq_with_TO->fireFightingEquipmentSpecific->id;
                    $company_title = $ff_eq_with_TO->fireFightingEquipmentSpecific->fireFightingObject->companyDepartment->company->title;
                    $place_title = $ff_eq_with_TO->fireFightingEquipmentSpecific->fireFightingObject->place->title;
                    $fire_fighting_eq[$month]['fire_fightning_equipments'][$day][$fire_fighting_equipment_id]['fire_fightning_specific_id'] = $fire_fighting_equipment_id;
                    $fire_fighting_eq[$month]['fire_fightning_equipments'][$day][$fire_fighting_equipment_id]['fire_fightning_equipment_title'] = $ff_eq_with_TO->fireFightingEquipmentSpecific->fireFightingObject->fireFightingEquipment->title;
                    $fire_fighting_eq[$month]['fire_fightning_equipments'][$day][$fire_fighting_equipment_id]['inventory_number'] = $ff_eq_with_TO->fireFightingEquipmentSpecific->inventory_number;
                    $fire_fighting_eq[$month]['fire_fightning_equipments'][$day][$fire_fighting_equipment_id]['company_title'] = $company_title;
                    $fire_fighting_eq[$month]['fire_fightning_equipments'][$day][$fire_fighting_equipment_id]['place_title'] = $place_title;
                    $fire_fighting_eq[$month]['fire_fightning_equipments'][$day][$fire_fighting_equipment_id]['date_write_off'] = $ff_eq_with_TO->fireFightingEquipmentSpecific->date_write_off;
                    $fire_fighting_eq[$month]['fire_fightning_equipments'][$day][$fire_fighting_equipment_id]['flag'] = '#9d9f9e';
                    $fire_fighting_eq[$month]['fire_fightning_equipments'][$day][$fire_fighting_equipment_id]['date_write_off_formated'] = date('d.m.Y', strtotime($ff_eq_with_TO->fireFightingEquipmentSpecific->date_write_off));
                    $fire_fighting_eq[$month]['fire_fightning_equipments'][$day][$fire_fighting_equipment_id]['status_id'] = $ff_eq_with_TO->fireFightingEquipmentSpecific->status_id;
                }
            }
            $result = $fire_fighting_eq;
        } catch (Throwable $exception) {
            $errors[] = 'GraficReplacementFireFightingEquipment. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GraficReplacementFireFightingEquipment. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetFireFightingStatistic() - Метод предназначенный для блока статистики в "Контроль наличия средств пожарной безопасности"
     * @param $company_departments
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * Входные обязательные параметры:
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.10.2019 18:23
     */
    public static function GetFireFightingStatistic($company_departments, $date)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $result['all_fire_fighting_equipment'] = 0;
        $result['count_surface'] = 0;
        $result['count_underground'] = 0;
        $result['count_replacement'] = 0;
        $result['count_by_equipment'] = array();
        $warnings[] = 'GetFireFightingStatistic. Начало метода';
        try {
            /*
             * Блок в шахте и на поверхности
             */
            $count_by_ground_underground = FireFightingEquipmentSpecific::find()
                ->select('count(fire_fighting_equipment_specific.id) count_ffes, ko.id as kind_objects_id,fire_fighting_equipment_specific.id as fire_fighting_equipment_specific_id')
                ->leftJoin('fire_fighting_object', 'fire_fighting_object.id = fire_fighting_equipment_specific.fire_fighting_object_id')
                ->leftJoin('place p', 'fire_fighting_object.place_id = p.id')
                ->leftJoin('object o', 'p.object_id = o.id')
                ->leftJoin('object_type ot', 'o.object_type_id = ot.id')
                ->leftJoin('kind_object ko', 'ot.kind_object_id = ko.id')
                ->where(['in', 'ko.id', [2, 6]])
                ->andWhere(['in', 'fire_fighting_object.company_department_id', $company_departments])
                ->andWhere(['or',
                        ['and',
                            'month(date_issue)=' . (int)date("m", strtotime($date)),
                            'year(date_issue)=' . (int)date("Y", strtotime($date))
                        ],

                        ['and',
                            'year(date_issue)<' . (int)date("Y", strtotime($date)),
                            'year(date_write_off)>' . (int)date("Y", strtotime($date))
                        ],

                        ['and',
                            'year(date_issue)<' . (int)date("Y", strtotime($date)),
                            'month(date_write_off)>=' . (int)date("m", strtotime($date)),
                            'year(date_write_off)=' . (int)date("Y", strtotime($date))
                        ],

                        ['and',
                            'year(date_issue)=' . (int)date("Y", strtotime($date)),
                            'month(date_issue)<=' . (int)date("m", strtotime($date)),
                            'month(date_write_off)>=' . (int)date("m", strtotime($date)),
                            'year(date_write_off)=' . (int)date("Y", strtotime($date))
                        ],
                    ]
                )
                ->andWhere(['in', 'fire_fighting_equipment_specific.status_id', [64, 65, 1]])
                ->asArray()
                ->groupBy('kind_objects_id,fire_fighting_equipment_specific_id')
                ->all();
            $count_surface = 0;
            $count_underground = 0;
            foreach ($count_by_ground_underground as $counter_specific) {
                if ($counter_specific['kind_objects_id'] == 6) {
                    $count_surface++;
                } else {
                    $count_underground++;
                }
            }
            $result['count_surface'] = $count_surface;
            $result['count_underground'] = $count_underground;
            $result['all_fire_fighting_equipment'] = $count_surface + $count_underground;
            /*
             * Подлежит замене
             */
            $count_replacement_fire_fighting_equipment = FireFightingEquipmentSpecific::find()
                ->select(
                    'datediff(fire_fighting_equipment_specific.date_write_off, curdate()) as diff_date,
                            count(fire_fighting_equipment_specific.id) as count_spec_id,
                            ffe.title as spec_eq_title'
                )
                ->leftJoin('fire_fighting_object ffo', 'fire_fighting_equipment_specific.fire_fighting_object_id = ffo.id')
                ->leftJoin('fire_fighting_equipment ffe', 'ffo.fire_fighting_equipment_id = ffe.id')
                ->groupBy('diff_date,spec_eq_title')
                ->where(['in', 'ffo.company_department_id', $company_departments])
                ->andWhere(['in', 'fire_fighting_equipment_specific.status_id', [64, 65, 1]])
                ->andWhere(['or',
                        ['and',
                            'month(date_issue)=' . (int)date("m", strtotime($date)),
                            'year(date_issue)=' . (int)date("Y", strtotime($date))
                        ],

                        ['and',
                            'year(date_issue)<' . (int)date("Y", strtotime($date)),
                            'year(date_write_off)>' . (int)date("Y", strtotime($date))
                        ],
                        ['and',
                            'year(date_issue)<' . (int)date("Y", strtotime($date)),
                            'month(date_write_off)>=' . (int)date("m", strtotime($date)),
                            'year(date_write_off)=' . (int)date("Y", strtotime($date))
                        ],
                        ['and',
                            'year(date_issue)=' . (int)date("Y", strtotime($date)),
                            'month(date_issue)<=' . (int)date("m", strtotime($date)),
                            'month(date_write_off)>=' . (int)date("m", strtotime($date)),
                            'year(date_write_off)=' . (int)date("Y", strtotime($date))
                        ],
                    ]
                )
                ->having('diff_date < 0')
                ->asArray()
                ->count();
            if (!empty($count_replacement_fire_fighting_equipment)) {
                $result['count_replacement'] = $count_replacement_fire_fighting_equipment;
            } else {
                $result['count_replacement'] = 0;
            }

            /*
             * Блок по количества по наименованию
             */
            $count_ff_by_eq_title = FireFightingEquipment::find()
                ->select([
                    'fire_fighting_equipment.title as ff_eq_title',
                    'count(fire_fighting_equipment_specific.id) as count_ff_eq_spec'])
                ->innerJoin('fire_fighting_object', 'fire_fighting_equipment.id = fire_fighting_object.fire_fighting_equipment_id')
                ->innerJoin('fire_fighting_equipment_specific', 'fire_fighting_object.id = fire_fighting_equipment_specific.fire_fighting_object_id')
                ->where(['in', 'fire_fighting_object.company_department_id', $company_departments])
                ->andWhere(['or',
                        ['and',
                            'month(date_issue)=' . (int)date("m", strtotime($date)),
                            'year(date_issue)=' . (int)date("Y", strtotime($date))
                        ],

                        ['and',
                            'year(date_issue)<' . (int)date("Y", strtotime($date)),
                            'year(date_write_off)>' . (int)date("Y", strtotime($date))
                        ],
                        ['and',
                            'year(date_issue)<' . (int)date("Y", strtotime($date)),
                            'month(date_write_off)>=' . (int)date("m", strtotime($date)),
                            'year(date_write_off)=' . (int)date("Y", strtotime($date))
                        ],
                        ['and',
                            'year(date_issue)=' . (int)date("Y", strtotime($date)),
                            'month(date_issue)<=' . (int)date("m", strtotime($date)),
                            'month(date_write_off)>=' . (int)date("m", strtotime($date)),
                            'year(date_write_off)=' . (int)date("Y", strtotime($date))
                        ],
                    ]
                )
                ->andWhere(['in', 'fire_fighting_equipment_specific.status_id', [64, 65, 1]])
                ->groupBy('ff_eq_title')
                ->asArray()
                ->all();
            $result['count_by_equipment'] = array();
            if (isset($count_ff_by_eq_title) && !empty($count_ff_by_eq_title)) {
                foreach ($count_ff_by_eq_title as $ff_eq_count) {
                    $result['count_by_equipment'][$ff_eq_count['ff_eq_title']] = $ff_eq_count['count_ff_eq_spec'];
                }
            }
            if (empty($result['count_by_equipment'])) {
                $result['count_by_equipment'] = (object)array();
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetFireFightingStatistic. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetFireFightingStatistic. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод WriteOffFireFightingEquipment() - Списание средства пожарной безопасности
     * @param null $data_post - JSON  с данными: идентификатор средства пожарной безопасности которое списываем, примечание, документы
     * @return array - стнадратный массив выходных данных
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\FireFightingEquipment&method=WriteOffFireFightingEquipment&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 18.10.2019 9:34
     */
    public static function WriteOffFireFightingEquipment($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $write_off_ff_equipment = array();                                                                                // Промежуточный результирующий массив
//        $data_post = '{"fire_fighting_specific_id":20,"reason_write_off":"Захотелось","documents":{"21":{"document_id":21}}}';
        $warnings[] = 'WriteOffFireFightingEquipment. Начало метода';
        try {
            $transaction = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception('WriteOffFireFightingEquipment. Не переданы входные параметры');
            }
            $warnings[] = 'WriteOffFireFightingEquipment. Данные успешно переданы';
            $warnings[] = 'WriteOffFireFightingEquipment. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'WriteOffFireFightingEquipment. Декодировал входные параметры';
            if (!property_exists($post_dec, 'fire_fighting_specific_id') ||
                !property_exists($post_dec, 'reason_write_off') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'date_write_off') ||
                !property_exists($post_dec, 'date')
            )                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception('WriteOffFireFightingEquipment. Переданы некорректные входные параметры');
            }
            $warnings[] = 'WriteOffFireFightingEquipment. Данные с фронта получены';
            $ff_equipment = $post_dec->fire_fighting_specific_id;
            $reason_write_off = $post_dec->reason_write_off;
            $date_write_off = date('Y-m-d', strtotime($post_dec->date_write_off));

            $chosen_date = $post_dec->date;
            $month = $chosen_date->monthNumber;
            $year = $chosen_date->year;
            $days = $chosen_date->numberDays;
            $date = "$year-$month-$days";
            /**
             * Получаем список всех вложенных участков
             */
            $company_department_id = $post_dec->company_department_id;
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new \Exception('WriteOffFireFightingEquipment. Ошибка получения вложенных департаментов' . $company_department_id);
            }

            $found_ff_eq = FireFightingEquipmentSpecific::findOne(['id' => $ff_equipment]);
            if ($found_ff_eq != null) {
                $found_ff_eq->description = $reason_write_off;
                $found_ff_eq->status_id = self::WRITE_OFF_FF_EQ;
                $found_ff_eq->date_write_off = $date_write_off;
                if ($found_ff_eq->save()) {
                    $ff_eq_id = $found_ff_eq->id;
                    $warnings[] = 'WriteOffFireFightingEquipment. Списание прошло успешно';
                } else {
                    throw new \Exception('WriteOffFireFightingEquipment. Ошибка при измении данных специфики средства пожарной безопасности');
                }
                $write_off_ff_equipment['objects'][$found_ff_eq->fire_fighting_object_id]['object_id'] = $found_ff_eq->fire_fighting_object_id;
                if (isset($ff_eq_id)) {
                    $inserted_ff_eq_status = new FireFightingEquipmentSpecificStatus();
                    $inserted_ff_eq_status->fire_fighting_equipment_specific_id = $ff_eq_id;
                    $inserted_ff_eq_status->status_id = self::WRITE_OFF_FF_EQ;
                    $inserted_ff_eq_status->date_time = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));
                    if ($inserted_ff_eq_status->save()) {
                        $warnings[] = 'WriteOffFireFightingEquipment. Новый статус в таблицу статусов был успешно добавлен';
                    } else {
                        throw new \Exception('WriteOffFireFightingEquipment. Ошибка при добавлении нового статуса в таблицу статусов');
                    }
                }
            }
            $result_statistic = self::GetFireFightingStatistic($company_departments, $date);
            if ($result_statistic['status'] == 1) {
                $write_off_ff_equipment['statistic'] = $result_statistic['Items'];
                $warnings[] = $result_statistic['warnings'];
            } else {
                $errors[] = $result_statistic['errors'];
                $warnings[] = $result_statistic['warnings'];
                throw new \Exception('WriteOffFireFightingEquipment. Возникла ошибка при получении статистики');
            }
            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            $errors[] = 'WriteOffFireFightingEquipment. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'WriteOffFireFightingEquipment. Конец метода';
        return array('Items' => $write_off_ff_equipment, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetFireFightingBySpecific() - Метод получения данных для выпадающего списка в модальном окне "Техническое обслуживание"
     * @param null $data_post - JSON с данными - идентификатор участка, идентификатор места
     * @return array - массив выходных данных со структурой: [fire_fighting_equipment_specific_id]
     *                                                                      fire_fighting_equipment_specific_id:
     *                                                                      fire_fighting_equipment_title:
     *                                                                      fire_fighting_equipment_specific_inventory_number:
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\FireFightingEquipment&method=GetFireFightingBySpecific&subscribe=&data={%22company_department_id%22:501,%22place_id%22:6181}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.10.2019 15:52
     */
    public static function GetFireFightingBySpecific($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetFireFightingByObjects. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception('SaveFireFightingEquipmentSpecific. Не переданы входные параметры');
            }
            $warnings[] = 'SaveFireFightingEquipmentSpecific. Данные успешно переданы';
            $warnings[] = 'SaveFireFightingEquipmentSpecific. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'SaveFireFightingEquipmentSpecific. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'place_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception('SaveFireFightingEquipmentSpecific. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveFireFightingEquipmentSpecific. Данные с фронта получены';
            $place_id = $post_dec->place_id;
            $company_department_id = $post_dec->company_department_id;
            /*
             * Получение всех объектов по участку и месту
             */
            $fire_fighting_objects = FireFightingObject::find()
                ->innerJoinWith('fireFightingEquipment')
                ->innerJoinWith(['fireFightingEquipmentSpecifics' => function ($q) {
                    $q->where(['in', 'fire_fighting_equipment_specific.status_id', [64, 65]]);
                }])
                ->where(['place_id' => $place_id, 'company_department_id' => $company_department_id])
                ->all();
            /*
             * Формируется структура со списком наименование оборудования для выпадашки модального окна "Техническое обслуживание"
             */
            foreach ($fire_fighting_objects as $fire_fighting_object) {
                foreach ($fire_fighting_object->fireFightingEquipmentSpecifics as $fireFightingEquipmentSpecific) {
                    $result[$fireFightingEquipmentSpecific->id]['fire_fighting_equipment_specific_id'] = $fireFightingEquipmentSpecific->id;
                    $result[$fireFightingEquipmentSpecific->id]['fire_fighting_equipment_title'] = $fire_fighting_object->fireFightingEquipment->title;
                    $result[$fireFightingEquipmentSpecific->id]['fire_fighting_equipment_specific_inventory_number'] = $fireFightingEquipmentSpecific->inventory_number;
                    $result[$fireFightingEquipmentSpecific->id]['fire_fighting_equipment_specific_date_write_off'] = $fireFightingEquipmentSpecific->date_write_off;
                }
            }

        } catch (Throwable $exception) {
            $errors[] = 'GetFireFightingByObjects. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetFireFightingByObjects. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод MaintenanceFireFightingEquipment() - Техническое обслуживание средства пожарной безопасности
     * @param null $data_post - JSON с входными данными: идентификатор специфичного оборудования пожарной безопасности
     *                                                   дата когда началася новый срок экспутации
     *                                                   новая дата списания
     *                                                   примечание
     *                                                   документы
     * @return array - стандартный массив выходных данных
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example amicum/read-manager-amicum?controller=industrial_safety\FireFightingEquipment&method=MaintenanceFireFightingEquipment&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 18.10.2019 11:53
     */
    public static function MaintenanceFireFightingEquipment($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $maintenance = array();                                                                                // Промежуточный результирующий массив
        $session = Yii::$app->session;
        $method_name = 'MaintenanceFireFightingEquipment';
        $warnings[] = 'MaintenanceFireFightingEquipment. Начало метода';
//        $data_post = '{"fire_fighting_equipment_specific_id":32,"date_issue":"2019-11-05","date_write_off":"2020-11-05","description":"ASDASASDADS","document":{"document_path":"/img/attachment/111_08-10-2019 15-44-17.1570524257.oxps","document_title":"111","document_type":"oxps","document_blob":null,"document_status":""}}';
        try {
            $transaction = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception('MaintenanceFireFightingEquipment. Не переданы  входные параметры');
            }
            $warnings[] = 'MaintenanceFireFightingEquipment. Данные успешно переданы';
            $warnings[] = 'MaintenanceFireFightingEquipment. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'MaintenanceFireFightingEquipment. Декодировал входные параметры';
            if (!property_exists($post_dec, 'fire_fighting_equipment_specific_id') ||
                !property_exists($post_dec, 'date_issue') ||
                !property_exists($post_dec, 'wear_period') ||
                !property_exists($post_dec, 'date_write_off') ||
                !property_exists($post_dec, 'description') ||
                !property_exists($post_dec, 'documents'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception('MaintenanceFireFightingEquipment. Переданы некорректные входные параметры');
            }
            $warnings[] = 'MaintenanceFireFightingEquipment. Данные с фронта получены';
            $fire_fighting_equipment_specific_id = $post_dec->fire_fighting_equipment_specific_id;
            $wear_period = $post_dec->wear_period;
            if ($post_dec->date_write_off == null) {
                $date_write_off = null;
            } else {
                $date_write_off = date('Y-m-d', strtotime($post_dec->date_write_off));
            }
            $date_issue = date('Y-m-d', strtotime($post_dec->date_issue));
            $description = $post_dec->description;
            $documents = $post_dec->documents;
            $ff_eq_spec = FireFightingEquipmentSpecific::findOne(['id' => $fire_fighting_equipment_specific_id]);
            if ($ff_eq_spec != null) {
                $ff_eq_spec->date_issue = date('Y-m-d', strtotime($date_issue));
                $ff_eq_spec->date_write_off = $date_write_off;
                $ff_eq_spec->description = $description;
                $ff_eq_spec->status_id = self::MAINTENCNCE_FF_EQ_SPEC;
                $ff_eq_spec->wear_period = $wear_period;
                if ($ff_eq_spec->save()) {
                    $ff_eq_spec_id = $ff_eq_spec->id;
                    $warnings[] = 'MaintenanceFireFightingEquipment. Данные по техническому обслуживанию успешно сохранены';
                } else {
                    throw new \Exception('MaintenanceFireFightingEquipment. Ошибка при сохранении данных по техническому обслуживания средства');
                }

                if (!empty($documents)) {
//                    $del_ff_attachment = FireFightingEquipmentDocuments::deleteAll(['fire_fighting_equipment_specific_id' => $ff_eq_spec_id]);
                    foreach ($documents as $document) {
                        if ($document->document_status == 'new') {
                            if ($document->document_id <= 0) {
                                $nomalize_path = Assistant::UploadFile($document->document_blob, $document->document_title, 'attachment', $document->document_type);
                                $add_attachment = new Attachment();
                                $add_attachment->attachment_type = $document->document_type;
                                $add_attachment->title = $document->document_title;
                                $add_attachment->path = $nomalize_path;
                                $add_attachment->date = BackendAssistant::GetDateFormatYMD();
                                $add_attachment->worker_id = $session['worker_id'];
                                $add_attachment->section_title = 'ОТ и ПБ/Контроль наличия средств пожарной безопасности';
                                if ($add_attachment->save()) {
                                    $warnings[] = 'WriteOffFireFightingEquipment. Новое вложение успешно сохранено';
                                    $add_attachment->refresh();
                                    $maintenance['documents'][$add_attachment->id]['document_id'] = $add_attachment->id;
                                    $maintenance['documents'][$add_attachment->id]['document_title'] = $document->document_title;
                                    $maintenance['documents'][$add_attachment->id]['document_type'] = $document->document_type;
                                    $maintenance['documents'][$add_attachment->id]['document_path'] = $add_attachment->path;
                                    $maintenance['documents'][$add_attachment->id]['document_blob'] = null;
                                    $maintenance['documents'][$add_attachment->id]['document_status'] = '';
                                    $ff_eq_docs[] = [$ff_eq_spec_id, $add_attachment->id];
                                } else {
                                    $errors[] = $add_attachment->errors;
                                    throw new \Exception('WriteOffFireFightingEquipment. Ошибка при сохранении вложения');
                                }
                            }
                        } elseif ($document->document_status == 'del') {
                            FireFightingEquipmentDocuments::deleteAll(['fire_fighting_equipment_specific_id' => $ff_eq_spec_id]);
                        }
                    }
                    if (isset($ff_eq_docs) && !empty($ff_eq_docs)) {
                        $ff_eq_doc_inserted = Yii::$app->db->createCommand()
                            ->batchInsert('fire_fighting_equipment_documents', ['fire_fighting_equipment_specific_id', 'attachment_id'], $ff_eq_docs)
                            ->execute();
                        if ($ff_eq_doc_inserted != 0) {
                            $warnings[] = $method_name . '. Связка СПБ и вложения успешно установлена';
                        } else {
                            throw new \Exception($method_name . '. Ошибка связки СПБ и вложения');
                        }
                    }
                }
                if (isset($ff_eq_spec_id)) {
                    $inserted_ff_eq_status = new FireFightingEquipmentSpecificStatus();
                    $inserted_ff_eq_status->fire_fighting_equipment_specific_id = $ff_eq_spec_id;
                    $inserted_ff_eq_status->status_id = self::MAINTENCNCE_FF_EQ_SPEC;
                    $inserted_ff_eq_status->date_time = date('Y-m-d', strtotime($date_issue));
                    if ($inserted_ff_eq_status->save()) {
                        $warnings[] = 'WriteOffFireFightingEquipment. Новый статус в таблицу статусов был успешно добавлен';
                    } else {
                        throw new \Exception('WriteOffFireFightingEquipment. Ошибка при добавлении нового статуса в таблицу статусов');
                    }
                }
                $ff_eq_spec->refresh();

                $maintenance['fire_fighting_equipment_specific_id'] = $ff_eq_spec->id;
                $maintenance['inventory_number'] = $ff_eq_spec->inventory_number;
                $maintenance['wear_period'] = $ff_eq_spec->wear_period;
                $maintenance['date_issue'] = $ff_eq_spec->date_issue;
                $maintenance['date_issue_formated'] = date('d.m.Y', strtotime($ff_eq_spec->date_issue));
                $maintenance['date_write_off'] = $ff_eq_spec->date_write_off;
                if ($ff_eq_spec->date_write_off == null) {
                    $date_write_off_format = null;
                } else {
                    $date_write_off_format = date('d.m.Y', strtotime($ff_eq_spec->date_write_off));
                }
                $maintenance['date_write_off_formated'] = $date_write_off_format;
                $maintenance['description'] = $ff_eq_spec->description;
                $maintenance['status_id'] = $ff_eq_spec->status_id;

            }
            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            $errors[] = 'MaintenanceFireFightingEquipment. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'MaintenanceFireFightingEquipment. Конец метода';
        $result = $maintenance;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeleteFireFightingSpecific() - Метод удаления конкретного средства пожарной безопасности
     * @param null $data_post - JSON с идентификатором средства пожарной безопасности
     * @return array - стандартный масси выходных данных
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example amicum/read-manager-amicum?controller=industrial_safety\FireFightingEquipment&method=DeleteFireFightingSpecific&subscribe=&data={"fire_fighting_equipment_specific_id":136}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 12.11.2019 9:50
     */
    public static function DeleteFireFightingSpecific($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $del_specific = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'DeleteFireFightingSpecific. Начало метода';
        try {
            $transaction = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception('DeleteFireFightingSpecific. Не переданы входные параметры');
            }
            $warnings[] = 'DeleteFireFightingSpecific. Данные успешно переданы';
            $warnings[] = 'DeleteFireFightingSpecific. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'DeleteFireFightingSpecific. Декодировал входные параметры';
            if (!property_exists($post_dec, 'fire_fighting_equipment_specific_id') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'date'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception('DeleteFireFightingSpecific. Переданы некорректные входные параметры');
            }
            $warnings[] = 'DeleteFireFightingSpecific. Данные с фронта получены';
            $fire_fighting_equipment_specific_id = $post_dec->fire_fighting_equipment_specific_id;
            /**
             * Получаем список всех вложенных участков
             */
            $company_department_id = $post_dec->company_department_id;
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new \Exception('DeleteFireFightingSpecific. Ошибка получения вложенных департаментов' . $company_department_id);
            }
            $chosen_date = $post_dec->date;
            $month = $chosen_date->monthNumber;
            $year = $chosen_date->year;
            $days = $chosen_date->numberDays;
            $date = "$year-$month-$days";
            $get_ff_eq_spec = FireFightingEquipmentSpecific::findOne(['id' => $fire_fighting_equipment_specific_id]);
            if ($get_ff_eq_spec != null) {
                $del_specific['objects'][$get_ff_eq_spec->fire_fighting_object_id]['object_id'] = $get_ff_eq_spec->fire_fighting_object_id;

                if ($get_ff_eq_spec->delete() != false) {
                    $warnings[] = 'DeleteFireFightingSpecific. Удаление средства пожарной безопасности прошло успешно';
                } else {
                    $errors[] = $get_ff_eq_spec->errors;
                    throw new \Exception('DeleteFireFightingSpecific. Произошла ошибка при удалении средства пожарной безопасности');
                }
            }
            $result_statistic = self::GetFireFightingStatistic($company_departments, $date);
            if ($result_statistic['status'] == 1) {
                $del_specific['statistic'] = $result_statistic['Items'];
                $warnings[] = $result_statistic['warnings'];
            } else {
                $errors[] = $result_statistic['errors'];
                $warnings[] = $result_statistic['warnings'];
                throw new \Exception('DeleteFireFightingSpecific. Возникла ошибка при получении статистики');
            }
            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            $errors[] = 'DeleteFireFightingSpecific. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'DeleteFireFightingSpecific. Конец метода';
        $result = $del_specific;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeleteFireFighting() - Метод удаления средства пожарной безопасности
     * @param null $data_post - JSON с идентификатором средства пожарной безопасности
     * @return array - стандартный масси выходных данных
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example amicum/read-manager-amicum?controller=industrial_safety\FireFightingEquipment&method=DeleteFireFighting&subscribe=&data={"fire_fighting_equipment_specific_id":136}
     */
    public static function DeleteFireFighting($data_post = NULL)
    {

        $result = array();                                                                                              // Массив результирующий
        $log = new LogAmicumFront("DeleteFireFighting");
        try {
            $log->addLog("Начал метод");
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            if (!property_exists($post_dec, 'fire_fighting_object_id') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'date'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('Переданы некорректные входные параметры');
            }
            $fire_fighting_object_id = $post_dec->fire_fighting_object_id;
            /**
             * Получаем список всех вложенных участков
             */
            $company_department_id = $post_dec->company_department_id;
            $response = DepartmentController::FindDepartment($company_department_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения вложенных департаментов' . $company_department_id);
            }
            $company_departments = $response['Items'];
            $chosen_date = $post_dec->date;
            $month = $chosen_date->monthNumber;
            $year = $chosen_date->year;
            $days = $chosen_date->numberDays;
            $date = "$year-$month-$days";

            FireFightingObject::deleteAll(['id' => $fire_fighting_object_id]);

            $result_statistic = self::GetFireFightingStatistic($company_departments, $date);
            $log->addLogAll($response);
            if ($result_statistic['status'] != 1) {
                throw new Exception('Возникла ошибка при получении статистики');
            }
            $del_specific['statistic'] = $result_statistic['Items'];
            $result = $del_specific;
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Окончил метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод SaveFireFightingEquipment() - Сохранение средства пожарной безопасности в справочник
     * @param null $data_post - JSON с наименованием средства пожарной безопасности, единица измерения
     * @return array - стандартный массив выходных данных
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example amicum/read-manager-amicum?controller=industrial_safety\FireFightingEquipment&method=SaveFireFightingEquipment&subscribe=&data={"fire_fighting_equipment_title":"УАПП","unit_id":9}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 12.11.2019 10:05
     */
    public static function SaveFireFightingEquipment($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $result = array();                                                                                            // Массив предупреждений
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'SaveFireFightingEquipment. Начало метода';
        try {
            $transaction = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception('SaveFireFightingEquipment. Не переданы входные параметры');
            }
            $warnings[] = 'SaveFireFightingEquipment. Данные успешно переданы';
            $warnings[] = 'SaveFireFightingEquipment. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'SaveFireFightingEquipment. Декодировал входные параметры';
            if (!property_exists($post_dec, 'fire_fighting_equipment_title') ||
                !property_exists($post_dec, 'unit_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception('SaveFireFightingEquipment. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveFireFightingEquipment. Данные с фронта получены';
            $fire_fighting_equipment_title = $post_dec->fire_fighting_equipment_title;
            $unit_id = $post_dec->unit_id;
            $add_specific_eq = new FireFightingEquipment();
            $add_specific_eq->title = $fire_fighting_equipment_title;
            $add_specific_eq->unit_id = $unit_id;
            if ($add_specific_eq->save()) {
                $warnings[] = 'SaveFireFightingEquipment. Средство пожрной безпоасности успешно добавлено в справочник';
                $add_specific_eq->refresh();
                $post_dec->fire_fighting_equipment_id = $add_specific_eq->id;
            } else {
                $errors[] = $add_specific_eq->errors;
                throw new \Exception('SaveFireFightingEquipment. Ошибка при добавлении средства пожарной безопасности в справочник');
            }
            $transaction->commit();
            $result = $post_dec;
        } catch (Throwable $exception) {
            $transaction->rollBack();
            $errors[] = 'SaveFireFightingEquipment. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SaveFireFightingEquipment. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }
}
