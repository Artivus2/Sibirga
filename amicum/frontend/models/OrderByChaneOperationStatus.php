<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_by_chane_operation_status".
 *
 * @property int $id
 * @property string $duration_fact
 * @property string $value_fact
 * @property int $role_id
 * @property int $status_id
 * @property string $date_time
 * @property int $order_by_chane_operation_id
 * @property int $operation_fact_id
 *
 * @property OrderByChaneOperation $orderByChaneOperation
 * @property Operation $operationFact
 */
class OrderByChaneOperationStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_by_chane_operation_status';
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
            [['duration_fact', 'date_time'], 'safe'],
            [['role_id', 'status_id', 'order_by_chane_operation_id', 'operation_fact_id'], 'integer'],
            [['date_time', 'order_by_chane_operation_id', 'operation_fact_id'], 'required'],
            [['value_fact'], 'string', 'max' => 255],
            [['order_by_chane_operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderByChaneOperation::className(), 'targetAttribute' => ['order_by_chane_operation_id' => 'id']],
            [['operation_fact_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['operation_fact_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'duration_fact' => 'Duration Fact',
            'value_fact' => 'Value Fact',
            'role_id' => 'Role ID',
            'status_id' => 'Status ID',
            'date_time' => 'Date Time',
            'order_by_chane_operation_id' => 'Order By Chane Operation ID',
            'operation_fact_id' => 'Operation Fact ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByChaneOperation()
    {
        return $this->hasOne(OrderByChaneOperation::className(), ['id' => 'order_by_chane_operation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationFact()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_fact_id']);
    }
}
