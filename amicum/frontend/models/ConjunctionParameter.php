<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "conjunction_parameter".
 *
 * @property int $id
 * @property int $conjunction_id
 * @property int $parameter_id
 * @property int $parameter_type_id
 *
 * @property Conjunction $conjunction
 * @property Parameter $parameter
 * @property ParameterType $parameterType
 * @property ConjunctionParameterHandbookValue[] $conjunctionParameterHandbookValues
 * @property ConjunctionParameterSensor[] $conjunctionParameterSensors
 * @property ConjunctionParameterValue[] $conjunctionParameterValues
 * @property NominalConjunctionParameter[] $nominalConjunctionParameters
 */
class ConjunctionParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'conjunction_parameter';
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
            [['conjunction_id', 'parameter_id', 'parameter_type_id'], 'required'],
            [['conjunction_id', 'parameter_id', 'parameter_type_id'], 'integer'],
            [['conjunction_id', 'parameter_id', 'parameter_type_id'], 'unique', 'targetAttribute' => ['conjunction_id', 'parameter_id', 'parameter_type_id']],
            [['conjunction_id'], 'exist', 'skipOnError' => true, 'targetClass' => Conjunction::className(), 'targetAttribute' => ['conjunction_id' => 'id']],
            [['parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => Parameter::className(), 'targetAttribute' => ['parameter_id' => 'id']],
            [['parameter_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ParameterType::className(), 'targetAttribute' => ['parameter_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'conjunction_id' => 'Conjunction ID',
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConjunction()
    {
        return $this->hasOne(Conjunction::className(), ['id' => 'conjunction_id']);
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
    public function getConjunctionParameterHandbookValues()
    {
        return $this->hasMany(ConjunctionParameterHandbookValue::className(), ['conjunction_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConjunctionParameterSensors()
    {
        return $this->hasMany(ConjunctionParameterSensor::className(), ['conjunction_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConjunctionParameterValues()
    {
        return $this->hasMany(ConjunctionParameterValue::className(), ['conjunction_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalConjunctionParameters()
    {
        return $this->hasMany(NominalConjunctionParameter::className(), ['conjunction_parameter_id' => 'id']);
    }
}
