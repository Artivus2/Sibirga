<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "checking_gratitude_attachment".
 *
 * @property int $id
 * @property int $checking_gratitude_id
 * @property int $attachment_id
 *
 * @property Attachment $attachment
 * @property CheckingGratitude $checkingGratitude
 */
class CheckingGratitudeAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'checking_gratitude_attachment';
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
            [['checking_gratitude_id', 'attachment_id'], 'required'],
            [['checking_gratitude_id', 'attachment_id'], 'integer'],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
            [['checking_gratitude_id'], 'exist', 'skipOnError' => true, 'targetClass' => CheckingGratitude::className(), 'targetAttribute' => ['checking_gratitude_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'checking_gratitude_id' => 'Checking Gratitude ID',
            'attachment_id' => 'Attachment ID',
        ];
    }

    /**
     * Gets query for [[Attachment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAttachment()
    {
        return $this->hasOne(Attachment::className(), ['id' => 'attachment_id']);
    }

    /**
     * Gets query for [[CheckingGratitude]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCheckingGratitude()
    {
        return $this->hasOne(CheckingGratitude::className(), ['id' => 'checking_gratitude_id']);
    }
}
