<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "plan_shift".
 *
 * @property int $id
 * @property string $title
 * @property string $date
 *
 * @property ShiftDepartment[] $shiftDepartments
 * @property ShiftMine[] $shiftMines
 * @property ShiftSchedule[] $shiftSchedules
 * @property ShiftWorker[] $shiftWorkers
 */
class PlanShift extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'plan_shift';
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
            [['title', 'date'], 'required'],
            [['date'], 'safe'],
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
            'date' => 'Date',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShiftDepartments()
    {
        return $this->hasMany(ShiftDepartment::className(), ['plan_shift_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShiftMines()
    {
        return $this->hasMany(ShiftMine::className(), ['plan_shift_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShiftSchedules()
    {
        return $this->hasMany(ShiftSchedule::className(), ['plan_shift_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShiftWorkers()
    {
        return $this->hasMany(ShiftWorker::className(), ['plan_shift_id' => 'id']);
    }
}
