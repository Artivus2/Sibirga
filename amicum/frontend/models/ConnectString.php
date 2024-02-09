<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "connect_string".
 *
 * @property int $id
 * @property string $title
 * @property string $ip
 * @property string $connect_string
 * @property int $Settings_DCS_id
 * @property string $source_type Тип соединения (ModBus,OPC,Сокет-сервер Strata,SNMP)
 *
 * @property SettingsDcs $settingsDCS
 * @property SensorConnectString[] $sensorConnectString
 * @property Trigger[] $triggers
 */
class ConnectString extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'connect_string';
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
            [['title', 'ip', 'connect_string', 'source_type'], 'required'],
            [['Settings_DCS_id'], 'integer'],
            [['title', 'connect_string'], 'string', 'max' => 255],
            [['ip'], 'string', 'max' => 15],
            [['source_type'], 'string', 'max' => 20],
            [['Settings_DCS_id'], 'exist', 'skipOnError' => true, 'targetClass' => SettingsDCS::className(), 'targetAttribute' => ['Settings_DCS_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'ip' => 'Ip',
            'connect_string' => 'Connect String',
            'Settings_DCS_id' => 'Settings Dcs ID',
            'source_type' => 'Тип соединения (ModBus,OPC,Сокет-сервер Strata,SNMP)',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSettingsDCS()
    {
        return $this->hasOne(SettingsDCS::className(), ['id' => 'Settings_DCS_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensorConnectString()
    {
        return $this->hasOne(SensorConnectString::className(), ['connect_string_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTriggers()
    {
        return $this->hasMany(Trigger::className(), ['connect_string_id' => 'id']);
    }
}
