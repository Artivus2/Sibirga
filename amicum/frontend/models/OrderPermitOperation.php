<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_permit_operation".
 *
 * @property int $id Ключ таблицы плановых объемов работ по наряду
 * @property int $order_permit_id внешний ключ привязки наряда к месту
 * @property int $operation_id внешний ключ справочника операций
 * @property string|null $operation_value_plan Плановое значение объема работы, которго должен выполнить работник
 * @property string|null $operation_value_fact Фактическоезначение объема работы, которго должен выполнить работник
 * @property int $equipment_id связка с оборудованием
 *
 * @property Operation $operation
 * @property Equipment $equipment
 * @property OrderPermit $orderPermit
 */
class OrderPermitOperation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_permit_operation';
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
            [['order_permit_id', 'operation_id', 'equipment_id'], 'required'],
            [['order_permit_id', 'operation_id', 'equipment_id'], 'integer'],
            [['operation_value_plan', 'operation_value_fact'], 'string', 'max' => 45],
            [['operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['operation_id' => 'id']],
            [['equipment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Equipment::className(), 'targetAttribute' => ['equipment_id' => 'id']],
            [['order_permit_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderPermit::className(), 'targetAttribute' => ['order_permit_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_permit_id' => 'Order Permit ID',
            'operation_id' => 'Operation ID',
            'operation_value_plan' => 'Operation Value Plan',
            'operation_value_fact' => 'Operation Value Fact',
            'equipment_id' => 'Equipment ID',
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
    public function getEquipment()
    {
        return $this->hasOne(Equipment::className(), ['id' => 'equipment_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPermit()
    {
        return $this->hasOne(OrderPermit::className(), ['id' => 'order_permit_id']);
    }
}
