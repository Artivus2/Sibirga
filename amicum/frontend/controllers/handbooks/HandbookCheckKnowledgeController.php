<?php

namespace frontend\controllers\handbooks;

use frontend\models\Access;
use frontend\models\ReasonCheckKnowledge;
use frontend\models\TypeCheckKnowledge;

class HandbookCheckKnowledgeController extends \yii\web\Controller
{

    // ReasonCheckKnowledge                     - Получение причин проверок знаний
    // SaveReasonCheckKnowledge                 - Сохранение причины проверки знаний
    // DeleteReasonCheckKnowledge               - Удаление причины проверки знаний

    // GetTypeCheckKnowledge()      - Получение справочника типов проверки знаний
    // SaveTypeCheckKnowledge()     - Сохранение справочника типов проверки знаний
    // DeleteTypeCheckKnowledge()   - Удаление справочника типов проверки знаний


    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод ReasonCheckKnowledge() - Получение причин проверок знаний
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,					    // идентификатор причины проверки знаний
     *      "title":"Причина 1"				// наименование причины проверки знаний
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookCheckKnowledge&method=GetReasonCheckKnowledge&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 10:11
     */
    public static function GetReasonCheckKnowledge()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetReasonCheckKnowledge';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $reason_check_knowldege_data = ReasonCheckKnowledge::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if(empty($reason_check_knowldege_data)){
                $result = (object) array();
                $warnings[] = $method_name.'. Справочник прав доступа пуст';
            }else{
                $result = $reason_check_knowldege_data;
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
     * Метод SaveReasonCheckKnowledge() - Сохранение причины проверки знаний
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "reason_check_knowledge":
     *  {
     *      "reason_check_knowledge_id":-1,					// идентификатор причины проверки знаний (-1 = при добавлении новой причины проверки знаний)
     *      "title":"Причниа"				                // наименование причины проверки знаний
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "reason_check_knowledge_id":5,					// идентификатор сохранённой причины проверки занинй
     *      "title":"Причниа"				                // сохранённое наименование причичины проверки знаний
     * }
     * warnings:{}                                          // массив предупреждений
     * errors:{}                                            // массив ошибок
     * status:1                                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookCheckKnowledge&method=SaveReasonCheckKnowledge&subscribe=&data={"reason_check_knowledge":{"reason_check_knowledge_id":-1,"title":"ACTION"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveReasonCheckKnowledge($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveAccess';
        $access_data = array();																				// Промежуточный результирующий массив
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
            if (!property_exists($post_dec, 'reason_check_knowledge'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $reason_check_knowledge_id = $post_dec->reason_check_knowledge->reason_check_knowledge_id;
            $title = $post_dec->reason_check_knowledge->title;
            $reasonCheckKnowledge = ReasonCheckKnowledge::findOne(['id'=>$reason_check_knowledge_id]);
            if (empty($reasonCheckKnowledge)){
                $reasonCheckKnowledge = new ReasonCheckKnowledge();
            }
            $reasonCheckKnowledge->title = $title;
            if ($reasonCheckKnowledge->save()){
                $reasonCheckKnowledge->refresh();
                $access_data['reason_check_knowledge_id'] = $reasonCheckKnowledge->id;
                $access_data['title'] = $reasonCheckKnowledge->title;
            }else{
                $errors[] = $reasonCheckKnowledge->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового права пользователей');
            }
            unset($reasonCheckKnowledge);
        }
        catch (\Throwable $exception)
        {
            $errors[] = $method_name.'. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name.'. Конец метода';
        $result = $access_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteReasonCheckKnowledge() - Удаление причины проверки знаний
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "reason_check_knowledge_id": 6             // идентификатор удаляемой причины проверки знаний
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookCheckKnowledge&method=DeleteReasonCheckKnowledge&subscribe=&data={"reason_check_knowledge_id":6}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteReasonCheckKnowledge($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteAccess';
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
            if (!property_exists($post_dec, 'reason_check_knowledge_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $reason_check_knowledge_id = $post_dec->reason_check_knowledge_id;
            $del_reason_check_knowledge = ReasonCheckKnowledge::deleteAll(['id'=>$reason_check_knowledge_id]);
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


    // GetTypeCheckKnowledge()      - Получение справочника типов проверки знаний
    // SaveTypeCheckKnowledge()     - Сохранение справочника типов проверки знаний
    // DeleteTypeCheckKnowledge()   - Удаление справочника типов проверки знаний

    /**
     * Метод GetTypeCheckKnowledge() - Получение справочника типов проверки знаний
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                        // ключ справочника
     *      "title":"ACTION",                // название справочника
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookCheckKnowledge&method=GetTypeCheckKnowledge&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetTypeCheckKnowledge()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetTypeCheckKnowledge';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_type_check_knowledge = TypeCheckKnowledge::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_type_check_knowledge)) {
                $result = (object) array();
                $warnings[] = $method_name . '. Справочник типов проверки знаний пуст';
            } else {
                $result = $handbook_type_check_knowledge;
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
     * Метод SaveTypeCheckKnowledge() - Сохранение справочника типов проверки знаний
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "type_check_knowledge":
     *  {
     *      "type_check_knowledge_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "type_check_knowledge_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookCheckKnowledge&method=SaveTypeCheckKnowledge&subscribe=&data={"type_check_knowledge":{"type_check_knowledge_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveTypeCheckKnowledge($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveTypeCheckKnowledge';
        $handbook_type_check_knowledge_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'type_check_knowledge'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_type_check_knowledge_id = $post_dec->type_check_knowledge->type_check_knowledge_id;
            $title = $post_dec->type_check_knowledge->title;
            $new_handbook_type_check_knowledge_id = TypeCheckKnowledge::findOne(['id' => $handbook_type_check_knowledge_id]);
            if (empty($new_handbook_type_check_knowledge_id)) {
                $new_handbook_type_check_knowledge_id = new TypeCheckKnowledge();
            }
            $new_handbook_type_check_knowledge_id->title = $title;
            if ($new_handbook_type_check_knowledge_id->save()) {
                $new_handbook_type_check_knowledge_id->refresh();
                $handbook_type_check_knowledge_data['type_check_knowledge_id'] = $new_handbook_type_check_knowledge_id->id;
                $handbook_type_check_knowledge_data['title'] = $new_handbook_type_check_knowledge_id->title;
            } else {
                $errors[] = $new_handbook_type_check_knowledge_id->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении справочника типов проверки знаний');
            }
            unset($new_handbook_type_check_knowledge_id);
        } catch (\Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_type_check_knowledge_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteTypeCheckKnowledge() - Удаление справочника типов проверки знаний
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "type_check_knowledge_id": 98             // идентификатор справочника типов проверки знаний
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookCheckKnowledge&method=DeleteTypeCheckKnowledge&subscribe=&data={"type_check_knowledge_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteTypeCheckKnowledge($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteTypeCheckKnowledge';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'type_check_knowledge_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_type_check_knowledge_id = $post_dec->type_check_knowledge_id;
            $del_handbook_type_check_knowledge = TypeCheckKnowledge::deleteAll(['id' => $handbook_type_check_knowledge_id]);
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
