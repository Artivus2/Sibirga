<?php

namespace frontend\controllers\handbooks;

use frontend\models\KindRepair;

class HandbookRepairMapController extends \yii\web\Controller
{
    // GetKindRepair                    - Получение справочника видов ремонтов
    // SaveKindRepair                   - Сохранение нового вида ремонта
    // DeleteKindRepair                 - Удаление вида ремонта


    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetKindRepair() - Получение справочника видов ремонтов
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,					                                // идентификатор вида ремонта
     *      "title":"Административная приостановка работ"				// наименование вида ремонта
     * ]
     * warnings:{}                                                      // массив предупреждений
     * errors:{}                                                        // массив ошибок
     * status:1                                                         // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookRepairMap&method=GetKindRepair&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 16:02
     */
    public static function GetKindRepair()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetKindRepair';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $kind_repair = KindRepair::find()
                ->asArray()
                ->all();
            if(empty($kind_repair)){
                $warnings[] = $method_name.'. Справочник видов ремонтов пуст';
            }else{
                $result = $kind_repair;
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
     * Метод SaveKindRepair() - Сохранение нового вида ремонта
     * @param null $data_post
     * @return array
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "kind_repair":
     *  {
     *      "kind_repair_id":-1,					                    // идентификатор вида ремонта (-1 =  новый вид ремонта)
     *      "title":"KIND_REPAIR_TEST"				                    // наименование вида ремонта
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "kind_repair_id":2,					                        // идентификатор сохранённого вида ремонта
     *      "title":"KIND_REPAIR_TEST"				                    // сохранённое наименование вида ремонта
     * }
     * warnings:{}                                                      // массив предупреждений
     * errors:{}                                                        // массив ошибок
     * status:1                                                         // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookRepairMap&method=SaveKindRepair&subscribe=&data={"kind_repair":{"kind_repair_id":-1,"title":"KIND_REPAIR_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 16:05
     */
    public static function SaveKindRepair($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveKindRepair';
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
            if (!property_exists($post_dec, 'kind_repair'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $kind_repair_id = $post_dec->kind_repair->kind_repair_id;
            $title = $post_dec->kind_repair->title;
            $kind_repair = KindRepair::findOne(['id'=>$kind_repair_id]);
            if (empty($kind_repair)){
                $kind_repair = new KindRepair();
            }
            $kind_repair->title = $title;
            if ($kind_repair->save()){
                $kind_repair->refresh();
                $chat_type_data['kind_repair_id'] = $kind_repair->id;
                $chat_type_data['title'] = $kind_repair->title;
            }else{
                $errors[] = $kind_repair->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового вида ремонта');
            }
            unset($kind_repair);
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
     * Метод DeleteKindRepair() - Удаление вида ремонта
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "kind_repair_id": 2                 // идентификатор удаляемого вида ремонта
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookRepairMap&method=DeleteKindRepair&subscribe=&data={"kind_repair_id":2}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 16:09
     */
    public static function DeleteKindRepair($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteKindRepair';
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
            if (!property_exists($post_dec, 'kind_repair_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $kind_repair_id = $post_dec->kind_repair_id;
            $del_kind_repair = KindRepair::deleteAll(['id'=>$kind_repair_id]);
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
