<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "operation_by_worker".
 *
 * @property int $id
 * @property int $order_by_worker_id
 * @property int $operation_id
 * @property int $unit_id
 * @property string $value_plan
 * @property string $duration_plan
 *
 * @property Operation $operation
 * @property OrderByWorker $orderByWorker
 * @property Unit $unit
 * @property OperationByWorkerStatus[] $operationByWorkerStatuses
 * @property OrderByWorkerStatus[] $orderByWorkerStatuses
 */
class OperationByWorker extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'operation_by_worker';
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
            [['order_by_worker_id', 'operation_id', 'unit_id', 'value_plan', 'duration_plan'], 'required'],
            [['order_by_worker_id', 'operation_id', 'unit_id'], 'integer'],
            [['duration_plan'], 'safe'],
            [['value_plan'], 'string', 'max' => 255],
            [['operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['operation_id' => 'id']],
            [['order_by_worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderByWorker::className(), 'targetAttribute' => ['order_by_worker_id' => 'id']],
            [['unit_id'], 'exist', 'skipOnError' => true, 'targetClass' => Unit::className(), 'targetAttribute' => ['unit_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_by_worker_id' => 'Order By Worker ID',
            'operation_id' => 'Operation ID',
            'unit_id' => 'Unit ID',
            'value_plan' => 'Value Plan',
            'duration_plan' => 'Duration Plan',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperation()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByWorker()
    {
        return $this->hasOne(OrderByWorker::className(), ['id' => 'order_by_worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUnit()
    {
        return $this->hasOne(Unit::className(), ['id' => 'unit_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationByWorkerStatuses()
    {
        return $this->hasMany(OperationByWorkerStatus::className(), ['operation_by_worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByWorkerStatuses()
    {
        return $this->hasMany(OrderByWorkerStatus::className(), ['operation_by_worker_id' => 'id']);
    }
}
