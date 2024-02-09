<?php

namespace frontend\controllers\handbooks;

use frontend\models\ClassifierDiseases;
use frontend\models\ClassifierDiseasesKind;
use frontend\models\ClassifierDiseasesType;
use frontend\models\Diseases;
use frontend\models\HarmfulFactors;
use frontend\models\MedReportResult;
use frontend\models\ReasonOccupationalIllness;

class HandbookMedReportController extends \yii\web\Controller
{
    // GetClassifierDiseasesKind                - Получение справочника классификатора видов диагнозов
    // SaveClassifierDiseasesKind               - Сохранение вида классификатора диагноза
    // DeleteClassifierDiseasesKind             - Удаление вида классификатора по идентификатору  диагноза
    // GetClassifierDiseasesType                - Получение справочника классификатора типов диагнозов
    // SaveClassifierDiseasesType               - Сохранение типа классификатора диагноза
    // DeleteClassifierDiseasesType             - Удаление типа классификатора по идентификатору диагноза
    // GetClassifierDiseases                    - Получение справочника классификатора диагнозов
    // SaveClassifierDiseases                   - Сохранение классификатора диагноза
    // DeleteClassifierDiseases                 - Удаление классификатора диагноза по идентификатору
    // GetDiseases                              - Получение справочника профзаболевания
    // SaveDiseases                             - Сохранение справочного профзаболевания
    // DeleteDiseases                           - Удаление справочного профзаболевания
    // GetHarmfulFactors                        - Получение справочника вредных факторов
    // SaveHarmfulFactor                        - Сохранение вредного фактора
    // DeleteHarmfulFactor                      - Удаление вредного фактора
    // GetMedReportResult                       - Получение справочника заключений медицинской комиссии
    // SaveMedReportResult                      - Сохранение/Редактирование заключения медицинской комиссии
    // DeleteMedReportResult                    - Удаление заключения медицинской комссии
    // GetReasonOccupationalIllness             - Получение справочника причин профзаболеваний в блоке профзаболеваний
    // SaveReasonOccupationalIllness            - Сохранение/Редактирование причин профзаболеваний в блоке профзаболеваний
    // DeleteReasonOccupationalIllness          - Удаление причин профзаболеваний в блоке профзаболеваний

    public function actionIndex()
    {
        return $this->render('index');
    }


