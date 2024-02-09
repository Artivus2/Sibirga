<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\ordersystem;

use backend\controllers\Assistant as BackendAssistant;
use backend\controllers\cachemanagers\WorkerCacheController;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\DocumentationController;
use frontend\controllers\handbooks\DepartmentController;
use frontend\controllers\handbooks\InjunctionController;
use frontend\controllers\industrial_safety\CheckingController;
use frontend\controllers\notification\NotificationController;
use frontend\controllers\positioningsystem\RouteController;
use frontend\controllers\system\LogAmicumFront;
use frontend\controllers\WebsocketController;
use frontend\models\Brigade;
use frontend\models\Chane;
use frontend\models\Checking;
use frontend\models\CompanyDepartment;
use frontend\models\CompanyDepartmentWorkerVgk;
use frontend\models\CorrectMeasures;
use frontend\models\Document;
use frontend\models\DocumentWithEcp;
use frontend\models\GraficTabelMain;
use frontend\models\Injunction;
use frontend\models\InjunctionStatus;
use frontend\models\Material;
use frontend\models\Mine;
use frontend\models\OperationWorker;
use frontend\models\Order;
use frontend\models\OrderHistory;
use frontend\models\OrderInstructionPb;
use frontend\models\OrderItemWorkerVgk;
use frontend\models\OrderOperation;
use frontend\models\OrderOperationPlaceStatusVtbAb;
use frontend\models\OrderOperationPlaceVtbAb;
use frontend\models\OrderOperationWorker;
use frontend\models\OrderOperationWorkerStatus;
use frontend\models\OrderPlace;
use frontend\models\OrderPlaceReason;
use frontend\models\OrderPlaceVtbAb;
use frontend\models\OrderRouteWorker;
use frontend\models\OrderStatus;
use frontend\models\OrderStatusAttachment;
use frontend\models\OrderTemplate;
use frontend\models\OrderTemplateInstructionPb;
use frontend\models\OrderTemplateOperation;
use frontend\models\OrderTemplatePlace;
use frontend\models\OrderVtbAb;
use frontend\models\OrderWorkerCoordinate;
use frontend\models\OrderWorkerVgk;
use frontend\models\RestrictionOrder;
use frontend\models\TemplateOrderVtbAb;
use frontend\models\Worker;
use Throwable;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\Controller;

class OrderSystemController extends Controller
{

    // Лежит в другом контроллере, Но используется в данной форме:
    // WorkSchedule\GetShiftList                - метод получения списка смен.
    // WorkSchedule\ListWorkerCompanyDepartment - Этот метод получает список работников по переданному идентификатору (company_department_id) применяется на вкладке персонала
    // WorkSchedule\GetListBrigade              - Метод получения списка бригад по конкретному департаменту, если задан, то вернет весь список актуальных бригад
    // HandbookOperation\GetListGroupOperation  - Метод получения списка групп операций из справочника операций
    // HandbookPlace\GetListKindPlace           - Метод получения списка мест сгруппированных по видам мест и шахтам
    // HandbookEmployeeController\GetWorkerGroupByCompanyDepartment    -   Метод получения списка людей сгруппированных по департаментам (департаменты отдельно, люди отдельно)

    // Находится здесь:
    /** Угольные шахты */
    // SaveNewOrder                             - Метод сохранения наряда написан Рудовым и ко
    // SaveOrderFromTableForm                   - метод сохранения нового наряда со страницы табличная форма выдачи наряда - переписан Якимовым
    // GetOrder                                 - метод получения данных для экранной формы "Выдача наряда"
    // GetOrderTable                            - правильный метод получения наряда из БД по департаменту, дате, смене
    // GetListWorkersByLastGraficShift          - Метод получения списка рабочих на смене на основе последнего графика выходов на заданный день месяц и год
    // GetListWorkersByLastGraficShiftMobile    - Метод получения списка рабочих на смену по графику для мобильной версии
    // GetWorkersVgk                            - Метод получения списка работников с пометкой ВГК, бригадиров, горного мастера
    // GetInjunctionByCompanyDepartment         - Метод получения списка актуальных предписаний выданных на участок
    // GetInjunctionsByCompanyDepartment        - Возвращает все предписания и информацию о них, по участку
    // GetInjunctionsIds                        - получить список ключей предписаний участка
    // GetInjunctionsByDate                     - Метод получения списка предписаний по дате, по департаменту и по смене
    // GetBrigadesByDepartment                  - Метод получения списка бригад по участку
    // CorrectOrder                             - Метод корректировка наряда
    // GetRouteMap                              - Метод получения данных для маршрутной карты нарядов
    // SaveOrderVtbAb                           - Сохранение наряда ВТБ АБ
    // GetOrdersVtbAb                           - Метод получения нарядов АБ ВТБ по дате по всей шахте под которой (с учётки залогиненного пользователя)
    // CorrectOrderVtbAb                        - Метод корректировки наряда ВТБ АБ
    // GetOrderVtbAb                            - Метод получения информации о наряд ВТБ АБ
    // GetRouteMapByOrder                       - Метод получает данные для конкретного наряда, по дате и смене
    // ChangeStatusWorkerInOrder                - Метод смены статуса работника на "Принял"
    // GetWorkersAttendanceInShift              - Возвращает количество работников по профессиям и сколько людей под землёй
    // ChangeWorkerValueOutgoing                - Устанавливает/меняет фактического значение выхода людей из шахты по идентификатору наряда
    // GetWorkersVgkByCompanyDepartment         - Получение ВГК по участку и дате назначения
    // GetTemplateOrderList                     - Метод получения списка шаблонов нарядов на производство работ
    // SaveTemplateOrderList                    - Метод сохранения шаблона наряда на производство работ
    // ChangeOrderStatus                        - Метод смены статуса наряда
    // SaveInstructions                         - Метод сохранения инструктажей
    // GetEmptyOrders                           - По дате получает все наряды за предыдущий день и формирует пустышку
    // DeleteOrderVtbAb                         - Метод удаления наряда АБ ВТБ
    // SaveTemplateOrderVtbAb                   - Метод сохранения шаблона наряда АБ ВТБ
    // GetTemplateOrderVtbAb                    - Получение списка шаблонов по участку
    // DeleteTemplateOrderVtbAb                 - Удаление шаблона наряда АБ ВТБ
    // GetOrdersForMatching                     - Метод получения данных для формы согласования наряда АБ ВТБ
    // SaveStatusOrderFromTableForm             - сохранение статуса наряда (утверждение, согласование)
    // AcceptOrder                              - метод получения данных с фронта по подписи данных с нарядной
    // GetFavouritesBriefing                    - получить список избранных инструктажей по участку
    // GetFavouritesPlace                       - получить список избранных мест по участку
    // GetFavouritesRouteTemplate               - получить список избранных шаблонов маршрута по заданному участку
    // GetLimitFromOrders                       - Ограничения по наряду для журнала ограничений наряда
    // GetOrderOperationsReason                 - Получение данных для журнала причин невыполнения наряда
    // CheckOperationVtbInOrder                 - метод проверки наличия операций ВТБ в выданных нарядах.
    // CheckPlaceVtbInOrder                     - метод проверки наличия места ВТБ в выданных нарядах.
    // SaveDocumentWithEcp                      - Сохранение документа с электронной подписью
    // GetDocumentWithEcp()                     - Получение бинарных данных документа с электронной подписью по document_id


    const STATUS_ACTUAL = 1;                                                                                            // Актуально
    const STATUS_INACTUAL = 19;                                                                                         // Не актуально

    const WORK_TIME = 1;                                                                                                // Тип рабочего времени, рабочий день

    const VGK_STATE = 1;                                                                                                // статус ВГК

    //    const BRIGADER_ROLE = 182;
    const GORN_MASTER_ROLE = 181;           // горный мастер
    const GORN_MASTER_ROLE_SOUT = 237;      // горный мастер
    const EL_MECH_ROLE = 195;               // электро механик участка
    const ZAM_MECH_ROLE = 179;              // зам механика участка

    //About order
    const OBJ_ORDER = 24;

    const ORDER_AGREEMENT = 2;              // согласование наряда
    const ORDER_APPROVALES = 3;             // утверждение наряда
    const ORDER_AGREED = 4;                 // наряд согласован
    const ORDER_ISSUED = 5;                 // наряд выдан
    const ORDER_APPROVAL = 6;               // наряд утвержден
    const ORDER_CORRECTED = 7;              // наряд скорректирован
    const ORDER_WAS_PASSED = 8;             // отчет сдан
    const ORDER_DELETED = 9;                // наряд удален
    const WORKER_ACCEPT_ORDER = 49;         // наряд принял
    const ORDER_CREATED = 50;               // наряд создан
    const OPERATION_CREATED = 51;           // операция создана
    const ORDER_NOT_AGREED_AB = 61;         // Наряд отклонен АБ
    const ORDER_NOT_AGREED_RVN = 10;        // Наряд отклонен РВН

    const UNDERGROUND = 2;

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Название метода: GetOrder
     * Назначение метода: метод получения данных для экранной формы "Выдача наряда"
     *
     * СТРУКТУРА: ListWorkersByGrafic
     *                      [worker_id]
     *                              worker_id           -       ключ работника из графика выходов
     *                              role_id             -       роль/профессия из графика выходов
     *            department_order
     *                      [brigade_id]
     *                              brigade_id
     *                              order
     *                                 [order_id]
     *                                      order_id
     *                                      title
     *                                      company_department_id
     *                                      order_date_time
     *                                      shift_id
     *                                      chane_id
     *                                      order_places
     *                                          [order_place_id]
     *                                                  order_place_id
     *                                                  place_id
     *                                                  passport_id
     *                                                  operation_production
     *                                                              [order_operation_worker_id]
     *                                                                             order_operation_worker_id (order_operation_worker_id)
     *                                                                             operation_id
     *                                                                             equipment_id
     *                                                                             operation_groups
     *                                                                                      [group_operation_id]
     *                                                                                                 operation_group_id
     *                                                                             operation_value_plan
     *                                                                             operation_value_fact
     *                                      order_workers
     *                                              [worker_id]
     *                                                    worker_id
     *                                                    operation_production
     *                                                              [order_operation_worker_id]
     *                                                                          order_operation_worker_id:
     *                                                                          status
     *                                                                                status_id_last
     *                                                                                status_id_all
     *                                                                                        [order_operation_worker_status_id]
     *                                                                                                          operation_status_id
     *                                                                                                          status_id
     *                                                                                                          status_date_time
     *                                                                                                          worker_id
     *
     *
     * Входные обязательные параметры:
     *      ListWorkersByGrafic - список работников по актуальному графику выходов
     *      Order               - сам наряд
     * @package frontend\controllers\ordersystem
     * $post['company_department_id'] - ИД
     * $post['date_time'] - дата и время
     * $post['shift_id'] - смена
     * $post['brigade_id'] - ИД бригады
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetOrder&subscribe=&data={%22company_department_id%22:4029831,%22date_time%22:%222019-06-27%22,%22shift_id%22:1,%22brigade_id%22:%22348%22}
     *
     * @author Якимов М.Н. <ooy@pfsz.ru>
     * Created date: on 13.06.2019 10:31
     */
    public static function GetOrder($data_post = NULL)
    {
        $status = 1;
        $orders_result = array();
        $order = (object)array();
        $errors = array();
        $warnings = array();

        try {
            /**
             * блок проверки наличия входных даных от readManager
             */
            if (!is_null($data_post) and $data_post != "") {
                $warnings[] = "GetOrder. данные успешно переданы";
                $post = json_decode($data_post);
                $warnings[] = "GetOrder. Входной массив данных" . $data_post;
            } else {
                throw new Exception("GetOrder. Входной массив данных post не передан");
            }
            /**
             * блок проверки входных условий с фронта
             */
            if (property_exists($post, 'company_department_id') and
                property_exists($post, 'date_time') and
                property_exists($post, 'mine_id') and
                property_exists($post, 'shift_id') and
                $post->company_department_id != '' &&
                $post->date_time != '' &&
                $post->mine_id != '' &&
                $post->shift_id != '') {
                $warnings[] = "GetOrder. Получил все входные параметры";
                $company_department_id = $post->company_department_id;
                $date_time = date("Y-m-d", strtotime($post->date_time));
                $mine_id = $post->mine_id;
                $shift_id = $post->shift_id;
            } else {
                throw new Exception("GetOrder. Входные параметры не переданы");
            }
            /**
             * блок список людей по плановому графику выходов
             */
            $response = self::GetListWorkersByLastGraficShift($company_department_id, $date_time, $shift_id);
            $orders_result['ListWorkersByGrafic'] = (object)array();
            if ($response['status'] == 1) {
                if (!empty($response['Items'])) {
                    $orders_result['ListWorkersByGrafic'] = $response['Items'];
                }
                if (count($response['errors']) != 0) {
                    $errors[] = $response['errors'];
                }
                $warnings[] = $response['warnings'];
            } else {
                if (count($response['errors']) != 0) {
                    $errors[] = $response['errors'];
                }
                $warnings[] = $response['warnings'];

                throw new Exception("GetOrder. Ошибка получения списка людей на смену на основе графика выходов");
            }
            /**
             * Блок формирования типа участка
             */
            $company_departments_type = CompanyDepartment::find()
                ->select(['company_department.department_type_id'])
                ->where(['company_department.id' => $company_department_id])
                ->limit(1)
                ->one();
            /**
             * блок формирования наряда
             */
            $orders = Order::find()
                ->joinWith('orderWorkerVgks')
                ->joinWith('orderPlaces')
                ->joinWith('orderPlaces.orderPlaceReasons')
                ->joinWith('orderPlaces.orderOperations.operationWorkers')
                ->joinWith('orderPlaces.orderOperations.operationWorkers.worker.workerObjects')
                ->joinWith('orderPlaces.orderOperations.operationWorkers.orderOperationWorkerStatuses')
                ->joinWith('orderPlaces.orderOperations.operation.operationGroups')
                ->joinWith('orderInstructionPbs')
                ->where([
                    'order.company_department_id' => $company_department_id,
                    'shift_id' => $shift_id,
                    'date_time_create' => $date_time
                ])
                ->all();
//			Assistant::PrintR($orders, 1);
            $orders_result['department_order'] = (object)array();
            if ($orders) {
                $warnings[] = "GetOrder. Получил данные из БД";
                $order = array();
//                $found_injunctions = Injunction::find()
//                    ->where(['company_department_id' => $company_department_id])
//                    ->andWhere(['IN', 'injunction.status_id', [57, 58, 60]])
//                    ->limit(50000);
                $order_status_last = Order::find()
                    ->joinWith('lastOrderStatuses')
                    ->where([
                        'order.company_department_id' => $company_department_id,
                        'order.shift_id' => $shift_id,
                        'order.date_time_create' => $date_time
                    ])
                    ->limit(1)
                    ->one();//FIXME переделать потом
                foreach ($orders as $order_item) {
                    /**
                     * Для каждого наряда получаем данные и создаем отдельный массив
                     */
                    $order_id = $order_item->id;
                    $brigade_id = $order_item->brigade_id;
                    $chane_id = $order_item->chane_id;
                    $order[$chane_id]['brigade_id'] = $brigade_id;
                    $order[$chane_id]['chane_id'] = $order_item->chane_id;
                    $order[$chane_id]['order']['order_id'] = (string)$order_id;
                    $order[$chane_id]['order']['worker_value_outgoing'] = $order_item->worker_value_outgoing;

                    $order[$chane_id]['order']['order_status_id'] = $order_status_last->lastOrderStatuses->status_id ? $order_status_last->lastOrderStatuses->status_id : null;
                    $order[$chane_id]['order']['title'] = $order_item->title;
                    $order[$chane_id]['order']['company_department_id'] = $order_item->company_department_id;

                    $order[$chane_id]['order']['department_type_id'] = $company_departments_type->department_type_id;
                    $order[$chane_id]['order']['order_date_time'] = $order_item->date_time_create;
                    $order[$chane_id]['order']['shift_id'] = $order_item->shift_id;
                    $order[$chane_id]['order']['chane_id'] = $order_item->chane_id;
                    $order[$chane_id]['order']['order_places'] = array();
                    $order[$chane_id]['order']['order_workers'] = array();
                    /**
                     * Блок с предписаниями
                     */
//                    foreach ($found_injunctions->each() as $found_injunction) {
//                        $JSON_for_info_about_inj = '{"injunction_id":' . $found_injunction->id . '}';
//                        $info_about_inj = CheckingController::GetInfoAboutInjunction($JSON_for_info_about_inj);
//                        $injunctions_info[$found_injunction->id] = $info_about_inj['Items'];
//
//                    }
//                    $order[$chane_id]['order']['injunctions'] = $injunctions_info;
                    /**
                     * Блок с инстрктажами
                     */
                    if (empty($order_item->orderInstructionPbs)) {
                        $order[$chane_id]['order']['order_instructions'] = (object)array();
                    } else {
                        foreach ($order_item->orderInstructionPbs as $order_instruction_pb) {
                            $order[$chane_id]['order']['order_instructions'][$order_instruction_pb->id]['order_instruction_id'] = $order_instruction_pb->id;
                            $order[$chane_id]['order']['order_instructions'][$order_instruction_pb->id]['instruction_pb_id'] = $order_instruction_pb->instruction_pb_id;
                        }
                    }


                    /**
                     * блок заполнения мест выдачи наряда
                     * ord - сокращенное order
                     */
                    if (empty($order_item->orderPlaces)) {
                        $order[$chane_id]['order']['order_places'] = (object)array();
                    } else {
                        foreach ($order_item->orderPlaces as $ord_place) {
                            $ord_place_id = $ord_place->id;
                            $order[$chane_id]['order']['order_places'][$ord_place_id]['order_place_id'] = $ord_place_id;
                            $order[$chane_id]['order']['order_places'][$ord_place_id]['place_id'] = $ord_place->place_id;
                            if ($ord_place->edge_id) {
                                $order[$chane_id]['order']['order_places'][$ord_place_id]['edge_id'] = $ord_place->edge_id;
                            } else {
                                $order[$chane_id]['order']['order_places'][$ord_place_id]['edge_id'] = 0;
                            }
                            $order[$chane_id]['order']['order_places'][$ord_place_id]['passport_id'] = $ord_place->passport_id;
                            $order[$chane_id]['order']['order_places'][$ord_place_id]['route_template_id'] = $ord_place->route_template_id;
                            $order[$chane_id]['order']['order_places'][$ord_place_id]['coordinate'] = $ord_place->coordinate;
                            $order[$chane_id]['order']['order_places'][$ord_place_id]['description'] = $ord_place->description;
                            if ($ord_place->orderPlaceReasons) {
                                $order[$chane_id]['order']['order_places'][$ord_place_id]['reason'] = $ord_place->orderPlaceReasons['reason'];
                            } else {
                                $order[$chane_id]['order']['order_places'][$ord_place_id]['reason'] = "";
                            }
                            $order[$chane_id]['order']['order_places'][$ord_place_id]['operation_production'] = array();
                            /**
                             * блок заполенния операций
                             */
                            foreach ($ord_place->orderOperations as $order_operation) {
                                $order_operation_id = $order_operation->id;
//                                    $order[$chane_id]['order']['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['operation_worker'] = $operation_worker->id;
                                $order[$chane_id]['order']['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['operation_id'] = $order_operation->operation_id;
                                $order[$chane_id]['order']['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['equipment_id'] = $order_operation->equipment_id;
                                if ($order_operation->edge_id) {
                                    $order[$chane_id]['order']['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['edge_id'] = $order_operation->edge_id;
                                } else {
                                    $order[$chane_id]['order']['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['edge_id'] = 0;
                                }
                                $order[$chane_id]['order']['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['coordinate'] = $order_operation->coordinate;
                                $order[$chane_id]['order']['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['order_operation_id'] = $order_operation->id;
                                /**
                                 * блок заполнения групп операций - нужны для разделения операций по блокам - работы по линии АБ, работы ПК, работы  по производству
                                 */
                                $order[$chane_id]['order']['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['operation_groups'] = array();
                                foreach ($order_operation->operation->operationGroups as $operation_group) {
                                    $operation_group_id = $operation_group->group_operation_id;
                                    $order[$chane_id]['order']['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['operation_groups'][] = $operation_group_id;
                                }
                                $order[$chane_id]['order']['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['operation_value_plan'] = $order_operation->operation_value_plan;
                                $order[$chane_id]['order']['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['operation_value_fact'] = $order_operation->operation_value_fact;
                                $order[$chane_id]['order']['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['operation_load_value'] = $order_operation->operation->operation_load_value;

                                //$warnings[]=$count_operation_status;
                                foreach ($order_operation->operationWorkers as $operation_worker) {
                                    $count_operation_status = count($operation_worker->orderOperationWorkerStatuses);
                                    $worker_id = $operation_worker->worker_id;
                                    if (isset($operation_worker->orderOperationWorkerStatuses[0])) {
                                        $order[$chane_id]['order']['order_workers'][$worker_id]['operation_production'][$order_operation_id]['status']['status_id_last'] = $operation_worker->orderOperationWorkerStatuses[$count_operation_status - 1]->status_id;
                                        /**
                                         * список всех статусов операции и кто их установил в выпадашку
                                         */
                                        foreach ($operation_worker->orderOperationWorkerStatuses as $operation_status) {//TODO тут сделать уже по воркерам
                                            $order[$chane_id]['order']['order_workers'][$worker_id]['operation_production'][$order_operation_id]['status']['status_id_all'][$operation_status->id]['operation_status_id'] = $operation_status->id;
                                            $order[$chane_id]['order']['order_workers'][$worker_id]['operation_production'][$order_operation_id]['status']['status_id_all'][$operation_status->id]['status_id'] = $operation_status->status_id;
                                            $order[$chane_id]['order']['order_workers'][$worker_id]['operation_production'][$order_operation_id]['status']['status_id_all'][$operation_status->id]['status_date_time'] = $operation_status->date_time;
                                            $order[$chane_id]['order']['order_workers'][$worker_id]['operation_production'][$order_operation_id]['status']['status_id_all'][$operation_status->id]['worker_id'] = $operation_status->worker_id;
                                        }
                                    }

                                    /**
                                     * формируем блок список работ по каждому работнику
                                     */
                                    $order[$chane_id]['order']['order_workers'][$worker_id]['worker_id'] = $worker_id;
                                    $order[$chane_id]['order']['order_workers'][$worker_id]['type_skud'] = 0;           // статус входа на предприятие через систему СКУД
                                    $order[$chane_id]['order']['order_workers'][$worker_id]['worker_role_id'] = $operation_worker->role_id;
                                    $order[$chane_id]['order']['order_workers'][$worker_id]['operation_list'][$order_operation->operation_id] = $order_operation->operation_id;
                                    $order[$chane_id]['order']['order_workers'][$worker_id]['operation_production'][$order_operation_id]['order_place_id'] = $ord_place_id;
                                    $order[$chane_id]['order']['order_workers'][$worker_id]['operation_production'][$order_operation_id]['order_operation_id'] = $order_operation_id;
                                    $order[$chane_id]['order']['order_workers'][$worker_id]['operation_production'][$order_operation_id]['operation_worker_id'] = $operation_worker->id;


//                                    if ($operation_worker->coordinate == null) {
//                                        $coordinate = '0.0,0.0,0.0';
//                                    } else {
//                                        $coordinate = $operation_worker->coordinate;
//                                    }
//                                    if ($operation_worker->group_workers_unity == null) {
//                                        $group_workers_unity = -1;
//                                    } else {
//                                        $group_workers_unity = $operation_worker->group_workers_unity;
//                                    }//FIXME переделать после смены default значения в бд
                                    $order[$chane_id]['order']['order_workers'][$worker_id]['operation_production'][$order_operation_id]['coordinate'] = $operation_worker->coordinate;
                                    $order[$chane_id]['order']['order_workers'][$worker_id]['operation_production'][$order_operation_id]['group_workers_unity'] = $operation_worker->group_workers_unity;
                                }
                            }
                            if (count($order[$chane_id]['order']['order_places'][$ord_place_id]['operation_production']) == 0) {
                                $order[$chane_id]['order']['order_places'][$ord_place_id]['operation_production'] = (object)array();
                            }
                        }
                    }

                    /**
                     * блок получения циклограммы
                     */
                    $cyclogram_result = ReportForPreviousPeriodController::GetCyclogramm(array($order_id), $brigade_id, $company_department_id, $date_time, $shift_id);
                    $order[$chane_id]['order']['cyclogram'] = $cyclogram_result['Items']['cyclogram'];
                    if (count($order[$chane_id]['order']['cyclogram']) == 0) {
                        $order[$chane_id]['order']['cyclogram'] = (object)array();
                    }
                    if (count($cyclogram_result['warnings']) != 0) {
                        $warnings = array_merge($warnings, $cyclogram_result['warnings']);
                    }
                    if (count($cyclogram_result['errors']) != 0) {
                        $errors = array_merge($errors, $cyclogram_result['errors']);
                    }
                    foreach ($order_item->orderWorkerVgks as $orderWorkerVgk) {
                        $order[$chane_id]['order']['order_workers_vgk'][$orderWorkerVgk->worker_id]['worker_id'] = $orderWorkerVgk->worker_id;
                    }
                    /**
                     * блок расчета людей на смене
                     */
                    $json_outgoing1 = json_encode(array(
                        'company_department_id' => $company_department_id,
                        'shift_id' => $shift_id,
                        'mine_id' => $mine_id,
                        'date_time' => $date_time
                    ));
                    $outgoing = self::GetWorkersAttendanceInShift($json_outgoing1);
                    if (!empty($outgoing['Items'])) {
                        $order[$chane_id]['order']['outgoing'] = $outgoing['Items'];
                    } else {
                        $order[$chane_id]['order']['outgoing'] = (object)array();
                    }
                    if (count($outgoing['errors']) != 0) {
                        $errors = array_merge($errors, $outgoing['errors']);
                    }
                    if (count($outgoing['warnings']) != 0) {
                        $warnings = array_merge($warnings, $outgoing['warnings']);
                    }
                    if (count($order[$chane_id]['order']['order_workers']) == 0) {
                        $order[$chane_id]['order']['order_workers'] = (object)array();
                    }
                    if ($order_item->orderWorkerVgks) {
                        foreach ($order_item->orderWorkerVgks as $rescuire) {
                            $workers_vgk[$rescuire->worker_id]['worker_id'] = $rescuire->worker_id;
                            $workers_vgk[$rescuire->worker_id]['worker_role'] = $rescuire->role_id;
                            $workers_vgk[$rescuire->worker_id]['vgk'] = $rescuire->vgk;
                        }
                    }
                }
            } else {
                $warnings[] = "GetOrder. Нет данных по заданному условию";
            }

            // получение шаблона ВГК из графика и всех ВГК. строим переменную запроса
            $input_filter = array(
                'date' => $date_time,
                'company_department_id' => $company_department_id,
                'shift_id' => $shift_id
            );

            /**
             * Блок получения инструктажей предсменных
             */
            // получаем всех ВГК с участка и ВГК на смене
            $response = self::GetWorkersVgk(json_encode($input_filter));
            $orders_result['all_rescuers'] = [];
            $filtered_rescuers = [];
            if ($response['status'] == 1) {
                if (!empty($response['Items'])) {
                    $orders_result['all_rescuers'] = $response['Items']['all_rescuers'];
                    $filtered_rescuers = $response['Items']['filtered_rescuers'];
                }
                if (count($response['errors']) != 0) {
                    $errors [] = $response['errors'];
                }
                $warnings[] = $response['warnings'];
            } else {
                if (count($response['errors']) != 0) {
                    $errors [] = $response['errors'];
                }
                $warnings[] = $response['warnings'];

                throw new Exception("GetOrder. Ошибка получения шаблона людей ВГК");
            }

            $orders_result['worker_vgk'] = array();
            // если ВГК есть в наряде, то берем оттуда, иначе берем с шаблона - графика входов, а если и там нет, то пустой объект
            if (isset($workers_vgk) and $workers_vgk) {
                foreach ($workers_vgk as $worker_vgk) {
                    $orders_result['worker_vgk'][] = $worker_vgk;
                }
            } else {
                $orders_result['worker_vgk'] = $filtered_rescuers;
            }

            $orders_result['department_order'] = $order;
            if ($shift_id == 1) {
                $time = " 14:00:00";
                $date_time_inj = date("Y-m-d", strtotime($date_time)) . $time;
            } else if ($shift_id == 2) {
                $time = " 20:00:00";
                $date_time_inj = date("Y-m-d", strtotime($date_time)) . $time;
            } else if ($shift_id == 3) {
                $time = " 02:00:00";
                $date_time_inj = date("Y-m-d", strtotime($date_time . ' +1 day')) . $time;
            } else if ($shift_id == 4) {
                $time = " 08:00:00";
                $date_time_inj = date("Y-m-d", strtotime($date_time . ' +1 day')) . $time;
            } else {
                throw new Exception("GetOrder. Не корректное значение смены");
            }
            $injunctions = self::GetInjunctionsIds($company_department_id, $date_time_inj);
            $orders_result['injunctions'] = $injunctions['Items'];
            if (count($injunctions['warnings']) != 0) {
                $warnings = array_merge($warnings, $injunctions['warnings']);
            }
            if (count($injunctions['errors']) != 0) {
                $errors = array_merge($errors, $injunctions['errors']);
            }
            if (empty($orders_result['injunctions'])) {
                if (count($orders_result['injunctions']) == 0) {
                    $orders_result['injunctions'] = (object)array();
                }
            }

            $favouritesPlace = self::GetFavouritesPlace($company_department_id);
            if ($favouritesPlace['status'] == 1) {
                $orders_result['favourites_place_list'] = $favouritesPlace['Items'];
            } else {
                if (count($favouritesPlace['warnings']) != 0) {
                    $warnings = array_merge($warnings, $favouritesPlace['warnings']);
                }
                if (count($favouritesPlace['errors']) != 0) {
                    $errors = array_merge($errors, $favouritesPlace['errors']);
                }
                $orders_result['favourites_place_list'] = (object)array();
            }

//            $cyclogram_result = ReportForPreviousPeriodController::GetCyclogramm($brigade_id,$company_department_id,$date_time);
//            $orders_result['cyclogramm'] = $cyclogram_result['Items'];
//            $warnings = array_merge($warnings,$cyclogram_result['warnings']);
//            $errors = array_merge($warnings,$cyclogram_result['errors']);
        } catch (Throwable $ex) {
            $errors[] = "GetOrder. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = (string)$ex->getLine();
            $status = 0;
        }
        if (empty($orders_result)) {
            $orders_result = (object)array();
        }
        $result = $orders_result;
        $warnings[] = "GetOrder. Закончил выполнять метод";
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // GetInjunctionsIds - получить список предписаний
    // выгребаем предписания, которые выписаны на запрашиваемую дату, или те которые выписаны ранее, но не снятые еще до указанной даты.
    public static function GetInjunctionsIds($company_department_id, $date_time)
    {
        $log = new LogAmicumFront("GetInjunctionsIds");

        $result = null;

        try {
            if (empty($company_department_id)) {
                throw new Exception('Не передан идентификатор участка');
            }

            // Выгребаем предписания, которые выписаны на запрашиваемую дату или те, которые выписаны ранее, но не снятые еще до указанной даты.
            // получаем статусы до указанной даты
            $found_injunctions = Yii::$app->db->createCommand("
                    SELECT  injunction.place_id, 
                            injunction.company_department_id, 
                            injunction.id as id, 
                            injunction.instruct_id_ip as instruct_id_ip, 
                            checking.date_time_start as date_time, 
                            checking.instruct_id as ppk_id_inside, 
                            checking.rostex_number as ppk_id_rtn, 
                            checking.nn_id as ppk_id_nn, 
                            injunction_status.status_id,
                            correct_measures.id as correct_measure_id, 
                            operation.title as operation_title, 
                            injunction.kind_document_id as kind_document_id,
                            correct_measures.status_id as correct_measure_status_id, 
                            correct_measures.date_time as  correct_measure_date_time,
                            correct_measures.correct_measures_value, 
                            correct_measures.operation_id, 
                            unit.short as unit_short_title
                    FROM injunction
                    INNER JOIN checking ON checking.id = injunction.checking_id
                    INNER JOIN injunction_violation ON injunction.id = injunction_violation.injunction_id
                    LEFT JOIN correct_measures ON injunction_violation.id = correct_measures.injunction_violation_id
                    LEFT JOIN operation ON correct_measures.operation_id = operation.id
                    LEFT JOIN unit ON unit.id=operation.unit_id
                    INNER JOIN injunction_status ON injunction_status.injunction_id = injunction.id
                    INNER JOIN (SELECT max(date_time) as max_date_time, injunction_id FROM injunction_status WHERE date_time < '" . $date_time . "' GROUP BY injunction_id) inj_statys_max
                        ON inj_statys_max.max_date_time=injunction_status.date_time AND inj_statys_max.injunction_id = injunction_status.injunction_id
                    WHERE (injunction.company_department_id = " . $company_department_id . " OR checking.company_department_id = " . $company_department_id . ")
                    AND ((injunction_status.status_id!=59) OR (DATE_FORMAT(injunction_status.date_time, '%Y-%m-%d')='" . date("Y-m-d", strtotime($date_time)) . "'))
                    AND (injunction.kind_document_id != 2)
                ")->queryAll();

            if (!empty($found_injunctions)) {
                foreach ($found_injunctions as $injunction) {
                    $result[$injunction['id']]['injunction_id'] = $injunction['id'];
                    $result[$injunction['id']]['injunction_date'] = $injunction['date_time'];
                    if ($injunction['ppk_id_inside']) {
                        $result[$injunction['id']]['instruct_id_ip'] = $injunction['ppk_id_inside'];
                    } else if ($injunction['ppk_id_rtn']) {
                        $result[$injunction['id']]['instruct_id_ip'] = $injunction['ppk_id_rtn'];
                    } else if ($injunction['ppk_id_nn']) {
                        $nn = explode("_", $injunction['ppk_id_nn']);
                        if (isset($nn[1])) {
                            $result[$injunction['id']]['instruct_id_ip'] = $nn[1];
                        } else {
                            $result[$injunction['id']]['instruct_id_ip'] = null;
                        }
                    } else {
                        $result[$injunction['id']]['instruct_id_ip'] = null;
                    }

                    $result[$injunction['id']]['injunction_status_id'] = $injunction['status_id'];
                    $result[$injunction['id']]['injunction_place_id'] = $injunction['place_id'];
                    $result[$injunction['id']]['kind_document_id'] = $injunction['kind_document_id'];
                    $result[$injunction['id']]['injunction_company_department_id'] = $injunction['company_department_id'];
                    $result[$injunction['id']]['correct_measure'] = array();
                    if ($injunction['correct_measure_id']) {
                        $result[$injunction['id']]['correct_measure'][$injunction['correct_measure_id']]['correct_measure_id'] = $injunction['correct_measure_id'];
                        $result[$injunction['id']]['correct_measure'][$injunction['correct_measure_id']]['operation_id'] = $injunction['operation_id'];
                        $result[$injunction['id']]['correct_measure'][$injunction['correct_measure_id']]['operation_title'] = $injunction['operation_title'];
                        $result[$injunction['id']]['correct_measure'][$injunction['correct_measure_id']]['unit_short_title'] = $injunction['unit_short_title'];
                        $result[$injunction['id']]['correct_measure'][$injunction['correct_measure_id']]['correct_measures_value'] = $injunction['correct_measures_value'];
                        $result[$injunction['id']]['correct_measure'][$injunction['correct_measure_id']]['correct_measure_status_id'] = $injunction['correct_measure_status_id'];
                        $result[$injunction['id']]['correct_measure'][$injunction['correct_measure_id']]['correct_measure_date_time'] = $injunction['correct_measure_date_time'];
                    }
                }
                foreach ($result as $injunction) {
                    if (empty($injunction['correct_measure'])) {
                        $result[$injunction['injunction_id']]['correct_measure'] = (object)array();
                    }
                }
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    // GetFavouritesPlace - получить список избранных мест по участку
    public static function GetFavouritesPlace($company_department_id = NULL)
    {
        $errors = array();
        $warnings = array();
        $result = (object)array();
        $status = 1;
        try {
            if (!empty($company_department_id)) {
                $date_now = date("Y-m-d", strtotime(BackendAssistant::GetDateTimeNow() . '-7 days'));
//                var_dump($date_now);
                $found_favourites_place = (new Query())
                    ->select([
                        'order_place.place_id as place_id',
                        'place.title as place_title'
                    ])
                    ->from('order')
                    ->innerJoin('order_place', 'order_place.order_id = order.id')
                    ->innerJoin('place', 'place.id = order_place.place_id')
                    ->where(['order.company_department_id' => $company_department_id])
                    ->andWhere("order.date_time_create>'" . $date_now . "'")
                    ->groupBy("place_id, place_title")
                    ->limit(50000)
                    ->indexBy('place_id')
                    ->all();

            } else {
                throw new Exception('GetFavouritesPlace. Не передан идентификатор участка');
            }
        } catch (Throwable $exception) {
            $errors[] = "GetFavouritesPlace. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = (string)$exception->getLine();
            $status = 0;
        }
        if (isset($found_favourites_place) and !empty($found_favourites_place)) {
            $result = $found_favourites_place;
        } else {
            $result = (object)array();
        }
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // GetFavouritesRouteTemplate - получить список избранных шаблонов маршрута по заданному участку
    public static function GetFavouritesRouteTemplate($company_department_id = NULL)
    {
        $errors = array();
        $warnings = array();
        $result = array();
        $status = 1;
        try {
            if (!empty($company_department_id)) {
                $found_favourites_route_template = (new Query())
                    ->select([
                        'route_template.id as id',
                        'route_template.title as title'
                    ])
                    ->from('order')
                    ->innerJoin('order_place', 'order_place.order_id = order.id')
                    ->innerJoin('route_template', 'route_template.id = order_place.route_template_id')
                    ->where(['order.company_department_id' => $company_department_id])
                    ->groupBy("id")
                    ->limit(50000)
                    ->indexBy('id')
                    ->all();

            } else {
                throw new Exception('GetFavouritesPlace. Не передан идентификатор участка');
            }
        } catch (Throwable $exception) {
            $errors[] = "GetFavouritesPlace. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = (string)$exception->getLine();
            $status = 0;
        }
        if (isset($found_favourites_route_template) and !empty($found_favourites_route_template)) {
            $result = $found_favourites_route_template;
        } else {
            $result = (object)array();
        }

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * GetFavouritesBriefing - метод получения списка избранных инструктажей по участку
     * Входные параметры:
     *      company_department_id   - ключ подразделения
     * Выходные параметры:
     *      {id}                    - ключ инструктажей
     *          id                      - ключ инструктажей
     *          title                   - наименование инструктажей
     * @example 127.0.0.1/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetFavouritesBriefing&subscribe=&data={"company_department_id":60002522,"mine_id":1}
     */
    public static function GetFavouritesBriefing($data_post = NULL)
    {
        $log = new LogAmicumFront("GetFavouritesBriefing");
        $result = (object)array();

        try {

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'mine_id')
            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $company_department_id = $post_dec->company_department_id;
            $mine_id = $post_dec->mine_id;

            if (!empty($company_department_id)) {
                $found_favourite_briefing = (new Query())
                    ->select([
                        'order_instruction_pb.instruction_pb_id as id',
                        'instruction_pb.title as title'
                    ])
                    ->from('order')
                    ->innerJoin('order_instruction_pb', 'order_instruction_pb.order_id = order.id')
                    ->innerJoin('instruction_pb', 'instruction_pb.id = order_instruction_pb.instruction_pb_id')
                    ->where(['order.company_department_id' => $company_department_id, 'order.mine_id' => $mine_id])
                    ->groupBy("id, title")
                    ->limit(50000)
                    ->indexBy('id')
                    ->all();

            } else {
                throw new Exception('Не передан идентификатор участка');
            }

            if (isset($found_favourite_briefing) and !empty($found_favourite_briefing)) {
                $result = $found_favourite_briefing;
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Название метода: GetListWorkersByLastGraficShift
     * Назначение метода: GetListWorkersByLastGraficShift - метод получения списка рабочих на смене сгруппированных в бригаду на основе последнего графика выходов на заданный день месяц и год
     * по всему участку
     *
     * Входные обязательные параметры:
     * $company_department_id   -   ключ конкретного департамента
     * $date_time               -   дата на которую надо взять график выходов
     * $shift_id                -   смена на которую надо взять график выходов
     *
     * @package frontend\controllers\ordersystem
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetListWorkersByLastGraficShift&subscribe=&data={%22company_department_id%22:801,%22date_time%22:%222019-06-15%22,%22shift_id%22:1,%22brigade_id%22:%22%22}
     *
     * @author Якимов М.Н.
     * Created date: on 16.06.2019 10:31
     */
    public static function GetListWorkersByLastGraficShift($company_department_id, $date_time, $shift_id, $mine_id = 1)
    {
        $log = new LogAmicumFront("GetListWorkersByLastGraficShift");

        $result = (object)array();
        $workers = [];                                                                                                  // массив всех работников в графике выходов
        try {
            $log->addLog("Начал выполнять метод");

            $date_time = strtotime($date_time);
            $grafic_year = date("Y", $date_time);
            $grafic_month = (int)date("m", $date_time);
            $grafic_day = (int)date("d", $date_time);
            $worker_list = GraficTabelMain::find()
                ->select('
                    grafic_tabel_date_plan.worker_id,
                    grafic_tabel_date_plan.role_id,
                    grafic_tabel_date_plan.chane_id,
                ')
                ->innerJoinWith('graficTabelDatePlans', false)
                ->where(['status_id' => self::STATUS_ACTUAL,
                    'grafic_tabel_main.company_department_id' => $company_department_id,
                    'grafic_tabel_main.year' => $grafic_year,
                    'grafic_tabel_main.month' => $grafic_month,
                    'grafic_tabel_date_plan.day' => $grafic_day,
                    'grafic_tabel_date_plan.shift_id' => $shift_id,
                    'grafic_tabel_date_plan.mine_id' => $mine_id,
                    'grafic_tabel_date_plan.working_time_id' => self::WORK_TIME])
                ->indexBy('worker_id')
                ->asArray()
                ->all();
            if (empty($worker_list)) {
                $log->addLog("График выходов по запрашиваемым параметрам не найден company_department_id = $company_department_id date_time=$date_time shift_id=$shift_id");
            } else {
                $log->addLog("График выходов на заданную смену есть, начинаю получать список бригад по департаменту");

                foreach ($worker_list as $worker) {
                    $chanes[$worker['chane_id']] = $worker['chane_id'];
                }


                $found_chanes = Chane::find()
                    ->where(['id' => $chanes])
                    ->indexBy('id')
                    ->all();
                /**
                 * По полученному графику выходов начинаем группировать людей в бригады
                 */

                $workers_by_brigade = array();

                if ($found_chanes) {
                    foreach ($worker_list as $worker) {
                        $worker_id = $worker['worker_id'];
                        $brigade_id = $found_chanes[$worker['chane_id']]->brigade_id;
                        $chane_id = $found_chanes[$worker['chane_id']]->id;
                        $chaner_id = $found_chanes[$worker['chane_id']]->chaner_id;
                        $chane_type_id = $found_chanes[$worker['chane_id']]->chane_type_id;

                        if (!isset($workers_by_brigade[$brigade_id])) {
                            $workers_by_brigade[$brigade_id]['brigade_id'] = $brigade_id;
                            $workers_by_brigade[$brigade_id]['chanes'] = [];
                        }

                        if (!isset($workers_by_brigade[$brigade_id]['chanes'][$chane_id])) {
                            $workers_by_brigade[$brigade_id]['chanes'][$chane_id]['chane_id'] = $chane_id;
                            $workers_by_brigade[$brigade_id]['chanes'][$chane_id]['chaner_id'] = $chaner_id;
                            $workers_by_brigade[$brigade_id]['chanes'][$chane_id]['chane_type_id'] = $chane_type_id;
                            $workers_by_brigade[$brigade_id]['chanes'][$chane_id]['workers'] = [];
                        }

                        if ($worker_id == $chaner_id) {
                            $chaner_worker = true;
                        } else {
                            $chaner_worker = false;
                        }

                        $workers_by_brigade[$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['chaner'] = $chaner_worker;
                        $workers_by_brigade[$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['worker_id'] = $worker_id;
                        $workers_by_brigade[$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['chane_id'] = $worker['chane_id'];
                        $workers_by_brigade[$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['role_id'] = $worker['role_id'];
                        $workers[] = $worker_id;
                    }
                }
                $result = $workers_by_brigade;
                unset($workers_by_brigade);
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Закончил выполнять метод");

        return array_merge(['Items' => $result, 'workers' => $workers], $log->getLogAll());
    }


    /**
     * Название метода: GetInjunctionByCompanyDepartment()
     * GetInjunctionByCompanyDepartment - Метод получения списка актуальных предписаний выданных на участок
     * @param null $data_post - объект с идентификатором наряда и идентификатором структурным подразделением
     * @return array - массив предписаний не выполненных на участке
     *
     * @package frontend\controllers\ordersystem
     * @example http://web.amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetInjunctionByCompanyDepartment&subscribe=&data={%22company_department_id%22:20000636}
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 19.06.2019 8:57
     * @since ver
     */
    public static function GetInjunctionByCompanyDepartment($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $injunction_list = array();                                                                                     // Промежуточный результирующий массив
        try {
            if ($data_post !== NULL && $data_post !== '') {
                $warnings[] = 'GetInjunctionByCompanyDepartment. Данные успешно переданы';
                $warnings[] = 'GetInjunctionByCompanyDepartment. Входной массив данных' . $data_post;
            } else {
                throw new Exception("GetInjunctionByCompanyDepartment. Входной массив данных post не передан");
            }

            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetInjunctionByCompanyDepartment. Декодировал входные параметры';

            if (
                property_exists($post_dec, 'company_department_id')
            )                                                     // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'GetInjunctionByCompanyDepartment. Данные с фронта получены';
            } else {
                throw new Exception("GetInjunctionByCompanyDepartment. Переданы некорректные входные параметры");
            }
            $company_department_id = $post_dec->company_department_id;                                                  // Идентификатор участка на которое выдается предписание
            $warnings[] = 'GetInjunctionByCompanyDepartment. Получение информации по предписаниям';
            $injunction_list = Injunction::find()                                                                       // Находим информацию о предписаниях, у которых указаны нарушения, не выполнены все корректирующие мероприятия. Дата устранения отображается минимальная из не исправленных нарушений
            ->select([
                'injunction.id AS injunction_id',
                'checking.date_time_end AS give_date',
                'place.title AS place_title',
                'place.id AS place_id',
                'MIN(injunction_violation.correct_period) AS correct_period',
                'status.title AS status_title',
                'status.id AS status_id'
            ])
                ->innerJoin('injunction_violation', 'injunction_violation.injunction_id = injunction.id')
                ->innerJoin('correct_measures', 'injunction_violation.id = correct_measures.injunction_violation_id')
                ->innerJoin('place', 'place.id = injunction.place_id')
                ->innerJoin('checking', 'checking.id = injunction.checking_id')
                ->innerJoin('status', 'status.id = injunction.status_id')
                ->where([
                    'injunction.status_id' => self::STATUS_ACTUAL,
                    'checking.company_department_id' => $company_department_id,
                    'correct_measures.status_id' => self::STATUS_ACTUAL,
                ])
                ->groupBy(['injunction.id'])
                ->indexBy('injunction_id')
                ->asArray()
                ->all();

            if (empty($injunction_list)) {                                                                              // Если данных не найдено, записываем информацию в массив предупреждений
                $warnings[] = 'GetInjunctionByCompanyDepartment. Данные по заданному условию не найдены';
            } else {
                $warnings[] = 'GetInjunctionByCompanyDepartment. Информация по предписаниям найдена в БД';
            }
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result = $injunction_list;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Название метода: GetTemplateOrderList()
     * GetTemplateOrderList - Метод получения списка шаблонов нарядов на производство работ
     * @param null $data_post - объект с идентификатором наряда и идентификатором структурным подразделением
     * @return array - массив предписаний не выполненных на участке
     *
     * @package frontend\controllers\ordersystem
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetTemplateOrderList&subscribe=&data={%22company_department_id%22:20000636}
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 22.09.2019 8:57
     * @since ver
     */
    public static function GetTemplateOrderList($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        // Массив ошибок
//        $injunction_list = array();                                                                                     // Промежуточный результирующий массив
        try {
            if ($data_post !== NULL && $data_post !== '') {
                $warnings[] = 'GetTemplateOrderList. Данные успешно переданы';
                $warnings[] = 'GetTemplateOrderList. Входной массив данных' . $data_post;
            } else {
                throw new Exception("GetTemplateOrderList. Входной массив данных post не передан");
            }

            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetTemplateOrderList. Декодировал входные параметры';

            if (
                property_exists($post_dec, 'company_department_id')
            )                                                     // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'GetTemplateOrderList. Данные с фронта получены';
            } else {
                throw new Exception("GetTemplateOrderList. Переданы некорректные входные параметры");
            }
            $company_department_id = $post_dec->company_department_id;                                                  // Идентификатор участка на которое выдается предписание
            $warnings[] = 'GetTemplateOrderList. Получение информации по списку шаблонов наряда на происзовдство работ';

            $templateListObj = OrderTemplate::find()                                                                       // Находим информацию о предписаниях, у которых указаны нарушения, не выполнены все корректирующие мероприятия. Дата устранения отображается минимальная из не исправленных нарушений
            ->joinWith('orderTemplatePlaces.place')
                ->joinWith('orderTemplatePlaces.placeTo')
                ->joinWith('orderTemplatePlaces.placeFrom')
                ->joinWith('orderTemplatePlaces.place')
                ->joinWith('orderTemplatePlaces.orderTemplateOperations.operation')
                ->joinWith('orderTemplateInstructionPbs.instructionPb')
                ->joinWith('orderTemplatePlaces.orderTemplateOperations.operation.operationGroups')
                ->where([
                    'order_template.company_department_id' => $company_department_id
                ])
                ->all();

            if (empty($templateListObj)) {                                                                              // Если данных не найдено, записываем информацию в массив предупреждений
                $warnings[] = 'GetTemplateOrderList. Данные по заданному условию шаблоны не найдены';
            } else {
                $warnings[] = 'GetTemplateOrderList. Информация по списку шаблонов наряда найдена в БД';
                foreach ($templateListObj as $templateList) {
                    $result[$templateList['id']]['template_order_id'] = $templateList['id'];
                    $result[$templateList['id']]['company_department_id'] = $templateList['company_department_id'];
                    $result[$templateList['id']]['date_time_create'] = $templateList['date_time_create'];
                    $result[$templateList['id']]['title'] = $templateList['title'];
                    foreach ($templateList->orderTemplatePlaces as $templatePlace) {
                        $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['order_place_id'] = 'template_' . $templatePlace['id'];
                        $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['place_id'] = $templatePlace['place_id'];
                        $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['place_title'] = $templatePlace['place']['title'];
                        $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['place_to_id'] = $templatePlace['place_to_id'];
                        $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['place_to_title'] = $templatePlace['place_to_id'] ? $templatePlace['placeTo']['title'] : "";
                        $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['place_from_id'] = $templatePlace['place_from_id'];
                        $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['place_from_title'] = $templatePlace['place_from_id'] ? $templatePlace['placeFrom']['title'] : "";
                        $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['mine_id'] = $templatePlace['place']['mine_id'];
                        if ($templatePlace['edge_id']) {
                            $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['edge_id'] = $templatePlace['edge_id'];
                        } else {
                            $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['edge_id'] = 0;
                        }
                        $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['passport_id'] = $templatePlace['passport_id'];
                        $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['route_template_id'] = $templatePlace['route_template_id'];
                        $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['coordinate'] = $templatePlace['coordinate'];
                        $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['description'] = null;
                        $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['reason'] = null;
                        foreach ($templatePlace->orderTemplateOperations as $templateOperation) {
                            $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['operation_production']['template_' . $templateOperation['id']]['order_operation_id'] = 'template_' . $templateOperation['id'];
                            $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['operation_production']['template_' . $templateOperation['id']]['operation_value_plan'] = $templateOperation['operation_value_plan'];
                            $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['operation_production']['template_' . $templateOperation['id']]['operation_value_fact'] = $templateOperation['operation_value_fact'];
                            $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['operation_production']['template_' . $templateOperation['id']]['operation_id'] = $templateOperation['operation_id'];
                            $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['operation_production']['template_' . $templateOperation['id']]['operation_title'] = $templateOperation['operation']['title'];
                            $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['operation_production']['template_' . $templateOperation['id']]['unit_id'] = $templateOperation['operation']['unit_id'];
                            $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['operation_production']['template_' . $templateOperation['id']]['equipment_id'] = $templateOperation['equipment_id'];
                            if ($templateOperation['edge_id']) {
                                $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['operation_production']['template_' . $templateOperation['id']]['edge_id'] = $templateOperation['edge_id'];
                            } else {
                                $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['operation_production']['template_' . $templateOperation['id']]['edge_id'] = 0;
                            }
                            $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['operation_production']['template_' . $templateOperation['id']]['coordinate'] = $templateOperation['coordinate'];
                            foreach ($templateOperation->operation->operationGroups as $operation_group) {
                                $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['operation_production']['template_' . $templateOperation['id']]['operation_groups'][$operation_group['group_operation_id']]['operation_group_id'] = $operation_group->group_operation_id;
                            }
                            // если список групп операций мест пуст, то делаем из него объект, но пустой
                            if (!isset($result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['operation_production']['template_' . $templateOperation['id']]['operation_groups'])) {
                                $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['operation_production']['template_' . $templateOperation['id']]['operation_groups'] = (object)array();
                            }
                        }
                        // если список операций мест пуст, то делаем из него объект, но пустой
                        if (!isset($result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['operation_production'])) {
                            $result[$templateList['id']]['order_places']['template_' . $templatePlace['id']]['operation_production'] = (object)array();
                        }
                    }
                    // если список мест пуст, то делаем из него объект, но пустой
                    if (!isset($result[$templateList['id']]['order_places'])) {
                        $result[$templateList['id']]['order_places'] = (object)array();
                    }
                    foreach ($templateList->orderTemplateInstructionPbs as $instruction_pb) {
                        $result[$templateList['id']]['order_instructions']['template_' . $instruction_pb['id']]['order_instruction_id'] = 'template_' . $instruction_pb['id'];
                        $result[$templateList['id']]['order_instructions']['template_' . $instruction_pb['id']]['instruction_pb_id'] = $instruction_pb['instruction_pb_id'];
                        $result[$templateList['id']]['order_instructions']['template_' . $instruction_pb['id']]['title'] = $instruction_pb['instructionPb']['title'];
                    }
                    // если список мест пуст, то делаем из него объект, но пустой
                    if (!isset($result[$templateList['id']]['order_instructions'])) {
                        $result[$templateList['id']]['order_instructions'] = (object)array();
                    }
                }

            }

        } catch (Throwable $exception) {
            $errors[] = "GetTemplateOrderList. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        if (!isset($result)) {
            $result = (object)array();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Название метода: DeleteTemplate()
     * DeleteTemplate - Метод удаления шаблона наряда
     * @param null $data_post - объект с идентификатором наряда и идентификатором структурным подразделением
     * @return array - массив предписаний не выполненных на участке
     *
     * @package frontend\controllers\ordersystem
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\OrderSystem&method=DeleteTemplate&subscribe=&data={%22template_id%22:20000636}
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 22.09.2019 8:57
     * @since ver
     */
    public static function DeleteTemplate($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        try {
            if ($data_post !== NULL && $data_post !== '') {
                $warnings[] = 'DeleteTemplate. Данные успешно переданы';
                $warnings[] = 'DeleteTemplate. Входной массив данных' . $data_post;
            } else {
                throw new Exception("DeleteTemplate. Входной массив данных post не передан");
            }

            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'DeleteTemplate. Декодировал входные параметры';

            if (
                property_exists($post_dec, 'template_id')
            )                                                     // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'DeleteTemplate. Данные с фронта получены';
            } else {
                throw new Exception("DeleteTemplate. Переданы некорректные входные параметры");
            }
            $template_id = $post_dec->template_id;                                                  // Идентификатор участка на которое выдается предписание
            $del_status = OrderTemplate::deleteAll(['id' => $template_id]);
            if (!$del_status) {
                throw new Exception("DeleteTemplate. Ошибка удаления шаблона наряда");
            }
        } catch (Throwable $exception) {
            $errors[] = "DeleteTemplate. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }


        $result_main = array('Items' => (object)array(), 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Название метода: SaveTemplateOrderList()
     * SaveTemplateOrderList - Метод сохранения шаблона наряда на производство работ
     * @param null $data_post - объект с идентификатором наряда и идентификатором структурным подразделением
     * @return array - массив предписаний не выполненных на участке
     *
     * @package frontend\controllers\ordersystem
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\OrderSystem&method=SaveTemplateOrderList&subscribe=&data={%22company_department_id%22:20000636}
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 22.09.2019 8:57
     * @since ver
     */
    public static function SaveTemplateOrderList($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = (object)array();                                                                                              // результурующий объект
//        $injunction_list = array();                                                                                     // Промежуточный результирующий массив
        try {
            if ($data_post !== NULL && $data_post !== '') {
                $warnings[] = 'SaveTemplateOrderList. Данные успешно переданы';
                $warnings[] = 'SaveTemplateOrderList. Входной массив данных' . $data_post;
            } else {
                throw new Exception("SaveTemplateOrderList. Входной массив данных post не передан");
            }

            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'SaveTemplateOrderList. Декодировал входные параметры';

            if (
                property_exists($post_dec, 'templateObject')
            )                                                     // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'SaveTemplateOrderList. Данные с фронта получены';
            } else {
                throw new Exception("SaveTemplateOrderList. Переданы некорректные входные параметры");
            }
            $templateObjects = $post_dec->templateObject;                                                                // объект шаблона наряда
            foreach ($templateObjects as $templateObject) {
                $company_department_id = $templateObject->company_department_id;                                            // Идентификатор участка на которое выдается предписание
                $new_order_template = new OrderTemplate();
                $new_order_template->company_department_id = $company_department_id;
                $new_order_template->title = $templateObject->title;
                $new_order_template->date_time_create = BackendAssistant::GetDateNow();
                if ($new_order_template->save()) {
                    $new_order_template->refresh();
                    $order_template_id = $new_order_template->id;
                } else {
                    $errors[] = $new_order_template->errors;
                    throw new Exception("SaveTemplateOrderList. Ошибка сохранения главной таблицы Шаблона наряда OrderTemplate");
                }
                if ($templateObject->order_places) {
                    foreach ($templateObject->order_places as $order_place) {
                        $new_order_place = new OrderTemplatePlace();
                        $new_order_place->order_template_id = $order_template_id;
                        $new_order_place->place_id = $order_place->place_id;
                        if (property_exists($order_place, "place_to_id")) {
                            $new_order_place->place_to_id = $order_place->place_to_id;
                        }
                        if (property_exists($order_place, "place_from_id")) {
                            $new_order_place->place_from_id = $order_place->place_from_id;
                        }
                        if ($order_place->edge_id) {
                            $new_order_place->edge_id = $order_place->edge_id;
                        } else {
                            $new_order_place->edge_id = null;
                        }
                        $new_order_place->passport_id = $order_place->passport_id;

                        if (property_exists($order_place, 'coordinate') and $order_place->coordinate != "") {
                            $new_order_place->coordinate = $order_place->coordinate;
                        } else {
                            $new_order_place->coordinate = "0.0,0.0,0.0";
                        }
                        if (property_exists($order_place, 'route_template_id') and $order_place->route_template_id != "") {
                            $new_order_place->route_template_id = $order_place->route_template_id;
                        } else {
                            $new_order_place->route_template_id = null;
                        }
                        if ($new_order_place->save()) {
                            $new_order_place->refresh();
                            $order_place_id = $new_order_place->id;
                        } else {
                            $errors[] = $new_order_place->errors;
                            throw new Exception("SaveTemplateOrderList. Ошибка сохранения таблицы мест OrderTemplatePlace");
                        }
                        if ($order_place->operation_production) {
                            foreach ($order_place->operation_production as $order_operation) {
                                $new_order_operation = new OrderTemplateOperation();
                                $new_order_operation->order_template_place_id = $order_place_id;
                                $new_order_operation->operation_id = $order_operation->operation_id;
                                $new_order_operation->equipment_id = $order_operation->equipment_id;
                                if ($order_operation->edge_id) {
                                    $new_order_operation->edge_id = $order_operation->edge_id;
                                } else {
                                    $new_order_operation->edge_id = null;
                                }
                                if (property_exists($order_operation, 'coordinate') and $order_operation->coordinate != "") {
                                    $new_order_operation->coordinate = $order_operation->coordinate;
                                } else {
                                    $new_order_operation->coordinate = "0.0,0.0,0.0";
                                }

                                if ($order_operation->operation_value_plan) {
                                    $new_order_operation->operation_value_plan = (string)((float)str_replace(",", ".", $order_operation->operation_value_plan));
                                } else {
                                    $new_order_operation->operation_value_plan = null;
                                }
                                if ($order_operation->operation_value_fact) {
                                    $new_order_operation->operation_value_fact = (string)((float)str_replace(",", ".", $order_operation->operation_value_fact));
                                } else {
                                    $new_order_operation->operation_value_fact = null;
                                }
                                $new_order_operation->status_id = 1;
                                $new_order_operation->description = $order_place->reason;
                                if ($new_order_operation->save()) {
                                    $new_order_operation->refresh();
                                    $order_operation_id = $new_order_operation->id;
                                } else {
                                    $errors[] = $new_order_operation->errors;
                                    throw new Exception("SaveTemplateOrderList. Ошибка сохранения таблицы операций OrderTemplateOperation");
                                }
                            }
                        } else {
                            $warnings[] = "SaveTemplateOrderList. При сохранении шаблона внутри места $order_place_id операций не было";
                        }
                    }
                } else {
                    $warnings[] = 'SaveTemplateOrderList. При сохранении шаблона внутри не было мест';
                }
                if ($templateObject->order_instructions) {
                    foreach ($templateObject->order_instructions as $order_instruction) {
                        $new_order_instruction = new OrderTemplateInstructionPb();
                        $new_order_instruction->order_template_id = $order_template_id;
                        $new_order_instruction->instruction_pb_id = $order_instruction->instruction_pb_id;
                        if ($new_order_instruction->save()) {
                            $new_order_instruction->refresh();
                            $order_instruction_id = $new_order_instruction->id;
                        } else {
                            $errors[] = $new_order_instruction->errors;
                            throw new Exception("SaveTemplateOrderList. Ошибка сохранения списка инструктажей OrderTemplateInstructionPb");
                        }
                    }
                }
            }
            $warnings[] = 'SaveTemplateOrderList. Получение информации по списку шаблонов наряда на произовдство работ';

            $response = self::GetTemplateOrderList(json_encode(array('company_department_id' => $company_department_id)));
            if ($response['status'] == 1) {
                $result = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception("SaveTemplateOrderList. Ошибка получения сохранненого списка шаблонов из БД");
            }
        } catch (Throwable $exception) {
            $errors[] = "SaveTemplateOrderList. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Название метода: GetBrigadesByDepartment()
     * GetBrigadesByDepartment - Метод получения списка бригад по участку
     *
     * @param null $data_post
     * @return array
     * @package frontend\controllers\ordersystem
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 19.06.2019 13:46
     * @since ver
     */
    public static function GetBrigadesByDepartment($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $brigades_list = array();                                                                                         // Промежуточный результирующий массив
        try {
            if ($data_post !== NULL && $data_post !== '') {
                $warnings[] = 'GetBrigadesByDepartment. Данные успешно переданы';
                $warnings[] = 'GetBrigadesByDepartment. Входной массив данных' . $data_post;
            } else {
                throw new Exception("GetBrigadesByDepartment. Данные с фронта не получены");
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetBrigadesByDepartment. Декодировал входные параметры';
            if (property_exists($post_dec, 'company_department_id'))                                            // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'GetBrigadesByDepartment.Данные с фронта получены';
            } else {
                throw new Exception("GetBrigadesByDepartment. Переданы некорректные входные параметры");
            }

            $company_department_id = $post_dec->company_department_id;
            $warnings[] = 'GetBrigadesByDepartment.Данные с фронта получены';
            $brigades_list = self::FindBrigade($company_department_id);                                       // Получаем список людей по идентификатору company_department
            $merged_array = ArrayHelper::merge(['warnings' => $warnings, 'errors' => $errors], $brigades_list);
            $warnings = $merged_array['warnings'];
            $errors = $merged_array['errors'];
            $status *= $brigades_list['status'];
            $brigades_list = $brigades_list['Items'];
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $result = $brigades_list;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // TODO: МЕТОД ПРИВАТНЫЙ, ИСПОЛЬЗУЕТСЯ В КОНТРОЛЛЕРАХ WorkScheduleController и OrderSystem, перенести нужно в базовый контроллер по бригадам
    private static function FindBrigade($company_department_id, $status_actual = 1)
    {
        $status = 1;                                                                                                      // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();
        $brigade_model = array();
        $warnings[] = 'FindBrigade. Зашел в метод.';
        try {
            $brigade_model = Brigade::find()
                ->select(['brigade.id AS brigade_id', 'brigade.description AS brigade_description', 'brigade.brigader_id'])
                ->where([
                    'company_department_id' => $company_department_id,
                    'status_id' => $status_actual
                ])
                ->indexBy('brigade_id')
                ->asArray()
                ->orderBy('description')
                ->all();
            if ($brigade_model) {
                $warnings[] = 'FindBrigade. Данные по бригада найдены';
            } else {
                $warnings[] = 'FindBrigade. У запрашиваемого конкретного департамента нет бригад';
                $brigade_model = "";
                $status *= 1;
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = $exception->getMessage();
        }
        $warnings[] = 'FindBrigade. Вышел из метода';
        $result = $brigade_model;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SaveNewOrder() - сохранение нового наряда
     *
     * Структура на вход описана в методе GetOrder
     *
     * @param null $data_post - JSON структура наряда
     * @return array $result_main - структура данных вида:[Items]
     *                                                      new_order_id:
     *                                                    status:
     *                                                    [errors]                                                      - массив ошибок
     *                                                    [warnings]                                                    - массив предупреждений (ход выполнения программы)
     *
     * @package frontend\controllers\ordersystem
     * @example amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=SaveNewOrder&subscribe=&data={}
     *
     * @author Рудов М.С. <rms@pfsz.ru>
     * Created date: on 27.06.2019 15:36
     */

    public static function SaveNewOrder($data_post = NULL)
    {
        $status = 1; // Флаг успешного выполнения метода
        $session = Yii::$app->session;
        $warnings = array(); // Массив предупреждений
        $errors = array(); // Массив ошибок
        $order_operation_worker_status = array(); //массив на добавление статусов (order_operation_worker_status)
        $order = array();
        $order_inst_id = array();
        $cyclogramm_data = array();
        $date_now = date('Y-m-d', strtotime(BackendAssistant::GetDateNow()));
        $date__time_now = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));
        $order_company_department_id = null;
        $order_id = null;
        $order_shift_id = null;
        $coordinate = '0.0,0.0,0.0';
        $group_workers_unity = 0;
        $order_brigade_id = null;
        $new_order_place_id = null; //идентификатор нового наряда на место
        $operation_count = null; //количство операций
        $new_order_id = null; //идентификатор нового наряда
        $old_order_id = null;
        $order_operation_id = null;
        $cyclogram_option = null;
        $cyclograms_operations = null;
        $brigade_id = null;
        $order_date_time = null;
        $get_order = null;
        $get_order_place = null;
        /**
         * Наряд с данными
         */
//        $data_post = '{"department_order":{"764":{"brigade_id":3908,"order":{"order_id":"150","worker_value_outgoing":8,"order_status_id":50,"title":"Наряд участка Прочее (TEST)","company_department_id":801,"department_type_id":5,"order_date_time":"2019-09-17","shift_id":4,"chane_id":764,"order_places":{"180":{"order_place_id":180,"place_id":139630,"passport_id":null,"reason":null,"operation_production":{"387":{"operation_id":10,"order_operation_id":387,"operation_groups":{"3":{"operation_group_id":3}},"operation_value_plan":"256","operation_value_fact":"500","operation_load_value":5},"388":{"operation_id":12,"order_operation_id":388,"operation_groups":{"4":{"operation_group_id":4}},"operation_value_plan":"5000","operation_value_fact":"2658","operation_load_value":0},"389":{"operation_id":24,"order_operation_id":389,"operation_groups":{"8":{"operation_group_id":8}},"operation_value_plan":"56000","operation_value_fact":"46542","operation_load_value":0},"390":{"operation_id":26,"order_operation_id":390,"operation_groups":{"2":{"operation_group_id":2}},"operation_value_plan":"10000","operation_value_fact":"25435","operation_load_value":0}}},"181":{"order_place_id":181,"place_id":139663,"passport_id":null,"reason":null,"operation_production":{"391":{"operation_id":1,"order_operation_id":391,"operation_groups":{"1":{"operation_group_id":1},"3":{"operation_group_id":3}},"operation_value_plan":"0","operation_value_fact":null,"operation_load_value":0}}}},"order_workers":{"70003548":{"operation_production":{"387":{"status":{"status_id_last":51,"status_id_all":{"243":{"operation_status_id":243,"status_id":51,"status_date_time":"2019-09-02 11:41:45","worker_id":1801}}},"order_operation_id":387,"operation_worker_id":65,"coordinate":"16812.93,-813.8101,-13272.49","group_workers_unity":-1},"389":{"status":{"status_id_last":51,"status_id_all":{"244":{"operation_status_id":244,"status_id":51,"status_date_time":"2019-09-02 11:41:45","worker_id":1801}}},"order_operation_id":389,"operation_worker_id":66,"coordinate":"16812.93,-813.8101,-13272.49","group_workers_unity":-1}},"worker_id":70003548,"operation_list":{"10":10,"24":24},"worker_role_id":1},"70003546":{"operation_production":{"388":{"status":{"status_id_last":51,"status_id_all":{"246":{"operation_status_id":246,"status_id":51,"status_date_time":"2019-09-02 11:41:45","worker_id":1801}}},"order_operation_id":388,"operation_worker_id":68,"coordinate":"16812.93,-813.8101,-13272.49","group_workers_unity":-1},"390":{"status":{"status_id_last":51,"status_id_all":{"247":{"operation_status_id":247,"status_id":51,"status_date_time":"2019-09-02 11:41:45","worker_id":1801}}},"order_operation_id":390,"operation_worker_id":69,"coordinate":"16812.93,-813.8101,-13272.49","group_workers_unity":-1}},"worker_id":70003546,"operation_list":{"12":12,"26":26},"worker_role_id":1},"70003549":{"operation_production":{"388":{"status":{"status_id_last":51,"status_id_all":{"248":{"operation_status_id":248,"status_id":51,"status_date_time":"2019-09-02 11:41:45","worker_id":1801}}},"order_operation_id":388,"operation_worker_id":70,"coordinate":"16812.93,-813.8101,-13272.49","group_workers_unity":-1}},"worker_id":70003549,"operation_list":{"12":12},"worker_role_id":1},"70003547":{"operation_production":{"389":{"status":{"status_id_last":51,"status_id_all":{"250":{"operation_status_id":250,"status_id":51,"status_date_time":"2019-09-02 11:41:45","worker_id":1801}}},"order_operation_id":389,"operation_worker_id":72,"coordinate":"16812.93,-813.8101,-13272.49","group_workers_unity":-1}},"worker_id":70003547,"operation_list":{"24":24},"worker_role_id":1},"70003550":{"operation_production":{"390":{"status":{"status_id_last":51,"status_id_all":{"249":{"operation_status_id":249,"status_id":51,"status_date_time":"2019-09-02 11:41:45","worker_id":1801}}},"order_operation_id":390,"operation_worker_id":71,"coordinate":"16812.93,-813.8101,-13272.49","group_workers_unity":-1}},"worker_id":70003550,"operation_list":{"26":26},"worker_role_id":1},"70003545":{"operation_production":{"391":{"status":{"status_id_last":51,"status_id_all":{"245":{"operation_status_id":245,"status_id":51,"status_date_time":"2019-09-02 11:41:45","worker_id":1801}}},"order_operation_id":391,"operation_worker_id":67,"coordinate":"16812.93,-813.8101,-13272.49","group_workers_unity":-1}},"worker_id":70003545,"operation_list":{"1":1},"worker_role_id":1}},"order_instructions":{},"cyclogram":{"2":{"shifts":{"3":{"shift_id":3,"chanes_cyclogram":{"764":{"chane_id":764,"cyclograms_operations":{"4869":4869,"4861":4861,"4865":4865,"4868":4868,"4864":4864,"4867":4867,"4863":4863,"4866":4866,"4862":4862}}},"cyclogram_option":{"date_time_start":"2019-09-02 20:00:00","date_time_end":"2019-09-03 02:00:00","max_section":100,"cyclegramm_type_id":2,"section_start":0,"section_end":100}}},"cyclograms_operations":{"4869":{"order_cyclegram_id":4869,"date_time_start":"2019-09-02 20:00:00","date_time_end":"2019-09-02 21:00:00","type_operation_id":1,"section_start":0,"section_end":0},"4861":{"order_cyclegram_id":4861,"date_time_start":"2019-09-02 23:46:00","date_time_end":"2019-09-03 02:00:00","type_operation_id":1,"section_start":100,"section_end":100},"4865":{"order_cyclegram_id":4865,"date_time_start":"2019-09-02 21:20:00","date_time_end":"2019-09-02 23:00:00","type_operation_id":2,"section_start":0,"section_end":100},"4868":{"order_cyclegram_id":4868,"date_time_start":"2019-09-02 21:00:00","date_time_end":"2019-09-02 21:06:40","type_operation_id":4,"section_start":0,"section_end":20},"4864":{"order_cyclegram_id":4864,"date_time_start":"2019-09-02 23:00:00","date_time_end":"2019-09-02 23:15:20","type_operation_id":4,"section_start":100,"section_end":80},"4867":{"order_cyclegram_id":4867,"date_time_start":"2019-09-02 21:06:40","date_time_end":"2019-09-02 21:13:20","type_operation_id":9,"section_start":20,"section_end":20},"4863":{"order_cyclegram_id":4863,"date_time_start":"2019-09-02 23:15:20","date_time_end":"2019-09-02 23:30:40","type_operation_id":9,"section_start":80,"section_end":80},"4866":{"order_cyclegram_id":4866,"date_time_start":"2019-09-02 21:13:20","date_time_end":"2019-09-02 21:20:00","type_operation_id":10,"section_start":20,"section_end":0},"4862":{"order_cyclegram_id":4862,"date_time_start":"2019-09-02 23:30:40","date_time_end":"2019-09-02 23:46:00","type_operation_id":10,"section_start":80,"section_end":100}}}},"outgoing":{"number_of_employees_by_roles":{"1":{"role_id":1,"count_worker":8}},"underground_in":8,"surface_in":0,"underground_out":8,"surface_out":0,"sum_workers_in":8,"sum_workers_out":0}}}}}';
//        $data_post = '{"department_order":{"702":{"brigade_id":"314","chane_id":"702","order":{"order_id":"166","order_status_id":50,"title":"Наряд участка 20028766","company_department_id":20028766,"department_type_id":3,"order_date_time":"2019-01-21","shift_id":3,"chane_id":702,"order_places":{"471":{"order_place_id":471,"place_id":122850,"passport_id":0,"reason":"","operation_production":{"1316":{"operation_id":113,"order_operation_id":1316,"operation_groups":{},"operation_value_plan":"6","operation_value_fact":"null","operation_load_value":0},"1317":{"operation_id":114,"order_operation_id":1317,"operation_groups":{},"operation_value_plan":"6","operation_value_fact":"null","operation_load_value":0},"1318":{"operation_id":187,"order_operation_id":1318,"operation_groups":{},"operation_value_plan":"6","operation_value_fact":"null","operation_load_value":0}}},"472":{"order_place_id":472,"place_id":122662,"passport_id":0,"reason":"","operation_production":{"1319":{"operation_id":4,"order_operation_id":1319,"operation_groups":{},"operation_value_plan":"6","operation_value_fact":"null","operation_load_value":6},"1320":{"operation_id":48,"order_operation_id":1320,"operation_groups":{},"operation_value_plan":"6","operation_value_fact":"null","operation_load_value":360}}}},"order_workers":{"2912299":{"operation_production":{},"worker_id":2912299,"type_skud":0,"worker_role_id":"187","operation_list":{}},"2909296":{"operation_production":{},"worker_id":2909296,"type_skud":0,"worker_role_id":"188","operation_list":{}},"2912343":{"operation_production":{},"worker_id":2912343,"type_skud":0,"worker_role_id":"188","operation_list":{}}},"order_instructions":{"1":{"instruction_pb_id":1},"3":{"instruction_pb_id":3}}}}}}';
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $warnings[] = 'SaveNewOrder. Начало метода';
            if ($data_post !== NULL && $data_post !== '') // Проверяем получены ли данные в формате JSON
            {
                $warnings[] = 'SaveNewOrder. Данные успешно переданы';
//                $warnings[] = 'SaveNewOrder. Входной массив данных' . $data_post;
                $warnings[] = 'SaveNewOrder. Декодировал входные параметры';
                $post_dec = json_decode($data_post); // Декодируем входную JSON строку в объект
            } else {
                throw new Exception('SaveNewOrder. Входная JSON строка не получена');
            }

            if (property_exists($post_dec, 'department_order')) {
                $warnings[] = 'SaveNewOrder. Данные с фронта получены';
            } else {
                throw new Exception('SaveNewOrder. Данные с фронта не получены');
            }
            $department_order = $post_dec->department_order;
            foreach ($department_order as $order_item) {
                $order_brigade_id = $order_item->brigade_id;
                $order = $order_item->order;
                $found_order = Order::find()
                    ->indexBy('id')
                    ->asArray()
                    ->all();//
                if (!empty($order)) {
                    if (isset($found_order[$order->order_id]['id'])) {
                        $old_order_id = $found_order[$order->order_id]['id'];
                        $get_order_place = OrderPlace::findOne(['order_id' => $old_order_id]);
                        if ($get_order_place != null) {
                            $delete_order_place = OrderPlace::deleteAll(['order_id' => $old_order_id]);
                            if ($delete_order_place != 0) {
                                $warnings[] = 'SaveNewOrder. Старые наяды на место удалены';
                            } else {
                                throw new Exception('SaveNewOrder. При удалении старого наряда на место произошла ошибка');
                            }
                        }
                    }
                    $order_id = $order->order_id;
                    /**
                     * Проверка есть ли хотябы одна операция на месте
                     */
//                        $warnings['order'] = $department_order;
                    if (!empty($order->order_places)) {
                        foreach ($order->order_places as $places) //перебор мест
                        {
                            if (property_exists($places, 'operation_production')) //проверяем есть ли в месте operation_prodaction
                            {
                                $place_operation = (array)$places->operation_production;
                                if (!empty($place_operation)) //проверяем есть ли операции, если найдена хотябы одна тогда выполняем код дальше
                                {
                                    $operation_count++;
                                    $warnings[] = 'SaveNewOrder. Операции найдены. Начинаю сохранение наряда.';
                                }
                            }
                        }
                    }
                    if ($operation_count === 0) { //если количество операций равно нулю тогда генерируем исключение
                        throw new Exception('SaveNewOrder. Список операций пуст');
                    }
                    /****************** Поиск звена ******************/
                    // $found_chane_id = Chane::find()
                    // ->where(['brigade_id' => $department_order->brigade_id])
                    // ->limit(1)
                    // ->one();//FIXME Подумать над необходимостью поиска звена
                    $order_company_department_id = $order->company_department_id;
                    $order_date_time = date('Y-m-d', strtotime($order->order_date_time));
                    $order_shift_id = $order->shift_id;
//                        $order_brigade_id = $brigade_id;
                    /************************************** Сохранение наряда *******************************************/
                    $get_order = Order::findOne(['id' => $order_id]);
                    if ($get_order == null) {
                        $result_save_new_order = self::FuncSaveOrder($order->title, //вызываем метод сохранения наряда с передачей нужных данных
                            $order_company_department_id,
                            $order_date_time,
                            $order_shift_id,
                            $order->chane_id,
                            $order_brigade_id);
                        if (!empty($result_save_new_order['errors'])) { //если массив ошибок не пуст при сохранении наряда, тогда генерируеи исключение
                            $errors[] = $result_save_new_order['errors'];
                            throw new Exception('SaveNewOrder .Ошибка при сохранении наряда');
                        }
                        $warnings[] = $result_save_new_order['warnings'];//записываем все предупреждения которые были в сохранении наряда
                        $order_id = $result_save_new_order['Items']['new_order_id'];
                    }
                    /**
                     * Сохранение статуса наряда
                     */
                    $new_order_status = new OrderStatus();
                    $new_order_status->order_id = $order_id;
                    $new_order_status->status_id = self::ORDER_CREATED;
                    $new_order_status->worker_id = $session['worker_id'];//идентификатор человека поменявшего статус
                    $new_order_status->date_time_create = $date__time_now;
                    $new_order_status->description = " ";
                    if ($new_order_status->save()) {
                        $warnings[] = 'SaveNewOrder. Статус наряда успешно сохранён.';
                    } else {
                        $errors[] = $new_order_status->errors;
                        throw new Exception('SaveNewOrder. Произошла ошибка при сохранении статуса наряда');
                    }
                    $get_order_instruction = OrderInstructionPb::find()
                        ->where(['order_id' => $order_id])
                        ->all();
                    if (!empty($get_order_instruction)) {
                        foreach ($get_order_instruction as $order_inst) {
                            $order_inst_id[] = $order_inst->instruction_pb_id;
                        }
                    }
                    if (!empty($order->order_instructions)) {
                        foreach ($order->order_instructions as $order_instruction) {
                            if ($order_instruction->instruction_pb_id != null) {
                                if (!in_array($order_instruction->instruction_pb_id, $order_inst_id)) {
                                    $new_order_instruction = new OrderInstructionPb();
                                    $new_order_instruction->order_id = $order_id;
                                    $new_order_instruction->instruction_pb_id = $order_instruction->instruction_pb_id;
                                    if ($new_order_instruction->save()) {
                                        $new_order_instruction->refresh();
                                        $new_order_instruction_id = $new_order_instruction->id;
                                        $warnings[] = 'SaveNewOrder. Предсменный инструктаж сохранен Новый айди = ' . $new_order_instruction_id;
                                    } else {
                                        $errors[] = $new_order_instruction->errors;
                                        throw new Exception('SaveNewOrder. Ошибка сохранения предсменного инструктаэа. Модели OrderInstructionPb');
                                    }
                                }
                            }
                        }
                    }
                    /**************************** Сохранение наряда на место *****************************************/
                    if (!empty($order->order_places)) {
                        foreach ($order->order_places as $order_place) {
                            if ($order_place->place_id != null) {
                                $new_order_place = new OrderPlace();
                                $new_order_place->order_id = $order_id;
                                $new_order_place->place_id = $order_place->place_id;
                                if ($order_place->edge_id) {
                                    $new_order_place->edge_id = $order_place->edge_id;
                                } else {
                                    $new_order_place->edge_id = null;
                                }
                                if (property_exists($order_place, 'coordinate') and $order_place->coordinate != "") {
                                    $new_order_place->coordinate = $order_place->coordinate;
                                } else {
                                    $new_order_place->coordinate = "0.0,0.0,0.0";
                                }
                                if (property_exists($order_place, 'description') and $order_place->description != "") {
                                    $new_order_place->description = $order_place->description;
                                }
                                if ($order_place->passport_id == 0) {
                                    $passport_id = null;
                                } else {
                                    $passport_id = $order_place->passport_id;
                                }
                                if ($order_place->route_template_id == 0) {
                                    $route_template_id = null;
                                } else {
                                    $route_template_id = $order_place->route_template_id;
                                }
                                $new_order_place->passport_id = $passport_id;
                                $new_order_place->route_template_id = $route_template_id;
                                if ($new_order_place->save()) {
                                    $new_order_place->refresh();
                                    $new_order_place_id = $new_order_place->id;
                                    $warnings[] = 'SaveNewOrder. Наряд на место ведения работ успешно сохранён. Идентификатор нового места ведения работ = ' . $new_order_place_id;
                                } else {
                                    $errors[] = $new_order_place->errors;
                                    throw new Exception('SaveNewOrder. Ошибка при сохранении наряда на место ведения работ. Модели OrderPlace');
                                }
                            }

                            /**
                             * создане начала массива на добавление
                             */
                            foreach ($order_place->operation_production as $operation_production) { //перебор всех операций
                                if ($operation_production->operation_id != null) {
                                    $add_order_operation = new OrderOperation();
                                    $add_order_operation->order_place_id = $new_order_place_id;
                                    $add_order_operation->operation_id = $operation_production->operation_id;
                                    $add_order_operation->equipment_id = $operation_production->equipment_id;
                                    if ($operation_production->edge_id) {
                                        $add_order_operation->edge_id = $operation_production->edge_id;
                                    } else {
                                        $add_order_operation->edge_id = null;
                                    }
                                    $add_order_operation->order_operation_id_vtb = $operation_production->order_operation_id_vtb;
                                    $add_order_operation->correct_measures_id = $operation_production->correct_measures_id;
                                    $add_order_operation->injunction_id = $operation_production->injunction_id;
                                    $add_order_operation->injunction_violation_id = $operation_production->injunction_violation_id;
                                    $add_order_operation->order_place_id_vtb = $operation_production->order_place_id_vtb;
                                    $add_order_operation->description = $operation_production->description;
                                    if (property_exists($add_order_operation, 'coordinate') and $operation_production->coordinate != "") {
                                        $add_order_operation->coordinate = $operation_production->coordinate;
                                    } else {
                                        $add_order_operation->coordinate = "0.0,0.0,0.0";
                                    }
                                    if ($operation_production->operation_value_plan) {
                                        $add_order_operation->operation_value_plan = (string)((float)str_replace(",", ".", $operation_production->operation_value_plan));
                                    } else {
                                        $add_order_operation->operation_value_plan = null;
                                    }
                                    if ($operation_production->operation_value_fact) {
                                        $add_order_operation->operation_value_fact = (string)((float)str_replace(",", ".", $operation_production->operation_value_fact));
                                    } else {
                                        $add_order_operation->operation_value_fact = null;
                                    }
                                    $add_order_operation->status_id = self::OPERATION_CREATED;
                                    if ($add_order_operation->save()) {
                                        $add_order_operation->refresh();
                                        $order_operation_id = $add_order_operation->id;
                                        $warnings[] = 'SaveNewOrder. Связка наряда на место и операции успешно сохранено';
                                    } else {
                                        $errors[] = $add_order_operation->errors;
                                        throw new Exception('SaveNewOrder. Ошибка при сохранении связки наряда на место и операции');
                                    }
                                    $order_operation_production[$operation_production->order_operation_id] = $order_operation_id;
                                }
                            }
                        }

                        if (!empty($order->order_workers)) {
                            foreach ($order->order_workers as $order_worker) //перебор работников
                            {
                                if (!empty($order_worker->operation_production)) {
                                    foreach ($order_worker->operation_production as $operation_worker) //перебр операций у работника
                                    {
                                        if ($order_worker->worker_id != null) {
                                            if (isset($operation_worker->coordinate, $operation_worker->group_workers_unity)) {
                                                $coordinate = $operation_worker->coordinate;
                                                $group_workers_unity = $operation_worker->group_workers_unity;
                                            }
                                        } else {
                                            $errors[] = 'SaveNewOrder. Нет работников для заполнения operation_workers';
                                        }
                                        if (isset($order_operation_production[$operation_worker->order_operation_id])) {
                                            $add_operation_worker = new OperationWorker();
                                            $add_operation_worker->order_operation_id = $order_operation_production[$operation_worker->order_operation_id];
                                            $add_operation_worker->worker_id = $order_worker->worker_id;
                                            $add_operation_worker->chane_id = $order_worker->chane_id;
                                            $add_operation_worker->brigade_id = $order_worker->brigade_id;
                                            $add_operation_worker->role_id = $order_worker->worker_role_id ? $order_worker->worker_role_id : 1;
                                            $add_operation_worker->status_id = self::OPERATION_CREATED;
                                            $add_operation_worker->date_time = $date_now;
                                            $add_operation_worker->coordinate = $coordinate;
                                            $add_operation_worker->group_workers_unity = $group_workers_unity;//                                    $warnings['$add_operation_worker'] = $add_operation_worker;
                                            if ($add_operation_worker->save()) {
                                                $add_operation_worker->refresh();
                                                $add_operation_worker_id = $add_operation_worker->id;
                                                $warnings[] = 'SaveNewOrder. Операция успешно закреплена за работником';
                                            } else {
                                                $errors[] = $add_operation_worker->errors;
                                                throw new Exception('SaveNewOrder. При закреплении операции за работником произошла ошибка');
                                            }
                                            $order_operation_worker_status[] = [$add_operation_worker_id,
                                                self::STATUS_ACTUAL,
                                                $date__time_now,
                                                $session['worker_id']];
                                        }
                                    }

                                }
                            }
                        }
                    }

                    if (!empty($order_operation_worker_status)) {
                        $insert_or_op_wo_status = Yii::$app->db->createCommand()->batchInsert('order_operation_worker_status',//insert_or_op_wo_status - insert order operation worker status
                            ['operation_worker_id', 'status_id', 'date_time', 'worker_id'],
                            $order_operation_worker_status)
                            ->execute();
                        unset($order_operation_worker_status);
                        if ($insert_or_op_wo_status !== 0) {
                            $warnings[] = 'SaveNewOrder. Статус привязки работы к месту и показателям успешно сохранён.';
                            $status *= 1;
                        } else {
                            $errors[] = 'SaveNewOrder. Статус привязки работы к месту и показателям не сохранён.';
                            $status = 0;
                        }
                    }
                    if (!empty($order->order_instruction)) {
                        foreach ($order->order_instruction as $order_instruction) {
                            $order_instruction_insert = [$order_id, $order_instruction['instruction_id']];
                        }
                        if (!empty($order_instruction_insert)) {
                            $insert_order_instruction = Yii::$app->db->createCommand()
                                ->batchInsert('order_instruction_pb',
                                    ['order_id', 'instruction_pb_id'], $order_instruction_insert)
                                ->execute();
                            if ($insert_order_instruction !== 0) {
                                $warnings[] = 'SaveNewOrder. Инструктажи в наряд успешно сохранены.';
                                $status *= 1;
                            } else {
                                $errors[] = 'SaveNewOrder. Инструктажи в наряд не сохранены.';
                                $status = 0;
                            }
                        }
                    }
                    $get_vkg_by_company_department = CompanyDepartmentWorkerVgk::find()
                        ->where(['company_department_id' => $order_company_department_id])
                        ->indexBy('worker_id')
                        ->asArray()
                        ->all();
                    if (!empty($order->order_worker_vgk)) {
                        foreach ($order->order_worker_vgk as $order_worker_vgk) {
                            if (!isset($get_vkg_by_company_department[$order_worker_vgk])) {
                                $order_worker_vgk_insert = [$order_company_department_id, $order_date_time, $order_worker_vgk];
                            }
                        }
                        if (!empty($order_worker_vgk_insert)) {
                            $insert_order_worker_vgk_ = Yii::$app->db->createCommand()
                                ->batchInsert('company_department_worker_vgk',
                                    ['company_department_id', 'date', 'worker_id'], $order_worker_vgk_insert)
                                ->execute();
                            if ($insert_order_worker_vgk_ !== 0) {
                                $warnings[] = 'SaveNewOrder. Связка ВГК в наряде успешно добавлена.';
                                $status *= 1;
                            } else {
                                $errors[] = 'SaveNewOrder. Связка ВГК в наряде не добавлена.';
                                $status = 0;
                            }
                        }
                    }
                    if (!empty($order->cyclogram)) {
                        $cyclogramm_data = $order->cyclogram;
                    } else {
                        $cyclogramm_data = array();
                    }
                } else {
                    $errors[] = 'SaveNewOrder. Нечего сохранять, так как department_order пустой';
                    $status = 0;
                }
            }
            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            $errors[] = 'SaveNewOrder. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = 'SaveNewOrder. Достигнут конец метода.';

// $errors[] = array_merge($errors, $get_new_order['errors']);
        if (!empty($errors)) {
            $status = 0;
        }
        if (
            property_exists($post_dec, 'cyclogram_option') &&
            property_exists($post_dec, 'cyclograms_operations') &&
            property_exists($post_dec, 'brigade_id') &&
            property_exists($post_dec, 'order')
        )                                                    // Проверяем наличие в нем нужных нам полей
        {
            $cyclogram_option = $post_dec->cyclogram_option;
            $cyclograms_operations = $post_dec->cyclograms_operations;
            $json = (object)array('cyclogram_option' => $cyclogram_option, 'cyclograms_operations' => $cyclograms_operations, 'brigade_id' => $order_brigade_id, 'company_department_id' => $order_company_department_id, 'date' => $order_date_time, 'shift_id' => $order_shift_id, 'order_id' => $order_id);
            $json = json_encode($json);//            $warnings['JSON_FOR_CYCLOGRAMM'] = $json;
            $save_cyclogramm = ReportForPreviousPeriodController::SaveReportCyclogram($json);
            if (count($save_cyclogramm['errors']) != 0) {
                $errors[] = $save_cyclogramm['errors'];
            }
//            $warnings['$cyclograms_operations'] = $cyclograms_operations;
//            $warnings['$cyclogram_option'] = $cyclogram_option;
        } else {
            foreach ($cyclogramm_data as $cyclogram_item) {
                $cyclograms_operations = $cyclogram_item->cyclograms_operations;
                foreach ($cyclogram_item->shifts as $shift) {
                    $cyclogram_option = $shift->cyclogram_option;
                }
            }
//            $warnings['$cyclograms_operations'] = $cyclograms_operations;
//            $warnings['$cyclogram_option'] = $cyclogram_option;
            $json = (object)array('cyclogram_option' => $cyclogram_option, 'cyclograms_operations' => $cyclograms_operations, 'brigade_id' => $order_brigade_id, 'company_department_id' => $order_company_department_id, 'date' => $order_date_time, 'shift_id' => $order_shift_id, 'order_id' => $order_id);
            $json = json_encode($json);//            $warnings['JSON_FOR_CYCLOGRAMM'] = $json;
            $save_cyclogramm = ReportForPreviousPeriodController::SaveReportCyclogram($json);
//            if (count($save_cyclogramm['errors']) != 0) {
//                $errors[] = $save_cyclogramm['errors'];
//            }
            $warnings[] = $save_cyclogramm['warnings'];
        }

        $json_for_get_order = '{"company_department_id":' . $order_company_department_id . ',"date_time":"' . $order_date_time . '","shift_id":' . $order_shift_id . ',"brigade_id":"' . $order_brigade_id . '"}';
        $get_new_order = self::GetOrder($json_for_get_order);
        $save_order = $get_new_order['Items'];
        $warnings[] = array_merge($warnings, $get_new_order['warnings']);

        $result = $save_order;

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /*
     * При корректировке наряда человек может сделать следуюзие действия:
     *                                                                  • Удалить место со всеми операциями и людьми в этой операции
     *                                                                  • Удалить операцию на месте
     *                                                                  • Добавление/удаление людей
     *                                                                  • Добавление места
     *                                                                  • Добвление операции на место
     */
    /**
     * Метод CorrectOrder() - корректировка наряда
     * @param null $data_post - JSON структура корректировки наряда
     *                                  correct_order
     *                                           order_id:
     *                                           order_status_descriptionorder_status_description:
     *                                           company_department_id:
     *                                           attachment_path:
     *                                           brigade_id:
     *                                           [new_order_place]
     *                                                    [order_place]
     *                                                                  [place_id]
     *                                                                   place_id:
     *                                                                   passport_id:
     *                                                                   [order_production]
     *                                                                             [operation_id]
     *                                                                                     operation_id:
     *                                                                                     operation_value_plan:
     *                                                                                     operation_value_fact:
     *                                                    [order_workers]
     *                                                            [worker_id]
     *                                                                   worker_id:
     *                                                                   [operation_production]
     *                                                                               [operation_id]
     *                                                                                      operation_id:
     *                                                                   status_last:
     *                                                                   [status_all]
     *                                                                         [order_operation_worker_status_id]
     *                                                                                         order_operation_worker_status_id:
     *                                                                                         status_id:
     *                                           [correct_exist_order]
     *                                                     [order_operation_worker_id]
     *                                                                  operation_value_plan:
     *                                                                  order_operation_worker_id:
     *                                                                  status_id:
     *                                           [new_instructions]
     *                                                     [instruction_pb_id]
     *                                                                 instruction_pb_id:
     * @return array - массив из метода GetOrder
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=CorrectOrder&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 04.07.2019 15:54
     */
    public static function CorrectOrder($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $session = Yii::$app->session;
        $date_time_now = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));
        $date_now = date('Y-m-d', strtotime(BackendAssistant::GetDateNow()));
        $errors = array();                                                                                              // Массив ошибок
//        $order_operation_workers = array();                                                                                              // Массив ошибок
        $order_operation_worker_status = array();                                                                                              // Массив ошибок
        $correct_orders = array();                                                                                         // Промежуточный результирующий массив
        $order_operation_id = null;
        $coordinate = '0.0,0.0,0.0';
        $group_workers_unity = 0;
        try {
            $warnings[] = 'CorrectOrder. Начало метода';
            if ($data_post !== NULL && $data_post !== '')                                                               // Проверяем получены ли данные в формате JSON
            {
                $warnings[] = 'CorrectOrder. Данные успешно переданы';
                $warnings[] = 'CorrectOrder. Входной массив данных' . $data_post;
                $warnings[] = 'CorrectOrder. Декодировал входные параметры';
            } else {
                throw new Exception('CorrectOrder. Входная JSON строка не получена');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входную JSON строку в объект
            if (
                property_exists($post_dec, 'correct_order')
            )                                                                                                       // Проверяем наличие в нем нужных нам полей
            {
                $correct_order = $post_dec->correct_order;
                $corrected_order_id = $correct_order->order_id;
                $warnings[] = 'CorrectOrder.Данные с фронта получены';
                $warnings[] = 'CorrectOrder. Начинаю сохранение статуса наряда';
                /******************** СОХРАНЕНИЕ НОВОГО СТАТУСА НАРЯДА ********************/
                $new_status_order = new OrderStatus();
                $new_status_order->order_id = $correct_order->order_id;
                $new_status_order->status_id = self::ORDER_CORRECTED;
                $new_status_order->worker_id = $session['worker_id'];
                $new_status_order->date_time_create = $date_time_now;
                if (strlen($correct_order->order_status_description) <= 255) {
                    $new_status_order->description = $correct_order->order_status_description;
                } else {
                    throw new Exception('CorrectOrder. Причина корректировки не может быть больше 255 символов');
                }
                if ($new_status_order->save()) {
                    $new_status_order->refresh();
                    $warnings[] = 'CorrectOrder. Статус наряда успешно сохранён. Идентификатор сохранённого статуса = ' . $new_status_order->id;
                    $warnings[] = 'CorrectOrder. Начинаю сохранение вложения.';
                } else {
                    $errors[] = $new_status_order->errors;
                    throw new Exception('CorrectOrder. Статус наряда не сохранён');
                }
                Order::updateAll(['status_id' => self::ORDER_CORRECTED], ['id' => $correct_order->order_id]);
                /******************** СОХРАНЕНИЕ ВЛОЖЕНИЯ ********************/
//                $attachment_arr = (array)$correct_order->attachment_path;
                if ($correct_order->attachment !== null) {
//                    $file = isset($_FILES['attachment_path']) ? $_FILES['attachment_path'] : null;                                                //-----
//                    $result_save_attachment = self::UploadCorrectOrderAttachment($new_status_order->id,$file,'name','php');
//                    $warnings['order_status_attachment_id'] = $result_save_attachment['order_status_attachment'];
//                    $errors = array_merge($errors, $result_save_attachment['errors']);
                    $upload_attachment = Assistant::UploadFile($correct_order->attachment->attachment_file, $correct_order->attachment->attachment_name, 'order_status_attachment', $correct_order->attachment->attachment_extension);
                    $add_order_attachment = new OrderStatusAttachment();
                    $add_order_attachment->order_status_id = $new_status_order->id;
                    $add_order_attachment->attachment_path = $upload_attachment;
                    if ($add_order_attachment->save()) {
                        $warnings[] = 'CorrectOrder. Вложение успешно сохранено';
                    } else {
                        $errors[] = $add_order_attachment->errors;
                        throw new Exception('CorrectOrder. Ошибка при сохранении вложения');
                    }

                }
                /********************  ДОБАВЛЕНИЕ НОВОГО НАРЯДА ********************/
//                $order_place_arr = (array)$correct_order->new_order_place;
                if ($correct_order->new_order_place !== null) {
                    $warnings[] = 'CorrectOrder. Начинаю сохранение нового наряда на место';
                    foreach ($correct_order->new_order_place->order_place as $order_place_new) {
                        if ($order_place_new->order_place_id === null) {
                            /******************** СОХРАНЕНИЕ НАРЯДА НА МЕСТО ********************/
                            $new_order_place = new OrderPlace();
                            $new_order_place->order_id = $correct_order->order_id;
                            $new_order_place->place_id = $order_place_new->place_id;
                            if ($order_place_new->edge_id) {
                                $new_order_place->edge_id = $order_place_new->edge_id;
                            } else {
                                $new_order_place->edge_id = null;
                            }
                            $new_order_place->passport_id = $order_place_new->passport_id;
                            if (property_exists($order_place_new, 'coordinate') and $order_place_new->coordinate != "") {
                                $new_order_place->coordinate = $order_place_new->coordinate;
                            } else {
                                $new_order_place->coordinate = "0.0,0.0,0.0";
                            }
                            if (property_exists($order_place_new, 'description') and $order_place_new->description != "") {
                                $new_order_place->description = $order_place_new->description;
                            }
                            if (property_exists($order_place_new, 'route_template_id') and $order_place_new->route_template_id != "") {
                                $new_order_place->route_template_id = $order_place_new->route_template_id;
                            } else {
                                $new_order_place->route_template_id = null;
                            }
                            if ($new_order_place->save()) {
                                $new_order_place->refresh();
                                $order_place_id = $new_order_place->id;
                                $warnings[] = 'CorrectOrder. Наряд на место успешно добавлен';
                            } else {
                                $errors[] = $new_order_place->errors;
                                throw new Exception('CorrectOrder. Наряд на место не сохранён');
                            }
                        } else {
                            $found_order_place = OrderPlace::find()
                                ->where(['id' => $order_place_new->order_place_id])
                                ->limit(1)
                                ->one();
                            $order_place_id = $found_order_place->id;
                        }
                        $warnings[] = 'CorrectOrder. Массовая вставка в таблицу объёма работы по плану (по наряду)';
                        foreach ($order_place_new->order_production as $order_production) {
                            $data_for_insert_operation_worker[$order_production->operation_id] =       //в массив по ключу (идентификатору операции на работника) записываю план, факт и идентификатор наряда на место
                                [
                                    'value_plan' => $order_production->operation_value_plan,
                                    'order_place' => $order_place_id
                                ];
                        }
                    }
                    /******************** ДЕЛАЕМ МАССИВ ДЛЯ ДОБАВЛЕНИЯ РАБОТНИКОВ В ОПЕРАЦИИ ********************/
                    foreach ($correct_order->new_order_place->order_workers as $order_worker)                                               //перебор работников
                    {

                        foreach ($order_worker->operation_production as $operation_worker)                              //перебр операций у работника
                        {
                            if (isset($operation_worker->coordinate, $operation_worker->group_workers_unity)) {
                                $coordinate = $operation_worker->coordinate;
                                $group_workers_unity = $operation_worker->group_workers_unity;
                            }
//                            $order_operation_workers[] =                                                                //генерируем массив на добавление объёма работы по плану (по наряда)
//                                [
//                                    0 => $data_for_insert_operation_worker[$operation_worker->operation_id]['order_place'],   // идентификатор наряда на место
//                                    1 => $operation_worker->operation_id,
//                                    2 => $order_worker->worker_id,
//                                    3 => $date_now,
//                                    4 => $data_for_insert_operation_worker[$operation_worker->operation_id]['value_plan'],   //объём по плану
//                                    5 => self::OPERATION_CREATED,
//                                    6 => $coordinate,
//                                    7 => $group_workers_unity,
//                                    8 => $operation_worker->role_id
//                                ];
                            $found_order_op = OrderOperation::findOne(['order_place_id' => $data_for_insert_operation_worker[$operation_worker->operation_id]['order_place'],
                                'operation_id' => $operation_worker->operation_id]);
                            if (!$found_order_op) {
                                $add_order_operation = new OrderOperation();
                                $add_order_operation->order_place_id = $data_for_insert_operation_worker[$operation_worker->operation_id]['order_place'];
                                $add_order_operation->operation_id = $operation_worker->operation_id;
                                $add_order_operation->equipment_id = $operation_worker->equipment_id;
                                if ($operation_worker->edge_id) {
                                    $add_order_operation->edge_id = $operation_worker->edge_id;
                                } else {
                                    $add_order_operation->edge_id = null;
                                }
                                $add_order_operation->order_operation_id_vtb = $operation_worker->order_operation_id_vtb;
                                $add_order_operation->correct_measures_id = $operation_worker->correct_measures_id;
                                $add_order_operation->injunction_violation_id = $operation_worker->injunction_violation_id;
                                $add_order_operation->injunction_id = $operation_worker->injunction_id;
                                $add_order_operation->order_place_id_vtb = $operation_worker->order_place_id_vtb;
                                $add_order_operation->description = $operation_worker->description;
                                if ($operation_worker->operation_value_plan) {
                                    $add_order_operation->operation_value_plan = (string)((float)str_replace(",", ".", $data_for_insert_operation_worker[$operation_worker->operation_id]['value_plan']));
                                } else {
                                    $add_order_operation->operation_value_plan = null;
                                }
                                if ($operation_worker->operation_value_fact) {
                                    $add_order_operation->operation_value_fact = (string)((float)str_replace(",", ".", $operation_worker->operation_value_fact));
                                } else {
                                    $add_order_operation->operation_value_fact = null;
                                }

                                if (property_exists($operation_worker, 'coordinate') and $operation_worker->coordinate != "") {
                                    $add_order_operation->coordinate = $operation_worker->coordinate;
                                } else {
                                    $add_order_operation->coordinate = "0.0,0.0,0.0";
                                }
                                $add_order_operation->status_id = self::OPERATION_CREATED;
                                if ($add_order_operation->save()) {
                                    $add_order_operation->refresh();
                                    $order_operation_id = $add_order_operation->id;
                                    $warnings[] = 'SaveNewOrder. Связка наряда на место и операции успешно сохранено';
                                } else {
                                    $errors[] = $add_order_operation->errors;
                                    throw new Exception('SaveNewOrder. Ошибка при сохранении связки наряда на место и операции');
                                }
                            }
                            $order_operation_id = $add_order_operation->id;
                            $add_operation_worker = new OperationWorker();
                            $add_operation_worker->order_operation_id = $order_operation_id;
                            $add_operation_worker->worker_id = $order_worker->worker_id;
                            $add_operation_worker->role_id = $order_worker->worker_role_id;
                            $add_operation_worker->date_time = $date_now;
                            $add_operation_worker->status_id = self::ORDER_CORRECTED;
                            $add_operation_worker->coordinate = $order_worker->coordinate;
                            $add_operation_worker->brigade_id = $order_worker->brigade_id;
                            $add_operation_worker->chane_id = $order_worker->chane_id;
                            $add_operation_worker->group_workers_unity = $group_workers_unity;
                            if ($add_operation_worker->save()) {
                                $add_operation_worker->refresh();
                                $add_operation_worker_id = $add_operation_worker->id;
                                $warnings[] = 'SaveNewOrder. Операция успешно закреплена за работником';
                            } else {
                                $errors[] = $add_operation_worker->errors;
                                throw new Exception('SaveNewOrder. При закреплении операции за работником произошла ошибка');
                            }
                            $order_operation_worker_status[] = [$add_operation_worker_id,
                                self::STATUS_ACTUAL,
                                $date_time_now,
                                $session['worker_id']];
                        }
                    }
                    $insert_or_op_wo_status = Yii::$app->db->createCommand()->batchInsert('order_operation_worker_status',//insert_or_op_wo_status - insert order operation worker status
                        ['operation_worker_id', 'status_id', 'date_time', 'worker_id'],
                        $order_operation_worker_status)
                        ->execute();
                    unset($order_operation_worker_status);
                    if ($insert_or_op_wo_status !== 0) {
                        $warnings[] = 'SaveNewOrder. Статус привязки работы к месту и показателям успешно сохранён.';
                        $status *= 1;
                    } else {
                        $errors[] = 'SaveNewOrder. Статус привязки работы к месту и показателям не сохранён.';
                    }
                }
                /********************  КОРРЕКТИРОВКА НАРЯДА ********************/
//                    $correct_exist_arr = (array)$correct_order->correct_exist_order;
                if ($correct_order->correct_exist_order !== null) {
                    foreach ($correct_order->correct_exist_order as $correct_exist_order)                           //перебор людей у которых необходимо сменить статус
                    {
                        if (isset($correct_exist_order->operation_worker_id)) {
                            $found_worker = OrderOperationWorkerStatus::findOne(['operation_worker_id' => $correct_exist_order->operation_worker_id]);
                            if ($found_worker !== null) {
                                if ($found_worker->status_id == $correct_exist_order->status_id_last) {
                                    $found_order_op_wo = OrderOperation::findOne(['order_place_id' => $correct_exist_order->order_place_id,
                                        'operation_id' => $correct_exist_order->operation_id]);
                                    if ($found_order_op_wo !== null) {
                                        $found_order_op_wo->operation_value_plan = $correct_exist_order->operation_value_plan;
                                        if ($found_order_op_wo->save()) {
                                            $warnings[] = 'CorrectOrder. Новое планове значение успешно установлено';
                                        } else {
                                            throw new Exception('CorrectOrder. Ошибка при смене статуса у прошлой операции на человека');
                                        }
                                    } else {
                                        throw new Exception("CorrectOrder. Работник с идентификатором: {$correct_exist_order->order_operation_worker_id} не найден");
                                    }
                                } else {
                                    /******************* СМЕНА СТАТУСА У РАБОТНИКА НА ОПЕРАЦИИ *******************/
                                    if ($correct_exist_order->status_id_last !== null) {
                                        $status_id_correct = $correct_exist_order->status_id_last;
                                    } else {
                                        $status_id_correct = self::OPERATION_CREATED;
                                    }
                                    $warnings[] = 'CorrectOrder. Начинаю сохранение статуса привязки работы к месту и показателяем';
                                    $add_order_operation_wo_status = new OrderOperationWorkerStatus();
                                    $add_order_operation_wo_status->status_id = $status_id_correct;
                                    $add_order_operation_wo_status->operation_worker_id = $correct_exist_order->operation_worker_id;
                                    $add_order_operation_wo_status->date_time = $date_time_now;
                                    $add_order_operation_wo_status->worker_id = $session['worker_id'];
                                    if ($add_order_operation_wo_status->save()) {
                                        $found_order_op_wo = OperationWorker::findOne(['id' => $correct_exist_order->operation_worker_id]);
                                        $found_order_op_wo->status_id = $status_id_correct;
                                        if ($found_order_op_wo->save()) {
                                            $warnings[] = 'CorrectOrder. Статус привязки работы к месту и показателяем успешно сохранён';
                                        }
                                    } else {
                                        $errors[] = $add_order_operation_wo_status->errors;
                                        throw new Exception('CorrectOrder. Произошла ошибка при смене статуса у привязки работы к месту и показателяем');
                                    }
                                }
                            }
                        } else {
                            $found_order_op_wo = OrderOperation::findOne(['order_place_id' => $correct_exist_order->order_place_id,
                                'operation_id' => $correct_exist_order->operation_id]);
                            if ($found_order_op_wo !== null) {
                                $found_order_op_wo->operation_value_plan = $correct_exist_order->operation_value_plan;
                                if ($found_order_op_wo->save()) {
                                    $warnings[] = 'CorrectOrder. Новое планове значение успешно установлено';
                                } else {
                                    throw new Exception('CorrectOrder. Ошибка при смене статуса у прошлой операции на человека');
                                }
                            } else {
                                throw new Exception("CorrectOrder. Работник с идентификатором: {$correct_exist_order->order_operation_worker_id} не найден");
                            }
                        }
                    }
                }
                /******************** ДОБАВЛЕНИЕ РАБОТНИКА В НАРЯД ********************/
//                    $new_worker_at_order = (array)$correct_order->add_worker_at_order;
                if ($correct_order->add_worker_at_order !== null) {
                    foreach ($correct_order->add_worker_at_order as $new_worker_in_order_place) {
                        foreach ($new_worker_in_order_place->worker_list as $worker) {
//                            $add_worker_in_order_place = new OrderOperationWorker();
//                            $add_worker_in_order_place->order_place_id = $new_worker_in_order_place->order_place_id;
//                            $add_worker_in_order_place->operation_id = $new_worker_in_order_place->operation_id;
//                            $add_worker_in_order_place->worker_id = $worker->worker_id;
//                            $add_worker_in_order_place->date_time = $date_time_now;
//                            $add_worker_in_order_place->operation_value_plan = (string)$new_worker_in_order_place->operation_value_plan;
//                            $add_worker_in_order_place->status_id = self::STATUS_ACTUAL;
//                            $add_worker_in_order_place->role_id = $worker->worker_role_id;
//                            if ($add_worker_in_order_place->save()) {
//                                $warnings[] = 'CorrectOrder. В наряд добавлен новый работник';
//                                $add_worker_in_order_place->refresh();
//                            } else {
//                                $errors[] = $add_worker_in_order_place->errors;
//                                throw new Exception('CorrectOrder. Ошибка при добавлении работника в наряд');
//                            }
                            $add_operation_worker = new OperationWorker();
                            $add_operation_worker->order_operation_id = $new_worker_in_order_place->order_operation_id;
                            $add_operation_worker->worker_id = $worker->worker_id;
                            $add_operation_worker->status_id = self::STATUS_ACTUAL;
                            $add_operation_worker->date_time = $date_time_now;
                            $add_operation_worker->role_id = $worker->worker_role_id;
                            if ($add_operation_worker->save()) {
                                $add_operation_worker->refresh();
                                $add_operation_worker_id = $add_operation_worker->id;
                                $warnings[] = 'CorrectOrder. Привязка операции к работнику успешно сохранена';
                            } else {
                                $errors[] = $add_operation_worker->errors;
                                throw new Exception('CorrectOrder. Ошибка при сохранении привязки работника к операции');
                            }
                            /******************** ДОБАВЛЕНИЕ СТАТУСА У НОВОГО РАБОТНИКА ********************/
                            $add_wo_op_status = new OrderOperationWorkerStatus();
                            $add_wo_op_status->operation_worker_id = $add_operation_worker_id;
                            $add_wo_op_status->status_id = self::STATUS_ACTUAL;
                            $add_wo_op_status->date_time = $date_time_now;
                            $add_wo_op_status->worker_id = $session['worker_id'];
                            if ($add_wo_op_status->save()) {
                                $warnings[] = 'CorrectOrder. Статус на человека успешно сохранён';
                            } else {
                                throw new Exception('CorrectOrder. Статус на человека небыл сохранён');
                            }
                        }
                    }
                }
                /******************** ДОБАВЛЕНИЕ ИнструктажЕЙ ********************/
//                    $new_instr_arr = (array)$correct_order->new_instructions;
                if ($correct_order->new_instructions !== null) {
                    foreach ($correct_order->new_instructions as $new_instruction) {
                        $warnings[] = 'CorrectOrder. Начинаю сохранение инструктажей.';
                        $add_order_instruction = new OrderInstructionPb();
                        $add_order_instruction->order_id = $correct_order->order_id;
                        $add_order_instruction->instruction_pb_id = $new_instruction->instruction_pb_id;
                        if ($add_order_instruction->save()) {
                            $warnings[] = 'CorrectOrder. Инструктаж на наряд успешно добавлен';
                        } else {
                            $errors[] = $add_order_instruction->errors;
                            throw new Exception('CorrectOrder. Ошибка при сохранении инструктажа для наряда');
                        }
                    }
                }
            } else {
                $errors[] = 'CorrectOrder. Переданы некорректные входные параметры';
                $status *= 0;
            }
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $order_corrected = Order::findOne(['id' => $corrected_order_id]);                                                 //ищем значение только что скорректированного наряда
        if ($order_corrected !== null)                                                                                  //если наряд найден тогда возвращаем структуру из метода GetOrder
        {
            $json_for_get_order = '{"company_department_id":' . $order_corrected->company_department_id . ',"date_time":"' . $order_corrected->date_time_create . '","shift_id":' . $order_corrected->shift_id . ',"brigade_id":"' . $order_corrected->brigade_id . '"}';
            $get_new_order = self::GetOrder($json_for_get_order);
            $correct_orders = $get_new_order['Items'];
            $warnings[] = array_merge($warnings, $get_new_order['warnings']);
            $errors[] = array_merge($errors, $get_new_order['errors']);
        }

        $result = $correct_orders;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Название метода: GetWorkersVgk()
     * GetWorkersVgk - Метод получения списка работников с пометкой ВГК, бригадиров, горного мастера
     *
     * @param null $data_post
     * @return array
     * @package frontend\controllers\ordersystem
     * @example http://web.amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetWorkersVgk&subscribe=ff&data={%22company_department_id%22:4029938,%22date%22:%222019-06-30%22,%22shift_id%22:1}
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 19.06.2019 12:59
     * @since ver
     */
    public static function GetWorkersVgk($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $workers_vgk = array();                                                                                         // Промежуточный результирующий массив
        $workers_vgk_group = array();                                                                                         // Промежуточный результирующий массив
        $all_vgk_on_comp_dep = array();                                                                                         // Промежуточный результирующий массив
        try {
            if ($data_post !== NULL && $data_post !== '') {
                $warnings[] = 'GetWorkersVgk. Данные успешно переданы';
                $warnings[] = 'GetWorkersVgk. Входной массив данных' . $data_post;
            } else {
                throw new Exception('GetWorkersVgk. Данные с фронта не получены');
            }
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetWorkersVgk. Декодировал входные параметры';
            if (
                property_exists($post_dec, 'date') &&
                property_exists($post_dec, 'company_department_id') &&
                property_exists($post_dec, 'shift_id')
            )                                                             // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'GetWorkersVgk.Данные с фронта получены';
            } else {
                throw new Exception('GetWorkersVgk. Переданы некорректные входные параметры');
            }
            $date = date('Y-m-d', strtotime($post_dec->date));
            $company_department_id = $post_dec->company_department_id;
            $shift_id = $post_dec->shift_id;
//            $sql_filter = '';

            /**
             * Выбираем список членов ВГК и горных мастеров на участке согласно графику выходов
             */
            $workers_vgk = GraficTabelMain::find()
                ->select([
                    'worker.vgk',
                    'grafic_tabel_date_plan.role_id as worker_role',
                    'employee.last_name',
                    'employee.first_name',
                    'employee.patronymic',
                    'role.title as worker_role_title',
                    'worker.id AS worker_id'
                ])
                ->innerJoin('grafic_tabel_date_plan', 'grafic_tabel_date_plan.grafic_tabel_main_id = grafic_tabel_main.id')
                ->innerJoin('worker', 'worker.id = grafic_tabel_date_plan.worker_id')
                ->innerJoin('employee', 'worker.employee_id = employee.id')
                ->innerJoin('role', 'grafic_tabel_date_plan.role_id = role.id')
                ->where(['<=', 1, 1])                                                                                   // todo исправить костыль
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date],
                    ['is', 'worker.date_end', null]
                ])
                ->andWhere(['grafic_tabel_date_plan.shift_id' => $shift_id,
                    'grafic_tabel_date_plan.date_time' => $date,
                    'grafic_tabel_main.company_department_id' => $company_department_id])
                ->andWhere(
                    'worker.vgk = ' . self::VGK_STATE
                    . ' OR grafic_tabel_date_plan.role_id = ' . self::GORN_MASTER_ROLE
                    . ' OR grafic_tabel_date_plan.role_id = ' . self::GORN_MASTER_ROLE_SOUT
                    . ' OR grafic_tabel_date_plan.role_id = ' . self::EL_MECH_ROLE
                )   // Либо он ВГК бригадир в бригаде либо Горн.Мастер на участке
                ->orderBy('worker_role DESC, employee.last_name')
                ->asArray()
                ->limit(200)
                ->all();
            $warnings[] = 'GetWorkersVgk. Получение списка всех ВГК на участке';
            if (isset($workers_vgk)) {
                foreach ($workers_vgk as $worker) {
                    $workers_vgk_group[$worker['worker_id']]['worker_id'] = (int)$worker['worker_id'];
                    if (
                        isset($workers_vgk_group[$worker['worker_id']]['worker_role']) and
                        (
                            $worker['worker_role'] == self::GORN_MASTER_ROLE or $worker['worker_role'] == self::GORN_MASTER_ROLE_SOUT
                        )
                    ) {
                        $workers_vgk_group[$worker['worker_id']]['worker_role'] = (int)$worker['worker_role'];
                    } else if (!isset($workers_vgk_group[$worker['worker_id']]['worker_role'])) {
                        $workers_vgk_group[$worker['worker_id']]['worker_role'] = (int)$worker['worker_role'];
                    }
                    if (isset($workers_vgk_group[$worker['worker_id']]['worker_role']) and $worker['vgk'] == 1) {
                        $workers_vgk_group[$worker['worker_id']]['vgk'] = (int)1;
                    } else if (!isset($workers_vgk_group[$worker['worker_id']]['vgk'])) {
                        $workers_vgk_group[$worker['worker_id']]['vgk'] = (int)$worker['vgk'];
                    }
                    $name = mb_substr($worker['first_name'], 0, 1);
                    $patronymic = mb_substr($worker['patronymic'], 0, 1);
                    $workers_vgk_group[$worker['worker_id']]['full_name'] = "{$worker['last_name']} {$name}. {$patronymic}.";
                    $workers_vgk_group[$worker['worker_id']]['worker_full_name'] = "{$worker['last_name']} {$worker['first_name']} {$worker['patronymic']}";
                    $workers_vgk_group[$worker['worker_id']]['worker_role_title'] = $worker['worker_role_title'];
                }
                $workers_vgk = [];
                foreach ($workers_vgk_group as $worker) {
                    $workers_vgk[] = $worker;
                }
            } else {
                $errors[] = 'GetWorkersVgk. нет ни одного ВГК на участке по графику выходов';
            }
            unset($workers_vgk_group);
            $workers_vgk_group = array();
            /**
             * Получаем список горных мастеров с графика выходов на обозначенную смену, день и департамент
             */

            /******************** ВСЕ ВГК НА УЧАСТКЕ НУЖНЫ ДЛЯ ВЫПАДАЮЩЕГО СПИСКА ДОБАВЛЕНИЯ ВГК ********************/

            // Для отображения ролей закинуть в селект IF(worker.vgk=1, worker.vgk, role.id) AS worker_role
            $all_vgk_on_comp_dep = GraficTabelMain::find()
                ->select([
                    'worker.vgk',
                    'grafic_tabel_date_plan.role_id as worker_role',
                    'employee.last_name',
                    'employee.first_name',
                    'employee.patronymic',
                    'role.title as worker_role_title',
                    'worker.id AS worker_id'
                ])
                ->innerJoin('grafic_tabel_date_plan', 'grafic_tabel_date_plan.grafic_tabel_main_id = grafic_tabel_main.id')
                ->innerJoin('worker', 'worker.id = grafic_tabel_date_plan.worker_id')
                ->innerJoin('employee', 'worker.employee_id = employee.id')
                ->innerJoin('role', 'grafic_tabel_date_plan.role_id = role.id')
                ->where(['<=', 1, 1])                                                                                   // todo исправить костыль
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date],
                    ['is', 'worker.date_end', null]
                ])
                ->andWhere([
                    'MONTH(grafic_tabel_date_plan.date_time)' => date('m', strtotime($date)),
                    'YEAR(grafic_tabel_date_plan.date_time)' => date('Y', strtotime($date)),
//                    'grafic_tabel_date_plan.month' => date('m', strtotime($date)),
                    'grafic_tabel_main.company_department_id' => $company_department_id])
                ->andWhere('(worker.vgk = ' . self::VGK_STATE . ') OR grafic_tabel_date_plan.role_id IN (175, 176, 177, 178, 179, 180, 181, 195, 237)')   // Либо он ВГК бригадир в бригаде либо Горн.Мастер на участке
                ->orderBy('worker_role DESC, employee.last_name')
                ->groupBy([
                    'worker.vgk',
                    'worker_role',
                    'employee.last_name',
                    'employee.first_name',
                    'employee.patronymic',
                    'worker_id'
                ])
                ->asArray()
                ->limit(200)
                ->all();
            if (isset($all_vgk_on_comp_dep)) {
                foreach ($all_vgk_on_comp_dep as $worker) {
                    $workers_vgk_group[$worker['worker_id']]['worker_id'] = $worker['worker_id'];
                    $workers_vgk_group[$worker['worker_id']]['last_name'] = $worker['last_name'];
                    $workers_vgk_group[$worker['worker_id']]['first_name'] = $worker['first_name'];
                    $workers_vgk_group[$worker['worker_id']]['patronymic'] = $worker['patronymic'];
                    $workers_vgk_group[$worker['worker_id']]['worker_full_name'] = "{$worker['last_name']} {$worker['first_name']} {$worker['patronymic']}";
                    $workers_vgk_group[$worker['worker_id']]['worker_role_title'] = $worker['worker_role_title'];

                    if (isset($workers_vgk_group[$worker['worker_id']]['worker_role']) and ($worker['worker_role'] == self::GORN_MASTER_ROLE or $worker['worker_role'] == self::GORN_MASTER_ROLE_SOUT)) {
                        $workers_vgk_group[$worker['worker_id']]['worker_role'] = (int)$worker['worker_role'];
                    } else if (!isset($workers_vgk_group[$worker['worker_id']]['worker_role'])) {
                        $workers_vgk_group[$worker['worker_id']]['worker_role'] = (int)$worker['worker_role'];
                    }
                    if (isset($workers_vgk_group[$worker['worker_id']]['worker_role']) and $worker['vgk'] == 1) {
                        $workers_vgk_group[$worker['worker_id']]['vgk'] = (int)1;
                    } else if (!isset($workers_vgk_group[$worker['worker_id']]['vgk'])) {
                        $workers_vgk_group[$worker['worker_id']]['vgk'] = (int)$worker['vgk'];
                    }
                }
                $all_vgk_on_comp_dep = [];
                foreach ($workers_vgk_group as $worker) {
                    $all_vgk_on_comp_dep[] = $worker;
                }
            } else {
                $errors[] = 'GetWorkersVgk. нет ни одного ВГК на участке';
            }
            $warnings[] = 'GetWorkersVgk. Метод закончил работу';
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $result = ['all_rescuers' => $all_vgk_on_comp_dep, 'filtered_rescuers' => $workers_vgk];
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод FuncSaveOrder() - метод сохранения наряда, вызывается  в методе SaveNewOrder
     * @param $title
     * @param $company_department_id
     * @param $date_time
     * @param $shift_id
     * @param $chane_id
     * @param $brigade_id
     * @return array
     *
     * @package frontend\controllers\ordersystem
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 09.07.2019 8:07
     */
    private static function FuncSaveOrder($title, $company_department_id, $date_time, $shift_id, $chane_id, $brigade_id)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $save_order = array();
        try {
            $warnings[] = 'FuncSaveOrder. Начинаю сохранение наряда.';
            $new_order = new Order();
            $new_order->title = $title;
            $new_order->company_department_id = $company_department_id;
            $new_order->date_time_create = $date_time;
            $new_order->object_id = self::OBJ_ORDER;
            $new_order->shift_id = $shift_id;
            $new_order->chane_id = $chane_id;
            $new_order->brigade_id = $brigade_id;
            if ($new_order->save()) {
                $new_order->refresh();
                $new_order_id = $new_order->id;
                $save_order['new_order_id'] = $new_order_id;
                $warnings[] = 'FuncSaveOrder. Наряд успешно сохранён. Идентификатор нового наряда =' . $new_order_id;
            } else {
                $errors['func_save_error'] = $new_order->errors;
                throw new Exception('FuncSaveOrder. Ошибка при сохранении наряда.  Модели Order');
            }
        } catch (Throwable $exception) {
            $errors[] = 'FuncSaveOrder. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $result = $save_order;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;

    }

    /**
     * Метод GetRouteMap() - получение данных для маршрутной карты нарядов
     *
     * @return array - массив со следующей стуктурой:
     *                                              [order_id]
     *                                                      order_id:
     *                                                      company_department_id:
     *                                                      date_time_create:
     *                                                      chane_id:
     *                                                      shift_id:
     *                                                      brigade_id:
     *                                                      [created_order]
     *                                                                full_name:
     *                                                                tabel_number:
     *                                                                position_title:
     *                                                                worker_id:
     *                                                                date_time:
     *                                                      [agreed_last]
     *                                                                full_name:
     *                                                                tabel_number:
     *                                                                position_title:
     *                                                                worker_id:
     *                                                                date_time:
     *                                                      [accept_last]
     *                                                                full_name:
     *                                                                tabel_number:
     *                                                                position_title:
     *                                                                worker_id:
     *                                                                date_time:
     *                                                      [corrected]
     *                                                             [order_status_id]
     *                                                                        full_name:
     *                                                                        tabel_number:
     *                                                                        position_title:
     *                                                                        worker_id:
     *                                                                        description:
     *                                                                        date_time:
     *                                                      percent_complete:
     *                                                      count_accept_worker:
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetRouteMap&subscribe=&data={%22company_department_id%22:20028766,%22year%22:2019,%22month%22:11}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 10.07.2019 11:01
     */
    public static function GetRouteMap($data_post = NULL)
    {
//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', '3000M');
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $get_route_map = array();                                                                                       // Промежуточный результирующий массив
        $operations_compleate = array();                                                                                       // Промежуточный результирующий массив
        $result = array();                                                                                              // Промежуточный результирующий массив
        $workers_array = array();                                                                                       // Промежуточный результирующий массив
        $get_route_map_array = array();
//        $count_worker_accept = 0;
//        $count_value_plan = 0;
//        $count_value_fact = 0;
        $start_mem = null;
        $memory_size = array();

        if ($data_post !== NULL && $data_post !== '') {
            try {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                $warnings[] = 'GetRouteMap. Декодировал входные параметры';

                $start_mem = memory_get_usage();
                $memory_size[] = 'start mem ' . (memory_get_usage() - $start_mem) / 1024;
                $memory_size[] = 'start mem  PEAK ' . (memory_get_peak_usage()) / 1024;

                if (
                    property_exists($post_dec, 'company_department_id') &&
                    property_exists($post_dec, 'year') &&
                    property_exists($post_dec, 'month')
                )                                                                                                       // Проверяем наличие в нем нужных нам полей
                {
                    $company_department_id = $post_dec->company_department_id;
                    $year = $post_dec->year;
                    $month = $post_dec->month;

//                    'month(date_issue)<=' . (int)date("m", strtotime($date_time)),
//                    'year(date_issue)<=' . (int)date("Y", strtotime($date_time)),

                    $filter_mine = [];
                    if (property_exists($post_dec, 'mine_id')) {
                        $mine_id = $post_dec->mine_id;
                        if ($mine_id != "-1" and $mine_id != "*") {
                            $filter_mine = ['order.mine_id' => $mine_id];
                        }
                    }

                    // получить список всех компаний департаментов
                    $response = DepartmentController::FindDepartment($company_department_id);
                    if ($response['status'] == 1) {
                        $company_departments = $response['Items'];
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                    } else {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        throw new Exception('GetRouteMap. Ошибка получения вложенных департаментов' . $company_department_id);
                    }
                    unset($response);
                    $memory_size[] = 'FindDepartment mem ' . (memory_get_usage() - $start_mem) / 1024;
                    $memory_size[] = 'FindDepartment mem  PEAK ' . (memory_get_peak_usage()) / 1024;

                    $orders = Order::find()
                        ->select('
                            operation_worker.worker_id,
                            order.id as id,
                            order.company_department_id,
                            order.shift_id as shift_id,
                            order.date_time_create as date_time_create,
                            order_operation.id as order_operation_id,
                            order_operation.operation_value_fact,
                            order_operation.operation_value_plan
                        ')
                        ->innerJoinWith('orderPlaces.orderOperations.operationWorkers')
                        ->where(['in', 'order.company_department_id', $company_departments])
                        ->andWhere($filter_mine)
                        ->andWhere('MONTH(order.date_time_create)=' . $month)
                        ->andWhere('YEAR(order.date_time_create)=' . $year)
                        ->orderBy(['order.date_time_create' => SORT_ASC])
                        ->limit(50000)
                        ->all();
                    $memory_size[] = 'Order orderOperations mem ' . (memory_get_usage() - $start_mem) / 1024;
                    $memory_size[] = 'Order orderOperations mem  PEAK ' . (memory_get_peak_usage()) / 1024;

                    foreach ($orders as $order) {
//                        $warnings[] = 'GetRouteMap. Наряд найден. Заполняю данные о наряде';
                        $order_id = $order->id;
                        $shift_id = $order->shift_id;
                        $order_date_time = date("d.m.Y", strtotime($order->date_time_create));

                        // считаем людей в наряде
                        $workers_array = [];
                        $operations_compleate = [];
                        foreach ($order->orderPlaces as $order_place) {
                            foreach ($order_place->orderOperations as $order_operation) {
                                if (isset($order_operation->operationWorkers) and !empty($order_operation->operationWorkers)) {
                                    if ($order_operation->operation_value_plan) {
                                        $fact = (float)$order_operation->operation_value_fact;
                                        $plan = (float)$order_operation->operation_value_plan;
                                        if ($plan != 0) {
                                            $operations_compleate[$order_operation->id] = ($fact) / $plan;
                                        } else {
                                            $operations_compleate[$order_operation->id] = 0;
                                        }
                                    } else {
                                        $operations_compleate[$order_operation->id] = 1;
                                    }
                                }
                                foreach ($order_operation->operationWorkers as $operation_worker) {
                                    $workers_array[$operation_worker->worker_id] = 1;
                                }
                            }
                        }
                        $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['count_accept_worker'] = count($workers_array);

                        // вычисляем процент выполнения наряда
                        $srednee = 0;
                        $count_operation = 0;
                        foreach ($operations_compleate as $operation_compleate) {
                            $srednee += $operation_compleate;
                            $count_operation++;
                        }
                        if ($count_operation) {
                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['percent_complete'] = round((($srednee / $count_operation) * 100), 1);
                        } else {
                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['percent_complete'] = 0;
                        }

                    }

                    unset($orders);
                    $memory_size[] = 'Order orderOperations end mem ' . (memory_get_usage() - $start_mem) / 1024;
                    $memory_size[] = 'Order orderOperations end mem  PEAK ' . (memory_get_peak_usage()) / 1024;

                    $orders = Order::find()
                        ->innerJoinWith('companyDepartment')
                        ->innerJoinWith('companyDepartment.company')
                        ->innerJoinWith('orderStatuses.status')
                        ->innerJoinWith('orderStatuses.worker.position')
                        ->innerJoinWith('orderStatuses.worker.employee')
                        ->where(['in', 'order.company_department_id', $company_departments])
                        ->andWhere($filter_mine)
                        ->andWhere('MONTH(order.date_time_create)=' . $month)
                        ->andWhere('YEAR(order.date_time_create)=' . $year)
                        ->orderBy(['order.date_time_create' => SORT_ASC, 'order_status.date_time_create' => SORT_ASC])
                        ->limit(50000)
                        ->all();
                    $memory_size[] = 'Order orderStatuses mem ' . (memory_get_usage() - $start_mem) / 1024;
                    $memory_size[] = 'Order orderStatuses mem  PEAK ' . (memory_get_peak_usage()) / 1024;
                    foreach ($orders as $order) {
//                        $warnings[] = 'GetRouteMap. Наряд найден. Заполняю данные о наряде';
                        $order_id = $order->id;
                        $shift_id = $order->shift_id;
                        $order_date_time = date("d.m.Y", strtotime($order->date_time_create));
                        $get_route_map[$order_date_time]['orders'][$order_id]['order_id'] = $order_id;
                        $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['order_id'] = $order_id;
                        $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['shift_id'] = $shift_id;
                        $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['company_department_id'] = $order->company_department_id;
                        $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['company_department_title'] = $order->companyDepartment->company->title;
                        $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['date_time_create'] = $order_date_time;
                        // считаем людей в наряде
                        $workers_array = [];
                        $operations_compleate = [];

                        $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_state'] = array(
                            '2' => false,    // отправлен на согласование +
                            '4' => false,    // согласован АБ +
                            '7' => false,    // скорректирован
                            '50' => true,    // создан +
                            '61' => false,   // отклонен АБ +
                            '10' => false,   // отклонен РВН +
                            '8' => false,    // отчет сдан +
                            '6' => false,    // утвержден РВН +
                        );

                        //получаем историю статусов и последний статус
                        foreach ($order->orderStatuses as $order_status) {
                            $order_status_date_time = date("d.m.Y H:i:s", strtotime($order_status->date_time_create));
                            $status_id = $order_status->status_id;
                            $get_route_map[$order_date_time]['date_time'] = $order_date_time;
                            $get_route_map[$order_date_time]['date_time_source'] = $order->date_time_create;
                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['status_history'][$order_status->id]['date_time'] = $order_status_date_time;
                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['status_history'][$order_status->id]['status_id'] = $order_status->status_id;
                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['status_history'][$order_status->id]['description'] = $order_status->description;
                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['status_history'][$order_status->id]['status_title'] = $order_status->status->title;
                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['status_history'][$order_status->id]['id'] = $order_status->id;
                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['status_history'][$order_status->id]['worker_id'] = $order_status->worker_id;
                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['status_history'][$order_status->id]['tabel_number'] = $order_status->worker->tabel_number;
                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['status_history'][$order_status->id]['full_name'] = $order_status->worker->employee->last_name . " " . $order_status->worker->employee->first_name . " " . $order_status->worker->employee->patronymic;
                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['status_history'][$order_status->id]['position_id'] = $order_status->worker->position_id;
                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['status_history'][$order_status->id]['position_title'] = $order_status->worker->position->title;

                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_status'][$status_id]['date_time'] = $order_status->date_time_create;
                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_status'][$status_id]['status_id'] = $order_status->status_id;
                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_status'][$status_id]['status_title'] = $order_status->status->title;
                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_status'][$status_id]['id'] = $order_status->id;
                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_status'][$status_id]['worker_id'] = $order_status->worker_id;
                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_status'][$status_id]['tabel_number'] = $order_status->worker->tabel_number;
                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_status'][$status_id]['full_name'] = $order_status->worker->employee->last_name . " " . $order_status->worker->employee->first_name . " " . $order_status->worker->employee->patronymic;
                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_status'][$status_id]['position_id'] = $order_status->worker->position_id;
                            $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_status'][$status_id]['position_title'] = $order_status->worker->position->title;

                            if (!isset($get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_status'][self::ORDER_CREATED])) {
                                $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_status'][self::ORDER_CREATED]['date_time'] = $order_status->date_time_create;
                                $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_status'][self::ORDER_CREATED]['status_id'] = $order_status->status_id;
                                $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_status'][self::ORDER_CREATED]['status_title'] = $order_status->status->title;
                                $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_status'][self::ORDER_CREATED]['id'] = $order_status->id;
                                $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_status'][self::ORDER_CREATED]['worker_id'] = $order_status->worker_id;
                                $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_status'][self::ORDER_CREATED]['tabel_number'] = $order_status->worker->tabel_number;
                                $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_status'][self::ORDER_CREATED]['full_name'] = $order_status->worker->employee->last_name . " " . $order_status->worker->employee->first_name . " " . $order_status->worker->employee->patronymic;
                                $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_status'][self::ORDER_CREATED]['position_id'] = $order_status->worker->position_id;
                                $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_status'][self::ORDER_CREATED]['position_title'] = $order_status->worker->position->title;
                            }

                            // наряд согласован АБ
                            if ($status_id == 4) {
                                $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_state']['4'] = true;
                            }

                            // наряд утвержден РВН
                            if ($status_id == 6) {
                                $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_state']['6'] = true;
                            }

                            // отчет сдан
                            if ($status_id == 8) {
                                $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_state']['8'] = true;
                            }

                            // отчет сдан
                            if ($status_id == 8) {
                                $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_state']['8'] = true;
                            }

                            // отклонен РВН
                            if ($status_id == 10) {
                                $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_state']['10'] = true;
                            }

                            // отклонен АБ
                            if ($status_id == 61) {
                                $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_state']['61'] = true;
                            }

                            // создан
                            if ($status_id == 50) {
                                $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_state'] = array(
                                    '2' => false,    // отправлен на согласование
                                    '4' => false,    // согласован АБ +
                                    '7' => false,    // скорректирован
                                    '50' => true,    // создан
                                    '61' => false,   // отклонен АБ +
                                    '10' => false,   // отклонен РВН +
                                    '8' => false,    // отчет сдан +
                                    '6' => false,    // утвержден РВН +
                                );
                            }

                            // отправлен на согласование
                            if ($status_id == 2) {
                                $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_state']['2'] = true;
                            }

                            // скорректирован
                            if ($status_id == 7) {
                                $get_route_map[$order_date_time]['orders'][$order_id]['shifts'][$shift_id]['last_state'] = array(
                                    '2' => false,    // отправлен на согласование
                                    '4' => false,    // согласован АБ +
                                    '7' => true,    // скорректирован
                                    '50' => true,    // создан
                                    '61' => false,   // отклонен АБ +
                                    '10' => false,   // отклонен РВН +
                                    '8' => false,    // отчет сдан +
                                    '6' => false,    // утвержден РВН +
                                );
                            }
                        }
                    }
                    unset($orders);
                    $memory_size[] = 'Order orderStatuses end mem ' . (memory_get_usage() - $start_mem) / 1024;
                    $memory_size[] = 'Order orderStatuses end mem  PEAK ' . (memory_get_peak_usage()) / 1024;
                    $get_route_map_array = [];
                    foreach ($get_route_map as $date_time_item) {
                        $order_array = [];
                        foreach ($date_time_item['orders'] as $order_item) {
                            $shift_array = [];
                            foreach ($order_item['shifts'] as $shift_item) {
                                $shift_array[] = $shift_item;
                            }
                            $shift_i['shifts'] = $shift_array;
                            $shift_i['order_id'] = $order_item['order_id'];
                            $order_array[] = $shift_i;
                        }
                        $order_i['orders'] = $order_array;
                        $order_i['date_time'] = $date_time_item['date_time'];
                        $order_i['date_time_source'] = $date_time_item['date_time_source'];
                        $get_route_map_array[] = $order_i;
                    }
                    $memory_size[] = 'Order  end mem ' . (memory_get_usage() - $start_mem) / 1024;
                    $memory_size[] = 'Order  end mem  PEAK ' . (memory_get_peak_usage()) / 1024;
                } else {
                    $errors[] = 'GetRouteMap. Переданы некорректные входные параметры';
                    $status *= 0;
                }
            } catch (Throwable $exception) {
                $warnings[] = 'GetRouteMap. Исключение';
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
                $status *= 0;
            }
        } else {
            $errors[] = 'GetRouteMap. Данные с фронта не получены';
            $status *= 0;
        }
        $warnings[] = 'GetRouteMap. Достигнут конец метода';
        $warnings[] = $memory_size;
        if (empty($get_route_map)) {
            $get_route_map = (object)array();
        }
        $result['route_map_object'] = $get_route_map;
        $result['route_map_array'] = $get_route_map_array;

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод GetRouteMapByOrder() - получает данные для конкретного наряда, по дате и смене
     * @param null $data_post - JSON массив с данными: идентификатор наряда, дата создания наряда, смена (на которую выдан наряд)
     * @return array - массив со следующей структурой:[order_id]
     *                                                      company_department_id:
     *                                                      brigade_id:
     *                                                      chane_id:
     *                                                      date:
     *                                                      shift_id:
     *                                                      [created_order]
     *                                                                full_name:
     *                                                                tabel_number:
     *                                                                position_title:
     *                                                                worker_id:
     *                                                                date_time:
     *                                                       [agreed_all]
     *                                                              [worker_id]
     *                                                                      full_name:
     *                                                                      tabel_number:
     *                                                                      position_title:
     *                                                                      worker_id:
     *                                                                      date_time:
     *                                                       [accept_all]
     *                                                              [worker_id]
     *                                                                      full_name:
     *                                                                      tabel_number:
     *                                                                      position_title:
     *                                                                      worker_id:
     *                                                                      date_time:
     *                                                      [corrected]
     *                                                             [order_status_id]
     *                                                                        full_name:
     *                                                                        tabel_number:
     *                                                                        position_title:
     *                                                                        worker_id:
     *                                                                        description:
     *                                                                        date_time:
     *                                                       [worker_list_accept]
     *                                                              [worker_id]
     *                                                                      worker_id:
     *                                                                      full_name:
     *                                                                      tabel_number:
     *                                                                      position_title:
     *                                                                      date_time:
     *
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetRouteMapByOrder&subscribe=&data={%22order_id%22:59,%22shift_id%22:%221%22,%22date%22:%222019-06-15%22}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 19.07.2019 16:16
     */
    public static function GetRouteMapByOrder($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $get_route_map_by_order = array();                                                                                         // Промежуточный результирующий массив
        if ($data_post !== NULL && $data_post !== '') {
            $warnings[] = 'GetRouteMapByOrder. Данные успешно переданы';
            $warnings[] = 'GetRouteMapByOrder. Входной массив данных' . $data_post;
            try {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                $warnings[] = 'GetRouteMapByOrder. Декодировал входные параметры';

                if (
                    property_exists($post_dec, 'order_id') &&
                    property_exists($post_dec, 'date') &&
                    property_exists($post_dec, 'shift_id')
                )                                                                                                       // Проверяем наличие в нем нужных нам полей
                {
                    $warnings[] = 'GetRouteMapByOrder.Данные с фронта получены';
                    $date_order = date('Y-m-d', strtotime($post_dec->date));
                    $shift_id = $post_dec->shift_id;
                    $order_id = $post_dec->order_id;
                    $order = Order::find()
                        ->joinWith('orderPlaces')
                        ->joinWith('orderStatuses.worker.position')
                        ->joinWith('orderStatuses.worker.employee')
                        ->where(['order.id' => $order_id,
                            'order.date_time_create' => $date_order,
                            'order.shift_id' => $shift_id])
                        ->limit(1)
                        ->one();
                    $get_route_map_by_order[$order->id]['company_department_id'] = $order->company_department_id;
                    $get_route_map_by_order[$order->id]['brigade_id'] = $order->brigade_id;
                    $get_route_map_by_order[$order->id]['chane_id'] = $order->chane_id;
                    $get_route_map_by_order[$order->id]['date'] = $order->date_time_create;
                    $get_route_map_by_order[$order->id]['shift_id'] = $order->shift_id;
                    foreach ($order->orderStatuses as $orderStatus) {
                        /******************** Если наряд создан, получаем данные о том кто создал ********************/
                        if ($orderStatus->status_id == self::ORDER_CREATED) {
                            $get_route_map_by_order[$order->id]['created_order']['full_name'] = "{$orderStatus->worker->employee->last_name} {$orderStatus->worker->employee->first_name} {$orderStatus->worker->employee->patronymic}";
                            $get_route_map_by_order[$order->id]['created_order']['tabel_number'] = $orderStatus->worker->tabel_number;
                            $get_route_map_by_order[$order->id]['created_order']['position_title'] = $orderStatus->worker->position->title;
                            $get_route_map_by_order[$order->id]['created_order']['worker_id'] = $orderStatus->worker_id;
                            $get_route_map_by_order[$order->id]['created_order']['date_time'] = $orderStatus->date_time_create;
                        }

                        /******************** Все кто согласовывал ********************/
                        if ($orderStatus->status_id == self::ORDER_AGREED) {
                            $get_route_map_by_order[$order->id]['agreed_all'][$orderStatus->worker_id]['full_name'] = "{$orderStatus->worker->employee->last_name} {$orderStatus->worker->employee->first_name} {$orderStatus->worker->employee->patronymic}";
                            $get_route_map_by_order[$order->id]['agreed_all'][$orderStatus->worker_id]['tabel_number'] = $orderStatus->worker->tabel_number;
                            $get_route_map_by_order[$order->id]['agreed_all'][$orderStatus->worker_id]['position_title'] = $orderStatus->worker->position->title;
                            $get_route_map_by_order[$order->id]['agreed_all'][$orderStatus->worker_id]['worker_id'] = $orderStatus->worker_id;
                            $get_route_map_by_order[$order->id]['agreed_all'][$orderStatus->worker_id]['date_time'] = $orderStatus->date_time_create;
                        }

                        /******************** Все кто утверждал ********************/
                        if ($orderStatus->status_id == self::ORDER_APPROVAL) {
                            $get_route_map_by_order[$order->id]['accept_all'][$orderStatus->worker_id]['full_name'] = "{$orderStatus->worker->employee->last_name} {$orderStatus->worker->employee->first_name} {$orderStatus->worker->employee->patronymic}";
                            $get_route_map_by_order[$order->id]['accept_all'][$orderStatus->worker_id]['tabel_number'] = $orderStatus->worker->tabel_number;
                            $get_route_map_by_order[$order->id]['accept_all'][$orderStatus->worker_id]['position_title'] = $orderStatus->worker->position->title;
                            $get_route_map_by_order[$order->id]['accept_all'][$orderStatus->worker_id]['worker_id'] = $orderStatus->worker_id;
                            $get_route_map_by_order[$order->id]['accept_all'][$orderStatus->worker_id]['date_time'] = $orderStatus->date_time_create;
                        }

                        /******************** Все кто вносил корректировки ********************/
                        if ($orderStatus->status_id == self::ORDER_CORRECTED) {
                            $get_route_map_by_order[$order->id]['corrected'][$orderStatus->id]['full_name'] = "{$orderStatus->worker->employee->last_name} {$orderStatus->worker->employee->first_name} {$orderStatus->worker->employee->patronymic}";
                            $get_route_map_by_order[$order->id]['corrected'][$orderStatus->id]['tabel_number'] = $orderStatus->worker->tabel_number;
                            $get_route_map_by_order[$order->id]['corrected'][$orderStatus->id]['position_title'] = $orderStatus->worker->position->title;
                            $get_route_map_by_order[$order->id]['corrected'][$orderStatus->id]['worker_id'] = $orderStatus->worker_id;
                            $get_route_map_by_order[$order->id]['corrected'][$orderStatus->id]['description'] = $orderStatus->description;
                            $get_route_map_by_order[$order->id]['corrected'][$orderStatus->id]['date_time'] = $orderStatus->date_time_create;
                        }

                    }
                    foreach ($order->orderPlaces as $order_place) {
                        $found_order_op_wo = OrderOperationWorker::find()
                            ->joinWith('orderOperationWorkerStatuses')
                            ->joinWith('worker')
                            ->joinWith('worker.position')
                            ->joinWith('worker.employee')
                            ->where(['order_place_id' => $order_place->id])
                            ->limit(300)
                            ->all();                                                                                    //ищем всех людей в наряде на это место
                        if ($found_order_op_wo !== null) {
                            foreach ($found_order_op_wo as $order_operation_worker)                                     //перебор найденных даннх
                            {
                                foreach ($order_operation_worker->orderOperationWorkerStatuses as $order_op_wo_status) {
                                    /******************** Записываем всех людей, у кого стоит статус "Принял" ********************/
                                    if ($order_op_wo_status->status_id == self::WORKER_ACCEPT_ORDER) {
                                        $get_route_map_by_order[$order->id]['worker_list_accept'][$order_operation_worker->worker->id]['worker_id'] = $order_operation_worker->worker->id;
                                        $get_route_map_by_order[$order->id]['worker_list_accept'][$order_operation_worker->worker->id]['full_name'] = "{$order_operation_worker->worker->employee->last_name} {$order_operation_worker->worker->employee->first_name} {$order_operation_worker->worker->employee->patronymic}";
                                        $get_route_map_by_order[$order->id]['worker_list_accept'][$order_operation_worker->worker->id]['tabel_number'] = $order_operation_worker->worker->tabel_number;
                                        $get_route_map_by_order[$order->id]['worker_list_accept'][$order_operation_worker->worker->id]['position_title'] = $order_operation_worker->worker->position->title;
                                        $get_route_map_by_order[$order->id]['worker_list_accept'][$order_operation_worker->worker->id]['date_time'] = $order_op_wo_status->date_time;
                                    }
                                }
                            }
                        }
                    }

                } else {
                    $errors[] = 'GetRouteMapByOrder. Переданы некорректные входные параметры';
                    $status *= 0;
                }
            } catch (Throwable $exception) {
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
                $status *= 0;
            }
        } else {
            $errors[] = 'GetRouteMapByOrder. Данные с фронта не получены';
            $status *= 0;
        }
        $result = $get_route_map_by_order;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод ChangeStatusWorkerInOrder() - смена статуса работника на "Принял"
     * @param null $data_post - JSON массив с данными: идентификатор операции в наряде на работника
     * @return array - стандартный массив выходных данных
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=ChangeStatusWorkerInOrder&subscribe=&data={%22order_operation_worker_id%22:100}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 19.07.2019 17:07
     */
    public static function ChangeStatusWorkerInOrder($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $status_worker_in_order = array();                                                                                         // Промежуточный результирующий массив
        if ($data_post !== NULL && $data_post !== '') {
            $warnings[] = 'ChangeStatusWorkerInOrder. Данные успешно переданы';
            $warnings[] = 'ChangeStatusWorkerInOrder. Входной массив данных' . $data_post;
            try {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                $warnings[] = 'ChangeStatusWorkerInOrder. Декодировал входные параметры';

                if (
                    property_exists($post_dec, 'order_id') &&
                    property_exists($post_dec, 'worker_id')
                )                                                                                                       // Проверяем наличие в нем нужных нам полей
                {
                    $warnings[] = 'ChangeStatusWorkerInOrder.Данные с фронта получены';
                    $order_id = $post_dec->order_id;
                    $worker_id = $post_dec->worker_id;
                    $found_data_order_operation = Order::find()
                        ->select('order_operation_worker_status.id as order_operation_worker_status_id')
                        ->innerJoin('order_place', 'order.id = order_place.order_id')
                        ->innerJoin('order_operation_worker', 'order_place.id = order_operation_worker.order_place_id')
                        ->innerJoin('order_operation_worker_status', 'order_operation_worker.id = order_operation_worker_status.order_operation_worker_id')
                        ->andWhere(['order.id' => $order_id])
                        ->andWhere(['order_operation_worker.worker_id' => $worker_id])
                        ->asArray()
                        ->all();
                    foreach ($found_data_order_operation as $order_operation_item) {
                        $found_status = OrderOperationWorkerStatus::find()
                            ->where(['id' => $order_operation_item['order_operation_worker_status_id']])
                            ->limit(1)
                            ->one();
                        if ($found_status !== null) {
                            $found_status->status_id = self::WORKER_ACCEPT_ORDER;
                            if ($found_status->save()) {
                                $found_order_op_wo = OrderOperationWorker::findOne(['worker_id' => $worker_id]);
                                $found_order_op_wo->status_id = self::WORKER_ACCEPT_ORDER;
                                if ($found_order_op_wo->save()) {
                                    $warnings[] = 'ChangeStatusWorkerInOrder. Статус работника успешно установлен';
                                }
                            } else {
                                $errors[] = $found_status->errors;
                                throw new Exception('ChangeStatusWorkerInOrder. Ошибка при изменении статуса работника');
                            }
                        }
                    }
                } else {
                    $errors[] = 'ChangeStatusWorkerInOrder. Переданы некорректные входные параметры';
                    $status *= 0;
                }
            } catch (Throwable $exception) {
                $errors[] = 'ChangeStatusWorkerInOrder. Исключение';
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
                $status *= 0;
            }
        } else {
            $errors[] = 'ChangeStatusWorkerInOrder. Данные с фронта не получены';
            $status *= 0;
        }
        $result = $status_worker_in_order;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод SaveOrderVtbAb() - Сохранение наряда ВТБ АБ
     * @param null $data_post - JSON с со структрой: company_department_id:                                             - идентификатор участка
     *                                               order_vtb_ab_id:                                                   - идентификатор наряда АБ ВТБ (по умолчанию: -1)
     *                                               [order_places]                                                     - наряды на места
     *                                                       [place_id]                                                 - идентификатор места
     *                                                             place_id:                                            - идентификатор места
     *                                                             [order_operations]                                   - операции в наряде на это место
     *                                                                      [operation_id]                              - идентификатор операции
     *                                                                              operation_id:                       - идентификатор операции
     *                                                                              operation_value_plan:               - плановое значение операции
     * @return array - стандартный массив
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=SaveOrderVtbAb&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 24.10.2019 14:43
     */
    public static function SaveOrderVtbAb($data_post = NULL)
    {
        $result = (object)array();
        $status = 1; // Флаг успешного выполнения метода
        $warnings = array(); // Массив предупреждений
        $errors = array(); // Массив ошибок
//        $saveing_order_ab_vtb = array();    // Промежуточный результирующий массив
        $inserted_order_operation_status = array();
        $date_time_now = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));
// $data_post = '{"orders_vtb_ab":{"0":{"company_department_id":501,"order_vtb_ab_id":-1,"order_places":{"6181":{"place_id":6181,"order_operations":{"20":{"operation_id":20,"operation_value_plan":500},"24":{"operation_id":24,"operation_value_plan":9000}}}}}}}';
        $warnings[] = 'SaveOrderVtbAb. Начало метода';
        try {
            $trancation = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveOrderVtbAb. Не переданы входные параметры');
            }
            $warnings[] = 'SaveOrderVtbAb. Данные успешно переданы';
            $warnings[] = 'SaveOrderVtbAb. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post); // Декодируем входной массив данных
            $warnings[] = 'SaveOrderVtbAb. Декодировал входные параметры';
            if (!property_exists($post_dec, 'orders_vtb_ab') ||
                !property_exists($post_dec, 'mine_id') ||
                !property_exists($post_dec, 'date') ||
                !property_exists($post_dec, 'shift_id')) // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('SaveOrderVtbAb. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveOrderVtbAb. Данные с фронта получены';
            $orders_vtb_ab = $post_dec->orders_vtb_ab;
            $mine_id = $post_dec->mine_id;
            $date = date('Y-m-d', strtotime($post_dec->date));
            $shift_id = $post_dec->shift_id;
            foreach ($orders_vtb_ab as $order_item) {
                $company_department_id = $order_item->company_department_id;
                $order_vtb_ab_id = $order_item->order_vtb_ab_id;
                $order_vtb_ab_f_n = OrderVtbAb::find()
                    ->where(['id' => $order_vtb_ab_id])
                    ->orWhere(['company_department_id' => $company_department_id, 'date_time_create' => $date, 'shift_id' => $shift_id, 'mine_id' => $mine_id])
                    ->one();
                if (!$order_vtb_ab_f_n) {
                    $order_vtb_ab_f_n = new OrderVtbAb();
                    $warnings[] = 'SaveOrderVtbAb. Наряд ВТБ АБ новый';
                } else {
                    $warnings[] = 'SaveOrderVtbAb. Наряд ВТБ АБ был';
                }
                $order_vtb_ab_f_n->company_department_id = $company_department_id;
                $order_vtb_ab_f_n->date_time_create = $date;
                $order_vtb_ab_f_n->shift_id = $shift_id;
                $order_vtb_ab_f_n->mine_id = $mine_id;
                if ($order_vtb_ab_f_n->save()) {
                    $order_vtb_ab_f_n->refresh();
                    $order_vtb_ab_id = $order_vtb_ab_f_n->id;
                    $warnings[] = 'SaveOrderVtbAb. Наряд на произвдоство работ линии АБ ВТБ успешно сохранён Новый айди ' . $order_vtb_ab_id;
                } else {
                    $errors[] = $order_vtb_ab_f_n->errors;
                    throw new Exception('SaveOrderVtbAb. Ошибка при сохранении наряда линии АБ ВТБ');
                }
                if (!empty($order_item->order_places)) {
                    $delete_order_places = OrderPlaceVtbAb::deleteAll(['order_vtb_ab_id' => $order_vtb_ab_id]);
                    foreach ($order_item->order_places as $order_place) {
                        $add_order_place = new OrderPlaceVtbAb();
                        if (!is_string($order_place->order_place_vtb_ab_id) or is_integer((int)$order_place->order_place_vtb_ab_id)) {
                            $add_order_place->id = $order_place->order_place_vtb_ab_id;
                        }
                        $add_order_place->order_vtb_ab_id = $order_vtb_ab_id;
                        $add_order_place->place_id = $order_place->place_id;
                        if (property_exists($order_place, "description")) {
                            $add_order_place->description = $order_place->description;
                        }

                        if ($add_order_place->save()) {
                            $warnings[] = 'SaveOrderVtbAb. Наряд на место АБ ВТБ успешно сохранён';
                            $add_order_place->refresh();
                            $order_place_id = $add_order_place->id;
                        } else {
                            $errors[] = $add_order_place->errors;
                            throw new Exception('SaveOrderVtbAb. Ошбика сохранения наряда на место АБ ВТБ');
                        }
                        if (isset($order_place->order_operations)) {

                            if (!empty($order_place->order_operations)) {
                                foreach ($order_place->order_operations as $order_operation) {
                                    $add_order_operation = new OrderOperationPlaceVtbAb();
                                    if (!is_string($order_operation->order_operation_place_vtb_ab_id) or is_integer((int)$order_operation->order_operation_place_vtb_ab_id)) {
                                        $add_order_operation->id = $order_operation->order_operation_place_vtb_ab_id;
                                    }
                                    $add_order_operation->order_place_vtb_ab_id = $order_place_id;
                                    $add_order_operation->operation_id = $order_operation->operation_id;
                                    if ($order_operation->operation_value_plan) {
                                        $add_order_operation->operation_value_plan = (string)((float)str_replace(",", ".", $order_operation->operation_value_plan));
                                    } else {
                                        $add_order_operation->operation_value_plan = null;
                                    }
                                    $add_order_operation->status_id = self::OPERATION_CREATED;
                                    if ($add_order_operation->save()) {
                                        $warnings[] = 'SaveOrderVtbAb. Операция в наряде на место АБ ВТБ успешно сохранена';
                                        $add_order_operation->refresh();
                                        $order_operation_id = $add_order_operation->id;
                                    } else {
                                        $errors[] = $add_order_operation->errors;
                                        throw new Exception('SaveOrderVtbAb. Ошибка при сохранении операции в наряде на место АБ ВТБ');
                                    }
                                    $inserted_order_operation_status[] = [
                                        $order_operation_id,
                                        self::OPERATION_CREATED,
                                        $date_time_now
                                    ];

                                }
                            }
                        }
                    }

                }
            }
            $warnings[] = 'SaveOrderVtbAb. Вставка статусов массовая';
            $warnings[] = $inserted_order_operation_status;
            if (!empty($inserted_order_operation_status)) {
                $result_inserted_order_operation_status = Yii::$app->db
                    ->createCommand()
                    ->batchInsert('order_operation_place_status_vtb_ab',
                        [
                            'order_operation_place_vtb_ab_id',
                            'status_id',
                            'date_time'],
                        $inserted_order_operation_status)
                    ->execute();
                if ($result_inserted_order_operation_status != 0) {
                    $warnings[] = 'SaveOrderVtbAb. Статусы операций наряда АБ ВТБ успешно сохранены';
                } else {
                    throw new Exception('SaveOrderVtbAb. Ошибка при сохранении статусов операций наряда АБ ВТБ');
                }
            }
            $trancation->commit();
            $json_order_vtb_ab = json_encode(array('date' => $date, 'shift_id' => $shift_id, 'mine_id' => $mine_id));
            $get_orders_vtb_ab = self::GetOrdersVtbAb($json_order_vtb_ab);
            if ($get_orders_vtb_ab['status'] == 1) {
                $result = $get_orders_vtb_ab['Items'];
                $warnings[] = $get_orders_vtb_ab['warnings'];
            } else {
                $errors[] = $get_orders_vtb_ab['errors'];
                $warnings[] = $get_orders_vtb_ab['warnings'];
                $result = (object)array();
            }
        } catch (Throwable $exception) {
            $trancation->rollBack();
            $errors[] = 'SaveOrderVtbAb. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SaveOrderVtbAb. Конец метода';
//        $date_now = date('Y-m-d', strtotime($date));


        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetOrdersVtbAb() - Метод получения нарядов АБ ВТБ по дате по всей шахте под которой (с учётки залогиненного пользователя)
     * @param null $data_post - JSON с датой
     * @return array - массив со следующей структурой: [company_department_id]
     *                                                          company_department_id:
     *                                                          company_title:
     *                                                          date:
     *                                                          [orders_vtb_ab]
     *                                                                  [order_vtb_ab_id]
     *                                                                          order_vtb_ab_id:
     *                                                                          [order_places]
     *                                                                                  [order_place_vtb_ab_id]
     *                                                                                              order_place_vtb_ab_id:
     *                                                                                              order_place_vtb_ab_reason:
     *                                                                                              place_id:
     *                                                                                              place_title:
     *                                                                                              [order_operations]
     *                                                                                                          [order_operation_place_vtb_ab_id]
     *                                                                                                                      order_operation_place_vtb_ab_id:
     *                                                                                                                      operation_id:
     *                                                                                                                      operation_title:
     *                                                                                                                      unit_title:
     *                                                                                                                      operation_value_fact:
     *                                                                                                                      operation_value_plan:
     *                                                                                                                      status_id:
     *                                                                                                                      [statuses]
     *                                                                                                                             [order_operation_status_vtb_ab_id]
     *                                                                                                                                          order_operation_status_vtb_ab_id:
     *                                                                                                                                          order_operation_place_vtb_ab_id:
     *                                                                                                                                          status_id:
     *                                                                                                                                          date_time:
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetOrdersVtbAb&subscribe=&data={%22date%22:%222019-10-24%22}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 24.10.2019 14:37
     */
    public static function GetOrdersVtbAb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $orders_vtb_ab = array();                                                                                // Промежуточный результирующий массив
        $order_vtb_ab = array();
        $session = Yii::$app->session;
        $warnings[] = 'GetOrdersVtbAb. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetOrdersVtbAb. Не переданы входные параметры');
            }
            $warnings[] = 'GetOrdersVtbAb. Данные успешно переданы';
            $warnings[] = 'GetOrdersVtbAb. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetOrdersVtbAb. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'date') ||
                !property_exists($post_dec, 'mine_id') ||
                !property_exists($post_dec, 'shift_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetOrdersVtbAb. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetOrdersVtbAb. Данные с фронта получены';
            $date = date('Y-m-d', strtotime($post_dec->date));
            $shift_id = $post_dec->shift_id;
            $mine_id = $post_dec->mine_id;
            $get_comany_department_id = Mine::find()
                ->select('company_id')
                ->where(['id' => $session['userMineId']])
                ->limit(1)
                ->scalar();
            $response = DepartmentController::FindDepartment($get_comany_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            }

            if (isset($company_departments)) {
                $orders_vtb_ab = OrderVtbAb::find()
                    ->joinWith('companyDepartment.company')
                    ->joinWith('orderPlaceVtbAbs.place')
                    ->joinWith('shift')
                    ->joinWith('orderPlaceVtbAbs.orderPlaceVtbAbReasons')
                    ->joinWith('orderPlaceVtbAbs.orderOperationPlaceVtbAbs.operation.unit')
                    ->joinWith('orderPlaceVtbAbs.orderOperationPlaceVtbAbs.orderOperationPlaceStatusVtbAbs')
                    ->where(['>=', 'order_vtb_ab.date_time_create', $date . ' 00:00:00'])
                    ->andWhere(['<=', 'order_vtb_ab.date_time_create', $date . ' 23:59:59'])
//                    ->andWhere(['in', 'order_vtb_ab.company_department_id', $company_departments])
                    ->andWhere(['order_vtb_ab.shift_id' => $shift_id])
                    ->andWhere(['order_vtb_ab.mine_id' => $mine_id])
                    ->asArray()
                    ->all();
                if (!empty($orders_vtb_ab)) {
                    foreach ($orders_vtb_ab as $order_item) {
                        $comp_dep_id = $order_item['company_department_id'];
                        $order_vtb_ab_id = $order_item['id'];
                        $company_title = $order_item['companyDepartment']['company']['title'];
                        $shift_id = $order_item['shift_id'];
                        $order_vtb_ab[$comp_dep_id]['company_department_id'] = $comp_dep_id;
                        $order_vtb_ab[$comp_dep_id]['company_title'] = $company_title;
                        $order_vtb_ab[$comp_dep_id]['date_time_create'] = $order_item['date_time_create'];
                        $order_vtb_ab[$comp_dep_id]['date_time_create_format'] = date('d.m.Y H:i:s', strtotime($order_item['date_time_create']));
                        $order_vtb_ab[$comp_dep_id]['order_vtb_ab_id'] = $order_vtb_ab_id;
                        $order_vtb_ab[$comp_dep_id]['shift_id'] = $order_item['shift_id'];
                        $order_vtb_ab[$comp_dep_id]['shift_title'] = $order_item['shift']['title'];
                        /******************** Наряд на место ВТБ АБ ********************/
                        foreach ($order_item['orderPlaceVtbAbs'] as $order_place) {
                            $order_vtb_ab[$comp_dep_id]['order_places'][$order_place['id']]['order_place_vtb_ab_id'] = $order_place['id'];
                            if (isset($order_place['orderPlaceVtbAbReasons']['reason'])) {
                                $order_vtb_ab[$comp_dep_id]['order_places'][$order_place['id']]['order_place_vtb_ab_reason'] = $order_place['orderPlaceVtbAbReasons']['reason'];
                            } else {
                                $order_vtb_ab[$comp_dep_id]['order_places'][$order_place['id']]['order_place_vtb_ab_reason'] = '';
                            }
                            $order_vtb_ab[$comp_dep_id]['order_places'][$order_place['id']]['place_id'] = $order_place['place_id'];
                            $order_vtb_ab[$comp_dep_id]['order_places'][$order_place['id']]['place_title'] = $order_place['place']['title'];
                            $order_vtb_ab[$comp_dep_id]['order_places'][$order_place['id']]['mine_id'] = $order_place['place']['mine_id'];
                            $order_vtb_ab[$comp_dep_id]['order_places'][$order_place['id']]['description'] = $order_place['description'];
                            /******************** Операции в наряде на место ВТБ АБ ********************/
                            foreach ($order_place['orderOperationPlaceVtbAbs'] as $order_operation_place) {
                                $order_vtb_ab[$comp_dep_id]['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['order_operation_place_vtb_ab_id'] = $order_operation_place['id'];
                                $order_vtb_ab[$comp_dep_id]['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['operation_id'] = $order_operation_place['operation_id'];
                                $order_vtb_ab[$comp_dep_id]['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['operation_title'] = $order_operation_place['operation']['title'];
                                $order_vtb_ab[$comp_dep_id]['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['unit_title'] = $order_operation_place['operation']['unit']['short'];
                                $order_vtb_ab[$comp_dep_id]['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['operation_value_fact'] = $order_operation_place['operation_value_fact'];
                                $order_vtb_ab[$comp_dep_id]['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['operation_value_plan'] = $order_operation_place['operation_value_plan'];
                                $order_vtb_ab[$comp_dep_id]['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['status_id'] = $order_operation_place['status_id'];
                                /******************** Статусы операций в наряде на место ВТБ АБ ********************/
                                foreach ($order_operation_place['orderOperationPlaceStatusVtbAbs'] as $order_op_place_status) {
                                    $order_vtb_ab[$comp_dep_id]['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['statuses'][$order_op_place_status['id']]['order_operation_status_vtb_ab_id'] = $order_op_place_status['id'];
                                    $order_vtb_ab[$comp_dep_id]['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['statuses'][$order_op_place_status['id']]['order_operation_place_vtb_ab_id'] = $order_op_place_status['order_operation_place_vtb_ab_id'];
                                    $order_vtb_ab[$comp_dep_id]['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['statuses'][$order_op_place_status['id']]['status_id'] = $order_op_place_status['status_id'];
                                    $order_vtb_ab[$comp_dep_id]['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['statuses'][$order_op_place_status['id']]['date_time'] = $order_op_place_status['date_time'];
                                }
                            }
                            if (empty($order_vtb_ab[$comp_dep_id]['order_places'][$order_place['id']]['order_operations'])) {
                                $order_vtb_ab[$comp_dep_id]['order_places'][$order_place['id']]['order_operations'] = (object)array();
                            }
                        }
                        if (empty($order_vtb_ab[$comp_dep_id]['order_places'])) {
                            $order_vtb_ab[$comp_dep_id]['order_places'] = (object)array();
                        }
                    }
                } else {

                    $json_empty_date = json_encode(array('date' => $date, 'shift_id' => $shift_id, 'mine_id' => $mine_id));
                    $response_empty_obj = self::GetEmptyOrders($json_empty_date);
                    if ($response_empty_obj['status'] == 1) {
                        $order_vtb_ab = $response_empty_obj['Items'];
                        $warnings[] = $response_empty_obj['warnings'];
                    } else {
                        $warnings[] = $response_empty_obj['warnings'];
                        $errors[] = $response_empty_obj['errors'];
                    }
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetOrdersVtbAb. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetOrdersVtbAb. Конец метода';
        if (empty($order_vtb_ab)) {
            $order_vtb_ab = (object)array();
        }
        $result = $order_vtb_ab;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetEmptyOrders() - По дате получает все наряды за предидущий день и формирует пустышку
     * @param null $data_post - JSON с датой на которую выдаём наряда
     * @return array - массив со структурой: [company_department_id]
     *                                                  company_department_id:
     *                                                  company_title:
     *                                                  date_time_create:
     *                                                  [orders_vtb_ab]
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetEmptyOrders&subscribe=&data={%22date%22:%222019-10-26%22}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.10.2019 16:22
     */
    public static function GetEmptyOrders($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $empty_orders = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'GetEmptyOrders. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetEmptyOrders. Не переданы входные параметры');
            }
            $warnings[] = 'GetEmptyOrders. Данные успешно переданы';
            $warnings[] = 'GetEmptyOrders. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetEmptyOrders. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'date') ||
                !property_exists($post_dec, 'mine_id') ||
                !property_exists($post_dec, 'shift_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetEmptyOrders. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetEmptyOrders. Данные с фронта получены';
            $date = date('Y-m-d', strtotime($post_dec->date . "- 1 day"));
            $shift_id = $post_dec->shift_id;
            $mine_id = $post_dec->mine_id;
            $orders_vtb_ab = OrderVtbAb::find()
                ->joinWith('companyDepartment.company')
                ->joinWith('shift')
                ->where(['>=', 'order_vtb_ab.date_time_create', $date . ' 00:00:00'])
                ->andWhere(['<=', 'order_vtb_ab.date_time_create', $date . ' 23:59:59'])
                ->andWhere(['order_vtb_ab.shift_id' => $shift_id])
                ->andWhere(['order_vtb_ab.mine_id' => $mine_id])
                ->all();
            if (isset($orders_vtb_ab)) {
                foreach ($orders_vtb_ab as $order_item) {
                    $comp_dep_id = $order_item->company_department_id;
                    $shift_id = $order_item->shift_id;
                    $mine_id = $order_item->mine_id;
                    $company_title = $order_item->companyDepartment->company->title;
                    $order_vtb_ab_id = $order_item->id;
                    $empty_orders[$comp_dep_id]['company_department_id'] = $comp_dep_id;
                    $empty_orders[$comp_dep_id]['company_title'] = $company_title;
                    $empty_orders[$comp_dep_id]['date_time_create'] = $order_item['date_time_create'];
                    $empty_orders[$comp_dep_id]['mine_id'] = $mine_id;
                    $empty_orders[$comp_dep_id]['shift_id'] = $shift_id;
                    $empty_orders[$comp_dep_id]['shift_title'] = $order_item->shift->title;
                    $empty_orders[$comp_dep_id]['order_vtb_ab_id'] = null;
                    $empty_orders[$comp_dep_id]['order_places'][1]['order_place_vtb_ab_id'] = -1;
                    $empty_orders[$comp_dep_id]['order_places'][1]['order_place_vtb_ab_reason'] = '';
                    $empty_orders[$comp_dep_id]['order_places'][1]['place_id'] = null;
                    $empty_orders[$comp_dep_id]['order_places'][1]['place_title'] = '';
                    $empty_orders[$comp_dep_id]['order_places'][1]['order_operations'][1]['order_operation_place_vtb_ab_id'] = null;
                    $empty_orders[$comp_dep_id]['order_places'][1]['order_operations'][1]['operation_id'] = null;
                    $empty_orders[$comp_dep_id]['order_places'][1]['order_operations'][1]['operation_title'] = '';
                    $empty_orders[$comp_dep_id]['order_places'][1]['order_operations'][1]['unit_title'] = '';
                    $empty_orders[$comp_dep_id]['order_places'][1]['order_operations'][1]['operation_value_fact'] = '';
                    $empty_orders[$comp_dep_id]['order_places'][1]['order_operations'][1]['operation_value_plan'] = '';
                    $empty_orders[$comp_dep_id]['order_places'][1]['order_operations'][1]['status_id'] = null;
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetEmptyOrders. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetEmptyOrders. Конец метода';
        $result = $empty_orders;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод CorrectOrderVtbAb() - корректировка наряда ВТБ АБ
     * @param null $data_post - JSON массив со структурой:
     *                                                     order_vtb_ab_id:
     *                                                     company_department_id:
     *                                                     [order_places]
     *                                                              [place_id]
     *                                                                      place_id:
     *                                                                      [order_operations]
     *                                                                                  [operation_id]
     *                                                                                          operation_id:
     *                                                                                          operation_value_plan:
     * @return array - стандартный массив выходных данных
     *
     *
     * @package frontend\controllers\ordersystem
     *
     * @example amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=CorrectOrderVtbAb&subscribe=&data={}
     *
     *
     * Для тестирования использовал: {"correct_order":{"order_vtb_ab_id":21,"correct_operation":{"45":{"order_operation_place_vtb_ab_id":45,"operation_value_plan":200},"47":{"order_operation_place_vtb_ab_id":47,"operation_value_plan":340}},"new_operation_place":{"26":{"flag":false,"order_place_vtb_ab_id":26,"operations":{"18":{"operation_id":18,"operation_value_plan":6000}}},"49":{"flag":true,"order_place_vtb_ab_id":6211,"operations":{"22":{"operation_id":22,"operation_value_plan":1}}}},"deleted_order_place_vtb_ab":{"26":{"order_place_vtb_ab_id":26,"operations":null},"25":{"order_place_vtb_ab_id":25,"operations":{"44":{"order_operation_place_vtb_ab_id":44}}}}}}
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.07.2019 8:31
     */
    public static function CorrectOrderVtbAb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
//        $correct_order_arr = array();                                                                                // Промежуточный результирующий массив
        $result = array();
//        $date_time_now = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));
//        $data_post = '{"mine_id":1,"order_vtb_ab_id":48,"company_department_id":501,"order_places":{"6225":{"place_id":6225,"order_operations":{"45":{"operation_id":45,"operation_value_plan":700},"52":{"operation_id":52,"operation_value_plan":20},"37":{"operation_id":37,"operation_value_plan":10}}}}}';
        $warnings[] = 'CorrectOrderVtbAb. Начало метода';
        try {
            $transaction = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('CorrectOrderVtbAb. Не переданы входные параметры');
            }
            $transaction = Yii::$app->db->beginTransaction();
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'CorrectOrderVtbAb. Декодировал входные параметры';

            if (
                !property_exists($post_dec, 'order_vtb_ab_id') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'shift_id') ||
                !property_exists($post_dec, 'mine_id') ||
                !property_exists($post_dec, 'order_places')
            ) {
                throw new Exception('CorrectOrderVtbAb. Переданы некорректные входные параметры');
            }
            $warnings[] = 'CorrectOrderVtbAb.Данные с фронта получены';
            $warnings[] = 'CorrectOrderVtbAb. Начало метода корректировки наряда АБ ВТБ';
            $order_vtb_ab_id = $post_dec->order_vtb_ab_id;
            $date_time_create = $post_dec->date_time_create;
            $company_department_id = $post_dec->company_department_id;
            $mine_id = $post_dec->mine_id;
            $shift_id = $post_dec->shift_id;
            $order_places = $post_dec->order_places;
//            $correct_order_arr = (array)$post_dec;

            $found_order_vtb_ab = OrderVtbAb::findOne(['id' => $order_vtb_ab_id]);
            if (isset($found_order_vtb_ab)) {
                $found_order_vtb_ab->company_department_id = $company_department_id;
                $found_order_vtb_ab->shift_id = $shift_id;
                $found_order_vtb_ab->mine_id = $mine_id;
                if ($found_order_vtb_ab->save()) {
                    $warnings[] = 'CorrectOrderVtbAb. Наряд успешно изменён';
                } else {
                    $errors[] = $found_order_vtb_ab->errors;
                    throw new Exception('CorrectOrderVtbAb. Ошибка при изменении наряда');
                }
                OrderPlaceVtbAb::deleteAll(['order_vtb_ab_id' => $order_vtb_ab_id]);
                foreach ($order_places as $key => $order_place) {
                    if ($order_place->place_id !== null) {
                        $add_order_place = new OrderPlaceVtbAb();
                        if (!is_string($order_place->order_place_vtb_ab_id) or is_integer((int)$order_place->order_place_vtb_ab_id)) {
                            $add_order_place->id = $order_place->order_place_vtb_ab_id;
                        }
                        $add_order_place->order_vtb_ab_id = $order_vtb_ab_id;
                        $add_order_place->place_id = $order_place->place_id;
                        $add_order_place->description = $order_place->description;
                        if ($add_order_place->save()) {
                            $warnings[] = 'CorrectOrderVtbAb. Наряд на место АБ ВТБ успешно сохранён';
                            $add_order_place->refresh();
//                            Assistant::PrintR(gettype($correct_order_arr));
//                            $warnings['key'][] = $correct_order_arr['order_places'][$key];
//                            $correct_order_arr['order_places'][$key]['order_place_vtb_ab_id'] = $add_order_place->id;
                            $order_place_vtb_ab_id = $add_order_place->id;
                        } else {
                            $errors[] = $add_order_place->errors;
                            throw new Exception('CorrectOrderVtbAb. Ошибка при сохранении наряда на место АБ ВТБ');
                        }
                        foreach ($order_place->order_operations as $key_operation => $order_operation) {
                            if ($order_operation->operation_id !== null) {
                                $add_order_operation = new OrderOperationPlaceVtbAb();
                                if (!is_string($order_operation->order_operation_place_vtb_ab_id) or is_integer((int)$order_operation->order_operation_place_vtb_ab_id)) {
                                    $add_order_operation->id = $order_operation->order_operation_place_vtb_ab_id;
                                }
                                $add_order_operation->order_place_vtb_ab_id = $order_place_vtb_ab_id;
                                $add_order_operation->operation_id = $order_operation->operation_id;
                                $add_order_operation->operation_value_plan = (string)$order_operation->operation_value_plan;
                                if ($order_operation->operation_value_plan) {
                                    $add_order_operation->operation_value_plan = (string)((float)str_replace(",", ".", $order_operation->operation_value_plan));
                                } else {
                                    $add_order_operation->operation_value_plan = null;
                                }

                                $add_order_operation->status_id = self::OPERATION_CREATED;
                                if ($add_order_operation->save()) {
                                    $warnings[] = 'CorrectOrderVtbAb. Операции в наряде на место успешно сохранены';
                                    $add_order_operation->refresh();

                                    $order_operation_vtb_ab_id = $add_order_operation->id;
//                                    $correct_order_arr['order_places'][$key]['order_operations'][$key_operation]['order_operation_place_vtb_ab_id'] = $order_operation_vtb_ab_id;
                                } else {
                                    $errors[] = $add_order_operation->errors;
                                    throw new Exception('CorrectOrderVtbAb. Ошибка при сохранении операций в наряде на место');
                                }
                            }
                        }
                    }
                }
            }
            $transaction->commit();
            $correctedOrder = self::GetOrderVtbAb(json_encode(array('company_department_id' => $company_department_id, 'date' => $date_time_create, 'shift_id' => $shift_id)));
            if ($correctedOrder['status'] == 1) {
                $result = $correctedOrder['Items'];
                $warnings[] = $correctedOrder['warnings'];
            } else {
                $warnings[] = $correctedOrder['warnings'];
                $errors[] = $correctedOrder['errors'];
                $result = (object)array();
            }
        } catch (Throwable $exception) {
            $transaction->rollBack();
            $errors[] = 'CorrectOrderVtbAb. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'CorrectOrderVtbAb. Конец метода';

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SaveOperation() - Сохранение операции для наряда ВТБ АБ
     * @param $order_place_vtb_ab_id
     * @param $operation_id
     * @param $operation_value_plan
     * @return array
     *
     *
     * @package frontend\controllers\ordersystem
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.07.2019 13:38
     */
    public static function SaveOperation($order_place_vtb_ab_id, $operation_id, $operation_value_plan)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $save_operation = array();                                                                                      // Промежуточный результирующий массив
        try {
            $warnings[] = 'SaveOperation. Начало метода сохранения новой операции на место ВТБ АБ';
            /******************** Добавление операции на место ********************/
            $add_operation = new OrderOperationPlaceVtbAb();
            $add_operation->order_place_vtb_ab_id = $order_place_vtb_ab_id;
            $add_operation->operation_id = $operation_id;
            $add_operation->operation_value_plan = (string)$operation_value_plan;
            if ($operation_value_plan) {
                $add_operation->operation_value_plan = (string)((float)str_replace(",", ".", (string)$operation_value_plan));
            } else {
                $add_operation->operation_value_plan = null;
            }
            $add_operation->status_id = self::STATUS_ACTUAL;
            if ($add_operation->save()) {
                $add_operation->refresh();
                $new_operation_id = $add_operation->id;
                $warnings[] = 'SaveOperation. Операция в наряд на место ВТБ АБ успешно добавлена';
            } else {
                throw new Exception('SaveOperation. Ошибка при сохранении операции в наряд на место');
            }
            $warnings[] = 'SaveOperation. Сохранение статуса новой операции наряда ВТБ АБ';
            /******************** Добавление статуса новой операции наряда ВТБ АБ ********************/
            $add_operation_status = new OrderOperationPlaceStatusVtbAb();
            $add_operation_status->order_operation_place_vtb_ab_id = $new_operation_id;
            $add_operation_status->status_id = self::STATUS_ACTUAL;
            if ($add_operation_status->save()) {
                $warnings[] = 'SaveOperation. Статус операции в наряде ВТБ АБ успешно сохраено';
            } else {
                throw new Exception('SaveOperation. Ошибка при сохранении статуса операции в наряде ВТБ АБ');
            }
        } catch (Throwable $exception) {
            $errors[] = 'SaveOperation. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SaveOperation. Конец метода сохранения операции наряда ВТБ АБ';
        $result = $save_operation;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetOrderVtbAb() - Получение информации о наряд ВТБ АБ
     * @param null $data_post - JSON массив с датой на которую необходимо найти наряды ВТБ АБ
     * @return array - массив с структурой: [order_vtb_ab_id]
     *                                                  order_vtb_ab_id:
     *                                                  [order_places]
     *                                                          [order_place_id]
     *                                                                  order_place_vtb_ab_id:
     *                                                                  place_id:
     *                                                                  order_place_vtb_ab_reason:
     *                                                                  [order_operations]
     *                                                                              [order_operation_place_vtb_ab_id]
     *                                                                                          order_operation_place_vtb_ab_id:
     *                                                                                          operation_id:
     *                                                                                          operation_value_plan:
     *                                                                                          [statuses_order_operation_place]
     *                                                                                                          [order_operation_place_status_id]
     *                                                                                                                      order_operation_place_status_id:
     *                                                                                                                      order_operation_place_id:
     *                                                                                                                      status_id:
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetOrderVtbAb&subscribe=&data={%22date%22:%222019-10-24%22,%22company_department_id%22:20028766}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.07.2019 15:02
     */
    public static function GetOrderVtbAb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $order_vtb_ab = array();                                                                                         // Промежуточный результирующий массив
        $company_department_id = null;
        $date = null;
        try {
            if ($data_post !== NULL && $data_post !== '') {
                $warnings[] = 'GetOrderVtbAb. Данные успешно переданы';
                $warnings[] = 'GetOrderVtbAb. Входной массив данных' . $data_post;
            } else {
                throw new Exception('GetOrderVtbAb. Данные с фронта не получены');
            }
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetOrderVtbAb. Декодировал входные параметры';
            if (
                property_exists($post_dec, 'date') &&
                property_exists($post_dec, 'company_department_id') &&
                property_exists($post_dec, 'shift_id')
            )                                                                                                       // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'GetOrderVtbAb.Данные с фронта получены';
                $date = date('Y-m-d', strtotime($post_dec->date));
                $company_department_id = $post_dec->company_department_id;
                $shift_id = $post_dec->shift_id;
            } else {
                throw new Exception('GetOrderVtbAb. Переданы некорректные входные параметры');
            }
            /******************** Поиск наряда ВТБ АБ по дате ********************/
            $get_order_data = OrderVtbAb::find()
                ->joinWith('orderPlaceVtbAbs.place')
                ->joinWith('orderPlaceVtbAbs.orderPlaceVtbAbReasons')
                ->joinWith('orderPlaceVtbAbs.orderOperationPlaceVtbAbs.operation.unit')
                ->joinWith('orderPlaceVtbAbs.orderOperationPlaceVtbAbs.operation.operationGroups')
                ->joinWith('orderPlaceVtbAbs.orderOperationPlaceVtbAbs.orderOperationPlaceStatusVtbAbs')
                ->joinWith('companyDepartment.company')
                ->joinWith('shift')
                ->where(['>=', 'order_vtb_ab.date_time_create', $date . ' 00:00:00'])
                ->andWhere(['<=', 'order_vtb_ab.date_time_create', $date . ' 23:59:59'])
                ->andWhere(['order_vtb_ab.company_department_id' => $company_department_id])
                ->andWhere(['order_vtb_ab.shift_id' => $shift_id])
                ->asArray()
                ->all();
            if ($get_order_data) {
                /******************** Наряд ВТБ АБ ********************/
                foreach ($get_order_data as $order_item) {
                    $order_vtb_ab['order_vtb_ab_id'] = $order_item['id'];
                    $order_vtb_ab['company_department_id'] = $order_item['company_department_id'];
                    $order_vtb_ab['shift_id'] = $order_item['shift_id'];
                    $order_vtb_ab['shift_title'] = $order_item['shift']['title'];
                    $order_vtb_ab['company_title'] = $order_item['companyDepartment']['company']['title'];
                    $order_vtb_ab['date_time_create'] = $order_item['date_time_create'];
                    /******************** Наряд на место ВТБ АБ ********************/
                    foreach ($order_item['orderPlaceVtbAbs'] as $order_place) {
                        $order_vtb_ab['order_places'][$order_place['id']]['order_place_vtb_ab_id'] = $order_place['id'];
                        if (isset($order_place['orderPlaceVtbAbReasons']['reason'])) {
                            $order_vtb_ab['order_places'][$order_place['id']]['order_place_vtb_ab_reason'] = $order_place['orderPlaceVtbAbReasons']['reason'];
                        } else {
                            $order_vtb_ab['order_places'][$order_place['id']]['order_place_vtb_ab_reason'] = "";
                        }
                        $order_vtb_ab['order_places'][$order_place['id']]['place_id'] = $order_place['place_id'];
                        $order_vtb_ab['order_places'][$order_place['id']]['place_title'] = $order_place['place']['title'];
                        $order_vtb_ab['order_places'][$order_place['id']]['mine_id'] = $order_place['place']['mine_id'];
                        $order_vtb_ab['order_places'][$order_place['id']]['description'] = $order_place['description'];
                        /******************** Операции в наряде на место ВТБ АБ ********************/
                        foreach ($order_place['orderOperationPlaceVtbAbs'] as $order_operation_place) {
                            $order_vtb_ab['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['order_operation_place_vtb_ab_id'] = $order_operation_place['id'];
                            $order_vtb_ab['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['operation_id'] = $order_operation_place['operation_id'];
                            $order_vtb_ab['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['operation_title'] = $order_operation_place['operation']['title'];
                            $order_vtb_ab['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['unit_id'] = $order_operation_place['operation']['unit_id'];
                            $order_vtb_ab['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['unit_title'] = $order_operation_place['operation']['unit']['short'];
                            $order_vtb_ab['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['operation_value_fact'] = $order_operation_place['operation_value_fact'];
                            $order_vtb_ab['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['operation_value_plan'] = $order_operation_place['operation_value_plan'];
                            $order_vtb_ab['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['status_id'] = $order_operation_place['status_id'];
                            /******************** Статусы операций в наряде на место ВТБ АБ ********************/
                            foreach ($order_operation_place['orderOperationPlaceStatusVtbAbs'] as $order_op_place_status) {
                                $order_vtb_ab['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['statuses'][$order_op_place_status['id']]['order_operation_status_vtb_ab_id'] = $order_op_place_status['id'];
                                $order_vtb_ab['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['statuses'][$order_op_place_status['id']]['order_operation_place_vtb_ab_id'] = $order_op_place_status['order_operation_place_vtb_ab_id'];
                                $order_vtb_ab['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['statuses'][$order_op_place_status['id']]['status_id'] = $order_op_place_status['status_id'];
                            }
                            /******************** Статусы операций в наряде на место ВТБ АБ ********************/
                            foreach ($order_operation_place['operation']['operationGroups'] as $operation_groups) {
                                $order_vtb_ab['order_places'][$order_place['id']]['order_operations'][$order_operation_place['id']]['operation_groups'][] = $operation_groups['group_operation_id'];
                            }
                        }
                    }

                }
                $errors[] = 'Найден наряд по линии АБ(ВТБ)';
                $status *= 0;
            }


        } catch (Throwable $exception) {
            $errors[] = 'GetOrderVtbAb. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        return array('Items' => $order_vtb_ab, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeleteOrderVtbAb() - Метод удаления наряда АБ ВТБ
     * @param null $data_post - JSON с идентификатором наряда который надо удалить
     * @return array - стандартный массив выходных данных
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=DeleteOrderVtbAb&subscribe=&data={"order_vtb_ab_id":48}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.10.2019 15:18
     */
    public static function DeleteOrderVtbAb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $delete_order_vtb_ab = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'DeleteOrderVtbAb. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('DeleteOrderVtbAb. Не переданы входные параметры');
            }
            $warnings[] = 'DeleteOrderVtbAb. Данные успешно переданы';
            $warnings[] = 'DeleteOrderVtbAb. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'DeleteOrderVtbAb. Декодировал входные параметры';
            if (!property_exists($post_dec, 'order_vtb_ab_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('DeleteOrderVtbAb. Переданы некорректные входные параметры');
            }
            $warnings[] = 'DeleteOrderVtbAb. Данные с фронта получены';
            $order_vtb_ab_id = $post_dec->order_vtb_ab_id;
            OrderVtbAb::deleteAll(['id' => $order_vtb_ab_id]);
        } catch (Throwable $exception) {
            $errors[] = 'DeleteOrderVtbAb. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'DeleteOrderVtbAb. Конец метода';
        $result = $delete_order_vtb_ab;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetWorkersAttendanceInShift() - Возвращает количество работников по профессиям и сколько людей под землёй
     *                                      а сколько на поверхности
     * @param null $data_post - массив с данными [По number_of_employees_by_roles]
     *                                                                      [role_id]
     *                                                                          count_worker:
     *                                           underground_in:
     *                                           surface_in:
     *                                           underground_out:
     *                                           surface_out:
     *                                           sum_workers_in:
     *                                           sum_workers_out:
     * @return array - JSON массив с обязательным идентификатором участка
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetWorkersAttendanceInShift&subscribe=&data={"company_department_id":801,"shift_id":"4","brigade_id":3908,"date_time":"2019-09-04"}
     *
     * Входные не объязательные параметры: order_id, shift_id, chane_id
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.08.2019 8:52
     */
    public static function GetWorkersAttendanceInShift($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $order_id = null;
        $shift_id = null;
        $brigade_id = null;
        $chane_id = null;
        $current_role_id = null;
        $current_kind_object_id = null;
        $worker_value_outgoing = null;
        $order_op_wo = null;
        $count_worker_all = null;
        $underground = null;
        $on_the_ground = 0;
        if ($data_post !== NULL && $data_post !== '') {
            $warnings[] = 'GetWorkersAttendanceInShift. Данные успешно переданы';
            $warnings[] = 'GetWorkersAttendanceInShift. Входной массив данных' . $data_post;
            try {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                $warnings[] = 'GetWorkersAttendanceInShift. Декодировал входные параметры';

                if (
                    property_exists($post_dec, 'company_department_id') &&
                    property_exists($post_dec, 'date_time')
                )                                                                                                       // Проверяем наличие в нем нужных нам полей
                {
                    $warnings[] = 'GetWorkersAttendanceInShift.Данные с фронта получены';
                    $company_department_id = $post_dec->company_department_id;
                    $mine_id = $post_dec->mine_id;
                    $date = date('Y-m-d H:s:i', strtotime($post_dec->date_time));
                    if (property_exists($post_dec, 'order_id')) {
                        $order_id = $post_dec->order_id;
                    }
                    if (property_exists($post_dec, 'shift_id')) {
                        $shift_id = $post_dec->shift_id;
                    }
//                    if (property_exists($post_dec, 'brigade_id')) {
//                        $brigade_id = $post_dec->brigade_id;
//                    }
//                    if (property_exists($post_dec, 'chane_id')) {
//                        $chane_id = $post_dec->chane_id;
//                    }

                    /**
                     * блок расчета статистики по ролям
                     */
                    $found_order_op_wo = OperationWorker::find()
                        ->select(['worker_id', 'role.id as role_id'])
                        ->innerJoin('order_operation', 'order_operation.id = operation_worker.order_operation_id')
                        ->innerJoin('order_place', 'order_place.id = order_operation.order_place_id')
                        ->innerJoin('order', 'order.id = order_place.order_id')
                        ->innerJoin('role', 'operation_worker.role_id = role.id')
                        ->andFilterWhere(['order.date_time_create' => $date])
                        ->andFilterWhere(['order.company_department_id' => $company_department_id])
                        ->andFilterWhere(['order.id' => $order_id])
                        ->andFilterWhere(['order.shift_id' => $shift_id])
                        ->groupBy(['worker_id', 'role_id'])
                        ->orderBy('role_id')
                        ->asArray()
                        ->all();
                    foreach ($found_order_op_wo as $order_op_worker) {
                        if ($current_role_id == $order_op_worker['role_id']) {
                            $order_op_wo[$order_op_worker['role_id']]['role_id'] = (int)$order_op_worker['role_id'];
                            $order_op_wo[$order_op_worker['role_id']]['count_worker']++;
                        } else {
                            $order_op_wo[$order_op_worker['role_id']]['role_id'] = (int)$order_op_worker['role_id'];
                            $order_op_wo[$order_op_worker['role_id']]['count_worker'] = 1;
                        }
                        $current_role_id = $order_op_worker['role_id'];
                    }

                    /**
                     * блок расчета статистики по местам
                     *  группировка по видам мест 2 - горная среда поземка, 6 - месторождение поверхность блок отладил Якимов М.Н.
                     */
                    $kind_places_in_order = OperationWorker::find()
                        ->select(['operation_worker.worker_id as worker_id',
                            'object_type.kind_object_id as kind_object_id'])
                        ->innerJoin('order_operation', 'order_operation.id = operation_worker.order_operation_id')
                        ->innerJoin('order_place', 'order_place.id = order_operation.order_place_id')
                        ->innerJoin('order', 'order.id = order_place.order_id')
                        ->innerJoin('place', 'order_place.place_id  = place.id')
                        ->innerJoin('object', 'place.object_id = object.id')
                        ->innerJoin('object_type', 'object_type.id = object.object_type_id')
                        ->andFilterWhere(['order.date_time_create' => $date])
                        ->andFilterWhere(['order.company_department_id' => $company_department_id])
                        ->andFilterWhere(['order.id' => $order_id])
                        ->andFilterWhere(['order.shift_id' => $shift_id])
                        ->andFilterWhere(['object_type.kind_object_id' => [2, 6]])
                        ->groupBy(['worker_id', 'kind_object_id'])
                        ->asArray()
                        ->all();

                    $group_place[2] = 0;  // 2 - горная среда     - подземка
                    $group_place[6] = 0;  // 6 - месторождение    - поверхность
                    foreach ($kind_places_in_order as $kind_place_in_order) {
                        $group_place[$kind_place_in_order['kind_object_id']]++;
                        $kind_place_workers[$kind_place_in_order['kind_object_id']][$kind_place_in_order['worker_id']] = $kind_place_in_order['worker_id'];
                    }
                    $underground = $group_place[2];
                    $on_the_ground = $group_place[6];

                    /**
                     * блок расчета статистики спустившихся в шахту и вышедших с шахты - не дописал нужно отдельный алгоритм Якимов М.Н.
                     */
//                    $workers_in_order = OperationWorker::find()
//                        ->select(['operation_worker.worker_id as op_worker_id'])
//                        ->innerJoin('order_operation', 'order_operation.id = operation_worker.order_operation_id')
//                        ->innerJoin('order_place', 'order_place.id = order_operation.order_place_id')
//                        ->innerJoin('order', 'order.id = order_place.order_id')
//                        ->andFilterWhere(['order.date_time_create' => $date])
//                        ->andFilterWhere(['order.company_department_id' => $company_department_id])
//                        ->andFilterWhere(['order.id' => $order_id])
//                        ->andFilterWhere(['order.shift_id' => $shift_id])
//                        ->andFilterWhere(['order.brigade_id' => $brigade_id])
//                        ->andFilterWhere(['order.chane_id' => $chane_id])
//                        ->groupBy(['op_worker_id'])
//                        ->asArray()
//                        ->all();
//                    if($workers_in_order){
//                        $worker_cache=(new WorkerCacheController());
//                        foreach($workers_in_order as $worker_in_order){
//                            $check_in_out=$worker_cache->getParameterValue($worker_in_order['worker_id'],158,2);
//                            // список спустившихся - дата наряда
//                            if($check_in_out and $check_in_out['date_time']){
//                                // расчет даты из кеша приведение ее к заданному значению
//                                $check_in_out
//                                // сравнение с датой из наряда
//                                // принятие решения о том куда считать
//                            }
//                            else{
//
//                            }
//
//                        }
//                        $count_worker_all++;
//                    }

                    /**
                     * блок расчета выхода людей из шахты после ее посещения
                     */
                    $worker_value_outgoing = Order::find()
                        ->select(['order.worker_value_outgoing'])
                        ->andFilterWhere(['order.date_time_create' => $date])
                        ->andFilterWhere(['order.mine_id' => $mine_id])
                        ->andFilterWhere(['order.company_department_id' => $company_department_id])
                        ->andFilterWhere(['order.shift_id' => $shift_id])
                        ->scalar();
//                    $warnings[] = 'GetWorkersAttendanceInShift. Считаем вышедших людей из шахты. Ключ наряда'. $order_id;
//                    $warnings[] = 'GetWorkersAttendanceInShift. Считаем вышедших людей из шахты. worker_value_outgoing'. $worker_value_outgoing;
                    if ($worker_value_outgoing == false) {
                        $worker_value_outgoing = 0;
                    }
                } else {
                    $errors[] = 'GetWorkersAttendanceInShift. Переданы некорректные входные параметры';
                    $status *= 0;
                }
            } catch (Throwable $exception) {
                $errors[] = "GetWorkersAttendanceInShift. исключение: ";
                $errors[] = $exception->getMessage();
                $errors[] = (string)$exception->getLine();
                $status *= 0;
            }
        } else {
            $errors[] = 'GetWorkersAttendanceInShift. Данные с фронта не получены';
            $status *= 0;
        }
        if (!isset($kind_place_workers)) {
            $kind_place_workers = (object)array();
        }
        $result = ['number_of_employees_by_roles' => $order_op_wo,      // количество работников по ролям
            'kind_place_workers' => $kind_place_workers,                // работники по профессиям по ролям
            'underground_in' => $underground,                           // количество работников по плану в шахту
            'surface_in' => $on_the_ground,                             // количество работников на поверхности по плану
            'underground_out' => (int)$worker_value_outgoing,           // количество людей из шахты - вводиться в итоге в ручную, но берется из наряда
            'surface_out' => 0,                                         // вышло с поверхности
//            'sum_workers_in' => $count_worker_all,
            'sum_workers_in' => $underground + $on_the_ground,          // итого работников по плану
            'sum_workers_out' => 0];                                    // итого работников по факту

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetInjunctionsByCompanyDepartment() - возвращает все предписания и информацию о них по участку
     * @param $company_department_id - идентификатор участка
     * @return array - массив с следующей структурой: injunction_id:                                                    -идентификатор предписания
     *                                                injunction_status_id:                                             -статус предписания
     *                                                mine_title:                                                       -наименование шахты
     *                                                date_first_status:                                                -дата и время оформления предписания
     *                                                base_checking:                                                    -основание проверки
     *                                                [auditors]                                                        -все кто проводил проверку (аудитор)
     *                                                      [worker_id]                                                 -идентификатор работника (аудитора)
     *                                                            worker_id:                                            -идентификатор работника (аудитора)
     *                                                [presents]                                                        -все кто присутствовал при проверке (присутствующий)
     *                                                      [worker_id]                                                 -идентификатор работника (присутствующий)
     *                                                            worker_id:                                            -идентификатор работника (присутствующий)
     *                                                [injunction_violations]                                           -предписание нарушения
     *                                                          [injunction_violation_id]                               -идентификатор прдеписание нарушения
     *                                                                              inj_violation_place_id:             -идентификатор прежписания нарушения
     *                                                                              violation_id:                       -идентификатор нарушения
     *                                                                              injunction_violation_img:           -картинка предписания нарушения
     *                                                                              document_id:                        -идентификатор документа
     *                                                                              document_title:                     -наименование документа
     *                                                                              paragraph_pb_text:                  -пункт документа
     *                                                                              dangerous:                          -опасность
     *                                                                              probability:                        -вероятность
     *                                                [correct_measures]                                                -корректирующие мероприятия
     *                                                          [correct_measures_id]                                   -идентификатор корректирующего мероприятия
     *                                                                              correct_measures_id:                -идентификатор корректирующего мероприятия
     *                                                                              operation_id:                       -идентификатор операции
     *                                                                              correct_measures_value:             -объём корректирующего мероприятия
     *                                                                              unit_id:                            -идентификатор единиц измерения
     *                                                                              date_time:                          -дата и время корректирующего мероприятия
     *                                                                              correct_measures_status_id:         -статус корректирующего мероприятия
     *                                                [stop_pbs]                                                        -простои
     *                                                          [type]                                                  -тип "Приостановка работ" либо "Технологически вынужденный простой"
     *                                                          [stop_pb_id]                                            -идентификатор прростоя
     *                                                                   stop_pb_id:                                    -идентификатор прростоя
     *                                                                   place_id:                                      -идентификатор места
     *                                                                   equipment_id:                                  -идентификатор оборудования
     *                                                                   date_time_start:                               -дата и время начала простоя
     *                                                                   date_time_end:                                 -дата и время окончания простоя
     *                                                kind_violation_title:                                             -направление нарушения
     *                                                [injunction_attachments]                                          -вложения
     *                                                          [injunction_attachment_id]                              -идентификатор вложения предписания
     *                                                                              attachment_path:                    -путь вложения
     *                                                responsible_worker_id:                                            -ответственный
     *
     * @package frontend\controllers\ordersystem
     *
     * Входные обязательные параметры:
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetInjunctionsByCompanyDepartment&subscribe=&data={%22company_department_id%22:4029831,%22date_time%22:%222019-06-15%22,%22shift_id%22:1,%22brigade_id%22:%22397%22}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 23.08.2019 15:49
     */
    public static function GetInjunctionsByCompanyDepartment($company_department_id)
    {
        $info_about_inj = array();                                                                                      // Промежуточный результирующий массив
//        $status = 0;
        $date_now = date('Y-m-d H:i:s');
        $inj_status_date_time = "1970-01-01 00:00:01";
        $session = Yii::$app->session;
        try {
            $found_data_abot_injuinction = Checking::find()
                ->select([
                    'injunction.id as injunction_id',
                    'injunction_violation.id as inj_viol_id',
                    'injunction_status.status_id as injunction_status_id',
                    'checking_worker_type.worker_id as worker_type_worker_id',
                    'checking_worker_type.worker_type_id as worker_type_id',
                    'injunction_status.date_time as inj_status_date_time',
                    'violator.worker_id as violator_worker_id',
                    'injunction_violation.place_id as inj_vio_place_id',
                    'injunction_violation.violation_id as inj_vio_violation_id',
                    'injunction_img.img_path as inj_img',
                    'document.id as document_id',
                    'document.title as document_title',
                    'paragraph_pb.text as paragraph_pb_text',
                    'injunction_violation.gravity as dangerous',
                    'injunction_violation.probability as probability',
                    'correct_measures.id as correct_measures_id',
                    'correct_measures.operation_id as operation_id',
                    'correct_measures.correct_measures_value as correct_measures_value',
                    'operation.unit_id as unit_id',
                    'correct_measures.result_correct_measures as result_correct_measures',
                    'correct_measures.status_id as correct_measures_status_id',
                    'correct_measures.date_time as date_time_date_time',
                    'injunction.place_id as injunction_place_id',
                    'injunction.description as injunction_description',
                    'injunction_violation.reason_danger_motion_id as reason_danger_motion_id',
                    'kind_violation.title as kind_violation_title',
                    'stop_pb.id as stop_pb_id',
                    'stop_pb.equipment_id as equipment_id',
                    'stop_pb.place_id as stop_pb_place_id',
                    'stop_pb.date_time_start as stop_date_time_start',
                    'stop_pb.date_time_end as stop_date_time_end',
                    'injunction_attachment.id as injunction_attachment_id',
                    'attachment.path as attachment_path',
                    'injunction_attachment.injunction_id as attach_injunction_id',
                    'injunction.worker_id inj_worker_id'
                ])                                                                                                      //TODO когда будет сохраняться по файлам на сервер переделать вывод пути
                ->leftjoin('checking_worker_type', 'checking.id = checking_worker_type.checking_id')
                ->leftjoin('injunction', 'checking.id = injunction.checking_id')
                ->leftjoin('injunction_violation', 'injunction.id = injunction_violation.injunction_id')
                ->leftjoin('injunction_status', 'injunction.id = injunction_status.injunction_id')
                ->leftjoin('injunction_img', 'injunction_violation.id = injunction_img.injunction_violation_id')
                ->leftjoin('violator', 'injunction_violation.id = violator.injunction_violation_id')
                ->leftjoin('paragraph_pb', 'injunction_violation.paragraph_pb_id = paragraph_pb.id')
                ->leftjoin('document', 'paragraph_pb.document_id = document.id')
                ->leftjoin('injunction_violation_status', 'injunction_violation.id = injunction_violation_status.injunction_violation_id')
                ->leftjoin('violation', 'injunction_violation.violation_id = violation.id')
                ->leftjoin('violation_type', 'violation.violation_type_id = violation_type.id')
                ->leftjoin('kind_violation', 'violation_type.kind_violation_id = kind_violation.id')
                ->leftjoin('correct_measures', 'injunction_violation.id = correct_measures.injunction_violation_id')
                ->leftjoin('operation', 'correct_measures.operation_id = operation.id')
                ->leftJoin('stop_pb', 'stop_pb.injunction_violation_id = injunction_violation.id')
                ->leftJoin('injunction_attachment', 'injunction_attachment.injunction_id = injunction.id')
                ->leftJoin('attachment', 'attachment.id = injunction_attachment.attachment_id')
                ->where(['injunction.company_department_id' => $company_department_id])
                ->asArray()
                ->all();
            if ($found_data_abot_injuinction)                                                                           //если данные найдены перебираем их
            {
                foreach ($found_data_abot_injuinction as $injunction_item) {
                    $info_about_inj[$injunction_item['injunction_id']]['injunction_id'] = $injunction_item['injunction_id'];
                    if (strtotime($date_now) >= strtotime($injunction_item['inj_status_date_time'])) {                  //если дата на текущей итерации не меньше либо равна предыдущей тогда записываем дату в переменную
                        $date_now = $injunction_item['inj_status_date_time'];                                           //нужна для определения даты первого статуса (дата первого статуса = дата создания предписания)
                    }
                    $info_about_inj[$injunction_item['injunction_id']]['date_first_status'] = $date_now;
                    if (strtotime($injunction_item['inj_status_date_time']) >= strtotime($inj_status_date_time))        //обратная вещь пред идущему блоку для определения последнего статуса
                    {
                        $inj_status_date_time = $injunction_item['inj_status_date_time'];
                    }
                    if ($injunction_item['inj_status_date_time'] == $inj_status_date_time) {
                        $info_about_inj[$injunction_item['injunction_id']]['status_id'] = $injunction_item['injunction_status_id'];
                    }
                    $info_about_inj[$injunction_item['injunction_id']]['mine_title'] = $session['userMineTitle'];
                    $info_about_inj[$injunction_item['injunction_id']]['base_checking'] = 'На основании Федерального закона от 21 июля 1997 года №116-ФЗ "О промышленной безопасности производственных объектов"';
                    /******************** ЗАПИСЫВАЕМ АУДИТОРОВ И ПРИСУТСТВУЮЩИХ ********************/
                    if ($injunction_item['worker_type_id'] == CheckingController::WORKER_TYPE_AUDITOR)                  //если тип работника аудитор записываем в массив
                    {
                        $info_about_inj[$injunction_item['injunction_id']]['auditors'][$injunction_item['worker_type_worker_id']] = $injunction_item['worker_type_worker_id'];
                    } elseif ($injunction_item['worker_type_id'] == CheckingController::WORKER_TYPE_PRESENT)            //иначе если тип работника присутствующий записываем в массив
                    {
                        $info_about_inj[$injunction_item['injunction_id']]['presentors'][$injunction_item['worker_type_worker_id']] = $injunction_item['worker_type_worker_id'];
                    }
                    /******************** ЗАПИСЫВАЕМ ИНФОРМАЦИЮ О НАРУШЕНИЯХ ********************/
                    $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['injunction_violation_id'] = $injunction_item['inj_viol_id'];
                    $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['violation_id'] = $injunction_item['inj_vio_violation_id'];
                    $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['inj_violation_place_id'] = $injunction_item['inj_vio_place_id'];
                    $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['injunction_violation_img'] = $injunction_item['inj_img'];
                    $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['document_id'] = $injunction_item['document_id'];
                    $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['document_title'] = $injunction_item['document_title'];
                    $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['paragraph_pb_text'] = $injunction_item['paragraph_pb_text'];
                    $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['dangerous'] = $injunction_item['dangerous'];
                    $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['probability'] = $injunction_item['probability'];
                    $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['injunction_description'] = $injunction_item['injunction_description'];
                    $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['kind_violation_title'] = $injunction_item['kind_violation_title'];
                    /******************** ЗАПИСЫВАЕМ КОРРЕКТИРУЮЩИЕ МЕРОПРИЯТИЯ ********************/
                    if ($injunction_item['correct_measures_id'] != null)                                                //если есть идентификатор корректирующего мероприятия не пуст тогда записываем корректирующие мероприятия
                    {
                        $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['correct_measures'][$injunction_item['correct_measures_id']]['correct_measures_id'] = $injunction_item['correct_measures_id'];
                        $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['correct_measures'][$injunction_item['correct_measures_id']]['operation_id'] = $injunction_item['operation_id'];
                        $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['correct_measures'][$injunction_item['correct_measures_id']]['correct_measures_value'] = $injunction_item['correct_measures_value'];
                        $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['correct_measures'][$injunction_item['correct_measures_id']]['unit_id'] = $injunction_item['unit_id'];
                        $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['correct_measures'][$injunction_item['correct_measures_id']]['date_time'] = $injunction_item['date_time_date_time'];
                        $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['correct_measures'][$injunction_item['correct_measures_id']]['correct_measures_status_id'] = $injunction_item['correct_measures_status_id'];
                    }
                    /******************** ЗАПИСЫВАЕМ ПРОСТОИ ПБ ********************/
                    if ($injunction_item['stop_pb_id'] !== null)                                                        //если есть идентификатор остановки не пуст тогда записываем остановки
                    {
                        if ($injunction_item['inj_vio_place_id'] !== $injunction_item['stop_pb_place_id'])              //если место остановки отличается от места на которое выдано предписание то это 'Технологически вынужденная остановка'
                        {
                            $type_stop_pb = 'Технологически вынужденная остановка';
                        } else {                                                                                        //иначе это 'Приостановка работ'
                            $type_stop_pb = 'Приостановка работ';
                        }
                        $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$injunction_item['stop_pb_id']]['stop_pb_id'] = $injunction_item['stop_pb_id'];
                        $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$injunction_item['stop_pb_id']]['place_id'] = $injunction_item['stop_pb_place_id'];
                        $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$injunction_item['stop_pb_id']]['equipment_id'] = $injunction_item['equipment_id'];
                        $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$injunction_item['stop_pb_id']]['stop_date_time_start'] = $injunction_item['stop_date_time_start'];
                        $info_about_inj[$injunction_item['injunction_id']]['injunction_violation'][$injunction_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$injunction_item['stop_pb_id']]['stop_date_time_end'] = $injunction_item['stop_date_time_end'];
                    }
                    if ($injunction_item['injunction_attachment_id'] !== null) {
                        $info_about_inj[$injunction_item['injunction_id']]['injunction_attachment'][$injunction_item['injunction_attachment_id']]['attachment_path'] = $injunction_item['attachment_path'];
                        $injunction_place_id = $injunction_item['injunction_place_id'];
                    }
                    /******************** ИЩЕМ И ЗАПИСЫВАЕМ ОТВЕТСТВЕННОГО ********************/
                    if ($injunction_item['worker_type_id'] == CheckingController::WORKER_TYPE_RESPONSIBLE)              //если тип работника аудитор тогда записываем его
                    {
                        $info_about_inj[$injunction_item['injunction_id']]['responsible_worker_id'] = $injunction_item['worker_type_worker_id'];
                    } else {
                        $info_about_inj[$injunction_item['injunction_id']]['responsible_worker_id'] = $injunction_item['inj_worker_id'];
                    }
                }
            }
        } catch (Throwable $exception) {
            $info_about_inj[] = $exception->getLine();
            $info_about_inj[] = $exception->getMessage();
        }
        return $info_about_inj;
    }

    /**
     * Метод ChangeWorkerValueOutgoing() - Устанавливает/меняет фактическое значение выхода людей из шахты по идентификатору наряда
     * @param null $data_post - JSON с идентификатором наряда у которого необходимо установить/сменить фактическое значение выхода людей из шахты
     * @return array - стандартный массив выходных данных
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=ChangeWorkerValueOutgoing&subscribe=&data={%22order_id%22:59,%22worker_value_outgoing%22:500}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 27.08.2019 17:40
     */
    public static function ChangeWorkerValueOutgoing($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $worker_value_outgoing = -1;                                                                                // Промежуточный результирующий массив
        $warnings[] = 'ChangeWorkerValueOutgoing. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('ChangeWorkerValueOutgoing. Не переданы входные параметры');
            }
            $warnings[] = 'ChangeWorkerValueOutgoing. Данные успешно переданы';
            $warnings[] = 'ChangeWorkerValueOutgoing. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'ChangeWorkerValueOutgoing. Декодировал входные параметры';
            if (!property_exists($post_dec, 'order_id') ||
                !property_exists($post_dec, 'worker_value_outgoing'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('ChangeWorkerValueOutgoing. Переданы некорректные входные параметры');
            }
            $warnings[] = 'ChangeWorkerValueOutgoing. Данные с фронта получены';
            $order_id = $post_dec->order_id;
            $worker_value_outgoing = $post_dec->worker_value_outgoing;
            $order = Order::findOne(['id' => $order_id]);
            if ($order) {
                $order->worker_value_outgoing = $worker_value_outgoing;
                if ($order->save()) {
                    $warnings[] = 'ChangeWorkerValueOutgoing. Фактическое значение выхода людей из шахты успешно сохранено';
                } else {
                    $errors[] = $order->errors;
                    throw new Exception('ChangeWorkerValueOutgoing. Ошибка при сохранении фактического значение выхода людей из шахты');
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'ChangeWorkerValueOutgoing. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'ChangeWorkerValueOutgoing. Конец метода';
        $result = $worker_value_outgoing;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetWorkersVgkByCompanyDepartment() - Получение ВГК по участку и дате назначения
     * @param null $data_post - JSON  с идентификатором участка и датой
     * @return array - массив выходных данных: [worker_id]
     *                                                worker_id:
     *                                                worker_full_name:
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetWorkersVgkByCompanyDepartment&subscribe=&data={"company_department_id":801,"date":"2019-08-29"}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 11.09.2019 17:47
     */
    public static function GetWorkersVgkByCompanyDepartment($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $worker_vgk = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'GetWorkersVgkByCompanyDepartment. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetWorkersVgkByCompanyDepartment. Не переданы входные параметры');
            }
            $warnings[] = 'GetWorkersVgkByCompanyDepartment. Данные успешно переданы';
            $warnings[] = 'GetWorkersVgkByCompanyDepartment. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetWorkersVgkByCompanyDepartment. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'date'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetWorkersVgkByCompanyDepartment. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetWorkersVgkByCompanyDepartment. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $date = date('Y-m-d', strtotime($post_dec->date));
            $get_workers_vgk_by_company_department = CompanyDepartmentWorkerVgk::find()
                ->joinWith('worker.employee')
                ->where(['company_department_worker_vgk.company_department_id' => $company_department_id,
                    'company_department_worker_vgk.date' => $date])
                ->all();
            foreach ($get_workers_vgk_by_company_department as $worker_vkg_by_comp_dep) {
                $name = mb_substr($worker_vkg_by_comp_dep->worker->employee->first_name, 0, 1);
                $patronymic = mb_substr($worker_vkg_by_comp_dep->worker->employee->patronymic, 0, 1);
                $full_name = "{$worker_vkg_by_comp_dep->worker->employee->last_name} {$name}.{$patronymic}.";
                $worker_vgk[$worker_vkg_by_comp_dep->worker_id]['worker_id'] = $worker_vkg_by_comp_dep->worker_id;
                $worker_vgk[$worker_vkg_by_comp_dep->worker_id]['worker_full_name'] = $full_name;
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetWorkersVgkByCompanyDepartment. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetWorkersVgkByCompanyDepartment. Конец метода';
        $result = $worker_vgk;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * ВАЖНО метод переписан для страници табличная форма выдачи наряда - кто его тронет тому придет звиздец!!!
     * только с моего согласования
     * Метод SaveOrderFromTableForm() - сохранение нового наряда со старницы табличная форма выдачи наряда
     *
     * Структура на вход описана в методе GetOrder
     *
     * @param null $data_post - JSON структура наряда
     * @return array $result_main - структура данных вида:[Items]
     *                                                      new_order_id:
     *                                                    status:
     *                                                    [errors]                                                      - массив ошибок
     *                                                    [warnings]                                                    - массив предупреждений (ход выполнения программы)
     *
     * @package frontend\controllers\ordersystem
     * @example amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=SaveOrderFromTableForm&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 27.06.2019 15:36
     */

    public static function SaveOrderFromTableForm($data_post = NULL)
    {
        $log = new LogAmicumFront("SaveOrderFromTableForm");
//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', '9000M');
        $session = Yii::$app->session;

        $order_operation_worker_status = array();                                                                       //массив на добавление статусов (order_operation_worker_status)
        $workers_for_restriction = array();
        $date_now = date('Y-m-d', strtotime(BackendAssistant::GetDateNow()));
        $date__time_now = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));
        $new_order_place_id = null;                                                                                     // идентификатор нового наряда на место
        $save_order = (object)array();

        try {
            $log->addLog('Начало метода');

            if ($data_post !== NULL && $data_post !== '') // Проверяем получены ли данные в формате JSON
            {
                $post_dec = json_decode($data_post); // Декодируем входную JSON строку в объект
            } else {
                throw new Exception('Входная JSON строка не получена');
            }

            if (property_exists($post_dec, 'order_places') &&
                property_exists($post_dec, 'brigadeChaneWorker') &&
                property_exists($post_dec, 'brigadeChaneWorkerSave') &&
                property_exists($post_dec, 'listWorkersByGrafic') &&
                property_exists($post_dec, 'listBrigade') &&
                property_exists($post_dec, 'order_date_time') &&
                property_exists($post_dec, 'shift_id') &&
                property_exists($post_dec, 'company_department_id') &&
                property_exists($post_dec, 'order_id') &&
                property_exists($post_dec, 'title') &&
                property_exists($post_dec, 'injunctions') &&
                property_exists($post_dec, 'department_type_id') &&
                property_exists($post_dec, 'worker_value_outgoing') &&
                property_exists($post_dec, 'routes') &&
                property_exists($post_dec, 'order_status_id')
            ) {
                $log->addLog('Данные с фронта получен');
            } else {
                throw new Exception('Данные с фронта не получены');
            }
            if (property_exists($post_dec, 'mine_id') and
                $post_dec->mine_id != ''
            ) {
                $mine_id = $post_dec->mine_id;
            } else {
                $mine_id = 1;
            }

            $save_order = $post_dec;

            $worker_vgk = $post_dec->worker_vgk;
            $brigadeChaneWorker = $post_dec->brigadeChaneWorker;
            $order_date_time = $post_dec->order_date_time;
            $shift_id = $post_dec->shift_id;
            $company_department_id = $post_dec->company_department_id;
            $title = $post_dec->title;
            $order_status_id = $post_dec->order_status_id;
            $worker_value_outgoing = $post_dec->worker_value_outgoing;
            $injunctions = $post_dec->injunctions;
            $routes = $post_dec->routes;
            unset($post_dec);

            /** ПРОВЕРКА ПРАВ НА ОТПРАВКУ НАРЯДА НА СОГЛАСОВАНИЕ */
            if ($order_status_id == 2 or $order_status_id == 7) {
                $user_worker_id = $session['worker_id'];
                $user_company = (new Query())
                    ->select(['company.id as company_id', 'company.upper_company_id as upper_company_id'])
                    ->from("company")
                    ->innerJoin("company_department", "company_department.company_id=company.id")
                    ->innerJoin("worker", "company_department.id=worker.company_department_id")
                    ->where(['worker.id' => $user_worker_id])
                    ->one();


                $attach_flag = 0;
                if ($user_company['upper_company_id']) {
                    $response = DepartmentController::FindDepartment($user_company['upper_company_id']);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception('Ошибка получения вложенных департаментов' . $user_company['upper_company_id']);
                    }
                    $company_departments = $response['Items'];

                    foreach ($company_departments as $company_department_id_item) {
                        if ($company_department_id_item == $company_department_id) {
                            $attach_flag = 1;
                        }
                    }
                }

                if (!$attach_flag and (!$user_company or ($company_department_id != $user_company['company_id'] and $company_department_id != $user_company['upper_company_id']))) {
                    throw new Exception('Недостаточно прав на отправку наряда на согласование');
                }
            }

            // сохраняем центральный объект наряда
            $response = OrderController::SaveMainOrder($order_date_time, $date__time_now, $shift_id, $company_department_id, $mine_id, (int)$order_status_id, (int)$worker_value_outgoing, $brigadeChaneWorker, $title);
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception('Ошибка сохранения главного наряда');
            }
            $order_id = $response['Items'];

            $save_order->order_id = $order_id;
            $save_order->brigadeChaneWorkerSave = $brigadeChaneWorker;

            /**************************** Сохранение наряда на место *****************************************/
            $response = OrderController::SaveOrderPlaceWithOperation($order_id, $save_order->order_places, $date_now, $save_order->brigadeChaneWorker, $workers_for_restriction);
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception('Ошибка сохранения главного наряда');
            }
            $workers_for_restriction = $response['workers_for_restriction'];

            /**************************** Сохранение предсменных инструктажей ********************************/
            $response = OrderController::SaveOrderInstructionPbs($order_id, $save_order->order_instructions);
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception('Ошибка сохранения предсменных инструктажей');
            }

            /**
             * Блок сохранения членов ВГК в каждый наряд на каждое звено
             **/
            $response = OrderController::SaveOrderWorkerVGK($order_id, $worker_vgk);
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception('Ошибка сохранения членов ВГК в каждый наряд на каждое звено');
            }

            if (!empty($injunctions)) {
                $response = InjunctionController::SaveStatusInjunctionFromOrder($injunctions, $date__time_now);
                $log->addLogAll($response);
                if (!$response['status']) {
                    throw new Exception('Ошибка сохранения статусов предписаний');
                }
            }

            if (!empty($routes)) {
                $response = RouteController::SaveRoute($routes);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Сохранение маршрутов');
                }
            }
            $json_request = json_encode(array('company_department_id' => $company_department_id, 'shift_id' => $shift_id, 'date_time' => $order_date_time, 'mine_id' => $mine_id));
            $response = self::GetOrderTable($json_request);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Получения сохраненного наряда');
            }
            $save_order = $response['Items'];

            if (isset($workers_for_restriction) && !empty($workers_for_restriction)) {
                $json_to_restrict = json_encode(array(
                    'workers' => $workers_for_restriction,
                    'date_time' => $order_date_time,
                    'company_department_id' => $company_department_id,
                    'order_id' => $order_id,
                    'shift_id' => $shift_id));
                $response = self::SaveRestrictionOrder($json_to_restrict);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка при выполнении метода сохранения ограничений по наряду');
                }
                unset($response);
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $save_order], $log->getLogAll());
    }

    /**
     * Название метода: actionTest()
     * Метод для использования отладчика Yii2
     *
     * @param $data - данные в формате json
     * @package frontend\controllers\ordersystem
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 19.06.2019 9:32
     * @since ver
     */
    public function actionTest($data = NULL)
    {
        self::GetInjunctionByCompanyDepartment($data);// Вызов конкретного метода для тестирования
        return $this->render('index');
    }


    /**
     * Подсчёт количества спустившихся и вышедших мз шахты
     * @param int $shift_num - Номер смены
     * @param string $date - Дата смены
     * @param array $workers - Массив идентификаторов работников
     * @return array
     */
    public static function actionGetOrderRegistrationInfo($shift_num, $date, $workers)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();

        try {
            $warnings[] = __FUNCTION__ . '. Начало метода';

            /**=================================================================
             * Валидация входных данных
             * =================================================================*/
            if ($shift_num === null) {
                throw new Exception(__FUNCTION__ . '. Номер смены null');
            }

            if ($shift_num === null) {
                throw new Exception(__FUNCTION__ . '. Номер смены null');
            }

            if ($shift_num === null) {
                throw new Exception(__FUNCTION__ . '. Номер смены null');
            }

            $shift_interval = Assistant::GetDateTimeByShift($date, $shift_num);
            if (!$shift_interval) {
                throw new Exception(__FUNCTION__ . '. Ошибка при расчёте интервала смены');
            }

            /**=================================================================
             * Получение значений параметра 158 за смену
             * =================================================================*/
            // Подзапрос для получения всех значений параметра 158 для конкретных
            // воркеров за определённый промежуток времени
            $workers_registration_values = (new Query())
                ->select([
                    'worker_parameter_value.worker_parameter_id',
                    'worker_parameter_value.value',
                    'worker_parameter_value.date_time'
                ])
                ->from('worker_parameter_value')
                ->innerJoin('worker_parameter', 'worker_parameter.id = worker_parameter_value.worker_parameter_id')
                ->innerJoin('worker_object', 'worker_object.id = worker_parameter.worker_object_id')
                ->where([
                    'worker_object.worker_id' => $workers,
                    'worker_parameter.parameter_id' => 158/*Статус спуска*/
                ])
                ->andWhere(['between', 'worker_parameter_value.date_time', $shift_interval['date_time_start'], $shift_interval['date_time_end']])
                ->all();
            //->groupBy(['worker_parameter_value.worker_parameter_id']);

            if (!$workers_registration_values) {
                throw new Exception(__FUNCTION__ . '. Нет данных по заданному условию');
            }

            /**=================================================================
             * Нахождение последних значений
             * =================================================================*/
            $workers_registration_last_values = [];
            foreach ($workers_registration_values as $worker_registration_value) {
                $workers_registration_last_values[$worker_registration_value['worker_parameter_id']] = $worker_registration_value['value'];
            }
            unset($workers_registration_values);

            /**=================================================================
             * Подсчёт количества спустившихся и вышедших из шахты
             * =================================================================*/
            $workers_registration_count = [];
            foreach ($workers_registration_last_values as $worker_registration_last_value) {
                if (!isset($workers_registration_count[$worker_registration_last_value])) {
                    $workers_registration_count[$worker_registration_last_value] = 1;
                } else {
                    $workers_registration_count[$worker_registration_last_value]++;
                }
            }

//            // Подсчёт количества различных значений параметра 158
//            $workers_registration_count = (new Query())
//                ->select('count(worker_parameter_id) as count_id, value')
//                ->from($workers_registration_values)
//                ->groupBy(['value'])
//                ->all();
//
//            $workers_registration_count = ArrayHelper::map($workers_registration_count, 'value', 'count_id');
//            $warnings[] = $workers_registration_count;
            $result['in_mine'] = (isset($workers_registration_count[1])) ? $workers_registration_count[1] : 0;
            $result['out_mine'] = (isset($workers_registration_count[0])) ? $workers_registration_count[0] : 0;
            $warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors[__FUNCTION__ . ' parameters'] = [
                '$shift_num' => $shift_num,
                '$date' => $date,
                '$workers' => $workers
            ];
        }

        return array('Items' => $result, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = array('Items' => $result, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors);
    }

    /**
     * GetOrderTable - правильный метод получения наряда из БД по департаменту, дате, смене
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetOrderTable&subscribe=&data={%22company_department_id%22:1,%22date_time%22:%222021-07-20%22,%22shift_id%22:1,%22brigade_id%22:%22348%22,%22mine_id%22:%221%22}
     */
    public static function GetOrderTable($data_post = NULL)
    {
        $log = new LogAmicumFront("GetOrderTable");
        // статус выполнения скрипта
        $orders_result = array();
        $injunctions = array();


        try {
//            ini_set('max_execution_time', 6000);
//            ini_set('memory_limit', '3000M');

            $log->addLog("Начало выполнения метода");

            /**
             * блок проверки наличия входных даных от readManager
             */
            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }
            /**
             * блок проверки входных условий с фронта
             */
            if (property_exists($post, 'company_department_id') and
                property_exists($post, 'date_time') and
                property_exists($post, 'shift_id') and
                $post->company_department_id != '' &&
                $post->date_time != '' &&
                $post->shift_id != '') {
                $company_department_id = $post->company_department_id;
                $date_time = date("Y-m-d", strtotime($post->date_time));
                $shift_id = $post->shift_id;
            } else {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            /**
             * Проверка наличия шахты при получении запроса с фронта - модуль  расширяется, потому делаем уточнение
             */
            if (property_exists($post, 'mine_id') and
                $post->mine_id != '') {
                $mine_id = $post->mine_id;
            } else {
                $mine_id = 1;
            }

            /**
             * Блок получения списка людей по плановому графику выходов
             */
            $response = self::GetListWorkersByLastGraficShift($company_department_id, $date_time, $shift_id, $mine_id);
            $log->addLogAll($response);

            $orders_result['listWorkersByGrafic'] = (object)array();
            if ($response['status'] == 1) {
                if (!empty($response['Items'])) {
                    $orders_result['listWorkersByGrafic'] = $response['Items'];
                }
            } else {
                throw new Exception("Ошибка получения списка людей на смену на основе графика выходов");
            }
            unset($response);

            $log->addLog("Список людей на смену на основе графика выходов получен");

            /**
             * Блок получения списка бригад и звеньев из графика выходов
             */
            $response = WorkScheduleController::GetListBrigade($data_post);
            $log->addLogAll($response);
            $orders_result['listBrigade'] = (object)array();
            if ($response['status'] == 1) {
                if (!empty($response['Items'])) {
                    $orders_result['listBrigade'] = $response['Items'];
                }
            } else {
                throw new Exception("Ошибка получения списка бригад из графика выходов");
            }
            unset($response);

            $worker_cache_controller = new WorkerCacheController();

            $log->addLog("Список бригад из графика выходов получен");

            // формируем сам наряд
            $orders_result['company_department_id'] = $company_department_id;
            $orders_result['shift_id'] = $shift_id;
            $orders_result['order_date_time'] = $date_time;

            $orders = Order::find()
                ->joinWith('orderJson')
                ->joinWith('orderWorkerVgks.worker.employee')
                ->joinWith('orderWorkerVgks.role')
                ->joinWith('orderInstructionPbs.instructionPb')
                ->joinWith('orderStatuses')
                ->where([
                    'order.company_department_id' => $company_department_id,
                    'shift_id' => $shift_id,
                    'order.mine_id' => $mine_id,
                    'order.date_time_create' => $date_time
                ])
                ->all();


            if ($orders) {
                foreach ($orders as $order) {
                    if (property_exists($order, "mine_id")) {
                        $orders_result['mine_id'] = $order->mine_id;
                    } else {
                        $orders_result['mine_id'] = 1;
                    }
                    $orders_result['order_id'] = $order->id;
                    $orders_result['title'] = $order->title;
                    $orders_result['order_status_id'] = $order->status_id;
                    $orders_result['worker_value_outgoing'] = $order->worker_value_outgoing;
//                    $orders_result['brigadeChaneWorkerSave'] = json_decode($order->brigadeChaneWorker);

                    if (isset($order->orderJson)) {
                        $orders_result['brigadeChaneWorkerSave'] = json_decode($order->orderJson->brigadeChaneWorker);
                    } else {
                        $orders_result['brigadeChaneWorkerSave'] = null;
                    }
                    // блок полечения предустановок по интерактивной форме (координаты, места, выработки и т.д.)

                    /**
                     * Блок с инструктажами
                     */
                    if (empty($order->orderInstructionPbs)) {
                        $orders_result['order_instructions'] = (object)array();
                    } else {
                        foreach ($order->orderInstructionPbs as $order_instruction_pb) {
                            $orders_result['order_instructions'][$order_instruction_pb->id]['order_instruction_id'] = $order_instruction_pb->id;
                            $orders_result['order_instructions'][$order_instruction_pb->id]['instruction_pb_id'] = $order_instruction_pb->instruction_pb_id;
                            $orders_result['order_instructions'][$order_instruction_pb->id]['title'] = $order_instruction_pb->instructionPb->title;
                        }
                    }
                    /**
                     * Блок со статусами
                     */
                    if (empty($order->orderStatuses)) {
                        $orders_result['order_statuses'] = (object)array();
                    } else {
                        foreach ($order->orderStatuses as $order_status) {
//                            $warnings[] = $order_status;
                            $orders_result['order_statuses'][$order_status->id]['order_status_id'] = $order_status->id;
                            $orders_result['order_statuses'][$order_status->id]['status_id'] = $order_status->status_id;
                            $orders_result['order_statuses'][$order_status->id]['worker_id'] = $order_status->worker_id;
                            $orders_result['order_statuses'][$order_status->id]['date_time_create'] = $order_status->date_time_create;
                            $orders_result['order_statuses'][$order_status->id]['date_time_create_formated'] = date("Y-m-d  H:i", strtotime($order_status->date_time_create));

                            // получаем последних людей по статусам (утвержденные, соглазованные/создавшие)
                            $orders_result['order_accept_workers'][$order_status->status_id]['status_id'] = $order_status->status_id;
                            $orders_result['order_accept_workers'][$order_status->status_id]['worker_id'] = $order_status->worker_id;
                            $orders_result['order_accept_workers'][$order_status->status_id]['date_time_create'] = $order_status->date_time_create;
                            $orders_result['order_accept_workers'][$order_status->status_id]['date_time_create_formated'] = date("Y-m-d  H:i", strtotime($order_status->date_time_create));
                        }
                    }
                    /**
                     * Блок с членами ВГК
                     */
                    if ($order->orderWorkerVgks) {
                        foreach ($order->orderWorkerVgks as $rescuire) {
                            $workers_vgk[$rescuire->worker_id]['worker_id'] = $rescuire->worker_id;
                            $workers_vgk[$rescuire->worker_id]['worker_role'] = $rescuire->role_id;
                            $workers_vgk[$rescuire->worker_id]['worker_role_title'] = $rescuire->role->title;
                            $workers_vgk[$rescuire->worker_id]['worker_full_name'] = $rescuire->worker->employee->last_name . " " . $rescuire->worker->employee->first_name . " " . $rescuire->worker->employee->patronymic;
                            $workers_vgk[$rescuire->worker_id]['vgk'] = $rescuire->vgk;
                        }
                    }
                    $response = RouteController::GetRouteData(json_encode(array('order_id' => $order->id)));
                    $log->addLogAll($response);
                    if ($response['status'] == 1) {
                        $orders_result['routes'] = $response['Items']['routes_by_route_id'];
                    } else {
                        $orders_result['routes'] = (object)array();
                    }
                }
            } else {
                $orders_result['mine_id'] = -1;
                $orders_result['order_id'] = -1;
                $orders_result['title'] = "";
                $orders_result['routes'] = (object)array();
                $orders_result['order_statuses'] = (object)array();
                $orders_result['order_accept_workers'] = (object)array();
                $orders_result['order_status_id'] = 0;
                $orders_result['worker_value_outgoing'] = null;
                $orders_result['brigadeChaneWorkerSave'] = null;
            }


            if (!isset($orders_result['order_instructions'])) {
                $orders_result['order_instructions'] = (object)array();
            }
            unset($orders);

            $log->addLog("Сведения о наряде получены");

            $order = Order::find()
                ->select('
                            order.id as id,
                            order.company_department_id,
                            order.shift_id as shift_id,
                            order.title as title,
                            order.status_id as status_id,
                            order.date_time_create as date_time_create,
                        ')
                ->joinWith('companyDepartment')
                ->joinWith('orderPlaces.place')
                ->joinWith('orderPlaces.passport.passportAttachments.attachment')
                ->joinWith('orderPlaces.orderRouteWorkers')
                ->joinWith('orderPlaces.orderPlaceReasons')
                ->joinWith('orderPlaces.orderOperations.operationWorkers.worker.workerObjects')
                ->joinWith('orderPlaces.orderOperations.operationWorkers.orderOperationWorkerStatuses')
                ->joinWith('orderPlaces.orderOperations.operation.operationGroups')
                ->joinWith('orderPlaces.orderOperations.equipment')
                ->where([
                    'order.company_department_id' => $company_department_id,
                    'shift_id' => $shift_id,
                    'date_time_create' => $date_time,
                    'order.mine_id' => $mine_id
                ])
                ->one();

            // формируем сам наряд

            if ($order) {
                $orders_result['department_type_id'] = $order->companyDepartment->department_type_id;
                // блок получения предустановок по интерактивной форме (координаты, места, выработки и т.д.)

                /**
                 * блок заполнения мест выдачи наряда
                 * ord - сокращенное order
                 */
                if (empty($order->orderPlaces)) {
                    $orders_result['order_places'] = (object)array();
                } else {
                    foreach ($order->orderPlaces as $ord_place) {
                        $ord_place_id = $ord_place->id;
                        $orders_result['order_places'][$ord_place_id]['order_place_id'] = $ord_place_id;
                        $orders_result['order_places'][$ord_place_id]['place_id'] = $ord_place->place_id;
                        $orders_result['order_places'][$ord_place_id]['place_title'] = $ord_place->place->title;
                        if ($ord_place->edge_id) {
                            $orders_result['order_places'][$ord_place_id]['edge_id'] = $ord_place->edge_id;
                        } else {
                            $orders_result['order_places'][$ord_place_id]['edge_id'] = 0;
                        }
                        $orders_result['order_places'][$ord_place_id]['passport_id'] = $ord_place->passport_id;
                        $orders_result['order_places'][$ord_place_id]['passport_attachments'] = array();
                        if (isset($ord_place->passport) and isset($ord_place->passport->passportAttachments)) {
                            foreach ($ord_place->passport->passportAttachments as $passport_attachment) {
                                if ($passport_attachment->attachment) {
                                    $orders_result['order_places'][$ord_place_id]['passport_attachments'][] = array(
                                        'passport_attachment_id' => $passport_attachment->id,
                                        'attachment_path' => $passport_attachment->attachment->path,
                                        'attachment_title' => $passport_attachment->attachment->title,
                                    );
                                }
                            }
                        }
                        $orders_result['order_places'][$ord_place_id]['route_template_id'] = $ord_place->route_template_id;
                        $orders_result['order_places'][$ord_place_id]['coordinate'] = $ord_place->coordinate;
                        $orders_result['order_places'][$ord_place_id]['description'] = $ord_place->description;
                        if ($ord_place->orderPlaceReasons) {
                            $orders_result['order_places'][$ord_place_id]['reason'] = $ord_place->orderPlaceReasons['reason'];
                        } else {
                            $orders_result['order_places'][$ord_place_id]['reason'] = "";
                        }
                        $orders_result['order_places'][$ord_place_id]['operation_production'] = array();
                        $orders_result['order_places'][$ord_place_id]['route_worker'] = array();

                        /**
                         *  блок заполнения наряд путевок ГМ
                         */
                        foreach ($ord_place->orderRouteWorkers as $order_route_worker) {
//                                $worker_id = $order_route_worker->worker_id;
//                                $order_route_worker_id = $order_route_worker->id;
//                                $orders_result['order_places'][$ord_place_id]['route_worker'][$worker_id]['order_route_worker_id'] = $order_route_worker_id;
//                                $orders_result['order_places'][$ord_place_id]['route_worker'][$worker_id]['order_places'] = $order_route_worker->order_places_id;
                            $orders_result['order_places'][$ord_place_id]['route_worker'][$order_route_worker->worker_id]['worker_id'] = $order_route_worker->worker_id;
                            $orders_result['order_places'][$ord_place_id]['route_worker'][$order_route_worker->worker_id]['order_route_json'] = $order_route_worker->order_route_json;
                            $orders_result['order_places'][$ord_place_id]['route_worker'][$order_route_worker->worker_id]['order_route_esp_json'] = $order_route_worker->order_route_esp_json;
                        }
                        if (count($orders_result['order_places'][$ord_place_id]['route_worker']) == 0) {
                            $orders_result['order_places'][$ord_place_id]['route_worker'] = (object)array();
                        }

                        /**
                         * блок заполенния операций
                         */
                        foreach ($ord_place->orderOperations as $order_operation) {

                            $order_operation_id = $order_operation->id;
//                                    $order[$chane_id]['order']['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['operation_worker'] = $operation_worker->id;
                            $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['unit_id'] = $order_operation->operation->unit_id;
                            $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['operation_id'] = $order_operation->operation_id;
                            $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['operation_title'] = $order_operation->operation->title;
                            $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['equipment_id'] = $order_operation->equipment_id;
                            $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['equipment_title'] = $order_operation->equipment->title;
                            if ($order_operation->edge_id) {
                                $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['edge_id'] = $order_operation->edge_id;
                            } else {
                                $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['edge_id'] = 0;
                            }
                            $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['coordinate'] = $order_operation->coordinate;
                            $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['order_operation_id'] = $order_operation->id;
                            $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['order_place_id'] = $ord_place_id;
                            /**
                             * блок заполнения групп операций - нужны для разделения операций по блокам - работы по линии АБ, работы ПК, работы  по производству
                             */
                            $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['operation_groups'] = array();
                            foreach ($order_operation->operation->operationGroups as $operation_group) {
                                $operation_group_id = $operation_group->group_operation_id;
                                $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['operation_groups'][] = $operation_group_id;
                            }
                            $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['operation_value_plan'] = $order_operation->operation_value_plan;
                            $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['operation_value_fact'] = $order_operation->operation_value_fact;
                            $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['operation_load_value'] = $order_operation->operation->operation_load_value;
                            $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['order_operation_id_vtb'] = $order_operation->order_operation_id_vtb;
                            $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['correct_measures_id'] = $order_operation->correct_measures_id;
                            $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['injunction_violation_id'] = $order_operation->injunction_violation_id;
                            $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['injunction_id'] = $order_operation->injunction_id;
                            $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['order_place_id_vtb'] = $order_operation->order_place_id_vtb;
                            $orders_result['order_places'][$ord_place_id]['operation_production'][$order_operation_id]['description'] = $order_operation->description;

// получаем привязку персонала к операциям

                            foreach ($order_operation->operationWorkers as $operation_worker) {


                                $count_operation_status = count($operation_worker->orderOperationWorkerStatuses);
                                $worker_id = $operation_worker->worker_id;
                                $workers_for_outgoing[] = $worker_id;
                                $chane_id = $operation_worker->chane_id;
                                $brigade_id = $operation_worker->brigade_id;

                                $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['operation_production'][$order_operation_id]['status_id'] = $operation_worker->status_id;
                                if (isset($operation_worker->orderOperationWorkerStatuses[0])) {
                                    /**
                                     * список всех статусов операции и кто их установил в выпадашку
                                     */
                                    foreach ($operation_worker->orderOperationWorkerStatuses as $operation_status) {//TODO тут сделать уже по воркерам
                                        $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['operation_production'][$order_operation_id]['statuses'][$operation_status->id]['operation_status_id'] = $operation_status->id;
                                        $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['operation_production'][$order_operation_id]['statuses'][$operation_status->id]['status_id'] = $operation_status->status_id;
                                        $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['operation_production'][$order_operation_id]['statuses'][$operation_status->id]['status_date_time'] = $operation_status->date_time;
                                        $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['operation_production'][$order_operation_id]['statuses'][$operation_status->id]['worker_id'] = $operation_status->worker_id;
                                    }

                                }

                                /**
                                 * формируем блок список работ по каждому работнику
                                 */
                                $orders_result['brigadeChaneWorker'][$brigade_id]['brigade_id'] = $brigade_id;
                                $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['chane_id'] = $chane_id;
                                $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['worker_id'] = $worker_id;

                                // Статусы СКУД не показываются при корректировке наряда!!!
                                // Значения статусов СКУД
                                // 1 - зашел в АБК
                                // 2 - вышел с АБК
                                // 3 - взял свет
                                // 4 - отдал свет
                                // 5 - отметка от светильника на поверхности
                                // 6 - отметка от светильника в шахте
                                $parameter_skud = $worker_cache_controller->getParameterValueHash($worker_id, 529, 2);
                                if ($parameter_skud) {
                                    $dif_date = (int)Assistant::DateTimeDiff($parameter_skud['date_time'], BackendAssistant::GetDateTimeNow(), 'd');
//                                        $warnings[] = $dif_date;
                                    if ($dif_date < 1) {
                                        $type_skud = $parameter_skud['value'];
                                    } else {
                                        $type_skud = 0;
                                    }
                                    $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['type_skud'] = $type_skud;           // статус входа на предприятие через систему СКУД
                                } else {
                                    $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['type_skud'] = 0;           // статус входа на предприятие через систему СКУД
                                }
                                $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['worker_role_id'] = $operation_worker->role_id;
                                $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['operation_list'][$order_operation->operation_id] = $order_operation->operation_id;
                                $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['operation_production'][$order_operation_id]['order_place_id'] = $ord_place_id;
                                $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['operation_production'][$order_operation_id]['order_operation_id'] = $order_operation_id;
                                $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['operation_production'][$order_operation_id]['operation_worker_id'] = $operation_worker->id;

                                $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['operation_production'][$order_operation_id]['coordinate'] = $operation_worker->coordinate;
                                $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['operation_production'][$order_operation_id]['group_workers_unity'] = $operation_worker->group_workers_unity;
                            }
                        }
                        if (count($orders_result['order_places'][$ord_place_id]['operation_production']) == 0) {
                            $orders_result['order_places'][$ord_place_id]['operation_production'] = (object)array();
                        }

                    }
                }

                // получаем координаты работников/смен/бригад
                $orders_chane = OrderWorkerCoordinate::find()
                    ->joinWith('order')
                    ->joinWith('workerRole')
                    ->where([
                        'order.company_department_id' => $company_department_id,
                        'shift_id' => $shift_id,
                        'date_time_create' => $date_time,
                        'order.mine_id' => $mine_id
                    ])
                    ->all();
                if ($orders_chane) {
                    // блок получения предустановок по интерактивной форме (координаты, места, выработки и т.д.)
                    foreach ($orders_chane as $worker_coordinate) {
                        $worker_coordinate_id = $worker_coordinate->id;
                        $brigade_id = $worker_coordinate->brigade_id;
                        $chane_id = $worker_coordinate->chane_id;
                        $worker_id = $worker_coordinate->worker_id;

                        $parameter_skud = $worker_cache_controller->getParameterValueHash($worker_id, 529, 2);
                        if ($parameter_skud) {
                            $dif_date = (int)Assistant::DateTimeDiff($parameter_skud['date_time'], BackendAssistant::GetDateTimeNow(), 'd');
//                                $warnings[] = $dif_date;
                            if ($dif_date < 1) {
                                $type_skud = $parameter_skud['value'];
                            } else {
                                $type_skud = 0;
                            }
                            $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['type_skud'] = $type_skud;           // статус входа на предприятие через систему СКУД
                        } else {
                            $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['type_skud'] = 0;           // статус входа на предприятие через систему СКУД
                        }
                        $workerRoleId = 9;
                        if (property_exists($worker_coordinate, 'workerRole') and property_exists($worker_coordinate->workerRole, 'role_id') and $worker_coordinate->workerRole->role_id) {
                            $workerRoleId = $worker_coordinate->workerRole->role_id;
                        }
                        $orders_result['brigadeChaneWorker'][$brigade_id]['brigade_id'] = $brigade_id;
                        $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['chane_id'] = $chane_id;
                        $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['worker_id'] = $worker_id;
                        $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['worker_role_id'] = $workerRoleId;
                        if (!isset($orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['operation_list'])) {
                            $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['operation_list'] = array();
                        }
                        if (!isset($orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['operation_production'])) {
                            $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['operation_production'] = array();
                        }
                        $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['chane_production'][$worker_coordinate_id]['order_worker_coordinate_id'] = $worker_coordinate_id;
                        $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['chane_production'][$worker_coordinate_id]['order_id'] = $worker_coordinate->order_id;
                        $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['chane_production'][$worker_coordinate_id]['brigade_id'] = $brigade_id;
                        $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['chane_production'][$worker_coordinate_id]['chane_id'] = $chane_id;
                        $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['chane_production'][$worker_coordinate_id]['worker_id'] = $worker_id;
                        $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['chane_production'][$worker_coordinate_id]['place_id'] = $worker_coordinate->place_id;
                        if ($worker_coordinate->edge_id) {
                            $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['chane_production'][$worker_coordinate_id]['edge_id'] = $worker_coordinate->edge_id;
                        } else {
                            $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['chane_production'][$worker_coordinate_id]['edge_id'] = 0;
                        }
                        $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['chane_production'][$worker_coordinate_id]['coordinate_chane'] = $worker_coordinate->coordinate_chane;
                        $orders_result['brigadeChaneWorker'][$brigade_id]['chanes'][$chane_id]['workers'][$worker_id]['chane_production'][$worker_coordinate_id]['coordinate_worker'] = $worker_coordinate->coordinate_worker;
                    }
                }
                unset($orders_chane);


                if (isset($orders_result['brigadeChaneWorker'])) {
                    foreach ($orders_result['brigadeChaneWorker'] as $brigade) {
                        if (isset($brigade['chanes'])) {
                            foreach ($brigade['chanes'] as $chane) {
                                if (isset($chane['workers'])) {
                                    foreach ($chane['workers'] as $worker) {
                                        if (empty($worker['operation_production'])) {
                                            $orders_result['brigadeChaneWorker'][$brigade['brigade_id']]['chanes'][$chane['chane_id']]['workers'][$worker['worker_id']]['operation_production'] = (object)array();
                                        }
                                    }
                                }

                            }
                        }

                    }
                } else {
                    $orders_result['brigadeChaneWorker'] = (object)array();
                }


            } else {
                $orders_result['order_places'] = (object)array();
                $orders_result['department_type_id'] = 0;
            }
            $log->addLog("Сведения об операциях наряда");

            // получения списка избранных мест
            $favouritesPlace = self::GetFavouritesPlace($company_department_id);
            $log->addLogAll($favouritesPlace);
            $orders_result['favourites_place_list'] = $favouritesPlace['Items'];
            unset($favouritesPlace);

            // получения списка избранных шаблонов маршрута
            $favouritesRouteTemplate = self::GetFavouritesRouteTemplate($company_department_id);
            $log->addLogAll($favouritesRouteTemplate);
            if ($favouritesRouteTemplate['status'] == 1) {
                $orders_result['favourites_route_template_list'] = $favouritesRouteTemplate['Items'];
            } else {
                $orders_result['favourites_route_template_list'] = (object)array();
            }
            unset($favouritesRouteTemplate);


            // получения списка избранных инструктажей
            // получаем всех ВГК с участка и ВГК на смене
            $input_filter = array(
                'date' => $date_time,
                'company_department_id' => $company_department_id,
                'mine_id' => $mine_id,
                'shift_id' => $shift_id
            );

            $favouritesBriefing = self::GetFavouritesBriefing(json_encode($input_filter));
            $log->addLogAll($favouritesBriefing);
            if ($favouritesBriefing['status'] == 1) {
                $orders_result['favourites_briefing_list'] = $favouritesBriefing['Items'];
            } else {
                $orders_result['favourites_briefing_list'] = (object)array();
            }
            unset($favouritesBriefing);

            $log->addLog("Избранные получены");

            /**
             * Блок получения ВГК
             */
            $response = self::GetWorkersVgk(json_encode($input_filter));
            $log->addLogAll($response);
            $orders_result['all_rescuers'] = [];
            $filtered_rescuers = [];
            if ($response['status'] == 1) {
                if (!empty($response['Items'])) {
                    $orders_result['all_rescuers'] = $response['Items']['all_rescuers'];
                    $filtered_rescuers = $response['Items']['filtered_rescuers'];
                }
            } else {
                throw new Exception("Ошибка получения шаблона людей ВГК");
            }
            unset($response);

            $log->addLog("Члены ВГК");

            $orders_result['worker_vgk'] = array();
            // если ВГК есть в наряде, то берем оттуда, иначе берем с шаблона - графика входов, а если и там нет, то пустой объект
            if (isset($workers_vgk) and $workers_vgk) {
                foreach ($workers_vgk as $worker_vgk) {
                    $orders_result['worker_vgk'][] = $worker_vgk;
                }
            } else if (!$order) {
                $orders_result['worker_vgk'] = $filtered_rescuers;
            }

            $response = Assistant::GetDateTimeByShift($date_time, $shift_id);
            $date_time_inj = $response['date_time_end'];

            $injunctions = self::GetInjunctionsIds($company_department_id, $date_time_inj);
            $log->addLogAll($injunctions);
            $orders_result['injunctions'] = $injunctions['Items'];

            if (!$orders_result['injunctions']) {
                $orders_result['injunctions'] = (object)array();
            }

            $log->addLog("Предписания");


            $outgoing = array('sum_workers_in' => 0, 'sum_workers_out' => 0);
//            if (isset($workers_for_outgoing)) {
//                $response = self::GetOutgoingWorkers($workers_for_outgoing, $date_time_inj);
//                if ($response['status'] == 1) {
//                    $outgoing = $response['Items'];

//                }
//            }

            $orders_result['outgoing'] = $outgoing;
            $log->addLog("В шахте/из");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        if (empty($orders_result)) {
            $orders_result = (object)array();
        }

        return array_merge(['Items' => $orders_result], $log->getLogAll());
    }

    /**
     * Метод SaveInstructions() - Метод сохранения инструктажей
     * @param null $data_post - JSON с данными: идентификатор наряда, массив инструктажей
     * @return array - стандартный массив выходных данных
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=SaveInstructions&subscribe=&data={"order_id":200,"instructions":{"5":{"instruction_id":5}}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 22.10.2019 11:27
     */
    public static function SaveInstructions($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $inserted_order_instructions = array();
        $warnings[] = 'SaveInstructions. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveInstructions. Не переданы входные параметры');
            }
            $warnings[] = 'SaveInstructions. Данные успешно переданы';
            $warnings[] = 'SaveInstructions. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'SaveInstructions. Декодировал входные параметры';
            if (!property_exists($post_dec, 'order_id') ||
                !property_exists($post_dec, 'instructions'))                                                                 // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('SaveInstructions. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveInstructions. Данные с фронта получены';
            $order_id = $post_dec->order_id;
            $instructions = $post_dec->instructions;
            $found_order = Order::findOne(['id' => $order_id]);
            if (isset($found_order)) {

                $order_instructions = OrderInstructionPb::find()
                    ->where(['order_id' => $order_id])
                    ->all();
                if (isset($order_instructions)) {
                    foreach ($order_instructions as $order_instruction) {
                        $old_order_instr[$order_instruction->instruction_pb_id] = $order_instruction->instruction_pb_id;
                    }
                }
                foreach ($instructions as $instruction) {
                    if (!isset($old_order_instr[$instruction->instruction_pb_id])) {
                        $inserted_order_instructions[] = [$order_id, $instruction->instruction_pb_id];
                    }
                }
            }
            if (!empty($inserted_order_instructions)) {
                $result_insert_order_instruction = Yii::$app->db
                    ->createCommand()
                    ->batchInsert('order_instruction_pb', ['order_id', 'instruction_pb_id'], $inserted_order_instructions)
                    ->execute();
                if ($result_insert_order_instruction != 0) {
                    $warnings[] = 'SaveInstructions. Инструктажи успешно сохранены';
                } else {
                    throw new Exception('SaveInstructions. Возникла ошибка при добавлении инструктажей');
                }
            }

        } catch (Throwable $exception) {
            $errors[] = 'SaveInstructions. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SaveInstructions. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SaveTemplateOrderVtbAb() - Метод сохранения шаблона наряда АБ ВТБ
     * @param null $data_post - JSON с данными: наименование шаблона наряда АБ ВТБ, сам json с нарядом
     * @return array - стандартный массив выходных данных
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=SaveTemplateOrderVtbAb&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 31.10.2019 11:56
     */
    public static function SaveTemplateOrderVtbAb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'SaveTemplateOrderVtbAb. Начало метода';
//    	$data_post = '{"title":"Шаблон 1","order_json":"тут будет json","company_department_id":20028766}';
        try {
            $transaction = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveTemplateOrderVtbAb. Не переданы входные параметры');
            }
            $warnings[] = 'SaveTemplateOrderVtbAb. Данные успешно переданы';
            $warnings[] = 'SaveTemplateOrderVtbAb. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'SaveTemplateOrderVtbAb. Декодировал входные параметры';
            if (!property_exists($post_dec, 'title') ||
                !property_exists($post_dec, 'order_json') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'shift_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('SaveTemplateOrderVtbAb. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveTemplateOrderVtbAb. Данные с фронта получены';
            $title = $post_dec->title;
            $order_json = $post_dec->order_json;
            $company_department_id = $post_dec->company_department_id;
            $shift_id = $post_dec->shift_id;

            $add_template_order_vtb_ab = new TemplateOrderVtbAb();
            $add_template_order_vtb_ab->title = $title;
            $add_template_order_vtb_ab->order_json = $order_json;
            $add_template_order_vtb_ab->company_department_id = $company_department_id;
            $add_template_order_vtb_ab->shift_id = $shift_id;
            if ($add_template_order_vtb_ab->save()) {
                $warnings[] = 'SaveTemplateOrderVtbAb. Шаблон наряда АБ ВТБ успешно сохранён';
            } else {
                $errors[] = $add_template_order_vtb_ab->errors;
                throw new Exception('SaveTemplateOrderVtbAb. Ошибка при сохранении шаблона наряда АБ ВТБ');
            }
            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            $errors[] = 'SavetemplateOrderVtbAb. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SaveTemplateOrderVtbAb. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetTemplateOrderVtbAb() - Получение списка шаблонов по участку
     * @param null $data_post - JSON  с даанными: идентификатор участка, на которы хотим получить все шаблоны
     * @return array - массив со следующей структурой: [template_order_vtb_ab_id]
     *                                                              template_order_vtb_ab_id:
     *                                                              title:
     *                                                              order_json:
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetTemplateOrderVtbAb&subscribe=&data={%22company_department_id%22:20028766}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 31.10.2019 13:29
     */
    public static function GetTemplateOrderVtbAb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $template_orders_vtb_ab = array();
        $warnings[] = 'GetTemplateOrderVtbAb. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetTemplateOrderVtbAb. Не переданы входные параметры');
            }
            $warnings[] = 'GetTemplateOrderVtbAb. Данные успешно переданы';
            $warnings[] = 'GetTemplateOrderVtbAb. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetTemplateOrderVtbAb. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'shift_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetTemplateOrderVtbAb. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetTemplateOrderVtbAb. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $shift_id = $post_dec->shift_id;
            $template_orders_vtb_ab = TemplateOrderVtbAb::find()
                ->select(['id', 'title', 'order_json'])
                ->where(['company_department_id' => $company_department_id])
//                ->andWhere(['shift_id' => $shift_id])
                ->asArray()
                ->all();
            if (!empty($template_orders_vtb_ab)) {
                foreach ($template_orders_vtb_ab as $template_order_vtb_ab) {
                    $result[$template_order_vtb_ab['id']]['template_order_vtb_ab_id'] = $template_order_vtb_ab['id'];
                    $result[$template_order_vtb_ab['id']]['title'] = $template_order_vtb_ab['title'];
                    $result[$template_order_vtb_ab['id']]['order_json'] = $template_order_vtb_ab['order_json'];
                }
            } else {
                $warnings[] = 'GetTemplateOrderVtbAb. Ничего не найдено';
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetTemplateOrderVtbAb. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetTemplateOrderVtbAb. Конец метода';
        if (empty($result)) {
            $result = (object)array();
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteTemplateOrderVtbAb() - Удаление шаблона наряда АБ ВТБ
     * @param null $data_post - JSON с данными: идентификатор шаблона наряда АБ ВТБ
     * @return array - стандартный массив выходных данных
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=DeleteTemplateOrderVtbAb&subscribe=&data={"template_order_vtb_ab_id":2}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 31.10.2019 13:47
     */
    public static function DeleteTemplateOrderVtbAb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'DeleteTemplateOrderVtbAb. Начало метода';
        try {
            $transaction = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('DeleteTemplateOrderVtbAb. Не переданы входные параметры');
            }
            $warnings[] = 'DeleteTemplateOrderVtbAb. Данные успешно переданы';
            $warnings[] = 'DeleteTemplateOrderVtbAb. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'DeleteTemplateOrderVtbAb. Декодировал входные параметры';
            if (!property_exists($post_dec, 'template_order_vtb_ab_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('DeleteTemplateOrderVtbAb. Переданы некорректные входные параметры');
            }
            $warnings[] = 'DeleteTemplateOrderVtbAb. Данные с фронта получены';
            $tepmlate_order_vtb_ab_id = $post_dec->template_order_vtb_ab_id;
            TemplateOrderVtbAb::deleteAll(['id' => $tepmlate_order_vtb_ab_id]);
            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            $errors[] = 'DeleteTemplateOrderVtbAb. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'DeleteTemplateOrderVtbAb. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод ChangeOrderStatus() - Метод смены статуса наряда
     * @param null $data_post - JSON с данными: идентификатор наряда, статус который необходимо установить, причина смены статуса
     * @return array - стандартный массив данных
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=ChangeOrderStatus&subscribe=&data={%22company_department_id%22:20028766,%22date_time%22:%222019-10-18%22,%22shift_id%22:4,%22brigade_id%22:%22314%22}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 21.10.2019 11:31
     */
    public static function ChangeOrderStatus($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $orders_status = array();
        $for_web_socket = array();
        $date_time_now = Assistant::GetDateTimeNow();
        $session = Yii::$app->session;
        $warnings[] = 'ChangeOrderStatus. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('ChangeOrderStatus. Не переданы входные параметры');
            }
            $warnings[] = 'ChangeOrderStatus. Данные успешно переданы';
            $warnings[] = 'ChangeOrderStatus. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'ChangeOrderStatus. Декодировал входные параметры';
            if (!property_exists($post_dec, 'orders') ||
                !property_exists($post_dec, 'status_id') ||
                !property_exists($post_dec, 'description'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('ChangeOrderStatus. Переданы некорректные входные параметры');
            }
            $warnings[] = 'ChangeOrderStatus. Данные с фронта получены';
            $orders = $post_dec->orders;
            $status_id = $post_dec->status_id;
            $description = $post_dec->description;

            Order::updateAll(['status_id' => $status_id], ['in', 'id', $orders]);
            $warnings[] = 'ChangeOrderStatus. Статусы нарядов успешно изменены';

            /** ПРОВЕРКА ПРАВ СОГЛАСОВАНИЕ НАРЯДА В РАМКАХ ОДНОГО ШАХТНОГО ПОЛЯ*/
            $user_mine_id = $session['userMineId'];
            $user = Worker::find()->where(['id' => $session['worker_id']])->one();                                      // ищем подразделение согласовывающего
//
//            $warnings[] = array(
//                'mine_id' => $session['userMineId'],
//            );
//            $company_mines = null;                                                                                      // составляем справочник шахт и подразделений
//            $mines = Mine::find()->indexBy('id')->asArray()->all();
//            foreach ($mines as $key => $mine) {
//                $response = DepartmentController::FindDepartment($mine['company_id']);
//                if ($response['status'] != 1) {
//                    $errors[] = $response['errors'];
//                    $warnings[] = $response['warnings'];
//                    throw new Exception('ChangeOrderStatus. Ошибка получения вложенных департаментов' . $mine['company_id']);
//                }
//
//                foreach ($response['Items'] as $company) {
//                    $company_mines[$company] = $key;
//                }
//            }


            foreach ($orders as $order) {
                $order_find = Order::find()->where(['id' => $order])->one();

//                $warnings[] = array(
//                    'company_mines' => $company_mines,
//                    'order_find' => $order_find,
//                    'user' => $user,
//                    'company_mines' => $company_mines,
//                    'order_find[company_department_id]' => $order_find['company_department_id'],
//                    'user[company_department_id]' => $user['company_department_id'],
//                );
                if (
//                    $company_mines and
                    $order_find and
                    $user and
                    $user_mine_id and
//                    isset($company_mines[$order_find['company_department_id']]) and
//                    isset($company_mines[$user['company_department_id']]) and
//                    $company_mines[$order_find['company_department_id']] == $company_mines[$user['company_department_id']] and
                    $user_mine_id == $order_find['mine_id']
                ) {
                    $orders_status[] = [$order, $status_id, $session['worker_id'], $date_time_now, $description];
                    if ($status_id == self::ORDER_AGREED or $status_id == self::ORDER_NOT_AGREED_AB) {
                        $for_web_socket[] = ['order_id' => $order, 'status_id' => $status_id, 'description' => $description];
                    }
                } else {
                    throw new Exception('Не достаточно прав для согласования/утверждения наряда');
                }
            }

            if (isset($orders_status)) {
                if (!empty($orders_status)) {
                    $result_insertetd_order_status = Yii::$app->db
                        ->createCommand()
                        ->batchInsert('order_status', [
                            'order_id',
                            'status_id',
                            'worker_id',
                            'date_time_create',
                            'description'
                        ], $orders_status)
                        ->execute();
                    if ($result_insertetd_order_status != 0) {
                        $warnings[] = 'ChangeOrderStatus. Новые статусы нарядов успешно добавлены';
                    } else {
                        throw new Exception('ChangeOrderStatus. Ошибка при смене статусов нарядов order_status');
                    }
                }
            }
            if (!empty($for_web_socket)) {
                $ws_msg = json_encode(array(
                    'clientType' => 'server',
                    'actionType' => 'publish',
                    'clientId' => 'server',
                    'subPubList' => ["agreementOrder"],//Согласование наряда
                    'messageToSend' => json_encode($for_web_socket)
                ));
                WebsocketController::actionSendMsg('ws://' . AMICUM_CONNECT_STRING_WEBSOCKET . '/ws', $ws_msg);
            }
        } catch (Throwable $exception) {
            $errors[] = 'ChangeOrderStatus. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'ChangeOrderStatus. Конец метода';

        return array('Items' => [], 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * findParentCompany - метод поиска двух родительских компаний
     * @param $all_company - справочник компаний
     * @param $company_department_id - ключ компании у которой ищем рддителей
     * @return array - ассоциативный массив:
     *          first_company:
     *                  id          - ключ первой родительской компании
     *                  title       - название первой родительской компании
     *           second_company:
     *                  id          - ключ второй родительской компании
     *                  title       - название второй родительской компании
     */
    public static function findParentCompany($all_company, $company_department_id): array
    {
        $companies_parent = array(
            'first_company' => array("id" => null, "title" => null),                                                        // первая компания
            'second_company' => array("id" => null, "title" => null)                                                        // вторая компания
        );
        // поиск двух родителей
        $count_iteration = 0;
        if (!empty($all_company)) {
            do {

                if (isset($all_company[$company_department_id])) {
                    $upper_company_id = $all_company[$company_department_id]['upper_company_id'];
                    if (isset($all_company[$upper_company_id])) {
                        $companies_parent['second_company'] = array("id" => $all_company[$company_department_id]['id'], "title" => $all_company[$company_department_id]['title']);
                    } else {
                        $companies_parent['first_company'] = array("id" => $all_company[$company_department_id]['id'], "title" => $all_company[$company_department_id]['title']);
                    }

                    $company_department_id = $upper_company_id;
                } else {
                    $upper_company_id = null;
                }
                $count_iteration++;

            } while ($upper_company_id or $count_iteration < 20);
        }
        return $companies_parent;
    }

    /**
     * Метод GetOrdersForMatching() - Метод получения данных для формы согласования наряда АБ ВТБ
     * @param null $data_post - JSON с данными: дата создания наряда, идентификатор смены
     * @return array - массив со следующей структурой: [company_department_id]
     *                                                             company_department_id:
     *                                                             company_title:
     *                                                              order_id:
     *                                                              worker_id_creator:
     *                                                              date_time_create:
     *                                                              date_time_create_format:
     *                                                              status_id:
     *                                                              count_all_workers:
     *                                                              brigade_id:
     *                                                              brigade_title:
     *                                                              [order_places]
     *                                                                    [order_place_id]
     *                                                                            order_place_id:
     *                                                                            place_id:
     *                                                                            place_title:
     *                                                                            [order_operations]
     *                                                                                    [order_operation_id]
     *                                                                                            order_operation_id:
     *                                                                                            operation_id:
     *                                                                                            operation_title:
     *                                                                                            equipment_id:
     *                                                                                            equipment_title:
     *                                                                                            unit_title:
     *                                                                                            operation_value_plan:
     *                                                                                            operation_value_fact:
     *                                                                                            count_workers:
     *                                                              [order_worker_vgk]
     *                                                                        [worker_id]
     *                                                                                worker_id:
     *                                                                                vgk:
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetOrdersForMatching&subscribe=&data={%22date%22:%222019-10-15%22,%22shift_id%22:1,%22company_department_id%22:4029720}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 01.11.2019 9:08
     */
    public static function GetOrdersForMatching($data_post = NULL)
    {
        $workers_mine = array(
            'workers_in_surface' => array(),                                                                            // работников на поверхности
            'workers_all' => array(),                                                                                   // всего работников
            'workers_by_role' => array(),                                                                               // работники по ролям
        );                                                                                                              // объект, хранит список людей идущих в шахту или на поверхности
        $companies_parent = array(
            'first_company' => array("id" => null, "title" => null),                                                    // первая компания
            'second_company' => array("id" => null, "title" => null)                                                    // вторая компания
        );                                                                                                              // объект содержащий первую и вторую компании
        $orders = array();                                                                                              // объект, хранит список нарядов
        $result = array(
            'workers_mine' => array(),                                                                                  // список людей в шахте
            'orders' => $workers_mine,                                                                                  // список нарядов
        );                                                                                                              // Промежуточный результирующий массив

        $log = new LogAmicumFront("GetOrdersForMatching");

        try {
            $log->addLog("Начало метода");
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            if (!property_exists($post_dec, 'date') ||
                !property_exists($post_dec, 'shift_id') ||
                !property_exists($post_dec, 'mine_id') ||
                !property_exists($post_dec, 'company_department_id'))                                           // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('Переданы некорректные входные параметры');
            }
            $log->addLog("Данные с фронта получены");

            $date = date('Y-m-d', strtotime($post_dec->date));
            $shift_id = $post_dec->shift_id;
            $mine_id = $post_dec->mine_id;
            $company_department_id = $post_dec->company_department_id;
            $response = DepartmentController::FindDepartment($company_department_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения списка департаментов');
            }
            $company_departments = $response['Items'];
            $all_company = $response['all_company'];

            // поиск двух родителей
            $companies_parent = self::findParentCompany($all_company, $company_department_id);

            $log->addLog("Получен список департамента");

            $filter_order_status = [];

            $typeMatching = $post_dec->typeMatching;
            switch ($typeMatching) {
                case "VTB":
                    $filter_order_status = [
                        'order.status_id' => [
                            2,                                                                                          // Отправлен на согласование/утверждение
                            3,                                                                                          // Утверждение наряда
                            4,                                                                                          // Наряд согласован
                            5,                                                                                          // Наряд выдан
                            6,                                                                                          // Наряд утвержден
                            7,                                                                                          // Наряд скорректирован
                            8,                                                                                          // Отчет сдан
                            9,                                                                                          // Наряд отклонен РВН
                            49,                                                                                         // Наряд принял
                            51,                                                                                         // Операция создана
                            61,                                                                                         // Наряд отклонен АБ
                        ]
                    ];
                    break;
                case "RVN":
                    $filter_order_status = [
                        'order.status_id' => [
//                            2,                                                                                          // Отправлен на согласование/утверждение
                            3,                                                                                          // Утверждение наряда
                            4,                                                                                          // Наряд согласован
                            5,                                                                                          // Наряд выдан
                            6,                                                                                          // Наряд утвержден
//                            7,                                                                                          // Наряд скорректирован
                            8,                                                                                          // Отчет сдан
                            9,                                                                                          // Наряд отклонен РВН
                            49,                                                                                         // Наряд принял
                            51,                                                                                         // Операция создана
                            61,                                                                                         // Наряд отклонен АБ
                        ]
                    ];
                    break;
                case "ALL":
                    $filter_order_status = [];
                    break;
                default:
                    throw new Exception('Не верный тип согласования');
            }


            $find_orders = Order::find()
                ->joinWith('companyDepartment.company')
                ->joinWith('orderStatuses.worker.employee')
                ->innerJoinWith('orderPlaces.orderOperations.operationWorkers')
                ->innerJoinWith('orderPlaces.place.object.objectType')
                ->innerJoinWith('orderPlaces.orderOperations.operation.unit')
                ->innerJoinWith('orderPlaces.orderOperations.equipment')
                ->joinWith('orderPlaces.orderOperations.operation.operationGroups')
                ->joinWith('orderWorkerVgks.worker.employee1')
                ->where(['order.date_time_create' => $date])
                ->andWhere($filter_order_status)
                ->andWhere(['order.shift_id' => $shift_id])
                ->andWhere(['order.mine_id' => $mine_id])
                ->andWhere(['in', 'order.company_department_id', $company_departments])
                ->orderBy('order_status.date_time_create DESC')
                ->asArray()
                ->all();
            $log->addLog("Получен список нарядов");

            if (isset($find_orders)) {
                foreach ($find_orders as $order) {
                    $iterator = 1;
                    $orders[$order['company_department_id']]['company_department_id'] = $order['company_department_id'];
                    $orders[$order['company_department_id']]['company_title'] = $order['companyDepartment']['company']['title'];
                    $orders[$order['company_department_id']]['order_id'] = $order['id'];
                    $orders[$order['company_department_id']]['date_time_create'] = $order['date_time_create'];
                    $orders[$order['company_department_id']]['date_time_create_format'] = date('d.m.Y H:i:s', strtotime($order['date_time_create']));
                    $orders[$order['company_department_id']]['status_id'] = $order['status_id'];
                    $orders[$order['company_department_id']]['ab_status_id'] = $order['status_id'];
                    $orders[$order['company_department_id']]['itr_status_id'] = $order['status_id'];

                    if ($order['orderStatuses'] and !empty($order['orderStatuses'])) {
                        $name_creator = mb_substr($order['orderStatuses'][0]['worker']['employee']['first_name'], 0, 1);
                        $patronymic_creator = mb_substr($order['orderStatuses'][0]['worker']['employee']['patronymic'], 0, 1);
                        $orders[$order['company_department_id']]['full_name_creator'] = "{$order['orderStatuses'][0]['worker']['employee']['last_name']} {$name_creator}. {$patronymic_creator}.";

                        // определение статуса согласования/утверждения наряда
                        foreach ($order['orderStatuses'] as $order_status) {
                            switch ($order_status['status_id']) {
                                case self::ORDER_AGREED:                                                                // наряд согласован
                                    $orders[$order['company_department_id']]['ab_status_id'] = $order_status['status_id'];
                                    break;
                                case self::ORDER_APPROVAL:                                                              // наряд утвержден
                                    $orders[$order['company_department_id']]['itr_status_id'] = $order_status['status_id'];
                                    break;
                                case self::ORDER_NOT_AGREED_AB:                                                         // наряд отклонен
                                case self::ORDER_AGREEMENT:                                                             // наряд отклонен АБ
                                case self::ORDER_CORRECTED:                                                             // наряд скорректирован
                                case self::ORDER_NOT_AGREED_RVN:                                                        // наряд отклонен РВН
                                    $orders[$order['company_department_id']]['ab_status_id'] = $order_status['status_id'];
                                    $orders[$order['company_department_id']]['itr_status_id'] = $order_status['status_id'];
                                    break;
                            }
                            $orders[$order['company_department_id']]['status_id'] = $order_status['status_id'];
                        }
                    } else {
                        $orders[$order['company_department_id']]['full_name_creator'] = "-";
                    }

                    foreach ($order['orderPlaces'] as $order_place) {
                        $orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_place_id'] = $order_place['id'];
                        $orders[$order['company_department_id']]['order_places'][$order_place['id']]['place_id'] = $order_place['place_id'];
                        $orders[$order['company_department_id']]['order_places'][$order_place['id']]['place_title'] = $order_place['place']['title'];
                        $orders[$order['company_department_id']]['order_places'][$order_place['id']]['mine_id'] = $order_place['place']['mine_id'];
                        foreach ($order_place['orderOperations'] as $order_operation) {
                            $orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_operations'][$order_operation['id']]['number_in_order'] = $iterator++;
                            $orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_operations'][$order_operation['id']]['order_operation_id'] = $order_operation['id'];
                            $orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_operations'][$order_operation['id']]['operation_id'] = $order_operation['operation_id'];
                            $orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_operations'][$order_operation['id']]['operation_title'] = $order_operation['operation']['title'] . ($order_operation['equipment_id'] != 1 ? (' ' . $order_operation['equipment']['title']) : "") . ($order_operation['description'] ? (' (' . $order_operation['description'] . ')') : "");
                            $orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_operations'][$order_operation['id']]['description'] = $order_operation['description'];
                            $orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_operations'][$order_operation['id']]['unit_title'] = $order_operation['operation']['unit']['short'];
                            $orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_operations'][$order_operation['id']]['equipment_id'] = $order_operation['equipment_id'];
                            $orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_operations'][$order_operation['id']]['equipment_title'] = $order_operation['equipment']['title'];
                            $orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_operations'][$order_operation['id']]['operation_value_plan'] = $order_operation['operation_value_plan'];
                            $orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_operations'][$order_operation['id']]['operation_value_fact'] = $order_operation['operation_value_fact'];

                            if ($order_operation['operation']['operationGroups']) {
                                foreach ($order_operation['operation']['operationGroups'] as $operation_group)
                                    $orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_operations'][$order_operation['id']]['groups'][$operation_group['group_operation_id']]['group_operation_id'] = $operation_group['group_operation_id'];
                            }
                            foreach ($order_operation['operationWorkers'] as $operation_worker) {
                                $orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_operations'][$order_operation['id']]['operation_workers'][$operation_worker['worker_id']]['worker_id'] = $operation_worker['worker_id'];
                                $orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_operations'][$order_operation['id']]['operation_workers'][$operation_worker['worker_id']]['role_id'] = $operation_worker['role_id'];
                                if (!isset($orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_operations'][$order_operation['id']]['roles'][$operation_worker['role_id']])) {
                                    $orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_operations'][$order_operation['id']]['roles'][$operation_worker['role_id']]['count'] = 0;
                                }
                                $workers_mine['workers_by_role'][$operation_worker['role_id']][$operation_worker['worker_id']] = $operation_worker['worker_id'];
                                $orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_operations'][$order_operation['id']]['roles'][$operation_worker['role_id']]['count']++;
                                if (!isset($orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_operations'][$order_operation['id']]['roles'][$operation_worker['role_id']])) {
                                    $orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_operations'][$order_operation['id']]['roles'][$operation_worker['role_id']]['count'] = 0;
                                }
                                if ($order_place['place']['object']['objectType']['kind_object_id'] != 2) {
                                    $orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_operations'][$order_operation['id']]['worker_in_surface'][$operation_worker['worker_id']] = 1;
                                    $orders[$order['company_department_id']]['worker_in_surface'][$operation_worker['worker_id']] = 1;
                                    $workers_mine['workers_in_surface'][$operation_worker['worker_id']] = $operation_worker['worker_id'];
                                }
                                $orders[$order['company_department_id']]['order_places'][$order_place['id']]['order_operations'][$order_operation['id']]['worker_all'][$operation_worker['worker_id']] = $operation_worker['worker_id'];
                                $orders[$order['company_department_id']]['roles'][$operation_worker['role_id']]['workers'][$operation_worker['worker_id']] = $operation_worker['worker_id'];
                                $orders[$order['company_department_id']]['worker_all'][$operation_worker['worker_id']] = 1;
                                $workers_mine['workers_all'][$operation_worker['worker_id']] = $operation_worker['worker_id'];
                            }
                        }
                    }

                    foreach ($order['orderWorkerVgks'] as $order_worker_vgk) {
                        $orders[$order['company_department_id']]['order_worker_vgk'][$order_worker_vgk['worker_id']]['worker_id'] = $order_worker_vgk['worker_id'];
                        $orders[$order['company_department_id']]['order_worker_vgk'][$order_worker_vgk['worker_id']]['role_id'] = $order_worker_vgk['role_id'];
                        $name_vgk = mb_substr($order_worker_vgk['worker']['employee1']['first_name'], 0, 1);
                        $patronymic_vgk = mb_substr($order_worker_vgk['worker']['employee1']['patronymic'], 0, 1);
                        $orders[$order['company_department_id']]['order_worker_vgk'][$order_worker_vgk['worker_id']]['full_name'] = "{$order_worker_vgk['worker']['employee1']['last_name']} {$name_vgk}. {$patronymic_vgk}.";
                        $orders[$order['company_department_id']]['order_worker_vgk'][$order_worker_vgk['worker_id']]['vgk'] = $order_worker_vgk['vgk'];
                    }
                }
                unset($company_departments);
                unset($find_orders);
                $log->addLog("Перепакоал наряды");
                foreach ($orders as $company_department) {
                    if (isset($company_department['order_places'])) {

                        foreach ($company_department['order_places'] as $order_place) {
                            if (isset($order_place['order_operations'])) {
                                foreach ($order_place['order_operations'] as $order_operation) {
                                    if (!isset($orders[$company_department['company_department_id']]['order_places'][$order_place['order_place_id']]['order_operations'][$order_operation['order_operation_id']]['groups'])) {
                                        $orders[$company_department['company_department_id']]['order_places'][$order_place['order_place_id']]['order_operations'][$order_operation['order_operation_id']]['groups'] = (object)array();
                                    }
                                    if (!isset($orders[$company_department['company_department_id']]['order_places'][$order_place['order_place_id']]['order_operations'][$order_operation['order_operation_id']]['operation_workers'])) {
                                        $orders[$company_department['company_department_id']]['order_places'][$order_place['order_place_id']]['order_operations'][$order_operation['order_operation_id']]['operation_workers'] = (object)array();
                                    }
                                }
                                if (!isset($orders[$company_department['company_department_id']]['order_places'][$order_place['order_place_id']]['order_operations'])) {
                                    $orders[$company_department['company_department_id']]['order_places'][$order_place['order_place_id']]['order_operations'] = (object)array();
                                }
                            }
                        }
                        if (!isset($orders[$company_department['company_department_id']]['order_worker_vgk'])) {
                            $orders[$company_department['company_department_id']]['order_worker_vgk'] = (object)array();
                        }
                    }
                }
                $result['orders'] = $orders;
                $result['workers_mine'] = $workers_mine;
                $log->addLog("Дополнил структуру пустыми объектами");
            }
            if (empty($result['orders'])) {
                $result['orders'] = (object)array();
            }
            if (empty($result['workers_mine']['workers_in_surface'])) {
                $result['workers_mine']['workers_in_surface'] = (object)array();
            }
            if (empty($result['workers_mine']['workers_all'])) {
                $result['workers_mine']['workers_all'] = (object)array();
            }
            if (empty($result['workers_mine']['workers_by_role'])) {
                $result['workers_mine']['workers_by_role'] = (object)array();
            }

            $result['companies_parent'] = $companies_parent;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Окончил метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /** AcceptOrder() - метод получения данных с фронта по подписи данных с нарядной
     * Входные параметры:
     * $order_id - id наряда
     * $worker_id - id работника
     * $status_id - id наряда (выдана/снята/отредактирована)
     * Выходные данные:
     * $status -  статус выполнение метода
     * $errors - возможние ошибки при выполенение метода
     * $warnings - информация о том как выполнился метод
     * алгоритм:
     * 1. проверка входных данных
     * 2. получить перечень операций работника из БД
     * 3. массово обновляю статусы у полученных операций по их ключам operation_worker_id
     * 4. подготавливается массив для вставки в историю статусов по работнику
     * 5. выполняется массовая вставка истории изменения статуса по конткретному работнику
     * пример вызова: 127.0.0.1/read-manager-amicum?controller=ordersystem\OrderSystem&method=AcceptOrder&subscribe=&data={%22order_id%22:%221116%22,%22worker_id%22:2914482,%22status_id%22:49}
     * date time: 2020:01.28
     */
    public static function AcceptOrder($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $result = array();                                                                                              // Промежуточный результирующий массив
        $errors = array();                                                                                              // Массив ошибок
        $session = Yii::$app->session;                                                                                  // текущая сессия
        $operation_worker_ids_array = array();                                                                          // массив ключей конкретных операци работника
        $order_operation_worker_status_array = array();                                                                 // массив истории статусов конкретных операций работника
        try {

            $warnings[] = 'AcceptOrder. Начало метода';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('AcceptOrder. Не переданы входные параметры');
            }
            $warnings[] = 'AcceptOrder. Данные успешно переданы';
            $warnings[] = 'AcceptOrder. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'AcceptOrder. Декодировал входные параметры';
            if (!property_exists($post_dec, 'order_id') ||
                !property_exists($post_dec, 'worker_id') ||
                !property_exists($post_dec, 'status_id'))                                                       // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('AcceptOrder. Переданы некорректные входные параметры');
            }
            $warnings[] = 'AcceptOrder. Данные с фронта получены';
            $order_id = $post_dec->order_id;
            $worker_id = $post_dec->worker_id;
            $status_id = $post_dec->status_id;
            $session_worker_id = $session['worker_id'];// id работника из сессии
            $date_time_now = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));
            /** блок получения данных из БД и обновление найденных operation_worker_id */
            $operation_worker_ids = OperationWorker::find()
                ->select('operation_worker.id as operation_worker_id')
                ->innerJoin('order_operation', 'operation_worker.order_operation_id=order_operation.id')
                ->innerJoin('order_place', 'order_place.id=order_operation.order_place_id')
                ->where(['order_place.order_id' => $order_id])
                ->andWhere(['operation_worker.worker_id' => $worker_id])
                ->indexBy('operation_worker_id')
                ->asArray()
                ->all();
            $warnings[] = "AcceptOrder. Найденные адишники операций работника";
            foreach ($operation_worker_ids as $operation_worker_id) {
                $operation_worker_ids_array[] = $operation_worker_id['operation_worker_id'];
            }
            // массово обновляю статусы у полученных операций по их ключам operation_worker_id
            $update_operation_worker_status = OperationWorker::updateAll(['status_id' => $status_id], ['in', 'id', $operation_worker_ids_array]);
            unset($operation_worker_ids_array);
            $warnings[] = 'AcceptOrder. Статусы обновлены в таблице operation_worker ' . $update_operation_worker_status;
            // подготовка массива для массовой вставки в order_operation_worker_status
            foreach ($operation_worker_ids as $operation_worker_id) {
                $order_operation_worker_status_array[] = array(
                    'operation_worker_id' => $operation_worker_id['operation_worker_id'],
                    'status_id' => $status_id,
                    'date_time' => $date_time_now,
                    'worker_id' => $session_worker_id,
                );
            }
            /** блок вставки данных в БД в таблицу order_operation_worker_status */
            $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert('order_operation_worker_status', ['operation_worker_id', 'status_id', 'date_time', 'worker_id'], $order_operation_worker_status_array)->execute();
            if (!$insert_result_to_MySQL) {
                $errors[] = 'AcceptOrder. Ошибка массовой вставки значения в order_operation_worker_status' . $insert_result_to_MySQL;
            }
            $warnings[] = 'AcceptOrder. Статусы вставлены в таблице operation_worker ' . $insert_result_to_MySQL;
        } catch (Throwable $exception) {
            $errors[] = 'AcceptOrder. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'AcceptOrder. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;

    }

    /**
     * Метод SaveStatusOrderFromTableForm() - сохранение статуса наряда (утверждение, согласование)
     * алгоритм:
     * по полученному ключу наряда, находим сам наряд, в нем меняем статус,
     * далее создаем в истории изменения наряда соответствующую запись и вызвращаем на фронт ключ конкретного статуса из истории нарядов order_status_id
     * входные переменные:
     *          order_id                    -   ключ наряда
     *          status_id                   -   ключ статуса
     * выходные параметры:
     *          order_status_id             -   ключ конкретного статуса наряда из истории
     * @package frontend\controllers\ordersystem
     * @example amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=SaveStatusOrderFromTableForm&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 27.06.2019 15:36
     */

    public static function SaveStatusOrderFromTableForm($data_post = NULL)
    {

        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $session = Yii::$app->session;                                                                                  // текущая сессия
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $memory_size = array();                                                                                         // текущее использование памяти
        $date_time_now = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                        // текущее дата и время
        $order_id = null;                                                                                               // ключ наряда
        $order_status_id = null;                                                                                        // конкретный ключ статуса из истории сохранения наряда

        try {
            $start_mem = memory_get_usage();                                                                            // переменная для хранения старта использованнй памяти
            $memory_size[] = 'start mem ' . (memory_get_usage() - $start_mem) / 1024;
            $warnings[] = 'SaveStatusOrderFromTableForm. Начало метода';
            if ($data_post !== NULL && $data_post !== '') // Проверяем получены ли данные в формате JSON
            {
                $warnings[] = 'SaveStatusOrderFromTableForm. Данные успешно переданы';
                $warnings[] = 'SaveStatusOrderFromTableForm. Входной массив данных' . $data_post;
                $warnings[] = 'SaveStatusOrderFromTableForm. Декодировал входные параметры';
                $post_dec = json_decode($data_post); // Декодируем входную JSON строку в объект
            } else {
                throw new Exception('SaveStatusOrderFromTableForm. Входная JSON строка не получена');
            }

            if (
                property_exists($post_dec, 'order_id') &&
//                property_exists($post_dec, 'worker_id') &&
                property_exists($post_dec, 'status_id')
            ) {
                $warnings[] = 'SaveStatusOrderFromTableForm. Данные с фронта получены';
            } else {
                throw new Exception('SaveStatusOrderFromTableForm. Данные с фронта не получены');
            }

            $order_id = $post_dec->order_id;
            $status_id = $post_dec->status_id;

            unset($post_dec);
            $memory_size[] = 'наряд получен ' . (memory_get_usage() - $start_mem) / 1024;
            // сохраняем центральный объект наряда
            // предварительно проверяем на наличие сохраняемого наряда на участке
            $order = Order::findOne(['id' => $order_id]);
            if (!$order) {
                throw new Exception('SaveStatusOrderFromTableForm. Наряд на утверждение не найден. Предварительно нужно сохранить наряд');
            }
            $order->status_id = $status_id;

            if (!$order->save()) {
                $errors[] = $order->errors;
                throw new Exception('SaveStatusOrderFromTableForm. Ошибка сохранения модели наряда Order');
            }
            $memory_size[] = 'order сохранен ' . (memory_get_usage() - $start_mem) / 1024;
            $order_status = new OrderStatus();
            $order_status->order_id = $order_id;
            $order_status->worker_id = $session['worker_id'];
            $order_status->date_time_create = $date_time_now;
            $order_status->status_id = $status_id;
            $order_status->description = "-";
            if ($order_status->save()) {
                $order_status->refresh();
                $order_status_id = $order_status->id;
            } else {
                $errors[] = $order_status->errors;
                throw new Exception('SaveStatusOrderFromTableForm. Ошибка сохранения модели статуса наряда OrderStatus');
            }
            unset($order_status);
            $memory_size[] = 'order status сохранен ' . (memory_get_usage() - $start_mem) / 1024;

        } catch (Throwable $exception) {
            $errors[] = 'SaveStatusOrderFromTableForm. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = $memory_size;
        $warnings[] = 'SaveStatusOrderFromTableForm. Достигнут конец метода.';

        $result = $order_status_id;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetLimitFromOrders() - Ограничения по наряду для журнала ограничений наряда
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\ordersystem
     *
     * Входные обязательные параметры:
     * @example amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetLimitFromOrders&subscribe=&data={}
     *
     * АЛГОРИТМ РАБОТЫ МЕТОДА:
     * 1. Находим все вложенные участки
     * 2. Выгружаем из таблицы ограничений по нарядам данные по условиям:
     *                                                              месяц равный переданному месяцу
     *                                                              год равный переданному году
     *                                                              участок находиться в массиве найденных вложенных участков
     * 3. Перебор полученных данных
     *      3.1 Формируем структуру выходного объекта
     * 4. Конец перебора
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 10.03.2020 13:52
     */
    public static function GetLimitFromOrders($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'GetLimitFromOrders';
        $limit_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'month') ||
                !property_exists($post_dec, 'year') ||
                !property_exists($post_dec, 'company_department_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $month = $post_dec->month;
            $year = $post_dec->year;
            $company_department_id = $post_dec->company_department_id;
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . ". Не смог получить список вложенных подразделений");
            }
            unset($response);
            /**
             * Получить все ограничения
             */
            $restrictions = RestrictionOrder::find()
                ->joinWith('companyDepartment.company')
                ->joinWith('lastIssuedOrder.worker.employee')
                ->joinWith('lastIssuedOrder.worker.position')
                ->where(['month(restriction_order.date)' => $month])
                ->andWhere(['year(restriction_order.date)' => $year])
                ->andWhere(['in', 'restriction_order.company_department_id', $company_departments])
                ->all();
            if (!empty($restrictions)) {
                /**
                 * Перебор полученных ограничений
                 */
                foreach ($restrictions as $restriction) {
                    $order_id = $restriction->order_id;
                    $shift_id = $restriction->shift_id;
                    $comp_dep_id = $restriction->company_department_id;
                    $company_title = $restriction->companyDepartment->company->title;
                    $order_date_time = date("d.m.Y", strtotime($restriction->date));
                    $limit_data[$order_date_time]['date'] = $order_date_time;
                    $limit_data[$order_date_time]['orders'][$order_id]['order_id'] = $order_id;
                    $limit_data[$order_date_time]['orders'][$order_id]['shift_id'] = $shift_id;
                    $limit_data[$order_date_time]['orders'][$order_id]['company_department_id'] = $comp_dep_id;
                    $limit_data[$order_date_time]['orders'][$order_id]['company_title'] = $company_title;
                    $limit_data[$order_date_time]['orders'][$order_id]['worker_id_issue'] = $restriction->lastIssuedOrder->worker_id;
                    /**
                     * Собираем фио работника создавшего наряд
                     */
                    $patronymic = ' ' . $restriction->lastIssuedOrder->worker->employee->patronymic;
                    $second_name = $restriction->lastIssuedOrder->worker->employee->last_name;
                    $name = $restriction->lastIssuedOrder->worker->employee->first_name;
                    $full_name = "{$second_name} {$name} {$patronymic}";
                    $limit_data[$order_date_time]['orders'][$order_id]['worker_full_name_issue'] = $full_name;
                    unset($patronymic, $second_name, $name, $full_name);
                    $limit_data[$order_date_time]['orders'][$order_id]['stuff_number_issue'] = $restriction->lastIssuedOrder->worker->tabel_number;
                    $limit_data[$order_date_time]['orders'][$order_id]['position_title'] = $restriction->lastIssuedOrder->worker->position->title;
                    /**
                     * Массив ограничений пуст?
                     *      да?     делаем пустой объект limits
                     *      нет?    декодируем json ограничений
                     */
                    if (!empty($restriction->restriction_json)) {
                        $restriction_json = json_decode($restriction->restriction_json);
                    } else {
                        $restriction_json = (object)array();
                    }
                    $limit_data[$order_date_time]['orders'][$order_id]['limits'] = $restriction_json;
                    unset($restriction_json);
                }
                /**
                 * Очистка  памяти от остаточных данных
                 */
                unset($restrictions, $restriction);
            }
            $result = $limit_data;
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetOrderPlacesReason() - Получение данных для журнала причин невыполнения наряда
     * @param null $data_post - JSON  с данными: месяц, год, идентификатор участка
     * @return array - массив с данными следующего вида: Items - массив вида:
     *                                                  {
     *                                                   "12.12.2019":{
     *                                                       "date":"12.12.2019",
     *                                                           "orders":{
     *                                                               "500":{
     *                                                                   "order_id": 500,
     *                                                                   "company_department_id": 4029938,
     *                                                                   "company_title": "Участок подготовительных работ №1",
     *                                                                   "shift_id": 4,
     *                                                                   "worker_id": 2191057,
     *                                                                   "worker_full_name": "Земченок Сергей Мечеславович",
     *                                                                   "worker_stuff_number":2191057,
     *                                                                   "worker_position_title":"Горный мастер",
     *                                                                   "order_places":{
     *                                                                       "6183":{
     *                                                                           "place_id":6183,
     *                                                                           "place_title": "ВГП вент. ствола №3"
     *                                                                       },
     *                                                                       "6262":{
     *                                                                           "place_id":6262,
     *                                                                           "place_title": "Вент. сб. №1 вент. ств. №1"
     *                                                                       }
     *                                                                   },
     *                                                                   "reasons":"Так вышло; Вот так получилось",
     *                                                                   "percent_complete": 80
     *                                                               }
     *                                                           }
     *                                                   }
     *                                                  }
     *                                                  warnings - массив предупреждений (ход выполнения метода)
     *                                                  errors - массив ошибок
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetOrderOperationsReason&subscribe=&data={%22month%22:11,%22year%22:2019,%22company_department_id%22:4029720}
     *
     * Алгоритм работы метода:
     * 1. Получить все вложенные участки
     * 2. Получить все наряды по условию: за переданные месяц, за переданный год, все вложенные участки
     * 3. Перебор полученных данных
     *      3.1 Подготвливаем часть данных
     *      3.2 Перебор нарядов на место
     *              3.2.1 Собираем массив мест
     *              3.2.2 Перебор операций для наряда на место
     *                      3.2.2.1 Собираем массив причин
     *                      3.2.2.2 Есть плановое значение? (operations_compleate)
     *                                  да?    Плановое значение не равно 0
     *                                              да?     добавляем в массив по идентификатору связка операции и наряда на место: факт значение  /  план значение
     *                                              нет?    добавляем в массив по идентификатору связка операции и наряда на место: 0
     *                                  нет?   добавляем в массив по идентификатору связка операции и наряда на место: 1
     *              3.2.3 Конец перебора операций по нарядам на место
     *      3.3 Конец перебора нарядов на место
     *      3.4 Перебор массива полученного в пункте 3.2.2.2 (operations_compleate)
     *              3.4.1 в переменную добавляем значенеи элемента массива operations_compleate (srednee)
     *              3.4.2 увеличиваю счётчик операций (count_operation)
     *      3.5 Конец перебора
     *      3.6 Количество операций не пустое
     *              да?     получаем процент выполнения наряда: среднее полученное в пункте 3.4.1 делим на количество операций полученное в пункте 3.4.2 и умножаем на 100
     *              нет?    процент выполнения  = 0
     * 4. Конец перебора полученных данных
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 11.03.2020 15:01
     */
    public static function GetOrderOperationsReason($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $reasons = array();
        $method_name = 'GetOrderOperationsReason';
        $order_places_reason = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'month') ||
                !property_exists($post_dec, 'year') ||
                !property_exists($post_dec, 'company_department_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $month = $post_dec->month;
            $year = $post_dec->year;
            $company_department_id = $post_dec->company_department_id;
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка при получении вложенных участков');
            }
            unset($response);
            $orders = Order::find()
                ->select('
                            operation_worker.worker_id,
                            order.id as id,
                            order.company_department_id,
                            order.shift_id as shift_id,
                            order.date_time_create as date_time_create,
                            order_operation.id as order_operation_id,
                            order_operation.operation_value_fact,
                            order_operation.operation_value_plan
                        ')
                ->innerJoinWith('orderPlaces.orderOperations.operationWorkers')
                ->innerJoinWith('lastIssuedOrder.worker.employee')
                ->innerJoinWith('lastIssuedOrder.worker.position')
                ->where(['in', 'order.company_department_id', $company_departments])
                ->andWhere('MONTH(order.date_time_create)=' . $month)
                ->andWhere('YEAR(order.date_time_create)=' . $year)
                ->orderBy(['order.date_time_create' => SORT_ASC])
                ->limit(50000)
                ->all();
            if (!empty($orders)) {
                foreach ($orders as $order) {
                    $order_id = $order->id;
                    $shift_id = $order->shift_id;
                    $order_date_time = date("d.m.Y", strtotime($order->date_time_create));
                    $order_places_reason[$order_date_time]['date'] = $order_date_time;
                    $order_places_reason[$order_date_time]['orders'][$order_id]['order_id'] = $order_id;
                    $order_places_reason[$order_date_time]['orders'][$order_id]['shift_id'] = $shift_id;
                    $order_places_reason[$order_date_time]['orders'][$order_id]['company_department_id'] = $order->company_department_id;
                    $order_places_reason[$order_date_time]['orders'][$order_id]['company_title'] = $order->companyDepartment->company->title;
                    $name = $order->lastIssuedOrder->worker->employee->first_name;
                    $second_name = $order->lastIssuedOrder->worker->employee->last_name;
                    $patronymic = ' ' . $order->lastIssuedOrder->worker->employee->patronymic;
                    $full_name = "{$second_name} {$name}{$patronymic}";
                    $order_places_reason[$order_date_time]['orders'][$order_id]['worker_id'] = $order->lastIssuedOrder->worker_id;
                    $order_places_reason[$order_date_time]['orders'][$order_id]['worker_full_name'] = $full_name;
                    $order_places_reason[$order_date_time]['orders'][$order_id]['worker_stuff_number'] = $order->lastIssuedOrder->worker->tabel_number;
                    $order_places_reason[$order_date_time]['orders'][$order_id]['worker_position_title'] = $order->lastIssuedOrder->worker->position->title;
                    $order_places_reason[$order_date_time]['orders'][$order_id]['order_places'] = array();

                    $operations_compleate = [];
                    foreach ($order->orderPlaces as $order_place) {
                        $place_id = $order_place->place_id;
                        $place_title = $order_place->place->title;
                        $order_places_reason[$order_date_time]['orders'][$order_id]['order_places'][$place_id]['place_id'] = $place_id;
                        $order_places_reason[$order_date_time]['orders'][$order_id]['order_places'][$place_id]['place_title'] = $place_title;
                        foreach ($order_place->orderOperations as $order_operation) {
                            if (!empty($order_operation->description)) {
                                $reasons[] = $order_operation->description;
                            }
                            if ($order_operation->operation_value_plan) {
                                $fact = (int)$order_operation->operation_value_fact;
                                $plan = (int)$order_operation->operation_value_plan;
                                if ($plan != 0) {
                                    $operations_compleate[$order_operation->id] = ($fact) / $plan;
                                } else {
                                    $operations_compleate[$order_operation->id] = 0;
                                }
                            } else {
                                $operations_compleate[$order_operation->id] = 1;
                            }
                        }
                    }

                    // вычисляем процент выполнения наряда
                    $srednee = 0;
                    $count_operation = 0;
                    foreach ($operations_compleate as $operation_compleate) {
                        $srednee += $operation_compleate;
                        $count_operation++;
                    }
                    if ($count_operation) {
                        $order_places_reason[$order_date_time]['orders'][$order_id]['percent_complete'] = (int)(($srednee / $count_operation) * 100);
                    } else {
                        $order_places_reason[$order_date_time]['orders'][$order_id]['percent_complete'] = 0;
                    }
                    $order_places_reason[$order_date_time]['orders'][$order_id]['reasons'] = $reasons;
                    $reasons = array();
                }
            }

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $order_places_reason;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SaveRestrictionOrder() - Метод записи ограничений по наряду
     * @param null $data_post
     * @return array
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * workers - массив работников которых надо проверить
     * date_time - дата и время создания наряда
     * company_department_id - идентификатор участка
     * order_id - идентификатор нарда
     * shift_id - идентификатор смены
     *
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * (стандартный массив выходных данных)
     *
     * АЛГОРИТМ:
     * 1. Получаем ограничения по всем работникам на определённую дату из метода CheckRestriction
     * 2. Проверка есть ли уже записи с ограничениями
     *        Да?        Изменяем ограничения наряда
     *        Нет?    Добавляем новую запись ограничений наряда
     *
     * @package frontend\controllers\ordersystem
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.04.2020 7:04
     */
    public static function SaveRestrictionOrder($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveRestrictionOrder';
        $result = array();
        $session = Yii::$app->session;
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'workers') ||
                !property_exists($post_dec, 'date_time') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'order_id') ||
                !property_exists($post_dec, 'shift_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $workers = $post_dec->workers;
            $date_time = date('Y-m-d H:i:s', strtotime($post_dec->date_time));
            $company_department_id = $post_dec->company_department_id;
            $order_id = $post_dec->order_id;
            $shift_id = $post_dec->shift_id;
            /**
             * Получить ограничения по людям на конкретную дату
             */
            $json_to_restrict = json_encode(array('workers' => $workers, 'date_time' => $date_time, 'company_department_id' => $company_department_id));
            $response = NotificationController::CheckRestriction($json_to_restrict);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                /**
                 * Сохраняем/изменяем ограничения по наряду
                 */
                $restriction = RestrictionOrder::findOne(['order_id' => $order_id]);
                if (empty($restriction)) {
                    $restriction = new RestrictionOrder();
                    $restriction->order_id = $order_id;
                    $restriction->worker_id = $session['worker_id'];
                    $restriction->date = $date_time;
                    $restriction->shift_id = $shift_id;
                    $restriction->company_department_id = $company_department_id;
                }
                $restriction->restriction_json = json_encode($response['Items']);
                if (!$restriction->save()) {
                    $errors[] = $restriction->errors;
                    throw new Exception($method_name . '. Ошибка при сохранении ограничений наряда');
                }
                unset($restriction);
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка при получении ограничений');
            }
            unset($response);
            unset($workers);

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * CheckOperationVtbInOrder                 - метод проверки наличия операций ВТБ в выданных нарядах.
     * @param null $data_post
     * @return array
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * order_place_id_vtb - ключ места из ВТБ
     * order_operation_id_vtb - ключ операции из ВТБ
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * Возвращает разрешение на удаление операции ВТБ (true - удалить можно, false -  удалять нельзя)
     * (стандартный массив выходных данных)
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=CheckOperationVtbInOrder&subscribe=&data={"order_operation_id_vtb":405,"order_place_id_vtb":276,"order_id":3192}
     *
     * @author Якимов М.Н.
     * Created date: on 17.04.2020 7:04
     */
    public static function CheckOperationVtbInOrder($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'CheckOperationVtbInOrder';
        $result = false;
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'order_place_id_vtb') ||
                !property_exists($post_dec, 'order_operation_id_vtb'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $order_place_id_vtb = $post_dec->order_place_id_vtb;
            $order_operation_id_vtb = $post_dec->order_operation_id_vtb;

            $exist_vtb_in_order = (new Query())
                ->select('count(order_place.id)')
                ->from('order_place')
                ->innerJoin('order_operation', 'order_operation.order_place_id=order_place.id')
                ->where(['and',
                    ['order_place_id_vtb' => $order_place_id_vtb],
                    ['order_operation_id_vtb' => $order_operation_id_vtb]
                ])
                ->scalar();
            if (!$exist_vtb_in_order) {
                $result = true;
            }

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * CheckPlaceVtbInOrder                 - метод проверки наличия места ВТБ в выданных нарядах.
     * @param null $data_post
     * @return array
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * order_place_id_vtb - ключ места из ВТБ
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * Возвращает разрешение на удаление места ВТБ (true - удалить можно, false -  удалять нельзя)
     * (стандартный массив выходных данных)
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderSystem&method=CheckPlaceVtbInOrder&subscribe=&data={"order_operation_id_vtb":405,"order_place_id_vtb":276,"order_id":3192}
     *
     * @author Якимов М.Н.
     * Created date: on 17.04.2020 7:04
     */
    public static function CheckPlaceVtbInOrder($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'CheckOperationVtbInOrder';
        $result = false;
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'order_place_id_vtb'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $order_place_id_vtb = $post_dec->order_place_id_vtb;

            $exist_vtb_in_order = (new Query())
                ->select('count(order_place.id)')
                ->from('order_place')
                ->innerJoin('order_operation', 'order_operation.order_place_id=order_place.id')
                ->where(
                    ['order_place_id_vtb' => $order_place_id_vtb]
                )
                ->scalar();
            if (!$exist_vtb_in_order) {
                $result = true;
            }

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetOutgoingWorkers - метод по расчету спустившихся фактически в шахту и вышедших из нее на заданную дату
    // входные данные:
    //      $workers            - список с ключами работников, которых ищем в шахте
    //      $date_time_end_shift- дата и время конца смены на период которого ищем статусы спуска и выхода
    // выходные данные:
    //      Items:
    //          sum_workers_in:     0   количество спустившихся в шахту
    //          sum_workers_out:    0   количество вышедших из шахты
    // алгоритм:
    //      1. получить первую отметку в шахте 122/2 по kind_object_id == 2
    //      2. получить все чекауты на время +11 часов от пришедшей даты
    public static function GetOutgoingWorkers($workers, $date_time_end_shift)
    {
        $method_name = 'GetOutgoingWorkers. ';                                                                          // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        $warnings[] = 'GetOutgoingWorkers. Начало метода';
        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
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

            $outgoing = array('sum_workers_in' => 0, 'sum_workers_out' => 0);
            $date_time_start = date('Y-m-d H:i:s', strtotime($date_time_end_shift . '-6 hours'));
            $date_time_end = date('Y-m-d H:i:s', strtotime($date_time_end_shift . '-4 hours'));
            $warnings[] = 'GetOutgoingWorkers. спустилось';
            $warnings[] = 'GetOutgoingWorkers. дата начала поиска date_time_start: ' . $date_time_start;
            $warnings[] = 'GetOutgoingWorkers. дата окончания поиска date_time_end: ' . $date_time_end;
            $place_fact = (new Query())
                ->select('
                        worker_object.worker_id as worker_id,
                    ')
                ->from('worker_parameter_value')
                ->innerJoin('place', 'worker_parameter_value.value=place.id')
                ->innerJoin('object', 'object.id=place.object_id')
                ->innerJoin('object_type', 'object_type.id=object.object_type_id')
                ->innerJoin('worker_parameter', 'worker_parameter.id=worker_parameter_value.worker_parameter_id')
                ->innerJoin('worker_object', 'worker_object.id=worker_parameter.worker_object_id')
                ->where(['in', 'worker_id', $workers])
                ->andWhere(
                    ['parameter_id' => 122, 'parameter_type_id' => 2, 'kind_object_id' => 2]
                )
                ->andWhere(['>=', 'date_time', $date_time_start])
                ->andWhere(['<=', 'date_time', $date_time_end])
                ->indexBy('worker_id')
                ->all();

            /** Отладка */
            $description = 'Места темп';                                                                      // описание текущей отладочной точки
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

            $place_fact_history = (new Query())
                ->select('
                        worker_object.worker_id as worker_id,
                    ')
                ->from('worker_parameter_value_history')
                ->innerJoin('place', 'worker_parameter_value_history.value=place.id')
                ->innerJoin('object', 'object.id=place.object_id')
                ->innerJoin('object_type', 'object_type.id=object.object_type_id')
                ->innerJoin('worker_parameter', 'worker_parameter.id=worker_parameter_value_history.worker_parameter_id')
                ->innerJoin('worker_object', 'worker_object.id=worker_parameter.worker_object_id')
                ->where(['in', 'worker_id', $workers])
                ->andWhere(
                    ['parameter_id' => 122, 'parameter_type_id' => 2, 'kind_object_id' => 2]
                )
                ->andWhere(['>=', 'date_time', $date_time_start])
                ->andWhere(['<=', 'date_time', $date_time_end])
                ->indexBy('worker_id')
                ->all();


            /** Отладка */
            $description = 'места history';                                                                      // описание текущей отладочной точки
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

            if ($place_fact_history) {
                foreach ($place_fact_history as $worker) {
                    $place_fact[$worker['worker_id']]['worker_id'] = $worker['worker_id'];
                }
            }

            $outgoing['sum_workers_in'] = count($place_fact);

            $warnings[] = 'GetOutgoingWorkers. Вышло';
            $date_time_start = date('Y-m-d H:i:s', strtotime($date_time_end_shift . '-4hours'));
            $date_time_end = date('Y-m-d H:i:s', strtotime($date_time_end_shift . '+4 hours'));
            $warnings[] = 'GetOutgoingWorkers. дата начала поиска date_time_start: ' . $date_time_start;
            $warnings[] = 'GetOutgoingWorkers. дата окончания поиска date_time_end: ' . $date_time_end;

            $warnings[] = 'GetOutgoingWorkers. дата окончания поиска date_time_end: ' . $date_time_end;
            $check_out_fact = (new Query())
                ->select('
                        worker_object.worker_id as worker_id,
                    ')
                ->from('worker_parameter_value')
                ->innerJoin('worker_parameter', 'worker_parameter.id=worker_parameter_value.worker_parameter_id')
                ->innerJoin('worker_object', 'worker_object.id=worker_parameter.worker_object_id')
                ->where(['in', 'worker_id', $workers])
                ->andWhere(
                    ['parameter_id' => 158, 'parameter_type_id' => 2, 'value' => 0]
                )
                ->andWhere(['>=', 'date_time', $date_time_start])
                ->andWhere(['<=', 'date_time', $date_time_end])
                ->indexBy('worker_id')
                ->all();

            /** Отладка */
            $description = 'Чекин темп';                                                                      // описание текущей отладочной точки
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

            $check_out_fact_history = (new Query())
                ->select('
                        worker_object.worker_id as worker_id,
                    ')
                ->from('worker_parameter_value_history')
                ->innerJoin('worker_parameter', 'worker_parameter.id=worker_parameter_value_history.worker_parameter_id')
                ->innerJoin('worker_object', 'worker_object.id=worker_parameter.worker_object_id')
                ->where(['in', 'worker_id', $workers])
                ->andWhere(
                    ['parameter_id' => 158, 'parameter_type_id' => 2, 'value' => 0]
                )
                ->andWhere(['>=', 'date_time', $date_time_start])
                ->andWhere(['<=', 'date_time', $date_time_end])
                ->indexBy('worker_id')
                ->all();


            /** Отладка */
            $description = 'Чекин history';                                                                      // описание текущей отладочной точки
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

            if ($check_out_fact_history) {
                foreach ($check_out_fact_history as $worker) {
                    $check_out_fact[$worker['worker_id']]['worker_id'] = $worker['worker_id'];
                }
            }

            $outgoing['sum_workers_out'] = count($check_out_fact);

        } catch (Throwable $exception) {
            $errors[] = 'GetOutgoingWorkers. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $debug;
        $warnings[] = 'GetOutgoingWorkers. Конец метода';

        $result_main = array('Items' => $outgoing, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetOutgoingWorkersFront - метод по расчету спустившихся фактически в шахту и вышедших из нее на заданную дату, вызов с требуемой дату и смену
    // http://127.0.0.1/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetOutgoingWorkersFront&subscribe=&data={%22workers%22:[%22100003%22],%22date_time%22:%222020-07-24%22,%22shift_id%22:%224%22}
    public static function GetOutgoingWorkersFront($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'GetOutgoingWorkersFront';
        $result = false;
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'workers') ||                                                     // Проверяем наличие в нем нужных нам полей
                !property_exists($post_dec, 'shift_id') ||                                                     // Проверяем наличие в нем нужных нам полей
                !property_exists($post_dec, 'date_time'))                                                                // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $workers = $post_dec->workers;
            $shift_id = $post_dec->shift_id;
            $date_time = $post_dec->date_time;

            $response = Assistant::GetDateTimeByShift($date_time, $shift_id);
            $date_time_end = $response['date_time_end'];

            $warnings[] = $method_name . ". Входная дата date_time: " . $date_time;
            $warnings[] = $method_name . ". Расчетная дата date_time_end: " . $date_time_end;
            $warnings[] = $method_name . ". Смена shift_id: " . $shift_id;
            $warnings[] = $method_name . ". Массив работников";
            $warnings[] = $workers;

            $response = self::GetOutgoingWorkers($workers, $date_time_end);
            if ($response['status'] == 1) {
                $result = $response['Items'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            }

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * GetInjunctionsByDate - Метод получения списка предписаний по дате, по департаменту и по смене
     * @param $data_post
     * @return array|object[]
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetInjunctionsByDate&subscribe=&data={%22company_department_id%22:101,%22date_time%22:%222020-07-24%22,%22shift_id%22:%223%22,%22mine_id%22:%221%22}
     */
    public static function GetInjunctionsByDate($data_post = NULL)
    {
        $log = new LogAmicumFront("GetInjunctionsByDate", true);
        $result = null;

        try {
            $log->addLog("Начало метода");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }

            $log->addData($data_post, '$data_post', __LINE__);

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $log->addLog("Декодировал входные параметры");

            if (
                !property_exists($post_dec, 'date_time') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'shift_id') ||
                !property_exists($post_dec, 'mine_id')
            )                                                                                                           // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $log->addLog("Данные с фронта получены");

            $company_department_id = $post_dec->company_department_id;
            $date = date('Y-m-d', strtotime($post_dec->date_time));
            $mine_id = $post_dec->mine_id;
            $shift_id = $post_dec->shift_id;

            $response = Assistant::GetDateTimeByShift($date, $shift_id);
            $date_time = date('Y-m-d H:i:s', strtotime($response['date_end'] . '+1 min'));


            $found_injunctions = Yii::$app->db->createCommand("
                    SELECT  injunction.place_id, 
                            place.title as place_title, 
                            place.mine_id as mine_id, 
                            injunction.company_department_id, 
                            injunction.id as injunction_id, 
                            injunction_violation.id as injunction_violation_id, 
                            injunction.instruct_id_ip as instruct_id_ip, 
                            checking.date_time_start as date_time, 
                            checking.instruct_id as ppk_id_inside, 
                            checking.rostex_number as ppk_id_rtn, 
                            checking.nn_id as ppk_id_nn, 
                            injunction_status.status_id,
                            correct_measures.id as correct_measure_id, 
                            operation.title as operation_title, 
                            violation.title as violation_title, 
                            injunction.kind_document_id as kind_document_id,
                            kind_document.title as kind_document_title,
                            correct_measures.status_id as correct_measure_status_id, 
                            correct_measures.date_time as  correct_measure_date_time,
                            correct_measures.correct_measures_value, 
                            correct_measures.operation_id, 
                            unit.id as unit_id,
                            unit.short as unit_short_title
                    FROM injunction
                    INNER JOIN place ON place.id = injunction.place_id
                    INNER JOIN checking ON checking.id = injunction.checking_id
                    INNER JOIN kind_document ON kind_document.id = injunction.kind_document_id
                    INNER JOIN injunction_violation ON injunction.id = injunction_violation.injunction_id
                    INNER JOIN violation ON violation.id = injunction_violation.violation_id
                    LEFT JOIN correct_measures ON injunction_violation.id = correct_measures.injunction_violation_id
                    LEFT JOIN operation ON correct_measures.operation_id = operation.id
                    LEFT JOIN unit ON unit.id=operation.unit_id
                    INNER JOIN injunction_status ON injunction_status.injunction_id = injunction.id
                    INNER JOIN (SELECT max(date_time) as max_date_time, injunction_id FROM injunction_status WHERE date_time < '" . $date_time . "' GROUP BY injunction_id) inj_status_max
                        ON inj_status_max.max_date_time=injunction_status.date_time AND inj_status_max.injunction_id = injunction_status.injunction_id
                    WHERE (injunction.company_department_id = " . $company_department_id . " OR checking.company_department_id = " . $company_department_id . ") 
                    AND ((injunction_status.status_id!=59) OR (DATE_FORMAT(injunction_status.date_time, '%Y-%m-%d')='" . date("Y-m-d", strtotime($date_time)) . "'))
                    AND place.mine_id = " . $mine_id . "
                    AND injunction.kind_document_id != 2
                ")->queryAll();

            $log->addLog("Получил предписания из БД");

            foreach ($found_injunctions as $injunction) {

                if ($injunction['ppk_id_inside']) {                                                                     // предписание внутреннее
                    $ppk_id = $injunction['ppk_id_inside'];
                } else if ($injunction['ppk_id_rtn']) {                                                                 // предписание РТН
                    $ppk_id = $injunction['ppk_id_rtn'];
                } else if ($injunction['ppk_id_nn']) {                                                                  // нарушение/несоответствие
                    $nn = explode("_", $injunction['ppk_id_nn']);
                    if (isset($nn[1])) {
                        $ppk_id = $nn[1];
                    } else {
                        $ppk_id = $injunction['injunction_id'];
                    }
                } else {                                                                                                // предписание АМИКУМ
                    $ppk_id = $injunction['injunction_id'];
                }

                if (!isset($result['injunctions'][$injunction['injunction_violation_id']])) {
                    $result['injunctions'][$injunction['injunction_violation_id']] = array(
                        'ppk_id' => $ppk_id,
                        'injunction_id' => $injunction['injunction_id'],
                        'injunction_violation_id' => $injunction['injunction_violation_id'],
                        'injunction_date' => $injunction['date_time'],
                        'injunction_status_id' => $injunction['status_id'],
                        'injunction_place_id' => $injunction['place_id'],
                        'injunction_place_title' => $injunction['place_title'],
                        'injunction_mine_id' => $injunction['mine_id'],
                        'violation_title' => $injunction['violation_title'],
                        'kind_document_id' => $injunction['kind_document_id'],
                        'kind_document_title' => $injunction['kind_document_title'],
                        'injunction_company_department_id' => $injunction['company_department_id'],
                        'correct_measure' => array(),
                    );
                }

                if ($injunction['correct_measure_id']) {
                    $result['injunctions'][$injunction['injunction_violation_id']]['correct_measure'][$injunction['correct_measure_id']] = array(
                        'correct_measure_id' => $injunction['correct_measure_id'],
                        'operation_id' => $injunction['operation_id'],
                        'operation_title' => $injunction['operation_title'],
                        'unit_id' => $injunction['unit_id'],
                        'unit_short_title' => $injunction['unit_short_title'],
                        'correct_measures_value' => $injunction['correct_measures_value'],
                        'correct_measure_status_id' => $injunction['correct_measure_status_id'],
                        'correct_measure_date_time' => $injunction['correct_measure_date_time'],
                    );
                }
            }

            if (isset ($result['injunctions'])) {
                foreach ($result['injunctions'] as $injunction) {
                    if (empty($injunction['correct_measure'])) {
                        $result['injunctions'][$injunction['injunction_violation_id']]['correct_measure'][-1] = array(
                            'correct_measure_id' => -1,
                            'operation_id' => 26,
                            'operation_title' => "Устранить",
                            'unit_id' => 79,
                            'unit_short_title' => "-",
                            'correct_measures_value' => 1,
                            'correct_measure_status_id' => 59,
                            'correct_measure_date_time' => Assistant::GetDateTimeNow()
                        );
                    }
                }
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Конец метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetListWorkersByLastGraficShiftMobile - Метод получения списка людей по графику для мобильной версии
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetListWorkersByLastGraficShiftMobile&subscribe=&data={%22company_department_id%22:4029938,%22date_time%22:%222020-03-22%22,%22shift_id%22:2,%22mine_id%22:1}
     */
    public static function GetListWorkersByLastGraficShiftMobile($data_post = NULL)
    {
        $log = new LogAmicumFront("GetListWorkersByLastGraficShiftMobile");

        $result = null;

        try {

            $log->addLog("Начало выполнения метода");

            /**
             * блок проверки наличия входных даных от readManager
             */
            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            /**
             * блок проверки входных условий с фронта
             */
            if (!property_exists($post, 'company_department_id') ||
                !property_exists($post, 'date_time') ||
                !property_exists($post, 'mine_id') ||
                !property_exists($post, 'shift_id') ||
                $post->company_department_id == '' ||
                $post->date_time == '' ||
                $post->mine_id == '' ||
                $post->shift_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $company_department_id = $post->company_department_id;
            $date_time = date("Y-m-d", strtotime($post->date_time));
            $shift_id = $post->shift_id;
            $mine_id = $post->mine_id;

            $date_time = strtotime($date_time);
            $grafic_year = date("Y", $date_time);
            $grafic_month = (int)date("m", $date_time);
            $grafic_day = (int)date("d", $date_time);

            $worker_list = GraficTabelMain::find()
                ->select('
                    grafic_tabel_date_plan.worker_id,
                    grafic_tabel_date_plan.role_id,
                    role.title as role_title,
                    grafic_tabel_date_plan.chane_id,
                    employee.first_name as first_name,
                    employee.last_name as last_name,
                    employee.patronymic as patronymic,
                    worker.tabel_number as tabel_number,
                    position.id as position_id,
                    position.title as position_title,
                    position.qualification as position_qualification,
                    position.short_title as position_short_title,
                ')
                ->innerJoinWith('graficTabelDatePlans.role', false)
                ->innerJoinWith('graficTabelDatePlans.worker.position', false)
                ->innerJoinWith('graficTabelDatePlans.worker.employee', false)
                ->where(['status_id' => self::STATUS_ACTUAL,
                    'grafic_tabel_main.company_department_id' => $company_department_id,
                    'grafic_tabel_main.year' => $grafic_year,
                    'grafic_tabel_main.month' => $grafic_month,
                    'grafic_tabel_date_plan.day' => $grafic_day,
                    'grafic_tabel_date_plan.shift_id' => $shift_id,
                    'grafic_tabel_date_plan.mine_id' => $mine_id,
                    'grafic_tabel_date_plan.working_time_id' => self::WORK_TIME])
                ->indexBy('worker_id')
                ->asArray()
                ->all();

            if (!empty($worker_list)) {
                $log->addLog("График выходов на заданную смену есть, начинаю получать список бригад по департаменту");

                foreach ($worker_list as $worker) {
                    $chanes[$worker['chane_id']] = $worker['chane_id'];
                }


                $found_chanes = Chane::find()
                    ->where(['id' => $chanes])
                    ->indexBy('id')
                    ->all();
                /**
                 * По полученному графику выходов начинаем группировать людей в бригады
                 */

                $workers_by_brigade = array();

                if ($found_chanes) {

                    foreach ($worker_list as $worker) {
                        $worker_id = $worker['worker_id'];
                        $brigade_id = $found_chanes[$worker['chane_id']]->brigade_id;
                        $chane_id = $found_chanes[$worker['chane_id']]->id;
                        $chaner_id = $found_chanes[$worker['chane_id']]->chaner_id;
                        $chane_type_id = $found_chanes[$worker['chane_id']]->chane_type_id;

                        if (!isset($workers_by_brigade[$brigade_id])) {
                            $workers_by_brigade[$brigade_id]['brigade_id'] = $brigade_id;
                            $workers_by_brigade[$brigade_id]['chanes'] = [];
                        }

                        if (!isset($workers_by_brigade[$brigade_id]['chanes'][$chane_id])) {
                            $workers_by_brigade[$brigade_id]['chanes'][$chane_id]['chane_id'] = $chane_id;
                            $workers_by_brigade[$brigade_id]['chanes'][$chane_id]['chaner_id'] = $chaner_id;
                            $workers_by_brigade[$brigade_id]['chanes'][$chane_id]['chane_type_id'] = $chane_type_id;
                            $workers_by_brigade[$brigade_id]['chanes'][$chane_id]['workers'] = [];
                        }

                        if ($worker_id == $chaner_id) {
                            $chaner_worker = true;
                        } else {
                            $chaner_worker = false;
                        }

                        $workers_by_brigade[$brigade_id]['chanes'][$chane_id]['workers'][$worker_id] = array(
                            'mine_id' => $mine_id,
                            'brigade_id' => $brigade_id,
                            'chane_id' => $worker['chane_id'],
                            'worker_id' => $worker_id,
                            'first_name' => $worker['first_name'],
                            'patronymic' => $worker['patronymic'],
                            'last_name' => $worker['last_name'],
                            'full_name_short' => Assistant::GetShortFullName($worker['first_name'], $worker['patronymic'], $worker['last_name']),
                            'full_name' => Assistant::GetFullName($worker['first_name'], $worker['patronymic'], $worker['last_name']),
                            'tabel_number' => $worker['tabel_number'],
                            'role_id' => $worker['role_id'],
                            'role_title' => $worker['role_title'],
                            'position_id' => $worker['position_id'],
                            'position_title' => $worker['position_title'],
                            'position_short_title' => $worker['position_short_title'],
                            'position_qualification' => $worker['position_qualification'],
                            'chaner' => $chaner_worker,
                        );

                    }
                }
                $result = $workers_by_brigade;
                unset($workers_by_brigade);
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * Метод SaveDocumentWithEcp() - Сохранение документа с электронной подписью
     * @param null $data_post
     * @return array
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     *
     *      "document_id"                     // ключ документа
     *      "document_with_ecp_id",           // ключ единицы изменерния
     *      "worker_id"                       // id сотрудника, создавшего документ (String)
     *      "document_title"                  // название документа (String)
     *      "date_time_start"                 // дата начала действия документа (String)
     *      "date_time_end"                   // дата окончания действия документа (String)
     *      "signed_document_text"            // подписанные данные в виде текста (String)
     *      "signed_data"                     // подписанные данные в виде массива байт (Binary)
     *      "signature"                       // значение подписи в виде массива байт (Binary)
     *
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "document_id"                     // ключ документа
     *      "document_with_ecp_id",           // ключ единицы изменерния
     *      "worker_id"                       // id сотрудника, создавшего документ (String)
     *      "document_title"                  // название документа (String)
     *      "date_time_start"                 // дата начала действия документа (String)
     *      "date_time_end"                   // дата окончания действия документа (String)
     *      "signed_document_text"            // подписанные данные в виде текста (String)
     *      "signed_data"                     // подписанные данные в виде массива байт (Binary)
     *      "signature"                       // значение подписи в виде массива байт (Binary)
     * }
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\OrderSystem&method=SaveDocumentWithEcp&subscribe=&data={}
     */
    public static function SaveDocumentWithEcp($data_post = NULL)
    {
        $log = new LogAmicumFront("SaveDocumentWithEcp");

        $result = [
            'document_id' => null,
            'document_with_ecp_id' => null,
            'worker_id' => null,
            'document_title' => null,
            'date_time_start' => null,
            'date_time_end' => null,
            'signed_document_text' => null,
            'signed_data' => null,
            'signature' => null
        ];

        try {

            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'document_id') || $post->document_id == '' ||
                !property_exists($post, 'document_with_ecp_id') || $post->document_with_ecp_id == '' ||
                !property_exists($post, 'worker_id') || $post->worker_id == '' ||
                !property_exists($post, 'document_title') || $post->document_title == '' ||
                !property_exists($post, 'date_time_start') || $post->date_time_start == '' ||
                !property_exists($post, 'date_time_end') || $post->date_time_end == '' ||
                !property_exists($post, 'signed_document_text') || $post->signed_document_text == '' ||
                !property_exists($post, 'signed_data') || $post->signed_data == '' ||
                !property_exists($post, 'signature') || $post->signature == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $document_id = $post->document_id;
            $document_with_ecp_id = $post->document_with_ecp_id;
            $worker_id = $post->worker_id;
            $document_title = $post->document_title;
            $date_time_start = $post->date_time_start;
            $date_time_end = $post->date_time_end;
            $signed_document_text = $post->signed_document_text;
            $signed_data = $post->signed_data;
            $signature = $post->signature;

            $document = Document::findOne(['id' => $document_id]);

            if (!$document) {
                $document = new Document();
            }
            $document->title = $document_title;
            $document->date_start = $date_time_start;
            $document->date_end = $date_time_end;
            $document->status_id = self::STATUS_ACTUAL;
            $document->vid_document_id = 22;
            $document->jsondoc = $signed_document_text;
            $document->date_time_sync = Assistant::GetDateTimeNow();
            $document->worker_id = $worker_id;
            if (!$document->save()) {
                $log->addData($document->errors, '$document->errors', __LINE__);
                throw new Exception("Ошибка сохранения модели Document");
            }
            $document->refresh();

            $document_id = $document->id;
            $result['document_id'] = $document_id;
            $result['document_title'] = $document_title;
            $result['worker_id'] = $worker_id;
            $result['date_time_start'] = $date_time_start;
            $result['date_time_end'] = $date_time_end;

            $document_with_ecp = DocumentWithEcp::findOne(['id' => $document_with_ecp_id]);

            if (!$document_with_ecp) {
                $document_with_ecp = DocumentWithEcp::findOne(['document_id' => $document_id]);
                if (!$document_with_ecp) {
                    $document_with_ecp = new DocumentWithEcp();
                }
            }

            $document_with_ecp->document_id = $document_id;
            $document_with_ecp->signed_data = implode(' ', $signed_data);
            $document_with_ecp->signature = implode(' ', $signature);
            if (!$document_with_ecp->save()) {
                $log->addData($document_with_ecp->errors, '$document_with_ecp->errors', __LINE__);
                throw new Exception("Ошибка сохранения модели DocumentWithEcp");
            }
            $document_with_ecp->refresh();

            $result['document_with_ecp_id'] = $document_with_ecp->id;
            $result['signed_data'] = $document_with_ecp->signed_data;
            $result['signature'] = $document_with_ecp->signature;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод GetDocumentWithEcp() - Получение ЭЦП документа по document_id или document_with_ecp_id
     * @param null $data_post
     * @return array
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     *      "document_id"                     // ключ документа
     *      "document_with_ecp_id",           // ключ единицы изменерния
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "document_id"                     // ключ документа
     *      "document_with_ecp_id",           // ключ единицы изменерния
     *      "worker_id"                       // id сотрудника, создавшего документ (String)
     *      "document_title"                  // название документа (String)
     *      "date_time_start"                 // дата начала действия документа (String)
     *      "date_time_end"                   // дата окончания действия документа (String)
     *      "signed_document_text"            // подписанные данные в виде текста (String)
     *      "signed_data"                     // подписанные данные в виде массива байт (Binary)
     *      "signature"                       // значение подписи в виде массива байт (Binary)
     * }
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\OrderSystem&method=GetDocumentWithEcp&subscribe=&data={}
     */
    public static function GetDocumentWithEcp($data_post = NULL)
    {
        $log = new LogAmicumFront("GetDocumentWithEcp");

        $result = [
            'document_id' => null,
            'document_with_ecp_id' => null,
            'worker_id' => null,
            'document_title' => null,
            'date_time_start' => null,
            'date_time_end' => null,
            'signed_document_text' => null,
            'signed_data' => null,
            'signature' => null
        ];

        try {

            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'document_id') || $post->document_id == '' ||
                !property_exists($post, 'document_with_ecp_id') || $post->document_with_ecp_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $document_id = $post->document_id;
            $document_with_ecp_id = $post->document_with_ecp_id;

            if ($document_id > 0) {
                $sql_filter['document_id'] = $document_id;
            }
            if ($document_with_ecp_id > 0) {
                $sql_filter['document_with_ecp_id'] = document_with_ecp_id;
            }

            $document_with_ecp = (new Query())
                ->select([
                    'document.id AS document_id',
                    'document_with_ecp.id AS document_with_ecp_id',
                    'worker_id',
                    'title AS document_title',
                    'date_start AS date_time_start',
                    'date_end AS date_time_end',
                    'jsondoc AS signed_document_text',
                    'signed_data',
                    'signature'
                ])
                ->from('document_with_ecp')
                ->leftJoin('document', 'document.id = document_with_ecp.document_id')
                ->where($sql_filter)
                ->one();
            if (!$document_with_ecp) {
                throw new Exception("Не удалось найти DocumentWithEcp по id = $document_with_ecp_id или document_id = $document_id");
            }
            $document_with_ecp['signed_data'] = explode(' ', $document_with_ecp['signed_data']);
            $document_with_ecp['signature'] = explode(' ', $document_with_ecp['signature']);

            $result = $document_with_ecp;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}

