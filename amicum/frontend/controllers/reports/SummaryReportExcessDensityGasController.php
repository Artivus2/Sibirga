<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\reports;

use frontend\controllers\Assistant;
use frontend\controllers\handbooks\HandbookPlaceController;

use yii\db\Query;
use Yii;
use yii\web\Response;

/**
 * Отчет: Превышение концентрации газа
 * Class SummaryReportExcessDensityGasController
 * @package app\controllers
 */
class SummaryReportExcessDensityGasController extends \yii\web\Controller
{
    public static $view_name = 'view_sensor_gas_concentration';
    public static $view_rows = 'sensor_id, sensor_title, parameter_id,parameter_title, gas_fact_value, edge_gas_nominal_value, date_time, edge_id, place_id, place_title, unit_title';
    public static $table_name = 'summary_report_sensor_gas_concentration';
    public static $table_rows = 'sensor_id, sensor_title, parameter_id,parameter_title, gas_fact_value, edge_gas_nominal_value, date_time, edge_id, place_id, place_title, unit_title';

    /**************************** Таблицы и представления для синхронизации ***************************/
    public static $sync_view_name = 'view_sensor_gas_concentration_sync';
    public static $sync_view_rows = 'temp_table_id, sensor_id, sensor_title, parameter_id, parameter_title, gas_fact_value, edge_gas_nominal_value, date_time, edge_id, place_id, place_title, unit_title';
    public static $sync_table_name = 'summary_report_sensor_gas_concentration';
    public static $sync_temp_table_name = 'summary_report_sensor_gas_concentration';
    public static $sync_table_rows = 'sensor_id, sensor_title, parameter_id, parameter_title, gas_fact_value, edge_gas_nominal_value, date_time, edge_id, place_id, place_title, unit_title';

    public function actionIndex()
    {

        $handbook_place_controller = new HandbookPlaceController(Yii::$app->controller->id, Yii::$app);
        $place_list = $handbook_place_controller->buildArray();
        return $this->render('index', [
            'places' => $place_list
        ]);
    }

