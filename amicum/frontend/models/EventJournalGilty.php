<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "event_journal_gilty".
 *
 * @property int $id ключ журнала ответственных за событие
 * @property int $event_journal_id
 * @property int $worker_id
 *
 * @property EventJournal $eventJournal
 * @property Worker $worker
 */
class EventJournalGilty extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event_journal_gilty';
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
            [['event_journal_id', 'worker_id'], 'required'],
            [['event_journal_id', 'worker_id'], 'integer'],
            [['event_journal_id', 'worker_id'], 'unique', 'targetAttribute' => ['event_journal_id', 'worker_id']],
            [['event_journal_id'], 'exist', 'skipOnError' => true, 'targetClass' => EventJournal::className(), 'targetAttribute' => ['event_journal_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'event_journal_id' => 'Event Journal ID',
            'worker_id' => 'Worker ID',
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
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
