<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "worker_card".
 *
 * @property int $id Идентификатор таблицы
 * @property int $worker_id Внешний идентифкатор работника
 * @property string $card_number Номер карты
 * @property string $date_time_sync
 *
 * @property Worker $worker
 */
class WorkerCard extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_card';
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
            [['worker_id', 'card_number'], 'required'],
            [['worker_id'], 'integer'],
            [['date_time_sync'], 'safe'],
            [['card_number'], 'string', 'max' => 255],
            [['worker_id', 'card_number'], 'unique', 'targetAttribute' => ['worker_id', 'card_number']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор таблицы',
            'worker_id' => 'Внешний идентифкатор работника',
            'card_number' => 'Номер карты',
            'date_time_sync' => 'Date Time Sync',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
