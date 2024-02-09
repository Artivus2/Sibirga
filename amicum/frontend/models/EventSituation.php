<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "event_situation".
 *
 * @property int $id
 * @property int $event_id
 * @property int $situation_id
 *
 * @property Event $event
 * @property Situation $situation
 */
class EventSituation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event_situation';
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
            [['event_id', 'situation_id'], 'required'],
            [['event_id', 'situation_id'], 'integer'],
            [['event_id'], 'exist', 'skipOnError' => true, 'targetClass' => Event::className(), 'targetAttribute' => ['event_id' => 'id']],
            [['situation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Situation::className(), 'targetAttribute' => ['situation_id' => 'id']],
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
            'situation_id' => 'Situation ID',
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
    public function getSituation()
    {
        return $this->hasOne(Situation::className(), ['id' => 'situation_id']);
    }
}
