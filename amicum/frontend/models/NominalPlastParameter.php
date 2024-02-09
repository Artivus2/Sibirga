<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "nominal_plast_parameter".
 *
 * @property int $id
 * @property int $plast_parameter_id
 * @property string $date_nominal
 * @property string $value_nominal
 * @property string $up_down
 * @property int $status_id
 * @property int $sensor_id
 * @property int $event_id
 *
 * @property Event $event
 * @property PlastParameter $plastParameter
 * @property Sensor $sensor
 * @property Status $status
 */
class NominalPlastParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'nominal_plast_parameter';
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
            [['plast_parameter_id', 'date_nominal', 'value_nominal', 'up_down', 'status_id', 'sensor_id', 'event_id'], 'required'],
            [['plast_parameter_id', 'status_id', 'sensor_id', 'event_id'], 'integer'],
            [['date_nominal'], 'safe'],
            [['value_nominal'], 'string', 'max' => 255],
            [['up_down'], 'string', 'max' => 10],
            [['event_id'], 'exist', 'skipOnError' => true, 'targetClass' => Event::className(), 'targetAttribute' => ['event_id' => 'id']],
            [['plast_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlastParameter::className(), 'targetAttribute' => ['plast_parameter_id' => 'id']],
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
            'plast_parameter_id' => 'Plast Parameter ID',
            'date_nominal' => 'Date Nominal',
            'value_nominal' => 'Value Nominal',
            'up_down' => 'Up Down',
            'status_id' => 'Status ID',
            'sensor_id' => 'Sensor ID',
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
    public function getPlastParameter()
    {
        return $this->hasOne(PlastParameter::className(), ['id' => 'plast_parameter_id']);
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
