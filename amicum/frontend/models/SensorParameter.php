<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sensor_parameter".
 *
 * @property int $id
 * @property int $sensor_id
 * @property int $parameter_id
 * @property int $parameter_type_id
 *
 * @property NominalSensorParameter[] $nominalSensorParameters
 * @property Parameter $parameter
 * @property ParameterType $parameterType
 * @property Sensor $sensor
 * @property SensorParameterHandbookValue[] $sensorParameterHandbookValues
 * @property SensorParameterSensor[] $sensorParameterSensors
 * @property SensorParameterValue[] $sensorParameterValues
 * @property SensorParameterValueErrors[] $sensorParameterValueErrors
 */
class SensorParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sensor_parameter';
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
            [['sensor_id', 'parameter_id', 'parameter_type_id'], 'required'],
            [['sensor_id', 'parameter_id', 'parameter_type_id'], 'integer'],
            [['sensor_id', 'parameter_id', 'parameter_type_id'], 'unique', 'targetAttribute' => ['sensor_id', 'parameter_id', 'parameter_type_id']],
            [['parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => Parameter::className(), 'targetAttribute' => ['parameter_id' => 'id']],
            [['parameter_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ParameterType::className(), 'targetAttribute' => ['parameter_type_id' => 'id']],
            [['sensor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Sensor::className(), 'targetAttribute' => ['sensor_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sensor_id' => 'Sensor ID',
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalSensorParameters()
    {
        return $this->hasMany(NominalSensorParameter::className(), ['sensor_parameter_id' => 'id']);
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
    public function getSensor()
    {
        return $this->hasOne(Sensor::className(), ['id' => 'sensor_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensorParameterHandbookValues()
    {
        return $this->hasMany(SensorParameterHandbookValue::className(), ['sensor_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensorParameterSensors()
    {
        return $this->hasMany(SensorParameterSensor::className(), ['sensor_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensorParameterValues()
    {
        return $this->hasMany(SensorParameterValue::className(), ['sensor_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensorParameterValueErrors()
    {
        return $this->hasMany(SensorParameterValueErrors::className(), ['sensor_parameter_id' => 'id']);
    }
}
