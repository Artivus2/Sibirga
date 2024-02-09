<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "fact_tabel_worker".
 *
 * @property int $id
 * @property string $date
 * @property int $tabel_id
 * @property int $worker_id
 *
 * @property Worker $worker
 * @property Tabel $tabel
 */
class FactTabelWorker extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fact_tabel_worker';
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
            [['date', 'tabel_id', 'worker_id'], 'required'],
            [['date'], 'safe'],
            [['tabel_id', 'worker_id'], 'integer'],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
            [['tabel_id'], 'exist', 'skipOnError' => true, 'targetClass' => Tabel::className(), 'targetAttribute' => ['tabel_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date' => 'Date',
            'tabel_id' => 'Tabel ID',
            'worker_id' => 'Worker ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTabel()
    {
        return $this->hasOne(Tabel::className(), ['id' => 'tabel_id']);
    }
}
