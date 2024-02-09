<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "operation_by_worker_status".
 *
 * @property int $id
 * @property int $operation_by_worker_id
 * @property int $status_id
 * @property string $date_time
 * @property int $fact_operation_id
 * @property int $unit_id
 * @property string $value_fact
 * @property string $duration_fact
 *
 * @property Operation $factOperation
 * @property OperationByWorker $operationByWorker
 * @property Status $status
 * @property Unit $unit
 */
class OperationByWorkerStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'operation_by_worker_status';
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
            [['operation_by_worker_id', 'status_id', 'date_time', 'fact_operation_id', 'unit_id', 'value_fact', 'duration_fact'], 'required'],
            [['operation_by_worker_id', 'status_id', 'fact_operation_id', 'unit_id'], 'integer'],
            [['date_time', 'duration_fact'], 'safe'],
            [['value_fact'], 'string', 'max' => 255],
            [['fact_operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['fact_operation_id' => 'id']],
            [['operation_by_worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => OperationByWorker::className(), 'targetAttribute' => ['operation_by_worker_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
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
            'operation_by_worker_id' => 'Operation By Worker ID',
            'status_id' => 'Status ID',
            'date_time' => 'Date Time',
            'fact_operation_id' => 'Fact Operation ID',
            'unit_id' => 'Unit ID',
            'value_fact' => 'Value Fact',
            'duration_fact' => 'Duration Fact',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFactOperation()
    {
        return $this->hasOne(Operation::className(), ['id' => 'fact_operation_id']);
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUnit()
    {
        return $this->hasOne(Unit::className(), ['id' => 'unit_id']);
    }
}
