<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "checking_gratitude_worker".
 *
 * @property int $id
 * @property int $checking_gratitude_id
 * @property int $worker_id Внешний ключ работника ответсвтенного за нарушение
 *
 * @property CheckingGratitude $checkingGratitude
 * @property Worker $worker
 */
class CheckingGratitudeWorker extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'checking_gratitude_worker';
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
            [['checking_gratitude_id', 'worker_id'], 'required'],
            [['checking_gratitude_id', 'worker_id'], 'integer'],
            [['checking_gratitude_id'], 'exist', 'skipOnError' => true, 'targetClass' => CheckingGratitude::className(), 'targetAttribute' => ['checking_gratitude_id' => 'id']],
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
            'checking_gratitude_id' => 'Checking Gratitude ID',
            'worker_id' => 'Worker ID',
        ];
    }

    /**
     * Gets query for [[CheckingGratitude]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCheckingGratitude()
    {
        return $this->hasOne(CheckingGratitude::className(), ['id' => 'checking_gratitude_id']);
    }

    /**
     * Gets query for [[Worker]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
