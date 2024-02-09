<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\reports;


use frontend\controllers\Assistant;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Department;
use frontend\models\Place;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;
use yii\web\Response;

/**
 * Отчет: Время нахождения персонала по зонам
 * Class SummaryReportTimeSpentController
 * отчет: Время нахождения персонала по зонам
 * @package app\controllers
 */
class SummaryReportTimeSpentController extends Controller
{

    // actionTimeSpentByLampStartEnd - метод построения сводного отчета начала и окончания смены по шахтам за заданный период

    public function actionIndex()
    {
        $faceList = Place::find()
            ->select(['title', 'id'])
            ->where('object_id in (79)')
            ->orderBy(['title' => SORT_ASC])
            ->asArray()->all();
        $departmentList = Department::find()
            ->select(['title', 'id'])
            ->orderBy(['title' => SORT_ASC])
            ->asArray()->all();
        $placeList = Place::find()
            ->select(['title', 'id'])
            ->orderBy(['title' => SORT_ASC])
            ->asArray()->all();
        return $this->render('index', [
            'departmentList' => $departmentList,
            'placeList' => $placeList,
            'faceList' => $faceList
        ]);
    }

    public $enableCsrfValidation = false;

    // запуск отчета: http://127.0.0.1/reports/summary-report-time-spent
    public function actionResult()
    {
        $debug_flag = 0;
//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', '10000M');
        $limit = 100000;
        $worker_collections_row_count = 0;
        $prew_recall = $limit;
        $result = array();
        $status = 1;
        $errors = array();
        $warnings = array();
        $post = Assistant::GetServerMethod();                                                                           // метод принимает данные из модели для фильтрации запроса.
        $sql_filter = '';                                                                                               // фильтр запроса, т.к. данных в запросе много, то по умолчанию возвращется только данные за текущие сутки,
        $limit_start = 0;                                                                                               // в этой переменной хранится последний id, по которому была сделана выборка.
        $limit_flag = -1;                                                                                               // флаг указывающий на то что есть данные больше чем указанного лимита, и фронт может обратно отправить запрос на получение оставшихся данных
        $date_time = date('Y-m-d H:i:s');
        $syn_table = array();
        $warnings[] = "SummaryReportTimeSpentController:actionResult. Начал выполнять метод.";
        isset($post['data_exists']) ? $data_exists = $post['data_exists'] : $data_exists = 0;                           // Если с фронта получали data_exists запоминаем значение в переменной если не получали ставим 0

        try {
            $data_search_start = date('Y-m-d H:i:s');
            $data_search_end = date('Y-m-d H:i:s');
            //фильтр по дате если дата не задана то берутся текущие сутки, если задана, то берутся из метода пост
            if (!isset($post['dateStart'], $post['dateFinish'])) {
                $sql_filter .= " DATE(date_time_work) = '" . date('Y-m-d H:i:s') . "'";
                $data_search_start = date('Y-m-d H:i:s');
                $data_search_end = date('Y-m-d H:i:s');
            } else {
                $formatted_date_start = date('Y-m-d H:i:s', strtotime($post['dateStart']));
                $formatted_date_end = date('Y-m-d H:i:s', strtotime($post['dateFinish']));
                if ($formatted_date_start == $formatted_date_end) {
                    $sql_filter .= "DATE(date_time_work) = '" . date('Y-m-d H:i:s', strtotime($post['dateStart'])) . "'";
                    $data_search_start = date('Y-m-d H:i:s', strtotime($post['dateStart']));
                    $data_search_end = date('Y-m-d H:i:s', strtotime($post['dateStart']));
                    $date_time = $post['dateStart'];
                } else if ($formatted_date_start < $formatted_date_end) {
                    $sql_filter .= " date_time_work >= '" . date('Y-m-d H:i:s', strtotime($post['dateStart'])) . "' AND  date_time_work <='" . date('Y-m-d H:i:s', strtotime($post['dateFinish'])) . "'";
                    $data_search_start = date('Y-m-d H:i:s', strtotime($post['dateStart']));
                    $data_search_end = date('Y-m-d H:i:s', strtotime($post['dateFinish']));
                    $date_time = $post['dateFinish'];
                } else if ($formatted_date_start > $formatted_date_end) {
                    $date_time = $post['dateStart'];
                    $sql_filter .= " date_time_work >= '" . date('Y-m-d H:i:s', strtotime($post['dateFinish'])) . "' AND date_time_work <= '" . date('Y-m-d H:i:s', strtotime($post['dateStart'])) . "'";
                    $data_search_start = date('Y-m-d H:i:s', strtotime($post['dateFinish']));
                    $data_search_end = date('Y-m-d H:i:s', strtotime($post['dateStart']));
                }
            }


            //фильтр по типу объекта/воркеру
            if (isset($post['type_worker_id']) && $post['type_worker_id'] != '')
                $sql_filter .= ' AND ' . 'type_worker_id=' . (int)$post['type_worker_id'] . '';

            //фильтр по подразделению
            if (isset($post['idDepartment']) && $post['idDepartment'] != '')
                $sql_filter .= ' AND ' . 'department_id=' . (int)$post['idDepartment'] . '';

            //фильтр по выработки
            if (isset($post['idPlace1']) && $post['idPlace1'] != '')
                $sql_filter .= ' AND ' . 'place_id=' . (int)$post['idPlace1'] . '';


            //фильтр по самой строке поиска - по буквенный
            if (isset($post['search']) && $post['search'] != '') {
                $sql_filter .= ' AND (' . 'FIO like "%' . $post['search'] . '%"';
//                $sql_filter .= ' OR  ' . 'type_worker_title like "%' . $post['search'] . '%"';
//                $sql_filter .= ' OR ' . 'company_title like "%' . $post['search'] . '%"';
//                $sql_filter .= ' OR ' . 'department_title like "%' . $post['search'] . '%")';
            }

            /******** ПРОВЕРЯЕМ, ЕСТЬ ЛИ ЗАПРОС СО ФРОНТА НА ПЛУЧЕНИЯ ОСТАВШИХСЯ ДАННЫХ **********/
            if (isset($post['start_position']) && $post['start_position'] != '') {
                $limit_start = $post['start_position'];
            }

//            /*******  Проверяем количество записей которые есть в таблицею. Если количество больше указанного лимита, то  отправим фронту флаг на обратный запрос   */
//            $worker_collections_row_count = (new Query())->select('COUNT(id) as count')->from('summary_report_time_spent')->where($sql_filter)->one();
//            $worker_collections_row_count = $worker_collections_row_count['count'];
//            if ($worker_collections_row_count > $limit && $worker_collections_row_count > $limit_start)               // если данных больше чем указанного лимита, то отправим фронту флаг, что есть еще данные и чтоб он отправил запрос обратно
//            {
//                $limit_flag = 1;
//            }
            //сам запрос по запретным зонам
            $warnings[] = "SummaryReportTimeSpentController:actionResult. Фильтр.";
            $warnings[] = $sql_filter;

            $personal_collections = (new Query())->select([                                                                    // запрос напрямую из базы по вьюшке view_personal_areas //обязательно сортируем по порядку
                'date_work',
                'date_time_work',
                'last_name as FIO',
                'titleObject as type_worker_title',
                'titleDepartment as department_title',
                'titleCompany as company_title',
                'titlePlace as place_title',
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
                ->where($sql_filter)//текущий воркер ИД для вычисления времени нахождения в зоне
//                ->andWhere('worker_id=2924401')
                ->orderBy([
                    'worker_id' => SORT_ASC,
                    'date_time_work' => SORT_ASC
                ])
//                ->offset($limit_start)
//                ->limit($limit)
                ->all();
            $warnings[] = "Пред обработка. Количество обрабатываемых данных: " . count($personal_collections);
            $warnings[] = $personal_collections;
            $limit_start += $limit;                                                                                     // отправим во фронт последнюю позицию (то есть откуда в след раз начинать limit начало)

            $warnings[] = "SummaryReportTimeSpentController:actionResult. Получил данные для анализа:.";
            $model = array();                                                                                           // сформированный список запретных зон
            $faceTek = array();
            $face = array();
            $i = 0;
            $k = 0;
            $flag_out_lamp = 0;                                                                                         // флаг выхода из ламповой (зарядки светильника)
            $modelTek = array();
            $modelTek['worker_id'] = 0;
            $time_on_summary_duration = 0;                                                                              // итоговое время работы в случае выхода из ламповой продолжительность
            $time_on_summary_start = 0;                                                                                 // итоговое время работы в случае выхода из ламповой старт
            $time_on_summary_end = 0;                                                                                   // итоговое время работы в случае выхода из ламповой окончание
            $time_on_surface_duration = 0;                                                                              // время рабочего времени на поверхности продолжительность
            $time_on_surface_start = 0;                                                                                 // время старта рабочего времени на поверхности
            $time_on_surface_end = 0;                                                                                   // время окончания рабочего времени на поверхности
            $time_on_mine_duration = 0;                                                                                 // время рабочего времени подземного продолжительность
            $time_on_mine_start = 0;                                                                                    // время старта рабочего времени подземного
            $time_on_mine_end = 0;                                                                                      // время окончания рабочего времени подземного
            $time_on_face_start = 0;                                                                                    // время начала работы в забое
            $time_on_face_end = 0;                                                                                      // время окончания работы в забое
            $time_on_face_duration = 0;                                                                                 // время нахождения в забое продолжительность
            $time_to_face_start = 0;                                                                                    // время следования в забой начало
            $time_to_face_end = 0;                                                                                      // время следования в забой окончание
            $time_to_face_duration = 0;                                                                                 // время следования в забой
            $time_out_face_start = 0;                                                                                   // время следования из забоя начало
            $time_out_face_end = 0;                                                                                     // время следования из забоя окончание
            $time_out_face_duration = 0;                                                                                // время следования из забоя


            foreach ($personal_collections as $personal_collection) {

                if ($modelTek['worker_id'] != $personal_collection['worker_id']) {                                      // выявление изменения человека при переборке
                    //текущие данные по воркеру
                    $modelTek['date_time_work'] = $personal_collection['date_time_work'];                               // текущее время с датой
                    $modelTek['iterator_face'] = 0;                                                                     // итератор забоев для этого человека
                    $modelTek['FIO'] = $personal_collection['FIO'];                                                     // ФИО рабочего воркера
                    $modelTek['type_worker_title'] = $personal_collection['type_worker_title'];                         // тип объекта (рабочий поверхности/подземный рабочий)
                    $modelTek['department_title'] = $personal_collection['company_title'];                              // название департамента
                    $modelTek['company_title'] = '"';                                                                   // название компании
                    $modelTek['place_title'] = $personal_collection['place_title'];                                     // Название места
                    $modelTek['worker_id'] = $personal_collection['worker_id'];                                         // ИД персонала
                    $modelTek['type_place_title'] = $personal_collection['type_place_title'];                           // название типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                    $modelTek['kind_place_title'] = $personal_collection['kind_place_title'];                           // название вида места(поверхность/шахта)
                    $modelTek['place_status_title'] = $personal_collection['place_status_title'];                       // название статуса выработки (разрешенная/запрещенная)
                    $modelTek['type_place_id'] = $personal_collection['type_place_id'];                                 // ИД типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                    $modelTek['kind_place_id'] = $personal_collection['kind_place_id'];                                 // ИД вида места (поверхность/шахта)
                    $modelTek['main_kind_place_id'] = $personal_collection['main_kind_place_id'];                       // ИД главного вида места (kind_object_id)
                    $modelTek['place_id'] = $personal_collection['place_id'];                                           // ИД места
                    $modelTek['department_id'] = $personal_collection['department_id'];                                 // ИД департамента
                    $modelTek['place_status_id'] = $personal_collection['place_status_id'];                             // ИД статуса выработки/места (запретная/разрешенная) 15/16

                    //текущие данные по нахождению воркера в забое
                    $faceTek['date_work'] = 0;                                                                          // текущая дата
                    $faceTek['place_title'] = 0;                                                                        // Название места
                    $faceTek['smena'] = 0;                                                                              // название смены (смена 1, смена 2 и т.д.)
                    $faceTek['type_place_title'] = 0;                                                                   // название типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                    $faceTek['kind_place_title'] = 0;                                                                   // название вида места(поверхность/шахта)
                    $faceTek['place_status_title'] = 0;                                                                 // название статуса выработки (разрешенная/запрещенная)
                    $faceTek['type_place_id'] = 0;                                                                      // ИД типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                    $faceTek['kind_place_id'] = 0;                                                                      // ИД вида места (поверхность/шахта)
                    $faceTek['main_kind_place_id'] = 0;                                                                 // ИД главного вида места (kind_object_id)
                    $faceTek['place_id'] = 0;                                                                           // ИД места
                    $faceTek['place_status_id'] = 0;                                                                    // ИД статуса выработки/места (запретная/разрешенная) 15/16

                    //текущие данные продолжительности по воркеру
                    $time_on_summary_duration = 0;                                                                      // итоговое время работы в случае выхода из ламповой продолжительность
                    $time_on_summary_start = 0;                                                                         // итоговое время работы в случае выхода из ламповой старт
                    $time_on_summary_end = 0;                                                                           // итоговое время работы в случае выхода из ламповой окончание
                    $time_on_surface_duration = 0;                                                                      // время рабочего времени на поверхности продолжительность
                    $time_on_surface_start = 0;                                                                         // время старта рабочего времени на поверхности
                    $time_on_surface_end = 0;                                                                           // время окончания рабочего времени на поверхности
                    $time_on_mine_duration = 0;                                                                         // время рабочего времени подземного продолжительность
                    $time_on_mine_start = 0;                                                                            // время старта рабочего времени подземного
                    $time_on_mine_end = 0;                                                                              // время окончания рабочего времени подземного
                    $time_on_face_start = 0;                                                                            // время начала работы в забое
                    $time_on_face_end = 0;                                                                              // время окончания работы в забое
                    $time_to_face_start = 0;                                                                            // время следования в забой начало
                    $time_to_face_end = 0;                                                                              // время следования в забой окончание
                    $time_out_face_start = 0;                                                                           // время следования из забоя начало
                    $time_out_face_end = 0;                                                                             // время следования из забоя окончание
                    $flag_out_lamp = 0;
                    $k = 0;
                }

                if ($modelTek['place_id'] != $personal_collection['place_id']) {                                        // изменение выработки

                    if (                                                                                                // переход из ламповой (80) в надшахтное здание (любой с месторождения вид: 110)
                        $modelTek['type_place_id'] == 80 && $personal_collection['kind_place_id'] == 110 && $personal_collection['type_place_id'] != 80
                    ) {
                        $modelTek['date_work'] = $personal_collection['date_work'];

                        $modelTek['smena'] = $personal_collection['smena'];                                             // название смены (смена 1, смена 2 и т.д.)

                        $time_on_summary_start = $personal_collection['date_time_work'];                                // время старта рабочего времени на поверхности
                        $time_to_face_start = $personal_collection['date_time_work'];
                        $faceTek['place_id'] = 0;
                        if ($debug_flag == 1) $warnings[] = "переход из ламповой (80) в надшахтное здание (любой с месторождения вид: 110)";
                    }

                    if ($modelTek['type_place_id'] == 80                                                                // переход из ламповой сразу в шахту
                        && $personal_collection['main_kind_place_id'] == 2) {

                        $modelTek['date_work'] = $personal_collection['date_work'];

                        $faceTek['place_id'] = 0;
                        $modelTek['smena'] = $personal_collection['smena'];
                        $time_on_summary_start = $personal_collection['date_time_work'];
                        $time_on_mine_start = $personal_collection['date_time_work'];
                        $time_to_face_start = $personal_collection['date_time_work'];

                        $time_on_surface_duration +=
                            strtotime($personal_collection['date_time_work']) - strtotime($modelTek['date_time_work']); // расчет продолжительности нахождения на поверхности

                        if ($debug_flag == 1) $warnings[] = "переход из ламповой сразу в шахту";
                    }


                    if (                                                                                                // переход из шахты сразу в ламповую
                        ($modelTek['main_kind_place_id'] == 2)
                        && $personal_collection['type_place_id'] == 80
                    ) {

                        $time_to_face_start = $personal_collection['date_time_work'];
                        $time_on_summary_end = $personal_collection['date_time_work'];
                        $time_on_mine_end = $modelTek['date_time_work'];
                        $time_out_face_end = $personal_collection['date_time_work'];
                        if ($time_on_summary_start != 0) $time_on_summary_duration += strtotime($time_on_summary_end) - strtotime($time_on_summary_start); //расчет продолжительности нахождения на смене

                        if ($time_on_mine_start != 0) {                                                                 // расчет продолжительности нахождения в шахте
                            $time_on_mine_duration += strtotime($time_on_mine_end) - strtotime($time_on_mine_start);
                        }

                        if ($time_out_face_start != 0) {
                            $time_out_face_duration += strtotime($time_out_face_end) - strtotime($time_out_face_start); //расчет продолжительности следования в забой
                            $face[$i][$k]['time_out_face'] = $time_out_face_duration;                                   // время следования в забой
                        }

                        $time_on_surface_duration +=
                            strtotime($personal_collection['date_time_work']) - strtotime($modelTek['date_time_work']); // расчет продолжительности нахождения на поверхности

                        if ($time_on_summary_duration / 60 > 15) {                                                      // если время отсутствия в ламповой больше 15 минут, то считаем ходкой
                            $model[$i] = array();
                            $model[$i] ['iterator'] = $i + 1;
                            $model[$i] ['iterator_face'] = $modelTek['iterator_face'];
                            $model[$i] ['date_work'] = $modelTek['date_work'];
                            $model[$i] ['date_time_work'] = $modelTek['date_time_work'];
                            $model[$i] ['FIO'] = $modelTek['FIO'];
                            $model[$i] ['type_worker_title'] = $modelTek['type_worker_title'];
                            $model[$i] ['department_title'] = $modelTek['department_title'];
                            $model[$i] ['company_title'] = $modelTek['company_title'];
                            $model[$i] ['place_title'] = $modelTek['place_title'];
                            $model[$i] ['smena'] = $modelTek['smena'];
                            $model[$i] ['worker_id'] = $modelTek['worker_id'];
                            $model[$i] ['department_id'] = $modelTek['department_id'];
                            $model[$i]['time_on_summary'] = round($time_on_summary_duration / 60, 2);      // время суммарное
                            $model[$i]['time_on_mine'] = round($time_on_mine_duration / 60, 2);            // время нахождения в шахте
//                            $model[$i]['time_on_surface'] = round($time_on_surface_duration / 60, 2);                 // время на поверхности
                            $model[$i]['time_on_surface'] = round($model[$i]['time_on_summary'] - $model[$i]['time_on_mine'], 0); // время на поверхности
                            $face[$i][$k]['time_out_face'] = round($time_out_face_duration / 60, 2);        // время выхода из забоя
                            $face[$i][$k]['time_into_face'] = round($time_on_face_duration / 60, 2);
                            $i++;
                            $k = 0;
                            $faceTek['place_id'] = 0;
                            $modelTek['iterator_face'] = 0;

                            $time_on_summary_duration = 0;                                                              // итоговое время работы в случае выхода из ламповой продолжительность
                            $time_on_summary_start = 0;                                                                 // итоговое время работы в случае выхода из ламповой старт
                            $time_on_summary_end = 0;                                                                   // итоговое время работы в случае выхода из ламповой окончание
                            $time_on_surface_duration = 0;                                                              // время рабочего времени на поверхности продолжительность
                            $time_on_surface_start = 0;                                                                 // время старта рабочего времени на поверхности
                            $time_on_surface_end = 0;                                                                   // время окончания рабочего времени на поверхности
                            $time_on_mine_duration = 0;                                                                 // время рабочего времени подземного продолжительность
                            $time_on_mine_start = 0;                                                                    // время старта рабочего времени подземного
                            $time_on_mine_end = 0;                                                                      // время окончания рабочего времени подземного
                            $time_on_face_start = 0;                                                                    // время начала работы в забое
                            $time_on_face_end = 0;                                                                      // время окончания работы в забое
                            $time_on_face_duration = 0;                                                                 // время нахождения в забое продолжительность
                            $time_to_face_start = 0;                                                                    // время следования в забой начало
                            $time_to_face_end = 0;                                                                      // время следования в забой окончание
                            $time_to_face_duration = 0;                                                                 // время следования в забой
                            $time_out_face_start = 0;                                                                   // время следования из забоя начало
                            $time_out_face_end = 0;                                                                     // время следования из забоя окончание
                            $time_out_face_duration = 0;                                                                // время следования из забоя
                            if ($debug_flag == 1) $warnings[] = "ХОДКА!!!!";
                        }
                        if ($debug_flag == 1) $warnings[] = "переход из шахты сразу в ламповую";
                    }

                    if (                                                                                                // переход из надшахтного здания в ламповую (80)
                        ($modelTek['kind_place_id'] == 110 && $modelTek['type_place_id'] != 80)
                        && $personal_collection['type_place_id'] == 80
                    ) {


                        $time_on_surface_end = $personal_collection['date_time_work'];
                        $time_on_summary_end = $personal_collection['date_time_work'];
                        if ($time_on_surface_start != 0) $time_on_surface_duration +=
                            strtotime($time_on_surface_end) - strtotime($time_on_surface_start);                        // расчет продолжительности нахождения на поверхности

                        if ($time_on_summary_start != 0) $time_on_summary_duration +=
                            strtotime($time_on_summary_end) - strtotime($time_on_summary_start);                        // расчет продолжительности рабочей смены

                        if ($time_out_face_start != 0) {
                            $time_out_face_duration +=
                                strtotime($time_out_face_end) - strtotime($time_out_face_start);                        // расчет продолжительности следования в забой
                            $face[$i][$k]['time_in_face'] = $time_out_face_duration;                                    // время следования в забой
                        }

                        if ($time_on_summary_duration / 60 > 15)                                                        // если время отсутствия в ламповой больше 15 минут, то считаем ходкой
                        {
                            $model[$i] = array();
                            $modelTek ['iterator'] = $i + 1;
                            $model[$i] ['iterator_face'] = $modelTek['iterator_face'];
                            $model[$i] ['date_work'] = $modelTek['date_work'];
                            $model[$i] ['date_time_work'] = $modelTek['date_time_work'];
                            $model[$i] ['FIO'] = $modelTek['FIO'];
                            $model[$i] ['type_worker_title'] = $modelTek['type_worker_title'];
                            $model[$i] ['department_title'] = $modelTek['department_title'];
                            $model[$i] ['company_title'] = $modelTek['company_title'];
                            $model[$i] ['place_title'] = $modelTek['place_title'];
                            $model[$i] ['smena'] = $modelTek['smena'];
                            $model[$i] ['worker_id'] = $modelTek['worker_id'];
                            $model[$i] ['department_id'] = $modelTek['department_id'];
                            $model[$i]['time_on_summary'] = round($time_on_summary_duration / 60, 2);      // время суммарное
                            $model[$i]['time_on_mine'] = round($time_on_mine_duration / 60, 2);            // время нахождения в шахте
//                            $model[$i]['time_on_surface'] = round($time_on_surface_duration / 60, 2);                 // время на поверхности
                            $model[$i]['time_on_surface'] = round($model[$i]['time_on_summary'] - $model[$i]['time_on_mine'], 0); // время на поверхности
                            $face[$i][$k]['time_out_face'] = round($time_out_face_duration / 60, 2);       // время выхода из забоя
                            $face[$i][$k]['time_into_face'] = round($time_on_face_duration / 60, 2);
                            $i++;
                            $k = 0;
                            $modelTek['iterator_face'] = 0;
                            $faceTek['place_id'] = 0;

                            $time_on_summary_duration = 0;                                                              // итоговое время работы в случае выхода из ламповой продолжительность
                            $time_on_summary_start = 0;                                                                 // итоговое время работы в случае выхода из ламповой старт
                            $time_on_summary_end = 0;                                                                   // итоговое время работы в случае выхода из ламповой окончание
                            $time_on_surface_duration = 0;                                                              // время рабочего времени на поверхности продолжительность
                            $time_on_surface_start = 0;                                                                 // время старта рабочего времени на поверхности
                            $time_on_surface_end = 0;                                                                   // время окончания рабочего времени на поверхности
                            $time_on_mine_duration = 0;                                                                 // время рабочего времени подземного продолжительность
                            $time_on_mine_start = 0;                                                                    // время старта рабочего времени подземного
                            $time_on_mine_end = 0;                                                                      // время окончания рабочего времени подземного
                            $time_on_face_start = 0;                                                                    // время начала работы в забое
                            $time_on_face_end = 0;                                                                      // время окончания работы в забое
                            $time_on_face_duration = 0;                                                                 // время нахождения в забое продолжительность
                            $time_to_face_start = 0;                                                                    // время следования в забой начало
                            $time_to_face_end = 0;                                                                      // время следования в забой окончание
                            $time_to_face_duration = 0;                                                                 // время следования в забой
                            $time_out_face_start = 0;                                                                   // время следования из забоя начало
                            $time_out_face_end = 0;                                                                     // время следования из забоя окончание
                            $time_out_face_duration = 0;                                                                // время следования из забоя
                            if ($debug_flag == 1) $warnings[] = "ХОДКА!!!!";
                        }
                        if ($debug_flag == 1) $warnings[] = "переход из надшахтного здания в ламповую (80)";
                    }

                    if (
                        ($modelTek['kind_place_id'] == 110 && $modelTek['type_place_id'] != 80)                         // переход из надшахтного здания в подземные горные выработки
                        && $personal_collection['main_kind_place_id'] == 2
                    ) {
                        $time_to_face_start = $personal_collection['date_time_work'];
                        $time_on_surface_end = $personal_collection['date_time_work'];
                        if ($time_on_surface_start != 0) {                                                              // расчет продолжительности нахождения на поверхности
                            $time_on_surface_duration += strtotime($time_on_surface_end) - strtotime($time_on_surface_start);
                        }

                        $time_on_mine_start = $personal_collection['date_time_work'];
                        if ($debug_flag == 1) $warnings[] = "переход из надшахтного здания в подземные горные выработки";
                    }

                    if (                                                                                                // переход из подземных горных выработок в надшахтное здание
                        ($personal_collection['kind_place_id'] == 110 && $personal_collection['type_place_id'] != 80)
                        && $modelTek['main_kind_place_id'] == 2
                    ) {
                        $time_on_surface_start = $personal_collection['date_time_work'];
                        $time_on_mine_end = $personal_collection['date_time_work'];
                        if ($time_on_mine_start != 0) {                                                                 // расчет продолжительности нахождения в шахте
                            $time_on_mine_duration += strtotime($time_on_mine_end) - strtotime($time_on_mine_start);
                        }
                        if ($debug_flag == 1) $warnings[] = "переход из подземных горных выработок в надшахтное здание";
                    }

                    if (($modelTek['type_place_id'] != 79 && $modelTek['main_kind_place_id'] == 2)                      // из горной выработки в забой
                        && $personal_collection['type_place_id'] == 79) {


                        if ($faceTek['place_id'] == 0) {
                            $faceTek['date_work'] = date('d-m-Y', strtotime($personal_collection['date_work'])); //текущая дата
                            $faceTek['place_title'] = $personal_collection['place_title'];                              // Название места
                            $faceTek['smena'] = $personal_collection['smena'];                                          // название смены (смена 1, смена 2 и т.д.)
                            $faceTek['type_place_title'] = $personal_collection['type_place_title'];                    // название типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                            $faceTek['kind_place_title'] = $personal_collection['kind_place_title'];                    // название вида места(поверхность/шахта)
                            $faceTek['place_status_title'] = $personal_collection['place_status_title'];                // название статуса выработки (разрешенная/запрещенная)
                            $faceTek['type_place_id'] = $personal_collection['type_place_id'];                          // ИД типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                            $faceTek['kind_place_id'] = $personal_collection['kind_place_id'];                          // ИД вида места (поверхность/шахта)
                            $faceTek['main_kind_place_id'] = $personal_collection['main_kind_place_id'];                // ИД главного вида места (kind_object_id)
                            $faceTek['place_id'] = $personal_collection['place_id'];                                    // ИД места
                            $faceTek['place_status_id'] = $personal_collection['place_status_id'];                      // ИД статуса выработки/места (запретная/разрешенная) 15/16


                            $time_on_face_start = $personal_collection['date_time_work'];
                            $time_to_face_end = $personal_collection['date_time_work'];
                            if ($time_to_face_start != 0) $time_to_face_duration += strtotime($time_to_face_end) - strtotime($time_to_face_start);         //расчет продолжительности следования в забой
                            $time_on_face_duration = 0;

                            $face[$i] = array();
                            $face[$i][$k] = array();
                            $modelTek['iterator_face'] = $k + 1;

                            $face[$i][$k]['place_title'] = $personal_collection['place_title'];                         // Название места

                            $face[$i][$k]['type_place_title'] = $personal_collection['type_place_title'];               // название типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                            $face[$i][$k]['kind_place_title'] = $personal_collection['kind_place_title'];               // название вида места(поверхность/шахта)
                            $face[$i][$k]['place_status_title'] = $personal_collection['place_status_title'];           // название статуса выработки (разрешенная/запрещенная)
                            $face[$i][$k]['type_place_id'] = $personal_collection['type_place_id'];                     // ИД типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                            $face[$i][$k]['kind_place_id'] = $personal_collection['kind_place_id'];                     // ИД вида места (поверхность/шахта)
                            $face[$i][$k]['main_kind_place_id'] = $personal_collection['main_kind_place_id'];           // ИД главного вида места (kind_object_id)
                            $face[$i][$k]['place_id'] = $personal_collection['place_id'];                               // ИД места
                            $face[$i][$k]['place_status_id'] = $personal_collection['place_status_id'];                 // ИД статуса выработки/места (запретная/разрешенная) 15/16
                            $face[$i][$k]['time_in_face'] = round($time_to_face_duration / 60, 2);          // время следования в забой
                            $face[$i][$k]['time_out_face'] = 0;                                                         // время выхода из забоя
                            $face[$i][$k]['time_into_face'] = 0;                                                        // время нахождения в забое
                        } elseif (                                                                                      // в одном и том же забое
                            $faceTek['place_id'] == $personal_collection['place_id'] && $faceTek['place_id'] != 0
                        ) {
                            $time_on_face_start = $personal_collection['date_time_work'];
                        } else {                                                                                        //пришел в другой забой
                            $time_out_face_end = $personal_collection['date_time_work'];

                            if ($time_out_face_start != 0) {                                                            // расчет продолжительности следования в забой
                                $time_out_face_duration += strtotime($time_out_face_end) - strtotime($time_out_face_start);
                            }

                            $model[$i]['iterator_face'] = $k + 1;
                            $face[$i][$k]['place_title'] = $faceTek['place_title'];                                     // Название места
                            $face[$i][$k]['type_place_title'] = $faceTek['type_place_title'];                           // название типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                            $face[$i][$k]['kind_place_title'] = $faceTek['kind_place_title'];                           // название вида места(поверхность/шахта)
                            $face[$i][$k]['place_status_title'] = $faceTek['place_status_title'];                       // название статуса выработки (разрешенная/запрещенная)
                            $face[$i][$k]['type_place_id'] = $faceTek['type_place_id'];                                 // ИД типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                            $face[$i][$k]['kind_place_id'] = $faceTek['kind_place_id'];                                 // ИД вида места (поверхность/шахта)
                            $face[$i][$k]['main_kind_place_id'] = $faceTek['main_kind_place_id'];                       // ИД главного вида места (kind_object_id)
                            $face[$i][$k]['place_id'] = $faceTek['place_id'];                                           // ИД места
                            $face[$i][$k]['place_status_id'] = $faceTek['place_status_id'];                             // ИД статуса выработки/места (запретная/разрешенная) 15/16

                            $face[$i][$k]['time_out_face'] = round($time_out_face_duration / 60, 2);       // время выхода из забоя
                            $face[$i][$k]['time_into_face'] = round($time_on_face_duration / 60, 2);       // время нахождения в забое
                            $k++;

                            $face[$i][$k] = array();
                            $model[$i]['iterator_face'] = $k + 1;

                            $face[$i][$k]['place_title'] = $personal_collection['place_title'];                         // Название места

                            $face[$i][$k]['type_place_title'] = $personal_collection['type_place_title'];               // название типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                            $face[$i][$k]['kind_place_title'] = $personal_collection['kind_place_title'];               // название вида места(поверхность/шахта)
                            $face[$i][$k]['place_status_title'] = $personal_collection['place_status_title'];           // название статуса выработки (разрешенная/запрещенная)
                            $face[$i][$k]['type_place_id'] = $personal_collection['type_place_id'];                     // ИД типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                            $face[$i][$k]['kind_place_id'] = $personal_collection['kind_place_id'];                     // ИД вида места (поверхность/шахта)
                            $face[$i][$k]['main_kind_place_id'] = $personal_collection['main_kind_place_id'];           // ИД главного вида места (kind_object_id)
                            $face[$i][$k]['place_id'] = $personal_collection['place_id'];                               // ИД места
                            $face[$i][$k]['place_status_id'] = $personal_collection['place_status_id'];                 // ИД статуса выработки/места (запретная/разрешенная) 15/16
                            $face[$i][$k]['time_in_face'] = round($time_out_face_duration / 60, 2);        // время следования в забой
                            $face[$i][$k]['time_out_face'] = 0;                                                         // время выхода из забоя
                            $face[$i][$k]['time_into_face'] = 0;                                                        // время нахождения в забое
                            $time_on_face_start = $personal_collection['date_time_work'];
                            $time_on_face_duration = 0;
                            $time_out_face_duration = 0;
                            $time_to_face_duration = 0;
                        }
                        if ($debug_flag == 1) $warnings[] = "из горной выработки в забой";
                    }

                    if (                                                                                                // из забоя в горные выработки
                        ($personal_collection['type_place_id'] != 79 && $personal_collection['main_kind_place_id'] == 2)
                        && $modelTek['type_place_id'] == 79
                    ) {
                        $time_on_face_end = $personal_collection['date_time_work'];

                        if ($time_on_face_start != 0) {                                                                 // расчет продолжительности следования в забой
                            $time_on_face_duration += strtotime($time_on_face_end) - strtotime($time_on_face_start);
                        }

                        $time_out_face_start = $personal_collection['date_time_work'];
                        if ($debug_flag == 1) $warnings[] = "из забоя в горные выработки";
                    }

                    $modelTek['date_time_work'] = $personal_collection['date_time_work'];                               // текущее время с датой
                    $modelTek['FIO'] = $personal_collection['FIO'];                                                     // ФИО рабочего воркера
                    $modelTek['type_worker_title'] = $personal_collection['type_worker_title'];                         // тип объекта (рабочий поверхности/подземный рабочий)
                    $modelTek['department_title'] = $personal_collection['company_title'];                              // название департамента
                    $modelTek['company_title'] = '"';                                                                   // название компании
                    $modelTek['place_title'] = $personal_collection['place_title'];                                     // Название места
                    $modelTek['worker_id'] = $personal_collection['worker_id'];                                         // ИД персонала
                    $modelTek['type_place_title'] = $personal_collection['type_place_title'];                           // название типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                    $modelTek['kind_place_title'] = $personal_collection['kind_place_title'];                           // название вида места(поверхность/шахта)
                    $modelTek['place_status_title'] = $personal_collection['place_status_title'];                       // название статуса выработки (разрешенная/запрещенная)
                    $modelTek['type_place_id'] = $personal_collection['type_place_id'];                                 // ИД типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                    $modelTek['kind_place_id'] = $personal_collection['kind_place_id'];                                 // ИД вида места (поверхность/шахта)
                    $modelTek['place_id'] = $personal_collection['place_id'];                                           // ИД места
                    $modelTek['department_id'] = $personal_collection['department_id'];                                 // ИД департамента
                    $modelTek['main_kind_place_id'] = $personal_collection['main_kind_place_id'];                       // ИД главного вида места (kind_object_id)
                    $modelTek['place_status_id'] = $personal_collection['place_status_id'];                             // ИД статуса выработки/места (запретная/разрешенная) 15/16
                    if ($debug_flag == 1) $warnings[] = "СМЕНИЛАСЬ ВЫРАБОТКА";
                    if ($debug_flag == 1) $warnings[] = $personal_collection;
                }
                $modelTek['place_id'] = $personal_collection['place_id'];
            }
            unset($personal_collections);
            $warnings[] = "все посчиталось и выход из расчета";

            $p = 0;
            $g = 0;
            $model_final = array();


            $flag_smena_search = 1;
            $flag_kind_search = 1;
            $flag_type_search = 1;
            $flag_date = 1;
            $flag_zapisi_final = 0;
            $flag_zapisi_face = 0;

            $warnings[] = "Пост обработка. Количество обрабатываемых данных: " . count($model);

            if (!empty($model)) {
                for ($j = 0; $j < $i; $j++) {

                    if (($data_search_start <= $model[$j] ['date_work']) && ($data_search_end >= $model[$j] ['date_work'])) $flag_date = 1;
                    else $flag_date = 0;

                    if (isset($post['titleSmena']) && ($post['titleSmena'] != '')) {
                        if ($model[$j]['smena'] == $post['titleSmena']) $flag_smena_search = 1;
                        else $flag_smena_search = 0;
                    }

                    if (isset($post['titleKind']) && ($post['titleKind'] != '')) {
                        if ($post['titleKind'] == $model[$j] ['kind_place_title']) $flag_kind_search = 1;
                        else $flag_kind_search = 0;
                    }

                    if (isset($post['titleType']) && ($post['titleType'] != '')) {
                        if ($post['titleType'] == $model[$j] ['place_title']) $flag_type_search = 1;
                        else $flag_type_search = 0;
                    }
                    $warnings[] = "Флаг поиска flag_type_search: $flag_type_search";
                    $warnings[] = "Флаг поиска flag_kind_search: $flag_kind_search";
                    $warnings[] = "Флаг поиска flag_smena_search: $flag_smena_search";
                    $warnings[] = "Флаг поиска flag_date: $flag_date";
                    $warnings[] = "Дата от: $data_search_start";
                    $warnings[] = "Дата до: $data_search_end";
                    $warnings[] = "Дата тек: " . $model[$j] ['date_work'];

                    if ($flag_type_search == 1 && $flag_kind_search == 1 && $flag_smena_search == 1 && $flag_date == 1) {

                        $model_final[$p] ['iterator'] = isset($model[$j] ['iterator']) ? $model[$j] ['iterator'] : 0;
                        //                    $model_final[$p] ['date_work'] = $model[$j] ['date_work'];
                        $model_final[$p] ['date_work'] = date('d-m-Y', strtotime($model [$j] ['date_work']));
                        $model_final[$p] ['FIO'] = $model[$j] ['FIO'];
                        $model_final[$p] ['iterator_face'] = $model[$j] ['iterator_face'];
                        $model_final[$p] ['type_worker_title'] = $model[$j] ['type_worker_title'];
                        $model_final[$p] ['department_title'] = $model[$j] ['department_title'];
                        $model_final[$p] ['company_title'] = $model[$j] ['company_title'];
                        $model_final[$p] ['place_title'] = '-';
                        $model_final[$p] ['smena'] = $model[$j] ['smena'];

                        $model_final[$p] ['worker_id'] = $model[$j] ['worker_id'];
                        $model_final[$p] ['department_id'] = $model[$j] ['department_id'];
                        $model_final[$p] ['time_on_surface'] = $model[$j]['time_on_surface'];                           // время на поверхности
                        $model_final[$p] ['time_on_mine'] = number_format($model[$j]['time_on_mine'], 0, ',', ' ');         //время нахождения в шахте
                        $model_final[$p] ['time_on_summary'] = number_format($model[$j]['time_on_summary'], 0, ',', ' ');   //время суммарное

                        $model_final[$p] ['time_out_face'] = 0;                                                         // время выхода из забоя
                        $model_final[$p] ['time_into_face'] = 0;
                        $model_final[$p] ['time_in_face'] = 0;

                        $model_final[$p] ['type_place_title'] = '-';                                                    // название типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                        $model_final[$p] ['kind_place_title'] = '-';                                                    // название вида места(поверхность/шахта)
                        $model_final[$p] ['place_status_title'] = 0;                                                    // название статуса выработки (разрешенная/запрещенная)
                        $model_final[$p] ['type_place_id'] = 0;                                                         // ИД типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                        $model_final[$p] ['kind_place_id'] = 0;                                                         // ИД вида места (поверхность/шахта)
                        $model_final[$p] ['main_kind_place_id'] = 0;                                                    // ИД главного вида места (kind_object_id)
                        $model_final[$p] ['place_id'] = 0;                                                              // ИД места
                        $model_final[$p] ['place_status_id'] = 0;                                                       // ИД статуса выработки/места (запретная/разрешенная) 15/16
                        $flag_zapisi_final = 1;
                    }
                    if ($model[$j]['iterator_face'] != 0) {
                        for ($g = 0; $g < $model[$j]['iterator_face']; $g++) {

                            if ($data_search_start <= $model[$j] ['date_work'] && $data_search_end >= $model[$j] ['date_work']) $flag_date = 1;
                            else $flag_date = 0;

                            if (isset($post['titleSmena']) && ($post['titleSmena'] != '')) {
                                if ($model[$j]['smena'] == $post['titleSmena']) $flag_smena_search = 1;
                                else $flag_smena_search = 0;
                            }

                            if (isset($post['titleKind']) && $post['titleKind'] != '') {
                                if ($post['titleKind'] == $face[$j][$g] ['kind_place_title']) $flag_kind_search = 1;
                                else $flag_kind_search = 0;
                            }

                            if (isset($post['titleType']) && $post['titleType'] != '') {
                                if ($post['titleType'] == $face[$j][$g] ['place_title']) $flag_type_search = 1;
                                else $flag_type_search = 0;
                            }

                            if ($flag_type_search == 1 && $flag_kind_search == 1 && $flag_smena_search == 1 && $flag_date == 1) {

                                $model_final[$p] ['iterator'] = isset($model[$j] ['iterator']) ? $model[$j] ['iterator'] : 0;
                                $model_final[$p] ['date_work'] = date('d-m-Y', strtotime($model[$j] ['date_work']));
                                $model_final[$p] ['FIO'] = $model[$j] ['FIO'];
                                $model_final[$p] ['iterator_face'] = $model[$j] ['iterator_face'];
                                $model_final[$p] ['type_worker_title'] = $model[$j] ['type_worker_title'];
                                $model_final[$p] ['department_title'] = $model[$j] ['department_title'];
                                $model_final[$p] ['company_title'] = $model[$j] ['company_title'];

                                $model_final[$p] ['smena'] = $model[$j] ['smena'];
                                $model_final[$p] ['worker_id'] = $model[$j] ['worker_id'];
                                $model_final[$p] ['department_id'] = $model[$j] ['department_id'];
                                $model_final[$p] ['time_on_surface'] = round($model[$j]['time_on_surface'], 2); //время на поверхности
                                $model_final[$p] ['time_on_mine'] = round($model[$j]['time_on_mine'], 2);       //время нахождения в шахте
                                $model_final[$p] ['time_on_summary'] = round($model[$j]['time_on_summary'], 2);

//                                    if(isset($face[$j][$g])) {
                                $model_final[$p] ['place_title'] = $face[$j][$g]['place_title'];                        // Название места
                                $model_final[$p] ['type_place_title'] = $face[$j][$g]['type_place_title'];              // название типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                                $model_final[$p] ['kind_place_title'] = $face[$j][$g]['kind_place_title'];              // название вида места(поверхность/шахта)
                                $model_final[$p] ['place_status_title'] = $face[$j][$g]['place_status_title'];          // название статуса выработки (разрешенная/запрещенная)
                                $model_final[$p] ['type_place_id'] = $face[$j][$g]['type_place_id'];                    // ИД типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                                $model_final[$p] ['kind_place_id'] = $face[$j][$g]['kind_place_id'];                    // ИД вида места (поверхность/шахта)
                                $model_final[$p] ['main_kind_place_id'] = $face[$j][$g]['main_kind_place_id'];          // ИД главного вида места (kind_object_id)
                                $model_final[$p] ['place_id'] = $face[$j][$g]['place_id'];                              // ИД места
                                $model_final[$p] ['place_status_id'] = $face[$j][$g]['place_status_id'];                // ИД статуса выработки/места (запретная/разрешенная) 15/16
                                $model_final[$p] ['time_out_face'] = round($face[$j][$g]['time_out_face'], 2);  //время выхода из забоя
                                $model_final[$p] ['time_into_face'] = round($face[$j][$g]['time_into_face'], 2);
                                $model_final[$p] ['time_in_face'] = round($face[$j][$g]['time_in_face'], 2);
//                                    }
                                $p++;
                                $flag_zapisi_face = 1;
                            }
                            $flag_smena_search = 1;
                            $flag_kind_search = 1;
                            $flag_type_search = 1;
                            $flag_date = 1;
                        }
                    }
                    $warnings[] = "окончательный результат:";
                    $warnings[] = $model_final;
                    $flag_smena_search = 1;
                    $flag_kind_search = 1;
                    $flag_type_search = 1;
                    $flag_date = 1;

                    if ($flag_zapisi_face == 1 && $flag_zapisi_final == 1) $flag_zapisi_face = 0;
                    elseif ($flag_zapisi_final == 1 && $flag_zapisi_face != 1) $p++;

                }
//                    $warnings[]=$face;
                unset($face, $model);

                //поиск по страничке
                if (!isset($post['search']) || isset($post['search']) && $post['search'] == '')                         // если не передан параметр поиска или передан и имеет пустое значение, то выводим без так как есть
                {
                    if (!empty($model_final))                                                                           // Если добавляем данные в таблицу меняем значение параметра data_exists
                        $data_exists++;

                } else if (isset($post['search']) && ($post['search'] != ''))                                           // если параметр поиска задан и не имеет пустое значение, то выделяем найденное значение
                {
                    //echo "Search in";
                    $needle = $post['search'];
                    for ($j = 0; $j < $p; $j++) {
                        $model_final[$j]['iterator'] = $j + 1;
                        //$model_final[$j]['date_work'] = $model_final[$j]['date_work'];
                        $model_final[$j]['FIO'] = Assistant::MarkSearched($needle, $model_final[$j]['FIO']);
                        $model_final[$j]['type_worker_title'] = Assistant::MarkSearched($needle, $model_final[$j]['type_worker_title']);
                        $model_final[$j]['department_title'] = Assistant::MarkSearched($needle, $model_final[$j]['department_title']);
                        $model_final[$j]['company_title'] = Assistant::MarkSearched($needle, $model_final[$j]['company_title']);
                        $model_final[$j]['place_title'] = Assistant::MarkSearched($needle, $model_final[$j]['place_title']);
                        $model_final[$j]['type_place_title'] = Assistant::MarkSearched($needle, $model_final[$j]['type_place_title']);
                        $model_final[$j]['kind_place_title'] = Assistant::MarkSearched($needle, $model_final[$j]['kind_place_title']);
                        $model_final[$j]['smena'] = Assistant::MarkSearched($needle, $model_final[$j]['smena']);

                    }

                    if (!empty($model_final)) {                                                                         // Если добавляем данные в таблицу меняем значение параметра data_exists
                        $data_exists++;
                    }
                    $warnings[] = "возврат по строке 786";
                }
                foreach ($model_final as $model) {
                    $result[] = $model;
                }
            } else if ($limit_flag == -1 && $limit <= $limit_start && $data_exists == 0) {                              // Если данных меньше указанного лимита флаг отрицателен и последний id по которому делалась выборка больше 500 отправляем ошибку
                $warnings[] = "возврат по строке 800";
                $errors[] = 'Нет данных по заданному условию';
            } else {                                                                                                    // Иначе пропускаем без отправки ошибки
                $warnings[] = "возврат по строке 803";
            }

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "SummaryReportTimeSpentController:actionResult. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "SummaryReportTimeSpentController:actionResult.Окончил выполнять метод.";

        $result_main = array('result' => $result, 'status' => $status, 'errors' => $errors, 'recall' => $limit_flag, 'start_position' => $limit_start, 'syn-report' => $syn_table, 'data_exists' => $data_exists, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }


    /**
     * actionTimeSpentByLampStartEnd - метод построения сводного отчета начала и окончания смены по шахтам за заданный период
     * @example  http://127.0.0.1/reports/summary-report-time-spent/time-spent-by-lamp-start-end?dateStart=2020-01-01&dateFinish=2024-01-01
     */
    public function actionTimeSpentByLampStartEnd()
    {
//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', '10000M');

        $log = new LogAmicumFront("actionTimeSpentByLampStartEnd");


        $model_final = array();

        $post = Assistant::GetServerMethod();                                                                           // метод принимает данные из модели для фильтрации запроса.

        $sql_filter = '';                                                                                               // фильтр запроса, т.к. данных в запросе много, то по умолчанию возвращаются только данные за текущие сутки,


        try {
            $log->addLog("Начал выполнять метод");

            $data_search_start = date('Y-m-d H:i:s');
            $data_search_end = date('Y-m-d H:i:s');
            //фильтр по дате если дата не задана то берутся текущие сутки, если задана, то берутся из метода пост
            if (!isset($post['dateStart'], $post['dateFinish'])) {
                $sql_filter .= " DATE(date_time_work) = '" . date('Y-m-d H:i:s') . "'";
                $data_search_start = date('Y-m-d H:i:s');
                $data_search_end = date('Y-m-d H:i:s');
            } else {
                $formatted_date_start = date('Y-m-d H:i:s', strtotime($post['dateStart']));
                $formatted_date_end = date('Y-m-d H:i:s', strtotime($post['dateFinish']));
                if ($formatted_date_start == $formatted_date_end) {
                    $sql_filter .= "DATE(date_time_work) = '" . date('Y-m-d H:i:s', strtotime($post['dateStart'])) . "'";
                    $data_search_start = date('Y-m-d H:i:s', strtotime($post['dateStart']));
                    $data_search_end = date('Y-m-d H:i:s', strtotime($post['dateStart']));
                } else if ($formatted_date_start < $formatted_date_end) {
                    $sql_filter .= " date_time_work >= '" . date('Y-m-d H:i:s', strtotime($post['dateStart'])) . "' AND  date_time_work <='" . date('Y-m-d H:i:s', strtotime($post['dateFinish'])) . "'";
                    $data_search_start = date('Y-m-d H:i:s', strtotime($post['dateStart']));
                    $data_search_end = date('Y-m-d H:i:s', strtotime($post['dateFinish']));
                } else if ($formatted_date_start > $formatted_date_end) {
                    $sql_filter .= " date_time_work >= '" . date('Y-m-d H:i:s', strtotime($post['dateFinish'])) . "' AND date_time_work <= '" . date('Y-m-d H:i:s', strtotime($post['dateStart'])) . "'";
                    $data_search_start = date('Y-m-d H:i:s', strtotime($post['dateFinish']));
                    $data_search_end = date('Y-m-d H:i:s', strtotime($post['dateStart']));
                }
            }

            $log->addData($sql_filter, '$sql_filter', __LINE__);


            $personal_collections = (new Query())->select([                                                             // запрос напрямую из базы по вьюшке view_personal_areas //обязательно сортируем по порядку
                'date_work',
                'date_time_work',
                'last_name as FIO',
                'titleObject as type_worker_title',
                'titleDepartment as department_title',
                'titleCompany as company_title',
                'titlePlace as place_title',
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
                'worker.link_1c as worker_link_1c',
            ])
                ->from('worker_collection')
                ->innerJoin('worker','worker.id=worker_collection.worker_id')
                ->where($sql_filter)
//                ->andWhere('worker_id=2924401')
                ->orderBy([
                    'worker_id' => SORT_ASC,
                    'date_time_work' => SORT_ASC
                ])
                ->all();

            $log->addData(count($personal_collections), 'count_personal_collections', __LINE__);

            $model = array();                                                                                           // сформированный список запретных зон

            $i = 0;
            $k = 0;
            $flag_out_lamp = 0;                                                                                         // флаг выхода из ламповой (зарядки светильника)
            $modelTek = array();
            $modelTek['worker_id'] = 0;
            $time_on_summary_duration = 0;                                                                              // итоговое время работы в случае выхода из ламповой продолжительность
            $time_on_summary_start = 0;                                                                                 // итоговое время работы в случае выхода из ламповой старт
            $time_on_summary_end = 0;                                                                                   // итоговое время работы в случае выхода из ламповой окончание
            $time_on_surface_duration = 0;                                                                              // время рабочего времени на поверхности продолжительность
            $time_on_surface_start = 0;                                                                                 // время старта рабочего времени на поверхности
            $time_on_surface_end = 0;                                                                                   // время окончания рабочего времени на поверхности
            $time_on_mine_duration = 0;                                                                                 // время рабочего времени подземного продолжительность
            $time_on_mine_start = 0;                                                                                    // время старта рабочего времени подземного
            $time_on_mine_end = 0;                                                                                      // время окончания рабочего времени подземного


            foreach ($personal_collections as $personal_collection) {

                if ($modelTek['worker_id'] != $personal_collection['worker_id']) {                                      // выявление изменения человека при переборке
                    //текущие данные по воркеру
                    $modelTek['date_time_work'] = $personal_collection['date_time_work'];                               // текущее время с датой
                    $modelTek['FIO'] = $personal_collection['FIO'];                                                     // ФИО рабочего воркера
                    $modelTek['date_time_start_shift'] = "";                                                            // начало смены
                    $modelTek['date_time_end_shift'] = "";                                                              // окончание смены
                    $modelTek['type_worker_title'] = $personal_collection['type_worker_title'];                         // тип объекта (рабочий поверхности/подземный рабочий)
                    $modelTek['department_title'] = $personal_collection['company_title'];                              // название департамента
                    $modelTek['company_title'] = '"';                                                                   // название компании
                    $modelTek['place_title'] = $personal_collection['place_title'];                                     // Название места
                    $modelTek['worker_id'] = $personal_collection['worker_id'];                                         // ИД персонала
                    $modelTek['worker_link_1c'] = $personal_collection['worker_link_1c'];                               // ИД персонала 1c
                    $modelTek['type_place_title'] = $personal_collection['type_place_title'];                           // название типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                    $modelTek['kind_place_title'] = $personal_collection['kind_place_title'];                           // название вида места(поверхность/шахта)
                    $modelTek['place_status_title'] = $personal_collection['place_status_title'];                       // название статуса выработки (разрешенная/запрещенная)
                    $modelTek['type_place_id'] = $personal_collection['type_place_id'];                                 // ИД типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                    $modelTek['kind_place_id'] = $personal_collection['kind_place_id'];                                 // ИД вида места (поверхность/шахта)
                    $modelTek['main_kind_place_id'] = $personal_collection['main_kind_place_id'];                       // ИД главного вида места (kind_object_id)
                    $modelTek['place_id'] = $personal_collection['place_id'];                                           // ИД места
                    $modelTek['department_id'] = $personal_collection['department_id'];                                 // ИД департамента

                    //текущие данные продолжительности по воркеру
                    $time_on_summary_duration = 0;                                                                      // итоговое время работы в случае выхода из ламповой продолжительность
                    $time_on_summary_start = 0;                                                                         // итоговое время работы в случае выхода из ламповой старт
                    $time_on_summary_end = 0;                                                                           // итоговое время работы в случае выхода из ламповой окончание
                    $time_on_surface_duration = 0;                                                                      // время рабочего времени на поверхности продолжительность
                    $time_on_surface_start = 0;                                                                         // время старта рабочего времени на поверхности
                    $time_on_surface_end = 0;                                                                           // время окончания рабочего времени на поверхности
                    $time_on_mine_duration = 0;                                                                         // время рабочего времени подземного продолжительность
                    $time_on_mine_start = 0;                                                                            // время старта рабочего времени подземного
                    $time_on_mine_end = 0;                                                                              // время окончания рабочего времени подземного
                    $flag_out_lamp = 0;
                    $k = 0;
                }

                if ($modelTek['place_id'] != $personal_collection['place_id']) {                                        // изменение выработки

                    if (                                                                                                // переход из ламповой (80) в надшахтное здание (любой с месторождения вид: 110)
                        $modelTek['type_place_id'] == 80 && $personal_collection['kind_place_id'] == 110 && $personal_collection['type_place_id'] != 80
                    ) {
                        $modelTek['date_work'] = $personal_collection['date_work'];
                        $modelTek['smena'] = $personal_collection['smena'];                                             // название смены (смена 1, смена 2 и т.д.)
                        $modelTek['date_time_start_shift'] = $personal_collection['date_time_work'];                    // начало смены

                        $time_on_summary_start = $personal_collection['date_time_work'];                                // время старта рабочего времени на поверхности
                    }

                    if ($modelTek['type_place_id'] == 80                                                                // переход из ламповой сразу в шахту
                        && $personal_collection['main_kind_place_id'] == 2) {

                        $modelTek['date_work'] = $personal_collection['date_work'];
                        $modelTek['smena'] = $personal_collection['smena'];
                        $modelTek['date_time_start_shift'] = $personal_collection['date_time_work'];                    // начало смены

                        $time_on_summary_start = $personal_collection['date_time_work'];
                        $time_on_mine_start = $personal_collection['date_time_work'];

                        $time_on_surface_duration +=
                            strtotime($personal_collection['date_time_work']) - strtotime($modelTek['date_time_work']); // расчет продолжительности нахождения на поверхности

                    }


                    if (                                                                                                // переход из шахты сразу в ламповую
                        ($modelTek['main_kind_place_id'] == 2)
                        && $personal_collection['type_place_id'] == 80
                    ) {

                        $modelTek['date_time_end_shift'] = $personal_collection['date_time_work'];                      // конец смены

                        $time_on_summary_end = $personal_collection['date_time_work'];
                        $time_on_mine_end = $modelTek['date_time_work'];
                        $time_out_face_end = $personal_collection['date_time_work'];
                        if ($time_on_summary_start != 0) $time_on_summary_duration += strtotime($time_on_summary_end) - strtotime($time_on_summary_start); //расчет продолжительности нахождения на смене

                        if ($time_on_mine_start != 0) {                                                                 // расчет продолжительности нахождения в шахте
                            $time_on_mine_duration += strtotime($time_on_mine_end) - strtotime($time_on_mine_start);
                        }


                        $time_on_surface_duration +=
                            strtotime($personal_collection['date_time_work']) - strtotime($modelTek['date_time_work']); // расчет продолжительности нахождения на поверхности

                        if ($time_on_summary_duration / 60 > 15) {                                                      // если время отсутствия в ламповой больше 15 минут, то считаем ходкой
                            $model[$i] = array();
                            $model[$i] ['iterator'] = $i + 1;
                            $model[$i] ['date_work'] = $modelTek['date_work'];
                            $model[$i] ['date_time_work'] = $modelTek['date_time_work'];
                            $model[$i] ['date_time_start_shift'] = $modelTek['date_time_start_shift'];
                            $model[$i] ['date_time_end_shift'] = $modelTek['date_time_end_shift'];
                            $model[$i] ['FIO'] = $modelTek['FIO'];
                            $model[$i] ['type_worker_title'] = $modelTek['type_worker_title'];
                            $model[$i] ['department_title'] = $modelTek['department_title'];
                            $model[$i] ['company_title'] = $modelTek['company_title'];
                            $model[$i] ['place_id'] = $modelTek['place_id'];
                            $model[$i] ['place_status_title'] = $modelTek['place_status_title'];
                            $model[$i] ['place_title'] = $modelTek['place_title'];
                            $model[$i] ['kind_place_title'] = $modelTek['kind_place_title'];
                            $model[$i] ['type_place_title'] = $modelTek['type_place_title'];
                            $model[$i] ['kind_place_id'] = $modelTek['kind_place_id'];
                            $model[$i] ['main_kind_place_id'] = $modelTek['main_kind_place_id'];
                            $model[$i] ['type_place_id'] = $modelTek['type_place_id'];
                            $model[$i] ['smena'] = $modelTek['smena'];
                            $model[$i] ['worker_id'] = $modelTek['worker_id'];
                            $model[$i] ['worker_link_1c'] = $modelTek['worker_link_1c'];
                            $model[$i] ['department_id'] = $modelTek['department_id'];
                            $model[$i]['time_on_summary'] = round($time_on_summary_duration / 60, 2);      // время суммарное
                            $model[$i]['time_on_mine'] = round($time_on_mine_duration / 60, 2);            // время нахождения в шахте
//                            $model[$i]['time_on_surface'] = round($time_on_surface_duration / 60, 2);                 // время на поверхности
                            $model[$i]['time_on_surface'] = round($model[$i]['time_on_summary'] - $model[$i]['time_on_mine'], 0); // время на поверхности
                            $i++;
                            $k = 0;

                            $modelTek['date_time_start_shift'] = "";                                                    // начало смены
                            $modelTek['date_time_end_shift'] = "";                                                      // окончание смены

                            $time_on_summary_duration = 0;                                                              // итоговое время работы в случае выхода из ламповой продолжительность
                            $time_on_summary_start = 0;                                                                 // итоговое время работы в случае выхода из ламповой старт
                            $time_on_summary_end = 0;                                                                   // итоговое время работы в случае выхода из ламповой окончание
                            $time_on_surface_duration = 0;                                                              // время рабочего времени на поверхности продолжительность
                            $time_on_surface_start = 0;                                                                 // время старта рабочего времени на поверхности
                            $time_on_surface_end = 0;                                                                   // время окончания рабочего времени на поверхности
                            $time_on_mine_duration = 0;                                                                 // время рабочего времени подземного продолжительность
                            $time_on_mine_start = 0;                                                                    // время старта рабочего времени подземного
                            $time_on_mine_end = 0;                                                                      // время окончания рабочего времени подземного
                        }
                    }

                    if (                                                                                                // переход из надшахтного здания в ламповую (80)
                        ($modelTek['kind_place_id'] == 110 && $modelTek['type_place_id'] != 80)
                        && $personal_collection['type_place_id'] == 80
                    ) {
                        $modelTek['date_time_end_shift'] = $personal_collection['date_time_work'];                      // конец смены

                        $time_on_surface_end = $personal_collection['date_time_work'];
                        if ($time_on_surface_start != 0) $time_on_surface_duration +=
                            strtotime($time_on_surface_end) - strtotime($time_on_surface_start);                        // расчет продолжительности нахождения на поверхности

                        if ($time_on_summary_start != 0) $time_on_summary_duration +=
                            strtotime($time_on_summary_end) - strtotime($time_on_summary_start);                        // расчет продолжительности рабочей смены


                        if ($time_on_summary_duration / 60 > 15)                                                        // если время отсутствия в ламповой больше 15 минут, то считаем ходкой
                        {
                            $model[$i] = array();
                            $modelTek ['iterator'] = $i + 1;
                            $model[$i] ['date_work'] = $modelTek['date_work'];
                            $model[$i] ['date_time_work'] = $modelTek['date_time_work'];
                            $model[$i] ['date_time_start_shift'] = $modelTek['date_time_start_shift'];
                            $model[$i] ['date_time_end_shift'] = $modelTek['date_time_end_shift'];
                            $model[$i] ['FIO'] = $modelTek['FIO'];
                            $model[$i] ['type_worker_title'] = $modelTek['type_worker_title'];
                            $model[$i] ['department_title'] = $modelTek['department_title'];
                            $model[$i] ['company_title'] = $modelTek['company_title'];
                            $model[$i] ['place_id'] = $modelTek['place_id'];
                            $model[$i] ['place_title'] = $modelTek['place_title'];
                            $model[$i] ['kind_place_title'] = $modelTek['kind_place_title'];
                            $model[$i] ['type_place_title'] = $modelTek['type_place_title'];
                            $model[$i] ['place_status_title'] = $modelTek['place_status_title'];
                            $model[$i] ['kind_place_id'] = $modelTek['kind_place_id'];
                            $model[$i] ['main_kind_place_id'] = $modelTek['main_kind_place_id'];
                            $model[$i] ['type_place_id'] = $modelTek['type_place_id'];
                            $model[$i] ['smena'] = $modelTek['smena'];
                            $model[$i] ['worker_id'] = $modelTek['worker_id'];
                            $model[$i] ['worker_link_1c'] = $modelTek['worker_link_1c'];
                            $model[$i] ['department_id'] = $modelTek['department_id'];
                            $model[$i]['time_on_summary'] = round($time_on_summary_duration / 60, 2);      // время суммарное
                            $model[$i]['time_on_mine'] = round($time_on_mine_duration / 60, 2);            // время нахождения в шахте
//                            $model[$i]['time_on_surface'] = round($time_on_surface_duration / 60, 2);                 // время на поверхности
                            $model[$i]['time_on_surface'] = round($model[$i]['time_on_summary'] - $model[$i]['time_on_mine'], 0); // время на поверхности
                            $i++;
                            $k = 0;

                            $modelTek['date_time_start_shift'] = "";                                                    // начало смены
                            $modelTek['date_time_end_shift'] = "";                                                      // окончание смены

                            $time_on_summary_duration = 0;                                                              // итоговое время работы в случае выхода из ламповой продолжительность
                            $time_on_summary_start = 0;                                                                 // итоговое время работы в случае выхода из ламповой старт
                            $time_on_summary_end = 0;                                                                   // итоговое время работы в случае выхода из ламповой окончание
                            $time_on_surface_duration = 0;                                                              // время рабочего времени на поверхности продолжительность
                            $time_on_surface_start = 0;                                                                 // время старта рабочего времени на поверхности
                            $time_on_surface_end = 0;                                                                   // время окончания рабочего времени на поверхности
                            $time_on_mine_duration = 0;                                                                 // время рабочего времени подземного продолжительность
                            $time_on_mine_start = 0;                                                                    // время старта рабочего времени подземного
                            $time_on_mine_end = 0;                                                                      // время окончания рабочего времени подземного

                        }
                    }


                    $modelTek['date_time_work'] = $personal_collection['date_time_work'];                               // текущее время с датой
                    $modelTek['FIO'] = $personal_collection['FIO'];                                                     // ФИО рабочего воркера
                    $modelTek['type_worker_title'] = $personal_collection['type_worker_title'];                         // тип объекта (рабочий поверхности/подземный рабочий)
                    $modelTek['department_title'] = $personal_collection['company_title'];                              // название департамента
                    $modelTek['company_title'] = '"';                                                                   // название компании
                    $modelTek['place_title'] = $personal_collection['place_title'];                                     // Название места
                    $modelTek['worker_id'] = $personal_collection['worker_id'];                                         // ИД персонала
                    $modelTek['worker_link_1c'] = $personal_collection['worker_link_1c'];                                         // ИД персонала
                    $modelTek['type_place_title'] = $personal_collection['type_place_title'];                           // название типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                    $modelTek['kind_place_title'] = $personal_collection['kind_place_title'];                           // название вида места(поверхность/шахта)
                    $modelTek['place_status_title'] = $personal_collection['place_status_title'];                       // название статуса выработки (разрешенная/запрещенная)
                    $modelTek['type_place_id'] = $personal_collection['type_place_id'];                                 // ИД типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                    $modelTek['kind_place_id'] = $personal_collection['kind_place_id'];                                 // ИД вида места (поверхность/шахта)
                    $modelTek['place_id'] = $personal_collection['place_id'];                                           // ИД места
                    $modelTek['department_id'] = $personal_collection['department_id'];                                 // ИД департамента
                    $modelTek['main_kind_place_id'] = $personal_collection['main_kind_place_id'];                       // ИД главного вида места (kind_object_id)
                }
                $modelTek['place_id'] = $personal_collection['place_id'];
            }
            unset($personal_collections);

            $i = 1;
            foreach ($model as $item) {
                $model_final[] = array(
                    'i' => $i++,
                    'date_work' => date('d-m-Y', strtotime($item ['date_work'])),
                    'FIO' => $item ['FIO'],
                    'date_time_start_shift' => $item ['date_time_start_shift'],
                    'date_time_end_shift' => $item ['date_time_end_shift'],
                    'type_worker_title' => $item ['type_worker_title'],
                    'department_title' => $item ['department_title'],
                    'company_title' => $item ['company_title'],
                    'place_title' => $item ['place_title'],
                    'place_id' => $item ['place_id'],
                    'smena' => $item ['smena'],
                    'worker_id' => $item ['worker_id'],
                    'worker_link_1c' => $item ['worker_link_1c'],
                    'department_id' => $item ['department_id'],
                    'time_on_surface' => number_format($item['time_on_surface'], 0, ',', ' '),  // время на поверхности
                    'time_on_mine' => number_format($item['time_on_mine'], 0, ',', ' '),         //время нахождения в шахте
                    'time_on_summary' => number_format($item['time_on_summary'], 0, ',', ' '),   //время суммарное
                    'type_place_title' => $item ['type_place_title'],                                                   // название типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                    'kind_place_title' => $item ['kind_place_title'],                                                   // название вида места(поверхность/шахта)
                    'place_status_title' => $item ['place_status_title'],                                               // название статуса выработки (разрешенная/запрещенная)
                    'type_place_id' => $item ['type_place_id'],                                                         // ИД типа места (ламповая/надшахтное здание/руддвор/капитальная выработка/участковая выработка/забой)
                    'main_kind_place_id' => $item ['main_kind_place_id'],                                               // ИД главного вида места (kind_object_id)
                    'kind_place_id' => $item ['kind_place_id'],                                                         // ИД вида места (поверхность/шахта)
                );
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $model_final], $log->getLogAll());;
    }
}