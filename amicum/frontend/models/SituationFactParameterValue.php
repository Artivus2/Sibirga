<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "situation_fact_parameter_value".
 *
 * @property int $id
 * @property int $situation_fact_parameter_id
 * @property int $operation_regulation_fact_id
 * @property string $date_time DATETIME(6)
 * @property string $value
 * @property int $status_id
 *
 * @property OperationRegulationFact $operationRegulationFact
 * @property SituationFactParameter $situationFactParameter
 * @property Status $status
 */
class SituationFactParameterValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'situation_fact_parameter_value';
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
            [['situation_fact_parameter_id', 'operation_regulation_fact_id', 'date_time', 'value', 'status_id'], 'required'],
            [['situation_fact_parameter_id', 'operation_regulation_fact_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['value'], 'string', 'max' => 255],
            [['operation_regulation_fact_id'], 'exist', 'skipOnError' => true, 'targetClass' => OperationRegulationFact::className(), 'targetAttribute' => ['operation_regulation_fact_id' => 'id']],
            [['situation_fact_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => SituationFactParameter::className(), 'targetAttribute' => ['situation_fact_parameter_id' => 'id']],
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
            'situation_fact_parameter_id' => 'Situation Fact Parameter ID',
            'operation_regulation_fact_id' => 'Operation Regulation Fact ID',
            'date_time' => 'DATETIME(6)',
            'value' => 'Value',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationRegulationFact()
    {
        return $this->hasOne(OperationRegulationFact::className(), ['id' => 'operation_regulation_fact_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituationFactParameter()
    {
        return $this->hasOne(SituationFactParameter::className(), ['id' => 'situation_fact_parameter_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
