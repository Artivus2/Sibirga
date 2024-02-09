<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\reports;


use backend\controllers\StrataJobController;
use frontend\controllers\Assistant;
use frontend\models\Company;
use frontend\models\Department;
use Yii;
use yii\db\Query;
use yii\web\Response;

//отчет: ВРЕМЯ ВЫХОДА ПЕРСОНАЛА ПО ОКОНЧАНИЮ СМЕНЫ
class SummaryReportEndOfShiftController extends \yii\web\Controller
{
    public static $view_name = 'view_end_shift_worker';
    public static $view_rows = 'temp_table_id, date_work, smena, worker_object_id, FIO, tabel_number, worker_parameter_id, department_id, company_title, company_id, department_title, parameter_type_id, value, date_time, worker_id';
    public static $table_name = 'summary_report_end_of_shift';
    public static $table_rows = 'date_work, date_time, FIO, worker_object_id, department_title, company_title, tabel_number, smena, worker_id, department_id, company_id';

    /**************************** Таблицы и представления для синхронизации ***************************/
    public static $sync_view_name = 'view_summary_report_end_of_shift_sync';
    public static $sync_view_rows = 'temp_table_id, date_work, date_time, FIO, worker_object_id, department_title, company_title, tabel_number, smena, worker_id, department_id,company_id';
    public static $sync_table_name = 'summary_report_end_of_shift';
    public static $sync_temp_table_name = 'worker_parameter_value_temp';
    public static $sync_table_rows = 'date_work, date_time, FIO, worker_object_id, department_title, company_title, tabel_number, smena, worker_id, department_id,company_id';

    public function actionIndex()
    {
        $departmentList = Department::find()->select(['title', 'id'])->orderBy(['title' => SORT_ASC])->asArray()->all();
        $companyList = Company::find()->select(['title', 'id'])->orderBy(['title' => SORT_ASC])->asArray()->all();
        //$employeeList = HandbookEmployeeController::GetEmpoyees();
        //$workersList = HandbookEmployeeController::GetTabelNumber();
        return $this->render('index', ['departmentList' => $departmentList, //'employeeList' => $employeeList,
            // 'workersList' => $workersList,
            'companyList' => $companyList]);
    }



