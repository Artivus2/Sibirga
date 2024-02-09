<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "outcome".
 *
 * @property int $id
 * @property string $title
 *
 * @property EventPbWorker[] $eventPbWorkers
 */
class Outcome extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'outcome';
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
     * @return \yii\db\ActiveQuery
     */
    public function getEventPbWorkers()
    {
        return $this->hasMany(EventPbWorker::className(), ['outcome_id' => 'id']);
    }
}
