<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "document_physical_attachment".
 *
 * @property int $id
 * @property int $document_physical_id Внешний идентификатор документа (приказа) по медосмотру
 * @property int $attachment_id Внешний идентификатор вложения
 *
 * @property Attachment $attachment
 * @property DocumentPhysical $documentPhysical
 */
class DocumentPhysicalAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'document_physical_attachment';
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
            [['document_physical_id', 'attachment_id'], 'required'],
            [['document_physical_id', 'attachment_id'], 'integer'],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
            [['document_physical_id'], 'exist', 'skipOnError' => true, 'targetClass' => DocumentPhysical::className(), 'targetAttribute' => ['document_physical_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'document_physical_id' => 'Внешний идентификатор документа (приказа) по медосмотру',
            'attachment_id' => 'Внешний идентификатор вложения',
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
    public function getDocumentPhysical()
    {
        return $this->hasOne(DocumentPhysical::className(), ['id' => 'document_physical_id']);
    }
}
