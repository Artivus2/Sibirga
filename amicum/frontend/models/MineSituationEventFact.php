<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "mine_situation_event_fact".
 *
 * @property int $id
 * @property int $mine_situation_fact_id
 * @property int $situation_fact_id
 *
 * @property MineSituationFact $mineSituationFact
 * @property SituationFact $situationFact
 */
class MineSituationEventFact extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mine_situation_event_fact';
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
            [['mine_situation_fact_id', 'situation_fact_id'], 'required'],
            [['mine_situation_fact_id', 'situation_fact_id'], 'integer'],
            [['mine_situation_fact_id'], 'exist', 'skipOnError' => true, 'targetClass' => MineSituationFact::className(), 'targetAttribute' => ['mine_situation_fact_id' => 'id']],
            [['situation_fact_id'], 'exist', 'skipOnError' => true, 'targetClass' => SituationFact::className(), 'targetAttribute' => ['situation_fact_id' => 'id']],
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
            'situation_fact_id' => 'Situation Fact ID',
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
    public function getSituationFact()
    {
        return $this->hasOne(SituationFact::className(), ['id' => 'situation_fact_id']);
    }
}
