<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "summary_report_sensor_gas_concentration".
 *
 * @property int $id
 * @property int $sensor_id
 * @property string $sensor_title
 * @property int $parameter_id
 * @property string $gas_fact_value
 * @property string $edge_gas_nominal_value
 * @property string $date_time
 * @property string $edge_id
 * @property string $place_title
 * @property string $unit_title
 * @property int $place_id
 * @property string $parameter_title
 */
class SummaryReportSensorGasConcentration extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'summary_report_sensor_gas_concentration';
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
            [['sensor_id', 'parameter_id', 'place_id'], 'integer'],
            [['date_time'], 'safe'],
            [['sensor_title', 'place_title', 'parameter_title'], 'string', 'max' => 255],
            [['gas_fact_value', 'edge_gas_nominal_value', 'edge_id', 'unit_title'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sensor_id' => 'Sensor ID',
            'sensor_title' => 'Sensor Title',
            'parameter_id' => 'Parameter ID',
            'gas_fact_value' => 'Gas Fact Value',
            'edge_gas_nominal_value' => 'Edge Gas Nominal Value',
            'date_time' => 'Date Time',
            'edge_id' => 'Edge ID',
            'place_title' => 'Place Title',
            'unit_title' => 'Unit Title',
            'place_id' => 'Place ID',
            'parameter_title' => 'Parameter Title',
        ];
    }
}
