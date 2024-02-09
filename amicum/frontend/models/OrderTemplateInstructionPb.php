<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_template_instruction_pb".
 *
 * @property int $id Ключ таблицы привязки шаблона наряда к справочнику инструктажей
 * @property int $order_template_id внешний ключ списка нарядов
 * @property int $instruction_pb_id внешний ключ справочника инструктажей
 *
 * @property InstructionPb $instructionPb
 * @property OrderTemplate $orderTemplate
 */
class OrderTemplateInstructionPb extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_template_instruction_pb';
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
            [['order_template_id', 'instruction_pb_id'], 'required'],
            [['order_template_id', 'instruction_pb_id'], 'integer'],
            [['order_template_id', 'instruction_pb_id'], 'unique', 'targetAttribute' => ['order_template_id', 'instruction_pb_id']],
            [['instruction_pb_id'], 'exist', 'skipOnError' => true, 'targetClass' => InstructionPb::className(), 'targetAttribute' => ['instruction_pb_id' => 'id']],
            [['order_template_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderTemplate::className(), 'targetAttribute' => ['order_template_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Ключ таблицы привязки шаблона наряда к справочнику инструктажей',
            'order_template_id' => 'внешний ключ списка нарядов',
            'instruction_pb_id' => 'внешний ключ справочника инструктажей',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInstructionPb()
    {
        return $this->hasOne(InstructionPb::className(), ['id' => 'instruction_pb_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderTemplate()
    {
        return $this->hasOne(OrderTemplate::className(), ['id' => 'order_template_id']);
    }
}
