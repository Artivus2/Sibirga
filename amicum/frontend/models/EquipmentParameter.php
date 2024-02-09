<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "equipment_parameter".
 *
 * @property int $id
 * @property int $equipment_id
 * @property int $parameter_id
 * @property int $parameter_type_id
 *
 * @property Equipment $equipment
 * @property Parameter $parameter
 * @property ParameterType $parameterType
 * @property EquipmentParameterHandbookValue[] $equipmentParameterHandbookValues
 * @property EquipmentParameterSensor[] $equipmentParameterSensors
 * @property EquipmentParameterValue[] $equipmentParameterValues
 * @property NominalEquipmentParameter[] $nominalEquipmentParameters
 */
class EquipmentParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'equipment_parameter';
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
            [['equipment_id', 'parameter_id', 'parameter_type_id'], 'required'],
            [['equipment_id', 'parameter_id', 'parameter_type_id'], 'integer'],
            [['equipment_id', 'parameter_id', 'parameter_type_id'], 'unique', 'targetAttribute' => ['equipment_id', 'parameter_id', 'parameter_type_id']],
            [['equipment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Equipment::className(), 'targetAttribute' => ['equipment_id' => 'id']],
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
            'equipment_id' => 'Equipment ID',
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEquipment()
    {
        return $this->hasOne(Equipment::className(), ['id' => 'equipment_id']);
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
    public function getEquipmentParameterHandbookValues()
    {
        return $this->hasMany(EquipmentParameterHandbookValue::className(), ['equipment_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEquipmentParameterSensors()
    {
        return $this->hasMany(EquipmentParameterSensor::className(), ['equipment_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEquipmentParameterValues()
    {
        return $this->hasMany(EquipmentParameterValue::className(), ['equipment_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalEquipmentParameters()
    {
        return $this->hasMany(NominalEquipmentParameter::className(), ['equipment_parameter_id' => 'id']);
    }
}
