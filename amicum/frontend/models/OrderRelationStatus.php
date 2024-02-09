<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_relation_status".
 *
 * @property int $id
 * @property int $order_relation_id
 * @property int $status_id
 * @property int $worker_id
 * @property string $date_time
 *
 * @property OrderRelation $orderRelation
 * @property Status $status
 * @property Worker $worker
 */
class OrderRelationStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_relation_status';
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
            [['order_relation_id', 'status_id', 'worker_id', 'date_time'], 'required'],
            [['order_relation_id', 'status_id', 'worker_id'], 'integer'],
            [['date_time'], 'safe'],
            [['order_relation_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderRelation::className(), 'targetAttribute' => ['order_relation_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
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
            'order_relation_id' => 'Order Relation ID',
            'status_id' => 'Status ID',
            'worker_id' => 'Worker ID',
            'date_time' => 'Date Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderRelation()
    {
        return $this->hasOne(OrderRelation::className(), ['id' => 'order_relation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
