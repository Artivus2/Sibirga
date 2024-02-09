<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "parameter".
 *
 * @property int $id
 * @property string $title
 * @property int $unit_id
 * @property int $kind_parameter_id
 *
 * @property ConjunctionParameter[] $conjunctionParameters
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
 * @property KindParameter $kindParameter
 * @property Unit $unit
 * @property PlaceParameter[] $placeParameters
 * @property PlastParameter[] $plastParameters
 * @property PpsMineParameter[] $ppsMineParameters
 * @property SensorParameter[] $sensorParameters
 * @property SituationFactParameter[] $situationFactParameters
 * @property TypeObjectParameter[] $typeObjectParameters
 */
class Parameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parameter';
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
            [['title', 'unit_id', 'kind_parameter_id'], 'required'],
            [['unit_id', 'kind_parameter_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['title'], 'unique'],
            [['kind_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindParameter::className(), 'targetAttribute' => ['kind_parameter_id' => 'id']],
            [['unit_id'], 'exist', 'skipOnError' => true, 'targetClass' => Unit::className(), 'targetAttribute' => ['unit_id' => 'id']],
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
            'unit_id' => 'Unit ID',
            'kind_parameter_id' => 'Kind Parameter ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConjunctionParameters()
    {
        return $this->hasMany(ConjunctionParameter::className(), ['parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEdgeParameters()
    {
        return $this->hasMany(EdgeParameter::className(), ['parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnergyMineParameters()
    {
        return $this->hasMany(EnergyMineParameter::className(), ['parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEquipmentParameters()
    {
        return $this->hasMany(EquipmentParameter::className(), ['parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFaceParameters()
    {
        return $this->hasMany(FaceParameter::className(), ['parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFunctionParameters()
    {
        return $this->hasMany(FunctionParameter::className(), ['parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineParameters()
    {
        return $this->hasMany(MineParameter::className(), ['parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineSituationFactParameters()
    {
        return $this->hasMany(MineSituationFactParameter::className(), ['parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationParameters()
    {
        return $this->hasMany(OperationParameters::className(), ['parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationReguationFactParameters()
    {
        return $this->hasMany(OperationReguationFactParameter::className(), ['parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationRegulationParameters()
    {
        return $this->hasMany(OperationRegulationParameter::className(), ['parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getKindParameter()
    {
        return $this->hasOne(KindParameter::className(), ['id' => 'kind_parameter_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUnit()
    {
        return $this->hasOne(Unit::className(), ['id' => 'unit_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceParameters()
    {
        return $this->hasMany(PlaceParameter::className(), ['parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlastParameters()
    {
        return $this->hasMany(PlastParameter::className(), ['parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPpsMineParameters()
    {
        return $this->hasMany(PpsMineParameter::className(), ['parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensorParameters()
    {
        return $this->hasMany(SensorParameter::className(), ['parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituationFactParameters()
    {
        return $this->hasMany(SituationFactParameter::className(), ['parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTypeObjectParameters()
    {
        return $this->hasMany(TypeObjectParameter::className(), ['parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerParameters()
    {
        return $this->hasMany(WorkerParameter::className(), ['parameter_id' => 'id']);
    }
}
