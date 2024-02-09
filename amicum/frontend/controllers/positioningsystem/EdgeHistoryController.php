<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\positioningsystem;

use backend\controllers\Assistant;
use backend\controllers\cachemanagers\EdgeCacheController;
use backend\controllers\const_amicum\ParamEnum;
use backend\controllers\const_amicum\ParameterTypeEnumController;
use backend\controllers\const_amicum\StatusEnumController;
use backend\controllers\EdgeBasicController;
use Exception;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\EdgeChanges;
use frontend\models\EdgeChangesHistory;
use frontend\models\EdgeParameter;
use frontend\models\EdgeParameterHandbookValue;
use frontend\models\EdgeStatus;
use Throwable;
use yii\db\Query;


class EdgeHistoryController extends \yii\web\Controller
{
    // AddEdgeChange                - Метод добавления изменений по выработкам
    // AddEdgeToHistoryChange       - Метод добавления выработки в историю изменения выработки
    // EditStatusEdge               - Метод изменения статуса ветви
    // UpdateEdgeChangeStatus       - Метод изменения статуса для истории изменений выработок на неактуальную
    // ReplaceEdges                 - Метод отмены последнего изменения выработки\
    // GetActualEdgeChangesHistory   - Метод получения актуальной EdgeChangesHistory


    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * AddEdgeChange - Метод добавления изменений по выработкам
     * @param $mas_edgeID - массив с идентификаторами выработок
     * Выходные данные:
     *      edge_changes_id - ключ истории изменений
     * @return array|int|string
     */
    public static function AddEdgeChange($mas_edgeID, $date_time_now = 1)
    {
        $log = new LogAmicumFront("AddEdgeChange");
        $result = null;
        $edge_changes_id = -1;

        try {
            $log->addLog("Начал выполнять метод");
            if ($date_time_now == 1) {
                $date_time_now = Assistant::GetDateTimeNow();
            }
//            $log->addData($mas_edgeID,'$mas_edgeID',__LINE__);
            $edge_changes = new EdgeChanges();
            $edge_changes->date_time = $date_time_now;
            $edge_changes->status_id = 1;
            if (!$edge_changes->save()) {
                throw new Exception("Не удалось сохранить запись в модели EdgeChanges ");
            }

            $edge_changes_id = $edge_changes['id'];
            $result = $edge_changes_id;

            foreach ($mas_edgeID as $edge_id) {
                $edge_changes_history = new EdgeChangesHistory();
                $edge_changes_history->edge_id = $edge_id;
                $edge_changes_history->id_edge_changes = $edge_changes_id;
                if (!$edge_changes_history->save()) {
                    throw new Exception("Не удалось сохранить запись в модели EdgeChangesHistory ");
                }
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result, 'edge_changes_id' => $edge_changes_id], $log->getLogAll());
    }

