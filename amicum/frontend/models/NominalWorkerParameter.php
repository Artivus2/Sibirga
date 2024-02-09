<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "nominal_worker_parameter".
 *
 * @property int $id
 * @property int $worker_parameter_id
 * @property string $date_nominal
 * @property string $value_nominal
 * @property string $up_down
 * @property int $status_id
 * @property int $sensor_id
 *
 * @property Sensor $sensor
 * @property Status $status
 * @property WorkerParameter $workerParameter
 */
class NominalWorkerParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'nominal_worker_parameter';
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
            [['worker_parameter_id', 'date_nominal', 'value_nominal', 'up_down', 'status_id', 'sensor_id'], 'required'],
            [['worker_parameter_id', 'status_id', 'sensor_id'], 'integer'],
            [['date_nominal'], 'safe'],
            [['value_nominal'], 'string', 'max' => 255],
            [['up_down'], 'string', 'max' => 10],
            [['sensor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Sensor::className(), 'targetAttribute' => ['sensor_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['worker_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => WorkerParameter::className(), 'targetAttribute' => ['worker_parameter_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'worker_parameter_id' => 'Worker Parameter ID',
            'date_nominal' => 'Date Nominal',
            'value_nominal' => 'Value Nominal',
            'up_down' => 'Up Down',
            'status_id' => 'Status ID',
            'sensor_id' => 'Sensor ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensor()
    {
        return $this->hasOne(Sensor::className(), ['id' => 'sensor_id']);
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
    public function getWorkerParameter()
    {
        return $this->hasOne(WorkerParameter::className(), ['id' => 'worker_parameter_id']);
    }
}
