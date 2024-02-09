<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "injunction_status".
 *
 * @property int $id Идентификатор текущей таблицы (автоинкрементный)\n
 * @property int $injunction_id Внешний ключ предписания
 * @property int $worker_id Внешний ключ работника
 * @property int $status_id Внешний ключ статуса из списка статусов(действующий, недействующий)
 * @property string $date_time Дата и время изменения статуса
 *
 * @property Injunction $injunction
 * @property Status $status
 * @property Worker $worker
 */
class InjunctionStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'injunction_status';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['injunction_id', 'worker_id', 'status_id', 'date_time'], 'required'],
            [['injunction_id', 'worker_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['injunction_id'], 'exist', 'skipOnError' => true, 'targetClass' => Injunction::className(), 'targetAttribute' => ['injunction_id' => 'id']],
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
            'injunction_id' => 'Injunction ID',
            'worker_id' => 'Worker ID',
            'status_id' => 'Status ID',
            'date_time' => 'Date Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInjunction()
    {
        return $this->hasOne(Injunction::className(), ['id' => 'injunction_id']);
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
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
