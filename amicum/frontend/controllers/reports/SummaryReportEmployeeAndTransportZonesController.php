<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\reports;


use frontend\controllers\Assistant;
use frontend\models\Department;
use frontend\models\KindObject;
use frontend\models\ObjectType;
use frontend\models\WorkerCollection;
use Yii;
use yii\db\Query;
use yii\web\Controller;
use yii\web\Response;


class SummaryReportEmployeeAndTransportZonesController extends Controller
{

    public function actionIndex()
    {
        $objectList = ObjectType::find()
            ->select(['title', 'id'])
            ->asArray()->all();
        $kindList = KindObject::find()
            ->select(['title', 'id'])
            ->asArray()->all();
        $departmentList = Department::find()
            ->select(['title', 'id'])
            ->orderBy('title')
            ->asArray()->all();
        return $this->render('index', [
            'departmentList' => $departmentList,
            'kindList' => $kindList,
            'objectList' => $objectList
        ]);
    }


    public function actionResult()
    {
//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "2000M");

        $post = Assistant::GetServerMethod();
        $query = new Query();
        $sql_filter = '';
        $limit = 10000;
        $warnings = array();
        $result = array();
        $errors = array();
        $last_id_condition = -1;                                                                                        // в этой переменной хранится последний id, по которому была сделал выборка.

        isset($post['data_exists']) ? $data_exists = $post['data_exists'] : $data_exists = 0;                           // Если с фронта получали значение data_exist запоминаем значение в переменной если не получали ставим 0

        $limit_flag = -1;                                                                                               // флаг указаывающий на то что есть данные больше чем указанного лимита, и фронт может обратно отправить запрос на получение оставшихся данных
        //фильтр по дате
        if ($post['dateStart'] == null or $post['dateFinish'] == null) {
            $sql_filter .= ' date_work = now() ';
        } else {
            $formatted_date_start = date("Y-m-d H:i:s", strtotime($post['dateStart']));
            $formatted_date_end = date("Y-m-d H:i:s", strtotime($post['dateFinish']));
            if ($formatted_date_start == $formatted_date_end) {
                $formatted_date_start = date("d.m.Y H:i:s", strtotime($post['dateStart']));
                $sql_filter .= 'DATE(date_work) = "' . $formatted_date_start . '"';
            } else if ($formatted_date_start < $formatted_date_end) {
                $sql_filter .= ' date_time_work >= "' . $formatted_date_start . '" AND date_time_work <= "' . $formatted_date_end . '" ';
            } else if ($formatted_date_start > $formatted_date_end) {
                $sql_filter .= ' date_time_work >= "' . $formatted_date_end . '" AND " date_time_work <= ' . $formatted_date_start . '" ';
            }
        }
        $warnings[] = "SummaryReportEmployeeAndTransportZonesController. actionResult. dateStart: " . $post['dateStart'];
        $warnings[] = "SummaryReportEmployeeAndTransportZonesController. actionResult. dateFinish: " . $post['dateFinish'];
        $warnings[] = "SummaryReportEmployeeAndTransportZonesController. actionResult. formatted_date_start: " . $formatted_date_start;
        $warnings[] = "SummaryReportEmployeeAndTransportZonesController. actionResult. formatted_date_end: " . $formatted_date_end;
        $warnings[] = "SummaryReportEmployeeAndTransportZonesController. actionResult. sql_filter: ";
        $warnings[] = $sql_filter;

        //фильтр по типу места
        if ($post['idType'] != null) $sql_filter .= ' AND ' . ' type_id ="' . $post['idType'] . '"';

        //фильтр по виду места
        if ($post['idKind'] != null) $sql_filter .= ' AND ' . ' kind_id="' . $post['idKind'] . '"';

        //фильтр по подразделению
        if ($post['idDepartment'] != null) $sql_filter .= ' AND ' . 'dep_id="' . $post['idDepartment'] . '"';

        //фильтр по статусу
        if ($post['idStatus'] != null) $sql_filter .= ' AND ' . 'stat_id="' . $post['idStatus'] . '"';


        if ($post['search'] != null) {
            $sql_filter .= ' AND (' . 'last_name like "%' . $post['search'] . '%"';
            $sql_filter .= ' OR  ' . 'titleObject like "%' . $post['search'] . '%"';
            $sql_filter .= ' OR  ' . 'titlePlace like "%' . $post['search'] . '%"';
            $sql_filter .= ' OR ' . 'titleType like "%' . $post['search'] . '%"';
            $sql_filter .= ' OR ' . 'titleKind like "%' . $post['search'] . '%"';
            $sql_filter .= ' OR ' . 'titleCompany like "%' . $post['search'] . '%"';
            $sql_filter .= ' OR ' . 'titleDepartment like "%' . $post['search'] . '%")';
        }

        if (isset($post['start_position']) and $post['start_position'] != "") {
            $last_id_condition = $post['start_position'];
            $sql_filter = "(id > $last_id_condition) AND " . $sql_filter;
        }
        $warnings[] = "SummaryReportEmployeeAndTransportZonesController. actionResult. -------------------------";
        $warnings[] = "SummaryReportEmployeeAndTransportZonesController. actionResult. sql_filter: ";
        $warnings[] = $sql_filter;

        $worker_collections = $query->select([
            'last_name lastName',
            'id',
            'titleObject',
            'DATE_FORMAT(date_time_work, "%d.%m.%Y %H:%i:%s") as date_work',
            'titlePlace',
            'titleType',
            'titleKind',
            'titleCompany',
            'titleDepartment',
            'status_worker statusWorker',
            'type_id',
            'kind_id',
            'dep_id',
            'stat_id'
        ])
            ->from("worker_collection")
            ->where($sql_filter)
            ->limit($limit)
            ->all();
        $max_id = 0;
        if ($worker_collections) {
            $max_id = array_column($worker_collections, 'id');
            $max_id = max($max_id);
        }
        $worker_collections_row_count = $query->select('COUNT(id) as count')->from("worker_collection")->where($sql_filter)->one();
        $worker_collections_row_count = $worker_collections_row_count['count'];
        if ($worker_collections_row_count > $limit and $max_id > $last_id_condition) {                                  // если данных больше чем указанного лимита, то отправим фронту флаг, что есть еще данные и чтоб он отправил запрос обратно
            $limit_flag = 1;
        }
        $last_id_condition = $max_id;

        $warnings[] = "SummaryReportEmployeeAndTransportZonesController. actionResult. -------------------------";
        $warnings[] = "SummaryReportEmployeeAndTransportZonesController. actionResult. limit_flag: " . $limit_flag;
        $warnings[] = "SummaryReportEmployeeAndTransportZonesController. actionResult. data_exists: " . $data_exists;
        $warnings[] = "SummaryReportEmployeeAndTransportZonesController. actionResult. count(worker_collections): ";
        $warnings[] = count($worker_collections);

        if ($limit_flag == 1 or ($limit_flag == -1 and !empty($worker_collections)) or $data_exists != 0) {             // Если флаг recall положительный или (Если флаг recall отрицательный и данные получались) или данные добавлялись ранее хотябы раз, не выводим ошибку
            $errors = array();
        } else {
            $errors[] = 'Нет данных по заданному условию';
        }
        //поиск по страничке
        if (!isset($post['search']) or isset($post['search']) and $post['search'] == "") {                              //  если не передан парамкетр поиска или передан и имеет пустое значение, то выводим без так как есть
            foreach ($worker_collections as $worker_collection) {
                if ($worker_collection['titlePlace'] == null) {
                    $worker_collection['titlePlace'] = '-';
                }
                if ($worker_collection['titleType'] == null) {
                    $worker_collection['titleType'] = '-';
                }
                if ($worker_collection['titleKind'] == null) {
                    $worker_collection['titleKind'] = '-';
                }
            }

            $result = $worker_collections;
            unset($worker_collections);
        } else if (isset($post['search']) and $post['search'] != null) {
            $model = array();
            $search_title = $post['search'];
            $i = 0;
            foreach ($worker_collections as $worker_collection) {
                if ($worker_collection['titlePlace'] == null) {
                    $worker_collection['titlePlace'] = '-';
                }
                if ($worker_collection['titleType'] == null) {
                    $worker_collection['titleType'] = '-';
                }
                if ($worker_collection['titleKind'] == null) {
                    $worker_collection['titleKind'] = '-';
                }
                $model[$i] = array();
                $model[$i] ['iterator'] = $i + 1;
                $model[$i] ['lastName'] = Assistant::MarkSearched($search_title, $worker_collection['lastName']);
                $model[$i] ['titleObject'] = Assistant::MarkSearched($search_title, $worker_collection['titleObject']);
                $model[$i] ['date_work'] = date('d.m.Y H:i:s', strtotime($worker_collection['date_work']));
                $model[$i] ['titlePlace'] = Assistant::MarkSearched($search_title, $worker_collection['titlePlace']);
                $model[$i] ['titleType'] = Assistant::MarkSearched($search_title, $worker_collection['titleType']);
                $model[$i] ['titleKind'] = Assistant::MarkSearched($search_title, $worker_collection['titleKind']);
                $model[$i] ['titleCompany'] = Assistant::MarkSearched($search_title, $worker_collection['titleCompany']);
                $model[$i] ['titleDepartment'] = Assistant::MarkSearched($search_title, $worker_collection['titleDepartment']);
                $model[$i] ['statusWorker'] = $worker_collection['statusWorker'];
                $i++;
            }

            $result = $model;
            unset($model);                                                                                              // освободим память
        }

        if (!empty($result)) {                                                                                          // Если добавляем данные в таблицу меняем значение параметра data_exists
            $data_exists++;
        }

        $result_main = array('warnings' => $warnings, 'result' => $result, 'recall' => $limit_flag, 'start_position' => $last_id_condition, 'errors' => $errors, 'data_exists' => $data_exists);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    public function buildCollection()
    {
        $work_cols = WorkerCollection::find()->all();
        $work_colsArray = array();
        $i = 0;
        foreach ($work_cols as $work_col) {
            $work_colsArray[$i]['lastName'] = $work_col->last_name;
            $work_colsArray[$i]['titleObject'] = $work_col->titleObject;
            $work_colsArray[$i]['dateWork'] = $work_col->date_work;
            $work_colsArray[$i]['titlePlace'] = $work_col->titlePlace;
            $i++;
        }
        return $work_colsArray;
    }

}