    /**
     * Метод GetClassifierDiseasesKind() - Получение справочника классификатора видов диагнозов
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * (входные параметры не требуются)
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * Items:[
     *      "id": "1",                                                                  // идентификатор вида диагноза
     *      "title": "Заболевания, связанные с воздействием произв.хим.факторов"        // наименование вида диагноза
     * ]
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=GetClassifierDiseasesKind&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:11
     */
    public static function GetClassifierDiseasesKind()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetClassifierDiseasesKind';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $classifier_kind = ClassifierDiseasesKind::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($classifier_kind)){
                $warnings = $method_name.'. Справочник классификатора видов пустой';
                $result = (object) array();
            }else{
                $result = $classifier_kind;
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
     * Метод SaveClassifierDiseasesKind() - Сохранение вида классификатора диагноза
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      "classifier_diseases_kind":{
     *          "classifier_diseases_kind_id":-1,                         // идентификатор вида диагноза (-1 новый вид диагноза)
     *          "title": "НОВЫЙ ВИД"                        // наименование вида диагноза
     *      }
     * }
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * Items:{
     *      "classifier_diseases_kind_id": 5,               // идентификатор сохранённого вида диагноза
     *      "title": "НОВЫЙ ВИД",                           // наименование сохранённого вида диагноза
     * }
     * warnings:{}                                          // массив предупреждений
     * errors:{}                                            // массив ошибок
     * status:1                                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=SaveClassifierDiseasesKind&subscribe=&data={"classifier_diseases_kind":{"title":"НОВЫЙ ВИД","classifier_diseases_kind_id":-1}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:17
     */
    public static function SaveClassifierDiseasesKind($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveClassifierDiseasesKind';
        $classifier_diseases = array();																				// Промежуточный результирующий массив
    	$warnings[] = $method_name.'. Начало метода';
    	try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'classifier_diseases_kind'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $title = $post_dec->classifier_diseases_kind->title;
            $classifier_diseases_kind_id = $post_dec->classifier_diseases_kind->classifier_diseases_kind_id;

            $class_kind = ClassifierDiseasesKind::findOne(['id' => $classifier_diseases_kind_id]);
            if (empty($class_kind)) {
                $class_kind = new ClassifierDiseasesKind();
            }
            $class_kind->title = $title;
            if ($class_kind->save()){
                $warnings[] = $method_name.'. Сохранили вид классификатора';
                $class_kind->refresh();
                $classifier_diseases['title'] =  $class_kind->title;
                $classifier_diseases['classifier_diseases_kind_id'] =  $class_kind->id;
            }else{
                $errors[] = $class_kind->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении вида классификатора');
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
        $result = $classifier_diseases;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteClassifierDiseasesKind() - Удаление вида классификатора по идентификатору  диагноза
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      "classifier_diseases_kind_id":8                               // идентификатор удаляемого вида диагноза
     * }
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * (стандартный массив выходных данных)
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=DeleteClassifierDiseasesKind&subscribe=&data={"classifier_diseases_kind_id":8}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:22
     */
    public static function DeleteClassifierDiseasesKind($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteClassifierDiseasesKind';
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
    		if (!property_exists($post_dec, 'classifier_diseases_kind_id'))                                                    // Проверяем наличие в нем нужных нам полей
    			{
    				throw new \Exception($method_name.'. Переданы некорректные входные параметры');
    			}
    		$warnings[] = $method_name.'. Данные с фронта получены';
            $classifier_diseases_kind_id = $post_dec->classifier_diseases_kind_id;

            $del_class_kind = ClassifierDiseasesKind::deleteAll(['id'=>$classifier_diseases_kind_id]);

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
     * Метод GetClassifierDiseasesType() - Получение справочника классификатора типов диагнозов
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * (входные параметры не требуются)
     *
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * Items:[
     *      "id": "1",                                                                  // идентификатор типа диагноза
     *      "title": "Наименование типа диагноза"                                       // наименование типа диагноза
     *      "classifier_diseases_kind_id": "5"                                          // идентификатор вида диагноза
     * ]
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=GetClassifierDiseasesType&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:11
     */
    public static function GetClassifierDiseasesType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetClassifierDiseasesType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $classifier_kind = ClassifierDiseasesType::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($classifier_kind)){
                $warnings = $method_name.'. Справочник классификатора видов пустой';
                $result = (object) array();
            }else{
                $result = $classifier_kind;
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
     * Метод SaveClassifierDiseasesType() - Сохранение типа классификатора диагноза
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      "classifier_diseases_type":{
     *          "classifier_diseases_type_id":-1,                         // идентификатор типа диагноза (-1 новый тип диагноза)
     *          "classifier_diseases_kind_id": 5,                         // идентификатор вида диагноза
     *          "title": "НОВЫЙ ТИП"                        // наименование типа диагноза
     *      }
     * }
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * Items:{
     *      "classifier_diseases_type_id": 5,                             // идентификатор сохранённого типа диагноза
     *      "classifier_diseases_kind_id": 5,                             // идентификатор вида диагноза
     *      "title": "НОВЫЙ ТИП"                            // наименование сохранённого типа диагноза
     * }
     * warnings:{}                                          // массив предупреждений
     * errors:{}                                            // массив ошибок
     * status:1                                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=SaveClassifierDiseasesType&subscribe=&data={"classifier_diseases_type":{"title":"НОВЫЙ ТИП","classifier_diseases_kind_id":4,"classifier_diseases_type_id":-1}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:17
     */
    public static function SaveClassifierDiseasesType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveClassifierDiseasesType';
        $warnings[] = $method_name.'. Начало метода';
        $classifier_diseases_type = array();
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'classifier_diseases_type'))                                       // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $title = $post_dec->classifier_diseases_type->title;
            $classifier_diseases_type_id = $post_dec->classifier_diseases_type->classifier_diseases_type_id;
            $classifier_diseases_kind_id = $post_dec->classifier_diseases_type->classifier_diseases_kind_id;

            $class_type = ClassifierDiseasesType::findOne(['id' => $classifier_diseases_type_id]);
            if (empty($class_type)) {
                $class_type = new ClassifierDiseasesType();
            }
            $class_type->title = $title;
            $class_type->classifier_diseases_kind_id = $classifier_diseases_kind_id;
            if ($class_type->save()){
                $warnings[] = $method_name.'. Сохранили вид классификатора';
                $class_type->refresh();
                $classifier_diseases_type['title'] = $class_type->title;
                $classifier_diseases_type['classifier_diseases_kind_id'] = $class_type->classifier_diseases_kind_id;
                $classifier_diseases_type['classifier_diseases_type_id'] = $class_type->id;
            }else{
                $errors[] = $class_type->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении типа классификатора');
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
        $result = $classifier_diseases_type;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteClassifierDiseasesType() - Удаление типа классификатора по идентификатору диагноза
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      "classifier_diseases_type_id":71                               // идентификатор удаляемого типа диагноза
     * }
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * (стандартный массив выходных данных)
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=DeleteClassifierDiseasesType&subscribe=&data={"classifier_diseases_type_id":71}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:22
     */
    public static function DeleteClassifierDiseasesType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteClassifierDiseasesType';
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
            if (!property_exists($post_dec, 'classifier_diseases_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $classifier_diseases_type_id = $post_dec->classifier_diseases_type_id;

            $del_class_type = ClassifierDiseasesType::deleteAll(['id'=>$classifier_diseases_type_id]);

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
     * Метод GetClassifierDiseases() - Получение справочника классификатора диагнозов
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * (входные параметры не требуются)
     *
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * Items:[
     *      "id": "1",                                                                  // идентификатор диагноза
     *      "disease_code": "Код диганоза"                                              // Код диагноза
     *      "title": "Наименование диагноза"                                            // наименование диагноза
     *      "classifier_diseases_type_id": "5"                                          // идентификатор типа диагноза
     * ]
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=GetClassifierDiseases&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:11
     */
    public static function GetClassifierDiseases()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetClassifierDiseases';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $classifier = ClassifierDiseases::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($classifier)){
                $warnings = $method_name.'. Справочник классификатора заболеваний пустой';
                $result = (object) array();
            }else{
                $result = $classifier;
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
     * Метод SaveClassifierDiseases() - Сохранение классификатора диагноза
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      "classifier_diseases":{
     *          "classifier_diseases_id":-1,                                      // идентификатор диагноза (-1 новый диагноз)
     *          "classifier_diseases_type_id": 5,                                 // идентификатор типа диагноза
     *          "disease_code": "КОД ДИАГНОЗА",                     // код диагноза
     *          "title": "НАИМЕНОВАНИЕ ДИАГНОЗА"                    // наименование диагноза
     *      }
     * }
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * Items:{
     *      "classifier_diseases_id": 10,                                         // идентификатор сохранённого диагноза
     *      "classifier_diseases_type_id": 5,                                     // идентификатор типа диагноза
     *      "disease_code": "НОВЫЙ КОД ДИАГНОЗА",                   // код диагноза
     *      "title": "НОВЫЙ ДИАГНОЗ"                                // наименование диагноза
     * }
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=SaveClassifierDiseases&subscribe=&data={"classifier_diseases":{"title":"абракадабра","disease_code":"T51.1 - T51.8","classifier_diseases_id":-1,"classifier_diseases_type_id":8}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:17
     */
    public static function SaveClassifierDiseases($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveClassifierDiseases';
        $warnings[] = $method_name.'. Начало метода';
        $classifier_diseases = array();
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'classifier_diseases'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $title = $post_dec->classifier_diseases->title;
            $classifier_diseases_id = $post_dec->classifier_diseases->classifier_diseases_id;
            $classifier_diseases_type_id = $post_dec->classifier_diseases->classifier_diseases_type_id;
            $disease_code = $post_dec->classifier_diseases->disease_code;

            $classifierDiseases = ClassifierDiseases::findOne(['id' => $classifier_diseases_id]);
            if (empty($classifierDiseases)) {
                $classifierDiseases = new ClassifierDiseases();
            }
            $classifierDiseases->title = $title;
            $classifierDiseases->disease_code = $disease_code;
            $classifierDiseases->classifier_diseases_type_id = $classifier_diseases_type_id;
            if ($classifierDiseases->save()){
                $warnings[] = $method_name.'. Сохранили вид классификатора';
                $classifierDiseases->refresh();
                $classifier_diseases['title'] = $classifierDiseases->title;
                $classifier_diseases['disease_code'] = $classifierDiseases->disease_code;
                $classifier_diseases['classifier_diseases_type_id'] = $classifierDiseases->classifier_diseases_type_id;
                $classifier_diseases['classifier_diseases_id'] = $classifierDiseases->id;
            }else{
                $errors[] = $classifierDiseases->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении вида классификатора');
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
        $result = $classifier_diseases;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteClassifierDiseases() - Удаление классификатора диагноза по идентификатору
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      "classifier_diseases_id":165                               // идентификатор удаляемого диагноза
     * }
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * (стандартный массив выходных данных)
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=DeleteClassifierDiseases&subscribe=&data={"classifier_diseases_id":165}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:22
     */
    public static function DeleteClassifierDiseases($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteClassifierDiseases';
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
            if (!property_exists($post_dec, 'classifier_diseases_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $classifier_diseases_id = $post_dec->classifier_diseases_id;

            $del_class = ClassifierDiseases::deleteAll(['id'=>$classifier_diseases_id]);

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
     * Метод GetDiseases() - Получение справочника профзаболевания
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * (входные параметры не требуются)
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * Items:[
     *      "id": "1",                                                                  // идентификатор профзаболевания
     *      "title": "Подозрение на хроническое заболевание"                            // наименование профзаболевания
     * ]
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=GetDiseases&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:11
     */
    public static function GetDiseases()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetDiseases';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $diseases = Diseases::find()
                ->asArray()
                ->all();
            if (empty($diseases)){
                $warnings = $method_name.'. Справочник классификатора профзаболеваний пустой';
                $result = array();
            }else{
                $result = $diseases;
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
     * Метод SaveDiseases() - Сохранение справочного профзаболевания
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      "diseases":{
     *          "diseases_id":-1,                           // идентификатор профзаболевания (-1 = новое профзаболевание)
     *          "title": "НОВОЕ ПРОФЗАБОЛЕВАНИЕ"            // наименование профзаболевания
     *      }
     * }
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * Items:{
     *      "diseases_id": 15,                              // идентификатор сохранённого профзаболевания
     *      "title": "НОВОЕ ПРОФЗАБОЛЕВАНИЕ"                // наименование сохранённого профзаболевания
     * }
     * warnings:{}                                          // массив предупреждений
     * errors:{}                                            // массив ошибок
     * status:1                                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=SaveDiseases&subscribe=&data={"diseases":{"title":"ТЕСТ1","diseases_id":-1}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:17
     */
    public static function SaveDiseases($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveDiseases';
        $warnings[] = $method_name.'. Начало метода';
        $diseases_data = array();
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'diseases'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $title = $post_dec->diseases->title;
            $diseases_id = $post_dec->diseases->diseases_id;

            $diseases = Diseases::findOne(['id' => $diseases_id]);
            if (empty($diseases)) {
                $diseases = new Diseases();
            }
            $diseases->title = $title;
            if ($diseases->save()){
                $warnings[] = $method_name.'. Сохранили вид классификатора';
                $diseases->refresh();
                $diseases_data['diseases_id'] = $diseases->id;
                $diseases_data['title'] = $diseases->title;
            }else{
                $errors[] = $diseases->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении профзаболеваний');
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
        $result = $diseases_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteDiseases() - Удаление справочного профзаболевания
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      "diseases_id":7                               // идентификатор удаляемого профзаболевания
     * }
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * (стандартный массив выходных данных)
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=DeleteDiseases&subscribe=&data={"diseases_id":7}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:22
     */
    public static function DeleteDiseases($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteDiseases';
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
            if (!property_exists($post_dec, 'diseases_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $diseases_id = $post_dec->diseases_id;

            $del_diseases = Diseases::deleteAll(['id'=>$diseases_id]);

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
     * Метод GetHarmfulFactors() - Получение справочника вредных факторов
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * (входные параметры не требуются)
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * Items:[
     *      "id": "1",                                                                  // идентификатор вредного фактора
     *      "title": "Пр.1 п.1.1.1. Аллергены, \"А\"",                                  // наименование вредного фактора
     *      "period": "12"                                                              // период прохождения МО
     * ]
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=GetHarmfulFactors&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:11
     */
    public static function GetHarmfulFactors()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetHarmfulFactors';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $diseases = HarmfulFactors::find()
                ->asArray()
                ->all();
            if (empty($diseases)){
                $warnings = $method_name.'. Справочник вредных факторов пуст';
                $result = array();
            }else{
                $result = $diseases;
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
     * Метод SaveHarmfulFactor() - Сохранение вредного фактора
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      "harmful_factor":{
     *          "harmful_factor_id":-1,                             // идентификатор вредного фактора (-1 = новый вредный фактор)
     *          "title": "НОВЫЙ ВРЕДНЫЙ ФАКТОР",                    // наименование вредного фактора
     *          "period": "ПЕРИОД"                                  // период прохождения МО
     *      }
     * }
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * Items:{
     *      "harmful_factor_id": 15,                                // идентификатор сохранённого профзаболевания
     *      "title": "НОВЫЙ ВРЕДНЫЙ ФАКТОР",                        // наименование вредного фактора
     *      "period": "ПЕРИОД"                                      // период прохождения МО
     * }
     * warnings:{}                                                  // массив предупреждений
     * errors:{}                                                    // массив ошибок
     * status:1                                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=SaveHarmfulFactor&subscribe=&data={"harmful_factor":{"title":"ТЕСТ СОХРАНЕНИЯ ВРЕДНОГО ФАКТОРА","harmful_factor_id":-1}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:17
     */
    public static function SaveHarmfulFactor($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveHarmfulFactor';
        $warnings[] = $method_name.'. Начало метода';
        $harmful_factor_data = array();
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'harmful_factor'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $title = $post_dec->harmful_factor->title;
            $period = $post_dec->harmful_factor->period;
            $harmful_factor_id = $post_dec->harmful_factor->harmful_factor_id;

            $harmful_factor = HarmfulFactors::findOne(['id' => $harmful_factor_id]);
            if (empty($harmful_factor)) {
                $harmful_factor = new HarmfulFactors();
            }
            $harmful_factor->title = $title;
            $harmful_factor->period = $period;
            if ($harmful_factor->save()){
                $warnings[] = $method_name.'. Сохранили вид классификатора';
                $harmful_factor->refresh();
                $harmful_factor_data['harmful_factor_id'] = $harmful_factor->id;
                $harmful_factor_data['title'] = $harmful_factor->title;
                $harmful_factor_data['period'] = $harmful_factor->period;
            }else{
                $errors[] = $harmful_factor->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении вредного фактора');
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
        $result = $harmful_factor_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteHarmfulFactor() - Удаление вредного фактора
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      "harmful_factor_id":221                               // идентификатор удаляемого вредного фактора
     * }
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * (стандартный массив выходных данных)
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=DeleteHarmfulFactor&subscribe=&data={"harmful_factor_id":221}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:22
     */
    public static function DeleteHarmfulFactor($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteHarmfulFactor';
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
            if (!property_exists($post_dec, 'harmful_factor_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $harmful_factor_id = $post_dec->harmful_factor_id;

            $del_harmful_factor = HarmfulFactors::deleteAll(['id'=>$harmful_factor_id]);

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
     * Метод GetMedReportResult() - Получение справочника заключений медицинской комиссии
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * (входные параметры не требуются)
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * Items:[
     *      "id": "1",                                                                  // идентификатор заключение медицинской комиссии
     *      "title": "Не годен постоянно"                                               // наименование заключения медицинской комиссии
     *      "group_med_report_result_id": "1"                                           // ключ группы заключения медицинской комиссии
     * ]
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=GetMedReportResult&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:11
     */
    public static function GetMedReportResult()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetHarmfulFactors';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $diseases = MedReportResult::find()
                ->asArray()
                ->all();
            if (empty($diseases)){
                $warnings = $method_name.'. Справочник результатов медицинской комиссии пуст';
                $result = array();
            }else{
                $result = $diseases;
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
     * Метод SaveMedReportResult() - Сохранение/Редактирование заключения медицинской комиссии
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      "med_report_result":{
     *          "med_report_result_id":-1,                          // идентификатор заключения медицинской комиссии (-1 = новое заключение медицинской комиссии)
     *          "title": "НОВОЕ ЗАКЛЮЧЕНИЕ"                         // наименование заключения медицинской комиссии
     *          "group_med_report_result_id": "-1"                  // ключ группы заключения медицинской комиссии
     *      }
     * }
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * Items:{
     *      "med_report_result_id": 15,                             // идентификатор сохранённого заключения медицинской комиссии
     *      "title": "НОВОЕ ЗАКЛЮЧЕНИЕ"                             // наименование сохранённого заключения медицинской комиссии
     *      "group_med_report_result_id": "-1"                      // ключ группы заключения медицинской комиссии
     * }
     * warnings:{}                                                  // массив предупреждений
     * errors:{}                                                    // массив ошибок
     * status:1                                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=SaveMedReportResult&subscribe=&data={"med_report_result":{"title":"ТЕСТ СОХРАНЕНИЯ ЗАКЛЮЧЕНИЕ МЕДИЦИНСКОЙ КОМИССИИ","med_report_result_id":-1}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:17
     */
    public static function SaveMedReportResult($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveMedReportResult';
        $warnings[] = $method_name.'. Начало метода';
        $med_report_result_data = array();
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'med_report_result'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $title = $post_dec->med_report_result->title;
            $med_report_result_id = $post_dec->med_report_result->med_report_result_id;
            $group_med_report_result_id = $post_dec->med_report_result->group_med_report_result_id;

            $med_report_result = MedReportResult::findOne(['id' => $med_report_result_id]);
            if (empty($med_report_result)) {
                $med_report_result = new MedReportResult();
            }
            $med_report_result->title = $title;
            $med_report_result->group_med_report_result_id = $group_med_report_result_id;
            if ($med_report_result->save()){
                $warnings[] = $method_name.'. Сохранили заключение медицинской комиссии';
                $med_report_result->refresh();
                $post_dec->med_report_result->med_report_result_id =  $med_report_result->id;
                $med_report_result_data['med_report_result_id'] = $med_report_result->id;
                $med_report_result_data['title'] = $med_report_result->title;
                $med_report_result_data['group_med_report_result_id'] = $med_report_result->group_med_report_result_id;
            }else{
                $errors[] = $med_report_result->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении вредного фактора');
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
        $result = $med_report_result_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteMedReportResult() - Удаление заключения медицинской комссии
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      "med_report_result_id":47                               // идентификатор удаляемого заключение медициской комиссии
     * }
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * (стандартный массив выходных данных)
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=DeleteMedReportResult&subscribe=&data={"med_report_result_id":47}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:22
     */
    public static function DeleteMedReportResult($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteMedReportResult';
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
            if (!property_exists($post_dec, 'med_report_result_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $med_report_result_id = $post_dec->med_report_result_id;

            $del_med_report_result = MedReportResult::deleteAll(['id'=>$med_report_result_id]);

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
     * Метод GetReasonOccupationalIllness() - Получение справочника причин профзаболеваний (Блок профзаболеваний)
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * (входные параметры не требуются)
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * Items:[
     *      "id": "1",                                                                  // идентификатор причины профзаболеваний
     *      "title": "Не годен постоянно"                                               // наименование причины профзаболевания
     * ]
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=GetReasonOccupationalIllness&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:11
     */
    public static function GetReasonOccupationalIllness()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetReasonOccupationalIllness';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $reason_occupational_illnes = ReasonOccupationalIllness::find()
                ->asArray()
                ->all();
            if (empty($reason_occupational_illnes)){
                $warnings = $method_name.'. Справочник причин профзаболеваний пуст пуст';
                $result = array();
            }else{
                $result = $reason_occupational_illnes;
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
     * Метод SaveReasonOccupationalIllness() - Сохранение/Редактирование причины профзаболевания
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      "reason_occupational_illness":{
     *          reason_occupational_illness_id":-1,                          // идентификатор причины профзаболевания
     *          "title": "НОВОЕ ЗАКЛЮЧЕНИЕ"                                 // наименование причины профзаболевания
     *      }
     * }
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * Items:{
     *      "reason_occupational_illness_id": 15,                             // идентификатор сохранённого заключения медицинской комиссии
     *      "title": "НОВОЕ ЗАКЛЮЧЕНИЕ"                             // наименование сохранённого заключения медицинской комиссии
     * }
     * warnings:{}                                                  // массив предупреждений
     * errors:{}                                                    // массив ошибок
     * status:1                                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=SaveReasonOccupationalIllness&subscribe=&data={"reason_occupational_illness":{"title":"ТЕСТ СОХРАНЕНИЯ ЗАКЛЮЧЕНИЕ МЕДИЦИНСКОЙ КОМИССИИ","reason_occupational_illness_id":-1}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:17
     */
    public static function SaveReasonOccupationalIllness($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveReasonOccupationalIllness';
        $warnings[] = $method_name.'. Начало метода';
        $reason_occupational_illness_data = array();
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'reason_occupational_illness'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $title = $post_dec->reason_occupational_illness->title;
            $reason_occupational_illness_id = $post_dec->reason_occupational_illness->reason_occupational_illness_id;

            $reason_occupational_illness_result = ReasonOccupationalIllness::findOne(['id' => $reason_occupational_illness_id]);
            if (empty($reason_occupational_illness_result)) {
                $reason_occupational_illness_result = new ReasonOccupationalIllness();
            }
            $reason_occupational_illness_result->title = $title;
            if ($reason_occupational_illness_result->save()){
                $warnings[] = $method_name.'. Сохранили заключение причин профзаболевания';
                $reason_occupational_illness_result->refresh();
                $post_dec->reason_occupational_illness->reason_occupational_illness_id =  $reason_occupational_illness_result->id;
                $reason_occupational_illness_data['reason_occupational_illness_id'] = $reason_occupational_illness_result->id;
                $reason_occupational_illness_data['title'] = $reason_occupational_illness_result->title;
            }else{
                $errors[] = $reason_occupational_illness_result->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении причин профзаболевания');
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
        $result = $reason_occupational_illness_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteReasonOccupationalIllness() - Удаление причин профзаболеваний
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      "med_report_result_id":47                               // идентификатор удаляемого заключение медициской комиссии
     * }
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * (стандартный массив выходных данных)
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\HandbookMedReport&method=DeleteReasonOccupationalIllness&subscribe=&data={"reason_occupational_illness_id":47}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.03.2020 9:22
     */
    public static function DeleteReasonOccupationalIllness($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteReasonOccupationalIllness';
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
            if (!property_exists($post_dec, 'reason_occupational_illness_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $reason_occupational_illness_id = $post_dec->reason_occupational_illness_id;

            $reason_occupational_illness_result = ReasonOccupationalIllness::deleteAll(['id'=>$reason_occupational_illness_id]);

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
