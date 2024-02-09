<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_by_chane_group_operation".
 *
 * @property int $id
 * @property int $order_by_chane_id
 * @property int $place_id
 * @property int $group_operation_id
 * @property string $date_time
 * @property int $unit_id
 * @property string $value_plan
 * @property string $duration_plan
 *
 * @property OrderByChaneByWorker[] $orderByChaneByWorkers
 * @property GroupOperation $groupOperation
 * @property OrderByChane $orderByChane
 * @property Place $place
 * @property Unit $unit
 * @property OrderByChaneGroupOperationStatus[] $orderByChaneGroupOperationStatuses
 * @property OrderByChaneOperation[] $orderByChaneOperations
 */
class OrderByChaneGroupOperation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_by_chane_group_operation';
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
            [['order_by_chane_id', 'place_id', 'group_operation_id', 'date_time', 'unit_id', 'value_plan', 'duration_plan'], 'required'],
            [['order_by_chane_id', 'place_id', 'group_operation_id', 'unit_id'], 'integer'],
            [['date_time', 'duration_plan'], 'safe'],
            [['value_plan'], 'string', 'max' => 255],
            [['group_operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => GroupOperation::className(), 'targetAttribute' => ['group_operation_id' => 'id']],
            [['order_by_chane_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderByChane::className(), 'targetAttribute' => ['order_by_chane_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
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
            'order_by_chane_id' => 'Order By Chane ID',
            'place_id' => 'Place ID',
            'group_operation_id' => 'Group Operation ID',
            'date_time' => 'Date Time',
            'unit_id' => 'Unit ID',
            'value_plan' => 'Value Plan',
            'duration_plan' => 'Duration Plan',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByChaneByWorkers()
    {
        return $this->hasMany(OrderByChaneByWorker::className(), ['order_by_chane_group_operation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupOperation()
    {
        return $this->hasOne(GroupOperation::className(), ['id' => 'group_operation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByChane()
    {
        return $this->hasOne(OrderByChane::className(), ['id' => 'order_by_chane_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
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
    public function getOrderByChaneGroupOperationStatuses()
    {
        return $this->hasMany(OrderByChaneGroupOperationStatus::className(), ['order_by_chane_group_operation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByChaneOperations()
    {
        return $this->hasMany(OrderByChaneOperation::className(), ['order_by_chane_group_operation_id' => 'id']);
    }
}
