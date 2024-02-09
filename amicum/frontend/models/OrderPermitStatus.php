<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_permit_status".
 *
 * @property int $id Ключ таблицы статуса наряда
 * @property int $order_permit_id внешний ключ списка нарядов
 * @property int $status_id внешний ключ справочника статусов
 * @property int $worker_id ключ работника изменившего статуса
 * @property string $date_time_create дата изменения статуса
 * @property string $description Причина смены статуса
 *
 * @property OrderPermit $orderPermit
 * @property Status $status
 * @property Worker $worker
 */
class OrderPermitStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_permit_status';
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
            [['order_permit_id', 'status_id', 'worker_id', 'date_time_create', 'description'], 'required'],
            [['order_permit_id', 'status_id', 'worker_id'], 'integer'],
            [['date_time_create'], 'safe'],
            [['description'], 'string', 'max' => 255],
            [['order_permit_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderPermit::className(), 'targetAttribute' => ['order_permit_id' => 'id']],
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
            'order_permit_id' => 'Order Permit ID',
            'status_id' => 'Status ID',
            'worker_id' => 'Worker ID',
            'date_time_create' => 'Date Time Create',
            'description' => 'Description',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPermit()
    {
        return $this->hasOne(OrderPermit::className(), ['id' => 'order_permit_id']);
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
