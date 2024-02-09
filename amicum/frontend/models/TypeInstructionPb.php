<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "type_instruction_pb".
 *
 * @property int $id
 * @property string $title Наименование типа инструктажа
 *
 * @property InstructionPb[] $instructionPbs
 */
class TypeInstructionPb extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'type_instruction_pb';
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
            [['title'], 'required'],
            [['title'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInstructionPbs()
    {
        return $this->hasMany(InstructionPb::className(), ['type_instruction_pb_id' => 'id']);
    }
}
