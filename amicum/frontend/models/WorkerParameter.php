<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "worker_parameter".
 *
 * @property int $id
 * @property int $worker_object_id
 * @property int $parameter_id
 * @property int $parameter_type_id
 *
 * @property NominalWorkerParameter[] $nominalWorkerParameters
 * @property WorkerObject $workerObject
 * @property WorkerParameterCalcValue[] $workerParameterCalcValues
 * @property WorkerParameterHandbookValue[] $workerParameterHandbookValues
 * @property WorkerParameterSensor[] $workerParameterSensors
 * @property WorkerParameterValue[] $workerParameterValues
 * @property WorkerParameterValueTemp[] $workerParameterValueTemps
 * @property WorkerParameterValueTempSnapshot[] $workerParameterValueTempSnapshots
 */
class WorkerParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_parameter';
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
            [['worker_object_id', 'parameter_id', 'parameter_type_id'], 'required'],
            [['worker_object_id', 'parameter_id', 'parameter_type_id'], 'integer'],
            [['worker_object_id', 'parameter_id', 'parameter_type_id'], 'unique', 'targetAttribute' => ['worker_object_id', 'parameter_id', 'parameter_type_id']],
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
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalWorkerParameters()
    {
        return $this->hasMany(NominalWorkerParameter::className(), ['worker_parameter_id' => 'id']);
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
    public function getWorkerParameterCalcValues()
    {
        return $this->hasMany(WorkerParameterCalcValue::className(), ['worker_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerParameterHandbookValues()
    {
        return $this->hasMany(WorkerParameterHandbookValue::className(), ['worker_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerParameterSensors()
    {
        return $this->hasMany(WorkerParameterSensor::className(), ['worker_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerParameterValues()
    {
        return $this->hasMany(WorkerParameterValue::className(), ['worker_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerParameterValueTemps()
    {
        return $this->hasMany(WorkerParameterValueTemp::className(), ['worker_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerParameterValueTempSnapshots()
    {
        return $this->hasMany(WorkerParameterValueTempSnapshot::className(), ['worker_parameter_id' => 'id']);
    }

    public function getParameterType()
    {
        return $this->hasOne(ParameterType::className(), ['id' => 'parameter_type_id']);
    }
}
