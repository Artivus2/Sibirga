<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\statistic;

use backend\controllers\Assistant;
use backend\controllers\const_amicum\StatusEnumController;
use backend\controllers\const_amicum\TypeBriefingEnumController;
use backend\controllers\LogAmicum;
use Exception;
use frontend\controllers\handbooks\DepartmentController;
use frontend\controllers\ordersystem\ReportForPreviousPeriodController;
use frontend\models\Injunction;
use frontend\models\WorkerSiz;
use Throwable;
use yii\db\Query;
use yii\web\Controller;


class StatisticController extends Controller
{
    // контроллер содержит методы по расчету статистических данных и передаче их на фронт в модуль СТАТИСТИКА:
    // GetOccupationalIllness           -   метод получения статистики по профзаболеваниям
    // GetInjunctionOther               -   метод получения статистики нарушений и иных сведений о персонале
    // GetRangeAge                      -   Диапазон возраста
    // GetRangeExperience               -   Диапазон стажа
    // GetMedical                       -   метод получения статистики медосмотров
    // GetBriefingAttestation           -   метод получения статистики прохождения инструктажей, проверок знаний и аттестации
    // GetInquiry                       -   метод получения статистики происшествий
    // GetSPT                           -   метод получения статистики наличия и состояния средств пожаротушения
    // GetSIZ                           -   метод получения статистики наличия и состояния СИЗ
    // GetIndustrialSafetyExpertise     -   метод получения статистики по ЭПБ зданий сооружений, проектной документации, технических устройств
    // GetIndustrialStatistic           -   метод получения статистики по производству

    public function actionIndex()
    {
        echo 'я родился';
    }

    // GetOccupationalIllness - метод получения статистики по профзаболеваниям
    // входные параметры:
    //      year                             - год за который строим статистику
    //      month                            - месяц за который строим статистику
    //      company_department_id            - подразделение с учетом вложений для которого строим статистику
    //      period                           - период за который строим статистику год/месяц 'month/year'
    // выходные параметры:
    //      count_all_worker                 - всего сотрудников
    //      count_all_worker_man             - всего сотрудников мужчин
    //      count_all_worker_woman           - всего сотрудников женщин
    //      count_worker_with_illness        - Всего сотрудников с выявленными профзаболеваниями
    //      count_worker_with_illness_man    - Всего сотрудников с выявленными профзаболеваниями мужчин
    //      count_worker_with_illness_woman  - Всего сотрудников с выявленными профзаболеваниями женщин
    //      count_occupational_illness       - Количество профзаболеваний
    //      disease_percentage:              - круговая диаграмма причин заболеваний
    //          [reason_occupational_illness_id]    -   ключ причины заболеваний
    //              reason_occupational_illness_id          -   ключ причины заболеваний
    //              reason_occupational_illness_title       -   название причины заболеваний
    //              reason_occupational_illness_value       -   количество причин заболеваний
    //              percent                                 -   процент причин заболеваний
    //      dynamic_occupational:            - динамика выявления профзаболеваний по годам
    //          [year_occupational]                 - год
    //              year_occupational                       - год за который считаем количество выявленных заболеваний
    //              sum_occupational_illness_value          - суммарное количество выявленных заболеваний за этот год
    //      statistic_by_department:            - статистика по департаментам
    //          []                                  - массив
    //              company_department_id                   - ключ департамента
    //              department_title                        - наименование департамента
    //              sum_occupational_illness_value          - количество профзаболеваний по департаменту
    //      statistic_by_position:              - статистика по должностям
    //          []                                  - массив
    //              position_id                             - ключ должности
    //              position_title                          - наименование должности
    //              sum_occupational_illness_value          - количество профзаболеваний по должности
    //      statistic_by_age:                   - статистика по возрасту
    //          [id]                                  - ключ группы возраста
    //              id                                      - ключ группы возраста
    //              title                                   - наименование группы возраста
    //              sum_occupational_illness_value          - количество профзаболеваний по возрасту
    //      statistic_by_experience:            - статистика по возрасту
    //          [id]                                  - ключ группы стажа
    //              id                                      - ключ группы стажа
    //              title                                   - наименование группы стажа
    //              sum_occupational_illness_value          - количество профзаболеваний по стажу
    // алгоритм:
    // 1. обработка входных данных с фронта - если выбран период год, то ищем работающих людей до конца года, если выбран период месяц, то ищем людей работающих до конца этого месяца
    // если период год, то выбираем данные только за этот год, если период месяц, то выбираем данные только за этот месяц
    // 2. получение списка вложенных департаментов
    // 3. получение количества работающих людей с делением на мужчин и женщин на заданный период (с учетом того факт, что он еще не трудоустроился на заданный период)
    // 4. расчет и подготовка выходных данных по работающим сотрудникам компании
    // 5. расчет сотрудников с выявленными профзаболеваниям
    // 6. Диаграмма причин заболеваний в процентах за запрашиваемый период
    // 7. Строим динамику выявленных профзаболеваний
    // 8. Статистика профзаболеваний по департаментам
    // 9. Статистика профзаболеваний по должностям
    // 10. Статистика профзаболеваний по возрасту
    // 11. Статистика профзаболеваний по стажу
    // пример:
    // http://127.0.0.1/read-manager-amicum?controller=statistic\Statistic&method=GetOccupationalIllness&subscribe=&data={%22company_department_id%22:4029720,%22year%22:%222020%22,%22month%22:%2201%22,%22period%22:%22month%22}
    // http://127.0.0.1/read-manager-amicum?controller=statistic\Statistic&method=GetOccupationalIllness&subscribe=&data={%22company_department_id%22:4029720,%22year%22:%222020%22,%22month%22:%2202%22,%22period%22:%22year%22}
    public static function GetOccupationalIllness($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'GetOccupationalIllness';                                                                // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = null;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        try {
            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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

            // запись в БД начала выполнения скрипта
            $response = LogAmicum::LogAmicumStatistic($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                           // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            /** Метод начало */

            // обработка входных параметров от фронта
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'company_department_id') ||                                       // ключ департамента
                !property_exists($post_dec, 'year') ||                                                         // год
                !property_exists($post_dec, 'month') ||                                                        // месяц
                !property_exists($post_dec, 'period'))                                                         // период 'month/year'
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $period = $post_dec->period;                                                                                // период за который строится статистика
            $year = $post_dec->year;                                                                                    // год за который строится статистика
            $month = $post_dec->month;                                                                                  // месяц за который строится статистика
            if ($period === 'month') {
                $count_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);                                  // количество дней в месяце
                $date = date('Y-m-d', strtotime($year . '-' . $month . '-' . $count_day));                 // период за месяц до конца месяца
//                $filterMonth = "MONTH(occupational_illness.date_act)='" . $month . "'";                                 // задаем фильтрацию по месяцу
            } elseif ($period === 'year') {
                $date = date('Y-m-d', strtotime($year . '-12-31'));                                        // период за год до конца года
//                $filterMonth = null;                                                                                    // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
                $month = null;                                                                                    // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
            } else {
                throw new Exception($method_name . '. Некорректный период: ' . $period);
            }
//            $warnings[] = $date;

            $company_department_id = $post_dec->company_department_id;                                                  // подразделение по которому нужно получить статистику


            /** Отладка */
            $description = 'Обработал входные данные с фронта';                                                                      // описание текущей отладочной точки
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

            // ищем вложенные подразделения
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception($method_name . '. Ошибка получения вложенных департаментов' . $company_department_id);
            }
            /** Отладка */
            $description = 'Получил список вложенных департаментов';                                                                      // описание текущей отладочной точки
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

            // получаем количество работающих работников на заданную дату сгруппировнных по гендерному признаку
            $found_worker = (new Query())
                ->select('count(worker.id) as count_worker, employee.gender')
                ->from('worker')
                ->innerJoin('employee', 'employee.id=worker.employee_id')
                ->where(['in', 'worker.company_department_id', $company_departments])
                ->andWhere(['<=', 'worker.date_start', $date])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date],
                    ['is', 'worker.date_end', null]
                ])
                ->groupBy('employee.gender')
                ->indexBy('gender')
                ->all();
            /** Отладка */
            $description = 'Получил список работающих работников из БД';                                                                      // описание текущей отладочной точки
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

            // считаю статистику работников по гендерному признаку
            $result['count_all_worker_woman'] = 0;                                                                      // количество работников мужчин в подразделении
            $result['count_all_worker_man'] = 0;                                                                        // количество работников женщин в подразделении
            if (isset($found_worker['М'])) {
                $result['count_all_worker_man'] = (int)$found_worker['М']['count_worker'];
            }
            if (isset($found_worker['Ж'])) {
                $result['count_all_worker_woman'] = (int)$found_worker['Ж']['count_worker'];
            }
            $result['count_all_worker'] = $result['count_all_worker_woman'] + $result['count_all_worker_man'];        // всего работников в подразделении
            unset($found_worker);

            // Считаем сотрудников с выявленными профзаболеваниями
            $found_worker = (new Query())
                ->select('worker.id, employee.gender')
                ->from('occupational_illness')
                ->innerJoin('worker', 'occupational_illness.worker_id = worker.id')
                ->innerJoin('employee', 'employee.id=worker.employee_id')
                ->where(['in', 'worker.company_department_id', $company_departments])
//                ->andWhere(['<', 'occupational_illness.date_act', $date])
                ->andWhere("YEAR(occupational_illness.date_act)='" . $year . "'")
                ->andFilterWhere(['MONTH(occupational_illness.date_act)' => $month])
                ->groupBy('worker.id, employee.gender')
                ->all();
            $result['count_worker_with_illness'] = 0;
            $result['count_worker_with_illness_man'] = 0;
            $result['count_worker_with_illness_woman'] = 0;
            foreach ($found_worker as $item) {
                if ($item['gender'] == 'М') {
                    $result['count_worker_with_illness_man']++;
                } elseif ($item['gender'] == 'Ж') {
                    $result['count_worker_with_illness_woman']++;
                }
            }
            $result['count_worker_with_illness'] = $result['count_worker_with_illness_man'] + $result['count_worker_with_illness_woman'];
            unset($found_worker);
            /** Отладка */
            $description = 'Посчитали сотрудников с профзаболеваниями';                                                 // описание текущей отладочной точки
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

            // считаем количество прозаболеваний за заданный период
            $found_worker = (new Query())
                ->select('count(occupational_illness.id)')
                ->from('occupational_illness')
                ->where(['in', 'occupational_illness.company_department_id', $company_departments])
//                ->andWhere(['<', 'occupational_illness.date_act', $date])
                ->andWhere("YEAR(occupational_illness.date_act)='" . $year . "'")
                ->andFilterWhere(['MONTH(occupational_illness.date_act)' => $month])
                ->scalar();
            $result['count_occupational_illness'] = (int)$found_worker;

            unset($found_worker);
            /** Отладка */
            $description = 'Посчитали сотрудников с профзаболеваниями';                                                                      // описание текущей отладочной точки
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

            // Круговая диаграмма Заболевания ВПФ
            $found_worker = (new Query())
                ->select('reason_occupational_illness_id, reason_occupational_illness.title as reason_occupational_illness_title, count(occupational_illness.id) as reason_occupational_illness_value')
                ->from('occupational_illness')
                ->innerJoin('reason_occupational_illness', 'reason_occupational_illness.id = occupational_illness.reason_occupational_illness_id')
                ->where(['in', 'occupational_illness.company_department_id', $company_departments])
//                ->andWhere(['<', 'occupational_illness.date_act', $date])
                ->andWhere("YEAR(occupational_illness.date_act)='" . $year . "'")
                ->andFilterWhere(['MONTH(occupational_illness.date_act)' => $month])
                ->groupBy('reason_occupational_illness_id, reason_occupational_illness_title')
                ->indexBy('reason_occupational_illness_id')
                ->all();
            foreach ($found_worker as $item) {
                $found_worker_percent[$item['reason_occupational_illness_id']]['percent'] = round(($item['reason_occupational_illness_value'] / $result['count_occupational_illness']) * 100, 1);
                $found_worker_percent[$item['reason_occupational_illness_id']]['reason_occupational_illness_title'] = $item['reason_occupational_illness_title'];
                $found_worker_percent[$item['reason_occupational_illness_id']]['reason_occupational_illness_value'] = (int)$item['reason_occupational_illness_value'];
                $found_worker_percent[$item['reason_occupational_illness_id']]['reason_occupational_illness_id'] = $item['reason_occupational_illness_id'];
            }
            if (!isset($found_worker_percent)) {
                $result['disease_percentage'] = (object)array();
            } else {
                $result['disease_percentage'] = $found_worker_percent;
            }
            unset($found_worker);

            /** Отладка */
            $description = 'Круговая диаграмма Заболевания ВПФ';                                                                      // описание текущей отладочной точки
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

            // Динамика выявленных профзаболеваний по годам
            $found_worker = (new Query())
                ->select('YEAR(occupational_illness.date_act) as year_occupational, count(occupational_illness.id) as sum_occupational_illness_value')
                ->from('occupational_illness')
                ->where(['in', 'occupational_illness.company_department_id', $company_departments])
                ->groupBy('year_occupational')
                ->indexBy('year_occupational')
                ->all();
            if (!$found_worker) {
                $result['dynamic_occupational'] = (object)array();
            } else {
                $result['dynamic_occupational'] = $found_worker;
            }


            unset($found_worker);

            /** Отладка */
            $description = 'Динамика выявленных профзаболеваний по годам';                                                                      // описание текущей отладочной точки
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

            // Статистика профзаболеваний по участкам
            $found_worker = (new Query())
                ->select('company.title as department_title, occupational_illness.company_department_id as company_department_id, count(occupational_illness.id) as sum_occupational_illness_value')
                ->from('occupational_illness')
                ->innerJoin('company_department', 'company_department.id = occupational_illness.company_department_id')
                ->innerJoin('company', 'company.id = company_department.company_id')
                ->where(['in', 'occupational_illness.company_department_id', $company_departments])
                ->andWhere("YEAR(occupational_illness.date_act)='" . $year . "'")
                ->andFilterWhere(['MONTH(occupational_illness.date_act)' => $month])
                ->groupBy('company_department_id, department_title')
                ->orderBy(['sum_occupational_illness_value' => SORT_DESC])
                ->all();
            $result['statistic_by_department'] = $found_worker;

            unset($found_worker);

            /** Отладка */
            $description = 'Статистика профзаболеваний по участкам';                                                                      // описание текущей отладочной точки
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

            // Статистика профзаболеваний по должностям
            $found_worker = (new Query())
                ->select('position.title as position_title, occupational_illness.position_id as position_id, count(occupational_illness.id) as sum_occupational_illness_value')
                ->from('occupational_illness')
                ->innerJoin('position', 'position.id = occupational_illness.position_id')
                ->where(['in', 'occupational_illness.company_department_id', $company_departments])
                ->andWhere("YEAR(occupational_illness.date_act)='" . $year . "'")
                ->andFilterWhere(['MONTH(occupational_illness.date_act)' => $month])
                ->groupBy('position_id, position_title')
                ->orderBy(['sum_occupational_illness_value' => SORT_DESC])
                ->all();
            $result['statistic_by_position'] = $found_worker;

            unset($found_worker);

            /** Отладка */
            $description = 'Статистика профзаболеваний по должностям';                                                                      // описание текущей отладочной точки
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

            // Статистика профзаболеваний по возрасту
            $found_worker = (new Query())
                ->select('(YEAR(occupational_illness.date_act) - YEAR(employee.birthdate)) as worker_age')
                ->from('occupational_illness')
                ->innerJoin('worker', 'worker.id = occupational_illness.worker_id')
                ->innerJoin('employee', 'employee.id = worker.employee_id')
                ->where(['in', 'occupational_illness.company_department_id', $company_departments])
                ->andWhere("YEAR(occupational_illness.date_act)='" . $year . "'")
                ->andFilterWhere(['MONTH(occupational_illness.date_act)' => $month])
                ->all();
            foreach ($found_worker as $worker_age) {
                $age_group = self::GetRangeAge((int)$worker_age['worker_age']);
                $statistic_age[$age_group['id']]['id'] = $age_group['id'];
                $statistic_age[$age_group['id']]['title'] = $age_group['title'];
                if (!isset($statistic_age[$age_group['id']]['sum_occupational_illness_value'])) {
                    $statistic_age[$age_group['id']]['sum_occupational_illness_value'] = 0;
                }
                $statistic_age[$age_group['id']]['sum_occupational_illness_value']++;

            }
            if (!isset($statistic_age)) {
                $result['statistic_by_age'] = (object)array();
            } else {
                $result['statistic_by_age'] = $statistic_age;
            }

            unset($found_worker);

            /** Отладка */
            $description = 'Статистика профзаболеваний по возрасту';                                                                      // описание текущей отладочной точки
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

            // Статистика профзаболеваний по стажу
            $found_worker = (new Query())
                ->select('(YEAR(occupational_illness.date_act) - YEAR(worker.date_start)) as worker_experience')
                ->from('occupational_illness')
                ->innerJoin('worker', 'worker.id = occupational_illness.worker_id')
                ->where(['in', 'occupational_illness.company_department_id', $company_departments])
                ->andWhere("YEAR(occupational_illness.date_act)='" . $year . "'")
                ->andFilterWhere(['MONTH(occupational_illness.date_act)' => $month])
                ->all();
            foreach ($found_worker as $worker_experience) {
                $experience_group = self::GetRangeExperience((int)$worker_experience['worker_experience']);
                $statistic_experience[$experience_group['id']]['id'] = $experience_group['id'];
                $statistic_experience[$experience_group['id']]['title'] = $experience_group['title'];
                if (!isset($statistic_experience[$experience_group['id']]['sum_occupational_illness_value'])) {
                    $statistic_experience[$experience_group['id']]['sum_occupational_illness_value'] = 0;
                }
                $statistic_experience[$experience_group['id']]['sum_occupational_illness_value']++;

            }
            if (!isset($statistic_experience)) {
                $result['statistic_by_experience'] = (object)array();
            } else {
                $result['statistic_by_experience'] = $statistic_experience;
            }

            unset($found_worker);

            /** Отладка */
            $description = 'Статистика профзаболеваний по стажу';                                                                      // описание текущей отладочной точки
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

            /** Метод окончание */


        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                   // описание текущей отладочной точки
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

        // запись в БД начала выполнения скрипта
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                         // время окончания выполнения метода
        LogAmicum::LogAmicumStatistic($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        return $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }



    // GetInjunctionOther - метод получения статистики нарушений и иных сведений о персонале
    // входные параметры:
    //      year                             - год за который строим статистику
    //      month                            - месяц за который строим статистику
    //      company_department_id            - подразделение с учетом вложений для которого строим статистику
    //      period                           - период за который строим статистику год/месяц 'month/year'
    // выходные параметры:
    //      count_all_worker                        - всего сотрудников
    //      count_worker_with_injunction            - Всего сотрудников с зафиксированными нарушениями
    //      count_injunction                        - Количество нарушений
    //      injunction_by_experience_percentage:    - круговая диаграмма Статистика нарушений по стажу
    //          [injunction_by_experience_percentage_id]    -   ключ группы стажа
    //              injunction_by_experience_percentage_id          -   ключ группы стажа
    //              injunction_by_experience_percentage_title       -   название группы стажа
    //              injunction_by_experience_percentage_value       -   количество человек в группе стажа
    //              percent                                         -   процен причин заболеваний
    //      dynamic_injunction:            - динамика выявления нарушений по годам
    //          [year_injunction]                 - год
    //              year_injunction                                 - год за который считаем количество выявленных нарушений
    //              sum_injunction_value                            - суммарное количество выявленных нарушений за этот год
    //      statistic_by_department:            - статистика по департаментам
    //          []                                  - массив
    //              company_department_id                   - ключ департамента
    //              department_title                        - наименование департамента
    //              sum_injunction_value                    - количество нарушений по департаменту
    //      statistic_by_position:              - статистика по должностям
    //          []                                  - массив
    //              position_id                             - ключ должности
    //              position_title                          - наименование должности
    //              sum_injunction_value                    - количество нарушений по должности
    //      statistic_by_reason:                - статистика по причине нарушения
    //          [reason_danger_motion_id]           - ключ причины нарушений
    //              reason_danger_motion_id                 - ключ причины нарушения
    //              reason_danger_motion_title              - наименование причины нарушения
    //              sum_injunction_value                    - количество нарушений по причине
    //      statistic_by_direction:             - статистика по направлению нарушений
    //          [id]                                  - ключ направления нарушения
    //              violation_type_id                       - ключ направления нарушения
    //              violation_type_title                    - наименование направления нарушения
    //              sum_injunction_value                    - количество нарушений по направлению
    //      statistic_by_department_worker:     - статистика по проценту нарушителей в участке
    //          [id]                                  - ключ участка
    //              company_department_id                   - ключ департамента
    //              percent                                 - процент нарушителей на участке от общего числа на участке
    //              count_worker_in_department              - число нарушителей на участке
    //              count_worker_all                        - число работников на участке
    // алгоритм:
    // 1. обработка входных данных с фронта - если выбран период год, то ищем работающих людей до конца года, если выбран период месяц, то ищем людей работающих до конца этого месяца
    // если период год, то выбираем данные только за этот год, если период месяц, то выбираем данные только за этот месяц
    // 2. получение списка вложенных департаментов
    // 3. получение количества работающих людей на заданный период (с учетом того факт, что он еще не трудоустроился на заданный период)
    // 4. расчет и подготовка выходных данных по работающим сотрудникам компании
    // 5. Количество сотрудников с зафиксированными нарушениями
    // 6. Диаграмма Статистика нарушений по стажу
    // 7. Строим динамику выявленных нарушений
    // 8. Статистика нарушений по департаментам
    // 9. Статистика нарушений по должностям
    // 10. Статистика нарушений по причине
    // 11. Статистика нарушений по направлению
    // пример:
    // http://127.0.0.1/read-manager-amicum?controller=statistic\Statistic&method=GetInjunctionOther&subscribe=&data={%22company_department_id%22:4029720,%22year%22:%222020%22,%22month%22:%2201%22,%22period%22:%22month%22}
    // http://127.0.0.1/read-manager-amicum?controller=statistic\Statistic&method=GetInjunctionOther&subscribe=&data={%22company_department_id%22:4029720,%22year%22:%222020%22,%22month%22:%2202%22,%22period%22:%22year%22}
    public static function GetInjunctionOther($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'GetInjunctionOther';                                                                            // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = null;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта
        $statistic_experience = array();
        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        try {
            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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

            // запись в БД начала выполнения скрипта
            $response = LogAmicum::LogAmicumStatistic($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                           // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            /** Метод начало */

            // обработка входных параметров от фронта
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'company_department_id') ||                                       // ключ департамента
                !property_exists($post_dec, 'year') ||                                                         // год
                !property_exists($post_dec, 'month') ||                                                        // месяц
                !property_exists($post_dec, 'period'))                                                         // период 'month/year'
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $period = $post_dec->period;                                                                                // период за который строится статистика
            $year = $post_dec->year;                                                                                    // год за который строится статистика
            $month = $post_dec->month;                                                                                  // месяц за который строится статистика
            if ($period === 'month') {
                $count_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);                                  // количество дней в месяце
                $date = date('Y-m-d', strtotime($year . '-' . $month . '-' . $count_day));                 // период за месяц до конца месяца
//                $filterMonth = "MONTH(occupational_illness.date_act)='" . $month . "'";                                 // задаем фильтрацию по месяцу
            } elseif ($period === 'year') {
                $date = date('Y-m-d', strtotime($year . '-12-31'));                                        // период за год до конца года
//                $filterMonth = null;                                                                                    // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
                $month = null;                                                                                    // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
            } else {
                throw new Exception($method_name . '. Некорректный период: ' . $period);
            }
