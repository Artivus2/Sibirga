<?php

namespace frontend\controllers\handbooks;

use frontend\controllers\Assistant;
use frontend\models\HandbookList;
use Yii;


class HandbookListController extends \yii\web\Controller
{
    // GetHandbookList()        - Получение справочника справочников
    // SaveHandbookList()       - Сохранение справочника справочников
    // DeleteHandbookList()     - Удаление справочника справочников


    /**
     * Метод GetHandbookList() - Получение справочника справочников
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,					    // ключ справочника
     *      "title":"ACTION",				// название справочника
     *      "description":"Описание",		// краткое описание справочника
     *      "url":"1"				        // путь до справочника
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookList&method=GetHandbookList&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetHandbookList()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetHandbookList';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_list_data = HandbookList::find()
                ->asArray()
                ->all();
            if(empty($handbook_list_data)){
                $warnings[] = $method_name.'. Справочник справочников пуст';
            }else{
                $result = $handbook_list_data;
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
     * Метод SaveHandbookList() - Сохранение справочника справочников
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "handbookList":
     *  {
     *      "handbook_list_id":-1,			// ключ справочника
     *      "title":"ACTION",				// название справочника
     *      "description":"Описание",		// краткое описание справочника
     *      "url":"1"				        // путь до справочника
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "handbook_list_id":-1,			// ключ справочника
     *      "title":"ACTION",				// название справочника
     *      "description":"Описание",		// краткое описание справочника
     *      "url":"1"				        // путь до справочника
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookList&method=SaveHandbookList&subscribe=&data={"handbookList":{"handbook_list_id":-1,"title":"ACTION","description":"Описание","url":"11"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveHandbookList($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveHandbookList';
        $handbook_list_data = array();																				// Промежуточный результирующий массив
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
            if (!property_exists($post_dec, 'handbookList'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $handbook_list_id = $post_dec->handbookList->handbook_list_id;
            $title = $post_dec->handbookList->title;
            $description = $post_dec->handbookList->description;
            $url = $post_dec->handbookList->url;
            $new_handbook_list = HandbookList::findOne(['id'=>$handbook_list_id]);
            if (empty($new_handbook_list)){
                $new_handbook_list = new HandbookList();
            }
            $new_handbook_list->title = $title;
            $new_handbook_list->description = $description;
            $new_handbook_list->url = $url;
            if ($new_handbook_list->save()){
                $new_handbook_list->refresh();
                $handbook_list_data['handbook_list_id'] = $new_handbook_list->id;
                $handbook_list_data['title'] = $new_handbook_list->title;
                $handbook_list_data['description'] = $new_handbook_list->description;
                $handbook_list_data['url'] = $new_handbook_list->url;
            }else{
                $errors[] = $new_handbook_list->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении справочника справочников');
            }
            unset($new_handbook_list);
        }
        catch (\Throwable $exception)
        {
            $errors[] = $method_name.'. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name.'. Конец метода';
        $result = $handbook_list_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteHandbookList() - Удаление справочника справочников
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "handbook_list_id": 98             // идентификатор справочника справочников
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookList&method=DeleteHandbookList&subscribe=&data={"handbook_list_id":98}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteHandbookList($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteHandbookList';
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
            if (!property_exists($post_dec, 'handbook_list_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $handbook_list_id = $post_dec->handbook_list_id;
            $del_handbook_list = HandbookList::deleteAll(['id'=>$handbook_list_id]);
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
