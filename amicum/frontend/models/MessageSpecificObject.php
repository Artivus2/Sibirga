<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "message_specific_object".
 *
 * @property int $id Таблица для хранения текстовых сообщений на пейджеры
 * @property int $specific_sender_id айдишник конкретного объекта отправителя из таблицы main
 * @property int $specific_reciever_id айдишник конкретного объекта получателя  из таблицы main
 * @property int $message_type_id
 * @property int $status_id доставлено/прочитано/отправлено из справочника статус 
 * @property string $text_message тип сообщения - между коммуникатором и светильником - в рамках системы страта или между пользователями смартфонов
 * @property string $datetime
 */
class MessageSpecificObject extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'message_specific_object';
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
            [['specific_sender_id', 'specific_reciever_id', 'message_type_id', 'status_id'], 'integer'],
            [['message_type_id', 'status_id', 'text_message', 'datetime'], 'required'],
            [['text_message'], 'string'],
            [['datetime'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Таблица для хранения текстовых сообщений на пейджеры',
            'specific_sender_id' => 'айдишник конкретного объекта отправителя из таблицы main',
            'specific_reciever_id' => 'айдишник конкретного объекта получателя  из таблицы main',
            'message_type_id' => 'Message Type ID',
            'status_id' => 'доставлено/прочитано/отправлено из справочника статус ',
            'text_message' => 'тип сообщения - между коммуникатором и светильником - в рамках системы страта или между пользователями смартфонов',
            'datetime' => 'Datetime',
        ];
    }
}
