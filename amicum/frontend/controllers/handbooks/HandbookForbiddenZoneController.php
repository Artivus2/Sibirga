<?php

namespace frontend\controllers\handbooks;

use frontend\models\ForbiddenType;

class HandbookForbiddenZoneController extends \yii\web\Controller
{

    // GetForbiddenType                     - Получение справочника типов запретов
    // SaveForbiddenType                    - Сохранение нового типа запрета
    // DeleteForbiddenType                  - Удаление типа запрета



    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetForbiddenType() - Получение справочника типов запретов
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * Входные обязательные параметры:
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookForbiddenZone&method=GetForbiddenType&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 11:14
     */
    public static function GetForbiddenType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetForbiddenType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $forbidden_type = ForbiddenType::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if(empty($forbidden_type)){
                $result = (object) array();
                $warnings[] = $method_name.'. Справочник типов запретов пуст';
            }else{
                $result = $forbidden_type;
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
     * Метод SaveForbiddenType() - Сохранение нового типа запрета
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * Входные обязательные параметры:
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookForbiddenZone&method=SaveForbiddenType&subscribe=&data={"forbidden_type":{"forbidden_type_id":-1,"title":"FORBIDDEN_TYPE"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 11:17
     */
    public static function SaveForbiddenType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveForbiddenType';
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
            if (!property_exists($post_dec, 'forbidden_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $forbidden_type_id = $post_dec->forbidden_type->forbidden_type_id;
            $title = $post_dec->forbidden_type->title;
            $forbidden_type = ForbiddenType::findOne(['id'=>$forbidden_type_id]);
            if (empty($forbidden_type)){
                $forbidden_type = new ForbiddenType();
            }
            $forbidden_type->title = $title;
            if ($forbidden_type->save()){
                $forbidden_type->refresh();
                $chat_type_data['forbidden_type_id'] = $forbidden_type->id;
                $chat_type_data['title'] = $forbidden_type->title;
            }else{
                $errors[] = $forbidden_type->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового типа запрета');
            }
            unset($forbidden_type);
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
     * Метод DeleteForbiddenType() - Удаление типа запрета
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * Входные обязательные параметры:
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookForbiddenZone&method=DeleteForbiddenType&subscribe=&data={"forbidden_type_id":6}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 11:19
     */
    public static function DeleteForbiddenType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteForbiddenType';
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
            if (!property_exists($post_dec, 'forbidden_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $forbidden_type_id = $post_dec->forbidden_type_id;
            $del_forbidden_type = ForbiddenType::deleteAll(['id'=>$forbidden_type_id]);
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
