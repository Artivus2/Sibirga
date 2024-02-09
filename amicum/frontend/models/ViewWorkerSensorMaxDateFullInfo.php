<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "view_worker_sensor_maxDate_fullInfo".
 *
 * @property int $sensor_id
 * @property string $network_id
 * @property int $worker_id
 * @property int $worker_object_id Ключ таблицы классификации работников по типовым объектам АМИКУМ
 * @property int $object_id внешний ключ типового объекта
 */
class ViewWorkerSensorMaxDateFullInfo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'view_worker_sensor_maxDate_fullInfo';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sensor_id', 'network_id', 'object_id'], 'required'],
            [['sensor_id', 'worker_id', 'worker_object_id', 'object_id'], 'integer'],
            [['network_id'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'sensor_id' => 'Sensor ID',
            'network_id' => 'Network ID',
            'worker_id' => 'Worker ID',
            'worker_object_id' => 'Worker Object ID',
            'object_id' => 'Object ID',
        ];
    }

    /**
     * {@inheritdoc}
     * @return ViewWorkerSensorMaxDateFullInfoQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ViewWorkerSensorMaxDateFullInfoQuery(get_called_class());
    }
}
