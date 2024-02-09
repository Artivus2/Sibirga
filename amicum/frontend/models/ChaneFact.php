<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "chane_fact".
 *
 * @property int $id
 * @property int $order_by_chane_id
 * @property int $worker_id
 *
 * @property OrderByChane $orderByChane
 * @property Worker $worker
 */
class ChaneFact extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chane_fact';
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
            [['order_by_chane_id', 'worker_id'], 'required'],
            [['order_by_chane_id', 'worker_id'], 'integer'],
            [['order_by_chane_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderByChane::className(), 'targetAttribute' => ['order_by_chane_id' => 'id']],
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
            'order_by_chane_id' => 'Order By Chane ID',
            'worker_id' => 'Worker ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByChane()
    {
        return $this->hasOne(OrderByChane::className(), ['id' => 'order_by_chane_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
