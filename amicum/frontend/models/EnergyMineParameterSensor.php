<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "energy_mine_parameter_sensor".
 *
 * @property int $id
 * @property int $energy_mine_parameter_id
 * @property int $sensor_id
 * @property string $date_time
 *
 * @property EnergyMineParameter $energyMineParameter
 */
class EnergyMineParameterSensor extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'energy_mine_parameter_sensor';
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
            [['energy_mine_parameter_id', 'sensor_id', 'date_time'], 'required'],
            [['energy_mine_parameter_id', 'sensor_id'], 'integer'],
            [['date_time'], 'safe'],
            [['energy_mine_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => EnergyMineParameter::className(), 'targetAttribute' => ['energy_mine_parameter_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'energy_mine_parameter_id' => 'Energy Mine Parameter ID',
            'sensor_id' => 'Sensor ID',
            'date_time' => 'Date Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnergyMineParameter()
    {
        return $this->hasOne(EnergyMineParameter::className(), ['id' => 'energy_mine_parameter_id']);
    }
}
