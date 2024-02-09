<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "question_paragraph_pb".
 *
 * @property int $id
 * @property int $question_id
 * @property int $paragraph_pb_id
 *
 * @property Question $question
 * @property ParagraphPb $paragraphPb
 */
class QuestionParagraphPb extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'question_paragraph_pb';
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
            [['question_id', 'paragraph_pb_id'], 'required'],
            [['question_id', 'paragraph_pb_id'], 'integer'],
            [['question_id'], 'exist', 'skipOnError' => true, 'targetClass' => Question::className(), 'targetAttribute' => ['question_id' => 'id']],
            [['paragraph_pb_id'], 'exist', 'skipOnError' => true, 'targetClass' => ParagraphPb::className(), 'targetAttribute' => ['paragraph_pb_id' => 'id']],
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
            'paragraph_pb_id' => 'Paragraph Pb ID',
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

    /**
     * Gets query for [[ParagraphPb]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParagraphPb()
    {
        return $this->hasOne(ParagraphPb::className(), ['id' => 'paragraph_pb_id']);
    }
}
