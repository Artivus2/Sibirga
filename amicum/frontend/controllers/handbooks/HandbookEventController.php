<?php

namespace frontend\controllers\handbooks;

//ob_start();

use frontend\controllers\Assistant;
use frontend\models\AccessCheck;
use frontend\models\Event;
use frontend\models\GroupAlarm;
use frontend\models\KindReason;
use frontend\models\Main;
use frontend\models\TypicalObject;
use Yii;
use yii\db\Query;
use yii\web\Response;

class HandbookEventController extends \yii\web\Controller
{
    // GetKindReason                    - Получение справочника видов причин ситуаций
    // SaveKindReason                   - Сохранение нового вида причины ситуации
    // DeleteKindReason                 - Удаление вида причины ситуации

    // GetGroupAlarm()      - Получение справочника групп оповещения
    // SaveGroupAlarm()     - Сохранение справочника групп оповещения
    // DeleteGroupAlarm()   - Удаление справочника групп оповещения


    public function actionIndex()
    {
        $model = $this->buildArray();
        return $this->render('index', [
            'model' => $model,
        ]);
    }

    public function buildArray()
    {
        $eventArray = Event::find()->orderBy('title')->all();
        $model = array();
        $i = 0;
        foreach ($eventArray as $events) {
            $model[$i] = array();
            $model[$i]['iterator'] = $i + 1;
            $model[$i]['id'] = $events->id;
            $model[$i]['title'] = $events->title;
            $model[$i]['objectId'] = $events->object_id;
            $i++;
        }
        return $model;
    }

