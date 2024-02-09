<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "media_theme".
 *
 * @property int $id
 * @property string $title
 *
 * @property MediaMediaTheme[] $mediaMediaThemes
 */
class MediaTheme extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'media_theme';
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
            [['title'], 'required'],
            [['title'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
        ];
    }

    /**
     * Gets query for [[MediaMediaThemes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMediaMediaThemes()
    {
        return $this->hasMany(MediaMediaTheme::className(), ['media_theme_id' => 'id']);
    }
}
