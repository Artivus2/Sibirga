<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sensor_parameter_value_temp".
 *
 * @property int $id
 * @property int $sensor_parameter_id
 * @property string $date_time колонка с микрокодомDATETIME(6)DATETIME(6)
 * @property string $value
 * @property int $status_id
 */
class SensorParameterValueTemp extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sensor_parameter_value_temp';
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
            [['sensor_parameter_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['value'], 'string', 'max' => 255],
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
            'date_time' => 'колонка с микрокодомDATETIME(6)DATETIME(6)',
            'value' => 'Value',
            'status_id' => 'Status ID',
        ];
    }
}