//            $warnings[] = $date;

            $company_department_id = $post_dec->company_department_id;                                                  // подразделение по которому нужно получить статистику


            /** Отладка */
            $description = 'Обработал входные данные с фронта';                                                                      // описание текущей отладочной точки
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

            // ищем вложенные подразделения
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception($method_name . '. Ошибка получения вложенных департаментов' . $company_department_id);
            }
            /** Отладка */
            $description = 'Получил список вложенных департаментов';                                                                      // описание текущей отладочной точки
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

            // получаем количество работающих работников на заданную дату
            $found_worker = (new Query())
                ->select('count(worker.id) as count_worker')
                ->from('worker')
                ->innerJoin('employee', 'employee.id=worker.employee_id')
                ->where(['in', 'worker.company_department_id', $company_departments])
                ->andWhere(['<=', 'worker.date_start', $date])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date],
                    ['is', 'worker.date_end', null]
                ])
                ->scalar();
            /** Отладка */
            $description = 'Получил список работающих работников из БД';                                                                      // описание текущей отладочной точки
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

            // считаю статистику работников
            $result['count_all_worker'] = (int)$found_worker;        // всего работников в подразделении
            unset($found_worker);

            // Считаем сотрудников с зафиксированными нарушениями
            $found_worker = Injunction::find()
                ->select(['injunction.worker_id as worker_id'])
                ->innerJoin('checking', 'checking.id = injunction.checking_id')
                ->where(['injunction.kind_document_id' => 2])                                                           // 2 - ПАБ
                ->andWhere(['in', 'injunction.company_department_id', $company_departments])
                ->andWhere("YEAR(checking.date_time_start)='" . $year . "'")
                ->andFilterWhere(['MONTH(checking.date_time_start)' => $month])
                ->groupBy('worker_id')
                ->count();
            $result['count_worker_with_injunction'] = (int)$found_worker;
            unset($found_worker);
            /** Отладка */
            $description = 'Посчитали сотрудников с зафиксированными нарушениям';                                                 // описание текущей отладочной точки
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

            // считаем количество нарушений за заданный период
            $found_worker = (new Query())
                ->select('count(injunction.id)')
                ->from('injunction_violation')
                ->innerJoin('injunction', 'injunction.id = injunction_violation.injunction_id')
                ->innerJoin('checking', 'checking.id = injunction.checking_id')
                ->where(['injunction.kind_document_id' => 2])                                                           // 2 - ПАБ
                ->andWhere(['in', 'injunction.company_department_id', $company_departments])
//                ->andWhere(['<', 'occupational_illness.date_act', $date])
                ->andWhere("YEAR(checking.date_time_start)='" . $year . "'")
                ->andFilterWhere(['MONTH(checking.date_time_start)' => $month])
                ->scalar();
            $result['count_injunction'] = (int)$found_worker;

            unset($found_worker);
            /** Отладка */
            $description = 'Посчитали количество нарушений';                                                                      // описание текущей отладочной точки
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

            // Круговая диаграмма Статистика нарушений по стажу
            // выгребаем все нарушения все работников, у работников берем дату начала работы, затем берем от туда год и высчитываем стаж на момент проведения ПАБ
            $found_worker = (new Query())
                ->select('(YEAR(checking.date_time_start) - YEAR(worker.date_start)) as worker_experience')
                ->from('injunction_violation')
                ->innerJoin('injunction', 'injunction.id = injunction_violation.injunction_id')
                ->innerJoin('worker', 'worker.id = injunction.worker_id')
                ->innerJoin('checking', 'checking.id = injunction.checking_id')
                ->where(['injunction.kind_document_id' => 2])                                                           // 2 - ПАБ
                ->andWhere(['in', 'injunction.company_department_id', $company_departments])
//                ->andWhere(['<', 'occupational_illness.date_act', $date])
                ->andWhere("YEAR(checking.date_time_start)='" . $year . "'")
                ->andFilterWhere(['MONTH(checking.date_time_start)' => $month])
                ->all();

            // типизируем стаж работниов и высчитываем количество
            foreach ($found_worker as $worker_experience) {
                $experience_group = self::GetRangeExperience((int)$worker_experience['worker_experience']);
                $statistic_experience[$experience_group['id']]['id'] = $experience_group['id'];
                $statistic_experience[$experience_group['id']]['title'] = $experience_group['title'];
                $statistic_experience[$experience_group['id']]['color'] = $experience_group['color'];
                if (!isset($statistic_experience[$experience_group['id']]['sum_injunction_by_experience_value'])) {
                    $statistic_experience[$experience_group['id']]['sum_injunction_by_experience_value'] = 0;
                }
                $statistic_experience[$experience_group['id']]['sum_injunction_by_experience_value']++;
            }

            // высчитываем процентное содеражение
            foreach ($statistic_experience as $item) {
                $found_worker_percent[$item['id']]['injunction_by_experience_percentage_id'] = $item['id'];
                $found_worker_percent[$item['id']]['injunction_by_experience_percentage_title'] = $item['title'];
                $found_worker_percent[$item['id']]['color'] = $item['color'];
                $found_worker_percent[$item['id']]['injunction_by_experience_percentage_value'] = (int)$item['sum_injunction_by_experience_value'];
                $found_worker_percent[$item['id']]['percent'] = round(($item['sum_injunction_by_experience_value'] / $result['count_injunction']) * 100, 1);
            }
            if (!isset($statistic_experience)) {
                $result['injunction_by_experience_percentage'] = (object)array();
            } else {
                $result['injunction_by_experience_percentage'] = $statistic_experience;
            }
            unset($found_worker);

            /** Отладка */
            $description = 'Круговая диаграмма Статистика нарушений по стажу';                                                                      // описание текущей отладочной точки
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

            // Динамика выявленных нарушений по годам
            $found_worker = (new Query())
                ->select('YEAR(checking.date_time_start) as year_injunction, count(injunction_violation.id) as sum_injunction_value')
                ->from('injunction_violation')
                ->innerJoin('injunction', 'injunction.id = injunction_violation.injunction_id')
                ->innerJoin('checking', 'checking.id = injunction.checking_id')
                ->where(['injunction.kind_document_id' => 2])                                                           // 2 - ПАБ
                ->andWhere(['in', 'injunction.company_department_id', $company_departments])
                ->andWhere("YEAR(checking.date_time_start)>'" . 2000 . "'")
                ->andWhere("YEAR(checking.date_time_start)<'" . 2500 . "'")
                ->groupBy('year_injunction')
                ->indexBy('year_injunction')
                ->all();
            if (!$found_worker) {
                $result['dynamic_injunction'] = (object)array();
            } else {
                $result['dynamic_injunction'] = $found_worker;
            }

            unset($found_worker);

            /** Отладка */
            $description = 'Динамика выявленных нарушений по годам';                                                                      // описание текущей отладочной точки
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

            // Статистика нарушений по участкам
            $found_worker = (new Query())
                ->select('company.title as department_title, injunction.company_department_id as company_department_id, count(injunction_violation.id) as sum_injunction_value')
                ->from('injunction_violation')
                ->innerJoin('injunction', 'injunction.id = injunction_violation.injunction_id')
                ->innerJoin('company_department', 'injunction.company_department_id = company_department.id')
                ->innerJoin('company', 'company_department.company_id = company.id')
                ->innerJoin('checking', 'checking.id = injunction.checking_id')
                ->where(['injunction.kind_document_id' => 2])                                                           // 2 - ПАБ
                ->andWhere(['in', 'injunction.company_department_id', $company_departments])
                ->andWhere("YEAR(checking.date_time_start)='" . $year . "'")
                ->andFilterWhere(['MONTH(checking.date_time_start)' => $month])
                ->groupBy('company_department_id, department_title')
                ->orderBy(['sum_injunction_value' => SORT_DESC])
                ->all();
            $result['statistic_by_department'] = $found_worker;

            unset($found_worker);

            // Статистика нарушителей по участкам
            // получаем всех нарушителей по участкам
            $found_workers = (new Query())
                ->select('injunction.company_department_id as company_department_id, injunction.worker_id as worker_id')
                ->from('injunction')
                ->innerJoin('checking', 'checking.id = injunction.checking_id')
                ->where(['injunction.kind_document_id' => 2])                                                           // 2 - ПАБ
                ->andWhere(['in', 'injunction.company_department_id', $company_departments])
                ->andWhere("YEAR(checking.date_time_start)='" . $year . "'")
                ->andFilterWhere(['MONTH(checking.date_time_start)' => $month])
                ->groupBy('company_department_id, worker_id')
                ->all();

            //считаем нарушителей по участкам
            $count_worker_in_dep = array();
            foreach ($found_workers as $worker) {
                if (!isset($count_worker[$worker['company_department_id']])) {
                    $count_worker_in_dep[$worker['company_department_id']] = 0;
                }
                $count_worker_in_dep[$worker['company_department_id']]++;
            }

            // получаем число работников на участке
            $worker_all = (new Query())
                ->select('company_department_id as company_department_id, count(id) as count_worker')
                ->from('worker')
                ->where(['in', 'worker.company_department_id', $company_departments])
                ->andWhere(['<=', 'worker.date_start', $date])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date],
                    ['is', 'worker.date_end', null]
                ])
                ->groupBy('company_department_id')
                ->indexBy('company_department_id')
                ->all();

            // считаем процент

            foreach ($count_worker_in_dep as $keys_dep => $count_worker) {
                if (isset($worker_all[$keys_dep])) {
                    $calc_worker[$keys_dep] = array(
                        'company_department_id' => $keys_dep,
                        'percent' => round(($count_worker / $worker_all[$keys_dep]['count_worker']) * 100, 1),
                        'count_worker_all' => $worker_all[$keys_dep]['count_worker'],
                        'count_worker_in_department' => $count_worker
                    );
                }
            }

            if (!isset($calc_worker)) {
                $result['statistic_by_department_worker'] = (object)array();
            } else {
                $result['statistic_by_department_worker'] = $calc_worker;
            }

            unset($found_worker);

            /** Отладка */
            $description = 'Статистика нарушений по участкам';                                                                      // описание текущей отладочной точки
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
//
            // Статистика нарушений по должностям
            $found_worker = (new Query())
                ->select('position.title as position_title, worker.position_id as position_id, count(injunction_violation.id) as sum_injunction_value')
                ->from('injunction_violation')
                ->innerJoin('injunction', 'injunction.id = injunction_violation.injunction_id')
                ->innerJoin('worker', 'injunction.worker_id = worker.id')
                ->innerJoin('position', 'position.id = worker.position_id')
                ->innerJoin('checking', 'checking.id = injunction.checking_id')
                ->where(['injunction.kind_document_id' => 2])                                                           // 2 - ПАБ
                ->andWhere(['in', 'injunction.company_department_id', $company_departments])
                ->andWhere("YEAR(checking.date_time_start)='" . $year . "'")
                ->andFilterWhere(['MONTH(checking.date_time_start)' => $month])
                ->groupBy('position_id, position_title')
                ->orderBy(['sum_injunction_value' => SORT_DESC])
                ->all();
            $result['statistic_by_position'] = $found_worker;

            unset($found_worker);

            /** Отладка */
            $description = 'Статистика нарушений по должностям';                                                                      // описание текущей отладочной точки
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

            // Статистика нарушений по причинам
            $found_worker = (new Query())
                ->select('reason_danger_motion.title as reason_danger_motion_title, reason_danger_motion.id as reason_danger_motion_id, count(injunction_violation.id) as sum_injunction_value')
                ->from('injunction_violation')
                ->innerJoin('injunction', 'injunction.id = injunction_violation.injunction_id')
                ->innerJoin('reason_danger_motion', 'injunction_violation.reason_danger_motion_id = reason_danger_motion.id')
                ->innerJoin('checking', 'checking.id = injunction.checking_id')
                ->where(['injunction.kind_document_id' => 2])                                                           // 2 - ПАБ
                ->andWhere(['in', 'injunction.company_department_id', $company_departments])
                ->andWhere("YEAR(checking.date_time_start)='" . $year . "'")
                ->andFilterWhere(['MONTH(checking.date_time_start)' => $month])
                ->groupBy('reason_danger_motion_id, reason_danger_motion_title')
                ->orderBy(['sum_injunction_value' => SORT_DESC])
                ->all();
            $result['statistic_by_reason'] = $found_worker;

            unset($found_worker);

            /** Отладка */
            $description = 'Статистика нарушений по направлениям';                                                                      // описание текущей отладочной точки
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

            // Статистика нарушений по причинам
            $found_worker = (new Query())
                ->select('violation_type.title as violation_type_title, violation_type.id as violation_type_id, count(injunction_violation.id) as sum_injunction_value')
                ->from('injunction_violation')
                ->innerJoin('injunction', 'injunction.id = injunction_violation.injunction_id')
                ->innerJoin('violation', 'injunction_violation.violation_id = violation.id')
                ->innerJoin('violation_type', 'violation.violation_type_id = violation_type.id')
                ->innerJoin('checking', 'checking.id = injunction.checking_id')
                ->where(['injunction.kind_document_id' => 2])                                                           // 2 - ПАБ
                ->andWhere(['in', 'injunction.company_department_id', $company_departments])
                ->andWhere("YEAR(checking.date_time_start)='" . $year . "'")
                ->andFilterWhere(['MONTH(checking.date_time_start)' => $month])
                ->groupBy('violation_type_id, violation_type_title')
                ->orderBy(['sum_injunction_value' => SORT_DESC])
                ->all();
            $result['statistic_by_direction'] = $found_worker;

            unset($found_worker);

            /** Отладка */
            $description = 'Статистика нарушений по направления';                                                                      // описание текущей отладочной точки
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

            /** Метод окончание */


        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                   // описание текущей отладочной точки
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

        // запись в БД начала выполнения скрипта
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                         // время окончания выполнения метода
        LogAmicum::LogAmicumStatistic($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);


        return $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }

    /**
     * Метод GetRangeAge() - Диапазон возраста
     * @param $year - количество лет
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example
     *
     * @author Якимов М.Н.
     * Created date: on 26.11.2019 15:20
     */
    public static function GetRangeAge($year)
    {
        if ($year >= 0 && $year <= 40) {
            $result = array('id' => 1, 'title' => 'Менее 40');
        } elseif ($year >= 41 && $year <= 45) {
            $result = array('id' => 2, 'title' => '41 - 45');
        } elseif ($year >= 46 && $year <= 50) {
            $result = array('id' => 3, 'title' => '46 - 50');
        } elseif ($year >= 51 && $year <= 55) {
            $result = array('id' => 4, 'title' => '51 - 55');
        } elseif ($year >= 56 && $year <= 60) {
            $result = array('id' => 5, 'title' => '56 - 60');
        } elseif ($year > 60) {
            $result = array('id' => 6, 'title' => 'Более 60');
        } else {
            $result = array('id' => -1, 'title' => 'Ошибка');
        }
        return $result;
    }

    /**
     * Метод GetRangeExperience() - Диапазон стажа
     * @param $exp
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * Входные обязательные параметры:
     * @example
     *
     * @author Якимов М.Н.
     * Created date: on 26.11.2019 15:34
     */
    public static function GetRangeExperience($exp)
    {
        if ($exp >= 0 && $exp <= 5) {
            $result = array('id' => 1, 'title' => 'Менее 5', 'color' => '#7c6580');
        } elseif ($exp >= 6 && $exp <= 10) {
            $result = array('id' => 2, 'title' => '6 - 10', 'color' => '#4d987c');
        } elseif ($exp >= 11 && $exp <= 15) {
            $result = array('id' => 3, 'title' => '11 - 15', 'color' => '#56698f');
        } elseif ($exp >= 16 && $exp <= 20) {
            $result = array('id' => 4, 'title' => '16 - 20', 'color' => '#598d9b');
        } elseif ($exp >= 21 && $exp <= 25) {
            $result = array('id' => 5, 'title' => '21 - 25', 'color' => '#59616e');
        } elseif ($exp >= 26 && $exp <= 30) {
            $result = array('id' => 6, 'title' => '26 - 30', 'color' => '#999999');
        } elseif ($exp > 30) {
            $result = array('id' => 7, 'title' => 'Более 30', 'color' => '#b55a6e');
        } else {
            $result = array('id' => -1, 'title' => 'Ошибка', 'color' => '#e6e6e6');
        }
        return $result;
    }

    // GetMedical - метод получения статистики медосмотров
    // входные параметры:
    //      year                             - год за который строим статистику
    //      month                            - месяц за который строим статистику
    //      company_department_id            - подразделение с учетом вложений для которого строим статистику
    //      period                           - период за который строим статистику год/месяц 'month/year'
    // выходные параметры:
    //      count_all_worker                 - всего сотрудников
    //      count_all_worker_man             - всего сотрудников мужчин
    //      count_all_worker_woman           - всего сотрудников женщин
    //      count_worker_with_medical        - Всего сотрудников с прошедших медосмотр
    //      count_worker_with_medical_man    - Всего сотрудников с прошедших медосмотр мужчин
    //      count_worker_with_medical_woman  - Всего сотрудников с прошедших медосмотр женщин
    //      med_report_percentage:           - круговая диаграмма статистика заключений медицинской комиссии
    //          [id]                               -   ключ заключения
    //              id                                      -   ключ заключения
    //              title                                   -   название заключения (годен/нет и т.д.)
    //              value                                   -   количество человек в группе заключения
    //              percent                                 -   процен людей в группе заключений
    //      statistic_by_department:            - статистика по департаментам
    //          []                                  - массив
    //              company_department_id                   - ключ департамента
    //              department_title                        - наименование департамента
    //              sum_worker_value_plan                   - количество сотрудников департамента
    //              sum_worker_value_fact                   - количество прошедших медосмотр

    // алгоритм:
    // 1. обработка входных данных с фронта - если выбран период год, то ищем работающих людей до конца года, если выбран период месяц, то ищем людей работающих до конца этого месяца
    // если период год, то выбираем данные только за этот год, если период месяц, то выбираем данные только за этот месяц
    // 2. получение списка вложенных департаментов
    // 3. получение количества работающих людей по гендерному признаку на заданный период (с учетом того факт, что он еще не трудоустроился на заданный период)
    // 4. расчет и подготовка выходных данных по работающим сотрудникам компании с учетом гендерного признака
    // 5. Количество сотрудников  прошедших МО по гендерному признаку
    // 6. Диаграмма Статистика заключений медицинской комиссии
    // 7. Получаем численность запрашиваемых участков
    // 8. Строим прохождения медицинского осмотра по шахте

    // пример:
    // http://127.0.0.1/read-manager-amicum?controller=statistic\Statistic&method=GetMedical&subscribe=&data={%22company_department_id%22:4029720,%22year%22:%222020%22,%22month%22:%2201%22,%22period%22:%22month%22}
    // http://127.0.0.1/read-manager-amicum?controller=statistic\Statistic&method=GetMedical&subscribe=&data={%22company_department_id%22:4029720,%22year%22:%222020%22,%22month%22:%2202%22,%22period%22:%22year%22}
    public static function GetMedical($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'GetMedical';                                                                                    // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = null;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта
        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        try {
            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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

            // запись в БД начала выполнения скрипта
            $response = LogAmicum::LogAmicumStatistic($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                           // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            /** Метод начало */

            // обработка входных параметров от фронта
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'company_department_id') ||                                       // ключ департамента
                !property_exists($post_dec, 'year') ||                                                         // год
                !property_exists($post_dec, 'month') ||                                                        // месяц
                !property_exists($post_dec, 'period'))                                                         // период 'month/year'
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $period = $post_dec->period;                                                                                // период за который строится статистика
            $year = $post_dec->year;                                                                                    // год за который строится статистика
            $month = $post_dec->month;                                                                                  // месяц за который строится статистика
            if ($period === 'month') {
                $count_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);                                  // количество дней в месяце
                $date = date('Y-m-d', strtotime($year . '-' . $month . '-' . $count_day));                 // период за месяц до конца месяца
//                $filterMonth = "MONTH(occupational_illness.date_act)='" . $month . "'";                                 // задаем фильтрацию по месяцу
            } elseif ($period === 'year') {
                $date = date('Y-m-d', strtotime($year . '-12-31'));                                        // период за год до конца года
//                $filterMonth = null;                                                                                    // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
                $month = null;                                                                                    // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
            } else {
                throw new Exception($method_name . '. Некорректный период: ' . $period);
            }
