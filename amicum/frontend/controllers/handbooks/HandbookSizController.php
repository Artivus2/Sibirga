<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers\handbooks;

use Exception;
use frontend\models\Season;
use frontend\models\Siz;
use frontend\models\SizGroup;
use frontend\models\SizKind;
use frontend\models\SizSubgroup;
use Throwable;
use yii\web\Controller;

class HandbookSizController extends Controller
{
    // GetSeason                            - Получение справочника сезонов Объектом
    // GetSeasonArray                       - Получение справочника сезонов Массивом
    // SaveSeason                           - Сохранение нового сезона
    // DeleteSeason                         - Удаление сезона

    // GetSiz()                             - Получение справочника СИЗ
    // SaveSiz()                            - Сохранение справочника СИЗ
    // DeleteSiz()                          - Удаление справочника СИЗ

    // GetSizGroup()                        - Получение справочника групп СИЗ
    // SaveSizGroup()                       - Сохранение справочника групп СИЗ
    // DeleteSizGroup()                     - Удаление справочника групп СИЗ

    // GetSizSubgroup()                     - Получение справочника подгруппы СИЗ
    // SaveSizSubgroup()                    - Сохранение справочника подгруппы СИЗ
    // DeleteSizSubgroup()                  - Удаление справочника подгруппы СИЗ

    // GetSizKind()                         - Получение справочника видов СИЗ
    // SaveSizKind()                        - Сохранение справочника видов СИЗ
    // DeleteSizKind()                      - Удаление справочника видов СИЗ

    public function actionIndex()
    {
        return $this->render('index');
    }


