<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "forbidden_zapret".
 *
 * @property int $id
 * @property int $forbidden_type_id Внешний идентификатор типа запретной зоны
 * @property int $forbidden_zone_id Внешний идентификатор запретной зоны
 * @property int $status_id Статус запретной зоны
 * @property string $date_time_create Дата создания (6)1
 * @property string $description Описание запрета
 * @property int|null $worker_id Кто смнеил статус
 *
 * @property ForbiddenTime[] $forbiddenTimes
 * @property ForbiddenType $forbiddenType
 * @property Status $status
 * @property Worker $worker
 * @property Worker $worker0
 * @property ForbiddenZone $forbiddenZone
 * @property Status $status0
 * @property ForbiddenZapretStatus[] $forbiddenZapretStatuses
 */
class ForbiddenZapret extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'forbidden_zapret';
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
            [['forbidden_type_id', 'forbidden_zone_id', 'status_id', 'date_time_create', 'description'], 'required'],
            [['forbidden_type_id', 'forbidden_zone_id', 'status_id', 'worker_id'], 'integer'],
            [['date_time_create'], 'safe'],
            [['description'], 'string', 'max' => 255],
            [['forbidden_zone_id', 'date_time_create'], 'unique', 'targetAttribute' => ['forbidden_zone_id', 'date_time_create']],
            [['forbidden_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ForbiddenType::className(), 'targetAttribute' => ['forbidden_type_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
            [['forbidden_zone_id'], 'exist', 'skipOnError' => true, 'targetClass' => ForbiddenZone::className(), 'targetAttribute' => ['forbidden_zone_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'forbidden_type_id' => 'Forbidden Type ID',
            'forbidden_zone_id' => 'Forbidden Zone ID',
            'status_id' => 'Status ID',
            'date_time_create' => 'Date Time Create',
            'description' => 'Description',
            'worker_id' => 'Worker ID',
        ];
    }

    /**
     * Gets query for [[ForbiddenTimes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getForbiddenTimes()
    {
        return $this->hasMany(ForbiddenTime::className(), ['forbidden_zapret_id' => 'id']);
    }

    /**
     * Gets query for [[ForbiddenType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getForbiddenType()
    {
        return $this->hasOne(ForbiddenType::className(), ['id' => 'forbidden_type_id']);
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

    /**
     * Gets query for [[Worker0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorker0()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }

    /**
     * Gets query for [[ForbiddenZone]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getForbiddenZone()
    {
        return $this->hasOne(ForbiddenZone::className(), ['id' => 'forbidden_zone_id']);
    }

    /**
     * Gets query for [[Status0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatus0()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * Gets query for [[ForbiddenZapretStatuses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getForbiddenZapretStatuses()
    {
        return $this->hasMany(ForbiddenZapretStatus::className(), ['forbidden_zapret_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker1()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id'])->alias('worker1');
    }
}
