<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "paragraph_pb".
 *
 * @property int $id Идентификатор текущей таблицы (автоинкрементный)\\n
 * @property int $document_id Внешний ключ документа из списка документов
 * @property string $text Полное описание пункта ПБ
 *
 * @property InjunctionViolation[] $injunctionViolations
 * @property Document $document
 * @property QuestionParagraphPb[] $questionParagraphPbs
 */
class ParagraphPb extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'paragraph_pb';
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
            [['document_id', 'text'], 'required'],
            [['document_id'], 'integer'],
            [['text'], 'string'],
            [['document_id'], 'exist', 'skipOnError' => true, 'targetClass' => Document::className(), 'targetAttribute' => ['document_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'document_id' => 'Document ID',
            'text' => 'Text',
        ];
    }

    /**
     * Gets query for [[InjunctionViolations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctionViolations()
    {
        return $this->hasMany(InjunctionViolation::className(), ['paragraph_pb_id' => 'id']);
    }

    /**
     * Gets query for [[Document]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDocument()
    {
        return $this->hasOne(Document::className(), ['id' => 'document_id']);
    }

    /**
     * Gets query for [[QuestionParagraphPbs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuestionParagraphPbs()
    {
        return $this->hasMany(QuestionParagraphPb::className(), ['paragraph_pb_id' => 'id']);
    }
}
