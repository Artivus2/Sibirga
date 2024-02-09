<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "timetable_tabel".
 *
 * @property int $id
 * @property int $timetable_id
 * @property string $date
 * @property int $shift_id
 * @property int $hours_plan
 * @property int $worker_object_id
 * @property int $department_role_id
 *
 * @property DepartmentRole $departmentRole
 * @property Shift $shift
 * @property Timetable $timetable
 * @property WorkerObject $workerObject
 */
class TimetableTabel extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'timetable_tabel';
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
            [['id', 'timetable_id', 'date', 'shift_id', 'hours_plan', 'worker_object_id', 'department_role_id'], 'required'],
            [['id', 'timetable_id', 'shift_id', 'hours_plan', 'worker_object_id', 'department_role_id'], 'integer'],
            [['date'], 'safe'],
            [['id'], 'unique'],
            [['timetable_id', 'date', 'shift_id', 'worker_object_id', 'department_role_id'], 'unique', 'targetAttribute' => ['timetable_id', 'date', 'shift_id', 'worker_object_id', 'department_role_id']],
            [['department_role_id'], 'exist', 'skipOnError' => true, 'targetClass' => DepartmentRole::className(), 'targetAttribute' => ['department_role_id' => 'id']],
            [['shift_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shift::className(), 'targetAttribute' => ['shift_id' => 'id']],
            [['timetable_id'], 'exist', 'skipOnError' => true, 'targetClass' => Timetable::className(), 'targetAttribute' => ['timetable_id' => 'id']],
            [['worker_object_id'], 'exist', 'skipOnError' => true, 'targetClass' => WorkerObject::className(), 'targetAttribute' => ['worker_object_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'timetable_id' => 'Timetable ID',
            'date' => 'Date',
            'shift_id' => 'Shift ID',
            'hours_plan' => 'Hours Plan',
            'worker_object_id' => 'Worker Object ID',
            'department_role_id' => 'Department Role ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDepartmentRole()
    {
        return $this->hasOne(DepartmentRole::className(), ['id' => 'department_role_id']);
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
    public function getTimetable()
    {
        return $this->hasOne(Timetable::className(), ['id' => 'timetable_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerObject()
    {
        return $this->hasOne(WorkerObject::className(), ['id' => 'worker_object_id']);
    }
}
