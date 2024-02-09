<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\positioningsystem;

use backend\controllers\cachemanagers\EquipmentCacheController as BackEquipmentCacheController;
use backend\controllers\EquipmentBasicController;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Equipment;
use Throwable;
use Yii;
use yii\web\Controller;
use yii\web\Response;

class EquipmentCacheController extends Controller
{
    // actionGetEquipmentParameters     - Метод получения значений всех значений параметров для всего оборудования
    // actionGetEquipmentMineDetail     - Метод получения детальной информации по всему оборудованию из EquipmentMine
    // GetEquipmentListGroup            - Метод получения справочника оборудования сгруппированного по типа и объектам
    // GetEquipmentList                 - Метод получения справочника оборудования
    /**
     * actionGetEquipmentMineDetail     - Метод для получения детальной информации по всему оборудованию из EquipmentMine
     *12,06,2019
     * http://127.0.0.1/equipment-cache/get-equipment-mine-detail?mine_id=290
     * @author Якимов М.Н.
     */
    public static function actionGetEquipmentMineDetail()
    {
        $log = new LogAmicumFront("actionGetEquipmentMineDetail");
        $result = array();


        try {
            $log->addLog("Начал выполнять метод");
            $post = Assistant::GetServerMethod();
            if (!isset($post['mine_id']) && $post['mine_id'] == '') {
                throw new Exception('Не передан обязательный входной параметр');
            }
            $mine_id = $post['mine_id'];
            $response = self::GetEquipmentMineDetail($mine_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Не смог получить сведения об оборудовании');
            }
            $result = $response['Items'];

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    public static function GetEquipmentMineDetail($mine_id)
    {
        $log = new LogAmicumFront("GetEquipmentMineDetail");
        $result = array();
        try {
            $log->addLog("Начал выполнять метод");

            if ($mine_id == -1) {
                $mine_id = '*';
            }
            if (!COD) {
                $equipment_parameters = (new BackEquipmentCacheController())->getEquipmentMine($mine_id);
            } else {
                $equipment_parameters = EquipmentBasicController::getEquipmentMain($mine_id);
            }
            if ($equipment_parameters !== false) {
                $result = $equipment_parameters;
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");
        return array_merge(['Items' => $result], $log->getLogAll());

    }

    /**
     * actionGetEquipmentParameters - Метод для получения значений всех значений параметров для всего
     * оборудования
     * http://127.0.0.1/positioningsystem/equipment-cache/get-equipment-parameters?mine_id=290
     * 12-06-2019
     * @author Якимов М.Н.
     */
    public static function actionGetEquipmentParameters()
    {
        $log = new LogAmicumFront("actionGetEquipmentParameters");
        $result = array();

        try {
            $log->addLog("Начал выполнять метод");

            $response = self::GetEquipmentParameters();
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Не смог получить параметры оборудовании');
            }
            $result = $response['Items'];

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    public static function GetEquipmentParameters()
    {

        $log = new LogAmicumFront("GetEquipmentParameters");
        $result = array();
        $equipment_parameters = array();

        try {
            if (!COD) {
                $log->addLog("Начал выполнять метод");

                $equipment_parameters_measure = (new BackEquipmentCacheController())->multiGetParameterValue('*', '*', 2);
                if ($equipment_parameters_measure !== false) {
                    $equipment_parameters = array_merge($equipment_parameters, $equipment_parameters_measure);
                    $log->addLog("Получил измеренные параметры оборудований");
                } else {
                    throw new Exception("Кеш параметров пуст");
                }
                /**
                 * Блок получения вычисленных значений - в данном случае СТАТСУОВ оборудования
                 */
                $equipment_parameters_calc = (new BackEquipmentCacheController())->multiGetParameterValue('*', 164, 3);
                if ($equipment_parameters_calc !== false) {
                    $equipment_parameters = array_merge($equipment_parameters, $equipment_parameters_calc);
                    $log->addLog("Получил статусы оборудований");
                } else {
                    throw new Exception("Кеш параметров пуст");
                }
            } else {
                $equipment_parameters = EquipmentBasicController::getEquipmentParameterValue('*', '*', '*');
            }

            if ($equipment_parameters) {
                foreach ($equipment_parameters as $equipment_parameter) {
                    $equipment_result['equipment_id'] = $equipment_parameter['equipment_id'];
                    $equipment_result['equipment_parameter_id'] = $equipment_parameter['equipment_parameter_id'];
                    $equipment_result['parameter_id'] = $equipment_parameter['parameter_id'];
                    $equipment_result['parameter_type_id'] = $equipment_parameter['parameter_type_id'];
                    if ($equipment_parameter['value'] !== null) {
                        $equipment_result['value'] = $equipment_parameter['value'];
                    } else {
                        $equipment_result['value'] = -1;
                    }
                    $equipment_result['date_time'] = ($equipment_parameter['date_time'] !== null) ? $equipment_parameter['date_time'] : '1970-01-01 00:00:00.000000';

                    $result[] = $equipment_result;
                    unset($equipment_result);
                }
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    // GetEquipmentListGroup - получение справочника оборудования сгруппированного по типа и объектам
    // http://127.0.0.1/read-manager-amicum?controller=positioningsystem\EquipmentCache&method=GetEquipmentListGroup&subscribe=&data={}
    public static function GetEquipmentListGroup($data_post = NULL): array
    {
        $log = new LogAmicumFront("GetEquipmentListGroup");
        $result = (object)array();

        try {
            $log->addLog("Начал выполнять метод");
            $equipments = Equipment::find()
                ->joinWith('object')
                ->joinWith('object.objectType')
                ->all();

            foreach ($equipments as $equipment) {
                $object_type_id = $equipment->object->object_type_id;
                $object_id = $equipment->object_id;
                $equipment_id = $equipment->id;
                $equipment_list[$object_type_id]['object_type_id'] = $object_type_id;
                $equipment_list[$object_type_id]['object_type_title'] = $equipment->object->objectType->title;
                $equipment_list[$object_type_id]['objects'][$object_id]['object_id'] = $object_id;
                $equipment_list[$object_type_id]['objects'][$object_id]['object_title'] = $equipment->object->title;
                $equipment_list[$object_type_id]['objects'][$object_id]['equipments'][$equipment_id]['equipment_id'] = $equipment_id;
                $equipment_list[$object_type_id]['objects'][$object_id]['equipments'][$equipment_id]['equipment_title'] = $equipment->title;
                $equipment_list[$object_type_id]['objects'][$object_id]['equipments'][$equipment_id]['parent_equipment_id'] = $equipment->parent_equipment_id;
                $equipment_list[$object_type_id]['objects'][$object_id]['equipments'][$equipment_id]['inventory_number'] = $equipment->inventory_number;
            }
            if (isset($equipment_list)) {
                $result = $equipment_list;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    // GetEquipmentList - получение справочника оборудования
    // http://127.0.0.1/read-manager-amicum?controller=positioningsystem\EquipmentCache&method=GetEquipmentList&subscribe=&data={}
    public static function GetEquipmentList($data_post = NULL): array
    {
        $log = new LogAmicumFront("GetEquipmentList");
        $result = (object)array();

        try {
            $log->addLog("Начал выполнять метод");

            $equipments = Equipment::find()
                ->indexBy('id')
                ->all();

            if ($equipments) {
                $result = $equipments;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}