//            $warnings[] = $date;

            $company_department_id = $post_dec->company_department_id;                                                  // подразделение по которому нужно получить статистику


            /** Отладка */
            $description = 'Обработал входные данные с фронта';                                                                      // описание текущей отладочной точки
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

            // ищем вложенные подразделения
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception($method_name . '. Ошибка получения вложенных департаментов' . $company_department_id);
            }
            /** Отладка */
            $description = 'Получил список вложенных департаментов';                                                                      // описание текущей отладочной точки
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

            // получаем количество работающих работников на заданную дату сгруппировнных по гентерному признаку
            $found_worker = (new Query())
                ->select('count(worker.id) as count_worker, employee.gender')
                ->from('worker')
                ->innerJoin('employee', 'employee.id=worker.employee_id')
                ->where(['in', 'worker.company_department_id', $company_departments])
                ->andWhere(['<=', 'worker.date_start', $date])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date],
                    ['is', 'worker.date_end', null]
                ])
                ->groupBy('employee.gender')
                ->indexBy('gender')
                ->all();
            /** Отладка */
            $description = 'Получил список работающих работников из БД';                                                                      // описание текущей отладочной точки
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

            // считаю статистику работников по гендерному признаку
            $result['count_all_worker_woman'] = 0;                                                                      // количество работников мужчин в подразделении
            $result['count_all_worker_man'] = 0;                                                                        // количество работников женщин в подразделении
            if (isset($found_worker['М'])) {
                $result['count_all_worker_man'] = (int)$found_worker['М']['count_worker'];
            }
            if (isset($found_worker['Ж'])) {
                $result['count_all_worker_woman'] = (int)$found_worker['Ж']['count_worker'];
            }
            $result['count_all_worker'] = $result['count_all_worker_woman'] + $result['count_all_worker_man'];        // всего работников в подразделении
            unset($found_worker);

            // Считаем сотрудников прошедших медосмотр
            $found_worker = (new Query())
                ->select('worker.id, employee.gender')
                ->from('med_report')
                ->innerJoin('worker', 'med_report.worker_id = worker.id')
                ->innerJoin('employee', 'employee.id=worker.employee_id')
                ->where(['in', 'med_report.company_department_id', $company_departments])
//                ->andWhere(['<', 'occupational_illness.date_act', $date])
                ->andWhere("YEAR(med_report.med_report_date)='" . $year . "'")
                ->andFilterWhere(['MONTH(med_report.med_report_date)' => $month])
                ->groupBy('worker.id, employee.gender')
                ->all();
            $result['count_worker_with_medical'] = 0;
            $result['count_worker_with_medical_man'] = 0;
            $result['count_worker_with_medical_woman'] = 0;
            foreach ($found_worker as $item) {
                if ($item['gender'] == 'М') {
                    $result['count_worker_with_medical_man']++;
                } elseif ($item['gender'] == 'Ж') {
                    $result['count_worker_with_medical_woman']++;
                }
            }
            $result['count_worker_with_medical'] = $result['count_worker_with_medical_man'] + $result['count_worker_with_medical_woman'];
            unset($found_worker);
            /** Отладка */
            $description = 'Посчитали сотрудников прошедших медосмотр';                                                 // описание текущей отладочной точки
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

            // Круговая диаграмма Статистика заключений медицинской комиссии
            $found_worker = (new Query())
                ->select('group_med_report_result_id, group_med_report_result.title as group_med_report_result_title, count(med_report.id) as sum_med_report_value')
                ->from('med_report')
                ->innerJoin('worker', 'med_report.worker_id = worker.id')
                ->innerJoin('med_report_result', 'med_report.med_report_result_id = med_report_result.id')
                ->innerJoin('group_med_report_result', 'med_report_result.group_med_report_result_id = group_med_report_result.id')
                ->where(['in', 'med_report.company_department_id', $company_departments])
                ->andWhere("YEAR(med_report.med_report_date)='" . $year . "'")
                ->andFilterWhere(['MONTH(med_report.med_report_date)' => $month])
                ->groupBy('group_med_report_result_id, group_med_report_result_title')
                ->indexBy('group_med_report_result_id')
                ->all();
            foreach ($found_worker as $item) {
                $found_worker_percent[$item['group_med_report_result_id']]['id'] = $item['group_med_report_result_id'];
                $found_worker_percent[$item['group_med_report_result_id']]['title'] = $item['group_med_report_result_title'];
                $found_worker_percent[$item['group_med_report_result_id']]['value'] = (int)$item['sum_med_report_value'];
                $found_worker_percent[$item['group_med_report_result_id']]['percent'] = round(($item['sum_med_report_value'] / $result['count_worker_with_medical']) * 100, 1);
            }
            if (!isset($found_worker_percent)) {
                $result['med_report_percentage'] = (object)array();
            } else {
                $result['med_report_percentage'] = $found_worker_percent;
            }
            unset($found_worker);

            /** Отладка */
            $description = 'Круговая диаграмма Статистика Заключений медицинской комиссии';                                                                      // описание текущей отладочной точки
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

            // получаем численность интересующих нас участков плановую
            $count_workers_in_department_plan = (new Query())
                ->select('company.title as department_title, worker.company_department_id as company_department_id, count(worker.id) as sum_worker_value')
                ->from('worker')
                ->innerJoin('company_department', 'worker.company_department_id = company_department.id')
                ->innerJoin('company', 'company_department.company_id = company.id')
                ->where(['in', 'worker.company_department_id', $company_departments])
                ->andWhere(['<=', 'worker.date_start', $date])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date],
                    ['is', 'worker.date_end', null]
                ])
                ->groupBy('company_department_id')
                ->all();

            // формируем выходной массив на плановых цифрах
            foreach ($count_workers_in_department_plan as $comp_dep) {
                $statistic_by_department[$comp_dep['company_department_id']]['company_department_id'] = $comp_dep['company_department_id'];
                $statistic_by_department[$comp_dep['company_department_id']]['department_title'] = $comp_dep['department_title'];
                $statistic_by_department[$comp_dep['company_department_id']]['sum_worker_value_plan'] = (int)$comp_dep['sum_worker_value'];
                $statistic_by_department[$comp_dep['company_department_id']]['sum_worker_value_fact'] = 0;
            }
            unset($count_workers_in_department_plan);

            // Статистика прохождения медицинского осмотра по шахте
            $count_workers_in_department_fact = (new Query())
                ->select('company.title as department_title, med_report.company_department_id as company_department_id, count(med_report.id) as sum_worker_value')
                ->from('med_report')
                ->innerJoin('worker', 'med_report.worker_id = worker.id')
                ->innerJoin('company_department', 'worker.company_department_id = company_department.id')
                ->innerJoin('company', 'company_department.company_id = company.id')
                ->where(['in', 'med_report.company_department_id', $company_departments])
                ->andWhere("YEAR(med_report.med_report_date)='" . $year . "'")
                ->andFilterWhere(['MONTH(med_report.med_report_date)' => $month])
                ->groupBy('med_report.company_department_id, department_title')
                ->all();

            // формируем выходной массив на фактических цифрах
            foreach ($count_workers_in_department_fact as $comp_dep) {
                $statistic_by_department[$comp_dep['company_department_id']]['company_department_id'] = $comp_dep['company_department_id'];
                $statistic_by_department[$comp_dep['company_department_id']]['department_title'] = $comp_dep['department_title'];
                if (!isset($statistic_by_department[$comp_dep['company_department_id']]['sum_worker_value_plan'])) {
                    $statistic_by_department[$comp_dep['company_department_id']]['sum_worker_value_plan'] = 0;
                }
                $statistic_by_department[$comp_dep['company_department_id']]['sum_worker_value_fact'] = (int)$comp_dep['sum_worker_value'];
            }
            unset($count_workers_in_department_plan);

            if (!isset($statistic_by_department)) {
                $result['statistic_by_department'] = (object)array();
            } else {
                $result['statistic_by_department'] = $statistic_by_department;
            }
            unset($found_worker);

            /** Отладка */
            $description = 'Статистика прохождения медицинского осмотра по шахте';                                                                      // описание текущей отладочной точки
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

            /** Метод окончание */


        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                   // описание текущей отладочной точки
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

        // запись в БД начала выполнения скрипта
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                         // время окончания выполнения метода
        LogAmicum::LogAmicumStatistic($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        return $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }
    // GetBriefingAttestation - метод получения статистики прохождения инструктажей, проверок знаний и аттестации
    // входные параметры:
    //      year                             - год за который строим статистику
    //      month                            - месяц за который строим статистику
    //      company_department_id            - подразделение с учетом вложений для которого строим статистику
    //      period                           - период за который строим статистику год/месяц 'month/year'
    // выходные параметры:
    //      checking_worker_all         - общее число РАБОЧИХ на участке
    //      checking_worker:            - круговая диаграмма статистика проверки знаний рабочих
    //          ['pass']                        -   прошли проверку знаний
    //              id                                      -   тут лежит pass
    //              title                                   -   название пункта проверки знаний
    //              value                                   -   количество человек прошедших проверку знаний
    //              percent                                 -   процен людей прошедших проверку знаний
    //          ['pass_need']                   -   Нужно пройти проверку знаний
    //              id                                      -   тут лежит pass_need
    //              title                                   -   название пункта проверки знаний
    //              value                                   -   количество человек не прошедших проверку знаний (требуется проверка знаний)
    //              percent                                 -   процен людей которым надо пройти проверку знаний
    //      checking_itr_all            - общее число ИТР на участке
    //      checking_itr:               - круговая диаграмма статистика проверки знаний ИТР
    //          ['pass']                        -   прошли проверку знаний
    //              id                                      -   тут лежит pass
    //              title                                   -   название пункта проверки знаний
    //              value                                   -   количество человек прошедших проверку знаний
    //              percent                                 -   процен людей прошедших проверку знаний
    //          ['pass_need']                   -   Нужно пройти проверку знаний
    //              id                                      -   тут лежит pass_need
    //              title                                   -   название пункта проверки знаний
    //              value                                   -   количество человек не прошедших проверку знаний (требуется проверка знаний)
    //              percent                                 -   процен людей которым надо пройти проверку знаний
    //      attestation_all             - общее число СТАРШЕГО ИТР на участке
    //      attestation:                - круговая диаграмма статистика аттестации старшего ИТР
    //          ['pass']                        -   прошли проверку знаний
    //              id                                      -   тут лежит pass
    //              title                                   -   название пункта проверки знаний
    //              value                                   -   количество человек прошедших проверку знаний
    //              percent                                 -   процен людей прошедших проверку знаний
    //          ['pass_need']                   -   Нужно пройти проверку знаний
    //              id                                      -   тут лежит pass_need
    //              title                                   -   название пункта проверки знаний
    //              value                                   -   количество человек не прошедших проверку знаний (требуется проверка знаний)
    //              percent                                 -   процен людей которым надо пройти проверку знаний
    //      statistic_briefing_by_department_repeat:      - статистика инструктажей повторных по департаментам
    //          [company_department_id]                    - ключ департамента
    //              company_department_id                   - ключ департамента
    //              department_title                        - наименование департамента
    //              sum_worker_value_pass                   - количество сотрудников департамента прошедшие инструктаж
    //              sum_worker_value_pass_need              - количество сотрудников департамента  которым необходим инструктаж
    //              percent_pass                            - процент людей прошедших инструктажи
    //      statistic_briefing_by_department_primary:     - статистика инструктажей первичных по департаментам
    //          [company_department_id]                    - ключ департамента
    //              company_department_id                   - ключ департамента
    //              department_title                        - наименование департамента
    //              sum_worker_value_plan                   - количество сотрудников департамента
    //              sum_worker_value_fact                   - количество прошедших медосмотр
    //              percent                                 - процент людей прошедших инструктажи
    // алгоритм:
    // 1. обработка входных данных с фронта - если выбран период год, то ищем работающих людей до конца года, если выбран период месяц, то ищем людей работающих до конца этого месяца
    // если период год, то выбираем данные только за этот год, если период месяц, то выбираем данные только за этот месяц
    // 2. получение списка вложенных департаментов
    // 3. расчет статистики проверки знаний рабочих
    // 4. расчет статитсики проверки знаний ИТР
    // 5. расчет статитсики аттестаций
    // 6. Расчет статистики повторных инструктажей
    // 7. Расчет статистики первичных инструктажей
    // пример:
    // http://127.0.0.1/read-manager-amicum?controller=statistic\Statistic&method=GetBriefingAttestation&subscribe=&data={%22company_department_id%22:4029720,%22year%22:%222020%22,%22month%22:%2201%22,%22period%22:%22month%22}
    // http://127.0.0.1/read-manager-amicum?controller=statistic\Statistic&method=GetBriefingAttestation&subscribe=&data={%22company_department_id%22:4029720,%22year%22:%222020%22,%22month%22:%2202%22,%22period%22:%22year%22}
    public static function GetBriefingAttestation($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'GetBriefingAttestation';                                                                                   // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = null;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта
        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        try {
            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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

            // запись в БД начала выполнения скрипта
            $response = LogAmicum::LogAmicumStatistic($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                           // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
//                $errors[] = $response['errors'];
//                $warnings[] = $response['warnings'];
            } else {
//                $errors[] = $response['errors'];
//                $warnings[] = $response['warnings'];
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            /** Метод начало */

            // обработка входных параметров от фронта
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'company_department_id') ||                                       // ключ департамента
                !property_exists($post_dec, 'year') ||                                                         // год
                !property_exists($post_dec, 'month') ||                                                        // месяц
                !property_exists($post_dec, 'period'))                                                         // период 'month/year'
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $period = $post_dec->period;                                                                                // период за который строится статистика
            $year = $post_dec->year;                                                                                    // год за который строится статистика
            $month = $post_dec->month;                                                                                  // месяц за который строится статистика
            if ($period === 'month') {
                $count_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);                                  // количество дней в месяце
                $date = date('Y-m-d', strtotime($year . '-' . $month . '-' . $count_day));                 // период за месяц до конца месяца
//                $filterMonth = "MONTH(occupational_illness.date_act)='" . $month . "'";                                 // задаем фильтрацию по месяцу
            } elseif ($period === 'year') {
                $date = date('Y-m-d', strtotime($year . '-12-31'));                                        // период за год до конца года
//                $filterMonth = null;                                                                                    // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
                $month = null;                                                                                    // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
            } else {
                throw new Exception($method_name . '. Некорректный период: ' . $period);
            }
