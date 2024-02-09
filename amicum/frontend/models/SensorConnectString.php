<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sensor_connect_string".
 *
 * @property int $id
 * @property int $sensor_id
 * @property int $connect_string_id
 * @property string $date_time
 *
 * @property ConnectString $connectString
 * @property Sensor $sensor
 */
class SensorConnectString extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sensor_connect_string';
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
            [['sensor_id', 'connect_string_id', 'date_time'], 'required'],
            [['sensor_id', 'connect_string_id'], 'integer'],
            [['date_time'], 'safe'],
            [['connect_string_id'], 'unique'],
            [['connect_string_id'], 'exist', 'skipOnError' => true, 'targetClass' => ConnectString::className(), 'targetAttribute' => ['connect_string_id' => 'id']],
            [['sensor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Sensor::className(), 'targetAttribute' => ['sensor_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sensor_id' => 'Sensor ID',
            'connect_string_id' => 'Connect String ID',
            'date_time' => 'Date Time',
        ];
    }

    /**
     * Gets query for [[ConnectString]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getConnectString()
    {
        return $this->hasOne(ConnectString::className(), ['id' => 'connect_string_id']);
    }

    /**
     * Gets query for [[Sensor]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSensor()
    {
        return $this->hasOne(Sensor::className(), ['id' => 'sensor_id']);
    }
}
