<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "briefer".
 *
 * @property int $id
 * @property int $worker_id Ключ сотрудника
 * @property int $position_id Должность инспектируемого
 * @property int $status_id Подтверждение прохождение инструктажа
 * @property int $briefing_id Какой инструктаж был проведен
 * @property string $date_time дата прохождения инструктажа
 * @property string $date_time_first последний инструктаж
 * @property string $date_time_second предпоследний инструктаж
 * @property string $date_time_third предпредпоследний инструктаж
 * @property int $internship_reason_id ключ основание для проведения стажировки
 * @property string $internship_start дата начала стажировки
 * @property string $internship_end дата окончания стажировки
 * @property int $internship_worker_id ключ наставника
 * @property int $internship_position_id роль наставника
 * @property int $internship_taken_status_id статус назначения стажировки
 * @property int $internship_end_status_id статус окончания стажировки
 * @property string $internship_end_fact_date время и дата окончания стажировки
 * @property int $duration_day ПРодолжительность стажировки
 *
 * @property Briefing $briefing
 * @property Position $position
 * @property Status $status
 * @property Worker $worker
 * @property Worker $internshipWorker
 * @property InternshipReason $internshipReason
 * @property Status $internshipEndStatus
 * @property Status $internshipTakenStatus
 */
class Briefer extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'briefer';
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
            [['worker_id', 'position_id', 'status_id', 'briefing_id'], 'required'],
            [['worker_id', 'position_id', 'status_id', 'briefing_id', 'internship_reason_id', 'internship_worker_id', 'internship_position_id', 'internship_taken_status_id', 'internship_end_status_id', 'duration_day'], 'integer'],
            [['date_time', 'date_time_first', 'date_time_second', 'date_time_third', 'internship_start', 'internship_end', 'internship_end_fact_date'], 'safe'],
            [['worker_id', 'briefing_id'], 'unique', 'targetAttribute' => ['worker_id', 'briefing_id']],
            [['briefing_id'], 'exist', 'skipOnError' => true, 'targetClass' => Briefing::className(), 'targetAttribute' => ['briefing_id' => 'id']],
            [['position_id'], 'exist', 'skipOnError' => true, 'targetClass' => Position::className(), 'targetAttribute' => ['position_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
            [['internship_worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['internship_worker_id' => 'id']],
            [['internship_reason_id'], 'exist', 'skipOnError' => true, 'targetClass' => InternshipReason::className(), 'targetAttribute' => ['internship_reason_id' => 'id']],
            [['internship_end_status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['internship_end_status_id' => 'id']],
            [['internship_taken_status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['internship_taken_status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'worker_id' => 'Ключ сотрудника',
            'position_id' => 'Должность инспектируемого',
            'status_id' => 'Подтверждение прохождение инструктажа',
            'briefing_id' => 'Какой инструктаж был проведен',
            'date_time' => 'дата прохождения инструктажа',
            'date_time_first' => 'последний инструктаж',
            'date_time_second' => 'предпоследний инструктаж',
            'date_time_third' => 'предпредпоследний инструктаж',
            'internship_reason_id' => 'ключ основание для проведения стажировки',
            'internship_start' => 'дата начала стажировки',
            'internship_end' => 'дата окончания стажировки',
            'internship_worker_id' => 'ключ наставника',
            'internship_position_id' => 'роль наставника',
            'internship_taken_status_id' => 'статус назначения стажировки',
            'internship_end_status_id' => 'статус окончания стажировки',
            'internship_end_fact_date' => 'время и дата окончания стажировки',
            'duration_day' => 'ПРодолжительность стажировки',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBriefing()
    {
        return $this->hasOne(Briefing::className(), ['id' => 'briefing_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPosition()
    {
        return $this->hasOne(Position::className(), ['id' => 'position_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
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
    public function getInternshipWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'internship_worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInternshipReason()
    {
        return $this->hasOne(InternshipReason::className(), ['id' => 'internship_reason_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInternshipEndStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'internship_end_status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInternshipTakenStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'internship_taken_status_id']);
    }
}
