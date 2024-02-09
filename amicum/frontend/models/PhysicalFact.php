<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "physical_fact".
 *
 * @property int $id
 * @property int $worker_id Ключ врача, кто проводил осмотр
 * @property string $date Дата выдачи заключения
 *
 * @property MedReport[] $medReports
 * @property Worker $worker
 */
class PhysicalFact extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'physical_fact';
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
            [['worker_id', 'date'], 'required'],
            [['worker_id'], 'integer'],
            [['date'], 'safe'],
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
            'worker_id' => 'Ключ врача, кто проводил осмотр',
            'date' => 'Дата выдачи заключения',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMedReports()
    {
        return $this->hasMany(MedReport::className(), ['physical_fact_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
