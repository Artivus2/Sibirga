<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "update_wish".
 *
 * @property int $id ключ таблицы пожеланий
 * @property string $date_time Дата пожелания
 * @property string $title пожелание пользователя
 * @property string|null $description_meta мета описание (нужно на будущее, если пользователь будет копипастить например с ворда
 * @property int $worker_id ключ работника, который оставил пожелание
 * @property int $status_id статус пожелания (выполнено или нет)
 *
 * @property Status $status
 * @property Worker $worker
 */
class UpdateWish extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'update_wish';
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
            [['date_time', 'title', 'worker_id', 'status_id'], 'required'],
            [['date_time', 'description_meta'], 'safe'],
            [['worker_id', 'status_id'], 'integer'],
            [['title'], 'string', 'max' => 9000],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
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
            'date_time' => 'Date Time',
            'title' => 'Title',
            'description_meta' => 'Description Meta',
            'worker_id' => 'Worker ID',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * Gets query for [[Status]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * Gets query for [[Worker]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
