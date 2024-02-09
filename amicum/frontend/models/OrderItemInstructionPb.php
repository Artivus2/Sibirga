<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_item_instruction_pb".
 *
 * @property int $id ключ инструктажа работника
 * @property int $instruction_pb_id ключ инструктажа
 * @property int $order_history_id ключ истории сохранения наряда
 *
 * @property OrderHistory $orderHistory
 * @property InstructionPb $instructionPb
 */
class OrderItemInstructionPb extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_item_instruction_pb';
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
            [['instruction_pb_id', 'order_history_id'], 'required'],
            [['instruction_pb_id', 'order_history_id'], 'integer'],
            [['instruction_pb_id', 'order_history_id'], 'unique', 'targetAttribute' => ['instruction_pb_id', 'order_history_id']],
            [['order_history_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderHistory::className(), 'targetAttribute' => ['order_history_id' => 'id']],
            [['instruction_pb_id'], 'exist', 'skipOnError' => true, 'targetClass' => InstructionPb::className(), 'targetAttribute' => ['instruction_pb_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'instruction_pb_id' => 'Instruction Pb ID',
            'order_history_id' => 'Order History ID',
        ];
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
     * Gets query for [[InstructionPb]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInstructionPb()
    {
        return $this->hasOne(InstructionPb::className(), ['id' => 'instruction_pb_id']);
    }
}
