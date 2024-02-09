<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "factors_of_contingent".
 *
 * @property int $id
 * @property int $harmful_factors_id
 * @property int $contingent_id
 *
 * @property Contingent $contingent
 * @property HarmfulFactors $harmfulFactors
 */
class FactorsOfContingent extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'factors_of_contingent';
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
            [['harmful_factors_id', 'contingent_id'], 'required'],
            [['harmful_factors_id', 'contingent_id'], 'integer'],
            [['contingent_id'], 'exist', 'skipOnError' => true, 'targetClass' => Contingent::className(), 'targetAttribute' => ['contingent_id' => 'id']],
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
            'harmful_factors_id' => 'Harmful Factors ID',
            'contingent_id' => 'Contingent ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContingent()
    {
        return $this->hasOne(Contingent::className(), ['id' => 'contingent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHarmfulFactors()
    {
        return $this->hasOne(HarmfulFactors::className(), ['id' => 'harmful_factors_id']);
    }
}
