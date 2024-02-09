<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\reports;

use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\handbooks\HandbookDepartmentController;
use frontend\controllers\handbooks\HandbookPlaceController;
use Throwable;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;

/**
 * Отчет: Нахождение персонала без движения (события)
 * Class SummaryReportMotionlessPeopleController
 * @package app\controllers
 */
class SummaryReportMotionlessPeopleController extends Controller
{
    // actionGetArrayForFilterDropdowns - Метод получения списков всех исходных данных для фильтра

    public static $view_name = 'view_worker_motion_less';
    public static $view_rows = 'date_work,tabel_number, FIO, value, title_department, department_id, title_place, date_time, smena';
    public static $table_name = 'worker_motion_less';
    public static $table_rows = 'date_work,tabel_number, FIO, value, title_department, department_id, title_place, date_time, smena';

    /**************************** Таблицы и представления для синхронизации ***************************/
    public static $sync_view_name = 'view_worker_motion_less_sync';
    public static $sync_view_rows = 'temp_table_id, date_work, tabel_number, fio, value, title_department, title_place, date_time, smena, department_id';
    public static $sync_table_name = 'worker_motion_less';
    public static $sync_temp_table_name = 'worker_parameter_value_temp';
    public static $sync_table_rows = 'date_work, tabel_number, fio, value, title_department, title_place, date_time, smena, department_id';


    public function actionIndex()
    {
        $handbook_deparment_controller = new HandbookDepartmentController(Yii::$app->controller->id, Yii::$app);
        $response = $handbook_deparment_controller->getCompanyListInLine();
        if($response['status']==1) {
            ArrayHelper::multisort($response['Items'], 'title', SORT_ASC);
            $department_list = $response['Items'];
        } else {
            $department_list = array();
        }
        $handbook_place_controller = new HandbookPlaceController(Yii::$app->controller->id, Yii::$app);
        $place_list = $handbook_place_controller->buildArray();
        return $this->render('index', [
            'departments' => $department_list,
            'places' => $place_list
        ]);
    }

