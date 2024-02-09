<?php

namespace frontend\controllers\handbooks;

use frontend\models\EdgeType;

class HandbookEdgeController extends \yii\web\Controller
{
    // GetEdgeType                  - Получение справочника типов выработок
    // SaveEdgeType                 - Сохранение нового типа выработки
    // DeleteEdgeType                - Удаление типа выработки




    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetEdgeType() - Получение справочника типов выработок
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,					    // идентификатор типов выработок
     *      "title":"Прочее"				// наименование типов выработок
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEdge&method=GetEdgeType&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 11:05
     */
    public static function GetEdgeType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetEdgeType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $edge_type_data = EdgeType::find()
                ->asArray()
                ->all();
            if(empty($edge_type_data)){
                $warnings[] = $method_name.'. Справочник типов выработок пуст';
            }else{
                $result = $edge_type_data;
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
     * Метод SaveEdgeType() - Сохранение нового типа выработки
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "edge_type":
     *  {
     *      "edge_type_id":-1,					            // идентификатор типа выработки (-1 = новый тип выработки)
     *      "title":"Причниа"				                // наименование типа выработки
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "edge_type_id":5,					            // идентификатор сохранённого типа выработки
     *      "title":"Причниа"				                // сохранённое наименование типа выработки
     * }
     * warnings:{}                                          // массив предупреждений
     * errors:{}                                            // массив ошибок
     * status:1                                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEdge&method=SaveEdgeType&subscribe=&data={"edge_type":{"edge_type_id":-1,"title":"EDGE_TYPE_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 10:45
     */
    public static function SaveEdgeType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveEdgeType';
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
            if (!property_exists($post_dec, 'edge_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $edge_type_id = $post_dec->edge_type->edge_type_id;
            $title = $post_dec->edge_type->title;
            $edge_type = EdgeType::findOne(['id'=>$edge_type_id]);
            if (empty($edge_type)){
                $edge_type = new EdgeType();
            }
            $edge_type->title = $title;
            if ($edge_type->save()){
                $edge_type->refresh();
                $chat_type_data['edge_type_id'] = $edge_type->id;
                $chat_type_data['title'] = $edge_type->title;
            }else{
                $errors[] = $edge_type->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового типа выработки');
            }
            unset($edge_type);
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
     * Метод DeleteEdgeType() - Удаление типа выработки
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "edge_type_id": 56             // идентификатор удаляемого типа выработки
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEdge&method=DeleteEdgeType&subscribe=&data={"edge_type_id":56}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 10:49
     */
    public static function DeleteEdgeType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeletEdgeType';
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
            if (!property_exists($post_dec, 'edge_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $edge_type_id = $post_dec->edge_type_id;
            $del_edge_type = EdgeType::deleteAll(['id'=>$edge_type_id]);
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
