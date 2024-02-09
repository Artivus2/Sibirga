<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_instruction_givers_mv".
 *
 * @property int $instruction_givers_id
 * @property int $instruction_id
 * @property int $hrsroot_id
 */
class SapInstructionGiversMv extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_instruction_givers_mv';
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
            [['instruction_givers_id'], 'required'],
            [['instruction_givers_id', 'instruction_id', 'hrsroot_id'], 'integer'],
            [['instruction_givers_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'instruction_givers_id' => 'Instruction Givers ID',
            'instruction_id' => 'Instruction ID',
            'hrsroot_id' => 'Hrsroot ID',
        ];
    }
}
