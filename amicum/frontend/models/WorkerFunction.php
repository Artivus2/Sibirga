<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "worker_function".
 *
 * @property int $id
 * @property int $worker_object_id
 * @property int $function_id
 *
 * @property Func $function
 * @property WorkerObject $workerObject
 */
class WorkerFunction extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_function';
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
            [['worker_object_id', 'function_id'], 'required'],
            [['worker_object_id', 'function_id'], 'integer'],
            [['function_id'], 'exist', 'skipOnError' => true, 'targetClass' => Func::className(), 'targetAttribute' => ['function_id' => 'id']],
            [['worker_object_id'], 'exist', 'skipOnError' => true, 'targetClass' => WorkerObject::className(), 'targetAttribute' => ['worker_object_id' => 'id']],
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
            'function_id' => 'Function ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFunction()
    {
        return $this->hasOne(Func::className(), ['id' => 'function_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerObject()
    {
        return $this->hasOne(WorkerObject::className(), ['id' => 'worker_object_id']);
    }
}
