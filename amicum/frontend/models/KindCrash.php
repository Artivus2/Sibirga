<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "kind_crash".
 *
 * @property int $id навзвание вида аварии
 * @property string $title
 *
 * @property EventPb[] $eventPbs
 */
class KindCrash extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'kind_crash';
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
            [['title'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'навзвание вида аварии',
            'title' => 'Title',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventPbs()
    {
        return $this->hasMany(EventPb::className(), ['kind_crash_id' => 'id']);
    }
}
