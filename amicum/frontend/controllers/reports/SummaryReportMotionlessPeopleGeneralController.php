<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\reports;


use frontend\controllers\Assistant;
use frontend\controllers\handbooks\HandbookDepartmentController;
use frontend\controllers\handbooks\HandbookPlaceController;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\Response;

class SummaryReportMotionlessPeopleGeneralController extends \yii\web\Controller
{
    public static $view_name = 'view_worker_motion_less';
    public static $view_rows = 'date_work, tabel_number, FIO, value, title_department, department_id, title_place, date_time, smena';
    public static $table_name = 'worker_motion_less';
    public static $table_rows = 'date_work,tabel_number, FIO, value, title_department, department_id, title_place, date_time, smena';
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
//        ini_set('max_execution_time', 700);
//        ini_set('memory_limit', "2000M");
        $query = new Query();
        $date_time_format = "Y-m-d H:i:s";
        $date_time = date($date_time_format);
        $limit = 100000;                                                                                                   // ограничение получаемых данных
        $limit_start = 0;                                                                                               // начало лимита, то есть откуда начинать
        $warnings = [];                                                                                                 // массив предупреждений
        $recall = -1;                                                                                                   // флаг используется для того, чтобы на фронт отправить флаг на то что есть данные больше чем указанного лиимита, и чтоб обратно отправил запрос
        $debug_flag = 0;                                                                                                  //Отладочный флаг 1 - включено , 0 выключено.
        $post = Yii::$app->request->post();                                                                             //Метод принимает данные из модели для фильтрации запроса.
        $sql_filter = '';
        //Фильтр запроса, т.к. данных в запросе много, то по умолчанию возвращется только данные за текущие сутки.
//        $post['dateStart'] = '01-06-2018';
//        $post['dateFinish'] = '01-10-2018';

        isset($post['data_exists']) ? $data_exists = $post['data_exists'] : $data_exists = 0;                           // Если с фронта получали data_exists запоминаем значение в переменной если не получали ставим 0

        $syn_table = array();

        //фильтр по дате
        if ($post['dateStart'] == null or $post['dateFinish'] == null) {
            $sql_filter .= ' date_work = CURDATE() ';
            $date_time = date($date_time_format);
        } else {
            $formatted_date_start = date($date_time_format, strtotime($post['dateStart']));
            $formatted_date_end = date($date_time_format, strtotime($post['dateFinish']));
            if ($formatted_date_start == $formatted_date_end) {
                $sql_filter .= 'DATE(date_time) = "' . $formatted_date_start . '"';
            } else if ($formatted_date_start < $formatted_date_end) {
                $sql_filter .= ' date_time between "' . $formatted_date_start . '" AND "' . $formatted_date_end . '" ';
            } else if ($formatted_date_start > $formatted_date_end) {
                $sql_filter .= ' date_time between "' . $formatted_date_end . '" AND "' . $formatted_date_start . '" ';
            }
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
            $sql_filter .= ' OR ' . ' tabel_number like "%' . $post['search'] . '%"';
            $sql_filter .= ' OR  ' . 'title_place like "%' . $post['search'] . '%")';
        }

        if (isset($post['mine_id']) && $post['mine_id'] != '' && $post['mine_id'] != -1)                                // если передан еще  id шахты, то добавим в массив
        {
            $sql_filter .= " and mine_id = " . (int)$post['mine_id'];                                                    //добавляем критерий поиска по его id
        }

        /******** ПРОВЕРЯЕМ, ЕСТЬ ЛИ ЗАПРОС СО ФРОНТА НА ПЛУЧЕНИЯ ОСТАВШИХСЯ ДАННЫХ **********/
        if (isset($post['start_position']) AND $post['start_position'] != "") {
            $limit_start = $post['start_position'];
        }

        $warnings[] = "Фильтр итоговый: ";
        $warnings[] = $sql_filter;

        /*******  Проверяем количество записей которые есть в таблицею. Если количество больше указанного лимита, то  отправим фронту флаг на обратный запрос   */
        $worker_motion_less_row_count = $query->select('COUNT(id) as count')->from(self::$table_name)->where($sql_filter)->one();
        $worker_motion_less_row_count = $worker_motion_less_row_count['count'];
        if ($worker_motion_less_row_count > $limit_start and $worker_motion_less_row_count > $limit)                     // если данных больше че муказанного лимита, то отправим фронту флаг, что есть еще данные и чтоб он отправил запрос обратно
        {
            $recall = 1;
        }

        $worker_collections = (new Query())//Запрос напрямую из базы по вьюшке view_personal_areas
        ->select(                                                                                                       //Обязательно сортируем по порядку
            [
                'date_work',
                'tabel_number',
                'FIO',
                'value',
                'title_department',
                'department_id',
                'title_place',
                'DATE_FORMAT(date_time,\'%d.%m.%Y %H:%i:%s\') AS date_time',
                'smena',
                'unmotion_time as motion_less_time'
            ])
            ->from(self::$table_name)
            ->where($sql_filter)
            ->limit($limit)
            ->offset($limit_start)
            ->orderBy([
                'FIO' => SORT_ASC,
                'date_time' => SORT_DESC
            ])
            ->all();
        $limit_start += $limit;                                                                                         // перемещаем начальную позицию для поиска, то есть начало лимита обновляем (полсе получения со фронта нужно прибавить к начало выборки лимит), то есть если тек начало лимита 50, то 50+ лимит, и со следующего раза выборка будет с 550 до limit
        $model = array();
        $errors = array();
        //поиск по страничке
        if (isset($post['search']) AND $post['search'] != '') {
            $search_title = $post['search'];
            $j = 0;
            foreach ($worker_collections as $worker) {
                $model[$j]['iterator'] = $j + 1;
                $model[$j]['tabel_number'] = Assistant::MarkSearched($search_title, $worker['tabel_number']);
                $model[$j]['FIO'] = Assistant::MarkSearched($search_title, $worker['FIO']);
                $model[$j]['title_department'] = Assistant::MarkSearched($search_title, $worker['title_department']);
                $model[$j]['department_id'] = Assistant::MarkSearched($search_title, $worker['department_id']);
                $model[$j]['title_place'] = Assistant::MarkSearched($search_title, $worker['title_place']);
                $model[$j]['date_time'] = Assistant::MarkSearched($search_title, $worker['date_time']);
                $model[$j]['smena'] = Assistant::MarkSearched($search_title, $worker['smena']);
                $model[$j]['motion_less_time'] = Assistant::MarkSearched($search_title, $worker['motion_less_time']);
                $j++;
            }
        } else {
            $model = $worker_collections;
        }
        unset($personal_collections);

        if ($recall == 1 OR $data_exists != 0)                                                                       // Если флаг запроса на получение данных с фронтенд положительный или данные ранее добавлялись в таблицу ошибку не отображаем
            $errors = array();
        else if (($limit >= $limit_start && $recall == -1 && count($model) <= 0)) // Иначе если id последнего выбранного меньше ограничение на выборку и флаг запроса к фронтенду отрицателен выводим ошибку
            $errors[] = "Нет данных по заданному условию";

        $result = array('result' => $model, 'errors' => $errors, 'recall' => $recall, 'start_position' => $limit_start,
            'syn-report' => $syn_table, 'record_count' => $worker_motion_less_row_count, 'data_exists' => $data_exists, 'warnings' => $warnings);
        unset($model);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

}