//            $warnings[] = $date;

            $company_department_id = $post_dec->company_department_id;                                                  // подразделение по которому нужно получить статистику


            /** Отладка */
            $description = 'Обработал входные данные с фронта';                                                                      // описание текущей отладочной точки
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

            // ищем вложенные подразделения
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception($method_name . '. Ошибка получения вложенных департаментов' . $company_department_id);
            }
            /** Отладка */
            $description = 'Получил список вложенных департаментов';                                                                      // описание текущей отладочной точки
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

            // прошли проверку знаний РАБОЧИЕ
            $result['checking_worker']['pass']['id'] = 'pass';
            $result['checking_worker']['pass']['title'] = 'Количество сотрудников, прошедших проверку знаний требований ОТ и ПБ (работники)';
            $result['checking_worker']['pass']['value'] = 0;
            $result['checking_worker']['pass']['percent'] = 0;
            // требуется проверка знаний
            $result['checking_worker']['pass_need']['id'] = 'pass_need';
            $result['checking_worker']['pass_need']['title'] = 'Количество сотрудников, которым необходимо пройти проверку знаний требований ОТ и ПБ (работники)';
            $result['checking_worker']['pass_need']['value'] = 0;
            $result['checking_worker']['pass_need']['percent'] = 0;
            // получаем РАБОЧИХ которые прошли провеку знаний
            $workers_pass_checking = (new Query())
                ->select('check_knowledge_worker.worker_id as worker_id, max(check_knowledge.date) as max_date')
                ->from('check_knowledge_worker')
                ->innerJoin('check_knowledge', 'check_knowledge.id = check_knowledge_worker.check_knowledge_id')
//                ->where(['in', 'check_knowledge.company_department_id', $company_departments])
                ->where(['check_knowledge.type_check_knowledge_id' => '1'])                                             // тип проверки знаний 1 - Рабочие проверка знаний
                ->andWhere(['check_knowledge_worker.status_id' => '79'])                                                // статус проверки знаний 79 - сдал
                ->andWhere(['<=', 'check_knowledge.date', $date])                                                       // прошедшие проверку до этого числа
                ->groupBy('worker_id')
                ->indexBy('worker_id')
                ->all();

            // получаем РАБОЧИХ (тип роли 3), которые должны проити проверку знаний
            $workers_plan = (new Query())
                ->select('worker.id as worker_id')
                ->from('worker')
                ->innerJoin('worker_object', 'worker.id = worker_object.worker_id')
                ->leftJoin('role', 'role.id = worker_object.role_id')
                ->innerJoin('company_department', 'worker.company_department_id = company_department.id')
                ->where(['in', 'worker.company_department_id', $company_departments])
                ->andWhere(
                    ['or',
                        ['role.type' => '3'],
                        ['is', 'worker_object.role_id', null]
                    ]
                )
                ->andWhere(['<=', 'worker.date_start', $date])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date],
                    ['is', 'worker.date_end', null]
                ])
                ->all();

            foreach ($workers_plan as $worker_item) {
                if (isset($workers_pass_checking[$worker_item['worker_id']]) and (date('Y-m-d', strtotime($workers_pass_checking[$worker_item['worker_id']]['max_date'] . ' +1 year')) > $date)) {
                    $result['checking_worker']['pass']['value']++;
                } else {
                    $result['checking_worker']['pass_need']['value']++;
                }
            }
            // общее число работников
            $result['checking_worker_all'] = $result['checking_worker']['pass']['value'] + $result['checking_worker']['pass_need']['value'];
            if ($result['checking_worker_all']) {
                $result['checking_worker']['pass']['percent'] = round(($result['checking_worker']['pass']['value'] / $result['checking_worker_all']) * 100, 1);
                $result['checking_worker']['pass_need']['percent'] = round(($result['checking_worker']['pass_need']['value'] / $result['checking_worker_all']) * 100, 1);
            }
            unset($workers_plan);
            unset($workers_pass_checking);
            /** Отладка */
            $description = 'Статистика проверки знаний РАБОЧИХ расчитана';                                                                      // описание текущей отладочной точки
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


            // прошли проверку знаний ИТР
            $result['checking_itr']['pass']['id'] = 'pass';
            $result['checking_itr']['pass']['title'] = 'Количество сотрудников, прошедших проверку знаний требований ОТ (ИТР)';
            $result['checking_itr']['pass']['value'] = 0;
            $result['checking_itr']['pass']['percent'] = 0;
            // требуется проверка знаний
            $result['checking_itr']['pass_need']['id'] = 'pass_need';
            $result['checking_itr']['pass_need']['title'] = 'Количество сотрудников, которым необходимо пройти проверку знаний требований ОТ (ИТР)';
            $result['checking_itr']['pass_need']['value'] = 0;
            $result['checking_itr']['pass_need']['percent'] = 0;
            // получаем ИТР которые прошли провеку знаний
            $workers_pass_checking = (new Query())
                ->select('check_knowledge_worker.worker_id as worker_id, max(check_knowledge.date) as max_date')
                ->from('check_knowledge_worker')
                ->innerJoin('check_knowledge', 'check_knowledge.id = check_knowledge_worker.check_knowledge_id')
//                ->where(['in', 'check_knowledge.company_department_id', $company_departments])
                ->where(['check_knowledge.type_check_knowledge_id' => 2])                                             // тип проверки знаний 2 - ИТР проверка знаний
                ->andWhere(['check_knowledge_worker.status_id' => 79])                                                // статус проверки знаний 79 - сдал
                ->andWhere(['<=', 'check_knowledge.date', $date])                                                       // прошедшие проверку до этого числа
                ->groupBy('worker_id')
                ->indexBy('worker_id')
                ->all();
//$warnings[]=$workers_pass_checking;
            // получаем ИТР (тип роли 2), которые должны проити проверку знаний
            $workers_plan = (new Query())
                ->select('worker.id as worker_id')
                ->from('worker')
                ->innerJoin('worker_object', 'worker.id = worker_object.worker_id')
                ->innerJoin('role', 'role.id = worker_object.role_id')
                ->innerJoin('company_department', 'worker.company_department_id = company_department.id')
                ->where(['in', 'worker.company_department_id', $company_departments])
                ->andWhere(['role.type' => 2])
                ->andWhere(['<=', 'worker.date_start', $date])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date],
                    ['is', 'worker.date_end', null]
                ])
                ->all();

            foreach ($workers_plan as $worker_item) {
                if (isset($workers_pass_checking[$worker_item['worker_id']]) and (date('Y-m-d', strtotime($workers_pass_checking[$worker_item['worker_id']]['max_date'] . ' +1 year')) > $date)) {
                    $result['checking_itr']['pass']['value']++;
                } else {
                    $result['checking_itr']['pass_need']['value']++;
                }
            }
            // общее число работников
            $result['checking_itr_all'] = $result['checking_itr']['pass']['value'] + $result['checking_itr']['pass_need']['value'];
            if ($result['checking_itr_all']) {
                $result['checking_itr']['pass']['percent'] = round(($result['checking_itr']['pass']['value'] / $result['checking_itr_all']) * 100, 1);
                $result['checking_itr']['pass_need']['percent'] = round(($result['checking_itr']['pass_need']['value'] / $result['checking_itr_all']) * 100, 1);
            }
            unset($workers_plan);
            unset($workers_pass_checking);
            /** Отладка */
            $description = 'Статистика проверки знаний ИТР расчитана';                                                                      // описание текущей отладочной точки
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

            // статистика аттестации старшего ИТР
            $result['attestation']['pass']['id'] = 'pass';
            $result['attestation']['pass']['title'] = 'Количество ИТР, прошедших аттестацию в области Промышленной Безопасности';
            $result['attestation']['pass']['value'] = 0;
            $result['attestation']['pass']['percent'] = 0;
            // требуется проверка знаний
            $result['attestation']['pass_need']['id'] = 'pass_need';
            $result['attestation']['pass_need']['title'] = 'Количество ИТР, которым необходимо пройти аттестацию в области Промышленной Безопасности';
            $result['attestation']['pass_need']['value'] = 0;
            $result['attestation']['pass_need']['percent'] = 0;
            // получаем старший ИТР который прошел АТТЕСТАЦИЮ
            $workers_pass_checking = (new Query())
                ->select('check_knowledge_worker.worker_id as worker_id, max(check_knowledge.date) as max_date')
                ->from('check_knowledge_worker')
                ->innerJoin('check_knowledge', 'check_knowledge.id = check_knowledge_worker.check_knowledge_id')
//                ->where(['in', 'check_knowledge.company_department_id', $company_departments])
                ->where(['check_knowledge.type_check_knowledge_id' => '3'])                                             // тип проверки знаний 3 - Аттестация
                ->andWhere(['check_knowledge_worker.status_id' => '79'])                                                // статус проверки знаний 79 - сдал
                ->andWhere(['<=', 'check_knowledge.date', $date])                                                       // прошедшие проверку до этого числа
                ->groupBy('worker_id')
                ->indexBy('worker_id')
                ->all();

            // получаем старший ИТР (тип роли 1), которые должны проити проверку знаний
            $workers_plan = (new Query())
                ->select('worker.id as worker_id')
                ->from('worker')
                ->innerJoin('worker_object', 'worker.id = worker_object.worker_id')
                ->innerJoin('role', 'role.id = worker_object.role_id')
                ->innerJoin('company_department', 'worker.company_department_id = company_department.id')
                ->where(['in', 'worker.company_department_id', $company_departments])
                ->andWhere(['role.type' => '1'])
                ->andWhere(['<=', 'worker.date_start', $date])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date],
                    ['is', 'worker.date_end', null]
                ])
                ->all();

            foreach ($workers_plan as $worker_item) {
                if (isset($workers_pass_checking[$worker_item['worker_id']]) and (date('Y-m-d', strtotime($workers_pass_checking[$worker_item['worker_id']]['max_date'] . ' +5 year')) > $date)) {
                    $result['attestation']['pass']['value']++;
                } else {
                    $result['attestation']['pass_need']['value']++;
                }
            }
            // общее число работников
            $result['attestation_all'] = $result['attestation']['pass']['value'] + $result['attestation']['pass_need']['value'];
            if ($result['attestation_all']) {
                $result['attestation']['pass']['percent'] = round(($result['attestation']['pass']['value'] / $result['attestation_all']) * 100, 1);
                $result['attestation']['pass_need']['percent'] = round(($result['attestation']['pass_need']['value'] / $result['attestation_all']) * 100, 1);
            }

            unset($workers_plan);
            unset($workers_pass_checking);
            /** Отладка */
            $description = 'Статистика аттестации старшего ИТР расчитана';                                                                      // описание текущей отладочной точки
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


            // строим статистику инструктажей повторных
            $workers_pass_briefing = (new Query())
                ->select('briefer.worker_id as worker_id, max(briefer.date_time) as max_date')
                ->from('briefer')
                ->innerJoin('briefing', 'briefing.id = briefer.briefing_id')
                ->where(['briefing.type_briefing_id' => 2])                                                             // тип инструктажа 2 - Повторный
                ->andWhere(['briefer.status_id' => StatusEnumController::BRIEFING_FAMILIAR])                            // статус инструктажа 69 ознакомлен
                ->andWhere(['<=', 'briefer.date_time', $date])                                                          // прошедшие инструктажи до этого числа
                ->groupBy('worker_id')
                ->indexBy('worker_id')
                ->all();

            //получаем список людей сгруппированных по подразделениям, которым нужно провести инструктаж
            $workers_briefing_plan = (new Query())
                ->select('worker.id as worker_id, worker.company_department_id as company_department_id, company.title as department_title')
                ->from('worker')
                ->innerJoin('company_department', 'worker.company_department_id = company_department.id')
                ->innerJoin('company', 'company_department.company_id = company.id')
                ->where(['in', 'worker.company_department_id', $company_departments])
                ->andWhere(['<=', 'worker.date_start', $date])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date],
                    ['is', 'worker.date_end', null]
                ])
                ->all();

            // считаем с учетом группировки по департаментам нужно или нет проходить инструктаж
            $statistic_briefing_by_department_repeat = array();
            foreach ($workers_briefing_plan as $worker_item) {
                if (!isset($statistic_briefing_by_department_repeat[$worker_item['company_department_id']])) {
                    $statistic_briefing_by_department_repeat[$worker_item['company_department_id']]['company_department_id'] = $worker_item['company_department_id'];
                    $statistic_briefing_by_department_repeat[$worker_item['company_department_id']]['department_title'] = $worker_item['department_title'];
                    $statistic_briefing_by_department_repeat[$worker_item['company_department_id']]['sum_worker_value_pass'] = 0;
                    $statistic_briefing_by_department_repeat[$worker_item['company_department_id']]['sum_worker_value_pass_need'] = 0;
                }

                if (isset($workers_pass_briefing[$worker_item['worker_id']]) and (date('Y-m-d', strtotime($workers_pass_briefing[$worker_item['worker_id']]['max_date'] . ' +3 month')) > $date)) {
                    $statistic_briefing_by_department_repeat[$worker_item['company_department_id']]['sum_worker_value_pass']++;
                } else {
                    $statistic_briefing_by_department_repeat[$worker_item['company_department_id']]['sum_worker_value_pass_need']++;
                }
            }

            // рассчитываем процентное содержание прошедших инструктажи повторные
            foreach ($statistic_briefing_by_department_repeat as $item) {
                $sum_worker_value = ($item['sum_worker_value_pass_need'] + $item['sum_worker_value_pass']);
                if ($sum_worker_value) {
                    $statistic_briefing_by_department_repeat[$item['company_department_id']]['percent_pass'] = round(($item['sum_worker_value_pass'] / $sum_worker_value) * 100, 1);
                } else {
                    $statistic_briefing_by_department_repeat[$item['company_department_id']]['percent_pass'] = 0;
                }
            }

            if (!isset($statistic_briefing_by_department_repeat)) {
                $result['statistic_briefing_by_department_repeat'] = (object)array();
            } else {
                $result['statistic_briefing_by_department_repeat'] = $statistic_briefing_by_department_repeat;
            }

            unset($worker_item);
            unset($item);
            unset($sum_worker_value);
            unset($workers_pass_briefing);
            unset($statistic_briefing_by_department_repeat);


            /** Отладка */
            $description = 'Статистика повторных инструктажей построена';                                                                      // описание текущей отладочной точки
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

            // строим статистику инструктажей первичных
            $workers_pass_briefing = (new Query())
                ->select('briefer.worker_id as worker_id, max(briefer.date_time) as max_date')
                ->from('briefer')
                ->innerJoin('briefing', 'briefing.id = briefer.briefing_id')
                ->where(['briefing.type_briefing_id' => 1])                                                             // тип инструктажа 1 - первичных
                ->andWhere(['briefer.status_id' => StatusEnumController::BRIEFING_FAMILIAR])                            // статус инструктажа 69 ознакомлен
                ->andWhere(['<=', 'briefer.date_time', $date])                                                          // прошедшие инструктажи до этого числа
                ->groupBy('worker_id')
                ->indexBy('worker_id')
                ->all();

            // считаем с учетом группировки по департаментам нужно или нет проходить инструктаж
            $statistic_briefing_by_department_primary = array();
            foreach ($workers_briefing_plan as $worker_item) {
                if (!isset($statistic_briefing_by_department_primary[$worker_item['company_department_id']])) {
                    $statistic_briefing_by_department_primary[$worker_item['company_department_id']]['company_department_id'] = $worker_item['company_department_id'];
                    $statistic_briefing_by_department_primary[$worker_item['company_department_id']]['department_title'] = $worker_item['department_title'];
                    $statistic_briefing_by_department_primary[$worker_item['company_department_id']]['sum_worker_value_pass'] = 0;
                    $statistic_briefing_by_department_primary[$worker_item['company_department_id']]['sum_worker_value_pass_need'] = 0;
                }

                if (isset($workers_pass_briefing[$worker_item['worker_id']])) {
                    $statistic_briefing_by_department_primary[$worker_item['company_department_id']]['sum_worker_value_pass']++;
                } else {
                    $statistic_briefing_by_department_primary[$worker_item['company_department_id']]['sum_worker_value_pass_need']++;
                }
            }
