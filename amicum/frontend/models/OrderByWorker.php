<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_by_worker".
 *
 * @property int $id
 * @property int $worker_id
 * @property int $order_kind_id
 * @property int $order_id
 * @property int $place_id
 *
 * @property OperationByWorker[] $operationByWorkers
 * @property OrderByChaneByWorker[] $orderByChaneByWorkers
 * @property Order $order
 * @property OrderKind $orderKind
 * @property Place $place
 * @property Worker $worker
 */
class OrderByWorker extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_by_worker';
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
            [['worker_id', 'order_kind_id', 'order_id', 'place_id'], 'required'],
            [['worker_id', 'order_kind_id', 'order_id', 'place_id'], 'integer'],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Order::className(), 'targetAttribute' => ['order_id' => 'id']],
            [['order_kind_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderKind::className(), 'targetAttribute' => ['order_kind_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
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
            'worker_id' => 'Worker ID',
            'order_kind_id' => 'Order Kind ID',
            'order_id' => 'Order ID',
            'place_id' => 'Place ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationByWorkers()
    {
        return $this->hasMany(OperationByWorker::className(), ['order_by_worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByChaneByWorkers()
    {
        return $this->hasMany(OrderByChaneByWorker::className(), ['order_by_worker_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::className(), ['id' => 'order_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderKind()
    {
        return $this->hasOne(OrderKind::className(), ['id' => 'order_kind_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
