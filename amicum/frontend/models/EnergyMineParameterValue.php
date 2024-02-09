<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "energy_mine_parameter_value".
 *
 * @property int $id
 * @property int $energy_mine_parameter_id
 * @property string $date_time DATETIME(3)DATETIME(3)DATETIME(6)
 * @property string $value
 * @property int $status_id
 *
 * @property EnergyMineParameter $energyMineParameter
 * @property Status $status
 */
class EnergyMineParameterValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'energy_mine_parameter_value';
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
            [['energy_mine_parameter_id', 'date_time', 'value', 'status_id'], 'required'],
            [['energy_mine_parameter_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['value'], 'string', 'max' => 255],
            [['energy_mine_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => EnergyMineParameter::className(), 'targetAttribute' => ['energy_mine_parameter_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
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
            'date_time' => 'DATETIME(3)DATETIME(3)DATETIME(6)',
            'value' => 'Value',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnergyMineParameter()
    {
        return $this->hasOne(EnergyMineParameter::className(), ['id' => 'energy_mine_parameter_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
