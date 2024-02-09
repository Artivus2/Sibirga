<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "pps_mine_parameter_sensor".
 *
 * @property int $id
 * @property int $pps_mine_parameter_id
 * @property int $sensor_id
 * @property string $date_time
 *
 * @property PpsMineParameter $ppsMineParameter
 */
class PpsMineParameterSensor extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pps_mine_parameter_sensor';
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
            [['pps_mine_parameter_id', 'sensor_id', 'date_time'], 'required'],
            [['pps_mine_parameter_id', 'sensor_id'], 'integer'],
            [['date_time'], 'safe'],
            [['pps_mine_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => PpsMineParameter::className(), 'targetAttribute' => ['pps_mine_parameter_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pps_mine_parameter_id' => 'Pps Mine Parameter ID',
            'sensor_id' => 'Sensor ID',
            'date_time' => 'Date Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPpsMineParameter()
    {
        return $this->hasOne(PpsMineParameter::className(), ['id' => 'pps_mine_parameter_id']);
    }
}
