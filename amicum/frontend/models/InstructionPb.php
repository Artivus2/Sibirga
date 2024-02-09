<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "instruction_pb".
 *
 * @property int $id Ключ справочника инструктажей по ПБ
 * @property string $title Название инструктажа по ПБ
 * @property string $repeat Повторяемость инструктажа ПБ в периоде
 * @property int $type_instruction_pb_id Внешний ключ справочника типов  инструктажей 
 *
 * @property TypeInstructionPb $typeInstructionPb
 * @property OrderInstructionPb[] $orderInstructionPbs
 * @property Order[] $orders
 * @property OrderTemplateInstructionPb[] $orderTemplateInstructionPbs
 * @property OrderTemplate[] $orderTemplates
 * @property TimetableInstructionPb[] $timetableInstructionPbs
 */
class InstructionPb extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'instruction_pb';
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
            [['title', 'repeat'], 'required'],
            [['type_instruction_pb_id'], 'integer'],
            [['title', 'repeat'], 'string', 'max' => 255],
            [['title'], 'unique'],
            [['title', 'repeat'], 'unique', 'targetAttribute' => ['title', 'repeat']],
            [['type_instruction_pb_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypeInstructionPb::className(), 'targetAttribute' => ['type_instruction_pb_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Ключ справочника инструктажей по ПБ',
            'title' => 'Название инструктажа по ПБ',
            'repeat' => 'Повторяемость инструктажа ПБ в периоде',
            'type_instruction_pb_id' => 'Внешний ключ справочника типов  инструктажей ',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTypeInstructionPb()
    {
        return $this->hasOne(TypeInstructionPb::className(), ['id' => 'type_instruction_pb_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderInstructionPbs()
    {
        return $this->hasMany(OrderInstructionPb::className(), ['instruction_pb_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Order::className(), ['id' => 'order_id'])->viaTable('order_instruction_pb', ['instruction_pb_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderTemplateInstructionPbs()
    {
        return $this->hasMany(OrderTemplateInstructionPb::className(), ['instruction_pb_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderTemplates()
    {
        return $this->hasMany(OrderTemplate::className(), ['id' => 'order_template_id'])->viaTable('order_template_instruction_pb', ['instruction_pb_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTimetableInstructionPbs()
    {
        return $this->hasMany(TimetableInstructionPb::className(), ['instruction_pb_id' => 'id']);
    }
}
