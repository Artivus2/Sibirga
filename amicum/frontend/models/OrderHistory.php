<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_history".
 *
 * @property int $id ключ истории
 * @property int $order_id ключ главного наряда
 * @property string $date_time_create дата сохранения наряда
 * @property int $worker_id ключ сохранившего наряд
 * @property int $status_id ключ статуса наряда
 *
 * @property Order $order
 * @property Worker $worker
 * @property Status $status
 * @property OrderItem[] $orderItems
 * @property OrderItemEquipment[] $orderItemEquipments
 * @property OrderItemInjunction[] $orderItemInjunctions
 * @property OrderItemInstructionPb[] $orderItemInstructionPbs
 * @property InstructionPb[] $instructionPbs
 * @property OrderItemWorker[] $orderItemWorkers
 * @property OrderItemWorkerInstructionPb[] $orderItemWorkerInstructionPbs
 * @property OrderItemWorkerVgk[] $orderItemWorkerVgks
 */
class OrderHistory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_history';
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
            [['order_id', 'date_time_create', 'worker_id', 'status_id'], 'required'],
            [['order_id', 'worker_id', 'status_id'], 'integer'],
            [['date_time_create'], 'safe'],
            [['order_id', 'date_time_create', 'worker_id', 'status_id'], 'unique', 'targetAttribute' => ['order_id', 'date_time_create', 'worker_id', 'status_id']],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Order::className(), 'targetAttribute' => ['order_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
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
            'date_time_create' => 'Date Time Create',
            'worker_id' => 'Worker ID',
            'status_id' => 'Status ID',
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
     * Gets query for [[Worker]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
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
     * Gets query for [[OrderItems]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderItems()
    {
        return $this->hasMany(OrderItem::className(), ['order_history_id' => 'id']);
    }

    /**
     * Gets query for [[OrderItemEquipments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderItemEquipments()
    {
        return $this->hasMany(OrderItemEquipment::className(), ['order_history_id' => 'id']);
    }

    /**
     * Gets query for [[OrderItemInjunctions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderItemInjunctions()
    {
        return $this->hasMany(OrderItemInjunction::className(), ['order_history_id' => 'id']);
    }

    /**
     * Gets query for [[OrderItemInstructionPbs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderItemInstructionPbs()
    {
        return $this->hasMany(OrderItemInstructionPb::className(), ['order_history_id' => 'id']);
    }

    /**
     * Gets query for [[InstructionPbs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInstructionPbs()
    {
        return $this->hasMany(InstructionPb::className(), ['id' => 'instruction_pb_id'])->viaTable('order_item_instruction_pb', ['order_history_id' => 'id']);
    }

    /**
     * Gets query for [[OrderItemWorkers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderItemWorkers()
    {
        return $this->hasMany(OrderItemWorker::className(), ['order_history_id' => 'id']);
    }

    /**
     * Gets query for [[OrderItemWorkerInstructionPbs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderItemWorkerInstructionPbs()
    {
        return $this->hasMany(OrderItemWorkerInstructionPb::className(), ['order_history_id' => 'id']);
    }

    /**
     * Gets query for [[OrderItemWorkerVgks]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderItemWorkerVgks()
    {
        return $this->hasMany(OrderItemWorkerVgk::className(), ['order_history_id' => 'id']);
    }
}
