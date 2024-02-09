<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "instructor".
 *
 * @property int $id
 * @property int $worker_id ключ сотрудника, кто имеет право проводить инструктаж
 * @property string $date_time дата назначение данного сотрудника инструктором. 
 *
 * @property Briefing[] $briefings
 * @property Worker $worker
 */
class Instructor extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'instructor';
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
            [['worker_id'], 'required'],
            [['worker_id'], 'integer'],
            [['date_time'], 'safe'],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'worker_id' => 'ключ сотрудника, кто имеет право проводить инструктаж',
            'date_time' => 'дата назначение данного сотрудника инструктором. ',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBriefings()
    {
        return $this->hasMany(Briefing::className(), ['instructor_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
