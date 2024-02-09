<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "test_question".
 *
 * @property int $id
 * @property int $question_id ключ вопроса
 * @property int $test_id ключ теста
 * @property int|null $actual_status Статус актуальности вопроса
 * @property string|null $date_time_create Дата и время созданий
 *
 * @property ExaminationAnswer[] $examinationAnswers
 * @property Question $question
 * @property Test $test
 * @property TestQuestionAnswer[] $testQuestionAnswers
 */
class TestQuestion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'test_question';
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
            [['question_id', 'test_id'], 'required'],
            [['question_id', 'test_id', 'actual_status'], 'integer'],
            [['date_time_create'], 'safe'],
            [['question_id'], 'exist', 'skipOnError' => true, 'targetClass' => Question::className(), 'targetAttribute' => ['question_id' => 'id']],
            [['test_id'], 'exist', 'skipOnError' => true, 'targetClass' => Test::className(), 'targetAttribute' => ['test_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'question_id' => 'ключ вопроса',
            'test_id' => 'ключ теста',
            'actual_status' => 'Статус актуальности вопроса',
            'date_time_create' => 'Дата и время созданий',
        ];
    }

    /**
     * Gets query for [[ExaminationAnswers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExaminationAnswers()
    {
        return $this->hasMany(ExaminationAnswer::className(), ['test_question_id' => 'id']);
    }

    /**
     * Gets query for [[Question]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuestion()
    {
        return $this->hasOne(Question::className(), ['id' => 'question_id']);
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
     * Gets query for [[TestQuestionAnswers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTestQuestionAnswers()
    {
        return $this->hasMany(TestQuestionAnswer::className(), ['test_question_id' => 'id']);
    }
}
