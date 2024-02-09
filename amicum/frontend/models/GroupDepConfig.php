<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "group_dep_config".
 *
 * @property int $id Идентификатор таблицы
 * @property int $group_dep_id Внешний ключ к списку бригад
 * @property int $status_id Внешний ключ к списку статусов
 * @property string $date_time Дата и время создания конфигурации
 * @property int $brigader_id
 *
 * @property GroupDep $groupDep
 * @property Status $status
 * @property Worker $brigader
 * @property GroupDepWorker[] $groupDepWorkers
 * @property Worker[] $workers
 * @property PodgroupDep[] $podgroupDeps
 */
class GroupDepConfig extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'group_dep_config';
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
            [['group_dep_id', 'status_id', 'date_time', 'brigader_id'], 'required'],
            [['group_dep_id', 'status_id', 'brigader_id'], 'integer'],
            [['date_time'], 'safe'],
            [['group_dep_id', 'date_time', 'brigader_id'], 'unique', 'targetAttribute' => ['group_dep_id', 'date_time', 'brigader_id']],
            [['group_dep_id'], 'exist', 'skipOnError' => true, 'targetClass' => GroupDep::className(), 'targetAttribute' => ['group_dep_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['brigader_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['brigader_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'group_dep_id' => 'Group Dep ID',
            'status_id' => 'Status ID',
            'date_time' => 'Date Time',
            'brigader_id' => 'Brigader ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupDep()
    {
        return $this->hasOne(GroupDep::className(), ['id' => 'group_dep_id']);
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
    public function getBrigader()
    {
        return $this->hasOne(Worker::className(), ['id' => 'brigader_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupDepWorkers()
    {
        return $this->hasMany(GroupDepWorker::className(), ['group_department_configuration_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkers()
    {
        return $this->hasMany(Worker::className(), ['id' => 'worker_id'])->viaTable('group_dep_worker', ['group_department_configuration_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPodgroupDeps()
    {
        return $this->hasMany(PodgroupDep::className(), ['group_dep_id' => 'id']);
    }
}
