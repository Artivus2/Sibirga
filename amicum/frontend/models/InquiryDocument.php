<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "inquiry_document".
 *
 * @property int $id
 * @property int $inquiry_id
 * @property int $document_event_pb_id
 *
 * @property DocumentEventPb $documentEventPb
 * @property InquiryPb $inquiry
 */
class InquiryDocument extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'inquiry_document';
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
            [['inquiry_id', 'document_event_pb_id'], 'required'],
            [['inquiry_id', 'document_event_pb_id'], 'integer'],
            [['inquiry_id', 'document_event_pb_id'], 'unique', 'targetAttribute' => ['inquiry_id', 'document_event_pb_id']],
            [['document_event_pb_id'], 'exist', 'skipOnError' => true, 'targetClass' => DocumentEventPb::className(), 'targetAttribute' => ['document_event_pb_id' => 'id']],
            [['inquiry_id'], 'exist', 'skipOnError' => true, 'targetClass' => InquiryPb::className(), 'targetAttribute' => ['inquiry_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'inquiry_id' => 'Inquiry ID',
            'document_event_pb_id' => 'Document Event Pb ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDocumentEventPb()
    {
        return $this->hasOne(DocumentEventPb::className(), ['id' => 'document_event_pb_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInquiry()
    {
        return $this->hasOne(InquiryPb::className(), ['id' => 'inquiry_id']);
    }
}
