<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "regulation_fact".
 *
 * @property int $id
 * @property int $regulation_id
 * @property int $situation_fact_id
 * @property string $date_time
 * @property int $status_id
 *
 * @property OperationRegulationFact[] $operationRegulationFacts
 * @property Regulation $regulation
 * @property SituationFact $situationFact
 * @property Status $status
 */
class RegulationFact extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'regulation_fact';
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
            [['regulation_id', 'situation_fact_id', 'date_time', 'status_id'], 'required'],
            [['regulation_id', 'situation_fact_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['regulation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Regulation::className(), 'targetAttribute' => ['regulation_id' => 'id']],
            [['situation_fact_id'], 'exist', 'skipOnError' => true, 'targetClass' => SituationFact::className(), 'targetAttribute' => ['situation_fact_id' => 'id']],
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
            'regulation_id' => 'Regulation ID',
            'situation_fact_id' => 'Situation Fact ID',
            'date_time' => 'Date Time',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationRegulationFacts()
    {
        return $this->hasMany(OperationRegulationFact::className(), ['regulation_fact_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRegulation()
    {
        return $this->hasOne(Regulation::className(), ['id' => 'regulation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituationFact()
    {
        return $this->hasOne(SituationFact::className(), ['id' => 'situation_fact_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
