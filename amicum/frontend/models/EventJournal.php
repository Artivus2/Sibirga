<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "event_journal".
 *
 * @property int $id
 * @property int $event_id Само событие
 * @property int $main_id ключ сохраняемого объекта у которого случилось событие sensor_id worker_id и т.д.
 * @property int|null $edge_id выработка в которой случилось событие
 * @property string|null $value Значение параметра которое вызвало событие
 * @property string|null $date_time Дата и время добавления параметра
 * @property string|null $xyz координата события
 * @property int|null $status_id статус значения которое вызвало событие (нормальное, аварийное)
 * @property int|null $parameter_id параметр который вызвал событие 99 значение газа метана или 98 значение газа СО
 * @property int|null $object_id типовой объект объекта вызвавшего событие
 * @property int|null $mine_id ключ шахты где назодится объект
 * @property string|null $object_title наименование объекта вызвавшего событие
 * @property string|null $object_table таблица в которой лежит объект вызвавший событие
 * @property int|null $event_status_id Статус события (устранено, в процессе)
 * @property int|null group_alarm_id Группа оповещения
 *
 * @property Event $event
 * @property EventJournalCorrectMeasure[] $eventJournalCorrectMeasures
 * @property Operation[] $operations
 * @property EventJournalGilty[] $eventJournalGilties
 * @property Worker[] $workers
 * @property EventJournalSituationJournal[] $eventJournalSituationJournals
 * @property EventJournalStatus[] $eventJournalStatuses
 * @property EventStatus[] $eventStatuses
 */
class EventJournal extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event_journal';
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
            [['event_id', 'main_id'], 'required'],
            [['event_id', 'main_id', 'edge_id', 'status_id', 'parameter_id', 'object_id', 'mine_id', 'event_status_id', 'group_alarm_id'], 'integer'],
            [['date_time'], 'safe'],
            [['value', 'object_table'], 'string', 'max' => 45],
            [['xyz'], 'string', 'max' => 55],
            [['object_title'], 'string', 'max' => 61],
            [['event_id'], 'exist', 'skipOnError' => true, 'targetClass' => Event::className(), 'targetAttribute' => ['event_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'event_id' => 'Event ID',
            'main_id' => 'Main ID',
            'edge_id' => 'Edge ID',
            'value' => 'Value',
            'date_time' => 'Date Time',
            'xyz' => 'Xyz',
            'status_id' => 'Status ID',
            'parameter_id' => 'Parameter ID',
            'object_id' => 'Object ID',
            'mine_id' => 'Mine ID',
            'object_title' => 'Object Title',
            'object_table' => 'Object Table',
            'event_status_id' => 'Event Status ID',
            'group_alarm_id' => 'Group Alarm ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEvent()
    {
        return $this->hasOne(Event::className(), ['id' => 'event_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventJournalCorrectMeasures()
    {
        return $this->hasMany(EventJournalCorrectMeasure::className(), ['event_journal_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperations()
    {
        return $this->hasMany(Operation::className(), ['id' => 'operation_id'])->viaTable('event_journal_correct_measure', ['event_journal_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventJournalGilties()
    {
        return $this->hasMany(EventJournalGilty::className(), ['event_journal_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkers()
    {
        return $this->hasMany(Worker::className(), ['id' => 'worker_id'])->viaTable('event_journal_gilty', ['event_journal_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventJournalSituationJournals()
    {
        return $this->hasMany(EventJournalSituationJournal::className(), ['event_journal_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventJournalStatuses()
    {
        return $this->hasMany(EventJournalStatus::className(), ['event_journal_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventStatuses()
    {
        return $this->hasMany(EventStatus::className(), ['event_journal_id' => 'id']);
    }

    // РУЧНЫЕ МЕТОДЫ
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventEdge()
    {
        return $this->hasOne(Edge::className(), ['id' => 'edge_id'])->alias('eventEdge');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParameter()
    {
        return $this->hasOne(Parameter::className(), ['id' => 'parameter_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensor()
    {
        return $this->hasOne(Sensor::className(), ['id' => 'main_id']);
    }
}
