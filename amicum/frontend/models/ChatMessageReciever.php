<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "chat_message_reciever".
 *
 * @property int $id
 * @property int $chat_message_id
 * @property int $worker_id Идентификатор работника
 * @property int $status_id_last Идентификатор последнего статуса сообщения
 * @property int $chat_message_chat_room_id
 *
 * @property ChatMessage $chatMessage
 * @property ChatRecieverHistory[] $chatRecieverHistories
 */
class ChatMessageReciever extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chat_message_reciever';
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
            [['chat_message_id', 'worker_id', 'status_id_last', 'chat_message_chat_room_id'], 'required'],
            [['chat_message_id', 'worker_id', 'status_id_last', 'chat_message_chat_room_id'], 'integer'],
            [['chat_message_id', 'chat_message_chat_room_id'], 'exist', 'skipOnError' => true, 'targetClass' => ChatMessage::className(), 'targetAttribute' => ['chat_message_id' => 'id', 'chat_message_chat_room_id' => 'chat_room_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'chat_message_id' => 'Chat Message ID',
            'worker_id' => 'Worker ID',
            'status_id_last' => 'Status Id Last',
            'chat_message_chat_room_id' => 'Chat Message Chat Room ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChatMessage()
    {
        return $this->hasOne(ChatMessage::className(), ['id' => 'chat_message_id', 'chat_room_id' => 'chat_message_chat_room_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChatRecieverHistories()
    {
        return $this->hasMany(ChatRecieverHistory::className(), ['chat_message_reciever_id' => 'id']);
    }
}
