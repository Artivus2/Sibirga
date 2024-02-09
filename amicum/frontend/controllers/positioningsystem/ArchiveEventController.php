<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers\positioningsystem;
//ob_start();

use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\system\LogAmicumFront;
use yii;
use yii\db\Query;
use yii\web\Controller;
use yii\web\Response;

class ArchiveEventController extends Controller
{

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**Функция получения архивных событий
     * Входные параметры:
     *  dateStart (date) - дата начала диапазона времени, за который нужно отобразить архивные события,
     *  dateEnd (date) - дата конца диапазона времени, за который нужно отобразить архивные события,
     *  place_id (int) - id места, в котором произошло событие,
     *  object_id (int) - id объекта, у которого наступило событие,
     *  event_id (int) - id наступившего события.
     *
     * Прримеры использования для get-запроса для Комсомольской базы:
     * http://localhost/archive-event/get-archive-events?dateStart=1.09.2017&dateEnd=11.12.2018&place_id=15009
     * http://localhost/archive-event/get-archive-events?dateStart=1.09.2017&dateEnd=11.12.2018&place_id=15009&object_id=2909314
     * http://localhost/archive-event/get-archive-events?dateStart=1.09.2017&dateEnd=11.12.2018&place_id=15009&object_id=2909314&event_id=7127
     * Created by: Курбанов И. С. on 11.12.2018
     */
    public function actionGetArchiveEvents()
    {
        $post = Assistant::GetServerMethod();                                                                             //Подучение данных методом POST
        $response = self::GetArchiveEvents($post);
        if ($response['status'] != 1) {
            throw new Exception("actionGetArchiveEvents. Не смог получить события из БД");
        }
        $events = $response['events'];
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array('debug_information' => $post, 'events' => $events);
    }

