<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "worker_siz_status1".
 *
 * @property int $id
 * @property int $worker_siz_id
 * @property string $date
 * @property string $comment
 * @property int $percentage_wear
 * @property int $status_id
 */
class WorkerSizStatus1 extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_siz_status1';
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
            [['worker_siz_id', 'date', 'status_id'], 'required'],
            [['worker_siz_id', 'percentage_wear', 'status_id'], 'integer'],
            [['date'], 'safe'],
            [['comment'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'worker_siz_id' => 'Worker Siz ID',
            'date' => 'Date',
            'comment' => 'Comment',
            'percentage_wear' => 'Percentage Wear',
            'status_id' => 'Status ID',
        ];
    }
}
