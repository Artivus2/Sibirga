<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "pps_mine_parameter".
 *
 * @property int $id
 * @property int $pps_mine_id
 * @property int $parameter_id
 * @property int $parameter_type_id
 *
 * @property NominalPpsMineParameter[] $nominalPpsMineParameters
 * @property Parameter $parameter
 * @property ParameterType $parameterType
 * @property PpsMine $ppsMine
 * @property PpsMineParameterHandbookValue[] $ppsMineParameterHandbookValues
 * @property PpsMineParameterSensor[] $ppsMineParameterSensors
 * @property PpsMineParameterValue[] $ppsMineParameterValues
 */
class PpsMineParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pps_mine_parameter';
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
            [['pps_mine_id', 'parameter_id', 'parameter_type_id'], 'required'],
            [['pps_mine_id', 'parameter_id', 'parameter_type_id'], 'integer'],
            [['parameter_type_id', 'parameter_id', 'pps_mine_id'], 'unique', 'targetAttribute' => ['parameter_type_id', 'parameter_id', 'pps_mine_id']],
            [['parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => Parameter::className(), 'targetAttribute' => ['parameter_id' => 'id']],
            [['parameter_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ParameterType::className(), 'targetAttribute' => ['parameter_type_id' => 'id']],
            [['pps_mine_id'], 'exist', 'skipOnError' => true, 'targetClass' => PpsMine::className(), 'targetAttribute' => ['pps_mine_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pps_mine_id' => 'Pps Mine ID',
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalPpsMineParameters()
    {
        return $this->hasMany(NominalPpsMineParameter::className(), ['pps_mine_parameter_id' => 'id']);
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
    public function getPpsMine()
    {
        return $this->hasOne(PpsMine::className(), ['id' => 'pps_mine_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPpsMineParameterHandbookValues()
    {
        return $this->hasMany(PpsMineParameterHandbookValue::className(), ['pps_mine_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPpsMineParameterSensors()
    {
        return $this->hasMany(PpsMineParameterSensor::className(), ['pps_mine_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPpsMineParameterValues()
    {
        return $this->hasMany(PpsMineParameterValue::className(), ['pps_mine_parameter_id' => 'id']);
    }
}
