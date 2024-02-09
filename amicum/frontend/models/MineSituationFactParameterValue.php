<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "mine_situation_fact_parameter_value".
 *
 * @property int $id
 * @property int $mine_situation_fact_parameter_id
 * @property int $pla_activity_fact_id
 *
 * @property MineSituationFactParameter $mineSituationFactParameter
 * @property PlaActivityFact $plaActivityFact
 */
class MineSituationFactParameterValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mine_situation_fact_parameter_value';
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
            [['mine_situation_fact_parameter_id', 'pla_activity_fact_id'], 'required'],
            [['mine_situation_fact_parameter_id', 'pla_activity_fact_id'], 'integer'],
            [['mine_situation_fact_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => MineSituationFactParameter::className(), 'targetAttribute' => ['mine_situation_fact_parameter_id' => 'id']],
            [['pla_activity_fact_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlaActivityFact::className(), 'targetAttribute' => ['pla_activity_fact_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mine_situation_fact_parameter_id' => 'Mine Situation Fact Parameter ID',
            'pla_activity_fact_id' => 'Pla Activity Fact ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineSituationFactParameter()
    {
        return $this->hasOne(MineSituationFactParameter::className(), ['id' => 'mine_situation_fact_parameter_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaActivityFact()
    {
        return $this->hasOne(PlaActivityFact::className(), ['id' => 'pla_activity_fact_id']);
    }
}
