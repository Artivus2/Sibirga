<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "event_journal_status".
 *
 * @property int $id ключ журнала оператора АГК/диспетчера (хранит отметки о принятых мерах и кем) 
 * @property int $event_journal_id ключ журнала событий
 * @property string $date_time дата создания отметки в журнале
 * @property int $worker_id ключ работника создавшего отметку
 * @property int|null $kind_reason_id ключ причины отказа/события
 * @property string|null $description ручное описание произошедшего события/отказа
 * @property string|null $check_done_date_time время установки отметки об устранении отказа/события
 * @property string|null $check_ignore_date_time время установки отметки об игнорировании отказа/события
 * @property int|null $check_done_status статус устранения/выполнения
 * @property int|null $check_ignore_status статус игнора 0 или 1
 *
 * @property EventJournal $eventJournal
 * @property KindReason $kindReason
 * @property Worker $worker
 */
class EventJournalStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event_journal_status';
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
            [['event_journal_id', 'date_time', 'worker_id'], 'required'],
            [['event_journal_id', 'worker_id', 'kind_reason_id', 'check_done_status', 'check_ignore_status'], 'integer'],
            [['date_time', 'check_done_date_time', 'check_ignore_date_time'], 'safe'],
            [['description'], 'string', 'max' => 500],
            [['event_journal_id', 'date_time', 'worker_id'], 'unique', 'targetAttribute' => ['event_journal_id', 'date_time', 'worker_id']],
            [['event_journal_id'], 'exist', 'skipOnError' => true, 'targetClass' => EventJournal::className(), 'targetAttribute' => ['event_journal_id' => 'id']],
            [['kind_reason_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindReason::className(), 'targetAttribute' => ['kind_reason_id' => 'id']],
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
            'date_time' => 'Date Time',
            'worker_id' => 'Worker ID',
            'kind_reason_id' => 'Kind Reason ID',
            'description' => 'Description',
            'check_done_date_time' => 'Check Done Date Time',
            'check_ignore_date_time' => 'Check Ignore Date Time',
            'check_done_status' => 'Check Done Status',
            'check_ignore_status' => 'Check Ignore Status',
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
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
