<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\positioningsystem;

use backend\controllers\Assistant;
use backend\controllers\SensorBasicController;
use frontend\controllers\handbooks\DepartmentController;
use frontend\models\EventCompareGas;
use frontend\models\Mine;
use frontend\models\OrderPlace;
use frontend\models\PlaceRoute;
use frontend\models\Route;
use frontend\models\RouteEdge;
use frontend\models\RouteTemplate;
use frontend\models\RouteTemplateEdge;
use frontend\models\Worker;
use frontend\models\WorkerParameterValue;
use frontend\models\WorkerParameterValueHistory;
use Throwable;
use Yii;
use yii\db\Exception;
use yii\db\Query;
use yii\web\Controller;

class RouteController extends Controller
{
    // GetRouteData             - Метод получения данных из таблицы маршрутов по наряду и статусу(статус необязательно) ИСПОЛЬЗУЕТСЯ В НАРЯДКЕ
    // GetRouteTemplate         - Метод получения данных из таблицы маршрутов, только про шаблоны по участку
    // SaveRouteData            - Метод сохранения данных в таблицу маршрутов
    // SaveRoute                - Сохраняем данные в таблицу маршрутов передвижения сотрудников к рабочему месту ИСПОЛЬЗУЕТСЯ В НАРЯДКЕ
    // SaveRouteTemplateData    - Сохраняем данные в таблицу шаблонов маршрутов передвижения сотрудников к рабочему месту
    // ChangeStatusRoute        - Метод изменения статуса маршрута на неактивный
    // EditRoute                - Метод редактования данных в таблице маршрутов (редактирование маршрута)
    // DeleteRouteTemplate      - Метод удаления шаблона маршрута

    // GetPlaceRoute            - Получаем привязку шаблонов маршрутов и мест
    // SavePlaceRoute           - Сохраняем привязку шаблона маршрута и места
    // DeletePlaceRoute         - Метод удаления привязки шаблона маршрута и места
    // GetRouteTemplateList     - справочник шаблонов маршрутов

    // GetCompareRoute          - сравнение маршрутов
    // GetCompareRouteDetail    - метод получения деталей сравнения маршрутов

    // GetRouteTemplatePlace    - метод получения мест шаблона маршрута горных мастеров АБ
    // GetRouteTemplatePlaceEsp - метод получения мест шаблона маршрута для ЭСП АБ

    // GetSensorRouteDetail     - метод получения списка сенсоров по эджам со значениями до запрашиваемой даты

    const NOT_ACTIVE = 19;              // статус "Не активный" шаблона маршрута
    const ACTIVE = 1;                   // статус "Активный" шаблона маршрута

    const TEMPLATE_CREATE_ROUTE = 2;    // тип маршрута - ШАБЛОН
    const PLAN_CREATE_ROUTE = 2;        // тип маршрута - ПЛАНОВЫЙ МАРШРУТ
    const FACT_CREATE_ROUTE = 2;        // тип маршрута - ФАКТИЧЕСКИЙ МАРШРУТ

    const TEMPLATE_CREATE = 98;         // статус "Создан" шаблона маршрута
    const TEMPLATE_CHANGE = 97;         // статус "Изменен" шаблона маршрута
    const TEMPLATE_DELETE = 99;         // статус "Удален" шаблона маршрута

    const STATIONARY_SENSOR = 116;      // тип типового объекта 116 стационарные датчики

    /**
     * Метод GetRouteData() - Получаем данные из таблицы маршрутов по наряду, статусу и номеру бригады(номер бригады необязательно)
     * @param null $data_post - JSON массив с данными по идентификатору звена
     * @return array Массив со следующей структурой: [routes_by_route_id]
     *                                                                      [route_id]:
     *                                                                          [route_id]          - ключ маршрута
     *                                                                          [title]             - название маршрута
     *                                                                          [route_type_id]     - тип маршрута (шаблон, рабочий)
     *                                                                          [order_id]          - наряд на который назначен даннй маршрут
     *                                                                          [chane_id]          - звено для которого устанавливается маршрут
     *                                                                          [edgeIds]           - список эджей по которым строим маршрут
     *                                                                              edge_id:
     *                                                                          [offset_start]      - смещение на эдже от начала для тех случаев когда маршрут строится с середины эджа
     *                                                                          [offset_end]        - смещение на эдже от конца
     *
     * @package frontend\controllers\positioningsystem
     * @example http://localhost/read-manager-amicum?controller=positioningsystem\Route&method=GetRouteData&subscribe=&data={%22order_id%22:59}
     *          http://localhost/read-manager-amicum?controller=positioningsystem\Route&method=GetRouteData&subscribe=&data={%22order_id%22:59,%22brigade_id%22:2}
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 01.08.2019 16:54
     */
    public static function GetRouteData($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $brigade_id = NULL;
        $route_data = array();                                                                                          // Промежуточный результирующий массив

        $warnings[] = 'GetRouteData. Данные успешно переданы';
        $warnings[] = 'GetRouteData. Входной массив данных' . $data_post;
        try {
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetRouteData. Декодировал входные параметры';

            if (!property_exists($post_dec, 'order_id')) {
                throw new Exception('GetRouteData. Переданы некорректные входные параметры');
            }

            $warnings[] = 'GetRouteData. Данные с фронта получены';

            $order_id = $post_dec->order_id;

            if (property_exists($post_dec, 'brigade_id')) {
                $brigade_id = $post_dec->brigade_id;
            }

            /****************** Поиск маршрута по условию идентификатора наряду и идентификатору бригады ******************/
            $found_data_route = Route::find()//
            ->joinWith('routeEdges')
                ->joinWith('order')
                ->where(['route.order_id' => $order_id])
                ->andWhere(['route.status_id' => self::ACTIVE])
                ->andFilterWhere(['order.brigade_id' => $brigade_id])
                ->limit(5000)
                ->orderBy('id')
                ->asArray()
                ->all();

            /****************** Если маршрут есть, то идет заполнение данных ******************/
            if (!empty($found_data_route)) {
                foreach ($found_data_route as $route) {
                    $route_data['routes_by_route_id'][$route['id']]['route_type_id'] = $route['route_type_id'];
                    $route_data['routes_by_route_id'][$route['id']]['order_id'] = $route['order_id'];
                    $route_data['routes_by_route_id'][$route['id']]['chane_id'] = $route['chane_id'];
                    $route_data['routes_by_route_id'][$route['id']]['title'] = $route['title'];
                    $route_data['routes_by_route_id'][$route['id']]['status_id'] = $route['status_id'];
                    $route_data['routes_by_route_id'][$route['id']]['route_id'] = $route['id'];
                    $route_data['routes_by_route_id'][$route['id']]['offset_start'] = $route['offset_start'];
                    $route_data['routes_by_route_id'][$route['id']]['offset_end'] = $route['offset_end'];
                    $route_data['routes_by_route_id'][$route['id']]['edgeIds'] = array();
                    foreach ($route['routeEdges'] as $route_edge) {                                                     //заполнение идентификаторами выработок
                        $route_data['routes_by_route_id'][$route['id']]['edgeIds'][] = $route_edge['edge_id'];
                    }
                }
            } else {
                $route_data['routes_by_route_id'] = (object)array();
                $warnings[] = 'GetRouteData. Не найден маршрут';
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetRouteData. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }


        return ['Items' => $route_data, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings];
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetRouteTemplate() - Получаем данные из таблицы маршрутов, только про шаблоны (по участку)
     * @param null $data_post - JSON массив с данными по идентификатору звена
     * @return array Массив со следующей структурой:
     *                                          [route_id]                         - ключ шаблона маршрута
     *                                                      route_id:                               - ключ шаблона маршрута
     *                                                      title:                                  - наименование шаблона маршрута
     *                                                      route_type_id:                          - ключ типа маршрута
     *                                                      date_time:                              - дата и время создания маршрута
     *                                                      chane_id:                               - заглушка
     *                                                      order_id:                               - заглушка
     *                                                      company_department_id:                  - ключ департамента
     *                                                      status_id:                              - ключ статуса
     *                                                      worker_id:                              - ключ работника, который создал шаблон маршрута
     *                                                      edgeIds:                                - список эджей
     *                                                          {edge_id}                               ключ эджа
     *                                                              edge_id:                            ключ эджа
     *                                                              place_id:                           ключ места
     *                                                              place_title:                        название места
     *                                                      offset_start:                           - сдвиг от начала эджа
     *                                                      offset_end:                             - сдвиг от конца эджа
     *
     * @package frontend\controllers\positioningsystem
     * @example http://localhost/read-manager-amicum?controller=positioningsystem\Route&method=GetRouteTemplate&subscribe=&data={%22company_department_id%22:20023045}
     *
     * @author Якимов М.Н,
     * Created date: on 06.08.2019 15:54
     */
    public static function GetRouteTemplate($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $company_department_id = NULL;
        $route_data = array();                                                                                          // Промежуточный результирующий массив
        if ($data_post !== NULL && $data_post !== '') {
            $warnings[] = 'GetRouteTemplate. Данные успешно переданы';
            $warnings[] = 'GetRouteTemplate. Входной массив данных' . $data_post;
            try {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                $warnings[] = 'GetRouteTemplate. Декодировал входные параметры';
                if (property_exists($post_dec, 'company_department_id'))                                       // Проверяем наличие в нем нужных нам полей
                {
                    $warnings[] = 'GetRouteTemplate. Данные с фронта получены';
                    $company_department_id = $post_dec->company_department_id;
                } else {
                    throw new Exception('GetRouteTemplate. Переданы некорректные входные параметры');
                }

                // поиск вложенных подразделений
                $response = DepartmentController::FindDepartment($company_department_id);
                if ($response['status'] == 1) {
                    $company_departments = $response['Items'];
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception("GetRouteTemplate. Не смог получить список вложенных подразделений");
                }

                $edge_status = (new Query())
                    ->select('edge_id,status_id')
                    ->select([
                        'edge_id as edge_id',
                        'status_id as status_id'
                    ])
                    ->from("view_edge_status_maxDate_full")
                    ->indexBy("edge_id")
                    ->all();

                /****************** Поиск маршрута по условию ID-участка и если это шаблон  ******************/
                $found_data_route = RouteTemplate::find()//
                ->joinWith('routeTemplateEdges')
                    ->joinWith('routeTemplateEdges.edge.place')
                    ->where(['route_template.company_department_id' => $company_departments])
                    ->andWhere(['route_template.route_type_id' => self::TEMPLATE_CREATE_ROUTE])
                    ->limit(5000)
                    ->orderBy('id')
                    ->asArray()
                    ->all();
                if (!empty($found_data_route)) {                                                                         //если данные найдены тогда проебагаем по массиву
//                    throw new Exception('GetRouteTemplate. Не найден маршрут');

                    /****************** если маршрут есть и это шаблон, то идет заполнение данных ******************/
                    foreach ($found_data_route as $route) {
                        $route_data[$route['id']]['route_id'] = $route['id'];
                        $route_data[$route['id']]['title'] = $route['title'];
                        $route_data[$route['id']]['route_type_id'] = $route['route_type_id'];
                        $route_data[$route['id']]['order_id'] = 0;
                        $route_data[$route['id']]['chane_id'] = 0;
                        $route_data[$route['id']]['company_department_id'] = $route['company_department_id'];
                        $route_data[$route['id']]['date_time'] = $route['date_time'];
                        $route_data[$route['id']]['status_id'] = $route['status_id'];
                        $route_data[$route['id']]['offset_start'] = $route['offset_start'];
                        $route_data[$route['id']]['offset_end'] = $route['offset_end'];
                        $route_data[$route['id']]['worker_id'] = $route['worker_id'];
                        $route_data[$route['id']]['edgeIds'] = array();
                        foreach ($route['routeTemplateEdges'] as $route_edge) {                                                     //заполнение индентификаторами выработок
                            $route_data[$route['id']]['edgeIds'][$route_edge['edge_id']]['edge_id'] = $route_edge['edge_id'];
                            $route_data[$route['id']]['edgeIds'][$route_edge['edge_id']]['place_id'] = $route_edge['edge']['place']['id'];
                            $route_data[$route['id']]['edgeIds'][$route_edge['edge_id']]['place_title'] = $route_edge['edge']['place']['title'];
                            if ($edge_status and isset($edge_status[$route_edge['edge_id']])) {
                                $route_data[$route['id']]['edgeIds'][$route_edge['edge_id']]['status_id'] = $edge_status[$route_edge['edge_id']]['status_id'];
                            } else {
                                $route_data[$route['id']]['edgeIds'][$route_edge['edge_id']]['status_id'] = 1;
                            }
                        }
                    }
                }
            } catch (Throwable $exception) {
                $errors[] = 'GetRouteTemplate. Исключение';
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
                $status *= 0;
            }
        } else {
            $errors[] = 'GetRouteTemplate. Данные с фронта не получены';
            $status *= 0;
        }
        $result = $route_data;
        $warnings[] = 'GetRouteTemplate. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * Метод SaveRouteTemplateData() - Сохраняем данные в таблицу шаблонов маршрутов передвижения сотрудников к рабочему месту
     * при редактировании маршрута, старый ммаршрут помечаем как неактуальный, а новый записываем с 0
     * @param null $data_post - Массив со следующей структурой:
     *                                                                      route:
     *                                                                          route_id                    - ключ шаблона маршрута
     *                                                                          title                       - название шаблона маршрута
     *                                                                          route_type_id               - тип маршрута (шаблон, рабочий)
     *                                                                          company_department_id       - ключ подразделения
     *                                                                          status_id                   - ключ сатуса (актуальный или нет маршрут)
     *                                                                          edgeIds                     - список эджей по которым строим маршрут
     *                                                                              {edge_id}
     *                                                                                  edge_id:
     *                                                                                  place_id:
     *                                                                                  place_title:
     *                                                                          offset_start                - смещение на эдже от начала для тех случаев когда маршрут строится с середины эджа
     *                                                                          offset_end                  - смещение на эдже от конца
     *                                                                          worker_id                   - ключ работника изменившего либо создавшего маршрут
     *
     * @return array - возвращает стандартный массив +результат выполнения метода получения
     * @package frontend\controllers\positioningsystem
     * @example http://localhost/read-manager-amicum?controller=positioningsystem\Route&method=SaveRouteTemplateData&subscribe=&data={}
     *
     *
     * Тестовый набор данных: '{"Items":{"routes_by_route_id":{"4":{"route_type_id":"1","order_id":"59","chane_id":"715","route_title":"Маршрут номер 777","route_id":"4","offset_start":"0","offset_end":"0.7","status_id":"1","edgeIds":["22140","22141","22142"]},"22":{"route_type_id":"1","order_id":"59","chane_id":"715","route_title":"Маршрут номер 666","route_id":"22","offset_start":"0.7","offset_end":"0.9","status_id":"1","edgeIds":["22183","22184","22185","22186"]}},"deprecated_ids":[4]},"status":1,"errors":[],"warnings":["GetRouteData. Данные успешно переданы","GetRouteData. Входной массив данных{\"order_id\":59}","GetRouteData. Декодировал входные параметры","GetRouteData. Данные с фронта получены"]}'
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 02.08.2019 10:00
     */
    public static function SaveRouteTemplateData($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'SaveRouteTemplateData';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
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

        $route_edges = array();
        $order_id = NULL;
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
            // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//                $date_time_debug_start, $date_time_debug_end, $log_id,
//                $duration_summary, $max_memory_peak, $count_all);
//            if ($response['status'] === 1) {
//                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
//            } else {
//                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
//            }

            /** Метод начало */
            $warnings[] = 'SaveRouteTemplateData. Начало метода';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveRouteTemplateData. Не переданы входные параметры');
            }
            $warnings[] = 'SaveRouteTemplateData. Данные успешно переданы';
            $warnings[] = 'SaveRouteTemplateData. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'SaveRouteTemplateData. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'route'))                                                         //
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }

            $warnings[] = 'SaveRouteTemplateData. Данные с фронта получены';
            $route = $post_dec->route;


            /****************** Перебор данных из  Json-а для добавления в БД ******************/

