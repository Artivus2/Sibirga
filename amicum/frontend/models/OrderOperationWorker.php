<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_operation_worker".
 *
 * @property int $id Ключ таблицы плановых объемов работ по наряду
 * @property int $order_place_id внешний ключ привязки наряда к месту
 * @property int $operation_id внешний ключ справочника операций
 * @property int $worker_id Идентификатор человека назначенного на эту операцию
 * @property string $date_time дата и время выполнения работы
 * @property string $operation_value_plan Плановое значение объема работы, которго должен выполнить работник
 * @property string $operation_value_fact Фактическоезначение объема работы, которго должен выполнить работник
 * @property int $status_id Внешний ключ спарвчоника статусов операции
 * @property string $coordinate Координаты где находиться операция человека 
 * @property int $group_workers_unity Поле используется для группировки людей в звенья
 * @property int $role_id Роль работника на операции
 *
 * @property Role $role
 * @property Status $status
 * @property Operation $operation
 * @property Worker $worker
 * @property OrderPlace $orderPlace
 * @property OrderOperationWorkerStatus[] $orderOperationWorkerStatuses
 */
class OrderOperationWorker extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_operation_worker';
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
            [['order_place_id', 'operation_id', 'worker_id', 'date_time', 'status_id', 'role_id'], 'required'],
            [['order_place_id', 'operation_id', 'worker_id', 'status_id', 'group_workers_unity', 'role_id'], 'integer'],
            [['date_time'], 'safe'],
            [['operation_value_plan', 'operation_value_fact'], 'string', 'max' => 45],
            [['coordinate'], 'string', 'max' => 50],
            [['order_place_id', 'operation_id', 'worker_id', 'date_time'], 'unique', 'targetAttribute' => ['order_place_id', 'operation_id', 'worker_id', 'date_time']],
            [['role_id'], 'exist', 'skipOnError' => true, 'targetClass' => Role::className(), 'targetAttribute' => ['role_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['operation_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
            [['order_place_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderPlace::className(), 'targetAttribute' => ['order_place_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Ключ таблицы плановых объемов работ по наряду',
            'order_place_id' => 'внешний ключ привязки наряда к месту',
            'operation_id' => 'внешний ключ справочника операций',
            'worker_id' => 'Идентификатор человека назначенного на эту операцию',
            'date_time' => 'дата и время выполнения работы',
            'operation_value_plan' => 'Плановое значение объема работы, которго должен выполнить работник',
            'operation_value_fact' => 'Фактическоезначение объема работы, которго должен выполнить работник',
            'status_id' => 'Внешний ключ спарвчоника статусов операции',
            'coordinate' => 'Координаты где находиться операция человека ',
            'group_workers_unity' => 'Поле используется для группировки людей в звенья',
            'role_id' => 'Роль работника на операции',
        ];
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
    public function getOperation()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_id']);
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
    public function getOrderPlace()
    {
        return $this->hasOne(OrderPlace::className(), ['id' => 'order_place_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderOperationWorkerStatuses()
    {
        return $this->hasMany(OrderOperationWorkerStatus::className(), ['order_operation_worker_id' => 'id']);
    }
}
