<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "mine_situation_fact".
 *
 * @property int $id
 * @property int $mine_situation_id
 * @property string $date_time
 * @property int $main_id
 * @property int $danger_level_id
 * @property int $status_id
 *
 * @property MineSituationEventFact[] $mineSituationEventFacts
 * @property DangerLevel $dangerLevel
 * @property Main $main
 * @property MineSituation $mineSituation
 * @property Status $status
 * @property MineSituationFactParameter[] $mineSituationFactParameters
 * @property PlaFact[] $plaFacts
 */
class MineSituationFact extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mine_situation_fact';
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
            [['id', 'mine_situation_id', 'date_time', 'main_id', 'danger_level_id', 'status_id'], 'required'],
            [['id', 'mine_situation_id', 'main_id', 'danger_level_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['id'], 'unique'],
            [['danger_level_id'], 'exist', 'skipOnError' => true, 'targetClass' => DangerLevel::className(), 'targetAttribute' => ['danger_level_id' => 'id']],
            [['main_id'], 'exist', 'skipOnError' => true, 'targetClass' => Main::className(), 'targetAttribute' => ['main_id' => 'id']],
            [['mine_situation_id'], 'exist', 'skipOnError' => true, 'targetClass' => MineSituation::className(), 'targetAttribute' => ['mine_situation_id' => 'id']],
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
            'mine_situation_id' => 'Mine Situation ID',
            'date_time' => 'Date Time',
            'main_id' => 'Main ID',
            'danger_level_id' => 'Danger Level ID',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineSituationEventFacts()
    {
        return $this->hasMany(MineSituationEventFact::className(), ['mine_situation_fact_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDangerLevel()
    {
        return $this->hasOne(DangerLevel::className(), ['id' => 'danger_level_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMain()
    {
        return $this->hasOne(Main::className(), ['id' => 'main_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineSituation()
    {
        return $this->hasOne(MineSituation::className(), ['id' => 'mine_situation_id']);
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
    public function getMineSituationFactParameters()
    {
        return $this->hasMany(MineSituationFactParameter::className(), ['mine_situation_fact_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaFacts()
    {
        return $this->hasMany(PlaFact::className(), ['mine_situation_fact_id' => 'id']);
    }
}
