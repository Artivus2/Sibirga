<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "equipment_parameter_sensor".
 *
 * @property int $id
 * @property int $equipment_parameter_id
 * @property int $sensor_id
 * @property string $date_time
 *
 * @property EquipmentParameter $equipmentParameter
 */
class EquipmentParameterSensor extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'equipment_parameter_sensor';
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
            [['equipment_parameter_id', 'sensor_id', 'date_time'], 'required'],
            [['equipment_parameter_id', 'sensor_id'], 'integer'],
            [['date_time'], 'safe'],
            [['equipment_parameter_id', 'sensor_id', 'date_time'], 'unique', 'targetAttribute' => ['equipment_parameter_id', 'sensor_id', 'date_time']],
            [['equipment_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => EquipmentParameter::className(), 'targetAttribute' => ['equipment_parameter_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'equipment_parameter_id' => 'Equipment Parameter ID',
            'sensor_id' => 'Sensor ID',
            'date_time' => 'Date Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEquipmentParameter()
    {
        return $this->hasOne(EquipmentParameter::className(), ['id' => 'equipment_parameter_id']);
    }
}
