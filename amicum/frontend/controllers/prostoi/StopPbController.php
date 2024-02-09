<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\prostoi;


use Exception;
use frontend\controllers\handbooks\DepartmentController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\StopPb;
use frontend\models\StopPbEquipment;
use frontend\models\StopPbEvent;
use Throwable;
use yii\db\Query;

class StopPbController extends \yii\web\Controller
{
    // getStopPB                    - Получение списка простоев
    // delStopPB                    - удалить простой
    // saveStopPB                   - сохранить простой


    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод getStopPB() - Получение списка простоев
     * @param null $data_post
     * $date_start                              - дата начала простоя
     * $date_end                                - дата окончания простоя
     * $company_department_id                   - департамент по которому получаем простои с учетом вложенности
     *
     * @return array
     *          downtimeAccStatistic:
     *              {kind_stop_pb_id}
     *                  kind_stop_pb_id                                                                                         // ключ вида простоя
     *                  kind_stop_pb_title                                                                                      // наименование вида простоя
     *                  count_kind_stop_id                                                                                      // количество видов простоев
     *         downtimeAcc: {                                                                                   // список простоев
     *              {stop_pb_id}
     *                  stop_pb_id: null                                                                                        // ключ простоя
     *                  company_department_id: null,                                                                            // ID департамента
     *                  kind_stop_pb_id: null,                                                                                  // ключ типа простоя
     *                  kind_stop_pb_title: ""                                                                                  // наименование типа простоя
     *                  place_id: null,                                                                                         // место простоя / объект
     *                  date_time_start: new Date().toLocaleDateString('ru-RU'),                                                // дата начала простоя
     *                  date_time_start_format                                                                                  // дата начала простоя формотированная
     *                  date_time_end: new Date().toLocaleDateString('ru-RU'),                                                  // дата окончания простоя (может быть Null - до устранения)
     *                  date_time_end_format                                                                                    // дата окончания простоя формотированная
     *                  events: {},                                                                                             // причины простоя
     *                      {event_id}
     *                          event_id
     *                          event_title
     *                  equipments: {},                                                                                         // оборудование
     *                      {equipment_id}
     *                          equipment_id
     *                          equipment_title
     *                  description: '',                                                                                        // комментарий
     *                  injunction_violation_id: null,                                                                          // предписание / нарушение
     *                  worker_id: null,                                                                                        // кто создал
     *                  type_operation_id: 8,                                                                                   // тип операции (по-умолчанию: 8 - Простой )
     *                  xyz: "0,0,0"                                                                                            // координаты простоя( оборудования ?)
     * @package frontend\controllers\prostoi
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=prostoi\StopPb&method=getStopPb&subscribe=&data={"company_department_id":20028766,"date_start":"2020-01-01","date_end":"2020-01-31"}
     *
     * @author Якимов М.Н,
     * Created date: on 30.12.2019 9:19
     */
    public static function getStopPB($data_post = NULL)
    {
        $log = new LogAmicumFront("getStopPB");
        $result = array();                                                                                        // Промежуточный результирующий массив
        try {
            $log->addLog("Начал выполнение метода");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            if (!property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'date_start') ||
                !property_exists($post_dec, 'date_end'))                                                            // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $log->addLog("Данные с фронта получены");

            $company_department_id = $post_dec->company_department_id;
            $date_start = $post_dec->date_start;
            $date_end = $post_dec->date_end;

            $response = DepartmentController::FindDepartment($company_department_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения вложенных департаментов' . $company_department_id);
            }

            $company_departments = $response['Items'];

            // получение статистики по простоям
            $count_kind_stop_pb = (new Query ())
                ->select('kind_stop_pb_id, kind_stop_pb.title as kind_stop_pb_title, count(stop_pb.id) as count_kind_stop_id')
                ->from('stop_pb')
                ->innerJoin('kind_stop_pb', 'kind_stop_pb.id = stop_pb.kind_stop_pb_id')
                ->where(['in', 'company_department_id', $company_departments])
                ->andWhere(['between', 'stop_pb.date_time_start', $date_start, $date_end])
                ->groupBy('kind_stop_pb_id, kind_stop_pb_title')
                ->indexBy('kind_stop_pb_id')
                ->all();

            if (!$count_kind_stop_pb) {
                $result['downtimeAccStatistic'] = (object)array();
            } else {
                $result['downtimeAccStatistic'] = $count_kind_stop_pb;
            }


            // получаем список простоев
            $stops = StopPb::find()
                ->joinWith('companyDepartment.company')
                ->joinWith('stopPbEvents')
                ->joinWith('stopPbEquipments')
                ->joinWith('kindStopPb')
                ->joinWith('place')
                ->joinWith('kindDuration')
                ->joinWith('stopPbEvents.event')
                ->joinWith('stopPbEquipments.equipment')
                ->where(['in', 'company_department_id', $company_departments])
                ->andWhere(['between', 'stop_pb.date_time_start', $date_start, $date_end])
                ->asArray()
                ->all();

            // обрабатываем список простоев
            foreach ($stops as $stop) {
                $stop_pb_id = $stop['id'];
                $company_department_id = $stop['company_department_id'];
                $stop_result[$company_department_id]['company_department_id'] = $company_department_id;
                $stop_result[$company_department_id]['stops'][$stop_pb_id]['stop_pb_id'] = $stop_pb_id;
                $stop_result[$company_department_id]['stops'][$stop_pb_id]['company_department_id'] = $stop['company_department_id'];
                $stop_result[$company_department_id]['stops'][$stop_pb_id]['company_department_title'] = $stop['companyDepartment']['company']['title'];
                $stop_result[$company_department_id]['stops'][$stop_pb_id]['kind_stop_pb_id'] = $stop['kind_stop_pb_id'];
                $stop_result[$company_department_id]['stops'][$stop_pb_id]['kind_stop_pb_title'] = $stop['kindStopPb']['title'];

                $stop_result[$company_department_id]['stops'][$stop_pb_id]['place_id'] = $stop['place_id'];
                $stop_result[$company_department_id]['stops'][$stop_pb_id]['place_title'] = $stop['place']['title'];
                $stop_result[$company_department_id]['stops'][$stop_pb_id]['date_time_start'] = $stop['date_time_start'];
                if ($stop['date_time_start']) {
                    $stop_result[$company_department_id]['stops'][$stop_pb_id]['date_time_start_format'] = date('d.m.Y H:i:s', strtotime($stop['date_time_start']));
                } else {
                    $stop_result[$company_department_id]['stops'][$stop_pb_id]['date_time_start_format'] = "";
                }


                $stop_result[$company_department_id]['stops'][$stop_pb_id]['date_time_end'] = $stop['date_time_end'];
                if ($stop['date_time_end']) {
                    $stop_result[$company_department_id]['stops'][$stop_pb_id]['date_time_end_format'] = date('d.m.Y H:i:s', strtotime($stop['date_time_end']));
                } else {
                    $stop_result[$company_department_id]['stops'][$stop_pb_id]['date_time_end_format'] = "";
                }
                $stop_result[$company_department_id]['stops'][$stop_pb_id]['description'] = $stop['description'];
                $stop_result[$company_department_id]['stops'][$stop_pb_id]['injunction_violation_id'] = $stop['injunction_violation_id'];
                $stop_result[$company_department_id]['stops'][$stop_pb_id]['worker_id'] = $stop['worker_id'];
                $stop_result[$company_department_id]['stops'][$stop_pb_id]['type_operation_id'] = $stop['type_operation_id'];
                $stop_result[$company_department_id]['stops'][$stop_pb_id]['xyz'] = $stop['xyz'];

                foreach ($stop['stopPbEquipments'] as $equipment_item) {
                    $stop_result[$company_department_id]['stops'][$stop_pb_id]['equipments'][$equipment_item['id']]['equipment_id'] = $equipment_item['equipment_id'];
                    $stop_result[$company_department_id]['stops'][$stop_pb_id]['equipments'][$equipment_item['id']]['equipment_title'] = $equipment_item['equipment']['title'];
                    $stop_result[$company_department_id]['stops'][$stop_pb_id]['equipments'][$equipment_item['id']]['inventory_number'] = $equipment_item['equipment']['inventory_number'];
                }

                foreach ($stop['stopPbEvents'] as $event_item) {
                    $stop_result[$company_department_id]['stops'][$stop_pb_id]['events'][$event_item['id']]['event_id'] = $event_item['event_id'];
                    $stop_result[$company_department_id]['stops'][$stop_pb_id]['events'][$event_item['id']]['event_title'] = $event_item['event']['title'];
                }
            }

            if (isset($stop_result)) {
                foreach ($stop_result as $company_department) {
                    foreach ($company_department['stops'] as $stop_result_item) {
                        if (!isset($stop_result_item['equipments'])) {
                            $stop_result[$company_department['company_department_id']]['stops'][$stop_result_item['stop_pb_id']]['equipments'] = (object)array();
                        }

                        if (!isset($stop_result_item['events'])) {
                            $stop_result[$company_department['company_department_id']]['stops'][$stop_result_item['stop_pb_id']]['events'] = (object)array();
                        }
                    }
                }
                $result['downtimeAcc'] = $stop_result;
            } else {
                $result['downtimeAcc'] = (object)array();
            }


        } catch (Exception $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * Метод delStopPB() - удалить простой
     * @param null $data_post
     * stop_pb_id                              - ключ простоя
     * @package frontend\controllers\prostoi
     * @return array
     * @example http://127.0.0.1/read-manager-amicum?controller=prostoi\StopPb&method=delStopPB&subscribe=&data={"stop_pb_id":20028766}
     *
     * @author Якимов М.Н.
     * Created date: on 30.12.2019 9:19
     */
    public static function delStopPB($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'delStopPB';
        $result = array();                                                                                        // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'stop_pb_id')
            )                                                            // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $stop_pb_id = $post_dec->stop_pb_id;

            // Удаляем простой
            $result = StopPb::deleteAll(['id' => $stop_pb_id]);

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
     * Метод saveStopPB() - сохранить простой
     * @param null $data_post
     *         downtimeAcc: {                                                                                   // шаблон простоя
     *              {stop_pb_id}
     *                  stop_pb_id: null                                                                                        // ключ простоя
     *                  company_department_id: null,                                                                            // ID департамента
     *                  kind_stop_pb_id: null,                                                                                  // ключ типа простоя
     *                  kind_stop_pb_title: ""                                                                                  // наименование типа простоя
     *                  place_id: null,                                                                                         // место простоя / объект
     *                  date_time_start: new Date().toLocaleDateString('ru-RU'),                                                // дата начала простоя
     *                  date_time_start_format                                                                                  // дата начала простоя формотированная
     *                  date_time_end: new Date().toLocaleDateString('ru-RU'),                                                  // дата окончания простоя (может быть Null - до устранения)
     *                  date_time_end_format                                                                                    // дата окончания простоя формотированная
     *                  events: {},                                                                                             // причины простоя
     *                      {event_id}
     *                          event_id
     *                          event_title
     *                  equipments: {},                                                                                         // оборудование
     *                      {equipment_id}
     *                          equipment_id
     *                          equipment_title
     *                  description: '',                                                                                        // комментарий
     *                  injunction_violation_id: null,                                                                          // предписание / нарушение
     *                  worker_id: null,                                                                                        // кто создал
     *                  type_operation_id: 8,                                                                                   // тип операции (по-умолчанию: 8 - Простой )
     *                  xyz: "0,0,0"                                                                                            // координаты простоя( оборудования ?)
     * @package frontend\controllers\prostoi
     * @return array
     * @example http://127.0.0.1/read-manager-amicum?controller=prostoi\StopPb&method=saveStopPB&subscribe=&data={"downtimeAcc":{}}
     *
     * @author Якимов М.Н.
     * Created date: on 30.12.2019 9:19
     */
    public static function saveStopPB($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'saveStopPB';
        $result = array();                                                                                        // Промежуточный результирующий массив
        $session = \Yii::$app->session;
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'downtimeAcc')
)                                                            // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $downtimeAcc = $post_dec->downtimeAcc;
            $stop_pb_id = $downtimeAcc->stop_pb_id;

            // сохраняем простой
            $save_stop = StopPb::findOne(['id' => $stop_pb_id]);

            if(!$save_stop) {
                $save_stop = new StopPb();
            }

            $save_stop->worker_id = $session['worker_id'];
            $save_stop->company_department_id = $downtimeAcc->company_department_id;
            $save_stop->xyz = $downtimeAcc->xyz;
            $save_stop->type_operation_id = $downtimeAcc->type_operation_id;
            $save_stop->description = $downtimeAcc->description;
            $save_stop->place_id = $downtimeAcc->place_id;
            $save_stop->kind_stop_pb_id = $downtimeAcc->kind_stop_pb_id;
            $save_stop->injunction_violation_id = $downtimeAcc->injunction_violation_id;
            $save_stop->date_time_start = $downtimeAcc->date_time_start;
            $save_stop->date_time_end = $downtimeAcc->date_time_end;
            if ($save_stop->save()) {
                $save_stop->refresh();
                $downtimeAcc->stop_pb_id = $save_stop->id;
            } else {
                $errors[] = $save_stop->errors;
                throw new Exception($method_name . '. Ошибка сохранения модели простоев StopPb');
            }

            // сохранение оборудования простоя
            StopPbEquipment::deleteAll(['stop_pb_id' => $stop_pb_id]);
            foreach ($downtimeAcc->equipments as $equipment_item) {
                $save_equipment = new StopPbEquipment();

                $save_equipment->stop_pb_id = $downtimeAcc->stop_pb_id;
                $save_equipment->equipment_id = $equipment_item->equipment_id;

                if ($save_equipment->save()) {
                    $save_equipment->refresh();
                } else {
                    $errors[] = $save_equipment->errors;
                    throw new Exception($method_name . '. Ошибка сохранения модели оборудования простоев StopPbEquipment');
                }
            }

            // сохранение причин простоя
            StopPbEvent::deleteAll(['stop_pb_id' => $stop_pb_id]);
            foreach ($downtimeAcc->events as $event_item) {
                $save_event = new StopPbEvent();

                $save_event->stop_pb_id = $downtimeAcc->stop_pb_id;
                $save_event->event_id = $event_item->event_id;

                if ($save_event->save()) {
                    $save_event->refresh();
                } else {
                    $errors[] = $save_event->errors;
                    throw new Exception($method_name . '. Ошибка сохранения модели причин простоев StopPbEvent');
                }
            }

            // добавление ID сотрудника в возвращаемый объект после сохранения простоя
            $downtimeAcc->worker_id = $session['worker_id'];

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result_main = array('Items' => $downtimeAcc, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

}
