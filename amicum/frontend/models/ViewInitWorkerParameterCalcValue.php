<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "view_initWorkerParameterCalcValue".
 *
 * @property int $worker_id внешний ключ работника
 * @property int $worker_parameter_id
 * @property int $parameter_id
 * @property int $parameter_type_id
 * @property string $date_time дата с микросекундамиDATETIME(6)DATETIME(6
 * @property string $value
 * @property int $status_id
 */
class ViewInitWorkerParameterCalcValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'view_initWorkerParameterCalcValue';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['worker_id', 'worker_parameter_id', 'parameter_id', 'parameter_type_id', 'date_time', 'value', 'status_id'], 'required'],
            [['worker_id', 'worker_parameter_id', 'parameter_id', 'parameter_type_id', 'status_id'], 'integer'],
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
            'worker_id' => 'Worker ID',
            'worker_parameter_id' => 'Worker Parameter ID',
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
            'date_time' => 'Date Time',
            'value' => 'Value',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * {@inheritdoc}
     * @return ViewInitWorkerParameterCalcValueQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ViewInitWorkerParameterCalcValueQuery(get_called_class());
    }
}
