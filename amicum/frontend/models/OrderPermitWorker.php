<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_permit_worker".
 *
 * @property int $id
 * @property int $order_permit_id ключ наряд допуска
 * @property int $worker_id ключ вложения
 *
 * @property OrderPermit $orderPermit
 * @property Worker $worker
 */
class OrderPermitWorker extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_permit_worker';
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
            [['order_permit_id', 'worker_id'], 'required'],
            [['order_permit_id', 'worker_id'], 'integer'],
            [['order_permit_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderPermit::className(), 'targetAttribute' => ['order_permit_id' => 'id']],
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
            'worker_id' => 'Worker ID',
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
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