    /**
     * Метод GetSeason() - Получение справочника сезонов
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookSiz&method=GetSeason&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.03.2020 08:53
     */
    public static function GetSeason()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetSeason';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $seasons = Season::find()
                ->indexBy('id')
                ->all();
            if (empty($seasons)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник сезонов пуст';
            } else {
                $result = $seasons;
            }
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
     * Метод GetSeasonArray() - Получение справочника сезонов массивом
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookSiz&method=GetSeasonArray&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 30.03.2020 08:53
     */
    public static function GetSeasonArray()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetSeasonArray';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $seasons = Season::find()
                ->asArray()
                ->all();
            if (!empty($seasons)) {
                $result = $seasons;
            }
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
     * Метод SaveSeason() - Сохранение нового сезона
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookSiz&method=SaveSeason&subscribe=&data={"season":{"season_id":-1,"title":"SEASON_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.03.2020 09:00
     */
    public static function SaveSeason($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveSeason';
        $chat_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'season'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $place_type_id = $post_dec->season->season_id;
            $title = $post_dec->season->title;
            $season = Season::findOne(['id' => $place_type_id]);
            if (empty($season)) {
                $season = new Season();
            }
            $season->title = $title;
            if ($season->save()) {
                $season->refresh();
                $chat_type_data['season_id'] = $season->id;
                $chat_type_data['title'] = $season->title;
            } else {
                $errors[] = $season->errors;
                throw new Exception($method_name . '. Ошибка при сохранении нового сезона');
            }
            unset($season);
        } catch (Throwable $exception) {
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
     * Метод DeleteSeason() - Удаление сезона
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookSiz&method=DeleteSeason&subscribe=&data={"season_id":6}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.03.2020 09:05
     */
    public static function DeleteSeason($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteSeason';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'season_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $season_id = $post_dec->season_id;
            $del_season = Season::deleteAll(['id' => $season_id]);
        } catch (Throwable $exception) {
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


    // GetSiz()      - Получение справочника СИЗ
    // SaveSiz()     - Сохранение справочника СИЗ
    // DeleteSiz()   - Удаление справочника СИЗ

    /**
     * Метод GetSiz() - Получение справочника СИЗ
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                            // ключ справочника СИЗ
     *      "title":"ACTION",                   // название СИЗ
     *      "unit_id":"-1",                     // ключ единицы измерения
     *      "wear_period":"36",                 // срок носки
     *      "season_id":"-1",                   // ключ сезона носки
     *      "comment":"павраврп",               // комментарий
     *      "siz_kind_id":"-1",                 // ключ вида СИЗ
     *      "document_id":"-1",                 // ключ документа/приказа по которому выдается СИЗ - обычно ГОСТ или норма
     *      "siz_subgroup_id":"-1",             // ключ подгруппы СИЗ
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=GetSiz&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetSiz()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetSiz';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_siz = Siz::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_siz)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник СИЗ пуст';
            } else {
                $result = $handbook_siz;
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
     * Метод SaveSiz() - Сохранение справочника СИЗ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "siz":
     *  {
     *      "siz_id":-1,                        // ключ справочника СИЗ
     *      "title":"ACTION",                   // название СИЗ
     *      "unit_id":"-1",                     // ключ единицы измерения
     *      "wear_period":"36",                 // срок носки
     *      "season_id":"-1",                   // ключ сезона носки
     *      "comment":"павраврп",               // комментарий
     *      "siz_kind_id":"-1",                 // ключ вида СИЗ
     *      "document_id":"-1",                 // ключ документа/приказа по которому выдается СИЗ - обычно ГОСТ или норма
     *      "siz_subgroup_id":"-1",             // ключ подгруппы СИЗ
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "siz_id":-1,                        // ключ справочника СИЗ
     *      "title":"ACTION",                   // название СИЗ
     *      "unit_id":"-1",                     // ключ единицы измерения
     *      "wear_period":"36",                 // срок носки
     *      "season_id":"-1",                   // ключ сезона носки
     *      "comment":"павраврп",               // комментарий
     *      "siz_kind_id":"-1",                 // ключ вида СИЗ
     *      "document_id":"-1",                 // ключ документа/приказа по которому выдается СИЗ - обычно ГОСТ или норма
     *      "siz_subgroup_id":"-1",             // ключ подгруппы СИЗ
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=SaveSiz&subscribe=&data={"siz":{}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveSiz($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveSiz';
        $handbook_siz_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'siz'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_siz_id = $post_dec->siz->siz_id;
            $title = $post_dec->siz->title;
            $unit_id = $post_dec->siz->unit_id;
            $wear_period = $post_dec->siz->wear_period;
            $season_id = $post_dec->siz->season_id;
            $comment = $post_dec->siz->comment;
            $siz_kind_id = $post_dec->siz->siz_kind_id;
            $document_id = $post_dec->siz->document_id;
            $siz_subgroup_id = $post_dec->siz->siz_subgroup_id;
            $new_handbook_siz_id = Siz::findOne(['id' => $handbook_siz_id]);
            if (empty($new_handbook_siz_id)) {
                $new_handbook_siz_id = new Siz();
            }
            $new_handbook_siz_id->title = $title;
            $new_handbook_siz_id->unit_id = $unit_id;
            $new_handbook_siz_id->wear_period = $wear_period;
            $new_handbook_siz_id->season_id = $season_id;
            $new_handbook_siz_id->comment = $comment;
            $new_handbook_siz_id->siz_kind_id = $siz_kind_id;
            $new_handbook_siz_id->document_id = $document_id;
            $new_handbook_siz_id->siz_subgroup_id = $siz_subgroup_id;
            if ($new_handbook_siz_id->save()) {
                $new_handbook_siz_id->refresh();
                $handbook_siz_data['siz_id'] = $new_handbook_siz_id->id;
                $handbook_siz_data['title'] = $new_handbook_siz_id->title;
                $handbook_siz_data['unit_id'] = $new_handbook_siz_id->unit_id;
                $handbook_siz_data['wear_period'] = $new_handbook_siz_id->wear_period;
                $handbook_siz_data['season_id'] = $new_handbook_siz_id->season_id;
                $handbook_siz_data['comment'] = $new_handbook_siz_id->comment;
                $handbook_siz_data['siz_kind_id'] = $new_handbook_siz_id->siz_kind_id;
                $handbook_siz_data['document_id'] = $new_handbook_siz_id->document_id;
                $handbook_siz_data['siz_subgroup_id'] = $new_handbook_siz_id->siz_subgroup_id;
            } else {
                $errors[] = $new_handbook_siz_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника СИЗ');
            }
            unset($new_handbook_siz_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_siz_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteSiz() - Удаление справочника СИЗ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "siz_id": 98             // идентификатор справочника СИЗ
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=DeleteSiz&subscribe=&data={"siz_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteSiz($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteSiz';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'siz_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_siz_id = $post_dec->siz_id;
            $del_handbook_siz = Siz::deleteAll(['id' => $handbook_siz_id]);
        } catch (Throwable $exception) {
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


    // GetSizGroup()      - Получение справочника групп СИЗ
    // SaveSizGroup()     - Сохранение справочника групп СИЗ
    // DeleteSizGroup()   - Удаление справочника групп СИЗ

    /**
     * Метод GetSizGroup() - Получение справочника групп СИЗ
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                         // ключ справочника групп СИЗ
     *      "title":"ACTION",                // название группы СИЗ
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=GetSizGroup&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetSizGroup()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetSizGroup';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_siz_group = SizGroup::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_siz_group)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник групп СИЗ пуст';
            } else {
                $result = $handbook_siz_group;
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
     * Метод SaveSizGroup() - Сохранение справочника групп СИЗ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "siz_group":
     *  {
     *      "siz_group_id":-1,              // ключ справочника группы СИЗ
     *      "title":"ACTION",               // название группы СИЗ
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "siz_group_id":-1,              // ключ справочника группы СИЗ
     *      "title":"ACTION",               // название группы СИЗ
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=SaveSizGroup&subscribe=&data={"siz_group":{"siz_group_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveSizGroup($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveSizGroup';
        $handbook_siz_group_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'siz_group'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_siz_group_id = $post_dec->siz_group->siz_group_id;
            $title = $post_dec->siz_group->title;
            $new_handbook_siz_group_id = SizGroup::findOne(['id' => $handbook_siz_group_id]);
            if (empty($new_handbook_siz_group_id)) {
                $new_handbook_siz_group_id = new SizGroup();
            }
            $new_handbook_siz_group_id->title = $title;
            if ($new_handbook_siz_group_id->save()) {
                $new_handbook_siz_group_id->refresh();
                $handbook_siz_group_data['siz_group_id'] = $new_handbook_siz_group_id->id;
                $handbook_siz_group_data['title'] = $new_handbook_siz_group_id->title;
            } else {
                $errors[] = $new_handbook_siz_group_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника групп СИЗ');
            }
            unset($new_handbook_siz_group_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_siz_group_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteSizGroup() - Удаление справочника групп СИЗ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "siz_group_id": 98             // идентификатор справочника групп СИЗ
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=DeleteSizGroup&subscribe=&data={"siz_group_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteSizGroup($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteSizGroup';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'siz_group_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_siz_group_id = $post_dec->siz_group_id;
            $del_handbook_siz_group = SizGroup::deleteAll(['id' => $handbook_siz_group_id]);
        } catch (Throwable $exception) {
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


    // GetSizSubgroup()      - Получение справочника подгруппы СИЗ
    // SaveSizSubgroup()     - Сохранение справочника подгруппы СИЗ
    // DeleteSizSubgroup()   - Удаление справочника подгруппы СИЗ

    /**
     * Метод GetSizSubgroup() - Получение справочника подгруппы СИЗ
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                        // ключ подгруппы СИЗ
     *      "title":"ACTION",               // название подгруппы СИЗ
     *      "siz_group_id":"-1",            // ключ группы СИЗ
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=GetSizSubgroup&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetSizSubgroup()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetSizSubgroup';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_siz_subgroup = SizSubgroup::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_siz_subgroup)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник подгруппы СИЗ пуст';
            } else {
                $result = $handbook_siz_subgroup;
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
     * Метод SaveSizSubgroup() - Сохранение справочника подгруппы СИЗ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "siz_subgroup":
     *  {
     *      "siz_subgroup_id":-1,           // ключ подгруппы СИЗ
     *      "title":"ACTION",               // название подгруппы СИЗ
     *      "siz_group_id":"-1",            // ключ группы СИЗ
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "siz_subgroup_id":-1,           // ключ подгруппы СИЗ
     *      "title":"ACTION",               // название подгруппы СИЗ
     *      "siz_group_id":"-1",            // ключ группы СИЗ
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=SaveSizSubgroup&subscribe=&data={"siz_subgroup":{"siz_subgroup_id":-1,"title":"ACTION","siz_group_id":"-1"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveSizSubgroup($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveSizSubgroup';
        $handbook_siz_subgroup_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'siz_subgroup'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_siz_subgroup_id = $post_dec->siz_subgroup->siz_subgroup_id;
            $title = $post_dec->siz_subgroup->title;
            $siz_group_id = $post_dec->siz_subgroup->siz_group_id;
            $new_handbook_siz_subgroup_id = SizSubgroup::findOne(['id' => $handbook_siz_subgroup_id]);
            if (empty($new_handbook_siz_subgroup_id)) {
                $new_handbook_siz_subgroup_id = new SizSubgroup();
            }
            $new_handbook_siz_subgroup_id->title = $title;
            $new_handbook_siz_subgroup_id->siz_group_id = $siz_group_id;
            if ($new_handbook_siz_subgroup_id->save()) {
                $new_handbook_siz_subgroup_id->refresh();
                $handbook_siz_subgroup_data['siz_subgroup_id'] = $new_handbook_siz_subgroup_id->id;
                $handbook_siz_subgroup_data['title'] = $new_handbook_siz_subgroup_id->title;
                $handbook_siz_subgroup_data['siz_group_id'] = $new_handbook_siz_subgroup_id->siz_group_id;
            } else {
                $errors[] = $new_handbook_siz_subgroup_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника подгруппы СИЗ');
            }
            unset($new_handbook_siz_subgroup_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_siz_subgroup_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteSizSubgroup() - Удаление справочника подгруппы СИЗ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "siz_subgroup_id": 98             // идентификатор справочника подгруппы СИЗ
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=DeleteSizSubgroup&subscribe=&data={"siz_subgroup_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteSizSubgroup($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteSizSubgroup';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'siz_subgroup_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_siz_subgroup_id = $post_dec->siz_subgroup_id;
            $del_handbook_siz_subgroup = SizSubgroup::deleteAll(['id' => $handbook_siz_subgroup_id]);
        } catch (Throwable $exception) {
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


    // GetSizKind()      - Получение справочника видов СИЗ
    // SaveSizKind()     - Сохранение справочника видов СИЗ
    // DeleteSizKind()   - Удаление справочника видов СИЗ

    /**
     * Метод GetSizKind() - Получение справочника видов СИЗ
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                        // ключ справочника вида СИЗ
     *      "title":"ACTION",               // название вида СИЗ
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=GetSizKind&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetSizKind()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetSizKind';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_siz_kind = SizKind::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_siz_kind)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник видов СИЗ пуст';
            } else {
                $result = $handbook_siz_kind;
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
     * Метод SaveSizKind() - Сохранение справочника видов СИЗ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "siz_kind":
     *  {
     *      "siz_kind_id":-1,               // ключ справочника вида СИЗ
     *      "title":"ACTION",               // название вида СИЗ
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "siz_kind_id":-1,               // ключ справочника вида СИЗ
     *      "title":"ACTION",               // название вида СИЗ
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=SaveSizKind&subscribe=&data={"siz_kind":{"siz_kind_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveSizKind($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveSizKind';
        $handbook_siz_kind_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'siz_kind'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_siz_kind_id = $post_dec->siz_kind->siz_kind_id;
            $title = $post_dec->siz_kind->title;
            $new_handbook_siz_kind_id = SizKind::findOne(['id' => $handbook_siz_kind_id]);
            if (empty($new_handbook_siz_kind_id)) {
                $new_handbook_siz_kind_id = new SizKind();
            }
            $new_handbook_siz_kind_id->title = $title;
            if ($new_handbook_siz_kind_id->save()) {
                $new_handbook_siz_kind_id->refresh();
                $handbook_siz_kind_data['siz_kind_id'] = $new_handbook_siz_kind_id->id;
                $handbook_siz_kind_data['title'] = $new_handbook_siz_kind_id->title;
            } else {
                $errors[] = $new_handbook_siz_kind_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника видов СИЗ');
            }
            unset($new_handbook_siz_kind_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_siz_kind_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteSizKind() - Удаление справочника видов СИЗ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "siz_kind_id": 98             // идентификатор справочника видов СИЗ
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=DeleteSizKind&subscribe=&data={"siz_kind_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteSizKind($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteSizKind';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'siz_kind_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_siz_kind_id = $post_dec->siz_kind_id;
            $del_handbook_siz_kind = SizKind::deleteAll(['id' => $handbook_siz_kind_id]);
        } catch (Throwable $exception) {
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
