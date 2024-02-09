<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_instruction_pb".
 *
 * @property int $id Ключ таблицы привязки наряда к справочнику инструктажей
 * @property int $order_id внешний ключ списка нарядов
 * @property int $instruction_pb_id внешний ключ справочника инструктажей
 *
 * @property Order $order
 * @property InstructionPb $instructionPb
 */
class OrderInstructionPb extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_instruction_pb';
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
            [['order_id', 'instruction_pb_id'], 'required'],
            [['order_id', 'instruction_pb_id'], 'integer'],
            [['order_id', 'instruction_pb_id'], 'unique', 'targetAttribute' => ['order_id', 'instruction_pb_id']],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Order::className(), 'targetAttribute' => ['order_id' => 'id']],
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
            'order_id' => 'Order ID',
            'instruction_pb_id' => 'Instruction Pb ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::className(), ['id' => 'order_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInstructionPb()
    {
        return $this->hasOne(InstructionPb::className(), ['id' => 'instruction_pb_id']);
    }
}
