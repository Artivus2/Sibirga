<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "test_question_answer".
 *
 * @property int $id ключ ответа
 * @property string|null $title название ответа
 * @property int $test_question_id ключ вопроса теста
 * @property float|null $count_mark количество балов
 * @property int|null $flag_true Признак верного ответа
 * @property int|null $number_in_order Номер по порядку
 * @property int|null $actual_status Статус актуальности (показывать или нет в вопросе
 *
 * @property ExaminationAnswer[] $examinationAnswers
 * @property TestQuestion $testQuestion
 */
class TestQuestionAnswer extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'test_question_answer';
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
            [['test_question_id'], 'required'],
            [['test_question_id', 'flag_true', 'number_in_order', 'actual_status'], 'integer'],
            [['count_mark'], 'number'],
            [['title'], 'string', 'max' => 724],
            [['test_question_id'], 'exist', 'skipOnError' => true, 'targetClass' => TestQuestion::className(), 'targetAttribute' => ['test_question_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ ответа',
            'title' => 'название ответа',
            'test_question_id' => 'ключ вопроса теста',
            'count_mark' => 'количество балов',
            'flag_true' => 'Признак верного ответа',
            'number_in_order' => 'Номер по порядку',
            'actual_status' => 'Статус актуальности (показывать или нет в вопросе',
        ];
    }

    /**
     * Gets query for [[ExaminationAnswers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExaminationAnswers()
    {
        return $this->hasMany(ExaminationAnswer::className(), ['test_question_answer_id' => 'id']);
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
