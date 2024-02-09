<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "chat_message_favorites".
 *
 * @property int $id
 * @property int $chat_message_id ключ сообщения
 * @property int $worker_id ключ работника
 *
 * @property ChatMessage $chatMessage
 * @property Worker $worker
 */
class ChatMessageFavorites extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chat_message_favorites';
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
            [['chat_message_id', 'worker_id'], 'required'],
            [['chat_message_id', 'worker_id'], 'integer'],
            [['chat_message_id', 'worker_id'], 'unique', 'targetAttribute' => ['chat_message_id', 'worker_id']],
            [['chat_message_id'], 'exist', 'skipOnError' => true, 'targetClass' => ChatMessage::className(), 'targetAttribute' => ['chat_message_id' => 'id']],
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
            'chat_message_id' => 'Chat Message ID',
            'worker_id' => 'Worker ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChatMessage()
    {
        return $this->hasOne(ChatMessage::className(), ['id' => 'chat_message_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
