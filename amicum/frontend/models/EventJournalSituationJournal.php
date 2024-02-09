<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "event_journal_situation_journal".
 *
 * @property int $id
 * @property int $situation_journal_id
 * @property int $event_journal_id
 *
 * @property EventJournal $eventJournal
 * @property SituationJournal $situationJournal
 */
class EventJournalSituationJournal extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event_journal_situation_journal';
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
            [['situation_journal_id', 'event_journal_id'], 'required'],
            [['situation_journal_id', 'event_journal_id'], 'integer'],
            [['event_journal_id'], 'exist', 'skipOnError' => true, 'targetClass' => EventJournal::className(), 'targetAttribute' => ['event_journal_id' => 'id']],
            [['situation_journal_id'], 'exist', 'skipOnError' => true, 'targetClass' => SituationJournal::className(), 'targetAttribute' => ['situation_journal_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'situation_journal_id' => 'Situation Journal ID',
            'event_journal_id' => 'Event Journal ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventJournal()
    {
        return $this->hasOne(EventJournal::className(), ['id' => 'event_journal_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituationJournal()
    {
        return $this->hasOne(SituationJournal::className(), ['id' => 'situation_journal_id']);
    }
}
