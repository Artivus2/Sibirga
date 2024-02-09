<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "podgroup_dep_worker".
 *
 * @property int $id
 * @property int $podgroup_department_id
 * @property int $worker_id
 *
 * @property PodgroupDep $podgroupDepartment
 * @property Worker $worker
 */
class PodgroupDepWorker extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'podgroup_dep_worker';
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
            [['podgroup_department_id', 'worker_id'], 'required'],
            [['podgroup_department_id', 'worker_id'], 'integer'],
            [['podgroup_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => PodgroupDep::className(), 'targetAttribute' => ['podgroup_department_id' => 'id']],
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
            'podgroup_department_id' => 'Podgroup Department ID',
            'worker_id' => 'Worker ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPodgroupDepartment()
    {
        return $this->hasOne(PodgroupDep::className(), ['id' => 'podgroup_department_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
