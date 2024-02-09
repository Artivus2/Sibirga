<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "worker_object".
 *
 * @property int $id Ключ таблицы классификации работников по типовым объектам АМИКУМ
 * @property int $worker_id внешний ключ работника
 * @property int $object_id внешний ключ типового объекта
 * @property int $role_id внешник ключ справочника ролей
 *
 * @property CheckingWorkerType[] $checkingWorkerTypes
 * @property OrderItrDepartment[] $orderItrDepartments
 * @property Order[] $orders
 * @property OrderOperationWorkerFact[] $orderOperationWorkerFacts
 * @property OrderOperationWorkerPlan[] $orderOperationWorkerPlans
 * @property OrderStatus[] $orderStatuses
 * @property TimetableStatus[] $timetableStatuses
 * @property TimetableTabel[] $timetableTabels
 * @property WorkerFunction[] $workerFunctions
 * @property Object $object
 * @property Role $role
 * @property Worker $worker
 * @property WorkerObjectRole[] $workerObjectRoles
 * @property Role[] $roles
 * @property WorkerParameter[] $workerParameters
 */
class WorkerObject extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_object';
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
            [['worker_id', 'object_id'], 'required'],
            [['worker_id', 'object_id', 'role_id'], 'integer'],
            [['worker_id', 'object_id'], 'unique', 'targetAttribute' => ['worker_id', 'object_id']],
            [['object_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypicalObject::className(), 'targetAttribute' => ['object_id' => 'id']],
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
            'worker_id' => 'Worker ID',
            'object_id' => 'Object ID',
            'role_id' => 'Role ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCheckingWorkerTypes()
    {
        return $this->hasMany(CheckingWorkerType::className(), ['worker_object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderItrDepartments()
    {
        return $this->hasMany(OrderItrDepartment::className(), ['worker_object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Order::className(), ['id' => 'order_id'])->viaTable('order_itr_department', ['worker_object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderOperationWorkerFacts()
    {
        return $this->hasMany(OrderOperationWorkerFact::className(), ['worker_object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderOperationWorkerPlans()
    {
        return $this->hasMany(OrderOperationWorkerPlan::className(), ['worker_object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderStatuses()
    {
        return $this->hasMany(OrderStatus::className(), ['worker_object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTimetableStatuses()
    {
        return $this->hasMany(TimetableStatus::className(), ['worker_object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTimetableTabels()
    {
        return $this->hasMany(TimetableTabel::className(), ['worker_object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerFunctions()
    {
        return $this->hasMany(WorkerFunction::className(), ['worker_object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getObject()
    {
        return $this->hasOne(TypicalObject::className(), ['id' => 'object_id']);
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
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerObjectRoles()
    {
        return $this->hasMany(WorkerObjectRole::className(), ['worker_object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRoles()
    {
        return $this->hasMany(Role::className(), ['id' => 'role_id'])->viaTable('worker_object_role', ['worker_object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerParameters()
    {
        return $this->hasMany(WorkerParameter::className(), ['worker_object_id' => 'id']);
    }
}