    /**
     * Метод вывода превышения уровная концентрации газа в период определенного времени
     * Created by: Одилов О.У. on 07.11.2018 15:37
     */
    public function actionResult()
    {
//        ini_set('max_execution_time',1200);
//        ini_set('memory_limit','2000M');
        $query = new Query();
        $post = Assistant::GetServerMethod();                                                                             //метод принимает данные из модели для фильтрации запроса.
        $sql_condition = '';                                                                                               //фильтр запроса, т.к. данных в запросе много, то по умолчанию возвращется только данные за текущие сутки,
        $limit = 500;                                                                                                   // ограничение получаемых данных
        $limit_start = 0;                                                                                               // начало лимита, то есть откуда начинать
        $recall = -1;                                                                                                   // флаг используется для того, чтобы на фронт отправить флаг на то что есть данные больше чем указанного лиимита, и чтоб обратно отправил запрос
        $date_time_format = 'Y-m-d H:i:s';

        isset($post['data_exists']) ? $data_exists = $post['data_exists'] : $data_exists = 0;                           // Если с фронта получали data_exists запоминаем значение в переменной если не получали ставим 0

        $syn_table = array();
        $errors = array();
        //фильтр по дате если дата не задана то берутся текущие сутки, если задана, то берутся из метода пост
        if (!isset($post['dateStart']) || !isset($post['dateFinish']))
        {
            $sql_condition .= ' DATE(date_time) = "'.date($date_time_format).'"';
        }
        else
        {
            $formatted_date_start = date($date_time_format,strtotime($post['dateStart']));
            $formatted_date_end = date($date_time_format,strtotime($post['dateFinish']));
            if ($formatted_date_start == $formatted_date_end)
            {
                $sql_condition .= 'DATE(date_time) = "' .$formatted_date_start. '"';
            }

            else if ($formatted_date_start < $formatted_date_end)
            {
                $sql_condition .= ' date_time between "' .$formatted_date_start. '" AND "' .$formatted_date_end. '" ';
            }
            else if ($formatted_date_start > $formatted_date_end)
            {
                $sql_condition .= ' date_time between "' .$formatted_date_end. '" AND "' .$formatted_date_start. '" ';
            }
        }

        /***********************************          ФИЛЬТР ПОИСКА                ************************************/
        $is_isset_search = false;
        $search_title = '';

        //фильтр по месту
        if (isset($post['idPlace'])  && $post['idPlace']!="")
            $sql_condition .= ' AND ' . 'place_id=' . $post['idPlace'] . '';
        //фильтр по типу датчика
        if (isset($post['gasTypeId'])  && $post['gasTypeId']!="")
            $sql_condition .= ' AND ' . 'parameter_id=' . $post['gasTypeId'] . '';

        if (isset($post['search']) && $post['search']!= "")                                                            // поиск по названию места и по типу датчика
        {
            $is_isset_search = true;
            $search_title = $post['search'];
            $sql_condition .= ' AND ' . 'place_title like "%' . $search_title . '%"';
            $sql_condition .= ' OR ' . 'sensor_title LIKE "%' . $search_title . '%"';
        }

        /******** ПРОВЕРЯЕМ, ЕСТЬ ЛИ ЗАПРОС СО ФРОНТА НА ПЛУЧЕНИЯ ОСТАВШИХСЯ ДАННЫХ **********/
        if(isset($post['start_position']) && $post['start_position'] != "")
        {
            $limit_start = $post['start_position'];
        }

        /*******  Проверяем количество записей которые есть в таблице. Если количество больше указанного лимита, то отправим фронту флаг на обратный запрос   */
        $sensor_collections_row_count = $query->select('COUNT(id) as count')->from(self::$table_name)->where($sql_condition)->one();
        $sensor_collections_row_count = $sensor_collections_row_count['count'];
        if($sensor_collections_row_count > $limit_start && $sensor_collections_row_count > $limit)                     // если данных больше че муказанного лимита, то отправим фронту флаг, что есть еще данные и чтоб он отправил запрос обратно
        {
            $recall = 1;
        }
        $sensors = array();
        $sensors_gas_concentration = $query->select([
            'sensor_id',
            'sensor_title',
            'parameter_title',
            'gas_fact_value',
            'edge_gas_nominal_value',
            'unit_title',
            'edge_id',
            'parameter_id',
            'place_id',
            'place_title',
            'date_time'
        ])
            ->from(self::$table_name)
            ->where($sql_condition)
            ->orderBy(['sensor_id' => SORT_ASC, 'parameter_id' => SORT_ASC, 'date_time' => SORT_DESC])					// обязательно!!!
            ->limit($limit)
            ->offset($limit_start)
            ->all();
        $limit_start += $limit;                                                                                         // перемещаем начальную позицию для поиска, то есть начало лимита обновляем (полсе получения со фронта нужно прибавить к начало выборки лимит), то есть если тек начало лимита 50, то 50+ лимит, и со следующего раза выборка будет с 550 до limit
        $date_format = 'Y-m-d H:i:s';
        if($sensors_gas_concentration)
        {
            /**************************    НАХОЖДЕНИЯ ПЕРИОД ПРЕВЫШЕНИЯ УРОВНЯ КОНЦ.ГАЗА    ****************************/
            $current_index = -1;
            $is_previshenie = true;                                                                                     // флаг есть превышение или нет. Если установить да, то добавить новый сенсор у которого есть превышение
            foreach ($sensors_gas_concentration as $sensor)                                                             // в цыкле перебираем каждый элемент массива
            {
                $current_sensor_id = $sensor['sensor_id'];
                if($sensor['edge_id'] != '' && $sensor['edge_id'] != 0)
                {
                    $current_sensor_parameter_id = $sensor['parameter_id'];
                    $current_sensor_gas_value = $sensor['gas_fact_value'];

                    $add_sensor = false;                                                                                // флаг для добавления сенсора, это для того, чтобы одно и тоже по несколько раз не писать)
                    $is_same_sensor = false;                                                                            // флаг указывающий на то что этот сенсор ==  предыдущему сенсору
                    if($current_index >= 0 && $sensors[$current_index]['sensor_id'] == $current_sensor_id)
                    {
                        $is_same_sensor = true;
                    }
                    $excess = $sensor['edge_gas_nominal_value'] - $sensor['gas_fact_value'];                            //концентрация уровня газа: от предельного значения отнимаем текущее значение уровня газа
                    if($current_index == -1 OR $sensors[$current_index]['sensor_id'] != $current_sensor_id and $sensors[$current_index]['parameter_id'] != $current_sensor_parameter_id) // если текущий сенсор == предыдущему и параметры тоже ровны
                    {
                        if($excess < 0)                                                                                // если есть превышение, то добавим (потому что нам нужно выбрать только датчиков, у которых концентрация уровня газа больще предельного
                        {
                            $current_index++;
                            $add_sensor = true;
                        }
                    }
                    else if($is_same_sensor == TRUE && $sensors[$current_index]['parameter_id'] != $current_sensor_parameter_id) // если текущий сенсор == предыдущему и параметры тоже ровны
                    {
                        if($excess < 0)                                                                                // если есть превышение, то добавим (потому что нам нужно выбрать только датчиков, у которых концентрация уровня газа больще предельного
                        {
                            $current_index++;
                            $add_sensor = true;
                        }
                    }
                    else if($is_previshenie == false)                                                                   //если в предыдущем цыкле НЕ было превышение и в текущем цикле нет превышения, то пропустим это цикл
                    {
                        if($excess < 0)                                                                                // если есть превышение, то добавим (потому что нам нужно выбрать только датчиков, у которых концентрация уровня газа больще предельного
                        {
                            $current_index++;
                            $add_sensor = true;
                            $is_previshenie = true;
                        }
                    }
                    else if ($is_same_sensor == TRUE && $sensors[$current_index]['parameter_id'] == $current_sensor_parameter_id)  // если это тот же самый сенсор  и есть превышение, то последнее время превышения == тек.индексу массива
                    {
                        if($excess < 0)
                        {
                            $sensors[$current_index]['date_time_end'] = $sensor['date_time'];                           // дата и время завершения превышения уровня концентрации газа
                            $date_end = date_create($sensors[$current_index]['date_time_start'])->format($date_format);
                            $date_start = date_create($sensor['date_time'])->format($date_format);
                            $sensors[$current_index]['duration'] =  Assistant::DateTimeDiff($date_end, $date_start);
                            $sensors[$current_index]['max_gas_val'] = ($sensors[$current_index]['gas_fact_value'] > $current_sensor_gas_value) ? $sensors[$current_index]['gas_fact_value'] : $current_sensor_gas_value;
                            $sensors[$current_index]['date_time_start'] = $date_start;                             		// дата и время начало превышения уровня концентрации газа
                            $sensors[$current_index]['date_time_end'] = $date_end;                              		 // дата и время завершения превышения уровня концентрации газа
                            // Из-за того, что дата сортируется по убиванию, название переменных как-то могут не соответсвовать,
                            // и это норм (проблема была в том, что дата начало больше чем дата окончания)
                        }
                        else
                        {
                            $is_previshenie = false;                                                                    // если превышения нет, уставновим флаг, чтоб в следующем итерации добавили в массив новый сенсор с первышением'
                        }
                    }
                    if($add_sensor === TRUE)
                    {
                        $sensors[$current_index]['sensor_id'] = $current_sensor_id;
                        $sensors[$current_index]['sensor_title'] = $sensor['sensor_title'];
                        $sensors[$current_index]['unit_title'] = $sensor['unit_title'];
                        $sensors[$current_index]['parameter_title'] = $sensor['parameter_title'];
                        $sensors[$current_index]['gas_fact_value'] = $sensor['gas_fact_value'];
                        $sensors[$current_index]['edge_gas_nominal_value'] = $sensor['edge_gas_nominal_value'];
                        $sensors[$current_index]['edge_id'] = $sensor['edge_id'];
                        $sensors[$current_index]['place_title'] = $sensor['place_title'];
                        $sensors[$current_index]['date_time'] = $sensor['date_time'];
                        $sensors[$current_index]['date_time_start'] = $sensor['date_time'];                             // дата и время начало превышения уровня концентрации газа
                        $sensors[$current_index]['date_time_end'] = $sensor['date_time'];                               // дата и время завершения превышения уровня концентрации газа
                        $sensors[$current_index]['parameter_id'] = $current_sensor_parameter_id;
                        $date_start = date_create($sensors[$current_index]['date_time_start'])->format($date_format);
                        $date_end = date_create($sensor['date_time'])->format($date_format);
                        $sensors[$current_index]['duration'] =  Assistant::DateTimeDiff($date_start, $date_end);
                        $sensors[$current_index]['max_gas_val'] = $sensor['gas_fact_value'];
                    }
                }
                else
                {
                    $errors[$current_sensor_id] = "У датчика с sensor_id = $current_sensor_id нет выработки";
                }
            }

            /**********************************     ВЫДЕЛЕНИЕ ИСКОМОГО ЗНАЧЕНИЯ      **********************************/
            if($is_isset_search === TRUE)                                                                               // если задан параметр поиска, то выделяем нискомого значения
            {
                $sensors_size = count($sensors);
                for ($i = 0; $i <  $sensors_size; $i++)
                {
                    $sensors[$i]['sensor_title'] = Assistant::MarkSearched($search_title, $sensors[$i]['sensor_title']);
                    $sensors[$i]['place_title'] = Assistant::MarkSearched($search_title, $sensors[$i]['place_title']);
                    $sensors[$i]['parameter_title'] = Assistant::MarkSearched($search_title, $sensors[$i]['parameter_title']);
                }
            }
        }
        else
        {
            if($limit >= $limit_start && $recall == -1 && $data_exists == 0)                                            // Если ограничение по выборке больше текущего id выборки, флаг на повторный запрос отрицателен и данные ранее не добавлялись в табличку выводим ошибку
                $errors[] = 'Нет данных по заданному условию';
        }
        if(!empty($sensors))                                                                                            // Если есть данные которые будем добавлять в таблицу изменяем значение data_exists
            $data_exists++;
//        ArrayHelper::multisort($sensors,'date_time',SORT_DESC); // todo Проблема: загружаются только по 500 записей и сортировка применяется только к ним, так если выбрать дату например с декабря, отсортируются сначала старые 500 записей, а потом новые 500 записей

        $result = array('errors' => $errors, 'sensors' => $sensors,  'recall' => $recall,
            'start_position' => $limit_start, 'syn-report' => $syn_table,
            'record_count' => $sensor_collections_row_count, 'data_exists' => $data_exists);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }
}
