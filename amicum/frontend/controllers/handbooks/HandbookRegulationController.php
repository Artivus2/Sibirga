<?php

namespace frontend\controllers\handbooks;

//ob_start();

use frontend\models\ActionOperation;
use frontend\models\ActionOperationEquipment;
use frontend\models\ActionOperationPosition;
use frontend\models\Regulation;
use frontend\models\RegulationAction;

class HandbookRegulationController extends \yii\web\Controller
{

    // GetRegulationList()                      - Получение справочника регламентов
    // GetRegulationActionsList                 - Получение списка действий регламента
    // SaveRegulation()                         - Сохранение справочника регламентов
    // SaveAllRegulationActions                 - Сохранение регламента действий
    // DeleteRegulation()                       - Удаление справочника регламентов
    // GetRegulationActionsListBySituations()   - Получение списка действий регламентов по списку ситуаций

    /**
     * Метод GetRegulationList() - Получение справочника регламентов
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
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookRegulation&method=GetRegulationList&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetRegulationList()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetRegulationList';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_regulation = Regulation::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_regulation)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник регламентов пуст';
            } else {
                $result = $handbook_regulation;
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
     * Метод GetRegulationActionsList() - Получение списка действий регламента
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     *      "regulation_id":-1,              // ключ регламента
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      regulation_id: null,                            // id регламента
     *      regulation_title: '',                           // название регламента
     *      regulation_actions:                             // список действий регламента
     *          {action_id}:
     *              action_id: 1,                           // id действия
     *              action_parent_id: 1,                    // id действия родителя
     *              action_title: 'действие 1',             // название действия
     *              action_parent_end_flag: null,           // флаг первого последнего действия в регламенте 1 первое действие 2 последние действие 0 нет или не задано это обычное действие
     *              action_number: 0,                       // порядковый номер действия (либо уровень действия)
     *              action_type: 'positive',                // тип действия (positive - действие, кт было выполнено вовремя; negative - просроченное действие)
     *              x: 0,                                   // координата абсциссы карточки действия (пока не понадобилось свойство)
     *              y: 0,                                   // координата ординаты карточки действия (пока не понадобилось свойство)
     *              responsible_position_id: null,          // ответственный (id должности)
     *              regulation_time: 100,                   // регламентное время выполнения действия
     *              finish_flag_mode: 'auto',               // тип действия завершения (auto - автоматическое действие, manual - ручное)
     *              expired_indicator_flag: true,           // флаг установки индикатора просрочки действия
     *              expired_indicator_mode: 'auto',         // тип действия просрочки (auto - автоматическое действие, manual - ручное)
     *              go_to_another_regulation_flag: '0',     // флаг перехода к другому регламенту
     *              child_action_id_negative: '-1',                // ключ позитивного действия
     *              child_action_id_positive: '-1',                // ключ негативного действия
     *              go_to_another_regulation_mode: 'auto',  // тип действия перехода к другому регламенту
     *              plan_new_action_flag: '0',              // флаг планирования нового действия
     *              operations:                             // список операций
     *                  {action_operation_id}
     *                      action_operation_id: -1         // ключ привязки операции к действию
     *                      operation_id: -1                // ключ операции
     *                      operation_type: "manual"        // тип действия
     *                      equipments:
     *                          {action_operation_equipment_id}
     *                              action_operation_equipment_id: -1   // ключ привязки оборудования к операции
     *                              equipment_id: -1                    // ключ оборудования
     *                      workers:
     *                          {action_operation_position_id}
     *                              action_operation_position_id: -1    // ключ привязки должности к операции
     *                              position_id: -1                     // ключ должности
     *                              company_department_id: -1           // ключ подразделения
     *                              on_shift: 1                         // оповещать работника на смене или первого из списка на участке
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookRegulation&method=GetRegulationActionsList&subscribe=&data={"regulation_id":1}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetRegulationActionsList($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetRegulationActionsList';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'regulation_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $regulation_id = $post_dec->regulation_id;

            $regulation_actions = Regulation::find()
                ->with('regulationActions.actionOperations.actionOperationEquipments')
                ->with('regulationActions.actionOperations.actionOperationPositions')
                ->where(['regulation.id' => $regulation_id])
                ->all();
            foreach ($regulation_actions as $regulation_action) {
                $regulation_action_list['regulation_id'] = $regulation_action->id;
                $regulation_action_list['regulation_title'] = $regulation_action->title;
                $regulation_action_list['situation_id'] = $regulation_action->situation_id;

                if ($regulation_action->regulationActions) {
                    foreach ($regulation_action->regulationActions as $action) {
                        /**
                         *              action_id: 1,                           // id действия
                         *              action_parent_id: 1,                    // id действия родителя
                         *              action_title: 'действие 1',             // название действия
                         *              action_parent_end_flag: null,           // флаг первого последнего действия в регламенте 1 первое действие 2 последние действие 0 нет или не задано это обычное действие
                         *              action_number: 0,                       // порядковый номер действия (либо уровень действия)
                         *              action_type: 'positive',                // тип действия (positive - действие, кт было выполнено вовремя; negative - просроченное действие)
                         *              x: 0,                                   // координата абсциссы карточки действия (пока не понадобилось свойство)
                         *              y: 0,                                   // координата ординаты карточки действия (пока не понадобилось свойство)
                         *              responsible_position_id: null,          // ответственный (id должности)
                         *              regulation_time: 100,                   // регламентное время выполнения действия
                         *              finish_flag_mode: 'auto',               // тип действия завершения (auto - автоматическое действие, manual - ручное)
                         *              expired_indicator_flag: true,           // флаг установки индикатора просрочки действия
                         *              expired_indicator_mode: 'auto',         // тип действия просрочки (auto - автоматическое действие, manual - ручное)
                         *              go_to_another_regulation_flag: '0',     // флаг перехода к другому регламенту
                         *              go_to_another_regulation_mode: 'auto',  // тип действия перехода к другому регламенту
                         *              plan_new_action_flag: '0',              // флаг планирования нового действия
                         *              operations: {},                         // список операций
                         */
                        $regulation_action_list['regulation_actions'][$action->id]['action_id'] = $action->id;
                        $regulation_action_list['regulation_actions'][$action->id]['action_parent_id'] = $action->parent_id;
                        $regulation_action_list['regulation_actions'][$action->id]['action_title'] = $action->title;
                        $regulation_action_list['regulation_actions'][$action->id]['action_parent_end_flag'] = $action->action_parent_end_flag;
                        $regulation_action_list['regulation_actions'][$action->id]['action_number'] = $action->action_number;
                        $regulation_action_list['regulation_actions'][$action->id]['action_type'] = $action->action_type;
                        $regulation_action_list['regulation_actions'][$action->id]['x'] = $action->x;
                        $regulation_action_list['regulation_actions'][$action->id]['y'] = $action->y;
                        $regulation_action_list['regulation_actions'][$action->id]['responsible_position_id'] = $action->responsible_position_id;
                        $regulation_action_list['regulation_actions'][$action->id]['regulation_time'] = $action->regulation_time;
                        $regulation_action_list['regulation_actions'][$action->id]['regulation_exchange_id'] = $action->regulation_exchange_id;
                        $regulation_action_list['regulation_actions'][$action->id]['finish_flag_mode'] = $action->finish_flag_mode;
                        $regulation_action_list['regulation_actions'][$action->id]['expired_indicator_flag'] = $action->expired_indicator_flag;
                        $regulation_action_list['regulation_actions'][$action->id]['expired_indicator_mode'] = $action->expired_indicator_mode;
                        $regulation_action_list['regulation_actions'][$action->id]['go_to_another_regulation_flag'] = $action->go_to_another_regulation_flag;
                        $regulation_action_list['regulation_actions'][$action->id]['go_to_another_regulation_mode'] = $action->go_to_another_regulation_mode;
                        $regulation_action_list['regulation_actions'][$action->id]['child_action_id_positive'] = $action->child_action_id_positive;
                        $regulation_action_list['regulation_actions'][$action->id]['child_action_id_negative'] = $action->child_action_id_negative;
                        $regulation_action_list['regulation_actions'][$action->id]['plan_new_action_flag'] = $action->plan_new_action_flag;
//                        $regulation_action_list['regulation_actions'][$action->id]['operations'] = array();
                        if ($action->actionOperations) {
                            foreach ($action->actionOperations as $operation) {
                                $operation_id = $operation->operation_id;
                                $regulation_action_list['regulation_actions'][$action->id]['operations'][$operation->id]['action_operation_id'] = $operation->id;
                                $regulation_action_list['regulation_actions'][$action->id]['operations'][$operation->id]['operation_id'] = $operation_id;
                                $regulation_action_list['regulation_actions'][$action->id]['operations'][$operation->id]['operation_type'] = $operation->operation_type;
                                if ($operation->actionOperationEquipments) {
                                    foreach ($operation->actionOperationEquipments as $action_operation_equipment) {
                                        $action_operation_equipment_id = $action_operation_equipment->id;
                                        $regulation_action_list['regulation_actions'][$action->id]['operations'][$operation->id]['equipments'][$action_operation_equipment_id]['action_operation_equipment_id'] = $action_operation_equipment_id;
                                        $regulation_action_list['regulation_actions'][$action->id]['operations'][$operation->id]['equipments'][$action_operation_equipment_id]['equipment_id'] = $action_operation_equipment->equipment_id;
                                    }
                                }
                                if ($operation->actionOperationPositions) {
                                    foreach ($operation->actionOperationPositions as $action_operation_position) {
                                        $action_operation_position_id = $action_operation_position->id;
                                        $regulation_action_list['regulation_actions'][$action->id]['operations'][$operation->id]['workers'][$action_operation_position_id]['action_operation_position_id'] = $action_operation_position_id;
                                        $regulation_action_list['regulation_actions'][$action->id]['operations'][$operation->id]['workers'][$action_operation_position_id]['position_id'] = $action_operation_position->position_id;
                                        $regulation_action_list['regulation_actions'][$action->id]['operations'][$operation->id]['workers'][$action_operation_position_id]['company_department_id'] = $action_operation_position->company_department_id;
                                        $regulation_action_list['regulation_actions'][$action->id]['operations'][$operation->id]['workers'][$action_operation_position_id]['on_shift'] = $action_operation_position->on_shift;
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $regulation_action_list['regulation_actions'] = (object)array();
                }
            }

            if (!isset($regulation_action_list)) {
                $result = (object)array();
                $warnings[] = $method_name . '. регламент пуст';
            } else {

                foreach ($regulation_action_list['regulation_actions'] as $action) {
                    if (!isset($action['operations'])) {
                        $regulation_action_list['regulation_actions'][$action['action_id']]['operations'] = (object)array();
                    } else {
                        foreach ($action['operations'] as $operation) {
                            if (!isset($operation['workers'])) {
                                $regulation_action_list['regulation_actions'][$action['action_id']]['operations'][$operation['action_operation_id']]['workers'] = (object)array();
                            }
                            if (!isset($operation['equipments'])) {
                                $regulation_action_list['regulation_actions'][$action['action_id']]['operations'][$operation['action_operation_id']]['equipments'] = (object)array();
                            }

                        }
                    }

                }
                $result = $regulation_action_list;
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
     * Метод SaveRegulation() - Сохранение справочника регламентов
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "regulation":
     *  {
     *      "regulation_id":-1,             // ключ справочника регламентов
     *      "title":"ACTION",               // название регламента
     *      "situation_id":"-1",            // ключ ситуации к которой привязан регламент
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "regulation_id":-1,             // ключ справочника регламентов
     *      "title":"ACTION",               // название регламента
     *      "situation_id":"-1",            // ключ ситуации к которой привязан регламент
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookRegulation&method=SaveRegulation&subscribe=&data={"regulation":{"regulation_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveRegulation($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // Массив ошибок
        $method_name = 'SaveRegulation';
        $handbook_regulation_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'regulation_id') or
                !property_exists($post_dec, 'regulation_title') or
                !property_exists($post_dec, 'situation_id')
            )                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_regulation_id = $post_dec->regulation_id;
            $regulation_title = $post_dec->regulation_title;
            $situation_id = $post_dec->situation_id;
            // проверка на существование регламента с такой ситуацией
            $handbook_regulation_id_with_situation_id = Regulation::findOne(['situation_id' => $situation_id]);
            if($handbook_regulation_id_with_situation_id) {
                throw new \Exception($method_name . '. Регламент с такой ситуацией уже существует в регламенте "'.$handbook_regulation_id_with_situation_id->title . '"');
            }

            $new_handbook_regulation_id = Regulation::findOne(['id' => $handbook_regulation_id]);
            if (empty($new_handbook_regulation_id)) {
                $new_handbook_regulation_id = new Regulation();
            }
            $new_handbook_regulation_id->title = $regulation_title;
            $new_handbook_regulation_id->situation_id = $situation_id;
            $new_handbook_regulation_id->object_id = 35;
            if ($new_handbook_regulation_id->save()) {
                $new_handbook_regulation_id->refresh();
                $handbook_regulation_data = $post_dec;
                $handbook_regulation_data->regulation_id = $new_handbook_regulation_id->id;
            } else {
                $errors[] = $new_handbook_regulation_id->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении справочника регламентов');
            }
            unset($new_handbook_regulation_id);
        } catch (\Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_regulation_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SaveAllRegulationActions - Сохранение регламента действий
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookRegulation&method=SaveAllRegulationActions&subscribe=&data={"regulation":{"regulation_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveAllRegulationActions($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $result = array();                                                                                            // Массив предупреждений
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveAllRegulationActions';
        $handbook_regulation_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
            !property_exists($post_dec, 'regulation')
            )                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $regulation = $post_dec->regulation;
            $delete_regulation_action_ids = $regulation->delete_regulation_action_ids;

            $new_handbook_regulation = Regulation::findOne(['id' => $regulation->regulation_id]);
            if (empty($new_handbook_regulation)) {
                $new_handbook_regulation = new Regulation();
                $new_handbook_regulation->title = $regulation->regulation_title;
                $new_handbook_regulation->situation_id = $regulation->situation_id;
                $new_handbook_regulation->object_id = 35;
                if ($new_handbook_regulation->save()) {
                    $new_handbook_regulation->refresh();
                } else {
                    $errors[] = $new_handbook_regulation->errors;
                    throw new \Exception($method_name . '. Ошибка при сохранении справочника регламентов');
                }
            }
            $regulation_id = $new_handbook_regulation->id;
            $regulation->regulation_id = $regulation_id;
            RegulationAction::deleteAll(['id' => $delete_regulation_action_ids]);

            $regulation->delete_regulation_action_ids=[];



            foreach ($regulation->regulation_actions as $action) {
                $warnings[] = $action;
                unset($new_regulation_action);
                $new_regulation_action = RegulationAction::findOne(['id' => $action->action_id]);
                if(!$new_regulation_action) {
                    $new_regulation_action = new RegulationAction();
                }
                $new_regulation_action->regulation_id = $regulation_id;
                $new_regulation_action->action_parent_end_flag = $action->action_parent_end_flag;
                $new_regulation_action->regulation_exchange_id = $action->regulation_exchange_id;
                $new_regulation_action->title = $action->action_title;
                $new_regulation_action->action_number = $action->action_number;
                $new_regulation_action->action_type = $action->action_type;
                $new_regulation_action->x = $action->x;
                $new_regulation_action->y = $action->y;
                $new_regulation_action->responsible_position_id = $action->responsible_position_id;
                $new_regulation_action->regulation_time = $action->regulation_time;
                $new_regulation_action->expired_indicator_flag = $action->expired_indicator_flag;
                $new_regulation_action->expired_indicator_mode = $action->expired_indicator_mode;
                $new_regulation_action->finish_flag_mode = $action->finish_flag_mode;
                $new_regulation_action->parent_id = $action->action_parent_id;
                $new_regulation_action->go_to_another_regulation_flag = $action->go_to_another_regulation_flag;
                $new_regulation_action->go_to_another_regulation_mode = $action->go_to_another_regulation_mode;
                $new_regulation_action->child_action_id_positive = $action->child_action_id_positive;
                $new_regulation_action->child_action_id_negative = $action->child_action_id_negative;
                $new_regulation_action->plan_new_action_flag = $action->plan_new_action_flag;

                if ($new_regulation_action->save()) {
                    $new_regulation_action->refresh();
                } else {
                    $errors[] = $new_regulation_action->errors;
                    throw new \Exception($method_name . '. Ошибка при сохранении справочника действий регламента');
                }
                $action_id = $new_regulation_action->id;
                $parent_action_hand[$action->action_id]['last_action_id'] = $action->action_id;
                $parent_action_hand[$action->action_id]['new_action_id'] = $action_id;
                $action->action_id = $action_id;
                /**
                 *              operations:                             // список операций
                 *                  {action_operation_id}
                 *                      action_operation_id: -1         // ключ привязки операции к действию
                 *                      operation_id: -1                // ключ операции
                 *                      operation_type: "manual"        // тип действия
                 *                      equipments:
                 *                          {action_operation_equipment_id}
                 *                              action_operation_equipment_id: -1        // ключ привязки оборудования к операции
                 *                              equipment_id: -1        // ключ оборудования
                 *                      workers:
                 *                          {action_operation_position_id}
                 *                              action_operation_position_id: -1    // ключ привязки должности к операции
                 *                              position_id: -1                     // ключ должности
                 *                              company_department_id: -1           // ключ подразделения
                 *                              on_shift: 1                         // оповещать работника который на смене или первого на участке
                 */
                foreach ($action->operations as $operation) {
                    $new_action_operation = ActionOperation::findOne(['id' => $operation->action_operation_id]);
                    if(!$new_action_operation) {
                        $new_action_operation = new ActionOperation();
                    }
                    $new_action_operation->operation_id = $operation->operation_id;
                    $new_action_operation->operation_type = $operation->operation_type;
                    $new_action_operation->regulation_action_id = $action_id;

                    if ($new_action_operation->save()) {
                        $new_action_operation->refresh();
                    } else {
                        $errors[] = $new_action_operation->errors;
                        throw new \Exception($method_name . '. Ошибка при сохранении справочника операций действий регламента');
                    }
                    $action_operation_id = $new_action_operation->id;
                    $operation->action_operation_id = $action_operation_id;

                    foreach ($operation->equipments as $key_equipment => $equipment) {
                        if ($equipment->equipment_id) {
                            $new_action_operation_equipment = ActionOperationEquipment::findOne(['id' => $equipment->action_operation_equipment_id]);
                            if(!$new_action_operation_equipment) {
                                $new_action_operation_equipment = new ActionOperationEquipment();
                            }

                            $new_action_operation_equipment->action_operation_id = $action_operation_id;
                            $new_action_operation_equipment->equipment_id = $equipment->equipment_id;

                            if ($new_action_operation_equipment->save()) {
                                $new_action_operation_equipment->refresh();
                            } else {
                                $errors[] = $new_action_operation_equipment->errors;
                                throw new \Exception($method_name . '. Ошибка при сохранении ActionOperationEquipment');
                            }
                            $equipment->action_operation_equipment_id = $new_action_operation_equipment->id;
                        } else {
                            unset($operation->equipments->$key_equipment);
                        }
                    }

                    foreach ($operation->workers as $key_worker => $worker) {
                        if ($worker->position_id) {
                            $new_action_operation_position = ActionOperationPosition::findOne(['id' => $worker->action_operation_position_id]);
                            if(!$new_action_operation_position) {
                                $new_action_operation_position = new ActionOperationPosition();
                            }
                            $new_action_operation_position = new ActionOperationPosition();
                            $new_action_operation_position->action_operation_id = $action_operation_id;
                            $new_action_operation_position->position_id = $worker->position_id;
                            $new_action_operation_position->company_department_id = $worker->company_department_id;
                            $new_action_operation_position->on_shift = $worker->on_shift;

                            if ($new_action_operation_position->save()) {
                                $new_action_operation_position->refresh();
                            } else {
                                $errors[] = $new_action_operation_position->errors;
                                throw new \Exception($method_name . '. Ошибка при сохранении ActionOperationPosition');
                            }
                            $worker->action_operation_position_id = $new_action_operation_position->id;
                        } else {
                            unset($operation->workers->$key_worker);
                        }
                    }

//                    $operation['action_operation_id']=$action_operation_id;
                }
            }
            $warnings[] = $method_name . '. Начал обновлять айдишники родителей';
//            throw new \Exception($method_name . '. отладочный стоп');
            if (isset($parent_action_hand)) {
                $warnings[] = $method_name . '. Справочник новых / старых айдишников есть';
                foreach ($parent_action_hand as $action) {
                    $regulation_actions = RegulationAction::findAll(['parent_id' => $action['last_action_id']]);
                    foreach ($regulation_actions as $find_action) {
                        $warnings[] = $parent_action_hand[$find_action->parent_id]['new_action_id'];
                        $find_action->parent_id = $parent_action_hand[$find_action->parent_id]['new_action_id'];

                        if ($find_action->save()) {
                            $find_action->refresh();
                        } else {
                            $errors[] = $find_action->errors;
                            throw new \Exception($method_name . '. Ошибка при сохранении справочника действий регламента');
                        }
                    }

                    $regulation_actions = RegulationAction::findAll(['child_action_id_negative' => $action['last_action_id']]);
                    foreach ($regulation_actions as $find_action) {
                        $warnings[] = $parent_action_hand[$find_action->child_action_id_negative]['new_action_id'];
                        $find_action->child_action_id_negative = $parent_action_hand[$find_action->child_action_id_negative]['new_action_id'];

                        if ($find_action->save()) {
                            $find_action->refresh();
                        } else {
                            $errors[] = $find_action->errors;
                            throw new \Exception($method_name . '. Ошибка при сохранении справочника действий регламента');
                        }
                    }

                    $regulation_actions = RegulationAction::findAll(['child_action_id_positive' => $action['last_action_id']]);
                    foreach ($regulation_actions as $find_action) {
                        $warnings[] = $parent_action_hand[$find_action->child_action_id_positive]['new_action_id'];
                        $find_action->child_action_id_positive = $parent_action_hand[$find_action->child_action_id_positive]['new_action_id'];

                        if ($find_action->save()) {
                            $find_action->refresh();
                        } else {
                            $errors[] = $find_action->errors;
                            throw new \Exception($method_name . '. Ошибка при сохранении справочника действий регламента');
                        }
                    }
                }
                foreach ($regulation->regulation_actions as $action) {
                    if ($action->action_parent_id) {
                        $action->action_parent_id = $parent_action_hand[$action->action_parent_id]['new_action_id'];
                    }

                    if ($action->child_action_id_positive) {
                        $action->child_action_id_positive = $parent_action_hand[$action->child_action_id_positive]['new_action_id'];
                    }

                    if ($action->child_action_id_negative) {
                        $action->child_action_id_negative = $parent_action_hand[$action->child_action_id_negative]['new_action_id'];
                    }
                }
            } else {
                $warnings[] = $method_name . '. Справочник родителей пуст';
            }

            $result = $regulation;

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
     * Метод DeleteRegulation() - Удаление справочника регламентов
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "regulation_id": 98             // идентификатор справочника регламентов
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookRegulation&method=DeleteRegulation&subscribe=&data={"regulation_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteRegulation($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteRegulation';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'regulation_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_regulation_id = $post_dec->regulation_id;
            $del_handbook_regulation = Regulation::deleteAll(['id' => $handbook_regulation_id]);
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

    /**
     * Метод GetRegulationActionsListBySituations() - Получение списка действий регламентов по списку ситуаций
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     *      "situations":[],              // массив ситуаций
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *  {regulation_id}
     *      regulation_id: null,                            // id регламента
     *      regulation_title: '',                           // название регламента
     *      regulation_actions:                             // список действий регламента
     *          {action_id}:
     *              action_id: 1,                           // id действия
     *              action_parent_id: 1,                    // id действия родителя
     *              action_title: 'действие 1',             // название действия
     *              action_parent_end_flag: null,           // флаг первого последнего действия в регламенте 1 - первое действие, 2 - последние действие, 0 нет или не задано это обычное действие
     *              action_number: 0,                       // порядковый номер действия (либо уровень действия)
     *              action_type: 'positive',                // тип действия (positive - действие, кт было выполнено вовремя; negative - просроченное действие)
     *              x: 0,                                   // координата абсциссы карточки действия (пока не понадобилось свойство)
     *              y: 0,                                   // координата ординаты карточки действия (пока не понадобилось свойство)
     *              responsible_position_id: null,          // ответственный (id должности)
     *              regulation_time: 100,                   // регламентное время выполнения действия (в минутах)
     *              finish_flag_mode: 'auto',               // тип действия завершения (auto - автоматическое действие, manual - ручное)
     *              expired_indicator_flag: true,           // флаг установки индикатора просрочки действия
     *              expired_indicator_mode: 'auto',         // тип действия просрочки (auto - автоматическое действие, manual - ручное)
     *              go_to_another_regulation_flag: '0',     // флаг перехода к другому регламенту
     *              child_action_id_negative: '-1',                // ключ позитивного действия
     *              child_action_id_positive: '-1',                // ключ негативного действия
     *              go_to_another_regulation_mode: 'auto',  // тип действия перехода к другому регламенту
     *              plan_new_action_flag: '0',              // флаг планирования нового действия
     *              operations:                             // список операций
     *                  {action_operation_id}
     *                      action_operation_id: -1         // ключ привязки операции к действию
     *                      operation_id: -1                // ключ операции
     *                      operation_type: "manual"        // тип действия
     *                      equipments:
     *                          {action_operation_equipment_id}
     *                              action_operation_equipment_id: -1        // ключ привязки оборудования к операции
     *                              equipment_id: -1        // ключ оборудования
     *                      workers:
     *                          {action_operation_position_id}
     *                              action_operation_position_id: -1        // ключ привязки должности к операции
     *                              position_id: -1         // ключ должности
     *                              company_department_id: -1         // ключ подразделения
     *                              on_shift: 1         // ключ подразделения
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetRegulationActionsListBySituations($situations)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetRegulationActionsListBySituations';
        $warnings[] = $method_name . '. Начало метода';
        try {

            $regulation_actions = Regulation::find()
                ->with('regulationActions.actionOperations.actionOperationEquipments')
                ->with('regulationActions.actionOperations.actionOperationPositions')
                ->where(['regulation.situation_id' => $situations])
                ->all();
            foreach ($regulation_actions as $regulation_action) {
                $regulation_id = $regulation_action->id;
                $regulation_action_list[$regulation_id]['regulation_id'] = $regulation_action->id;
                $regulation_action_list[$regulation_id]['regulation_title'] = $regulation_action->title;
                $regulation_action_list[$regulation_id]['situation_id'] = $regulation_action->situation_id;

                if ($regulation_action->regulationActions) {
                    foreach ($regulation_action->regulationActions as $action) {
                        /**
                         *              action_id: 1,                           // id действия
                         *              action_parent_id: 1,                    // id действия родителя
                         *              action_title: 'действие 1',             // название действия
                         *              action_parent_end_flag: null,           // флаг первого последнего действия в регламенте 1 первое действие 2 последние действие 0 нет или не задано это обычное действие
                         *              action_number: 0,                       // порядковый номер действия (либо уровень действия)
                         *              action_type: 'positive',                // тип действия (positive - действие, кт было выполнено вовремя; negative - просроченное действие)
                         *              x: 0,                                   // координата абсциссы карточки действия (пока не понадобилось свойство)
                         *              y: 0,                                   // координата ординаты карточки действия (пока не понадобилось свойство)
                         *              responsible_position_id: null,          // ответственный (id должности)
                         *              regulation_time: 100,                   // регламентное время выполнения действия
                         *              finish_flag_mode: 'auto',               // тип действия завершения (auto - автоматическое действие, manual - ручное)
                         *              expired_indicator_flag: true,           // флаг установки индикатора просрочки действия
                         *              expired_indicator_mode: 'auto',         // тип действия просрочки (auto - автоматическое действие, manual - ручное)
                         *              go_to_another_regulation_flag: '0',     // флаг перехода к другому регламенту
                         *              go_to_another_regulation_mode: 'auto',  // тип действия перехода к другому регламенту
                         *              plan_new_action_flag: '0',              // флаг планирования нового действия
                         *              operations: {},                         // список операций
                         */
                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['action_id'] = $action->id;
                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['action_parent_id'] = $action->parent_id;
                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['action_title'] = $action->title;
                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['action_parent_end_flag'] = $action->action_parent_end_flag;
                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['action_number'] = $action->action_number;
                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['action_type'] = $action->action_type;
                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['x'] = $action->x;
                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['y'] = $action->y;
                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['responsible_position_id'] = $action->responsible_position_id;
                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['regulation_time'] = $action->regulation_time;
                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['regulation_exchange_id'] = $action->regulation_exchange_id;
                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['finish_flag_mode'] = $action->finish_flag_mode;
                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['expired_indicator_flag'] = $action->expired_indicator_flag;
                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['expired_indicator_mode'] = $action->expired_indicator_mode;
                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['go_to_another_regulation_flag'] = $action->go_to_another_regulation_flag;
                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['go_to_another_regulation_mode'] = $action->go_to_another_regulation_mode;
                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['child_action_id_positive'] = $action->child_action_id_positive;
                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['child_action_id_negative'] = $action->child_action_id_negative;
                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['plan_new_action_flag'] = $action->plan_new_action_flag;
//                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['operations'] = array();
                        if ($action->actionOperations) {
                            foreach ($action->actionOperations as $operation) {
                                $operation_id = $operation->operation_id;
                                $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['operations'][$operation->id]['action_operation_id'] = $operation->id;
                                $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['operations'][$operation->id]['operation_id'] = $operation_id;
                                $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['operations'][$operation->id]['operation_type'] = $operation->operation_type;
                                if ($operation->actionOperationEquipments) {
                                    foreach ($operation->actionOperationEquipments as $action_operation_equipment) {
                                        $action_operation_equipment_id = $action_operation_equipment->id;
                                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['operations'][$operation->id]['equipments'][$action_operation_equipment_id]['action_operation_equipment_id'] = $action_operation_equipment_id;
                                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['operations'][$operation->id]['equipments'][$action_operation_equipment_id]['equipment_id'] = $action_operation_equipment->equipment_id;
                                    }
                                }
                                if ($operation->actionOperationPositions) {
                                    foreach ($operation->actionOperationPositions as $action_operation_position) {
                                        $action_operation_position_id = $action_operation_position->id;
                                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['operations'][$operation->id]['workers'][$action_operation_position_id]['action_operation_position_id'] = $action_operation_position_id;
                                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['operations'][$operation->id]['workers'][$action_operation_position_id]['position_id'] = $action_operation_position->position_id;
                                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['operations'][$operation->id]['workers'][$action_operation_position_id]['company_department_id'] = $action_operation_position->company_department_id;
                                        $regulation_action_list[$regulation_id]['regulation_actions'][$action->id]['operations'][$operation->id]['workers'][$action_operation_position_id]['on_shift'] = $action_operation_position->on_shift;
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $regulation_action_list[$regulation_id]['regulation_actions'] = (object)array();
                }
            }

            if (!isset($regulation_action_list)) {
                $result = (object)array();
                $warnings[] = $method_name . '. регламент пуст';
            } else {
                foreach ($regulation_action_list as $regulation_action) {
                    $regulation_id = $regulation_action['regulation_id'];
                    foreach ($regulation_action['regulation_actions'] as $action) {
                        if (!isset($action['operations'])) {
                            $regulation_action_list[$regulation_id]['regulation_actions'][$action['action_id']]['operations'] = (object)array();
                        } else {
                            foreach ($action['operations'] as $operation) {
                                if (!isset($operation['workers'])) {
                                    $regulation_action_list[$regulation_id]['regulation_actions'][$action['action_id']]['operations'][$operation['action_operation_id']]['workers'] = (object)array();
                                }
                                if (!isset($operation['equipments'])) {
                                    $regulation_action_list[$regulation_id]['regulation_actions'][$action['action_id']]['operations'][$operation['action_operation_id']]['equipments'] = (object)array();
                                }

                            }
                        }

                    }
                }
                $result = $regulation_action_list;
            }
        } catch (\Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }
}
