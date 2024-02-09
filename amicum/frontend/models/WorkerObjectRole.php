<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "worker_object_role".
 *
 * @property int $id Идентификатор таблицы 
 * @property int $worker_object_id Внешний ключ типизированного работника
 * @property int $role_id Внейшний ключ роли
 *
 * @property WorkerObject $workerObject
 * @property Role $role
 */
class WorkerObjectRole extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_object_role';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['worker_object_id', 'role_id'], 'required'],
            [['worker_object_id', 'role_id'], 'integer'],
            [['worker_object_id', 'role_id'], 'unique', 'targetAttribute' => ['worker_object_id', 'role_id']],
            [['worker_object_id'], 'exist', 'skipOnError' => true, 'targetClass' => WorkerObject::className(), 'targetAttribute' => ['worker_object_id' => 'id']],
            [['role_id'], 'exist', 'skipOnError' => true, 'targetClass' => Role::className(), 'targetAttribute' => ['role_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'worker_object_id' => 'Worker Object ID',
            'role_id' => 'Role ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerObject()
    {
        return $this->hasOne(WorkerObject::className(), ['id' => 'worker_object_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRole()
    {
        return $this->hasOne(Role::className(), ['id' => 'role_id']);
    }
}
