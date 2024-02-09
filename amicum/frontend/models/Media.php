<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "media".
 *
 * @property int $id
 * @property int $media_group_id
 * @property int $attachment_id
 *
 * @property MediaGroup $mediaGroup
 * @property Attachment $attachment
 * @property MediaMediaTheme[] $mediaMediaThemes
 * @property QuestionMedia[] $questionMedia
 */
class Media extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'media';
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
            [['media_group_id', 'attachment_id'], 'required'],
            [['media_group_id', 'attachment_id'], 'integer'],
            [['media_group_id'], 'exist', 'skipOnError' => true, 'targetClass' => MediaGroup::className(), 'targetAttribute' => ['media_group_id' => 'id']],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'media_group_id' => 'Media Group ID',
            'attachment_id' => 'Attachment ID',
        ];
    }

    /**
     * Gets query for [[MediaGroup]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMediaGroup()
    {
        return $this->hasOne(MediaGroup::className(), ['id' => 'media_group_id']);
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
     * Gets query for [[MediaMediaThemes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMediaMediaThemes()
    {
        return $this->hasMany(MediaMediaTheme::className(), ['media_id' => 'id']);
    }

    /**
     * Gets query for [[QuestionMedia]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuestionMedia()
    {
        return $this->hasMany(QuestionMedia::className(), ['media_id' => 'id']);
    }
}
