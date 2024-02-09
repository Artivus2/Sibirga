<?php

namespace frontend\controllers\handbooks;

use frontend\models\ChatAttachmentType;
use frontend\models\ChatMessage;
use frontend\models\ChatType;

class HandbookChatController extends \yii\web\Controller
{
    // GetChatType                          - Получение справочника типов чатов
    // SaveChatType                         - Сохранение нового типа чата
    // DeleteChatType                       - Удаление типа чата
    // GetChatAttachmentType                - Получение справочника типов вложений в чате
    // SaveChatAttachmentType               - Сохранение нового типа вложения в чате
    // DeleteChatAttachmentType             - Удаление типа вложения в чате



    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetChatType() - Получение справочника типов чатов
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,					            // идентификатор типа чата
     *      "title":"Индивидуальный"				// наименование типа чата
     * ]
     * warnings:{}                                  // массив предупреждений
     * errors:{}                                    // массив ошибок
     * status:1                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookChat&method=GetChatType&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 13:41
     */
    public static function GetChatType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetChatType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $chant_type_data = ChatType::find()
                ->asArray()
                ->all();
            if(empty($chant_type_data)){
                $warnings[] = $method_name.'. Справочник типов чатов пуст';
            }else{
                $result = $chant_type_data;
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
     * Метод SaveChatType() - Сохранение нового типа чата
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "chat_type":
     *  {
     *      "chat_type_id":-1,					            // идентификатор типа чата (-1 = при добавлении нового типа чата)
     *      "title":"CHAT_TYPE"				                // наименование типа чата
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "chat_type_id":5,					            // идентификатор сохранённого типа чата
     *      "title":"CHAT_TYPE"				                // сохранённое наименование типа чата
     *
     * }
     * warnings:{}                                          // массив предупреждений
     * errors:{}                                            // массив ошибок
     * status:1                                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookChat&method=SaveChatType&subscribe=&data={"chat_type":{"chat_type_id":-1,"title":"CHAT_TYPE"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 13:47
     */
    public static function SaveChatType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveChatType';
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
            if (!property_exists($post_dec, 'chat_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $chat_type_id = $post_dec->chat_type->chat_type_id;
            $title = $post_dec->chat_type->title;
            $chat_type = ChatType::findOne(['id'=>$chat_type_id]);
            if (empty($chat_type)){
                $chat_type = new ChatType();
            }
            $chat_type->title = $title;
            if ($chat_type->save()){
                $chat_type->refresh();
                $chat_type_data['chat_type_id'] = $chat_type->id;
                $chat_type_data['title'] = $chat_type->title;
            }else{
                $errors[] = $chat_type->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового типа чата');
            }
            unset($chat_type);
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
     * Метод DeleteChatType() - Удаление типа чата
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "chat_type_id": 3             // идентификатор удаляемого типа чата
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookChat&method=DeleteChatType&subscribe=&data={"chat_type_id":3}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 13:49
     */
    public static function DeleteChatType($data_post = NULL)
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
            if (!property_exists($post_dec, 'chat_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $chat_type_id = $post_dec->chat_type_id;
            $del_chat_type = ChatType::deleteAll(['id'=>$chat_type_id]);
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
     * Метод GetChatAttachmentType() - Получение справочника типов вложений в чате
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,					        // идентификатор типа вложения в чате
     *      "title":"Изображение"				// наименование типа вложения в чате
     * ]
     * warnings:{}                              // массив предупреждений
     * errors:{}                                // массив ошибок
     * status:1                                 // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookChat&method=GetChatAttachmentType&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 13:41
     */
    public static function GetChatAttachmentType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetChatType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $chant_attachment_type_data = ChatAttachmentType::find()
                ->asArray()
                ->all();
            if(empty($chant_attachment_type_data)){
                $warnings[] = $method_name.'. Справочник типов вложений для чата пуст';
            }else{
                $result = $chant_attachment_type_data;
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
     * Метод SaveChatAttachmentType() - Сохранение нового типа вложения в чате
     *
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "chat_attachment_type":
     *  {
     *      "chat_attachment_type_id":-1,					// идентификатор типа вложения в чате (-1 = при добавлении нового типа вложения в чате)
     *      "title":"Новый тип"				                // наименование действия
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "chat_attachment_type_id":5,					// идентификатор сохранённого типа вложения в чате
     *      "title":"Новый тип"				                // сохранённое наименование типа вложения в чате
     *
     * }
     * warnings:{}                                          // массив предупреждений
     * errors:{}                                            // массив ошибок
     * status:1                                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookChat&method=SaveChatAttachmentType&subscribe=&data={"chat_attachment_type":{"chat_attachment_type_id":-1,"title":"CHAT_ATTACHMENT_TYPE"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 14:27
     */
    public static function SaveChatAttachmentType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveChatType';
        $chant_attachment_type_data = array();																				// Промежуточный результирующий массив
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
            if (!property_exists($post_dec, 'chat_attachment_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $chat_attachment_type_id = $post_dec->chat_attachment_type->chat_attachment_type_id;
            $title = $post_dec->chat_attachment_type->title;
            $chat_attachment_type = ChatAttachmentType::findOne(['id'=>$chat_attachment_type_id]);
            if (empty($chat_attachment_type)){
                $chat_attachment_type = new ChatAttachmentType();
            }
            $chat_attachment_type->title = $title;
            if ($chat_attachment_type->save()){
                $chat_attachment_type->refresh();
                $chant_attachment_type_data['chat_attachment_type_id'] = $chat_attachment_type->id;
                $chant_attachment_type_data['title'] = $chat_attachment_type->title;
            }else{
                $errors[] = $chat_attachment_type->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового типа вложения');
            }
            unset($chat_attachment_type);
        }
        catch (\Throwable $exception)
        {
            $errors[] = $method_name.'. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name.'. Конец метода';
        $result = $chant_attachment_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteChatAttachmentType() - Удаление типа вложения в чате
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "chat_attachment_type_id": 3             // идентификатор удаляемого типа вложения в чате
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookChat&method=DeleteChatAttachmentType&subscribe=&data={"chat_attachment_type_id":3}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 14:27
     */
    public static function DeleteChatAttachmentType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteChatAttachmentType';
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
            if (!property_exists($post_dec, 'chat_attachment_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $chat_attachment_type_id = $post_dec->chat_attachment_type_id;
            $chat_message = ChatMessage::find()
                ->where(['chat_attachment_type_id'=>$chat_attachment_type_id])
                ->all();
            if (empty($chat_message)){
                $del_chat_attachment_type = ChatAttachmentType::deleteAll(['id'=>$chat_attachment_type_id]);
            }else{
                $errors[] = 'Невозможно удалить тип вложения. Так как существуют сообщения с таким типов вложения.';
                $status = 0;
            }
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
