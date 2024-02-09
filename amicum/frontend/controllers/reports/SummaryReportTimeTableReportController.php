<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\reports;

use frontend\controllers\Assistant;
use frontend\controllers\handbooks\DepartmentController;
use Yii;
use frontend\models\Department;
use yii\db\Query;
use yii\web\Response;
use yii\web\Controller;


//отчет: Табельный отчет
class SummaryReportTimeTableReportController extends Controller
{

    public function actionIndex()
    {
        $departmentList = Department::find()
            ->select(['title', 'id'])
            ->orderBy(['title' => SORT_ASC])
            ->asArray()->all();
        return $this->render('index', [
            'departmentList' => $departmentList
        ]);
    }

    public $enableCsrfValidation = false;

    public function actionResult()
    {

//        ini_set('max_execution_time', 1200);
//        ini_set('memory_limit', "2000M");
        $query = new Query();
        $post = Assistant::GetServerMethod();                                                                             //метод принимает данные из модели для фильтрации запроса.
        $sql_filter = '';                                                                                               //фильтр запроса, т.к. данных в запросе много, то по умолчанию возвращется только данные за текущие сутки,
        $debug_flag = 0;
        $limit = 100000;                                                                                                   // ограничение получаемых данных
        $limit_start = 0;                                                                                               // начало лимита, то есть откуда начинать
        $recall = -1;                                                                                                   // флаг используется для того, чтобы на фронт отправить флаг на то что есть данные больше чем указанного лиимита, и чтоб обратно отправил запрос
        $date_time = date("Y-m-d");
        $syn_table = array();
        $warnings = array();
        $errors = array();
        $workers_row_count = 0;
        //фильтр по дате если дата не задана то берутся текущие сутки, если задана, то берутся из метода пост
        if (!isset($post['dateStart']) || !isset($post['dateFinish'])) {
            $sql_filter .= ' DATE(date_time_work) = "' . date("Y-m-d H:i:s") . '"';
//            $data_search_start = date("Y-m-d");
//            $data_search_end = date("Y-m-d");
//            if ($debug_flag == 1) $sql_filter = 'date_work between "2018-03-01" and "2018-03-28"';
//            if ($debug_flag == 1) $data_search_start="2018-03-01";
//            if ($debug_flag == 1) $data_search_end="2018-03-28";

        } else {
            if ($post['dateStart'] == $post['dateFinish']) {
                $date_time = $post['dateStart'];
                $sql_filter .= 'DATE(date_time_work) = "' . date('Y-m-d H:i:s', strtotime($post['dateStart'])) . '"';
//                $data_search_start = date('Y-m-d', strtotime($post['dateStart']));
//                $data_search_end = date('Y-m-d', strtotime($post['dateStart']));
            } else if ($post['dateStart'] < $post['dateFinish']) {
                $date_time = $post['dateFinish'];
                $sql_filter .= ' date_time_work >= "' . date('Y-m-d H:i:s', strtotime($post['dateStart'] . " -1 day")) . '" AND date_time_work <= "' . date('Y-m-d H:i:s', strtotime($post['dateFinish'] . " +1 day")) . '" ';
//                $data_search_start = date('Y-m-d', strtotime($post['dateStart']));
//                $data_search_end = date('Y-m-d', strtotime($post['dateFinish']));
            } else if ($post['dateStart'] > $post['dateFinish']) {
                $date_time = $post['dateStart'];
                $sql_filter .= ' date_time_work >= "' . date('Y-m-d H:i:s', strtotime($post['dateFinish'] . " -1 day")) . '" AND date_time_work <= "' . date('Y-m-d H:i:s', strtotime($post['dateStart'] . " +1 day")) . '" ';
//                $data_search_start = date('Y-m-d', strtotime($post['dateFinish']));
//                $data_search_end = date('Y-m-d', strtotime($post['dateStart']));
            }
        }

        //фильтр по подразделению
        if (isset($post['idDepartment']) && $post['idDepartment'] != "")
            $sql_filter .= ' AND ' . 'dep_id=' . $post['idDepartment'] . '';


        //фильтр по самой строке поиска - по буквенный
        if (isset($post['search'])) {
            $sql_filter .= ' AND (' . 'last_name like "%' . $post['search'] . '%"';
            $sql_filter .= ' OR  ' . 'titleObject like "%' . $post['search'] . '%"';

            $sql_filter .= ' OR ' . 'titleCompany like "%' . $post['search'] . '%"';
            $sql_filter .= ' OR ' . 'worker_id like "%' . $post['search'] . '%"';


            $sql_filter .= ' OR ' . 'titleDepartment like "%' . $post['search'] . '%")';
        }

        /******** ПРОВЕРЯЕМ, ЕСТЬ ЛИ ЗАПРОС СО ФРОНТА НА ПЛУЧЕНИЯ ОСТАВШИХСЯ ДАННЫХ **********/
        if (isset($post['start_position']) and $post['start_position'] != "") {
            $limit_start = $post['start_position'];
        }


//        /*******  Проверяем количество записей которые есть в таблицею. Если количество больше указанного лимита, то  отправим фронту флаг на обратный запрос   */
//        $workers_row_count = $query->select('COUNT(id) as count')->from('worker_collection')->where($sql_filter)->one();
//        $workers_row_count = $workers_row_count['count'];
//        if ($workers_row_count > $limit_start and $workers_row_count > $limit)                     // если данных больше че муказанного лимита, то отправим фронту флаг, что есть еще данные и чтоб он отправил запрос обратно
//        {
//            $recall = 1;
//        }

        // var_dump($sql_filter);
        //сам запрос по запретным зонам
        $personal_collections = (new Query())                                                                           //запрос напрямую из базы по вьюшке view_personal_areas
        ->select(                                                                                                       //обязательно сортируем по порядку
            [
                'date_work',
                'date_time_work',
                'last_name as FIO',
                'titleObject as type_worker_title',
                'titleDepartment as department_title',
                'titleCompany as company_title',
                'titlePlace as place_title',
                'worker_id as tabel_number',
                'place_id',
                'smena',
                'worker_id',
                'titleType as type_place_title',
                'titleKind as kind_place_title',
                'status_worker as place_status_title',
                'type_id as type_place_id',
                'kind_id as kind_place_id',
                'main_kind_place_id',
                'dep_id as department_id',
                'stat_id as place_status_id'
            ])
            ->from('worker_collection')
            ->orderBy([
                'worker_id' => SORT_ASC,
                'date_time_work' => SORT_ASC
            ])
            ->where($sql_filter)
//            ->limit($limit)
//            ->offset($limit_start)
            ->all();                                                                                              //текущий воркер ИД для вычисления времени нахождения в зоне
        $limit_start += $limit;                                                                                         // перемещаем начальную позицию для поиска, то есть начало лимита обновляем (полсе получения со фронта нужно прибавить к начало выборки лимит), то есть если тек начало лимита 50, то 50+ лимит, и со следующего раза выборка будет с 550 до limit

//        $warnings[]=$personal_collections;
        $model = array();                                                                                               //сформированный список запретныхз зон


        $i = 0;
        $k = 0;
        $flag_edinich_zapisi = 0;                                                                                         //флаг единичных записей
        $flag_out_lamp = 0;                                                                                               //флаг выхода из ламповой (зарядки светильника)
        $modelTek = array();
        $modelTek['worker_id'] = 0;
        $time_on_summary_duration = 0;                                                                                    //итоговое время работы в случае выхода из ламповой продолжительность
        $time_on_summary_start = 0;                                                                                       //итоговое время работы в случае выхода из ламповой старт
        $time_on_summary_end = 0;                                                                                         //итоговое время работы в случае выхода из ламповой окончание
        $time_on_surface_duration = 0;                                                                                    //время рабочего времени на поверхности продолжительность
        $time_on_surface_start = 0;                                                                                       //время старта рабочего времени на поверхности
        $time_on_surface_end = 0;                                                                                         //время окончания рабочего времени на поверхности
        $time_on_mine_duration = 0;                                                                                       //время рабочего времени подзеного продолжительность
        $time_on_mine_start = 0;                                                                                          //время старта рабочего времени подзеного
        $time_on_mine_end = 0;                                                                                            //время окончания рабочего времени подзеного

        $time_check_in = 0;                                                                                               //время зарядки
        $time_check_out = 0;                                                                                               //время разрядки


        //var_dump($personal_collections);
        foreach ($personal_collections as $personal_collection) {


            if ($modelTek['worker_id'] != $personal_collection['worker_id']) {                                          //выявление изменения человека при переборке
                //текущие данные по воркеру
                $modelTek['date_time_work'] = $personal_collection['date_time_work'];                               //текущее время с датой
                $modelTek['iterator_face'] = 0;                                                                          //итератор забоев для этого человека
                $modelTek['FIO'] = $personal_collection['FIO'];                                                     //ФИО рабочего воркера
                $modelTek['type_worker_title'] = $personal_collection['type_worker_title'];                         //тип объекта (рабочий поверхности/подземный рабочий)
                $modelTek['department_title'] = $personal_collection['company_title'];                                  //название департамента
                $modelTek['company_title'] = '';                                     //название компании
                $modelTek['place_title'] = $personal_collection['place_title'];                                     //Название места
                $modelTek['tabel_number'] = $personal_collection['tabel_number'];                                     //табельный номер
                $modelTek['worker_id'] = $personal_collection['worker_id'];                                         //ИД персонала
                $modelTek['type_place_title'] = $personal_collection['type_place_title'];                           //название типа места (ламповая/надшахтное здание/руддвор/капитанльная выработка/участковая выработка/забой)
                $modelTek['kind_place_title'] = $personal_collection['kind_place_title'];                           //название вида места(поверхность/шахта)
                $modelTek['place_status_title'] = $personal_collection['place_status_title'];                       //название статуса выработки (разрешенная/запрещенная)
                $modelTek['type_place_id'] = $personal_collection['type_place_id'];                                 //ИД типа места (ламповая/надшахтное здание/руддвор/капитанльная выработка/участковая выработка/забой)
                $modelTek['kind_place_id'] = $personal_collection['kind_place_id'];                                 //ИД вида места (поверхность/шахта)
                $modelTek['main_kind_place_id'] = $personal_collection['main_kind_place_id'];                       //ИД главного вида места (kind_object_id)
                $modelTek['place_id'] = $personal_collection['place_id'];                                           //ИД  места
                $modelTek['department_id'] = $personal_collection['department_id'];                                 //ИД департамента
                $modelTek['place_status_id'] = $personal_collection['place_status_id'];                             //ИД статуса выработки/места (запретная/разрешенная) 15/16


                //текущие данные продолжительности по воркеру
                $time_on_summary_duration = 0;                                                                                    //итоговое время работы в случае выхода из ламповой продолжительность
                $time_on_summary_start = 0;                                                                                       //итоговое время работы в случае выхода из ламповой старт
                $time_on_summary_end = 0;                                                                                         //итоговое время работы в случае выхода из ламповой окончание
                $time_on_surface_duration = 0;                                                                                    //время рабочего времени на поверхности продолжительность
                $time_on_surface_start = 0;                                                                                       //время старта рабочего времени на поверхности
                $time_on_surface_end = 0;                                                                                         //время окончания рабочего времени на поверхности
                $time_on_mine_duration = 0;                                                                                       //время рабочего времени подзеного продолжительность
                $time_on_mine_start = 0;                                                                                          //время старта рабочего времени подзеного
                $time_on_mine_end = 0;                                                                                            //время окончания рабочего времени подзеного

                $flag_out_lamp = 0;
                $k = 0;

            } else {
                if ($modelTek['type_place_id'] == 80                                                                     //человек в ламповой
                    and $personal_collection['type_place_id'] == 80) {

                    $time_check_in = $personal_collection['date_time_work'];
                    //echo "из ламповой в ламповую"."/n";

                }
                if ($modelTek['place_id'] != $personal_collection['place_id']) {                                        //изменение места

                    if ($modelTek['type_place_id'] == 80 and $personal_collection['kind_place_id'] == 110 and $personal_collection['type_place_id'] != 80) {                                           //переход из ламповой (80) в надшахтное здание (любой с месторождения вид: 110)
                        //echo  nl2br("вышел из ламповой в надшахтное"."\n");
                        $time_check_in = $personal_collection['date_time_work'];                                          //человек вышел из ламповой в надшахтное
                        if (date('G', strtotime($personal_collection['date_time_work'])) <= 11
                            and $personal_collection['smena'] != 'Смена 1') {
                            $modelTek['date_work'] = strtotime($personal_collection['date_work'] . " -1 day");                                         //текущая дата

                        } else {
                            $modelTek['date_work'] = $personal_collection['date_work'];
                        }
                        $modelTek['smena'] = $personal_collection['smena'];                                                 //название смены (смена 1, смена 2 и т.д.)

                        $time_on_surface_start = $personal_collection['date_time_work'];                                                      //время выхода из ламповой
                        $time_on_summary_start = $personal_collection['date_time_work'];                                             //время старта рабочего времени на поверхности


                        //echo "увидел ламповую вход поверхность------";
                        //echo nl2br(date('G', strtotime($personal_collection['date_time_work']))."\n");
                        //echo nl2br($personal_collection['date_time_work']."\n");
                        //echo nl2br($modelTek['smena']."\n");
                    }
                    if ($modelTek['type_place_id'] == 80                                                                //переход из ламповой сразу в шахту
                        and $personal_collection['main_kind_place_id'] == 2) {
                        //echo nl2br("из ламповой сразу в шахту"."\n");
                        $time_check_in = $personal_collection['date_time_work'];                                          //человек вышел из ламповой
                        if (date('G', strtotime($personal_collection['date_time_work'])) <= 11
                            and $personal_collection['smena'] != 'Смена 1') {
                            $modelTek['date_work'] = strtotime($personal_collection['date_work'] . " -1 day");                                         //текущая дата
                        } else {
                            $modelTek['date_work'] = $personal_collection['date_work'];
                        }

                        $modelTek['smena'] = $personal_collection['smena'];
                        $time_on_summary_start = $personal_collection['date_time_work'];
                        $time_on_mine_start = $personal_collection['date_time_work'];

                        //echo nl2br(date('G', strtotime($personal_collection['date_time_work']))."\n");
                        //echo nl2br($personal_collection['date_time_work']."\n");
                        //echo nl2br($modelTek['smena']."\n");
                        //echo "-----из ламповой сразу в шахту -----";
                    }


                    if (($modelTek['main_kind_place_id'] == 2)                                                             //переход из шахты сразу в ламповую
                        and $personal_collection['type_place_id'] == 80) {

                        //echo nl2br("до входа в процедуру из шахты в лаповую------ ".$k."\n");
                        //var_dump($face);
                        //echo nl2br("------ ".$k."\n");

                        $time_check_out = $personal_collection['date_time_work'];                                          //человек зашел в ламповую

                        $time_on_summary_end = $personal_collection['date_time_work'];
                        $time_on_mine_end = $personal_collection['date_time_work'];

                        if ($time_on_summary_start != 0) $time_on_summary_duration += strtotime($time_on_summary_end) - strtotime($time_on_summary_start); //расчет продолжительности нахождения на смене

                        if ($time_on_mine_start != 0) $time_on_mine_duration += strtotime($time_on_mine_end) - strtotime($time_on_mine_start);          //расчет продолжительности нахождения в шахте


                        if ($time_on_summary_duration / 60 > 15)                                                             //если время отсутствия в ламповой больше 15 минут, то считаем ходкой
                        {
                            $model[$i] = array();
                            $model[$i] ['iterator'] = $i + 1;
                            $model[$i] ['iterator_face'] = $modelTek['iterator_face'];
//                            $model[$i] ['date_work'] = $modelTek['date_work'];
                            $model[$i] ['date_work'] = date("d-m-Y", strtotime($modelTek['date_work']));
                            $model[$i] ['day'] = date("d", strtotime($modelTek['date_work']));
                            $model[$i] ['FIO'] = $modelTek['FIO'];
                            $model[$i] ['type_worker_title'] = $modelTek['type_worker_title'];
                            $model[$i] ['tabel_number'] = $modelTek['tabel_number'];
                            $model[$i] ['department_title'] = $modelTek['department_title'];
                            $model[$i] ['company_title'] = $modelTek['company_title'];
                            $model[$i] ['place_title'] = $modelTek['place_title'];
                            $model[$i] ['smena'] = $modelTek['smena'];
                            $model[$i] ['worker_id'] = $modelTek['worker_id'];
                            $model[$i] ['department_id'] = $modelTek['department_id'];
                            $model[$i]['time_on_surface'] = number_format($time_on_surface_duration / 60, 0, ',', ' ');                                 //время на поверхности
                            $model[$i]['time_on_mine'] = number_format($time_on_mine_duration / 60, 0, ',', ' ');                                       //время нахождения в шахте
                            $model[$i]['time_on_summary'] = number_format($time_on_summary_duration / 3600, 0, ',', ' ');                                 //время суммарное в часах
                            $model[$i]['time_on_summary_source'] = $time_on_summary_duration / 60;
                            $model[$i]['time_on_mine_source'] = $time_on_mine_duration / 60;                                 //время в шахте
                            $model[$i]['time_on_surface_source'] = $time_on_surface_duration / 60;                                 //время на поверхности

                            $i++;
                            $k = 0;

                            $time_on_summary_duration = 0;                                                                                    //итоговое время работы в случае выхода из ламповой продолжительность
                            $time_on_summary_start = 0;                                                                                       //итоговое время работы в случае выхода из ламповой старт
                            $time_on_summary_end = 0;                                                                                         //итоговое время работы в случае выхода из ламповой окончание
                            $time_on_surface_duration = 0;                                                                                    //время рабочего времени на поверхности продолжительность
                            $time_on_surface_start = 0;                                                                                       //время старта рабочего времени на поверхности
                            $time_on_surface_end = 0;                                                                                         //время окончания рабочего времени на поверхности
                            $time_on_mine_duration = 0;                                                                                       //время рабочего времени подзеного продолжительность
                            $time_on_mine_start = 0;                                                                                          //время старта рабочего времени подзеного
                            $time_on_mine_end = 0;                                                                                            //время окончания рабочего времени подзеного

                            //echo nl2br("после процедуры из шахты в лаповую------ ".$k."\n");
                            //var_dump($face);
                            //echo nl2br("------ ".$k."\n");
                        }

                    }

                    if (($modelTek['kind_place_id'] == 110 and $modelTek['type_place_id'] != 80)
                        and $personal_collection['type_place_id'] == 80) {                                          //переход из надшахтного здания в ламповую (80)

                        $time_check_out = $personal_collection['date_time_work'];                                       //человек зашел в ламповую

                        //человек вернулся в ламповую
                        $time_on_surface_end = $personal_collection['date_time_work'];
                        $time_on_summary_end = $personal_collection['date_time_work'];
                        if ($time_on_surface_start != 0) $time_on_surface_duration +=
                            strtotime($time_on_surface_end) - strtotime($time_on_surface_start);                        //расчет продолжительности нахождения на поверхности

                        if ($time_on_summary_start != 0) $time_on_summary_duration +=
                            strtotime($time_on_summary_end) - strtotime($time_on_summary_start);                        //расчет продолжительности рабочей смены
                        //echo "вышел с поверхности -----";


                        if ($time_on_summary_duration / 60 > 15)                                                             //если время отсутствия в ламповой болше 15 минут, то считаем ходкой
                        {
                            $model[$i] = array();
                            $modelTek ['iterator'] = $i + 1;
                            $model[$i] ['iterator_face'] = $modelTek['iterator_face'];
//                            $model[$i] ['date_work'] = $modelTek['date_work'];
                            $model[$i] ['date_work'] = date("d-m-Y", strtotime($modelTek['date_work']));
                            $model[$i] ['day'] = date("d", strtotime($modelTek['date_work']));
                            $model[$i] ['FIO'] = $modelTek['FIO'];
                            $model[$i] ['type_worker_title'] = $modelTek['type_worker_title'];
                            $model[$i] ['department_title'] = $modelTek['department_title'];
                            $model[$i] ['company_title'] = $modelTek['company_title'];
                            $model[$i] ['tabel_number'] = $modelTek['tabel_number'];
                            $model[$i] ['place_title'] = $modelTek['place_title'];
                            $model[$i] ['smena'] = $modelTek['smena'];
                            $model[$i] ['worker_id'] = $modelTek['worker_id'];
                            $model[$i] ['department_id'] = $modelTek['department_id'];
                            $model[$i]['time_on_surface'] = number_format($time_on_surface_duration / 60, 0, ',', ' ');                                 //время на поверхности
                            $model[$i]['time_on_mine'] = number_format($time_on_mine_duration / 60, 0, ',', ' ');                                       //время нахождения в шахте
                            $model[$i]['time_on_summary'] = number_format($time_on_summary_duration / 3600, 0, ',', ' ');                                 //время суммарное в часах
                            $model[$i]['time_on_summary_source'] = $time_on_summary_duration / 60;                                 //время суммарное
                            $model[$i]['time_on_mine_source'] = $time_on_mine_duration / 60;                                 //время в шахте
                            $model[$i]['time_on_surface_source'] = $time_on_surface_duration / 60;                                 //время на поверхности
                            $i++;
                            $k = 0;

                            $time_on_summary_duration = 0;                                                                                    //итоговое время работы в случае выхода из ламповой продолжительность
                            $time_on_summary_start = 0;                                                                                       //итоговое время работы в случае выхода из ламповой старт
                            $time_on_summary_end = 0;                                                                                         //итоговое время работы в случае выхода из ламповой окончание
                            $time_on_surface_duration = 0;                                                                                    //время рабочего времени на поверхности продолжительность
                            $time_on_surface_start = 0;                                                                                       //время старта рабочего времени на поверхности
                            $time_on_surface_end = 0;                                                                                         //время окончания рабочего времени на поверхности
                            $time_on_mine_duration = 0;                                                                                       //время рабочего времени подзеного продолжительность
                            $time_on_mine_start = 0;                                                                                          //время старта рабочего времени подзеного
                            $time_on_mine_end = 0;                                                                                            //время окончания рабочего времени подзеного


                        }
                    }


                    if (($modelTek['kind_place_id'] == 110 and $modelTek['type_place_id'] != 80)                           //переход из надшахтного здания в в подземные горные выработки
                        and $personal_collection['main_kind_place_id'] == 2) {

                        $time_on_surface_end = $personal_collection['date_time_work'];
                        if ($time_on_surface_start != 0) $time_on_surface_duration += strtotime($time_on_surface_end) - strtotime($time_on_surface_start);          //расчет продолжительности нахождения на поверхности

                        $time_on_mine_start = $personal_collection['date_time_work'];
                        //echo "зашел в шахту с поверхности -------";
                    }

                    if (($personal_collection['kind_place_id'] == 110 and $personal_collection['type_place_id'] != 80)     //переход из подземных горных выработок в надшахтное здание
                        and $modelTek['main_kind_place_id'] == 2) {

                        $time_on_surface_start = $personal_collection['date_time_work'];
                        $time_on_mine_end = $personal_collection['date_time_work'];
                        if ($time_on_mine_start != 0) $time_on_mine_duration += strtotime($time_on_mine_end) - strtotime($time_on_mine_start);         //расчет продолжительности нахождения в шахте

                        //echo "вышел из шахты на поверхность -----";
                    }

                    //echo nl2br("при отсечке тип текущее и предыдущие места------ ".$k."\n");
                    //var_dump($modelTek['type_place_title']);
                    //echo nl2br("------ ".$k."\n");
                    //var_dump($personal_collection['type_place_title']);
                    //echo nl2br("------ ".$k."\n");
                    $modelTek['date_time_work'] = $personal_collection['date_time_work'];                               // текущее время с датой
                    $modelTek['FIO'] = $personal_collection['FIO'];                                                     // ФИО рабочего воркера
                    $modelTek['type_worker_title'] = $personal_collection['type_worker_title'];                         // тип объекта (рабочий поверхности/подземный рабочий)
                    $modelTek['department_title'] = $personal_collection['company_title'];                              // название департамента
                    $modelTek['company_title'] = '';                                                   // название компании
                    $modelTek['place_title'] = $personal_collection['place_title'];                                     // Название места
                    $modelTek['worker_id'] = $personal_collection['worker_id'];                                         // ИД персонала
                    $modelTek['type_place_title'] = $personal_collection['type_place_title'];                           // название типа места (ламповая/надшахтное здание/руддвор/капитанльная выработка/участковая выработка/забой)
                    $modelTek['tabel_number'] = $personal_collection['tabel_number'];                                   // табельный номер
                    $modelTek['kind_place_title'] = $personal_collection['kind_place_title'];                           // название вида места(поверхность/шахта)
                    $modelTek['place_status_title'] = $personal_collection['place_status_title'];                       // название статуса выработки (разрешенная/запрещенная)
                    $modelTek['type_place_id'] = $personal_collection['type_place_id'];                                 // типа места (ламповая/надшахтное здание/руддвор/капитанльная выработка/участковая выработка/забой)
                    $modelTek['kind_place_id'] = $personal_collection['kind_place_id'];                                 // ИД вида места (поверхность/шахта)
                    $modelTek['place_id'] = $personal_collection['place_id'];                                           // ИД  места
                    $modelTek['department_id'] = $personal_collection['department_id'];                                 // ИД департамента
                    $modelTek['main_kind_place_id'] = $personal_collection['main_kind_place_id'];                       // ИД главного вида места (kind_object_id)
                    $modelTek['place_status_id'] = $personal_collection['place_status_id'];                             // ИД статуса выработки/места (запретная/разрешенная) 15/16
                    //echo "------обновил текущую модель------";
                    $flag_edinich_zapisi += 1;
                    //echo nl2br("Отсечка------ ".$k."\n");
                    //var_dump($face);
                    //echo nl2br("------ ".$k."\n");

                }
            }
        }
        unset($personal_collections);
        //echo nl2br("все посчиталось и выход------ ".$k."\n");
        //var_dump($face);
        //echo nl2br("------ ".$k."\n");
        /*$model_final=array();

        ЭТА ЧАСТЬ КОДА РАБОТАЕТ, НО ЗА НЕНАДОБНОСТЬЮ СКРЫТА
        ОНА ВОЗВРАЩАЕТ СРАЗУ ГОТОВЫ ДАННЫЕ ДЛЯ ОТЧЕТА ТАБЕЛЬНЫЙ ОТЧЕТ

        $p=0;
        for($j=0;$j<$i;$j++)
        {
            if($j==0) {                                                                                                 //первый заход определение переменных
                $modelTek['date_work']=$model[$j] ['date_work'];
                $modelTek ['FIO']=$model[$j] ['FIO'];
                $modelTek ['tabel_number']=$model[$j] ['tabel_number'];
                $modelTek ['department_title']=$model[$j] ['department_title'];
                $modelTek ['company_title']=$model[$j] ['company_title'];
                $modelTek ['smena']=$model[$j] ['smena'];
                $modelTek ['worker_id']=$model[$j] ['worker_id'];
                $modelTek ['department_id']=$model[$j] ['department_id'];
                $modelTek ['time_on_summary']=$model[$j] ['time_on_summary'];
                if(date('Y-m-d',strtotime($modelTek['date_work']))>date('Y-m-d',strtotime($data_search_start))){

                    for($k=date('Y-m-d',strtotime($data_search_start));$k<date('Y-m-d',strtotime($modelTek['date_work']));$k=date('Y-m-d',strtotime($k . " +1 day"))) {
                        $model_final[$p] ['date_work'] = $k;
                        $model_final[$p] ['FIO']=$model[$j] ['FIO'];
                        $model_final[$p] ['tabel_number']=$model[$j] ['tabel_number'];
                        $model_final[$p] ['department_title']=$model[$j] ['department_title'];
                        $model_final[$p] ['company_title']=$model[$j] ['company_title'];
                        $model_final[$p] ['smena']=$model[$j] ['smena'];
                        $model_final[$p] ['worker_id']=$model[$j] ['worker_id'];
                        $model_final[$p] ['department_id']=$model[$j] ['department_id'];
                        $model_final[$p] ['time_on_summary']=0;
                        $p++;
                    }
                }
                //if ($debug_flag == 1) echo nl2br("первый заход в массив------ " . var_dump($model_final) . "\n");
                //if ($debug_flag == 1) echo nl2br("------------------ " . "\n");
                //if ($debug_flag == 1) echo nl2br("------------------- data_search_start " .$data_search_start. "\n");
                //if ($debug_flag == 1) echo nl2br("------------------- " .$k. "\n");
                //if ($debug_flag == 1) echo nl2br("------------------- добавлено пустых дат" .$p. "\n");

                $model_final[$p] ['date_work'] = $model[$j] ['date_work'];
                $model_final[$p] ['FIO']=$model[$j] ['FIO'];
                $model_final[$p] ['tabel_number']=$model[$j] ['tabel_number'];
                $model_final[$p] ['department_title']=$model[$j] ['department_title'];
                $model_final[$p] ['company_title']=$model[$j] ['company_title'];
                $model_final[$p] ['smena']=$model[$j] ['smena'];
                $model_final[$p] ['worker_id']=$model[$j] ['worker_id'];
                $model_final[$p] ['department_id']=$model[$j] ['department_id'];
                $model_final[$p] ['time_on_summary']=$model[$j] ['time_on_summary'];

            }
            if($modelTek['worker_id']!=$model[$j] ['worker_id'])                                                        //воркер поменялся
            {

                //старый воркер сделать запись


                $modelTek['date_work']=$model[$j] ['date_work'];
                $modelTek ['FIO']=$model[$j] ['FIO'];
                $modelTek ['tabel_number']=$model[$j] ['tabel_number'];
                $modelTek ['department_title']=$model[$j] ['department_title'];
                $modelTek ['company_title']=$model[$j] ['company_title'];
                $modelTek ['smena']=$model[$j] ['smena'];
                $modelTek ['worker_id']=$model[$j] ['worker_id'];
                $modelTek ['department_id']=$model[$j] ['department_id'];
                $modelTek ['time_on_summary']=$model[$j] ['time_on_summary'];
                if(date('Y-m-d',strtotime($modelTek['date_work']))>date('Y-m-d',strtotime($data_search_start))){

                    for($k=date('Y-m-d',strtotime($data_search_start));$k<date('Y-m-d',strtotime($modelTek['date_work']));$k=date('Y-m-d',strtotime($k . " +1 day"))) {
                        $model_final[$p] ['date_work'] = $k;
                        $model_final[$p] ['FIO']=$model[$j] ['FIO'];
                        $model_final[$p] ['tabel_number']=$model[$j] ['tabel_number'];
                        $model_final[$p] ['department_title']=$model[$j] ['department_title'];
                        $model_final[$p] ['company_title']=$model[$j] ['company_title'];
                        $model_final[$p] ['smena']=$model[$j] ['smena'];
                        $model_final[$p] ['worker_id']=$model[$j] ['worker_id'];
                        $model_final[$p] ['department_id']=$model[$j] ['department_id'];
                        $model_final[$p] ['time_on_summary']=0;
                        $p++;
                    }
                }
                //if ($debug_flag == 1) echo nl2br("Воркер сменился------ " . var_dump($model_final) . "\n");
                //if ($debug_flag == 1) echo nl2br("------------------ " . "\n");
                //if ($debug_flag == 1) echo nl2br("------------------- data_search_start " .$data_search_start. "\n");
                //if ($debug_flag == 1) echo nl2br("------------------- " .$k. "\n");
                //if ($debug_flag == 1) echo nl2br("------------------- добавлено пустых дат" .$p. "\n");

                $model_final[$p] ['date_work'] = $model[$j] ['date_work'];
                $model_final[$p] ['FIO']=$model[$j] ['FIO'];
                $model_final[$p] ['tabel_number']=$model[$j] ['tabel_number'];
                $model_final[$p] ['department_title']=$model[$j] ['department_title'];
                $model_final[$p] ['company_title']=$model[$j] ['company_title'];
                $model_final[$p] ['smena']=$model[$j] ['smena'];
                $model_final[$p] ['worker_id']=$model[$j] ['worker_id'];
                $model_final[$p] ['department_id']=$model[$j] ['department_id'];
                $model_final[$p] ['time_on_summary']=$model[$j] ['time_on_summary'];
            }
            else
            {
                if(date('Y-m-d',strtotime($modelTek['date_work']))==date('Y-m-d',strtotime($model[$j] ['date_work'])))                 //суммируем в рамках одного воркера инфу по текущей дате
                {
                    $model_final[$p] ['time_on_summary'] += $model[$j] ['time_on_summary'];
                }


                if(date('Y-m-d',strtotime($modelTek['date_work']))!=date('Y-m-d',strtotime($model[$j] ['date_work'])))                  //поменялась дата
                {
                    if(date('Y-m-d',strtotime($modelTek['date_work']. " +1 day"))!=date('Y-m-d',strtotime($model[$j] ['date_work'])))                  //есть пробел между датами
                        for($k=date('Y-m-d',strtotime($modelTek['date_work']. " +1 day"));$k<date('Y-m-d',strtotime($model[$j] ['date_work']));$k=date('Y-m-d',strtotime($k . " +1 day"))) {
                            $model_final[$p] ['date_work'] = $k;
                            $model_final[$p] ['FIO']=$model[$j] ['FIO'];
                            $model_final[$p] ['tabel_number']=$model[$j] ['tabel_number'];
                            $model_final[$p] ['department_title']=$model[$j] ['department_title'];
                            $model_final[$p] ['company_title']=$model[$j] ['company_title'];
                            $model_final[$p] ['smena']=$model[$j] ['smena'];
                            $model_final[$p] ['worker_id']=$model[$j] ['worker_id'];
                            $model_final[$p] ['department_id']=$model[$j] ['department_id'];
                            $model_final[$p] ['time_on_summary']=0;
                            $p++;
                        }
                    $model_final[$p] ['date_work'] = $model[$j] ['date_work'];
                    $model_final[$p] ['FIO']=$model[$j] ['FIO'];
                    $model_final[$p] ['tabel_number']=$model[$j] ['tabel_number'];
                    $model_final[$p] ['department_title']=$model[$j] ['department_title'];
                    $model_final[$p] ['company_title']=$model[$j] ['company_title'];
                    $model_final[$p] ['smena']=$model[$j] ['smena'];
                    $model_final[$p] ['worker_id']=$model[$j] ['worker_id'];
                    $model_final[$p] ['department_id']=$model[$j] ['department_id'];
                    $model_final[$p] ['time_on_summary']=$model[$j] ['time_on_summary'];
                }


                $modelTek['date_work']=$model[$j] ['date_work'];                                                        //обновляем текущие данные для следующего прохода
                $modelTek ['FIO']=$model[$j] ['FIO'];
                $modelTek ['tabel_number']=$model[$j] ['tabel_number'];
                $modelTek ['department_title']=$model[$j] ['department_title'];
                $modelTek ['company_title']=$model[$j] ['company_title'];
                $modelTek ['smena']=$model[$j] ['smena'];
                $modelTek ['worker_id']=$model[$j] ['worker_id'];
                $modelTek ['department_id']=$model[$j] ['department_id'];
                $modelTek ['time_on_summary']=$model[$j] ['time_on_summary'];
            }
        }*/


        //поиск по страничке
        if (isset($post['search']) and $post['search'] != "") {
//            echo "Search in";
            $search_title = $post['search'];
            for ($j = 0; $j < $i; $j++) {
                $model[$j] ['iterator'] = $j + 1;
//                $model[$j] ['date_work'] = $model[$j]['date_work'];
                $model[$j] ['date_work'] = date("d-m-Y", strtotime($model [$j] ['date_work']));
                $model[$j] ['FIO'] = Assistant::MarkSearched($search_title, $model[$j]['FIO']);
                $model[$j] ['type_worker_title'] = Assistant::MarkSearched($search_title, $model[$j]['type_worker_title']);
                $model[$j] ['department_title'] = Assistant::MarkSearched($search_title, $model[$j]['department_title']);
                $model[$j] ['company_title'] = Assistant::MarkSearched($search_title, $model[$j]['company_title']);
                $model[$j] ['tabel_number'] = Assistant::MarkSearched($search_title, $model[$j]['tabel_number']);
            }
//            echo json_encode($model);

        }
        foreach ($model as $worker_day) {
            if (!isset($tabel_worker[$worker_day['worker_id']])) {
                $tabel_worker[$worker_day['worker_id']] = $worker_day;
                $tabel_worker[$worker_day['worker_id']]['time_on_mine']=0;
                $tabel_worker[$worker_day['worker_id']]['time_on_summary']=0;
                $tabel_worker[$worker_day['worker_id']]['time_on_surface']=0;
                for ($i = 0; $i < 31; $i++) {
                    $tabel_worker[$worker_day['worker_id']]['days'][$i] = 0;
                }
            }

            $tabel_worker[$worker_day['worker_id']]['days'][$worker_day['day'] - 1] += $worker_day['time_on_summary_source'];
            $tabel_worker[$worker_day['worker_id']]['time_on_mine'] += $worker_day['time_on_mine_source'];
            $tabel_worker[$worker_day['worker_id']]['time_on_summary'] += $worker_day['time_on_summary_source'];
            $tabel_worker[$worker_day['worker_id']]['time_on_surface'] += $worker_day['time_on_surface_source'];
        }

        if(isset($tabel_worker)) {
            unset($model);
            foreach ($tabel_worker as $worker) {
                $model[] = $worker;
            }
        }

        if(!$model) {
            $errors[]="Нет данных для отображения";
        }
        $result = array('result' => $model, 'errors' => $errors, 'warnings' => $warnings, 'recall' => $recall, 'start_position' => $limit_start,
            'syn-report' => $syn_table, 'record_count' => $workers_row_count);
        unset($model);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }
}

