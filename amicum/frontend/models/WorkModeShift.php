<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "work_mode_shift".
 *
 * @property int $id
 * @property int $work_mode_id
 * @property string $time_start
 * @property string $time_end
 * @property int $shift_type_id
 * @property int $shift_id
 *
 * @property Shift $shift
 * @property ShiftType $shiftType
 * @property WorkMode $workMode
 */
class WorkModeShift extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'work_mode_shift';
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
            [['work_mode_id', 'time_start', 'time_end', 'shift_type_id', 'shift_id'], 'required'],
            [['work_mode_id', 'shift_type_id', 'shift_id'], 'integer'],
            [['time_start', 'time_end'], 'safe'],
            [['shift_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shift::className(), 'targetAttribute' => ['shift_id' => 'id']],
            [['shift_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ShiftType::className(), 'targetAttribute' => ['shift_type_id' => 'id']],
            [['work_mode_id'], 'exist', 'skipOnError' => true, 'targetClass' => WorkMode::className(), 'targetAttribute' => ['work_mode_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'work_mode_id' => 'Work Mode ID',
            'time_start' => 'Time Start',
            'time_end' => 'Time End',
            'shift_type_id' => 'Shift Type ID',
            'shift_id' => 'Shift ID',
        ];
    }

    /**
     * Gets query for [[Shift]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getShift()
    {
        return $this->hasOne(Shift::className(), ['id' => 'shift_id']);
    }

    /**
     * Gets query for [[ShiftType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getShiftType()
    {
        return $this->hasOne(ShiftType::className(), ['id' => 'shift_type_id']);
    }

    /**
     * Gets query for [[WorkMode]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkMode()
    {
        return $this->hasOne(WorkMode::className(), ['id' => 'work_mode_id']);
    }
}
