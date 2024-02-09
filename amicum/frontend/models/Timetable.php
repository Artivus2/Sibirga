<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "timetable".
 *
 * @property int $id
 * @property string $title
 * @property string $date_time_create
 * @property string $month
 * @property int $department_id
 *
 * @property Department $department
 * @property TimetableStatus[] $timetableStatuses
 * @property TimetableTabel[] $timetableTabels
 */
class Timetable extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'timetable';
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
            [['title', 'date_time_create', 'month', 'department_id'], 'required'],
            [['date_time_create', 'month'], 'safe'],
            [['department_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['department_id'], 'exist', 'skipOnError' => true, 'targetClass' => Department::className(), 'targetAttribute' => ['department_id' => 'id']],
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
            'date_time_create' => 'Date Time Create',
            'month' => 'Month',
            'department_id' => 'Department ID',
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
    public function getTimetableStatuses()
    {
        return $this->hasMany(TimetableStatus::className(), ['timetable_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTimetableTabels()
    {
        return $this->hasMany(TimetableTabel::className(), ['timetable_id' => 'id']);
    }
}
