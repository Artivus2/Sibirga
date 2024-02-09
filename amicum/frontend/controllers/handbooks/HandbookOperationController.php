<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\handbooks;
//ob_start();

use backend\controllers\cachemanagers\LogCacheController;
use Exception;
use frontend\controllers\HandbookCachedController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\GroupOperation;
use frontend\models\Operation;
use frontend\models\OperationGroup;
use frontend\models\OperationKind;
use frontend\models\OperationType;
use frontend\models\OrderOperation;
use frontend\models\TypeOperation;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;

class HandbookOperationController extends Controller
{
    // TypeOperationsList       - Метод получения списка операций из справочника операций (Работы очистным работам)
    // GetListGroupOperation    - Метод получения списка групп операций из справочника операций (работы по линии АБ, работы по линии ПК и т.д.)
    // SaveNewOperation         - сохранение новой операции
    // MoveOperation            - перенос операции между типами операций
    // GetOperationAbVtb        - Метод возвращает список операций по линии АБ
    // SaveNewOperationAbVtb    - сохранение новой операции для АБ ВТБ c сохранением группы операции
    // GetOperationsList        - Метод возвращает список операций по структуре

    // GetGroupOperation()      - Получение справочника групп операций
    // SaveGroupOperation()     - Сохранение справочника групп операций
    // DeleteGroupOperation()   - Удаление справочника групп операций

    // GetTypeOperation()       - Получение справочника тип операций
    // SaveTypeOperation()      - Сохранение справочника тип операций
    // DeleteTypeOperation()    - Удаление справочника тип операций

    // GetOperationKind()       - Получение справочника вид операций
    // SaveOperationKind()      - Сохранение справочника вид операций
    // DeleteOperationKind()    - Удаление справочника вид операций

    // GetOperationType()       - Получение справочника группы тип операций
    // SaveOperationType()      - Сохранение справочника группы тип операций
    // DeleteOperationType()    - Удаление справочника группы тип операций

    // GetOperation()           - Получение справочника операций
    // SaveOperation()          - Сохранение справочника операций
    // DeleteOperation()        - Удаление справочника операций

    // GetOperationGroup()      - Получение справочника привязки операций и групп операций
    // SaveOperationGroup()     - Сохранение справочника привязки операций и групп операций
    // DeleteOperationGroup()   - Удаление справочника привязки операций и групп операций

    /**@var int Группа операций: Работы по линии АБ */
    const AbWORK = 1;