            $session = Yii::$app->session;
            $add_route = RouteTemplate::findOne(['id' => $route->route_id]);
            if (!$add_route) {
                $add_route = new RouteTemplate();
                $add_route->status_id = self::TEMPLATE_CREATE;
            } else {
                $add_route->status_id = self::TEMPLATE_CHANGE;
            }
            $add_route->title = $route->title;
            $add_route->route_type_id = $route->route_type_id;
            $add_route->company_department_id = $route->company_department_id;
            $add_route->offset_start = $route->offset_start;
            $add_route->offset_end = $route->offset_end;
            $add_route->date_time = $route->date_time;
            $add_route->worker_id = $session['worker_id'];
            /****************** проверка на сохранение ******************/
            if ($add_route->save()) {
                $warnings[] = 'SaveRouteTemplateData. Успешное сохранение  данных о маршруте';
                $add_route->refresh();
                $id_new_route = $add_route->id;
                $route->route_id = $id_new_route;
                $route->worker_id = $session['worker_id'];
            } else {
                $errors[] = $add_route->errors;
                throw new Exception('SaveRouteTemplateData. Ошибка при сохранении данных маршрута');
            }
            foreach ($route->edgeIds as $edge) {                                                            //формируем массив для добавления связки маршрута и выработок
                $route_edges[] = [$id_new_route, $edge->edge_id];
            }
            $warnings[] = 'SaveRouteTemplateData. Выбрано больше одной выработки, сохраняем';
            RouteTemplateEdge::deleteAll(['route_template_id' => $route->route_id]);

