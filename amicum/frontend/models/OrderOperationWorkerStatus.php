<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_operation_worker_status".
 *
 * @property int $id
 * @property int $operation_worker_id ключ конкретной работы
 * @property int $status_id статус работы
 * @property string $date_time дата смены статуса работы
 * @property int $worker_id Работник сменивший статус работы
 *
 * @property OperationWorker $operationWorker
 * @property Status $status
 * @property Worker $worker
 */
class OrderOperationWorkerStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_operation_worker_status';
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
            [['operation_worker_id', 'status_id', 'date_time'], 'required'],
            [['operation_worker_id', 'status_id', 'worker_id'], 'integer'],
            [['date_time'], 'safe'],
            [['operation_worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => OperationWorker::className(), 'targetAttribute' => ['operation_worker_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'operation_worker_id' => 'ключ конкретной работы',
            'status_id' => 'статус работы',
            'date_time' => 'дата смены статуса работы',
            'worker_id' => 'Работник сменивший статус работы',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationWorker()
    {
        return $this->hasOne(OperationWorker::className(), ['id' => 'operation_worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
