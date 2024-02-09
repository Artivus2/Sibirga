<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "timetable_status".
 *
 * @property int $id
 * @property int $timetable_id
 * @property int $status_id
 * @property int $worker_object_id
 *
 * @property Status $status
 * @property Timetable $timetable
 * @property WorkerObject $workerObject
 */
class TimetableStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'timetable_status';
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
            [['id', 'timetable_id', 'status_id', 'worker_object_id'], 'required'],
            [['id', 'timetable_id', 'status_id', 'worker_object_id'], 'integer'],
            [['id'], 'unique'],
            [['timetable_id', 'status_id', 'worker_object_id'], 'unique', 'targetAttribute' => ['timetable_id', 'status_id', 'worker_object_id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['timetable_id'], 'exist', 'skipOnError' => true, 'targetClass' => Timetable::className(), 'targetAttribute' => ['timetable_id' => 'id']],
            [['worker_object_id'], 'exist', 'skipOnError' => true, 'targetClass' => WorkerObject::className(), 'targetAttribute' => ['worker_object_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'timetable_id' => 'Timetable ID',
            'status_id' => 'Status ID',
            'worker_object_id' => 'Worker Object ID',
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
    public function getTimetable()
    {
        return $this->hasOne(Timetable::className(), ['id' => 'timetable_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerObject()
    {
        return $this->hasOne(WorkerObject::className(), ['id' => 'worker_object_id']);
    }
}
