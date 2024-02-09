<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "mine_parameter".
 *
 * @property int $id
 * @property int $mine_id
 * @property int $parameter_id
 * @property int $parameter_type_id
 *
 * @property Mine $mine
 * @property Parameter $parameter
 * @property ParameterType $parameterType
 * @property MineParameterHandbookValue[] $mineParameterHandbookValues
 * @property MineParameterSensor[] $mineParameterSensors
 * @property MineParameterValue[] $mineParameterValues
 */
class MineParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mine_parameter';
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
            [['mine_id', 'parameter_id', 'parameter_type_id'], 'required'],
            [['mine_id', 'parameter_id', 'parameter_type_id'], 'integer'],
            [['mine_id', 'parameter_id', 'parameter_type_id'], 'unique', 'targetAttribute' => ['mine_id', 'parameter_id', 'parameter_type_id']],
            [['mine_id'], 'exist', 'skipOnError' => true, 'targetClass' => Mine::className(), 'targetAttribute' => ['mine_id' => 'id']],
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
            'mine_id' => 'Mine ID',
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMine()
    {
        return $this->hasOne(Mine::className(), ['id' => 'mine_id']);
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
    public function getMineParameterHandbookValues()
    {
        return $this->hasMany(MineParameterHandbookValue::className(), ['mine_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineParameterSensors()
    {
        return $this->hasMany(MineParameterSensor::className(), ['mine_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineParameterValues()
    {
        return $this->hasMany(MineParameterValue::className(), ['mine_parameter_id' => 'id']);
    }
}
