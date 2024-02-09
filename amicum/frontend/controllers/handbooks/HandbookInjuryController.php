<?php

namespace frontend\controllers\handbooks;

use frontend\models\CasePb;
use frontend\models\KindCrash;
use frontend\models\KindIncident;
use frontend\models\KindMishap;
use frontend\models\Outcome;

class HandbookInjuryController extends \yii\web\Controller
{
    // GetCasePb                    - Получение справочника обстоятельств
    // SaveCasePb                   - Сохранение нового обстоятельства
    // DeleteCasePb                 - Удаление обстоятельства
    // GetKindCrash                 - Получение справочника видов аварий
    // SaveKindCrash                - Сохранение нового вида аварии
    // DeleteKindCrash              - Удаление типа аварии
    // GetKindIncident              - Получение справочника видов инцидентов
    // SaveKindIncident             - Сохранение нового вида инцидента
    // DeleteKindIncident           - Удаление вида инцидента
    // GetKindMishap                - Получение справочника видов несчастных случаев
    // SaveKindMishap               - Сохранение нового вида несчастного случая
    // DeleteKindMishap             - Удаление вида несчастного случая
    // GetOutcome                   - Получение справочника исходов травм
    // SaveOutcome                  - Сохранение нового исхода травмы
    // DeleteOutcome                - Удаление исхода травмы

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetCasePb() - Получение справочника обстоятельств
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,					    // идентификатор обстоятельства
     *      "title":"Авария"				// наименование обстоятельства
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInjury&method=GetCasePb&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 08:21
     */
    public static function GetCasePb()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetCasePb';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $case_pb_data = CasePb::find()
                ->asArray()
                ->all();
            if(empty($case_pb_data)){
                $warnings[] = $method_name.'. Справочник обстоятельств пуст';
            }else{
                $result = $case_pb_data;
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
     * Метод SaveCasePb() - Сохранение нового обстоятельства
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "case_pb":
     *  {
     *      "case_pb_id":-1,					                    // идентификатор обстоятельства (-1 = при добавлении нового обстоятельства)
     *      "title":"CASE_PB_TEST"				                    // наименование обстоятельства
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "case_pb_id":5,					                        // идентификатор сохранённого обстоятельства
     *      "title":"CASE_PB_TEST"				                    // сохранённое наименование обстоятельства
     *
     * }
     * warnings:{}                                                  // массив предупреждений
     * errors:{}                                                    // массив ошибок
     * status:1                                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInjury&method=SaveCasePb&subscribe=&data={"case_pb":{"case_pb_id":-1,"title":"CASE_PB_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 08:23
     */
    public static function SaveCasePb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveCasePb';
        $chat_type_data = array();																				// Промежуточный результирующий массив
        $warnings[] = $method_name.'. Начало метода';
        try
        {
            if ($data_post == NULL && $data_post == '')
            {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'case_pb'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $case_pb_id = $post_dec->case_pb->case_pb_id;
            $title = $post_dec->case_pb->title;
            $case_pb = CasePb::findOne(['id'=>$case_pb_id]);
            if (empty($case_pb)){
                $case_pb = new CasePb();
            }
            $case_pb->title = $title;
            if ($case_pb->save()){
                $case_pb->refresh();
                $chat_type_data['case_pb_id'] = $case_pb->id;
                $chat_type_data['title'] = $case_pb->title;
            }else{
                $errors[] = $case_pb->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового обстоятельства');
            }
            unset($case_pb);
        }
        catch (\Throwable $exception)
        {
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
     * Метод DeleteCasePb() - Удаление обстоятельства
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "case_pb_id": 40             // идентификатор удаляемого обстоятельства
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInjury&method=DeleteCasePb&subscribe=&data={"case_pb_id":40}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 08:24
     */
    public static function DeleteCasePb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteCasePb';
        $warnings[] = $method_name.'. Начало метода';
        try
        {
            if ($data_post == NULL && $data_post == '')
            {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'case_pb_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $case_pb_id = $post_dec->case_pb_id;
            $del_case_pb = CasePb::deleteAll(['id'=>$case_pb_id]);
        }
        catch (\Throwable $exception)
        {
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
     * Метод GetKindCrash() - Получение справочника видов аварий
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,					                    // идентификатор вида аварии
     *      "title":"Неконтролируемый взрыв"				// наименование вида аварии
     * ]
     * warnings:{}                                          // массив предупреждений
     * errors:{}                                            // массив ошибок
     * status:1                                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInjury&method=GetKindCrash&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 11:54
     */
    public static function GetKindCrash()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetKindCrash';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $kind_crash_data = KindCrash::find()
                ->asArray()
                ->all();
            if(empty($kind_crash_data)){
                $warnings[] = $method_name.'. Справочник видов аварий';
            }else{
                $result = $kind_crash_data;
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
     * Метод SaveKindCrash() - Сохранение нового вида аварии
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "kind_crash":
     *  {
     *      "kind_crash_id":-1,					                // идентификатор вида аварии (-1 = новый вид аварии)
     *      "title":"KIND_CRASH"				                // наименование вида аварии
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "kind_crash_id":5,					                // идентификатор сохранённого вида аварии
     *      "title":"KIND_CRASH"				                // сохранённое наименование вида аварии
     * }
     * warnings:{}                                              // массив предупреждений
     * errors:{}                                                // массив ошибок
     * status:1                                                 // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInjury&method=SaveKindCrash&subscribe=&data={"kind_crash":{"kind_crash_id":-1,"title":"KIND_CRASH"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 11:59
     */
    public static function SaveKindCrash($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveKindCrash';
        $chat_type_data = array();																				// Промежуточный результирующий массив
        $warnings[] = $method_name.'. Начало метода';
        try
        {
            if ($data_post == NULL && $data_post == '')
            {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_crash'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $kind_crash_id = $post_dec->kind_crash->kind_crash_id;
            $title = $post_dec->kind_crash->title;
            $kind_сrash = KindCrash::findOne(['id'=>$kind_crash_id]);
            if (empty($kind_сrash)){
                $kind_сrash = new KindCrash();
            }
            $kind_сrash->title = $title;
            if ($kind_сrash->save()){
                $kind_сrash->refresh();
                $chat_type_data['kind_crash_id'] = $kind_сrash->id;
                $chat_type_data['title'] = $kind_сrash->title;
            }else{
                $errors[] = $kind_сrash->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового вида аварии');
            }
            unset($kind_сrash);
        }
        catch (\Throwable $exception)
        {
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
     * Метод DeleteKindCrash() - Удаление типа аварии
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "kind_crash_id": 9             // идентификатор удаляемого вида аварии
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInjury&method=DeleteKindCrash&subscribe=&data={"kind_crash_id":9}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 11:59
     */
    public static function DeleteKindCrash($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteChatType';
        $warnings[] = $method_name.'. Начало метода';
        try
        {
            if ($data_post == NULL && $data_post == '')
            {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_crash_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $kind_crash_id = $post_dec->kind_crash_id;
            $del_kind_crash = KindCrash::deleteAll(['id'=>$kind_crash_id]);
        }
        catch (\Throwable $exception)
        {
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
     * Метод GetKindIncident() - Получение справочника видов инцидентов
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 13,					                            // идентификатор вида инцидента
     *      "title":"Повреждение технических устройств"				// наименование вида инцидента
     * ]
     * warnings:{}                                                  // массив предупреждений
     * errors:{}                                                    // массив ошибок
     * status:1                                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInjury&method=GetKindIncident&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 10:38
     */
    public static function GetKindIncident()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetKindIncident';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $kind_incident_data = KindIncident::find()
                ->asArray()
                ->all();
            if(empty($kind_incident_data)){
                $warnings[] = $method_name.'. Справочник видов инцидентов пуст';
            }else{
                $result = $kind_incident_data;
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
     * Метод SaveKindIncident() - Сохранение нового вида инцидента
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "kind_incident":
     *  {
     *      "kind_incident_id":-1,					            // идентификатор вида инцидента (-1 =  новый инцидент)
     *      "title":"KIND_INCIDENT"				                // наименование вида инцидента
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "kind_incident_id":17,					            // идентификатор сохранённого вида инцидента
     *      "title":"KIND_INCIDENT"				                // сохранённое наименование вида инцидента
     * }
     * warnings:{}                                              // массив предупреждений
     * errors:{}                                                // массив ошибок
     * status:1                                                 // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInjury&method=SaveKindIncident&subscribe=&data={"kind_incident":{"kind_incident_id":-1,"title":"KIND_INCIDENT"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 10:48
     */
    public static function SaveKindIncident($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveKindIncident';
        $chat_type_data = array();																				// Промежуточный результирующий массив
        $warnings[] = $method_name.'. Начало метода';
        try
        {
            if ($data_post == NULL && $data_post == '')
            {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_incident'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $kind_incident_id = $post_dec->kind_incident->kind_incident_id;
            $title = $post_dec->kind_incident->title;
            $kind_incident = KindIncident::findOne(['id'=>$kind_incident_id]);
            if (empty($kind_incident)){
                $kind_incident = new KindIncident();
            }
            $kind_incident->title = $title;
            if ($kind_incident->save()){
                $kind_incident->refresh();
                $chat_type_data['kind_incident_id'] = $kind_incident->id;
                $chat_type_data['title'] = $kind_incident->title;
            }else{
                $errors[] = $kind_incident->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового вида инцидента');
            }
            unset($kind_incident);
        }
        catch (\Throwable $exception)
        {
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
     * Метод DeleteKindIncident() - Удаление вида инцидента
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "kind_incident_id": 17             // идентификатор удаляемого вида инцидента
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInjury&method=DeleteKindIncident&subscribe=&data={"kind_incident_id":17}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 10:49
     */
    public static function DeleteKindIncident($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteKindIncident';
        $warnings[] = $method_name.'. Начало метода';
        try
        {
            if ($data_post == NULL && $data_post == '')
            {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_incident_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $kind_incident_id = $post_dec->kind_incident_id;
            $del_kind_incident = KindIncident::deleteAll(['id'=>$kind_incident_id]);
        }
        catch (\Throwable $exception)
        {
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
     * Метод GetKindMishap() - Получение справочника видов несчастных случаев
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,					                    // идентификатор вида несчастного случая
     *      "title":"Легкий нечастный случай"				// наименование вида несчастного случая
     * ]
     * warnings:{}                                          // массив предупреждений
     * errors:{}                                            // массив ошибок
     * status:1                                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInjury&method=GetKindMishap&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 11:38
     */
    public static function GetKindMishap()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetKindMishap';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $kind_mishap_data = KindMishap::find()
                ->asArray()
                ->all();
            if(empty($kind_mishap_data)){
                $warnings[] = $method_name.'. Справочник видов несчастных случаев пуст';
            }else{
                $result = $kind_mishap_data;
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
     * Метод SaveKindMishap() - Сохранение нового вида несчастного случая
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "kind_mishap":
     *  {
     *      "kind_mishap_id":-1,					                    // идентификатор вида несчастного случая (-1 =  новый вид несчастного случая)
     *      "title":"KIND_MISHAP_TEST"				                    // наименование вида несчастного случая
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "kind_mishap_id":7,					                        // идентификатор сохранённого вида несчастного случая
     *      "title":"KIND_MISHAP_TEST"				                    // сохранённое наименование вида несчастного случая
     * }
     * warnings:{}                                                      // массив предупреждений
     * errors:{}                                                        // массив ошибок
     * status:1                                                         // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInjury&method=SaveKindMishap&subscribe=&data={"kind_mishap":{"kind_mishap_id":-1,"title":"KIND_MISHAP_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 11:48
     */
    public static function SaveKindMishap($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveKindMishap';
        $chat_type_data = array();																				// Промежуточный результирующий массив
        $warnings[] = $method_name.'. Начало метода';
        try
        {
            if ($data_post == NULL && $data_post == '')
            {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_mishap'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $kind_mishap_id = $post_dec->kind_mishap->kind_mishap_id;
            $title = $post_dec->kind_mishap->title;
            $kind_mishap = KindMishap::findOne(['id'=>$kind_mishap_id]);
            if (empty($kind_mishap)){
                $kind_mishap = new KindMishap();
            }
            $kind_mishap->title = $title;
            if ($kind_mishap->save()){
                $kind_mishap->refresh();
                $chat_type_data['kind_mishap_id'] = $kind_mishap->id;
                $chat_type_data['title'] = $kind_mishap->title;
            }else{
                $errors[] = $kind_mishap->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового вида несчастного случая');
            }
            unset($kind_mishap);
        }
        catch (\Throwable $exception)
        {
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
     * Метод DeleteKindMishap() - Удаление вида несчастного случая
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "kind_mishap_id": 7             // идентификатор удаляемого вида несчастного случая
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInjury&method=DeleteKindMishap&subscribe=&data={"kind_mishap_id":7}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 11:49
     */
    public static function DeleteKindMishap($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteKindMishap';
        $warnings[] = $method_name.'. Начало метода';
        try
        {
            if ($data_post == NULL && $data_post == '')
            {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_mishap_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $kind_mishap_id = $post_dec->kind_mishap_id;
            $del_kind_mishap = KindMishap::deleteAll(['id'=>$kind_mishap_id]);
        }
        catch (\Throwable $exception)
        {
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
     * Метод GetOutcome() - Получение справочника исходов травм
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,					        // идентификатор исхода травмы
     *      "title":"Инвалидность"				// наименование исхода травмы
     * ]
     * warnings:{}                              // массив предупреждений
     * errors:{}                                // массив ошибок
     * status:1                                 // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInjury&method=GetOutcome&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.03.2020 11:12
     */
    public static function GetOutcome()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetOutcome';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $outcome = Outcome::find()
                ->asArray()
                ->all();
            if(empty($outcome)){
                $warnings[] = $method_name.'. Справочник исходов травм пуст';
            }else{
                $result = $outcome;
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
     * Метод SaveOutcome() - Сохранение нового исхода травмы
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "outcome":
     *  {
     *      "outcome_id":-1,					                    // идентификатор исхода травмы (-1 =  новый исход травмы)
     *      "title":"OUTCOME_TEST"				                    // наименование исхода травмы
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "outcome_id":4,					                    // идентификатор сохранённого исхода травмы
     *      "title":"OUTCOME_TEST"				                // сохранённое наименование исхода травмы
     * }
     * warnings:{}                                              // массив предупреждений
     * errors:{}                                                // массив ошибок
     * status:1                                                 // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInjury&method=SaveOutcome&subscribe=&data={"outcome":{"outcome_id":-1,"title":"OUTCOME_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.03.2020 11:14
     */
    public static function SaveOutcome($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveOutcome';
        $chat_type_data = array();																				// Промежуточный результирующий массив
        $warnings[] = $method_name.'. Начало метода';
        try
        {
            if ($data_post == NULL && $data_post == '')
            {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'outcome'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $outcome_id = $post_dec->outcome->outcome_id;
            $title = $post_dec->outcome->title;
            $kind_mishap = Outcome::findOne(['id'=>$outcome_id]);
            if (empty($kind_mishap)){
                $kind_mishap = new Outcome();
            }
            $kind_mishap->title = $title;
            if ($kind_mishap->save()){
                $kind_mishap->refresh();
                $chat_type_data['outcome_id'] = $kind_mishap->id;
                $chat_type_data['title'] = $kind_mishap->title;
            }else{
                $errors[] = $kind_mishap->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового вида несчастного случая');
            }
            unset($kind_mishap);
        }
        catch (\Throwable $exception)
        {
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
     * Метод DeleteOutcome() - Удаление исхода травмы
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "outcome_id": 4             // идентификатор удаляемого исхода травмы
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInjury&method=DeleteOutcome&subscribe=&data={"outcome_id":4}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.03.2020 11:20
     */
    public static function DeleteOutcome($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteOutcome';
        $warnings[] = $method_name.'. Начало метода';
        try
        {
            if ($data_post == NULL && $data_post == '')
            {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'outcome_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $outcome_id = $post_dec->outcome_id;
            $del_outcome = Outcome::deleteAll(['id'=>$outcome_id]);
        }
        catch (\Throwable $exception)
        {
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
