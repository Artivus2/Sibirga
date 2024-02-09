<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sensor_parameter_sensor".
 *
 * @property int $id
 * @property int $sensor_parameter_id
 * @property int $sensor_parameter_id_source
 * @property string $date_time
 *
 * @property SensorParameter $sensorParameter
 */
class SensorParameterSensor extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sensor_parameter_sensor';
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
            [['sensor_parameter_id', 'sensor_parameter_id_source', 'date_time'], 'required'],
            [['sensor_parameter_id', 'sensor_parameter_id_source'], 'integer'],
            [['date_time'], 'safe'],
            [['sensor_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => SensorParameter::className(), 'targetAttribute' => ['sensor_parameter_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sensor_parameter_id' => 'Sensor Parameter ID',
            'sensor_parameter_id_source' => 'Sensor Parameter Id Source',
            'date_time' => 'Date Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensorParameter()
    {
        return $this->hasOne(SensorParameter::className(), ['id' => 'sensor_parameter_id']);
    }
}
