<?php

namespace frontend\controllers\handbooks;

use frontend\models\MoDopusk;
use frontend\models\MoResult;

class HandbookESMOController extends \yii\web\Controller
{
    // GetESMOAllowance                             - Получение справочника допусков ЭСМО
    // SaveESMOAllowance                            - Сохранение нового допуска ЭСМО
    // DeleteESMOAllowance                          - Удаление допуска ЭСМО
    // GetESMOResult                                - Получение справочника результатов ЭСМО
    // SaveESMOResult                               - Сохранение нового результата ЭСМО
    // DeleteESMOResult                             - Удаление результата ЭСМО


    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetESMOAllowance() - Получение справочника допусков ЭСМО
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,					    // идентификатор допуска ЭСМО
     *      "title":"Допуск"				// наименование допуска ЭСМО
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookESMO&method=GetESMOAllowance&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.03.2020 10:23
     */
    public static function GetESMOAllowance()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetESMOAllowance';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $esmo_allowance = MoDopusk::find()
                ->asArray()
                ->all();
            if(empty($esmo_allowance)){
                $warnings[] = $method_name.'. Справочник допусков ЭСМО пуст';
            }else{
                $result = $esmo_allowance;
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
     * Метод SaveESMOAllowance() - Сохранение нового допуска ЭСМО
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "mo_dopusk":
     *  {
     *      "mo_dopusk_id":-1,					                    // идентификатор допуска ЭСМО (-1 =  новый допуск ЭСМО)
     *      "title":"MO_DOPUSK_TEST"				                // наименование допуска ЭСМО
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "mo_dopusk_id":11,					                    // идентификатор сохранённого допуска ЭСМО
     *      "title":"MO_DOPUSK_TEST"				                // сохранённое наименование допуска ЭСМО
     * }
     * warnings:{}                                                  // массив предупреждений
     * errors:{}                                                    // массив ошибок
     * status:1                                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookESMO&method=SaveESMOAllowance&subscribe=&data={"mo_dopusk":{"mo_dopusk_id":-1,"title":"MO_DOPUSK_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.03.2020 10:25
     */
    public static function SaveESMOAllowance($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveESMOAllowance';
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
            if (!property_exists($post_dec, 'mo_dopusk'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $mo_dopusk_id = $post_dec->mo_dopusk->mo_dopusk_id;
            $title = $post_dec->mo_dopusk->title;
            $esmo_allowance = MoDopusk::findOne(['id'=>$mo_dopusk_id]);
            if (empty($esmo_allowance)){
                $esmo_allowance = new MoDopusk();
            }
            $esmo_allowance->title = $title;
            if ($esmo_allowance->save()){
                $esmo_allowance->refresh();
                $chat_type_data['mo_dopusk_id'] = $esmo_allowance->id;
                $chat_type_data['title'] = $esmo_allowance->title;
            }else{
                $errors[] = $esmo_allowance->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового допуска ЭСМО');
            }
            unset($esmo_allowance);
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
     * Метод DeleteESMOAllowance() - Удаление допуска ЭСМО
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "mo_dopusk_id": 11             // идентификатор удаляемого допуска ЭСМО
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookESMO&method=DeleteESMOAllowance&subscribe=&data={"mo_dopusk_id":11}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.03.2020 10:28
     */
    public static function DeleteESMOAllowance($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteESMOAllowance';
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
            if (!property_exists($post_dec, 'mo_dopusk_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $esmo_allowance_id = $post_dec->mo_dopusk_id;
            $del_esmo_allowance = MoDopusk::deleteAll(['id'=>$esmo_allowance_id]);
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
     * Метод GetESMOResult() - Получение справочника результатов ЭСМО
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,					            // идентификатор результата ЭСМО
     *      "title":"Результат ЭСМО"				// наименование результата ЭСМО
     * ]
     * warnings:{}                                  // массив предупреждений
     * errors:{}                                    // массив ошибок
     * status:1                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookESMO&method=GetESMOResult&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.03.2020 10:35
     */
    public static function GetESMOResult()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetESMOResult';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $esmo_result = MoResult::find()
                ->asArray()
                ->all();
            if(empty($esmo_result)){
                $warnings[] = $method_name.'. Справочник результатов ЭСМО пуст';
            }else{
                $result = $esmo_result;
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
     * Метод SaveESMOResult() - Сохранение нового результата ЭСМО
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "mo_result":
     *  {
     *      "mo_result_id":-1,					                    // идентификатор результата ЭСМО (-1 =  новый результат ЭСМО)
     *      "title":"MO_RESULT_TEST"				                // наименование результата ЭСМО
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "mo_result_id":5,					                    // идентификатор сохранённого результата ЭСМО
     *      "title":"MO_RESULT_TEST"				                // сохранённое наименование результата ЭСМО
     * }
     * warnings:{}                                                  // массив предупреждений
     * errors:{}                                                    // массив ошибок
     * status:1                                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookESMO&method=SaveESMOResult&subscribe=&data={"mo_result":{"mo_result_id":-1,"title":"MO_RESULT_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.03.2020 10:38
     */
    public static function SaveESMOResult($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveESMOResult';
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
            if (!property_exists($post_dec, 'mo_result'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $mo_result_id = $post_dec->mo_result->mo_result_id;
            $title = $post_dec->mo_result->title;
            $esmo_result = MoResult::findOne(['id'=>$mo_result_id]);
            if (empty($esmo_result)){
                $esmo_result = new MoResult();
            }
            $esmo_result->title = $title;
            if ($esmo_result->save()){
                $esmo_result->refresh();
                $chat_type_data['mo_result_id'] = $esmo_result->id;
                $chat_type_data['title'] = $esmo_result->title;
            }else{
                $errors[] = $esmo_result->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового реузльтата ЭСМО');
            }
            unset($esmo_result);
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
     * Метод DeleteESMOResult() - Удаление результата ЭСМО
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "mo_result_id": 5             // идентификатор удаляемого результата ЭСМО
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookESMO&method=DeleteESMOResult&subscribe=&data={"mo_result_id":5}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.03.2020 10:41
     */
    public static function DeleteESMOResult($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteESMOResult';
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
            if (!property_exists($post_dec, 'mo_result_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $mo_result_id = $post_dec->mo_result_id;
            $del_mo_result = MoResult::deleteAll(['id'=>$mo_result_id]);
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
