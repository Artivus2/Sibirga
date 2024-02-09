<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "event_journal_correct_measure".
 *
 * @property int $id ключ журнала ответственных за событие
 * @property int $event_journal_id
 * @property int $operation_id
 *
 * @property EventJournal $eventJournal
 * @property Operation $operation
 */
class EventJournalCorrectMeasure extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event_journal_correct_measure';
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
            [['event_journal_id', 'operation_id'], 'required'],
            [['event_journal_id', 'operation_id'], 'integer'],
            [['event_journal_id', 'operation_id'], 'unique', 'targetAttribute' => ['event_journal_id', 'operation_id']],
            [['event_journal_id'], 'exist', 'skipOnError' => true, 'targetClass' => EventJournal::className(), 'targetAttribute' => ['event_journal_id' => 'id']],
            [['operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['operation_id' => 'id']],
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
            'operation_id' => 'Operation ID',
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
    public function getOperation()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_id']);
    }
}
