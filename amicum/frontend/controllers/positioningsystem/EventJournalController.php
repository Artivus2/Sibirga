<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\positioningsystem;
//ob_start();

use backend\controllers\cachemanagers\EventCacheController;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Edge;
use frontend\models\Event;
use frontend\models\EventJournal;
use frontend\models\EventStatus;
use frontend\models\Parameter;
use frontend\models\Status;
use Throwable;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;


class EventJournalController extends Controller
{

    // actionInitEventCache         - метод по заполнению кеша событий за указанную дату
    // actionChangeMessageStatus    - метод по изменению статуса у события из журнала событий
    // actionGetViewEventJournal    - Метод вывода списка событий, объектов и мест для фильтрации поиска для журнала событий
    // actionGetEvents              - Функция передачи на фронт массива событий с учетом фильтра
    // methodGetEventsFromCache     - Функция получения событий из кэша
    // fillAllEventJournalCache     - Функция заполнения кэша событий за указанную дату

    public function actionIndex()
    {
        //self::fillAllEventJournalCache();                                                                             // вернул эту строку, так как по-другому кэш событий не создается и тем более не заполняется
        return $this->render('index');
    }




    // actionChangeMessageStatus - метод по изменению статуса у события из журнала событий
    // 127.0.0.1/positioningsystem/event-journal/change-message-status?event_journal_id=-1&mine_id=290&event_id=17014&object_id=25&mine_id=290&status_id=45
    public function actionChangeMessageStatus()
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $warnings = array();
        try {
            $warnings[] = "actionChangeMessageStatus. Начал выполнять метод";
            $post = Assistant::GetServerMethod();                                                                            //Подучение данных методом POST
            $errors = array();


            if (isset($post['event_journal_id']) && $post['event_journal_id'] != "" &&
                isset($post['mine_id']) && $post['mine_id'] != "" &&
                isset($post['event_id']) && $post['event_id'] != "" &&
                isset($post['object_id']) && $post['object_id'] != "" &&
                isset($post['status_id']) && $post['status_id'] != ""
            ) {
                $mine_id = (int)$post['mine_id'];
                $event_id = (int)$post['event_id'];
                $object_id = (int)$post['object_id'];
                $status_id = (int)$post['status_id'];
                $event_journal_id = (int)$post['event_journal_id'];
            } else {
                throw new Exception('actionChangeMessageStatus. Не переданы входные параметры');
            }

            /**
             * блок созранения в базу данных изменения события
             */
            $eventJournal = EventJournal::findOne(['id' => $event_journal_id]);
            if (!$eventJournal) {
                throw new Exception('actionChangeMessageStatus. Нет такого события в системе');
            }
            $eventJournal->event_status_id = $status_id;

            if ($eventJournal->save()) {
                $warnings[] = "actionChangeMessageStatus. Изменение статуса сохранено в БД";
            } else {
                $errors[] = $eventJournal->errors;
                throw new Exception('actionChangeMessageStatus. Ошибка сохранения модели EventJournal');
            }

            $eventStatus = new EventStatus();
            $eventStatus->status_id = $status_id;
            $eventStatus->event_journal_id = $event_journal_id;
            $eventStatus->datetime = date("Y-m-d H:i:s");
            if ($eventStatus->save()) {
                $warnings[] = "actionChangeMessageStatus. Изменение статуса сохранено в БД";
            } else {
                $errors[] = $eventStatus->errors;
                throw new Exception('actionChangeMessageStatus. Ошибка сохранения модели EventStatus');
            }

            /**
             * блок получения событий с кеша
             */
            $response = (new EventCacheController())->updateEventCacheValue($mine_id, $event_id, $object_id, "event_status_id", $status_id);
            if ($response['status'] == 1) {
                $status *= $response['status'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception('actionChangeMessageStatus. Ошибка сохранения статуса события в кеш');
            }

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "actionChangeMessageStatus. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $warnings[] = "actionChangeMessageStatus. Окончил выполнять метод";
        $result_main = array('status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**-
     * actionGetViewEventJournal - Метод вывода списка событий, объектов и мест для фильтрации поиска для журнала событий
     */
    // пример 127.0.0.1/positioningsystem/event-journal/get-view-event-journal?mine_id=290
    public function actionGetViewEventJournal()
    {

        $events = array();
        $places = array();
        $errors = array();
        $warnings = array();
        $post = Assistant::GetServerMethod();
        /**
         * Блок фильтров по шахте
         */
        try {
            if (isset($post['mine_id']) && $post['mine_id'] != '' && $post['mine_id'] != -1)                                                     // если передан еще  id места, то добавим в массив
            {
                $mine_id = (int)$post['mine_id'];
            } else {
                $mine_id = '*';
            }
            /**
             * получаем список событий из кэша
             */
            $response = self::methodGetEventsFromCache($mine_id);
            if ($response['status'] == 1) {
                $status = $response['status'];
                $events_in_cache = $response['events'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('actionGetViewEventJournal. Ошибка при получении события из кеша');
            }

            /**
             * если в КЭШе есть события
             **/
            if ($events_in_cache) {
                $events_array = array();
                $places_array = array();
                /**
                 * перебор массива событий с целью собрать в новые массивы id мест и id событий
                 */
                foreach ($events_in_cache as $item) {
                    $events_array[$item['event_id']] = (int)$item['event_id'];                                          // получаем event_id из кэша, чтобы потом из таблицы events получить данные по  id-кам
                    $places_array[$item['place_id']] = (int)$item['place_id'];                                          // получаем place_id из кэша, чтобы потом из таблицы place получить данные по  id-кам
                }

                $events_id = implode(',', $events_array);                                                       // преобразуем в строку разделяя запятой
                $places_id = implode(',', $places_array);                                                       // преобразуем в строку разделяя запятой
                /**
                 * пишем запрос на получение списка мест из БД со структурой
                 *   id {int} - идентификатор места
                 *   title {string} - наименование места
                 */
                $places = (new Query())                                                                                 // получаем места
                ->select('id, title')
                    ->from('place')
                    ->where('id IN (' . $places_id . ')')
                    ->orderBy(['title' => SORT_ASC])
                    ->all();
                /**
                 * пишем запрос на получение списка событий из БД со структурой
                 *   id {int} - идентификатор события
                 *   title {string} - наименование события
                 */
                $events = (new Query())                                                                                 // получаем события
                ->select('id, title')
                    ->from('event')
                    ->where('id IN (' . $events_id . ')')
                    ->orderBy(['title' => SORT_ASC])
                    ->all();
            }
        } catch (Throwable $error) {
            $status = 0;
            $errors[] = "actionGetViewEventJournal. Исключение: ";
            $errors[] = $error->getMessage();
            $errors[] = $error->getLine();
        }
        $result = array('errors' => $errors, 'events' => $events, 'places' => $places, 'warnings' => $warnings, 'status' => $status);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;

    }


    /** actionGetEvents - Функция передачи на фронт массива событий с учетом фильтра
     * Входной объект:
     *      date1           "07.02.2021"    - дата начала получения событий
     *      date2           "09.02.2021"    - дата окончая получения событий
     *      place_id        ""              - ключ места события
     *      event_id[]      "7129"          - массив ключе событий
     *      search          ""              - строка поиска
     *      mine_id         "250"           - ключ шахты
     *      caller          "ajaxGetEvents" - кто вызвал метод
     * Выходной объект:
     *  []
     *      event_id            7129                                        - ключ события
     *      main_id             "2042094"                                   - ключ главного объекта
     *      edge_id             2673925                                     - ключ ветви
     *      value               "Stationary"                                - значение события
     *      value_status_id     44                                          - ключ статуса значения события
     *      date_time           "2021-2-9 14:22:58.838365"                  - дата события
     *      event_status_id     38                                          - ключ статуса события
     *      mine_id             "270"                                       - ключ шахты
     *      xyz                 "15768.33,-733.09,-11744.74"                - координата события
     *      parameter_id        356                                         - ключ параметра
     *      object_id           25                                          - ключ типового события
     *      object_title        "Бакиров Фарид Борисович"                   - название/ФИО объекта события
     *      object_table        "worker"                                    - таблица в которой лежит объект события
     *      event_journal_id    47016547                                    - ключ журнала событий
     *      group_alarm_id      "-1"                                        - ключ группы опопещения
     *      status_id           "38"                                        - ключ статуса решения события
     *      object_type         "worker"                                    - тип объекта
     *      status_title        "Событие получено"                          - навзвание статуса
     *      event_type          "Аварийное значение"                        - тип события
     *      event_title         "Человек без движения"                      - название события
     *      unit_short          "-"                                         - сокращенное название единицы
     *      place_id            "1181731"                                   - ключ места
     *      place_title         "Конвейерный штрек 211-ю пл. Тройного"      - название места
     *      id                  47016547                                    - ключ журнала событий
     *      parameter_value     "Stationary"                                - значение параметра события
     * Moved by: Курбанов И. С. on 27.05.2019
     */

    // пример: 127.0.0.1/positioningsystem/event-journal/get-events?search=-1&object_id=290&place_id=17014&mine_id=25&event_id=290&date1=45&date2=45
    public function actionGetEvents()
    {
        $log = new LogAmicumFront("actionGetEvents");
        $result = array();                                                                                              // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $events = array();
        try {
            $log->addLog("Начало выполнение метода");

            /**
             * блок обработки входных параметров с фронта
             */
            $post = Assistant::GetServerMethod();
//            if (COD) {
//                $response = ArchiveEventController::GetArchiveEvents($post);
//                $log->addLogAll($response);
//                if ($response['status'] != 1) {
//                    throw new Exception("actionGetEvents. Не смог получить события из БД");
//                }
//                $events = $response['events'];
//            } else {
            $object = '';
            $place = '';
            $event = '';
            $date2 = '';
            $date1 = '';
            $search = '';

            if (isset($post['search']) && $post['search'] != '')                                                        // если передан id объекта, то фильтр только по object_id
            {
                $search = $post['search'];
                }
                /**
                 * блок фильтров по объектам
                 */
                if (isset($post['object_id']) && $post['object_id'] != '')                                                  // если передан id объекта, то фильтр только по object_id
                {
                    $object = (int)$post['object_id'];
                    $main_id = $object;
                } else {
                    $main_id = '*';
                }
                if (isset($post['place_id']) && $post['place_id'] != '')                                                    // если передан еще id места, то добавим в массив
                {
                    $place = (int)$post['place_id'];
                }
                /**
                 * Блок фильтров по шахте
                 */
                if (isset($post['mine_id']) && $post['mine_id'] != '' && $post['mine_id'] != -1)                            // если передан еще  id места, то добавим в массив
                {
                    $mine_id = (int)$post['mine_id'];
                } else {
                    $mine_id = '*';
                }

                /**
                 * Блок фильтров события
                 */
                if (isset($post['event_id']) && $post['event_id'] != '')                                                    // если передан еще  id места, то добавим в массив
                {
                    $event = $post['event_id'];
                    if (is_array($event)) {
                        $event_id = '*';
                    } else {
                        $event_id = $event;
                    }
                } else {
                    $event_id = '*';
                }
                if (isset($post['date1']) && $post['date1'] != '')                                                          // если передан еще  id места, то добавим в массив
                {
                    $date1 = strtotime(date('Y-m-d H:i:s', strtotime($post['date1'])));
                }
                if (isset($post['date2']) && $post['date2'] != '')                                                          // если передан еще  id места, то добавим в массив
                {
                    $date2 = strtotime(date('Y-m-d H:i:s', strtotime($post['date2'] . '+23 hours 59 minutes 59 seconds')));
                }

                $log->addData($post, '$post', __LINE__);
                $log->addData($object, '$object', __LINE__);
                $log->addData($place, '$place', __LINE__);
                $log->addData($event, '$event', __LINE__);
                $log->addData($event_id, '$event_id', __LINE__);
                $log->addData($date1, '$date1', __LINE__);
                $log->addData($date2, '$date2', __LINE__);

                /**
                 * блок получения данных из кеша
                 */
                $response = self::methodGetEventsFromCache($mine_id, $event_id, $main_id);
                if ($response['status'] == 1) {
                    $log->addLogAll($response);

                    $events = $response['events'];
                    $log->addLog("Получил данные с кеша", count($events));

                    /** блок фильтрации данных по строке поиска из фронта */
                    if ($object != '' || $place != '' || $event != '' || $date2 != '' || $date1 != '') {
                        $log->addLog("Начал поиск по входным параметрам");

                        $events = array_filter($events, static function ($v) use ($search, $place, $event, $object, $date1, $date2) {
                            $title = false;

                            if ($search != '') {
                                if (strpos(mb_strtolower($v['event_title']), mb_strtolower($search)) !== false || strpos(mb_strtolower($v['place_title']), mb_strtolower($search)) !== false
                                    || strpos(mb_strtolower($v['object_title']), mb_strtolower($search)) !== false || strpos(mb_strtolower($v['event_type']), mb_strtolower($search)) !== false) {
                                    $title = true;
                                }
                            }
//                        var_dump(array_search($v['event_id'], $event));
                            return (($v['place_id'] == $place || $place == '') && ($v['object_id'] == $object || $object == '') && ($event == '' || is_numeric(array_search($v['event_id'], $event)))
                                && (strtotime($v['date_time']) >= $date1 || $date1 == '') && (strtotime($v['date_time']) <= $date2 || $date2 == '')
                                && ($title === true || $search == ''));
                        });

//                    $log->addData($events, '$events', __LINE__);

                    } else {
                        $log->addLog("Поиск не осуществлялся работа на прямую");
                    }
                    $events = array_merge(array(), $events);
                    usort($events, static function ($a, $b) {
                        return strtotime($a['date_time']) > strtotime($b['date_time']) ? -1 : 1;
                    });
                } else {
                    $log->addLog("СЛУЖБЫ СБОРА ДАННЫХ НЕ ЗАПУЩЕНА. КЕШ ПУСТ.");
                    $log->addLogAll($response);
//                throw new \Exception('СЛУЖБЫ СБОРА ДАННЫХ НЕ ЗАПУЩЕНА. КЕШ ПУСТ.');
                }
//            }
            $log->addLog("Окончание выполнения метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['events' => $events, 'Items' => $result], $log->getLogAll());
    }

    /** methodGetEventsFromCache - Функция получения событий из кэша
     * @param $mine_id - ключ шахты
     * @param string $event_id - ключ события
     * @param string $main_id - главный ключ объектов системы АМИКУМ
     * @return array|mixed
     * Moved by: Курбанов И. С. on 27.05.2019
     */
    public static function methodGetEventsFromCache($mine_id, $event_id = '*', $main_id = '*')
    {
        $log = new LogAmicumFront("methodGetEventsFromCache");

        $event_result = array();
        try {
            $log->addLog("Начал выполнять метод");

            $log->addData($mine_id, '$mine_id', __LINE__);
            $log->addData($event_id, '$event_id', __LINE__);
            $log->addData($main_id, '$main_id', __LINE__);

            $event_cache_controller = new EventCacheController();
            $response = $event_cache_controller->getEventsList($mine_id, $event_id, $main_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка при получении события из кеша');
            }
            $events = $response['Items'];
            if ($events) {
                ArrayHelper::multisort($events, 'date_time', SORT_DESC);
                /**
                 * блок формирования фильтра для выборки справчоника мест
                 */
                $i = 0;
                $events_filter = '';
                foreach ($events as $event) {
                    if ($event['edge_id'] == -1 || $event['edge_id'] == null || $event['edge_id'] == '') {
                        continue;
                    }

                    if ($i == 0) {
                        $events_filter = $event['edge_id'];
                        $i = 1;
                    } else {
                        $events_filter = $events_filter . ',' . $event['edge_id'];
                    }
                }

                $log->addData($events_filter, '$events_filter', __LINE__);

                /**
                 * блок получения справочника мест
                 */
                if ($events_filter != '') {
                    $edges = Edge::find()
                        ->with('place')
                        ->where('edge.id in (' . $events_filter . ')')
                        ->asArray()
                        ->all();

                    foreach ($edges as $edge) {
                        $place_dictionary[$edge['id']]['place_title'] = $edge['place']['title'];
                        $place_dictionary[$edge['id']]['place_id'] = $edge['place_id'];
                    }
                }

                /**
                 * блок получения справочника единиц измерений
                 */
                $parameters = Parameter::find()
                    ->with('unit')
                    ->asArray()
                    ->all();

                foreach ($parameters as $parameter) {
                    $parameter_dictionary[$parameter['id']]['unit_short'] = $parameter['unit']['short'];
                    $parameter_dictionary[$parameter['id']]['unit_id'] = $parameter['unit_id'];
                }

                /**
                 * блок получения справочника Событий
                 */
                $events_db = Event::find()
                    ->asArray()
                    ->all();

                foreach ($events_db as $event_db) {
                    $event_dictionary[$event_db['id']]['event_title'] = $event_db['title'];
                }

                /**
                 * блок получения справочника Статусов
                 */
                $statuses = Status::find()
                    ->asArray()
                    ->all();

                foreach ($statuses as $status1) {
                    $status_dictionary[$status1['id']]['status_title'] = $status1['title'];
                }

                /**
                 * блок формирования результирующего массива событий
                 */
                foreach ($events as $event) {
                    $event_with_place = $event;
                    $event_with_place['status_id'] = (string)$event['event_status_id'];
                    $event_with_place['object_type'] = (string)$event['object_table'];
                    if (isset($status_dictionary[$event['event_status_id']])) {
                        $event_with_place['status_title'] = $status_dictionary[$event['event_status_id']]['status_title'];
                        $event_with_place['event_type'] = $status_dictionary[$event['value_status_id']]['status_title'];
                    } else {
                        $event_with_place['status_title'] = "";
                        $event_with_place['event_type'] = "";
                    }
                    if (isset($event_dictionary[$event['event_id']])) {
                        $event_with_place['event_title'] = $event_dictionary[$event['event_id']]['event_title'];
                    } else {
                        $event_with_place['event_title'] = "";
                    }
                    if (isset($parameter_dictionary[$event['parameter_id']])) {
                        $event_with_place['unit_short'] = $parameter_dictionary[$event['parameter_id']]['unit_short'];
                    } else {
                        $event_with_place['unit_short'] = "";
                    }

                    if ($event['edge_id'] == -1 || $event['edge_id'] == null || $event['edge_id'] == '') {
                        $event_with_place['place_id'] = -1;
                        $event_with_place['place_title'] = '-';
                    } else {
                        $event_with_place['place_id'] = isset($place_dictionary[$event['edge_id']]) ? $place_dictionary[$event['edge_id']]['place_id'] : '-';
                        $event_with_place['place_title'] = isset($place_dictionary[$event['edge_id']]) ? $place_dictionary[$event['edge_id']]['place_title'] : '-';
                    }
                    $event_with_place['id'] = $event['event_journal_id'];
                    $event_with_place['parameter_value'] = (string)$event['value'];
                    $event_result[] = $event_with_place;
                }
                unset($parameter_dictionary);
                unset($event_dictionary);
                unset($status_dictionary);
                unset($event_with_place);
                unset($place_dictionary);
                unset($edges);
                unset($events_filter);
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Окончил выполнять метод");

        return array_merge(['events' => $event_result, 'Items' => []], $log->getLogAll());
    }

    /**
     * fillAllEventJournalCache - Функция заполнения кэша событий
     * @param $date_time - дата до которой строим кеш
     * @return array - стандартный набор
     * метод переработал Якимов М.Н. - заполняет кеш событий за указанную дату
     */
    // пример: 127.0.0.1/positioningsystem/event-journal/init-event-cache?date_time=2020-04-01
    public static function fillAllEventJournalCache($date_time)
    {
//        ini_set('max_execution_time', 3000);
//        ini_set('memory_limit', '2000M');
        $errors = array();                                                                                                //массив ошибок
        $result = array();
        $warnings = array();

        try {
            $warnings[] = "fillAllEventJournalCache. Начал выполнять метод";
            $delta_date = (date("Y-m-d", strtotime($date_time)));
            $warnings[] = $delta_date;
            $event_journal = (new Query())
                ->select([
                    'id AS event_journal_id',
                    'event_title',
                    'event_id',
                    //'status_title',
                    'status_id as event_status_id',
                    'status_id',
                    'edge_id',
                    'place_title',
                    'place_id',
                    'object_id',
                    'main_id',
                    'object_title',
                    'event_type',
                    'event_type_id as value_status_id',
                    'event_type_id',
                    'parameter_value as value',
                    'parameter_value',
                    'parameter_id',
                    'xyz',
                    'mine_id',
                    'date_time',
                    'unit_short',
                    'specific_object as object_table',
                    'group_alarm_id'
                ])
                ->from('view_event_journal')
                ->where('status_id in (38,39)')//ПОД ВОПРОСОМ!!!!!!!!!!!!!!!!!!! event_type_id <> 1
                ->andWhere("date_time > '" . $delta_date . "'")
                ->orderBy(['date_time' => SORT_ASC])
                ->all();
            $event_cache_controller = (new EventCacheController());
            $response = $event_cache_controller->multiSetEvent($event_journal);
            if ($response['status'] == 1) {
                $status = $response['status'];
                $result = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('fillAllEventJournalCache. Ошибка при получении события из кеша');
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "fillAllEventJournalCache. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $warnings[] = "fillAllEventJournalCache. Окончил выполнять метод";
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);

    }

    // actionInitEventCache - метод по заполнению кеша событий за указанную дату
    // пример: 127.0.0.1/positioningsystem/event-journal/init-event-cache?date_time=2020-04-01
    public function actionInitEventCache()
    {
        $post = Assistant::GetServerMethod();
        $date_time = $post['date_time'];
        $result = self::fillAllEventJournalCache($date_time);                                                                   //вызов функции инициализации кэша по соыбтиям
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    public function actionGetMineList()
    {
        /**
         * получаем список шахт
         */
        $mines = (new Query())
            ->select('id, title')
            ->from('mine')
            ->orderBy(['title' => SORT_ASC])
            ->all();

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $mines;

    }
}
