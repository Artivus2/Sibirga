<?php

namespace frontend\controllers;

use backend\controllers\Assistant as BackendAssistant;
use backend\controllers\cachemanagers\EventCacheController;
use backend\controllers\cachemanagers\SituationCacheController;
use backend\controllers\const_amicum\EventEnumController;
use Exception;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Event;
use frontend\models\EventCompareGas;
use frontend\models\EventJournal;
use frontend\models\EventJournalCorrectMeasure;
use frontend\models\EventJournalGilty;
use frontend\models\EventJournalStatus;
use frontend\models\EventStatus;
use frontend\models\KindReason;
use frontend\models\Situation;
use frontend\models\SituationJournal;
use frontend\models\SituationJournalCorrectMeasure;
use frontend\models\SituationJournalGilty;
use frontend\models\SituationJournalStatus;
use frontend\models\SituationStatus;
use frontend\models\Status;
use Throwable;
use Yii;
use yii\db\Query;

class EventCompareGasController extends \yii\web\Controller
{
    // внешние:
    // industrial_safety\Checking\GetPlacesList()           - получение списка мест из справочника мест
    // handbooks\HandbookOperation\GetOperationsList        - Метод возвращает список опираций по структуре
    // handbooks\HandbookEmployee\GetWorkersForHandbook     - Метод получения списка людей

    // местные:
    // GetLampJournal               - метод получения журнала событий по светильникам
    // GetStaticJournal             - метод получения журнала событий по стационарным датчикам

    // GetListKindReason            - получить список  причин отказов/событий

    // SaveNewKindReason            - метод сохранения нового вида причин отказа/события

    // SaveEventJournalStatus       - метод сохранения описания причин отказа светильника/датчика

    // SaveIgnoreStatus             - изменение статуса игнорирования события
    // SaveIgnoreSituationStatus    - изменение статуса игнорирования ситуации

    // GetStatisticGas              - получение статистики по газам
    // GetStatisticSituation        - получение статистики по ситуациям
    // GetStatisticLamp             - получение статистики по расхождению показаний по лампам
    // GetStatisticStatic           - получение статистики по расхождению показаний по стационарным датчикам

    // GetListStatusEDB             - получить список статусов событий Единой диспетчерской по безопасности
    // GetListStatusSituationEDB    - получить список статусов ситуаций Единой диспетчерской по безопасности
    // GetListEvent                 - получить список событий
    // GetListSituation             - получить список ситуаций
    // GetListSituationJournal      - получить список журнала ситуаций

    // SaveEventGournalStatus       - метод сохранения объяснения причин события/отказа
    // SaveSituationJournalStatus   - метод сохранения объяснения причин ситуации

    // GetABJournal                 - метод получения журнала событий  оператора АГК
    // GetABJournalSituation        - метод получения журнала ситуаций  оператора АГК

    // GetHystoryStaticCompare      - метод получения истории по СТАЦИОНАРУ сравнений двух газов
    // GetHystoryLampCompare        - метод получения истории ЛАМПЫ сравнений двух газов

    // GetTypicalObjectList         - метод получения списка типовых объектов - видов и типов


