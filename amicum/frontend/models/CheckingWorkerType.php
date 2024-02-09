<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "checking_worker_type".
 *
 * @property int $id Идентификатор таблицы 
 * @property int $worker_id Внешний ключ работника
 * @property int $worker_type_id Внешний ключ типа работника инспектор, присутствовал, ответственный)
 * @property int $checking_id Внешний ключ проверки
 * @property string $instruct_givers_id внешний ключ из sap выдавшего предписание
 * @property string $date_time_sync
 * @property string $instruct_rtn_id
 * @property string $date_time_sync_rostex
 * @property string $instruct_pab_id
 * @property string $date_time_sync_pab
 * @property string $instruct_nn_id
 * @property string $date_time_sync_nn
 *
 * @property Checking $checking
 * @property Worker $worker
 * @property WorkerType $workerType
 */
class CheckingWorkerType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'checking_worker_type';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_amicum2');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['worker_id', 'worker_type_id', 'checking_id'], 'required'],
            [['worker_id', 'worker_type_id', 'checking_id'], 'integer'],
            [['date_time_sync', 'date_time_sync_rostex', 'date_time_sync_pab', 'date_time_sync_nn'], 'safe'],
            [['instruct_givers_id', 'instruct_pab_id'], 'string', 'max' => 45],
            [['instruct_rtn_id', 'instruct_nn_id'], 'string', 'max' => 255],
            [['checking_id'], 'exist', 'skipOnError' => true, 'targetClass' => Checking::className(), 'targetAttribute' => ['checking_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
            [['worker_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => WorkerType::className(), 'targetAttribute' => ['worker_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор таблицы ',
            'worker_id' => 'Внешний ключ работника',
            'worker_type_id' => 'Внешний ключ типа работника инспектор, присутствовал, ответственный)',
            'checking_id' => 'Внешний ключ проверки',
            'instruct_givers_id' => 'внешний ключ из sap выдавшего предписание',
            'date_time_sync' => 'Date Time Sync',
            'instruct_rtn_id' => 'Instruct Rtn ID',
            'date_time_sync_rostex' => 'Date Time Sync Rostex',
            'instruct_pab_id' => 'Instruct Pab ID',
            'date_time_sync_pab' => 'Date Time Sync Pab',
            'instruct_nn_id' => 'Instruct Nn ID',
            'date_time_sync_nn' => 'Date Time Sync Nn',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChecking()
    {
        return $this->hasOne(Checking::className(), ['id' => 'checking_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerType()
    {
        return $this->hasOne(WorkerType::className(), ['id' => 'worker_type_id']);
    }
}
