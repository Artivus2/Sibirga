<?php

namespace frontend\controllers\handbooks;

use frontend\models\InternshipReason;

class HandbookInternshipController extends \yii\web\Controller
{
    // GetInternshipReason                      - Получение справочника причин стажировок
    // SaveInternshipReason                     - Сохранение новой причины стажировок
    // DeleteInternshipReason                   - Удаление причины стажировок


    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetInternshipReason() - Получение справочника причин стажировок
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,					            // идентификатор причины стажировки
     *      "title":"Трудоустройство"				// наименование причины стажировки
     * ]
     * warnings:{}                                  // массив предупреждений
     * errors:{}                                    // массив ошибок
     * status:1                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInternship&method=GetInternshipReason&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 11:38
     */
    public static function GetInternshipReason()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetInternshipReason';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $internship_reason = InternshipReason::find()
                ->asArray()
                ->all();
            if(empty($internship_reason)){
                $warnings[] = $method_name.'. Справочник причин стажировок пуст';
            }else{
                $result = $internship_reason;
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
     * Метод SaveInternshipReason() - Сохранение новой причины стажировок
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "internship_reason":
     *  {
     *      "internship_reason_id":-1,					            // идентификатор причины стажировки (-1 = новый тип проверки стажировки)
     *      "title":"INTERNSHIP_REASON"				                // наименование причины стажировки
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "internship_reason_id":5,					            // идентификатор сохранённой причины стажировки
     *      "title":"INTERNSHIP_REASON"				                // сохранённое наименование причины стажировки
     * }
     * warnings:{}                                                  // массив предупреждений
     * errors:{}                                                    // массив ошибок
     * status:1                                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInternship&method=SaveInternshipReason&subscribe=&data={"internship_reason":{"internship_reason_id":-1,"title":"INTERNSHIP_REASON"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 11:41
     */
    public static function SaveInternshipReason($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveInternshipReason';
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
            if (!property_exists($post_dec, 'internship_reason'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $internship_reason_id = $post_dec->internship_reason->internship_reason_id;
            $title = $post_dec->internship_reason->title;
            $internship_reason = InternshipReason::findOne(['id'=>$internship_reason_id]);
            if (empty($internship_reason)){
                $internship_reason = new InternshipReason();
            }
            $internship_reason->title = $title;
            if ($internship_reason->save()){
                $internship_reason->refresh();
                $chat_type_data['internship_reason_id'] = $internship_reason->id;
                $chat_type_data['title'] = $internship_reason->title;
            }else{
                $errors[] = $internship_reason->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении новой причины стажировок');
            }
            unset($internship_reason);
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
     * Метод DeleteInternshipReason() - Удаление причины стажировок
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "internship_reason_id": 6             // идентификатор удаляемой причины проверки стажировки
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInternship&method=DeleteInternshipReason&subscribe=&data={"internship_reason_id":6}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 11:44
     */
    public static function DeleteInternshipReason($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteInternshipReason';
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
            if (!property_exists($post_dec, 'internship_reason_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $internship_reason_id = $post_dec->internship_reason_id;
            $del_internship_reason = InternshipReason::deleteAll(['id'=>$internship_reason_id]);
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
