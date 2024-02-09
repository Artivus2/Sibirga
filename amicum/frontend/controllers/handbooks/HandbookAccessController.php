<?php

namespace frontend\controllers\handbooks;

use frontend\models\Access;
use frontend\models\Page;

class HandbookAccessController extends \yii\web\Controller
{

    // GetPage                  - Получение справочника страниц системы
    // SavePage                 - Сохранение новой страницы системы
    // DeletePage               - Удаление страницы системы

    // GetAccess                - Получение справочника прав доступа
    // SaveAccess               - Сохранение нового права доступа
    // DeleteAccess             - Удаление права доступа


    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetAccess() - Получение справочника прав доступа
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,					    // идентификатор права доступа
     *      "title":"ACTION",				// наименование действия
     *      "description":"Описание",		// описание действия
     *      "page_id":"1"				// ключ страницы на которой выполняется дейтсвие
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookAccess&method=GetAccess&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 10:11
     */
    public static function GetAccess()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetAccess';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $access_data = Access::find()
                ->asArray()
                ->all();
            if(empty($access_data)){
                $warnings[] = $method_name.'. Справочник прав доступа пуст';
            }else{
                $result = $access_data;
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
     * Метод GetPage() - Получение справочника страниц системы
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":				// ключ страницы
     *      "title"				// наименование страницы
     *      "url":		        // адресс страницы
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookAccess&method=GetPage&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetPage()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetPage';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $page_data = Page::find()
                ->asArray()
                ->all();
            if(empty($page_data)){
                $warnings[] = $method_name.'. Справочник страниц системы пуст';
            }else{
                $result = $page_data;
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
     * Метод SaveAccess() - Сохранение нового права доступа
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "access":
     *  {
     *      "access_id":-1,					// идентификатор права доступа (-1 = при добавлении)
     *      "title":"ACTION",				// наименование действия
     *      "description":"Описание",		// описание действия
     *      "page":"11"					// ключ страницы на которой выполняется дейтсвие
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "access_id":5,					// идентификатор сохранённого права доступа
     *      "title":"ACTION",				// сохранённое наименование действия
     *      "description":"Описание",		// сохранённое описание действия
     *      "page_id":"11"					//  сохранённый ключ страницы на которой выполняется дейтсвие
     *
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookAccess&method=SaveAccess&subscribe=&data={"access":{"access_id":-1,"title":"ACTION","description":"Описание","page_id":"11"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveAccess($data_post = NULL)
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
    		if (!property_exists($post_dec, 'access'))                                                    // Проверяем наличие в нем нужных нам полей
    			{
    				throw new \Exception($method_name.'. Переданы некорректные входные параметры');
    			}
    		$warnings[] = $method_name.'. Данные с фронта получены';
    		$access_id = $post_dec->access->access_id;
    		$title = $post_dec->access->title;
            $description = $post_dec->access->description;
            $page_id = $post_dec->access->page_id;
    		$access = Access::findOne(['id'=>$access_id]);
    		if (empty($access)){
                $access = new Access();
            }
            $access->title = $title;
            $access->description = $description;
            $access->page_id = $page_id;
    		if ($access->save()){
                $access->refresh();
                $access_data['access_id'] = $access->id;
                $access_data['title'] = $access->title;
                $access_data['description'] = $access->description;
                $access_data['page_id'] = $access->page_id;
            }else{
    		    $errors[] = $access->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового права пользователей');
            }
    		unset($access);
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
     * Метод SavePage() - Сохранение новой страницы системы
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "page":
     *      "page_id":				// ключ страницы
     *      "title"				// наименование страницы
     *      "url":		        // адресс страницы
     *
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "page_id":				// ключ страницы
     *      "title"				// наименование страницы
     *      "url":		        // адресс страницы
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookAccess&method=SavePage&subscribe=&data={"page":{"page_id":-1,"title":"ACTION","url":"Описание"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 10:17
     */
    public static function SavePage($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SavePage';
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
    		if (!property_exists($post_dec, 'page'))                                                    // Проверяем наличие в нем нужных нам полей
    			{
    				throw new \Exception($method_name.'. Переданы некорректные входные параметры');
    			}
    		$warnings[] = $method_name.'. Данные с фронта получены';
    		$page_id = $post_dec->page->page_id;
    		$title = $post_dec->page->title;
            $url = $post_dec->page->url;
    		$new_page = Page::findOne(['id'=>$page_id]);
    		if (empty($new_page)){
                $new_page = new Page();
            }
            $new_page->title = $title;
            $new_page->url = $url;
    		if ($new_page->save()){
                $new_page->refresh();
                $access_data['page_id'] = $new_page->id;
                $access_data['title'] = $new_page->title;
                $access_data['url'] = $new_page->url;
            }else{
    		    $errors[] = $new_page->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении новой страницы системы');
            }
    		unset($new_page);
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
     * Метод DeleteAccess() - Удаление права доступа
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "access_id": 98             // идентификатор удаляемого права доступа
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookAccess&method=DeleteAccess&subscribe=&data={"access_id":98}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteAccess($data_post = NULL)
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
    		if (!property_exists($post_dec, 'access_id'))                                                    // Проверяем наличие в нем нужных нам полей
    			{
    				throw new \Exception($method_name.'. Переданы некорректные входные параметры');
    			}
    		$warnings[] = $method_name.'. Данные с фронта получены';
    		$access_id = $post_dec->access_id;
    		$del_access = Access::deleteAll(['id'=>$access_id]);
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
     * Метод DeletePage               - Удаление страницы системы
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "page_id": 98             // идентификатор страницы системы
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookAccess&method=DeletePage&subscribe=&data={"page_id":98}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 10:21
     */
    public static function DeletePage($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeletePage';
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
    		if (!property_exists($post_dec, 'page_id'))                                                    // Проверяем наличие в нем нужных нам полей
    			{
    				throw new \Exception($method_name.'. Переданы некорректные входные параметры');
    			}
    		$warnings[] = $method_name.'. Данные с фронта получены';
    		$page_id = $post_dec->page_id;
    		$del_page = Page::deleteAll(['id'=>$page_id]);
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