//$warnings[]=$statistic_briefing_by_department_primary;
            // рассчитываем процентное содержание прошедших инструктажи повторные
            foreach ($statistic_briefing_by_department_primary as $item) {
                $sum_worker_value = ($item['sum_worker_value_pass_need'] + $item['sum_worker_value_pass']);
                if ($sum_worker_value) {
                    $statistic_briefing_by_department_primary[$item['company_department_id']]['percent_pass'] = round(($item['sum_worker_value_pass'] / $sum_worker_value) * 100, 1);
                } else {
                    $statistic_briefing_by_department_primary[$item['company_department_id']]['percent_pass'] = 0;
                }
            }

            if (!isset($statistic_briefing_by_department_primary)) {
                $result['statistic_briefing_by_department_primary'] = (object)array();
            } else {
                $result['statistic_briefing_by_department_primary'] = $statistic_briefing_by_department_primary;
            }

            unset($worker_item);
            unset($item);
            unset($sum_worker_value);
            unset($statistic_briefing_by_department_repeat);


            /** Отладка */
            $description = 'Статистика первичных инструктажей построена';                                                                      // описание текущей отладочной точки
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

            /** Метод окончание */


        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                   // описание текущей отладочной точки
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

        // запись в БД начала выполнения скрипта
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                         // время окончания выполнения метода
        LogAmicum::LogAmicumStatistic($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        return $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }


    // GetInquiry - метод получения статистики происшествий
    // входные параметры:
    //      year                            - год за который строим статистику
    //      month                           - месяц за который строим статистику
    //      company_department_id           - подразделение с учетом вложений для которого строим статистику
    //      period                          - период за который строим статистику год/месяц 'month/year'
    // выходные параметры:
    //      count_inquiry_all               - количество всех происшествий
    //      count_crash                     - количество аварий
    //      count_miscellaneous             - количесвто несчастных случаев
    //      count_incident                  - количесто инцидентов
    //      count_other                     - количество прочих происшествий
    //      statistic_miscellaneous_by_place:      - статистика несчастных случаев по местам
    //          []                    - массив
    //              place_name                              - наименование департамента
    //              sum_event_pb_value                      - количество происшествий
    //      statistic_miscellaneous_by_accident:    - статистика несчастных случаев по видам несчастных случаев
    //          []                    - массив
    //              kind_accident_id                        - ключ вида несчастного случая
    //              kind_accident_title                     - наименование вида несчастного случая
    //              sum_event_pb_value                      - количество несчастных случаев в виде
    //      statistic_incident_by_place:            - статистика инцидентов по видам несчастных случаев
    //          []                    - массив
    //              place_name                              - наименование департамента
    //              sum_event_pb_value                      - количество происшествий
    //      statistic_incident_by_incident:         - статистика инцидентов по видам инцидентов
    //          []                    - массив
    //              kind_incident_id                        - ключ вида инцидентов
    //              kind_incident_title                     - наименование вида инцидента
    //              sum_event_pb_value                      - количество инцидентов в виде
    //      dynamic_inquiry:                        - динамика происшествий по видам по годам
    //          crash:                                  - аварии
    //              [event_pb_year]                         - год
    //                  case_pb_title                           - название происшествия
    //                  event_pb_year                           - год
    //                  sum_event_pb_value                      - количество происшествий
    //          miscellaneous:                          - несчастные случаи
    //              [event_pb_year]
    //                  case_pb_title                           - название происшествия
    //                  event_pb_year                           - год
    //                  sum_event_pb_value                      - количество происшествий
    //          incident:                               - инцидент
    //              [event_pb_year]
    //                  case_pb_title                           - название происшествия
    //                  event_pb_year                           - год
    //                  sum_event_pb_value                      - количество происшествий
    //          other:                                  - прочие происшествия
    //              [event_pb_year]
    //                  case_pb_title                           - название происшествия
    //                  event_pb_year                           - год
    //                  sum_event_pb_value                      - количество происшествий
    // алгоритм:
    // 1. обработка входных данных с фронта - если выбран период год, то ищем работающих людей до конца года, если выбран период месяц, то ищем людей работающих до конца этого месяца
    // если период год, то выбираем данные только за этот год, если период месяц, то выбираем данные только за этот месяц
    // 2. получение списка вложенных департаментов
    // 3. получаем статистику происшествий по группам (несчастный случай, инцидент, авария, прочие происшествия)
    // 4. создаем результирующий массив с учетом групп и расчета полного количества
    // 5. получаем статистику по несчастным случаям с группировкой по местам
    // 6. получаем статистику по несчастным случаям с группировкой по видам
    // 7. получаем статистику по инцидентам с группировкой по местам
    // 8. получаем статистику по инцидентам с группировкой по видам
    // 9. получаем динамику происшествий по видам по годам
    // пример:
    // http://127.0.0.1/read-manager-amicum?controller=statistic\Statistic&method=GetInquiry&subscribe=&data={%22company_department_id%22:4029720,%22year%22:%222020%22,%22month%22:%2201%22,%22period%22:%22month%22}
    // http://127.0.0.1/read-manager-amicum?controller=statistic\Statistic&method=GetInquiry&subscribe=&data={%22company_department_id%22:4029720,%22year%22:%222020%22,%22month%22:%2202%22,%22period%22:%22year%22}
    public static function GetInquiry($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'GetInquiry';                                                                                   // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = null;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта
        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        try {
            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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

            // запись в БД начала выполнения скрипта
            $response = LogAmicum::LogAmicumStatistic($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                           // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            /** Метод начало */

            // обработка входных параметров от фронта
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'company_department_id') ||                                       // ключ департамента
                !property_exists($post_dec, 'year') ||                                                         // год
                !property_exists($post_dec, 'month') ||                                                        // месяц
                !property_exists($post_dec, 'period'))                                                         // период 'month/year'
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $period = $post_dec->period;                                                                                // период за который строится статистика
            $year = $post_dec->year;                                                                                    // год за который строится статистика
            $month = $post_dec->month;                                                                                  // месяц за который строится статистика
            if ($period === 'month') {
//                $count_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);                                  // количество дней в месяце
//                $date = date('Y-m-d', strtotime($year . '-' . $month . '-' . $count_day));                 // период за месяц до конца месяца
//                $filterMonth = "MONTH(occupational_illness.date_act)='" . $month . "'";                                 // задаем фильтрацию по месяцу
            } elseif ($period === 'year') {
//                $date = date('Y-m-d', strtotime($year . '-12-31'));                                        // период за год до конца года
//                $filterMonth = null;                                                                                    // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
                $month = null;                                                                                    // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
            } else {
                throw new Exception($method_name . '. Некорректный период: ' . $period);
            }
//            $warnings[] = $date;

            $company_department_id = $post_dec->company_department_id;                                                  // подразделение по которому нужно получить статистику


            /** Отладка */
            $description = 'Обработал входные данные с фронта';                                                                      // описание текущей отладочной точки
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

            // ищем вложенные подразделения
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception($method_name . '. Ошибка получения вложенных департаментов' . $company_department_id);
            }
            /** Отладка */
            $description = 'Получил список вложенных департаментов';                                                                      // описание текущей отладочной точки
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


            // получаем статистику происшествий по группам (несчастный случай, инцидент, авария, прочие происшествия)
            $count_event_pb = (new Query())
                ->select('event_pb.case_pb_id as case_pb_id, count(event_pb.id) as sum_event_pb_value')
                ->from('event_pb')
                ->where(['in', 'event_pb.company_department_id', $company_departments])
                ->andWhere("YEAR(event_pb.date_time_event)='" . $year . "'")
                ->andFilterWhere(['MONTH(event_pb.date_time_event)' => $month])
                ->groupBy('case_pb_id')
                ->indexBy('case_pb_id')
                ->all();

            // если вид происшествия case_pb = 1, авария то формируем выходящее значение
            if (isset($count_event_pb[1])) {
                $result['count_crash'] = (int)$count_event_pb[1]['sum_event_pb_value'];
            } else {
                $result['count_crash'] = 0;
            }

            // если вид происшествия case_pb = 2, несчастный случай то формируем выходящее значение
            if (isset($count_event_pb[2])) {
                $result['count_miscellaneous'] = (int)$count_event_pb[2]['sum_event_pb_value'];
            } else {
                $result['count_miscellaneous'] = 0;
            }

            // если вид происшествия case_pb = 3, инцидент то формируем выходящее значение
            if (isset($count_event_pb[3])) {
                $result['count_incident'] = (int)$count_event_pb[3]['sum_event_pb_value'];
            } else {
                $result['count_incident'] = 0;
            }

            // рассчитываем количество прочий происшествий
            $result['count_other'] = 0;
            foreach ($count_event_pb as $event_pb) {
                if ($event_pb['case_pb_id'] >= 4) {
                    $result['count_other']++;
                }
            }
            // рассчитываем полное количество происшествий
            $result['count_inquiry_all'] = $result['count_crash'] + $result['count_miscellaneous'] + $result['count_incident'] + $result['count_other'];
            unset($count_event_pb);

            /** Отладка */
            $description = 'Статистика происшествий посчитана этап 1';                                                                      // описание текущей отладочной точки
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

            // получаем статистику по несчастным случаям с группировкой по местам
            $count_miscellaneous = (new Query())
                ->select('company.title as place_name, count(event_pb.id) as sum_event_pb_value')
                ->from('event_pb')
                ->innerJoin('company_department', 'company_department.id=event_pb.company_department_id')
                ->innerJoin('company', 'company.id=company_department.company_id')
                ->where(['in', 'event_pb.company_department_id', $company_departments])
                ->andWhere(['case_pb_id' => 2])
                ->andWhere("YEAR(event_pb.date_time_event)='" . $year . "'")
                ->andFilterWhere(['MONTH(event_pb.date_time_event)' => $month])
                ->groupBy('company.title')
                ->orderBy(['sum_event_pb_value' => SORT_DESC])
                ->all();

            if ($count_miscellaneous) {
                $result['statistic_miscellaneous_by_place'] = $count_miscellaneous;
            } else {
                $result['statistic_miscellaneous_by_place'] = array();
            }
            unset($count_miscellaneous);

            /** Отладка */
            $description = 'Статистика по несчастным случаям с группировкой по местам';                                                                      // описание текущей отладочной точки
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

            // получаем статистику по несчастным случаям с группировкой по видам несчастных случаев
            $count_miscellaneous = (new Query())
                ->select('kind_accident.title as kind_accident_title, kind_accident.id as kind_accident_id, count(event_pb.id) as sum_event_pb_value')
                ->from('event_pb')
                ->innerJoin('kind_accident', 'kind_accident.id=event_pb.kind_mishap_id')
                ->where(['in', 'event_pb.company_department_id', $company_departments])
                ->andWhere(['case_pb_id' => 2])
                ->andWhere("YEAR(event_pb.date_time_event)='" . $year . "'")
                ->andFilterWhere(['MONTH(event_pb.date_time_event)' => $month])
                ->groupBy('kind_accident_id')
                ->orderBy(['sum_event_pb_value' => SORT_DESC])
                ->all();

            if ($count_miscellaneous) {
                $result['statistic_miscellaneous_by_accident'] = $count_miscellaneous;
            } else {
                $result['statistic_miscellaneous_by_accident'] = array();
            }
            unset($count_miscellaneous);

            /** Отладка */
            $description = 'Статистика по несчастным случаям с группировкой по видам несчастных случаев';                                                                      // описание текущей отладочной точки
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

            // получаем статистику по инцидентам с группировкой по местам
            $count_incident = (new Query())
                ->select('company.title as place_name, count(event_pb.id) as sum_event_pb_value')
                ->from('event_pb')
                ->innerJoin('company_department', 'company_department.id=event_pb.company_department_id')
                ->innerJoin('company', 'company.id=company_department.company_id')
                ->where(['in', 'event_pb.company_department_id', $company_departments])
                ->andWhere(['case_pb_id' => 3])
                ->andWhere("YEAR(event_pb.date_time_event)='" . $year . "'")
                ->andFilterWhere(['MONTH(event_pb.date_time_event)' => $month])
                ->groupBy('company.title')
                ->orderBy(['sum_event_pb_value' => SORT_DESC])
                ->all();

            if ($count_incident) {
                $result['statistic_incident_by_place'] = $count_incident;
            } else {
                $result['statistic_incident_by_place'] = array();
            }
            unset($count_incident);

            /** Отладка */
            $description = 'Статистика по инцидентам с группировкой по местам';                                                                      // описание текущей отладочной точки
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

            // получаем статистику по инцидентам с группировкой по видам инцидентов
            $count_incident = (new Query())
                ->select('kind_incident.title as kind_incident_title, kind_incident.id as kind_incident_id, count(event_pb.id) as sum_event_pb_value')
                ->from('event_pb')
                ->innerJoin('kind_incident', 'kind_incident.id=event_pb.kind_incident_id')
                ->where(['in', 'event_pb.company_department_id', $company_departments])
                ->andWhere(['case_pb_id' => 3])
                ->andWhere("YEAR(event_pb.date_time_event)='" . $year . "'")
                ->andFilterWhere(['MONTH(event_pb.date_time_event)' => $month])
                ->groupBy('kind_incident_id')
                ->orderBy(['sum_event_pb_value' => SORT_DESC])
                ->all();

            if ($count_incident) {
                $result['statistic_incident_by_incident'] = $count_incident;
            } else {
                $result['statistic_incident_by_incident'] = array();
            }
            unset($count_incident);

            /** Отладка */
            $description = 'Статистика по инцидентам с группировкой по видам несчастных случаев';                                                                      // описание текущей отладочной точки
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


            // получаем динамику травматизма по годам
            $count_event_pb = (new Query())
                ->select('YEAR(event_pb.date_time_event) as event_pb_year, event_pb.case_pb_id as case_pb_id, count(event_pb.id) as sum_event_pb_value')
                ->from('event_pb')
                ->where(['in', 'event_pb.company_department_id', $company_departments])
                ->groupBy('case_pb_id, event_pb_year')
                ->all();

            foreach ($count_event_pb as $event_pb) {
                // проверяем наличие везде годов и если нет то создаем
                if (!isset($result['dynamic_inquiry']['crash'][$event_pb['event_pb_year']])) {
                    $result['dynamic_inquiry']['crash'][$event_pb['event_pb_year']]['case_pb_title'] = 'Авария';
                    $result['dynamic_inquiry']['crash'][$event_pb['event_pb_year']]['event_pb_year'] = (int)$event_pb['event_pb_year'];
                    $result['dynamic_inquiry']['crash'][$event_pb['event_pb_year']]['sum_event_pb_value'] = 0;
                }
                if (!isset($result['dynamic_inquiry']['miscellaneous'][$event_pb['event_pb_year']])) {
                    $result['dynamic_inquiry']['miscellaneous'][$event_pb['event_pb_year']]['case_pb_title'] = 'Несчастные случай';
                    $result['dynamic_inquiry']['miscellaneous'][$event_pb['event_pb_year']]['event_pb_year'] = (int)$event_pb['event_pb_year'];
                    $result['dynamic_inquiry']['miscellaneous'][$event_pb['event_pb_year']]['sum_event_pb_value'] = 0;
                }
                if (!isset($result['dynamic_inquiry']['incident'][$event_pb['event_pb_year']])) {
                    $result['dynamic_inquiry']['incident'][$event_pb['event_pb_year']]['case_pb_title'] = 'Инцидент';
                    $result['dynamic_inquiry']['incident'][$event_pb['event_pb_year']]['event_pb_year'] = (int)$event_pb['event_pb_year'];
                    $result['dynamic_inquiry']['incident'][$event_pb['event_pb_year']]['sum_event_pb_value'] = 0;
                }
                if (!isset($result['dynamic_inquiry']['other'][$event_pb['event_pb_year']])) {
                    $result['dynamic_inquiry']['other'][$event_pb['event_pb_year']]['case_pb_title'] = 'Прочие происшествия';
                    $result['dynamic_inquiry']['other'][$event_pb['event_pb_year']]['event_pb_year'] = (int)$event_pb['event_pb_year'];
                    $result['dynamic_inquiry']['other'][$event_pb['event_pb_year']]['sum_event_pb_value'] = 0;
                }

                switch ($event_pb['case_pb_id']) {
                    case 1:
                    {
                        $result['dynamic_inquiry']['crash'][$event_pb['event_pb_year']]['sum_event_pb_value'] += (int)$event_pb['sum_event_pb_value'];
                        break;
                    }
                    case 2:
                    {
                        $result['dynamic_inquiry']['miscellaneous'][$event_pb['event_pb_year']]['sum_event_pb_value'] += (int)$event_pb['sum_event_pb_value'];
                        break;
                    }
                    case 3:
                    {
                        $result['dynamic_inquiry']['incident'][$event_pb['event_pb_year']]['sum_event_pb_value'] += (int)$event_pb['sum_event_pb_value'];
                        break;
                    }
                    default:
                        $result['dynamic_inquiry']['other'][$event_pb['event_pb_year']]['sum_event_pb_value'] += (int)$event_pb['sum_event_pb_value'];
                        break;
                }

            }


            unset($event_pb);
            unset($count_event_pb);

            /** Метод окончание */


        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                   // описание текущей отладочной точки
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

        // запись в БД начала выполнения скрипта
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                         // время окончания выполнения метода
        LogAmicum::LogAmicumStatistic($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);


        return $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }


    // GetSPT - метод получения статистики наличия и сосояния средств пожаротушения
    // входные параметры:
    //      year                            - год за который строим статистику
    //      month                           - месяц за который строим статистику
    //      company_department_id           - подразделение с учетом вложений для которого строим статистику
    //      period                          - период за который строим статистику год/месяц 'month/year'
    // выходные параметры:
    //      count_spt_all                   - количество СПТ всех на предприятии
    //      count_spt_in_surface            - количество СПТ на поверхности
    //      count_spt_in_mine               - количество СПТ под землей в шахте
    //      count_spt_need_change           - количество СПТ подлежащих списанию или ТО
    //      statistic_spt:                  - статистика укомплектованности СПТ участком
    //          [company_department_id]         - ключ участка/подразделения/департамента
    //              company_department_id           - ключ участка/подразделения/департамента
    //              department_title                - название департамента
    //              spts:                           - список средств СПТ
    //                  [spt_id]                        - ключ спт
    //                      spt_id                          - ключ СПТ
    //                      spt_title                       - наименование СПТ
    //                      count_spt_taken                 - количество выданных СПТ
    //                      count_spt_status_normal         - количество СПТ с нормальным сроком годности
    //                      count_spt_status_yellow         - количество СПТ с предсроком сроком годности
    //                      count_spt_status_expired        - количество СПТ с просроченным сроком годности

    // алгоритм:
    // 1. обработка входных данных с фронта - если выбран период год, то ищем работающих людей до конца года, если выбран период месяц, то ищем людей работающих до конца этого месяца
    // если период год, то выбираем данные только за этот год, если период месяц, то выбираем данные только за этот месяц
    // 2. получение списка вложенных департаментов
    // 3. получаем статистику средств пожарной безопасности с учетом типа места (подземлей на поверхности)
    // 4. формируем выходной массив средств пожарной безопасности на предприятии, подземлей, на поверхности
    // 5. считаем статистику средств пожарной безопасности подлежащих замене или ТО
    // 6. считаем укомлпектованность участков средствами пожаротушения
    // пример:
    // http://127.0.0.1/read-manager-amicum?controller=statistic\Statistic&method=GetSPT&subscribe=&data={%22company_department_id%22:4029720,%22year%22:%222020%22,%22month%22:%2201%22,%22period%22:%22month%22}
    // http://127.0.0.1/read-manager-amicum?controller=statistic\Statistic&method=GetSPT&subscribe=&data={%22company_department_id%22:4029720,%22year%22:%222020%22,%22month%22:%2202%22,%22period%22:%22year%22}
    public static function GetSPT($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'GetSPT';                                                                                   // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = null;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта
        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        try {
            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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

            // запись в БД начала выполнения скрипта
            $response = LogAmicum::LogAmicumStatistic($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                           // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            /** Метод начало */

            // обработка входных параметров от фронта
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'company_department_id') ||                                       // ключ департамента
                !property_exists($post_dec, 'year') ||                                                         // год
                !property_exists($post_dec, 'month') ||                                                        // месяц
                !property_exists($post_dec, 'period'))                                                         // период 'month/year'
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $period = $post_dec->period;                                                                                // период за который строится статистика
            $year = $post_dec->year;                                                                                    // год за который строится статистика
            $month = $post_dec->month;                                                                                  // месяц за который строится статистика
            if ($period === 'month') {
                $count_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);                                  // количество дней в месяце
                $date = date('Y-m-d', strtotime($year . '-' . $month . '-' . $count_day));                 // период за месяц до конца месяца
//                $filterMonth = "MONTH(occupational_illness.date_act)='" . $month . "'";                                 // задаем фильтрацию по месяцу
            } elseif ($period === 'year') {
                $date = date('Y-m-d', strtotime($year . '-12-31'));                                        // период за год до конца года
//                $filterMonth = null;                                                                                    // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
                $month = null;                                                                                    // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
            } else {
                throw new Exception($method_name . '. Некорректный период: ' . $period);
            }
//            $warnings[] = $date;

            $company_department_id = $post_dec->company_department_id;                                                  // подразделение по которому нужно получить статистику


            /** Отладка */
            $description = 'Обработал входные данные с фронта';                                                                      // описание текущей отладочной точки
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

            // ищем вложенные подразделения
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception($method_name . '. Ошибка получения вложенных департаментов' . $company_department_id);
            }
            /** Отладка */
            $description = 'Получил список вложенных департаментов';                                                                      // описание текущей отладочной точки
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


            // получаем статистику средств пожарной безопасности с учетом типа места (подземлей на поверхности)
            $count_spt = (new Query())
                ->select('object_type.kind_object_id as kind_object_id, count(fire_fighting_object.id) as sum_spt_value')
                ->from('fire_fighting_equipment_specific')
                ->innerJoin('fire_fighting_object', 'fire_fighting_equipment_specific.fire_fighting_object_id=fire_fighting_object.id')
                ->innerJoin('place', 'place.id=fire_fighting_object.place_id')
                ->innerJoin('object', 'place.object_id=object.id')
                ->innerJoin('object_type', 'object.object_type_id=object_type.id')
                ->where(['in', 'fire_fighting_object.company_department_id', $company_departments])
//                ->andWhere('status_id!=66')
                ->andWhere(['<=', 'fire_fighting_equipment_specific.date_issue', $date])
                ->andWhere(['>', 'fire_fighting_equipment_specific.date_write_off', $date])
