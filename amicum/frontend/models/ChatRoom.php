<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "chat_room".
 *
 * @property int $id
 * @property string $title
 * @property string $creation_date
 * @property int $chat_type_id
 *
 * @property ChatMember[] $chatMembers
 * @property ChatMessage[] $chatMessages
 * @property ChatType $chatType
 */
class ChatRoom extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chat_room';
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
            [['title', 'creation_date', 'chat_type_id'], 'required'],
            [['creation_date'], 'safe'],
            [['chat_type_id'], 'integer'],
            [['title'], 'string', 'max' => 45],
            [['title'], 'unique'],
            [['chat_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ChatType::className(), 'targetAttribute' => ['chat_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'creation_date' => 'Creation Date',
            'chat_type_id' => 'Chat Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChatMembers()
    {
        return $this->hasMany(ChatMember::className(), ['chat_room_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChatMessages()
    {
        return $this->hasMany(ChatMessage::className(), ['chat_room_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChatType()
    {
        return $this->hasOne(ChatType::className(), ['id' => 'chat_type_id']);
    }
}
