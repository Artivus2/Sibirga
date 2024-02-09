<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "shift_type".
 *
 * @property int $id
 * @property string $title
 *
 * @property ShiftSchedule[] $shiftSchedules
 * @property WorkModeShift[] $workModeShifts
 */
class ShiftType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'shift_type';
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
            [['title'], 'required'],
            [['title'], 'string', 'max' => 120],
            [['title'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
        ];
    }

    /**
     * Gets query for [[ShiftSchedules]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getShiftSchedules()
    {
        return $this->hasMany(ShiftSchedule::className(), ['shift_type_id' => 'id']);
    }

    /**
     * Gets query for [[WorkModeShifts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkModeShifts()
    {
        return $this->hasMany(WorkModeShift::className(), ['shift_type_id' => 'id']);
    }
}
