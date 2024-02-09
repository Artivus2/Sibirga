<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "situation_fact_parameter".
 *
 * @property int $id
 * @property int $situation_fact_id
 * @property int $parameter_id
 * @property int $parameter_type_id
 *
 * @property Parameter $parameter
 * @property ParameterType $parameterType
 * @property SituationJournal $situationFact
 * @property SituationFactParameterValue[] $situationFactParameterValues
 */
class SituationFactParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'situation_fact_parameter';
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
            [['situation_fact_id', 'parameter_id', 'parameter_type_id'], 'required'],
            [['situation_fact_id', 'parameter_id', 'parameter_type_id'], 'integer'],
            [['parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => Parameter::className(), 'targetAttribute' => ['parameter_id' => 'id']],
            [['parameter_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ParameterType::className(), 'targetAttribute' => ['parameter_type_id' => 'id']],
            [['situation_fact_id'], 'exist', 'skipOnError' => true, 'targetClass' => SituationJournal::className(), 'targetAttribute' => ['situation_fact_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'situation_fact_id' => 'Situation Fact ID',
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParameter()
    {
        return $this->hasOne(Parameter::className(), ['id' => 'parameter_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParameterType()
    {
        return $this->hasOne(ParameterType::className(), ['id' => 'parameter_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituationFact()
    {
        return $this->hasOne(SituationJournal::className(), ['id' => 'situation_fact_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituationFactParameterValues()
    {
        return $this->hasMany(SituationFactParameterValue::className(), ['situation_fact_parameter_id' => 'id']);
    }
}
