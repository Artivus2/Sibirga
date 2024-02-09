<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "group_dep_worker".
 *
 * @property int $id
 * @property int $group_department_configuration_id
 * @property int $worker_id
 *
 * @property GroupDepConfig $groupDepartmentConfiguration
 * @property Worker $worker
 */
class GroupDepWorker extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'group_dep_worker';
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
            [['group_department_configuration_id', 'worker_id'], 'required'],
            [['group_department_configuration_id', 'worker_id'], 'integer'],
            [['group_department_configuration_id', 'worker_id'], 'unique', 'targetAttribute' => ['group_department_configuration_id', 'worker_id']],
            [['group_department_configuration_id'], 'exist', 'skipOnError' => true, 'targetClass' => GroupDepConfig::className(), 'targetAttribute' => ['group_department_configuration_id' => 'id']],
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
            'group_department_configuration_id' => 'Group Department Configuration ID',
            'worker_id' => 'Worker ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupDepartmentConfiguration()
    {
        return $this->hasOne(GroupDepConfig::className(), ['id' => 'group_department_configuration_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
