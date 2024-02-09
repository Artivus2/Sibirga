<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "physical_worker".
 *
 * @property int $id
 * @property int $worker_id Ключ сотрудника, который должен пройти мо
 * @property int $contingent_id К какому контингенту относится данный сотрудник
 * @property int $physical_schedule_id
 *
 * @property Contingent $contingent
 * @property PhysicalSchedule $physicalSchedule
 * @property Worker $worker
 * @property PhysicalWorkerDate[] $physicalWorkerDates
 */
class PhysicalWorker extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'physical_worker';
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
            [['worker_id', 'contingent_id', 'physical_schedule_id'], 'required'],
            [['worker_id', 'contingent_id', 'physical_schedule_id'], 'integer'],
            [['contingent_id'], 'exist', 'skipOnError' => true, 'targetClass' => Contingent::className(), 'targetAttribute' => ['contingent_id' => 'id']],
            [['physical_schedule_id'], 'exist', 'skipOnError' => true, 'targetClass' => PhysicalSchedule::className(), 'targetAttribute' => ['physical_schedule_id' => 'id']],
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
            'worker_id' => 'Ключ сотрудника, который должен пройти мо',
            'contingent_id' => 'К какому контингенту относится данный сотрудник',
            'physical_schedule_id' => 'Physical Schedule ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContingent()
    {
        return $this->hasOne(Contingent::className(), ['id' => 'contingent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhysicalSchedule()
    {
        return $this->hasOne(PhysicalSchedule::className(), ['id' => 'physical_schedule_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhysicalWorkerDate()
    {
        return $this->hasOne(PhysicalWorkerDate::className(), ['physical_worker_id' => 'id']);
    }
}