            if (!empty($route_edges)) {
                $result_add_route_edges = Yii::$app->db->createCommand()
                    ->batchInsert('route_template_edge', ['route_template_id', 'edge_id'], $route_edges)//массовая вставка в БД
                    ->execute();
                if ($result_add_route_edges != 0) {
                    $warnings[] = 'SaveRouteTemplateData. Связка маршрута и выработок успешно сохранена ';
                } else {
                    throw new Exception('SaveRouteTemplateData. Ошибка при добавлении связки маршрута и выработок');
                }
            }
            $result = $route;
            /** Метод окончание */

        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
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
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result_main;
    }

    /**
     * Метод DeleteRouteTemplate() -  удаление шаблона маршрута
     * @param null $data_post - Массив со следующей структурой:
     *                                                                          route_id                    - ключ шаблона маршрута
     *
     * @return array - возвращает стандартный массив +результат выполнения метода получения
     * @package frontend\controllers\positioningsystem
     * @example http://localhost/read-manager-amicum?controller=positioningsystem\Route&method=DeleteRouteTemplate&subscribe=&data={"route_id":12}
     *
     *
     * Якимов М.Н.
     * Created date: on 02.08.2019 10:00
     */
    public static function DeleteRouteTemplate($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'DeleteRouteTemplate';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
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
            // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//                $date_time_debug_start, $date_time_debug_end, $log_id,
//                $duration_summary, $max_memory_peak, $count_all);
//            if ($response['status'] === 1) {
//                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
//            } else {
//                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
//            }

            /** Метод начало */
            $warnings[] = 'SaveRouteTemplateData. Начало метода';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveRouteTemplateData. Не переданы входные параметры');
            }
            $warnings[] = 'SaveRouteTemplateData. Данные успешно переданы';
            $warnings[] = 'SaveRouteTemplateData. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'SaveRouteTemplateData. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'route_id'))                                                         //
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }

            $warnings[] = 'SaveRouteTemplateData. Данные с фронта получены';
            $route_id = $post_dec->route_id;

            $find_routes = OrderPlace::findAll(['route_template_id' => $route_id]);
            if (!$find_routes) {
                RouteTemplate::deleteAll(['id' => $route_id]);
            } else {
                throw new Exception($method_name . '. Удаление шаблона маршрута не возможно. Данный маршрут используется в нарядной системе');
            }

            /** Метод окончание */

        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
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
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result_main;
    }

    /**
     * Метод SaveRouteData() - Сохраняем данные в таблицу маршрутов передвижения сотрудников к рабочему месту
     * при редактировании маршрута, старый ммаршрут помечаем как неактуальный, а новый записываем с 0
     * @param null $data_post - Массив со следующей структурой:     [Items]
     *                                                                  [routes_by_route_id]
     *                                                                      [route_id]:
     *                                                                          [route_id]          - ключ маршрута
     *                                                                          [route_type_id]     - тип маршрута (шаблон, рабочий)
     *                                                                          [order_id]          - наряд на который назначен даннй маршрут
     *                                                                          [chane_id]          - звено для которого устанавливается маршрут
     *                                                                          [route_title]       - название маршрута
     *                                                                          [edgeIds]           - список эджей по которым строим маршрут
     *                                                                              edge_id:
     *                                                                          [offset_start]      - смещение на эдже от начала для тех случаев когда маршрут строится с середины эджа
     *                                                                          [offset_end]        - смещение на эдже от конца
     *                                                                  [deprecated_ids]            - айдишники маршрутов, помечаемые как не актуальные
     *                                                               status:
     *                                                               [errors]
     *                                                               [warnings]
     *
     * @return array - возвращает стандартный массив +результат выполнения метода получения
     * @package frontend\controllers\positioningsystem
     * @example http://localhost/read-manager-amicum?controller=positioningsystem\Route&method=SaveRouteData&subscribe=&data={}
     *
     *
     * Тестовый набор данных: '{"Items":{"routes_by_route_id":{"4":{"route_type_id":"1","order_id":"59","chane_id":"715","route_title":"Маршрут номер 777","route_id":"4","offset_start":"0","offset_end":"0.7","status_id":"1","edgeIds":["22140","22141","22142"]},"22":{"route_type_id":"1","order_id":"59","chane_id":"715","route_title":"Маршрут номер 666","route_id":"22","offset_start":"0.7","offset_end":"0.9","status_id":"1","edgeIds":["22183","22184","22185","22186"]}},"deprecated_ids":[4]},"status":1,"errors":[],"warnings":["GetRouteData. Данные успешно переданы","GetRouteData. Входной массив данных{\"order_id\":59}","GetRouteData. Декодировал входные параметры","GetRouteData. Данные с фронта получены"]}'
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 02.08.2019 10:00
     */
    public static function SaveRouteData($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $save_route = array();                                                                                        // Промежуточный результирующий массив
        $route_edges = array();
        $order_id = NULL;
//        $data_post = '{"Items":{"routes_by_route_id":{"4":{"route_type_id":"1","order_id":"66","chane_id":"715","route_title":"Маршрут номер 777","route_id":"4","offset_start":"0","offset_end":"0.7","status_id":"1","edgeIds":["22140","22141","22142"]},"22":{"route_type_id":"1","order_id":"66","chane_id":"715","route_title":"Маршрут номер 666","route_id":"22","offset_start":"0.7","offset_end":"0.9","status_id":"1","edgeIds":["22183","22184","22185","22186"]}}},"status":1,"errors":[],"warnings":["GetRouteData. Данные успешно переданы","GetRouteData. Входной массив данных{\"order_id\":59}","GetRouteData. Декодировал входные параметры","GetRouteData. Данные с фронта получены"]}';

        if ($data_post !== NULL && $data_post !== '') {
            $warnings[] = 'SaveRouteData. Данные успешно переданы';
            $warnings[] = 'SaveRouteData. Входной массив данных' . $data_post;
            try {
                //$post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                $warnings[] = 'SaveRouteData. Декодировал входные параметры';
                if (
                    property_exists($post_dec, 'Items')
                )                                                                                                    // Проверяем наличие в нем нужных нам полей
                {
                    $warnings[] = 'SaveRouteData. Данные с фронта получены';
                    $route_data = $post_dec->Items->routes_by_route_id;
                    $warnings[] = $post_dec->Items;
                    if (isset($post_dec->Items->deprecated_ids)) {
                        $route_deprecated_ids = $post_dec->Items->deprecated_ids;
                    }

                    /****************** Перебор данных из  Json-а для добавления в БД ******************/
                    foreach ($route_data as $route) {
                        if (count($route->edgeIds) < 2) {                                                               // проверяем на наличие не менее двух выработок в маршруте передвижения
                            throw new Exception('SaveRouteData. Переданы некорректные входные параметры');
                        }

                        $add_route = new Route();
                        $add_route->title = $route->route_title;
                        $add_route->route_type_id = $route->route_type_id;
                        $add_route->order_id = $route->order_id;
                        $add_route->offset_start = $route->offset_start;
                        $add_route->status_id = $route->status_id;
                        $add_route->offset_end = $route->offset_end;
                        $add_route->chane_id = $route->chane_id;

                        $order_id = $route->order_id;
                        /****************** проверка на сохранение ******************/
                        if ($add_route->save()) {
                            $warnings[] = 'SaveRouteData. Успешное сохранение  данных о маршруте';
                            $add_route->refresh();
                            $id_new_route = $add_route->id;
                        } else {
                            $errors[] = $add_route->errors;
                            throw new Exception('SaveRouteData. Ошибка при сохранении данных маршрута');
                        }
                        foreach ($route->edgeIds as $edge) {                                                            //формируем массив для добавления связки маршрута и выработок
                            $route_edges[] = [$id_new_route, $edge];
                        }
                        $warnings[] = 'SaveRouteData. Выбрано больше одной выработки, сохраняем';
                    }
                    $result_add_route_edges = Yii::$app->db->createCommand()
                        ->batchInsert('route_edge', ['route_id', 'edge_id'], $route_edges)//массовая вставка в БД
                        ->execute();
                    if ($result_add_route_edges != 0) {
                        $warnings[] = 'SaveRouteData. Связка маршрута и выработок успешно сохранена ';
                    } else {
                        throw new Exception('SaveRouteData. Ошибка при добавлении связки маршрута и выработок');
                    }
                    $Json_get_data = '{"order_id":' . $order_id . '}';
                    $found_route_get = self::GetRouteData($Json_get_data);
                    $save_route = $found_route_get['Items'];
                }
            } catch (Throwable $exception) {
                $errors[] = 'SaveRouteData. Исключение';
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
                $status *= 0;
            }
        } else {
            $errors[] = 'SaveRouteData. Данные с фронта не получены';
            $status *= 0;
        }
        $result = $save_route;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SaveRoute() - Сохраняем данные в таблицу маршрутов передвижения сотрудников к рабочему месту
     * при редактировании маршрута, старый ммаршрут помечаем как неактуальный, а новый записываем с 0
     * @param null routes - Массив со следующей структурой:
     *                             route_id           - ключ маршрута
     *                             route_type_id      - тип маршрута (шаблон, рабочий)
     *                             order_id           - наряд на который назначен даннй маршрут
     *                             chane_id           - звено для которого устанавливается маршрут
     *                             title              - название маршрута
     *                             edgeIds            - список эджей по которым строим маршрут
     *                                {edge_id}
     *                             offset_start       - смещение на эдже от начала для тех случаев когда маршрут строится с середины эджа
     *                             offset_end         - смещение на эдже от конца
     *
     * @return array - возвращает стандартный массив +результат выполнения метода получения
     * @package frontend\controllers\positioningsystem
     * @example http://localhost/read-manager-amicum?controller=positioningsystem\Route&method=SaveRoute&subscribe=&data={}
     *
     *
     * Тестовый набор данных: '{"Items":{"routes_by_route_id":{"4":{"route_type_id":"1","order_id":"59","chane_id":"715","route_title":"Маршрут номер 777","route_id":"4","offset_start":"0","offset_end":"0.7","status_id":"1","edgeIds":["22140","22141","22142"]},"22":{"route_type_id":"1","order_id":"59","chane_id":"715","route_title":"Маршрут номер 666","route_id":"22","offset_start":"0.7","offset_end":"0.9","status_id":"1","edgeIds":["22183","22184","22185","22186"]}},"deprecated_ids":[4]},"status":1,"errors":[],"warnings":["GetRouteData. Данные успешно переданы","GetRouteData. Входной массив данных{\"order_id\":59}","GetRouteData. Декодировал входные параметры","GetRouteData. Данные с фронта получены"]}'
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 02.08.2019 10:00
     */
    public static function SaveRoute($route_data)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $route_edges = array();
        $result = NULL;

        try {
            /****************** Перебор данных из  Json-а для добавления в БД ******************/
            foreach ($route_data as $route) {

                if (count($route->edgeIds) < 2) {                                                               // проверяем на наличие не менее двух выработок в маршруте передвижения
                    throw new Exception('SaveRoute. Переданы некорректные входные параметры');
                }
                $add_route = Route::findOne(['id' => $route->route_id]);
                if (!$add_route) {
                    $add_route = new Route();
                }
                $add_route->title = $route->title;
                $add_route->route_type_id = $route->route_type_id;
                $add_route->order_id = $route->order_id;
                $add_route->offset_start = $route->offset_start;
                $add_route->status_id = $route->status_id;
                $add_route->offset_end = $route->offset_end;
                $add_route->chane_id = $route->chane_id;
                /****************** проверка на сохранение ******************/
                if ($add_route->save()) {
                    $warnings[] = 'SaveRoute. Успешное сохранение  данных о маршруте';
                    $add_route->refresh();
                    $id_new_route = $add_route->id;
                } else {
                    $errors[] = $add_route->errors;
                    throw new Exception('SaveRoute. Ошибка при сохранении данных маршрута');
                }
                foreach ($route->edgeIds as $edge) {                                                            //формируем массив для добавления связки маршрута и выработок
                    $route_edges[] = [$id_new_route, $edge];
                }
                $warnings[] = 'SaveRoute. Выбрано больше одной выработки, сохраняем';
                RouteEdge::deleteAll(['route_id' => $route->route_id]);
            }
            if (!empty($route_edges)) {
                $result_add_route_edges = Yii::$app->db->createCommand()
                    ->batchInsert('route_edge', ['route_id', 'edge_id'], $route_edges)//массовая вставка в БД
                    ->execute();
                if ($result_add_route_edges != 0) {
                    $warnings[] = 'SaveRoute. Связка маршрута и выработок успешно сохранена ';
                } else {
                    throw new Exception('SaveRoute. Ошибка при добавлении связки маршрута и выработок');
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'SaveRoute. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод ChangeStatusRoute() - Изменяем статус маршрута на неактивный
     * @param null $data_post - Параметр номера маршрута - ID
     * @return array Сообщение, что статус маршрута изменен
     * @package frontend\controllers\positioningsystem
     * @example http://localhost/read-manager-amicum?controller=positioningsystem\Route&method=ChangeStatusRoute&subscribe=&data={%22id%22:30}
     *
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 07.08.2019 16:00
     */
    public static function ChangeStatusRoute($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'ChangeStatusRoute. Начал выполнять метод';
        if ($data_post !== NULL && $data_post !== '') {
            $warnings[] = 'ChangeStatusRoute. Данные успешно переданы';
            try {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                $warnings[] = 'ChangeStatusRoute. Декодировал входные параметры';
                if (
                    property_exists($post_dec, 'id') and
                    $post_dec->id != ""
                )                                                                                                    // Проверяем наличие в нем нужных нам полей
                {
                    $route_id = $post_dec->id;
                    $warnings[] = 'ChangeStatusRoute. Данные с фронта получены';                                            // Для получения параметра - $post_dec->имя параметра
                } else {
                    throw new Exception('ChangeStatusRoute. Переданы некорректные входные параметры');
                }
                /******************  Изменение статуса маршрута в бд ******************/
                $route = Route::findOne(['id' => $route_id]);
                if (!$route) {                                                                                          //если данные найдены тогда проебагаем по массиву
                    throw new Exception('ChangeStatusRoute. Не найден маршрут в БД');
                }
                if ($route->status_id === self::NOT_ACTIVE) {
                    throw new Exception('ChangeStatusRoute. Статус уже является "Неактивный"');
                }
                $route->status_id = self::NOT_ACTIVE;
                /****************** проверка на сохранение ******************/
                if ($route->save()) {
                    $warnings[] = 'ChangeStatusRoute. Успешное сохранение изменненого данных о маршруте';
                    $route->refresh();
                    //$id_new_route = $route->id;
                } else {
                    $errors[] = $route->errors;
                    throw new Exception('ChangeStatusRoute. Ошибка при сохранении изменении статуса маршрута');
                }
            } catch (Throwable $exception) {
                $errors[] = 'ChangeStatusRoute. Исключение';
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
                $status *= 0;
            }
        } else {
            $errors[] = 'ChangeStatusRoute. Данные с фронта не получены';
            $status *= 0;
        }
        $warnings[] = 'ChangeStatusRoute. Закончил выполнять метод';
        $result_main = array('Items' => 'id', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод EditRoute() - Редактируем данные в таблице маршрутов (редактирование маршрута)
     * @param null $data_post - Массив со следующей структурой:     [Items]:
     *                                                              [routes_by_route_id]
     *                                                                      [route_id]:
     *                                                                          [route_id]          - ключ маршрута
     *                                                                          [route_type_id]     - тип маршрута (шаблон, рабочий)
     *                                                                          [order_id]          - наряд на который назначен даннй маршрут
     *                                                                          [chane_id]          - звено для которого устанавливается маршрут
     *                                                                          [route_title]       - название маршрута
     *                                                                          [edgeIds]           - список эджей по которым строим маршрут
     *                                                                              edge_id:
     *                                                                          [offset_start]      - смещение на эдже от начала для тех случаев когда маршрут строится с середины эджа
     *                                                                          [offset_end]        - смещение на эдже от конца
     *                                                                  [deprecated_ids]            - айдишники маршрутов, помечаемые как не актуальные
     *
     *                                                               status:
     *                                                               [errors]
     *                                                               [warnings]
     *
     * @return array - возвращает стандартный измененный массив
     * @package frontend\controllers\positioningsystem
     * @example http://localhost/read-manager-amicum?controller=positioningsystem\route&method=EditRoute&subscribe=&data={}
     *
     *
     * Тестовый набор данных: {"Items":{"10":{"id":30,"title":"Типовой маршрут №24","offset_start":0.2,"offset_end":0.9,"chane_id":730,"edgeIds":[22252,22253,22254]}},"status":1,"errors":[],"warnings":{"0":"GetRouteData. Данные успешно переданы","1":"GetRouteData. Входной массив данных{\"chane_id\":718}","2":"GetRouteData. Декодировал входные параметры","3":"GetRouteData.Данные с фронта получены","sql":null}}
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 07.08.2019 11:00
     */
    public static function EditRoute($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $route_data = array();                                                                                            // Промежуточный результирующий массив
        $warnings[] = 'EditRoute. Начало работы метода';
        if ($data_post !== NULL && $data_post !== '') {
            $warnings[] = 'EditRoute. Данные успешно переданы';
            $warnings[] = 'EditRoute. Входной массив данных' . $data_post;
            try {
                $post_dec = json_decode('{"Items":{"id":30,"title":"Типовой маршрут №25","offset_start":0.2,"offset_end":0.9,"chane_id":730,"edgeIds":[22252,22253,22254]},"status":1,"errors":[],"warnings":{"0":"GetRouteData. Данные успешно переданы","1":"GetRouteData. Входной массив данных{\"chane_id\":718}","2":"GetRouteData. Декодировал входные параметры","3":"GetRouteData.Данные с фронта получены","sql":null}}');                                                                    // Декодируем входной массив данных
                $warnings[] = 'EditRoute. Декодировал входные параметры';
                $data_items = $post_dec->Items;
                if (
                    property_exists($data_items, 'id') and
                    property_exists($data_items, 'offset_start') and
                    property_exists($data_items, 'offset_end') and
                    property_exists($data_items, 'title') and
                    property_exists($data_items, 'chane_id') and
                    property_exists($data_items, 'route_type_id') and
                    property_exists($data_items, 'order_id') and
                    property_exists($data_items, 'edgeIds') and
                    $data_items->id != "" and
                    $data_items->offset_start != "" and
                    $data_items->offset_end != "" and
                    $data_items->title != "" and
                    $data_items->chane_id != "" and
                    $data_items->route_type_id != "" and
                    $data_items->order_id != ""
                )                                                                                                       // Проверяем наличие в нем нужных нам полей
                {
                    $route_id = $data_items->id;                                                                        //преобразование данных
                    $order_id = $data_items->order_id;
                    $route_type_id = $data_items->route_type_id;
                    $route_title = $data_items->title;
                    $route_offset_end = $data_items->offset_end;
                    $route_offset_start = $data_items->offset_start;
                    $route_chane_id = $data_items->chane_id;
                    $edges = $data_items->edgeIds;
                    $warnings[] = 'EditRoute. Данные с фронта получены';                                                // Для получения параметра - $post_dec->имя параметра
                } else {
                    throw new Exception('EditRoute. Переданы некорректные входные параметры');
                }
                /****************** поиск данных в бд ******************/
                $route = Route::findOne(['id' => $route_id]);
                if (!$route) {                                                                                          //если данные найдены тогда проебагаем по массиву
                    throw new Exception('EditRoute. Не найден маршрут в БД');
                }
                $route->title = $route_title;
                $route->order_id = $order_id;
                $route->route_type_id = $route_type_id;
                $route->offset_start = $route_offset_start;
                $route->offset_end = $route_offset_end;
                $route->chane_id = $route_chane_id;
                if ($route->save()) {                                                                                     //Сохранение
                    $warnings[] = 'EditRoute. Успешное сохранение отредактированных данных о маршруте';
                    $route->refresh();
                } else {
                    $errors[] = $route->errors;
                    throw new Exception('EditRoute. Ошибка при сохранении отредактированных данных маршрута');
                }

                /****************** Удаление выработок ******************/
                $status_delete = RouteEdge::DeleteAll(['route_id' => $route_id]);
                if (!$status_delete) {
                    $warnings[] = 'EditRoute. В базе данных данный маршрут пуст';
                }

                foreach ($edges as $edge) {                                                                             //заполнение индентификаторами выработок
                    $route_edge[] = [$route_id, $edge];
                }
                $result_add_route_edges = Yii::$app->db->createCommand()
                    ->batchInsert('route_edge', ['route_id', 'edge_id'], $route_edge)//массовая вставка в БД
                    ->execute();
                if ($result_add_route_edges != 0) {
                    $warnings[] = 'EditRoute. Связка маршрута и выработок успешно сохранена ';
                } else {
                    throw new Exception('EditRoute. Ошибка при добавлении связки маршрута и выработок');
                }
            } catch (Throwable $exception) {
                $errors[] = 'EditRoute. Исключение';
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
                $status *= 0;
            }
        } else {
            $errors[] = 'EditRoute. Данные с фронта не получены';
            $status *= 0;
        }
        $warnings[] = 'EditRoute. Успешное окончание выполнения метода';
        $result = $route_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetPlaceRoute() - Получаем привязку шаблонов маршрутов и мест
     * @param null $data_post - JSON массив с данными по идентификатору звена
     * @return array Массив со следующей структурой:
     *                                      route_data:
     *                                          {place_route_template_id}                         - ключ привязки шаблона маршрута и места
     *                                                      place_route_template_id                      - ключ привязки шаблона маршрута и места
     *                                                      route_template_id:                           - ключ шаблона маршрута
     *                                                      route_template_title:                        - наименование шаблона маршрута
     *                                                      place_id:                                    - ключ места
     *                                                      place_title:                                 - наименование места
     *                                      route_handbook
     *                                          {place_id}
     *                                                  place_id:
     *                                                  route_template_id:
     * @package frontend\controllers\positioningsystem
     * @example http://localhost/read-manager-amicum?controller=positioningsystem\Route&method=GetPlaceRoute&subscribe=&data={%22company_department_id%22:20023045}
     *
     * @author Якимов М.Н,
     * Created date: on 06.08.2019 15:54
     */
    public static function GetPlaceRoute($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        // Промежуточный результирующий массив
        if ($data_post !== NULL && $data_post !== '') {
            $warnings[] = 'GetPlaceRoute. Данные успешно переданы';
            $warnings[] = 'GetPlaceRoute. Входной массив данных' . $data_post;
            try {


                /****************** Поиск маршрута по условию ID-участка и если это шаблон  ******************/
                $found_data_route = PlaceRoute::find()//
                ->joinWith('place')
                    ->joinWith('routeTemplate')
                    ->limit(5000)
                    ->asArray()
                    ->all();
                /****************** если маршрут есть и это шаблон, то идет заполнение данных ******************/
                foreach ($found_data_route as $route_place) {
                    $route_data[$route_place['id']]['place_route_template_id'] = $route_place['id'];
                    $route_data[$route_place['id']]['route_template_id'] = $route_place['route_template_id'];
                    $route_data[$route_place['id']]['route_template_title'] = $route_place['routeTemplate']['title'];
                    $route_data[$route_place['id']]['place_id'] = $route_place['place_id'];
                    $route_data[$route_place['id']]['place_title'] = $route_place['place']['title'];

                    $route_handbook[$route_place['place_id']]['place_id'] = $route_place['place_id'];
                    $route_handbook[$route_place['place_id']]['route_template_id'] = $route_place['route_template_id'];
                }

                if (!isset($route_data)) {
                    $result['route_data'] = (object)array();
                } else {
                    $result['route_data'] = $route_data;
                }
                if (!isset($route_handbook)) {
                    $result['route_handbook'] = (object)array();
                } else {
                    $result['route_handbook'] = $route_handbook;
                }
            } catch (Throwable $exception) {
                $errors[] = 'GetPlaceRoute. Исключение';
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
                $status *= 0;
            }
        } else {
            $errors[] = 'GetPlaceRoute. Данные с фронта не получены';
            $status *= 0;
        }

        $warnings[] = 'GetPlaceRoute. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод SavePlaceRoute - Сохраняем привязку шаблона маршрута и места
     * при редактировании маршрута, старый ммаршрут помечаем как неактуальный, а новый записываем с 0
     * @param null $data_post - Массив со следующей структурой:
     *                          place_route:
     *                              [place_route_template_id]                         - ключ привязки шаблона маршрута и места
     *                                                      place_route_template_id                      - ключ привязки шаблона маршрута и места
     *                                                      route_template_id:                           - ключ шаблона маршрута
     *                                                      route_template_title:                        - наименование шаблона маршрута
     *                                                      place_id:                                    - ключ места
     *                                                      place_title:                                 - наименование места
     *
     * @return array - возвращает стандартный массив +результат выполнения метода получения
     * @package frontend\controllers\positioningsystem
     * @example http://localhost/read-manager-amicum?controller=positioningsystem\Route&method=SavePlaceRoute&subscribe=&data={}
     *
     *
     */
    public static function SavePlaceRoute($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'SavePlaceRoute';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
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

        $order_id = NULL;
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
            // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//                $date_time_debug_start, $date_time_debug_end, $log_id,
//                $duration_summary, $max_memory_peak, $count_all);
//            if ($response['status'] === 1) {
//                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
//            } else {
//                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
//            }

            /** Метод начало */
            $warnings[] = 'SavePlaceRoute. Начало метода';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SavePlaceRoute. Не переданы входные параметры');
            }
            $warnings[] = 'SavePlaceRoute. Данные успешно переданы';
            $warnings[] = 'SavePlaceRoute. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'SavePlaceRoute. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'place_route'))                                                         //
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }

            $warnings[] = 'SavePlaceRoute. Данные с фронта получены';
            $place_route = $post_dec->place_route;


            /****************** Перебор данных из  Json-а для добавления в БД ******************/


            $add_place_route = PlaceRoute::findOne(['id' => $place_route->place_route_template_id]);
            if (!$add_place_route) {
                $add_place_route = new PlaceRoute();
            }
            $add_place_route->place_id = $place_route->place_id;
            $add_place_route->route_template_id = $place_route->route_template_id;
            /****************** проверка на сохранение ******************/
            if ($add_place_route->save()) {
                $warnings[] = 'SavePlaceRoute. Успешное сохранение  данных о маршруте';
                $add_place_route->refresh();
                $place_route->place_route_template_id = $add_place_route->id;
            } else {
                $errors[] = $add_place_route->errors;
                throw new Exception('SavePlaceRoute. Ошибка при сохранении PlaceRoute');
            }

            $result = $place_route;
            /** Метод окончание */

        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
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
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result_main;
    }

    /**
     * Метод DeletePlaceRoute() -  удаление привязки шаблона маршрута и места
     * @param null $data_post - Массив со следующей структурой:
     *                  place_route_template_id                    - ключ привязки шаблона маршрута и места
     *
     * @return array - возвращает стандартный массив +результат выполнения метода получения
     * @package frontend\controllers\positioningsystem
     * @example http://localhost/read-manager-amicum?controller=positioningsystem\Route&method=DeletePlaceRoute&subscribe=&data={}
     *
     *
     * Якимов М.Н.
     * Created date: on 02.08.2019 10:00
     */
    public static function DeletePlaceRoute($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'DeletePlaceRoute';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
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
            // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//                $date_time_debug_start, $date_time_debug_end, $log_id,
//                $duration_summary, $max_memory_peak, $count_all);
//            if ($response['status'] === 1) {
//                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
//            } else {
//                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
//            }

            /** Метод начало */
            $warnings[] = 'DeletePlaceRoute. Начало метода';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('DeletePlaceRoute. Не переданы входные параметры');
            }
            $warnings[] = 'DeletePlaceRoute. Данные успешно переданы';
            $warnings[] = 'DeletePlaceRoute. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'DeletePlaceRoute. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'place_route_template_id'))                                                         //
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }

            $warnings[] = 'DeletePlaceRoute. Данные с фронта получены';
            $place_route_template_id = $post_dec->place_route_template_id;


            PlaceRoute::deleteAll(['id' => $place_route_template_id]);

            /** Метод окончание */

        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
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
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result_main;
    }

    // GetRouteTemplateList - справочник шаблонов маршрутов
    // company_department_id - фильтр
    // 127.0.0.1/read-manager-amicum?controller=positioningsystem\Route&method=GetRouteTemplateList&subscribe=&data={}
    public static function GetRouteTemplateList($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $result = array();                                                                                            // Массив предупреждений
        $method_name = "GetRouteTemplateList";                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'GetRouteTemplateList. Начало метода';
        try {
            /** Метод начало */
            $warnings[] = $method_name . '. Начало метода';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                property_exists($post_dec, 'company_department_id'))                                                         // период 'month/year'
            {
                $company_department_id = $post_dec->company_department_id;
            }
            $warnings[] = 'GetRouteTemplateList. Данные с фронта получены и они правильные';
            $route_template_all = RouteTemplate::find()
                ->orderBy('title')
                ->all();

            if (isset($company_department_id)) {
                foreach ($route_template_all as $route) {
                    if ($route['company_department_id'] == $company_department_id) {
                        $route_favorites[] = $route;
                    }
                }
            }

            if (isset($route_favorites)) {
                $result['department_route_templates'] = $route_favorites;
            } else {
                $result['department_route_templates'] = array();
            }

            if ($route_template_all) {
                $result['route_template_all'] = $route_template_all;
            } else {
                $result['route_template_all'] = array();
            }

        } catch (Throwable $exception) {
            $errors[] = 'GetRouteTemplateList. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetRouteTemplateList. Конец метода';

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetCompareRoute - метод сравнения маршрутов
    // входные данные:
    //      worker_id:                      - ключ работника                                        (фильтр)
    //      company_department_id:          - ключ департамента                                     (фильтр)
    //      date:                           - дата, на которую хотим получить маршрут работника     (обязательное поле)
    // выходной объект:
    // mines:   - содержит информацию о полноте обследования шахт
    //      [mine_id]           - ключ шахты
    //              mine_id                         - ключ шахты
    //              mine_title                      - название шахты
    //              count_all_edges                 - количество выработок всего
    //              count_explorer_edges            - количество обследованных выработок
    //              percent_count_explorer_edges    - процент от количества обследованных выработок
    //              length_all_edges                - длина всех выработок
    //              length_explorer_edges           - длина обследованных выработок
    //              percent_length_explorer_edges   - процент от длины обследованных выработок
    // workers:   - содержит информацию по каждому работнику по обследуемым маршрутам и их полноте
    //      {worker_id}         - ключ работника
    //          worker_id                       - ключ работника
    //          full_name                       - полное имя работника
    //          tabel_number                    - табельный номер
    //          position_id                     - ключ должности работника
    //          position_title                  - наименование должности работника
    //          company_department_id           - ключ подразделения работника
    //          company_title                   - наименование подразделения работника
    //          order_operation_description:    - отчет по наряду !!!! здесь объект!!!
    //          date_time_start:                - время начала маршрута
    //          date_time_end:                  - время окончания маршрута
    //          duration:                       - продолжительность в пути
    //          exist_deviation:                - флаг наличия отклонений (1 - есть отклонение, 0 - нет отклонения)
    //          count_coincidence               - количество совпадений
    //          count_deviation                 - количество отклонений
    //          count_full_route_point          - полное количество точек сравнения
    //          time_percentage_match:          - процент времени нахождения на маршруте - в процентах!!!
    //          edge_percentage_match:          - процент обследования маршрута по количеству ветвей
    //          edge_percentage_match_by_length - процент обследования маршрута по длине
    //          compare_routs:                  - сравнение маршрутов
    //              {date_time}                       - дата и время отметки
    //                  edge_id:                        - ключ маршрута
    //                  place_title:                    - название места
    //                  exist_deviation:                - флаг наличия отклонений (1 - есть отклонение, 0 - нет отклонения)
    //                  date_time:                      - дата отметки
    //                  length:                         - протяженность выработки
    //                  sensor_xyz:                     - координата работника
    //          route_title:                    - название маршрута !!!! Здесь объект !!!
    //              {order_place_id}                - ключ привязки места к наряду
    //                      route_title                 - название маршрута
    //          route_length:                   - протяженность маршрута в метрах
    //          route_fact_length:              - протяженность фактического маршрута в метрах
    //          route_fact_by_plan_length:      - протяженность фактического маршрута в метрах по плану
    //          route_plan:                     - плановый маршрут
    //              {edge_id}                       - ключ выработки
    //                  edge_id:                        - ключ выработки
    //                  place_id:                       - ключ места
    //                  place_title:                    - наименование места
    //                  length:                         - протяженность
    //          route_fact:                     - фактический маршрут
    //                  edge_id:                        - ключ выработки
    //                  place_id:                       - ключ места
    //                  place_title:                    - наименование места
    //                  length:                         - протяженность
    //          compare_gas:                    - дублирующий контроль газов по маршруту
    //                  event_compare_gas_id            - ключ сравнения газов
    //                  pdk_status                      - статус наличия превышения ПДК
    //                  sensor_id                       - ключ ЛУЧ-4
    //                  sensor_title                    - наименование ЛУЧ-4
    //                  sensor_xyz                      - координата светильника
    //                  event_date_time                 - время события
    //                  sensor_value                    - значение ЛУЧ-4 по CH4
    //                  unit_id                         - единицы измерения
    //                  edge_id                         - выработка индивидуального датчика - проверка по нему
    //                  place_id                        - ключ места
    //                  place_title                     - наименование места
    //                  sensor2_id                      - ключ стационара
    //                  sensor2_title                   - наименование стационара
    //                  sensor2_xyz                     - координата стационара
    //                  event_date_time2                - время стационара
    //                  sensor2_value                   - значение стационара по CH4
    //                  unit2_id                        - единицы измерения
    //                  edge2_id                        - выработка стационарного датчика
    //                  place2_id                       - ключ места
    //                  place2_title                    - наименование места
    //                  length:                         - протяженность выработки
    //                  exist_deviation:                - флаг наличия отклонений (1 - есть отклонение, 0 - нет отклонения !!!! с бека прилетает null)
    //                                                    сравнение наличия отклонения от маршрута осуществляеся путем проверки наличия эджа светильника в плановом маршруте
    // алгоритм:
    // 0. получить список входных параметров
    // 1. Получить маршруты и работников в нем по наряду (если задан работник, то по нему)
    //      а. получить дату
    //      б. найти все наряды на все смены, в которых есть обозначенный сотрудник(если задан фильтр)
    //          - сохранить смены в отдельный объект - нужен для определения времени старта и окончания выборки по фактическому маршруту
    //          - сохранить список работников найденных в нарядах с привязанными маршрутами
    //          - сохранить комментарии к наряду в один объект
    //      в. в найденных нарядах найти привязку работника к операции, а операции к месту
    //      г. найти все привязанные к месту актуальные маршруты
    //      д. если маршрутов несколько, то объединить в один
    // 2. получить справочник длин эджей
    // 3. Сформировать плановый выходной объект где человек мог находиться.
    // 4. На основании полученных смен, получить дату начала - 2 часа и дату окончания + 2 часа - период получения фактического маршрута
    // 5. Получить фактический маршрут за период из п.2
    // 6. Дополнить плановый выходной объект фактическими данными
    //      а. если edge_id  существовал в плановом маршруте, то ставим отметку "по маршруту"
    //      б. если нет, то создаем структуру и ставим отметку "отклонение от маршрута" на данном edge и меняем статус по работнику "имеется отклонение по маршруту"
    // 7. Вычисляем длину маршрута фактическую
    // 8. Вычисляем процент совпадения с маршрутом
    // 9. Вычисляем суммарное время в пути
    // 10. находим привязку привязку светильника к шахтера на обозначенную дату
    // 11. по заданному lamp_sensor_id получаем расхождения показаний газов на искомое время
    // 12. заполняем ФИО и прочие данные по работнику
    // 13. Получаем статистику по обследованию всей шахты
    // 14. отправляем выходной объект на фронт
    // Пример: http://127.0.0.1/read-manager-amicum?controller=positioningsystem\Route&method=GetCompareRoute&subscribe=&data={"company_department_id":NULL,"worker_id":NULL,"date":"2020-02-02"}
    // Разработал: Якимов М.Н.
    public static function GetCompareRoute($data_post = NULL)
    {
//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', '5000M');
//        ini_set('mysqlnd.connect_timeout', 1440000);
//        ini_set('default_socket_timeout', 1440000);
//        ini_set('mysqlnd.net_read_timeout', 1440000);
//        ini_set('mysqlnd.net_write_timeout', 1440000);

        // Стартовая отладочная информация
        $method_name = 'GetCompareRoute';                                                                               // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта
        $worker_array = array();

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив работникам
        $status = 1;                                                                                                    // статус выполнения скрипта

        try {

            $count_shifts = \frontend\controllers\Assistant::GetCountShifts();

            /** Отладка */
            $description = 'Начало выполнение метода';                                                                  // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            // запись в БД начала выполнения скрипта
            // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//                $date_time_debug_start, $date_time_debug_end, $log_id,
//                $duration_summary, $max_memory_peak, $count_all);
//            if ($response['status'] === 1) {
//                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
//            } else {
//                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
//            }

            /** Метод начало */
            $warnings[] = $method_name . '. Начало метода';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'date') ||                                                          // ключ департамента
                !property_exists($post_dec, 'company_department_id') ||                                         // год
                !property_exists($post_dec, 'worker_id'))                                                       // период 'month/year'
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            // 0. получить список входных параметров
            $company_department_id = $post_dec->company_department_id;                                                  // ключ департамента для которого строим сравнение маршрутов
            $worker_id = $post_dec->worker_id;                                                                          // ключ работника по которому строим сравнение маршрутов
            $date = date('Y-m-d', strtotime($post_dec->date));                                                   // дата на которую строим маршруты
            $workers = array();                                                                                         // список работников для которых строится сравнение маршрутов
            $shifts = array();                                                                                          // список смен по которым получаем фактический маршрут

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

            // 1. Получить маршруты и работников в нем по наряду (если задан работник, то по нему)
            //      а. получить дату
            //      б. найти все наряды на все смены, в которых есть обозначенный сотрудник(если задан фильтр)
            //          - сохранить смены в отдельный объект - нужен для определения времени старта и окончания выборки по фактическому маршруту
            //          - сохранить список работников найденных в нарядах с привязанными маршрутами
            //          - сохранить комментарии к наряду в один объект
            //      в. в найденных нарядах найти привязку работника к операции, а операции к месту
            //      г. найти все привязанные к месту актуальные маршруты
            //      д. если маршрутов несколько, то объединить в один
            $order_routes = (new Query())
                ->select('
                    order.shift_id as shift_id,
                    operation_worker.worker_id as worker_id,
                    order_operation.description as description,
                    route_template_edge.edge_id as edge_id,
                    route_template.title as route_title,
                    order_place.id as order_place_id,
                    place.id as place_id,
                    place.title as place_title
                ')
                ->from('operation_worker')
                ->innerJoin('order_operation', 'order_operation.id=operation_worker.order_operation_id')
                ->innerJoin('order_place', 'order_place.id=order_operation.order_place_id')
                ->innerJoin('order', 'order.id=order_place.order_id')
//                ->innerJoin('place_route', 'order_place.place_id=place_route.place_id')
                ->innerJoin('route_template', 'order_place.route_template_id=route_template.id')
                ->innerJoin('route_template_edge', 'route_template.id=route_template_edge.route_template_id')
                ->innerJoin('edge', 'edge.id=route_template_edge.edge_id')
                ->innerJoin('place', 'place.id=edge.place_id')
                ->where(['order.date_time_create' => $date])
//                ->andFilterWhere(['order.company_department_id' => $company_departments])
                ->andFilterWhere(['in', 'order.company_department_id', $company_departments])
                ->andFilterWhere(['order_operation.worker_id' => $worker_id])
                ->groupBy('order_place_id, shift_id, worker_id, edge_id, place_id, place_title, description,route_title')
                ->all();

            /** Отладка */
            $description = 'Получил маршруты';                                                                          // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            // 2. получить справочник длин эджей
            // метод для заполнения длин эджей: http://127.0.0.1/super-test/update-edge-length
            $edge_length = (new Query())
                ->select('edge_id, value as edge_length')
                ->from('view_edge_parameter_handbook_151_last')
                ->indexBy('edge_id')
                ->all();
            if (!$edge_length) {
                $edge_length = array();
            }

            /** Отладка */
            $description = 'Получил справочник длин эджей';                                                             // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


            // получить историю выработок на интересующую дату
            $edge_status = (new Query())
                ->select('edge.id as id, edge_status.status_id, place.mine_id as mine_id')
                ->from('edge')
                ->innerJoin('edge_status', 'edge_status.edge_id=edge.id')
                ->innerJoin('place', 'place.id=edge.place_id')
                ->innerJoin('(select max(date_time) as date_time_to_date, edge_id from edge_status where date_time<="' . $date . '" group by edge_id) edge_status_to_date', 'edge_status_to_date.edge_id=edge_status.edge_id and edge_status_to_date.date_time_to_date=edge_status.date_time')
                ->where(['edge_status.status_id' => 1])
                ->indexBy('id')
                ->all();

//            $warnings[]=$edge_status;
            /** Отладка */
            $description = 'Получил историю эджей на искомую дату';                                                             // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            // 3. получить справочник мест
            $edge_handbook = (new Query())
                ->select('
                    edge.id as edge_id, 
                    place.title as place_title, 
                    place.id as place_id, 
                    place.mine_id as mine_id
                ')
                ->from('edge')
                ->innerJoin('place', 'place.id=edge.place_id')
                ->indexBy('edge_id')
                ->all();

            if (!$edge_handbook) {
                $edge_handbook = array();
            }
            /** Отладка */
            $description = 'Получил справочник мест';                                                                   // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            // 3. Сформировать плановый выходной объект где человек мог находиться.
            // route_plan:                     - плановый маршрут
            //    //              {edge_id}                       - ключ выработки
            //    //                  edge_id:                        - ключ выработки
            //    //                  place_id:                       - ключ места
            //    //                  place_title:                    - наименование места
            //    //                  length:                         - протяженность
            $shifts_workers = [];
            // exist_deviation:                - флаг наличия отклонений (1 - есть отклонение, 0 - нет отклонения)
            foreach ($order_routes as $worker_edge) {
                if (isset($edge_status[$worker_edge['edge_id']])) {
                    $worker_id = $worker_edge['worker_id'];
                    $edge_id = $worker_edge['edge_id'];
                    $compare_route[$worker_id]['duration'] = 0;
                    $compare_route[$worker_id]['route_plan'][$edge_id]['edge_id'] = $edge_id;
                    $compare_route[$worker_id]['route_plan'][$edge_id]['place_id'] = $worker_edge['place_id'];
                    $compare_route[$worker_id]['route_plan'][$edge_id]['place_title'] = $worker_edge['place_title'];
                    if (isset($edge_length[$edge_id]) and $edge_length[$edge_id]['edge_length'] != "-1" and $edge_length[$edge_id]['edge_length'] != 'empty') {
                        $compare_route[$worker_id]['route_plan'][$edge_id]['length'] = round($edge_length[$edge_id]['edge_length'], 1);
                    } else {
                        $compare_route[$worker_id]['route_plan'][$worker_edge['edge_id']]['length'] = 0;
                    }

                    if (!isset($compare_route[$worker_id]['exist_deviation'])) {
                        $compare_route[$worker_id]['exist_deviation'] = 0;                                              // если будет фактический edge_id которого нет в плане, то флаг поднимается
                        $compare_route[$worker_id]['edge_percentage_match'] = 0;
                        $compare_route[$worker_id]['edge_percentage_match_by_length'] = 0;
                        $compare_route[$worker_id]['route_fact_by_plan_length'] = 0;
                    }
                    if (!isset($compare_route[$worker_id]['description'])) {
                        $compare_route[$worker_id]['order_operation_description'] = array();
                    }
                    if ($worker_edge['description'] and $worker_edge['description'] != "") {
                        $compare_route[$worker_id]['order_operation_description'][$worker_edge['description']] = $worker_edge['description'];
                    }

                    if ($worker_edge['route_title'] and $worker_edge['route_title'] != "") {
                        $compare_route[$worker_id]['route_title'][$worker_edge['order_place_id']] = $worker_edge['route_title'];
//                    $compare_route[$worker_id]['order_place_id'] = $worker_edge['order_place_id'];
                    }

                }
                $workers[$worker_id]['worker_id'] = $worker_id;
                $shifts[$worker_edge['shift_id']]['shift_id'] = $worker_edge['shift_id'];
                $shifts_workers[$worker_id]['shifts'][$worker_edge['shift_id']]['shift_id'] = $worker_edge['shift_id'];

            }

            unset($order_routes);
            foreach ($workers as $worker) {
                $worker_array[] = $worker['worker_id'];

                $warnings[] = $shifts_workers[$worker['worker_id']];
                if ($count_shifts == 3) {
                    if (isset($shifts_workers[$worker['worker_id']]['shifts'][1])) {
                        if (!isset($shifts_workers[$worker['worker_id']]['date_time_start'])) {
                            $shifts_workers[$worker['worker_id']]['date_time_start'] = strtotime($date . ' 06:00:00');
                            $shifts_workers[$worker['worker_id']]['date_time_start_format'] = $date . ' 06:00:00';
                        }

                        $shifts_workers[$worker['worker_id']]['date_time_end'] = strtotime($date . ' 18:00:00');
                        $shifts_workers[$worker['worker_id']]['date_time_end_format'] = $date . ' 18:00:00';

                    }
                    if (isset($shifts_workers[$worker['worker_id']]['shifts'][2])) {
                        if (!isset($shifts_workers[$worker['worker_id']]['date_time_start'])) {
                            $shifts_workers[$worker['worker_id']]['date_time_start'] = strtotime($date . ' 14:00:00');
                            $shifts_workers[$worker['worker_id']]['date_time_start_format'] = $date . ' 14:00:00';
                        }
                        $shifts_workers[$worker['worker_id']]['date_time_end'] = strtotime(date('Y-m-d', strtotime($post_dec->date . '+1day')) . ' 02:00:00');
                        $shifts_workers[$worker['worker_id']]['date_time_end_format'] = date('Y-m-d', strtotime($post_dec->date . '+1day')) . ' 02:00:00';
                    }

                    if (isset($shifts_workers[$worker['worker_id']]['shifts'][3])) {
                        if (!isset($shifts_workers[$worker['worker_id']]['date_time_start'])) {
                            $shifts_workers[$worker['worker_id']]['date_time_start'] = strtotime($date . ' 22:00:00');
                            $shifts_workers[$worker['worker_id']]['date_time_start_format'] = $date . ' 22:00:00';
                        }
                        $shifts_workers[$worker['worker_id']]['date_time_end'] = strtotime(date('Y-m-d', strtotime($post_dec->date . '+1day')) . ' 10:00:00');
                        $shifts_workers[$worker['worker_id']]['date_time_end_format'] = date('Y-m-d', strtotime($post_dec->date . '+1day')) . ' 10:00:00';
                    }

                } else {
                    if (isset($shifts_workers[$worker['worker_id']]['shifts'][1])) {
                        if (!isset($shifts_workers[$worker['worker_id']]['date_time_start'])) {
                            $shifts_workers[$worker['worker_id']]['date_time_start'] = strtotime($date . ' 06:00:00');
                            $shifts_workers[$worker['worker_id']]['date_time_start_format'] = $date . ' 06:00:00';
                        }

                        $shifts_workers[$worker['worker_id']]['date_time_end'] = strtotime($date . ' 16:00:00');
                        $shifts_workers[$worker['worker_id']]['date_time_end_format'] = $date . ' 16:00:00';

                    }
                    if (isset($shifts_workers[$worker['worker_id']]['shifts'][2])) {
                        if (!isset($shifts_workers[$worker['worker_id']]['date_time_start'])) {
                            $shifts_workers[$worker['worker_id']]['date_time_start'] = strtotime($date . ' 12:00:00');
                            $shifts_workers[$worker['worker_id']]['date_time_start_format'] = $date . ' 12:00:00';
                        }
                        $shifts_workers[$worker['worker_id']]['date_time_end'] = strtotime($date . ' 22:00:00');
                        $shifts_workers[$worker['worker_id']]['date_time_end_format'] = $date . ' 22:00:00';
                    }

                    if (isset($shifts_workers[$worker['worker_id']]['shifts'][3])) {
                        if (!isset($shifts_workers[$worker['worker_id']]['date_time_start'])) {
                            $shifts_workers[$worker['worker_id']]['date_time_start'] = strtotime($date . ' 18:00:00');
                            $shifts_workers[$worker['worker_id']]['date_time_start_format'] = $date . ' 18:00:00';
                        }
                        $shifts_workers[$worker['worker_id']]['date_time_end'] = strtotime(date('Y-m-d', strtotime($post_dec->date . '+1day')) . ' 04:00:00');
                        $shifts_workers[$worker['worker_id']]['date_time_end_format'] = date('Y-m-d', strtotime($post_dec->date . '+1day')) . ' 04:00:00';
                    }
                    if (isset($shifts_workers[$worker['worker_id']]['shifts'][4])) {
                        if (!isset($shifts_workers[$worker['worker_id']]['date_time_start'])) {
                            $shifts_workers[$worker['worker_id']]['date_time_start'] = strtotime(date('Y-m-d', strtotime($post_dec->date . '+1day')) . ' 00:00:00');
                            $shifts_workers[$worker['worker_id']]['date_time_start_format'] = date('Y-m-d', strtotime($post_dec->date . '+1day')) . ' 00:00:00';
                        }
                        $shifts_workers[$worker['worker_id']]['date_time_end'] = strtotime(date('Y-m-d', strtotime($post_dec->date . '+1day')) . ' 10:00:00');
                        $shifts_workers[$worker['worker_id']]['date_time_end_format'] = date('Y-m-d', strtotime($post_dec->date . '+1day')) . ' 10:00:00';
                    }
                }
            }

//            $warnings[]= '$shifts_workers';
//            $warnings[]= $shifts_workers;

            /** Отладка */
            $description = 'Сформировал плановый маршрут';                                                              // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;    // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            // 4. На основании полученных смен, получить дату начала - 2 часа и дату окончания + 2 часа - период получения фактического маршрута
            if ($count_shifts == 3) {
                if (isset($shifts[1])) {
                    if (!isset($date_time_start)) {
                        $date_time_start = $date . ' 06:00:00';
                    }

                    $date_time_end = $date . ' 18:00:00';
                }

                if (isset($shifts[2])) {
                    if (!isset($date_time_start)) {
                        $date_time_start = $date . ' 14:00:00';
                    }
                    $date_time_end = date('Y-m-d', strtotime($post_dec->date . '+1day')) . ' 02:00:00';
                }

                if (isset($shifts[3])) {
                    if (!isset($date_time_start)) {
                        $date_time_start = $date . ' 22:00:00';
                    }
                    $date_time_end = date('Y-m-d', strtotime($post_dec->date . '+1day')) . ' 10:00:00';
                }
            } else {
                if (isset($shifts[1])) {
                    if (!isset($date_time_start)) {
                        $date_time_start = $date . ' 06:00:00';
                    }

                    $date_time_end = $date . ' 16:00:00';

                }
                if (isset($shifts[2])) {
                    if (!isset($date_time_start)) {
                        $date_time_start = $date . ' 12:00:00';
                    }
                    $date_time_end = $date . ' 22:00:00';
                }

                if (isset($shifts[3])) {
                    if (!isset($date_time_start)) {
                        $date_time_start = $date . ' 18:00:00';
                    }
                    $date_time_end = date('Y-m-d', strtotime($post_dec->date . '+1day')) . ' 04:00:00';
                }
                if (isset($shifts[4])) {
                    if (!isset($date_time_start)) {
                        $date_time_start = date('Y-m-d', strtotime($post_dec->date . '+1day')) . ' 00:00:00';
                    }
                    $date_time_end = date('Y-m-d', strtotime($post_dec->date . '+1day')) . ' 10:00:00';
                }
            }

            if (isset($date_time_start) and isset($date_time_end)) {
                $warnings[] = $method_name . '. Дата начала ' . $date_time_start;
                $warnings[] = $method_name . '. Дата окончания ' . $date_time_end;
            } else {
                $warnings[] = $method_name . '. нет ни одной смены, т.к. нет ни одного наряда';
            }
//            $warnings[]= '$count_shifts';
//            $warnings[]= $count_shifts;

            /** Отладка */
            $description = 'Посчитал дату начала и конца';                                                              // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;  // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            // 5. Получить фактический маршрут за период из п.2
            if (isset($worker_array) and isset($date_time_start) and isset($date_time_end)) {
                $route_fact = WorkerParameterValue::find()
                    ->select('
                        worker_object.worker_id as worker_id,
                        worker_parameter.parameter_id as parameter_id,
                        worker_parameter.parameter_type_id as parameter_type_id,
                        worker_parameter.worker_object_id as worker_object_id,
                        worker_parameter_value.date_time as date_time,
                        worker_parameter_value.value as value,
                        worker_parameter_value.status_id as status_id,
                    ')
//                    ->innerJoinWith('workerParameter.workerObject')
                    ->innerJoin('worker_parameter', 'worker_parameter.id=worker_parameter_value.worker_parameter_id')
                    ->innerJoin('worker_object', 'worker_object.id=worker_parameter.worker_object_id')
                    ->where(['in', 'worker_id', $worker_array])
                    ->andWhere(
                        ['parameter_id' => [83, 269], 'parameter_type_id' => 2]
                    )
                    ->andWhere(['>=', 'date_time', $date_time_start])
                    ->andWhere(['<=', 'date_time', $date_time_end])
                    ->asArray()
                    ->orderBy(['date_time' => SORT_ASC])
                    ->all();

                $route_fact_history = WorkerParameterValueHistory::find()
                    ->select('
                        worker_object.worker_id as worker_id,
                        worker_parameter.parameter_id as parameter_id,
                        worker_parameter.parameter_type_id as parameter_type_id,
                        worker_parameter.worker_object_id as worker_object_id,
                        worker_parameter_value_history.date_time as date_time,
                        worker_parameter_value_history.value as value,
                        worker_parameter_value_history.status_id as status_id,
                    ')
//                    ->innerJoinWith('workerParameter.workerObject')
                    ->innerJoin('worker_parameter', 'worker_parameter.id=worker_parameter_value_history.worker_parameter_id')
                    ->innerJoin('worker_object', 'worker_object.id=worker_parameter.worker_object_id')
                    ->where(['in', 'worker_id', $worker_array])
                    ->andWhere(
                        ['parameter_id' => [83, 269], 'parameter_type_id' => 2]
                    )
                    ->andWhere(['>=', 'date_time', $date_time_start])
                    ->andWhere(['<=', 'date_time', $date_time_end])
                    ->asArray()
                    ->orderBy(['date_time' => SORT_ASC])
                    ->all();

                if ($route_fact_history) {
                    $route_fact = array_merge($route_fact, $route_fact_history);
                }

                /** Отладка */
                $description = 'Получил фактический маршрут';                                                           // описание текущей отладочной точки
                $description = $method_name . ' ' . $description;
                $warnings[] = $description;                                                                             // описание текущей отладочной точки
                $debug['description'][] = $description;                                                                 // описание текущей отладочной точки
                $max_memory_peak = memory_get_peak_usage() / 1024;                                                      // текущее пиковое значение использованной памяти
                $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                        // текущее пиковое значение использованной памяти
                $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                    // текущее количество использованной памяти
                $duration_summary = round(microtime(true) - $microtime_start, 6);                 // общая продолжительность выполнения скрипта
                $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                   // итоговая продолжительность выполнения скрипта
                $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
                $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
                $microtime_current = microtime(true);
                /** Окончание отладки */

                foreach ($route_fact as $worker) {
                    $route_fact_group[$worker['parameter_id']][] = $worker;
                }
                unset($route_fact);

                /** Отладка */
                $description = 'Сгруппировал фактический маршрут по 269 и 83';                                          // описание текущей отладочной точки
                $description = $method_name . ' ' . $description;
                $warnings[] = $description;                                                                             // описание текущей отладочной точки
                $debug['description'][] = $description;                                                                 // описание текущей отладочной точки
                $max_memory_peak = memory_get_peak_usage() / 1024;                                                      // текущее пиковое значение использованной памяти
                $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                        // текущее пиковое значение использованной памяти
                $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                    // текущее количество использованной памяти
                $duration_summary = round(microtime(true) - $microtime_start, 6);                 // общая продолжительность выполнения скрипта
                $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                   // итоговая продолжительность выполнения скрипта
                $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;    // продолжительность выполнения текущего куска кода
                $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;             // количество обработанных записей
                $microtime_current = microtime(true);
                /** Окончание отладки */

//                $warnings[] = $route_fact;
                //          route_fact:                     - фактический маршрут
                //                  edge_id:                        - ключ выработки
                //                  place_id:                       - ключ места
                //                  place_title:                    - наименование места
                //                  length:                         - протяженность
                // 6. Дополнить плановый выходной объект фактическими данными
                //      а. если edge_id  существовал в плановом маршруте, то ставим отметку "по маршруту"
                //      б. если нет, то создаем структуру и ставим отметку "отклонение от маршрута" на данном edge и меняем статус по работнику "имеется отклонение по маршруту"
                if (isset($route_fact_group[269])) {
                    foreach ($route_fact_group[269] as $worker_edge) {
                        $worker_id = $worker_edge['worker_id'];
                        $edge_id = $worker_edge['value'];
                        $point_date_time = strtotime($worker_edge['date_time']);
                        if (
                            isset($shifts_workers[$worker_id]['date_time_start']) and
                            isset($shifts_workers[$worker_id]['date_time_end']) and
                            $point_date_time > $shifts_workers[$worker_id]['date_time_start'] and
                            $point_date_time < $shifts_workers[$worker_id]['date_time_end']
                        ) {
                            $compare_route[$worker_id]['route_fact'][$edge_id]['edge_id'] = $edge_id;
                            if (isset($edge_handbook[$edge_id])) {
                                $compare_route[$worker_id]['route_fact'][$edge_id]['place_id'] = $edge_handbook[$edge_id]['place_id'];
                                $compare_route[$worker_id]['route_fact'][$edge_id]['place_title'] = $edge_handbook[$edge_id]['place_title'];
                            } else {
                                $compare_route[$worker_id]['route_fact'][$edge_id]['place_id'] = null;
                                $compare_route[$worker_id]['route_fact'][$edge_id]['place_title'] = "";
                            }
                            if (isset($edge_length[$edge_id]) and $edge_length[$edge_id]['edge_length'] != "-1" and $edge_length[$edge_id]['edge_length'] != 'empty') {
                                $compare_route[$worker_id]['route_fact'][$edge_id]['length'] = round($edge_length[$edge_id]['edge_length'], 1);
                            } else {
                                $compare_route[$worker_id]['route_fact'][$edge_id]['length'] = 0;
                            }

                            $route_date_time = $worker_edge['date_time'];
                            $compare_route[$worker_id]['date_time_end'] = $route_date_time;
                            if (!isset($compare_route[$worker_id]['date_time_start'])) {
                                if (isset($compare_route[$worker_id]['route_plan'][$edge_id])) {
                                    $compare_route[$worker_id]['date_time_start'] = $route_date_time;
                                }
                                if (!isset($compare_route[$worker_id]['exist_deviation'])) {
                                    $compare_route[$worker_id]['exist_deviation'] = 0;
                                }
                                if (!isset($compare_route[$worker_id]['count_deviation'])) {
                                    $compare_route[$worker_id]['count_deviation'] = 0;                                          // количество отклонений
                                }
                                if (!isset($compare_route[$worker_id]['count_coincidence'])) {
                                    $compare_route[$worker_id]['count_coincidence'] = 0;                                        // количество совподений
                                }
                                if (!isset($compare_route[$worker_id]['count_full_route_point'])) {
                                    $compare_route[$worker_id]['count_full_route_point'] = 0;                                   // полное количество точек сравнения
                                }

                            }

//                        // 9. Вычисляем суммарное время в пути
//                        $compare_route[$worker_id]['duration'] = round((strtotime($compare_route[$worker_id]['date_time_end']) - strtotime($compare_route[$worker_id]['date_time_start'])) / 60, 1);

                            if (!isset($compare_route[$worker_id]['route_plan'][$edge_id])) {                               // если будет фактический edge_id которого нет в плане, то флаг поднимается
                                $compare_route[$worker_id]['exist_deviation'] = 1;
                                $compare_route[$worker_id]['count_deviation']++;                                            // количество отклонений
                                $compare_route[$worker_id]['compare_routs'][$route_date_time]['exist_deviation'] = 1;       // отклонения имеется
                            } else {
                                $compare_route[$worker_id]['count_coincidence']++;                                          // количество попаданий
                                $compare_route[$worker_id]['compare_routs'][$route_date_time]['exist_deviation'] = 0;       // отклонений нет
                                // 9. Вычисляем суммарное время в пути
                                if (isset($compare_route[$worker_id]['date_time_start'])) {
                                    $compare_route[$worker_id]['duration'] = round((strtotime($compare_route[$worker_id]['date_time_end']) - strtotime($compare_route[$worker_id]['date_time_start'])) / 60, 1);
                                }
                            }
                            $compare_route[$worker_id]['count_full_route_point'] = $compare_route[$worker_id]['count_coincidence'] + $compare_route[$worker_id]['count_deviation'];
                            $compare_route[$worker_id]['compare_routs'][$route_date_time]['edge_id'] = $edge_id;
                            if (isset($edge_handbook[$edge_id])) {
                                $compare_route[$worker_id]['compare_routs'][$route_date_time]['place_title'] = $edge_handbook[$edge_id]['place_title'];
                            } else {
                                $compare_route[$worker_id]['compare_routs'][$route_date_time]['place_title'] = "";
                            }
                            $compare_route[$worker_id]['compare_routs'][$route_date_time]['date_time'] = $route_date_time;


                            $compare_route[$worker_id]['time_percentage_match'] = round(($compare_route[$worker_id]['count_coincidence'] / $compare_route[$worker_id]['count_full_route_point']) * 100, 1);
                            if (isset($edge_length[$edge_id]) and $edge_length[$edge_id]['edge_length'] != "-1" and $edge_length[$edge_id]['edge_length'] != 'empty') {
                                $compare_route[$worker_id]['compare_routs'][$route_date_time]['length'] = round($edge_length[$edge_id]['edge_length'], 1);
                            } else {
                                $compare_route[$worker_id]['compare_routs'][$route_date_time]['length'] = 0;
                            }

                            // 7. Вычисляем длину маршрута фактическую
                            // 8. Вычисляем процент совпадения с маршрутом
                            $count_plan_edge = count($compare_route[$worker_id]['route_plan']);
                            $count_coincidence = 0;
                            $compare_route[$worker_id]['route_length'] = 0;
                            foreach ($compare_route[$worker_id]['route_plan'] as $edge) {
                                if (isset($compare_route[$worker_id]['route_fact'][$edge['edge_id']])) {
                                    $count_coincidence++;
                                }
                                $compare_route[$worker_id]['route_length'] += $edge['length'];
                            }
                            $compare_route[$worker_id]['edge_percentage_match'] = round(($count_coincidence / $count_plan_edge) * 100, 1);
                            $compare_route[$worker_id]['route_length'] = round($compare_route[$worker_id]['route_length'], 1);

                            $compare_route[$worker_id]['route_fact_length'] = 0;
                            $compare_route[$worker_id]['route_fact_by_plan_length'] = 0;
                            if (isset($compare_route[$worker_id]['route_fact'])) {
                                foreach ($compare_route[$worker_id]['route_fact'] as $edge) {
                                    $compare_route[$worker_id]['route_fact_length'] += $edge['length'];
                                    if (isset($compare_route[$worker_id]['route_plan'][$edge['edge_id']])) {
                                        $compare_route[$worker_id]['route_fact_by_plan_length'] += $edge['length'];
                                    }
                                }
                                $compare_route[$worker_id]['route_fact_length'] = round($compare_route[$worker_id]['route_fact_length'], 1);
                            }

                            if (
                                isset($compare_route[$worker_id]['route_length']) and
                                isset($compare_route[$worker_id]['route_fact_length']) and
                                $compare_route[$worker_id]['route_length'] != 0 and
                                $compare_route[$worker_id]['route_fact_length'] != 0
                            ) {
                                $compare_route[$worker_id]['edge_percentage_match_by_length'] = round(($compare_route[$worker_id]['route_fact_by_plan_length'] / $compare_route[$worker_id]['route_length']) * 100, 1);
                            }
                        }
                    }
                }

                // в системе позиционирования есть касяк, в какой то момент пишется координата, при этом edge_id не записывается
                if (isset($route_fact_group[83])) {
                    foreach ($route_fact_group[83] as $worker) {
                        if (isset($compare_route[$worker['worker_id']]['compare_routs'][$worker['date_time']])) {
//                            if (!isset($compare_route[$worker_id]['compare_routs'][$route_date_time])) {
//                                $compare_route[$worker_id]['compare_routs'][$route_date_time]['edge_id'] = -1;
//                                $compare_route[$worker_id]['compare_routs'][$route_date_time]['place_title'] = "";
//                                $compare_route[$worker_id]['compare_routs'][$route_date_time]['length'] = 0;
//                                $compare_route[$worker_id]['compare_routs'][$route_date_time]['exist_deviation'] = 1;
//                            }
                            $compare_route[$worker['worker_id']]['compare_routs'][$worker['date_time']]['sensor_xyz'] = $worker['value'];
                            $compare_route[$worker['worker_id']]['compare_routs'][$worker['date_time']]['date_time'] = $worker['date_time'];
                        }
                    }
                }


                /** Отладка */
                $description = 'Сравнил маршруты';                                                                      // описание текущей отладочной точки
                $description = $method_name . ' ' . $description;
                $warnings[] = $description;                                                                             // описание текущей отладочной точки
                $debug['description'][] = $description;                                                                 // описание текущей отладочной точки
                $max_memory_peak = memory_get_peak_usage() / 1024;                                                      // текущее пиковое значение использованной памяти
                $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                        // текущее пиковое значение использованной памяти
                $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                    // текущее количество использованной памяти
                $duration_summary = round(microtime(true) - $microtime_start, 6);                 // общая продолжительность выполнения скрипта
                $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                   // итоговая продолжительность выполнения скрипта
                $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
                $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
                $microtime_current = microtime(true);
                /** Окончание отладки */

                // 10. находим привязку привязку светильника к шахтера на обозначенную дату
                //  - находим максимальную дату по привязке светильников
                //  - находим все привязки между датами
                //  - соединяем в единый массив для запроса данных
                $lamp_for_group = array();
                $max_lamps = (new Query())
                    ->select('
                            worker_parameter_sensor.sensor_id as sensor_id,
                            worker_object.worker_id as worker_id
                    ')
                    ->from('worker_parameter_sensor')
                    ->innerJoin('worker_parameter', 'worker_parameter.id=worker_parameter_sensor.worker_parameter_id')
                    ->innerJoin('worker_object', 'worker_object.id=worker_parameter.worker_object_id')
                    ->innerJoin("(select worker_parameter_id, max(date_time) as max_date_time from worker_parameter_sensor 
                                   where date_time<='" . $date_time_start . "' 
                                   group by worker_parameter_id) max_worker_parameter_sensor",
                        'max_worker_parameter_sensor.worker_parameter_id=worker_parameter_sensor.worker_parameter_id AND max_worker_parameter_sensor.max_date_time=worker_parameter_sensor.date_time')
                    ->where(['parameter_id' => 83, 'parameter_type_id' => 2])
                    ->andWhere(['in', 'worker_id', $worker_array])
//                    ->indexBy('worker_id')
                    ->all();
                foreach ($max_lamps as $lamp) {
                    $lamp_for_search[] = $lamp['sensor_id'];
                    $lamp_for_group[] = $lamp;
                }

                unset($max_lamps);

                $between_lamps = (new Query())
                    ->select('
                            worker_parameter_sensor.sensor_id as sensor_id,
                            worker_object.worker_id as worker_id
                    ')
                    ->from('worker_parameter_sensor')
                    ->innerJoin('worker_parameter', 'worker_parameter.id=worker_parameter_sensor.worker_parameter_id')
                    ->innerJoin('worker_object', 'worker_object.id=worker_parameter.worker_object_id')
                    ->innerJoin("(select worker_parameter_id, date_time as date_time from worker_parameter_sensor 
                                   where date_time>'" . $date_time_start . "' and
                                   date_time<='" . $date_time_end . "') max_worker_parameter_sensor",
                        'max_worker_parameter_sensor.worker_parameter_id=worker_parameter_sensor.worker_parameter_id AND max_worker_parameter_sensor.date_time=worker_parameter_sensor.date_time')
                    ->where(['parameter_id' => 83, 'parameter_type_id' => 2])
                    ->andWhere(['in', 'worker_id', $worker_array])
//                    ->indexBy('worker_id')
                    ->all();

                foreach ($between_lamps as $lamp) {
                    $lamp_for_search[] = $lamp['sensor_id'];
                    $lamp_for_group[] = $lamp;
                }
                unset($between_lamps);
                /** Отладка */
                $description = 'Нашел сенсоры работников';                                                                      // описание текущей отладочной точки
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

                // 11. по заданному lamp_sensor_id получаем расхождения показаний газов на искомое время
                if (isset($lamp_for_search)) {
                    // получаем события сравнения двух газов из таблицы event_journal_gas
                    $event_compare_gas = EventCompareGas::find()
                        ->joinWith('lampParameter')
                        ->joinWith('staticParameter')
                        ->joinWith('lampEdge.lampPlace')
                        ->joinWith('staticEdge.staticPlace')
                        ->where("date_time>='" . $date_time_start . "'")
                        ->andWhere("date_time<='" . $date_time_end . "'")
                        ->andWhere(['in', 'lamp_sensor_id', $lamp_for_search])
                        ->asArray()
                        ->all();
                    // готовим данные по сравнению в удобном виде
                    foreach ($event_compare_gas as $event_compare) {
                        $event_compare_item['event_compare_gas_id'] = $event_compare['id'];
                        if ($event_compare['lamp_value'] > 1 or $event_compare['static_value'] > 2) {
                            $event_compare_item['pdk_status'] = 1;  // статус превышения ПДК: 1 есть, 0 нет
                        } else {
                            $event_compare_item['pdk_status'] = 0;
                        }
                        $event_compare_item['event_id'] = $event_compare['event_id'];
                        $event_compare_item['sensor_id'] = $event_compare['lamp_sensor_id'];
                        $event_compare_item['sensor_title'] = $event_compare['lamp_object_title'];
                        if ($event_compare['date_time']) {
                            $event_compare_item['event_date_time'] = date('d.m.Y H:i:s', strtotime($event_compare['date_time']));
                        } else {
                            $event_compare_item['event_date_time'] = "";
                        }
                        $event_compare_item['sensor_value'] = $event_compare['lamp_value'];
                        $event_compare_item['sensor_xyz'] = $event_compare['lamp_xyz'];
                        $event_compare_item['unit_id'] = $event_compare['lampParameter']['unit_id'];
                        $event_compare_item['edge_id'] = $event_compare['lamp_edge_id'];

                        if ($event_compare['lamp_edge_id']) {
                            $event_compare_item['place_id'] = $event_compare['lampEdge']['place_id'];
                            $event_compare_item['place_title'] = $event_compare['lampEdge']['lampPlace']['title'];
                        } else {
                            $event_compare_item['place_id'] = -1;
                            $event_compare_item['place_title'] = '';
                        }
                        if (isset($edge_length[$event_compare['lamp_edge_id']]) and $edge_length[$event_compare['lamp_edge_id']]['edge_length'] != "-1" and $edge_length[$event_compare['lamp_edge_id']]['edge_length'] != 'empty') {
                            $event_compare_item['length'] = round($edge_length[$event_compare['lamp_edge_id']]['edge_length'], 1);
                        } else {
                            $event_compare_item['length'] = 0;
                        }
                        $event_compare_item['sensor2_id'] = $event_compare['static_sensor_id'];
                        $event_compare_item['sensor2_title'] = $event_compare['static_object_title'];
                        $event_compare_item['sensor2_xyz'] = $event_compare['static_xyz'];
                        if ($event_compare['date_time']) {
                            $event_compare_item['event_date_time2'] = date('d.m.Y H:i:s', strtotime($event_compare['date_time']));
                        } else {
                            $event_compare_item['event_date_time2'] = "";
                        }
                        $event_compare_item['exist_deviation'] = null;
                        $event_compare_item['sensor2_value'] = $event_compare['static_value'];
                        $event_compare_item['unit2_id'] = $event_compare['staticParameter']['unit_id'];
                        $event_compare_item['edge2_id'] = $event_compare['static_edge_id'];

                        if ($event_compare['static_edge_id']) {
                            $event_compare_item['place2_id'] = $event_compare['staticEdge']['place_id'];
                            $event_compare_item['place2_title'] = $event_compare['staticEdge']['staticPlace']['title'];
                        } else {
                            $event_compare_item['place2_id'] = -1;
                            $event_compare_item['place2_title'] = '';
                        }
                        $event_compare_gas_array[$event_compare['lamp_sensor_id']][$event_compare['date_time']] = $event_compare_item;
                    }

                    unset($event_compare_gas);
                    foreach ($lamp_for_group as $lamp) {
                        $lamp_group[$lamp['worker_id']][$lamp['sensor_id']] = $lamp;
                    }
                    foreach ($lamp_group as $worker_id => $worker) {
                        if (!isset($compare_route[$worker_id]['compare_gas'])) {
                            $compare_route[$worker_id]['compare_gas'] = array();
                        }
                        foreach ($worker as $sensor_id => $lamp) {
                            if (isset($event_compare_gas_array[$sensor_id])) {
                                $compare_route[$worker_id]['compare_gas'] = array_merge($compare_route[$worker_id]['compare_gas'], $event_compare_gas_array[$sensor_id]);
                            }
                        }
                    }
                }
                /** Отладка */
                $description = 'Нашел расхождение газов';                                                                      // описание текущей отладочной точки
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

//                $warnings[] = $between_lamps;
            } else {
                $warnings[] = $method_name . '. нет ни одного работника, т.к. нет ни одного наряда';
            }

            // 12. заполняем ФИО и прочие данные по работнику
            $workers_info = Worker::find()
                ->select('
                        worker.id as worker_id,
                        employee.last_name as last_name,
                        employee.first_name as first_name,
                        employee.patronymic as patronymic,
                        worker.tabel_number as tabel_number,
                        position.id as position_id,
                        position.title as position_title,
                        company_department.id as company_department_id,
                        company.title as company_title
                    ')
                ->innerJoin('employee', 'employee.id=worker.employee_id')
                ->innerJoin('position', 'position.id=worker.position_id')
                ->innerJoin('company_department', 'company_department.id=worker.company_department_id')
                ->innerJoin('company', 'company.id=company_department.company_id')
                ->where(['in', 'worker.id', $worker_array])
                ->asArray()
                ->all();
            foreach ($workers_info as $worker) {
                $worker_id = $worker['worker_id'];
                $compare_route[$worker_id]['worker_id'] = $worker_id;
                $compare_route[$worker_id]['full_name'] = $worker['last_name'] . ' ' . $worker['first_name'] . ' ' . $worker['patronymic'];
                $compare_route[$worker_id]['position_id'] = $worker['position_id'];
                $compare_route[$worker_id]['position_title'] = $worker['position_title'];
                $compare_route[$worker_id]['tabel_number'] = $worker['tabel_number'];
                $compare_route[$worker_id]['company_department_id'] = $worker['company_department_id'];
                $compare_route[$worker_id]['company_title'] = $worker['company_title'];

                if (
                    empty($compare_route[$worker_id]['route_fact'])
                ) {
                    $compare_route[$worker_id]['exist_deviation'] = 1;
                }

                if (
                    (isset($compare_route[$worker_id]['edge_percentage_match']) and $compare_route[$worker_id]['edge_percentage_match'] == 100) or
                    (isset($compare_route[$worker_id]['edge_percentage_match_by_length']) and $compare_route[$worker_id]['edge_percentage_match_by_length'] == 100)
                ) {
                    $compare_route[$worker_id]['exist_deviation'] = 0;
                }
            }

//            $warnings[] = $shifts_workers;
            /** Отладка */
            $description = 'Заполнил сведения о работниках';                                                                      // описание текущей отладочной точки
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

            // 13. считаем статистику обследования всех шахта:
            // получить историю обследования за неделю индексированную по эджу
            $date_time_end = $date . ' 23:59:59';
            $date_time_start = date('Y-m-d', strtotime($post_dec->date . '-7day')) . ' 00:00:00';
            if (1 == 2 and isset($worker_array) and isset($date_time_start) and isset($date_time_end)) {
                $route_fact = WorkerParameterValue::find()
                    ->select('
                        worker_parameter_value.value as value
                    ')
//                    ->innerJoinWith('workerParameter.workerObject')
                    ->innerJoin('worker_parameter', 'worker_parameter.id=worker_parameter_value.worker_parameter_id')
                    ->innerJoin('worker_object', 'worker_object.id=worker_parameter.worker_object_id')
                    ->where(['in', 'worker_id', $worker_array])
                    ->andWhere(
                        ['parameter_id' => 269, 'parameter_type_id' => 2]
                    )
                    ->andWhere(['>=', 'date_time', $date_time_start])
                    ->andWhere(['<=', 'date_time', $date_time_end])
                    ->distinct()
                    ->asArray()
                    ->all();

                $route_fact_history = WorkerParameterValueHistory::find()
                    ->select('
                        worker_parameter_value_history.value as value
                    ')
//                    ->innerJoinWith('workerParameter.workerObject')
                    ->innerJoin('worker_parameter', 'worker_parameter.id=worker_parameter_value_history.worker_parameter_id')
                    ->innerJoin('worker_object', 'worker_object.id=worker_parameter.worker_object_id')
                    ->where(['in', 'worker_id', $worker_array])
                    ->andWhere(
                        ['parameter_id' => 269, 'parameter_type_id' => 2]
                    )
                    ->andWhere(['>=', 'date_time', $date_time_start])
                    ->andWhere(['<=', 'date_time', $date_time_end])
                    ->distinct()
                    ->asArray()
                    ->all();

                if ($route_fact_history) {
                    $route_fact = array_merge($route_fact, $route_fact_history);
                }

                foreach ($route_fact as $worker) {
                    $route_fact_group[$worker['value']] = "";
                }
                unset($route_fact);
            }

            // получаем справочник шахт
            $mines_handbook = Mine::find()->indexBy('id')->asArray()->all();

            //перебрать все эджи шахт и проверить хотя бы раз нахождение в них человека за последнюю неделю
            // compare_route_by_mine:   - содержит информацию о полноте обследования шахт
            //      [mine_id]           - ключ шахты
            //              mine_id                         - ключ шахты
            //              mine_title                      - название шахты
            //              count_all_edges                 - количество выработок всего
            //              count_explorer_edges            - количество обследованных выработок
            //              percent_count_explorer_edges    - процент от количества обследованных выработок
            //              length_all_edges                - длина всех выработок
            //              length_explorer_edges           - длина обследованных выработок
            //              percent_length_explorer_edges   - процент от длины обследованных выработок
            if ($edge_status) {
                foreach ($edge_status as $edge) {
                    $edge_id = $edge['id'];
                    $mine_id = $edge['mine_id'];

                    // получение длины выработок
                    if (isset($edge_length[$edge_id]) and $edge_length[$edge_id]['edge_length'] != "-1" and $edge_length[$edge_id]['edge_length'] != 'empty') {
                        $edge_length_item = round($edge_length[$edge_id]['edge_length'], 0);
                    } else {
                        $edge_length_item = 0;
                    }
                    // инициализация выходного объекта
                    if (!isset($compare_route_by_mine[$mine_id])) {
                        $compare_route_by_mine[$mine_id]['mine_id'] = $mine_id;
                        if ($mines_handbook and $mines_handbook[$mine_id]) {
                            $compare_route_by_mine[$mine_id]['mine_title'] = $mines_handbook[$mine_id]['title'];
                        } else {
                            $compare_route_by_mine[$mine_id]['mine_title'] = "";
                        }
                        $compare_route_by_mine[$mine_id]['count_all_edges'] = 0;
                        $compare_route_by_mine[$mine_id]['count_explorer_edges'] = 0;
                        $compare_route_by_mine[$mine_id]['length_all_edges'] = 0;
                        $compare_route_by_mine[$mine_id]['length_explorer_edges'] = 0;
                        $compare_route_by_mine[$mine_id]['percent_length_explorer_edges'] = 0;
                        $compare_route_by_mine[$mine_id]['percent_count_explorer_edges'] = 0;
                    }

                    // считаем все выработки и их длины
                    $compare_route_by_mine[$mine_id]['count_all_edges']++;
                    $compare_route_by_mine[$mine_id]['length_all_edges'] += $edge_length_item;

                    // считаем обследованные выработки и их длины
                    if (isset($route_fact_group[$edge['id']])) {
                        $compare_route_by_mine[$mine_id]['count_explorer_edges']++;
                        $compare_route_by_mine[$mine_id]['length_explorer_edges'] += $edge_length_item;
                    }
                }

                if (isset($compare_route_by_mine)) {
                    foreach ($compare_route_by_mine as $mine) {
                        if ($compare_route_by_mine[$mine['mine_id']]['length_all_edges']) {
                            $compare_route_by_mine[$mine['mine_id']]['percent_length_explorer_edges'] = round(($compare_route_by_mine[$mine['mine_id']]['length_explorer_edges'] / $compare_route_by_mine[$mine['mine_id']]['length_all_edges']) * 100, 1);
                        }
                        if ($compare_route_by_mine[$mine['mine_id']]['count_all_edges']) {
                            $compare_route_by_mine[$mine['mine_id']]['percent_count_explorer_edges'] = round(($compare_route_by_mine[$mine['mine_id']]['count_explorer_edges'] / $compare_route_by_mine[$mine['mine_id']]['count_all_edges']) * 100, 1);
                        }
                    }
                }
            }


            /** Отладка */
            $description = 'Закончил расчет всех шахт на полноту обследования';                                             // описание текущей отладочной точки
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

            // 14. отправляем выходной объект на фронт
            /** Метод окончание */
            if (isset($compare_route)) {
                $result['workers'] = $compare_route;
            } else {
                $result['workers'] = (object)array();
            }

            if (isset($compare_route_by_mine)) {
                $result['mines'] = $compare_route_by_mine;
            } else {
                $result['mines'] = (object)array();
            }

        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
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
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result_main;
    }

    // GetCompareRouteDetail - метод получения деталей сравнения маршрутов
    // входные данные:
    //      order_place_id:                      - ключ привязки места к наряду
    // выходной объект:
    //      order_id	                "1414"                                                                          // ключ наряда
    //      date_time_create	        "2020-02-18"                                                                    // дата выдачи наряда
    //      company_department_id	    "20028748"                                                                      // ключ подразделения
    //      company_title	            "АИСиРЭО"                                                                       // наименование компании
    //      order_operation_description                                                                                 // описание - отчет по наряду
    //          []
    //      workers                                                                                                     // список работников
    //          1094021                                                                                                 // ключ работника
    //              worker_id	            "1094021"                                                                   // ключ работника
    //              tabel_number	        "1094021"                                                                   // таблеьный номер
    //              position_id	            "1171780003"                                                                // ключ должности
    //              position_title	        "Горнорабочий подземный 3р"                                                 // наименование должности
    //              last_name	            "Жиленко"                                                                   // фамилия работника
    //              first_name	            "Виталий"                                                                   // имя работник
    //              patronymic	            "Михайлович"                                                                // отчество работника
    //              full_name	            "Жиленко Виталий Михайлович"                                                // полное имя работника
    //      route_id	                "52"                                                                            // ключ маршрута
    //      route_title	                "Маршрут 2"                                                                     // название маршрута
    //      plan_route                                                                                                  // плановый маршрут
    //          15012                                                                                                   // ключ выработки
    //              edge_id	                "15012"                                                                     // ключ выработки
    //      fact_route                                                                                                  // фактический маршрут
    //          23466                                                                                                   // ключ выработки
    //              edge_id	                "23466"                                                                     // ключ выработки
    //      sensors                                                                                                     // список сенсоров
    //          115984                                                                                                  // ключ сенсора
    //              sensor_id	            "115984"                                                                    // ключ сенсора
    //              sensor_title	        "АГЗ ПУПП № 139 ГКУ 23-ю (CH4_KUSH6)"                                       // наименование сенсора
    //              edge_id	                "23466"                                                                     // ключ выработки на которой стоит сенсор
    //              place_id	            "6228"                                                                      // ключ места на которой стоит сенсор
    //              place_title	            "ГКУ 23 пл. Четв."                                                          // наименование места
    //              exist_deviation         0                                                                           // наличие расхождений (1 есть расхождение, 0 нет расхождения)
    // алгоритм:
    //      1. Получить по order_place_id наряд, работников на маршруте, сам маршрут
    //      2. по плановому маршруту найти список edge_id
    //      3. получить фактический маршрут передвижения
    //      4. по списку edge_id составить плановый список датчиков
    //      5. сравнить плановый список датчиков(их edge) с фактическим маршрутом
    //      6. отдать данные на фронт
    // Пример: http://127.0.0.1/read-manager-amicum?controller=positioningsystem\Route&method=GetCompareRouteDetail&subscribe=&data={"order_place_id":1515}
    // Разработал: Якимов М.Н.
    public static function GetCompareRouteDetail($data_post = NULL)
    {
//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', '5000M');
        // Стартовая отладочная информация
        $method_name = 'GetCompareRouteDetail';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
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
            // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//                $date_time_debug_start, $date_time_debug_end, $log_id,
//                $duration_summary, $max_memory_peak, $count_all);
//            if ($response['status'] === 1) {
//                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
//            } else {
//                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
//            }

            /** Метод начало */
            $warnings[] = $method_name . '. Начало метода';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'order_place_id'))                                                 // ключ привязки наряда к месту
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            // 0. получить список входных параметров
            $order_place_id = $post_dec->order_place_id;                                                                // ключ привязки наряда к месту
            //      1. Получить по order_place_id наряд, работников на маршруте, сам маршрут
            $orders = OrderPlace::find()
                ->joinWith('order.companyDepartment.company')
                ->joinWith('orderOperations.operationWorkers.worker.employee')
                ->joinWith('orderOperations.operationWorkers.worker.position')
                ->joinWith('routeTemplate.routeTemplateEdges')
                ->where(['order_place.id' => $order_place_id])
                ->asArray()
                ->all();

            foreach ($orders as $order) {
//                $warnings['Наряд'] = $order;
                $result_detail['order_id'] = $order['order_id'];
                $result_detail['date_time_create'] = $order['order']['date_time_create'];
                $result_detail['company_department_id'] = $order['order']['company_department_id'];
                $result_detail['company_title'] = $order['order']['companyDepartment']['company']['title'];

                // составить комментарий к наряду и спсиок работников по маршруту
                foreach ($order['orderOperations'] as $operation) {
                    if ($operation['description']) {
                        $result_detail['order_operation_description'][] = $operation['description'];
                    }
                    foreach ($operation['operationWorkers'] as $worker) {
                        $result_detail['workers'][$worker['worker_id']]['worker_id'] = $worker['worker_id'];
                        $result_detail['workers'][$worker['worker_id']]['tabel_number'] = $worker['worker']['tabel_number'];
                        $result_detail['workers'][$worker['worker_id']]['position_id'] = $worker['worker']['position_id'];
                        $result_detail['workers'][$worker['worker_id']]['position_title'] = $worker['worker']['position']['title'];
                        $result_detail['workers'][$worker['worker_id']]['last_name'] = $worker['worker']['employee']['last_name'];
                        $result_detail['workers'][$worker['worker_id']]['first_name'] = $worker['worker']['employee']['first_name'];
                        $result_detail['workers'][$worker['worker_id']]['patronymic'] = $worker['worker']['employee']['patronymic'];
                        $result_detail['workers'][$worker['worker_id']]['full_name'] = $worker['worker']['employee']['last_name'] . ' ' . $worker['worker']['employee']['first_name'] . ' ' . $worker['worker']['employee']['patronymic'];
                    }
                }
                if (!isset($result_detail['workers'])) {
                    $result_detail['workers'] = (object)array();
                }
                $worker_array = [];
                foreach ($result_detail['workers'] as $worker) {
                    $worker_array[] = $worker['worker_id'];
                }
                if (!isset($result_detail['order_operation_description'])) {
                    $result_detail['order_operation_description'] = array();
                }

                // получить историю выработок на интересующую дату
                $date = $order['order']['date_time_create'];
                $edge_status = (new Query())
                    ->select('edge.id as id, edge_status.status_id')
                    ->from('edge')
                    ->innerJoin('edge_status', 'edge_status.edge_id=edge.id')
                    ->innerJoin('(select max(date_time) as date_time_to_date, edge_id from edge_status where date_time<="' . $date . '" group by edge_id) edge_status_to_date', 'edge_status_to_date.edge_id=edge_status.edge_id and edge_status_to_date.date_time_to_date=edge_status.date_time')
                    ->where(['edge_status.status_id' => 1])
                    ->indexBy('id')
                    ->all();

//            $warnings[]=$edge_status;
                /** Отладка */
                $description = 'Получил историю эджей на искомую дату';                                                             // описание текущей отладочной точки
                $description = $method_name . ' ' . $description;
                $warnings[] = $description;                                                                                 // описание текущей отладочной точки
                $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
                $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
                $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
                $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
                $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
                $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
                $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
                $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
                $microtime_current = microtime(true);
                /** Окончание отладки */

                //      2. по плановому маршруту найти список edge_id
                $result_detail['route_id'] = $order['route_template_id'];
                $route_template_id = $order['route_template_id'];
                if ($route_template_id) {
                    $result_detail['route_title'] = $order['routeTemplate']['title'];
                    foreach ($order['routeTemplate']['routeTemplateEdges'] as $edge) {
                        if (!isset($edge_status[$edge['edge_id']])) {
                            $result_detail['plan_route'][$edge['edge_id']]['edge_id'] = $edge['edge_id'];
                        }
                    }
                } else {
                    $result_detail['route_title'] = "";
                }
                if (!isset($result_detail['plan_route'])) {
                    $result_detail['plan_route'] = (object)array();
                }

                //      3. получить фактический маршрут передвижения
                $shift_id = $order['order']['shift_id'];
                // На основании полученных смен, получить дату начала - 2 часа и дату окончания + 2 часа - период получения фактического маршрута
                if ($shift_id == 1) {
                    $date_time_start = $date . ' 06:00:00';
                    $date_time_end = $date . ' 16:00:00';
                } else if ($shift_id == 2) {
                    $date_time_start = $date . ' 12:00:00';
                    $date_time_end = $date . ' 22:00:00';
                } else if ($shift_id == 3) {
                    $date_time_start = $date . ' 18:00:00';
                    $date_time_end = date('Y-m-d', strtotime($date . '+1day')) . ' 04:00:00';
                } else if ($shift_id == 4) {
                    $date_time_start = date('Y-m-d', strtotime($date . '+1day')) . ' 00:00:00';
                    $date_time_end = date('Y-m-d', strtotime($date . '+1day')) . ' 10:00:00';
                }

                /** Отладка */
                $description = 'Посчитал дату начала и конца';                                                                      // описание текущей отладочной точки
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

                $route_fact = WorkerParameterValue::find()
                    ->select('
                        worker_parameter_value.value as edge_id,
                    ')
//                    ->innerJoinWith('workerParameter.workerObject')
                    ->innerJoin('worker_parameter', 'worker_parameter.id=worker_parameter_value.worker_parameter_id')
                    ->innerJoin('worker_object', 'worker_object.id=worker_parameter.worker_object_id')
                    ->where(['in', 'worker_id', $worker_array])
                    ->andWhere(
                        ['parameter_id' => 269, 'parameter_type_id' => 2]
                    )
                    ->andWhere(['>=', 'date_time', $date_time_start])
                    ->andWhere(['<=', 'date_time', $date_time_end])
                    ->asArray()
                    ->indexBy('edge_id')
                    ->all();

                $route_fact_history = WorkerParameterValueHistory::find()
                    ->select('
                        worker_parameter_value_history.value as edge_id,
                    ')
//                    ->innerJoinWith('workerParameter.workerObject')
                    ->innerJoin('worker_parameter', 'worker_parameter.id=worker_parameter_value_history.worker_parameter_id')
                    ->innerJoin('worker_object', 'worker_object.id=worker_parameter.worker_object_id')
                    ->where(['in', 'worker_id', $worker_array])
                    ->andWhere(
                        ['parameter_id' => 269, 'parameter_type_id' => 2]
                    )
                    ->andWhere(['>=', 'date_time', $date_time_start])
                    ->andWhere(['<=', 'date_time', $date_time_end])
                    ->asArray()
                    ->indexBy('edge_id')
                    ->all();


                if ($route_fact_history) {
                    $route_fact = array_merge($route_fact, $route_fact_history);
                }
                $result_detail['fact_route'] = $route_fact;

                /** Отладка */
                $description = 'Получил фактический маршрут';                                                                      // описание текущей отладочной точки
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


                // создаем справочник выработок и мест
                $edge_place_handbook = (new Query())
                    ->select('
                        edge.id as edge_id,
                        place.id as place_id,
                        place.title as place_title
                    ')
                    ->from('edge')
                    ->innerJoin('place', 'place.id=edge.place_id')
                    ->indexBy('edge_id')
                    ->all();

                //      - получить последнее местоположение датчиков CH4 на указанную дату
                $ch4_edges = (new Query())
                    ->select('
                        sensor.id as sensor_id,
                        sensor.title as sensor_title,
                        sensor_parameter_handbook_value.value as edge_id
                    ')
                    ->from('sensor')
                    ->innerJoin('sensor_parameter', 'sensor_parameter.sensor_id=sensor.id')
                    ->innerJoin('sensor_parameter_handbook_value', 'sensor_parameter_handbook_value.sensor_parameter_id=sensor_parameter.id')
                    ->innerJoin("(
                        select sensor_parameter_handbook_value_to_date.sensor_parameter_id, max(sensor_parameter_handbook_value_to_date.date_time) as max_date_time
                        from (
                            select sensor_parameter_id, date_time from sensor_parameter_handbook_value where date_time<='" . $date_time_end . "'
                        ) sensor_parameter_handbook_value_to_date 
                        group by sensor_parameter_handbook_value_to_date.sensor_parameter_id
                    ) sensor_parameter_handbook_value_max",
                        'sensor_parameter_handbook_value_max.sensor_parameter_id=sensor_parameter_handbook_value.sensor_parameter_id and sensor_parameter_handbook_value_max.max_date_time=sensor_parameter_handbook_value.date_time'
                    )
                    ->where(['object_id' => 28, 'sensor_parameter.parameter_id' => 269, 'sensor_parameter.parameter_type_id' => 1])
                    ->andWhere('sensor_parameter_handbook_value.value!=-1')
                    ->all();
//                $result_detail['ch4_edges'] = $ch4_edges;

                // создаем справчочник сенсоров
                foreach ($ch4_edges as $sensor) {
                    if (isset($edge_place_handbook[$sensor['edge_id']])) {
                        $place_id = $edge_place_handbook[$sensor['edge_id']]['place_id'];
                        $place_title = $edge_place_handbook[$sensor['edge_id']]['place_title'];
                    } else {
                        $place_id = null;
                        $place_title = "";
                    }
                    $sensor_edges[$sensor['edge_id']][$sensor['sensor_id']] = array(
                        'sensor_id' => $sensor['sensor_id'],
                        'edge_id' => $sensor['edge_id'],
                        'sensor_title' => $sensor['sensor_title'],
                        'place_id' => $place_id,
                        'place_title' => $place_title,
                    );

                }


                //      4. по списку edge_id составить плановый список датчиков
                // перебираем плановый маршрут, если в нем встречается выработка на которой стоит стационарный сенсор,
                // то кладем его в результирующий массив
                // затем проверяем на наличие данной выработке в фактически посещенной
                if (isset($result_detail['plan_route'])) {
                    foreach ($result_detail['plan_route'] as $edge) {
                        if (isset($sensor_edges[$edge['edge_id']])) {
                            foreach ($sensor_edges[$edge['edge_id']] as $sensor) {
                                // делаем болванку по умолчанию
                                if (!isset($result_detail['sensors'][$sensor['sensor_id']])) {
                                    $result_detail['sensors'][$sensor['sensor_id']]['sensor_id'] = $sensor['sensor_id'];
                                    $result_detail['sensors'][$sensor['sensor_id']]['sensor_title'] = $sensor['sensor_title'];
                                    $result_detail['sensors'][$sensor['sensor_id']]['edge_id'] = $edge['edge_id'];
                                    $result_detail['sensors'][$sensor['sensor_id']]['place_id'] = $sensor['place_id'];
                                    $result_detail['sensors'][$sensor['sensor_id']]['place_title'] = $sensor['place_title'];
                                    $result_detail['sensors'][$sensor['sensor_id']]['exist_deviation'] = 1;
                                }

                                // проверяем наличие выработки в фактически посещенных выработках
                                //      5. сравнить плановый список датчиков(их edge) с фактическим маршрутом
                                if (isset($result_detail['fact_route'][$edge['edge_id']])) {
                                    $result_detail['sensors'][$sensor['sensor_id']]['exist_deviation'] = 0;
                                }
                            }
                        }
                    }
                } else {
                    $result_detail['plan_route'] = (object)array();
                }
                //      6. отдать данные на фронт
            }

            if (!$result_detail['fact_route']) {
                $result_detail['fact_route'] = (object)array();
            }

            if (!isset($result_detail['sensors'])) {
                $result_detail['sensors'] = (object)array();
            }

            // 13. отправляем выходной объект на фронт
            /** Метод окончание */
            if (isset($result_detail)) {
                $result = $result_detail;
            } else {
                $result = (object)array();
            }
        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
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
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result_main;
    }

    // GetSensorRouteDetail - метод получения списка сенсоров по эджам со значениями до запрашиваемой даты
    // входные данные:
    //      date_time_end:                      - дата до которой получаем список датчиков из БД
    // выходной объект:
    // 22196	                    - ключ ветви  edge_id
    //      141562	                    - ключ сенсора sensor_id
    //          sensor_id	    "141562"                        - ключ сенсора
    //          edge_id	        "22196"                         - ключ ветви на которой стоит сенсор
    //          sensor_title	"CH19#2"                        - название сенсора
    //          place_id	    "6212"                          - ключ места на котором стоит сенсор
    //          place_title	    "Ходок на скип. ствол 3 гор."   - название места на котором стоит сенсор
    // алгоритм:
    //      1. создаем справочник выработок и мест
    //      2. получить последнее местоположение датчиков CH4 на указанную дату
    //      3. создаем справчочник сенсоров
    // Пример: http://127.0.0.1/read-manager-amicum?controller=positioningsystem\Route&method=GetSensorRouteDetail&subscribe=&data={"date_time_end":"2020-03-18 08:00:00"}
    // Разработал: Якимов М.Н.
    public static function GetSensorRouteDetail($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'GetSensorRouteDetail';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
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
            // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//                $date_time_debug_start, $date_time_debug_end, $log_id,
//                $duration_summary, $max_memory_peak, $count_all);
//            if ($response['status'] === 1) {
//                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
//            } else {
//                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
//            }

            /** Метод начало */
            $warnings[] = $method_name . '. Начало метода';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'date_time_end'))                                                 // ключ привязки наряда к месту
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            // 0. получить список входных параметров
            $date_time_end = $post_dec->date_time_end;                                                                // ключ привязки наряда к месту

            // создаем справочник выработок и мест
            $edge_place_handbook = (new Query())
                ->select('
                        edge.id as edge_id,
                        place.id as place_id,
                        place.title as place_title
                    ')
                ->from('edge')
                ->innerJoin('place', 'place.id=edge.place_id')
                ->indexBy('edge_id')
                ->all();

            //      - получить последнее местоположение датчиков CH4 на указанную дату
            $ch4_edges = (new Query())
                ->select('
                        sensor.id as sensor_id,
                        sensor.title as sensor_title,
                        sensor_parameter_handbook_value.value as edge_id
                    ')
                ->from('sensor')
                ->innerJoin('sensor_parameter', 'sensor_parameter.sensor_id=sensor.id')
                ->innerJoin('sensor_parameter_handbook_value', 'sensor_parameter_handbook_value.sensor_parameter_id=sensor_parameter.id')
                ->innerJoin("(
                        select sensor_parameter_handbook_value_to_date.sensor_parameter_id, max(sensor_parameter_handbook_value_to_date.date_time) as max_date_time
                        from (
                            select sensor_parameter_id, date_time from sensor_parameter_handbook_value where date_time<='" . $date_time_end . "'
                        ) sensor_parameter_handbook_value_to_date 
                        group by sensor_parameter_handbook_value_to_date.sensor_parameter_id
                    ) sensor_parameter_handbook_value_max",
                    'sensor_parameter_handbook_value_max.sensor_parameter_id=sensor_parameter_handbook_value.sensor_parameter_id and sensor_parameter_handbook_value_max.max_date_time=sensor_parameter_handbook_value.date_time'
                )
                ->where(['object_id' => 28, 'sensor_parameter.parameter_id' => 269, 'sensor_parameter.parameter_type_id' => 1])
                ->andWhere('sensor_parameter_handbook_value.value!=-1')
                ->all();
//                $result_detail['ch4_edges'] = $ch4_edges;

            // создаем справчочник сенсоров
            foreach ($ch4_edges as $sensor) {
                if (isset($edge_place_handbook[$sensor['edge_id']])) {
                    $place_id = $edge_place_handbook[$sensor['edge_id']]['place_id'];
                    $place_title = $edge_place_handbook[$sensor['edge_id']]['place_title'];
                } else {
                    $place_id = null;
                    $place_title = "";
                }
                $sensor_edges[$sensor['edge_id']][$sensor['sensor_id']] = array(
                    'sensor_id' => $sensor['sensor_id'],
                    'edge_id' => $sensor['edge_id'],
                    'sensor_title' => $sensor['sensor_title'],
                    'place_id' => $place_id,
                    'place_title' => $place_title,
                );

            }

            // 13. отправляем выходной объект на фронт
            /** Метод окончание */
            if (isset($sensor_edges)) {
                $result = $sensor_edges;
            } else {
                $result = (object)array();
            }
        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
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
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);

        return array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }

    // GetRouteTemplatePlace - метод получения мест шаблона маршрута
    // входные данные:
    //      route_template_id:                      - ключ шаблона маршрута
    // выходной объект:
    //                  [] - массив (отсортирован по возрастанию)
    //                    'place_id'                                                                                    // id места
    //                    'place_title'                                                                                 // наименование места
    //                    'methane_value'                                                                               // замер метана по прибору
    //                    'carbon_dioxide_value'                                                                        // замер углекислого газа по прибору
    //                    'carbon_monoxide_value'                                                                       // замер угарного газа по прибору
    //                    'oxygen_value'                                                                                // замер кислорода по прибору
    //                    'stationary_sensor_methane_value'                                                             // показание стационарного датчика контроля метана ДМС
    //                    'stationary_sensor_water_pressure'                                                            // показание стационарного датчика давления воды СДД                                                                      // ключ наряда
    // алгоритм:

    // Пример: http://127.0.0.1/read-manager-amicum?controller=positioningsystem\Route&method=GetRouteTemplatePlace&subscribe=&data={"route_template_id":52}
    // Разработал: Якимов М.Н.
    public static function GetRouteTemplatePlace($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'GetRouteTemplatePlace';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
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
            // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//                $date_time_debug_start, $date_time_debug_end, $log_id,
//                $duration_summary, $max_memory_peak, $count_all);
//            if ($response['status'] === 1) {
//                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
//            } else {
//                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
//            }

            /** Метод начало */
            $warnings[] = $method_name . '. Начало метода';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'route_template_id'))                                                 // ключ привязки наряда к месту
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            // 0. получить список входных параметров
            $route_template_id = $post_dec->route_template_id;                                                                // ключ привязки наряда к месту

            $route_places = (new Query())
                ->select('
                    place.id as place_id,
                    place.title as place_title
                ')
                ->from('route_template_edge')
                ->innerJoin('edge', 'route_template_edge.edge_id=edge.id')
                ->innerJoin('place', 'place.id=edge.place_id')
                ->where(['route_template_edge.route_template_id' => $route_template_id])
                ->orderBy(['place.title' => SORT_ASC])
                ->groupBy('place_id, place_title')
                ->all();
            $result_route_place = array();
            foreach ($route_places as $place) {
                $result_route_place[] = array(
                    'place_id' => $place['place_id'],                                                                   // id места
                    'place_title' => $place['place_title'],                                                             // наименование места
                    'methane_value' => "",                                                                              // замер метана по прибору
                    'carbon_dioxide_value' => "",                                                                       // замер углекислого газа по прибору
                    'carbon_monoxide_value' => "",                                                                      // замер угарного газа по прибору
                    'oxygen_value' => "",                                                                               // замер кислорода по прибору
                    'stationary_sensor_methane_value' => "",                                                            // показание стационарного датчика контроля метана ДМС
                    'stationary_sensor_water_pressure' => "",                                                           // показание стационарного датчика давления воды СДД
                );
            }

            $result = $result_route_place;
        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
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
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);

        return array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }

    // GetRouteTemplatePlaceEsp - метод получения мест шаблона маршрута для ЭСП АБ
    // входные данные:
    //      route_template_id:                      - ключ шаблона маршрута
    //      date_time:                              - дата, на которую получаем значения
    // выходной объект:
    //                  [] - массив (отсортирован по возрастанию)
    //                      place_id: null,                     // id места
    //                      place_title: "",                    // наименование места
    //                      kind_equipment_title: "",           // вид оборудования
    //                      kind_place_title: "",               // вид места
    //                      gas_individual_sensor_value: "",    // показания газоанализатора
    //                      sensor_value: "",                   // показания стационарного датчика
    //                      sensor_inventar_number: "",         // инвентарный номер оборудования
    // алгоритм:

    // Пример: http://127.0.0.1/read-manager-amicum?controller=positioningsystem\Route&method=GetRouteTemplatePlaceEsp&subscribe=&data={"route_template_id":52,"date_time":"2020-12-23"}
    // Разработал: Якимов М.Н.
    public static function GetRouteTemplatePlaceEsp($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'GetRouteTemplatePlaceEsp';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
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
            // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//                $date_time_debug_start, $date_time_debug_end, $log_id,
//                $duration_summary, $max_memory_peak, $count_all);
//            if ($response['status'] === 1) {
//                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
//            } else {
//                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
//            }

            /** Метод начало */
            $warnings[] = $method_name . '. Начало метода';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'route_template_id') ||
                !property_exists($post_dec, 'date_time')
            )                                                 // ключ привязки наряда к месту
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            // 0. получить список входных параметров
            $route_template_id = $post_dec->route_template_id;                                                                // ключ привязки наряда к месту
            $date_time = $post_dec->date_time;                                                                // ключ привязки наряда к месту

            $route_places = (new Query())
                ->select('
                    place.id as place_id,
                    place.title as place_title
                ')
                ->from('route_template_edge')
                ->innerJoin('edge', 'route_template_edge.edge_id=edge.id')
                ->innerJoin('place', 'place.id=edge.place_id')
                ->where(['route_template_edge.route_template_id' => $route_template_id])
                ->orderBy(['place.title' => SORT_ASC])
                ->groupBy('place_id, place_title')
                ->all();

            // если есть список мест, то начинаем получать список сенсоров в данных местах и их инвентарные номера для следующих видов датчиков (object_type_id==116 (стационарные датчики):
            // - датчик CH4
            // - датчик CO
            // - датчик NO
            // - датчик CO2
            // - датчик H2
            // - датчик PL3
            // - датчик ИЗСТ
            // - шлюз

            // 122 - ключ параметра места
            // 104 - ключ параметра инвентарный номер
            if ($route_places) {
                $sphvs = SensorBasicController::getSensorParameterHandbookValueByDate($date_time, "*", [122, 104], "*", self::STATIONARY_SENSOR);
//                $warnings[]=$sphvs;
                foreach ($sphvs as $sphv)
                    if ($sphv['parameter_id'] == 122) {
                        $sphvs_handbook_sensor_by_place[$sphv['parameter_id']][$sphv['value']][] = $sphv;
                    } else if ($sphv['parameter_id'] == 104) {
                        $sphvs_handbook_inventor_number[$sphv['sensor_id']] = $sphv['value'];
                    } else {
                        $sphvs_handbook_by_sensor[$sphv['sensor_id']][$sphv['parameter_id']] = $sphv;
                    }
                unset($sphvs);
            }
//            $warnings[]=$sphvs_handbook_inventor_number;
            $result_route_place = array();
            foreach ($route_places as $place) {
                if (isset($sphvs_handbook_sensor_by_place[122][$place['place_id']])) {
                    foreach ($sphvs_handbook_sensor_by_place[122][$place['place_id']] as $sensor) {
                        $result_route_place[] = array(
                            'place_id' => $place['place_id'],                                                                   // id места
                            'place_title' => $place['place_title'],                                                             // наименование места
                            'kind_equipment_title' => $sensor['object_title'],                                                  // вид оборудования
                            'sensor_id' => $sensor['sensor_id'],                                                                // ключ сенсора
                            'kind_place_title' => $sensor['sensor_title'],                                                      // вид места
                            'gas_individual_sensor_value' => "",                                                                // показания газоанализатора
                            'sensor_value' => "",                                                                               // показания стационарного датчика
                            'sensor_inventar_number' => isset($sphvs_handbook_inventor_number[$sensor['sensor_id']]) ? $sphvs_handbook_inventor_number[$sensor['sensor_id']] : "",                                                                     // инвентарный номер оборудования
                        );
                    }
                } else {
                    $result_route_place[] = array(
                        'place_id' => $place['place_id'],                                                                   // id места
                        'place_title' => $place['place_title'],                                                             // наименование места
                        'kind_equipment_title' => "",                                                                       // вид оборудования
                        'sensor_id' => "",                                                                                  // ключ сенсора
                        'kind_place_title' => "",                                                                           // вид места
                        'gas_individual_sensor_value' => "",                                                                // показания газоанализатора
                        'sensor_value' => "",                                                                               // показания стационарного датчика
                        'sensor_inventar_number' => "",                                                                     // инвентарный номер оборудования
                    );
                }
            }

            $result = $result_route_place;
        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
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
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);

        return array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }
}
