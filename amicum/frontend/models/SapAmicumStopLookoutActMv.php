<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_amicum_stop_lookout_act_mv".
 *
 * @property int $id
 * @property string $LOOKOUT_ACTION_ID
 * @property int $INSTRUCTION_ID
 * @property int $PAB_ID
 * @property string $ACTION_NAME
 * @property string $ACTION_DATE
 * @property string $DATE_FACT
 * @property int $COLOR
 */
class SapAmicumStopLookoutActMv extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_amicum_stop_lookout_act_mv';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['INSTRUCTION_ID', 'PAB_ID', 'COLOR'], 'integer'],
            [['ACTION_DATE', 'DATE_FACT'], 'safe'],
            [['LOOKOUT_ACTION_ID'], 'string', 'max' => 100],
            [['ACTION_NAME'], 'string', 'max' => 1055],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'LOOKOUT_ACTION_ID' => 'Lookout Action ID',
            'INSTRUCTION_ID' => 'Instruction ID',
            'PAB_ID' => 'Pab ID',
            'ACTION_NAME' => 'Action Name',
            'ACTION_DATE' => 'Action Date',
            'DATE_FACT' => 'Date Fact',
            'COLOR' => 'Color',
        ];
    }
}