    /**
     * Название метода: TypeOperationsList()
     * Метод получения списка операций из справочника операций
     *
     * @param null $data_post
     * @return array
     * @package frontend\controllers\ordersystem
     * @example http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookOperation&method=TypeOperationsList&subscribe=&data=
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 25.05.2019 19:46
     * @since ver
     */
    public static function TypeOperationsList($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Промежуточный результирующий массив

        $warnings[] = 'TypeOperationsList. Данные успешно переданы';
        $warnings[] = 'TypeOperationsList. Входной массив данных' . $data_post;
        try {
            $groupEquipmentOperations = OrderOperation::find()
                ->select(['operation_id', 'equipment_id', 'equipment.title as equipment_title'])
                ->innerJoin('equipment', 'equipment.id=order_operation.equipment_id')
                ->groupBy(['operation_id', 'equipment_id', 'equipment.title'])
                ->where(['is not', 'equipment_id', null])
                ->andWhere('equipment_id != 1')
                ->limit(4000)
                ->asArray()
                ->all();
//            $warnings[]=$groupEquipmentOperation;
            foreach ($groupEquipmentOperations as $groupEquipmentOperation) {
                $handbookEquipOrder[$groupEquipmentOperation['operation_id']][] = $groupEquipmentOperation;
            }
//            $warnings[]=$handbookEquipOrder;
            $kind_operations = OperationKind::find()
                ->joinWith('operationTypes')
                ->joinWith('operationTypes.operations')// Получаем вложенные связи сразу
                ->joinWith('operationTypes.operations.unit')
                ->joinWith('operationTypes.operations.operationGroups')
                ->where("operation_kind.id!=5")
                ->all();
            if ($kind_operations) {
                foreach ($kind_operations as $kind_operation) {
                    $operation_list[$kind_operation->id]['kind_operation_id'] = $kind_operation->id;
                    $operation_list[$kind_operation->id]['kind_operation_title'] = $kind_operation->title;
//                    $operation_list[$kind_operation->id]['operation_type'] = array();
                    foreach ($kind_operation->operationTypes as $operation_type) {
                        $operation_list[$kind_operation->id]['operation_type'][$operation_type->id]['operation_type_id'] = $operation_type->id;
                        $operation_list[$kind_operation->id]['operation_type'][$operation_type->id]['operation_type_title'] = $operation_type->title;
//                        $operation_list[$kind_operation->id]['operation_type'][$operation_type->id]['operation'] = array();
                        foreach ($operation_type->operations as $operation) {
                            $operation_list[$kind_operation->id]['operation_type'][$operation_type->id]['operation'][$operation->id]['operation_id'] = $operation->id;
                            $operation_list[$kind_operation->id]['operation_type'][$operation_type->id]['operation'][$operation->id]['operation_title'] = $operation->title;
                            $operation_list[$kind_operation->id]['operation_type'][$operation_type->id]['operation'][$operation->id]['operation_unit_id'] = $operation->unit_id;
                            $operation_list[$kind_operation->id]['operation_type'][$operation_type->id]['operation'][$operation->id]['operation_unit_title'] = $operation->unit->title;
                            $operation_list[$kind_operation->id]['operation_type'][$operation_type->id]['operation'][$operation->id]['operation_unit_short_title'] = $operation->unit->short;
                            if (isset($handbookEquipOrder) and isset($handbookEquipOrder[$operation->id])) {
                                $operation_list[$kind_operation->id]['operation_type'][$operation_type->id]['operation'][$operation->id]['equipments'] = $handbookEquipOrder[$operation->id];
                            } else {
                                $operation_list[$kind_operation->id]['operation_type'][$operation_type->id]['operation'][$operation->id]['equipments'] = array();
                            }
                            if (!isset($operation_data[$operation['id']]['operation_groups'])) {
                                $operation_list[$kind_operation->id]['operation_type'][$operation_type->id]['operation'][$operation->id]['operation_groups'] = array();
                            }
                            foreach ($operation->operationGroups as $operation_group) {
                                $operation_list[$kind_operation->id]['operation_type'][$operation_type->id]['operation'][$operation->id]['operation_groups'][] = $operation_group->group_operation_id;
                            }
                        }
                    }
                }
                unset($kind_operation);
                unset($operation_type);
                foreach ($operation_list as $kind_operation) {
                    if (!isset($kind_operation['operation_type'])) {
                        $operation_list[$kind_operation['kind_operation_id']]['operation_type'] = (object)array();
                    } else {
                        foreach ($kind_operation['operation_type'] as $operation_type) {
                            if (!isset($operation_type['operation'])) {
                                $operation_list[$kind_operation['kind_operation_id']]['operation_type'][$operation_type['operation_type_id']]['operation'] = (object)array();
                            }
                        }
                    }
                }
                $result = $operation_list;
                $status *= 1;
                $warnings[] = 'TypeOperationsList. Метод отработал все ок';
            } else {
                $warnings[] = 'TypeOperationsList. справочник операций пуст';
                $result = (object)array();
            }

        } catch (Throwable $ex) {

            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
            $data_to_log = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
            LogCacheController::setHandbooksLogValue('TypeOperationsList', $data_to_log, '2');
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Название метода: GetOperationsList() - Метод возвращает список опираций по структуре
     *
     * СТРУКТУРА: [operation_id]
     *                  operation_id:
     *                  operation_title:
     *                  operation_load_value:
     *                  operation_unit_short_title:
     *                  [operation_groups]
     *                          [group_operation_id]
     *
     * @return array - массив с выше описанной структурой
     *
     * @package frontend\controllers\handbooks
     *
     * @see
     * @example  http://amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=GetOperationsList&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 28.06.2019 15:14
     */
    public static function GetOperationsList()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("GetOperationsList");

        try {
            $log->addLog("Начало выполнения метода");

            $cache = Yii::$app->cache;
            $key = "GetOperationsList";
            $keyHash = "GetOperationsListHash";
            $operation_data = $cache->get($key);
            if (!$operation_data) {
                $log->addLog("Кеша не было, получаю данные из БД");

                $operations = (new Query())
                    ->select('
                    operation.id as id,
                    operation.title as title,
                    operation.operation_load_value as operation_load_value,
                    unit.short as operation_unit_short_title,
                    operation_group.group_operation_id as group_operation_id
                ')
                    ->from('operation')
                    ->innerJoin('unit', 'unit.id=operation.unit_id')
                    ->leftJoin('operation_group', 'operation_group.operation_id=operation.id')
                    ->all();                                                                                                //получает список всех операций
                $log->addLog("Получил данные с БД");

                if ($operations)                                                                                            //если там есть данные тогда перебираем их
                {
                    foreach ($operations as $operation)                                                                     //перебор операций с целью формирования структуры
                    {
                        $operation_data[$operation['id']]['operation_id'] = $operation['id'];
                        $operation_data[$operation['id']]['operation_title'] = $operation['title'];
                        $operation_data[$operation['id']]['operation_load_value'] = $operation['operation_load_value'];
                        $operation_data[$operation['id']]['operation_unit_short_title'] = $operation['operation_unit_short_title'];
                        if (!isset($operation_data[$operation['id']]['operation_groups'])) {
                            $operation_data[$operation['id']]['operation_groups'] = array();
                        }
                        if ($operation['group_operation_id']) {
                            $operation_data[$operation['id']]['operation_groups'][] = $operation['group_operation_id'];
                        }
                    }
                } else {                                                                                                      //иначе выводим предупреждение о том что список операций пуст
                    $log->addLog("Справочник операций пуст");
                }

                $hash = md5(json_encode($operation_data));
                $cache->set($keyHash, $hash, 60 * 60 * 24);
                $cache->set($key, $operation_data, 60 * 60 * 24);   // 60 * 60 * 24 = сутки
            } else {
                $log->addLog("Кеш был");
                $hash = $cache->get($keyHash);
            }

            if (empty($operation_data)) {
                $result = (object)array();
            } else {
                $result['hash'] = $hash;
                $result['handbook'] = $operation_data;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод GetOperationAbVtb() - Метод возвращает список операций по линии АБ
     * @return array массив со структурой: [operation_id]
     *                                              operation_id:
     *                                              operation_title:
     *                                              operation_load_value:
     *                                              operation_unit_short_title:
     *                                              [operation_groups]
     *                                                      [group_operation_id]
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=GetOperationAbVtb&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 07.11.2019 14:14
     */
    public static function GetOperationAbVtb()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $operations = array();
        $operation_data = array();
        $warnings[] = 'GetOperationAbVtb. Начало метода';
        try {
            $groupEquipmentOperations = OrderOperation::find()
                ->select(['operation_id', 'equipment_id', 'equipment.title as equipment_title'])
                ->innerJoin('equipment', 'equipment.id=order_operation.equipment_id')
                ->groupBy(['operation_id', 'equipment_id', 'equipment.title'])
                ->where(['is not', 'equipment_id', null])
                ->limit(4000)
                ->asArray()
                ->all();
//            $warnings[]=$groupEquipmentOperation;
            foreach ($groupEquipmentOperations as $groupEquipmentOperation) {
                $handbookEquipOrder[$groupEquipmentOperation['operation_id']][] = $groupEquipmentOperation;
            }
            $operations = Operation::find()
                ->joinWith(['operationGroups' => function ($q) {
                    $q->where(['operation_group.group_operation_id' => self::AbWORK]);
                }])
                ->joinWith('operationType.operationKind')
                ->joinWith('unit')
                ->all();
            if (!empty($operations)) {
                foreach ($operations as $operation) {
                    $operation_kind_id = $operation->operationType->operationKind->id;
                    $operation_type_id = $operation->operationType->id;

                    $operation_data[$operation_kind_id]['kind_operation_id'] = $operation_kind_id;
                    $operation_data[$operation_kind_id]['kind_operation_title'] = $operation->operationType->operationKind->title;
                    //                $operation_data[$operation_kind_id]['operation_type'] = array();
                    $operation_data[$operation_kind_id]['operation_type'][$operation_type_id]['operation_type_id'] = $operation_type_id;
                    $operation_data[$operation_kind_id]['operation_type'][$operation_type_id]['operation_type_title'] = $operation->operationType->title;
                    $operation_data[$operation_kind_id]['operation_type'][$operation_type_id]['operation'][$operation->id]['operation_id'] = $operation->id;
                    $operation_data[$operation_kind_id]['operation_type'][$operation_type_id]['operation'][$operation->id]['operation_title'] = $operation->title;
                    $operation_data[$operation_kind_id]['operation_type'][$operation_type_id]['operation'][$operation->id]['operation_load_value'] = $operation->operation_load_value;
                    $operation_data[$operation_kind_id]['operation_type'][$operation_type_id]['operation'][$operation->id]['operation_unit_short_title'] = $operation->unit->short;
                    $operation_data[$operation_kind_id]['operation_type'][$operation_type_id]['operation'][$operation->id]['operation_groups'] = array();
                    foreach ($operation->operationGroups as $operationGroup) {
                        $operation_data[$operation_kind_id]['operation_type'][$operation_type_id]['operation'][$operation->id]['operation_groups'][] = $operationGroup->group_operation_id;
                    }
                    if (isset($handbookEquipOrder) and isset($handbookEquipOrder[$operation->id])) {
                        $operation_data[$operation_kind_id]['operation_type'][$operation_type_id]['operation'][$operation->id]['equipments'] = $handbookEquipOrder[$operation->id];
                    } else {
                        $operation_data[$operation_kind_id]['operation_type'][$operation_type_id]['operation'][$operation->id]['equipments'] = array();
                    }
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetOperationAbVtb. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        if (!empty($operation_data)) {
            $result = $operation_data;
        } else {
            $result = (object)array();
        }
        $warnings[] = 'GetOperationAbVtb. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Название метода: GetListGroupOperation()
     * Метод получения списка групп операций из справочника операций
     *
     * @param null $data_post
     * @return array
     * @package frontend\controllers\ordersystem
     * @example http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookOperation&method=GetListGroupOperation&subscribe=&data=
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 25.05.2019 19:46
     * @since ver
     */
    public static function GetListGroupOperation($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Промежуточный результирующий массив

        $warnings[] = 'GetListGroupOperation. Данные успешно переданы';
        $warnings[] = 'GetListGroupOperation. Входной массив данных' . $data_post;
        try {
            $group_operations = GroupOperation::find()
                ->joinWith('operationGroups')
                ->joinWith('operationGroups.operation')// Получаем вложенные связи сразу
                ->joinWith('operationGroups.operation.unit')
                ->all();
            if ($group_operations) {
                foreach ($group_operations as $group_operation) {
                    $operation_list[$group_operation->id]['group_operation_id'] = $group_operation->id;
                    $operation_list[$group_operation->id]['group_operation_title'] = $group_operation->title;
                    $operation_list[$group_operation->id]['operation'] = array();
                    foreach ($group_operation->operationGroups as $operation_group) {
                        $operation_list[$group_operation->id]['operation'][$operation_group->operation_id]['operation_id'] = $operation_group->operation_id;
                        $operation_list[$group_operation->id]['operation'][$operation_group->operation_id]['operation_title'] = $operation_group->operation->title;
                        $operation_list[$group_operation->id]['operation'][$operation_group->operation_id]['operation_unit_id'] = $operation_group->operation->unit_id;
                        $operation_list[$group_operation->id]['operation'][$operation_group->operation_id]['operation_unit_title'] = $operation_group->operation->unit->title;
                        $operation_list[$group_operation->id]['operation'][$operation_group->operation_id]['operation_unit_short_title'] = $operation_group->operation->unit->short;
                    }
                }
                $result = $operation_list;
                $status *= 1;
                $warnings[] = 'GetListGroupOperation. Метод отработал все ок';
            } else {
                $warnings[] = 'GetListGroupOperation. справочник групп операций пуст';
            }

        } catch (Throwable $ex) {
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // SaveNewOperation - сохранение новой операции
    public static function SaveNewOperation($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'SaveNewOperation. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('SaveNewPlace. Данные с фронта не получены');
            }
            $warnings[] = 'SaveNewOperation. Данные успешно переданы';
            $warnings[] = 'SaveNewOperation. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'SaveNewOperation. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'operation_obj'))
            ) {
                throw new Exception('SaveNewOperation. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            HandbookCachedController::clearOperationCache();
            $warnings[] = 'SaveNewOperation. Данные с фронта получены и они правильные';
            $operation_obj = $post_dec->operation_obj;
            $operation = Operation::findOne([
                'title' => $operation_obj->operation_title,
                'unit_id' => $operation_obj->operation_unit_id,
                'operation_type_id' => $operation_obj->operation_type_id,
            ]);                                                                                                    //находим место в БД в таблице place (Список мест)
            if ($operation) {
                throw new Exception('SaveNewOperation. Такая операция уже существует');
            }

            $operation = new Operation();

            $operation->title = $operation_obj->operation_title;
            $operation->operation_type_id = $operation_obj->operation_type_id;
            $operation->unit_id = $operation_obj->operation_unit_id;
            $operation->value = $operation_obj->value;
            $operation->description = $operation_obj->description;
            $operation->short_title = $operation_obj->operation_short_title;
            $operation->operation_load_value = $operation_obj->operation_load_value;
            if ($operation->save()) {
                $operation->refresh();
                $operation_id = $operation->id;
            } else {
                $errors[] = $operation->errors;
                throw new Exception('SaveNewOperation. Ошибка сохранения модели операции Operation');
            }

            $operation_obj->operation_id = $operation_id;

            HandbookCachedController::clearOperationCache();
        } catch (Throwable $exception) {
            $errors[] = 'SaveNewOperation. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'SaveNewOperation. Конец метода';
        if (!isset($operation_obj)) {
            $result = (object)array();
        } else {
            $result = $operation_obj;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SaveNewOperationAbVtb() - сохранение новой операции для АБ ВТБ c сохранением группы операции
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=SaveNewOperationAbVtb&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 20.11.2019 8:42
     */
    public static function SaveNewOperationAbVtb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
//        $data_post = '{"operation_obj":{"operation_title":"тест сохранения работы АБ ВТБ","operation_type_id":6,"operation_unit_id":81,"value":0,"description":"пусто","operation_short_title":"тест АБ ВТБ","operation_load_value":5,"operation_id":null}}';
        $warnings[] = 'SaveNewOperationAbVtb. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('SaveNewOperationAbVtb. Данные с фронта не получены');
            }
            $warnings[] = 'SaveNewOperationAbVtb. Данные успешно переданы';
            $warnings[] = 'SaveNewOperationAbVtb. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'SaveNewOperationAbVtb. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'operation_obj'))
            ) {
                throw new Exception('SaveNewOperationAbVtb. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'SaveNewOperationAbVtb. Данные с фронта получены и они правильные';
            $operation_obj = $post_dec->operation_obj;
            $operation = Operation::findOne([
                'title' => $operation_obj->operation_title,
                'unit_id' => $operation_obj->operation_unit_id,
                'operation_type_id' => $operation_obj->operation_type_id,
            ]);                                                                                                    //находим место в БД в таблице opertation (Список операций)
            if ($operation) {
                throw new Exception('SaveNewOperationAbVtb. Такая операция уже существует');
            }

            $operation = new Operation();

            $operation->title = $operation_obj->operation_title;
            $operation->operation_type_id = $operation_obj->operation_type_id;
            $operation->unit_id = $operation_obj->operation_unit_id;
            $operation->value = $operation_obj->value;
            $operation->description = $operation_obj->description;
            $operation->short_title = $operation_obj->operation_short_title;
            $operation->operation_load_value = $operation_obj->operation_load_value;
            if ($operation->save()) {
                $operation->refresh();
                $operation_id = $operation->id;
            } else {
                $errors[] = $operation->errors;
                throw new Exception('SaveNewOperationAbVtb. Ошибка сохранения модели операции Operation');
            }
            $operation_obj->operation_id = $operation_id;


            $operation_group = new OperationGroup();
            $operation_group->operation_id = $operation_id;
            $operation_group->group_operation_id = 1;
            if ($operation_group->save()) {
                $warnings[] = 'SaveNewOperationAbVtb. Привязка операции к группе прошла успешно';
            } else {
                $errors[] = $operation_group->errors;
                throw new Exception('SaveNewOperationAbVtb. Ошибка сохранения привязки операции к группе');
            }

            HandbookCachedController::clearOperationCache();
        } catch (Throwable $exception) {
            $errors[] = 'SaveNewOperation. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'SaveNewOperationAbVtb. Конец метода';
        if (!isset($operation_obj)) {
            $result = (object)array();
        } else {
            $result = $operation_obj;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetGroupOperation()      - Получение справочника групп операций
    // SaveGroupOperation()     - Сохранение справочника групп операций
    // DeleteGroupOperation()   - Удаление справочника групп операций

    /**
     * Метод GetGroupOperation() - Получение справочника групп операций
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
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=GetGroupOperation&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetGroupOperation()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetGroupOperation';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_group_operation = GroupOperation::find()
                ->asArray()
                ->all();
            if (empty($handbook_group_operation)) {
                $warnings[] = $method_name . '. Справочник групп операций пуст';
            } else {
                $result = $handbook_group_operation;
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
     * Метод SaveGroupOperation() - Сохранение справочника групп операций
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "groupOperation":
     *  {
     *      "group_operation_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "group_operation_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=SaveGroupOperation&subscribe=&data={"groupOperation":{"group_operation_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveGroupOperation($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveGroupOperation';
        $handbook_group_operation_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'groupOperation'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_group_operation_id = $post_dec->groupOperation->group_operation_id;
            $title = $post_dec->groupOperation->title;
            $new_handbook_group_operation_id = GroupOperation::findOne(['id' => $handbook_group_operation_id]);
            if (empty($new_handbook_group_operation_id)) {
                $new_handbook_group_operation_id = new GroupOperation();
            }
            $new_handbook_group_operation_id->title = $title;
            if ($new_handbook_group_operation_id->save()) {
                $new_handbook_group_operation_id->refresh();
                $handbook_group_operation_data['group_operation_id'] = $new_handbook_group_operation_id->id;
                $handbook_group_operation_data['title'] = $new_handbook_group_operation_id->title;
            } else {
                $errors[] = $new_handbook_group_operation_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника групп операций');
            }
            unset($new_handbook_group_operation_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_group_operation_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteGroupOperation() - Удаление справочника групп операций
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "group_operation_id": 98             // идентификатор справочника групп операций
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=DeleteGroupOperation&subscribe=&data={"group_operation_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteGroupOperation($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteGroupOperation';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'group_operation_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_group_operation_id = $post_dec->group_operation_id;
            $del_handbook_group_operation = GroupOperation::deleteAll(['id' => $handbook_group_operation_id]);
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


    // GetTypeOperation()      - Получение справочника тип операций
    // SaveTypeOperation()     - Сохранение справочника тип операций
    // DeleteTypeOperation()   - Удаление справочника тип операций

    /**
     * Метод GetTypeOperation() - Получение справочника тип операций
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
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=GetTypeOperation&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetTypeOperation()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetTypeOperation';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_type_operation = TypeOperation::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_type_operation)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник тип операций пуст';
            } else {
                $result = $handbook_type_operation;
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
     * Метод SaveTypeOperation() - Сохранение справочника тип операций
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "type_operation":
     *  {
     *      "type_operation_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "type_operation_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=SaveTypeOperation&subscribe=&data={"type_operation":{"type_operation_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveTypeOperation($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveTypeOperation';
        $handbook_type_operation_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'type_operation'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_type_operation_id = $post_dec->type_operation->type_operation_id;
            $title = $post_dec->type_operation->title;
            $new_handbook_type_operation_id = TypeOperation::findOne(['id' => $handbook_type_operation_id]);
            if (empty($new_handbook_type_operation_id)) {
                $new_handbook_type_operation_id = new TypeOperation();
            }
            $new_handbook_type_operation_id->title = $title;
            if ($new_handbook_type_operation_id->save()) {
                $new_handbook_type_operation_id->refresh();
                $handbook_type_operation_data['type_operation_id'] = $new_handbook_type_operation_id->id;
                $handbook_type_operation_data['title'] = $new_handbook_type_operation_id->title;
            } else {
                $errors[] = $new_handbook_type_operation_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника тип операций');
            }
            unset($new_handbook_type_operation_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_type_operation_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteTypeOperation() - Удаление справочника тип операций
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "type_operation_id": 98             // идентификатор справочника тип операций
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=DeleteTypeOperation&subscribe=&data={"type_operation_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteTypeOperation($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteTypeOperation';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'type_operation_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_type_operation_id = $post_dec->type_operation_id;
            $del_handbook_type_operation = TypeOperation::deleteAll(['id' => $handbook_type_operation_id]);
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

    // GetOperationKind()      - Получение справочника вид операций
    // SaveOperationKind()     - Сохранение справочника вид операций
    // DeleteOperationKind()   - Удаление справочника вид операций

    /**
     * Метод GetOperationKind() - Получение справочника вид операций
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
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=GetOperationKind&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetOperationKind()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetOperationKind';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_operation_kind = OperationKind::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_operation_kind)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник вид операций пуст';
            } else {
                $result = $handbook_operation_kind;
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
     * Метод SaveOperationKind() - Сохранение справочника вид операций
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "operation_kind":
     *  {
     *      "operation_kind_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "operation_kind_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=SaveOperationKind&subscribe=&data={"operation_kind":{"operation_kind_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveOperationKind($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveOperationKind';
        $handbook_operation_kind_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'operation_kind'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_operation_kind_id = $post_dec->operation_kind->operation_kind_id;
            $title = $post_dec->operation_kind->title;
            $new_handbook_operation_kind_id = OperationKind::findOne(['id' => $handbook_operation_kind_id]);
            if (empty($new_handbook_operation_kind_id)) {
                $new_handbook_operation_kind_id = new OperationKind();
            }
            $new_handbook_operation_kind_id->title = $title;
            if ($new_handbook_operation_kind_id->save()) {
                $new_handbook_operation_kind_id->refresh();
                $handbook_operation_kind_data['operation_kind_id'] = $new_handbook_operation_kind_id->id;
                $handbook_operation_kind_data['title'] = $new_handbook_operation_kind_id->title;
            } else {
                $errors[] = $new_handbook_operation_kind_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника вид операций');
            }
            unset($new_handbook_operation_kind_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_operation_kind_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteOperationKind() - Удаление справочника вид операций
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "operation_kind_id": 98             // идентификатор справочника вид операций
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=DeleteOperationKind&subscribe=&data={"operation_kind_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteOperationKind($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteOperationKind';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'operation_kind_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_operation_kind_id = $post_dec->operation_kind_id;
            $del_handbook_operation_kind = OperationKind::deleteAll(['id' => $handbook_operation_kind_id]);
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


    // GetOperationType()      - Получение справочника группы тип операций
    // SaveOperationType()     - Сохранение справочника группы тип операций
    // DeleteOperationType()   - Удаление справочника группы тип операций

    /**
     * Метод GetOperationType() - Получение справочника группы тип операций
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
     *      "operation_kind_id":"-1",                // ключ вида операции
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=GetOperationType&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetOperationType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetOperationType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_operation_type = OperationType::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_operation_type)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник группы тип операций пуст';
            } else {
                $result = $handbook_operation_type;
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
     * Метод SaveOperationType() - Сохранение справочника группы тип операций
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "operation_type":
     *  {
     *      "operation_type_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *      "operation_kind_id":"-1",                // ключ вида операции
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "operation_type_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *      "operation_kind_id":"-1",                // ключ вида операции
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=SaveOperationType&subscribe=&data={"operation_type":{"operation_type_id":-1,"title":"ACTION","operation_kind_id":"-1"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveOperationType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveOperationType';
        $handbook_operation_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'operation_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_operation_type_id = $post_dec->operation_type->operation_type_id;
            $title = $post_dec->operation_type->title;
            $operation_kind_id = $post_dec->operation_type->operation_kind_id;
            $new_handbook_operation_type_id = OperationType::findOne(['id' => $handbook_operation_type_id]);
            if (empty($new_handbook_operation_type_id)) {
                $new_handbook_operation_type_id = new OperationType();
            }
            $new_handbook_operation_type_id->id = $handbook_operation_type_id;
            $new_handbook_operation_type_id->operation_kind_id = $operation_kind_id;
            $new_handbook_operation_type_id->title = $title;
            if ($new_handbook_operation_type_id->save()) {
                $new_handbook_operation_type_id->refresh();
                $handbook_operation_type_data['operation_type_id'] = $new_handbook_operation_type_id->id;
                $handbook_operation_type_data['title'] = $new_handbook_operation_type_id->title;
                $handbook_operation_type_data['operation_kind_id'] = $new_handbook_operation_type_id->operation_kind_id;
            } else {
                $errors[] = $new_handbook_operation_type_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника группы тип операций');
            }
            unset($new_handbook_operation_type_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_operation_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteOperationType() - Удаление справочника группы тип операций
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "operation_type_id": 98             // идентификатор справочника группы тип операций
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=DeleteOperationType&subscribe=&data={"operation_type_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteOperationType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteOperationType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'operation_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_operation_type_id = $post_dec->operation_type_id;
            $del_handbook_operation_type = OperationType::deleteAll(['id' => $handbook_operation_type_id]);
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


    // GetOperation()      - Получение справочника операций
    // SaveOperation()     - Сохранение справочника операций
    // DeleteOperation()   - Удаление справочника операций

    /**
     * Метод GetOperation() - Получение справочника операций
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                            // ключ справочника
     *      "title":"ACTION",                   // название справочника
     *      "operation_type_id":"-1",           // ключ типа операции
     *      "unit_id":"-1",                     // ключ единицы измерения
     *      "value":"1",                        // значение операции за единицу измерения - объём операции на нагрузку по человекам и по времени 1 метр крепления горной выработки 2 человеками в течение 30минут
     *      "description":"пва",                // описание операции
     *      "operation_load_value":"15",        // Нагрузка по операции в человеках
     *      "short_title":"павр",               // сокращенное название поерации
     *      "opeartion_load_time":"15",         // нагрузка по операции во времени
     *
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=GetOperation&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetOperation()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetOperation';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_operation = Operation::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_operation)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник операций пуст';
            } else {
                $result = $handbook_operation;
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
     * Метод SaveOperation() - Сохранение справочника операций
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "operation":
     *  {
     *      "operation_id":-1,                  // ключ справочника
     *      "title":"ACTION",                   // название справочника
     *      "operation_type_id":"-1",           // ключ типа операции
     *      "unit_id":"-1",                     // ключ единицы измерения
     *      "value":"1",                        // значение операции за единицу измерения - объём операции на нагрузку по человекам и по времени 1 метр крепления горной выработки 2 человеками в течение 30минут
     *      "description":"пва",                // описание операции
     *      "operation_load_value":"15",        // Нагрузка по операции в человеках
     *      "short_title":"павр",               // сокращенное название поерации
     *      "opeartion_load_time":"15",         // нагрузка по операции во времени
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "operation_id":-1,                  // ключ справочника
     *      "title":"ACTION",                   // название справочника
     *      "operation_type_id":"-1",           // ключ типа операции
     *      "unit_id":"-1",                     // ключ единицы измерения
     *      "value":"1",                        // значение операции за единицу измерения - объём операции на нагрузку по человекам и по времени 1 метр крепления горной выработки 2 человеками в течение 30минут
     *      "description":"пва",                // описание операции
     *      "operation_load_value":"15",        // Нагрузка по операции в человеках
     *      "short_title":"павр",               // сокращенное название поерации
     *      "opeartion_load_time":"15",         // нагрузка по операции во времени
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=SaveOperation&subscribe=&data={"operation":{"operation_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveOperation($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveOperation';
        $handbook_operation_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'operation'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_operation_id = $post_dec->operation->operation_id;
            $title = $post_dec->operation->title;
            $operation_type_id = $post_dec->operation->operation_type_id;
            $unit_id = $post_dec->operation->unit_id;
            $value = $post_dec->operation->value;
            $description = $post_dec->operation->description;
            $operation_load_value = $post_dec->operation->operation_load_value;
            $short_title = $post_dec->operation->short_title;
            $opeartion_load_time = $post_dec->operation->opeartion_load_time;
            $new_handbook_operation_id = Operation::findOne(['id' => $handbook_operation_id]);
            if (empty($new_handbook_operation_id)) {
                $new_handbook_operation_id = new Operation();
            }
            $new_handbook_operation_id->title = $title;
            $new_handbook_operation_id->operation_type_id = $operation_type_id;
            $new_handbook_operation_id->unit_id = $unit_id;
            $new_handbook_operation_id->value = $value;
            $new_handbook_operation_id->description = $description;
            $new_handbook_operation_id->operation_load_value = $operation_load_value;
            $new_handbook_operation_id->short_title = $short_title;
            $new_handbook_operation_id->opeartion_load_time = $opeartion_load_time;
            if (!$new_handbook_operation_id->save()) {
                $errors[] = $new_handbook_operation_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника операций');
            }

            $new_handbook_operation_id->refresh();
            $handbook_operation_data['operation_id'] = $new_handbook_operation_id->id;
            $handbook_operation_data['title'] = $new_handbook_operation_id->title;
            $handbook_operation_data['operation_type_id'] = $new_handbook_operation_id->operation_type_id;
            $handbook_operation_data['unit_id'] = $new_handbook_operation_id->unit_id;
            $handbook_operation_data['value'] = $new_handbook_operation_id->value;
            $handbook_operation_data['description'] = $new_handbook_operation_id->description;
            $handbook_operation_data['operation_load_value'] = $new_handbook_operation_id->operation_load_value;
            $handbook_operation_data['short_title'] = $new_handbook_operation_id->short_title;
            $handbook_operation_data['opeartion_load_time'] = $new_handbook_operation_id->opeartion_load_time;

            unset($new_handbook_operation_id);
            HandbookCachedController::clearOperationCache();
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_operation_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteOperation() - Удаление справочника операций
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "operation_id": 98             // идентификатор справочника операций
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=DeleteOperation&subscribe=&data={"operation_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteOperation($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteOperation';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'operation_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_operation_id = $post_dec->operation_id;
            $del_handbook_operation = Operation::deleteAll(['id' => $handbook_operation_id]);
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

    // MoveOperation - перенос операции между типами операций
    // operation_obj:
    //      operation_id        -   ключ операции которую переносим
    //      operation_type_id   -   тип операции в который переносим данную операци.
    public static function MoveOperation($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'MoveOperation. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('SaveNewPlace. Данные с фронта не получены');
            }
            $warnings[] = 'MoveOperation. Данные успешно переданы';
            $warnings[] = 'MoveOperation. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'MoveOperation. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'operation_obj'))
            ) {
                throw new Exception('MoveOperation. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'MoveOperation. Данные с фронта получены и они правильные';
            $operation_obj = $post_dec->operation_obj;
            $operation = Operation::findOne([
                'id' => $operation_obj->operation_id
            ]);                                                                                                    //находим место в БД в таблице place (Список мест)
            if (!$operation) {
                throw new Exception('MoveOperation. Такая операция не существует');
            }

            $operation->operation_type_id = $operation_obj->operation_type_id;
            if ($operation->save()) {
                $operation->refresh();
                $operation_id = $operation->id;
            } else {
                $errors[] = $operation->errors;
                throw new Exception('MoveOperation. Ошибка сохранения модели операции Operation');
            }
            $operation_obj->operation_id = $operation_id;

        } catch (Throwable $exception) {
            $errors[] = 'MoveOperation. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'MoveOperation. Конец метода';
        if (!isset($operation_obj)) {
            $result = (object)array();
        } else {
            $result = $operation_obj;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetOperationGroup()      - Получение справочника привязки операций и групп операций
    // SaveOperationGroup()     - Сохранение справочника привязки операций и групп операций
    // DeleteOperationGroup()   - Удаление справочника привязки операций и групп операций

    /**
     * Метод GetGroupAlarm() - Получение справочника привязки операций и групп операций
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                        // ключ справочника
     *      "operation_id":-1,              // ключ операции
     *      "group_operation_id":-1,        // ключ группы операции
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookOperation&method=GetOperationGroup&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetOperationGroup()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetOperationGroup';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_operation_group = OperationGroup::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_operation_group)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник привязки операций и групп операций пуст';
            } else {
                $result = $handbook_operation_group;
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
     * Метод SaveOperationGroup() - Сохранение справочника привязки операций и групп операций
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "operation_group":
     *  {
     *      "operation_group_id":-1,            // ключ справочника
     *      "operation_id":-1,                  // ключ операции
     *      "group_operation_id":-1,            // ключ группы операции
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "operation_group_id":-1,            // ключ справочника
     *      "operation_id":-1,                  // ключ операции
     *      "group_operation_id":-1,            // ключ группы операции
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=SaveOperationGroup&subscribe=&data={"operation_group":{"operation_group_id":-1,"operation_id":-1,"group_operation_id":-1,}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveOperationGroup($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveOperationGroup';
        $handbook_operation_group_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'operation_group'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_operation_group_id = $post_dec->operation_group->operation_group_id;
            $operation_id = $post_dec->operation_group->operation_id;
            $group_operation_id = $post_dec->operation_group->group_operation_id;
            $new_handbook_operation_group_id = OperationGroup::findOne(['id' => $handbook_operation_group_id]);
            if (empty($new_handbook_operation_group_id)) {
                $new_handbook_operation_group_id = new OperationGroup();
            }
            $new_handbook_operation_group_id->operation_id = $operation_id;
            $new_handbook_operation_group_id->group_operation_id = $group_operation_id;
            if ($new_handbook_operation_group_id->save()) {
                $new_handbook_operation_group_id->refresh();
                $handbook_operation_group_data['operation_group_id'] = $new_handbook_operation_group_id->id;
                $handbook_operation_group_data['operation_id'] = $new_handbook_operation_group_id->operation_id;
                $handbook_operation_group_data['group_operation_id'] = $new_handbook_operation_group_id->group_operation_id;
            } else {
                $errors[] = $new_handbook_operation_group_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника привязки операций и групп операций');
            }
            unset($new_handbook_operation_group_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_operation_group_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteOperationGroup() - Удаление справочника привязки операций и групп операций
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "operation_group_id": 98             // идентификатор справочника привязки операций и групп операций
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=DeleteOperationGroup&subscribe=&data={"operation_group_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteOperationGroup($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteOperationGroup';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'operation_group_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_operation_group_id = $post_dec->operation_group_id;
            $del_handbook_operation_group = OperationGroup::deleteAll(['id' => $handbook_operation_group_id]);
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
}
