<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "stop_face".
 *
 * @property int $id Идентификатор таблицы
 * @property string $title Наименование простоя
 * @property int $event_id Внешний ключ к таблице событий(причина простоя)
 * @property string $description Комментарий к простою
 * @property string $date_time_start Дата начала простоя
 * @property string $date_time_end Дата окончания простоя
 * @property int $performer_id Внешний ключ к таблице сотрудников(Исполнитель)
 * @property int $dispatcher_id Внешний ключ к таблице сотрудников(Диспетчер)
 *
 * @property Event $event
 * @property Worker $performer
 * @property Worker $dispatcher
 */
class StopFace extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'stop_face';
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
            [['title', 'event_id', 'date_time_start', 'date_time_end'], 'required'],
            [['event_id', 'performer_id', 'dispatcher_id'], 'integer'],
            [['date_time_start', 'date_time_end'], 'safe'],
            [['title'], 'string', 'max' => 45],
            [['description'], 'string', 'max' => 255],
            [['event_id'], 'exist', 'skipOnError' => true, 'targetClass' => Event::className(), 'targetAttribute' => ['event_id' => 'id']],
            [['performer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['performer_id' => 'id']],
            [['dispatcher_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['dispatcher_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор таблицы',
            'title' => 'Наименование простоя',
            'event_id' => 'Внешний ключ к таблице событий(причина простоя)',
            'description' => 'Комментарий к простою',
            'date_time_start' => 'Дата начала простоя',
            'date_time_end' => 'Дата окончания простоя',
            'performer_id' => 'Внешний ключ к таблице сотрудников(Исполнитель)',
            'dispatcher_id' => 'Внешний ключ к таблице сотрудников(Диспетчер)',
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
    public function getPerformer()
    {
        return $this->hasOne(Worker::className(), ['id' => 'performer_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDispatcher()
    {
        return $this->hasOne(Worker::className(), ['id' => 'dispatcher_id']);
    }

    /** Связка написана вручную с операциями циклограммы */
    public function getCyclegrammOperations()
    {
        return $this->hasMany(CyclegrammOperation::className(), ['id' => 'stop_face_id']);
    }
}
