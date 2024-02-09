<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_item_status".
 *
 * @property int $id Ключ таблицы статуса наряда
 * @property int $order_item_id внешний ключ атомарного наряда
 * @property int $status_id внешний ключ справочника статусов
 * @property int $worker_id
 * @property string $date_time_create
 * @property string|null $description Причина смены статуса
 *
 * @property Status $status
 * @property Worker $worker
 * @property OrderItem $orderItem
 */
class OrderItemStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_item_status';
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
            [['order_item_id', 'status_id', 'worker_id', 'date_time_create'], 'required'],
            [['order_item_id', 'status_id', 'worker_id'], 'integer'],
            [['date_time_create'], 'safe'],
            [['description'], 'string', 'max' => 255],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
            [['order_item_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderItem::className(), 'targetAttribute' => ['order_item_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_item_id' => 'Order Item ID',
            'status_id' => 'Status ID',
            'worker_id' => 'Worker ID',
            'date_time_create' => 'Date Time Create',
            'description' => 'Description',
        ];
    }

    /**
     * Gets query for [[Status]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
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

    /**
     * Gets query for [[OrderItem]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderItem()
    {
        return $this->hasOne(OrderItem::className(), ['id' => 'order_item_id']);
    }
}
