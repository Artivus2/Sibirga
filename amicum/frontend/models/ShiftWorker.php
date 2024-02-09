<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "shift_worker".
 *
 * @property int $id
 * @property string $date_time
 * @property int $plan_shift_id
 * @property int $worker_id
 *
 * @property PlanShift $planShift
 * @property Worker $worker
 */
class ShiftWorker extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'shift_worker';
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
            [['date_time', 'plan_shift_id', 'worker_id'], 'required'],
            [['date_time'], 'safe'],
            [['plan_shift_id', 'worker_id'], 'integer'],
            [['plan_shift_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlanShift::className(), 'targetAttribute' => ['plan_shift_id' => 'id']],
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
            'plan_shift_id' => 'Plan Shift ID',
            'worker_id' => 'Worker ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlanShift()
    {
        return $this->hasOne(PlanShift::className(), ['id' => 'plan_shift_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
