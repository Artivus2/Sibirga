<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_item_worker_vgk".
 *
 * @property int $id
 * @property int $order_history_id ключ истории наряда
 * @property int $worker_id внешний идентификатор работника ВГК
 * @property int $role_id ключ роли
 * @property int $vgk Принадлежность к ВГК
 *
 * @property OrderHistory $orderHistory
 * @property Role $role
 * @property Worker $worker
 */
class OrderItemWorkerVgk extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_item_worker_vgk';
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
            [['order_history_id', 'worker_id', 'role_id', 'vgk'], 'required'],
            [['order_history_id', 'worker_id', 'role_id', 'vgk'], 'integer'],
            [['order_history_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderHistory::className(), 'targetAttribute' => ['order_history_id' => 'id']],
            [['role_id'], 'exist', 'skipOnError' => true, 'targetClass' => Role::className(), 'targetAttribute' => ['role_id' => 'id']],
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
            'order_history_id' => 'Order History ID',
            'worker_id' => 'Worker ID',
            'role_id' => 'Role ID',
            'vgk' => 'Vgk',
        ];
    }

    /**
     * Gets query for [[OrderHistory]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderHistory()
    {
        return $this->hasOne(OrderHistory::className(), ['id' => 'order_history_id']);
    }

    /**
     * Gets query for [[Role]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRole()
    {
        return $this->hasOne(Role::className(), ['id' => 'role_id']);
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
