<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "shift_schedule".
 *
 * @property int $id
 * @property int $plan_shift_id
 * @property string $title
 * @property string $time_start
 * @property string $time_end
 * @property int $shift_type_id
 *
 * @property PlanShift $planShift
 * @property ShiftType $shiftType
 */
class ShiftSchedule extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'shift_schedule';
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
            [['plan_shift_id', 'title', 'time_start', 'time_end', 'shift_type_id'], 'required'],
            [['plan_shift_id', 'shift_type_id'], 'integer'],
            [['time_start', 'time_end'], 'safe'],
            [['title'], 'string', 'max' => 45],
            [['plan_shift_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlanShift::className(), 'targetAttribute' => ['plan_shift_id' => 'id']],
            [['shift_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ShiftType::className(), 'targetAttribute' => ['shift_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'plan_shift_id' => 'Plan Shift ID',
            'title' => 'Title',
            'time_start' => 'Time Start',
            'time_end' => 'Time End',
            'shift_type_id' => 'Shift Type ID',
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
    public function getShiftType()
    {
        return $this->hasOne(ShiftType::className(), ['id' => 'shift_type_id']);
    }
}
