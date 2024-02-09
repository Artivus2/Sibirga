<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

/**
 * Created by PhpStorm.
 * User: OzarOdilov
 * Date: 12.10.2018
 * Time: 11:50
 */

namespace frontend\controllers\reports;

use Yii;
use yii\db\Query;
use yii\web\Controller;
use DateTime;

class SummaryStatistics extends Controller
{
    public function actionIndex()
    {
        echo "Go!!!";
    }

    /**
     * МЕТОД ДОБАВЛЕНИЯ ДАННЫХ ИЗ ПРЕДСТАВЛЕНИЯ В ТАБЛИЦУ
     * ИСПОЛЬЗУЕТСЯ ДЛЯ ОПТИМИЗАЦИИ СВОДНОЙ СТАТИСТИКИ, НО МОЖНО ИСПОЛЬЗОВАТЬ ДЛЯ ДРУГИХ ЦЕЛЕЙ
     * Данные сначала получаем из представлений, потом добавляем в таблицу. Потому что если INSERT ... SELECT, то
     * MySql заблокирует таблицу в этот момент!!!
     *
     * @param $view_name - название представления
     * @param $view_rows - поля (колонки) представления
     * @param $table_name - название таблицы
     * @param $table_rows - поля (колонки) таблицы
     * @param $input_date - желаемая дата
     * @param $column_date_name - название поля даты таблицы и представления(они должны быть одинаковые)
     *
     * @return array - результат выполнения фукнции в виде массива
     * Автор: ОДИЛОВ О.У.
     * @throws \yii\db\Exception
     */
    public static function FillTableWithNewDatasFromView($view_name, $view_rows, $table_name, $table_rows, $input_date, $column_date_name)
    {
        $success = array();
        $result = "";
        $sql_view = "";
        $sql_done = 0;
//        ini_set('max_execution_time', 600);
//        ini_set('memory_limit', "10500M");
        $input_date = date("Y-m-d", strtotime($input_date));
        $table_max_date_time = "";
        $flag_table_empty = false;
        $flag_view_empty = false;
        $table_max_date = (new Query())// получаем последнюю дану из таблицы
        ->select(" max($column_date_name) as max_date_work")->from($table_name)->one();
        $table_max_date_time = $table_max_date['max_date_work'];                                                        // точная дата со временем =
        $table_max_date = $table_max_date['max_date_work'];                                                             // точная дата таблицы без времени, так как дата приходит к нам в обыном формате, без времени

        /**
         *  Если в таблице данных нет, получаем минимальную дату из вюшки
         *  Так как в таблице данных нет, то максимальную дату в таблице укажем минимальную дату таблицы, чтоб потом создать период.
         *  Если таблица не пустая то
         */
        if (empty($table_max_date) === TRUE)                                                                                   // если данных нет, то получаем мин дату из вюшки и добавим в таблицу
        {
            $flag_table_empty = true;
            $min_date = (new Query())->select("min($column_date_name) as date_time")->from("$view_name")->one(); // получаем минимальную дату из вюшки
            if ($min_date and $min_date['date_time'] != "")                                                              // проверяем, есть ли минимальная дата, если есть то для таблицы будет как масимальная дата
            {
                $min_date = $min_date['date_time'];
                $table_max_date = date("Y-m-d", strtotime($min_date));
                $table_max_date_time = date("Y-m-d H:i:s", strtotime("$table_max_date 00:00:01"));
                $success[] = "В таблице нет данных. Получил минимальную дату ($table_max_date) из представления $view_name. И задал полученную дату как мак.дата для таблицы $table_name";
            } else
            {
                $flag_view_empty = true;
            }
        } else                                                                                                            // если данные есть в таблице, то получаем  максимальную дату таблицы
        {
            $table_max_date = date("Y-m-d", strtotime($table_max_date));
        }

        $success[] = "Масимальная дата в таблице $table_name $table_max_date_time";
        $success[] = "Полученная дата $input_date";

        if ($input_date > $table_max_date)                                                                               // если последняя дата таблицы меньше чем полученная дата, то получаем данные из вюшки где данные больше чем последняя дата таблицы
        {
            if ($flag_view_empty == true)                                                                                // если данных нет во вюшке
            {
                $success[] = "Нет данных в представлении $view_name для копирования в таблицу $table_name";
            } else                                                                                                        // если даныне есть во вюшке, то скопируем их в таблицу
            {
                if ($flag_table_empty == true)                                                                           // если таблица пустая, то по частям получаем и добавляем данные в БД
                {
                    $interval_date = 3;
                    $period = Assistant::CreateDatePeriod($table_max_date, $input_date, $interval_date);                // создаем период времени
                    $date_max_select = "";                                                                              // это для того, чтобы дату из foreach получить и добавить 3 дня
                    $i = 1;
                    foreach ($period as $date_of_period)
                    {
                        $date = $date_of_period->format("Y-m-d");                                                       // текущая дата в массиве
                        $date_max_select = $date;
                        $date_max_select = Assistant::AddSubtractDate($date_max_select, "+", $interval_date);          // добавим интервал даты чтоб получить меньше указанной даты ВКЛЮЧИТЕЛЬНО  (добавляем в тек. дату 3 дня, чтоб с $date по $date + 3 дня получить данные)
                        $input_date = Assistant::AddSubtractDate($input_date, "+", 1);          // добавим интервал даты чтоб получить меньше указанной даты ВКЛЮЧИТЕЛЬНО

                        if ($view_name == 'view_personal_areas_all' AND $table_name == 'summary_report_time_spent')
                        {
                            $sql_view = "CALL SyncSumRepTimeSpent('$date', '$date_max_select')";
                            \Yii::$app->db->createCommand($sql_view)->execute();
                        } else
                        {
                            $sql_view = "SELECT $view_rows FROM $view_name where $column_date_name > '$date' AND $column_date_name < '$date_max_select'";// получаем данные из основной таблицы
                            $sql_view_data = \Yii::$app->db->createCommand($sql_view)->queryAll();                                                  // выполняем запрос
                            if ($sql_view_data)
                            {
                                $array_count = count($sql_view_data);
                                $sql = "INSERT INTO $table_name ($table_rows) VALUES ";                                                    // вставляем в другую таблицу
                                for ($index = 0; $index < $array_count; $index++)
                                {
                                    $sql .= "('" . implode("','", $sql_view_data[$index]) . "'),";

                                }
                                $sql = rtrim($sql, ",");                                                            // убираем последний символ
                                $sql_done = \Yii::$app->db->createCommand($sql)->execute();                                 // выполняем запрос
                                $success['loop'] = "Добавил $sql_done запись(ей) из представления $view_name в таблицу $table_name";
                                unset($sql, $array_count, $sql_view_data);
                            }
                        }
                        $success['sql'][] = $sql_view;
                        $i++;
                    }
                } else                                                                                                    // если таблица не пустая, то добавим данные в таблицу
                {
//                    $date_time = date('Y-m-d', strtotime($input_date ." +1 days"));                                      // добавим 1 день, чтобы получить все данные, меньше полченного дня(включая полученного дня)
                    $date_time = date('Y-m-d', strtotime($input_date)) . " 23:59:59.999999";                                      // добавим 1 день, чтобы получить все данные, меньше полченного дня(включая полученного дня)
                    $table_max_date_time = $table_max_date_time . '.999999';
                    if ($view_name == 'view_personal_areas_all' AND $table_name == 'summary_report_time_spent')
                    {
                        $sql_view = "CALL  SyncSumRepTimeSpent('$table_max_date_time', '$date_time')";
                        \Yii::$app->db->createCommand($sql_view)->execute();
                    } else
                    {
                        $sql_view = "SELECT $view_rows FROM $view_name where $column_date_name > '$table_max_date_time' and $column_date_name < '$date_time'";// получаем данные из основной таблицы
                        $sql_view_data = \Yii::$app->db->createCommand($sql_view)->queryAll();                                                  // выполняем запрос
                        if ($sql_view_data)
                        {
                            $array_count = count($sql_view_data);
                            $sql = "INSERT INTO $table_name ($table_rows) VALUES ";                                                    // вставляем в другую таблицу
                            for ($index = 0; $index < $array_count; $index++)
                            {
                                $sql .= "('" . implode("','", $sql_view_data[$index]) . "'),";

                            }
                            $sql = rtrim($sql, ",");                                                            // убираем последний символ
                            $sql_done = Yii::$app->db->createCommand($sql)->execute();                                 // выполняем запрос
                            $success['loop'] = "Добавил $sql_done запись(ей) из представления $view_name в таблицу $table_name";
                            unset($sql, $array_count, $sql_view_data);
                        }
                    }
                    $success[] = "Полученная дата $input_date больше чем последняя дата $table_max_date в таблице $table_name";
                    $success['sql'] = $sql_view;
                }
            }
        } else $success[] = "В таблице $table_name данные актуальные";
        return $success;
    }

