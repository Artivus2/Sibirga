<?php

namespace frontend\controllers\handbooks;

use frontend\models\InstructionPb;
use frontend\models\KindFirePreventionInstruction;
use frontend\models\TypeInstructionPb;
use yii\base\Exception;

class HandbookInstructionPBController extends \yii\web\Controller
{

    // GetInstructionPB                                 - Получение списка всех инструктажей
    // actionAddBriefing                                - функция по сохранению нового инструктажа
    // GetKindFirePreventionInstruction                 - Получение справочника видов противопожарных инструктажей
    // SaveKindFirePreventionInstruction                - Сохранение нового вида противопожарного инструктажа
    // DeleteKindFirePreventionInstruction              - Удаление вида противопожарного инструктажа


// GetHandbookInstructionPb()      - Получение справочника инструктажей ПБ
// SaveInstructionPb()     - Сохранение справочника инструктажей ПБ
// DeleteInstructionPb()   - Удаление справочника инструктажей ПБ

// GetTypeInstructionPb()      - Получение справочника типов инструктажей ПБ
// SaveTypeInstructionPb()     - Сохранение справочника типов инструктажей ПБ
// DeleteTypeInstructionPb()   - Удаление справочника типов инструктажей ПБ


    /**
     * Метод GetInstructionPB() - Получение списка всех инструктажей
     * @return array - [instruction_id]
     *                          instruction_id:
     *                          instruction_title:
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\HandbookInstructionPB&method=GetInstructionPB&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 13.08.2019 7:59
     */
    public static function GetInstructionPB()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $instruction_pb = array();                                                                                         // Промежуточный результирующий массив
        try {
            $instruction_pb = InstructionPb::find()
                ->select(['id', 'title'])
                ->orderBy('title')
                ->indexBy('id')
                ->asArray()
                ->all();
        } catch (\Throwable $exception) {
            $errors[] = 'GetParameters. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $result = $instruction_pb;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // actionAddBriefing     - функция по сохранению нового инструктажа
    public static function AddBriefing($data_post = null)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $list_worker = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'actionAddBriefing. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetListWorker. Данные с фронта не получены');
            }
            $warnings[] = 'actionAddBriefing. Данные успешно переданы';
            $warnings[] = 'actionAddBriefing. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'actionAddBriefing. Декодировал входные параметры';
            if (
            !(property_exists($post_dec, 'new_briefing_title'))
            ) {
                throw new Exception('actionAddBriefing. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей
            $new_briefing_title = $post_dec->new_briefing_title;
            $warnings[] = 'actionAddBriefing. Данные с фронта получены';

            if ($new_briefing_title == "") {
                throw new Exception('actionAddBriefing. Передано пустое название инструктажа');
            }
            $new_briefing = InstructionPb::findOne(['title' => $new_briefing_title]);

            if ($new_briefing) {
                throw new Exception('actionAddBriefing. Введенный инстуктаж уже существует');
            }
            $new_briefing = new InstructionPb();
            $new_briefing->title = $new_briefing_title;
            $new_briefing->repeat = "каждую смену";
            $new_briefing->type_instruction_pb_id = 1;
            if ($new_briefing->save()) {
                $new_briefing->refresh();
                $briefingObj['id'] = $new_briefing->id;
                $briefingObj['title'] = $new_briefing_title;
            } else {
                $errors[] = $new_briefing->errors;
                throw new Exception('actionAddBriefing. Не удалось сохранить инструктаж модель InstructionPb');
            }

        } catch (\Throwable $exception) {
            $errors[] = 'actionAddBriefing. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'actionAddBriefing. Конец метода';
        if (isset($briefingObj)) {
            $result = $briefingObj;
        } else {
            $result = (object)array();
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetKindFirePreventionInstruction() - Получение справочника видов противопожарных инструктажей
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,                        // идентификатор вида противопожарного инструктажа
     *      "title":"Первичный"                // наименование вида противопожарного инструктажа
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInstructionPB&method=GetKindFirePreventionInstruction&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 10:01
     */
    public static function GetKindFirePreventionInstruction()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetKindFirePreventionInstruction';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $kind_fire_prevention_instruction = KindFirePreventionInstruction::find()
                ->asArray()
                ->all();
            if (empty($kind_fire_prevention_instruction)) {
                $warnings[] = $method_name . '. Справочник видов противопожарных инструктажей пуст';
            } else {
                $result = $kind_fire_prevention_instruction;
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
     * Метод SaveKindFirePreventionInstruction() - Сохранение нового вида противопожарного инструктажа
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "kind_fire_prevention_instruction":
     *  {
     *      "kind_fire_prevention_instruction_id":-1,                                    // идентификатор вида противопожарного инструктажа (-1 =  новый вид противопожарного инструктажа)
     *      "title":"KIND_FIRE_PREVENTION_INSTRUCTION_TEST"                                 наименование вида противопожарного инструктажа
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "kind_fire_prevention_instruction_id":5,                                    // идентификатор сохранённого вида противопожарного инструктажа
     *      "title":"KIND_FIRE_PREVENTION_INSTRUCTION_TEST"                                // сохранённое наименование вида противопожарного инструктажа
     * }
     * warnings:{}                                                                      // массив предупреждений
     * errors:{}                                                                        // массив ошибок
     * status:1                                                                         // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInstructionPB&method=SaveKindFirePreventionInstruction&subscribe=&data={"kind_fire_prevention_instruction":{"kind_fire_prevention_instruction_id":-1,"title":"KIND_FIRE_PREVENTION_INSTRUCTION_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 10:05
     */
    public static function SaveKindFirePreventionInstruction($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveKindFirePreventionInstruction';
        $chat_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_fire_prevention_instruction'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $kind_fire_prevention_instruction_id = $post_dec->kind_fire_prevention_instruction->kind_fire_prevention_instruction_id;
            $title = $post_dec->kind_fire_prevention_instruction->title;
            $kind_fire_prevention_instruction = KindFirePreventionInstruction::findOne(['id' => $kind_fire_prevention_instruction_id]);
            if (empty($kind_fire_prevention_instruction)) {
                $kind_fire_prevention_instruction = new KindFirePreventionInstruction();
            }
            $kind_fire_prevention_instruction->title = $title;
            if ($kind_fire_prevention_instruction->save()) {
                $kind_fire_prevention_instruction->refresh();
                $chat_type_data['kind_fire_prevention_instruction_id'] = $kind_fire_prevention_instruction->id;
                $chat_type_data['title'] = $kind_fire_prevention_instruction->title;
            } else {
                $errors[] = $kind_fire_prevention_instruction->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового типа чата');
            }
            unset($kind_fire_prevention_instruction);
        } catch (\Throwable $exception) {
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
     * Метод DeleteKindFirePreventionInstruction() - Удаление вида противопожарного инструктажа
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "kind_fire_prevention_instruction_id": 17             // идентификатор удаляемого вида противопожарного инструктажа
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInstructionPB&method=DeleteKindFirePreventionInstruction&subscribe=&data={"kind_fire_prevention_instruction_id":17}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 10:08
     */
    public static function DeleteKindFirePreventionInstruction($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteKindFirePreventionInstruction';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_fire_prevention_instruction_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $kind_fire_prevention_instruction_id = $post_dec->kind_fire_prevention_instruction_id;
            $del_kind_fire_prevention_instruction = KindFirePreventionInstruction::deleteAll(['id' => $kind_fire_prevention_instruction_id]);
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


    // GetHandbookInstructionPb()      - Получение справочника инструктажей ПБ
    // SaveInstructionPb()     - Сохранение справочника инструктажей ПБ
    // DeleteInstructionPb()   - Удаление справочника инструктажей ПБ

    /**
     * Метод GetHandbookInstructionPb() - Получение справочника инструктажей ПБ
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                                // ключ справочника
     *      "title":"ACTION",                        // название справочника
     *      "repeat":"каждую смену",                // Повторяемость инструктажа ПБ в периоде
     *      "type_instruction_pb_id":"1",            // Внешний ключ справочника типов  инструктажей
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=Briefing&method=GetHandbookInstructionPb&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetHandbookInstructionPb()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetHandbookInstructionPb';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_instruction_pb = InstructionPb::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_instruction_pb)) {
                $result = (object) array();
                $warnings[] = $method_name . '. Справочник инструктажей ПБ пуст';
            } else {
                $result = $handbook_instruction_pb;
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
     * Метод SaveInstructionPb() - Сохранение справочника инструктажей ПБ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "instruction_pb":
     *  {
     *      "instruction_pb_id":-1,                    // ключ справочника
     *      "title":"ACTION",                        // название справочника
     *      "repeat":"каждую смену",                // Повторяемость инструктажа ПБ в периоде
     *      "type_instruction_pb_id":"1",            // Внешний ключ справочника типов  инструктажей
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "instruction_pb_id":-1,                    // ключ справочника
     *      "title":"ACTION",                        // название справочника
     *      "repeat":"каждую смену",                // Повторяемость инструктажа ПБ в периоде
     *      "type_instruction_pb_id":"1",            // Внешний ключ справочника типов  инструктажей
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=Briefing&method=SaveInstructionPb&subscribe=&data={"instruction_pb":{"instruction_pb_id":-1,"title":"ACTION","repeat":"каждую смену","type_instruction_pb_id":"-1"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveInstructionPb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveInstructionPb';
        $handbook_instruction_pb_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'instruction_pb'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_instruction_pb_id = $post_dec->instruction_pb->instruction_pb_id;
            $title = $post_dec->instruction_pb->title;
            $repeat = $post_dec->instruction_pb->repeat;
            $type_instruction_pb_id = $post_dec->instruction_pb->type_instruction_pb_id;
            $new_handbook_instruction_pb_id = InstructionPb::findOne(['id' => $handbook_instruction_pb_id]);
            if (empty($new_handbook_instruction_pb_id)) {
                $new_handbook_instruction_pb_id = new InstructionPb();
            }
            $new_handbook_instruction_pb_id->title = $title;
            $new_handbook_instruction_pb_id->repeat = $repeat;
            $new_handbook_instruction_pb_id->type_instruction_pb_id = $type_instruction_pb_id;
            if ($new_handbook_instruction_pb_id->save()) {
                $new_handbook_instruction_pb_id->refresh();
                $handbook_instruction_pb_data['instruction_pb_id'] = $new_handbook_instruction_pb_id->id;
                $handbook_instruction_pb_data['title'] = $new_handbook_instruction_pb_id->title;
                $handbook_instruction_pb_data['repeat'] = $new_handbook_instruction_pb_id->repeat;
                $handbook_instruction_pb_data['type_instruction_pb_id'] = $new_handbook_instruction_pb_id->type_instruction_pb_id;
            } else {
                $errors[] = $new_handbook_instruction_pb_id->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении справочника инструктажей ПБ');
            }
            unset($new_handbook_instruction_pb_id);
        } catch (\Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_instruction_pb_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteInstructionPb() - Удаление справочника инструктажей ПБ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "instruction_pb_id": 98             // идентификатор справочника инструктажей ПБ
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInstructionPB&method=DeleteInstructionPb&subscribe=&data={"instruction_pb_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteInstructionPb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteInstructionPb';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'instruction_pb_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_instruction_pb_id = $post_dec->instruction_pb_id;
            $del_handbook_instruction_pb = InstructionPb::deleteAll(['id' => $handbook_instruction_pb_id]);
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

    // GetTypeInstructionPb()      - Получение справочника типов инструктажей ПБ
    // SaveTypeInstructionPb()     - Сохранение справочника типов инструктажей ПБ
    // DeleteTypeInstructionPb()   - Удаление справочника типов инструктажей ПБ

    /**
     * Метод GetTypeInstructionPb() - Получение справочника типов инструктажей ПБ
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
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInstructionPB&method=GetTypeInstructionPb&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetTypeInstructionPb()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetTypeInstructionPb';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_type_instruction_pb = TypeInstructionPb::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_type_instruction_pb)) {
                $result = (object) array();
                $warnings[] = $method_name . '. Справочник типов инструктажей ПБ пуст';
            } else {
                $result = $handbook_type_instruction_pb;
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
     * Метод SaveTypeInstructionPb() - Сохранение справочника типов инструктажей ПБ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "type_instruction_pb":
     *  {
     *      "type_instruction_pb_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "type_instruction_pb_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInstructionPB&method=SaveTypeInstructionPb&subscribe=&data={"type_instruction_pb":{"type_instruction_pb_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveTypeInstructionPb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveTypeInstructionPb';
        $handbook_type_instruction_pb_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'type_instruction_pb'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_type_instruction_pb_id = $post_dec->type_instruction_pb->type_instruction_pb_id;
            $title = $post_dec->type_instruction_pb->title;
            $new_handbook_type_instruction_pb_id = TypeInstructionPb::findOne(['id' => $handbook_type_instruction_pb_id]);
            if (empty($new_handbook_type_instruction_pb_id)) {
                $new_handbook_type_instruction_pb_id = new TypeInstructionPb();
            }
            $new_handbook_type_instruction_pb_id->title = $title;
            if ($new_handbook_type_instruction_pb_id->save()) {
                $new_handbook_type_instruction_pb_id->refresh();
                $handbook_type_instruction_pb_data['type_instruction_pb_id'] = $new_handbook_type_instruction_pb_id->id;
                $handbook_type_instruction_pb_data['title'] = $new_handbook_type_instruction_pb_id->title;
            } else {
                $errors[] = $new_handbook_type_instruction_pb_id->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении справочника типов инструктажей ПБ');
            }
            unset($new_handbook_type_instruction_pb_id);
        } catch (\Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_type_instruction_pb_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteTypeInstructionPb() - Удаление справочника типов инструктажей ПБ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "type_instruction_pb_id": 98             // идентификатор справочника типов инструктажей ПБ
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookInstructionPB&method=DeleteTypeInstructionPb&subscribe=&data={"type_instruction_pb_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteTypeInstructionPb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteTypeInstructionPb';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'type_instruction_pb_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_type_instruction_pb_id = $post_dec->type_instruction_pb_id;
            $del_handbook_type_instruction_pb = TypeInstructionPb::deleteAll(['id' => $handbook_type_instruction_pb_id]);
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
