<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "downtime".
 *
 * @property int $id
 * @property string $title
 *
 * @property DowntimeDependencyFace[] $downtimeDependencyFaces
 * @property Reason[] $reasons
 */
class Downtime extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'downtime';
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
            [['title'], 'unique'],
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
     * @return \yii\db\ActiveQuery
     */
    public function getDowntimeDependencyFaces()
    {
        return $this->hasMany(DowntimeDependencyFace::className(), ['downtime_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReasons()
    {
        return $this->hasMany(Reason::className(), ['downtime_id' => 'id']);
    }
}
