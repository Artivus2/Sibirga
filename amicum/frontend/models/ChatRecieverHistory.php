<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "chat_reciever_history".
 *
 * @property int $id
 * @property int $chat_message_reciever_id Идентификатор получателя сообщения
 * @property int $status_id Идентификатор статуса
 * @property string $date_time Дата и время смены статуса сообщения
 *
 * @property ChatMessageReciever $chatMessageReciever
 */
class ChatRecieverHistory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chat_reciever_history';
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
            [['chat_message_reciever_id', 'status_id', 'date_time'], 'required'],
            [['chat_message_reciever_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['chat_message_reciever_id', 'status_id', 'date_time'], 'unique', 'targetAttribute' => ['chat_message_reciever_id', 'status_id', 'date_time']],
            [['chat_message_reciever_id'], 'exist', 'skipOnError' => true, 'targetClass' => ChatMessageReciever::className(), 'targetAttribute' => ['chat_message_reciever_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'chat_message_reciever_id' => 'Chat Message Reciever ID',
            'status_id' => 'Status ID',
            'date_time' => 'Date Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChatMessageReciever()
    {
        return $this->hasOne(ChatMessageReciever::className(), ['id' => 'chat_message_reciever_id']);
    }
}
