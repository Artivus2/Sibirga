<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "view_positioning_sensors_with_workers".
 *
 * @property int $sensor_id
 * @property string $sensor_title
 * @property string $network_id
 * @property string $staff_number табельный номер
 * @property int $worker_id
 * @property string $position_title название должности
 * @property string $full_name
 * @property int $company_id
 * @property string $company_title
 * @property int $department_id
 * @property string $department_title
 */
class ViewPositioningSensorsWithWorkers extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'view_positioning_sensors_with_workers';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sensor_id', 'sensor_title', 'network_id'], 'required'],
            [['sensor_id', 'worker_id', 'company_id', 'department_id'], 'integer'],
            [['sensor_title', 'network_id', 'position_title', 'company_title', 'department_title'], 'string', 'max' => 255],
            [['staff_number'], 'string', 'max' => 20],
            [['full_name'], 'string', 'max' => 152],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'sensor_id' => 'Sensor ID',
            'sensor_title' => 'Sensor Title',
            'network_id' => 'Network ID',
            'staff_number' => 'табельный номер',
            'worker_id' => 'Worker ID',
            'position_title' => 'название должности',
            'full_name' => 'Full Name',
            'company_id' => 'Company ID',
            'company_title' => 'Company Title',
            'department_id' => 'Department ID',
            'department_title' => 'Department Title',
        ];
    }
}
