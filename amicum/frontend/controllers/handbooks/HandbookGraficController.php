<?php

namespace frontend\controllers\handbooks;

use frontend\models\KindWorkingTime;
use frontend\models\ChaneType;
use frontend\models\WorkingTime;

class HandbookGraficController extends \yii\web\Controller
{
    // GetKindWorkingTime                       - Получение справочника видов рабочего времени
    // SaveKindWorkingTime                      - Сохранение нового вида рабочего времени
    // DeleteKindWorkingTime                    - Удаление вида рабочего времени

    // GetChaneType()      - Получение справочника типов звеньев
    // SaveChaneType()     - Сохранение справочника типов звеньев
    // DeleteChaneType()   - Удаление справочника типов звеньев

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetKindWorkingTime() - Получение справочника видов рабочего времени
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                            // ключ справочника
     *      "title":"ACTION",                   // название вида рабочего времени
     *      "short_title":"AC",                 // сокращенное название вида рабочего времени (2 символа)
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookGrafic&method=GetKindWorkingTime&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetKindWorkingTime()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetKindWorkingTime';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_kind_working_time = KindWorkingTime::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_kind_working_time)) {
                $result = (object) array();
                $warnings[] = $method_name . '. Справочник видов рабочего времени пуст';
            } else {
                $result = $handbook_kind_working_time;
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
     * Метод GetWorkingTime() - Получение справочника рабочего времени
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                            // ключ справочника
     *      "title":"ACTION",                   // название вида рабочего времени
     *      "short_title":"AC",                 // сокращенное название вида рабочего времени (2 символа)
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookGrafic&method=GetWorkingTime&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetWorkingTime()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetWorkingTime';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_working_time = WorkingTime::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_working_time)) {
                $result = (object) array();
                $warnings[] = $method_name . '. Справочник рабочего времени пуст';
            } else {
                $result = $handbook_working_time;
            }
        } catch (\Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SaveKindWorkingTime() - Сохранение справочника видов рабочего времени
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "kind_working_time":
     *  {
     *      "kind_working_time_id":-1,          // ключ справочника
     *      "title":"ACTION",                   // название вида рабочего времени
     *      "short_title":"AC",                 // сокращенное название вида рабочего времени (2 символа)
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "kind_working_time_id":-1,          // ключ справочника
     *      "title":"ACTION",                   // название вида рабочего времени
     *      "short_title":"AC",                 // сокращенное название вида рабочего времени (2 символа)
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookGrafic&method=SaveKindWorkingTime&subscribe=&data={"kind_working_time":{"kind_working_time_id":-1,"title":"ACTION","short_title":"AC"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveKindWorkingTime($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveKindWorkingTime';
        $handbook_kind_working_time_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_working_time'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_kind_working_time_id = $post_dec->kind_working_time->kind_working_time_id;
            $title = $post_dec->kind_working_time->title;
            $short_title = $post_dec->kind_working_time->short_title;
            $new_handbook_kind_working_time_id = KindWorkingTime::findOne(['id' => $handbook_kind_working_time_id]);
            if (empty($new_handbook_kind_working_time_id)) {
                $new_handbook_kind_working_time_id = new KindWorkingTime();
            }
            $new_handbook_kind_working_time_id->title = $title;
            $new_handbook_kind_working_time_id->short_title = $short_title;
            if ($new_handbook_kind_working_time_id->save()) {
                $new_handbook_kind_working_time_id->refresh();
                $handbook_kind_working_time_data['kind_working_time_id'] = $new_handbook_kind_working_time_id->id;
                $handbook_kind_working_time_data['title'] = $new_handbook_kind_working_time_id->title;
                $handbook_kind_working_time_data['short_title'] = $new_handbook_kind_working_time_id->short_title;
            } else {
                $errors[] = $new_handbook_kind_working_time_id->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении справочника видов рабочего времени');
            }
            unset($new_handbook_kind_working_time_id);
        } catch (\Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_kind_working_time_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteKindWorkingTime() - Удаление справочника видов рабочего времени
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "kind_working_time_id": 98             // идентификатор справочника видов рабочего времени
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookGrafic&method=DeleteKindWorkingTime&subscribe=&data={"kind_working_time_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteKindWorkingTime($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteKindWorkingTime';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_working_time_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_kind_working_time_id = $post_dec->kind_working_time_id;
            $del_handbook_kind_working_time = KindWorkingTime::deleteAll(['id' => $handbook_kind_working_time_id]);
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


    // GetChaneType()      - Получение справочника типов звеньев
    // SaveChaneType()     - Сохранение справочника типов звеньев
    // DeleteChaneType()   - Удаление справочника типов звеньев

    /**
     * Метод GetChaneType() - Получение справочника типов звеньев
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                            // ключ типа звена
     *      "title":"ACTION",                   // название типа звена
     *      "type":"1/2",                       // уровень типа звена (может быть или 1 или 2)
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookGrafic&method=GetChaneType&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetChaneType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetChaneType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_chane_type = ChaneType::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_chane_type)) {
                $result = (object) array();
                $warnings[] = $method_name . '. Справочник типов звеньев пуст';
            } else {
                $result = $handbook_chane_type;
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
     * Метод SaveChaneType() - Сохранение справочника типов звеньев
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "chane_type":
     *  {
     *      "chane_type_id":-1,                 // ключ типа звена
     *      "title":"ACTION",                   // название типа звена
     *      "type":"1/2",                       // уровень типа звена (может быть или 1 или 2)
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "chane_type_id":-1,                 // ключ типа звена
     *      "title":"ACTION",                   // название типа звена
     *      "type":"1/2",                       // уровень типа звена (может быть или 1 или 2)
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookGrafic&method=SaveChaneType&subscribe=&data={"chane_type":{"chane_type_id":-1,"title":"ACTION","type":"1"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveChaneType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveChaneType';
        $handbook_chane_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'chane_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_chane_type_id = $post_dec->chane_type->chane_type_id;
            $title = $post_dec->chane_type->title;
            $type = $post_dec->chane_type->type;
            $new_handbook_chane_type_id = ChaneType::findOne(['id' => $handbook_chane_type_id]);
            if (empty($new_handbook_chane_type_id)) {
                $new_handbook_chane_type_id = new ChaneType();
            }
            $new_handbook_chane_type_id->title = $title;
            $new_handbook_chane_type_id->type = $type;
            if ($new_handbook_chane_type_id->save()) {
                $new_handbook_chane_type_id->refresh();
                $handbook_chane_type_data['chane_type_id'] = $new_handbook_chane_type_id->id;
                $handbook_chane_type_data['title'] = $new_handbook_chane_type_id->title;
                $handbook_chane_type_data['type'] = $new_handbook_chane_type_id->type;
            } else {
                $errors[] = $new_handbook_chane_type_id->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении справочника типов звеньев');
            }
            unset($new_handbook_chane_type_id);
        } catch (\Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_chane_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteChaneType() - Удаление справочника типов звеньев
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "chane_type_id": 98             // идентификатор справочника типов звеньев
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookGrafic&method=DeleteChaneType&subscribe=&data={"chane_type_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteChaneType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteChaneType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'chane_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_chane_type_id = $post_dec->chane_type_id;
            $del_handbook_chane_type = ChaneType::deleteAll(['id' => $handbook_chane_type_id]);
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
