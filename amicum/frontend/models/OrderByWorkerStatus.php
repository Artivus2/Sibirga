<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_by_worker_status".
 *
 * @property int $id
 * @property int $operation_by_worker_id
 * @property int $status_id
 * @property string $date_time
 *
 * @property OperationByWorker $operationByWorker
 * @property Status $status
 */
class OrderByWorkerStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_by_worker_status';
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
            [['operation_by_worker_id', 'status_id', 'date_time'], 'required'],
            [['operation_by_worker_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['operation_by_worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => OperationByWorker::className(), 'targetAttribute' => ['operation_by_worker_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'operation_by_worker_id' => 'Operation By Worker ID',
            'status_id' => 'Status ID',
            'date_time' => 'Date Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationByWorker()
    {
        return $this->hasOne(OperationByWorker::className(), ['id' => 'operation_by_worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
