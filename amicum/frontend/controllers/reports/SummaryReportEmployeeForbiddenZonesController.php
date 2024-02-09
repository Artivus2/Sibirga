<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\reports;


use backend\controllers\LogAmicum;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\handbooks\HandbookDepartmentController;
use frontend\models\Department;
use frontend\models\Place;
use frontend\models\SummaryReportForbiddenZonesResult;
use Yii;
use yii\db\Query;
use yii\web\Response;

//отчет: Нахождение персонала в запрещенных зонах
class SummaryReportEmployeeForbiddenZonesController extends \yii\web\Controller
{
    /*************************** Таблицы для синхронизации **********************************/
    public static $sync_view_name = 'view_summary_report_employee_forbidden_zones_sync';
    public static $sync_view_rows = 'temp_table_id, date_work, date_time_work, FIO, type_worker_title, department_title, company_title, place_title, smena, worker_id, type_place_title, kind_place_title, place_status_title, type_place_id, kind_place_id, department_id, place_status_id';
    public static $sync_table_name = 'summary_report_time_spent';
    public static $sync_temp_table_name = 'worker_parameter_value_temp';
    public static $sync_table_rows = 'date_work, date_time_work, FIO, type_worker_title, department_title, company_title, place_title, smena, worker_id, type_place_title, kind_place_title, place_status_title, type_place_id, kind_place_id, department_id, place_status_id';

    public function actionIndex()
    {
        $placeList = Place::find()
            ->select(['title', 'id'])
            ->orderBy('title ASC')
            ->asArray()->all();
        $departmentList = Department::find()
            ->select(['title', 'id'])
            ->orderBy(['title' => SORT_ASC])
            ->asArray()->all();

        return $this->render('index', [
            'placeList' => $placeList,
            'departmentList' => $departmentList
        ]);
    }

