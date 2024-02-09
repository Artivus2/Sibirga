<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "type_object_parameter".
 *
 * @property int $id
 * @property int $parameter_id
 * @property int $parameter_type_id
 * @property int $object_id
 *
 * @property NominalTypeObjectParameter[] $nominalTypeObjectParameters
 * @property Object $object
 * @property Parameter $parameter
 * @property ParameterType $parameterType
 * @property TypeObjectParameterFunction[] $typeObjectParameterFunctions
 * @property TypeObjectParameterSensor[] $typeObjectParameterSensors
 * @property TypeObjectParameterValue[] $typeObjectParameterValues
 */
class TypeObjectParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'type_object_parameter';
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
            [['parameter_id', 'parameter_type_id', 'object_id'], 'required'],
            [['parameter_id', 'parameter_type_id', 'object_id'], 'integer'],
            [['parameter_id', 'parameter_type_id', 'object_id'], 'unique', 'targetAttribute' => ['parameter_id', 'parameter_type_id', 'object_id']],
            [['object_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypicalObject::className(), 'targetAttribute' => ['object_id' => 'id']],
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
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
            'object_id' => 'Object ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalTypeObjectParameters()
    {
        return $this->hasMany(NominalTypeObjectParameter::className(), ['type_object_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getObject()
    {
        return $this->hasOne(TypicalObject::className(), ['id' => 'object_id']);
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
    public function getTypeObjectParameterFunctions()
    {
        return $this->hasMany(TypeObjectParameterFunction::className(), ['type_object_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTypeObjectParameterHandbookValues()
    {
        return $this->hasMany(TypeObjectParameterHandbookValue::className(), ['type_object_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTypeObjectParameterSensors()
    {
        return $this->hasMany(TypeObjectParameterSensor::className(), ['type_object_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTypeObjectParameterValues()
    {
        return $this->hasMany(TypeObjectParameterValue::className(), ['type_object_parameter_id' => 'id']);
    }
}
