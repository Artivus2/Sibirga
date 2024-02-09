<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "examination_answer".
 *
 * @property int $id ключ ответов
 * @property int $examination_id ключ проверки знаний
 * @property int $test_question_id ключ вопроса
 * @property int $test_question_answer_id ключ ответа
 * @property int $flag_answer ответ пользователя
 * @property float $count_mark количество баллов за ответ
 * @property int|null $flag_true правильный или нет ответ
 *
 * @property Examination $examination
 * @property TestQuestionAnswer $testQuestionAnswer
 * @property TestQuestion $testQuestion
 */
class ExaminationAnswer extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'examination_answer';
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
            [['examination_id', 'test_question_id', 'test_question_answer_id', 'flag_answer'], 'required'],
            [['examination_id', 'test_question_id', 'test_question_answer_id', 'flag_answer', 'flag_true'], 'integer'],
            [['count_mark'], 'number'],
            [['examination_id'], 'exist', 'skipOnError' => true, 'targetClass' => Examination::className(), 'targetAttribute' => ['examination_id' => 'id']],
            [['test_question_answer_id'], 'exist', 'skipOnError' => true, 'targetClass' => TestQuestionAnswer::className(), 'targetAttribute' => ['test_question_answer_id' => 'id']],
            [['test_question_id'], 'exist', 'skipOnError' => true, 'targetClass' => TestQuestion::className(), 'targetAttribute' => ['test_question_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ ответов',
            'examination_id' => 'ключ проверки знаний',
            'test_question_id' => 'ключ вопроса',
            'test_question_answer_id' => 'ключ ответа',
            'flag_answer' => 'ответ пользователя',
            'count_mark' => 'количество баллов за ответ',
            'flag_true' => 'правильный или нет ответ',
        ];
    }

    /**
     * Gets query for [[Examination]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExamination()
    {
        return $this->hasOne(Examination::className(), ['id' => 'examination_id']);
    }

    /**
     * Gets query for [[TestQuestionAnswer]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTestQuestionAnswer()
    {
        return $this->hasOne(TestQuestionAnswer::className(), ['id' => 'test_question_answer_id']);
    }

    /**
     * Gets query for [[TestQuestion]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTestQuestion()
    {
        return $this->hasOne(TestQuestion::className(), ['id' => 'test_question_id']);
    }
}
