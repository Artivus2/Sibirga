<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "activity_regulation".
 *
 * @property int $id
 * @property int $activity_id
 * @property int $regulation_id
 * @property string $duration
 *
 * @property Activity $activity
 * @property Regulation $regulation
 */
class ActivityRegulation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'activity_regulation';
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
            [['activity_id', 'regulation_id', 'duration'], 'required'],
            [['activity_id', 'regulation_id'], 'integer'],
            [['duration'], 'safe'],
            [['activity_id'], 'exist', 'skipOnError' => true, 'targetClass' => Activity::className(), 'targetAttribute' => ['activity_id' => 'id']],
            [['regulation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Regulation::className(), 'targetAttribute' => ['regulation_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'activity_id' => 'Activity ID',
            'regulation_id' => 'Regulation ID',
            'duration' => 'Duration',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActivity()
    {
        return $this->hasOne(Activity::className(), ['id' => 'activity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRegulation()
    {
        return $this->hasOne(Regulation::className(), ['id' => 'regulation_id']);
    }
}
