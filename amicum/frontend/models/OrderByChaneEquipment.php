<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_by_chane_equipment".
 *
 * @property int $id
 * @property int $order_by_chane_id
 * @property int $equipment_id
 *
 * @property Equipment $equipment
 * @property OrderByChane $orderByChane
 */
class OrderByChaneEquipment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_by_chane_equipment';
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
            [['order_by_chane_id', 'equipment_id'], 'required'],
            [['order_by_chane_id', 'equipment_id'], 'integer'],
            [['equipment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Equipment::className(), 'targetAttribute' => ['equipment_id' => 'id']],
            [['order_by_chane_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderByChane::className(), 'targetAttribute' => ['order_by_chane_id' => 'id']],
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
            'equipment_id' => 'Equipment ID',
        ];
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
    public function getOrderByChane()
    {
        return $this->hasOne(OrderByChane::className(), ['id' => 'order_by_chane_id']);
    }
}
