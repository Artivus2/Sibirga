<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "parameter_type".
 *
 * @property int $id
 * @property string $title
 *
 * @property ConjunctionParameter[] $conjunctionParameters
 * @property DepartmentParameter[] $departmentParameters
 * @property DepartmentParameterSummary[] $departmentParameterSummaries
 * @property EdgeParameter[] $edgeParameters
 * @property EnergyMineParameter[] $energyMineParameters
 * @property EquipmentParameter[] $equipmentParameters
 * @property FaceParameter[] $faceParameters
 * @property FunctionParameter[] $functionParameters
 * @property MineParameter[] $mineParameters
 * @property MineSituationFactParameter[] $mineSituationFactParameters
 * @property OperationParameters[] $operationParameters
 * @property OperationReguationFactParameter[] $operationReguationFactParameters
 * @property OperationRegulationParameter[] $operationRegulationParameters
 * @property PlaceParameter[] $placeParameters
 * @property PlastParameter[] $plastParameters
 * @property PpsMineParameter[] $ppsMineParameters
 * @property SensorParameter[] $sensorParameters
 * @property SituationFactParameter[] $situationFactParameters
 * @property TypeObjectParameter[] $typeObjectParameters
 */
class ParameterType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parameter_type';
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
            [['title'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['title'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConjunctionParameters()
    {
        return $this->hasMany(ConjunctionParameter::className(), ['parameter_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDepartmentParameters()
    {
        return $this->hasMany(DepartmentParameter::className(), ['parameter_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDepartmentParameterSummaries()
    {
        return $this->hasMany(DepartmentParameterSummary::className(), ['parameter_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEdgeParameters()
    {
        return $this->hasMany(EdgeParameter::className(), ['parameter_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnergyMineParameters()
    {
        return $this->hasMany(EnergyMineParameter::className(), ['parameter_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEquipmentParameters()
    {
        return $this->hasMany(EquipmentParameter::className(), ['parameter_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFaceParameters()
    {
        return $this->hasMany(FaceParameter::className(), ['parameter_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFunctionParameters()
    {
        return $this->hasMany(FunctionParameter::className(), ['parameter_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineParameters()
    {
        return $this->hasMany(MineParameter::className(), ['parameter_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineSituationFactParameters()
    {
        return $this->hasMany(MineSituationFactParameter::className(), ['parameter_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationParameters()
    {
        return $this->hasMany(OperationParameters::className(), ['parameter_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationReguationFactParameters()
    {
        return $this->hasMany(OperationReguationFactParameter::className(), ['parameter_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationRegulationParameters()
    {
        return $this->hasMany(OperationRegulationParameter::className(), ['parameter_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceParameters()
    {
        return $this->hasMany(PlaceParameter::className(), ['parameter_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlastParameters()
    {
        return $this->hasMany(PlastParameter::className(), ['parameter_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPpsMineParameters()
    {
        return $this->hasMany(PpsMineParameter::className(), ['parameter_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensorParameters()
    {
        return $this->hasMany(SensorParameter::className(), ['parameter_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituationFactParameters()
    {
        return $this->hasMany(SituationFactParameter::className(), ['parameter_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTypeObjectParameters()
    {
        return $this->hasMany(TypeObjectParameter::className(), ['parameter_type_id' => 'id']);
    }
}