    public function actionResult()
    {
//        ini_set('max_execution_time', 600);
//        ini_set('memory_limit', '2000M');
        $post = Assistant::GetServerMethod();
        //$post = Yii::$app->request->post();                                                                             //метод принимает данные из модели для фильтрации запроса.
        $sql_filter = '';                                                                                               //фильтр запроса, т.к. данных в запросе много, то по умолчанию возвращется только данные за текущие сутки,
        $date_time = date('Y-m-d');
        $syn_table = array();
        //фильтр по дате если дата не задана то берутся текущие сутки, если задана, то берутся из метода пост
        if (!isset($post['dateStart']) || !isset($post['dateFinish'])) {
            $sql_filter .= ' DATE(date_work) = "' . date('Y-m-d') . '"';
            $data_search_start = date('Y-m-d');
            $data_search_end = date('Y-m-d');
            $date_time = date('Y-m-d');
        } else {
            if ($post['dateStart'] == $post['dateFinish']) {
                $sql_filter .= 'DATE(date_work) = "' . date('Y-m-d', strtotime($post['dateStart'])) . '"';
                $data_search_start = date('Y-m-d', strtotime($post['dateStart']));
                $data_search_end = date('Y-m-d', strtotime($post['dateStart']));
                $date_time = $post['dateStart'];
            } else if ($post['dateStart'] < $post['dateFinish']) {
                $date_time = $post['dateStart'];
                $sql_filter .= ' date_work >= "' . date('Y-m-d H:i:s', strtotime($post['dateStart'] . ' -1 day')) . '" AND date_work <= "' . date('Y-m-d H:i:s', strtotime($post['dateFinish'] . ' +1 day')) . '" ';
                $data_search_start = date('Y-m-d', strtotime($post['dateStart']));
                $data_search_end = date('Y-m-d', strtotime($post['dateFinish']));
            } else if ($post['dateStart'] > $post['dateFinish']) {
                $date_time = $post['dateFinish'];
                $sql_filter .= ' date_work >= "' . date('Y-m-d H:i:s', strtotime($post['dateFinish'] . ' -1 day')) . '" AND date_work <= "' . date('Y-m-d H:i:s', strtotime($post['dateStart'] . ' +1 day')) . '" ';
                $data_search_start = date('Y-m-d', strtotime($post['dateFinish']));
                $data_search_end = date('Y-m-d', strtotime($post['dateStart']));
            }
        }

        //фильтр по подразделению
        if ($post['department_id'] != null) $sql_filter .= ' AND department_id="' . $post['department_id'] . '"';

        //фильтр по месту
        if ($post['place_id'] != null) $sql_filter .= ' AND place_id="' . $post['place_id'] . '"';

        if ($post['search'] != null) {
            $sql_filter .= ' AND (' . 'date_work like "%' . $post['search'] . '%"';
            $sql_filter .= ' OR  ' . 'name like "%' . $post['search'] . '%"';
            $sql_filter .= ' OR  ' . 'tabel_number like "%' . $post['search'] . '%"';
            $sql_filter .= ' OR ' . 'department_title like "%' . $post['search'] . '%"';
            $sql_filter .= ' OR ' . 'company_title like "%' . $post['search'] . '%"';
            $sql_filter .= ' OR ' . 'place_title like "%' . $post['search'] . '%"';
            $sql_filter .= ' OR ' . 'duration like "%' . $post['search'] . '%")';
        }

        isset($post['data_exists']) ? $data_exists = $post['data_exists'] : $data_exists = 0;
        $query = new Query();
        $limit = 10000;
        $limit_start = 0;                                                                                               // начало лимита, то есть откуда начинать
        $recall = -1;                                                                                                   // флаг используется для того, чтобы на фронт отправить флаг на то что есть данные больше чем указанного лиимита, и чтоб обратно отправил запрос

        /******** ПРОВЕРЯЕМ, ЕСТЬ ЛИ ЗАПРОС СО ФРОНТА НА ПЛУЧЕНИЯ ОСТАВШИХСЯ ДАННЫХ **********/
        if (isset($post['start_position']) && $post['start_position'] != '') {
            $limit_start = $post['start_position'];
        }

        /*******  Проверяем количество записей которые есть в таблицею. Если количество больше указанного лимита, то  отправим фронту флаг на обратный запрос   */
        $worker_motion_less_row_count = $query->select('COUNT(id) as count')->from('summary_report_forbidden_zones_result')->where($sql_filter)->one();
        $worker_motion_less_row_count = $worker_motion_less_row_count['count'];
        if ($worker_motion_less_row_count > $limit_start && $worker_motion_less_row_count > $limit)                     // если данных больше че муказанного лимита, то отправим фронту флаг, что есть еще данные и чтоб он отправил запрос обратно
        {
            $recall = 1;
        }
        $worker_collections = (new Query())                                                                             //Запрос напрямую из базы по вьюшке view_personal_areas
        ->select([                                                                                                       //Обязательно сортируем по порядку
            'date_work',
            'name',
            'tabel_number',
            'department_title',
            'company_title',
            'place_title',
            'duration'
        ])
            ->from('summary_report_forbidden_zones_result')
            ->orderBy([
                'id' => SORT_DESC
            ])
            ->where($sql_filter)
            ->limit($limit)
            ->offset($limit_start)
            ->all();

        $limit_start += $limit;                                                                                         // перемещаем начальную позицию для поиска, то есть начало лимита обновляем (полсе получения со фронта нужно прибавить к начало выборки лимит), то есть если тек начало лимита 50, то 50+ лимит, и со следующего раза выборка будет с 550 до limit
        $errors = array();

        $model = array();
        $j = 0;
        //поиск по страничке
        if (isset($post['search']) && $post['search'] != '') {
            $search_title = $post['search'];
            foreach ($worker_collections as $worker) {
                $model[$j]['date_work'] = Assistant::MarkSearched($search_title, date('d.m.Y', strtotime($worker['date_work'])));
                $model[$j]['name'] = Assistant::MarkSearched($search_title, $worker['name']);
                $model[$j]['tabel_number'] = Assistant::MarkSearched($search_title, $worker['tabel_number']);
                $model[$j]['department_title'] = Assistant::MarkSearched($search_title, $worker['department_title']);
                $model[$j]['company_title'] = Assistant::MarkSearched($search_title, $worker['company_title']);
                $model[$j]['place_title'] = Assistant::MarkSearched($search_title, $worker['place_title']);
                $model[$j]['duration'] = Assistant::MarkSearched($search_title, $worker['duration']);
                $j++;
            }
        } else {
            foreach ($worker_collections as $worker) {
                $model[$j]['date_work'] = date('d.m.Y', strtotime($worker['date_work']));
                $model[$j]['name'] = $worker['name'];
                $model[$j]['tabel_number'] = $worker['tabel_number'];
                $model[$j]['department_title'] = $worker['department_title'];
                $model[$j]['company_title'] = $worker['company_title'];
                $model[$j]['place_title'] = $worker['place_title'];
                $model[$j]['duration'] = $worker['duration'];
                $j++;
            }
        }

        if ($recall == 1 || $data_exists != 0)                                                                       // Если флаг запроса на получение данных с фронтенд положительный или данные ранее добавлялись в таблицу ошибку не отображаем
            $errors = array();
        elseif ($limit >= $limit_start && $recall == -1 && count($model) <= 0) // Иначе если id последнего выбранного меньше ограничение на выборку и флаг запроса к фронтенду отрицателен выводим ошибку
            $errors[] = 'Нет данных по заданному условию';

        $result = array('result' => $model, 'errors' => $errors, 'recall' => $recall, 'start_position' => $limit_start,
            'syn-report' => $syn_table, 'record_count' => $worker_motion_less_row_count, 'data_exists' => $data_exists);
        unset($model);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;                                                                                         //текущий воркер ИД для вычисления времени нахождения в зоне
    }

