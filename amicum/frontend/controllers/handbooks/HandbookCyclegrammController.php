<?php

namespace frontend\controllers\handbooks;

use frontend\models\CyclegrammType;

class HandbookCyclegrammController extends \yii\web\Controller
{
    // GetCyclegrammType                            - Получение справочника типов циклограмм
    // SaveCyclegrammType                           - Сохранение нового типа циклограммы
    // DeleteCyclegrammType                         - Удаление типа циклограммы

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetCyclegrammType() - Получение справочника типов циклограмм
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,					    // идентификатор типа циклограммы
     *      "title":"Плановая"				// наименование типа циклограммы
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookCyclegramm&method=GetCyclegrammType&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 08:51
     */
    public static function GetCyclegrammType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetCyclegrammType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $cyclegramm_type_data = CyclegrammType::find()
                ->asArray()
                ->all();
            if(empty($cyclegramm_type_data)){
                $warnings[] = $method_name.'. Справочник типов циклограмм пуст';
            }else{
                $result = $cyclegramm_type_data;
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
     * Метод SaveCyclegrammType() - Сохранение нового типа циклограммы
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "cyclegramm_type":
     *  {
     *      "cyclegramm_type_id":-1,					                    // идентификатор типа циклограммы (-1 = при добавлении нового типа циклограммы)
     *      "title":"CYCLOGRAMM_TYPE_TEST"				                    // наименование типа циклограммы
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "cyclegramm_type_id":5,					                    // идентификатор сохранённого типа циклограммы
     *      "title":"CYCLOGRAMM_TYPE_TEST"				                // сохранённое наименование типа циклограммы
     *
     * }
     * warnings:{}                                                      // массив предупреждений
     * errors:{}                                                        // массив ошибок
     * status:1                                                         // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookCyclegramm&method=SaveCyclegrammType&subscribe=&data={"cyclegramm_type":{"cyclegramm_type_id":-1,"title":"CYCLOGRAMM_TYPE_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 08:54
     */
    public static function SaveCyclegrammType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveCyclegrammType';
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
            if (!property_exists($post_dec, 'cyclegramm_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $cyclegramm_type_id = $post_dec->cyclegramm_type->cyclegramm_type_id;
            $title = $post_dec->cyclegramm_type->title;
            $cyclegramm_type = CyclegrammType::findOne(['id'=>$cyclegramm_type_id]);
            if (empty($cyclegramm_type)){
                $cyclegramm_type = new CyclegrammType();
            }
            $cyclegramm_type->title = $title;
            if ($cyclegramm_type->save()){
                $cyclegramm_type->refresh();
                $chat_type_data['cyclegramm_type_id'] = $cyclegramm_type->id;
                $chat_type_data['title'] = $cyclegramm_type->title;
            }else{
                $errors[] = $cyclegramm_type->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового типа циклограммы');
            }
            unset($cyclegramm_type);
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
     * Метод DeleteCyclegrammType() - Удаление типа циклограммы
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "cyclegramm_type_id": 5             // идентификатор удаляемого типа циклограммы
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookCyclegramm&method=DeleteCyclegrammType&subscribe=&data={"cyclegramm_type_id":5}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 08:56
     */
    public static function DeleteCyclegrammType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteCyclegrammType';
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
            if (!property_exists($post_dec, 'cyclegramm_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $cyclegramm_type_id = $post_dec->cyclegramm_type_id;
            $del_cyclegramm_type = CyclegrammType::deleteAll(['id'=>$cyclegramm_type_id]);
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