//                ->andWhere("YEAR(fire_fighting_equipment_specific.date_issue)='" . $year . "'")
//                ->andFilterWhere(['MONTH(fire_fighting_equipment_specific.date_issue)' => $month])
                ->groupBy('kind_object_id')
                ->indexBy('kind_object_id')
                ->all();

            // СПТ  в шахте (2 - горная среда)
            $result['count_spt_in_mine'] = 0;
            if (isset($count_spt['2'])) {
                $result['count_spt_in_mine'] = (int)$count_spt['2']['sum_spt_value'];
            }

            // СПТ  в шахте (6 - месторождение - поверхность)
            $result['count_spt_in_surface'] = 0;
            if (isset($count_spt['6'])) {
                $result['count_spt_in_surface'] = (int)$count_spt['6']['sum_spt_value'];
            }

            // рассчитываем полное количество СПТ
            $result['count_spt_all'] = $result['count_spt_in_surface'] + $result['count_spt_in_mine'];
            unset($count_spt);

            /** Отладка */
            $description = 'Статистика средств пожарной безопасности с учетом типа места (подземлей на поверхности)';                                                                      // описание текущей отладочной точки
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

            // получаем статистику по СПТ подлежащим замене или ТО
            $count_spt = (new Query())
                ->select('count(fire_fighting_equipment_specific.id) as sum_spt_value')
                ->from('fire_fighting_equipment_specific')
                ->innerJoin('fire_fighting_object', 'fire_fighting_equipment_specific.fire_fighting_object_id=fire_fighting_object.id')
                ->where(['in', 'fire_fighting_object.company_department_id', $company_departments])
                ->andWhere('status_id!=66')
                ->andWhere(['<=', 'fire_fighting_equipment_specific.date_issue', $date])
                ->andWhere(['<=', 'fire_fighting_equipment_specific.date_write_off', $date])
                ->scalar();

            $result['count_spt_need_change'] = (int)$count_spt;
            unset($count_spt);

            /** Отладка */
            $description = 'Статистика по СПТ подлежащим замене или ТО';                                                                      // описание текущей отладочной точки
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

            // получаем Укомплектованность участков средствами пожарной безопасности
            //      statistic_spt:                  - статистика укомплектованности СПТ участком
            //          [company_department_id]         - ключ участка/подразделения/департамента
            //              company_department_id           - ключ участка/подразделения/департамента
            //              department_title                - название департамента
            //              spts:                           - список средств СПТ
            //                  [spt_id]                        - ключ спт
            //                      spt_id                          - ключ СПТ
            //                      spt_title                       - наименование СПТ
            //                      count_spt_taken                 - количество выданных СПТ
            //                      count_spt_status_normal         - количество СПТ с нормальным сроком годности
            //                      count_spt_status_yellow         - количество СПТ с предсроком сроком годности
            //                      count_spt_status_expired        - количество СПТ с просроченным сроком годности
            $count_spt = (new Query())
                ->select('
                fire_fighting_equipment_id as spt_id,
                fire_fighting_equipment.title as spt_title,
                fire_fighting_object.company_department_id as company_department_id,
                company.title as department_title,
                fire_fighting_equipment_specific.date_issue as date_issue,
                fire_fighting_equipment_specific.date_write_off as date_write_off
                ')
                ->from('fire_fighting_equipment_specific')
                ->innerJoin('fire_fighting_object', 'fire_fighting_equipment_specific.fire_fighting_object_id=fire_fighting_object.id')
                ->innerJoin('company_department', 'company_department.id=fire_fighting_object.company_department_id')
                ->innerJoin('company', 'company.id=company_department.company_id')
                ->innerJoin('fire_fighting_equipment', 'fire_fighting_object.fire_fighting_equipment_id=fire_fighting_equipment.id')
                ->where(['in', 'fire_fighting_object.company_department_id', $company_departments])
                ->andWhere(['or',
                        ['and',
                            'month(fire_fighting_equipment_specific.date_issue)<=' . (int)date("m", strtotime($date)),
                            'year(fire_fighting_equipment_specific.date_issue)<=' . (int)date("Y", strtotime($date)),
                            'month(fire_fighting_equipment_specific.date_write_off)>=' . (int)date("m", strtotime($date)),
                            'year(fire_fighting_equipment_specific.date_write_off)>=' . (int)date("Y", strtotime($date))
                        ],
                        [
                            'and', 'fire_fighting_equipment_specific.status_id in (64, 65)',
                            "fire_fighting_equipment_specific.date_issue<='" . $date . "'"
                        ]
                    ]
                )
                ->all();

            foreach ($count_spt as $spt) {
                if (!isset($statistic_spt[$spt['company_department_id']])) {
                    $statistic_spt[$spt['company_department_id']]['company_department_id'] = $spt['company_department_id'];
                    $statistic_spt[$spt['company_department_id']]['department_title'] = $spt['department_title'];
                    $statistic_spt[$spt['company_department_id']]['spts'] = array();
                }
                if (!isset($statistic_spt[$spt['company_department_id']]['spts'][$spt['spt_id']])) {
                    $statistic_spt[$spt['company_department_id']]['spts'][$spt['spt_id']]['spt_id'] = $spt['spt_id'];
                    $statistic_spt[$spt['company_department_id']]['spts'][$spt['spt_id']]['spt_title'] = $spt['spt_title'];
                    $statistic_spt[$spt['company_department_id']]['spts'][$spt['spt_id']]['count_spt_taken'] = 0;
                    $statistic_spt[$spt['company_department_id']]['spts'][$spt['spt_id']]['count_spt_status_normal'] = 0;
                    $statistic_spt[$spt['company_department_id']]['spts'][$spt['spt_id']]['count_spt_status_yellow'] = 0;
                    $statistic_spt[$spt['company_department_id']]['spts'][$spt['spt_id']]['count_spt_status_expired'] = 0;
                }

                if (strtotime($spt['date_write_off']) < strtotime($date)) {
                    $statistic_spt[$spt['company_department_id']]['spts'][$spt['spt_id']]['count_spt_status_expired']++;
                } else if (strtotime($spt['date_write_off']) < strtotime($date . ' +3 day')) {
                    $statistic_spt[$spt['company_department_id']]['spts'][$spt['spt_id']]['count_spt_status_yellow']++;
                } else {
                    $statistic_spt[$spt['company_department_id']]['spts'][$spt['spt_id']]['count_spt_status_normal']++;
                }
                $statistic_spt[$spt['company_department_id']]['spts'][$spt['spt_id']]['count_spt_taken'] = $statistic_spt[$spt['company_department_id']]['spts'][$spt['spt_id']]['count_spt_status_normal'] +
                    $statistic_spt[$spt['company_department_id']]['spts'][$spt['spt_id']]['count_spt_status_yellow'] +
                    $statistic_spt[$spt['company_department_id']]['spts'][$spt['spt_id']]['count_spt_status_expired'];
            }

            if (isset($statistic_spt)) {
                $result['statistic_spt'] = $statistic_spt;
            } else {
                $result['statistic_spt'] = array();
            }
            unset($statistic_spt);
            unset($count_spt);

            /** Отладка */
            $description = 'Статистика Укомплектованность участков средствами пожарной безопасности';                                                                      // описание текущей отладочной точки
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

            /** Метод окончание */


        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                   // описание текущей отладочной точки
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

        // запись в БД начала выполнения скрипта
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                         // время окончания выполнения метода
        LogAmicum::LogAmicumStatistic($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        return $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }

    // GetSIZ - метод получения статистики наличия и сосояния СИЗ
    // входные параметры:
    //      year                            - год за который строим статистику
    //      month                           - месяц за который строим статистику
    //      company_department_id           - подразделение с учетом вложений для которого строим статистику
    //      period                          - период за который строим статистику год/месяц 'month/year'
    // выходные параметры:
    //      count_siz_all                   - количество СИЗ всех на предприятии
    //      count_siz_need_change           - количество СИЗ подлежащих списанию/замене
    //      statistic_siz:                  - статистика укомплектованности СИЗ участком
    //                  [siz_id]                        - ключ СИЗ
    //                      siz_id                          - ключ СИЗ
    //                      siz_title                       - наименование СИЗ
    //                      count_siz_taken                 - количество выданных СИЗ
    //                      count_siz_status_normal         - количество СИЗ с нормальным сроком годности
    //                      count_siz_status_yellow         - количество СИЗ с предсроком сроком годности
    //                      count_siz_status_expired        - количество СИЗ с просроченным сроком годности

    // алгоритм:
    // 1. обработка входных данных с фронта - если выбран период год, то ищем работающих людей до конца года, если выбран период месяц, то ищем людей работающих до конца этого месяца
    // если период год, то выбираем данные только за этот год, если период месяц, то выбираем данные только за этот месяц
    // 2. получение списка вложенных департаментов
    // 3. получаем количество выданных СИЗ
    // 4. получаем количество сиз подлежащих замене
    // 5. считаем укомлпектованность участков средствами индивидуальной защиты
    // пример:
    // http://127.0.0.1/read-manager-amicum?controller=statistic\Statistic&method=GetSIZ&subscribe=&data={%22company_department_id%22:4029720,%22year%22:%222020%22,%22month%22:%2201%22,%22period%22:%22month%22}
    // http://127.0.0.1/read-manager-amicum?controller=statistic\Statistic&method=GetSIZ&subscribe=&data={%22company_department_id%22:4029720,%22year%22:%222020%22,%22month%22:%2202%22,%22period%22:%22year%22}
    public static function GetSIZ($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'GetSIZ';                                                                                   // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = null;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта
        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        try {
            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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

            // запись в БД начала выполнения скрипта
            $response = LogAmicum::LogAmicumStatistic($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                           // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            /** Метод начало */

            // обработка входных параметров от фронта
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'company_department_id') ||                                       // ключ департамента
                !property_exists($post_dec, 'year') ||                                                         // год
                !property_exists($post_dec, 'month') ||                                                        // месяц
                !property_exists($post_dec, 'period'))                                                         // период 'month/year'
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $period = $post_dec->period;                                                                                // период за который строится статистика
            $year = $post_dec->year;                                                                                    // год за который строится статистика
            $month = $post_dec->month;                                                                                  // месяц за который строится статистика
            if ($period === 'month') {
                $count_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);                                  // количество дней в месяце
                $date = date('Y-m-d', strtotime($year . '-' . $month . '-' . $count_day));                 // период за месяц до конца месяца
//                $filterMonth = "MONTH(occupational_illness.date_act)='" . $month . "'";                                 // задаем фильтрацию по месяцу
            } elseif ($period === 'year') {
                $date = date('Y-m-d', strtotime($year . '-12-31'));                                        // период за год до конца года
//                $filterMonth = null;                                                                                    // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
                $month = null;                                                                                    // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
            } else {
                throw new Exception($method_name . '. Некорректный период: ' . $period);
            }
//            $warnings[] = $date;

            $company_department_id = $post_dec->company_department_id;                                                  // подразделение по которому нужно получить статистику


            /** Отладка */
            $description = 'Обработал входные данные с фронта';                                                                      // описание текущей отладочной точки
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

            // ищем вложенные подразделения
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception($method_name . '. Ошибка получения вложенных департаментов' . $company_department_id);
            }
            /** Отладка */
            $description = 'Получил список вложенных департаментов';                                                                      // описание текущей отладочной точки
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


            // получаем количество СИЗ на участке
            $count_siz = (new Query())
                ->select('count(worker_siz.id) as sum_siz_value')
                ->from('worker_siz')
                ->innerJoin('worker', 'worker_siz.worker_id=worker.id')
                ->where(['in', 'worker.company_department_id', $company_departments])
                ->andWhere('status_id!=66')
                ->andWhere(['or',
                    ['and',
                        'worker_siz.date_issue<="' . $date . '"',
                        'worker_siz.date_write_off>="' . $date . '"'
                    ],
                    ['and',
                        'worker_siz.date_issue<="' . $date . '"',
                        'worker_siz.status_id in (64, 65)'
                    ]
                ])
                ->scalar();


            // рассчитываем полное количество СИЗ
            $result['count_siz_all'] = (int)$count_siz;
            unset($count_siz);

            /** Отладка */
            $description = 'Статистика СИЗ по всем';                                                                      // описание текущей отладочной точки
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

            // получаем статистику по СИЗ подлежащим замене или ТО
            $count_siz_need = (new Query())
                ->select('count(worker_siz.id) as sum_siz_value')
                ->from('worker_siz')
                ->innerJoin('siz', 'worker_siz.siz_id=siz.id')
                ->innerJoin('worker', 'worker_siz.worker_id=worker.id')
                ->where(['in', 'worker.company_department_id', $company_departments])
                ->andWhere('status_id!=66')
                ->andWhere('wear_period<36')
                ->andWhere('wear_period!=0')
                ->andWhere(['is not', 'wear_period', null])
                ->andWhere(['<=', 'worker_siz.date_issue', $date])
                ->andWhere(['<=', 'worker_siz.date_write_off', $date])
                ->scalar();

            $result['count_siz_need_change'] = (int)$count_siz_need;
            unset($count_siz_need);

            /** Отладка */
            $description = 'Статистика по СИЗ подлежащим замене ';                                                                      // описание текущей отладочной точки
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

            // получаем Укомплектованность участков СИЗ
            //      statistic_siz:                  - статистика укомплектованности СИЗ участком
            //                  [siz_id]                        - ключ СИЗ
            //                      siz_id                          - ключ СИЗ
            //                      siz_title                       - наименование СИЗ
            //                      count_siz_taken                 - количество выданных СИЗ
            //                      count_siz_status_normal         - количество СИЗ с нормальным сроком годности
            //                      count_siz_status_yellow         - количество СИЗ с предсроком сроком годности
            //                      count_siz_status_expired        - количество СИЗ с просроченным сроком годности
            $count_siz = (new Query())
                ->select('
                siz.id as siz_id,
                siz.title as siz_title,
                siz.wear_period as wear_period,
                worker_siz.date_issue as date_issue,
                worker_siz.date_write_off as date_write_off
                ')
                ->from('worker_siz')
                ->innerJoin('siz', 'worker_siz.siz_id=siz.id')
                ->innerJoin('worker', 'worker_siz.worker_id=worker.id')
                ->where(['in', 'worker.company_department_id', $company_departments])
                ->andWhere(['or',
                        ['and',
//                            'month(worker_siz.date_issue)<=' . (int)date("m", strtotime($date)),
//                            'year(worker_siz.date_issue)<=' . (int)date("Y", strtotime($date)),
//                            'month(worker_siz.date_write_off)>=' . (int)date("m", strtotime($date)),
//                            'year(worker_siz.date_write_off)>=' . (int)date("Y", strtotime($date)),
                            'worker_siz.date_issue<="' . $date . '"',
                            'worker_siz.date_write_off>="' . $date . '"'
                        ],
                        [
                            'and', 'worker_siz.status_id in (64, 65)',
                            "worker_siz.date_issue<='" . $date . "'"
                        ]
                    ]
                )
                ->all();

            foreach ($count_siz as $siz) {
                if (!isset($statistic_siz[$siz['siz_id']])) {
                    $statistic_siz[$siz['siz_id']]['siz_id'] = $siz['siz_id'];
                    $statistic_siz[$siz['siz_id']]['siz_title'] = $siz['siz_title'];
                    $statistic_siz[$siz['siz_id']]['count_siz_taken'] = 0;
                    $statistic_siz[$siz['siz_id']]['count_siz_status_normal'] = 0;
                    $statistic_siz[$siz['siz_id']]['count_siz_status_yellow'] = 0;
                    $statistic_siz[$siz['siz_id']]['count_siz_status_expired'] = 0;
                }

                if (strtotime($siz['date_write_off']) < strtotime($date) and $siz['wear_period'] != 36 and $siz['wear_period'] != 0) {
                    $statistic_siz[$siz['siz_id']]['count_siz_status_expired']++;
                } else if (strtotime($siz['date_write_off']) < strtotime($date . ' +3 day') and $siz['wear_period'] != 36 and $siz['wear_period'] != 0) {
                    $statistic_siz[$siz['siz_id']]['count_siz_status_yellow']++;
                } else {
                    $statistic_siz[$siz['siz_id']]['count_siz_status_normal']++;
                }
                $statistic_siz[$siz['siz_id']]['count_siz_taken'] = $statistic_siz[$siz['siz_id']]['count_siz_status_normal'] +
                    $statistic_siz[$siz['siz_id']]['count_siz_status_yellow'] +
                    $statistic_siz[$siz['siz_id']]['count_siz_status_expired'];
            }

            if (isset($statistic_siz)) {
                $result['statistic_siz'] = $statistic_siz;
            } else {
                $result['statistic_siz'] = array();
            }
            unset($statistic_siz);
            unset($count_siz);

            /** Отладка */
            $description = 'Статистика Укомплектованность участков СИЗ';                                                                      // описание текущей отладочной точки
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

            /** Метод окончание */


        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                   // описание текущей отладочной точки
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

        // запись в БД начала выполнения скрипта
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                         // время окончания выполнения метода
        LogAmicum::LogAmicumStatistic($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        return $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }


    // GetIndustrialSafetyExpertise - метод получения статистики по ЭПБ зданий сооружений, проектной документации, технических устройств
    // входные параметры:
    //      year                            - год за который строим статистику
    //      month                           - месяц за который строим статистику
    //      company_department_id           - подразделение с учетом вложений для которого строим статистику
    //      period                          - период за который строим статистику год/месяц 'month/year'
    // выходные параметры:
    //      count_equipment_need            - количество технических средств требующих проведение ЭПБ
    //      count_documentation_need        - количество проектной документации требующей проведение ЭПБ
    //      count_building_need             - количество зданий и сооружений требующих проведение ЭБП
    //      count_equipment                 - количество технических средств прошедших ЭПБ в заправшиваемый период
    //      count_documentation             - количество проектной документации прошедших ЭПБ в заправшиваемый период
    //      count_building                  - количество зданий и сооружений прошедших ЭПБ в заправшиваемый период
    // алгоритм:
    // 1. обработка входных данных с фронта - если выбран период год, то ищем работающих людей до конца года, если выбран период месяц, то ищем людей работающих до конца этого месяца
    // если период год, то выбираем данные только за этот год, если период месяц, то выбираем данные только за этот месяц
    // 2. получение списка вложенных департаментов
    // 3. получение данных по объектам требующим проведение ЭПБ
    // 4. создание выходного массива данных
    // 5. получение данных по объектам прошедшим эпб в запрашиваемый период
    // 6. создание выходного массива данных
    // пример:
    // http://127.0.0.1/read-manager-amicum?controller=statistic\Statistic&method=GetIndustrialSafetyExpertise&subscribe=&data={%22company_department_id%22:4029720,%22year%22:%222020%22,%22month%22:%2201%22,%22period%22:%22month%22}
    // http://127.0.0.1/read-manager-amicum?controller=statistic\Statistic&method=GetIndustrialSafetyExpertise&subscribe=&data={%22company_department_id%22:4029720,%22year%22:%222020%22,%22month%22:%2202%22,%22period%22:%22year%22}
    public static function GetIndustrialSafetyExpertise($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'GetIndustrialSafetyExpertise';                                                                                   // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = null;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта
        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        try {
            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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

            // запись в БД начала выполнения скрипта
            $response = LogAmicum::LogAmicumStatistic($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                           // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            /** Метод начало */

            // обработка входных параметров от фронта
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'company_department_id') ||                                       // ключ департамента
                !property_exists($post_dec, 'year') ||                                                         // год
                !property_exists($post_dec, 'month') ||                                                        // месяц
                !property_exists($post_dec, 'period'))                                                         // период 'month/year'
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $period = $post_dec->period;                                                                                // период за который строится статистика
            $year = $post_dec->year;                                                                                    // год за который строится статистика
            $month = $post_dec->month;                                                                                  // месяц за который строится статистика
            if ($period === 'month') {
                $count_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);                                  // количество дней в месяце
                $date = date('Y-m-d', strtotime($year . '-' . $month . '-' . $count_day));                 // период за месяц до конца месяца
//                $filterMonth = "MONTH(occupational_illness.date_act)='" . $month . "'";                                 // задаем фильтрацию по месяцу
            } elseif ($period === 'year') {
                $date = date('Y-m-d', strtotime($year . '-12-31'));                                        // период за год до конца года
//                $filterMonth = null;                                                                                    // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
                $month = null;                                                                                    // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
            } else {
                throw new Exception($method_name . '. Некорректный период: ' . $period);
            }
//            $warnings[] = $date;

            $company_department_id = $post_dec->company_department_id;                                                  // подразделение по которому нужно получить статистику


            /** Отладка */
            $description = 'Обработал входные данные с фронта';                                                                      // описание текущей отладочной точки
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

            // ищем вложенные подразделения
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception($method_name . '. Ошибка получения вложенных департаментов' . $company_department_id);
            }
            /** Отладка */
            $description = 'Получил список вложенных департаментов';                                                                      // описание текущей отладочной точки
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


            // получаем ЭПБ просрочки
            $count_expertise = (new Query())
                ->select('industrial_safety_object.industrial_safety_object_type_id, count(expertise.id) as sum_expertise_value')
                ->from('expertise')
                ->innerJoin('industrial_safety_object', 'industrial_safety_object.id=expertise.industrial_safety_object_id')
                ->where(['in', 'expertise.company_department_id', $company_departments])
                ->andWhere([
                    'or',
                    ['<=', 'expertise.date_last_expertise', $date],
                    ['is', 'expertise.date_last_expertise', null],
                ])
                ->andWhere([
                    'or',
                    ['<=', 'expertise.date_next_expertise', $date],
                    ['is', 'expertise.date_next_expertise', null],
                ])