    // GetTypicalObjectList - метод получения списка типовых объектов - видов и типов
    // разработал: Якимов М.Н.
    // дата: 07.12.2019г
    // пример: http://127.0.0.1/read-manager-amicum?controller=EventCompareGas&method=GetTypicalObjectList&subscribe=&data={}
    public static function GetTypicalObjectList($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // Массив результата
        $warnings[] = 'GetTypicalObjectList. Начало метода';
        try {
            $typical_objects = (new Query())
                ->select(
                    [
                        'object_id',                                                                                    //айди типового объекта
                        'object_type_id',                                                                               //айди типа типового объекта
                        'kind_object_id'                                                                                //айди вида типового объекта
                    ])
                ->from(['view_type_object'])
                ->indexBy('object_id')
                ->all();
            if (!$typical_objects) {
                $result = (object)array();
            } else {
                $result = $typical_objects;
            }

        } catch (Throwable $exception) {
            $errors[] = 'GetTypicalObjectList. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetTypicalObjectList. Конец метода';
        if (!isset($journal_lamp)) {
            $journal_lamp = (object)array();
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetLampJournal - метод получения журнала событий по светильникам
    // входные параметры:
    //      date_time - дата за которую хотим получить журнал
    //      shift_id  - смена за которую хотим получить журнал
    // разработал: Якимов М.Н.
    // дата: 07.12.2019г
    // пример: http://127.0.0.1/read-manager-amicum?controller=EventCompareGas&method=GetLampJournal&subscribe=&data={%22date_time%22:%222019-09-24%22,%22shift_id%22:1}
    public static function GetLampJournal($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'GetLampJournal. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetLampJournal. Данные с фронта не получены');
            }
            $warnings[] = 'GetLampJournal. Данные успешно переданы';
            $warnings[] = 'GetLampJournal. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'GetLampJournal. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'date_time')) ||
                !(property_exists($post_dec, 'shift_id'))
            ) {
                throw new Exception('GetLampJournal. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'GetLampJournal. Данные с фронта получены и они правильные';
            $date_time = $post_dec->date_time;
            $shift_id = $post_dec->shift_id;

            $filter_mine = array();
            $filter_mine_static = array();
            if (property_exists($post_dec, 'mine_id') and $post_dec->mine_id != -1) {
                $filter_mine = ['event_journal.mine_id' => $post_dec->mine_id];
                $filter_mine_static = ['event_compare_gas.static_mine_id' => $post_dec->mine_id];
            }

            // составляем фильтр для выбора всех событий из event_journal
            $response = Assistant::GetDateTimeByShift($date_time, $shift_id);
            $date_time_event_start = $response['date_time_start'];
            $date_time_event_end = $response['date_time_end'];

            $warnings[] = 'shift_id: ' . $shift_id;
            $warnings[] = 'date_time_event_start: ' . $date_time_event_start;
            $warnings[] = 'date_time_event_end: ' . $date_time_event_end;

            // получаем события из заданный период из таблицы event_journal
            $event_journal = EventJournal::find()
                ->select([
                    'event_journal.id as id',
                    'sensor.id as sensor_id',
                    'sensor.title as sensor_title',
                    'event.id as event_id',
                    'event.title as event_title',
                    'event_journal.date_time as event_date_time'
                ])
                ->innerJoin('sensor', 'sensor.id=event_journal.main_id')
                ->innerJoin('event_compare_gas', 'event_compare_gas.lamp_event_journal_id=event_journal.id')
                ->joinWith('event')
                ->joinWith('eventJournalStatuses')
                ->where("event_journal.date_time>'" . $date_time_event_start . "'")
                ->andWhere("event_journal.date_time<'" . $date_time_event_end . "'")
                ->andWhere([
                    'event_journal.object_id' => 47,
                    'event_journal.status_id' => 44,
                ])
                ->andWhere(['IN', 'event_journal.event_id', [22409, 7130, 22411]])
                ->andFilterWhere($filter_mine)
                ->asArray()
                ->all();
//            if (!$event_journal) {
//                throw new Exception("GetLampJournal. Журнал событий пуст");
//            }
            if ($event_journal) {
                // получаем события сравнения двух газов из таблицы event_journal_gas
                $event_compare_gas = EventCompareGas::find()
                    ->joinWith('lampParameter')
                    ->joinWith('staticParameter')
                    ->joinWith('lampEdge.lampPlace')
                    ->joinWith('staticEdge.staticPlace')
                    ->where("date_time>'" . $date_time_event_start . "'")
                    ->andWhere("date_time<'" . $date_time_event_end . "'")
                    ->andFilterWhere($filter_mine_static)
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
                    $event_compare_item['sensor_id'] = $event_compare['lamp_sensor_id'];
                    $event_compare_item['sensor_title'] = $event_compare['lamp_object_title'];
                    if ($event_compare['date_time']) {
                        $event_compare_item['event_date_time'] = date('d.m.Y H:i:s', strtotime($event_compare['date_time']));
                    } else {
                        $event_compare_item['event_date_time'] = "";
                    }
                    $event_compare_item['sensor_value'] = $event_compare['lamp_value'];
                    $event_compare_item['unit_id'] = $event_compare['lampParameter']['unit_id'];

                    if ($event_compare['lamp_edge_id']) {
                        $event_compare_item['place_id'] = $event_compare['lampEdge']['place_id'];
                        $event_compare_item['place_title'] = $event_compare['lampEdge']['lampPlace']['title'];
                    } else {
                        $event_compare_item['place_id'] = -1;
                        $event_compare_item['place_title'] = '';
                    }
                    $event_compare_item['sensor2_id'] = $event_compare['static_sensor_id'];
                    $event_compare_item['sensor2_title'] = $event_compare['static_object_title'];
                    if ($event_compare['date_time']) {
                        $event_compare_item['event_date_time2'] = date('d.m.Y H:i:s', strtotime($event_compare['date_time']));
                    } else {
                        $event_compare_item['event_date_time2'] = "";
                    }
                    $event_compare_item['sensor2_value'] = $event_compare['static_value'];
                    $event_compare_item['unit2_id'] = $event_compare['staticParameter']['unit_id'];
                    if ($event_compare['static_edge_id']) {
                        $event_compare_item['place2_id'] = $event_compare['staticEdge']['place_id'];
                        $event_compare_item['place2_title'] = $event_compare['staticEdge']['staticPlace']['title'];
                    } else {
                        $event_compare_item['place2_id'] = -1;
                        $event_compare_item['place2_title'] = '';
                    }
                    $event_compare_gas_array[$event_compare['lamp_sensor_id']][] = $event_compare_item;
                }

                foreach ($event_journal as $event_lamp) {
                    $journal_lamp[$event_lamp['id']]['journal_ml_id'] = $event_lamp['id'];
                    $journal_lamp[$event_lamp['id']]['event_journal_id'] = $event_lamp['id'];
                    $journal_lamp[$event_lamp['id']]['sensor_id'] = $event_lamp['sensor_id'];
                    $journal_lamp[$event_lamp['id']]['sensor_title'] = $event_lamp['sensor_title'];
                    $journal_lamp[$event_lamp['id']]['event_date_time'] = date('d.m.Y H:i:s', strtotime($event_lamp['event_date_time']));
                    $journal_lamp[$event_lamp['id']]['event_id'] = $event_lamp['event_id'];
                    $journal_lamp[$event_lamp['id']]['event_title'] = $event_lamp['event_title'];
                    if (isset($event_compare_gas_array[$event_lamp['sensor_id']])) {
                        $journal_lamp[$event_lamp['id']]['count_event'] = count($event_compare_gas_array[$event_lamp['sensor_id']]);
                        $journal_lamp[$event_lamp['id']]['sensor_places'] = $event_compare_gas_array[$event_lamp['sensor_id']];
                    } else {
                        $journal_lamp[$event_lamp['id']]['count_event'] = 0;
                        $journal_lamp[$event_lamp['id']]['sensor_places'] = (object)array();
                    }
                    if ($event_lamp['eventJournalStatuses']) {
                        foreach ($event_lamp['eventJournalStatuses'] as $eventJournalStatuses) {
                            $journal_lamp[$event_lamp['id']]['event_journal_status_id'] = $eventJournalStatuses['id'];
                            $journal_lamp[$event_lamp['id']]['description'] = $eventJournalStatuses['description'];
                            $journal_lamp[$event_lamp['id']]['kind_reason_id'] = $eventJournalStatuses['kind_reason_id'];
                            $journal_lamp[$event_lamp['id']]['check_done_worker_id'] = $eventJournalStatuses['worker_id'];
                            if ($eventJournalStatuses['check_done_date_time']) {
                                $journal_lamp[$event_lamp['id']]['status_done']['date_time'] = date('d.m.Y H:i:s', strtotime($eventJournalStatuses['check_done_date_time']));
                            } else {
                                $journal_lamp[$event_lamp['id']]['status_done']['date_time'] = "";
                            }
                            if ($eventJournalStatuses['check_ignore_date_time']) {
                                $journal_lamp[$event_lamp['id']]['status_ignore']['date_time'] = date('d.m.Y H:i:s', strtotime($eventJournalStatuses['check_ignore_date_time']));
                            } else {
                                $journal_lamp[$event_lamp['id']]['status_ignore']['date_time'] = "";
                            }
                            $journal_lamp[$event_lamp['id']]['status_done']['status'] = $eventJournalStatuses['check_done_status'];

                            $journal_lamp[$event_lamp['id']]['status_ignore']['status'] = $eventJournalStatuses['check_ignore_status'];

                        }
                    } else {
                        $journal_lamp[$event_lamp['id']]['description'] = "";
                        $journal_lamp[$event_lamp['id']]['event_journal_status_id'] = -1;
                        $journal_lamp[$event_lamp['id']]['check_done_worker_id'] = -1;
                        $journal_lamp[$event_lamp['id']]['kind_reason_id'] = -1;
                        $journal_lamp[$event_lamp['id']]['status_done']['status'] = null;
                        $journal_lamp[$event_lamp['id']]['status_done']['date_time'] = "";
                        $journal_lamp[$event_lamp['id']]['status_ignore']['status'] = null;
                        $journal_lamp[$event_lamp['id']]['status_ignore']['date_time'] = "";
                    }
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetLampJournal. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetLampJournal. Конец метода';
        if (!isset($journal_lamp)) {
            $journal_lamp = (object)array();
        }

        return array('Items' => $journal_lamp, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // GetStaticJournal - метод получения журнала событий по стационарным датчикам
    // входные параметры:
    //      date_time - дата за которую хотим получить журнал
    //      shift_id  - смена за которую хотим получить журнал
    // разработал: Якимов М.Н.
    // дата: 07.12.2019г
    // пример: http://127.0.0.1/read-manager-amicum?controller=EventCompareGas&method=GetStaticJournal&subscribe=&data={%22date_time%22:%222019-09-24%22,%22shift_id%22:1}
    public static function GetStaticJournal($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $method_name = "GetStaticJournal";
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'GetStaticJournal. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetStaticJournal. Данные с фронта не получены');
            }
            $warnings[] = 'GetStaticJournal. Данные успешно переданы';
            $warnings[] = 'GetStaticJournal. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'GetStaticJournal. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'date_time')) ||
                !(property_exists($post_dec, 'shift_id'))
            ) {
                throw new Exception('GetStaticJournal. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'GetStaticJournal. Данные с фронта получены и они правильные';
            $date_time = $post_dec->date_time;
            $shift_id = $post_dec->shift_id;

            $filter_mine = array();
            $filter_mine_static = array();
            if (property_exists($post_dec, 'mine_id') and $post_dec->mine_id != -1) {
                $filter_mine = ['event_journal.mine_id' => $post_dec->mine_id];
                $filter_mine_static = ['event_compare_gas.static_mine_id' => $post_dec->mine_id];
            }

            // составляем фильтр для выбора всех событий из event_journal
            $response = Assistant::GetDateTimeByShift($date_time, $shift_id);
            $date_time_event_start = $response['date_time_start'];
            $date_time_event_end = $response['date_time_end'];

            $warnings[] = 'shift_id: ' . $shift_id;
            $warnings[] = 'date_time_event_start: ' . $date_time_event_start;
            $warnings[] = 'date_time_event_end: ' . $date_time_event_end;

            $warnings[] = $method_name . ". date_time_event_start=" . $date_time_event_start;
            $warnings[] = $method_name . ". date_time_event_end=" . $date_time_event_end;
            // получаем события из заданный период из таблицы event_journal
            $event_journal = EventJournal::find()
                ->select([
                    'event_journal.id as id',
                    'sensor.id as sensor_id',
                    'sensor.title as sensor_title',
                    'event.id as event_id',
                    'event.title as event_title',
                    'event_journal.date_time as event_date_time'
                ])
                ->innerJoin('sensor', 'sensor.id=event_journal.main_id')
                ->innerJoin('event_compare_gas', 'event_compare_gas.static_event_journal_id=event_journal.id')
                ->joinWith('event')
                ->joinWith('eventJournalStatuses')
                ->where("event_journal.date_time>'" . $date_time_event_start . "'")
                ->andWhere("event_journal.date_time<'" . $date_time_event_end . "'")
                ->andWhere([
                    'event_journal.object_id' => 28,
                ])
                ->andWhere(['IN', 'event_journal.event_id', [22409, 7130, 22411]])
                ->andFilterWhere($filter_mine)
                ->asArray()
                ->all();
//            if (!$event_journal) {
//                throw new Exception("GetStaticJournal. Журнал событий пуст");
//            }

            if ($event_journal) {
                // получаем события сравнения двух газов из таблицы event_journal_gas
                $event_compare_gas = EventCompareGas::find()
                    ->joinWith('lampParameter')
                    ->joinWith('staticParameter')
                    ->joinWith('lampEdge.lampPlace')
                    ->joinWith('staticEdge.staticPlace')
                    ->where("date_time>'" . $date_time_event_start . "'")
                    ->andWhere("date_time<'" . $date_time_event_end . "'")
                    ->andFilterWhere($filter_mine_static)
                    ->asArray()
                    ->all();

                // готовим данные по сравнению в удобнов виде
                foreach ($event_compare_gas as $event_compare) {
                    $event_compare_item['event_compare_gas_id'] = $event_compare['id'];
                    if ($event_compare['lamp_value'] > 1 or $event_compare['static_value'] > 2) {
                        $event_compare_item['pdk_status'] = 1;  // статус превышения ПДК: 1 есть, 0 нет
                    } else {
                        $event_compare_item['pdk_status'] = 0;
                    }
                    $event_compare_item['sensor2_id'] = $event_compare['lamp_sensor_id'];
                    $event_compare_item['sensor2_title'] = $event_compare['lamp_object_title'];
                    if ($event_compare['date_time']) {
                        $event_compare_item['event_date_time2'] = date('d.m.Y H:i:s', strtotime($event_compare['date_time']));
                    } else {
                        $event_compare_item['event_date_time2'] = "";
                    }
                    $event_compare_item['sensor2_value'] = $event_compare['lamp_value'];
                    $event_compare_item['unit2_id'] = $event_compare['lampParameter']['unit_id'];

                    if ($event_compare['lamp_edge_id']) {
                        $event_compare_item['place2_id'] = $event_compare['lampEdge']['place_id'];
                        $event_compare_item['place2_title'] = $event_compare['lampEdge']['lampPlace']['title'];
                    } else {
                        $event_compare_item['place2_id'] = -1;
                        $event_compare_item['place2_title'] = '';
                    }
                    $event_compare_item['sensor_id'] = $event_compare['static_sensor_id'];
                    $event_compare_item['sensor_title'] = $event_compare['static_object_title'];
                    if ($event_compare['date_time']) {
                        $event_compare_item['event_date_time'] = date('d.m.Y H:i:s', strtotime($event_compare['date_time']));
                    } else {
                        $event_compare_item['event_date_time'] = "";
                    }
                    $event_compare_item['sensor_value'] = $event_compare['static_value'];
                    $event_compare_item['unit_id'] = $event_compare['staticParameter']['unit_id'];
                    if ($event_compare['static_edge_id']) {
                        $event_compare_item['place_id'] = $event_compare['staticEdge']['place_id'];
                        $event_compare_item['place_title'] = $event_compare['staticEdge']['staticPlace']['title'];
                    } else {
                        $event_compare_item['place_id'] = -1;
                        $event_compare_item['place_title'] = '';
                    }
                    $event_compare_gas_array[$event_compare['static_sensor_id']][] = $event_compare_item;
                }

                foreach ($event_journal as $event_static) {
                    $journal_lamp[$event_static['id']]['journal_ab_id'] = $event_static['id'];
                    $journal_lamp[$event_static['id']]['event_journal_id'] = $event_static['id'];
                    $journal_lamp[$event_static['id']]['sensor_id'] = $event_static['sensor_id'];
                    $journal_lamp[$event_static['id']]['sensor_title'] = $event_static['sensor_title'];
                    $journal_lamp[$event_static['id']]['event_date_time'] = date('d.m.Y H:i:s', strtotime($event_static['event_date_time']));
                    $journal_lamp[$event_static['id']]['event_id'] = $event_static['event_id'];
                    $journal_lamp[$event_static['id']]['event_title'] = $event_static['event_title'];
                    if (isset($event_compare_gas_array[$event_static['sensor_id']])) {
                        $journal_lamp[$event_static['id']]['count_event'] = count($event_compare_gas_array[$event_static['sensor_id']]);
                        $journal_lamp[$event_static['id']]['sensor_places'] = $event_compare_gas_array[$event_static['sensor_id']];
                    } else {
                        $journal_lamp[$event_static['id']]['count_event'] = 0;
                        $journal_lamp[$event_static['id']]['sensor_places'] = (object)array();
                    }
                    if ($event_static['eventJournalStatuses']) {
                        foreach ($event_static['eventJournalStatuses'] as $eventJournalStatuses) {
                            $journal_lamp[$event_static['id']]['event_journal_status_id'] = $eventJournalStatuses['id'];
                            $journal_lamp[$event_static['id']]['description'] = $eventJournalStatuses['description'];
                            $journal_lamp[$event_static['id']]['kind_reason_id'] = $eventJournalStatuses['kind_reason_id'];
                            $journal_lamp[$event_static['id']]['check_done_worker_id'] = $eventJournalStatuses['worker_id'];
                            if ($eventJournalStatuses['check_done_date_time']) {
                                $journal_lamp[$event_static['id']]['status_done']['date_time'] = date('d.m.Y H:i:s', strtotime($eventJournalStatuses['check_done_date_time']));
                            } else {
                                $journal_lamp[$event_static['id']]['status_done']['date_time'] = "";
                            }
                            if ($eventJournalStatuses['check_ignore_date_time']) {
                                $journal_lamp[$event_static['id']]['status_ignore']['date_time'] = date('d.m.Y H:i:s', strtotime($eventJournalStatuses['check_ignore_date_time']));
                            } else {
                                $journal_lamp[$event_static['id']]['status_ignore']['date_time'] = "";
                            }
                            $journal_lamp[$event_static['id']]['status_done']['status'] = $eventJournalStatuses['check_done_status'];

                            $journal_lamp[$event_static['id']]['status_ignore']['status'] = $eventJournalStatuses['check_ignore_status'];

                        }
                    } else {
                        $journal_lamp[$event_static['id']]['description'] = "";
                        $journal_lamp[$event_static['id']]['event_journal_status_id'] = -1;
                        $journal_lamp[$event_static['id']]['check_done_worker_id'] = -1;
                        $journal_lamp[$event_static['id']]['kind_reason_id'] = -1;
                        $journal_lamp[$event_static['id']]['status_done']['status'] = null;
                        $journal_lamp[$event_static['id']]['status_done']['date_time'] = "";
                        $journal_lamp[$event_static['id']]['status_ignore']['status'] = null;
                        $journal_lamp[$event_static['id']]['status_ignore']['date_time'] = "";
                    }
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetStaticJournal. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetStaticJournal. Конец метода';
        if (!isset($journal_lamp)) {
            $journal_lamp = (object)array();
        }
        $result_main = array('Items' => $journal_lamp, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetListKindReason - получить список  причин отказов/событий
    // пример: http://127.0.0.1/read-manager-amicum?controller=EventCompareGas&method=GetListKindReason&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetListKindReason($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // Массив ошибок

        try {
            $kind_reason_list = KindReason::find()
                ->limit(20000)
                ->indexBy('id')
                ->asArray()
                ->all();

            if (!$kind_reason_list) {
                $warnings[] = 'GetListKindReason. Справочник видов причит событий/откзаов пуст';
                $result = (object)array();
            } else {
                $result = $kind_reason_list;
            }
        } catch (Throwable $exception) {
            $warnings[] = 'GetListKindReason. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // SaveNewKindReason - метод сохранения нового вида причин отказа/события
    public static function SaveNewKindReason($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'SaveNewKindReason. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('SaveNewKindReason. Данные с фронта не получены');
            }
            $warnings[] = 'SaveNewKindReason. Данные успешно переданы';
            $warnings[] = 'SaveNewKindReason. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'SaveNewKindReason. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'kind_reason_obj'))
            ) {
                throw new Exception('SaveNewKindReason. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'SaveNewKindReason. Данные с фронта получены и они правильные';
            $kind_reason_obj = $post_dec->kind_reason_obj;
            $kind_reason = KindReason::findOne(['title' => $kind_reason_obj->kind_reason_title]);                       //находим место в БД в таблице place (Список мест)
            if ($kind_reason) {
                throw new Exception('SaveNewKindReason. Такая причина события/отказа уже существует');
            }

            $kind_reason = new KindReason();

            $kind_reason->title = $kind_reason_obj->kind_reason_title;
            if ($kind_reason->save()) {
                $kind_reason->refresh();
                $kind_reason_id = $kind_reason->id;
            } else {
                $errors[] = $kind_reason->errors;
                throw new Exception('SaveNewKindReason. Ошибка сохранения модели причин события/отказа KindReason');
            }
            $kind_reason_obj->kind_reason_id = $kind_reason_id;

        } catch (Throwable $exception) {
            $errors[] = 'SaveNewKindReason. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'SaveNewKindReason. Конец метода';
        if (!isset($kind_reason_obj)) {
            $result = (object)array();
        } else {
            $result = $kind_reason_obj;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // SaveEventJournalStatus - метод сохранения описания причин отказа светильника/датчика
    // метод ждет следующую структуру:
    //  event_journal: {
    //      event_journal_id            - ключ события из журнала
    //      event_journal_status_id     - ключ коментария к событию из журнала
    //      check_done_worker_id        - ключ работника изменяющего статус
    //      kind_reason_id              - ключ причины события/отказа
    //      description                 - ручное описание причины отказа события
    //      status_done: {              - статус устранения события
    //              date_time           - дата изменения статуса устранения события
    //              status              - итоговый статус (0/1)
    //          }
    //      status_ignore: {            - статус игнорирования события
    //              date_time           - дата изменения статуса игнорирования события
    //              status              - итоговый статус (0/1)
    //          }
    public static function SaveEventJournalStatus($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'SaveEventJournalStatus. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('SaveEventJournalStatus. Данные с фронта не получены');
            }
            $warnings[] = 'SaveEventJournalStatus. Данные успешно переданы';
            $warnings[] = 'SaveEventJournalStatus. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'SaveEventJournalStatus. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'event_journal'))
            ) {
                throw new Exception('SaveEventJournalStatus. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'SaveEventJournalStatus. Данные с фронта получены и они правильные';
            $event_journal = $post_dec->event_journal;

            $event_journal_status = new EventJournalStatus();

            $event_journal_status->event_journal_id = $event_journal->event_journal_id;
            $event_journal_status->date_time = BackendAssistant::GetDateNow();
            $event_journal_status->worker_id = $event_journal->check_done_worker_id;
            $event_journal_status->kind_reason_id = $event_journal->kind_reason_id;
            $event_journal_status->description = $event_journal->description;
            if ($event_journal->status_done->date_time) {
                $event_journal_status->check_done_date_time = date("Y-m-d H:i:s", strtotime($event_journal->status_done->date_time));
                $event_journal_status->check_done_status = $event_journal->status_done->status;
            }
            if ($event_journal->status_ignore->date_time) {
                $event_journal_status->check_ignore_date_time = date("Y-m-d H:i:s", strtotime($event_journal->status_ignore->date_time));
                $event_journal_status->check_ignore_status = $event_journal->status_ignore->status;
            }
            if ($event_journal_status->save()) {
                $event_journal_status->refresh();
                $event_journal_status_id = $event_journal_status->id;
            } else {
                $errors[] = $event_journal_status->errors;
                throw new Exception('SaveEventJournalStatus. Ошибка сохранения модели EventJournalStatus');
            }
            $event_journal->event_journal_status_id = $event_journal_status_id;

        } catch (Throwable $exception) {
            $errors[] = 'SaveEventJournalStatus. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'SaveEventJournalStatus. Конец метода';
        if (!isset($event_journal)) {
            $result = (object)array();
        } else {
            $result = $event_journal;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // SaveIgnoreStatus - изменение статуса игнорирования события
    // метод ждет:
    // event_journal_id:            ключ события
    // event_journal_status_id      ключ журнала устранения событий
    // status_ignore: {             статус игнорирования событий
    //      date_time:              дата любая
    //      status:                 новый статус
    //      }
    //
    public static function SaveIgnoreStatus($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $event_journal_id = -1;                                                                                         // Флаг успешного выполнения метода
        $event_journal_status_id = -1;                                                                                  // Флаг успешного выполнения метода
        $status_ignore = (object)array();                                                                               // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'SaveIgnoreStatus. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('SaveIgnoreStatus. Данные с фронта не получены');
            }
            $warnings[] = 'SaveIgnoreStatus. Данные успешно переданы';
            $warnings[] = 'SaveIgnoreStatus. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'SaveIgnoreStatus. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'event_journal_id')) ||
                !(property_exists($post_dec, 'event_journal_status_id')) ||
                !(property_exists($post_dec, 'status_ignore'))
            ) {
                throw new Exception('SaveIgnoreStatus. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'SaveIgnoreStatus. Данные с фронта получены и они правильные';
            $event_journal_id = $post_dec->event_journal_id;
            $event_journal_status_id = $post_dec->event_journal_status_id;
            $status_ignore = $post_dec->status_ignore;
            $session = Yii::$app->session;
            $last_event_journal_status = EventJournalStatus::findOne(['id' => $event_journal_status_id]);
            if ($last_event_journal_status) {
                $kind_reason_id = $last_event_journal_status->kind_reason_id;
                $description = $last_event_journal_status->description;
                $check_done_date_time = $last_event_journal_status->check_done_date_time;
                $check_done_status = $last_event_journal_status->check_done_status;
            } else {
                $kind_reason_id = null;
                $description = "";
                $check_done_date_time = null;
                $check_done_status = 0;
            }
            $event_journal_status = new EventJournalStatus();

            $event_journal_status->event_journal_id = $event_journal_id;
            $event_journal_status->date_time = BackendAssistant::GetDateNow();
            $event_journal_status->worker_id = $session['worker_id'];
            $event_journal_status->kind_reason_id = $kind_reason_id;
            $event_journal_status->description = $description;

            $event_journal_status->check_done_date_time = $check_done_date_time;
            $event_journal_status->check_done_status = $check_done_status;

            if ($status_ignore->date_time) {
                $event_journal_status->check_ignore_date_time = BackendAssistant::GetDateNow();
                $event_journal_status->check_ignore_status = $status_ignore->status;
            }
            if ($event_journal_status->save()) {
                $event_journal_status->refresh();
                $event_journal_status_id = $event_journal_status->id;
                $status_ignore->date_time = date('d.m.Y H:i:s', strtotime($event_journal_status->check_ignore_date_time));
            } else {
                $errors[] = $event_journal_status->errors;
                throw new Exception('SaveIgnoreStatus. Ошибка сохранения модели EventJournalStatus');
            }

        } catch (Throwable $exception) {
            $errors[] = 'SaveIgnoreStatus. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'SaveIgnoreStatus. Конец метода';

        $result = array('event_journal_status_id' => $event_journal_status_id, 'status_ignore' => $status_ignore, 'event_journal_id' => $event_journal_id);

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // SaveIgnoreSituationStatus - изменение статуса игнорирования ситуации
    // метод ждет:
    // situation_journal_id:            ключ события
    // situation_journal_status_id      ключ журнала устранения событий
    // status_ignore: {             статус игнорирования событий
    //      date_time:              дата любая
    //      status:                 новый статус
    //      }
    //
    public static function SaveIgnoreSituationStatus($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $situation_journal_id = -1;                                                                                     // Флаг успешного выполнения метода
        $situation_journal_status_id = -1;                                                                              // Флаг успешного выполнения метода
        $status_ignore = (object)array();                                                                               // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'SaveIgnoreSituationStatus. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('SaveIgnoreSituationStatus. Данные с фронта не получены');
            }
            $warnings[] = 'SaveIgnoreSituationStatus. Данные успешно переданы';
            $warnings[] = 'SaveIgnoreSituationStatus. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'SaveIgnoreSituationStatus. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'situation_journal_id')) ||
                !(property_exists($post_dec, 'situation_journal_status_id')) ||
                !(property_exists($post_dec, 'status_ignore'))
            ) {
                throw new Exception('SaveIgnoreSituationStatus. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'SaveIgnoreSituationStatus. Данные с фронта получены и они правильные';
            $situation_journal_id = $post_dec->situation_journal_id;
            $situation_journal_status_id = $post_dec->situation_journal_status_id;
            $status_ignore = $post_dec->status_ignore;
            $session = Yii::$app->session;
            $last_situation_journal_status = SituationJournalStatus::findOne(['id' => $situation_journal_status_id]);
            if ($last_situation_journal_status) {
                $kind_reason_id = $last_situation_journal_status->kind_reason_id;
                $description = $last_situation_journal_status->description;
                $check_done_date_time = $last_situation_journal_status->check_done_date_time;
                $check_done_status = $last_situation_journal_status->check_done_status;
            } else {
                $kind_reason_id = null;
                $description = "";
                $check_done_date_time = null;
                $check_done_status = 0;
            }
            $situation_journal_status = new SituationJournalStatus();

            $situation_journal_status->situation_journal_id = $situation_journal_id;
            $situation_journal_status->date_time = BackendAssistant::GetDateNow();
            $situation_journal_status->worker_id = $session['worker_id'];
            $situation_journal_status->kind_reason_id = $kind_reason_id;
            $situation_journal_status->description = $description;

            $situation_journal_status->check_done_date_time = $check_done_date_time;
            $situation_journal_status->check_done_status = $check_done_status;

            if ($status_ignore->date_time) {
                $situation_journal_status->check_ignore_date_time = BackendAssistant::GetDateNow();
                $situation_journal_status->check_ignore_status = $status_ignore->status;
            }
            if ($situation_journal_status->save()) {
                $situation_journal_status->refresh();
                $situation_journal_status_id = $situation_journal_status->id;
                $status_ignore->date_time = date('d.m.Y H:i:s', strtotime($situation_journal_status->check_ignore_date_time));
            } else {
                $errors[] = $situation_journal_status->errors;
                throw new Exception('SaveIgnoreSituationStatus. Ошибка сохранения модели SituationJournalStatus');
            }

        } catch (Throwable $exception) {
            $errors[] = 'SaveIgnoreSituationStatus. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'SaveIgnoreSituationStatus. Конец метода';

        $result = array('situation_journal_status_id' => $situation_journal_status_id, 'status_ignore' => $status_ignore, 'situation_journal_id' => $situation_journal_id);

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetStatisticGas - получение статистики по газам
    // метод ждет:
    //      year: 2019,
    //      period: 'monthly/weekly'
    // http://127.0.0.1/read-manager-amicum?controller=EventCompareGas&method=GetStatisticGas&subscribe=&data={%22year%22:%222019%22,%22period%22:%22monthly%22}
    // http://127.0.0.1/read-manager-amicum?controller=EventCompareGas&method=GetStatisticGas&subscribe=&data={%22year%22:%222019%22,%22period%22:%22weekly%22}
    public static function GetStatisticGas($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $individual_sensors = array();
        $stationary_sensors = array();
        $warnings[] = 'GetStatisticGas. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetStatisticGas. Данные с фронта не получены');
            }
            $warnings[] = 'GetStatisticGas. Данные успешно переданы';
            $warnings[] = 'GetStatisticGas. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'GetStatisticGas. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'year')) ||
                !(property_exists($post_dec, 'period'))
            ) {
                throw new Exception('GetStatisticGas. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'GetStatisticGas. Данные с фронта получены и они правильные';
            $year = $post_dec->year;
            $period = $post_dec->period;

            $filter_mine = array();
            if (property_exists($post_dec, 'mine_id') and $post_dec->mine_id != -1) {
                $filter_mine = ['event_journal.mine_id' => $post_dec->mine_id];
            }

            if ($period == 'monthly') {
                // получаем общее количество событий по индивидуальным средствам из таблицы event_journal
                // 22409 - Превышение CH4 со светильника
                // 7130  - Превышение концентрации газа
                // 22411 - Датчик требует проверки на поверочной смеси
                $total_events_count_lamp = EventJournal::find()
                    ->select([
                        'COUNT(event_journal.id) as count_event_all',
                        'MONTH(event_journal.date_time) as month_count'
                    ])
                    ->where("YEAR(event_journal.date_time)='" . $year . "'")
                    ->andWhere([
                        'event_journal.object_id' => 47,
                        'event_journal.status_id' => 44,
                    ])
                    ->andWhere(['IN', 'event_journal.event_id', [22409, 22411]])
                    ->andFilterWhere($filter_mine)
                    ->groupBy('month_count')
                    ->indexBy('month_count')
                    ->asArray()
                    ->all();
                for ($i = 1; $i <= 12; $i++) {
                    if (isset($total_events_count_lamp[$i])) {
                        $individual_sensors['total_events_count'][] = (int)$total_events_count_lamp[$i]['count_event_all'];
                    } else {
                        $individual_sensors['total_events_count'][] = 0;
                    }
                }
                unset($total_events_count_lamp);

                // получаем количество событий с превышением ПДК по индивидуальным средствам из таблицы event_journal
                $event_count_with_excess = EventJournal::find()
                    ->select([
                        'COUNT(event_journal.id) as count_danger_all',
                        'MONTH(event_journal.date_time) as month_count'
                    ])
                    ->where("YEAR(event_journal.date_time)='" . $year . "'")
                    ->andWhere([
                        'event_journal.object_id' => 47,
                        'event_journal.status_id' => 44,
                    ])
                    ->andWhere(['IN', 'event_journal.event_id', [22409, 7130]])
                    ->andFilterWhere($filter_mine)
                    ->groupBy('month_count')
                    ->indexBy('month_count')
                    ->asArray()
                    ->all();
                for ($i = 1; $i <= 12; $i++) {
                    if (isset($event_count_with_excess[$i])) {
                        $individual_sensors['event_count_with_excess'][] = (int)$event_count_with_excess[$i]['count_danger_all'];
                    } else {
                        $individual_sensors['event_count_with_excess'][] = 0;
                    }
                }
                unset($event_count_with_excess);

                // получаем количество светильков подлежащий проверке по месяцам
                $total_sensors_count_for_check = EventJournal::find()
                    ->select([
                        'COUNT(event_journal.id) as count_need_check_all',
                        'MONTH(event_journal.date_time) as month_count'
                    ])
                    ->leftJoin('view_event_journal_status', '
                view_event_journal_status.event_journal_id=event_journal.id')
                    ->where("YEAR(event_journal.date_time)='" . $year . "'")
                    ->andWhere([
                        'event_journal.object_id' => 47,
                        'event_journal.status_id' => 44,
                    ])
                    ->andWhere(['or',
                        'view_event_journal_status.event_journal_id is null',
                        'view_event_journal_status.check_done_status!=1'
                    ])
//                    ->andWhere(['IN', 'event_journal.event_id', [22409, 7130, 22411]])
                    ->andWhere(['IN', 'event_journal.event_id', [22411]])
                    ->andFilterWhere($filter_mine)
                    ->groupBy('month_count')
                    ->indexBy('month_count')
                    ->asArray()
                    ->all();

                for ($i = 1; $i <= 12; $i++) {
                    if (isset($total_sensors_count_for_check[$i])) {
                        $individual_sensors['total_sensors_count_for_check'][] = (int)$total_sensors_count_for_check[$i]['count_need_check_all'];
                    } else {
                        $individual_sensors['total_sensors_count_for_check'][] = 0;
                    }
                }
                unset($total_sensors_count_for_check);

                // получаем количество светильков провереных по месяцам
                $checked_sensors_count = EventJournal::find()
                    ->select([
                        'COUNT(event_journal.id) as count_checked_all',
                        'MONTH(event_journal.date_time) as month_count'
                    ])
                    ->innerJoin('view_event_journal_status', '
                view_event_journal_status.event_journal_id=event_journal.id')
                    ->where("YEAR(event_journal.date_time)='" . $year . "'")
                    ->andWhere([
                        'event_journal.object_id' => 47,
                        'event_journal.status_id' => 44,
                    ])
                    ->andWhere('view_event_journal_status.check_done_status=1')
//                    ->andWhere(['IN', 'event_journal.event_id', [22409, 7130, 22411]])
                    ->andWhere(['IN', 'event_journal.event_id', [22411]])
                    ->andFilterWhere($filter_mine)
                    ->groupBy('month_count')
                    ->indexBy('month_count')
                    ->asArray()
                    ->all();

                for ($i = 1; $i <= 12; $i++) {
                    if (isset($checked_sensors_count[$i])) {
                        $individual_sensors['checked_sensors_count'][] = (int)$checked_sensors_count[$i]['count_checked_all'];
                    } else {
                        $individual_sensors['checked_sensors_count'][] = 0;
                    }
                }
                unset($checked_sensors_count);

                // получаем количество датчиков с неизвестной причиной события или отказа по месяцам
                $broken_sensors_count = EventJournal::find()
                    ->select([
                        'COUNT(event_journal.id) as count_without_reason_all',
                        'MONTH(event_journal.date_time) as month_count'
                    ])
                    ->leftJoin('view_event_journal_status', '
                view_event_journal_status.event_journal_id=event_journal.id')
                    ->where("YEAR(event_journal.date_time)='" . $year . "'")
                    ->andWhere([
                        'event_journal.object_id' => 47,
                        'event_journal.status_id' => 44,
                    ])
                    ->andWhere(['or',
                        'view_event_journal_status.event_journal_id is null',
                        'view_event_journal_status.kind_reason_id is null',
                        'view_event_journal_status.kind_reason_id = 5'
                    ])
//                    ->andWhere(['IN', 'event_journal.event_id', [22409, 7130, 22411]])
                    ->andWhere(['IN', 'event_journal.event_id', [22411]])
                    ->andFilterWhere($filter_mine)
                    ->groupBy('month_count')
                    ->indexBy('month_count')
                    ->asArray()
                    ->all();

                for ($i = 1; $i <= 12; $i++) {
                    if (isset($broken_sensors_count[$i])) {
                        $individual_sensors['broken_sensors_count'][] = (int)$broken_sensors_count[$i]['count_without_reason_all'];
                    } else {
                        $individual_sensors['broken_sensors_count'][] = 0;
                    }
                }
                unset($broken_sensors_count);

                // получаем общее количество событий по индивидуальным средствам из таблицы event_journal
                // 22409 - Превышение CH4 со светильника
                // 7130  - Превышение концентрации газа
                // 22411 - Датчик требует проверки на поверочной смеси
                $total_events_count_lamp = EventJournal::find()
                    ->select([
                        'COUNT(event_journal.id) as count_event_all',
                        'MONTH(event_journal.date_time) as month_count'
                    ])
                    ->where("YEAR(event_journal.date_time)='" . $year . "'")
                    ->andWhere([
                        'event_journal.object_id' => 28,
                        'event_journal.status_id' => 44,
                    ])
                    ->andWhere(['IN', 'event_journal.event_id', [7130, 22411]])
                    ->andFilterWhere($filter_mine)
                    ->groupBy('month_count')
                    ->indexBy('month_count')
                    ->asArray()
                    ->all();
                for ($i = 1; $i <= 12; $i++) {
                    if (isset($total_events_count_lamp[$i])) {
                        $stationary_sensors['total_events_count'][] = (int)$total_events_count_lamp[$i]['count_event_all'];
                    } else {
                        $stationary_sensors['total_events_count'][] = 0;
                    }
                }
                unset($total_events_count_lamp);

                // получаем количество событий с превышением ПДК по индивидуальным средствам из таблицы event_journal
                $event_count_with_excess = EventJournal::find()
                    ->select([
                        'COUNT(event_journal.id) as count_danger_all',
                        'MONTH(event_journal.date_time) as month_count'
                    ])
                    ->where("YEAR(event_journal.date_time)='" . $year . "'")
                    ->andWhere([
                        'event_journal.object_id' => 28,
                        'event_journal.status_id' => 44,
                    ])
                    ->andWhere(['IN', 'event_journal.event_id', [22409, 7130]])
                    ->andFilterWhere($filter_mine)
                    ->groupBy('month_count')
                    ->indexBy('month_count')
                    ->asArray()
                    ->all();
                for ($i = 1; $i <= 12; $i++) {
                    if (isset($event_count_with_excess[$i])) {
                        $stationary_sensors['event_count_with_excess'][] = (int)$event_count_with_excess[$i]['count_danger_all'];
                    } else {
                        $stationary_sensors['event_count_with_excess'][] = 0;
                    }
                }
                unset($event_count_with_excess);

                // получаем количество датчиков подлежащий проверке по месяцам
                $total_sensors_count_for_check = EventJournal::find()
                    ->select([
                        'COUNT(event_journal.id) as count_need_check_all',
                        'MONTH(event_journal.date_time) as month_count'
                    ])
                    ->leftJoin('view_event_journal_status', '
                view_event_journal_status.event_journal_id=event_journal.id')
                    ->where("YEAR(event_journal.date_time)='" . $year . "'")
                    ->andWhere([
                        'event_journal.object_id' => 28,
                        'event_journal.status_id' => 44,
                    ])
                    ->andWhere(['or',
                        'view_event_journal_status.event_journal_id is null',
                        'view_event_journal_status.check_done_status!=1'
                    ])
//                    ->andWhere(['IN', 'event_journal.event_id', [22409, 7130, 22411]])
                    ->andWhere(['IN', 'event_journal.event_id', [22411]])
                    ->andFilterWhere($filter_mine)
                    ->groupBy('month_count')
                    ->indexBy('month_count')
                    ->asArray()
                    ->all();

                for ($i = 1; $i <= 12; $i++) {
                    if (isset($total_sensors_count_for_check[$i])) {
                        $stationary_sensors['total_sensors_count_for_check'][] = (int)$total_sensors_count_for_check[$i]['count_need_check_all'];
                    } else {
                        $stationary_sensors['total_sensors_count_for_check'][] = 0;
                    }
                }
                unset($total_sensors_count_for_check);

                // получаем количество датчиков провереных по месяцам
                $checked_sensors_count = EventJournal::find()
                    ->select([
                        'COUNT(event_journal.id) as count_checked_all',
                        'MONTH(event_journal.date_time) as month_count'
                    ])
                    ->innerJoin('view_event_journal_status', '
                view_event_journal_status.event_journal_id=event_journal.id')
                    ->where("YEAR(event_journal.date_time)='" . $year . "'")
                    ->andWhere([
                        'event_journal.object_id' => 28,
                        'event_journal.status_id' => 44,
                    ])
                    ->andWhere('view_event_journal_status.check_done_status=1')
//                    ->andWhere(['IN', 'event_journal.event_id', [22409, 7130, 22411]])
                    ->andWhere(['IN', 'event_journal.event_id', [22411]])
                    ->andFilterWhere($filter_mine)
                    ->groupBy('month_count')
                    ->indexBy('month_count')
                    ->asArray()
                    ->all();

                for ($i = 1; $i <= 12; $i++) {
                    if (isset($checked_sensors_count[$i])) {
                        $stationary_sensors['checked_sensors_count'][] = (int)$checked_sensors_count[$i]['count_checked_all'];
                    } else {
                        $stationary_sensors['checked_sensors_count'][] = 0;
                    }
                }
                unset($checked_sensors_count);

                // получаем количество датчиков с неизвестной причиной события или отказа по месяцам
                $broken_sensors_count = EventJournal::find()
                    ->select([
                        'COUNT(event_journal.id) as count_without_reason_all',
                        'MONTH(event_journal.date_time) as month_count'
                    ])
                    ->leftJoin('view_event_journal_status', '
                view_event_journal_status.event_journal_id=event_journal.id')
                    ->where("YEAR(event_journal.date_time)='" . $year . "'")
                    ->andWhere([
                        'event_journal.object_id' => 28,
                        'event_journal.status_id' => 44,
                    ])
                    ->andWhere(['or',
                        'view_event_journal_status.event_journal_id is null',
                        'view_event_journal_status.kind_reason_id is null',
                        'view_event_journal_status.kind_reason_id = 5'
                    ])
//                    ->andWhere(['IN', 'event_journal.event_id', [22409, 7130, 22411]])
                    ->andWhere(['IN', 'event_journal.event_id', [22411]])
                    ->andFilterWhere($filter_mine)
                    ->groupBy('month_count')
                    ->indexBy('month_count')
                    ->asArray()
                    ->all();

                for ($i = 1; $i <= 12; $i++) {
                    if (isset($broken_sensors_count[$i])) {
                        $stationary_sensors['broken_sensors_count'][] = (int)$broken_sensors_count[$i]['count_without_reason_all'];
                    } else {
                        $stationary_sensors['broken_sensors_count'][] = 0;
                    }
                }
                unset($broken_sensors_count);
            } else if ($period == 'weekly') { // СТАТИСТИКА ПО НЕДЕЛЬНО!!!!!!!!!!!!!!!!!!!!
                $week_count = BackendAssistant::getCountWeek($year) + 1;
                // получаем общее количество событий по индивидуальным средствам из таблицы event_journal
                $total_events_count_lamp = EventJournal::find()
                    ->select([
                        'COUNT(event_journal.id) as count_event_all',
                        'WEEK(event_journal.date_time, 3)  as week_count'
                    ])
                    ->where("YEAR(event_journal.date_time)='" . $year . "'")
                    ->andWhere([
                        'event_journal.object_id' => 47,
                        'event_journal.status_id' => 44,
                    ])
                    ->andWhere(['IN', 'event_journal.event_id', [22409, 22411]])
                    ->andFilterWhere($filter_mine)
                    ->groupBy('week_count')
                    ->indexBy('week_count')
                    ->asArray()
                    ->all();
                for ($i = 1; $i <= $week_count; $i++) {
                    if (isset($total_events_count_lamp[$i])) {
                        $individual_sensors['total_events_count'][] = (int)$total_events_count_lamp[$i]['count_event_all'];
                    } else {
                        $individual_sensors['total_events_count'][] = 0;
                    }
                }
                unset($total_events_count_lamp);

                // получаем количество событий с превышением ПДК по индивидуальным средствам из таблицы event_journal
                $event_count_with_excess = EventJournal::find()
                    ->select([
                        'COUNT(event_journal.id) as count_danger_all',
                        'WEEK(event_journal.date_time,3)  as week_count'
                    ])
                    ->where("YEAR(event_journal.date_time)='" . $year . "'")
                    ->andWhere([
                        'event_journal.object_id' => 47,
                        'event_journal.status_id' => 44,
                    ])
                    ->andWhere(['IN', 'event_journal.event_id', [22409, 7130]])
                    ->andFilterWhere($filter_mine)
                    ->groupBy('week_count')
                    ->indexBy('week_count')
                    ->asArray()
                    ->all();
                for ($i = 1; $i <= $week_count; $i++) {
                    if (isset($event_count_with_excess[$i])) {
                        $individual_sensors['event_count_with_excess'][] = (int)$event_count_with_excess[$i]['count_danger_all'];
                    } else {
                        $individual_sensors['event_count_with_excess'][] = 0;
                    }
                }
                unset($event_count_with_excess);

                // получаем количество светильников подлежащий проверке по неделям
                $total_sensors_count_for_check = EventJournal::find()
                    ->select([
                        'COUNT(event_journal.id) as count_need_check_all',
                        'WEEK(event_journal.date_time,3)  as week_count'
                    ])
                    ->leftJoin('view_event_journal_status', '
                view_event_journal_status.event_journal_id=event_journal.id')
                    ->where("YEAR(event_journal.date_time)='" . $year . "'")
                    ->andWhere([
                        'event_journal.object_id' => 47,
                        'event_journal.status_id' => 44,
                    ])
                    ->andWhere(['or',
                        'view_event_journal_status.event_journal_id is null',
                        'view_event_journal_status.check_done_status!=1'
                    ])
//                    ->andWhere(['IN', 'event_journal.event_id', [22409, 7130, 22411]])
                    ->andWhere(['IN', 'event_journal.event_id', [22411]])
                    ->andFilterWhere($filter_mine)
                    ->groupBy('week_count')
                    ->indexBy('week_count')
                    ->asArray()
                    ->all();

                for ($i = 1; $i <= $week_count; $i++) {
                    if (isset($total_sensors_count_for_check[$i])) {
                        $individual_sensors['total_sensors_count_for_check'][] = (int)$total_sensors_count_for_check[$i]['count_need_check_all'];
                    } else {
                        $individual_sensors['total_sensors_count_for_check'][] = 0;
                    }
                }
                unset($total_sensors_count_for_check);

                // получаем количество светильников провереных по неделям
                $checked_sensors_count = EventJournal::find()
                    ->select([
                        'COUNT(event_journal.id) as count_checked_all',
                        'WEEK(event_journal.date_time,3) as week_count'
                    ])
                    ->innerJoin('view_event_journal_status', '
                view_event_journal_status.event_journal_id=event_journal.id')
                    ->where("YEAR(event_journal.date_time)='" . $year . "'")
                    ->andWhere([
                        'event_journal.object_id' => 47,
                        'event_journal.status_id' => 44,
                    ])
                    ->andWhere('view_event_journal_status.check_done_status=1')
//                    ->andWhere(['IN', 'event_journal.event_id', [22409, 7130, 22411]])
                    ->andWhere(['IN', 'event_journal.event_id', [22411]])
                    ->andFilterWhere($filter_mine)
                    ->groupBy('week_count')
                    ->indexBy('week_count')
                    ->asArray()
                    ->all();

                for ($i = 1; $i <= $week_count; $i++) {
                    if (isset($checked_sensors_count[$i])) {
                        $individual_sensors['checked_sensors_count'][] = (int)$checked_sensors_count[$i]['count_checked_all'];
                    } else {
                        $individual_sensors['checked_sensors_count'][] = 0;
                    }
                }
                unset($checked_sensors_count);

                // получаем количество светильников с неизвестной причиной события или отказа по неделям
                $broken_sensors_count = EventJournal::find()
                    ->select([
                        'COUNT(event_journal.id) as count_without_reason_all',
                        'WEEK(event_journal.date_time,3)  as week_count'
                    ])
                    ->leftJoin('view_event_journal_status', '
                view_event_journal_status.event_journal_id=event_journal.id')
                    ->where("YEAR(event_journal.date_time)='" . $year . "'")
                    ->andWhere([
                        'event_journal.object_id' => 47,
                        'event_journal.status_id' => 44,
                    ])
                    ->andWhere(['or',
                        'view_event_journal_status.event_journal_id is null',
                        'view_event_journal_status.kind_reason_id is null',
                        'view_event_journal_status.kind_reason_id = 5'
                    ])
//                    ->andWhere(['IN', 'event_journal.event_id', [22409, 7130, 22411]])
                    ->andWhere(['IN', 'event_journal.event_id', [22411]])
                    ->andFilterWhere($filter_mine)
                    ->groupBy('week_count')
                    ->indexBy('week_count')
                    ->asArray()
                    ->all();

                for ($i = 1; $i <= $week_count; $i++) {
                    if (isset($broken_sensors_count[$i])) {
                        $individual_sensors['broken_sensors_count'][] = (int)$broken_sensors_count[$i]['count_without_reason_all'];
                    } else {
                        $individual_sensors['broken_sensors_count'][] = 0;
                    }
                }
                unset($broken_sensors_count);

                // получаем общее количество событий по индивидуальным средствам из таблицы event_journal
                $total_events_count_lamp = EventJournal::find()
                    ->select([
                        'COUNT(event_journal.id) as count_event_all',
                        'WEEK(event_journal.date_time,3) as week_count'
                    ])
                    ->where("YEAR(event_journal.date_time)='" . $year . "'")
                    ->andWhere([
                        'event_journal.object_id' => 28,
                        'event_journal.status_id' => 44,
                    ])
                    ->andWhere(['IN', 'event_journal.event_id', [7130, 22411]])
                    ->andFilterWhere($filter_mine)
                    ->groupBy('week_count')
                    ->indexBy('week_count')
                    ->asArray()
                    ->all();
                for ($i = 1; $i <= $week_count; $i++) {
                    if (isset($total_events_count_lamp[$i])) {
                        $stationary_sensors['total_events_count'][] = (int)$total_events_count_lamp[$i]['count_event_all'];
                    } else {
                        $stationary_sensors['total_events_count'][] = 0;
                    }
                }
                unset($total_events_count_lamp);

                // получаем количество событий с превышением ПДК по индивидуальным средствам из таблицы event_journal
                $event_count_with_excess = EventJournal::find()
                    ->select([
                        'COUNT(event_journal.id) as count_danger_all',
                        'WEEK(event_journal.date_time,3) as week_count'
                    ])
                    ->where("YEAR(event_journal.date_time)='" . $year . "'")
                    ->andWhere([
                        'event_journal.object_id' => 28,
                        'event_journal.status_id' => 44,
                    ])
                    ->andWhere(['IN', 'event_journal.event_id', [22409, 7130]])
                    ->andFilterWhere($filter_mine)
                    ->groupBy('week_count')
                    ->indexBy('week_count')
                    ->asArray()
                    ->all();
                for ($i = 1; $i <= $week_count; $i++) {
                    if (isset($event_count_with_excess[$i])) {
                        $stationary_sensors['event_count_with_excess'][] = (int)$event_count_with_excess[$i]['count_danger_all'];
                    } else {
                        $stationary_sensors['event_count_with_excess'][] = 0;
                    }
                }
                unset($event_count_with_excess);

                // получаем количество датчиков подлежащий проверке по неделям
                $total_sensors_count_for_check = EventJournal::find()
                    ->select([
                        'COUNT(event_journal.id) as count_need_check_all',
                        'WEEK(event_journal.date_time,3) as week_count'
                    ])
                    ->leftJoin('view_event_journal_status', '
                view_event_journal_status.event_journal_id=event_journal.id')
                    ->where("YEAR(event_journal.date_time)='" . $year . "'")
                    ->andWhere([
                        'event_journal.object_id' => 28,
                        'event_journal.status_id' => 44,
                    ])
                    ->andWhere(['or',
                        'view_event_journal_status.event_journal_id is null',
                        'view_event_journal_status.check_done_status!=1'
                    ])
//                    ->andWhere(['IN', 'event_journal.event_id', [22409, 7130, 22411]])
                    ->andWhere(['IN', 'event_journal.event_id', [22411]])
                    ->andFilterWhere($filter_mine)
                    ->groupBy('week_count')
                    ->indexBy('week_count')
                    ->asArray()
                    ->all();

                for ($i = 1; $i <= $week_count; $i++) {
                    if (isset($total_sensors_count_for_check[$i])) {
                        $stationary_sensors['total_sensors_count_for_check'][] = (int)$total_sensors_count_for_check[$i]['count_need_check_all'];
                    } else {
                        $stationary_sensors['total_sensors_count_for_check'][] = 0;
                    }
                }
                unset($total_sensors_count_for_check);

                // получаем количество датчиков провереных по неделям
                $checked_sensors_count = EventJournal::find()
                    ->select([
                        'COUNT(event_journal.id) as count_checked_all',
                        'WEEK(event_journal.date_time,3) as week_count'
                    ])
                    ->innerJoin('view_event_journal_status', '
                view_event_journal_status.event_journal_id=event_journal.id')
                    ->where("YEAR(event_journal.date_time)='" . $year . "'")
                    ->andWhere([
                        'event_journal.object_id' => 28,
                        'event_journal.status_id' => 44,
                    ])
                    ->andWhere('view_event_journal_status.check_done_status=1')
//                    ->andWhere(['IN', 'event_journal.event_id', [22409, 7130, 22411]])
                    ->andWhere(['IN', 'event_journal.event_id', [22411]])
                    ->andFilterWhere($filter_mine)
                    ->groupBy('week_count')
                    ->indexBy('week_count')
                    ->asArray()
                    ->all();

                for ($i = 1; $i <= $week_count; $i++) {
                    if (isset($checked_sensors_count[$i])) {
                        $stationary_sensors['checked_sensors_count'][] = (int)$checked_sensors_count[$i]['count_checked_all'];
                    } else {
                        $stationary_sensors['checked_sensors_count'][] = 0;
                    }
                }
                unset($checked_sensors_count);

                // получаем количество датчиков с неизвестной причиной события или отказа по неделям
                $broken_sensors_count = EventJournal::find()
                    ->select([
                        'COUNT(event_journal.id) as count_without_reason_all',
                        'WEEK(event_journal.date_time,3) as week_count'
                    ])
                    ->leftJoin('view_event_journal_status', '
                view_event_journal_status.event_journal_id=event_journal.id')
                    ->where("YEAR(event_journal.date_time)='" . $year . "'")
                    ->andWhere([
                        'event_journal.object_id' => 28,
                        'event_journal.status_id' => 44,
                    ])
                    ->andWhere(['or',
                        'view_event_journal_status.event_journal_id is null',
                        'view_event_journal_status.kind_reason_id is null',
                        'view_event_journal_status.kind_reason_id = 5'
                    ])
//                    ->andWhere(['IN', 'event_journal.event_id', [22409, 7130, 22411]])
                    ->andWhere(['IN', 'event_journal.event_id', [22411]])
                    ->andFilterWhere($filter_mine)
                    ->groupBy('week_count')
                    ->indexBy('week_count')
                    ->asArray()
                    ->all();

                for ($i = 1; $i <= $week_count; $i++) {
                    if (isset($broken_sensors_count[$i])) {
                        $stationary_sensors['broken_sensors_count'][] = (int)$broken_sensors_count[$i]['count_without_reason_all'];
                    } else {
                        $stationary_sensors['broken_sensors_count'][] = 0;
                    }
                }
                unset($broken_sensors_count);
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetStatisticGas. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetStatisticGas. Конец метода';

        if (empty($individual_sensors)) {
            $individual_sensors = (object)array();
        }

        if (empty($stationary_sensors)) {
            $stationary_sensors = (object)array();
        }

        $result = array('individual_sensors' => $individual_sensors, 'stationary_sensors' => $stationary_sensors);

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetStatisticSituation - получение статистики по ситуациям
    // метод ждет:
    //      year: 2019,
    // выходной массив:
    //      danger_level_full                       - максимальный ровень риска при котором случается тяжелый случай
    //      count_situations_all                    - общее количество ситуаций за год
    //      place_situations_count                  - колиество ситуаций по местам по году
    //              [0]
    //                  place_id                - ключ места
    //                  place_title             - наименование места
    //                  count_situation         - количество ситуаций по местам
    //                  percantage              - процент от общего количества
    //      kind_reason_situations_count            - количество ситуаций по причинам
    //              [0]
    //                  kind_reason_id          - ключ причины ситуации
    //                  kind_reason_title       - наименование причины ситуации
    //                  count_situation         - количество ситуаций по причинам
    //                  percantage              - процент от общего количества
    //      monthly:
    //          total_situations_count              -   количество ситуаций по месяцам
    //                  [0,0,0,0,0,0,0,0,0,0,0,0]           - на каждый месяц количество
    //          danger_level_current                -   количество ситуаций по месяцам
    //                  [0,0,0,0,0,0,0,0,0,0,0,0]           - на каждый месяц уровень риска
    //      weekly:
    //          total_situations_count              -   количество ситуаций по неделям
    //                  [0,0,0,0,0,0,0,0,0,0,...]           - на каждую неделю количество
    //          danger_level_current                -   количество ситуаций по месяцам
    //                  [0,0,0,0,0,0,0,0,0,0,...]           - на каждый месяц по количеству
    // http://127.0.0.1/read-manager-amicum?controller=EventCompareGas&method=GetStatisticSituation&subscribe=&data={%22year%22:%222020%22}
    public static function GetStatisticSituation($data_post = NULL)
    {
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("GetJournalSituationByYear");
        $result = (object)array();
        try {
            $log->addLog("Начало выполнения метода");
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('Данные с фронта не получены');
            }
            $log->addLog("Данные успешно переданы");
            $log->addLog("Входной массив данных" . $data_post);

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $log->addLog("Декодировал входные параметры");

            if (
                !property_exists($post_dec, 'year')
            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $log->addLog("Данные с фронта получены и они правильные");

            $year = $post_dec->year;

            $filter_mine = array();

            if (property_exists($post_dec, 'mine_id') and $post_dec->mine_id != -1) {
                $filter_mine = ['situation_journal.mine_id' => $post_dec->mine_id];
            }

            // КОНСТАНТА _ ОСНОВАНИЕ ПИРАМИДЫ _ МАКСИМАЛЬНЫЙ УРОВЕНЬ РИСКА В КОЛИЧЕСТВО СОБЫТИЙ
            $situation_result['danger_level_full'] = 10000;

            // получаем общее количество ситуаций по году
            $count_situations_all = (int)SituationJournal::find()
                ->select([
                    'COUNT(situation_journal.id) as count_situation_all'
                ])
                ->where("YEAR(situation_journal.date_time)='" . $year . "'")
                ->andFilterWhere($filter_mine)
                ->scalar();

            $situation_result['count_situations_all'] = $count_situations_all;

            $log->addLog("получил общее количество ситуаций по году");

            // получаем количество ситуаций по местам
            $situation_result['place_situations_count'] = array();
            $place_situations_count = SituationJournal::find()
                ->select([
                    'situation_journal.id as situation_journal_id',
                    'place.id as place_id',
                    'place.title as place_title',

                ])
                ->innerJoin('situation_journal_zone', 'situation_journal_zone.situation_journal_id=situation_journal.id')
                ->innerJoin('edge', 'situation_journal_zone.edge_id=edge.id')
                ->innerJoin('place', 'edge.place_id=place.id')
                ->where("YEAR(situation_journal.date_time)='" . $year . "'")
                ->andFilterWhere($filter_mine)
                ->groupBy('place_id, place_title, situation_journal_id')
                ->asArray()
                ->all();
            $count_place = array();
            foreach ($place_situations_count as $place) {
                $count_record++;
                if (!isset($count_place[$place['place_id']])) {
                    $count_place[$place['place_id']]['place_id'] = $place['place_id'];
                    $count_place[$place['place_id']]['place_title'] = $place['place_title'];
                    $count_place[$place['place_id']]['count_situation'] = 0;
                    $count_place[$place['place_id']]['percantage'] = 0;
                }
                $count_place[$place['place_id']]['count_situation']++;
                $count_place[$place['place_id']]['percantage'] = round(($count_place[$place['place_id']]['count_situation'] / $count_situations_all) * 100, 1);
            }
            unset($place_situations_count);
            unset($place);

            foreach ($count_place as $place) {
                $situation_result['place_situations_count'][] = $place;
            }
            unset($place);
            unset($count_place);

            $log->addLog("получил количество ситуаций по местам", $count_record);
            $count_record = 0;
            // получаем статистику причин ситуаций за год
            $situation_result['kind_reason_situations_count'] = array();
            $kind_reason_situations_count = (new Query())
                ->select([
                    'count(situation_journal_id) as count_situation',
                    'ifnull(kind_reason.id,"0") as kind_reason_id',
                    'ifnull(kind_reason.title, "Причина не выяснялась") as kind_reason_title',

                ])
                ->from('view_situation_kind_reason_last')
                ->innerJoin('situation_journal', 'situation_journal.id=view_situation_kind_reason_last.situation_journal_id')
                ->leftJoin('kind_reason', 'view_situation_kind_reason_last.kind_reason_id=kind_reason.id')
                ->where("YEAR(view_situation_kind_reason_last.date_time)='" . $year . "'")
                ->andFilterWhere($filter_mine)
                ->groupBy('kind_reason_id, kind_reason_title')
                ->all();
            if ($kind_reason_situations_count) {
                foreach ($kind_reason_situations_count as $index => $kind_reason_item) {
                    $count_record++;
                    $kind_reason_situations_count[$index]['percantage'] = round(($kind_reason_item['count_situation'] / $count_situations_all) * 100, 1);
                }
                $situation_result['kind_reason_situations_count'] = $kind_reason_situations_count;
            }
            unset($kind_reason_situations_count);

            $log->addLog("получил статистику причин ситуаций за год");

            // получаем общее количество ситуаций по месяцам
            $total_situations_count = SituationJournal::find()
                ->select([
                    'COUNT(situation_journal.id) as count_situation_all',
                    'MONTH(situation_journal.date_time) as month_count'
                ])
                ->where("YEAR(situation_journal.date_time)='" . $year . "'")
                ->andFilterWhere($filter_mine)
                ->groupBy('month_count')
                ->indexBy('month_count')
                ->asArray()
                ->all();
            for ($i = 1; $i <= 12; $i++) {
                if (isset($total_situations_count[$i])) {
                    $situation_result['monthly']['total_situations_count'][] = (int)$total_situations_count[$i]['count_situation_all'];
                    $situation_result['monthly']['danger_level_current'][] = round(($total_situations_count[$i]['count_situation_all'] / $situation_result['danger_level_full']) * 100, 1);
                } else {
                    $situation_result['monthly']['total_situations_count'][] = 0;
                    $situation_result['monthly']['danger_level_current'][] = 0;
                }
            }
            unset($total_situations_count);

            $log->addLog("получил общее количество ситуаций по месецам");

//            $situation_result['monthly']['danger_level_current'][2] = 1500;
            // СТАТИСТИКА ПО НЕДЕЛЬНО!!!!!!!!!!!!!!!!!!!!
            $week_count = BackendAssistant::getCountWeek($year) + 1;
            // получаем общее количество событий по индивидуальным средствам из таблицы event_journal
            // получаем общее количество ситуаций по месецам
            $total_situations_count = SituationJournal::find()
                ->select([
                    'COUNT(situation_journal.id) as count_situation_all',
                    'WEEK(situation_journal.date_time,3) as week_count'
                ])
                ->where("YEAR(situation_journal.date_time)='" . $year . "'")
                ->andFilterWhere($filter_mine)
                ->groupBy('week_count')
                ->indexBy('week_count')
                ->asArray()
                ->all();

            for ($i = 1; $i <= $week_count; $i++) {
                if (isset($total_situations_count[$i])) {
                    $situation_result['weekly']['total_situations_count'][] = (int)$total_situations_count[$i]['count_situation_all'];
                    $situation_result['weekly']['danger_level_current'][] = round(($total_situations_count[$i]['count_situation_all'] / ($situation_result['danger_level_full'] / 4)) * 100, 1);
                } else {
                    $situation_result['weekly']['total_situations_count'][] = 0;
                    $situation_result['weekly']['danger_level_current'][] = 0;
                }
            }
            unset($total_situations_count);

            $log->addLog("получил ообщее количество событий по индивидуальным средствам из таблицы event_journal");

            $result = $situation_result;

            $log->addLog("Окончание выполнения метода", $count_record);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    // GetStatisticLamp - получение статистики по расхождению показаний по лампам
    // метод ждет:
    //      year: 2019,
    // выходной массив:
    //      count_event_all                    - общее количество расхождений за год
    //      place_event_count                  - колиество расхождений по местам по году
    //              [0]
    //                  place_id                    - ключ места
    //                  place_title                 - наименование места
    //                  count_event                 - количество расхождений по местам
    //                  percantage                  - процент от общего количества
    //      kind_reason_event_count            - количество расхождений по причинам
    //              [0]
    //                  kind_reason_id              - ключ причины события
    //                  kind_reason_title           - наименование причины события
    //                  count_event                 - количество расхождений по причинам
    //                  percantage                  - процент от общего количества
    //      monthly:
    //          total_event_count              -   количество расхождений по месяцам
    //                  [0,0,0,0,0,0,0,0,0,0,0,0]           - на каждый месяц количество
    //      weekly:
    //          total_event_count              -   количество событий расхождений по неделям
    //                  [0,0,0,0,0,0,0,0,0,0,...]           - на каждую неделю количество
    // http://127.0.0.1/read-manager-amicum?controller=EventCompareGas&method=GetStatisticLamp&subscribe=&data={%22year%22:%222020%22}
    public static function GetStatisticLamp($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // Массив ошибок
        $warnings[] = 'GetStatisticLamp. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetStatisticLamp. Данные с фронта не получены');
            }
            $warnings[] = 'GetStatisticLamp. Данные успешно переданы';
            $warnings[] = 'GetStatisticLamp. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'GetStatisticLamp. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'year')
            ) {
                throw new Exception('GetStatisticLamp. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'GetStatisticLamp. Данные с фронта получены и они правильные';
            $year = $post_dec->year;
            $filter_mine = array();
            if (property_exists($post_dec, 'mine_id') and $post_dec->mine_id != -1) {
                $filter_mine = ['event_journal.mine_id' => $post_dec->mine_id];
            }

            // получаем общее количество ситуаций по году
            $count_event_all = (int)EventJournal::find()
                ->select([
                    'COUNT(event_journal.id) as count_event_all'
                ])
                ->where("YEAR(event_journal.date_time)='" . $year . "'")
                ->andWhere(['event_journal.event_id' => 22411, 'event_journal.object_id' => 47])
                ->andFilterWhere($filter_mine)
                ->scalar();

            $event_result['count_event_all'] = $count_event_all;

            // получаем количество ситуаций по местам
            $event_result['place_event_count'] = array();
            $place_event_count = EventJournal::find()
                ->select([
                    'event_journal.id as event_journal_id',
                    'place.id as place_id',
                    'place.title as place_title',

                ])
                ->innerJoin('edge', 'event_journal.edge_id=edge.id')
                ->innerJoin('place', 'edge.place_id=place.id')
                ->where("YEAR(event_journal.date_time)='" . $year . "'")
                ->andWhere(['event_journal.event_id' => 22411, 'event_journal.object_id' => 47])
                ->andFilterWhere($filter_mine)
                ->groupBy('place_id, place_title, event_journal_id')
                ->asArray()
                ->all();
            $count_place = array();
            foreach ($place_event_count as $place) {
                if (!isset($count_place[$place['place_id']])) {
                    $count_place[$place['place_id']]['place_id'] = $place['place_id'];
                    $count_place[$place['place_id']]['place_title'] = $place['place_title'];
                    $count_place[$place['place_id']]['count_event'] = 0;
                    $count_place[$place['place_id']]['percantage'] = 0;
                }
                $count_place[$place['place_id']]['count_event']++;
                $count_place[$place['place_id']]['percantage'] = round(($count_place[$place['place_id']]['count_event'] / $count_event_all) * 100, 1);
            }
            unset($place_event_count);
            unset($place);

            foreach ($count_place as $place) {
                $event_result['place_event_count'][] = $place;
            }
            unset($place);
            unset($count_place);

            // получаем статистику причин ситуаций за год
            $event_result['kind_reason_event_count'] = array();
            $kind_reason_event_count = (new Query())
                ->select([
                    'count(event_journal_id) as count_event',
                    'ifnull(kind_reason.id,"0") as kind_reason_id',
                    'ifnull(kind_reason.title, "Причина не выяснялась") as kind_reason_title',

                ])
                ->from('view_event_journal_kind_reason_last')
                ->innerJoin('event_journal', 'event_journal.id=view_event_journal_kind_reason_last.event_journal_id')
                ->leftJoin('kind_reason', 'view_event_journal_kind_reason_last.kind_reason_id=kind_reason.id')
                ->where("YEAR(view_event_journal_kind_reason_last.date_time)='" . $year . "'")
                ->andWhere(['view_event_journal_kind_reason_last.event_id' => 22411, 'view_event_journal_kind_reason_last.object_id' => 47])
                ->andFilterWhere($filter_mine)
                ->groupBy('kind_reason_id, kind_reason_title')
                ->all();
            if ($kind_reason_event_count) {
                foreach ($kind_reason_event_count as $index => $kind_reason_item) {
                    $kind_reason_event_count[$index]['percantage'] = round(($kind_reason_item['count_event'] / $count_event_all) * 100, 1);
                }
                $event_result['kind_reason_event_count'] = $kind_reason_event_count;
            }
            unset($kind_reason_event_count);


            // получаем общее количество событий по месецам
            $total_event_count = EventJournal::find()
                ->select([
                    'COUNT(event_journal.id) as count_event_all',
                    'MONTH(event_journal.date_time) as month_count'
                ])
                ->where("YEAR(event_journal.date_time)='" . $year . "'")
                ->andWhere(['event_journal.event_id' => 22411, 'event_journal.object_id' => 47])
                ->andFilterWhere($filter_mine)
                ->groupBy('month_count')
                ->indexBy('month_count')
                ->asArray()
                ->all();
            for ($i = 1; $i <= 12; $i++) {
                if (isset($total_event_count[$i])) {
                    $event_result['monthly']['total_event_count'][] = (int)$total_event_count[$i]['count_event_all'];
                } else {
                    $event_result['monthly']['total_event_count'][] = 0;
                }
            }
            unset($total_event_count);

            // СТАТИСТИКА ПО НЕДЕЛЬНО!!!!!!!!!!!!!!!!!!!!
            $week_count = BackendAssistant::getCountWeek($year) + 1;
            // получаем общее количество событий по индивидуальным средствам из таблицы event_journal
            // получаем общее количество событий по месецам
            $total_event_count = EventJournal::find()
                ->select([
                    'COUNT(event_journal.id) as count_event_all',
                    'WEEK(event_journal.date_time,3) as week_count'
                ])
                ->where("YEAR(event_journal.date_time)='" . $year . "'")
                ->andWhere(['event_journal.event_id' => 22411, 'event_journal.object_id' => 47])
                ->andFilterWhere($filter_mine)
                ->groupBy('week_count')
                ->indexBy('week_count')
                ->asArray()
                ->all();

            for ($i = 1; $i <= $week_count; $i++) {
                if (isset($total_event_count[$i])) {
                    $event_result['weekly']['total_event_count'][] = (int)$total_event_count[$i]['count_event_all'];
                } else {
                    $event_result['weekly']['total_event_count'][] = 0;
                }
            }
            unset($total_event_count);


            $result = $event_result;
        } catch (Throwable $exception) {
            $errors[] = 'GetStatisticLamp. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetStatisticLamp. Конец метода';

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetStatisticStatic - получение статистики по расхождению показаний по стационарным датчикам
    // метод ждет:
    //      year: 2019,
    // выходной массив:
    //      count_event_all                    - общее количество расхождений за год
    //      place_event_count                  - колиество расхождений по местам по году
    //              [0]
    //                  place_id                    - ключ места
    //                  place_title                 - наименование места
    //                  count_event                 - количество расхождений по местам
    //                  percantage                  - процент от общего количества
    //      kind_reason_event_count            - количество расхождений по причинам
    //              [0]
    //                  kind_reason_id              - ключ причины события
    //                  kind_reason_title           - наименование причины события
    //                  count_event                 - количество расхождений по причинам
    //                  percantage                  - процент от общего количества
    //      monthly:
    //          total_event_count              -   количество расхождений по месяцам
    //                  [0,0,0,0,0,0,0,0,0,0,0,0]           - на каждый месяц количество
    //      weekly:
    //          total_event_count              -   количество событий расхождений по неделям
    //                  [0,0,0,0,0,0,0,0,0,0,...]           - на каждую неделю количество
    // http://127.0.0.1/read-manager-amicum?controller=EventCompareGas&method=GetStatisticStatic&subscribe=&data={%22year%22:%222020%22}
    public static function GetStatisticStatic($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // Массив ошибок
        $warnings[] = 'GetStatisticStatic. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetStatisticStatic. Данные с фронта не получены');
            }
            $warnings[] = 'GetStatisticStatic. Данные успешно переданы';
            $warnings[] = 'GetStatisticStatic. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'GetStatisticStatic. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'year')
            ) {
                throw new Exception('GetStatisticStatic. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'GetStatisticStatic. Данные с фронта получены и они правильные';
            $year = $post_dec->year;

            $filter_mine = array();
            if (property_exists($post_dec, 'mine_id') and $post_dec->mine_id != -1) {
                $filter_mine = ['event_journal.mine_id' => $post_dec->mine_id];
            }

            // получаем общее количество ситуаций по году
            $count_event_all = (int)EventJournal::find()
                ->select([
                    'COUNT(event_journal.id) as count_event_all'
                ])
                ->where("YEAR(event_journal.date_time)='" . $year . "'")
                ->andWhere(['event_journal.event_id' => 22411, 'event_journal.object_id' => 28])
                ->andFilterWhere($filter_mine)
                ->scalar();

            $event_result['count_event_all'] = $count_event_all;

            // получаем количество ситуаций по местам
            $event_result['place_event_count'] = array();
            $place_event_count = EventJournal::find()
                ->select([
                    'event_journal.id as event_journal_id',
                    'place.id as place_id',
                    'place.title as place_title',

                ])
                ->innerJoin('edge', 'event_journal.edge_id=edge.id')
                ->innerJoin('place', 'edge.place_id=place.id')
                ->where("YEAR(event_journal.date_time)='" . $year . "'")
                ->andWhere(['event_journal.event_id' => 22411, 'event_journal.object_id' => 28])
                ->andFilterWhere($filter_mine)
                ->groupBy('place_id, place_title, event_journal_id')
                ->asArray()
                ->all();
            $count_place = array();
            foreach ($place_event_count as $place) {
                if (!isset($count_place[$place['place_id']])) {
                    $count_place[$place['place_id']]['place_id'] = $place['place_id'];
                    $count_place[$place['place_id']]['place_title'] = $place['place_title'];
                    $count_place[$place['place_id']]['count_event'] = 0;
                    $count_place[$place['place_id']]['percantage'] = 0;
                }
                $count_place[$place['place_id']]['count_event']++;
                $count_place[$place['place_id']]['percantage'] = round(($count_place[$place['place_id']]['count_event'] / $count_event_all) * 100, 1);
            }
            unset($place_event_count);
            unset($place);

            foreach ($count_place as $place) {
                $event_result['place_event_count'][] = $place;
            }
            unset($place);
            unset($count_place);

            // получаем статистику причин ситуаций за год
            $event_result['kind_reason_event_count'] = array();
            $kind_reason_event_count = (new Query())
                ->select([
                    'count(event_journal_id) as count_event',
                    'ifnull(kind_reason.id,"0") as kind_reason_id',
                    'ifnull(kind_reason.title, "Причина не выяснялась") as kind_reason_title',

                ])
                ->from('view_event_journal_kind_reason_last')
                ->innerJoin('event_journal', 'event_journal.id=view_event_journal_kind_reason_last.event_journal_id')
                ->leftJoin('kind_reason', 'view_event_journal_kind_reason_last.kind_reason_id=kind_reason.id')
                ->where("YEAR(view_event_journal_kind_reason_last.date_time)='" . $year . "'")
                ->andWhere(['view_event_journal_kind_reason_last.event_id' => 22411, 'view_event_journal_kind_reason_last.object_id' => 28])
                ->andFilterWhere($filter_mine)
                ->groupBy('kind_reason_id, kind_reason_title')
                ->all();
            if ($kind_reason_event_count) {
                foreach ($kind_reason_event_count as $index => $kind_reason_item) {
                    $kind_reason_event_count[$index]['percantage'] = round(($kind_reason_item['count_event'] / $count_event_all) * 100, 1);
                }
                $event_result['kind_reason_event_count'] = $kind_reason_event_count;
            }
            unset($kind_reason_event_count);


            // получаем общее количество событий по месецам
            $total_event_count = EventJournal::find()
                ->select([
                    'COUNT(event_journal.id) as count_event_all',
                    'MONTH(event_journal.date_time) as month_count'
                ])
                ->where("YEAR(event_journal.date_time)='" . $year . "'")
                ->andWhere(['event_journal.event_id' => 22411, 'event_journal.object_id' => 28])
                ->andFilterWhere($filter_mine)
                ->groupBy('month_count')
                ->indexBy('month_count')
                ->asArray()
                ->all();
            for ($i = 1; $i <= 12; $i++) {
                if (isset($total_event_count[$i])) {
                    $event_result['monthly']['total_event_count'][] = (int)$total_event_count[$i]['count_event_all'];
                } else {
                    $event_result['monthly']['total_event_count'][] = 0;
                }
            }
            unset($total_event_count);

            // СТАТИСТИКА ПО НЕДЕЛЬНО!!!!!!!!!!!!!!!!!!!!
            $week_count = BackendAssistant::getCountWeek($year) + 1;
            // получаем общее количество событий по индивидуальным средствам из таблицы event_journal
            // получаем общее количество событий по месецам
            $total_event_count = EventJournal::find()
                ->select([
                    'COUNT(event_journal.id) as count_event_all',
                    'WEEK(event_journal.date_time,3) as week_count'
                ])
                ->where("YEAR(event_journal.date_time)='" . $year . "'")
                ->andWhere(['event_journal.event_id' => 22411, 'event_journal.object_id' => 28])
                ->andFilterWhere($filter_mine)
                ->groupBy('week_count')
                ->indexBy('week_count')
                ->asArray()
                ->all();

            for ($i = 1; $i <= $week_count; $i++) {
                if (isset($total_event_count[$i])) {
                    $event_result['weekly']['total_event_count'][] = (int)$total_event_count[$i]['count_event_all'];
                } else {
                    $event_result['weekly']['total_event_count'][] = 0;
                }
            }
            unset($total_event_count);


            $result = $event_result;
        } catch (Throwable $exception) {
            $errors[] = 'GetStatisticStatic. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetStatisticStatic. Конец метода';

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetABJournal - метод получения журнала событий  оператора АГК
    // входные параметры:
    //      date_time_start - дата начала за которую хотим получить журнал
    //      date_time_end - дата окончания за которую хотим получить журнал
    // выходной объект:
    // {event_id}                                                   - ключ типа события
    //      event_id	"22409"                                     - ключ типа события
    //      event_title	"Превышение CH4 со светильника"             - название события
    //      status_checked	1                                       - статус снятия события (1 устранено, 0 нет)
    //      events:                                                             - список событий
    //          {event_journal_id}                                              - ключ журнала событий
    //              event_journal_id	    "1578570"                           - ключ журнала событий
    //              event_date_time	        "2019-09-19 09:24:46"               - дата начала события
    //              event_date_time_format	"19.09.2019 09:24:46"               - форматированная дата события
    //              sensor_id	            "26721"                             - ключ датчика
    //              sensor_title	        "Z-ЛУЧ-4 № Р 11-9  (Net ID 687953)" - название датчика
    //              edge_id	                "139138"                            - ключ ветви в которой произошло событие
    //              place_id	            "15009"                             - ключ места в котором произошло событие
    //              sensor_value	        "0.5"                               - значение параметра сгенерировавшее событие
    //              event_status_id	        "1502783"                           - ключ журнала статусов событий
    //              status_id	            "38"                                - ключ последнего статуса
    //              kind_reason_id	        "1"                                 - ключ причины события
    //              status_date_time	    "19.09.2019 10:23:46"               - дата изменения статуса события
    //              duration	            null/60 (мин)                       - продолжительность события (если статус не 40 и не 52 то считается)
    //              statuses:                                                   - история изменения статуса
    //                  [0]
    //                      event_status_id	"1354066"                           - ключ журнала статусов
    //                      status_id	    "40"                                - ключ статуса события
    //                      kind_reason_id	null                                - причина события
    //                      date_time	    "19.09.2019 10:24:46"               - дата изменения статуса события
    //              gilties:                                                    - список ответственных
    //                  [0]
    //                      event_journal_gilty_id	"1"                         - ключ списка ответственных
    //                      worker_id	            "35001543"                  - ключ ответственного работника
    //              operations:                                                 - список принятых мер
    //                  [0]
    //                      event_journal_correct_measure_id	"1"             - ключ списка корректирующих мероприятий
    //                      operation_id	                    "1"             - ключ операции
    // разработал: Якимов М.Н.
    // дата: 07.12.2019г
    // пример: http://127.0.0.1/read-manager-amicum?controller=EventCompareGas&method=GetABJournal&subscribe=&data={%22date_time_start%22:%222019-01-24%22,%22date_time_end%22:%222019-12-24%22}
    public static function GetABJournal($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'GetABJournal. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetABJournal. Данные с фронта не получены');
            }
            $warnings[] = 'GetABJournal. Данные успешно переданы';
            $warnings[] = 'GetABJournal. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'GetABJournal. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'date_time_start')) ||
                !(property_exists($post_dec, 'date_time_end'))
            ) {
                throw new Exception('GetABJournal. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'GetABJournal. Данные с фронта получены и они правильные';
            $date_time_event_start = $post_dec->date_time_start;
            $date_time_event_end = $post_dec->date_time_end;

            // получаем события из заданный период из таблицы event_journal
            $event_journal = EventJournal::find()
                ->select([
                    'event_journal.id as id',
                    'event_journal.value as sensor_value',
                    'event_journal.edge_id as edge_id',
                    'event_journal.date_time as date_time',
                    'event_status.datetime as status_date_time',
                    'sensor.id as sensor_id',
                    'sensor.title as sensor_title',
                    'event.id as event_id',
                    'event.title as event_title',
                    'edge.place_id as place_id',
                ])
                ->innerJoin('sensor', 'sensor.id=event_journal.main_id')
                ->leftJoin('edge', 'edge.id=event_journal.edge_id')
                ->joinWith('eventJournalGilties')
                ->joinWith('eventJournalCorrectMeasures')
                ->joinWith('event')
                ->joinWith('eventStatuses')
                ->where("event_journal.date_time>'" . $date_time_event_start . "'")
                ->andWhere("event_journal.date_time<'" . $date_time_event_end . "'")
                ->andWhere([
                    'event_journal.object_id' => [28, 47],
                ])
                ->andWhere(['IN', 'event_journal.event_id', [22409, 7130]])
                ->asArray()
                ->all();
            if (!$event_journal) {
                throw new Exception("GetABJournal. Журнал событий пуст");
            }

            foreach ($event_journal as $event_ab) {
                $event_id = $event_ab['event_id'];
                $journal_lamp[$event_id]['event_id'] = $event_id;
                $journal_lamp[$event_id]['event_title'] = $event_ab['event_title'];
                $journal_lamp[$event_id]['status_checked'] = 1;                                                         // статус устранения
                $event_date_time = $event_ab['date_time'];
                $journal_lamp[$event_id]['events'][$event_ab['id']]['event_date_time'] = $event_ab['date_time'];
                $journal_lamp[$event_id]['events'][$event_ab['id']]['event_date_time_format'] = date('d.m.Y H:i:s', strtotime($event_ab['date_time']));
                $journal_lamp[$event_id]['events'][$event_ab['id']]['event_journal_id'] = $event_ab['id'];
                $journal_lamp[$event_id]['events'][$event_ab['id']]['sensor_id'] = $event_ab['sensor_id'];
                $journal_lamp[$event_id]['events'][$event_ab['id']]['sensor_title'] = $event_ab['sensor_title'];
                $journal_lamp[$event_id]['events'][$event_ab['id']]['edge_id'] = $event_ab['edge_id'];
                $journal_lamp[$event_id]['events'][$event_ab['id']]['place_id'] = $event_ab['place_id'];
                $journal_lamp[$event_id]['events'][$event_ab['id']]['sensor_value'] = $event_ab['sensor_value'];

                // todo тут может быть засада, если статус последний ведет себя не корректно, то надо добавить проверку на сравнение уже существующей даты с текущей в переборе
                // и если текущая больше предыдущей, то все нормально, иначе пропускать.
                if ($event_ab['eventStatuses']) {
                    foreach ($event_ab['eventStatuses'] as $eventStatuses) {
                        $journal_lamp[$event_id]['events'][$event_ab['id']]['event_status_id'] = $eventStatuses['id'];
                        $journal_lamp[$event_id]['events'][$event_ab['id']]['status_id'] = $eventStatuses['status_id'];
                        $journal_lamp[$event_id]['events'][$event_ab['id']]['kind_reason_id'] = $eventStatuses['kind_reason_id'];
                        if ($eventStatuses['datetime']) {
                            $journal_lamp[$event_id]['events'][$event_ab['id']]['status_date_time'] = date('d.m.Y H:i:s', strtotime($eventStatuses['datetime']));
                        } else {
                            $journal_lamp[$event_id]['events'][$event_ab['id']]['status_date_time'] = "";
                        }
                        $status_item['event_status_id'] = $eventStatuses['id'];
                        $status_item['status_id'] = $eventStatuses['status_id'];
                        $status_item['kind_reason_id'] = $eventStatuses['kind_reason_id'];
                        if ($eventStatuses['datetime']) {
                            $status_item['date_time'] = date('d.m.Y H:i:s', strtotime($eventStatuses['datetime']));
                        } else {
                            $status_item['date_time'] = "";
                        }
                        $journal_lamp[$event_id]['events'][$event_ab['id']]['statuses'][] = $status_item;
                        if ($eventStatuses['status_id'] != 40) {
                            $journal_lamp[$event_id]['status_checked'] = 0;
                        }
                        if ($eventStatuses['status_id'] == 40 or $eventStatuses['status_id'] == 52) {
                            $journal_lamp[$event_id]['events'][$event_ab['id']]['duration'] = (strtotime($eventStatuses['datetime']) - strtotime($event_date_time)) / 60;
                        } else {
                            $journal_lamp[$event_id]['events'][$event_ab['id']]['duration'] = null;
                        }
                    }
                } else {
                    $journal_lamp[$event_id]['status_checked'] = 0;
                    $journal_lamp[$event_id]['events'][$event_ab['id']]['duration'] = null;
                    $journal_lamp[$event_id]['events'][$event_ab['id']]['event_status_id'] = -1;
                    $journal_lamp[$event_id]['events'][$event_ab['id']]['status_id'] = -1;
                    $journal_lamp[$event_id]['events'][$event_ab['id']]['status_date_time'] = "";
                    $journal_lamp[$event_id]['events'][$event_ab['id']]['kind_reason_id'] = -1;
                    $journal_lamp[$event_id]['events'][$event_ab['id']]['statuses'] = [];
                }

                if ($event_ab['eventJournalGilties']) {
                    foreach ($event_ab['eventJournalGilties'] as $eventGilty) {
                        $gilty_item['event_journal_gilty_id'] = $eventGilty['id'];
                        $gilty_item['worker_id'] = $eventGilty['worker_id'];
                        $journal_lamp[$event_id]['events'][$event_ab['id']]['gilties'][] = $gilty_item;
                    }
                } else {
                    $journal_lamp[$event_id]['events'][$event_ab['id']]['gilties'] = [];
                }

                if ($event_ab['eventJournalCorrectMeasures']) {
                    foreach ($event_ab['eventJournalCorrectMeasures'] as $eventOperation) {
                        $operation_item['event_journal_correct_measure_id'] = $eventOperation['id'];
                        $operation_item['operation_id'] = $eventOperation['operation_id'];
                        $journal_lamp[$event_id]['events'][$event_ab['id']]['operations'][] = $operation_item;
                    }
                } else {
                    $journal_lamp[$event_id]['events'][$event_ab['id']]['operations'] = [];
                }
            }

        } catch (Throwable $exception) {
            $errors[] = 'GetABJournal. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetABJournal. Конец метода';
        if (!isset($journal_lamp)) {
            $journal_lamp = (object)array();
        }
        $result_main = array('Items' => $journal_lamp, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetABJournalSituation - метод получения журнала ситуаций  оператора АГК
    // входные параметры:
    //      date_time_start         - дата начала за которую хотим получить журнал
    //      date_time_end           - дата окончания за которую хотим получить журнал
    //      situation_journal_id    - ключ журнала ситуаций (может быть Null - при этом если задан, то фильтр по дате не учитывается)
    //      mine_id                 - ключ шахты

    // выходной объект:
    //  {situation_id}
    //      situation_id:                   -   ключ ситуации
    //      situation_title                 -   название ситуации
    //      status_checked                  -   статус проверки (1 выполнена, 0 не выполнена)

    //      situations                      -   список ситуаций
    //          {situation_journal_id}
    //              situation_journal_id	    "1578570"                           - ключ журнала ситуаций
    //              situation_date_time_create          "2019-09-19 09:24:46"       - дата создания ситуации
    //              situation_date_time_create_format	"19.09.2019 09:24:46"       - форматированная дата создания ситуации
    //              situation_date_time_start           "2019-09-19 09:24:46"       - дата начала ситуации
    //              situation_date_time_start_format	"19.09.2019 09:24:46"       - форматированная дата начала ситуации
    //              situation_date_time_end             "2019-09-19 09:24:46"       - дата окончания ситуации
    //              situation_date_time_end_format	    "19.09.2019 09:24:46"       - форматированная дата окончания ситуации
    //              situation_status_id	        "1502783"                           - ключ журнала статусов ситуации
    //              status_id	                "38"                                - ключ последней ситуации
    //              kind_reason_id	            "1"                                 - ключ причины ситуации
    //              worker_id	                "1"                                 - ключ работника
    //              description	                "выапывап"                          - описание ситуации
    //              status_date_time	        "19.09.2019 10:23:46"               - дата изменения статуса ситуации
    //              duration	                null/60 (мин)                       - продолжительность ситуации (если статус не 40 и не 52 то считается)
    //              edges:                                                          - зона опасной ситуации
    //                  {edge_id}
    //                      edge_id                                                     - ключ выработки в которой произошло событие
    //              places:                                                         - зона опасной ситуации
    //                  {place_id}
    //                      place_id                                                    - ключ места в котором произошла ситуация
    //                      place_title                                                 - наименование места в котором произошло событие
    //              statuses:                                                       - история изменения статуса ситуации
    //                  [0]
    //                      situation_status_id "1354066"                               - ключ журнала ситуаций
    //                      status_id	        "40"                                    - ключ статуса ситуаций
    //                      kind_reason_id	    null                                    - причина ситуации
    //                      worker_id	        null                                    - ключ работника
    //                      description	        "sdfg"                                  - описание причин ситуации
    //                      date_time	        "19.09.2019 10:24:46"                   - дата изменения статуса ситуации
    //              gilties:                                                        - список ответственных
    //                  [0]
    //                      event_journal_gilty_id	"1"                                 - ключ списка ответственных
    //                      worker_id	            "35001543"                          - ключ ответственного работника
    //              operations:                                                     - список принятых мер
    //                  [0]
    //                      event_journal_correct_measure_id	"1"                     - ключ списка корректирующих мероприятий
    //                      operation_id	                    "1"                     - ключ операции
    //              events:                                                         - список событий
    //                {event_journal_id}                                              - ключ журнала событий
    //                    event_journal_id	        "1578570"                           - ключ журнала событий
    //                    event_id                                                      - ключ события
    //                    event_title                                                   - наименование события
    //                    object_table                                                   - наименование объекта (человек или сенсор)
    //                    event_date_time	        "2019-09-19 09:24:46"               - дата начала события
    //                    event_date_time_format	"19.09.2019 09:24:46"               - форматированная дата события
    //                    sensor_id	                "26721"                             - ключ датчика
    //                    sensor_title	            "Z-ЛУЧ-4 № Р 11-9  (Net ID 687953)" - название датчика
    //                    edge_id	                "139138"                            - ключ ветви в которой произошло событие
    //                    place_id	                "15009"                             - ключ места в котором произошло событие
    //                    object_id	                "1"                                 - ключ конкретного объекта
    //                    typical_object_id	        "1"                                 - ключ типового объекта
    //                    object_value	            "0.5"                               - значение параметра сгенерировавшее событие
    //                    event_status_id           "38"                                - ключ последнего статуса
    //                    group_alarm_id            ""                                  - ключ группы оповещения
    //                    xyz                       "0,0,0"                             - координата события
    // разработал: Якимов М.Н.
    // дата: 07.12.2019г
    // пример:
    // http://127.0.0.1/read-manager-amicum?controller=EventCompareGas&method=GetABJournalSituation&subscribe=&data={%22date_time_start%22:%222019-01-24%22,%22date_time_end%22:%222020-12-24%22,%22situation_journal_id%22:null}
    // http://127.0.0.1/read-manager-amicum?controller=EventCompareGas&method=GetABJournalSituation&subscribe=&data={%22date_time_start%22:%222019-01-24%22,%22date_time_end%22:%222020-12-24%22,%22situation_journal_id%22:61}
    public static function GetABJournalSituation($data_post = NULL)
    {
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("GetABJournalSituation");
        $result = (object)array();
        try {
            $log->addLog("Начало выполнения метода");

            /** Метод начало */
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('Данные с фронта не получены');
            }
            $log->addLog("Данные успешно переданы");
            $log->addLog('Входной массив данных' . $data_post);

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $log->addLog("Декодировал входные параметры");

            if (
                !(property_exists($post_dec, 'situation_journal_id')) ||
                !(property_exists($post_dec, 'date_time_start')) ||
                !(property_exists($post_dec, 'date_time_end'))
            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $log->addLog("Данные с фронта получены и они правильные");

            $date_time_event_start = date("Y-m-d H:i:s", strtotime($post_dec->date_time_start));
            $date_time_event_end = date("Y-m-d H:i:s", strtotime($post_dec->date_time_end));
            $front_situation_journal_id = $post_dec->situation_journal_id;

            $filter_mine = array();
            if (property_exists($post_dec, 'mine_id') and $post_dec->mine_id != -1) {
                $filter_mine = ['situation_journal.mine_id' => $post_dec->mine_id];
            }

            if ($front_situation_journal_id) {
                $filter_situation = "situation_journal.id = $front_situation_journal_id";
            } else {
                $filter_situation = "situation_journal.date_time>'" . $date_time_event_start . "'" . " and situation_journal.date_time<'" . $date_time_event_end . "'";
            }

            // получаем события из заданный период из таблицы $situation_journal
            $situation_journal = SituationJournal::find()
                ->select([
                    'situation_journal.id as id',
                    'situation_journal.mine_id as mine_id',
                    'situation.id as situation_id',
                    'situation.title as situation_title',
                    'situation_journal.danger_level_id as danger_level_id',
                    'danger_level.number_of_level as number_of_level',
                    'danger_level.title as danger_level_title',
                    'group_situation.id as group_situation_id',
                    'group_situation.title as group_situation_title',
                    'kind_group_situation.id as kind_group_situation_id',
                    'kind_group_situation.title as kind_group_situation_title',
                    'situation_journal.status_id as status_id_situation',
                    'situation_journal.date_time as date_time_create',
                    'situation_journal.date_time_start as date_time_start',
                    'situation_journal.date_time_end as date_time_end',
                    'situation_status.id as situation_status_id',
                    'situation_status.kind_reason_id as kind_reason_id',
                    'situation_status.worker_id as worker_id',
                    'situation_status.description as description',
                    'situation_status.date_time as status_date_time',
                    'situation_solution.regulation_time as regulation_time'
                ])
//                ->innerJoin('sensor', 'sensor.id=event_journal.main_id')
                ->joinWith('situationJournalZones.edge.place')
                ->joinWith('situationJournalSituationSolutions')
                ->joinWith('situationJournalGilties')
                ->joinWith('situationJournalCorrectMeasures')
                ->joinWith('situation.groupSituation.kindGroupSituation')
                ->joinWith('dangerLevel')
                ->joinWith('eventJournalSituationJournals.eventJournal.eventEdge.eventPlace')
                ->joinWith('eventJournalSituationJournals.eventJournal.event')
                ->joinWith('eventJournalSituationJournals.eventJournal.sensor.asmtp')
                ->joinWith('eventJournalSituationJournals.eventJournal.parameter.unit')
                ->joinWith('situationJournalSituationSolutions.situationSolution')
                ->joinWith('situationStatuses')
                ->where($filter_situation)
                ->andFilterWhere($filter_mine)
                ->orderBy(['status_date_time' => SORT_ASC, 'date_time_create' => SORT_ASC])
                ->asArray()
                ->all();

            foreach ($situation_journal as $situation_ab) {
                $situation_id = $situation_ab['situation_id'];
                $journal_AB[$situation_id]['situation_id'] = $situation_id;
                $journal_AB[$situation_id]['situation_title'] = $situation_ab['situation_title'];
                $journal_AB[$situation_id]['group_situation_id'] = $situation_ab['group_situation_id'];
                $journal_AB[$situation_id]['group_situation_title'] = $situation_ab['group_situation_title'];
                $journal_AB[$situation_id]['kind_group_situation_id'] = $situation_ab['kind_group_situation_id'];
                $journal_AB[$situation_id]['kind_group_situation_title'] = $situation_ab['kind_group_situation_title'];
                $journal_AB[$situation_id]['status_checked'] = 1;
                $situation_date_time = $situation_ab['date_time_create'];
                $journal_AB[$situation_id]['situations'][$situation_ab['id']]['date_time_create'] = $situation_ab['date_time_create'];
                if ($situation_ab['date_time_create']) {
                    $journal_AB[$situation_id]['situations'][$situation_ab['id']]['date_time_create_format'] = date('d.m.Y H:i:s', strtotime($situation_ab['date_time_create']));
                }
                $journal_AB[$situation_id]['situations'][$situation_ab['id']]['date_time_start'] = $situation_ab['date_time_start'];
                if ($situation_ab['date_time_start']) {
                    $journal_AB[$situation_id]['situations'][$situation_ab['id']]['date_time_start_format'] = date('d.m.Y H:i:s', strtotime($situation_ab['date_time_start']));
                } else {
                    $journal_AB[$situation_id]['situations'][$situation_ab['id']]['date_time_start_format'] = "";
                }
                $journal_AB[$situation_id]['situations'][$situation_ab['id']]['date_time_end'] = $situation_ab['date_time_end'];

                if ($situation_ab['date_time_end']) {
                    $journal_AB[$situation_id]['situations'][$situation_ab['id']]['date_time_end_format'] = date('d.m.Y H:i:s', strtotime($situation_ab['date_time_end']));
                    $date_time_end = $situation_ab['date_time_end'];
                } else {
                    $journal_AB[$situation_id]['situations'][$situation_ab['id']]['date_time_end_format'] = "";
                    $date_time_end = BackendAssistant::GetDateNow();
                }

                $journal_AB[$situation_id]['situations'][$situation_ab['id']]['mine_id'] = $situation_ab['mine_id'];
                $journal_AB[$situation_id]['situations'][$situation_ab['id']]['regulation_time'] = $situation_ab['regulation_time'];
                $journal_AB[$situation_id]['situations'][$situation_ab['id']]['danger_level_id'] = $situation_ab['danger_level_id'];
                $journal_AB[$situation_id]['situations'][$situation_ab['id']]['danger_level_title'] = $situation_ab['danger_level_title'];
                $journal_AB[$situation_id]['situations'][$situation_ab['id']]['number_of_level'] = $situation_ab['number_of_level'];
                $journal_AB[$situation_id]['situations'][$situation_ab['id']]['situation_journal_id'] = $situation_ab['id'];
                $journal_AB[$situation_id]['situations'][$situation_ab['id']]['situation_status_id'] = $situation_ab['situation_status_id'];
                $journal_AB[$situation_id]['situations'][$situation_ab['id']]['status_id'] = $situation_ab['status_id_situation'];
                $journal_AB[$situation_id]['situations'][$situation_ab['id']]['is_favorite_situation'] = false;
                $journal_AB[$situation_id]['situations'][$situation_ab['id']]['is_checked'] = false;
                $journal_AB[$situation_id]['situations'][$situation_ab['id']]['status_date_time'] = $situation_ab['status_date_time'];
                $journal_AB[$situation_id]['situations'][$situation_ab['id']]['worker_id'] = $situation_ab['worker_id'];
                $journal_AB[$situation_id]['situations'][$situation_ab['id']]['kind_reason_id'] = $situation_ab['kind_reason_id'];
                $journal_AB[$situation_id]['situations'][$situation_ab['id']]['description'] = $situation_ab['description'];
                $journal_AB[$situation_id]['situations'][$situation_ab['id']]['duration'] = round((strtotime($date_time_end) - strtotime($situation_date_time)) / 60, 0);
//                $journal_AB[$situation_id]['situations'][$situation_ab['id']]['durationFloat'] = (strtotime($date_time_end) - strtotime($situation_date_time)) / 60;


                // todo тут может быть засада, если статус последний ведет себя не корректно, то надо добавить проверку на сравнение уже существующей даты с текущей в переборе
                // и если текущая больше предыдущей, то все нормально, иначе пропускать.
                // получение списка статусов
                if ($situation_ab['situationStatuses']) {
                    foreach ($situation_ab['situationStatuses'] as $situationStatuses) {
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['event_status_id'] = $situationStatuses['id'];
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['status_id'] = $situationStatuses['status_id'];
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['kind_reason_id'] = $situationStatuses['kind_reason_id'];
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['worker_id'] = $situationStatuses['worker_id'];
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['description'] = $situationStatuses['description'];
                        if ($situationStatuses['date_time']) {
                            $journal_AB[$situation_id]['situations'][$situation_ab['id']]['status_date_time'] = date('d.m.Y H:i:s', strtotime($situationStatuses['date_time']));
                        } else {
                            $journal_AB[$situation_id]['situations'][$situation_ab['id']]['status_date_time'] = "";
                        }
                        $status_item['event_status_id'] = $situationStatuses['id'];
                        $status_item['status_id'] = $situationStatuses['status_id'];
                        $status_item['kind_reason_id'] = $situationStatuses['kind_reason_id'];
                        $status_item['worker_id'] = $situationStatuses['worker_id'];
                        $status_item['description'] = $situationStatuses['description'];
                        if ($situationStatuses['date_time']) {
                            $status_item['date_time'] = date('d.m.Y H:i:s', strtotime($situationStatuses['date_time']));
                        } else {
                            $status_item['date_time'] = "";
                        }
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['statuses'][] = $status_item;
                        if ($situationStatuses['status_id'] != 33 and $situationStatuses['status_id'] != 37) {                                                    // todo: нужно уточнить насчет статуса 32
                            $journal_AB[$situation_id]['status_checked'] = 0;
                        }
                        /**
                         * я закомментировал этот блок, так как нужно считать интервал не от даты записи статуса
                         * устранения ситуации, а от даты окончания ситуации (и они не равны), ведь теперь пользователь
                         * задает время окончания в модальном окне
                         **/
//                        if ($situationStatuses['status_id'] == 33 or $situationStatuses['status_id'] == 32) {
//                            $journal_AB[$situation_id]['situations'][$situation_ab['id']]['duration'] = round((strtotime($situationStatuses['date_time']) - strtotime($situation_date_time)) / 60, 0);
//                        } else {
//                            $journal_AB[$situation_id]['situations'][$situation_ab['id']]['duration'] = null;
//                        }
                    }
                } else {
                    $journal_AB[$situation_id]['status_checked'] = 0;
                    $journal_AB[$situation_id]['situations'][$situation_ab['id']]['situation_status_id'] = -1;
                    $journal_AB[$situation_id]['situations'][$situation_ab['id']]['status_id'] = -1;
                    $journal_AB[$situation_id]['situations'][$situation_ab['id']]['status_date_time'] = "";
                    $journal_AB[$situation_id]['situations'][$situation_ab['id']]['kind_reason_id'] = -1;
                    $journal_AB[$situation_id]['situations'][$situation_ab['id']]['worker_id'] = null;
                    $journal_AB[$situation_id]['situations'][$situation_ab['id']]['description'] = "";
                    $journal_AB[$situation_id]['situations'][$situation_ab['id']]['statuses'] = [];
                }

                // получение списка ответственных
                if ($situation_ab['situationJournalGilties']) {
                    foreach ($situation_ab['situationJournalGilties'] as $situationGilty) {
                        $gilty_item['event_journal_gilty_id'] = $situationGilty['id'];
                        $gilty_item['worker_id'] = $situationGilty['worker_id'];
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['gilties'][] = $gilty_item;
                    }
                } else {
                    $journal_AB[$situation_id]['situations'][$situation_ab['id']]['gilties'] = [];
                }

                // получение списка корректирующих мероприятий
                if ($situation_ab['situationJournalCorrectMeasures']) {
                    foreach ($situation_ab['situationJournalCorrectMeasures'] as $situationOperation) {
                        $operation_item['event_journal_correct_measure_id'] = $situationOperation['id'];
                        $operation_item['operation_id'] = $situationOperation['operation_id'];
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['operations'][] = $operation_item;
                    }
                } else {
                    $journal_AB[$situation_id]['situations'][$situation_ab['id']]['operations'] = [];
                }

                // получение списка мест и выработок
                if ($situation_ab['situationJournalZones']) {
                    foreach ($situation_ab['situationJournalZones'] as $situationZone) {
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['edges'][$situationZone['edge_id']] = array('edge_id' => $situationZone['edge_id']);
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['places'][$situationZone['edge']['place']['id']] =
                            array('place_id' => $situationZone['edge']['place']['id'],
                                'place_title' => $situationZone['edge']['place']['title']);

                    }
                } else {
                    $journal_AB[$situation_id]['situations'][$situation_ab['id']]['edges'] = (object)array();
                    $journal_AB[$situation_id]['situations'][$situation_ab['id']]['places'] = (object)array();
                }

                // получение списка событий
                if ($situation_ab['eventJournalSituationJournals']) {
                    foreach ($situation_ab['eventJournalSituationJournals'] as $event) {
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['event_journal_id'] = $event['event_journal_id'];
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['event_date_time'] = $event['eventJournal']['date_time'];
                        if ($event['eventJournal']['date_time']) {
                            $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['event_date_time_format'] = date('d.m.Y H:i:s', strtotime($event['eventJournal']['date_time']));
                        } else {
                            $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['event_date_time_format'] = '';
                        }
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['event_id'] = $event['eventJournal']['event_id'];
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['event_title'] = $event['eventJournal']['event']['title'];
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['object_id'] = $event['eventJournal']['main_id'];
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['typical_object_id'] = $event['eventJournal']['object_id'];
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['object_table'] = $event['eventJournal']['object_table'];
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['object_title'] = $event['eventJournal']['object_title'];
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['object_value'] = $event['eventJournal']['value'];
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['event_status_id'] = $event['eventJournal']['event_status_id'];
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['xyz'] = $event['eventJournal']['xyz'];
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['group_alarm_id'] = $event['eventJournal']['group_alarm_id'];
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['edge_id'] = $event['eventJournal']['edge_id'];
                        if ($event['eventJournal']['object_table'] == 'sensor' and isset($event['eventJournal']['sensor']) and isset($event['eventJournal']['sensor']['asmtp'])) {
                            $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['asmtp_id'] = $event['eventJournal']['sensor']['asmtp']['id'];
                            $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['asmtp_title'] = $event['eventJournal']['sensor']['asmtp']['title'];
                        } else {
                            $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['asmtp_id'] = -1;
                            $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['asmtp_title'] = "Система позиционирования";
                        }
                        if ($event['eventJournal']['eventEdge']) {
                            $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['place_id'] = $event['eventJournal']['eventEdge']['place_id'];
                            $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['place_title'] = $event['eventJournal']['eventEdge']['eventPlace']['title'];
                        } else {
                            $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['place_id'] = null;
                            $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['place_title'] = '';
                        }
                        if ($event['eventJournal']['parameter_id']) {
                            $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['unit_id'] = $event['eventJournal']['parameter']['unit']['id'];
                            $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['unit_short_title'] = $event['eventJournal']['parameter']['unit']['short'];
                        } else {
                            $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['unit_id'] = null;
                            $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'][$event['event_journal_id']]['unit_short_title'] = '';
                        }
                    }
                } else {
                    $journal_AB[$situation_id]['situations'][$situation_ab['id']]['events'] = (object)array();
                }

                // получение решений ситуаций
                if ($situation_ab['situationJournalSituationSolutions']) {
                    foreach ($situation_ab['situationJournalSituationSolutions'] as $situationSolution) {
                        $journal_AB[$situation_id]['situations'][$situation_ab['id']]['situation_solution_id'] = $situationSolution['situation_solution_id'];
                    }
                } else {
                    $journal_AB[$situation_id]['situations'][$situation_ab['id']]['situation_solution_id'] = null;
                }

            }
            if (isset($journal_AB)) {
                $result = $journal_AB;
            }

            /** Метод окончание */

            $log->addLog("Окончание выполнения метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    // GetListStatusEDB - получить список статусов событий Единой диспетчерской по безопасности
    // пример: http://127.0.0.1/read-manager-amicum?controller=EventCompareGas&method=GetListStatusEDB&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetListStatusEDB($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // Массив ошибок

        try {
            $status_list = Status::find()
                ->where(['status_type_id' => 10])
                ->limit(20000)
                ->indexBy('id')
                ->asArray()
                ->all();

            if (!$status_list) {
                $warnings[] = 'GetListStatus. Справочник статусов пуст';
                $result = (object)array();
            } else {
                $result = $status_list;
            }
        } catch (Throwable $exception) {
            $warnings[] = 'GetListStatus. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetListEvent - получить список событий
    // пример: http://127.0.0.1/read-manager-amicum?controller=EventCompareGas&method=GetListEvent&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetListEvent($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // Массив ошибок

        try {
            $event_list = Event::find()
                ->limit(20000)
                ->indexBy('id')
                ->asArray()
                ->all();

            if (!$event_list) {
                $warnings[] = 'GetListEvent. Справочник событий пуст';
                $result = (object)array();
            } else {
                $result = $event_list;
            }
        } catch (Throwable $exception) {
            $warnings[] = 'GetListEvent. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // SaveEventGournalStatus - метод сохранения объяснения причин события/отказа
    // входной объект:
    //          event_journal:                                              - название входного объекта
    //              event_journal_id	    "1578570"                           - ключ журнала событий
    //              event_date_time	        "2019-09-19 09:24:46"               - дата начала события
    //              event_date_time_format	"19.09.2019 09:24:46"               - форматированная дата события
    //              sensor_id	            "26721"                             - ключ датчика
    //              sensor_title	        "Z-ЛУЧ-4 № Р 11-9  (Net ID 687953)" - название датчика
    //              edge_id	                "139138"                            - ключ ветви в которой произошло событие
    //              place_id	            "15009"                             - ключ места в котором произошло событие
    //              sensor_value	        "0.5"                               - значение параметра сгенерировавшее событие
    //              event_status_id	        "1502783"                           - ключ журнала статусов событий
    //              status_id	            "38"                                - ключ последнего статуса
    //              kind_reason_id	        "1"                                 - ключ причины события
    //              status_date_time	    "19.09.2019 10:23:46"               - дата изменения статуса события
    //              duration	            null/60 (мин)                       - продолжительность события (если статус не 40 и не 52 то считается)
    //              statuses:                                                   - история изменения статуса
    //                  [0]
    //                      event_status_id	"1354066"                           - ключ журнала статусов
    //                      status_id	    "40"                                - ключ статуса события
    //                      kind_reason_id	null                                - причина события
    //                      date_time	    "19.09.2019 10:24:46"               - дата изменения статуса события
    //              gilties:                                                    - список ответственных
    //                  [0]
    //                      event_journal_gilty_id	"1"                         - ключ списка ответственных
    //                      worker_id	            "35001543"                  - ключ ответственного работника
    //              operations:                                                 - список принятых мер
    //                  [0]
    //                      event_journal_correct_measure_id	"1"             - ключ списка корректирующих мероприятий
    //                      operation_id	                    "1"             - ключ операции
    public static function SaveEventGournalStatus($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'SaveEventGournalStatus. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('SaveEventGournalStatus. Данные с фронта не получены');
            }
            $warnings[] = 'SaveEventGournalStatus. Данные успешно переданы';
            $warnings[] = 'SaveEventGournalStatus. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'SaveEventGournalStatus. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'event_journal'))
            ) {
                throw new Exception('SaveEventGournalStatus. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'SaveEventGournalStatus. Данные с фронта получены и они правильные';
            $event_journal = $post_dec->event_journal;

            // сохраняем принятые меры в таблицу EventJournalCorrectMeasure
            EventJournalCorrectMeasure::deleteAll(['event_journal_id' => $event_journal->event_journal_id]);
            foreach ($event_journal->operations as $operation) {
                if (isset($operation->operation_id) && !empty($operation->operation_id)) {
                    $event_journal_correct_measure = new EventJournalCorrectMeasure();

                    $event_journal_correct_measure->event_journal_id = $event_journal->event_journal_id;

                    $event_journal_correct_measure->operation_id = $operation->operation_id;


                    if ($event_journal_correct_measure->save()) {
                    } else {
                        $errors[] = $event_journal_correct_measure->errors;
                        throw new Exception('SaveEventGournalStatus. Ошибка сохранения модели принятых мер EventJournalCorrectMeasure');
                    }
                }
            }

            // сохраняем ответственных/виновных в таблицу EventJournalGilty
            EventJournalGilty::deleteAll(['event_journal_id' => $event_journal->event_journal_id]);
            foreach ($event_journal->gilties as $gilty) {
                if (isset($gilty->worker_id) && !empty($gilty->worker_id)) {
                    $event_journal_gilty = new EventJournalGilty();

                    $event_journal_gilty->event_journal_id = $event_journal->event_journal_id;
                    $event_journal_gilty->worker_id = $gilty->worker_id;
                    if ($event_journal_gilty->save()) {
                    } else {
                        $errors[] = $event_journal_gilty->errors;
                        throw new Exception('SaveEventGournalStatus. Ошибка сохранения модели принятых мер EventJournalGilty');
                    }
                }

            }

            // сохраняем Новый статус и причину события в таблицу EventStatus
            $eventStatus = new EventStatus();
            $eventStatus->event_journal_id = $event_journal->event_journal_id;
            $eventStatus->status_id = $event_journal->status_id;
            $eventStatus->datetime = BackendAssistant::GetDateNow();
            $eventStatus->kind_reason_id = $event_journal->kind_reason_id;
            if ($eventStatus->save()) {
                $eventStatus->refresh();
                $event_journal->event_status_id = $eventStatus->id;
                $event_journal->status_date_time = date('d.m.Y H:i:s', strtotime($eventStatus->datetime));
                $event_journal->statuses[] = array('event_status_id' => $eventStatus->id, 'status_id' => $eventStatus->status_id, 'kind_reason_id' => $eventStatus->kind_reason_id, 'date_time' => date('d.m.Y H:i:s', strtotime($eventStatus->datetime)));
                if ($event_journal->status_id == 52 || $event_journal->status_id == 40) {
                    $event_journal->duration = (strtotime($eventStatus->datetime) - strtotime($event_journal->event_date_time)) / 60;
                }
            } else {
                $errors[] = $eventStatus->errors;
                throw new Exception('SaveEventGournalStatus. Ошибка сохранения модели принятых мер EventStatus');
            }

        } catch (Throwable $exception) {
            $errors[] = 'SaveEventGournalStatus. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'SaveEventGournalStatus. Конец метода';
        if (!isset($event_journal)) {
            $result = (object)array();
        } else {
            $result = $event_journal;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // SaveSituationJournalStatus - метод сохранения объяснения причин ситуации
    // входной объект:
    //          situation_journal:                                              - название входного объекта
    //  {situation_id}
    //      situation_id:                   -   ключ ситуации
    //      situation_title                 -   название ситуации
    //      situations                      -   список ситуаций
    //      status_checked                  -   статус проверки (1 выполнена, 0 не выполнена)
    //          {situation_journal_id}
    //              situation_journal_id	    "1578570"                           - ключ журнала ситуаций
    //              situation_date_time_create          "2019-09-19 09:24:46"       - дата создания ситуации
    //              situation_date_time_create_format	"19.09.2019 09:24:46"       - форматированная дата создания ситуации
    //              situation_date_time_start           "2019-09-19 09:24:46"       - дата начала ситуации
    //              situation_date_time_start_format	"19.09.2019 09:24:46"       - форматированная дата начала ситуации
    //              situation_date_time_end             "2019-09-19 09:24:46"       - дата окончания ситуации
    //              situation_date_time_end_format	    "19.09.2019 09:24:46"       - форматированная дата окончания ситуации
    //              situation_status_id	        "1502783"                           - ключ журнала статусов ситуации
    //              status_id	                "38"                                - ключ последней ситуации
    //              kind_reason_id	            "1"                                 - ключ причины ситуации
    //              worker_id	                "1"                                 - ключ работника
    //              description	                "выап"                              - описание причины события
    //              status_date_time	        "19.09.2019 10:23:46"               - дата изменения статуса ситуации
    //              duration	                null/60 (мин)                       - продолжительность ситуации (если статус не 40 и не 52 то считается)
    //              edges:                                                          - зона опасной ситуации
    //                  {edge_id}
    //                      edge_id                                                     - ключ выработки в которой произошло событие
    //              places:                                                         - зона опасной ситуации
    //                  {place_id}
    //                      place_id                                                    - ключ места в котором произошла ситуация
    //                      place_title                                                 - наименование места в котором произошло событие
    //              statuses:                                                       - история изменения статуса ситуации
    //                  [0]
    //                      situation_status_id "1354066"                               - ключ журнала ситуаций
    //                      status_id	        "40"                                    - ключ статуса ситуаций
    //                      kind_reason_id	    null                                    - причина ситуации
    //                      worker_id	        null                                    - ключ работника
    //                      description	        ""                                      - описание причины события
    //                      date_time	        "19.09.2019 10:24:46"                   - дата изменения статуса ситуации
    //              gilties:                                                        - список ответственных
    //                  [0]
    //                      event_journal_gilty_id	"1"                                 - ключ списка ответственных
    //                      worker_id	            "35001543"                          - ключ ответственного работника
    //              operations:                                                     - список принятых мер
    //                  [0]
    //                      event_journal_correct_measure_id	"1"                     - ключ списка корректирующих мероприятий
    //                      operation_id	                    "1"                     - ключ операции
    //              events:                                                         - список событий
    //                {event_journal_id}                                              - ключ журнала событий
    //                    event_journal_id	        "1578570"                           - ключ журнала событий
    //                    event_id                                                      - ключ события
    //                    event_title                                                   - наименование события
    //                    event_date_time	        "2019-09-19 09:24:46"               - дата начала события
    //                    event_date_time_format	"19.09.2019 09:24:46"               - форматированная дата события
    //                    sensor_id	                "26721"                             - ключ датчика
    //                    sensor_title	            "Z-ЛУЧ-4 № Р 11-9  (Net ID 687953)" - название датчика
    //                    edge_id	                "139138"                            - ключ ветви в которой произошло событие
    //                    place_id	                "15009"                             - ключ места в котором произошло событие
    //                    object_value	            "0.5"                               - значение параметра сгенерировавшее событие
    //                    event_status_id           "38"                                - ключ последнего статуса
    //                    group_alarm_id            "1"                                 - ключ группы оповещения
    //                    xyz                       "0,0,0"                             - координата события
    public static function SaveSituationJournalStatus($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'SaveSituationJournalStatus. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('SaveSituationJournalStatus. Данные с фронта не получены');
            }
            $warnings[] = 'SaveSituationJournalStatus. Данные успешно переданы';
            $warnings[] = 'SaveSituationJournalStatus. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'SaveSituationJournalStatus. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'situation_journal'))
            ) {
                throw new Exception('SaveSituationJournalStatus. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'SaveSituationJournalStatus. Данные с фронта получены и они правильные';
            $situation_journal = $post_dec->situation_journal;

            // сохраняем принятые меры в таблицу situationJournalCorrectMeasure
            SituationJournalCorrectMeasure::deleteAll(['situation_journal_id' => $situation_journal->situation_journal_id]);
            foreach ($situation_journal->operations as $operation) {
                if (isset($operation->operation_id) && !empty($operation->operation_id)) {
                    $situation_journal_correct_measure = new SituationJournalCorrectMeasure();

                    $situation_journal_correct_measure->situation_journal_id = $situation_journal->situation_journal_id;

                    $situation_journal_correct_measure->operation_id = $operation->operation_id;


                    if ($situation_journal_correct_measure->save()) {
                    } else {
                        $errors[] = $situation_journal_correct_measure->errors;
                        throw new Exception('SaveSituationJournalStatus. Ошибка сохранения модели принятых мер situationJournalCorrectMeasure');
                    }
                }
            }

            // сохраняем ответственных/виновных в таблицу situationJournalGilty
            SituationJournalGilty::deleteAll(['situation_journal_id' => $situation_journal->situation_journal_id]);
            foreach ($situation_journal->gilties as $gilty) {
                if (isset($gilty->worker_id) && !empty($gilty->worker_id)) {
                    $situation_journal_gilty = new SituationJournalGilty();

                    $situation_journal_gilty->situation_journal_id = $situation_journal->situation_journal_id;
                    $situation_journal_gilty->worker_id = $gilty->worker_id;
                    if ($situation_journal_gilty->save()) {
                    } else {
                        $errors[] = $situation_journal_gilty->errors;
                        throw new Exception('SaveSituationJournalStatus. Ошибка сохранения модели принятых мер situationJournalGilty');
                    }
                }

            }

            // сохраняем Новый статус и причину события в таблицу situationStatus
            $session = Yii::$app->session;
            $situationStatus = new SituationStatus();
            $situationStatus->situation_journal_id = $situation_journal->situation_journal_id;
            $situationStatus->status_id = $situation_journal->status_id;
            $situationStatus->worker_id = $session['worker_id'];
            $situationStatus->date_time = BackendAssistant::GetDateNow();
            $situationStatus->kind_reason_id = $situation_journal->kind_reason_id;
            $situationStatus->description = $situation_journal->description;
            if ($situationStatus->save()) {
                $situationStatus->refresh();
                $situation_journal->situation_status_id = $situationStatus->id;
                $situation_journal->worker_id = $session['worker_id'];
                $situation_journal->status_date_time = date('d.m.Y H:i:s', strtotime($situationStatus->date_time));
                $situation_journal->statuses[] = array(
                    'situation_status_id' => $situationStatus->id,
                    'status_id' => $situationStatus->status_id,
                    'kind_reason_id' => $situationStatus->kind_reason_id,
                    'worker_id' => $session['worker_id'],
                    'description' => $situationStatus->description,
                    'date_time' => date('d.m.Y H:i:s', strtotime($situationStatus->date_time)));
//                if ($situation_journal->status_id == 32 || $situation_journal->status_id == 33) {
//                    $situation_journal->duration = (strtotime($situationStatus->date_time) - strtotime($situation_journal->date_time_start)) / 60;
////                    $situation_journal->date_time_end = $situationStatus->date_time;
////                    $situation_journal->date_time_end_format = date('d.m.Y H:i:s', strtotime($situationStatus->date_time));
//                }
            } else {
                $errors[] = $situationStatus->errors;
                throw new Exception('SaveSituationJournalStatus. Ошибка сохранения модели принятых мер situationStatus');
            }

            // сохраняем Новый статус и дату окончания ситуации в таблице SituationJournal
            $saveSituation = SituationJournal::findOne(['id' => $situation_journal->situation_journal_id]);

            // блок обработки отказа светильника/ стационарного датчика
            // алгоритм:
            //  1. если kind_reason_id==[1] - отказ светильника и количество событий в этой ситуации одно и ситуация не отказ светильника (situation_id!=2), то
            //      -- эту часть пока не стал делать, хз чем это обернется
            //          2. ещем прочие ситуации с участием  данного датчика/светильника в течение 4 часов.
            //          3. Если нашли, то проверяем, что там одно событие, и если так, то берем от туда дату начала (если она меньше текущей) и в данной ситуации пересчитываем время начала и окончания
            //          4. найденную ситуацию с одним событием удаляем
            //      ^^ эту часть пока не стал делать, хз чем это обернется
            //  5. ищем запись журнала ситуации и меняем ситуацию на 2(отказ светильника)
            //  6. сохраняем данную ситуацию с другими данными
            //  7. в кеше переквалифицируем данную ситуацию, и добавляем событие отказ светильника
            $count_events = count((array)$situation_journal->events);
            $warnings[] = 'SaveSituationJournalStatus. Проверка на переклассификацию ситуации';
            $warnings[] = 'SaveSituationJournalStatus. Причина отказа kind_reason_id = ' . $situation_journal->kind_reason_id;
            $warnings[] = 'SaveSituationJournalStatus. Ситуация situation_id = ' . $situation_journal->situation_id;
            $warnings[] = 'SaveSituationJournalStatus. Количество событий в ситуации count_events = ' . $count_events;
            $last_situation_id = $situation_journal->situation_id;

            if ($situation_journal->kind_reason_id == 1
                and $situation_journal->situation_id != 2
                and $count_events == 1) {
                $warnings[] = 'SaveSituationJournalStatus. Зашел в переклассификацию';
                $saveSituation->situation_id = 2;                                                                       // откза светильника

            } else {
                $warnings[] = 'SaveSituationJournalStatus. Причина ситуации не отказ (kind_reason_id!=1) или ситуацию уже отказ светильника (situation_id=2)';
            }

            $saveSituation->date_time_end = $situation_journal->date_time_end;
            $saveSituation->status_id = $situation_journal->status_id;
            $situation_cache = new SituationCacheController();
            $event_cache = new EventCacheController();

            if ($saveSituation->save()) {
                $saveSituation->refresh();
                $warnings[] = 'SaveSituationJournalStatus. сохранил ситуацию в БД';
                $mine_id = $saveSituation->mine_id;
                $situation_journal->situation_id = $saveSituation->situation_id;
                if ($situation_journal->situation_id == 2 and $last_situation_id != 2) {
                    $situation_journal->situation_title = "Отказ индив.датч.";
                    $situation_journal->last_situation_id = $last_situation_id;

                    // переклассифицируем ситуацию
                    $situation_cache->deleteSituation($mine_id, $situation_journal->situation_journal_id, $last_situation_id);
                    $warnings[] = 'SaveSituationJournalStatus. Удалил ситуацию из кеша';

                    $response = $situation_cache->setSituation($saveSituation->id, $saveSituation->situation_id, $saveSituation->date_time,
                        $saveSituation->main_id, $saveSituation->status_id, $saveSituation->danger_level_id, $saveSituation->company_department_id,
                        $mine_id, $saveSituation->date_time_start, $saveSituation->date_time_end);
                    if (!$response['status']) {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        throw new Exception('SaveSituationJournalStatus. Ошибка сохранения Ситуации в кеш');
                    }
                    $warnings[] = 'SaveSituationJournalStatus. создал новую ситуацию в кеше';

                    // обновляем кеш зон ситуации
                    $situation_cache->deleteMultiZone($mine_id, $situation_journal->situation_journal_id);
                    foreach ($situation_journal->edges as $edge) {
                        $zone[] = $edge->edge_id;
                    }
                    if (isset($zone)) {
                        $response = $situation_cache->multiSetZone($saveSituation->id, $zone, $mine_id, $saveSituation->situation_id);
                        if (!$response['status']) {
                            $errors[] = $response['errors'];
                            $warnings[] = $response['warnings'];
                            throw new Exception('SaveSituationJournalStatus. Ошибка сохранения зоны ситуации в кеш');
                        }
                        $warnings[] = 'SaveSituationJournalStatus. Сохранил зону в кеше';
                    }

                    // обновляем событие в журнале событий
                    foreach ($situation_journal->events as $event) {
                        $last_event_id = $event->event_id;
                        $save_event = EventJournal::findOne(['id' => $event->event_journal_id]);
                        if (!$save_event) {
                            throw new Exception('SaveSituationJournalStatus. Такого события не существует в  EventJournal event_journal_id = ' . $event->event_journal_id);
                        }
                        $save_event->event_id = EventEnumController::CH4_CRUSH_LAMP;
                        if (!$save_event->save()) {
                            $errors[] = $save_event->errors;
                            throw new Exception('SaveSituationJournalStatus. Ошибка сохранения модели EventJournal');
                        }
                        $event->event_id = EventEnumController::CH4_CRUSH_LAMP;
                        $event->event_title = "Отказ светильника";
                        $warnings[] = 'SaveSituationJournalStatus. обновил событие в БД';

                        $event_cache->deleteEvent($mine_id, $last_event_id, $event->object_id);
                        $warnings[] = 'SaveSituationJournalStatus. удалил предыдущее событие из кеша';

                        $response = $event_cache->setEvent($save_event->mine_id, $save_event->event_id, $save_event->main_id, $save_event->event_status_id,
                            $save_event->edge_id, $save_event->value,
                            $save_event->status_id, $save_event->date_time, $save_event->xyz,
                            $save_event->parameter_id, $save_event->object_id, $save_event->object_title, $save_event->object_table,
                            $save_event->id, $save_event->group_alarm_id);
                        if (!$response['status']) {
                            $errors[] = $response['errors'];
                            $warnings[] = $response['warnings'];
                            throw new Exception('SaveSituationJournalStatus. Ошибка сохранения События в кеш');
                        }
                        $warnings[] = 'SaveSituationJournalStatus. сохранил событие отказа в кеш';

                    }
                }

                // если статус все хорошо, то очищаем кеш ситуаций по данной ситуации
                if ($situation_journal->status_id == 32 || $situation_journal->status_id == 33 || $situation_journal->status_id == 37) {
                    $situation_journal->duration = round((strtotime($situation_journal->date_time_end) - strtotime($situation_journal->date_time_start)) / 60, 0);
                    // если ситуация закрыта, то удаляем ее из кеша
                    $situation_cache->deleteSituation($mine_id, $situation_journal->situation_journal_id, $situation_journal->situation_id);
                    $situation_cache->deleteMultiSituationEvent($mine_id, $situation_journal->situation_journal_id);
                    $situation_cache->deleteMultiZone($mine_id, $situation_journal->situation_journal_id);
                }
            } else {
                $errors[] = $saveSituation->errors;
                throw new Exception('SaveSituationJournalStatus. Ошибка сохранения модели журнала ситуаций SituationJournal');
            }

            $situation_current_to_ws = array(
                'situation_journal_id' => $situation_journal->situation_journal_id,                                     // ключ журнала ситуации
                'situation_id' => 1,                                                                                    // ключ ситуации
                'situation_title' => '',                                                                                // название ситуации
                'mine_id' => $mine_id,                                                                                  // ключ шахты
                'status_checked' => 0,                                                                                  // статус проверки
                'situation_date_time' => "",                                                                            // время создания ситуации
                'situation_date_time_format' => "",                                                                     // время создания ситуации форматированное
                'object_id' => null,                                                                                    // ключ работника
                'object_title' => '',                                                                                   // ФИО работника
                'edge_id' => null,                                                                                      // выработка в которой произошла ситуация
                'place_id' => 0,                                                                                        // место ситуации
                'status_id' => null,                                                                                    // статус значения (нормальное/ аварийное)
                'sensor_value' => 5,                                                                                    // значение концентрации газа
                'kind_reason_id' => null,                                                                               // вид причины опасного действия
                'status_date_time' => "",                                                                               // время изменения статуса ситуации
                'situation_status_id' => '',                                                                            // текущий статус ситуации (принята в работу, устранена и т.д.)
                'duration' => null,                                                                                     // продолжительность ситуации
                'statuses' => [],                                                                                       // список статусов (история изменения ситуации)
                'gilties' => [],                                                                                        // список виновных
                'operations' => [],                                                                                     // список принятых мер
                'event_journals' => (object)array(),                                                                    // список журнала событий ситуации
                'object_table' => ""                                                                                    // таблица в котрой лежит объект (сенсор, воркер)
            );
            $response = WebsocketController::SendMessageToWebSocket("addNewSituationJournal", $situation_current_to_ws);

            $warnings[] = $response['warnings'];
            $errors[] = $response['errors'];


        } catch (Throwable $exception) {
            $errors[] = 'SaveSituationJournalStatus. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'SaveSituationJournalStatus. Конец метода';
        if (!isset($situation_journal)) {
            $result = (object)array();
        } else {
            $result = $situation_journal;
        }

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // GetListSituation - получить список ситуаций
    // пример: http://127.0.0.1/read-manager-amicum?controller=EventCompareGas&method=GetListSituation&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetListSituation($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // Массив ошибок

        try {
            $event_list = Situation::find()
                ->limit(20000)
                ->indexBy('id')
                ->asArray()
                ->all();

            if (!$event_list) {
                $warnings[] = 'GetListSituation. Справочник ситуаций пуст';
                $result = (object)array();
            } else {
                $result = $event_list;
            }
        } catch (Throwable $exception) {
            $warnings[] = 'GetListSituation. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetListSituationJournal - получить список журнала ситуаций
    // пример: http://127.0.0.1/read-manager-amicum?controller=EventCompareGas&method=GetListSituationJournal&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetListSituationJournal($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // Массив ошибок

        try {
            $event_list = SituationJournal::find()
                ->limit(20000)
                ->indexBy('id')
                ->asArray()
                ->all();

            if (!$event_list) {
                $warnings[] = 'GetListSituationJournal. Справочник ситуаций пуст';
                $result = (object)array();
            } else {
                $result = $event_list;
            }
        } catch (Throwable $exception) {
            $warnings[] = 'GetListSituationJournal. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetListStatusSituationEDB - получить список статусов ситуаций Единой диспетчерской по безопасности
    // пример: http://127.0.0.1/read-manager-amicum?controller=EventCompareGas&method=GetListStatusSituationEDB&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetListStatusSituationEDB($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // Массив ошибок

        try {
            $status_list = Status::find()
                ->where(['status_type_id' => 9])
                ->limit(20000)
                ->indexBy('id')
                ->asArray()
                ->all();

            if (!$status_list) {
                $warnings[] = 'GetListStatusSituationEDB. Справочник статусов ситуаций пуст';
                $result = (object)array();
            } else {
                $result = $status_list;
            }
        } catch (Throwable $exception) {
            $warnings[] = 'GetListStatusSituationEDB. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetHystoryStaticCompare - метод получения истории по СТАЦИОНАРУ сравнений двух газов
    // входные параметры:
    //      date_time_start     - дата начала получения истории
    //      date_time_end       - дата окончания получения истории
    //      lamp_sensor_id      - ключ индивидуального светильника
    // выходные параметры:
    //  {141270}	                    static_sensor_id
    //        [0]
    //          event_compare_gas_id	"32"
    //          pdk_status	            0
    //          sensor2_id	            "26411"
    //          sensor2_title	        "Z-ЛУЧ-4 № 1-19 (Net ID 660416)"
    //          event_date_time2	    "12.12.2019 10:28:59"
    //          sensor2_value	        "0"
    //          unit2_id	            "82"
    //          place2_id	            "141270"
    //          place2_title	        "Лава 623-ю пл. Четвертого"
    //          sensor_id	            "115986"
    //          sensor_title	        "АГЗ комбайн лавы 623-ю (CH33_KUSH6)"
    //          event_date_time	        "12.12.2019 10:28:59"
    //          sensor_value	        "0.5"
    //          unit_id	                "82"
    //          place_id	            "141270"
    //          place_title	            "Лава 623-ю пл. Четвертого"
    // разработал: Якимов М.Н.
    // дата: 07.12.2019г
    // пример: http://127.0.0.1/read-manager-amicum?controller=EventCompareGas&method=GetHystoryStaticCompare&subscribe=&data={%22date_time_start%22:%222019-09-24%22,%22date_time_end%22:%222020-09-24%22,%22static_sensor_id%22:%22115986%22}
    public static function GetHystoryStaticCompare($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'GetHystoryStaticCompare. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetHystoryStaticCompare. Данные с фронта не получены');
            }
            $warnings[] = 'GetHystoryStaticCompare. Данные успешно переданы';
            $warnings[] = 'GetHystoryStaticCompare. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'GetHystoryStaticCompare. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'date_time_start')) ||
                !(property_exists($post_dec, 'date_time_end')) ||
                !(property_exists($post_dec, 'static_sensor_id'))
            ) {
                throw new Exception('GetHystoryStaticCompare. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'GetHystoryStaticCompare. Данные с фронта получены и они правильные';
            $date_time_start = $post_dec->date_time_start;
            $date_time_end = $post_dec->date_time_end;
            $static_sensor_id = $post_dec->static_sensor_id;

            // получаем события сравнения двух газов из таблицы event_journal_gas
            $event_compare_gas = EventCompareGas::find()
                ->joinWith('lampParameter')
                ->joinWith('staticParameter')
                ->joinWith('lampEdge.lampPlace')
                ->joinWith('staticEdge.staticPlace')
                ->where("date_time>'" . $date_time_start . "'")
                ->andWhere("date_time<'" . $date_time_end . "'")
                ->andWhere(['static_sensor_id' => $static_sensor_id])
                ->asArray()
                ->all();

            // готовим данные по сравнению в удобнов виде
            foreach ($event_compare_gas as $event_compare) {
                $event_compare_item['event_compare_gas_id'] = $event_compare['id'];
                if ($event_compare['event_id'] == 22409) {
                    $event_compare_item['pdk_status'] = 1;  // статус превышения ПДК: 1 есть, 0 нет
                } else {
                    $event_compare_item['pdk_status'] = 0;
                }
                $event_compare_item['sensor2_id'] = $event_compare['lamp_sensor_id'];
                $event_compare_item['sensor2_title'] = $event_compare['lamp_object_title'];
                if ($event_compare['date_time']) {
                    $event_compare_item['event_date_time2'] = date('d.m.Y H:i:s', strtotime($event_compare['date_time']));
                } else {
                    $event_compare_item['event_date_time2'] = "";
                }
                $event_compare_item['sensor2_value'] = $event_compare['lamp_value'];
                $event_compare_item['unit2_id'] = $event_compare['lampParameter']['unit_id'];

                if ($event_compare['lamp_edge_id']) {
                    $event_compare_item['place2_id'] = $event_compare['lampEdge']['place_id'];
                    $event_compare_item['place2_title'] = $event_compare['lampEdge']['lampPlace']['title'];
                } else {
                    $event_compare_item['place2_id'] = -1;
                    $event_compare_item['place2_title'] = '';
                }
                $event_compare_item['sensor_id'] = $event_compare['static_sensor_id'];
                $event_compare_item['sensor_title'] = $event_compare['static_object_title'];
                if ($event_compare['date_time']) {
                    $event_compare_item['event_date_time'] = date('d.m.Y H:i:s', strtotime($event_compare['date_time']));
                } else {
                    $event_compare_item['event_date_time'] = "";
                }
                $event_compare_item['sensor_value'] = $event_compare['static_value'];
                $event_compare_item['unit_id'] = $event_compare['staticParameter']['unit_id'];
                if ($event_compare['static_edge_id']) {
                    $event_compare_item['place_id'] = $event_compare['staticEdge']['place_id'];
                    $event_compare_item['place_title'] = $event_compare['staticEdge']['staticPlace']['title'];
                } else {
                    $event_compare_item['place_id'] = -1;
                    $event_compare_item['place_title'] = '';
                }
                $event_compare_gas_array[$event_compare['static_sensor_id']][] = $event_compare_item;
            }


        } catch (Throwable $exception) {
            $errors[] = 'GetHystoryStaticCompare. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetHystoryStaticCompare. Конец метода';
        if (!isset($event_compare_gas_array)) {
            $event_compare_gas_array = (object)array();
        }
        $result_main = array('Items' => $event_compare_gas_array, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    // GetHystoryLampCompare - метод получения истории ЛАМПЫ сравнений двух газов
    // входные параметры:
    //      date_time_start     - дата начала получения истории
    //      date_time_end       - дата окончания получения истории
    //      lamp_sensor_id      - ключ индивидуального светильника
    // выходные параметры:
    //  {26411}	                    lamp_sensor_id
    //        [0]
    //          event_compare_gas_id	    "32"
    //          sensor2_id	                "115986"
    //          sensor2_title	            "АГЗ комбайн лавы 623-ю (CH33_KUSH6)"
    //          event_date_time2	        "12.12.2019 10:28:59"
    //          sensor2_value	            "0.5"
    //          unit2_id	                "82"
    //          place2_id	                "141270"
    //          place2_title	            "Лава 623-ю пл. Четвертого"
    //          pdk_status	                0
    //          sensor_id	                "26411"
    //          sensor_title	            "Z-ЛУЧ-4 № 1-19 (Net ID 660416)"
    //          event_date_time	            "12.12.2019 10:28:59"
    //          sensor_value	            "0"
    //          unit_id	                    "82"
    //          place_id	                "141270"
    //          place_title	                "Лава 623-ю пл. Четвертого"
    // разработал: Якимов М.Н.
    // дата: 07.12.2019г
    // пример: http://127.0.0.1/read-manager-amicum?controller=EventCompareGas&method=GetHystoryLampCompare&subscribe=&data={%22date_time_start%22:%222020-09-24%22,%22date_time_end%22:%222019-09-24%22,%22lamp_sensor_id%22:%2226411%22}
    public static function GetHystoryLampCompare($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'GetHystoryLampCompare. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetHystoryLampCompare. Данные с фронта не получены');
            }
            $warnings[] = 'GetHystoryLampCompare. Данные успешно переданы';
            $warnings[] = 'GetHystoryLampCompare. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'GetHystoryLampCompare. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'date_time_start')) ||
                !(property_exists($post_dec, 'date_time_end')) ||
                !(property_exists($post_dec, 'lamp_sensor_id'))
            ) {
                throw new Exception('GetHystoryLampCompare. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'GetHystoryLampCompare. Данные с фронта получены и они правильные';
            $date_time_start = $post_dec->date_time_start;
            $date_time_end = $post_dec->date_time_end;
            $lamp_sensor_id = $post_dec->lamp_sensor_id;

            // получаем события сравнения двух газов из таблицы event_journal_gas
            $event_compare_gas = EventCompareGas::find()
                ->joinWith('lampParameter')
                ->joinWith('staticParameter')
                ->joinWith('lampEdge.lampPlace')
                ->joinWith('staticEdge.staticPlace')
                ->where("date_time>'" . $date_time_start . "'")
                ->andWhere("date_time<'" . $date_time_end . "'")
                ->andWhere(['lamp_sensor_id' => $lamp_sensor_id])
                ->asArray()
                ->all();

            // готовим данные по сравнению в удобнов виде
            foreach ($event_compare_gas as $event_compare) {
                $event_compare_item['event_compare_gas_id'] = $event_compare['id'];
                if ($event_compare['event_id'] == 22409) {
                    $event_compare_item['pdk_status'] = 1;  // статус превышения ПДК: 1 есть, 0 нет
                } else {
                    $event_compare_item['pdk_status'] = 0;
                }
                $event_compare_item['sensor_id'] = $event_compare['lamp_sensor_id'];
                $event_compare_item['sensor_title'] = $event_compare['lamp_object_title'];
                if ($event_compare['date_time']) {
                    $event_compare_item['event_date_time'] = date('d.m.Y H:i:s', strtotime($event_compare['date_time']));
                } else {
                    $event_compare_item['event_date_time'] = "";
                }
                $event_compare_item['sensor_value'] = $event_compare['lamp_value'];
                $event_compare_item['unit_id'] = $event_compare['lampParameter']['unit_id'];

                if ($event_compare['lamp_edge_id']) {
                    $event_compare_item['place_id'] = $event_compare['lampEdge']['place_id'];
                    $event_compare_item['place_title'] = $event_compare['lampEdge']['lampPlace']['title'];
                } else {
                    $event_compare_item['place_id'] = -1;
                    $event_compare_item['place_title'] = '';
                }
                $event_compare_item['sensor2_id'] = $event_compare['static_sensor_id'];
                $event_compare_item['sensor2_title'] = $event_compare['static_object_title'];
                if ($event_compare['date_time']) {
                    $event_compare_item['event_date_time2'] = date('d.m.Y H:i:s', strtotime($event_compare['date_time']));
                } else {
                    $event_compare_item['event_date_time2'] = "";
                }
                $event_compare_item['sensor_value2'] = $event_compare['static_value'];
                $event_compare_item['unit_id2'] = $event_compare['staticParameter']['unit_id'];
                if ($event_compare['static_edge_id']) {
                    $event_compare_item['place_id2'] = $event_compare['staticEdge']['place_id'];
                    $event_compare_item['place_title2'] = $event_compare['staticEdge']['staticPlace']['title'];
                } else {
                    $event_compare_item['place_id2'] = -1;
                    $event_compare_item['place_title2'] = '';
                }
                $event_compare_gas_array[$event_compare['lamp_sensor_id']][] = $event_compare_item;
            }


        } catch (Throwable $exception) {
            $errors[] = 'GetHystoryLampCompare. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetHystoryLampCompare. Конец метода';
        if (!isset($event_compare_gas_array)) {
            $event_compare_gas_array = (object)array();
        }
        $result_main = array('Items' => $event_compare_gas_array, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }
}
