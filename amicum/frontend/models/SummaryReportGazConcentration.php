<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "summary_report_gaz_concentration".
 *
 * @property int $id
 * @property string $date_work
 * @property string $place_title
 * @property string $sensor_title
 * @property string $parameter_title
 * @property string $unit_title
 * @property int $gas_parameter_id
 * @property int $gas_status_id
 * @property string $gas_status_title
 * @property string $smena1fact
 * @property string $smena2fact
 * @property string $smena3fact
 * @property string $smena4fact
 * @property string $smena_plan
 * @property int $sensor_id
 */
class SummaryReportGazConcentration extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'summary_report_gaz_concentration';
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
            [['date_work'], 'safe'],
            [['gas_parameter_id', 'gas_status_id', 'sensor_id'], 'integer'],
            [['place_title'], 'string', 'max' => 250],
            [['sensor_title', 'parameter_title', 'gas_status_title', 'smena1fact', 'smena2fact', 'smena3fact', 'smena4fact', 'smena_plan'], 'string', 'max' => 255],
            [['unit_title'], 'string', 'max' => 15],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date_work' => 'Date Work',
            'place_title' => 'Place Title',
            'sensor_title' => 'Sensor Title',
            'parameter_title' => 'Parameter Title',
            'unit_title' => 'Unit Title',
            'gas_parameter_id' => 'Gas Parameter ID',
            'gas_status_id' => 'Gas Status ID',
            'gas_status_title' => 'Gas Status Title',
            'smena1fact' => 'Smena1fact',
            'smena2fact' => 'Smena2fact',
            'smena3fact' => 'Smena3fact',
            'smena4fact' => 'Smena4fact',
            'smena_plan' => 'Smena Plan',
            'sensor_id' => 'Sensor ID',
        ];
    }
}
