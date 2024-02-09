<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\handbooks;

use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Event;
use frontend\models\KindDuration;
use frontend\models\KindStopPb;
use Throwable;

class HandbookStopPbController extends \yii\web\Controller
{
    // GetKindDuration                              - Получение справочника видов остановок
    // SaveKindDuration                             - Сохранение нового вида пристановки
    // DeleteKindDuration                           - Удаление вида приостановки
    // GetKindStopPb                                - Получение справочника видов простоев ПБ
    // GetKindStopPbFavorite                        - Метод получения справочника избранных видов простоев ПБ
    // GetEventStopPbFavorite                       - Метод получения справочника избранных причин простоев
    // SaveKindStopPb                               - Сохранение нового вида простоя ПБ
    // DeleteKindStopPb                             - Удаление вида простоя ПБ

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetKindDuration() - Получение справочника видов остановок
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,					        // идентификатор вида остановки
     *      "title":"До устранения"				// наименование вида остановки
     * ]
     * warnings:{}                              // массив предупреждений
     * errors:{}                                // массив ошибок
     * status:1                                 // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookStopPb&method=GetKindDuration&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 09:43
     */
    public static function GetKindDuration()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetKindDuration';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $kind_duration = KindDuration::find()
                ->asArray()
                ->all();
            if(empty($kind_duration)){
                $warnings[] = $method_name.'. Справочник видов приостановок пуст';
            }else{
                $result = $kind_duration;
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
     * Метод SaveKindDuration() - Сохранение нового вида пристановки
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "kind_duration":
     *  {
     *      "kind_duration_id":-1,					                    // идентификатор вида приостановки (-1 = новый вид приостановки)
     *      "title":"KIND_DURATION_TEST"				                // наименование вида приостановки
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "kind_duration_id":5,					                    // идентификатор сохранённого вида приостановки
     *      "title":"KIND_DURATION_TEST"				                // сохранённое наименование вида приостановки
     * }
     * warnings:{}                                                      // массив предупреждений
     * errors:{}                                                        // массив ошибок
     * status:1                                                         // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookStopPb&method=SaveKindDuration&subscribe=&data={"kind_duration":{"kind_duration_id":-1,"title":"KIND_DURATION_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 09:47
     */
    public static function SaveKindDuration($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveKindDuration';
        $chat_type_data = array();																				// Промежуточный результирующий массив
        $warnings[] = $method_name.'. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_duration'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $kind_duration_id = $post_dec->kind_duration->kind_duration_id;
            $title = $post_dec->kind_duration->title;
            $kind_duration = KindDuration::findOne(['id'=>$kind_duration_id]);
            if (empty($kind_duration)){
                $kind_duration = new KindDuration();
            }
            $kind_duration->title = $title;
            if ($kind_duration->save()){
                $kind_duration->refresh();
                $chat_type_data['kind_duration_id'] = $kind_duration->id;
                $chat_type_data['title'] = $kind_duration->title;
            }else{
                $errors[] = $kind_duration->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового вида приостановки');
            }
            unset($kind_duration);
        } catch (\Throwable $exception) {
            $errors[] = $method_name.'. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name.'. Конец метода';
        $result = $chat_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteKindDuration() - Удаление вида приостановки
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "kind_duration_id": 3             // идентификатор удаляемого вида приостановки
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookStopPb&method=DeleteKindDuration&subscribe=&data={"kind_duration_id":3}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 09:49
     */
    public static function DeleteKindDuration($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteKindDuration';
        $warnings[] = $method_name.'. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_duration_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $kind_duration_id = $post_dec->kind_duration_id;
            $del_kind_duration = KindDuration::deleteAll(['id'=>$kind_duration_id]);
        } catch (\Throwable $exception) {
            $errors[] = $method_name.'. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name.'. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetKindStopPb() - Получение справочника видов простоев ПБ
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 4,					            // идентификатор вида простоя ПБ
     *      "title":"Аварийный простой"				// наименование вида простоя ПБ
     * ]
     * warnings:{}                                  // массив предупреждений
     * errors:{}                                    // массив ошибок
     * status:1                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookStopPb&method=GetKindStopPb&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 16:25
     */
    public static function GetKindStopPb()
    {
        $log = new LogAmicumFront("GetKindStopPb");
        $result = array();

        try {
            $log->addLog("Начал выполнение метода");

            $kind_stop_pb = KindStopPb::find()
                ->asArray()
                ->all();

            if (empty($kind_stop_pb)) {
                $log->addLog("Справочник видов простоев ПБ пуст");
            } else {
                $result = $kind_stop_pb;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetKindStopPbFavorite - Метод получения справочника избранных видов простоев ПБ
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     *      company_department_id   - ключ подразделения, на который получаем список избранных материалов
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 4,                                // идентификатор вида простоя ПБ
     *      "title":"Аварийный простой"                // наименование вида простоя ПБ
     * ]
     * warnings:{}                                  // массив предупреждений
     * errors:{}                                    // массив ошибок
     * status:1                                     // статус выполнения метода
     *
     * @example 127.0.0.1/read-manager-amicum?controller=handbooks\HandbookStopPb&method=GetKindStopPbFavorite&subscribe=&data={%22company_department_id%22:60002522}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 16:25
     */
    public static function GetKindStopPbFavorite($data_post = null)
    {
        $log = new LogAmicumFront("GetKindStopPbFavorite");
        $result = array();

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
            $date_time = date("Y-m-d", strtotime(Assistant::GetDateTimeNow() . '-14days'));

            $kind_stop_pb = KindStopPb::find()
                ->innerJoin('stop_pb', 'stop_pb.kind_stop_pb_id=kind_stop_pb.id')
                ->where(['company_department_id' => $company_department_id])
                ->andWhere('date_time_start>"' . $date_time . '"')
                ->asArray()
                ->all();

            if (empty($kind_stop_pb)) {
                $log->addLog("Справочник видов простоев ПБ пуст");
            } else {
                $result = $kind_stop_pb;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetEventStopPbFavorite - Метод получения справочника избранных причин простоев
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     *      company_department_id   - ключ подразделения, на который получаем список избранных материалов
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 4,                                // идентификатор вида простоя ПБ
     *      "title":"Аварийный простой"                // наименование вида простоя ПБ
     * ]
     *
     * @example 127.0.0.1/read-manager-amicum?controller=handbooks\HandbookStopPb&method=GetEventStopPbFavorite&subscribe=&data={%22company_department_id%22:60002522}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 16:25
     */
    public static function GetEventStopPbFavorite($data_post = null)
    {
        $log = new LogAmicumFront("GetEventStopPbFavorite");
        $result = array();

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
            $date_time = date("Y-m-d", strtotime(Assistant::GetDateTimeNow() . '-14days'));

            $event_stop_pb = Event::find()
                ->innerJoin('stop_pb_event', 'stop_pb_event.event_id=event.id')
                ->innerJoin('stop_pb', 'stop_pb.id=stop_pb_event.stop_pb_id')
                ->where(['company_department_id' => $company_department_id])
                ->andWhere('date_time_start>"' . $date_time . '"')
                ->asArray()
                ->all();

            if (empty($event_stop_pb)) {
                $log->addLog("Справочник избранных причин простоев ПБ пуст");
            } else {
                $result = $event_stop_pb;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод SaveKindStopPb() - Сохранение нового вида простоя ПБ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "kind_stop_pb":
     *  {
     *      "kind_stop_pb_id":-1,					                // идентификатор вида простоя ПБ (-1 =  новый вид простоя ПБ)
     *      "title":"KIND_STOP_PB_TEST"				                // наименование вида простоя ПБ
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "kind_stop_pb_id":9,					                // идентификатор сохранённого вида простоя ПБ
     *      "title":"KIND_STOP_PB_TEST"				                // сохранённое наименование вида простоя ПБ
     * }
     * warnings:{}                                                  // массив предупреждений
     * errors:{}                                                    // массив ошибок
     * status:1                                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookStopPb&method=SaveKindStopPb&subscribe=&data={"kind_stop_pb":{"kind_stop_pb_id":-1,"title":"KIND_STOP_PB_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 16:30
     */
    public static function SaveKindStopPb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveKindStopPb';
        $chat_type_data = array();																				// Промежуточный результирующий массив
        $warnings[] = $method_name.'. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_stop_pb'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $kind_stop_pb_id = $post_dec->kind_stop_pb->kind_stop_pb_id;
            $title = $post_dec->kind_stop_pb->title;
            $kind_duration = KindStopPb::findOne(['id'=>$kind_stop_pb_id]);
            if (empty($kind_duration)){
                $kind_duration = new KindStopPb();
            }
            $kind_duration->title = $title;
            if ($kind_duration->save()){
                $kind_duration->refresh();
                $chat_type_data['kind_stop_pb_id'] = $kind_duration->id;
                $chat_type_data['title'] = $kind_duration->title;
            }else{
                $errors[] = $kind_duration->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового вида простоя ПБ');
            }
            unset($kind_duration);
        } catch (\Throwable $exception) {
            $errors[] = $method_name.'. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name.'. Конец метода';
        $result = $chat_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteKindStopPb() - Удаление вида простоя ПБ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "kind_stop_pb_id": 9             // идентификатор удаляемого вида простоя ПБ
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookStopPb&method=DeleteKindStopPb&subscribe=&data={"kind_stop_pb_id":9}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 16:37
     */
    public static function DeleteKindStopPb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteKindStopPb';
        $warnings[] = $method_name.'. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_stop_pb_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $kind_stop_pb_id = $post_dec->kind_stop_pb_id;
            $del_kind_stop_pb = KindStopPb::deleteAll(['id'=>$kind_stop_pb_id]);
        } catch (\Throwable $exception) {
            $errors[] = $method_name.'. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name.'. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }
}