//                ->andWhere("YEAR(worker_siz.date_issue)='" . $year . "'")
//                ->andFilterWhere(['MONTH(worker_siz.date_issue)' => $month])
                ->groupBy('industrial_safety_object_type_id')
                ->indexBy('industrial_safety_object_type_id')
                ->all();

            // технические устройства, нуждающиеся в ЭПБ
            if (isset($count_expertise['1'])) {
                $result['count_equipment_need'] = (int)$count_expertise['1']['sum_expertise_value'];
            } else {
                $result['count_equipment_need'] = 0;
            }

            // здания и сооружения, нуждающиеся в ЭПБ
            if (isset($count_expertise['2'])) {
                $result['count_building_need'] = (int)$count_expertise['2']['sum_expertise_value'];
            } else {
                $result['count_building_need'] = 0;
            }

            // проектная документация, нуждающиеся в ЭПБ
            if (isset($count_expertise['3'])) {
                $result['count_documentation_need'] = (int)$count_expertise['3']['sum_expertise_value'];
            } else {
                $result['count_documentation_need'] = 0;
            }

            /** Отладка */
            $description = 'Статистика ЭПБ которые еще требуются';                                                                      // описание текущей отладочной точки
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

            // объекты прошедшие экспертизу в запрашиваемом году
            $count_expertise = (new Query())
                ->select('industrial_safety_object.industrial_safety_object_type_id, count(expertise.id) as sum_expertise_value')
                ->from('expertise')
                ->innerJoin('industrial_safety_object', 'industrial_safety_object.id=expertise.industrial_safety_object_id')
                ->where(['in', 'expertise.company_department_id', $company_departments])
                ->andWhere([
                    'or',
                    ['<=', 'expertise.date_last_expertise', $date],
                    ['is not', 'expertise.date_last_expertise', null],
                ])
                ->andWhere("YEAR(expertise.date_last_expertise)='" . $year . "'")
                ->andFilterWhere(['MONTH(expertise.date_last_expertise)' => $month])
                ->groupBy('industrial_safety_object_type_id')
                ->indexBy('industrial_safety_object_type_id')
                ->all();

            // технические устройства, прошедшие ЭПБ
            if (isset($count_expertise['1'])) {
                $result['count_equipment'] = (int)$count_expertise['1']['sum_expertise_value'];
            } else {
                $result['count_equipment'] = 0;
            }

            // здания и сооружения, прошедшие ЭПБ
            if (isset($count_expertise['2'])) {
                $result['count_building'] = (int)$count_expertise['2']['sum_expertise_value'];
            } else {
                $result['count_building'] = 0;
            }

            // проектная документация, прошедшие ЭПБ
            if (isset($count_expertise['3'])) {
                $result['count_documentation'] = (int)$count_expertise['3']['sum_expertise_value'];
            } else {
                $result['count_documentation'] = 0;
            }

            unset($count_expertise);

            /** Отладка */
            $description = 'Статистика ЭПБ по всем';                                                                      // описание текущей отладочной точки
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

            /** Метод окончание */


        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                   // описание текущей отладочной точки
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

        // запись в БД начала выполнения скрипта
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                         // время окончания выполнения метода
        LogAmicum::LogAmicumStatistic($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        return $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }


