<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_item_worker".
 *
 * @property int $id
 * @property int $worker_id ключ работника
 * @property int $role_id ключ роли
 * @property int $order_history_id ключ истории сохранения наряда
 * @property string|null $worker_restriction_json ограничения по наряду работника
 * @property string|null $workers_json
 * @property int|null $reason_status_id ключ статуса работника по выходу из шахты 
 * @property string|null $reason_description Описание причины того, что работник остался на вторую смену
 *
 * @property OrderHistory $orderHistory
 * @property Role $role
 * @property Status $reasonStatus
 * @property Worker $worker
 */
class OrderItemWorker extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_item_worker';
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
            [['worker_id', 'role_id', 'order_history_id'], 'required'],
            [['worker_id', 'role_id', 'order_history_id', 'reason_status_id'], 'integer'],
            [['worker_restriction_json', 'workers_json'], 'safe'],
            [['reason_description'], 'string', 'max' => 255],
            [['worker_id', 'role_id', 'order_history_id'], 'unique', 'targetAttribute' => ['worker_id', 'role_id', 'order_history_id']],
            [['order_history_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderHistory::className(), 'targetAttribute' => ['order_history_id' => 'id']],
            [['role_id'], 'exist', 'skipOnError' => true, 'targetClass' => Role::className(), 'targetAttribute' => ['role_id' => 'id']],
            [['reason_status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['reason_status_id' => 'id']],
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
            'role_id' => 'Role ID',
            'order_history_id' => 'Order History ID',
            'worker_restriction_json' => 'Worker Restriction Json',
            'workers_json' => 'Workers Json',
            'reason_status_id' => 'Reason Status ID',
            'reason_description' => 'Reason Description',
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
     * Gets query for [[ReasonStatus]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getReasonStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'reason_status_id']);
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
