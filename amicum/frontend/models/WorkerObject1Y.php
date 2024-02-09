<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "worker_object1".
 *
 * @property int $id
 * @property int $worker_id
 * @property int $object_id
 * @property int $role_id
 */
class WorkerObject1Y extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_object1';
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
}
