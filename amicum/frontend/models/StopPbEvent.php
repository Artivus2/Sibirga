<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "stop_pb_event".
 *
 * @property int $id
 * @property int $stop_pb_id ключ простоя
 * @property int $event_id ключ события В данном случае является причиной простоя Обоснование что любое событие и есть причина для чего либо
 *
 * @property Event $event
 * @property StopPb $stopPb
 */
class StopPbEvent extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'stop_pb_event';
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
            [['stop_pb_id', 'event_id'], 'required'],
            [['stop_pb_id', 'event_id'], 'integer'],
            [['event_id'], 'exist', 'skipOnError' => true, 'targetClass' => Event::className(), 'targetAttribute' => ['event_id' => 'id']],
            [['stop_pb_id'], 'exist', 'skipOnError' => true, 'targetClass' => StopPb::className(), 'targetAttribute' => ['stop_pb_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'stop_pb_id' => 'Stop Pb ID',
            'event_id' => 'Event ID',
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
    public function getStopPb()
    {
        return $this->hasOne(StopPb::className(), ['id' => 'stop_pb_id']);
    }
}
