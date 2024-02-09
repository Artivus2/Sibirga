<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers\positioningsystem;
//ob_start();

use frontend\controllers\Assistant;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\Response;

class WorkerInfoController extends \yii\web\Controller
{

    public function actionIndex()
    {
        $status = $this->WorkerStatuses();
        return $this->render('index', ['status' => $status]);
    }

    /**
     * Название метода: actionWorkerStatuses()
     * Метод возврата статусов работника
     * Входные параметры:
     * @author Озармехр Одилов
     * Created date: on 24.12.2018 15:51
     */
    public function WorkerStatuses()
    {
        $status = array();
        $status[0]['id'] = 1;
        $status[0]['title'] = "Зарегистрировался/В ламповой";

        $status[1]['id'] = 2;
        $status[1]['title'] = "Зарегистрировался/В шахте";

        $status[2]['id'] = 3;
        $status[2]['title'] = "Разрядился/В шахте/Ошибка";

        $status[3]['id'] = 4;
        $status[3]['title'] = "Разрядился/В ламповой";
        return $status;
    }

    public function actionGetWorkersData()
    {
        $post = Assistant::GetServerMethod();                                                                             // получение данных со стороны фронтэнда
        $sql_filter = 'true';                                                                                             // фильтр поиска в запросе (стартовый, чтобы все остальные AND нормально отрабатывали
        $sql_filter_charge_id = -1;
        $workers = array();
        if (isset($post['sensor_id']) && $post['sensor_id'] != "") {                                                       // фильтр по сенсорам возвращать через запятую ключи сенсора
            $sql_filter .= ' AND sensor_id IN (' . $post['sensor_id'] . ')';
        }
        if (isset($post['up_to_date']) && $post['up_to_date'] != "") {                                                             // фильтр актуальности данных 0/1
            $sql_filter .= ' AND up_to_date = "' . $post['up_to_date'] . '"';
        }
        if (isset($post['charge_id']) && $post['charge_id'] != "") {                                                             // фильтр статуса заряда
//            $sql_filter .= ' AND charge = "'.$post['charge'].'"';
            $sql_filter_charge_id = $post['charge_id'];
        }
        if (isset($post['place']) && $post['place'] != "") {                                                               // фильтр по названию места
            $sql_filter .= ' AND place_title like "%' . $post['place'] . '%"';
        }
        if (isset($post['department']) && $post['department'] != "") {
            $sql_filter .= ' AND department_title like "%' . str_replace('"', '', $post['department']) . '%"';
        }
        if (isset($post['status']) && $post['status'] != "") {                                                             // фильтр по значению состояния 0/1/2
            $sql_filter .= ' AND status = "' . $post['status'] . '"';
        }
        if (isset($post['search']) and $post['search'] != null) {
            $sql_filter .= ' AND (' . 'fio like "%' . $post['search'] . '%"';
            $sql_filter .= ' OR  ' . 'place_title like "%' . $post['search'] . '%"';
            $sql_filter .= ' OR  ' . 'tabel_number like "%' . $post['search'] . '%")';
        }
        $worker_info_list = (new  Query())
            ->select([
                'worker_id',
                'tabel_number',
                'fio',
                'charge',
                'charge_date_time',
                'date_time',
                'up_to_date',
                'place_title',
                'place_date_time',
                'place_object_id',
                'department_id',
                'department_title',
                'status',
                'parameter439_date_time',
                'parameter386_date_time',
                'parameter387_date_time'
            ])
            ->from('view_worker_info')
            ->where($sql_filter)
            ->orderBy('fio')
            ->all();
//        Assistant::PrintR($worker_info_list);
        $statuses = $this->WorkerStatuses();                                                                            // получаем список статусы для работников.
        foreach ($worker_info_list as $worker)                                                                          // убираем дубликаты
        {
            $worker_id = $worker['worker_id'];
            $workers[$worker_id]['worker_id'] = $worker_id;
            $workers[$worker_id]['tabel_number'] = $worker['tabel_number'];
            $workers[$worker_id]['fio'] = $worker['fio'];
            $charge = $worker['charge'];
            $status_index = -1;
            if ($charge == 1)                                                                                            // если пришел пакет со значением один, значит, что он зарядился. Необходмо уточнить, где именно он зарядился.
            {
                if ($worker['place_object_id'] == 80)                                                                    // если он в ламповой, то выводим, что он в ламповой.
                {
                    $status_index = 0;                                                                                  // 1 это индекс ключа массив статусов
                } else                                                                                                    // иначе если твой объект не ламповой, то значит шахта
                {
                    $status_index = 1;                                                                                  // указываем, что он в ламповой зарядился
                }
            } else if ($charge == 0)                                                                                       // если пришел пакет со значением ноль, значит, что он разрядился. Необходмо уточнить, где именно он зарядился.
            {
                if ($worker['place_object_id'] == 80)                                                                    // если он в ламповой, то выводим, что он в ламповой.
                {
                    $status_index = 3;                                                                                  // Разрядился/В ламповой
                } else                                                                                                    // иначе если твой объект не ламповой, то значит шахта
                {
                    $status_index = 2;                                                                                  // Разрядился/В шахте/Ошибка.
                }
            }
            $workers[$worker_id]['charge_id'] = ($status_index == -1) ? null : $statuses[$status_index]['id'];
            $workers[$worker_id]['charge'] = ($status_index == -1) ? null : $statuses[$status_index]['title'];
            $workers[$worker_id]['charge_date_time'] = date('H:i:s d.m.Y', strtotime($worker['charge_date_time']));
            $workers[$worker_id]['date_time'] = $worker['date_time'];
            $workers[$worker_id]['up_to_date'] = $worker['up_to_date'];
            $workers[$worker_id]['place_title'] = $worker['place_title'];
            $workers[$worker_id]['place_date_time'] = date('H:i:s d.m.Y', strtotime($worker['place_date_time']));
            $workers[$worker_id]['status'] = $worker['status'];
            $workers[$worker_id]['department_id'] = $worker['department_id'];
            $workers[$worker_id]['department_title'] = $worker['department_title'];
            $parameters_datas = array($worker['parameter387_date_time'], $worker['parameter386_date_time'], $worker['parameter439_date_time']);
            $workers[$worker_id]['status_date_time'] = date('H:i:s d.m.Y', strtotime(max($parameters_datas)));
            $workers[$worker_id]['server_current_date_time'] = date("H:i:s d.m.Y");
        }

        /*************************************  Фильтрация массива по статусу спуска        ***************************/
        if ($sql_filter_charge_id != -1) {
            $workers = Assistant::ArrayFilter($workers, 'charge_id', $sql_filter_charge_id);
        }

        $workers_list = array_values($workers);                                                                         // пеериндексируем массив
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            // формат json
        Yii::$app->response->data = $workers_list;
    }