    public function actionResult()
    {
//        ini_set('max_execution_time', 600);
//        ini_set('memory_limit', '2000M');
        $errors = array();
        $result = (object)array();
        $status = 1;
        $syn_table = array();
        $sql_filter = '';                                                                                               //Фильтр запроса, т.к. данных в запросе много, то по умолчанию возвращется только данные за текущие сутки
        $date_time_format = 'Y-m-d H:i:s';
        $limit = 100000;                                                                                                // ограничение получаемых данных
        $limit_start = 0;                                                                                               // начало лимита, то есть откуда начинать
        $recall = -1;
        try {
            // флаг используется для того, чтобы на фронт отправить флаг на то что есть данные больше чем указанного лиимита, и чтоб обратно отправил запрос
            $post = Assistant::GetServerMethod();                                                                             //Метод принимает данные из модели для фильтрации запроса.
            isset($post['data_exists']) ? $data_exists = $post['data_exists'] : $data_exists = 0;                           // Если с фронта получали data_exists запоминаем значение в переменной если не получали ставим 0

            //фильтр по дате
            if ($post['dateStart'] == null || $post['dateFinish'] == null) {
                $sql_filter .= ' date_work = CURDATE() ';
            } else {
                $formatted_date_start = date($date_time_format, strtotime($post['dateStart']));
                $formatted_date_end = date($date_time_format, strtotime($post['dateFinish']));
                if ($formatted_date_start == $formatted_date_end) {
                    $sql_filter .= 'DATE(date_work) = "' . $formatted_date_start . '"';
                } else if ($formatted_date_start > $formatted_date_end) {
                    $sql_filter .= ' date_work between "' . $formatted_date_end . '" AND "' . $formatted_date_start . '" ';
                } else if ($formatted_date_start < $formatted_date_end) {
                    $sql_filter .= ' date_work between "' . $formatted_date_start . '" AND "' . $formatted_date_end . '" ';
                }
            }

            if (isset($post['mine_id']) && $post['mine_id'] != '' && $post['mine_id'] != -1)                                // если передан еще  id шахты, то добавим в массив
            {
                $sql_filter .= " and mine_id = " . (int)$post['mine_id'];                                                    //добавляем критерий поиска по его id
            }

            //фильтр по смене
            if (isset($post['smena']) && $post['smena'] != "")
                $sql_filter .= ' AND ' . 'smena like "%' . $post['smena'] . '%"';

            //фильтр по подразделению
            if (isset($post['idDepartment']) && $post['idDepartment'] != "")
                $sql_filter .= ' AND ' . 'department_id=' . $post['idDepartment'] . '';

            //фильтр по самой строке поиска - по буквенный
            if (isset($post['search']) && $post['search'] != "") {
                $sql_filter .= ' AND (' . 'FIO like "%' . $post['search'] . '%"';
                $sql_filter .= ' OR ' . 'title_department like "%' . $post['search'] . '%"';
                $sql_filter .= ' OR ' . 'tabel_number like "%' . $post['search'] . '%"';
                $sql_filter .= ' OR  ' . 'title_place like "%' . $post['search'] . '%")';
            }

            /******** ПРОВЕРЯЕМ, ЕСТЬ ЛИ ЗАПРОС СО ФРОНТА НА ПЛУЧЕНИЯ ОСТАВШИХСЯ ДАННЫХ **********/
            if (isset($post['start_position']) && $post['start_position'] != "") {
                $limit_start = $post['start_position'];
            }

            $query = new Query();
            /*******  Проверяем количество записей которые есть в таблице. Если количество больше указанного лимита, то  отправим фронту флаг на обратный запрос   */
            $workers_row_count = $query->select('COUNT(id) as count')->from(self::$table_name)->where($sql_filter)->one();
            $workers_row_count = $workers_row_count['count'];
            if ($workers_row_count > $limit_start && $workers_row_count > $limit)                     // если данных больше чем указанного лимита, то отправим фронту флаг, что есть еще данные и чтоб он отправил запрос обратно
            {
                $recall = 1;
            }

            $worker_collections = $query->select([                                                                                                       //Обязательно сортируем по порядку // //Запрос напрямую из базы по вьюшке view_personal_areas
                'DATE_FORMAT(date_work, "%d.%m.%Y") as date_work',
                'tabel_number',
                'FIO',
                'title_department',
                'department_id',
                'title_place',
                //'DATE_FORMAT(date_time,\'%d.%m.%Y  %H:%i:%s\') AS date_time',
                'smena',
                'COUNT(tabel_number) as count'
            ])
                ->from(['worker_motion_less'])
                ->where($sql_filter)
                ->groupBy([
                    'date_work',
                    'tabel_number',
                    'FIO',
                    'title_department',
                    'department_id',
                    'title_place',
                    //'date_time',
                    'smena'
                ])
                //->having('COUNT(tabel_number)')
                ->limit($limit)
                ->offset($limit_start)
                ->all();
            $limit_start += $limit;                                                                                         // перемещаем начальную позицию для поиска, то есть начало лимита обновляем (полсе получения со фронта нужно прибавить к начало выборки лимит), то есть если тек начало лимита 50, то 50+ лимит, и со следующего раза выборка будет с 550 до limit
            if ($worker_collections) {
                $model = array();
                if (isset($post['search']) && $post['search'] != '') {
                    $search_title = $post['search'];
                    $j = 0;
                    foreach ($worker_collections as $worker) {
                        $model[$j]['date_work'] = $worker['date_work'];
                        $model[$j]['tabel_number'] = Assistant::MarkSearched($search_title, $worker['tabel_number']);
                        $model[$j]['FIO'] = Assistant::MarkSearched($search_title, $worker['FIO']);
                        $model[$j]['title_department'] = Assistant::MarkSearched($search_title, $worker['title_department']);
                        $model[$j]['department_id'] = $worker['department_id'];
                        $model[$j]['title_place'] = Assistant::MarkSearched($search_title, $worker['title_place']);
//                        $model[$j]['date_time'] = $worker['date_time'];
                        $model[$j]['smena'] = $worker['smena'];
                        $model[$j]['count'] = $worker['count'];
                        $j++;
                    }
                    $worker_collections = $model;
                }
                if ($recall == 1 || $data_exists != 0)                                                                      // Если запрос к фронтенд положительный или данные ранее добавлялись ошибку не отправлять
                    $errors = array();
                else if ($limit >= $limit_start && $recall == -1 && count($worker_collections) <= 0)                        // Иначе если id последнего выбранного меньше ограничения выборки и флаг запроса к фронтенду отрицателен и ничего не выводится в таблицу отображаем ошибку
                    $errors[] = 'Нет данных по заданному условию';
            }
            $result = array('result' => $worker_collections, 'errors' => $errors, 'recall' => $recall, 'start_position' => $limit_start,
                'syn-report' => $syn_table, 'record_count' => $workers_row_count, 'data_exists' => $data_exists, 'status' => $status);
            unset($personal_collections);
        } catch (Exception $e) {
            $status = 0;
            $errors[] = "SummaryReportMotionlessPeopleController::actionResult. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $result['status'] = $status;

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    public static function actionWorker()
    {
        $worker_motion_less = (new Query())
            ->select('date_work, tabel_number, fio, value, title_department, title_place, date_time, smena, department_id, unmotion_time')
            ->from('worker_motion_less')
            ->where('date_work > "2018-11-23 06:14:51"')
            ->orderBy(['tabel_number' => SORT_ASC, 'date_work' => SORT_ASC])
            ->limit(1)
            ->all();

        $workers = array();
        $sql_insert_query = "INSERT INTO worker_motion_less (date_work, tabel_number, fio, value, title_department, title_place, date_time, smena, department_id, unmotion_time) VALUE ";
        // Перебираем массив worker_motion_less
        foreach ($worker_motion_less as $worker_info) {
            // Если работник есть в массиве
            if (array_key_exists($worker_info['tabel_number'], $workers)) {
                // Если значение параметра не равно 'Stationary'
                if ($worker_info['value'] !== 'Stationary') {
                    // Находим разницу между началом "Без движения" и первым следующим значением "Moving" для воркера
                    $unmotion_time = Assistant::GetMysqlTimeDifference($worker_info['date_time'], $workers[$worker_info['tabel_number']]['date_time']);
                    $worker_info['unmotion_time'] = Assistant::SecondsToTime($unmotion_time);
                    if ($unmotion_time > 300) {
                        $sql_insert_query .= "('" . implode("','", $worker_info) . "'),";                            // сохраняем данные в строку
                    }
                    unset($workers[$worker_info['tabel_number']]);
                }
            } elseif ($worker_info['value'] === 'Stationary') { // если работника нет в массиве воркеров, то добавлем его
                $workers[$worker_info['tabel_number']] = $worker_info;
            }
        }
        $sql_insert_query = rtrim($sql_insert_query, ",");                                                  // убираем последний символ
        $sql_do_insert = Yii::$app->db->createCommand($sql_insert_query)->execute();                                    // выполняем запрос
    }

    /**
     * actionGetArrayForFilterDropdowns - Метод получения списков всех исходных данных для фильтра
     * Нужен для фильтрации, на стороне Front
     * Пример запроса:
     * http://127.0.0.1/summary-report-motionless-people/get-array-for-filter-dropdowns
     *
     * Создал: Якимов М.Н.
     */
    public function actionGetArrayForFilterDropdowns()
    {
// Стартовая отладочная информация
        $method_name = 'actionGetArrayForFilterDropdowns';                                                                          // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = null;                                                                                           // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
//        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

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


            /** Метод начало */
            $mines = (new Query())
                ->select('id, title')
                ->from('mine')
                ->orderBy(['title' => SORT_ASC])
                ->all();

            if ($mines) {
                $result['mines'] = $mines;
            } else {
                $result['mines'] = (object)array();
            }

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
        /** Окончание отладки */


        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);

    }
}
