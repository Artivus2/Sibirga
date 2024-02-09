<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "timetable_pb".
 *
 * @property int $id
 * @property string $title
 * @property string $year
 * @property string $month
 *
 * @property TimetableInstructionPb[] $timetableInstructionPbs
 */
class TimetablePb extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'timetable_pb';
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
            [['title', 'year', 'month'], 'required'],
            [['year', 'month'], 'safe'],
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
            'year' => 'Year',
            'month' => 'Month',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTimetableInstructionPbs()
    {
        return $this->hasMany(TimetableInstructionPb::className(), ['timetable_pb_id' => 'id']);
    }
}
