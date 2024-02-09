<?php

namespace frontend\controllers\handbooks;

use frontend\models\CheckingType;

class HandbookCheckingController extends \yii\web\Controller
{
    // GetCheckingType                      - Получение справочника типов проверок
    // SaveCheckingType                     - Сохранение нового типа проверки
    // DeleteCheckingType                   - Удаление типа проверки


    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetCheckingType() - Получение справочника типов проверок
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,					    // идентификатор типа проверки
     *      "title":"Плановая"				// наименование типа проверки
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookChecking&method=GetCheckingType&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 08:30
     */
    public static function GetCheckingType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetCheckingType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $checking_type_data = CheckingType::find()
                ->asArray()
                ->all();
            if(empty($checking_type_data)){
                $warnings[] = $method_name.'. Справочник типов проверок пуст';
            }else{
                $result = $checking_type_data;
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
     * Метод SaveCheckingType() - Сохранение нового типа проверки
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "checking_type":
     *  {
     *      "checking_type_id":-1,					                    // идентификатор типа проверки (-1 = при добавлении нового типа проверки)
     *      "title":"CHECKING_TYPE_TEST"				                // наименование типа проверки
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "checking_type_id":5,					                    // идентификатор сохранённого типа проверки
     *      "title":"CHECKING_TYPE_TEST"				                // сохранённое наименование типа проверки
     *
     * }
     * warnings:{}                                                      // массив предупреждений
     * errors:{}                                                        // массив ошибок
     * status:1                                                         // статус выполнения методас
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookChecking&method=SaveCheckingType&subscribe=&data={"checking_type":{"checking_type_id":-1,"title":"CHECKING_TYPE_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 08:35
     */
    public static function SaveCheckingType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveCheckingType';
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
            if (!property_exists($post_dec, 'checking_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $checking_type_id = $post_dec->checking_type->checking_type_id;
            $title = $post_dec->checking_type->title;
            $checking_type = CheckingType::findOne(['id'=>$checking_type_id]);
            if (empty($checking_type)){
                $checking_type = new CheckingType();
            }
            $checking_type->title = $title;
            if ($checking_type->save()){
                $checking_type->refresh();
                $chat_type_data['checking_type_id'] = $checking_type->id;
                $chat_type_data['title'] = $checking_type->title;
            }else{
                $errors[] = $checking_type->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового типа провреки');
            }
            unset($checking_type);
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
     * Метод DeleteCheckingType() - Удаление типа проверки
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "checking_type_id": 5             // идентификатор удаляемого типа проверки
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookChecking&method=DeleteCheckingType&subscribe=&data={"checking_type_id":5}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 08:38
     */
    public static function DeleteCheckingType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteCheckingType';
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
            if (!property_exists($post_dec, 'checking_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $checking_type_id = $post_dec->checking_type_id;
            $del_checking_type = CheckingType::deleteAll(['id'=>$checking_type_id]);
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
