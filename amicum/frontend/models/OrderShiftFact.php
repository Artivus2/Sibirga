<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_shift_fact".
 *
 * @property int $id
 * @property int $operation_regulation_fact_id
 * @property string $date_time
 * @property int $order_id
 *
 * @property OperationRegulationFact $operationRegulationFact
 * @property Order $order
 */
class OrderShiftFact extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_shift_fact';
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
            [['operation_regulation_fact_id', 'date_time', 'order_id'], 'required'],
            [['operation_regulation_fact_id', 'order_id'], 'integer'],
            [['date_time'], 'safe'],
            [['operation_regulation_fact_id'], 'exist', 'skipOnError' => true, 'targetClass' => OperationRegulationFact::className(), 'targetAttribute' => ['operation_regulation_fact_id' => 'id']],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Order::className(), 'targetAttribute' => ['order_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'operation_regulation_fact_id' => 'Operation Regulation Fact ID',
            'date_time' => 'Date Time',
            'order_id' => 'Order ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationRegulationFact()
    {
        return $this->hasOne(OperationRegulationFact::className(), ['id' => 'operation_regulation_fact_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::className(), ['id' => 'order_id']);
    }
}
