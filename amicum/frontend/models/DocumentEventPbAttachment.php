<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "document_event_pb_attachment".
 *
 * @property int $id
 * @property int $document_event_pb_id
 * @property int $attachment_id
 *
 * @property Attachment $attachment
 * @property DocumentEventPb $documentEventPb
 */
class DocumentEventPbAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'document_event_pb_attachment';
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
            [['document_event_pb_id', 'attachment_id'], 'required'],
            [['document_event_pb_id', 'attachment_id'], 'integer'],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
            [['document_event_pb_id'], 'exist', 'skipOnError' => true, 'targetClass' => DocumentEventPb::className(), 'targetAttribute' => ['document_event_pb_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'document_event_pb_id' => 'Document Event Pb ID',
            'attachment_id' => 'Attachment ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAttachment()
    {
        return $this->hasOne(Attachment::className(), ['id' => 'attachment_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDocumentEventPb()
    {
        return $this->hasOne(DocumentEventPb::className(), ['id' => 'document_event_pb_id']);
    }
}
