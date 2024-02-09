<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "energy_mine_parameter".
 *
 * @property int $id
 * @property int $energy_mine_id
 * @property int $parameter_id
 * @property int $parameter_type_id
 *
 * @property EnergyMine $energyMine
 * @property Parameter $parameter
 * @property ParameterType $parameterType
 * @property EnergyMineParameterHandbookValue[] $energyMineParameterHandbookValues
 * @property EnergyMineParameterSensor[] $energyMineParameterSensors
 * @property EnergyMineParameterValue[] $energyMineParameterValues
 * @property NominalEnergyMineParameter[] $nominalEnergyMineParameters
 */
class EnergyMineParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'energy_mine_parameter';
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
            [['energy_mine_id', 'parameter_id', 'parameter_type_id'], 'required'],
            [['energy_mine_id', 'parameter_id', 'parameter_type_id'], 'integer'],
            [['energy_mine_id'], 'exist', 'skipOnError' => true, 'targetClass' => EnergyMine::className(), 'targetAttribute' => ['energy_mine_id' => 'id']],
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
            'energy_mine_id' => 'Energy Mine ID',
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnergyMine()
    {
        return $this->hasOne(EnergyMine::className(), ['id' => 'energy_mine_id']);
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
    public function getEnergyMineParameterHandbookValues()
    {
        return $this->hasMany(EnergyMineParameterHandbookValue::className(), ['energy_mine_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnergyMineParameterSensors()
    {
        return $this->hasMany(EnergyMineParameterSensor::className(), ['energy_mine_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnergyMineParameterValues()
    {
        return $this->hasMany(EnergyMineParameterValue::className(), ['energy_mine_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalEnergyMineParameters()
    {
        return $this->hasMany(NominalEnergyMineParameter::className(), ['energy_mine_parameter_id' => 'id']);
    }
}
