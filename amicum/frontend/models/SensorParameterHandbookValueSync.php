<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "{{%view_sensor_parameter_handbook_value}}".
 *
 * @property int $sensor_id
 * @property int $sensor_parameter_id
 * @property int $parameter_id
 * @property int $parameter_type_id
 * @property string $value
 * @property string $date_time DATETIME(3)DATETIME(6
 */
class SensorParameterHandbookValueSync extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%view_sensor_parameter_handbook_value}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_source');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sensor_id', 'parameter_id', 'parameter_type_id', 'value', 'date_time'], 'required'],
            [['sensor_id', 'sensor_parameter_id', 'parameter_id', 'parameter_type_id'], 'integer'],
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
            'sensor_id' => 'Sensor ID',
            'sensor_parameter_id' => 'Sensor Parameter ID',
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
            'value' => 'Value',
            'date_time' => 'DATETIME(3)DATETIME(6',
        ];
    }
}
