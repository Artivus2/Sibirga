<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "operation_worker".
 *
 * @property int $id
 * @property int $order_operation_id Операции назначеные на наряд
 * @property int $worker_id Идентификатор человека назначенного на эту операцию
 * @property int $role_id Роль человека на данной операции
 * @property string $date_time
 * @property int $status_id Статус
 * @property string $coordinate координаты
 * @property int $group_workers_unity группа
 * @property int $chane_id Звено в котором числится человек
 * @property int $brigade_id бригада в которой числится человек
 *
 * @property Worker $worker
 * @property Role $role
 * @property Status $status
 * @property OrderOperation $orderOperation
 * @property Brigade $brigade
 * @property Chane $chane
 * @property OrderOperationWorkerStatus[] $orderOperationWorkerStatuses
 */
class OperationWorker extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'operation_worker';
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
            [['order_operation_id', 'worker_id', 'role_id', 'date_time', 'status_id', 'chane_id', 'brigade_id'], 'required'],
            [['order_operation_id', 'worker_id', 'role_id', 'status_id', 'group_workers_unity', 'chane_id', 'brigade_id'], 'integer'],
            [['date_time'], 'safe'],
            [['coordinate'], 'string', 'max' => 50],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
            [['role_id'], 'exist', 'skipOnError' => true, 'targetClass' => Role::className(), 'targetAttribute' => ['role_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['order_operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderOperation::className(), 'targetAttribute' => ['order_operation_id' => 'id']],
            [['brigade_id'], 'exist', 'skipOnError' => true, 'targetClass' => Brigade::className(), 'targetAttribute' => ['brigade_id' => 'id']],
            [['chane_id'], 'exist', 'skipOnError' => true, 'targetClass' => Chane::className(), 'targetAttribute' => ['chane_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_operation_id' => 'Операции назначеные на наряд',
            'worker_id' => 'Идентификатор человека назначенного на эту операцию',
            'role_id' => 'Роль человека на данной операции',
            'date_time' => 'Date Time',
            'status_id' => 'Статус',
            'coordinate' => 'координаты',
            'group_workers_unity' => 'группа',
            'chane_id' => 'Звено в котором числится человек',
            'brigade_id' => 'бригада в которой числится человек',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRole()
    {
        return $this->hasOne(Role::className(), ['id' => 'role_id']);
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
    public function getOrderOperation()
    {
        return $this->hasOne(OrderOperation::className(), ['id' => 'order_operation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrigade()
    {
        return $this->hasOne(Brigade::className(), ['id' => 'brigade_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChane()
    {
        return $this->hasOne(Chane::className(), ['id' => 'chane_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderOperationWorkerStatuses()
    {
        return $this->hasMany(OrderOperationWorkerStatus::className(), ['operation_worker_id' => 'id']);
    }
}
