<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\industrial_safety;

use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Checklist;
use frontend\models\ChecklistChecking;
use frontend\models\ChecklistCheckingItem;
use frontend\models\ChecklistGroup;
use frontend\models\ChecklistItem;
use Throwable;
use Yii;
use yii\web\Controller;

class ChecklistController
{
    // SaveChecklist                    - Метод сохранения чек-листа
    // GetChecklistList                 - Метод получения списка чек-листов
    // SaveChecklistChecking            - Метод сохранения связи чек-листа с проверкой
    // GetChecklistCheckingList         - Метод получения списка связи чек-листа с проверкой
    // DeleteChecklist                  - Метод удаления чек-листа
    // DeleteChecklistChecking          - Метод удаления связи чек-листа с проверкой

    /**
     * Название метода: SaveChecklist() - Метод сохранения чек-листа
     * @param string $data_post - JSON данной структуре:
     *      "checklist":{
     *          "checklist_id":-1,
     *          "title":"Чек-лист",
     *          "json":"{}",
     *          "checklist_items":{
     *              "1":{
     *                  "checklist_items_id":-2,
     *                  "title":"Задача",
     *                  "number":1,
     *                  "description":"Описание",
     *                  "checklist_group_id":-3,
     *                  "checklist_group_title":"Группа",
     *                  "operation_id":1
     *              }
     *              "...":{}
     *          }
     *      }
     * @return array - возвращает данные по такой-же структуре
     * @package frontend\controllers\industrial_safety
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Checklist&method=SaveChecklist&subscribe=&data={"checklist":{"checklist_id":-1,"title":"Чек-лист","json":"{}","checklist_items":{"1":{"checklist_items_id":-1,"title":"Задача1","number":1,"description":"Описание1","checklist_group_id":0,"checklist_group_title":"","operation_id":1},"2":{"checklist_items_id":-2,"title":"Задача2","number":2,"description":"Описание2","checklist_group_id":-1,"checklist_group_title":"Группа1","operation_id":1},"3":{"checklist_items_id":-2,"title":"Задача3","number":3,"description":"Описание3","checklist_group_id":-1,"checklist_group_title":"Группа1","operation_id":1},"4":{"checklist_items_id":-2,"title":"Задача4","number":4,"description":"Описание4","checklist_group_id":-2,"checklist_group_title":"Группа2","operation_id":1}}}}
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Checklist&method=SaveChecklist&subscribe=&data={"checklist":{"checklist_id":1,"title":"Чек-лист","json":"{}","checklist_items":{"1":{"checklist_items_id":1,"title":"Задача1","number":1,"description":"Описание1","checklist_group_id":null,"checklist_group_title":"","operation_id":1},"2":{"checklist_items_id":2,"title":"Задача2","number":2,"description":"Описание2","checklist_group_id":1,"checklist_group_title":"Группа1","operation_id":1},"3":{"checklist_items_id":3,"title":"Задача3","number":3,"description":"Описание3","checklist_group_id":1,"checklist_group_title":"Группа1","operation_id":1},"4":{"checklist_items_id":4,"title":"Задача4","number":4,"description":"Описание4","checklist_group_id":2,"checklist_group_title":"Группа2","operation_id":1}}}}
     */
    public static function SaveChecklist($data_post = NULL)
    {
        $log = new LogAmicumFront('SaveChecklist');
        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'checklist') || $post->checklist == '' ||
                !property_exists($post->checklist, 'checklist_id') || $post->checklist->checklist_id == '' ||
                !property_exists($post->checklist, 'title') || $post->checklist->title == '' ||
                !property_exists($post->checklist, 'checklist_items')
            ) {
                throw new Exception("Входные параметры не переданы");
            }


            $checklist = (array) $post->checklist;

            $numbers = array();
            foreach ($checklist['checklist_items'] as $checklist_item) {
                if (isset($numbers[$checklist_item->number])){
                    throw new Exception("Номера повторяются");
                }
                $numbers[$checklist_item->number] = $checklist_item->number;
            }

            $log->addLog("Получил все входные параметры");

            $model_checklist = Checklist::find()
                ->joinWith('checklistItems.checklistGroup')
                ->where(['checklist.id' => $checklist['checklist_id']])
                ->one();

            $old_groups = array();
            $old_checklist_items = array();
            if (!$model_checklist) {
                $log->addLog("Чек-листа нет в бд");
                $model_checklist = new Checklist();
            } else {
                $log->addLog("Чек-лист есть в бд");
                foreach ($model_checklist->checklistItems as $checklist_item) {
                    if ($checklist_item->checklist_group_id != null) {
                        $old_groups[$checklist_item->checklist_group_id] = $checklist_item->checklistGroup;
                    }
                    $old_checklist_items[$checklist_item->id] = $checklist_item;
                }
            }

            $model_checklist->title = $checklist['title'];
            $model_checklist->json = $checklist['json'];
            if (!$model_checklist->save()) {
                $log->addData($model_checklist->errors, '$model_checklist->errors', __LINE__);
                throw new Exception("Ошибка сохранения модели Checklist");
            }
            $checklist['checklist_id'] = $model_checklist->id;
            $log->addLog("Чек-лист $model_checklist->id сохранён в бд");

            $groups = array();
            foreach ($checklist['checklist_items'] as $checklist_item) {
                if (!$checklist_item->checklist_group_id || $checklist_item->checklist_group_id == 0) {
                    $groups[$checklist_item->checklist_group_id] = null;
                } else {
                    if (!isset($groups[$checklist_item->checklist_group_id])) {
                        if (isset($old_groups[$checklist_item->checklist_group_id])) {
                            $model_checklist_group = $old_groups[$checklist_item->checklist_group_id];
                        } else {
                            $log->addLog("Новая ChecklistGroup");
                            $model_checklist_group = new ChecklistGroup();
                        }
                        $model_checklist_group->title = $checklist_item->checklist_group_title;
                        if (!$model_checklist_group->save()) {
                            $log->addData($model_checklist_group->errors, '$model_checklist_group->errors', __LINE__);
                            throw new Exception("Ошибка сохранения модели ChecklistGroup");
                        }
                        $groups[$checklist_item->checklist_group_id] = $model_checklist_group->id;
                        $log->addLog("ChecklistGroup $model_checklist_group->id сохранена в бд");
                    }
                }
            }

            $checklist_items = array();
            foreach ($checklist['checklist_items'] as $checklist_item) {
                if (isset($old_checklist_items[$checklist_item->checklist_items_id])) {
                    $model_checklist_item = $old_checklist_items[$checklist_item->checklist_items_id];
                } else {
                    $log->addLog("Новый ChecklistItem");
                    $model_checklist_item = new ChecklistItem();
                }
                $model_checklist_item->title = $checklist_item->title;
                $model_checklist_item->number = $checklist_item->number;
                $model_checklist_item->description = $checklist_item->description;
                $model_checklist_item->checklist_id = $checklist['checklist_id'];
                $model_checklist_item->checklist_group_id = $groups[$checklist_item->checklist_group_id];
                $model_checklist_item->operation_id = $checklist_item->operation_id;
                if (!$model_checklist_item->save()) {
                    $log->addData($model_checklist_item->errors, '$model_checklist_item->errors', __LINE__);
                    throw new Exception("Ошибка сохранения модели ChecklistItem");
                }
                $checklist_item->checklist_items_id = $model_checklist_item->id;
                $checklist_item->checklist_group_id = $groups[$checklist_item->checklist_group_id];
                $checklist_items[$model_checklist_item->number] = $checklist_item;
                $log->addLog("ChecklistItem $model_checklist_item->id сохранён в бд");
            }

            ksort($checklist_items);
            $checklist['checklist_items'] = $checklist_items;

            foreach ($old_checklist_items as $checklist_item) {
                if (!isset($checklist['checklist_items'][$checklist_item['id']])){
                    if (!$checklist_item->delete()) {
                        $log->addData($checklist_item->errors, '$checklist_item->errors', __LINE__);
                        throw new Exception("Ошибка удаления модели ChecklistItem");
                    }
                    $log->addLog("Удалён ChecklistItem ".$checklist_item['id']);
                }
            }

            foreach ($old_groups as $checklist_group) {
                if (!isset($groups[$checklist_group['id']])) {
                    if (!$checklist_group->delete()) {
                        $log->addData($checklist_group->errors, '$checklist_group->errors', __LINE__);
                        throw new Exception("Ошибка удаления модели ChecklistGroup");
                    }
                    $log->addLog("Удалена ChecklistGroup ".$checklist_group['id']);
                }
            }

            $result = $checklist;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Название метода: GetChecklistList() - Метод получения списка чек-листов
     * @return array - возвращает данные следующей структуре
     *      "10":{
     *          "checklist_id":10,
     *          "title":"Чек-лист",
     *          "json":"{}",
     *          "checklist_items":{
     *              "1":{
     *                  "checklist_items_id":5,
     *                  "title":"Задача",
     *                  "number":1,
     *                  "description":"Описание",
     *                  "checklist_group_id":-3,
     *                  "checklist_group_title":"Группа",
     *                  "operation_id":1,
     *                  "operation_title":"операция",
     *                  "unit_title":"Час",
     *                  "unit_short":"ч"
     *              }
     *              "...":{}
     *          }
     *      },
     *      "...":{}
     * @package frontend\controllers\industrial_safety
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Checklist&method=GetChecklistList&subscribe=&data=
     */
    public static function GetChecklistList()
    {
        $log = new LogAmicumFront('GetChecklistList');
        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            $checklistList = Checklist::find()
                ->joinWith('checklistItems.operation.unit')
                ->joinWith('checklistItems.checklistGroup')
                ->indexBy('id')
                ->all();

            $result = self::getChecklist($checklistList);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Название метода: SaveChecklistChecking() - Метод сохранения связи чек-листа с проверкой
     * @param string $data_post - JSON данной структуре:
     *      "checklist_checking":{
     *          "checklist_checking_id":-1,
     *          "checklist_id":10,
     *          "checking_id":2753,
     *          "audit_id"231,
     *          "json":"{}",
     *          "checklist":{ - может быть пуста на входе
     *              "checklist_id":10,
     *              "title":"Чек-лист",
     *              "json":"{}",
     *              "checklist_items":{
     *                  "1":{
     *                      "checklist_items_id":-2,
     *                      "title":"Задача",
     *                      "number":1,
     *                      "description":"Описание",
     *                      "checklist_group_id":-3,
     *                      "checklist_group_title":"Группа",
     *                      "operation_id":1,
     *                      "operation_title":"операция",
     *                      "unit_title":"Час",
     *                      "unit_short":"ч",
     *                      "complete":1
     *                  }
     *                  "...":{}
     *              }
     *          }
     *      }
     * @return array - возвращает данные по такой-же структуре
     * @package frontend\controllers\industrial_safety
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Checklist&method=SaveChecklistChecking&subscribe=&data={"checklist_checking":{"checklist_checking_id":-1,"checklist_id":1,"checking_id":17640709,"json":"{}"}}
     */
    public static function SaveChecklistChecking($data_post = NULL)
    {
        $log = new LogAmicumFront('SaveChecklistChecking');
        $flag_new = false;
        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];


            if (
                !property_exists($post, 'checklist_checking') || $post->checklist_checking == '' ||
                !property_exists($post->checklist_checking, 'checklist_checking_id') || $post->checklist_checking->checklist_checking_id == '' ||
                !property_exists($post->checklist_checking, 'checklist_id') || $post->checklist_checking->checklist_id == '' ||
                !property_exists($post->checklist_checking, 'audit_id') || $post->checklist_checking->audit_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $checklist_checking = (array)$post->checklist_checking;

            $model_checklist_checking = ChecklistChecking::find()
                ->joinWith('checklistCheckingItems')
                ->where([
                    'checklist_checking.checklist_id' => $checklist_checking['checklist_id'],
                    'checklist_checking.audit_id' => $checklist_checking['audit_id']
                ])
                ->one();

            if (!$model_checklist_checking) {
                $model_checklist_checking = new ChecklistChecking;
                $model_checklist_checking->checklist_id = $checklist_checking['checklist_id'];
                $model_checklist_checking->audit_id = $checklist_checking['audit_id'];
                $flag_new = true;
            }
            if (isset($checklist_checking['checking_id'])) {
                $model_checklist_checking->checking_id = $checklist_checking['checking_id'];
            }
            if (isset($checklist_checking['json'])) {
                $model_checklist_checking->json = $checklist_checking['json'];
            }
            if (!$model_checklist_checking->save()) {
                $log->addData($model_checklist_checking->errors, '$model_checklist_checking->errors', __LINE__);
                throw new Exception("Ошибка сохранения модели ChecklistChecking");
            }
            $checklist_checking['checklist_checking_id'] = $model_checklist_checking->id;

            if ($flag_new) {
                $model_checklist = Checklist::find()
                    ->joinWith('checklistItems.operation.unit')
                    ->joinWith('checklistItems.checklistGroup')
                    ->where(['checklist.id' => $model_checklist_checking->checklist_id])
                    ->one();

                foreach ($model_checklist->checklistItems as $model_checklist_item) {
                    $model_checklist_checking_item = new ChecklistCheckingItem();
                    $model_checklist_checking_item->checklist_checking_id = $model_checklist_checking->id;
                    $model_checklist_checking_item->checklist_item_id = $model_checklist_item->id;
                    $model_checklist_checking_item->complete = 0;
                    if (!$model_checklist_checking_item->save()) {
                        $log->addData($model_checklist_checking_item->errors, '$model_checklist_checking_item->errors', __LINE__);
                        throw new Exception("Ошибка сохранения модели ChecklistCheckingItem");
                    }
                    $completes[$model_checklist_item['id']] = 0;
                }
                $checklistList[] = $model_checklist;
                $checklist = self::getChecklist($checklistList, $completes)[$model_checklist->id];
                $checklist_checking['checklist'] = $checklist;

            } else if (isset($checklist_checking['checklist']['checklist_items'])) {
                foreach ($checklist_checking['checklist']['checklist_items'] as $checklist_item) {
                    $completes[$checklist_item['id']] = $checklist_item['complete'];
                }
                foreach ($model_checklist_checking->checklistCheckingItems as $model_checklist_checking_item) {
                    $model_checklist_checking_item->complete = $completes[$model_checklist_checking_item->checklist_item_id];
                    if (!$model_checklist_checking->save()) {
                        $log->addData($model_checklist_checking_item->errors, '$model_checklist_checking_item->errors', __LINE__);
                        throw new Exception("Ошибка сохранения модели ChecklistCheckingItem");
                    }
                }
            }

            $result = $checklist_checking;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Название метода: GetChecklistCheckingList() - Метод получения списка связи чек-листа с проверкой
     * @param string $data_post - JSON {"checking_id":2753} или {"audit_id":140}
     * @return array - возвращает данные следующей структуре
     *      "1":{
     *          "checklist_checking_id":1,
     *          "checklist_id":10,
     *          "checking_id":2753,
     *          "audit_id"231,
     *          "json":"{}",
     *          "checklist":{ - может быть пуста на входе
     *              "checklist_id":10,
     *              "title":"Чек-лист",
     *              "json":"{}",
     *              "checklist_items":{
     *                  "1":{
     *                      "checklist_items_id":-2,
     *                      "title":"Задача",
     *                      "number":1,
     *                      "description":"Описание",
     *                      "checklist_group_id":-3,
     *                      "checklist_group_title":"Группа",
     *                      "operation_id":1,
     *                      "operation_title":"операция",
     *                      "unit_title":"Час",
     *                      "unit_short":"ч",
     *                      "complete":1
     *                  }
     *                  "...":{}
     *              }
     *          }
     *      }
     *      "...":{}
     * @package frontend\controllers\industrial_safety
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Checklist&method=GetChecklistCheckingList&subscribe=&data={"checking_id":17640709}
     */
    public static function GetChecklistCheckingList($data_post = NULL)
    {
        $log = new LogAmicumFront('GetChecklistCheckingList');
        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (property_exists($post, 'checking_id')) {
                $filter = ['checklist_checking.checking_id' => $post->checking_id];
            } else if (property_exists($post, 'audit_id')) {
            $filter = ['checklist_checking.audit_id' => $post->audit_id];
            } else {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $model_checklist_checkings = ChecklistChecking::find()
                ->joinWith('checklistCheckingItems')
                ->joinWith('checklist.checklistItems.operation.unit')
                ->joinWith('checklist.checklistItems.checklistGroup')
                ->where($filter)
                ->all();

            foreach ($model_checklist_checkings as $model_checklist_checking) {
                foreach ($model_checklist_checking->checklistCheckingItems as $checklist_checking_item) {
                    $completes[$checklist_checking_item->checklist_item_id] = $checklist_checking_item->complete;
                }
                $checklist_checking['checklist_checking_id'] = $model_checklist_checking->id;
                $checklist_checking['checklist_id'] = $model_checklist_checking->checklist_id;
                $checklist_checking['checking_id'] = $model_checklist_checking->checking_id;
                $checklist_checking['audit_id'] = $model_checklist_checking->audit_id;
                $checklist_checking['json'] = $model_checklist_checking->json;
                $checklistList[] = $model_checklist_checking->checklist;
                $checklist = self::getChecklist($checklistList, $completes)[$model_checklist_checking->checklist->id];
                $checklist_checking['checklist'] = $checklist;
                $result[$model_checklist_checking->id] = $checklist_checking;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    private static function getChecklist($checklistList, $completes = null)
    {
        foreach ($checklistList as $model_checklist) {
            $checklist['checklist_id'] = $model_checklist->id;
            $checklist['title'] = $model_checklist->title;
            $checklist['json'] = $model_checklist->json;

            foreach ($model_checklist->checklistItems as $model_checklist_item) {
                $checklist_item['checklist_items_id'] = $model_checklist_item->id;
                $checklist_item['title'] = $model_checklist_item->title;
                $checklist_item['number'] = $model_checklist_item->number;
                $checklist_item['description'] = $model_checklist_item->description;
                $checklist_item['checklist_group_id'] = $model_checklist_item->checklist_group_id;
                if (isset($model_checklist_item->checklistGroup)) {
                    $checklist_item['checklist_group_title'] = $model_checklist_item->checklistGroup->title;
                } else {
                    $checklist_item['checklist_group_title'] = null;
                }
                $checklist_item['operation_id'] = $model_checklist_item->operation_id;
                $checklist_item['operation_title'] = $model_checklist_item->operation->title;
                $checklist_item['unit_title'] = $model_checklist_item->operation->unit->title;
                $checklist_item['unit_short'] = $model_checklist_item->operation->unit->short;
                if (isset($completes[$model_checklist_item->id])) {
                    $checklist_item['complete'] = $completes[$model_checklist_item->id];
                }
                $checklist_items[$checklist_item['number']] = $checklist_item;
            }
            ksort($checklist_items);
            $checklist['checklist_items'] = $checklist_items;

            $result[$checklist['checklist_id']] = $checklist;
        }
        return $result;
    }

    /**
     * Название метода: DeleteChecklist - Метод удаления чек-листа
     * @param string $data_post - JSON {"checklist_id":2}
     * @return array
     * @package frontend\controllers\industrial_safety
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Checklist&method=DeleteChecklist&subscribe=&data={"checklist_id":2}
     */
    public static function DeleteChecklist($data_post = NULL)
    {
        $log = new LogAmicumFront('DeleteChecklist');
        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'checklist_id') || $post->checklist_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $checking_id = $post->checklist_id;

            $model_checklist = Checklist::find()
                ->joinWith('checklistItems.checklistGroup')
                ->where(['checklist.id' => $checking_id])
                ->one();

            foreach ($model_checklist->checklistItems as $checklist_item) {
                if ($checklist_item->checklist_group_id) {
                    $groups[$checklist_item->checklist_group_id] = $checklist_item->checklistGroup;
                }
            }
            if (!$model_checklist->delete()) {
                $log->addData($model_checklist->errors, '$model_checklist->errors', __LINE__);
                throw new Exception("Ошибка удаления модели Checklist");
            }
            $log->addLog("Удалён Checklist ".$model_checklist->id);

            foreach ($groups as $checklist_group) {
                if (!$checklist_group->delete()) {
                    $log->addData($checklist_group->errors, '$checklist_group->errors', __LINE__);
                    throw new Exception("Ошибка удаления модели ChecklistGroup");
                }
                $log->addLog("Удалён ChecklistGroup ".$checklist_group->id);
            }
            $result = $checking_id;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Название метода: DeleteChecklistChecking - Метод удаления связи чек-листа с проверкой
     * @param string $data_post - JSON {"checklist_checking_id":1}
     * @return array
     * @package frontend\controllers\industrial_safety
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Checklist&method=DeleteChecklistChecking&subscribe=&data={"checklist_checking_id":1}
     */
    public static function DeleteChecklistChecking($data_post = NULL)
    {
        $log = new LogAmicumFront('DeleteChecklistChecking');
        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'checklist_checking_id') || $post->checklist_checking_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $checklist_checking_id = $post->checklist_checking_id;

            $model_checklist_checking = ChecklistChecking::findOne(['id' => $checklist_checking_id]);

            if (!$model_checklist_checking->delete()) {
                $log->addData($model_checklist_checking->errors, '$model_checklist_checking->errors', __LINE__);
                throw new Exception("Ошибка удаления модели ChecklistChecking");
            }
            $log->addLog("Удалён ChecklistChecking ".$model_checklist_checking->id);


            $result = $checklist_checking_id;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}