    public function actionResult()
    {
//        ini_set('max_execution_time', 600);
//        ini_set('memory_limit', '2000M');
        $date_time_format = 'Y-m-d H:i:s';
        $debug_flag = 0;                                                                                                  //отладочный флаг 1 - включено , 0 выключено.
        $post = Assistant::GetServerMethod();                                                                             //метод принимает данные из модели для фильтрации запроса.
        $sql_filter = '';                                                                                               //фильтр запроса, т.к. данных в запросе много, то по умолчанию возвращется только данные за текущие сутки,
        $date_time = date($date_time_format);
        $syn_table = array();
        $limit = 500;                                                                                                   // ограничение получаемых данных
        $limit_start = 0;                                                                                               // начало лимита, то есть откуда начинать
        $recall = -1;                                                                                                   // флаг используется для того, чтобы на фронт отправить флаг на то что есть данные больше чем указанного лиимита, и чтоб обратно отправил запрос
        $errors = array();

        isset($post['data_exists']) ? $data_exists = $post['data_exists'] : $data_exists = 0;                           // Если с фронта получали data_exists запоминаем значение в переменной если не получали ставим 0

        $query = new Query();
        //фильтр по дате если дата не задана то берутся текущие сутки, если задана, то берутся из метода пост
        if (!isset($post['dateStart'], $post['dateFinish'])) {
            $sql_filter .= ' DATE(date_time) = "' . date($date_time_format) . '"';
            if ($debug_flag == 1) $sql_filter = ' date_work = "2018-03-25" ';
        } else {
            $formatted_date_start = date($date_time_format, strtotime($post['dateStart']));
            $formatted_date_end = date($date_time_format, strtotime($post['dateFinish']));
            if ($formatted_date_start == $formatted_date_end) {
                $sql_filter .= 'DATE(date_time) = "' . $formatted_date_start . '"';
            } else if ($formatted_date_start < $formatted_date_end) {
                $sql_filter .= ' date_time >= "' . $formatted_date_start . '" AND date_time <= "' . $formatted_date_end . '" ';
            } else if ($formatted_date_start > $formatted_date_end) {
                $sql_filter .= ' date_time >= "' . $formatted_date_end . '" AND date_time <= "' . $formatted_date_start . '" ';
            }
        }

        //фильтр по подразделению
        if (isset($post['idDepartment']) && $post['idDepartment'] != '') $sql_filter .= ' AND ' . 'department_id=' . $post['idDepartment'] . '';

        //фильтр по предприятию
        if (isset($post['idCompany']) && $post['idCompany'] != '') $sql_filter .= ' AND ' . 'company_id=' . $post['idCompany'] . '';

        //фильтр по самой строке поиска - по буквенный
        if (isset($post['search']) && $post['search'] != '') {
            $sql_filter .= ' AND (' . 'FIO like "%' . $post['search'] . '%"';
            $sql_filter .= ' OR ' . 'company_title like "%' . $post['search'] . '%"';
            $sql_filter .= ' OR ' . 'department_title like "%' . $post['search'] . '%"';
            $sql_filter .= ' OR ' . 'tabel_number like "%' . $post['search'] . '%")';
        }
        /******** ПРОВЕРЯЕМ, ЕСТЬ ЛИ ЗАПРОС СО ФРОНТА НА ПЛУЧЕНИЯ ОСТАВШИХСЯ ДАННЫХ **********/
        if (isset($post['start_position']) && $post['start_position'] != '') {
            $limit_start = $post['start_position'];
        }

        /*******  Проверяем количество записей которые есть в таблицею. Если количество больше указанного лимита, то  отправим фронту флаг на обратный запрос   */
        $workers_row_count = $query
            ->select('COUNT(id) as count')
            ->from(self::$table_name)
            ->where($sql_filter)
            ->scalar();
        //$workers_row_count = $workers_row_count['count'];

        if ($workers_row_count > $limit_start && $workers_row_count > $limit)                     // если данных больше че муказанного лимита, то отправим фронту флаг, что есть еще данные и чтоб он отправил запрос обратно
        {
            $recall = 1;
        }

        $personal_collections = $query
            ->select([
                'date_work',
                'date_time',
                'FIO',
                'worker_object_id',
                'department_title',
                'company_title',
                'tabel_number',
                'smena',
                'worker_id',
                'department_id'
            ])
            ->from(self::$table_name)
            ->orderBy(['date_time' => SORT_DESC])
            ->where($sql_filter)
            ->limit($limit)
            ->offset($limit_start)
            ->all();
        $limit_start += $limit;                                                 // перемещаем начальную позицию для поиска, то есть начало лимита обновляем (полсе получения со фронта нужно прибавить к начало выборки лимит), то есть если тек начало лимита 50, то 50+ лимит, и со следующего раза выборка будет с 550 до limit
        $j = 0;
        $model = array();
        //поиск по страничке
        if (isset($post['search']) && $post['search'] != '')                                //  если не передан парамкетр поиска или передан и имеет пустое значение, то выводим без так как есть
        {
            $search_title = $post['search'];
            foreach ($personal_collections as $personal) {
                $date_work = $personal ['date_work'];
                //$model[$j]['iterator'] = $j + 1;
                $model[$j]['date_work'] = (($date_work == NULL || $date_work == "") ? date('d.m.Y', strtotime($personal ['date_time'])) : date('d.m.Y', strtotime($date_work)));
                $model[$j]['FIO'] = Assistant::MarkSearched($search_title, $personal['FIO']);
                //$model[$j]['smena'] = Assistant::MarkSearched($search_title, $personal['smena']);
                $model[$j]['department_title'] = Assistant::MarkSearched($search_title, $personal['department_title']);
                $model[$j]['company_title'] = Assistant::MarkSearched($search_title, $personal['company_title']);
                $model[$j]['tabel_number'] = Assistant::MarkSearched($search_title, (string)$personal['tabel_number']);
                $model[$j]['date_time'] = Assistant::MarkSearched($search_title, date('d.m.Y H:i:s', strtotime($personal['date_time'])));
                $j++;
            }
        } else {
            foreach ($personal_collections as $personal) {
                $date_work = $personal ['date_work'];
                //$model[$j]['iterator'] = $j + 1;
                $model[$j]['date_work'] = (($date_work == NULL || $date_work == "") ? date('d.m.Y', strtotime($personal['date_time'])) : date('d.m.Y', strtotime($date_work)));
                $model[$j]['FIO'] = $personal ['FIO'];
                $model[$j]['department_title'] = $personal ['department_title'];
                $model[$j]['company_title'] = $personal ['company_title'];
                $model[$j]['tabel_number'] = $personal ['tabel_number'];
                //$model[$j]['smena'] = $personal ['smena'];
                //$model[$j]['worker_id'] = $personal ['worker_id'];
                //$model[$j]['department_id'] = $personal ['department_id'];
                $model[$j]['date_time'] = date('d.m.Y H:i:s', strtotime($personal['date_time']));
                $j++;
            }
        }

        unset($personal_collections);

        if ($recall == 1 || $data_exists != 0)                                   // Если флаг запроса на получение данных с фронтенд положительный или данные ранее добавлялись в таблицу ошибку не отображаем
            $errors = array();
        elseif ($limit >= $limit_start && $recall == -1 && count($model) <= 0) // Иначе если id последнего выбранного меньше ограничение на выборку и флаг запроса к фронтенду отрицателен выводим ошибку
            $errors[] = 'Нет данных по заданному условию';

        $result = array('result' => $model, 'errors' => $errors, 'recall' => $recall,
            'start_position' => $limit_start, 'syn-report' => $syn_table,
            'record_count' => $workers_row_count, 'data_exists' => $data_exists);
        unset($model);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Метод для добавления записи в отчётную таблицу summary_report_end_of_shift
     * @param int $worker_id Идентификатор воркера
     * @param string $date_time Дата и время получения значения выхода из шахты
     * @throws \yii\db\Exception При ошибке выполнения запроса на запись
     */
    public static function AddTableReportRecord($worker_id, $date_time)
    {
        $shift_info = StrataJobController::getShiftDateNum($date_time);
        $worker_info = (new Query())
            ->select([
                'FIO',
                'worker_object_id',
                'department_title',
                'company_title',
                'tabel_number',
                'department_id',
                'company_id',
                'worker_link_1c',
                'company_link_1c'

            ])
            ->from('view_worker_employee_info')
            ->where([
                'worker_id' => $worker_id
            ])
            ->one();
        Yii::$app->db->createCommand()->insert('summary_report_end_of_shift', [
            'date_work' => $shift_info['shift_date'],
            'date_time' => $date_time,
            'FIO' => $worker_info['FIO'],
            'worker_object_id' => $worker_info['worker_object_id'],
            'department_title' => $worker_info['department_title'],
            'company_title' => $worker_info['company_title'],
            'tabel_number' => $worker_info['tabel_number'],
            'smena' => $shift_info['shift_num'],
            'worker_id' => $worker_id,
            'department_id' => $worker_info['department_id'],
            'company_id' => $worker_info['company_id']
        ])->execute();
        return $worker_info;
    }
}


