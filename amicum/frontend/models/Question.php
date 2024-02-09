<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "question".
 *
 * @property int $id ключ вопроса
 * @property string $title название вопроса
 * @property string|null $question
 * @property string|null $date_time_create Дата и время созданий
 * @property string|null $comment Комментарий при неправильном ответе
 *
 * @property QuestionAnswer[] $questionAnswers
 * @property QuestionMedia[] $questionMedia
 * @property QuestionParagraphPb[] $questionParagraphPbs
 * @property TestQuestion[] $testQuestions
 * @property Test[] $tests
 */
class Question extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'question';
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
            [['question'], 'string'],
            [['date_time_create'], 'safe'],
            [['title'], 'string', 'max' => 1024],
            [['comment'], 'string', 'max' => 1025],
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
            'question' => 'Question',
            'date_time_create' => 'Date Time Create',
            'comment' => 'Comment',
        ];
    }

    /**
     * Gets query for [[QuestionAnswers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuestionAnswers()
    {
        return $this->hasMany(QuestionAnswer::className(), ['question_id' => 'id']);
    }

    /**
     * Gets query for [[QuestionMedia]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuestionMedia()
    {
        return $this->hasMany(QuestionMedia::className(), ['question_id' => 'id']);
    }

    /**
     * Gets query for [[QuestionParagraphPbs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuestionParagraphPbs()
    {
        return $this->hasMany(QuestionParagraphPb::className(), ['question_id' => 'id']);
    }

    /**
     * Gets query for [[TestQuestions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTestQuestions()
    {
        return $this->hasMany(TestQuestion::className(), ['question_id' => 'id']);
    }

    /**
     * Gets query for [[Tests]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTests()
    {
        return $this->hasMany(Test::className(), ['id' => 'test_id'])->viaTable('test_question', ['question_id' => 'id']);
    }
}