    /**
     * Метод возврата данных
     */

    //вызываемую вьюшку исправил Якимов М.Н. 18.12.2018

    public function actionGetWorkersParameters()
    {
        $post = Assistant::GetServerMethod();                                                                           //получение данных от ajax-запроса
        $sql_filter = '';                                                                                                 //переменная для создания фильтра в MySQL запросе
        $worker_parameter_list_result = array();                                                                        //созадем пустой массив результирующих значений
        $errors = array();                                                                                              //массив ошибок для передачи во фронтэнж
        $flag_filter = -1;                                                                                                //флаг проверки наличия входного параметра от фронт энд, т.к. запрос по всем сенсорам может выполняться очень долго, то поставлено ограничение на один конкретный сенсор
        if (isset($post['worker_id']) && $post['worker_id'] != "") {
            $sql_filter .= ' worker_id=' . $post['worker_id'] . '';                                                     //создание фильтра для вьюшки  по конкретному сенсору, если сенсор не задан то возвращается пустой массив с ошибкой
            $flag_filter = 1;                                                                                             //условие фильтрациии есть, запрос может выполняться
        } else {
            $errors[] = "не задан конкретный воркер айди, запрос не выполнялся";                                             //запись массива ошибок для передачи на фронтэнд
            $flag_filter = 0;                                                                                             //обнуление флага фильтра для обработки случая когда не задан фильтр с фронтэнда
        }

        if ($flag_filter == 1) {
            try {
                $worker_parameter_list = (new Query())//запрос напрямую из базы по вьюшке view_personal_areas
                ->select(
                    [
                        'parameter_id',
                        'parameter_title',      //название параметра справочного
                        'parameter_type_id',    //тип параметра конкретного
                        'unit_title',           //единицы измерения параметра
                        'date_time_work',       //время измерения или вычисления значения конкретного параметра
                        'value',                //значение измеренного или вычисленного конкретного параметра крайнее
                        'handbook_value',       //значение справочного конкретного параметра крайнее
                        'handbook_date_time_work'//время создания справочного конкретного параметра крайнее
                    ])
                    ->from(['view_worker_parameter_value_detail_main'])//представление с крайними значениями конкретного параметра конкретного сенсора
                    ->where($sql_filter)
                    ->orderBy(['parameter_id' => SORT_DESC, 'parameter_type_id' => SORT_DESC])
                    ->all();
                if (!$worker_parameter_list) {
                    $errors[] = "Запрос выполнился, нет данных по запрошенному сенсору в БД";                           //запрос не выполнился по той или иной причине
                } else {
                    $j = -1;                                                                                               //индекс создания результирующего запроса
                    $parameter_id_tek = 0;                                                                                //текущий параметер айди
                    $type_parameter_id_tek = 0;                                                                           //текущий тип параметра 1 справочный, 2 измеренный, 3 вычисленный
                    $parameter_value_array = array();                                                                     //массив значений параметров по типам
                    $parameter_date_array = array();                                                                      //массив дат параметров по типам
                    $worker_parameter_tek = array();                                                                      //списко текущих значений полей сенсора

                    foreach ($worker_parameter_list as $worker_parameter_row) {
                        if ($parameter_id_tek != $worker_parameter_row['parameter_id']) {
                            if ($j != -1) {
                                $worker_parameter_list_result[$j]['parameter_id'] = $worker_parameter_tek['parameter_id'];
                                $worker_parameter_list_result[$j]['parameter_title'] = $worker_parameter_tek['parameter_title'];
                                $worker_parameter_list_result[$j]['unit_title'] = $worker_parameter_tek['unit_title'];
                                $worker_parameter_list_result[$j]['value'] = $parameter_value_array;
                                $worker_parameter_list_result[$j]['date_time'] = $parameter_date_array;
                            }

                            $j++;

                            $worker_parameter_tek['parameter_id'] = $worker_parameter_row['parameter_id'];
                            $worker_parameter_tek['parameter_title'] = $worker_parameter_row['parameter_title'];
                            $worker_parameter_tek['unit_title'] = $worker_parameter_row['unit_title'];

                            $type_parameter_id_tek = $worker_parameter_row['parameter_type_id'];
                            $parameter_id_tek = $worker_parameter_row['parameter_id'];

                            $parameter_value_array[0] = -1;                                                               //справочное значение
                            $parameter_value_array[1] = -1;                                                               //измеренное значение
                            $parameter_value_array[2] = -1;                                                               //вычисленное значение
                            $parameter_date_array[0] = "-1";                                                                //дата ввода справочного значения
                            $parameter_date_array[1] = "-1";                                                                //дата измерения значения
                            $parameter_date_array[2] = "-1";                                                                //дата вычисления значения

                            if ($type_parameter_id_tek == 2) {
                                if ($comp = (float)$worker_parameter_row['value']) {                                          // проверка на тип данных
                                    $parameter_value_array[1] = $this->RoundFloat($worker_parameter_row['value'], 2);
                                    if ($worker_parameter_row['date_time_work'] != -1) $parameter_date_array[1] = date('H:i:s d.m.Y', strtotime($worker_parameter_row['date_time_work']));
                                } else {
                                    $parameter_value_array[1] = $worker_parameter_row['value'];
                                    if ($worker_parameter_row['date_time_work'] != -1) $parameter_date_array[1] = date('H:i:s d.m.Y', strtotime($worker_parameter_row['date_time_work']));
                                }
                            } elseif ($type_parameter_id_tek == 1) {
                                $parameter_value_array[0] = $worker_parameter_row['handbook_value'];
                                if ($worker_parameter_row['handbook_date_time_work'] != -1) $parameter_date_array[0] = date('H:i:s d.m.Y', strtotime($worker_parameter_row['handbook_date_time_work']));
                            } elseif ($type_parameter_id_tek == 3) {
                                $parameter_value_array[2] = $worker_parameter_row['value'];
                                if ($worker_parameter_row['date_time_work'] != -1) $parameter_date_array[2] = date('H:i:s d.m.Y', strtotime($worker_parameter_row['date_time_work']));
                            } else {
                                $errors[] = "Недокументированный тип параметра";
                            }
                        } else {
                            $type_parameter_id_tek = $worker_parameter_row['parameter_type_id'];
                            $parameter_id_tek = $worker_parameter_row['parameter_id'];
                            if ($type_parameter_id_tek == 2) {
                                $parameter_value_array[1] = $this->RoundFloat($worker_parameter_row['value'], 2);
                                if ($worker_parameter_row['date_time_work'] != -1) $parameter_date_array[1] = date('H:i:s d.m.Y', strtotime($worker_parameter_row['date_time_work']));
                            } elseif ($type_parameter_id_tek == 1) {
                                $parameter_value_array[0] = $worker_parameter_row['handbook_value'];
                                if ($worker_parameter_row['handbook_date_time_work'] != -1) $parameter_date_array[0] = date('H:i:s d.m.Y', strtotime($worker_parameter_row['handbook_date_time_work']));
                            } elseif ($type_parameter_id_tek == 3) {
                                $parameter_value_array[2] = $worker_parameter_row['value'];
                                if ($worker_parameter_row['date_time_work'] != -1) $parameter_date_array[2] = date('H:i:s d.m.Y', strtotime($worker_parameter_row['date_time_work']));
                            } else {
                                $errors[] = "Недокументированный тип параметра";
                            }

                        }
                    }
                    //запись последнего значения по строкам
                    $worker_parameter_list_result[$j]['parameter_id'] = $worker_parameter_row['parameter_id'];
                    $worker_parameter_list_result[$j]['parameter_title'] = $worker_parameter_row['parameter_title'];
                    $worker_parameter_list_result[$j]['unit_title'] = $worker_parameter_row['unit_title'];
                    $worker_parameter_list_result[$j]['value'] = $parameter_value_array;
                    $worker_parameter_list_result[$j]['date_time'] = $parameter_date_array;
                }
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        ArrayHelper::multisort($worker_parameter_list_result, 'parameter_title', SORT_ASC);
        $result = array('worker_list' => $worker_parameter_list_result, 'errors' => $errors, 'flag_filter' => $flag_filter);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Метод округляет числа с плавающей точкой типа float, double
     * @param $input_value
     * @param $precision - точность (количестко округляемых чисел после запятой)
     * @return float|string
     */
    public function RoundFloat($input_value, $precision)
    {
        $res = "";
        if ($input_value)                                                                                               //проверяем пришла ли нам строка
        {
            $values_round = explode(",", $input_value);                                                         //разбиваем строку на подстроки
            for ($i = 0; $i < count($values_round); $i++)                                                               //проходим по всем строкам
            {
                if ($i != count($values_round) && $i != 0)                                                              //ставим запятую кроме начала и конца возвращаемой строки
                {
                    $res .= ", ";
                }
                if (is_float((float)$values_round[$i]))                                                                 //если входящее число имеет тип с плавающей точкой
                {
                    $value = round($values_round[$i], $precision);                                                      // то округляем число до нужных нам знаков
                    $res .= $value;
                } else {
                    $res .= $values_round[$i];                                                                          //добавляем тоже число что и было
                }
            }
        }
        return $res;
    }

    public function actionGetDepartments()
    {
        $departments = (new Query())
            ->select('id, title')
            ->from('department')
            ->orderBy('title')
            ->all();
        \Yii::$app->response->format = Response::FORMAT_JSON;                                                   // формат json
        \Yii::$app->response->data = $departments;
    }

}
