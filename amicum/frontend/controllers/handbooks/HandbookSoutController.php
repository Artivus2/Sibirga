<?php

namespace frontend\controllers\handbooks;

use frontend\models\CheckingSoutType;
use frontend\models\WorkingPlace;

class HandbookSoutController extends \yii\web\Controller
{

    // GetCheckingSoutType                  - Получение справочника типов проверок СОУТ
    // SaveCheckingSoutType                 - Сохранение нового типа чата
    // DeleteCheckingSoutType               - Удаление типа проверки СОУТ

    // GetWorkingPlace()                    - Получение справочника рабочих мест СОУТ
    // SaveWorkingPlace()                   - Сохранение справочника рабочих мест СОУТ
    // DeleteWorkingPlace()                 - Удаление справочника рабочих мест СОУТ


    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetCheckingSoutType() - Получение справочника типов проверок СОУТ
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,					                            // идентификатор типа проверки СОУТ
     *      "title":"Специальная оценка условий труда"				// наименование типа проверки СОУТ
     * ]
     * warnings:{}                                                  // массив предупреждений
     * errors:{}                                                    // массив ошибок
     * status:1                                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookSout&method=GetCheckingSoutType&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 09:40
     */
    public static function GetCheckingSoutType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetChatType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $checking_sout_type = CheckingSoutType::find()
                ->asArray()
                ->all();
            if(empty($checking_sout_type)){
                $warnings[] = $method_name.'. Справочник типов проверок СОУТ';
            }else{
                $result = $checking_sout_type;
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
     * Метод SaveCheckingSoutType() - Сохранение нового типа проверки СОУТ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "checking_sout_type":
     *  {
     *      "checking_sout_type_id":-1,					                // идентификатор типа проверки СОУТ (-1 = при добавлении нового типа проверки СОУТ)
     *      "title":"CHECKING_SOUT_TYPE"				                // наименование типа проверки СОУТ
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "reason_check_knowledge_id":5,					            // идентификатор сохранённого типа проверки СОУТ
     *      "title":"CHECKING_SOUT_TYPE"				                // сохранённое наименование типа проверки СОУТ
     *
     * }
     * warnings:{}                                                      // массив предупреждений
     * errors:{}                                                        // массив ошибок
     * status:1                                                         // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookSout&method=SaveCheckingSoutType&subscribe=&data={"checking_sout_type":{"checking_sout_type_id":-1,"title":"CHECKING_SOUT_TYPE"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 09:40
     */
    public static function SaveCheckingSoutType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveChatType';
        $checking_sout_type_data = array();																				// Промежуточный результирующий массив
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
            if (!property_exists($post_dec, 'checking_sout_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $chat_type_id = $post_dec->checking_sout_type->checking_sout_type_id;
            $title = $post_dec->checking_sout_type->title;
            $checkeing_sout_type = CheckingSoutType::findOne(['id'=>$chat_type_id]);
            if (empty($checkeing_sout_type)){
                $checkeing_sout_type = new CheckingSoutType();
            }
            $checkeing_sout_type->title = $title;
            if ($checkeing_sout_type->save()){
                $checkeing_sout_type->refresh();
                $checking_sout_type_data['checking_sout_type_id'] = $checkeing_sout_type->id;
                $checking_sout_type_data['title'] = $checkeing_sout_type->title;
            }else{
                $errors[] = $checkeing_sout_type->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении типа проверки СОУТ');
            }
            unset($checkeing_sout_type);
        }
        catch (\Throwable $exception)
        {
            $errors[] = $method_name.'. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name.'. Конец метода';
        $result = $checking_sout_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteCheckingSoutType() - Удаление типа проверки СОУТ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "checking_sout_type_id": 15                     // идентификатор удаляемого типа проверки СОУТ
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookSout&method=DeleteCheckingSoutType&subscribe=&data={"checking_sout_type_id":5}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 09:49
     */
    public static function DeleteCheckingSoutType($data_post = NULL)
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
            if (!property_exists($post_dec, 'checking_sout_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $checking_sout_type_id = $post_dec->checking_sout_type_id;
            $del_checking_sout_type = CheckingSoutType::deleteAll(['id'=>$checking_sout_type_id]);
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


    // GetWorkingPlace()      - Получение справочника рабочих мест СОУТ
    // SaveWorkingPlace()     - Сохранение справочника рабочих мест СОУТ
    // DeleteWorkingPlace()   - Удаление справочника рабочих мест СОУТ

    /**
     * Метод GetWorkingPlace() - Получение справочника рабочих мест СОУТ
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                        // ключ справочника рабочих мест СОУТ
     *      "company_department_id":"-1",   // ключ департамента
     *      "place_id":"-1",                // ключ места
     *      "place_type_id":"-1",           // ключ типа места
     *      "role_id":"-1",                 // ключ роли
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookSout&method=GetWorkingPlace&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetWorkingPlace()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetWorkingPlace';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_working_place = WorkingPlace::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_working_place)) {
                $result = (object) array();
                $warnings[] = $method_name . '. Справочник рабочих мест СОУТ пуст';
            } else {
                $result = $handbook_working_place;
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
     * Метод SaveWorkingPlace() - Сохранение справочника рабочих мест СОУТ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "working_place":
     *  {
     *      "working_place_id":-1,          // ключ справочника рабочих мест СОУТ
     *      "company_department_id":"-1",   // ключ департамента
     *      "place_id":"-1",                // ключ места
     *      "place_type_id":"-1",           // ключ типа места
     *      "role_id":"-1",                 // ключ роли
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "working_place_id":-1,          // ключ справочника рабочих мест СОУТ
     *      "company_department_id":"-1",   // ключ департамента
     *      "place_id":"-1",                // ключ места
     *      "place_type_id":"-1",           // ключ типа места
     *      "role_id":"-1",                 // ключ роли
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookSout&method=SaveWorkingPlace&subscribe=&data={"working_place":{"working_place_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveWorkingPlace($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveWorkingPlace';
        $handbook_working_place_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'working_place'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_working_place_id = $post_dec->working_place->working_place_id;
            $company_department_id = $post_dec->working_place->company_department_id;
            $place_id = $post_dec->working_place->place_id;
            $place_type_id = $post_dec->working_place->place_type_id;
            $role_id = $post_dec->working_place->role_id;
            $new_handbook_working_place_id = WorkingPlace::findOne(['id' => $handbook_working_place_id]);
            if (empty($new_handbook_working_place_id)) {
                $new_handbook_working_place_id = new WorkingPlace();
            }
            $new_handbook_working_place_id->company_department_id = $company_department_id;
            $new_handbook_working_place_id->place_id = $place_id;
            $new_handbook_working_place_id->place_type_id = $place_type_id;
            $new_handbook_working_place_id->role_id = $role_id;
            if ($new_handbook_working_place_id->save()) {
                $new_handbook_working_place_id->refresh();
                $handbook_working_place_data['working_place_id'] = $new_handbook_working_place_id->id;
                $handbook_working_place_data['company_department_id'] = $new_handbook_working_place_id->company_department_id;
                $handbook_working_place_data['place_id'] = $new_handbook_working_place_id->place_id;
                $handbook_working_place_data['place_type_id'] = $new_handbook_working_place_id->place_type_id;
                $handbook_working_place_data['role_id'] = $new_handbook_working_place_id->role_id;
            } else {
                $errors[] = $new_handbook_working_place_id->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении справочника рабочих мест СОУТ');
            }
            unset($new_handbook_working_place_id);
        } catch (\Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_working_place_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteWorkingPlace() - Удаление справочника рабочих мест СОУТ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "working_place_id": 98             // идентификатор справочника рабочих мест СОУТ
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookSout&method=DeleteWorkingPlace&subscribe=&data={"working_place_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteWorkingPlace($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteWorkingPlace';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'working_place_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_working_place_id = $post_dec->working_place_id;
            $del_handbook_working_place = WorkingPlace::deleteAll(['id' => $handbook_working_place_id]);
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
