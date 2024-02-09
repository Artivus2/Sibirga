<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "view_initWorkerParameterHandbookValue".
 *
 * @property int $worker_id внешний ключ работника
 * @property int $worker_parameter_id
 * @property int $parameter_id
 * @property int $parameter_type_id
 * @property string $date_time DATETIME(3)DATETIME(6
 * @property string $value
 * @property int $status_id
 */
class ViewInitWorkerParameterHandbookValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'view_initWorkerParameterHandbookValue';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['worker_id', 'parameter_id', 'parameter_type_id'], 'required'],
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
            'worker_id' => 'внешний ключ работника',
            'worker_parameter_id' => 'Worker Parameter ID',
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
            'date_time' => 'DATETIME(3)DATETIME(6',
            'value' => 'Value',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * {@inheritdoc}
     * @return ViewInitWorkerParameterHandbookValueQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ViewInitWorkerParameterHandbookValueQuery(get_called_class());
    }
}
