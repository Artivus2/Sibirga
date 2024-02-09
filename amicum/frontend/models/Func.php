<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "func".
 *
 * @property int $id
 * @property string $title
 * @property int $function_type_id
 * @property string|null $func_script_name
 *
 * @property ConjunctionFunction[] $conjunctionFunctions
 * @property EdgeFunction[] $edgeFunctions
 * @property EnergyMineFunction[] $energyMineFunctions
 * @property EquipmentFunction[] $equipmentFunctions
 * @property FunctionType $functionType
 * @property FunctionParameter[] $functionParameters
 * @property MineFunction[] $mineFunctions
 * @property OperationFunction[] $operationFunctions
 * @property OperationRegulationFact[] $operationRegulationFacts
 * @property PlaActivityFact[] $plaActivityFacts
 * @property PlaceFunction[] $placeFunctions
 * @property PlastFunction[] $plastFunctions
 * @property PpsMineFunction[] $ppsMineFunctions
 * @property SensorFunction[] $sensorFunctions
 * @property TypeObjectFunction[] $typeObjectFunctions
 * @property TypeObjectParameterFunction[] $typeObjectParameterFunctions
 * @property WorkerFunction[] $workerFunctions
 */
class Func extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'func';
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
            [['title', 'function_type_id'], 'required'],
            [['function_type_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['func_script_name'], 'string', 'max' => 45],
            [['function_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => FunctionType::className(), 'targetAttribute' => ['function_type_id' => 'id']],
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
            'function_type_id' => 'Function Type ID',
            'func_script_name' => 'Func Script Name',
        ];
    }

    /**
     * Gets query for [[ConjunctionFunctions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getConjunctionFunctions()
    {
        return $this->hasMany(ConjunctionFunction::className(), ['function_id' => 'id']);
    }

    /**
     * Gets query for [[EdgeFunctions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEdgeFunctions()
    {
        return $this->hasMany(EdgeFunction::className(), ['function_id' => 'id']);
    }

    /**
     * Gets query for [[EnergyMineFunctions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEnergyMineFunctions()
    {
        return $this->hasMany(EnergyMineFunction::className(), ['function_id' => 'id']);
    }

    /**
     * Gets query for [[EquipmentFunctions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEquipmentFunctions()
    {
        return $this->hasMany(EquipmentFunction::className(), ['function_id' => 'id']);
    }

    /**
     * Gets query for [[FunctionType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFunctionType()
    {
        return $this->hasOne(FunctionType::className(), ['id' => 'function_type_id']);
    }

    /**
     * Gets query for [[FunctionParameters]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFunctionParameters()
    {
        return $this->hasMany(FunctionParameter::className(), ['function_id' => 'id']);
    }

    /**
     * Gets query for [[MineFunctions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMineFunctions()
    {
        return $this->hasMany(MineFunction::className(), ['function_id' => 'id']);
    }

    /**
     * Gets query for [[OperationFunctions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperationFunctions()
    {
        return $this->hasMany(OperationFunction::className(), ['function_id' => 'id']);
    }

    /**
     * Gets query for [[OperationRegulationFacts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperationRegulationFacts()
    {
        return $this->hasMany(OperationRegulationFact::className(), ['function_id' => 'id']);
    }

    /**
     * Gets query for [[PlaActivityFacts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlaActivityFacts()
    {
        return $this->hasMany(PlaActivityFact::className(), ['function_id' => 'id']);
    }

    /**
     * Gets query for [[PlaceFunctions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceFunctions()
    {
        return $this->hasMany(PlaceFunction::className(), ['function_id' => 'id']);
    }

    /**
     * Gets query for [[PlastFunctions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlastFunctions()
    {
        return $this->hasMany(PlastFunction::className(), ['function_id' => 'id']);
    }

    /**
     * Gets query for [[PpsMineFunctions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPpsMineFunctions()
    {
        return $this->hasMany(PpsMineFunction::className(), ['function_id' => 'id']);
    }

    /**
     * Gets query for [[SensorFunctions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSensorFunctions()
    {
        return $this->hasMany(SensorFunction::className(), ['function_id' => 'id']);
    }

    /**
     * Gets query for [[TypeObjectFunctions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTypeObjectFunctions()
    {
        return $this->hasMany(TypeObjectFunction::className(), ['func_id' => 'id']);
    }

    /**
     * Gets query for [[TypeObjectParameterFunctions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTypeObjectParameterFunctions()
    {
        return $this->hasMany(TypeObjectParameterFunction::className(), ['function_id' => 'id']);
    }

    /**
     * Gets query for [[WorkerFunctions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerFunctions()
    {
        return $this->hasMany(WorkerFunction::className(), ['function_id' => 'id']);
    }
}
