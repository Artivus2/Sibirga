<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "mine_situation_fact_parameter".
 *
 * @property int $id
 * @property int $mine_situation_fact_id
 * @property int $parameter_id
 * @property int $parameter_type_id
 * @property int $status_id
 *
 * @property MineSituationFact $mineSituationFact
 * @property Parameter $parameter
 * @property ParameterType $parameterType
 * @property Status $status
 * @property MineSituationFactParameterValue[] $mineSituationFactParameterValues
 */
class MineSituationFactParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mine_situation_fact_parameter';
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
            [['id', 'mine_situation_fact_id', 'parameter_id', 'parameter_type_id', 'status_id'], 'required'],
            [['id', 'mine_situation_fact_id', 'parameter_id', 'parameter_type_id', 'status_id'], 'integer'],
            [['parameter_type_id', 'parameter_id', 'mine_situation_fact_id', 'status_id'], 'unique', 'targetAttribute' => ['parameter_type_id', 'parameter_id', 'mine_situation_fact_id', 'status_id']],
            [['id'], 'unique'],
            [['mine_situation_fact_id'], 'exist', 'skipOnError' => true, 'targetClass' => MineSituationFact::className(), 'targetAttribute' => ['mine_situation_fact_id' => 'id']],
            [['parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => Parameter::className(), 'targetAttribute' => ['parameter_id' => 'id']],
            [['parameter_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ParameterType::className(), 'targetAttribute' => ['parameter_type_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mine_situation_fact_id' => 'Mine Situation Fact ID',
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineSituationFact()
    {
        return $this->hasOne(MineSituationFact::className(), ['id' => 'mine_situation_fact_id']);
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
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineSituationFactParameterValues()
    {
        return $this->hasMany(MineSituationFactParameterValue::className(), ['mine_situation_fact_parameter_id' => 'id']);
    }
}
