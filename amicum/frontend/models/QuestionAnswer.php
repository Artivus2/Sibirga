<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "question_answer".
 *
 * @property int $id ключ ответа
 * @property int $question_id ключ вопроса
 * @property string|null $title название ответа
 * @property float|null $count_mark количество балов
 * @property int|null $flag_true Признак верного ответа
 * @property int|null $number_in_order Номер по порядку
 * @property int|null $actual_status Статус актуальности (показывать или нет в вопросе
 *
 * @property Question $question
 */
class QuestionAnswer extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'question_answer';
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
            [['question_id'], 'required'],
            [['question_id', 'flag_true', 'number_in_order', 'actual_status'], 'integer'],
            [['count_mark'], 'number'],
            [['title'], 'string', 'max' => 724],
            [['question_id'], 'exist', 'skipOnError' => true, 'targetClass' => Question::className(), 'targetAttribute' => ['question_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'question_id' => 'Question ID',
            'title' => 'Title',
            'count_mark' => 'Count Mark',
            'flag_true' => 'Flag True',
            'number_in_order' => 'Number In Order',
            'actual_status' => 'Actual Status',
        ];
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
}
