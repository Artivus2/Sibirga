<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "worker_parameter_sensor".
 *
 * @property int $id
 * @property int $worker_parameter_id
 * @property int $sensor_id
 * @property string $date_time
 * @property int $type_relation_sensor 0 - резервная лампа\\n1- постоянная лампа
 *
 * @property WorkerParameter $workerParameter
 */
class WorkerParameterSensor extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_parameter_sensor';
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
            [['worker_parameter_id', 'sensor_id', 'date_time'], 'required'],
            [['worker_parameter_id', 'sensor_id', 'type_relation_sensor'], 'integer'],
            [['date_time'], 'safe'],
            [['worker_parameter_id', 'sensor_id', 'date_time'], 'unique', 'targetAttribute' => ['worker_parameter_id', 'sensor_id', 'date_time']],
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
            'sensor_id' => 'Sensor ID',
            'date_time' => 'Date Time',
            'type_relation_sensor' => '0 - резервная лампа\\\\n1- постоянная лампа',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerParameter()
    {
        return $this->hasOne(WorkerParameter::className(), ['id' => 'worker_parameter_id']);
    }
}
