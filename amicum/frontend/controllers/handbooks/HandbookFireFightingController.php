<?php

namespace frontend\controllers\handbooks;

use frontend\models\FireFightingEquipment;

class HandbookFireFightingController extends \yii\web\Controller
{
    // GetFireFightingEquipment                 - Получение справочника средств пожаротушения
    // SaveFireFightingEquipment                - Сохранение новых средств пожаротушения
    // DeleteFireFightingEquipment               - Удаление средства пожаротушения


    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetFireFightingEquipment() - Получение справочника средств пожаротушения
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,					                // идентификатор средства пожаротушения
     *      "title":"Тестовое средство",				// наименование средства пожаротушения
     *      "unit_id":"81"				                // идентификатор единицы измерения средства пожаротушения
     * ]
     * warnings:{}                                      // массив предупреждений
     * errors:{}                                        // массив ошибок
     * status:1                                         // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookFireFighting&method=GetFireFightingEquipment&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 14:42
     */
    public static function GetFireFightingEquipment()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetFireFightingEquipment';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $fire_fighting_equipment = FireFightingEquipment::find()
                ->asArray()
                ->all();
            if(empty($fire_fighting_equipment)){
                $warnings[] = $method_name.'. Справочник средств пожаротушения пуст';
            }else{
                $result = $fire_fighting_equipment;
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
     * Метод SaveFireFightingEquipment() - Сохранение новых средств пожаротушения
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "fire_fighting_equipment":
     *  {
     *      "fire_fighting_equipment_id":-1,					                // идентификатор средства пожаротушения (-1 = новое средство пожаротушения)
     *      "title":"FIRE_FIGHTING_EQUIPMENT_TEST",				                // наименование средства пожаротушения
     *      "unit_id":81				                                        // идентификатор единицы измерения средства пожаротушения
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "reason_check_knowledge_id":5,					                    // идентификатор сохранённого средства пожаротушения
     *      "title":"FIRE_FIGHTING_EQUIPMENT_TEST",				                // сохранённое наименование средства пожаротушения
     *      "unit_id":81				                                        // идентификатор сохранённой единицы измерения средства пожаротушения
     * }
     * warnings:{}                                                              // массив предупреждений
     * errors:{}                                                                // массив ошибок
     * status:1                                                                 // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookFireFighting&method=SaveFireFightingEquipment&subscribe=&data={"fire_fighting_equipment":{"fire_fighting_equipment_id":-1,"title":"FIRE_FIGHTING_EQUIPMENT_TEST","unit_id":81}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 14:44
     */
    public static function SaveFireFightingEquipment($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveFireFightingEquipment';
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
            if (!property_exists($post_dec, 'fire_fighting_equipment'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $fire_fighting_equipment_id = $post_dec->fire_fighting_equipment->fire_fighting_equipment_id;
            $title = $post_dec->fire_fighting_equipment->title;
            $unit_id = $post_dec->fire_fighting_equipment->unit_id;
            $fireFightingEquipment = FireFightingEquipment::findOne(['id'=>$fire_fighting_equipment_id]);
            if (empty($fireFightingEquipment)){
                $fireFightingEquipment = new FireFightingEquipment();
            }
            $fireFightingEquipment->title = $title;
            $fireFightingEquipment->unit_id = $unit_id;
            if ($fireFightingEquipment->save()){
                $fireFightingEquipment->refresh();
                $chat_type_data['fire_fighting_equipment_id'] = $fireFightingEquipment->id;
                $chat_type_data['title'] = $fireFightingEquipment->title;
                $chat_type_data['unit_id'] = $fireFightingEquipment->unit_id;
            }else{
                $errors[] = $fireFightingEquipment->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового средства пожаротушения');
            }
            unset($fireFightingEquipment);
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
     * Метод DeleteFireFightingEquipment() - Удаление средства пожаротушения
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "fire_fighting_equipment_id": 36             // идентификатор удаляемого средства пожаротушения
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookFireFighting&method=DeleteFireFightingEquipment&subscribe=&data={"fire_fighting_equipment_id":36}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 14:49
     */
    public static function DeleteFireFightingEquipment($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteFireFightingEquipment';
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
            if (!property_exists($post_dec, 'fire_fighting_equipment_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $fire_fighting_equipment_id = $post_dec->fire_fighting_equipment_id;
            $del_fire_fighting_equipment = FireFightingEquipment::deleteAll(['id'=>$fire_fighting_equipment_id]);
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
