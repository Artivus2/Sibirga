<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "physical_plan".
 *
 * @property int $id
 * @property int $physical_schedule_id
 *
 * @property PhysicalSchedule $physicalSchedule
 * @property PhysicalWorker[] $physicalWorkers
 */
class PhysicalPlan extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'physical_plan';
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
            [['physical_schedule_id'], 'required'],
            [['physical_schedule_id'], 'integer'],
            [['physical_schedule_id'], 'exist', 'skipOnError' => true, 'targetClass' => PhysicalSchedule::className(), 'targetAttribute' => ['physical_schedule_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'physical_schedule_id' => 'Physical Schedule ID',
        ];
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
    public function getPhysicalWorkers()
    {
        return $this->hasMany(PhysicalWorker::className(), ['physical_plan_id' => 'id']);
    }
}
