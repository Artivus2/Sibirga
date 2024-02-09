<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "event_situation_fact".
 *
 * @property int $id
 * @property int $situation_fact_id
 * @property int $event_fact_id
 *
 * @property EventFact $eventFact
 * @property SituationFact $situationFact
 */
class EventSituationFact extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event_situation_fact';
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
            [['situation_fact_id', 'event_fact_id'], 'required'],
            [['situation_fact_id', 'event_fact_id'], 'integer'],
            [['event_fact_id'], 'exist', 'skipOnError' => true, 'targetClass' => EventFact::className(), 'targetAttribute' => ['event_fact_id' => 'id']],
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
            'situation_fact_id' => 'Situation Fact ID',
            'event_fact_id' => 'Event Fact ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventFact()
    {
        return $this->hasOne(EventFact::className(), ['id' => 'event_fact_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituationFact()
    {
        return $this->hasOne(SituationFact::className(), ['id' => 'situation_fact_id']);
    }
}
