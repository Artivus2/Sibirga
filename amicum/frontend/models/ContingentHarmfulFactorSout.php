<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "contingent_harmful_factor_sout".
 *
 * @property int $id
 * @property int $contingent_from_sout_id Внешний идентификатор контингента СОУТ
 * @property int $harmful_factors_id Внешний идентификатор вредных факторов
 *
 * @property ContingentFromSout $contingentFromSout
 * @property HarmfulFactors $harmfulFactors
 */
class ContingentHarmfulFactorSout extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'contingent_harmful_factor_sout';
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
            [['contingent_from_sout_id', 'harmful_factors_id'], 'required'],
            [['contingent_from_sout_id', 'harmful_factors_id'], 'integer'],
            [['contingent_from_sout_id'], 'exist', 'skipOnError' => true, 'targetClass' => ContingentFromSout::className(), 'targetAttribute' => ['contingent_from_sout_id' => 'id']],
            [['harmful_factors_id'], 'exist', 'skipOnError' => true, 'targetClass' => HarmfulFactors::className(), 'targetAttribute' => ['harmful_factors_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'contingent_from_sout_id' => 'Внешний идентификатор контингента СОУТ',
            'harmful_factors_id' => 'Внешний идентификатор вредных факторов',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContingentFromSout()
    {
        return $this->hasOne(ContingentFromSout::className(), ['id' => 'contingent_from_sout_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHarmfulFactors()
    {
        return $this->hasOne(HarmfulFactors::className(), ['id' => 'harmful_factors_id']);
    }
}
