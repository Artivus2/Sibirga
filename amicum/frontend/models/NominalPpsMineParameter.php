<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "nominal_pps_mine_parameter".
 *
 * @property int $id
 * @property int $pps_mine_parameter_id
 * @property string $date_nominal
 * @property string $value_nominal
 * @property string $up_down
 * @property int $sensor_id
 * @property int $status_id
 * @property int $event_id
 *
 * @property Event $event
 * @property PpsMineParameter $ppsMineParameter
 * @property Sensor $sensor
 * @property Status $status
 */
class NominalPpsMineParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'nominal_pps_mine_parameter';
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
            [['pps_mine_parameter_id', 'date_nominal', 'value_nominal', 'up_down', 'sensor_id', 'status_id', 'event_id'], 'required'],
            [['pps_mine_parameter_id', 'sensor_id', 'status_id', 'event_id'], 'integer'],
            [['date_nominal'], 'safe'],
            [['value_nominal'], 'string', 'max' => 255],
            [['up_down'], 'string', 'max' => 10],
            [['event_id'], 'exist', 'skipOnError' => true, 'targetClass' => Event::className(), 'targetAttribute' => ['event_id' => 'id']],
            [['pps_mine_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => PpsMineParameter::className(), 'targetAttribute' => ['pps_mine_parameter_id' => 'id']],
            [['sensor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Sensor::className(), 'targetAttribute' => ['sensor_id' => 'id']],
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
            'pps_mine_parameter_id' => 'Pps Mine Parameter ID',
            'date_nominal' => 'Date Nominal',
            'value_nominal' => 'Value Nominal',
            'up_down' => 'Up Down',
            'sensor_id' => 'Sensor ID',
            'status_id' => 'Status ID',
            'event_id' => 'Event ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEvent()
    {
        return $this->hasOne(Event::className(), ['id' => 'event_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPpsMineParameter()
    {
        return $this->hasOne(PpsMineParameter::className(), ['id' => 'pps_mine_parameter_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensor()
    {
        return $this->hasOne(Sensor::className(), ['id' => 'sensor_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
