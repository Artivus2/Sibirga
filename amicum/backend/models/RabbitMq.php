<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "rabbit_mq".
 *
 * @property int $id ключ журнала синхронизации
 * @property string $message сообщений синхронизации
 * @property string $date_time_create дата создания сообщения синхронизации
 * @property int|null $status статус обработки синхронизации
 * @property string $queue_name Название очереди, она же таблица синхронизации
 */
class RabbitMq extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'rabbit_mq';
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
            [['message', 'date_time_create', 'queue_name'], 'required'],
            [['message'], 'string'],
            [['date_time_create'], 'safe'],
            [['status'], 'integer'],
            [['queue_name'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'message' => 'Message',
            'date_time_create' => 'Date Time Create',
            'status' => 'Status',
            'queue_name' => 'Queue Name',
        ];
    }
}
