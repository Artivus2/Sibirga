<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "text_message".
 *
 * @property int $id
 * @property int $sender_sensor_id
 * @property int $reciever_sensor_id
 * @property int $sender_worker_id
 * @property int $reciever_worker_id
 * @property string $sender_network_id
 * @property string $reciever_network_id
 * @property string $message Сообщения длиной более 40 символов не отправляется на пейджер
 * @property int $status_id
 * @property int $message_id
 * @property string $datetime
 * @property string $message_type
 *
 * @property Sensor $recieverSensor
 * @property Worker $recieverWorker
 * @property Sensor $senderSensor
 * @property Worker $senderWorker
 * @property Status $status
 */
class TextMessage extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'text_message';
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
            [['sender_sensor_id', 'reciever_sensor_id', 'sender_worker_id', 'reciever_worker_id', 'status_id', 'message_id'], 'integer'],
            [['sender_network_id', 'reciever_network_id', 'message', 'status_id', 'message_id', 'datetime'], 'required'],
            [['datetime'], 'safe'],
            [['sender_network_id', 'reciever_network_id'], 'string', 'max' => 45],
            [['message'], 'string', 'max' => 40],
            [['message_type'], 'string', 'max' => 5],
            [['reciever_sensor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Sensor::className(), 'targetAttribute' => ['reciever_sensor_id' => 'id']],
            [['reciever_worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['reciever_worker_id' => 'id']],
            [['sender_sensor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Sensor::className(), 'targetAttribute' => ['sender_sensor_id' => 'id']],
            [['sender_worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['sender_worker_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sender_sensor_id' => 'Sender Sensor ID',
            'reciever_sensor_id' => 'Reciever Sensor ID',
            'sender_worker_id' => 'Sender Worker ID',
            'reciever_worker_id' => 'Reciever Worker ID',
            'sender_network_id' => 'Sender Network ID',
            'reciever_network_id' => 'Reciever Network ID',
            'message' => 'Сообщения длиной более 40 символов не отправляется на пейджер',
            'status_id' => 'Status ID',
            'message_id' => 'Message ID',
            'datetime' => 'Datetime',
            'message_type' => 'Message Type',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRecieverSensor()
    {
        return $this->hasOne(Sensor::className(), ['id' => 'reciever_sensor_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRecieverWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'reciever_worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSenderSensor()
    {
        return $this->hasOne(Sensor::className(), ['id' => 'sender_sensor_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSenderWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'sender_worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