    /**
     * AddEdgeToHistoryChange - Метод добавления выработки в историю изменения выработки
     * @param $mas_edgeID - массив с идентификаторами выработок
     * Выходные данные:
     *      edge_changes_id - ключ истории изменений
     * @return array|int|string
     */
    public static function AddEdgeToHistoryChange($edge_changes_id, $edge_id)
    {
        $log = new LogAmicumFront("AddEdgeChange");
        $result = null;

        try {
            $log->addLog("Начал выполнять метод");
//            $log->addData($edge_id,'$edge_id',__LINE__);
            $edge_changes_history = new EdgeChangesHistory();
            $edge_changes_history->edge_id = $edge_id;
            $edge_changes_history->id_edge_changes = $edge_changes_id;
            if (!$edge_changes_history->save()) {
                $log->addData($edge_changes_history->errors, '$edge_changes_history->errors', __LINE__);
                throw new Exception("Не удалось сохранить запись в модели EdgeChangesHistory ");
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * EditStatusEdge - Метод изменения статуса ветви
     * @param $edge_id - ключ ветви статус которой меняем
     * @param $status_id - статус, который ставим
     * @return array
     */
    public static function EditStatusEdge($edge_id, $status_id)
    {
        $log = new LogAmicumFront("EditStatusEdge");

        $data_time = Assistant::GetDateNow();
        $result = null;                                                                                                 // результирующий массив (если требуется)

        try {

            $log->addLog("Начало выполнения метода");
            $log->addData($edge_id, '$edge_id', __LINE__);

            //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый
            $edge_parameter = EdgeParameter::findOne(['edge_id' => $edge_id, 'parameter_id' => 164, 'parameter_type_id' => 1]);
            if (!$edge_parameter) {
                $edge_parameter_new = new EdgeParameter();
                $edge_parameter_new->edge_id = $edge_id;                                                                    //айди ветви
                $edge_parameter_new->parameter_id = 164;                                                                    //айди параметра
                $edge_parameter_new->parameter_type_id = 1;                                                                 //айди типа параметра
                if (!$edge_parameter_new->save()) {
                    $log->addData($edge_parameter_new->errors, '$edge_parameter_new_errors', __LINE__);
                    throw new Exception("Не смог сохранить модель EdgeParameter");
                }
            }
            $log->addLog("Сохранил параметр ветви");

            $edge_parameter_handbook_value = new EdgeParameterHandbookValue();
            $edge_parameter_handbook_value->edge_parameter_id = $edge_parameter->id;
            $edge_parameter_handbook_value->date_time = $data_time;
            $edge_parameter_handbook_value->value = $status_id;
            $edge_parameter_handbook_value->status_id = $status_id;
            if (!$edge_parameter_handbook_value->save()) {
                $log->addData($edge_parameter_handbook_value->errors, '$edge_parameter_handbook_value_errors', __LINE__);
                throw new Exception("Не смог сохранить модель EdgeParameterHandbookValue");
            }

            $log->addLog("Сохранил значение параметра ветви");

            $edge_status = new EdgeStatus();                                                                                // в таблицу статусов edgeй пишем такой же статус
            $edge_status->edge_id = $edge_id;
            $edge_status->status_id = $status_id;
            $edge_status->date_time = $data_time;
            if (!$edge_status->save()) {
                $log->addData($edge_status->errors, '$edge_status_errors', __LINE__);
                throw new Exception("Не смог сохранить модель EdgeStatus");
            }

            $log->addLog("Сохранил статус ветви");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * UpdateEdgeChangeStatus - Метод изменения статуса для истории изменений выработок на неактуальную
     * @param $edge_change_id - идентификатор изменения
     * @return array|int|string
     */
    public static function UpdateEdgeChangeStatus($edge_change_id)
    {
        $log = new LogAmicumFront("UpdateEdgeChangeStatus");
        $result = null;

        try {
            if (!$edge_change_id) {
                throw new Exception("Не передан идентификатор истории изменений выработок");
            }

            $edge_history = EdgeChanges::findOne($edge_change_id);
            if (!$edge_history) {
                throw new Exception("Изменения не найдены в БД");
            }

            $edge_history->status_id = 19;
            if (!$edge_history->save()) {
                $log->addData($edge_history->errors, '$edge_history->errors', __LINE__);
                throw new Exception("Не смог сохранить статус у изменения выработки в таблице EdgeChange у id" . $edge_change_id);
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * ReplaceEdges - Метод отмены последнего изменения выработки
     * @param $edge_src_id - идентификатор выработки
     * @param $mine_id - идентификатор шахты
     * @param $date_time_now - дата создания изменения
     * @return array|string
     * @throws \yii\db\Exception
     */
    public static function ReplaceEdges($mine_id, $edge_src_id = NULL, $date_time_now = 1)
    {
        $log = new LogAmicumFront("ReplaceEdges");
        $result = array();
        try {

            $log->addLog("Начало выполнения метода");

            if ($mine_id == '') {
                throw new Exception("Не передан mine_id");
            }
            if ($date_time_now == 1) {
                $date_time_now = Assistant::GetDateTimeNow();
            }

            /** НАХОДИМ ПОСЛЕДНИЕ ИЗМЕНЕНИЯ ПО ЗАПРАШИВАЕМОЙ ВЫРАБОТКЕ */
            $edge_changes_id = self::GetActualEdgeChangesHistory($mine_id, $edge_src_id);
            if ($edge_changes_id) {
                /** НАХОДИМ ВСЕ ВЫРАБОТКИ ИЗМЕНИВШИЕСЯ ПРИ ЭТОМ */
                $list_edge_id = (new Query())
                    ->select(['edge_id'])
                    ->from(['edge_changes_history'])
                    ->where(['id_edge_changes' => $edge_changes_id])
                    ->column();
                if (!$list_edge_id) {
                    throw new Exception("Не смог получить список выработок");
                }

                /** ПРОВЕРЯЕМ НА НАЛИЧИИ БОЛЕЕ НОВЫХ ИЗМЕНЕНИЙ С ИЗМЕНИВШИМИСЯ ВЫРАБОТКАМИ */
                foreach ($list_edge_id as $edge_id) {
                    $change_edge_id_last = self::GetActualEdgeChangesHistory($mine_id, $edge_id);

                    if ($change_edge_id_last != $edge_changes_id) {
                        throw new Exception("Невозможно отменить, так как есть другие изменения");
                    }
                }

                /** ИЗМЕНЯЕМ СТАТУС ИСТОРИИ ИЗМЕНЕНИЯ ВЫРАБОТКИ */
                $response = self::UpdateEdgeChangeStatus($edge_changes_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Не смог сохранить статус изменения');
                }
            } else {
                $list_edge_id[] = $edge_src_id;
            }

            $edge_cache_controller = (new EdgeCacheController());

            foreach ($list_edge_id as $edge_id) {
                $edge_status_id_last = (new Query())
                    ->select(['status_id'])
                    ->from(['edge_status'])
                    ->where(['edge_id' => $edge_id])
                    ->orderBy('date_time DESC')
                    ->scalar();
                if (!$edge_status_id_last) {
                    $status_id = 19;
                } else {
                    $status_id = $edge_status_id_last == 1 ? 19 : 1;
                }

                $response = EdgeBasicController::saveEdgeStatus($edge_id, $status_id, $date_time_now);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Не смог сохранить статус выработки');
                }

                /****************************** записываем что выработка актуальна в параметры выработки */
                $response = EdgeBasicController::addEdgeParameterWithHandbookValue($edge_id, ParamEnum::STATE, ParameterTypeEnumController::REFERENCE, $status_id, StatusEnumController::ACTUAL, $date_time_now);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка сохранения параметра ' . ParamEnum::STATE);
                }

                if ($status_id == 19) {
                    $result[$edge_id] = array(
                        'status' => 'delete',
                        'mine_id' => $mine_id,
                        'edge_id' => $edge_id,
                    );

                    /***************************** УДАЛЯЕМ СТАРУЮ ВЫРАБОТКУ ИЗ КЭША   *****************************************/
                    $edge_cache_controller->delEdgeScheme($mine_id, $edge_id);
                    $edge_cache_controller->delEdgeMine($mine_id, $edge_id);
                    $edge_cache_controller->delParameterValue($edge_id);

                } else {
                    /** ОБНОВЛЕНИЯ КЕША СХЕМЫ ШАХТЫ */
                    $edge_cache_controller = new EdgeCacheController();
                    $edge_schema = $edge_cache_controller->initEdgeScheme($mine_id, $edge_id);
                    if (!$edge_schema) {
                        throw new Exception('Ошибка инициализации выработки в кеше схема шахты');
                    }

                    /** ОБНОВЛЕНИЕ КЕША ВЫРАБОТОК ШАХТЫ */
                    $edge_mine = $edge_cache_controller->initEdgeMine($mine_id, $edge_id);
                    if (!$edge_mine) {
                        throw new Exception('Ошибка инициализации выработки в главном кеше выработок');
                    }

                    $edge_schema[0]['status'] = 'add';
                    $result[$edge_id] = $edge_schema[0];
                }
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetActualEdgeChangesHistory - Метод получения актуальной EdgeChangesHistory
     * @param $edge_id - идентификатор выработки
     */
    private static function GetActualEdgeChangesHistory($mine_id, $edge_id = -1)
    {
        $where = ['mine_id' => $mine_id, 'edge_changes.status_id' => 1];

        if ($edge_id != -1) {
            $where['view_edge_mine_main.edge_id'] = $edge_id;
        }

        return (new Query())
            ->select(['id_edge_changes'])
            ->from(['edge_changes_history'])
            ->leftJoin('edge_changes', 'edge_changes.id = edge_changes_history.id_edge_changes')
            ->leftJoin('view_edge_mine_main', 'view_edge_mine_main.edge_id = edge_changes_history.edge_id')
            ->where($where)
            ->andWhere(['edge_changes.status_id' => 1])
            ->orderBy('edge_changes_history.id DESC')
            ->scalar();
    }
}