// GetIndustrialStatistic - метод получения статистики по производству
    // входные параметры:
    //      year                            - год за который строим статистику
    //      month                           - месяц за который строим статистику
    //      company_department_id           - подразделение с учетом вложений для которого строим статистику
    //      period                          - период за который строим статистику год/месяц 'month/year'
    //      brigade_id                      - ключ бригады
    //      chane_id                        - ключ звена
    // выходные параметры:
    //      percent                         - Объем выполненных работ (план, факт), %
    //      summary_count_people            - выхождаемость, чел
    //      summary_production_by_people    - производительность на человека, показательль/чел
    //      summary_stop_by_agk             - остановки по АГК, шт
    //      summary_stop_by_rtn             - остановки по РТН, шт
    //      inquire:                        - производственный травматизм
    //      stabile_brigade:                - стабильность бригад в шт.
    // ПОЛНАЯ СТРУКТУРА
    //      statistic:
    //           [brigade_id]                                                                                           // ключ бригады
    //              [chane_id]                                                                                          // ключ звена
    //                  [order_month]                                                                                   // разбивка по месяцам
    //                      month:                                                                                // номер месяца
    //                      summary_operation_plan:                                                                     // план на месяц
    //                      summary_operation_fact:                                                                     // факт на месяц
    //                      summary_count_people:                                                                       // факт выхождаемости в месяц
    //                      summary_production_by_people                                                                // производительность на человека
    //                      summary_stop_by_agk                                                                         // остановки по АГК
    //                      summary_stop_by_rtn                                                                         // остановки по РТН
    //                      percent:                                                                                    // процент выполнения плана на месяц
    //                      days:                                                                                       // разбивка по дням
    //                          [order_day]                                                                             // день
    //                              day                                                                           // номер дня
    //                              summary_operation_plan                                                              // план на день
    //                              summary_operation_fact                                                              // факт на день
    //                              summary_count_people:                                                               // факт выхождаемости в день
    //                              summary_production_by_people                                                        // производительность на человека
    //                              summary_stop_by_agk                                                                 // остановки по АГК
    //                              summary_stop_by_rtn                                                                 // остановки по РТН
    //                              percent                                                                             // процент выполнения плана на день
    //      inquire:                    - производственный травматизм
    //           [brigade_id]                                                                                           // ключ бригады
    //              brigade_id                                                                                          // ключ бригады
    //              count_event_pb                                                                                      // количество случаев травматизма
    //              chanes                                                                                              // список звеньев
    //                  [chane_id]                                                                                      // ключ звена
    //                      chane_id                                                                                    // ключ звена
    //                      count_event_pb                                                                              // количество случаев травматизма
    //      stabile_brigade:
    //          [month]
    //              month:                                                                                              - номер месяца
    //              brigades:
    //                  brigade_id                                                                                      - ключ бригады
    //                  chanes:
    //                      chane_id:                                                                                   - ключ звена
    //                      group_count:                                                                                - количество видов звеньев
    // алгоритм:
    // 1. обработка входных данных с фронта - если выбран период год, то ищем работающих людей до конца года, если выбран период месяц, то ищем людей работающих до конца этого месяца
    // если период год, то выбираем данные только за этот год, если период месяц, то выбираем данные только за этот месяц
    // 2.
    // 3.
    // 4. создание выходного массива данных
    // 5. получение данных по объектам прошедшим эпб в запрашиваемый период
    // 6. создание выходного массива данных
    // пример:
    // http://127.0.0.1/read-manager-amicum?controller=statistic\Statistic&method=GetIndustrialStatistic&subscribe=&data={%22company_department_id%22:4029938,%22year%22:%222019%22,%22month%22:%2212%22,%22period%22:%22month%22,%22brigade_id%22:null,%22chane_id%22:null}
    // 127.0.0.1/read-manager-amicum?controller=statistic\Statistic&method=GetIndustrialStatistic&subscribe=&data={"company_department_id":4029938,"year":"2019","month":"12","period":"year","brigade_id":null,"chane_id_id":null}
    public static function GetIndustrialStatistic($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'GetIndustrialStatistic';                                                                                   // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = null;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта
        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        /**
         * объявил 2 массива, чтобы не было ошибок, что эти переменные не объявлены
         */
        $group_peoples = array();
        $update_people = array();
        try {
            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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

            // запись в БД начала выполнения скрипта
            $response = LogAmicum::LogAmicumStatistic($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                           // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            /** Метод начало */

            // обработка входных параметров от фронта
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'company_department_id') ||                                       // ключ департамента
                !property_exists($post_dec, 'year') ||                                                         // год
                !property_exists($post_dec, 'month') ||                                                        // месяц
                !property_exists($post_dec, 'brigade_id') ||                                                   // ключ бригады
                !property_exists($post_dec, 'chane_id') ||                                                     // ключ звена
                !property_exists($post_dec, 'period'))                                                         // период 'month/year'
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $period = $post_dec->period;                                                                                // период за который строится статистика
            $year = $post_dec->year;                                                                                    // год за который строится статистика
            $month = $post_dec->month;                                                                                  // месяц за который строится статистика
            $brigade_id = $post_dec->brigade_id;                                                                        // ключ бригады
            $chane_id = $post_dec->chane_id;                                                                            // ключ звена
            if ($period === 'month') {
//                $count_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);                                  // количество дней в месяце
//                $date = date('Y-m-d', strtotime($year . '-' . $month . '-' . $count_day));                 // период за месяц до конца месяца
//                $filterMonth = "MONTH(occupational_illness.date_act)='" . $month . "'";                                 // задаем фильтрацию по месяцу
            } elseif ($period === 'year') {
//                $date = date('Y-m-d', strtotime($year . '-12-31'));                                        // период за год до конца года
//                $filterMonth = null;                                                                                    // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
                $month = null;                                                                                          // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
            } else {
                throw new Exception($method_name . '. Некорректный период: ' . $period);
            }
//            $warnings[] = $date;

            $company_department_id = $post_dec->company_department_id;                                                  // подразделение по которому нужно получить статистику


            /** Отладка */
            $description = 'Обработал входные данные с фронта';                                                                      // описание текущей отладочной точки
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
            // считаем производительность тонн(очистники)/метров(подготовители)/простоев(УКТ) на человек по месяцу/году
            // получаем основной показатель участка
            $response = ReportForPreviousPeriodController::GetDepartmentMainOperation($company_department_id);
            if ($response['status'] === 1) {
                $main_operation_id = $response['operation_id'];
            } else {
                throw new Exception($method_name . '. Не смог получить главную операцию участка');
            }
            $warnings[] = $method_name . 'Главная операция участка = ' . $main_operation_id;


            // получаем количество по искомому департаменту главного показателя
            // (группируем выборку по году, месяцу, дню, смене, бригаде, звену, )
            $report_by_main_indicator = (new Query())
                ->select('
                    YEAR(order.date_time_create) as order_year, 
                    MONTH(order.date_time_create) as order_month,
                    DAY(order.date_time_create) as order_day,
                    order.shift_id as shift_id,
                    operation_worker.brigade_id as brigade_id,
                    operation_worker.chane_id as chane_id,
                    order_operation.operation_value_plan as value_plan,
                    order_operation.operation_value_fact as value_fact
                    ')
                ->from('operation_worker')
                ->innerJoin('order_operation', 'order_operation.id=operation_worker.order_operation_id')
                ->innerJoin('order_place', 'order_place.id=order_operation.order_place_id')
                ->innerJoin('order', 'order.id=order_place.order_id')
                ->where(['order.company_department_id' => $company_department_id])
                ->andWhere(['order_operation.operation_id' => $main_operation_id])
                ->andWhere("YEAR(order.date_time_create)='" . $year . "'")
                ->andFilterWhere(['MONTH(order.date_time_create)' => $month])
                ->andFilterWhere(['brigade_id' => $brigade_id])
                ->andFilterWhere(['chane_id' => $chane_id])
                ->groupBy('order_year, order_month, order_day, brigade_id, chane_id, value_plan, value_fact, shift_id')
                ->all();
//            $warnings[] = $report_by_main_indicator;

            $percent_done_operation = array();                                                                          // объем выполненных работ по суткам
            // [brigade_id]                                                                                             // ключ бригады
            //      [chane_id]                                                                                          // ключ звена
            //          [order_month]                                                                                   // разбивка по месяцам
            //              month:                                                                                // номер месяца
            //              summary_operation_plan:                                                                     // план на месяц
            //              summary_operation_fact:                                                                     // факт на месяц
            //              summary_count_people:                                                                       // факт выхождаемости в месяц
            //              summary_production_by_people                                                                // производительность на человека
            //              percent:                                                                                    // процент выполнения плана на месяц
            //              days:                                                                                       // разбивка по дням
            //                  [order_day]                                                                             // день
            //                      day                                                                           // номер дня
            //                      summary_operation_plan                                                              // план на день
            //                      summary_operation_fact                                                              // факт на день
            //                      summary_count_people:                                                               // факт выхождаемости в день
            //                      summary_production_by_people                                                        // производительность на человека
            //                      percent                                                                             // процент выполнения плана на день
            foreach ($report_by_main_indicator as $indic) {
                if (!isset($percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']])) {
                    for ($i = 1; $i <= 12; $i++) {
                        $percent_done_operation[$indic['brigade_id']]['brigade_id'] = $indic['brigade_id'];

                        if (!isset($percent_done_operation[$indic['brigade_id']]['months'][$i])) {
                            $percent_done_operation[$indic['brigade_id']]['months'][$i]['month'] = $i;
                            $percent_done_operation[$indic['brigade_id']]['months'][$i]['summary_operation_plan'] = 0;
                            $percent_done_operation[$indic['brigade_id']]['months'][$i]['summary_operation_fact'] = 0;
                            $percent_done_operation[$indic['brigade_id']]['months'][$i]['summary_count_people'] = 0;
                            $percent_done_operation[$indic['brigade_id']]['months'][$i]['summary_production_by_people'] = 0;
                            $percent_done_operation[$indic['brigade_id']]['months'][$i]['summary_stop_by_rtn'] = 0;
                            $percent_done_operation[$indic['brigade_id']]['months'][$i]['summary_stop_by_agk'] = 0;
                            $percent_done_operation[$indic['brigade_id']]['months'][$i]['percent'] = 0;
                        }

                        $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['chane_id'] = $indic['chane_id'];

                        $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$i]['month'] = $i;
                        $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$i]['summary_operation_plan'] = 0;
                        $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$i]['summary_operation_fact'] = 0;
                        $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$i]['summary_count_people'] = 0;
                        $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$i]['summary_production_by_people'] = 0;
                        $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$i]['summary_stop_by_rtn'] = 0;
                        $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$i]['summary_stop_by_agk'] = 0;
                        $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$i]['percent'] = 0;

                        for ($j = 1; $j <= cal_days_in_month(CAL_GREGORIAN, $i, $year); $j++) {

                            if (!isset($percent_done_operation[$indic['brigade_id']]['months'][$i]['days'][$j])) {
                                $percent_done_operation[$indic['brigade_id']]['months'][$i]['days'][$j]['day'] = $j;
                                $percent_done_operation[$indic['brigade_id']]['months'][$i]['days'][$j]['summary_operation_plan'] = 0;
                                $percent_done_operation[$indic['brigade_id']]['months'][$i]['days'][$j]['summary_operation_fact'] = 0;
                                $percent_done_operation[$indic['brigade_id']]['months'][$i]['days'][$j]['summary_count_people'] = 0;
                                $percent_done_operation[$indic['brigade_id']]['months'][$i]['days'][$j]['summary_production_by_people'] = 0;
                                $percent_done_operation[$indic['brigade_id']]['months'][$i]['days'][$j]['summary_stop_by_agk'] = 0;
                                $percent_done_operation[$indic['brigade_id']]['months'][$i]['days'][$j]['summary_stop_by_rtn'] = 0;
                                $percent_done_operation[$indic['brigade_id']]['months'][$i]['days'][$j]['percent'] = 0;
                            }

                            $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$i]['days'][$j]['day'] = $j;
                            $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$i]['days'][$j]['summary_operation_plan'] = 0;
                            $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$i]['days'][$j]['summary_operation_fact'] = 0;
                            $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$i]['days'][$j]['summary_count_people'] = 0;
                            $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$i]['days'][$j]['summary_production_by_people'] = 0;
                            $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$i]['days'][$j]['summary_stop_by_agk'] = 0;
                            $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$i]['days'][$j]['summary_stop_by_rtn'] = 0;
                            $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$i]['days'][$j]['percent'] = 0;

                        }
                    }
                }

                //считаем суммарный показатель за месяц план/факт
                $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$indic['order_month']]['summary_operation_plan'] += (int)$indic['value_plan'];
                $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$indic['order_month']]['summary_operation_fact'] += (int)$indic['value_fact'];

                // считаем суммарный показатель за день план/факт
                $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$indic['order_month']]['days'][$indic['order_day']]['summary_operation_plan'] += (int)$indic['value_plan'];
                $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$indic['order_month']]['days'][$indic['order_day']]['summary_operation_fact'] += (int)$indic['value_fact'];

                // процент выпонения плана на день
                if ($percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$indic['order_month']]['days'][$indic['order_day']]['summary_operation_plan'] != 0) {
                    $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$indic['order_month']]['days'][$indic['order_day']]['percent']
                        =
                        round(($percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$indic['order_month']]['days'][$indic['order_day']]['summary_operation_fact']
                                /
                                $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$indic['order_month']]['days'][$indic['order_day']]['summary_operation_plan']) * 100, 1);
                } else {
                    $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$indic['order_month']]['days'][$indic['order_day']]['percent'] = 100;
                }

                // процент выпонения плана на месяц
                if ($percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$indic['order_month']]['summary_operation_plan'] != 0) {
                    $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$indic['order_month']]['percent']
                        =
                        round(($percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$indic['order_month']]['summary_operation_fact']
                                /
                                $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$indic['order_month']]['summary_operation_plan']) * 100, 1);
                } else {
                    $percent_done_operation[$indic['brigade_id']]['chanes'][$indic['chane_id']]['months'][$indic['order_month']]['percent'] = 100;
                }


                //считаем суммарный показатель за месяц план/факт
                $percent_done_operation[$indic['brigade_id']]['months'][$indic['order_month']]['summary_operation_plan'] += (int)$indic['value_plan'];
                $percent_done_operation[$indic['brigade_id']]['months'][$indic['order_month']]['summary_operation_fact'] += (int)$indic['value_fact'];

                // считаем суммарный показатель за день план/факт
                $percent_done_operation[$indic['brigade_id']]['months'][$indic['order_month']]['days'][$indic['order_day']]['summary_operation_plan'] += (int)$indic['value_plan'];
                $percent_done_operation[$indic['brigade_id']]['months'][$indic['order_month']]['days'][$indic['order_day']]['summary_operation_fact'] += (int)$indic['value_fact'];

                // процент выпонения плана на день
                if ($percent_done_operation[$indic['brigade_id']]['months'][$indic['order_month']]['days'][$indic['order_day']]['summary_operation_plan'] != 0) {
                    $percent_done_operation[$indic['brigade_id']]['months'][$indic['order_month']]['days'][$indic['order_day']]['percent']
                        =
                        round(($percent_done_operation[$indic['brigade_id']]['months'][$indic['order_month']]['days'][$indic['order_day']]['summary_operation_fact']
                                /
                                $percent_done_operation[$indic['brigade_id']]['months'][$indic['order_month']]['days'][$indic['order_day']]['summary_operation_plan']) * 100, 1);
                } else {
                    $percent_done_operation[$indic['brigade_id']]['months'][$indic['order_month']]['days'][$indic['order_day']]['percent'] = 100;
                }

                // процент выпонения плана на месяц
                if ($percent_done_operation[$indic['brigade_id']]['months'][$indic['order_month']]['summary_operation_plan'] != 0) {
                    $percent_done_operation[$indic['brigade_id']]['months'][$indic['order_month']]['percent']
                        =
                        round(($percent_done_operation[$indic['brigade_id']]['months'][$indic['order_month']]['summary_operation_fact']
                                /
                                $percent_done_operation[$indic['brigade_id']]['months'][$indic['order_month']]['summary_operation_plan']) * 100, 1);
                } else {
                    $percent_done_operation[$indic['brigade_id']]['months'][$indic['order_month']]['percent'] = 100;
                }

            }

            // выхождаемость
            // (группируем выборку по году, месяцу, дню, смене, бригаде, звену, )
            $report_by_count_people = (new Query())
                ->select('
                    YEAR(order.date_time_create) as order_year, 
                    MONTH(order.date_time_create) as order_month,
                    DAY(order.date_time_create) as order_day,
                    order.shift_id as shift_id,
                    operation_worker.brigade_id as brigade_id,
                    operation_worker.chane_id as chane_id,
                    operation_worker.worker_id as worker_id,

                    ')
                ->from('operation_worker')
                ->innerJoin('order_operation', 'order_operation.id=operation_worker.order_operation_id')
                ->innerJoin('order_place', 'order_place.id=order_operation.order_place_id')
                ->innerJoin('order', 'order.id=order_place.order_id')
                ->where(['order.company_department_id' => $company_department_id])
                ->andWhere("YEAR(order.date_time_create)='" . $year . "'")
                ->andFilterWhere(['MONTH(order.date_time_create)' => $month])
                ->andFilterWhere(['brigade_id' => $brigade_id])
                ->andFilterWhere(['chane_id' => $chane_id])
                ->groupBy('order_year, order_month, order_day, brigade_id, chane_id, worker_id, shift_id')
                ->all();
//            $warnings[] = $report_by_count_people;

            foreach ($report_by_count_people as $people) {
                if (!isset($percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']])) {
                    for ($i = 1; $i <= 12; $i++) {
                        $percent_done_operation[$people['brigade_id']]['brigade_id'] = $people['brigade_id'];
                        if (!isset($percent_done_operation[$people['brigade_id']]['months'][$i])) {
                            $percent_done_operation[$people['brigade_id']]['months'][$i]['month'] = $i;
                            $percent_done_operation[$people['brigade_id']]['months'][$i]['summary_operation_plan'] = 0;
                            $percent_done_operation[$people['brigade_id']]['months'][$i]['summary_operation_fact'] = 0;
                            $percent_done_operation[$people['brigade_id']]['months'][$i]['summary_count_people'] = 0;
                            $percent_done_operation[$people['brigade_id']]['months'][$i]['summary_production_by_people'] = 0;
                            $percent_done_operation[$people['brigade_id']]['months'][$i]['summary_stop_by_rtn'] = 0;
                            $percent_done_operation[$people['brigade_id']]['months'][$i]['summary_stop_by_agk'] = 0;
                            $percent_done_operation[$people['brigade_id']]['months'][$i]['percent'] = 0;
                        }

                        $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$i]['month'] = $i;
                        $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['chane_id'] = $people['chane_id'];
                        $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$i]['summary_operation_plan'] = 0;
                        $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$i]['summary_operation_fact'] = 0;
                        $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$i]['summary_count_people'] = 0;
                        $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$i]['summary_production_by_people'] = 0;
                        $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$i]['summary_stop_by_rtn'] = 0;
                        $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$i]['summary_stop_by_agk'] = 0;
                        $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$i]['percent'] = 0;

                        for ($j = 1; $j <= cal_days_in_month(CAL_GREGORIAN, $i, $year); $j++) {
                            if (!isset($percent_done_operation[$people['brigade_id']]['months'][$i]['days'][$j])) {
                                $percent_done_operation[$people['brigade_id']]['months'][$i]['days'][$j]['day'] = $j;
                                $percent_done_operation[$people['brigade_id']]['months'][$i]['days'][$j]['summary_operation_plan'] = 0;
                                $percent_done_operation[$people['brigade_id']]['months'][$i]['days'][$j]['summary_operation_fact'] = 0;
                                $percent_done_operation[$people['brigade_id']]['months'][$i]['days'][$j]['summary_count_people'] = 0;
                                $percent_done_operation[$people['brigade_id']]['months'][$i]['days'][$j]['summary_production_by_people'] = 0;
                                $percent_done_operation[$people['brigade_id']]['months'][$i]['days'][$j]['summary_stop_by_rtn'] = 0;
                                $percent_done_operation[$people['brigade_id']]['months'][$i]['days'][$j]['summary_stop_by_agk'] = 0;
                                $percent_done_operation[$people['brigade_id']]['months'][$i]['days'][$j]['percent'] = 0;
                            }
                            $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$i]['days'][$j]['day'] = $j;
                            $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$i]['days'][$j]['summary_operation_plan'] = 0;
                            $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$i]['days'][$j]['summary_operation_fact'] = 0;
                            $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$i]['days'][$j]['summary_count_people'] = 0;
                            $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$i]['days'][$j]['summary_production_by_people'] = 0;
                            $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$i]['days'][$j]['summary_stop_by_rtn'] = 0;
                            $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$i]['days'][$j]['summary_stop_by_agk'] = 0;
                            $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$i]['days'][$j]['percent'] = 0;
                        }
                    }
                }

                // ЗВЕНО
                //считаем выхождаемость за месяц план/факт
                $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$people['order_month']]['summary_count_people']++;

                // считаем выхождаемость за день план/факт
                $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$people['order_month']]['days'][$people['order_day']]['summary_count_people']++;

                // производительность на человека в месяц
                $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$people['order_month']]['summary_production_by_people'] =
                    round($percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$people['order_month']]['summary_operation_fact']
                        /
                        $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$people['order_month']]['summary_count_people'], 3);

                // производительность на человека в день
                $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$people['order_month']]['days'][$people['order_day']]['summary_production_by_people'] =
                    round($percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$people['order_month']]['days'][$people['order_day']]['summary_operation_fact']
                        /
                        $percent_done_operation[$people['brigade_id']]['chanes'][$people['chane_id']]['months'][$people['order_month']]['days'][$people['order_day']]['summary_count_people'], 3);

                // БРИГАДА
                //считаем выхождаемость за месяц план/факт
                $percent_done_operation[$people['brigade_id']]['months'][$people['order_month']]['summary_count_people']++;

                // считаем выхождаемость за день план/факт
                $percent_done_operation[$people['brigade_id']]['months'][$people['order_month']]['days'][$people['order_day']]['summary_count_people']++;

                // производительность на человека в месяц
                $percent_done_operation[$people['brigade_id']]['months'][$people['order_month']]['summary_production_by_people'] =
                    round($percent_done_operation[$people['brigade_id']]['months'][$people['order_month']]['summary_operation_fact']
                        /
                        $percent_done_operation[$people['brigade_id']]['months'][$people['order_month']]['summary_count_people'], 3);

                // производительность на человека в день
                $percent_done_operation[$people['brigade_id']]['months'][$people['order_month']]['days'][$people['order_day']]['summary_production_by_people'] =
                    round($percent_done_operation[$people['brigade_id']]['months'][$people['order_month']]['days'][$people['order_day']]['summary_operation_fact']
                        /
                        $percent_done_operation[$people['brigade_id']]['months'][$people['order_month']]['days'][$people['order_day']]['summary_count_people'], 3);
            }

            if (empty($percent_done_operation)) {
                $result['statistic'] = (object)array();
            } else {
                $result['statistic'] = $percent_done_operation;
            }

            foreach ($result['statistic'] as $brigade) {
                foreach ($brigade['months'] as $month) {

                    $final['year']['brigades'][$brigade['brigade_id']]['percent'][] = $month['percent'];
                    $final['year']['brigades'][$brigade['brigade_id']]['summary_count_people'][] = $month['summary_count_people'];
                    $final['year']['brigades'][$brigade['brigade_id']]['summary_production_by_people'][] = $month['summary_production_by_people'];
                    $final['year']['brigades'][$brigade['brigade_id']]['summary_stop_by_agk'][] = $month['summary_stop_by_agk'];
                    $final['year']['brigades'][$brigade['brigade_id']]['summary_stop_by_rtn'][] = $month['summary_stop_by_rtn'];

                    foreach ($month['days'] as $day) {
                        $final['month'][$month['month']]['brigades'][$brigade['brigade_id']]['percent'][] = $day['percent'];
                        $final['month'][$month['month']]['brigades'][$brigade['brigade_id']]['summary_count_people'][] = $day['summary_count_people'];
                        $final['month'][$month['month']]['brigades'][$brigade['brigade_id']]['summary_production_by_people'][] = $day['summary_production_by_people'];
                        $final['month'][$month['month']]['brigades'][$brigade['brigade_id']]['summary_stop_by_agk'][] = $day['summary_stop_by_agk'];
                        $final['month'][$month['month']]['brigades'][$brigade['brigade_id']]['summary_stop_by_rtn'][] = $day['summary_stop_by_rtn'];
                    }
                }

                foreach ($brigade['chanes'] as $chane) {
                    foreach ($chane['months'] as $month) {

                        $final['year']['brigades'][$brigade['brigade_id']]['chanes'][$chane['chane_id']]['chane_id'][] = $chane['chane_id'];
                        $final['year']['brigades'][$brigade['brigade_id']]['chanes'][$chane['chane_id']]['percent'][] = $month['percent'];
                        $final['year']['brigades'][$brigade['brigade_id']]['chanes'][$chane['chane_id']]['summary_count_people'][] = $month['summary_count_people'];
                        $final['year']['brigades'][$brigade['brigade_id']]['chanes'][$chane['chane_id']]['summary_production_by_people'][] = $month['summary_production_by_people'];
                        $final['year']['brigades'][$brigade['brigade_id']]['chanes'][$chane['chane_id']]['summary_stop_by_agk'][] = $month['summary_stop_by_agk'];
                        $final['year']['brigades'][$brigade['brigade_id']]['chanes'][$chane['chane_id']]['summary_stop_by_rtn'][] = $month['summary_stop_by_rtn'];

                        foreach ($month['days'] as $day) {


                            $final['month'][$month['month']]['brigades'][$brigade['brigade_id']]['chanes'][$chane['chane_id']]['chane_id'][] = $chane['chane_id'];
                            $final['month'][$month['month']]['brigades'][$brigade['brigade_id']]['chanes'][$chane['chane_id']]['percent'][] = $day['percent'];
                            $final['month'][$month['month']]['brigades'][$brigade['brigade_id']]['chanes'][$chane['chane_id']]['summary_count_people'][] = $day['summary_count_people'];
                            $final['month'][$month['month']]['brigades'][$brigade['brigade_id']]['chanes'][$chane['chane_id']]['summary_production_by_people'][] = $day['summary_production_by_people'];
                            $final['month'][$month['month']]['brigades'][$brigade['brigade_id']]['chanes'][$chane['chane_id']]['summary_stop_by_agk'][] = $day['summary_stop_by_agk'];
                            $final['month'][$month['month']]['brigades'][$brigade['brigade_id']]['chanes'][$chane['chane_id']]['summary_stop_by_rtn'][] = $day['summary_stop_by_rtn'];
                        }
                    }
                }
            }

            if (!isset($final)) {
                $result['statistic_to_front'] = (object)array();
            } else {
                $result['statistic_to_front'] = $final;
            }

            // расчет обновления численности
            foreach ($report_by_count_people as $people) {
                if (!isset($group_peoples[$people['order_month']]['days'][$people['order_day']]['brigades'][$people['brigade_id']]['chanes'][$people['chane_id']])) {
                    $group_peoples[$people['order_month']]['month'] = $people['order_month'];
                    $group_peoples[$people['order_month']]['days'][$people['order_day']]['day'] = $people['order_day'];
                    $group_peoples[$people['order_month']]['days'][$people['order_day']]['brigades'][$people['brigade_id']]['brigade_id'] = $people['brigade_id'];
                    $group_peoples[$people['order_month']]['days'][$people['order_day']]['brigades'][$people['brigade_id']]['chanes'][$people['chane_id']]['chane_id'] = $people['chane_id'];
                    $group_peoples[$people['order_month']]['days'][$people['order_day']]['brigades'][$people['brigade_id']]['chanes'][$people['chane_id']]['worker_group'] = 'k ';
                    if (!isset($group_peoples[$people['order_month']]['days'][$people['order_day']]['brigades'][$people['brigade_id']]['worker_group'])) {
                        $group_peoples[$people['order_month']]['days'][$people['order_day']]['brigades'][$people['brigade_id']]['worker_group'] = 'k ';
                    }
                }
                $group_peoples[$people['order_month']]['days'][$people['order_day']]['brigades'][$people['brigade_id']]['chanes'][$people['chane_id']]['worker_group'] .= $people['worker_id'];
                $group_peoples[$people['order_month']]['days'][$people['order_day']]['brigades'][$people['brigade_id']]['worker_group'] .= $people['worker_id'];
            }

            foreach ($group_peoples as $people_month) {
                foreach ($people_month['days'] as $people_day) {
                    foreach ($people_day['brigades'] as $people_brigade) {
                        $update_people[$people_month['month']]['brigades'][$people_brigade['brigade_id']]['worker_group'][$people_brigade['worker_group']] = $people_brigade['worker_group'];
                        foreach ($people_brigade['chanes'] as $people_chane) {
                            $update_people[$people_month['month']]['month'] = $people_month['month'];
                            $update_people[$people_month['month']]['brigades'][$people_brigade['brigade_id']]['brigade_id'] = $people_brigade['brigade_id'];
                            $update_people[$people_month['month']]['brigades'][$people_brigade['brigade_id']]['chanes'][$people_chane['chane_id']]['chane_id'] = $people_chane['chane_id'];
                            $update_people[$people_month['month']]['brigades'][$people_brigade['brigade_id']]['chanes'][$people_chane['chane_id']]['worker_group'][$people_chane['worker_group']] = $people_chane['worker_group'];
                        }
                    }
                }
            }

            foreach ($update_people as $people_month) {
                foreach ($people_month['brigades'] as $people_brigade) {
                    foreach ($people_brigade['worker_group'] as $worker_group1) {
                        if (!isset($update_people_final[$people_month['month']]['brigades'][$people_brigade['brigade_id']]['group_count'])) {
                            for ($i = 1; $i <= 12; $i++) {
                                $update_people_final[$i]['brigades'][$people_brigade['brigade_id']]['group_count'] = 0;
                            }
                        }
                        $update_people_final[$people_month['month']]['brigades'][$people_brigade['brigade_id']]['group_count']++;
                    }

                    foreach ($people_brigade['chanes'] as $people_chane) {
                        foreach ($people_chane['worker_group'] as $worker_group) {
                            if (!isset($update_people_final[$people_month['month']]['brigades'][$people_brigade['brigade_id']]['chanes'][$people_chane['chane_id']]['group_count'])) {
                                for ($i = 1; $i <= 12; $i++) {
                                    $update_people_final[$i]['month'] = $i;
                                    $update_people_final[$i]['brigades'][$people_brigade['brigade_id']]['brigade_id'] = $people_brigade['brigade_id'];
                                    $update_people_final[$i]['brigades'][$people_brigade['brigade_id']]['chanes'][$people_chane['chane_id']]['chane_id'] = $people_chane['chane_id'];
                                    $update_people_final[$i]['brigades'][$people_brigade['brigade_id']]['chanes'][$people_chane['chane_id']]['group_count'] = 0;
                                }
                            }
                            $update_people_final[$people_month['month']]['brigades'][$people_brigade['brigade_id']]['chanes'][$people_chane['chane_id']]['group_count']++;
                        }
                    }
                }

            }
            if (!isset($update_people_final)) {
                $result['stabile_brigade'] = (object)array();
            } else {
                $result['stabile_brigade'] = $update_people_final;
            }

            // получаем статистику происшествий по группам (несчастный случай, инцидент, авария, прочие происшествия)
            $count_event_pb = (new Query())
                ->select('max_chane_id, max_brigade_id, count(event_pb.id) as sum_event_pb_value')
                ->from('event_pb_worker')
                ->innerJoin('event_pb', 'event_pb_worker.event_pb_id = event_pb.id')
                ->innerJoin('view_order_brigade_chane_by_worker_date',
                    '
                    event_pb_worker.worker_id=view_order_brigade_chane_by_worker_date.worker_id and
                    event_pb.date_time_event=view_order_brigade_chane_by_worker_date.date_time_create
                    ')
                ->where(['view_order_brigade_chane_by_worker_date.company_department_id' => $company_department_id])
                ->andWhere("YEAR(event_pb.date_time_event)='" . $year . "'")
                ->andFilterWhere(['MONTH(event_pb.date_time_event)' => $month])
                ->groupBy('max_brigade_id, max_chane_id')
                ->all();

            foreach ($count_event_pb as $item) {
                if (!isset($inquire[$item['max_brigade_id']]['count_event_pb'])) {
                    $inquire[$item['max_brigade_id']]['count_event_pb'] = 0;
                }
                $inquire[$item['max_brigade_id']]['brigade_id'] = $item['max_brigade_id'];
                $inquire[$item['max_brigade_id']]['count_event_pb'] += $item['sum_event_pb_value'];
                $inquire[$item['max_brigade_id']]['chanes'][$item['max_chane_id']]['chane_id'] = $item['max_chane_id'];
                $inquire[$item['max_brigade_id']]['chanes'][$item['max_chane_id']]['count_event_pb'] = $item['sum_event_pb_value'];
            }

            if (!isset($inquire)) {
                $result['inquire'] = (object)array();
            } else {
                $result['inquire'] = $inquire;
            }

            // расчет обновления численности


            /** Метод окончание */
        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                   // описание текущей отладочной точки
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

        // запись в БД начала выполнения скрипта
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                         // время окончания выполнения метода
        LogAmicum::LogAmicumStatistic($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        return $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }
}
