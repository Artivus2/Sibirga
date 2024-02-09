<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\ordersystem;

use backend\controllers\Assistant as BackendAssistant;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\industrial_safety\CheckingController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Attachment;
use frontend\models\CompanyDepartment;
use frontend\models\Cyclegramm;
use frontend\models\CyclegrammOperation;
use frontend\models\Event;
use frontend\models\GraficTabelMain;
use frontend\models\Injunction;
use frontend\models\Operation;
use frontend\models\Order;
use frontend\models\OrderOperation;
use frontend\models\OrderOperationAttachment;
use frontend\models\OrderOperationImg;
use frontend\models\OrderPlace;
use frontend\models\OrderStatus;
use frontend\models\PlaceOperation;
use frontend\models\PlaceOperationValue;
use frontend\models\Planogramma;
use frontend\models\PlanogrammOperation;
use frontend\models\StopPb;
use frontend\models\StopPbEquipment;
use frontend\models\StopPbEvent;
use frontend\models\TypeOperation;
use frontend\models\Unit;
use Throwable;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\Controller;

class ReportForPreviousPeriodController extends Controller
{

    // SaveReportCyclogram          - сохранение циклограммы через ReadManager
    // GetReportDataByShift         - получение показателей посменно бригадой
    // SaveReport                   - Общий метод сохранения отчёта (вызываются методы:SaveReportCyclogram, OrderSystemController::ChangeWorkerValueOutgoing)
    // SavingIndicators             - Сохранение показателей на стринаце "Заполнение отчёта"
    // GetReportData                - Возвращает все данные для страницы "Заполнение отчёта"
    // GetBrigadeStatistic          - получает статистику бригады на участке(показатели по сменам и циклограмма)
    // GetPlanogramma               - Метод получения планограммы
    // GetCyclogramm                - метод получения циклограммы. Работает не через readManager
    // GetRecoverabilityByShifts    - Метод получения выхождаемости работников по сменам
    // GetTypeOperations            - метод получяения типов операций для циклограммы из справочника типов операций
    // GetEvents                    - метод получения списка событий для циклограммы
    // SetCoalMiningPlan            - Метод сохранения плана добычи угля бригадой на месяц
    // GetInjunctionReport          - получение предписаний по сменам
    // GetDepartmentOperationType   - получение типа участка и типа операций которые выполняются на участке по идентификатору участка
    // GetDepartmentMainOperation   - получение 1 главной операции для участка
    // GetCoalMiningPlan            - Метод получения плана добычи угля бригадой на год
    // GetCoalMiningReportByMonth   - Метод получения плана добычи угля бригадой на месяц
    // GetMaterial                  - Метод получения остатков материалов
    // SaveCyclogram                - сохранение циклограммы
    // SaveStopFace                 - метод сохранения данных простоя
    // GetUnits                     - получения справочника единиц измерения
    // SavePlanogramm               - Сохранение планограммы

    public function actionIndex()
    {
        return $this->render('index');
    }

    const STATUS_ACTIVE = 1;                                                                                            // Актуальный/активный статус
    const STATUS_INACTIVE = 19;                                                                                            // Неактуальный/не активный статус
    const WORKING_TIME_WORK = 1;                                                                                        // Тип рабочего времени - рабочий день
    const PLACE_LEVEL_UPMINE = 110;                                                                                     // Тип места - надшахтное здание
    const OPERATION_EXCAVATION_COAL = 24;
    /** @var int Тип операции - запуск под нагрузкой */
    const TYPE_OPERATION_LAUNCH_LOAD = 5;
    /** @var int Тип операции - очистные работы */
    const TYPE_OPERATION_SEWAGE = 9;
    /** @var int Тип операции - проведение горных выработок */
    const TYPE_OPERATION_MINE = 11;
    /** @var int Количество смен */
    const SHIFT_COUNT = 5;

