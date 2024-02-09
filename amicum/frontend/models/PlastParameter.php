<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "plast_parameter".
 *
 * @property int $id
 * @property int $plast_id
 * @property int $parameter_id
 * @property int $parameter_type_id
 *
 * @property NominalPlastParameter[] $nominalPlastParameters
 * @property Parameter $parameter
 * @property ParameterType $parameterType
 * @property Plast $plast
 * @property PlastParameterHandbookValue[] $plastParameterHandbookValues
 * @property PlastParameterSensor[] $plastParameterSensors
 * @property PlastParameterValue[] $plastParameterValues
 */
class PlastParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'plast_parameter';
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
            [['plast_id', 'parameter_id', 'parameter_type_id'], 'required'],
            [['plast_id', 'parameter_id', 'parameter_type_id'], 'integer'],
            [['parameter_type_id', 'parameter_id', 'plast_id'], 'unique', 'targetAttribute' => ['parameter_type_id', 'parameter_id', 'plast_id']],
            [['parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => Parameter::className(), 'targetAttribute' => ['parameter_id' => 'id']],
            [['parameter_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ParameterType::className(), 'targetAttribute' => ['parameter_type_id' => 'id']],
            [['plast_id'], 'exist', 'skipOnError' => true, 'targetClass' => Plast::className(), 'targetAttribute' => ['plast_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'plast_id' => 'Plast ID',
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalPlastParameters()
    {
        return $this->hasMany(NominalPlastParameter::className(), ['plast_parameter_id' => 'id']);
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
    public function getPlast()
    {
        return $this->hasOne(Plast::className(), ['id' => 'plast_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlastParameterHandbookValues()
    {
        return $this->hasMany(PlastParameterHandbookValue::className(), ['plast_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlastParameterSensors()
    {
        return $this->hasMany(PlastParameterSensor::className(), ['plast_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlastParameterValues()
    {
        return $this->hasMany(PlastParameterValue::className(), ['plast_parameter_id' => 'id']);
    }
}