    /**
     * Метод для заполнения отчётной таблицы summary_report_forbidden_zones
     * сформированными данными из summary_report_forbidden_zones_result
     * @throws Exception
     */
    public static function CreateReportForbiddenZonesTableData()
    {
//        ini_set('max_execution_time', 600);
//        ini_set('memory_limit', "10500M");
        // Стартовая отладочная информация
        $method_name = 'CreateReportForbiddenZonesTableData';                                                                          // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                           // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        $count_add = 0;

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
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            // запись в БД начала выполнения скрипта
            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                           // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            $max_date_time_result = SummaryReportForbiddenZonesResult::find()->max('date_work');                     //получение максимального номера синхронизации
            if (!$max_date_time_result) {
                $max_date_time_result = date("Y-m-d", strtotime(\backend\controllers\Assistant::GetDateTimeNow() . '-1 days'));
            }

            $warnings[] = "CreateReportForbiddenZonesTableData.Максимальная дата в назначении: " . $max_date_time_result;

            $raw_report_data = (new Query())
                ->select([                                                                                                       //Обязательно сортируем по порядку
                    'summary_report_forbidden_zones.id as id',
                    'summary_report_forbidden_zones.main_id as main_id',
                    'summary_report_forbidden_zones.main_title as main_title',
                    'summary_report_forbidden_zones.table_name as table_name',
                    'summary_report_forbidden_zones.date_work as date_work',
                    'summary_report_forbidden_zones.duration as duration',
                    'summary_report_forbidden_zones.place_id as place_id',
                    'place.title as place_title'
                ])
                ->from('summary_report_forbidden_zones')
                ->innerJoin('place', 'place.id = summary_report_forbidden_zones.place_id')
                ->where("summary_report_forbidden_zones.date_work>'" . $max_date_time_result . "'")
                ->orderBy([
                    'summary_report_forbidden_zones.id' => SORT_DESC
                ])
                ->all();

            $warnings[] = "CreateReportForbiddenZonesTableData. Количество полученных записей: " . count($raw_report_data);

            if (!$raw_report_data) {
                throw new Exception("CreateReportForbiddenZonesTableData. Нет данных для обработки");
            }

//            $max_idx_report_forbidden_zones = $raw_report_data[0]['id'];

            $insert_string_data = '(date_work, name, tabel_number, department_id, department_title, company_title, place_id, place_title, duration)';
            $sql_query = 'INSERT INTO summary_report_forbidden_zones_result' . $insert_string_data . ' VALUES';


            foreach ($raw_report_data as $raw_record) {
                $count_all++;
                $insert_string = '(';
                $insert_string .= "'" . $raw_record['date_work'] . "',";

                if ($raw_record['table_name'] === 'worker_object') {

                    $worker_info = (new Query())
                        ->select([
                            'tabel_number',
                            'department_id',
                            'department_title',
                            'position_title',
                            'company_id',
                            'company_title'
                        ])
                        ->from('view_worker_employee_info')
                        ->where([
                            'worker_object_id' => $raw_record['main_id']
                        ])
                        ->one();
                    $response = HandbookDepartmentController::GetAllParentsCompanies($worker_info['company_id']);
                    if ($response['status'] == 1) {
                        $copmany_path = $response['Items'];
                    } else {
                        $copmany_path = $worker_info['company_title'];
                    }
                    $insert_string .= "'" . $raw_record['main_title'] . ' (' . $worker_info['position_title'] . ")',";
                    $insert_string .= "'" . $worker_info['tabel_number'] . "',";
                    $insert_string .= "'" . $worker_info['company_id'] . "',";
                    $insert_string .= "'" . $worker_info['company_title'] . "',";
                    $insert_string .= "'" . $copmany_path . "',";

                } else {
                    $insert_string .= "'" . $raw_record['main_title'] . "',";
                    $insert_string .= 'NULL,NULL,NULL,NULL,';
                }

                $insert_string .= "'" . $raw_record['place_id'] . "',";
                $insert_string .= "'" . $raw_record['place_title'] . "',";
                $insert_string .= "'" . $raw_record['duration'] . "'),";

                $sql_query .= $insert_string;

            }

            $warnings[] = "CreateReportForbiddenZonesTableData. Количество обработанных записей: " . $count_all;
            $sql_query = substr($sql_query, 0, -1);

            $warnings[] = "CreateReportForbiddenZonesTableData. sql_query: " . $sql_query;

            Yii::$app->db->createCommand($sql_query)->execute();

//            Yii::$app->db->createCommand()
//                ->delete('summary_report_forbidden_zones', 'id <= ' . $max_idx_report_forbidden_zones)
//                ->execute();

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
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Exception $exception) {
            $errors[] = 'CreateReportForbiddenZonesTableData. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время окончания выполнения метода
        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        return array('Items' => $result, 'status' => $status, 'debug' => $debug, 'errors' => $errors, 'warnings' => $warnings);
    }
}