    public static function GetArchiveEvents($post)
    {
        $log = new LogAmicumFront("GetArchiveEvents");
        $events = [];
        try {
            //если переданы обе даты
            if (isset($post['dateStart']) && $post['dateStart'] != '' && isset($post['dateEnd']) && $post['dateEnd'] != '') {
                //если даты равны, найти события за указанную дату
                if (strtotime($post['dateStart']) == strtotime($post['dateEnd']))
                    $sqlFilter = "DATE(status_date_time) = ('" . date('Y-m-d', strtotime($post['dateStart'])) . "')";
                //если стартовая дата меньше конечной, найти события по диапазону
                else if (strtotime($post['dateStart']) < strtotime($post['dateEnd']))
                    $sqlFilter = "DATE(status_date_time) BETWEEN '" . date('Y-m-d', strtotime($post['dateStart'])) . "' AND '" . date('Y-m-d', strtotime($post['dateEnd'] . ' +1 day')) . "'";
                //если конечная дата меньше стартовой, найти события по диапазону
                else {
                    $sqlFilter = "DATE(status_date_time) BETWEEN '" . date('Y-m-d', strtotime($post['dateEnd'])) . "' AND '" . date('Y-m-d', strtotime($post['dateStart'] . ' +1 day')) . "'";
                }
            } //если даты не переданы, найти события за последние 5 дней
            else {
                $sqlFilter = 'status_date_time >= CURDATE() - INTERVAL 5 DAY ';
            }

            if (isset($post['mine_id']) && $post['mine_id'] != '' && $post['mine_id'] != -1)                                // если передан еще  id шахты, то добавим в массив
            {
                $sqlFilter .= " and mine_id = " . (int)$post['mine_id'];                                                    //добавляем критерий поиска по его id
            }

            if (isset($post['object_id']) and $post['object_id'] != '') {                                                   //если задан фильтр по объекту, у которого произошло событие
                $post['objectIdFlag'] = true;
                $post['object_info'] = $post['object_id'];
                $sqlFilter .= " and object_id = " . (int)$post['object_id'];                                                //добавляем критерий поиска по его id
            } else {
                $post['objectIdFlag'] = false;
            }
            if (isset($post['place_id']) and $post['place_id'] != '') {                                                     //если задан фильтр по месту, на котором произошло событие
                $post['placeIdFlag'] = true;
                $sqlFilter .= " and place_id = " . (int)$post['place_id'];                                                  //добавляем критерий поиска по его id
            } else {
                $post['placeIdFlag'] = false;
            }
            if (isset($post['event_id']) and $post['event_id'] != '') {                                                     //если задан фильтр по произошедшему событию
                $post['eventIdFlag'] = true;
                $event_ids = $post['event_id'];

                if (is_array($event_ids)) {
                    $event_id_text = null;
                    foreach ($event_ids as $event_id) {
                        if (!$event_id_text) {
                            $event_id_text = $event_id;
                        } else {
                            $event_id_text .= "," . $event_id;
                        }
                    }
                    $sqlFilter .= " and event_id in (" . $event_id_text . ")";                                                //добавляем критерий поиска по его id
                } else {
                    $sqlFilter .= " and event_id = " . (int)$event_ids;                                                //добавляем критерий поиска по его id
                }
            } else {
                $post['eventIdFlag'] = false;
            }
            if (isset($post['search']) and $post['search'] != "") {
                $post['search'] = str_replace('"', '', $post['search']);
                $post['search'] = str_replace("'", '', $post['search']);

                $sqlFilter .= " and (place_title like '%" . strval($post['search']) .
                    "%' or object_title like '%" . strval($post['search']) .
                    "%' or object_id = '%" . strval($post['search']) .
                    "%')";
            }
            $post[] = $sqlFilter;
            $events = (new Query())
                ->select([
                    'id',
                    'event_title',
                    'event_id',
                    'status_title',
                    'status_id',
                    'edge_id',
                    'place_title',
                    'place_id',
                    'object_id',
                    'object_title',
                    'event_type',
                    'event_type_id',
                    'parameter_value',
                    'mine_id',
                    'status_date_time as date_time',
                    'unit_short'
                ])
                ->from('view_event_journal_all')
                ->where($sqlFilter)
//            ->orderBy("date_time ASC")
                ->all();
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        return array_merge(['events' => $events, 'Items' => []], $log->getLogAll());
    }

    /**
     * Метод получения списков всех местоположений, объектов, и событий.
     * Нужен для фильтрации, на стороне Front
     * Пример завпроса:
     * http://localhost/archive-event/get-array-for-filter-dropdowns
     *
     * Создал: Аксенов И.Ю.
     */
    public function actionGetArrayForFilterDropdowns()
    {
        $post = Assistant::GetServerMethod();
        $mine_id = null;
        if (isset($post['mine_id']) && $post['mine_id'] != '' && $post['mine_id'] != -1)                                                     // если передан еще  id места, то добавим в массив
        {
            $mine_id = (int)$post['mine_id'];
        }

        $places = self::getPlacesByMineId($mine_id);

        $events = (new Query())
            ->select('id, title')
            ->from('event')
            ->orderBy('title ASC')
            ->all();

//        $objects = $query
//            ->select('object_id as id, object_title as title')
//            ->from('view_event_journal_all')
//            ->groupBy('object_id, object_title, specific_object')
//            ->orderBy(['specific_object' => SORT_DESC, 'title' => SORT_ASC])
//            ->all();

        $mines = (new Query())
            ->select('id, title')
            ->from('mine')
            ->orderBy(['title' => SORT_ASC])
            ->all();

        $result = array('places' => $places, 'events' => $events, 'mines' => $mines);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    public function actionGetPlacesByMineId()
    {
        $post = Assistant::GetServerMethod();
        $mine_id = null;

        if (isset($post['mine_id']) && $post['mine_id'] != '' && $post['mine_id'] != -1)                                                     // если передан еще  id места, то добавим в массив
        {
            $mine_id = (int)$post['mine_id'];
        }

        $places = self::getPlacesByMineId($mine_id);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $places;
    }

    public static function getPlacesByMineId($mine_id)
    {
        if ($mine_id && $mine_id > 0) {
            $places = (new Query())
                ->select('id, title')
                ->from('place')
                ->where(['mine_id' => $mine_id])
                ->orderBy('title ASC')
                ->all();
        } else {
            $places = (new Query())
                ->select('id, title')
                ->from('place')
                ->orderBy('title ASC')
                ->all();
        }
        return $places;
    }
}
