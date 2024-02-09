<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "examination".
 *
 * @property int $id ключ тестирования
 * @property int $worker_id ключ работника
 * @property int $type_test_id тип проверки знаний
 * @property int $position_id
 * @property int $test_id ключ теста
 * @property string $date_time дата проведения проверки знания
 * @property string $date_time_start дата начала проверки знания
 * @property string $date_time_end дата окончания проверки знаний
 * @property int $duration продолжительность, в секундах
 * @property int $status_id
 * @property float $count_mark Количество баллов
 * @property int $mine_id ключ шахты
 * @property int $company_department_id
 *
 * @property CompanyDepartment $companyDepartment
 * @property Mine $mine
 * @property Position $position
 * @property Status $status
 * @property Test $test
 * @property TypeTest $typeTest
 * @property Worker $worker
 * @property ExaminationAnswer[] $examinationAnswers
 * @property TestQuestion[] $testQuestions
 * @property ExaminationAttachment[] $examinationAttachments
 */
class Examination extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'examination';
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
            [['worker_id', 'type_test_id', 'position_id', 'test_id', 'date_time', 'date_time_start', 'date_time_end', 'status_id', 'mine_id', 'company_department_id'], 'required'],
            [['worker_id', 'type_test_id', 'position_id', 'test_id', 'duration', 'status_id', 'mine_id', 'company_department_id'], 'integer'],
            [['date_time', 'date_time_start', 'date_time_end'], 'safe'],
            [['count_mark'], 'number'],
            [['worker_id', 'type_test_id', 'test_id', 'date_time_start'], 'unique', 'targetAttribute' => ['worker_id', 'type_test_id', 'test_id', 'date_time_start']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['mine_id'], 'exist', 'skipOnError' => true, 'targetClass' => Mine::className(), 'targetAttribute' => ['mine_id' => 'id']],
            [['position_id'], 'exist', 'skipOnError' => true, 'targetClass' => Position::className(), 'targetAttribute' => ['position_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['test_id'], 'exist', 'skipOnError' => true, 'targetClass' => Test::className(), 'targetAttribute' => ['test_id' => 'id']],
            [['type_test_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypeTest::className(), 'targetAttribute' => ['type_test_id' => 'id']],
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
            'worker_id' => 'Worker ID',
            'type_test_id' => 'Type Test ID',
            'position_id' => 'Position ID',
            'test_id' => 'Test ID',
            'date_time' => 'Date Time',
            'date_time_start' => 'Date Time Start',
            'date_time_end' => 'Date Time End',
            'duration' => 'Duration',
            'status_id' => 'Status ID',
            'count_mark' => 'Count Mark',
            'mine_id' => 'Mine ID',
            'company_department_id' => 'Company Department ID',
        ];
    }

    /**
     * Gets query for [[CompanyDepartment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartment()
    {
        return $this->hasOne(CompanyDepartment::className(), ['id' => 'company_department_id']);
    }

    /**
     * Gets query for [[Mine]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMine()
    {
        return $this->hasOne(Mine::className(), ['id' => 'mine_id']);
    }

    /**
     * Gets query for [[Position]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPosition()
    {
        return $this->hasOne(Position::className(), ['id' => 'position_id']);
    }

    /**
     * Gets query for [[Status]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * Gets query for [[Test]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTest()
    {
        return $this->hasOne(Test::className(), ['id' => 'test_id']);
    }

    /**
     * Gets query for [[TypeTest]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTypeTest()
    {
        return $this->hasOne(TypeTest::className(), ['id' => 'type_test_id']);
    }

    /**
     * Gets query for [[Worker]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }

    /**
     * Gets query for [[ExaminationAnswers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExaminationAnswers()
    {
        return $this->hasMany(ExaminationAnswer::className(), ['examination_id' => 'id']);
    }

    /**
     * Gets query for [[TestQuestions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTestQuestions()
    {
        return $this->hasMany(TestQuestion::className(), ['id' => 'test_question_id'])->via('examinationAnswers');
    }

    /**
     * Gets query for [[ExaminationAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExaminationAttachments()
    {
        return $this->hasMany(ExaminationAttachment::className(), ['examination_id' => 'id']);
    }
}
