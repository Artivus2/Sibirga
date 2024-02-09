<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "media_media_theme".
 *
 * @property int $id
 * @property int $media_id
 * @property int $media_theme_id
 *
 * @property MediaTheme $mediaTheme
 * @property Media $media
 */
class MediaMediaTheme extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'media_media_theme';
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
            [['media_id', 'media_theme_id'], 'required'],
            [['media_id', 'media_theme_id'], 'integer'],
            [['media_theme_id'], 'exist', 'skipOnError' => true, 'targetClass' => MediaTheme::className(), 'targetAttribute' => ['media_theme_id' => 'id']],
            [['media_id'], 'exist', 'skipOnError' => true, 'targetClass' => Media::className(), 'targetAttribute' => ['media_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'media_id' => 'Media ID',
            'media_theme_id' => 'Media Theme ID',
        ];
    }

    /**
     * Gets query for [[MediaTheme]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMediaTheme()
    {
        return $this->hasOne(MediaTheme::className(), ['id' => 'media_theme_id']);
    }

    /**
     * Gets query for [[Media]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMedia()
    {
        return $this->hasOne(Media::className(), ['id' => 'media_id']);
    }
}
