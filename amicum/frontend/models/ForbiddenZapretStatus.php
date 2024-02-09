<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "forbidden_zapret_status".
 *
 * @property int $id Ключ таблицы статуса наряда
 * @property int $forbidden_zapret_id внешний ключ списка нарядов
 * @property int $status_id внешний ключ справочника статусов
 * @property int $worker_id
 * @property string $date_time_create
 *
 * @property ForbiddenZapret $forbiddenZapret
 * @property Status $status
 * @property Worker $worker
 */
class ForbiddenZapretStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'forbidden_zapret_status';
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
            [['forbidden_zapret_id', 'status_id', 'worker_id', 'date_time_create'], 'required'],
            [['forbidden_zapret_id', 'status_id', 'worker_id'], 'integer'],
            [['date_time_create'], 'safe'],
            [['forbidden_zapret_id'], 'exist', 'skipOnError' => true, 'targetClass' => ForbiddenZapret::className(), 'targetAttribute' => ['forbidden_zapret_id' => 'id']],
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
            'forbidden_zapret_id' => 'Forbidden Zapret ID',
            'status_id' => 'Status ID',
            'worker_id' => 'Worker ID',
            'date_time_create' => 'Date Time Create',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getForbiddenZapret()
    {
        return $this->hasOne(ForbiddenZapret::className(), ['id' => 'forbidden_zapret_id']);
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
