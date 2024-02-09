<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "asmtp".
 *
 * @property int $id
 * @property string $title
 *
 * @property Sensor[] $sensors
 * @property Trigger[] $triggers
 */
class Asmtp extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'asmtp';
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
    public function getSensors()
    {
        return $this->hasMany(Sensor::className(), ['asmtp_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTriggers()
    {
        return $this->hasMany(Trigger::className(), ['asmtp_id' => 'id']);
    }
}
