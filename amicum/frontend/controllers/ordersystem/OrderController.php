<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\ordersystem;

use backend\controllers\Assistant as BackendAssistant;
use Complex\Complex;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\ChatController;
use frontend\controllers\handbooks\InjunctionController;
use frontend\controllers\industrial_safety\CheckingController;
use frontend\controllers\notification\NotificationController;
use frontend\controllers\positioningsystem\RouteController;
use frontend\controllers\system\LogAmicumFront;
use frontend\controllers\WebsocketController;
use frontend\models\CompanyDepartment;
use frontend\models\OperationWorker;
use frontend\models\Order;
use frontend\models\OrderHistory;
use frontend\models\OrderInstructionPb;
use frontend\models\OrderItem;
use frontend\models\OrderItemEquipment;
use frontend\models\OrderItemGroup;
use frontend\models\OrderItemInjunction;
use frontend\models\OrderItemInstructionPb;
use frontend\models\OrderItemStatus;
use frontend\models\OrderItemWorker;
use frontend\models\OrderItemWorkerInstructionPb;
use frontend\models\OrderItemWorkerVgk;
use frontend\models\OrderJson;
use frontend\models\OrderOperation;
use frontend\models\OrderPlace;
use frontend\models\OrderPlaceReason;
use frontend\models\OrderRouteWorker;
use frontend\models\OrderStatus;
use frontend\models\OrderWorkerCoordinate;
use frontend\models\OrderWorkerVgk;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;

class OrderController extends Controller
{
    /** Карьер/рудник/разрез */
    // GetOrderPit                              - Метод получения наряда на разрез/карьер/рудник
    // GetLastOrderHistory                      - Метод получения последнего сохраненного наряда
    // SaveMainOrder                            - Метод сохранения главного наряда
    // SaveOrderPit                             - Метод сохранения наряда на разрез/карьер/рудник
    // SaveOrderInstructionPbs                  - Метод сохранения предсменных инструктажей
    // SaveOrderWorkerVGK                       - Метод сохранения работников ВГК по наряду
    // SaveOrderItemWorkerVGK                   - Метод сохранения работников ВГК по наряду атомарному
    // SaveHistoryOrder                         - Метод сохранения истории наряда
    // SaveOrderItemInstructionPbs              - Метод сохранения предсменных инструктажей атомарного наряда
    // SaveOrderItemWorkerInstructionPbs        - Метод сохранения предсменных инструктажей работников атомарного наряда
    // SaveOrderItemWorkers                     - Метод сохранения сведений о работниках атомарного наряда
    // SaveOrderItemEquipments                  - Метод сохранения сведений об оборудовании
    // SaveOrderItemInjunctions                 - Метод сохранения сведений о предписаниях
    // SaveOrderItems                           - Метод сохранения атомарных нарядов
    // GetEquipments                            - Метод получения списка оборудования за последние 7 дней
    // GetEquipmentFavorite                     - Метод получения списка избранного оборудования за последние 7 дней
    // GetOperationFavorite                     - Метод получения списка избранных операций за последние 7 дней
    // GetPlaceFavorite                         - Метод получения списка избранных мест за последние 7 дней
    // GetOrderItemGroups                       - Метод получения списка групп
    // GetWorkersInOrder                        - Метод получения работников получивших наряд в заданный период времени
    // GetWorkerOrders                          - Метод получения нарядов работников - конкретные дни, в которые работник получал наряды

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

    /**
     * SaveOrderWorkerVGK - метод сохранения работников ВГК по наряду
     * @param $order_id - ключ наряда
     * @param $worker_vgk - список ВГК на сохранение
     * @return array
     */
    public static function SaveOrderWorkerVGK($order_id, $worker_vgk): array
    {
        $log = new LogAmicumFront("SaveOrderWorkerVGK");

        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            OrderWorkerVgk::deleteAll(['order_id' => $order_id]);
            if (!empty($worker_vgk)) {
                foreach ($worker_vgk as $rescuires) {
                    if (property_exists($rescuires, 'vgk') and $rescuires->vgk != 0) {
                        $vgk = 1;
                    } else {
                        $vgk = 0;
                    }
                    $order_worker_vgk_insert[] = [$order_id, $rescuires->worker_id, $rescuires->worker_role, $vgk];
                }

                if (!empty($order_worker_vgk_insert)) {
                    Yii::$app->db->createCommand()
                        ->batchInsert('order_worker_vgk',
                            ['order_id', 'worker_id', 'role_id', 'vgk'], $order_worker_vgk_insert)
                        ->execute();
                }
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SaveOrderPit - Метод сохранения наряда на разрез/карьер/рудник
     * входные параметры:
     *      company_department_id                    - ключ подразделения
     *      department_type_id                       - ключ типа подразделения
     *      shift_id                                 - ключ смены
     *      order_date_time                          - дата наряда
     *      mine_id                                  - ключ шахты
     *      order_id                                 - ключ наряда
     *      order_title                              - название наряда
     *      order_history_id                         - ключ истории наряда
     *      routes                                   - маршруты следования к месту ведения работ
     *      order_statuses                           - история изменения наряда
     *      order_accept_workers                     - согласовавшие/утвердившие/создавшие
     *      order_status_id                          - последний статус наряда
     *      worker_value_outgoing                    - количество людей вышедших из шахты
     *      brigadeChaneWorker                       - последний сохраненный наряд в формате json
     *      order_instruction_pbs                    - список инструктажей сделанных по наряду
     *      all_rescuers                             - члены ВГК все
     *      worker_vgk                               - члены ВГК на смене
     *      list_workers_by_graphic                  - список людей по плановому графику выходов
     *      list_brigade                             - список бригад и звеньев из графика выходов
     *      order_places                             - наряд построенный из шаблонов
     *      restriction_workers                      - список ограничения людей по наряду
     *      favourites_briefing_list                 - список избранных инструктажей
     *      favourites_route_template_list           - список избранных маршрутов инструктажей
     *      favourites_place_list                    - список избранных мест
     *      injunctions                              - список предписаний на запрашиваемую дату
     *      equipments                               - список оборудования за последние 7 лет
     *      orders                                   - список атомарных нарядов
     *      order_item_instruction_pbs               - список инструктажей атомарных нарядов
     *      order_item_worker_instruction_pbs        - список персональных инструктажей атомарных нарядов
     *      order_item_workers                       - список работников по наряду и ограничений
     *      order_item_equipments                    - список оборудования по наряду и ограничений
     *      order_item_injunctions                   - список предписаний в наряде
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\Order&method=SaveOrderPit&subscribe=&data={%22company_department_id%22:4029938,%22date_time%22:%222020-03-22%22,%22shift_id%22:2,%22mine_id%22:1,%22order_history_id%22:1}
     */
    public static function SaveOrderPit($data_post = NULL): array
    {
        $log = new LogAmicumFront("SaveOrderPit");

        $result = null;

        try {

            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

//            $response = self::GetOrderPit(json_encode(array('company_department_id' => $post->company_department_id, 'date_time' => $post->date_time, 'mine_id' => $post->mine_id, 'order_history_id' => $post->order_history_id, 'shift_id' => $post->shift_id,)));
//            $log->addLogAll($response);
//            if (!$response['status']) {
//                throw new Exception('Ошибка получения наряда');
//            }
//            $post = json_decode(json_encode($response['Items']));

            if (!property_exists($post, 'company_department_id') ||
                !property_exists($post, 'department_type_id') ||
                !property_exists($post, 'shift_id') ||
                !property_exists($post, 'order_date_time') ||
                !property_exists($post, 'mine_id') ||
                !property_exists($post, 'order_id') ||
                !property_exists($post, 'order_title') ||
                !property_exists($post, 'order_history_id') ||
                !property_exists($post, 'routes') ||
                !property_exists($post, 'order_statuses') ||
                !property_exists($post, 'order_accept_workers') ||
                !property_exists($post, 'order_status_id') ||
                !property_exists($post, 'worker_value_outgoing') ||
                !property_exists($post, 'brigadeChaneWorker') ||
                !property_exists($post, 'order_instruction_pbs') ||
                !property_exists($post, 'all_rescuers') ||
                !property_exists($post, 'worker_vgk') ||
                !property_exists($post, 'list_workers_by_graphic') ||
                !property_exists($post, 'list_brigade') ||
                !property_exists($post, 'order_places') ||
                !property_exists($post, 'restriction_workers') ||
                !property_exists($post, 'favourites_briefing_list') ||
                !property_exists($post, 'favourites_route_template_list') ||
                !property_exists($post, 'favourites_place_list') ||
                !property_exists($post, 'injunctions') ||
                !property_exists($post, 'equipments') ||
                !property_exists($post, 'orders') ||
                !property_exists($post, 'order_item_instruction_pbs') ||
                !property_exists($post, 'order_item_worker_instruction_pbs') ||
                !property_exists($post, 'order_item_workers') ||
                !property_exists($post, 'order_item_equipments') ||
                !property_exists($post, 'order_item_groups') ||
                !property_exists($post, 'order_item_injunctions')

            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");
            $date__time_now = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));

            $company_department_id = $post->company_department_id;
            $department_type_id = $post->department_type_id;
            $shift_id = $post->shift_id;
            $order_date_time = $post->order_date_time;
            $mine_id = $post->mine_id;
            $order_id = $post->order_id;
            $order_title = $post->order_title;
            $last_order_history_id = $post->order_history_id;
            $routes = $post->routes;
            $order_statuses = $post->order_statuses;
            $order_accept_workers = $post->order_accept_workers;
            $order_status_id = $post->order_status_id ? $post->order_status_id : self::ORDER_CREATED;
            $worker_value_outgoing = $post->worker_value_outgoing;
            $brigadeChaneWorker = $post->brigadeChaneWorker;
            $order_instruction_pbs = $post->order_instruction_pbs;
            $all_rescuers = $post->all_rescuers;
            $worker_vgk = $post->worker_vgk;
            $list_workers_by_graphic = $post->list_workers_by_graphic;
            $list_brigade = $post->list_brigade;
            $order_places = $post->order_places;
            $restriction_workers = $post->restriction_workers;
            $favourites_briefing_list = $post->favourites_briefing_list;
            $favourites_route_template_list = $post->favourites_route_template_list;
            $favourites_place_list = $post->favourites_place_list;
            $injunctions = $post->injunctions;
            $equipments = $post->equipments;
            $orders = $post->orders;
            $order_item_instruction_pbs = $post->order_item_instruction_pbs;
            $order_item_worker_instruction_pbs = $post->order_item_worker_instruction_pbs;
            $order_item_workers = $post->order_item_workers;
            $order_item_equipments = $post->order_item_equipments;
            $order_item_injunctions = $post->order_item_injunctions;
            $order_item_groups = $post->order_item_groups;

            /** БЛОК СОХРАНЕНИЯ ГРУПП НАРЯДА БЕЗ ОПЕРАЦИЙ */
            $response = self::SaveOrderItemGroup($order_item_groups);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения групп наряда');
            }

            /** БЛОК СОХРАНЕНИЯ ГЛАВНОГО НАРЯДА */
            $response = self::SaveMainOrder($order_date_time, $date__time_now, $shift_id, $company_department_id, $mine_id, $order_status_id, (int)$worker_value_outgoing, $data_post, $order_title);
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception('Ошибка сохранения главного наряда');
            }
            $order_id = $response['Items'];

            /** БЛОК СОХРАНЕНИЯ МЕСТ И ОПЕРАЦИЙ ГЛАВНОГО НАРЯДА */
            $response = self::SaveOrderPlaceWithOperation($order_id, $order_places, $date__time_now);
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception('Ошибка сохранения мест и операций главного наряда');
            }


