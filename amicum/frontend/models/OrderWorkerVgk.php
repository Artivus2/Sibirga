<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_worker_vgk".
 *
 * @property int $id
 * @property int $order_id внешний идентификатор наряда
 * @property int $worker_id внешний идентификатор работника ВГК
 * @property int $role_id ключ роли
 * @property int $vgk Принадлежность к ВГК
 *
 * @property Order $order
 * @property Worker $worker
 */
class OrderWorkerVgk extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_worker_vgk';
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
            [['order_id', 'worker_id', 'role_id', 'vgk'], 'required'],
            [['order_id', 'worker_id', 'role_id', 'vgk'], 'integer'],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Order::className(), 'targetAttribute' => ['order_id' => 'id']],
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
            'order_id' => 'внешний идентификатор наряда',
            'worker_id' => 'внешний идентификатор работника ВГК',
            'role_id' => 'ключ роли',
            'vgk' => 'Принадлежность к ВГК',
        ];
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
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id'])->alias('workerVgk');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRole()
    {
        return $this->hasOne(Role::className(), ['id' => 'role_id']);
    }
}
