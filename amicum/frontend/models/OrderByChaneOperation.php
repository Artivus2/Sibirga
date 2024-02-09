<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_by_chane_operation".
 *
 * @property int $id
 * @property string $durationPlan
 * @property string $valuePlan
 * @property int $role_id
 * @property int $order_by_chane_group_operation_id
 * @property int $operation_id
 *
 * @property OrderByChaneGroupOperation $orderByChaneGroupOperation
 * @property Operation $operation
 * @property OrderByChaneOperationEquipment[] $orderByChaneOperationEquipments
 * @property OrderByChaneOperationStatus[] $orderByChaneOperationStatuses
 */
class OrderByChaneOperation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_by_chane_operation';
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
            [['durationPlan'], 'safe'],
            [['role_id', 'order_by_chane_group_operation_id', 'operation_id'], 'integer'],
            [['order_by_chane_group_operation_id', 'operation_id'], 'required'],
            [['valuePlan'], 'string', 'max' => 255],
            [['order_by_chane_group_operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderByChaneGroupOperation::className(), 'targetAttribute' => ['order_by_chane_group_operation_id' => 'id']],
            [['operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['operation_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'durationPlan' => 'Duration Plan',
            'valuePlan' => 'Value Plan',
            'role_id' => 'Role ID',
            'order_by_chane_group_operation_id' => 'Order By Chane Group Operation ID',
            'operation_id' => 'Operation ID',
        ];
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
    public function getOperation()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByChaneOperationEquipments()
    {
        return $this->hasMany(OrderByChaneOperationEquipment::className(), ['order_by_chane_operation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByChaneOperationStatuses()
    {
        return $this->hasMany(OrderByChaneOperationStatus::className(), ['order_by_chane_operation_id' => 'id']);
    }
}
