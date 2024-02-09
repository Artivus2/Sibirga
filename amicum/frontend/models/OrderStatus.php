<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_status".
 *
 * @property int $id Ключ таблицы статуса наряда
 * @property int $order_id внешний ключ списка нарядов
 * @property int $status_id внешний ключ справочника статусов
 * @property int $worker_id
 * @property string $date_time_create
 * @property string|null $description Причина смены статуса
 *
 * @property Order $order
 * @property Status $status
 * @property Worker $worker
 * @property OrderStatusAttachment[] $orderStatusAttachments
 */
class OrderStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_status';
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
            [['order_id', 'status_id', 'worker_id', 'date_time_create'], 'required'],
            [['order_id', 'status_id', 'worker_id'], 'integer'],
            [['date_time_create'], 'safe'],
            [['description'], 'string', 'max' => 255],
            [['order_id', 'status_id', 'worker_id', 'date_time_create'], 'unique', 'targetAttribute' => ['order_id', 'status_id', 'worker_id', 'date_time_create']],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Order::className(), 'targetAttribute' => ['order_id' => 'id']],
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
            'order_id' => 'Order ID',
            'status_id' => 'Status ID',
            'worker_id' => 'Worker ID',
            'date_time_create' => 'Date Time Create',
            'description' => 'Description',
        ];
    }

    /**
     * Gets query for [[Order]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::className(), ['id' => 'order_id']);
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
     * Gets query for [[OrderStatusAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderStatusAttachments()
    {
        return $this->hasMany(OrderStatusAttachment::className(), ['order_status_id' => 'id']);
    }
}
