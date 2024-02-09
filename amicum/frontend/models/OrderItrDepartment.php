<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_itr_department".
 *
 * @property int $id ключ таблицы Наряд ИТР - 
 * @property int $order_id внешний ключ списка нарядов
 * @property int $worker_object_id внешний ключ работника 
 *
 * @property Order $order
 * @property WorkerObject $workerObject
 */
class OrderItrDepartment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_itr_department';
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
            [['id', 'order_id', 'worker_object_id'], 'required'],
            [['id', 'order_id', 'worker_object_id'], 'integer'],
            [['id'], 'unique'],
            [['order_id', 'worker_object_id'], 'unique', 'targetAttribute' => ['order_id', 'worker_object_id']],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Order::className(), 'targetAttribute' => ['order_id' => 'id']],
            [['worker_object_id'], 'exist', 'skipOnError' => true, 'targetClass' => WorkerObject::className(), 'targetAttribute' => ['worker_object_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ таблицы Наряд ИТР - ',
            'order_id' => 'внешний ключ списка нарядов',
            'worker_object_id' => 'внешний ключ работника ',
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
    public function getWorkerObject()
    {
        return $this->hasOne(WorkerObject::className(), ['id' => 'worker_object_id']);
    }
}
