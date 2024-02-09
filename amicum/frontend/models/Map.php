<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "map".
 *
 * @property int $id ключ карты
 * @property string $title название карты
 * @property int|null $attachment_id ключ вложения
 * @property int $count_number количество пунктов на карте
 *
 * @property Attachment $attachment
 * @property TestMap[] $testMaps
 */
class Map extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'map';
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
            [['attachment_id', 'count_number'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ карты',
            'title' => 'название карты',
            'attachment_id' => 'ключ вложения',
            'count_number' => 'количество пунктов на карте',
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
     * Gets query for [[TestMaps]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTestMaps()
    {
        return $this->hasMany(TestMap::className(), ['map_id' => 'id']);
    }
}
