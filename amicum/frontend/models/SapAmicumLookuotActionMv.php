<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_amicum_lookuot_action_mv".
 *
 * @property int $REG_DAN_EFFECT_ID
 * @property string $DATE_CHECK
 * @property int $HRSROOT_ID_A
 * @property string $DATA_CREATED_REG_DAN_EFFECT
 * @property string $PRIS_PR
 * @property int $INSTRUCTION_ID
 * @property string $DATA_INSTRUCTION
 * @property int $LOOKOUT_ACTION_ID
 * @property int $LOOKOUT_ID
 * @property string $DAN_EFFECT
 * @property int $ACTION_OTV_ID
 * @property string $PLACE_NAME
 * @property string $REF_NORM_DOC_ID
 * @property int $PAB_ID
 * @property string $ERROR_POINT
 */
class SapAmicumLookuotActionMv extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_amicum_lookuot_action_mv';
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
            [['REG_DAN_EFFECT_ID'], 'required'],
            [['REG_DAN_EFFECT_ID', 'HRSROOT_ID_A', 'INSTRUCTION_ID', 'LOOKOUT_ACTION_ID', 'LOOKOUT_ID', 'ACTION_OTV_ID', 'PAB_ID'], 'integer'],
            [['DATE_CHECK', 'DATA_CREATED_REG_DAN_EFFECT', 'DATA_INSTRUCTION'], 'safe'],
            [['PRIS_PR', 'REF_NORM_DOC_ID'], 'string', 'max' => 45],
            [['DAN_EFFECT'], 'string', 'max' => 900],
            [['PLACE_NAME', 'ERROR_POINT'], 'string', 'max' => 255],
            [['REG_DAN_EFFECT_ID'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'REG_DAN_EFFECT_ID' => 'Reg Dan Effect ID',
            'DATE_CHECK' => 'Date Check',
            'HRSROOT_ID_A' => 'Hrsroot Id A',
            'DATA_CREATED_REG_DAN_EFFECT' => 'Data Created Reg Dan Effect',
            'PRIS_PR' => 'Pris Pr',
            'INSTRUCTION_ID' => 'Instruction ID',
            'DATA_INSTRUCTION' => 'Data Instruction',
            'LOOKOUT_ACTION_ID' => 'Lookout Action ID',
            'LOOKOUT_ID' => 'Lookout ID',
            'DAN_EFFECT' => 'Dan Effect',
            'ACTION_OTV_ID' => 'Action Otv ID',
            'PLACE_NAME' => 'Place Name',
            'REF_NORM_DOC_ID' => 'Ref Norm Doc ID',
            'PAB_ID' => 'Pab ID',
            'ERROR_POINT' => 'Error Point',
        ];
    }
}
