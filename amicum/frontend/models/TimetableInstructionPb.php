<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "timetable_instruction_pb".
 *
 * @property int $id
 * @property int $timetable_pb_id
 * @property int $instruction_pb_id
 * @property int $department_id
 * @property int $shift_id
 * @property string $date
 *
 * @property Department $department
 * @property InstructionPb $instructionPb
 * @property Shift $shift
 * @property TimetablePb $timetablePb
 */
class TimetableInstructionPb extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'timetable_instruction_pb';
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
            [['id', 'timetable_pb_id', 'instruction_pb_id', 'department_id', 'shift_id', 'date'], 'required'],
            [['id', 'timetable_pb_id', 'instruction_pb_id', 'department_id', 'shift_id'], 'integer'],
            [['date'], 'safe'],
            [['id'], 'unique'],
            [['timetable_pb_id', 'instruction_pb_id', 'department_id', 'shift_id', 'date'], 'unique', 'targetAttribute' => ['timetable_pb_id', 'instruction_pb_id', 'department_id', 'shift_id', 'date']],
            [['department_id'], 'exist', 'skipOnError' => true, 'targetClass' => Department::className(), 'targetAttribute' => ['department_id' => 'id']],
            [['instruction_pb_id'], 'exist', 'skipOnError' => true, 'targetClass' => InstructionPb::className(), 'targetAttribute' => ['instruction_pb_id' => 'id']],
            [['shift_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shift::className(), 'targetAttribute' => ['shift_id' => 'id']],
            [['timetable_pb_id'], 'exist', 'skipOnError' => true, 'targetClass' => TimetablePb::className(), 'targetAttribute' => ['timetable_pb_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'timetable_pb_id' => 'Timetable Pb ID',
            'instruction_pb_id' => 'Instruction Pb ID',
            'department_id' => 'Department ID',
            'shift_id' => 'Shift ID',
            'date' => 'Date',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDepartment()
    {
        return $this->hasOne(Department::className(), ['id' => 'department_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInstructionPb()
    {
        return $this->hasOne(InstructionPb::className(), ['id' => 'instruction_pb_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShift()
    {
        return $this->hasOne(Shift::className(), ['id' => 'shift_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTimetablePb()
    {
        return $this->hasOne(TimetablePb::className(), ['id' => 'timetable_pb_id']);
    }
}
