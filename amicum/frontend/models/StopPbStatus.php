<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "stop_pb_status".
 *
 * @property int $id Идентификатор текущей таблицы (автоинкрементный)
 * @property int $stop_pb_id Внешний ключ простоя ПБ\n
 * @property int $worker_id Внешний ключ работника
 * @property int $status_id Внешний ключ статуса из списка статусов
 * @property string $date_time Дата и время смены статуса
 *
 * @property Status $status
 * @property StopPb $stopPb
 * @property Worker $worker
 */
class StopPbStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'stop_pb_status';
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
            [['stop_pb_id', 'worker_id', 'status_id', 'date_time'], 'required'],
            [['stop_pb_id', 'worker_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['stop_pb_id'], 'exist', 'skipOnError' => true, 'targetClass' => StopPb::className(), 'targetAttribute' => ['stop_pb_id' => 'id']],
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
            'stop_pb_id' => 'Stop Pb ID',
            'worker_id' => 'Worker ID',
            'status_id' => 'Status ID',
            'date_time' => 'Date Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStopPb()
    {
        return $this->hasOne(StopPb::className(), ['id' => 'stop_pb_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
