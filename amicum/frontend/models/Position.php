<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "position".
 *
 * @property int $id ключ справочника должностей
 * @property string $title название должности
 * @property string|null $qualification
 * @property string|null $short_title
 * @property string date_time_sync
 * @property string link_1c
 *
 * @property Briefer[] $briefers
 * @property EventPbWorker[] $eventPbWorkers
 * @property MedReport[] $medReports
 * @property OccupationalIllness[] $occupationalIllnesses
 * @property Worker[] $workers
 */
class Position extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'position';
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
            [['date_time_sync'], 'safe'],
            [['link_1c'], 'string', 'max' => 100],
            [['title'], 'string', 'max' => 255],
            [['qualification'], 'string', 'max' => 10],
            [['short_title'], 'string', 'max' => 60],
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
            'qualification' => 'Qualification',
            'short_title' => 'Short Title',
            'link_1c' => 'link_1c',
            'date_time_sync' => 'date_time_sync',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBriefers()
    {
        return $this->hasMany(Briefer::className(), ['position_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventPbWorkers()
    {
        return $this->hasMany(EventPbWorker::className(), ['position_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMedReports()
    {
        return $this->hasMany(MedReport::className(), ['position_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOccupationalIllnesses()
    {
        return $this->hasMany(OccupationalIllness::className(), ['position_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkers()
    {
        return $this->hasMany(Worker::className(), ['position_id' => 'id']);
    }
}