    /**
     * Метод синхронизации таблиц.
     * Метод получает данные из указанной таблицы, и добавляет в указанную таблицу и удаляет данные из временной таблицы
     * Название метода: SyncTable()
     * @package app\controllers
     * @param $view_name - название предситавления
     * @param $view_rows - поля представления
     * @param $table_name - название таблицы в которую нужно добавлять данные
     * @param $table_rows - поля таблицы  в которую нужно добавлять данные
     * @param $temp_table_name - название временной таблицы из которой нужно удалять данные после добавления
     * @param $show_debug - показывать ли результат добавления запроса для дебага
     *
     * @return array результат выполнения запроса
     *
     *
     * @throws \yii\db\Exception
     * Документация на портале:
     *
     * Входные обязательные параметры:
     * @see
     * @example
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 05.04.2019 21:27
     * @since ver
     */
    public static function SyncTable($view_name, $view_rows, $table_name, $table_rows, $temp_table_name, $show_debug)
    {
        $success = array();
        $errors = array();
        $sql_view_query = "SELECT $view_rows FROM $view_name ";                                                            // создаем запрос на получении данных из представления
        $sql_view_data = Yii::$app->db->createCommand($sql_view_query)->queryAll();                                    // получаем данные из представления
        if ($sql_view_data)
        {
            $array_count = count($sql_view_data);                                                                        // получаем количество полученных данных из вьюшки
            $sql_insert_query = "INSERT IGNORE INTO $table_name ($table_rows) VALUES ";                                        // подготовим запрос добавления в таблицу (которая для отчета)
            $table_max_id = -1;
            for ($index = 0; $index < $array_count; $index++)                                                            // чтобы один раз добавить полученные данные, создаем запрос на INSERT
            {
                $table_max_id = (($table_max_id > $sql_view_data[$index]['temp_table_id']) ? $table_max_id : $sql_view_data[$index]['temp_table_id']);
                unset($sql_view_data[$index]['temp_table_id']);
                $sql_insert_query .= "('" . implode("','", $sql_view_data[$index]) . "'),";                            // сохраняем данные в строку
            }
            $sql_insert_query = rtrim($sql_insert_query, ",");                                                  // убираем последний символ
            $sql_do_insert = Yii::$app->db->createCommand($sql_insert_query)->execute();                                    // выполняем запрос
            //$sql_delete_old_data = Yii::$app->db->createCommand("DELETE FROM $temp_table_name WHERE id <= $table_max_id");
            $success[] = "Количество полученных данных из представления(временной таблицы) : $array_count";
            $success[] = "Количество добавленных данных в таблицу $table_name: $sql_do_insert";
            //$success[] = "Удалил из временной таблицы $temp_table_name $sql_delete_old_data запись(ей)";
            unset($sql_insert_query, $sql_view_data);
        }
        else
        {
            $errors[] = "Нет данных во временной таблице $view_name (это представление включает в себя временную таблицу)";
        }
        $result = array('errors' => $errors, 'success' => $success);
        if ($show_debug == true)
        {
            echo nl2br("\r\n------------------------	  Результат выполнения запроса	------------------------\r\n");
            Assistant::PrintR($result);
        }
        //return $result;
    }
}