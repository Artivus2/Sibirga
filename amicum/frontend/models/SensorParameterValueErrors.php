<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sensor_parameter_value_errors".
 *
 * @property int $id
 * @property int $sensor_parameter_id
 * @property string $date_time колонка с микрокодомDATETIME(6)
 * @property string $value
 * @property int $status_id
 *
 * @property SensorParameter $sensorParameter
 * @property Status $status
 */
class SensorParameterValueErrors extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sensor_parameter_value_errors';
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
            [['sensor_parameter_id', 'date_time', 'value', 'status_id'], 'required'],
            [['sensor_parameter_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['value'], 'string', 'max' => 255],
            [['sensor_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => SensorParameter::className(), 'targetAttribute' => ['sensor_parameter_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
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
            'date_time' => 'колонка с микрокодомDATETIME(6)',
            'value' => 'Value',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensorParameter()
    {
        return $this->hasOne(SensorParameter::className(), ['id' => 'sensor_parameter_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}