            /** БЛОК СОХРАНЕНИЯ ИСТОРИИ НАРЯДА */
            $response = self::SaveHistoryOrder($date__time_now, $order_id, $order_status_id);
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception('Ошибка сохранения истории наряда');
            }
            $order_history_id = $response['Items'];

            /** БЛОК СОХРАНЕНИЯ ПРЕДСМЕННЫХ ИНСТРУКТАЖЕЙ */
            $response = self::SaveOrderInstructionPbs($order_id, $order_instruction_pbs);
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception('Ошибка сохранения предсменных инструктажей');
            }

            /** БЛОК СОХРАНЕНИЯ ЧЛЕНОВ ВГК */
            $response = self::SaveOrderItemWorkerVGK($order_history_id, $worker_vgk);
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception('Ошибка сохранения работников ВГК по наряду');
            }

            /** БЛОК СОХРАНЕНИЯ МАРШРУТОВ К МЕСТУ РАБОТ */
            $response = RouteController::SaveRoute($routes);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения маршрутов');
            }

            /** БЛОК СОХРАНЕНИЯ ПРЕДСМЕННЫХ ИНСТРУКТАЖЕЙ АТАМАРНОГО НАРЯДА */
            $response = self::SaveOrderItemInstructionPbs($order_history_id, $order_item_instruction_pbs);
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception('Ошибка сохранения предсменных инструктажей атомарного наряда');
            }

            /** БЛОК СОХРАНЕНИЯ ПРЕДСМЕННЫХ ИНСТРУКТАЖЕЙ РАБОТНИКОВ АТАМАРНОГО НАРЯДА */
            $response = self::SaveOrderItemWorkerInstructionPbs($order_history_id, $order_item_worker_instruction_pbs);
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception('Ошибка сохранения предсменных инструктажей работников атомарного наряда');
            }

            /** БЛОК СОХРАНЕНИЯ СВЕДЕНИЙ О РАБОТНИКАХ АТОМАРНОГО НАРЯДА*/
            $response = self::SaveOrderItemWorkers($order_history_id, $order_item_workers);
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception('Ошибка сохранения сведений о работниках атомарного наряда');
            }

            /** БЛОК СОХРАНЕНИЯ СВЕДЕНИЙ ОБ ОБОРУДОВАНИИ АТОМАРНОГО НАРЯДА*/
            $response = self::SaveOrderItemEquipments($order_history_id, $order_item_equipments);
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception('Ошибка сохранения сведений об оборудовании атомарного наряда');
            }

            /** БЛОК СОХРАНЕНИЯ СВЕДЕНИЙ О ПРЕДПИСАНИИ АТОМАРНОГО НАРЯДА*/
            $response = self::SaveOrderItemInjunctions($order_history_id, $order_item_injunctions);
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception('Ошибка сохранения сведений о предписаниях атомарного наряда');
            }
            $log->addLog("Сохранил предписания наряда");

            /** БЛОК СОХРАНЕНИЯ АТОМАРНЫХ НАРЯДОВ*/
            $response = self::SaveOrderItems($order_history_id, $orders);
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception('Ошибка сохранения атомарных нарядов');
            }
            $workers_for_restriction = $response['workers_for_restriction'];

            $log->addLog("Сохранил атомарные наряды");

            if (!empty($injunctions)) {
                $response = InjunctionController::SaveStatusInjunctionFromOrder($injunctions, $date__time_now);
                $log->addLogAll($response);
                if (!$response['status']) {
                    throw new Exception('Ошибка сохранения статусов предписаний');
                }
            }

            $log->addLog("Сохранил статусы предписаний");

            /** БЛОК СОХРАНЕНИЯ ОГРАНИЧЕНИЯ ПО НАРЯДАМ */
            if (isset($workers_for_restriction) && !empty($workers_for_restriction)) {
                $json_to_restrict = json_encode(array(
                    'workers' => $workers_for_restriction,
                    'date_time' => $order_date_time,
                    'company_department_id' => $company_department_id,
                    'order_id' => $order_id,
                    'shift_id' => $shift_id));
                $response = OrderSystemController::SaveRestrictionOrder($json_to_restrict);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка при выполнении метода сохранения ограничений по наряду');
                }
                unset($response);
            }

            $log->addLog("Сохранил ограничения по наряду");


            /** БЛОК ПОЛУЧЕНИЯ СОХРАНЕННОГО НАРЯДА */
            $response = self::GetOrderPit(json_encode(array('company_department_id' => $company_department_id, 'date_time' => $order_date_time, 'mine_id' => $mine_id, 'order_history_id' => $order_history_id, 'shift_id' => $shift_id,)));
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception('Ошибка получения наряда');
            }
            $result = $response['Items'];

            $log->addLog("Получил сохраненный наряд");

            $response = WebsocketController::SendMessageToWebSocket('orderSystem',
                array(
                    'type' => 'SaveOrderPit',
                    'message' => $result
                )
            );
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка отправки данных на вебсокет (SaveOrderPit)');
            }

            $log->addLog("Отправил наряд на вебсокеты");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetOrderPit - Метод получения наряда на разрез/карьер/рудник
     * входные параметры:
     *      company_department_id - ключ конкретного подразделения
     *      date_time - дата наряда (производственная)
     *      mine_id - ключ шахты
     *      order_history_id - ключ истории наряда (если не задан, то искать последний наряд)
     *      shift_id - ключ смены
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\Order&method=GetOrderPit&subscribe=&data={%22company_department_id%22:4029938,%22date_time%22:%222020-03-22%22,%22shift_id%22:2,%22mine_id%22:1,%22order_history_id%22:1}
     */
    public static function GetOrderPit($data_post = NULL): array
    {
        $log = new LogAmicumFront("GetOrderPit");

        $result = null;

        try {

            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (!property_exists($post, 'company_department_id') ||
                !property_exists($post, 'date_time') ||
                !property_exists($post, 'mine_id') ||
                !property_exists($post, 'order_history_id') ||
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
            $shift_id = $post->shift_id;
            $date_time = $post->date_time;
            $mine_id = $post->mine_id;
            $order_history_id = $post->order_history_id;
            $workers_for_restriction = [];                                                                              // список всех работников для получения ограничений по наряду

            $input_filter = array(
                'date' => $date_time,
                'date_time' => $date_time,
                'company_department_id' => $company_department_id,
                'mine_id' => $mine_id,
                'shift_id' => $shift_id
            );

            $order_result = array(
                'company_department_id' => $company_department_id,                                                      // ключ подразделения
                'department_type_id' => null,                                                                           // ключ типа подразделения
                'shift_id' => $shift_id,                                                                                // ключ смены
                'order_date_time' => $date_time,                                                                        // дата наряда
                'mine_id' => $mine_id,                                                                                  // ключ шахты
                'order_id' => -1,                                                                                       // ключ наряда
                'order_title' => "",                                                                                    // название наряда
                'order_history_id' => -1,                                                                               // ключ истории наряда
                'order_histories' => null,                                                                              // история изменения наряда
                'routes' => null,                                                                                       // маршруты следования к месту ведения работ
                'order_statuses' => null,                                                                               // история изменения наряда
                'order_accept_workers' => null,                                                                         // согласовавшие/утвердившие/создавшие
                'order_status_id' => null,                                                                              // последний статус наряда
                'worker_value_outgoing' => 0,                                                                           // количество людей вышедших из шахты
                'brigadeChaneWorker' => "",                                                                             // последний сохраненный наряд в формате json
                'order_instruction_pbs' => null,                                                                        // список инструктажей сделанных по наряду
                'all_rescuers' => [],                                                                                   // члены ВГК все
                'worker_vgk' => [],                                                                                     // члены ВГК на смене
                'list_workers_by_graphic' => (object)array(),                                                           // список людей по плановому графику выходов
                'list_brigade' => (object)array(),                                                                      // список бригад и звеньев из графика выходов
                'order_places' => null,                                                                                 // наряд построенный из шаблонов
                'restriction_workers' => null,                                                                          // список ограничения людей по наряду
                'favourites_briefing_list' => null,                                                                     // список избранных инструктажей
                'favourites_route_template_list' => null,                                                               // список избранных маршрутов инструктажей
                'favourites_place_list' => null,                                                                        // список избранных мест
                'injunctions' => null,                                                                                  // список предписаний на запрашиваемую дату
                'equipments' => null,                                                                                   // список оборудования за последние 7 дней
                'orders' => null,                                                                                       // список атомарных нарядов
                'order_item_instruction_pbs' => null,                                                                   // список инструктажей атомарных нарядов
                'order_item_worker_instruction_pbs' => null,                                                            // список персональных инструктажей атомарных нарядов
                'order_item_workers' => null,                                                                           // список работников по наряду и ограничений
                'order_item_equipments' => null,                                                                        // список оборудования по наряду и ограничений
                'order_item_injunctions' => null,                                                                       // список предписаний в наряде
                'order_item_groups' => null,                                                                            // справочник групп
            );

            /** БЛОК СПИСКА ЛЮДЕЙ ПО ПЛАНОВОМУ ГРАФИКУ ВЫХОДОВ */
            $response = OrderSystemController::GetListWorkersByLastGraficShift($company_department_id, $date_time, $shift_id, $mine_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка получения списка людей на смену на основе графика выходов");
            }
            $order_result['list_workers_by_graphic'] = $response['Items'];
            $workers_for_restriction = array_merge($workers_for_restriction, $response['workers']);
            unset($response);

            $log->addLog("Список людей на смену на основе графика выходов получен");

            /** БЛОК СПИСКА БРИГАД И ЗВЕНЬЕВ ИЗ ГРАФИКА ВЫХОДОВ */
            $response = WorkScheduleController::GetListBrigade(json_encode($input_filter));
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка получения списка бригад из графика выходов");
            }
            $order_result['list_brigade'] = $response['Items'];
            $workers_for_restriction = array_merge($workers_for_restriction, $response['workers']);
            unset($response);

            /** БЛОК ПОЛУЧЕНИЯ ИЗБРАННЫХ МЕСТ */
            $response = OrderSystemController::GetFavouritesPlace($company_department_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка получения избранных мест");
            }
            $order_result['favourites_place_list'] = $response['Items'];
            unset($response);

            /** БЛОК ПОЛУЧЕНИЯ ИЗБРАННЫХ ШАБЛОНОВ МАРШРУТОВ */
            $response = OrderSystemController::GetFavouritesRouteTemplate($company_department_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка получения избранных шаблонов маршрутов");
            }
            $order_result['favourites_route_template_list'] = $response['Items'];
            unset($response);

            /** БЛОК ПОЛУЧЕНИЯ ИЗБРАННЫХ ИНСТРУКТАЖЕЙ */
            $response = OrderSystemController::GetFavouritesBriefing(json_encode($input_filter));
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка получения избранных инструктажей");
            }
            $order_result['favourites_briefing_list'] = $response['Items'];
            unset($response);

            $log->addLog("Избранные получены");


            /** БЛОК ОБОРУДОВАНИЯ ЗА ПОСЛЕДНИЕ 7 ДНЕЙ */
            $response = self::GetEquipments($company_department_id, $mine_id, $date_time);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка получения списка оборудования");
            }
            $order_result['equipments'] = $response['Items'];
            unset($response);

            $order_result['department_type_id'] = null;
            $company_department = CompanyDepartment::findOne(['id' => $company_department_id]);
            if ($company_department) {
                $order_result['department_type_id'] = $company_department->department_type_id;
            }
            unset($company_department);

            /** БЛОК СПРАВОЧНИК ГРУПП */
            $response = self::GetOrderItemGroups($company_department_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка получения списка групп");
            }
            $order_result['order_item_groups'] = $response['Items'];
            unset($response);

            $order = Order::find()
                ->joinWith('orderJson')
                ->joinWith('orderInstructionPbs.instructionPb')
                ->joinWith('orderStatuses')
                ->where([
                    'order.company_department_id' => $company_department_id,
                    'shift_id' => $shift_id,
                    'order.mine_id' => $mine_id,
                    'order.date_time_create' => $date_time
                ])
                ->one();

            /** БЛОК ПОЛУЧЕНИЯ НАРЯДА */
            if ($order) {
                $order_id = $order->id;

                if (!$order_history_id) {
                    $log->addData($order_id, '$order_id', __LINE__);
                    $response = self::GetLastOrderHistory($order_id);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception("Ошибка нахождения последнего сохраненного наряда");
                    }
                    $log->addData($response['Items'], '$response[Items]', __LINE__);
                    $order_history_id = $response['order_history_id'];
                    unset($response);
                }

                $order_result['order_id'] = $order->id;
                $order_result['order_title'] = $order->title;
                $order_result['order_status_id'] = $order->status_id;
                $order_result['worker_value_outgoing'] = $order->worker_value_outgoing;

                if (isset($order->orderJson)) {
                    $order_result['brigadeChaneWorker'] = $order->orderJson->brigadeChaneWorker;
                } else {
                    $order_result['brigadeChaneWorker'] = null;
                }


                /** БЛОК С ИНСТРУКТАЖАМИ НАРЯДА */
                foreach ($order->orderInstructionPbs as $order_instruction_pb) {
                    $order_result['order_instruction_pbs'][$order_instruction_pb->id]['order_instruction_id'] = $order_instruction_pb->id;
                    $order_result['order_instruction_pbs'][$order_instruction_pb->id]['instruction_pb_id'] = $order_instruction_pb->instruction_pb_id;
                    $order_result['order_instruction_pbs'][$order_instruction_pb->id]['title'] = $order_instruction_pb->instructionPb->title;
                }

                /** БЛОК СО СТАТУСАМИ НАРЯДА */
                foreach ($order->orderStatuses as $order_status) {
                    $order_result['order_statuses'][$order_status->id]['order_status_id'] = $order_status->id;
                    $order_result['order_statuses'][$order_status->id]['status_id'] = $order_status->status_id;
                    $order_result['order_statuses'][$order_status->id]['worker_id'] = $order_status->worker_id;
                    $order_result['order_statuses'][$order_status->id]['date_time_create'] = $order_status->date_time_create;
                    $order_result['order_statuses'][$order_status->id]['date_time_create_formatted'] = date("Y-m-d  H:i", strtotime($order_status->date_time_create));

                    // получаем последних людей по статусам (утвержденные, согласованные/создавшие)
                    $order_result['order_accept_workers'][$order_status->status_id]['status_id'] = $order_status->status_id;
                    $order_result['order_accept_workers'][$order_status->status_id]['worker_id'] = $order_status->worker_id;
                    $order_result['order_accept_workers'][$order_status->status_id]['date_time_create'] = $order_status->date_time_create;
                    $order_result['order_accept_workers'][$order_status->status_id]['date_time_create_formatted'] = date("Y-m-d  H:i", strtotime($order_status->date_time_create));
                }

                /** БЛОК ПОЛУЧЕНИЯ МАРШРУТОВ ДЛЯ СЛЕДОВАНИЯ К МЕСТУ ВЕДЕНИЯ РАБОТ */
                $response = RouteController::GetRouteData(json_encode(array('order_id' => $order->id)));
                $log->addLogAll($response);
                if ($response['status'] == 1) {
                    $order_result['routes'] = $response['Items']['routes_by_route_id'];
                }

                unset($order);

                /** БЛОК ПОЛУЧЕНИЯ НАРЯДА НА ПРОИЗВОДСТВО РАБОТ БЕЗ ПРИВЯЗКИ К ЛЮДЯМ */
                $order = Order::find()
                    ->joinWith('orderPlaces.place')
                    ->joinWith('orderPlaces.placeTo')
                    ->joinWith('orderPlaces.placeFrom')
                    ->joinWith('orderPlaces.passport.passportAttachments.attachment')
                    ->joinWith('orderPlaces.orderOperations.operation.operationGroups')
                    ->joinWith('orderPlaces.orderOperations.equipment')
                    ->where(['order.id' => $order_id])
                    ->one();

                foreach ($order->orderPlaces as $order_place) {
                    $order_place_id = $order_place->id;
                    $order_result['order_places'][$order_place_id]['order_place_id'] = $order_place_id;
                    $order_result['order_places'][$order_place_id]['place_id'] = $order_place->place_id;
                    $order_result['order_places'][$order_place_id]['place_title'] = $order_place->place->title;

                    if (isset($order_place->placeTo)) {
                        $order_result['order_places'][$order_place_id]['place_to_id'] = $order_place->place_to_id;
                        $order_result['order_places'][$order_place_id]['place_to_title'] = $order_place->placeTo->title;
                    } else {
                        $order_result['order_places'][$order_place_id]['place_to_id'] = null;
                        $order_result['order_places'][$order_place_id]['place_to_title'] = "";
                    }

                    if (isset($order_place->placeFrom)) {
                        $order_result['order_places'][$order_place_id]['place_from_id'] = $order_place->place_from_id;
                        $order_result['order_places'][$order_place_id]['place_from_title'] = $order_place->placeFrom->title;
                    } else {
                        $order_result['order_places'][$order_place_id]['place_from_id'] = null;
                        $order_result['order_places'][$order_place_id]['place_from_title'] = "";
                    }

                    if ($order_place->edge_id) {
                        $order_result['order_places'][$order_place_id]['edge_id'] = $order_place->edge_id;
                    } else {
                        $order_result['order_places'][$order_place_id]['edge_id'] = 0;
                    }
                    $order_result['order_places'][$order_place_id]['passport_id'] = $order_place->passport_id;
                    $order_result['order_places'][$order_place_id]['passport_attachments'] = array();
                    if (isset($order_place->passport) and isset($order_place->passport->passportAttachments)) {
                        foreach ($order_place->passport->passportAttachments as $passport_attachment) {
                            if ($passport_attachment->attachment) {
                                $order_result['order_places'][$order_place_id]['passport_attachments'][] = array(
                                    'passport_attachment_id' => $passport_attachment->id,
                                    'attachment_path' => $passport_attachment->attachment->path,
                                    'attachment_title' => $passport_attachment->attachment->title,
                                );
                            }
                        }
                    }
                    $order_result['order_places'][$order_place_id]['route_template_id'] = $order_place->route_template_id;
                    $order_result['order_places'][$order_place_id]['coordinate'] = $order_place->coordinate;
                    $order_result['order_places'][$order_place_id]['description'] = $order_place->description;
                    $order_result['order_places'][$order_place_id]['reason'] = "";
                    $order_result['order_places'][$order_place_id]['route_worker'] = array();
                    $order_result['order_places'][$order_place_id]['operation_production'] = null;

                    /**
                     * БЛОК ЗАПОЛНЕНИЯ ОПЕРАЦИЙ
                     */
                    foreach ($order_place->orderOperations as $order_operation) {

                        $order_operation_id = $order_operation->id;
                        $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['unit_id'] = $order_operation->operation->unit_id;
                        $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['operation_id'] = $order_operation->operation_id;
                        $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['operation_title'] = $order_operation->operation->title;
                        $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['equipment_id'] = $order_operation->equipment_id;
                        $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['equipment_title'] = $order_operation->equipment->title;
                        if ($order_operation->edge_id) {
                            $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['edge_id'] = $order_operation->edge_id;
                        } else {
                            $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['edge_id'] = 0;
                        }
                        $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['coordinate'] = $order_operation->coordinate;
                        $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['order_operation_id'] = $order_operation->id;
                        $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['order_place_id'] = $order_place_id;
                        /**
                         * Блок заполнения групп операций - нужны для разделения операций по блокам - работы по линии АБ, работы ПК, работы по производству
                         */
                        $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['operation_groups'] = array();
                        foreach ($order_operation->operation->operationGroups as $operation_group) {
                            $operation_group_id = $operation_group->group_operation_id;
                            $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['operation_groups'][] = $operation_group_id;
                        }
                        $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['operation_value_plan'] = $order_operation->operation_value_plan;
                        $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['operation_value_fact'] = $order_operation->operation_value_fact;
                        $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['operation_load_value'] = $order_operation->operation->operation_load_value;
                        $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['order_operation_id_vtb'] = $order_operation->order_operation_id_vtb;
                        $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['correct_measures_id'] = $order_operation->correct_measures_id;
                        $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['injunction_violation_id'] = $order_operation->injunction_violation_id;
                        $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['injunction_id'] = $order_operation->injunction_id;
                        $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['order_place_id_vtb'] = $order_operation->order_place_id_vtb;
                        $order_result['order_places'][$order_place_id]['operation_production'][$order_operation_id]['description'] = $order_operation->description;
                    }
                }

                unset($order);

                /** БЛОК ПОЛУЧЕНИЯ ИСТОРИИ ИЗМЕНЕНИЯ НАРЯДА */
                $order_histories = OrderHistory::find()
                    ->joinWith('status')
                    ->joinWith('worker.employee')
                    ->where(['order_id' => $order_id])
                    ->orderBy(['date_time_create' => SORT_DESC])
                    ->asArray()
                    ->all();
                foreach ($order_histories as $order_history) {
                    $order_result['order_histories'][] = array(
                        "order_history_id" => $order_history['id'],
                        "date_time_create" => $order_history['date_time_create'],
                        "worker_id" => $order_history['worker_id'],
                        "worker_full_name" => Assistant::GetShortFullName($order_history['worker']['employee']['first_name'], $order_history['worker']['employee']['patronymic'], $order_history['worker']['employee']['last_name']),
                        "status_id" => $order_history['status_id'],
                        "status_title" => $order_history['status']['title'],
                    );
                }

                unset($order_histories);

                /** БЛОК ПОЛУЧЕНИЯ НАРЯДА ИЗ ИСТОРИИ */
                $order = OrderHistory::find()
                    ->joinWith('orderItemWorkerInstructionPbs.instructionPb')
                    ->joinWith('orderItemInstructionPbs.instructionPb')
                    ->joinWith('orderItemWorkerVgks.worker.employee')
                    ->joinWith('orderItemWorkerVgks.role')
                    ->joinWith('orderItemWorkers.role')
                    ->joinWith('orderItemEquipments.equipment')
                    ->joinWith('orderItemEquipments.status')
                    ->joinWith('orderItemInjunctions.status')
                    ->joinWith('orderItems.orderItemStatuses')
                    ->joinWith('orderItems.group')
                    ->where(['order_history.id' => $order_history_id])
                    ->one();

                if ($order) {
                    $order_result['order_history_id'] = $order_history_id;
                }

                /** БЛОК ЧЛЕНОВ ВГК В НАРЯДЕ */
                foreach ($order->orderItemWorkerVgks as $rescuire) {
                    $workers_vgk[$rescuire->worker_id]['worker_id'] = $rescuire->worker_id;
                    $workers_vgk[$rescuire->worker_id]['worker_role'] = $rescuire->role_id;
                    $workers_vgk[$rescuire->worker_id]['worker_role_title'] = $rescuire->role->title;
                    $workers_vgk[$rescuire->worker_id]['worker_full_name'] = Assistant::GetFullName($rescuire->worker->employee->first_name, $rescuire->worker->employee->patronymic, $rescuire->worker->employee->last_name);
                    $workers_vgk[$rescuire->worker_id]['vgk'] = $rescuire->vgk;
                }

                /** БЛОК С ИНСТРУКТАЖАМИ НАРЯДА */
                foreach ($order->orderItemInstructionPbs as $order_instruction_pb) {
                    $order_result['order_item_instruction_pbs'][$order_instruction_pb->id] = array(
                        'order_item_instruction_pb_id' => $order_instruction_pb->id,
                        'instruction_pb' => array(
                            'instruction_pb_id' => $order_instruction_pb->instruction_pb_id,
                            'instruction_pb_title' => $order_instruction_pb->instructionPb->title,
                        )
                    );
                }

                /** БЛОК С ПЕРСОНАЛЬНЫМИ ИНСТРУКТАЖАМИ НАРЯДА */
                foreach ($order->orderItemWorkerInstructionPbs as $order_instruction_pb) {
                    $order_result['order_item_worker_instruction_pbs'][$order_instruction_pb->id] = array(
                        'order_item_worker_instruction_pb_id' => $order_instruction_pb->id,
                        'worker_id' => $order_instruction_pb->worker_id,
                        'instruction_pb' => array(
                            'instruction_pb_id' => $order_instruction_pb->instruction_pb_id,
                            'instruction_pb_title' => $order_instruction_pb->instructionPb->title,
                        )
                    );
                }

                /** БЛОК СВЕДЕНИЯ О РАБОТНИКЕ */
                foreach ($order->orderItemWorkers as $order_worker) {
                    $order_result['order_item_workers'][$order_worker->worker_id] = array(
                        'order_item_worker_id' => $order_worker->id,
                        'worker_id' => $order_worker->worker_id,
                        'role' => array(
                            'role_id' => $order_worker->role_id,
                            'role_title' => $order_worker->role->title,
                        ),
                        'worker_restriction_json' => $order_worker->worker_restriction_json,
                        'workers_json' => $order_worker->workers_json,
                        'reason' => array(
                            'reason_status_id' => $order_worker->reason_status_id,
                            'reason_description' => $order_worker->reason_description,
                        ),
                    );
                }

                /** БЛОК СВЕДЕНИЯ ОБ ОБОРУДОВАНИИ */
                foreach ($order->orderItemEquipments as $order_equipment) {
                    $order_result['order_item_equipments'][$order_equipment->id] = array(
                        'order_item_equipment_id' => $order_equipment->id,
                        'equipment' => array(
                            'equipment_id' => $order_equipment->equipment_id,
                            'equipment_title' => $order_equipment->equipment->title,
                            'inventory_number' => $order_equipment->equipment->inventory_number,
                        ),
                        'status' => array(
                            'status_id' => $order_equipment->status_id,
                            'status_title' => $order_equipment->status->title,
                        ),
                        'equipments_json' => $order_equipment->equipments_json,
                    );
                }

                /** БЛОК СВЕДЕНИЯ ПРЕДПИСАНИЯХ */
                foreach ($order->orderItemInjunctions as $order_injunction) {
                    $order_result['order_item_injunctions'][$order_injunction->id] = array(
                        'order_item_injunction_id' => $order_injunction->id,
                        'injunction_id' => $order_injunction->injunction_id,
                        'status' => array(
                            'status_id' => $order_injunction->status_id,
                            'status_title' => $order_injunction->status->title,
                        ),
                        'injunctions_json' => $order_injunction->injunctions_json,
                    );
                }

                $injunction_handbook = [];
                /** БЛОК АТОМАРНЫХ НАРЯДОВ */
                foreach ($order->orderItems as $order_item) {

                    if ($order_item->injunction_id and !isset($injunction_handbook[$order_item->injunction_id])) {
                        $response = CheckingController::GetInfoAboutInjunction(json_encode(array('injunction_id' => $order_item->injunction_id)));
                        $log->addLogAll($response);
                        if ($response['status'] != 1) {
                            throw new Exception("Ошибка получения конкретного предписания");
                        }
                        $injunction = $response['Items'];
                        $injunction_handbook[$order_item->injunction_id] = $injunction;
                    }

                    $order_item_statuses = [];

                    foreach ($order_item->orderItemStatuses as $order_item_status) {
                        $order_item_statuses[] = array(
                            'worker_id' => $order_item_status->worker_id,
                            'status_id' => $order_item_status->status_id,
                            'date_time_create' => $order_item_status->date_time_create,
                            'description' => $order_item_status->description,
                            'order_item_status_id' => $order_item_status->id,
                        );
                    }


                    $order_result['orders'][$order_item->id] = array(
                        'order_item_id' => $order_item->id,
                        'worker' => (!$order_item->worker_id ? (object)array() : array(
                            'worker_id' => $order_item->worker_id,
                            'fullName' => Assistant::GetFullName(
                                $order_item->worker->employee->first_name,
                                $order_item->worker->employee->patronymic,
                                $order_item->worker->employee->last_name
                            ),
                            'workerTabelNumber' => $order_item->worker->tabel_number,
                        )),
                        'equipment' => (!$order_item->equipment_id ? (object)array() : array(
                            'equipment_id' => $order_item->equipment_id,
                            'equipment_title' => $order_item->equipment->title,
                            'inventory_number' => $order_item->equipment->inventory_number,
                        )),
                        'operation' => (!$order_item->operation_id ? (object)array() : array(
                            'operation_id' => $order_item->operation_id,
                            'operation_title' => $order_item->operation->title,
                        )),
                        'place_from' => (!$order_item->place_from_id ? (object)array() : array(
                            'place_id' => $order_item->place_from_id,
                            'place_title' => $order_item->placeFrom->title,
                        )),
                        'place_to' => (!$order_item->place_to_id ? (object)array() : array(
                            'place_id' => $order_item->place_to_id,
                            'place_title' => $order_item->placeTo->title,
                        )),
                        'group_order_id' => $order_item->group_order_id,
                        'plan' => $order_item->plan,
                        'fact' => $order_item->fact,
                        'description' => $order_item->description,
                        'group' => (!$order_item->group_id ? (object)array() : array(
                            'group_id' => $order_item->group_id,
                            'group_title' => $order_item->group->title,
                        )),
                        'chane' => (!$order_item->chane_id ? (object)array() : array(
                            'chane_id' => $order_item->chane_id,
                            'chane_title' => $order_item->chane->title,
                        )),
                        'brigade' => (!$order_item->brigade_id ? (object)array() : array(
                            'brigade_id' => $order_item->brigade_id,
                            'brigade_title' => $order_item->brigade->description,
                        )),
                        'status' => (!$order_item->status_id ? (object)array() : array(
                            'status_id' => $order_item->status_id,
                            'status_title' => $order_item->status->title,
                        )),
                        'order_operation_id_vtb' => $order_item->order_operation_id_vtb,
                        'correct_measures_id' => $order_item->correct_measures_id,
                        'order_place_id_vtb' => $order_item->order_place_id_vtb,
                        'injunction_violation_id' => $order_item->injunction_violation_id,
                        'injunction' => (($order_item->injunction_id and isset($injunction_handbook[$order_item->injunction_id])) ? $injunction_handbook[$order_item->injunction_id] : (object)array()),
                        'equipment_status' => (!$order_item->equipment_status_id ? (object)array() : array(
                            'status_id' => $order_item->equipment_status_id,
                            'status_title' => $order_item->equipmentStatus->title,
                        )),
                        'role' => (!$order_item->role_id ? (object)array() : array(
                            'role_id' => $order_item->role_id,
                            'role_title' => $order_item->role->title,
                        )),
                        'date_time_create' => $order_item->date_time_create,
                        'order_type_id' => $order_item->order_type_id,
                        'chat_room_id' => $order_item->chat_room_id,
                        'passport_id' => $order_item->passport_id,
                        'route_template_id' => $order_item->route_template_id,
                        'order_route_json' => $order_item->order_route_json,
                        'order_route_esp_json' => $order_item->order_route_esp_json,
                        'order_item_statuses' => $order_item_statuses,
                    );
                }

            }


            /** БЛОК ПОЛУЧЕНИЯ ЧЛЕНОВ ВГК ИЗ ГРАФИКА */
            $filtered_rescuers = [];

            $response = OrderSystemController::GetWorkersVgk(json_encode($input_filter));                                            // получаем всех ВГК с участка и ВГК на смене
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка получения шаблона людей ВГК");
            }
            if (!empty($response['Items'])) {
                $order_result['all_rescuers'] = $response['Items']['all_rescuers'];
                $filtered_rescuers = $response['Items']['filtered_rescuers'];
            }
            unset($response);

            if (isset($workers_vgk)) {                                                                              // если ВГК есть в наряде, то берем оттуда, иначе берем с шаблона - графика входов, а если и там нет, то пустой объект
                foreach ($workers_vgk as $worker_vgk) {
                    $order_result['worker_vgk'][] = $worker_vgk;
                }
            } else if (!$order) {
                $order_result['worker_vgk'] = $filtered_rescuers;
            }

            $response = Assistant::GetDateTimeByShift($date_time, $shift_id);
            $date_time_inj = $response['date_time_end'];

            $input_filter['date_time'] = $date_time_inj;
            $response = OrderSystemController::GetInjunctionsByDate(json_encode($input_filter));
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка получения предписаний");
            }
            if ($response['Items'] and isset($response['Items']['injunctions'])) {
                $order_result['injunctions'] = $response['Items']['injunctions'];
            }
            unset($response);


            $log->addLog("Предписания");

            /** БЛОК ПРОВЕРКИ ЛЮДЕЙ НА НАЛИЧИЕ ОГРАНИЧЕНИЙ */
            $json_to_restrict = json_encode(array('workers' => $workers_for_restriction, 'date_time' => $date_time, 'company_department_id' => $company_department_id));
            $response = NotificationController::CheckRestriction($json_to_restrict);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка получения ограничений по наряду");
            }
            $order_result['restriction_workers'] = $response['Items'];
            unset($response);


            $result = $order_result;
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetEquipments - Метод получения списка оборудования за последние 7 дней
     * @return array|object[]
     */
    private static function GetEquipments($company_department_id, $mine_id, $date_time_end): array
    {
        $log = new LogAmicumFront("GetEquipments", true);
        $result = null;

        try {
            $log->addLog("Начало метода");
            $date_time_start = date('Y-m-d', strtotime($date_time_end . '-7 days'));

//            $order_items = OrderItem::find()
//                ->innerJoinWith('orderHistory.order')
//                ->innerJoinWith('equipment')
//                ->innerJoinWith('equipmentStatus')
//                ->where(['order.company_department_id' => $company_department_id, 'order.mine_id' => $mine_id])
//                ->andWhere(['>=', 'order.date_time_create', $date_time_start])
//                ->andWhere(['<=', 'order.date_time_create', $date_time_end])
//                ->all();

            $order_items = (new Query)
                ->select([
                    'order_item.equipment_id as equipment_id',
                    'equipment.title as equipment_title',
                    'equipment.inventory_number as inventory_number',
                    'order_item.equipment_status_id as equipment_status_id',
                    'status.title as status_title',
                ])
                ->from('order_item')
                ->innerJoin('order_history', 'order_history.id=order_item.order_history_id')
                ->innerJoin('order', 'order.id=order_history.order_id')
                ->innerJoin('equipment', 'order_item.equipment_id=equipment.id')
                ->innerJoin('status', 'order_item.equipment_status_id=status.id')
                ->where(['order.company_department_id' => $company_department_id, 'order.mine_id' => $mine_id])
                ->andWhere(['>=', 'order.date_time_create', $date_time_start])
                ->andWhere(['<=', 'order.date_time_create', $date_time_end])
                ->all();


            foreach ($order_items as $order_item) {
                if ($order_item['equipment_id']) {
                    $result[$order_item['equipment_id']] = array(
                        'equipment' => array(
                            'equipment_id' => $order_item['equipment_id'],
                            'equipment_title' => $order_item['equipment_title'],
                            'inventory_number' => $order_item['inventory_number'],
                        ),
                        'equipment_status' => (!$order_item['equipment_status_id'] ? (object)array() : array(
                            'status_id' => $order_item['equipment_status_id'],
                            'status_title' => $order_item['status_title'],
                        )),
                    );
                }
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Конец метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetEquipmentFavorite - Метод получения списка избранного оборудования за последние 7 дней
     * Входные параметры:
     *      mine_id                 - ключ шахтного поля
     *      company_department_id   - ключ подразделения
     * Выходные параметры:
     *   group   - вложенный
     *      {object_type_id}    - ключ типа типового объекта
     *           object_type_id     - ключ типа типового объекта
     *           object_type_title  - название типа типового объекта
     *           objects            - список типовых объектов
     *               {object_id}        - ключ типового объекта
     *                   object_id          - ключ типового объекта
     *                   object_title       - название типового объекта
     *                   equipments         - список оборудования
     *                       {equipment_id}     - ключ оборудования
     *                           equipment_id           - ключ оборудования
     *                           equipment_title        - название оборудования
     *                           parent_equipment_id    - ключ родительского оборудования
     *                           inventory_number       - инвентарный номер оборудования
     *   list   - список
     *     {equipment_id}     - ключ оборудования
     *         equipment_id           - ключ оборудования
     *         equipment_title        - название оборудования
     *         parent_equipment_id    - ключ родительского оборудования
     *         inventory_number       - инвентарный номер оборудования
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\Order&method=GetEquipmentFavorite&subscribe=&data={%22company_department_id%22:60002522,%22mine_id%22:1}
     * @return array|object[]
     */
    public static function GetEquipmentFavorite($data_post = NULL): array
    {
        $log = new LogAmicumFront("GetEquipmentFavorite");

        $result = array();                                                                                              // Массив ошибок

        try {
            $log->addLog("Начал выполнение метода");

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
            $date_time_end = Assistant::GetDateTimeNow();
            $date_time_start = date('Y-m-d', strtotime($date_time_end . '-7 days'));

            $order_items = OrderItem::find()
                ->innerJoinWith('orderHistory.order')
                ->innerJoinWith('equipment.object.objectType')
                ->innerJoinWith('equipmentStatus')
                ->where(['order.company_department_id' => $company_department_id, 'order.mine_id' => $mine_id])
                ->andWhere(['>=', 'order.date_time_create', $date_time_start])
                ->andWhere(['<=', 'order.date_time_create', $date_time_end])
                ->all();

            foreach ($order_items as $order_item) {
                if ($order_item->equipment_id) {

                    $type_object_id = $order_item['equipment']['object']['objectType']['id'];
                    $object_id = $order_item['equipment']['object']['id'];
                    $equipment_id = $order_item['equipment']['id'];
                    if (!isset($result['group'][$type_object_id])) {
                        $result['group'][$type_object_id] = array(
                            'object_type_id' => $type_object_id,
                            'object_type_title' => $order_item['equipment']['object']['objectType']['title'],
                            'objects' => null,
                        );

                        if (!isset($result['group'][$type_object_id]['objects'][$object_id])) {
                            $result['group'][$type_object_id]['objects'][$object_id] = array(
                                'object_id' => $object_id,
                                'object_title' => $order_item['equipment']['object']['title'],
                                'equipments' => null,
                            );
                        }
                    }

                    $result['group'][$type_object_id]['objects'][$object_id]['equipments'][$equipment_id] = array(
                        'equipment_id' => $order_item->equipment_id,
                        'equipment_title' => $order_item->equipment->title,
                        'parent_equipment_id' => $order_item->equipment->parent_equipment_id,
                        'inventory_number' => $order_item->equipment->inventory_number,
                    );
                    $result['list'][$equipment_id] = array(
                        'equipment_id' => $order_item->equipment_id,
                        'equipment_title' => $order_item->equipment->title,
                        'parent_equipment_id' => $order_item->equipment->parent_equipment_id,
                        'inventory_number' => $order_item->equipment->inventory_number,
                    );
                }
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Конец метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * GetOperationFavorite - Метод получения списка избранных операций за последние 7 дней
     * Входные параметры:
     *      company_department_id   - ключ подразделения
     * Выходные параметры:
     *   group   - вложенный
     *      {operation_kind_id}    - ключ вида операции
     *           operation_kind_id     - ключ вида операции
     *           operation_kind_title  - вид операции
     *           operation_types       - список типов операций
     *               {operation_type_id}        - ключ типа операции
     *                   operation_type_id          - ключ типа операции
     *                   operation_type_title       - название типа операции
     *                   operations         - список операций
     *                       {operation_id}     - ключ операции
     *                           operation_id       - ключ операции
     *                           operation_title    - название операции
     *                           unit_id            - ключ ед.измерения
     *                           short_title        - сокращенное название единицы измерения
     *   list   - список
     *      {operation_id}     - ключ операции
     *          operation_id       - ключ операции
     *          operation_title    - название операции
     *          unit_id            - ключ ед.измерения
     *          short_title        - сокращенное название единицы измерения
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\Order&method=GetOperationFavorite&subscribe=&data={%22company_department_id%22:60002522,"mine_id":290}
     * @return array|object[]
     */
    public static function GetOperationFavorite($data_post = NULL): array
    {
        $log = new LogAmicumFront("GetOperationFavorite");
        $result = array();

        try {
            $log->addLog("Начал выполнение метода");

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
            $date_time_end = Assistant::GetDateTimeNow();
            $date_time_start = date('Y-m-d', strtotime($date_time_end . '-7 days'));

            $order_items = OrderItem::find()
                ->innerJoinWith('orderHistory.order')
                ->innerJoinWith('operation.operationType.operationKind')
                ->innerJoinWith('equipmentStatus')
                ->where(['order.company_department_id' => $company_department_id, 'order.mine_id' => $mine_id])
                ->andWhere(['>=', 'order.date_time_create', $date_time_start])
                ->andWhere(['<=', 'order.date_time_create', $date_time_end])
                ->all();

            foreach ($order_items as $order_item) {
                if ($order_item->operation_id) {

                    $operation_kind_id = $order_item['operation']['operationType']['operationKind']['id'];
                    $operation_type_id = $order_item['operation']['operationType']['id'];
                    $operation_id = $order_item['operation']['id'];
                    if (!isset($result['group'][$operation_kind_id])) {
                        $result['group'][$operation_kind_id] = array(
                            'operation_kind_id' => $operation_kind_id,
                            'operation_kind_title' => $order_item['operation']['operationType']['operationKind']['title'],
                            'operation_types' => null,
                        );

                        if (!isset($result['group'][$operation_kind_id]['operation_types'][$operation_type_id])) {
                            $result['group'][$operation_kind_id]['operation_types'][$operation_type_id] = array(
                                'operation_type_id' => $operation_type_id,
                                'operation_type_title' => $order_item['operation']['operationType']['title'],
                                'operations' => null,
                            );
                        }
                    }

                    $result['group'][$operation_kind_id]['operation_types'][$operation_type_id]['operations'][$operation_id] = array(
                        'operation_id' => $order_item->operation_id,
                        'operation_title' => $order_item->operation->title,
                    );
                    $result['list'][$operation_id] = array(
                        'operation_id' => $order_item->operation_id,
                        'operation_title' => $order_item->operation->title,
                    );
                }
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Конец метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * GetPlaceFavorite - Метод получения списка избранных мест за последние 7 дней
     * Входные параметры:
     *      company_department_id   - ключ подразделения
     * Выходные параметры:
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\Order&method=GetPlaceFavorite&subscribe=&data={%22company_department_id%22:60002522}
     * @return array|object[]
     */
    public static function GetPlaceFavorite($data_post = NULL): array
    {
        $log = new LogAmicumFront("GetPlaceFavorite");
        $result = (object)array();

        try {
            $log->addLog("Начал выполнение метода");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'company_department_id')
            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $company_department_id = $post_dec->company_department_id;

            $response = OrderSystemController::GetFavouritesPlace($company_department_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка получения справочника избранных мест");
            }

            $result = $response['Items'];

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Конец метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetLastOrderHistory - Метод получения последнего сохраненного наряда
     * @param $order_id
     * @return array|array[]
     */
    private static function GetLastOrderHistory($order_id): array
    {
        $log = new LogAmicumFront("GetLastOrderHistory");
        $session = Yii::$app->session;

        $result = array(
            'id' => null,
            'order_id' => $order_id,
            'date_time_create' => '',
            'worker_id' => null,
            'status_id' => null,
        );

        try {
            $log->addLog("Начало выполнения метода");

            $max_date_time_create = (new Query())
                ->select('max(date_time_create)')
                ->from('order_history')
                ->where(['order_id' => $order_id])
                ->scalar();

            if (!$max_date_time_create) {
                $new_order_history = new OrderHistory();
                $new_order_history->order_id = $order_id;
                $new_order_history->date_time_create = Assistant::GetDateTimeNow();
                $new_order_history->worker_id = $session['worker_id'];
                $new_order_history->status_id = self::ORDER_CREATED;

                if (!$new_order_history->save()) {
                    throw new Exception("Не смог создать историю по наряду");
                }
                $new_order_history->refresh();
                $result = $new_order_history->id;
            } else {

                $last_order = (new Query())
                    ->from('order_history')
                    ->where(['date_time_create' => $max_date_time_create, 'order_id' => $order_id])
                    ->one();

                if (!$result) {
                    throw new Exception("Последний сохраненный наряд не найден в БД");
                }
                $result = $last_order['id'];
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result, 'order_history_id' => $result], $log->getLogAll());
    }

    /**
     * GetOrderItemGroups - Метод получения списка групп для подразделения за весь период
     * @param $company_department_id - ключ подразделения
     * @return array|array[]
     */
    private static function GetOrderItemGroups($company_department_id): array
    {
        $log = new LogAmicumFront("GetOrderItemGroups");

        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

//            $groups = OrderItemGroup::find()
//                ->innerJoinWith('orderItems.orderHistory.order')
//                ->indexBy('id')
//                ->where(['order.company_department_id' => $company_department_id])
//                ->asArray()
//                ->all();

            $groups = (new Query())
                ->select(['order_item_group.id', 'order_item_group.title'])
                ->from('order_item_group')
                ->innerJoin('order_item', 'order_item.group_id=order_item_group.id')
                ->innerJoin('order_history', 'order_history.id=order_item.order_history_id')
                ->innerJoin('order', 'order.id=order_history.order_id')
//                ->where(['order_item.order_history_id' => $order_history_id])
                ->where(['order.company_department_id' => $company_department_id])
                ->groupBy(['order_item_group.id', 'order_item_group.title'])
                ->all();

            foreach ($groups as $group) {
                $result[$group['id']] = array(
                    'id' => $group['id'],
                    'title' => $group['title']
                );

            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SaveMainOrder - Метод сохранения главного наряда
     * @param $order_date_time - дата создания наряда
     * @param $date__time_now - текущая дата
     * @param $shift_id - ключ смены
     * @param $company_department_id - ключ подразделения
     * @param $mine_id - ключ шахты
     * @param int $order_status_id - ключ статуса наряда
     * @param int $worker_value_outgoing - количество людей вышедших из шахты
     * @param $brigadeChaneWorker - весь объект наряда на сохранение
     * @param string $order_title - название наряда
     * В Items возвращается ключ главного наряда
     * @return array
     */
    public static function SaveMainOrder(
        $order_date_time,
        $date__time_now,
        $shift_id,
        $company_department_id,
        $mine_id,
        int $order_status_id = self::ORDER_CREATED,
        int $worker_value_outgoing = 0,
        $brigadeChaneWorker = null,
        string $order_title = ""
    ): array
    {
        $log = new LogAmicumFront("SaveMainOrder");
        $session = Yii::$app->session;

        $result = -1;

        try {
            $log->addLog("Начало выполнения метода");

            /** СОХРАНЕНИЕ НАРЯДА */
            $order = Order::findOne(['date_time_create' => $order_date_time, 'shift_id' => $shift_id, 'company_department_id' => $company_department_id, 'mine_id' => $mine_id]);
            if (!$order) {
                $order = new Order();
            }
            if (!$order_title) {
                $order_title = "Наряда участка " . $company_department_id;
            }
            $order->title = $order_title;
            $order->mine_id = $mine_id;
            $order->company_department_id = $company_department_id;
            $order->object_id = 24;
//            $order->brigadeChaneWorker = "";
//            $order->brigadeChaneWorker = json_encode($brigadeChaneWorker);
            $order->date_time_create = $order_date_time;
            $order->shift_id = $shift_id;
            $order->worker_value_outgoing = $worker_value_outgoing;
            $order->status_id = $order_status_id;

            if (!$order->save()) {
                $log->addData($order->errors, '$order->errors', __LINE__);
                throw new Exception('Ошибка сохранения модели наряда Order');
            }

            $order->refresh();
            $order_id = $order->id;


            /** СОХРАНЕНИЕ JSON наряда */
            $order_json = OrderJson::findOne(['id' => $order_id]);
            if (!$order_json) {
                $order_json = new OrderJson();
            }
            $order_json->id = $order_id;
            $order_json->order_id = $order_id;
            $order_json->brigadeChaneWorker = json_encode($brigadeChaneWorker);
            if (!$order_json->save()) {
                $log->addData($order_json->errors, '$order_json->errors', __LINE__);
                throw new Exception('Ошибка сохранения модели OrderJson');
            }

            /** СОХРАНЕНИЕ статусов наряда */
            if ($order_status_id != self::ORDER_CREATED) {
                $order_created_status_id = OrderStatus::findOne(['order_id' => $order_id, 'status_id' => self::ORDER_CREATED]);
                if (!$order_created_status_id) {
                    $order_status = new OrderStatus();
                    $order_status->order_id = $order_id;
                    $order_status->worker_id = $session['worker_id'];
                    $order_status->date_time_create = $date__time_now;
                    $order_status->status_id = self::ORDER_CREATED;
                    $order_status->description = "-";
                    if (!$order_status->save()) {
                        $log->addData($order_status->errors, '$order_status->errors', __LINE__);
                        throw new Exception('Ошибка сохранения модели статуса наряда OrderStatus Created');
                    }
                }
            }

            $order_status = new OrderStatus();
            $order_status->order_id = $order_id;
            $order_status->worker_id = $session['worker_id'];
            $order_status->date_time_create = $date__time_now;
            $order_status->status_id = $order_status_id;
            $order_status->description = "-";
            if (!$order_status->save()) {
                $log->addData($order_status->errors, '$order_status->errors', __LINE__);
                throw new Exception('Ошибка сохранения модели статуса наряда OrderStatus Add');
            }
            unset($order_status);
            $result = $order_id;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    public static function SaveOrderPlaceWithOperation($order_id, $order_places, $date_now, $brigadeChaneWorker = null, $workers_for_restriction = null): array
    {
        $log = new LogAmicumFront("SaveOrderPlaceWithOperation");
        $session = Yii::$app->session;
        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            /**************************** Сохранение наряда на место *****************************************/
            OrderPlace::deleteAll(['order_id' => $order_id]);
            OrderWorkerCoordinate::deleteAll(['order_id' => $order_id]);


            foreach ($order_places as $order_place) {
                $log->addData($order_place, '$order_place', __LINE__);
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

                    if (property_exists($order_place, 'place_to_id') and $order_place->place_to_id != "") {
                        $new_order_place->place_to_id = $order_place->place_to_id;
                    }

                    if (property_exists($order_place, 'place_from_id') and $order_place->place_from_id != "") {
                        $new_order_place->place_from_id = $order_place->place_from_id;
                    }

                    $new_order_place->passport_id = $order_place->passport_id;
                    if (property_exists($order_place, 'route_template_id') and $order_place->route_template_id != "") {
                        $new_order_place->route_template_id = $order_place->route_template_id;
                    }
                    if (!$new_order_place->save()) {
                        $log->addData($new_order_place->errors, '$new_order_place->errors', __LINE__);
                        throw new Exception('Ошибка при сохранении наряда на место ведения работ. Модели OrderPlace');
                    }

                    $new_order_place->refresh();
                    $new_order_place_id = $new_order_place->id;

                    if ($order_place->reason) {
                        $orderPlaceReason = new OrderPlaceReason();
                        $orderPlaceReason->order_place_id = $new_order_place_id;
                        $orderPlaceReason->reason = $order_place->reason;
                        if (!$orderPlaceReason->save()) {
                            $log->addData($orderPlaceReason->errors, '$orderPlaceReason->errors', __LINE__);
                            throw new Exception('Ошибка при сохранении описания причины не выполнения наряда. Модели OrderPlaceReason');
                        }
                        unset($orderPlaceReason);
                    }
                }

                /**
                 * Сохранение наряд путевок ГМ участка АБ
                 */
                if (property_exists($order_place, "route_worker")) {
                    foreach ($order_place->route_worker as $route_worker) {
                        $add_order_route_worker = new OrderRouteWorker();
                        $add_order_route_worker->order_place_id = $new_order_place_id;
                        $add_order_route_worker->worker_id = $route_worker->worker_id;
                        $add_order_route_worker->order_route_json = $route_worker->order_route_json;
                        $add_order_route_worker->order_route_esp_json = $route_worker->order_route_esp_json;
                        if (!$add_order_route_worker->save()) {
                            $log->addData($add_order_route_worker->errors, '$add_order_route_worker->errors', __LINE__);
                            throw new Exception('Ошибка при сохранении наряд путевки ГМ участка АБ');
                        }
                    }
                }


                /**
                 * Создание начала массива на добавление
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
                        if (property_exists($operation_production, 'coordinate') and $operation_production->coordinate != "") {
                            $add_order_operation->coordinate = $operation_production->coordinate;
                        } else {
                            $add_order_operation->coordinate = "0.0,0.0,0.0";
                        }

                        $add_order_operation->order_operation_id_vtb = $operation_production->order_operation_id_vtb;
                        $add_order_operation->correct_measures_id = $operation_production->correct_measures_id;
                        $add_order_operation->injunction_id = $operation_production->injunction_id;
                        $add_order_operation->injunction_violation_id = $operation_production->injunction_violation_id;
                        $add_order_operation->order_place_id_vtb = $operation_production->order_place_id_vtb;
                        $add_order_operation->description = $operation_production->description;
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
                        if (!$add_order_operation->save()) {
                            $log->addData($add_order_operation->errors, '$add_order_operation->errors', __LINE__);
                            throw new Exception('Ошибка при сохранении связки наряда на место и операции');
                        }

                        $add_order_operation->refresh();
                        $order_operation_id = $add_order_operation->id;

                        $last_order_operation_id = $operation_production->order_operation_id;

                        $order_operation_production[$last_order_operation_id] = $order_operation_id;
                        unset($add_order_operation);
                    }
                }
            }

            if ($brigadeChaneWorker) {
                foreach ($brigadeChaneWorker as $brigade) {
                    if (!empty($brigade->chanes)) {
                        foreach ($brigade->chanes as $chane) {
                            if (!empty($chane->workers)) {
                                foreach ($chane->workers as $order_worker) //перебор работников
                                {
                                    // сохраняем привязку людей к местам, выработкам, координатам - используется на интерактивной форме
                                    if (!in_array($order_worker->worker_id, $workers_for_restriction)) {
                                        $workers_for_restriction[] = $order_worker->worker_id;
                                    }
                                    if (!empty($order_worker->chane_production)) {
                                        foreach ($order_worker->chane_production as $coordinate_worker) //перебор операций у работника
                                        {
                                            $add_coordinate_worker = new OrderWorkerCoordinate();
                                            if (isset($coordinate_worker->coordinate_worker) and $coordinate_worker->coordinate_worker and $coordinate_worker->coordinate_worker != "") {
                                                $add_coordinate_worker->coordinate_worker = $coordinate_worker->coordinate_worker;
                                            } else {
                                                $add_coordinate_worker->coordinate_worker = '0.0,0.0,0.0';
                                            }
                                            if (isset($coordinate_worker->coordinate_chane) and $coordinate_worker->coordinate_chane and $coordinate_worker->coordinate_chane != "") {
                                                $add_coordinate_worker->coordinate_chane = $coordinate_worker->coordinate_chane;
                                            } else {
                                                $add_coordinate_worker->coordinate_chane = '0.0,0.0,0.0';
                                            }
                                            $add_coordinate_worker->worker_id = $coordinate_worker->worker_id;
                                            $add_coordinate_worker->chane_id = $coordinate_worker->chane_id;
                                            $add_coordinate_worker->brigade_id = $coordinate_worker->brigade_id;
                                            $add_coordinate_worker->place_id = $coordinate_worker->place_id;
                                            if ($coordinate_worker->edge_id) {
                                                $add_coordinate_worker->edge_id = $coordinate_worker->edge_id;
                                            } else {
                                                $add_coordinate_worker->edge_id = null;
                                            }
                                            $add_coordinate_worker->order_id = $order_id;


                                            if (!$add_coordinate_worker->save()) {
                                                $log->addData($add_coordinate_worker->errors, '$add_coordinate_worker->errors', __LINE__);
                                                throw new Exception('При закреплении координат за работником произошла ошибка');
                                            }

                                            $add_coordinate_worker->refresh();

                                            unset($add_coordinate_worker);
                                        }
                                    }

                                    // сохраняем операции работника
                                    if (!empty($order_worker->operation_production)) {
                                        foreach ($order_worker->operation_production as $operation_worker) // перебор операций у работника
                                        {
                                            $add_operation_worker = new OperationWorker();

                                            if (isset($operation_worker->coordinate, $operation_worker->group_workers_unity) and $operation_worker->coordinate and $operation_worker->coordinate != "") {
                                                $add_operation_worker->coordinate = $operation_worker->coordinate;
                                                $add_operation_worker->group_workers_unity = $operation_worker->group_workers_unity;
                                            } else {
                                                $add_operation_worker->coordinate = '0.0,0.0,0.0';
                                                $add_operation_worker->group_workers_unity = 0;
                                            }
                                            $add_operation_worker->order_operation_id = $order_operation_production[$operation_worker->order_operation_id];
                                            $add_operation_worker->worker_id = $order_worker->worker_id;
                                            $add_operation_worker->chane_id = $chane->chane_id;
                                            $add_operation_worker->brigade_id = $brigade->brigade_id;
                                            if ($order_worker->worker_role_id) {
                                                $role_id = $order_worker->worker_role_id;
                                            } else {
                                                $role_id = 9;
                                            }
                                            $add_operation_worker->role_id = $role_id;
                                            $add_operation_worker->status_id = $operation_worker->status_id;
                                            $add_operation_worker->date_time = $date_now;

                                            if (!$add_operation_worker->save()) {
                                                $log->addData($add_operation_worker->errors, '$add_operation_worker->errors', __LINE__);
                                                throw new Exception('При закреплении операции за работником произошла ошибка');
                                            }

                                            $add_operation_worker->refresh();
                                            $add_operation_worker_id = $add_operation_worker->id;

                                            if (property_exists($operation_worker, 'statuses')) {
                                                foreach ($operation_worker->statuses as $status_item) {
                                                    $order_operation_worker_status[] = [$add_operation_worker_id,
                                                        $status_item->status_id,
                                                        $status_item->status_date_time,
                                                        $session['worker_id']];
                                                    unset($add_operation_worker);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

            }


            if (!empty($order_operation_worker_status)) {
                Yii::$app->db->createCommand()->batchInsert('order_operation_worker_status',//insert_or_op_wo_status - insert order operation worker status
                    ['operation_worker_id', 'status_id', 'date_time', 'worker_id'], $order_operation_worker_status)->execute();
                unset($order_operation_worker_status);

            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result, 'workers_for_restriction' => $workers_for_restriction], $log->getLogAll());
    }

    /**
     * SaveHistoryOrder - метод сохранения истории наряда
     * @param $date__time_now - текущая дата
     * @param $order_id - ключ наряда
     * @param $status_id - ключ статуса
     * @return array
     */
    private static function SaveHistoryOrder(
        $date__time_now,
        $order_id,
        $status_id,
    ): array
    {
        $log = new LogAmicumFront("SaveHistoryOrder");
        $session = Yii::$app->session;
        $worker_id = $session['worker_id'];
        $result = -1;

        try {
            $log->addLog("Начало выполнения метода");

            $order_history = OrderHistory::findOne(['date_time_create' => $date__time_now, 'order_id' => $order_id, 'status_id' => $status_id, 'worker_id' => $worker_id]);
            if (!$order_history) {
                $order_history = new OrderHistory();
            }
            $order_history->date_time_create = $date__time_now;
            $order_history->order_id = $order_id;
            $order_history->status_id = $status_id;
            $order_history->worker_id = $worker_id;

            if (!$order_history->save()) {
                $log->addData($order_history->errors, '$order_history->errors', __LINE__);
                throw new Exception('Ошибка сохранения модели наряда OrderHistory');
            }

            $order_history->refresh();
            $order_history_id = $order_history->id;

            $result = $order_history_id;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SaveOrderItemGroup - метод сохранения истории наряда
     * @param $order_item_groups - группы наряда
     * @return array
     */
    private static function SaveOrderItemGroup($order_item_groups): array
    {
        $log = new LogAmicumFront("SaveOrderItemGroup");
        $result = -1;

        try {
            $log->addLog("Начало выполнения метода");

            $groups = OrderItemGroup::find()->indexBy('id')->all();
            foreach ($groups as $group) {
                $group_id = $group['id'];
                if (property_exists($order_item_groups, $group_id) and $order_item_groups->{$group_id}->title != $group->title) {
                    $group->title = $order_item_groups->{$group_id}->title;
                    if (!$group->save()) {
                        $log->addData($group->errors, '$group->errors', __LINE__);
                        throw new Exception('Ошибка сохранения модели OrderItemGroup');
                    }
                }
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SaveOrderInstructionPbs - метод сохранения предсменных инструктажей
     * @param $order_id
     * @param $order_instruction_pbs
     * @return array
     */
    public static function SaveOrderInstructionPbs($order_id, $order_instruction_pbs): array
    {
        $log = new LogAmicumFront("SaveOrderInstructionPbs");

        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            OrderInstructionPb::deleteAll(['order_id' => $order_id]);

            if (!empty($order_instruction_pbs)) {
                foreach ($order_instruction_pbs as $order_instruction) {
                    if ($order_instruction->instruction_pb_id != null) {
                        $new_order_instruction = new OrderInstructionPb();
                        $new_order_instruction->order_id = $order_id;
                        $new_order_instruction->instruction_pb_id = $order_instruction->instruction_pb_id;
                        if (!$new_order_instruction->save()) {
                            $log->addData($new_order_instruction->errors, '$new_order_instruction->errors', __LINE__);
                            throw new Exception('Ошибка сохранения предсменного инструктажа. Модели OrderInstructionPb');
                        }
                        $new_order_instruction->refresh();
                        unset($new_order_instruction);
                    }
                }
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SaveOrderItemWorkerVGK - метод сохранения работников ВГК по наряду атомарному
     * @param $order_history_id - ключ наряда
     * @param $worker_vgk - список ВГК на сохранение
     * @return array
     */
    private static function SaveOrderItemWorkerVGK($order_history_id, $worker_vgk): array
    {
        $log = new LogAmicumFront("SaveOrderItemWorkerVGK");

        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            OrderItemWorkerVgk::deleteAll(['order_history_id' => $order_history_id]);
            if (!empty($worker_vgk)) {
                foreach ($worker_vgk as $rescuires) {
                    if (property_exists($rescuires, 'vgk') and $rescuires->vgk != 0) {
                        $vgk = 1;
                    } else {
                        $vgk = 0;
                    }
                    $order_worker_vgk_insert[] = [$order_history_id, $rescuires->worker_id, $rescuires->worker_role, $vgk];
                }

                if (!empty($order_worker_vgk_insert)) {
                    Yii::$app->db->createCommand()
                        ->batchInsert('order_item_worker_vgk',
                            ['order_history_id', 'worker_id', 'role_id', 'vgk'], $order_worker_vgk_insert)
                        ->execute();
                }
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SaveOrderItemInstructionPbs - метод сохранения предсменных инструктажей атомарного наряда
     * @param $order_history_id
     * @param $order_item_instruction_pbs
     * @return array
     */
    public static function SaveOrderItemInstructionPbs($order_history_id, $order_item_instruction_pbs): array
    {
        $log = new LogAmicumFront("SaveOrderItemInstructionPbs");

        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            OrderItemInstructionPb::deleteAll(['order_history_id' => $order_history_id]);

            foreach ($order_item_instruction_pbs as $order_item_instruction_pb) {
                if ($order_item_instruction_pb->instruction_pb->instruction_pb_id != null) {
                    $new_order_item_instruction_pb = new OrderItemInstructionPb();
                    $new_order_item_instruction_pb->order_history_id = $order_history_id;
                    $new_order_item_instruction_pb->instruction_pb_id = property_exists($order_item_instruction_pb->instruction_pb, 'instruction_pb_id') ? $order_item_instruction_pb->instruction_pb->instruction_pb_id : null;
                    if (!$new_order_item_instruction_pb->save()) {
                        $log->addData($new_order_item_instruction_pb->errors, '$new_order_item_instruction_pb->errors', __LINE__);
                        throw new Exception('Ошибка сохранения предсменного инструктажа. Модели OrderItemInstructionPb');
                    }
                    $new_order_item_instruction_pb->refresh();
                    unset($new_order_item_instruction_pb);
                }
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SaveOrderItemWorkerInstructionPbs - метод сохранения предсменных инструктажей работников атомарного наряда
     * @param $order_history_id
     * @param $order_item_worker_instruction_pbs
     * @return array
     */
    public static function SaveOrderItemWorkerInstructionPbs($order_history_id, $order_item_worker_instruction_pbs): array
    {
        $log = new LogAmicumFront("SaveOrderItemWorkerInstructionPbs");

        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            OrderItemWorkerInstructionPb::deleteAll(['order_history_id' => $order_history_id]);

            foreach ($order_item_worker_instruction_pbs as $order_item_worker_instruction_pb) {
                if ($order_item_worker_instruction_pb->instruction_pb->instruction_pb_id != null) {
                    $new_order_item_worker_instruction_pb = new OrderItemWorkerInstructionPb();
                    $new_order_item_worker_instruction_pb->order_history_id = $order_history_id;
                    $new_order_item_worker_instruction_pb->instruction_pb_id = property_exists($order_item_worker_instruction_pb->instruction_pb, 'instruction_pb_id') ? $order_item_worker_instruction_pb->instruction_pb->instruction_pb_id : null;
                    $new_order_item_worker_instruction_pb->worker_id = $order_item_worker_instruction_pb->worker_id;
                    if (!$new_order_item_worker_instruction_pb->save()) {
                        $log->addData($new_order_item_worker_instruction_pb->errors, '$new_order_item_worker_instruction_pb->errors', __LINE__);
                        throw new Exception('Ошибка сохранения предсменного инструктажа работников атомарного наряда. Модели OrderItemWorkerInstructionPb');
                    }
                    $new_order_item_worker_instruction_pb->refresh();
                    unset($new_order_item_worker_instruction_pb);
                }
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SaveOrderItemWorkers - метод сохранения сведений о работниках атомарного наряда
     * @param $order_history_id
     * @param $order_item_workers
     * @return array
     */
    public static function SaveOrderItemWorkers($order_history_id, $order_item_workers): array
    {
        $log = new LogAmicumFront("SaveOrderItemWorkers");

        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            OrderItemWorker::deleteAll(['order_history_id' => $order_history_id]);

            foreach ($order_item_workers as $order_item_worker) {
                $new_order_item_worker = new OrderItemWorker();
                $new_order_item_worker->order_history_id = $order_history_id;
                $new_order_item_worker->worker_id = $order_item_worker->worker_id;
                $new_order_item_worker->role_id = (property_exists($order_item_worker->role, 'role_id') and $order_item_worker->role->role_id) ? $order_item_worker->role->role_id : 9;
                $new_order_item_worker->worker_restriction_json = $order_item_worker->worker_restriction_json;
//                $new_order_item_worker->workers_json = json_encode($order_item_worker);
                $new_order_item_worker->workers_json = "";
                $new_order_item_worker->reason_status_id = property_exists($order_item_worker->reason, 'reason_status_id') ? $order_item_worker->reason->reason_status_id : null;
                $new_order_item_worker->reason_description = property_exists($order_item_worker->reason, 'reason_description') ? $order_item_worker->reason->reason_description : null;
                if (!$new_order_item_worker->save()) {
                    $log->addData($new_order_item_worker->errors, '$new_order_item_worker->errors', __LINE__);
                    throw new Exception('Ошибка сохранения сведений о работниках атомарного наряда. Модели OrderItemWorker');
                }
                $new_order_item_worker->refresh();
                unset($new_order_item_worker);
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SaveOrderItemEquipments - метод сохранения сведений об оборудовании
     * @param $order_history_id
     * @param $order_item_equipments
     * @return array
     */
    public static function SaveOrderItemEquipments($order_history_id, $order_item_equipments): array
    {
        $log = new LogAmicumFront("SaveOrderItemEquipments");

        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            OrderItemEquipment::deleteAll(['order_history_id' => $order_history_id]);

            foreach ($order_item_equipments as $order_item_equipment) {
                $new_order_item_equipment = new OrderItemEquipment();
                $new_order_item_equipment->order_history_id = $order_history_id;
                $new_order_item_equipment->equipment_id = property_exists($order_item_equipment->equipment, 'equipment_id') ? $order_item_equipment->equipment->equipment_id : null;
                $new_order_item_equipment->status_id = property_exists($order_item_equipment->status, 'status_id') ? $order_item_equipment->status->status_id : null;
//                $new_order_item_equipment->equipments_json = json_encode($order_item_equipment);
                $new_order_item_equipment->equipments_json = "";
                if (!$new_order_item_equipment->save()) {
                    $log->addData($new_order_item_equipment->errors, '$new_order_item_equipment->errors', __LINE__);
                    throw new Exception('Ошибка сохранения сведений об оборудовании атомарного наряда. Модели OrderItemEquipment');
                }
                $new_order_item_equipment->refresh();
                unset($new_order_item_equipment);
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SaveOrderItemInjunctions - метод сохранения сведений о предписаниях
     * @param $order_history_id
     * @param $order_item_injunctions
     * @return array
     */
    public static function SaveOrderItemInjunctions($order_history_id, $order_item_injunctions): array
    {
        $log = new LogAmicumFront("SaveOrderItemInjunctions");

        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            OrderItemInjunction::deleteAll(['order_history_id' => $order_history_id]);

            foreach ($order_item_injunctions as $order_item_injunction) {
                $new_order_item_equipment = new OrderItemInjunction();
                $new_order_item_equipment->order_history_id = $order_history_id;
                $new_order_item_equipment->injunction_id = $order_item_injunction->injunction_id;
                $new_order_item_equipment->status_id = property_exists($order_item_injunction->status, 'status_id') ? $order_item_injunction->status->status_id : null;
                $new_order_item_equipment->injunctions_json = "";
//                $new_order_item_equipment->injunctions_json = json_encode($order_item_injunction);
                if (!$new_order_item_equipment->save()) {
                    $log->addData($new_order_item_equipment->errors, '$new_order_item_equipment->errors', __LINE__);
                    throw new Exception('Ошибка сохранения предписаний атомарного наряда. Модели OrderItemInjunction');
                }
                $new_order_item_equipment->refresh();
                unset($new_order_item_equipment);
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SaveOrderItems - метод сохранения атомарных нарядов
     * @param $order_history_id
     * @param $order_items
     * @return array
     */
    public static function SaveOrderItems($order_history_id, $order_items): array
    {
        $log = new LogAmicumFront("SaveOrderItems");

        $result = null;
        $workers_for_restriction = [];                                                                                  // массив работников получивших наряд, для запроса ограничений по наряду
        $exist_chat_room_id = null;                                                                                     // список ключей комнат чата сгруппированных по группам нарядов
        $exist_member_chat = null;                                                                                      // список членов чата сгруппированных по ключам комнат чата
        $session = Yii::$app->session;

        try {
            $log->addLog("Начало выполнения метода");

            $date_time_now = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));

            OrderItem::deleteAll(['order_history_id' => $order_history_id]);

            foreach ($order_items as $order_item) {
                $worker_id = property_exists($order_item->worker, 'worker_id') ? $order_item->worker->worker_id : null;
                $group_order_id = $order_item->group_order_id;

                if ($worker_id) {
                    $workers_for_restriction[] = $order_item->worker->worker_id;
                }

                /** ПОЛУЧЕНИЕ ИЛИ СОЗДАНИЕ КОМНАТЫ ЧАТА ПЕРЕД СОХРАНЕНИЕМ НАРЯДА */
                if ($order_item->chat_room_id and $order_item->chat_room_id > 0) {
                    $chat_room_id = $order_item->chat_room_id;
                } else {
                    if ($exist_chat_room_id and isset($exist_chat_room_id[$group_order_id])) {
                        $chat_room_id = $exist_chat_room_id[$group_order_id];
                    } else {
                        $response = ChatController::AddNewRoom(json_encode((object)array(
                            'title' => "Наряд " . $order_history_id . ($group_order_id ?: ""),
                            'chat_type_id' => 2
                        )));
                        $log->addLogAll($response);
                        if ($response['status'] != 1) {
                            throw new Exception("Ошибка создания комнаты чата");
                        }
                        $chat_room_id = $response['Items']['chat_room_id'];
                        $exist_chat_room_id[$group_order_id] = $chat_room_id;
                    }
                }

                /** ПРОВЕРИТЬ ИЛИ СОЗДАТЬ ЧЛЕНОВ ЧАТА */
                if (!isset($exist_member_chat[$chat_room_id][$worker_id]) and $worker_id) {
                    $response = ChatController::actionAddWorkerInGroupChat(json_encode((object)array(
                        'room_id' => $chat_room_id,
                        'worker_id' => $worker_id,
                        'chat_role_id' => 2
                    )));
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception("Ошибка добавления члена чата");
                    }
                    $exist_member_chat[$chat_room_id][$worker_id] = $worker_id;
                }

                /** СОХРАНЕНИЕ СОЗДАННОЙ ГРУППЫ */
                $group_id = null;
                if (property_exists($order_item->group, 'group_id') and $order_item->group->group_id) {

                    if (!isset($hand_group[$order_item->group->group_id])) {
                        $group = OrderItemGroup::findOne(['id' => $order_item->group->group_id]);
                        if (!$group) {
                            $group = new OrderItemGroup();
                        }
                        if ($group->title != $order_item->group->group_title) {
                            $group->title = $order_item->group->group_title;
                            if (!$group->save()) {
                                $log->addData($group->errors, '$group->errors', __LINE__);
                                throw new Exception('Ошибка сохранения атомарных нарядов. Модели OrderItemGroup');
                            }
                            $group->refresh();
                        }
                        $group_id = $group->id;
                        $hand_group[$order_item->group->group_id] = $group_id;
                    } else {
                        $group_id = $hand_group[$order_item->group->group_id];
                    }
                }

                //$group_id = $group_id < 0 ? (-1) * $group_id : $group_id;

                $new_order_item = new OrderItem();
                $new_order_item->order_history_id = $order_history_id;
                $new_order_item->worker_id = $worker_id;
                $new_order_item->equipment_id = property_exists($order_item->equipment, 'equipment_id') ? $order_item->equipment->equipment_id : null;
                $new_order_item->operation_id = property_exists($order_item->operation, 'operation_id') ? $order_item->operation->operation_id : null;
                $new_order_item->place_from_id = property_exists($order_item->place_from, 'place_id') ? $order_item->place_from->place_id : null;
                $new_order_item->place_to_id = property_exists($order_item->place_to, 'place_id') ? $order_item->place_to->place_id : null;
                $new_order_item->group_order_id = ($group_order_id < 0) ? (-1) * $group_order_id : $group_order_id;
                $new_order_item->plan = $order_item->plan;
                $new_order_item->fact = $order_item->fact;
                $new_order_item->description = $order_item->description;
                $new_order_item->group_id = $group_id; //property_exists($order_item->group, 'group_id') ? $order_item->group->group_id : null;
                $new_order_item->chane_id = property_exists($order_item->chane, 'chane_id') ? $order_item->chane->chane_id : null;
                $new_order_item->brigade_id = property_exists($order_item->brigade, 'brigade_id') ? $order_item->brigade->brigade_id : null;
                $new_order_item->status_id = property_exists($order_item->status, 'status_id') ? $order_item->status->status_id : null;
                $new_order_item->order_operation_id_vtb = $order_item->order_operation_id_vtb;
                $new_order_item->correct_measures_id = $order_item->correct_measures_id != -1 ? $order_item->correct_measures_id : null;
                $new_order_item->order_place_id_vtb = $order_item->order_place_id_vtb;
                $new_order_item->injunction_violation_id = $order_item->injunction_violation_id;
                $new_order_item->injunction_id = $order_item->injunction->injunction_id;
                $new_order_item->equipment_status_id = property_exists($order_item->equipment_status, 'status_id') ? $order_item->equipment_status->status_id : null;
                $new_order_item->role_id = (property_exists($order_item->role, 'role_id') and $order_item->role->role_id) ? $order_item->role->role_id : 9;
                $new_order_item->date_time_create = $order_item->date_time_create;
                $new_order_item->order_type_id = $order_item->order_type_id;
                $new_order_item->chat_room_id = $chat_room_id;
                $new_order_item->passport_id = $order_item->passport_id;
                $new_order_item->route_template_id = $order_item->route_template_id;
                $new_order_item->order_route_json = $order_item->order_route_json;
                $new_order_item->order_route_esp_json = $order_item->order_route_esp_json;

                if (!$new_order_item->save()) {
                    $log->addData($order_item, '$order_item', __LINE__);
                    $log->addData($new_order_item->errors, '$new_order_item->errors', __LINE__);
                    throw new Exception('Ошибка сохранения атомарных нарядов. Модели OrderItem');
                }
                $new_order_item->refresh();


                /** СОХРАНЕНИЕ СТАТУСОВ АТОМАРНЫХ НАРЯДОВ */
                foreach ($order_item->order_item_statuses as $order_item_status) {
                    $new_order_item_status = new OrderItemStatus();
                    $new_order_item_status->order_item_id = $new_order_item->id;
                    $new_order_item_status->status_id = $order_item_status->status_id;
                    $new_order_item_status->worker_id = $session['worker_id'];
                    $new_order_item_status->date_time_create = $order_item_status->date_time_create;
                    $new_order_item_status->description = $order_item_status->description;

                    if (!$new_order_item_status->save()) {
                        $log->addData($new_order_item_status, '$new_order_item_status', __LINE__);
                        $log->addData($new_order_item_status->errors, '$new_order_item_status->errors', __LINE__);
                        throw new Exception('Ошибка сохранения атомарных нарядов. Модели OrderItemStatus');
                    }
                }
                unset($new_order_item);
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result, 'workers_for_restriction' => $workers_for_restriction], $log->getLogAll());
    }

    public function actionIndex(): string
    {
        return $this->render('index');
    }

    /**
     * ChangeOrderItemStatus - Метод меняет статус в order_item
     * Входные параметры:
     *  order_items_id []
     *  status_id
     *  description
     * Выходные параметры:
     *  order_items_id [] - изменённые order_id
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\Order&method=ChangeOrderItemStatus&subscribe=&data={"order_items_id":[3,4],"status_id":49,"description":"TEST"}
     */
    public static function ChangeOrderItemStatus($data_post = NULL)
    {
        $log = new LogAmicumFront("ChangeOrderItemStatus");

        $result = [];

        try {

            $log->addLog("Начало выполнения метода");

            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }

            if (!property_exists($post, 'order_items_id') ||
                !property_exists($post, 'status_id') ||
                !property_exists($post, 'description')
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $order_items_id = $post->order_items_id;
            $status_id = $post->status_id;
            $description = $post->description;
            $date_time = Assistant::GetDateTimeNow();

            $order_items = OrderItem::find()
                ->joinWith('orderHistory.order')
                ->where(['order_item.id' => $order_items_id])
                ->all();

            $session = Yii::$app->session;

            foreach ($order_items as $order_item) {
                if ($order_item->worker_id != $session['worker_id']) {
                    break;
                }

                $order_item->status_id = $status_id;

                if (!$order_item->save()) {
                    $log->addData($order_item->errors, '$order_item->errors', __LINE__);
                    throw new Exception('Ошибка сохранения атомарных нарядов. Модели OrderItem');
                }

                $new_order_item_status = new OrderItemStatus();
                $new_order_item_status->order_item_id = $order_item->id;
                $new_order_item_status->status_id = $status_id;
                $new_order_item_status->worker_id = $session['worker_id'];
                $new_order_item_status->date_time_create = $date_time;
                $new_order_item_status->description = $description;

                if (!$new_order_item_status->save()) {
                    $log->addData($new_order_item_status->errors, '$new_order_item_status->errors', __LINE__);
                    throw new Exception('Ошибка сохранения атомарных нарядов. Модели OrderItemStatus');
                }

                $result['order_items_id'][] = $order_item->id;

            }

            $order = $order_item->orderHistory->order;

            $log->addLog("Получение GetOrderPit");
            $response = self::GetOrderPit(json_encode(array('company_department_id' => $order->company_department_id, 'date_time' => $order->date_time_create, 'mine_id' => $order->mine_id, 'order_history_id' => null, 'shift_id' => $order->shift_id,)));
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception('Ошибка получения наряда');
            }
            $order_pit = $response['Items'];

            $response = WebsocketController::SendMessageToWebSocket('orderSystem',
                array(
                    'type' => 'SaveOrderPit',
                    'message' => $order_pit
                )
            );
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка отправки данных на вебсокет (ChangeOrderItemStatus)');
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetWorkersInOrder - Метод получения работников получивших наряд в заданный период времени
     * @param $company_department_id - ключ подразделения
     * @param $date_start - дата начала выборки
     * @param $date_end - дата окончания выборки
     * @param null $workers_excluded - исключенные работники
     * @return array|array[]|\Check[]|Complex|null[]|\string[][]
     */
    public static function GetWorkersInOrder($company_department_id, $date_start, $date_end, $workers_excluded = null)
    {
        $log = new LogAmicumFront("GetWorkersInOrder");
        $result = null;
        try {
            $log->addLog("Начал выполнять метод");

            $result = OperationWorker::find()
                ->joinWith('worker.employee')
                ->joinWith('worker.company')
                ->innerJoinWith("orderOperation.orderPlace.order")
                ->where(['between', "order.date_time_create", $date_start, $date_end])
                ->andFilterWhere(['not in', 'operation_worker.worker_id', $workers_excluded])
                ->andFilterWhere(['order.company_department_id' => $company_department_id])
                ->indexBy('worker_id')
                ->all();

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнять метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetWorkerOrders - Метод получения нарядов работников - конкретные дни, в которые работник получал наряды
     * требуется для арсчета статистики предсменного тестирования - сколько раз получал или должен был получать и сколько раз сдал (считается в методе выше)
     * @param null $company_department_id - ключ подразделения
     * @param null $date_start - дата начала выборки
     * @param null $date_end - дата окончания выборки
     * @return array|array[]|\Check[]|Complex|null[]|\string[][]
     */
    public static function GetWorkerOrders($company_department_id = null, $date_start = null, $date_end = null)
    {
        $log = new LogAmicumFront("GetWorkerOrders");
        $result = null;
        try {
            $log->addLog("Начал выполнять метод");

            $result = (new Query())
                ->select(['employee_id', 'worker_id', 'date_time_create', 'shift_id'])
                ->from('operation_worker')
                ->innerJoin('worker', 'operation_worker.worker_id=worker.id')
                ->innerJoin('employee', 'employee.id=worker.employee_id')
                ->innerJoin('order_operation', 'order_operation.id=operation_worker.order_operation_id')
                ->innerJoin('order_place', 'order_place.id=order_operation.order_place_id')
                ->innerJoin('order', 'order.id=order_place.order_id')
                ->where(['between', "order.date_time_create", date("Y-m-d", strtotime($date_start)), date("Y-m-d", strtotime($date_end))])
//                ->where("order.date_time_create>=" . '"' . date("Y-m-d", strtotime($date_start)) . '"')
//                ->andWhere("order.date_time_create<=" . '"' . date("Y-m-d", strtotime($date_end)) . '"')
                ->andFilterWhere(['order.company_department_id' => $company_department_id])
                ->indexBy(function ($func) {
                    return $func['employee_id'] . '_' . $func['date_time_create'] . '_' . $func['shift_id'];
                })
                ->all();
            $log->addData($result, '$result', __LINE__);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнять метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}

