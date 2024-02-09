<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "inquiry_attachment".
 *
 * @property int $id
 * @property int $inquiry_id
 * @property int $attachment_id
 *
 * @property Attachment $attachment
 * @property InquiryPb $inquiry
 */
class InquiryAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'inquiry_attachment';
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
            [['inquiry_id', 'attachment_id'], 'required'],
            [['inquiry_id', 'attachment_id'], 'integer'],
            [['inquiry_id', 'attachment_id'], 'unique', 'targetAttribute' => ['inquiry_id', 'attachment_id']],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
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
            'attachment_id' => 'Attachment ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAttachment()
    {
        return $this->hasOne(Attachment::className(), ['id' => 'attachment_id'])->alias('attachment4');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInquiry()
    {
        return $this->hasOne(InquiryPb::className(), ['id' => 'inquiry_id']);
    }
}
