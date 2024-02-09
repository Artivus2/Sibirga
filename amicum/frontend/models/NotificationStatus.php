<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "notification_status".
 *
 * @property int $id
 * @property int $worker_id
 * @property string $date_time
 * @property int $restriction_id
 * @property string $type_restriction
 * @property int $status_id
 *
 * @property Status $status
 * @property Worker $worker
 */
class NotificationStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'notification_status';
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
            [['worker_id', 'date_time', 'restriction_id', 'type_restriction', 'status_id'], 'required'],
            [['worker_id', 'restriction_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['type_restriction'], 'string', 'max' => 45],
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
            'worker_id' => 'Worker ID',
            'date_time' => 'Date Time',
            'restriction_id' => 'Restriction ID',
            'type_restriction' => 'Type Restriction',
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
