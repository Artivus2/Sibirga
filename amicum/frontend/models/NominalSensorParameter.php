<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "nominal_sensor_parameter".
 *
 * @property int $id
 * @property int $sensor_parameter_id
 * @property string $value_nominal
 * @property string $date_nominal
 * @property int $event_id
 * @property string $up_down
 *
 * @property Event $event
 * @property SensorParameter $sensorParameter
 */
class NominalSensorParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'nominal_sensor_parameter';
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
            [['sensor_parameter_id', 'value_nominal', 'date_nominal', 'event_id', 'up_down'], 'required'],
            [['sensor_parameter_id', 'event_id'], 'integer'],
            [['date_nominal'], 'safe'],
            [['value_nominal'], 'string', 'max' => 255],
            [['up_down'], 'string', 'max' => 10],
            [['event_id'], 'exist', 'skipOnError' => true, 'targetClass' => Event::className(), 'targetAttribute' => ['event_id' => 'id']],
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
            'value_nominal' => 'Value Nominal',
            'date_nominal' => 'Date Nominal',
            'event_id' => 'Event ID',
            'up_down' => 'Up Down',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEvent()
    {
        return $this->hasOne(Event::className(), ['id' => 'event_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensorParameter()
    {
        return $this->hasOne(SensorParameter::className(), ['id' => 'sensor_parameter_id']);
    }
}
