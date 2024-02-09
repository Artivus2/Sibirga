<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "place_parameter".
 *
 * @property int $id
 * @property int $place_id
 * @property int $parameter_id
 * @property int $parameter_type_id
 *
 * @property NominalPlaceParameter[] $nominalPlaceParameters
 * @property Parameter $parameter
 * @property ParameterType $parameterType
 * @property Place $place
 * @property PlaceParameterHandbookValue[] $placeParameterHandbookValues
 * @property PlaceParameterSensor[] $placeParameterSensors
 * @property PlaceParameterValue[] $placeParameterValues
 */
class PlaceParameterSync extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'place_parameter';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_target');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['place_id', 'parameter_id', 'parameter_type_id'], 'required'],
            [['place_id', 'parameter_id', 'parameter_type_id'], 'integer'],
            [['parameter_type_id', 'parameter_id', 'place_id'], 'unique', 'targetAttribute' => ['parameter_type_id', 'parameter_id', 'place_id']],
            [['parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => Parameter::className(), 'targetAttribute' => ['parameter_id' => 'id']],
            [['parameter_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ParameterType::className(), 'targetAttribute' => ['parameter_type_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'place_id' => 'Place ID',
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalPlaceParameters()
    {
        return $this->hasMany(NominalPlaceParameter::className(), ['place_parameter_id' => 'id']);
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
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceParameterHandbookValues()
    {
        return $this->hasMany(PlaceParameterHandbookValue::className(), ['place_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceParameterSensors()
    {
        return $this->hasMany(PlaceParameterSensor::className(), ['place_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceParameterValues()
    {
        return $this->hasMany(PlaceParameterValue::className(), ['place_parameter_id' => 'id']);
    }
}
