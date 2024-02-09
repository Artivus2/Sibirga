<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_item_equipment".
 *
 * @property int $id
 * @property int $equipment_id ключ оборудования
 * @property int $status_id ключ статуса
 * @property int $order_history_id ключ истории сохранения наряда
 * @property string|null $equipments_json ограничения по наряду оборудования
 *
 * @property Equipment $equipment
 * @property OrderHistory $orderHistory
 * @property Status $status
 */
class OrderItemEquipment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_item_equipment';
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
            [['equipment_id', 'status_id', 'order_history_id'], 'required'],
            [['equipment_id', 'status_id', 'order_history_id'], 'integer'],
            [['equipments_json'], 'safe'],
            [['equipment_id', 'status_id', 'order_history_id'], 'unique', 'targetAttribute' => ['equipment_id', 'status_id', 'order_history_id']],
            [['equipment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Equipment::className(), 'targetAttribute' => ['equipment_id' => 'id']],
            [['order_history_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderHistory::className(), 'targetAttribute' => ['order_history_id' => 'id']],
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
            'equipment_id' => 'Equipment ID',
            'status_id' => 'Status ID',
            'order_history_id' => 'Order History ID',
            'equipments_json' => 'Equipments Json',
        ];
    }

    /**
     * Gets query for [[Equipment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEquipment()
    {
        return $this->hasOne(Equipment::className(), ['id' => 'equipment_id']);
    }

    /**
     * Gets query for [[OrderHistory]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderHistory()
    {
        return $this->hasOne(OrderHistory::className(), ['id' => 'order_history_id']);
    }

    /**
     * Gets query for [[Status]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
