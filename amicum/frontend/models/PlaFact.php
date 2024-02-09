<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "pla_fact".
 *
 * @property int $id
 * @property int $pla_id
 * @property int $mine_situation_fact_id
 * @property string $date_time
 * @property int $status_id
 *
 * @property PlaActivityFact[] $plaActivityFacts
 * @property MineSituationFact $mineSituationFact
 * @property Pla $pla
 * @property Status $status
 */
class PlaFact extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pla_fact';
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
            [['pla_id', 'mine_situation_fact_id', 'date_time', 'status_id'], 'required'],
            [['pla_id', 'mine_situation_fact_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['mine_situation_fact_id'], 'exist', 'skipOnError' => true, 'targetClass' => MineSituationFact::className(), 'targetAttribute' => ['mine_situation_fact_id' => 'id']],
            [['pla_id'], 'exist', 'skipOnError' => true, 'targetClass' => Pla::className(), 'targetAttribute' => ['pla_id' => 'id']],
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
            'pla_id' => 'Pla ID',
            'mine_situation_fact_id' => 'Mine Situation Fact ID',
            'date_time' => 'Date Time',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaActivityFacts()
    {
        return $this->hasMany(PlaActivityFact::className(), ['pla_fact_id' => 'id']);
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
    public function getPla()
    {
        return $this->hasOne(Pla::className(), ['id' => 'pla_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
