<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_by_chane_group_operation_status".
 *
 * @property int $id
 * @property int $order_by_chane_group_operation_id
 * @property int $status_id
 * @property string $date_time
 * @property string $value_fact
 * @property string $duration_fact
 * @property int $group_operation_fact_id
 *
 * @property GroupOperation $groupOperationFact
 * @property OrderByChaneGroupOperation $orderByChaneGroupOperation
 * @property Status $status
 */
class OrderByChaneGroupOperationStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_by_chane_group_operation_status';
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
            [['order_by_chane_group_operation_id', 'status_id', 'date_time', 'value_fact', 'duration_fact', 'group_operation_fact_id'], 'required'],
            [['order_by_chane_group_operation_id', 'status_id', 'group_operation_fact_id'], 'integer'],
            [['date_time', 'duration_fact'], 'safe'],
            [['value_fact'], 'string', 'max' => 255],
            [['group_operation_fact_id'], 'exist', 'skipOnError' => true, 'targetClass' => GroupOperation::className(), 'targetAttribute' => ['group_operation_fact_id' => 'id']],
            [['order_by_chane_group_operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderByChaneGroupOperation::className(), 'targetAttribute' => ['order_by_chane_group_operation_id' => 'id']],
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
            'order_by_chane_group_operation_id' => 'Order By Chane Group Operation ID',
            'status_id' => 'Status ID',
            'date_time' => 'Date Time',
            'value_fact' => 'Value Fact',
            'duration_fact' => 'Duration Fact',
            'group_operation_fact_id' => 'Group Operation Fact ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupOperationFact()
    {
        return $this->hasOne(GroupOperation::className(), ['id' => 'group_operation_fact_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByChaneGroupOperation()
    {
        return $this->hasOne(OrderByChaneGroupOperation::className(), ['id' => 'order_by_chane_group_operation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
