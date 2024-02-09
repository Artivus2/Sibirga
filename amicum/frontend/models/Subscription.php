<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "subscription".
 *
 * @property int $id
 * @property string $title
 * @property string $tag_group
 *
 * @property SensorConnectString[] $sensorConnectStrings
 */
class Subscription extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'subscription';
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
            [['title', 'tag_group'], 'string', 'max' => 255],
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
            'tag_group' => 'Tag Group',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensorConnectStrings()
    {
        return $this->hasMany(SensorConnectString::className(), ['subscription_id' => 'id']);
    }
}
