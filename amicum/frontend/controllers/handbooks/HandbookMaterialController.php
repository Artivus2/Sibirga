<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\handbooks;

use Exception;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\KindDirectionStore;
use frontend\models\Nomenclature;
use frontend\models\Material;
use Throwable;

class HandbookMaterialController extends \yii\web\Controller
{
    // GetKindDirectionStore                        - Получение справочника направлений списания материалов
    // SaveKindDirectionStore                       - Сохранение нового направлений списания материалов
    // DeleteKindDirectionStore                     - Удаление направления списания материалов
    // GetNomenclature                              - Получение справочника номенклатуры
    // SaveNomenclature                             - Сохранение новой номенклатуры
    // DeleteNomenclature                           - Удаление номенклатуры

    // GetMaterial()      - Получение справочника материалов
    // SaveMaterial()     - Сохранение справочника материалов
    // DeleteMaterial()   - Удаление справочника материалов


    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetKindDirectionStore() - Получение справочника направлений списания материалов
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,                        // идентификатор направления списания материалов
     *      "title":"Списание"                // наименование направления списания материалов
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookMaterial&method=GetKindDirectionStore&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 12:12
     */
    public static function GetKindDirectionStore()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetKindDirectionStore';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $kind_direction_store = KindDirectionStore::find()
                ->asArray()
                ->all();
            if (empty($kind_direction_store)) {
                $warnings[] = $method_name . '. Справочник направлений сприсаний материалов';
            } else {
                $result = $kind_direction_store;
            }
        } catch (Throwable $exception) {
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
     * Метод SaveKindDirectionStore() - Сохранение нового направлений списания материалов
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "kind_direction_store":
     *  {
     *      "kind_direction_store_id":-1,                                    // идентификатор направления списания матераилов (-1 = новое направление списания матераилов)
     *      "title":"KIND_DIRECTION_STORE_TEST"                                // наименование направления списания материалов
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "kind_direction_store_id":5,                                    // идентификатор сохранённого направления списания матераилов
     *      "title":"KIND_DIRECTION_STORE_TEST"                                // сохранённое наименование направления списания матераилов
     * }
     * warnings:{}                                                          // массив предупреждений
     * errors:{}                                                            // массив ошибок
     * status:1                                                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookMaterial&method=SaveKindDirectionStore&subscribe=&data={"kind_direction_store":{"kind_direction_store_id":-1,"title":"KIND_DIRECTION_STORE_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 11:59
     */
    public static function SaveKindDirectionStore($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveKindCrash';
        $chat_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_direction_store'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $kind_direction_store_id = $post_dec->kind_direction_store->kind_direction_store_id;
            $title = $post_dec->kind_direction_store->title;
            $kind_direction_store = KindDirectionStore::findOne(['id' => $kind_direction_store_id]);
            if (empty($kind_direction_store)) {
                $kind_direction_store = new KindDirectionStore();
            }
            $kind_direction_store->title = $title;
            if ($kind_direction_store->save()) {
                $kind_direction_store->refresh();
                $chat_type_data['kind_direction_store_id'] = $kind_direction_store->id;
                $chat_type_data['title'] = $kind_direction_store->title;
            } else {
                $errors[] = $kind_direction_store->errors;
                throw new Exception($method_name . '. Ошибка при сохранении нового направления списания материала');
            }
            unset($kind_direction_store);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $chat_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteKindDirectionStore() - Удаление направления списания материалов
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "kind_direction_store_id": 3             // идентификатор удаляемого направления списания материалов
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookMaterial&method=DeleteKindCrash&subscribe=&data={"kind_direction_store_id":3}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 11:59
     */
    public static function DeleteKindCrash($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteChatType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_direction_store_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $kind_direction_store_id = $post_dec->kind_direction_store_id;
            $del_kind_direction_store = KindDirectionStore::deleteAll(['id' => $kind_direction_store_id]);
        } catch (Throwable $exception) {
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

    /**
     * Метод GetNomenclature() - Получение справочника номенклатуры
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,                                // идентификатор номенклатуры
     *      "title":"Анкер болтовой"                // наименование номенклатуры
     * ]
     * warnings:{}                                  // массив предупреждений
     * errors:{}                                    // массив ошибок
     * status:1                                     // статус выполнения метода
     *
     * @example 127.0.0.1/read-manager-amicum?controller=handbooks\HandbookMaterial&method=GetNomenclature&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.03.2020 10:10
     */
    public static function GetNomenclature()
    {
        $log = new LogAmicumFront("GetNomenclature");

        $result = array();
        try {
            $log->addLog("Начал выполнение метода");

            $nomenclature = Nomenclature::find()
                ->asArray()
                ->all();
            if (empty($nomenclature)) {
                $log->addLog("Справочник номенклатуры пуст");
            } else {
                $result = $nomenclature;
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод SaveNomenclature() - Сохранение новой номенклатуры
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "nomenclature":
     *  {
     *      "nomenclature_id":-1,                                    // идентификатор номеклатуры (-1 =  новая номеклатура)
     *      "title":"NOMENCLATURE_TEST"                                // наименование номеклатуры
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "nomenclature_id":16,                                    // идентификатор сохранённой номеклатуры
     *      "title":"NOMENCLATURE_TEST"                                // сохранённое наименование номеклатуры
     * }
     * warnings:{}                                                  // массив предупреждений
     * errors:{}                                                    // массив ошибок
     * status:1                                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookMaterial&method=SaveNomenclature&subscribe=&data={"nomenclature":{"nomenclature_id":-1,"title":"NOMENCLATURE_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.03.2020 10:12
     */
    public static function SaveNomenclature($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveNomenclature';
        $chat_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'nomenclature'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $nomenclature_id = $post_dec->nomenclature->nomenclature_id;
            $title = $post_dec->nomenclature->title;
            $nomenclature = Nomenclature::findOne(['id' => $nomenclature_id]);
            if (empty($nomenclature)) {
                $nomenclature = new Nomenclature();
            }
            $nomenclature->title = $title;
            if ($nomenclature->save()) {
                $nomenclature->refresh();
                $chat_type_data['nomenclature_id'] = $nomenclature->id;
                $chat_type_data['title'] = $nomenclature->title;
            } else {
                $errors[] = $nomenclature->errors;
                throw new Exception($method_name . '. Ошибка при сохранении новой номеклатуры');
            }
            unset($nomenclature);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $chat_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteNomenclature() - Удаление номенклатуры
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "nomenclature_id": 15             // идентификатор удаляемой номеклатуры
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookMaterial&method=DeleteNomenclature&subscribe=&data={"nomenclature_id":15}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.03.2020 10:14
     */
    public static function DeleteNomenclature($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteNomenclature';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'nomenclature_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $nomenclature_id = $post_dec->nomenclature_id;
            $del_nomenclature = Nomenclature::deleteAll(['id' => $nomenclature_id]);
        } catch (Throwable $exception) {
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

    // GetMaterial()      - Получение справочника материалов
    // SaveMaterial()     - Сохранение справочника материалов
    // DeleteMaterial()   - Удаление справочника материалов

    /**
     * Метод GetMaterial() - Получение справочника материалов
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
     * @example 127.0.0.1/read-manager-amicum?controller=handbooks\HandbookMaterial&method=GetMaterial&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetMaterial()
    {
        $log = new LogAmicumFront("GetMaterial");

        $result = (object)array();
        try {
            $log->addLog("Начал выполнение метода");
            $handbook_material = Material::find()
                ->asArray()
                ->indexBy('id')
                ->all();

            if (empty($handbook_material)) {
                $log->addLog("Справочник материала пуст");
            } else {
                $result = $handbook_material;
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод SaveMaterial() - Сохранение справочника материалов
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "material":
     *  {
     *      "material_id":-1,            // ключ справочника
     *      "nomenclature_id":"-1",        // ключ номенклатуры
     *      "unit_id":"-1",                // ключ единицы изменерния
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "material_id":-1,            // ключ справочника
     *      "nomenclature_id":"-1",        // ключ номенклатуры
     *      "unit_id":"-1",                // ключ единицы изменерния
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=SaveMaterial&subscribe=&data={"material":{"material_id":-1,"nomenclature_id":"-1","unit_id":"-1"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveMaterial($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $result = array();                                                                                            // Массив предупреждений
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveMaterial';
        $handbook_material_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'material'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_material_id = $post_dec->material->material_id;
            $unit_id = $post_dec->material->unit_id;
            $nomenclature_id = $post_dec->material->nomenclature_id;
            $new_handbook_material_id = Material::findOne(['id' => $handbook_material_id]);
            if (empty($new_handbook_material_id)) {
                $new_handbook_material_id = new Material();
            }
            $new_handbook_material_id->unit_id = $unit_id;
            $new_handbook_material_id->nomenclature_id = $nomenclature_id;
            if ($new_handbook_material_id->save()) {
                $new_handbook_material_id->refresh();
                $handbook_material_data['material_id'] = $new_handbook_material_id->id;
                $handbook_material_data['unit_id'] = $new_handbook_material_id->unit_id;
                $handbook_material_data['nomenclature_id'] = $new_handbook_material_id->nomenclature_id;
            } else {
                $errors[] = $new_handbook_material_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника материалов');
            }
            unset($new_handbook_material_id);
            $result = $handbook_material_data;
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeleteMaterial() - Удаление справочника материалов
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "material_id": 98             // идентификатор справочника материалов
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=DeleteMaterial&subscribe=&data={"material_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteMaterial($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $result = array();                                                                                            // Массив предупреждений
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteMaterial';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'material_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_material_id = $post_dec->material_id;
            $del_handbook_material = Material::deleteAll(['id' => $handbook_material_id]);
            $result = $post_dec;
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

}
