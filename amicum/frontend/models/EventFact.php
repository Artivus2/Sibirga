<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "event_fact".
 *
 * @property int $id
 * @property int $event_id
 * @property string $date_time_start
 * @property string $date_time_end
 * @property int $main_id
 *
 * @property Event $event
 * @property Main $main
 * @property EventSituationFact[] $eventSituationFacts
 */
class EventFact extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event_fact';
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
            [['event_id', 'date_time_start', 'date_time_end', 'main_id'], 'required'],
            [['event_id', 'main_id'], 'integer'],
            [['date_time_start', 'date_time_end'], 'safe'],
            [['event_id'], 'exist', 'skipOnError' => true, 'targetClass' => Event::className(), 'targetAttribute' => ['event_id' => 'id']],
            [['main_id'], 'exist', 'skipOnError' => true, 'targetClass' => Main::className(), 'targetAttribute' => ['main_id' => 'id']],
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
            'date_time_start' => 'Date Time Start',
            'date_time_end' => 'Date Time End',
            'main_id' => 'Main ID',
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
    public function getMain()
    {
        return $this->hasOne(Main::className(), ['id' => 'main_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventSituationFacts()
    {
        return $this->hasMany(EventSituationFact::className(), ['event_fact_id' => 'id']);
    }
}
