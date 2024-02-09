<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "event_status".
 *
 * @property int $id ключ истории
 * @property int $event_journal_id ключ журнала событий
 * @property int $status_id ключ события
 * @property string $datetime дата и всремя изменения статуса события
 * @property int|null $kind_reason_id причина события
 *
 * @property EventJournal $eventJournal
 * @property KindReason $kindReason
 * @property Status $status
 */
class EventStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event_status';
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
            [['event_journal_id', 'status_id', 'datetime'], 'required'],
            [['event_journal_id', 'status_id', 'kind_reason_id'], 'integer'],
            [['datetime'], 'safe'],
            [['event_journal_id'], 'exist', 'skipOnError' => true, 'targetClass' => EventJournal::className(), 'targetAttribute' => ['event_journal_id' => 'id']],
            [['kind_reason_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindReason::className(), 'targetAttribute' => ['kind_reason_id' => 'id']],
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
            'event_journal_id' => 'Event Journal ID',
            'status_id' => 'Status ID',
            'datetime' => 'Datetime',
            'kind_reason_id' => 'Kind Reason ID',
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
    public function getKindReason()
    {
        return $this->hasOne(KindReason::className(), ['id' => 'kind_reason_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