    /**
     * Название метода: GetRecoverabilityByShifts() - Метод получения выхождаемости работников по сменам
     * Метод получения выхождаемости работников по сменам
     *
     * @param null $data_post
     * @return array
     * @package frontend\controllers\ordersystem
     * @example http://amicum/read-manager-amicum?controller=ordersystem\ReportForPreviousPeriod&method=GetRecoverabilityByShifts&subscribe=worker_list&data={"brigade_id":347,"year":2019,"month":6,"day":11}
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 24.06.2019 16:20
     * @since ver
     */
    public static function GetRecoverabilityByShifts($data_post = NULL)
    {
        $status = 1;                                                                                                        // Флаг успешного выполнения метода
        $warnings = array();                                                                                                // Массив предупреждений
        $errors = array();                                                                                                  // Массив ошибок
        $recoverability_shifts = array();                                                                                   // Промежуточный результирующий массив
        try {
            if ($data_post !== NULL && $data_post !== '')                                                                   // Проверяем передана ли входная JSON строка
            {
                $warnings[] = 'GetRecoverabilityByShifts. Данные успешно переданы';
                $warnings[] = 'GetRecoverabilityByShifts. Входной массив данных' . $data_post;
            } else {
                throw new Exception('GetRecoverabilityByShifts. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetRecoverabilityByShifts. Декодировал входные параметры';
            if (
                property_exists($post_dec, 'year') &&
                property_exists($post_dec, 'month') &&
                property_exists($post_dec, 'day') &&
                property_exists($post_dec, 'brigade_id')
            )                                                                                                           // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'GetRecoverabilityByShifts. Данные с фронта получены';
            } else {
                throw new Exception('GetRecoverabilityByShifts. Переданы некорректные входные параметры');
            }

            /************************************************* ТЕЛО МЕТОДА *************************************************/
            $warnings[] = 'GetRecoverabilityByShifts. Начало выполнения метода';

            $brigade_id = $post_dec->brigade_id;
            $day = $post_dec->day;
            $month = $post_dec->month;
            $year = $post_dec->year;
            $date = $year . '-' . $month . '-' . $day;
            /**
             * Формируем пустой массив выхождаемости для отправки на фронт
             */
            for ($i = 1; $i < 5; $i++) {
                $recoverability_shifts[$i]['recoverability_plan'] = 0;
                $recoverability_shifts[$i]['recoverability_mine'] = 0;
                $recoverability_shifts[$i]['recoverability_upmine'] = 0;
            }

            /************************************************* ФОРМИРУЕМ ПЛАНОВУЮ ВЫХОЖДАЕМОСТЬ НА ОСНОВЕ ГРАФИКА ВЫХОДОВ *************************************************/

            $worker_plan_count = GraficTabelMain::find()// Получение выхождаемости работников по плану из графика выходов
            ->select('grafic_tabel_date_plan.day, grafic_tabel_date_plan.shift_id')
                ->innerJoin('grafic_tabel_date_plan',
                    'grafic_tabel_date_plan.grafic_tabel_main_id = grafic_tabel_main.id AND grafic_tabel_main.status_id = ' . self::STATUS_ACTIVE .
                    ' AND grafic_tabel_date_plan.working_time_id = ' . self::WORKING_TIME_WORK .
                    ' AND grafic_tabel_date_plan.day = ' . $day .
                    ' AND grafic_tabel_date_plan.month = ' . $month .
                    ' AND grafic_tabel_date_plan.year = ' . $year)
                ->innerJoin('brigade_worker', 'brigade_worker.worker_id = grafic_tabel_date_plan.worker_id')
                ->innerJoin('brigade', 'brigade_worker.brigade_id = brigade.id AND brigade.status_id = ' . self::STATUS_ACTIVE . ' AND brigade.id = ' . $brigade_id)
                ->asArray()
                ->all();

            foreach ($worker_plan_count as $worker_plan) {
                $recoverability_shifts[$worker_plan['shift_id']]['recoverability_plan']++;
            }

            /**
             * Формируем фактическую выхождаемость на основе нарядов
             */
            $worker_fact_count = Order::find()
                ->select(['object.object_type_id', 'order.shift_id', 'operation_worker.worker_id'])
                ->innerJoin('order_place', 'order_place.order_id = order.id')
                ->leftJoin('place', 'order_place.place_id = place.id')
                ->leftJoin('object', 'place.object_id = object.id')
                ->innerJoin('order_operation', 'order_place.id = order_operation.order_place_id')
                ->innerJoin('operation_worker', 'operation_worker.order_operation_id = order_operation.id')
                ->groupBy('object.object_type_id, order.shift_id, operation_worker.worker_id')
                ->where(['order.brigade_id' => $brigade_id, 'order.date_time_create' => $date])
                ->asArray()
                ->all();
            /**
             * Подсчитываем количество работников работающих над шахтой и в шахте
             */
            foreach ($worker_fact_count as $worker_fact) {
                if ($worker_fact['object_type_id'] === self::PLACE_LEVEL_UPMINE . '') {
                    $recoverability_shifts[$worker_fact['shift_id']]['recoverability_upmine']++;
                } else {
                    $recoverability_shifts[$worker_fact['shift_id']]['recoverability_mine']++;
                }
            }
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result = $recoverability_shifts;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод GetCyclogramm() - метод получения циклограммы. Работает не через readManager
     *
     * @param $brigade_id - идентификатор бригады
     * @param $company_department_id - идентификатор участка
     * @param $date - дата на которую получается циклограмма
     * @return array                    - массив с данными об операциях циклограммы
     * @package frontend\controllers\ordersystem
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * order_arr - массив нарядов
     * company_department_id - идентификатор участка
     * date - дата (для получения date_start, date_end, в зависимости от того передана ли смена)
     * shift_id - идентификатор смену (по умолчанию пуст)
     * chane_id - идентификатор звена (по умолчанию пуст)
     *
     * СТРУКТУРА ВЫХОДНОГО МАССИВА:
     * {
     *      "cyclogram_option":
     *      {
     *          "max_section": "100",
     *          "date_time_start": "2020-02-20 08:00:00",
     *          "date_time_end": "2020-02-20 14:00:00",
     *          "type_operation_id": "1",
     *          "section_start": 0,
     *          "section_end": 0,
     *          "cyclegramm_type_id": 2,
     *          "equipment_id": 139966
     *          "equipment_title": "sdsdf"
     *      },
     *      "cyclograms_operations":
     *      [{
     *          "type_operation_id": "1",
     *          "date_time_start": "2020-02-20 08:00:00",
     *          "date_time_end": "2020-02-20 11:11:00",
     *          "section_start": 0,
     *          "section_end": 20
     *      },
     *      {
     *          "type_operation_id": "1",
     *          "date_time_start": "2020-02-20 11:12:00",
     *          "date_time_end": "2020-02-20 14:00:00",
     *          "section_start": 20,
     *          "section_end": 0
     *      },
     *      {
     *          "type_operation_id": "8",
     *          "date_time_start": "2020-02-20 11:11:00",
     *          "date_time_end": "2020-02-20 11:12:00",
     *          "section_start": 20,
     *          "section_end": 20,
     *          "events": [{
     *          "event_id": 5
     *          }, {
     *          "event_id": 10
     *          }
     *          ],
     *          "place_id": "6183",
     *          "place_title": ";kmhk",
     *          "kind_stop_pb_id": "4",
     *          "description": ""
     *      }]
     * }
     * АЛГОРИТМ РАБОТЫ:
     * 1. Получить все приостановки работ за смену (если её передали) если нет то за сутки
     * 2. Сформировать данные по приостановке работы массив вида:
     *                                   [equipment_id]
     *                                       [-1]
     *                                           order_planogram_id:
     *                                           stop_pb_id:
     *                                           date_time_start:
     *                                           date_time_end:
     *                                           type_operation_id:
     *                                           kind_stop_pb_id:
     *                                           place_id:
     *                                           place_title:
     *                                           description:
     *                                           [events]
     *                                                   [event_id:]
     * 3. Получить данные циклограммы по: наряду и звену
     * 4. Подготавливаем массив для записи простоев по сменам
     * 5. Объявляем переменные которые будут хранить в себе типы операций и идентификаторы операций
     * 6. Перебор полученных данных
     *    6.1 Дополнительно формируем отдельные циклограммы на каждую смену и на каждое звено
     *    6.2 Докидываем пересменку в предыдущую смену
     *    6.3 Перебираем список операций циклограммы
     *        6.3.1 Формируем массив
     *    6.4 Докидываем сформированные простои по идентификатору оборудования
     *    6.5 Запоминаем данные смены и звена для записи пересменки
     * 7. Конец перебора
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 26.06.2019 10:14
     */
    public static function GetCyclogramm($order_arr, $company_department_id, $date, $shift_id = 5, $chane_id = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $stop_pb_data = array();
        try {
            $warnings[] = 'GetCyclogramm. Зашел в метод, получение данных циклограммы';
            /******************** Получаем данные из простоев ********************/
            /**
             * Выргружаем данные за 4 смены одного дня по участку
             */
            $response = Assistant::GetDateTimeByShift($date, $shift_id);
            $date_start = $response['date_start'];
            $date_end = $response['date_end'];
            $stop_pbs = StopPb::find()
                ->innerJoinWith('stopPbEquipments')
                ->innerJoinWith('stopPbEvents')
                ->where(['stop_pb.company_department_id' => $company_department_id])
                ->andWhere(['or',
                    ['and',
                        'stop_pb.date_time_start > \'' . $date_start . '\'',
                        'stop_pb.date_time_start < \'' . $date_end . '\''
                    ],
                    ['and',
                        'stop_pb.date_time_end > \'' . $date_start . '\'',
                        'stop_pb.date_time_end < \'' . $date_end . '\''
                    ]
                ])
                ->all();
            $counter = -1;
            /******************** Формируем массив простоев ********************/
            if (!empty($stop_pbs)) {
                foreach ($stop_pbs as $stop_pb) {
                    $stop_pb_id = $stop_pb->id;
                    $kind_stop_pb_id = $stop_pb->kind_stop_pb_id;
                    $place_id = $stop_pb->place_id;
                    $place_title = $stop_pb->place->title;
                    $date_time_start = $stop_pb->date_time_start;
                    $date_time_end = $stop_pb->date_time_end;
                    $description = $stop_pb->description;
                    $type_operation_id = $stop_pb->type_operation_id;
                    $section = $stop_pb->section;
                    $counter_evetns = 0;
                    foreach ($stop_pb->stopPbEvents as $stopPbEvent) {
                        $events[$stop_pb_id][$counter_evetns]['event_id'] = $stopPbEvent->event_id;
                        $counter_evetns++;
                    }
                    foreach ($stop_pb->stopPbEquipments as $stopPbEquipment) {
                        $equipment_id = $stopPbEquipment->equipment_id;
                        $stop_pb_data[$equipment_id][$counter]['stop_pb_id'] = $stop_pb_id;
                        $stop_pb_data[$equipment_id][$counter]['order_cyclegram_id'] = $counter;
                        // $date_time_start - начало простоя
                        // $date_time_end - окончание простоя
                        // $date_start - начало смены
                        // $date_end - окончание смены
                        if ($shift_id != null) {
                            if ($date_time_start < $date_start) {
                                $date_time_start = $date_start;
                            }
                            if ($date_time_end > $date_end) {
                                $date_time_end = date('Y-m-d H:i:s', strtotime($date_end . '+1 min'));
                            }
                        }
                        $stop_pb_data[$equipment_id][$counter]['date_time_start'] = $date_time_start;
                        $stop_pb_data[$equipment_id][$counter]['date_time_end'] = $date_time_end;
                        $stop_pb_data[$equipment_id][$counter]['type_operation_id'] = $type_operation_id;
                        $stop_pb_data[$equipment_id][$counter]['section_start'] = $section;
                        $stop_pb_data[$equipment_id][$counter]['section_end'] = $section;
                        $stop_pb_data[$equipment_id][$counter]['kind_stop_pb_id'] = $kind_stop_pb_id;
                        $stop_pb_data[$equipment_id][$counter]['place_id'] = $place_id;
                        $stop_pb_data[$equipment_id][$counter]['place_title'] = $place_title;
                        $stop_pb_data[$equipment_id][$counter]['description'] = $description;
                        if (isset($events[$stop_pb_id]) && !empty($events[$stop_pb_id])) {
                            $stop_pb_data[$equipment_id][$counter]['events'] = $events[$stop_pb_id];
                        } else {
                            $stop_pb_data[$equipment_id][$counter]['events'] = (object)array();
                        }
                        $counter--;
                    }
                }
                unset($stop_pbs, $stop_pb, $stopPbEvent, $stopPbEquipment, $events);
            }

            /******************** Получаем данные циклограмм ********************/
            $warnings[] = $chane_id;
            $order_cyclegramms = Order::find()
                ->joinWith(['cyclegramms cyclo' => function ($cyclogramm) use ($chane_id) {
                    $cyclogramm->andFilterWhere(['cyclo.chane_id' => $chane_id]);
                }])
                ->joinWith('cyclegramms.cyclegrammOperations')
                ->where(['IN', 'order.id', $order_arr])
                ->orderBy('cyclegramm_operation.date_time_start')
                ->all();
            /**
             * Подготавливаем массив для записи простоев по сменам
             */
            for ($i = 1; $i < 5; $i++) {
                $order_statistic[$i]['stop_time_count'] = 0;
            }
            $warnings[] = 'GetCyclogramm. Раскидываем операции по сменам';

            /**
             * Объявляем переменные которые будут хранить в себе типы операций и идентификаторы операций
             */
            $previous_cyclogram_shift_id = -1;
            $previous_cyclogram_chane_id = -1;
            /**
             * Дополнительно формируем отдельные циклограммы на каждую смену и на каждое звено
             */

            foreach ($order_cyclegramms as $order_cyclegramm)                                                            // Перебор данных нарядов
            {
                if (isset($order_cyclegramm->cyclegramms))                                                                // Если для данного наряда найдена циклограмма записываем данные о ней
                {

                    foreach ($order_cyclegramm->cyclegramms as $cyclegramm_order) {

//                        if (date("H:i", strtotime($cyclegramm_order->date_time_end)) == "00:00") {
//                            $cyclegramm_order->date_time_end = date("Y:m:d", strtotime($cyclegramm_order->date_time_end . '-1day')) . " 24:00:00";
//                        }
                        $cyclegramm[$cyclegramm_order->cyclegramm_type_id]['shifts'][$order_cyclegramm->shift_id]['shift_id'] = $order_cyclegramm->shift_id;
                        $cyclegramm[$cyclegramm_order->cyclegramm_type_id]['shifts'][$order_cyclegramm->shift_id]['chanes_cyclogram'][$cyclegramm_order->chane_id]['chane_id'] = $cyclegramm_order->chane_id;
                        $cyclegramm[$cyclegramm_order->cyclegramm_type_id]['shifts'][$order_cyclegramm->shift_id]['cyclogram_option']['date_time_start'] = $cyclegramm_order->date_time_start;
                        $cyclegramm[$cyclegramm_order->cyclegramm_type_id]['shifts'][$order_cyclegramm->shift_id]['cyclogram_option']['date_time_end'] = $cyclegramm_order->date_time_end;
                        $cyclegramm[$cyclegramm_order->cyclegramm_type_id]['shifts'][$order_cyclegramm->shift_id]['cyclogram_option']['max_section'] = $cyclegramm_order->max_section;
                        $cyclegramm[$cyclegramm_order->cyclegramm_type_id]['shifts'][$order_cyclegramm->shift_id]['cyclogram_option']['cyclegramm_type_id'] = $cyclegramm_order->cyclegramm_type_id;
                        $cyclegramm[$cyclegramm_order->cyclegramm_type_id]['shifts'][$order_cyclegramm->shift_id]['cyclogram_option']['section_start'] = $cyclegramm_order->section_start;
                        $cyclegramm[$cyclegramm_order->cyclegramm_type_id]['shifts'][$order_cyclegramm->shift_id]['cyclogram_option']['section_end'] = $cyclegramm_order->section_end;
                        $cyclegramm[$cyclegramm_order->cyclegramm_type_id]['shifts'][$order_cyclegramm->shift_id]['cyclogram_option']['equipment_id'] = $cyclegramm_order->equipment_id;
                        $cyclegramm[$cyclegramm_order->cyclegramm_type_id]['shifts'][$order_cyclegramm->shift_id]['cyclogram_option']['equipment_title'] = $cyclegramm_order->equipment->title;
                        /**
                         * Докидываем пересменку в предыдущую смену
                         */
                        if (isset($cyclegramm_order->cyclegrammOperations[0]) && $previous_cyclogram_shift_id !== -1 && $cyclegramm_order->cyclegrammOperations[0]->type_operation_id === 3) {
                            $cyclegramm[$cyclegramm_order->cyclegramm_type_id]['shifts'][$previous_cyclogram_shift_id]['chanes_cyclogram'][$previous_cyclogram_chane_id]['cyclograms_operations'][$cyclegramm_order->cyclegrammOperations[0]->id] = $cyclegramm_order->cyclegrammOperations[0]->id;
                        }


                        /**
                         * Перебираем список операций циклограммы
                         */
                        foreach ($cyclegramm_order->cyclegrammOperations as $operation)                                        // Перебор операций(отрезков) циклограммы
                        {
                            if ($operation->date_time_start < $cyclegramm_order->date_time_start && $order_cyclegramm->shift_id !== 1)               // Если операция заползает на прошлую смену для корректного построения добавляем её в прошлую смену
                            {
                                $cyclegramm[$cyclegramm_order->cyclegramm_type_id]['shifts'][$order_cyclegramm->shift_id - 1]['chanes_cyclogram'][$cyclegramm_order->chane_id]['cyclograms_operations'][$operation->id] = $operation->id;
                            }
                            $cyclegramm[$cyclegramm_order->cyclegramm_type_id]['shifts'][$order_cyclegramm->shift_id]['chanes_cyclogram'][$cyclegramm_order->chane_id]['cyclograms_operations'][$operation->id] = $operation->id;
                            $cyclegramm[$cyclegramm_order->cyclegramm_type_id]['cyclograms_operations'][$operation->id] = [
                                'order_cyclegram_id' => $operation->id,
                                'date_time_start' => $operation->date_time_start,
                                'date_time_end' => $operation->date_time_end,
                                'type_operation_id' => $operation->type_operation_id,
                                'section_start' => $operation->section_start,
                                'section_end' => $operation->section_end,
                            ];
                        }

                        if (isset($stop_pb_data[$cyclegramm_order->equipment_id]) && !empty($stop_pb_data[$cyclegramm_order->equipment_id])) {
                            foreach ($stop_pb_data[$cyclegramm_order->equipment_id] as $stop_pb_key => $stop_pb_item) {
                                $cyclegramm[$cyclegramm_order->cyclegramm_type_id]['shifts'][$order_cyclegramm->shift_id]['chanes_cyclogram'][$cyclegramm_order->chane_id]['cyclograms_operations'][$stop_pb_key] = $stop_pb_key;
                                $cyclegramm[$cyclegramm_order->cyclegramm_type_id]['cyclograms_operations'][$stop_pb_key] = $stop_pb_item;
                            }
                        }
                        /**
                         * Запоминаем данные смены и звена
                         */
                        $previous_cyclogram_shift_id = $order_cyclegramm->shift_id;
                        $previous_cyclogram_chane_id = $cyclegramm_order->chane_id;
                    }
                }
            }

            $warnings[] = 'GetCyclogram. Метод завершил работу';
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = (string)$exception->getLine();
            $status = 0;
        }
        if (!isset($cyclegramm)) {
            $cyclegramm = (object)array();
        }
        if (!isset($order_statistic)) {
            $order_statistic = (object)array();
        }

        return array('Items' => ['cyclogram' => $cyclegramm, 'order_statistics' => $order_statistic], 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetReportDataByShift() - получение показателей посменно бригадой
     * @param $brigade_id - идентификатор бригады
     * @param $company_department_id - идентификатор структурного подразделения
     * @param $date - дата на которую выбираются показатели
     * @return array                        - массив с данными показателей
     * @package frontend\controllers\ordersystem
     * @example
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 03.07.2019 15:25
     */
    private static function GetReportDataByShift($order_arr, $company_department_id, $date)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $order_results_worker = NULL;
        try {
            $warnings[] = 'GetReportDataByShift. Зашел в метод, получение типа операции на участке';
            /**
             * Получаем список операции для данного участка для построения показателей работы участка
             */
            $operations_ids = self::GetDepartmentOperation($company_department_id);

            if ($operations_ids['status'] === 1) {
                $warnings[] = $operations_ids['warnings'];
                $order_results_worker['department_type_id'] = $operations_ids['department_type_id'];
                $operation_ids = $operations_ids['operation_ids'];
                $errors[] = $operations_ids['errors'];
            } else {
                $errors[] = $operations_ids['errors'];
                $order_results_worker['department_type_id'] = -1;
                $warnings[] = $operations_ids['warnings'];
                throw new Exception('GetReportDataByShift. Для данного участка не найден тип участка и тип операций выполняемых на нем');
            }

            $warnings[] = 'GetReportDataByShift. список операций';
            $warnings[] = $operation_ids;
            $warnings[] = 'GetReportDataByShift. Получение статистических показателей';
            /**
             * Статистические показатели выполнения работ по плану и факту
             */
            //            $orders = (new Query())
//                ->select('order.id')
//                ->from('order')
//                ->innerJoin('order_place', 'order.id=order_place.order_id')
//                ->innerJoin('place', 'place.id=order_place.place_id')
//                ->innerJoin('order_operation', 'order_operation.order_place_id=order_place.id')
//                ->innerJoin('operation', 'operation.id=order_operation.operation_id')
//                ->innerJoin('unit', 'operation.unit_id=unit.id')
//                ->innerJoin('shift', 'shift.id=order.shift_id')
//                ->where(['IN', 'order.id', $order_arr])
//                ->andWhere(['IN', 'operation.id', $operation_ids])
//                ->all();

//            $orders = Order::find()
//                ->innerJoinWith('orderPlaces')
//                ->innerJoinWith('orderPlaces.place')
//                ->innerJoinWith('orderPlaces.orderOperations')
//                ->innerJoinWith('orderPlaces.orderOperations.operation')
//                ->innerJoinWith('orderPlaces.orderOperations.operation.unit')
//                ->innerJoinWith('shift')
//                ->where(['IN', 'order.id', $order_arr])
//                ->andWhere(['IN', 'operation.id', $operation_ids])
//                ->all();
            $orderOperations = OrderOperation::find()
                ->innerJoinWith('orderPlace')
                ->innerJoinWith('orderPlace.place')
                ->innerJoinWith('orderPlace.order')
                ->innerJoinWith('operation')
                ->innerJoinWith('operation.unit')
                ->innerJoinWith('orderPlace.order.shift')
                ->where(['IN', 'order.id', $order_arr])
                ->andWhere(['IN', 'operation.id', $operation_ids])
                ->all();
            $warnings[] = 'GetReportDataByShift. Данные по показаелям получены из БД, формирование массива';
            $warnings[] = $orderOperations;

            // получаем список нарядов пользователей и справочник звеньев
            $operationWorkers = (new Query())
                ->select(['operation_worker.id as id', 'operation_worker.order_operation_id', 'chane.id as chane_id', 'chane.title as chane_title'])
                ->from('operation_worker')
                ->innerJoin('order_operation', 'order_operation.id=operation_worker.order_operation_id')
                ->innerJoin('order_place', 'order_operation.order_place_id=order_place.id')
                ->innerJoin('chane', 'operation_worker.chane_id=chane.id')
                ->where(['IN', 'order_place.order_id', $order_arr])
                ->indexBy('order_operation_id')
                ->all();
//            if (!$operationWorkers) {
//                throw new \Exception('GetReportDataByShift. Таблица нарядов пользователей пуста OperationWorker');
//            }

            /**
             * Раскидываем данные по операциям по сменам, и смены по операциям
             */
            $latin_number = array(1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V');
            foreach ($orderOperations as $orderOperation) {
                $shift_id = $orderOperation->orderPlace->order->shift_id;
                $order_place_id = $orderOperation->orderPlace->id;
                $order_operation_id = $orderOperation->id;
                if (isset($operationWorkers[$order_operation_id])) {
                    $chane_id = (int)$operationWorkers[$order_operation_id]['chane_id'];
                    $order_results_worker['shifts'][$shift_id . "/" . $chane_id]['shift_id'] = (int)$shift_id;
                    $order_results_worker['shifts'][$shift_id . "/" . $chane_id]['shift_chane_id'] = $shift_id . "/" . $chane_id;
                    $order_results_worker['shifts'][$shift_id . "/" . $chane_id]['numberShift'] = $latin_number[(int)$shift_id];
                    $order_results_worker['shifts'][$shift_id . "/" . $chane_id]['shift_title'] = $orderOperation->orderPlace->order->shift->title;
                    if ($orderOperation->description) {
                        $order_results_worker['shifts'][$shift_id . "/" . $chane_id]['description'][] = $orderOperation->description;
                    }


                    $order_results_worker['shifts_operation'][$shift_id . "/" . $chane_id]['shift_id'] = $shift_id;
                    $order_results_worker['shifts_operation'][$shift_id . "/" . $chane_id]['shift_place'][$order_place_id]['order_place_id'] = $order_place_id;
                    $order_results_worker['shifts_operation'][$shift_id . "/" . $chane_id]['shift_place'][$order_place_id]['place_id'] = $orderOperation->orderPlace->place_id;
                    $order_results_worker['shifts_operation'][$shift_id . "/" . $chane_id]['shift_place'][$order_place_id]['place_title'] = $orderOperation->orderPlace->place->title;
                    $warnings[] = $order_operation_id;
                    if (isset($operationWorkers[$order_operation_id])) {
                        $chane_title = $operationWorkers[$order_operation_id]['chane_title'];
                        $order_results_worker['shifts'][$shift_id . "/" . $chane_id]['chane_id'] = $chane_id;
                    } else if (!isset($order_results_worker['shifts'][$shift_id]['chane_title'])) {
                        $chane_title = "";
                        $order_results_worker['shifts'][$shift_id . "/" . $chane_id]['chane_id'] = null;
                    }
                    $order_results_worker['shifts'][$shift_id . "/" . $chane_id]['chane_title'] = $chane_title;
                    $order_results_worker['shifts_operation'][$shift_id . "/" . $chane_id]['shift_place'][$order_place_id]['operations'][$order_operation_id]['order_operation_id'] = $order_operation_id;
                    $order_results_worker['shifts_operation'][$shift_id . "/" . $chane_id]['shift_place'][$order_place_id]['operations'][$order_operation_id]['operation_id'] = $orderOperation->operation_id;
                    $order_results_worker['shifts_operation'][$shift_id . "/" . $chane_id]['shift_place'][$order_place_id]['operations'][$order_operation_id]['operation_title'] = $orderOperation->operation->title;
                    $order_results_worker['shifts_operation'][$shift_id . "/" . $chane_id]['shift_place'][$order_place_id]['operations'][$order_operation_id]['operation_short_title'] = $orderOperation->operation->short_title;
                    $order_results_worker['shifts_operation'][$shift_id . "/" . $chane_id]['shift_place'][$order_place_id]['operations'][$order_operation_id]['unit_short_title'] = $orderOperation->operation->unit->short;
                    // рассчет планового показателя
                    if (!isset($order_results_worker['shifts_operation'][$shift_id . "/" . $chane_id]['shift_place'][$order_place_id]['operations'][$order_operation_id]['plan_value'])) {
                        $order_results_worker['shifts_operation'][$shift_id . "/" . $chane_id]['shift_place'][$order_place_id]['operations'][$order_operation_id]['plan_value'] = 0;
                    }
                    $operation_value_plan = $orderOperation->operation_value_plan;
                    if (!$operation_value_plan) {
                        $operation_value_plan = 0;
                    }
                    $order_results_worker['shifts_operation'][$shift_id . "/" . $chane_id]['shift_place'][$order_place_id]['operations'][$order_operation_id]['plan_value'] += (float)$operation_value_plan;

                    // рассчет фактического показателя
                    if (!isset($order_results_worker['shifts_operation'][$shift_id . "/" . $chane_id]['shift_place'][$order_place_id]['operations'][$order_operation_id]['fact_value'])) {
                        $order_results_worker['shifts_operation'][$shift_id . "/" . $chane_id]['shift_place'][$order_place_id]['operations'][$order_operation_id]['fact_value'] = 0;
                    }
                    $operation_value_fact_value = $orderOperation->operation_value_fact;
                    if (!$operation_value_fact_value) {
                        $operation_value_fact_value = 0;
                    }
                    $order_results_worker['shifts_operation'][$shift_id . "/" . $chane_id]['shift_place'][$order_place_id]['operations'][$order_operation_id]['fact_value'] += (float)$operation_value_fact_value;


                    //обратаный объект для левого списка показателей
//                        $order_results_worker['operation_shift'][$order_place->place_id]['order_place_id'] = $order_place_id;
                    $order_results_worker['operation_shift'][$orderOperation->orderPlace->place_id]['place_id'] = $orderOperation->orderPlace->place_id;
                    $order_results_worker['operation_shift'][$orderOperation->orderPlace->place_id]['place_title'] = $orderOperation->orderPlace->place->title;
//                        $order_results_worker['operation_shift'][$order_place->place_id]['operations_shifts'][$order_operation_id]['order_operation_id'] = $order_operation_id;
                    $order_results_worker['operation_shift'][$orderOperation->orderPlace->place_id]['operations_shifts'][$orderOperation->operation_id]['operation_id'] = $orderOperation->operation_id;
                    $order_results_worker['operation_shift'][$orderOperation->orderPlace->place_id]['operations_shifts'][$orderOperation->operation_id]['operation_title'] = $orderOperation->operation->title;
                    $order_results_worker['operation_shift'][$orderOperation->orderPlace->place_id]['operations_shifts'][$orderOperation->operation_id]['operation_short_title'] = $orderOperation->operation->short_title;
                    $order_results_worker['operation_shift'][$orderOperation->orderPlace->place_id]['operations_shifts'][$orderOperation->operation_id]['unit_short_title'] = $orderOperation->operation->unit->short;
                    $order_results_worker['operation_shift'][$orderOperation->orderPlace->place_id]['operations_shifts'][$orderOperation->operation_id]['shifts'][$shift_id]['fact_value'] = $order_results_worker['shifts_operation'][$shift_id . "/" . $chane_id]['shift_place'][$order_place_id]['operations'][$order_operation_id]['fact_value'];
                    $order_results_worker['operation_shift'][$orderOperation->orderPlace->place_id]['operations_shifts'][$orderOperation->operation_id]['shifts'][$shift_id]['plan_value'] = $order_results_worker['shifts_operation'][$shift_id . "/" . $chane_id]['shift_place'][$order_place_id]['operations'][$order_operation_id]['plan_value'];
                    $order_results_worker['operation_shift'][$orderOperation->orderPlace->place_id]['operations_shifts'][$orderOperation->operation_id]['shifts'][$shift_id]['shift_id'] = $shift_id;
                    $order_results_worker['operation_shift'][$orderOperation->orderPlace->place_id]['operations_shifts'][$orderOperation->operation_id]['shifts'][$shift_id]['chane_title'] = $chane_title;
                    $order_results_worker['operation_shift'][$orderOperation->orderPlace->place_id]['operations_shifts'][$orderOperation->operation_id]['shifts'][$shift_id]['numberShift'] = $latin_number[(int)$shift_id];
                    if (isset($order_results_worker['shifts'][$shift_id . "/" . $chane_id]['description'])) {
                        $order_results_worker['operation_shift'][$orderOperation->orderPlace->place_id]['operations_shifts'][$orderOperation->operation_id]['shifts'][$shift_id]['description'] = $order_results_worker['shifts'][$shift_id . "/" . $chane_id]['description'];
                    }

                }
            }

            if (!isset($order_results_worker['shifts'])) {
                $order_results_worker['shifts'] = (object)array();
            }
            if (!isset($order_results_worker['shifts_operation'])) {
                $order_results_worker['shifts_operation'] = (object)array();
            }
            if (!isset($order_results_worker['operation_shift'])) {
                $order_results_worker['operation_shift'] = (object)array();
            }
            $warnings[] = 'GetReportDataByShift. Метод завершил работу';
        } catch (Throwable $exception) {
            $errors[] = "GetReportDataByShift. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $result = $order_results_worker;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetTypeOperations() - метод получяения типов операций для циклограммы из справочника типов операций
     * @param null $data_post
     * @return array
     * @package frontend\controllers\ordersystem
     * @example http://web.amicum/read-manager-amicum?controller=ordersystem\ReportForPreviousPeriod&method=GetTypeOperations&subscribe=worker_list&data=
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 03.07.2019 14:36
     */
    public static function GetTypeOperations($data_post = NULL)
    {
        $status = 1;                                                                                                        // Флаг успешного выполнения метода
        $warnings = array();                                                                                                // Массив предупреждений
        $errors = array();                                                                                                  // Массив ошибок
        $type_operations = array();                                                                                   // Промежуточный результирующий массив
        try {
            $type_operations = TypeOperation::find()
                ->asArray()
                ->indexBy('id')
                ->all();
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result = $type_operations;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetEvents() - метод получения списка событий для циклограммы
     * @param null $data_post - пустой массив
     * @return array                - массив событий
     * @package frontend\controllers\ordersystem
     * @example http://web.amicum/read-manager-amicum?controller=ordersystem\ReportForPreviousPeriod&method=GetEvents&subscribe=worker_list&data=
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 03.07.2019 14:38
     */
    public static function GetEvents($data_post = NULL)
    {
        $status = 1;                                                                                                        // Флаг успешного выполнения метода
        $warnings = array();                                                                                                // Массив предупреждений
        $errors = array();                                                                                                  // Массив ошибок
        $events = array();                                                                                   // Промежуточный результирующий массив
        try {
            $events = Event::find()
                ->asArray()
                ->indexBy('id')
                ->all();
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result = $events;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetBrigadeStatistic() - получает статистику бригады на участке(показатели по сменам и циклограмма)
     * @param null $data_post
     * @return array
     * @package frontend\controllers\ordersystem
     * @example http://web.amicum/read-manager-amicum?controller=ordersystem\ReportForPreviousPeriod&method=GetBrigadeStatistic&subscribe=worker_list&data={%22company_department_id%22:4029831,%22brigade_id%22:397,%22date%22:%2201.07.2019%22,%22shift_id%22:1}
     *              Для полчения данных циклограммы: http://web.amicum/read-manager-amicum?controller=ordersystem\ReportForPreviousPeriod&method=GetBrigadeStatistic&subscribe=worker_list&data={%22company_department_id%22:20004382,%22brigade_id%22:384,%22date%22:%2226.06.2019%22,%22shift_id%22:3}
     *              Корректные данные по показателям бригады: http://web.amicum/read-manager-amicum?controller=ordersystem\ReportForPreviousPeriod&method=GetBrigadeStatistic&subscribe=worker_list&data={%22company_department_id%22:4029831,%22brigade_id%22:397,%22date%22:%2201.06.2019%22}
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 03.07.2019 15:09
     */
    public static function GetBrigadeStatistic($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $brigade_statistic = array();                                                                                   // Промежуточный результирующий массив с показателями бригады
        $cyclogram = (object)array();                                                                                           // Промежуточный результирующий массив с данными циклограммы
        $material_residues = -1;
//        $injunctions = array();                                                                                       // Промежуточный результирующий массив с данными циклограммы
        $needle_injunctions = array();                                                                                  // Промежуточный результирующий массив с данными циклограммы
        $injunction_result = array();                                                                                   // Промежуточный результирующий массив с данными циклограммы
        $result_outgoing_plan = array();                                                                                // Промежуточный результирующий массив с данными циклограммы
        $result_outgoing = array();
        $planogramm = (object)array();
        try {
            if ($data_post !== NULL && $data_post !== '')                                                               // Проверяем передана ли входная JSON строка
            {
                $warnings[] = 'GetBrigadeStatistic. Данные успешно переданы';
                $warnings[] = 'GetBrigadeStatistic. Входной массив данных' . $data_post;
            } else {
                throw new Exception('GetBrigadeStatistic. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetBrigadeStatistic. Декодировал входные параметры';
            if (
                property_exists($post_dec, 'company_department_id') &&
                property_exists($post_dec, 'brigade_id') &&
                property_exists($post_dec, 'mine_id') &&
                property_exists($post_dec, 'date')
            )                                                                                                           // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'GetBrigadeStatistic. Данные с фронта получены';
            } else {
                throw new Exception('GetBrigadeStatistic. Переданы некорректные входные параметры');
            }

            $mine_id = $post_dec->mine_id;                                                                        // Закидываем полученные данные с фронта в переменные
            $brigade_id = $post_dec->brigade_id;                                                                        // Закидываем полученные данные с фронта в переменные
            $date = date('Y-m-d', strtotime($post_dec->date));
            $day = date('d', strtotime($date));
            $month = date('m', strtotime($date));
            $year = date('Y', strtotime($date));
            $company_department_id = $post_dec->company_department_id;

            /******************** Получить все предписания на определённую дату ********************/
            $injunctions = Injunction::find()
                ->select(['injunction.id'])
                ->joinWith('firstInjunctionStatuses')
                ->where(['>=', 'injunction_status.date_time', $date . ' 00:00:00'])
                ->andWhere(['<=', 'injunction_status.date_time', $date . ' 23:59:59'])
                ->andWhere(['injunction.company_department_id' => $company_department_id])
                ->asArray()
                ->all();
            foreach ($injunctions as $injunction) {
                $needle_injunctions[] = $injunction['id'];
            }
            /******************** Перебрать предписания и вывести полную по каждому ********************/
            foreach ($needle_injunctions as $needle_injunction) {
                $json = '{"injunction_id":' . $needle_injunction . '}';
                $info_about_instruction = CheckingController::GetInfoAboutInjunction($json);
                $injunction_result[$needle_injunction] = $info_about_instruction['Items'];
                $errors[] = $info_about_instruction['errors'];
                $warnings[] = $info_about_instruction['warnings'];
            }

            /**
             * Получаем данные циклограммы:
             */
            $ordersObj = Order::find()
                ->where([
                    'order.date_time_create' => $date,
                    'order.mine_id' => $mine_id,
                    'order.company_department_id' => $company_department_id
                ])
                ->all();
            if ($ordersObj) {
                $count_shifts = Assistant::GetCountShifts();
                for ($i = 1; $i <= $count_shifts; $i++) {
                    $order_arr_shift[$i] = -1;
                }
                $warnings[] = $ordersObj;

                foreach ($ordersObj as $order_item) {
                    $order_arr[] = $order_item->id;
                    $order_arr_shift[$order_item->shift_id] = $order_item->id;
                }

                $cyclogram = self::GetCyclogramm($order_arr, $company_department_id, $date);
                if ($cyclogram['status'] === 1) {
                    $warnings = ArrayHelper::merge($warnings, $cyclogram['warnings']);
                    $cyclogram = $cyclogram['Items']['cyclogram'];
                } else {
                    $errors = ArrayHelper::merge($cyclogram['errors'], $errors);
                    throw new Exception('GetBrigadeStatistic. Для данного участка не найден тип участка и тип операций выполняемых на нем');
                }

                /**
                 * Получаем данные планограммы:
                 */
                $json_planogram = json_encode(array("order_arr" => $order_arr, "company_department_id" => $company_department_id, "date" => $date));
                $planogramm_result = self::GetPlanogramma($json_planogram);
                if ($planogramm_result['status'] == 1) {
                    $planogramm = $planogramm_result['Items'];
                    $warnings[] = $planogramm_result['warnings'];
                } else {
                    $warnings[] = $planogramm_result['warnings'];
                    $errors[] = $planogramm_result['errors'];
                }

                /**
                 * Получаем данные по показателям смены
                 */
                $brigade_statistic = self::GetReportDataByShift($order_arr, $company_department_id, $date);
                if ($brigade_statistic['status'] === 1) {
                    $warnings = ArrayHelper::merge($warnings, $brigade_statistic['warnings']);
                    $errors = ArrayHelper::merge($brigade_statistic['errors'], $errors);
                    $brigade_statistic = $brigade_statistic['Items'];
                } else {
                    $warnings = ArrayHelper::merge($warnings, $brigade_statistic['warnings']);
                    $errors = ArrayHelper::merge($brigade_statistic['errors'], $errors);
                    throw new Exception('GetBrigadeStatistic. Для данного участка не найден тип участка и тип операций выполняемых на нем');
                }
//            /******************** ПОЛУЧАЕМ ДАННЫЕ ПРЕДПИСАНИЙ ********************/
//            $json_for_get_inj = '{"brigade_id":' . $brigade_id . ',"company_department_id":' . $company_department_id . ',"date":"' . $date . '"}';//делаем json потому как метод GetInjunctionReport написан через readmanager и на вход требудет json
//            $injunction = self::GetInjunctionReport($json_for_get_inj);
//            if ($injunction['status'] === 1) {
//                $warnings = ArrayHelper::merge($warnings, $injunction['warnings']);
//                $injunctions = $injunction['Items']['injunctions'];
//            }

                /******************** ПОЛУЧАЕМ ДАННЫЕ ВЫХОЖДАЕМОСТИ ПО ФАКТУ ********************/
                for ($i = 1; $i <= $count_shifts; $i++) {
                    $json_for_get_outgoing_shift = json_encode(array(
                        'company_department_id' => $company_department_id,
                        'shift_id' => $i,
                        'mine_id' => $mine_id,
                        'date_time' => $date,
                        'order_id' => $order_arr_shift[$i]
                    ));
                    $out_going_shift = OrderSystemController::GetWorkersAttendanceInShift($json_for_get_outgoing_shift);
                    $result_outgoing[$i] = $out_going_shift['Items'];
                }

                /******************** ПОЛУЧАЕМ ДАННЫЕ ВЫХОЖДАЕМОСТИ ПО ПЛАНУ ********************/
                $count_worker_by_shifts = GraficTabelMain::find()
                    ->select(['grafic_tabel_date_plan.shift_id as grafic_shift_id',
                        'count(grafic_tabel_date_plan.worker_id) as count_worker'])
                    ->leftJoin('grafic_tabel_date_plan', 'grafic_tabel_date_plan.grafic_tabel_main_id = grafic_tabel_main.id')
                    ->where(['grafic_tabel_main.company_department_id' => $company_department_id,
                        'grafic_tabel_main.month' => $month,
                        'grafic_tabel_main.year' => $year,
                        'grafic_tabel_date_plan.day' => $day,
                        'grafic_tabel_date_plan.mine_id' => $mine_id,
                    ])
                    ->groupBy('grafic_shift_id')
                    ->asArray()
                    ->all();
                foreach ($count_worker_by_shifts as $count_worker_by_shift) {
                    $result_outgoing_plan[$count_worker_by_shift['grafic_shift_id']] = $count_worker_by_shift['count_worker'];
                }
                /******************** Конец блока выхождаемости ********************/
            } else {
                $warnings[] = 'GetBrigadeStatistic. Нет выданного наряда на данном участке на выбранную дату';
            }
            $warnings[] = 'GetBrigadeStatistic. Метод завершил работу';
        } catch (Throwable $exception) {
            $errors[] = 'GetBrigadeStatistic.Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result = ['statistic' => $brigade_statistic,
            'cyclogram' => $cyclogram,
            'planogramm' => $planogramm,
            'injunctions' => $injunction_result,
            'material' => $material_residues,
            'outgoing_fact' => $result_outgoing,
            'outgoing_plan' => $result_outgoing_plan];

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * Название метода: SetCoalMiningPlan() - Метод сохранения плана добычи угля бригадой на месяц
     * Метод сохранения плана добычи угля бригадой на месяц
     *
     * @param null $data_post - массив с идентификатором бригады, годом, месяцем и значеним плана
     * @return array                - массив с результатом выполнения запроса
     * @package frontend\controllers\ordersystem
     * @example Добавление нового значения существующему параметру: http://web.amicum/read-manager-amicum?controller=ordersystem\ReportForPreviousPeriod&method=SetCoalMiningPlan&subscribe=login&data={%22brigade_id%22:347,%22year%22:2019,%22month%22:6,%22value%22:23000,%22place_id%22:6187}
     *          Добавление нового параметра и значения: http://web.amicum/read-manager-amicum?controller=ordersystem\ReportForPreviousPeriod&method=SetCoalMiningPlan&subscribe=login&data={%22brigade_id%22:347,%22year%22:2019,%22month%22:8,%22value%22:23000,%22place_id%22:6187}
     *          Данные не переданы: http://web.amicum/read-manager-amicum?controller=ordersystem\ReportForPreviousPeriod&method=SetCoalMiningPlan&subscribe=login&data={}
     *
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 27.06.2019 11:31
     * @since ver
     */
    public static function SetCoalMiningPlan($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        try {
            if ($data_post !== NULL && $data_post !== '')                                                               // Проверяем передана ли входная JSON строка
            {
                $warnings[] = 'SetCoalMiningPlan. Данные успешно переданы';
                $warnings[] = 'SetCoalMiningPlan. Входной массив данных' . $data_post;
            } else {
                throw new Exception('SetCoalMiningPlan. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'SetCoalMiningPlan. Декодировал входные параметры';
            if (
                property_exists($post_dec, 'brigade_id') &&
                property_exists($post_dec, 'year') &&
                property_exists($post_dec, 'month') &&
                property_exists($post_dec, 'value') &&
                property_exists($post_dec, 'place_id') &&
                property_exists($post_dec, 'operation_id')
            )                                                             // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'SetCoalMiningPlan. Данные с фронта получены';
            } else {
                throw new Exception('SetCoalMiningPlan. Переданы некорректные входные параметры');
            }
            $brigade_id = $post_dec->brigade_id;
            $value = $post_dec->value;
            $year = $post_dec->year;
            $month = $post_dec->month;
            $place_id = $post_dec->place_id;
            $count_days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $operation_id = $post_dec->operation_id;

            $day_value = $value / $count_days_in_month;                                                                 // Определяем посуточный план добычи угля

            /******************** ДЛЯ НАЙДЕННЫХ ОПЕРАЦИЙ УСТАНОВИТЬ СТАТУС НЕ АКТУАЛЬНЫЙ ********************/

            $place_operation = PlaceOperation::find()// Получаем конкретную строку-связку бригады/места/операции
            ->where(['place_operation.brigade_id' => $brigade_id, 'place_operation.place_id' => $place_id,
                'place_operation.operation_id' => $operation_id])
                ->limit(1)
                ->one();

            $place_operation_id = -1;
            if ($place_operation)                                                                                       // Если связка найдена, находим значения за данный месяц
            {
                $place_operation_values = PlaceOperationValue::find()
                    ->where(['place_operation_value.place_operation_id' => $place_operation->id, 'place_operation_value.status_id' => self::STATUS_ACTIVE])
                    ->andWhere(['>=', 'place_operation_value.date', date('Y-m-d', strtotime($year . '-' . $month . '-1'))])
                    ->andWhere(['<=', 'place_operation_value.date', date('Y-m-d', strtotime($year . '-' . $month . '-' . $count_days_in_month))])
                    ->all();
                /******************** ИЗМЕНЯЕМ СТАТУС ДЛЯ ПРОШЛЫХ ЗНАЧЕНИЙ ПЛАНА ДОБЫЧИ УГЛЯ ********************/
                $place_operation_id = $place_operation->id;
                foreach ($place_operation_values as $place_operation_value) {
                    $place_operation_value->status_id = self::STATUS_INACTIVE;
                    if (!$place_operation_value->save()) {
                        $errors[] = $place_operation_value->errors;
                        throw new Exception('SetCoalMiningPlan. Возникли ошибки во время изменения статуса значения');
                    }
                }
            } else                                                                                                        // Если такой связки не найдено, добавляем новую
            {
                /******************** ДОБАВЛЯЕМ НОВУЮ СВЯЗКУ ОПЕРАЦИИ/БРИГАДЫ/МЕСТА ********************/

                $new_place_operation = new PlaceOperation();
                $new_place_operation->operation_id = $operation_id;
                $new_place_operation->place_id = $place_id;
                $new_place_operation->brigade_id = $brigade_id;
                if ($new_place_operation->save()) {
                    $new_place_operation->refresh();
                    $place_operation_id = $new_place_operation->id;
                } else {
                    $errors[] = $new_place_operation->errors;
                    throw new Exception('SetCoalMiningPlan. Возникла ошибка во время добавления связки операции/бригады/места');
                }
            }

            /******************** ПОДГОТАВЛИВАЕМ МАССИВ ЗНАЧЕНИЙ ПЛАНА ДОБЫЧИ УГЛЯ ********************/

            $day_values_array = array();
            for ($i = 1; $i <= $count_days_in_month; $i++) {
                $day_values_array[] = [$place_operation_id, $day_value, $year . '-' . $month . '-' . $i, self::STATUS_ACTIVE];
            }

            /******************** ДОБАВЛЯЕМ ПЛАН ДОБЫЧИ УГЛЯ ********************/

            $count_inserted_rows = Yii::$app->db->createCommand()->batchInsert('place_operation_value',
                ['place_operation_id', 'value', 'date', 'status_id'],
                $day_values_array)->execute();
            if ($count_inserted_rows === 0) {
                throw new Exception('SetCoalMiningPlan. Не добавлено ни одного значения');
            }
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result = NULL;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Название метода: GetCoalMiningPlan()
     * Метод получения плана добычи угля бригадой на месяц
     *
     * @param $brigade_id - идентификатор бригады
     * @param $year - год
     * @param $month - месяц
     * @param $place_id - идентификатор места
     * @param $count_days_in_month - количество дней в месяце
     * @param $operation_type_id - тип операции
     *
     * @return array                - массив с результатом выполнения запроса
     * @package frontend\controllers\ordersystem
     * @example Передаются корректные параметры: http://web.amicum/read-manager-amicum?controller=ordersystem\ReportForPreviousPeriod&method=GetCoalMiningPlan&subscribe=login&data={"brigade_id":347,"year":2019,"month":6,"place_id":6187}
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 27.06.2019 11:45
     * @since ver
     */
    public static function GetCoalMiningPlan($brigade_id, $year, $month, $count_days_in_month, $operation_type_id)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $coal_mining_plan = array();                                                                                    // Промежуточный результирующий массив
        try {
            $warnings[] = 'GetCoalMiningPlan. Получение плана добычи угля';
            $coal_mining_plan = PlaceOperation::find()
                ->select(['place_operation.id AS place_operation_id', 'place_operation_value.id AS place_operation_value_id', 'place_operation_value.value',
                    "DATE_FORMAT(date, '%e') AS day"])
                ->innerJoin('place_operation_value', 'place_operation.id = place_operation_value.place_operation_id')
                ->innerJoin('operation', 'operation.id = place_operation.operation_id')
                ->where(['place_operation.brigade_id' => $brigade_id,
                    'operation.operation_type_id' => $operation_type_id,
                    'place_operation_value.status_id' => self::STATUS_ACTIVE])
                ->andWhere(['>=', 'date', date('Y-m-d', strtotime($year . '-' . $month . '-1'))])
                ->andWhere(['<=', 'date', date('Y-m-d', strtotime($year . '-' . $month . '-' . $count_days_in_month))])
                ->indexBy('day')
                ->asArray()
                ->all();
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result = $coal_mining_plan;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetDepartmentOperationType()   - получение типа участка и типа операций которые выполняются на участке по идентификатору участка
     * @param $company_department_id - Идентификатор структурного подразделения
     * @return array                        - Массив с данными типа операции, типа участка
     */
    public static function GetDepartmentOperation($company_department_id)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $operation_ids = NULL;
        $department_type_id = NULL;

        $warnings[] = 'GetDepartmentOperationType. Зашел в метод';
        /**
         * Идентификаторы типов участков:
         * 1    Очистной участок
         * 2    Подготовительный участок
         * 3    Вспомогательный участок
         * 4    Участок транспорта
         * 5    Прочее
         */
        $company_department_type = CompanyDepartment::find()
            ->select(['company_department.department_type_id'])
            ->where(['company_department.id' => $company_department_id])
            ->asArray()
            ->limit(1)
            ->one();

        /**
         * Определяем какой основной тип операций выполняется на участке
         */
        // TODO: Может быть несколько типов операций для типа участка?
        if ($company_department_type) {
            $department_type_id = $company_department_type['department_type_id'];
            $warnings[] = 'GetDepartmentOperationType. Данные по типу участка получены, определяем тип операций';
            switch ($department_type_id) {
                case 2:                             // подготовительный участок
                    $operation_ids[] = 1;               // Проведение и крепление выработки по паспорту, пог. м
                    $operation_ids[] = 2;               // Проведение и крепление выработки по паспорту, циклы
                    $operation_ids[] = 3;               // Подготовка к проведению выработки, часы
                    $operation_ids[] = 116;             // Выемка горной массы, проведение выработки комбайном, пог. м
                    $operation_ids[] = 198;             // Выемка горной массы, проведение выработки комбайном, пог. м
//                    $operation_ids[] = 164758;          // Выемка горной массы, проведение выработки комбайном 1ГПКС, пог. м
                    $operation_ids[] = 140;             // Выемка угля комбайном, тонн
                    break;
                case 1:                             // очистной участок
                    $operation_ids[] = 6;               // Подготовка к выемке угля, часы
                    $operation_ids[] = 7;               // Выемка угля, согласно паспорту, тонн
                    $operation_ids[] = 140;             // Выемка угля комбайном, тонн
                    $operation_ids[] = 340;             // Выемка угля комбайном, тонн
                    $operation_ids[] = 349;             // Выемка угля, согласно паспорту, цикл
                    $operation_ids[] = 317;             // Зачистка ленточного конвейера от просыпей
                    break;
                case 3:                             // вспомогательный участок
                    $operation_ids[] = 5;               // Транспортировка горной массы, часы
                    $operation_ids[] = 350;             // Работа без простоев, в часах
                    break;
                case 4:                             // участок транспорта
                    $operation_ids[] = 5;               // Транспортировка горной массы, часы
                    $operation_ids[] = 350;             // Работа без простоев, в часах
                    break;
                case 5:                             // прочие
                    $operation_ids[] = 350;             // Работа без простоев, в часах
                    break;
            }
        } else {
            $status = 0;
            $errors[] = 'GetDepartmentOperationType. Данных по заданному условию не найдено, company_department_id: ' . $company_department_id;
        }
        return ['operation_ids' => $operation_ids, 'department_type_id' => $department_type_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Метод GetDepartmentMainOperation()   - получение 1 главной операции для участка
     * @param $company_department_id - Идентификатор структурного подразделения
     */
    public static function GetDepartmentMainOperation($company_department_id)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $operation_id = NULL;
        $department_type_id = NULL;

        $warnings[] = 'GetDepartmentOperationType. Зашел в метод';
        $company_department_type = CompanyDepartment::find()
            ->select(['company_department.department_type_id'])
            ->where(['company_department.id' => $company_department_id])
            ->asArray()
            ->limit(1)
            ->one();
        /**
         * Идентификаторы типов участков:
         * 3    Вспомогательный участок         - 10 Вспомогательные работы
         * 1    Очистной участок                - 9 Очистные работы
         * 2    Подготовительный участок        - 6 Обслуживание оборудования
         * 5    Прочее                          - 11 Проведение горных выработок
         * 4    Участок транспорта              - 7 Доставка
         */

        /**
         * Определяем какой основной тип операций выполняется на участке
         */
        if ($company_department_type) {
            $department_type_id = $company_department_type['department_type_id'];
            $warnings[] = 'GetDepartmentOperationType. Данные по типу участка получены, определяем тип операций';
            switch ($department_type_id) {
                case 2:                             // подготовительный участок
                    $operation_id[] = 1;                // Проведение и крепление выработки по паспорту, пог. м
                    $operation_id[] = 198;              // Выемка горной массы, проведение выработки комбайном
                    break;
                case 1:                             // очистной участок
                    $operation_id[] = 340;              // Выемка угля комбайном
                    $operation_id[] = 140;              // Выемка угля комбайном
                    $operation_id[] = 7;                // Выемка угля, согласно паспорта, тонн
                    break;
                case 3:                             // вспомогательный участок
                    $operation_id = 5;                  // Транспортировка горной массы, часы
                    break;
                case 4:                             // участок транспорта
                    $operation_id = 5;                  // Транспортировка горной массы, часы
                    break;
                case 5:                             // прочие
                    $operation_id = 7;                  // Выемка угля, согласно паспорта, тонн
                    break;
            }
        } else {
            $status = 0;
            $errors[] = 'GetDepartmentOperationType. Данных по заданному условию не найдено, company_department_id: ' . $company_department_id;
        }
        return ['operation_id' => $operation_id, 'department_type_id' => $department_type_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Название метода: GetCoalMiningReportByMonth() - Метод получения плана добычи угля бригадой на месяц
     * Метод получения плана добычи угля бригадой на месяц
     *
     * @param null $data_post - массив с идентификатором бригады, годом, месяцем и значеним плана
     * @return array                - массив с результатом выполнения запроса
     * @package frontend\controllers\ordersystem
     * @example Переданы правильные параметры, с существующими значениями: http://web.amicum/read-manager-amicum?controller=ordersystem\ReportForPreviousPeriod&method=GetCoalMiningReportByMonth&subscribe=login&data={%22brigade_id%22:397,%22report_year%22:2019,%22report_month%22:7,%22place_id%22:6187,%22company_department_id%22:4029831}
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 27.06.2019 11:45
     * @since ver
     */
    public static function GetCoalMiningReportByMonth($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $coal_mining_fact = array();                                                                                    // Промежуточный результирующий массив
        $coal_mining_plan = array();                                                                                    // Промежуточный результирующий массив
        $operation_id = -1;                                                                                             // главная операция для данного участка

        try {
            if ($data_post !== NULL && $data_post !== '')                                                               // Проверяем передана ли входная JSON строка
            {
                $warnings[] = 'GetCoalMiningReportByMonth. Данные успешно переданы';
                $warnings[] = 'GetCoalMiningReportByMonth. Входной массив данных' . $data_post;
            } else {
                throw new Exception('GetCoalMiningReportByMonth. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetCoalMiningReportByMonth. Декодировал входные параметры';
            if (
                property_exists($post_dec, 'company_department_id') &&
                property_exists($post_dec, 'brigade_id') &&
                property_exists($post_dec, 'mine_id') &&
                property_exists($post_dec, 'report_year') &&
                property_exists($post_dec, 'report_month')
            )                                                                                                           // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'GetCoalMiningReportByMonth. Данные с фронта получены';
            } else {
                throw new Exception('GetCoalMiningReportByMonth. Переданы некорректные входные параметры');
            }

            $warnings[] = 'GetCoalMiningReportByMonth. Начало выполнения метода';

            $company_department_id = $post_dec->company_department_id;
            $brigade_id = $post_dec->brigade_id;
            $mine_id = $post_dec->mine_id;
            $report_year = $post_dec->report_year;
            $report_month = $post_dec->report_month;
            $count_days_month = cal_days_in_month(CAL_GREGORIAN, $report_month, $report_year);
            $operation_ids = -1;

            $company_department_operation_type = self::GetDepartmentMainOperation($company_department_id);
            if ($company_department_operation_type['status'] === 1) {
                $warnings = ArrayHelper::merge($company_department_operation_type['warnings'], $warnings);
                $operation_id = $company_department_operation_type['operation_id'];
            } else {
                $errors = ArrayHelper::merge($company_department_operation_type['errors'], $errors);
                throw new Exception('GetCoalMiningReportByMonth. Для данного участка не найден тип участка и тип операций выполняемых на нем');
            }
            $unit_obj = Operation::find()
                ->joinWith('unit')
                ->where(['operation.id' => $operation_id])
                ->limit(1)
                ->one();

            $warnings[] = $unit_obj;
            if ($unit_obj) {
                $unit['unit_id'] = $unit_obj->unit->id;
                $unit['unit_title'] = $unit_obj->unit->title;
                $unit['unit_short'] = $unit_obj->unit->short;
            } else {
                $unit['unit_id'] = null;
                $unit['unit_title'] = "-";
                $unit['unit_short'] = "-";
            }

            /**
             * Выбираем данные по операциям, группируем их по типам и самим операциям
             */
            $order_operation_worker = OrderOperation::find()
                ->select('order.date_time_create, order.shift_id, operation.operation_type_id, order_operation.operation_id, order_operation.operation_value_fact, order_operation.operation_value_plan')
                ->innerJoin('order_place', 'order_place.id = order_operation.order_place_id')
                ->innerJoin('order', 'order_place.order_id = order.id')
                ->innerJoin('operation', 'operation.id = order_operation.operation_id')
                ->where(['order.company_department_id' => $company_department_id])
                ->andWhere(['order.mine_id' => $mine_id])
                ->andWhere(['order_operation.operation_id' => $operation_id])
                ->andWhere(['>=', 'order.date_time_create', $report_year . '-' . $report_month . '-1'])
                ->andWhere(['<=', 'order.date_time_create', $report_year . '-' . $report_month . '-' . $count_days_month])
                ->asArray()
                ->all();

            $warnings[] = "GetCoalMiningReportByMonth. Найденные операции";
            $warnings[] = $order_operation_worker;
            /**
             * Подсчитываем количественные показатели добычи угля по факту из операций
             */
            foreach ($order_operation_worker as $order_operation)                                                       // Суммируем данные факта по сменам и дням
            {

                $day = date('j', strtotime($order_operation['date_time_create']));
                if (isset($coal_mining_fact[$day]['shifts'][$order_operation['shift_id']]['fact_value'])) {
                    if ($order_operation['operation_value_fact']) {
                        $coal_mining_fact[$day]['shifts'][$order_operation['shift_id']]['fact_value'] += (float)$order_operation['operation_value_fact'];
                    }
                } else {
                    if (!$order_operation['operation_value_fact']) {
                        $coal_mining_fact[$day]['shifts'][$order_operation['shift_id']]['fact_value'] = 0;
                    } else {
                        $coal_mining_fact[$day]['shifts'][$order_operation['shift_id']]['fact_value'] = (float)$order_operation['operation_value_fact'];
                    }
                    $coal_mining_fact[$day]['day'] = $day;
                    $coal_mining_fact[$day]['shifts'][$order_operation['shift_id']]['shift_id'] = $order_operation['shift_id'];
                }

                if (!isset($coal_mining_plan[$day])) {
                    $coal_mining_plan[$day]['day'] = $day;
                    if (!$order_operation['operation_value_plan']) {
                        $coal_mining_plan[$day]['value'] = 0;
                    } else {
                        $coal_mining_plan[$day]['value'] = (float)$order_operation['operation_value_plan'];
                    }
                } else {
                    if ($order_operation['operation_value_plan']) {
                        $coal_mining_plan[$day]['value'] += (float)$order_operation['operation_value_plan'];
                    }
                }
            }

//            $method_result = self::GetCoalMiningPlan($brigade_id, $report_year, $report_month, $count_days_month, $operation_id);
//            if ($method_result['status'] === 1) {
//                $warnings = ArrayHelper::merge($method_result['warnings'], $warnings);
//                $coal_mining_plan = $method_result['Items'];
//            } else {
//                $errors = ArrayHelper::merge($method_result['errors'], $errors);
//                throw new \Exception('GetCoalMiningReportByMonth. Для данного участка не найден тип участка и тип операций выполняемых на нем');
//            }
            $warnings[] = 'GetCoalMiningReportByMonth. Получение плана добычи угля на месяц';
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result = ['coal_mining_plan' => $coal_mining_plan, 'coal_mining_fact' => $coal_mining_fact, 'operation_type_id' => $operation_id, 'unit' => $unit];
        return $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Название метода: GetMaterial() - Метод получения остатков материалов
     * Метод получения остатков материалов
     *
     * @param null $data_post - массив с идентификатором бригады, годом, месяцем и значеним плана
     * @return array                - массив с результатом выполнения запроса
     * @package frontend\controllers\ordersystem
     * @example http://web.amicum/read-manager-amicum?controller=ordersystem\ReportForPreviousPeriod&method=GetCoalMiningReportByMonth&subscribe=login&data={"brigade_id":348,"report_year":2019,"report_month":6}
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 27.06.2019 11:45
     * @since ver
     */
    public static function GetMaterial($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $coal_mining_plan = array();                                                                                    // Промежуточный результирующий массив
        try {
            if ($data_post !== NULL && $data_post !== '')                                                               // Проверяем передана ли входная JSON строка
            {
                $warnings[] = 'GetCoalMiningReportByMonth. Данные успешно переданы';
                $warnings[] = 'GetCoalMiningReportByMonth. Входной массив данных' . $data_post;
            } else {
                throw new Exception('GetCoalMiningReportByMonth. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetCoalMiningReportByMonth. Декодировал входные параметры';
            if (
                property_exists($post_dec, 'brigade_id') &&
                property_exists($post_dec, 'report_year') &&
                property_exists($post_dec, 'report_month')
            )                                                                                                           // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'GetCoalMiningReportByMonth. Данные с фронта получены';
            } else {
                throw new Exception('GetCoalMiningReportByMonth. Переданы некорректные входные параметры');
            }

            /************************************************* ТЕЛО МЕТОДА *************************************************/
            $warnings[] = 'GetCoalMiningReportByMonth. Начало выполнения метода';

        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result = $coal_mining_plan;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод SaveReportCyclogram()  - сохранение циклограммы через ReadManager
     * @param null $data_post - данные по операциям циклограмммы, бригады, наряда, смены, даты, участка
     * @return array                - стандартный выходной массив с предупреждениями ошибками и статусом выполнения метода
     * @package frontend\controllers\ordersystem
     * @example
     *          Корректные данные: http://web.amicum/read-manager-amicum?controller=ordersystem\ReportForPreviousPeriod&method=SaveReportCyclogram&subscribe=worker_list&data={%22shift_id%22:1,%22date%22:%222019-06-27%22,%22company_department_id%22:20004382,%22brigade_id%22:384,%22order_id%22:44,%22cyclogram_option%22:{%22date_time_start%22:%222019-06-27%2008:00:00%22,%22date_time_end%22:%222019-06-27%2014:00:00%22,%22max_section%22:170},%22cyclograms_operations%22:[{%22date_time_start%22:%222019-06-27%2007:20:00%22,%22date_time_end%22:%222019-06-27%2008:20:00%22,%22type_operation_id%22:3,%22section_start%22:40,%22section_end%22:40,%22stop_face%22:{%22description%22:%20%22%D0%94%D0%BB%D1%8F%20%D1%80%D0%B5%D0%BC%D0%BE%D0%BD%D1%82%D0%B0%20%D1%82%D1%80%D0%B0%D0%BA%D0%BE%D0%B2%D0%BE%D0%B9%20%D1%86%D0%B5%D0%BF%D0%B8%20%D0%B2%D1%8B%D0%B5%D1%85%D0%B0%D0%BB%D0%B8%20%D0%BA%D0%BE%D0%BC%D0%B1%D0%B0%D0%B9%D0%BD%D0%BE%D0%BC%20%D0%B2%D0%BD%D0%B8%D0%B7%22,%22title%22:%22Простой циклограммы 1%22,%22event_id%22:7135,%22date_time_start%22:%222019-06-27%2022:42:00%22,%22date_time_end%22:%222019-06-27%2023:05:00%22,%22stop_face_id%22:2}},{%22date_time_start%22:%222019-06-27%2008:20:00%22,%22date_time_end%22:%222019-06-27%2009:05:00%22,%22type_operation_id%22:1,%22section_start%22:40,%22section_end%22:0},{%22date_time_start%22:%222019-06-27%2009:05:00%22,%22date_time_end%22:%222019-06-27%2009:40:00%22,%22type_operation_id%22:4,%22section_start%22:0,%22section_end%22:0},{%22date_time_start%22:%222019-06-27%2009:40:00%22,%22date_time_end%22:%222019-06-27%2010:00:00%22,%22type_operation_id%22:2,%22section_start%22:0,%22section_end%22:30},{%22date_time_start%22:%222019-06-27%2010:00:00%22,%22date_time_end%22:%222019-06-27%2013:53:00%22,%22type_operation_id%22:7,%22section_start%22:30,%22section_end%22:30},{%22date_time_start%22:%222019-06-27%2013:53:00%22,%22date_time_end%22:%222019-06-26%2014:11:00%22,%22type_operation_id%22:3,%22section_start%22:30,%22section_end%22:30}]}
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 10.07.2019 14:24
     */
    public static function SaveReportCyclogram($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $cyclogram_test = array();                                                                                      // Промежуточный результирующий массив
        try {
            if ($data_post !== NULL && $data_post !== '')                                                               // Проверяем передана ли входная JSON строка
            {
                $warnings[] = 'SaveReportCyclogram. Данные успешно переданы';
                $warnings[] = 'SaveReportCyclogram. Входной массив данных' . $data_post;
            } else {
                throw new Exception('SaveReportCyclogram. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'SaveReportCyclogram. Декодировал входные параметры';
            if (
                property_exists($post_dec, 'cyclogram_option') &&
                property_exists($post_dec, 'cyclograms_operations') &&
                property_exists($post_dec, 'order_id') &&
                property_exists($post_dec, 'chane_id') &&
                property_exists($post_dec, 'company_department_id') &&
                property_exists($post_dec, 'date') &&
                property_exists($post_dec, 'shift_id')
            )                                                                                                           // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'SaveReportCyclogram. Данные с фронта получены';
            } else {
                throw new Exception('SaveReportCyclogram. Переданы некорректные входные параметры');
            }


            $order_id = $post_dec->order_id;
            $cyclogram_option = $post_dec->cyclogram_option;
            $cyclograms_operations = $post_dec->cyclograms_operations;
            $chane_id = $post_dec->chane_id;
            $company_department_id = $post_dec->company_department_id;
            $date = $post_dec->date;
            $shift_id = $post_dec->shift_id;
            /******************** ПРОВЕРЯЕМ ЕСТЬ ЛИ В БАЗЕ ЦИКЛОГРАММА ********************/
            $cyclogram = Cyclegramm::find()// Получаем данные циклограммы если она имеется в БД
            ->where(
                [
                    'order_id' => $order_id,
                    'chane_id' => $chane_id,
                    'cyclegramm_type_id' => $cyclogram_option->cyclegramm_type_id
                ])
                ->limit(1)
                ->one();
            if ($cyclogram)                                                                                              // Если циклограмма существует, удаляем её
            {
                $warnings[] = 'SaveReportCyclogram. Удаляется существующая циклограмма';
                $cyclogram->delete();
            } else {
                $warnings[] = 'SaveReportCyclogram. Циклограммы ранее не создавались';
            }
            $operation_result = self::SaveCyclogram($cyclogram_option, $cyclograms_operations, $order_id, $chane_id, $company_department_id, $date, $shift_id);            // Вызываем метод сохранения циклограммы
            if ($operation_result['status'] === 1)                                                                      // Проверяем результат выполнения метода сохранения циклограммы
            {
                $warnings[] = $operation_result['warnings'];
                $warnings[] = 'SaveReportCyclogram. Данные циклограммы успешно сохранены';
            } else {
                $errors[] = $operation_result['errors'];
                throw new Exception('SaveReportCyclogram. Во время выполнения метода возникли ошибки');
            }
        } catch (Throwable $exception) {
            $errors[] = 'SaveReportCyclogram. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result = $cyclogram_test;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SaveCyclogram() - сохранение циклограммы
     * @param $cyclogram_option - информация о циклограмме на смену
     * @param $cyclograms_sections - операции циклограммы
     * @param $order_id - идентификатор наряда
     * @param $chane_id - идентификатор звена
     * @param $company_department_id - идентификатор участка
     * @param $date - дата формирования циклограммы
     * @param $shift_id - идентификатор смены
     * @return array                    - стандартный выходной массив с предупреждениями ошибками и статусом выполнения метода
     * @package frontend\controllers\ordersystem
     * Пример входных данных:
     * {
     *              {
     *                  "order_id":123,
     *                  "chane_id":123,
     *                  "company_department_id":123,
     *                  "shift_id":123,
     *                  "date":"2020-02-20",
     *                  "cyclogram_option": {
     *                  "max_section": "100",
     *                  "date_time_start": "2020-02-20 08:00:00",
     *                  "date_time_end": "2020-02-20 14:00:00",
     *                  "type_operation_id": "1",
     *                  "section_start": 0,
     *                  "section_end": 0,
     *                  "cyclegramm_type_id": 2,
     *                  "equipment_id": 139966   //
     *                  "equipment_title": "sdsdf"   //
     *              },
     *              "cyclograms_operations": [{
     *                  "type_operation_id": "1",
     *                  "date_time_start": "2020-02-20 08:00:00",
     *                  "date_time_end": "2020-02-20 11:11:00",
     *                  "section_start": 0,
     *                  "section_end": 20
     *              }, {
     *                  "type_operation_id": "1",
     *                  "date_time_start": "2020-02-20 11:12:00",
     *                  "date_time_end": "2020-02-20 14:00:00",
     *                  "section_start": 20,
     *                  "section_end": 0
     *              }, {
     *                  "type_operation_id": "8",
     *                  "date_time_start": "2020-02-20 11:11:00",
     *                  "date_time_end": "2020-02-20 11:12:00",
     *                  "section_start": 20,
     *                  "section_end": 20,
     *                  "events": [{
     *                  "event_id": 5
     *                  }, {
     *                  "event_id": 10
     *                  }
     *                  ],
     *                  "place_id": "6183",
     *                  "place_title": ";kmhk",
     *                  "kind_stop_pb_id": "4",
     *                  "date_time_start": "2020-02-20 11:11:00",
     *                  "date_time_end": "2020-02-20 11:12:00",
     *                  "description": ""
     *
     *              }
     *          ]
     * }
     *
     * ПАРАМЕТРЫ ВЫХОДНЫХ ДАННЫХ:
     * (стандартный массив выходных данных)
     *
     * АЛГОРИТМ РАБОТЫ:
     * 1. Найти следующую и предидущую смены
     * 2. Ищем циклограмму на предидущую смену
     *      найдено?        Сформировать пересменок
     *      не найдено?     Пропустить
     * 3. Ищем циклограму на следующую смену
     *      найдено?        Сформировать пересменок
     *      не найдено?     Пропустить
     * 4. Сохраняем данные циклограммы
     * 5. Перебор операций циклограммы
     *      5.1 Тип операции не 8 (Простой)?
     *                      да?     Добавляем запись в stop_pb
     *                              Перебираем массив events формируем массив связку остановки и причины
     *                              Добавляем связку оборудования и приостановки
     *                      нет?    Добавляем в массив простои по идентификатору оборудования
     * 6. Конец перебора
     * 7. Массив на добавление операций циклограммы пуст?
     *      да?             Пропускаем
     *      нет?            Массово сохранить операции циклограммы
     * 8. Массив на добавление простоев пуст?
     *      да?             Пропускаем
     *      нет?            Массово сохранить простои
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 10.07.2019 14:49
     */
    public static function SaveCyclogram($cyclogram_option, $cyclograms_sections, $order_id, $chane_id, $company_department_id, $date, $shift_id)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $stop_pb_events = array();
        $equipment_id = null;
        $method_name = 'SaveCyclogram';
        $session = Yii::$app->session;
        try {
            $cyclogram_option = ArrayHelper::toArray($cyclogram_option);
            $equipment_id = $cyclogram_option['equipment_id'];
            $warnings[] = $method_name . '. Начало выполнения метода';

            /******************** ДОБАВЛЯЕМ ДАННЫЕ ЦИКЛОГРАММЫ ********************/

            $warnings[] = $method_name . '. Добавление циклограммы';
            $new_cyclogram = new Cyclegramm();
            $new_cyclogram->order_id = $order_id;
            $new_cyclogram->date_time_start = date('Y-m-d H:i:s', strtotime($cyclogram_option['date_time_start']));
            $new_cyclogram->date_time_end = date('Y-m-d H:i:s', strtotime($cyclogram_option['date_time_end']));
            $new_cyclogram->cyclegramm_type_id = $cyclogram_option['cyclegramm_type_id'];
            $new_cyclogram->section_start = $cyclogram_option['section_start'];
            $new_cyclogram->section_end = $cyclogram_option['section_end'];
            $new_cyclogram->max_section = $cyclogram_option['max_section'];
            $new_cyclogram->chane_id = $chane_id;
            $new_cyclogram->equipment_id = $cyclogram_option['equipment_id'];
//            $new_cyclogram->attributes = $cyclogram_option;
            if (!$new_cyclogram->save()) {
                $errors[] = $new_cyclogram->errors;
                throw new Exception($method_name . '. Возникла ошибка при сохранении циклограммы');
            }
            $new_cyclogram->refresh();
            $new_cyclogram_id = $new_cyclogram->id;
            unset($new_cyclogram);
            $cyclogram_operations = array();

            $warnings[] = $method_name . '. Формируется массив операций циклограммы';
            foreach ($cyclograms_sections as $cyclograms_section) {
                $stop_pb_id = NULL;
                if ($cyclograms_section->type_operation_id == 8)                                       // Если передана информация по простою
                {
                    /******************** ДОБАВЛЯЕМ ДАННЫЕ ПРОСТОЯ ********************/
                    $new_stop_pb = StopPb::findOne(['id' => $cyclograms_section->stop_pb_id]);
                    if (empty($new_stop_pb)) {
                        $new_stop_pb = new StopPb();
                    } else {
                        $del_events = StopPbEvent::deleteAll(['stop_pb_id' => $cyclograms_section->stop_pb_id]);
                        $del_quipments = StopPbEquipment::deleteAll(['stop_pb_id' => $cyclograms_section->stop_pb_id]);
                    }
                    $new_stop_pb->kind_stop_pb_id = $cyclograms_section->kind_stop_pb_id;
                    $new_stop_pb->place_id = $cyclograms_section->place_id;
                    $new_stop_pb->date_time_start = date('Y-m-d H:i:s', strtotime($cyclograms_section->date_time_start));
                    $new_stop_pb->date_time_end = date('Y-m-d H:i:s', strtotime($cyclograms_section->date_time_end));
                    $new_stop_pb->worker_id = $session['worker_id'];
                    $new_stop_pb->description = $cyclograms_section->description;
                    $new_stop_pb->type_operation_id = $cyclograms_section->type_operation_id;
                    $new_stop_pb->xyz = '0.0,0.0,0.0';//TODO 27.02.2020 rudov: в будущем переделать когда будет связка мест и координат мест
                    $new_stop_pb->company_department_id = $company_department_id;
                    $new_stop_pb->section = $cyclograms_section->section_start;
                    if (!$new_stop_pb->save()) {
                        $errors[] = $new_stop_pb->errors;
                        throw new Exception($method_name . '. Ошибка при сохранении простоя');
                    }
                    $new_stop_pb->refresh();
                    $stop_pb_id = $new_stop_pb->id;
                    unset($new_stop_pb);
                    /******************** ДОБАВЛЯЕМ ДАННЫЕ СВЯЗКИ ПРОСТОЯ И ОБОРУДОВАНИЯ ********************/
                    $new_stop_equipment = new StopPbEquipment();
                    $new_stop_equipment->stop_pb_id = $stop_pb_id;
                    $new_stop_equipment->equipment_id = $equipment_id;
                    if (!$new_stop_equipment->save()) {
                        $errors[] = $new_stop_equipment->errors;
                        throw new Exception($method_name . '. Ошибка при сохранении связки простоя и оборудования');
                    }
                    if (isset($cyclograms_section->events) && !empty($cyclograms_section->events)) {
                        foreach ($cyclograms_section->events as $event) {
                            $stop_pb_events[] = [$stop_pb_id, $event->event_id];
                        }
                    }
                } else {
                    /******************** ФОРМИРУЕМ МАССИВ ОПЕРАЦИЙ ДЛЯ МНОЖЕСТВЕННОГО ДОБАВЛЕНИЯ ********************/
                    $cyclogram_operations[] = [$cyclograms_section->date_time_end, $cyclograms_section->date_time_start,
                        $cyclograms_section->type_operation_id, $cyclograms_section->section_start,
                        $cyclograms_section->section_end, $new_cyclogram_id];
                }

            }
            $warnings[] = $method_name . '. Добавление операций циклограммы';

            /******************** ДОБАВЛЯЕМ ОПЕРАЦИИ ЦИКЛОГРАММЫ ********************/

            if (isset($cyclogram_operations) && !empty($cyclogram_operations)) {
                $count_rows = Yii::$app->db->createCommand()->batchInsert('cyclegramm_operation',
                    ['date_time_end',
                        'date_time_start',
                        'type_operation_id',
                        'section_start',
                        'section_end',
                        'cyclegramm_id'], $cyclogram_operations)->execute();
                if ($count_rows == 0) {
                    throw new Exception($method_name . '. Ошибка при сохранении операциий циклограммы');
                }
            }

            /******************** ДОБАВЛЯЕМ СВЯЗКУ ПРОСТОЕВ И ПРИЧИН ********************/
            if (isset($stop_pb_events) && !empty($stop_pb_events)) {
                $inserted_stop_pb_events = Yii::$app->db->createCommand()->batchInsert('stop_pb_event',
                    ['stop_pb_id',
                        'event_id'], $stop_pb_events)->execute();
                if ($inserted_stop_pb_events == 0) {
                    throw new Exception($method_name . '. Ошика при сохранении связки простоев и причин');
                }
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SaveStopFace() - метод сохранения данных простоя
     * @param null $data_post
     * @return array
     * @package frontend\controllers\ordersystem
     * @example
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 09.07.2019 10:09
     */
    public static function SaveStopFace($data_post = NULL)
    {
        $status = 1;                                                                                                        // Флаг успешного выполнения метода
        $warnings = array();                                                                                                // Массив предупреждений
        $errors = array();                                                                                                  // Массив ошибок
        $new_stop_face = array();                                                                                   // Промежуточный результирующий массив
        try {
            if ($data_post !== NULL && $data_post !== '')                                                                   // Проверяем передана ли входная JSON строка
            {
                $warnings[] = 'SaveStopFace. Данные успешно переданы';
                $warnings[] = 'SaveStopFace. Входной массив данных' . $data_post;
            } else {
                throw new Exception('SaveStopFace. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                            // Декодируем входной массив данных
            $warnings[] = 'SaveStopFace. Декодировал входные параметры';
            if (
                property_exists($post_dec, 'brigade_id') &&
                property_exists($post_dec, 'company_department_id') &&
                property_exists($post_dec, 'date')
            )                                                             // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'SaveStopFace. Данные с фронта получены';
            } else {
                throw new Exception('SaveStopFace. Переданы некорректные входные параметры');
            }

            /************************************************* ТЕЛО МЕТОДА *************************************************/
            $brigade_id = $post_dec->brigade_id;
            $company_department_id = $post_dec->company_department_id;
            $date = $post_dec->date;


        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result = $new_stop_face;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод GetInjunctionReport()  - получение предписаний по сменам
     * @param null $data_post - входная JSON строка с данными: идентификатор бригады, идентификатор участка, дата, идентификатор смены
     * @return array                - список предписаний, нарушений сгруппированных по сменам
     * @package frontend\controllers\ordersystem
     * @example Проверка с корректными данными: http://web.amicum/read-manager-amicum?controller=ordersystem\ReportForPreviousPeriod&method=GetInjunctionReport&subscribe=worker_list&data={%22brigade_id%22:397,%22company_department_id%22:4029831,%22date%22:%222019-06-15%22}
     *          Наряд есть по данным условиям, но предписаний нет: http://web.amicum/read-manager-amicum?controller=ordersystem\ReportForPreviousPeriod&method=GetInjunctionReport&subscribe=worker_list&data={%22brigade_id%22:397,%22company_department_id%22:4029831,%22date%22:%222019-06-16%22}
     *          Еще один вариант, бригада работала на многих местах но предписание выдано только на одно: http://web.amicum/read-manager-amicum?controller=ordersystem\ReportForPreviousPeriod&method=GetInjunctionReport&subscribe=worker_list&data={%22brigade_id%22:397,%22company_department_id%22:4029831,%22date%22:%222019-06-16%22}
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 09.07.2019 15:32
     */
    public static function GetInjunctionReport($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $shift_injunctions = array();                                                                                   // Промежуточный результирующий массив
        try {
            if ($data_post !== NULL && $data_post !== '')                                                               // Проверяем передана ли входная JSON строка
            {
                $warnings[] = 'GetInjunctionReport. Данные успешно переданы';
                $warnings[] = 'GetInjunctionReport. Входной массив данных' . $data_post;
            } else {
                throw new Exception('GetInjunctionReport. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetInjunctionReport. Декодировал входные параметры';
            if (
                property_exists($post_dec, 'brigade_id') &&
                property_exists($post_dec, 'company_department_id') &&
                property_exists($post_dec, 'date')
            )                                                             // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'GetInjunctionReport. Данные с фронта получены';
            } else {
                throw new Exception('GetInjunctionReport. Переданы некорректные входные параметры');
            }
            $company_department_id = $post_dec->company_department_id;
            $brigade_id = $post_dec->brigade_id;
            $date = $post_dec->date;
            $warnings[] = 'GetInjunctionReport. Получаем предписания';
            $order_injunctions = Order::find()// Получаем данные по предписаниям за конкретную дату, бригаду, участок
            ->select(['injunction.id AS injunction_id', 'checking.date_time_end AS injunction_date_time_start',
                'status.title AS status_title', 'employee.first_name', 'employee.last_name', 'employee.patronymic',
                'worker_object.role_id', 'worker.tabel_number', 'place.title as place_title',
                'injunction_violation.correct_period', 'injunction_violation.id AS injunction_violation_id',
                'violation.title AS violation_title', 'order.shift_id'])
                ->innerJoin('order_place', 'order_place.order_id = order.id')
                ->innerJoin('injunction', 'injunction.place_id = order_place.place_id')
                ->innerJoin('place', 'place.id = order_place.place_id')
                ->innerJoin('status', 'injunction.status_id = status.id')
                ->innerJoin('worker', 'injunction.worker_id = worker.id')
                ->innerJoin('employee', 'worker.employee_id = employee.id')
                ->leftJoin('worker_object', 'worker_object.worker_id = worker.id')
                ->innerJoin('checking', 'injunction.checking_id = checking.id')
                ->innerJoin('injunction_violation', 'injunction_violation.injunction_id = injunction.id')
                ->innerJoin('violation', 'injunction_violation.violation_id = violation.id')
                ->where(['order.company_department_id' => $company_department_id, 'order.brigade_id' => $brigade_id,
                    'order.date_time_create' => $date, 'injunction.status_id' => 1])
                ->andWhere(['>=', 'checking.date_time_start', $date . ' 00:00:00'])
                ->andWhere(['<=', 'checking.date_time_start', $date . ' 23:59:59'])
                ->asArray()
                ->all();

            /******************** Перебор полученных предписаний с целью подготовки выходного массива ********************/

            if ($order_injunctions) {
                foreach ($order_injunctions as $injunction) {
                    //                $shift_injunctions['shifts']['shift_id'] = (int)$injunction['shift_id'];
                    $shift_injunctions['injunctions'][$injunction['injunction_id']]['injunction_id'] = (int)$injunction['injunction_id'];
                    $shift_injunctions['injunctions'][$injunction['injunction_id']]['injunction_date_time'] = date('Y-m-d', strtotime($injunction['injunction_date_time_start']));
                    $shift_injunctions['injunctions'][$injunction['injunction_id']]['injunction_status'] = $injunction['status_title'];
                    $shift_injunctions['injunctions'][$injunction['injunction_id']]['injunction_place_title'] = $injunction['place_title'];

                    /******************** Информация о работнике который выдал предписание ********************/

                    $shift_injunctions['injunctions'][$injunction['injunction_id']]['issuer_tabel_number'] = $injunction['tabel_number'];
                    $shift_injunctions['injunctions'][$injunction['injunction_id']]['issuer_role_id'] = (int)$injunction['role_id'];
                    $shift_injunctions['injunctions'][$injunction['injunction_id']]['issuer_full_name'] = $injunction['last_name'] . ' ' . $injunction['first_name'];
                    if (isset($injunction['patronymic']) && $injunction['patronymic'] !== ' ' && $injunction['patronymic'] != -1) {
                        $shift_injunctions['injunctions'][$injunction['injunction_id']]['issuer_full_name'] .= ' ' . $injunction['patronymic'];
                    }

                    /******************** Информация по нарушениям предписания ********************/

                    $shift_injunctions['injunctions'][$injunction['injunction_id']]['injunction_violations'][$injunction['injunction_violation_id']]['injunction_violation_id'] = (int)$injunction['injunction_violation_id'];
                    $shift_injunctions['injunctions'][$injunction['injunction_id']]['injunction_violations'][$injunction['injunction_violation_id']]['violation_title'] = $injunction['violation_title'];
                    $shift_injunctions['injunctions'][$injunction['injunction_id']]['injunction_violations'][$injunction['injunction_violation_id']]['correct_period'] = $injunction['correct_period'];
                }
            } else {
                throw new Exception('GetInjunctionReport. Предписаний не найдено');
            }
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result = $shift_injunctions;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetUnits() - получения справочника единиц измерения
     * @param null $data_post
     * @return array
     * @package frontend\controllers\ordersystem
     * @example http://web.amicum/read-manager-amicum?controller=ordersystem\ReportForPreviousPeriod&method=GetUnits&subscribe=worker_list&data=
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 16.07.2019 16:00
     */
    public static function GetUnits($data_post = NULL)
    {
        $status = 1;                                                                                                        // Флаг успешного выполнения метода
        $warnings = array();                                                                                                // Массив предупреждений
        $errors = array();                                                                                                  // Массив ошибок
        $units = array();                                                                                   // Промежуточный результирующий массив
        try {
            $units = Unit::find()
                ->select(['id', 'title', 'short AS short_title'])
                ->asArray()
                ->all();
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result = $units;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SaveReport() - Общий метод сохранения отчёта
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://web.amicum/read-manager-amicum?controller=ordersystem\ReportForPreviousPeriod&method=SaveReport&subscribe=worker_list&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 28.08.2019 9:24
     */
    public static function SaveReport($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $report = array();                                                                                // Промежуточный результирующий массив
        $order_id = null;
        $method_name = 'SaveReport';
//        $data_post = '{"cyclogram_option":{"max_section":"100","date_time_start":"2019-12-26+14:00:00","date_time_end":"2019-12-26+20:00:00","type_operation_id":"1","section_start":0,"section_end":0,"cyclegramm_type_id":2},"cyclograms_operations":[{"type_operation_id":"4","date_time_start":"2019-12-26+15:00:00","date_time_end":"2019-12-26+15:20:00","section_start":"0","section_end":20},{"type_operation_id":"9","date_time_start":"2019-12-26+15:20:00","date_time_end":"2019-12-26+15:40:00","section_start":20,"section_end":20},{"type_operation_id":"10","date_time_start":"2019-12-26+15:40:00","date_time_end":"2019-12-26+16:00:00","section_start":20,"section_end":"0"},{"type_operation_id":"4","date_time_start":"2019-12-26+18:00:00","date_time_end":"2019-12-26+18:20:00","section_start":"100","section_end":80},{"type_operation_id":"9","date_time_start":"2019-12-26+18:20:00","date_time_end":"2019-12-26+18:40:00","section_start":80,"section_end":80},{"type_operation_id":"10","date_time_start":"2019-12-26+18:40:00","date_time_end":"2019-12-26+19:00:00","section_start":80,"section_end":"100"},{"type_operation_id":"1","date_time_start":"2019-12-26+14:00:00","date_time_end":"2019-12-26+15:00:00","section_start":0,"section_end":"0"},{"type_operation_id":"2","date_time_start":"2019-12-26+16:00:00","date_time_end":"2019-12-26+18:00:00","section_start":"0","section_end":"100"},{"type_operation_id":"1","date_time_start":"2019-12-26+19:00:00","date_time_end":"2019-12-26+20:00:00","section_start":"100","section_end":0}],"shift_id":"2","date":"2019-12-26","company_department_id":20028748,"brigade_id":363,"chane_id":null,"order_id":481,"worker_value_outgoing":8,"order_place":[{"order_place_id":4146,"place_id":134680,"passport_id":null,"order_production":[{"operation_id":199,"order_operation_id":15185,"equipment_id":1,"order_operation_id_vtb":null,"correct_measures_id":null,"order_place_id_vtb":null,"operation_value_plan":null,"operation_value_fact":"3","description":"","attachment":[],"images":[]},{"operation_id":201,"order_operation_id":15186,"equipment_id":1,"order_operation_id_vtb":null,"correct_measures_id":null,"order_place_id_vtb":null,"operation_value_plan":null,"operation_value_fact":"3","description":"","attachment":[],"images":[]},{"operation_id":200,"order_operation_id":15187,"equipment_id":1,"order_operation_id_vtb":null,"correct_measures_id":null,"order_place_id_vtb":null,"operation_value_plan":null,"operation_value_fact":"3","description":"","attachment":[],"images":[]},{"operation_id":141086,"order_operation_id":15194,"equipment_id":1,"order_operation_id_vtb":null,"correct_measures_id":null,"order_place_id_vtb":null,"operation_value_plan":"0","operation_value_fact":"0","description":"чаврыврварыварываыавыва","attachment":[],"images":[]}],"deleted_img":[],"deleted_attachment":[]}]}';
//        $data_post = '{"cyclogram_option":{"max_section":"100","date_time_start":"2020-02-05+08:00:00","date_time_end":"2020-02-05+14:00:00","type_operation_id":"1","section_start":20,"section_end":78,"cyclegramm_type_id":2},"cyclograms_operations":[{"type_operation_id":"4","date_time_start":"2020-02-05+08:00:00","date_time_end":"2020-02-05+08:20:00","section_start":"0","section_end":20},{"type_operation_id":"9","date_time_start":"2020-02-05+08:20:00","date_time_end":"2020-02-05+08:40:00","section_start":20,"section_end":20},{"type_operation_id":"9","date_time_start":"2020-02-05+08:40:00","date_time_end":"2020-02-05+09:00:00","section_start":20,"section_end":100},{"type_operation_id":"10","date_time_start":"2020-02-05+09:20:00","date_time_end":"2020-02-05+09:30:00","section_start":100,"section_end":"0"},{"type_operation_id":"4","date_time_start":"2020-02-05+09:40:00","date_time_end":"2020-02-05+10:03:20","section_start":"100","section_end":80},{"type_operation_id":"9","date_time_start":"2020-02-05+10:03:20","date_time_end":"2020-02-05+10:26:40","section_start":80,"section_end":80},{"type_operation_id":"10","date_time_start":"2020-02-05+10:26:40","date_time_end":"2020-02-05+10:50:00","section_start":80,"section_end":"100"},{"type_operation_id":"4","date_time_start":"2020-02-05+11:00:00","date_time_end":"2020-02-05+11:40:00","section_start":"0","section_end":20},{"type_operation_id":"9","date_time_start":"2020-02-05+11:40:00","date_time_end":"2020-02-05+12:20:00","section_start":20,"section_end":20},{"type_operation_id":"10","date_time_start":"2020-02-05+12:20:00","date_time_end":"2020-02-05+13:00:00","section_start":20,"section_end":"0"},{"type_operation_id":"1","date_time_start":"2020-02-05+08:00:00","date_time_end":"2020-02-05+08:00:00","section_start":20,"section_end":"0"},{"type_operation_id":"2","date_time_start":"2020-02-05+09:30:00","date_time_end":"2020-02-05+09:40:00","section_start":"0","section_end":"100"},{"type_operation_id":"1","date_time_start":"2020-02-05+10:50:00","date_time_end":"2020-02-05+11:00:00","section_start":"100","section_end":"0"},{"type_operation_id":"2","date_time_start":"2020-02-05+13:00:00","date_time_end":"2020-02-05+14:00:00","section_start":"0","section_end":78},{"type_operation_id":"8","date_time_start":"2020-02-05+09:00:00","date_time_end":"2020-02-05+09:20:00","section_start":100,"section_end":100,"stop_face":{"event_id":"7127","title":"Шахтер+подал+SOS","date_time_start":"2020-02-05+09:00:00","date_time_end":"2020-02-05+09:20:00","description":"1"}}],"shift_id":"1","date":"2020-02-05","company_department_id":"4029938","brigade_id":316,"chane_id":722,"order_id":1362,"order_place":[{"order_place_id":18514,"place_id":26387,"passport_id":null,"order_production":[{"operation_id":112,"order_operation_id":83478,"equipment_id":1,"order_operation_id_vtb":null,"correct_measures_id":null,"order_place_id_vtb":null,"operation_value_plan":"0","operation_value_fact":"0","description":"","attachment":[],"images":[]}],"deleted_img":[],"deleted_attachment":[]}]}';
        $warnings[] = 'SaveReport. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveReport. Не переданы входные параметры');
            }
            $warnings[] = 'SaveReport. Данные успешно переданы';
            $warnings[] = 'SaveReport. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'SaveReport. Декодировал входные параметры';

            /**
             * Если переданы данные для сохранения выхождаемости, вызываем метод сохранения выхождаемости
             */
            if (property_exists($post_dec, 'order_id') &&
                property_exists($post_dec, 'worker_value_outgoing')) {
                $order_id = $post_dec->order_id;
                if ($order_id != null) {
                    $warnings[] = 'SaveReport. Данные с фронта получены';
                    $save_worker_value_outgoing = OrderSystemController::ChangeWorkerValueOutgoing($data_post);
                    $report['outgoing_workers'] = $save_worker_value_outgoing['Items'];
                    if (count($save_worker_value_outgoing['errors']) != 0) {
                        $errors[] = $save_worker_value_outgoing['errors'];
                        $status = $save_worker_value_outgoing['status'];
                    } else
                        $warnings[] = $save_worker_value_outgoing['warnings'];
                }

            }

            /**
             * Если переданы данные для показателей, вызываем метод сохранения показателей
             */
            if (property_exists($post_dec, 'order_id') &&
                property_exists($post_dec, 'order_place')) {
                $save_indicators = self::SavingIndicators($data_post);
                if ($save_indicators['status'] == 1) {
                    $status = $save_indicators['status'];
                    $warnings[] = $save_indicators['warnings'];
                    $post_dec->order_id = $save_indicators['order_id'];
                    $order_id = $save_indicators['order_id'];
                } else {
                    $errors[] = $save_indicators['errors'];
                    $warnings[] = $save_indicators['warnings'];
                    throw new Exception($method_name . '. Ошибка при сохранении наряда');
                }
            }


            /**
             * Если переданы данные для сохранения циклограммы, то вызываем метод сохранения циклограммы
             */
            if (
                property_exists($post_dec, 'cyclogram_option') &&
                property_exists($post_dec, 'cyclograms_operations') &&
                property_exists($post_dec, 'order_id') &&
                property_exists($post_dec, 'chane_id') &&
                property_exists($post_dec, 'company_department_id') &&
                property_exists($post_dec, 'date') &&
                property_exists($post_dec, 'shift_id')
            )                                                    // Проверяем наличие в нем нужных нам полей
            {
                if ($order_id != null) {
                    $warnings[] = 'SaveReport. Данные с фронта получены';
                    $json_for_cyclogramm = json_encode($post_dec);
                    $save_cyclogramm = self::SaveReportCyclogram($json_for_cyclogramm);
                    unset($json_for_cyclogramm);
                    $report['cyclogramm'] = $save_cyclogramm['Items'];
                    if (count($save_cyclogramm['errors']) != 0) {
                        $errors[] = $save_cyclogramm['errors'];
                        $status = $save_cyclogramm['status'];
                    }
                    $warnings[] = $save_cyclogramm['warnings'];
                }
            }
            unset($save_cyclogramm);

            /**
             * Сохранение планограммы
             */
            if (property_exists($post_dec, 'planogramm_data') &&
                property_exists($post_dec, 'company_department_id') &&
                property_exists($post_dec, 'order_id') &&
                property_exists($post_dec, 'date') &&
                property_exists($post_dec, 'delete_planogramm_ids') &&
                property_exists($post_dec, 'shift_id')
            ) {
                if (!empty($post_dec->planogramm_data)) {
                    $json_for_planogramm = json_encode($post_dec);
                    $response = self::SavePlanogramm($json_for_planogramm);
                    unset($json_for_planogramm);
                    if ($response['status'] == 1) {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new Exception('Ошибка при сохранении планограммы');
                    }
                }
            }
            unset($response);

            $json_report_data = json_encode(array(
                'company_department_id' => $post_dec->company_department_id,
                'shift_id' => $post_dec->shift_id,
                'date_time' => $post_dec->date,
                'mine_id' => $post_dec->mine_id,
                'brigade_id' => $post_dec->brigade_id,
                'chane_id' => $post_dec->chane_id
            ));
            $warnings[] = $json_report_data;
            $get_report_data = self::GetReportData($json_report_data);
            $report['save_indicators'] = $get_report_data['Items'];
            if ($get_report_data['status'] == 0) {
                $errors[] = $get_report_data['errors'];
                $warnings[] = $get_report_data['warnings'];
            }

        } catch (Throwable $exception) {
            $errors[] = 'SaveReport. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = 'SaveReport. Конец метода';
        $result = $report;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SavingIndicators() - Сохранение показателей на стринаце "Заполнение отчёта"
     * @param null $data_post - JSON с индентификатором наряда, и объектом который необходимо сохранить
     * @return array - стандартный массив выходных данных
     *
     * @package frontend\controllers\ordersystem
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 29.08.2019 10:31
     */
    public static function SavingIndicators($data_post = NULL)
    {
//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', '3000M');
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // Массив ошибок
        $saving_indicators = array();                                                                                // Промежуточный результирующий массив
        $order_operation_attachment = array();                                                                                // Промежуточный результирующий массив
        $order_operation_img = array();                                                                                // Промежуточный результирующий массив
        $add_order_operation_attachment = null;
        $add_order_operation_image = null;
        $company_department_id = null;
        $mine_id = null;
        $order_operation_id = null;
        $shift_id = null;
        $date_time = null;
        $brigade_id = null;
        $new_order_id = null;
        $order_id = null;
        $worker_value_outgoing = null;
        $deleted_img = array();
        $deleted_attachment = array();
        $session = Yii::$app->session;
//        $counter = 0;
//        $counter_attachment = 0;
        $warnings[] = 'SavingIndicators. Начало метода';
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SavingIndicators. Не переданы входные параметры');
            }
            $warnings[] = 'SavingIndicators. Данные успешно переданы';
            $warnings[] = 'SavingIndicators. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'SavingIndicators. Декодировал входные параметры';
            if (!property_exists($post_dec, 'order_id') ||
                !property_exists($post_dec, 'order_place'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('SavingIndicators. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SavingIndicators. Данные с фронта получены';
            $chane_id = $post_dec->chane_id;
            $order_id = $post_dec->order_id;
            $order_places = $post_dec->order_place;
            $company_department_id = $post_dec->company_department_id;
            $mine_id = $post_dec->mine_id;
            $shift_id = $post_dec->shift_id;
            $date_time = date('Y-m-d', strtotime($post_dec->date));
            $brigade_id = $post_dec->brigade_id;
            if (property_exists($post_dec, 'worker_value_outgoing')) {
                $worker_value_outgoing = $post_dec->worker_value_outgoing;
            }
//    		if (property_exists($post_dec,'company_department_id')&&
//                property_exists($post_dec,'shift_id')&&
//                property_exists($post_dec,'date_time')&&
//                property_exists($post_dec,'brigade_id')){
//
//            }

            if ($order_id != null) {

                foreach ($order_places as $order_place) {
                    $found_order_place = OrderPlace::findOne(['order_id' => $order_id, 'place_id' => $order_place->place_id]);

                    if (!$found_order_place) {
                        if ($order_place->order_place_id == null) {
                            $add_order_place = new OrderPlace();
                            $add_order_place->order_id = $order_id;
                            $add_order_place->place_id = $order_place->place_id;
                            if ($add_order_place->save()) {
                                $add_order_place->refresh();
                                $order_place_id = $add_order_place->id;
                                $warnings[] = 'SavingIndicators. Наряд на место успешно сохранён';
                            } else {
                                $errors[] = $add_order_place->errors;
                                throw new Exception('SavingIndicators. Ошибка при сохранении наряда на место');
                            }
                        } else {
                            $order_place_id = $order_place->order_place_id;
                        }
                    } else {
                        $order_place_id = $found_order_place->id;
                    }
                    foreach ($order_place->order_production as $order_production) {
                        if ($order_production->order_operation_id == null) {
                            $add_order_operation = new OrderOperation();
                            $add_order_operation->order_place_id = $order_place_id;
                            $add_order_operation->operation_id = $order_production->operation_id;
                            $add_order_operation->equipment_id = $order_production->equipment_id;
                            $add_order_operation->order_operation_id_vtb = $order_production->order_operation_id_vtb;
                            $add_order_operation->correct_measures_id = $order_production->correct_measures_id;
                            $add_order_operation->order_place_id_vtb = $order_production->order_place_id_vtb;
                            $add_order_operation->operation_value_plan = '0';
                            $add_order_operation->operation_value_fact = (string)((float)str_replace(",", ".", $order_production->operation_value_fact));
                            $add_order_operation->status_id = OrderSystemController::OPERATION_CREATED;
                            $add_order_operation->description = $order_production->description;
                            if (isset($order_production->equipment_id)) {
                                $add_order_operation->equipment_id = $order_production->equipment_id;
                            }
                            if ($add_order_operation->save()) {
                                $add_order_operation->refresh();
                                $order_operation_id = $add_order_operation->id;
                                $warnings[] = 'SavingIndicators. Операция в наярде успешно сохранена';
                            } else {
                                $errors[] = $add_order_operation->errors;
                                throw new Exception('SavingIndicatorsОшибка при сохранении операции в наряде');
                            }
                        } else {
                            $found_order_operation = OrderOperation::findOne(['id' => $order_production->order_operation_id]);
                            if ($found_order_operation) {
                                $found_order_operation->operation_value_fact = (string)((float)str_replace(",", ".", $order_production->operation_value_fact));
                                $found_order_operation->description = $order_production->description;
                                if ($found_order_operation->save()) {
                                    $order_operation_id = $found_order_operation->id;
                                    $warnings[] = 'SavingIndicators. Фактическое значение и описание операции наряда успешно сохранено';
                                } else {
                                    throw new Exception('SavingIndicators. Ошибка при сохранении фактического значения и описания операции наряда');
                                }
                            }
                        }

                        if (isset($order_production->attachment) && !empty($order_production->attachment)) {
                            foreach ($order_production->attachment as $attachment) {
                                if ($attachment->attachment_id == null) {
                                    $subst_src = mb_substr($attachment->attachment_src, 0, 4);
                                    if ($subst_src == 'data') {
                                        $path_attachment = Assistant::UploadFile($attachment->attachment_src, $attachment->attachment_name, 'attachment', $attachment->attachment_type);
                                    } else {
                                        $path_attachment = Assistant::upload_mobile_file($attachment->attachment_src, $attachment->attachment_name, 'attachment', $attachment->attachment_type);
                                    }

                                    $add_attachment = new Attachment();
                                    $add_attachment->path = $path_attachment;
                                    $add_attachment->date = BackendAssistant::GetDateFormatYMD();
                                    $add_attachment->worker_id = $session['worker_id'];
                                    $add_attachment->section_title = 'Заполнение отчёта';
                                    if ($add_attachment->save()) {
                                        $add_attachment->refresh();
                                        $attachment_id = $add_attachment->id;
                                        $warnings[] = 'SavingIndicators. Вложение успешно сохранено';
                                    } else {
                                        throw new Exception('SavingIndicators. Ошибка при сохранении вложения');
                                    }
                                    $order_operation_attachment[] = [$order_operation_id, $attachment_id];
                                }
                            }
                        }
                        if (isset($order_production->images) && !empty($order_production->images)) {
                            foreach ($order_production->images as $image) {
                                if (!isset($image->img_id) && empty($image->img_id)) {
                                    $subst_src = mb_substr($image->img_src, 0, 4);
                                    if ($subst_src == 'data') {
                                        $path_img = Assistant::UploadFile($image->img_src, $image->img_name, 'order_operation_img', $image->img_type);
                                    } else {
                                        $path_img = Assistant::upload_mobile_file($image->img_src, $image->img_name, 'attachment', $image->img_type);
                                    }
                                    $order_operation_img[] = [$order_operation_id, $path_img];
                                }
                            }
                        }
                    }
                    if (!empty($order_place->deleted_img)) {
                        $deleted_img[] = $order_place->deleted_img;
                    }
                    if (!empty($order_place->deleted_attachment)) {
                        $deleted_attachment[] = $order_place->deleted_attachment;
                    }
                }
            } else {
                $add_order = new Order();
                $add_order->title = "Наряд участка {$company_department_id}";
                $add_order->company_department_id = $company_department_id;
                $add_order->object_id = 24;
                $add_order->mine_id = $mine_id;
                $add_order->date_time_create = $date_time;
                $add_order->shift_id = $shift_id;
                $add_order->status_id = 8;
                $add_order->worker_value_outgoing = $worker_value_outgoing;
                if ($add_order->save()) {
                    $add_order->refresh();
                    $new_order_id = $add_order->id;
                    $order_id = $add_order->id;
                    $warnings[] = 'SaveIndicators. Новый наряд успешно сохранён';
                } else {
                    $errors[] = $add_order->errors;
                    throw new Exception('SaveIndicators. Ошибка при сохранении наряда');
                }
//                if (property_exists($post_dec, 'cyclogram_option') &&
//                    property_exists($post_dec, 'cyclograms_operations')) {
//                    $cyclogram_option = $post_dec->cyclogram_option;
//                    $cyclograms_operations = $post_dec->cyclograms_operations;
//                    $json = (object)array('cyclogram_option' => $cyclogram_option,
//                        'cyclograms_operations' => $cyclograms_operations,
//                        'brigade_id' => $brigade_id,
//                        'company_department_id' => $company_department_id,
//                        'date' => $date_time, 'shift_id' => $shift_id, 'order_id' => $new_order_id);
//                    $json = json_encode($json);//            $warnings['JSON_FOR_CYCLOGRAMM'] = $json;
//                    $save_cyclogramm = ReportForPreviousPeriodController::SaveReportCyclogram($json);
//                    if ($save_cyclogramm['status'] == 1) {
//                        $warnings[] = $save_cyclogramm['warnings'];
//                    } else {
//                        $warnings[] = $save_cyclogramm['warnings'];
//                        $errors[] = $save_cyclogramm['errors'];
//
//                    }
//                }

                foreach ($order_places as $order_place) {
                    $add_order_place = new OrderPlace();
                    $add_order_place->order_id = $new_order_id;
                    $add_order_place->place_id = $order_place->place_id;
                    if ($add_order_place->save()) {
                        $add_order_place->refresh();
                        $order_place_id = $add_order_place->id;
                        $warnings[] = 'SavingIndicators. Наряд на место успешно сохранён';
                    } else {
                        $errors[] = $add_order_place->errors;
                        throw new Exception('SavingIndicators. Ошибка при сохранении наряда на место');
                    }
                    foreach ($order_place->order_production as $order_production) {
                        $add_order_operation = new OrderOperation();
                        $add_order_operation->order_place_id = $order_place_id;
                        $add_order_operation->operation_id = $order_production->operation_id;
                        $add_order_operation->equipment_id = $order_production->equipment_id;
                        $add_order_operation->operation_value_plan = '0';
                        $add_order_operation->operation_value_fact = (string)$order_production->operation_value_fact;
                        $add_order_operation->status_id = OrderSystemController::OPERATION_CREATED;
                        $add_order_operation->description = $order_production->description;
                        if ($add_order_operation->save()) {
                            $add_order_operation->refresh();
                            $order_operation_id = $add_order_operation->id;
                            $warnings[] = 'SavingIndicators. Операция в наярде успешно сохранена';
                        } else {
                            $errors[] = $add_order_operation->errors;
                            throw new Exception('SavingIndicatorsОшибка при сохранении операции в наряде');
                        }
                        if (isset($order_production->attachment) && !empty($order_production->attachment)) {
                            foreach ($order_production->attachment as $attachment) {
                                if ($attachment->attachment_id == null) {
                                    $subst_src = mb_substr($attachment->attachment_src, 0, 4);
                                    if ($subst_src == 'data') {
                                        $path_attachment = Assistant::UploadFile($attachment->attachment_src, $attachment->attachment_name, 'attachment', $attachment->attachment_type);
                                    } else {
                                        $path_attachment = Assistant::upload_mobile_file($attachment->attachment_src, $attachment->attachment_name, 'attachment', $attachment->attachment_type);
                                    }

                                    $add_attachment = new Attachment();
                                    $add_attachment->path = $path_attachment;
                                    $add_attachment->date = BackendAssistant::GetDateFormatYMD();
                                    $add_attachment->worker_id = $session['worker_id'];
                                    $add_attachment->section_title = 'Заполнение отчёта';
                                    if ($add_attachment->save()) {
                                        $add_attachment->refresh();
                                        $attachment_id = $add_attachment->id;
                                        $warnings[] = 'SavingIndicators. Вложение успешно сохранено';
                                    } else {
                                        throw new Exception('SavingIndicators. Ошибка при сохранении вложения');
                                    }
                                    $order_operation_attachment[] = [$order_operation_id, $attachment_id];
                                }
                            }
                        }
                        if ($order_production->images != null) {
                            foreach ($order_production->images as $image) {
                                if (!isset($image->img_id) && empty($image->img_id)) {
                                    $subst_src = mb_substr($image->img_src, 0, 4);
                                    if ($subst_src == 'data') {
                                        $path_img = Assistant::UploadFile($image->img_src, $image->img_name, 'order_operation_img', $image->img_type);
                                    } else {
                                        $path_img = Assistant::upload_mobile_file($image->img_src, $image->img_name, 'order_operation_img', $image->img_type);
                                    }
                                    $order_operation_img[] = [$order_operation_id, $path_img];
                                }
                            }
                        }
                    }
                }
            }
            /**
             * Сохранение статуса наряда
             */
            $new_order_status = new OrderStatus();
            $new_order_status->order_id = $order_id;
            $new_order_status->status_id = OrderSystemController::ORDER_WAS_PASSED;
            $new_order_status->worker_id = $session['worker_id'];//идентификатор человека поменявшего статус
            $new_order_status->date_time_create = \backend\controllers\Assistant::GetDateNow();
            $new_order_status->description = " ";
            if ($new_order_status->save()) {
                $warnings[] = 'SaveIndicators. Статус наряда успешно сохранён.';
            } else {
                $errors[] = $new_order_status->errors;
                throw new Exception('SaveIndicators. Произошла ошибка при сохранении статуса наряда');
            }
            $images_for_delete = array();
            array_walk_recursive($deleted_img, function ($item, $key) use (&$images_for_delete) {
                $images_for_delete[] = $item;
            });
            $attachment_for_delete = array();
            array_walk_recursive($deleted_attachment, function ($item, $key) use (&$attachment_for_delete) {
                $attachment_for_delete[] = $item;
            });
            if ($images_for_delete != null) {
                $delete_order_operation_image = OrderOperationImg::deleteAll(['in', 'id', $images_for_delete]);
                if ($delete_order_operation_image != 0) {
                    $warnings[] = 'SavingIndicators. Фотографии успешно удалены';
                } else {
                    $errors[] = $delete_order_operation_image;
                }
            }
            $warnings['DELETED_ATTACHMENTS'] = $attachment_for_delete;
            if ($attachment_for_delete != null) {
                $delete_order_operation_attachment = OrderOperationAttachment::deleteAll(['in', 'id', $attachment_for_delete]);
                if ($delete_order_operation_attachment != 0) {
                    $warnings[] = 'SavingIndicators. Вложения успешно удалены';
                }
            }

            /******************** СОХРАНЕНИЕ ВЛОЖЕНИЙ НА ОПЕРАЦИЮ ********************/
            if ($order_operation_attachment != null) {
                $add_order_operation_attachment = Yii::$app->db
                    ->createCommand()
                    ->batchInsert('order_operation_attachment', ['order_operation_id', 'attachment_id'], $order_operation_attachment)
                    ->execute();
                if ($add_order_operation_attachment != 0) {
                    $warnings[] = 'SavingIndicators. Вложения успешно сохранены';
                } else {
                    throw new Exception('SavingIndicators. Ошибка при сохранении вложений');
                }
            }

            $warnings['Массив перед добавлением изображений'] = $order_operation_img;
            /******************** СОХРАНЕНИЕ ФОТОГРАФИЙ НА ОПЕРАЦИЮ ********************/
            if (!empty($order_operation_img)) {
                $add_order_operation_image = Yii::$app->db
                    ->createCommand()
                    ->batchInsert('order_operation_img', ['order_operation_id', 'path'], $order_operation_img)
                    ->execute();
                if ($add_order_operation_image != 0) {
                    $warnings[] = 'SavingIndicators. Фотографий операции наряда успешно сохранены';
                } else {
                    throw new Exception('SavingIndicators. Ошибка при сохранении фотографий операции наряда');
                }
            }
            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            $errors[] = 'SavingIndicators. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $warnings[] = 'SavingIndicators. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'order_id' => $order_id);
        return $result_main;
    }

    /**
     * Метод GetReportData() - Возвращает все данные для страницы "Заполнение отчёта"
     * @param null $data_post - JSON с данными: идентификатор бригады, идентификатор участка, идентификатор смены, дата
     * @return array - массив с данными: [order_data]
     *                                          [order_id]
     *                                                  order_id:
     *                                                  chane_id:
     *                                                  brigade_id:
     *                                                  [order_place]
     *                                                          [order_place_id]
     *                                                                  order_place_id:
     *                                                                  place_id:
     *                                                                  passport_id:
     *                                                                  [operation_production]
     *                                                                          [order_operation_id]
     *                                                                                  order_operation_id:
     *                                                                                  operation_id:
     *                                                                                  operation_value_plan:
     *                                                                                  operation_value_fact:
     *                                                                                  description:
     *                                                                                  [attachments]
     *                                                                                      [attachment_id:
     *                                                                                       attachment_src:]
     *                                                                                  [images]
     *                                                                                      [img_id:
     *                                                                                       img_src:]
     *                                  [cyclogram]
     *                                          (Объект из метода циклограммы)
     *                                  [outgoing]
     *                                          [По number_of_employees_by_roles]
     *                                                                      [role_id]
     *                                                                          count_worker:
     *                                           underground_in:
     *                                           surface_in:
     *                                           underground_out:
     *                                           surface_out:
     *                                           sum_workers_in:
     *                                           sum_workers_out:
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\ReportForPreviousPeriod&method=GetReportData&subscribe=&data={%22company_department_id%22:801,%22shift_id%22:%221%22,%22date_time%22:%2229.08.2019%22,%22brigade_id%22:3908}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 29.08.2019 16:01
     */
    public static function GetReportData($data_post = NULL)
    {
        $log = new LogAmicumFront("GetReportData", true);

        $order_data = array();                                                                                          // данные наряда
        $cyclogram = array();                                                                                           // массив циклограмм
        $planogramm = array();                                                                                          // массив планограмм
        $order_arr = array();                                                                                           // массив нарядов
        $outgoing_workers = array();                                                                                    // выхождаемость
        $department_type_id = null;
        $main_operations = array();

        $log->addLog("Начало метода");

        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $log->addLog("Данные успешно переданы");
            $log->addData($data_post, '$data_post', __LINE__);

            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных

            $log->addLog("Декодировал входные параметры");

            if (!property_exists($post_dec, 'date_time') ||
                !property_exists($post_dec, 'brigade_id') ||
                !property_exists($post_dec, 'company_department_id') ||                                                    // Проверяем наличие в нем нужных нам полей
                !property_exists($post_dec, 'shift_id') ||
                !property_exists($post_dec, 'mine_id') ||
                !property_exists($post_dec, 'chane_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $log->addLog("Данные с фронта получены");

            $company_department_id = $post_dec->company_department_id;
            $date = date('Y-m-d', strtotime($post_dec->date_time));
            $shift_id = $post_dec->shift_id;
            $brigade_id = $post_dec->brigade_id;
            $chane_id = $post_dec->chane_id;
            $mine_id = $post_dec->mine_id;
            $chane_id_for_cyclegram = $post_dec->chane_id;

            $response = self::GetDepartmentOperation($company_department_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка при получении типа участка');
            }

            $department_type_id = $response['department_type_id'];
            $main_operations = $response['operation_ids'];

            unset($response);
            $orders = Order::find()
                ->joinWith('orderPlaces.place')
                ->joinWith('orderPlaces.orderOperations.orderOperationAttachments.attachment')
                ->joinWith('orderPlaces.orderOperations.orderOperationImgs')
                ->joinWith('orderPlaces.orderOperations.operationWorkers')
                ->joinWith('orderPlaces.orderOperations.operationWorkers.worker.workerObjects')
                ->joinWith('orderPlaces.orderOperations.operationWorkers.orderOperationWorkerStatuses')
                ->joinWith('orderPlaces.orderOperations.operation.operationGroups')
                ->joinWith('orderPlaces.orderOperations.operation.unit')
                ->joinWith('orderPlaces.orderOperations.equipment')
                ->where(['order.company_department_id' => $company_department_id,
                    'order.date_time_create' => $date,
                    'order.mine_id' => $mine_id,
                    'order.shift_id' => $shift_id])
                ->all();
            if (!empty($orders)) {
                foreach ($orders as $order) {
                    $order_id = $order->id;
                    $order_arr[] = $order_id;
                    $order_data['order_id'] = $order_id;
                    $order_data['chane_id'] = $chane_id;
                    $order_data['brigade_id'] = $brigade_id;

                    foreach ($order->orderPlaces as $orderPlace) {
                        $order_place_id = $orderPlace->id;
                        $order_data['order_place'][$order_place_id]['order_place_id'] = $order_place_id;
                        $order_data['order_place'][$order_place_id]['place_id'] = $orderPlace->place_id;
                        $order_data['order_place'][$order_place_id]['place_title'] = $orderPlace->place->title;
                        $order_data['order_place'][$order_place_id]['passport_id'] = $orderPlace->passport_id;
                        $order_data['order_place'][$order_place_id]['route_template_id'] = $orderPlace->route_template_id;
                        foreach ($orderPlace->orderOperations as $orderOperation) {
                            $order_operation_id = $orderOperation->id;
                            $order_data['order_place'][$order_place_id]['operation_production'][$order_operation_id]['order_operation_id'] = $orderOperation->id;
                            $order_data['order_place'][$order_place_id]['operation_production'][$order_operation_id]['operation_id'] = $orderOperation->operation_id;
                            $order_data['order_place'][$order_place_id]['operation_production'][$order_operation_id]['operation_title'] = $orderOperation->operation->title;
                            $order_data['order_place'][$order_place_id]['operation_production'][$order_operation_id]['operation_unit_short_title'] = $orderOperation->operation->unit->short;
                            $order_data['order_place'][$order_place_id]['operation_production'][$order_operation_id]['equipment_id'] = $orderOperation->equipment_id;
                            $order_data['order_place'][$order_place_id]['operation_production'][$order_operation_id]['equipment_title'] = $orderOperation->equipment->title;
                            $order_data['order_place'][$order_place_id]['operation_production'][$order_operation_id]['order_operation_id_vtb'] = $orderOperation->order_operation_id_vtb;
                            $order_data['order_place'][$order_place_id]['operation_production'][$order_operation_id]['correct_measures_id'] = $orderOperation->correct_measures_id;
                            $order_data['order_place'][$order_place_id]['operation_production'][$order_operation_id]['order_place_id_vtb'] = $orderOperation->order_place_id_vtb;
                            $order_data['order_place'][$order_place_id]['operation_production'][$order_operation_id]['operation_value_plan'] = $orderOperation->operation_value_plan;
                            $order_data['order_place'][$order_place_id]['operation_production'][$order_operation_id]['operation_value_fact'] = $orderOperation->operation_value_fact;
                            $order_data['order_place'][$order_place_id]['operation_production'][$order_operation_id]['description'] = $orderOperation->description;
                            foreach ($orderOperation->orderOperationAttachments as $orderOperationAttachment) {
                                if ($orderOperationAttachment->attachment) {
                                    if ($orderOperationAttachment->attachment->title == null) {
                                        $attachment_name = basename($orderOperationAttachment->attachment->path);
                                    } else {
                                        $attachment_name = $orderOperationAttachment->attachment->title;
                                    }
                                    $attachment_data = ['attachment_id' => $orderOperationAttachment->id, 'attachment_src' => $orderOperationAttachment->attachment->path, 'attachment_name' => $attachment_name];
                                } else {
                                    $attachment_data = ['attachment_id' => $orderOperationAttachment->id, 'attachment_src' => "", 'attachment_name' => ""];
                                }
                                $order_data['order_place'][$order_place_id]['operation_production'][$order_operation_id]['attachments'][] = $attachment_data;
                            }
                            foreach ($orderOperation->orderOperationImgs as $orderOperationImg) {
                                $img_name = basename($orderOperationImg->path);
                                $image_data = ['img_id' => $orderOperationImg->id, 'img_src' => $orderOperationImg->path, 'img_name' => $img_name];
                                $order_data['order_place'][$order_place_id]['operation_production'][$orderOperation->id]['images'][] = $image_data;
                            }
                            $order_data['order_place'][$order_place_id]['operation_production'][$orderOperation->id]['operation_groups'] = array();
                            foreach ($orderOperation->operation->operationGroups as $operationGroup) {
                                $order_data['order_place'][$order_place_id]['operation_production'][$orderOperation->id]['operation_groups'][] = $operationGroup;
                            }
                            foreach ($orderOperation->operationWorkers as $operation_worker) {
                                $worker_id = $operation_worker->worker_id;
                                $order_data['order_workers'][$worker_id]['worker_id'] = $worker_id;
                                $order_data['order_workers'][$worker_id]['type_skud'] = 0;
                                $order_data['order_workers'][$worker_id]['worker_role_id'] = $operation_worker->role_id;
                                $order_data['order_workers'][$worker_id]['operation_production'][$order_operation_id]['order_place_id'] = $order_place_id;
                                $order_data['order_workers'][$worker_id]['operation_production'][$order_operation_id]['order_operation_id'] = $order_operation_id;
                                $order_data['order_workers'][$worker_id]['operation_production'][$order_operation_id]['operation_worker_id'] = $operation_worker->id;
                                $order_data['order_workers'][$worker_id]['operation_production'][$order_operation_id]['coordinate'] = $operation_worker->coordinate;
                                $order_data['order_workers'][$worker_id]['operation_production'][$order_operation_id]['group_workers_unity'] = $operation_worker->group_workers_unity;
                            }
                        }
                    }
                }
            } else {
                $log->addLog("Нет данных по наряду");
            }

            $cyclogram_result = self::GetCyclogramm($order_arr, $company_department_id, $date, $shift_id, $chane_id_for_cyclegram);
            $log->addLogAll($cyclogram_result);
            if ($cyclogram_result['status'] != 1) {
                throw new Exception('Ошибка при получении циклограммы');
            }
            $cyclogram = $cyclogram_result['Items']['cyclogram'];
            $log->addLog("Получил данные по циклограмме");

            $orders = Order::find()
                ->joinWith('orderWorkerVgks')
                ->joinWith('orderInstructionPbs')
                ->where(['order.company_department_id' => $company_department_id,
                    'order.date_time_create' => $date,
                    'order.mine_id' => $mine_id,
                    'order.shift_id' => $shift_id])
                ->all();
            if (!empty($orders)) {
                foreach ($orders as $order) {
                    $order_id = $order->id;
                    $order_arr[] = $order_id;
                    $order_data['order_id'] = $order_id;
                    $order_data['chane_id'] = $chane_id;
                    $order_data['brigade_id'] = $brigade_id;
                    if (empty($order->orderInstructionPbs)) {
                        $order_data['order_instructions'] = (object)array();
                    } else {
                        foreach ($order->orderInstructionPbs as $order_instruction_pb) {
                            $order_data['order_instructions'][$order_instruction_pb->id]['order_instruction_id'] = $order_instruction_pb->id;
                            $order_data['order_instructions'][$order_instruction_pb->id]['instruction_pb_id'] = $order_instruction_pb->instruction_pb_id;
                        }
                    }
                    if ($order->orderWorkerVgks) {
                        foreach ($order->orderWorkerVgks as $rescuire) {
                            $order_data['workers_vgk'][$rescuire->worker_id]['worker_id'] = $rescuire->worker_id;
                            $order_data['workers_vgk'][$rescuire->worker_id]['worker_role'] = $rescuire->role_id;
                            $order_data['workers_vgk'][$rescuire->worker_id]['vgk'] = $rescuire->vgk;
                        }
                    }
                }
            } else {
                $log->addLog("Нет данных по наряду");
            }

            if (!empty($orders)) {
                /**
                 * Получаем данные планограммы:
                 */
                $json_planogram = json_encode(array("order_arr" => $order_arr, "company_department_id" => $company_department_id, "date" => $date, 'shift_id' => $shift_id));
                $planogramm_result = self::GetPlanogramma($json_planogram);
                $log->addLogAll($planogramm_result);
                if ($planogramm_result['status'] != 1) {
                    throw new Exception('Ошибка при получении планограммы');
                }
                $planogramm = $planogramm_result['Items'];
            }

            if (!empty($orders)) {
                $json_outgoing = json_encode(array(
                    'company_department_id' => $company_department_id,
                    'shift_id' => $shift_id,
                    'date_time' => $date,
                    'mine_id' => $mine_id,
                    'order_id' => $order_arr[0],
                ));

                $outgoing = OrderSystemController::GetWorkersAttendanceInShift($json_outgoing);
                $log->addLogAll($outgoing);
                $outgoing_workers = $outgoing['Items'];
                if (count($outgoing_workers) != 0) {
                    $log->addLog("Нет данных по выхождаемости");
                }
            }

            $response = Assistant::GetDateTimeByShift($date, $shift_id);
            $date_time_inj = date('Y-m-d H:i:s', strtotime($response['date_end'] . '+1 min'));
            $injunctions = OrderSystemController::GetInjunctionsIds($company_department_id, $date_time_inj);
            $log->addLogAll($injunctions);
            if ($injunctions['status'] != 1) {
                throw new Exception('Ошибка при получении предписаний');
            }

            if (!empty($injunctions['Items'])) {
                $order_data['injunctions'] = $injunctions['Items'];
            } else {
                $order_data['injunctions'] = (object)array();
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Конец метода");

        $result = ['order_data' => $order_data, 'cyclogram' => $cyclogram, 'outgoing' => $outgoing_workers, 'department_type_id' => $department_type_id, 'main_operations' => $main_operations, 'planogram' => $planogramm];
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод GetPlanogramma() - Метод получения планограммы
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\ReportForPreviousPeriod&method=GetPlanogramma&subscribe=&data={%22order_arr%22:[1809],%22company_department_id%22:20028766,%22date%22:%222020-02-21%22,%22shift_id%22:4}
     * Входные данные:
     *      order_arr                   - массив нарядов
     *      company_department_id       - идентификатор участка
     *      date                        - дата на которую нужна планограмма
     *      shift_id                    - идентификатор смены
     * ВЫХОДНЫЕ ДАННЫЕ:
     *      {
     *      "planogram": {
     *              "2": {
     *                  "shifts": {
     *                      "2": {
     *                          "shift_id": 2,
     *                          "planogram_option": {
     *                              "date_time_start": "2020-02-28 14:00:00",
     *                              "date_time_end": "2020-02-28 20:00:00",
     *                              "planogram_type_id": 2, // План / факт(1 - план / 2 - факт)
     *                              "equipments": {
     *                                  "100": {
     *                                      "equipment_id": 100,
     *                                      "equipment_title": 'PIOMA-1200',
     *                                      "planogam_operations": [
     *                                              2839, -1
     *                                          ]
     *                                  }
     *                              }
     *                          }
     *                      }
     *                  },
     *                  "planograms_operations": {
     *                  // Работа
     *                      "2839": {
     *                          "order_planogram_id": 2839,
     *                          "date_time_start": "2020-02-28 14:00:00",
     *                          "date_time_end": "2020-02-28 14:00:00",
     *                          "type_operation_id": 1, // Тип операции
     *                  },
     *
     *                  // Простой
     *                      "-1": {
     *                          "order_planogram_id": -1,
     *                          "date_time_start": "2020-02-28 17:15:00",
     *                          "date_time_end": "2020-02-28 18:00:00",
     *                          "type_operation_id": 8, // Тип операции
     *                          "stop_pb_id": 12794,
     *                          "kind_stop_pb_id": 1, // тип простоя
     *                          "place_id": 601579,
     *                          "place_title": "кш-623ю",
     *                          "description": "gtfvtgfftgftyugftgyu//////",
     *                          "events": [{ // Причины простоя
     *                                  "event_id": 1
     *                              }, {
     *                                  "event_id": 7128
     *                              }, {
     *                                  "event_id": 22412
     *                              }
     *                          ]
     *                      }
     *
     *                  }
     *              }
     *          }
     *      }
     *
     *
     * АЛГОРИТМ РАБОТЫ МЕТОДА:
     * 1. Получить все приостановки работ за смену (если её передали) если нет то за сутки
     * 2. Сформировать данные по приостановке работы массив вида:
     *                                      [equipment_id]
     *                                          [-1]
     *                                              order_planogram_id:
     *                                              stop_pb_id:
     *                                              date_time_start:
     *                                              date_time_end:
     *                                              type_operation_id:
     *                                              kind_stop_pb_id:
     *                                              place_id:
     *                                              place_title:
     *                                              description:
     *                                              [events]
     *                                                      [event_id:]
     * 3. Получить планограмму по переданным: массив нарядов, смена (если передана)
     * 4. Перебор полученных данных
     *      4.1 Формирование выходного массива
     *      4.2 Если массив приостановок не пуст по оборудованию в планограмме
     *              пуст?       Пропустить
     *              не пуст?    Добавить к массиву выходных данных данные о приостановке
     * 5.Конец перебора
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 10.10.2019 11:31
     */
    public static function GetPlanogramma($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // Промежуточный результирующий массив
        $shift_id = 5;
        $chane_id = null;
        $warnings[] = 'GetPlanogramma. Начало метода';
        $stop_pb_data = array();
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetPlanogramma. Не переданы входные параметры');
            }
            $warnings[] = 'GetPlanogramma. Данные успешно переданы';
//            $warnings[] = 'GetPlanogramma. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetPlanogramma. Декодировал входные параметры';
            if (!property_exists($post_dec, 'order_arr') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'date'))                                                            // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetPlanogramma. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetPlanogramma. Данные с фронта получены';
            $order_arr = $post_dec->order_arr;
            $company_department_id = $post_dec->company_department_id;
            $date = date('Y-m-d', strtotime($post_dec->date));
            if (property_exists($post_dec, 'shift_id')) {
                $shift_id = $post_dec->shift_id;
            }
            /******************** Получаем данные из простоев ********************/
            /**
             * Выргружаем данные за 4 смены одного дня по участку
             */
            $response = Assistant::GetDateTimeByShift($date, $shift_id);
            $date_start = $response['date_start'];
            $date_end = $response['date_end'];
            $stop_pbs = StopPb::find()
                ->innerJoinWith('stopPbEquipments')
                ->innerJoinWith('stopPbEvents')
                ->where(['stop_pb.company_department_id' => $company_department_id])
                ->andWhere(['or',
                    ['and',
                        'stop_pb.date_time_start >= \'' . $date_start . '\'',
                        'stop_pb.date_time_start <= \'' . $date_end . '\''
                    ],
                    ['and',
                        'stop_pb.date_time_end >= \'' . $date_start . '\'',
                        'stop_pb.date_time_end <= \'' . $date_end . '\''
                    ]
                ])
                ->all();

            /******************** Формируем массив простоев ********************/
            if (!empty($stop_pbs)) {
                foreach ($stop_pbs as $stop_pb) {
                    $stop_pb_id = $stop_pb->id;
                    $kind_stop_pb_id = $stop_pb->kind_stop_pb_id;
                    $place_id = $stop_pb->place_id;
                    $place_title = $stop_pb->place->title;
                    $date_time_start = $stop_pb->date_time_start;
                    $date_time_end = $stop_pb->date_time_end;
                    $description = $stop_pb->description;
                    $type_operation_id = $stop_pb->type_operation_id;
                    if ($stop_pb->operation_id == null) {
                        $operation_id = null;
                        $operation_title = '';
                    } else {
                        $operation_id = $stop_pb->operation_id;
                        $operation_title = $stop_pb->operation->title;
                    }
                    $counter_evetns = 0;
                    foreach ($stop_pb->stopPbEvents as $stopPbEvent) {
                        $events[$stop_pb_id][$counter_evetns]['event_id'] = $stopPbEvent->event_id;
                        $counter_evetns++;
                    }
                    foreach ($stop_pb->stopPbEquipments as $stopPbEquipment) {
                        $equipment_id = $stopPbEquipment->equipment_id;
                        $stop_pb_data[$equipment_id][$stop_pb_id]['stop_pb_id'] = $stop_pb_id;
                        // $date_time_start - начало простоя
                        // $date_time_end - окончание простоя
                        // $date_start - начало смены
                        // $date_end - окончание смены
//                        if ($shift_id != null) {
//                            if ($date_time_start < $date_start) {
//                                $date_time_start = $date_start;
//                            }
//                            if ($date_time_end > $date_end) {
//                                $date_time_end = date('Y-m-d H:i:s', strtotime($date_end . '+1 min'));
//                            }
//                        }
                        $stop_pb_data[$equipment_id][$stop_pb_id]['date_time_start'] = $date_time_start;
                        $stop_pb_data[$equipment_id][$stop_pb_id]['date_time_end'] = $date_time_end;
                        $stop_pb_data[$equipment_id][$stop_pb_id]['type_operation_id'] = $type_operation_id;
                        $stop_pb_data[$equipment_id][$stop_pb_id]['kind_stop_pb_id'] = $kind_stop_pb_id;
                        $stop_pb_data[$equipment_id][$stop_pb_id]['place_id'] = $place_id;
                        $stop_pb_data[$equipment_id][$stop_pb_id]['place_title'] = $place_title;
                        $stop_pb_data[$equipment_id][$stop_pb_id]['description'] = $description;
                        $stop_pb_data[$equipment_id][$stop_pb_id]['operation_id'] = $operation_id;
                        $stop_pb_data[$equipment_id][$stop_pb_id]['operation_title'] = $operation_title;
                        $stop_pb_data[$equipment_id][$stop_pb_id]['stop_pb_status'] = false;
                        if (isset($events[$stop_pb_id]) && !empty($events[$stop_pb_id])) {
                            $stop_pb_data[$equipment_id][$stop_pb_id]['events'] = $events[$stop_pb_id];
                        } else {
                            $stop_pb_data[$equipment_id][$stop_pb_id]['events'] = (object)array();
                        }
                    }
                }
                unset($stop_pbs, $stop_pb, $stopPbEvent, $stopPbEquipment, $events);
            }
            /******************** ПОИСК ПЛАНОГРАММ ********************/
            if ($shift_id == 5) {
                $shift_id = null;
            }
            $order_planogramms = Order::find()
                ->joinWith('planogrammas.equipment')
                ->joinWith('planogrammas.planogrammOperations.typeOperation')
                ->where(['IN', 'order.id', $order_arr])
                ->andFilterWhere(['order.shift_id' => $shift_id])
                ->orderBy('planogramma.date_time_start')
                ->all();
            $warnings[] = $shift_id;
            $warnings[] = $order_planogramms;
            if (isset($order_planogramms)) {
                /******************** ПЕРЕБОР НАРЯДОВ ********************/
                foreach ($order_planogramms as $order_planogramma) {
                    if (!empty($order_planogramma->planogrammas)) {
                        /******************** ПЕРЕБОРА ПЛАНОГРАММ ********************/
                        foreach ($order_planogramma->planogrammas as $planogramm_order) {
                            $planogramm_type_id = $planogramm_order->cyclegramm_type_id;
                            $equipment_id = $planogramm_order->equipment_id;
                            $planogramm_id = $planogramm_order->id;
                            $planogramm[$planogramm_type_id]['planogramms'][$planogramm_id]['planogramm_option']['planogramm_id'] = $planogramm_id;
                            $planogramm[$planogramm_type_id]['planogramms'][$planogramm_id]['planogramm_option']['date_time_start'] = $planogramm_order->date_time_start;
                            $planogramm[$planogramm_type_id]['planogramms'][$planogramm_id]['planogramm_option']['date_time_end'] = $planogramm_order->date_time_end;
                            $planogramm[$planogramm_type_id]['planogramms'][$planogramm_id]['planogramm_option']['cyclegramm_type_id'] = $planogramm_order->cyclegramm_type_id;
                            $planogramm[$planogramm_type_id]['planogramms'][$planogramm_id]['planogramm_option']['equipment_id'] = $equipment_id;
                            $planogramm[$planogramm_type_id]['planogramms'][$planogramm_id]['planogramm_option']['equipment_title'] = $planogramm_order->equipment->title;
                            $planogramm[$planogramm_type_id]['planogramms'][$planogramm_id]['planogramm_operation'] = array();
                            $planogramm[$planogramm_type_id]['planogramms'][$planogramm_id]['stop_pb'] = array();
                            /******************** ПЕРЕБОР ОПЕРАЦИЙ ПЛАНОГРАММ ********************/
                            foreach ($planogramm_order->planogrammOperations as $planogrammOperation) {
                                $planogramm_operation_id = $planogrammOperation->id;
                                $planogramm[$planogramm_type_id]['planogramms'][$planogramm_id]['planogramm_operation'][$planogramm_operation_id]['planogramm_operation_id'] = $planogramm_operation_id;
                                $planogramm[$planogramm_type_id]['planogramms'][$planogramm_id]['planogramm_operation'][$planogramm_operation_id]['type_operation_id'] = $planogrammOperation->type_operation_id;
                                $planogramm[$planogramm_type_id]['planogramms'][$planogramm_id]['planogramm_operation'][$planogramm_operation_id]['type_operation_title'] = $planogrammOperation->typeOperation->title;
                                $planogramm[$planogramm_type_id]['planogramms'][$planogramm_id]['planogramm_operation'][$planogramm_operation_id]['date_time_start'] = $planogrammOperation->date_time_start;
                                $planogramm[$planogramm_type_id]['planogramms'][$planogramm_id]['planogramm_operation'][$planogramm_operation_id]['date_time_end'] = $planogrammOperation->date_time_end;
                            }
                            if (empty($planogramm[$planogramm_type_id]['planogramms'][$planogramm_id]['planogramm_operation'])) {
                                $planogramm[$planogramm_type_id]['planogramms'][$planogramm_id]['planogramm_operation'] = (object)array();
                            }
                            /******************** ПОИСК ОСТАНОВОК ПЛАНОГРАММ ********************/
                            if (isset($stop_pb_data[$equipment_id]) && !empty($stop_pb_data[$equipment_id])) {
                                /******************** Заполнить массив приостановок ********************/
                                if ($planogramm_type_id == 2) {
                                    $planogramm[$planogramm_type_id]['planogramms'][$planogramm_id]['stop_pb'] = $stop_pb_data[$equipment_id];
                                }
                            }
                            if (empty($planogramm[$planogramm_type_id]['planogramms'][$planogramm_id]['stop_pb'])) {
                                $planogramm[$planogramm_type_id]['planogramms'][$planogramm_id]['stop_pb'] = (object)array();
                            }
                        }
                    }
                }
            }
            if (isset($planogramm)) {
                $result = $planogramm;
            } else {
                $result = (object)array();
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetPlanogramma. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetPlanogramma. Конец метода';

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SavePlanogramm() - Сохранение планограммы
     * @param null $data_post
     * @return array
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * planogramm_data - данные планограммы
     * company_department_id - идентификатор участка
     * order_id - идентификатор наряда на который сохраняем планограмму
     * date - дата
     * shift_id - идентификатор смены
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * (стандартный массив данных)
     *
     * @package frontend\controllers\ordersystem
     *
     * @example
     *
     * АЛГОРИТМ РАБОТЫ МЕТОДА:
     * 1. Получить планограмму по идентификатору наряда
     *      получено?       Изменяем данными которые пришли
     *      не получено?    Создаём новую планограмму
     * 2. Удаляем все операции планограммы
     * 3. Перебор операций планограммы
     *      3.1 Тип операции не равен 8 (приостановка работ)
     *              равно?      Добавляем в массив простоев
     *              не равно?   Добавляем в массив на сохранение операций планограмму
     * 4. Конец перебора
     * 5. Массив на добавление операций планограмм не пуст
     *      пуст?       Пропустить
     *      не пуст?    Массово добавить в базу все операции планограммы
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 19.03.2020 15:15
     */
    public static function SavePlanogramm($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'SavePlanogramm';
        $planogramm_data = array();                                                                                // Промежуточный результирующий массив
        $session = Yii::$app->session;
        $warnings[] = $method_name . '. Начало метода';
        //$data_post = '{"planogramm_data":{"1":{"planogramms":{"-1":{"planogramm_option":{"date_time_start":"2020-03-19 10:00","date_time_end":"2020-03-19 16:00","cyclegramm_type_id":1,"equipment_id":186730,"equipment_title":"SL-300 (BUK_42)","planogramm_id":-1},"planogramm_operation":{"-1":{"planogramm_operation_id":-1,"type_operation_id":1,"date_time_start":"2020-03-19 10:00","date_time_end":"2020-03-19 16:00"}},"stop_pb":{}},"-2":{"planogramm_option":{"date_time_start":"2020-03-19 10:00","date_time_end":"2020-03-19 16:00","cyclegramm_type_id":1,"equipment_id":141498,"equipment_title":"2KM-138","planogramm_id":-2},"planogramm_operation":{"-2":{"planogramm_operation_id":-2,"type_operation_id":1,"date_time_start":"2020-03-19 10:00","date_time_end":"2020-03-19 16:00"}},"stop_pb":{}}}},"2":{"planogramms":{"-1":{"planogramm_option":{"date_time_start":"2020-03-19 10:00","date_time_end":"2020-03-19 16:00","cyclegramm_type_id":2,"equipment_id":186730,"equipment_title":"SL-300 (BUK_42)","planogramm_id":-1},"planogramm_operation":{"-1":{"planogramm_operation_id":-1,"type_operation_id":1,"date_time_start":"2020-03-19 10:00","date_time_end":"2020-03-19 16:00"}},"stop_pb":{"-1":{"stop_pb_id":-1,"date_time_start":"2020-03-19 11:00","date_time_end":"2020-03-19 12:00","type_operation_id":8,"kind_stop_pb_id":2,"place_id":26387,"place_title":"Аварийный ВМП","description":"1111","operation_id":112,"operation_title":"Обслуживание конвейера, контроль работы","events":[{"event_id":"7126"}]},"-2":{"stop_pb_id":-2,"date_time_start":"2020-03-19 12:00","date_time_end":"2020-03-19 15:00","type_operation_id":8,"kind_stop_pb_id":1,"place_id":601432,"place_title":"Дизельное депо","description":"2222","operation_id":114,"operation_title":"Обмывка выработки, пересыпов. Осланцевание","events":[{"event_id":"7126"}]}}},"-2":{"planogramm_option":{"date_time_start":"2020-03-19 10:00","date_time_end":"2020-03-19 16:00","cyclegramm_type_id":2,"equipment_id":141498,"equipment_title":"2KM-138","planogramm_id":-2},"planogramm_operation":{"-2":{"planogramm_operation_id":-2,"type_operation_id":1,"date_time_start":"2020-03-19 10:00","date_time_end":"2020-03-19 16:00"}},"stop_pb":{"-1":{"stop_pb_id":-1,"date_time_start":"2020-03-19 11:10","date_time_end":"2020-03-19 12:34","type_operation_id":8,"kind_stop_pb_id":5,"place_id":6184,"place_title":"Порож. ветвь кл. ств. 3 гор.","description":"212312","operation_id":112,"operation_title":"Обслуживание конвейера, контроль работы","events":[{"event_id":"1"}]}}}}}},"company_department_id":20028748,"shift_id":"2","date":"2020-03-19"}';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'planogramm_data') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'delete_planogramm_ids') ||
                !property_exists($post_dec, 'order_id') ||
                !property_exists($post_dec, 'shift_id')
            )                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $planogramm_data = $post_dec->planogramm_data;
            $company_department_id = $post_dec->company_department_id;
            $order_id = $post_dec->order_id;
            $shift_id = $post_dec->shift_id;
            $delete_planogramm_ids = $post_dec->delete_planogramm_ids;

            Planogramma::deleteAll(['id' => $delete_planogramm_ids]);

            foreach ($planogramm_data as $planogramm_type_id => $planogramm_by_type) {
                if (!empty($planogramm_by_type->planogramms)) {
                    $warnings[] = $method_name . ". Тип сохраняемой планограммы: " . $planogramm_type_id;
                    foreach ($planogramm_by_type->planogramms as $planogramm) {
                        $add_planogramm = Planogramma::findOne(['id' => $planogramm->planogramm_option->planogramm_id]);
                        if (!$add_planogramm) {
                            $add_planogramm = new Planogramma();
                        }
                        $add_planogramm->order_id = $order_id;
                        $add_planogramm->date_time_start = date("Y-m-d H:i:s", strtotime($planogramm->planogramm_option->date_time_start));
                        $add_planogramm->date_time_end = date("Y-m-d H:i:s", strtotime($planogramm->planogramm_option->date_time_end));
                        $add_planogramm->cyclegramm_type_id = $planogramm->planogramm_option->cyclegramm_type_id;
                        $add_planogramm->equipment_id = $planogramm->planogramm_option->equipment_id;
                        if (!$add_planogramm->save()) {
                            $errors[] = $add_planogramm->errors;
                            throw new Exception($method_name . '. Ошибка при сохранении планограммы');
                        }
                        $add_planogramm->refresh();
                        $planogramm_id = $add_planogramm->id;
                        $equipment_id = $add_planogramm->equipment_id;
                        $warnings[] = $method_name . ". Ключ сохраняемой планограммы: " . $planogramm_id;
                        unset($add_planogramm);

                        /******************** ДОБАВЛЯЕМ ДАННЫЕ ОПЕРАЦИИ ПЛАНОГРАММЫ ********************/
                        $del_planogramm_operations = PlanogrammOperation::deleteAll(['planogramma_id' => $planogramm_id]);
                        foreach ($planogramm->planogramm_operation as $planogramm_operation) {
                            $planogramm_operation_data[] = [
                                date("Y-m-d H:i:s", strtotime($planogramm_operation->date_time_start)),
                                date("Y-m-d H:i:s", strtotime($planogramm_operation->date_time_end)),
                                $planogramm_operation->type_operation_id,
                                $planogramm_id,
                            ];
                        }
                        /******************** ДОБАВЛЯЕМ ДАННЫЕ ПРОСТОЯ ********************/
                        foreach ($planogramm->stop_pb as $stop_pbs) {
                            if ($stop_pbs->stop_pb_status) {
                                $del_stop_pb = StopPb::deleteAll(['id' => $stop_pbs->stop_pb_id]);
                            } else {
                                $new_stop_pb = StopPb::findOne(['id' => $stop_pbs->stop_pb_id]);
                                if (!$new_stop_pb) {
                                    $new_stop_pb = new StopPb();
                                }
                                $new_stop_pb->kind_stop_pb_id = $stop_pbs->kind_stop_pb_id;
                                $new_stop_pb->place_id = $stop_pbs->place_id;
                                $new_stop_pb->date_time_start = date('Y-m-d H:i:s', strtotime($stop_pbs->date_time_start));
                                $new_stop_pb->date_time_end = date('Y-m-d H:i:s', strtotime($stop_pbs->date_time_end));
                                $new_stop_pb->worker_id = $session['worker_id'];
                                $new_stop_pb->description = $stop_pbs->description;
                                $new_stop_pb->type_operation_id = $stop_pbs->type_operation_id;
                                $new_stop_pb->xyz = '0.0,0.0,0.0';
                                $new_stop_pb->company_department_id = $company_department_id;
                                $new_stop_pb->operation_id = $stop_pbs->operation_id;
                                if (!$new_stop_pb->save()) {
                                    $errors[] = $new_stop_pb->errors;
                                    throw new Exception($method_name . '. Ошибка при сохранении простоя');
                                }
                                $new_stop_pb->refresh();
                                $stop_pb_id = $new_stop_pb->id;
                                unset($new_stop_pb);
                                $del_events = StopPbEvent::deleteAll(['stop_pb_id' => $stop_pb_id]);
                                $del_quipments = StopPbEquipment::deleteAll(['stop_pb_id' => $stop_pb_id]);
                                /******************** ДОБАВЛЯЕМ ДАННЫЕ СВЯЗКИ ПРОСТОЯ И ОБОРУДОВАНИЯ ********************/
                                $new_stop_equipment = new StopPbEquipment();
                                $new_stop_equipment->stop_pb_id = $stop_pb_id;
                                $new_stop_equipment->equipment_id = $equipment_id;
                                if (!$new_stop_equipment->save()) {
                                    $errors[] = $new_stop_equipment->errors;
                                    throw new Exception($method_name . '. Ошибка при сохранении связки простоя и оборудования');
                                }
                                if (isset($stop_pbs->events) && !empty($stop_pbs->events)) {
                                    foreach ($stop_pbs->events as $event) {
                                        $stop_pb_events[] = [$stop_pb_id, $event->event_id];
                                    }
                                }

                            }
                        }

                    }
                }
            }
            unset($planogramm_data);
            /******************** СОХРАНЕНИЕ МАССИВА ОПЕРАЦИЙ ПЛАНОГРАММ ********************/
            if (isset($planogramm_operation_data) && !empty($planogramm_operation_data)) {
                $insert_planogramm_operations = Yii::$app->db->createCommand()->batchInsert('planogramm_operation', [
                    'date_time_start',
                    'date_time_end',
                    'type_operation_id',
                    'planogramma_id'
                ], $planogramm_operation_data)->execute();
                if ($insert_planogramm_operations == 0) {
                    throw new Exception($method_name . '. Ошибка при сохранении операций планограмм');
                }
            }
            unset($planogramm_operation_data);
            /******************** СОХРАНЕНИЕ МАССИВ ПРИОСТАНОВОК РАБОТ ********************/
            if (isset($stop_pb_events) && !empty($stop_pb_events)) {
                $insert_stop_pbs_events = Yii::$app->db->createCommand()->batchInsert('stop_pb_event', [
                    'stop_pb_id',
                    'event_id'
                ], $stop_pb_events)->execute();
                if ($insert_stop_pbs_events == 0) {
                    throw new Exception($method_name . '. Ошибка при сохранении приостновок');
                }
            }
            unset($stop_pb_events);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }
}