    public function actionAddEvent()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 29)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $events = Event::findOne(['title' => $post['title']]);
                if (!$events) {
                    $main = new Main();
                    $main->table_address = "event";
                    $main->db_address = "amicum2";
                    $main->save();

                    $events = new Event();
                    $events->id = $main->id;
                    $events->title = $post['title'];
                    $objectID = TypicalObject::findOne(['title' => 'Событие']);
                    if ($objectID) {
                        $events->object_id = $objectID->id;
                    } else {
                        $errors[] = "Нет объекта 'Событие'";
                        $model = $this->buildArray();
                    }
                    if ($events->save()) {
                        $model = $this->buildArray();
//                        echo json_encode($model);
                    } else {
                        $errors[] = "Модель не сохранена";
                        $model = $this->buildArray();
                    }
                } else {
                    $errors[] = "Такое событие уще существует";
                    $model = $this->buildArray();
                }
            } else {
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->buildArray();
            }
        } else {
            $errors[] = "Сессия неактивна";
            $model = $this->buildArray();
        }
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }


    public function actionEditEvent()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 30)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $events = Event::findOne($post['id']);
                if ($events) {
                    $existingEvent = Event::findOne(['title' => $post['title']]);
                    if (!$existingEvent || $events->id === $existingEvent->id) {
                        $events->title = $post['title'];
                        if ($events->save()) {
                            $model = $this->buildArray();
//                            echo json_encode($model);
                        } else {
                            $errors[] = "Редактирование не удалось";
                            $model = $this->buildArray();
                        }
                    } else {
                        $errors[] = "Такое событие уже существует";
                        $model = $this->buildArray();
                    }
                } else {
                    $errors[] = "Такого события не существует";
                    $model = $this->buildArray();
                }
            } else {
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->buildArray();
            }
        } else {
            $errors[] = "Сессия неактивна";
            $model = $this->buildArray();
        }
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    public function actionDeleteEvent()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 31)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $events = Event::findOne($post['id']);
                if ($events) {
                    if ($events->delete()) {
                        $main = Main::findOne($post['id']);
                        $main->delete();
                        $model = $this->buildArray();
//                        echo json_encode($model);
                    } else {
                        $errors[] = "Удаление не было выполнено";
                        $model = $this->buildArray();
                    }
                } else {
                    $errors[] = "Нет такого события";
                    $model = $this->buildArray();
                }
            } else {
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->buildArray();
            }
        } else {
            $errors[] = "Сессия неактивна";
            $model = $this->buildArray();
        }
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }


    /**
     * Метод поиска событий с выделением найденного
     * Created by: Одилов О.У. on 30.10.2018 12:02
     */
    public function actionMarkSearchEvents()
    {
        $post = Yii::$app->request->post();
        $errors = array();
        $events_handbook = array();
        if (isset($post['search_title'])) {
            $search_title = $post['search_title'];
            $sql_condition = "title LIKE '%$search_title%'";
            $events = (new Query())->select('id, title, object_id')->from('event')->where($sql_condition)->orderBy(['title' => SORT_ASC])->all();
            $i = 0;
            foreach ($events as $event) {
                $events_handbook[$i]['id'] = $event['id'];
                $events_handbook[$i]['iterator'] = $i + 1;
                $events_handbook[$i]['title'] = Assistant::MarkSearched($search_title, $event['title']);
                $events_handbook[$i]['objectId'] = $event['object_id'];
                $i++;
            }
            unset($events);
        } else {
            $errors[] = "Параметры не переданы";
        }
        $result = array('errors' => $errors, 'events' => $events_handbook);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Метод GetKindReason() - Получение справочника видов причин ситуаций
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,                                // идентификатор вида причины ситуации
     *      "title":"Отказ светильника"                // наименование вида причины ситуации
     * ]
     * warnings:{}                                  // массив предупреждений
     * errors:{}                                    // массив ошибок
     * status:1                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=GetKindReason&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 16:02
     */
    public static function GetKindReason()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetKindReason';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $kind_reason = KindReason::find()
                ->asArray()
                ->all();
            if (empty($kind_reason)) {
                $warnings[] = $method_name . '. Справочник видов причин ситуаций пуст';
            } else {
                $result = $kind_reason;
            }
        } catch (\Throwable $exception) {
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
     * Метод SaveKindReason() - Сохранение нового вида причины ситуации
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "kind_reason":
     *  {
     *      "kind_reason_id":-1,                                        // идентификатор вида причины ситуации (-1 =  новый вид причины ситуации)
     *      "title":"KIND_REASON_TEST"                                    / наименование вида причины ситуации
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "kind_reason_id":26,                                        // идентификатор сохранённого вида причины ситуации
     *      "title":"KIND_REASON_TEST"                                // сохранённое наименование вида причины ситуации
     * }
     * warnings:{}                                                  // массив предупреждений
     * errors:{}                                                    // массив ошибок
     * status:1                                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=SaveKindReason&subscribe=&data={"kind_reason":{"kind_reason_id":-1,"title":"KIND_REASON_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 16:05
     */
    public static function SaveKindReason($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveKindReason';
        $chat_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_reason'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $kind_reason_id = $post_dec->kind_reason->kind_reason_id;
            $title = $post_dec->kind_reason->title;
            $kind_reason = KindReason::findOne(['id' => $kind_reason_id]);
            if (empty($kind_reason)) {
                $kind_reason = new KindReason();
            }
            $kind_reason->title = $title;
            if ($kind_reason->save()) {
                $kind_reason->refresh();
                $chat_type_data['kind_reason_id'] = $kind_reason->id;
                $chat_type_data['title'] = $kind_reason->title;
            } else {
                $errors[] = $kind_reason->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового вида причины ситуации');
            }
            unset($kind_reason);
        } catch (\Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $chat_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteKindReason() - Удаление вида причины ситуации
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "kind_reason_id": 6             // идентификатор удаляемого вида причины ситуации
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=DeleteKindReason&subscribe=&data={"kind_reason_id":26}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 16:09
     */
    public static function DeleteKindReason($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteKindMishap';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_reason_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $kind_reason_id = $post_dec->kind_reason_id;
            $del_kind_reason = KindReason::deleteAll(['id' => $kind_reason_id]);
        } catch (\Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetGroupAlarm()      - Получение справочника групп оповещения
    // SaveGroupAlarm()     - Сохранение справочника групп оповещения
    // DeleteGroupAlarm()   - Удаление справочника групп оповещения

    /**
     * Метод GetGroupAlarm() - Получение справочника групп оповещения
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                        // ключ справочника
     *      "title":"ACTION",                // название справочника
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=GetGroupAlarm&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetGroupAlarm()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetGroupAlarm';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_group_alarm = GroupAlarm::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_group_alarm)) {
                $result = (object) array();
                $warnings[] = $method_name . '. Справочник групп оповещения пуст';
            } else {
                $result = $handbook_group_alarm;
            }
        } catch (\Throwable $exception) {
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
     * Метод SaveGroupAlarm() - Сохранение справочника групп оповещения
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "group_alarm":
     *  {
     *      "group_alarm_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "group_alarm_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=SaveGroupAlarm&subscribe=&data={"group_alarm":{"group_alarm_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveGroupAlarm($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveGroupAlarm';
        $handbook_group_alarm_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'group_alarm'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_group_alarm_id = $post_dec->group_alarm->group_alarm_id;
            $title = $post_dec->group_alarm->title;
            $new_handbook_group_alarm_id = GroupAlarm::findOne(['id' => $handbook_group_alarm_id]);
            if (empty($new_handbook_group_alarm_id)) {
                $new_handbook_group_alarm_id = new GroupAlarm();
            }
            $new_handbook_group_alarm_id->title = $title;
            if ($new_handbook_group_alarm_id->save()) {
                $new_handbook_group_alarm_id->refresh();
                $handbook_group_alarm_data['group_alarm_id'] = $new_handbook_group_alarm_id->id;
                $handbook_group_alarm_data['title'] = $new_handbook_group_alarm_id->title;
            } else {
                $errors[] = $new_handbook_group_alarm_id->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении справочника групп оповещения');
            }
            unset($new_handbook_group_alarm_id);
        } catch (\Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_group_alarm_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteGroupAlarm() - Удаление справочника групп оповещения
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "group_alarm_id": 98             // идентификатор справочника групп оповещения
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=DeleteGroupAlarm&subscribe=&data={"group_alarm_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteGroupAlarm($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteGroupAlarm';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'group_alarm_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_group_alarm_id = $post_dec->group_alarm_id;
            $del_handbook_group_alarm = GroupAlarm::deleteAll(['id' => $handbook_group_alarm_id]);
        } catch (\Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }
}
