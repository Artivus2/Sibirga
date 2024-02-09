<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "message_sensor".
 *
 * @property int $id Таблица для хранения текстовых сообщений на пейджеры
 * @property int $sensor_sender_id
 * @property int $sensor_reciever_id
 * @property string $text_message
 * @property string $datetime
 * @property int $status_id доставлено/прочитано/отправлено из справочника статус 
 * @property int $message_type_id тип сообщения - между коммуникатором и светильником - в рамках системы страта или между пользователями смартфонов
 *
 * @property Sensor $sensorSender
 */
class MessageSensor extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'message_sensor';
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
            [['sensor_sender_id', 'text_message', 'datetime'], 'required'],
            [['sensor_sender_id', 'sensor_reciever_id', 'status_id', 'message_type_id'], 'integer'],
            [['text_message'], 'string'],
            [['datetime'], 'safe'],
            [['sensor_sender_id'], 'exist', 'skipOnError' => true, 'targetClass' => Sensor::className(), 'targetAttribute' => ['sensor_sender_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Таблица для хранения текстовых сообщений на пейджеры',
            'sensor_sender_id' => 'Sensor Sender ID',
            'sensor_reciever_id' => 'Sensor Reciever ID',
            'text_message' => 'Text Message',
            'datetime' => 'Datetime',
            'status_id' => 'доставлено/прочитано/отправлено из справочника статус ',
            'message_type_id' => 'тип сообщения - между коммуникатором и светильником - в рамках системы страта или между пользователями смартфонов',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensorSender()
    {
        return $this->hasOne(Sensor::className(), ['id' => 'sensor_sender_id']);
    }
}
