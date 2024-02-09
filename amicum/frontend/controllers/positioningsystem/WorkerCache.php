<?php


namespace frontend\controllers\positioningsystem;

use Yii;
use yii\db\Query;


class WorkerCache
{
    /**
     * Метод для получения списка параметров для конкретного работника.
     * Позволяет быстро получать связки параметров и их типов из оперативной
     * памяти.
     * @param $worker_id
     * @return array|bool|mixed
     */
    public static function GetParameters($worker_id)
    {
        $cache = Yii::$app->cache;
        $cache_key = 'WorkerTypeParameter_'.$worker_id;

        $result = $cache->get($cache_key);

        if ($result === false) {
            $sql = (new Query())
                ->select([
                    'worker_parameter.parameter_type_id as parameter_type_id',
                    'worker_parameter.parameter_id as parameter_id'
                ])
                ->from('worker_parameter')
                ->innerJoin('worker_object', 'worker_parameter.worker_object_id = worker_object.id')
                ->where([
                    'worker_object.worker_id' => $worker_id
                ])
                ->all();

            if ($sql) {
                foreach ($sql as $row) {
                    $result[] = $row['parameter_type_id'] . '-' . $row['parameter_id'];
                }
                $cache->set($cache_key, $result);
            }
        }

        return $result;
    }

    /**
     * Метод для получения последнего значения конкретного параметра работника.
     * Позволяет быстро получать последние данные из оперативной памяти.
     * @param $worker_id                    -   идентификатор работника
     * @param $parameter_type_id            -   идентификатор типа параметра
     * @param $parameter_id                 -   идентификатор параметра
     * @param int $database_request_flag    -   флаг, требуется ли лезть в базу для поиска значения
     * @return array|bool|mixed             -   строка из таблицы worker_parameter_value или worker_parameter_handbook_value
     */
    public static function GetLastValue($worker_id, $parameter_type_id, $parameter_id, $database_request_flag = 1)
    {
        $cache = Yii::$app->cache;
        $cache_key = 'WorkerParameter_'.$worker_id.'_'.$parameter_type_id.'-'.$parameter_id;

        $result = $cache->get($cache_key);
        if ($result === false && $database_request_flag === 1) {
            //Assistant::VarDump('WorkerId '.$worker_id.'  $parameter '.$parameter_type_id.'-'.$parameter_id);
            if ($parameter_type_id == 1) {
                $result = (new Query())
                    ->select([
                        'worker_parameter_handbook_value.date_time as date_time',
                        'worker_parameter_handbook_value.value as value',
                        'worker_parameter_handbook_value.status_id as status_id'
                    ])
                    ->from('worker_object')
                    ->innerJoin('worker_parameter', 'worker_parameter.worker_object_id = worker_object.id')
                    ->innerJoin('worker_parameter_handbook_value', 'worker_parameter_handbook_value.worker_parameter_id = worker_parameter.id')
                    ->innerJoin('view_worker_parameter_handbook_value_maxDate',
                        'view_worker_parameter_handbook_value_maxDate.worker_parameter_id = worker_parameter_handbook_value.worker_parameter_id 
                and view_worker_parameter_handbook_value_maxDate.date_time_last = worker_parameter_handbook_value.date_time')
                    ->where([
                        'worker_object.worker_id' => $worker_id,
                        'worker_parameter.parameter_id' => $parameter_id,
                        'worker_parameter.parameter_type_id' => $parameter_type_id])
                    ->one();
            } else {
                $result = (new Query())
                    ->select([
                        'worker_parameter_value.date_time as date_time',
                        'worker_parameter_value.value as value',
                        'worker_parameter_value.status_id as status_id'
                    ])
                    ->from('worker_object')
                    ->innerJoin('worker_parameter', 'worker_parameter.worker_object_id = worker_object.id')
                    ->innerJoin('worker_parameter_value', 'worker_parameter_value.worker_parameter_id = worker_parameter.id')
                    ->innerJoin('view_worker_parameter_value_maxDate',
                        'view_worker_parameter_value_maxDate.worker_parameter_id = worker_parameter_value.worker_parameter_id 
                and view_worker_parameter_value_maxDate.date_time_last = worker_parameter_value.date_time')
                    ->where([
                        'worker_object.worker_id' => $worker_id,
                        'worker_parameter.parameter_id' => $parameter_id,
                        'worker_parameter.parameter_type_id' => $parameter_type_id])
                    ->one();
            }

            $cache->set($cache_key, $result/*, 60*/);
        }

        return $result;
    }

    /**
     * Метод для сохранения значения параметра воркера в кэш.
     * Используется для обеспечения ыстрого доступа к последним данным.
     * @param $worker_id            -   идентификатор работника
     * @param $parameter_type_id    -   идентификатор типа параметра
     * @param $parameter_id         -   идентификатор параметра
     * @param $date_time            -   дата и время сохранения
     * @param $value                -   значение параметра
     * @param $status_id            -   идентификатор статуса параметра
     * @return bool         -   результат сохранения данных в кэш (true/false)
     */
    public static function SetValue($worker_id, $parameter_type_id, $parameter_id, $date_time, $value, $status_id)
    {
        $cache = Yii::$app->cache;
        $cache_key = 'WorkerParameter_'.$worker_id.'_'.$parameter_type_id.'-'.$parameter_id;

        $data = array(
            'date_time' => $date_time,
            'value' => $value,
            'status_id' => $status_id
        );

        return $cache->set($cache_key, $data/*, 60*/);
    }
}